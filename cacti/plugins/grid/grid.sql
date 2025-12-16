--
-- $Id$
--
--
-- Table structure for table `grid_applications`
--

DROP TABLE IF EXISTS `grid_applications`;
CREATE TABLE `grid_applications` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `appName` varchar(40) NOT NULL default '',
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
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`appName`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_apps`
--

DROP TABLE IF EXISTS `grid_apps`;
CREATE TABLE `grid_apps` (
  `app_id` int(10) unsigned NOT NULL auto_increment,
  `app_name` varchar(45) NOT NULL default '',
  `matchResReq` varchar(45) NOT NULL default '',
  `matchProject` varchar(45) NOT NULL default '',
  `matchJobname` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`app_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_apps_error_codes`
--

DROP TABLE IF EXISTS `grid_apps_error_codes`;
CREATE TABLE `grid_apps_error_codes` (
  `app_id` int(10) unsigned NOT NULL default '0',
  `exit_code` int(11) NOT NULL default '0',
  `reason` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`app_id`,`exit_code`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_arrays`
--

DROP TABLE IF EXISTS `grid_arrays`;
CREATE TABLE `grid_arrays` (
  `clusterid` int(10) unsigned NOT NULL,
  `jobid` int(10) unsigned NOT NULL,
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `stat` int(10) unsigned NOT NULL default '0',
  `jType` int(10) unsigned NOT NULL default '0',
  `jName` varchar(3000) NOT NULL default '',
  `user` varchar(60) NOT NULL default '',
  `userGroup` varchar(60) default '',
  `queue` varchar(45) NOT NULL default '',
  `projectName` varchar(256) NOT NULL default '0',
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
  KEY `clusterid_stat` (`clusterid`,`stat`)
) ENGINE=InnoDB COMMENT='Job Groups and Array';

--
-- Table structure for table `grid_arrays_finished`
--

DROP TABLE IF EXISTS `grid_arrays_finished`;
CREATE TABLE `grid_arrays_finished` (
  `clusterid` int(10) unsigned NOT NULL,
  `jobid` int(10) unsigned NOT NULL,
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `stat` int(10) unsigned NOT NULL default '0',
  `jType` int(10) unsigned NOT NULL default '0',
  `jName` varchar(3000) NOT NULL default '',
  `user` varchar(60) NOT NULL default '',
  `userGroup` varchar(60) default '',
  `queue` varchar(45) NOT NULL default '',
  `projectName` varchar(256) NOT NULL default '0',
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
  KEY `clusterid_stat` (`clusterid`,`stat`)
) ENGINE=InnoDB COMMENT='Finished Job Groups and Array';

--
-- Table structure for table `grid_clusters`
--

DROP TABLE IF EXISTS `grid_clusters`;
CREATE TABLE `grid_clusters` (
  `clusterid` int(10) unsigned NOT NULL auto_increment,
  `poller_id` int(10) unsigned NOT NULL default '1',
  `cacti_host` int(10) unsigned NOT NULL default '0',
  `cacti_tree` int(10) unsigned NOT NULL default '0',
  `clustername` varchar(128) NOT NULL default '',
  `cluster_timezone` varchar(64) NOT NULL default '',
  `efficiency_state` tinyint(3) unsigned NOT NULL default '1',
  `efficiency` double NOT NULL default '100',
  `efficiency_queues` varchar(255) default '',
  `efficiency_warn_count` int(10) unsigned NOT NULL default '0',
  `efficiency_alarm_count` int(10) unsigned NOT NULL default '0',
  `efficiency_clear_count` int(10) unsigned NOT NULL default '0',
  `disabled` char(2) NOT NULL default '',
  `total_hosts` int(10) unsigned NOT NULL default '0',
  `total_cpus` int(10) unsigned NOT NULL default '0',
  `total_clients` int(10) unsigned NOT NULL default '0',
  `hourly_started_jobs` double NOT NULL default '0',
  `hourly_done_jobs` double NOT NULL default '0',
  `hourly_exit_jobs` double NOT NULL default '0',
  `daily_started_jobs` double NOT NULL default '0',
  `daily_done_jobs` double NOT NULL default '0',
  `daily_exit_jobs` double NOT NULL default '0',
  `lim_timeout` int(10) unsigned NOT NULL default '10',
  `mbd_timeout` int(10) unsigned NOT NULL default '10',
  `mbd_job_timeout` int(10) unsigned NOT NULL default '1',
  `mbd_job_retries` int(10) unsigned NOT NULL default '1',
  `lsf_envdir` varchar(255) NOT NULL default '',
  `lsf_confdir` varchar(255) NOT NULL default '',
  `ego_confdir` varchar(255) NOT NULL default '',
  `lsf_version` int(10) unsigned NOT NULL default '62',
  `lsf_clustername` varchar(20) NOT NULL default '',
  `lsf_ls_error` int(10) unsigned NOT NULL default '0',
  `lsf_lsb_error` int(10) unsigned NOT NULL default '0',
  `lsf_lsb_jobs_error` int(10) unsigned NOT NULL default '0',
  `lsf_lim_response` float NOT NULL default '0',
  `lsf_lsb_response` float NOT NULL default '0',
  `lsf_lsb_jobs_response` float NOT NULL default '0',
  `lsf_admins` varchar(256) NOT NULL default '',
  `lsb_debug` varchar(20) NOT NULL default '',
  `lsf_lim_debug` varchar(20) NOT NULL default '',
  `lsf_res_debug` varchar(20) NOT NULL default '',
  `lsf_log_mask` varchar(50) NOT NULL default '',
  `lsf_master` varchar(64) NOT NULL default '',
  `lsf_masterhosts` varchar(1024) NOT NULL default '',
  `lsf_serverhosts` varchar(1024) NOT NULL default '',
  `lsf_lic_schedhosts` varchar(1024) NOT NULL default '',
  `collection_timing` int(10) unsigned NOT NULL,
  `max_nonjob_runtime` int(10) unsigned NOT NULL,
  `job_minor_timing` int(10) unsigned NOT NULL,
  `job_major_timing` int(10) unsigned NOT NULL,
  `ha_timing` int(10) unsigned NOT NULL,
  `max_job_runtime` int(10) unsigned NOT NULL,
  `ip` varchar(255) NOT NULL default '',
  `lim_port` varchar(10) NOT NULL default '',
  `lsf_ego` char(3) default 'N',
  `lsf_strict_checking` varchar(10) NOT NULL default 'N',
  `lsf_krb_auth` char(3) default '',
  `lsf_master_hostname` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  `credential` varchar(512) NOT NULL default '',
  `communication` varchar(10) NOT NULL default 'ssh',
  `privatekey_path` varchar(255) NOT NULL default '',
  `LSF_TOP` varchar(255) NOT NULL default '',
  `add_frequency` int(10) unsigned NOT NULL,
  `host_template_id` mediumint(8) unsigned NOT NULL default '14',
  `add_graph_frequency` int(10) unsigned NOT NULL default '0',
  `advanced_enabled` char(2) NOT NULL default '',
  `email_domain` varchar(64) NOT NULL default '',
  `email_admin` varchar(512) NOT NULL default '',
  `grididle_enabled` char(2) NOT NULL default '',
  `grididle_notify` int(1) NOT NULL default '0',
  `grididle_runtime` int(10) NOT NULL default '3600',
  `grididle_window` int(10) NOT NULL default '3600',
  `grididle_cputime` int(10) NOT NULL default '24',
  `grididle_jobtypes` varchar(20) NOT NULL default 'all',
  `grididle_jobcommands` varchar(255) NOT NULL default '',
  `grididle_exclude_queues` varchar(255) NOT NULL default '',
  `perfmon_run` char(3) default '' ,
  `perfmon_interval` int unsigned default '0',
  PRIMARY KEY  (`clusterid`),
  KEY `poller_id_disabled` (`poller_id`,`disabled`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_clusters_dashboard_items`
--

DROP TABLE IF EXISTS `grid_clusters_dashboard_items`;
CREATE TABLE `grid_clusters_dashboard_items` (
  `dashboard_item_id` int(10) unsigned NOT NULL auto_increment,
  `clusterid` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `type` varchar(10) NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `sequence` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`dashboard_item_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_groups`
--

DROP TABLE IF EXISTS `grid_groups`;
CREATE TABLE `grid_groups` (
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
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`groupName`(191))
) ENGINE=InnoDB;

--
-- Table structure for table `grid_hostgroups`
--

DROP TABLE IF EXISTS `grid_hostgroups`;
CREATE TABLE `grid_hostgroups` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `groupName` varchar(64) NOT NULL default '',
  `host` varchar(64) NOT NULL default '',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`groupName`,`host`),
  KEY `host` (`host`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_hostgroups_stats`
--

DROP TABLE IF EXISTS `grid_hostgroups_stats`;
CREATE TABLE `grid_hostgroups_stats` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `groupName` varchar(64) NOT NULL,
  `efficiency` double NOT NULL default '0',
  `avg_mem` double NOT NULL default '0',
  `max_mem` double NOT NULL default '0',
  `avg_swap` double NOT NULL default '0',
  `max_swap` double NOT NULL default '0',
  `total_cpu` bigint(20) unsigned NOT NULL default '0',
  `numRUN` int(10) unsigned NOT NULL default '0',
  `numJOBS` int(10) unsigned NOT NULL default '0',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`groupName`)
) ENGINE=InnoDB COMMENT='Tracks Host Group Statistics';

--
-- Table structure for table `grid_hostinfo`
--

DROP TABLE IF EXISTS `grid_hostinfo`;
CREATE TABLE `grid_hostinfo` (
  `host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `hostType` varchar(20) NOT NULL default '',
  `hostModel` varchar(40) NOT NULL default '0',
  `cpuFactor` varchar(10) default NULL,
  `maxCpus` varchar(10) default NULL,
  `maxMem` varchar(10) default NULL,
  `maxSwap` varchar(10) default NULL,
  `maxTmp` varchar(10) default NULL,
  `nDisks` varchar(10) NOT NULL default '0',
  `resources` varchar(255) NOT NULL default '',
  `windows` varchar(255) NOT NULL default '0',
  `isServer` char(1) NOT NULL default '',
  `licensed` char(1) NOT NULL default '',
  `rexPriority` int(10) unsigned NOT NULL default '0',
  `licFeaturesNeeded` int(10) unsigned NOT NULL default '0',
  `licClass` int(10) unsigned NOT NULL default '0',
  `cores` int(10) unsigned NOT NULL default '0',
  `first_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`host`,`clusterid`),
  KEY `isServer` (`isServer`),
  KEY `hostType` (`hostType`),
  KEY `hostModel` (`hostModel`),
  KEY `last_seen` (`last_seen`),
  KEY `clusterid_is_server` (`clusterid`,`isServer`),
  KEY `licFeaturesNeeded` (`licFeaturesNeeded`),
  KEY `clusterid_present` (`clusterid`,`present`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_hostresources`
--

DROP TABLE IF EXISTS `grid_hostresources`;
CREATE TABLE `grid_hostresources` (
  `host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `resource_name` varchar(50) NOT NULL default '',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`host`,`clusterid`,`resource_name`),
  KEY `clusterid` (`clusterid`),
  KEY `resource_name` (`resource_name`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_hosts`
--

DROP TABLE IF EXISTS `grid_hosts`;
CREATE TABLE `grid_hosts` (
  `host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `hStatus` int(10) unsigned NOT NULL default '0',
  `hCtrlMsg` varchar(255) NOT NULL default '',
  `status` varchar(20) default NULL,
  `prev_status` varchar(20) NOT NULL default '',
  `time_in_state` int(10) unsigned NOT NULL default '0',
  `cpuFactor` float NOT NULL default '0',
  `windows` varchar(255) default NULL,
  `userJobLimit` varchar(20) default NULL,
  `maxJobs` int(11) NOT NULL default '0',
  `numJobs` int(10) unsigned NOT NULL default '0',
  `numRun` int(10) unsigned NOT NULL default '0',
  `numSSUSP` int(10) unsigned NOT NULL default '0',
  `numUSUSP` int(10) unsigned NOT NULL default '0',
  `mig` int(10) unsigned NOT NULL default '0',
  `attr` int(10) unsigned NOT NULL default '0',
  `numRESERVE` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  `exceptional` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`host`,`clusterid`),
  KEY `clusterid` (`clusterid`),
  KEY `status` (`status`),
  KEY `prev_status` (`prev_status`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_hosts_jobtraffic`
--

DROP TABLE IF EXISTS `grid_hosts_jobtraffic`;
CREATE TABLE `grid_hosts_jobtraffic` (
  `host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `idle_slot_time` bigint(20) unsigned NOT NULL default '0',
  `jobs_done` bigint(20) unsigned NOT NULL default '0',
  `jobs_exited` bigint(20) unsigned NOT NULL default '0',
  `job_last_started` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_ended` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_suspended` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_exited` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`host`,`clusterid`),
  KEY `clusterid` (`clusterid`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_hosts_resources`
--

DROP TABLE IF EXISTS `grid_hosts_resources`;
CREATE TABLE `grid_hosts_resources` (
  `host` varchar(64) NOT NULL,
  `clusterid` int(10) unsigned NOT NULL,
  `resource_name` varchar(40) NOT NULL,
  `resType` tinyint(3) unsigned NOT NULL default '0',
  `flag` tinyint(3) unsigned NOT NULL default '0',
  `availValue` varchar(128) NOT NULL default '',
  `totalValue` varchar(128) NOT NULL default '',
  `reservedValue` varchar(128) NOT NULL default '',
  `value` varchar(128) NOT NULL default '',
  `present` tinyint(3) unsigned NOT NULL,
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  USING HASH (`host`,`clusterid`,`resource_name`,`resType`),
  KEY `value` (`value`),
  KEY `resource_name_host` (`resource_name`,`host`),
  KEY `clusterid` USING BTREE (`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_hosts_alarm`
--
DROP TABLE IF EXISTS `grid_hosts_alarm`;
CREATE TABLE `grid_hosts_alarm`
	(`type_id` bigint NOT NULL default '0' ,
	`name` varchar(100) NOT NULL default '',
	`hostname`  varchar(64) NOT NULL default '',
	`clusterid`  int(10) unsigned NOT NULL default '0',
	`type` int NOT NULL default '0',
	`message` varchar(1024) NOT NULL,
	`acknowledgement` char(3) NOT NULL default 'off',
	`alert_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`present` tinyint(3) unsigned NOT NULL default '1',
	PRIMARY KEY  (`type`,`type_id`,`clusterid`,`hostname`),
	KEY `hostname` (`hostname`),
	KEY`clusterid`(`clusterid`)) ENGINE=MEMORY;

--
-- Table structure for table `grid_host_threshold`
--
DROP TABLE IF EXISTS `grid_host_threshold`;
CREATE TABLE `grid_host_threshold`
(`id` bigint unsigned NOT NULL auto_increment,
`clusterid` int(10) unsigned NOT NULL default '0',
`hostname`  varchar(64) NOT NULL default '',
`resource_name` varchar(40) NOT NULL default '',
`loadSched` double NOT NULL default '0',
`loadStop` double NOT NULL default '0',
`busySched` int NOT NULL default '0',
`busyStop` int NOT NULL default '0',
`present` tinyint(3) unsigned NOT NULL default '1',
PRIMARY KEY (`clusterid`,`hostname`,`resource_name`),
KEY`id`(`id`),
KEY `hostname` (`hostname`),
KEY `resource_name` (`resource_name`)) ENGINE=MEMORY;

--
-- Table structure for table `grid_job_daily_stats`
--

DROP TABLE IF EXISTS `grid_job_daily_stats`;
CREATE TABLE `grid_job_daily_stats` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `user` varchar(60) NOT NULL default '',
  `stat` varchar(45) NOT NULL default '',
  `queue` varchar(45) NOT NULL default '',
  `from_host` varchar(64) NOT NULL default '',
  `exec_host` varchar(64) NOT NULL default '',
  `projectName` varchar(256) NOT NULL default '',
  `jobs_in_state` int(10) unsigned NOT NULL default '0',
  `jobs_wall_time` int(10) unsigned NOT NULL default '0',
  `jobs_stime` double NOT NULL,
  `jobs_utime` double NOT NULL,
  `slots_in_state` int(10) unsigned NOT NULL default '0',
  `avg_memory` double NOT NULL default '0',
  `max_memory` double NOT NULL default '0',
  `interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`date_recorded`),
  KEY `interval_start` (`interval_start`),
  KEY `interval_end` USING BTREE (`interval_end`),
  KEY `date_recorded` (`date_recorded`),
  KEY `user` (`user`),
  KEY `stat` (`stat`),
  KEY `queue` (`queue`),
  KEY `exec_host` (`exec_host`),
  KEY `projectName` (`projectName`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_job_interval_stats`
--

DROP TABLE IF EXISTS `grid_job_interval_stats`;
CREATE TABLE `grid_job_interval_stats` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `user` varchar(60) NOT NULL default '',
  `stat` varchar(45) NOT NULL default '',
  `queue` varchar(60) NOT NULL default '',
  `from_host` varchar(64) NOT NULL default '',
  `exec_host` varchar(64) NOT NULL default '',
  `projectName` varchar(256) NOT NULL default '',
  `jobs_reaching_state` int(10) unsigned NOT NULL default '0',
  `jobs_wall_time` int(10) unsigned NOT NULL default '0',
  `jobs_stime` double NOT NULL,
  `jobs_utime` double NOT NULL,
  `slots_in_state` int(10) unsigned NOT NULL default '0',
  `avg_memory` double NOT NULL default '0',
  `max_memory` double NOT NULL default '0',
  `interval_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `interval_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`date_recorded`),
  KEY `interval_start` (`interval_start`),
  KEY `interval_end` (`interval_end`),
  KEY `stat` (`stat`),
  KEY `queue` (`queue`),
  KEY `date_recorded` (`date_recorded`),
  KEY `clusterid_queue_stat` (`clusterid`,`queue`,`stat`),
  KEY `clusterid_date_recorded` (`clusterid`,`date_recorded`),
  KEY `clusterid_stat_interval_end` (`clusterid`,`stat`,`interval_end`,`user`),
  KEY `projectName` (`projectName`),
  KEY `user` (`user`)
) ENGINE=MyISAM;

--
-- Table structure for table `grid_jobs`
--

DROP TABLE IF EXISTS `grid_jobs`;
CREATE TABLE `grid_jobs` (
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `options` int(10) unsigned NOT NULL default '0',
  `options2` int(10) unsigned NOT NULL default '0',
  `options3` int(10) unsigned NOT NULL default '0',
  `user` varchar(60) NOT NULL default '',
  `stat` varchar(10) NOT NULL default '',
  `prev_stat` varchar(10) NOT NULL default '',
  `stat_changes` int(10) unsigned NOT NULL default '0',
  `flapping_logged` tinyint(3) unsigned NOT NULL default '0',
  `exitStatus` int(10) unsigned NOT NULL default '0',
  `pendReasons` varchar(512) NOT NULL default '',
  `queue` varchar(30) NOT NULL default '',
  `nice` varchar(4) NOT NULL default '',
  `from_host` varchar(64) NOT NULL default '',
  `exec_host` varchar(64) default NULL,
  `execUid` int(10) unsigned NOT NULL default '0',
  `loginShell` varchar(20) default NULL,
  `execHome` varchar(255) NOT NULL default '',
  `execCwd` varchar(255) NOT NULL default '',
  `cwd` varchar(255) NOT NULL default '',
  `exceptMask` int(10) NOT NULL default '0',
  `exitInfo` int(10) NOT NULL default '0',
  `postExecCmd` varchar(3000) NOT NULL default '',
  `app` varchar(40) NOT NULL default '',
  `execUsername` varchar(60) NOT NULL default '',
  `mailUser` varchar(512) default NULL,
  `jobname` varchar(3000) default NULL,
  `jobPriority` int(10) unsigned NOT NULL default '0',
  `jobPid` int(10) unsigned NOT NULL default '0',
  `userPriority` int(11) default '0',
  `projectName` varchar(256) NOT NULL default '',
  `parentGroup` varchar(128) NOT NULL default '',
  `sla` varchar(60) NOT NULL default '',
  `jobGroup` varchar(512) NOT NULL default '',
  `licenseProject` varchar(256) NOT NULL default '',
  `command` varchar(3000) default NULL,
  `inFile` varchar(255) default NULL,
  `outFile` varchar(255) default NULL,
  `errFile` varchar(255) default NULL,
  `preExecCmd` varchar(3000) default NULL,
  `res_requirements` varchar(512) default NULL,
  `dependCond` varchar(1024) default '',
  `mem_used` double default NULL,
  `swap_used` double NOT NULL default '0',
  `max_memory` double default '0',
  `max_swap` double default '0',
  `mem_requested` double default '0',
  `mem_requested_oper` varchar(8) default '',
  `mem_reserved` double default '0',
  `cpu_used` double NOT NULL default '0',
  `utime` double NOT NULL default '0',
  `stime` double NOT NULL default '0',
  `efficiency` decimal(9,5) NOT NULL default '0.00000',
  `effic_logged` tinyint(3) unsigned NOT NULL default '0',
  `numPIDS` int(10) unsigned NOT NULL default '0',
  `numPGIDS` int(10) unsigned NOT NULL default '0',
  `numThreads` int(10) unsigned NOT NULL default '0',
  `pid_alarm_logged` tinyint(3) unsigned NOT NULL default '0',
  `num_nodes` int(10) unsigned NOT NULL default '1',
  `num_cpus` int(10) unsigned NOT NULL default '1',
  `max_allocated_processes` int(10) unsigned NOT NULL default '0' COMMENT 'job level allocated slot',
  `maxNumProcessors` int(10) unsigned NOT NULL default '0',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `reserveTime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `predictedStartTime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `beginTime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `termTime` timestamp NOT NULL default '0000-00-00 00:00:00',
  `runtimeEstimation` int(10) unsigned default '0',
  `pend_time` int(10) unsigned NOT NULL default '0',
  `psusp_time` int(10) unsigned NOT NULL default '0',
  `run_time` int(10) unsigned NOT NULL default '0',
  `ususp_time` int(10) unsigned NOT NULL default '0',
  `ssusp_time` int(10) unsigned NOT NULL default '0',
  `unkwn_time` int(10) unsigned NOT NULL default '0',
  `prov_time`  int(10) unsigned NOT NULL default '0',
  `acJobWaitTime` int(10) unsigned NOT NULL default '0',
  `hostSpec` varchar(40) default NULL,
  `rlimit_max_cpu` int(10) unsigned NOT NULL default '0',
  `rlimit_max_wallt` int(10) unsigned NOT NULL default '0',
  `rlimit_max_swap` int(10) unsigned NOT NULL default '0',
  `rlimit_max_fsize` int(10) unsigned NOT NULL default '0',
  `rlimit_max_data` int(10) unsigned NOT NULL default '0',
  `rlimit_max_stack` int(10) unsigned NOT NULL default '0',
  `rlimit_max_core` int(10) unsigned NOT NULL default '0',
  `rlimit_max_rss` int(10) unsigned NOT NULL default '0',
  `job_start_logged` tinyint(3) unsigned NOT NULL default '0',
  `job_end_logged` tinyint(3) unsigned NOT NULL default '0',
  `job_scan_logged` tinyint(3) unsigned NOT NULL default '0',
  `userGroup` varchar(60) NOT NULL,
  `jobDescription` varchar(512) default '',
  `combinedResreq` varchar(512) default '',
  `effectiveResreq` varchar(512) default '',
  `chargedSAAP` varchar(256) default '',
  `ineligiblePendingTime` int(10) unsigned NOT NULL default '0',
  `pendState` int(10) NOT NULL default '-1',
  `effectivePendingTimeLimit` int(10) unsigned NOT NULL default '0',
  `effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL default '0',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`),
  KEY `submit_time` (`submit_time`),
  KEY `projectName` (`projectName`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`),
  KEY `job_start_logged` (`job_start_logged`),
  KEY `job_end_logged` (`job_end_logged`),
  KEY `job_scan_logged` (`job_scan_logged`),
  KEY `jobname` (`jobname`),
  KEY `queue_clusterid` (`queue`,`clusterid`),
  KEY `stat_clusterid_exitInfo` (`stat`,`clusterid`,`exitInfo`),
  KEY `user_clusterid` (`user`,`clusterid`),
  KEY `from_host_clusterid` (`from_host`,`clusterid`),
  KEY `exec_host_clusterid` (`exec_host`,`clusterid`),
  KEY `swap_used` (`swap_used`),
  KEY `cpu_used` (`cpu_used`),
  KEY `mem_used` (`mem_used`),
  KEY `num_nodes` (`num_nodes`),
  KEY `num_cpus` (`num_cpus`),
  KEY `pend_time` (`pend_time`),
  KEY `ineligiblePendingTime` (`ineligiblePendingTime`),
  KEY `effectivePendingTimeLimit` (`effectivePendingTimeLimit`),
  KEY `effectiveEligiblePendingTimeLimit` (`effectiveEligiblePendingTimeLimit`),
  KEY `psusp_time` (`psusp_time`),
  KEY `run_time` (`run_time`),
  KEY `ususp_time` (`ususp_time`),
  KEY `ssusp_time` (`ssusp_time`),
  KEY `unkwn_time` (`unkwn_time`),
  KEY `prov_time` (`prov_time`),
  KEY `nice` (`nice`),
  KEY `app` (`app`),
  KEY `prev_stat` (`prev_stat`),
  KEY `stat_changes` (`stat_changes`),
  KEY `flapping_logged` (`flapping_logged`),
  KEY `efficiency` (`efficiency`),
  KEY `effic_logged` (`effic_logged`),
  KEY `userGroup` (`userGroup`),
  KEY `indexid` (`indexid`),
  KEY `jobid` (`jobid`),
  KEY `pid_alarm_logged` (`pid_alarm_logged`),
  KEY `clusterid_end_logged` (`clusterid`,`job_end_logged`),
  KEY `clusterid_stat_end_logged` (`clusterid`,`stat`,`job_end_logged`),
  KEY `clusterid_stat_start_time` (`clusterid`,`stat`,`start_time`),
  KEY `clusterid_stat_last_updated` (`clusterid`,`stat`,`last_updated`),
  KEY `stat_last_updated` (`stat`, `last_updated`),
  KEY `last_updated` (`last_updated`),
  KEY `licenseProject` (`licenseProject`),
  KEY `jobGroup` (`jobGroup`(191)),
  KEY `sla` (`sla`),
  KEY `exitInfo` (`exitInfo`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_exec_hosts`
--

DROP TABLE IF EXISTS `grid_jobs_exec_hosts`;
CREATE TABLE `grid_jobs_exec_hosts` (
  `exec_host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`exec_host`,`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_jobs_finished`
--

DROP TABLE IF EXISTS `grid_jobs_finished`;
CREATE TABLE `grid_jobs_finished` LIKE `grid_jobs`;
ALTER TABLE `grid_jobs_finished` DROP INDEX `clusterid_end_logged`,
  DROP INDEX `clusterid_stat_end_logged`,
  DROP INDEX `clusterid_stat_start_time`,
  DROP INDEX `clusterid_stat_last_updated`,
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
  DROP INDEX `ususp_time`;

--
-- Table structure for table `grid_jobs_from_hosts`
--

DROP TABLE IF EXISTS `grid_jobs_from_hosts`;
CREATE TABLE `grid_jobs_from_hosts` (
  `from_host` varchar(64) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`from_host`,`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_jobs_idled`
--

DROP TABLE IF EXISTS `grid_jobs_idled`;
CREATE TABLE `grid_jobs_idled` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `jobid` int(10) unsigned NOT NULL,
  `indexid` varchar(45) NOT NULL,
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cumulative_cpu` int(10) unsigned NOT NULL,
  `notified` tinyint(3) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`submit_time`,`indexid`,`jobid`),
  KEY `present` (`present`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_jobhosts`
--

DROP TABLE IF EXISTS `grid_jobs_jobhosts`;
CREATE TABLE `grid_jobs_jobhosts` (
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `exec_host` varchar(64) NOT NULL default '',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `processes` int(11) NOT NULL default '0',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`exec_host`),
  KEY `exec_host` (`exec_host`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_jobhosts_finished`
--

DROP TABLE IF EXISTS `grid_jobs_jobhosts_finished`;
CREATE TABLE `grid_jobs_jobhosts_finished` (
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `exec_host` varchar(64) NOT NULL default '',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `processes` int(11) NOT NULL default '0',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`exec_host`),
  KEY `exec_host` (`exec_host`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_memvio`
--

DROP TABLE IF EXISTS `grid_jobs_memvio`;
CREATE TABLE `grid_jobs_memvio` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `jobid` int(10) unsigned NOT NULL,
  `indexid` varchar(45) NOT NULL,
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `type` int(10) NOT NULL default '0',
  `run_time` int(10) NOT NULL default '0',
  `rusage_memory` int(10) unsigned NOT NULL,
  `rusage_swap` int(10) unsigned NOT NULL,
  `max_memory` int(10) unsigned NOT NULL,
  `max_swap` int(10) unsigned NOT NULL,
  `notified` tinyint(3) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`submit_time`,`indexid`,`jobid`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_pendreason_maps`
--

DROP TABLE IF EXISTS `grid_jobs_pendreason_maps`;
CREATE TABLE `grid_jobs_pendreason_maps` (
  `issusp` tinyint(3) unsigned NOT NULL default '0',
  `reason_code` int(10) unsigned NOT NULL default '0',
  `sub_reason_code` varchar(40) NOT NULL default '',
  `reason` varchar(256) NOT NULL default '',
  PRIMARY KEY  (`issusp`,`reason_code`,`sub_reason_code`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_pendreasons`
--

DROP TABLE IF EXISTS `grid_jobs_pendreasons`;
CREATE TABLE `grid_jobs_pendreasons` (
  `clusterid` int(10) unsigned NOT NULL,
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `issusp` tinyint(3) unsigned NOT NULL default '0',
  `reason` int(10) unsigned NOT NULL default '0',
  `subreason` varchar(40) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `detail` varchar(128) NOT NULL default '',
  `ratio` float NOT NULL default '0',
  `start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`type`,`end_time`),
  KEY `clusterid_end_time_last_updated` (`clusterid`,`end_time`,`last_updated`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_pendreasons_finished`
--

DROP TABLE IF EXISTS `grid_jobs_pendreasons_finished`;
CREATE TABLE `grid_jobs_pendreasons_finished` (
  `clusterid` int(10) unsigned NOT NULL,
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `issusp` tinyint(3) unsigned NOT NULL default '0',
  `reason` int(10) unsigned NOT NULL default '0',
  `subreason` varchar(40) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `detail` varchar(128) NOT NULL default '',
  `ratio`  float NOT NULL default '0',
  `start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`type`,`end_time`),
  KEY `clusterid_end_time` (`clusterid`, `end_time`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_processes`
--

DROP TABLE IF EXISTS `grid_jobs_processes`;
CREATE TABLE `grid_jobs_processes` (
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
  `last_seen` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`clusterid`,`jobid`,`indexid`,`host`,`PID`),
  KEY `clusterid_host` (`clusterid`,`host`),
  KEY `pid` (`PID`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_queues`
--

DROP TABLE IF EXISTS `grid_jobs_queues`;
CREATE TABLE `grid_jobs_queues` (
  `queue` varchar(30) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`queue`,`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_jobs_reqhosts`
--

DROP TABLE IF EXISTS `grid_jobs_reqhosts`;
CREATE TABLE `grid_jobs_reqhosts` (
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `host` varchar(64) NOT NULL default '',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`),
  KEY `host` (`host`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_reqhosts_finished`
--

DROP TABLE IF EXISTS `grid_jobs_reqhosts_finished`;
CREATE TABLE `grid_jobs_reqhosts_finished` (
  `jobid` bigint(20) unsigned NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `host` varchar(64) NOT NULL default '',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`),
  KEY `host` (`host`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_runtime`
--

DROP TABLE IF EXISTS `grid_jobs_runtime`;
CREATE TABLE IF NOT EXISTS `grid_jobs_runtime` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `jobid` int(10) unsigned NOT NULL default '0',
  `indexid` varchar(45) NOT NULL default '0',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `rlimit_max_wallt` int(10) unsigned NOT NULL default '0',
  `runtimeEstimation` int(10) unsigned default '0',
  `run_time` int(10) unsigned NOT NULL default '0',
  `type` int(10) unsigned NOT NULL default '0',
  `notified` tinyint(3) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`submit_time`,`indexid`,`jobid`,`type`,`rlimit_max_wallt`,`runtimeEstimation`)
) ENGINE=InnoDB;

/*!40000 ALTER TABLE `grid_jobs_runtime` DISABLE KEYS */;
LOCK TABLES `grid_jobs_runtime` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `grid_jobs_runtime` ENABLE KEYS */;


--
-- Table structure for table `grid_jobs_rusage`
--

DROP TABLE IF EXISTS `grid_jobs_rusage`;
CREATE TABLE `grid_jobs_rusage` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `jobid` bigint(20) NOT NULL default '0',
  `indexid` int(10) unsigned NOT NULL default '0',
  `submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `utime` float NOT NULL default '0',
  `stime` float NOT NULL default '0',
  `mem` float NOT NULL default '0',
  `swap` float NOT NULL default '0',
  `mem_reserved` float NOT NULL DEFAULT '0',
  `npids` int(10) unsigned NOT NULL default '0',
  `npgids` int(10) unsigned NOT NULL default '0',
  `nthreads` int(10) unsigned NOT NULL default '0',
  `num_cpus` int(10) unsigned NOT NULL default '0' COMMENT 'job level allocated slot',
  `pids` varchar(1024) NOT NULL default '',
  `pgids` varchar(40) NOT NULL default '',
  PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`,`update_time`),
  KEY `update_time` (`update_time`),
  KEY `submit_time` (`submit_time`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_jobs_host_rusage`
--

DROP TABLE IF EXISTS `grid_jobs_host_rusage`;
CREATE TABLE `grid_jobs_host_rusage` (
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `jobid` bigint(20) NOT NULL DEFAULT '0',
  `indexid` int(10) unsigned NOT NULL DEFAULT '0',
  `submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `host` varchar(64) NOT NULL DEFAULT '',
  `utime` float NOT NULL DEFAULT '0',
  `stime` float NOT NULL DEFAULT '0',
  `mem` float NOT NULL DEFAULT '0',
  `swap` float NOT NULL DEFAULT '0',
  `processes` int(11)   NOT NULL default '0' COMMENT 'job host level allocated slot',
  PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`update_time`),
  KEY `update_time` (`update_time`),
  KEY `submit_time` (`submit_time`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `grid_jobs_stats`;
CREATE TABLE `grid_jobs_stats` (
  `stat` varchar(10) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`stat`,`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_jobs_users`
--

DROP TABLE IF EXISTS `grid_jobs_users`;
CREATE TABLE `grid_jobs_users` (
  `user` varchar(60) NOT NULL default '',
  `clusterid` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`user`,`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_license_projects`
--

DROP TABLE IF EXISTS `grid_license_projects`;
CREATE TABLE `grid_license_projects` (
  `clusterid` int(10) unsigned NOT NULL,
  `licenseProject` varchar(256) NOT NULL,
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
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`licenseProject`),
  KEY `present` (`present`)
) ENGINE=InnoDB COMMENT='Tracks License Project Information';

--
-- Table structure for table `grid_load`
--

DROP TABLE IF EXISTS `grid_load`;
CREATE TABLE `grid_load` (
  `host` varchar(64) NOT NULL default '',
  `clusterid` int(11) NOT NULL default '0',
  `status` varchar(20) NOT NULL default '',
  `prev_status` varchar(20) NOT NULL default '',
  `time_in_state` int(10) unsigned NOT NULL default '0',
  `istatus` int(10) unsigned NOT NULL default '0',
  `r15s` float NOT NULL default '0',
  `r1m` float NOT NULL default '0',
  `r15m` float NOT NULL default '0',
  `ut` float NOT NULL default '0',
  `pg` float NOT NULL default '0',
  `io` float NOT NULL default '0',
  `ls` float NOT NULL default '0',
  `it` float NOT NULL default '0',
  `tmp` float NOT NULL default '0',
  `swp` float NOT NULL default '0',
  `mem` float NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`host`,`clusterid`),
  KEY `clusterid` (`clusterid`),
  KEY `status` (`status`),
  KEY `prev_status` (`prev_status`),
  KEY `istatus` (`istatus`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_os_signals`
--

DROP TABLE IF EXISTS `grid_os_signals`;
CREATE TABLE `grid_os_signals` (
  `hostType_match` varchar(45) NOT NULL default '',
  `signal` int(10) unsigned NOT NULL default '0',
  `action` tinyint(3) unsigned NOT NULL default '0',
  `abreviation` varchar(20) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`hostType_match`,`signal`,`abreviation`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_params`
--

DROP TABLE IF EXISTS `grid_params`;
CREATE TABLE `grid_params` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `parameter` varchar(45) NOT NULL default '',
  `parameter_class` varchar(10) NOT NULL default '',
  `parameter_value` varchar(255) NOT NULL default '',
  `parameter_default` varchar(40) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `type` varchar(10) NOT NULL default '',
  PRIMARY KEY  (`clusterid`,`parameter`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_pendreasons_ignore`
--

DROP TABLE IF EXISTS `grid_pendreasons_ignore`;
CREATE TABLE `grid_pendreasons_ignore` (
  `user_id` mediumint(8) unsigned NOT NULL,
  `issusp` tinyint(3) unsigned NOT NULL default '0',
  `reason` int(10) unsigned NOT NULL,
  `subreason` varchar(40) NOT NULL default '',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`user_id`,`issusp`,`reason`,`subreason`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_pollers`
--

DROP TABLE IF EXISTS `grid_pollers`;
CREATE TABLE `grid_pollers` (
  `poller_id` int(10) unsigned NOT NULL auto_increment,
  `poller_name` varchar(45) NOT NULL default '',
  `poller_lbindir` varchar(255) NOT NULL default '',
  `poller_licserver_threads` int(11) NOT NULL default '5',
  `poller_location` varchar(255) NOT NULL default '',
  `poller_support_info` varchar(255) NOT NULL default '',
  `lsf_version` int(10) unsigned NOT NULL default '1017',
  `remote` varchar(20),
  `poller_max_insert_packet_size` varchar(255),
  PRIMARY KEY  (`poller_id`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_processes`
--

DROP TABLE IF EXISTS `grid_processes`;
CREATE TABLE `grid_processes` (
  `pid` int(10) unsigned NOT NULL default '0',
  `taskname` varchar(20) NOT NULL default '0',
  `taskid` int(10) unsigned NOT NULL default '0',
  `heartbeat` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`taskname`,`taskid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_projects`
--

DROP TABLE IF EXISTS `grid_projects`;
CREATE TABLE `grid_projects` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `projectName` varchar(256) NOT NULL,
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
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`projectName`),
  KEY `present` (`present`)
) ENGINE=InnoDB COMMENT='Tracks Project Information';

--
-- Table structure for table `grid_queues`
--

DROP TABLE IF EXISTS `grid_queues`;
CREATE TABLE `grid_queues` (
  `queuename` varchar(50) NOT NULL default '',
  `clusterid` int(11) NOT NULL default '0',
  `description` varchar(255) default NULL,
  `priority` int(10) unsigned NOT NULL default '0',
  `nice` int(10) unsigned NOT NULL default '0',
  `status` varchar(20) NOT NULL default '',
  `reason` varchar(20) NOT NULL default '',
  `numslots` int(10) unsigned NOT NULL default '0',
  `maxjobs` varchar(10) NOT NULL default '',
  `userJobLimit` varchar(20) NOT NULL default '',
  `procJobLimit` varchar(5) NOT NULL default '',
  `hostJobLimit` varchar(5) NOT NULL default '',
  `nojobs` int(10) unsigned NOT NULL default '0',
  `pendjobs` int(10) unsigned NOT NULL default '0',
  `runjobs` int(10) unsigned NOT NULL default '0',
  `suspjobs` int(10) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  `avg_pend_time` int(10) unsigned NOT NULL default '0',
  `max_pend_time` int(10) unsigned NOT NULL default '0',
  `avg_psusp_time` int(10) unsigned NOT NULL default '0',
  `max_psusp_time` int(10) unsigned NOT NULL default '0',
  `avg_run_time` int(10) unsigned NOT NULL default '0',
  `max_run_time` int(10) unsigned NOT NULL default '0',
  `avg_ususp_time` int(10) unsigned NOT NULL default '0',
  `max_ususp_time` int(10) unsigned NOT NULL default '0',
  `avg_ssusp_time` int(10) unsigned NOT NULL default '0',
  `max_ssusp_time` int(10) unsigned NOT NULL default '0',
  `avg_unkwn_time` int(10) unsigned NOT NULL default '0',
  `max_unkwn_time` int(10) unsigned NOT NULL default '0',
  `avg_disp_time` int(10) unsigned NOT NULL default '0',
  `max_disp_time` int(10) unsigned NOT NULL default '0',
  `hourly_started_jobs` double NOT NULL default '0',
  `hourly_done_jobs` double NOT NULL default '0',
  `hourly_exit_jobs` double NOT NULL default '0',
  `daily_started_jobs` double NOT NULL default '0',
  `daily_done_jobs` double NOT NULL default '0',
  `daily_exit_jobs` double NOT NULL default '0',
  `efficiency` double NOT NULL default '0',
  `avg_mem` double NOT NULL default '0',
  `max_mem` double NOT NULL default '0',
  `avg_swap` double NOT NULL default '0',
  `max_swap` double NOT NULL default '0',
  `total_cpu` double NOT NULL default '0',
  `dedicatedSlots` int(11) NOT NULL default '0',
  `sharedSlots` int(11) NOT NULL default '0',
  `openDedicatedSlots` int(11) NOT NULL default '0',
  `openSharedSlots` int(11) NOT NULL default '0',
  `windows` varchar(255) NOT NULL,
  `windowsD` varchar(255) NOT NULL,
  `hostSpec` varchar(64) NOT NULL,
  `qAttrib` int(11) NOT NULL default '0',
  `qStatus` int(10) unsigned NOT NULL,
  `userShares` varchar(255) NOT NULL,
  `defaultHostSpec` varchar(64) NOT NULL,
  `procLimit` varchar(5) NOT NULL default '',
  `admins` varchar(255) NOT NULL,
  `preCmd` varchar(255) NOT NULL,
  `postCmd` varchar(255) NOT NULL,
  `requeueEValues` varchar(64) NOT NULL,
  `resReq` varchar(255) NOT NULL,
  `slotHoldTime` int(10) unsigned NOT NULL,
  `sndJobsTo` varchar(255) NOT NULL,
  `rcvJobsFrom` varchar(255) NOT NULL,
  `resumeCond` varchar(255) NOT NULL,
  `stopCond` varchar(255) NOT NULL,
  `jobStarter` varchar(255) NOT NULL,
  `suspendActCmd` varchar(255) NOT NULL,
  `resumeActCmd` varchar(255) NOT NULL,
  `terminateActCmd` varchar(255) NOT NULL,
  `preemption` varchar(255) NOT NULL,
  `maxRschedTime` int(10) unsigned NOT NULL,
  `maxJobRequeue` int(10) unsigned NOT NULL,
  `chkpntDir` varchar(255) NOT NULL,
  `chkpntPeriod` int(11) NOT NULL default '0',
  `imptJobBklg` int(11) NOT NULL default '0',
  `chunkJobSize` int(10) unsigned NOT NULL,
  `minProcLimit` int(11) NOT NULL default '0',
  `defProcLimit` int(11) NOT NULL default '0',
  `fairshareQueues` varchar(255) NOT NULL,
  `defExtSched` varchar(255) NOT NULL,
  `mandExtSched` varchar(255) NOT NULL,
  `slotShare` int(11) NOT NULL default '0',
  `slotPool` varchar(255) NOT NULL,
  `underRCond` int(10) unsigned NOT NULL,
  `overRCond` int(10) unsigned NOT NULL,
  `idleCond` double NOT NULL,
  `underRJobs` int(10) unsigned NOT NULL,
  `overRJobs` int(10) unsigned NOT NULL,
  `idleJobs` int(10) unsigned NOT NULL,
  `warningTimePeriod` int(11) NOT NULL default '0',
  `warningAction` varchar(255) NOT NULL,
  `qCtrlMsg` varchar(255) NOT NULL,
  `rlimit_max_cpu` int(10) unsigned NOT NULL default '0',
  `rlimit_max_wallt` int(10) unsigned NOT NULL default '0',
  `rlimit_max_swap` int(10) unsigned NOT NULL default '0',
  `rlimit_max_fsize` int(10) unsigned NOT NULL default '0',
  `rlimit_max_data` int(10) unsigned NOT NULL default '0',
  `rlimit_max_stack` int(10) unsigned NOT NULL default '0',
  `rlimit_max_core` int(10) unsigned NOT NULL default '0',
  `rlimit_max_rss` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`queuename`,`clusterid`),
  KEY `status` (`status`),
  KEY `reason` (`reason`),
  KEY `nice` (`nice`),
  KEY `clusterid` (`clusterid`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_queues_hosts`
--

DROP TABLE IF EXISTS `grid_queues_hosts`;
CREATE TABLE `grid_queues_hosts` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `queue` varchar(45) NOT NULL default '',
  `host` varchar(64) NOT NULL default '',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`queue`,`host`),
  KEY `host` (`host`),
  KEY `queue` (`queue`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_queues_shares`
--

DROP TABLE IF EXISTS `grid_queues_shares`;
CREATE TABLE `grid_queues_shares` (
  `clusterid` int(10) unsigned NOT NULL,
  `queue` varchar(50) NOT NULL,
  `user_or_group` varchar(60) NOT NULL,
  `shareAcctPath` varchar(191) NOT NULL default '',
  `shares` int(10) unsigned NOT NULL,
  `priority` double NOT NULL,
  `started` int(10) unsigned NOT NULL,
  `reserved` int(10) unsigned NOT NULL,
  `cpu_time` double NOT NULL,
  `run_time` bigint unsigned NOT NULL,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  USING HASH (`clusterid`,`queue`,`user_or_group`, `shareAcctPath`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_queues_stats`
--

DROP TABLE IF EXISTS `grid_queues_stats`;
CREATE TABLE `grid_queues_stats` (
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
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`queue`),
  KEY `present` (`present`)
) ENGINE=InnoDB COMMENT='Tracks Queue Statistical Information';

--
-- Table structure for table `grid_queues_thresholds`
--

DROP TABLE IF EXISTS `grid_queues_thresholds`;
CREATE TABLE `grid_queues_thresholds` (
  `clusterid` int(10) unsigned NOT NULL,
  `queue` varchar(45) NOT NULL,
  `loadSched` double NOT NULL,
  `loadStop` double NOT NULL,
  PRIMARY KEY  (`clusterid`,`queue`)
) ENGINE=InnoDB COMMENT='Dermines Queue Scheduling Load Thresholds';

--
-- Table structure for table `grid_queues_users`
--

DROP TABLE IF EXISTS `grid_queues_users`;
CREATE TABLE `grid_queues_users` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `queue` varchar(45) NOT NULL default '',
  `user` varchar(60) NOT NULL default '',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`queue`,`user`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_queues_users_stats`
--

DROP TABLE IF EXISTS `grid_queues_users_stats`;
CREATE TABLE `grid_queues_users_stats` (
  `clusterid` int(10) unsigned NOT NULL,
  `queue` varchar(45) NOT NULL,
  `user_or_group` varchar(60) NOT NULL,
  `nojobs` int(10) unsigned NOT NULL,
  `pendjobs` int(10) unsigned NOT NULL,
  `runjobs` int(10) unsigned NOT NULL,
  `suspjobs` int(10) unsigned NOT NULL,
  `efficiency` double NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`queue`,`user_or_group`),
  KEY `clusterid_user_or_group` (`clusterid`,`user_or_group`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_resources`
--

DROP TABLE IF EXISTS `grid_resources`;
CREATE TABLE `grid_resources` (
  `resource_name` varchar(20) NOT NULL,
  `value` varchar(128) NOT NULL,
  `clusterid` int(10) unsigned NOT NULL,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  USING HASH (`resource_name`,`value`,`clusterid`),
  KEY `clusterid` (`clusterid`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_sharedresources`
--

DROP TABLE IF EXISTS `grid_sharedresources`;
CREATE TABLE `grid_sharedresources` (
  `clusterid` int(10) unsigned NOT NULL,
  `resource_name` varchar(40) NOT NULL,
  `description` varchar(128) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY USING HASH (`clusterid`, `resource_name`),
  KEY `resource_name` (`resource_name`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_settings`
--

DROP TABLE IF EXISTS `grid_settings`;
CREATE TABLE `grid_settings` (
  `user_id` smallint(8) unsigned NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` varchar(1024) NOT NULL default '',
  PRIMARY KEY  (`user_id`,`name`)
) ENGINE=MyISAM;

--
-- Table structure for table `grid_summary`
--

DROP TABLE IF EXISTS `grid_summary`;
CREATE TABLE `grid_summary` (
  `clusterid` int(10) unsigned NOT NULL,
  `clustername` varchar(128) NOT NULL default '',
  `host` varchar(64) NOT NULL default '',
  `summary_status` varchar(20) NOT NULL default '',
  `load_status` varchar(20) NOT NULL default '',
  `r15s` float NOT NULL default '0',
  `r1m` float NOT NULL default '0',
  `r15m` float NOT NULL default '0',
  `ut` float NOT NULL default '0',
  `pg` float NOT NULL default '0',
  `io` float NOT NULL default '0',
  `ls` float NOT NULL default '0',
  `it` float NOT NULL default '0',
  `tmp` float NOT NULL default '0',
  `swp` float NOT NULL default '0',
  `mem` float NOT NULL default '0',
  `hStatus` int(10) unsigned NOT NULL default '0',
  `hCtrlMsg` varchar(255) NOT NULL default '',
  `bhost_status` varchar(20) default NULL,
  `cpuFactor` float NOT NULL default '0',
  `windows` varchar(255) default NULL,
  `userJobLimit` varchar(20) default NULL,
  `maxJobs` int(10) unsigned NOT NULL default '0',
  `numJobs` int(10) unsigned NOT NULL default '0',
  `numRun` int(10) unsigned NOT NULL default '0',
  `numSSUSP` int(10) unsigned NOT NULL default '0',
  `numUSUSP` int(10) unsigned NOT NULL default '0',
  `numRESERVE` int(10) unsigned NOT NULL default '0',
  `hostType` varchar(20) NOT NULL default '',
  `hostModel` varchar(40) NOT NULL default '0',
  `maxCpus` varchar(10) default NULL,
  `maxMem` varchar(10) default NULL,
  `maxSwap` varchar(10) default NULL,
  `maxTmp` varchar(10) default NULL,
  `nDisks` varchar(10) NOT NULL default '0',
  `isServer` char(1) NOT NULL default '',
  `licensed` char(1) NOT NULL default '',
  `rexPriority` int(10) unsigned NOT NULL default '0',
  `licFeaturesNeeded` int(10) unsigned NOT NULL default '0',
  `first_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_started` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_ended` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_suspended` timestamp NOT NULL default '0000-00-00 00:00:00',
  `job_last_exited` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
  `cacti_hostid` int(10) unsigned default NULL,
  `monitor` char(3) default NULL,
  `disabled` char(2) default NULL,
  `cacti_status` tinyint(2) default NULL,
  `status_rec_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `status_fail_date` timestamp NOT NULL default '0000-00-00 00:00:00',
  `min_time` decimal(10,5) default '9.99999',
  `max_time` decimal(10,5) default '0.00000',
  `cur_time` decimal(10,5) default '0.00000',
  `avg_time` decimal(10,5) default '0.00000',
  `availability` decimal(10,5) default NULL,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`host`),
  KEY `load_status` (`load_status`),
  KEY `cacti_status` (`cacti_status`),
  KEY `summary_status` (`summary_status`),
  KEY `bhost_status` (`bhost_status`),
  KEY `clusterid` (`clustername`),
  KEY `host` (`host`),
  KEY `monitor` (`monitor`),
  KEY `disabled` (`disabled`)
) ENGINE=MEMORY;

--
-- Table structure for table `grid_summary_timeinstate`
--

DROP TABLE IF EXISTS `grid_summary_timeinstate`;
CREATE TABLE `grid_summary_timeinstate` (
  `clusterid` int(10) unsigned NOT NULL,
  `host` varchar(64) NOT NULL,
  `unavail` bigint(20) unsigned NOT NULL,
  `busyclose` bigint(20) unsigned NOT NULL,
  `idleclose` bigint(20) unsigned NOT NULL,
  `lowres` bigint(20) unsigned NOT NULL,
  `busy` bigint(20) unsigned NOT NULL,
  `idlewjobs` bigint(20) unsigned NOT NULL,
  `idle` bigint(20) unsigned NOT NULL,
  `starved` bigint(20) unsigned NOT NULL,
  `admindown` bigint(20) unsigned NOT NULL,
  `blackhole` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`host`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_table_partitions`
--

DROP TABLE IF EXISTS `grid_table_partitions`;
CREATE TABLE `grid_table_partitions` (
  `partition` varchar(5) NOT NULL,
  `table_name` varchar(45) NOT NULL,
  `min_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `max_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`partition`,`table_name`),
  KEY `max_time` (`max_time`),
  KEY `min_time` (`min_time`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_user_group_members`
--

DROP TABLE IF EXISTS `grid_user_group_members`;
CREATE TABLE `grid_user_group_members` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `groupname` varchar(45) NOT NULL default '0',
  `username` varchar(60) NOT NULL default '0',
  `shares` int(10) unsigned NOT NULL default '1',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`groupname`,`username`),
  KEY `groupname` (`groupname`),
  KEY `username` (`username`)
) ENGINE=InnoDB;

--
-- Table structure for table `grid_user_group_stats`
--

DROP TABLE IF EXISTS `grid_user_group_stats`;
CREATE TABLE `grid_user_group_stats` (
  `clusterid` int(10) unsigned NOT NULL,
  `userGroup` varchar(60) NOT NULL,
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
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`userGroup`),
  KEY `present` (`present`)
) ENGINE=InnoDB COMMENT='Tracks userGroup Stats';

--
-- Table structure for table `grid_users_or_groups`
--

DROP TABLE IF EXISTS `grid_users_or_groups`;
CREATE TABLE `grid_users_or_groups` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `user_or_group` varchar(60) NOT NULL default '',
  `type` char(1) NOT NULL default '',
  `procJobLimit` double NOT NULL default '0',
  `maxJobs` int(10) unsigned NOT NULL default '0',
  `numStartJobs` int(10) unsigned NOT NULL default '0',
  `numJobs` int(10) unsigned NOT NULL default '0',
  `numPEND` int(10) unsigned NOT NULL default '0',
  `numRUN` int(10) unsigned NOT NULL default '0',
  `numSSUSP` int(10) unsigned NOT NULL default '0',
  `numUSUSP` int(10) unsigned NOT NULL default '0',
  `numRESERVE` int(10) unsigned NOT NULL default '0',
  `maxPendJobs` int(10) unsigned NOT NULL default '0',
  `efficiency` double NOT NULL,
  `first_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`user_or_group`),
  KEY `type` (`type`)
) ENGINE=InnoDB;

--
-- Table structure for ELIM
--

DROP TABLE IF EXISTS `grid_elim_templates`;
CREATE TABLE grid_elim_templates (
  id mediumint(8) unsigned NOT NULL auto_increment,
  hash char(32) NOT NULL default '',
  name char(255) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY name (name(191))
) ENGINE=InnoDB COMMENT='Contains each ELIM graph template name.';

DROP TABLE IF EXISTS `grid_elim_templates_graph`;
CREATE TABLE grid_elim_templates_graph (
  id mediumint(8) unsigned NOT NULL auto_increment,
  local_graph_template_graph_id mediumint(8) unsigned NOT NULL default '0',
  local_graph_id mediumint(8) unsigned NOT NULL default '0',
  graph_template_id mediumint(8) unsigned NOT NULL default '0',
  t_image_format_id char(2) default '0',
  image_format_id tinyint(1) NOT NULL default '0',
  t_title char(2) default '0',
  title varchar(255) NOT NULL default '',
  title_cache varchar(255) NOT NULL default '',
  t_height char(2) default '0',
  height mediumint(8) NOT NULL default '0',
  t_width char(2) default '0',
  width mediumint(8) NOT NULL default '0',
  t_upper_limit char(2) default '0',
  upper_limit varchar(20) NOT NULL default '0',
  t_lower_limit char(2) default '0',
  lower_limit varchar(20) NOT NULL default '0',
  t_vertical_label char(2) default '0',
  vertical_label varchar(200) default NULL,
  t_slope_mode char(2) default '0',
  slope_mode char(2) default 'on',
  t_auto_scale char(2) default '0',
  auto_scale char(2) default NULL,
  t_auto_scale_opts char(2) default '0',
  auto_scale_opts tinyint(1) NOT NULL default '0',
  t_auto_scale_log char(2) default '0',
  auto_scale_log char(2) default NULL,
  t_scale_log_units char(2) default '0',
  scale_log_units char(2) default NULL,
  t_auto_scale_rigid char(2) default '0',
  auto_scale_rigid char(2) default NULL,
  t_auto_padding char(2) default '0',
  auto_padding char(2) default NULL,
  t_base_value char(2) default '0',
  base_value mediumint(8) NOT NULL default '0',
  t_grouping char(2) default '0',
  grouping char(2) NOT NULL default '',
  t_export char(2) default '0',
  export char(2) default NULL,
  t_unit_value char(2) default '0',
  unit_value varchar(20) default NULL,
  t_unit_exponent_value char(2) default '0',
  unit_exponent_value varchar(5) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY local_graph_id (local_graph_id),
  KEY graph_template_id (graph_template_id),
  KEY title_cache (title_cache(191))
) ENGINE=InnoDB COMMENT='Stores the actual ELIM graph data.';


DROP TABLE IF EXISTS `grid_elim_templates_item`;
CREATE TABLE grid_elim_templates_item (
  id int(12) unsigned NOT NULL auto_increment,
  hash varchar(32) NOT NULL default '',
  local_graph_template_item_id int(12) unsigned NOT NULL default '0',
  local_graph_id mediumint(8) unsigned NOT NULL default '0',
  graph_template_id mediumint(8) unsigned NOT NULL default '0',
  task_item_id mediumint(8) unsigned NOT NULL default '0',
  color_id mediumint(8) unsigned NOT NULL default '0',
  alpha char(2) default 'FF',
  graph_type_id tinyint(3) NOT NULL default '0',
  cdef_id mediumint(8) unsigned NOT NULL default '0',
  consolidation_function_id tinyint(2) NOT NULL default '0',
  text_format varchar(255) default NULL,
  value varchar(255) default NULL,
  hard_return char(2) default NULL,
  gprint_id mediumint(8) unsigned NOT NULL default '0',
  sequence mediumint(8) unsigned NOT NULL default '0',
  resource_name varchar(40) default '',
  resource_option tinyint(3) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY graph_template_id (graph_template_id),
  KEY local_graph_id (local_graph_id),
  KEY task_item_id (task_item_id)
) ENGINE=InnoDB COMMENT='Stores the actual ELIM graph item data.';

DROP TABLE IF EXISTS `grid_elim_template_instances`;
CREATE TABLE grid_elim_template_instances (
  id mediumint(8) unsigned NOT NULL auto_increment,
  name char(255) NOT NULL default '',
  grid_elim_template_id mediumint(8) unsigned NOT NULL default '0',
  clusterid int(10) unsigned NOT NULL default '0',
  hosttype_option tinyint(3) unsigned  NOT NULL default '0',
  hosttype_value varchar(40) default NULL,
  data_source_profile_id mediumint(8) unsigned NOT NULL default '1',
  PRIMARY KEY  (id),
  KEY grid_elim_template_id (grid_elim_template_id)
) ENGINE=InnoDB COMMENT='Stores the instances of  ELIM graph template.';

DROP TABLE IF EXISTS `grid_elim_instance_graphs`;
CREATE TABLE grid_elim_instance_graphs (
  grid_elim_template_instance_id mediumint(8) unsigned NOT NULL,
  local_graph_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (grid_elim_template_instance_id,local_graph_id)
) ENGINE=InnoDB COMMENT='Stores the Map of ELIM template instance to the cacti graph local.';


DROP TABLE IF EXISTS `grid_elim_templates_graph_map`;
CREATE TABLE grid_elim_templates_graph_map (
  local_graph_id mediumint(8) unsigned NOT NULL,
  graph_templates_graph_id mediumint(8) unsigned NOT NULL,
  grid_elim_template_id mediumint(8) unsigned NOT NULL,
  grid_elim_templates_graph_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (local_graph_id,graph_templates_graph_id)
) ENGINE=InnoDB COMMENT='Stores the Map of ELIM grid_elim_templates_graph to cacti graph_templates_graph.';

DROP TABLE IF EXISTS `grid_elim_templates_item_map`;
CREATE TABLE grid_elim_templates_item_map (
  local_graph_id mediumint(8) unsigned NOT NULL,
  graph_templates_item_id int(12) unsigned NOT NULL,
  grid_elim_template_id mediumint(8) unsigned NOT NULL,
  grid_elim_templates_item_id int(12) unsigned NOT NULL,
  PRIMARY KEY  (local_graph_id,graph_templates_item_id)
) ENGINE=InnoDB COMMENT='Stores the Map of ELIM grid_elim_templates_item to cacti graph_templates_item.';

DROP TABLE IF EXISTS `grid_guarantee_pool`;
CREATE TABLE  `grid_guarantee_pool` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `name` varchar(60) NOT NULL default '',
  `poolType` varchar(32) NOT NULL default '',
  `rsrcName` varchar(128) default NULL,
  `status` varchar(32) NOT NULL,
  `res_select` varchar(128) NOT NULL,
  `slots_per_host` int(10) unsigned NOT NULL default '0',
  `policies` tinyint(3) unsigned NOT NULL default '0',
  `loan_duration` int(10) unsigned NOT NULL default '0',
  `retain` int(10) unsigned NOT NULL default '0',
  `total` int(10)  NOT NULL default '0',
  `free` int(10)  NOT NULL default '0',
  `guar_config` int(10)  NOT NULL default '0',
  `guar_used` int(10)  NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`name`)
) ENGINE=MEMORY COMMENT='Stores Configuration of Guarantee Resource Pools';
-- Types are: 1 = Slots, 2 == Hosts, 3 == Package, 4 == Resources

DROP TABLE IF EXISTS `grid_guarantee_pool_distribution`;
CREATE TABLE  `grid_guarantee_pool_distribution` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `consumer` varchar(60) NOT NULL,
  `alloc` int(10) unsigned NOT NULL,
  `alloc_type` int(10) unsigned NOT NULL,
  `guarantee_config` int(10) unsigned NOT NULL,
  `guarantee_used` int(10) unsigned NOT NULL,
  `total_used` int(10) unsigned NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`,`consumer`)
) ENGINE=MEMORY COMMENT='Stores Distribution Consumer Information for Guarantee Pool';

DROP TABLE IF EXISTS `grid_guarantee_pool_hosts`;
CREATE TABLE  `grid_guarantee_pool_hosts` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `host` varchar(64) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`,`host`),
  KEY `host` (`host`)
) ENGINE=MEMORY COMMENT='Stores Normalized Host Membership for Guarantee Pool';

DROP TABLE IF EXISTS `grid_guarantee_pool_loan_queues`;
CREATE TABLE  `grid_guarantee_pool_loan_queues` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `queue` varchar(60) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`,`queue`),
  KEY ` queue` (`queue`)
) ENGINE=MEMORY COMMENT='Stores Loan Policies for Guarantee Pool';

DROP TABLE IF EXISTS `grid_service_class`;
CREATE TABLE  `grid_service_class` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` varchar(255) NOT NULL,
  `consumer` varchar(255) NOT NULL,
  `priority` int(10) unsigned NOT NULL,
  `control_action` varchar(128) NOT NULL,
  `auto_attach` tinyint(3) unsigned NOT NULL,
  `ego_res_req` varchar(255) NOT NULL,
  `max_host_idle_time` int(10) unsigned NOT NULL,
  `throughput` double NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`)
) ENGINE=MEMORY COMMENT='Stores Service Class Definitions';

DROP TABLE IF EXISTS `grid_service_class_goals`;
CREATE TABLE  `grid_service_class_goals` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `goal_seq` int(10) default 0,
  `goalType` varchar(20) default NULL,
  `goal_window` varchar(1024) default NULL,
  `status` varchar(64) default NULL,
  `min_config` int(10) unsigned default NULL,
  `goal_config` int(10) unsigned default NULL,
  `actual` int(10) unsigned default NULL,
  `optimum` int(10) unsigned default NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`, `goal_seq`)
) ENGINE=MEMORY COMMENT='Stores Service Class Goals';

DROP TABLE IF EXISTS `grid_service_class_access_control`;
CREATE TABLE  `grid_service_class_access_control` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `acl_type` int(10) unsigned NOT NULL,
  `acl_member` varchar(60) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`,`acl_type`, `acl_member`),
  KEY `acl_member` (`acl_member`)
) ENGINE=MEMORY COMMENT='Stores Access Control Information for Service Class';

-- Types are: 1 == Queue, 2 == Users, 3 == Fairshare Groups, 4== Apps, 5 == Projects, 6 == License Projects

DROP TABLE IF EXISTS `grid_service_class_groups`;
CREATE TABLE  `grid_service_class_groups` (
  `clusterid` int(10) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `user_or_group` varchar(60) NOT NULL,
  `present` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`clusterid`,`name`,`user_or_group`)
) ENGINE=MEMORY COMMENT='User or User Groups Permitted to Use Service Class';

--
-- Table structure for Perfmon tables
--

DROP TABLE IF EXISTS `grid_clusters_perfmon_mbatchd_metrics`;
CREATE TABLE `grid_clusters_perfmon_mbatchd_metrics` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `metric` varchar(40) NOT NULL default '',
  `current` int(10) unsigned NOT NULL default '0',
  `max` int(10) unsigned NOT NULL default '0',
  `min` int(10) unsigned NOT NULL default '0',
  `avg` double unsigned NOT NULL default '0',
  `total` bigint(20) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`metric`)
) ENGINE=MEMORY COMMENT='Contains Perfmon Metrics';

--
-- Table structure for Perfmon tables
--

DROP TABLE IF EXISTS `grid_clusters_perfmon_scheduler_metrics`;
CREATE TABLE `grid_clusters_perfmon_scheduler_metrics` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `metric` varchar(40) NOT NULL default '',
  `current` int(10) unsigned NOT NULL default '0',
  `max` int(10) unsigned NOT NULL default '0',
  `min` int(10) unsigned NOT NULL default '0',
  `avg` double unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`metric`)
) ENGINE=MEMORY COMMENT='Contains Perfmon Metrics';

--
-- Table structure for Perfmon tables
--

DROP TABLE IF EXISTS `grid_clusters_perfmon_usage_metrics`;
CREATE TABLE `grid_clusters_perfmon_usage_metrics` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `metric` varchar(40) NOT NULL default '',
  `used` bigint(20) unsigned NOT NULL default '0',
  `free` bigint(20) unsigned NOT NULL default '0',
  `total` bigint(20) unsigned NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`,`metric`)
) ENGINE=MEMORY COMMENT='Contains Perfmon Metrics';

--
-- Table structure for Perfmon tables
--

DROP TABLE IF EXISTS `grid_clusters_perfmon_status`;
CREATE TABLE `grid_clusters_perfmon_status` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `clients` int(10) unsigned NOT NULL default '0',
  `clients_peak` int(10) unsigned NOT NULL default '0',
  `servers` int(10) unsigned NOT NULL default '0',
  `servers_peak` int(10) unsigned NOT NULL default '0',
  `cpus` int(10) unsigned NOT NULL default '0',
  `cpus_peak` int(10) unsigned NOT NULL default '0',
  `cores` int(10) unsigned NOT NULL default '0',
  `cores_peak` int(10) unsigned NOT NULL default '0',
  `slots` int(10) unsigned NOT NULL default '0',
  `slots_peak` int(10) unsigned NOT NULL default '0',
  `serv_all` int(10) unsigned NOT NULL default '0',
  `serv_ok` int(10) unsigned NOT NULL default '0',
  `serv_closed` int(10) unsigned NOT NULL default '0',
  `serv_unreachable` int(10) unsigned NOT NULL default '0',
  `serv_unavail` int(10) unsigned NOT NULL default '0',
  `dc_servers` int(10) unsigned NOT NULL default '0',
  `dc_servers_peak` int(10) unsigned NOT NULL default '0',
  `dc_cores` int(10) unsigned NOT NULL default '0',
  `dc_cores_peak` int(10) unsigned NOT NULL default '0',
  `dc_vm_containers` int(10) unsigned NOT NULL default '0',
  `dc_vm_containers_peak` int(10) unsigned NOT NULL default '0',
  `num_jobs` int(10) unsigned NOT NULL default '0',
  `num_run` int(10) unsigned NOT NULL default '0',
  `num_susp` int(10) unsigned NOT NULL default '0',
  `num_pend` int(10) unsigned NOT NULL default '0',
  `num_finished` int(10) unsigned NOT NULL default '0',
  `num_users` int(10) unsigned NOT NULL default '0',
  `num_active_users` int(10) unsigned NOT NULL default '0',
  `num_groups` int(10) unsigned NOT NULL default '0',
  `last_mbatchd_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `active_mbd_pid` int(10) unsigned NOT NULL default '0',
  `last_mbatchd_reconfig` timestamp NOT NULL default '0000-00-00 00:00:00',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`)
) ENGINE=MEMORY COMMENT='Contains badmin showstatus information';

--
-- Table structure for Perfmon tables
--

DROP TABLE IF EXISTS `grid_clusters_perfmon_summary`;
CREATE TABLE `grid_clusters_perfmon_summary` (
  `clusterid` int(10) unsigned NOT NULL default '0',
  `last_jobid` int(10) unsigned NOT NULL default '0',
  `start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_run` timestamp NOT NULL default '0000-00-00 00:00:00',
  `poller_interval` int(10) unsigned NOT NULL default '0',
  `pjob_submitTime` double NOT NULL default '0',
  `pjob_seenTime` double NOT NULL default '0',
  `pjob_runTime` int(10) unsigned NOT NULL default '0',
  `pjob_doneTime` double NOT NULL default '0',
  `pjob_seenDoneTime` double NOT NULL default '0',
  `pjob_startTime` double NOT NULL default '0',
  `present` tinyint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`clusterid`)
) ENGINE=MEMORY COMMENT='Contains Perfmon Sampling Information';
