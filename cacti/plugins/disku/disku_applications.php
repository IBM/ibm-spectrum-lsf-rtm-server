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

chdir('../../');
include('./include/auth.php');
include_once('./plugins/disku/include/disku_functions.php');

$application_actions = array(
	1 => __('Delete', 'disku'),
);

disku_request_validation();
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
		application_edit();
		bottom_footer();

		break;
	default:
		top_header();
		disku_applications();
		bottom_footer();

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if ((isset_request_var('save_component')) && (isempty_request_var('add_dq_y'))) {
		input_validate_input_number(get_request_var('id'));

		$save['application'] = form_input_validate(get_request_var('application'), 'application', '^[A-Za-z0-9\._\\\@\ -]+$', false, 3);
		$save['vendor']      = form_input_validate(get_request_var('vendor'), 'vendor', '^[A-Za-z0-9\._\\\@\ -]+$', true, 3);

		if (!isempty_request_var('id')) {
			$save['id'] = get_request_var('id');
		} else {
			$save['id'] = 0;
		}

		if (!is_error_message()) {
			$id = sql_save($save, 'disku_applications');
			raise_message(1);
		}

		if (is_error_message()) {
			header('Location: disku_applications.php?action=edit&id=' . (!isempty_request_var('id') ? get_request_var('id') : ''));
		} else {
			header('Location: disku_applications.php');
		}
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $application_actions, $field_app_edit;

	input_validate_input_regex(get_request_var('drp_action'), '^([a-zA-Z0-9_]+)$');

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				api_disku_application_remove($selected_items);
			}
		}

		header('Location: disku_applications.php');
		exit;
	}

	/* setup some variables */
	$app_list = ''; $app_array = array();

	/* loop through each of the Scanner Path(s) selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$app_info = db_fetch_cell_prepared('SELECT application
				FROM disku_applications
				WHERE id = ?',
				array($matches[1]));

			$app_list .= '<li>' . html_escape($app_info) . '</li>';
			$app_array[] = $matches[1];
		}
	}

	top_header();

	form_start('disku_applications.php', 'chk');

	html_start_box($application_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($app_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			$applications = array_rekey(
				db_fetch_assoc("SELECT DISTINCT application
					FROM disku_applications
					WHERE id IN ('" . implode("','",$app_array) . "')"),
				'application', 'application'
			);

			$graphs = array();

			/* find out which (if any) graphs will be delete*/
			$hostid = db_fetch_cell("SELECT h.id
				FROM host AS h
				INNER JOIN host_template AS ht
				ON h.host_template_id=ht.id
				WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");

			$dqid = db_fetch_cell("SELECT id
				FROM snmp_query
				WHERE hash='a1f6a75855fc8d1ed526031e1a0de7e7'");

			if (!empty($dqid)) {
				$query_field_name = db_fetch_cell_prepared("SELECT sort_field
					FROM host_snmp_query
					WHERE host_id = ?
					AND snmp_query_id = ?",
					array($hostid, $dqid));

				$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
					FROM snmp_query_graph
					WHERE snmp_query_id = ?
					ORDER BY id",
					array($dqid));

				if (cacti_sizeof($snmp_query_types)) {
					foreach ($snmp_query_types as $snmp_query_type) {
						$a_extension_graphs = db_fetch_assoc_prepared("SELECT graph_templates_graph.title_cache
							FROM graph_local
							JOIN graph_templates_graph
							ON graph_local.id = graph_templates_graph.local_graph_id
							WHERE host_id=?
							AND graph_local.graph_template_id=?
							AND snmp_query_id=?
							AND snmp_index IN ('" . implode("','",$applications) . "')", array($hostid, $snmp_query_type['graph_template_id'], $dqid));
						$graphs = array_merge($graphs, $a_extension_graphs);
					}
				}
			}

			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to delete the following application(s)?</p>
					<p><ul>$app_list</ul></p>";

					if (cacti_sizeof($graphs) > 0) {
						print "<tr><td class='textArea'><p class='textArea'>The following graphs are related with these application(s):</p>\n";

						print "<ul>";
						foreach ($graphs as $graph) {
							print "<li><strong>" . html_escape($graph["title_cache"]) . "</strong></li>\n";
						}
						print "</ul>";

						print "<br>";
						form_radio_button("delete_type", "3", "1", "Leave the Graph(s) and related Data source(s) untouched.", "1"); print "<br>";
						form_radio_button("delete_type", "2", "2", "Delete all Graph(s) and related Data source(s) that reference these application(s).", "1"); print "<br>";
						print "</td></tr>";
					}
					print "</td>
				</tr>";

			$title = __('Delete Application(s)', 'disku');
		}

		$save_html = "<input type='button' value='Cancel' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='Continue' title='$title'";
	} else {
		raise_message(40, __("You must select at least one Application."), MESSAGE_LEVEL_ERROR);
		header('Location: disku_applications.php?header=false');
		exit;
	}

	print " <tr>
		<td align='right' bgcolor='#eaeaea'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($app_array) ? serialize($app_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

function api_disku_application_remove($selected_items) {
	/* setup some variables */
	$appids = array();

	for ($i=0; $i<count($selected_items); $i++) {
		/* ================= input validation ================= */
		input_validate_input_number($selected_items[$i]);
		/* ==================================================== */
		$appids[] = $selected_items[$i];
	}

	if (!isset_request_var('delete_type')) { set_request_var('delete_type',1); }

	if (cacti_sizeof($appids)) {
		if (get_request_var('delete_type') == 2) {
			$applications=array_rekey(db_fetch_assoc("SELECT DISTINCT application FROM disku_applications WHERE id IN ('" . implode("','",$appids) . "')"), 'application', 'application');
			remove_graphs_for_applications($applications);
		}

		db_execute("DELETE FROM disku_applications WHERE id IN ('" . implode("','",$appids) . "')");
		db_execute("DELETE FROM disku_extension_monitors WHERE application_id IN ('" . implode("','",$appids) . "')");
	}
}

function remove_graphs_for_applications($applications) {
	global  $config;

	include_once($config['library_path'] . '/api_graph.php');
	include_once($config['library_path'] . '/api_data_source.php');

	/* setup some variables */
	$extension_graphs = array(); $a_extension_graphs = array();

	$hostid = db_fetch_cell("SELECT h.id
		FROM host AS h
		INNER JOIN host_template AS ht
		ON h.host_template_id=ht.id
		WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");

	$dqid = db_fetch_cell("SELECT id
		FROM snmp_query
		WHERE hash='a1f6a75855fc8d1ed526031e1a0de7e7'");

	if (!empty($dqid)) {
		$query_field_name = db_fetch_cell_prepared("SELECT sort_field
			FROM host_snmp_query
			WHERE host_id=?
			AND snmp_query_id=?", array($hostid, $dqid));

		print "NOTE: The Query Field name is '$query_field_name'\n";

		$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
			FROM snmp_query_graph
			WHERE snmp_query_id=?
			ORDER BY id", array($dqid));

		if (cacti_sizeof($snmp_query_types)) {
			foreach ($snmp_query_types as $snmp_query_type) {
				$a_extension_graphs = db_fetch_assoc_prepared("SELECT id
					FROM graph_local
					WHERE host_id=?
					AND graph_template_id=?
					AND snmp_query_id=?
					AND snmp_index IN ('" . implode("','",$applications) . "')", array($hostid, $snmp_query_type['graph_template_id'], $dqid));
				$extension_graphs = array_merge($extension_graphs, $a_extension_graphs);
			}
		}
	}
	if (cacti_sizeof($extension_graphs)) {
		$delete_graphs= array();
		for ($i=0;($i<count($extension_graphs));$i++) {
			$delete_graphs[] = $extension_graphs[$i]['id'];
		}

		$data_sources = array_rekey(db_fetch_assoc("SELECT data_template_data.local_data_id
			FROM (data_template_rrd, data_template_data, graph_templates_item)
			WHERE graph_templates_item.task_item_id=data_template_rrd.id
			AND data_template_rrd.local_data_id=data_template_data.local_data_id
			AND " . array_to_sql_or($delete_graphs, "graph_templates_item.local_graph_id") . "
			AND data_template_data.local_data_id > 0"), "local_data_id", "local_data_id");

		if (cacti_sizeof($data_sources)) {
			api_data_source_remove_multi($data_sources);
			api_plugin_hook_function('data_source_remove', $data_sources);
		}
		api_graph_remove_multi($delete_graphs);
	}
}

function application_edit() {
	global $field_app_edit;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$application = db_fetch_row_prepared("SELECT * FROM disku_applications WHERE id = ?", array(get_request_var('id')));

		$header_label = __esc('Disk Monitoring Application [edit: %s]', ($application['application'] == '' ? __('Undefined', 'disku'):$application['application']), 'disku');
	} else {
		$header_label = __('Disk Monitoring Application [new]', 'disku');
		$application = array();
	}

	$field_app_edit = array(
		'application' => array(
			'method' => 'textbox',
			'friendly_name' => __('Application Name', 'disku'),
			'description' => __('Application Name', 'disku'),
			'value' => '|arg1:application|',
			'default' => '',
			'size' => '40',
			'max_length' => '40'
		),
		'vendor' => array(
			'method' => 'textbox',
			'friendly_name' => __('Vendor', 'disku'),
			'description' => __('Vendor', 'disku'),
			'value' => '|arg1:vendor|',
			'default' => '',
			'size' => '40',
			'max_length' => '40'
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

	form_start('disku_applications.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($field_app_edit, (isset($application) ? $application : array()))
		)
	);

	html_end_box();

	form_save_button('disku_applications.php', '', 'id');
}

function disku_filter() {
	global $config, $disku_rows_selector;

	?>
	<tr class='odd'>
		<td>
		<form id='form_disku' action='disku_applications.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Applications');?>
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
							<input type='submit' id='go' value='<?php print __('Go');?>' title='<?php print __('Search');?>'>
							<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function disku_applications() {
	global $title, $report, $config;
	global $application_actions, $config;
	$sql_params = array();

	html_start_box(__('Disk Monitoring Applications', 'disku'), '100%', '', '3', 'center', 'disku_applications.php?action=edit');
	disku_filter();
	html_end_box();

	$sql_where  = '';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$applications = disku_get_application_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'disku_applications.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'disku_applications.php?header=false&action=view&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_disku').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$rows_query_string = "SELECT COUNT(*) FROM disku_applications $sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'application' => array(
			'display' => __('Application Name', 'disku'),
			'sort' => 'ASC'
		),
		'vendor' => array(
			'display' => __('Vendor Name', 'disku'),
			'sort' => 'ASC'
		),
		'id' => array(
			'display' => __('ID', 'disku'),
			'sort' => 'ASC'
		)
	);

	form_start('disku_applications.php', 'chk');

	$nav = html_nav_bar('disku_applications.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Applications', 'disku'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($applications)) {
		foreach ($applications as $p) {
			form_alternate_row('line' . $p['id']);
			form_selectable_cell("<a class='linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/disku/disku_applications.php?action=edit&id=' . $p['id']) . "'>" . html_escape($p['application']) . '</a>', $p['id']);
			form_selectable_cell(html_escape($p['vendor']), $p['id']);
			form_selectable_cell(html_escape($p['id']), $p['id']);
			form_checkbox_cell(html_escape($p['application']), $p['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Applications Found', 'disku') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($applications)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($application_actions);

	form_end();
}

function disku_get_application_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(application LIKE ?
			OR vendor LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$app_sql = "SELECT * FROM disku_applications $sql_where $sql_order";

	if ($apply_limits) {
		$app_sql .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($app_sql, $sql_params);
}

function disku_request_validation() {
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
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
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
			)
	);

	validate_store_request_vars($filters, 'sess_disku_a');
	/* ================= input validation ================= */
}
