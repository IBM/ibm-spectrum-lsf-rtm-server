#!/usr/bin/php -q
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

/* Start Initialization Section */
include(dirname(__FILE__) . '/../../include/cli_check.php');

include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/heuristics/functions.php');

/* get the srm polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

/* take the start time to log performance data */
$start = microtime(true);

$debug     = false;
$force     = false;
$percent   = false;
$run_maint = false;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-f':
	case '--force':
		$force = true;
		break;
	case '-p':
	case '--percentiles':
		$percent = true;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

$nowrun = time();
$now = date('Y-m-d H:i:s', $nowrun);

heuristics_debug('NOTE: About to Enter Heuristics Poller Pprocessing');

if (read_config_option('grid_collection_enabled') != 'on') {
	heuristics_debug('DB schema upgrade in process. Heuristics poller exit.');
	exit;
}

//Along with bug 40694 fix, correct saved heuristics_days from previous "0,1,2,3,4,5,6,7,14,30" options to default '1week'.
//In the new options, "2days" is the minimum

if (read_config_option('heuristics_days') == '1week') {
	set_config_option('heuristics_days', 7);
}

if (strtotime('-' . read_config_option('heuristics_days') . 'days') > strtotime('-2days')) {
	set_config_option('heuristics_days', 7);
	/* reset local settings cache so the user sees the new settings */
	kill_session_var('sess_config_array');

}

$last_run = read_config_option('grid_heuristics_lastrun');
set_config_option('grid_heuristics_lastrun', $nowrun);

$grid_maint_time       = read_config_option('grid_prev_db_maint_time');
$heuristics_maint_time = read_config_option('heuristics_prev_db_maint_time');
heuristics_debug ("grid prev maint time=$grid_maint_time, heuristics prev maint time=$heuristics_maint_time");

/* obtain the polleri interval if the user is using that Cacti mod */
$poller_interval = read_config_option('poller_interval');
if (empty($poller_interval)) {
	$poller_interval = 300;
}

if (empty($grid_maint_time)) {
	$run_maint = false;
} elseif (empty($heuristics_maint_time)) {
	set_config_option('heuristics_prev_db_maint_time', $grid_maint_time);
	$run_maint = false;
} elseif ($heuristics_maint_time != $grid_maint_time){
	$run_maint = true;
}

// See if the aggregation column changed, if so, reset statistics
$custom      = read_config_option('heuristics_custom_column');
$prev_custom = read_config_option('heuristics_custom_column_previous');
$reset       = false;

if ($prev_custom != '') {
	if ($custom != $prev_custom) {
		$reset = true;
	}
}
set_config_option('heuristics_custom_column_previous', $custom);

// Remove old tables if the customer has reset the aggregation column
if ($reset) {
	heuristics_debug('NOTE: Detecting an Aggregation Column Changes, Clearing old Data.');

	// Reset the user statistics
	$tables = array(
		'grid_heuristics_user_history_today',
		'grid_heuristics_user_history_yesterday',
		'grid_heuristics_user_stats',
		'grid_heuristics',
		'grid_heuristics_percentiles',
	);

	foreach($tables as $table) {
		heuristics_debug('NOTE: Truncating Old Data from Table ' . $table);
		if (db_table_exists($table)) {
			db_execute("TRUNCATE TABLE $table");
		}
	}

	// Remove old user stats partitions next
	$tables = array_rekey(
		db_fetch_assoc('SELECT TABLE_NAME
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA="cacti"
			AND (
				TABLE_NAME LIKE "grid_heuristics_percentiles_v%"
				OR TABLE_NAME LIKE "grid_heuristics_user_history_v%"
			)'),
		'TABLE_NAME', 'TABLE_NAME'
	);

	foreach($tables as $table) {
		heuristics_debug('NOTE: Dropping Old Table ' . $table);
		db_execute("DROP TABLE $table");
	}
}

if (!$force && !$percent) {
	if (detect_and_correct_running_processes(0, 'HEURISTICSPOLLER', $poller_interval*3)) {
		heuristics_debug('NOTE: Calculating User Throughput');

		// Update the user stats if someone has not from the GUI recently
		update_user_statistics();

		$max_time = db_fetch_cell('SELECT MAX(last_updated) FROM grid_heuristics_user_stats');
		db_execute_prepared("DELETE FROM grid_heuristics_user_stats WHERE last_updated<?", array($max_time));
		db_execute("INSERT IGNORE INTO grid_heuristics_user_history_today SELECT * FROM grid_heuristics_user_stats");

		/* take the start time to log performance data */
		$end = microtime(true);

		$cacti_stats = sprintf('Time:%01.4f', round($end-$start,4));

		/* log to the database */
		set_config_option('stats_heuristics_5min', $cacti_stats);

		/* log to the logfile */
		cacti_log('HEURISTIC STATS: ' . $cacti_stats ,true,'SYSTEM');

		/* update table grid_clusters_reportdata*/
		grid_reportdata_update();

		remove_process_entry(0, 'HEURISTICSPOLLER');
	}
}

/* take the start time to log performance data */
$end = microtime(true);

if (empty($last_run)) {
	$last_run = time();
} elseif ($run_maint || $force) {
	if (empty($grid_maint_time)) {
		print "The Grid maintenance never ran before (grid_prev_db_maint_time is empty). The Grid maintenance should be run first, even force historical aggregation.\n";
		exit;
	}
	/* allow to run up to 20 hours */
	if (detect_and_correct_running_processes(0, 'HEURISTICSMAINT', 72000)) {
		// Log the maint start time first
		set_config_option('heuristics_prev_db_maint_time', $grid_maint_time);

		heuristics_debug('NOTE: Managing Old Partitions');
		/* determine which packages to include */
		$time   = time();
		$day    = date('z');
		$year   = date('Y');
		$fday1  = date('z', $time - 86400); //1 Days
		$fyear1 = date('Y', $time - 86400); //1 Days
		$fday   = date('z', $time - 604800); //7 Days
		$fyear  = date('Y', $time - 604800); //7 Days

		// New partition suffix
		$year_day = $year . $day;

		// Move to the new 7 day scheme
		$old_table = db_fetch_row("SHOW TABLES LIKE 'grid_heuristics_user_history_yesterday'");
		$new_table = db_fetch_row("SHOW TABLES LIKE 'grid_heuristics_user_history_v" . $fyear1 . $fday1 . "'");
		if (cacti_sizeof($old_table) && !cacti_sizeof($new_table)) {
			heuristics_debug('NOTE: Renaming Legacy Table');
			db_execute('RENAME TABLE grid_heuristics_user_history_yesterday to grid_heuristics_user_history_v' . $fyear1 . $fday1);
		}

		$partitions = db_fetch_assoc("SELECT TABLE_NAME
			FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_TYPE='BASE TABLE'
			AND TABLE_SCHEMA='cacti'
			AND TABLE_NAME LIKE 'grid_heuristics_user_history_v%';");

		if (cacti_sizeof($partitions)) {
			foreach($partitions as $part) {
				$partition_yearday = str_replace('grid_heuristics_user_history_v', '', $part['TABLE_NAME']);
				$tmp_year = substr($partition_yearday, 0, 4);
				$tmp_day = substr($partition_yearday, 4);
				if ($tmp_year < $fyear){
					db_execute('DROP TABLE ' . $part['TABLE_NAME']);
				} elseif ($tmp_year = $fyear) {
					if ($tmp_day < $fday) {
						db_execute('DROP TABLE ' . $part['TABLE_NAME']);
					}
				}
			}
		}

		heuristics_debug('NOTE: Updating Percentile Data for Queries');

		add_remove_percentile_data();

		/* set a long maximum string length ~ 1GB */
		db_execute('SET SESSION group_concat_max_len = 1048576000');

		/* determine how to aggregate */
		$custom = read_config_option('heuristics_custom_column');
		$lowest = read_config_option('heuristics_low_level_agg');

		if ($custom == 'none') {
			$custom = "'-' AS custom";
		} else {
			$custom = "custom";
		}

		if ($lowest == 'resreq') {
			/* start aggregation to three levels - first is a heavy lift */
			heuristics_debug('NOTE: Calculating Historical Detailed Averages - All Levels including ResReq');

			$field_list = "clusterid, queue, $custom, reqCpus, projectName, resReq, run_time, max_memory, mem_used, pend_time";
			$group_by   = "clusterid, queue, custom, reqCpus, projectName, resReq";

			insert_heuristics_by_group($now, $field_list, $group_by, 'Detailed to ResReq');
		}

		if ($lowest == 'project') {
			// Todo - Project Aggregation
			if (read_config_option('grid_project_group_aggregation') == 'on') {
				$delim = read_config_option('grid_job_stats_project_delimiter');
				$count = read_config_option('grid_job_stats_project_level_number');

				for($i = 1; $i <= $count; $i++) {
					/* continuing aggregation to three levels - second should be much less */
					heuristics_debug('NOTE: Calculating Historical Averages @ Project Level ' . $i . ' - Excluding resReq');

					$project  = "SUBSTRING_INDEX(projectName, '$delim', $i) AS projectName";
					$project1 = "SUBSTRING_INDEX(projectName, '$delim', $i)";

					$field_list = "clusterid, queue, $custom, reqCpus, $project, '-' AS resReq, run_time, max_memory, mem_used, pend_time";
					$group_by   = "clusterid, queue, custom, reqCpus, $project1, resReq";

					insert_heuristics_by_group($now, $field_list, $group_by, 'No ResReq');
				}
			} else {
				/* continuing aggregation to three levels - second should be much less */
				heuristics_debug('NOTE: Calculating Historical Averages - Excluding resReq');

				$field_list = "clusterid, queue, $custom, reqCpus, projectName, '-' AS resReq, run_time, max_memory, mem_used, pend_time";
				$group_by   = "clusterid, queue, custom, reqCpus, projectName, resReq";

				insert_heuristics_by_group($now, $field_list, $group_by, 'No ResReq');
			}
		}

		/* continuing aggregation to three levels - final should be least */
		heuristics_debug('NOTE: Calculating Historical Averages - Excluding projectName, resReq');

		$field_list = "clusterid, queue, $custom, reqCpus, '-' AS projectName, '-' AS resReq, run_time, max_memory, mem_used, pend_time";
		$group_by   = "clusterid, queue, custom, reqCpus, projectName, resReq";

		insert_heuristics_by_group($now, $field_list, $group_by, 'No Project, No ResReq');

		if ($custom != 'none') {
			/* continuing aggregation to three levels - final should be least */
			heuristics_debug('NOTE: Calculating Historical Averages - Excluding custom, projectName, resReq');

			$field_list = "clusterid, queue, '-' AS custom, reqCpus, '-' AS projectName, '-' AS resReq, run_time, max_memory, mem_used, pend_time";
			$group_by   = "clusterid, queue, custom, reqCpus, projectName, resReq";

			insert_heuristics_by_group($now, $field_list, $group_by, 'No Custom, No Project, No ResReq');
		}

		/* rollup user statistics */
		$old_table_current = db_fetch_row("SHOW TABLES LIKE 'grid_heuristics_user_history_v$year_day'");
		if (!$force && empty($old_table_current)) {
			$interim_table = 'grid_heuristics_user_history_today_' . time();
			db_execute('START TRANSACTION');
			db_execute("CREATE TABLE $interim_table LIKE grid_heuristics_user_history_today");
			db_execute("RENAME TABLE grid_heuristics_user_history_today TO grid_heuristics_user_history_v$year_day, $interim_table TO grid_heuristics_user_history_today");
			db_execute('COMMIT');
		}

		// Prune data if no partitions
		if (read_config_option('grid_partitioning_enable') == '') {
			/* get how many records do we delete per pass */
			$delete_size = read_config_option('grid_db_maint_delete_size');

			/*get purge date, which is same as partitioned table data retention period*/
			$max_date = date('Ymd',strtotime('-' . read_config_option('heuristics_days') . 'days')) ;

			if (strlen($max_date)) {
				while (1) {
					heuristics_debug("Deleting $delete_size Records from grid_heuristics_percentiles where `partition` < '$max_date' ");
					$deleted_rows = db_fetch_cell_prepared("SELECT COUNT(clusterid)
						FROM grid_heuristics_percentiles
						WHERE `partition` < ?
						LIMIT $delete_size", array($max_date));

					if ($deleted_rows == 0) break;

					db_execute_prepared("DELETE FROM grid_heuristics_percentiles
						WHERE `partition` < ?
						LIMIT $delete_size", array($max_date));
				}
			}
		}

		heuristics_debug('Removing Stale Heuristics Data');
		db_execute_prepared("DELETE FROM grid_heuristics WHERE last_updated < ?", array($now));

		heuristics_debug('Optimizing the heuristics tables.');
		db_execute('OPTIMIZE TABLE grid_heuristics');

		/* take the start time to log performance data */
		$dend = microtime(true);

		$cacti_stats = sprintf('Time:%01.4f', round($dend-$end,4));

		/* log to the database */
		set_config_option('stats_heuristics_daily', $cacti_stats);

		/* log to the logfile */
		cacti_log('HEURISTIC STATS DAILY: ' . $cacti_stats ,true,'SYSTEM');

		//After grid maint, hueristics maint, run partition tables backup, partition tables optimization consequently

		//run partition tables backup
		if (read_config_option('run_partition_backup') > 0) {
			exec(read_config_option('path_php_binary') . ' -q ' . $config['base_path'] . '/plugins/grid/database_backup_partitions.php -y');
			set_config_option('run_partition_backup', 0);
		}

		//lastly background run partition tables optimization
		$optimize_args = '';
		if (read_config_option('run_optimization') > 0) {
			$optimize_args .= '--ntables ';
		}

		$new_partition_tables = read_config_option('run_partition_optimization');
		if (!empty($new_partition_tables)) {
			$optimize_args .= "--otables=$new_partition_tables ";
		}

		if (!empty($optimize_args)) {
			$path_rtm_top=grid_get_path_rtm_top();
			$cmd = read_config_option('path_php_binary') . " $path_rtm_top/cacti/plugins/grid/database_optimization.php $optimize_args >/dev/null &";
			cacti_log('OPTIMIZE cmd: ' . $cmd ,true,'SYSTEM');
			exec ($cmd);
		}

		remove_process_entry(0, 'HEURISTICSMAINT');
	}
}

function insert_heuristics_by_group($now, $field_list = null, $group_by = null, $message = null) {
	// record the start time
	$start = microtime(true);

	if ($field_list == null) {
		$field_list = 'clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time';
	}

	if ($group_by == null) {
		$group_by   = 'clusterid, queue, custom, projectName, resReq, reqCpus';
	}

	if ($message == null) {
		$message    = 'Default Roll-up';
	}

	$sub_query = create_heuristics_sub_query($field_list);

	heuristics_debug("INSERT INTO grid_heuristics
		FROM (
			SELECT clusterid, queue, custom, projectName, resReq, reqCpus,
			COUNT(reqCpus) AS jobs,
			SUM(reqCpus) AS cores,
			AVG(run_time) AS run_avg,
			MAX(run_time) AS run_max,
			MIN(run_time) AS run_min,
			STDDEV(run_time) AS run_stddev,
			GROUP_CONCAT(run_time ORDER BY run_time SEPARATOR ',') AS runtimes,
			GROUP_CONCAT(max_memory ORDER BY max_memory SEPARATOR ',') AS memories,
			GROUP_CONCAT(pend_time ORDER BY pend_time SEPARATOR ',') AS pendings,
			AVG(max_memory) AS mem_avg,
			MAX(max_memory) AS mem_max,
			MIN(max_memory) AS mem_min,
			STDDEV(max_memory) AS mem_stddev,
			AVG(pend_time) AS pend_avg,
			MAX(pend_time) AS pend_max,
			MIN(pend_time) AS pend_min,
			STDDEV(pend_time) AS pend_stddev,
			3600/AVG(run_time) AS jph_avg,
			3600/(AVG(run_time)+3*STDDEV(run_time)) AS jph_3std,
			'$now' AS last_updated
			FROM $sub_query
			GROUP BY $group_by
		) AS rs
		ON DUPLICATE KEY UPDATE
			last_updated = VALUES(last_updated)");
	db_execute("INSERT INTO grid_heuristics
		(clusterid, queue, custom, projectName, resReq, reqCpus, jobs, cores,
		run_avg, run_max, run_min, run_stddev,
		run_25thp, run_median, run_75thp, run_90thp,
		mem_avg, mem_max, mem_min, mem_stddev,
		mem_25thp, mem_median, mem_75thp, mem_90thp,
		pend_avg, pend_max, pend_min, pend_stddev,
		pend_25thp, pend_median, pend_75thp, pend_90thp,
		jph_avg, jph_3std, last_updated)
		SELECT clusterid, queue, custom, projectName, resReq, reqCpus,
		jobs, cores,
		run_avg, run_max, run_min, run_stddev,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(runtimes, ',', 25/100 * jobs + 1), ',', -1) AS DECIMAL) AS `run_25thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(runtimes, ',', 50/100 * jobs + 1), ',', -1) AS DECIMAL) AS `run_median`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(runtimes, ',', 75/100 * jobs + 1), ',', -1) AS DECIMAL) AS `run_75thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(runtimes, ',', 90/100 * jobs + 1), ',', -1) AS DECIMAL) AS `run_90thp`,
		mem_avg, mem_max, mem_min, mem_stddev,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(memories, ',', 25/100 * jobs + 1), ',', -1) AS DECIMAL) AS `mem_25thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(memories, ',', 50/100 * jobs + 1), ',', -1) AS DECIMAL) AS `mem_median`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(memories, ',', 75/100 * jobs + 1), ',', -1) AS DECIMAL) AS `mem_75thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(memories, ',', 90/100 * jobs + 1), ',', -1) AS DECIMAL) AS `mem_90thp`,
		pend_avg, pend_max, pend_min, pend_stddev,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pendings, ',', 25/100 * jobs + 1), ',', -1) AS DECIMAL) AS `pend_25thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pendings, ',', 50/100 * jobs + 1), ',', -1) AS DECIMAL) AS `pend_median`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pendings, ',', 75/100 * jobs + 1), ',', -1) AS DECIMAL) AS `pend_75thp`,
		CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pendings, ',', 90/100 * jobs + 1), ',', -1) AS DECIMAL) AS `pend_90thp`,
		jph_avg, jph_3std, last_updated
		FROM (
			SELECT clusterid, queue, custom, projectName, resReq, reqCpus,
			COUNT(reqCpus) AS jobs,
			SUM(reqCpus) AS cores,
			AVG(run_time) AS run_avg,
			MAX(run_time) AS run_max,
			MIN(run_time) AS run_min,
			STDDEV(run_time) AS run_stddev,
			GROUP_CONCAT(run_time ORDER BY run_time SEPARATOR ',') AS runtimes,
			GROUP_CONCAT(max_memory ORDER BY max_memory SEPARATOR ',') AS memories,
			GROUP_CONCAT(pend_time ORDER BY pend_time SEPARATOR ',') AS pendings,
			AVG(max_memory) AS mem_avg,
			MAX(max_memory) AS mem_max,
			MIN(max_memory) AS mem_min,
			STDDEV(max_memory) AS mem_stddev,
			AVG(pend_time) AS pend_avg,
			MAX(pend_time) AS pend_max,
			MIN(pend_time) AS pend_min,
			STDDEV(pend_time) AS pend_stddev,
			3600/AVG(run_time) AS jph_avg,
			3600/(AVG(run_time)+3*STDDEV(run_time)) AS jph_3std,
			'$now' AS last_updated
			FROM $sub_query
			GROUP BY $group_by
		) AS rs
		ON DUPLICATE KEY UPDATE
			jobs = VALUES(jobs),
			cores = VALUES(cores),
			run_avg = VALUES(run_avg),
			run_max = VALUES(run_max),
			run_min = VALUES(run_min),
			run_stddev = VALUES(run_stddev),
			run_25thp = VALUES(run_25thp),
			run_median = VALUES(run_median),
			run_75thp = VALUES(run_75thp),
			run_90thp = VALUES(run_90thp),
			mem_avg = VALUES(mem_avg),
			mem_max = VALUES(mem_max),
			mem_min = VALUES(mem_min),
			mem_stddev = VALUES(mem_stddev),
			mem_25thp = VALUES(mem_25thp),
			mem_median = VALUES(mem_median),
			mem_75thp = VALUES(mem_75thp),
			mem_90thp = VALUES(mem_90thp),
			pend_avg = VALUES(pend_avg),
			pend_max = VALUES(pend_max),
			pend_min = VALUES(pend_min),
			pend_stddev = VALUES(pend_stddev),
			pend_25thp = VALUES(pend_25thp),
			pend_median = VALUES(pend_median),
			pend_75thp = VALUES(pend_75thp),
			pend_90thp = VALUES(pend_90thp),
			jph_avg = VALUES(jph_avg),
			jph_3std = VALUES(jph_3std),
			last_updated = VALUES(last_updated)");

	$end = microtime(true);

	heuristics_debug(sprintf("Total Time %4.2f for %s To Complete", $end - $start, $message));
}

function create_heuristics_sub_query($field_list, $sql_where = '') {
	if (read_config_option('grid_partitioning_enable') == '') {
		return "(SELECT $field_list FROM grid_heuristics_percentiles " . (strlen($sql_where) ? "WHERE $sql_where":'') . ' ) AS subquery';
	} else {
		$query  = '';

		/* get the early date */
		$days       = read_config_option('heuristics_days');
		$early_date = date('Y-m-d H:i:s', time() - (86400 * $days));
		$tables     = partition_get_partitions_for_query('grid_jobs_finished', $early_date, date('Y-m-d 00:00:00'));

		if (cacti_sizeof($tables)) {
			foreach($tables as $table) {
				// Fix the table name
				$table = str_replace('grid_jobs_finished', 'grid_heuristics_percentiles', $table);

				if (!db_table_exists($table)) {
					db_execute("CREATE TABLE IF NOT EXISTS $table LIKE grid_heuristics_percentiles");
				}

				if (strlen($query)) {
					$query .= ' UNION ALL ';
				}

				$query .= "SELECT $field_list FROM $table " . (strlen($sql_where) ? "WHERE $sql_where":'');
			}

			if (strlen($query)) {
				$query = "($query) AS subquery";
			}
		}

		if (strlen($query)) {
			return $query;
		} else {
			return "(SELECT $field_list FROM grid_heuristics_percentiles " . (strlen($sql_where) ? "WHERE $sql_where":'') . ' ) AS subquery';
		}
	}
}

function add_remove_percentile_data() {
	global $cnn_id;

	/* get the early date */
	$days       = read_config_option('heuristics_days');
	$early_date = date('Y-m-d H:i:s', time() - (86400 * $days));

	heuristics_debug('Adding Recently Finished Jobs to Percentile table');

	/* purge the finished percentiles table first */
	db_execute('TRUNCATE TABLE grid_heuristics_percentiles');

	$cluster_string91   = '';
	$cluster_string_low = '';
	$cluster_versions   = db_fetch_assoc('SELECT clusterid, grid_pollers.lsf_version
		FROM grid_pollers
		INNER JOIN grid_clusters
		ON grid_clusters.poller_id=grid_pollers.poller_id');

	if (cacti_sizeof($cluster_versions)) {
		$x = 0;
		$y = 0;

		foreach($cluster_versions as $cluster_version) {
			if (lsf_version_not_lower_than($cluster_version['lsf_version'], '91')) {
				if ($x == 0) {
					$cluster_string91 = "('" . $cluster_version['clusterid'];
				} else {
					$cluster_string91 .= "', '" . $cluster_version['clusterid'];
				}
				$x++;
			} else {
				if ($y == 0) {
					$cluster_string_low = "('" . $cluster_version['clusterid'];
				} else {
					$cluster_string_low .= "', '" . $cluster_version['clusterid'];
				}
				$y++;
			}
		}

		if (!empty($cluster_string91)){
			$cluster_string91 .= "')";
		}

		if (!empty($cluster_string_low)){
			$cluster_string_low .= "')";
		}
	}

	$custom = read_config_option('heuristics_custom_column');

	if ($custom != 'none') {
		$custom = "$custom AS custom";
	} else {
		$custom = "'-' AS custom";
	}

	if (!empty($cluster_string91)){
		heuristics_debug("INSERT INTO grid_heuristics_percentiles
			(clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time, `partition`)
			SELECT clusterid, queue, $custom, projectName, effectiveResreq,
			num_cpus, run_time, max_memory, mem_used, pend_time, DATE_FORMAT(end_time,'%Y%m%d') AS mypartition
			FROM grid_jobs_finished
			WHERE stat = 'DONE'
			AND clusterid IN $cluster_string91
			AND run_time > 0");
		db_execute("INSERT INTO grid_heuristics_percentiles
			(clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time, `partition`)
			SELECT clusterid, queue, $custom, projectName, effectiveResreq,
			num_cpus, run_time, max_memory, mem_used, pend_time, DATE_FORMAT(end_time,'%Y%m%d') AS mypartition
			FROM grid_jobs_finished
			WHERE stat = 'DONE'
			AND clusterid IN $cluster_string91
			AND run_time > 0");
	}

	if (!empty($cluster_string_low)) {
		db_execute("INSERT INTO grid_heuristics_percentiles
			(clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time, `partition`)
			SELECT clusterid, queue, $custom, projectName, res_requirements,
			num_cpus, run_time, max_memory, mem_used, pend_time, DATE_FORMAT(end_time,'%Y%m%d') AS mypartition
			FROM grid_jobs_finished
			WHERE stat = 'DONE'
			AND clusterid IN $cluster_string_low
			AND run_time > 0");
	}

	if (read_config_option('grid_partitioning_enable') == 'on') {
		$partitions = array_rekey(
			db_fetch_assoc_prepared("SELECT `partition`
				FROM grid_table_partitions
				WHERE table_name = 'grid_jobs_finished'
				AND max_time > ?
				ORDER BY max_time DESC ", array($early_date)),
			'partition', 'partition'
		);

		// Remove old partitions first
		$part_tables = array_rekey(
			db_fetch_assoc('SELECT TABLE_NAME
				FROM information_schema.TABLES
				WHERE TABLE_SCHEMA="cacti"
				AND TABLE_NAME LIKE "grid_heuristics_percentiles_v%"'),
			'TABLE_NAME', 'TABLE_NAME'
		);

		heuristics_debug('Checking for Old Partitions and removing them');

		if (cacti_sizeof($part_tables)) {
			foreach($part_tables as $t) {
				$part_no = str_replace('grid_heuristics_percentiles_v', '', $t);
				$found = false;

				foreach($partitions as $p) {
					// Format the partition correctly
					$p = substr('000' . $p, -3);

					if ($p == $part_no) {
						$found = true;
						break;
					}
				}

				if (!$found) {
					heuristics_debug("Removing old Partition table $t. Out of range.");
					db_execute("DROP TABLE $t");
				} else {
					heuristics_debug("Keeping Partition table $t.  Still in range.");
				}
			}
		}

		if (cacti_sizeof($partitions)) {
			foreach ($partitions as $p) {
				$pn = substr('000' . $p, -3);

				if (db_table_exists('grid_heuristics_percentiles_v' . $pn)) {
					heuristics_debug("Skipping partition $pn Since it exists already.");
				} else {
					heuristics_debug ("Creating partition number is:$pn");

					db_execute("CREATE TABLE grid_heuristics_percentiles_v$pn LIKE grid_heuristics_percentiles");

					if (!empty($cluster_string91)){
						db_execute_prepared("INSERT INTO grid_heuristics_percentiles_v$pn
							(clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time, `partition`)
							SELECT clusterid, queue, $custom, projectName, effectiveResreq,
							num_cpus, run_time, max_memory, mem_used, pend_time, DATE_FORMAT(end_time,'%Y%m%d') AS mypartition
							FROM grid_jobs_finished_v$pn
							WHERE stat = 'DONE'
							AND clusterid IN $cluster_string91
							AND run_time > 0
							AND end_time >= ?", array($early_date));
					}

					if (!empty($cluster_string_low)) {
						db_execute_prepared("INSERT INTO grid_heuristics_percentiles_v$pn
							(clusterid, queue, custom, projectName, resReq, reqCpus, run_time, max_memory, mem_used, pend_time, `partition`)
							SELECT clusterid, queue, $custom, projectName, res_requirements,
							num_cpus, run_time, max_memory, mem_used, pend_time, DATE_FORMAT(end_time,'%Y%m%d') AS mypartition
							FROM grid_jobs_finished_v$pn
							WHERE stat = 'DONE'
							AND clusterid IN $cluster_string_low
							AND run_time > 0
							AND end_time >= ?", array($early_date));
					}
				}
			}
		}
	}
}

function heuristics_debug($message) {
	global $debug;

	if (defined('CACTI_DATE_TIME_FORMAT')) {
		$date = date(CACTI_DATE_TIME_FORMAT);
	} else {
		$date = date('Y-m-d H:i:s');
	}

	if ($debug) {
		print $date . ' - DEBUG: ' . trim($message) . PHP_EOL;
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	print 'RTM Cluster Heuritics Poller ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	print "Usage:\n";
	print "poller_heuristics.php [-f|--force] [-p|--percentiles] [-d|--debug] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-f | --force       - Force Historical Aggregation\n";
	print "-p | --percentiles - Force Historical Percentile Calculation\n";
	print "-d | --debug       - Display verbose output during execution\n";
	print "-v -V --version    - Display this help message\n";
	print "-h --help          - Display this help message\n";
}

/**
 * This function will manage a partitioned table by checking for time to create
 */
function heuristics_partition_manage($table = 'grid_heuristics_percentiles') {
	$heuristics_deleted = 0;

	if (heuristics_partition_check('grid_heuristics_percentiles')) {
		heuristics_partition_create('grid_heuristics_percentiles');
		$heuristics_deleted = heuristics_partition_remove('grid_heuristics_percentiles');
	}

	return $heuristics_deleted;
}

/**
 * This function will create a new partition for the specified table.
 */
function heuristics_partition_create($table) {
	/* determine the format of the table name */
	$time    = time();
	$cformat = "d" . date("Ymd", $time);
	$lnow    = date('Y-m-d', $time+86400);

	cacti_log("HEURISTICS: Creating new partition for table '$table' using '$cformat'", false, "SYSTEM");
	heuristics_debug("Creating new partition '$cformat'");
	db_execute("ALTER TABLE `$table` REORGANIZE PARTITION dMaxValue INTO (
		PARTITION $cformat VALUES LESS THAN (TO_DAYS('$lnow')),
		PARTITION dMaxValue VALUES LESS THAN MAXVALUE)");
}

/**
 * This function will remove all old partitions for the specified table.
 */
function heuristics_partition_remove($table) {
	global $db_default;

	$heuristics_deleted   = 0;
	$number_of_partitions = db_fetch_assoc_prepared("SELECT *
		FROM `information_schema`.`partitions`
		WHERE TABLE_SCHEMA=? AND TABLE_NAME=?
		ORDER BY partition_ordinal_position", array($db_default, $table));

	$days = read_config_option("heuristics_days");
	heuristics_debug("There are currently '" . cacti_sizeof($number_of_partitions) . "' We will keep '$days' days of them.");

	if ($days > 0) {
		$user_partitions = cacti_sizeof($number_of_partitions) - 1;
		if ($user_partitions >= $days) {
			$i = 0;

			while ($user_partitions > $days) {
				$oldest = $number_of_partitions[$i];
				cacti_log("HEURISTICS: Removing old partition 'd" . $oldest["PARTITION_NAME"] . "'", false, "SYSTEM");
				heuristics_debug("Removing partition '" . $oldest["PARTITION_NAME"] . "'");
				db_execute("ALTER TABLE `" . $db_default . "`.`$table` DROP PARTITION " . $oldest["PARTITION_NAME"]);
				$i++;
				$user_partitions--;
				$heuristics_deleted++;
			}
		}
	}

	return $heuristics_deleted;
}

function heuristics_partition_check($table) {
	global $db_default;

	/* find date of last partition */
	$last_part = db_fetch_cell_prepared("SELECT PARTITION_NAME
		FROM `information_schema`.`partitions`
		WHERE TABLE_SCHEMA=? AND TABLE_SCHEMA=?
		ORDER BY partition_ordinal_position DESC
		LIMIT 1,1;", array($db_default, $table));

	$lformat   = str_replace("d", "", $last_part);
	$cformat   = date('Ymd');

	if ($cformat > $lformat) {
		return true;
	} else {
		return false;
	}
}

/*
create a memory table grid_clusters_reportdata for storing cluster level reporting results

reportid
	'fmemslots':		free memory slots availability report;
*/
function create_table_grid_clusters_reportdata() {
	db_execute("CREATE TABLE IF NOT EXISTS grid_clusters_reportdata (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`reportid` VARCHAR(20) NOT NULL DEFAULT '',
		`name` VARCHAR(20) NOT NULL DEFAULT '',
		`value` double NOT NULL DEFAULT '0',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`clusterid`,`reportid`,`name`)
		) ENGINE = MEMORY COMMENT = 'cluster level reporting results table';");

	db_execute("CREATE TABLE IF NOT EXISTS grid_clusters_queue_reportdata (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`reportid` VARCHAR(20) NOT NULL DEFAULT '',
		`name` VARCHAR(20) NOT NULL DEFAULT '',
		`value` double NOT NULL DEFAULT '0',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`clusterid`,`queue`,`reportid`,`name`)
		) ENGINE = MEMORY COMMENT = 'queue level reporting results table';");

}

function grid_reportdata_update_fmemslots() {
	$fmemslots = db_fetch_assoc("SELECT
		clusterid,
		SUM(1gSlots) AS free1gSlots, SUM(2gSlots) AS free2gSlots,
		SUM(4gSlots) AS free4gSlots, SUM(8gSlots) AS free8gSlots,
		SUM(16gSlots) AS free16gSlots, SUM(32gSlots) AS free32gSlots,
		SUM(64gSlots) AS free64gSlots, SUM(128gSlots) AS free128gSlots,
		SUM(256gSlots) AS free256gSlots, SUM(512gSlots) AS free512gSlots,
		SUM(1024gSlots) AS free1024gSlots
		FROM (
			SELECT clusterid, host,
			(CASE WHEN unReservedMem > 1024   THEN FLOOR(LEAST(freeSlots, unReservedMem/1024))   ELSE 0 END) AS 1gSlots,
			(CASE WHEN unReservedMem > 2048   THEN FLOOR(LEAST(freeSlots, unReservedMem/2048))   ELSE 0 END) AS 2gSlots,
			(CASE WHEN unReservedMem > 4096   THEN FLOOR(LEAST(freeSlots, unReservedMem/4096))   ELSE 0 END) AS 4gSlots,
			(CASE WHEN unReservedMem > 8192   THEN FLOOR(LEAST(freeSlots, unReservedMem/8192))   ELSE 0 END) AS 8gSlots,
			(CASE WHEN unReservedMem > 16384  THEN FLOOR(LEAST(freeSlots, unReservedMem/16384))  ELSE 0 END) AS 16gSlots,
			(CASE WHEN unReservedMem > 32768  THEN FLOOR(LEAST(freeSlots, unReservedMem/32768))  ELSE 0 END) AS 32gSlots,
			(CASE WHEN unReservedMem > 65536  THEN FLOOR(LEAST(freeSlots, unReservedMem/65536))  ELSE 0 END) AS 64gSlots,
			(CASE WHEN unReservedMem > 131072 THEN FLOOR(LEAST(freeSlots, unReservedMem/131072)) ELSE 0 END) AS 128gSlots,
			(CASE WHEN unReservedMem > 262144 THEN FLOOR(LEAST(freeSlots, unReservedMem/262144)) ELSE 0 END) AS 256gSlots,
			(CASE WHEN unReservedMem > 524288 THEN FLOOR(LEAST(freeSlots, unReservedMem/524288)) ELSE 0 END) AS 512gSlots,
			(CASE WHEN unReservedMem > 1048575 THEN FLOOR(LEAST(freeSlots, unReservedMem/1048575)) ELSE 0 END) AS 1024gSlots
			FROM (
				SELECT gh.clusterid, gh.host, SUM(IF(maxJobs<=numJobs,0,maxJobs-numJobs)) AS freeSlots,
				totalValue AS unReservedMem
				FROM grid_hosts AS gh
				INNER JOIN grid_hostinfo AS ghi
				ON gh.clusterid=ghi.clusterid
				AND gh.host=ghi.host
				INNER JOIN grid_hosts_resources AS ghr
				ON ghi.clusterid=ghr.clusterid
				AND ghi.host=ghr.host
				AND ghr.resource_name='mem'
				WHERE (gh.status NOT LIKE 'U%' AND gh.status NOT LIKE 'Closed%')
				GROUP BY gh.clusterid, gh.host
			) AS results
		) AS results1 GROUP BY clusterid");

//	$fmemslots = db_fetch_assoc("
//		SELECT clusterid,
//		SUM(1gSlots) AS free1gSlots, SUM(2gSlots) AS free2gSlots,
//		SUM(4gSlots) AS free4gSlots, SUM(8gSlots) AS free8gSlots,
//		SUM(16gSlots) AS free16gSlots, SUM(32gSlots) AS free32gSlots,
//		SUM(64gSlots) AS free64gSlots, SUM(128gSlots) AS free128gSlots,
//		SUM(256gSlots) AS free256gSlots
//		FROM (
//			SELECT *,
//			(CASE WHEN least(mem, maxMem-resMem) > 1024 THEN least(freeSlots, floor(least(mem,maxMem-resMem)/1024)) ELSE 0 END) AS 1gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 2048 THEN least(freeSlots, floor(least(mem,maxMem-resMem)/2048)) ELSE 0 END) AS 2gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 4096 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/4096)) ELSE 0 END) AS 4gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 8192 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/8192)) ELSE 0 END) AS 8gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 16384 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/16384)) ELSE 0 END) AS 16gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 32768 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/32768)) ELSE 0 END) AS 32gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 65536 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/65536)) ELSE 0 END) AS 64gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 131072 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/131072)) ELSE 0 END) AS 128gSlots,
//			(CASE WHEN least(mem,maxMem-resMem) > 262144 THEN least(freeSlots,floor(least(mem,maxMem-resMem)/262144)) ELSE 0 END) AS 256gSlots
//			FROM (
//				SELECT gj.clusterid, ghi.host, sum(IF(maxJobs<=numRun,0,maxJobs-numRun)) AS freeSlots,
//				maxMem, sum(mem_reserved/1024) AS resMem, mem, sum(num_cpus) AS usedSlots
//				FROM  grid_jobs AS gj
//				INNER JOIN grid_hostinfo AS ghi
//				ON gj.clusterid=ghi.clusterid
//				AND gj.exec_host=ghi.host
//				INNER JOIN grid_hosts AS gh
//				ON gh.clusterid=gj.clusterid
//				AND gh.host=gj.exec_host
//				INNER JOIN grid_load AS gl
//				ON gl.clusterid=gj.clusterid
//				AND gl.host=gj.exec_host
//				group by gj.clusterid, ghi.host
//			) AS results
//		) AS results1 GROUP BY clusterid;");

	$format = array(
		'clusterid',
		'reportid',
		'name',
		'value',
		'present'
	);

	$duplicate = ' ON DUPLICATE KEY UPDATE value = VALUES(value), present = VALUES(present)';

	$fmemslot_columns = array (
		'free1gSlots',
		'free2gSlots',
		'free4gSlots',
		'free8gSlots',
		'free16gSlots',
		'free32gSlots',
		'free64gSlots',
		'free128gSlots',
		'free256gSlots',
		'free512gSlots',
		'free1024gSlots'
	);

	$records = array();

	if (cacti_sizeof($fmemslots)) {
		foreach($fmemslots as $fmemslot) {
			foreach ($fmemslot_columns as $c) {
				$records[] = array(
					'clusterid' => $fmemslot['clusterid'],
					'reportid' => 'fmemslots',
					'name' => $c,
					'value' => $fmemslot[$c],
					'present' => 1
				);
			}
		}
	}

	grid_pump_records($records, 'grid_clusters_reportdata', $format, false, $duplicate);
	return cacti_sizeof($records);
}

function grid_reportdata_update_fmemslots_queue() {
	$fmemslots = db_fetch_assoc("SELECT
		clusterid, queue,
		SUM(1gSlots) AS free1gSlots, SUM(2gSlots) AS free2gSlots,
		SUM(4gSlots) AS free4gSlots, SUM(8gSlots) AS free8gSlots,
		SUM(16gSlots) AS free16gSlots, SUM(32gSlots) AS free32gSlots,
		SUM(64gSlots) AS free64gSlots, SUM(128gSlots) AS free128gSlots,
		SUM(256gSlots) AS free256gSlots, SUM(512gSlots) AS free512gSlots,
		SUM(1024gSlots) AS free1024gSlots
		FROM (
			SELECT clusterid, host, queue,
			(CASE WHEN unReservedMem > 1024   THEN FLOOR(LEAST(freeSlots, unReservedMem/1024))   ELSE 0 END) AS 1gSlots,
			(CASE WHEN unReservedMem > 2048   THEN FLOOR(LEAST(freeSlots, unReservedMem/2048))   ELSE 0 END) AS 2gSlots,
			(CASE WHEN unReservedMem > 4096   THEN FLOOR(LEAST(freeSlots, unReservedMem/4096))   ELSE 0 END) AS 4gSlots,
			(CASE WHEN unReservedMem > 8192   THEN FLOOR(LEAST(freeSlots, unReservedMem/8192))   ELSE 0 END) AS 8gSlots,
			(CASE WHEN unReservedMem > 16384  THEN FLOOR(LEAST(freeSlots, unReservedMem/16384))  ELSE 0 END) AS 16gSlots,
			(CASE WHEN unReservedMem > 32768  THEN FLOOR(LEAST(freeSlots, unReservedMem/32768))  ELSE 0 END) AS 32gSlots,
			(CASE WHEN unReservedMem > 65536  THEN FLOOR(LEAST(freeSlots, unReservedMem/65536))  ELSE 0 END) AS 64gSlots,
			(CASE WHEN unReservedMem > 131072 THEN FLOOR(LEAST(freeSlots, unReservedMem/131072)) ELSE 0 END) AS 128gSlots,
			(CASE WHEN unReservedMem > 262144 THEN FLOOR(LEAST(freeSlots, unReservedMem/262144)) ELSE 0 END) AS 256gSlots,
			(CASE WHEN unReservedMem > 524288 THEN FLOOR(LEAST(freeSlots, unReservedMem/524288)) ELSE 0 END) AS 512gSlots,
			(CASE WHEN unReservedMem > 1048575 THEN FLOOR(LEAST(freeSlots, unReservedMem/1048575)) ELSE 0 END) AS 1024gSlots
			FROM (
				SELECT gh.clusterid, gh.host, gqh.queue, SUM(IF(maxJobs<=numJobs,0,maxJobs-numJobs)) AS freeSlots,
				totalValue AS unReservedMem
				FROM grid_hosts AS gh
				INNER JOIN grid_hostinfo AS ghi
				ON gh.clusterid=ghi.clusterid
				AND gh.host=ghi.host
				INNER JOIN grid_hosts_resources AS ghr
				ON ghi.clusterid=ghr.clusterid
				AND ghi.host=ghr.host
				AND ghr.resource_name='mem'
				INNER JOIN grid_queues_hosts AS gqh ON gqh.clusterid=ghr.clusterid AND gqh.host=ghr.host
				WHERE (gh.status NOT LIKE 'U%' AND gh.status NOT LIKE 'Closed%')
				GROUP BY gh.clusterid, gh.host, gqh.queue
			) AS results
		) AS results1 GROUP BY clusterid, queue");

	$format = array(
		'clusterid',
		'queue',
		'reportid',
		'name',
		'value',
		'present'
	);

	$duplicate = ' ON DUPLICATE KEY UPDATE value = VALUES(value), present = VALUES(present)';

	$fmemslot_columns = array (
		'free1gSlots',
		'free2gSlots',
		'free4gSlots',
		'free8gSlots',
		'free16gSlots',
		'free32gSlots',
		'free64gSlots',
		'free128gSlots',
		'free256gSlots',
		'free512gSlots',
		'free1024gSlots'
	);

	$records = array();

	if (cacti_sizeof($fmemslots)) {
		foreach($fmemslots as $fmemslot) {
			foreach ($fmemslot_columns as $c) {
				$records[] = array(
					'clusterid' => $fmemslot['clusterid'],
                    'queue' => $fmemslot['queue'],
                    'reportid' => 'fmemslots',
					'name' => $c,
					'value' => $fmemslot[$c],
					'present' => 1
				);
			}
		}
	}

	grid_pump_records($records, 'grid_clusters_queue_reportdata', $format, false, $duplicate);
	return cacti_sizeof($records);
}

function grid_reportdata_update() {
	/*create table grid_clusters_reportdata if not exists*/
	create_table_grid_clusters_reportdata();

	/* prep for subsequent deletion */
	db_execute('UPDATE grid_clusters_reportdata SET present=0 WHERE present=1');

	/*1. update reportid='fmemslots'*/
	$start_time = microtime(true);
	$count_fmemslots = grid_reportdata_update_fmemslots();
	$fmemslots_time = microtime(true) - $start_time;

	/*2. reserved here for updating other types of reportid in future*/

	//update reportid = 'tput1hour' and 'tput24hour'
	$start_time = microtime(true);
	calculate_job_throughput($count_tput1hour, $count_tput24hour);
	$tput_time = microtime(true) - $start_time;

	/* get rid of old data */
	db_execute('DELETE FROM grid_clusters_reportdata WHERE present=0');

	/* update queue level reportid='fmemslots'*/
	db_execute('UPDATE grid_clusters_queue_reportdata SET present=0 WHERE present=1');
	$start_time = microtime(true);
	$count_fmemslots_queue = grid_reportdata_update_fmemslots_queue();
	$fmemslots_queue_time = microtime(true) - $start_time;
	db_execute('DELETE FROM grid_clusters_queue_reportdata WHERE present=0');

	/* record the end time */
	$end_time = microtime(true);

	cacti_log('GRIDREPORT STATS: Time:' . round($fmemslots_time, 2) . ' fmemslots:' . $count_fmemslots, true, 'SYSTEM');
	cacti_log('GRIDREPORT STATS: Time:' . round($fmemslots_queue_time, 2) . ' fmemslotsqueue:' . $count_fmemslots_queue, true, 'SYSTEM');
	cacti_log('GRIDREPORT STATS: Time:' . round($tput_time, 2) . ' tput1hour:' . $count_tput1hour, true, 'SYSTEM');
	cacti_log('GRIDREPORT STATS: Time:' . round($tput_time, 2) . ' tput24hour:' . $count_tput24hour, true, 'SYSTEM');
}
