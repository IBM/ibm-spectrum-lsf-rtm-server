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

function upgrade_to_10_2() {
	global $config;

    include_once($config['library_path'] . '/rtm_functions.php');
    include_once($config['library_path'] . '/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.2.0.0 ...', true, 'UPGRADE');

	// Notification List Extension
	api_plugin_register_hook('gridalarms', 'notify_list_tabs', 'gridalarms_notify_list_tabs', 'includes/notify.php', 1);
	api_plugin_register_hook('gridalarms', 'notify_list_save', 'gridalarms_notify_list_save', 'includes/notify.php', 1);
	api_plugin_register_hook('gridalarms', 'notify_list_form_confirm', 'gridalarms_notify_list_form_confirm', 'includes/notify.php', 1);
	api_plugin_register_hook('gridalarms', 'notify_list_display', 'gridalarms_notify_list_display', 'includes/notify.php', 1);

	// Allow Modifying Thold form logic, javascript and save functions
	api_plugin_register_hook('gridalarms', 'thold_edit_save_thold', 'gridalarms_th_edit_save_thold', 'includes/thold.php', 1);
	api_plugin_register_hook('gridalarms', 'thold_edit_form_array', 'gridalarms_th_edit_form_array', 'includes/thold.php', 1);
	api_plugin_register_hook('gridalarms', 'thold_edit_javascript', 'gridalarms_th_edit_javascript', 'includes/thold.php', 1);

	// Allow Modifying Thold Template form logic, javascript and save functions
	api_plugin_register_hook('gridalarms', 'thold_template_edit_save_thold', 'gridalarms_tht_edit_save_thold', 'includes/thold.php', 1);
	api_plugin_register_hook('gridalarms', 'thold_template_edit_form_array', 'gridalarms_th_edit_form_array', 'includes/thold.php', 1);
	api_plugin_register_hook('gridalarms', 'thold_template_edit_javascript', 'gridalarms_th_edit_javascript', 'includes/thold.php', 1);

	// Allow additional actions based upon Threshold breaches
	api_plugin_register_hook('gridalarms', 'thold_action', 'gridalarms_thold_action', 'includes/thold.php', 1);

	// Common JavaScript and CSS
	api_plugin_register_hook('gridalarms', 'page_head', 'gridalarms_page_head', 'setup.php', 1);

	// Replace Data Source and Thold titles
	api_plugin_register_hook('gridalarms', 'expand_title', 'gridalarms_expand_title', 'includes/thold.php', 1);

	db_execute('UPDATE plugin_realms
		SET file="grid_alarmdb.php", display="View Alerts"
		WHERE plugin="gridalarms" AND file LIKE "%grid_alarmdb.php%"');

	db_execute('UPDATE plugin_realms
		SET file="notify_lists.php,gridalarms_alarm.php,gridalarms_templates.php,gridalarms_alarm_edit.php,gridalarms_template_edit.php", display="Configure Alerts"
		WHERE plugin="gridalarms" AND file LIKE "%gridalarms_templates.php%"');

	$gridalarms_templates = array(
		'1' => array (
			'value' => 'ALERT - Kill Pending Jobs Over Pending Time Limit',
			'name' => 'grid_alarms_kill_pending_jobs_over_pending_time_limit.xml'
		),
		'2' => array (
			'value' => 'ALERT - Kill Pending Jobs Over Eligible Pending Time Limit',
			'name' => 'grid_alarms_kill_pending_Jobs_over_eligible_pending_time_limit.xml'
		)
	);
	foreach($gridalarms_templates as $gridalarms_template) {
		if (file_exists($config['base_path'].'/plugins/gridalarms/templates/upgrades/10_1/'.$gridalarms_template['name'])) {
			cacti_log('NOTE: Importing ' . $gridalarms_template['value'], true, 'UPGRADE');
			$results = gridalarms_do_import($config['base_path'].'/plugins/gridalarms/templates/upgrades/10_1/'.$gridalarms_template['name']);
			if ($results==-1) {
				cacti_log('WARNING: There are invailid/wrong hash in the imported file.', true, 'UPGRADE');
			} elseif ($results==0) {
				cacti_log('NOTE: Import the new template file successfully.', true, 'UPGRADE');

			} elseif ($results==1) {
				cacti_log('NOTE: Overwrote the old template successfully.', true, 'UPGRADE');
			}
		}
	}

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridalarms_db_version', '10.2.0.0');");
    return 0;
}
