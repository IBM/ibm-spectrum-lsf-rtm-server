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

$debug = FALSE;
$form  = '';

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
		$debug = TRUE;
		break;
	case '-h':
		display_help();
		exit;
	case '-form':
		$form = ' USE_FRM';
		break;
	case '-v':
	case '-V':
		display_help();
		exit;
	case '--version':
		display_help();
		exit;
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}
echo "Repairing All Cacti Database Tables\n";

$tables = db_fetch_assoc('SHOW TABLES FROM ' . $database_default);

if (cacti_sizeof($tables)) {
	foreach($tables AS $table) {
		if (substr_count($table['Tables_in_' . $database_default], '_v')) {
			echo "Skipping Table due to partitioning '" . $table['Tables_in_' . $database_default] . "\n";
		}else{
			echo "Repairing Table -> '" . $table['Tables_in_' . $database_default] . "'";
			$statuses = db_fetch_assoc('REPAIR TABLE ' . $table['Tables_in_' . $database_default] . $form);
			if(cacti_sizeof($statuses)){
				$return_status = 'Successful'; //To keep the old behavior, include The storage engine for the table doesn't support repair
				foreach($statuses as $status) {
					if($status['Msg_type'] == 'status'){
						if($status['Msg_text'] != 'OK'){
							$return_status =  $status['Msg_text'];
						}
						break;
					}
				}
			}else{
				$return_status = 'Failed';
			}
			echo " " . $return_status . "\n";
		}
	}
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	echo 'RTM Database Repair Tool ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "Usage:\n";
	echo "database_repair.php [-d] [-h] [--form] [--help] [-v] [-V] [--version]\n\n";
	echo "-form            - Force rebuilding the indexes from the database creation syntax\n";
	echo "-d               - Display verbose output during execution\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n";
}

