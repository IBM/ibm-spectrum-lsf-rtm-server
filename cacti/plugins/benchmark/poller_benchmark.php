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

/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

/* We are not talking to the browser */
$no_http_headers = true;

$dir = dirname(__FILE__);
chdir($dir);

if (strpos($dir, 'benchmark') !== false) {
	chdir('../../');
}

/* Start Initialization Section */
include(dirname(__FILE__) . '/../../include/global.php');
include_once('./plugins/grid/setup.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/benchmark/functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$debug = FALSE;
$force = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = TRUE;
		break;
	case '-f':
	case '--force':
		$force = TRUE;
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

benchmark_debug('Starting Poller Porcess');

/* take the start time to log performance data */
$start = microtime(true);

$results   = db_fetch_assoc_prepared("SELECT gcb.benchmark_id, gcb.clusterid, gcb.enabled, gc.disabled
	FROM grid_clusters_benchmarks gcb JOIN grid_clusters gc ON gcb.clusterid=gc.clusterid
	WHERE gcb.enabled=1 AND gc.disabled=''");

// Determine if we need to perform graph automation
$total_rows   = cacti_sizeof($results);

benchmark_debug("There are $total_rows Benchmarks");
db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('benchmark_total_benchmarks',?)", array($total_rows));

// Determine if we need to perform data purgine
$last_start = read_config_option('benchmark_last_start');
if (!empty($last_start)) {
	$curday = date('z');
	$prevday = date('z', $last_start);

	if ($curday != $prevday) {
		benchmark_debug('Time to purge records');
		$purge_records = true;
	}else{
		benchmark_debug('Not time to purge records');
		$purge_records = false;
	}
}
db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('benchmark_last_start',?)", array(time()));

$good = 0; $bad = 0;
if (cacti_sizeof($results)) {
	$submit_size = cacti_sizeof($results);
	$max_run     = read_config_option('benchmark_concurrent_jobs');
	if (empty($max_run)) {
		$max_run =2;
		db_execute("REPLACE INTO settings (name,value) VALUES ('benchmark_concurrent_jobs','2')");
	}

	/* log and record long running benchmarks > 1 hour */
	$number_long = db_fetch_cell("SELECT COUNT(*)
		FROM grid_processes
		WHERE taskname LIKE 'GRIDBENCH%'
		AND heartbeat<FROM_UNIXTIME(UNIX_TIMESTAMP()-4*3600)");

	if ($number_long > 0) {
		cacti_log("WARNING: There are $number_long Benchmarks that have run over 4 hours", false, "BENCHMARK");

		db_execute("DELETE FROM grid_processes
			WHERE taskname LIKE 'GRIDBENCH%'
			AND heartbeat<FROM_UNIXTIME(UNIX_TIMESTAMP()-4*3600)");
	}

	foreach ($results as $result) {
		$lsf_bindir = db_fetch_cell_prepared("SELECT b.poller_lbindir
			FROM grid_clusters a
			INNER JOIN grid_pollers b
			ON (a.poller_id = b.poller_id)
			WHERE a.clusterid = ?", array($result['clusterid']));


		//for a bechmark whose assoicated cluster is deleted, skip the benchmark;
		if (empty($lsf_bindir)) continue;

		chdir($lsf_bindir);
		benchmark_debug('Prepping Benchmark ' . $result['benchmark_id']);

		while(true) {
			$num_run = db_fetch_cell("SELECT count(*) FROM grid_processes WHERE taskname LIKE 'GRIDBENCH%'");
			if (empty($num_run) || $num_run < $max_run) {
				break;
			}

			sleep(1);

			$current = microtime(true);
			if ($current - $start > 250) {
				benchmark_debug('Benchmark Launching Timed Out for ' . $result['benchmark_id']);
				cacti_log('ERROR: Benchmark Launching Timed Out.  Please check LSF for issues', true, 'BENCHMARK');
				exit(1);
			}
		}

		if (file_exists("$lsf_bindir/gridbenchmark")) {
			if (is_executable("$lsf_bindir/gridbenchmark")) {
				benchmark_debug('Running Benchmark ' . $result['benchmark_id']);
				exec_background("./gridbenchmark", '-B ' . $result['benchmark_id']);
				$good++;
			}else{
				benchmark_debug('Not running Benchmark ' . $result['benchmark_id']);
				cacti_log('FATAL: Benchmark binary found, but not executable.  Please contact IBM Support!', false, 'BENCHMARK');
				$bad++;
			}
		}else{
			benchmark_debug('Not running Benchmark ' . $result['benchmark_id']);
			cacti_log('FATAL: Benchmark binary not found.  Please contact IBM Support!', false, 'BENCHMARK');
			$bad++;
		}
		//Sleep 0.05s to ensure gridbenchmark binary update grid_processes table, and show as concunrrent job under submit_time level
		usleep(50000);
	}
} else {
	$submit_size = 0;
}

/* take the start time to log performance data */
$end = microtime(true);
$total = round($end-$start,2);

// Separately, purge old records from the database
if (isset($purge_records) && $purge_records) {
	benchmark_debug('Purging Benchmarks');
	benchmark_purge_records();
}

// Manage graphs for benchmarks
benchmark_add_graphs();
benchmark_disable_benchmarks();

cacti_log("BENCHMARK STATS: Time:$total, Launched:$good, Launched Failed:$bad", true, 'SYSTEM');

/*
 * Clusters can be removed from RTM without code notification. As such, we need to look
 * for benchmarks that relate to deleted clusters, and disable them.
 */
function benchmark_disable_benchmarks() {
	db_execute('UPDATE grid_clusters_benchmarks
		SET enabled=0
		WHERE clusterid NOT IN (SELECT clusterid FROM grid_clusters)');
}

/*
 * Purges benchmark job records older than benchmark_purge_days days
 */
function benchmark_purge_records() {
	global $config;

	$purge_period = read_config_option('benchmark_purge_days');

	$days = 0;
	switch ($purge_period) {
		case 1:
			$days = 30;
			break;
		case 2:
			$days = 90;
			break;
		case 3:
			$days = 180;
			break;
		case 4:
			$days = 365;
			break;
		case 5:
			$days = 730;
			break;
		case 6:
		    $days = 1095;
			break;
		case 7:
			$days = 999999;
			break;
		default:
			$days = 30;
			break;
	}

	db_execute_prepared("DELETE FROM grid_clusters_benchmark_summary
		WHERE start_time < DATE_SUB(NOW(), INTERVAL ? DAY)", array($days));

	cacti_log('Optimizing the benchmark tables.');
	db_execute('OPTIMIZE TABLE grid_clusters_benchmark_summary');
}

/*
 * Every time the Cacti poller runs, ensure that all graphs created by RTM are
 * added to the graph tree.
 */
function benchmark_add_graphs() {
	global $base_path, $php_bin, $path_web, $path_grid;

	$base_path = read_config_option('path_webroot');
	$php_bin   = read_config_option('path_php_binary');
	$path_web  = read_config_option('path_webroot');
	$path_grid = $path_web . '/plugins/grid';

	$result0   = db_fetch_assoc("SELECT * FROM grid_clusters WHERE disabled=''");

	if (cacti_sizeof($result0)) {
	foreach ($result0 as $result) {

		//if there is no cluster summary host id and tree id, skip creating benchmark graphs this time until the cluster summary device is totally available.
		if (empty($result['cacti_tree'])) continue;
		if (empty($result["cacti_host"])) continue;

		$benchmark_graph_count = db_fetch_cell_prepared("SELECT count(DISTINCT graph_local.id)
			FROM graph_local
			INNER JOIN graph_templates
			ON graph_templates.id=graph_local.graph_template_id
			WHERE graph_local.host_id=?
			AND graph_templates.hash='6c8c4a6c27c0b73866f11748e17f5ed2'", array($result["cacti_host"]));

		$benchmark_count = db_fetch_cell_prepared("SELECT count(benchmark_id)
			FROM grid_clusters_benchmarks
			WHERE enabled=1
			AND clusterid =?", array($result["clusterid"]));

		if ($benchmark_count == $benchmark_graph_count) continue;

		benchmark_debug('Running Graph Automation for ' . $result['clustername']);

		$snmp_query_id = db_fetch_row_prepared("SELECT snmp_query.id, snmp_query.name, host_snmp_query.reindex_method
			FROM (snmp_query, host_snmp_query)
			WHERE snmp_query.id = host_snmp_query.snmp_query_id
			AND host_snmp_query.host_id = ?
			AND hash='df901648b4a72d6efab70f851273b9ea'", array($result["cacti_host"]));

		if (cacti_sizeof($snmp_query_id)) {
			db_execute_prepared("REPLACE INTO host_snmp_query
				(host_id, snmp_query_id, reindex_method)
				VALUES (?, ?, 0)", array($result['cacti_host'], $snmp_query_id["id"]));

			db_execute_prepared("DELETE FROM poller_reindex
				WHERE host_id=?
				AND data_query_id=?", array($result['cacti_host'], $snmp_query_id["id"]));

			// Reindex the Query id
			echo $php_bin .' -q ' . $path_web . '/cli/poller_reindex_hosts.php -id=' . $result['cacti_host'] . ' -qid=' . $snmp_query_id['id'] . " -d\n";
			echo trim(passthru($php_bin . ' -q ' . $path_web . '/cli/poller_reindex_hosts.php -id=' . $result['cacti_host'] . ' -qid=' . $snmp_query_id['id'] . ' -d'));

			$snmp_query_types = db_fetch_assoc_prepared('SELECT id, name, graph_template_id
				FROM snmp_query_graph
				WHERE snmp_query_id = ?
				ORDER BY id', array($snmp_query_id['id']));

			if (cacti_sizeof($snmp_query_types)) {
				foreach ($snmp_query_types as $snmp_query_type){
					$query_field_name = get_query_field_name($result['cacti_host'], $snmp_query_id['id']);
					add_data_query_graphs($result, $snmp_query_type['graph_template_id'], $snmp_query_id['id'], $snmp_query_type['id'], $query_field_name);
				}
			}
		}
	}
	}

	// Get the graph template id for our Benchmark graph template
	$result1 = db_fetch_cell("SELECT id FROM graph_templates WHERE hash='6c8c4a6c27c0b73866f11748e17f5ed2'");

	// We need to find this template in order to continue here; not finding it is an error
	if (empty($result1)) {
		cacti_log('ERROR: Benchmark plugin could not find the correct Graph Template.');
		return;
	}

	// Find all the instances of our graph template. These have been created by RTM due
	// to their association with the grid summary host template. Each graph has been
	// created for a specific host_id (that is, a specific grid summary device).
	$result2 = db_fetch_assoc_prepared("SELECT id, host_id FROM graph_local WHERE graph_template_id=?", array($result1));

	// For each graph instance across 1 or more clusters
	if (cacti_sizeof($result2)) {
		foreach ($result2 as $result) {
			if (!empty($result['host_id'])) {
				// Get the graph tree for that cluster
				$result3 = db_fetch_cell_prepared("SELECT cacti_tree FROM grid_clusters WHERE cacti_host=?", array($result["host_id"]));

				if(!empty($result3)){
					// Get the Benchmark header node in that cluster's tree
					$result4 = db_fetch_cell_prepared("SELECT id FROM graph_tree_items WHERE graph_tree_id=? AND title='Benchmarks'", array($result3));

					if (!empty($result3) && !empty($result4)) {
						// Add the graph instance to this tree item id, under the Benchmark header node
						$cmd    = "$php_bin -q $base_path/cli/add_tree.php --type=node --node-type=graph --tree-id=" . $result3  . " --graph-id=" . $result['id'] . " --parent-node=" . $result4;
						$output = shell_exec($cmd);
						benchmark_debug($output);
					}
				}
			}
		}
	}
}

function add_data_query_graphs(&$cluster, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = "", $include = "on"){
	global $php_bin, $path_web, $path_grid, $graphs, $debug;

	/* let's see what queries are defined for this host */
	$found = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM host_snmp_query AS hsq
			INNER JOIN snmp_query_graph AS sqg
			ON hsq.snmp_query_id=sqg.snmp_query_id
			WHERE hsq.host_id=?
			AND hsq.snmp_query_id=?
			AND sqg.graph_template_id=?
			AND sqg.id=?", array($cluster["cacti_host"], $snmp_query_id, $graph_template_id,$snmp_query_type_id));

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = array_rekey(db_fetch_assoc_prepared("SELECT field_value
			FROM host_snmp_cache
			WHERE host_id=?
			AND snmp_query_id=?
			AND field_name=?", array($cluster["cacti_host"], $snmp_query_id, $snmp_field_name)), "field_value", "field_value");

		if (cacti_sizeof($items)) {
			foreach($items as $key => $item) {
				if ($regmatch == "") {
					/* add graph below */
				}elseif ((($include == "on") && (preg_match("/$regmatch/", $item))) ||
					(($include != "on") && (!preg_match("/$regmatch/", $item)))) {
					/* add graph below */
				}else{
					echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"] . "\n";
					continue;
				}

				/* see if graph exists */
				$exists = db_fetch_cell_prepared("SELECT count(*)
					FROM graph_local
					WHERE host_id=?
					AND graph_template_id=?
					AND snmp_query_id=?
					AND snmp_index=?", array($cluster["cacti_host"], $graph_template_id, $snmp_query_id, $item));

				if (!$exists) {
					echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"] . "\n";

					$command = "$php_bin -q $path_grid/grid_add_graphs.php" .
						" --graph-template-id=$graph_template_id --graph-type=ds"     .
						" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $cluster["cacti_host"] .
						" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
						" --snmp-value='" . $item . "'";

					echo $command. "\n";
					echo trim(passthru($command)) . "\n";
					$graphs++;
				}
			}
		}
	}elseif (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Cluster: " . $cluster["clustername"], TRUE, "GRID");
	}
}

function get_query_field_name($host_id, $query_id) {
	return db_fetch_cell_prepared("SELECT sort_field
		FROM host_snmp_query
		WHERE host_id=? AND snmp_query_id=?", array($host_id, $query_id));
}

function benchmark_debug($message) {
	global $debug;

	if ($debug) {
		echo $message . "\n";
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	echo "RTM Benchmark Job Poller " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";


	echo "Usage:\n";
	echo "poller_benchmark.php [-d | --debug] [-f | --force] [-h | --help | -v | -V | --version]\n\n";
	echo "-d | --debug     - Display verbose output during execution\n";
	echo "-f | --force     - Force Graph automation\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n";
}

