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

global $debug, $start, $force;

$debug = false;
$force = false;

/* we need long group concats */
db_execute('SET SESSION group_concat_max_len = 1000000');

/* take the start time to log performance data */
$start = microtime(true);

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter, 2);
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

$poller_interval = read_config_option('poller_interval');

/* create required tables */
create_required_tables();

if ($force || grid_detect_and_correct_running_processes(0, 'LSFENHJOBS', '300')) {
	// Run background jobs collectors
	run_background_jobs_collectors();

	// Remove interlock
	grid_remove_process_entry(0, 'LSFENHJOBS');
}

if ($force || grid_detect_and_correct_running_processes(0, 'LSFENHDATA', '7200')) {
	// Purge bsub metrics
	purge_lsf_metrics();

	// Remove old Cluster Records
	purge_old_cluster_records();

	// Reload Guarantee Pools
	freshen_guarantee_pools();

	// Set LS Features 'Key'
	make_ls_features_key_features();

	// Aggregate Reasons for Graphing
	$num_reasons = grid_aggregate_reasons();

	// Update cpu counts by hostModel
	update_hosts_model_stats();

	// Optimize key analytics table
	grid_analytics_optimize();

	// Get cluster change events
	update_lsf_events();

	// Remove interlock
	grid_remove_process_entry(0, 'LSFENHDATA');

	$end = microtime(true);

	cacti_log(sprintf('LSFENH Core STATS: Time:%4.2f Reasons:%d', $end - $start, $num_reasons), false, 'SYSTEM');

	exit(0);
} else {
	cacti_log('WARNING: Unable to start new Grid Analytics Collector, prior is still running.', false, 'LSFENH');

	exit(1);
}

function freshen_guarantee_pools() {
	db_execute('INSERT IGNORE INTO grid_guarantees
		(clusterid, pool_name, sla_name, common_bu, common_pool, common_sla)
		SELECT DISTINCT clusterid, name, consumer, "" AS bu, name, consumer
		FROM grid_guarantee_pool_distribution
		WHERE clusterid IN (SELECT clusterid FROM grid_clusters WHERE disabled="")');

	$rows = db_affected_rows();

	if ($rows > 0) {
		cacti_log('WARNING: New Guarantee Pools Detected.  Remember to Map their Common Names', false, 'LSFENH');
	}
}

function update_hosts_model_stats() {
	db_execute("INSERT INTO grid_clusters_maxcpus_bymodel
		(clusterid, hostModel, maxCpus)
		SELECT clusterid, hostModel, max(maxCpus) AS maxJobs
		FROM grid_hostinfo
		WHERE isServer = 1
		AND maxCpus != '-'
		GROUP BY hostModel, maxCpus
		ON DUPLICATE KEY UPDATE maxCpus=values(maxCpus)");
}

function grid_analytics_optimize() {
	$last_optimize = read_config_option('grid_analytics_optimize_lastrun');

	if (empty($last_optimize) || (time() - $last_optimize) > 14440) {
		set_config_option('grid_analytics_optimize_lastrun', time());
		db_execute('OPTIMIZE TABLE grid_jobs_summary');
	}
}

function run_background_jobs_collectors() {
	global $config;

	$remotes = read_config_option('grid_remote');

	if ($remotes == '') {
		$clusters = array_rekey(
			db_fetch_assoc('SELECT clusterid, remote
				FROM grid_clusters AS gc
				INNER JOIN grid_pollers AS gp
				ON gc.poller_id = gp.poller_id
				WHERE disabled = ""'),
			'clusterid', 'remote'
		);
	} else {
		$clusters = array_rekey(
			db_fetch_assoc('SELECT clusterid, remote
				FROM grid_clusters AS gc
				INNER JOIN grid_pollers AS gp
				ON gc.poller_id = gp.poller_id
				WHERE disabled = ""
				AND gp.remote = ""'),
			'clusterid', 'remote'
		);
	}

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $clusterid => $remote) {
			$cluster = db_fetch_row_prepared('SELECT *
				FROM grid_clusters
				WHERE clusterid = ?',
				array($clusterid));

			$cluster_status = grid_get_cluster_collect_status($cluster);

			if ($cluster_status == 'Up' || $cluster_status == 'Down' || $cluster_status == 'Jobs Down') {
				grid_debug('Lanching Background Jobs Collector for Cluster ' . $clusterid);

				$command = ' -q ' . $config['base_path'] . '/plugins/lsfenh/poller_lsf.php --clusterid=' . $clusterid;
				exec_background(read_config_option('path_php_binary'), $command);
			} else {
				cacti_log('NOTE: Jobs Collection skipped for Cluster[' . $cluster['clustername'] . '], Status[' . $cluster_status . ']', false, 'LSFENH');
			}
		}
	}
}

function purge_lsf_metrics() {
	if (db_table_exists('grid_clusters_bsub_timing')) {
		db_execute('DELETE FROM grid_clusters_bsub_timing
			WHERE last_updated < (NOW() - INTERVAL 15 MINUTE)');
	}

	if (db_table_exists('grid_clusters_dns_response_timing')) {
		db_execute('DELETE FROM grid_clusters_dns_response_timing
			WHERE last_updated < (NOW() - INTERVAL 15 MINUTE)');
	}

	if (db_table_exists('grid_clusters_backlog_stats')) {
		db_execute('DELETE FROM grid_clusters_backlog_stats
			WHERE update_time < (NOW() - INTERVAL 600 MINUTE)');
	}

	if (db_table_exists('grid_pool_memory')) {
		db_execute("DELETE FROM grid_pool_memory
			WHERE CONCAT(clusterid, '/', name) NOT IN (SELECT CONCAT(clusterid,'/', name) FROM grid_guarantee_pool)");
	}
}

function purge_old_cluster_records() {
	$tables = array(
		'grid_djob_hstats',
		'grid_guarantees',
		'grid_jobs_summary',
		'grid_jobs_reason_details',
		'grid_jobs_reasons',
		'grid_pool_host_lending',
		'grid_pool_memory',
		'grid_sla_stats'
	);

	$clusters = array_rekey(
		db_fetch_assoc('SELECT clusterid
			FROM grid_clusters
			WHERE disabled=""'
		),
		'clusterid', 'clusterid'
	);

	if (cacti_sizeof($clusters)) {
		$clusterids = implode(', ', $clusters);

		foreach($tables as $t) {
			$count = db_fetch_cell("SELECT COUNT(*)
				FROM $t
				WHERE clusterid > 0
				AND clusterid NOT IN ($clusterids)");

			if ($count > 0) {
				db_execute_prepared("DELETE FROM $t
					WHERE clusterid > 0
					AND clusterid NOT IN($clusterids)");
			}
		}
	}
}

function make_ls_features_key_features() {
	db_execute('UPDATE lic_application_feature_map AS fm
		INNER JOIN grid_blstat_feature_map AS bld
		ON fm.feature_name = bld.lic_feature
		SET critical = 1
		WHERE critical = 0');
}

function display_version() {
	print 'RTM LSF Enhanced Statistics Poller ' . read_config_option('grid_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . 'Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}

function display_help() {
	display_version();

	print 'usage: poller_analytics.php [-f|--force] [-d|--debug]' . PHP_EOL;
}

