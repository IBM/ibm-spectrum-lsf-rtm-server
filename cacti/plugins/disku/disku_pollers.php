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

chdir('../../');
include('./include/auth.php');
include('./plugins/disku/include/disku_functions.php');

$disku_poller_actions = array(
//	2 => "Disable",
//	3 => "Enable",
	1 => __('Delete', 'disku'),
	4 => __('Reset Counters', 'disku'),
);

/* set default action */
set_default_action();

switch (get_request_var('action')) {
	case 'save':
		form_save();
		break;
	case 'actions':
		form_actions();
		break;
	case 'edit':
		top_header();
		disku_poller_edit();
		bottom_footer();
		break;
	default:
		top_header();
		disku_pollers();
		bottom_footer();
		break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save() {
	if ((isset_request_var('save_component')) && (isempty_request_var('add_dq_y'))) {
		input_validate_input_number(get_request_var('id'));

		$save['location']  = form_input_validate(get_request_var('location'), 'location', '', true, 3);
		$save['frequency'] = get_request_var('frequency');
		$save['dayOfWeek'] = get_request_var('dayOfWeek');
		$save['timeOfDay'] = get_request_var('timeOfDay');
		$save['df_collect_flag'] = get_request_var('df_collect_flag');

		if (!isempty_request_var('id')) {
			$save['id'] = get_request_var('id');
		} else {
			$save['id'] = 0;
		}

		if (!is_error_message()) {
			$id = sql_save($save, 'disku_pollers');
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	if (is_error_message()) {
		header('Location: disku_pollers.php?action=edit&id=' . (empty($id) ? get_request_var('id') : $id));
	} else {
		header('Location: disku_pollers.php');
	}
}

/* ------------------------
	The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $disku_poller_actions, $fields_disku_poller_edit;

	input_validate_input_regex(get_request_var('drp_action'), '^([a-zA-Z0-9_]+)$');

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$errors = array();

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					$return = api_disku_poller_remove($selected_items[$i]);
					if ($return == false) {
						$errors[] = $selected_items[$i];
					}
				}
			} elseif (get_request_var('drp_action') == '2') { /* disable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_poller_disable($selected_items[$i]);
				}
			} elseif (get_request_var('drp_action') == '3') { /* enable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_poller_enable($selected_items[$i]);
				}
			} elseif (get_request_var('drp_action') == '4') { /* reset */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_poller_reset_counters($selected_items[$i]);
				}
			}
		}

		if (cacti_sizeof($errors)) {
			raise_message('disku_delete', __('Unable to delete Pollers that have services running.  Shutdown diskud on the host prior to removing it.', 'disku'), MESSAGE_LEVEL_ERROR);
		}

		header('Location: disku_pollers.php');
		exit;
	}

	/* setup some variables */
	$disku_poller_list = ''; $disku_poller_array = array();

	/* loop through each of the pollers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$disku_poller_info = db_fetch_cell_prepared("SELECT CONCAT_WS('', location, ' (', hostname, ')' )
				FROM disku_pollers
				WHERE id = ?",
				array($matches[1]));

			$disku_poller_list .= '<li>' . html_escape($disku_poller_info) . '</li>';
			$disku_poller_array[] = $matches[1];
		}
	}

	general_header();

	form_start('disku_pollers.php');

	html_start_box($disku_poller_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($disku_poller_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following Disk Monitoring Poller(s).', 'disku') . "</p>
					<ul>$disku_poller_list</ul>
				</td>
			</tr>";

			$title = __('Delete Disk Monitoring Poller(s)', 'disku');
		} elseif (get_request_var('drp_action') == '2') { /* disable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Disable the following Disk Monitoring Poller(s).', 'disku') . "</p>
					<ul>$disku_poller_list</ul>
				</td>
			</tr>";

			$title = __('Disable Disk Monitoring Poller(s)', 'disku');
		} elseif (get_request_var('drp_action') == '3') { /* enable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Enable the following Disk Monitoring Poller(s).', 'disku') . "</p>
					<ul>$disku_poller_list</ul>
				</td>
			</tr>";

			$title = __('Enable Disk Monitoring Poller(s)', 'disku');
		} elseif (get_request_var('drp_action') == '4') { /* enable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Reset Counters on the following Disk Monitoring Poller(s).', 'disku') . "</p>
					<ul>$disku_poller_list</ul>
				</td>
			</tr>";

			$title = __('Reset Counters for Disk Monitoring Poller(s)', 'disku');
		}

		$save_html = "<input type='button' value='" . __('Cancel', 'disku') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue', 'disku') . "' title='" . html_escape($title) . "'";
	} else {
		raise_message(40, __('You must select at least one Disk Monitoring Poller.', 'disku'), MESSAGE_LEVEL_ERROR);
		header('Location: disku_pollers.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($disku_poller_array) ? serialize($disku_poller_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

/* ---------------------
	Poller Functions
   --------------------- */

function get_disku_poller_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	$sql_where = " WHERE (disku_processes.taskname='RTMCLIENTD' OR disku_processes.taskname IS NULL)";
	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(hostname LIKE ? OR location LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$query_string = "SELECT dp.*,
		disku_processes.heartbeat,
		dpp.paths,
		SUM(CASE WHEN dt.status='pending' THEN 1 ELSE 0 END) AS pending,
		SUM(CASE WHEN dt.status='running' THEN 1 ELSE 0 END) AS running
		FROM disku_pollers AS dp
		LEFT JOIN disku_processes
		ON dp.id = disku_processes.taskid
		LEFT JOIN disku_pollers_threads AS dt
		ON dp.id=dt.poller_id
		LEFT JOIN (SELECT poller_id, COUNT(*) AS paths FROM disku_pollers_paths GROUP BY poller_id) AS dpp
		ON dp.id=dpp.poller_id
		$sql_where
		GROUP BY dp.id
		$sql_order";

	if ($apply_limits) {
		$query_string .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($query_string, $sql_params);
}

function disku_poller_edit() {
	global $disku_frequencies, $disku_weekdays, $disku_timesofday;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$disku_poller = db_fetch_row_prepared('SELECT *
			FROM disku_pollers
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __esc('Disk Monitoring Poller [edit: %s (%s) ]', $disku_poller['location'], $disku_poller['hostname'], 'disku');
	} else {
		$header_label = __('Disk Monitoring Poller [new]', 'disku');
	}

	$fields_disku_poller_edit = array(
		'location' => array(
			'method' => 'textbox',
			'friendly_name' => __('Poller Location', 'disku'),
			'description' => __('The location where the Poller is located', 'disku'),
			'value' => '|arg1:location|',
			'default' => '',
			'size' => '40',
			'max_length' => '40'
		),
		'frequency' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Collection Timing', 'disku'),
			'description' => __('How often will the collectors be launched for this Poller?', 'disku'),
			'value' => '|arg1:frequency|',
			'default' => '86400',
			'array' => $disku_frequencies
		),
		'dayOfWeek' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Day of Week', 'disku'),
			'description' => __('When scanning weekly, what day of week should the scan be run?', 'disku'),
			'value' => '|arg1:dayOfWeek|',
			'default' => '6',
			'array' => $disku_weekdays
		),
		'timeOfDay' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Time of Day', 'disku'),
			'description' => __('What time of the day should scanning start?', 'disku'),
			'value' => '|arg1:timeOfDay|',
			'default' => '0',
			'array' => $disku_timesofday
		),
		'df_collect_flag' => array(
			'method' => 'checkbox',
			'friendly_name' => __('Collect all file system disk space usage', 'disku'),
			'description' => __('Collect disk space usage, not only the usage of local file systems, but also NFS file systems. If checked on more than one Poller, and Pollers in the same NFS, may cause duplicate records in Disk Monitoring Dashboard (Grid > Disk Utilization > By Volume)', 'disku'),
			'default' => '',
			'value' => isset($disku_poller['df_collect_flag']) ? $disku_poller['df_collect_flag']:''
		),
		'id' => array(
			'method' => 'hidden_zero',
			'value' => isset_request_var('id') ? get_request_var('id'):''
		),
		'save_component' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	form_start('disku_pollers.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(array(
		'config' => array('form_name' => 'chk'),
		'fields' => inject_form_variables($fields_disku_poller_edit, (isset($disku_poller) ? $disku_poller : array()))
	));

	html_end_box();

	form_save_button('disku_pollers.php', '', 'id');
}

function disku_pollers() {
	global $disku_poller_actions, $config, $disku_frequencies, $disku_weekdays, $disku_timesofday;
	$sql_params = array();

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
			'default' => 'hostname',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_dup_');
	/* ================= input validation ================= */

	$debug_log = debug_log_return('disku_pollers');

	if (!empty($debug_log)) {
		debug_log_clear('disku_pollers');
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

	html_start_box(__('Disk Monitoring Pollers', 'disku'), '100%', '', '3', 'center', '');
	disku_filter();
	html_end_box();

	form_start('disku_pollers.php', 'chk');

	$sql_where  = '';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$disku_pollers = get_disku_poller_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'disku_pollers.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'disku_pollers.php?header=false&action=view&clear=true'
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#formname').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#filter, #rows').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		})
	});

	</script>
	<?php

	$rows_query_string = "SELECT COUNT(*) FROM (
		SELECT dp.id
		FROM disku_pollers AS dp
		LEFT JOIN disku_processes
		ON dp.id = disku_processes.taskid
		LEFT JOIN disku_pollers_threads AS dt
		ON dp.id=dt.poller_id
		LEFT JOIN (SELECT poller_id FROM disku_pollers_paths GROUP BY poller_id) AS dpp
		ON dp.id=dpp.poller_id
		$sql_where
		GROUP BY dp.id
		) as tmp";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'hostname' => array(
			'display' => __('Hostname', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'nosort0' => array(
			'display' => __('Status', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'location' => array(
			'display' => __('Location', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'frequency' => array(
			'display' => __('Launch Frequency', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'timeOfDay' => array(
			'display' => __('Launch Time', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'dayOfWeek' => array(
			'display' => __('Launch Day', 'disku'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'paths' => array(
			'display' => __('Paths', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'pending' => array(
			'display' => __('Pending', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'running' => array(
			'display' => __('Running', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'cur_time' => array(
			'display' => __('Last Time', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'avg_time' => array(
			'display' => __('Avg Time', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'max_time' => array(
			'display' => __('Max Time', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'last_started' => array(
			'display' => __('Last Started', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'last_ended' => array(
			'display' => __('Last Ended', 'disku'),
			'align' => 'right',
			'sort' => 'ASC'
		)
	);

	$nav = html_nav_bar('disku_pollers.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Pollers', 'disku'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;
	if (cacti_sizeof($disku_pollers)) {
		foreach ($disku_pollers as $disku_poller) {
			$procs = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM disku_pollers_threads
				WHERE poller_id = ?',
				array($disku_poller['id']));

			if (isset($disku_poller['heartbeat']) && (time() - strtotime($disku_poller['heartbeat']) < 300)) {
				$status = "<span class='deviceUp'>"   . ($procs > 0 ? __('Running', 'disku'):__('Up', 'disku')) . '</span>';
			} else {
				$status = "<span class='deviceDown'>" . ($procs > 0 ? __('Orphaned', 'disku'):__('Down', 'disku')) . '</span>';
			}

			form_alternate_row('line' . $disku_poller['id']);
			form_selectable_cell("<a class='linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/disku/disku_pollers.php?action=edit&id=' . $disku_poller['id']) . "'>" . html_escape($disku_poller['hostname']) . '</a>', $disku_poller['id']);
			form_selectable_cell($status, $disku_poller['id']);
			form_selectable_cell(html_escape($disku_poller['location']), $disku_poller['id']);
			form_selectable_cell($disku_frequencies[$disku_poller['frequency']], $disku_poller['id']);
			form_selectable_cell($disku_timesofday[$disku_poller['timeOfDay']], $disku_poller['id']);
			form_selectable_cell(($disku_poller['frequency'] > 0 ? 'Daily':$disku_weekdays[$disku_poller['dayOfWeek']]), $disku_poller['id']);
			form_selectable_cell("<a href='" . html_escape('disku_paths.php?query=1&location=' . $disku_poller['id']) . "' title='" . __esc('View Paths') . "'><b><i>" . html_escape($disku_poller['paths']) . '</i></b></a>', $disku_poller['id'], '', 'right');
			form_selectable_cell(number_format_i18n($disku_poller['pending']), $disku_poller['id'], '', 'right');
			form_selectable_cell(number_format_i18n($disku_poller['running']), $disku_poller['id'], '', 'right');
			form_selectable_cell(format_seconds($disku_poller['cur_time']), $disku_poller['id'], '', 'right');
			form_selectable_cell(format_seconds($disku_poller['avg_time']), $disku_poller['id'], '', 'right');
			form_selectable_cell(format_seconds($disku_poller['max_time']), $disku_poller['id'], '', 'right');
			form_selectable_cell(substr($disku_poller['last_started'],0,-3), $disku_poller['id'], '', 'right');
			form_selectable_cell(substr($disku_poller['last_ended'],0,-3), $disku_poller['id'], '', 'right');
			form_checkbox_cell($disku_poller['hostname'], $disku_poller['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Pollers Defined', 'disku') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($disku_pollers)) {
		print $nav;
	}

	draw_actions_dropdown($disku_poller_actions);

	form_end();
}

function format_seconds($time) {
	if ($time >= 60) {
		$time = $time / 60;
		if ($time >= 60) {
			$time = $time / 60;
			return __('%s Hrs', round($time, 1), 'disku');
		} else {
			return __('%s Mins', round($time, 1), 'disku');
		}
	} else {
		return __('%s Secs', round($time, 1), 'disku');
	}
}

function disku_filter() {
	global $disku_rows_selector, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='formname' action='disku_pollers.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'disku');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Pollers', 'disku');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($disku_rows_selector)) {
								foreach ($disku_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Search', 'disku');?>'>
						<input type='button' id='clear' value='<?php print __('Clear', 'disku');?>' title='<?php print __('Clear Filters', 'disku');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}
