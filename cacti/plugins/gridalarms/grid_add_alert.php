#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');

ini_set('max_execution_time', '0');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	$clusterid         = 0;
	$template_id       = 0;
	$users_list        = '';

	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch ($arg) {
		case '-d':
		case '--debug':
			$debug = TRUE;
			break;
		case '--clusterid':
			$clusterid = trim($value);
			if($clusterid==0 && $clusterid != 'all'){
				print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
				display_help();
				return 1;
			}
			break;

		case '--template':
			$template_id = $value;
			if($template_id==0 && $template_id!='all'){
				print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
				display_help();
				return 1;
			}
			break;

		case '-v':
		case '-V':
		case '-h':
		case '--h':
		case '--help':
			display_help();
			return 0;
		case '--list-clusters':
			display_clusters(get_clusters());
			return 0;
		case '--list-alert-templates':
			display_alert_templates(get_alert_templates());
			return 0;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}

	if ($template_id == 'all' && $clusterid == 'all' ){
		create_alert_for_all_cluster_from_all_template();
	}elseif ($template_id == 'all' ){
		create_alert_from_all_templates($clusterid);
	}elseif ($clusterid == 'all' ){
		create_alert_for_all_cluster($template_id);
	} else {
		create_alert_from_templates($template_id, $clusterid);
	}
} else {
	display_help();
	return 0;
}

function get_alert_templates($alert_template_id = '') {
	$alert_templates = array();

	if (strtolower($alert_template_id) == 'all') {
		$sql_where = '';
	}else if (strlen($alert_template_id)) {
		$sql_where = 'WHERE id = ' . $alert_template_id;
	} else {
		$sql_where = '';
	}

	$tmpArray = db_fetch_assoc("SELECT *
		FROM gridalarms_template
		$sql_where
		ORDER BY id");

	if (cacti_sizeof($tmpArray)) {
		foreach ($tmpArray as $alert_template) {
			$alert_templates[$alert_template['id']] = $alert_template;
		}
	}

	return $alert_templates;
}

function display_alert_templates($alert_templates) {
	print "Known Alert Templates:(id, name)\n";

	if (cacti_sizeof($alert_templates)) {
		foreach($alert_templates as $alert_template) {
			print "\t" . $alert_template["id"] . "\t" . $alert_template["name"] . "\n";
		}
	}
}

function display_clusters($clusters) {
	print "Known Clusters:(clusterid, clustername)\n";

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $cluster) {
			print "\t" . $cluster["clusterid"] . "\t" . $cluster["clustername"] . "\n";
		}
	}
}

function create_alert_for_all_cluster_from_all_template(){
	$clusterids = db_fetch_assoc('SELECT clusterid
		FROM grid_clusters
		ORDER BY clusterid');

	if (cacti_sizeof($clusterids)) {
		foreach($clusterids as $clusterid) {
			create_alert_from_all_templates($clusterid['clusterid']);
		}
	}
}

function create_alert_for_all_cluster($template_id){
	$clusterids = db_fetch_assoc('SELECT clusterid
		FROM grid_clusters
		ORDER BY clusterid');

	if (cacti_sizeof($clusterids)) {
		foreach($clusterids as $clusterid) {
			create_alert_from_templates($template_id, $clusterid['clusterid']);
		}
	}
}

function create_alert_from_all_templates($clusterid){
	$tmpl_hashs = array(
		'0f1a584487506ce1e41389c99f377ec8',
		'48540c33b119e45d7ea394ec850cb28c',
		'8101639df814222ff5d5a74319c4add6',
		'b3480d5b7821978c98fd7cd4511f6a76',
		'fce727eb3e8918ae4bfd8e07cc98c1bc'
	);

	foreach($tmpl_hashs as $tmpl_hash) {
		$tmpl_id = db_fetch_cell_prepared('SELECT id
			FROM gridalarms_template
			WHERE hash = ?',
			array($tmpl_hash));

		create_alert_from_templates($tmpl_id, $clusterid);
	}
}

function create_alert_from_templates($template_id, $clusterid){
	$alarm = db_fetch_row_prepared('SELECT hash, alarm_fail_trigger, alarm_hi,
		alarm_low, alarm_type, base_time_display, clusterid,
		email_body, email_subject, expression_id, frequency, name,
		notify_alert, notify_extra, repeat_alert,
		time_fail_length, time_fail_trigger, time_hi, time_low, notify_cluster_admin
		FROM gridalarms_template WHERE id = ?',
		array($template_id));

	$clusteridname = db_fetch_cell_prepared('SELECT clustername
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	print $template_id . "\n";
	print $clusterid . "\n";

	print "NOTE: Associating Alert \"" . $alarm['name'] . "\" to Cluster \"" . $clusteridname . "\"\n";

	if (cacti_sizeof($alarm)) {
		$alarm['template'] = $template_id;
		//Ignore clusterid for License alert template
		if( $alarm['hash'] == '8101639df814222ff5d5a74319c4add6'){
			$alarm['clusterid'] = '';
		} else {
			$alarm['clusterid'] = $clusterid;
		}
		$alarm['hash'] = '';
		$alarm['template_enabled'] = 'on';

		if (read_config_option('gridalarm_disable_legacy') == '') {
			get_users_list($template_id, 'template', $users_list);
			$alarm['notify_accounts'] = $users_list;
		}

		$custom = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_expression_input
			WHERE expression_id = ?',
			array($alarm['expression_id']));

		if (cacti_sizeof($custom)) {
			foreach($custom as $c) {
				$alarm['custom_entry_' . $c['id']] = $c['value'];
			}
		}

		do_title_replacement ($alarm);

		$id = push_out_template_to_alarm($alarm, isset($alarm['template']) ? $alarm['template']:0);
		template_propagation ("gridalarms_alarm", $id);

		if (isset($alarm['notify_accounts']) && trim($alarm['notify_accounts']) != '') {
			alarm_save_contacts($id, $alarm['notify_accounts']);
		}
	} else {
		print "Error: Can not find alert template by Id: " . $template_id . "\n";
		exit;
	}
}

function get_users_list($id, $type = 'alarm', &$users_list = '') {
	if ($type == 'alarm') {
		$alarm = db_fetch_row_prepared('SELECT *
			FROM gridalarms_alarm
			WHERE id = ?',
			array($id));

		$selected_users_where = ' AND plugin_thold_contacts.id IN (
			SELECT contact_id
			FROM gridalarms_alarm_contacts
			WHERE alarm_id = ' . $id . ')';

		$not_selected_users_where = ' AND plugin_thold_contacts.id NOT IN (
			SELECT contact_id
			FROM gridalarms_alarm_contacts
			WHERE alarm_id = ' . $id . ')';
	} else {
		$alarm = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template
			WHERE id = ?',
			array($id));

		$selected_users_where = ' AND plugin_thold_contacts.id IN (
			SELECT contact_id
			FROM gridalarms_template_contacts
			WHERE alarm_id = ' . $id . ')';

		$not_selected_users_where = ' AND plugin_thold_contacts.id NOT IN (
			SELECT contact_id
			FROM gridalarms_template_contacts
			WHERE alarm_id = ' . $id . ')';
	}

	$not_users = db_fetch_assoc("SELECT plugin_thold_contacts.id, plugin_thold_contacts.data,
		plugin_thold_contacts.type, user_auth.full_name
		FROM plugin_thold_contacts, user_auth
		WHERE user_auth.id=plugin_thold_contacts.user_id
		AND plugin_thold_contacts.data!='' $not_selected_users_where
		ORDER BY user_auth.full_name ASC, plugin_thold_contacts.type ASC");

	if ($selected_users_where != '') {
		$users = db_fetch_assoc("SELECT plugin_thold_contacts.id, plugin_thold_contacts.data,
			plugin_thold_contacts.type, user_auth.full_name
			FROM plugin_thold_contacts, user_auth
			WHERE user_auth.id=plugin_thold_contacts.user_id
			AND plugin_thold_contacts.data!=''
			$selected_users_where
			ORDER BY user_auth.full_name ASC, plugin_thold_contacts.type ASC");
	} else {
		$users = array();
	}

	$users_list = '';

	if (!empty($users)) {
		foreach ($users as $users) {
			$users_list .= $users['id'] . ' ';
		}
	}
}

function display_help() {
	print 'RTM Add Alerts Utility ' . read_config_option('gridalarms_db_version') . "\n";

	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	print "This program allows you to manage the addition of only alerts into RTM.\n\n";

	print "usage:\n";
	print "Adding a Alert:\n";
	print "   grid_add_alert.php --clusterid=[ID] --template=[ID]\n\n";

	print "Required for Hosts and Data Queries:\n";
	print "    --clusterid    the clusterid for the cluster that this device belongs to\n";
	print "    --template     number (read below to get a list of templates)\n\n";

	print "List Options:\n";
	print "    --list-clusters\n";
	print "    --list-alert-templates\n";
}

