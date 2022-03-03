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
	print call_user_func_array('ss_grid_pool_stats', $_SERVER['argv']);
}

function ss_grid_pool_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_pool_stats_getnames($clusterid, $arg1);
		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == 'query') {
		$arr_index = ss_grid_pool_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_pool_stats_getinfo($clusterid, $arg1, $arg2);
		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_pool_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_pool_stats_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();

	switch ($column) {
		case 'memSelected':
			$totalMemory = db_fetch_cell("SELECT SUM(maxMem*1024*1024)
				FROM grid_guarantee_pool_hosts AS gph
				INNER JOIN grid_hostinfo AS ghi
				ON ghi.clusterid=gph.clusterid
				AND ghi.host=gph.host
				INNER JOIN grid_hosts AS gh
				ON gh.host=ghi.host
				AND gh.clusterid=ghi.clusterid
				AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
				WHERE gph.name = '$index'
				AND gph.clusterid = $clusterid");

			$memSelected = db_fetch_cell("SELECT memRequested
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			$value = $memSelected / $totalMemory * 100;

			break;
		case 'memReserved':
			$totalMemory = db_fetch_cell("SELECT SUM(maxMem*1024*1024)
				FROM grid_guarantee_pool_hosts AS gph
				INNER JOIN grid_hostinfo AS ghi
				ON ghi.clusterid=gph.clusterid
				AND ghi.host=gph.host
				INNER JOIN grid_hosts AS gh
				ON gh.host=ghi.host
				AND gh.clusterid=ghi.clusterid
				AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
				WHERE gph.name = '$index'
				AND gph.clusterid = $clusterid");

			$memReserved = db_fetch_cell("SELECT memReserved
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			$value = $memReserved / $totalMemory * 100;

			break;
		case 'memUsed':
			$totalMemory = db_fetch_cell("SELECT SUM(maxMem*1024*1024)
				FROM grid_guarantee_pool_hosts AS gph
				INNER JOIN grid_hostinfo AS ghi
				ON ghi.clusterid = gph.clusterid
				AND ghi.host = gph.host
				INNER JOIN grid_hosts AS gh
				ON gh.host = ghi.host
				AND gh.clusterid = ghi.clusterid
				AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
				WHERE gph.name = '$index'
				AND gph.clusterid = $clusterid");

			$memUsed = db_fetch_cell("SELECT memUsed
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			$value = $memUsed / $totalMemory * 100;

			break;
		case 'memSlotUtil':
			$value = db_fetch_cell("SELECT memSlotUtil
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			break;
		case 'slotUtil':
			$value = db_fetch_cell("SELECT slotUtil
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			break;
		case 'cpuUtil':
			$value = db_fetch_cell("SELECT cpuUtil
				FROM grid_guarantee_pool
				WHERE name = '$index'
				AND clusterid = $clusterid");

			break;
	}

	if (!empty($value)) {
		return $value;
	}else{
		return '0';
	}
}

function ss_grid_pool_stats_getnames($clusterid) {
	$return_arr = array();
	$arr = db_fetch_assoc("SELECT DISTINCT name
		FROM grid_guarantee_pool_hosts
		WHERE clusterid='" . $clusterid . "'
		ORDER BY name");

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['name'];
	}

	return $return_arr;
}

function ss_grid_pool_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();
	if ($info_requested == 'hosts') {
		$arr = db_fetch_assoc("SELECT name AS qry_index, COUNT(host) AS qry_value
			FROM grid_guarantee_pool_hosts
			WHERE clusterid ='" . $clusterid ."'
			GROUP BY name
			ORDER BY name");
	}elseif ($info_requested == 'name') {
		$arr = db_fetch_assoc("SELECT DISTINCT name AS qry_index, name AS qry_value
			FROM grid_guarantee_pool_hosts
			WHERE clusterid ='" . $clusterid ."'
			GROUP BY name
			ORDER BY name");
    }elseif ($info_requested == 'slas') {
		$arr = db_fetch_assoc("SELECT DISTINCT name AS qry_index, GROUP_CONCAT(DISTINCT consumer SEPARATOR ', ') AS qry_value
			FROM grid_guarantee_pool_distribution
			WHERE clusterid ='" . $clusterid ."'
			GROUP BY name
			ORDER BY name, consumer");
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
