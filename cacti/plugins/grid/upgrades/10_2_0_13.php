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

function upgrade_to_10_2_0_13() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	//Add new poller "Poller for LSF 10.1.0.12" for LSF 10.1.0.12 and later
	$php_cmd = read_config_option('path_php_binary');
	$extra_args = ' -q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/get_grid_poller.php') . ' 10.1.0.12';
	shell_exec($php_cmd . $extra_args);

	$data = array();
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = array('queuename','clusterid');
	db_update_table('grid_queues', $data);

	add_index("grid_users_or_groups", "type", "ADD INDEX `type` (`type`);");
	add_index("grid_queues_shares", "clusterid_user_or_group", "ADD INDEX `clusterid_user_or_group` (`clusterid`,`user_or_group`);");

	$data = array();
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary']   = array('clusterid', 'groupName');
	db_update_table('grid_hostgroups_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary']   = array('clusterid', 'queue');
	db_update_table('grid_queues_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['primary']   = array('clusterid', 'groupname', 'username');
	db_update_table('grid_user_group_members', $data);

	execute_sql("Drop settings_graphs table", "DROP TABLE IF EXISTS settings_graphs");
	execute_sql("Drop rra_cf table", "DROP TABLE IF EXISTS rra_cf");
	execute_sql("Drop rra table", "DROP TABLE IF EXISTS rra");

	add_column("grid_pollers", "poller_max_insert_packet_size", "ADD COLUMN `poller_max_insert_packet_size` varchar(255);");

	add_index("grid_hostinfo", "clusterid_present", "ADD INDEX `clusterid_present` (`clusterid`,`present`);");

	add_index("grid_jobs", "clusterid_stat_last_updated", "ADD INDEX `clusterid_stat_last_updated` (`clusterid`,`stat`,`last_updated`)");

	add_index("grid_jobs_pendreasons", "clusterid_end_time_last_updated", "ADD INDEX `clusterid_end_time_last_updated` (`clusterid`,`end_time`,`last_updated`)");

	db_execute("UPDATE data_input_data did JOIN data_input_fields dif ON dif.id=did.data_input_field_id SET did.t_value='on' WHERE dif.hash IN ('6027a919c7c7731fbe095b6f53ab127b','cbbe5c1ddfb264a6e5d509ce1c78c95f','e6deda7be0f391399c5130e7c4a48b28','d39556ecad6166701bfb0e28c5a11108','3b7caa46eb809fc238de6ef18b6e10d5','74af2e42dc12956c4817c2ef5d9983f9','172b4b0eacee4948c6479f587b62e512','30fb5d5bcf3d66bb5abe88596f357c26','31112c85ae4ff821d3b288336288818c') AND did.t_value='';");

	db_execute("ALTER TABLE grid_queues_shares MODIFY COLUMN run_time bigint unsigned NOT NULL;");
	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.13' WHERE directory IN ('benchmark', 'gridcstat', 'gridpend', 'lichist', 'RTM')");
}
