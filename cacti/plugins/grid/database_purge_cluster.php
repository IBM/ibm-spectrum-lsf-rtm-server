#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/api_data_source.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$cluster = 0;
	$force   = false;
	$debug   = false;

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

		switch ($arg) {
		case '-d':
		case '--debug':
			$debug = true;
			break;
		case '-f':
		case '--force':
			$force = true;
			break;
		case '--clusterid':
			$cluster = trim($value);
			break;
		case '-h':
		case '-v':
		case '-V':
		case '--help':
			display_help();
			return 0;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}
}

if ($cluster > 0 && is_numeric($cluster)) {
	global $cnn_id;
	$total  = 0;
	$tables = array_rekey(db_fetch_assoc("SHOW TABLES LIKE 'grid%'"), 'Tables_in_' . $database_default . ' (grid%)', 'Tables_in_' . $database_default . ' (grid%)');
	if (cacti_sizeof($tables)) {
	foreach($tables as $t) {
		$columns = array_rekey(db_fetch_assoc("SHOW COLUMNS in $t"), 'Field', 'Field');
		if (array_key_exists('clusterid', $columns)) {
			if ($force) {
				if ($debug) {
					print "Deleting rows from '$t'\n";
				} else {
					print '.';
				}

				db_execute("DELETE FROM $t WHERE clusterid=?", array($cluster));

				if ($debug) {
					print db_affected_rows() . " Rows Removed from '$t'\n";
				}
			} else {
				$rows = db_fetch_cell_prepared("SELECT COUNT(*) FROM $t WHERE clusterid=?", array($cluster));
				if ($debug) {
					print 'There were ' . number_format($rows) . " Rows found in '$t'\n";
				}
				$total += $rows;
			}
		}
	}
	}

	if (!$force) {
		if ($total > 0) {
			print number_format($total) . " Records Found.  Purging this Cluster would clean up records\n";
		} else {
			print "No Records Found.  Purging this Cluster would NOT clean up records\n";
		}
	} else {
		print "\nOperation Completed\n";
	}
}

function display_help() {
	print 'RTM Purge Cluster Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')."Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	print "Usage:\n";
	print "database_purge_cluster.php --clusterid=[ID] --debug --force\n";
	print "Required:\n";
	print "    - clusterid: The id of the cluster\n";
	print "    - force: Force the execution without prompts\n";
	print "    - debug: Verbose messages\n";
}

