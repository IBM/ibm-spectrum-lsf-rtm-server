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

function upgrade_to_10_2_0_12() {
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

	execute_sql("Remove Non-Required Indexes from grid_jobs_finished table",
	    "ALTER TABLE `grid_jobs_finished` DROP INDEX `clusterid_end_logged`,
		 DROP INDEX `clusterid_stat_end_logged`,
         DROP INDEX `clusterid_stat_start_time`,
         DROP INDEX `effectiveEligiblePendingTimeLimit`,
         DROP INDEX `effectivePendingTimeLimit`,
         DROP INDEX `effic_logged`,
         DROP INDEX `flapping_logged`,
         DROP INDEX `ineligiblePendingTime`,
         DROP INDEX `job_end_logged`,
         DROP INDEX `job_scan_logged`,
         DROP INDEX `job_start_logged`,
         DROP INDEX `nice`,
         DROP INDEX `pid_alarm_logged`,
         DROP INDEX `prev_stat`,
         DROP INDEX `prov_time`,
         DROP INDEX `psusp_time`,
         DROP INDEX `ssusp_time`,
         DROP INDEX `swap_used`,
         DROP INDEX `unkwn_time`,
         DROP INDEX `ususp_time`,
         MODIFY `userPriority` int(11) signed DEFAULT 0,
		 MODIFY `mailUser` varchar(512) default NULL");

	execute_sql("Update grid_jobs table",
	    "ALTER TABLE `grid_jobs` MODIFY `userPriority` int(11) signed DEFAULT 0,
			MODIFY `mailUser` varchar(512) default NULL,
			ADD INDEX `stat_last_updated`(`stat`, `last_updated`)");

	$column_arr= array(
		'owner' => "MODIFY COLUMN `owner` varchar(40) NOT NULL default ''"
	);
	$index_arr = array(
		"clusterid_host" => "ADD INDEX `clusterid_host` (`clusterid`, `host`)",
		"clusterid_owner" => "ADD INDEX `clusterid_owner` (`clusterid`, `owner`)"
	);
	add_columns_indexes("grid_guarantee_pool_hosts", $column_arr, $index_arr);

	$data = array();
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'cpuUtil');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memUsed');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memRequested');
	$data['primary']   = array('clusterid','name');
	db_update_table('grid_guarantee_pool', $data);

	cacti_log('Importing RTM templates..', true, 'UPGRADE');
	$grid_templates = array(
		"1" => array (
			'value' => 'GRID - Queue - Pending/Running Slots',
			'name' => 'cacti_graph_template_grid_-_queue_-_pendingrunning_slots.xml'
		)
	);
	foreach($grid_templates as $grid_template) {
		cacti_log(' - Importing ' . $grid_template['value'] . '.', true, 'UPGRADE');
		$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_2_0_12/" . $grid_template['name']);
	}
	cacti_log('Templates import complete.', true, 'UPGRADE');

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.12' WHERE directory IN ('gridpend', 'benchmark', 'RTM', 'lichist', 'meta')");
}

function partition_tables_to_10_2_0_12(){
	return array(
		'grid_jobs' => array(
			'drop' => array(
				'indexes' => array(
					'clusterid_end_logged',
					'clusterid_stat_end_logged',
					'clusterid_stat_start_time',
					'effectiveEligiblePendingTimeLimit',
					'effectivePendingTimeLimit',
					'effic_logged',
					'flapping_logged',
					'ineligiblePendingTime',
					'job_end_logged',
					'job_scan_logged',
					'job_start_logged',
					'nice',
					'pid_alarm_logged',
					'prev_stat',
					'prov_time',
					'psusp_time',
					'ssusp_time',
					'swap_used',
					'unkwn_time',
					'ususp_time'
				)
			)
		)
	);
}
