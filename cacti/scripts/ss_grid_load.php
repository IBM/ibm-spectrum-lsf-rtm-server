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
	print call_user_func_array('ss_grid_load', $_SERVER['argv']);
}

function ss_grid_load($hostname = '', $clusterid = 0, $summary = 'no') {
	if ($hostname == '' || $hostname == 'localhost' || $summary == 'yes') {
		if ($clusterid == 0) {
			$job_stats = db_fetch_row("SELECT SUM(r15s) as t_r15s, SUM(r1m) as t_r1m, SUM(r15m) as t_r15m
				FROM grid_load
				WHERE status NOT LIKE 'U%'");
		} else {
			$job_stats = db_fetch_row_prepared("SELECT SUM(r15s) as t_r15s, SUM(r1m) as t_r1m, SUM(r15m) as t_r15m
				FROM grid_load
				WHERE status NOT LIKE 'U%'
				AND clusterid = ?",
				array($clusterid));
		}
	} else {
		if ($clusterid == 0) {
			$job_stats = db_fetch_row_prepared("SELECT SUM(r15s) as t_r15s, SUM(r1m) as t_r1m, SUM(r15m) as t_r15m
				FROM grid_load
				WHERE status NOT LIKE 'U%'
				AND host = ?",
				array($hostname));
		} else {
			$job_stats = db_fetch_row_prepared("SELECT r15s as t_r15s, r1m as t_r1m, r15m as t_r15m
				FROM grid_load
				WHERE status NOT LIKE 'U%'
				AND clusterid = ?
				AND host = ?",
				array($clusterid, $hostname));
		}
	}

	if (empty($job_stats['t_r15s'])) $job_stats['t_r15s'] = 0;
	if (empty($job_stats['t_r1m']))  $job_stats['t_r1m'] = 0;
	if (empty($job_stats['t_r15m'])) $job_stats['t_r15m'] = 0;

	$result =
		'r15s:' . ss_grid_load_value($job_stats['t_r15s']) . ' ' .
		'r1m:'  . ss_grid_load_value($job_stats['t_r1m'])  . ' ' .
		'r15m:' . ss_grid_load_value($job_stats['t_r15m']);

	return $result;
}

function ss_grid_load_value($value) {
	if ($value == '') {
    	$value = 0;
	}

	return $value;
}
