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

function upgrade_to_2_1_2() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	/* adding vendor to flexlm table */
	add_column("lic_flexlm_servers", "server_vendor", "ALTER TABLE `lic_flexlm_servers`
		ADD COLUMN `server_vendor` VARCHAR(60) NOT NULL DEFAULT '' AFTER `server_name`;");

	execute_sql("Add index to column user for grid_job_interval_stats", "ALTER TABLE grid_job_interval_stats ADD INDEX `user` (`user`);");
	execute_sql("Add index to column username for grid_user_group_members", "ALTER TABLE grid_user_group_members ADD INDEX `username` (`username`);");
	execute_sql("Add index to column host for grid_hostgroups", "ALTER TABLE grid_hostgroups ADD INDEX `host` (`host`);");

	add_index("grid_jobs_rusage", "submit_time", "ALTER TABLE `grid_jobs_rusage` ADD INDEX `submit_time`(`submit_time`);");

	create_table("grid_arrays_finished", "CREATE TABLE `grid_arrays_finished` (
	  `clusterid` int(10) unsigned NOT NULL,
	  `jobid` int(10) unsigned NOT NULL,
	  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	  `stat` int(10) unsigned NOT NULL default '0',
	  `jType` int(10) unsigned NOT NULL default '0',
	  `jName` varchar(128) NOT NULL default '',
	  `user` varchar(45) NOT NULL default '',
	  `userGroup` varchar(45) default '',
	  `queue` varchar(45) NOT NULL default '',
	  `projectName` varchar(45) NOT NULL default '0',
	  `numJobs` int(10) unsigned NOT NULL default '0',
	  `numPEND` int(10) unsigned NOT NULL default '0',
	  `numPSUSP` int(10) unsigned NOT NULL default '0',
	  `numRUN` int(10) unsigned NOT NULL default '0',
	  `numSSUSP` int(10) unsigned NOT NULL default '0',
	  `numUSUSP` int(10) unsigned NOT NULL default '0',
	  `numEXIT` int(10) unsigned NOT NULL default '0',
	  `numDONE` int(10) unsigned NOT NULL default '0',
	  `minMemory` double NOT NULL default '0',
	  `maxMemory` double NOT NULL default '0',
	  `avgMemory` double NOT NULL default '0',
	  `minSwap` double NOT NULL default '0',
	  `maxSwap` double NOT NULL default '0',
	  `avgSwap` double NOT NULL default '0',
	  `totalCPU` double NOT NULL default '0',
	  `totalUTime` double NOT NULL default '0',
	  `totalSTime` double NOT NULL default '0',
	  `totalEfficiency` decimal(9,5) NOT NULL default '0.00000',
	  `first_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
	  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	  PRIMARY KEY  (`clusterid`,`jobid`,`submit_time`),
	  KEY `clusterid_last_updated` (`clusterid`,`last_updated`),
	  KEY `clusterid_jName` (`clusterid`,`jName`),
	  KEY `clusterid_user` (`clusterid`,`user`),
	  KEY `clusterid_queue` (`clusterid`,`queue`),
	  KEY `clusterid_userGroup` (`clusterid`,`userGroup`),
	  KEY `clusterid_projectName` (`clusterid`,`projectName`),
	  KEY `clusterid_stat` (`clusterid`,`stat`),
	  KEY `clusterid` (`clusterid`)
	) ENGINE=InnoDB COMMENT='Finished Job Groups and Array';");

	/* setting status to 0 for all disable flexlm server */
	$disabled = db_fetch_assoc("SELECT id FROM lic_servers WHERE disabled='on'");
	if (cacti_sizeof($disabled)) {
		foreach($disabled as $dab) {
  			db_execute("UPDATE lic_flexlm_servers SET status=0 WHERE portatserver_id=" . $dab["id"]);
		}
	}

	create_table("lic_daily_stats_traffic", "CREATE TABLE IF NOT EXISTS `lic_daily_stats_traffic` (
	  `portatserver_id` int(10) unsigned NOT NULL,
	  `feature` varchar(100) NOT NULL,
	  `user` varchar(64) NOT NULL,
	  `host` varchar(64) NOT NULL,
	  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	  PRIMARY KEY  (`portatserver_id`,`feature`,`user`,`host`),
	  KEY `feature` (`feature`),
	  KEY `user` (`user`),
	  KEY `host` (`host`),
	  KEY `portatserver_id` (`portatserver_id`),
	  KEY `last_updated` (`last_updated`)
	) ENGINE=InnoDB;");

	/* dropping old license tables */
	execute_sql("Drop grid_license_servers", "DROP TABLE IF EXISTS grid_license_servers;");
	execute_sql("Drop grid_license_servers_feature_details", "DROP TABLE IF EXISTS grid_license_servers_feature_details;");
	execute_sql("Drop grid_license_servers_feature_expirations", "DROP TABLE IF EXISTS grid_license_servers_feature_expirations;");
	execute_sql("Drop grid_license_servers_feature_use", "DROP TABLE IF EXISTS grid_license_servers_feature_use;");

	/*Upgrade realm_id*/
	execute_sql("Append realm_id(39) If user is able to '35'", "insert into user_auth_realm select 39, user_id from user_auth_realm where realm_id=3;");

	$column_arr= array(
		'type' => "ADD COLUMN `type` int(10) NOT NULL default '0' AFTER `submit_time`",
		'run_time' => "ADD COLUMN `run_time` int(10) NOT NULL default '0' AFTER `type`"
		);
	add_columns_indexes("grid_jobs_memvio", $column_arr, $index_arr);

	/* snmp query graphs should not be in host_graph tables */
	execute_sql("Remove snmp query graphs from host_graph table", "DELETE FROM host_graph WHERE graph_template_id IN (SELECT DISTINCT graph_template_id FROM snmp_query_graph);");

}
