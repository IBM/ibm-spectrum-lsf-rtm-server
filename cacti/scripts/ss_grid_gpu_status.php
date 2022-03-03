<?php
// $Id$
$no_http_headers = true;

/* display No errors */
include_once(dirname(__FILE__) . "/../lib/functions.php");
error_no_deprecated();

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . "/../include/global.php");
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_grid_gpu", $_SERVER["argv"]);
}

function ss_grid_gpu($clusterid = 0, $host = '', $cmd = "index", $arg1 = "", $arg2 = "") {
	if ($cmd == "index") {
		$return_arr = ss_grid_gpu_getnames($clusterid, $host, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	}elseif ($cmd == "query") {
		$arr_index = ss_grid_gpu_getnames($clusterid, $host, $arg1);
		$arr = ss_grid_gpu_getinfo($clusterid, $host, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . "!" . $arr[$arr_index[$i]] . "\n";
			}
		}
	}elseif ($cmd == "get") {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_gpu_getvalue($clusterid, $host, $index, $arg);
	}
}

function ss_grid_gpu_getvalue($clusterid, $host, $index, $column) {
	global $grid_date_recorded;

	$return_arr = array();
	$index      = str_replace('GPU', '', $index);

	switch ($column) {
		case 'numJobs':
		case 'numRun':
		case 'numSUSP':
		case 'numRSV':
			$value = db_fetch_cell("SELECT $column
				FROM grid_hosts_gpu
				WHERE gpu_id='" . $index . "'
				AND clusterid='$clusterid'
				AND host='$host'");
			break;

		default:
			$value = db_fetch_cell("SELECT $column
				FROM grid_hostinfo_gpu
				WHERE gpu_id='" . $index . "'
				AND clusterid='$clusterid'
				AND host='$host'");
	}
	if (!empty($value)) {
		return $value;
	}else{
		return "0";
	}
}

function ss_grid_gpu_getnames($clusterid, $host) {
	$return_arr = array();

	$arr = db_fetch_assoc("SELECT CONCAT('GPU', gpu_id) AS gpuIndex
		FROM grid_hostinfo_gpu
		WHERE host='$host'
		AND clusterid='$clusterid'
		ORDER BY gpu_id");

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["gpuIndex"];
	}

	return $return_arr;
}

function ss_grid_gpu_getinfo($clusterid, $host, $info_requested) {
	$return_arr = array();

	if ($info_requested == "gpuIndex") {
		$arr = db_fetch_assoc("SELECT
			CONCAT('GPU', gpu_id) AS qry_index,
			CONCAT('GPU', gpu_id) AS qry_value
			FROM grid_hostinfo_gpu
			WHERE host='$host'
			AND clusterid='$clusterid'
			ORDER BY gpu_id");
	}else if ($info_requested == "gpumodel") {
		$arr = db_fetch_assoc("SELECT
			CONCAT('GPU', gpu_id) AS qry_index,
			CONCAT(gBrand, gModel) AS qry_value
			FROM grid_hostinfo_gpu
			WHERE host='$host'
			AND clusterid='$clusterid'
			ORDER BY gpu_id");
	}else if ($info_requested == "gpudriver") {
		$arr = db_fetch_assoc("SELECT
			CONCAT('GPU', gpu_id) AS qry_index,
			driverVersion AS qry_value
			FROM grid_hostinfo_gpu
			WHERE host='$host'
			AND clusterid='$clusterid'
			ORDER BY gpu_id");
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]["qry_index"]] = addslashes($arr[$i]["qry_value"]);
	}

	return $return_arr;
}

?>
