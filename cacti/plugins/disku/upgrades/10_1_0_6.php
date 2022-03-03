<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
 |                                                                         |
 | Licensed under the Apache License, Version 2.0 (the "License");         |
 | you may not use this file except in compliance with the License.        |
 | You may obtain a copy of the License at                                 |
 |                                                                         |
 | http://www.apache.org/licenses/LICENSE-2.0                              |
 |                                                                         |
 | Unless required by applicable law or agreed to in writing, software     |
 | distributed under the License is distributed on an "AS IS" BASIS,       |
 | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.|
 | See the License for the specific language governing permissions and     |
 | limitations under the License.                                          |
 +-------------------------------------------------------------------------+
*/

function upgrade_to_10_1_0_6() {
    global $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_functions.php');
    include_once(dirname(__FILE__) . '/../../grid/include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	cacti_log('NOTE: Upgrading disku to v10.1.0.6 ...', true, 'UPGRADE');

	cacti_log('NOTE: Importing DiskU device templates ...', true, 'UPGRADE');

	$disku_templates = array(
		'4' => array (
			'value' => 'Disku Filesystem - Host Template',
			'name' => 'cacti_host_template_disk_filesystem_host.xml'
		),
	);
	foreach($disku_templates as $disku_template) {
		if (file_exists(dirname(__FILE__) . '/../templates/upgrades/10_1_0_6/' . $disku_template['name'])) {
			cacti_log('NOTE: Importing ' . $disku_template['value'], true, 'UPGRADE');
			$results = rtm_do_import(dirname(__FILE__) . '/../templates/upgrades/10_1_0_6/' . $disku_template['name']);
		}
	}

	cacti_log('Disku Templates Import Complete.', true, 'UPGRADE');

	//For SUP#189661
	execute_sql("Append user auth realm of Disku Administrator for build-in Admin", "REPLACE INTO `user_auth_realm` VALUES ('8902',1);");

	$column_arr= array(
		'df_collect_flag' => "ADD COLUMN `df_collect_flag` char(3) DEFAULT '' AFTER `disabled`",
		'cacti_host' => "ADD COLUMN `cacti_host` int(10) unsigned NOT NULL DEFAULT '0' AFTER `df_collect_flag`"
		);
	add_columns("disku_pollers", $column_arr);

	// host information for default device
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='0d2005384e7a4fa6c63dd3d78d82abed'");
	$snmp_community   = '';
	$snmp_version     = 0;
	$snmp_username    = '';
	$snmp_password    = '';
	$snmp_port        = 161;
	$snmp_timeout     = 500;
	$avail_method     = '0';
	$ping_method      = 2;
	$ping_port        = 0;
	$ping_timeout     = 400;
	$ping_retries     = 1;
	$disabled         = '';
	$notes            = 'This device includes disk file system graphs';
	$auth_protocol    = '';
	$priv_passphrase  = '';
	$priv_protocol    = '';
	$snmp_context     = '';
	$max_oids         = 5;
	$filesystem_snmp_query_id = db_fetch_cell("select id from snmp_query where hash='ad574050ad411342d40035133cdef860';");
	$disku_pollers = db_fetch_assoc("SELECT id, hostname, cacti_host FROM disku_pollers ORDER BY id ASC;");

	cacti_log('Upgrade Disku device related graphs to each standalone device', true, 'UPGRADE');;

	foreach ($disku_pollers as $disku_poller) {
		if (empty($disku_poller['cacti_host'])) {
			$device_name = 'Disku_'. $disku_poller['hostname'];
			$host_id = api_device_save('0', $host_template_id, $device_name, $disku_poller['hostname'],
				$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
				$avail_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,$notes,
				$auth_protocol, $priv_passphrase, $priv_protocol, $snmp_context, $max_oids);
			if (!empty($host_id)) {
				db_execute("UPDATE disku_pollers SET cacti_host=$host_id WHERE id=".$disku_poller['id']);
				db_execute("UPDATE data_local
							SET host_id=$host_id
							WHERE snmp_query_id=$filesystem_snmp_query_id AND snmp_index LIKE '".$disku_poller['id'] . "|%'");
				db_execute("UPDATE poller_item
							SET host_id=$host_id, arg1=REPLACE(arg1, ' get ', ' $host_id get ')
							WHERE local_data_id IN (
								SELECT id FROM data_local
								WHERE snmp_query_id=$filesystem_snmp_query_id AND snmp_index LIKE '".$disku_poller['id'] . "|%')");
				db_execute("UPDATE graph_local
							SET host_id=$host_id
							WHERE snmp_query_id=$filesystem_snmp_query_id AND snmp_index LIKE '".$disku_poller['id'] . "|%'");
				db_execute("UPDATE host_snmp_cache
							SET host_id=$host_id
							WHERE snmp_query_id=$filesystem_snmp_query_id AND snmp_index LIKE '".$disku_poller['id'] . "|%'");
			}
		}
	}
	$old_host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='8cb14a5b4c4623801ffbe011191ff9d8'");
	if(!empty($old_host_template_id)) {
		execute_sql("Unassociate data query DISKU - Filesystem Usage",
			"DELETE FROM host_template_snmp_query WHERE snmp_query_id=$filesystem_snmp_query_id AND host_template_id=$old_host_template_id" );
		$old_disku_hosts = db_fetch_assoc("SELECT id FROM host WHERE host_template_id=$old_host_template_id;");
		foreach ($old_disku_hosts as $old_disku_host) {
			if (!empty($old_disku_host['id'])) {
				api_device_dq_remove($old_disku_host['id'], $filesystem_snmp_query_id);
			}
		}
	}
}
