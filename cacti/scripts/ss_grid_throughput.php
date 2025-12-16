<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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
	print call_user_func_array('ss_grid_throughput', $_SERVER['argv']);
}

function ss_grid_throughput($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {

	if ($cmd == 'index') {
		$return_arr = ss_grid_throughput_getnames($clusterid, $arg1);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_throughput_getnames($clusterid, $arg1);
		$arr = ss_grid_throughput_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			} else {
				print '0';
			}
		}
	} elseif ($cmd == 'get') {
		$measure = $arg1;
		$index = $arg2;

		return ss_grid_throughput_getvalue($clusterid, $index, $measure);
	}
}

function ss_grid_throughput_getvalue($clusterid, $measure, $measure_value) {
	global $grid_int_dates;

	$return_arr = array();

	if ($measure == 'user') $column = 'user';
	elseif ($measure == 'total') $column = '';
	elseif ($measure == 'project') $column = 'projectName';
	elseif ($measure == 'queue') $column = 'queue';
	elseif ($measure == 'from_host') $column = 'from_host';
	elseif ($measure == 'exec_host') $column = 'exec_host';

	if (!isset($grid_int_dates[$clusterid])) {
		$grid_int_dates[$clusterid] = db_fetch_cell('SELECT
			MAX(grid_job_interval_stats.date_recorded) AS MaxOfdate_recorded
			FROM grid_job_interval_stats');
	}

	$arr = db_fetch_cell_prepared("SELECT
		SUM(grid_job_interval_stats.jobs_reaching_state) AS SumOfjobs_reaching_state
		FROM grid_job_interval_stats
		GROUP BY grid_job_interval_stats.clusterid, $column
		WHERE $column = ?
		AND clusterid = ?
		AND stat IN ('DONE', 'EXIT')
		ORDER BY grid_job_interval_stats.clusterid",
		array($measure_value, $clusterid));

	if ($arr == '') {
		$arr = 'U';
	}

	return trim($arr);
}

function ss_grid_throughput_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT queuename
		from grid_queues
		WHERE clusterid = ?
		ORDER BY queuename',
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['queuename'];
	}

	return $return_arr;
}

function ss_grid_throughput_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'description') {
		$arr = db_fetch_assoc_prepared('SELECT queuename AS qry_index,
			description AS qry_value
			FROM grid_queues
			WHERE clusterid = ?
			ORDER BY queuename',
			array($clusterid));
	} elseif ($info_requested == 'queue') {
		$arr = db_fetch_assoc_prepared('SELECT queuename AS qry_index,
			queuename AS qry_value
			FROM grid_queues
			WHERE clusterid = ?
			ORDER BY queuename',
			array($clusterid));
	} elseif ($info_requested == 'prio') {
		$arr = db_fetch_assoc_prepared('SELECT queuename AS qry_index,
			priority AS qry_value
			FROM grid_queues
			WHERE clusterid = ?
			ORDER BY queuename',
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
