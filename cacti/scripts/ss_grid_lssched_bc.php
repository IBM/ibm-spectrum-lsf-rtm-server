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

	print call_user_func_array('ss_grid_lssched_bc', $_SERVER['argv']);
}

function ss_grid_lssched_bc($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lssched_bc_getnames($host_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lssched_bc_getnames($host_id);
		$arr = ss_grid_lssched_bc_getinfo($host_id, $arg1);

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

		return ss_grid_lssched_bc_getvalue($host_id, $index, $arg);
	}
}

function ss_grid_lssched_bc_getvalue($host_id, $index, $column) {
	$return_arr = array();

	if ($column == 'featAvail') {
		$parts = explode('|', $index);
		$lsid  = $parts[0];
		$feat  = $parts[1];
		$list  = implode(',', array_rekey(
			db_fetch_assoc_prepared('SELECT lic_id
				FROM grid_blstat_service_domains AS gbsd
				INNER JOIN grid_blstat AS bs
				ON gbsd.service_domain=bs.service_domain
				WHERE bs.lsid = ?
				AND bs.feature = ?',
				array($lsid, $feat)),
			'lic_id', 'lic_id'
		));

		if (strlen($list)) {
			$value = db_fetch_cell_prepared("SELECT SUM(cast(feature_max_licenses as signed) - cast(feature_inuse_licenses as signed))
				FROM lic_services_feature_use AS fu
				INNER JOIN grid_blstat_feature_map AS fm
				ON fm.lic_feature=fu.feature_name
				WHERE fm.lsid = ?
				AND fm.bld_feature = ?
				AND service_id IN($list)",
				array($lsid, $feat));
		} else {
			$value = 0;
		}
	} elseif ($column == 'featQueued') {
		$parts = explode('|', $index);
		$lsid  = $parts[0];
		$feat  = $parts[1];
		$list  = implode(',', array_rekey(
			db_fetch_assoc_prepared('SELECT lic_id
				FROM grid_blstat_service_domains AS gbsd
				INNER JOIN grid_blstat AS bs
				ON gbsd.service_domain = bs.service_domain
				WHERE bs.lsid = ?
				AND bs.feature = ?',
				array($lsid, $feat)),
			'lic_id', 'lic_id'
		));

		if (strlen($list)) {
			$value = db_fetch_cell_prepared("SELECT SUM(feature_queued)
				FROM lic_services_feature_use AS fu
				INNER JOIN grid_blstat_feature_map AS fm
				ON fm.lic_feature=fu.feature_name
				WHERE fm.lsid = ?
				AND fm.bld_feature = ?
				AND service_id IN($list)",
				array($lsid, $feat));
		} else {
			$value = 0;
		}
	} elseif ($column == 'totalOthers') {
		$parts = explode('|', $index);
		$lsid  = $parts[0];
		$feat  = $parts[1];
		$value = db_fetch_cell_prepared('SELECT total_others
			FROM grid_blstat
			WHERE lsid = ?
			AND feature = ?',
			array($lsid, $feat));
	} else {
		$table  = 'grid_blstat_cluster_use';
		switch ($column) {
		case 'totalInUse':
			$column = 'inuse + `over` AS inuse';
			break;
		case 'totalReserve':
			$column = 'reserve';
			break;
		case 'totalFree':
			$column = 'free';
			break;
		case 'totalNeed':
			$column = 'need';
			break;
		case 'acumUse':
			$column = 'acum_use';
			break;
		case 'scaledAcum':
			$column = 'scaled_acum';
			break;
		}

		$index_arr = explode('|', $index);
        for($i=0;$i<4;$i++) {
           if (!isset($index_arr[$i]))
              $index_arr[$i]='';
        }

		$value = db_fetch_cell_prepared("SELECT $column
			FROM $table
			WHERE lsid = ?
			AND feature = ?
			AND project = ?
			AND cluster = ?",
			array($index_arr[0], $index_arr[1], $index_arr[2], $index_arr[3]));
	}

	if (is_numeric($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_lssched_bc_getnames($host_id) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));
	if (empty($lsid)) {
		return $return_arr;
	}

	$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS feature_pr_cl
		FROM grid_blstat_cluster_use
		WHERE lsid = ?
		ORDER BY CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '')",
		array($lsid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_pr_cl'];
	}

	return $return_arr;
}

function ss_grid_lssched_bc_getinfo($host_id, $info_requested) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared('SELECT lsid
		FROM grid_blstat_collectors
		WHERE cacti_host = ?',
		array($host_id));

	if (empty($lsid)) {
		return $return_arr;
	}

	if ($info_requested == 'lsid_feature_pr_cl') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS qry_value
			FROM grid_blstat_cluster_use
			WHERE lsid='$lsid'
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	} elseif ($info_requested == 'cluster') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			cluster AS qry_value
			FROM grid_blstat_cluster_use
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	} elseif ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			feature AS qry_value
			FROM grid_blstat_cluster_use
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	} elseif ($info_requested == 'region') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', gbcu.lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			region AS qry_value
			FROM grid_blstat_cluster_use AS gbcu
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=gbcu.lsid
			WHERE gbc.lsid = ?
			ORDER BY CONCAT_WS('', gbcu.lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	} elseif ($info_requested == 'collector') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', gbcu.lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			gbc.name AS qry_value
			FROM grid_blstat_cluster_use AS gbcu
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=gbcu.lsid
			WHERE gbc.lsid = ?
			ORDER BY CONCAT_WS('', gbcu.lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	} elseif ($info_requested == 'project') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '') AS qry_index,
			project AS qry_value
			FROM grid_blstat_cluster_use
			WHERE lsid = ?
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', project, '|', cluster, '')",
			array($lsid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
