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
	print call_user_func_array('ss_grid_licenses', $_SERVER['argv']);
}

function ss_grid_licenses($hostname = '', $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_licenses_getnames($hostname);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_licenses_getnames($hostname);
		$arr = ss_grid_licenses_getinfo($hostname, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_licenses_getvalue($hostname, $index, $arg);
	}
}

function ss_grid_licenses_getwhere($hostname, $aw = 'AND') {
	$service_ids = db_fetch_assoc("SELECT *
		FROM lic_services
		WHERE server_portatserver LIKE '%$hostname%'");

	$sql_where = '';
	$i = 0;
	if (cacti_sizeof($service_ids)) {
		foreach($service_ids as $id) {
			$delim = '';
			$found_host = false;
			if (substr_count($id['server_portatserver'], ',')) {
				$delim = ',';
			} else if (substr_count($id['server_portatserver'], ':')) {
				$delim = ':';
			} else if (substr_count($id['server_portatserver'], ';')) {
				$delim = ';';
			}
			if ($delim != '') {
				$split = explode($delim, $id['server_portatserver']);
				foreach ($split as $s) {
					$hostarr = explode('@', $s);
					if ($hostname == $hostarr[1]) {
						$found_host = true;
					}
				}
			} else {
				$split = explode('@', $id['server_portatserver']);
				if ($hostname == $split[1]) {
					$found_host = true;
				}
			}
			if (!$found_host) {
				continue;
			}

			if ($i == 0) {
				$sql_where = " $aw lic_services_feature_use.service_id IN (" . $id['service_id'];
				$i++;
			} else {
				$sql_where .= ', ' . $id['service_id'];
			}
		}
		if (strlen($sql_where)) {
			$sql_where .= ')';
		}
	} else {
		$sql_where = " $aw 0=1";
	}


	return $sql_where;
}

function ss_grid_licenses_getvalue($hostname, $featurename, $column) {
	global $poller_dates;

	$return_arr = array();

	$feat_explode = explode('-', $featurename);

	if (cacti_sizeof($feat_explode) == 3) {
		$service_id = $feat_explode[0];

		if (!isset($poller_dates[$service_id])) {
			$last_update = strtotime(db_fetch_cell_prepared('SELECT poller_date
				FROM lic_services
				WHERE service_id = ?',
				array($service_id)));

			$poller_dates[$service_id] = strtotime(date('%Y-%m-%d %H:%M:%S', ($last_update+date('Z'))));
		}

		/* return 0 if the license server has not been responding for a while */
		if ($poller_dates[$service_id] + 600 < time()) {
			return 0;
		}
	}

	$sql_where = ss_grid_licenses_getwhere($hostname);

	if ($column == 'inuse')         $column = 'feature_inuse_licenses';
	else if ($column == 'maxavail') $column = 'feature_max_licenses';
	else if ($column == 'reserved') $column = 'feature_reserved';
	else if ($column == 'queued')   $column = 'feature_queued';

	$arr = db_fetch_cell("SELECT $column
		FROM lic_services_feature_use
		WHERE CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon)='" . $featurename . "'
		$sql_where");

	if ($arr == '') {
		return 0;
	} else {
		return trim($arr);
	}
}

function ss_grid_licenses_getnames($hostname) {
	$return_arr = array();

	$sql_where = ss_grid_licenses_getwhere($hostname, 'WHERE');

	$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS feature_name
		FROM lic_services_feature_use
		$sql_where
		ORDER BY feature_name");

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['feature_name'];
	}

	return $return_arr;
}

function ss_grid_licenses_getinfo($hostname, $info_requested) {
	$return_arr = array();

	$sql_where = ss_grid_licenses_getwhere($hostname, 'WHERE');

	if ($info_requested == 'server') {
		$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_index,
			server_name AS qry_value
			FROM lic_services_feature_use
			INNER JOIN lic_services
			ON lic_services.service_id = lic_services_feature_use.service_id
			$sql_where
			ORDER BY feature_name");
	} else if ($info_requested == 'vendor') {
		$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_index,
			vendor_daemon AS qry_value
			FROM lic_services_feature_use
			INNER JOIN lic_services
			ON lic_services_feature_use.service_id=lic_services.service_id
			$sql_where
			ORDER BY vendor_daemon");
	} else if ($info_requested == 'feature') {
		$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_index,
			feature_name AS qry_value
			FROM lic_services_feature_use
			$sql_where
			ORDER BY feature_name");
	} else if ($info_requested == 'licenses') {
		$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_index,
			feature_max_licenses AS qry_value
			FROM lic_services_feature_use
			$sql_where
			ORDER BY feature_name");
	} else if ($info_requested == 'index') {
		$arr = db_fetch_assoc("SELECT CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_index,
			CONCAT(lic_services_feature_use.service_id, '-', feature_name, '-', vendor_daemon) AS qry_value
			FROM lic_services_feature_use
			$sql_where
			ORDER BY feature_name");
 	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
