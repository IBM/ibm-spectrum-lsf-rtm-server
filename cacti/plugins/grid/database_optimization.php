#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

global $config, $debug;

$debug = false;
$force   = false;

declare(ticks = 1);

/* need to capture signals from users */
function sig_handler($signo) {
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
		case SIGABRT:
		case SIGQUIT:
		case SIGSEGV:
			cacti_log('OPTIMIZE - WARNING: database_optimization is terminated. It may be restarted manually at any time when database is not busy.', true);
			remove_process_entry('0', 'OPTIMIZE');
			exit;
			break;
		default:
			break;
	}
}

if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGABRT, 'sig_handler');
	pcntl_signal(SIGQUIT, 'sig_handler');
	pcntl_signal(SIGSEGV, 'sig_handler');
}

$ntables = false;

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
		case '-h':
		case '-v':
		case '-V':
		case '--version':
		case '--help':
			display_help();
			exit;
		case '-f':
		case '--force':
			$force = true;
			break;
		case '-d':
		case '--debug':
			$debug = true;
			break;
		case '--ntables':
			$ntables = true;
			break;
		case '--otables':
			$otables_input = trim($value);
			break;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			exit;
	}
}

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

if (isset($otables_input)) {
	$otables = explode(',', $otables_input);
	if (!empty($otables)) {
		if (!is_array($otables)) {
			print "ERROR: Comma separated string required for otpion --otables\n\n";
			exit;
		}
	}
}

if (detect_and_correct_running_processes('0', 'OPTIMIZE', 99999999) == true ) {
	if ($ntables || $force) {
		optimize_normal_tables();
	}

	if (isset($otables) && cacti_sizeof($otables)) {
		optimize_other_tables($otables);
	}

	remove_process_entry('0', 'OPTIMIZE');

	/* analyze tables for query optimization */
	$path_rtm_top=grid_get_path_rtm_top();
	exec(read_config_option('path_php_binary') . " -q $path_rtm_top/cacti/cli/analyze_database.php");

}

function optimize_other_tables($otables) {
	foreach($otables as $otable) {
		grid_debug("Optimizing table $otable.");
		db_execute("OPTIMIZE TABLE $otable");
	}
	set_config_option('run_partition_optimization', '');

	cacti_log('OPTIMIZE - NOTICE: Customized Tables Optimization completed. Tables: ' . implode(',', $otables), true);
}

function optimize_normal_tables() {
	$innodb_version_array = db_fetch_row("SHOW GLOBAL VARIABLES LIKE 'innodb_version';");
	if (isset($innodb_version_array)) {
		$innodb_version = substr($innodb_version_array['Value'], 0, 3);
	} else {
		$innodb_version = '';
	}

	if ($innodb_version != '5.6') {
		grid_debug('Optimizing the main jobs table.');
		db_execute('OPTIMIZE TABLE grid_jobs');
	}

	grid_debug('Optimizing the main pending reasons table.');
	db_execute('OPTIMIZE TABLE grid_jobs_pendreasons');

	/* add a heartbeat for optimization */
	make_heartbeat(0, 'OPTIMIZE');

	grid_debug('Optimizing the jobhosts table.');
	db_execute('OPTIMIZE TABLE grid_jobs_jobhosts');
	grid_debug('Optimizing the reqhosts table.');
	db_execute('OPTIMIZE TABLE grid_jobs_reqhosts');
	grid_debug('Optimizing the hostinfo table.');
	db_execute('OPTIMIZE TABLE grid_hostinfo');
	grid_debug('Optimizing the grid_users_or_groups table.');
	db_execute('OPTIMIZE TABLE grid_users_or_groups');
	grid_debug('Optimizing the grid_jobs_memvio table.');
	db_execute('OPTIMIZE TABLE grid_jobs_memvio');

	if (!substr_count('MEMORY', strtoupper(db_fetch_cell('SHOW CREATE TABLE grid_user_group_members')))) {
		grid_debug('Optimizing the grid_user_group_members table.');
		db_execute('OPTIMIZE TABLE grid_user_group_members');
	}

	/* add a heartbeat for optimization */
	make_heartbeat(0, 'OPTIMIZE');

	/* don't due any more frequent than 120 days */
	if (time() - read_config_option('grid_last_rusage_optimize') > 3600*24*120) {
		grid_debug('Optimizing the main jobs rusage database.');
		db_execute('OPTIMIZE TABLE grid_jobs_rusage');
		set_config_option('grid_last_rusage_optimize', time());
	}

	/* add a heartbeat for optimization */
	make_heartbeat(0, 'OPTIMIZE');

	/* don't due any more frequent than 30 days */
	if (time() - read_config_option('grid_last_interval_optimize') > 3600*24*30) {
		grid_debug('Optimizing the main job interval database.');
		db_execute('OPTIMIZE TABLE grid_job_interval_stats');
		set_config_option('grid_last_interval_optimize', time());
	}

	/* add a heartbeat for optimization */
	make_heartbeat(0, 'OPTIMIZE');

	/* don't due any more frequent than 1 year */
	if (time() - read_config_option('grid_last_daily_optimize') > 3600*24*365) {
		grid_debug('Optimizing the main job daily database.');
		db_execute('OPTIMIZE TABLE grid_job_daily_stats');
		set_config_option('grid_last_daily_optimize', time());
	}

	set_config_option('run_optimization', '0');

	cacti_log('OPTIMIZE - NOTICE: Normal Tables Optimization completed.', true);
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print "\nIBM Spectrum LSF RTM Background Database Optimization Script " . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "database_optimization.php [-d | --debug] [-f | --force] --ntables --otables=table1,table2,...\n\n";
	print "--ntables             - optimize normal pre-defined tables\n";
	print "--otables             - Comma separated table name list for table optimization\n";
	print "-d | --debug          - Log verbose information to standard output\n";
	print "-f | --force          - Force to optimize normal pre-defined tables\n";
	print "-v | -V | --version   - Display this help message\n";
	print "-h | --help           - display this help message\n\n";
}

