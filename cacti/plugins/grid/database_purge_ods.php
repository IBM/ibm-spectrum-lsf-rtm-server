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
include_once($config['library_path'] . '/api_device.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/rtm_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);
	switch ($arg) {
	case "-d":
		$debug = true;
		break;
    case "-v":
    case "-V":
    case "--version":
        display_version();
        exit;
	}
}

function list_affected_data_sources($dss) {
	foreach($dss as $dss_item) {
		$dss_name = db_fetch_cell('SELECT name_cache FROM data_template_data WHERE local_data_id=' . $dss_item);
		print "ID: $dss_item, Data Source Name: $dss_name\n";
	}
}

function display_version() {
	print 'IBM Spectrum LSF RTM Remove Orphaned Data Sources Utility ' . get_grid_version() . "\n";
	print rtm_copyright();
}

function display_ods_tooltip() {
	print "This utility removes orphaned Cacti Data Sources from your Cacti System.\n\n";
	print "You should only use this utility is directed by support to do so.  Please\n";
	print "note, if you wish to revert this change, you must restore your system\n";
	print "from the last Cacti backup!!!\n\n";
}

/* we need to access adodb */
global $cnn_id;

/* get some information from the user */
display_version();
display_ods_tooltip();

$proceed = true;

/* get the list of orphaned data sources */
$dss = array_rekey(db_fetch_assoc("SELECT dtr.local_data_id
	FROM data_template_rrd AS dtr
	LEFT JOIN graph_templates_item AS gti
	ON dtr.id=gti.task_item_id
	GROUP BY dtr.local_data_id
	HAVING COUNT(gti.task_item_id)=0 AND dtr.local_data_id>0"), "local_data_id", "local_data_id");

if (cacti_sizeof($dss)) {
	print "There are '" . cacti_sizeof($dss) . "' orpaned Data Sources on this system\n";

	$handle = fopen("php://stdin", "r");

	if ($handle) {
		while ( true ) {
			print "\nTo delete these Data Sources press 'Y', To list affected data sources press 'L' or 'N' to quit\n";
			print "[N]: ";
			$remove = fgets($handle);

			if (strtolower(trim($remove)) == "l") {
				print "\n";
				list_affected_data_sources($dss);
				print "\n";
				continue;
			}

			if (strtolower(trim($remove)) == "n") exit(0);

			if (strtolower(trim($remove)) == "y") break;

			print "ERROR: You provided incorrect input.  Please try again.\n";
		}
	} else {
		print "FATAL: Unable to read from standard in.  Please contact support\n";
		exit(-1);
	}
} else {
	print "NOTE: No orphaned Data Sources found.  Exiting!\n";
	exit(0);
}

print "Deleting old Data Sources.  Please be patient.\n";
api_data_source_remove_multi($dss);
print "Old Data Source Remove Complete.\n";
