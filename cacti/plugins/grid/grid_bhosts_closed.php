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
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include('./lib/rtm_timespan_settings.php');

$title = __('IBM Spectrum LSF RTM - Closed Admin Hosts', 'grid');

$grid_host_control_actions = array(
	1 => __('Open', 'grid')
);
grid_default_request_vars();
set_default_action();

switch (get_request_var('action')) {
	case 'actions':
		form_action();
		break;
	case 'ajax_rtm_hgroups':
		validate_bhosts_closed_request_vars();
		$sql_where = '';

		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}

		rtm_autocomplete_ajax('grid_bhosts_closed.php', 'hgroup', $sql_where);
		break;
	case 'ajax_rtm_exec_hosts':
		grid_closure_events_request_vars();
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bhosts_closed.php', 'exec_host', $sql_where, array('0' => 'All'));
		break;
	default:
		grid_view_closed();
	break;
}

function grid_view_closed() {
	global $config;

	/* set the default tab */
	load_current_session_value('tab', 'sess_grid_bhosts_closed_tab', 'host');
	$current_tab = get_request_var('tab');

	if ($current_tab == 'host') {
		grid_view_bhosts();
	} else {
		grid_closure_events();
	}

}

function grid_closure_events_request_vars() {
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
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'host',
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
			'default' => 'false'
			),
		'exec_host' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '0'
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'all',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'lockid' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'all',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_grid_closure_event');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	/* set variables for first time use */
	$timespan = rtm_initialize_timespan($grid_timespans, $grid_timeshifts, 'sess_grid_closure_event', 'read_grid_config_option', 'Y-m-d H:i:s');
	$timeshift = rtm_set_timeshift($grid_timeshifts, 'sess_grid_closure_event', 'read_grid_config_option');

   	/* process the timespan/timeshift settings */
	rtm_process_html_variables($grid_timespans, $grid_timeshifts, 'sess_grid_closure_event', 'read_grid_config_option');
	rtm_process_user_input($timespan, $timeshift, $grid_timespans, 'sess_grid_closure_event', 'read_grid_config_option', 'Y-m-d H:i:s');

   	/* save session variables */
	rtm_finalize_timespan($timespan, $grid_timespans, 'sess_grid_closure_event', 'read_grid_config_option', 'Y-m-d H:i:s');

	set_request_var('date1', $_SESSION['sess_grid_closure_event_current_date1']);
	set_request_var('date2', $_SESSION['sess_grid_closure_event_current_date2']);
}

function grid_bhosts_closed_event_records(&$total_rows, $apply_limits = true, $rows = 30) {
	global $grid_out_of_services;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;

	$rowsquery = '';
	$sql_where = '';

	/* request validation */
	get_filter_request_var('clusterid');


	/* user id sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		$sql_where .= 'WHERE (clusterid=' . get_filter_request_var('clusterid') . ')';
	}

	/* exec_host sql where */
	if (get_request_var('exec_host') == '0') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (host=' . db_qstr(get_request_var('exec_host')) . ')';
		} else {
			$sql_where = 'WHERE (host=' . db_qstr(get_request_var('exec_host')) . ')';
		}
	}

	/* type sql where */
	if (get_request_var('type') == 'all') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (end_time="0000-00-00 00:00:00")';
		} else {
			$sql_where = 'WHERE (end_time="0000-00-00 00:00:00")';
		}
	}

	/* lockid sql where */
	if (get_request_var('lockid') == 'all') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (lockid=' . db_qstr(get_request_var('lockid')) . ')';
		} else {
			$sql_where = 'WHERE (lockid=' . db_qstr(get_request_var('lockid')) . ')';
		}
	}

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ((host LIKE '%" . get_request_var('filter') . "%') OR (hCtrlMsg LIKE '%" . get_request_var('filter') . "%'))";
	}

	/* timespan sql where , put this as last where to get a sql_where2 for no '0000-00-00 00:00:00' */
	if (isset_request_var('date1') && isset_request_var('date2')) {
		$date1 = get_request_var('date1');
		$date2 = get_request_var('date2');
		if (strlen($sql_where)) {
			$sql_where2 = $sql_where . " AND ((event_time BETWEEN '$date1' AND '$date2') OR (event_time < '$date1' AND (end_time > '$date1')))";
			$sql_where .= " AND ((event_time BETWEEN '$date1' AND '$date2') OR (event_time < '$date1' AND (end_time > '$date1' OR end_time = '0000-00-00 00:00:00')))";
		} else {
			$sql_where2 = "WHERE ((event_time BETWEEN '$date1' AND '$date2') OR (event_time < '$date1' AND (end_time > '$date1')))";
			$sql_where = "WHERE ((event_time BETWEEN '$date1' AND '$date2') OR (event_time < '$date1' AND (end_time > '$date1' OR end_time = '0000-00-00 00:00:00')))";
		}
	} else {
		$current_time = time();
		$date1 = date('Y-m-d H:i:s', $current_time);
		$date2 = date('Y-m-d H:i:s', $current_time - 86400);
	}

	$summarize_checked = false;
	if (isset_request_var('summarize') && ((get_request_var('summarize') == 'true') || (get_request_var('summarize') == 'on'))) {
		$interval = "'N/A' as admin, 'N/A' as hCtrlMsg, 'N/A' as event_time, 'N/A' as end_time";
		$group_by = 'GROUP BY clusterid, host, lockid';
		$summarize_checked = true;
	} else {
		$interval = "admin, hCtrlMsg, event_time, end_time";
		$group_by = '';
	}

	$sql_order = get_order_string();

	$select = "SELECT clusterid, host, lockid, $interval";
	if (read_config_option('grid_partitioning_enable') == '') {
		if($summarize_checked){
			$sql_query = "SELECT * FROM (
				$select
				FROM grid_host_closure_events
				$sql_where " . (get_request_var('type') == 'all' ? "
				UNION ALL
				$select
				FROM grid_host_closure_events_finished
				$sql_where2 " : "") . "
				) AS a
				$group_by
				$sql_order";
			/* cacti_log("total_rows SELECT clusterid FROM (
						SELECT clusterid, host, lockid FROM grid_host_closure_events $sql_where
						UNION ALL
						SELECT clusterid, host, lockid FROM grid_host_closure_events_finished $sql_where2
					) AS a
					$group_by"); */
			$total_rows_array = db_fetch_assoc("SELECT clusterid FROM (
						SELECT clusterid, host, lockid FROM grid_host_closure_events $sql_where " . (get_request_var('type') == 'all' ? "
						UNION ALL
						SELECT clusterid, host, lockid FROM grid_host_closure_events_finished $sql_where2 " : "") . "
					) AS a
					$group_by");
			if(cacti_sizeof($total_rows_array)){
				$total_rows = cacti_sizeof($total_rows_array);
			}
		} else {//no group by
			$sql_query = "$select
				FROM grid_host_closure_events
				$sql_where " . (get_request_var('type') == 'all' ? "
				UNION ALL
				$select
				FROM grid_host_closure_events_finished
				$sql_where2 " : "") . "
				$sql_order";
			/* cacti_log("total_rows SELECT COUNT(*) FROM (
						SELECT clusterid, host, lockid FROM grid_host_closure_events $sql_where
						UNION ALL
						SELECT clusterid, host, lockid FROM grid_host_closure_events_finished $sql_where2
					) AS a"); */
			$total_rows = db_fetch_cell("SELECT COUNT(*) FROM (
						SELECT clusterid, host, lockid FROM grid_host_closure_events $sql_where " . (get_request_var('type') == 'all' ? "
						UNION ALL
						SELECT clusterid, host, lockid FROM grid_host_closure_events_finished $sql_where2 " : "") . "
					) AS a");
		}
	} else {
		if (get_request_var('type') == 'all') {
			$union_tables = partition_get_partitions_for_query('grid_host_closure_events_finished', $date1, $date2);
		} else {
			$union_tables = array(); //active, no need check finished tables.
		}
		$sql_query = "$select FROM grid_host_closure_events $sql_where";
		$rowsquery =  "SELECT clusterid, host, lockid FROM grid_host_closure_events $sql_where";
		if (cacti_sizeof($union_tables)) {
			foreach ($union_tables as $table) {
				$sql_query .= " UNION ALL $select FROM $table $sql_where2";
				$rowsquery .= " UNION ALL SELECT clusterid, host, lockid FROM $table $sql_where2";
			}
		}
		if($summarize_checked){
			$sql_query = "SELECT * FROM ($sql_query) AS a $group_by $sql_order";

			//cacti_log("total_rows SELECT clusterid FROM ($rowsquery) AS a $group_by");
			$total_rows_array = db_fetch_assoc("SELECT clusterid FROM ($rowsquery) AS a $group_by");
			if(cacti_sizeof($total_rows_array)){
				$total_rows = cacti_sizeof($total_rows_array);
			}
		} else {//no group by
			$sql_query = "$sql_query $sql_order";

			//cacti_log("total_rows SELECT COUNT(*) FROM ($rowsquery) AS a");
			$total_rows = db_fetch_cell("SELECT COUNT(*) FROM ($rowsquery) AS a");
		}
	}
	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//echo $sql_query;
	return db_fetch_assoc($sql_query);
}

function closureEventsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;
	global $grid_efficiency_display_ranges;
	global $grid_time_range;

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>

			date1='<?php print $_SESSION['sess_grid_closure_event_current_date1'];?>'
			date2='<?php print $_SESSION['sess_grid_closure_event_current_date2'];?>'

			function stopRKey(evt) {
				var evt  = (evt) ? evt : ((event) ? event : null);
				var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
				if ((evt.keyCode == 13) && (node.type=='text')) { return false; }
			}
			document.onkeypress = stopRKey;

			function applyFilterChangePDTS() {
				strURL = 'grid_bhosts_closed.php?header=false&tab=closure_event&predefined_timespan=' + $('#predefined_timespan').val();
				strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();
				loadPageNoHeader(strURL);
			}

			function moveRight() {
				strURL = 'grid_bhosts_closed.php?header=false&tab=closure_event&move_right_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				loadPageNoHeader(strURL);
			}

			function moveLeft() {
				strURL = 'grid_bhosts_closed.php?header=false&tab=closure_event&move_left_x=1';
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				loadPageNoHeader(strURL);
			}

			function applyFilter() {
				strURL  = urlPath + 'plugins/grid/grid_bhosts_closed.php?header=false';
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&type=' + $('#type').val();
				strURL += '&lockid=' + $('#lockid').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&exec_host=' + $('#exec_host').val();
				strURL += '&filter=' + $('#filter').val();
				if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
					strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				} else {
					strURL += '&date1=' + $('#date1').val();
					strURL += '&date2=' + $('#date2').val();
				}
				strURL += '&summarize=' + $('#summarize').is(':checked');
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = urlPath + 'plugins/grid/grid_bhosts_closed.php?header=false&clear=true';
				loadPageNoHeader(strURL);
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

				$('#clear').click(function() {
					clearFilter();
				});

				$('#clusterid, #rows, #summarize, #exec_host, #lockid, #type, #filter, #predefined_timeshift').change(function() {
					applyFilter();
				});

				applySkinRTM();
			});

			</script>
			<form id='form_grid' action='grid_bhosts_closed.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>All</option>
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
					<?php print html_autocomplete_filter('grid_bhosts_closed.php', __('Host', 'grid'), 'exec_host', get_request_var('exec_host'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('0' => __('All', 'grid')));?>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='all' <?php  if (get_request_var('type') == 'all')  { print 'selected'; }?>><?php print __('All', 'grid');?></option>
							<option value='active' <?php  if (get_request_var('type') == 'active')  { print 'selected'; }?>><?php print __('Active', 'grid');?></option>
						</select>
					</td>
					<td>
						<?php print __('LockId', 'grid');?>
					</td>
					<td>
						<select id='lockid'>
							<option value='all' <?php  if (get_request_var('lockid') == 'all')  { print 'selected'; }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') <= 0) {
								if (get_request_var('type') == 'active') {
									$projects = db_fetch_assoc('SELECT DISTINCT lockid
										FROM grid_host_closure_events
										WHERE end_time = "0000-00-00 00:00:00"
										ORDER BY lockid');
								} else {
									$projects = db_fetch_assoc('SELECT DISTINCT lockid
										FROM grid_host_closure_lockids
										ORDER BY lockid');
								}
							} else {
								if (get_request_var('type') == 'active') {
									$projects = db_fetch_assoc_prepared('SELECT DISTINCT lockid
										FROM grid_host_closure_events
										WHERE end_time = "0000-00-00 00:00:00" AND clusterid = ?
										ORDER BY lockid',
										array(get_request_var('clusterid')));
								} else {
									$projects = db_fetch_assoc_prepared('SELECT DISTINCT lockid
										FROM grid_host_closure_lockids
										WHERE clusterid = ?
										ORDER BY lockid',
										array(get_request_var('clusterid')));
								}
							}

							if (cacti_sizeof($projects)) {
								foreach ($projects as $project) {
									print '<option value="' . html_escape($project['lockid']) . '"'; if (get_request_var('lockid') == $project['lockid']) print ' selected'; print '>' . title_trim($project['lockid'], 100) . '</option>';
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
						</span>
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
							if ($_SESSION['sess_grid_closure_event_custom']) {
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
									print "<option value='0'"; if ($_SESSION['sess_grid_closure_event_current_timespan'] == '0') { print ' selected'; } print '>' . __('Custom', 'grid') . '</option>';
								}
								for ($value=$start_val; $value < $end_val; $value++) {
									if ($value > 6 && $value!=GT_DAY_SHIFT && $value!=GT_THIS_DAY && $value!=GT_PREV_DAY) {
										print "<option value='" . $value . "'"; if ($_SESSION['sess_grid_closure_event_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
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
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print (isset($_SESSION['sess_grid_closure_event_current_date1']) ? $_SESSION['sess_grid_closure_event_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'grid');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print (isset($_SESSION['sess_grid_closure_event_current_date2']) ? $_SESSION['sess_grid_closure_event_current_date2'] : '');?>'>
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

function grid_closure_events() {
	global $config,$log_types;

	grid_closure_events_request_vars();

	$hosts = array();

	general_header();

	/* present a tabbed interface */
	$tabs_gridhost = array(
		'host'  => __('Closed Hosts', 'grid'),
		'closure_event' => __('Closure Events', 'grid')
	);

	/* draw the tabs */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";
	if (cacti_sizeof($tabs_gridhost)) {
		$i = 0;
		foreach (array_keys($tabs_gridhost) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == get_request_var('tab')) ? "class='pic selected'" : "class='pic '") . " href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_bhosts_closed.php?' .
				'tab=' . $tab_short_name) .
				"'>" . html_escape($tabs_gridhost[$tab_short_name]) . '</a></li>';
			$i++;
		}
	}

	print '</ul></nav></div>';
	print '</tr></table>';

	/* build the user interface */
	html_start_box(__('Event Filter Options', 'grid'), '100%', '', '3', 'center', '');
	closureEventsFilter();
	html_end_box();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$total_rows = 0;
	$results = grid_bhosts_closed_event_records($total_rows, true, $rows);

	$display_text = array(
		'nosort0' => array( 'display' => __('Cluster', 'grid'), 'align' => 'left'),
		'host'    	=> array('display' => __('Host', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		//'clusterid' => array( 'display' => __('Cluster ID', 'grid'), 'align' => 'left', 'sort'    => 'ASC'),
		'lockid'      => array('display' => __('LockId', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		'event_time'  => array('display' => __('Event Time', 'grid'), 'align' => 'right', 'sort' => 'DESC'),
		'admin'        => array('display' => __('Admin', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		'nosort'      => array('display' => __('Message', 'grid'), 'align' => 'left'),
		'end_time'  => array('display' => __('End Time', 'grid'), 'align' => 'right', 'sort' => 'DESC')
	);

	$nav = html_nav_bar('grid_bhosts_closed.php?tab=closure_event&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Events', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_bhosts_closed.php?tab=closure_event');

	$i = 0;
	if (cacti_sizeof($results)) {
		foreach ($results as $row) {
			form_alternate_row();
			form_selectable_cell(grid_get_clustername($row['clusterid']), $i);
			form_selectable_cell_metadata('simple', 'host', $row['clusterid'], $row['host'], '', '', html_escape($row['host']), true);
			//form_selectable_cell($row['clusterid'], $i);
			//form_selectable_cell(html_escape($row['host']), $i);
			form_selectable_cell(html_escape($row['lockid']), $i);
			form_selectable_cell($row['event_time'], $i, '', 'right');
			form_selectable_cell($row['admin'], $i);
			form_selectable_cell(html_escape($row['hCtrlMsg']), $i);
			form_selectable_cell($row['end_time'], $i, '', 'right');
			form_end_row();
			$i++;
		}

		html_end_box(false);

		print $nav;
	} else {
		form_alternate_row();
		print '<td colspan=15><center>' . __('No Events Found', 'grid') . '</center></td>';
		form_end_row();
		html_end_box(false);
	}
	bottom_footer();
}

function form_action() {
	global $config, $grid_host_control_actions;

	$count_ok     = 0;
	$count_fail   = 0;
	$action_level = 'host';
	$message      = '';

	if (isset_request_var('command') && get_request_var('command') == 'goback') {
		header('Location: grid_bhosts_closed.php');
		exit;
	}

	debug_log_clear('grid_admin');
	debug_log_clear('grid_admin_ok');
	debug_log_clear('grid_admin_failed');

	if (isset_request_var('selected_items') && read_config_option('grid_management_clusters') == 'on') {
		form_input_validate(trim(get_request_var('message')), 'message', '', false, 'error_mandatory_input_field');
	}
	if (isset_request_var('message')) {
		form_input_validate(get_nfilter_request_var('message'),   'message', '^[A-Za-z0-9\._\\\@\ \/-]+$', true, '148');

	}

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items') && !isset($_SESSION['sess_error_fields']['message'])) {
		if (isset_request_var('message') && trim(get_request_var('message')) != '') {
			$username = get_username($_SESSION['sess_user_id']);

			$message = "'" . trim(get_request_var('message')) . "' - " . __('by RTM User %s', $username, 'grid') . "'";
		} else {
			$message = '';
		}
		if (isset_request_var('action_lockid') && trim(get_request_var('action_lockid')) != '') { //change $message to array.
			$message_array = array();
			$message_array['message'] = $message;
			$message_array['action_lockid'] = trim(get_request_var('action_lockid'));
			$message = $message_array;
		}

		if (get_request_var('drp_action') == '1') { /* Open Host  */
			$host_action = 'open';
		} else if (get_request_var('drp_action') == '2') { /* Close Host */
			$host_action = 'close';
		}

		//$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		$rsp_content = array();
		$advocate_max = 30;
		if ($selected_items_whole != false) {
		for($i=0; $i<cacti_sizeof($selected_items_whole); $i+=$advocate_max){
			$selected_items = array_slice($selected_items_whole, $i, $advocate_max);
			if(cacti_sizeof($selected_items)<=0) break;
			//print_r($selected_items_whole);
			$json_return_format = sorting_json_format($selected_items, $message, $action_level); //sort the variables into required format

			$advocate_key = session_auth();

			$json_host_info = array (
					'key' => $advocate_key,
					'action' => $host_action,
					'target' => $json_return_format,
			);

			$output = json_encode($json_host_info);

			$curl_output =  exec_curl($action_level, $output); //pass to advocate for processing

			if ($curl_output['http_code'] == 400)
				raise_message(134);
			else if ($curl_output['http_code'] == 500)
				raise_message(135);
			else{
				if ($curl_output['http_code'] == 200) {
					// log_action permanently be 'Open' currently
					$log_action = $grid_host_control_actions[get_request_var('drp_action')];
					$json_output = json_decode($output);
					$username_log = get_username($_SESSION['sess_user_id']);
					foreach ($json_output->target as $target) {
					    $action_message = get_request_var('message');
					    cacti_log("Host '{$target->name}', {$log_action} by '{$username_log}', comment: '{$action_message}'.", false, 'LSFCONTROL');
					}
				} else {
					raise_message(136);
				}
			}

			$content_response = $curl_output['content']; //return response from advocate in json format

			$json_decode_content_response = json_decode($content_response,true);

			$rsp_content_temp = $json_decode_content_response['rsp'];
			if(is_array($rsp_content_temp)){
				$rsp_content = array_merge($rsp_content, $rsp_content_temp);
			}
		}
		}

		for ($k=0;$k<count($rsp_content);$k++) {
			$key_sort[$k] = $rsp_content[$k]['clusterid'];
		}

		$output_message='';
		if(isset($key_sort)){
			asort($key_sort);
			if(cacti_sizeof($key_sort)){
			foreach( $key_sort as $key => $val) {
				foreach ($rsp_content as $key_rsp_content => $value) {
					if ($key_rsp_content == $key) {
						if (strchr($value['name'], '|')) {
							$messages = explode("|", $value['status_message']);
							foreach ($messages as $message) {
								if(strlen($message) > 0) {
									if(strstr($message, "Unable to")) {
										$count_fail ++;
										$return_status = __('Failed. Status Code: %d', $value['status_code'], 'grid');
									} else {
										$return_status = __('Ok');
										$count_ok ++;
									}
									$message='Status:' . $return_status . ' - Cluster Name:' . grid_get_clustername($value['clusterid']) . ' - '.$message.'<br/>';
									$output_message=$output_message.$message;
								}
							}
						} else {
							if ($value['status_code'] == 0) {
								$return_status = 'OK';
								$count_ok ++;
							}
							else{
								$count_fail ++;
								$return_status = 'Failed. Status Code: '.$value['status_code'];
							}
							$message='Status:' . $return_status . ' - Cluster Name:' . grid_get_clustername($value['clusterid']) . ' - '.$value['status_message'].'<br/>';
				                        $output_message=$output_message.$message;
						}
					}
				}
			}
				if($count_fail>0)
              				raise_message('mymessage', $output_message, MESSAGE_LEVEL_ERROR);
           			 else
             				raise_message('mymessage', $output_message, MESSAGE_LEVEL_INFO);
			}
		}

		header('Location: grid_bhosts_closed.php?header=false');
		exit;
	}

	/* setup some variables */
	$host_list = '';
	$i = 0;

	if (isset_request_var('selected_items') && isset($_SESSION['sess_error_fields']['message'])) {
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		if ($selected_items_whole != false) {
			foreach ($selected_items_whole as $selected_item) {
				$host_whole_array[$i] = $selected_item;
				$host_details = explode(':',$selected_item);

				input_validate_input_number($host_details[1]);

				$host_list .= '<li>' . __('Host', 'grid') . ' ' . html_escape($host_details[0]) . ' ' . __('from Cluster Name', 'grid') . ' ' . grid_get_clustername($host_details[1]) . '</li>';
				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];

				$i++;
			}
		}
	} else {
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'chk_', '4') == 0) {
				$key = str_replace('@', '.', $key);
				$host_whole_array[$i] = substr($key, 4);
				$host_details = explode(':',substr($key, 4));

				/* ================= input validation ================= */
				input_validate_input_number($host_details[1]);
				/* ================= input validation ================= */

				$host_list .= '<li>' . __('Host', 'grid') . ' ' . html_escape($host_details[0]) . ' ' . __('from Cluster Name', 'grid') . ' ' . grid_get_clustername($host_details[1]) . '</li>';
				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];
				$i++;
			}
		}
	}

	general_header();

	form_start('grid_bhosts_closed.php');

	html_start_box($grid_host_control_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	switch(get_request_var('drp_action')) {
		case '1':$action = 'open';
			break;
		case '2':$action = 'close';
			break;
	}

	if (!empty($host_array)) {
		print "<tr>
			<td class='textArea' colspan=2>
				<p>" . __('Are you sure you want to %s the following host(s)?', $action, 'grid') . "</p>
				<div class='itemlist'><ul>$host_list</ul></div>
			</td>
		</tr>
		<tr>
			<td>Select LockId<br>"
				. __('LockId that are appended to LSF with the -i option.', 'grid')
				. "<br>Select the LockId for $action. Default value all. </td>
			<td><select name='action_lockid'><option value='all' selected>all</option>";
			$action_lockid_old= '';
			if (isset_request_var('action_lockid')) {
				$action_lockid_old= sanitize_search_string(get_nfilter_request_var('action_lockid'));
			}
			$lockid_result = db_fetch_assoc("SELECT DISTINCT lockid FROM grid_host_closure_lockids");
			foreach($lockid_result as $result) {
				print '<option value="'.$result['lockid'].'"'. ($action_lockid_old==$result['lockid'] ? ' selected':'') . '>'. $result['lockid'] . '</option>'."\n";
			}
		print "</select><br></td></tr>
		<tr>
			<td class='textArea'>
				" . __('Comments that are appended to LSF with the -C option.', 'grid');

			if (read_config_option('grid_management_clusters') != 'on') {
				print '<br>' . __('Leave BLANK if no comment is required.', 'grid');
			}

			$username = get_username($_SESSION['sess_user_id']);

			print '<br>' . __('&lt;&lt;RTM %s &gt;&gt; will be appended after your comments.', $username, 'grid') . '</td>';

			print '<td class="textArea"><input ';

			if (isset($_SESSION['sess_error_fields']['message'])) {
				if (isset_request_var('message')) {
					print " value='". sanitize_search_string(get_nfilter_request_var('message')) . "' ";
				}
			}
			if (isset($_SESSION['sess_error_fields']['message'])) {
				print "class='txtErrorTextBox'";
				unset($_SESSION['sess_error_fields']['message']);
			}

			print "type=text name='message' col='255' size='40' maxlength='512'></td>
		</tr>
		<tr>
			<td colspan='2' class='deviceDown'>" .
				__('NOTE: Wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . '
			</td>
		</tr>';
	}

	if (!isset($host_array)) {
		raise_message(40);
		header('Location: grid_bhosts_closed.php?header=false');
		exit;
	} else {
		$save_html = "<input type='submit' value='" . __esc('Yes', 'grid') . "'>";
		$button_false = __esc('No', 'grid');
	}

	print "<tr>
		<td class='saveRow' colspan='2'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='command' value=''>
			<input type='hidden' name='selected_items' value='" . (isset($host_array) ? serialize($host_array) : '') . "'>
			<input type='hidden' name='selected_items_hostname' value='" . (isset($host_array_hostname) ? serialize($host_array_hostname) : '') . "'>
			<input type='hidden' name='selected_items_whole' value='" . (isset($host_whole_array) ? serialize($host_whole_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . $button_false . "' alt='' onClick='cactiReturnTo();'>
			$save_html
		</td>
		</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function grid_view_get_bhosts_records(&$sql_where, $apply_limits = true, $rows = 30) {
	global $grid_out_of_services;

	/* request validation */
	get_filter_request_var('clusterid');

	if (get_request_var('clusterid') <= 0) {
		$status = db_fetch_assoc("SELECT DISTINCT gh.status AS bstatus, gl.status AS lstatus
			FROM grid_hosts AS gh
			INNER JOIN grid_load AS gl
			ON gh.host = gl.host
			AND gh.clusterid = gl.clusterid");
	} else {
		$status = db_fetch_assoc_prepared('SELECT DISTINCT gh.status AS bstatus, gl.status AS lstatus
			FROM grid_hosts AS gh
			INNER JOIN grid_load AS gl
			ON gh.host = gl.host
			AND gh.clusterid = gl.clusterid
			WHERE gh.clusterid = ?',
			array(get_request_var('clusterid')));
	}

	/* user id sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		$sql_where .= 'WHERE (gh.clusterid=' . get_filter_request_var('clusterid') . ')';
	}

	/* host group sql where */
	if (get_request_var('hgroup') == '-1') {
		/* Show all items */
	} else {
		if (get_request_var('clusterid') == 0) {
			$hosts = db_fetch_assoc_prepared('SELECT host
				FROM grid_hostgroups
				WHERE groupName = ?',
				array(get_request_var('hgroup')));
		} else {
			$hosts = db_fetch_assoc_prepared('SELECT host
				FROM grid_hostgroups
				WHERE groupName = ?
				AND clusterid = ?',
				array(get_request_var('hgroup'), get_request_var('clusterid')));
		}

		if (cacti_sizeof($hosts)) {
			$hgroup_hosts = '';
			$num_hosts = 0;

			foreach($hosts as $host) {
				if ($num_hosts == 0) {
					$hgroup_hosts .= db_qstr($host['host']);
				} else {
					$hgroup_hosts .= ', ' . db_qstr($host['host']);
				}

				$num_hosts++;
			}

			if (strlen($sql_where)) {
				$sql_where .= " AND (gh.host IN ($hgroup_hosts))";
			} else {
				$sql_where  = "WHERE (gh.host IN ($hgroup_hosts))";
			}
		}
	}

	if (get_request_var('resource_str') != '') {
		if (get_request_var('clusterid') > 0) {
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
				$res_tool  = ".\\gridhres.exe";
				$res_tool_fullpath = grid_get_res_tooldir(get_request_var('clusterid')) . "\\gridhres.exe";
			} else {
				$res_tool  = "./gridhres";
				$res_tool_fullpath = grid_get_res_tooldir(get_request_var('clusterid')) . '/gridhres';
			}

			if (is_executable($res_tool_fullpath)) {
				get_filter_request_var('clusterid');

				$cwd = getcwd();
				chdir(grid_get_res_tooldir(get_request_var('clusterid')));


				$res_cmd   = $res_tool . ' -C ' . get_request_var('clusterid') . ' -R ' . cacti_escapeshellarg(get_request_var('resource_str'));
				$ret_val   = 0;
				$ret_out   = array();
				$res_hosts = exec($res_cmd, $ret_out, $ret_val);

				chdir($cwd);
				if (!$ret_val) {
					if (strlen($res_hosts)) {
						if (strlen($sql_where)) {
							$sql_where .= " AND gh.host IN ($res_hosts)";
						} else {
							$sql_where = "WHERE gh.host IN ($res_hosts)";
						}
					} else {
						if (strlen($sql_where)) {
							$sql_where .= ' AND gh.host IS NULL';
						} else {
							$sql_where = 'WHERE gh.host IN NULL';
						}
					}
				} else {
					if (strlen($sql_where)) {
						$sql_where .= ' AND gh.host IS NULL';
					} else {
						$sql_where = 'WHERE gh.host IN NULL';
					}

					if ($ret_val == 96) {
						$_SESSION['sess_messages'] = __('No hosts returned', 'grid');
					} else if ($ret_val == 95) {
						$_SESSION['sess_messages'] = __('Invalid Resource String', 'grid');
					} else {
						$_SESSION['sess_messages'] = __('Unknown LSF Error: %s', $ret_val, 'grid');
					}
				}
			} else {
				cacti_log('ERROR: gridhres either does not exist or is not executable!');
			}
		} else {
			unset_request_var('resource_str');
			load_current_session_value('resource_str', 'sess_grid_view_bhosts_resource_str', '');
		}
	}

	/* hostType sql where */
	if (get_request_var('type') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (hostType=' . db_qstr(get_request_var('type')) . ')';
		} else {
			$sql_where = 'WHERE (hostType=' . db_qstr(get_request_var('type')) . ')';
		}
	}

	/* hostModel sql where */
	if (get_request_var('model') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (hostModel=' . db_qstr(get_request_var('model')) . ')';
		} else {
			$sql_where = 'WHERE (hostModel=' . db_qstr(get_request_var('model')) . ')';
		}
	}

	$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . '(gh.status="Closed-Admin")';

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ((gh.host LIKE '%" . get_request_var('filter') . "%') OR
			(gh.hCtrlMsg LIKE '%" . get_request_var('filter') . "%') OR
			(ghi.hostType LIKE '%" . get_request_var('filter') . "%') OR
			(ghi.hostModel LIKE '%" . get_request_var('filter') . "%'))";

	}

	$sql_order = get_order_string();

	$sql_query = "SELECT gc.clustername, gh.clusterid, gh.host, COUNT(IF(ghce.end_time='0000-00-00 00:00:00',1, NULL)) AS lockid_count, ghi.hostType,
		ghi.hostModel, ghi.maxCpus, gl.ut, gl.r1m,
		CONCAT_WS('',gl.status,':',gh.status) AS status,
		(CASE WHEN gl.status NOT LIKE 'U%' THEN ((ghi.maxMem - gl.mem) / ghi.maxMem) * 100 ELSE 0 END) AS memUsage,
		(CASE WHEN gl.status NOT LIKE 'U%' THEN ((ghi.maxSwap - gl.swp) / ghi.maxSwap) * 100 ELSE 0 END) AS swpUsage,
		gl.pg, gh.cpuFactor, gh.maxJobs, gh.numJobs, gh.numRun, gh.numSSUSP,
		gh.numUSUSP, gh.numUSUSP+gh.numSSUSP AS numSUSP, gh.numRESERVE, gh.time_in_state, gh.hCtrlMsg
		FROM grid_hosts AS gh
		INNER JOIN grid_clusters AS gc ON gc.clusterid = gh.clusterid
		INNER JOIN grid_hostinfo AS ghi ON ghi.clusterid = gh.clusterid AND ghi.host = gh.host
		INNER JOIN grid_load AS gl ON gl.clusterid = gh.clusterid AND gh.host = gl.host
		LEFT JOIN grid_host_closure_events AS ghce ON ghce.clusterid = gh.clusterid AND gh.host = ghce.host
		$sql_where
		GROUP BY gh.clusterid, gh.host
		$sql_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}
	return db_fetch_assoc($sql_query);
}

function bhostsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_bhosts_closed.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
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
					<?php print html_autocomplete_filter('grid_bhosts_closed.php', 'Group', 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Refresh', 'grid');?>
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
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc("SELECT DISTINCT hostModel
									FROM grid_hostinfo AS ghi
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									ORDER BY hostModel");
							} else {
								$models = db_fetch_assoc_prepared("SELECT DISTINCT hostModel
									FROM grid_hostinfo AS ghi
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									AND clusterid = ?
									ORDER BY hostModel",
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($models)) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc("SELECT DISTINCT hostType
									FROM grid_hostinfo AS ghi
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									ORDER BY hostType");
							} else {
								$types = db_fetch_assoc_prepared("SELECT DISTINCT hostType
									FROM grid_hostinfo AS ghi
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									AND clusterid = ?
									ORDER BY hostType", array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] .'"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
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
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<?php resource_browser();?>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function validate_bhosts_closed_request_vars() {
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
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'hgroup' => array(
			'filter' => FILTER_SANITIZE_STRING,
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
			'default' => 'numRun',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gbhc');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}
function grid_view_bhosts() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval,
		$minimum_user_refresh_intervals, $config, $grid_host_control_actions;

	validate_bhosts_closed_request_vars();

	grid_set_minimum_page_refresh();

	general_header();
	/* present a tabbed interface */
	$tabs_gridhost = array(
		'host'  => __('Closed Hosts', 'grid'),
		'closure_event' => __('Closure Events', 'grid')
	);
	/* draw the tabs */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";
	if (cacti_sizeof($tabs_gridhost)) {
		$i = 0;
		foreach (array_keys($tabs_gridhost) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == get_request_var('tab')) ? "class='pic selected'" : "class='pic '") . " href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_bhosts_closed.php?' .
				'tab=' . $tab_short_name) .
				"'>" . html_escape($tabs_gridhost[$tab_short_name]) . '</a></li>';
			$i++;
		}
	}
	print '</ul></nav></div>';
	print '</tr></table>';

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bhosts_closed.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
		strURL += '&filter=' + $('#filter').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&model=' + $('#model').val();
		strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_bhosts_closed.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #type, #hgroup, #model, #refresh, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$bhosts_results = grid_view_get_bhosts_records($sql_where, true, $rows);

	$debug_log = debug_log_return('grid_admin');

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

	html_start_box(__('Closed Admin Host Filters', 'grid'), '100%', '', '3', 'center', '');
	bhostsFilter();
	html_end_box();

	/* print checkbox form for validation */
	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		form_start('grid_bhosts_closed.php', 'chk');
	}

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM grid_hosts AS gh
		INNER JOIN grid_clusters AS gc ON gc.clusterid = gh.clusterid
		INNER JOIN grid_hostinfo AS ghi ON ghi.clusterid = gh.clusterid AND ghi.host = gh.host
		INNER JOIN grid_load AS gl ON gl.clusterid = gh.clusterid AND gh.host = gl.host
		$sql_where");

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'grid')
		),
		'gh.host' => array(
			'display' => __('Host Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'dbname'  => 'host_cluster',
			'sort'    => 'ASC'
		),
		'hostType'    => array(
			'display' => __('Type', 'grid'),
			'tip'     => __('Auto-detected or user defined Type of the host as defined in lsf.cluster file.', 'grid'),
			'sort'    => 'ASC',
			'dbname'  => 'host_type'
		),
		'hostModel'   => array(
			'display' => __('Model', 'grid'),
			'tip'     => __('Auto-detected or user defined Model of the host as defined in lsf.cluster file.', 'grid'),
			'sort'    => 'ASC',
			'dbname'  => 'host_model'
		),
		'lockid_count' => array(
			'display' => __('Current LockId Count', 'grid'),
			'sort'    => 'ASC'
		),
		'hCtrlMsg' => array(
			'display' => __('Host Control Message', 'grid'),
			'sort'    => 'ASC'
		),
		'time_in_state' => array(
			'display' => __('TIS', 'grid'),
			'dbname'  => 'show_time_in_state',
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'numRun' => array(
			'display' => __('Run Jobs', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numSUSP' => array(
			'display' => __('Susp Jobs', 'grid'),
			'dbname'  => 'host_ssusp_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ut' => array(
			'display' => __('CPU Pct', 'grid'),
			'dbname'  => 'host_ut',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'r1m' => array(
			'display' => __('RunQ 1m', 'grid'),
			'dbname'  => 'host_r1m',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'memUsage' => array(
			'display' => __('Mem Usage', 'grid'),
			'dbname'  => 'host_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'swpUsage' => array(
			'display' => __('Page Usage', 'grid'),
			'dbname'  => 'host_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pg' => array(
			'display' => __('Page Rate', 'grid'),
			'dbname'  => 'host_pagerate',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	$display_text = form_process_visible_display_text($display_text);

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$colspan = cacti_sizeof($display_text) + 1;
	} else {
		$colspan = cacti_sizeof($display_text);
	}

	/* generate page list */
	$nav = html_nav_bar('grid_bhosts_closed.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $colspan, __('Hosts', 'grid'), 'page', 'main');

	print $nav;

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$disabled = false;
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	} else {
		$disabled = true;
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	}

	$i = 0;
	if (cacti_sizeof($bhosts_results)) {
		foreach ($bhosts_results as $bhosts) {
			$host_id = db_fetch_cell_prepared('SELECT id
				FROM host
				WHERE clusterid = ?
				AND hostname = ?',
				array($bhosts['clusterid'], $bhosts['host']));

			if (!empty($host_id)) {
				$host_graphs = db_fetch_cell_prepared('SELECT count(*)
					FROM graph_local
					WHERE host_id = ?',
					array($host_id));
			} else {
				$host_graphs = 0;
			}

			$bl = str_replace('.', '@', $bhosts['host'] . ':' . $bhosts['clusterid']);

			form_alternate_row('line' . $bl, true, $disabled);

			?>
			<td class='nowrap' style='width:1%'>
				<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details WHERE hostname=?", array($bhosts["host"]))) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&host=' . $bhosts['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts');?>'></a>
				<?php } if ($host_graphs > 0) {?><a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&graph_template_id=-1&rfilter=&host_id=' . $host_id);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Graphs');?>'></a><?php }?>
				<?php api_plugin_hook_function('grid_bhost_action_insert', $bhosts); ?>
			</td>
			<?php

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $bhosts['clusterid'] .
				'&exec_host=' . $bhosts['host'] .
				'&status=RUNNING&page=1');

			form_selectable_cell_metadata('simple', 'host', $bhosts['clusterid'], $bhosts['host'], '', '', html_escape($bhosts['host']), true, $url);
			form_selectable_cell_visible($bhosts['clustername'], 'host_cluster');
			form_selectable_cell_visible(filter_value($bhosts['hostType'], get_request_var('filter')), 'host_type');
			form_selectable_cell_visible(filter_value($bhosts['hostModel'], get_request_var('filter')), 'host_model');

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bhosts_closed.php' .
				'?header=false&tab=closure_event' .
				'&clusterid=' . $bhosts['clusterid'] .
				'&exec_host=' . $bhosts['host'] .
				'&type=active&lockid=all' .
				'&predefined_timespan=7');
			form_selectable_cell(filter_value($bhosts['lockid_count'], get_request_var('filter'), $url), '', $bl, 'nowrap');
			form_selectable_cell(filter_value($bhosts['hCtrlMsg'], get_request_var('filter')), '', $bl, 'nowrap');
			form_selectable_cell_visible(display_time_in_state($bhosts['time_in_state']), 'show_time_in_state', $bl, '', 'right');

			form_selectable_cell_visible(number_format_i18n($bhosts['numRun'], -1), '', $bl, 'right');
			form_selectable_cell_visible(number_format_i18n($bhosts['numSUSP'], -1), 'host_ssusp_slots', $bl, 'right');
			form_selectable_cell_visible(display_ut($bhosts['ut']), 'host_ut', $bl, 'right');
			form_selectable_cell_visible(display_load($bhosts['r1m']), 'host_r1m', $bl, 'right');
			form_selectable_cell_visible(($bhosts['memUsage'] > 0 ? round($bhosts['memUsage'], 2):'0').'%', 'host_mem', $bl, 'right');
			form_selectable_cell_visible(($bhosts['swpUsage'] > 0 ? round($bhosts['swpUsage'], 2):'0').'%', 'host_swap', $bl, 'right');
			form_selectable_cell_visible(display_pg($bhosts['pg'],0), 'host_pagerate', $bl, 'right');

			if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
				form_checkbox_cell($bhosts['host'], $bl, $disabled);
			}

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Closed Admin Hosts', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($bhosts_results)) {
		print $nav;
	}

	if (!$disabled) {
		draw_actions_dropdown($grid_host_control_actions);
		form_end();
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function grid_default_request_vars() {
	global $grid_timespans, $grid_timeshifts, $grid_weekdays;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_grid_closure_event');
	validate_store_request_vars($filters, 'sess_gbhc');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */
}
