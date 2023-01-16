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
$parms = $_SERVER['argv'];
array_shift($parms);

$force_version = '';

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-h':
	case '-H':
	case '--help':
		display_help();
		exit;
	case '-v':
	case '-V':
	case '--version':
		display_version();
		exit;
	case "--force-ver":
		$force_version = trim($value);

		switch(rtm_plugin_ver_validate($force_version, RTM_VERSION_LATEST_GA)){
			case -1:
				cacti_log("ERROR: version number '$force_version' is not supportted", true, 'UPGRADE');
				exit;
			case  1:
				cacti_log("ERROR: Invalid gridalarms plugin version number: '$force_version'", true, 'UPGRADE');
				exit;
			case 0:
			default:
				cacti_log("NOTE: Upgrading gridalarms from v$force_version", true, 'UPGRADE');
		}
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

if(!empty($force_version)){
	$gridalarms_version = $force_version;
} else {
	$gridalarms_version = read_config_option('gridalarms_version');
}
$gridalarms_current_version = get_gridalarms_version();

rtm_plugin_upgrade_fp($gridalarms_version, $gridalarms_current_version, 'gridalarms');

function display_version() {
	print 'IBM Spectrum LSF RTM Fix Pack Database Upgrader ' . get_gridalarms_version() . "\n";
	print rtm_copyright();
}

/*	display_help - displays the usage of the function */
function display_help () {
	echo "database_upgrade_fp.php " . get_gridalarms_version() . "\n\n";
	echo "Upgrade Alerting plugin dababase, host/graph/datasource template, and dataquery for FixPack deployment.\n";
	echo "Usage:\n";
	print "database_upgrade_fp.php [--force-ver=VER]\n";
	print "database_upgrade_fp.php [-h|--help] [-v|-V|--version]\n\n";
	print "--force-ver      - Forces the upgrade to begin at the specified version\n";
	print "-h|-H|--help     - Display this help and exit.\n";
	print "-v|-V|--version  - Output version information and exit.\n";
}
