#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2025                                                |
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
include_once(dirname(__FILE__) . '/include/lic_functions.php');
include_once(dirname(__FILE__) . '/include/lic_feature_functions.php');

/* take the start time to log performance data */
$start = microtime(true);

/* get the time the cacti poller started */
$lic_poller_start = read_config_option('lic_poller_start');

/* get the lic polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* get the start time*/
$current_date_time = microtime(true); //getting the current time in unix timestamp

/** process callling arguments*/
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug;

// $config['DEBUG_SQL_CONNECT'] = 'y';
// $config['DEBUG_SQL_CMD'] = 'y';

$debug	  	    = false;
$forcerun       = false;
$forcerun_maint = false;
$daily_stats    = false;
$interval_stats = false;
$run_tests      = false;

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
	case '-fm':
		$forcerun_maint = true;
		break;
	case '-fr':
		$forcerun = true;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	case '-test':
		$run_tests = true;
		break;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . '\n\n';
		display_help();
		exit;
	}
}

$poller_interval = read_config_option('poller_interval');
if (empty($poller_interval)){
	$poller_interval = 300;
}

if (read_config_option('grid_collection_enabled') != 'on') {
	lic_debug('Grid collection is disabled.  License feature poller exit.');
	exit;
}

if ($run_tests){
	print "Running tests.  License data will be deleted!\n";
	include_once(dirname(__FILE__) . '/include/unit_tests.php');
	license_unit_test();
	exit;
}

if (read_config_option('grid_db_upgrade', true) == '1'){
	lic_debug('DB job tables upgrade in process, will not run DB maintenance routine.');
} else {
	// checks if its time to perform maintenance
	$current_time = time();
	if (read_config_option('lic_db_maint_time', true)){
		$database_maint_time = strtotime(read_config_option('lic_db_maint_time', true));
	} else {
		$database_maint_time = strtotime('12:00am');
	}

	if ($database_maint_time < $current_time){
		if ($current_time - $poller_interval < $database_maint_time){
			$next_db_maint_time = $current_time;
		} else {
			$next_db_maint_time = $database_maint_time + 3600*24;
		}
	} else {
		$next_db_maint_time = $database_maint_time;
	}

	$time_till_next_db_maint = $next_db_maint_time - $current_time;
	if (($time_till_next_db_maint <= 0) || ($forcerun_maint)) {
		$run_maint = true;
		lic_debug('The next lic feature database maintenance is NOW');
	} else {
		$run_maint = false;
		lic_debug('The next lic feature database maintenance is "'. date('Y-m-d G:i:s', $next_db_maint_time).'"');
	}
}


lic_debug('About to enter License Feature Poller Processing');

$last_interval_stats_start = read_config_option('lic_feature_interval_stats_start_time');
$last_interval_stats_end   = read_config_option('lic_feature_interval_stats_end_time');

$last_daily_stats_start    = read_config_option('lic_feature_daily_stats_start_time');
$last_daily_stats_end      = read_config_option('lic_feature_daily_stats_end_time');

/* update lic_interval_stats */
if (($poller_interval-($current_date_time - $last_interval_stats_start) <= 30) || ($forcerun)){
	if (detect_and_correct_running_processes(0, 'LICFEATINTERVALSTAT', $poller_interval*3)){
		lic_debug('About to enter license feature interval stats updating process');
		$start = microtime(true);
		lic_feature_update_license_interval_stats($current_date_time, $last_interval_stats_start);
		remove_process_entry(0, 'LICFEATINTERVALSTAT');
		log_lic_feature_statistics('interval');
	}
}

if ($run_maint || $forcerun_maint){
	if (detect_and_correct_running_processes(0, 'LICFEATMAINT', $poller_interval*3)){
		$start = microtime(true);
		lic_feature_purge_event($next_db_maint_time, $last_daily_stats_start);
		remove_process_entry(0, 'LICFEATMAINT');
		log_lic_feature_statistics('maint');
	}

	/* update lic daily stats */
	if (detect_and_correct_running_processes(0, 'LICFEATDAILYSTAT', $poller_interval*10)){
		lic_debug('About to enter license feature daily stats updating process');
		$start = microtime(true);
		lic_feature_update_license_daily_stats($current_date_time);
		remove_process_entry(0, 'LICFEATDAILYSTAT');
		log_lic_feature_statistics('daily');
	}
}


/*      display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Master Poller Process ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8'). ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: poller_feature.php [-fr] [-fm] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-fm        - Force the execution of the database maintenance process\n";
	print "-fr        - Force the interval process. Used in conjunction with -fm\n";
	print "-d         - Display verbose output during execution\n";
	print "-test      - DANGER!  Wipe license data and run tests\n";
	print "-h --help  - display this help message\n";
	print "-v -V --version	- Display this help message\n\n";
}

function log_lic_feature_statistics($type = 'collect') {
	global $start;

	/* take time and log performance data */
	$end = microtime(true);

	if ($type == 'interval') {
		$cacti_stats = sprintf('Time:%01.4f', round($end-$start,4));

		/* log to the database */
		db_execute("REPLACE INTO settings (name,value) VALUES ('stats_lic_feature_interval', '" . $cacti_stats . "')");

		/* log to the logfile */
		cacti_log('LICENSE FEATURE STATS: ' . $cacti_stats , true, 'SYSTEM');
	}elseif ($type == 'daily') {
		$cacti_stats = sprintf('Time:%01.4f', round($end-$start,4));

		/* log to the database */
		db_execute("REPLACE INTO settings (name,value) VALUES ('stats_lic_feature_daily', '" . $cacti_stats . "')");

		/* log to the logfile */
		cacti_log('LICENSE FEATURE DAILY STATS: ' . $cacti_stats ,true,'SYSTEM');
	} else {
		$cacti_stats = sprintf('Time:%01.4f', round($end-$start,4));

		/* log to the database */
		db_execute("REPLACE INTO settings (name,value) VALUES ('stats_lic_feature_maint', '" . $cacti_stats . "')");

		/* log to the logfile */
		cacti_log('LICENSE FEATURE MAINT STATS: ' . $cacti_stats ,true,'SYSTEM');
	}
}

