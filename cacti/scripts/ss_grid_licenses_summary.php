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
	print call_user_func_array('ss_grid_licenses_summary', $_SERVER['argv']);
}

function ss_grid_licenses_summary($hostname = '', $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_licenses_summary_getnames($hostname);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_licenses_summary_getnames($hostname);
		$arr = ss_grid_licenses_summary_getinfo($hostname, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_licenses_summary_getvalue($hostname, $index, $arg);
	}
}

function ss_grid_licenses_summary_getvalue($hostname, $featurename, $column) {
	$return_arr = array();

	if ($column == 'inuse') $column = 'SUM(feature_inuse_licenses)';
	else if ($column == 'maxavail') $column = 'SUM(feature_max_licenses)';
	else if ($column == 'reserved') $column = 'SUM(feature_reserved)';
	else if ($column == 'queued') $column = 'SUM(feature_queued)';

	$arr = db_fetch_cell_prepared("SELECT
		$column
		FROM lic_services_feature_use
		WHERE feature_name = ?",
		array($featurename));

	if ($arr == '') {
		return 0;
	} else {
		return trim($arr);
	}
}

function ss_grid_licenses_summary_getnames($hostname) {
	$return_arr = array();

	$arr = db_fetch_assoc('SELECT DISTINCT feature_name
		FROM lic_services_feature_use
		ORDER BY feature_name');

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_name'];
	}

	return $return_arr;
}

function ss_grid_licenses_summary_getinfo($hostname, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'feature') {
		$arr = db_fetch_assoc('SELECT DISTINCT feature_name AS qry_index,
			feature_name AS qry_value
			FROM lic_services_feature_use
			ORDER BY feature_name');
	} elseif ($info_requested == 'vendor') {
		$arr = db_fetch_assoc('SELECT DISTINCT feature_name AS qry_index,
			vendor_daemon AS qry_value
			FROM lic_services_feature_use
			INNER JOIN lic_services
			ON lic_services.service_id=lic_services_feature_use.service_id
			ORDER BY feature_name');
	} elseif ($info_requested == 'licenses') {
		$arr = db_fetch_assoc('SELECT DISTINCT feature_name AS qry_index,
			SUM(feature_max_licenses) AS qry_value
			FROM lic_services_feature_use
			GROUP BY feature_name');
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
