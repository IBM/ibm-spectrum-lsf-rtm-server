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
	print call_user_func_array('ss_lic_flexlm_licenses', $_SERVER['argv']);
}

function ss_lic_flexlm_licenses($lic_id = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_lic_flexlm_getnames($lic_id);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_lic_flexlm_getnames($lic_id);
		$arr = ss_lic_flexlm_getinfo($lic_id, $arg1);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_lic_flexlm_getvalue($lic_id, $index, $arg);
	}
}

function ss_lic_flexlm_getvalue($lic_id, $featurename, $column) {
	global $poller_dates, $poller_status;

	$return_arr = array();

	$feat_explode = explode('-', $featurename);

	if (substr_count($feat_explode[0], '_')) {
		$legacy = true;
		$feat_explode = explode('_', $featurename);
		$service_id = $feat_explode[0];
	} else {
		$legacy = false;
		$service_id = $feat_explode[0];
	}

	if (!isset($poller_dates[$service_id])) {
		$time_status = db_fetch_row_prepared('SELECT status, poller_date
			FROM lic_services
			WHERE service_id = ?',
			array($service_id));

		$last_update = strtotime($time_status['poller_date']);
		$poller_dates[$service_id] = $last_update;
		$poller_status[$service_id] = $time_status['status'];
	}

	/* return 0 if the license server has not been responding for a while */
	/* poller_status: 1 HOST_DOWN, 4 HOST_ERROR */
	if ($poller_status[$service_id]=='1' || $poller_status[$service_id]=='4' || $poller_dates[$service_id] + 600 < time()) {
		return 0;
	}

	if ($column == 'inuse')         $column = 'feature_inuse_licenses';
	elseif ($column == 'maxavail') $column = 'feature_max_licenses';
	elseif ($column == 'reserved') $column = 'feature_reserved';
	elseif ($column == 'queued')   $column = 'feature_queued';

	/* support legacy indexes */
	if ($legacy) {
		$arr = db_fetch_cell_prepared("SELECT $column
			FROM lic_services_feature_use
			WHERE CONCAT(service_id, '_', feature_name) = ?
			AND service_id = ?",
			array($featurename, $service_id));
	} else {
		$arr = db_fetch_cell_prepared("SELECT $column
			FROM lic_services_feature_use
			WHERE CONCAT(service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) = ?
			AND service_id = ?",
			array($featurename, $lic_id));
	}

	if ($arr == '') {
		return 0;
	} else {
		return $arr;
	}
}

function ss_lic_flexlm_getnames($lic_id) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT CONCAT(service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS feature_name
		FROM lic_services_feature_use
		WHERE service_id = ?
		ORDER BY feature_name",
		array($lic_id));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_name'];
	}
	return $return_arr;
}

function ss_lic_flexlm_getinfo($lic_id, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'server') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_index,
			server_name AS qry_value
			FROM lic_services_feature_use AS lsfu
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfu.service_id
			WHERE lsfu.service_id = ?
			ORDER BY feature_name",
			array($lic_id));
	} elseif ($info_requested == 'vendor') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_index,
			vendor_daemon AS qry_value
			FROM lic_services_feature_use AS lsfu
			INNER JOIN lic_services AS ls
			ON lsfu.service_id=ls.service_id
			WHERE lsfu.service_id = ?
			ORDER BY vendor_daemon",
			array($lic_id));
	} elseif ($info_requested == 'feature') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_index,
			feature_name AS qry_value
			FROM lic_services_feature_use AS lsfu
			WHERE lsfu.service_id = ?
			ORDER BY feature_name",
			array($lic_id));
	} elseif ($info_requested == 'licenses') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_index,
			feature_max_licenses AS qry_value
			FROM lic_services_feature_use AS lsfu
			WHERE lsfu.service_id = ?
			ORDER BY feature_name",
			array($lic_id));
	} elseif ($info_requested == 'index') {
		$arr = db_fetch_assoc_prepared("SELECT CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_index,
			CONCAT(lsfu.service_id, '-', feature_name, '-', REPLACE(vendor_daemon, ' ', '_')) AS qry_value
			FROM lic_services_feature_use AS lsfu
			WHERE lsfu.service_id = ?
			ORDER BY feature_name",
			array($lic_id));
 	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
