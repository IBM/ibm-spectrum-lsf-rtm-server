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

	print call_user_func_array('ss_grid_lssched_bcs', $_SERVER['argv']);
}

function ss_grid_lssched_bcs($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lssched_bcs_getnames($host_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lssched_bcs_getnames($host_id);
		$arr = ss_grid_lssched_bcs_getinfo($host_id, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			} else {
				print '0';
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_lssched_bcs_getvalue($host_id, $index, $arg);
	}
}

function ss_grid_lssched_bcs_getvalue($host_id, $index, $column) {
	$return_arr = array();

	$index_arr = explode('|', $index);
	for($i=0;$i<3;$i++) {
		if (!isset($index_arr[$i]))
			$index_arr[$i]='';
	}

	if ($column == 'totalInUse') {
		$value = db_fetch_cell("SELECT SUM(totals)
			FROM (
				SELECT SUM(inuse + `over`) AS totals
				FROM grid_blstat_cluster_use
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
				UNION
				SELECT SUM(inuse + `over`) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
			) AS totals");
	} else if ($column == 'totalReserve') {
		$value = db_fetch_cell("SELECT SUM(totals)
			FROM (
				SELECT SUM(reserve) AS totals
				FROM grid_blstat_cluster_use
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
				UNION
				SELECT SUM(reserve) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
			) AS totals");
	} else if ($column == 'totalFree') {
		$value = db_fetch_cell("SELECT SUM(totals)
			FROM (
				SELECT SUM(free) AS totals
				FROM grid_blstat_cluster_use
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
				UNION
				SELECT SUM(free) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
			) AS totals");
	} else if ($column == 'totalDemand') {
		$value = db_fetch_cell("SELECT SUM(totals)
			FROM (
				SELECT SUM(need+demand) AS totals
				FROM grid_blstat_cluster_use
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
				UNION
				SELECT SUM(demand) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
			) AS totals");
	} else if ($column == 'totalOver') {
		$value = db_fetch_cell("SELECT SUM(totals)
			FROM (
				SELECT SUM(`over`) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
				UNION
				SELECT SUM(`over`) AS totals
				FROM grid_blstat_clusters
				WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "'  AND cluster='" . $index_arr[2] ."'
			) AS totals");
	}

	if (is_numeric($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_lssched_bcs_getnames($host_id) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	$arr = db_fetch_assoc_prepared("SELECT DISTINCT lsid_feature_cl
		FROM (
			SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS lsid_feature_cl
			FROM grid_blstat_cluster_use
			WHERE lsid = ?
			UNION
			SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS lsid_feature_cl
			FROM grid_blstat_clusters
			WHERE lsid = ?
		) AS union_vals
		ORDER BY lsid_feature_cl",
		array($lsid, $lsid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['lsid_feature_cl'];
	}

	return $return_arr;
}

function ss_grid_lssched_bcs_getinfo($host_id, $info_requested) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	if ($info_requested == 'lsid_feature_cl') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT qry_index, qry_value
			FROM (
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_value
				FROM grid_blstat_cluster_use
				WHERE lsid = ?
				UNION
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_value
				FROM grid_blstat_clusters
				WHERE lsid = ?
			) AS union_vals
			ORDER BY qry_index",
			array($lsid, $lsid));
	} elseif ($info_requested == 'cluster') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT qry_index, qry_value
			FROM (
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				cluster AS qry_value
				FROM grid_blstat_cluster_use
				WHERE lsid = ?
				UNION
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				cluster AS qry_value
				FROM grid_blstat_clusters
				WHERE lsid = ?
			) AS union_vals
			ORDER BY qry_index",
			array($lsid, $lsid));
	} elseif ($info_requested == 'region') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT qry_index, qry_value
			FROM (
				SELECT CONCAT_WS('', gbcu.lsid, '|', feature, '|', cluster, '') AS qry_index,
				region AS qry_value
				FROM grid_blstat_cluster_use AS gbcu
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid = gbcu.lsid
				WHERE gbcu.lsid = ?
				UNION
				SELECT CONCAT_WS('', gbcc.lsid, '|', feature, '|', cluster, '') AS qry_index,
				region AS qry_value
				FROM grid_blstat_clusters AS gbcc
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid = gbcc.lsid
				WHERE gbcc.lsid = ?
			) AS union_vals
			ORDER BY qry_index",
			array($lsid, $lsid));
	} elseif ($info_requested == 'collector') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT qry_index, qry_value
			FROM (
				SELECT CONCAT_WS('', gbcu.lsid, '|', feature, '|', cluster, '') AS qry_index,
				gbc.name AS qry_value
				FROM grid_blstat_cluster_use AS gbcu
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid = gbcu.lsid
				WHERE gbcu.lsid = ?
				UNION
				SELECT CONCAT_WS('', gbcc.lsid, '|', feature, '|', cluster, '') AS qry_index,
				gbc.name AS qry_value
				FROM grid_blstat_clusters AS gbcc
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid = gbcc.lsid
				WHERE gbcc.lsid = ?
			) AS union_vals
			ORDER BY qry_index",
			array($lsid, $lsid));
	} elseif ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT qry_index, qry_value
			FROM (
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				feature AS qry_value
				FROM grid_blstat_cluster_use
				WHERE lsid = ?
				UNION
				SELECT CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
				feature AS qry_value
				FROM grid_blstat_clusters
				WHERE lsid = ?
			) AS union_vals
			ORDER BY qry_index",
			array($lsid, $lsid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
