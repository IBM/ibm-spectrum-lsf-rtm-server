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
	print call_user_func_array('ss_lic_get_users_features', $_SERVER['argv']);
}

function ss_lic_get_users_features($lic_server_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_lic_users_features_getnames($lic_server_id);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_lic_users_features_getnames($lic_server_id);
		$arr = ss_lic_users_features_getinfo($lic_server_id, $arg1);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_lic_users_features($lic_server_id, $index, $arg);
	}
}

function ss_lic_users_features($lic_server_id, $featurename, $column) {

	$return_arr = array();

	$feat_explode = explode('=', $featurename);
	$i = 0;
	$feature_name = '';

	if (cacti_sizeof($feat_explode) > 3) {
		for ($i = 2;$i < sizeof($feat_explode); $i++) {
			if (strlen($feature_name) > 0) {
				$feature_name .= '='.$feat_explode[$i];
			} else {
				$feature_name = $feat_explode[$i];
			}
		}
	} else {
		$feature_name = $feat_explode[2];
	}

	$current_time = date('Y-m-d H:i:s');
	if (read_config_option('poller_interval') == 300) {
		$minus_time = 5;
	} else {
		$minus_time = 1;
	}
	$earlier_time = mktime(date('H'), date('i')-$minus_time, date('s'), date('n'), date('j'), date('Y'));
	$earlier_time = strftime('%F %H:%M:%S', $earlier_time);

	if ($column == 'inuse') {
		$column_name = 'SUM(tokens_acquired)';
		$table = 'lic_services_feature_details';
	} elseif ($column == 'maxavail') {
		$column_name = 'SUM(feature_max_licenses)';
		$table = 'lic_services_feature_use';
	} elseif ($column == 'denied') {
		$column_name = 'DENIED';
		$table = 'lic_flexlm_log';
	} elseif ($column == 'queued') {
		$column_name = 'QUEUED';
		$table = 'lic_flexlm_log';
	}

	if ($column == 'inuse') {
		$arr = db_fetch_cell_prepared("SELECT
			$column_name
			FROM $table
			WHERE feature_name = ?
			AND username = ?
			AND service_id = ?",
			array($feature_name, $feat_explode[1], $lic_server_id));

	} elseif ($column == 'maxavail') {
		$arr = db_fetch_cell_prepared("SELECT
			$column_name
			FROM $table
			WHERE feature_name = ?
			AND service_id = ?",
			array($feature_name, $lic_server_id));
	} else {
		$arr = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM $table
			WHERE action = ?
			AND portatserver = ?
			AND feature = ?
			AND user = ?
			AND datetime BETWEEN ? AND ?",
			array($column_name, $lic_server_id, $feature_name, $feat_explode[1], $earlier_time, $current_time));
	}

	if ($arr == '') {
		return 0;
	} else {
		return $arr;
	}
}

function ss_lic_users_features_getnames($lic_server_id) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT CONCAT(service_id, '=', username, '=', feature_name) AS feature_name
		FROM lic_services_feature_details
		WHERE service_id = ?
		AND username != ''
		ORDER BY feature_name",
		array($lic_server_id));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_name'];
	}

	return $return_arr;
}

function ss_lic_users_features_getinfo($lic_server_id, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(service_id, '=', username, '=', feature_name) AS qry_index,
			CONCAT(username, '-', feature_name) AS qry_value
			FROM lic_services_feature_details AS lsfd
			WHERE service_id = ?
			AND username != ''
			ORDER BY feature_name",
			array($lic_server_id));
	} elseif ($info_requested == 'user') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(service_id, '=', username, '=', feature_name) AS qry_index,
			username AS qry_value
			FROM lic_services_feature_details AS lsfd
			WHERE service_id = ?
			AND username != ''
			ORDER BY username",
			array($lic_server_id));
	} elseif ($info_requested == 'licenses') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '=', lsfd.username, '=', lsfu.feature_name) AS qry_index,
			feature_max_licenses AS qry_value
			FROM lic_services_feature_use AS lsfu
			INNER JOIN lic_services_feature_details AS lsfd
			ON lsfu.service_id = lsfd.service_id
			AND lsfu.feature_name = lsfd.feature_name
			WHERE lsfu.service_id = ?
			AND lsfd.username != ''
			ORDER BY lsfu.feature_name",
			array($lic_server_id));
	} elseif ($info_requested == 'index') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfd.service_id, '=', username, '=', feature_name) AS qry_index,
			CONCAT(lsfd.service_id, '=', username, '=', feature_name) AS qry_value
			FROM lic_services_feature_details AS lsfd
			WHERE username != ''
			AND service_id = ?
			ORDER BY feature_name",
			array($lic_server_id));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
