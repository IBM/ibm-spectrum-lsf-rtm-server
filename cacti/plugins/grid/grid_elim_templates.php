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
include_once(dirname(__FILE__) . '/grid_elim_functions.php');
include_once('./lib/utility.php');
include_once('./lib/template.php');
include_once('./lib/api_tree.php');
include_once('./lib/html_tree.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_data_source.php');

$graph_actions = array(
	1 => __('Delete', 'grid')
);

/* set default action */
set_default_action();

get_filter_request_var('graph_template_id');

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'template_remove':
		template_remove();

		header('Location: grid_elim_templates.php');
		break;
	case 'input_remove':
		input_remove();

		header('Location: grid_elim_templates.php?action=template_edit&id=' . get_request_var('graph_template_id'));
		break;
	case 'input_edit':
		top_header();

		input_edit();

		bottom_footer();
		break;
	case 'template_edit':
		top_header();

		template_edit();

		bottom_footer();
		break;
	default:
		top_header();

		template();

		bottom_footer();
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	global $struct_graph;
	if (isset_request_var('save_component_template')) {
		get_filter_request_var('graph_template_id');

		$save1['id']   = get_request_var('graph_template_id');
		$save1['hash'] = elim_get_hash_graph_template(get_request_var('graph_template_id'));
		$save1['name'] = form_input_validate(get_request_var('name'), 'name', '', false, 3);

		$save2['id']                     = get_nfilter_request_var('graph_template_graph_id');
		$save2['local_graph_template_graph_id'] = 0;
		$save2['local_graph_id']         = 0;
		$save2['t_image_format_id']      = (isset_request_var('t_image_format_id') ? get_nfilter_request_var('t_image_format_id') : '');
		$save2['image_format_id']        = form_input_validate(get_nfilter_request_var('image_format_id'), 'image_format_id', '^[0-9]+$', true, 3);
		$save2['t_title']                = form_input_validate((isset_request_var('t_title') ? get_nfilter_request_var('t_title') : ''), 't_title', '', true, 3);
		$save2['title']                  = form_input_validate(get_nfilter_request_var('title'), 'title', '', (isset_request_var('t_title') ? true : false), 3);
		$save2['t_height']               = form_input_validate((isset_request_var('t_height') ? get_nfilter_request_var('t_height') : ''), 't_height', '', true, 3);
		$save2['height']                 = form_input_validate(get_nfilter_request_var('height'), 'height', '^[0-9]+$', (isset_request_var('t_height') ? true : false), 3);
		$save2['t_width']                = form_input_validate((isset_request_var('t_width') ? get_nfilter_request_var('t_width') : ''), 't_width', '', true, 3);
		$save2['width']                  = form_input_validate(get_nfilter_request_var('width'), 'width', '^[0-9]+$', (isset_request_var('t_width') ? true : false), 3);
		$save2['t_upper_limit']          = form_input_validate((isset_request_var('t_upper_limit') ? get_nfilter_request_var('t_upper_limit') : ''), 't_upper_limit', '', true, 3);
		$save2['upper_limit']            = form_input_validate(get_nfilter_request_var('upper_limit'), 'upper_limit', '^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$', ((isset_request_var('t_upper_limit') || (strlen(get_nfilter_request_var('upper_limit')) === 0)) ? true : false), 3);
		$save2['t_lower_limit']          = form_input_validate((isset_request_var('t_lower_limit') ? get_nfilter_request_var('t_lower_limit') : ''), 't_lower_limit', '', true, 3);
		$save2['lower_limit']            = form_input_validate(get_nfilter_request_var('lower_limit'), 'lower_limit', '^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$', ((isset_request_var('t_lower_limit') || (strlen(get_nfilter_request_var('lower_limit')) === 0)) ? true : false), 3);
		$save2['t_vertical_label']       = form_input_validate((isset_request_var('t_vertical_label') ? get_nfilter_request_var('t_vertical_label') : ''), 't_vertical_label', '', true, 3);
		$save2['vertical_label']         = form_input_validate(get_nfilter_request_var('vertical_label'), 'vertical_label', '', true, 3);
		$save2['t_slope_mode']           = form_input_validate((isset_request_var('t_slope_mode') ? get_nfilter_request_var('t_slope_mode') : ''), 't_slope_mode', '', true, 3);
		$save2['slope_mode']             = form_input_validate((isset_request_var('slope_mode') ? get_nfilter_request_var('slope_mode') : ''), 'slope_mode', '', true, 3);
		$save2['t_auto_scale']           = form_input_validate((isset_request_var('t_auto_scale') ? get_nfilter_request_var('t_auto_scale') : ''), 't_auto_scale', '', true, 3);
		$save2['auto_scale']             = form_input_validate((isset_request_var('auto_scale') ? get_nfilter_request_var('auto_scale') : ''), 'auto_scale', '', true, 3);
		$save2['t_auto_scale_opts']      = form_input_validate((isset_request_var('t_auto_scale_opts') ? get_nfilter_request_var('t_auto_scale_opts') : ''), 't_auto_scale_opts', '', true, 3);
		$save2['auto_scale_opts']        = form_input_validate(get_nfilter_request_var('auto_scale_opts'), 'auto_scale_opts', '', true, 3);
		$save2['t_auto_scale_log']       = form_input_validate((isset_request_var('t_auto_scale_log') ? get_nfilter_request_var('t_auto_scale_log') : ''), 't_auto_scale_log', '', true, 3);
		$save2['auto_scale_log']         = form_input_validate((isset_request_var('auto_scale_log') ? get_nfilter_request_var('auto_scale_log') : ''), 'auto_scale_log', '', true, 3);
		$save2['t_scale_log_units']      = form_input_validate((isset_request_var('t_scale_log_units') ? get_nfilter_request_var('t_scale_log_units') : ''), 't_scale_log_units', '', true, 3);
		$save2['scale_log_units']        = form_input_validate((isset_request_var('scale_log_units') ? get_nfilter_request_var('scale_log_units') : ''), 'scale_log_units', '', true, 3);
		$save2['t_auto_scale_rigid']     = form_input_validate((isset_request_var('t_auto_scale_rigid') ? get_nfilter_request_var('t_auto_scale_rigid') : ''), 't_auto_scale_rigid', '', true, 3);
		$save2['auto_scale_rigid']       = form_input_validate((isset_request_var('auto_scale_rigid') ? get_nfilter_request_var('auto_scale_rigid') : ''), 'auto_scale_rigid', '', true, 3);
		$save2['t_auto_padding']         = form_input_validate((isset_request_var('t_auto_padding') ? get_nfilter_request_var('t_auto_padding') : ''), 't_auto_padding', '', true, 3);
		$save2['auto_padding']           = form_input_validate((isset_request_var('auto_padding') ? get_nfilter_request_var('auto_padding') : ''), 'auto_padding', '', true, 3);
		$save2['t_base_value']           = form_input_validate((isset_request_var('t_base_value') ? get_nfilter_request_var('t_base_value') : ''), 't_base_value', '', true, 3);
		$save2['base_value']             = form_input_validate(get_nfilter_request_var('base_value'), 'base_value', '^[0-9]+$', (isset_request_var('t_base_value') ? true : false), 3);
		$save2['t_unit_value']           = form_input_validate((isset_request_var('t_unit_value') ? get_nfilter_request_var('t_unit_value') : ''), 't_unit_value', '', true, 3);
		$save2['unit_value']             = form_input_validate(get_nfilter_request_var('unit_value'), 'unit_value', '', true, 3);
		$save2['t_unit_exponent_value']  = form_input_validate((isset_request_var('t_unit_exponent_value') ? get_nfilter_request_var('t_unit_exponent_value') : ''), 't_unit_exponent_value', '', true, 3);
		$save2['unit_exponent_value']    = form_input_validate(get_nfilter_request_var('unit_exponent_value'), 'unit_exponent_value', '^-?[0-9]+$', true, 3);
		$save2['t_alt_y_grid']           = form_input_validate((isset_request_var('t_alt_y_grid') ? get_nfilter_request_var('t_alt_y_grid') : ''), 't_alt_y_grid', '', true, 3);
		$save2['alt_y_grid']             = form_input_validate((isset_request_var('alt_y_grid') ? get_nfilter_request_var('alt_y_grid') : ''), 'alt_y_grid', '', true, 3);
		$save2['t_right_axis']           = form_input_validate((isset_request_var('t_right_axis') ? get_nfilter_request_var('t_right_axis') : ''), 't_right_axis', '', true, 3);
		$save2['right_axis']             = form_input_validate((isset_request_var('right_axis') ? get_nfilter_request_var('right_axis') : ''), 'right_axis', '^[.0-9]+:-?[.0-9]+$', true, 3);
		$save2['t_right_axis_label']     = form_input_validate((isset_request_var('t_right_axis_label') ? get_nfilter_request_var('t_right_axis_label') : ''), 't_right_axis_label', '', true, 3);
		$save2['right_axis_label']       = form_input_validate((isset_request_var('right_axis_label') ? get_nfilter_request_var('right_axis_label') : ''), 'right_axis_label', '', true, 3);
		$save2['t_right_axis_format']    = form_input_validate((isset_request_var('t_right_axis_format') ? get_nfilter_request_var('t_right_axis_format') : ''), 't_right_axis_format', '', true, 3);
		$save2['right_axis_format']      = form_input_validate((isset_request_var('right_axis_format') ? get_nfilter_request_var('right_axis_format') : ''), 'right_axis_format', '^[0-9]+$', true, 3);
		$save2['t_no_gridfit']           = form_input_validate((isset_request_var('t_no_gridfit') ? get_nfilter_request_var('t_no_gridfit') : ''), 't_no_gridfit', '', true, 3);
		$save2['no_gridfit']             = form_input_validate((isset_request_var('no_gridfit') ? get_nfilter_request_var('no_gridfit') : ''), 'no_gridfit', '', true, 3);
		$save2['t_unit_length']          = form_input_validate((isset_request_var('t_unit_length') ? get_nfilter_request_var('t_unit_length') : ''), 't_unit_length', '', true, 3);
		$save2['unit_length']            = form_input_validate((isset_request_var('unit_length') ? get_nfilter_request_var('unit_length') : ''), 'unit_length', '^[0-9]+$', true, 3);
		$save2['t_tab_width']            = form_input_validate((isset_request_var('t_tab_width') ? get_nfilter_request_var('t_tab_width') : ''), 't_tab_width', '', true, 3);
		$save2['tab_width']              = form_input_validate((isset_request_var('tab_width') ? get_nfilter_request_var('tab_width') : ''), 'tab_width', '^[0-9]*$', true, 3);
		$save2['t_dynamic_labels']       = form_input_validate((isset_request_var('t_dynamic_labels') ? get_nfilter_request_var('t_dynamic_labels') : ''), 't_dynamic_labels', '', true, 3);
		$save2['dynamic_labels']         = form_input_validate((isset_request_var('dynamic_labels') ? get_nfilter_request_var('dynamic_labels') : ''), 'dynamic_labels', '', true, 3);
		$save2['t_force_rules_legend']   = form_input_validate((isset_request_var('t_force_rules_legend') ? get_nfilter_request_var('t_force_rules_legend') : ''), 't_force_rules_legend', '', true, 3);
		$save2['force_rules_legend']     = form_input_validate((isset_request_var('force_rules_legend') ? get_nfilter_request_var('force_rules_legend') : ''), 'force_rules_legend', '', true, 3);
		$save2['t_legend_position']      = form_input_validate((isset_request_var('t_legend_position') ? get_nfilter_request_var('t_legend_position') : ''), 't_legend_position', '', true, 3);
		$save2['legend_position']        = form_input_validate((isset_request_var('legend_position') ? get_nfilter_request_var('legend_position') : ''), 'legend_position', '', true, 3);
		$save2['t_legend_direction']     = form_input_validate((isset_request_var('t_legend_direction') ? get_nfilter_request_var('t_legend_direction') : ''), 't_legend_direction', '', true, 3);
		$save2['legend_direction']       = form_input_validate((isset_request_var('legend_direction') ? get_nfilter_request_var('legend_direction') : ''), 'legend_direction', '', true, 3);
		$save2['t_right_axis_formatter'] = form_input_validate((isset_request_var('t_right_axis_formatter') ? get_nfilter_request_var('t_right_axis_formatter') : ''), 't_right_axis_formatter', '', true, 3);
		$save2['right_axis_formatter']   = form_input_validate((isset_request_var('right_axis_formatter') ? get_nfilter_request_var('right_axis_formatter') : ''), 'right_axis_formatter', '', true, 3);
		$save2['t_left_axis_formatter']  = form_input_validate((isset_request_var('t_left_axis_formatter') ? get_nfilter_request_var('t_left_axis_formatter') : ''), 't_left_axis_formatter', '', true, 3);
		$save2['left_axis_formatter']    = form_input_validate((isset_request_var('left_axis_formatter') ? get_nfilter_request_var('left_axis_formatter') : ''), 'left_axis_formatter', '', true, 3);
		if (!is_error_message()) {
			$graph_template_id = sql_save($save1, 'grid_elim_templates');

			if ($graph_template_id) {
				raise_message(1);
			} else {
				raise_message(2);
			}
		}

		if (!is_error_message()) {
			$save2['graph_template_id'] = $graph_template_id;
			$graph_template_graph_id = sql_save($save2, 'grid_elim_templates_graph');

			if ($graph_template_graph_id) {
				raise_message(1);

				elim_push_out_graph($graph_template_graph_id);
			} else {
				raise_message(2);
			}
		}
	}

	header('Location: grid_elim_templates.php?action=template_edit&id=' . (empty($graph_template_id) ? get_request_var('graph_template_id') : $graph_template_id));
}

/* ------------------------
    The "actions" function
   ------------------------ */
function form_actions() {
	global $graph_actions;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				if (!isset_request_var('delete_type')) {
					set_request_var('delete_type', 1);
				}

				for ($i=0;($i<count($selected_items));$i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */
				}

				switch (get_request_var('delete_type')) {
					case '2': /* delete all graphs are created based on thost ELIM templates */
						$elim_graphs = array_rekey(
							db_fetch_assoc('SELECT gtg.local_graph_id
								FROM graph_templates_graph AS gtg
								INNER JOIN grid_elim_templates_graph_map AS getgm
								ON getgm.local_graph_id = gtg.local_graph_id
								WHERE gtg.local_graph_id > 0
								AND ' . array_to_sql_or($selected_items, 'getgm.grid_elim_template_id')),
							'local_graph_id', 'local_graph_id'
						);

						if (cacti_sizeof($elim_graphs)) {
							/* change $elim_graphs to $elim_graphs_array */
							$elim_graphs_array=array();
							$i=0;
							foreach($elim_graphs as $id => $local_graph_id) {
								if (!empty($local_graph_id)) {
									$elim_graphs_array[$i] = $local_graph_id;
									$i++;
								}
							}
							/*$data_sources_before = array_rekey(db_fetch_assoc("SELECT data_template_data.local_data_id
							FROM (data_template_rrd, data_template_data, graph_templates_item)
							WHERE graph_templates_item.task_item_id=data_template_rrd.id
							AND data_template_rrd.local_data_id=data_template_data.local_data_id
							AND " . array_to_sql_or($elim_graphs_array, "graph_templates_item.local_graph_id") . "
							AND data_template_data.local_data_id > 0
							group by data_template_data.local_data_id
							having count(data_template_data.local_data_id) = 1"), "local_data_id", "local_data_id");*/
							$data_sources_before = array_rekey(
								db_fetch_assoc('SELECT DISTINCT dtr.local_data_id
									FROM data_template_rrd AS dtr
									INNER JOIN graph_templates_item AS gti
									ON gti.task_item_id = dtr.id
									WHERE ' . array_to_sql_or($elim_graphs_array, 'gti.local_graph_id') . '
									AND dtr.local_data_id > 0'),
								'local_data_id', 'local_data_id'
							);

							api_graph_remove_multi($elim_graphs);
							api_plugin_hook_function('graphs_remove', $elim_graphs);

							if (cacti_sizeof($data_sources_before)) {
								/* change $data_sources_before to $data_sources_before_array */
								$data_sources_before_array=array();
								$i=0;
								foreach($data_sources_before as $id => $local_data_id) {
									if (!empty($local_data_id)) {
										$data_sources_before_array[$i] = $local_data_id;
										$i++;
									}
								}

								/* check the data_sources again to find out if it is be used by other graphs after delete the ELIM graphs */
								$data_sources_after = array_rekey(
									db_fetch_assoc('SELECT DISTINCT dtr.local_data_id
										FROM data_template_rrd AS dtr
										INNER JOIN graph_templates_item AS gti
										ON gti.task_item_id = dtr.id
										AND ' . array_to_sql_or($data_sources_before_array, 'dtr.local_data_id') . '
										AND dtr.local_data_id > 0'),
									'local_data_id', 'local_data_id'
								);

								$data_sources_remove=array_remove($data_sources_before, $data_sources_after);
								if (cacti_sizeof($data_sources_remove)) {
									api_data_source_remove_multi($data_sources_remove);
									api_plugin_hook_function('data_source_remove', $data_sources_remove);
								}
							}
						}

						break;
				}

				db_execute('DELETE FROM grid_elim_templates
					WHERE ' . array_to_sql_or($selected_items, 'id'));

				db_execute('DELETE FROM grid_elim_templates_graph
					WHERE ' . array_to_sql_or($selected_items, 'graph_template_id') . ' and local_graph_id=0');

				db_execute('DELETE FROM grid_elim_templates_item
					WHERE ' . array_to_sql_or($selected_items, 'graph_template_id') . ' and local_graph_id=0');

				db_execute('DELETE FROM grid_elim_templates_graph_map
					WHERE ' . array_to_sql_or($selected_items, 'grid_elim_template_id'));

				db_execute('DELETE FROM grid_elim_templates_item_map
					WHERE ' . array_to_sql_or($selected_items, 'grid_elim_template_id'));

				db_execute('DELETE FROM grid_elim_template_instances
					WHERE ' . array_to_sql_or($selected_items, 'grid_elim_template_id'));
			} elseif (get_request_var('drp_action') == '2') { /* duplicate */
				for ($i=0;($i<count($selected_items));$i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					duplicate_graph(0, $selected_items[$i], get_request_var('title_format'));
				}
			}
		}

		header('Location: grid_elim_templates.php');
		exit;
	}

	if(!isset($graph_actions[get_request_var('drp_action')])) {
		header('Location: grid_elim_templates.php?header=false');
		exit;
	}

	/* setup some variables */
	$elim_template_list = ''; $i = 0;

	/* loop through each of the graphs selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$name = db_fetch_cell_prepared('SELECT name
				FROM grid_elim_templates
				WHERE id = ?',
				array($matches[1]));

			$elim_template_list .= '<li>' . html_escape($name) . '</li>';
			$elim_template_array[$i] = $matches[1];

			$i++;
		}
	}

	top_header();

	form_start('grid_elim_templates.php');

	html_start_box(__('%s ELIM Templates', $graph_actions[get_request_var('drp_action')], 'grid'), '60%', '', '3', 'center', '');

	if (isset($elim_template_array) && cacti_sizeof($elim_template_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			/* find out which (if any) graphs are created based on this ELIM template, so we can tell the user */
			if (isset($elim_template_array) && cacti_sizeof($elim_template_array)) {
				$elim_graphs = db_fetch_assoc('SELECT
					gtg.local_graph_id,
					gtg.title_cache
					FROM graph_templates_graph AS gtg
					INNER JOIN grid_elim_templates_graph_map AS getgm
					ON getgm.local_graph_id=gtg.local_graph_id
					WHERE gtg.local_graph_id > 0
					AND ' . array_to_sql_or($elim_template_array, 'getgm.grid_elim_template_id') . '
					GROUP BY gtg.local_graph_id
					ORDER BY gtg.title_cache');
			}

			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following ELIM Templates and ELIM Instances.', 'grid') . "</p>
					<div class='itemlist'><ul>$elim_template_list</ul></div>";

					if (isset($elim_graphs) && cacti_sizeof($elim_graphs)) {
						print "<tr class='even'><td class='textArea'><p class='textArea'>" . __('The following are the Graphs that were created from those ELIM templates', 'grid') . '</p>';

						print '<div class="itemlist"><ul>';
						foreach ($elim_graphs as $elim_graph) {
							print '<li>' . html_escape($elim_graph['title_cache']) . '</li>';
						}
						print '</ul></div>';

						print '<br>';

						form_radio_button('delete_type', '2', '1', __('Do not Delete the Graphs that are generated by these ELIM Templates.', 'grid'), '1'); print '<br>';
						form_radio_button('delete_type', '2', '2', __('Delete all Graphs that are generated by these ELIM Templates.', 'grid'), '1'); print '<br>';

						print '</td></tr>';
					}
			print '</td>
			</tr>';

			$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue', 'grid') . "' title='" . __esc('Delete Graph Template(s)', 'grid') . "'>";
		} elseif (get_request_var('drp_action') == '2') { /* duplicate */
			print "<tr>
				<td class='textArea'>
					<p>" . __('When you click \'Continue\', the following ELIM Graph Template(s) will be duplicated. You can optionally change the title format for the new Graph Template(s).', 'grid') . "</p>
					<div class='itemlist'><ul>$elim_template_list</ul></div>
					<p>" . __('Title Format', 'grid') . '<br>';

			form_text_box('title_format', __('<template_title> (1)', 'grid'), '', '255', '30', 'text');

			print '</p>
				</td>
			</tr>';

			$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel', 'grid') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue', 'grid') . "' title='" . __esc('Duplicate Graph Template(s)', 'grid') . "'>";
		}
	} else {
		raise_message(40);
		header('Location: grid_elim_templates.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($elim_template_array) ? serialize($elim_template_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function item() {
	global $consolidation_functions, $graph_item_types;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (isempty_request_var('id')) {
		$template_item_list = array();

		$header_label = __('ELIM Graph Template Items [ new ]', 'grid');
	} else {
		$template_item_list = db_fetch_assoc_prepared('SELECT geti.id, geti.alpha, geti.text_format,
			geti.value, geti.hard_return, geti.graph_type_id, geti.consolidation_function_id,
			geti.resource_name, geti.resource_option, cdef.name as cdef_name, colors.hex
			FROM grid_elim_templates_item AS geti
			LEFT JOIN data_template_rrd AS dtr
			ON (geti.task_item_id=dtr.id)
			LEFT JOIN data_local AS dl
			ON dtr.local_data_id=dl.id
			LEFT JOIN data_template_data AS dtd
			ON dl.id=dtd.local_data_id
			LEFT JOIN cdef
			ON cdef_id=cdef.id
			LEFT JOIN colors
			ON color_id=colors.id
			WHERE geti.graph_template_id = ?
			AND geti.local_graph_id = 0
			ORDER BY geti.sequence',
			array(get_request_var('id')));

		$i = 0;
		if (cacti_sizeof($template_item_list)) {
			foreach ($template_item_list as $item) {
				if (empty($item['resource_name'])) {
					$template_item_list[$i]['data_source_name'] = '';
				} else {
					switch($item['resource_option']) {
						case '1': //Total
							$template_item_list[$i]['data_source_name'] = __('%s - Total', $item['resource_name'], 'grid');
							break;
						case '2': //Avail
							$template_item_list[$i]['data_source_name'] = __('%s - Avail', $item['resource_name'], 'grid');
							break;
						case '3': //Reserved
							$template_item_list[$i]['data_source_name'] = __('%s - Reserved', $item['resource_name'], 'grid');
							break;
						default:
							$template_item_list[$i]['data_source_name'] = '';
							break;
						}
				}

				$i++;
			}
		}

		$name = db_fetch_cell_prepared('SELECT name
			FROM grid_elim_templates
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __esc('ELIM Graph Template Items [ edit: %s ]', $name, 'grid');
	}

	html_start_box($header_label, '100%', '', '3', 'center', 'grid_elim_graph_templates_items.php?action=item_edit&graph_template_id=' . get_request_var('id'));

	elim_draw_graph_items_list($template_item_list, 'grid_elim_graph_templates_items.php', 'graph_template_id=' . get_request_var('id'), false);

	?>
	<script type='text/javascript'>
	$(function() {
		$('.deleteMarker, .moveArrow').click(function(event) {
			event.preventDefault();
			loadPageNoHeader($(this).attr('href'));
		});
	});
	</script>
	<?php

	html_end_box();
}

/* ----------------------------
    template - Graph Templates
   ---------------------------- */

function template_edit() {
	global $struct_graph, $image_types, $fields_graph_template_template_edit;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	/* graph item list goes here */
	if (!isempty_request_var('id')) {
		item();
	}

	if (!isempty_request_var('id')) {
		$template = db_fetch_row_prepared('SELECT *
			FROM grid_elim_templates
			WHERE id = ?',
			array(get_request_var('id')));

		$template_graph = db_fetch_row_prepared('SELECT *
			FROM grid_elim_templates_graph
			WHERE graph_template_id = ?
			AND local_graph_id = 0',
			array(get_request_var('id')));

		$header_label = __esc('ELIM Template [ edit: %s ]', $template['name'], 'grid');
	} else {
		$header_label = __('ELIM Template [ new ]', 'grid');
	}

	form_start('grid_elim_templates.php', 'grid_elim_templates');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	draw_edit_form(array(
		'config' => array(),
		'fields' => inject_form_variables($fields_graph_template_template_edit, (isset($template) ? $template : array()), (isset($template_graph) ? $template_graph : array()))
		));

	html_end_box();

	html_start_box(__('ELIM Graph Template', 'grid'), '100%', '', '3', 'center', '');

	$form_array = array();

	foreach ($struct_graph as $field_name => $field_array) {
		$form_array += array($field_name => $struct_graph[$field_name]);
		if ($form_array[$field_name]['method'] != 'spacer') {
			$form_array[$field_name]['value'] = (isset($template_graph) ? $template_graph[$field_name] : '');
			$form_array[$field_name]['form_id'] = (isset($template_graph) ? $template_graph['id'] : '0');
			$form_array[$field_name]['description'] = '';
			$form_array[$field_name]['sub_checkbox'] = array(
				'name' => 't_' . $field_name,
				'friendly_name' => __('Use Per-Graph Value (Ignore this Value)', 'grid'),
				'value' => (isset($template_graph) ? $template_graph['t_' . $field_name] : '')
				);
		}
	}

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => $form_array
		)
	);

	form_hidden_box('rrdtool_version', read_config_option('rrdtool_version'), '');
	html_end_box();
	form_save_button('grid_elim_templates.php', 'return');

	//Now we need some javascript to make it dynamic
	?>
	<script type='text/javascript'>

	$(function() {
		dynamic();
	});

	function dynamic() {
		$('#t_scale_log_units').prop('disabled', true);
		$('#scale_log_units').prop('disabled', true);
		if ($('#auto_scale_log').is(':checked')) {
			$('#t_scale_log_units').prop('disabled', false);
			$('#scale_log_units').prop('disabled', false);
		}
	}

	function changeScaleLog() {
		$('#t_scale_log_units').prop('disabled', true);
		$('#scale_log_units').prop('disabled', true);
		if ($('#auto_scale_log').is(':checked')) {
			$('#t_scale_log_units').prop('disabled', false);
			$('#scale_log_units').prop('disabled', false);
		}
	}
	</script>
	<?php
}

function template() {
	global $graph_actions;

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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'elim_resource' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
	);

	validate_store_request_vars($filters, 'sess_elim_template');
	/* ==================================================== */
	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_elim_templates.php?header=false&filter=' + $('#filter').val();
		strURL += '&elim_resource=' + $('#elim_resource').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_elim_templates.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#elim_resource, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	html_start_box(__('ELIM Graph Templates', 'grid'), '100%', '', '3', 'center', 'grid_elim_templates.php?action=template_edit');

	?>
	<tr class='even'>
		<td>
		<form id='form_grid' action='grid_elim_templates.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Resource', 'grid');?>
					</td>
					<td>
						<select id='elim_resource'>
							<option value='-1'<?php if (get_request_var('elim_resource') == '-1') {?> selected<?php }?>><?php print __('Any', 'grid');?></option>
							<?php
							$resources = db_fetch_assoc('SELECT DISTINCT resource_name
								FROM grid_hosts_resources
								ORDER BY resource_name');

							if (cacti_sizeof($resources)) {
								foreach ($resources as $resource) {
									print "<option value='" . html_escape($resource['resource_name']) . "'"; if (get_request_var('elim_resource') == $resource['resource_name']) { print ' selected'; } print '>' . title_trim(html_escape($resource['resource_name']), 40) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go');?>' title='<?php print __esc('Set/Refresh Filters', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$sql_where = '';
	$sql_params = array();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = "WHERE (getmpl.name LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('elim_resource') != '-1' && !isempty_request_var('elim_resource')) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' geti.resource_name= ?';
		$sql_params[] = get_request_var('elim_resource');
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell_prepared("SELECT
		COUNT(distinct getmpl.id)
		FROM grid_elim_templates AS getmpl
		JOIN grid_elim_templates_graph AS getg
		ON getg.graph_template_id=getmpl.id
		LEFT JOIN grid_elim_templates_item geti
		ON geti.graph_template_id=getmpl.id
		$sql_where", $sql_params);

	$template_list = db_fetch_assoc_prepared("SELECT DISTINCT
		getmpl.id, getmpl.name, getg.height, getg.width
		FROM grid_elim_templates AS getmpl
		JOIN grid_elim_templates_graph AS getg
		ON getg.graph_template_id=getmpl.id
		LEFT JOIN grid_elim_templates_item AS geti
		ON geti.graph_template_id=getmpl.id
		$sql_where
		$sql_order
		$sql_limit", $sql_params);

	$display_text = array(
		'name' => array(
			'display' => __('Template Title', 'grid'),
			'sort'    => 'ASC'
		),
		'id' => array(
			'display' => __('ID', 'grid'),
			'sort'    => 'ASC'
		),
		'nosort' => array(
			'display' => __('Resources Name', 'grid')
		),
		'height' => array(
			'display' => __('Size', 'grid'),
			'sort'    => 'ASC'
		)
	);

	$nav = html_nav_bar('grid_elim_templates.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Templates', 'grid'), 'page', 'main');

	print $nav;

	form_start('grid_elim_templates.php', 'chk');

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($template_list)) {
		foreach ($template_list as $template) {
			$resource_name = array_rekey(
				db_fetch_assoc_prepared("SELECT resource_name
					FROM grid_elim_templates_item
					WHERE graph_template_id= ?
					AND resource_name !='0'",
					array($template['id'])),
				'resource_name','resource_name');

			if (cacti_sizeof($resource_name)) {
				$resource = implode(', ', $resource_name);
			} else {
				$resource = __('No Resource', 'grid');
			}

			form_alternate_row('line' . $template['id'], true);
			form_selectable_cell(filter_value($template['name'], get_request_var('filter'), 'grid_elim_templates.php?action=template_edit&id=' . $template['id']), $template['id']);
			form_selectable_cell($template['id'], $template['id']);
			form_selectable_cell(html_escape($resource), $template['id']);
			form_selectable_cell($template['height'] . 'x' . $template['width'], $template['id']);
			form_checkbox_cell($template['name'], $template['id']);

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No ELIM Templates', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($template_list)) {
		print $nav;
	}

	draw_actions_dropdown($graph_actions);

	form_end();
}

