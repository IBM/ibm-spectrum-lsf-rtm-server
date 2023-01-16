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
include_once(dirname(__FILE__) . '/setup.php');
include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['library_path'] . '/rtm_db_upgrade.php');

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

global $debug;

$debug         = false;
$system_type   = "large";
$force_version = '';

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case "-d":
		$debug = true;
		break;
	case "--force-ver":
		$force_version = trim($value);
		if (cacti_version_compare($force_version, '9.1', '<')) {
			$force_version = '8.3';
		}

		switch(rtm_plugin_ver_validate($force_version)){
			case -1:
				cacti_log("ERROR: version number '$force_version' is not supportted", true, 'UPGRADE');
				exit;
			case  1:
				cacti_log("ERROR: Invalid grid plugin version number: '$force_version'", true, 'UPGRADE');
				exit;
			case 0:
			default:
				cacti_log("NOTE: Upgrading grid from v$force_version", true, 'UPGRADE');
		}
		break;
	case "--type":
		$system_type = strtolower($value);
		break;
	case "-v":
	case "-V":
	case "--version":
		display_version();
		exit;
	case "-h":
	case "-H":
	case "--help":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}
$system_type='large';
if (($system_type == "") ||
	(($system_type != "large") &&
	($system_type != "standard"))) {
	print "FATAL: You must specify either 'standard' or 'large' system types (--type)\n";
	display_help();
	exit(-1);
}

/* set execution params */
ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");

if(!empty($force_version)){
	$grid_version = $force_version;
	$grid_db_version = $force_version;
} else {
	$grid_version = read_config_option('grid_version');
	$grid_db_version = read_config_option('grid_db_version');
}

$grid_current_version = get_grid_version();

rtm_plugin_upgrade_ga($grid_version, $grid_current_version);

function display_version() {
	print 'IBM Spectrum LSF RTM Database Upgrader ' . get_grid_version() . "\n";
	print rtm_copyright();
}

/*	display_help - displays the usage of the function */
function display_help () {
	print "database_upgrade.php " . get_grid_version() . "\n\n";
	print "Upgrade Grid plugin dababase, host/graph/datasource template, and dataquery.\n";
	print "Usage:\n";
	print "database_upgrade.php [--force-ver=VER] [-d] \n";
	print "database_upgrade.php [-h|--help] [-v|-V|--version]\n\n";
//	print "--type           - Defines the system type.  Determines if tables should be transactional of not\n";
	print "--force-ver      - Forces the upgrade to begin at the specified version\n";
	print "-d               - Display verbose output during execution\n";
	print "-h|-H|--help     - Display this help and exit.\n";
	print "-v|-V|--version  - Output version information and exit.\n";
}
