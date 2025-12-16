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

	print call_user_func_array('ss_grid_lssched_bcms', $_SERVER['argv']);
}

function ss_grid_lssched_bcms($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lssched_bcms_getnames($host_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lssched_bcms_getnames($host_id);
		$arr = ss_grid_lssched_bcms_getinfo($host_id, $arg1);

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

		return ss_grid_lssched_bcms_getvalue($host_id, $index, $arg);
	}
}

function ss_grid_lssched_bcms_getvalue($host_id, $index, $column) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	$index_arr = explode('|', $index);

	for($i=0;$i<3;$i++) {
		if (!isset($index_arr[$i]))
			$index_arr[$i]='';
	}

	switch($column) {
	case 'alloc':
	case 'reserve':
	case 'free':
	case 'peak':
	case 'over':
	case 'inuse':
	case 'demand':
		$value = db_fetch_cell_prepared("SELECT `$column`
			FROM grid_blstat_clusters
			WHERE lsid = ?
			AND feature = ?
			AND cluster = ?",
			array($index_arr[0], $index_arr[1], $index_arr[2]));

		break;
	case 'share':
		$parts    = explode('|', $index);
		$bfeat    = $parts[1];
		$licenses = 0;

		$flexfeature = db_fetch_cell_prepared('SELECT lic_feature
			FROM grid_blstat_feature_map
			WHERE lsid = ?
			AND bld_feature = ?',
			array($lsid, $bfeat));

		if ($flexfeature != '') {
			$service_domains = array_rekey(
				db_fetch_assoc_prepared('SELECT service_domain
					FROM grid_blstat_clusters
					WHERE lsid = ?
					AND lsid = ?
					AND feature = ?
					AND cluster = ?',
					array($lsid, $index_arr[0], $index_arr[1], $index_arr[2])),
				'service_domain', 'service_domain'
			);

			$lic_ids = array_rekey(
				db_fetch_assoc("SELECT lic_id
					FROM grid_blstat_service_domains
					WHERE service_domain IN('" . implode("','", $service_domains) . "')"),
				'lic_id', 'lic_id'
			);

			if (!empty($lic_ids)) {
				$licenses = db_fetch_cell_prepared("SELECT SUM(feature_max_licenses)
					FROM lic_services_feature_use
					WHERE feature_name = ?
					AND service_id IN (" . implode(',', $lic_ids) . ")",
					array($flexfeature));
			}
		}

		if (empty($licenses)) {
			$value = 0;
		} else {
			$value = db_fetch_cell_prepared("SELECT (share/100)*($licenses) AS share
				FROM grid_blstat_clusters
				WHERE lsid = ?
				AND feature = ?
				AND cluster = ?",
				array($index_arr[0], $index_arr[1], $index_arr[2]));
		}

		break;
	}

	if (is_numeric($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_lssched_bcms_getnames($host_id) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	$arr = db_fetch_assoc_prepared("SELECT
		CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS feature_cl
		FROM grid_blstat_clusters
		WHERE lsid = ?
		ORDER BY feature_cl",
		array($lsid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_cl'];
	}

	return $return_arr;
}

function ss_grid_lssched_bcms_getinfo($host_id, $info_requested) {
	$return_arr = array();
	$arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	if ($info_requested == 'lsid_feature_cl') {
		$arr = db_fetch_assoc_prepared("SELECT
			CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
			CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_value
			FROM grid_blstat_clusters
			WHERE lsid = ?
			ORDER BY qry_index",
			array($lsid));
	} elseif ($info_requested == 'cluster') {
		$arr = db_fetch_assoc_prepared("SELECT
			CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
			cluster AS qry_value
			FROM grid_blstat_clusters
			WHERE lsid = ?
			ORDER BY qry_index",
			array($lsid));
	} elseif ($info_requested == 'collector') {
		$arr = db_fetch_assoc_prepared("SELECT DISTINCT CONCAT_WS('', gbc.lsid, '|', feature, '|', cluster, '') AS qry_index,
			gbc.name AS qry_value
			FROM grid_blstat_clusters
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=grid_blstat_clusters.lsid
			WHERE gbc.lsid = ?
			ORDER BY qry_index",
			array($lsid));
	} elseif ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT
			CONCAT_WS('', lsid, '|', feature, '|', cluster, '') AS qry_index,
			feature AS qry_value
			FROM grid_blstat_clusters
			WHERE lsid = ?
			ORDER BY qry_index",
			array($lsid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
