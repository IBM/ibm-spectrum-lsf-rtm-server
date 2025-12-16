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

print "Removing Invalid RUSAGE Records\n";

$rusage_records = db_fetch_assoc('SELECT DISTINCT clusterid, jobid, indexid, submit_time
	FROM grid_jobs_rusage;');

if (cacti_sizeof($rusage_records)) {
	print 'There are ' . cacti_sizeof($rusage_records) . " to check\n";
	foreach($rusage_records AS $record) {
		$status = db_fetch_row_prepared('SELECT *
			FROM grid_jobs
			WHERE clusterid = ?
			AND jobid = ?
			AND indexid = ?
			AND submit_time = ?',
			array($record['clusterid'], $record['jobid'], $record['indexid'], $record['submit_time']));

		if (cacti_sizeof($status)) {
			print '.';
		} else {
			print 'd';
			db_execute_prepared('DELETE FROM grid_jobs_rusage
				WHERE clusterid = ?
				AND jobid = ?
				AND indexid = ?
				AND submit_time= ?',
				array($record['clusterid'], $record['jobid'], $record['indexid'], $record['submit_time']));
		}
	}
}

