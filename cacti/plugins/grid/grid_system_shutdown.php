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

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

global $debug;

$debug   = false;
$killall = false;
$time    = 20;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '--time':
		$time = $value;

		if (!is_numeric($time)) {
			die('ERROR: You must add a valid time in seconds');
		}
		break;
	case '-k':
		$killall = true;
		break;
	case '-d':
		$debug = true;
		break;
	case '-h':
	case '-H':
	case '--help':
	case '-v':
	case '-V':
	case '--version':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}
echo "Shutting down Cacti/RTM\n";

// Disable collection
set_config_option('poller_enabled', '');
set_config_option('grid_collection_enabled', '');
set_config_option('grid_system_collection_enabled', '');

/* kill all processes */
if ($killall) {
	/* Sleep for a small time */
	sleep($time);

	/* Main RTM Pollers */
	shell_exec('killall rtm_ha.php > /dev/null 2>&1');
	shell_exec('killall lsf_poller.php > /dev/null 2>&1');

	/* Collector Binaries */
	shell_exec('killall gridarrays > /dev/null 2>&1');
	shell_exec('killall gridbenchmark > /dev/null 2>&1');
	shell_exec('killall gridbhosts > /dev/null 2>&1');
	shell_exec('killall gridbsla > /dev/null 2>&1');
	shell_exec('killall gridload > /dev/null 2>&1');
	shell_exec('killall gridjobs > /dev/null 2>&1');
	shell_exec('killall gridpend > /dev/null 2>&1');
	shell_exec('killall gridperf > /dev/null 2>&1');
	shell_exec('killall gridhosts > /dev/null 2>&1');
	shell_exec('killall gridusers > /dev/null 2>&1');
	shell_exec('killall gridqueues > /dev/null 2>&1');
	shell_exec('killall gridparams > /dev/null 2>&1');
	shell_exec('killall gridusergroups > /dev/null 2>&1');
	shell_exec('killall gridhostgroups > /dev/null 2>&1');
	shell_exec('killall poller_grid.php > /dev/null 2>&1');

	/* Cacti Binaries */
	shell_exec('killall cmd.php > /dev/null 2>&1');
	shell_exec('killall spine > /dev/null 2>&1');
	shell_exec('killall poller.php > /dev/null 2>&1');
}

/* see if we are all clean */
$poller_output  = db_fetch_cell('SELECT count(*) FROM poller_output');
$grid_processes = db_fetch_assoc('SELECT taskname, taskid FROM grid_processes WHERE taskname!="GRIDPOLLER"');

if ($poller_output > 0) {
	cacti_log('WARNING: Cacti shutdown not 100% clean.  There were poller items that had not been transferred to RRDs', true, 'RTM');
} else {
	cacti_log('NOTE: Cacti shutdown completed cleanly', true, 'RTM');
}

if (cacti_sizeof($grid_processes)) {
	cacti_log('WARNING: RTM Shutdown not 100% clean.  There were some running collector/maintenance processes running', true, 'RTM');
} else {
	cacti_log('NOTE: RTM Shutdown completed cleanly', true, 'RTM');
}

/* truncate tables that affect restart */
db_execute('TRUNCATE grid_processes');

/* remove remaining SQL processes from the database */
$processes = db_fetch_assoc('SHOW PROCESSLIST');

if (cacti_sizeof($processes)) {
	foreach($processes AS $proc) {
		echo "Killing Process -> '" . $proc['Id'];
		$status = db_execute("KILL " . $proc['Id']);
		echo ($status == 0 ? " Failed" : " Successful") . "\n";
	}
}

echo "Cacti/RTM Shutdown Completed\n";

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM System Shutdown Script ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: grid_system_shutdown.php [-t=xxx] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-k               - Kill all Cacti/RTM Processes\n";
	print "--time=xx        - '20', Wait a small period of time prior to killing processes.\n";
	print "-d               - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

