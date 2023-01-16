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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = false;
$exec  = false;
$time  = 600; /* kill any process older than 600 seconds */
$count = -1;  /* database connection threshold */
$maxQueryTime = -1; /* kill any process older than $maxQueryTime no matter the info and command*/

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
	case '-t':
	case '--time':
		$time = $value;
		break;
	case '-c':
	case '--count':
		$count = $value;
		break;
	case '--max-query-time':
		$maxQueryTime = $value;
		break;
	case '--exec':
		$exec = true;
		break;
	case '-v':
	case '-V':
	case '-h':
	case '--help':
	case '--version':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}
print "NOTE: Checking for Hung Processes\n";

$processes = db_fetch_assoc('SHOW PROCESSLIST;');
$kills     = array();
$total     = 0;

if (cacti_sizeof($processes)) {
	foreach($processes AS $proc) {
		if (($proc['Info'] != 'NULL') && ($proc['Command'] != 'Sleep')) {
			$total++;

			if (($proc['Time'] > $time)) {
				$kills[$proc['Id']] = $proc['Time'];
			}
		} elseif (0 < $maxQueryTime && $proc['Time'] > $maxQueryTime) {
			$total++;
			$kills[$proc['Id']] = $proc['Time'];
		}
	}

	if ($exec && cacti_sizeof($kills) >= $count) {
		foreach($kills as $pid => $time) {
			$message  = "WARINING: Killing PID:'" . $pid . "', Time:'" . $time . "'";
			$status = db_execute('KILL ' . $pid);
			$message .= ($status == 0 ? ' Failed' : ' Successful') . "\n";
			print $message;
			cacti_log(trim($message), false, 'SYSTEM');
		}
	} elseif (cacti_sizeof($kills)) {
		foreach($kills as $pid => $time) {
			print "NOTE: Found PID:'" . $pid . "', Time:'" . $time . "', below Threshold: '$count', Observed: '" . cacti_sizeof($kills) . "'\n";
		}
	}

	print "NOTE: Found $total Running Processes\n";
}

print "NOTE: Check Complete\n";

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Database Process Killer ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "database_kill.php [-t=N|--time=N] [-c=N|--count=N] [--max-query-time=N] [--exec] [-h|--help|-v|-V|--version]\n\n";
	print "-t=N   				- Include process older than N seconds.\n";
	print "-c=N   				- Don't kill any processes if there are less than N processes active.\n";
	print "--max-query-time=N	- Include process older than N seconds no matter the Info and Command.\n";
	print "--exec 				- Proceed with killing threads\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

