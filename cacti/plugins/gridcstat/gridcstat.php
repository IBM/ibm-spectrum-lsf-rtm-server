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
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/grid/lib/grid_filter_functions.php');
include_once('plugins/grid/lib/grid_partitioning.php');
include('./lib/rtm_timespan_settings.php');
include_once('./plugins/RTM/include/fusioncharts/fusioncharts.php');
include_once($config['library_path'] . '/rtm_plugins.php');
include_once($config['library_path'] . '/rtm_functions.php');

$title = __('IBM Spectrum LSF RTM - Historical Statistics', 'gridcstat');

set_default_action();

gridcstat_process_request_vars();

switch (get_request_var('action')) {
	case 'ajaxstats':
		gridcstat_ajax_stats();

		break;
	case 'ajaxsave':
		gridcstat_ajax_save();

		break;
	case 'ajax_rtm_users':
		$sql_where = '';

		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('gridcstat.php', 'job_user', $sql_where, array('0' => __('All', 'gridcstat')));

		break;
	default:
		general_header();
		gridcstat_view_cstats();
		bottom_footer();

		break;
}

function generate_partition_union_query($sql_query, $table_name, $sql_where) {
	$sql_query_set='';
	$tables = partition_get_partitions_for_query($table_name, get_request_var('date1'), get_request_var('date2'));
	foreach ($tables as $table) {
		$query_replaced = '(' . str_replace($table_name, $table, $sql_query);
		if (strlen($sql_query_set) == 0) {
			$sql_query_set = $query_replaced . ' ' . $sql_where . ')';
		} else {
			$sql_query_set = $sql_query_set . ' UNION ALL ' . $query_replaced . ' ' . $sql_where . ')';
		}
	}

	return $sql_query_set;
}

function gridcstat_view_get_cstat_records(&$sql_where, &$group_by, &$table_name, $sort_column, $sort_direction, $dimension = 'user', $page = 1, $row_limit = 30) {
	global $group_function, $summary_stats;

	if (get_request_var('exit') == 'true') {
		$stat = "'ENDED', 'EXITED'";
	} else {
		$stat = "'ENDED'";
	}

	$sql_where  = "WHERE stat IN ($stat) AND exec_host NOT IN ('-', '')";

	/* timespan sql where */
	$db_maint_time = date('H:i:s', strtotime(read_config_option('grid_db_maint_time')));
	if ($_SESSION['sess_cstat_current_timespan'] == '-1') {
		$sql_where  .= " AND interval_start>='" . get_request_var('date1') . " $db_maint_time' AND interval_end<='" . get_request_var('date2') . " $db_maint_time'";
		$table_name = 'grid_job_interval_stats';
		$jobs_col   = 'jobs_reaching_state';
	} else {
		$sql_where  .= " AND interval_start>='" . get_request_var('date1') . " $db_maint_time' AND interval_end<='" . get_request_var('date2') . " $db_maint_time'";
		$table_name = 'grid_job_daily_stats';
		$jobs_col   = 'jobs_in_state';
	}

	$group_by = '';
	/* clusterid sql where */
	if (get_request_var('clusterid') == '-1') {
		$cluster_name = "'N/A' AS clustername";
	} else if (get_request_var('clusterid') == '0') {
		$cluster_name = "'TBD' AS clustername";
		$group_by    .= (strlen($group_by) ? ', ' : '') . ' clusterid';
	} else {
		$cluster_name = "'TBD' AS clustername";
		$group_by    .= (strlen($group_by) ? ', ' : '') . ' clusterid';
		$sql_where   .= ' AND (clusterid=' . get_request_var('clusterid') . ')';
	}

	if (get_request_var('job_user') != '0') {
		$sql_where .= ' AND user=' . db_qstr(get_request_var('job_user'));
	}

	if (get_request_var('project_filter') != '0') {
		$sql_where .= ' AND projectName=' . db_qstr(get_request_var('project_filter'));
	}

	if (get_request_var('app_filter') != '0') {
		$sql_where .= ' AND app=' . db_qstr(get_request_var('app_filter'));
	}

	if (get_request_var('queue_filter') != '0') {
		$sql_where .= ' AND queue=' . db_qstr(get_request_var('queue_filter'));
	}

	/* dimesion sql where */
	switch ($dimension) {
		case 'user':
		case 'queue':
		case 'app':
			$qdimension = $dimension;
			$group_by  .= (strlen($group_by) ? ', ' : '') . " $dimension";
			break;
		case 'project':
			$qdimension = 'projectName';
			$group_by  .= (strlen($group_by) ? ', ' : '') . " $qdimension";
			break;
		case 'host':
			$qdimension = 'exec_host';
			$group_by  .= (strlen($group_by) ? ', ' : '') . " $qdimension";
			break;
	}

	if (strlen($group_by)) {
		$group_by = 'GROUP BY ' . $group_by;
	}

	$sql_query = "SELECT
		$cluster_name,
		clusterid,
		$qdimension,
		SUM(jobs_wall_time) AS wall_time,
		SUM(gpu_wall_time) AS gpu_wall_time,
		SUM(jobs_stime+jobs_utime) AS cpu_time,
		" . (get_request_var('exit') == 'true' ? "SUM(CASE WHEN stat='ENDED' THEN slots_in_state ELSE 0 END) AS slots_done,":'') . "
		" . (get_request_var('exit') == 'true' ? "SUM(CASE WHEN stat='EXITED' THEN slots_in_state ELSE 0 END) AS slots_exited,":'') . "
		SUM(jobs_stime+jobs_utime) / SUM(jobs_wall_time) AS efficiency,
		SUM(slots_in_state) AS total_slots,
		SUM(gpus_in_state) AS total_gpus,
		SUM($jobs_col) AS total_jobs
		FROM ";

	if (read_config_option('grid_partitioning_enable') == '') {
		$sql_query_set   = $sql_query . ' ' . $table_name . ' ' . $sql_where;
	} else {
		$partition_query = 'SELECT * FROM ' . $table_name;
		$partition_union_query = generate_partition_union_query($partition_query, $table_name, $sql_where);
		if (empty($partition_union_query)) {
			return null;
		} else {
			$sql_query_set   = $sql_query . ' (' . $partition_union_query . ') as all_stats_partition';
		}
	}

	$sql_order = get_order_string();

	$sql_query_set .= ' ' . $group_by . ' ORDER BY ' . $sort_column . ' ' . $sort_direction . ' LIMIT ' . ($row_limit*($page-1)) . ',' . $row_limit;

	return db_fetch_assoc($sql_query_set);
}

function gridcstat_view_get_cluster_records($sort_column, $sort_direction) {
	global $timespan, $group_function, $summary_stats;

	if (get_request_var('exit') == 'true') {
		$stat = "'ENDED', 'EXITED'";
	} else {
		$stat = "'ENDED'";
	}

	$sql_where  = "WHERE stat IN ($stat) AND exec_host NOT IN ('-', '')";

	/* timespan sql where */
	$db_maint_time = date('H:i:s', strtotime(read_config_option('grid_db_maint_time')));
	if ($_SESSION['sess_cstat_current_timespan'] == '-1') {
		$sql_where .= " AND interval_start>='" . get_request_var('date1') . " $db_maint_time' AND interval_end<='" . get_request_var('date2') . " $db_maint_time'";
		$table_name = 'grid_job_interval_stats';
		$jobs_col   = 'jobs_reaching_state';
	} else {
		$sql_where .= " AND interval_start>='" . get_request_var('date1') . " $db_maint_time' AND interval_end<='" . get_request_var('date2') . " $db_maint_time'";
		$table_name = 'grid_job_daily_stats';
		$jobs_col   = 'jobs_in_state';
	}

	$group_by = '';
	$clusterid_column = 'clusterid';
	/* clusterid sql where */
	if (get_request_var('clusterid') == '-1') {
		$cluster_name = "'N/A' AS clustername";
		$clusterid_column = "'-1' as clusterid";
	} else if (get_request_var('clusterid') == '0') {
		$cluster_name = "'TBD' AS clustername";
		$group_by    .= (strlen($group_by) ? ', ' : '') . ' clusterid';
	} else {
		$cluster_name = "'TBD' AS clustername";
		$group_by    .= (strlen($group_by) ? ', ' : '') . ' clusterid';
		$sql_where   .= ' AND (clusterid=' . get_request_var('clusterid') . ')';
	}

	if (get_request_var('job_user') != '0') {
		$sql_where   .= ' AND user=' . db_qstr(get_request_var('job_user'));
	}

	if (get_request_var('project_filter') != '0') {
		$sql_where .= ' AND projectName=' . db_qstr(get_request_var('project_filter'));
	}

	if (get_request_var('app_filter') != '0') {
		$sql_where .= ' AND app=' . db_qstr(get_request_var('app_filter'));
	}

	if (get_request_var('queue_filter') != '0') {
		$sql_where .= ' AND queue=' . db_qstr(get_request_var('queue_filter'));
	}

	if (strlen($group_by)) {
		$group_by = 'GROUP BY ' . $group_by;
	}

	$sql_query = "SELECT
		$cluster_name,
		$clusterid_column,
		SUM(jobs_wall_time) AS wall_time,
		SUM(gpu_wall_time) AS gpu_wall_time,
		SUM(jobs_stime+jobs_utime) AS cpu_time,
		" . (get_request_var('exit') == 'true' ? "SUM(CASE WHEN stat='ENDED' THEN slots_in_state ELSE 0 END) AS slots_done,":'') . "
		" . (get_request_var('exit') == 'true' ? "SUM(CASE WHEN stat='EXITED' THEN slots_in_state ELSE 0 END) AS slots_exited,":'') . "
		SUM(jobs_stime+jobs_utime) / SUM(jobs_wall_time) AS efficiency,
		SUM(slots_in_state) AS total_slots,
		SUM(gpus_in_state) AS total_gpus,
		SUM($jobs_col) AS total_jobs
		FROM ";

	if (read_config_option('grid_partitioning_enable') == '') {
		$sql_query_set   = $sql_query . ' ' . $table_name . ' ' . $sql_where;
	} else {
		$partition_query = 'SELECT * FROM ' . $table_name;
		$partition_union_query = generate_partition_union_query($partition_query, $table_name, $sql_where);
		if (empty($partition_union_query)) {
			return null;
		} else {
			$sql_query_set   = $sql_query . ' (' . $partition_union_query . ') as all_stats_partition';
		}
	}

	$sql_query_set .= ' ' . $group_by . ' ORDER BY ' . $sort_column . ' ' . $sort_direction;

	$rows = db_fetch_assoc($sql_query_set);

	if (get_request_var('clusterid') == '-1') {
		if ($rows[0]['total_jobs'] == 0) {
			return array();
		} else {
			return $rows;
		}
	} else {
		return $rows;
	}
}

function gridcstat_build_list($type, $header_items, $sort_column, $sort_direction) {
	global $config;

	print "<style type='text/css'>";
	print ".cstatSelectable { vertical-align: text-top; padding:5px 5px; font-size: 12px; }";
	print ".cstatSelectable td { padding:5px 5px; font-size: 12px; }";
	print ".cstatmenu { margin: 0px; padding: 0px; }";
	print ".cstatmenu td { text-decoration: none; list-style:none; padding: 1px; display: block; }";
	print ".cstatmenu a { text-decoration: none; list-style:none; padding: 2px 2px; display: block; }";
	print "</style>";

	print '<tr>';
	print '<td class="cstatSelectable">';
	print "<div style='vertical-align:top;'>";
	print "<table class='cstatmenu' style='width: 100px;'>";
	print "<tr class='tableHeader'><td>" . __('Report Types', 'gridcstat') . "</td></tr>";

	/* reverse the sort direction */
	if ($sort_direction == 'ASC') {
		$new_sort_direction = 'DESC';
	} else {
		$new_sort_direction = 'ASC';
	}

	$i = 1;
	if (cacti_sizeof($header_items)) {
		foreach ($header_items as $db_column => $display_column) {
			$id = $type . '_' . $sort_column;

			/* by default, you will always sort ascending, with the exception of an already sorted column */
			if ($id == $db_column) {
				$direction    = $new_sort_direction;
				$display_text = $display_column['display'];
			} else {
				$display_text = $display_column['display'];
				$direction    = $display_column['sort'];
			}

			if (($db_column == '') || (substr_count($db_column, 'nosort'))) {
				/* not a sortable row */
			} else {
				print "<tr class='selectable" . ($db_column == $id ? ' selected':'') . "'><td>
					<a id='$id' class='chartpic' data-type='$type' href='gridcstat.php?action=ajaxstats&callback=$type&type=$type&sort_column=$db_column&sort_direction=$direction&add=reset'>" .
						$display_column['display'] . '
					</a>
				</td></tr>';
			}
		}
	}

	print '</table>';
	print '</div>';
	print '</td>';
}

function gridcstat_ajax_save() {
    $settings =
		'clusterid='      . get_request_var('clusterid')    . '|' .
		'rows='           . get_request_var('rows')         . '|' .
		'display='        . get_request_var('display')      . '|' .
		'type='           . get_request_var('type')         . '|' .
		'units='          . get_request_var('units')        . '|' .
		'cluster='        . get_request_var('cluster')      . '|' .
		'host='           . get_request_var('host')         . '|' .
		'queue='          . get_request_var('queue')        . '|' .
		'user='           . get_request_var('user')         . '|' .
		'project='        . get_request_var('project')      . '|' .
		'app='            . get_request_var('app')          . '|' .
		'exit='           . get_request_var('exit')         . '|' .
		'job_user='       . get_request_var('job_user')     . '|' .
		'queue_filter='   . get_request_var('queue_filter') . '|' .
		'app_filter='     . get_request_var('app_filter')   . '|' .
		'project_filter=' . get_request_var('project_filter');

	set_grid_config_option('gridcstat', $settings);
}

function gridcstat_process_request_vars() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_views_setting('gridcstat', 'rows', '10')
		),
		'display' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_views_setting('gridcstat', 'display', -3)
		),
		'upage' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
		),
		'ppage' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
		),
		'hpage' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
		),
		'gpage' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
		),
		'width' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '900'
		),
		'height' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '400'
		),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
		),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_views_setting('gridcstat', 'job_user', '0'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'queue_filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_views_setting('gridcstat', 'queue_filter', '0'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'app_filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_views_setting('gridcstat', 'app_filter', '0'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'project_filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_views_setting('gridcstat', 'project_filter', '0'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'units' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'units', 'auto'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'cluster' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'cluster', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'user' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'user', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'queue', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'app' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'app', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'project', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'host' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'host', 'false'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'exit' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('gridcstat', 'exit', 'true'),
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'wall_time',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

	validate_store_request_vars($filters, 'sess_cstat');

	$filters = array(
	    'clusterid' => array(
	        'filter' => FILTER_VALIDATE_INT,
			'default' => get_views_setting('gridcstat', 'clusterid', read_grid_config_option('default_grid'))
	        )
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	/* set variables for first time use */
	$timespan  = rtm_initialize_timespan($grid_timespans, $grid_timeshifts, 'sess_cstat', 'read_grid_config_option');
	$timeshift = rtm_set_timeshift($grid_timeshifts, 'sess_cstat', 'read_grid_config_option');

	/* process the timespan/timeshift settings */
	rtm_process_html_variables($grid_timespans, $grid_timeshifts, 'sess_cstat', 'read_grid_config_option');
	rtm_process_user_input($timespan, $timeshift, $grid_timespans, 'sess_cstat', 'read_grid_config_option');

	/* save session variables */
	rtm_finalize_timespan($timespan, $grid_timespans, 'sess_cstat', 'read_grid_config_option');

	set_request_var('date1', $_SESSION['sess_cstat_current_date1']);
	set_request_var('date2', $_SESSION['sess_cstat_current_date2']);
}

function gridcstat_view_cstats() {
	global $title, $grid_search_types, $gridcstat_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $group_function, $summary_stats;

	print '<div id="stats_content"></div>';

	?>
	<script type='text/javascript'>
	$(function() {
		$.get('gridcstat.php?action=ajaxstats&init=true&header=fasle&width='+(parseInt($('#main').width())-120)+'&sort_column=<?php print get_request_var('sort_column');?>&sort_direction=<?php print get_request_var('sort_direction');?>', function(data) {
			$('#stats_content').html(data);
			$('#status').html('');
			applySkin();
			applySkinRTM();
		});
	});
	</script>
	<?php
}

function gridcstat_ajax_stats() {
	global $title, $grid_search_types, $gridcstat_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $group_function, $summary_stats;

	if (isset_request_var('init')) {
		$type = '';
	} else {
		$type = get_nfilter_request_var('type');
	}

	if (isset_request_var('callback')) {
		$ajax = true;
	} else {
		$ajax = false;
	}

	if ($type == '' || !isset_request_var('callback')) {
		general_header();

		/* main filter */
		$title = __('Cluster Statistics', 'gridcstat');

		html_start_box("$title <div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div><span id='message'></span>", '100%', '', '3', 'center', '');
		gridcstat_filter_table();
		html_end_box();
	}

	/* cluster statistics */
	if ((get_request_var('cluster') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'cluster')) {
		display_cluster_stats('cluster', $ajax);
	}

	/* user statistics */
	if ((get_request_var('user') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'user')) {
		display_daily_stats('user', $ajax);
	}

	/* queue stats */
	if ((get_request_var('queue') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'queue')) {
		display_daily_stats('queue', $ajax);
	}

	/* application stats */
	if ((get_request_var('app') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'app')) {
		display_daily_stats('app', $ajax);
	}

	/* project stats */
	if ((get_request_var('project') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'project')) {
		display_daily_stats('project', $ajax);
	}

	/* host stats */
	if ((get_request_var('host') == 'true' && !isset_request_var('callback')) ||
		(isset_request_var('callback') && get_nfilter_request_var('callback') == 'host')) {
		display_daily_stats('host', $ajax);
	}

	if ($type == '' && !isset_request_var('callback')) {
		bottom_footer();
	}

	?>
	<script type='text/javascript'>
	var data_type = '';

	$(function() {
		$('.chartpic').off('click').on('click', function(event) {
			event.preventDefault();

			$('#status').show();

			data_type = $(this).attr('data-type');

			$.get($(this).attr('href'), function(data) {
				$('#stats_panel_'+data_type).html(data);
				applySkin();
				applySkinRTM();
				$('#status').hide();
			});
		});

		$('.sortinfo').off('click').on('click', function(data) {
			event.preventDefault();

			data_type = $(this).attr('sort-return');

			$.get($(this).attr('sort-page') + '&sort_column=' + $(this).attr('sort-column') + '&sort_direction=' + $(this).attr('sort-direction'), function(data) {
				$('#stats_panel_'+data_type).html(data);
				applySkin();
				applySkinRTM();
				$('#status').hide();
			});
		});
	});
	</script>
	<?php
}

function gridcstat_display_time($value, $units = 'auto') {
	if ($value < 0) {
		return '-';
	} elseif (($value < 3600 && $units == 'auto') || $units == 'minutes') {
		return number_format(round($value/60)) . ($units == 'auto' ? ' mins':'');
	} elseif (($value < 86400 && $units == 'auto') || $units == 'hours') {
		return number_format(round($value/3600,2),2) . ($units == 'auto' ? ' hrs':'');
	} elseif (($value < 604800 && $units == 'auto') || $units == 'days') {
		return number_format(round($value/86400,2),2) . ($units == 'auto' ? ' days':'');
	} elseif (($value < 2618784 && $units == 'auto') || $units == 'weeks') {
		return number_format(round($value/604800, 2),2) . ($units == 'auto' ? ' wks':'');
	} elseif (($value < 31536000 && $units == 'auto') || $units == 'months') {
		return number_format(round($value/2618784, 2),2) . ($units == 'auto' ? ' mths':'');
	} else {
		return number_format(round($value/31536000, 2),2) . ($units == 'auto' ? ' yrs':'');
	}
}

function determine_sorting($type, &$sort_column, &$sort_direction) {
	if (isset_request_var('clear') || isset_request_var('reset')) {
		$sort_column    = 'wall_time';
		$sort_direction = 'DESC';
	} else {
		if (strpos($sort_column, $type . '_') !== false) {
			$sort_column    = str_replace($type . '_', '', $sort_column);
			$sort_direction = $sort_direction;
		} elseif (isset($_SESSION['sess_cstat_sort_' . $type])) {
			$sort_column    = $_SESSION['sess_cstat_sort_' . $type]['column'];
			$sort_direction = $_SESSION['sess_cstat_sort_' . $type]['direction'];
		} else {
			$sort_column    = 'wall_time';
			$sort_direction = 'DESC';
		}
	}

	if (($sort_column == 'slots_exited' || $sort_column == 'slots_done') && (get_request_var('exit') != 'true')) {
		$sort_column    = 'wall_time';
		$sort_direction = 'DESC';
	}

	$_SESSION['sess_cstat_sort_' . $type]['column']    = $sort_column;
	$_SESSION['sess_cstat_sort_' . $type]['direction'] = $sort_direction;

	set_request_var('sort_column', $sort_column);
	set_request_var('sort_direction', $sort_direction);
}

function gridcstat_encode_fusion_chars($s) {
	$s = str_replace('&', '&amp;', $s);
	$s = str_replace('<', '&lt;', $s);
	$s = str_replace('>', '&gt;', $s);
	$s = str_replace("'", '&apos;', $s);
	$s = str_replace('"', '&quot;', $s);
	return $s;
}

function gridcstat_get_header_suffix($page = '') {
	$units = '';

	if (get_request_var('units') != 'auto') {
		$units = __esc(' (Time in %s', ucfirst(get_request_var('units')), 'gridcstat');
	}

	if (get_request_var('project_filter') != '0' && $page != 'project') {
		$units .= (strlen($units) ? ', ':' (') . __('Project: %s', get_request_var('project_filter'), 'gridcstat');
	}

	if (get_request_var('app_filter') != '0' && $page != 'app') {
		$units .= (strlen($units) ? ', ':' (') . __('Application: %s', get_request_var('app_filter'), 'gridcstat');
	}

	if (get_request_var('queue_filter') != '0' && $page != 'queue') {
		$units .= (strlen($units) ? ', ':' (') . __esc('Queue: %s', get_request_var('queue_filter'), 'gridcstat');
	}

	if (get_request_var('job_user') != '0' && $page != 'user') {
		$units .= (strlen($units) ? ', ':' (') . __esc('User: %s', get_request_var('job_user'), 'gridcstat');
	}

	$units .= (strlen($units) ? ')':'');

	return $units;
}

function display_cluster_stats($type, $ajax = false) {
	global $config;

	$sort_column    = get_request_var('sort_column');
	$sort_direction = get_request_var('sort_direction');

	determine_sorting($type, $sort_column, $sort_direction);

	$units = gridcstat_get_header_suffix($type);

	if (!$ajax) {
		print '<div id="stats_panel_' . $type . '">';
	}

	html_start_box(__esc('Cluster Stats %s', $units, 'gridcstat'), '100%', '', '3', 'center', '');

	$display_text = gridcstat_build_cstat_cluster_display_array();

	if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
		html_header_sort($display_text, $sort_column, $sort_direction, 1, 'gridcstat.php?action=ajaxstats&callback=' . $type . '&type=' . $type . '&add=reset', 'stats_panel_' . $type);
	}

	$stats = gridcstat_view_get_cluster_records($sort_column, $sort_direction);

	if (cacti_sizeof($stats)) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$i = $k = 0;
		foreach ($stats as $stat) {
			if (get_request_var('clusterid') == '-1') {
				$clustername = __('N/A', 'gridcstat');
				$stat['clusterid'] = '-1';
			} else {
				$clustername = grid_get_clustername($stat['clusterid']);
				if ($clustername == '') {
					$clustername = __('Not Found', 'gridcstat');
				}
			}

			$max_value = $stat['wall_time'];
			if (($max_value < 3600 && get_request_var('units') == 'auto') || get_request_var('units') == 'minutes') {
				$caption1 = __('Cluster CPU Stats for %s (in Minutes)', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$caption2 = __('Cluster Job Stats for %s', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$divisor = 60;
			} elseif (($max_value < 86400 && get_request_var('units') == 'auto') || get_request_var('units') == 'hours') {
				$caption1 = __('Cluster CPU Stats for %s (in Hours)', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$caption2 = __('Cluster Job Stats for %s', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$divisor   = 3600;
			} elseif (($max_value < 604800 && get_request_var('units') == 'auto') || get_request_var('units') == 'days') {
				$caption1 = __('Cluster CPU Stats for %s (in Days)', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$caption2 = __('Cluster Job Stats for %s', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$divisor   = 86400;
			} elseif (($max_value < 31449600 && get_request_var('units') == 'auto') || get_request_var('units') == 'weeks') {
				$caption1 = __('Cluster CPU Stats for %s (in Weeks)', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$caption2 = __('Cluster Job Stats for %s', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$divisor   = 604800;
			} else {
				$caption1 = __('Cluster CPU Stats for %s (in Months)', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$caption2 = __('Cluster Job Stats for %s', gridcstat_encode_fusion_chars($clustername), 'gridcstat');
				$divisor   = 2628000;
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
				$strXML[$i]   = "<chart caption='" . $caption1 . "' palette='1' animation='1' showAboutMenuItem='0' pieYScale='20' pieRadius='100' formatNumberScale='1' numberPrefix='' pieSliceDepth='15' startingAngle='120' $fusion_theme exportEnabled='1'>";
				$strXML[$i+1] = "<chart caption='" . $caption2 . "' palette='5' animation='1' showAboutMenuItem='0' pieYScale='20' pieRadius='100' formatNumberScale='1' numberPrefix='' pieSliceDepth='15' startingAngle='120' $fusion_theme exportEnabled='1'>";
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
			 	print "<tr class='odd tableRow'>\n";
				//form_alternate_row('line' . $i, true);
				if ($clustername == 'N/A') {
					form_selectable_cell(__('N/A', 'gridcstat'), $i);
				} elseif ($clustername == 'Not Found') {
					form_selectable_cell(__('Not Found', 'gridcstat'), $i);
				} else {
					form_selectable_cell(html_escape($clustername), $i);
				}
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
				$strXML[$i]   .= "<set label='Other Time' value='" . (($stat['wall_time']-$stat['cpu_time'])/$divisor) . "' />";
				$strXML[$i]   .= "<set label='CPU Time' value='" . ($stat['cpu_time']/$divisor) . "' />";

				if (get_request_var('exit') == 'true') {
					$link_url_base  = '../grid/grid_dailystats.php?query=1%26summarize=true%26rows=30%26filter=%26exec_host=-1%26units=' . get_request_var('units') . '%26clusterid=' . $stat['clusterid'] . '%26date1=' . get_request_var('date1') . '%26date2=' . get_request_var('date2');
					$link_url_base_all = '%26queue=-1%26project=-1%26exec_host=-1%26job_user=-1';
					$link_url_base_cluster = '%26queue=' . get_request_var('queue_filter') . '%26app=' . get_request_var('app_filter')  . '%26project=' . get_request_var('project_filter') . '%26job_user=' . get_request_var('job_user');
					if ($stat['clusterid'] == '-1') {
						$link_done = $link_url_base . $link_url_base_all . '%26stat=ENDED';
						$link_exit = $link_url_base . $link_url_base_all . '%26stat=EXITED';
					} else {
						$link_done = $link_url_base . $link_url_base_cluster . '%26stat=ENDED';
						$link_exit = $link_url_base . $link_url_base_cluster . '%26stat=EXITED';
					}
					$strXML[$i+1] .= "<set label='Done " . format_job_slots() . "' value='" . $stat['slots_done'] . "' link='". $link_done . "' />";
					$strXML[$i+1] .= "<set label='Exit " . format_job_slots() . "' value='" . $stat['slots_exited'] . "' link='". $link_exit . "' />";
				}
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
				form_selectable_cell(gridcstat_display_time($stat['wall_time'] ,get_request_var('units')), $i, '', 'right');
				form_selectable_cell(gridcstat_display_time($stat['gpu_wall_time'] ,get_request_var('units')), $i, '', 'right');
				form_selectable_cell(gridcstat_display_time($stat['cpu_time'], get_request_var('units')), $i, '', 'right');
				form_selectable_cell(round($stat['efficiency'] * 100,2) . '%', $i, '', 'right');
				form_selectable_cell(number_format_i18n($stat['total_jobs'], 2, 1000), $i, '', 'right');
				form_selectable_cell(number_format_i18n($stat['total_slots'], 2, 1000), $i, '', 'right');

				if (get_request_var('exit') == 'true') {
					form_selectable_cell(number_format_i18n($stat['slots_done'], 2, 1000), $i, '', 'right');
					form_selectable_cell(number_format_i18n($stat['slots_exited'], 2, 1000), $i, '', 'right');
				}
				form_selectable_cell(number_format_i18n($stat['total_gpus'], 2, 1000), $i, '', 'right');
			}

			form_end_row();

			if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
				$strXML[$i] .= '</chart>';
				$strXML[$i+1] .= '</chart>';
			}
			$i += 2;
		}

		if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
			html_end_box(false);
			html_start_box('', '100%', '', '3', 'center', '');

			for ($j = 0; $j <= $i-2; $j = $j + 2) {
				print '<tr>';
				print "<td style='width:50%;text-align:center;' id='cpustats-$j' class='cchart'></td>";
				$cstate = new FusionCharts('Pie3D', 'cpustats' . $j , get_request_var('width') / 2, 300, 'cpustats-' . $j, 'xml', str_replace("'", "\\'", $strXML[$j]));
				$cstate->render();
				if (get_request_var('exit') == 'true') {
					print '</td>';
					print "<td style='width:50%;text-align:center;' id='jobstats-" . ($j+1) . "' class='cchart'></td>";
					$jobstats = new FusionCharts('Pie3D', 'jobstats' . ($j+1) , get_request_var('width') / 2, 300, 'jobstats-' . ($j+1), 'xml', str_replace("'", "\\'", $strXML[$j+1]));
					$jobstats->render();
				}

				$chart1 = 'cpustats' . $j;
				$chart2 = 'jobstats' . ($j + 1);

				print "<script type='text/javascript'>
				$(function() {
					$(window).resize(function() {
						width = parseInt($('#main').width() / 2);
						if ($('#$chart1').length) {
							FusionCharts('$chart1').resizeTo(width, 300);
						}

						if ($('#$chart2').length) {
							FusionCharts('$chart2').resizeTo(width, 300);
						}
					});
				});
				</script>";

				print '</tr>';
			}
		}
	} else {
		print '<tr><td colspan="7"><em>' . __('No Cluster Statistics Found', 'gridcstat') . '</em></td></tr>';
	}

	html_end_box();

	if (!$ajax) {
		print '</div>';
	}
}

function display_daily_stats($type, $ajax = false) {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $group_function, $summary_stats;

	$sql_where  = '';
	$group_by   = '';
	$table_name = 'grid_job_daily_stats';

	if (get_request_var('rows') == -1) {
		$row_limit = 10;
	} elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows');
	}

	$units = gridcstat_get_header_suffix($type);

	$sort_column    = get_request_var('sort_column');
	$sort_direction = get_request_var('sort_direction');

	determine_sorting($type, $sort_column, $sort_direction);

	switch($type) {
		case 'user':
		case 'project':
		case 'app':
		case 'host':
		case 'queue':
			$page = get_request_var('upage');
			break;
	default:
		$page = 1;
		break;
	}

	if (empty($page)) {
		$page = 1;
	}

	$stats = gridcstat_view_get_cstat_records($sql_where, $group_by, $table_name, $sort_column, $sort_direction, $type, $page, $row_limit);

	if (read_config_option('grid_partitioning_enable') == '') {
		$rows_query_string = "SELECT COUNT(interval_end)
			FROM $table_name
			$sql_where
			$group_by";
		$total_rows = cacti_sizeof(db_fetch_assoc($rows_query_string));
	} else {
		$partition_query   = "SELECT * FROM grid_job_daily_stats";
		$partition_union_query = generate_partition_union_query($partition_query, "grid_job_daily_stats", $sql_where);
		if (empty($partition_union_query)) {
			$total_rows = 0;
		} else {
			$rows_query_string = "SELECT COUNT(interval_end) FROM (" . $partition_union_query . ") AS all_stats_partition " . $group_by;
			$total_rows = cacti_sizeof(db_fetch_assoc($rows_query_string));
		}
	}

	/* make a pass to get the desired units */
	$max_value = 0;
	if (cacti_sizeof($stats)) {
		foreach ($stats as $stat) {
			if ($stat[$sort_column] > $max_value) {
				$max_value = $stat[$sort_column];
			}
		}
	}

	/* get the units and title values */
	switch($sort_column) {
		case 'efficiency':
			$title_suffix = __('Average Efficiency', 'gridcstat');
			$units_suffix = __(' (in Percent)', 'gridcstat');
			$divisor = 0.01;

			break;
		case 'wall_time':
		case 'gpu_wall_time':
		case 'cpu_time':
			if ($sort_column == 'wall_time') {
				$title_suffix = __('Wall Time', 'gridcstat');
			}else if ($sort_column == 'gpu_wall_time') {
				$title_suffix = __('GPU Wall Time(Excl.)', 'gridcstat');
			} else {
				$title_suffix = __('CPU Time', 'gridcstat');
			}

			if (($max_value < 3600 && get_request_var('units') == 'auto') || get_request_var('units') == 'minutes') {
				$units_suffix = __(' (in Minutes)', 'gridcstat');
				$divisor = 60;
			} elseif (($max_value < 86400 && get_request_var('units') == 'auto') || get_request_var('units') == 'hours') {
				$units_suffix = __(' (in Hours)', 'gridcstat');
				$divisor   = 3600;
			} elseif (($max_value < 604800 && get_request_var('units') == 'auto') || get_request_var('units') == 'days') {
				$units_suffix = __(' (in Days)', 'gridcstat');
				$divisor   = 86400;
			} elseif (($max_value < 31449600 && get_request_var('units') == 'auto') || get_request_var('units') == 'weeks') {
				$units_suffix = __(' (in Weeks)', 'gridcstat');
				$divisor   = 604800;
			} else {
				$units_suffix = __(' (in Months)', 'gridcstat');
				$divisor   = 3144960;
			}

			break;
		default:
			if ($sort_column == 'total_jobs') {
				$title_suffix = __('Job', 'gridcstats');
			} else if ($sort_column == 'total_gpus') {
				$title_suffix = __('GPU', 'gridcstats');
			} else {
				$title_suffix = format_job_slots();
			}

			if ($max_value > 1000000) {
				$units_suffix = __(' (in Millions)', 'gridcstats');
				$divisor   = 1000000;
			} elseif ($max_value > 100000) {
				$units_suffix = __(' (in Thousands)', 'gridcstats');
				$divisor   = 1000;
			} else {
				$units_suffix = '';
				$divisor   = 1;
			}

			break;
	}

	switch($sort_column) {
		case 'efficiency':
			$title = __('Average Job Efficiency', 'gridcstat');

			break;
		case 'wall_time':
			$title = __('Total Wall Time', 'gridcstat');

			break;
		case 'gpu_wall_time':
			$title = __('Total GPU Wall Time(Excl.)', 'gridcstat');

			break;
		case 'cpu_time':
			$title = __('Total CPU Time', 'gridcstat');

			break;
		case 'total_jobs':
			$title = __('Total Jobs', 'gridcstat');

			break;
		case 'total_slots':
			$title = __('Total %s', format_job_slots(false,true), 'gridcstat');

			break;
		case 'slots_done':
			$title = __('Done %s', format_job_slots(), 'gridcstat');

			break;
		case 'slots_exited':
			$title = __('Exited %s', format_job_slots(), 'gridcstat');

			break;
		case 'total_gpus':
			$title = __('Total GPUs', 'gridcstat');

			break;
	}

	if (get_request_var('clusterid') == 0) {
		$title .= __(' for All Clusters', 'gridcstat');
	} elseif (get_request_var('clusterid') == -1) {
		$title .= __(' Consolidated from All Clusters', 'gridcstat');
	} else {
		$title .= __(' for Cluster %s', grid_get_clustername(get_request_var('clusterid')), 'gridcstat');
	}

	if (!$ajax) {
		print '<div id="stats_panel_' . $type . '">';
	}

	html_start_box(ucfirst($type) . ' Stats (Showing ' . ($sort_direction == 'ASC' ? 'Bottom ':'Top ') . ((($total_rows < $row_limit) || ($total_rows < ($row_limit*$page))) ? $total_rows : ($row_limit*$page)) . " of $total_rows)$units", '100%', '', '3', 'center', '');

	if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$strXML = "<chart useRoundEdges='1' labelDisplay='ROTATE' slantLabels='1' showValues='0' caption='" . ($sort_direction == 'ASC' ? 'Bottom ':'Top ') . $row_limit . ' ' . ucfirst($type) . 's Showing - ' . $title . "' yAxisName='$title_suffix $units_suffix' xAxisName='" . ucfirst($type) . "' formatNumberScale='0' $fusion_theme exportEnabled='1'>";
	}

	$display_text = gridcstat_build_cstat_display_array($type);
	if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
		html_header_sort($display_text, $sort_column, $sort_direction, 1, 'gridcstat.php?action=ajaxstats&callback=' . $type . '&type=' . $type . '&add=reset', 'stats_panel_' . $type);
	}

	$i = 0;
	if (cacti_sizeof($stats)) {
		foreach ($stats as $stat) {
			if (get_request_var('clusterid') == '-1') {
				$clustername = __('N/A', 'gridcstat');
				$stat['clusterid'] = '-1';
			} else {
				$clustername = grid_get_clustername($stat['clusterid']);
				if ($clustername == '') {
					$clustername = __('Not Found', 'gridcstat');
				}
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
				print "<tr class='odd tableRow'>\n";
				//form_alternate_row('line' . $i, true);
				form_selectable_cell(html_escape($clustername), $i);
			}

			switch($type) {
			case 'user':
				if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
					$link = '../grid/grid_dailystats.php' .
						'?query=1' .
						'%26units=' . get_request_var('units') .
						'%26clusterid=' . $stat['clusterid'] .
						'%26stat=-1' .
						'%26rows=30' .
						'%26job_user=' . $stat['user'].
						'%26queue=' . get_request_var('queue_filter') .
						'%26app=' . get_request_var('app_filter') .
						'%26project=' . get_request_var('project_filter') .
						'%26exec_host=-1' .
						'%26filter=' .
						'%26date1=' . get_request_var('date1') .
						'%26date2=' . get_request_var('date2') .
						'%26summarize=true';

					$strXML .= "<set label='" . (get_request_var('clusterid') == 0 ? gridcstat_encode_fusion_chars($clustername) . ' - ': '') . $stat[$type] .
						"' value='" . round($stat[$sort_column] / $divisor, 2) .
						"' link='". $link .  "' />";
				}

				if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
					form_selectable_cell($stat[$type], $i);
				}

				break;
			case 'queue':
				if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
					$link = '../grid/grid_dailystats.php' .
						'?query=1' .
						'%26units=' . get_request_var('units') .
						'%26clusterid=' . $stat['clusterid'] .
						'%26stat=-1' .
						'%26rows=30' .
						'%26queue=' . gridcstat_encode_fusion_chars($stat['queue']) .
						'%26app=' . get_request_var('app_filter') .
						'%26project=' . get_request_var('project_filter') .
						'%26exec_host=-1' .
						'%26filter=' .
						'%26date1=' . get_request_var('date1') .
						'%26date2=' . get_request_var('date2') .
						'%26job_user=' . get_request_var('job_user') .
						'%26summarize=true';

					$strXML .= "<set label='" . (get_request_var('clusterid') == 0 ? gridcstat_encode_fusion_chars($clustername) . ' - ': '') . gridcstat_encode_fusion_chars($stat[$type]) .
						"' value='" . round($stat[$sort_column] / $divisor,2) .
						"' link='". $link .  "' />";
				}

				if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
					form_selectable_cell($stat['queue'], $i);
				}

				break;
			case 'app':
				if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
					$link = '../grid/grid_dailystats.php' .
						'?query=1' .
						'%26units=' . get_request_var('units') .
						'%26clusterid=' . $stat['clusterid'] .
						'%26stat=-1' .
						'%26rows=30' .
						'%26queue=' . get_request_var('queue_filter') .
						'%26app=' . gridcstat_encode_fusion_chars($stat['app']) .
						'%26project=' . get_request_var('project_filter') .
						'%26exec_host=-1' .
						'%26filter=' .
						'%26date1=' . get_request_var('date1') .
						'%26date2=' . get_request_var('date2') .
						'%26job_user=' . get_request_var('job_user') .
						'%26summarize=true';

					$strXML .= "<set label='" . (get_request_var('clusterid') == 0 ? gridcstat_encode_fusion_chars($clustername) . ' - ': '') . gridcstat_encode_fusion_chars($stat[$type]) .
						"' value='" . round($stat[$sort_column] / $divisor,2) .
						"' link='". $link .  "' />";
				}

				if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
					form_selectable_cell($stat['app'], $i);
				}

				break;
			case 'project':
				if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
					$link = '../grid/grid_dailystats.php' .
						'?query=1' .
						'%26units=' . get_request_var('units') .
						'%26clusterid=' . $stat['clusterid'] .
						'%26stat=-1' .
						'%26rows=30' .
						'%26queue=' . get_request_var('queue_filter') .
						'%26app=' . get_request_var('app_filter') .
						'%26project=' . gridcstat_encode_fusion_chars($stat['projectName']) .
						'%26exec_host=-1' .
						'%26filter=' .
						'%26date1=' . get_request_var('date1') . '%26date2=' . get_request_var('date2') . '%26job_user=' . get_request_var('job_user') . '%26summarize=true';

					$strXML .= "<set label='" . (get_request_var('clusterid') == 0 ? gridcstat_encode_fusion_chars($clustername) . " - ": "") . gridcstat_encode_fusion_chars($stat["projectName"]) .
						"' value='" . round($stat[$sort_column] / $divisor,2) .
						"' link='". $link .  "' />";
				}

				if (get_request_var('display') == "-2" || get_request_var('display') == "-1") {
					form_selectable_cell($stat['projectName'], $i);
				}

				break;
			case 'host':
				if ($stat['exec_host'] == '-' || $stat['exec_host'] == '') $stat['exec_host'] = 'Did Not Start';

				if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
					$link = '../grid/grid_dailystats.php' .
						'?query=1' .
						'%26units=' . get_request_var('units') .
						'%26clusterid=' . $stat['clusterid'] .
						'%26stat=-1' .
						'%26rows=30' .
						'%26queue=' . get_request_var('queue_filter') .
						'%26app=' . get_request_var('app_filter') .
						'%26project=' . get_request_var('project_filter') .
						'%26exec_host=' . $stat['exec_host'].
						'%26filter=' .
						'%26date1=' . get_request_var('date1') .
						'%26date2=' . get_request_var('date2') .
						'%26job_user=' . get_request_var('job_user') .
						'%26summarize=true';

					$strXML .= "<set label='" . (get_request_var('clusterid') == 0 ? gridcstat_encode_fusion_chars($clustername) . ' - ': '') . $stat['exec_host'] .
						"' value='" . round($stat[$sort_column] / $divisor,2) .
						"' link='". $link .  "' />";
				}

				if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
					form_selectable_cell($stat['exec_host'], $i);
				}

				break;
			}

			if (get_request_var('display') == '-2' || get_request_var('display') == '-1') {
				form_selectable_cell(gridcstat_display_time($stat['wall_time'], get_request_var('units')), $i, '', 'right');
				form_selectable_cell(gridcstat_display_time($stat['gpu_wall_time'], get_request_var('units')), $i, '', 'right');
				form_selectable_cell(gridcstat_display_time($stat['cpu_time'], get_request_var('units')), $i, '', 'right');
				form_selectable_cell(number_format_i18n($stat['efficiency'] * 100, 2) . '%', $i, '', 'right');
				form_selectable_cell(number_format_i18n($stat['total_jobs']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($stat['total_slots']), $i, '', 'right');

				if (get_request_var('exit') == 'true') {
					form_selectable_cell(number_format_i18n($stat['slots_done']), $i, '', 'right');
					form_selectable_cell(number_format_i18n($stat['slots_exited']), $i, '', 'right');
				}
				form_selectable_cell(number_format_i18n($stat['total_gpus'], 2, 1000), $i, '', 'right');
			}
		}

		form_end_row();

		if (get_request_var('display') == '-2' || get_request_var('display') == '-3') {
			html_end_box(false);

			html_start_box('', '100%', '', '3', 'center', '');

			gridcstat_build_list($type, $display_text, $sort_column, $sort_direction);

			$strXML .= '</chart>';
			print '<td id="' . $type . '-' . $i . '" style="text-align:center" class="fchart"></td>';
			$cstate = new FusionCharts('Column3D', $type . $i, get_request_var('width') - 80, 400, $type . '-'. $i, 'xml', str_replace("'", "\\'", $strXML));
			$cstate->render();

			$chart1 = $type . $i;

			print "<script type='text/javascript'>
			$(function() {
				$(window).resize(function() {
					width = parseInt($('#main').width() - 180);
					if ($('#$chart1').length) {
						FusionCharts('$chart1').resizeTo(width, 400);
					}
				});
			});
			</script>";

			print '</tr>';

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No %s Statistics Found', ucfirst($type), 'gridcstat') . '</em></td></tr>';
	}

	html_end_box();

	if (!$ajax) {
		print '</div>';
	}
}

function gridcstat_build_cstat_cluster_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Cluster Name', 'gridcstat'),
			'sort' => 'ASC'
		),
		'cluster_wall_time' => array(
			'display' => __('Wall Time', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cluster_gpu_wall_time' => array(
			'display' => __('GPU Wall Time(Excl.)', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cluster_cpu_time' => array(
			'display' => __('CPU Time', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cluster_efficiency' => array(
			'display' => __('Efficiency', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cluster_total_jobs' => array(
			'display' => __('Total Jobs', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cluster_total_slots' => array(
			'display' => __('Total %s', format_job_slots(false,true), 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	if (get_request_var('exit') == 'true') {
		$display_text += array(
			'cluster_slots_done' => array(
				'display' => __('Done %s', format_job_slots(), 'gridcstat'),
				'align' => 'right',
				'sort' => 'DESC'
			)
		);

		$display_text += array(
			'cluster_slots_exited' => array(
				'display' => __('Exited %s', format_job_slots(), 'gridcstat'),
				'align' => 'right',
				'sort' => 'DESC'
			)
		);
	}
	$display_text += array(
		'cluster_total_gpus' => array(
			'display' => __('Total GPUs', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);
	return $display_text;
}

function gridcstat_build_cstat_display_array($type) {
	$display_text = array(
		'nosort' => array(
			'display' => __('Cluster Name', 'gridcstat'),
			'sort'    => 'ASC'
		)
	);

	$display_text += array(
		'nosort1' => array(
			'display' => __('%s Name', ucfirst($type), 'gricstat'),
			'sort'    => 'ASC'
		)
	);

	$display_text += array(
		$type .'_wall_time' => array(
			'display' => __('Wall Time', 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		$type .'_gpu_wall_time' => array(
			'display' => __('GPU Wall Time(Excl.)', 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		$type .'_cpu_time' => array(
			'display' => __('CPU Time', 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		$type .'_efficiency' => array(
			'display' => __('Efficiency', 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		$type .'_total_jobs' => array(
			'display' => __('Total Jobs', 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		$type .'_total_slots' => array(
			'display' => __('Total %s', format_job_slots(false, true), 'gridcstat'),
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	if (get_request_var('exit') == 'true') {
		$display_text += array(
			$type . '_slots_done' => array(
				'display' => __('Done %s', format_job_slots(), 'gridcstat'),
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			$type . '_slots_exited' => array(
				'display' => __('Exited %s', format_job_slots(), 'gridcstat'),
				'align'   => 'right',
				'sort'    => 'DESC'
			)
		);
	}
	$display_text += array(
		$type .'_total_gpus' => array(
			'display' => __('Total GPUs', 'gridcstat'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);
	return $display_text;
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

function gridcstat_filter_table() {
	global $config, $gridcstat_rows_selector;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;
	global $gridcstat_time_range;

	if (read_config_option('grid_partitioning_enable') == '') {
		$min_start = db_fetch_cell('SELECT MIN(interval_start) FROM grid_job_daily_stats');
	} else {
		//$timespan = get_request_var('timespan');
		$min_start = db_fetch_cell("SELECT MIN(min_time) FROM grid_table_partitions WHERE table_name='grid_job_daily_stats'");

		/* no partitions are created yet */
		if (strlen($min_start) == 0) {
			$min_start = db_fetch_cell('SELECT MIN(interval_start) FROM grid_job_daily_stats');
		}
	}
	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>

			function applyFilter() {
				var strURL  = 'gridcstat.php?action=ajaxstats&header=false&init=true&clusterid=' + $('#clusterid').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&job_user=' + $('#job_user').val();
				strURL += '&queue_filter=' + $('#queue_filter').val();
				strURL += '&app_filter=' + $('#app_filter').val();
				strURL += '&project_filter=' + $('#project_filter').val();
				strURL += '&units=' + $('#units').val();
				strURL += '&cluster=' + $('#views option[value=cluster]').is(':selected');
				strURL += '&user=' + $('#views option[value=user]').is(':selected');
				strURL += '&project=' + $('#views option[value=project]').is(':selected');
				strURL += '&app=' + $('#views option[value=app]').is(':selected');
				strURL += '&queue=' + $('#views option[value=queue]').is(':selected');
				strURL += '&host=' + $('#views option[value=host]').is(':selected');
				strURL += '&exit=' + $('#exit').is(':checked')
				strURL += '&display=' + $('#display').val();

				if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
					strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				} else {
					strURL += '&date1=' + $('#date1').val();
					strURL += '&date2=' + $('#date2').val();
				}

				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

				$('#status').show();
				$('#views').multiselect('close');
				$.get(strURL, function(data) {
			        $('#stats_content').html(data);
			        $('#status').hide();
			        applySkin();
					applySkinRTM();
			    });
			}

			function applyFilterChangePDTS() {
				var strURL  = 'gridcstat.php?action=ajaxstats&header=false&predefined_timespan=' + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

				$('#status').show();
				$('#views').multiselect('close');

				$.get(strURL, function(data) {
			        $('#stats_content').html(data);
			        $('#status').hide();

			        applySkin();
					applySkinRTM();
			    });
			}

			function moveRight() {
				var strURL  = 'gridcstat.php?action=ajaxstats&header=false&move_right_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

				$('#status').show();
				$('#views').multiselect('close');

				$.get(strURL, function(data) {
			        $('#stats_content').html(data);
			        $('#status').hide();

			        applySkin();
					applySkinRTM();
			    });
			}

			function moveLeft() {
				var strURL  = 'gridcstat.php?action=ajaxstats&header=false&move_left_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

				$('#status').show();
				$('#views').multiselect('close');

				$.get(strURL, function(data) {
			        $('#stats_content').html(data);
			        $('#status').hide();

			        applySkin();
					applySkinRTM();
			    });
			}

			function filterSave() {
				var strURL  = 'gridcstat.php';
				strURL += '?action=ajaxsave';
				strURL += '&clusterid='      + $('#clusterid').val();
				strURL += '&display='        + $('#display').val();
				strURL += '&rows='           + $('#rows').val();
				strURL += '&job_user='       + $('#job_user').val();
				strURL += '&queue_filter='   + $('#queue_filter').val();
				strURL += '&app_filter='     + $('#app_filter').val();
				strURL += '&project_filter=' + $('#project_filter').val();
				strURL += '&units='          + $('#units').val();
				strURL += '&exit='           + $('#exit').is(':checked');

				strURL += '&predefined_timespan='  + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();

				var selectedViews    = $('#views').children('option:selected');
				var notSelectedViews = $('#views').children('option:not(:selected)');

				selectedViews.each(function() {
					strURL += '&' + $(this).attr('value') + '=true';
				});

				notSelectedViews.each(function() {
					strURL += '&' + $(this).attr('value') + '=false';
				});

				$.get(strURL, function() {
					$('#status').show();
					$('#message').text('').show().html('<?php print __('Filter Settings Saved', 'gridcstat');?>').delay(2000).fadeOut(1000);
					$('#status').hide();
				});
			}

			function clearFilterSelections() {
				var strURL = 'gridcstat.php?action=ajaxstats&header=false&clear=1';

				$('#status').show();

				$.get(strURL, function(data) {
					$('#stats_content').html(data);
					$('#status').hide();

					applySkin();
					applySkinRTM();
				});
			}

			$(function() {
				date1='<?php print $_SESSION['sess_cstat_current_date1'];?>';
				date2='<?php print $_SESSION['sess_cstat_current_date2'];?>';

				$('#views').multiselect({
					header: '<?php print __('Choose a View', 'gridcstat');?>',
					minWidth: 140
				});

				$('#clusterid, #display, #rows, #views, #exit, #job_user, #queue_filter, #app_filter, #project_filter, #units').change(function() {
					applyFilter();
				});

				$('#predefined_timespan').change(function() {
					applyFilterChangePDTS();
				});

				$('#view_cstat').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilterSelections();
				});

				$('#save').click(function() {
					filterSave();
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

				applySkinRTM();

				$('#status').hide();
			});

			</script>
			<form id='view_cstat' action='gridcstat.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridcstat');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='-1'<?php if (get_request_var('clusterid') == '-1') {?> selected<?php }?>><?php print __('N/A', 'gridcstat');?></option>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'gridcstat');?></option>
							<?php
							$clusters = grid_get_clusterlist();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Display', 'gridcstat');?>
					</td>
					<td>
						<select id='display'>
							<option value='-1'<?php if (get_request_var('display') == '-1') {?> selected<?php }?>><?php print __('Statistics', 'gridcstat');?></option>
							<option value='-3'<?php if (get_request_var('display') == '-3') {?> selected<?php }?>><?php print __('Graphs', 'gridcstat');?></option>
							<option value='-2'<?php if (get_request_var('display') == '-2') {?> selected<?php }?>><?php print __('Both', 'gridcstat');?></option>
						</select>
					</td>
					<td>
						<?php print __('Top', 'gridcstat');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($gridcstat_rows_selector)) {
								foreach ($gridcstat_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print 'selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Views', 'gridcstat');?>
					</td>
					<td>
						<select id='views' multiple='multiple' style='display:none'>
							<option value='cluster' <?php print (get_request_var('cluster') == 'true' ? 'selected':'');?>><?php print __('Clusters', 'gridcstat');?></option>
							<option value='user' <?php print (get_request_var('user') == 'true' ? 'selected':'');?>><?php print __('Users', 'gridcstat');?></option>
							<option value='queue' <?php print (get_request_var('queue') == 'true' ? 'selected':'');?>><?php print __('Queues', 'gridcstat');?></option>
							<option value='app' <?php print (get_request_var('app') == 'true' ? 'selected':'');?>><?php print __('Applications', 'gridcstat');?></option>
							<option value='project' <?php print (get_request_var('project') == 'true' ? 'selected':'');?>><?php print __('Projects', 'gridcstat');?></option>
							<option value='host' <?php print (get_request_var('host') == 'true' ? 'selected':'');?>><?php print __('Hosts', 'gridcstat');?></option>
						</select>
					</td>
					<td>
						<input id='exit' type='checkbox' <?php if (isset_request_var('exit') && (get_request_var('exit') == 'true')) print ' checked';?>>
					</td>
					<td>
						<label for='exit'><?php print __('Include Exited Jobs', 'gridcstat');?></label>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'gridcstat');?>' title='<?php print __esc('Search', 'gridcstat');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'gridcstat');?>' title='<?php print __esc('Clear Filters', 'gridcstat');?>'>
							<input type='button' id='save' value='<?php print __esc('Save', 'gridcstat');?>' title='<?php print __esc('Save Filters', 'gridcstat');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<?php print html_autocomplete_filter('gridcstat.php', __('User', 'gridcstat'), 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('0' => __('All', 'gricstat')));?>
					<td>
						<?php print __('Queue', 'gridcstat');?>
					</td>
					<td>
						<input type='hidden' id='ajax_queue_query'>
						<input type='hidden' id='queue_clusterid'>
						<select id='queue_filter'>
							<option value='0'<?php if (get_request_var('queue_filter') == '0') {?> selected<?php }?>><?php print __('All', 'gridcstat');?></option>
							<?php

							if (get_request_var('clusterid') <= 0) {
								$queues = db_fetch_assoc('SELECT DISTINCT queuename
									FROM grid_queues
									ORDER BY queuename');
							} else {
								$queues = db_fetch_assoc_prepared('SELECT DISTINCT queuename
									FROM grid_queues
									WHERE clusterid = ?
									ORDER BY queuename',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue['queuename'] .'"'; if (str_replace("\\\\", "\\", get_request_var('queue_filter')) == $queue['queuename']) { print ' selected'; } print '>' . $queue['queuename'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Project', 'gridcstat');?>
					</td>
					<td>
						<input type='hidden' id='ajax_project_query'>
						<input type='hidden' id='project_clusterid'>
						<select id='project_filter'>
							<option value='0'<?php if (get_request_var('project_filter') == '0') {?> selected<?php }?>><?php print __('All', 'gridcstat');?></option>
							<?php

							if (get_request_var('clusterid') <= 0) {
								$projects = db_fetch_assoc("SELECT DISTINCT projectName
									FROM grid_projects
									ORDER BY projectName");
							} else {
								$projects = db_fetch_assoc_prepared('SELECT DISTINCT projectName
									FROM grid_projects
									WHERE clusterid = ?
									ORDER BY projectName',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($projects)) {
								foreach ($projects as $project) {
									print '<option value="' . $project['projectName'] .'"'; if (str_replace("\\\\", "\\", get_request_var('project_filter')) == $project['projectName']) { print ' selected'; } print '>' . $project['projectName'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('App', 'gridcstat');?>
					</td>
					<td>
						<input type='hidden' id='ajax_app_query'>
						<input type='hidden' id='app_clusterid'>
						<select id='app_filter'>
							<option value='0'<?php if (get_request_var('app_filter') == '0') {?> selected<?php }?>><?php print __('All', 'gridcstat');?></option>
							<?php

							if (get_request_var('clusterid') <= 0) {
								$projects = db_fetch_assoc("SELECT DISTINCT appName
									FROM grid_applications
									ORDER BY appName");
							} else {
								$projects = db_fetch_assoc_prepared('SELECT DISTINCT appName
									FROM grid_applications
									WHERE clusterid = ?
									ORDER BY appName',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($projects)) {
								foreach ($projects as $project) {
									print '<option value="' . $project['appName'] .'"'; if (str_replace("\\\\", "\\", get_request_var('app_filter')) == $project['appName']) { print ' selected'; } print '>' . $project['appName'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Unit', 'gridcstat');?>
					</td>
					<td>
						<select id='units'>
							<option value='auto' <?php print (get_request_var('units') == 'auto' ? 'selected':'');?>><?php print __('Auto', 'gridcstat');?></option>
							<option value='minutes' <?php print (get_request_var('units') == 'minutes' ? 'selected':'');?>><?php print __('Minutes', 'gridcstat');?></option>
							<option value='hours' <?php print (get_request_var('units') == 'hours' ? 'selected':'');?>><?php print __('Hours', 'gridcstat');?></option>
							<option value='days' <?php print (get_request_var('units') == 'days' ? 'selected':'');?>><?php print __('Days', 'gridcstat');?></option>
							<option value='weeks' <?php print (get_request_var('units') == 'weeks' ? 'selected':'');?>><?php print __('Weeks', 'gridcstat');?></option>
							<option value='months' <?php print (get_request_var('units') == 'months' ? 'selected':'');?>><?php print __('Months', 'gridcstat');?></option>
						</select>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Presets');?>
					</td>
					<td>
						<select id='predefined_timespan'>
							<?php
							if ($_SESSION['sess_cstat_custom']) {
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

							if (cacti_sizeof($grid_timespans)) {
								if ($start_val == 0) {
									print "<option value='0'"; if ($_SESSION['sess_cstat_current_timespan'] == '0') { print ' selected'; } print '>' . __('Custom', 'gridcstat') . '</option>';
								}
								print "<option value='-1'"; if ($_SESSION['sess_cstat_current_timespan'] == '-1') { print ' selected'; } print '>' . __('Today', 'gridcstat') . '</option>';

								for ($value=$start_val; $value < $end_val; $value++) {
									if ($value > 7 && $value!=GT_DAY_SHIFT && $value!=GT_THIS_DAY && $value!=GT_PREV_DAY) {
										print "<option value='" . $value . "'"; if ($_SESSION['sess_cstat_current_timespan'] == $value) { print ' selected'; } print '>' . $grid_timespans[$value] . '</option>';
									} elseif ($value == 7) {
										print "<option value='" . $value . "'"; if ($_SESSION['sess_cstat_current_timespan'] == $value) { print ' selected'; } print '>' . __('Yesterday', 'gridcstat') . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'gridcstat');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='10' value='<?php print (isset($_SESSION['sess_cstat_current_date1']) ? $_SESSION['sess_cstat_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'gridcstat');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'gridcstat');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='10' value='<?php print (isset($_SESSION['sess_cstat_current_date2']) ? $_SESSION['sess_cstat_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector', 'gridcstat');?>'></i>
						</span>
					</td>
					<td>
						<span>
						<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward', 'gridcstat');?>'></i>
						<select id='predefined_timeshift' title='Define Shifting Interval'>
							<?php
							$start_val = 1;
							$end_val = cacti_sizeof($grid_timeshifts)+1;
							if (cacti_sizeof($grid_timeshifts)) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									if ($shift_value >=7) {
										print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . $grid_timeshifts[$shift_value] . '</option>';
									}
								}
							}
							?>
						</select>
						<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward', 'gridcstat');?>'></i>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr><?php
}
