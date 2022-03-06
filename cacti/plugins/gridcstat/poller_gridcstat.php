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

/* take the start time to log performance data */
$start = microtime(true);

/* get the srm polling cycle */
ini_set("max_execution_time", "0");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$debug          = FALSE;
$forcerun       = FALSE;
$forcerun_maint = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
		$debug = TRUE;
		break;
	case "-h":
	case "-v":
	case "-V":
	case "--version":
	case "--help":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}

gridcstat_debug("NOTE: About to Enter GRIDCSTAT Poller Pprocessing");

function gridcstat_debug($message) {
	global $debug;

	if ($debug) {
		echo $message . "\n";
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	echo "RTM Cluster Statistics Poller " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
	echo "Usage:\n";
	echo "poller_gridcstat.php [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	echo "-d               - Display verbose output during execution\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n";
}

?>
