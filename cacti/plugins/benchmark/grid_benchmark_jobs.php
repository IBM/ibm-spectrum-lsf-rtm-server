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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include('./lib/rtm_timespan_settings.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/benchmark/functions.php');

$benchmark_actions = array(
	1 => __('Delete'),
	2 => __('Enable'),
	3 => __('Disable')
);

$timespans = array(
	GT_LAST_HALF_HOUR => '-30 minutes',
	GT_LAST_HOUR      => '-1 hour',
	GT_LAST_2_HOURS   => '-2 hours',
	GT_LAST_4_HOURS   => '-4 hours',
	GT_LAST_6_HOURS   => '-6 hours',
	GT_LAST_12_HOURS  => '-12 hours',
	GT_LAST_DAY       => '-1 day',
	GT_LAST_2_DAYS    => '-2 days',
	GT_LAST_3_DAYS    => '-3 days',
	GT_LAST_4_DAYS    => '-4 days',
	GT_LAST_WEEK      => '-1 week',
	GT_LAST_2_WEEKS   => '-2 weeks',
	GT_LAST_MONTH     => '-1 month',
	GT_LAST_2_MONTHS  => '-2 months',
	GT_LAST_3_MONTHS  => '-3 months',
	GT_LAST_4_MONTHS  => '-4 months',
	GT_LAST_6_MONTHS  => '-6 months',
	GT_LAST_YEAR      => '-1 year',
	GT_LAST_2_YEARS   => '-2 years'
);

$timeshifts = array(
	GTS_HALF_HOUR => 1800,
	GTS_1_HOUR    => 3600,
	GTS_2_HOURS   => 7200,
	GTS_4_HOURS   => 14400,
	GTS_6_HOURS   => 21600,
	GTS_12_HOURS  => 43200,
	GTS_1_DAY     => 86400,
	GTS_2_DAYS    => 172800,
	GTS_3_DAYS    => 259200,
	GTS_4_DAYS    => 345600,
	GTS_1_WEEK    => 604800
);


$title = __('IBM Spectrum LSF RTM - Benchmark Job Management');

set_default_action();

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();

$title = __('IBM Spectrum LSF RTM - Benchmark Jobs');

process_request_var();
if (isset_request_var('ajaxstats')) {
	general_header();
	ajax_benchmark_jobs();
	bottom_footer();
}else{
	general_header();
	view_benchmark_jobs();
	bottom_footer();
}

function grid_view_get_records(&$sql_where, $apply_limits = TRUE, $rows, &$sql_params) {
	benchmark_status_where($sql_where);

	/* cluster id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (a.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	/* cluster id sql where */
	if (get_request_var('benchmark_id') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (a.benchmark_id=?)';
		$sql_params[] = get_request_var('benchmark_id');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " (b.benchmark_name LIKE ? OR
			a.pjob_jobid LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (isset_request_var('date1') && isset_request_var('date2')) {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . "(start_time BETWEEN ? AND ?)";
		$sql_params[] = get_request_var('date1');
		$sql_params[] = get_request_var('date2');
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT a.*, b.benchmark_name, b.status_last_error
		FROM grid_clusters_benchmark_summary a
		INNER JOIN grid_clusters_benchmarks b
		ON (a.benchmark_id = b.benchmark_id)
		$sql_where
		$sql_order";

	//echo $sql_query;

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_job_config_display_array() {
	$display_text = array();

	$display_text += array(
		'benchmark_name' => array('display' => __('Benchmark Name'), 'sort' => 'ASC')
	);

	$display_text += array(
		'benchmark_id' => array('display' => __('ID'), 'sort' => 'ASC')
	);

	$display_text += array(
		'clusterid' => array('display' => __('Cluster Name'), 'sort' => 'ASC')
	);

	$display_text += array(
		'start_time' => array('display' => __('Start Time'), 'sort' => 'ASC')
	);

	$display_text += array(
		'pjob_jobid' => array('display' => __('Job ID'), 'sort' => 'ASC')
	);

	$display_text += array(
		'status' => array('display' => __('Status'), 'align' => 'left', 'sort' => 'ASC', 'tip' => __('Status is sorted by its code'))
	);

	$display_text += array(
		'exitStatus' => array('display' => __('Exit Code'), 'align' => 'left', 'sort' => 'ASC')
	);

	$display_text += array(
		'pjob_bsubTime' => array('display' => __('Submitted'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'pjob_seenTime' => array('display' => __('Seen'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'pjob_startTime' => array('display' => __('Started'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'pjob_runTime' => array('display' => __('Runtime'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'pjob_doneTime' => array('display' => __('Finished'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'pjob_seenDoneTime' => array('display' => __('SeenDone'), 'align' => 'right', 'sort' => 'DESC')
	);

	$display_text += array(
		'nosort1' => array('display' => __('Status Reason'), 'align' => 'left', 'sort' => 'ASC')
	);

	return $display_text;
}

function job_config_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $timespans, $grid_timespans, $timeshifts, $grid_timeshifts, $grid_weekdays, $timespan;

	?>
	<tr class='odd noprint'>
		<td class='noprint'>
		<form id='form_grid' action='grid_benchmark_jobs.php'>
			<script type='text/javascript'>
				function applyFilter() {
					strURL = 'grid_benchmark_jobs.php?ajaxstats=1&header=false&clusterid=' + $('#clusterid').val();
					strURL += '&benchmark_id=' + $('#benchmark_id').val();
					strURL += '&filter=' + $('#filter').val();
					strURL += '&refresh=' + $('#refresh').val();
					strURL += '&rows=' + $('#rows').val();
					strURL += '&status=' + $('#status').val();

					if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
						strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
					}else{
						strURL = strURL + '&date1=' + $('#date1').val();
						strURL = strURL + '&date2=' + $('#date2').val();
					}
					strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();


					$('#status_ajax').html("<img src='../grid/images/wait-loader.gif' align='absmiddle' border='0'>");
					$.get(strURL, function(data) {
						$('#stats_content').html(data);
						$('#status_ajax').html('');
						applySkin();
						applySkinRTM();
					});
				}

				function applyFilterChangePDTS() {
					strURL = 'grid_benchmark_jobs.php?ajaxstats=1&header=false&predefined_timespan=' + $('#predefined_timespan').val();
					strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();

					$('#status_ajax').html("<img src='../grid/images/wait-loader.gif' align='absmiddle' border='0'>");
					$.get(strURL, function(data) {
						$('#stats_content').html(data);
						$('#status_ajax').html('');
						applySkin();
						applySkinRTM();
					});
				}

				function moveRight() {
					strURL = 'grid_benchmark_jobs.php?ajaxstats=1&header=false&move_right_x=1';
					strURL = strURL + '&date1=' + $('#date1').val();
					strURL = strURL + '&date2=' + $('#date2').val();
					strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();

					$('#status_ajax').html("<img src='../grid/images/wait-loader.gif' align='absmiddle' border='0'>");
					$.get(strURL, function(data) {
						$('#stats_content').html(data);
						$('#status_ajax').html('');
						applySkin();
						applySkinRTM();
					});
				}

				function moveLeft() {
					strURL = 'grid_benchmark_jobs.php?ajaxstats=1&header=false&move_left_x=1';
					strURL = strURL + '&date1=' + $('#date1').val();
					strURL = strURL + '&date2=' + $('#date2').val();
					strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();

					$('#status_ajax').html("<img src='../grid/images/wait-loader.gif' align='absmiddle' border='0'>");
					$.get(strURL, function(data) {
						$('#stats_content').html(data);
						$('#status_ajax').html('');
						applySkin();
						applySkinRTM();
					});
				}


				function clearFilterSelections() {
					strURL = 'grid_benchmark_jobs.php?ajaxstats=1&header=false&clear=1';
					$('#status_ajax').html("<img src='../grid/images/wait-loader.gif' align='absmiddle' border='0'>");
					$.get(strURL, function(data) {
						$('#stats_content').html(data);
						$('#status_ajax').html('');
						applySkin();
						applySkinRTM();
					});
				}

			$(function() {
				date1='<?php print $_SESSION['sess_gbmj_current_date1'];?>';
				date2='<?php print $_SESSION['sess_gbmj_current_date2'];?>';

				// Initialize the calendar
				calendar=null;

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

				function stopRKey(evt) {
					var evt  = (evt) ? evt : ((event) ? event : null);
					var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
					if ((evt.keyCode == 13) && (node.type=='text')) { return false; }
				}
				document.onkeypress = stopRKey;


				$('#views').multiselect({header: "Choose a View", minWidth: 140 });

				$('#clusterid, #benchmark_id, #rows, #refresh, #status').change(function() {
					applyFilter();
				});

				$('#predefined_timespan').change(function() {
					applyFilterChangePDTS();
				});

				$('#predefined_timeshift').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilterSelections();
				});

				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
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

				$('#date1').datetimepicker({
					minuteGrid: 10,
					stepMinute: 1,
					showAnim: 'slideDown',
					numberOfMonths: 1,
					timeFormat: 'HH:mm:ss',
					dateFormat: 'yy-mm-dd',
					showButtonPanel: false
				});

				$('#date2').datetimepicker({
					minuteGrid: 10,
					stepMinute: 1,
					showAnim: 'slideDown',
					numberOfMonths: 1,
					timeFormat: 'HH:mm:ss',
					dateFormat: 'yy-mm-dd',
					showButtonPanel: false
				});

				$('#move_left').click(function() {
					moveLeft();
				});

				$('#move_right').click(function() {
					moveRight();
				});
				applySkinRTM();
			});
			</script>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All');?></option>
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
					<td>
						<?php print __('Benchmark');?>
					</td>
					<td>
						<select id='benchmark_id'>
							<option value='0'<?php if (get_request_var('benchmark_id') == '0') {?> selected<?php }?>><?php print __('All');?></option>
							<?php
							if (get_request_var('clusterid') > 0) {
								$benchmarks = db_fetch_assoc_prepared("SELECT benchmark_id, benchmark_name FROM grid_clusters_benchmarks WHERE clusterid=? ORDER BY benchmark_name", array(get_request_var('clusterid')));
							} else {
								$benchmarks = db_fetch_assoc("SELECT benchmark_id, benchmark_name FROM grid_clusters_benchmarks ORDER BY benchmark_name");
							}

							if (cacti_sizeof($benchmarks)) {
								foreach ($benchmarks as $benchmark) {
									print '<option value="' . $benchmark['benchmark_id'] .'"'; if (get_request_var('benchmark_id') == $benchmark['benchmark_id']) { print ' selected'; } print '>' . $benchmark['benchmark_name'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php benchmark_status_filter('details'); ?>
					<td>
						<?php print __('Records');?>
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
						<?php print __('Refresh');?>
					</td>
					<td>
						<select id='refresh'>
							<?php
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							foreach($grid_refresh_interval as $key => $value) {
								if ($key >= $max_refresh) {
									print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input id='go' type='submit' value='<?php print __('Go');?>' title='<?php print __('Apply Custom Date Ranges or Search');?>'>
					</td>
					<td>
						<input id='clear' type='button' value='<?php print __('Clear');?>' title='<?php print __('Clear All Filters');?>'>
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
							if (isset($_SESSION['custom']) && $_SESSION['custom']) {
								$grid_timespans[GT_CUSTOM] = __('Custom', 'syslog');
								set_request_var('predefined_timespan', GT_CUSTOM);
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
									print "<option value='$value'"; if (get_request_var('predefined_timespan') == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'syslog');?>
					</td>
					<td>
						<input type='text' id='date1' size='20' value='<?php print (isset($_SESSION['sess_gbmj_current_date1']) ? $_SESSION['sess_gbmj_current_date1'] : '');?>'>
					</td>
					<td>
						<i title='<?php print __esc('Start Date Selector', 'syslog');?>' class='calendar fa fa-calendar' id='startDate'></i>
					</td>
					<td>
						<?php print __('To', 'syslog');?>
					</td>
					<td>
						<input type='text' id='date2' size='20' value='<?php print (isset($_SESSION['sess_gbmj_current_date2']) ? $_SESSION['sess_gbmj_current_date2'] : '');?>'>
					</td>
					<td>
						<i title='<?php print __esc('End Date Selector', 'syslog');?>' class='calendar fa fa-calendar' id='endDate'></i>
					</td>
					<td>
						<i title='<?php print __esc('Shift Time Backward', 'syslog');?>' id='move_left' class='shiftArrow fa fa-backward'></i>
					</td>
					<td>
						<select id='predefined_timeshift' title='<?php print __esc('Define Shifting Interval', 'syslog');?>'>
							<?php
							$start_val = 1;
							$end_val = cacti_sizeof($grid_timeshifts)+1;
							if (cacti_sizeof($grid_timeshifts) > 0) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									print "<option value='$shift_value'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . title_trim($grid_timeshifts[$shift_value], 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<i title='<?php print __esc('Shift Time Forward', 'syslog');?>' id='move_right' class='shiftArrow fa fa-forward'></i>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function view_benchmark_jobs() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config, $benchmark_actions;
	global $timespans, $grid_timespans, $timeshifts, $grid_timeshifts, $grid_weekdays, $timespan;

	echo "<div id='stats_content'></div>\n";

	?>
	<script type='text/javascript'>
	$(function() {
		if (navigator.userAgent.match(/msie/i)) {
			var D = (document.body.clientWidth)? document.body: document.documentElement;
			fw  = D.clientWidth - 350;
		}else{
			fw = window.innerWidth - 350;
		}
		if(fw < 0){
			fw = 1;
		}
		$.get('grid_benchmark_jobs.php?ajaxstats=1&header=false&width='+fw+'&sort_column=<?php print get_request_var("sort_column");?>&sort_direction=<?php print get_request_var("sort_direction");?>&add=<?php print get_request_var("add");?>', function(data) {
			$('#stats_content').html(data);
			$('#status_ajax').html('');
			applySkin();
			applySkinRTM();
		});
	});
	</script>
	<?php
}

function ajax_benchmark_jobs() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config, $benchmark_actions;
	global $timespans, $grid_timespans, $timeshifts, $grid_timeshifts, $grid_weekdays, $timespan;
	$sql_params = array();

	$title = __('Benchmark Job Results');
	html_start_box("$title<span id='status_ajax' style='padding:1px 1px 0px 5px;'></span>", '100%', '', '3', 'center', '');
	job_config_filter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$benchmark_jobs_results = grid_view_get_records($sql_where, TRUE, $rows, $sql_params);

	$rows_query_string = "SELECT COUNT(*)
		FROM grid_clusters_benchmark_summary a
		INNER JOIN grid_clusters_benchmarks b
		ON (a.benchmark_id = b.benchmark_id)
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);
	$display_text = build_job_config_display_array();

	/* generate page list */
	$nav = html_nav_bar('grid_benchmark_jobs.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, '', __('Benchmark Jobs'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($benchmark_jobs_results)) {
		foreach ($benchmark_jobs_results as $benchmark_job) {
			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($benchmark_job['clusterid']));

			form_alternate_row();

			$cluster_name = grid_get_clustername($benchmark_job['clusterid']);

			// It's possible that the cluster has been removed from RTM
			if ($cluster_name == '') {
				$cluster_name = '<i>' . __('Deleted') . '</i>';
			}

			//Assume job done with exitcode '0'
			$job_exitinfo = 0;
			if ($benchmark_job['status'] == 5  ||
				$benchmark_job['status'] == 14 ||
				$benchmark_job['status'] == 15 ||
				$benchmark_job['status'] == 16 ||
				$benchmark_job['status'] == 17
				) {
				$job_exitinfo = $benchmark_job['exitInfo'];  //for all exit jobs, use their actual exitinfo
			}

			$reason = getExceptionStatus($benchmark_job['exceptMask'], $job_exitinfo);
			if (empty($reason))  {
				$reason = __('N/A');
			}

			/* if the submit time is real, use it; Or search it from grid_jobs, grid_jobs_finished and its partitioned tables in order */
			$submit_time = '';
			$start_time  = '';
			$end_time    = '';

			//search job from grid_jobs
			if (!$submit_time) {
				$submit_time = db_fetch_cell_prepared('SELECT submit_time
					FROM grid_jobs
					WHERE jobid = ?
					AND indexid = 0
					AND clusterid = ?',
					array($benchmark_job['pjob_jobid'], $benchmark_job['clusterid']));

				if (isset($submit_time) && $submit_time != '0000-00-00 00:00:00') {
					$submit_time = strtotime($submit_time);
				}
			}

			//search job from grid_jobs_finished
			if (!$submit_time) {
				$submit_time = db_fetch_cell_prepared('SELECT submit_time
					FROM grid_jobs_finished
					WHERE jobid = ?
					AND indexid = 0
					AND clusterid = ?',
					array($benchmark_job['pjob_jobid'], $benchmark_job['clusterid']));

				if (isset($submit_time) && $submit_time != '0000-00-00 00:00:00') {
					$submit_time = strtotime($submit_time);
				}
			}

			//search job from grid_jobs_finished partitioned tables
			if (!$submit_time) {
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$start_time = date('Y-m-d H:i:s', strtotime($benchmark_job['start_time'])-3600);
					$end_time   = date('Y-m-d H:i:s');

					$tables = partition_get_partitions_for_query('grid_jobs_finished', $start_time, $end_time);
					foreach ($tables as $table){
						$submit_time = db_fetch_cell_prepared('SELECT submit_time
							FROM ' . $table . '
							WHERE jobid = ?
							AND indexid = 0
							AND clusterid = ?',
							array($benchmark_job['pjob_jobid'], $benchmark_job['clusterid']));

						if (isset($submit_time) && $submit_time != '0000-00-00 00:00:00') {
							$submit_time = strtotime($submit_time);
							break;
						}
					}
				}
			}

			//will show job not found
			if (!$submit_time) {
				$submit_time = strtotime($benchmark_job['start_time']);
			}

			$start_time  = round($submit_time - 86400,0);
			$end_time    = round($submit_time + 86400,0);

			echo '<td>' . filter_value($benchmark_job['benchmark_name'], get_request_var('filter')) . '</td>';
			echo '<td>'  . $benchmark_job['benchmark_id'] . '</td>';
			echo '<td>' . $cluster_name . '</td>';
			echo "<td class='nowrap'>"  . $benchmark_job['start_time'] . '</td>';

			echo '<td>' . filter_value($benchmark_job['pjob_jobid'], get_request_var('filter'), ($benchmark_job['pjob_jobid'] > 0)?$config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewjob&cluster_tz&clusterid=' . $benchmark_job['clusterid'] . '&jobid=' . $benchmark_job['pjob_jobid'] . '&indexid=0&submit_time=' . $submit_time . '&start_time=' . $start_time . '&end_time=' . $end_time:'') . '</td>';

			echo "<td class='nowrap'>"  . benchmark_get_status($benchmark_job['status']) . '</td>';
			echo '<td>'  . benchmark_get_exit_code($benchmark_job['exitStatus'], $reason) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_bsubTime']) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_seenTime']) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_startTime']) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_runTime']) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_doneTime']) . '</td>';
			echo "<td class='right nowrap'>"  . benchmark_get_time($benchmark_job['pjob_seenDoneTime']) . '</td>';
			echo "<td>$reason</td>";

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text). '"><em>' . __('No Benchmark Job Records Found') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($benchmark_jobs_results) > 0) {
		print $nav;
	}
}

function process_request_var() {
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
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'user' => array(
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
		'benchmark_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			)
	);

	validate_store_request_vars($filters, 'sess_gbmj');

	$timespan = rtm_initialize_timespan($grid_timespans, $grid_timeshifts, 'sess_gbmj', 'read_grid_config_option', 'Y-m-d H:i:s');
	$timeshift = rtm_set_timeshift($grid_timeshifts, 'sess_gbmj', 'read_grid_config_option');

		/* process the timespan/timeshift settings */
	rtm_process_html_variables($grid_timespans, $grid_timeshifts, 'sess_gbmj', 'read_grid_config_option');
	rtm_process_user_input($timespan, $timeshift, $grid_timespans, 'sess_gbmj', 'read_grid_config_option', 'Y-m-d H:i:s');

		/* save session variables */
	rtm_finalize_timespan($timespan, $grid_timespans, 'sess_gbmj', 'read_grid_config_option', 'Y-m-d H:i:s');

	set_request_var('date1',$_SESSION['sess_gbmj_current_date1']);
	set_request_var('date2',$_SESSION['sess_gbmj_current_date2']);

	load_current_session_value('benchmark_id', 'sess_gbmj_benchmark_id', '0');

	grid_set_minimum_page_refresh();

	$debug_log = nl2br(debug_log_return('grid_admin'));
	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table class='debug'>
			<tr>
				<td>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}
}
