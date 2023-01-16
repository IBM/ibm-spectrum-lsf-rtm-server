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

include(dirname(__FILE__) . "/../../include/cli_check.php");
include_once($config["library_path"] . '/rtm_functions.php');

// Include core files
include(dirname(__FILE__) . '/functions.php');
include(dirname(__FILE__) . '/../grid/lib/grid_partitioning.php');

ini_set('memory_limit', '-1');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

/* take the start time to log performance data */
$start = microtime(true);

$debug      = FALSE;
$force      = FALSE;
$start_date = 0;
$end_date   = 0;
$custom     = false;

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
		$debug = TRUE;
		break;
	case '-f':
	case '--force':
		$force = TRUE;
		break;
	case '-s':
	case '--start-date':
		$start_date = $value;
		$custom = true;
		break;
	case '-e':
	case '--end-date':
		$end_date = $value;
		$custom = true;
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

if ($custom && (empty($start_date) || empty($end_date))) {
	print "ERROR: You must supply both an start date and an end date.\n\n";
}

$poller_interval = read_config_option('poller_interval');

if (detect_and_correct_running_processes(0, 'LICHIST', $poller_interval*3) || $force) {
	global $custom;

	// catch the unlikely event that the lic_services_feature_history is missing
	if (!db_table_exists('lic_services_feature_history')) {
		db_execute('CREATE TABLE lic_services_feature_history LIKE lic_services_feature_history_template;');
		db_execute('ALTER TABLE lic_services_feature_history ENGINE=InnoDB');
	}

	// Process Finished Jobs
	list($job_count, $hist_count) = process_jobs($start_date, $end_date);

	// Partition the history and the job mapping tables
	do_partitions();

	remove_process_entry(0, 'LICHIST');

	// Record end time for statistics and log statistics
	$end = microtime(true);

	if (!$custom) {
		cacti_log('LICHIST STATS: Total Time: ' . round($end - $start,2) . ', Total Jobs: ' . $job_count . ', History Events: ' . $hist_count, false, 'SYSTEM');
	}else{
		cacti_log('LICHIST REPLAY STATS: Total Time: ' . round($end - $start,2) . ', Total Jobs: ' . $job_count . ', History Events: ' . $hist_count, false, 'SYSTEM');
	}
}

// Responsible for creating/deleting partitions under the _history and history_mapping tables
function do_partitions() {
	$last_partition_run = read_config_option('lichist_partition_last_run');
	if (date('z') != $last_partition_run) {
		if (read_config_option('grid_partitioning_enable') == 'on') {
			manage_partitions();
		}
	}

	db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('lichist_partition_last_run', ?)", array(date('z')));
}

// A utility debugging function
//
// @param The message to be logged, if debugging is enabled
function lichist_debug($mes) {
	global $debug;

	if (defined('CACTI_DATE_TIME_FORMAT')) {
		$date = date(CACTI_DATE_TIME_FORMAT);
	} else {
		$date = date('Y-m-d H:i:s');
	}

	if ($debug) {
		print $date . ' - DEBUG: ' . trim($mes) . PHP_EOL;
	}
}

// Returns the delay interval for computing a job's license history,
//   measured in seconds
function get_history_delay_interval() {
	return 600;
}

// Get the time at which the Cacti poller last ran.
function get_lichist_last_run() {
	$lichist_poller_last_run = read_config_option('lichist_poller_last_run');
	if ($lichist_poller_last_run == '') {
		return '0000-00-00 00:00:00';
	} else {
		return date('Y-m-d H:i:s', strtotime($lichist_poller_last_run) - get_history_delay_interval());
	}
}

// Get all the jobs that we need to process this time around
//
// @return An array of jobs that meet the criteria
function get_finished_jobs($start_date, $end_date) {
	// We need to find all jobs that finished since the last time this logic ran,
	// but not include those jobs that finished within the last $delay_interval
	// seconds.
	// For completeness, we'll assume that some of these jobs may have been moved
	// into the grid_jobs_finished partitions

	// The start date for our time period, which is the last time this logic ran,
	// minus the delay_interval for considering finished_jobs
	if ($start_date == 0) {
		$date1 = get_lichist_last_run();
	}else{
		$date1 = $start_date;
	}

	// The end date for our time period, which is NOW minus the delay_interval for
	// considering finished jobs
	if ($end_date == 0) {
		$date2 = date('Y-m-d H:i:s', strtotime('-' .  get_history_delay_interval() . ' seconds'));
	}else{
		$date2 = $end_date;
	}

	lichist_debug ('date1=' . $date1 . ', date2=' . $date2 . "\n");
	// Now construct the query
	if (read_config_option('grid_partitioning_enable') == 'on') {
		$finished_jobs = get_finished_jobs_partition("SELECT jobid, indexid, clusterid,
			submit_time, start_time, end_time, exec_host, user FROM grid_jobs_finished
			WHERE end_time BETWEEN '" . $date1 . "' AND '" . $date2 . "'",
			"grid_jobs_finished", "", $date1, $date2);
	}else{
		// Pull all of the jobs from the database
	 	$finished_jobs = db_fetch_assoc_prepared("SELECT jobid, indexid, clusterid,
			submit_time, start_time, end_time, exec_host, user FROM grid_jobs_finished
			WHERE end_time BETWEEN ? AND ?", array($date1, $date2));

	}

	return $finished_jobs;
}

// A function to manage partitions of historical license data
function manage_partitions() {
	/* determine if a new partition needs to be created */
	if (partition_timefor_create('lic_services_feature_history', 'tokens_released_date')) {
		partition_create('lic_services_feature_history', 'tokens_released_date', 'tokens_released_date');
		if (read_config_option('lic_services_feature_history_partitioning_version', 'TRUE')){
			$partition_version = read_config_option('lic_services_feature_history_partitioning_version', 'TRUE');
		}else{
			$partition_version = '-1';
		}

		partition_create('lic_services_feature_history_mapping', 'tokens_released_date', 'tokens_released_date', $partition_version);
	}

	/* remove old partitions if required */
	grid_debug("Pruning Partitions for 'lic_services_feature_history'");
	partition_prune_partitions('lic_services_feature_history');

	grid_debug("Pruning Partitions for 'lic_services_feature_history_mapping'");
	partition_prune_partitions('lic_services_feature_history_mapping');
}

// Get all the license events that may relate to a specific job
//
// @param $start_time The start time from which to pull license events; the start time of the job
// @param $end_time The end time to which to pull license events; the end time of the job
// @param $exec_host The first execution host for the job
// @param $user The user running the job
//
// @return An array of license events related to the given time period, username and execution host
function get_license_events($start_time, $end_time, $exec_host, $user) {
	$sql_query = "SELECT id, username, hostname, tokens_acquired_date, last_poll_time, tokens_released_date
		FROM lic_services_feature_history
		WHERE username=?
		AND hostname=?
		AND tokens_acquired_date>=?
		AND last_poll_time<?";

	lichist_debug("$sql_query params:$user, $exec_host, $start_time, $end_time");
	$license_events = db_fetch_assoc_prepared($sql_query, array($user, $exec_host, $start_time, $end_time));

	return $license_events;
}

// Update the database with the conflicting jobs for the jobid
//
// @param jobid_current The current finished job id in the outer loop. We use this to NOT
//                      insert it as a conflicting jobid.
// @param conflicting_jobs The set of jobs that conflict with the outer job id, in terms of
//                         license checkouts.
function update_job_conflicts($job, $license_event) {
	// For the job itself, and all conflicting jobs, add an
	$jobid                = $job['jobid'];
	$indexid              = $job['indexid'];
	$clusterid            = $job['clusterid'];
	$submit_time          = $job['submit_time'];
	$exec_host            = $job['exec_host'];
	$history_event_id     = $license_event['id'];
	$tokens_released_date = $license_event['tokens_released_date'];

	$insert_stmt = "INSERT INTO lic_services_feature_history_mapping
		(jobid, indexid, clusterid, submit_time, exec_host, history_event_id, tokens_released_date)
		VALUES (?, ?, ?, ?, ?,  ?, ?)
		ON DUPLICATE KEY UPDATE exec_host=VALUES(exec_host), tokens_released_date=VALUES(tokens_released_date)";

	$result = db_execute_prepared($insert_stmt, array($jobid, $indexid, $clusterid, $submit_time, $exec_host,  $history_event_id, $tokens_released_date));
}

// Called from poller_bottom eventually, in poller_lichist.php
function process_jobs($start_date, $end_date) {
	global $custom;

	$hist_events = 0;
	$total_jobs  = 0;

	// Get the finished jobs that we need to process this time around
	$jobs = get_finished_jobs($start_date, $end_date);

	$total_jobs = cacti_sizeof($jobs);

	lichist_debug('Found ' . $total_jobs . ' Jobs');

	// For each such job......
	if ($total_jobs > 0) {
		foreach ($jobs as $job) {
			$job_start_time = date('Y-m-d H:i:00', strtotime($job['start_time']));
			$job_end_time   = date('Y-m-d H:i:s', strtotime($job['end_time']));

			// Get the license events that may have come from this job
			$license_events = get_license_events($job_start_time, $job_end_time, $job['exec_host'], $job['user']);

			$hist_events += cacti_sizeof($license_events);

			lichist_debug('Found Total of ' . $hist_events . ' License Events for Job ' . $job['jobid'] . ($job['indexid'] > 0 ? '[' . $job['indexid'] . ']':'') . ' and User ' . $job['user']);

			// For each such license event....
			if (cacti_sizeof($license_events)) {
				foreach ($license_events as $license_event) {
					update_job_conflicts($job, $license_event);
				}
			}
		}
	}

	// Update last run time for this logic
	if (!$custom) {
		db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('lichist_poller_last_run', ?)", array(date('Y-m-d H:i:s')));
	}

	return array($total_jobs, $hist_events);
}

/* display_help - displays the usage of the function */
function display_help () {
	echo "RTM License History Poller " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	echo "Usage:\n";
	echo "poller_lichist.php [-f|--force] [-s|--start-date -e|--end-date] [-d|--debug] [-h] [--help] [-v] [-V] [--version]\n\n";
	echo "-f | --force       - Force License History Correlation\n";
	echo "-s | --start-date  - Start with Jobs that Ended beginning with this Start Date 'YYYY-MM-DD'\n";
	echo "-e | --end-date    - End with Jobs that Ended by this End Date 'YYYY-MM-DD'\n";
	echo "-d | --debug       - Display verbose output during execution\n";
	echo "-v -V --version    - Display this help message\n";
	echo "-h --help          - Display this help message\n";
}

?>
