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
include_once('./lib/api_device.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_data_source.php');
include_once('./plugins/license/include/lic_functions.php');

$actions = array(
	1 => 'Delete'
);

/* set default action */
if (!isset_request_var('action')) { set_request_var('action',''); }

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'edit':
		top_header();

		lic_server_edit();

		bottom_footer();
		break;
	default:
		top_header();

		api_update_applications_from_mappings();

		lic_applications();

		bottom_footer();
		break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save() {
	if (isset_request_var('save_component_application')) {
		$id = get_filter_request_var('id');

		if (empty($id)) {
			$id = 0;
		}

		$save = array();
		$save['id']           = $id;
		$save['application']  = get_request_var('application');
		$save['monthly_cost'] = form_input_validate(get_nfilter_request_var('monthly_cost'), 'monthly_cost', '^[0-9]+$', true, 3);
		$save['user_id']      = $_SESSION['sess_user_id'];
		$save['last_updated'] = date('Y-m-d H:i:s');

		if (!is_error_message()) {
			$id = sql_save($save, 'lic_application_accounting');
	        if ($id) {
	            raise_message(1);
				header('Location: lic_applications.php');
				exit;
			}
		}
	    raise_message(2);
		header('Location: lic_applications.php?action=edit&id=' . $id);
		exit;
	}
}

/* ------------------------
	The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $actions, $fields_fm_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		if (get_request_var('drp_action') == '1') { /* delete */
			for ($i=0; $i<count($selected_items); $i++) {
				api_delete_application($selected_items[$i]);
			}
		}
		}

		header('Location: lic_applications.php');
		exit;
	}

	/* setup some variables */
	$app_list = ''; $app_array = array();

	/* loop through each of the license servers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([^.]+)$/', $var, $matches)) {
			$id = $matches[1];

			$info = db_fetch_cell_prepared("SELECT application
				FROM lic_application_accounting AS laa
				WHERE id=?", array($id));

			$app_list    .= '<li>' . html_escape($info) . '</li>';
			$app_array[]  = $matches[1];
		}
	}

	top_header();

	form_start('lic_applications.php');

	html_start_box('<strong>' . $actions[get_request_var('drp_action')] . '</strong>', '60%', '', '3', 'center', '');

	if (cacti_sizeof($app_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea' colspan='2'>
					<p>Click 'Continue' to delete the Application Accounting data below.  Any Features Mapped to this Application will be Unmapped.</p>
					<ul>$app_list</ul>
				</td>
			</tr>";

			$title = 'Delete Application(s)';
		}

		$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='" . __esc($title) . "'>";
	} else {
		raise_message('licapp40', __('You must select at least one Application.'), MESSAGE_LEVEL_ERROR);
		header('Location: lic_applications.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($app_array) ? serialize($app_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function api_update_applications_from_mappings() {
	global $cnn_id;

	$applications = db_fetch_assoc('SELECT DISTINCT application
		FROM lic_application_feature_map
		WHERE application!=""');

	if (cacti_sizeof($applications)) {
		foreach($applications as $app) {
			$exists = db_fetch_cell_prepared('SELECT id
				FROM lic_application_accounting
				WHERE application = ?', array($app['application']));

			if (empty($id)) {
				db_execute_prepared('INSERT INTO lic_application_accounting
					(application, user_id, last_updated)
					VALUES (?, ?, NOW())
					ON DUPLICATE KEY UPDATE
						user_id = VALUES(user_id),
						last_updated = VALUES(last_updated)',
					array($app['application'], $_SESSION['sess_user_id']));
			}
		}
	}
}

function api_delete_application($id) {
	global $cnn_id;

	$application = db_fetch_cell_prepared('SELECT application
		FROM lic_application_accounting
		WHERE id = ?', array($id));

	db_execute_prepared('DELETE FROM lic_application_accounting WHERE id = ?', array($id));

	db_execute_prepared('UPDATE lic_application_feature_map
		SET application = ?,
		user_id = ?,
		last_updated=NOW()
		WHERE application = ?', array('', $_SESSION['sess_user_id'], $application));
}

/* ---------------------
	Edit Functions
   --------------------- */

function lic_server_edit() {

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	/* file: lic_applications.php, action: edit */
	$fields_fm_edit = array(
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Information'
			),
		'application' => array(
			'friendly_name' => 'Application Name',
			'description' => 'The Application Name Defined in the Feature Mappings.',
			'method' => 'other',
			'value' => '|arg1:application|',
			),
		'monthly_cost' => array(
			'method' => 'textbox',
			'friendly_name' => 'Monthly Cost',
			'description' => 'A the monthly cost of this Application.',
			'value' => '|arg1:monthly_cost|',
			'max_length' => '20',
			'size' => '20',
			'default' => '0.00'
			),
		'id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:id|'
			),
		'save_component_application' => array(
			'method' => 'hidden',
			'value' => '1'
			)
	);

	if (!isempty_request_var('id')) {
		$id = get_request_var('id');

		$app = db_fetch_row_prepared('SELECT laa.*
			FROM lic_application_accounting AS laa
			WHERE id=?', array($id));

		if ($app){
			$header_label = 'License Application [edit: ' . htmlspecialchars($app['application']) . ']';
		}
	} else {
		$header_label = 'License Application [new]';
	}

	form_start('lic_applications.php', 'lic_app_form');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_fm_edit, (isset($app) ? $app : array()))
		)
	);

	html_end_box();

	form_save_button('lic_applications.php', '', 'id');
}

function lic_filter() {
	global $lic_search_types, $lic_rows_selector, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_config' action='lic_applications.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print htmlspecialchars(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Records', 'license');?>
					</td>
					<td>
						<select id='rows_selector' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector)) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows_selector') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function lic_application_records(&$sql_where, $apply_limits, $row_limit, &$sql_params) {
	global $cnn_id;

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'laa.application LIKE ?';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();
	$sql_limit = '';

	if ($apply_limits) {
	    $sql_limit .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	$sql_query = "SELECT laa.*, COUNT(*) AS features
		FROM lic_application_accounting AS laa
		LEFT JOIN lic_application_feature_map AS lafm
		ON laa.application=lafm.application
		$sql_where
		GROUP BY laa.application
		$sql_order
		$sql_limit";

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function lic_applications() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $config;
	global $actions, $config;
	$sql_params = array();

	$filters = array(
		'rows_selector' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'application',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_la');

	html_start_box(__('License Applications'), '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	$sql_where    = '';
	$apply_limits = true;

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$applications = lic_application_records($sql_where, $apply_limits, $row_limit, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = urlPath + 'plugins/license/lic_applications.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = urlPath + 'plugins/license/lic_applications.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}


	$(function() {
	    $('#form_lic_config').submit(function(event) {
	        event.preventDefault();
	        applyFilter();
	    });
	});

	</script>
	<?php

	$rows_query_string = "SELECT COUNT(DISTINCT laa.application)
		FROM lic_application_accounting AS laa
		INNER JOIN lic_application_feature_map AS lafm
		ON laa.application=lafm.application
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'application'  => array('display' => __('Application', 'license'), 'sort' => 'ASC'),
		'monthly_cost' => array('display' => __('Monthly Cost', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'features'     => array('display' => __('Features', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'user_id'      => array('display' => __('Modified By', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'last_updated' => array('display' => __('Modification Date', 'license'), 'align' => 'right', 'sort' => 'DESC')
	);

	$nav = html_nav_bar('lic_applications.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Application'), 'page', 'main');

	form_start('lic_applications.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($applications)) {
		foreach ($applications as $app) {
			form_alternate_row('line' . $app['id'], true);

			$url = htmlspecialchars($config['url_path'] . 'plugins/license/lic_applications.php?action=edit&id=' . $app['id']) ;
			form_selectable_cell(filter_value($app['application'], get_request_var('filter'), $url), $app['id']);

			form_selectable_cell('$ ' . number_format($app['monthly_cost'],0), $app['id'], '', 'text-align:right');
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars($config['url_path'] . 'plugins/license/lic_feature_maps.php?reset=true&application=' . urlencode($app['application'])) . "'>" . $app['features'] . '</a>', $app['id'], '', 'text-align:right');
			form_selectable_cell(htmlspecialchars(get_username($app['user_id'])), $app['id'], '', 'text-align:right');
			form_selectable_cell($app['last_updated'], $app['id'], '', 'text-align:right');
			form_checkbox_cell($app['application'], $app['id']);
			form_end_row();
		}
		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No License Applications Found</em></td></tr>";
		html_end_box(false);
	}
	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($actions);

	form_end();

	bottom_footer();
}
