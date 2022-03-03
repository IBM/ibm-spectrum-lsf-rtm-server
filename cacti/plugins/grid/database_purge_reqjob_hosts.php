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

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = FALSE;
$form  = '';

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '--debug':
		$debug = TRUE;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}
echo "Removing Orphan Records from Database\n";

grid_purge_from_and_request_hosts();

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	echo 'RTM Database Purge Old Request and Job Hosts Tool ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "Usage:\n";
	echo "database_purge_reqjob_hosts.php [--debug] [-h | --help |-v | -V |--version]\n\n";
	echo "--debug          - Display verbose output during execution\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - Display this help message\n";
}

function grid_purge_from_and_request_hosts() {
	global $cnn_id;

	grid_debug('Removing Stale Requested Hosts');
	db_execute('DELETE gjh.* FROM grid_jobs_reqhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL');

	$num_found = db_affected_rows();

	grid_debug("Removed '$num_found' Stale Requested Hosts");

	grid_debug('Removing Stale Job Hosts');
	db_execute('DELETE gjh.* FROM grid_jobs_jobhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL');

	$num_found = db_affected_rows();

	grid_debug("Removed '$num_found' Stale Job Hosts");
}

