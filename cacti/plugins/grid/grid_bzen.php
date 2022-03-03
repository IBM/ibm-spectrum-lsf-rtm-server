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
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/lichist/functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$views = array(
    'summary'   => array('name' => __('Status', 'grid'), 'display' => 'true', 'function' => 'show_cluster_stats'),
    'jobs'      => array('name' => __('Jobs', 'grid'),   'display' => 'true', 'function' => 'show_queue_stats'),
    'checkouts' => array('name' => __('License', 'grid'), 'display' => 'true', 'function' => 'show_license_checkouts'),
    'graphs'    => array('name' => __('Graphs', 'grid'), 'display' => 'true', 'function' => 'show_pending_reasons'),
);

$title = __('IBM Spectrum LSF RTM - Batch Host Jobs Details', 'grid');
if (!isset_request_var('action')) {
	set_request_var('action', 'zoom');
}

switch(get_request_var('action')) {
case 'zoom':
	grid_view_batch_zen();
	break;
case 'ajaxsave':
	zen_filter_save();
	break;
}

function zen_filter_save() {
	global $views;

	// Filter Settings
	$settings =
		'clusterid='  . get_request_var('clusterid')  . '|' .
		'job_user='   . get_request_var('job_user')   . '|' .
		'status='     . get_request_var('status')     . '|' .
		'rows='       . get_request_var('rows')       . '|' .
		'thumbnails=' . get_request_var('thumbnails') . '|' .
		'columns='    . get_request_var('columns')    . '|';

	foreach($views as $id => $view) {
		if (isset_request_var($id)) {
			$settings .= "$id=" . get_request_var($id) . '|';
		}
	}

	set_grid_config_option('grid_bzen', $settings);
}

function grid_view_get_jobs_records(&$total_rows, $table_name, $apply_limits = true, $rows = "30") {
	global $authfull;

	$jobs_query          = "";
	$jobs_finished_query = "";
	$sql_order = '';
	$rowsquery1          = "";
	$rowsquery2          = "";
	$authfull            = true;

	$sql_order = ' ' . get_order_string();

	if (get_request_var('status') == 'ACTIVE') {
		get_jobs_query("grid_jobs", false, $jobs_query, $rowsquery1);
	} else {
		get_jobs_query("grid_jobs_finished", false, $jobs_finished_query, $rowsquery2);
	}

	$jobs = NULL;
	if (strlen($jobs_query) && $table_name == "grid_jobs") {
		if (strlen($jobs_finished_query)) {
			$jobs_query = $jobs_query . " UNION " . $jobs_finished_query;
		}

		$jobs_query .= $sql_order;

		if ($apply_limits) {
			$jobs_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		}

		$jobs = db_fetch_assoc($jobs_query);
		$total_rows  = (strlen($rowsquery1) ? db_fetch_cell($rowsquery1):0);
		$total_rows += (strlen($rowsquery2) ? db_fetch_cell($rowsquery2):0);
	} else if (strlen($jobs_finished_query)) {
		$jobs_finished_query .= $sql_order;

		if ($apply_limits) {
			$jobs_finished_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		}

		$jobs = db_fetch_assoc($jobs_finished_query);

		$total_rows  = (strlen($rowsquery1) ? db_fetch_cell($rowsquery1):0);
		$total_rows += (strlen($rowsquery2) ? db_fetch_cell($rowsquery2):0);
	}

	return $jobs;
}

function grid_view_get_zen_records(&$total_rows, &$rows) {
	global $timespan, $total_rows, $authfull;;

	$sql_where   = "";
	$sql_where1  = "";
	$sql_where2  = "";
	$xport_array = array();
	$total_rows  = 0;
	$authfull    = true;

	/* determine the table for queries */
	if ((preg_match('/^(DONE|EXIT|FINISHED|ALL)$/', get_request_var('status')))) {
		$table_name = "grid_jobs_finished";
	} else {
		$table_name = "grid_jobs";
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	/* get the jobs */
	if (get_request_var('status') == 'STARTED' || get_request_var('status') == 'ALL' || get_request_var('status') == -1) {
		$grid_jobs_rows = 0;
		$grid_jobs_query = "";
		$grid_jobs_finished_query = "";
		$jobsquery = "";
		$rowsquery1 = "";
		$rowsquery2 = "";

		get_jobs_query("grid_jobs", false, $jobsquery, $rowsquery1);
		$grid_jobs_query = $jobsquery;

		get_jobs_query("grid_jobs_finished", false, $jobsquery, $rowsquery2);
		$grid_jobs_finished_query = $jobsquery;

		$union_jobs_query = union_grids($grid_jobs_query, $grid_jobs_finished_query, false, $rows, $total_rows);

		$jobs  = db_fetch_assoc($union_jobs_query);
	} else {
		$jobs = grid_view_get_jobs_records($total_rows, $table_name, true, $rows);
	}

	return $jobs;
}

function zenFilter() {
	global $config, $views, $grid_rows, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;

	?>
	<script type='text/javascript'>

	var custom = false;

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

		$('#views').multiselect({
			height:124,
			minWidth: 180,
			header: '<?php print __esc('Select your Views', 'grid');?>',
			close: function(event, ui) {
				applyFilter();
			}
		});

		$('#clusterid, #exec_host, #job_user, #status, #rows, #predefined_timespan, #refresh').change(function() {
			applyFilter();
		});

/*
		if ($('#predefined_timespan').val() == 0) {
			custom = true;
		}

		$('#date1, #date2').change(function() {
			if (custom != true) {
				$('#predefined_timespan').prepend('<option value="0" selected="selected">Custom</option>');
			}
			custom = true;
		});
		$('.linkOverDark').click(function(event) {
			event.preventDefault();
			loadZenPage($(this).attr('href'));
		});

		$('a.textSubHeaderDark').click(function(event) {
			event.preventDefault();
			loadZenPage($(this).attr('href'));
		});
*/

		$('#move_left').click(function(event) {
			event.preventDefault();
			strURL = 'grid_bzen.php?move_left_x=1' +
				'&exec_host=' + $('#exec_host').val()  +
				'&job_user='    + $('#job_user').val()     +
				'&date1='   + $('#date1').val()    +
				'&date2='   + $('#date2').val();

			loadZenPage(strURL);
		});

		$('#move_right').click(function(event) {
			event.preventDefault();
			strURL = 'grid_bzen.php?move_right_x=1' +
				'&exec_host=' + $('#exec_host').val()   +
				'&job_user='    + $('#job_user').val()      +
				'&date1='   + $('#date1').val()     +
				'&date2='   + $('#date2').val();

			loadZenPage(strURL);
		});
	});

	</script>

	<tr class='odd'>
		<td>
		<form name='form_grid_view_zen'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td width='1'>
						<select id='clusterid'>
						<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
						<?php
						$clusters = db_fetch_assoc('SELECT * FROM grid_clusters ORDER BY clustername');
						if (cacti_sizeof($clusters)) {
							foreach ($clusters as $cluster) {
								print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('Exec Host', 'grid');?>
					</td>
					<td>
						<select id='exec_host'>
						<?php
						if (get_request_var('clusterid') == 0) {
							$xhosts = db_fetch_assoc('SELECT DISTINCT exec_host FROM grid_jobs_exec_hosts ORDER BY exec_host');
						} else {
							$xhosts = db_fetch_assoc_prepared('SELECT exec_host FROM grid_jobs_exec_hosts WHERE clusterid= ? ORDER BY exec_host', array(get_filter_request_var('clusterid')));
						}

						if (cacti_sizeof($xhosts)) {
							foreach ($xhosts as $xhost) {
								print '<option value="' . $xhost['exec_host'] .'"'; if (substr_count(get_request_var('exec_host'),$xhost['exec_host'])) { print ' selected'; } print '>' . $xhost['exec_host'] . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('User', 'grid');?>
					</td>
					<td>
						<select id='job_user'>
							<option value='-1'<?php if (get_request_var('job_user') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$users = db_fetch_assoc("SELECT DISTINCT user_or_group AS user
									FROM grid_users_or_groups
									WHERE type='U'
									ORDER BY user");
							} else {
								$users = db_fetch_assoc_prepared("SELECT user_or_group AS user
									FROM grid_users_or_groups
									WHERE type='U'
									AND clusterid= ?
									ORDER BY user", array(get_filter_request_var('clusterid')));
							}

							if (cacti_sizeof($users)) {
								foreach ($users as $u) {
									print '<option value="' . $u['user'] .'"'; if (get_request_var('job_user') == $u['user']) { print ' selected'; } print '>' . $u['user'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td width='1'>
						<select id='status'>
							<option value='ACTIVE'<?php print (get_request_var('status') == 'ACTIVE' ? ' selected':'');?>>ACTIVE</option>
							<option value='FINISHED'<?php print (get_request_var('status') == 'FINISHED' ? ' selected':'');?>>FINISHED</option>
							<option value='DONE'<?php print (get_request_var('status') == 'DONE' ? ' selected':'');?>>DONE</option>
							<option value='EXIT'<?php print (get_request_var('status') == 'EXIT' ? ' selected':'');?>>EXIT</option>
						</select>
					</td>
					<td>
						<?php print __('Records', 'grid');?>
					</td>
					<td width='1'>
						<select id="rows">
						<?php
						$selectors = array('5' => '5', '10' => '10', '15' => '15', '20' => '20', '25' => '25', '30' => '30', '35' => '35', '40' => '40');
						if (cacti_sizeof($selectors)) {
							foreach ($selectors as $key => $value) {
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
						<?php print __('Views', 'grid');?>
					</td>
					<td>
						<select id='views' multiple='multiple' style='display:none'>
						<?php foreach($views as $id => $view) print "<option value='$id' " . (get_request_var($id) == 'true' ? ' selected':'') . '>' . $view['name'] . '</option>'; ?>
						</select>
					</td>
					<td>
                        <?php print __('Columns', 'grid');?>
					</td>
					<td>
						<select id='columns' onChange='applyFilter()'>
							<option value='1'<?php if (get_request_var('columns') == '1') {?> selected<?php }?>><?php print __('%d Column', 1, 'grid');?></option>
							<option value='2'<?php if (get_request_var('columns') == '2') {?> selected<?php }?>><?php print __('%d Columns', 2, 'grid');?></option>
							<option value='3'<?php if (get_request_var('columns') == '3') {?> selected<?php }?>><?php print __('%d Columns', 3, 'grid');?></option>
							<option value='4'<?php if (get_request_var('columns') == '4') {?> selected<?php }?>><?php print __('%d Columns', 4, 'grid');?></option>
							<option value='5'<?php if (get_request_var('columns') == '5') {?> selected<?php }?>><?php print __('%d Columns', 5, 'grid');?></option>
							<option value='6'<?php if (get_request_var('columns') == '6') {?> selected<?php }?>><?php print __('%d Columns', 6, 'grid');?></option>
						</select>
					</td>
					<td>
						<label for='thumbnails'><?php print __('Thumbnails', 'grid');?></label>
					</td>
					<td>
						<input type='checkbox' id='thumbnails' onChange='applyFilter()' <?php print get_request_var('thumbnails') == 'true' ? 'checked':'';?>>
					</td>
					<td>
						<span>
							<input type='button' id='refresh' value='Refresh' title='Refresh Values' onClick='applyFilter()'>
							<input type='button' id='save' value='Save' title='Save Defaults' onClick='saveZenFilter()'>
						</span>
					</td>
				</tr>
			</table>
			<?php if (get_request_var('status') != 'ACTIVE') {?>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Presets', 'grid');?>
					</td>
					<td>
						<select id='predefined_timespan'>
							<?php
							$grid_timespans[GT_CUSTOM] = __('Custom', 'grid');
							$start_val = 0;
							$end_val = cacti_sizeof($grid_timespans);

							if (cacti_sizeof($grid_timespans) > 0) {
								for ($value=$start_val; $value < $end_val; $value++) {
									print "<option value='" . $value . "'"; if ($_SESSION["sess_grid_current_timespan"] == $value) { print " selected"; } print ">" . title_trim($grid_timespans[$value], 40) . "</option>\n";
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
						<select id='predefined_timeshift' title='Define Shifting Interval'>
							<?php
							$start_val = 1;
							$end_val = cacti_sizeof($grid_timeshifts)+1;
							if (cacti_sizeof($grid_timeshifts) > 0) {
								for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
									print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print " selected"; } print ">" . title_trim($grid_timeshifts[$shift_value], 40) . "</option>\n";
								}
							}
							?>
						</select>
						<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward');?>'></i>
						</span>
					</td>
				</tr>
			</table>
			<?php } else {?>
				<input type='hidden' id='predefined_timespan' value='<?php print $_SESSION["sess_grid_current_timespan"];?>'>
				<input type='hidden' id='predefined_timeshift' value='<?php print $_SESSION["sess_grid_current_timeshift"];?>'>
				<input type='hidden' id='date1' size='16' value='<?php print (isset($_SESSION['sess_grid_current_date1']) ? $_SESSION['sess_grid_current_date1'] : '');?>'>
				<input type='hidden' id='date2' size='16' value='<?php print (isset($_SESSION['sess_grid_current_date2']) ? $_SESSION['sess_grid_current_date2'] : '');?>'>
			<?php }?>
		</form>
		</td>
	</tr><?php
}

function grid_view_batch_zen() {
	global $title, $views, $grid_search_types, $grid_rows, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $total_rows;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bzen', 'rows', '10')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'page_lhis' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'page_lcur' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'thumbnails' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => get_user_page_setting('grid_bzen', 'thumbnails', 'true')
			),
		'columns' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bzen', 'columns', '2')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
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
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bzen', 'job_user', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bzen', 'status', 'ACTIVE'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bzen', 'clusterid', read_grid_config_option('default_grid'))
			),
		'action' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'zoom',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => false
			),
		'exec_host' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
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
		'hgroup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'jgroup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'usergroup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'efficiency' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'exception' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'sub_host' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'jobid' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'cluster_tz' => array(
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
	);

	validate_store_request_vars($filters, 'sess_grid_view_zen');
	/* ==================================================== */

	$timespan  = initialize_timespan();
	$timeshift = grid_set_timeshift();

	process_html_variables();
	process_user_input($timespan, $timeshift);

	finalize_timespan($timespan);

	// Multiselect variables for Views
	if (cacti_sizeof($views)) {
		foreach($views as $id => $view) {
			load_current_session_value($id, 'sess_grid_view_zen_' . $id, get_user_page_setting('grid_bzen', $id, $view['display']));
		}
	}

	general_header();

	?>
	<script type='text/javascript'>

	function loadZenPage(href) {
		$('#spinner').show().addClass('fa-spin');
		$.get(href, function(html) {
			var htmlObject  = $(html);
			var matches     = html.match(/<title>(.*?)<\/title>/);
			var htmlTitle   = matches[1];
			var breadCrumbs = htmlObject.find('#breadcrumbs').html();
			var content     = htmlObject.find('#main').html();

			$('title').text(htmlTitle);
			$('#breadcrumbs').html(breadCrumbs);
			$('#main').hide();
			$('#main').html(content);

			applySkin();

			$('#main').show();

			if (typeof window.history.pushState !== 'undefined') {
				window.history.pushState({page:href}, htmlTitle, href);
			}

			myTitle = htmlTitle;

			window.scrollTo(0, 0);

			return false;
		});

		return false;
	}

	function applyFilter() {
		strURL  = '?action=zoom'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&exec_host=' + $('#exec_host').val();
		strURL += '&job_user=' + $('#job_user').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&thumbnails=' + $('#thumbnails').is(':checked');
		strURL += '&columns=' + $('#columns').val();

		if ($('#predefined_timespan').val() == 0) {
			strURL += '&date1=' + $('#date1').val();
			strURL += '&date2=' + $('#date2').val();
		} else {
			strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
			strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		}

		//strURL += '&exception=-1';
		//strURL += '&usergroup=-1';
		//strURL += '&queue=-1';
		//strURL += '&sub_host=-1';
		//strURL += '&hgroup=-1';
		//strURL += '&app=-1';
		//strURL += '&jgroup=-1';
		//strURL += '&jobid=';
		//strURL += '&filter=';
		//strURL += '&resource_str=';
		//strURL += '&efficiency=';

		$('#views').find('option').each(function(data) {
			if ($(this).is(':selected')) {
				strURL += '&' + $(this).attr('value') + '=true';
			} else {
				strURL += '&' + $(this).attr('value') + '=false';
			}
		});

		loadZenPage(strURL);
	}

	function saveZenFilter() {
		$('#spinner').show().addClass('fa-spin');
		strURL = '?action=ajaxsave&clusterid=' + $('#clusterid').val();
		strURL = strURL + '&rows=' + $('#rows').val();
		strURL = strURL + '&job_user=' + $('#job_user').val();
		strURL = strURL + '&status=' + $('#status').val();
		strURL = strURL + '&thumbnails=' + $('#thumbnails').is(':checked');
		strURL = strURL + '&columns=' + $('#columns').val();

		$('#views').find('option').each(function(data) {
			if ($(this).is(':selected')) {
				strURL += '&' + $(this).attr('value') + '=true';
			} else {
				strURL += '&' + $(this).attr('value') + '=false';
			}
		});

		$.get(strURL, function(data) {
			$('#message').text('').show().html('<?php print __esc('Filter Settings Saved', 'grid');?>').delay(500).fadeOut(500, function() {
				$('#spinner').hide().removeClass('fa-spin');
			});
		});

	}

	$(function() {
		initializeGraphs();
	});
	</script>
	<?php html_spikekill_js();?>
	<?php

	// Show the filter
	html_start_box(__('Host Job Detail Filters', 'grid') . '&nbsp;<span id="spinner" style="margin: 0px; padding: 0px; vertical-align: -10%; display: none;" class="fa fa-sync deviceUp"></span>&nbsp;<span id="message"></span>', '100%', '', '5', 'center', '');
	zenFilter();
	html_end_box();

	// Show Host Information
	if (get_request_var('summary') == 'true') {
		host_info();
	}

	// Show Job Information
	if (get_request_var('jobs') == 'true') {
		job_info();
	}

	// Show License Information
	if (get_request_var('checkouts') == 'true') {
		print "<div id='licenses'>\n";

		license_info();

		print "</div>\n";
	}

	// Show Graph Information
	if (get_request_var('graphs') == 'true') {
		graph_info();

		?>
		<script type='text/javascript'>
		$(function() {
			responsiveResizeGraphs();
		});
		</script>
		<?php
	}

	bottom_footer();
}

function host_info() {
	$lshosts_results = db_fetch_row_prepared('SELECT *
		FROM grid_hostinfo
		WHERE grid_hostinfo.clusterid= ?
		AND grid_hostinfo.host= ?', array(get_request_var('clusterid'), get_request_var('exec_host')));

	$batch_results = db_fetch_row_prepared('SELECT *
		FROM grid_hosts
		WHERE grid_hosts.clusterid= ?
		AND grid_hosts.host= ?', array(get_request_var('clusterid'), get_request_var('exec_host')));

	$load_results = db_fetch_row_prepared('SELECT *
		FROM grid_load
		WHERE grid_load.clusterid= ?
		AND grid_load.host= ?', array(get_request_var('clusterid'), get_request_var('exec_host')));

	$clustername = db_fetch_row_prepared('SELECT clustername
		FROM grid_clusters
		WHERE grid_clusters.clusterid= ?', array(get_request_var('clusterid')));

	$grid_hostinfo_gpu_results = db_fetch_assoc_prepared('SELECT *
		FROM grid_hostinfo_gpu
		WHERE clusterid= ?
		AND host= ?', array(get_request_var('clusterid'), get_request_var('exec_host')));

	$grid_hosts_gpu_results = db_fetch_assoc_prepared('SELECT *
		FROM grid_hosts_gpu
		WHERE clusterid= ?
		AND host= ?', array(get_request_var('clusterid'), get_request_var('exec_host')));

	print "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr><td><div class='panel' id='status'>";

	html_start_box(__('Host Information', 'grid'), '100%', '100%', '2', 'center', '');

	if (cacti_sizeof($lshosts_results)) {
		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Host Name', 'grid');?>
		</td>
		<?php form_selectable_cell_metadata('simple', 'host', $lshosts_results['clusterid'], $lshosts_results['host']); ?>
		<td width='15%'>
			<?php print __('Cluster Name', 'grid');?>
		</td>
			<?php form_selectable_cell($clustername['clustername'], ''); ?>
		</tr>
		<?php

		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Type', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['hostType']; ?>
		</td>
		<td width='15%'>
			<?php print __('Model', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['hostModel'];?>
		</td>
		</tr>
		<?php

		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Number of CPUs', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['maxCpus']; ?>
		</td>
		<td width='15%'>
			<?php print __('CPU Factor', 'grid');?>
		</td>
		<td width='35%'>
			<?php print round($lshosts_results['cpuFactor']);?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Max Mem', 'grid');?>
		</td>
		<td width='35%'>
			<?php print display_memory($lshosts_results['maxMem']); ?>
		</td>
		<td width='15%'>
			<?php print __('Max Swap', 'grid');?>
		</td>
		<td width='35%'>
			<?php print display_memory($lshosts_results['maxSwap']);?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Max Temp', 'grid');?>
		</td>
		<td width='35%'>
			<?php print display_memory($lshosts_results['maxTmp']); ?>
		</td>
		<td width='15%'>
			<?php print __('Resources', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['resources'];?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Exclusive Resources', 'grid');?>
		</td>
		<td width='35%'>
			<?php
			$excl_resources = trim($lshosts_results['excl_resources']);
			if(strlen($excl_resources) > 0 && $excl_resources != '-') {
				$excl_resources = str_replace(' ', ' !', $excl_resources);
				$excl_resources = '!' . $excl_resources;
			} else {
				$excl_resources = $lshosts_results['excl_resources'];
			}
			print $excl_resources;
			?>
		</td>
		<td width='15%'>
		</td>
		<td width='35%'>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='15%'>
			<?php print __('Number of GPUs', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['ngpus']; ?>
		</td>
		<td width='15%'>
			<?php print __('GPU Factor', 'grid');?>
		</td>
		<td width='35%'>
			<?php print $lshosts_results['gMaxFactor'];?>
		</td>
		</tr>
  		<?php
	} else {
		print '<tr><td colspan="4"><em>' . __('No Host Information Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	html_start_box('Host Load Information', '100%', '', '3', 'center', '');
	if (cacti_sizeof($load_results) > 0) {
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('Status', 'grid');?>
		</td>
		<td width='16%'>
			<?php print $load_results['status']; ?>
		</td>
		<td width='16%'>
			<?php print __('Login Sessions', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_ls($load_results['ls']);?>
		</td>
		<td width='16%'>
			<?php print __('Idle Time', 'grid');?>
		</td>
		<td width='20%'>
			<?php print display_hours($load_results['it']);?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('R15S', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_load($load_results['r15s']); ?>
		</td>
		<td width='16%'>
			<?php print __('R1M', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_load($load_results['r1m']);?>
		</td>
		<td width='16%'>
			<?php print __('R15M', 'grid');?>
		</td>
		<td width='20%'>
			<?php print display_load($load_results['r15m']);?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('CPU %%%', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_ut($load_results['ut']); ?>
		</td>
		<td width='16%'>
			<?php print __('Paging Rate', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_pg($load_results['pg']);?>
		</td>
		<td width='16%'>
			<?php print __('I/O Rate', 'grid');?>
		</td>
		<td width='20%'>
			<?php print display_load($load_results['io']);?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('Free Memory', 'grid');?>
			</td>
		<td width='16%'>
			<?php print display_memory($load_results['mem']); ?>
		</td>
		<td width='16%'>
			<?php print __('Free Swap', 'grid');?>
		</td>
		<td width='16%'>
			<?php print display_memory($load_results['swp']);?>
		</td>
		<td width='16%'>
			<?php print __('Free Temp', 'grid');?>
		</td>
		<td width='20%'>
			<?php print display_memory($load_results['tmp']);?>
		</td>
		</tr>
		<?php
	} else {
		print "<tr><td colspan='6'><em>No Host Load Records Found</em></td></tr>";
	}

	html_end_box(false);

	html_start_box('Batch Information', '100%', '', '3', 'center', '');

	if (cacti_sizeof($batch_results)) {
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('Host Status', 'grid');?>
		</td>
		<td width='16%'>
			<?php print $batch_results['status']; ?>
		</td>
		<td width='16%'>
			<strong> </strong>
		</td>
		<td width='16%'>
			<?php print '';?>
		</td>
		<td width='16%'>
			<strong> </strong>
		</td>
		<td width='20%'>
			<?php print '';?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('Max Jobs', 'grid');?>
		</td>
		<td width='16%'>
			<?php print $batch_results['maxJobs']; ?>
		</td>
		<td width='16%'>
			<?php print __('Num Jobs', 'grid');?>
		</td>
		<td width='16%'>
			<?php print $batch_results['numJobs'];?>
		</td>
		<td width='16%'>
			<?php print __('Running Jobs', 'grid');?>
		</td>
		<td width='20%'>
			<?php print $batch_results['numRun'];?>
		</td>
		</tr>
		<?php
		form_alternate_row();?>
		<td width='16%'>
			<?php print __('SSUSP %s', format_job_slots(), 'grid');?>
		</td>
		<td width='16%'>
			<?php print $batch_results['numSSUSP']; ?>
		</td>
		<td width='16%'>
			<?php print __('USUSP %s', format_job_slots(), 'grid');?>
		</td>
		<td width='16%'>
			<?php print $batch_results['numUSUSP'];?>
		</td>
		<td width='16%'>
			<?php print __('Reserved %s', format_job_slots(), 'grid');?>
		</td>
		<td width='20%'>
			<?php print $batch_results['numRESERVE'];?>
		</td>
		</tr>

		<?php
	} else {
		print '<tr><td colspan="4"><em>' . __('No Batch Information Found', 'grid') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($grid_hostinfo_gpu_results)) {
		html_start_box(__('GPU Information', 'grid'), '100%', '', '3', 'center', '');
		$display_text = array(
			'GPU_Id' => array(
				'display' => __('GPU Id', 'grid'),
				'tip' => __('The GPU ID on the host', 'grid'),
				'align'   => 'left'
			),
			'Brand' => array(
				'display' => __('Brand', 'grid'),
				'tip' => __('The GPU brand name', 'grid'),
				'align'   => 'left'
			),
			'Model' => array(
				'display' => __('Model', 'grid'),
				'tip' => __('The GPU model type', 'grid'),
				'align'   => 'left'
			),
			'Mode' => array(
				'display' => __('Mode', 'grid'),
				'tip' => __('The GPU mode', 'grid'),
				'align'   => 'left'
			),
			'Error' => array(
				'display' => __('Error', 'grid'),
				'tip' => __('The GPU error message', 'grid'),
				'align'   => 'left'
			),
			'Factor' => array(
				'display' => __('Factor', 'grid'),
				'tip' => __('Factor of the GPU', 'grid'),
				'align'   => 'right'
			),
			'Temp' => array(
				'display' => __('Temp', 'grid'),
				'tip' => __('Temperature of the GPU', 'grid'),
				'align'   => 'right'
			),
			'ECC' => array(
				'display' => __('ECC', 'grid'),
				'tip' => __('Error Correcting Code of the GPU', 'grid'),
				'align'   => 'right'
			),
			'UT' => array(
				'display' => __('UT', 'grid'),
				'tip' => __('The utilization of the GPU', 'grid'),
				'align'   => 'right'
			),
			'Memory_UT' => array(
				'display' => __('Memory UT', 'grid'),
				'tip' => __('The memory utilization of the GPU', 'grid'),
				'align'   => 'right'
			),
			'Memory Total' => array(
				'display' => __('Memory Total', 'grid'),
				'tip' => __('The total of GPU memory', 'grid'),
				'align'   => 'right'
			),
			'Memory Used' => array(
				'display' => __('Memory Used', 'grid'),
				'tip' => __('The amount of GPU memory that is actually used by jobs', 'grid'),
				'align'   => 'right'
			),
			'Type' => array(
				'display' => __('Type', 'grid'),
				'tip' => __('The type of the GPU', 'grid'),
				'align'   => 'right'
			),
			'Driver Version' => array(
				'display' => __('Driver Version', 'grid'),
				'tip' => __('The driver version of the GPU', 'grid'),
				'align'   => 'right'
			)
		);
		html_header($display_text);
		$i = 0;
		foreach($grid_hostinfo_gpu_results as $detail) {
			form_alternate_row();

			form_selectable_cell('GPU'.$detail['gpu_id'], $i);
			form_selectable_cell($detail['gBrand'], $i);
			form_selectable_cell($detail['gModel'], $i);
			if($detail['gpu_mode'] == 0) {
				$gpu_mode = 'Shared';
			} else {
				$gpu_mode = 'Exclusive';
			}
			form_selectable_cell($gpu_mode, $i);
			form_selectable_cell($detail['gpu_error'], $i);
			form_selectable_cell($detail['gpu_factor'], $i, '', 'right');
			form_selectable_cell($detail['gpu_temp'], $i, '', 'right');
			form_selectable_cell($detail['gpu_ecc'], $i, '', 'right');
			form_selectable_cell($detail['gpu_ut'] .'%', $i, '', 'right');
			form_selectable_cell($detail['gpu_mut'] .'%', $i, '', 'right');
			form_selectable_cell(display_memory($detail['gpu_mtotal']), $i, '', 'right');
			form_selectable_cell(display_memory($detail['gpu_mused']), $i, '', 'right');
			if($detail['gvendor']==1) {
				$gvendor = 'AMD';
			} else if ($detail['gvendor']==2) {
				$gvendor = 'NVIDIA';
			} else {
				$gvendor = 'Unkown';
			}
			form_selectable_cell($gvendor, $i, '', 'right');
			form_selectable_cell($detail['driverVersion'], $i, '', 'right');

			form_end_row();
			$i++;
		}
		html_end_box();
	}

	if (cacti_sizeof($grid_hosts_gpu_results)) {
		html_start_box(__('GPU Job Information', 'grid'), '100%', '', '3', 'center', '');
		$display_text = array(
			'GPU_Id' => array(
				'display' => __('GPU Id', 'grid'),
				'tip' => __('The GPU ID on the host', 'grid'),
				'align'   => 'left'
			),
			'SocketId' => array(
				'display' => __('Socket Id', 'grid'),
				'tip' => __('The socket ID of the GPU on the host', 'grid'),
				'align'   => 'left'
			),
			'Status' => array(
				'display' => __('Status', 'grid'),
				'tip' => __('Status of the GPU', 'grid'),
				'align'   => 'left'
			),
			'Pstate' => array(
				'display' => __('Pstate', 'grid'),
				'tip' => __('Pstate range from P0 to P15, with P0 being the highest performance/power state, and P15 being the lowest performance/power state', 'grid'),
				'align'   => 'left'
			),
			'MUSED' => array(
				'display' => __('MUSED', 'grid'),
				'tip' => __('The amount of GPU memory that is actually used by jobs', 'grid'),
				'align'   => 'right'
			),
			'MRSV' => array(
				'display' => __('MRSV', 'grid'),
				'tip' => __('The amount of GPU memory that is reserved by jobs', 'grid'),
				'align'   => 'right'
			),
			'NJOBS' => array(
				'display' => __('NJOBS', 'grid'),
				'tip' => __('The total number of jobs that are using the GPU', 'grid'),
				'align'   => 'right'
			),
			'RUN' => array(
				'display' => __('RUN', 'grid'),
				'tip' => __('The total number of running jobs that are using the GPU', 'grid'),
				'align'   => 'right'
			),
			'SUSP' => array(
				'display' => __('SUSP', 'grid'),
				'tip' => __('The total number of suspended jobs that are using the GPU', 'grid'),
				'align'   => 'right'
			),
			'RSV' => array(
				'display' => __('RSV', 'grid'),
				'tip' => __('The total number of pending jobs that reserved the GPU', 'grid'),
				'align'   => 'right'
			)
		);
		html_header($display_text);
		$i = 0;
		foreach($grid_hosts_gpu_results as $detail) {
			form_alternate_row();

			form_selectable_cell('GPU'.$detail['gpu_id'], $i);
			form_selectable_cell($detail['socketid'], $i);
			form_selectable_cell($detail['status'], $i);
			form_selectable_cell('P'.$detail['pstatus'], $i);
			form_selectable_cell(display_memory($detail['mem_used']), $i, '', 'right');
			form_selectable_cell(display_memory($detail['mem_rsv']), $i, '', 'right');
			form_selectable_cell($detail['numJobs'], $i, '', 'right');
			form_selectable_cell($detail['numRun'], $i, '', 'right');
			form_selectable_cell($detail['numSUSP'], $i, '', 'right');
			form_selectable_cell($detail['numRSV'], $i, '', 'right');
			form_end_row();
			$i++;
		}
		html_end_box();
	}
	print "</div></td></tr></table>\n";

	api_plugin_hook('grid_page_bottom');
}

function job_info() {
	global $config, $total_rows;

	$total_rows = 0;
	$rows       = 0;

	$job_results = grid_view_get_zen_records($total_rows, $rows);

    $jobs_page = $config['url_path'] . 'plugins/grid/grid_bzen.php';

    display_job_results($jobs_page, 'grid_jobs', $job_results, $rows, $total_rows);

    display_job_legend();
}

function license_info($job = array()) {
	global $config, $total_rows;

	$check_license_plugin = db_fetch_cell("SELECT status FROM plugin_config WHERE directory='license'");

	$total_up_license_services = 0;
	if ($check_license_plugin == 1) {
		$total_up_license_services = db_fetch_cell("SELECT count(*) FROM lic_services WHERE disabled=''");
	}

	if ($total_up_license_services > 0) {
		if (get_request_var('rows') == -1) {
			$rows = read_grid_config_option('grid_records');
		} elseif (get_request_var('rows') == -2) {
			$rows = 99999999;
		} else {
			$rows = get_request_var('rows');
		}
		$display_text = build_license_display_array();
		array_splice($display_text, 5, 1);

		if (get_request_var('status') == 'ACTIVE') {
			if (cacti_sizeof($job)) {
				$tmp_job = db_fetch_row_prepared('SELECT jobid, exec_host, user FROM grid_jobs
					WHERE (jobid= ?
					AND indexid= ?
					AND submit_time= ?
					AND clusterid= ?)', array($job['jobid'], $job['indexid'], date('Y-m-d H:i:s', $job['submit_time']), $job['clusterid']));

				$job['exec_host'] = $tmp_job['exec_host'];
				$job['user'] = $tmp_job['user'];
				$license_details = grid_get_license_records($job['exec_host'], $job['user'], true, $rows);
			} else {
				$license_details = grid_get_license_records(get_request_var('exec_host'), get_request_var('job_user'), true, $rows);
			}

			html_start_box(__('Current License Usage', 'grid'), '100%', '', '3', 'center', '');

			/* generate page list */
			$nav = html_nav_bar('grid_bzen.php?action=zoom', MAX_DISPLAY_PAGES, get_request_var('page_lcur'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Checkouts', 'grid'), 'page_lcur', 'main');

			print $nav;

			html_header($display_text);

			$i = 0;
			if (cacti_sizeof($license_details)) {
				foreach($license_details as $detail) {
					form_alternate_row();

					if (isset($detail['tokens_released_date'])) {
						if ($detail['tokens_released_date'] != '0000-00-00 00:00:00') {
							$returned = $detail['tokens_released_date'];
							$duration = strtotime($detail['tokens_released_date']) - strtotime($detail['tokens_acquired_date']);
						} else {
							$returned = __('N/A', 'grid');
							$duration = time() - strtotime($detail['tokens_acquired_date']);
						}
					} else {
						$returned = __('N/A', 'grid');
						$duration = time() - strtotime($detail['tokens_acquired_date']);
					}

					form_selectable_cell($detail['server_name'], $i);
					form_selectable_cell($detail['feature_name'], $i);
					form_selectable_cell($detail['username'], $i);
					form_selectable_cell(strtoupper($detail['status']), $i);
					form_selectable_cell($detail['feature_version'], $i);
					form_selectable_cell($detail['tokens_acquired'], $i);
					form_selectable_cell($detail['tokens_acquired_date'], $i);
					form_selectable_cell($returned, $i);
					form_selectable_cell(grid_format_seconds($duration/60), $i);

					form_end_row();
				}

				html_end_box();

				print $nav;

				$i++;
			} else {
				print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Active License Checkouts Found', 'grid') . '</em></td></tr>';

				html_end_box();
			}
		}

		if (cacti_sizeof($job)) {
			//print "<pre>";print_r($job);print "</pre>";
			$one_job     = true;
			$exec_host   = get_request_var('exec_host');
			$clusterid   = $job['clusterid'];
			$jobid       = $job['jobid'];
			$indexid     = $job['indexid'];
			$submit_time = date('Y-m-d H:i:s', $job['submit_time']);

			$start_time  = date('Y-m-d H:i:s', $job['start_time']);
			$end_time    = date('Y-m-d H:i:s', $job['end_time']);

			$sql_query = generate_partition_union_query('SELECT ' . build_jobs_select_list('grid_jobs_finished', 'grid_jobs_finished') . "
				FROM grid_jobs_finished
				WHERE clusterid=$clusterid" .
				($one_job ? "
				AND jobid=$jobid
				AND indexid=$indexid
				AND submit_time='$submit_time'":'') . "
				AND exec_host='$exec_host'",
				'grid_jobs_finished',
				'', $start_time, $end_time);

			$job = db_fetch_row($sql_query);
		} else {
			$one_job          = false;
			$clusterid        = get_request_var('clusterid');
			$start_time       = $_SESSION['sess_grid_current_date1'];
			$end_time         = $_SESSION['sess_grid_current_date2'];
			$exec_host        = get_request_var('exec_host');

			$sql_query = generate_partition_union_query("SELECT min(start_time) AS start_time, max(end_time) AS end_time
				FROM grid_jobs_finished
				WHERE clusterid=$clusterid" .
				($one_job ? "
				AND jobid=$jobid
				AND indexid=$indexid
				AND submit_time='$submit_time'":'') . "
				AND exec_host='$exec_host'",
				'grid_jobs_finished',
				'', $start_time, $end_time);

			$job = db_fetch_row("SELECT min(start_time) AS start_time, max(end_time) AS end_time FROM ($sql_query) AS rs");

			$job['stat']       = get_request_var('status');
			$job['clusterid']  = get_request_var('clusterid');
			$job['exec_host']  = get_request_var('exec_host');
			$job['user']       = get_request_var('job_user');
			$job['start_time'] = $start_time;
			$job['end_time']   = $end_time;
		}

		$total_rows = 0;
		$license_details  = grid_get_license_events($job, $total_rows, $apply_limits = true, $rows);

		html_start_box(__('Historical License Usage', 'grid'), '100%', '', '3', 'center', '');

		$display_text = build_license_display_array();

		/* generate page list */
		$nav = html_nav_bar('grid_bzen.php?action=zoom', MAX_DISPLAY_PAGES, get_request_var('page_lhis'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Checkouts', 'grid'), 'page_lhis', 'main');

		print $nav;

		html_header($display_text);

		$i = 0;
		if (cacti_sizeof($license_details)) {
			foreach($license_details as $detail) {
				form_alternate_row();

				if (isset($detail['tokens_released_date'])) {
					if ($detail['tokens_released_date'] != '0000-00-00 00:00:00') {
						$returned = $detail['tokens_released_date'];
						$duration = strtotime($detail['tokens_released_date']) - strtotime($detail['tokens_acquired_date']);
					} else {
						$returned = __('N/A', 'grid');
						$duration = time() - strtotime($detail['tokens_acquired_date']);
					}
				} else {
					$returned = __('N/A', 'grid');
					$duration = time() - strtotime($detail['tokens_acquired_date']);
				}

				if ($detail['conflicting_jobids_count'] == 'N/A') {
					$conflicting_jobids_count = __('N/A', 'grid');
				} elseif ($detail['conflicting_jobids_count'] > 0) {
					$conflicting_jobids_count = __('%s Conflicts', $detail['conflicting_jobids_count'], 'grid');
				} else {
					$conflicting_jobids_count = __('No Conflicts', 'grid');
				}

				form_selectable_cell($detail['server_name'], $i);
				form_selectable_cell($detail['feature_name'], $i);
				form_selectable_cell($detail['username'], $i);
				form_selectable_cell(strtoupper($detail['status']), $i);
				form_selectable_cell($detail['feature_version'], $i);
				form_selectable_cell($conflicting_jobids_count, $i);
				form_selectable_cell($detail['tokens_acquired'], $i);
				form_selectable_cell($detail['tokens_acquired_date'], $i);
				form_selectable_cell($returned, $i);
				form_selectable_cell(grid_format_seconds($duration/60), $i);

				form_end_row();

				$i++;
			}
		} else {
			print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Historical License Events Found', 'grid') . '</em></td></tr>';
		}

		html_end_box();

		if (cacti_sizeof($license_details)) {
			print $nav;
		}
	}
}

function graph_info() {
	global $config, $total_rows, $current_user, $timespan;

	$host_id = db_fetch_cell_prepared('SELECT id
		FROM host
		WHERE hostname = ?
		AND clusterid = ?',
		array(get_request_var('exec_host'), get_request_var('clusterid')));

	if (!empty($host_id)) {
		if (empty($current_user['show_preview'])) {
			print '<em>' . __('Unable to view Graph data due to permissions', 'grid') . '</em>';
			return;
		}

		$total_rows = 0;
		$sql_where = 'gl.host_id = ' . $host_id;

		$graphs = get_allowed_graphs($sql_where, 'gtg.title_cache', '', $total_rows);

		foreach($graphs as $index => $graph) {
			$graphs[$index]['height'] = read_config_option('default_graph_height');
			$graphs[$index]['width'] = read_config_option('default_graph_width');
		}
	} else {
		$total_rows = 0;
		$graphs     = array();
	}

	html_start_box(__('Host Graphs', 'grid'), '100%', '', '2', 'center', '');

	$nav = html_nav_bar('', 1, 1, 100, $total_rows, 30, __('Graphs'), '', 'main');

	print $nav;

	if (cacti_sizeof($graphs)) {
		if (get_request_var('thumbnails') == 'true') {
			grid_graph_thumbnail_area($graphs, $timespan['begin_now'], $timespan['end_now'], get_request_var('columns'));
		} else {
			grid_graph_area($graphs, $timespan['begin_now'], $timespan['end_now'], get_request_var('columns'));
		}

		html_end_box();

		print $nav;
	} else {
		html_end_box();
	}
}

function build_license_display_array() {
	$display_text = array(
		__('License Server Name', 'grid'),
		__('Feature', 'grid'),
		__('User', 'grid'),
		__('Status', 'grid'),
		__('Version', 'grid'),
		__('Conflicts', 'grid'),
		__('Tokens', 'grid'),
		__('Acquired', 'grid'),
		__('Max Released', 'grid'),
		__('Duration', 'grid')
	);

	return $display_text;
}

function grid_get_license_records($host, $user, $apply_limits = true, $rows = "30") {
	global $total_rows;

	if (strchr($host, '.')) {
		$short_hostname=substr($host, 0, strpos($host, '.'));
	} else {
		$short_hostname=$host;
	}

	if (get_request_var('status') == 'ACTIVE') {
		$sql_params = array();
		$sql_query = 'SELECT lsfd.*, ls.server_name,
			unix_timestamp()-unix_timestamp(tokens_acquired_date) AS duration
			FROM lic_services_feature_details AS lsfd
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfd.service_id
			WHERE lsfd.hostname= ?
			' . ($user != '-1' ? 'AND username= ?':'') . '
			AND lsfd.status IN ("start","queued")';
		$sql_params[] = $short_hostname;
		$user != '-1' ? ($sql_params[] = $user):'';

		$total_rows = cacti_sizeof(db_fetch_assoc_prepared($sql_query, $sql_params));

		if ($apply_limits) {
			$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page_lcur')-1)) . ',' . $rows;
		}

		return db_fetch_assoc_prepared($sql_query, $sql_params);
	} else {
		return array();
	}
}

// Get all the license events that may relate to a specific job or host for a time range
//
// @param $job  - An array of key job information
// @param $host - An array of key host information and time range
// @param $row_count - The number of event rows found
// @param $apply_limits - Return only the limited number of rows or all rows
// @param $rows - When applying limits, the number of rows to return
//
// @return An array of license checkouts or events related to the given job, or time period for a host and user
function grid_get_license_events($job, &$row_count, $apply_limits = true, $rows) {
	if (isset($job['jobid'])) {
		$one_job     = true;
		$clusterid   = $job['clusterid'];
		$jobid       = $job['jobid'];
		$indexid     = $job['indexid'];
		$submit_time = $job['submit_time'];
		$user        = $job['user'];
		$exec_host   = $job['exec_host'];
	} else {
		$one_job     = false;
		$clusterid   = $job['clusterid'];
		$exec_host   = $job['exec_host'];
		$user        = $job['user'];
	}

	//print "<pre>Events -> ";print_r($job);print "</pre>";

	// Because lmstat only returns minutes, not seconds, accuracy
	$start_time = date('Y-m-d H:i:00', strtotime($job['start_time']));
	$end_time   = $job['end_time'];
	$sql_query ='';

	if ($job['stat'] == 'ACTIVE') {
		$sql_query = "SELECT server_name, lsfh.feature_name, lsfh.status, lsfh.feature_version, username,
			groupname, hostname, chkoutid, tokens_acquired, tokens_acquired_date,
			last_poll_time, tokens_released_date,
			unix_timestamp(last_poll_time)-unix_timestamp(tokens_acquired_date) AS duration,
			'N/A' as conflicting_jobids,
			'N/A' as conflicting_jobids_count
			FROM lic_services_feature_history AS lsfh
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfh.service_id
			WHERE tokens_acquired_date> (SELECT MIN(start_time) FROM grid_jobs WHERE exec_host='$exec_host' AND stat='RUNNING'
			" . ($user != '-1' ? "AND user='$user'":"") . "
		)
			" . ($user != '-1' ? "AND username='$user'":"") . "
			AND hostname='$exec_host'
			AND last_poll_time<=NOW()";
	} else if ($job['stat'] == 'DONE' || $job['stat'] == 'EXIT' || $job['stat'] == 'FINISHED') {
		// Now construct the query
		if ($start_time != '0000-00-00 00:00:00') {
			if (read_config_option('grid_partitioning_enable') == 'on') {
				$sql_query = generate_partition_union_query("SELECT count(*)
					FROM lic_services_feature_history_mapping
					WHERE clusterid=$clusterid" .
					($one_job ? " AND jobid=$jobid
					AND indexid=$indexid
					AND submit_time='$submit_time'":'') . "
					AND exec_host='$exec_host'",
					'lic_services_feature_history_mapping',
					'', $start_time, date('Y-m-d H:i:00', strtotime($end_time) + 300));
			} else {
				$sql_query = "SELECT count(*)
					FROM lic_services_feature_history_mapping
					WHERE clusterid=$clusterid" .
					($one_job ? " AND jobid=$jobid
					AND indexid=$indexid
					AND submit_time='$submit_time'":'') . "
					AND exec_host='$exec_host'
					AND tokens_released_date BETWEEN '$start_time' AND '$end_time'";
			}
		} else {
			$sql_query = "SELECT '1' AS number WHERE 1 = 0";
		}

		$count = 0;
		if (strlen($sql_query)) {
			$count = db_fetch_cell($sql_query);
		}

		if ($count > 0) {
			if ($start_time != '0000-00-00 00:00:00') {
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$tables    = partition_get_partitions_for_query('lic_services_feature_history', $start_time, date('Y-m-d H:i:00', strtotime($end_time) + 300));
					$sql_query = '';

					if (cacti_sizeof($tables)) {
						foreach($tables as $table) {
							$partition = str_replace('lic_services_feature_history', '', $table);
							$sql_query .= (strlen($sql_query) ? ' UNION ':'') . "SELECT *
								FROM (
									SELECT b.id, ls.server_name, b.feature_name, b.status, b.feature_version,
									b.username, b.groupname, b.hostname, b.chkoutid, b.tokens_acquired,
									b.tokens_acquired_date, b.last_poll_time, b.tokens_released_date,
									unix_timestamp(b.last_poll_time)-unix_timestamp(b.tokens_acquired_date) AS duration,
									0 AS conflicting_jobids, count(distinct a.jobid) - 1 as conflicting_jobids_count
									FROM lic_services_feature_history_mapping$partition a
									INNER JOIN lic_services_feature_history$partition b
									ON (a.history_event_id = b.id)
									INNER JOIN lic_services AS ls
									ON ls.service_id=b.service_id
									WHERE a.history_event_id IN (
										SELECT history_event_id
										FROM lic_services_feature_history_mapping$partition c
										WHERE clusterid=$clusterid" .
										($one_job ? " AND jobid=$jobid
										AND indexid=$indexid
										AND submit_time='$submit_time'":"") . "
										AND exec_host='$exec_host')
									AND exec_host='$exec_host'" .
									(strlen(get_request_var('filter')) ? " AND (ls.server_name LIKE '%" . get_request_var('filter') . "%' OR c.feature_name LIKE '%" . get_request_var('filter') . "%')":"") . "
									GROUP BY b.id, b.service_id, b.feature_name, b.feature_version,
									b.username, b.groupname, b.hostname, b.tokens_acquired, b.tokens_acquired_date,
									b.last_poll_time, b.tokens_released_date
								) c";
						}
					}
				} else {
					$sql_query = "SELECT *
						FROM (
							SELECT b.id, ls.server_name, b.feature_name, b.status, b.feature_version,
							b.username, b.groupname, b.hostname, b.chkoutid, b.tokens_acquired,
							b.tokens_acquired_date, b.last_poll_time, b.tokens_released_date,
							unix_timestamp(b.last_poll_time)-unix_timestamp(b.tokens_acquired_date) AS duration,
							0 AS conflicting_jobids, count(distinct a.jobid) - 1 as conflicting_jobids_count
							FROM lic_services_feature_history_mapping a
							INNER JOIN lic_services_feature_history b
							ON (a.history_event_id = b.id)
							INNER JOIN lic_services AS ls
							ON ls.service_id=b.service_id
							WHERE a.history_event_id IN (
								SELECT history_event_id
								FROM lic_services_feature_history_mapping
								WHERE clusterid=$clusterid" .
								($one_job ? " AND jobid=$jobid
								AND indexid=$indexid
								AND submit_time='$submit_time'":"") . "
								AND exec_host='$exec_host')
							AND ((b.tokens_acquired_date >= '$start_time') AND (b.tokens_released_date <='$end_time' OR ('$end_time' BETWEEN b.last_poll_time AND b.tokens_released_date)))
							AND exec_host='$exec_host'" .
							(strlen(get_request_var('filter')) ? " AND (ls.server_name LIKE '%" . get_request_var('filter') . "%' OR c.feature_name LIKE '%" . get_request_var('filter') . "%')":"") . "
							GROUP BY b.id, ls.server_name, b.feature_name, b.feature_version,
							b.username, b.groupname, b.hostname, b.tokens_acquired, b.tokens_acquired_date,
							b.last_poll_time, b.tokens_released_date
						) AS c";
				}
			} else {
				$sql_query = "SELECT '1' AS number WHERE 1 = 0";
			}
		} else {
			$sql_query = "SELECT
				server_name, lsfh.feature_name, lsfh.status, lsfh.feature_version,
				username, groupname, hostname, chkoutid, tokens_acquired,
				tokens_acquired_date, last_poll_time, tokens_released_date,
				unix_timestamp(last_poll_time)-unix_timestamp(tokens_acquired_date) AS duration,
				'N/A' AS conflicting_jobids, 'N/A' AS conflicting_jobids_count
				FROM lic_services_feature_history AS lsfh
				INNER JOIN lic_services AS ls
				ON ls.service_id=lsfh.service_id
				WHERE tokens_acquired_date>'$start_time'
				AND tokens_acquired_date<'$end_time'
				" . ($user != '-1' ? "AND username='$user'":"") . "
				AND hostname='$exec_host'
				AND '$end_time' > last_poll_time" .
				(strlen(get_request_var('filter')) ? " AND (ls.server_name LIKE '%" . get_request_var('filter') . "%' OR lsfh.feature_name LIKE '%" . get_request_var('filter') . "%')":"");

			if ($start_time != '0000-00-00 00:00:00') {
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$sql_query = generate_partition_union_query($sql_query, 'lic_services_feature_history', '', $start_time, $end_time);
				}
			} else {
				$sql_query = "SELECT '1' AS number WHERE 1 = 0";
			}
		}
	}

	if ($sql_query != '') {
		$total_rows = db_fetch_assoc($sql_query);
		$row_count = count($total_rows);

		if ($apply_limits) {
            $sql_query .= ' LIMIT ' . ($rows*(get_request_var('page_lhis')-1)) . ',' . $rows;
		}

		$total_rows = db_fetch_assoc($sql_query);
	} else {
		$row_count = 0;
		$total_rows = array();
	}

	return $total_rows;
}
