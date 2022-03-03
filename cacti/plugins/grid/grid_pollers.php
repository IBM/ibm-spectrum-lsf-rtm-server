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

$grid_poller_actions = array(
	1 => __('Delete', 'grid')
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
		grid_poller_edit();
		bottom_footer();

		break;
	default:
		top_header();
		grid_pollers();
		bottom_footer();

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset_request_var('save_component_poller') && isempty_request_var('add_dq_y')) {
		get_filter_request_var('poller_id');

		$poller_id = api_grid_poller_save(get_request_var('poller_id'),
			get_nfilter_request_var('poller_name'), get_nfilter_request_var('lsf_version'),
			get_nfilter_request_var('poller_lbindir'), get_nfilter_request_var('poller_location'),
			get_nfilter_request_var('poller_support_info'), get_nfilter_request_var('remote'));

		if (is_error_message()) {
			header('Location: grid_pollers.php?header=false&action=edit&poller_id=' . (empty($poller_id) ? get_request_var('poller_id') : $poller_id));
		} else {
			header('Location: grid_pollers.php?header=false');
		}
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $grid_poller_actions, $fields_grid_poller_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_nfilter_request_var('drp_action') === '1') { /* delete */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					api_grid_poller_remove($selected_items[$i]);
				}
			}
		}

		header('Location: grid_pollers.php');

		exit;
	}

	/* setup some variables */
	$poller_list = ''; $poller_array = array();

	/* loop through each of the pollers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$poller_info = db_fetch_cell_prepared('SELECT poller_name FROM grid_pollers WHERE poller_id = ?', array($matches[1]));
			$poller_list .= '<li>' . html_escape($poller_info) . '</li>';
			$poller_array[] = $matches[1];
		}
	}

	top_header();

	form_start('grid_pollers.php');

	html_start_box($grid_poller_actions[get_filter_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($poller_array)) {
		if (get_filter_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('If you click \'Continue\', the following Grid Poller(s) will be deleted', 'grid'). "</p>
					<ul>$poller_list</ul>
				</td>
			</tr>";

			$title = __('Delete Poller(s)', 'grid');
		}

		$save_html = "<input type='button' value='" . __('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue', 'grid') . "' title='" . html_escape($title) . "'";
   	} else {
		raise_message(40);
		header('Location: grid_pollers.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($poller_array) ? serialize($poller_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_nfilter_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function api_grid_poller_save($poller_id, $poller_name, $lsf_version, $poller_lbindir, $poller_location, $poller_support_info, $remote) {
	if ($poller_id) {
		$save['poller_id'] = $poller_id;
	} else {
		$save['poller_id'] = '';
	}

	$save['poller_name']         = form_input_validate($poller_name,         'poller_name', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
	$save['lsf_version']         = form_input_validate($lsf_version,         get_nfilter_request_var('lsf_version'),         '', false, 3);
	$save['poller_lbindir']      = form_input_validate($poller_lbindir,      get_nfilter_request_var('poller_lbindir'),      '', false, 3);
	$save['poller_location']     = form_input_validate($poller_location,     get_nfilter_request_var('poller_location'),     '', true,  3);
	$save['poller_support_info'] = form_input_validate($poller_support_info, get_nfilter_request_var('poller_support_info'), '', true,  3);
	$save['remote']              = $remote;

	if ($save['poller_name'] == '') {
		$_SESSION['sess_error_fields']['poller_name'] = 'poller_name';
	}

	if ($remote != 'on') {
		if (!is_dir($save['poller_lbindir'])) {
			raise_message(144);
			$_SESSION['sess_error_fields']['poller_lbindir'] = 'poller_lbindir';
			$_SESSION['sess_field_values']['poller_lbindir'] = $poller_lbindir;
		}
	}

	$poller_id = 0;
	if (!is_error_message()) {
		$poller_id = sql_save($save, 'grid_pollers', 'poller_id');

		if ($poller_id) {
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	return $poller_id;
}

function api_grid_poller_remove($poller_id) {
	$grids = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM grid_clusters
		WHERE poller_id = ?',
		array($poller_id));

	if ($grids > 0) {
		raise_message(143);
	} else {
		db_execute_prepared('DELETE FROM grid_pollers
			WHERE poller_id = ?',
			array($poller_id));
	}
}

/* ---------------------
    Poller Functions
   --------------------- */

function grid_get_poller_records() {
	$sql_order = get_order_string();

	return db_fetch_assoc("SELECT *
		FROM grid_pollers
		$sql_order");
}

function grid_poller_edit() {
	global $fields_grid_poller_edit;

	/* ================= input validation ================= */
	get_filter_request_var('poller_id');
	/* ==================================================== */

	if (!isempty_request_var('poller_id')) {
		$poller = db_fetch_row_prepared('SELECT *
			FROM grid_pollers
			WHERE poller_id = ?',
			array(get_request_var('poller_id')));

		$header_label = __esc('RTM Poller [edit: %s]', $poller['poller_name'], 'grid');

		//Edit a existing poller, for fresh installed RTM, just allow to show LSF 8 or above pollers; for upgraded RTM, show all pollers
		$fields_grid_poller_edit1 = $fields_grid_poller_edit;

		$lsf7_count = db_fetch_cell('SELECT count(*) FROM grid_pollers WHERE lsf_version like "70%"');

		if ($lsf7_count == 0) {  //No LSF 7.x pollers - fresh installed RTM 10.1+
			unset($fields_grid_poller_edit1['lsf_version']['array']['701']);
			unset($fields_grid_poller_edit1['lsf_version']['array']['702']);
			unset($fields_grid_poller_edit1['lsf_version']['array']['703']);
			unset($fields_grid_poller_edit1['lsf_version']['array']['704']);
			unset($fields_grid_poller_edit1['lsf_version']['array']['705']);
			unset($fields_grid_poller_edit1['lsf_version']['array']['706']);
		} else {  //having LSF 7.x pollers - upraded RTM 10.1+,
			$fields_grid_poller_edit1 = $fields_grid_poller_edit;
		}
	} else {
		$header_label = __('RTM Poller [new]', 'grid');

		//Add a new poller, For new freshed RTM and upgraded RTM, just allow to show LSF 8 or above pollers
		$fields_grid_poller_edit1 = $fields_grid_poller_edit;

		unset($fields_grid_poller_edit1['lsf_version']['array']['701']);
		unset($fields_grid_poller_edit1['lsf_version']['array']['702']);
		unset($fields_grid_poller_edit1['lsf_version']['array']['703']);
		unset($fields_grid_poller_edit1['lsf_version']['array']['704']);
		unset($fields_grid_poller_edit1['lsf_version']['array']['705']);
		unset($fields_grid_poller_edit1['lsf_version']['array']['706']);
	}

	form_start('grid_pollers.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	inject_form_variables($fields_grid_poller_edit1, (isset($poller) ? $poller : array()));
	if ($fields_grid_poller_edit1['remote'] && $fields_grid_poller_edit1['remote']['value'] == 'on') {
		$fields_grid_poller_edit1['poller_lbindir']['method'] = 'textbox';
	} else {
		$fields_grid_poller_edit1['poller_lbindir']['method'] = 'dirpath';
	}

	draw_edit_form(
		array(
			'config' => array('form_name' => 'chk'),
			'fields' => $fields_grid_poller_edit1
		)
	);

	html_end_box();

	form_save_button('grid_pollers.php', '', 'poller_id');
}

function grid_pollers() {
	global $grid_poller_actions, $config;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'lsf_version',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_lsfp');
	/* ================= input validation ================= */

	form_start('grid_pollers.php', 'chk');

	html_start_box(__('RTM Pollers', 'grid'), '100%', '', '3', 'center', 'grid_pollers.php?action=edit&poller_id=' . get_request_var('poller_id'));

	$pollers = grid_get_poller_records();

	$display_text = array(
		'poller_name'         => array(__('Name', 'grid'), 'ASC'),
		'poller_id'           => array(__('ID', 'grid'), 'ASC'),
		'nosort0'             => array(__('Status', 'grid'), 'ASC'),
		'lsf_version'         => array(__('LSF Version', 'grid'), 'ASC'),
		'poller_location'     => array(__('Physical Location', 'grid'), 'ASC'),
		'nosort1'             => array(__('Last Finish', 'grid'), 'ASC'),
		'poller_support_info' => array(__('Support Information', 'grid'), 'ASC'),
		'remote'              => array(__('Remote Poller', 'grid'), 'ASC'));

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$stats = array();
	$start_times = db_fetch_assoc("SELECT *
		FROM settings
		WHERE name LIKE 'grid_prev_%_start_%'");

	if (cacti_sizeof($start_times)) {
		foreach ($start_times as $t) {
			$id_parts = explode('_', $t['name']);
			$id       = $id_parts[cacti_sizeof($id_parts)-1];
			$poller   = db_fetch_cell_prepared('SELECT poller_id
				FROM grid_clusters
				WHERE clusterid = ?',
				array($id));

			if (!empty($poller)) {
				if (!isset($stats[$poller])) {
					$stats[$poller] = $t['value'];
				} elseif ($t['value'] > $stats[$poller]) {
					$stats[$poller] = $t['value'];
				}
			}
		}
	}

	if (is_array($pollers) && cacti_sizeof($pollers)) {
		foreach ($pollers as $poller) {
			if (isset($stats[$poller['poller_id']])) {
				if (time() - $stats[$poller['poller_id']] < 1200) {
					$status = '<span class="deviceUp">' . __('Up', 'grid') . '</span>';
					$date   = date('Y-m-d H:i:s', $stats[$poller['poller_id']]);
				} else {
					$status = '<span class="deviceDown">' . __('Down', 'grid') . '</span>';
					$date   = date('Y-m-d H:i:s', $stats[$poller['poller_id']]);
				}
			} else {
				if (db_fetch_cell_prepared('SELECT count(*) FROM grid_clusters WHERE poller_id = ?', array($poller['poller_id']))) {
					$status = '<span class="deviceUnknown">' . __('Unknown', 'grid'). '</span>';
					$date   = __('Unknown', 'grid');
				} else {
					$status = '<span class="deviceRecoverying">' . __('N/A', 'grid') . '</span>';
					$date   = __('N/A', 'grid');
				}
			}

			form_alternate_row('line' . $poller['poller_id'], true);
			form_selectable_cell("<a class='linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_pollers.php?action=edit&poller_id=' . $poller['poller_id']) . "'>" . html_escape($poller['poller_name']) . '</a>', $poller['poller_id']);
			form_selectable_cell($poller['poller_id'], $poller['poller_id']);
			form_selectable_cell($status, $poller['poller_id']);
			form_selectable_cell($poller['lsf_version'], $poller['poller_id']);
			form_selectable_cell(html_escape($poller['poller_location']), $poller['poller_id']);
			form_selectable_cell($date, $poller['poller_id']);
			form_selectable_cell(html_escape($poller['poller_support_info']), $poller['poller_id']);
			form_selectable_cell($poller['remote']=='on'?'Y':'N', $poller['poller_id']);
			form_checkbox_cell($poller['poller_name'], $poller['poller_id']);
			form_end_row();
		}
	} else {
		print "<tr><td colspan='8'><em>" . __('No RTM Pollers Defined', 'grid') . "</em></td></tr>";
	}

	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($grid_poller_actions);

	form_end();
}
