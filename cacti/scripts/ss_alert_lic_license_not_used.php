<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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
	array_shift($_SERVER['argv']);
	print call_user_func_array('alert_license_not_used', $_SERVER['argv']);
}

function alert_license_not_used($cmd = 'query', $not_used_days = 1) {
	if ($cmd == 'query') {
		return query($not_used_days);
	}elseif ($cmd == 'get') {
		return get($not_used_days);
	}
}

function check_input_days($not_used_days) {//check if have records before the time inputed
	if (read_config_option('grid_partitioning_enable') == '') {
		$sql_query = "SELECT count(*)
			FROM lic_daily_stats
			WHERE date_recorded <= SUBDATE(now(),interval $not_used_days day)";

		$record_count = db_fetch_cell($sql_query);
	} else {
		$sql_query = "SELECT count(*)
			FROM grid_table_partitions
			WHERE table_name='lic_daily_stats'
			AND min_time <= SUBDATE(NOW(),interval $not_used_days day)";

		$record_count = db_fetch_cell($sql_query);

		if ($record_count == '' || $record_count == 0) {
			$sql_query = "SELECT count(*)
				FROM lic_daily_stats
				WHERE date_recorded <= SUBDATE(now(),interval $not_used_days day)";

			$record_count = db_fetch_cell($sql_query);
		}
	}

	return $record_count;
}

function get_table_query($not_used_days) {
	if (read_config_option('grid_partitioning_enable') == '') {
		$table_query = "lic_daily_stats WHERE date_recorded > SUBDATE(NOW(),interval $not_used_days day) AND action='INUSE'";
	} else {
		global $config;
		include_once($config['base_path'] . '/plugins/license/include/lic_functions.php');

		$current = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));
		$current = strftime('%F %H:%M:%S', $current);
		$earlier = mktime(date('H'), date('i'), date('s'), date('n'), date('j')-$not_used_days, date('Y'));
		$earlier = strftime('%F %H:%M:%S', $earlier);

		$table_query = make_partition_query('lic_daily_stats', $earlier, $current, " WHERE date_recorded > SUBDATE(NOW(),interval $not_used_days day) AND action='INUSE'");
	}

	return $table_query;
}

function get($not_used_days) {
	$breach_items = '"Feature","Last Day Seen","Server Name","Vendor Deamon","ID","Port@Server","Location","Support Information"' . "\n";
	if (!is_numeric($not_used_days)) {
		return $breach_items;
	}

	$record_count = check_input_days($not_used_days);
	if ($record_count == '' || $record_count == 0) {
		return $breach_items;
	}

	$table_query=get_table_query($not_used_days);
	$sql_query = "SELECT lfsfu.feature_name, lfsfu.vendor_daemon, traffic.last_updated, ls.service_id, ls.server_name,
		ls.server_portatserver, ls.server_location,
		ls.server_support_info, lds.date_recorded
		FROM lic_services_feature_use as lfsfu
		INNER JOIN lic_services AS ls
		ON lfsfu.service_id=ls.service_id
		LEFT JOIN (
			SELECT service_id, feature, MAX(date_recorded) AS date_recorded
			FROM $table_query
			GROUP BY service_id,feature
			UNION
			SELECT service_id,feature,date_recorded
			FROM lic_interval_stats
			WHERE date_recorded > SUBDATE(NOW(),interval $not_used_days day)
			AND action='INUSE'
			GROUP BY service_id,feature
		) AS lds
		ON lfsfu.service_id=lds.service_id
		AND lfsfu.feature_name=lds.feature
		LEFT JOIN
		(select service_id, feature, MAX(last_updated) as last_updated from lic_daily_stats_traffic GROUP BY service_id, feature)
		AS traffic
		ON lfsfu.service_id=traffic.service_id AND lfsfu.feature_name=traffic.feature
		WHERE lds.date_recorded IS NULL
		LIMIT ". read_config_option('gridalarm_alert_limit');

	$arr = db_fetch_assoc($sql_query);

	for ($i=0;($i<sizeof($arr));$i++) {
		$item = '"' .
			$arr[$i]['feature_name']        . '","'.
			(!empty($arr[$i]['last_updated']) ? substr($arr[$i]['last_updated'],0,10):'Never') . '","' .
			$arr[$i]['server_name']         . '","' .
			$arr[$i]['vendor_daemon']       . '","' .
			$arr[$i]['service_id']          . '","' .
			$arr[$i]['server_portatserver'] . '","' .
			$arr[$i]['server_location']     . '","' .
			$arr[$i]['server_support_info'] . '"';

		$breach_items = $breach_items . $item . "\n";
	}

	return $breach_items;
}

function query($not_used_days) {
	if (!is_numeric($not_used_days)) {
		return 0;
	}
	$record_count=check_input_days($not_used_days);
	if ($record_count == '' || $record_count == 0) {
		return 0;
	}
	$table_query=get_table_query($not_used_days);
	$sql_query = "SELECT count(*) FROM lic_services_feature_use as lfsfu
		LEFT JOIN (
			SELECT service_id,feature,date_recorded
			FROM $table_query
			GROUP BY service_id,feature
			UNION
			SELECT service_id,feature,date_recorded
			FROM lic_interval_stats
			WHERE date_recorded > SUBDATE(NOW(),interval $not_used_days day)
			AND action='INUSE'
			GROUP BY service_id,feature
		) AS lds
		ON lfsfu.service_id=lds.service_id
		AND lfsfu.feature_name=lds.feature
		WHERE lds.date_recorded IS NULL";

	$count = db_fetch_cell($sql_query);

	if ($count == '') {
		return 0;
	}else{
		return $count;
	}
}

