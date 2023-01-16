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
	print call_user_func_array('ss_grid_lproj_sstats', $_SERVER['argv']);
}

function ss_grid_lproj_sstats($cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lproj_sstats_getnames($arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lproj_sstats_getnames($arg1);
		$arr = ss_grid_lproj_sstats_getinfo($arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_lproj_sstats_getvalue($index, $arg);
	}
}

function ss_grid_lproj_sstats_getvalue($index, $column) {
	global $config;

	switch ($column) {
	case 'numPEND':
	case 'numRUN':
	case 'numJOBS':
	case 'total_cpu':
		$value = db_fetch_cell_prepared("SELECT
			SUM($column) AS value
			FROM grid_license_projects
			WHERE licenseProject = ?",
			array($index));

		break;
	case 'max_mem':
	case 'max_swap':
		$value = db_fetch_cell_prepared("SELECT
			MAX($column) AS value
			FROM grid_license_projects
			WHERE licenseProject = ?",
			array($index));

		break;
	case 'efficiency':
		$value = db_fetch_cell_prepared("SELECT
			AVG($column) AS value
			FROM grid_license_projects
			WHERE licenseProject = ?",
			array($index));

		break;
	case 'avg_swap':
	case 'avg_mem':
		$value = db_fetch_cell_prepared("SELECT
			SUM($column * numRUN) / SUM(numRUN) AS value
			FROM grid_license_projects
			WHERE licenseProject = ?",
			array($index));

		break;
	}

	if (!empty($value)) {
		return $value;
	} else {
		return '0';
	}
}

function ss_grid_lproj_sstats_getnames() {
	$return_arr = array();

	$arr = db_fetch_assoc('SELECT DISTINCT licenseProject AS licenseProject
		FROM grid_license_projects
		ORDER BY licenseProject');

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['licenseProject'];
	}

	return $return_arr;
}

function ss_grid_lproj_sstats_getinfo($info_requested) {
	$return_arr = array();

	if ($info_requested == 'licenseProject') {
		$arr = db_fetch_assoc('SELECT licenseProject AS qry_index,
			licenseProject AS qry_value
			FROM grid_license_projects
			ORDER BY licenseProject');
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
                $return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

