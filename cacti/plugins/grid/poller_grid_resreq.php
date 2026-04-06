#!/usr/bin/php -q
<?php
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

include_once(dirname(__FILE__) . '/../../include/cli_check.php');
include_once(dirname(__FILE__) . '/lib/grid_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

$start = microtime(true);

/* get the grid polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* get the start time */
$start_time = time();

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug;

$debug     = false;
$forcerun  = false;
$lastmaint = false;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '--debug':
		$debug = true;
		break;
	case '--force':
		$forcerun = true;
		break;
	case '--lastmaint':
		$lastmaint = $value;
		break;
	case '-h':
	case '-H':
	case '--help':
		display_help();
		exit(0);
		break;
	case '-v':
	case '-V':
	case '--version':
		display_version();
		exit(0);
		break;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit(1);
	}
}

$poller_interval = read_config_option('poller_interval');
$lastrun         = read_config_option('grid_resreq_lastrun');
$runnow          = false;

if (empty($lastrun) || time() - $lastrun > 1800 || $forcerun) {
	$runnow = true;
	set_config_option('grid_resreq_lastrun', time());
}

/* disable gridresreq for now */
#$runnow = false;

if (!$runnow) {
	print "NOTE: Grid Resreq only runs once every 30 minutes.  It's not time to run." . PHP_EOL;
	exit(0);
}

create_base_table();

if (!is_grid_process_running(0, 'OPTIMIZE')) {
	if ((read_config_option('grid_system_collection_enabled') == 'on') &&
		(read_config_option('grid_collection_enabled') == 'on')) {
		if (detect_and_correct_running_processes(0, 'GRIDRESREQ', $poller_interval*4)) {
			/* check for idled jobs*/
			grid_refresh_resreq_stats();

			/* remove the process entry */
			remove_process_entry(0, 'GRIDRESREQ');
		}
	}
}

function create_base_table() {
	if (!db_table_exists('grid_jobs_resreq_analysis')) {
		db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_resreq_analysis` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
			`queue` varchar(60) NOT NULL DEFAULT '',
			`sla` varchar(60) NOT NULL DEFAULT '',
			`num_cpus` int(10) unsigned NOT NULL DEFAULT 0,
			`combinedResreq` varchar(1024) NOT NULL DEFAULT '',
			`res_select_pend` varchar(512) NOT NULL DEFAULT '',
			`res_select_run` varchar(512) NOT NULL DEFAULT '',
			`res_rusage` varchar(512) NOT NULL DEFAULT '',
			`res_order` varchar(128) NOT NULL DEFAULT '',
			`res_cu` varchar(128) NOT NULL DEFAULT '',
			`res_affinity` varchar(128) NOT NULL DEFAULT '',
			`res_span` varchar(64) NOT NULL DEFAULT '',
			`res_same` varchar(64) NOT NULL DEFAULT '',
			`total_users` int(10) unsigned NOT NULL DEFAULT 0,
			`unique_users` mediumblob NOT NULL DEFAULT '',
			`unique_projects` mediumblob NOT NULL DEFAULT '',
			`unique_userGroups` mediumblob NOT NULL DEFAULT '',
			`jobids` longblob NOT NULL DEFAULT '',
			`pendJobs` int(10) unsigned NOT NULL DEFAULT 0,
			`runJobs` int(10) unsigned NOT NULL DEFAULT 0,
			`pendSlots` int(10) unsigned NOT NULL DEFAULT 0,
			`runSlots` int(10) unsigned NOT NULL DEFAULT 0,
			`tputHOUR` int(10) unsigned NOT NULL DEFAULT 0,
			`tput5MIN` int(10) unsigned NOT NULL DEFAULT 0,
			`pendTime` int(10) unsigned NOT NULL DEFAULT 0,
			`pendReasons` mediumblob NOT NULL DEFAULT '',
			`stalledTime` int(10) unsigned NOT NULL DEFAULT 0,
			`last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			UNIQUE KEY `resreq_unique` (`clusterid`,`queue`,`sla`,`num_cpus`,`combinedResreq`),
			KEY `clusterid` (`clusterid`),
			KEY `last_updated` (`last_updated`),
			KEY `tput5MIN` (`tput5MIN`),
			KEY `tputHOUR` (`tputHOUR`),
			KEY `runJobs` (`runJobs`),
			KEY `pendJobs` (`pendJobs`),
			KEY `pendTime` (`pendTime`),
			KEY `stalledTime` (`stalledTime`))
			ENGINE=InnoDB
			CHARSET=latin1
			COMMENT='Holds information on information by status'");
	}
}

function grid_refresh_resreq_stats() {
	global $debug;

	/* record the start time */
	$start_time = microtime(true);

	if (!db_index_exists('grid_jobs', 'stat')) {
		db_execute('ALTER TABLE grid_jobs ADD INDEX stat(stat)');
	}

	if (!db_index_exists('grid_jobs_pendreasons', 'clusterid_jobid_indexid')) {
		db_execute('ALTER TABLE grid_jobs_pendreasons ADD INDEX clusterid_jobid_indexid(clusterid, jobid, indexid)');
	}

	// Create a temporary table for merge
	db_execute('CREATE TEMPORARY TABLE IF NOT EXISTS grid_resreq_jobs (
		clusterid int unsigned not null default "0",
		jobid bigint unsigned not null default "0",
		indexid int(10) unsigned not null default "0",
		PRIMARY KEY (clusterid, jobid, indexid))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic');

	// MariaDB [cacti]> desc grid_jobs_resreq_analysis;
	// +-------------------+------------------+------+-----+---------------------+-------+
	// | Field             | Type             | Null | Key | Default             | Extra |
	// +-------------------+------------------+------+-----+---------------------+-------+
	// | id                | biging unsigned  | NO   | PRI | auto_increment      |       |
	// | clusterid         | int(10) unsigned | NO   | UNI | 0                   |       |
	// | queue             | varchar(60)      | NO   | UNI |                     |       |
	// | sla               | varchar(60)      | NO   | UNI |                     |       |
	// | num_cpus          | int(10) unsigned | NO   | UNI | 0                   |       |
	// | combinedResreq    | varchar(1024)    | NO   | UNI |                     |       |
	// | res_select_pend   | varchar(512)     | NO   |     |                     |       |
	// | res_select_run    | varchar(512)     | NO   |     |                     |       |
	// | res_rusage        | varchar(512)     | NO   |     |                     |       |
	// | res_order         | varchar(128)     | NO   |     |                     |       |
	// | res_cu            | varchar(128)     | NO   |     |                     |       |
	// | res_affinity      | varchar(128)     | NO   |     |                     |       |
	// | res_span          | varchar(64)      | NO   |     |                     |       |
	// | res_same          | varchar(64)      | NO   |     |                     |       |
	// | total_users       | int(10) unsigned | NO   |     | 0                   |       |
	// | unique_users      | mediumblob       | NO   |     |                     |       |
	// | unique_projects   | mediumblob       | NO   |     |                     |       |
	// | unique_userGroups | mediumblob       | NO   |     |                     |       |
	// | pendJobs          | int(10) unsigned | NO   |     | 0                   |       |
	// | runJobs           | int(10) unsigned | NO   |     | 0                   |       |
	// | pendSlots         | int(10) unsigned | NO   |     | 0                   |       |
	// | runSlots          | int(10) unsigned | NO   |     | 0                   |       |
	// | tputHOUR          | int(10) unsigned | NO   |     | 0                   |       |
	// | tput5MIN          | int(10) unsigned | NO   |     | 0                   |       |
	// | pendTime          | int(10) unsigned | NO   |     | 0                   |       |
	// | stalledTime       | int(10) unsigned | NO   |     | 0                   |       |
	// | last_updated      | timestamp        | NO   |     | current_timestamp() |       |
	// +-------------------+------------------+------+-----+---------------------+-------+

	$nreq = array();

	$sql = "SELECT clusterid, queue, sla, num_cpus, combinedResreq,
		IF(combinedResreq LIKE '%select[%', REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'select[', -1), ']', 1), 'select[', ''), '') AS res_select_pend,
		IF(effectiveResreq LIKE '%select[%', REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(effectiveResreq, 'select[', -1), ']', 1), 'select[', ''), '') AS res_select_run,
		IF(combinedResreq LIKE '%rusage%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'rusage[', -1), ']', 1), '') AS res_rusage,
		IF(combinedResreq LIKE '%order%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'order[', -1), ']', 1), '') AS res_order,
		IF(combinedResreq LIKE '%cu%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'cu[', -1), ']', 1), '') AS res_cu,
		IF(combinedResreq LIKE '%affinity%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'affinity[', -1), ']', 1), '') AS res_affinity,
		IF(combinedResreq LIKE '%span%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'span[', -1), ']', 1), '') AS res_span,
		IF(combinedResreq LIKE '%same%', SUBSTRING_INDEX(SUBSTRING_INDEX(combinedResreq, 'same[', -1), ']', 1), '') AS res_same,
		total_users, unique_users, unique_projects, unique_userGroups, jobids, pendJobs, runJobs, pendSlots, runSlots, tputHOUR, tput5MIN, pendTime, NOW() AS last_updated
		FROM (
			SELECT clusterid, queue, sla, num_cpus,
			GROUP_CONCAT(CASE WHEN stat = 'PEND' OR stat = 'PSUSP' THEN IF(indexid = 0, jobid, CONCAT(jobid, '[', indexid, ']')) ELSE NULL END) AS jobids,
			combinedResreq, effectiveResreq,
			COUNT(DISTINCT user) AS total_users,
			GROUP_CONCAT(DISTINCT user) AS unique_users,
			GROUP_CONCAT(DISTINCT SUBSTRING_INDEX(projectName, '.', 3)) AS unique_projects,
			GROUP_CONCAT(DISTINCT IF(userGroup != '', userGroup, NULL)) AS unique_userGroups,
			SUM(CASE WHEN stat IN ('RUNNING', 'SSUSP', 'USUSP') THEN 1 ELSE 0 END) AS runJobs,
			SUM(CASE WHEN stat IN ('RUNNING', 'SSUSP', 'USUSP') THEN num_cpus ELSE 0 END) AS runSlots,
			SUM(CASE WHEN stat LIKE 'P%' THEN 1 ELSE 0 END) AS pendJobs,
			SUM(CASE WHEN stat LIKE 'P%' THEN num_cpus ELSE 0 END) AS pendSlots,
			SUM(CASE WHEN stat IN ('DONE', 'EXIT') AND end_time BETWEEN ? AND ? THEN 1 ELSE 0 END) AS tputHOUR,
			SUM(CASE WHEN stat IN ('DONE', 'EXIT') AND end_time BETWEEN ? AND ? THEN 1 ELSE 0 END) AS tput5MIN,
			SUM(CASE WHEN stat IN ('PEND', 'PSUSP') THEN pend_time ELSE 0 END) AS pendTime
			FROM grid_jobs FORCE INDEX (stat)
			GROUP BY clusterid, queue, sla, num_cpus, combinedResreq, effectiveResreq
		) AS rs";

	// There are two time windows
	// From 1 Hour 15 Minutes ago to 10 Minutes ago
	// From 15 Minutes ago to 10 minutes ago
	$check_start1 = round($start_time - (3600 + 900), 0);
	$check_start2 = round($start_time - 900, 0);
	$end_time     = round($start_time - 600, 0);

	$params = array(
		date('Y-m-d H:i:s', $check_start1),
		date('Y-m-d H:i:s', $end_time),
		date('Y-m-d H:i:s', $check_start2),
		date('Y-m-d H:i:s', $end_time)
	);

	$max_end_time = db_fetch_cell('SELECT MAX(last_updated) FROM grid_jobs_resreq_analysis');

	$requirements = db_fetch_assoc_prepared($sql, $params);

	if (cacti_sizeof($requirements)) {
		foreach($requirements as $r) {
			printf('Combined:%s'. PHP_EOL, $r['combinedResreq']);

			$r['res_select_pend'] = select_prune($r['res_select_pend']);
			$r['res_select_run']  = select_prune($r['res_select_run']);

			if ($r['sla'] == '') {
				$r['sla'] = 'N/A';
			}

			if (isset($nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']])) {
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['runJobs']     += $r['runJobs'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['runSlots']    += $r['runSlots'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['pendJobs']    += $r['pendJobs'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['pendSlots']   += $r['pendSlots'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['total_users'] += $r['total_users'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['tput5MIN']    += $r['tput5MIN'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['tputHOUR']    += $r['tputHOUR'];
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['pendTime']    += $r['pendTime'];

				$users1 = explode(',', $nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_users'] ?? '');
				$users2 = explode(',', $r['unique_users'] ?? '');
				$users  = array_unique(array_merge($users1, $users2));

				$projects1 = explode(',', $nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_projects'] ?? '');
				$projects2 = explode(',', $r['unique_projects'] ?? '');
				$projects  = array_unique(array_merge($projects1, $projects2));

				$groups1 = explode(',', $nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_userGroups'] ?? '');
				$groups2 = explode(',', $r['unique_userGroups'] ?? '');
				$groups  = array_unique(array_merge($groups1, $groups2));

				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_users']      = implode(',', $users);
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_projects']   = implode(',', $projects);
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['unique_userGroups'] = implode(',', $groups);

				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']]['total_users']  = cacti_sizeof($users);
			} else {
				$nreq[$r['clusterid'] . '||' . $r['queue'] . '||' . $r['sla'] . '||' . $r['num_cpus'] . '||' . $r['combinedResreq']] = $r;
			}
		}

		$sql_prefix = 'INSERT INTO grid_jobs_resreq_analysis
			(clusterid, queue, sla, num_cpus, combinedResreq, res_select_pend, res_select_run, res_rusage, res_order, res_cu, res_affinity, res_span, res_same, total_users, unique_users, unique_projects, unique_userGroups, jobids, pendJobs, runJobs, pendSlots, runSlots, tputHOUR, tput5MIN, pendTime, pendReasons, last_updated) VALUES';

		$sql_suffix = ' ON DUPLICATE KEY UPDATE
			total_users = VALUES(total_users),
			unique_users = VALUES(unique_users),
			unique_projects = VALUES(unique_projects),
			unique_userGroups = VALUES(unique_userGroups),
			jobids = VALUES(jobids),
			pendJobs = VALUES(pendJobs),
			runJobs = VALUES(runJobs),
			pendSlots = VALUES(pendSlots),
			runSlots = VALUES(runSlots),
			tputHOUR = VALUES(tputHOUR),
			tput5MIN = VALUES(tput5MIN),
			pendTime = VALUES(pendTime),
			pendReasons = VALUES(pendReasons),
			last_updated = VALUES(last_updated)';

		$i = 0;

		$nnreq = array_chunk($nreq, 1000);

		$maxjobs = 100000;

		foreach($nnreq as $chunk) {
			$sql = array();

			foreach($chunk as $req) {
				// Ignore newly submitted buckets
				if ($req['combinedResreq'] == '-') {
					continue;
				}

				$req['jobids'] = trim($req['jobids'] ?? '', ', ');

				// Some queries are taking too long to run
				if ($req['jobids'] != '' && $req['pendJobs'] < $maxjobs && $req['pendJobs'] > 0) {
					$ps = microtime(true);
					$pendReasons = get_pend_reasons($req['clusterid'], $req['jobids']);
					$pe = microtime(true);

					cacti_log(sprintf('NOTE: Timing:%0.2f Pending:%s', $pe - $ps, $req['pendJobs']), false, 'RESREQ', POLLER_VERBOSITY_MEDIUM);
				} else {
					if ($req['jobids'] != '' && $req['pendJobs'] >= $maxjobs) {
						cacti_log(sprintf('NOTE: Skipping ResReq Pending for %s Pending Jobs', $req['pendJobs']), false, 'RESREQ', POLLER_VERBOSITY_LOW);
					}

					$pendReasons = '';
				}

				if (empty($req['unique_userGroups'])) {
					$req['unique_userGroups'] = '';
				}

				$sql[] = '(' .
					$req['clusterid']                  . ', ' .
					db_qstr($req['queue'])             . ', ' .
					db_qstr($req['sla'])               . ', ' .
					$req['num_cpus']                   . ', ' .
					db_qstr($req['combinedResreq'])    . ', ' .
					db_qstr($req['res_select_pend'])   . ', ' .
					db_qstr($req['res_select_run'])    . ', ' .
					db_qstr($req['res_rusage'])        . ', ' .
					db_qstr($req['res_order'])         . ', ' .
					db_qstr($req['res_cu'])            . ', ' .
					db_qstr($req['res_affinity'])      . ', ' .
					db_qstr($req['res_span'])          . ', ' .
					db_qstr($req['res_same'])          . ', ' .
					$req['total_users']                . ', ' .
					db_qstr($req['unique_users'])      . ', ' .
					db_qstr($req['unique_projects'])   . ', ' .
					db_qstr($req['unique_userGroups']) . ', ' .
					db_qstr($req['jobids'])            . ', ' .
					$req['pendJobs']                   . ', ' .
					$req['runJobs']                    . ', ' .
					$req['pendSlots']                  . ', ' .
					$req['runSlots']                   . ', ' .
					$req['tputHOUR']                   . ', ' .
					$req['tput5MIN']                   . ', ' .
					$req['pendTime']                   . ', ' .
					db_qstr($pendReasons)              . ', ' .
					db_qstr($req['last_updated'])      . ')';
			}

			db_execute($sql_prefix . implode(', ', $sql) . $sql_suffix);
		}
	}

	$job_buckets = cacti_sizeof($nreq);

	/* get rid of old jobs */
	db_execute_prepared('UPDATE grid_jobs_resreq_analysis
		SET pendJobs=0, runJobs=0, tputHOUR = 0, tput5MIN = 0, pendTime = 0, runSlots = 0, pendSlots = 0, stalledTime = 0
		WHERE last_updated <= ?',
		array($max_end_time));

	$poller_interval = read_config_option('poller_interval');
	if (empty($poller_interval)) {
		$poller_interval = 0;
	}

	/* increase the stalled time if the resource is blocked */
	db_execute_prepared('UPDATE grid_jobs_resreq_analysis
		SET stalledTime = stalledTime + ?
		WHERE last_updated > ?
		AND pendJobs > 0 AND runJobs = 0 AND tputHOUR = 0 AND tput5MIN = 0',
		array($poller_interval, $max_end_time));

	/* error condition where some jobs have a combinedResreq of '-' when first submitted */
	db_execute_prepared('DELETE FROM grid_jobs_resreq_analysis
		WHERE combinedResreq = ? AND last_updated < ?', array('-', $max_end_time));

	db_execute_prepared('DELETE FROM grid_jobs_resreq_analysis
		WHERE last_updated < DATE_SUB(CURDATE(), INTERVAL 1 WEEK)');

	db_execute('TRUNCATE grid_resreq_jobs');

	/* record the end time */
	$end_time = microtime(true);

	cacti_log('GRIDRESREQ STATS: Time:' . round($end_time-$start_time,2) . ' Buckets:' . $job_buckets, true, 'SYSTEM');
}

function get_pend_reasons($clusterid, $jobids) {
	$jobids  = explode(',', $jobids);
	$reasons = array();

	if (cacti_sizeof($jobids)) {
		grid_debug('Getting pending reasons for ' . cacti_sizeof($jobids) . ' jobs.');

		$i = 0;
		$sql = array();

		foreach($jobids as $jid) {
			// trim the jobid
			$jid = trim($jid);

			// skip empty jobids
			if ($jid == '') {
				continue;
			}

			// post process looking for LSF syntax
			if (strpos($jid, '[') !== false) {
				$parts   = explode('[', $jid);
				$jobid   = $parts[0];
				$indexid = trim($parts[1], ']');
			} else {
				$jobid   = $jid;
				$indexid = 0;
			}

			// Last check.  This should never happen, but we get periodic errors
			if (empty($jobid)) {
				continue;
			} elseif (empty($indexid)) {
				$indexid = 0;
			}

			$sql[] = "($clusterid, $jobid, $indexid)";
		}

		if (cacti_sizeof($sql)) {
			db_execute('TRUNCATE TABLE grid_resreq_jobs');

			$chunks = array_chunk($sql, 10000);

			foreach($chunks as $sql) {
				db_execute('INSERT INTO grid_resreq_jobs (clusterid, jobid, indexid) VALUES ' . implode(', ', $sql) . ' ON DUPLICATE KEY UPDATE clusterid=VALUES(clusterid)');
			}

			$reasons = db_fetch_assoc("SET STATEMENT max_statement_time=40 FOR 
				SELECT reason, detail, type, SUM(pendTime) AS pendTime, COUNT(DISTINCT jobid, indexid) AS jobs
				FROM (
					SELECT prm.reason, gjp.detail, gjp.jobid, gjp.indexid,
					IF(end_time = '0000-00-00', 'active', 'inactive') AS type,
					IF(end_time = '0000-00-00', UNIX_TIMESTAMP() - UNIX_TIMESTAMP(start_time), UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time)) AS pendTime
					FROM grid_jobs_pendreasons AS gjp
					INNER JOIN grid_resreq_jobs AS grj
					ON grj.clusterid = gjp.clusterid
					AND grj.jobid = gjp.jobid
					AND grj.indexid = gjp.indexid
					INNER JOIN grid_jobs_pendreason_maps AS prm
					ON gjp.reason = prm.reason_code
					AND gjp.subreason = prm.sub_reason_code
				) AS rs
				GROUP BY reason, type, detail");
	
			grid_debug('Found ' . cacti_sizeof($reasons) . ' pending reasons for ' . cacti_sizeof($jobids) . ' jobs.');
		}
	}

	return json_encode($reasons);
}

function select_prune($requirement) {
	if (strpos($requirement, 'select[') !== false) {
		$parts = explode(']', $requirement);

		foreach($parts as $index => $p) {
			if (strpos($p, 'select[') !== false) {
				$parts[$index] = 'select[' . select_prune(str_replace('select[', '', $p));
			} else {
				$parts[$index] = trim($p);
			}
		}

		return trim(implode('] ', $parts));
	} elseif (strpos($requirement, '|') === false) {
		return trim_select(str_replace(array('(', ')'), '', $requirement));
	} else {
		return trim_select($requirement);
	}
}

function trim_select($string) {
	// Trim the string first
	$string = trim($string);

	if ($string == '-' || $string == '') {
		return '';
	}

	// ----------------------------------
	// Tokenize by '&&'
	// ----------------------------------
	$parts = explode('&&', $string);
	foreach($parts as $index => $p) {
		$parts[$index] = trim($p);
	}
	$string = implode(' && ', $parts);

	// ----------------------------------
	// Tokenize by '||'
	// ----------------------------------
	$parts = explode('||', $string);
	foreach($parts as $index => $p) {
		$parts[$index] = trim($p);
	}
	$string = implode(' || ', $parts);

	// ----------------------------------
	// Tokenize by '<'
	// ----------------------------------
	$parts = explode('<', $string);
	foreach($parts as $index => $p) {
		$parts[$index] = trim($p);
	}

	$string = '';
	foreach($parts as $index => $p) {
		if (substr($p, 0, 1) == '=') {
			$string .= ($index > 0 ? ' <= ':'') . substr(trim($p), 1);
		} else {
			$string .= ($index > 0 ?' < ':'') . $p;
		}
	}

	// ----------------------------------
	// Tokenize by '>'
	// ----------------------------------
	$parts = explode('>', $string);
	foreach($parts as $index => $p) {
		$parts[$index] = trim($p);
	}

	$string = '';
	foreach($parts as $index => $p) {
		if (substr($p, 0, 1) == '=') {
			$string .= ($index > 0 ? ' >= ':'') . substr(trim($p), 1);
		} else {
			$string .= ($index > 0 ? ' > ':'') . $p;
		}
	}

	// Normalize spacing
	$string = str_replace(array('( ', ' )'), array('(', ')'), $string);
	$string = str_replace(array('(', ')'), array('( ', ' )'), $string);
	$string = optimize_select($string);

	// Run through && and || and trim unneeded parens
	// TBD

	return $string;
}

function optimize_select($string) {
	$parts  = explode(' ', $string);

	grid_debug($string);

	// Variables for navigation
	$level       = 0;
	$in_or       = array();
	$in_and      = array();
	$open_index  = array();
	$close_index = array();

	if (cacti_sizeof($parts)) {
		foreach($parts as $index => $p) {
			switch($p) {
				case '(';
					$level++;

					grid_debug("Increasing level to $level");

					$open_index[$level] = $index;
					$in_and[$level]     = false;
					$in_or[$level]      = false;

					break;
				case ')':
					$close_index[$level] = $index;

					grid_debug("Found an end paren for $level" . ($level > 0 ? ' reducing':''));

					if ($level > 0) {
						if (isset($in_and[$level]) && isset($in_or[$level])) {
							if ($in_and[$level] && !$in_or[$level]) {
								// Mark for removal
								if (isset($close_index[$level]) && isset($parts[$close_index[$level]])) {
									$parts[$close_index[$level]] = '++';
								}

								if (isset($open_index[$level]) && isset($parts[$open_index[$level]])) {
									$parts[$open_index[$level]] = '++';
								}

								$in_and[$level] = false;
							} elseif ($in_or[$level] && !$in_and[$level]) {
								// Preserve all parenthesis
								$in_or[$level] = false;
							} elseif (!$in_or[$level] && !$in_and[$level]) {
								// Should not happen, so bark
								$parts[$index] = '++';

								if (isset($open_index[$level]) && isset($parts[$open_index[$level]])) {
									$parts[$open_index[$level]] = '++';
								}
							}
						}

						$level--;
					}

					break;
				case '&&':
					$in_and[$level] = true;

					break;
				case '||':
					$in_or[$level] = true;

					break;
				case '>=':
				case '<=':
				case '>':
				case '<':
				case '==':
				case '=':
					break;
				default:
					if (is_numeric($p)) {
						grid_debug("Value is $p");
					} else {
						grid_debug("Resource is $p");
					}
			}
		}

		foreach($parts as $index => $p) {
			if ($p == '++') {
				unset($parts[$index]);
			}
		}

		$string = implode(' ', $parts);
	}

	// Handle first special case 'type == any'
	if (strpos($string, 'type == any && type == any') !== false) {
		$string = str_replace('type == any && type == any', 'type == any', $string);
	}

	// Handle second special case 'type == any'
	if (strpos($string, 'type == any && health == ok && type == any') !== false) {
		$string = str_replace('type == any && health == ok && type == any', 'health == ok && type == any', $string);
	}

	return $string;
}

/**
 * display_version - Display version information
 */
function display_version() {
	global $config;

	print 'IBM Spectrum LSF RTM Job Bucket Poller Process ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}

/**
 * display_help - displays the usage of the function
 */
function display_help() {
	display_version();

	print 'usage: poller_grid_resreq.php [--debug] [--force]' . PHP_EOL . PHP_EOL;
	print '--force          - Empty option for now.  May change' . PHP_EOL;
	print '--debug          - Display verbose output during execution' . PHP_EOL;
}

