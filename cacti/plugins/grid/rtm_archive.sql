--
-- Table structure for table `grid_clusters`
--

DROP TABLE IF EXISTS `grid_clusters`;
CREATE TABLE `grid_clusters` (
  `clusterid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poller_id` int(10) unsigned NOT NULL DEFAULT '1',
  `cacti_host` int(10) unsigned NOT NULL DEFAULT '0',
  `cacti_tree` int(10) unsigned NOT NULL DEFAULT '0',
  `clustername` varchar(128) NOT NULL DEFAULT '',
  `cluster_timezone` varchar(64) NOT NULL DEFAULT '',
  `efficiency_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `efficiency` double NOT NULL DEFAULT '100',
  `efficiency_queues` varchar(255) DEFAULT '',
  `efficiency_warn_count` int(10) unsigned NOT NULL DEFAULT '0',
  `efficiency_alarm_count` int(10) unsigned NOT NULL DEFAULT '0',
  `efficiency_clear_count` int(10) unsigned NOT NULL DEFAULT '0',
  `disabled` char(2) NOT NULL DEFAULT '',
  `total_hosts` int(10) unsigned NOT NULL DEFAULT '0',
  `total_cpus` int(10) unsigned NOT NULL DEFAULT '0',
  `total_clients` int(10) unsigned NOT NULL DEFAULT '0',
  `hourly_started_jobs` double NOT NULL DEFAULT '0',
  `hourly_done_jobs` double NOT NULL DEFAULT '0',
  `hourly_exit_jobs` double NOT NULL DEFAULT '0',
  `daily_started_jobs` double NOT NULL DEFAULT '0',
  `daily_done_jobs` double NOT NULL DEFAULT '0',
  `daily_exit_jobs` double NOT NULL DEFAULT '0',
  `lim_timeout` int(10) unsigned NOT NULL DEFAULT '10',
  `mbd_timeout` int(10) unsigned NOT NULL DEFAULT '10',
  `mbd_job_timeout` int(10) unsigned NOT NULL DEFAULT '1',
  `mbd_job_retries` int(10) unsigned NOT NULL DEFAULT '1',
  `lsf_envdir` varchar(255) DEFAULT '',
  `lsf_confdir` varchar(255) DEFAULT '',
  `ego_confdir` varchar(255) NOT NULL DEFAULT '',
  `lsf_version` int(10) unsigned NOT NULL DEFAULT '62',
  `lsf_clustername` varchar(128) NOT NULL DEFAULT '',
  `lsf_ls_error` int(10) unsigned NOT NULL DEFAULT '0',
  `lsf_lsb_error` int(10) unsigned NOT NULL DEFAULT '0',
  `lsf_lsb_jobs_error` int(10) unsigned NOT NULL DEFAULT '0',
  `lsf_lim_response` float NOT NULL DEFAULT '0',
  `lsf_lsb_response` float NOT NULL DEFAULT '0',
  `lsf_lsb_jobs_response` float NOT NULL DEFAULT '0',
  `lsf_admins` varchar(256) NOT NULL DEFAULT '',
  `lsb_debug` varchar(20) NOT NULL DEFAULT '',
  `lsf_lim_debug` varchar(20) NOT NULL DEFAULT '',
  `lsf_res_debug` varchar(20) NOT NULL DEFAULT '',
  `lsf_log_mask` varchar(50) NOT NULL DEFAULT '',
  `lsf_master` varchar(64) NOT NULL DEFAULT '',
  `lsf_masterhosts` varchar(1024) NOT NULL DEFAULT '',
  `lsf_serverhosts` varchar(1024) NOT NULL DEFAULT '',
  `lsf_lic_schedhosts` varchar(1024) NOT NULL DEFAULT '',
  `lsf_unit` varchar(4) NOT NULL DEFAULT '',
  `collection_timing` int(10) unsigned NOT NULL,
  `max_nonjob_runtime` int(10) unsigned NOT NULL,
  `job_minor_timing` int(10) unsigned NOT NULL,
  `job_major_timing` int(10) unsigned NOT NULL,
  `ha_timing` int(10) unsigned NOT NULL,
  `max_job_runtime` int(10) unsigned NOT NULL,
  `ip` varchar(255) NOT NULL DEFAULT '',
  `lim_port` varchar(10) NOT NULL DEFAULT '',
  `lsf_ego` char(3) DEFAULT 'N',
  `lsf_strict_checking` varchar(10) NOT NULL DEFAULT 'N',
  `lsf_krb_auth` varchar(3) NOT NULL DEFAULT '',
  `lsf_master_hostname` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(255) NOT NULL DEFAULT '',
  `credential` varchar(512) NOT NULL DEFAULT '',
  `communication` varchar(10) NOT NULL DEFAULT 'ssh',
  `privatekey_path` varchar(255) NOT NULL DEFAULT '',
  `LSF_TOP` varchar(255) NOT NULL DEFAULT '',
  `add_frequency` int(10) unsigned NOT NULL,
  `host_template_id` mediumint(8) unsigned NOT NULL DEFAULT '14',
  `add_graph_frequency` int(10) unsigned NOT NULL DEFAULT '0',
  `advanced_enabled` char(2) NOT NULL DEFAULT '',
  `email_domain` varchar(64) NOT NULL DEFAULT '',
  `email_admin` varchar(512) NOT NULL DEFAULT '',
  `grididle_enabled` char(2) NOT NULL DEFAULT '',
  `grididle_notify` int(1) NOT NULL DEFAULT '0',
  `grididle_runtime` int(10) NOT NULL DEFAULT '3600',
  `grididle_window` int(10) NOT NULL DEFAULT '3600',
  `grididle_cputime` int(10) NOT NULL DEFAULT '24',
  `grididle_jobtypes` varchar(20) NOT NULL DEFAULT 'all',
  `grididle_jobcommands` varchar(255) NOT NULL DEFAULT '',
  `grididle_exclude_queues` varchar(255) NOT NULL DEFAULT '',
  `perfmon_run` char(3) DEFAULT '',
  `perfmon_interval` int unsigned DEFAULT '0',
  `exec_host_res_req` varchar(512) NOT NULL DEFAULT '',
  PRIMARY KEY (`clusterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hostgroups`
--

DROP TABLE IF EXISTS `grid_hostgroups`;
CREATE TABLE `grid_hostgroups` (
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `groupName` varchar(64) NOT NULL DEFAULT '',
  `host` varchar(64) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`clusterid`,`groupName`,`host`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hostinfo`
--

DROP TABLE IF EXISTS `grid_hostinfo`;
CREATE TABLE `grid_hostinfo` (
  `host` varchar(64) NOT NULL DEFAULT '',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `hostType` varchar(20) NOT NULL DEFAULT '',
  `hostModel` varchar(40) NOT NULL DEFAULT '0',
  `cpuFactor` varchar(10) DEFAULT NULL,
  `maxCpus` varchar(10) DEFAULT NULL,
  `maxMem` varchar(20) DEFAULT NULL,
  `maxSwap` varchar(20) DEFAULT NULL,
  `maxTmp` varchar(20) DEFAULT NULL,
  `nDisks` varchar(10) NOT NULL DEFAULT '0',
  `resources` varchar(255) NOT NULL DEFAULT '',
  `excl_resources` varchar(255) NOT NULL DEFAULT '',
  `windows` varchar(255) NOT NULL DEFAULT '0',
  `isServer` char(1) NOT NULL DEFAULT '',
  `licensed` char(1) NOT NULL DEFAULT '',
  `rexPriority` int(10) unsigned NOT NULL DEFAULT '0',
  `licFeaturesNeeded` int(10) unsigned NOT NULL DEFAULT '0',
  `licClass` int(10) unsigned NOT NULL DEFAULT '0',
  `nProcs` int(10) unsigned NOT NULL DEFAULT '0',
  `cores` int(10) unsigned NOT NULL DEFAULT '0',
  `nThreads` int(10) unsigned NOT NULL DEFAULT '0',
  `ngpus` int(10) unsigned NOT NULL DEFAULT '0',
  `gMaxFactor` float NOT NULL DEFAULT '0',
  `gpu_shared_avg_mut` float NOT NULL DEFAULT '0',
  `gpu_shared_avg_ut` float NOT NULL DEFAULT '0',
  `first_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`host`,`clusterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hostresources`
--

DROP TABLE IF EXISTS `grid_hostresources`;
CREATE TABLE `grid_hostresources` (
  `host` varchar(64) NOT NULL DEFAULT '',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `rtype` TINYINT unsigned NOT NULL DEFAULT '0', 
  `resource_name` varchar(50) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`host`,`clusterid`,`resource_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hosts`
--

DROP TABLE IF EXISTS `grid_hosts`;
CREATE TABLE `grid_hosts` (
  `host` varchar(64) NOT NULL DEFAULT '',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `hStatus` int(10) unsigned NOT NULL DEFAULT '0',
  `hCtrlMsg` varchar(255) NOT NULL DEFAULT '',
  `status` varchar(20) DEFAULT NULL,
  `prev_status` varchar(20) NOT NULL DEFAULT '',
  `time_in_state` int(10) unsigned NOT NULL DEFAULT '0',
  `cpuFactor` float NOT NULL DEFAULT '0',
  `windows` varchar(255) DEFAULT NULL,
  `userJobLimit` varchar(20) DEFAULT NULL,
  `maxJobs` int(11) NOT NULL DEFAULT '0',
  `numJobs` int(10) unsigned NOT NULL DEFAULT '0',
  `numRun` int(10) unsigned NOT NULL DEFAULT '0',
  `numSSUSP` int(10) unsigned NOT NULL DEFAULT '0',
  `numUSUSP` int(10) unsigned NOT NULL DEFAULT '0',
  `mig` int(10) unsigned NOT NULL DEFAULT '0',
  `attr` int(10) unsigned NOT NULL DEFAULT '0',
  `numRESERVE` int(10) unsigned NOT NULL DEFAULT '0',
  `ngpus` int(10) unsigned NOT NULL DEFAULT '0',
  `avail_shared_ngpus` int(10) unsigned NOT NULL DEFAULT '0',
  `avail_excl_ngpus` int(10) unsigned NOT NULL DEFAULT '0',
  `alloc_jsexcl_ngpus` int(10) unsigned NOT NULL DEFAULT '0',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `exceptional` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`host`,`clusterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hosts_gpu`
--

DROP TABLE IF EXISTS `grid_hosts_gpu`;
CREATE TABLE `grid_hosts_gpu` (
  `host` varchar(64) NOT NULL DEFAULT '',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `gpu_id` int(10) unsigned NOT NULL DEFAULT '0',
  `gpu_model` varchar(40) NOT NULL DEFAULT '',
  `gpu_mode` int(10) unsigned NOT NULL DEFAULT '0',
  `pstatus` int(10) unsigned NOT NULL DEFAULT '0',
  `status` varchar(20) DEFAULT NULL,
  `gpu_error` varchar(255) NOT NULL DEFAULT '',
  `prev_status` varchar(20) NOT NULL DEFAULT '',
  `time_in_state` int(10) unsigned NOT NULL DEFAULT '0',
  `mem_used` int(10) unsigned NOT NULL DEFAULT '0',
  `mem_rsv` int(10) unsigned NOT NULL DEFAULT '0',
  `numJobs` int(10) unsigned NOT NULL DEFAULT '0',
  `numRun` int(10) unsigned NOT NULL DEFAULT '0',
  `numSUSP` int(10) unsigned NOT NULL DEFAULT '0',
  `numRSV` int(10) unsigned NOT NULL DEFAULT '0',
  `socketid` int(10) unsigned NOT NULL DEFAULT '0',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`host`,`clusterid`, `gpu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hosts_jobtraffic`
--

DROP TABLE IF EXISTS `grid_hosts_jobtraffic`;
CREATE TABLE `grid_hosts_jobtraffic` (
  `host` varchar(64) NOT NULL DEFAULT '',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `idle_slot_time` bigint(20) unsigned NOT NULL DEFAULT '0',
  `jobs_done` bigint(20) unsigned NOT NULL DEFAULT '0',
  `jobs_exited` bigint(20) unsigned NOT NULL DEFAULT '0',
  `job_last_started` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `job_last_ended` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `job_last_suspended` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `job_last_exited` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`host`,`clusterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_hosts_resources`
--

DROP TABLE IF EXISTS `grid_hosts_resources`;
CREATE TABLE `grid_hosts_resources` (
  `host` varchar(64) NOT NULL,
  `clusterid` int(10) unsigned NOT NULL,
  `resource_name` varchar(40) NOT NULL,
  `resType` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `flag` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `availValue` varchar(128) NOT NULL DEFAULT '',
  `totalValue` varchar(128) NOT NULL DEFAULT '',
  `reservedValue` varchar(128) NOT NULL DEFAULT '',
  `value` varchar(128) NOT NULL DEFAULT '',
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY USING HASH (`host`,`clusterid`,`resource_name`,`resType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_job_daily_stats`
--

DROP TABLE IF EXISTS `grid_job_daily_stats`;
CREATE TABLE `grid_job_daily_stats` (
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `user` varchar(45) NOT NULL DEFAULT '',
  `stat` varchar(45) NOT NULL DEFAULT '',
  `queue` varchar(60) NOT NULL DEFAULT '',
  `app` varchar(40) NOT NULL DEFAULT '',
  `from_host` varchar(64) NOT NULL DEFAULT '',
  `exec_host` varchar(64) NOT NULL DEFAULT '',
  `projectName` varchar(60) NOT NULL DEFAULT '',
  `jobs_in_state` int(10) unsigned NOT NULL DEFAULT '0',
  `jobs_wall_time` int(10) unsigned NOT NULL DEFAULT '0',
  `gpu_wall_time` int(10) unsigned NOT NULL DEFAULT '0',
  `jobs_stime` double NOT NULL,
  `jobs_utime` double NOT NULL,
  `slots_in_state` int(10) unsigned NOT NULL DEFAULT '0',
  `gpus_in_state` int(10) unsigned NOT NULL DEFAULT '0',
  `avg_memory` double NOT NULL DEFAULT '0',
  `max_memory` double NOT NULL DEFAULT '0',
  `gpu_avg_mem` double NOT NULL DEFAULT '0',
  `gpu_max_mem` double NOT NULL DEFAULT '0',
  `interval_start` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_jobs_finished`
--

DROP TABLE IF EXISTS `grid_jobs_finished`;
CREATE TABLE `grid_jobs_finished` (
  `jobid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `indexid` int(10) unsigned NOT NULL DEFAULT '0',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `options` int(10) unsigned NOT NULL DEFAULT '0',
  `options2` int(10) unsigned NOT NULL DEFAULT '0',
  `options3` int(10) unsigned NOT NULL DEFAULT '0',
  `options4` int(10) unsigned NOT NULL DEFAULT '0',
  `user` varchar(40) NOT NULL DEFAULT '',
  `stat` varchar(10) NOT NULL DEFAULT '',
  `prev_stat` varchar(10) NOT NULL DEFAULT '',
  `stat_changes` int(10) unsigned NOT NULL DEFAULT '0',
  `flapping_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `exitStatus` int(10) unsigned NOT NULL DEFAULT '0',
  `pendReasons` varchar(512) NOT NULL DEFAULT '',
  `queue` varchar(60) NOT NULL DEFAULT '',
  `nice` varchar(4) NOT NULL DEFAULT '',
  `from_host` varchar(64) NOT NULL DEFAULT '',
  `exec_host` varchar(64) DEFAULT NULL,
  `execUid` int(10) unsigned NOT NULL DEFAULT '0',
  `loginShell` varchar(20) DEFAULT NULL,
  `execHome` varchar(255) NOT NULL DEFAULT '',
  `execCwd` varchar(255) NOT NULL DEFAULT '',
  `cwd` varchar(255) NOT NULL DEFAULT '',
  `exceptMask` int(10) NOT NULL DEFAULT '0',
  `exitInfo` int(10) NOT NULL DEFAULT '0',
  `postExecCmd` varchar(255) NOT NULL DEFAULT '',
  `app` varchar(40) NOT NULL DEFAULT '',
  `execUsername` varchar(40) NOT NULL DEFAULT '',
  `mailUser` varchar(512) DEFAULT NULL,
  `jobname` varchar(128) DEFAULT NULL,
  `jobPriority` int(10) unsigned NOT NULL DEFAULT '0',
  `jobPid` int(10) unsigned NOT NULL DEFAULT '0',
  `userPriority` int(11) DEFAULT '0',
  `projectName` varchar(60) NOT NULL DEFAULT '',
  `parentGroup` varchar(128) NOT NULL DEFAULT '',
  `sla` varchar(60) NOT NULL DEFAULT '',
  `jobGroup` varchar(512) NOT NULL DEFAULT '',
  `licenseProject` varchar(60) NOT NULL DEFAULT '',
  `command` varchar(1024) DEFAULT NULL,
  `inFile` varchar(255) DEFAULT NULL,
  `outFile` varchar(255) DEFAULT NULL,
  `errFile` varchar(255) DEFAULT NULL,
  `preExecCmd` varchar(255) DEFAULT NULL,
  `res_requirements` varchar(512) DEFAULT NULL,
  `gpuResReq` varchar(512) DEFAULT NULL,
  `dependCond` varchar(1024) DEFAULT '',
  `mem_used` double DEFAULT NULL,
  `swap_used` double NOT NULL DEFAULT '0',
  `max_memory` double DEFAULT '0',
  `gpu_mem_used` double DEFAULT '0',
  `gpu_max_memory` double DEFAULT '0',
  `max_swap` double DEFAULT '0',
  `mem_requested` double DEFAULT '0',
  `mem_requested_oper` varchar(8) DEFAULT '',
  `mem_reserved` double DEFAULT '0',
  `cpu_used` double NOT NULL DEFAULT '0',
  `utime` double NOT NULL DEFAULT '0',
  `stime` double NOT NULL DEFAULT '0',
  `efficiency` decimal(9,5) NOT NULL DEFAULT '0.00000',
  `effic_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `numPIDS` int(10) unsigned NOT NULL DEFAULT '0',
  `numPGIDS` int(10) unsigned NOT NULL DEFAULT '0',
  `numThreads` int(10) unsigned NOT NULL DEFAULT '0',
  `pid_alarm_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `num_nodes` int(10) unsigned NOT NULL DEFAULT '1',
  `num_cpus` int(10) unsigned NOT NULL DEFAULT '1',
  `max_allocated_processes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'job level allocated slot',
  `maxNumProcessors` int(10) unsigned NOT NULL DEFAULT '0',
  `num_gpus` int(10) unsigned NOT NULL DEFAULT '0',
  `gpu_mode` int(10) unsigned NOT NULL DEFAULT '0',
  `submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `reserveTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `predictedStartTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `beginTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `termTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `runtimeEstimation` int(10) unsigned DEFAULT '0',
  `pend_time` int(10) unsigned NOT NULL DEFAULT '0',
  `psusp_time` int(10) unsigned NOT NULL DEFAULT '0',
  `run_time` int(10) unsigned NOT NULL DEFAULT '0',
  `gpu_exec_time` int(10) unsigned NOT NULL DEFAULT '0',
  `ususp_time` int(10) unsigned NOT NULL DEFAULT '0',
  `ssusp_time` int(10) unsigned NOT NULL DEFAULT '0',
  `unkwn_time` int(10) unsigned NOT NULL DEFAULT '0',
  `prov_time` int(10) unsigned NOT NULL DEFAULT '0',
  `acJobWaitTime` int(10) unsigned NOT NULL DEFAULT '0',
  `hostSpec` varchar(40) DEFAULT NULL,
  `rlimit_max_cpu` int(10) unsigned NOT NULL DEFAULT '0',
  `rlimit_max_wallt` int(10) unsigned NOT NULL DEFAULT '0',
  `rlimit_max_swap` float unsigned NOT NULL DEFAULT '0',
  `rlimit_max_fsize` float unsigned NOT NULL DEFAULT '0',
  `rlimit_max_data` float unsigned NOT NULL DEFAULT '0',
  `rlimit_max_stack` float unsigned NOT NULL DEFAULT '0',
  `rlimit_max_core` float unsigned NOT NULL DEFAULT '0',
  `rlimit_max_rss` float unsigned NOT NULL DEFAULT '0',
  `job_start_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `job_end_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `job_scan_logged` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `userGroup` varchar(40) NOT NULL,
  `jobDescription` varchar(512) DEFAULT '',
  `combinedResreq` varchar(512) DEFAULT '',
  `effectiveResreq` varchar(512) DEFAULT '',
  `gpuCombinedResreq` varchar(512) DEFAULT '',
  `gpuEffectiveResreq` varchar(512) DEFAULT '',
  `chargedSAAP` varchar(256) DEFAULT '',
  `ineligiblePendingTime` int(10) unsigned NOT NULL DEFAULT '0',
  `pendState` int(10) NOT NULL DEFAULT '-1',
  `effectivePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT '0',
  `effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT '0',
  `isLoaningGSLA` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_jobs_jobhosts_finished`
--

DROP TABLE IF EXISTS `grid_jobs_jobhosts_finished`;
CREATE TABLE `grid_jobs_jobhosts_finished` (
  `jobid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `indexid` int(10) unsigned NOT NULL DEFAULT '0',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `exec_host` varchar(64) NOT NULL DEFAULT '',
  `submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `processes` int(11) NOT NULL DEFAULT '0',
  `ngpus` mediumint(8) NOT NULL DEFAULT '0',
  `isborrowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`exec_host`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_jobs_reqhosts_finished`
--

DROP TABLE IF EXISTS `grid_jobs_reqhosts_finished`;
CREATE TABLE `grid_jobs_reqhosts_finished` (
  `jobid` bigint(20) unsigned NOT NULL DEFAULT '0',
  `indexid` int(10) unsigned NOT NULL DEFAULT '0',
  `clusterid` int(10) unsigned NOT NULL DEFAULT '0',
  `host` varchar(64) NOT NULL DEFAULT '',
  `submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_pollers`
--

DROP TABLE IF EXISTS `grid_pollers`;
CREATE TABLE `grid_pollers` (
  `poller_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poller_name` varchar(45) NOT NULL DEFAULT '',
  `poller_lbindir` varchar(255) NOT NULL DEFAULT '',
  `poller_licserver_threads` int(11) NOT NULL DEFAULT '5',
  `poller_location` varchar(255) NOT NULL DEFAULT '',
  `poller_support_info` varchar(255) NOT NULL DEFAULT '',
  `lsf_version` int(10) unsigned NOT NULL DEFAULT '100101',
  `remote` varchar(20) DEFAULT NULL,
  `poller_max_insert_packet_size` varchar(255),
  PRIMARY KEY (`poller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `grid_resources`
--

DROP TABLE IF EXISTS `grid_resources`;
CREATE TABLE `grid_resources` (
  `resource_name` varchar(20) NOT NULL,
  `value` varchar(128) NOT NULL,
  `clusterid` int(10) unsigned NOT NULL,
  `present` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY USING HASH (`resource_name`,`value`,`clusterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
