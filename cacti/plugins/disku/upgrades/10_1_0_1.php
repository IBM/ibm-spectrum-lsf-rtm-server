<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2022                                          |
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

function upgrade_to_10_1_0_1() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_functions.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');

	cacti_log('NOTE: Upgrading disku to v10.1.0.1 ...', true, 'UPGRADE');

	add_index('disku_groups_members', 'user', 'ADD INDEX `user` (`user`);');

	$disku_templates = array(
		'1' => array (
			'value' => 'Disk Monitoring Host',
			'name' => 'cacti_host_template_disk_monitoring_host.xml'
		)
	);

	foreach($disku_templates as $disku_template) {
		if (file_exists(dirname(__FILE__) . "/../templates/upgrades/10_1_0_1/" . $disku_template['name'])) {
			cacti_log("NOTE: Importing " . $disku_template['value'], true, 'UPGRADE');
			$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_1_0_1/" . $disku_template['name']);
		}
	}

	$disku_hostid = db_fetch_cell("SELECT h.id FROM host AS h INNER JOIN host_template AS ht ON h.host_template_id=ht.id
		WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");
	$disku_org_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='f319b29a29f238701413d1bc1293f173'");

	if (empty($disku_hostid) && empty($disku_org_id)){
		execute_sql("Associate data query 'DISKU - Organization Totals' to host template 'Disk Monitoring Host'",
			"REPLACE INTO host_snmp_query (host_id,snmp_query_id) VALUES ('$disku_hostid', '$disku_org_id')");
	}
}
