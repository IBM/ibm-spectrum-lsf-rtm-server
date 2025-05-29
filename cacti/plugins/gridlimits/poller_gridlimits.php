#!/usr/bin/env php
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['library_path'] . '/poller.php');

// Include core files
include_once(dirname(__FILE__) . '/../grid/lib/grid_partitioning.php');
include_once(dirname(__FILE__) . '/../grid/lib/grid_functions.php');

ini_set('memory_limit', '-1');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

/* take the start time to log performance data */
$start = microtime(true);

$debug      = false;
$force      = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-f':
	case '--force':
		$force = true;
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

$poller_interval = read_config_option('poller_interval');

if (detect_and_correct_running_processes(0, 'GRIDLIMITS', $poller_interval*3) || $force) {
	$last_run  = read_config_option('gridlimits_lastrun');
	$now       = time();
	$pass_last = $last_run;

	if (empty($last_run)) {
		$last_run = $now;
	}

	set_config_option('gridlimits_lastrun', $now);

	if (date('z', $now) != date('z', $last_run)) {
		$run_partition = true;
	} else {
		$run_partition = false;
	}

	// Get rollup statistics for the user interface
	$limits = do_rollup($pass_last);

	// Dump old records
	do_purge();

	// Partition the history and the job mapping tables
	if ($run_partition) {
		gridlimits_manage_partitions();
	}

	// Record end time for statistics and log statistics
	$end = microtime(true);

	$stats = 'GRIDLIMITS STATS: ' .
		'Time:' . round($end - $start, 2) .
		', Limits:' . $limits['limits'] .
		', History:' . $limits['history'] .
		', Clusters: ' . $limits['clusters'];

	cacti_log($stats, false, 'SYSTEM');

	remove_process_entry(0, 'GRIDLIMITS');

	gridlimits_debug($stats);
}

function do_purge() {
	$retention = read_config_option('grid_detail_data_retention');

	preg_match_all('!\d+!', $retention, $matches);

	$num = $matches[0][0];
	$str = preg_replace('/[0-9]+/', '', $retention);

	# Translate into the string that the MySQL INTERVAL needs
	if ($str == 'days') {
		$str = 'day';
	} elseif ($str == 'weeks') {
		$str = 'week';
	} elseif ($str == 'months') {
		$str = 'month';
	} elseif ($str == 'years') {
		$str = 'year';
	}

	db_execute('DELETE FROM grid_limits
		WHERE present = 0
		AND last_updated < DATE_SUB(now(), INTERVAL ' . $num . ' ' . $str . ')');

    db_execute('DELETE FROM grid_limits_history
		WHERE last_seen < DATE_SUB(now(), INTERVAL ' . $num . ' ' . $str . ')');
}

function do_rollup($last_run) {
	$last_updated = db_fetch_assoc("SELECT clusterid, MAX(last_updated) AS last_updated
		FROM grid_limits_usage
		GROUP BY clusterid");

	$limits['clusters'] = cacti_sizeof($last_updated);
	$limits['limits']   = 0;
	$limits['history']  = 0;

	if ($limits['clusters'] > 0) {
		foreach($last_updated as $c) {
			$format = array(
				'clusterid', 'limit_name', 'clusters', 'resources_usage',
                'slots_usage', 'slots_limit',
                'mem_usage', 'mem_limit',
                'swp_usage', 'swp_limit',
                'tmp_usage', 'tmp_limit',
                'jobs_usage', 'jobs_limit',
                'fwd_tasks_usage', 'fwd_tasks_limit', 'last_updated'
			);

			$records = db_fetch_assoc_prepared("SELECT
				clusterid, limit_name, clusters, resources AS resources_usage,
				slots_usage, slots_limit,
				mem_usage, mem_limit,
				swp_usage, swp_limit,
				tmp_usage, tmp_limit,
				jobs_usage, jobs_limit,
				fwd_tasks_usage, fwd_tasks_limit, last_updated
				FROM grid_limits_usage
				WHERE clusterid = ?
				AND last_updated = ?",
				array($c['clusterid'], $c['last_updated']));

			$duplicate = 'ON DUPLICATE KEY UPDATE
				clusters=VALUES(clusters),
				resources_usage=VALUES(resources_usage),
				slots_limit=VALUES(slots_limit),
				slots_usage=VALUES(slots_usage),
				mem_usage=VALUES(mem_usage),
				mem_limit=VALUES(mem_limit),
				swp_usage=VALUES(swp_usage),
				swp_limit=VALUES(swp_limit),
				tmp_usage=VALUES(tmp_usage),
				tmp_limit=VALUES(tmp_limit),
				jobs_usage=VALUES(jobs_usage),
				jobs_limit=VALUES(jobs_limit),
				fwd_tasks_usage=VALUES(fwd_tasks_usage),
				fwd_tasks_limit=VALUES(fwd_tasks_limit),
				last_updated=VALUES(last_updated)';

			grid_pump_records($records, 'grid_limits', $format, false, $duplicate);

			$limits['limits'] += db_fetch_cell_prepared('SELECT COUNT(clusterid)
				FROM grid_limits_usage
				WHERE clusterid = ?
				AND last_updated = ?',
				array($c['clusterid'], $c['last_updated']));
		}

		$format  = array(
			'sequenceid',
			'clusterid',
			'limit_name',
			'users',
			'queues',
			'hosts',
			'projects',
			'lic_projects',
			'clusters',
			'apps',
			'resources',
			'slots_usage',
			'slots_limit',
			'mem_usage',
			'mem_limit',
			'swp_usage',
			'swp_limit',
			'tmp_usage',
			'tmp_limit',
			'jobs_usage',
			'jobs_limit',
			'fwd_tasks_usage',
			'fwd_tasks_limit',
			'unit_for_limits',
			'last_seen'
		);

		$params = array();
		if (empty($last_run)) {
			$sql_where = '';
		} else {
			$sql_where = 'WHERE last_updated >= ?';
			$params[]  = date('Y-m-d H:i:s', $last_run);
		}

		$records = db_fetch_assoc_prepared("SELECT sequenceid,
			clusterid, limit_name, users, queues, hosts, projects,
			lic_projects, clusters, apps, resources,
			slots_usage, slots_limit,
			mem_usage, mem_limit,
			swp_usage, swp_limit,
			tmp_usage, tmp_limit,
			jobs_usage, jobs_limit,
			fwd_tasks_usage, fwd_tasks_limit,
			unit_for_limits, MAX(last_updated) AS last_seen
			FROM grid_limits_usage
			$sql_where
			GROUP BY
				clusterid,
				limit_name,
				users,
				queues,
				hosts,
				projects,
				lic_projects,
				clusters,
				apps",
			$params);

		$duplicate = 'ON DUPLICATE KEY UPDATE
			resources=VALUES(resources),
			slots_limit=VALUES(slots_limit),
			slots_usage=VALUES(slots_usage),
			mem_usage=VALUES(mem_usage),
			mem_limit=VALUES(mem_limit),
			swp_usage=VALUES(swp_usage),
			swp_limit=VALUES(swp_limit),
			tmp_usage=VALUES(tmp_usage),
			tmp_limit=VALUES(tmp_limit),
			jobs_usage=VALUES(jobs_usage),
			jobs_limit=VALUES(jobs_limit),
			fwd_tasks_usage=VALUES(fwd_tasks_usage),
			fwd_tasks_limit=VALUES(fwd_tasks_limit),
			unit_for_limits=VALUES(unit_for_limits),
			last_seen=VALUES(last_seen)';

		grid_pump_records($records, 'grid_limits_history', $format, false, $duplicate);

		$limits['history'] = cacti_sizeof($records);
	}

	return $limits;
}

/**
 * manage_partitions()
 *
 * A function to manage partitions of historical license data
 */
function gridlimits_manage_partitions() {
	/* determine if a new partition needs to be created */
	if (partition_timefor_create('grid_limits_usage', 'last_updated')) {
		partition_create('grid_limits_usage', 'last_updated', 'last_updated');
	}

	/* remove old partitions if required */
	grid_debug("Pruning Partitions for 'grid_limits_usage'");
	partition_prune_partitions('grid_limits_usage');
}

/**
 * A utility debugging function
 *
 * @param The message to be logged, if debugging is enabled
 */
function gridlimits_debug($mes) {
	global $debug;

	if ($debug) {
		print date('m/d/Y H:i:s') . ' ' . trim($mes) . "\n";
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	print "RTM Limits Poller " . read_config_option("grid_version") . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	print "Usage:\n";
	print "poller_gridlimits.php [-f|--force] [-d|--debug]\n\n";
	print "-f | --force       - Force License History Correlation\n";
	print "-d | --debug       - Display verbose output during execution\n";
}

