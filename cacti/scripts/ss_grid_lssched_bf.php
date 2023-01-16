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

	print call_user_func_array('ss_grid_lssched_bf', $_SERVER['argv']);
}

function ss_grid_lssched_bf($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lssched_bf_getnames($host_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lssched_bf_getnames($host_id);
		$arr = ss_grid_lssched_bf_getinfo($host_id, $arg1);

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

		return ss_grid_lssched_bf_getvalue($host_id, $index, $arg);
	}
}

function ss_grid_lssched_bf_getvalue($host_id, $index, $column) {
	$return_arr = array();
	$table      = '';

	$lsid = db_fetch_cell("SELECT lsid FROM grid_blstat_collectors WHERE cacti_host=$host_id");

	switch ($column) {
		case 'totalInUse':
			$column = 'total_inuse';
			$table  = 'grid_blstat';
			break;
		case 'totalReserve':
			$column = 'total_reserve';
			$table  = 'grid_blstat';
			break;
		case 'totalFree':
			$column = 'total_free';
			$table  = 'grid_blstat';
			break;
		case 'totalOthers':
			$column = 'total_others';
			$table  = 'grid_blstat';
			break;
		case 'totalAvail':
			$column = 'total';
			$table  = 'grid_blstat_distribution';

			break;
		case 'totalQueued':
			$column = 'queued';
			$table  = '';

			break;
		case 'lsfUse':
			$column = 'lsf_use';
			$table  = 'grid_blstat_distribution';
			break;
		case 'lsfAvail':
			$column = 'lsf_avail';
			$table  = 'grid_blstat_distribution';
			break;
		case 'lsfFree':
			$column = 'lsf_free';
			$table  = 'grid_blstat_distribution';
			break;
		case 'nonLsf':
			$column = 'non_lsf_use';
			$table  = 'grid_blstat_distribution';
			break;
	}

	$index_arr = explode('|', $index);
	for($i=0;$i<3;$i++) {
		if (!isset($index_arr[$i]))
			$index_arr[$i]='';
	}

	if ($table != '') {
		$value = db_fetch_cell_prepared("SELECT $column
			FROM $table
			WHERE lsid = ?
			AND feature = ?
			AND service_domain = ?",
			array($index_arr[0], $index_arr[1], $index_arr[2]));
	} else {
		$value = db_fetch_cell("SELECT SUM(feature_queued)
			FROM lic_services_feature_use
			WHERE '$index' LIKE CONCAT_WS('$lsid|', feature_name, '%')");
	}

	if (is_numeric($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_lssched_bf_getnames($host_id) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '') AS feature_sd
		FROM grid_blstat
		WHERE lsid = ?
		ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '')",
		array($lsid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_sd'];
	}

	return $return_arr;
}

function ss_grid_lssched_bf_getinfo($host_id, $info_requested) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	if ($info_requested == 'lsid_feature_sd') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '') AS qry_index,
			CONCAT_WS('', lsid, '|', feature, '|', service_domain, '') AS qry_value
			FROM grid_blstat
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '')",
			array($lsid));
	} elseif ($info_requested == 'sd') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '') AS qry_index,
			service_domain AS qry_value
			FROM grid_blstat
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '')",
			array($lsid));
	} elseif ($info_requested == 'region') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', gb.lsid, '|', feature, '|', service_domain, '') AS qry_index,
			region AS qry_value
			FROM grid_blstat AS gb
			INNER JOIN grid_blstat_collectors AS gbc
			ON gb.lsid = gbc.lsid
			WHERE gb.lsid = ?
			ORDER BY CONCAT_WS('', gb.lsid, '|', feature, '|', service_domain, '')",
			array($lsid));
	} elseif ($info_requested == 'collector') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', gb.lsid, '|', feature, '|', service_domain, '') AS qry_index,
			gbc.name AS qry_value
			FROM grid_blstat AS gb
			INNER JOIN grid_blstat_collectors AS gbc
			ON gb.lsid = gbc.lsid
			WHERE gb.lsid = ?
			ORDER BY CONCAT_WS('', gb.lsid, '|', feature, '|', service_domain, '')",
			array($lsid));
	} elseif ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '') AS qry_index,
			feature AS qry_value
			FROM grid_blstat
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '')",
			array($lsid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
