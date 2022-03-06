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

function upgrade_to_1_5() {
	global $system_type;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	execute_sql('Add TimeZone to the License Servers', "ALTER TABLE `grid_license_servers`
		ADD COLUMN `server_timezone` VARCHAR(64) NOT NULL AFTER `server_portatserver`");

	/* must modify several columns to define datetime as timestamp */
	execute_sql("Convert the Job Interval Stats to Timestamp Fields and Add Fields", "ALTER TABLE `grid_job_interval_stats`
		MODIFY COLUMN `interval_start` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `interval_end` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `date_recorded` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		ADD COLUMN `jobs_wall_time` INTEGER UNSIGNED NOT NULL default 0 AFTER `jobs_reaching_state`,
		ADD COLUMN `slots_in_state` INTEGER UNSIGNED NOT NULL default 0 AFTER `jobs_utime`,
		ADD COLUMN `avg_memory` DOUBLE NOT NULL default 0 AFTER `slots_in_state`,
		ADD COLUMN `max_memory` DOUBLE NOT NULL default 0 AFTER `avg_memory`;");

	execute_sql("Convert the Job Daily Stats to Timestamp Fields and Add Fields", "ALTER TABLE `grid_job_daily_stats`
		MODIFY COLUMN `interval_start` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `interval_end` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `date_recorded` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		ADD COLUMN `jobs_wall_time` INTEGER UNSIGNED NOT NULL default 0 AFTER `jobs_in_state`,
		ADD COLUMN `slots_in_state` INTEGER UNSIGNED NOT NULL default 0 AFTER `jobs_utime`,
		ADD COLUMN `avg_memory` DOUBLE NOT NULL default 0 AFTER `slots_in_state`,
		ADD COLUMN `max_memory` DOUBLE NOT NULL default 0 AFTER `avg_memory`;");

	execute_sql("Convert the Job Arrays Table to Timestamp Fields", "ALTER TABLE `grid_arrays`
		MODIFY COLUMN `submit_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';");

	execute_sql("Convert the Hostinfo Table to Timestamp Fields", "ALTER TABLE `grid_hostinfo`
		MODIFY COLUMN `first_seen` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `last_seen` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';");

	execute_sql("Convert the Summary Table to Timestamp Fields", "ALTER TABLE `grid_summary`
		MODIFY COLUMN `first_seen` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `last_seen` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';");

	create_table("grid_jobs_processes", "CREATE TABLE  `grid_jobs_processes` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`jobid` bigint(20) unsigned NOT NULL default '0',
		`indexid` int(10) unsigned NOT NULL default '0',
		`host` varchar(64) NOT NULL default '',
		`PID` int(10) unsigned NOT NULL default '0',
		`PGID` int(10) unsigned NOT NULL default '0',
		`mem` double default NULL,
		`swap` double default NULL,
		`utime` double default NULL,
		`stime` double default NULL,
		`first_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_seen` timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  USING BTREE (`clusterid`, `jobid`, `indexid`, `host`, `PID`),
		KEY `clusterid_host` (`clusterid`, `host`),
		KEY `pid` (`PID`)) ENGINE=InnoDB;");

	execute_sql("Convert to Timestamps, and add new columns to Jobs", "ALTER TABLE `grid_jobs`
		MODIFY COLUMN `reserveTime` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `predictedStartTime` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `beginTime` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		MODIFY COLUMN `termTime` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		ADD COLUMN `options` INTEGER UNSIGNED NOT NULL default 0 AFTER `clusterid`,
		ADD COLUMN `options2` INTEGER UNSIGNED NOT NULL default 0 AFTER `options`,
		ADD COLUMN `options3` INTEGER UNSIGNED NOT NULL default 0 AFTER `options2`,
		ADD COLUMN `sla` VARCHAR(60) NOT NULL default '' AFTER `parentGroup`,
		ADD COLUMN `jobGroup` VARCHAR(60) NOT NULL default '' AFTER `sla`,
		ADD COLUMN `licenseProject` VARCHAR(60) NOT NULL default '' AFTER `jobGroup`,
		ADD INDEX `licenseProject` (`licenseProject`),
		ADD INDEX `jobGroup` (`jobGroup`),
		ADD INDEX `sla` (`sla`)");

	/* execute the table changes for performance reasons */
	execute_sql("Rename grid_jobs to grid_jobs_finished", "RENAME TABLE `grid_jobs` TO `grid_jobs_finished`");
	execute_sql("Copy grid_jobs_finished table structure to grid_jobs", "CREATE TABLE `grid_jobs` LIKE `grid_jobs_finished`");

	if ($system_type == "large") {
		execute_sql("Change grid_jobs to INNODB type table", "ALTER TABLE `grid_jobs` ENGINE=INNODB");
	}

	execute_sql("Remove non-finished jobs from finished table", "DELETE FROM grid_jobs_finished WHERE stat NOT IN ('DONE', 'EXIT')");

	execute_sql("Add the integer status to the load table", "ALTER TABLE `grid_load`
		ADD COLUMN `istatus` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `status`,
		ADD INDEX `istatus`(`istatus`);");

	execute_sql("Add additional cluster fields", "ALTER TABLE `grid_clusters`
		MODIFY COLUMN `lsf_masterhosts` VARCHAR(1024) NOT NULL default '',
		MODIFY COLUMN `lsf_serverhosts` VARCHAR(1024) NOT NULL default '',
		MODIFY COLUMN `lsf_clustername` VARCHAR(128) NOT NULL default '',
		ADD COLUMN `lsf_lic_schedhosts` VARCHAR(1024) NOT NULL default '' AFTER `lsf_serverhosts`,
		ADD COLUMN `lsf_admins` VARCHAR(256) NOT NULL default '' AFTER `lsf_clustername`,
		ADD COLUMN `lsb_debug` VARCHAR(20) NOT NULL default '' AFTER `lsf_admins`,
		ADD COLUMN `lsf_lim_debug` VARCHAR(20) NOT NULL default '' AFTER `lsb_debug`,
		ADD COLUMN `lsf_res_debug` VARCHAR(20) NOT NULL default '' AFTER `lsf_lim_debug`,
		ADD COLUMN `lsf_log_mask` VARCHAR(50) NOT NULL default '' AFTER `lsf_res_debug`;");

	/* add the license projects table */
	execute_sql("Create License Projects Table", "CREATE TABLE `grid_license_projects` (
		`clusterid`      INT(10) unsigned NOT NULL,
		`licenseProject` VARCHAR(64) NOT NULL,
		`numRUN`         INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`numPEND`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`numJOBS`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`efficiency`     DOUBLE NOT NULL DEFAULT 0,
		`avg_mem`        DOUBLE NOT NULL DEFAULT 0,
		`max_mem`        DOUBLE NOT NULL DEFAULT 0,
		`avg_swap`       DOUBLE NOT NULL DEFAULT 0,
		`max_swap`       DOUBLE NOT NULL DEFAULT 0,
		`total_cpu`      BIGINT(20) UNSIGNED  NOT NULL DEFAULT 0,
		`present`        TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (`clusterid`,`licenseProject`),
		KEY `present` (`present`)) ENGINE=MEMORY COMMENT='Tracks License Project Information';");

	execute_sql("Speed up license server ops", "ALTER TABLE `grid_license_servers`
		MODIFY COLUMN `server_portatserver` VARCHAR(512) NOT NULL DEFAULT '',
		ADD INDEX `server_location`(`server_location`);");

	execute_sql("Provide more lsf Insight", "ALTER TABLE `grid_clusters`
		ADD COLUMN `lsf_ls_error` INTEGER UNSIGNED NOT NULL default 0 AFTER `lsf_clustername`,
		ADD COLUMN `lsf_lsb_error` INTEGER UNSIGNED NOT NULL default 0 AFTER `lsf_ls_error`,
		ADD COLUMN `lsf_lsb_jobs_error` VARCHAR(10) NOT NULL default '' AFTER `lsf_lsb_error`,
		ADD COLUMN `lsf_lim_response` FLOAT NOT NULL default 0 AFTER `lsf_lsb_jobs_error`,
		ADD COLUMN `lsf_lsb_response` FLOAT NOT NULL default 0 AFTER `lsf_lim_response`,
		ADD COLUMN `lsf_lsb_jobs_response` FLOAT NOT NULL default 0 AFTER `lsf_lsb_response`;");

	execute_sql("Add Vendor Daemon to Primary Key", "ALTER TABLE `grid_license_servers_feature_use`
		DROP PRIMARY KEY,
		ADD PRIMARY KEY  USING HASH(`poller_id`, `feature_name`, `portatserver_id`, `vendor_daemon`);");

	execute_sql("Remove Old Feature Use Details Table", "DROP TABLE `grid_license_servers_feature_details`;");

	execute_sql("Create New Feature Use Details Table", "CREATE TABLE `grid_license_servers_feature_details` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`vendor_daemon` varchar(40) NOT NULL default '',
		`feature_name` varchar(50) NOT NULL default '0',
		`subfeature` varchar(50) NOT NULL default '',
		`feature_version` varchar(50) NOT NULL default '',
		`username` varchar(50) NOT NULL default '',
		`groupname` varchar(50) NOT NULL default '',
		`hostname` varchar(64) NOT NULL default '',
		`chkoutid` varchar(20) NOT NULL default '',
		`status` varchar(20) NOT NULL default '',
		`tokens_acquired` int(10) unsigned NOT NULL default '0',
		`tokens_acquired_date` varchar(20) NOT NULL default '',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`present` tinyint(1) NOT NULL default '1',
		PRIMARY KEY  USING HASH (`portatserver_id`,`vendor_daemon`,`chkoutid`,`tokens_acquired_date`,`hostname`,`username`),
		KEY `poller_id` (`poller_id`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `vendor_daemon` (`vendor_daemon`),
		KEY `feature_name` (`feature_name`),
		KEY `username` (`username`),
		KEY `hostname` (`hostname`)
		) ENGINE=MEMORY");

	execute_sql("Remove Old Feature Expirations Table", "DROP TABLE `grid_license_servers_feature_expirations`");

	execute_sql("Create New Feature Expirations Table", "CREATE TABLE `grid_license_servers_feature_expirations` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature_name` varchar(50) NOT NULL default '',
		`feature_version` varchar(20) NOT NULL default '',
		`feature_number_to_expire` int(10) unsigned NOT NULL default '0',
		`feature_expiration_date` varchar(45) NOT NULL default '',
		`vendor_daemon` varchar(45) NOT NULL default '',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  USING HASH (`poller_id`,`portatserver_id`,`feature_name`,`feature_version`,`vendor_daemon`,`feature_expiration_date`),
		KEY `poller_id` (`poller_id`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `feature_name` (`feature_name`),
		KEY `feature_version` (`feature_version`),
		KEY `vendor_daemon` (`vendor_daemon`)
		) ENGINE=MEMORY");

	execute_sql("Remove Old Feature Use Information", "DROP TABLE IF EXISTS `grid_license_servers_feature_use`;");

	execute_sql("Recreate the Feature Use Informatoin Table" , "CREATE TABLE `grid_license_servers_feature_use` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature_name` varchar(50) NOT NULL default '',
		`feature_max_licenses` int(10) unsigned NOT NULL default '0',
		`feature_inuse_licenses` int(10) unsigned NOT NULL default '0',
		`feature_queued` int(10) unsigned NOT NULL default '0',
		`feature_reserved` int(10) unsigned NOT NULL default '0',
		`vendor_daemon` varchar(45) NOT NULL default 'TBD',
		`present` tinyint(1) NOT NULL default '1',
		PRIMARY KEY  (`poller_id`,`feature_name`,`portatserver_id`),
		KEY `poller_id` (`poller_id`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `vendor_daemon` (`vendor_daemon`),
		KEY `feature_name` (`feature_name`),
		KEY `feature_queued` (`feature_queued`),
		KEY `feature_reserved` (`feature_reserved`)
		) ENGINE=MEMORY");

	execute_sql("Modify the Cluster table to add a tree", "ALTER TABLE `grid_clusters`
		ADD COLUMN `cacti_tree` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `cacti_host`;");

    /* if running a large configuration, make sure users_or_groups is innodb */
	if ($system_type == "large") {
		execute_sql("Change Users or Groups to InnoDB", "ALTER TABLE `grid_users_or_groups` ENGINE=InnoDB");
	} else {
		execute_sql("Change Users or Groups to InnoDB", "ALTER TABLE `grid_users_or_groups` ENGINE=InnoDB");
	}

	/* start beta3 */
	execute_sql("Modify Clusters for Batch Errors", "ALTER TABLE `grid_clusters`
        MODIFY COLUMN `lsf_lsb_jobs_error` INTEGER UNSIGNED NOT NULL DEFAULT 0;");

	/* more indexes for interval stats */
	execute_sql("Add Indexes for Daily Stats", "ALTER TABLE grid_job_interval_stats ADD INDEX `projectName` (`projectName`)");

	execute_sql("Project Stats Need to Be Non-Transient", "ALTER TABLE grid_projects ENGINE=InnoDB");
	execute_sql("licenseProject Stats Need to Be Non-Transient", "ALTER TABLE grid_license_projects ENGINE=InnoDB");

	execute_sql("Add Department and LicenseType to Servers Table", "ALTER TABLE `grid_license_servers`
		ADD COLUMN `server_department` VARCHAR(45) AFTER `server_licensefile`,
		ADD COLUMN `server_licensetype` VARCHAR(20) AFTER `server_name`,
		ADD COLUMN `poller_interval` INTEGER UNSIGNED NOT NULL default 300 AFTER `poller_id`,
		ADD COLUMN `poller_trigger` INTEGER NOT NULL default 0 AFTER `poller_interval`,
		ADD COLUMN `server_querybin_path` VARCHAR(255) NOT NULL DEFAULT '' AFTER `server_timezone`,
		ADD COLUMN `enable_checkouts` VARCHAR(20) NOT NULL DEFAULT '' AFTER `server_support_info`");

	execute_sql("Add Poller Threads to the Poller Table", "ALTER TABLE `grid_pollers`
		ADD COLUMN `poller_licserver_threads` INTEGER NOT NULL default 5 AFTER `poller_lbindir`");

	execute_sql("Speed Up Client Requests for Feature Information", "ALTER TABLE `grid_hostinfo`
		ADD INDEX `licFeaturesNeeded`(`licFeaturesNeeded`);");

	execute_sql("Speedup Cacti", "ALTER TABLE `data_input_data`
		MODIFY COLUMN `value` VARCHAR(255) DEFAULT NULL,
		ADD INDEX `value`(`value`);");

	execute_sql("Add one more column to the license servers", "ALTER TABLE `grid_license_servers`
		ADD COLUMN `poller_date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `poller_interval`;");

	execute_sql("Remove Non-Required Indexes from grid_jobs_finished table", "ALTER TABLE `grid_jobs_finished`
		DROP INDEX `job_start_logged`,
		DROP INDEX `job_end_logged`,
		DROP INDEX `job_scan_logged`,
		DROP INDEX `nice`,
		DROP INDEX `completion_time`,
		DROP INDEX `prev_stat`,
		DROP INDEX `flapping_logged`,
		DROP INDEX `effic_logged`,
		DROP INDEX `pid_alarm_logged`,
		DROP INDEX `clusterid_end_logged`,
        DROP INDEX `clusterid_stat_end_logged`,
		DROP INDEX `clusterid_job_scan_logged`;");

	/* add the license projects table */
	execute_sql("Create userGroup Stats Table", "CREATE TABLE `grid_user_group_stats` (
		`clusterid`      INT(10) unsigned NOT NULL,
		`userGroup`      VARCHAR(64) NOT NULL,
		`numRUN`         INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`numPEND`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`numJOBS`        INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`efficiency`     DOUBLE NOT NULL DEFAULT 0,
		`avg_mem`        DOUBLE NOT NULL DEFAULT 0,
		`max_mem`        DOUBLE NOT NULL DEFAULT 0,
		`avg_swap`       DOUBLE NOT NULL DEFAULT 0,
		`max_swap`       DOUBLE NOT NULL DEFAULT 0,
		`total_cpu`      BIGINT(20) UNSIGNED  NOT NULL DEFAULT 0,
		`present`        TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (`clusterid`,`userGroup`),
		KEY `present` (`present`)) ENGINE=InnoDB COMMENT='Tracks userGroup Stats';");

	db_execute("Add last_update time to properly account for Exec Hosts", "ALTER TABLE `grid_jobs_jobhosts`
		ADD COLUMN `last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `processes`;");

	/* if running a large configuration, make sure users_or_groups is innodb */
	if ($system_type == "large") {
		execute_sql("Change Users Group Members to InnoDB", "ALTER TABLE `grid_user_group_members` ENGINE=InnoDB");
	}

	db_execute("Avoid warning messages when adding queues", "ALTER TABLE `grid_queues`
		MODIFY COLUMN `hourly_started_jobs` DOUBLE NOT NULL DEFAULT 0,
		MODIFY COLUMN `hourly_done_jobs` DOUBLE NOT NULL DEFAULT 0,
		MODIFY COLUMN `hourly_exit_jobs` DOUBLE NOT NULL DEFAULT 0,
		MODIFY COLUMN `daily_started_jobs` DOUBLE NOT NULL DEFAULT 0,
		MODIFY COLUMN `daily_done_jobs` DOUBLE NOT NULL DEFAULT 0,
		MODIFY COLUMN `daily_exit_jobs` DOUBLE NOT NULL DEFAULT 0;");

	// clean up lsf version in grid_pollers from 1.04
    include_once("../../plugins/grid/include/grid_constants.php");
	execute_sql("Update lsf_version in grid_pollers table",
		"UPDATE grid_pollers set poller_lbindir='" .
		$rtm["lsf62"]["LSF_SERVERDIR"] . "' where lsf_version='62';");

	execute_sql("Update lsf_version in grid_pollers table",
		"UPDATE grid_pollers set lsf_version='701' where lsf_version='71';");
	execute_sql("Update lsf_version in grid_pollers table",
		"UPDATE grid_pollers set lsf_version='702' where lsf_version='72';");
	execute_sql("Update lsf_version in grid_pollers table",
		"UPDATE grid_pollers set lsf_version='703' where lsf_version='73';");

	execute_sql("Update poller dir in grid_pollers table",
		"UPDATE grid_pollers set poller_lbindir='" . $rtm["lsf62"]["LSF_SERVERDIR"] . "' " .
		"where poller_lbindir='/opt/rtm/lsf62/bin'");

	execute_sql("Update poller dir in grid_pollers table",
		"UPDATE grid_pollers set poller_lbindir='" . $rtm["lsf701"]["LSF_SERVERDIR"] . "' " .
		"where poller_lbindir='/opt/rtm/lsf701/bin'");

	execute_sql("Update poller dir in grid_pollers table",
		"UPDATE grid_pollers set poller_lbindir='" . $rtm["lsf702"]["LSF_SERVERDIR"] . "' " .
		"where poller_lbindir='/opt/rtm/lsf702/bin'");

	execute_sql("Update poller dir in grid_pollers table",
		"UPDATE grid_pollers set poller_lbindir='" . $rtm["lsf703"]["LSF_SERVERDIR"] . "' " .
		"where poller_lbindir='/opt/rtm/lsf703/bin'");

	execute_sql("Clear lsf_version in grid_clusters table", "UPDATE grid_clusters set lsf_version='';'");

	if ($system_type == "large") {
		/*  from innodb sql
		grid_arrays
		grid_job_collection_details
		grid_job_collection_members
		grid_jobs
		grid_jobs_finished
		grid_jobs_jobhosts
		grid_jobs_memperf
		grid_jobs_reqhosts
		grid_jobs_rusage
		grid_user_group_members
		grid_users_or_groups
		*/

		if (upgrade_get_table_engine('grid_arrays') != 'InnoDB') {
			execute_sql('Change grid_arrays to InnoDB', 'ALTER TABLE grid_arrays ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_job_collection_details') != 'InnoDB') {
			execute_sql('Change grid_job_collection_details to InnoDB', 'ALTER TABLE grid_job_collection_details ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_job_collection_members') != 'InnoDB') {
			execute_sql('Change grid_job_collection_members to InnoDB', 'ALTER TABLE grid_job_collection_members ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs') != 'InnoDB') {
			execute_sql('Change grid_jobs to InnoDB', 'ALTER TABLE grid_jobs ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs_finished') != 'InnoDB') {
			execute_sql('Change grid_jobs_finished to InnoDB', 'ALTER TABLE grid_jobs_finished ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs_jobhosts') != 'InnoDB') {
			execute_sql('Change grid_jobs_jobhosts to InnoDB', 'ALTER TABLE grid_jobs_jobhosts ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs_memperf') != 'InnoDB') {
			execute_sql('Change grid_jobs_memperf to InnoDB', 'ALTER TABLE grid_jobs_memperf ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs_reqhosts') != 'InnoDB') {
			execute_sql('Change grid_jobs_reqhosts to InnoDB', 'ALTER TABLE grid_jobs_reqhosts ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_jobs_rusage') != 'InnoDB') {
			execute_sql('Change grid_jobs_rusage to InnoDB', 'ALTER TABLE grid_jobs_rusage ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_user_group_members') != 'InnoDB') {
			execute_sql('Change grid_user_group_members to InnoDB', 'ALTER TABLE grid_user_group_members ENGINE=InnoDB');
		}

		if (upgrade_get_table_engine('grid_users_or_groups') != 'InnoDB') {
			execute_sql('Change grid_users_or_groups to InnoDB', 'ALTER TABLE grid_users_or_groups ENGINE=InnoDB');
		}
	}
}
