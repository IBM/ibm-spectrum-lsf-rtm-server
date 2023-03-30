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

$dir = dirname(__FILE__);
chdir($dir);

if (strpos($dir, 'grid') !== false) {
	chdir('../../');
}

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/api_data_source.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$poller_id             = 0;
	$poller_name           = '';
	$poller_lsf_bindir     = '';
	$poller_rlsf_bindir    = '';
	$poller_location       = '';
	$poller_support        = '';

	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch ($arg) {
		case '-d':
			$debug = true;
			break;
		case '--pollerid':
			$poller_id = trim($value);
			break;
		case '--name':
			$poller_name = $value;
			break;
		case '--lsfbindir':
			$poller_lsf_bindir = trim($value);
			break;
		case '--rlsfbindir':
			$poller_rlsf_bindir = trim($value);
			break;
		case '--location':
			$poller_location = trim($value);
			break;
		case '--support':
			$poller_support = trim($value);
			break;
		case '--version':
			$snmp_ver = trim($value);
			break;
		case '-h':
		case '-H':
		case '--help':
		case '-v':
		case '-V':
		case '--version':
			display_help();
			return 0;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}
}

$poller_id = 0;

$save['poller_id']            = $poller_id;
$save['poller_name']          = $poller_name;
$save['poller_lbindir']       = $poller_lsf_bindir;
$save['poller_location']      = $poller_location;
$save['poller_support_info']  = $poller_support;

$poller_id = 0;

$poller_id = sql_save($save, 'grid_pollers', 'poller_id');
if ($poller_id) {
	print "Successfully added $poller_name Poller\n";
} else {
	print "Error, could not add poller\n";
}

function display_help() {
	echo 'RTM Add Poller Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "Usage:\n";
	echo "grid_add_poller.php --name=[NAME] --lsfbindir=[PATH]\n";
	echo "   --location=[NAME] --support=[NAME] [--rlsfbindir=[PATH]]\n\n";
	echo "Required:\n";
	echo "    - name: the poller name \n";
	echo "    - lsfbindir: the location of the local RTM poller\n";
	echo "    - location: the physical location of the poller (i.e. San Jose)\n";
	echo "    - support: the relevant contact information for the license servers\n\n";
	echo "Optional:\n";
	echo "    - rlsfbindir: the remote RTM poller locaton\n\n";
}

