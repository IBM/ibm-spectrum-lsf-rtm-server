<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

include_once(dirname(__FILE__) . '/../plugins/grid/lib/grid_functions.php');

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_jgroup_stats', $_SERVER['argv']);
}

function ss_grid_jgroup_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_jgroup_stats_getnames($clusterid, $arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_jgroup_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_jgroup_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_jgroup_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_jgroup_stats_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();
	$max_date = ss_grid_jgroup_get_maxdate($clusterid);

	$poller_interval = read_config_option('poller_interval');

	if (empty($poller_interval)) { $poller_interval = 300; }

	if ((time() - 2 * $poller_interval) > $max_date) {
		return 0;
	}

	switch ($column) {
		case 'numPEND':
		case 'numRUN':
		case 'numJOBS':
		case 'total_cpu':
			$value = db_fetch_cell_prepared("SELECT SUM($column) AS value
				FROM grid_groups
				WHERE (groupName = ? OR groupName LIKE '$index/%') AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'max_mem':
		case 'max_swap':
			$value = db_fetch_cell_prepared("SELECT MAX($column) AS value
				FROM grid_groups
				WHERE (groupName = ? OR groupName LIKE '$index/%') AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'efficiency':
			$value = db_fetch_cell_prepared("SELECT AVG($column) AS value
				FROM grid_groups
				WHERE (groupName = ? OR groupName LIKE '$index/%') AND clusterid= ?",
				array($index, $clusterid));

			break;
		case 'avg_swap':
		case 'avg_mem':
			$value = db_fetch_cell_prepared("SELECT SUM($column * numRUN) / SUM(numRUN) AS value
			FROM grid_groups
			WHERE (groupName = ? OR groupName LIKE '$index/%') AND clusterid = ?",
			array($index, $clusterid));

			break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_jgroup_stats_getnames($clusterid) {
	$return_arr = array();

	$group_names = get_group_aggregation_string();

	$groupnames = db_fetch_assoc_prepared("SELECT DISTINCT $group_names AS groupName
		FROM grid_groups
		WHERE clusterid = ?
		ORDER BY groupName",
		array($clusterid));

	foreach($groupnames as $gname) {
		if (strlen($gname['groupName']) > 0) {
			$sub_gnames = explode('/', $gname['groupName']);
			$added_gname = '/';
			foreach($sub_gnames as $sub_gname) {
				if (strlen($sub_gname) > 0) {
					$added_gname .= $sub_gname;
					if (!in_array($added_gname, $return_arr, true)) {
						$return_arr[] = $added_gname;
					}
					$added_gname .= '/';
				}
			}
		}
	}
	sort($return_arr);
	return $return_arr;
}

function ss_grid_jgroup_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	$group_names = get_group_aggregation_string();

	if ($info_requested == 'groupName') {
		$groupnames = db_fetch_assoc_prepared("SELECT DISTINCT $group_names AS groupName
			FROM grid_groups
			WHERE clusterid = ?
			ORDER BY groupName",
			array($clusterid));

		$i = 0;
		foreach($groupnames as $gname) {
			if (strlen($gname['groupName']) > 0) {
				$sub_gnames = explode('/', $gname['groupName']);
				$added_gname = '/';
				foreach($sub_gnames as $sub_gname) {
					if (strlen($sub_gname) > 0) {
						$added_gname .= $sub_gname;
						if (!in_array($added_gname, $return_arr, true)) {
							$arr[$i]['qry_index'] = $added_gname;
							$arr[$i]['qry_value'] = $added_gname;
							$i++;
						}
						$added_gname .= '/';
					}
				}
			}
		}
	}

	for ($i=0;(isset($arr) && $i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

function ss_grid_jgroup_get_maxdate($clusterid) {
	global $config;

	$job_stats_minor = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Minor');
	$job_stats_major = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Major');

	$date_minor = 0;
	if (strlen($job_stats_minor)) {
		$stats = explode(' ', $job_stats_minor);
		$date_minor = strtotime(str_replace('_', ' ', str_replace('EndDate:', '', $stats[0])));
	}

	if (strlen($job_stats_major)) {
		$stats = explode(' ', $job_stats_major);
		$date_major = strtotime(str_replace('_', ' ', str_replace('EndDate:', '', $stats[0])));
	}

	if ($date_major > $date_minor) {
		$last_string = $job_stats_major;
	} else {
		$last_string = $job_stats_minor;
	}

	$last_array = explode(' ', $last_string);
	$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));

	return strtotime($date_string);
}
