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
	print call_user_func_array('ss_grid_benchmarks', $_SERVER['argv']);
}

function ss_grid_benchmarks($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_benchmarks_getnames($clusterid, $arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_benchmarks_getnames($clusterid, $arg1);
		$arr = ss_grid_benchmarks_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_benchmarks_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_benchmarks_getvalue($clusterid, $index, $column) {
	global $config;
	static $benchmarks;

	if (!isset($benchmarks[$index])) {
		$benchmarks[$index] = db_fetch_row_prepared('SELECT *
			FROM grid_clusters_benchmarks
			WHERE benchmark_id = ?',
			array($index));
	}

	if (!isset($benchmarks[$index]['status'])) {
		return 'U';
	}elseif (($benchmarks[$index]['status'] == 1) ||
		($benchmarks[$index]['status'] == 2) ||
		($benchmarks[$index]['status'] == 3) ||
		($benchmarks[$index]['status'] == 5) ||
		($benchmarks[$index]['status'] == 15) ||
		($benchmarks[$index]['status'] == 17) ||
		($benchmarks[$index]['status'] == 16)) {
		return trim($benchmarks[$index][$column]);
	}else{
		return 'U';
	}
}

function ss_grid_benchmarks_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT benchmark_id AS benchmarkId
		FROM grid_clusters_benchmarks
		WHERE clusterid = ?
		ORDER BY 1',
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['benchmarkId'];
	}

	return $return_arr;
}

function ss_grid_benchmarks_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'benchmarkName') {
		$arr = db_fetch_assoc_prepared('SELECT benchmark_id qry_index,
			benchmark_name AS qry_value
			FROM grid_clusters_benchmarks
			WHERE clusterid = ?
			ORDER BY qry_index',
			array($clusterid));

			for ($i=0;($i<cacti_sizeof($arr));$i++) {
				$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
			}
	}
	else if ($info_requested == 'benchmarkId') {
		$arr = db_fetch_assoc_prepared('SELECT benchmark_id qry_index,
			benchmark_id AS qry_value
			FROM grid_clusters_benchmarks
			WHERE clusterid = ?
			ORDER BY qry_index',
			array($clusterid));

			for ($i=0;($i<cacti_sizeof($arr));$i++) {
				$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
			}
	}

	return $return_arr;
}

function ss_grid_benchmarks_get_maxdate($clusterid) {
	global $config;

	$last_string = read_config_option('grid_update_time_lsload_' . $clusterid);
	$last_array = explode(' ', $last_string);
	$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));

	return strtotime($date_string);
}
