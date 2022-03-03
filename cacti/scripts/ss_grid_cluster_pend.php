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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_cluster_pend', $_SERVER['argv']);
}

function ss_grid_cluster_pend($clusterid = 0, $summary = 'no') {
	if ($summary == 'yes') {
		$pending_jobs = db_fetch_cell('SELECT
			SUM(pendjobs) as pending
			FROM grid_queues');
	}else{
		$pending_jobs = db_fetch_cell_prepared('SELECT
			SUM(pendjobs) as pending
			FROM grid_queues
			WHERE clusterid = ?',
			array($clusterid));
	}

	$result = ss_grid_cluster_pend_value($pending_jobs) . "\n";

	return trim($result);
}

function ss_grid_cluster_pend_value($value) {
	if ($value == '') {
    	$value = 0;
	}

	return $value;
}
