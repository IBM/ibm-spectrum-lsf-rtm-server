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
	print call_user_func_array('ss_grid_sres_stats', $_SERVER['argv']);
}

function ss_grid_sres_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_sres_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_sres_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_sres_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_sres_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_sres_stats_getvalue($clusterid, $index, $column) {
	$return_arr = array();

	switch ($column) {
	case 'maxValue':
		$column = 'value';
	case 'totalValue':
	case 'reservedValue':
		$value = db_fetch_cell_prepared("SELECT
			$column AS value
			FROM grid_hosts_resources
			WHERE grid_hosts_resources.host = 'ALLHOSTS'
			AND resource_name = ?
			AND grid_hosts_resources.clusterid = ?",
			array($index, $clusterid));

		break;
	}

	if ((!empty($value)) && (trim($value) != '-')) {
		if (!is_numeric($value)) {
			return '0';
		} else {
			return trim($value);
		}
	} else {
		return '0';
	}
}

function ss_grid_sres_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT resource_name
		FROM grid_hosts_resources
		WHERE clusterid = ?
		AND host = 'ALLHOSTS'
		AND resType IN ('1','2')
		ORDER BY resource_name",
		array($clusterid));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['resource_name'];
	}

	return $return_arr;
}

function ss_grid_sres_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'resource_name') {
		$arr = db_fetch_assoc_prepared("SELECT
			resource_name AS qry_index,
			resource_name AS qry_value
			FROM grid_hosts_resources
			WHERE clusterid = ?
			AND host = 'ALLHOSTS'
			AND resType IN('1','2')
			ORDER BY resource_name",
			array($clusterid));
	} else {
		/* for any other fields */
	}

	for ($i=0;($i<sizeof($arr));$i++) {
                $return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
