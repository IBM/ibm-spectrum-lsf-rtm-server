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
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug   = FALSE;
$killall = FALSE;
$time    = 20;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
		$debug = TRUE;
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
echo "Starting Up Cacti/RTM\n";

// Enable Data Collection
set_config_option('poller_enabled', 'on');
set_config_option('grid_collection_enabled', 'on');
set_config_option('grid_system_collection_enabled', 'on');

/* truncate tables that affect restart */
db_execute('TRUNCATE grid_processes');

echo "Cacti/RTM Startup Completed\n";

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM System Startup Script ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: grid_system_startup.php [-t=xxx] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-d               - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

