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
	include_once(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_sbres_stats', $_SERVER['argv']);
}

function ss_grid_sbres_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_sbres_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_sbres_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_sbres_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			print $arr_index[$i] . '!' . $arr[$i] . "\n";
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_sbres_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_sbres_stats_getvalue($clusterid, $index, $column) {
	$return_arr = array();

	if (preg_match('/(memAvg|memMax|memMin|swapMax|swapMin|swapAvg|cpuMin|cpuMax|cpuAvg)/', $column)) {
		switch ($column) {
		case 'memAvg':
			$group_func = 'AVG';
			$column     = 'mem';
			break;
		case 'memMax':
			$group_func = 'MAX';
			$column     = 'mem';
			break;
		case 'memMin':
			$group_func = 'MIN';
			$column     = 'mem';
			break;
		case 'swapMax':
			$group_func = 'MAX';
			$column     = 'swp';
			break;
		case 'swapMin':
			$group_func = 'MIN';
			$column     = 'swp';
			break;
		case 'swapAvg':
			$group_func = 'AVG';
			$column     = 'swp';
			break;
		case 'cpuMax':
			$group_func = 'MAX';
			$column     = 'ut';
			break;
		case 'cpuMin':
			$group_func = 'MIN';
			$column     = 'ut';
			break;
		case 'cpuAvg':
			$group_func = 'AVG';
			$column     = 'ut';
			break;
		}

		$value = db_fetch_cell_prepared("SELECT
			$group_func($column) AS value
			FROM grid_load AS gl
			INNER JOIN grid_hostresources AS ghr
			ON ghr.host = gl.host
			AND ghr.clusterid = gl.clusterid
			INNER JOIN grid_hosts AS gh
			ON ghr.host = gh.host
			AND ghr.clusterid = gh.clusterid
			WHERE ghr.resource_name = ?
			AND ghr.clusterid = ?
			AND gh.status IN ('Ok', 'Closed-Limits', 'Closed-Full', 'Closed-Busy')",
			array($index, $clusterid));

	} else {
		switch($column) {
		case 'total_slots':
			$value = db_fetch_cell_prepared("SELECT
				SUM(maxJobs) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostresources AS ghr
				ON ghr.host = ghi.host
				AND ghr.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghr.host = gh.host
				AND ghr.clusterid = gh.clusterid
				WHERE ghr.resource_name = ?
				AND ghr.clusterid = ?
				AND gh.status IN ('Ok', 'Closed-Limits', 'Closed-Full', 'Closed-Busy')",
				array($index, $clusterid));

			break;
		case 'total_jobs':
			$value = db_fetch_cell_prepared('SELECT
				SUM(numJobs) AS value
				FROM grid_hosts AS gl
				INNER JOIN grid_hostresources AS ghr
				ON ghr.host = gh.host
				AND ghr.clusterid = gh.clusterid
				WHERE (ghr.resource_name = ?
				AND ghr.clusterid = ?)',
				array($index, $clusterid));

			break;
		case 'capacity':
		case 'load':
			if ($column == 'capacity') $query = 'SUM(ghi.cpuFactor*maxCpus) AS tcapacity';
			if ($column == 'load')     $query = 'SUM(ghi.cpuFactor*maxCpus*ut) AS tload';

			$value = db_fetch_cell_prepared("SELECT
				$query
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_load AS gl
				ON ghi.host = gl.host
				AND ghi.clusterid = gl.clusterid
				INNER JOIN grid_hostresources AS ghr
				ON ghr.host = gl.host
				AND ghr.clusterid = gl.clusterid
				WHERE ghr.resource_name = ?
				AND ghr.clusterid = ?
				AND isServer='1'",
				array($index, $clusterid));

			break;
		}
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_sbres_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT DISTINCT resource_name
		FROM grid_hostresources
		WHERE clusterid = ?
		ORDER BY resource_name',
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['resource_name'];
	}

	return $return_arr;
}

function ss_grid_sbres_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'hosts') {
		$arr = db_fetch_assoc_prepared('SELECT resource_name AS qry_index,
			COUNT(host) AS qry_value FROM grid_hostresources
			WHERE clusterid = ?
			GROUP BY resource_name
			ORDER BY resource_name',
			array($clusterid));
	} elseif ($info_requested == 'resource_name') {
		$arr = db_fetch_assoc_prepared('SELECT DISTINCT resource_name AS qry_index,
			resource_name AS qry_value
			FROM grid_hostresources
			WHERE clusterid = ?
			GROUP BY resource_name
			ORDER BY resource_name',
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
