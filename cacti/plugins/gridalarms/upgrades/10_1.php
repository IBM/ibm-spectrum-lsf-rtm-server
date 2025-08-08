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

function upgrade_to_10_1() {
	global $config;

    include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');
    include_once($config['library_path'] . '/rtm_functions.php');
    include_once($config['library_path'] . '/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.1.0.0 ...', true, 'UPGRADE');

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

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridalarms_db_version', '10.1.0.0');");

	return 0;
}
