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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_clustertp', $_SERVER['argv']);
}

function ss_grid_clustertp($clusterid = 0, $summary = 'no') {
	if ($summary == 'yes' || $clusterid == 0) {
		$cluster_stats = db_fetch_row('SELECT
			SUM(hourly_started_jobs) as started,
			SUM(hourly_done_jobs) as done,
			SUM(hourly_exit_jobs) as exited
			FROM grid_clusters');
	} else {
		$cluster_stats = db_fetch_row_prepared('SELECT
			hourly_started_jobs as started,
			hourly_done_jobs as done,
			hourly_exit_jobs as exited
			FROM grid_clusters
			WHERE clusterid = ?',
			array($clusterid));
	}

	if (!cacti_sizeof($cluster_stats)) {
		$result = "started:0 done:0 exited:0";
	} else {
		$result =
		'started:' . ss_grid_clustertp_value($cluster_stats['started']) . ' ' .
		'done:'    . ss_grid_clustertp_value($cluster_stats['done'])    . ' ' .
		'exited:'  . ss_grid_clustertp_value($cluster_stats['exited']);
	}

	return $result;
}

function ss_grid_clustertp_value($value) {
	if ($value == '') {
    	$value = 0;
	}

	return $value;
}
