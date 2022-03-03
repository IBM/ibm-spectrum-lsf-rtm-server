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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include('./lib/rtm_timespan_settings.php');
include_once('./plugins/license/include/lic_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');

$title = 'IBM Spectrum LSF RTM - Daily Stats';

if (isset_request_var('export')) {
	lic_view_export_dstat_records();
} elseif (isset_request_var('action')) {
	switch(get_request_var('action')) {
	case 'ajax_rtm_lic_hosts':
		lic_view_dstats_request_vars();
		$sql_where = '';
		if (get_request_var('service_id') > 0) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "ls.service_id='" . get_request_var('service_id') . "' ";
		}
		if (!isempty_request_var('poller_type')) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "lp.poller_type='" . get_request_var('poller_type') . "' ";
		}
		$sql_where = "host!=''" . $sql_where;
		rtm_autocomplete_ajax('lic_dailystats.php', 'lic_host', $sql_where, array('0' => __('All', 'license'), '-1' => __('N/A', 'license')));
		break;
	case 'ajax_rtm_lic_users':
		lic_view_dstats_request_vars();
		$sql_where = '';
		if (get_request_var('service_id') > 0) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "ls.service_id='" . get_request_var('service_id') . "' ";
		}
		if (!isempty_request_var('poller_type')) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "lp.poller_type='" . get_request_var('poller_type') . "' ";
		}
		$sql_where = "user!=''" . $sql_where;
		rtm_autocomplete_ajax('lic_dailystats.php', 'lic_user', $sql_where, array('0' => __('All', 'license'), '-1' => __('N/A', 'license')));
		break;
	case 'ajax_rtm_lic_features':
		lic_view_dstats_request_vars();
		$sql_where = '';
		if (get_request_var('service_id') > 0) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "lsfu.service_id='" . get_request_var('service_id') . "' ";
		}
		if (!isempty_request_var('poller_type')) {
			$sql_where = ($sql_where != '' ? ' AND ' : '') . "lp.poller_type='" . get_request_var('poller_type') . "' ";
		}
		rtm_autocomplete_ajax('lic_dailystats.php', 'lic_feature', $sql_where, array('0' => __('All', 'license')));
		break;
	}
} else {
	lic_view_dstats();
}

function get_dstat_records(&$table_name, $apply_limits = true, $row_limit = '30', &$total_rows) {
	global $grid_efficiency_sql_ranges;

	$sql_where = '';
	$group_by = '';

	$sql_where_daily  = '';
	$sql_where_service = '';
	$sql_where_lafm = '';

	$type    = 0;
	$type_id = 1;

	$type_user = 1 << 1;
	$type_host = 1 << 2;
	$type_org  = 1 << 3;

	$group_by_daily = '';

	$time_sql = db_fetch_row("SELECT value
		FROM settings
		WHERE name='lic_daily_stats_end_time'");

	//$time_sql and $daily_time are used to optimize time expression in where clause
	//Since FP5, page was re-design. Temp-ly comment $daily_time to avoid PHP Warning
	//$daily_time   = date('Y-m-d', $time_sql['value']);
	$current_time = date('Y-m-d', time());

	$sql_where_daily = " AND interval_end BETWEEN '" . get_request_var('date1') . "'
		AND '" . get_request_var('date2') . "'";

	$day1 = strtotime(get_request_var('date1'));
	$day2 = strtotime(get_request_var('date2'));

	$timespan = ($day2-$day1)/3600/24;
	if ($timespan == 0) {
		$timespan = 1;
	}

	if (get_request_var('date1') > $current_time) {
		return NULL;
	}

	if (get_request_var('keyfeat') == 'true') {
		$sql_where_daily .= ($sql_where_daily != '' ? ' AND ':' WHERE ') . 'critical = 1';
	}

	if (isset_request_var('summarize') && get_request_var('summarize') == 'true') {
		$interval = "'N/A' AS interval_end";
		$group_by = '';
		$group_by_daily = '';
	} else {
		$timespan = 1;
		$interval = 'interval_end';
		$group_by = 'interval_end';
		$group_by_daily = 'interval_end';
	}

	/* portatserver sql where */
	if (get_request_var('service_id') == '-1') {
		//'N/A' service id is 001;
		$type_id   = 0;
		$id        = "'N/A' AS id";
		$id2       = "'N/A' AS id";
		$service   = "'N/A' AS server_name";
	} elseif (get_request_var('service_id') == '0') {
		$id        = 'service_id AS id';
		$id2       = 'id';
		$service   = 'server_name AS server_name';
		$group_by .= ($group_by != '' ? ',' : '') . ' id';

		$group_by_daily .=($group_by_daily != '' ? ',' : '') . ' id';
	} else {
		$id        = 'service_id AS id';
		$id2       = 'id';
		$service   = 'server_name AS server_name';
		$group_by .= ($group_by != '' ? ',' : '') . ' id';

		$group_by_daily  .=($group_by_daily != '' ? ',' : '') . ' id';
		$sql_where_daily .= ' AND (lic_daily_stats.service_id=' . get_request_var('service_id') . ')';
	}

	/* region sql where */
	if (get_request_var('region') == '-1') {
		$region    = "'N/A' AS server_region";
	} elseif (get_request_var('region') == '-2') {
		$region    = 'server_region';
		$group_by .= ($group_by != '' ? ',' : '') . ' server_region';
	} else {
		$region    = 'server_region';
		$group_by .= ($group_by != '' ? ',' : '') . ' server_region';

		if ($sql_where_service == '') {
			$sql_where_service .= " WHERE (ls.server_region='" . get_request_var('region') . "')";
		} else {
			$sql_where_service .= " AND (ls.server_region='" . get_request_var('region') . "')";
		}
	}

	if (get_request_var('poller_type') != 0) {
		$sql_where_service .= ($sql_where_service != '' ? ' AND ':' WHERE ') . 'poller_type = ' . get_request_var('poller_type');
	}

	/* feature sql where */
	if (get_request_var('feature') == '-1') {
		$feature   = "'N/A' AS feature";
	} elseif (get_request_var('feature') == '0') {
		$feature   = 'feature';
		$group_by .= ($group_by != '' ? ',' : '') . ' feature';

		$group_by_daily .= ($group_by_daily != '' ? ',' : '') . ' feature';
	} else {
		$feature   = 'feature';
		$group_by .= ($group_by != '' ? ',' : '') . ' feature';

		$group_by_daily  .= ($group_by_daily != '' ? ',' : '') . ' feature';
		$sql_where_daily .= " AND (" . get_feature_where(get_request_var('feature')) . ")";
	}

	/* user sql where */
	if (get_request_var('couser') == '-1') {
		//'N/A' User is 010
		$type_user        = 0;
		$user             = "'N/A' AS user";
	} elseif (get_request_var('couser') == '0') {
		$user             = 'user';
		$group_by        .= ($group_by != '' ? ',' : '') . ' user';
		$group_by_daily  .= ($group_by_daily != '' ? ',' : '') . ' user';
	} else {
		$user             = 'user';
		$group_by        .= ($group_by != '' ? ',' : '') . ' user';
		$group_by_daily  .= ($group_by_daily != '' ? ',' : '') . ' user';
		$sql_where_daily .= " AND (user='" . get_request_var('couser') . "')";
	}

	/* host sql where */
	if (get_request_var('host') == '-1') {
		//'N/A' host is 100;
		$type_host        = 0;
		$host             = "'N/A' AS host";
	} elseif (get_request_var('host') == '0') {
		$host             = 'host';
		$group_by        .= ($group_by != '' ? ',' : '') . ' host';
		$group_by_daily  .= ($group_by_daily != '' ? ',' : '') . ' host';
	} else {
		$host             = 'host';
		$group_by        .= ($group_by != '' ? ',' : '') . ' host';
		$group_by_daily  .= ($group_by_daily != '' ? ',' : '') . ' host';
		$sql_where_daily .= " AND (host='" . get_request_var('host') . "')";
	}

	if (get_request_var('filter') != '') {
		if (strtolower(read_config_option('lic_filter_exact')) == 'on' && isset_request_var('reset')) {
			$sql_where_daily .= " AND feature='" . get_request_var('filter') . "'";
		} else {
			$sql_where_service .=($sql_where_service != '' ? ' AND':'WHERE') . "
				(ls.server_region LIKE '%" . get_request_var('filter') ."%'
				OR feature LIKE '%" . get_request_var('filter') ."%'
				OR user LIKE '%" . get_request_var('filter') ."%'
				OR host LIKE '%" . get_request_var('filter') ."%')";

			$sql_where_daily .= " AND (feature LIKE '%" . get_request_var('filter') ."%'
				OR lafm.user_feature_name LIKE '%" . get_request_var('filter') ."%'
				OR user LIKE '%" . get_request_var('filter') ."%'
				OR host LIKE '%" . get_request_var('filter') ."%')";

			$sql_where_lafm = " WHERE user_feature_name LIKE '%" . get_request_var('filter') ."%' ";
		}
	}

	if ($group_by != '') {
		$group_by = 'GROUP BY ' . $group_by;
	}

	if ($group_by_daily != '') {
		$group_by_daily = 'GROUP BY ' . $group_by_daily;
	}

	$sort_order = get_order_string();

	$sql_interval_query = '';
	$sql_daily_query    = '';

	$type = ($type_id | $type_user | $type_host) + 1;
	if ($type == 8) {
		$type = 0;
	}

// 	if ($type_org != 0) {
// 		$type = 0;
// 		$sql_where_daily .= " AND (user != 'N/A')";
// 	}

	$poller_interval = read_config_option('poller_interval');
	if (empty($poller_interval)) {
		$poller_interval = 300;
	}

	$cycles_per_hour = 3600 / $poller_interval;

	$table_name = 'lic_daily_stats';

	if ($type == 1 || $type == 3 || $type == 5 || $type == 7) {
		if (get_request_var('poller_type') == 0) {
			$sql_daily_query = "SELECT $id, $service, $user, $host,
				$feature, $region,
				SUM(`count`)*$poller_interval AS checkouttime,
				SUM(`count`)/(total_license_count*$cycles_per_hour*24*$timespan) AS utilization,
				MAX(peak_ut) AS peak_ut,
				SUM(`count`) AS total_tokens,
				total_license_count,
				interval_end
				FROM $table_name
				LEFT JOIN (SELECT * FROM lic_application_feature_map $sql_where_lafm GROUP BY feature_name) AS lafm
				ON $table_name.feature = lafm.feature_name
				WHERE type='$type'
				$sql_where_daily
				$group_by_daily";
		} else {
			$sql_daily_query="SELECT $id, $service, $user, $host,
				$feature, $region,
				SUM(`count`)*$poller_interval AS checkouttime,
				SUM(`count`)/(total_license_count*$cycles_per_hour*24*$timespan) AS utilization,
				MAX(peak_ut) AS peak_ut,
				SUM(`count`) AS total_tokens,
				total_license_count,
				interval_end
				FROM $table_name
				INNER JOIN lic_services AS ls
				ON $table_name.service_id=ls.service_id
				INNER JOIN lic_pollers AS lp
				ON ls.poller_id=lp.id
				LEFT JOIN (SELECT * FROM lic_application_feature_map $sql_where_lafm GROUP BY feature_name) AS lafm
				ON $table_name.feature = lafm.feature_name
				WHERE type = '$type'
				$sql_where_daily " .
				($sql_where_daily != '' ? ' AND ':'WHERE ') . ' (poller_type='. get_request_var('poller_type') .') ' .
				$group_by_daily;
		}
	} else {
		$sql_daily_query = "SELECT $id, $service, $user, $host,
			$feature, $region, checkouttime, utilization,
			peak_ut, total_tokens, total_license_count, interval_end
			FROM (
				SELECT $table_name.service_id AS id, user, host, feature,
				SUM(`count`)*$poller_interval AS checkouttime,
				SUM(`count`)/(total_license_count*$cycles_per_hour*24*$timespan) AS utilization,
				MAX(peak_ut) AS peak_ut,
				SUM(`count`) AS total_tokens,
				total_license_count,
				interval_end
				FROM $table_name
				LEFT JOIN (SELECT * FROM lic_application_feature_map $sql_where_lafm GROUP BY service_id, feature_name) AS lafm
				ON $table_name.service_id = lafm.service_id
				AND $table_name.feature = lafm.feature_name
				WHERE type = '$type'
				$sql_where_daily
				$group_by_daily
			) AS it
			LEFT JOIN lic_services AS ls
			ON it.id=ls.service_id
			INNER JOIN lic_pollers AS lp
			ON ls.poller_id=lp.id
			$sql_where_service
			$group_by";
	}

	if (read_config_option('grid_partitioning_enable')) {
		$union_tables = partition_get_partitions_for_query('lic_daily_stats', get_request_var('date1'), get_request_var('date2'));

		if (cacti_sizeof($union_tables)) {
			$union_query = '';

			foreach ($union_tables as $table) {
				if ($union_query != '') {
					$union_query .= ' UNION ALL ';
				}

				$union_query .= str_replace($table_name, $table, $sql_daily_query);
			}
		} else {
			return NULL;
		}

		$sql_daily_query = $union_query;
	}

	$sql_query = "SELECT $id2, $service, $user, $host,
		$feature, $region,
		SUM(checkouttime) AS checkouttime,
		SUM(total_tokens)/(total_license_count*$cycles_per_hour*24*$timespan) AS utilization,
		MAX(peak_ut) AS peak_ut,
		total_tokens, $interval
		FROM (
			$sql_daily_query
		) AS it
		$group_by";

	$sql_query .= " " . $sort_order;

	$total_rows = db_fetch_cell("SELECT COUNT(*) FROM ($sql_query) AS q");

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//echo $sql_query;
	return db_fetch_assoc($sql_query);
}

function lic_view_export_dstat_records() {
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;

	lic_view_dstats_request_vars(true);

	$table_name = '';
	$total_rows = 0;

	$stats = get_dstat_records($table_name, true, read_config_option('grid_xport_rows'), $total_rows);

	$columns = '"Feature","User"';

	$columns .= ',"Host","Service","Region","Cost","Token Time",' .
        '"Avg Utilization","Peak Utilization","End Date"';

	$xport_array = array();

	array_push($xport_array, $columns);

	foreach($stats as $stat) {
		$cost = get_usage_cost($stat['feature'], $stat['id'], $stat['checkouttime']);

		array_push($xport_array,'"' .
			$stat['feature']        . '","' . $stat['user']          . '","' .
			$stat['host']           . '","' . $stat['server_name']   . '","' .
			$stat['server_region']  . '","' . $cost                  . '","' .
			$stat['checkouttime']   . '","' . $stat['utilization']   . '","' .
			$stat['peak_ut']        . '","' .
			($stat['interval_end'] != 'N/A' ? substr($stat['interval_end'],0,10):'N/A') . '"');
	}

	header('Content-type: application/csv');
	header('Cache-Control: max-age=15');
	header('Content-Disposition: attachment; filename=license_dstats_xport.csv');
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}

function dailyStatsFilter() {
	global $config, $lic_rows_selector, $grid_refresh_interval;
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;

	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_dailystats.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Service', 'license');?>
					</td>
					<td>
						<select id='service_id' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('service_id') == '0') {?> selected<?php }?>>All</option>
							<?php
							if (isempty_request_var('poller_type')) {
								print "<option value='-1'"; if (get_request_var('service_id') == '-1') { print ' selected'; } print '>N/A</option>';
								$services = db_fetch_assoc('SELECT service_id AS id, server_name AS name
									FROM lic_services
									WHERE disabled=""
									ORDER BY name');
							} else {
								$services = db_fetch_assoc_prepared('SELECT service_id AS id, server_name AS name
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE poller_type=?
									AND disabled=""
									ORDER BY name', array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($services)) {
								foreach ($services as $s) {
									print '<option value="' . $s['id'] . '"'; if (get_request_var('service_id') == $s['id']) { print ' selected'; } print '>' . $s['name'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Manager', 'license');?>
					</td>
					<td>
						<select id='poller_type' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('poller_type') == '0') {?> selected<?php }?>>All</option>
							<?php
							$managers = db_fetch_assoc('SELECT id, name
								FROM lic_managers
								WHERE disabled=""
								ORDER BY name');

							if (cacti_sizeof($managers)) {
								foreach ($managers as $manager) {
									print '<option value="' . $manager['id'] . '"'; if (get_request_var('poller_type') == $manager['id']) { print ' selected'; } print '>' . html_escape($manager['name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Region', 'license');?>
					</td>
					<td width='1'>
						<select id='region' onChange='applyFilter()' <?php if (get_request_var('service_id')=='-1') {?> disabled='true'<?php }?>>
							<option value='-2' <?php  if (get_request_var('region') == '-2')  { print 'selected'; }?>>All</option>
							<option value='-1' <?php if (get_request_var('region') == '-1') { print 'selected'; }?>>N/A</option>
							<?php
							if (get_request_var('service_id') <= 0) {
								if (isempty_request_var('poller_type')) {
									$regions = db_fetch_assoc("SELECT DISTINCT server_region
										FROM lic_services AS ls
										INNER JOIN lic_pollers AS lp
										ON ls.poller_id=lp.id
										WHERE server_region!=''
										ORDER BY server_region");
								} else {
									$regions = db_fetch_assoc_prepared("SELECT DISTINCT server_region
										FROM lic_services AS ls
										INNER JOIN lic_pollers AS lp
										ON ls.poller_id=lp.id
										WHERE server_region!=''
										AND lp.poller_type=?
										ORDER BY server_region", array(get_request_var('poller_type')));
								}
							} else {
								if (isempty_request_var('poller_type')) {
									$regions = db_fetch_assoc_prepared("SELECT DISTINCT server_region
										FROM lic_services AS ls
										INNER JOIN lic_pollers AS lp
										ON ls.poller_id=lp.id
										WHERE server_region!=''
										AND service_id=?
										ORDER BY server_region", array(get_request_var('service_id')));
								} else {
									$regions = db_fetch_assoc_prepared("SELECT DISTINCT server_region
										FROM lic_services AS ls
										INNER JOIN lic_pollers AS lp
										ON ls.poller_id=lp.id
										WHERE server_region!=''
										AND service_id=?
										AND lp.poller_type=?
										ORDER BY server_region", array(get_request_var('service_id'), get_request_var('poller_type')));
								}
							}

							if (cacti_sizeof($regions)) {
								foreach ($regions as $r) {
									print '<option value="' . $r['server_region'] . '"'; if (get_request_var('region') == $r['server_region']) print ' selected'; print '>' . $r['server_region'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php key_features();?>
					<td>
						<input id='summarize' type='checkbox' <?php if ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on')) print ' checked="true"';?> onClick='applyFilter()'>
					</td>
					<td>
						<label for='summarize' style='vertical-align:30%;'> <?php print __('Summarize', 'license');?></label>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters'>
					</td>
					<td>
						<input type='button' id='export' value='Export' title='Export to CSV'>
					</td>
				</tr>
			</table>
			<?php lic_level_filter();?>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<?php
						$sql_where_feature = '';
						if (get_request_var('service_id') > 0) {
							$sql_where_feature = $sql_where_feature . " AND lsfu.service_id='" . get_request_var('service_id') . "' ";
						}
						if (!isempty_request_var('poller_type')) {
							$sql_where_feature = $sql_where_feature . " AND lp.poller_type='" . get_request_var('poller_type') . "' ";
						}
						$feature_field_value = get_request_var('feature');
						$feature_field_value = ((strpos($feature_field_value, 'rtmft_') !== false || strpos($feature_field_value, 'rtmapp_') !== false) ? substr($feature_field_value, strpos($feature_field_value, '_')+1) : $feature_field_value);
						print html_autocomplete_filter('lic_dailystats.php', __('Feature', 'license'), 'lic_feature', $feature_field_value, 'applyFilter', $sql_where_feature, array('0' => __('All', 'license')));

						$sql_where_raw = '';
						if (get_request_var('service_id') > 0) {
							$sql_where_raw = $sql_where_raw . " AND ls.service_id='" . get_request_var('service_id') . "' ";
						}
						if (!isempty_request_var('poller_type')) {
							$sql_where_raw = $sql_where_raw . " AND lp.poller_type='" . get_request_var('poller_type') . "' ";
						}

						$sql_where_user = "user!=''" . $sql_where_raw;
						print html_autocomplete_filter('lic_dailystats.php', __('User', 'license'), 'lic_user', get_request_var('couser'), 'applyFilter', $sql_where_user, array('-1' => __('N/A', 'license'), '0' => __('All', 'license')));

						$sql_where_host = "host!=''" . $sql_where_raw;
						print html_autocomplete_filter('lic_dailystats.php', __('Host', 'license'), 'lic_host', get_request_var('host'), 'applyFilter', $sql_where_host, array('-1' => __('N/A', 'license'), '0' => __('All', 'license')));
					?>
					<td>
						<?php print __('Records', 'license');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector) > 0) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print 'selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Presets', 'license');?>
					</td>
					<td>
						<select id='predefined_timespan' onChange='applyFilterChangePDTS()'>
							<?php
							if ($_SESSION['sess_ldst_custom']) {
								$lic_timespans[GT_CUSTOM] = 'Custom';
								$start_val = 0;
								$end_val   = cacti_sizeof($lic_timespans);
							} else {
								if (isset($lic_timespans[GT_CUSTOM])) {
									asort($lic_timespans);
									array_shift($lic_timespans);
								}
								$start_val = 1;
								$end_val   = cacti_sizeof($lic_timespans) + 1;
							}

							if (cacti_sizeof($lic_timespans)) {
								$retention = read_config_option('lic_data_retention', true);
								$lastday = strtotime(date("Y-m-d",strtotime("-".$retention)));
								$timespan = array();
								$first_weekdayid = read_lic_config_option('first_weekdayid');

								if ($start_val == 0) {
									print "<option value='0'"; if ($_SESSION['sess_ldst_current_timespan'] == '0') { print ' selected'; } print '>Custom</option>';
								}
								for ($value=$start_val; $value < $end_val; $value++) {
									if ($value > 6 && $value <> GT_THIS_DAY && $value <> GT_DAY_SHIFT) {
										rtm_get_timespan($timespan, time(), $value , $first_weekdayid);
										if (strtotime($timespan['begin_now']) >= $lastday && strtotime($timespan['end_now']) >= $lastday) {
											print "<option value='" . $value . "'"; if ($_SESSION['sess_ldst_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($lic_timespans[$value], 40) . '</option>';
										}
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'license');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date1' aria-label='enter the from date with the format yyyymmdd, for example 2016-09-09 is input as 20160909' size='10' value='<?php print (isset($_SESSION['sess_ldst_current_date1']) ? $_SESSION['sess_ldst_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'license');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'license');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' aria-label='enter the to date with the format yyyymmdd, for example 2016-09-09 is input as 20160909' size='10' value='<?php print (isset($_SESSION['sess_ldst_current_date2']) ? $_SESSION['sess_ldst_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector', 'license');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<?php
							$fromDate = date("Y-m-d",strtotime("-1 day"));
							$toDate = date("Y-m-d");
							if (isset($_SESSION['sess_ldst_current_date1'])) {
								$fromDate = $_SESSION['sess_ldst_current_date1'];
							}
							if (isset($_SESSION['sess_ldst_current_date2'])) {
								$toDate = $_SESSION['sess_ldst_current_date2'];
							}
							$retention = read_config_option('lic_data_retention', true);
							$lastday = strtotime(date("Y-m-d",strtotime("-".$retention)));

							$shift = 1;
							if (isset($_SESSION['sess_ldst_current_timeshift'])) {
								$shift = $_SESSION['sess_ldst_current_timeshift'];
							}
							$shiftDate = $lic_timeshifts[$shift];
							$day1 = strtotime("-".$shiftDate, strtotime($fromDate));
							$day2 = strtotime("-".$shiftDate, strtotime($toDate));
							if ($day1 < $lastday || $day2 < $lastday) {
								print "<i id='move_left' class='shiftArrow fa fa-backward' title='".__esc('Shift Time Backward', 'license')."' style='display:none'></i>";
							} else {
								print "<i id='move_left' class='shiftArrow fa fa-backward' title='".__esc('Shift Time Backward', 'license')."'></i>";
							}
							?>
							<select id='predefined_timeshift' title='Define Shifting Interval'>
							<?php
							$start_val = 1;
							$end_val   = cacti_sizeof($lic_timeshifts) + 1;
							if (cacti_sizeof($lic_timeshifts)) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									if ($shift_value >= 7) {
										print "<option value='" . $shift_value . "'"; if ($_SESSION['sess_ldst_current_timeshift'] == $shift_value) { print ' selected'; } print '>' . title_trim($lic_timeshifts[$shift_value], 40) . '</option>';
									}
								}
							}
							?>
							</select>
							<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward', 'license');?>'></i>
						</span>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='35' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='1'>
			</form>
		</td>
	</tr>
	<?php
}

function lic_view_dstats() {
	global $title, $grid_search_types, $lic_rows_selector, $config;
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;

	lic_view_dstats_request_vars();

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilterChangePDTS() {
		strURL  = 'lic_dailystats.php?header=false&predefined_timespan=' + $('#predefined_timespan').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function moveRight() {
		strURL  = 'lic_dailystats.php?header=false'
		strURL += '&move_right_x=1';
		strURL += '&date1=' + $('#date1').val();
		strURL += '&date2=' + $('#date2').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function moveLeft() {
		strURL = 'lic_dailystats.php?header=false';
		strURL += '&move_left_x=1';
		strURL += '&date1=' + $('#date1').val();
		strURL += '&date2=' + $('#date2').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function applyFilter() {
		strURL = 'lic_dailystats.php?header=false&service_id=' + $('#service_id').val();
		strURL += '&feature=' + $('#lic_feature').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&couser=' + $('#lic_user').val();
		strURL += '&host=' + $('#lic_host').val();
		strURL += '&poller_type=' + $('#poller_type').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&summarize=' + $('#summarize').is(':checked');
		strURL += '&keyfeat=' + $('#keyfeat').is(':checked');
		if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
			strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
		} else {
			strURL = strURL + '&date1=' + $('#date1').val();
			strURL = strURL + '&date2=' + $('#date2').val();
		}
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

		if ($('#service_id').find('option:selected').text()=='N/A') {
			strURL += '&region=-1';
		} else {
			strURL += '&region=' + $('#region').val();
		}

		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'lic_dailystats.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	function exportFilter() {
		strURL  = 'lic_dailystats.php?header=false&export=true';
		document.location = strURL;
		Pace.stop();
	}

	function getDate(dateString) {
		var timediff = 0;
		if(dateString === "1day" || dateString === "1 Day") {
			timediff = 1;
		} else if (dateString === "2days" || dateString === "2 Days") {
			timediff = 2;
		} else if (dateString === "3days" || dateString === "3 Days") {
			timediff = 3;
		} else if (dateString === "4days" || dateString === "4 Days") {
			timediff = 4;
		} else if (dateString === "1week" || dateString === "1 Week") {
			timediff = 7;
		}
		return timediff;
	}

	$(function() {
		date1='<?php print rtm_safe_session('sess_ldst_current_date1');?>';
		date2='<?php print rtm_safe_session('sess_ldst_current_date2');?>';

		var retention = "<?php if (read_config_option('lic_data_retention', true)) {print read_config_option('lic_data_retention', true);}?>";
		var lastday = new Date();
		lastday.setDate(lastday.getDate() - getDate(retention));
		lastday.setHours(0);
		lastday.setMinutes(0);
		lastday.setSeconds(0);
		var timeshifts = new Array(<?php
										if (cacti_sizeof($lic_timeshifts)) {
											print "\"0 Min\",";//$lic_timeshifts starts at index 1(not as usual 0), so make a dummy item in the new array.
											for ($shift_value=1; $shift_value <= cacti_sizeof($lic_timeshifts); $shift_value++) {
												print "\"$lic_timeshifts[$shift_value]\",";
											}
										}
									?>);

		$('#form_lic_view').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#export').click(function() {
			exportFilter();
		});

		$('.tableSubHeaderColumn, .navBar').find('a').click(function(event) {
			event.preventDefault();

			if ($('#predefined_timespan').val() == '0') {
				document.location = $(this).attr('href') + '&date1=' + $('#date1').val() + '&date2=' + $('#date2').val();
			} else {
				document.location = $(this).attr('href') + '&predefined_timespan=' + $('#predefined_timespan').val();
			}
		});

		var date1Open = false;
		var date2Open = false;

		$('#startDate').click(function() {
			if (date1Open) {
				date1Open = false;
				$('#date1').datetimepicker('hide');
			} else {
				date1Open = true;
				$('#date1').datetimepicker('show');
			}
		});

		$('#endDate').click(function() {
			if (date2Open) {
				date2Open = false;
				$('#date2').datetimepicker('hide');
			} else {
				date2Open = true;
				$('#date2').datetimepicker('show');
			}
		});

		$('#date1').datepicker({
			dateFormat: 'yy-mm-dd',
<?php if (read_config_option('lic_data_retention', true)) {print "minDate: '-".read_config_option('lic_data_retention', true)."',";}?>
			buttonText: 'Select Start Date'
		});

		$('#date2').datepicker({
			dateFormat: 'yy-mm-dd',
<?php if (read_config_option('lic_data_retention', true)) {print "minDate: '-".read_config_option('lic_data_retention', true)."',";}?>
			buttonText: 'Select End Date'
		});

		$('#move_left').click(function() {
					moveLeft();
		});

		$('#move_right').click(function() {
			moveRight();
		});

		$('#predefined_timeshift').change(function() {
			var timeShift = getDate(timeshifts[$(this).val()]);
			var fromDate = new Date($(date1).val());
			var toDate = new Date($(date2).val());
			fromDate.setDate(fromDate.getDate() - timeShift);
			toDate.setDate(toDate.getDate() - timeShift);
			if (fromDate < lastday || toDate < lastday) {
				$('#move_left').attr("style","display:none");
			} else {
				$('#move_left').attr("style","display:auto");
			}
		});

		$('.ui-datepicker-trigger').css('padding-left', '3px');
		applySkin();
		applySkinRTM();
	});

	</script>
	<?php

	if (get_request_var('rows') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows');
	}

	$table_name = '';
	$total_rows = 0;

	$stats = get_dstat_records($table_name, true, $row_limit, $total_rows);

	html_start_box(__('Daily Statistics Filter'), '100%', '', '3', 'center', '');
	dailyStatsFilter();
	html_end_box(true);

	html_start_box('', '100%', '', '3', 'center', '');

	$display_text = build_dstat_display_array();

	/* generate page list */
	$nav = html_nav_bar('lic_dailystats.php?predefined_timespan=' . get_request_var('predefined_timespan'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('Daily Stat'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($stats)) {
		foreach ($stats as $stat) {
			$distrib_page  = htmlspecialchars($config['url_path'] .
				'plugins/license/lic_dist_reports.php' .
				'?service_id='      . $stat['id'] .
				'&poller_type=0'    .
				'&couser='            . ($stat['user'] != 'N/A' ? $stat['user']:'-1') .
				(get_request_var('predefined_timespan') == 0 ?
				'&date1='           . get_request_var('date1') .
				'&date2='           . get_request_var('date2'):
				'&predefined_timespan=' . get_request_var('predefined_timespan')) .
				'&host=-2'          .
				'&keyfeat=false'    .
				'&measure=-2'       .
				'&show=-1'          .
				'&rows_selector=10' .
				'&feature='         . $stat['feature']);

			$reports_page  = htmlspecialchars($config['url_path'] .
				'plugins/license/lic_lm_fusion.php?reset=true' .
				'&service_id='   . ($stat['id'] != 'N/A' ? $stat['id'] : '-1').
				'&poller_type=0' .
				'&couser='         . ($stat['user'] != 'N/A' ? $stat['user']:'-1') .
				'&timestamp=-1' .
				'&feature='      . $stat['feature']);

			$service_name = $stat['server_name'];

			form_alternate_row();

			/* get query values */
			$query_string = '?reset=true&action=viewlist';

			if ($stat['id'] != 'N/A')            $query_string .= '&id='      . $stat['id'];

			if ($stat['user'] != 'N/A')          $query_string .= '&couser='    . $stat['user'];
			if ($stat['host'] != 'N/A')          $query_string .= '&host='    . $stat['host'];
			if ($stat['feature'] != 'N/A')       $query_string .= '&feature=' . $stat['feature'];
			if ($stat['server_region'] != 'N/A') $query_string .= '&region='  . $stat['server_region'];

			if ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on')) {
				$query_string .= '&date1=' . urlencode(get_request_var('date1') . ' 00:00');
				$query_string .= '&date2=' . urlencode(get_request_var('date2') . ' 00:00');
			} else {
				$query_string .= '&date1=' . urlencode($stat['interval_end']);
				$query_string .= '&date2=' . urlencode($stat['interval_end']);
			}

			if (empty($stat['user'])) {
				$user = 'unknown';
			} elseif ($stat['user'] != 'N/A') {
				$user = $stat['user'];
			} else {
				$user = 'N/A';
			}

			if (empty($stat['host'])) {
				$host = 'unknown';
			} else {
				$host = $stat['host'];
			}

			// Actions
			print '<td style="white-space:nowrap;">';
			print '<a style="padding-right:3px;" href="' . $reports_page . '" title="View Reports"><img src="' . $config['url_path'] . 'plugins/license/images/view_charts.gif" align="absmiddle" border="0"></a>';
			//print '<a style="padding-right:3px;" href="' . $distrib_page . '" title="View License Distribution"><img src="' . $config['url_path'] . 'plugins/license/images/view_distribution.png" align="absmiddle" border="0"></a>';
			print "</td>";

			if ($stat['feature'] != 'N/A') {
				print '<td title="' . htmlspecialchars($stat['feature']) . '">' . filter_value(title_trim(get_feature_name($stat['feature'], $stat['id']), 50), get_request_var('filter')) . '</td>';
			} else {
				print '<td>' . $stat['feature'] . '</td>';
			}

			lic_show_metadata_detailed('detailed', 'user', $user, true);
			lic_show_metadata_detailed('simple', 'host', $host, true);
			print '<td>' . $service_name  . '</td>';
			print '<td>' . filter_value($stat['server_region'], get_request_var('filter')) . '</td>';
			print "<td style='text-align:right;'>" . get_usage_cost($stat['feature'], $stat['id'], $stat['checkouttime']) . '</td>';
			print "<td style='text-align:right;'>" . lic_format_seconds($stat['checkouttime']) . '</td>';
			print "<td style='text-align:right;'>" . round($stat['utilization']*100,2) . ' %</td>';
			print "<td style='text-align:right;'>" . round($stat['peak_ut']*100,2) . '%</td>';
			print "<td style='text-align:right;'>" . ($stat['interval_end'] != 'N/A' ? substr($stat['interval_end'],0,10):'N/A') . '</td>';
		}
		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='8'><em>No Daily Stat Records Found</em></td></tr>";
		html_end_box(false);
	}

	api_plugin_hook('lic_page_bottom');

	bottom_footer();
}

function get_usage_cost($feature, $service_id, $tokentime) {
	if ($service_id != '' && $service_id != 'N/A' && $service_id != '0') {
		$application = db_fetch_cell_prepared("SELECT application
			FROM lic_application_feature_map
			WHERE feature_name=?
			AND service_id =?", array($feature, $service_id));
	} else {
		$application = db_fetch_cell_prepared("SELECT application
			FROM lic_application_feature_map
			WHERE feature_name=?", array($feature));
	}

	if ($application != '') {
		if ($service_id == '' || $service_id == 'N/A' || $service_id == '0') {
			$monthly_cost = db_fetch_cell_prepared('SELECT SUM(monthly_cost)
				FROM lic_application_accounting AS laa
				INNER JOIN (
					SELECT DISTINCT application, feature_name
					FROM lic_application_feature_map
					WHERE application != ""
					AND feature_name = ?
				) AS lafm
				ON laa.application=lafm.application', array($feature));

			$my_tokens = db_fetch_cell_prepared("SELECT SUM(feature_max_licenses)
				FROM lic_services_feature_use AS lsfu
				WHERE lsfu.feature_name=?", array($feature));

			$total_tokens = db_fetch_cell_prepared("SELECT SUM(feature_max_licenses)
				FROM lic_services_feature_use AS lsfu
				INNER JOIN lic_application_feature_map AS lafm
				ON lsfu.feature_name=lafm.feature_name
				WHERE lafm.application=?", array($application));
		} else {
			$monthly_cost = db_fetch_cell_prepared('SELECT monthly_cost
				FROM lic_application_accounting AS laa
				INNER JOIN lic_application_feature_map AS lafm
				ON laa.application=lafm.application
				WHERE lafm.feature_name=?', array($feature));

			$my_tokens = db_fetch_cell_prepared("SELECT feature_max_licenses
				FROM lic_services_feature_use AS lsfu
				WHERE lsfu.feature_name=?
				AND service_id=?", array($feature, $service_id));

			$total_tokens = db_fetch_cell_prepared("SELECT SUM(feature_max_licenses)
				FROM lic_services_feature_use AS lsfu
				INNER JOIN lic_application_feature_map AS lafm
				ON lsfu.feature_name=lafm.feature_name
				WHERE lafm.application=?
				AND lsfu.service_id=?", array($application, $service_id));
		}

		//my_tokens is only valuable for non-equal cost per feature
		$token_seconds_per_month = $total_tokens * 30 * 86400;

		$token_use_cost = $monthly_cost * ($tokentime / $token_seconds_per_month);

		return '$ ' . number_format($token_use_cost, 2);
	}

	return 'N/A';
}

function build_dstat_display_array() {
	$display_text = array();
	$display_text += array('nosort'        => array('display' => __('Actions')));
	$display_text += array('feature'       => array('display' => __('Feature', 'license'), 'sort' => 'ASC'));
	$display_text += array('user'          => array('display' => __('User', 'license'), 'sort' => 'ASC'));
	$display_text += array('host'          => array('display' => __('Host', 'license'), 'sort' => 'ASC'));
	$display_text += array('server_name'   => array('display' => __('Service', 'license'), 'sort' => 'ASC'));
	$display_text += array('server_region' => array('display' => __('Region', 'license'), 'sort' => 'ASC'));
	$display_text += array('nosort1'       => array('display' => __('Cost', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('checkouttime'  => array('display' => __('Token Time', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('utilization'   => array('display' => __('Avg Utilization', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('peak_ut'       => array('display' => __('Peak Utilization', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('interval_end'  => array('display' => __('End Date', 'license'), 'align' => 'right', 'sort' => 'DESC'));

	return $display_text;
}


function format_time($time, $twoline = false) {
	if (!substr_count($time, '0000-00-00')) {
		if ($twoline) {
			return substr($time,0,10) . '<br>' . substr($time,11);
		} else {
			return $time;
		}
	} else {
		return '-';
	}
}

function lic_view_dstats_request_vars($export = false) {
	global $title, $grid_search_types, $lic_rows_selector, $config;
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;

	$filters = array(
			'page' => array(
				'filter' => FILTER_VALIDATE_INT,
				'default' => '1'
				),
			'rows' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'sort_column' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'user',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sort_direction' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'ASC',
				'options' => array('options' => 'sanitize_search_string')
				),
			'service_id' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => (get_request_var('poller_type')==0? '-1':'0')
				),
			'poller_type' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '0',
				),
			'filter' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'couser' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '-1',
				'options' => array('options' => 'addslashes')
				),
			'host' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '-1',
				'options' => array('options' => 'sanitize_search_string')
				),
			'region' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '-1',
				'options' => array('options' => 'sanitize_search_string')
				),
			'feature' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '0',
				'options' => array('options' => 'sanitize_search_string')
				),
			'region' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '-1',
				'options' => array('options' => 'sanitize_search_string')
				),
			'summarize' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => 'true',
				'options' => array('options' => 'sanitize_search_string')
				),
			'keyfeat' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => 'false',
				'options' => array('options' => 'sanitize_search_string')
				)
		);

		if (!$export) {
			if (!isset_request_var('reset')) {
		    	if (check_changed('poller_type', 'sess_ldst_poller_type') && get_request_var('poller_type') != '0') {
					kill_session_var('sess_ldst_service_id');
					kill_session_var('sess_ldst_user');
					kill_session_var('sess_ldst_host');
					kill_session_var('sess_ldst_feature');
					kill_session_var('sess_ldst_region');

					unset_request_var('service_id');
					unset_request_var('couser');
					unset_request_var('host');
					unset_request_var('feature');
					unset_request_var('region');
				}
			}

			/* Force reset 'service_id' to 'All' if 'poller_type' is not 'All' and Pre-'service_id' is 'N/A'. */
			if (isset_request_var('poller_type')) {
				if (!isempty_request_var('poller_type') && get_request_var('service_id')== -1) {
					set_request_var('service_id',0);
				}
			}
		}

		//load_current_session_value('direction', 'sess_ldst_direction', 'close');
		validate_store_request_vars($filters, 'sess_ldst');

		/* set variables for first time use */
		$timespan = rtm_initialize_timespan($lic_timespans, $lic_timeshifts);
		$timeshift = rtm_set_timeshift($lic_timeshifts);

	   	/* process the timespan/timeshift settings */
		rtm_process_html_variables($lic_timespans, $lic_timeshifts);
		rtm_process_user_input($timespan, $timeshift, $lic_timespans);

	   	/* save session variables */
		rtm_finalize_timespan($timespan, $lic_timespans);

		set_request_var('date1',$_SESSION['sess_ldst_current_date1']);
		set_request_var('date2',$_SESSION['sess_ldst_current_date2']);
}
