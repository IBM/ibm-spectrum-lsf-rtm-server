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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include_once('./plugins/grid/include/grid_constants.php');
include_once('./plugins/grid/include/grid_messages.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/grid/lib/grid_validate.php');
include_once('./plugins/grid/lib/grid_filter_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');
include_once('./plugins/heuristics/heuristics_webapi.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

/* get the grid polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

$grid_job_control_actions = array(
	1 => __('Set First in Queue', 'heuristics'),
	2 => __('Set Last in Queue', 'heuristics'),
	3 => __('Switch Queue', 'heuristics'),
	4 => __('Run Now', 'heuristics'),
	5 => __('Suspend Job', 'heuristics'),
	6 => __('Resume Job', 'heuristics'),
	7 => __('Terminate Job', 'heuristics'),
	8 => __('Force Kill', 'heuristics'),
	9 => __('Signal Kill', 'heuristics'),
	10=> __('Kill as DONE', 'heuristics')
);


/* prevent users actions from clearing filters */
if ((!isset_request_var('sort_column')) &&
	(!isset_request_var('tab')) &&
	(!isset_request_var('page')) &&
	(!isset_request_var('jobs_open_x')) &&
	(!isset_request_var('jobs_close_x')) &&
	(!isset_request_var('close_console_x')) &&
	(!isset_request_var('open_console_x'))) {
	if (!isset_request_var('action')) {
		load_current_session_value('action', 'sess_grid_view_jobs_action', 'viewlist');
	}
} elseif (!isset_request_var('action')) {
	set_request_var('action','');
}

$title = __('IBM Spectrum LSF RTM - Batch Jobs Utility', 'heuristics');

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();
switch (get_request_var('action')) {
	case 'viewjob':
		heuristics_validate_job_request_variables('sess_grid_view_jobs_viewjob');
		general_header();
		$update_hint  = ' [ <a href="heuristics.php" class="pic">' . __('JobIQ Dashboard', 'heuristics') . '</a> ]';
		$update_hint .= ' [ <a href="heuristics_jobs.php?action=viewlist&jobid=" class="pic">' . __('JobIQ Jobs', 'heuristics') . '</a> ]';

		html_start_box(__esc('Batch Job %s %s for User %s', get_request_var('jobid'), get_request_var('indexid') > 0 ? '[' . get_request_var('indexid') . ']':'', (get_request_var('job_user') == -1 ? __('All Users', 'heuristics'):get_request_var('job_user')), 'heuristics') . $update_hint, '100%', true, '3', 'center', '');

		print '<br>';

		// Fake the header off
		set_request_var('header', 'false');

		grid_view_job_detail($config['url_path'] . 'plugins/heuristics/heuristics_jobs.php');

		html_end_box(false, true);

		bottom_footer();

		break;
	case 'actions':
		heuristics_validate_job_request_variables();
		bjobs_form_action($config['url_path'] . 'plugins/heuristics/heuristics_jobs.php');
		break;
	case 'ajax_rtm_users':
		heuristics_validate_job_request_variables();
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bjobs.php', 'job_user', $sql_where);
		break;
	default:
		heuristics_validate_job_request_variables();
		if (isset_request_var('export')) {
			grid_view_export_jobs();
		} else {
			grid_view_jobs();
		}

		break;
}

/* changing tz back when this page is done */
if ($tz_is_changed) {
	db_execute("SET SESSION time_zone='SYSTEM'");
	date_default_timezone_set($orig_tz);
}

function heuristics_validate_job_request_variables($sess_prefix = 'sess_grid_view_jobs') {
	/* ================= input validation ================= */
	input_validate_input_regex_exitcode(get_request_var_request('exitcode'));
	input_validate_input_regex_jobid_indexid(get_request_var('jobid'));
	/* ==================================================== */

    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('grid_records')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('default_grid')
			),
		'efficiency' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'memsize' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'runtime' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'date1' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'date2' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => read_grid_config_option('default_job_status'),
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'exception' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'cluster_tz' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => read_grid_config_option('default_grid_tz')
			),
		'dynamic_updates' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => read_grid_config_option('default_grid_dynamic')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'run_time',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '86400'
			),
		'jobid' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'resource_str' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'resource_sanitize_search_string'),
			'pageset' => true
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, $sess_prefix);
	/* ================= input validation ================= */
}

function grid_view_export_jobs() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;

	if (get_request_var('clusterid') > 0) {
		if (isset_request_var('cluster_tz') && (get_request_var('cluster_tz') == 'on' || get_request_var('cluster_tz') == 'true')) {
			$cluster_tz = db_fetch_cell_prepared('SELECT cluster_timezone
				FROM grid_clusters
				WHERE clusterid = ?',
				array(get_request_var('clusterid')));

			if ($cluster_tz) {
				db_execute_prepared("SET SESSION time_zone=?", array($cluster_tz));
				date_default_timezone_set($cluster_tz);
				$tz_is_changed = true;

			} else {
				db_execute("SET SESSION time_zone='SYSTEM'");
			}
		} else {
			db_execute("SET SESSION time_zone='SYSTEM'");
		}
	} else {
		db_execute("SET SESSION time_zone='SYSTEM'");
	}

	$xport_array = array();

    $jobs = grid_view_get_jobs_records($total_rows, FALSE, read_config_option('grid_xport_rows'));

	$queue_nice_levels = array_rekey(db_fetch_assoc("SELECT
		CONCAT_WS('',clusterid,'-',queuename,'') AS cluster_queue,
		nice
		FROM grid_queues"), 'cluster_queue', 'nice');

	/* build header */
	array_push($xport_array, grid_jobs_build_export_header());

	if (cacti_sizeof($jobs)) {
		foreach($jobs as $job) {
			array_push($xport_array, grid_jobs_build_export_row($job, $queue_nice_levels));
		}
	}

	header('Content-type: application/csv');
	header('Cache-Control: max-age=15');
	header('Content-Disposition: attachment; filename=grid_jobs_xport.csv');
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}

function build_heuristics_job_list($table, $reference_table, $sql_where) {
	$jobs_sql = '';
	$jobs_sql = build_jobs_select_list($table, $reference_table, false);

	if (orderby_clustername()) {
		$jobs_sql .=  ", grid_clusters.clustername as jobclustername FROM $table, grid_clusters ";
	} else {
		$jobs_sql .=  ", '' AS jobclustername FROM $table";
	}

	$sql_where = str_replace('grid_jobs.', $table . '.', $sql_where);

	return $jobs_sql . ' ' . $sql_where;
}

function build_heuristics_job_count_list ($table, $sql_where) {
	$rows_sql = " COUNT(jobid) AS mycount FROM $table ";

	$sql_where = str_replace('grid_jobs.', $table . '.', $sql_where);
	$sql_where = str_replace("$table.clusterid = grid_clusters.clusterid AND", '', $sql_where);

	return $rows_sql . ' ' . $sql_where;
}

function grid_view_get_jobs_records(&$total_rows, $apply_limits = TRUE, $rows = '30') {
	$sort_order = ' ' . get_order_string();

	/* get the jobs */
	$sql_where = heuristics_sql_where('grid_jobs');

	if ((preg_match('/(-1|STARTED|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != '-1')) {
		if (get_request_var('timespan') <= 7200) {  //for today
			$jobs_sql = 'SELECT '  . build_heuristics_job_list('grid_jobs', 'grid_jobs', (strlen($sql_where) ? "$sql_where AND " : 'WHERE ') . "stat NOT IN ('DONE', 'EXIT')");
			$jobs_sql .= ' UNION SELECT '  . build_heuristics_job_list('grid_jobs_finished', 'grid_jobs',$sql_where);
		} else {  //for yesterday or last 2 days
			$jobs_sql = 'SELECT '  . build_heuristics_job_list('grid_jobs', 'grid_jobs', (strlen($sql_where) ? "$sql_where AND " : 'WHERE ') . "stat NOT IN ('DONE', 'EXIT')");
			$jobs_sql .= ' UNION SELECT '  . build_heuristics_job_list('grid_jobs_finished', 'grid_jobs',$sql_where);

			if (read_config_option('grid_partitioning_enable') == 'on') {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', time() - get_request_var('timespan')), date('Y-m-d H:i:s'));

				if (cacti_sizeof($tables)) {
				foreach($tables as $table) {
					$jobs_sql .= ' UNION SELECT '  . build_heuristics_job_list($table, 'grid_jobs', $sql_where);
				}
				}
			}
		}
	} else {
		$jobs_sql = 'SELECT '  . build_heuristics_job_list('grid_jobs', 'grid_jobs', $sql_where);
	}

	$jobs_sql = "$jobs_sql $sort_order";

	if ($apply_limits) {
		$jobs_sql .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//echo $jobs_sql .'<br>';
	$jobs = db_fetch_assoc($jobs_sql);

	if ((preg_match('/(-1|STARTED|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != '-1')) {
		if (get_request_var('timespan') <= 7200) {  //for today
			$rows_sql = 'SELECT ' . build_heuristics_job_count_list('grid_jobs', (strlen($sql_where) ? "$sql_where AND " : 'WHERE ') . "stat NOT IN ('DONE', 'EXIT')");
			$rows_sql .= ' UNION SELECT ' . build_heuristics_job_count_list('grid_jobs_finished', $sql_where)  ;
		} else {  //for yesterday or last 2 days
			$rows_sql = 'SELECT ' . build_heuristics_job_count_list('grid_jobs', (strlen($sql_where) ? "$sql_where AND " : 'WHERE ') . "stat NOT IN ('DONE', 'EXIT')");
			$rows_sql .= ' UNION SELECT ' . build_heuristics_job_count_list('grid_jobs_finished', $sql_where)  ;

			if (read_config_option('grid_partitioning_enable') == 'on') {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', time() - get_request_var('timespan')), date('Y-m-d H:i:s'));

				if (cacti_sizeof($tables)) {
				foreach($tables as $table) {
					$rows_sql .= ' UNION SELECT ' . build_heuristics_job_count_list($table, $sql_where);
				}
				}
			}
		}
		$rows_sql = "SELECT SUM(mycount) FROM ($rows_sql) AS `rows`";
	} else {
		$rows_sql = 'SELECT ' . build_heuristics_job_count_list('grid_jobs', $sql_where);
	}

	$total_rows = db_fetch_cell($rows_sql);

	return $jobs;
}

function jobsDetailedFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_efficiency_display_ranges;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	global $heuristics_memsizes, $heuristics_runtimes, $heuristics_history;

	if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) {
		$filterChange = 'applyFilter()';
	} else {
		$filterChange = '';
	}

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='heuristics_jobs.php'>
			<table class='filterTable'>
				<tr>
					<?php print html_autocomplete_filter('grid_bjobs.php', 'User', 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => 'All'), '75');?>
					<td>
						<?php print __('Cluster', 'heuristics');?>
					</td>
					<td>
						<select id='clusterid' onChange='<?php print $filterChange;?>'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if(isempty_request_var('job_user') || get_request_var('job_user') == '-1') {
								$clusterids = db_fetch_assoc('SELECT DISTINCT clusterid FROM grid_jobs');
							} else {
								$clusterids = db_fetch_assoc_prepared('SELECT DISTINCT clusterid FROM grid_jobs WHERE user=?', array(get_request_var('job_user')));
							}
							if (cacti_sizeof($clusterids)) {
								$clusterids = array_rekey($clusterids, 'clusterid', 'clusterid');
								$clusters = grid_get_clusterlist(false, $clusterids);
								if (cacti_sizeof($clusters) > 0) {
									foreach ($clusters as $cluster) {
										print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'heuristics');?>
					</td>
					<td>
						<select id='queue' onChange='<?php print $filterChange;?>'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								if (get_request_var('job_user') == '-1') {
									$queues = db_fetch_assoc('SELECT DISTINCT queue
										FROM grid_jobs
										ORDER BY queue');
								} else {
									$queues = db_fetch_assoc_prepared('SELECT DISTINCT queue
										FROM grid_jobs WHERE user=?
										ORDER BY queue', array(get_request_var('job_user')));
								}
							} else {
								if (get_request_var('job_user') == '-1') {
									$queues = db_fetch_assoc_prepared('SELECT DISTINCT queue
										FROM grid_jobs WHERE clusterid=?
										ORDER BY queue', array(get_request_var('clusterid')));
								} else {
									$queues = db_fetch_assoc_prepared('SELECT DISTINCT queue
										FROM grid_jobs WHERE user=? AND clusterid=?
										ORDER BY queue', array(get_request_var('job_user'), get_request_var('clusterid')));
								}
							}
							if (cacti_sizeof($queues) > 0) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue['queue'] .'"'; if (get_request_var('queue') == $queue['queue']) { print ' selected'; } print '>' . $queue['queue'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Project', 'heuristics');?>
					</td>
					<td>
						<select id='project' onChange='<?php print $filterChange;?>'>
							<option value='-1'<?php if (get_request_var('project') == '-1') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								if (get_request_var('job_user') == '-1') {
									$projects = db_fetch_assoc('SELECT DISTINCT projectName
										FROM grid_jobs
										ORDER BY projectName');
								} else {
									$projects = db_fetch_assoc_prepared('SELECT DISTINCT projectName
										FROM grid_jobs WHERE user=?
										ORDER BY projectName', array(get_request_var('job_user')));
								}
							} else {
								if (get_request_var('job_user') == '-1') {
									$projects = db_fetch_assoc_prepared('SELECT DISTINCT projectName
										FROM grid_jobs WHERE clusterid=?
										ORDER BY projectName', array(get_request_var('clusterid')));
								} else {
									$projects = db_fetch_assoc_prepared('SELECT DISTINCT projectName
										FROM grid_jobs WHERE user=? AND clusterid=?
										ORDER BY projectName', array(get_request_var('job_user'), get_request_var('clusterid')));
								}
							}

							if (cacti_sizeof($projects)) {
								foreach ($projects as $p) {
									print '<option value="' . urlencode($p['projectName']) .'"'; if (get_request_var('project') == $p['projectName']) { print ' selected'; } print '>' . $p['projectName'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'heuristics');?>
					</td>
					<td>
						<select id='status' onChange="<?php print $filterChange;?>">
							<?php if (read_grid_config_option('default_job_status_list') == 'on') {?>
							<option value='ALL'<?php if (get_request_var('status') == 'ALL') {?> selected<?php }?>><?php print __('ALL', 'heuristics');?></option>
							<?php } ?>
							<?php if (get_request_var('status') == '-1') {?>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('CUSTOM', 'heuristics');?></option>
							<?php }?>
							<option value='ACTIVE'<?php if (get_request_var('status') == 'ACTIVE') {?> selected<?php }?>><?php print __('ACTIVE', 'heuristics');?></option>
							<option value='STARTED'<?php if (get_request_var('status') == 'STARTED') {?> selected<?php }?>><?php print __('STARTED', 'heuristics');?></option>
							<option value='FINISHED'<?php if (get_request_var('status') == 'FINISHED') {?> selected<?php }?>><?php print __('FINISHED', 'heuristics');?></option>
							<option value='SUSP'<?php if (get_request_var('status') == 'SUSP') {?> selected<?php }?>><?php print __('SUSPENDED', 'heuristics');?></option>
							<?php

							if (get_request_var('clusterid') == 0) {
								$status = db_fetch_assoc('SELECT DISTINCT stat
									FROM grid_jobs_stats
									ORDER BY stat');
							} else {
								$status = db_fetch_assoc_prepared('SELECT stat
									FROM grid_jobs_stats
									WHERE clusterid= ?
									ORDER BY stat',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($status)) {
								foreach ($status as $stat) {
									print '<option value="' . $stat['stat'] . '"'; if (get_request_var('status') == $stat['stat']) { print ' selected'; } print '>' . $stat['stat'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Jobs', 'heuristics');?>
					</td>
					<td>
						<select id='rows' onChange='<?php print $filterChange;?>'>
						<?php
						if (cacti_sizeof($grid_rows_selector) > 0) {
							foreach ($grid_rows_selector as $key => $value) {
								print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
						}
						?>
						</select>
					</td>
					<?php if ((preg_match('/(-1|STARTED|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != "-1")) { ?>
					<td>
					</td>
					<?php } ?>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'heuristics');?>' title='<?php print __esc('Search', 'heuristics');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'heuristics');?>' title='<?php print __esc('Clear Filters', 'heuristics');?>'>
							<input type='button' id='export' value='<?php print __esc('Export', 'heuristics');?>' title='<?php print __esc('Export to CSV', 'heuristics');?>'>
						</span>
					</td>
					<td class='nowrap'><?php print (get_request_var('exitcode') != '' ? __('NOTE: Custom ExitCode filter set.  Press Clear to remove it.', 'heuristics'):'');?></td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<?php if ((preg_match('/(-1|STARTED|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != "-1")) { ?>
					<td>
						<?php print __('History', 'heuristics');?>
					</td>
					<td>
						<select id='timespan' onChange="<?php print $filterChange;?>">
							<?php
							foreach($heuristics_history AS $time => $name) {
								print "<option value='" . $time . "'"; if (get_request_var('timespan') == $time) { print ' selected'; } print '>' . $name . '</option>';
							}
							?>
						</select>
					</td>
					<?php } ?>
					<td>
						<?php print __('Effic', 'heuristics');?>
					</td>
					<td>
						<select id='efficiency' onChange='<?php print $filterChange;?>'>
							<option value='-1'<?php if (get_request_var('efficiency') == '-1') {?> selected<?php }?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (cacti_sizeof($grid_efficiency_display_ranges) > 0) {
								foreach ($grid_efficiency_display_ranges as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('efficiency') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('ShowOnly', 'heuristics');?>
					</td>
					<td>
						<select id='exception' onChange="<?php print $filterChange;?>">
							<?php
							print "<option value='-1' " . (get_request_var('exception') == '-1' ? 'selected' : '') .  '>' . __('N/A', 'heuristics') . '</option>';

							print (read_config_option('grid_efficiency_warning_bgcolor') > 0 ? "<option value='warn' " . ((get_request_var('exception') == 'warn') ? 'selected' : '') . '>' . __('Warning', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_efficiency_alarm_bgcolor') > 0 ? "<option value='alarm' " . ((get_request_var('exception') == 'alarm') ? 'selected' : '') . '>' . __('Alarm', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_flapping_bgcolor') > 0 ? "<option value='flap' " . ((get_request_var('exception') == 'flap') ? 'selected' : '') . '>' . __('Flapping', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_depend_bgcolor') > 0 ? "<option value='dep' " . ((get_request_var('exception') == 'dep') ? 'selected' : '') . '>' . __('Depend', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_invalid_depend_bgcolor') > 0 ? "<option value='invdep' " . ((get_request_var('exception') == 'invdep') ? 'selected' : '') . '>' . __('Inv Depend', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_exclusive_bgcolor') > 0 ? "<option value='excl' " . ((get_request_var('exception') == 'excl') ? 'selected' : '') . '>' . __('Exclusive', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_interactive_bgcolor') > 0 ? "<option value='inter' " . ((get_request_var('exception') == 'inter') ? 'selected' : '') . '>' . __('Interactive', 'heuristics') . '</option>' : '');

							print (read_config_option('grid_licsched_bgcolor') > 0 ? "<option value='licsch' " . ((get_request_var('exception') == 'licsch') ? 'selected' : '') . '>' . __('Susp Lic Sched', 'heuristics') . '</option>' : '');

							print (read_config_option('grididle_bgcolor') > 0 ? "<option value='hogs' " . ((get_request_var('exception') == 'hogs') ? 'selected' : '') . '>' . read_config_option('grididle_filter_name') . '</option>' : '');

							print (read_config_option('gridmemvio_bgcolor') > 0 ? "<option value='memvio' " . ((get_request_var('exception') == 'memvio') ? 'selected' : '') . '>' . read_config_option('gridmemvio_filter_name') . '</option>' : '');

							print (read_config_option('gridmemvio_us_bgcolor') > 0 ? "<option value='memviou' " . ((get_request_var('exception') == 'memviou') ? 'selected' : '') . '>' . read_config_option('gridmemvio_us_filter_name') . '</option>' : '');

							print (read_config_option('gridrunlimitvio_bgcolor') > 0 ? "<option value='runtimevio' " . ((get_request_var('exception') == 'runtimevio') ? 'selected' : '') . '>' . read_config_option('gridrunlimitvio_filter_name') . '</option>' : '');

							print "<option value='gpuonly' " . ((get_request_var('exception') == 'gpuonly')?'selected' : '').'>' . __('GPU', 'heuristics') . '</option>';
							print (read_config_option('grid_slaloaning_bgcolor') > 0 ? "<option value='slaloaning' " . ((get_request_var('exception') == 'slaloaning') ? 'selected' : '') . '>' . __('GuarRes Loaning', 'grid') . '</option>' : '');

							api_plugin_hook('grid_jobs_filter');
							?>
						</select>
					</td>
					<td>
						<?php print __('MemSize', 'heuristics');?>
					</td>
					<td>
						<select id='memsize' onChange='<?php print $filterChange;?>'>
						<?php
						if (cacti_sizeof($heuristics_memsizes)) {
							foreach($heuristics_memsizes as $index => $data) {
								print "<option value='$index' ". (get_request_var('memsize') == $index ? 'selected' : '') .  '>' . $data['text'] . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('RunTime', 'heuristics');?>
					</td>
					<td>
						<select id='runtime' onChange='<?php print $filterChange;?>'>
						<?php
						if (cacti_sizeof($heuristics_runtimes)) {
							foreach($heuristics_runtimes as $index => $data) {
								print "<option value='$index' " . (get_request_var('runtime') == $index ? 'selected' : '') .  '>' . $data['text'] . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<input type='checkbox' id='dynamic_updates' <?php if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) print ' checked="true"';?> onClick='applyFilter()'>
					</td>
					<td>
						<label for='dynamic_updates'>Dynamic</label>
					</td>
					<?php if (get_request_var('clusterid') > 0) { ?>
					<td>
						<input type='checkbox' id='cluster_tz'<?php if ((get_request_var('cluster_tz') == 'true') || (get_request_var('cluster_tz') == 'on')) print ' checked="true"';?> onClick='applyFilter()'>
					</td>
					<td>
						<label for='cluster_tz'>Cluster TZ</label>
					</td>
					<?php } ?>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('JobID', 'heuristics');?>
					</td>
					<td>
						<input type='text' id='jobid' size='10' value='<?php print html_escape_request_var('jobid');?>'>
					</td>
					<td>
						<?php print __('Search', 'heuristics');?>
					</td>
					<td colspan='3'>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<?php resource_browser($filterChange);?>
				</tr>
			</table>
			<?php if (get_request_var('clusterid') == 0) { ?>
			<input type='hidden'<?php print (get_request_var('cluster_tz') == 'true' || get_request_var('cluster_tz') == 'on') ? ' checked="true">': '';?>>
			<?php } ;?>
			<?php if (!((preg_match('/(-1|STARTED|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != "-1"))) { ?>
			<input type='hidden' id='timespan' value='<?php print html_escape_request_var('timespan');?>'>
			<?php } ;?>
			<input type='hidden' id='page' value='1'>
			<input type='hidden' id='report' value='jobs'>
			<input type='hidden' id='exitcode' value='<?php print html_escape_request_var('exitcode');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_view_jobs() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	global $grid_efficiency_display_ranges, $grid_efficiency_sql_ranges;
	global $job_id, $index_id, $job, $row_color, $tz_is_changed;
	global $grid_job_control_actions;

	$display_array = build_job_display_array('heuristics_jobs.php');

	/* clean up date1 string */
	if (isset_request_var('jobid')) {
		if (substr_count(get_request_var('jobid'), "[")) {
			$job_array = explode("[", str_replace("]", "", get_request_var('jobid')));
			$job_id    = $job_array[0];
			$index_id  = $job_array[1];
		} elseif (strlen(get_request_var('jobid'))) {
			$job_id    = get_request_var('jobid');
			$index_id  = "";
		}
	} else {
		$job_id   = "";
		$index_id = "";
	}

	//set_request_var('jobid', $job_id);
	//set_request_var('indexid', $index_id);

	/* set m_page variable */
	$sql_where  = "";
	$sql_where1 = "";
	$sql_where2 = "";

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option("grid_records");
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('clusterid') > 0) {
		if (isset_request_var('cluster_tz') && (get_request_var('cluster_tz') == "on" || get_request_var('cluster_tz') == "true")) {
			$cluster_tz = db_fetch_cell_prepared("SELECT cluster_timezone FROM grid_clusters WHERE clusterid = ?", array(get_request_var('clusterid')));

			if ($cluster_tz) {
				db_execute_prepared("SET SESSION time_zone=?", array($cluster_tz));
				date_default_timezone_set($cluster_tz);
				$tz_is_changed = true;

			} else {
				db_execute("SET SESSION time_zone='SYSTEM'");
			}
		} else {
			db_execute("SET SESSION time_zone='SYSTEM'");
		}
	} else {
		db_execute("SET SESSION time_zone='SYSTEM'");
	}

	$total_rows = 0;

	$job_results = grid_view_get_jobs_records($total_rows, TRUE, $rows);

	// Pull lookup tables into memory; improve page performance at the expense of per-request memory
	$host_lookup = array_rekey(grid_view_get_host_records(), "id",
			array("hostType", "hostModel"));
	$queue_lookup = array_rekey(grid_view_get_queue_records(), "id",
			array("maxjobs", "userJobLimit", "availSlots", "runjobs", "numslots"));
	$user_lookup = array_rekey(grid_view_get_user_records(), "id",
			array("maxJobs", "numJobs", "numRUN", "numPEND"));
	$user_queue_lookup = array_rekey(grid_view_get_user_queue_slot_records(), "id",
			array("total_cpus"));

	general_header();

	$debug_log = nl2br(debug_log_return('grid_admin'));

	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table class='debug'>
			<tr>
				<td class='monospace'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
	<?php
	}

	$lastsec = db_fetch_cell("SELECT MAX(`value`) FROM settings WHERE `name` LIKE 'poller_lastrun%'", false);

	if (empty($lastsec)) {
		$update_hint = '';
	} else {
		$lastsec = time() - (int) $lastsec;
		$lastmin = (int) ($lastsec / 60);
		$lastsec = $lastsec - $lastmin * 60;
		$update_hint = ' [ Updated ' . ($lastmin ? ($lastmin . ' Minutes and ') : '') . $lastsec . ' Seconds Ago ]';
	}

	$update_hint .= ' [ <a href="heuristics.php" class="pic">' . __('JobIQ Dashboard', 'heuristics') . '</a> ]';

	html_start_box(__('Batch Job Filters for User \'%s\'', (get_request_var('job_user') == -1 ? __('All Users', 'heuristics'):get_request_var('job_user')), 'heuristics') . $update_hint . "<span id='message' style='position:absolute;right:20px;top:5px;'></span>", '100%', '', '3', 'center', '');

	jobsDetailedFilter();

	html_end_box();

	if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) {
		?>
		<script type='text/javascript'>

		function applyFilter() {
			strURL = 'heuristics_jobs.php?header=false&action=viewlist';
			strURL += '&job_user=' + $('#job_user').val();
			strURL += '&clusterid=' + $('#clusterid').val();
			strURL += '&exception=' + $('#exception').val();
			strURL += '&memsize=' + $('#memsize').val();
			strURL += '&runtime=' + $('#runtime').val();
			strURL += '&cluster_tz=' + $('#cluster_tz').is(':checked');
			strURL += '&rows=' + $('#rows').val();
			strURL += '&status=' + $('#status').val();
			strURL += '&queue=' + $('#queue').val();
			strURL += '&project=' + $('#project').val();
			strURL += '&efficiency=' + $('#efficiency').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&jobid=' + $('#jobid').val();
			strURL += '&dynamic_updates=' + $('#dynamic_updates').is(':checked');
			strURL += '&resource_str=' + escape($('input[id="resource_str"]').val());
			strURL += '&timespan=' + $('#timespan').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = 'heuristics_jobs.php?header=false&action=viewlist&clear=true';
			loadPageNoHeader(strURL);
		}

		function exportJobs() {
			strURL = 'heuristics_jobs.php?export=true';
			document.location = strURL;
			Pace.stop();
		}

		$(function() {
			$('#form_grid').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#export').click(function() {
				exportJobs();
			});

			if (typeof refreshTimer != 'undefined') {
				clearTimeout(refreshTimer);
			}
			applySkinRTM();
		});

		</script>
		<?php
	} else {
		?>
		<script type='text/javascript'>

		function applyFilter() {
			strURL = '?action=viewlist&dynamic_updates=' + $('#dynamic_updates').is(':checked');

			document.location = strURL;
		}

		$(function() {
			if (typeof refreshTimer != 'undefined') {
				clearTimeout(refreshTimer);
			}
		});

		</script>
		<?php
	}

	$if_show_actions = check_user_status();

	$jobs_page  = $config['url_path'] . 'plugins/heuristics/heuristics_jobs.php';
	$table_name = 'grid_jobs';

    display_job_results($jobs_page, $table_name, $job_results, $rows, $total_rows);

    display_job_legend();

    if ($if_show_actions) {
        draw_actions_dropdown($grid_job_control_actions);
        form_end();
    }

    bottom_footer();

	exit;
}

function get_jobs_col_width() {
	$i = 0;
	if (read_config_option('grid_efficiency_warning_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_efficiency_alarm_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_flapping_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_depend_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_invalid_depend_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_exit_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_exclusive_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_interactive_bgcolor') > 0) { $i++; }
	if (read_config_option('grid_licsched_bgcolor') > 0) { $i++; }
	if (read_config_option('grididle_bgcolor') > 0) { $i++; }
	if (read_config_option('gridmemvio_bgcolor') > 0) { $i++; }
	if (read_config_option('gridmemvio_us_bgcolor') > 0) { $i++; }
	if (read_config_option('gridrunlimitvio_bgcolor') > 0) { $i++; }

	return floor(100/$i);
}

function heuristics_sql_where($table_name) {
	global $grid_efficiency_sql_ranges, $job_id, $index_id;

	$rows = 0;

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = "";
	if (orderby_clustername()) {
		$sql_where = "WHERE $table_name.clusterid = grid_clusters.clusterid ";
	}

	if (get_request_var('exception') == "-1") {
		/* Show all items */
	} else {
		switch(get_request_var('exception')) {
		case "alarm":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.efficiency<='" . read_config_option("grid_efficiency_alarm") . "' AND $table_name.stat!='PEND')";
			break;
		case "warn":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.efficiency<='" . read_config_option("grid_efficiency_warning") . "' AND $table_name.stat!='PEND')";
			break;
		case "excl":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.options & " . SUB_EXCLUSIVE . ")";
			break;
		case "invdep":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.pendReasons LIKE '%invalid or never satisfied%')";
			break;
		case "licsch":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.pendReasons LIKE '%preempted by the License Scheduler%')";
			break;
		case "dep":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "(LENGTH($table_name.dependCond)!=0)";
			break;
		case "inter":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.options & " . SUB_INTERACTIVE . ")";
			break;
		case "flap":
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "($table_name.stat_changes>='" . read_config_option("grid_flapping_threshold") . "')";
			break;
		case 'gpuonly':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . '(num_gpus > 0)';
			break;
		case 'slaloaning':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "isLoaningGSLA = 1";
			break;
		}
	}

	/* jobid  id sql where */
	if (get_request_var('jobid') == "") {
		/* Show all items */
	} else {
		if (empty($job_id)) {
			$tjob = explode("[", get_request_var('jobid'));
			$job_id = $tjob[0];
			if (isset($tjob[1])) {
				$index_id = trim($tjob[1], "] \r\n");
			} else {
				$index_id = "";
			}
		}

		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.jobid=" . $job_id . ")";

		if (strlen($index_id)) {
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.indexid='" . $index_id . "')";
		}
	}

	/* exitcode sql where */
	if (get_request_var('exitcode') == "") {
		/* Show all items */
	} else {
		$parts  = explode("|", get_request_var('exitcode'));
		$status = (isset($parts[0]) && strlen($parts[0]))? $parts[0] :NULL ;
		$mask   = (isset($parts[1]) && strlen($parts[1]))? $parts[1] :NULL ;
		$info   = (isset($parts[2]) && strlen($parts[2]))? $parts[2] :NULL ;
		$sql_where .= strlen($status) ? (strlen($sql_where) ? " AND " : " WHERE ") . " ($table_name.exitStatus='" . $status . "')" : "";
		$sql_where .= strlen($mask)   ? (strlen($sql_where) ? " AND " : " WHERE ") . " ($table_name.exceptMask='" . $mask . "')" : "";
		$sql_where .= strlen($info)   ? (strlen($sql_where) ? " AND " : " WHERE ") . " ($table_name.exitInfo='" . $info . "')" : "";
	}

	/* user id sql where */
	if (get_request_var('job_user') == "-1") {
		/* Show all items */
	} else {
		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.user='" . get_request_var('job_user') . "')";
	}

	/* project id sql where */
	if (get_request_var('project') == "-1") {
		/* Show all items */
	} else {
		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.projectName='" . get_request_var('project') . "')";
	}

	/* efficiency sql where */
	if (get_request_var('efficiency') == "-1") {
		/* Show all items */
	} else {
		$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . "(" . str_replace("efficiency", $table_name . ".efficiency", $grid_efficiency_sql_ranges[get_request_var('efficiency')]) . ")";
	}

	/* clusterid sql where */
	if (get_request_var('clusterid') == "0") {
		/* Show all items */
	} else {
		$sql_where  .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.clusterid='" . get_request_var('clusterid') . "')";
	}

	/* job status sql where */
	if ((get_request_var('status') == "-1") || (get_request_var('status') == "ALL")) {
		/* Show all items */
	} else {
		if ((get_request_var('status') == "ACTIVE")) {
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.stat NOT IN ('DONE', 'EXIT', 'ZOMBI'))";
		} elseif ((get_request_var('status') == "STARTED")) {
			/* do nothing, all status' wanted */
		} elseif ((get_request_var('status') == "FINISHED")) {
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.stat IN ('DONE', 'EXIT'))";
		} elseif ((get_request_var('status') == "SUSP")) {
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.stat IN ('PSUSP','USUSP','SSUSP'))";
		} else {
			$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.stat='" . get_request_var('status') . "')";
		}
	}

	/* queue sql where */
	if (get_request_var('queue') == "-1") {
		/* Show all items */
	} else {
		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.queue='" . get_request_var('queue') . "')";
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " ($table_name.jobname LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.projectName LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.licenseProject LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.jobGroup LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.jobid LIKE '%" . get_request_var('filter') . "%')";
	}

	/* memsize SQL */

	/* runtime SQL */
	if (isset_request_var('memsize')) {
	switch(get_request_var('memsize')) {
	case 1:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 0 AND 1024000';
		break;
	case 2:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 1024000 AND 2048000';
		break;
	case 3:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 2048000 AND 4096000';
		break;
	case 4:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 4096000 AND 8096000';
		break;
	case 5:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 8096000 AND 16172000';
		break;
	case 6:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 16172000 AND 24576000';
		break;
	case 7:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 24576000 AND 32768000';
		break;
	case 8:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory BETWEEN 32768000 AND 65536000';
		break;
	case 9:
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'max_memory >= 65536000';
		break;
	}
	}

	if (isset_request_var('runtime')) {
	switch(get_request_var('runtime')) {
	case '1':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 0 AND 300';
		break;
	case '2':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 300 AND 900';
		break;
	case '3':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 900 AND 1800';
		break;
	case '4':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 1800 AND 3600';
		break;
	case '5':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 3600 AND 7200';
		break;
	case '6':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 7200 AND 21600';
		break;
	case '7':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 21600 AND 43200';
		break;
	case '8':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 43200 AND 86400';
		break;
	case '9':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time BETWEEN 86400 AND 172800';
		break;
	case '10':
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'run_time >= 172800';
		break;
	}
	}

	/* resource string where clause */
	if (get_request_var('resource_str') != "") {
		if (get_request_var('clusterid') > 0) {
			$res_tool  = grid_get_res_tooldir(get_request_var('clusterid')) . "/gridhres";

			$cwd = getcwd();
			chdir(grid_get_res_tooldir(get_request_var('clusterid')));

			if (is_executable($res_tool)) {
				input_validate_input_number(get_request_var('clusterid'));
				input_validate_input_regex_xss_attack(get_request_var('resource_str'));
				$res_cmd   = $res_tool . " -C " . get_request_var('clusterid') . " -R \"" . get_request_var('resource_str'). "\"";
				$ret_val   = 0;
				$ret_out   = array();
				$res_hosts = exec($res_cmd, $ret_out, $ret_val);

				chdir($cwd);
				if (!$ret_val) {
					if (strlen($res_hosts)) {
						$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " $table_name.exec_host IN ($res_hosts)";
					} else {
						$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " $table_name.exec_host IS NULL";
					}
				} else {
					$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . " $table_name.exec_host IS NULL";

					if ($ret_val == 96) {
						$_SESSION["sess_messages"] = "No hosts returned";
					} elseif ($ret_val == 95) {
						$_SESSION["sess_messages"] = "Invalid Resource String";
					} else {
						$_SESSION["sess_messages"] = "Unknown LSF Error: $ret_val";
					}
				}
			} else {
				cacti_log("ERROR: gridhres either does not exist or is not executable!");
			}
		} else {
			unset_request_var('resource_str');
			load_current_session_value("resource_str", "sess_grid_view_jobs_resource_str", "");
		}
	}

	if (read_config_option('grididle_bgcolor') > 0 && get_request_var('exception') == 'hogs') {
		if (get_request_var('clusterid')>0) {
			$idle_jobs = db_fetch_assoc_prepared("SELECT * FROM grid_jobs_idled WHERE clusterid=?", array(get_request_var('clusterid')));
		} else {
			$idle_jobs = db_fetch_assoc("SELECT * FROM grid_jobs_idled");
		}

		$in_clause = "(";
		if (cacti_sizeof($idle_jobs)) {
			foreach($idle_jobs as $job) {
				if ($in_clause == "(") {
					$in_clause .= "'" . $job["clusterid"] . "_" . $job["jobid"] . "_" . $job["indexid"] . "'";
				} else {
					$in_clause .= ",'" . $job["clusterid"] . "_" . $job["jobid"] . "_" . $job["indexid"] . "'";
				}
			}
		}
		$in_clause .= ")";

		/* if empty set then put empty string for in clause */
		if ($in_clause == "()") {
			$in_clause = "('')";
		}

		$sql_where .= (strlen($sql_where) ? " AND ": "WHERE ") . "CONCAT_WS('',$table_name.clusterid,'_',$table_name.jobid,'_',$table_name.indexid,'') IN $in_clause";
	}

	if (read_config_option('gridmemvio_bgcolor') > 0 && get_request_var('exception') == 'memvio') {
		$mem_limit = read_config_option("gridmemvio_min_memory");
		$memvio_window = read_config_option("gridmemvio_window");
		$overage   = read_config_option("gridmemvio_overage");

		$sql_where .= (strlen($sql_where) ? " AND ": "WHERE ") .
		               "max_memory > mem_reserved * (1+$overage) AND
		               mem_reserved * (1+$overage) > 0 AND
		               run_time > $memvio_window" .
		               ($mem_limit != -1 ? " AND max_memory>$mem_limit":"");
	} elseif (read_config_option('gridmemvio_us_bgcolor') > 0 && get_request_var('exception') == 'memviou') {
		$mem_limit = read_config_option("gridmemvio_min_memory");
		$memvio_window = read_config_option("gridmemvio_window");
		$underage  = read_config_option("gridmemvio_us_allocation");

		$sql_where .= (strlen($sql_where) ? " AND ": "WHERE ") .
		               "max_memory < mem_reserved * (1-$underage) AND
		               mem_reserved * (1-$underage) > 0 AND
		               run_time > $memvio_window" .
		               ($mem_limit != -1 ? " AND max_memory>$mem_limit":"");
	}

	if (read_config_option('gridrunlimitvio_bgcolor') > 0 && get_request_var('exception') == 'runtimevio') {
		if (get_request_var('clusterid')>0) {
			$runtime_jobs = db_fetch_assoc_prepared("SELECT * FROM grid_jobs_runtime WHERE present=1 AND (type=2 OR type=1) AND clusterid=?", array(get_request_var('clusterid')));
		} else {
			$runtime_jobs = db_fetch_assoc("SELECT * FROM grid_jobs_runtime WHERE present=1 AND (type=2 OR type=1)");
		}
		$in_clause = "(";
		if (cacti_sizeof($runtime_jobs)) {
			foreach($runtime_jobs as $job) {
				if ($in_clause == "(") {
					$in_clause .= "'" . $job["clusterid"] . "_" . $job["jobid"] . "_" . $job["indexid"] . "'";
				} else {
					$in_clause .= ",'" . $job["clusterid"] . "_" . $job["jobid"] . "_" . $job["indexid"] . "'";
				}
			}
		}
		$in_clause .= ")";

		/* if empty set then put empty string for in clause */
		if ($in_clause == "()") {
			$in_clause = "('')";
		}

		$sql_where .= (strlen($sql_where) ? " AND ": "WHERE ") . "CONCAT_WS('',$table_name.clusterid,'_',$table_name.jobid,'_',$table_name.indexid,'') IN $in_clause";

	}

	if ((preg_match('/(STARTED|FINISHED|DONE|EXIT)/', get_request_var('status'))) AND (get_request_var('clusterid') != '-1')) {
		if (isset_request_var('timespan')) {
			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . " $table_name.end_time>'" . date('Y-m-d H:i:s', time() - get_request_var('timespan')) . "'";
		}
	}

	$sql_where = api_plugin_hook_function('grid_jobs_sql_where', $sql_where);

	return $sql_where;
}

function format_seconds($time) {
	if ($time >= 60) {
		$time = $time / 60;
		if ($time >= 24) {
			$time = $time / 24;
			return round($time,1) . 'd';
		} else {
			return round($time,1) . 'h';
		}
	} else {
		return round($time,1) . 'm';
	}
}

function format_time($time, $twoline = FALSE) {
	if (!substr_count($time, '0000-00-00')) {
		if ($twoline) {
			if (date('Y', time()) == substr($time, 0, 4)) {
				return substr($time,5,5) . '<br>' . substr($time,11);
			} else {
				return substr($time,0,10) . '<br>' . substr($time,11);
			}
		} else {
			if (date('Y', time()) == substr($time, 0, 4)) {
				return substr($time,5);
			} else {
				return $time;
			}
		}
	} else {
		return '-';
	}
}
