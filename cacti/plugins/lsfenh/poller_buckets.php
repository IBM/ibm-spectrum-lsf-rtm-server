#!/usr/bin/php -q
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

chdir(__DIR__);
chdir('../..');
include('./include/cli_check.php');
include_once('./lib/poller.php');
include_once('./lib/time.php');
include_once('./lib/rrd.php');
include_once('./lib/rtm_functions.php');
include_once('./lib/rtm_plugins.php');
include_once('./plugins/lsfenh/lib/analytics.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');

global $debug, $start, $force, $rrdtool;

$debug = false;
$force = false;

$rrdtool = read_config_option('path_rrdtool');

/* we need long group concats */
db_execute('SET SESSION group_concat_max_len = 1000000');

/* take the start time to log performance data */
$start = microtime(true);

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
	case '-v':
	case '-V':
	case '--version':
		display_version();
		exit(0);
	case '-h':
	case '-H':
	case '--help':
		display_help();
		exit(0);
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
		display_help();
		exit(1);
	}
}

if (!db_table_exists('grid_jobs')) {
	print 'FATAL: RTM must be installed before this plugin can run!' .  PHP_EOL;
	exit(1);
}

$ranges = array(
	6 => 86400 * 200
);

$now  = time();
$now  = strtotime(date('Y-m-d 00:00:00', $now));
$date = date('Y-m-d H:i:s');

if ($force || grid_detect_and_correct_running_processes(0, 'LSFENHBUCKET', '86400')) {
	foreach($ranges as $range_id => $timespan) {
		$start_time = date('Y-m-d 00:00:00', $now - $timespan);
		$end_time   = date('Y-m-d 00:00:00', $now);

		if (read_config_option('grid_partitioning_enable') == 'on') {
			$tables = partition_get_partitions_for_query('grid_jobs_finished', $start_time, $end_time);
		} else {
			$tables = array('grid_jobs_finished');
		}

		$queries = grid_sla_bucket_range_sql($start_time, $end_time, $tables);

		if (cacti_sizeof($queries)) {
			foreach($queries as $table => $sql) {
				if (add_data_to_raw_table($table, $sql, $date)) {
					grid_debug("Table $table Statistics added to Raw Table");
				} else {
					grid_debug("Table $table Statistics skipped due to already being present");
				}
			}
		}
	}

	// Remove interlock
	grid_remove_process_entry(0, 'LSFENHBUCKET');
}

function add_data_to_raw_table($table, $sql, $date) {
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

	if ($table == 'grid_jobs_finished') {
		db_execute('DELETE FROM grid_sla_finished_memory_buckets
			WHERE table_name = "grid_jobs_finished"');
	}

	$exists = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM grid_sla_finished_memory_buckets
		WHERE table_name = ?',
		array($table));

	if ($exists) {
		return false;
	}

	$data = db_fetch_assoc($sql);

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
				$reserved    = $r[$prefix . 'reserved'];
				$max         = $r[$prefix . 'max'];
				$requested   = $r[$prefix . 'requested'];

				db_execute_prepared('INSERT INTO grid_sla_finished_memory_buckets
					(clusterid, sla, table_name, year_day, memory_size, mem_requested, mem_reserved, max_memory, doneJobs, doneArrays, exitJobs, exitArrays, present, last_updated)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE
						mem_requested = VALUES(mem_requested),
						mem_reserved = VALUES(mem_reserved),
						max_memory = VALUES(max_memory),
						doneJobs = VALUES(doneJobs),
						doneArrays = VALUES(doneArrays),
						exitJobs = VALUES(exitJobs),
						exitArrays = VALUES(exitArrays),
						present = VALUES(present),
						last_updated = VALUES(last_updated)',
					array(
						$r['clusterid'],
						$r['sla'],
						$r['table_name'],
						$r['year_day'],
						$bucket,
						$requested,
						$reserved,
						$max,
						$doneJobs,
						$doneArrays,
						$exitJobs,
						$exitArrays,
						1,
						$date
					)
				);
			}
		}
	}

	return true;
}

function display_version() {
	print 'RTM Analytics Job Memory Bucket Calculator ' . read_config_option('grid_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . 'Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}

function display_help() {
	display_version();

	print 'usage: poller_buckets.php [-f|--force] [-d|--debug]' . PHP_EOL;
}

