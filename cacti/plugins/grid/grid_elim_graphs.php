<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 |  Cacti - http://www.cacti.net/                                          |
 +-------------------------------------------------------------------------+
 |  IBM Corporation - http://www.ibm.com/                                  |
 +-------------------------------------------------------------------------+
*/

chdir('../../');

include('./include/auth.php');
include_once('./lib/utility.php');
include_once(dirname(__FILE__) . '/grid_elim_functions.php');
include_once($config["base_path"] . '/lib/rtm_functions.php');

$graph_actions = array(
	1 => __('Delete', 'grid'),
	2 => __('Create Graph Immediately', 'grid')
);

set_default_action();

global $messages;
if (isset ($_SESSION ['message_elim_matching_hosts']) ) {
	$messages[146]['message'] = $_SESSION ['message_elim_matching_hosts'];
	unset ($_SESSION ['message_elim_matching_hosts']);
}

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'instance_edit':
		top_header();
		instance_edit();
		bottom_footer();

		break;
	default:
		top_header();
		instance();
		bottom_footer();

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	global $messages;

	get_filter_request_var('elim_instance_id');

	if (isset_request_var('clusterid') && get_request_var('clusterid') > 0) {
		$clustername = db_fetch_cell_prepared('SELECT clustername
			FROM grid_clusters
			WHERE clusterid = ?',
			array(get_request_var('clusterid')));
	} else {
		$clustername = __('NoClusterName', 'grid');
	}
	$save['id']                    = get_request_var('elim_instance_id');
	$save['grid_elim_template_id'] = form_input_validate(get_request_var('grid_elim_template_id'), 'grid_elim_template_id', '', false, 3);
	$save['clusterid']             = form_input_validate(get_request_var('clusterid'), 'clusterid', '', false, 3);
	$save['hosttype_option']       = get_request_var('hosttype_option');
	$save['hosttype_value']        = form_input_validate(trim(get_request_var('hosttype_value')),'hosttype_value','',false,3);
	$save['data_source_profile_id']= get_request_var('data_source_profile_id');

	switch(get_request_var('hosttype_option')) {
	case '1':
		$save['name'] = $clustername . ' - Host Type - ' . $save['hosttype_value'];
		break;
	case '2':
		$save['name'] = $clustername . ' - Host Model - ' . $save['hosttype_value'];
		break;
	case '3':
		$save['name'] = $clustername . ' - Host Group - ' . $save['hosttype_value'];
		break;
	default:
		$save['name'] = $clustername . ' - - ' . $save['hosttype_value'];
		break;
	}

	if (!is_error_message()) {
		$grid_elim_template_instances_id = sql_save($save, 'grid_elim_template_instances');
		if ($grid_elim_template_instances_id) {
			$elim_instance = db_fetch_row_prepared('SELECT *
				FROM grid_elim_template_instances
				WHERE id = ?',
				array($grid_elim_template_instances_id));

			$hosts   = elim_get_matching_hosts($elim_instance);

			if (empty($hosts)) {
				$message = __('ELIM Instance Saved: No Matching Hosts for now', 'grid');
			} else {
				$message = __n('ELIM Instance Saved: There is one Matching Host', 'ELIM Instance Saved: There are '. cacti_sizeof($hosts) . ' Matching Hosts', cacti_sizeof($hosts), 'grid');
			}
			raise_message('message_elim_matching_hosts', $message, MESSAGE_LEVEL_INFO);
		} else {
			raise_message(2);
		}
	}

	header('Location: grid_elim_graphs.php?action=instance_edit&id=' . (empty($grid_elim_template_instances_id) ? get_request_var('elim_instance_id') : $grid_elim_template_instances_id));
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $graph_actions;
	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if (get_request_var('drp_action') == '1') { /* delete */
			if ($selected_items != false) {
			if (!isset_request_var('delete_type')) {
				set_request_var('delete_type', 1);
			}

			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
			}

			$ids_to_delete = '';
			$i = 0;
			/* build the array */
			if (cacti_sizeof($selected_items)) {
				foreach($selected_items as $elim_instance_id) {
					if ($i == 0) {
						$ids_to_delete .= $elim_instance_id;
					} else {
						$ids_to_delete .= ', ' . $elim_instance_id;
					}

					$i++;

					if (($i % 1000) == 0) {
						db_execute("DELETE FROM grid_elim_template_instances WHERE id IN ($ids_to_delete)");
						db_execute("DELETE FROM grid_elim_instance_graphs WHERE grid_elim_template_instance_id IN ($ids_to_delete)");
						$i = 0;
						$ids_to_delete = '';
					}
				}

				if ($i > 0) {
					db_execute("DELETE FROM grid_elim_template_instances WHERE id IN ($ids_to_delete)");
					db_execute("DELETE FROM grid_elim_instance_graphs WHERE grid_elim_template_instance_id IN ($ids_to_delete)");
				}
			}
			}
		} elseif (get_request_var('drp_action') == '2') { /* create graphs immediately */
			if ($selected_items != false) {
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				elim_check_add_graph($selected_items[$i]);
			}
			}
		} else {
			api_plugin_hook_function('graphs_action_execute', get_request_var('drp_action'));
		}

		header('Location: grid_elim_graphs.php');
		exit;
	}

	/* setup some variables */
	$graph_list = ''; $i = 0;

	/* loop through each of the graphs selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */
			$grid_elim_template_instance = db_fetch_row_prepared('SELECT id, name
				FROM grid_elim_template_instances
				WHERE id = ?',
				array($matches[1]));

			$graph_list .= '<li>' . $grid_elim_template_instance['id'] . ': ' . html_escape($grid_elim_template_instance['name']) . '</li>';
			$graph_array[$i] = $matches[1];

			$i++;
		}
	}

	top_header();

	form_start('grid_elim_graphs.php');

	html_start_box($graph_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (isset($graph_array) && cacti_sizeof($graph_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following ELIM Template Graph instance(s).', 'grid') . "</p>
					<ul>$graph_list</ul>
				</td>
			</tr>";

			$save_html = "<input type='button' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'grid') . "' title='" . __esc('Delete Instance(s)', 'grid') . "'>";
		} elseif (get_request_var('drp_action') == '2') { /* create graphs immediately*/
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Create ELIM Template Graphs immediately.', 'grid') . "</p>
					<ul>$graph_list</ul>
				</td>
			</tr>";

			$save_html = "<input type='button' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'grid') . "' title='" . __esc('Create Selected Graph(s)', 'grid') . "'>";
		} else {
			$save['drp_action']  = get_request_var('drp_action');
			$save['graph_list']  = $graph_list;
			$save['graph_array'] = (isset($graph_array) ? $graph_array : array());

			api_plugin_hook_function('graphs_action_prepare', $save);

			$save_html = "<input type='button' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'grid') . "'>";
		}
	} else {
		raise_message(40);
		header('Location: grid_elim_graphs.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($graph_array) ? serialize($graph_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

/* ----------------------------
    add a sub-tab
   ---------------------------- */
function instance_edit() {
	global $config;

	if (isempty_request_var('id')) { //new, so no existing graph.
		instance_edit_real();
		return;
	}

	get_filter_request_var('id');

	/* present a tabbed interface */
	$tabs_elim_instance = array(
		'instance_edit' => __('ELIM Instances', 'grid'),
		'list_graphs'   => __('Existing Graphs', 'grid')
	);

	/* set the default tab */
	load_current_session_value('tab', 'sess_elim_templates_tab', 'instance_edit');
	$current_tab = get_request_var('tab');

	/* draw the categories tabs on the top of the page */
	print "<table><tr><td style='padding-bottom:0px;'>\n";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>\n";

	if (cacti_sizeof($tabs_elim_instance) > 0) {
		$i = 0;
		foreach (array_keys($tabs_elim_instance) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == $current_tab) ? "class='selected'" : "class=''") . " href='" . html_escape($config['url_path'] .
				"plugins/grid/grid_elim_graphs.php?action=instance_edit" .
				"&id=" . get_request_var('id') .
				"&tab=$tab_short_name") .
				"'>$tabs_elim_instance[$tab_short_name]</a></li>\n";
			$i++;
		}
	}

	print "</ul></nav></div>\n";
	print "</tr></table>\n";

	if ($current_tab == 'instance_edit') {
		instance_edit_real();
	} else {
		list_exsiting_graphs();
	}
}

function validate_elim_graphs_graph_variables() {
	global $title, $rows_selector, $config;

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
		'alarm_type' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'title_cache',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

	validate_store_request_vars($filters, 'sess_elim_graphs_graph');
	/* ================= input validation ================= */
}

function list_exsiting_graphs() {
	global $alarm_bgcolors, $config, $gridalarms_platform_cols, $gridalarms_types, $alarm_types, $log_types;
	global $grid_rows_selector;
	$sql_params = array();

	validate_elim_graphs_graph_variables();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = 'WHERE grid_elim_instance_graphs.grid_elim_template_instance_id= ?';
	$sql_params[] = get_request_var('id');

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND (':'WHERE (') . "(title_cache LIKE ?) OR
			(hostname  LIKE ?) OR
			(grid_elim_templates.name  LIKE ?))";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ",$rows";

	$sql = "SELECT graph_templates_graph.title_cache, grid_elim_instance_graphs.local_graph_id, host.hostname,
		grid_elim_templates.name as grid_elim_template_name,
		grid_elim_templates.id as grid_elim_template_id,
		graph_templates_graph.height,graph_templates_graph.width
		FROM grid_elim_instance_graphs
		INNER JOIN grid_elim_template_instances
		ON grid_elim_template_instances.id=grid_elim_instance_graphs.grid_elim_template_instance_id
		INNER JOIN grid_elim_templates
		ON grid_elim_templates.id=grid_elim_template_instances.grid_elim_template_id
		INNER JOIN graph_templates_graph
		ON graph_templates_graph.local_graph_id=grid_elim_instance_graphs.local_graph_id
		INNER JOIN graph_local
		ON graph_local.id=grid_elim_instance_graphs.local_graph_id
		INNER JOIN host on host.id=graph_local.host_id
		$sql_where
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);
	?>
	<script type='text/javascript'>

	var elim_instance_id='<?php print get_request_var('id');?>';

	function applyFilter() {
		strURL  = 'grid_elim_graphs.php?action=instance_edit&header=false&tab=list_graphs';
		strURL += '&id='+elim_instance_id;
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_elim_graphs.php?clear=true&action=instance_edit&header=false&tab=list_graphs&id='+elim_instance_id;
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
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

	html_start_box(__('Existing Graphs', 'grid'), '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_elim_graphs.php?action=instance_edit&tab=list_graphs&id=<?php print get_request_var('id');?>' method='post'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Rows', 'grid');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search for Graph', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
		</td>
		</form>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT count(*)
		from grid_elim_instance_graphs
		join grid_elim_template_instances on grid_elim_template_instances.id=grid_elim_instance_graphs.grid_elim_template_instance_id
		join grid_elim_templates on grid_elim_templates.id=grid_elim_template_instances.grid_elim_template_id
		join graph_templates_graph on graph_templates_graph.local_graph_id=grid_elim_instance_graphs.local_graph_id
		join graph_local on graph_local.id=grid_elim_instance_graphs.local_graph_id
		join host on host.id=graph_local.host_id"  . "
		$sql_where", $sql_params);

	$strURL = 'grid_elim_graphs.php?action=instance_edit&tab=list_graphs&id=' . get_request_var('id');

	$display_text = array(
		'title_cache'           => array(__('Graph Title', 'grid'), 'ASC'),
		'local_graph_id'        => array(__('Graph ID', 'grid'), 'ASC'),
		'hostname'              => array(__('Host Name', 'grid'), 'ASC'),
		'nosort'                => array(__('Resource Name', 'grid'), ''),
		'grid_elim_template_id' => array(__('ELIM Template ID', 'grid'), 'ASC'),
		'height'                => array(__('Size', 'grid'), 'ASC')
	);

	$nav = html_nav_bar($strURL, MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Graphs', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, $strURL);

	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			$resource_name = get_resource_names($row['local_graph_id']);
			if (cacti_sizeof($resource_name)) {
				$resource = implode(', ', $resource_name);
			} else {
				$resource = __('No Resource', 'grid');
			}

			form_alternate_row();
			form_selectable_cell(filter_value(title_trim($row['title_cache'], read_config_option('max_title_length')), get_request_var('filter'), html_escape($config['url_path']) . 'graphs.php?action=graph_edit&id=' . $row['local_graph_id']), $row['local_graph_id']);
			form_selectable_cell($row['local_graph_id'], $row['local_graph_id']);
			form_selectable_cell(html_escape($row['hostname']), $row['local_graph_id']);
			form_selectable_cell(html_escape($resource), $row['local_graph_id']);
			form_selectable_cell($row['grid_elim_template_id'], $row['local_graph_id']);
			form_selectable_cell($row['height'] . 'x' . $row['width'], $row['local_graph_id']);
			form_end_row();
		}
	} else {
		form_alternate_row();
		print '<td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Graphs were found based on this ELIM Instance', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}
}

function get_resource_names($local_graph_id) {
	$i = 0;
	$source_names = array();

	$ds_ids = array_rekey(
		db_fetch_assoc_prepared('SELECT DISTINCT local_data_id
			FROM data_template_rrd
			INNER JOIN graph_templates_item
			ON graph_templates_item.task_item_id=data_template_rrd.id
			WHERE graph_templates_item.local_graph_id = ?',
			array($local_graph_id)),
		'local_data_id', 'local_data_id'
	);

    if (cacti_sizeof($ds_ids)) {
        foreach ($ds_ids as $ds_id) {
			$resource_name = db_fetch_row_prepared('SELECT value
				FROM data_input_data
				INNER JOIN data_template_data
				ON data_template_data.id=data_input_data.data_template_data_id
				INNER JOIN data_local
				ON data_local.id=data_template_data.local_data_id
				WHERE data_local.id = ?
				ORDER BY data_input_field_id DESC LIMIT 1',
				array($ds_id));

			if ($resource_name!='0' && !empty($resource_name)) {
				$source_names[$i] = $resource_name['value'];
				$i++;
			}
        }
    }

	return $source_names;
}

function instance_edit_real() {
	global $struct_graph, $image_types, $elim_hosttype_options, $graph_item_types, $struct_graph_item;

	form_start('grid_elim_graphs.php', 'grid_elim_graphs');

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	$use_graph_template = true;

	if (!isempty_request_var('id')) {
		$elim_instance= db_fetch_row_prepared('SELECT id, name, grid_elim_template_id, clusterid, hosttype_option, hosttype_value, data_source_profile_id
			FROM grid_elim_template_instances
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __esc('ELIM Instance Selection [edit: %s]', $elim_instance['name'], 'grid');

		if ($elim_instance['grid_elim_template_id'] == '0') {
			$use_graph_template = false;
		}
	} else {
		$header_label = __('ELIM Instance Selection [new]', 'grid');
		$use_graph_template = false;
	}

	/* handle debug mode */
	if (isset_request_var('debug')) {
		if (get_request_var('debug') == '0') {
			kill_session_var('graph_debug_mode');
		} elseif (get_request_var('debug') == '1') {
			$_SESSION['graph_debug_mode'] = true;
		}
	}

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$form_array = array(
		'grid_elim_template_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => __('Selected ELIM Template', 'grid'),
			'description' => __('Select an ELIM template to apply to this instance. Note that the data might be lost if you try to reapply a different template.', 'grid'),
			'value' => (isset($elim_instance) ? $elim_instance['grid_elim_template_id'] : '0'),
			//'none_value' => 'None',
			'sql' => 'select grid_elim_templates.id,grid_elim_templates.name from grid_elim_templates order by name'
			),
		'clusterid' => array(
			'method' => 'drop_sql',
			'friendly_name' => __('Cluster Name', 'grid'),
			'description' => __('Select the cluster that this graph belongs.', 'grid'),
			'value' => (isset($elim_instance) ? $elim_instance['clusterid'] : '0'),
			'sql' => 'select clusterid as id, clustername as name from grid_clusters order by clustername'
			),
		'hosttype_option' => array(
			'method' => 'drop_array',
			'array' => $elim_hosttype_options,
			'friendly_name' => __('Select a Host Filter', 'grid'),
			'description' => __('Choose the host Type, Model, or Host Group that this ELIM template will associate with.', 'grid'),
			'value' => (isset($elim_instance) ? $elim_instance['hosttype_option'] : '0')
			),
		'hosttype_value' => array(
			'method' => 'textbox',
			'friendly_name' => __('Enter a value for this filter', 'grid'),
			'description' => __('Associate the ELIM template to the selected hosts. (This filter supports regular expressions. For example, host names beginning with A will be queried as \'^A\')', 'grid'),
			'max_length' => '255',
			'value' => (isset($elim_instance) ? $elim_instance['hosttype_value'] : ''),
			'default' => ''
			),
		'data_source_profile_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => __('Data Source Profile', 'grid'),
			'description' => __('Select the Data Source Profile.  The Data Source Profile controls polling interval, the data aggregation, and retention policy for the resulting Data Sources.', 'grid'),
			'value' => (isset($elim_instance) ? $elim_instance['data_source_profile_id'] : '1'),
			'sql' => 'SELECT id, name FROM data_source_profiles ORDER BY name'
			)
		);

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
			)
		);

	if (isset_request_var('id')) {
		form_hidden_box('elim_instance_id', get_request_var('id'), '');
	} else {
		form_hidden_box('elim_instance_id', '', '');
	}

	html_end_box();

	form_save_button('grid_elim_graphs.php', 'return');
}

function instance() {
	global $graph_actions, $grid_rows_selector;
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
			'default' => 'grid_elim_template_instances.name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_elim_graph_instance');
	/* ================= input validation ================= */

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_elim_graphs.php?header=false';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_elim_graphs.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
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

	html_start_box(__('ELIM Instance Management', 'grid'), '100%', '', '3', 'center', 'grid_elim_graphs.php?action=instance_edit');
	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_elim_graphs.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Rows', 'grid');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($grid_rows_selector) > 0) {
								foreach ($grid_rows_selector as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Set/Refresh Filters', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = "WHERE (grid_elim_templates.name LIKE ?" .
			" OR grid_elim_template_instances.name LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	} else {
		$sql_where = '';
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	/* print checkbox form for validation */
	form_start('grid_elim_graphs.php', 'chk');

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(grid_elim_template_instances.id)
		FROM grid_elim_template_instances
		LEFT JOIN grid_elim_templates
		ON grid_elim_template_instances.grid_elim_template_id=grid_elim_templates.id
		$sql_where", $sql_params);

	$graph_list = db_fetch_assoc_prepared("SELECT grid_elim_template_instances.name, grid_elim_template_instances.id,
		grid_elim_templates.name as template_name, grid_elim_template_instances.clusterid
		FROM grid_elim_template_instances
		LEFT JOIN grid_elim_templates
		ON grid_elim_template_instances.grid_elim_template_id=grid_elim_templates.id
		$sql_where
		$sql_order
		$sql_limit", $sql_params);

	$display_text = array(
		'name'          => array(__('ELIM Instance', 'grid'), 'ASC'),
		'id'            => array(__('ID', 'grid'), 'ASC'),
		'template_name' => array(__('ELIM Template Name', 'grid'), 'ASC'),
		'clusterid'     => array(__('Cluster ID', 'grid'), 'ASC')
	);

	/* generate page list */
	$nav = html_nav_bar('grid_elim_graphs.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Graphs', 'grid'), 'page', 'main');

	print $nav;

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($graph_list)) {
		foreach ($graph_list as $graph) {
			/* we're escaping strings here, so no need to escape them on form_selectable_cell */
			$template_name = ((empty($graph['template_name'])) ? '<em>' . __('None', 'grid') . '</em>' : $graph['template_name']);
			form_alternate_row('line' . $graph['id']);
			form_selectable_cell(filter_value(title_trim($graph['name'], read_config_option('max_title_length')), get_request_var('filter'), 'grid_elim_graphs.php?action=instance_edit&id=' . $graph['id']), $graph['id']);
			form_selectable_cell($graph['id'], $graph['id']);
			form_selectable_cell(filter_value($template_name, get_request_var('filter')), $graph['id']);
			form_selectable_cell($graph['clusterid'], $graph['id']);
			form_checkbox_cell($graph['name'], $graph['id']);
			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No ELIM Instances Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($graph_list)) {
		print $nav;
	}

	draw_actions_dropdown($graph_actions);

	form_end();
}

