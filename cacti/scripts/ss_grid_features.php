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

$no_http_headers = true;

/* display No errors */
include_once(dirname(__FILE__) . "/../lib/functions.php");

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_grid_features", $_SERVER["argv"]);
}

function ss_grid_features($clusterid, $cmd = "index", $arg1 = "", $arg2 = "") {
	if ($cmd == "index") {
		$return_arr = ss_grid_features_getnames($clusterid, $arg1);
		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arr_index = ss_grid_features_getnames($clusterid, $arg1);
		$arr = ss_grid_features_getinfo($clusterid, $arg1, $arg2);
		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . "!" . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_features_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_features_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();

	$featwhere = "lsfu.feature_name='$index' AND ls.status != 0";
	switch ($column) {
	case "numAVAIL":
		$value = db_fetch_cell("SELECT
			SUM(lsfu.feature_max_licenses) AS value
			FROM lic_services_feature_use lsfu
			LEFT JOIN lic_services ls
			ON lsfu.service_id = ls.service_id
			WHERE $featwhere
			GROUP BY lsfu.feature_name");

		break;
	case "numUSED":
		$value = db_fetch_cell("SELECT
			SUM(lsfu.feature_inuse_licenses) AS value
			FROM lic_services_feature_use lsfu
			LEFT JOIN lic_services ls
			ON lsfu.service_id = ls.service_id
			WHERE $featwhere
			GROUP BY lsfu.feature_name");

		break;
	}

	if (!empty($value)) {
		return $value;
	}else{
		return "0";
	}
}

function ss_grid_features_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc("SELECT DISTINCT feature_name as featureName
		FROM lic_services_feature
		ORDER BY feature_name");

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["featureName"];
	}

	return $return_arr;
}

function ss_grid_features_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == "featureName") {
		$featureNames = db_fetch_assoc("SELECT DISTINCT feature_name AS featureName
					FROM lic_services_feature
					ORDER by feature_name");
		for ($i=0; ($i<cacti_sizeof($featureNames)); $i++) {
			$return_arr[$featureNames[$i]["featureName"]] = addslashes($featureNames[$i]["featureName"]);
		}

	}
	return $return_arr;
}

?>
