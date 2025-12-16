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

	print call_user_func_array('ss_grid_lssched_bp', $_SERVER['argv']);
}

function ss_grid_lssched_bp($host_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_lssched_bp_getnames($host_id);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_lssched_bp_getnames($host_id);
		$arr = ss_grid_lssched_bp_getinfo($host_id, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			} else {
				print '0';
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_lssched_bp_getvalue($host_id, $index, $arg);
	}
}

function ss_grid_lssched_bp_getvalue($host_id, $index, $column) {
	$return_arr = array();

	$index_arr = explode('|', $index);
	for($i=0;$i<4;$i++) {
		if (!isset($index_arr[$i]))
			$index_arr[$i]='';
	}

	$table  = 'grid_blstat_projects';
	switch ($column) {
		case "share":
			$column = "share";
			$query  = 1;
			break;
		case "own":
			$column = "own";
			$query  = 1;
			break;
		case "inUse":
			$column = "inUse";
			$query  = 1;
			break;
		case "reserve":
			$column = "reserve";
			$query  = 1;
			break;
		case "free":
			$column = "free";
			$query  = 1;
			break;
		case "demand":
			$column = "demand";
			$query  = 1;
			break;
		case "proj_use":
			$column = "inUse+reserve";
			$query  = 1;
			break;
		case "proj_duse":
			$column = "inUse+reserve+demand";
			$query  = 1;
			break;
		case "proj_quota":
			$query  = 3;
			break;
		case "sum_total":
			$column = "total_inuse+total_reserve+total_free+total_others";
			$query  = 2;
			break;
		case "sum_used":
			$column = "total_inuse+total_reserve+total_others";
			$query  = 2;
			break;
		case "sum_queued":
			$query  = 4;
			break;
	}

	if ($query == 1) {
		$value = db_fetch_cell("SELECT $column AS mvalue
			FROM $table
			WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "' AND
				service_domain='" . $index_arr[2] . "' AND project='" . $index_arr[3] . "'");
	} else {
		$parts = explode("|", $index);
		if ($query == 2) {
			$value = db_fetch_cell("SELECT $column AS mvalue
				FROM grid_blstat
				WHERE lsid='" . $parts[0] . "'
				AND feature='" . $parts[1] . "'
				AND service_domain='" . $parts[2] . "'");
		} else {
			$lic_ids = array_rekey(db_fetch_assoc("SELECT lic_id
				FROM grid_blstat_service_domains
				WHERE lsid='" . $parts[0] . "'
				AND service_domain='" . $parts[2] . "'"), "lic_id", "lic_id");
			$lic_ids = implode(",", $lic_ids);

			if ($query == 3) {
				$share_own = db_fetch_row("SELECT share, own
					FROM $table
					WHERE lsid='" . $index_arr[0] . "' AND feature='" . $index_arr[1] . "' AND
						service_domain='" . $index_arr[2] . "' AND project='" . $index_arr[3] . "'");

				if (!empty($lic_ids) && cacti_sizeof($share_own)) {
					if ($share_own['own'] == 0) {
						$feat = explode("@",$parts[1]);
						$feature = db_fetch_cell("SELECT lic_feature
							FROM grid_blstat_feature_map
							WHERE lsid='" . $parts[0] . "'
							AND bld_feature='" . $feat[0] . "'");

						$max = db_fetch_cell("SELECT SUM(feature_max_licenses)
							FROM lic_services_feature_use
							WHERE service_id IN($lic_ids)
							AND feature_name='$feature'");

						if (empty($max)) {
							$value = 0;
						} else {
							$value = ($share_own['share'] * $max ) / 100;
						}
					} else {
						$value = $share_own['own'];
					}
				} else {
					$value = 0;
				}
			} else {
				if (!empty($lic_ids)) {
					$feat = explode("@",$parts[1]);
					$feature = db_fetch_cell("SELECT lic_feature
						FROM grid_blstat_feature_map
						WHERE lsid='" . $parts[0] . "'
						AND bld_feature='" . $feat[0] . "'");

					$value = db_fetch_cell("SELECT SUM(feature_queued)
						FROM lic_services_feature_use
						WHERE service_id IN($lic_ids)
						AND feature_name='$feature'");
				} else {
					$value = 0;
				}
			}
		}
	}

	if (is_numeric($value)) {
		return trim($value);
	} else {
		return "0";
	}
}

function ss_grid_lssched_bp_getnames($host_id) {
	$return_arr = array();

	$lsid = db_fetch_cell_prepared("SELECT lsid FROM grid_blstat_collectors WHERE cacti_host = ?", array($host_id));
	if (empty($lsid)) {
		return $return_arr;
	}

	$arr = db_fetch_assoc("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS feature_sd_pr
		FROM grid_blstat_projects
		WHERE lsid='$lsid'
		ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '')");

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["feature_sd_pr"];
	}

	return $return_arr;
}

function ss_grid_lssched_bp_getinfo($host_id, $info_requested) {
	$return_arr = array();

	$lsid = db_fetch_cell("SELECT lsid FROM grid_blstat_collectors WHERE cacti_host=$host_id");
	if (empty($lsid)) {
		return $return_arr;
	}

	if ($info_requested == "lsid_feature_sd_pr") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_value
			FROM grid_blstat_projects
			WHERE lsid='$lsid'
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '')");
	} elseif ($info_requested == "sd") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			service_domain AS qry_value
			FROM grid_blstat_projects
			WHERE lsid='$lsid'
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '')");
	} elseif ($info_requested == "feature") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			feature AS qry_value
			FROM grid_blstat_projects
			WHERE lsid='$lsid'
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '')");
	} elseif ($info_requested == "region") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', gbp.lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			gbc.region AS qry_value
			FROM grid_blstat_projects AS gbp
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbp.lsid=gbc.lsid
			WHERE gbp.lsid='$lsid'
			ORDER BY CONCAT_WS('', gbc.lsid, '|', gbp.feature, '|', service_domain, '|', project, '')");
	} elseif ($info_requested == "collector") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', gbp.lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			gbc.name AS qry_value
			FROM grid_blstat_projects AS gbp
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbp.lsid=gbc.lsid
			WHERE gbp.lsid='$lsid'
			ORDER BY CONCAT_WS('', gbc.lsid, '|', gbp.feature, '|', service_domain, '|', project, '')");
	} elseif ($info_requested == "project") {
		$arr = db_fetch_assoc("SELECT CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '') AS qry_index,
			project AS qry_value
			FROM grid_blstat_projects
			WHERE lsid='$lsid'
			ORDER BY CONCAT_WS('', lsid, '|', feature, '|', service_domain, '|', project, '')");
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]["qry_index"]] = addslashes($arr[$i]["qry_value"]);
	}

	return $return_arr;
}
