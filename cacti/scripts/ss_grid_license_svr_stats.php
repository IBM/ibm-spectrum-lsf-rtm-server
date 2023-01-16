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
	print call_user_func_array('ss_grid_license_svr_stats', $_SERVER['argv']);
}

function ss_grid_license_svr_stats($cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_license_svr_stats_getnames();

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_license_svr_stats_getnames();
		$arr = ss_grid_license_svr_stats_getinfo($arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_license_svr_stats_getvalue($index, $arg);
	}
}

function ss_grid_license_svr_stats_getvalue($server, $column) {
	$return_arr = array();

	if ($column == 'avgTime') $column = 'avg_time';
	else if ($column == 'maxTime') $column = 'max_time';
	else if ($column == 'lastTime') $column = 'cur_time';

	$arr = db_fetch_cell_prepared("SELECT $column
		FROM lic_services
		WHERE service_id = ?",
		array($server));

	if (empty($arr)) {
		$arr = 0;
	}

	return trim($arr);
}

function ss_grid_license_svr_stats_getnames() {
	$return_arr = array();

	$arr = db_fetch_assoc('SELECT service_id
		FROM lic_services
		ORDER BY server_name');

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['service_id'];
	}

	return $return_arr;
}

function ss_grid_license_svr_stats_getinfo($info_requested) {
	$return_arr = array();

	if ($info_requested == 'serverName') {
		$arr = db_fetch_assoc('SELECT service_id AS qry_index, server_name AS qry_value
			FROM lic_services
			ORDER BY server_name');
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

