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
	print call_user_func_array('ss_grid_bsla_stats', $_SERVER['argv']);
}

function ss_grid_bsla_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_bsla_stats_getnames($clusterid);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_bsla_stats_getnames($clusterid);
		$arr = ss_grid_bsla_stats_getinfo($clusterid, $arg1);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_bsla_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_bsla_stats_getvalue($clusterid, $bgslaname, $column) {
	$return_arr = array();

	$keys = explode('/', $bgslaname);

	if (cacti_sizeof($keys) < 2) {
		return '0';
	}

	if ($column != 'running' && $column != 'pending') {
		if ($column == 'guar_config') $column = 'guarantee_config';
		elseif ($column == 'guar_used') $column = 'guarantee_used';
		elseif ($column == 'total_used') $column = 'total_used';

		$arr = db_fetch_cell_prepared("SELECT
			$column
			FROM grid_guarantee_pool_distribution
			WHERE clusterid = ?
			AND name = ?
			AND consumer = ?",
			array($clusterid, $keys[0], $keys[1]));
	} else {
		if ($column == 'running') {
			$arr = db_fetch_cell_prepared('SELECT runSlots
				FROM grid_guarantee_pool_distribution
				WHERE clusterid = ?
				AND name = ?
				AND consumer = ?',
				array($clusterid, $keys[0], $keys[1]));
		} else {
			$arr = db_fetch_cell_prepared('SELECT pendSlots
				FROM grid_guarantee_pool_distribution
				WHERE clusterid = ?
				AND name = ?
				AND consumer = ?',
				array($clusterid, $keys[0], $keys[1]));
		}
	}

	if ($arr == NULL) {
		$arr = 0;
	}

	return trim($arr);
}

function ss_grid_bsla_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT CONCAT(name, "/", consumer) AS gsla
		FROM grid_guarantee_pool_distribution
		WHERE clusterid = ?
		ORDER BY gsla',
		array($clusterid));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['gsla'];
	}

	return $return_arr;
}

function ss_grid_bsla_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'gslaname') {
		$arr = db_fetch_assoc_prepared('SELECT CONCAT(name, "/", consumer) AS qry_index,
			CONCAT(name, "/", consumer) AS qry_value
			FROM grid_guarantee_pool_distribution
			WHERE grid_guarantee_pool_distribution.clusterid = ?
			ORDER BY qry_index',
			array($clusterid));
	} elseif ($info_requested == 'pooltype') {
		$arr = db_fetch_assoc_prepared('SELECT CONCAT(ggpd.name, "/", ggpd.consumer) AS qry_index,
			(CASE WHEN ggp.poolType IN ("package", "resource", "unknown")
				THEN CONCAT(IF(ggp.poolType!="unknown", ggp.poolType, "package"), " [", ggp.rsrcName, "]")
				ELSE ggp.poolType END) AS qry_value
			FROM grid_guarantee_pool_distribution AS ggpd
			JOIN grid_guarantee_pool AS ggp
			ON ggp.clusterid=ggpd.clusterid
			AND ggp.name=ggpd.name
			WHERE ggpd.clusterid = ?
			ORDER BY qry_index',
			array($clusterid));
	} elseif ($info_requested == 'poolname') {
		$arr = db_fetch_assoc_prepared('SELECT CONCAT(ggpd.name, "/", ggpd.consumer) AS qry_index,
			ggpd.name AS qry_value
			FROM grid_guarantee_pool_distribution AS ggpd
			JOIN grid_guarantee_pool AS ggp
			ON ggp.clusterid=ggpd.clusterid
			AND ggp.name=ggpd.name
			WHERE ggpd.clusterid = ?
			ORDER BY qry_index',
			array($clusterid));
	} elseif ($info_requested == 'slaname') {
		$arr = db_fetch_assoc_prepared('SELECT CONCAT(ggpd.name, "/", ggpd.consumer) AS qry_index,
			ggpd.consumer AS qry_value
			FROM grid_guarantee_pool_distribution AS ggpd
			JOIN grid_guarantee_pool AS ggp
			ON ggp.clusterid=ggpd.clusterid
			AND ggp.name=ggpd.name
			WHERE ggpd.clusterid = ?
			ORDER BY qry_index',
			array($clusterid));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
