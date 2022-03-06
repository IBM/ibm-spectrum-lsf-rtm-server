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

$debug   = false;
$confirm = true;
$force   = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-f':
		$force = true;
		break;
	case '-d':
		$debug = true;
		break;
	case '-y':
		$confirm = false;
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

print "IBM Spectrum LSF RTM Cacti Database Backup Tool\n\n";

$stdin = fopen('php://stdin', 'r');
if ($confirm == true) {
	while (1) {
		print 'Are you sure you wish to backup the database? [y/n] ';
		$result = trim(strtolower(fgets($stdin)));

		if ($result == 'y') {
			fclose($stdin);
			break;
		} else if ($result == 'n') {
			print "\nOperation Canceled, Exiting!\n";
			fclose($stdin);
			exit(1);
		} else {
			print "You must enter either y or n.  Please try again\n";
		}
	}
}

cacti_log('NOTE: Backing Up Cacti, RTM and MySQL Databases - NOTE: Jobs and Jobs Rusage are NOT Backed Up Currently!!!!', true, 'GRID');

grid_backup_cacti_db(false, $force);

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Database Backup Tool ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "database_backup.php [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-d               - Display verbose output during execution\n";
	print "-f               - Force the backup even though it is disabled from the UI\n";
	print "-y               - Do not prompt for confirmation\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

