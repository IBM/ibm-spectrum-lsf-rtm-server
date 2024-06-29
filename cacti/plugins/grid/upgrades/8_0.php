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

function upgrade_to_8_0() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	modify_column("data_local", "snmp_index", "ALTER TABLE `data_local` MODIFY COLUMN `snmp_index` VARCHAR(255) NOT NULL DEFAULT '';");
	modify_column("graph_local", "snmp_index", "ALTER TABLE `graph_local` MODIFY COLUMN `snmp_index` VARCHAR(255) NOT NULL DEFAULT '';");
	modify_column("poller_item", "rrd_step", "ALTER TABLE `poller_item` MODIFY COLUMN `rrd_step` MEDIUMINT(8) NOT NULL DEFAULT 300");
	modify_column("graph_tree_items", "id", "ALTER TABLE graph_tree_items MODIFY COLUMN id MEDIUMINT(8) unsigned NOT NULL auto_increment");

	execute_sql("Update name in settings", "UPDATE settings SET name = 'deletion_verification' WHERE name = 'remove_verification'");

	add_index("data_input_fields", "type_code", "ALTER TABLE `data_input_fields` ADD INDEX `type_code`(`type_code`);");
	add_index("data_template_rrd", "local_data_template_rrd_id", "ALTER TABLE `data_template_rrd` ADD INDEX `local_data_template_rrd_id`(`local_data_template_rrd_id`);");
	add_index("graph_local", "snmp_query_id", "ALTER TABLE `graph_local` ADD INDEX `snmp_query_id`(`snmp_query_id`);");
	add_index("host_snmp_cache", "field_name", "ALTER TABLE `host_snmp_cache` ADD INDEX `field_name`(`field_name`);");
	add_index("host_snmp_cache", "field_value", "ALTER TABLE `host_snmp_cache` ADD INDEX `field_value`(`field_value`);");
	add_index("host_snmp_cache", "snmp_query_id", "ALTER TABLE `host_snmp_cache` ADD INDEX `snmp_query_id`(`snmp_query_id`);");
	add_index("snmp_query_graph_rrd", "data_template_rrd_id", "ALTER TABLE `snmp_query_graph_rrd` ADD INDEX `data_template_rrd_id`(`data_template_rrd_id`);");
	add_index("snmp_query_graph_rrd_sv", "data_template_id", "ALTER TABLE `snmp_query_graph_rrd_sv` ADD INDEX `data_template_id`(`data_template_id`);");

	$column_arr= array(
		'lsf_confdir' => "ADD COLUMN `lsf_confdir` VARCHAR(255) NOT NULL DEFAULT '' AFTER `lsf_envdir`",
		'ego_confdir' => "ADD COLUMN `ego_confdir` VARCHAR(255) NOT NULL DEFAULT '' AFTER `lsf_confdir`",
		'ha_timing' => "ADD COLUMN `ha_timing` int(10) unsigned NOT NULL AFTER `job_major_timing`",
		'credential' => "ADD COLUMN `credential` varchar(512) NOT NULL default '' AFTER `username`",
		'grididle_enabled' => "ADD COLUMN `grididle_enabled` char(2) NOT NULL default '' AFTER `email_domain`",
		'grididle_notify' => "ADD COLUMN `grididle_notify` int(1) NOT NULL default '0' AFTER `grididle_enabled`",
		'grididle_runtime' => "ADD COLUMN `grididle_runtime` int(10) NOT NULL default '3600' AFTER `grididle_notify`",
		'grididle_window' => "ADD COLUMN `grididle_window` int(10) NOT NULL default '3600' AFTER `grididle_runtime`",
		'grididle_cputime' => "ADD COLUMN `grididle_cputime` int(10) NOT NULL default '24' AFTER `grididle_window`",
		'grididle_jobtypes' => "ADD COLUMN `grididle_jobtypes` varchar(20) NOT NULL default 'all' AFTER `grididle_cputime`",
		'grididle_jobcommands' => "ADD COLUMN `grididle_jobcommands` varchar(255) NOT NULL default '' AFTER `grididle_jobtypes`",
		'grididle_exclude_queues' => "ADD COLUMN `grididle_exclude_queues` varchar(255) NOT NULL default '' AFTER `grididle_jobcommands`"
		);
	add_columns_indexes("grid_clusters", $column_arr, NULL);

	create_table("grid_ha_config", "CREATE TABLE `grid_ha_config` (
		`id` varchar(50) NOT NULL,
		`name` varchar(40) NOT NULL,
		`version` varchar(10) default NULL,
		`clusterid` int(10) NOT NULL,
		`description` varchar(255) default NULL,
		`primary_host` varchar(255) NOT NULL,
		`failover_host` varchar(255) NOT NULL,
		`failover_interval` int(10) default NULL,
		`exec_start` varchar(512) NOT NULL,
		`exec_stop` varchar(512) default NULL,
		`control_wait` int(10) default NULL,
		`username` varchar(50) NOT NULL,
		`password` varchar(50) default NULL,
		`dependency` varchar(255) default NULL,
		PRIMARY KEY  (`id`))
		ENGINE=InnoDB");

	create_table("grid_ha_status", "CREATE TABLE `grid_ha_status` (
		`id` varchar(128) NOT NULL,
		`name` varchar(110) default NULL,
		`clusterid` int(10) default NULL,
		`processid` int(32) default NULL,
		`status` int(10) default NULL,
		`running_host` varchar(255) default NULL,
		`start_time` varchar(64) default NULL,
		`need_alert` int(10) default NULL,
		PRIMARY KEY  (`id`),
		KEY `name` (`name`),
		KEY `clusterid` (`clusterid`),
		KEY `status` (`status`))
		ENGINE=InnoDB
		COMMENT='Table of HA application status';");

	create_table("grid_ha_status_string", "CREATE TABLE `grid_ha_status_string` (
		`status` int(10) NOT NULL default '0',
		`status_string` varchar(16) default NULL,
		PRIMARY KEY  (`status`),
		KEY `status_string` (`status_string`))
		ENGINE=InnoDB
		COMMENT='Table of status meaning';");

	create_table("grid_ha_template", "CREATE TABLE `grid_ha_template` (
		`id` varchar(50) NOT NULL,
		`name` varchar(40) NOT NULL,
		`description` varchar(255) default NULL,
		`clusterid` int(10) NOT NULL,
		`date_time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`change_user` varchar(50) NOT NULL,
		PRIMARY KEY  (`id`))
		ENGINE=InnoDB;");

	create_table("grid_ha_template_details", "CREATE TABLE `grid_ha_template_details` (
		`template_id` varchar(50) NOT NULL,
		`app_id` varchar(255) NOT NULL,
		`name` varchar(50) NOT NULL,
		`version` varchar(10) default NULL,
		`clusterid` int(10) NOT NULL,
		`description` varchar(255) default NULL,
		`primary_host` varchar(255) NOT NULL,
		`failover_host` varchar(255) NOT NULL,
		`failover_interval` int(10) default NULL,
		`exec_start` varchar(512) NOT NULL,
		`exec_stop` varchar(512) default NULL,
		`control_wait` int(10) default NULL,
		`username` varchar(50) NOT NULL,
		`password` varchar(50) default NULL,
		`dependency` varchar(255) default NULL,
		PRIMARY KEY  (`template_id`,`app_id`)) ENGINE=InnoDB;");

	create_table("grid_lsf_config_audit_log", "CREATE TABLE `grid_lsf_config_audit_log` (
		`id` int(12) NOT NULL auto_increment,
		`userid` int(12) NOT NULL default '0',
		`cluster_id` int(12) NOT NULL default '0',
		`config_id` int(12) unsigned NOT NULL default '0',
		`config_name` varchar(150) NOT NULL default '',
		`item_name` varchar(150) NOT NULL default '',
		`item_type` varchar(20) NOT NULL default '',
		`action` varchar(20) NOT NULL default '',
		`item_id` int(12) NOT NULL default '0',
		`status` int(11) NOT NULL default '0',
		`message` text NOT NULL,
		`time` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`id`),
		KEY `time` (`time`),
		KEY `userid` (`userid`),
		KEY `cluster_id` (`cluster_id`),
		KEY `config_id` (`config_id`),
		KEY `status` (`status`))
		ENGINE=InnoDB
		COMMENT='Table of lsf_config audit log';");

	create_table("grid_lsf_config_item", "CREATE TABLE `grid_lsf_config_item` (
		`item_id` int(12) NOT NULL auto_increment,
		`item_name` varchar(255) NOT NULL default '',
		`item_type` varchar(50) NOT NULL default '',
		`config_id` int(12) NOT NULL default '0',
		PRIMARY KEY  (`item_id`),
		KEY `item_id` (`item_id`),
		KEY `item_type` (`item_type`),
		KEY `config_id` (`config_id`))
		ENGINE=InnoDB
		COMMENT='Table of grid_lsf_config_item';");

	create_table("grid_lsf_config_item_attribute", "CREATE TABLE `grid_lsf_config_item_attribute` (
		`id` int(12) NOT NULL auto_increment,
		`key_attr` varchar(255) NOT NULL default '',
		`value_attr` varchar(512) NOT NULL default '',
		`item_id` int(12) NOT NULL default '0',
		PRIMARY KEY  (`id`),
		KEY `id` (`id`),
		KEY `item_id` (`item_id`))
		ENGINE=InnoDB
		COMMENT='Table of grid_lsf_config_item_attribute';");

	create_table("grid_lsf_configuration", "CREATE TABLE `grid_lsf_configuration` (
		`config_id` int(12) NOT NULL auto_increment,
		`cluster_id` int(12) NOT NULL default '0',
		`config_name` varchar(150) NOT NULL default '',
		`status` int(11) NOT NULL default '0',
		`cur_in_use` tinyint(3) NOT NULL default '0',
		`datetime` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`config_id`),
		KEY `config_id` (`config_id`),
		KEY `cluster_id` (`cluster_id`))
		ENGINE=InnoDB
		COMMENT='Table of grid_lsf_configuration ';");

	create_table("grid_applications", "CREATE TABLE `grid_applications` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`appName` varchar(64) NOT NULL default '',
		`numRUN` int(10) unsigned NOT NULL default '0',
		`numPEND` int(10) unsigned NOT NULL default '0',
		`numJOBS` int(10) unsigned NOT NULL default '0',
		`efficiency` double NOT NULL default '0',
		`avg_mem` double NOT NULL default '0',
		`max_mem` double NOT NULL default '0',
		`avg_swap` double NOT NULL default '0',
		`max_swap` double NOT NULL default '0',
		`total_cpu` bigint(20) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`appName`))
		ENGINE=InnoDB;");

	$column_arr= array(
		'app' => "ADD COLUMN `app` VARCHAR(512) NOT NULL DEFAULT '' AFTER `postExecCmd`",
		'exceptMask' => "ADD COLUMN `exceptMask` int(10) NOT NULL default '0' AFTER `cwd`",
		'exitInfo' => "ADD COLUMN `exitInfo` int(10) NOT NULL default '-1' AFTER `exceptMask`"
		);
	add_columns_indexes("grid_jobs", $column_arr, NULL);
	add_columns_indexes("grid_jobs_finished", $column_arr, NULL);

	modify_column("grid_jobs", "jobGroup", "ALTER TABLE `grid_jobs` MODIFY COLUMN `jobGroup` VARCHAR(512) NOT NULL DEFAULT '';");
	modify_column("grid_jobs_finished", "jobGroup", "ALTER TABLE `grid_jobs_finished` MODIFY COLUMN `jobGroup` VARCHAR(512) NOT NULL DEFAULT '';");

	create_table("grid_groups", "CREATE TABLE `grid_groups` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`groupName` varchar(512) NOT NULL default '',
		`numRUN` int(10) unsigned NOT NULL default '0',
		`numPEND` int(10) unsigned NOT NULL default '0',
		`numJOBS` int(10) unsigned NOT NULL default '0',
		`numSSUSP` int(10) unsigned NOT NULL default '0',
  		`numUSUSP` int(10) unsigned NOT NULL default '0',
		`efficiency` double NOT NULL default '0',
		`avg_mem` double NOT NULL default '0',
		`max_mem` double NOT NULL default '0',
		`avg_swap` double NOT NULL default '0',
		`max_swap` double NOT NULL default '0',
		`total_cpu` bigint(20) unsigned NOT NULL default '0',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`groupName`))
		ENGINE=InnoDB");

	create_table("grid_jobs_pendreasons", "CREATE TABLE `grid_jobs_pendreasons` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` bigint(20) unsigned  NOT NULL default '0',
		`indexid` int(10) unsigned NOT NULL default '0',
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`issusp` tinyint(3) unsigned NOT NULL default '0',
		`reason` int(10) unsigned NOT NULL default '0',
		`subreason` varchar(40) NOT NULL default '',
		`start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`end_time`),
		KEY `clusterid_end_time` (`clusterid`, `end_time`),
		KEY `job_key` (`clusterid`,`jobid`,`indexid`,`submit_time`)
		) ENGINE=InnoDB;");

	create_table("grid_jobs_pendreasons_finished", "CREATE TABLE `grid_jobs_pendreasons_finished` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` bigint(20) unsigned  NOT NULL default '0',
		`indexid` int(10) unsigned NOT NULL default '0',
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`issusp` tinyint(3) unsigned NOT NULL default '0',
		`reason` int(10) unsigned NOT NULL default '0',
		`subreason` varchar(40) NOT NULL default '',
		`start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`end_time`),
		KEY `clusterid_end_time` (`clusterid`, `end_time`)
		) ENGINE=InnoDB;");

	create_table("grid_pendreasons_ignore", "CREATE TABLE `grid_pendreasons_ignore` (
		`user_id` mediumint(8) unsigned NOT NULL,
		`issusp` tinyint(3) unsigned NOT NULL default '0',
		`reason` int(10) unsigned NOT NULL,
		`subreason` varchar(40) NOT NULL default '',
		`last_updated` timestamp,
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY (`user_id`,`issusp`,`reason`,`subreason`)
		) ENGINE=InnoDB;");

	create_table("grid_jobs_pendreason_maps", "CREATE TABLE `grid_jobs_pendreason_maps` (
		`issusp` tinyint(3) unsigned NOT NULL default '0',
		`reason_code` int(10) unsigned NOT NULL default '0',
		`sub_reason_code` varchar(40) NOT NULL default '',
		`reason` varchar(256) NOT NULL default '',
		PRIMARY KEY  (`issusp`,`reason_code`,`sub_reason_code`)
		) ENGINE=InnoDB;");

	/* Drop legacy tables for PDB#153339 */
	db_execute("DROP TABLE IF EXISTS grid_cont_users");
	db_execute("DROP TABLE IF EXISTS grid_job_collection_details");
	db_execute("DROP TABLE IF EXISTS grid_job_collection_members");
	db_execute("DROP TABLE IF EXISTS grid_job_collection_patterns");
	execute_sql("Add Additional Queue Columns", "ALTER TABLE `grid_queues`
		MODIFY COLUMN `userJobLimit` VARCHAR(5) NOT NULL default '',
		MODIFY COLUMN `procJobLimit` VARCHAR(5) NOT NULL default '',
		MODIFY COLUMN `hostJobLimit` VARCHAR(5) NOT NULL default '',
		ADD COLUMN `dedicatedSlots` INTEGER NOT NULL default '0' AFTER `total_cpu`,
		ADD COLUMN `sharedSlots` INTEGER NOT NULL default '0' AFTER `dedicatedSlots`,
		ADD COLUMN `openDedicatedSlots` INTEGER NOT NULL default '0' AFTER `sharedSlots`,
		ADD COLUMN `openSharedSlots` INTEGER NOT NULL default '0' AFTER `openDedicatedSlots`,
		ADD COLUMN `windows` VARCHAR(255) NOT NULL AFTER `openSharedSlots`,
		ADD COLUMN `windowsD` VARCHAR(255) NOT NULL AFTER `windows`,
		ADD COLUMN `hostSpec` VARCHAR(64) NOT NULL AFTER `windowsD`,
		ADD COLUMN `qAttrib` INTEGER NOT NULL default '0' AFTER `hostSpec`,
		ADD COLUMN `qStatus` INTEGER UNSIGNED NOT NULL AFTER `qAttrib`,
		ADD COLUMN `userShares` VARCHAR(255) NOT NULL AFTER `qStatus`,
		ADD COLUMN `defaultHostSpec` VARCHAR(64) NOT NULL AFTER `userShares`,
		ADD COLUMN `procLimit` VARCHAR(5) NOT NULL default '' AFTER `defaultHostSpec`,
		ADD COLUMN `admins` VARCHAR(255) NOT NULL AFTER `procLimit`,
		ADD COLUMN `preCmd` VARCHAR(255) NOT NULL AFTER `admins`,
		ADD COLUMN `postCmd` VARCHAR(255) NOT NULL AFTER `preCmd`,
		ADD COLUMN `requeueEValues` VARCHAR(64) NOT NULL AFTER `postCmd`,
		ADD COLUMN `resReq` VARCHAR(255) NOT NULL AFTER `requeueEValues`,
		ADD COLUMN `slotHoldTime` INTEGER UNSIGNED NOT NULL AFTER `resReq`,
		ADD COLUMN `sndJobsTo` VARCHAR(255) NOT NULL AFTER `slotHoldTime`,
		ADD COLUMN `rcvJobsFrom` VARCHAR(255) NOT NULL AFTER `sndJobsTo`,
		ADD COLUMN `resumeCond` VARCHAR(255) NOT NULL AFTER `rcvJobsFrom`,
		ADD COLUMN `stopCond` VARCHAR(255) NOT NULL AFTER `resumeCond`,
		ADD COLUMN `jobStarter` VARCHAR(255) NOT NULL AFTER `stopCond`,
		ADD COLUMN `suspendActCmd` VARCHAR(255) NOT NULL AFTER `jobStarter`,
		ADD COLUMN `resumeActCmd` VARCHAR(255) NOT NULL AFTER `suspendActCmd`,
		ADD COLUMN `terminateActCmd` VARCHAR(255) NOT NULL AFTER `resumeActCmd`,
		ADD COLUMN `preemption` VARCHAR(255) NOT NULL AFTER `terminateActCmd`,
		ADD COLUMN `maxRschedTime` INTEGER UNSIGNED NOT NULL AFTER `preemption`,
		ADD COLUMN `maxJobRequeue` INTEGER UNSIGNED NOT NULL AFTER `maxRschedTime`,
		ADD COLUMN `chkpntDir` VARCHAR(255) NOT NULL AFTER `maxJobRequeue`,
		ADD COLUMN `chkpntPeriod` INTEGER NOT NULL default '0' AFTER `chkpntDir`,
		ADD COLUMN `imptJobBklg` INTEGER NOT NULL default '0' AFTER `chkpntPeriod`,
		ADD COLUMN `chunkJobSize` INTEGER UNSIGNED NOT NULL AFTER `imptJobBklg`,
		ADD COLUMN `minProcLimit` INTEGER NOT NULL default '0' AFTER `chunkJobSize`,
		ADD COLUMN `defProcLimit` INTEGER NOT NULL default '0' AFTER `minProcLimit`,
		ADD COLUMN `fairshareQueues` VARCHAR(255) NOT NULL AFTER `defProcLimit`,
		ADD COLUMN `defExtSched` VARCHAR(255) NOT NULL AFTER `fairshareQueues`,
		ADD COLUMN `mandExtSched` VARCHAR(255) NOT NULL AFTER `defExtSched`,
		ADD COLUMN `slotShare` INTEGER NOT NULL default '0' AFTER `mandExtSched`,
		ADD COLUMN `slotPool` VARCHAR(255) NOT NULL AFTER `slotShare`,
		ADD COLUMN `underRCond` INTEGER UNSIGNED NOT NULL AFTER `slotPool`,
		ADD COLUMN `overRCond` INTEGER UNSIGNED NOT NULL AFTER `underRCond`,
		ADD COLUMN `idleCond` DOUBLE NOT NULL AFTER `overRCond`,
		ADD COLUMN `underRJobs` INTEGER UNSIGNED NOT NULL AFTER `idleCond`,
		ADD COLUMN `overRJobs` INTEGER UNSIGNED NOT NULL AFTER `underRJobs`,
		ADD COLUMN `idleJobs` INTEGER UNSIGNED NOT NULL AFTER `overRJobs`,
		ADD COLUMN `warningTimePeriod` INTEGER NOT NULL default '0' AFTER `idleJobs`,
		ADD COLUMN `warningAction` VARCHAR(255) NOT NULL AFTER `warningTimePeriod`,
		ADD COLUMN `qCtrlMsg` VARCHAR(255) NOT NULL AFTER `warningAction`,
		ADD COLUMN `rlimit_max_cpu` int(10) unsigned NOT NULL default '0' AFTER `qCtrlMsg`,
		ADD COLUMN `rlimit_max_wallt` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_cpu`,
		ADD COLUMN `rlimit_max_swap` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_wallt`,
		ADD COLUMN `rlimit_max_fsize` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_swap`,
		ADD COLUMN `rlimit_max_data` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_fsize`,
		ADD COLUMN `rlimit_max_stack` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_data`,
		ADD COLUMN `rlimit_max_core` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_stack`,
		ADD COLUMN `rlimit_max_rss` int(10) unsigned NOT NULL default '0' AFTER `rlimit_max_core`");

	execute_sql("Adding Load Thresholds for the Queues Table",
		"CREATE TABLE  `grid_queues_thresholds` (
		`clusterid` int(10) unsigned NOT NULL,
		`queue` varchar(45) NOT NULL,
		`loadSched` double NOT NULL,
		`loadStop` double NOT NULL,
		PRIMARY KEY  (`clusterid`,`queue`))
		ENGINE=InnoDB
		COMMENT='Dermines Queue Scheduling Load Thresholds'");

	/* add region for flexlm server */
	add_column("lic_flexlm_servers", "server_region", "ALTER TABLE `lic_flexlm_servers`
		ADD COLUMN `server_region` VARCHAR(100) NOT NULL DEFAULT '' AFTER `server_location`;");
	add_index("lic_flexlm_servers", "server_region", "ALTER TABLE `lic_flexlm_servers` ADD INDEX `server_region`(`server_region`);");

	/* add last update times to key tables for graph management */
	db_execute("ALTER TABLE `grid_projects` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `total_cpu`");
	db_execute("UPDATE `grid_projects` SET last_updated=NOW()");

	db_execute("ALTER TABLE `grid_license_projects` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `total_cpu`");
	db_execute("UPDATE `grid_license_projects` SET last_updated=NOW()");

	db_execute("ALTER TABLE `grid_hostgroups_stats` ADD COLUMN `numRUN` int(10) unsigned NOT NULL default '0' AFTER `total_cpu`");
	db_execute("ALTER TABLE `grid_hostgroups_stats` ADD COLUMN `numJOBS` int(10) unsigned NOT NULL default '0' AFTER `numRUN`");

	db_execute("ALTER TABLE `grid_hostgroups_stats` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `numJOBS`");
	db_execute("UPDATE `grid_hostgroups_stats` SET last_updated=NOW()");

	db_execute("ALTER TABLE `grid_groups` DROP COLUMN `last_updated`");
	db_execute("ALTER TABLE `grid_groups` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `total_cpu`");
	db_execute("UPDATE `grid_groups` SET last_updated=NOW()");

	db_execute("ALTER TABLE `grid_applications` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `total_cpu`");
	db_execute("UPDATE `grid_applications` SET last_updated=NOW()");

	db_execute("ALTER TABLE `grid_user_group_stats` ADD COLUMN `last_updated` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER `total_cpu`");
	db_execute("UPDATE `grid_user_group_stats` SET last_updated=NOW()");

	db_execute("CREATE TABLE `grid_queues_stats` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`queue` varchar(64) NOT NULL,
		`numRUN` int(10) unsigned NOT NULL default '0',
		`numPEND` int(10) unsigned NOT NULL default '0',
		`numJOBS` int(10) unsigned NOT NULL default '0',
		`efficiency` double NOT NULL default '0',
		`avg_mem` double NOT NULL default '0',
		`max_mem` double NOT NULL default '0',
		`avg_swap` double NOT NULL default '0',
		`max_swap` double NOT NULL default '0',
		`total_cpu` bigint(20) unsigned NOT NULL default '0',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`queue`),
		KEY `present` (`present`))
		ENGINE=InnoDB
		COMMENT='Tracks Queue Statistical Information'");

	// increase pid column length
	execute_sql("Increase PID column length for grid_jobs_rusage", "ALTER TABLE grid_jobs_rusage MODIFY COLUMN `pids` varchar(1024) NOT NULL DEFAULT '';");
	execute_sql("Add index to column host for grid_queues_hosts", "ALTER TABLE grid_queues_hosts ADD INDEX `host` (`host`);");
	execute_sql("Add index to column queue for grid_queues_hosts", "ALTER TABLE grid_queues_hosts ADD INDEX `queue` (`queue`);");
	execute_sql("Upgrade cacti version to 0.8.7g", "UPDATE version set cacti='0.8.7g';");

	execute_sql("Add two new dataquery to host template", "REPLACE INTO host_template_snmp_query
		SELECT ht.id, sq.id FROM host_template AS ht, snmp_query AS sq WHERE ht.hash='d8ff1374e732012338d9cd47b9da18d4' and sq.hash in ('0267437b46a292e27772e5c5787719bc', 'cb50d45044ed8d6f76389b600747fad7');");

	execute_sql("Add two new data query to host template 'ucd/net SNMP Host'", "REPLACE INTO host_template_snmp_query
		select ht.id, sq.id from  host_template as ht, snmp_query as sq where ht.hash='07d3fe6a52915f99e642d22e27d967a4' and sq.hash in ('9343eab1f4d88b0e61ffc9d020f35414', '0d1ab53fe37487a5d0b9e1d3ee8c1d0d');");

	execute_sql("Add two new graph template to host template 'ucd/net SNMP Host'", "REPLACE INTO host_template_graph
		select ht.id, gt.id from  host_template as ht, graph_templates as gt where ht.hash='07d3fe6a52915f99e642d22e27d967a4' and gt.hash in ('e8462bbe094e4e9e814d4e681671ea82', '62205afbd4066e5c4700338841e3901e', 'eee71ec20dc7b44635ab185bbf924dc4', '4d2bdea3c52db05896b0d9323076613d', 'a9a2ce15df48242361bea33f786de6ee');");
	/* grid_processes table should be memory  */
	db_execute("ALTER TABLE `grid_processes` ENGINE='MEMORY';");

	create_table("grid_sharedresources", "CREATE TABLE `grid_sharedresources` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`resource_name` varchar(20) NOT NULL,
  		`description` varchar(128) NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL default '1',
  		PRIMARY KEY USING HASH (`clusterid`, `resource_name`),
  		KEY `resource_name` (`resource_name`)
		) ENGINE=MEMORY;");
}
