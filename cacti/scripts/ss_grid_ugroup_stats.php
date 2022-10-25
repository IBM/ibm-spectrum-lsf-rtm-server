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
	print call_user_func_array('ss_grid_ugroup_stats', $_SERVER['argv']);
}

function ss_grid_ugroup_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_ugroup_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_ugroup_stats_getnames($clusterid, $arg1);
		$arr       = ss_grid_ugroup_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg   = $arg1;
		$index = $arg2;

		return ss_grid_ugroup_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_ugroup_stats_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();
	$value = 0;

	$max_date = read_config_option('grid_prev_nonjob_start_' . $clusterid);
	$method = read_config_option('grid_usergroup_method');

	if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
		$delim=read_config_option('grid_job_stats_ugroup_delimiter');
		$groupwhere1 = "(userGroup LIKE '$index$delim%' OR userGroup='$index')";
		$groupwhere2 = "type='G' AND (user_or_group LIKE '$index$delim%' OR user_or_group='$index')";
	} else {
		$groupwhere1 = "userGroup='$index'";
		$groupwhere2 = "type='G' AND user_or_group='$index'";
	}

	switch ($column) {
		case 'numRUN':
		case 'numPEND':
		case 'numJOBS':
		case 'numJobs':
			if ($method == 'jobmap') {
				$value = db_fetch_cell_prepared("SELECT
					SUM($column) AS value
					FROM grid_user_group_stats
					WHERE $groupwhere1
					AND clusterid = ?",
					array($clusterid));
			} else {
				$value = db_fetch_cell_prepared("SELECT
					SUM($column) AS value
					FROM grid_users_or_groups
					WHERE $groupwhere2
					AND clusterid = ?",
					array($clusterid));
			}
			break;
		case 'max_mem':
		case 'max_swap':
			$value = db_fetch_cell_prepared("SELECT
				MAX($column) AS value
				FROM grid_user_group_stats
				WHERE $groupwhere1
				AND clusterid = ?",
				array($clusterid));

			break;
		case 'efficiency':
			$value = db_fetch_cell_prepared("SELECT
				AVG($column) AS value
				FROM grid_user_group_stats
				WHERE $groupwhere1
				AND clusterid = ?",
				array($clusterid));

			break;
		case 'avg_swap':
		case 'avg_mem':
			$value = db_fetch_cell_prepared("SELECT
				Sum($column * numRUN) / Sum(numRUN) AS value
				FROM grid_user_group_stats
				WHERE $groupwhere1
				AND clusterid = ?",
				array($clusterid));

			break;
		case 'total_cpu':
			$value = db_fetch_cell_prepared("SELECT
				SUM($column) AS value
				FROM grid_user_group_stats
				WHERE $groupwhere1
				AND clusterid = ?",
				array($clusterid));
			break;
		case 'maxJobs':
		case 'numSSUSP':
		case 'numUSUSP':
		case 'maxPendJobs':
		case 'numStartJobs':
		case 'numRESERVE':
			$value = db_fetch_cell_prepared("SELECT
				SUM($column) AS value
				FROM grid_users_or_groups
				WHERE $groupwhere2
				AND clusterid = ?",
				array($clusterid));
				break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_ugroup_stats_getnames($clusterid) {
	global $config;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$return_arr = array();

	if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
		$group_names = get_ugroup_aggregation_string();
		$arr = $return_arr = array();
		$delim = read_config_option('grid_job_stats_ugroup_delimiter');

		$groupNames = db_fetch_assoc_prepared("SELECT DISTINCT $group_names as groupName
			FROM grid_user_group_stats
			WHERE clusterid = ?
			ORDER BY groupName",
			array($clusterid));

		$i=0;
		if (cacti_sizeof($groupNames)) {
		foreach($groupNames as $groupname) {
			if (strlen($groupname['groupName']) > 0) {
				$sub_groupnames = explode($delim, $groupname['groupName']);
				$added_groupname = '';
				foreach ($sub_groupnames as $sub_groupname) {
					if (strlen($sub_groupname) > 0) {
						$added_groupname .= $sub_groupname;
						if (!in_array($added_groupname, $return_arr, true)) {
							$return_arr[] = $added_groupname;
							$arr[$i]['groupName'] = $added_groupname;
							$i++;
						}
						$added_groupname .= $delim;
					}
				}
			}
		}
		}
		sort($arr);
	} else {
		$arr = db_fetch_assoc_prepared('SELECT DISTINCT userGroup AS groupName
			FROM grid_user_group_stats
			WHERE clusterid = ?
			ORDER BY groupName',
			array($clusterid));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['groupName'];
	}

	return $return_arr;
}

function ss_grid_ugroup_stats_getinfo($clusterid, $info_requested) {
	global $config;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$arr = $return_arr = array();

	if ($info_requested == 'groupMembers') {
		$arr = db_fetch_assoc_prepared('SELECT groupname AS qry_index, COUNT(groupname) AS qry_value
			FROM grid_user_group_members
			WHERE clusterid = ?
			GROUP BY groupname
			ORDER BY groupname',
			array($clusterid));
	} elseif ($info_requested == 'groupName') {
		if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
			$group_names = get_ugroup_aggregation_string();
			$delim = read_config_option('grid_job_stats_ugroup_delimiter');
			$groupNames = db_fetch_assoc_prepared("SELECT DISTINCT $group_names as groupName
				FROM grid_user_group_stats
				WHERE clusterid = ?
				ORDER BY groupName",
				array($clusterid));

			$i=0;
			if (cacti_sizeof($groupNames)) {
				foreach($groupNames as $groupname) {
					if (strlen($groupname['groupName']) > 0) {
						$sub_groupnames = explode($delim, $groupname['groupName']);
						$added_groupname = '';

						foreach ($sub_groupnames as $sub_groupname) {
							if (strlen($sub_groupname) > 0) {
								$added_groupname .= $sub_groupname;

								if (!in_array($added_groupname, $return_arr, true)) {
									$arr[$i]['qry_index'] = $added_groupname;
									$arr[$i]['qry_value'] = $added_groupname;
									$i++;
								}

								$added_groupname .= $delim;
							}
						}
					}
				}
			}

			for ($i=0;(isset($arr) && $i<sizeof($arr));$i++) {
				$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
			}
		} else {
			$arr = db_fetch_assoc_prepared('SELECT DISTINCT userGroup AS qry_index, userGroup AS qry_value
				FROM grid_user_group_stats
				WHERE clusterid = ?
				ORDER BY userGroup',
				array($clusterid));
		}
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
