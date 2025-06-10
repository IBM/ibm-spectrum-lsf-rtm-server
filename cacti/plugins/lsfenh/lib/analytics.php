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

function set_lsf_environment(&$cluster) {
	$found = false;

	if (is_dir($cluster['lsf_envdir'])) {
		$lsf_serverdir = str_replace('/conf', '', $cluster['lsf_envdir']) . '/10.1/linux3.10-glibc2.17-x86_64/etc';

		if (!is_dir($lsf_serverdir)) {
			$lsf_serverdir = str_replace('/conf', '', $cluster['lsf_envdir']) . '/10.1/linux2.6-glibc2.3-x86_64/etc';

			if (!is_dir($lsf_serverdir)) {
				$poller = db_fetch_row_prepared('SELECT *
					FROM grid_pollers
					WHERE poller_id = ?',
					array($cluster['poller_id']));

				if (cacti_sizeof($poller)) {
					$lsf_serverdir = str_replace('/bin/', '/etc/', $poller['poller_lbindir']);

					$found = true;
				}
			} else {
				$found = true;
			}
		} else {
			$found = true;
		}
	}

	if ($found) {
		putenv('LSF_ENVDIR=' . $cluster['lsf_envdir']);
		putenv('LSF_SERVERDIR=' . $lsf_serverdir);
		putenv('LSB_DISPLAY_YEAR=y');
		putenv('LSB_GPOOL_NAME_DISP_LENGTH=40');
	}

	return $found;
}

function getMemory($data) {
	if (strpos($data, 'Kbytes') !== false) {
		return trim(str_replace('Kbytes', '', $data)) * 1024;
	} elseif (strpos($data, 'Mbytes') !== false) {
		return trim(str_replace('Mbytes', '', $data)) * 1024 * 1024;
	} elseif (strpos($data, 'Gbytes') !== false) {
		return trim(str_replace('Gbytes', '', $data)) * 1024 * 1024 * 1024;
	} elseif (strpos($data, 'Tbytes') !== false) {
		return trim(str_replace('Tbytes', '', $data)) * 1024 * 1024 * 1024 * 1024;
	} elseif ($data != '-' && $data != '') {
		return trim($data);
	} else {
		return 0;
	}
}

function getReserved($data) {
	$reserved = explode('mem=', $data);

	if (isset($reserved[1])) {
		$parts1 = explode(',', $reserved[1]);
		$parts2 = explode(']', $reserved[1]);

		if (isset($parts1[0]) && is_numeric($parts1[0])) {
			return $parts1[0] * 1024 * 1024;
		} elseif (isset($parts2[0]) && is_numeric($parts2[0])) {
			return $parts2[0] * 1024 * 1024;
		}
	}

	return 0;
}

function getRequested($data) {
	$requested = explode('mem>', $data);

	if (isset($requested[1])) {
		$requested[1] = trim($requested[1], '=');
		$parts1 = explode(',', $requested[1]);
		$parts2 = explode(']', $requested[1]);
		$parts3 = explode(')', $requested[1]);

		if (isset($parts1[0]) && is_numeric($parts1[0])) {
			return $parts1[0] * 1024 * 1024;
		} elseif (isset($parts2[0]) && is_numeric($parts2[0])) {
			return $parts2[0] * 1024 * 1024;
		} elseif (isset($parts3[0]) && is_numeric($parts3[0])) {
			return $parts3[0] * 1024 * 1024;
		}
	}

	return 0;
}

function create_required_tables() {
	if (!db_table_exists('grid_reason_classes')) {
		db_execute('CREATE TABLE IF NOT EXISTS `grid_reason_classes` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`issusp` tinyint(3) unsigned NOT NULL DEFAULT "0",
			`reason_code` int(10) unsigned NOT NULL DEFAULT "0",
			`sub_reason_code` varchar(40) NOT NULL DEFAULT "",
			`reason` varchar(255) NOT NULL DEFAULT "",
			`class` varchar(20) NOT NULL DEFAULT "",
			`present` tinyint(4) DEFAULT "1",
			`last_seen` timestamp NOT NULL DEFAULT "0000-00-00 00:00:00",
			PRIMARY KEY (`id`),
			UNIQUE KEY `issusp_reason_subreason` (`issusp`,`reason_code`,`sub_reason_code`))
			ENGINE=InnoDB
			ROW_FORMAT=DYNAMIC
			COMMENT="Customer Derived Map of Reason Classes"');

		db_execute('INSERT INTO grid_reason_classes (reason_code, sub_reason_code, reason)
			SELECT reason_code, sub_reason_code, reason
			FROM grid_jobs_pendreason_maps
			WHERE issusp=0');
	}

	db_execute('CREATE TABLE IF NOT EXISTS `grid_guarantees` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL DEFAULT "0",
		`pool_name` varchar(40) NOT NULL DEFAULT "",
		`sla_name` varchar(40) NOT NULL DEFAULT "",
		`common_bu` varchar(40) NOT NULL DEFAULT "",
		`common_pool` varchar(40) NOT NULL DEFAULT "",
		`common_sla` varchar(40) NOT NULL DEFAULT "",
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp ON UPDATE current_timestamp,
		PRIMARY KEY (`id`),
		UNIQUE KEY `clusterid_pool_sla` (`clusterid`,`pool_name`,`sla_name`))
		ENGINE=InnoDB
		ROW_FORMAT=DYNAMIC');

	db_execute("CREATE TABLE IF NOT EXISTS `grid_sla_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`sla` varchar(40) NOT NULL DEFAULT '',
		`running` int(10) unsigned NOT NULL DEFAULT '0',
		`pending` int(10) unsigned NOT NULL DEFAULT '0',
		`suspended` int(10) unsigned NOT NULL DEFAULT '0',
		`jobs` int(10) unsigned NOT NULL DEFAULT '0',
		`slots` int(10) unsigned NOT NULL DEFAULT '0',
		`rslots` int(10) unsigned NOT NULL DEFAULT '0',
		`pslots` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_time` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_avg` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_max` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_median` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_p75` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_p95` int(10) unsigned NOT NULL DEFAULT '0',
		`pend_mem_reserved` double DEFAULT '0',
		`mem_requested` double DEFAULT '0',
		`mem_reserved` double DEFAULT '0',
		`mem_used` double DEFAULT '0',
		`rjob_efficiency` double NOT NULL DEFAULT '0',
		`rjob_cputime` int(10) unsigned NOT NULL DEFAULT '0',
		`rjob_walltime` int(10) unsigned NOT NULL DEFAULT '0',
		`dmem_reserved` double NOT NULL DEFAULT '0',
		`dmem_used` double NOT NULL DEFAULT '0',
		`djob_efficiency` double NOT NULL DEFAULT '0',
		`djob_cputime` int(10) unsigned NOT NULL DEFAULT '0',
		`djob_walltime` int(10) unsigned NOT NULL DEFAULT '0',
		`hourly_started_jobs` int(10) unsigned NOT NULL DEFAULT '0',
		`hourly_done_jobs` int(10) unsigned NOT NULL DEFAULT '0',
		`hourly_exit_jobs` int(10) unsigned DEFAULT '0',
		`hostl_hosts_total` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_hosts_shared` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_hosts_busy` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_slots_total` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_slots_used` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_slots_shared` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_slots_busy` int(10) unsigned NOT NULL DEFAULT '0',
		`hostl_memSlotUtil` double NOT NULL DEFAULT '0',
		`hostl_slotUtil` double NOT NULL DEFAULT '0',
		`hostl_cpuUtil` double NOT NULL DEFAULT '0',
		`hostl_memUsed` double NOT NULL DEFAULT '0',
		`hostl_memReserved` double NOT NULL DEFAULT '0',
		`present` tinyint(4) NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`,`sla`))
		ENGINE=InnoDB
		CHARSET=latin1
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds SLA Statistics'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_djob_hstats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`host` varchar(64) NOT NULL DEFAULT '',
		`mem_reserved` int(10) unsigned NOT NULL DEFAULT '0',
		`mem_used` int(10) unsigned NOT NULL DEFAULT '0',
		`runJobs` int(10) unsigned NOT NULL DEFAULT '0',
		`maxJobs` int(10) unsigned NOT NULL DEFAULT '0',
		`maxMemory` int(10) unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`,`host`))
		ENGINE=InnoDB
		CHARSET=latin1
		ROW_FORMAT=DYNAMIC
		COMMENT='Stores Recently Completed Job Metrics by Host for Memory Efficiency Stats'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_pool_memory` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`name` varchar(40) NOT NULL DEFAULT '',
		`freeMemory` double NOT NULL DEFAULT '0',
		`maxMemory` bigint(20) NOT NULL DEFAULT '0',
		`freePercent` double NOT NULL DEFAULT '0',
		`hosts` int(10) unsigned NOT NULL DEFAULT '0',
		`present` tinyint(4) DEFAULT '1',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`,`name`))
		ENGINE=InnoDB
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds Pool Free Memory Statistics'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_pool_host_lending` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`host` varchar(64) NOT NULL DEFAULT '',
		`numRun` int(10) unsigned NOT NULL DEFAULT '0',
		`maxJobs` int(10) unsigned NOT NULL DEFAULT '0',
		`freeMemory` double NOT NULL DEFAULT '0',
		`maxMemory` int(10) unsigned NOT NULL DEFAULT '0',
		`consumers` varchar(1024) NOT NULL DEFAULT '',
		`consumer_count` int(10) unsigned NOT NULL DEFAULT '0',
		`present` tinyint(4) DEFAULT '1',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`,`host`))
		ENGINE=InnoDB
		CHARSET=latin1
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds Lending Status of Service Class Hosts'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_sla_memory_buckets` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`sla` varchar(40) NOT NULL DEFAULT '',
		`memory_size` varchar(10) NOT NULL DEFAULT '',
		`pmem_requested` bigint(20) unsigned NOT NULL DEFAULT 0,
		`pmem_reserved` bigint(20) unsigned NOT NULL DEFAULT 0,
		`rmem_requested` bigint(20) unsigned NOT NULL DEFAULT 0,
		`rmem_reserved` bigint(20) unsigned NOT NULL DEFAULT 0,
		`rmax_memory` bigint(20) unsigned NOT NULL DEFAULT 0,
		`mem_requested` bigint(20) unsigned NOT NULL DEFAULT 0,
		`mem_reserved` bigint(20) unsigned NOT NULL DEFAULT 0,
		`max_memory` bigint(20) unsigned NOT NULL DEFAULT 0,
		`mem_efficiency` double NOT NULL DEFAULT 0,
		`rmem_efficiency` double NOT NULL DEFAULT 0,
		`pendJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`runJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`runArrays` int(10) unsigned NOT NULL DEFAULT 0,
		`runArrayStd` double NOT NULL DEFAULT 0,
		`doneJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`doneArrays` int(10) unsigned NOT NULL DEFAULT 0,
		`exitJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`exitArrays` int(10) unsigned NOT NULL DEFAULT 0,
		`finishArrayStd` double NOT NULL DEFAULT 0,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`sla`,`memory_size`))
		ENGINE=InnoDB ROW_FORMAT=DYNAMIC
		COMMENT='Holds Cluster/SLA memory bucket usage for finished jobs'");

	if (!db_table_exists('grid_sla_finished_memory_buckets')) {
		db_execute('CREATE TABLE `grid_sla_finished_memory_buckets` LIKE `grid_sla_memory_buckets`');
		db_execute('ALTER TABLE `grid_sla_finished_memory_buckets`
			ADD COLUMN table_name varchar(40) NOT NULL DEFAULT "" AFTER sla,
			ADD COLUMN year_day int(10) unsigned NOT NULL DEFAULT 0 AFTER table_name');
	}

	db_execute("CREATE TABLE IF NOT EXISTS `grid_hostgroups_definitions` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`groupName` varchar(64) NOT NULL DEFAULT '',
		`definition` mediumtext NOT NULL DEFAULT '',
		`present` tinyint unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`clusterid`,`groupName`))
		ENGINE=InnoDB
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds the Host Group Definition as reported by bmgroup -w in text format'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_job_groups` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`groupName` varchar(512) NOT NULL DEFAULT '',
		`sla` varchar(128) NOT NULL DEFAULT '',
		`numJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`pendJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`pendSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`runJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`runSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`suspJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`suspSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`finishJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`finishSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`rsvSlots` int(10) unsigned NOT NULL DEFAULT 0,
		`pendMemory` bigint(20) unsigned NOT NULL DEFAULT 0,
		`runMemReserved` bigint(20) unsigned NOT NULL DEFAULT 0,
		`runMaxMemory` bigint(20) unsigned NOT NULL DEFAULT 0,
		`runCpuUsed` bigint(20) unsigned NOT NULL DEFAULT 0,
		`limitUsed` int(11) NOT NULL DEFAULT 0,
		`limitTotal` int(11) NOT NULL DEFAULT 0,
		`owner` varchar(40) NOT NULL DEFAULT '',
		`last_active` timestamp NOT NULL DEFAULT current_timestamp(),
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 0,
		PRIMARY KEY (`clusterid`,`groupName`),
		KEY `last_active_updated` (`last_active`,`last_updated`))
		ENGINE=InnoDB
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds more detailed Job Group Information'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_planned` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`num_cpus` int(10) unsigned NOT NULL DEFAULT 0,
		`mem_reserved` int(10) unsigned NOT NULL DEFAULT 0,
		`plan_host` varchar(64) NOT NULL DEFAULT '',
		`plan_created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`plat_rank` int(10) unsigned NOT NULL DEFAULT 0,
		`plan_start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`plan_finish_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`plan_slots` int(10) unsigned NOT NULL DEFAULT 0,
		`plan_memory` int(10) unsigned NOT NULL DEFAULT 0,
		`plan_resources` varchar(1024) NOT NULL DEFAULT '',
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`))
		ENGINE=InnoDB DEFAULT CHARSET=latin1
		ROW_FORMAT=DYNAMIC
		COMMENT='Holds Jobs that have Plans from Analytics Plugin'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_clusters_maxcpus_bymodel` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`hostModel` varchar(64) NOT NULL DEFAULT '',
		`maxCpus` int(10) unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`hostModel`))
		ENGINE=InnoDB
		ROW_FORMAT=DYNAMIC");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_hosts_perf_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`host` varchar(64) NOT NULL DEFAULT '',
		`status` varchar(20) NOT NULL DEFAULT '',
		`slotUtil` double NOT NULL DEFAULT '0',
		`cpuUtil` double NOT NULL DEFAULT '0',
		`memEffUtil` double NOT NULL DEFAULT '0',
		`memEffic` double NOT NULL DEFAULT '0',
		`cpuEffic` double NOT NULL DEFAULT '0',
		`memUtil` double NOT NULL DEFAULT '0',
		`maxSlots` int unsigned NOT NULL DEFAULT '0',
		`runSlots` int unsigned NOT NULL DEFAULT '0',
		`oFactor` double NOT NULL DEFAULT '0',
		`maxCpus` int unsigned NOT NULL DEFAULT '0',
		`memReserved` bigint unsigned NOT NULL DEFAULT '0',
		`memUsed` bigint unsigned NOT NULL DEFAULT '0',
		`maxMem` bigint unsigned NOT NULL DEFAULT '0',
		`totalMem` bigint unsigned NOT NULL DEFAULT '0',
		`memFree` bigint unsigned NOT NULL DEFAULT '0',
		`runTime` int unsigned NOT NULL DEFAULT '0',
		`cpuUsed` int unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`, `host`),
		KEY `clusterid_last_updated` (`clusterid`, `last_updated`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=Dynamic
		COMMENT='Holds Host Level Effective Utilization'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_hostgroups_perf_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`groupName` varchar(64) NOT NULL DEFAULT '',
		`totalHosts` varchar(20) NOT NULL DEFAULT '',
		`okHosts` int unsigned NOT NULL default '0',
		`slotUtil` double NOT NULL DEFAULT '0',
		`cpuUtil` double NOT NULL DEFAULT '0',
		`memEffUtil` double NOT NULL DEFAULT '0',
		`memEffic` double NOT NULL DEFAULT '0',
		`cpuEffic` double NOT NULL DEFAULT '0',
		`memUtil` double NOT NULL DEFAULT '0',
		`maxSlots` int unsigned NOT NULL DEFAULT '0',
		`runSlots` int unsigned NOT NULL DEFAULT '0',
		`oFactor` double NOT NULL DEFAULT '0',
		`maxCpus` int unsigned NOT NULL DEFAULT '0',
		`memReserved` bigint unsigned NOT NULL DEFAULT '0',
		`memUsed` bigint unsigned NOT NULL DEFAULT '0',
		`maxMem` bigint unsigned NOT NULL DEFAULT '0',
		`totalMem` bigint unsigned NOT NULL DEFAULT '0',
		`memFree` bigint unsigned NOT NULL DEFAULT '0',
		`runTime` int unsigned NOT NULL DEFAULT '0',
		`cpuUsed` int unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`, `groupName`),
		KEY `clusterid_last_updated` (`clusterid`, `last_updated`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=Dynamic
		COMMENT='Holds Hostgroup Level Effective Utilization'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_queues_perf_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`queue` varchar(64) NOT NULL DEFAULT '',
		`totalHosts` varchar(20) NOT NULL DEFAULT '',
		`okHosts` int unsigned NOT NULL default '0',
		`slotUtil` double NOT NULL DEFAULT '0',
		`cpuUtil` double NOT NULL DEFAULT '0',
		`memEffUtil` double NOT NULL DEFAULT '0',
		`memEffic` double NOT NULL DEFAULT '0',
		`cpuEffic` double NOT NULL DEFAULT '0',
		`memUtil` double NOT NULL DEFAULT '0',
		`maxSlots` int unsigned NOT NULL DEFAULT '0',
		`runSlots` int unsigned NOT NULL DEFAULT '0',
		`oFactor` double NOT NULL DEFAULT '0',
		`maxCpus` int unsigned NOT NULL DEFAULT '0',
		`memReserved` bigint unsigned NOT NULL DEFAULT '0',
		`memUsed` bigint unsigned NOT NULL DEFAULT '0',
		`maxMem` bigint unsigned NOT NULL DEFAULT '0',
		`totalMem` bigint unsigned NOT NULL DEFAULT '0',
		`memFree` bigint unsigned NOT NULL DEFAULT '0',
		`runTime` int unsigned NOT NULL DEFAULT '0',
		`cpuUsed` int unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`, `queue`),
		KEY `clusterid_last_updated` (`clusterid`, `last_updated`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=Dynamic
		COMMENT='Holds Queue Level Effective Utilization'");

	db_execute("CREATE TABLE IF NOT EXISTS grid_jobs_summary (
		CLUSTERID int UNSIGNED default '0',
		JOBID bigint UNSIGNED default '0',
		JOBINDEX int UNSIGNED default '0',
		USER varchar(20) default '',
		PROJ_NAME varchar(64) default '',
		APPLICATION varchar(64) default '',
		STAT varchar(10) default '',
		QUEUE varchar(64) default '',
		SERVICE_CLASS varchar(40) default '',
		FIRST_HOST varchar(64) default '',
		SLOTS int UNSIGNED default '0',
		CPU_USED int UNSIGNED default '0',
		MEM_REQUESTED double UNSIGNED default '0',
		MEM_RESERVED double UNSIGNED default '0',
		MAX_MEM double UNSIGNED default '0',
		MEM double UNSIGNED default '0',
		RUN_TIME int UNSIGNED default '0',
		SUBMIT_TIME timestamp default '0000-00-00',
		START_TIME timestamp default '0000-00-00',
		FINISH_TIME timestamp default '0000-00-00',
		EFFECTIVE_RESREQ varchar(1024) default '',
		COMBINED_RESREQ varchar(1024) default '',
		PRESENT tinyint default '1',
		PRIMARY KEY (CLUSTERID, JOBID, JOBINDEX),
		INDEX sla(SERVICE_CLASS),
		INDEX stat(STAT),
		INDEX queue(QUEUE),
		INDEX clusterid_sla(CLUSTERID, SERVICE_CLASS),
		INDEX clusterid_stat(CLUSTERID, STAT),
		INDEX clusterid_queue(CLUSTERID, QUEUE))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=Dynamic
		COMMENT='Hold Active Job Summary Info'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_reason_summary` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`issusp` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`level` varchar(20) NOT NULL DEFAULT 'cluster',
		`type` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`reason` varchar(255) NOT NULL DEFAULT '',
		`jobs_occurrences` int(10) unsigned NOT NULL DEFAULT 0,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`id`),
		UNIQUE KEY `clusterid_level_type_reason` (`clusterid`,`level`,`type`,`reason`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=DYNAMIC
		COMMENT='Pending Reasons Summary at Various Levels'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_reason_details` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`issusp` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`level` varchar(20) NOT NULL DEFAULT 'cluster',
		`type` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`reason` varchar(255) NOT NULL DEFAULT '',
		`jobs_occurrences` int(10) unsigned NOT NULL DEFAULT 0,
		`present` tinyint(4) DEFAULT 1,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`id`),
		UNIQUE KEY `clusterid_level_type_reason` (`clusterid`,`level`,`type`,`reason`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=DYNAMIC
		COMMENT='Pending Reasons at Various Levels'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_reasons` (
        `CLUSTERID` int(10) unsigned NOT NULL DEFAULT '0',
        `REASONS` varchar(8192) DEFAULT NULL,
        PRIMARY KEY (`CLUSTERID`))
        ENGINE=InnoDB
        ROW_FORMAT=DYNAMIC");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_resource_descriptions` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`resource_name` varchar(64) NOT NULL DEFAULT '0',
		`description` varchar(128) NOT NULL DEFAULT '',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`, `resource_name`))
		ENGINE=InnoDB
		DEFAULT CHARSET=latin1
		COLLATE=latin1_swedish_ci
		ROW_FORMAT=DYNAMIC
		COMMENT='Stores Resource Descriptions'");
}

function grid_kill_process_tree($pid, $db_thread, $signal = SIGTERM) {
	$exists = posix_getpgid($pid);

	if ($exists) {
		$output = shell_exec("pstree -p $pid");

		if ($output != '') {
			$piddata = explode('(', $output);

			foreach($piddata as $p) {
				if (strpos($p, ')') !== false) {
					$parts = explode(')', $p);
					$pid   = $parts[0];

					if ($pid > 0) {
						posix_kill($pid, $signal);
					}
				}
			}

			if ($db_thread != false) {
				$query_id = db_fetch_cell_prepared('SELECT QUERY_ID
					FROM information_schema.PROCESSLIST
					WHERE ID = ?
					AND INFO IS NOT NULL
					AND command != "Sleep"
					AND time > 30',
					array($db_thread));

				if ($query_id) {
					// Try to kill the query first MariaDB 10.5.0++
					cacti_log('NOTE: Killing ETL MariaDB Query ID:' . $query_id, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

					if (!db_execute_prepared('KILL QUERY ID ?', array($query_id), false)) {
						cacti_log('NOTE: Killing ETL MariaDB Thread ID:' . $db_thread, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

						// If that faile, kill the thread "dangerous"
						db_execute_prepared('KILL ?', array($db_thread), false);
					}
				}
			}
		}
	}

	posix_kill($pid, $signal);

	return posix_getpgid($pid);
}

function grid_remove_process_entry($taskid, $taskname) {
	db_execute_prepared('DELETE FROM grid_processes
		WHERE taskid = ?
		AND taskname = ?',
		array($taskid, $taskname));
}

function grid_detect_and_correct_running_processes($pollerid, $taskname, $max_runtime, $kill = false) {
	$now = time();

	$row = db_fetch_row_prepared('SELECT *
		FROM grid_processes
		WHERE taskid = ?
		AND taskname = ?',
		array($pollerid, $taskname));

	if (strpos($taskname, '|') !== false) {
		$parts = explode('|', $taskname);
		$db_thread = $parts[1];
	} else {
		$db_thread = false;
	}

	if (sizeof($row)) {
		$heartbeat = strtotime($row['heartbeat']);

		if (($now - $heartbeat) > $max_runtime) {
			$running = false;

			if ($row['pid'] > 1) {
				$running = posix_kill($row['pid'], 0);

				if (posix_get_last_error() == 1) {
					$running = true;
				}
			}

			if ($row['pid'] > 1 && $kill && $running) {
				cacti_log("ERROR: TASK:$taskname, PID:" . $row['pid'] . ", ran over its max runtime.  It and its children to be killed", false, 'LSFENH');

				grid_kill_process_tree($row['pid'], $db_thread, SIGTERM);
			} elseif ($row['pid'] > 1) {
				if ($running) {
					cacti_log("TASK:$taskname, Exceeded its run limit and will continue to run.", false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);
				} else {
					cacti_log("ERROR: TASK:$taskname, Has crashed.  New process to be started.", false, 'LSFENH');

					$kill = true;
				}
			} else {
				cacti_log("ERROR TASK:$taskname, Has an invalid PID.  Contact Support.", false, 'LSFENH');

				return false;
			}

			if (!$kill) {
				db_execute_prepared('UPDATE IGNORE grid_processes
					SET heartbeat = NOW()
					WHERE taskname = ?
					AND taskid = ?',
					array($taskname, $pollerid));

				return false;
			} else {
				db_execute_prepared('REPLACE INTO grid_processes
					(pid, taskname, taskid, heartbeat)
					VALUES (?, ?, ?, NOW())',
					array(getmypid(), $taskname, $pollerid));

				return true;
			}
		}
	} else {
		db_execute_prepared('REPLACE INTO grid_processes
			(pid, taskname, taskid, heartbeat)
			VALUES (?, ?, ?, NOW())',
			array(getmypid(), $taskname, $pollerid));
	}

	return true;
}

function grid_collect_pend_remote($clusterid) {
	cacti_log('NOTE: Bjobs pending/suspended starting for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$jobs = db_fetch_assoc_prepared('SELECT pendReasons, count(*) AS jobs
		FROM grid_jobs
		WHERE clusterid = ?
		AND stat = "PEND"
		GROUP BY pendReasons',
		array($clusterid));

	if (cacti_sizeof($jobs)) {
		foreach($jobs as $job) {
			$reasons = explode(';', $job['pendReasons']);
		}
	}
}

function grid_collect_pend($clusterid) {
	global $config;

	$pstart = microtime(true);

	cacti_log('NOTE: Bjobs pending/suspended starting for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$exclude_clusters = read_config_option('grid_reasons_exclude_clusters');

	if ($exclude_clusters == '') {
		$exclude_clusters = array(38, 120);
	} else {
		$exclude_clusters = explode(',', $exclude_clusters);
	}

	// Don't run on excluded clusters.
	if (in_array($clusterid, $exclude_clusters)) {
		db_execute_prepared('DELETE FROM grid_jobs_reason_summary WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_jobs_reason_details WHERE clusterid = ?', array($clusterid));
		return;
	}

	// Get summary pending reasons
	$reasons = shell_exec($config['base_path'] . '/plugins/lsfenh/bin/bjobs -psum -p1 -uall 2>&1');
	grid_process_reasons($clusterid, $reasons, 'cluster', 0);

	// Get summary suspended reasons
	$suspend = shell_exec($config['base_path'] . '/plugins/lsfenh/bin/bjobs -uall -s 2>&1');

	$pend = microtime(true);

	cacti_log("NOTE: Bjobs pending/suspended completed for ClusterID:$clusterid in " . number_format($pend - $pstart, 2) . " seconds", false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$clustername = '';

	if ($suspend != '') {
		$sreasons = explode("\n", $suspend);
		$susp = array();
		$sjobs = 0;

		foreach($sreasons as $line) {
			if (substr($line, 0, 1) == ' ') {
				$line = trim($line, ' ;');
				if (isset($susp[$line])) {
					$susp[$line]++;
				} else {
					$susp[$line] = 1;
				}
				$sjobs++;
			}

			if (cacti_sizeof($susp)) {
				if ($clustername == '') {
					$clustername = db_fetch_cell_prepared('SELECT lsf_clustername
						FROM grid_clusters
						WHERE clusterid = ?',
						array($clusterid));
				}

				$suspend = PHP_EOL . 'Suspended reasons summary: published ' . date('D M j H:i:s Y') . PHP_EOL;
				$suspend .= 'Summarizing ' . $sjobs . ' suspended jobs in cluster (' . $clustername . '):' . PHP_EOL;

				foreach($susp as $reason => $sjobs) {
					$suspend .= $reason . ' ' . $sjobs . ' job' . ($sjobs > 1 ? 's':'') . PHP_EOL;
				}
			} else {
				$suspend = PHP_EOL . trim($suspend);
			}
		}
	}

	grid_process_reasons($clusterid, $suspend, 'cluster', 1);

	db_execute_prepared('INSERT INTO grid_jobs_reasons
		(REASONS, CLUSTERID)
		VALUES (?, ?)
		ON DUPLICATE KEY UPDATE REASONS=VALUES(REASONS)',
		array(trim($reasons . $suspend), $clusterid));
}

function grid_collect_sla_reasons($sla, $clusterid) {
	global $config;

	$remote = db_fetch_cell_prepared('SELECT remote
		FROM grid_clusters as gc
		INNER JOIN grid_pollers AS gp
		ON gc.poller_id = gp.poller_id
		WHERE clusterid = ?',
		array($clusterid));

	// Get summary pending reasons
	if ($remote != 'on') {
		$reasons = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bjobs -psum -p1 -uall -sla $sla 2>&1");
		grid_process_reasons($clusterid, $reasons, 'sla_' . $sla, 0);
	}
}

function grid_update_job_groups($clusterid) {
	global $config;

	$job_lines     = array();
	$slot_lines    = array();
	$bjgroup_jobs  = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bjgroup -s 2>&1");
	$jsql = $ssql  = array();

	grid_debug('Job Group Length ' . strlen($bjgroup_jobs) . ' Job Group Length in ClusterID:' . $clusterid);

	if ($bjgroup_jobs != '') {
		$job_lines = explode("\n", $bjgroup_jobs);

		if (strpos($job_lines[0], 'No job group found') === false && strpos($job_lines[0], 'Failed in an LSF library') === false) {
			$bjgroup_slots = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bjgroup -N 2>&1");

			if ($bjgroup_slots != '') {
				$slot_lines = explode("\n", $bjgroup_slots);
			}
		} else {
			$job_lines = array();
		}
	}

	grid_debug('There are ' . cacti_sizeof($job_lines) . 'Job Groups in ClusterID:' . $clusterid);

	$update_time = date('Y-m-d H:i:s');

	$jsuffix = ' ON DUPLICATE KEY UPDATE
		numJobs = VALUES(numJobs),
		pendJobs = VALUES(pendJobs),
		runJobs = VALUES(runJobs),
		suspJobs = VALUES(suspJobs),
		finishJobs = VALUES(finishJobs),
		sla = VALUES(sla),
		limitUsed = VALUES(limitUsed),
		limitTotal = VALUES(limitTotal),
		last_updated = VALUES(last_updated),
		present = 1';

	$ssuffix = ' ON DUPLICATE KEY UPDATE
		numSlots = VALUES(numSlots),
		pendSlots = VALUES(pendSlots),
		runSlots = VALUES(runSlots),
		suspSlots = VALUES(suspSlots),
		rsvSlots = VALUES(rsvSlots),
		finishSlots = VALUES(finishSlots),
		last_updated = VALUES(last_updated),
		present = 1';


	$jprefix = 'INSERT INTO grid_job_groups
		(clusterid, groupName, numJobs, pendJobs, runJobs, suspJobs, finishJobs, sla, limitUsed, limitTotal, owner, last_updated, present) VALUES ';

	$errors = 0;
	$logged = false;

	if (cacti_sizeof($job_lines)) {
		foreach($job_lines as $line) {
			$line = trim($line);

			if ($line == '') {
				continue;
			} elseif (strpos($line, 'GROUP_NAME') !== false) {
				continue;
			}

			$parts = preg_split('/[\s]+/', $line);

			if (cacti_sizeof($parts) > 10) {
				$nparts = cacti_sizeof($parts);

				if (!$logged) {
					cacti_log("Job: Cluster: $clusterid Elements: $nparts, $line", false, 'LSFENH');
					$logged = true;
				}

				$errors++;

				continue;
			}

			$numJobs    = is_numeric($parts[1]) ? $parts[1]:0;
			$pendJobs   = is_numeric($parts[2]) ? $parts[2]:0;
			$runJobs    = is_numeric($parts[3]) ? $parts[3]:0;
			$uJobs      = is_numeric($parts[4]) ? $parts[4]:0;
			$sJobs      = is_numeric($parts[5]) ? $parts[5]:0;
			$suspJobs   = $uJobs + $sJobs;
			$finishJobs = is_numeric($parts[6]) ? $parts[6]:0;
			$limit      = explode('/', $parts[8]);
			$sla        = trim($parts[7], '()');

			if ($limit[0] == '-') {
				$limitUsed = '-1';
			} else {
				$limitUsed = $limit[0];
			}

			if (isset($limit[1])) {
				if ($limit[1] == '-') {
					$limitTotal = '-1';
				} else {
					$limitTotal = $limit[1];
				}
			} else {
				$limitTotal = '0';
			}

			$jsql[] = '(' .
				$clusterid            . ',' .
				db_qstr($parts[0])    . ', ' .
				db_qstr($numJobs)     . ', ' .
				db_qstr($pendJobs)    . ', ' .
				db_qstr($runJobs)     . ', ' .
				db_qstr($suspJobs)    . ', ' .
				db_qstr($finishJobs)  . ', ' .
				db_qstr($sla)         . ', ' .
				db_qstr($limitUsed)   . ', ' .
				db_qstr($limitTotal)  . ', ' .
				db_qstr($parts[9])    . ', ' .
				db_qstr($update_time) . ', ' .
				'1)';
		}
	}

	$sprefix = 'INSERT INTO grid_job_groups
		(clusterid, groupName, numSlots, pendSlots, runSlots, suspSlots, rsvSlots, last_updated, present) VALUES ';

	$logged = false;

	if (cacti_sizeof($slot_lines)) {
		foreach($slot_lines as $line) {
			$line = trim($line);

			if ($line == '') {
				continue;
			} elseif (strpos($line, 'GROUP_NAME') !== false) {
				continue;
			}

			$parts = preg_split('/[\s]+/', $line);

			if (cacti_sizeof($parts) > 10) {
				$nparts = cacti_sizeof($parts);

				if (!$logged) {
					cacti_log("Slot: Cluster: $clusterid Elements: $nparts, $line", false, 'LSFENH');
					$logged = true;
				}

				$errors++;

				continue;
			}

			$numSlots  = is_numeric($parts[1]) ? $parts[1]:0;
			$uSlots    = is_numeric($parts[4]) ? $parts[4]:0;
			$sSlots    = is_numeric($parts[5]) ? $parts[5]:0;
			$suspSlots = $uSlots + $sSlots;
			$pendSlots = is_numeric($parts[2]) ? $parts[2]:0;
			$runSlots  = is_numeric($parts[3]) ? $parts[3]:0;
			$rsvSlots  = is_numeric($parts[6]) ? $parts[6]:0;

			$ssql[] = '(' .
				$clusterid            . ',' .
				db_qstr($parts[0])    . ', ' .
				db_qstr($numSlots)    . ', ' .
				db_qstr($pendSlots)    . ', ' .
				db_qstr($runSlots)    . ', ' .
				db_qstr($suspSlots)    . ', ' .
				db_qstr($rsvSlots)   . ', ' .
				db_qstr($update_time) . ', ' .
				'1)';
		}
	}

	if (cacti_sizeof($jsql)) {
		db_execute($jprefix . implode(', ', $jsql) . $jsuffix);
	}

	if (cacti_sizeof($ssql)) {
		db_execute($sprefix . implode(', ', $ssql) . $ssuffix);
	}

	db_execute_prepared('UPDATE grid_job_groups
		SET last_active = ?
		WHERE clusterid = ?
		AND numJobs > 0',
		array($update_time, $clusterid));

	if ($errors > 0) {
		cacti_log(sprintf('WARNING: Job Group Processing Encountered %s Errors', $errors), false, 'LSFENH');
	}
}

function grid_hostgroup_sla_definitions($clusterid) {
	global $config;

	$bmgroup = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bmgroup -w 2>&1");

	grid_process_bmgroup($clusterid, $bmgroup);

	$bresources = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bresources -g 2>&1");

	if ($bresources != '') {
		$lines = explode("\n", $bresources);

		foreach($lines as $line) {
			if ($line == '') {
				continue;
			}

			$parts = preg_split('/[\s]+/', $line);

			if ($parts[0] == 'POOL_NAME') {
				continue;
			}

			grid_process_bresources($clusterid, $parts[0]);
		}
	}
}

function grid_process_bresources($clusterid, $group) {
	global $config;

	$bresources = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bresources -g -l $group 2>&1 | grep 'HOSTS:'");

	if ($bresources != '') {
		if (!db_column_exists('grid_guarantee_pool', 'hosts')) {
			db_execute('ALTER TABLE grid_guarantee_pool ADD COLUMN hosts INT UNSIGNED NOT NULL default 0 AFTER guar_used');
		}

		$bresources = trim(str_replace('HOSTS:', '', $bresources));

		db_execute_prepared('UPDATE grid_guarantee_pool
			SET hosts = ?
			WHERE clusterid = ? AND name = ?',
			array($bresources, $clusterid, $group));
	}
}

function grid_process_bmgroup($clusterid, &$bmgroup) {
	if ($bmgroup != '') {
		$lines = explode("\n", $bmgroup);

		$sql_prefix = 'INSERT INTO grid_hostgroups_definitions (clusterid, groupName, definition, present) VALUES ';
		$sql_suffix = ' ON DUPLICATE KEY UPDATE definition=VALUES(definition), present=1';

		$sql = array();

		foreach($lines as $line) {
			if ($line == '') {
				continue;
			}

			$parts = preg_split('/[\s]+/', $line);

			if ($parts[0] == 'GROUP_NAME') {
				continue;
			}

			$groupName = $parts[0];
			unset($parts[0]);

			$definition = implode(' ', $parts);

			$sql[] = '(' . $clusterid . ', ' . db_qstr($groupName) . ', ' . db_qstr($definition) . ', 1)';
		}

		if (cacti_sizeof($sql)) {
			db_execute($sql_prefix . implode(', ', $sql) . $sql_suffix);
		}
	}
}

function grid_update_host_perf_stats($clusterid) {
	$start = microtime(true);

	/* do the hosts first, then hostgroups become easy */
	$last_updated = db_fetch_cell_prepared('SELECT MAX(last_updated)
		FROM grid_hosts_perf_stats
		WHERE clusterid = ?',
		array($clusterid));

	$format = array(
		'clusterid',
		'host',
		'status',
		'slotUtil',
		'cpuUtil',
		'memEffUtil',
		'memEffic',
		'cpuEffic',
		'memUtil',
		'maxSlots',
		'runSlots',
		'oFactor',
		'memFree',
		'maxCpus',
		'memReserved',
		'memUsed',
		'maxMem',
		'totalMem',
		'runTime',
		'cpuUsed',
	);

	$duplicate = '';

	foreach($format as $column) {
		if ($column == 'clusterid' || $column == 'host') {
			continue;
		}

		if ($duplicate == '') {
			$duplicate .= " ON DUPLICATE KEY UPDATE `$column` = VALUES(`$column`)";
		} else {
			$duplicate .= ", `$column` = VALUES(`$column`)";
		}
	}

	$records = db_fetch_assoc_prepared('SELECT gh.clusterid, gh.host, gh.status,
		(gh.numRun / gh.maxJobs) * 100 AS slotUtil,
		ROUND(IF(gh.status NOT LIKE "U%", gl.ut, 0) * 100, 2) AS cpuUtil,
		ROUND((sj.memReserved/ghi.maxMem) * 100, 2) AS memEffUtil,
		ROUND(sj.memEffic, 2) AS memEffic,
		ROUND(sj.cpuEffic, 2) AS cpuEffic,
		ROUND((sj.memUsed/ghi.maxMem) * 100, 2) AS memUtil,
		IF(gh.status NOT LIKE "U%", gh.maxJobs, 0) AS maxSlots,
		gh.numRun AS runSlots,
		IF(gh.status NOT LIKE "U%", gh.maxJobs/ghi.maxCpus, 1) AS oFactor,
		IF(gh.status NOT LIKE "U%", gl.mem, 0) AS memFree,
		IF(gh.status NOT LIKE "U%", ghi.maxCpus, 0) AS maxCpus,
		sj.memReserved, sj.memUsed, sj.maxMem, ghi.maxMem AS totalMem, sj.runTime, sj.cpuUsed
		FROM grid_hosts AS gh
		INNER JOIN grid_load AS gl
		ON gh.clusterid = gl.clusterid AND gh.host = gl.host
		INNER JOIN grid_hostinfo AS ghi
		ON gh.clusterid = ghi.clusterid AND gh.host = ghi.host
		LEFT JOIN (
			SELECT CLUSTERID, FIRST_HOST, SUM(MEM_RESERVED)/1048576 AS memReserved, SUM(MEM)/1048576 AS memUsed,
			SUM(MAX_MEM)/1048576 AS maxMem,
			(SUM(MEM)/SUM(MEM_RESERVED)) * 100 AS memEffic,
			SUM(RUN_TIME) AS runTime, SUM(CPU_USED) AS cpuUsed,
			(SUM(CPU_USED) / SUM(RUN_TIME * SLOTS)) * 100 AS cpuEffic
			FROM grid_jobs_summary
			WHERE STAT IN ("RUN", "SSUSP", "USUSP")
			GROUP BY CLUSTERID, FIRST_HOST
		) AS sj
		ON gh.clusterid = sj.CLUSTERID AND gh.host = sj.FIRST_HOST
		WHERE gh.clusterid = ?
		GROUP BY gh.clusterid, gh.host',
		array($clusterid));

	grid_pump_records($records, 'grid_hosts_perf_stats', $format, false, $duplicate);

	db_execute_prepared('DELETE FROM grid_hosts_perf_stats
		WHERE last_updated < ?
		AND clusterid = ?',
		array($last_updated, $clusterid));

	/**
	 * Hostgroups Section
	 *
	 * do the hostgroups next, based upon host info
	 */
	$last_updated = db_fetch_cell_prepared('SELECT MAX(last_updated)
		FROM grid_hostgroups_perf_stats
		WHERE clusterid = ?',
		array($clusterid));

	$host_groups = array_rekey(
		db_fetch_assoc_prepared('SELECT DISTINCT groupName
			FROM grid_hostgroups
			WHERE clusterid = ?',
			array($clusterid)),
		'groupName', 'groupName'
	);

	$format = array(
		'clusterid',
		'groupName',
		'totalHosts',
		'okHosts',
		'slotUtil',
		'cpuUtil',
		'memEffUtil',
		'memEffic',
		'cpuEffic',
		'memUtil',
		'maxSlots',
		'runSlots',
		'oFactor',
		'memFree',
		'maxCpus',
		'memReserved',
		'memUsed',
		'maxMem',
		'totalMem',
		'runTime',
		'cpuUsed',
	);

	$duplicate = '';

	foreach($format as $column) {
		if ($column == 'clusterid' || $column == 'groupName') {
			continue;
		}

		if ($duplicate == '') {
			$duplicate .= " ON DUPLICATE KEY UPDATE `$column` = VALUES(`$column`)";
		} else {
			$duplicate .= ", `$column` = VALUES(`$column`)";
		}
	}

	if (cacti_sizeof($host_groups)) {
		foreach($host_groups as $group) {
			$records = db_fetch_assoc_prepared('SELECT ghp.clusterid, ghg.groupName,
				COUNT(ghp.clusterid) AS totalHosts,
				SUM(IF(ghp.status NOT LIKE "U%", 1, 0)) AS okHosts,
				ROUND((SUM(ghp.runSlots) / SUM(ghp.maxSlots)) * 100, 2) AS slotUtil,
				ROUND(SUM(IF(ghp.status NOT LIKE "U%", ghp.cpuUtil*ghp.maxSlots, 0)) / SUM(IF(ghp.status NOT LIKE "U%", ghp.maxSlots, 0)), 2) AS cpuUtil,
				ROUND((SUM(memReserved)/SUM(totalMem)) * 100, 2) AS memEffUtil,
				ROUND((SUM(memUsed)/SUM(memReserved)) * 100, 2) AS memEffic,
				ROUND((SUM(cpuUsed) / SUM(runTime*runSlots)) * 100, 2) AS cpuEffic,
				ROUND((SUM(memUsed)/SUM(totalMem)) * 100, 2) AS memUtil,
				SUM(maxSlots) AS maxSlots,
				SUM(runSlots) AS runSlots,
				SUM(maxSlots)/SUM(maxCpus) AS oFactor,
				SUM(memFree) AS memFree,
				SUM(maxCpus) AS maxCpus,
				SUM(memReserved) AS memReserved,
				SUM(memUsed) AS memUsed,
				SUM(maxMem) AS maxMem,
				SUM(totalMem) AS totalMem,
				SUM(runTime) AS runTime, SUM(cpuUsed) AS cpuUsed
				FROM grid_hosts_perf_stats AS ghp
				INNER JOIN grid_hostgroups AS ghg
				ON ghp.clusterid = ghg.clusterid
				AND ghp.host = ghg.host
				WHERE ghg.clusterid = ?
				AND ghg.groupName = ?
				GROUP BY ghg.clusterid, ghg.groupName',
				array($clusterid, $group));

			grid_pump_records($records, 'grid_hostgroups_perf_stats', $format, false, $duplicate);
		}
	}

	db_execute_prepared('DELETE FROM grid_hostgroups_perf_stats
		WHERE last_updated < ?
		AND clusterid = ?',
		array($last_updated, $clusterid));

	/**
	 * Queues Section
	 *
	 * do the queues next, based upon host info
	 */
	$last_updated = db_fetch_cell_prepared('SELECT MAX(last_updated)
		FROM grid_queues_perf_stats
		WHERE clusterid = ?',
		array($clusterid));

	$queues = array_rekey(
		db_fetch_assoc_prepared('SELECT DISTINCT queueName
			FROM grid_queues
			WHERE clusterid = ?',
			array($clusterid)),
		'queueName', 'queueName'
	);

	$format = array(
		'clusterid',
		'queue',
		'totalHosts',
		'okHosts',
		'slotUtil',
		'cpuUtil',
		'memEffUtil',
		'memEffic',
		'cpuEffic',
		'memUtil',
		'maxSlots',
		'runSlots',
		'oFactor',
		'memFree',
		'maxCpus',
		'memReserved',
		'memUsed',
		'maxMem',
		'totalMem',
		'runTime',
		'cpuUsed',
	);

	$duplicate = '';

	foreach($format as $column) {
		if ($column == 'clusterid' || $column == 'queue') {
			continue;
		}

		if ($duplicate == '') {
			$duplicate .= " ON DUPLICATE KEY UPDATE `$column` = VALUES(`$column`)";
		} else {
			$duplicate .= ", `$column` = VALUES(`$column`)";
		}
	}

	if (cacti_sizeof($queues)) {
		foreach($queues as $queue) {
			$records = db_fetch_assoc_prepared('SELECT ghp.clusterid, gqh.queue,
				COUNT(ghp.clusterid) AS totalHosts,
				SUM(IF(ghp.status NOT LIKE "U%", 1, 0)) AS okHosts,
				ROUND((SUM(ghp.runSlots) / SUM(ghp.maxSlots)) * 100, 2) AS slotUtil,
				ROUND(SUM(IF(ghp.status NOT LIKE "U%", ghp.cpuUtil*ghp.maxSlots, 0)) / SUM(IF(ghp.status NOT LIKE "U%", ghp.maxSlots, 0)), 2) AS cpuUtil,
				ROUND((SUM(memReserved)/SUM(totalMem)) * 100, 2) AS memEffUtil,
				ROUND((SUM(memUsed)/SUM(memReserved)) * 100, 2) AS memEffic,
				ROUND((SUM(cpuUsed) / SUM(runTime*runSlots)) * 100, 2) AS cpuEffic,
				ROUND((SUM(memUsed)/SUM(totalMem)) * 100, 2) AS memUtil,
				SUM(maxSlots) AS maxSlots,
				SUM(runSlots) AS runSlots,
				SUM(maxSlots)/SUM(maxCpus) AS oFactor,
				SUM(memFree) AS memFree,
				SUM(maxCpus) AS maxCpus,
				SUM(memReserved) AS memReserved,
				SUM(memUsed) AS memUsed,
				SUM(maxMem) AS maxMem,
				SUM(totalMem) AS totalMem,
				SUM(runTime) AS runTime, SUM(cpuUsed) AS cpuUsed
				FROM grid_hosts_perf_stats AS ghp
				INNER JOIN grid_queues_hosts AS gqh
				ON ghp.clusterid = gqh.clusterid
				AND ghp.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?
				GROUP BY gqh.clusterid, gqh.queue',
				array($clusterid, $queue));

			grid_pump_records($records, 'grid_queues_perf_stats', $format, false, $duplicate);
		}
	}

	db_execute_prepared('DELETE FROM grid_queues_perf_stats
		WHERE last_updated < ?
		AND clusterid = ?',
		array($last_updated, $clusterid));

	$end = microtime(true);

	cacti_log('Finished Host, Hostgroup, and Queue Performance Stats for ClusterID:' . $clusterid . ' in ' . number_format($end - $start, 2) . ' seconds', false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);
}

function grid_collect_shared_descriptions($clusterid) {
	global $config;

	$remote = db_fetch_cell_prepared('SELECT remote
		FROM grid_clusters as gc
		INNER JOIN grid_pollers AS gp
		ON gc.poller_id = gp.poller_id
		WHERE clusterid = ?',
		array($clusterid));

	// Get summary pending reasons
	if ($remote != 'on') {
		$lsinfo = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/lsinfo -w 2>&1");
		grid_process_lsinfo($clusterid, $lsinfo);
	}
}

function grid_process_lsinfo($clusterid, &$lsinfo) {
	if ($lsinfo != '') {
		$lines = explode("\n", $lsinfo);
		$in_resources = false;
		$numcols = 4;

		$sql_prefix = 'INSERT INTO grid_resource_descriptions (clusterid, resource_name, description) VALUES ';
		$sql_suffix = ' ON DUPLICATE KEY UPDATE description = VALUES(description), last_updated=NOW()';
		$sql_params = array();
		$sql = '';

		foreach($lines as $line) {
			$line = trim($line);

			if ($line == '') {
				continue;
			}

			if (substr($line, 0, 13) == 'RESOURCE_NAME') {
				$in_resources = true;

				$numcols = sizeof(preg_split('/[\s]+/', $line));
			} elseif (substr($line, 0, 10) == 'TYPE_NAME') {
				$in_resources = false;
			} elseif (substr($line, 0, 10) == 'MODEL_NAME') {
				$in_resources = false;
			} elseif ($in_resources) {
				$parts = preg_split('/[\s]+/', $line, $numcols);

				$sql .= ($sql != '' ? ', ':'') . '(?, ?, ?)';

				$sql_params[] = $clusterid;
				$sql_params[] = $parts[0];
				$sql_params[] = end($parts);
			}
		}

		if ($sql != '') {
			$last_updated = db_fetch_cell_prepared('SELECT MAX(last_updated)
				FROM grid_resource_descriptions
				WHERE clusterid = ?',
				array($clusterid));

			db_execute_prepared($sql_prefix . $sql . $sql_suffix, $sql_params);

			db_execute_prepared('DELETE FROM grid_resource_descriptions WHERE last_updated < ?', array($last_updated));
		}
	}
}

function grid_translate_reason($reason) {
	// Record the original reason
	$oreason = $reason;

	// Clean up nasty long pending reasons and case issues
	$reason = str_replace('Resource limit defined on', '', $reason);
	$reason = str_replace('Jobs requirements for resource reservation not satisfied', 'Job Reservation not satisfied', $reason);
	$reason = str_replace('(Resource:', '(Res:', $reason);
	$reason = str_replace('Limit Name:', 'Name:', $reason);
	$reason = str_replace('Limit Value:', 'Value:', $reason);
	$reason = str_replace('JOBS limit defined on host or host group', 'Host/Group Jobs', $reason);
	$reason = str_replace('User has reached the per-user job slot limit of the queue', 'Queue User per-Slot Limit Reached', $reason);
	$reason = str_replace('user or user group', 'User/Group', $reason);
	$reason = str_replace('host(s) and/or host group', 'Host/Group', $reason);
	$reason = str_replace('queue', 'Queue', $reason);
	$reason = str_replace('cluster', 'Cluster', $reason);
	$reason = str_replace("'", '', $reason);
	$reason = str_replace('has been reached', 'Limit Reached', $reason);
	$reason = str_replace('has reached', 'Reached', $reason);

	// Host Reasons are fist
	if (str_contains($reason, ' (Host:')) {
		$reason = db_fetch_cell("SELECT TRIM(SUBSTRING_INDEX(reason,' (Host:',1)) AS reason
			FROM (SELECT " . db_qstr($reason) . " AS reason HAVING reason LIKE '% (Host:%') AS rs");
	}

	// Job level Reasons
	if (str_contains($reason, 'job <')) {
		$reason = db_fetch_cell("SELECT TRIM(SUBSTRING_INDEX(reason,'<', 1)) AS reason,
			FROM (SELECT " . db_qstr($reason) . " AS reason HAVING reason LIKE '%job <%') AS rs");
	}


	// Limit Value
	if (str_contains($reason, ' (Limit Value:')) {
		$reason = db_fetch_cell("SELECT TRIM(SUBSTRING_INDEX(reason,' (Limit Value:', 1)) AS reason,
			FROM (SELECT " . db_qstr($reason) . " AS reason HAVING reason LIKE '% (Limit Value:%') AS rs");
	}

	// Remaining pending reasons
	if (str_contains($reason, ' (Limit Value:')) {
		$reason = db_fetch_cell("SELECT TRIM(REPLACE(REPLACE(reason, \"'\", \"\"), 'Limit: ', '')) AS reason,
			FROM (SELECT " . db_qstr($reason) . " AS reason
			HAVING reason NOT LIKE '% (Limit Value:%'
			AND reason NOT LIKE '% (Host:%'
			AND reason NOT LIKE '%job <%') AS rs");
	}

	$reason = str_replace(
		array(
			'job', 'dependency', 'condition', 'user', 'host', 'group', 'guarantee',
			'threshold', 'reached', 'slot', 'limit', 'reserved', 'honor', 'retry', 'start', 'time'
		),
		array(
			'Job', 'Dependency', 'Condition', 'User', 'Host', 'Group', 'Guarantee',
			'Threshold', 'Reached', 'Slot', 'Limit', 'Reserved', 'Honor', 'Retry', 'Start', 'Time'
		), $reason
	);

	// Debugging
	//cacti_log("O:'$oreason' T:'$reason'");

	return trim($reason);
}


function grid_process_reasons($clusterid, $reasons, $level = 'clusterid', $issusp = 0) {
	$reasons = explode("\n", $reasons);
	$records  = array();

	foreach($reasons as $r) {
		$r = trim($r);

		if ($r == '') {
			continue;
		}

		if (substr($r, -4) == 'jobs') {
			// Peel off the jobs
			$len = strlen($r);
			$r = substr($r, 0, $len - 5);

			// Get the job count
			$pieces = explode(' ', $r);
			$jobs   = end($pieces);

			// Reconstruct and trim the reason
			array_pop($pieces);
			$r = trim(implode(' ', $pieces));
			$r = trim(str_replace("'", "", $r));

			// Prep the record
			$records[] = sprintf('(%d, %d, %s, 0, %s, %d, 1)', $clusterid, $issusp, db_qstr($level), db_qstr($r), $jobs);
		} elseif (substr($r, -11) == 'occurrences') {
			// Peel off the occurrence
			$len = strlen($r);
			$r = substr($r, 0, $len - 12);

			// Get the occurrence count
			$pieces = explode(' ', $r);
			$occur  = end($pieces);

			// Reconstruct and trim the reason
			array_pop($pieces);
			$r = trim(implode(' ', $pieces));
			$r = trim($r);

			// Prep the record
			$records[] = sprintf('(%d, %d, %s, 1, %s, %d, 1)', $clusterid, $issusp, db_qstr($level), db_qstr($r), $occur);
		} elseif (strpos($r, 'Pending reason summary') !== false) {
			set_config_option('grid_pending_cluster_' . $clusterid, time());
		} elseif (substr($r, 0, 11) == 'Summarizing' && !$issusp) {
			$r = str_replace('Summarizing ', '', $r);
			$pieces = explode(' ', $r);
			$jobs = $pieces[0];
			set_config_option('grid_pending_jobs_' . $clusterid, $jobs);
		}
	}

	db_execute_prepared('UPDATE grid_jobs_reason_details
		SET present = 0
		WHERE clusterid = ?
		AND level = ?
		AND issusp = ?',
		array($clusterid, $level, $issusp));

	if (cacti_sizeof($records)) {
		db_execute('INSERT INTO grid_jobs_reason_details
			(clusterid, issusp, level, type, reason, jobs_occurrences, present) VALUES ' .
			implode(', ', $records) . '
			ON DUPLICATE KEY UPDATE jobs_occurrences=VALUES(jobs_occurrences), present=1');
	}

	db_execute_prepared('UPDATE grid_jobs_reason_details
		SET jobs_occurrences = 0, present = 1
		WHERE present = 0
		AND clusterid = ?
		AND level = ?
		AND issusp = ?',
		array($clusterid, $level, $issusp));
}

function grid_aggregate_reasons() {
	db_execute('UPDATE grid_jobs_reason_summary SET present = 0');

	$exclude_clusters = read_config_option('grid_reasons_exclude_clusters');

	if ($exclude_clusters == '') {
		$exclude_clusters = array(38, 120);
	} else {
		$exclude_clusters = explode(',', $exclude_clusters);
	}

	$sql_where = '';
	if (cacti_sizeof($exclude_clusters)) {
		foreach($exclude_clusters as $index => $cluster) {
			$cluster = trim($cluster);
			if (!is_numeric($cluster)) {
				unset($exclude_clusters[$index]);
			} else {
				$exclude_clusters[$index] = $cluster;
			}
		}

		$sql_where = ' WHERE clusterid NOT IN (' . implode(',', $exclude_clusters) . ')';
	}

	$reasons = db_fetch_assoc("SELECT * FROM grid_jobs_reason_details $sql_where");

	$num_reasons = cacti_sizeof($reasons);

	/* translate the reasons into something readable */
	if (cacti_sizeof($reasons)) {
		foreach($reasons as $index => $r) {
			$reasons[$index]['reason'] = grid_translate_reason($r['reason']);
		}
	}

	$format = array(
		'clusterid',
		'issusp',
		'level',
		'type',
		'reason',
		'jobs_occurrences',
		'present',
		'last_updated'
	);

	$duplicate = " ON DUPLICATE KEY UPDATE
		jobs_occurrences = VALUES(jobs_occurrences),
		last_updated = VALUES(last_updated),
		present = 1";

	grid_pump_records($reasons, 'grid_jobs_reason_summary', $format, false, $duplicate);

	db_execute('UPDATE grid_jobs_reason_summary SET jobs_occurrences = 0 WHERE present = 0');

	return $num_reasons;
}

function grid_collect_jobs_remote($clusterid) {
	global $config;

	// Get all the jobs and their key status metrics
	// Example output

	// "JOBID":"287679",
	// "JOBINDEX":"0",
	// "USER":"agoyal",
	// "PROJ_NAME":"afe2490",
	// "APPLICATION":"vrts",
	// "STAT":"RUN",
	// "QUEUE":"i_vrts_l",
	// "SERVICE_CLASS":"au_grp",
	// "FIRST_HOST":"sjc1upp-grid14",
	// "SLOTS":"1",
	// "CPU_USED":"1 second(s)",
	// "MAX_MEM":"1.8 Gbytes",
	// "MEM":"233 Mbytes",
	// "SUBMIT_TIME":"Feb 21 00:02 2019",
	// "START_TIME":"Feb 21 00:02 2019",
	// "FINISH_TIME":"",
	// "EFFECTIVE_RESREQ":"select[type == local] order[mem:ut] rusage[mem=8192.00] span[hosts=1] "

	$start = microtime(true);
	$time  = time();
	$total_jobs = 0;

	// Setup tables in case they are missing
	create_required_tables($clusterid);

	// Chunk size for inserts
	$chunk = 8000;

	if (!is_grid_process_running('0', 'OPTIMIZE')) {
		// Only update on a frequency
		$last_time = read_config_option('grid_jobs_summary_' . $clusterid, true);
		$upd_freq  = read_config_option('poller_interval', true);
		$now       = time();

		if (empty($last_time)) {
			set_config_option('grid_jobs_summary_' . $cluster, $now);
		}

		cacti_log('NOTE: Gathering jobs for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

		$sql = "SELECT clusterid AS CLUSTERID, '1' AS PRESENT, jobid AS JOBID, indexid AS JOBINDEX, user AS USER, projectName AS PROJ_NAME,
			app AS APPLICATION, IF(stat = 'RUNNING', 'RUN', stat) AS STAT, queue AS QUEUE, sla AS SERVICE_CLASS, exec_host AS FIRST_HOST, num_cpus AS SLOTS, cpu_used AS CPU_USED,
			mem_reserved AS MEM_RESERVED, mem_requested AS MEM_REQUESTED, max_memory AS MAX_MEM, mem_used AS MEM, run_time AS RUN_TIME,
			submit_time AS SUBMIT_TIME, start_time AS START_TIME, end_time AS FINISH_TIME, effectiveResreq AS EFFECTIVE_RESREQ, combinedResreq AS COMBINED_RESREQ
			FROM grid_jobs
			WHERE clusterid = $clusterid
			AND stat NOT IN ('DONE', 'EXIT')";

		$format = array(
			'CLUSTERID',
			'PRESENT',
			'JOBID',
			'JOBINDEX',
			'USER',
			'PROJ_NAME',
			'APPLICATION',
			'STAT',
			'QUEUE',
			'SERVICE_CLASS',
			'FIRST_HOST',
			'SLOTS',
			'CPU_USED',
			'MEM_RESERVED',
			'MEM_REQUESTED',
			'MAX_MEM',
			'MEM',
			'RUN_TIME',
			'SUBMIT_TIME',
			'START_TIME',
			'FINISH_TIME',
			'EFFECTIVE_RESREQ',
			'COMBINED_RESREQ'
		);

		db_execute_prepared('UPDATE grid_jobs_summary
			SET PRESENT = 0
			WHERE CLUSTERID = ?',
			array($clusterid));

		grid_pump_infile('grid_jobs_summary', $sql, array(), $format, false);

		db_execute_prepared('DELETE FROM grid_jobs_summary
			WHERE PRESENT = 0
			AND CLUSTERID = ?',
			array($clusterid));

		$total_jobs = db_fetch_cell_prepared('SELECT COUNT(*)
			FROM grid_jobs_summary
			WHERE clusterid = ?',
			array($clusterid));

		grid_debug('Finished inserting Job records for ClusterID:' . $clusterid);

		// Collect pending reason data this is commented out for now
		grid_collect_pend($clusterid);

		// Pool, SLA and Done Job Statistics

		// Prepare to remove some records
		db_execute_prepared('UPDATE grid_sla_stats SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_pool_memory SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_pool_host_lending SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_hostgroups_definitions SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_job_groups SET present = 0 WHERE clusterid = ?', array($clusterid));

		grid_update_pool_stats($clusterid);

		grid_update_djob_stats($clusterid, $time);

		grid_hostgroup_sla_definitions($clusterid);

		grid_update_job_groups($clusterid);

		// Remove Stale records
		db_execute_prepared('DELETE FROM grid_sla_stats WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_pool_memory WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_pool_host_lending WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_hostgroups_definitions WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_job_groups WHERE present = 0 AND clusterid = ?', array($clusterid));
	}

	$end = microtime(true);

	return array($end - $start, $total_jobs);
}

function grid_collect_jobs($clusterid) {
	global $config;

	// Get all the jobs and their key status metrics
	// Example output

	// "JOBID":"287679",
	// "JOBINDEX":"0",
	// "USER":"agoyal",
	// "PROJ_NAME":"afe2490",
	// "APPLICATION":"vrts",
	// "STAT":"RUN",
	// "QUEUE":"i_vrts_l",
	// "SERVICE_CLASS":"au_grp",
	// "FIRST_HOST":"sjc1upp-grid14",
	// "SLOTS":"1",
	// "CPU_USED":"1 second(s)",
	// "MAX_MEM":"1.8 Gbytes",
	// "MEM":"233 Mbytes",
	// "SUBMIT_TIME":"Feb 21 00:02 2019",
	// "START_TIME":"Feb 21 00:02 2019",
	// "FINISH_TIME":"",
	// "EFFECTIVE_RESREQ":"select[type == local] order[mem:ut] rusage[mem=8192.00] span[hosts=1] "

	$start = microtime(true);
	$time  = time();
	$total_jobs = 0;

	// Setup tables in case they are missing
	create_required_tables($clusterid);

	// Chunk size for inserts
	$chunk = 8000;

	// Only update on a frequency
	$last_time = read_config_option('grid_jobs_summary_' . $clusterid, true);
	$upd_freq  = read_config_option('poller_interval', true);
	$now       = time();

	if (empty($last_time)) {
		set_config_option('grid_jobs_summary_' . $cluster, $now);
	}

	cacti_log('NOTE: Bjobs command starting for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$jobs = shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bjobs -uall -o 'jobid jobindex user project app stat queue sla:60 first_host slots min_req_proc max_req_proc cpu_used max_mem mem run_time submit_time start_time finish_time effective_resreq combined_resreq' -json 2>/dev/null");

	if ($jobs != '') {
		$jobs = json_decode($jobs, true);
	} else {
		cacti_log("WARNING: Job information was not returned from Cluster $clusterid", false, 'LSFENH');
		return false;
	}

	$bjend = microtime(true);

	cacti_log("NOTE: Bjobs command completed for ClusterID:$clusterid in " . number_format($bjend - $start, 2) . " seconds", false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	// Collect pending reason data
	grid_collect_pend($clusterid);

	// Get shared resource descriptions
	grid_collect_shared_descriptions($clusterid);

	cacti_log('Starting to parse Job records for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	if (cacti_sizeof($jobs['RECORDS'])) {
		$prefix = 'INSERT INTO grid_jobs_summary (`CLUSTERID`, `PRESENT`, `JOBID`, `JOBINDEX`, `USER`, `PROJ_NAME`, `APPLICATION`, `STAT`, `QUEUE`, `SERVICE_CLASS`, `FIRST_HOST`, `SLOTS`, `CPU_USED`, `MEM_RESERVED`, `MEM_REQUESTED`, `MAX_MEM`, `MEM`, `RUN_TIME`, `SUBMIT_TIME`, `START_TIME`, `FINISH_TIME`, `EFFECTIVE_RESREQ`, `COMBINED_RESREQ`) VALUES ';

		$suffix = ' ON DUPLICATE KEY UPDATE PRESENT=1, QUEUE=VALUES(QUEUE), STAT=VALUES(STAT), SERVICE_CLASS=VALUES(SERVICE_CLASS), FIRST_HOST=VALUES(FIRST_HOST), SLOTS=VALUES(SLOTS), CPU_USED=VALUES(CPU_USED), MEM_RESERVED=VALUES(MEM_RESERVED), MEM_REQUESTED=VALUES(MEM_REQUESTED), MAX_MEM=VALUES(MAX_MEM), MEM=VALUES(MEM), RUN_TIME=VALUES(RUN_TIME), START_TIME=VALUES(START_TIME), FINISH_TIME=VALUES(FINISH_TIME), EFFECTIVE_RESREQ=VALUES(EFFECTIVE_RESREQ), COMBINED_RESREQ=VALUES(COMBINED_RESREQ)';

		foreach($jobs['RECORDS'] as $j) {
			// Accomodate pending jobs
			$slots = 1;

			foreach($j as $column => $data) {
				switch($column) {
					case 'JOBID':            //'1234'
						$jobid = $data;
						break;
					case 'JOBINDEX':         //'0'
						$jobindex = $data;
						break;
					case 'USER':             //'agoyal'
						$user = db_qstr($data);
						break;
					case 'PROJ_NAME':        //'afe2490'
						$proj_name = db_qstr($data);
						break;
					case 'APPLICATION':      //'vrts'
						$app = db_qstr($data);
						break;
					case 'STAT':             //'RUN'
						// Correct emulation difference
						if ($data == 'RUNNING') {
							$data = 'RUN';
						}

						$stat = db_qstr($data);

						break;
					case 'QUEUE':            //'i_vrts_l'
						$queue = db_qstr($data);
						break;
					case 'SERVICE_CLASS':    //'au_grp'
						$sla = db_qstr($data);
						break;
					case 'FIRST_HOST':       //'sjc1upp-grid14'
						$host = db_qstr($data);
						break;
					case 'SLOTS':            //'1',
						if (empty($data) || $data == '-') {
							$slots = 1;
						} else {
							$slots = db_qstr($data);
						}
						break;
					case 'MIN_REQ_PROC':            //'1',
						if (empty($data) || $data == '-') {
							$slots = 1;
						} elseif ($data > $slots) {
							$slots = db_qstr($data);
						}
						break;
					case 'MAX_REQ_PROC':            //'1',
						if (empty($data) || $data == '-') {
							$slots = 1;
						} elseif ($data > $slots) {
							$slots = db_qstr($data);
						}
						break;
					case 'CPU_USED':       //'-' or XXX second(s)
						if (empty($data) || $data == '-') {
							$cpu_used = 0;
						} else {
							$parts = preg_split('/[\s]+/', $data);
							$cpu_used = $parts[0];
						}
						break;
					case 'MAX_MEM':          //'1.8 Gbytes'
						$maxmem = getMemory($data);
						break;
					case 'MEM':              //'233 Mbytes'
						$mem = getMemory($data);
						break;
					case 'RUN_TIME':              //'233 Mbytes'
						if ($data != '-') {
							$run_time = trim(str_replace('second(s)', '', $data));
						} else {
							$run_time = 0;
						}
						break;
					case 'SUBMIT_TIME':      //'Feb 21 00:02 2019'
						$submit_time = db_qstr(date('Y-m-d H:i:s', strtotime($data)));
						break;
					case 'START_TIME':       //'Feb 21 00:02 2019'
						if ($data != '-') {
							$start_time = db_qstr(date('Y-m-d H:i:s', strtotime($data)));
						} else {
							$start_time = db_qstr('0000-00-00');
						}
						break;
					case 'FINISH_TIME':      //''
						if ($data != '-') {
							$finish_time = db_qstr(date('Y-m-d H:i:s', strtotime($data)));
						} else {
							$finish_time = db_qstr('0000-00-00');
						}
						break;
					case 'EFFECTIVE_RESREQ': //'select[type == local] order[mem:ut] rusage[mem=8192.00] span[hosts=1]'
						if (strpos($stat, 'PEND') === false && strpos($stat, 'PSUSP') === false) {
							$effective_resreq = db_qstr($data);
							$mem_reserved     = getReserved($data);
							$mem_requested    = getRequested($data);
						} else {
							$effective_resreq = db_qstr('');
						}
						break;
					case 'COMBINED_RESREQ': //'select[type == local] order[mem:ut] rusage[mem=8192.00] span[hosts=1]'
						if (strpos($stat, 'PEND') !== false || strpos($stat, 'PSUSP') !== false) {
							$combined_resreq = db_qstr($data);
							$mem_reserved     = getReserved($data);
							$mem_requested    = getRequested($data);
						} else {
							$combined_resreq = db_qstr('');
						}
						break;
				}
			}

			$sql[] = '(' .
				$clusterid        . ', 1, ' .
				$jobid            . ', ' .
				$jobindex         . ', ' .
				$user             . ', ' .
				$proj_name        . ', ' .
				$app              . ', ' .
				$stat             . ', ' .
				$queue            . ', ' .
				$sla              . ', ' .
				$host             . ', ' .
				$slots            . ', ' .
				$cpu_used         . ', ' .
				$mem_reserved     . ', ' .
				$mem_requested    . ', ' .
				$maxmem           . ', ' .
				$mem              . ', ' .
				$run_time         . ', ' .
				$submit_time      . ', ' .
				$start_time       . ', ' .
				$finish_time      . ', ' .
				$effective_resreq . ', ' .
				$combined_resreq  . ')';
		}

		grid_debug('Finished to parse Job records for ClusterID:' . $clusterid);

		if (cacti_sizeof($sql)) {
			grid_debug('Started to insert Job records for ClusterID:' . $clusterid);

			$total_jobs = cacti_sizeof($sql);

			db_execute_prepared('UPDATE grid_jobs_summary
				SET PRESENT = 0
				WHERE CLUSTERID = ?',
				array($clusterid));

			$parts = array_chunk($sql, $chunk);

			foreach($parts as $p) {
				grid_debug('Insert length is ' . number_format(strlen($prefix . implode(', ', $p) . $suffix)) . ' for ClusterID:' . $clusterid);
				db_execute($prefix . implode(', ', $p) . $suffix);
			}

			db_execute_prepared('DELETE FROM grid_jobs_summary
				WHERE PRESENT = 0
				AND CLUSTERID = ?',
				array($clusterid));

			grid_debug('Finished inserting Job records for ClusterID:' . $clusterid);
		}
	} else {
		db_execute_prepared('DELETE FROM grid_jobs_summary
			WHERE CLUSTERID = ?',
			array($clusterid));
	}

	// Get host performance metrics for reporting
	grid_update_host_perf_stats($clusterid);

	if (!is_grid_process_running('0', 'OPTIMIZE')) {
		// Pool, SLA and Done Job Statistics

		// Prepare to remove some records
		db_execute_prepared('UPDATE grid_sla_stats SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_pool_memory SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_pool_host_lending SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_hostgroups_definitions SET present = 0 WHERE clusterid = ?', array($clusterid));
		db_execute_prepared('UPDATE grid_job_groups SET present = 0 WHERE clusterid = ?', array($clusterid));

		grid_update_pool_stats($clusterid);

		grid_update_djob_stats($clusterid, $time);

		$date = date('Y-m-d H:i:s');

		// Set max memory column since RTM does not
		grid_set_job_maxmem($clusterid);

		grid_update_sla_finished_memory_buckets($clusterid, $date);

		grid_update_sla_pending_running_memory_buckets($clusterid, $date);

		grid_hostgroup_sla_definitions($clusterid);

		grid_update_job_groups($clusterid);

		// Remove Stale records
		db_execute_prepared('DELETE FROM grid_sla_stats WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_pool_memory WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_pool_host_lending WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_hostgroups_definitions WHERE present = 0 AND clusterid = ?', array($clusterid));
		db_execute_prepared('DELETE FROM grid_job_groups WHERE present = 0 AND clusterid = ?', array($clusterid));
	}

	$end = microtime(true);

	return array($end - $start, $total_jobs);
}

function grid_planner_jobs($clusterid) {
	global $config;

	$jobs = file(shell_exec($config['base_path'] . "/plugins/lsfenh/bin/bjobs -uall -UF -plan 2>/dev/null"));
}

function grid_update_djob_stats($clusterid, $time) {
	$start = microtime(true);

	cacti_log('Starting Done Job Stats for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$delay = read_config_option('grid_djob_delay');

	$end_time = date('Y-m-d H:i:s', $time - $delay);
	$start    = time();

	$format = array('clusterid', 'host', 'mem_reserved', 'mem_used', 'runJobs', 'maxJobs', 'maxMemory');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		mem_reserved = IF(VALUES(maxMemory) < VALUES(mem_reserved) AND VALUES(maxMemory) > 0, VALUES(maxMemory), VALUES(mem_reserved)),
		mem_used = VALUES(mem_used),
		runJobs = VALUES(runJobs),
		maxJobs = VALUES(maxJobs),
		maxMemory = IF(VALUES(maxMemory) > 0, VALUES(maxMemory), maxMemory)';

	$records = db_fetch_assoc_prepared('SELECT gj.clusterid,
		gj.exec_host AS host,
		SUM(mem_reserved) AS mem_reserved,
		SUM(mem_used) AS mem_used,
		COUNT(*) AS runJobs,
		IF(maxJobs="-", maxCpus, maxJobs) AS maxJobs,
		ghi.maxMem * 1024 AS maxMemory
		FROM grid_jobs AS gj
		INNER JOIN grid_hostinfo AS ghi
		ON ghi.clusterid = gj.clusterid
		AND ghi.host = gj.exec_host
		INNER JOIN grid_hosts AS gh
		ON gh.clusterid = gj.clusterid
		AND gh.host = gj.exec_host
		WHERE gj.clusterid = ?
		AND gj.exec_host != ""
		AND gj.start_time > "0000-00-00"
		AND gj.start_time <= ?
		AND (gj.end_time >= ? OR gj.end_time = "0000-00-00")
		GROUP BY gj.exec_host',
		array($clusterid, $end_time, $end_time));

	grid_pump_records($records, 'grid_djob_hstats', $format, false, $duplicate);

	$now         = date('Y-m-d H:i:s', time() - 30);
	$hourly_time = date('Y-m-d H:i:s', time() - 3600);

	// Set values to 0 for hosts without jobs
	db_execute_prepared('UPDATE grid_djob_hstats
		SET mem_reserved = 0,
		mem_used = 0,
		runJobs = 0
		WHERE clusterid = ?
		AND last_updated < ?',
		array($clusterid, $now));

	// Purge delete hosts
	db_execute_prepared('DELETE adjh
		FROM grid_djob_hstats AS adjh
		LEFT JOIN grid_hosts AS gh
		ON gh.clusterid = adjh.clusterid
		AND gh.host = adjh.host
		WHERE adjh.clusterid = ?
		AND gh.host IS NULL
		AND last_updated < ?',
		array($clusterid, $now));

	// Stats by SLA
	$format = array('clusterid', 'sla', 'dmem_reserved', 'dmem_used', 'djob_efficiency', 'djob_cputime', 'djob_walltime', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		dmem_reserved = VALUES(dmem_reserved),
		dmem_used = VALUES(dmem_used),
		djob_efficiency = VALUES(djob_efficiency),
		djob_cputime = VALUES(djob_cputime),
		djob_walltime = VALUES(djob_walltime),
		present = VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT gj.clusterid,
		IF(gj.sla="", "none", gj.sla) AS sla,
		SUM(mem_reserved) AS dmem_reserved,
		SUM(mem_used) AS dmem_used,
		(SUM(cpu_used)/SUM(num_cpus*run_time)) * 100 AS djob_efficiency,
		SUM(cpu_used) AS djob_cputime,
		SUM(num_cpus*run_time) AS djob_walltime,
		"1" AS present
		FROM grid_jobs AS gj
		WHERE gj.clusterid = ?
		AND gj.exec_host != ""
		AND gj.start_time <= ?
		AND (gj.end_time >= ? OR gj.end_time = "0000-00-00")
		GROUP BY gj.sla',
		array($clusterid, $end_time, $end_time));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// SLA Throughput
	$format = array('clusterid', 'sla', 'hourly_started_jobs', 'hourly_done_jobs', 'hourly_exit_jobs', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		hourly_done_jobs = VALUES(hourly_done_jobs),
		hourly_exit_jobs = VALUES(hourly_exit_jobs),
		hourly_started_jobs = VALUES(hourly_started_jobs),
		present = VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT gj.clusterid,
		IF(gj.sla="", "none", gj.sla) AS sla,
		SUM(CASE WHEN start_time >= ? THEN 1 ELSE 0 END) AS hourly_started_jobs,
		SUM(CASE WHEN stat = "DONE" AND end_time > "0000-00-00" THEN 1 ELSE 0 END) AS hourly_done_jobs,
		SUM(CASE WHEN stat = "EXIT" AND end_time > "0000-00-00" THEN 1 ELSE 0 END) AS hourly_exit_jobs,
		"1" AS present
		FROM grid_jobs AS gj
		WHERE gj.clusterid = ?
		AND gj.exec_host != ""
		AND (gj.end_time >= ? OR gj.end_time = "0000-00-00" OR gj.start_time >= ?)
		GROUP BY gj.sla',
		array($hourly_time, $clusterid, $hourly_time, $hourly_time));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Host Loaning Phase 1
	$format = array('clusterid', 'sla', 'hostl_hosts_total', 'hostl_hosts_busy', 'hostl_slots_total', 'hostl_slots_used', 'hostl_slots_busy', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		hostl_hosts_total = VALUES(hostl_hosts_total),
		hostl_hosts_busy  = VALUES(hostl_hosts_busy),
		hostl_slots_total = VALUES(hostl_slots_total),
		hostl_slots_used  = VALUES(hostl_slots_used),
		hostl_slots_busy  = VALUES(hostl_slots_busy),
		present = VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT ss.clusterid, ss.sla,
		COUNT(DISTINCT gph.host) AS hostl_hosts_total,
		SUM(CASE WHEN gh.status = "Closed-Busy" THEN 1 ELSE 0 END) AS hostl_hosts_busy,
		SUM(CASE WHEN gh.maxJobs != "-" THEN gh.maxJobs ELSE ghi.maxCpus END) AS hostl_slots_total,
		SUM(gh.numRun) AS hostl_slots_used,
		SUM(CASE WHEN gh.status = "Closed-Busy" THEN IF(gh.maxJobs != "-", gh.maxJobs - gh.numRun, ghi.maxCpus - gh.numRun) ELSE 0 END) AS hostl_slots_busy, "1" AS present
		FROM grid_sla_stats AS ss
		INNER JOIN grid_guarantee_pool_hosts AS gph
		ON ss.clusterid = gph.clusterid
		AND ss.sla = gph.owner
		INNER JOIN grid_hosts AS gh
		ON gph.clusterid = gh.clusterid
		AND gph.host = gh.host
		INNER JOIN grid_hostinfo AS ghi
		ON ghi.clusterid = gh.clusterid
		AND ghi.host = gh.host
		WHERE ss.clusterid = ?
		GROUP BY ss.clusterid, ss.sla',
		array($clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Host Loaning Phase 2
	$format = array('clusterid', 'sla', 'hostl_hosts_shared', 'hostl_slots_shared', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		hostl_hosts_shared = VALUES(hostl_hosts_shared),
		hostl_slots_shared = VALUES(hostl_slots_shared),
		present = VALUES(present)';

	if (!db_index_exists('grid_jobs', 'clusterid_stat')) {
		db_execute('ALTER TABLE `grid_jobs` ADD INDEX clusterid_stat (`clusterid`, `stat`)');
	}

	$records = db_fetch_assoc_prepared('SELECT ss.clusterid,
		ss.sla,
		COUNT(DISTINCT gj.exec_host) AS hostl_hosts_shared,
		SUM(CASE WHEN gj.sla != ss.sla THEN num_cpus ELSE 0 END) AS hostl_slots_shared,
		"1" AS present
		FROM grid_sla_stats AS ss
		INNER JOIN grid_guarantee_pool_hosts AS gph
		ON ss.clusterid = gph.clusterid
		AND ss.sla = gph.owner
		INNER JOIN (
			SELECT clusterid, sla, exec_host, SUM(num_cpus) AS num_cpus
			FROM grid_jobs AS gj FORCE INDEX (clusterid_stat)
			WHERE stat = "RUNNING"
			AND clusterid = ?
			GROUP BY clusterid, sla, exec_host
		) AS gj
		ON gj.clusterid = gph.clusterid
		AND gj.exec_host = gph.host
		WHERE ss.clusterid = ?
		GROUP BY ss.clusterid, ss.sla',
		array($clusterid, $clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Host Loaning Phase 3
	$format = array('clusterid', 'sla', 'hostl_memSlotUtil', 'hostl_slotUtil', 'hostl_cpuUtil', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		hostl_memSlotUtil = VALUES(hostl_memSlotUtil),
		hostl_slotUtil = VALUES(hostl_slotUtil),
		hostl_cpuUtil = VALUES(hostl_cpuUtil),
		present = VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT clusterid, sla,
		SUM(memSlotUtil*totalSlots)/SUM(totalSlots) AS hostl_memSlotUtil,
		SUM(slotUtil*totalSlots)/SUM(totalSlots) AS hostl_slotUtil,
		SUM(cpuUtil*totalSlots)/SUM(totalSlots) AS hostl_cpuUtil,
		"1" AS present
		FROM (
			SELECT clusterid, sla, host, memSlotUtil, cpuUtil, slotUtil, totalSlots
			FROM (
				SELECT *, (totalSlots-(FLOOR(totalSlots*freeMem/maxMem)))/totalSlots*100 AS memSlotUtil,
				ROUND((numRun/totalSlots)*100,1) AS slotUtil
				FROM (
					SELECT gph.clusterid, gph.owner AS sla, ghr.host, numRun, ROUND(IF(ut>0, ut*100,0),1) AS cpuUtil,
					ROUND(totalValue,0) AS freeMem, ROUND(reservedValue,0) AS reservedMem,
					GREATEST(maxJobs, maxCpus) AS totalSlots, maxMem
					FROM grid_hosts_resources AS ghr
					INNER JOIN grid_hostinfo AS ghi
					ON ghi.host=ghr.host
					AND ghi.clusterid=ghr.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host=ghr.host
					AND gh.clusterid=ghr.clusterid
					AND gh.status NOT IN ("Unavail", "Unreach", "Closed-Admin", "Closed-LIM")
					INNER JOIN grid_load AS gl
					ON gl.host=ghr.host
					AND gl.clusterid=ghr.clusterid
					INNER JOIN grid_guarantee_pool_hosts AS gph
					ON ghr.host=gph.host
					AND ghr.clusterid=gph.clusterid
					WHERE resource_name="mem"
					AND gh.clusterid = ?
					AND gh.numRun > 0
				) AS results
			) AS results2
		) AS results3
		GROUP BY clusterid, sla',
		array($clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Host Loaning Phase 4
	$format = array('clusterid', 'sla', 'hostl_memUsed', 'hostl_memReserved', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		hostl_memUsed = VALUES(hostl_memUsed),
		hostl_memReserved = VALUES(hostl_memReserved),
		present = VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT ss.clusterid, ss.sla,
		IFNULL(SUM(gj.mem_used)/SUM(gj.maxMem)*100,0) AS hostl_memUsed,
		IFNULL(SUM(gj.mem_reserved)/SUM(gj.maxMem)*100,0) AS hostl_memReserved,
		"1" AS present
		FROM grid_sla_stats AS ss
		INNER JOIN grid_guarantee_pool_hosts AS gph
		ON ss.clusterid=gph.clusterid
		AND ss.sla=gph.owner
		LEFT JOIN (
			SELECT gj.clusterid, gj.exec_host,
			SUM(mem_used) AS mem_used, SUM(mem_reserved) AS mem_reserved, ghi.maxMem * 1024 AS maxMem
			FROM grid_jobs AS gj FORCE INDEX (clusterid_stat)
			INNER JOIN grid_hostinfo AS ghi
			ON gj.clusterid = ghi.clusterid
			AND gj.exec_host = ghi.host
			WHERE stat = "RUNNING"
			AND gj.clusterid = ?
			AND ghi.maxMem != "-"
			GROUP BY gj.clusterid, gj.exec_host
		) AS gj
		ON gj.clusterid = gph.clusterid
		AND gj.exec_host = gph.host
		WHERE ss.clusterid = ?
		GROUP BY ss.clusterid, ss.sla',
		array($clusterid, $clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	$end = microtime(true);

	grid_debug('Finished Done Job Stats for ClusterID:' . $clusterid . ' in ' . number_format($end - $start, 2)  . ' seconds');
}

function grid_set_job_maxmem($clusterid) {
	if (!db_column_exists('grid_jobs', 'max_mem_requested')) {
		db_execute('ALTER TABLE grid_jobs ADD COLUMN max_mem_requested DOUBLE NOT NULL default 0 AFTER mem_requested');
	}

	if (!db_column_exists('grid_jobs_finished', 'max_mem_requested')) {
		db_execute('ALTER TABLE grid_jobs_finished ADD COLUMN max_mem_requested DOUBLE NOT NULL default 0 AFTER mem_requested');
	}

	$jobs = db_fetch_assoc_prepared("SELECT clusterid, jobid, indexid, submit_time, effectiveResreq
		FROM grid_jobs
		WHERE clusterid = ?
		AND effectiveResreq LIKE '%maxmem%order[%'
		AND max_mem_requested = 0",
		array($clusterid));

	$i = 0;
	if (cacti_sizeof($jobs)) {
		foreach($jobs as $job) {
			$resreq = explode('order', $job['effectiveResreq']);
			$select = $resreq[0];
			if (strpos($select, 'maxmem')) {
				// Extract the maxmem
				$parts   = explode('maxmem', $select);
				$mempart = trim($parts[1], '>= ');
				$mempart = str_replace(array(')', '&', ']'), ' ', $mempart);
				$parts   = explode(' ', $mempart);

				// Convert to KBytes
				if (is_numeric($parts[0])) {
					$maxmem = $parts[0] * 1024;

					db_execute_prepared("UPDATE IGNORE grid_jobs
						SET max_mem_requested = ?
						WHERE clusterid = ?
						AND jobid = ?
						AND indexid = ?
						AND submit_time = ?",
						array($maxmem, $job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

					$i++;
				} else {
					cacti_log(sprintf("The unprocessed mempart was '%s' for '%s'", $parts[0], $select), false, 'LSFENH');
				}
			}
		}
	}
}

function grid_update_sla_pending_running_memory_buckets($clusterid, $date) {
	$sql_where  = "AND clusterid = $clusterid";

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET present = 0
		WHERE clusterid = ?',
		array($clusterid));

	$data = db_fetch_assoc('SELECT clusterid, sla,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 8G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 8G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 8G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 8G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 8G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "PEND" THEN 1 ELSE 0 END) AS 8G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 8G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 8G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 16G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 16G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 16G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 16G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 16G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "PEND" THEN 1 ELSE 0 END) AS 16G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 16G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 16G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 32G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 32G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 32G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 32G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 32G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "PEND" THEN 1 ELSE 0 END) AS 32G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 32G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 32G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 64G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 64G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 64G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 64G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 64G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "PEND" THEN 1 ELSE 0 END) AS 64G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 64G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 64G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 128G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 128G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 128G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 128G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 128G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "PEND" THEN 1 ELSE 0 END) AS 128G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 128G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 128G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 192G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 192G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 192G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 192G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 192G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "PEND" THEN 1 ELSE 0 END) AS 192G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 192G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 192G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 256G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 256G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 256G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 256G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 256G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "PEND" THEN 1 ELSE 0 END) AS 256G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 256G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 256G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 384G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 384G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 384G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 384G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 384G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "PEND" THEN 1 ELSE 0 END) AS 384G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 384G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 384G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 512G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 512G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 512G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 512G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 512G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "PEND" THEN 1 ELSE 0 END) AS 512G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 512G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 512G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 768G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 768G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 768G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 768G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 768G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "PEND" THEN 1 ELSE 0 END) AS 768G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 768G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 768G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1024G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 1024G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1024G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 1024G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 1024G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "PEND" THEN 1 ELSE 0 END) AS 1024G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 1024G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 1024G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1536G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 1536G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1536G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 1536G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 1536G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "PEND" THEN 1 ELSE 0 END) AS 1536G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 1536G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 1536G_runArrays,

		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 2048G_prequested,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS 2048G_preserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 2048G_rrequested,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS 2048G_rreserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS 2048G_rmax,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "PEND" THEN 1 ELSE 0 END) AS 2048G_pendJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS 2048G_runJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS 2048G_runArrays,

		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "PEND" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS MAXG_prequested,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "PEND" THEN mem_reserved ELSE 0 END) AS MAXG_preserved,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "RUNNING" THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS MAXG_rrequested,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "RUNNING" THEN mem_reserved ELSE 0 END) AS MAXG_rreserved,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "RUNNING" THEN max_memory ELSE 0 END) AS MAXG_rmax,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "PEND" THEN 1 ELSE 0 END) AS MAXG_pendJobs,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "RUNNING" THEN 1 ELSE 0 END) AS MAXG_runJobs,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "RUNNING" AND indexid > 0 THEN 1 ELSE 0 END) AS MAXG_runArrays
		FROM grid_jobs
		WHERE stat IN ("RUNNING", "PEND")' .
		$sql_where . '
		GROUP BY sla');

	$column_prefixes = array(
		'8G_',
		'16G_',
		'32G_',
		'64G_',
		'128G_',
		'192G_',
		'256G_',
		'384G_',
		'512G_',
		'768G_',
		'1024G_',
		'1536G_',
		'2048G_',
		'MAXG_'
	);

	if (cacti_sizeof($data)) {
		foreach($data as $r) {
			if ($r['sla'] == '') {
				$r['sla'] = '-';
			}

			foreach($column_prefixes as $prefix) {
				$bucket    = trim($prefix, '_');

				$preserved  = $r[$prefix . 'preserved'];
				$prequested = $r[$prefix . 'prequested'];

				$rrequested = $r[$prefix . 'rrequested'];
				$rreserved  = $r[$prefix . 'rreserved'];
				$rmax       = $r[$prefix . 'rmax'];

				$runJobs   = $r[$prefix . 'runJobs'];
				$runArrays = $r[$prefix . 'runArrays'];
				$pendJobs  = $r[$prefix . 'pendJobs'];

				db_execute_prepared('INSERT INTO grid_sla_memory_buckets
					(clusterid, sla, memory_size, pmem_requested, pmem_reserved, rmem_requested, rmem_reserved, rmax_memory, pendJobs, runJobs, runArrays, present, last_updated)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE
						pmem_requested = VALUES(pmem_requested),
						pmem_reserved = VALUES(pmem_reserved),
						rmem_requested = VALUES(rmem_requested),
						rmem_reserved = VALUES(rmem_reserved),
						rmax_memory = VALUES(rmax_memory),
						pendJobs = VALUES(pendJobs),
						runJobs = VALUES(runJobs),
						runArrays = VALUES(runArrays),
						present = VALUES(present),
						last_updated = VALUES(last_updated)',
					array(
						$clusterid,
						$r['sla'],
						$bucket,
						$prequested,
						$preserved,
						$rrequested,
						$rreserved,
						$rmax,
						$pendJobs,
						$runJobs,
						$runArrays,
						1,
						$date
					)
				);
			}
		}
	}

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET rmem_efficiency = (rmax_memory / rmem_reserved) * 100
		WHERE rmem_reserved > 0
		AND clusterid = ?',
		array($clusterid));

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET rmem_efficiency = 0
		WHERE rmem_reserved = 0
		AND clusterid = ?',
		array($clusterid));

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET pendJobs = 0,
			runJobs = 0,
			runArrays = 0,
			runArrayStd = 0,
			pmem_requested = 0,
			pmem_reserved = 0,
			rmem_requested = 0,
			rmem_reserved = 0,
			max_memory = 0
		WHERE present = 0
		AND clusterid = ?',
		array($clusterid));
}

function grid_sla_bucket_range_sql($start_time, $end_time, $tables) {
	$ranges = array(
		'8G' => array(
			'lowMem'   => 0,
			'highMem'  => 8388608
		),
		'16G' => array(
			'lowMem'   => 8388608,
			'highMem'  => 16777216
		),
		'32G' => array(
			'lowMem'   => 16777216,
			'highMem'  => 33554432
		),
		'64G' => array(
			'lowMem'   => 33554432,
			'highMem'  => 67108864
		),
		'128G' => array(
			'lowMem'   => 67108864,
			'highMem'  => 134217728
		),
		'192G' => array(
			'lowMem'   => 134217728,
			'highMem'  => 201326592
		),
		'256G' => array(
			'lowMem'   => 201326592,
			'highMem'  => 268435456
		),
		'384G' => array(
			'lowMem'   => 268435456,
			'highMem'  => 402653184
		),
		'512G' => array(
			'lowMem'   => 402653184,
			'highMem'  => 536870912
		),
		'768G' => array(
			'lowMem'   => 536870912,
			'highMem'  => 805306368
		),
		'1024G' => array(
			'lowMem'   => 805306368,
			'highMem'  => 1073741824
		),
		'1536G' => array(
			'lowMem'   => 1073741824,
			'highMem'  => 1610612736
		),
		'2048G' => array(
			'lowMem'   => 1610612736,
			'highMem'  => 2147483648
		),
		'MAXG' => array(
			'lowMem'   => 2147483648,
			'highMem'  => -1
		)
	);

	$queries = array();

//	$sql = "SELECT clusterid, sla, time_range, ";

//	foreach($ranges as $prefix => $data) {
//		$sql .= " SUM({$prefix}_requested) AS {$prefix}_requested,";
//		$sql .= " SUM({$prefix}_reserved) AS {$prefix}_reserved,";
//		$sql .= " SUM({$prefix}_max) AS {$prefix}_max,";
//		$sql .= " SUM({$prefix}_doneJobs) AS {$prefix}_doneJobs,";
//		$sql .= " SUM({$prefix}_exitJobs) AS {$prefix}_exitJobs,";
//		$sql .= " SUM({$prefix}_doneArrays) AS {$prefix}_doneArrays,";
//		$sql .= " SUM({$prefix}_exitArrays) AS {$prefix}_exitArrays,";
//	}

//	$sql  = trim($sql, ',');
//	$sql .= PHP_EOL . " FROM (";

	foreach($tables as $index => $table) {
//		if ($index > 0) {
//			$sql .= PHP_EOL . ' UNION' . PHP_EOL;
//		}

		$sql = "SELECT clusterid, sla, '$table' AS table_name, CONCAT(YEAR(end_time), SUBSTRING(CONCAT('00', DAYOFYEAR(end_time)), -3)) AS year_day,";

		foreach($ranges as $prefix => $data) {
			$lowMem   = $data['lowMem'];
			$highMem  = $data['highMem'];

			if ($highMem > 0) {
				$range_sql = "BETWEEN $lowMem AND $highMem";
			} else {
				$range_sql = " >= $lowMem";
			}

			$sql .= " SUM(CASE WHEN mem_reserved $range_sql THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END ) AS {$prefix}_requested,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql THEN mem_reserved ELSE 0 END) AS {$prefix}_reserved,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql THEN max_memory ELSE 0 END) AS {$prefix}_max,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql AND stat = 'DONE' THEN 1 ELSE 0 END) AS {$prefix}_doneJobs,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql AND stat = 'EXIT' THEN 1 ELSE 0 END) AS {$prefix}_exitJobs,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql AND stat = 'DONE' AND indexid > 0 THEN 1 ELSE 0 END) AS {$prefix}_doneArrays,";
			$sql .= " SUM(CASE WHEN mem_reserved $range_sql AND stat = 'EXIT' AND indexid > 0 THEN 1 ELSE 0 END) AS {$prefix}_exitArrays,";
			$sql .= " STD(CASE WHEN mem_reserved $range_sql AND stat = 'EXIT' AND indexid > 0 THEN 1 ELSE 0 END) AS {$prefix}_exitArrays,";
			$sql .= " STDDEV(CASE WHEN mem_reserved $range_sql AND stat IN ('EXIT', 'DONE') AND indexid > 0 THEN max_memory ELSE NULL END) AS {$prefix}_finishArrayStd,";

		}

		$sql  = trim($sql, ',');
		$sql .= PHP_EOL . "FROM $table" . PHP_EOL;

		if ($table == 'grid_jobs_finished') {
			$sql .= "WHERE end_time BETWEEN '$start_time' AND '$end_time' AND start_time > '0000-00-00'" . PHP_EOL;
		} else {
			$sql .= "WHERE start_time > '0000-00-00'" . PHP_EOL;
		}

		$sql .= "GROUP BY clusterid, sla, DAYOFYEAR(end_time)" . PHP_EOL;

		$queries[$table] = $sql;
	}

//	$sql .= ") AS rs
//		GROUP BY clusterid, sla" . PHP_EOL;

	return $queries;
}

function grid_update_sla_finished_memory_buckets($clusterid, $date) {
	// Get last two hours of finished jobs
	$begin_time = date('Y-m-d H:i:s', time() - 7200);
	$sql_where  = "AND clusterid = $clusterid AND end_time > " . db_qstr($begin_time);

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET present = 0
		WHERE clusterid = ?',
		array($clusterid));

	$data = db_fetch_assoc('SELECT clusterid, sla,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 8G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 THEN mem_reserved ELSE 0 END) AS 8G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 THEN max_memory ELSE 0 END) AS 8G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "DONE" THEN 1 ELSE 0 END) AS 8G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 8G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 8G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 8G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 0 AND 8388608 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 8G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 16G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 THEN mem_reserved ELSE 0 END) AS 16G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 THEN max_memory ELSE 0 END) AS 16G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "DONE" THEN 1 ELSE 0 END) AS 16G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 16G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 16G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 16G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 8388608 AND 16777216 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 16G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 32G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 THEN mem_reserved ELSE 0 END) AS 32G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 THEN max_memory ELSE 0 END) AS 32G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "DONE" THEN 1 ELSE 0 END) AS 32G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 32G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 32G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 32G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 16777216 AND 33554432 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 32G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 64G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 THEN mem_reserved ELSE 0 END) AS 64G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 THEN max_memory ELSE 0 END) AS 64G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "DONE" THEN 1 ELSE 0 END) AS 64G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 64G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 64G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 64G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 33554432 AND 67108864 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 64G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 128G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 THEN mem_reserved ELSE 0 END) AS 128G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 THEN max_memory ELSE 0 END) AS 128G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "DONE" THEN 1 ELSE 0 END) AS 128G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 128G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 128G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 128G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 67108864 AND 134217728 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 128G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 192G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 THEN mem_reserved ELSE 0 END) AS 192G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 THEN max_memory ELSE 0 END) AS 192G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "DONE" THEN 1 ELSE 0 END) AS 192G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 192G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 192G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 192G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 134217728 AND 201326592 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 192G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 256G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 THEN mem_reserved ELSE 0 END) AS 256G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 THEN max_memory ELSE 0 END) AS 256G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "DONE" THEN 1 ELSE 0 END) AS 256G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 256G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 256G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 256G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 201326592 AND 268435456 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 256G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 384G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 THEN mem_reserved ELSE 0 END) AS 384G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 THEN max_memory ELSE 0 END) AS 384G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "DONE" THEN 1 ELSE 0 END) AS 384G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 384G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 384G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 384G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 268435456 AND 402653184 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 384G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 512G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 THEN mem_reserved ELSE 0 END) AS 512G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 THEN max_memory ELSE 0 END) AS 512G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "DONE" THEN 1 ELSE 0 END) AS 512G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 512G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 512G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 512G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 402653184 AND 536870912 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 512G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 768G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 THEN mem_reserved ELSE 0 END) AS 768G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 THEN max_memory ELSE 0 END) AS 768G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "DONE" THEN 1 ELSE 0 END) AS 768G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 768G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 768G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 768G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 536870912 AND 805306368 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 768G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1024G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 THEN mem_reserved ELSE 0 END) AS 1024G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 THEN max_memory ELSE 0 END) AS 1024G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "DONE" THEN 1 ELSE 0 END) AS 1024G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 1024G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 1024G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 1024G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 805306368 AND 1073741824 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 1024G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 1536G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 THEN mem_reserved ELSE 0 END) AS 1536G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 THEN max_memory ELSE 0 END) AS 1536G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "DONE" THEN 1 ELSE 0 END) AS 1536G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 1536G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 1536G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 1536G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 1073741824 AND 1610612736 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 1536G_finishArrayStd,

		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS 2048G_requested,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 THEN mem_reserved ELSE 0 END) AS 2048G_reserved,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 THEN max_memory ELSE 0 END) AS 2048G_max,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "DONE" THEN 1 ELSE 0 END) AS 2048G_doneJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "EXIT" THEN 1 ELSE 0 END) AS 2048G_exitJobs,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS 2048G_doneArrays,
		SUM(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS 2048G_exitArrays,
		STDDEV(CASE WHEN mem_reserved BETWEEN 1610612736 AND 2147483648 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS 2048G_finishArrayStd,

		SUM(CASE WHEN mem_reserved >= 2147483648 THEN GREATEST(mem_requested, max_mem_requested) ELSE 0 END) AS MAXG_requested,
		SUM(CASE WHEN mem_reserved >= 2147483648 THEN mem_reserved ELSE 0 END) AS MAXG_reserved,
		SUM(CASE WHEN mem_reserved >= 2147483648 THEN max_memory ELSE 0 END) AS MAXG_max,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "DONE" THEN 1 ELSE 0 END) AS MAXG_doneJobs,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "EXIT" THEN 1 ELSE 0 END) AS MAXG_exitJobs,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "DONE" AND indexid > 0 THEN 1 ELSE 0 END) AS MAXG_doneArrays,
		SUM(CASE WHEN mem_reserved >= 2147483648 AND stat = "EXIT" AND indexid > 0 THEN 1 ELSE 0 END) AS MAXG_exitArrays,
		STDDEV(CASE WHEN mem_reserved >= 2147483648 AND stat IN ("EXIT", "DONE") AND indexid > 0 THEN max_memory ELSE NULL END) AS MAXG_finishArrayStd
		FROM grid_jobs
		WHERE stat IN ("DONE", "EXIT") AND start_time > "0000-00-00" ' .
		$sql_where . '
		GROUP BY sla');

	$column_prefixes = array(
		'8G_',
		'16G_',
		'32G_',
		'64G_',
		'128G_',
		'192G_',
		'256G_',
		'384G_',
		'512G_',
		'768G_',
		'1024G_',
		'1536G_',
		'2048G_',
		'MAXG_'
	);

	if (cacti_sizeof($data)) {
		foreach($data as $r) {
			if ($r['sla'] == '') {
				$r['sla'] = '-';
			}

			foreach($column_prefixes as $prefix) {
				$bucket      = trim($prefix, '_');
				$doneJobs    = $r[$prefix . 'doneJobs'];
				$doneArrays  = $r[$prefix . 'doneArrays'];
				$exitJobs    = $r[$prefix . 'exitJobs'];
				$exitArrays  = $r[$prefix . 'exitArrays'];
				$finishedStd = $r[$prefix . 'finishArrayStd'];
				$reserved    = $r[$prefix . 'reserved'];
				$max         = $r[$prefix . 'max'];
				$requested   = $r[$prefix . 'requested'];

				db_execute_prepared('INSERT INTO grid_sla_memory_buckets
					(clusterid, sla, memory_size, mem_requested, mem_reserved, max_memory, doneJobs, doneArrays, exitJobs, exitArrays, finishArrayStd, present, last_updated)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE
						mem_requested = VALUES(mem_requested),
						mem_reserved = VALUES(mem_reserved),
						max_memory = VALUES(max_memory),
						doneJobs = VALUES(doneJobs),
						doneArrays = VALUES(doneArrays),
						exitJobs = VALUES(exitJobs),
						exitArrays = VALUES(exitArrays),
						finishArrayStd = VALUES(finishArrayStd),
						present = VALUES(present),
						last_updated = VALUES(last_updated)',
					array(
						$clusterid,
						$r['sla'],
						$bucket,
						$requested,
						$reserved,
						$max,
						$doneJobs,
						$doneArrays,
						$exitJobs,
						$exitArrays,
						$finishedStd ?? '0',
						1,
						$date
					)
				);
			}
		}
	}

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET mem_efficiency = (max_memory / mem_reserved) * 100
		WHERE mem_reserved > 0
		AND clusterid = ?',
		array($clusterid));

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET mem_efficiency = 0
		WHERE mem_reserved = 0
		AND clusterid = ?',
		array($clusterid));

	db_execute_prepared('UPDATE grid_sla_memory_buckets
		SET doneJobs = 0,
			doneArrays = 0,
			exitJobs = 0,
			exitArrays = 0,
			finishArrayStd = 0,
			mem_requested = 0,
			mem_reserved = 0,
			max_memory = 0
		WHERE present = 0
		AND clusterid = ?',
		array($clusterid));
}

function grid_update_pool_stats($clusterid) {
	$start = microtime(true);

	cacti_log('NOTE: Starting Pool Stats for ClusterID:' . $clusterid, false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);

	$format = array('clusterid', 'sla', 'running', 'pending', 'suspended', 'jobs', 'slots', 'rslots', 'pslots', 'pend_time', 'pend_mem_reserved', 'mem_reserved', 'mem_used', 'mem_requested', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		running=VALUES(running),
		pending=VALUES(pending),
		suspended=VALUES(suspended),
		jobs=VALUES(jobs),
		slots=VALUES(slots),
		rslots=VALUES(rslots),
		pslots=VALUES(pslots),
		pend_time=VALUES(pend_time),
		pend_mem_reserved=VALUES(pend_mem_reserved),
		mem_reserved=VALUES(mem_reserved),
		mem_used=VALUES(mem_used),
		mem_requested=VALUES(mem_requested),
		present=VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT CLUSTERID AS clusterid,
		IF(SERVICE_CLASS="", "none", SERVICE_CLASS) AS sla,
		SUM(CASE WHEN stat="RUN" THEN 1 ELSE 0 END) AS running,
		SUM(CASE WHEN stat="PEND" THEN 1 ELSE 0 END) AS pending,
		SUM(CASE WHEN stat LIKE "%SUSP%" THEN 1 ELSE 0 END) AS suspended,
		COUNT(JOBID) AS jobs,
		SUM(SLOTS) AS slots,
		SUM(CASE WHEN stat="RUN" THEN SLOTS ELSE 0 END) AS rslots,
		SUM(CASE WHEN stat="PEND" THEN SLOTS ELSE 0 END) AS pslots,
		SUM(CASE WHEN STAT="PEND" THEN UNIX_TIMESTAMP()-UNIX_TIMESTAMP(SUBMIT_TIME) ELSE 0 END) AS pend_time,
		SUM(CASE WHEN STAT="PEND" THEN MEM_RESERVED ELSE 0 END) AS pend_mem_reserved,
		SUM(CASE WHEN stat="RUN" THEN MEM_RESERVED ELSE 0 END) AS mem_reserved,
		SUM(CASE WHEN STAT="RUN" THEN MEM ELSE 0 END) AS mem_used,
		SUM(CASE WHEN stat="RUN" THEN MEM_REQUESTED ELSE 0 END) AS mem_requested,
		"1" AS present
		FROM grid_jobs_summary
		WHERE CLUSTERID = ?
		GROUP BY clusterid, service_class',
		array($clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Used for Running Job Efficiency
	$format = array('clusterid', 'sla', 'rjob_efficiency', 'rjob_cputime', 'rjob_walltime', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		rjob_efficiency=VALUES(rjob_efficiency),
		rjob_cputime=VALUES(rjob_cputime),
		rjob_walltime=VALUES(rjob_walltime),
		present=VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT CLUSTERID AS clusterid,
		IF(SERVICE_CLASS="", "none", SERVICE_CLASS) AS sla,
		(SUM(CPU_USED)/SUM(SLOTS*RUN_TIME)) * 100 AS rjob_efficiency,
		SUM(CPU_USED) AS rjob_cputime,
		SUM(SLOTS*RUN_TIME) AS rjob_walltime,
		"1" AS present
		FROM grid_jobs_summary
		WHERE CLUSTERID = ?
		AND stat="RUN"
		GROUP BY clusterid, service_class',
		array($clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Need long strings
	db_execute('SET SESSION group_concat_max_len = 10485760');

	$format = array('clusterid', 'sla', 'pend_max', 'pend_avg', 'pend_median', 'pend_p75', 'pend_p95');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		pend_max=VALUES(pend_max),
		pend_avg=VALUES(pend_avg),
		pend_median=VALUES(pend_median),
		pend_p75=VALUES(pend_p75),
		pend_p95=VALUES(pend_p95)';

	$records = db_fetch_assoc_prepared("
		SELECT CLUSTERID AS clusterid, SERVICE_CLASS AS sla,
		MAX(pending) AS pend_max,
		AVG(pending) AS pend_avg,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(pending ORDER BY pending SEPARATOR ','), ',', 50/100 * COUNT(*) + 1), ',', -1) AS DECIMAL) AS `pend_median`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(pending ORDER BY pending SEPARATOR ','), ',', 75/100 * COUNT(*) + 1), ',', -1) AS DECIMAL) AS `pend_p75`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(GROUP_CONCAT(pending ORDER BY pending SEPARATOR ','), ',', 95/100 * COUNT(*) + 1), ',', -1) AS DECIMAL) AS `pend_p95`
		FROM (
			SELECT CLUSTERID, IF(SERVICE_CLASS='', 'none', SERVICE_CLASS) AS SERVICE_CLASS,
			UNIX_TIMESTAMP()-UNIX_TIMESTAMP(SUBMIT_TIME) AS pending
			FROM grid_jobs_summary
			WHERE CLUSTERID = ?
			AND STAT='PEND'
		) AS rs
		GROUP BY SERVICE_CLASS",
		array($clusterid));

	grid_pump_records($records, 'grid_sla_stats', $format, false, $duplicate);

	// Analytics pool memory
	$format = array('clusterid', 'name', 'freeMemory', 'maxMemory', 'freePercent', 'hosts', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		freeMemory=VALUES(freeMemory),
		maxMemory=VALUES(maxMemory),
		freePercent=VALUES(freePercent),
		hosts=VALUES(hosts),
		present=VALUES(present)';

	$records = db_fetch_assoc_prepared('SELECT gph.clusterid, gph.name,
		SUM(totalValue) AS freeMemory,
		SUM(maxMem) AS maxMemory,
		(SUM(totalValue)/SUM(maxMem))*100 AS freePercent,
		COUNT(ghr.host) AS hosts,
		"1" AS present
		FROM grid_guarantee_pool_hosts AS gph
		INNER JOIN (
			SELECT clusterid, host, totalValue
			FROM grid_hosts_resources
			WHERE resource_name="mem"
			AND clusterid = ?
		) AS ghr
		ON ghr.clusterid=gph.clusterid
		AND ghr.host=gph.host
		INNER JOIN grid_hostinfo AS ghi
		ON ghi.clusterid=gph.clusterid
		AND ghi.host=gph.host
		AND ghi.maxMem > 0
		INNER JOIN grid_hosts AS gh
		ON gh.clusterid=gph.clusterid
		AND gh.host=gph.host
		AND gh.status NOT IN ("Closed-Admin", "Unavail", "Unreach", "Closed-LIM")
		WHERE gph.clusterid = ?
		GROUP BY gph.name',
		array($clusterid, $clusterid));

	grid_pump_records($records, 'grid_pool_memory', $format, false, $duplicate);

	// Host Lending
	$format = array('clusterid', 'host', 'numRun', 'maxJobs', 'freeMemory', 'maxMemory', 'consumers', 'consumer_count', 'present');

	$duplicate = 'ON DUPLICATE KEY UPDATE
		numRun=VALUES(numRun),
		maxJobs=VALUES(maxJobs),
		freeMemory=VALUES(freeMemory),
		maxMemory=VALUES(maxMemory),
		consumers=VALUES(consumers),
		consumer_count=VALUES(consumer_count),
		present=VALUES(present)';

	$records = db_fetch_assoc_prepared("SELECT aj.CLUSTERID AS clusterid,
		FIRST_HOST AS host,
		gh.numRun,
		IF(gh.maxJobs = '-', ghi.maxCpus, gh.maxJobs) AS maxJobs,
		totalValue AS freeMemory,
		maxMem AS maxMemory,
		CONCAT(\"'\", GROUP_CONCAT(DISTINCT SERVICE_CLASS SEPARATOR \"', '\"),\"'\") AS consumers,
		COUNT(DISTINCT SERVICE_CLASS) AS consumer_count,
		'1' AS present
		FROM grid_jobs_summary AS aj
		INNER JOIN grid_hosts AS gh
		ON aj.CLUSTERID = gh.clusterid
		AND aj.FIRST_HOST = gh.host
		AND gh.status NOT IN ('Closed-Admin', 'Unavail', 'Unreach', 'Closed-LIM')
		INNER JOIN grid_hostinfo AS ghi
		ON gh.clusterid = ghi.clusterid
		AND gh.host = ghi.host
		INNER JOIN (
			SELECT clusterid, host, totalValue
			FROM grid_hosts_resources
			WHERE resource_name='mem'
			AND clusterid = ?
		) AS ghr
		ON gh.CLUSTERID = ghr.clusterid
		AND gh.host = ghr.host
		WHERE aj.stat='RUN'
		AND aj.CLUSTERID = ?
		GROUP BY aj.CLUSTERID, aj.FIRST_HOST",
		array($clusterid, $clusterid));

	grid_pump_records($records, 'grid_pool_host_lending', $format, false, $duplicate);

	$end = microtime(true);

	cacti_log('Finished Pool Stats for ClusterID:' . $clusterid . ' in ' . number_format($end - $start, 2) . ' seconds', false, 'LSFENH', POLLER_VERBOSITY_MEDIUM);
}

