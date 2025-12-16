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

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_projects', $_SERVER['argv']);
}

function ss_grid_projects($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_projects_getnames($clusterid, $arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_projects_getnames($clusterid, $arg1);
		$arr = ss_grid_projects_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_projects_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_projects_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();
	$max_date = ss_grid_projects_get_maxdate($clusterid);
	$poller_interval = read_config_option('poller_interval');

	if (empty($poller_interval)) { $poller_interval = 300; }

	if ((time() - 2*$poller_interval) > $max_date) {
		return 0;
	}

	if (read_config_option('grid_project_group_aggregation') == 'on') {
		$delim = read_config_option('grid_job_stats_project_delimiter');
		$projwhere = "(projectName LIKE '$index$delim%' OR projectName='$index')";
	} else {
		$projwhere = "projectName='$index'";
	}

	switch ($column) {
	case 'numPEND':
	case 'numRUN':
	case 'numJOBS':
	case 'total_cpu':
	case 'pendJOBS':
	case 'runJOBS':
	case 'totalJOBS':
		$value = db_fetch_cell("SELECT
			Sum($column) AS value
			FROM grid_projects
			WHERE $projwhere
			AND clusterid='$clusterid'");

		break;
	case 'max_mem':
	case 'max_swap':
		$value = db_fetch_cell("SELECT
			MAX($column) AS value
			FROM grid_projects
			WHERE $projwhere
			AND clusterid='$clusterid'");

		break;
	case 'efficiency':
		$value = db_fetch_cell("SELECT
			AVG($column) AS value
			FROM grid_projects
			WHERE $projwhere
			AND clusterid='$clusterid'");

		break;
	case 'avg_swap':
	case 'avg_mem':
		$value = db_fetch_cell("SELECT
			Sum($column * numRUN) / Sum(numRUN) AS value
			FROM grid_projects
			WHERE $projwhere
			AND clusterid='$clusterid'");

		break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_projects_getnames($clusterid) {
	$return_arr = array();

	if (read_config_option('grid_project_group_aggregation') == 'on') {
		$project_names = get_project_aggregation_string();

		$arr = $return_arr = array();
		$delim = read_config_option('grid_job_stats_project_delimiter');

		$projectNames = db_fetch_assoc("SELECT DISTINCT $project_names AS projectName
			FROM grid_projects
			WHERE clusterid='$clusterid'
			ORDER BY projectName");

		$i = 0;
		if (cacti_sizeof($projectNames)) {
			foreach($projectNames as $projname) {
				if (strlen($projname['projectName']) > 0) {
					$sub_projnames = explode($delim, $projname['projectName']);
					$added_projname = '';

					foreach ($sub_projnames as $sub_projname) {
						if (strlen($sub_projname) > 0) {
							$added_projname .= $sub_projname;

							if (!in_array($added_projname, $return_arr, true)) {
								$return_arr[] = $added_projname;
								$arr[$i]['projectName'] = $added_projname;
								$i++;
							}

							$added_projname .= $delim;
						}
					}
				}
			}
		}

		sort($arr);
	} else {
		$arr = db_fetch_assoc_prepared('SELECT DISTINCT projectName
			FROM grid_projects
			WHERE clusterid = ?
			ORDER BY projectName',
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["projectName"];
	}

	return $return_arr;
}

function ss_grid_projects_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'projectName') {
		if (read_config_option('grid_project_group_aggregation') == 'on') {
			$project_names = get_project_aggregation_string();
			$delim = read_config_option('grid_job_stats_project_delimiter');

			$projectNames = db_fetch_assoc("SELECT DISTINCT $project_names as projectName
				FROM grid_projects
				WHERE clusterid='$clusterid'
				ORDER BY projectName");

			$i=0;
			if (cacti_sizeof($projectNames)) {
				foreach($projectNames as $projname) {
					if (strlen($projname['projectName']) > 0) {
						$sub_projnames = explode($delim, $projname['projectName']);
						$added_projname = '';

						foreach ($sub_projnames as $sub_projname) {
							if (strlen($sub_projname) > 0) {
								$added_projname .= $sub_projname;

								if (!in_array($added_projname, $return_arr, true)) {
									$arr[$i]['qry_index'] = $added_projname;
									$arr[$i]['qry_value'] = $added_projname;
									$i++;
								}

								$added_projname .= $delim;
							}
						}
					}
				}
			}

			for ($i=0;(isset($arr) && $i<cacti_sizeof($arr));$i++) {
				$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
			}
		} else {
			$arr = db_fetch_assoc_prepared('SELECT projectName qry_index,
				projectName AS qry_value
				FROM grid_projects
				WHERE clusterid = ?
				ORDER BY projectName',
				array($clusterid));

			for ($i=0;($i<cacti_sizeof($arr));$i++) {
				$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
			}
		}
	}

	return $return_arr;
}

function ss_grid_projects_get_maxdate($clusterid) {
	global $config;

	$last_string = read_config_option('grid_update_time_lsload_' . $clusterid);
	$last_array = explode(' ', $last_string);
	$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));

	return strtotime($date_string);
}
