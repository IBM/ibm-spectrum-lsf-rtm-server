<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/grid/lib/grid_filter_functions.php');
include_once('plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include('./lib/rtm_timespan_settings.php');

$title = __('IBM Spectrum LSF RTM - Daily Stats', 'grid');

if (isset_request_var('export')) {
	grid_view_export_dstat_records();
} elseif (isset_request_var('ajaxstats')) {
	grid_view_dstats_ajax();
} elseif (isset_request_var('action')) {
	switch(get_request_var('action')) {
	case 'ajax_rtm_exec_hosts':
		grid_view_dstats_request_vars();
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_dailystats.php', 'exec_host', $sql_where, array('0' => 'All', '-1' => 'N/A'));
		break;
	case 'ajax_rtm_users':
		grid_view_dstats_request_vars();

		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('grid_dailystats.php', 'job_user', $sql_where, array('0' => __('All', 'grid'), '-1' => __('N/A', 'grid')));
		break;
	case 'ajax_rtm_projects':
		grid_view_dstats_request_vars();

		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('grid_dailystats.php', 'project', $sql_where, array('-2' => __('All', 'grid'), '-1' => __('N/A', 'grid')));
		break;
	case 'ajax_rtm_queues':
		grid_view_dstats_request_vars();

		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('grid_dailystats.php', 'queue', $sql_where, array('0' => __('All', 'grid'), '-1' => __('N/A', 'grid')));
		break;
	case 'ajax_rtm_apps':
		grid_view_dstats_request_vars();

		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('grid_dailystats.php', 'app', $sql_where, array('0' => __('All', 'grid'), '-1' => __('N/A', 'grid')));
		break;
	}
} else {
	grid_view_dstats();
}

function grid_view_get_dstat_records(&$sql_where, &$group_by, &$table_name, $apply_limits = true, $rows = '30', &$total_rows = 0) {
	global $grid_efficiency_sql_ranges;
	$sql_params = array();

	/* timespan sql where */
	$db_maint_time = date('H:i:s', strtotime(read_config_option('grid_db_maint_time')));
	if ($_SESSION['sess_grid_view_dstat_current_timespan'] == '-1' ||
			($_SESSION['sess_grid_view_dstat_current_timespan'] == '0' && date('Y-m-d') == get_request_var('date1') && date('Y-m-d', time()+86400) == get_request_var('date2'))) {
		$sql_where = " AND interval_start>=? AND interval_end<=?";
		$sql_params[] = get_request_var('date1') . " $db_maint_time";
		$sql_params[] = get_request_var('date2') . " $db_maint_time";
		$table_name = 'grid_job_interval_stats';
	} else {
		$sql_where = " AND interval_start>=? AND interval_end<=?";
		$sql_params[] = get_request_var('date1') . " $db_maint_time";
		$sql_params[] = get_request_var('date2') . " $db_maint_time";
		$table_name = 'grid_job_daily_stats';
	}
	$jobs_col = 'jobs_in_state';

	if (isset_request_var('summarize') && ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on'))) {
		$interval = "'N/A' as interval_start,\n 'N/A' as interval_end";
		$group_by = '';
	} else {
		$interval = "interval_start, interval_end";
		$group_by = 'interval_end';
	}

	/* clusterid sql where */
	if (get_request_var('clusterid') == '-1') {
		$cluster_name = "'N/A' AS clustername";
		/* set clusterid=0 for fake "group by" and 0 is for match the clusterid when drilldown to Detail page */
		$clusterid = "'0' AS clusterid";
	} else if (get_request_var('clusterid') == '0') {
		$cluster_name = "'TBD' AS clustername";
		$clusterid = 'clusterid';
		$group_by    .= (strlen($group_by) ? ',' : '') . ' clusterid';
	} else {
		$cluster_name = "'TBD' AS clustername";
		$clusterid = 'clusterid';
		$group_by    .= (strlen($group_by) ? ',' : '') . ' clusterid';
		$sql_where   .= ' AND (clusterid=?)';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	/* project sql where */
	/* Workaround for #196086 'All' is 0 in gridcstat.php and '-2' in this page; */
	if (get_request_var('project') == '0') {
		set_request_var('project', '-2');
	}
	if (get_request_var('project') == '-1') {
		$project    = "'N/A' AS projectName";
	} else if (get_request_var('project') == '-2') {
		$project    = 'projectName';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' projectName';
	} else {
		$project    = 'projectName';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' projectName';
		$sql_where .= ' AND (projectName=?)';
		$sql_params[] = get_request_var('project');
	}

	/* stat sql where */
	if (get_request_var('stat') == '-1') {
		$stat       = "'N/A' AS stat";
	} else if (get_request_var('stat') == '0') {
		$stat       = 'stat';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' stat';
	} else {
		$stat       = 'stat';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' stat';
		$sql_where .= ' AND (stat=?)';
		$sql_params[] = get_request_var('stat');
	}

	/* user sql where */
	if (get_request_var('job_user') == '-1') {
		$user       = "'N/A' AS user";
	} else if (get_request_var('job_user') == '0') {
		$user       = 'user';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' user';
	} else {
		$user       = 'user';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' user';
		$sql_where .= ' AND (user=?)';
		$sql_params[] = get_request_var('job_user');
	}

	/* exec_host sql where */
	if (get_request_var('exec_host') == '-1') {
		$exec_host  = "'N/A' AS exec_host";
	} else if (get_request_var('exec_host') == '0') {
		set_request_var('exec_host', '0');
		$exec_host  = 'exec_host';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' exec_host';
	} else {
		$exec_host  = 'exec_host';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' exec_host';
		$sql_where .= ' AND (exec_host=?)';
		$sql_params[] = get_request_var('exec_host');
	}

	/* queue sql where */
	if (get_request_var('queue') == '-1') {
		$queue      = "'N/A' AS queue";
	} else if (get_request_var('queue') == '0') {
		$queue      = 'queue';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' queue';
	} else {
		$queue      = 'queue';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' queue';
		$sql_where .= ' AND (queue=?)';
		$sql_params[] = get_request_var('queue');
	}

	/* queue sql where */
	if (get_request_var('app') == '-1') {
		$app      = "'N/A' AS app";
	} else if (get_request_var('app') == '0') {
		$app      = 'app';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' app';
	} else {
		$app      = 'app';
		$group_by  .= (strlen($group_by) ? ',' : '') . ' app';
		$sql_where .= ' AND (app=?)';
		$sql_params[] = get_request_var('app');
	}

	/* search filter sql where */
	if (strlen(get_request_var('filter'))) {
		$nwhere = '';
		if (get_request_var('project') == -2) {
			$nwhere .= (strlen($nwhere) ? ' OR ':' AND (') . "(projectName REGEXP ?)";
			$sql_params[] = get_request_var('filter');
		}

		if (get_request_var('queue') == 0) {
			$nwhere .= (strlen($nwhere) ? ' OR ':' AND (') . "(queue REGEXP ?)";
			$sql_params[] = get_request_var('filter');
		}

		if (get_request_var('app') == 0) {
			$nwhere .= (strlen($nwhere) ? ' OR ':' AND (') . "(app REGEXP ?)";
			$sql_params[] = get_request_var('filter');
		}

		if (get_request_var('job_user') == 0) {
			$nwhere .= (strlen($nwhere) ? ' OR ':' AND (') . "(user REGEXP ?)";
			$sql_params[] = get_request_var('filter');
		}

		if (get_request_var('exec_host') == 0) {
			$nwhere .= (strlen($nwhere) ? ' OR ':' AND (') . "(exec_host REGEXP ?)";
			$sql_params[] = get_request_var('filter');
		}

		if (strlen($nwhere)) {
			$sql_where .= $nwhere . ')';
		}
	}

	if (strlen($group_by)) {
		$group_by = 'GROUP BY ' . $group_by;
	}

	/* efficiency sql having */
	$sql_having='';
	if (get_request_var('efficiency') == '-1') {
		/* Show all items */
	} else {
		$sql_having = ' HAVING (' . str_replace('efficiency', 'avg_cpu_effic', $grid_efficiency_sql_ranges[get_request_var('efficiency')]) . ')';
	}

	$sql_order = get_order_string();

	if ($table_name =='grid_job_interval_stats') {
		$table_name_part    = '';
		$table_name_union   = '';
		$app_name           = '';
		$app_groupby        = '';
		$from_hosts         = '';
		$from_hosts_groupby = '';
		$project_names      = '';
		$project_groupby    = '';
		$wallt_method_done  = '';
		$wallt_method_exit  = '';

		get_daily_stats_parameters(	$table_name_part, $table_name_union,
									$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
									$project_names, $project_groupby, $wallt_method_done,
									$wallt_method_exit);


		/**
		 * ToDo: Keep '$gpu_wallt_method_excl' generation for patch/hotfix. It should be moved into function#get_daily_stats_parameters during FixPack release
		 */
		if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
			if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
				$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), ((CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed), 0))";
			} else {
				$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN (gpu_exec_time-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed) ELSE 0 END)";
			}
		} else {
			if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
				$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), (CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)*CAST(num_gpus AS signed), 0))";
			} else {
				$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN gpu_exec_time*CAST(num_gpus AS signed) ELSE 0 END)";
			}
		}

		$sql_query = "SELECT $cluster_name, $clusterid, $user, $queue, $app, $project, $stat,
			$exec_host, SUM($jobs_col) as total_jobs, SUM(jobs_wall_time) as wall_time, SUM(gpu_wall_time) as gpu_wall_time,
			SUM(jobs_stime) as system_time, SUM(jobs_utime) as user_time,
			(SUM(jobs_stime)+SUM(jobs_utime))/SUM(jobs_wall_time)*100 as avg_cpu_effic,
			SUM(slots_in_state) as total_slots, SUM(gpus_in_state) as total_gpus, $interval
			FROM (
				SELECT
				$table_name_part.clusterid, $table_name_part.user,
				(CASE WHEN stat = 'EXIT' THEN 'EXITED' ELSE 'ENDED' END) as stat,
				$table_name_part.queue, $app_name, $from_hosts, $table_name_part.exec_host, $project_names AS projectName,
				SUM($table_name_part.job_count) AS jobs_in_state,
				(CASE WHEN stat = 'EXIT' THEN $wallt_method_exit ELSE $wallt_method_done END) AS jobs_wall_time,
				$gpu_wallt_method_excl AS gpu_wall_time,
				SUM(stime) AS jobs_stime, SUM(utime) AS jobs_utime, SUM(num_cpus) AS slots_in_state,
				SUM($table_name_part.num_gpus) AS gpus_in_state,
				DATE_ADD(CURDATE(), INTERVAL 0 HOUR) AS interval_start,
				DATE_ADD(CURDATE(), INTERVAL 24 HOUR) AS interval_end,
				DATE_ADD(CURDATE(), INTERVAL 0 HOUR) AS date_recorded
				FROM  $table_name_union AS $table_name_part
				WHERE (($table_name_part.end_time>=DATE_ADD(CURDATE(), INTERVAL 0 HOUR)) AND
				($table_name_part.end_time<=DATE_ADD(CURDATE(), INTERVAL 24 HOUR)) AND stat IN('DONE', 'EXIT'))
				GROUP BY $table_name_part.clusterid,
				$table_name_part.user,
				$table_name_part.queue,
				$app_groupby
				$table_name_part.exec_host,
				$table_name_part.stat
				$from_hosts_groupby
				$project_groupby
			) AS today
			WHERE stat IN('ENDED', 'EXITED')
			$sql_where
			$group_by";
			$count_sql_query = $sql_query;
	} else {
		if ($table_name == 'grid_job_daily_stats' && read_config_option('grid_partitioning_enable')) {
			$sql_query = "SELECT $cluster_name, $clusterid, $user, $queue, $app, $project, $stat,
				$exec_host, SUM($jobs_col) as total_jobs, SUM(jobs_wall_time) as wall_time, SUM(gpu_wall_time) as gpu_wall_time,
				SUM(jobs_stime) as system_time, SUM(jobs_utime) as user_time,
				SUM(slots_in_state) as total_slots, SUM(gpus_in_state) as total_gpus, $interval
				FROM $table_name
				WHERE stat IN('ENDED', 'EXITED')
				$sql_where
				$group_by";

			$count_select = str_replace('GROUP BY', '', $group_by);
			if(strstr($count_select, 'interval_end') === false){
				$count_select  .= (strlen(trim($count_select)) ? ',' : '') . ' interval_end';
			}
			if(!empty($sql_having)){
				$count_select2 = $count_select;
				$count_select  .= (strlen(trim($count_select)) ? ',' : '') . ' SUM(jobs_wall_time) as wall_time, SUM(jobs_stime) as system_time, SUM(jobs_utime) as user_time';
			}
			$count_sql_query = "SELECT $count_select
				FROM $table_name
				WHERE stat IN('ENDED', 'EXITED')
				$sql_where
				$group_by";

			$ws_abrev = get_request_var('date1');
			$we_abrev = get_request_var('date2');
			$union_tables = partition_get_partitions_for_query('grid_job_daily_stats', $ws_abrev, $we_abrev);

			if (cacti_sizeof($union_tables)) {
				$union_query = '';
				$count_union_query = '';
				$sql_params_union = array();
				foreach ($union_tables as $table) {
					if (strlen($union_query)) {
						$union_query .= ' UNION ALL ';
						$count_union_query .= ' UNION ALL ';
					}
					$union_query .= str_replace($table_name, $table, $sql_query);
					$count_union_query .= str_replace($table_name, $table, $count_sql_query);
					$sql_params_union = array_merge($sql_params_union, $sql_params);
				}
				$sql_params = $sql_params_union;
			} else {
				$total_rows = 0;
				return array();
			}

			$sql_query = "SELECT $cluster_name, $clusterid, $user, $queue, $app, $project, $stat,
				$exec_host, SUM(total_jobs) AS total_jobs, SUM(wall_time) as wall_time, SUM(gpu_wall_time) as gpu_wall_time,
				SUM(system_time) as system_time, SUM(user_time) as user_time,
				(SUM(system_time)+SUM(user_time))/SUM(wall_time)*100 as avg_cpu_effic,
				SUM(total_slots) as total_slots, SUM(total_gpus) as total_gpus,
				$interval
				FROM ($union_query) AS stats $group_by";
			if(!empty($sql_having)){
				$count_sql_query = "SELECT $count_select2,
				SUM(wall_time) as wall_time,
				SUM(system_time) as system_time, SUM(user_time) as user_time,
				(SUM(system_time)+SUM(user_time))/SUM(wall_time)*100 as avg_cpu_effic
				FROM ($count_union_query) AS stats $group_by";
			} else {
				$count_sql_query = "SELECT $count_select FROM ($count_union_query) AS stats $group_by";
			}
		} else {
			$sql_query = "SELECT $cluster_name, $clusterid, $user, $queue, $app, $project, $stat,
				$exec_host, SUM($jobs_col) as total_jobs, SUM(jobs_wall_time) as wall_time, SUM(gpu_wall_time) as gpu_wall_time,
				SUM(jobs_stime) as system_time, SUM(jobs_utime) as user_time,
				(SUM(jobs_stime)+SUM(jobs_utime))/SUM(jobs_wall_time)*100 as avg_cpu_effic,
				SUM(slots_in_state) as total_slots, SUM(gpus_in_state) as total_gpus, $interval
				FROM $table_name
				WHERE stat IN('ENDED', 'EXITED')
				$sql_where
				$group_by";

			$count_select = str_replace('GROUP BY', '', $group_by);
			if(!empty($sql_having)){
				$count_sql_query = "SELECT $count_select,
				(SUM(jobs_stime)+SUM(jobs_utime))/SUM(jobs_wall_time)*100 as avg_cpu_effic
				FROM $table_name
				WHERE stat IN('ENDED', 'EXITED')
				$sql_where
				$group_by";
			} else {
				$count_sql_query = "SELECT $count_select
				FROM $table_name
				WHERE stat IN('ENDED', 'EXITED')
				$sql_where
				$group_by";
			}

		}
	}

	$sql_query .= $sql_having;
	$count_sql_query .= $sql_having;
	//cacti_log('DEBUG: ' . str_replace("\n", ' ', $sql_query));
	if (grid_validate_filter_var(get_request_var('filter'))) {
		if (strlen($group_by)) {
			$total_rows = db_fetch_cell_prepared("SELECT COUNT(*) FROM ($count_sql_query) AS q", $sql_params);
		} else {
			$total_rows = 1;
		}
		$sql_query .= ' '. $sql_order;
		if ($apply_limits) {
			$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		}
		return db_fetch_assoc_prepared($sql_query, $sql_params);
	} else {
		$total_rows = 0;
		return array();
	}
}

function grid_view_export_dstat_records() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	grid_view_dstats_request_vars();

	set_request_var('page','1');

	$sql_where  = '';
	$group_by   = '';
	$table_name = '';
	$total_rows = 0;

	$stats = grid_view_get_dstat_records($sql_where, $group_by, $table_name, true, read_config_option('grid_xport_rows'), $total_rows);

	$xport_array = array();

	array_push($xport_array, '"clustername","user","queue","app",' .
		'"projectName","exec_host","result","total_jobs",' .
		'"total_slots","total_gpus","wall_time","gpu_wall_time","system_time","user_time",' .
		'"core_eff","interval_start","interval_end"');

	if (cacti_sizeof($stats)) {
		foreach($stats as $stat) {
			if (get_request_var('clusterid') == '-1') {
				$clustername = 'N/A';
			} else {
				$clustername = grid_get_clustername($stat['clusterid']);
			}

			array_push($xport_array,'"' .
			$clustername                         . '","' . $stat['user']                      . '","' .
			$stat['queue']                       . '","' . $stat['app']                       . '","' . $stat['projectName'] . '","' .
			$stat['exec_host']                   . '","' . $stat['stat']                      . '","' .
			$stat['total_jobs']                  . '","' . $stat['total_slots']               . '","' . $stat['total_gpus']  . '","' .
			$stat['wall_time']                   . '","' . $stat['gpu_wall_time']             . '","' . $stat['system_time'] . '","' .
			$stat['user_time']                   . '","' . $stat["avg_cpu_effic"]             . '","' .
			substr($stat['interval_start'],0,10) . '","' . substr($stat['interval_end'],0,10) . '"'
			);
		}
	}

	header('Content-type: application/csv');
	header('Cache-Control: max-age=15');
	header('Content-Disposition: attachment; filename=grid_dstats_xport.csv');
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}


function is_good_range($range_var, $min_start) {
	/* timespan sql where */
	if (substr_count($range_var, 'day')) {
		$group_function = 'DAY';
		$span = substr($range_var, 0, 1) - 5;
		if ($span == 0) {
			$span = 2;
			$group_function = 'DAY';
		}
	} elseif (substr_count($range_var, 'week')) {
		$group_function = 'WEEK';
		$span = substr($range_var, 0, 1) - 1;
		if ($span == 0) {
			$span = 5;
			$group_function = 'DAY';
		}
	} elseif (substr_count($range_var, 'month')) {
		$group_function = 'MONTH';
		$span = substr($range_var, 0, 1) - 1;
		if ($span == 0) {
			$span = 3;
			$group_function = 'WEEK';
		}
	} elseif (substr_count($range_var, 'quarter')) {
		$group_function = 'QUARTER';
		$span = substr($range_var, 0, 1) - 1;
		if ($span == 0) {
			$span = 2;
			$group_function = 'MONTH';
		}
	} elseif (substr_count($range_var, 'year')) {
		$group_function = 'YEAR';
		$span = substr($range_var, 0, 1) - 1;
		if ($span == 0) {
			$span = 3;
			$group_function = 'QUARTER';
		}
	}
	return db_fetch_cell_prepared("SELECT (CURDATE() - INTERVAL ?) >= ? AS test", array("$span $group_function", $min_start));
}

function dailyStatsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;
	global $grid_efficiency_display_ranges;
	global $grid_time_range;

	//if (strlen(get_request_var('filter'))) {
	//	if (db_fetch_cell("SELECT COUNT(*) FROM grid_hosts WHERE host LIKE '%" . get_request_var('filter') . "%'")) {
	//		get_request_var('exec_host') = "0";
	//	}
	//}
	if (read_config_option('grid_partitioning_enable') == '') {
		$min_start    = db_fetch_cell('SELECT MIN(interval_start) FROM grid_job_daily_stats');
	} else {
		$min_start     = db_fetch_cell("SELECT MIN(min_time) FROM grid_table_partitions WHERE table_name='grid_job_daily_stats'");

		/* no partitions are created yet */
		if (strlen($min_start) == 0) {
			$min_start    = db_fetch_cell('SELECT MIN(interval_start) FROM grid_job_daily_stats');
		}
	}
	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>

			date1='<?php print $_SESSION['sess_grid_view_dstat_current_date1'];?>'
			date2='<?php print $_SESSION['sess_grid_view_dstat_current_date2'];?>'

			function stopRKey(evt) {
				var evt  = (evt) ? evt : ((event) ? event : null);
				var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
				if ((evt.keyCode == 13) && (node.type=='text')) { return false; }
			}
			document.onkeypress = stopRKey;

			function applyFilterChangePDTS() {
				strURL = 'grid_dailystats.php?ajaxstats=1&predefined_timespan=' + $('#predefined_timespan').val();
				strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();
				$('#status').show();
				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();
					applySkin();
					applySkinRTM();
				});
			}

			function moveRight() {
				strURL = 'grid_dailystats.php?ajaxstats=1&move_right_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				$('#status').show();
				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();
					applySkin();
					applySkinRTM();
				});
			}

			function moveLeft() {
				strURL = 'grid_dailystats.php?ajaxstats=1&move_left_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				$('#status').show();
				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();
					applySkin();
					applySkinRTM();
				});
			}

			function applyFilter() {
				strURL = 'grid_dailystats.php?ajaxstats=1&clusterid=' + $('#clusterid').val();
				strURL = strURL + '&stat=' + $('#stat').val();
				strURL = strURL + '&rows=' + $('#rows').val();
				strURL = strURL + '&job_user=' + $('#job_user').val();
				strURL = strURL + '&queue=' + $('#queue').val();
				strURL = strURL + '&app=' + $('#app').val();
				strURL = strURL + '&efficiency=' + $('#efficiency').val();
				strURL = strURL + '&project=' + $('#project').val();
				strURL = strURL + '&exec_host=' + $('#exec_host').val();
				strURL = strURL + '&units=' + $('#units').val();
				strURL = strURL + '&filter=' + base64_encode($('#filter').val());
				if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
					strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
				} else {
					strURL = strURL + '&date1=' + $('#date1').val();
					strURL = strURL + '&date2=' + $('#date2').val();
				}
				strURL = strURL + '&summarize=' + $('#summarize').is(':checked');
				strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();
				$('#status').show();
				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();
					applySkin();
					applySkinRTM();
				});
			}

			function clearFilterChange() {
				strURL = 'grid_dailystats.php?ajaxstats=1&clear=1';
				$('#status').show();
				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();
					applySkin();
					applySkinRTM();
				});
			}

			function exportToCSV(objForm) {
				strURL = 'grid_dailystats.php?export=1&clusterid=' + $('#clusterid').val();
				strURL = strURL + '&stat=' + $('#stat').val();
				strURL = strURL + '&rows=' + $('#rows').val();
				strURL = strURL + '&job_user=' + $('#job_user').val();
				strURL = strURL + '&queue=' + $('#queue').val();
				strURL = strURL + '&app=' + $('#app').val();
				strURL = strURL + '&efficiency=' + $('#efficiency').val();
				strURL = strURL + '&project=' + $('#project').val();
				strURL = strURL + '&exec_host=' + $('#exec_host').val();
				strURL = strURL + '&filter=' + $('#filter').val();
				if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
					strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
				} else {
					strURL = strURL + '&date1=' + $('#date1').val();
					strURL = strURL + '&date2=' + $('#date2').val();
				}

				strURL = strURL + '&summarize=' + $('#summarize').is(':checked');
				strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();
				$('#status').show();
				document.location = strURL;
				Pace.stop();
				$('#status').hide();
			}

			$('#form_grid').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});

			$(function() {
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
					dateFormat: 'yy-mm-dd'
				});

				$('#date2').datepicker({
					dateFormat: 'yy-mm-dd'
				});

				$('#move_left').click(function() {
					moveLeft();
				});

				$('#move_right').click(function() {
					moveRight();
				});

				message = '';
				if ($('#project').val() == -2) {
					message = ' [ <?php print __esc('Searching from the Project field', 'grid');?>';
				}

				if ($('#queue').val() == 0) {
					message += (message.length > 0 ? '<?php print __(', and the Queue field', 'grid');?>':'<?php print __(' [ Searching from the Queue field', 'grid');?>');
				}

				if ($('#job_user').val() == 0) {
					message += (message.length > 0 ? '<?php print __(', and the User field', 'grid');?>':'<?php print __(' [ Searching from the User field', 'grid');?>');
				}

				if ($('#exec_host').val() == 0) {
					message += (message.length > 0 ? '<?php print __(', and the Exec Hosts field', 'grid');?>':'<?php print __(' [ Searching from the Exec Hosts field', 'grid');?>');
				}

				if (message != '') {
					message += ' ] ';
				} else {
					message = ' [ <?php print __('Select &apos;All&apos; for User, Queue, Application, Project, or Exec Host fields to make them searchable', 'grid');?> ] ';
				}
				$('#message').html(message);

				$('#clear').click(function() {
					clearFilterChange();
				});

				$('#clusterid, #job_user, #queue, #app, #efficiency, #rows, #summarize, #stat, #project, #exec_host, #units, #filter').change(function() {
					applyFilter();
				});

				$('#main').show();
			});

			</script>
			<form id='form_grid' action='grid_dailystats.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>All</option>
							<option value='-1'<?php if (get_request_var('clusterid') == '-1') {?> selected<?php }?>>N/A</option>
							<?php
							$clusters = grid_get_clusterlist();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_dailystats.php', __('User', 'grid'), 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('N/A', 'grid'), '0' => __('All', 'grid')));?>
					<?php print html_autocomplete_filter('grid_dailystats.php', __('Queue', 'grid'), 'queue', get_request_var('queue'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('N/A', 'grid'), '0' => __('All', 'grid')));?>
					<td>
						<?php print __('Eff', 'grid');?>
					</td>
					<td>
						<select id='efficiency'>
							<option value='-1'<?php if (get_request_var('efficiency') == '-1') {?> selected<?php }?>><?php print __('All');?></option>
							<?php
							if (cacti_sizeof($grid_efficiency_display_ranges)) {
								foreach ($grid_efficiency_display_ranges as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('efficiency') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'grid');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print 'selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters');?>'>
							<input type='button' id='export' value='<?php print __esc('Export', 'grid');?>' title='<?php print __('Export to CSV', 'grid');?>' onClick='exportToCSV()'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Stat', 'grid');?>
					</td>
					<td>
						<select id='stat'>
							<option value='0' <?php if (get_request_var('stat') == '0') { print 'selected'; }?>><?php print __('All', 'grid');?></option>
							<option value='-1' <?php if (get_request_var('stat') == '-1') { print 'selected'; }?>><?php print __('N/A', 'grid');?></option>
							<option value='ENDED' <?php if (get_request_var('stat') == 'ENDED') { print 'selected'; }?>><?php print __('Ended', 'grid');?></option>
							<option value='EXITED' <?php if (get_request_var('stat') == 'EXITED') { print 'selected'; }?>><?php print __('Exited', 'grid');?></option>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_dailystats.php', __('Project', 'grid'), 'project', get_request_var('project'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('N/A', 'grid'), '-2' => __('All', 'grid')));?>
					<?php print html_autocomplete_filter('grid_dailystats.php', __('Host', 'grid'), 'exec_host', get_request_var('exec_host'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('N/A', 'grid'), '0' => __('All', 'grid')));?>
					<?php print html_autocomplete_filter('grid_dailystats.php', __('Apps', 'grid'), 'app', get_request_var('app'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('N/A', 'grid'), '0' => __('All', 'grid')));?>
                    <td>
						<?php print __('Unit', 'grid');?>
                    </td>
                    <td>
                        <select id='units'>
                            <option value='auto' <?php print (get_request_var('units') == 'auto' ? 'selected':'');?>><?php print __('Auto', 'grid');?></option>
                            <option value='minutes' <?php print (get_request_var('units') == 'minutes' ? 'selected':'');?>><?php print __('Minutes', 'grid');?></option>
                            <option value='hours' <?php print (get_request_var('units') == 'hours' ? 'selected':'');?>><?php print __('Hours', 'grid');?></option>
                            <option value='days' <?php print (get_request_var('units') == 'days' ? 'selected':'');?>><?php print __('Days', 'grid');?></option>
                            <option value='weeks' <?php print (get_request_var('units') == 'weeks' ? 'selected':'');?>><?php print __('Weeks', 'grid');?></option>
                            <option value='months' <?php print (get_request_var('units') == 'months' ? 'selected':'');?>><?php print __('Months', 'grid');?></option>
                        </select>
                    </td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Presets', 'grid');?>
					</td>
					<td>
						<select id='predefined_timespan' onChange='applyFilterChangePDTS()'>
							<?php
							if ($_SESSION['sess_grid_view_dstat_custom']) {
								$grid_timespans[GT_CUSTOM] = 'Custom';
								$start_val = 0;
								$end_val = cacti_sizeof($grid_timespans);
							} else {
								if (isset($grid_timespans[GT_CUSTOM])) {
									asort($grid_timespans);
									array_shift($grid_timespans);
								}
								$start_val = 1;
								$end_val = cacti_sizeof($grid_timespans)+1;
							}

							if (cacti_sizeof($grid_timespans) > 0) {
								if ($start_val == 0) {
									print "<option value='0'"; if ($_SESSION['sess_grid_view_dstat_current_timespan'] == '0') { print ' selected'; } print '>' . __('Custom', 'grid') . '</option>';
								}
								print "<option value='-1'"; if ($_SESSION['sess_grid_view_dstat_current_timespan'] == '-1') { print ' selected'; } print '>' . __('Today', 'grid') . '</option>';

								for ($value=$start_val; $value < $end_val; $value++) {
									if ($value > 7 && $value!=GT_DAY_SHIFT && $value!=GT_THIS_DAY && $value!=GT_PREV_DAY) {
										print "<option value='" . $value . "'"; if ($_SESSION['sess_grid_view_dstat_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
									} elseif ($value == 7) {
										print "<option value='" . $value . "'"; if ($_SESSION['sess_grid_view_dstat_current_timespan'] == $value) { print ' selected'; } print '>' . __('Yesterday', 'grid') . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='10' value='<?php print (isset($_SESSION['sess_grid_view_dstat_current_date1']) ? $_SESSION['sess_grid_view_dstat_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'grid');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='10' value='<?php print (isset($_SESSION['sess_grid_view_dstat_current_date2']) ? $_SESSION['sess_grid_view_dstat_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector', 'grid');?>'></i>
						</span>
					</td>
					<td>
						<span>
						<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward', 'grid');?>'></i>
						<select id='predefined_timeshift'>
							<?php
							$start_val = 1;
							$end_val = cacti_sizeof($grid_timeshifts)+1;

							if (cacti_sizeof($grid_timeshifts)) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									if ($shift_value >= 7) {
										print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . title_trim($grid_timeshifts[$shift_value], 40) . '</option>';
									}
								}
							}
							?>
						</select>
						<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward', 'grid');?>'></i>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input id='filter' size='30' type='text' id='filter' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<input type='checkbox' id='summarize'<?php if ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on')) print ' checked="true"';?>>
					</td>
					<td>
						<label for='summarize' style='vertical-align:30%;'><?php print __('Summarize', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='1'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_view_dstats_request_vars() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_VALIDATE_IS_REGEX,
			'pageset' => true,
			'default' => ''
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'user',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'summarize' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => 'true'
			),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'app' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'stat' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'exec_host' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'units' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => 'auto'
			),
		'efficiency' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
	);

	validate_store_request_vars($filters, 'sess_grid_view_dstat');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	/* set variables for first time use */
	$timespan = rtm_initialize_timespan($grid_timespans, $grid_timeshifts, 'sess_grid_view_dstat', 'read_grid_config_option');
	$timeshift = rtm_set_timeshift($grid_timeshifts, 'sess_grid_view_dstat', 'read_grid_config_option');

   	/* process the timespan/timeshift settings */
	rtm_process_html_variables($grid_timespans, $grid_timeshifts, 'sess_grid_view_dstat', 'read_grid_config_option');
	rtm_process_user_input($timespan, $timeshift, $grid_timespans, 'sess_grid_view_dstat', 'read_grid_config_option');

   	/* save session variables */
	rtm_finalize_timespan($timespan, $grid_timespans, 'sess_grid_view_dstat', 'read_grid_config_option');

	set_request_var('date1', $_SESSION['sess_grid_view_dstat_current_date1']);
	set_request_var('date2', $_SESSION['sess_grid_view_dstat_current_date2']);
}

function grid_view_dstats() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	grid_view_dstats_request_vars();

	general_header();

	print "<div id='stats_content'>";

	html_start_box(__('Daily Statistics Filters', 'grid') . "</span><span id='message'></span>&nbsp;<div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div>", '100%', '', '3', 'center', '');
	dailyStatsFilter();
	html_end_box();

	print "</div>";

	?>
	<script type='text/javascript'>
	$(function() {
		$('#main').show();
		$.get('grid_dailystats.php?ajaxstats=1', function(data) {
			$('#stats_content').html(data);
			$('#status').hide();
			applySkin();
			applySkinRTM();
		});
	});
	</script>
	<?php

	bottom_footer();
}

function grid_view_dstats_ajax() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	grid_view_dstats_request_vars();

	$sql_where  = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$group_by   = '';
	$table_name = '';
	$total_rows = 0;

	$stats = grid_view_get_dstat_records($sql_where, $group_by, $table_name, true, $rows, $total_rows);

	$units = '';
	if (get_request_var('units') != 'auto') {
		$units = htmlspecialchars(' (Time in ' . ucfirst(get_request_var('units')) . ')');
	}

	html_start_box(__('Daily Statistics Filters', 'grid') . "</span><span id='message'></span>&nbsp;<div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div>", '100%', '', '3', 'center', '');
	dailyStatsFilter();
	html_end_box();

	html_start_box('', '100%', '', '3', 'center', '');

	$rows_query_string = "SELECT COUNT(interval_end)
		FROM $table_name
		WHERE stat IN('ENDED', 'EXITED')
		$sql_where
		$group_by";

	//print $rows_query_string;

	$display_text = build_dstat_display_array();

	/* generate page list */
	$nav = html_nav_bar('grid_dailystats.php?ajaxstats=1', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Records'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;

	if (cacti_sizeof($stats)) {
		foreach ($stats as $stat) {
			if (get_request_var('clusterid') == '-1') {
				$clustername = 'N/A';
			} else {
				$clustername = grid_get_clustername($stat['clusterid']);
			}

			form_alternate_row(); $i++;

			/* get query values */
			$query_string = '?reset=1&action=viewlist';
			if ($stat['clusterid'] != 'N/A')   $query_string .= '&clusterid=' . $stat['clusterid'];
			if ($stat['user'] != 'N/A')        $query_string .= '&job_user='      . $stat['user'];
			if ($stat['queue'] != 'N/A')       $query_string .= '&queue='     . $stat['queue'];
			if ($stat['app'] != 'N/A')         $query_string .= '&app='     . $stat['app'];
			if ($stat['exec_host'] != 'N/A')   $query_string .= '&exec_host=' . $stat['exec_host'];
			if ($stat['projectName'] != 'N/A') $query_string .= '&project='   . $stat['projectName'];

			if ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on')) {
				$query_string .= '&predefined_timespan=0&date1=' . urlencode(get_request_var('date1') . ' 00:00');
				$query_string .= '&date2=' . urlencode(get_request_var('date2') . ' 00:00');
			} else {
				$query_string .= '&predefined_timespan=0&date1=' . urlencode($stat['interval_start']);
				$query_string .= '&date2=' . urlencode($stat['interval_end']);
			}

			if ($stat['stat'] == 'N/A') {
				$squery_string = '&status=FINISHED';
			} elseif ($stat['stat'] == 'ENDED') {
				$squery_string = '&status=DONE';
			} else {
				$squery_string = '&status=EXIT';
			}

			$url = "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' . $query_string . '&status=ACTIVE') . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_jobs.gif' alt='' title='" . __esc('View Active Jobs', 'grid') . "'></a>";

			$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' . $query_string . $squery_string) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_jobs_finished.gif' alt='' title='" . __esc('View Finished Jobs', 'grid') . "'></a>";

			form_selectable_cell($url, $i, '10');

			if (get_request_var('clusterid') != '-1') {
				form_selectable_cell(display_column($clustername), $i);
			}
			if (get_request_var('job_user') != '-1') {
				form_selectable_cell(filter_value(display_column($stat['user']), get_request_var('filter')), $i);
			}
			if (get_request_var('queue') != '-1') {
				form_selectable_cell(filter_value(display_column($stat['queue']), get_request_var('filter')), $i);
			}
			if (get_request_var('app') != '-1') {
				form_selectable_cell(filter_value(display_column($stat['app']), get_request_var('filter')), $i);
			}
			if (get_request_var('project') != '-1') {
				form_selectable_cell(filter_value(display_column($stat['projectName']), get_request_var('filter')), $i);
			}
			if (get_request_var('exec_host') != '-1') {
				form_selectable_cell(filter_value(display_column($stat['exec_host']), get_request_var('filter')), $i);
			}
			if (get_request_var('stat') != '-1') {
				form_selectable_cell(display_column($stat['stat'] == 'ENDED' ? __('DONE', 'grid') : ($stat['stat'] == 'EXITED' ? __('EXIT', 'grid') : $stat['stat'])), $i);
			}

			form_selectable_cell(number_format_i18n($stat['total_jobs']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($stat['total_slots']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($stat['total_gpus']), $i, '', 'right');

			form_selectable_cell(dstat_display_time($stat['wall_time'], get_request_var('units')), $i, '', 'right');
			form_selectable_cell(dstat_display_time($stat['gpu_wall_time'], get_request_var('units')), $i, '', 'right');
			form_selectable_cell(dstat_display_time($stat['system_time'], get_request_var('units')), $i, '', 'right');
			form_selectable_cell(dstat_display_time($stat['user_time'], get_request_var('units')), $i, '', 'right');
			form_selectable_cell(display_job_effic($stat['avg_cpu_effic'], $stat['wall_time'], 2), $i, '', 'right');

			if (!isset_request_var('summarize') || ((get_request_var('summarize') != 'true') && (get_request_var('summarize') != 'on'))) {
				form_selectable_cell(substr($stat['interval_start'], 0, 10), $i, '', 'right');
				form_selectable_cell(substr($stat['interval_end'], 0, 10), $i, '', 'right');
			}

			form_end_row();
		}

		html_end_box(false);

		print $nav;
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Daily Stat Records Found', 'grid') . '</em></td></tr>';

		html_end_box(false);
	}
}

function display_column($value) {
	if ($value == 'N/A') {
		return __('N/A');
	} else {
		return $value;
	}
}

function dstat_display_time($value, $units = 'auto') {
	if ($value < 0) {
		return '-';
	} elseif (($value < 3600 && $units == 'auto') || $units == 'minutes') {
		return ($units == 'auto' ? __('%s mins', number_format_i18n(round($value/60)), 'grid'):($value > 1024 ? number_format_i18n(round($value/60,2),2) : number_format_i18n(round($value/60))));
	} elseif (($value < 86400 && $units == 'auto') || $units == 'hours') {
		return ($units == 'auto' ? __('%s hrs', number_format_i18n(round($value/3600,2),2), 'grid') : number_format_i18n(round($value/3600,2),2));
	} elseif (($value < 604800 && $units == 'auto') || $units == 'days') {
		return ($units == 'auto' ? __('%s days',number_format_i18n(round($value/86400,2),2), 'grid') : number_format_i18n(round($value/86400,2),2));
	} elseif (($value < 2618784 && $units == 'auto') || $units == 'weeks') {
		return ($units == 'auto' ? __('%s wks', number_format_i18n(round($value/604800, 2),2), 'grid') : number_format_i18n(round($value/604800, 2),2));
	} elseif (($value < 31536000 && $units == 'auto') || $units == 'months') {
		return ($units == 'auto' ? __('%s mths', number_format_i18n(round($value/2618784, 2),2), 'grid') : number_format_i18n(round($value/2618784, 2),2));
	} else {
		return ($units == 'auto' ? __('%s yrs', number_format_i18n(round($value/31536000, 2),2), 'grid') : number_format_i18n(round($value/31536000, 2),2));
	}
}

function build_dstat_display_array() {
	$display_text = array();
	$display_text['nosort1'] = array(
			'display' => __('Actions', 'grid'),
			'sort'    => 'ASC'
	);
	if (get_request_var('clusterid') != '-1') {
		$display_text['nosort2'] = array(
			'display' => __('Cluster Name', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('job_user') != '-1') {
		$display_text['user'] = array(
			'display' => __('User Name', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('queue') != '-1') {
		$display_text['queue'] = array(
			'display' => __('Queue Name', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('app') != '-1') {
		$display_text['app'] = array(
			'display' => __('Application', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('project') != '-1') {
		$display_text['projectName'] = array(
			'display' => __('Project Name', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('exec_host') != '-1') {
		$display_text['exec_host'] = array(
			'display' => __('Exec Host', 'grid'),
			'sort'    => 'ASC'
		);
	}
	if (get_request_var('stat') != '-1') {
		$display_text['stat'] = array(
			'display' => __('Result', 'grid'),
			'sort'    => 'ASC'
		);
	}
	$display_text['total_jobs'] = array(
		'display' => __('Jobs', 'grid'),
		'align'   => 'right',
		'sort'    => 'DESC'
	);
	$display_text['total_slots'] = array(
		'display' => format_job_slots('',true),
		'align'   => 'right',
		'sort'    => 'DESC'
	);
	$display_text['total_gpus'] = array(
		'display' => __('GPUs', 'grid'),
		'align'   => 'right',
		'sort'    => 'DESC'
	);
	$display_text['wall_time'] = array(
		'display' => __('Wall Time', 'grid'),
		'align'   => 'right',
		'sort'    => 'DESC'
	);
	$display_text['gpu_wall_time'] = array(
		'display' => __('GPU Wall Time(Excl.)', 'grid'),
		'align'   => 'right',
		'sort'    => 'DESC'
	);
	$display_text['system_time'] = array(
		'display' => __('System Time', 'grid'),
		'align'   => 'right',
		'tip'     => __('The system time multiplied by the number of slots or cores requested of all jobs in this time period matching the filter criteria.', 'grid'),
		'sort'    => 'DESC'
	);
	$display_text['user_time'] = array(
		'display' => __('User Time', 'grid'),
		'align'   => 'right',
		'tip'     => __('The user time multiplied by the number of slots or cores requested of all jobs in this time period matching the filter criteria.', 'grid'),
		'sort'    => 'DESC'
	);
	$display_text['avg_cpu_effic'] = array(
		'display' => __('Core Eff', 'grid'),
		'align'   => 'right',
		'tip'     => __('The Average Core Efficiency of all jobs in this time period matching the filter criteria.', 'grid'),
		'sort'    => 'DESC'
	);
	if (!isset_request_var('summarize') || ((get_request_var('summarize') != 'true') && (get_request_var('summarize') != 'on'))) {
		$display_text['interval_start'] = array(
			'display' => __('Start Date', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		);
		$display_text['interval_end'] = array(
			'display' => __('End Date', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		);
	}
	return $display_text;
}

function grid_invalid_regex($regex) {
    /* override the error handler */
    $track_errors = ini_get('track_errors');
    ini_set('track_errors', 1);

    if (@preg_match("'".$regex."'", '') !== false) {
        ini_set('track_errors', $track_errors);
        return false;
    }

	$last_err = error_get_last();

    $php_error = trim(str_replace('preg_match():', '', $last_err['message']));

    /* reset the error handler */
    ini_set('track_errors', $track_errors);

    $errors = array(
        PREG_NO_ERROR               => __('Code 0 : Syntax errors', 'grid'),
        PREG_INTERNAL_ERROR         => __('Code 1 : There was an internal PCRE error', 'grid'),
        PREG_BACKTRACK_LIMIT_ERROR  => __('Code 2 : Backtrack limit was exhausted', 'grid'),
        PREG_RECURSION_LIMIT_ERROR  => __('Code 3 : Recursion limit was exhausted', 'grid'),
        PREG_BAD_UTF8_ERROR         => __('Code 4 : The offset didn\'t correspond to the begin of a valid UTF-8 code point', 'grid'),
        PREG_BAD_UTF8_OFFSET_ERROR  => __('Code 5 : Malformed UTF-8 data', 'grid'),
    );

    $error = preg_last_error();

    if (empty($error)) {
        return $php_error;
    } else {
        return $errors[$error];
    }
}

function grid_validate_filter_var($filter = '') {
    if ($filter != '') {
        $error = grid_invalid_regex($filter);

        if ($error !== false) {
            $_SESSION['grid_regex_custom'] = "Invalid RegEX: $error";
            raise_message('grid_regex_custom');
            return false;
        }
    }

    return true;
}
