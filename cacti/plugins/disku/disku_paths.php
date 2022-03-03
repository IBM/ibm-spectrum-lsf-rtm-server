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

chdir('../../');
include('./include/auth.php');
include_once('./plugins/disku/include/disku_functions.php');

$path_server_actions = array(
	2 => __('Disable', 'disku'),
	3 => __('Enable', 'disku'),
	1 => __('Delete', 'disku'),
	4 => __('Clear Stats', 'disku')
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

		path_edit();
		bottom_footer();

		break;
	default:
		top_header();
		disku_paths();
		bottom_footer();

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if ((isset_request_var('save_component')) && (isempty_request_var('add_dq_y'))) {
		input_validate_input_number(get_request_var('id'));

		$save['name']        = get_request_var('name');
		$save['description'] = get_request_var('description');
		$save['tagname']     = get_request_var('tagname');
		$save['path']        = form_input_validate(get_request_var('path'), 'path', '^\/', false, 304);
		$save['threads']     = get_request_var('threads');
		$save['depth']       = get_request_var('depth');
		$save['poller_id']   = form_input_validate((isset_request_var('poller_id') ? get_request_var('poller_id'): ''), 'poller_id', '^[0-9]+$', false, 3);
		$save['disabled']    = (isset_request_var('disabled') ? 'on':'');

		if (!isempty_request_var('id')) {
			$save['id'] = get_request_var('id');
		} else {
			$save['id'] = 0;

			$path_exist = db_fetch_cell_prepared('SELECT name
				FROM disku_pollers_paths
				WHERE poller_id = ?
				AND path = ?',
				array($save['poller_id'], $save['path']));

			if (!empty($path_exist)) {
				raise_message(305);
			}
		}

		if (!is_error_message()) {
			$id = sql_save($save, 'disku_pollers_paths');
			raise_message(1);
		}

		if (is_error_message()) {
			header('Location: disku_paths.php?action=edit&id=' . (!isempty_request_var('id') ? get_request_var('id') : ''));
		} else {
			header('Location: disku_paths.php');
		}
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $path_server_actions, $fields_path_edit;

	input_validate_input_regex(get_request_var('drp_action'), '^([a-zA-Z0-9_]+)$');

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_path_remove($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '2') { /* disable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_path_disable($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '3') { /* enable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_path_enable($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '4') { /* clear stats */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_disku_path_clear_stats($selected_items[$i]);
				}
			}
		}

		header('Location: disku_paths.php');
		exit;
	}

	/* setup some variables */
	$path_list = ''; $path_array = array();

	/* loop through each of the Scanner Path(s) selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$path_info = db_fetch_cell_prepared('SELECT name
				FROM disku_pollers_paths
				WHERE id = ?',
				array($matches[1]));

			$path_list .= '<li>' . html_escape($path_info) . '</li>';
			$path_array[] = $matches[1];
		}
	}

	top_header();

	form_start('disku_paths.php');

	html_start_box($path_server_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($path_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following Scanner Path(s).', 'disku') . "</p>
					<ul>$path_list</ul>
				</td>
			</tr>";

			$title = __('Delete Disk Monitor Scan Path(s)', 'disku');
		} else if (get_request_var('drp_action') == '2') { /* disable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Disable Poller of the following Scanner Path(s).', 'disku') . "</p>
					<ul>$path_list</ul>
				</td>
			</tr>";

			$title = __('Disable Disk Monitor Scan Path(s)', 'disku');
		} else if (get_request_var('drp_action') == '3') { /* enable */
			print "<tr>
				<td class='textArea'
					<p>" . __('Click \'Continue\' to Enable the following Scanner Path(s).', 'disku') . "</p>
					<ul>$path_list</ul>
				</td>
			</tr>";

			$title = __('Enable Disk Monitor Scan Path(s)', 'disku');
		} else if (get_request_var('drp_action') == '4') { /* clear stats */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Clear Statistics on the following Scanner Path(s).', 'disku') . "</p>
					<ul>$path_list</ul>
				</td>
			</tr>";

			$title = __('Clear Disk Monitor Scan Path Stats', 'disku');
		}

		$save_html = "<input type='button' value='" . __('Cancel', 'disku') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue', 'disku') . "' title='$title'";
	} else {
		raise_message(40, __('You must select at least one Disk Monitor Scan Path.', 'disku'), MESSAGE_LEVEL_ERROR);
		header('Location: disku_paths.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($path_array) ? serialize($path_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

/* ---------------------
    Local Functions
   --------------------- */

function path_edit() {
	global $fields_path_edit;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$path = db_fetch_row_prepared('SELECT *
			FROM disku_pollers_paths
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __esc('Disk Monitoring Path [edit: %s]', ($path['name'] == '' ? __('Undefined'):$path['name']), 'disku');
	} else {
		$header_label = __('Disk Monitoring Path [new]', 'disku');
		$path = array();
	}

	$fields_path_edit = array(
		'path' => array(
			'method' => 'textbox',
			'friendly_name' => __('Path to Monitor', 'disku'),
			'description' => __('Enter the path that must be scanned.', 'disku'),
			'value' => '|arg1:path|',
			'default' => '',
			'size' => '40',
			'max_length' => '128'
		),
		'name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Path Name', 'disku'),
			'description' => __('Give the path a common name for others.', 'disku'),
			'value' => '|arg1:name|',
			'default' => '',
			'size' => '40',
			'max_length' => '40'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => __('Path Description', 'disku'),
			'description' => __('Details about this path for support purposes.', 'disku'),
			'value' => '|arg1:description|',
			'default' => '',
			'size' => '90',
			'max_length' => '128'
		),
		'tagname' => array(
			'method' => 'textbox',
			'friendly_name' => __('Tag Name for Accounting', 'disku'),
			'description' => __('Aggregates file statistical information under this name.', 'disku'),
			'value' => '|arg1:tagname|',
			'default' => '',
			'size' => '20',
			'max_length' => '20'
		),
		'poller_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => __('Poller Name', 'disku'),
			'description' => __('Select the Poller that collects data for the configured path.', 'disku'),
			'value' => '|arg1:poller_id|',
			'default' => '',
			'sql' => "SELECT id, CONCAT_WS('',location,' (',hostname,')') AS name FROM disku_pollers ORDER BY location"
		),
		'depth' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Directory Depth', 'disku'),
			'description' => __('For parallelization support, what depth to you want to traverse the file system to determine the number of threads.', 'disku'),
			'value' => '|arg1:depth|',
			'default' => '2',
			'array' => array(
				1 => __('%d Directory Deep', 1, 'disku'),
				2 => __('%d Directories Deep', 2, 'disku'),
				3 => __('%d Directories Deep', 3, 'disku'),
				4 => __('%d Directories Deep', 4, 'disku')
			)
		),
		'threads' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Max Concurrent Threads', 'disku'),
			'description' => __('Select the default maximum concurrent threads that you want to use in parallel on the configured path for that host. For example, if you set the path as <span class="filepath">/tmp</span> with a Depth 2 and Thread 5, then<span class="filepath">/tmp</span> is scanned until two directories depth by initiating 5 concurrent processes. Default is 5 Threads per path.', 'disku'),
			'value' => '|arg1:threads|',
			'default' => '5',
			'array' => array(
				1  => __('%d Thread', 1, 'disku'),
				2  => __('%d Threads', 2, 'disku'),
				3  => __('%d Threads', 3, 'disku'),
				4  => __('%d Threads', 4, 'disku'),
				5  => __('%d Threads', 5, 'disku'),
				6  => __('%d Threads', 6, 'disku'),
				7  => __('%d Threads', 7, 'disku'),
				8  => __('%d Threads', 8, 'disku'),
				9  => __('%d Threads', 9, 'disku'),
				10 => __('%d Threads', 10, 'disku')
			)
		),
		'disabled' => array(
			'method' => 'checkbox',
			'friendly_name' => __('Do Not Monitor this Path', 'disku'),
			'description' => __('Check this box if you want to disable scanning for this path.', 'disku'),
			'value' => '|arg1:disabled|',
			'default' => '',
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

	form_start('disku_paths.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_name' => true),
			'fields' => inject_form_variables($fields_path_edit, (isset($path) ? $path : array()))
		)
	);

	html_end_box();

	form_save_button('disku_paths.php', '', 'id');
}

function format_seconds($time) {
	if ($time >= 60) {
		$time = $time / 60;
		if ($time >= 60) {
			$time = $time / 60;
			return __('%d Hrs', round($time, 1), 'disku');
		} else {
			return __('%d Mins', round($time, 1), 'disku');
		}
	} else {
		return __('%d Secs', round($time, 1), 'disku');
	}
}

function disku_filter() {
	global $disku_rows_selector, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_disku' action='disku_paths.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Location', 'disku');?>
					</td>
					<td>
						<select id='location'>
							<option value='-1'<?php if (get_request_var('location') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<?php
							$locations = db_fetch_assoc("SELECT DISTINCT id,
								CONCAT_WS('',location,' (',hostname,')') AS location
								FROM disku_pollers
								ORDER BY location");

							if (cacti_sizeof($locations)) {
								foreach ($locations as $l) {
									print '<option value="' . $l['id'] .'"'; if (get_request_var('location') == $l['id']) { print ' selected'; } print '>' . html_escape($l['location']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'disku');?>
					</td>
					<td>
						<select id='status'>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>><?php print __('Any', 'disku');?></option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>><?php print __('Enabled', 'disku');?></option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>><?php print __('Disabled', 'disku');?></option>
						</select>
					</td>
					<td>
						<?php print __('Paths', 'disku');?>
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
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Search', 'disku');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'disku');?>' title='<?php print __('Clear Filters', 'disku');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'disku');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function disku_paths() {
	global $title, $report, $disku_rows_selector, $config;
	global $path_server_actions, $config;
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
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-3'
			),
		'location' => array(
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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_diskupa');
	/* ================= input validation ================= */

	html_start_box(__('Disk Monitoring Paths'), '100%', '', '3', 'center', 'disku_paths.php?action=edit');
	disku_filter();
	html_end_box();

	$sql_where  = '';

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$paths = disku_get_path_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'disku_paths.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&location=' + $('#location').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'disku_paths.php?header=false&action=view&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_disku').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#location, #status, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM (
			SELECT p.id
			FROM disku_pollers_paths AS p
			INNER JOIN disku_pollers AS dp
			ON p.poller_id=dp.id
			LEFT JOIN disku_pollers_threads AS dpt
			ON p.id=dpt.path_id AND p.poller_id=dpt.poller_id
			$sql_where
			GROUP BY p.id
		) as tmp", $sql_params);

	$display_text = array(
		'name'         => array('display' => __('Path Name'),     'align' => 'left',  'sort' => 'ASC'),
		'path'         => array('display' => __('Path'),          'align' => 'left',  'sort' => 'DESC'),
		'tagname'      => array('display' => __('Tag Name'),      'align' => 'left',  'sort' => 'DESC'),
		'location'     => array('display' => __('Location'),      'align' => 'left',  'sort' => 'ASC'),
		'disabled'     => array('display' => __('Enabled'),       'align' => 'left',  'sort' => 'DESC'),
		'nosort'       => array('display' => __('Status'),        'align' => 'left',  'sort' => 'DESC'),
		'pending'      => array('display' => __('Pending'),       'align' => 'right', 'sort' => 'DESC'),
		'running'      => array('display' => __('Running'),       'align' => 'right', 'sort' => 'DESC'),
		'nosort1'      => array('display' => __('Depth/Threads'), 'align' => 'right', 'sort' => 'DESC'),
		'cur_time'     => array('display' => __('Cur Time'),      'align' => 'right', 'sort' => 'DESC'),
		'avg_time'     => array('display' => __('Avg Time'),      'align' => 'right', 'sort' => 'DESC'),
		'max_time'     => array('display' => __('Max Time'),      'align' => 'right', 'sort' => 'DESC'),
		'last_started' => array('display' => __('Last Started'),  'align' => 'right', 'sort' => 'DESC'),
		'last_ended'   => array('display' => __('Last Ended'),    'align' => 'right', 'sort' => 'DESC'));

	$nav = html_nav_bar('disku_paths.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Paths'), 'page', 'main');

	form_start('disku_paths.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($paths)) {
		foreach ($paths as $p) {
			form_alternate_row('line' . $p['id']);
			form_selectable_cell("<a class='linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/disku/disku_paths.php?action=edit&id=' . $p['id']) . "'>" . ($p['name'] == '' ? __('Undefined'): html_escape($p['name'])) . '</a>', $p['id']);
			form_selectable_cell(html_escape($p['path']), $p['id']);
			form_selectable_cell(html_escape($p['tagname']), $p['id']);
			form_selectable_cell(html_escape($p['location']), $p['id']);
			form_selectable_cell(($p['disabled'] == 'on' ? __('No'):__('Yes')), $p['id']);
			form_selectable_cell(($p['disabled'] == 'on' ? "<span class='deviceDisabled'>" . __('Disabled') . "</span>":($p['pending'] > 0 || $p['running'] > 0 ? "<span class='deviceUp'>" . __('Running') . "</span>":"<span style='deviceRecovering'>" . __('Idle') . '</span>')), $p['id']);
			form_selectable_cell(number_format($p['pending']), $p['id'], '', 'right');
			form_selectable_cell(number_format($p['running']), $p['id'], '', 'right');
			form_selectable_cell($p['depth'] . '/' . $p['threads'], $p['id'], '', 'right');
			form_selectable_cell(format_seconds($p['cur_time'],1), $p['id'], '', 'right');
			form_selectable_cell(format_seconds($p['avg_time'],1), $p['id'], '', 'right');
			form_selectable_cell(format_seconds($p['max_time'],1), $p['id'], '', 'right');
			form_selectable_cell(substr($p['last_started'],0,-3), $p['id'], '', 'right');
			form_selectable_cell(substr($p['last_ended'],0,-3), $p['id'], '', 'right');
			form_checkbox_cell(html_escape($p['name']), $p['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Disk Data Collector Paths Defined', 'disku') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($paths)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($path_server_actions);

	form_end();
}

function disku_get_path_records(&$sql_where, $apply_limits = true, $rows = 20, &$sql_params) {
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(path LIKE ? OR name LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('location') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'p.poller_id=?';
		$sql_params[] = get_request_var('location');
	}

	if (get_request_var('status') == 2) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'p.disabled=""';
	} elseif (get_request_var('status') == 3) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'p.disabled="on"';
	}

	$sql_order = get_order_string();

	$path_sql = "SELECT p.*, CONCAT_WS('',dp.location,' (',dp.hostname,')') AS location,
		SUM(CASE WHEN dpt.status='pending' THEN 1 ELSE 0 END) AS pending,
		SUM(CASE WHEN dpt.status='running' THEN 1 ELSE 0 END) AS running
		FROM disku_pollers_paths AS p
		INNER JOIN disku_pollers AS dp
		ON p.poller_id=dp.id
		LEFT JOIN disku_pollers_threads AS dpt
		ON p.id=dpt.path_id AND p.poller_id=dpt.poller_id
		$sql_where
		GROUP BY p.id
		$sql_order";

	if ($apply_limits) {
		$path_sql .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($path_sql, $sql_params);
}
