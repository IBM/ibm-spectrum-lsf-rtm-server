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
	print call_user_func_array('ss_grid_proj_stats', $_SERVER['argv']);
}

function ss_grid_proj_stats($clusterid = 0, $level = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_proj_stats_getnames($clusterid, $level, $arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_proj_stats_getnames($clusterid, $level, $arg1);
		$arr = ss_grid_proj_stats_getinfo($clusterid, $level, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_proj_stats_getvalue($clusterid, $level, $index, $arg);
	}
}

function ss_grid_proj_stats_getvalue($clusterid, $level, $index, $column) {
	global $config;

	$return_arr      = array();
	$max_date        = ss_grid_proj_get_maxdate($clusterid);
	$delim           = read_config_option("grid_job_stats_project_delimiter");
	$poller_interval = read_config_option("poller_interval");

	if (empty($poller_interval)) { $poller_interval = 300; }

	if ((time() - 2*$poller_interval) > $max_date) {
		return 0;
	}

	if (read_config_option("grid_project_group_aggregation") == 'on') {
		$level = substr_count($index, $delim)+1;
	}

	$project_names = get_project_aggregation_string($level);

	if ($clusterid > 0) {
		$sql_where = "AND clusterid='$clusterid'";
	} else {
		$sql_where = "";
	}

	switch ($column) {
	case "numPEND":
	case "numRUN":
	case "numJOBS":
	case "total_cpu":
	case "pendJOBS":
	case "runJOBS":
	case "totalJOBS":
		$value = db_fetch_cell("SELECT
			Sum($column) AS value
			FROM grid_projects
			WHERE ($project_names='$index' $sql_where)");

		break;
	case "max_mem":
	case "max_swap":
		$value = db_fetch_cell("SELECT
			MAX($column) AS value
			FROM grid_projects
			WHERE ($project_names='$index' $sql_where)");

		break;
	case "efficiency":
		$value = db_fetch_cell("SELECT
			AVG($column) AS value
			FROM grid_projects
			WHERE ($project_names='$index' $sql_where)");

		break;
	case "avg_swap":
	case "avg_mem":
		$value = db_fetch_cell("SELECT
			Sum($column * numRUN) / Sum(numRUN) AS value
			FROM grid_projects
			WHERE ($project_names='$index' $sql_where)");

		break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return "0";
	}
}

function ss_grid_proj_stats_getnames($clusterid, $level) {
	$arr = array();
	$return_arr = array();

	$sql_where = "WHERE projectName<>'' ";
	if ($clusterid > 0) {
		$sql_where .= "AND clusterid='$clusterid'";
	}

	if (read_config_option("grid_project_group_aggregation") == 'on') {
		$level = read_config_option("grid_job_stats_project_level_number");
		$project_levels = get_project_aggregation_string($level);
		$delim = read_config_option("grid_job_stats_project_delimiter");
		$projects = db_fetch_assoc("SELECT DISTINCT $project_levels as projectName
			FROM grid_projects
			$sql_where
			ORDER BY projectName");

		$i=0;
		if (cacti_sizeof($projects)) {
		foreach($projects as $project) {
			if (strlen($project['projectName']) > 0) {
				$sub_projects = explode($delim, $project['projectName']);
				$added_project = "";
				foreach ($sub_projects as $sub_project) {
					if (strlen($sub_project) > 0) {
						$added_project .= $sub_project;

						if (!in_array($added_project, $return_arr, true)) {
							$return_arr[] = $added_project;
							$arr[$i]["projects"] = $added_project;
							$i++;
						}

						$added_project .= $delim;
					}
				}
			}
		}
		}
		sort($arr);
	} else {
		$arr = db_fetch_assoc("SELECT DISTINCT projectName AS projects
			FROM grid_projects
			$sql_where
			ORDER BY projectName");
	}

	$return_arr = array();
	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["projects"];
	}

	return $return_arr;
}

function ss_grid_proj_stats_getinfo($clusterid, $level, $info_requested) {
	$return_arr = array();

	if ($clusterid > 0) {
		$sql_where = "WHERE clusterid='$clusterid'";
	} else {
		$sql_where = '';
	}

	if ($info_requested == 'projectLevel1') {
		if (read_config_option('grid_project_group_aggregation') == 'on') {
			$level = read_config_option('grid_job_stats_project_level_number');
			$project_levels = get_project_aggregation_string($level);
			$delim = read_config_option('grid_job_stats_project_delimiter');

			$projects = db_fetch_assoc("SELECT DISTINCT $project_levels as projectName
				FROM grid_projects
				$sql_where
				ORDER BY projectName");

			$i=0;
			if (cacti_sizeof($projects)) {
				foreach($projects as $project) {
					if (strlen($project['projectName']) > 0) {
						$sub_projects = explode($delim, $project['projectName']);
						$added_project = '';

						foreach ($sub_projects as $sub_project) {
							if (strlen($sub_project) > 0) {
								$added_project .= $sub_project;

								if (!in_array($added_project, $return_arr, true)) {
									$arr[$i]['qry_index'] = $added_project;
									$arr[$i]['qry_value'] = $added_project;
									$i++;
								}

								$added_project .= $delim;
							}
						}
					}
				}
			}
		} else {
			$project_levels = get_project_aggregation_string(1);
			$project_names  = get_project_aggregation_string($level);

			$arr = db_fetch_assoc("SELECT $project_names AS qry_index,
				$project_levels AS qry_value
				FROM grid_projects
				$sql_where
				GROUP BY $project_names
				ORDER BY $project_levels");
		}
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
   		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

function ss_grid_proj_get_maxdate($clusterid) {
	global $config;

	if ($clusterid > 0) {
		$last_string = read_config_option('grid_update_time_lsload_' . $clusterid);
		$last_array = explode(' ', $last_string);
		$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));

		return strtotime($date_string);
	} else {
		$maxdate = 0;
		$dates   = db_fetch_assoc("SELECT value FROM settings WHERE name LIKE 'grid_update_time_lsload_%'");

		if (cacti_sizeof($dates)) {
			foreach($dates as $date) {
				$last_string = $date['value'];
				$last_array  = explode(' ', $last_string);
				$mydate      = strtotime(str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0])));

				if ($mydate > $maxdate) {
					$maxdate = $mydate;
				}
			}
		}

		return $maxdate;
	}
}
