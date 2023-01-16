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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

/* get the gridalarms polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug;

$debug          = FALSE;
$forcerun       = FALSE;
$forcerun_maint = FALSE;

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
	case '-fr':
		$forcerun = TRUE;
		break;
	case '-fm':
		$forcerun_maint = TRUE;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
		display_help();
		exit;
	}
}

// if db alarm upgrade process is running, do not run alarm check
if (read_config_option('gridalarms_db_upgrade',TRUE) == '1') {
	gridalarms_debug('DB alarm data upgrade in progress, will not run alarm check.');
} else {
	gridalarms_debug('NOTE   - About to enter gridalarms poller processing');

	/* obtain the polleri interval if the user is using that Cacti mod */
	$poller_interval = read_config_option('poller_interval');
	if (empty($poller_interval)) {
		$poller_interval = 300;
	}

	/* find out when it's time to perform maintenance */
	$current_time = time();

	$database_maint_time = strtotime('12:00am');
	if ($database_maint_time < $current_time){
		if ($current_time - $poller_interval < $database_maint_time){
			$next_db_maint_time = $current_time;
		}else{
			$next_db_maint_time = $database_maint_time + 3600*24;
		}
	}else{
		$next_db_maint_time = $database_maint_time;
	}

	$time_till_next_db_maint = $next_db_maint_time - $current_time;
	if (($time_till_next_db_maint <= 0) || ($forcerun_maint)) {
		$run_maint = TRUE;
		gridalarms_debug('The next gridalarms database maintenance is NOW');
	}else{
		$run_maint = FALSE;
		gridalarms_debug('The next gridalarms database maintenance is "'. date('Y-m-d G:i:s', $next_db_maint_time) . '"');
	}

	gridalarms_debug('START  - gridalarms Polling Process');
	if (detect_and_correct_running_processes(0, 'GRIDALERTSPOLLER', $poller_interval*3)) {
		db_execute_prepared('REPLACE INTO settings
			(name, value) VALUES
			("gridalarms_last_run_time", ?)',
			array(date('Y-m-d G:i:s', $current_time)));

		/* process alarms */
		gridalarms_debug('START  - Alerts Poller');
		gridalarms_alarm_poller();
		gridalarms_debug('FINISH - Alerts Poller');
		remove_process_entry(0, 'GRIDALERTSPOLLER');
	}

	if ($run_maint || $forcerun_maint) {
		gridalarms_debug('START - Alerts Log Cleanup');
		gridalarms_alarm_log_cleanup();
		gridalarms_debug('FINISH - Alerts Log Cleanup');
	}
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'Grid Alerts Poller Process ' . read_config_option('gridalarms_db_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8').' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: poller_gridalarms.php [-fr] [-fm] [-d | --debug]\n\n";
	print "-fr              - Force the interval process.  Used in conjunction with -fm\n";
	print "-fm              - Force the maintenance process\n";
	print "-d | --debug     - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - Display this help message\n";
}
