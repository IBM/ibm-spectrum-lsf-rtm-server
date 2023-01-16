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
	print call_user_func_array('ss_disku_extensions', $_SERVER['argv']);
}

function ss_disku_extensions($cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_disku_extensions_getnames();

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_disku_extensions_getnames();
		$arr = ss_disku_extensions_getinfo($arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_disku_extensions_getvalue($index, $arg);
	}
}

function ss_disku_extensions_getvalue($primaryKey, $column) {
	$return_arr = array();

	if ($column == 'size') {
		$column = 'SUM(size)';
	} elseif ($column == 'files') {
		$column = 'SUM(files)';
	} elseif ($column == 'dirs') {
		$column = 'SUM(directories)';
	}

	$arr = db_fetch_cell("SELECT
		$column
		FROM disku_extension_totals
		WHERE delme=0 AND extension='" . $primaryKey . "'");

	if ($arr == '') {
		$arr = 0;
	}

	return $arr;
}

function ss_disku_extensions_getnames() {
	$return_arr = array();

	$arr = db_fetch_assoc('SELECT r.extension AS primaryKey
		FROM disku_extension_registry AS r
		WHERE r.monitor="on"
		GROUP BY r.extension
		ORDER BY primaryKey');

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$item = str_replace('!', '.', $arr[$i]['primaryKey']);
		$return_arr[$i] = trim($item);
	}

	return $return_arr;
}

function ss_disku_extensions_getinfo($info_requested) {
	$return_arr = array();

	if ($info_requested == 'extension') {
		$arr = db_fetch_assoc('SELECT
			r.extension AS qry_index,
			r.extension AS qry_value
			FROM disku_extension_registry AS r
			WHERE r.monitor="on"
			GROUP BY r.extension
			ORDER BY qry_index');
	} elseif ($info_requested == 'applicationName') {
		$arr = db_fetch_assoc('SELECT
			r.extension AS qry_index,
			r.notes AS qry_value
			FROM disku_extension_registry AS r
			WHERE r.monitor="on"
			GROUP BY r.extension
			ORDER BY qry_index');
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$index = str_replace('!', '.', $arr[$i]['qry_index']);
		$value = str_replace('!', '.', $arr[$i]['qry_value']);
		$return_arr[$index] = trim(addslashes($value));
	}

	return $return_arr;
}

