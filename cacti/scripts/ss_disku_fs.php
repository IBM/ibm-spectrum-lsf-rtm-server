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
	print call_user_func_array('ss_disku_fs', $_SERVER['argv']);
}

function ss_disku_fs($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	global $poller_ids;

	if (!isset($poller_ids[$host_id])) {
		$table_fields = array_rekey(db_fetch_assoc('DESCRIBE disku_pollers'), 'Field', 'Field');
		if (key_exists('cacti_host', $table_fields)) {
			$poller_id = db_fetch_cell_prepared("SELECT id FROM disku_pollers WHERE cacti_host = ?", array($host_id));
			if (!empty($poller_id)) {
				$poller_ids[$host_id] = $poller_id;
			}
		}
	}
	if (!isset($poller_ids[$host_id])) {
		return 0;
	}

	if ($cmd == 'index') {
		$return_arr = ss_disku_fs_getnames($poller_ids[$host_id]);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_disku_fs_getnames($poller_ids[$host_id]);
		$arr = ss_disku_fs_getinfo($poller_ids[$host_id], $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_disku_fs_getvalue($poller_ids[$host_id], $index, $arg);
	}
}

function ss_disku_fs_getvalue($poller_id, $primaryKey, $column) {
	$return_arr = array();

	if ($column == 'percent') {
		$column = 'percentUsed';
	}

	$arr = db_fetch_cell("SELECT
		$column
		FROM disku_pollers_filesystems
		WHERE poller_id = $poller_id
		AND CONCAT_WS('',poller_id,'|',mountPoint,'') = '" . $primaryKey . "'");

	if ($arr == '') {
		$arr = 0;
	}

	return $arr;
}

function ss_disku_fs_getnames($poller_id) {
	$return_arr = array();

	$arr = db_fetch_assoc("SELECT DISTINCT CONCAT_WS('',poller_id,'|',mountPoint,'') AS primaryKey
		FROM disku_pollers_filesystems
		WHERE poller_id=$poller_id
		ORDER BY primaryKey");

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['primaryKey'];
	}

	return $return_arr;
}

function ss_disku_fs_getinfo($poller_id, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'location') {
		$arr = db_fetch_assoc("SELECT
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_index,
			location AS qry_value
			FROM disku_pollers_filesystems AS dpf
			INNER JOIN disku_pollers AS dp
			ON dp.id=dpf.poller_id
			WHERE dp.id=$poller_id
			ORDER BY qry_index");
	} elseif ($info_requested == 'hostname') {
		$arr = db_fetch_assoc("SELECT
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_index,
			hostname AS qry_value
			FROM disku_pollers_filesystems AS dpf
			INNER JOIN disku_pollers AS dp
			ON dp.id=dpf.poller_id
			WHERE dp.id=$poller_id
			ORDER BY qry_index");
	} elseif ($info_requested == 'device') {
		$arr = db_fetch_assoc("SELECT DISTINCT
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_index,
			device AS qry_value
			FROM disku_pollers_filesystems
			WHERE poller_id=$poller_id
			ORDER BY qry_index");
	} elseif ($info_requested == 'mount') {
		$arr = db_fetch_assoc("SELECT DISTINCT
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_index,
			mountPoint AS qry_value
			FROM disku_pollers_filesystems
			WHERE poller_id=$poller_id
			ORDER BY qry_index");
	} elseif ($info_requested == 'primaryKey') {
		$arr = db_fetch_assoc("SELECT DISTINCT
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_index,
			CONCAT_WS('',poller_id,'|',mountPoint,'') AS qry_value
			FROM disku_pollers_filesystems
			WHERE poller_id=$poller_id
			ORDER BY qry_index");
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = trim(addslashes($arr[$i]['qry_value']));
	}

	return $return_arr;
}
