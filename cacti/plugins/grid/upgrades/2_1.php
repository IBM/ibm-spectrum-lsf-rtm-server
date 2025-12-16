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

function upgrade_to_2_1() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	$column_arr= array(
		'advanced_enabled' => "ADD COLUMN `advanced_enabled` CHAR(2) NOT NULL DEFAULT '' AFTER `add_graph_frequency`",
		'email_domain' => "ADD COLUMN `email_domain` VARCHAR(64) NOT NULL DEFAULT '' AFTER `advanced_enabled`",
		'email_admin' => "ADD COLUMN `email_admin` VARCHAR(512) NOT NULL DEFAULT '' AFTER `email_domain`"
		);
	add_columns_indexes("grid_clusters", $column_arr, NULL);

	execute_sql("Rename current grid_jobs table to grid_jobs_pre21",
		"RENAME TABLE `grid_jobs` TO `grid_jobs_pre21`;");
	execute_sql("Recreate new grid_jobs table from grid_jobs_pre21",
		"CREATE TABLE `grid_jobs` LIKE `grid_jobs_pre21`;");

	$column_arr= array(
		'cwd' => "ADD COLUMN `cwd` VARCHAR(255) NOT NULL DEFAULT '' AFTER `execCwd`",
		'postExecCmd' => "ADD COLUMN `postExecCmd` VARCHAR(255) NOT NULL DEFAULT '' AFTER `cwd`",
		'mem_requested' => "ADD COLUMN `mem_requested` DOUBLE DEFAULT '0' AFTER `max_swap`",
		'mem_requested_oper' => "ADD COLUMN `mem_requested_oper` VARCHAR(8) DEFAULT '' AFTER `mem_requested`",
		'mem_reserved' => "ADD COLUMN `mem_reserved` DOUBLE DEFAULT '0' AFTER `mem_requested_oper`"
		);
	add_columns_indexes("grid_jobs", $column_arr, NULL);
	add_columns_indexes("grid_jobs_finished", $column_arr, NULL);

	execute_sql("Drop grid_jobs_pre21 table", "DROP TABLE IF EXISTS `grid_jobs_pre21`;");

	execute_sql("Add avg and max dispatch time to grid_queues", "ALTER TABLE `grid_queues`
		ADD COLUMN `avg_disp_time` int(10) unsigned NOT NULL DEFAULT '0' AFTER `max_unkwn_time`,
		ADD COLUMN `max_disp_time` int(10) unsigned NOT NULL DEFAULT '0' AFTER `avg_disp_time`;");

	execute_sql("Change grid_hosts_resources present and cluster id field to use btree index", "ALTER TABLE grid_hosts_resources DROP INDEX `clusterid`, DROP INDEX `present`, ADD INDEX USING BTREE (`clusterid`), ADD INDEX USING BTREE (`present`);");

	modify_column("grid_hosts_resources", "resource_name", "ALTER TABLE `grid_hosts_resources`
		MODIFY COLUMN `resource_name` VARCHAR(40) NOT NULL;");

	execute_sql("Add user auth realm for Job Detail View", "REPLACE INTO `user_auth_realm` VALUES (1046,1);");

	/* add time in state to load and bhost tables */

	execute_sql("Add time_in_state and prev_status to grid_load table", "ALTER TABLE `grid_load` ADD COLUMN `prev_status` VARCHAR(20) NOT NULL default '' AFTER `status`,
		ADD COLUMN `time_in_state` int(10) UNSIGNED NOT NULL default '0' AFTER `prev_status`,
		ADD INDEX `prev_status`(`prev_status`);");

	execute_sql("Add time_in_state and prev_status to grid_host table", "ALTER TABLE `grid_hosts` ADD COLUMN `prev_status` VARCHAR(20) NOT NULL default '' AFTER `status`,
		ADD COLUMN `time_in_state` int(10) UNSIGNED NOT NULL default '0' AFTER `prev_status`,
		ADD INDEX `prev_status`(`prev_status`);");

	create_table("grid_table_partitions", "CREATE TABLE `grid_table_partitions` (
		`partition` varchar(5) NOT NULL,
		`table_name` varchar(45) NOT NULL,
		`min_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`max_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  USING BTREE (`partition`,`table_name`),
		KEY `max_time` (`max_time`),
		KEY `min_time` (`min_time`)
		) ENGINE=InnoDB;");

	/* add new license servers tables */
	$column_arr= array(
		'lic_server_id' => "ADD COLUMN `lic_server_id` int(10) unsigned NOT NULL DEFAULT '0' AFTER `clusterid`"
		);
	$index_arr= array(
		'lic_server_id' => "ADD INDEX `lic_server_id` (`lic_server_id`)"
		);
	add_columns_indexes("host", $column_arr, $index_arr);

	create_table("lic_servers", "CREATE TABLE `lic_servers` (
		`id` int(12) unsigned NOT NULL auto_increment,
		`lic_description` varchar(100) NOT NULL,
		`lic_poller_id` int(10) unsigned NOT NULL,
		`disabled` varchar(2) NOT NULL DEFAULT '',
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB;");

	create_table("lic_pollers", "CREATE TABLE `lic_pollers` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`poller_path` varchar(100) NOT NULL,
		`poller_description` varchar(100) NOT NULL,
		`poller_hostname` varchar(64) NOT NULL,
		`poller_type` tinyint(3) NOT NULL DEFAULT 0,
		 PRIMARY KEY (`id`)
		) ENGINE=InnoDB;");

	execute_sql("Insert local license poller entries", "INSERT INTO `lic_pollers` (id, poller_path, poller_description, poller_hostname, poller_type) VALUES
		(1, '/opt/rtm/lic/bin', 'FLEXlm poller', 'local', 1)");

	create_table("lic_flexlm_log", "CREATE TABLE `lic_flexlm_log` (
		`id` int(12) unsigned NOT NULL auto_increment,
		`portatserver` int(12) NOT NULL,
		`vendor_daemon` varchar(100) NOT NULL,
		`feature` varchar(50) NOT NULL,
		`action` varchar(50) NOT NULL,
		`no_of_license_out_in` int(12) NOT NULL default 1,
		`user` varchar(200) NOT NULL,
		`host` varchar(200) NOT NULL,
		`reasons` text NOT NULL,
		`datetime` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`id`)
		) ENGINE=InnoDB;");

	create_table("lic_flexlm_servers", "CREATE TABLE `lic_flexlm_servers` (
		`portatserver_id` int(10) unsigned NOT NULL,
		`poller_interval` int(10) unsigned NOT NULL default '300',
		`poller_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`poller_trigger` int(11) NOT NULL default '0',
		`daemon_type` int(10) unsigned NOT NULL default '1',
		`server_portatserver` varchar(512) NOT NULL default '',
		`server_timezone` varchar(64) NOT NULL,
		`server_querybin_path` varchar(255) NOT NULL default '',
		`server_name` varchar(45) NOT NULL default '',
		`server_vendor` varchar(60) NOT NULL default '',
		`server_licensetype` varchar(20) default NULL,
		`server_licensefile` varchar(255) NOT NULL default '',
		`server_department` varchar(45) default NULL,
		`server_location` varchar(100) NOT NULL default '',
		`server_support_info` varchar(255) NOT NULL default '',
		`enable_checkouts` varchar(20) NOT NULL default '',
		`timeout` int(10) unsigned NOT NULL default '1',
		`retries` int(10) unsigned NOT NULL default '3',
		`status` int(10) unsigned NOT NULL default '0',
		`status_event_count` int(10) unsigned NOT NULL default '0',
		`cur_time` decimal(10,5) NOT NULL default '0.00000',
		`min_time` decimal(10,5) NOT NULL default '0.00000',
		`max_time` decimal(10,5) NOT NULL default '0.00000',
		`avg_time` decimal(10,5) NOT NULL default '0.00000',
		`total_polls` int(10) unsigned NOT NULL default '0',
		`failed_polls` int(10) unsigned NOT NULL default '0',
		`status_fail_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`status_rec_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`availability` decimal(8,5) NOT NULL default '0.00000',
		`file_path` varchar(255) NOT NULL default '',
		`prefix` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`portatserver_id`),
		KEY `server_location` (`server_location`)
		) ENGINE=InnoDB;");
	create_table("lic_flexlm_servers_feature_details", "CREATE TABLE `lic_flexlm_servers_feature_details` (
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
		`restype` INTEGER UNSIGNED NOT NULL DEFAULT '0',
		`status` varchar(20) NOT NULL default '',
		`tokens_acquired` int(10) unsigned NOT NULL default '0',
		`tokens_acquired_date` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`present` tinyint(1) NOT NULL default '1',
		PRIMARY KEY (`portatserver_id`, `vendor_daemon`, `feature_name`, `username`, `groupname`, `hostname`, `chkoutid`, `restype`, `status`, `tokens_acquired_date`),
		INDEX `idx_portatserver_id` (`portatserver_id`),
		INDEX `idx_poller_id` (`poller_id`),
		INDEX `idx_vendor_daemon` (`vendor_daemon`),
		INDEX `idx_feature_name` (`feature_name`),
		INDEX `idx_username` (`username`),
		INDEX `idx_hostname` (`hostname`),
		INDEX `idx_status` (`status`)
		) ENGINE=MEMORY;");
	create_table("lic_flexlm_servers_feature_expirations", "CREATE TABLE `lic_flexlm_servers_feature_expirations` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature_name` varchar(50) NOT NULL default '',
		`feature_version` varchar(20) NOT NULL default '',
		`feature_number_to_expire` int(10) unsigned NOT NULL default '0',
		`feature_expiration_date` timestamp  NOT NULL default '0000-00-00 00:00:00',
		`vendor_daemon` varchar(45) NOT NULL default '',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  USING HASH (`poller_id`,`portatserver_id`,`feature_name`,`feature_version`,`vendor_daemon`,`feature_expiration_date`),
		KEY `poller_id` (`poller_id`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `feature_name` (`feature_name`),
		KEY `feature_version` (`feature_version`),
		KEY `vendor_daemon` (`vendor_daemon`)
		) ENGINE=MEMORY;");
	create_table("lic_flexlm_servers_feature_use", "CREATE TABLE `lic_flexlm_servers_feature_use` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature_name` varchar(50) NOT NULL default '',
		`feature_max_licenses` int(10) unsigned NOT NULL default '0',
		`feature_inuse_licenses` int(10) unsigned NOT NULL default '0',
		`feature_queued` int(10) unsigned NOT NULL default '0',
		`feature_reserved` int(10) unsigned NOT NULL default '0',
		`vendor_daemon` varchar(45) NOT NULL default 'TBD',
		`present` tinyint(1) NOT NULL default '1',
		`vendor_status` varchar(10) NOT NULL default '',
		`vendor_version` varchar(30) NOT NULL default '',
		`status` varchar(29) NOT NULL default '',
		PRIMARY KEY  (`poller_id`,`feature_name`,`portatserver_id`),
		KEY `poller_id` (`poller_id`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `vendor_daemon` (`vendor_daemon`),
		KEY `feature_name` (`feature_name`),
		KEY `feature_queued` (`feature_queued`),
		KEY `feature_reserved` (`feature_reserved`)
		) ENGINE=MEMORY;");
	create_table("lic_flexlm_quorum_servers", "CREATE TABLE `lic_flexlm_quorum_servers` (
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`name` varchar(100) NOT NULL default '',
		`status` varchar(20) NOT NULL default '',
		`type` varchar(50) NOT NULL default '',
		`version` varchar(20) NOT NULL default '',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY (`portatserver_id`, `name`),
		KEY `portatserver_id` (`portatserver_id`),
		KEY `name` (`name`)
		) ENGINE=MEMORY;");
	create_table("lic_interval_stats", "CREATE TABLE `lic_interval_stats` (
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature` varchar(100) NOT NULL default '',
		`user` varchar(100) NOT NULL default '',
		`host` varchar(100) NOT NULL default '',
		`action` varchar(20) NOT NULL default '',
		`count` int(10) unsigned NOT NULL default '0',
		`total_license_count` int(10) unsigned NOT NULL default '0',
		`utilization` float NOT NULL default '0',
		`vendor` varchar(100) NOT NULL default '0',
		`duration` int(10) unsigned NOT NULL default '0',
		`interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
		`event_id` int(12) unsigned NOT NULL default '0',
		PRIMARY KEY (`portatserver_id`, `feature`, `user`, `host`, `action`, `vendor`, `date_recorded`, `event_id`),
		KEY `feature` (`feature`),
		KEY `interval_end` (`interval_end`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `vendor` (`vendor`),
		KEY `event_id` (`event_id`)
		) ENGINE=InnoDB;");
	create_table("lic_daily_stats", "CREATE TABLE `lic_daily_stats` (
		`portatserver_id` int(10) unsigned NOT NULL default '0',
		`feature` varchar(100) NOT NULL default '',
		`user` varchar(100) NOT NULL default '',
		`host` varchar(100) NOT NULL default '',
		`action` varchar(20) NOT NULL default '',
		`count` int(10) unsigned NOT NULL default '0',
		`total_license_count` int(10) unsigned NOT NULL default '0',
		`utilization` float NOT NULL default '0',
		`vendor` varchar(100) NOT NULL default '0',
		`duration` int(10) unsigned NOT NULL default '0',
		`transaction_count` int(10) unsigned NOT NULL default '0',
		`interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (`portatserver_id`, `feature`, `user`, `host`, `action`, `vendor`, `date_recorded`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `vendor` (`vendor`)
		) ENGINE=InnoDB;");

	/* migrate FLEXlm license servers from old tables */
	execute_sql("Insert lic_servers entries from grid_license_servers", "INSERT INTO lic_servers
		(id, lic_description, lic_poller_id, disabled)
		SELECT portatserver_id, server_name, '1', disabled FROM grid_license_servers;");
	execute_sql("Insert lic_flexlm_servers entries from grid_license_servers",
		"INSERT INTO lic_flexlm_servers (portatserver_id, poller_interval, poller_date, poller_trigger,
		daemon_type, server_portatserver, server_timezone, server_querybin_path, server_name, server_vendor,
		server_licensetype, server_department, server_location, server_support_info, enable_checkouts,
		timeout, retries, status, status_event_count, cur_time, min_time, max_time, avg_time, total_polls,
		failed_polls, status_fail_date, status_rec_date, availability)
		SELECT portatserver_id, poller_interval, poller_date, poller_trigger, daemon_type, server_portatserver,
		server_timezone, server_querybin_path, server_name, server_master, server_licensetype,
		server_department, server_location, server_support_info, (CASE WHEN enable_checkouts = 'on' THEN 'on' ELSE 'off' END) as enable_checkouts,
		timeout, retries, status, status_event_count, cur_time, min_time, max_time, avg_time, total_polls,
		failed_polls, status_fail_date, status_rec_date, availability FROM grid_license_servers");

	// restore appkey
	$appkeystr = get_appkey();
	if (strlen($appkeystr) > 0 ) {
		db_execute("REPLACE INTO settings (name, value) VALUES ('app_key', '".$appkeystr."');");
	} else {
		print "\nERROR: Unable to restore appkey. Please insert contents of ".RTM_ROOT."/etc/.appkey file into settings table under name='app_key'.";
	}

	// Insert values to settings
	db_execute('INSERT INTO settings (name, value) VALUES ("grid_os", "OFF")');

	// Add columns to Thold data
	$column_arr= array(
		'persist_ack' => "ADD COLUMN `persist_ack` CHAR(3) NOT NULL DEFAULT 'off' AFTER `reset_ack`",
		'prev_thold_alert' => "ADD COLUMN `prev_thold_alert` int(1) NOT NULL DEFAULT '0' AFTER `thold_alert`"
		);
	$index_arr= array(
		'tcheck' => "ADD INDEX tcheck(`tcheck`)"
		);
	add_columns_indexes("thold_data", $column_arr, $index_arr);

	// Thold Templates
	$thold_templates = array(
		"1" => array (
			'value' => 'FLEXlm license feature usage percentage',
			'name' => 'cacti_graph_template_alert_-_flexlm_license_feature_usage_.xml'
		)
	);
	foreach($thold_templates as $tholdtemplates) {
		$results = do_import($config["base_path"]."/plugins/thold/extras/".$tholdtemplates['name']);
	}
	// end Thold Templates

	// gridcstat
	create_table("grid_job_daily_user_stats", "CREATE TABLE  `grid_job_daily_user_stats` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`user` varchar(45) NOT NULL default '',
		`wall_time` bigint(20) unsigned NOT NULL default '0',
		`total_wall_time` bigint(20) unsigned NOT NULL default '0',
		`cpu_time` bigint(20) unsigned NOT NULL default '0',
		`total_cpu_time` bigint(20) unsigned NOT NULL default '0',
		`slots_done` int(10) unsigned NOT NULL default '0',
		`slots_exited` int(10) unsigned NOT NULL default '0',
		`interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
		`interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  USING BTREE (`clusterid`,`user`,`interval_start`,`interval_end`),
		KEY `interval_start` (`interval_start`),
		KEY `interval_end` USING BTREE (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `user` (`user`)
		) ENGINE=InnoDB;");
	create_table("grid_job_daily_usergroup_stats", "CREATE TABLE  `grid_job_daily_usergroup_stats` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`usergroup` varchar(45) NOT NULL default '',
		`wall_time` bigint(20) unsigned NOT NULL default '0',
		`total_wall_time` bigint(20) unsigned NOT NULL default '0',
		`cpu_time` bigint(20) unsigned NOT NULL default '0',
		`total_cpu_time` bigint(20) unsigned NOT NULL default '0',
		`slots_done` int(10) unsigned NOT NULL default '0',
		`slots_exited` int(10) unsigned NOT NULL default '0',
		`interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
		`interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  USING BTREE (`clusterid`,`usergroup`,`interval_start`,`interval_end`),
		KEY `interval_start` (`interval_start`),
		KEY `interval_end` USING BTREE (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `usergroup` (`usergroup`)
		) ENGINE=InnoDB;");

	execute_sql("Add gridcstat user realm to admin user", "REPLACE INTO user_auth_realm
		VALUES (1012, 1);");
	// end gridcstat

	// Idled Jobs and Mem Violations Tables
	execute_sql("Add Idled Jobs Table to Cacti",
		"CREATE TABLE  `grid_jobs_idled` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`jobid` int(10) unsigned NOT NULL,
		`indexid` varchar(45) NOT NULL,
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`cumulative_cpu` int(10) unsigned NOT NULL,
		`notified` tinyint(3) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '1',
		PRIMARY KEY  (`clusterid`,`submit_time`,`indexid`,`jobid`),
		KEY `present` (`present`)) ENGINE=InnoDB;");
	execute_sql("Add Memory Violations Table to Cacti",
		"CREATE TABLE  `grid_jobs_memvio` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`jobid` int(10) unsigned NOT NULL,
		`indexid` varchar(45) NOT NULL,
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`rusage_memory` int(10) unsigned NOT NULL,
		`rusage_swap` int(10) unsigned NOT NULL,
		`max_memory` int(10) unsigned NOT NULL,
		`max_swap` int(10) unsigned NOT NULL,
		`notified` tinyint(3) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '1',
		PRIMARY KEY  (`clusterid`,`submit_time`,`indexid`,`jobid`)) ENGINE=InnoDB;");
	// End Idled Jobs and Mem Violations Tables
}
