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

function upgrade_to_10_2_0_1() {
	global $config;

	include_once($config['library_path'] . '/rtm_functions.php');
	include_once($config['library_path'] . '/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.2.0.1 ...', true, 'UPGRADE');

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_high_lockid',
		'type'=> 'varchar(128)',
		'NULL' => false,
		'default' => '',
		'after' => 'notes'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_low_lockid',
		'type'=> 'varchar(128)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_high_lockid'));

	return 0;
}
