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

function upgrade_to_10_1_0_6() {
    global $config;

    include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');
    include_once($config['library_path'] . '/rtm_functions.php');
    include_once($config['library_path'] . '/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.1.0.6 ...', true, 'UPGRADE');

	$gridalarms_templates = array(
		'1' => array (
			'value' => 'ALERT - Disk Used Over X Percent',
			'name' => 'grid_alarms_disk_used_over_x_percent.xml'
		)
	);

	foreach($gridalarms_templates as $gridalarms_template) {
		if (file_exists($config['base_path'].'/plugins/gridalarms/templates/upgrades/10_1_0_6/'.$gridalarms_template['name'])) {
			cacti_log('NOTE: Importing ' . $gridalarms_template['value'], true, 'UPGRADE');
			$results = gridalarms_do_import($config['base_path'].'/plugins/gridalarms/templates/upgrades/10_1_0_6/'.$gridalarms_template['name']);
			if ($results==-1) {
				cacti_log('WARNING: There are invailid/wrong hash in the imported file.', true, 'UPGRADE');
			} elseif ($results==0) {
				cacti_log('NOTE: Imported the new template file successfully.', true, 'UPGRADE');

			} elseif ($results==1) {
				cacti_log('NOTE: Overwrote the old template successfully.', true, 'UPGRADE');
			}
		}
	}

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridalarms_db_version', '10.1.0.6');");

	return 0;
}
