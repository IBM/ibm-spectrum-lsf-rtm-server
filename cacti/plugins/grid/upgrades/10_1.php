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

function upgrade_to_10_1() {
    global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../include/grid_constants.php');
    include_once($config['library_path'] . '/rtm_functions.php');
	include_once($config["library_path"] . '/rtm_db_upgrade.php');
	include_once($config["library_path"] . '/import.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.0 ...', true, 'UPGRADE');

	execute_sql("Load RTM help locations", "REPLACE INTO `settings` VALUES ('help_loc_local_pdf','plugins/hoverhelp/learnmore/getting_started/rtm_help.pdf'),('help_loc_online_kc','https://www.ibm.com/docs/en/spectrum-lsf-rtm/10.1.0');");

	execute_sql("Modify table grid_queues_hosts", "ALTER TABLE `grid_queues_hosts`
		MODIFY COLUMN `host` varchar(64) NOT NULL default '';");
	add_column("grid_clusters", "lsf_strict_checking", "ADD COLUMN `lsf_strict_checking` char(3) default 'N' AFTER `lsf_ego`;");
	modify_column("grid_jobs", "exitInfo", "MODIFY COLUMN `exitInfo` int(10) NOT NULL default '0';");
	modify_column("grid_jobs_finished", "exitInfo", "MODIFY COLUMN `exitInfo` int(10) NOT NULL default '0';");

	// Project collecting SAAP and ineligible pending time - append new fields into grid_jobs and grid_jobs_finished
	// project pending time limit collection and alert - append new fields into grid_jobs and grid_jobs_finished
	$column_arr = array(
		"chargedSAAP" => "ADD COLUMN `chargedSAAP` varchar(256) default '' AFTER `effectiveResreq`",
		"ineligiblePendingTime" => "ADD COLUMN `ineligiblePendingTime` int(10) unsigned NOT NULL default '0' AFTER `chargedSAAP`",
		"pendState" => "ADD COLUMN `pendState` int(10) NOT NULL default '-1' AFTER `ineligiblePendingTime`",
		"effectivePendingTimeLimit" => "ADD COLUMN `effectivePendingTimeLimit` int(10) unsigned NOT NULL default '0' AFTER `pendState`",
		"effectiveEligiblePendingTimeLimit" => "ADD COLUMN `effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL default '0' AFTER `effectivePendingTimeLimit`"
	);

	$index_arr = array(
		"ineligiblePendingTime" => "ADD INDEX `ineligiblePendingTime` (`ineligiblePendingTime`)",
		"effectivePendingTimeLimit" => "ADD INDEX `effectivePendingTimeLimit` (`effectivePendingTimeLimit`)",
		"effectiveEligiblePendingTimeLimit" => "ADD INDEX `effectiveEligiblePendingTimeLimit` (`effectiveEligiblePendingTimeLimit`)"
	);

	add_columns_indexes("grid_jobs", $column_arr, $index_arr);
	add_columns_indexes("grid_jobs_finished", $column_arr, $index_arr);

	$column_arr = array(
		"cmd_retrigger_enabled" => "ADD COLUMN `cmd_retrigger_enabled` char(3) NOT NULL default '' AFTER `trigger_cmd_norm`"
	);
	add_columns("gridalarms_template", $column_arr);
	add_columns("gridalarms_alarm", $column_arr);

	//Drop grid_jobs_memperf table #73689
	$drop_tables=db_fetch_assoc("SELECT CONCAT_WS('_v',table_name,partition) AS drop_table
							FROM grid_table_partitions WHERE table_name='grid_jobs_memperf'");
	if (cacti_sizeof($drop_tables)) {
		foreach($drop_tables AS $drop_table) {
			execute_sql("Drop grid_jobs_memperf partition table", "DROP TABLE IF EXISTS ". $drop_table['drop_table']);
		}
		execute_sql("Delete grid_jobs_memperf record of grid_table_partitions table", "DELETE FROM grid_table_partitions WHERE table_name='grid_jobs_memperf'");
	}

	execute_sql("Drop grid_jobs_memperf table", "DROP TABLE IF EXISTS grid_jobs_memperf");

	//Simiplied Pend Reason Project
	$column_arr = array(
		"type" => "ADD COLUMN `type` tinyint(3) unsigned NOT NULL default '0' AFTER `subreason`",
		"detail" => "ADD COLUMN `detail` varchar(128) NOT NULL default ''AFTER `type`",
		"ratio" => "ADD COLUMN `ratio` float NOT NULL default '0' AFTER `detail`"
	);
	add_columns("grid_jobs_pendreasons", $column_arr);
	add_columns("grid_jobs_pendreasons_finished", $column_arr);

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('grid_partitions_upgrade_status', 'scheduled')");
}

function partition_tables_to_10_1(){
	return array(
		'grid_jobs_finished' => array(
			'columns' => array(
				"chargedSAAP" => "ADD COLUMN `chargedSAAP` varchar(256) default '' AFTER `effectiveResreq`",
				"ineligiblePendingTime" => "ADD COLUMN `ineligiblePendingTime` int(10) unsigned NOT NULL default '0' AFTER `chargedSAAP`",
				"pendState" => "ADD COLUMN `pendState` int(10) NOT NULL default '-1' AFTER `ineligiblePendingTime`",
				"effectivePendingTimeLimit" => "ADD COLUMN `effectivePendingTimeLimit` int(10) unsigned NOT NULL default '0' AFTER `pendState`",
				"effectiveEligiblePendingTimeLimit" => "ADD COLUMN `effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL default '0' AFTER `effectivePendingTimeLimit`",
				"exitInfo" => "MODIFY COLUMN `exitInfo` int(10) NOT NULL default '0'"
			),
			'indexes' => array(
				"ineligiblePendingTime" => "ADD INDEX `ineligiblePendingTime` (`ineligiblePendingTime`)",
				"effectivePendingTimeLimit" => "ADD INDEX `effectivePendingTimeLimit` (`effectivePendingTimeLimit`)",
				"effectiveEligiblePendingTimeLimit" => "ADD INDEX `effectiveEligiblePendingTimeLimit` (`effectiveEligiblePendingTimeLimit`)"
			)
		),
		'grid_jobs_pendreasons_finished' => array(
			'columns' => array(
				"type" => "ADD COLUMN `type` tinyint(3) unsigned NOT NULL default '0' AFTER `subreason`",
				"detail" => "ADD COLUMN `detail` varchar(128) NOT NULL default ''AFTER `type`",
				"ratio" => "ADD COLUMN `ratio` float NOT NULL default '0' AFTER `detail`"
			)
		)
	);
}
