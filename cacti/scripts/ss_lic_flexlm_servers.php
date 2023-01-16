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
	print call_user_func_array('ss_lic_get_servers', $_SERVER['argv']);
}

function ss_lic_get_servers($lic_server_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_lic_servers_getnames($lic_server_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_lic_servers_getnames($lic_server_id);
		$arr = ss_lic_servers_getinfo($lic_server_id, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_lic_servers($lic_server_id, $index, $arg);
	}
}

function ss_lic_servers($lic_server_id, $featurename, $column) {
	$return_arr = array();

	if ($column == 'inuse') {
		$column = 'SUM(feature_inuse_licenses)';
	} elseif ($column == 'maxavail') {
		$column = 'SUM(feature_max_licenses)';
	} elseif ($column == 'reserved') {
		$column = 'SUM(feature_reserved)';
	} elseif ($column == 'queued') {
		$column = 'SUM(feature_queued)';
	}

	$arr = db_fetch_cell_prepared("SELECT
		$column
		FROM lic_services_feature_use
		WHERE service_id = ?",
		array($lic_server_id));

	if ($arr == '') {
		return 0;
	} else {
		return trim($arr);
	}
}

function ss_lic_servers_getnames($lic_server_id) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT DISTINCT server_name
		FROM lic_services
		WHERE service_id = ?
		ORDER BY server_name',
		array($lic_server_id));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['server_name'];
	}

	return $return_arr;
}

function ss_lic_servers_getinfo($lic_server_id, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'servername') {
		$arr = db_fetch_assoc_prepared('SELECT DISTINCT server_name AS qry_index,
			server_name AS qry_value
			FROM lic_services AS ls
			INNER JOIN lic_services_feature_use AS lsfu
			ON ls.service_id = lsfu.service_id
			WHERE ls.service_id = ?
			ORDER BY server_name',
			array($lic_server_id));

	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
