<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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
include_once($config['library_path'] . '/rtm_plugins.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_messages.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_validate.php');

/* get the grid polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

$grid_job_control_actions = array(
	1 => __('Set First in Queue', 'grid'),
	2 => __('Set Last in Queue', 'grid'),
	3 => __('Switch Queue', 'grid'),
	4 => __('Run Now', 'grid'),
	5 => __('Suspend Job', 'grid'),
	6 => __('Resume Job', 'grid'),
	7 => __('Terminate Job', 'grid'),
	8 => __('Force Kill', 'grid'),
	9 => __('Signal Kill', 'grid'),
	10=> __('Kill as DONE', 'grid')
);

$grid_job_control_actions = api_plugin_hook_function('job_actions', $grid_job_control_actions);

set_default_action('viewlist');

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
}

$title = __('IBM Spectrum LSF RTM - Batch Jobs Utility', 'grid');

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();

if (isempty_request_var('tab') && (get_request_var('action') !='viewjob')) {
	grid_validate_job_request_variables();
}

switch (get_request_var('action')) {
	case 'viewjob':
		grid_validate_job_request_variables_tab();
		grid_view_job_detail($config['url_path'] . 'plugins/grid/grid_bjobs.php');

		break;
	case 'actions':
		bjobs_form_action($config['url_path'] . 'plugins/grid/grid_bjobs.php');
		break;
	case 'ajaxsearch':
		ajax_search();
		break;
	case 'ajaxlsfversion':
		ajaxlsfversion();
		break;
	case 'ajax_rtm_exec_hosts':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bjobs.php', 'exec_host', $sql_where);
		break;
	case 'ajax_rtm_users':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bjobs.php', 'job_user', $sql_where);
		break;
	case 'ajax_rtm_hgroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bjobs.php', 'hgroup', $sql_where);
		break;
	case 'ajax_rtm_usergroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		if (read_config_option('grid_usergroup_method') == 'jobmap') {
			rtm_autocomplete_ajax('grid_bjobs2.php', 'usergroup', $sql_where);
		} else {
			rtm_autocomplete_ajax('grid_bjobs.php', 'usergroup', $sql_where);
		}
		break;
	default:
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

function grid_validate_job_request_variables() {
	global $job_id, $index_id;

	/* ================= input validation ================= */
	input_validate_input_regex_jobid_indexid(get_request_var('jobid'));
	/* ==================================================== */

	if (isset_request_var('jobid')) {
		if (substr_count(get_request_var('jobid'), '[')) {
			$job_array = explode('[', str_replace(']', '', get_request_var('jobid')));
			$job_id    = $job_array[0];
			$index_id  = $job_array[1];
			set_request_var('jobid', $job_id);
		} elseif (strlen(get_request_var('jobid'))) {
			$job_id    = get_request_var('jobid');
			$index_id  = '';
		} else {
			$job_id   = '';
			$index_id = '';
		}
	} else {
		$job_id   = '';
		$index_id = '';
	}
	
	set_request_var('indexid', $index_id);

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
/*		'predefined_timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_timespan')
			),
		'predefined_timeshift' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_timeshift')
			),
*/
		'efficiency' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'predefined_graph_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'start_time',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'level' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'reasonid' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => ''
			),
		'usergroup' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
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
		'exec_host' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'hgroup' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => '-1'
			),
		'sub_host' => array(
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
		'app' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '-1'
			),
		'jgroup' => array(
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
		'jobid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '',
			'pageset' => true
			),
		'resource_str_search_by' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '1'
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'end_time' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'start_time' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'submit_time' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'indexid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => ''
			)
	);

	validate_store_request_vars($filters, 'sess_gbj');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('resource_str', 'sess_grid_view_jobs_resource_str', '');
	load_current_session_value('resource_str_search_by', 'sess_grid_view_jobs_resource_str_search_by', '1');
	/* ================= input validation ================= */
}

function grid_validate_job_request_variables_tab() {
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
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'start_time',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gbj_tab'. get_request_var('tab'));
}

function grid_view_export_jobs() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull;

	$display_array = grid_job_export_display_array();

	if (get_request_var('clusterid') > 0) {
		if (isset_request_var('cluster_tz') && (get_request_var('cluster_tz') == 'on' || get_request_var('cluster_tz') == 'true')) {
			$cluster_tz = db_fetch_cell_prepared('SELECT cluster_timezone FROM grid_clusters WHERE clusterid = ?', array(get_request_var('clusterid')));

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

	/* set variables for first time use */
	$timespan  = initialize_timespan();
	$timeshift = grid_set_timeshift();

	/* process the timespan/timeshift settings */
	process_html_variables();
	process_user_input($timespan, $timeshift);

	/* save session variables */
	finalize_timespan($timespan);

	$sql_where   = '';
	$sql_where1  = '';
	$sql_where2  = '';
	$xport_array = array();
	$total_rows  = 0;
	/* determine users authentication level */
	$authfull = api_plugin_user_realm_auth('LSF_Extended_History');

	/* determine the table for queries */
	if ($authfull) {
		if ((preg_match('/^(DONE|EXIT|FINISHED|ALL)$/', get_request_var('status')))) {
			$table_name = 'grid_jobs_finished';
		} else {
			$table_name = 'grid_jobs';
		}
	} else {
		$table_name = 'grid_jobs';
	}

	/* get the jobs */
	if ($authfull) {
		if (preg_match('/^(ALL|STARTED|DONE|EXIT|FINISHED)$/', get_request_var('status')) || get_request_var('status') == -1) {
			$grid_jobs_rows = 0;
			$grid_jobs_query = '';
			$grid_jobs_finished_query = '';
			$jobsquery = '';
			//$rowsquery1 = '';
			$rowsquery = "";//$rowsquery is not required for export
			//$rowsquery2 = '';

			get_jobs_query('grid_jobs', false, $jobsquery, $rowsquery, get_request_var('resource_str_search_by'));
			$grid_jobs_query = $jobsquery;

			get_jobs_query('grid_jobs_finished', false, $jobsquery, $rowsquery, get_request_var('resource_str_search_by'));
			$grid_jobs_finished_query = $jobsquery;

			$union_jobs_query = union_grids($grid_jobs_query, $grid_jobs_finished_query, true, read_config_option('grid_xport_rows'), $total_rows);
			$jobs  = db_fetch_assoc($union_jobs_query);
		} else {
			$jobs = grid_view_get_jobs_records($total_rows, $table_name, true, false, read_config_option('grid_xport_rows'), get_request_var('resource_str_search_by'));
		}
	} else {
		$jobs = grid_view_get_jobs_records($total_rows, $table_name, false, false, read_config_option('grid_xport_rows'), get_request_var('resource_str_search_by'));
	}

	$queue_nice_levels = array_rekey(db_fetch_assoc("SELECT
		CONCAT_WS('',clusterid,'-',queuename,'') AS cluster_queue,
		nice
		FROM grid_queues"), 'cluster_queue', 'nice');

	/* build header */
	array_push($xport_array, grid_jobs_build_export_header());

	if (!empty($jobs)) {
		foreach(array_unique_multidimensional($jobs) as $job) {
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

function grid_view_get_jobs_records(&$total_rows, $table_name, $authfull = false, $apply_limits = true, $rows = '30', $resreq_query = '1') {
	$jobs_query = '';
	$jobs_finished_query = '';
	$sql_order = '';
	$rowsquery1 = '';
	$rowsquery2 = '';

	$sql_order = ' ' . get_order_string();
	//Remove the wasted_memory from order clause, if NOT displayed
	if (strpos($sql_order, "wasted_memory") && read_grid_config_option('show_wasted_memory') != 'on') {
		$sql_order = "";
	}

	get_jobs_query('grid_jobs', false, $jobs_query, $rowsquery1, $resreq_query);
	if ($authfull && preg_match('/(-1|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) {
		get_jobs_query('grid_jobs_finished', false, $jobs_finished_query, $rowsquery2, $resreq_query);
	}

	$jobs = NULL;
	if (strlen($jobs_query) && $table_name == 'grid_jobs') {
		if (strlen($jobs_finished_query)) {
			$jobs_query = $jobs_query . ' UNION ' . $jobs_finished_query;
		}

		$jobs_query .= $sql_order;

		if ($apply_limits) {
			$jobs_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		}

		$jobs = db_fetch_assoc($jobs_query);
		if (strlen($rowsquery1)) {
			$total_rows = (strlen($rowsquery1) ? db_fetch_cell($rowsquery1):0);
		}

		if (strlen($rowsquery2)) {
			$total_rows += (strlen($rowsquery2) ? db_fetch_cell($rowsquery2):0);
		}
	} else if (strlen($jobs_finished_query)) {
		$jobs_finished_query .= $sql_order;

		if ($apply_limits) {
			$jobs_finished_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		}

		$jobs = db_fetch_assoc($jobs_finished_query);

		$total_rows += (strlen($rowsquery2) ? db_fetch_cell($rowsquery2):0);

		if (get_request_var('status') != 'DONE' && get_request_var('status') != 'EXIT' && get_request_var('status') != 'FINISHED') {
			$total_rows += (strlen($rowsquery1) ? db_fetch_cell($rowsquery1):0);
		}
	}

	return $jobs;
}

function jobsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_efficiency_display_ranges;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull;

	$fetch_job_status_list = (read_config_option('grid_global_opts_jobs_status_all', true) == 'on') ? read_grid_config_option('default_job_status_list', true) : 'off';
	$callbackName = "dummy";
	if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) {
		$callbackName = "applyFilter";
	}

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>

			// This function update the date in the input field when selected
			function selected(cal, date) {
				cal.sel.value = date;      // just update the date in the input field.
			}

			// This function gets called when the end-user clicks on the 'Close' button.
			// It just hides the calendar without destroying it.
			function closeHandler(cal) {
				cal.hide();                        // hide the calendar
				calendar = null;
			}

			$(function() {
				$('#reasonid').autocomplete({
					autoFocus: true,
					source: urlPath + 'plugins/grid/grid_bjobs.php?action=ajaxsearch&type=reasonid&clusterid=<?php print get_request_var('clusterid');?>&level=<?php if (isset_request_var('level')) {print get_request_var('level');}?>',
					minLength: 0,
					select: function(event, ui) {
						$('#reasonid').val(ui.item.value);

						if ($('input[name=dynamic_updates]').is(':checked')) {
							applyJobsFilterChange(document.form_grid_view_jobs);
						}
					}
   			     });
			});
			</script>

			<form id='form_grid' action='grid_bjobs.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>All</option>
							<?php
							$clusters = grid_get_clusterlist();
							if (!empty($clusters) > 0) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_bjobs.php', __('User', 'grid'), 'job_user', get_request_var('job_user'), $callbackName, get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<?php
						if (read_config_option('grid_usergroup_method') == 'jobmap') {
							print html_autocomplete_filter('grid_bjobs2.php', __('UGroup', 'grid'), 'usergroup', get_request_var('usergroup'), $callbackName, get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');
						} else {
							print html_autocomplete_filter('grid_bjobs.php', __('UGroup', 'grid'), 'usergroup', get_request_var('usergroup'), $callbackName, get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');
						}
					?>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td>
						<select id='status'>
							<?php if ($fetch_job_status_list == 'on') {?>
							<option value='ALL'<?php if (get_request_var('status') == 'ALL') {?> selected<?php }?>><?php print __('ALL', 'grid');?></option>
							<?php }if (get_request_var('status') == '-1') {?>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('CUSTOM', 'grid');?></option>
							<?php }?>
							<option value='ACTIVE'<?php if (get_request_var('status') == 'ACTIVE') {?> selected<?php }?>><?php print __('ACTIVE', 'grid');?></option>
							<option value='STARTED'<?php if (get_request_var('status') == 'STARTED') {?> selected<?php }?>><?php print __('STARTED', 'grid');?></option>
							<option value='FINISHED'<?php if (get_request_var('status') == 'FINISHED') {?> selected<?php }?>><?php print __('FINISHED', 'grid');?></option>
							<?php

							if (get_request_var('clusterid') == 0) {
								$status = db_fetch_assoc('SELECT DISTINCT stat
									FROM grid_jobs_stats
									ORDER BY stat');
							} else {
								$status = db_fetch_assoc_prepared('SELECT stat
									FROM grid_jobs_stats
									WHERE clusterid= ? ORDER BY stat',
									array(get_request_var('clusterid')));
							}

							if (!empty($status)) {
								foreach ($status as $stat) {
									print '<option value="' . $stat['stat'] . '"'; if (get_request_var('status') == $stat['stat']) { print ' selected'; } print '>' . html_escape($stat['stat']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Eff', 'grid');?>
					</td>
					<td>
						<select id='efficiency'>
							<option value='-1'<?php if (get_request_var('efficiency') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (cacti_sizeof($grid_efficiency_display_ranges)) {
								foreach ($grid_efficiency_display_ranges as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('efficiency') == $key) { print ' selected'; } print '>' . $value . '</option>\n';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go');?>' title='<?php print __esc('Search');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear');?>' title='<?php print __esc('Clear Filters');?>'>
							<input type='button' id='export' value='<?php print __esc('Export', 'grid');?>' title='<?php print __esc('Export to CSV');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Queue', 'grid');?>
					</td>
					<td>
						<select id='queue'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$queues = db_fetch_assoc('SELECT DISTINCT queue
									FROM grid_jobs_queues
									ORDER BY queue');
							} else {
								$queues = db_fetch_assoc_prepared('SELECT queue
									FROM grid_jobs_queues
									WHERE clusterid = ? ORDER BY queue',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue['queue'] .'"'; if (get_request_var('queue') == $queue['queue']) { print ' selected'; } print '>' . html_escape($queue['queue']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_bjobs.php', __('Host', 'grid'), 'exec_host', get_request_var('exec_host'), $callbackName, get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<?php print html_autocomplete_filter('grid_bjobs.php', __('HGroup', 'grid'), 'hgroup', get_request_var('hgroup'), $callbackName, get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Jobs', 'grid');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Except', 'grid');?>
					</td>
					<td>
						<select id='exception'>
							<?php
							print "<option value='-1' ". (get_request_var('exception') == '-1' ? 'selected' : '') . '>' . __('N/A', 'grid') . '</option>';
							print (read_config_option('grid_efficiency_warning_bgcolor') > 0 ? "<option value='warn' " . ((get_request_var('exception') == 'warn') ? 'selected' : '') . '>' . __('Warning', 'grid') . '</option>' : '');
							print (read_config_option('grid_efficiency_alarm_bgcolor') > 0 ? "<option value='alarm' " . ((get_request_var('exception') == 'alarm') ? 'selected' : '') . '>' . __('Alarm', 'grid') . '</option>' : '');
							print (read_config_option('grid_flapping_bgcolor') > 0 ? "<option value='flap' " . ((get_request_var('exception') == 'flap') ? 'selected' : '') . '>' . __('Flapping', 'grid') . '</option>' : '');
							print (read_config_option('grid_depend_bgcolor') > 0 ? "<option value='dep' " . ((get_request_var('exception') == 'dep') ? 'selected' : '') . '>' . __('Depend', 'grid') . '</option>' : '');
							print (read_config_option('grid_invalid_depend_bgcolor') > 0 ? "<option value='invdep' " . ((get_request_var('exception') == 'invdep') ? 'selected' : '') . '>' . __('Inv Depend', 'grid') . '</option>' : '');
							print (read_config_option('grid_exclusive_bgcolor') > 0 ? "<option value='excl' " . ((get_request_var('exception') == 'excl') ? 'selected' : '') . '>' . __('Exclusive', 'grid') . '</option>' : '');
							print (read_config_option('grid_interactive_bgcolor') > 0 ? "<option value='inter' " . ((get_request_var('exception') == 'inter') ? 'selected' : '') . '>' . __('Interactive', 'grid') . '</option>' : '');
							print (read_config_option('grid_licsched_bgcolor') > 0 ? "<option value='licsch' " . ((get_request_var('exception') == 'licsch') ? 'selected' : '') . '>' . __('Susp Lic Sched', 'grid') . '</option>' : '');
							print (read_config_option('grididle_bgcolor') > 0 ? "<option value='hogs' " . ((get_request_var('exception') == 'hogs') ? 'selected' : '') . '>' . read_config_option('grididle_filter_name') . '</option>' : '');
							print (read_config_option('gridmemvio_bgcolor') > 0 ? "<option value='memvio' " . ((get_request_var('exception') == 'memvio') ? 'selected' : '') . '>' . read_config_option('gridmemvio_filter_name') . '</option>' : '');
							print (read_config_option('gridmemvio_us_bgcolor') > 0 ? "<option value='memviou' " . ((get_request_var('exception') == 'memviou') ? 'selected' : '') . '>' . read_config_option('gridmemvio_us_filter_name') . '</option>' : '');
							print (read_config_option('gridrunlimitvio_bgcolor') > 0 ? "<option value='runtimevio' " . ((get_request_var('exception') == 'runtimevio') ? 'selected' : '') . '>' . read_config_option('gridrunlimitvio_filter_name') . '</option>' : '');
							print "<option value='gpuonly' " . ((get_request_var('exception') == 'gpuonly')?'selected' : '').'>' . __('GPU Only', 'grid') . '</option>';
							if (get_request_var('status')=='PEND') {
								print "<option value='pwht' " . ((get_request_var('exception') == 'pwht')?'selected' : '').'>' . __('Pend with Host', 'grid') . '</option>';
								print "<option value='pwre' " . ((get_request_var('exception') == 'pwre')?'selected' : '').'>' . __('Pend with Resource', 'grid') . '</option>';
								print "<option value='pwqe' " . ((get_request_var('exception') == 'pwqe')?'selected' : '').'>' . __('Pend with Queue', 'grid') . '</option>';
								print "<option value='pwjg' " . ((get_request_var('exception') == 'pwjg')?'selected' : '').'>' . __('Pend with JobGroup', 'grid') . '</option>';
								print "<option value='pwug' " . ((get_request_var('exception') == 'pwug')?'selected' : '').'>' . __('Pend with UserGroup', 'grid') . '</option>';
								print "<option value='pwlm' " . ((get_request_var('exception') == 'pwlm')?'selected' : '').'>' . __('Pend with Limit', 'grid') . '</option>';
							}
							print (read_config_option('grid_slaloaning_bgcolor') > 0 ? "<option value='slaloaning' " . ((get_request_var('exception') == 'slaloaning') ? 'selected' : '') . '>' . __('GuarRes Loaning', 'grid') . '</option>' : '');

							api_plugin_hook('grid_jobs_filter');

							?>
						</select>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('JobID', 'grid');?>
					</td>
					<td>
						<input type='text' id='jobid' size='10' value='<?php print get_request_var('jobid').(!isempty_request_var('indexid')?"[".get_request_var('indexid')."]":"");?>'>
					</td>
					<?php if (get_request_var('status') == 'PEND') { ?>
					<td id='pend_td' colspan='2' class='nowrap'>
						<label><?php print __('Pend Level', 'grid');?></label>
						<select id='level' name='level'>
							<option value='-1' <?php if (get_request_var('level') == '-1') print ' selected';?>><?php print __('All', 'grid');?></option>
								<?php
								if (get_request_var('clusterid') != 0) {
									$lsf_version = db_fetch_cell_prepared('SELECT grid_pollers.lsf_version
										FROM grid_pollers
										JOIN grid_clusters
										ON grid_clusters.poller_id=grid_pollers.poller_id
										WHERE grid_clusters.clusterid = ?',
										array(get_request_var('clusterid')));

									if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1010')) {
								?>
								<option value='0' <?php if (get_request_var('level') == '0') print ' selected';?>><?php print __('Uncategorized Reason (-p0)', 'grid');?></option>
								<option value='1' <?php if (get_request_var('level') == '1') print ' selected';?>><?php print __('Single Key Reason (-p1)', 'grid');?></option>
								<option value='2' <?php if (get_request_var('level') == '2') print ' selected';?>><?php print __('Candidate Host Reason (-p2)', 'grid');?></option>
								<?php }
							} ?>
						</select>
					</td>
					<td id='reason_td' colspan='2' class='nowrap'>
						<label><?php print __('Reason', 'grid');?></label>
						<input type='text' id='reasonid' size='30' maxlength='40' value='<?php print get_request_var('reasonid');?>'>
					</td>
					<?php } else { ?>
					<td id='pend_td' colspan=2 class='nowrap'>
						<input type='hidden' id='level' value=''>
					</td>
					<td id='reason_td' colspan=2 class='nowrap'>
						<input type='hidden' id='reasonid' value=''>
					</td>
					<?php } ?>
					<td>
						<?php print __('Apps', 'grid');?>
					</td>
					<td width='1'>
						<select id='app'>
							<option value='-1' <?php if (get_request_var('app') == '-1') {?> selected <?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$apps = db_fetch_assoc('SELECT DISTINCT appName
									FROM grid_applications
									ORDER BY appName');
							} else {
								$apps = db_fetch_assoc_prepared('SELECT appName
									FROM grid_applications
									WHERE clusterid = ?
									ORDER BY appName',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($apps)) {
								foreach ($apps as $app) {
									print '<option value="' . $app['appName'] .'"'; if (get_request_var('app') == $app['appName']) { print ' selected'; } print '>' . html_escape($app['appName']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php if (read_config_option('grid_job_group_aggregation') == 'on') {
						if ((preg_match('/(-1|FINISHED|DONE|EXIT|ALL)/', get_request_var('status'))) AND (get_request_var('clusterid') != '-1')) { ?>
					<td>
					<?php } else { ?>
					<td>
					<?php } ?>
						<?php print __('JGroup', 'grid');?>
					</td>
					<td>
						<select id='jgroup'>
							<option value='-1' <?php if (get_request_var('jgroup') == '-1') {?> selected <?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$jgroups = db_fetch_assoc('SELECT DISTINCT groupName
									FROM grid_groups
									WHERE present="1"
									ORDER BY groupName');
							} else {
								$jgroups = db_fetch_assoc_prepared('SELECT groupName
									FROM grid_groups
									WHERE clusterid = ?
									AND present="1" ORDER BY groupName',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($jgroups)) {
								foreach ($jgroups as $jgroup) {
									print '<option value="' . $jgroup['groupName'] .'"'; if (get_request_var('jgroup') == $jgroup['groupName']) { print ' selected'; } print '>' . html_escape($jgroup['groupName']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php } else { print("<input type='hidden' id='jgroup' value = '-1'>"); } ?>
				</tr>
			</table>
			<?php
			if (preg_match('(STARTED|FINISHED|DONE|EXIT|ALL)', get_request_var('status'))) {
				print "<table class='filterTable'>";
				if ($authfull) {
					jobsDetailedTimeFilter();
				} else {
					jobsSimpleTimeFilter();
				}
				print '</table>';
			}
			?>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<?php resource_browser(true);?>
					<td>
						<input type='checkbox' id='dynamic_updates'<?php if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) print ' checked';?>>
					</td>
					<td>
						<label for='dynamic_updates'><?php print __('Dynamic', 'grid');?></label>
					</td>
					<?php if (get_request_var('clusterid') > 0) { ?>
					<td>
						<input type='checkbox' id='cluster_tz'<?php if ((get_request_var('cluster_tz') == 'true') || (get_request_var('cluster_tz') == 'on')) print ' checked';?>>
					</td>
					<td>
						<label for="cluster_tz"><?php print __('Cluster TZ', 'grid');?> </label>
					</td>
					<?php } ?>
				</tr>
			</table>
			<?php if (get_request_var('clusterid') == 0) { ?>
			<input type='hidden' id='cluster_tz'<?php print (get_request_var('cluster_tz') == 'true' || get_request_var('cluster_tz') == 'on') ? ' checked>':'';?>>
			<?php } ;?>
			<input type='hidden' id='sub_host' value='-1'>
			<input type='hidden' id='page' value='1'>
			</form>
		</td>
	</tr>
	<?php
}

function jobsDetailedTimeFilter() {
	global $config, $grid_timespans, $grid_timeshifts, $timespan;

	?>
	<tr id='time_filter'>
		<td>
			<?php print __('Presets', 'grid');?>
		</td>
		<td>
			<select id='predefined_timespan'>
				<?php
				if ($_SESSION['grid_custom']) {
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
					for ($value=$start_val; $value < $end_val; $value++) {
						print "<option value='" . $value . "'"; if ($_SESSION['sess_grid_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
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
				<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print (isset($_SESSION['sess_grid_current_date1']) ? rtm_safe_session('sess_grid_current_date1') : '');?>'>
				<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'grid');?>'></i>
			</span>
		</td>
		<td>
			<?php print __('To', 'grid');?>
		</td>
		<td>
			<span>
				<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print (isset($_SESSION['sess_grid_current_date2']) ? rtm_safe_session('sess_grid_current_date2') : '');?>'>
				<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector');?>'></i>
			</span>
		</td>
		<td>
			<span>
			<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward', 'grid');?>'></i>
			<select id='predefined_timeshift' title='<?php print __esc('Define Shifting Interval', 'grid');?>'>
				<?php
				$start_val = 1;
				$end_val = cacti_sizeof($grid_timeshifts)+1;
				if (cacti_sizeof($grid_timeshifts)) {
					for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
						print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . title_trim($grid_timeshifts[$shift_value], 40) . '</option>';
					}
				}
				?>
			</select>
			<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward', 'grid');?>'></i>
			</span>
		</td>
	</tr>
	<?php
}

function jobsSimpleTimeFilter() {
	global $grid_timespans;

	?>
	<tr id='time_filter'>
		<td>
			<?php print __('Presets', 'grid');?>
		</td>
		<td>
			<select id='predefined_timespan'>
				<?php
				if ($_SESSION['grid_custom']) {
					$grid_timespans[GT_CUSTOM] = __('Custom', 'grid');
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

				switch (read_config_option('grid_jobs_clean_period')) {
				case 3600:
					$break = 2;
					break;
				case 7200:
					$break = 3;
					break;
				case 14400:
					$break = 4;
					break;
				case 21600:
					$break = 5;
					break;
				case 36000:
					$break = 6;
					break;
				case 86400:
					$break = 7;
					break;
				default:
					$break = 3;
				}

				if (cacti_sizeof($grid_timespans)) {
					for ($value=$start_val; $value < $end_val; $value++) {
						print "<option value='" . $value . "'"; if ($_SESSION['sess_grid_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
						if ($value == $break) break;
					}
				}
				?>
			</select>
		</td>
		<td>
			<?php print __('From', 'grid');?>
		</td>
		<td>
			<input disabled type='text' id='date1' size='16' value='<?php print (isset($_SESSION['sess_grid_current_date1']) ? rtm_safe_session('sess_grid_current_date1') : '');?>'>
		</td>
		<td>
			<?php print __('To', 'grid');?>
		</td>
		<td nowrap>
			<input disabled type='text' id='date2' size='16' value='<?php print (isset($_SESSION['sess_grid_current_date2']) ? rtm_safe_session('sess_grid_current_date2') : '');?>'>
		</td>
		<td>
			<input type='hidden' id='predefined_timeshift' value='<?php print get_request_var('predefined_timeshift');?>'>
		</td>
	</tr>
	<?php
}


function grid_view_jobs() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	global $grid_efficiency_display_ranges, $grid_efficiency_sql_ranges;
	global $job_id, $index_id, $job, $row_color, $authfull, $tz_is_changed;
	global $grid_job_control_actions;

	$display_array = build_job_display_array();

	/* set m_page variable */
	$sql_where  = '';
	$sql_where1 = '';
	$sql_where2 = '';

	/* determine users authentication level */
	$authfull = api_plugin_user_realm_auth('LSF_Extended_History');

	// if db upgrade in progress, do not allow full job table search
	if (read_config_option('grid_db_upgrade', true) == '1') {
		cacti_log('INFO: DB job tables upgrade in progress, job search queries is limited.');
		$authfull = false;
	}

	/* determine the table for queries */
	if ($authfull) {
		if ((preg_match('/^(DONE|EXIT|FINISHED|ALL)$/', get_request_var('status')))) {
			$table_name = 'grid_jobs_finished';
		} else {
			$table_name = 'grid_jobs';
		}
	} else {
		$table_name = 'grid_jobs';
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

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

	/* set variables for first time use */
	$timespan  = initialize_timespan();
	$timeshift = grid_set_timeshift();

	if (isset_request_var('clear')) {
		kill_session_var('sess_graph_job_graph_type');
		unset_request_var('predefined_graph_type');
	}
   	load_current_session_value('predefined_graph_type', 'sess_graph_job_graph_type', '0');

	/* process the timespan/timeshift settings */
	process_html_variables();
	process_user_input($timespan, $timeshift);

	/* save session variables */
	finalize_timespan($timespan);

	$total_rows = 0;

	if ($authfull) {
		if (preg_match('/^(ALL|STARTED|DONE|EXIT|FINISHED)$/', get_request_var('status')) || get_request_var('status') == -1) {
			$grid_jobs_rows = 0;
			$grid_jobs_query = '';
			$grid_jobs_finished_query = '';
	//		$jobsquery  = '';
	//		$rowsquery  = '';
			$rowsquery1  = '';
			$rowsquery2  = '';
			$total_rows = 0;

			//get_jobs_query('grid_jobs', false, $jobsquery, $rowsquery);
			get_jobs_query('grid_jobs', false, $grid_jobs_query, $rowsquery1, get_request_var('resource_str_search_by'));
			//$grid_jobs_query = $jobsquery;

			//$rowsquery  = '';
			//get_jobs_query('grid_jobs_finished', false, $jobsquery, $rowsquery);
			get_jobs_query('grid_jobs_finished', false, $grid_jobs_finished_query, $rowsquery2, get_request_var('resource_str_search_by'));
			//$grid_jobs_finished_query = $jobsquery;

			$union_jobs_query = union_grids($grid_jobs_query, $grid_jobs_finished_query, true, $rows, $total_rows);

			$job_results  = db_fetch_assoc($union_jobs_query);
		} else {
			$job_results = grid_view_get_jobs_records($total_rows, $table_name, true, true, $rows, get_request_var('resource_str_search_by'));
		}
	} else {
		$job_results = grid_view_get_jobs_records($total_rows, $table_name, false, true, $rows, get_request_var('resource_str_search_by'));
	}

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

	if ($lastsec === null) {
		$header_label = __('Batch Job Filters');
	} else {
		$lastsec = time() - (int) $lastsec;
		$lastmin = (int) ($lastsec / 60);
		$lastsec = $lastsec - $lastmin * 60;
		if ($lastmin) {
			if ($lastsec) {
				$header_label = __('Batch Job Filters [ Updated %s Minutes and %s Seconds Ago ]', $lastmin, $lastsec, 'grid');
			} else {
				$header_label = __('Batch Job Filters [ Updated %s Minutes Ago ]', $lastmin, 'grid');
			}
		} else {
			if ($lastsec) {
				$header_label = __('Batch Job Filters [ Updated %s Seconds Ago ]', $lastsec, 'grid');
			} else {
				$header_label = __('Batch Job Filters [ Just Updated ]', 'grid');
			}
		}
	}

	html_start_box($header_label, '100%', '', '3', 'center', '');
	jobsFilter();
	html_end_box();

	if ((get_request_var('dynamic_updates') == 'true') || (get_request_var('dynamic_updates') == 'on')) {
		?>
		<script type='text/javascript'>

		function applyFilter(move_flag) {
			if (arguments.length == 2 && arguments[1] == 'status') {
				if (($('#status').val().search('-1|STARTED|FINISHED|DONE|EXIT|ALL') != -1) && ($('#clusterid').val() != -1)) {
					$('#time_filter').show();
				} else {
					$('#time_filter').hide();
				}
			}
			if ($('#status').val() =='PEND') {
				//$('#pend_td').show();
				//$('#reason_td').show();
			} else {
				//$('#pend_td').hide();
				//$('#reason_td').hide();
				$("#exception option[value='pwht']").remove();
				$("#exception option[value='pwre']").remove();
				$("#exception option[value='pwqe']").remove();
				$("#exception option[value='pwjg']").remove();
				$("#exception option[value='pwug']").remove();
				$("#exception option[value='pwlm']").remove();
			}
			strURL  = urlPath + 'plugins/grid/grid_bjobs.php?action=viewlist&header=false';
			strURL += '&job_user=' + $('#job_user').val();
			strURL += '&usergroup=' + encodeURIComponent($('#usergroup').val());
			strURL += '&clusterid=' + $('#clusterid').val();
			strURL += '&exception=' + $('#exception').val();
			strURL += '&cluster_tz=' + $('#cluster_tz').is(':checked');
			strURL += '&rows=' + $('#rows').val();
			strURL += '&status=' + $('#status').val();
			strURL += '&queue=' + $('#queue').val();
			strURL += '&sub_host=' + $('#sub_host').val();
			strURL += '&exec_host=' + $('#exec_host').val();
			strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
			strURL += '&efficiency=' + $('#efficiency').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&jobid=' + $('#jobid').val();
			strURL += '&dynamic_updates=' + $('#dynamic_updates').is(':checked');
			if (typeof $('#resource_str').val() != 'undefined') {
				strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
			}
			if (typeof $('#resource_str_search_by').val() != 'undefined') {
				strURL += '&resource_str_search_by=' + $('#resource_str_search_by').val();
			}
			strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
			strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
			strURL += '&app=' + $('#app').val();
			strURL += '&jgroup=' + $('#jgroup').val();
			if ($('#status').val() == 'PEND') {
				strURL += '&reasonid=' + $('#reasonid').val();
				if($('#level').val() == ''){
					strURL += '&level=-1';
				} else {
					strURL += '&level=' + $('#level').val();
				}
			}
			if (move_flag==1 || move_flag==2) {
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				if (move_flag == 1) {
					strURL += '&move_left_x=move_left_x';
				}
				if (move_flag == 2) {
					strURL += '&move_right_x=move_right_x';
				}
			} else {
				if (($('#date1').val() != date1 || $('#date2').val() != date2) && !!$('#date1').val() && !!$('#date2').val()) {
					strURL += '&date1=' + $('#date1').val();
					strURL += '&date2=' + $('#date2').val();
				}
			}
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = urlPath + 'plugins/grid/grid_bjobs.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		$(function() {
			date1='<?php print rtm_safe_session('sess_grid_current_date1');?>';
			date2='<?php print rtm_safe_session('sess_grid_current_date2');?>';

			$('tr[id^="line"].selectable').filter(':not(.disabled_row)').mouseenter(function() {
				tmp = $(this).attr('style');
				if(tmp != 'undefined'){
					$(this).attr('pre-style', tmp);
					$(this).removeAttr('style');
				}
			}).mouseleave(function() {
				tmp = $(this).attr('pre-style');
				if(tmp != 'undefined'){
					$(this).attr('style', tmp);
					$(this).removeAttr('pre-style');
				}
			});

			var date1Open       = false;
			var date2Open       = false;
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

			$('#date1').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});

			$('#date2').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});

			if (($('#status').val().search('-1|STARTED|FINISHED|DONE|EXIT|ALL') != -1) && ($('#clusterid').val() != -1)) {
				$('#time_filter').show();
			} else {
				$('#time_filter').hide();
			}
			if ($('#status').val() =='PEND') {
				//$('#pend_td').show();
				//$('#reason_td').show();
			} else {
				//$('#pend_td').hide();
				//$('#reason_td').hide();
				$("#exception option[value='pwht']").remove();
				$("#exception option[value='pwre']").remove();
				$("#exception option[value='pwqe']").remove();
				$("#exception option[value='pwjg']").remove();
				$("#exception option[value='pwug']").remove();
				$("#exception option[value='pwlm']").remove();
			}

			$('#form_grid').submit(function(event) {
				event.preventDefault();
				applyFilter(0);
			});

			$('#rows, #page, #clusterid, #efficiency, #predefined_graph_type, #filter, #sort_column, #sort_direction, #reasonid, #usergroup, #job_user, #status, #queue, #exec_host, #hgroup, #sub_host,#exception, #app, #jgroup, #cluster_tz, #predefined_timespan, #predefined_timeshift, #resource_str_search_by, #level').change(function() {
				applyFilter(0);
			});

			$('#clear').click(function() {
				clearFilter();
			});
			$('#dynamic_updates').click(function() {
				applyFilter(0);
			});
			$('#move_left').click(function() {
				applyFilter(1);
			});
			$('#move_right').click(function() {
				applyFilter(2);
			});

			$('#export').click(function() {
				strURL  = urlPath + 'plugins/grid/grid_bjobs.php?action=viewlist&header=false';
				strURL += '&job_user=' + $('#job_user').val();
				strURL += '&usergroup=' + encodeURIComponent($('#usergroup').val());
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&exception=' + $('#exception').val();
				strURL += '&cluster_tz=' + $('#cluster_tz').is(':checked');
				strURL += '&rows=' + $('#rows').val();
				strURL += '&status=' + $('#status').val();
				strURL += '&queue=' + $('#queue').val();
				strURL += '&sub_host=' + $('#sub_host').val();
				strURL += '&exec_host=' + $('#exec_host').val();
				strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
				strURL += '&efficiency=' + $('#efficiency').val();
				strURL += '&filter=' + $('#filter').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&dynamic_updates=' + $('#dynamic_updates').is(':checked');
				if (typeof $('#resource_str').val() != 'undefined') {
					strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
				}
				if (typeof $('#resource_str_search_by').val() != 'undefined') {
					strURL += '&resource_str_search_by=' + $('#resource_str_search_by').val();
				}
				strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				strURL += '&app=' + $('#app').val();
				strURL += '&jgroup=' + $('#jgroup').val();
				if ($('#status').val() == 'PEND') {
					strURL += '&reasonid=' + $('#reasonid').val();
					if($('#level').val() == ''){
						strURL += '&level=-1';
					} else {
						strURL += '&level=' + $('#level').val();
					}
				}
				strURL += '&export=Export';
				document.location = strURL;
				Pace.stop();
			});

			applySkinRTM();
		});
		</script>
		<?php } else { ?>
		<script type='text/javascript'>

		$(function() {
			var date1Open       = false;
			var date2Open       = false;
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

			$('#date1').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});

			$('#date2').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});
			$('#form_grid').submit(function(event) {
				event.preventDefault();
				applyFilter(0);
			});
			$('#clusterid').change(function() {
				if ($('#clusterid').val != 0) {
					getLsfversion();
				}
			});

			if (($('#status').val().search('-1|STARTED|FINISHED|DONE|EXIT|ALL')!= -1) && ($('#clusterid').val() != -1)) {
				$('#time_filter').show();
			} else {
				$('#time_filter').hide();
			}
			if ($('#status').val() == 'PEND') {
				//$('#pend_td').show();
				//$('#reason_td').show();
			} else {
				//$('#pend_td').hide();
				//$('#reason_td').hide();
				//$('#level').hide();
				$("#exception option[value='pwht']").remove();
				$("#exception option[value='pwre']").remove();
				$("#exception option[value='pwqe']").remove();
				$("#exception option[value='pwjg']").remove();
				$("#exception option[value='pwug']").remove();
				$("#exception option[value='pwlm']").remove();
			}
			$('#clear').click(function() {
				clearFilter();
			});
			$('#dynamic_updates').click(function() {
				applyFilter(0);
			});
			$('#move_left').click(function() {
				applyFilter(1);
			});
			$('#move_right').click(function() {
				applyFilter(2);
			});

			$('#export').click(function() {
				strURL  = urlPath + 'plugins/grid/grid_bjobs.php?action=viewlist&header=false';
				strURL += '&job_user=' + $('#job_user').val();
				strURL += '&usergroup=' + encodeURIComponent($('#usergroup').val());
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&exception=' + $('#exception').val();
				strURL += '&cluster_tz=' + $('#cluster_tz').is(':checked');
				strURL += '&rows=' + $('#rows').val();
				strURL += '&status=' + $('#status').val();
				strURL += '&queue=' + $('#queue').val();
				strURL += '&sub_host=' + $('#sub_host').val();
				strURL += '&exec_host=' + $('#exec_host').val();
				strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
				strURL += '&efficiency=' + $('#efficiency').val();
				strURL += '&filter=' + $('#filter').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&dynamic_updates=' + $('#dynamic_updates').is(':checked');
				if (typeof $('#resource_str').val() != 'undefined') {
					strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
				}
				if (typeof $('#resource_str_search_by').val() != 'undefined') {
					strURL += '&resource_str_search_by=' + $('#resource_str_search_by').val();
				}
				strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				strURL += '&app=' + $('#app').val();
				strURL += '&jgroup=' + $('#jgroup').val();
				if ($('#status').val() == 'PEND') {
					strURL += '&reasonid=' + $('#reasonid').val();
					if($('#level').val() == ''){
						strURL += '&level=-1';
					} else {
						strURL += '&level=' + $('#level').val();
					}
				}
				strURL += '&export=Export';
				document.location = strURL;
				Pace.stop();
			});
			applySkinRTM();
		});

		function clearFilter() {
			strURL = urlPath + 'plugins/grid/grid_bjobs.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		function getLsfversion() {
			var clusterid = $('#clusterid').val();
			$.ajax({
				type: 'POST',
				url: urlPath + 'plugins/grid/grid_bjobs.php?action=ajaxlsfversion',
				data: 'clusterid='+clusterid + '&__csrf_magic='+csrfMagicToken,
				success: showLSFVersion
			});
		}

		function showLSFVersion(result) {
			var jsonList=JSON.parse(result);
			if ($('#status').val() == 'PEND') {
				if (jsonList[0].value < 1010) {
					if ($('#level').children().length == 4) {
						$('#level').find('option').remove();
						$('#level').append($('<option>', {value:'-1', text:'<?php print __('All', 'grid');?>'}));
					}
				} else {
					if ($('#level').children().length == 1) {
						$('#level').find('option').remove();
						$('#level').append($('<option>', {value:'-1', text:'<?php print __('All');?>'}));
						$('#level').append($('<option>', {value:'0',  text:'<?php print __('Uncategorized Reason (-p0)', 'grid');?>'}));
						$('#level').append($('<option>', {value:'1',  text:'<?php print __('Single Key Reason (-p1)', 'grid');?>'}));
						$('#level').append($('<option>', {value:'2',  text:'<?php print __('Candidate Host Reason (-p2)', 'grid');?>'}));
					}

                		}
            		}
        	}

		function applyFilter(move_flag) {
			if ($('#status').val() == 'PEND') {
				//$('#pend_td').show();
				//$('#reason_td').show();
				$('#exception').append($('<option>', {value:'pwht', text:'<?php print __('Pend with Host', 'grid');?>'}));
				$('#exception').append($('<option>', {value:'pwre', text:'<?php print __('Pend with Resource', 'grid');?>'}));
				$('#exception').append($('<option>', {value:'pwqe', text:'<?php print __('Pend with Queue', 'grid');?>'}));
				$('#exception').append($('<option>', {value:'pwjg', text:'<?php print __('Pend with JobGroup', 'grid');?>'}));
				$('#exception').append($('<option>', {value:'pwug', text:'<?php print __('Pend with UserGroup', 'grid');?>'}));
				$('#exception').append($('<option>', {value:'pwlm', text:'<?php print __('Pend with Limit', 'grid');?>'}));
			} else {
				//$('#pend_td').hide();
				//$('#reason_td').hide();
				$("#exception option[value='pwht']").remove();
				$("#exception option[value='pwre']").remove();
				$("#exception option[value='pwqe']").remove();
				$("#exception option[value='pwjg']").remove();
				$("#exception option[value='pwug']").remove();
				$("#exception option[value='pwlm']").remove();
			}

			//if (arguments.length==2 && arguments[1]=='timespan') {
				strURL  = urlPath + 'plugins/grid/grid_bjobs.php?action=viewlist&header=false';
				strURL += '&job_user=' + $('#job_user').val();
				strURL += '&usergroup=' + $('#usergroup').val();
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&exception=' + $('#exception').val();
				strURL += '&cluster_tz=' + $('#cluster_tz').is(':checked');
				strURL += '&rows=' + $('#rows').val();
				strURL += '&status=' + $('#status').val();
				strURL += '&queue=' + $('#queue').val();
				strURL += '&sub_host=' + $('#sub_host').val();
				strURL += '&exec_host=' + $('#exec_host').val();
				strURL += '&hgroup=' + $('#hgroup').val();
				strURL += '&efficiency=' + $('#efficiency').val();
				strURL += '&filter=' + $('#filter').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&dynamic_updates=' + $('#dynamic_updates').is(':checked');
				if (typeof $('#resource_str').val() != 'undefined') {
					strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
				}
				if (typeof $('#resource_str_search_by').val() != 'undefined') {
					strURL += '&resource_str_search_by=' + $('#resource_str_search_by').val();
				}
				strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				strURL += '&app=' + $('#app').val();
				strURL += '&jgroup=' + $('#jgroup').val();

				if ($('#status').val() == 'PEND') {
					strURL = strURL + '&reasonid=' + $('#reasonid').val();
					if($('#level').val() == ''){
						strURL += '&level=-1';
					} else {
						strURL += '&level=' + $('#level').val();
					}
				}
				if (($('#status').val().search('-1|STARTED|FINISHED|DONE|EXIT|ALL')!= -1) && ($('#clusterid').val() != -1)) {
					$('#time_filter').show();
				} else {
					$('#time_filter').hide();
				}
				if (move_flag==1 || move_flag==2) {
					strURL += '&date1=' + $('#date1').val();
					strURL += '&date2=' + $('#date2').val();
					if (move_flag == 1) {
						strURL += '&move_left_x=move_left_x';
					}
					if (move_flag == 2) {
						strURL += '&move_right_x=move_right_x';
					}
				}
				loadPageNoHeader(strURL);
			//}
		}

		</script>
		<?php
	}

	$jobs_page = $config['url_path'] . 'plugins/grid/grid_bjobs.php';

	display_job_results($jobs_page, $table_name, array_unique_multidimensional($job_results), $rows, $total_rows);

	display_job_legend();

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		draw_actions_dropdown($grid_job_control_actions);
		form_end();
	}

	bottom_footer();
}

function ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case 'reasonid':
			$sql_params = array();
			if (get_request_var('term') != '') {
				set_request_var('term', sanitize_search_string(get_request_var('term')));

				if (isset_request_var('clusterid') && get_request_var('clusterid') > 0) {
					$cwhere = ' AND clusterid=?';
					$sql_params[] = get_request_var('clusterid');
				} else {
					$cwhere = '';
				}

				if (get_request_var('level') != -1) {
					if (get_request_var('level') == 0) {
						$level_where = ' AND type IN (15,13,9)';
					} else if (get_request_var('level')==1) {
						$level_where = ' AND type IN (15,2)';
					} else if (get_request_var('level')==2) {
						$level_where = ' AND type IN (15,13)';
					}

					$values = db_fetch_assoc_prepared("SELECT DISTINCT a.reason AS label, a.reason AS value
						FROM grid_jobs_pendreason_maps as a
						INNER JOIN grid_jobs_pendreasons as b
						ON a.reason_code = b.reason
						AND a.issusp=b.issusp
						AND a.sub_reason_code=b.subreason
						WHERE  a.reason LIKE ?
						$level_where
						$cwhere
						ORDER BY label
						LIMIT 20", array_merge(array("%" . get_request_var('term') . "%"), $sql_params));
				} else {
					$values = db_fetch_assoc_prepared("SELECT DISTINCT a.reason AS label, a.reason AS value
						FROM grid_jobs_pendreason_maps as a
						INNER JOIN grid_jobs_pendreasons as b
						ON a.reason_code = b.reason
						AND a.issusp=b.issusp
						AND a.sub_reason_code=b.subreason
						WHERE  a.reason LIKE ?
						$cwhere
						ORDER BY label
						LIMIT 20", array_merge(array("%" . get_request_var('term') . "%"), $sql_params));
				}
			} else {
				if (isset_request_var('clusterid') && get_request_var('clusterid') > 0) {
					$cwhere = 'WHERE clusterid=?';
					$sql_params[] = get_request_var('clusterid');
				} else {
					$cwhere = '';
				}

				$values = db_fetch_assoc_prepared("SELECT DISTINCT a.reason AS label, a.reason AS value
					FROM grid_jobs_pendreason_maps AS a
					INNER JOIN grid_jobs_pendreasons as b
					ON a.reason_code = b.reason
					AND a.issusp=b.issusp
					AND a.sub_reason_code=b.subreason
					$cwhere
					ORDER BY label
					LIMIT 20", $sql_params);
		}
		$new_values = array();
		foreach ($values as $row) {
			$row['value'] =  str_replace('\'', '%', $row['value']);
			$row['value'] =  str_replace('\"', '%', $row['value']);
			$new_values[] = array('label' => $row['label'], 'value' => $row['value']);
		}
		print json_encode($new_values);
		break;
        }
    }
}

function ajaxlsfversion() {
	print json_encode(db_fetch_assoc_prepared('SELECT grid_pollers.lsf_version AS lable,
		grid_pollers.lsf_version  AS value
		FROM grid_pollers JOIN grid_clusters
		ON grid_clusters.poller_id=grid_pollers.poller_id
		WHERE grid_clusters.clusterid = ?',
		array(get_request_var('clusterid')))
	);
}
