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
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = false;
$exec  = false;
$query = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-q':
	case '--query':
		$query = true;
		break;
	case '--exec':
		$exec = true;
		break;
	case '-v':
	case '-V':
	case '--version':
		display_version();
		exit(0);
	case '-h':
	case '-H':
	case '--help':
		display_help();
		exit(0);
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
		display_help();
		exit(1);
	}
}

if (read_config_option('grid_partitioning_enable') !== 'on') {
	print 'FATAL: Record Partitioning is not enabled in RTM.  Exiting!' . PHP_EOL;
	exit(1);
}

if (!$query && !$exec) {
	print 'ERROR: You must specify either a --query or --exec option!' . PHP_EOL;
}

if ($query && !$exec) {
	print 'NOTE: Checking and reporting duplicate job records.' . PHP_EOL;
}

if ($exec) {
	print 'NOTE: Checking and removing duplicate job records.' . PHP_EOL;
}

$tables = db_fetch_assoc('SELECT table_name, partition
	FROM grid_table_partitions
	WHERE table_name="grid_jobs_finished"
	ORDER BY max_time ASC');

$tables[] = array('table_name' => 'grid_jobs_finished', 'partition' => -1);

$ptable = '';

// Create temporary table to handle duplicated records
db_execute("CREATE TEMPORARY TABLE `rdups` (
	`jobid` bigint(20) unsigned NOT NULL default '0',
	`indexid` int(10) unsigned NOT NULL default '0',
	`clusterid` int(10) unsigned NOT NULL default '0',
	`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`))
	ENGINE=InnoDB");

if (cacti_sizeof($tables)) {
	print 'There are ' . cacti_sizeof($tables) . ' to check.' . PHP_EOL;

	foreach($tables as $table) {
		db_execute('TRUNCATE TABLE `rdups`');

		if ($table['partition'] >= 0) {
			$ctable    = $table['table_name'] . '_v' . $table['partition'];
			$partition = $table['partition'];
		} else {
			$ctable    = $table['table_name'];
			$partition = -1;
		}

		if ($ptable == '') {
			$ptable = $ctable;
			continue;
		}

		db_execute('INSERT INTO `rdups` (clusterid, jobid, indexid, submit_time)
			SELECT p.clusterid, p.jobid, p.indexid, p.submit_time
			FROM ' . $ctable . ' AS c
			INNER JOIN ' . $ptable . ' AS p
			ON c.clusterid = p.clusterid
			AND c.jobid = p.jobid
			AND c.indexid = p.indexid
			AND c.submit_time = p.submit_time');

		$duplicates = db_fetch_cell('SELECT COUNT(*) FROM `rdups`');

		if ($duplicates && $query) {
			printf('Table \'%s\' has %d duplicate jobs also in table \'%s\'', $ptable, $duplicates, $ctable) . PHP_EOL;
		} elseif (!$duplicates) {
			printf('Table \'%s\' has NO duplicated jobs also in table \'%s\'', $ptable, $ctable) . PHP_EOL;
		}

		if ($duplicates && $exec) {
			remove_duplicates($ptable, $partition);
			printf('Removed %d duplicate jobs records in table \'%s\' and other reference tables.', $duplicates, $ptable) . PHP_EOL;
		}

		$ptable = $ctable;
	}
}

db_execute('DROP TEMPORARY TABLE `rdups`');

exit(0);

// Function to remove the duplicate records
function remove_duplicates($ptable, $partition) {
	// Delete duplicates from the older partition
	db_execute('DELETE p
		FROM ' . $ptable . ' AS p
		INNER JOIN `rdups` AS c
		ON c.clusterid = p.clusterid
		AND c.jobid = p.jobid
		AND c.indexid = p.indexid
		AND c.submit_time = p.submit_time');

	// Array of reference tables that might include jobs records
	$tables = array(
		'grid_jobs_jobhosts_finished',
		'grid_jobs_pendreasons_finished',
		'grid_jobs_reqhosts_finished'
	);

	// Delete duplicate from the older reference tables
	foreach($tables as $table) {
		$jtable = $table . '_' . $partition;
		db_execute('DELETE p
			FROM ' . $jtable . ' AS p
			INNER JOIN `rdups` AS c
			ON c.clusterid = p.clusterid
			AND c.jobid = p.jobid
			AND c.indexid = p.indexid
			AND c.submit_time = p.submit_time');
	}
}

function display_version() {
	global $config;

	print 'RTM Duplicate Job Pruner ' . read_config_option('grid_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . PHP_EOL . PHP_EOL;
}

function display_help() {
	display_version();

	print 'Usage:' . PHP_EOL;
	print 'database_prune_duplicate_jobs.php [--query] | [--exec]' . PHP_EOL . PHP_EOL;
	print '--query          - Check RTM Jobs partitions for duplicate records' . PHP_EOL;
	print '--exec           - Remove duplicate jobs records in RTM Job partitions' . PHP_EOL;
}
