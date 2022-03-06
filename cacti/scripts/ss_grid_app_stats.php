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
	print call_user_func_array('ss_grid_app_stats', $_SERVER['argv']);
}

function ss_grid_app_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_app_stats_getnames($clusterid);
		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_app_stats_getnames($clusterid);
		$arr = ss_grid_app_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_app_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_app_stats_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();
	$max_date = ss_grid_app_get_maxdate($clusterid);
	$poller_interval = read_config_option('poller_interval');

	if (empty($poller_interval)) { $poller_interval = 300; }

	if ((time() - 2*$poller_interval) > $max_date) {
		return 0;
	}

	switch ($column) {
		case 'numPEND':
		case 'numRUN':
		case 'numJOBS':
		case 'total_cpu':
			$value = db_fetch_cell("SELECT
			Sum($column) AS value
			FROM grid_applications
			WHERE (appName='$index'
			AND clusterid='$clusterid')");

			break;
		case 'max_mem':
		case 'max_swap':
			$value = db_fetch_cell("SELECT
			MAX($column) AS value
			FROM grid_applications
			WHERE (appName='$index'
			AND clusterid='$clusterid')");

			break;
		case 'efficiency':
			$value = db_fetch_cell("SELECT
			AVG($column) AS value
			FROM grid_applications
			WHERE (appName='$index'
			AND clusterid='$clusterid')");

			break;
		case 'avg_swap':
		case 'avg_mem':
			$value = db_fetch_cell("SELECT
			Sum($column * numRUN) / Sum(numRUN) AS value
			FROM grid_applications
			WHERE (appName='$index'
			AND clusterid='$clusterid')");

			break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_app_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT DISTINCT appName
		FROM grid_applications
		WHERE clusterid = ?
		ORDER BY appName',
		array($clusterid));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['appName'];
	}

	return $return_arr;
}

function ss_grid_app_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'appName') {
		$arr = db_fetch_assoc_prepared('SELECT appName AS qry_index, appName AS qry_value
			FROM grid_applications
			WHERE clusterid = ?
			ORDER BY appName',
			array($clusterid));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

function ss_grid_app_get_maxdate($clusterid) {
	global $config;

	$job_stats_minor = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Minor');
	$job_stats_major = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Major');

	if (strlen($job_stats_minor)) {
		$stats = explode(' ', $job_stats_minor);
		$date_minor = strtotime(str_replace('_', ' ', str_replace('EndDate:', '', $stats[0])));
	}

	if (strlen($job_stats_major)) {
		$stats = explode(' ', $job_stats_major);
		$date_major = strtotime(str_replace('_', ' ', str_replace('EndDate:', '', $stats[0])));
	}

	if (!empty($date_major) && !empty($date_minor) ) {
		if ($date_major > $date_minor) {
			$last_string = $job_stats_major;
		} else {
			$last_string = $job_stats_minor;
		}
	} elseif (!empty($date_major) && empty($date_minor) ) {
		$last_string = $job_stats_major;
	} elseif (empty($date_major) && !empty($date_minor) ) {
		$last_string = $job_stats_minor;
	} else {
		return (0);
	}

	$last_array = explode(' ', $last_string);
	$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));

	return strtotime($date_string);
}
