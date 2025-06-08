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
include_once('./lib/rtm_functions.php');
include_once('./lib/rtm_plugins.php');
include_once('./lib/rtm_functions.php');
include_once('./plugins/lsfenh/lib/analytics.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');

global $debug, $force;

$debug     = false;
$clusterid = false;
$force     = false;
$now       = time();

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter, 2);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
		case '-c':
		case '--clusterid':
			$clusterid = $value;
			break;
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

$start = microtime(true);

create_required_tables();

if ($clusterid !== false && $clusterid > 0) {
	$cluster = db_fetch_row_prepared('SELECT clustername, gc.poller_id, lsf_envdir, lsf_clustername, remote, disabled
		FROM grid_clusters as gc
		INNER JOIN grid_pollers AS gp
		ON gc.poller_id = gp.poller_id
		WHERE clusterid = ?',
		array($clusterid));

	$frequency = read_config_option('poller_interval');

	if ($frequency == '') {
		$frequency = 300;
		set_config_option('grid_jobs_summary', $frequency);
	}

	printf('NOTE:  %s - Starting the Jobs Collector.' . PHP_EOL, date('H:i:s'));

	if (cacti_sizeof($cluster)) {
		if ($cluster['disabled'] == 'on') {
			print "WARNING: Attempting to collect jobs from a disabled clusterid $clusterid." . PHP_EOL;
		} elseif ($cluster['remote'] == 'on') {
			set_config_option('grid_jobs_summary_' . $clusterid, $now);

			if (grid_detect_and_correct_running_processes(0, 'LSFENH' . $clusterid, $frequency * 5)) {
				list($total_time, $jobs) = grid_collect_jobs_remote($clusterid);

				/* remove the process entry */
				grid_remove_process_entry(0, 'LSFENH' . $clusterid);

				if ($total_time !== false) {
					printf('NOTE:  %s - The total time to run the collector was %4.2f seconds with %s jobs.' . PHP_EOL, date('H:i:s'), $total_time, $jobs);

					if ($jobs > 0) {
						cacti_log(sprintf('LSFENH Cluster STATS: Total:%4.2f, ClusterID:%s, Jobs:%s', $total_time, $clusterid, $jobs), false, 'SYSTEM');
					}
				} else {
					printf('NOTE:  %s - The LSF cluster did not repond, or we requested too quickly.' . PHP_EOL, date('H:i:s'));
				}
			}
		} elseif (grid_detect_and_correct_running_processes(0, 'LSFENH' . $clusterid, $frequency * 5)) {
			set_config_option('grid_jobs_summary_' . $clusterid, $now);

			if (!set_lsf_environment($cluster)) {
				cacti_log('WARNING: Unable to set cluster environment for ' . $cluster['clustername'], false, 'LSFENH');

				return false;
			}

			/* collect jobs */
			list($total_time, $jobs) = grid_collect_jobs($clusterid);

			/* remove the process entry */
			grid_remove_process_entry(0, 'LSFENH' . $clusterid);

			if ($total_time !== false) {
				printf('NOTE:  %s - The total time to run the collector was %4.2f seconds with %s jobs.' . PHP_EOL, date('H:i:s'), $total_time, $jobs);

				if ($jobs > 0) {
					cacti_log(sprintf('LSFENH Cluster: Total:%4.2f, ClusterID:%s, Jobs:%s', $total_time, $clusterid, $jobs), false, 'SYSTEM');
				}
			} else {
				printf('NOTE:  %s - The LSF cluster did not repond, or we requested too quickly.' . PHP_EOL, date('H:i:s'));
			}
		} else {
			print "ERROR: Attempting to start jobs collector and another is still running for $clusterid" . PHP_EOL;
		}
	} else {
		print "FATAL: The RTM clusterid $clusterid does not exist." . PHP_EOL;
	}
} else {
	print "FATAL: You must specificy a valid RTM clusterid." . PHP_EOL;
}

$end = microtime(true);

grid_debug('Total time was: ' . round($end - $start, 2));

function display_version() {
	print 'RTM Enhanced LSF Collector ' . read_config_option('grid_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . 'Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}

function display_help() {
	display_version();

	print 'usage: poller_lsf.php --clusterid=N [-f|--force] [-d|--debug]' . PHP_EOL;
}

