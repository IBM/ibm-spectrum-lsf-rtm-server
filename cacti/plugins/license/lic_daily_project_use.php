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
include('./lib/rtm_timespan_settings.php');
include($config['library_path'] . '/rrd.php');
include_once('./plugins/license/include/lic_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Saving favourite filters 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['fav_filters']) && !empty($_POST['fav_filter_name']) && !empty($_POST['page_name'])) {
	if(!isset($_POST['overwrite'])){
		
		$sql = sprintf(
			"SELECT * FROM grid_settings WHERE user_id = %d AND name = %s AND filter_name = %s",
			(int)$_SESSION['sess_user_id'],
			db_qstr($_POST['page_name']),
			db_qstr($_POST['fav_filter_name'])
		);

		$existing_record = db_fetch_row($sql);
        if($existing_record){
			$_SESSION['fav_filter_save_data'] = [
				"fav_filter_name" => $_POST['fav_filter_name'],
				"fav_filters" => $_POST['fav_filters']
			];
			$_SESSION['fav_filter_save_stat'] = 'fail';
		} else {
			db_execute_prepared("INSERT INTO grid_settings (user_id, name, value, filter_name ) VALUES (?,?,?,?)",array($_SESSION['sess_user_id'], $_POST['page_name'], $_POST['fav_filters'], $_POST['fav_filter_name']));
			$_SESSION['fav_filter_save_stat'] = 'success'; 
		}
	}
	else {
		db_execute_prepared("UPDATE grid_settings SET value = ? WHERE  user_id = ? AND name = ? AND filter_name = ?",[ $_POST['fav_filters'], $_SESSION['sess_user_id'], $_POST['page_name'], $_POST['fav_filter_name']]);
		$_SESSION['fav_filter_save_stat'] = 'success';  
	}
	header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
	exit;
}

// Deleting filters 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['fav_filter_name']) && isset($_POST['delete_filter']) && !empty($_POST['page_name'])) { 
	$success = db_execute_prepared("DELETE FROM grid_settings WHERE  user_id = ? AND name = ? AND filter_name = ?",[$_SESSION['sess_user_id'], $_POST['page_name'], $_POST['fav_filter_name']]);
	$_SESSION['fav_filter_del_stat'] = $success; 
	header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
	exit;
}

$fav_filter_save_stat = null;
if (isset($_SESSION['fav_filter_save_stat'])) {
    $fav_filter_save_stat = $_SESSION['fav_filter_save_stat'];
    unset($_SESSION['fav_filter_save_stat']);
}

$fav_filter_save_data = null;
if (isset($_SESSION['fav_filter_save_data'])) {
    $fav_filter_save_data = $_SESSION['fav_filter_save_data'];
    unset($_SESSION['fav_filter_save_data']);
}

if($fav_filter_save_stat == "success"){
	raise_message("title", "Filters saved successfully.", MESSAGE_LEVEL_INFO);
}

$fav_filter_del_stat = null;
if (isset($_SESSION['fav_filter_del_stat'])) {
    $fav_filter_del_stat = $_SESSION['fav_filter_del_stat'];
    unset($_SESSION['fav_filter_del_stat']);
}

if($fav_filter_del_stat  == "success"){
	raise_message("title", "Filter deleted successfully.", MESSAGE_LEVEL_INFO);
}

if (isset_request_var('action') && get_request_var('action') == 'ajaxsearch') {
	if (isset_request_var('type')) {
        switch(get_request_var('type')) {
            case 'feature':
                header('Content-Type: application/json');
                $page =  isset_request_var('page') ? get_request_var('page') : 1;
                $searchTerm = isset_request_var('search') ? get_request_var('search') : "";
                $recordLimit = 30;
                $offset = $recordLimit * ($page - 1);

                $fetchRecordsQuery = "SELECT DISTINCT feature_name FROM lic_daily_project_stats " . ($searchTerm ? "WHERE feature_name LIKE ?" : "")  . " LIMIT $recordLimit OFFSET $offset";
                $fetchRecordsQueryParams = [];
                if ($searchTerm) {
                $fetchRecordsQueryParams[] = "%$searchTerm%";
                }

                $fetchedRecords = db_fetch_assoc_prepared($fetchRecordsQuery, $fetchRecordsQueryParams);

                $recordsCountQuery = "SELECT COUNT(DISTINCT feature_name) FROM lic_daily_project_stats " . ($searchTerm ? "WHERE feature_name LIKE ?" : "");
                $recordCount = db_fetch_cell_prepared($recordsCountQuery, $fetchRecordsQueryParams);

                $response = [
                "results" => [],
                "pagination" => ["more" => null]
                ];

                foreach ($fetchedRecords as $record) {
                $response["results"][] = ["id" => $record["feature_name"], "text" => $record["feature_name"]];
                }

                $response["pagination"]["more"] = $recordCount > $page * 30;

                echo json_encode($response);
            break;
            case 'project':
                header('Content-Type: application/json');
                $page =  isset_request_var('page') ? get_request_var('page') : 1;
                $searchTerm = isset_request_var('search') ? get_request_var('search') : "";
                $recordLimit = 30;
                $offset = $recordLimit * ($page - 1);

                $fetchRecordsQuery = "SELECT DISTINCT projectName FROM lic_daily_project_stats " . ($searchTerm ? "WHERE projectName LIKE ?" : "")  . " LIMIT $recordLimit OFFSET $offset";
                $fetchRecordsQueryParams = [];
                if ($searchTerm) {
                $fetchRecordsQueryParams[] = "%$searchTerm%";
                }

                $fetchedRecords = db_fetch_assoc_prepared($fetchRecordsQuery, $fetchRecordsQueryParams);

                $recordsCountQuery = "SELECT COUNT(DISTINCT projectName) FROM lic_daily_project_stats " . ($searchTerm ? "WHERE projectName LIKE ?" : "");
                $recordCount = db_fetch_cell_prepared($recordsCountQuery, $fetchRecordsQueryParams);

                $response = [
                "results" => [],
                "pagination" => ["more" => null]
                ];

                foreach ($fetchedRecords as $record) {
                $response["results"][] = ["id" => $record["projectName"], "text" => $record["projectName"]];
                }

                $response["pagination"]["more"] = $recordCount > $page * 30;

                echo json_encode($response);
            break;
        }
    }
} else {
	lic_view_daily_proj_use();
}

function convertToQueryString($input) {
	$pairs = explode('|', $input);
	$queryParts = [];

	foreach ($pairs as $pair) {
		list($key, $value) = explode('=', $pair, 2);

		// If the value has commas, convert it to an array of key[]=value
		if (strpos($value, ',') !== false) {
			$items = explode(',', $value);
			$items = array_map('trim', $items);
			$joined = implode(" ", $items);
			$queryParts[] = urlencode($key) . '=' . rawurlencode($joined);
		} else {
			$queryParts[] = urlencode($key) . '=' . rawurlencode($value);
		}	
	}

	return implode('&', $queryParts);
}

function parseCustomFilterString($input) {
	$result = [];
	$pairs = explode('|', $input);

	foreach ($pairs as $pair) {
		list($key, $value) = explode('=', $pair, 2);

		if (strpos($value, ',') !== false) {
			$result[$key] = explode(',', $value);
		} else {
			$result[$key] = $value;
		}
	}

	$result_values = [];
	$result_values["Feature"] = is_array($result["feature"]) ? $result["feature"] : [$result["feature"]];
	$result_values["Project"] = is_array($result["project"]) ? $result["project"] : [$result["project"]];
	$result_values["From"] = $result["date1"];
	$result_values["To"] = $result["date2"];
	return $result_values;
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

function dailyStatsFilter() {
	global $config, $lic_rows_selector, $grid_search_types;
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;
	array_push($lic_timeshifts, "1 Month", "2 Months", "6 Months");
	global $fav_filter_save_stat, $fav_filter_save_data, $fav_filter_del_stat;
	$pageName = "lic_daily_project_use";

	$filters = array(
		'page' => array(
				'filter' => FILTER_VALIDATE_INT,
				'default' => '1'
				),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'feature',
			'options' => array('options' => 'sanitize_search_string')
			),
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
	);

    validate_store_request_vars($filters, 'sess_dpu');

	/* set variables for first time use */
	$timespan = rtm_initialize_timespan($lic_timespans, $lic_timeshifts, "sess_dpu");
	$timeshift = rtm_set_timeshift($lic_timeshifts, "sess_dpu");

	/* process the timespan/timeshift settings */
	rtm_process_html_variables($lic_timespans, $lic_timeshifts, "sess_dpu");
	rtm_process_user_input($timespan, $timeshift, $lic_timespans, "sess_dpu");

	/* save session variables */
	rtm_finalize_timespan($timespan, $lic_timespans, "sess_dpu");

	set_request_var('date1',$_SESSION['sess_dpu_current_date1']);
	set_request_var('date2',$_SESSION['sess_dpu_current_date2']);

	$lic_features = preg_split('/\s+/', get_request_var("feature"), -1, PREG_SPLIT_NO_EMPTY);
	$lic_projects = preg_split('/\s+/', get_request_var("project"), -1, PREG_SPLIT_NO_EMPTY);
?>
	<div id="save_filters_dialog" title="Save filters" style="display: none" />
	<div class="filter-form-container">	
		<form class="filter-form" id="filter-form" method="POST" action='lic_daily_project_use.php'>
			<label for="region">Filter Name</label>
			<div style="display:grid; grid-template-rows: auto auto; gap: 4px">
				<input type="text" name="fav_filter_name" id='fav_filter_name' value=''>
				<span style="color: #ff0000; display: none" id="error-filter-input">Specify a filter name.</span>
			</div>
			<input type="hidden" name="fav_filters" id="fav_filters">
			<input type="hidden" name="page_name" id="page_name" value="<?= html_escape($pageName) ?>">
		</form>
		<?php
			html_start_box("Filters", "100%", "", "2", "center", "");
			echo "<table class='cactiTable' id='save-filter-table' style='width: 100%; table-layout: fixed;'>
			<tr class='tableHeader'>
				<th style='width: 20%;'>Name</th>
				<th style='max-width: 80%;'>Value</th>
			</tr>
			";
			echo "</table>";
			html_end_box();
		?>
	</div>
	</div>
	<div id="overwrite_filters_dialog" title="Duplicate record found" style="display: none" >
			<form id="overwrite-filter-form" method="POST" action='lic_daily_project_use.php'>
			<span class="overwrite-filter-form-text">A filter with this name <b><i><?= isset($fav_filter_save_data) ? html_escape($fav_filter_save_data["fav_filter_name"]) : "" ?></i></b> already exists. Do you want to overwrite it?</span>
			<input type="hidden" name="fav_filter_name"  id='overwrite_fav_filter_name' value=''>
			<input type="hidden" name="fav_filters" id="overwrite_fav_filters" value=''>
			<input type="hidden" name="page_name" id="overwrite_fav_page_name" value="<?= $pageName ?>">
			<input type="hidden" name="overwrite">
		</form>
	</div>
	<div id="all_filters_dialog" title="Saved Filters" style="display: none">
		<?php $all_filters =  db_fetch_assoc_prepared("SELECT * FROM grid_settings WHERE user_id = ? AND name = ? ", [$_SESSION['sess_user_id'], $pageName]); ?>
		<?php if(count($all_filters)) : ?>
			<div id="filter-accordion">
				<?php  foreach ($all_filters as $filter) : ?>
					<h3><?= html_escape($filter["filter_name"]) ?></h3>
					<div style="position: relative">
						<?php html_start_box("", "100%", "", "2", "center", ""); ?>
							<table class='cactiTable' id='save-filter-table' style='width: 100%; table-layout: fixed;'>
							<tr class='tableHeader'>
								<th style='width: 20%;'>Name</th>
								<th style='max-width: 80%;'>Value</th>
							</tr>
							<?php 
								$index = 0; 
								foreach(parseCustomFilterString($filter["value"]) as $key => $value) 
								{ 
									$rowClass = $index % 2 === 0 ? 'even tableRow' : 'odd tableRow';
							?>
								<tr class="<?= $rowClass ?>">
									<td><?= html_escape($key) ?></td>
									<td>
									<?php
										if (is_array($value)) {
											foreach ($value as $val) {
												if ($val) {
													echo '<span class="filter-array-item" title="' . html_escape($val) . '">'
														. html_escape($val)
														. '</span>';
												}
											}
										} else {
											echo html_escape($value);
										}
										?>							
									</td>
								</tr>
							<?php $index += 1;  } ?>
							</table>
						<?php html_end_box(); $filter_name =  str_replace(' ', '_', $filter["filter_name"]);?>
						<div style="display: flex; justify-content : flex-end; gap: 0.5rem; margin-top: 0.5rem">
							<button class="ui-button ui-corner-all ui-widget ui-state-active" id="delete-filter-<?= html_escape($filter_name) ?>">Delete</button>
							<button onclick="loadPageNoHeader('/cacti/plugins/license/lic_daily_project_use.php?&header=false&<?= html_escape(convertToQueryString($filter['value'])) ?>')">Apply</button>
						</div>
						<div id="delete-filter-dialog-<?=  $filter_name ?>" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; background: rgba(170, 170, 170, 0.5); z-index: 200; display: flex; justify-content : center; align-items: center">
							<div style="z-index: 300; width: 500px; background-color: white">
								<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix ui-draggable-handle">
									<span class="ui-dialog-title">Delete filter</span>
									<button type="button"  id="close-delete-icon-confirm-filter-<?=  $filter_name ?>" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close">
										<span class="ui-button-icon ui-icon ui-icon-closethick"></span>
										<span class="ui-button-icon-space"> </span>Close</button>
								</div>
								<div class="ui-dialog-content ui-widget-content" style="background-color: white; margin: 16px 0 16px 12px; display: inline-block">Are you sure you want to delete the filter <b><i><?= html_escape($filter["filter_name"]) ?></i></b>?</div>
								<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix" style="margin-top : 0">
									<form class="ui-dialog-buttonset" method="POST" action='lic_daily_project_use.php'>
										<button type="submit" class="ui-button ui-corner-all ui-widget ui-state-active" id="delete-confirm-filter-<?=  $filter_name ?>">Delete</button>
										<button type="button" class="ui-button ui-corner-all ui-widget" id="close-delete-confirm-filter-<?=  $filter_name ?>">Close</button>
										<input type="hidden" name="fav_filter_name" value="<?= html_escape($filter["filter_name"]) ?>">
										<input type="hidden" name="page_name" value="<?= html_escape($pageName) ?>">
										<input type="hidden" name="delete_filter" value="">
									</form>
								</div>
							</div>	
						</div>
						<script>
							$(function() {
								const delete_filter_name = "<?=  $filter_name ?>";
								$(`#delete-filter-dialog-${delete_filter_name}`).hide();
								$(`#delete-filter-${delete_filter_name}`).click(function(){
									$(`#delete-filter-dialog-${delete_filter_name}`).show();
								});
								$(`#close-delete-confirm-filter-${delete_filter_name}`).click(function(){
									$(`#delete-filter-dialog-${delete_filter_name}`).hide();
								});
								$(`#close-delete-icon-confirm-filter-${delete_filter_name}`).click(function(){
									$(`#delete-filter-dialog-${delete_filter_name}`).hide();
								});
							});
						</script>
					</div>
				<?php endforeach ?>
			</div>
		<?php else : ?>	
			<p style="margin-left: 8px">No filters to display!</p>
		<?php endif ?>	
	</div >
	<?php html_start_box(__('Daily Project Use Filters'), '100%', '', '3', 'center', '') ?>
	<tr class='odd'>
		<td>
			<form id='form_lic_view' action='lic_daily_project_use.php'>
				<table class='filterTable'>
					<div class="blstat-form">
						<label style="padding-right:5px">
							<?php print __('LM Feature(s)', 'license');?>
						</label>
						<select id="lic_features" multiple="multiple" class="select-multi-dd">
							<?php foreach ($lic_features as $feature) : ?>
								<option value="<?= html_escape($feature) ?>" selected="selected"><?= html_escape($feature) ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<div class="blstat-form">
						<label style="padding-right:5px; width: 78px; text-align: left">
							<?php print __('Project(s)', 'license');?>
						</label>
						<select id="lic_projects" multiple="multiple" class="select-multi-dd">
							<?php foreach ($lic_projects as $project) : ?>
								<option value="<?= html_escape($project) ?>" selected="selected"><?= html_escape($project) ?></option>
							<?php endforeach ?>
						</select>
					</div>
				</table>
				<table cellpadding='2' cellspacing='0' class='filterTable'>
					<tr>
						<td style="width: 79px">
							<?php print __('Presets', 'license');?>
						</td>
						<td>
							<select id='predefined_timespan' onChange='applyFilterChangePDTS()'>
								<?php
								if ($_SESSION['sess_dpu_custom']) {
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
										print "<option value='0'"; if ($_SESSION['sess_dpu_current_timespan'] == '0') { print ' selected'; } print '>Custom</option>';
									}
									for ($value=$start_val; $value < $end_val; $value++) {
										if ($value > 6 && $value <> GT_THIS_DAY && $value <> GT_DAY_SHIFT) {
											rtm_get_timespan($timespan, time(), $value , $first_weekdayid);
											if (strtotime($timespan['begin_now']) >= $lastday && strtotime($timespan['end_now']) >= $lastday) {
												print "<option value='" . $value . "'"; if ($_SESSION['sess_dpu_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($lic_timespans[$value], 40) . '</option>';
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
								<input type='text' class='ui-state-default ui-corner-all' id='date1' aria-label='enter the from date with the format yyyymmdd, for example 2016-09-09 is input as 20160909' size='10' value='<?php print (isset($_SESSION['sess_dpu_current_date1']) ? $_SESSION['sess_dpu_current_date1'] : '');?>'>
								<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector', 'license');?>'></i>
							</span>
						</td>
						<td>
							<?php print __('To', 'license');?>
						</td>
						<td>
							<span>
								<input type='text' class='ui-state-default ui-corner-all' id='date2' aria-label='enter the to date with the format yyyymmdd, for example 2016-09-09 is input as 20160909' size='10' value='<?php print (isset($_SESSION['sess_dpu_current_date2']) ? $_SESSION['sess_dpu_current_date2'] : '');?>'>
								<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector', 'license');?>'></i>
							</span>
						</td>
						<td>
							<span>
								<?php
								$fromDate = date("Y-m-d",strtotime("-1 day"));
								$toDate = date("Y-m-d");
								if (isset($_SESSION['sess_dpu_current_date1'])) {
									$fromDate = $_SESSION['sess_dpu_current_date1'];
								}
								if (isset($_SESSION['sess_dpu_current_date2'])) {
									$toDate = $_SESSION['sess_dpu_current_date2'];
								}
								$retention = read_config_option('lic_data_retention', true);
								$lastday = strtotime(date("Y-m-d",strtotime("-".$retention)));

								$shift = 1;
								if (isset($_SESSION['sess_dpu_current_timeshift'])) {
									$shift = $_SESSION['sess_dpu_current_timeshift'];
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
											print "<option value='" . $shift_value . "'"; if ($_SESSION['sess_dpu_current_timeshift'] == $shift_value) { print ' selected'; } print '>' . title_trim($lic_timeshifts[$shift_value], 40) . '</option>';
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
						<td style="width: 79px">
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
						<td style="padding-left: 1rem">
							<input type='submit' id='go' value='Go' title='Search' onClick="applyFilter()">
							<input type='button' id='clear' value='Clear' title='Clear Filters'>
							<input id='save-filters' type='button' value='Save'>
							<input id='all_filters' type='button' value='Filters'>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
	<?php html_end_box(true);?>
	<script>
		$(function() { 
			const filterDialog = $( "#save_filters_dialog" );
			filterDialog.dialog({
				autoOpen: false,
				draggable: true,
				modal: true,
				width: 700,
				open: function () {
					$('#error-filter-input').hide();
					$("#fav_filter_name").removeClass("error-filter-input");
					$(this).parent().find(".ui-dialog-titlebar-close").removeAttr("title");
					$(this).closest(".ui-dialog").attr("tabindex", -1).focus();
				},
				buttons: [
					{
						text: "Create",
						class: "ui-button ui-corner-all ui-widget ui-state-active",
						click: function () {
							if(!$("#fav_filter_name").val().length){
								$("#fav_filter_name").addClass("error-filter-input");
								$('#error-filter-input').show();
								return;
							}
							$('#filter-form').submit();
						}
					},
					{
						text: "Cancel",
						click: function () {
							filterDialog.dialog( "close" );
						}
					}
				]					 
			});	

			const getCurrentFilters = () => {
				const form_filters = {};
				const form_filter_values = {};

				// features
				form_filters.feature = $('#lic_features').val();
				form_filter_values["Features"] = $('#lic_features').val();

				// region
				form_filters.project = $('#lic_projects').val();
				form_filter_values["Projects"] = $('#lic_projects').val();

				//From
				form_filters.date1 = $('#date1').val();
				form_filter_values["From"] = $('#date1').val();

				//To
				form_filters.date2 = $('#date2').val();
				form_filter_values["To"] = $('#date2').val();

				return [form_filters, form_filter_values];
			}

			$("#save-filters").on( "click", function() {
				const [form_filters, form_filter_values] = getCurrentFilters();	
				const $table = $('#save-filter-table');
				$('#save-filter-table tr:not(:first)').remove();
				Object.entries(form_filter_values).forEach(([key, value], index) => {
					const rowClass = index % 2 === 0 ? 'even tableRow' : 'odd tableRow';
					// Format value
					let valueContent;
					if (Array.isArray(value)) {
						valueContent = value.map(item => `<span class="filter-array-item" title="${item}">${item}</span>`).join('');
					} else {
						valueContent = value;
					}

					const $row = $(`
						<tr class="${rowClass}">
							<td style="word-wrap: break-word;">${key}</td>
							<td style="word-wrap: break-word;">${valueContent}</td>
						</tr>
					`);

					$table.append($row);
			    });
				const post_value = Object.entries(form_filters).map(([k, v]) => `${k}=${v == null ? "" : Array.isArray(v) ? v.join(",") : v}`).join("|");
				$('#fav_filters').val(post_value);
			    filterDialog.dialog("open");
		    });

            const favFilterSaveStatus = "<?= $fav_filter_save_stat ?>";
		    if(favFilterSaveStatus === 'fail'){
				$("#overwrite_fav_filter_name").val("<?= isset($fav_filter_save_data) ? $fav_filter_save_data["fav_filter_name"] : "" ?>");
				$("#overwrite_fav_filters").val("<?= isset($fav_filter_save_data) ? $fav_filter_save_data["fav_filters"]: "" ?>");
				$("#overwrite_filters_dialog").dialog({
					modal: true,
					width: 600,
					open: function () {
						$(this).parent().find(".ui-dialog-titlebar-close").removeAttr("title");
						$(this).closest(".ui-dialog").attr("tabindex", -1).focus();
				    },
					buttons: [
					{
						text: "Save anyway",
						class: "ui-button ui-corner-all ui-widget ui-state-active",
						click: function () {
							$('#overwrite-filter-form').submit();
						}
					},
					{
						text: "Cancel",
						click: function () {
							$(this).dialog( "close" );
							$("#fav_filter_name").val("<?= isset($fav_filter_save_data) ? $fav_filter_save_data["fav_filter_name"] : "" ?>");
							$('#save-filters').click();
						}
					}]
				});
			}

			//  Show filters 
			$("#all_filters_dialog").dialog({
				autoOpen: false,
					modal: true,
					width: 700,
					maxHeight: 600,
					open: function () {
						$(this).parent().find(".ui-dialog-titlebar-close").removeAttr("title");
						$(this).closest(".ui-dialog").attr("tabindex", -1).focus();
						$(this).css({              
							'overflow-y': 'auto'
						});
				    },
					buttons: [
					{
						text: "Close",
						click: function () {
							$(this).dialog( "close" );
						}
					}]
			});

			$("#all_filters").on( "click", function() {
					$("#all_filters_dialog").dialog("open");
			})

			$( "#filter-accordion" ).accordion({
				heightStyle: "content",
				activate: function(event, ui) {
					if (ui.newHeader.length) {
					}  
					if (ui.oldHeader.length > 0) {
						$(`#delete-filter-dialog-${ui.oldHeader.text()}`).hide();
					}
				} 
			});
	   })
	</script>
<?php
	
}

function format_timing($time) {
	if ($time > 86400) {
		$days  = floor($time/86400);
		$time %= 86400;
	} else {
		$days  = 0;
	}

	if ($time > 3600) {
		$hours = floor($time/3600);
		$time  %= 3600;
	} else {
		$hours = 0;
	}

	$minutes = floor($time/60);

	return $days . "d " . $hours . "h " . $minutes . "m";
}

function get_daily_project_view_records($row_limit = 30, &$total_rows = array()) {
	$sql_where = "WHERE poll_time >= '" . get_request_var('date1') . " 00:00:00' AND poll_time <  DATE_ADD('" . get_request_var('date2') . " 00:00:00', INTERVAL 1 DAY)";
	$sql_params = array();

	if (get_request_var("feature")) {
		$features = preg_split('/\s+/', get_request_var("feature"), -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($features) ) {
			$features  = [$features];
		} 
		$placeholders = implode(',', array_fill(0, count($features), '?'));
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " ldps.feature_name IN ($placeholders)";
		$sql_params = array_merge($sql_params, $features);
	}

	if (get_request_var("project")) {
		$projects = preg_split('/\s+/', get_request_var("project"), -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($projects) ) {
			$projects  = [$projects];
		} 
		$placeholders = implode(',', array_fill(0, count($projects), '?'));
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " ldps.projectName IN ($placeholders)";
		$sql_params = array_merge($sql_params, $projects);
	}

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*) 
	FROM 
	(SELECT count(*) FROM lic_daily_project_stats ldps 
	LEFT JOIN lic_application_feature_map licafp ON ldps.feature_name = licafp.feature_name
	LEFT JOIN lic_application_accounting lica on lica.application = licafp.application
	$sql_where
	group by ldps.feature_name,projectName) as a;", $sql_params);

	$sort_order = '';

	if (isset_request_var('sort_column_array') && cacti_sizeof(get_request_var('sort_column_array')) > 0) {
		$sort_order .= ' ORDER BY ' . lic_build_order_string(get_request_var('sort_column_array'), get_request_var('sort_direction_array'));
	}

	$sort_order = get_order_string();

	$sql_query = "SELECT ldps.feature_name AS feature, 
	projectName AS project, 
	CASE 
	WHEN monthly_cost IS NULL then 'N/A' 
	ELSE CONCAT('$', ROUND((monthly_cost/30)*(SUM(token_minutes) / 1440), 2)) 
	END as cost,
	SUM(token_minutes) AS token_time, 
	CONCAT(ROUND(SUM(token_minutes) / (SUM(feature_max_licenses) * 1440), 2), '%') AS avg_utilization,
	MAX(token_minutes) AS peak_utilization 
	FROM 
	lic_daily_project_stats ldps 
	LEFT JOIN lic_application_feature_map licafp ON ldps.feature_name = licafp.feature_name
	LEFT JOIN lic_application_accounting lica on lica.application = licafp.application
	$sql_where
	group by ldps.feature_name,projectName $sort_order";

	$sql_query .= ' LIMIT ' . ($row_limit*(max(1, (int)get_request_var('page'))-1)) . ',' . $row_limit;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function lic_view_daily_proj_use() {

	global $title, $grid_search_types, $lic_rows_selector, $config, $title;
	global $lic_timespans, $lic_timeshifts, $lic_weekdays;

	$title = 'IBM Spectrum LSF RTM - Daily Project Use';
	general_header();
	dailyStatsFilter();
	?>

	<script>

	function applyFilterChangePDTS() {
		strURL  = 'lic_daily_project_use.php?header=false&predefined_timespan=' + $('#predefined_timespan').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function moveRight() {
		strURL  = 'lic_daily_project_use.php?header=false'
		strURL += '&move_right_x=1';
		strURL += '&date1=' + $('#date1').val();
		strURL += '&date2=' + $('#date2').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function moveLeft() {
		strURL = 'lic_daily_project_use.php?header=false';
		strURL += '&move_left_x=1';
		strURL += '&date1=' + $('#date1').val();
		strURL += '&date2=' + $('#date2').val();
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		loadPageNoHeader(strURL);
	}

	function applyFilter() {

		strURL = 'lic_daily_project_use.php?header=false';

		lic_features_values = $('#lic_features').val();
		if(lic_features_values.length){
			lic_features_values = lic_features_values.filter(value => value.length);
			strURL = strURL + '&feature=' + encodeURIComponent(lic_features_values.join(" "));
		} else {
			strURL += '&feature='
		}

		lic_projects_values = $('#lic_projects').val();
		if(lic_projects_values.length){
			lic_projects_values = lic_projects_values.filter(value => value.length);
			strURL = strURL + '&project=' + encodeURIComponent(lic_projects_values.join(" "));
		} else {
			strURL += '&project='
		}

		if ($('#date1').val() == date1 && $('#date2').val() == date2 && $('#predefined_timespan').val() != 0) {
			strURL = strURL + '&predefined_timespan=' + $('#predefined_timespan').val();
		} else {
			strURL = strURL + '&date1=' + $('#date1').val();
			strURL = strURL + '&date2=' + $('#date2').val();
		}
		strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'lic_daily_project_use.php?header=false&clear=true';
		loadPageNoHeader(strURL);
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
		} else if (dateString === "1month" || dateString === "1 Month") {
			timediff = 30;
		} else if (dateString === "2months" || dateString === "2 Months") {
			timediff = 60;
		} else if (dateString === "6months" || dateString === "6 Months") {
			timediff = 180;
		}
		return timediff;
	}

	$(function() {

		date1='<?php print rtm_safe_session('sess_dpu_current_date1');?>';
		date2='<?php print rtm_safe_session('sess_dpu_current_date2');?>';

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
        initSelect2Multi("lic_features",{url: "lic_daily_project_use.php?action=ajaxsearch&type=feature", preventOpenOnClear: true, triggerFormSubmit: applyFilter});
        initSelect2Multi("lic_projects",{url: "lic_daily_project_use.php?action=ajaxsearch&type=project", preventOpenOnClear: true, triggerFormSubmit: applyFilter});
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

	$daily_project_view_records = get_daily_project_view_records($row_limit, $total_rows);
	// Table logic
	$display_text = array();
	$display_text += array('feature'       => array('display' => __('LM Feature', 'license'), 'sort' => 'ASC'));
	$display_text += array('project'          => array('display' => __('Project', 'license')));
	$display_text += array('cost'          => array('display' => __('Cost', 'license')));
	$display_text += array('token_time'   => array('display' => __('Token time', 'license')));
	$display_text += array('avg_utilization'   => array('display' => __('Avg Utilization', 'license'), 'align' => 'right'));
	$display_text += array('peak_utilization'       => array('display' => __('Peak Utilization', 'license'), 'align' => 'right'));
	
	/* generate page list */
	$nav = html_nav_bar('lic_daily_project_use.php', MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('Daily Project Use Records'), 'page', 'main');
	//$nav = html_nav_bar('lic_daily_project_use.php, MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('Daily Stat'), 'page', 'main');
	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	
	
	if (cacti_sizeof($daily_project_view_records)) {
		foreach ($daily_project_view_records as $record) {
			form_alternate_row();
			?>
				<td style='white-space:nowrap;'><?php print $record['feature'];?></td>
				<td style='white-space:nowrap;'><?php print $record['project'];?></td>
				<td style='white-space:nowrap; text-align:right;'><?php print $record['cost'];?></td>
				<td style='white-space:nowrap; text-align:right;'><?php print $record['token_time'];?></td>
				<td style='white-space:nowrap; text-align:right;'><?php print $record['avg_utilization'];?></td>
				<td style='white-space:nowrap; text-align:right;'><?php print $record['peak_utilization'];?></td>
			<?php
		}
		/* put the nav bar on the bottom as well */
		html_end_box(true);
		print $nav;
	} else {
		html_end_box(true);
		print "<tr><td colspan='11'><em>No Daily Project Use Records Found</em></td></tr>";
	}
	
	api_plugin_hook('lic_page_bottom');
	bottom_footer();
}
?>