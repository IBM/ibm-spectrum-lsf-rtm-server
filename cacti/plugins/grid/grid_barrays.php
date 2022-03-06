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
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');

$title = __('IBM Spectrum LSF RTM - Batch Job Array Statistics', 'grid');

validate_barrays_request_vars();

switch (get_request_var('action')) {
	case 'ajax_rtm_users':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bhosts.php', 'job_user', $sql_where);
		break;
	case 'ajax_rtm_usergroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bhosts.php', 'usergroup', $sql_where);
		break;
	default:
		grid_view_arrays();
	break;
}

function grid_view_get_array_records(&$sql_where, $apply_limits = true, $rows = '30', &$sql_params) {
	global $config, $timespan, $grid_efficiency_sql_ranges, $ws, $we;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

	/* user id sql where */
	if (get_request_var('job_user') != '-1' && get_request_var('job_user') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'user=?';
		$sql_params[] = get_request_var('job_user');
	}

	$table_name = 'grid_arrays';
	/* job status sql where */
	switch (get_request_var('status')) {
		case 'ACTIVE':
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'stat=0';
			break;
		case 'PEND':
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(stat=0 AND numRUN+numDONE+numEXIT+numSSUSP+numUSUSP=0)';
			break;
		case 'RUNNING':
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(stat=0 AND numRUN+numSSUSP+numUSUSP > 0)';
			break;
		case 'COMPLETE':
			$table_name = 'grid_arrays_finished';
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'stat=1';
			break;
	}

	/* usergroup sql where */
	if (get_request_var('usergroup') != '-1') {
		$delim = read_config_option('grid_job_stats_ugroup_delimiter');
		if (read_config_option('grid_usergroup_method') == 'jobmap') {
			if (get_request_var('usergroup') == 'default') {
				$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(userGroup="")';
			} else {
				if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
					$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (userGroup=? OR userGroup LIKE ?)';
					$sql_params[] = get_request_var('usergroup');
					$sql_params[] = get_request_var('usergroup') . "\\$delim%";
				} else {
					$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (userGroup=?)';
					$sql_params[] = get_request_var('usergroup');
				}
			}
		} else {
			if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
				$users = db_fetch_assoc_prepared("SELECT username
					FROM grid_user_group_members
					WHERE groupname=? OR groupname LIKE ?",
					array(get_request_var('usergroup'), get_request_var('usergroup') . "\\$delim%"));
			} else {
				$users = db_fetch_assoc_prepared("SELECT username
					FROM grid_user_group_members
					WHERE groupname=?",
					array(get_request_var('usergroup')));
			}

			if (!empty($users)) {
				$usercount = 0;
				foreach($users as $user) {
					if ($user['username'] == 'all') {
						break;
					}

					if ($usercount == 0) {
						$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "($table_name.user IN (";
						$sql_where .= db_qstr($user['username']);
					} else {
						$sql_where .= ', ' . db_qstr($user['username']);
					}

					$usercount++;
				}

				if ($usercount > 0) {
					$sql_where .= '))';
				}
			}
		}
	}

	/* clusterid sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "($table_name.clusterid=?)";
		$sql_params[] = get_request_var('clusterid');
	}


	/* queue sql where */
	if (get_request_var('queue') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(queue=?)';
		$sql_params[] = get_request_var('queue');
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "($table_name.jName LIKE ? OR $table_name.jobid LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('status') =='COMPLETE') {
		/* timespane sql where */
		$ws       = date('Y-m-d H:i:s', strtotime($timespan['current_value_date1']));
		$we       = date('Y-m-d H:i:s', strtotime($timespan['current_value_date2']. ":59"));

		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "($table_name.last_updated BETWEEN ? AND ?)";
		$sql_params[] = $ws;
		$sql_params[] = $we;
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT * FROM $table_name $sql_where $sql_order";
	if (get_request_var('status') =='COMPLETE' && read_config_option('grid_partitioning_enable') != '') {
		$tables = partition_get_partitions_for_query('grid_arrays_finished', $ws, $we);

		if (cacti_sizeof($tables)) {
			$sql_query  = '';
			$sql_params_union = array();
			foreach($tables as $table) {
				if (strlen($sql_query)) {
					$sql_query .= ' UNION ';
				}

				$sql_query .= "SELECT *
					FROM $table " .
					str_replace($table_name, $table, $sql_where);
				$sql_params_union = array_merge($sql_params_union, $sql_params);
			}

			$sql_query .= $sql_order;
		}
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, (isset($sql_params_union) ? $sql_params_union : $sql_params));
}

function arraysFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_timeshifts, $grid_timespans;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_barrays.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT * from grid_clusters ORDER BY clustername');
							if (!empty($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_barrays.php', 'UGroup', 'usergroup', get_request_var('usergroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<?php print html_autocomplete_filter('grid_barrays.php', 'User', 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<input type='submit' id='go' value='<?php print __('Go', 'grid');?>' title='<?php print __('Search', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear', 'grid');?>' title='<?php print __('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td>
						<select id='status'>
							<option value='ACTIVE'<?php if (get_request_var('status') == 'ACTIVE') {?> selected<?php }?>><?php print __('ACTIVE', 'grid');?></option>
							<option value='PEND'<?php if (get_request_var('status') == 'PEND') {?> selected<?php }?>><?php print __('PEND', 'grid');?></option>
							<option value='RUNNING'<?php if (get_request_var('status') == 'RUNNING') {?> selected<?php }?>><?php print __('RUNNING', 'grid');?></option>
							<option value='COMPLETE'<?php if (get_request_var('status') == 'COMPLETE') {?> selected<?php }?>><?php print __('COMPLETE', 'grid');?></option>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'grid');?>
					</td>
					<td>
						<select id='queue'>
						<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>>All</option>
						<?php
						if (get_request_var('clusterid') == 0) {
							$queues = db_fetch_assoc('SELECT DISTINCT queuename
								FROM grid_queues
								ORDER BY queuename');
						} else {
							$queues = db_fetch_assoc_prepared('SELECT queuename
								FROM grid_queues
								WHERE clusterid = ?
								ORDER BY queuename', array(get_request_var('clusterid')));
						}

						if (!empty($queues)) {
							foreach ($queues as $queue) {
								print '<option value="' . $queue['queuename'] .'"'; if (get_request_var('queue') == $queue['queuename']) { print ' selected'; } print '>' . $queue['queuename'] . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('Arrays', 'grid');?>
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
				</tr>
			</table>
			<?php if (get_request_var('status') == 'COMPLETE') { ?>
			<table class='filterTable'>
				<tr>
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
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print (isset($_SESSION['sess_grid_current_date1']) ? $_SESSION['sess_grid_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print (isset($_SESSION['sess_grid_current_date2']) ? $_SESSION['sess_grid_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward');?>'></i>
							<select id='predefined_timeshift' title='<?php print __('Define Shifting Interval', 'grid');?>'>
								<?php
								$start_val = 1;
								$end_val = cacti_sizeof($grid_timeshifts)+1;
								if (cacti_sizeof($grid_timeshifts)) {
									for ($shift_value = $start_val; $shift_value < $end_val; $shift_value++) {
										print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . title_trim($grid_timeshifts[$shift_value], 40) . '</option>';
									}
								}
								?>
							</select>
							<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward');?>'></i>
						</span>
					</td>
				</tr>
			</table>
			<?php } else { ?>
			<table class='filterTable' style='display:none'>
				<tr>
					<td>
						<input type='hidden' name='predefined_timespan' value='<?php print get_request_var('predefined_timespan');?>'>
						<input type='hidden' name='predefined_timeshift' value='<?php print get_request_var('predefined_timeshift');?>'>
					</td>
				</tr>
			</table>
			<?php } ?>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
			</form>

			<script type='text/javascript'>
			// Initialize the calendar
			calendar=null;

			// This function displays the calendar associated to the input field 'id'
			function showCalendar(id) {
				var el = document.getElementById(id);
				if (calendar != null) {
					// we already have some calendar created
					calendar.hide();  // so we hide it first.
				} else {
					// first-time call, create the calendar.
					var cal = new Calendar(true, null, selected, closeHandler);
					cal.weekNumbers = false;  // Do not display the week number
					cal.showsTime = true;     // Display the time
					cal.time24 = true;        // Hours have a 24 hours format
					cal.showsOtherMonths = false;    // Just the current month is displayed
					calendar = cal;                  // remember it in the global var
					cal.setRange(1900, 2070);        // min/max year allowed.
					cal.create();
				}

				calendar.setDateFormat('%Y-%m-%d %H:%M');    // set the specified date format
				calendar.parseDate(el.value);                // try to parse the text in field
				calendar.sel = el;                           // inform it what input field we use

				// Display the calendar below the input field
				calendar.showAtElement(el, "Br");        // show the calendar

				return false;
			}

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
			</script>
		</td>
	</tr>
	<?php
}

function validate_barrays_request_vars() {
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
		'predefined_timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_timespan')
			),
		'predefined_timeshift' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_timeshift')
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_grid_config_option('default_job_status'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'usergroup' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
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
			'default' => 'numJobs',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gar');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}
function grid_view_arrays() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	global $array, $ws, $we;
	$sql_params = array();

	/* set variables for first time use */
	$timespan = initialize_timespan();
	$timeshift = grid_set_timeshift();

	/* process the timespan/timeshift settings */
	process_html_variables();
	process_user_input($timespan, $timeshift);

	/* save session variables */
	finalize_timespan($timespan);

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter(move_flag) {
		strURL  = 'grid_barrays.php?action=viewarray&header=false';
		strURL += '&job_user=' + $('#job_user').val();
		strURL += '&usergroup=' + encodeURIComponent($('#usergroup').val());
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&queue=' + $('#queue').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
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
			if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
				strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
			} else {
				if ($('#date1').val() && $('#date2').val()) {
					strURL = strURL + '&date1=' + $('#date1').val();
					strURL = strURL + '&date2=' + $('#date2').val();
				}
			}
		}
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_barrays.php?clear=true&header=false';

		loadPageNoHeader(strURL);
	}

	$(function() {
		date1='<?php print $_SESSION['sess_grid_current_date1'];?>';
		date2='<?php print $_SESSION['sess_grid_current_date2'];?>';

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

		$('#job_user, #usergroup, #clusterid, #rows, #status, #queue, #filter, #predefined_timespan, #predefined_timeshift').change(function() {
			applyFilter(0);
		});
		$('#move_left').click(function() {
			applyFilter(1);
		});
		$('#move_right').click(function() {
			applyFilter(2);
		});

		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter(0);
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	html_start_box(__('Batch Array Filters', 'grid'), '100%', '', '3', 'center', '');
	arraysFilter();
	html_end_box();

	$sql_where  = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$array_results = grid_view_get_array_records($sql_where, true, $rows, $sql_params);

	$queue_nice_levels = array_rekey(db_fetch_assoc("SELECT
		CONCAT_WS('',clusterid,'-',queuename,'') AS cluster_queue,
		nice
		FROM grid_queues"), 'cluster_queue', 'nice');

	html_start_box('', '100%', '', '3', 'center', '');

	$table_name='grid_arrays';

	if (get_request_var('status') =='COMPLETE') {
		$table_name='grid_arrays_finished';
	}

	$rows_query_string = "SELECT COUNT(*) AS Total FROM $table_name $sql_where";

	if (get_request_var('status') =='COMPLETE' && read_config_option('grid_partitioning_enable') != '') {
		$tables = partition_get_partitions_for_query('grid_arrays_finished', $ws, $we);

		if (cacti_sizeof($tables)) {
			$rows_query_string  = '';
			$sql_params_union = array();

			foreach($tables as $table) {
				if (strlen($rows_query_string)) {
					$rows_query_string .= ' UNION ';
				}

				$rows_query_string .= "SELECT COUNT(*) AS Total FROM $table " .	str_replace($table_name, $table, $sql_where);
				$sql_params_union = array_merge($sql_params_union, $sql_params);
			}

			$rows_query_string = 'SELECT SUM(Total) FROM (' . $rows_query_string . ') AS ARRAYS';
		}
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, (isset($sql_params_union) ? $sql_params_union : $sql_params));

	$display_text = build_array_display_array();

	/* generate page list */
	$nav = html_nav_bar('grid_barrays.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Arrays', 'grid'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;
	if (!empty($array_results)) {
		foreach ($array_results as $array) {
			form_alternate_row();
			?>
			<td class='nowrap' style='width:1%'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&predefined_timespan=' . get_request_var('predefined_timeshift') . '&clusterid=' . $array['clusterid'] . '&status=ACTIVE&page=1&jobid=' . $array['jobid']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Active Jobs', 'grid');?>'></a>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&predefined_timespan=' . get_request_var('predefined_timespan') . '&clusterid=' . $array['clusterid'] . '&status=FINISHED&page=1&jobid=' . $array['jobid']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs_finished.gif' alt='' title='<?php print __('View Finished Jobs', 'grid');?>'></a>
			</td>
			<td>
				<a class='pic' class='linkEditMain' href='<?php print html_escape('grid_bjobs.php?action=viewlist&reset=1&status=RUNNING&clusterid=' . $array['clusterid'] . '&jobid=' . $array['jobid']);?>' title='<?php print $array['jobid'];?>'><?php print filter_value($array['jobid'], get_request_var('filter'));?></a>
			</td>
			<?php
			if (read_grid_config_option('show_Ajobname')) {
				print api_plugin_hook_function('grid_arrays_jobname', "<td title='" . $array['jName'] . "'>" . (filter_value(title_trim($array['jName'], 50), get_request_var('filter'))) . '</td>');
			}

			form_selectable_cell_visible(grid_format_time($array['submit_time'], false), 'show_Asubmit_time', $i, 'left');

			form_selectable_cell_metadata('simple', 'user-group', $array['clusterid'], $array['userGroup'], 'show_AuserGroup');
			form_selectable_cell_metadata('detailed', 'user', $array['clusterid'], $array['user'], 'show_Auser');
			form_selectable_cell_metadata('simple', 'queue', $array['clusterid'], $array['queue'], 'show_Aqueue');
			form_selectable_cell_metadata('simple', 'project', $array['clusterid'], $array['projectName'], 'show_Aproject', '', html_escape($array['projectName']), true);

			form_selectable_cell_visible(number_format_i18n($array['numJobs']), 'show_Ajobs', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numPEND']), 'show_Apend', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numRUN']), 'show_Arunning', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numDONE']), 'show_Adone', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numEXIT']), 'show_Aexit', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numSSUSP']), 'show_Assusp', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numUSUSP']), 'show_Aususp', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($array['numPSUSP']), 'show_Apsusp', $i, 'right');
			form_selectable_cell_visible(display_job_effic($array['totalEfficiency'],2), 'show_Aefficiency', $i, 'right');
			form_selectable_cell_visible(display_job_memory($array['minMemory'],2), 'show_AminMemory', $i, 'right');
			form_selectable_cell_visible(display_job_memory($array['maxMemory'],2), 'show_AmaxMemory', $i, 'right');
			form_selectable_cell_visible(display_job_memory($array['avgMemory'],2), 'show_AavgMemory', $i, 'right');
			form_selectable_cell_visible(display_job_memory($array['minSwap'],2), 'show_AminSwap', $i, 'right');;
			form_selectable_cell_visible(display_job_memory($array['maxSwap'],2), 'show_AmaxSwap', $i, 'right');
			form_selectable_cell_visible(display_job_memory($array['avgSwap'],2), 'show_AavgSwap', $i, 'right');
			form_selectable_cell_visible(display_job_time($array['totalCPU'],2), 'show_Atotalcpu', $i, 'right');
			form_selectable_cell_visible(display_job_time($array['totalSTime'],2), 'show_Atotalstime', $i, 'right');
			form_selectable_cell_visible(display_job_time($array['totalUTime'],2), 'show_Atotalutime', $i, 'right');

			form_end_row();

			$i++;
		}

		html_end_box(false);

		print $nav;
	} else {
		print "<tr><td colspan='" . cacti_sizeof($display_text) . "'><em>" . __('No Array Records Found', 'grid') . '</em></td></tr>';
		html_end_box(false);
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function build_array_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'grid')
		),
		'jobid' => array(
			'display' => __('Array ID', 'grid'),
			'sort'    => 'ASC'
		),
		'jName' => array(
			'display' => __('Job Name', 'grid'),
			'dbname'  => 'show_Ajobname',
			'sort'    => 'ASC'
		),
		'submit_time' => array(
			'display' => __('Submit Time', 'grid'),
			'dbname'  => 'show_Asubmit_time',
			'sort'    => 'DESC'
		),
		'userGroup' => array(
			'display' => __('User Group', 'grid'),
			'dbname'  => 'show_AuserGroup',
			'sort'    => 'ASC'
		),
		'user' => array(
			'display' => __('User ID', 'grid'),
			'dbname'  => 'show_Auser',
			'sort'    => 'ASC'
		),
		'queue' => array(
			'display' => __('Running Queue', 'grid'),
			'dbname'  => 'show_Aqueue',
			'sort'    => 'ASC'
		),
		'projectName' => array(
			'display' => __('Project', 'grid'),
			'dbname'  => 'show_Aproject',
			'sort'    => 'ASC'
		),
		'numJobs' => array(
			'display' => __('Total Jobs', 'grid'),
			'align'   => 'right',
			'dbname'  => 'show_Ajobs',
			'sort'    => 'DESC'
		),
		'numPEND' => array(
			'display' => __('Pending Jobs', 'grid'),
			'align'   => 'right',
			'dbname'  => 'show_Apend',
			'sort'    => 'DESC'
		),
		'numRUN' => array(
			'display' => __('Running Jobs', 'grid'),
			'align'   => 'right',
			'dbname'  => 'show_Arunning',
			'sort'    => 'DESC'
		),
		'numDONE' => array(
			'display' => __('Done Jobs', 'grid'),
			'align'   => 'right',
			'dbname'  => 'show_Adone',
			'sort'    => 'DESC'
		),
		'numEXIT' => array(
			'display' => __('Exit Jobs', 'grid'),
			'dbname'  => 'show_Aexit',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numSSUSP' => array(
			'display' => __('SSUSP Jobs', 'grid'),
			'dbname'  => 'show_Assusp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numUSUSP' => array(
			'display' => __('USUSP Jobs', 'grid'),
			'dbname'  => 'show_Aususp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numPSUSP' => array(
			'display' => __('PSUSP Jobs', 'grid'),
			'dbname'  => 'show_Apsusp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'totalEfficiency' => array(
			'display' => __('Array Effic', 'grid'),
			'dbname'  => 'show_Aefficiency',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'minMemory' => array(
			'display' => __('MIN Memory', 'grid'),
			'dbname'  => 'show_AminMemory',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'maxMemory' => array(
			'display' => __('MAX Memory', 'grid'),
			'dbname'  => 'show_AmaxMemory',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avgMemory' => array(
			'display' => __('Avg Memory', 'grid'),
			'dbname'  => 'show_AavgMemory',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'minSwap' => array(
			'display' => __('MIN Swap', 'grid'),
			'dbname'  => 'show_AminSwap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'maxSwap' => array(
			'display' => __('MAX Swap', 'grid'),
			'dbname'  => 'show_AmaxSwap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avgSwap' => array(
			'display' => __('Avg Swap', 'grid'),
			'dbname'  => 'show_AavgSwap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'totalCPU' => array(
			'display' => __('Total CPU Time', 'grid'),
			'dbname'  => 'show_Atotalcpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'totalSTime' => array(
			'display' => __('Total System Time', 'grid'),
			'dbname'  => 'show_Atotalstime',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'totalUTime' => array(
			'display' => __('Total User Time', 'grid'),
			'dbname'  => 'show_Atotalutime',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}
