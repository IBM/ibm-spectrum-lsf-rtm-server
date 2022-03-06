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

// Turn off error reporting
error_reporting(0);

include(dirname(__FILE__) . "/../../include/cli_check.php");
include_once(dirname(__FILE__) . "/lib/metadata_api.php");

$dir = dirname(__FILE__);
chdir($dir);

$object_type = "";
$filename    = "";
$action      = "";
$debug       = FALSE;
$force       = FALSE;

/**
 * Parses command-line options passed to the CLI utility
 */

function parse_opts() {
	global $action, $filename, $object_type, $force, $debug;

	$shortopts = 't:f:lcdHhVv';
	$longopts  = array('load', 'configure', 'delete', 'list-valid-object-types', 'type:', 'file:', 'force', 'debug', 'help', 'version');
	$options   = getopt($shortopts, $longopts);

	$act_load = (isset($options['l']) || isset($options['load']));
	$act_del  = (isset($options['d']) || isset($options['delete']));
	$act_cfg  = (isset($options['c']) || isset($options['configure']));
	$act_list = (isset($options['list-valid-object-types']));

	if (isset($options['v']) || isset($options['V']) || isset($options['version'])){
		display_version();
	}

	if((!$act_load && !$act_del && !$act_cfg && !$act_list )                                                           //Action is required
		|| (($act_load && $act_del) || ($act_load && $act_cfg) || ($act_load && $act_list)                             //Action should be specified only one
			|| ($act_del && $act_cfg) || ($act_del && $act_list) || ($act_cfg && $act_list))
		|| (isset($options['t']) && empty($options['t'])) || (isset($options['type']) && empty($options['type']))      //Object Type option required a value
		|| (isset($options['f']) && empty($options['f'])) || (isset($options['file']) && empty($options['file']))      //File Path option required a value
		|| ((isset($options['l']) || isset($options['load']) || isset($options['c']) || isset($options['configure']))  //Load and Configure required '-f' option
			&& ((!isset($options['f']) && !isset($options['file'])) || (empty($options['f']) && empty($options['file']))))
		|| ((isset($options['l']) || isset($options['load']) || isset($options['d']) || isset($options['delete']))     //Load and Delete  required '-t' option
			&& ((!isset($options['t']) && !isset($options['type'])) || (empty($options['t']) && empty($options['type']))))
		|| (isset($options['h']) || isset($options['H']) || isset($options['help']))){
		display_help();
	}

	if ($act_load) {
		$action = "load";
	} else if ($act_del) {
		$action = "delete";
	} else if ($act_cfg) {
		$action = "config";
	} else if ($act_list) {
		$action = "list";
	}

	$force = (isset($options['force']));
	$debug = (isset($options['debug']));

	$object_type  = (isset($options['t']) ? $options['t'] : (isset($options['type']) ? $options['type'] : ''));
	$filename     = (isset($options['f']) ? $options['f'] : (isset($options['file']) ? $options['file'] : ''));
}

/**
 * Prints usage for this CLI utility
 */
function display_help() {
	global $config;
	echo "metadata_cli.php " . read_config_option('grid_version') . "\n\n";
	echo "Import meta configuration, load meta data, or delete meta data by options.\n";
	echo "Usage:\n\n";
	echo "metadata_cli.php -l|--load -t|--type='object type' -f|--file='csv file path' [--debug]\n";
	echo "metadata_cli.php -c|--configure -f|--file='xml file path' [--force] [--debug]\n";
	echo "metadata_cli.php -d|--delete -t|--type='object type' [--debug]\n";
	echo "metadata_cli.php -h|-H|--help|-v|-V|--version\n\n";
	echo "Actions:\n";
	echo "-t, --load                 - Load meta data from 'csv file' for specified 'object type'.\n";
	echo "-d, --delete               - Delete all meta data for specified 'object type'.\n";
	echo "-c, --configure            - Load and initial meta configuration from 'xml file'.\n";
	echo "--list-valid-object-types  - List valid meta object types under current RTM supporting.\n\n";
	echo "Options:\n";
	echo "-t, --type='object type'   - Specified meta object type for 'load' or 'delete' action.\n";
	echo "-f, --file='file path'     - Load file, 'csv file' for meta data, or 'xml file' for meta configuration.\n";
	echo "--force                    - Delete existing meta configuration of 'object types' in 'xml file'.\n";
	echo "--debug                    - Display verbose output during excution.\n";
	echo "-h, -H, --help             - Display this help and exit.\n";
	echo "-v, -V, --version          - Output version information and exit.\n";
	exit;
}

/*	display_version - displays version info */
function display_version () {
	global $config;
	echo 'RTM Meta Data Command Line Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	exit;
}

parse_opts();

if ($action == "load") {

	// Load the metadata
	$result = load_metadata($filename, $object_type, $debug);

	if (is_numeric($result)) {
		if ($result == 0) {
			printf("Metadata import completed.\n");
			exit(0);
		}
		else if ($result != 0) {
			printf($messages[$result]["message"] . "\n");
			printf("Metadata import completed.\n");
			exit($result);
		}
	} else if (!$result) {
		printf("Metadata import could not be completed successfully.\n");
		exit(1);
	}
} else if ($action == "config") {
	global $messages;

	$result = parse_metadata_conf($filename, $force, $debug);

	if (is_numeric($result)) {
		if ($result == 0) {
			printf($messages[116]["message"] . "\n");
			exit(0);
		} else {
			foreach (libxml_get_errors() as $error) {
				printf($error->message . "\n");
			}
			printf($messages[$result]["message"] . "\n");
			exit($result);
		}
	}
} else if ($action == "delete") {
	$result = delete_metadata($object_type);

	if (is_numeric($result)) {
		printf($messages[$result]["message"] . "\n");
		exit($result);
	}
}

exit(0);

