<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2023                                          |
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

include_once(dirname(__FILE__) . '/grid_elim_functions.php');
chdir('../../');

include('./include/auth.php');
include_once('./lib/template.php');
include_once('./lib/utility.php');

/* set default action */
set_default_action();

get_filter_request_var('graph_template_id');

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'item_remove':
		item_remove();

		header('Location: grid_elim_templates.php?action=template_edit&header=false&id=' . get_request_var('graph_template_id'));
		break;
	case 'item_movedown':
		item_movedown();

		header('Location: grid_elim_templates.php?action=template_edit&header=false&id=' . get_request_var('graph_template_id'));
		break;
	case 'item_moveup':
		item_moveup();

		header('Location: grid_elim_templates.php?action=template_edit&header=false&id=' . get_request_var('graph_template_id'));
		break;
	case 'item_edit':
		top_header();
		item_edit();
		bottom_footer();

		break;
	case 'item':
		top_header();
		item();
		bottom_footer();

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */
/* elim_push_out_graph_item - pushes out templated graph template item fields to all matching
	children. if the graph template item is part of a graph input, the field will not be
	pushed out
   @arg $graph_template_item_id - the id of the graph template item to push out values for */
function elim_push_out_graph_item($graph_template_item_id) {
	global $struct_graph_item;

	/* get information about this graph template */
	$graph_template_item = db_fetch_row_prepared('SELECT *
		FROM grid_elim_templates_item
		WHERE id = ?',
		array($graph_template_item_id));

	/* must be a graph template */
	if ($graph_template_item['graph_template_id'] == 0) {
		return 0;
	}

	/* find out if any graphs actual contain this item */
	if (cacti_sizeof(db_fetch_assoc_prepared("SELECT graph_templates_item_id FROM grid_elim_templates_item_map WHERE grid_elim_templates_item_id=?",array($graph_template_item_id))) == 0) {
		/* if not, reapply the template to push out the new item */
		$attached_graphs = db_fetch_assoc_prepared('SELECT local_graph_id
			FROM grid_elim_templates_graph_map
			WHERE grid_elim_template_id = ?',
			array($graph_template_item['graph_template_id']));

		if (cacti_sizeof($attached_graphs)) {
			foreach ($attached_graphs as $item) {
				elim_change_graph_template($item['local_graph_id'], $graph_template_item['graph_template_id'], true);
				elim_push_out_graph_input($item['local_graph_id'], $graph_template_item['graph_template_id'] ) ;
			}
		}
	}

	$graph_templates_items = array_rekey(
		db_fetch_assoc_prepared('SELECT graph_templates_item_id
			FROM grid_elim_templates_item_map
			WHERE grid_elim_templates_item_id = ?',
			array($graph_template_item['id'])),
		'graph_templates_item_id', 'graph_templates_item_id'
	);

	if (cacti_sizeof($graph_templates_items)) {
		/* loop through each graph item column name (from the above array) */
		reset($struct_graph_item); //must be struct_graph_item, for struct_elim_graph_item has more items which not in

		foreach ($struct_graph_item as $field_name => $field_array) {
			/* are we allowed to push out the column? */
			if ($field_name != 'task_item_id') {
				db_execute_prepared("UPDATE graph_templates_item
					SET $field_name = ?
					WHERE id IN (" . implode(',', $graph_templates_items) . ')',
					array($graph_template_item[$field_name]));
			}
		}
	}
}

function form_save() {
	if (isset_request_var('save_component_item')) {
		/* ================= input validation ================= */
		get_filter_request_var('graph_template_id');
		get_filter_request_var('graph_template_item_id');
		get_request_var('task_item_id'); //task_item_id for ELIM can hase the value of -1,0,mem-1

		@list($resource_name, $resource_option) = @explode("-", get_request_var('task_item_id'));
		/* ==================================================== */

		$items[0] = array();

		global $graph_item_types;
		if ($graph_item_types[get_request_var('graph_type_id')] == "LEGEND") {
			/* this can be a major time saver when creating lots of graphs with the typical
			GPRINT LAST/AVERAGE/MAX legends */
			$items = array(
				0 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "4",
					"text_format" => "Current:",
					"hard_return" => ""
					),
				1 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "1",
					"text_format" => "Average:",
					"hard_return" => ""
					),
				2 => array(
					"color_id" => "0",
					"graph_type_id" => "9",
					"consolidation_function_id" => "3",
					"text_format" => "Maximum:",
					"hard_return" => "on"
					));
		}

		foreach ($items as $item) {
			/* generate a new sequence if needed */
			if (isempty_request_var('sequence')) {
				set_request_var('sequence', get_sequence(get_request_var('sequence'), 'sequence', 'grid_elim_templates_item', 'graph_template_id=' . get_request_var('graph_template_id') . ' AND local_graph_id=0'));
			}

			$save["id"] = get_request_var('graph_template_item_id');
			$save["hash"] = elim_get_hash_graph_template(get_request_var('graph_template_item_id'), "grid_elim_template_item");
			$save["graph_template_id"] = get_request_var('graph_template_id');
			$save["local_graph_id"] = 0;
			//$save["task_item_id"] = form_input_validate(get_request_var('task_item_id'), "task_item_id", "", true, 3);
			$save["color_id"] = form_input_validate((isset($item["color_id"]) ? $item["color_id"] : get_request_var('color_id')), "color_id", "", true, 3);

			/* if alpha is disabled, use invisible_alpha instead */
			if (!isset_request_var('alpha')) {
				set_request_var('alpha', get_request_var('invisible_alpha'));
			}

			$save["alpha"] = form_input_validate((isset($item["alpha"]) ? $item["alpha"] : get_request_var('alpha')), "alpha", "", true, 3);
			$save["graph_type_id"] = form_input_validate((isset($item["graph_type_id"]) ? $item["graph_type_id"] : get_request_var('graph_type_id')), "graph_type_id", "", true, 3);
			$save["cdef_id"] = form_input_validate(get_request_var('cdef_id'), "cdef_id", "", true, 3);
			$save["consolidation_function_id"] = form_input_validate((isset($item["consolidation_function_id"]) ? $item["consolidation_function_id"] : get_request_var('consolidation_function_id')), "consolidation_function_id", "", true, 3);
			$save["text_format"] = form_input_validate((isset($item["text_format"]) ? $item["text_format"] : get_request_var('text_format')), "text_format", "", true, 3);
			$save["value"] = form_input_validate(get_request_var('value'), "value", "", true, 3);
			$save["hard_return"] = form_input_validate(((isset($item["hard_return"]) ? $item["hard_return"] : (isset_request_var('hard_return') ? get_request_var('hard_return') : ""))), "hard_return", "", true, 3);
			$save["gprint_id"] = form_input_validate(get_request_var('gprint_id'), "gprint_id", "", true, 3);
			$save["sequence"] = get_request_var('sequence');
			if (get_request_var('task_item_id')!='-1') { //if the resource NOT existing now.
				$save["resource_name"] = $resource_name;
				$save["resource_option"] = $resource_option;
			}

			if (!is_error_message()) {
				$graph_template_item_id = sql_save($save, "grid_elim_templates_item");

				if ($graph_template_item_id) {
					raise_message(1);

					elim_push_out_graph_item($graph_template_item_id);

					if (get_request_var('_task_item_id')!=get_request_var('task_item_id')) {
						/* make sure all current graphs using this graph input are aware of this change */
						$attached_graphs = db_fetch_assoc("SELECT local_graph_id FROM grid_elim_templates_graph_map WHERE grid_elim_template_id=" . get_request_var('graph_template_id') );

						if (cacti_sizeof($attached_graphs) > 0) {
						foreach ($attached_graphs as $attached_graph) {
							elim_push_out_graph_input($attached_graph["local_graph_id"], get_request_var('graph_template_id') ) ;
						}
						}
					}
				} else {
					raise_message(2);
				}
			}

			set_request_var('sequence', 0);
		}

		if (is_error_message()) {
			header("Location: grid_elim_graph_templates_items.php?action=item_edit&graph_template_item_id=" . (empty($graph_template_item_id) ? get_request_var('graph_template_item_id') : $graph_template_item_id) . "&id=" . get_request_var('graph_template_id'));
			exit;
		} else {
			header("Location: grid_elim_templates.php?action=template_edit&id=" . get_request_var('graph_template_id'));
			exit;
		}
	}
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function item_movedown() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('graph_template_id');
	/* ==================================================== */

	global $graph_item_types;

	$arr     = elim_get_graph_group(get_request_var('id'));
	$next_id = elim_get_graph_parent(get_request_var('id'), "next");

	if ((!empty($next_id)) && (isset($arr[get_request_var('id')]))) {
		elim_move_graph_group(get_request_var('id'), $arr, $next_id, "next");
	}elseif (preg_match("/(GPRINT|VRULE|HRULE|COMMENT)/", $graph_item_types[db_fetch_cell_prepared("SELECT graph_type_id FROM grid_elim_templates_item WHERE id=?", array(get_request_var('id')))])) {
		/* this is so we know the "other" graph item to propagate the changes to */
		$next_item = get_item('grid_elim_templates_item', 'sequence', get_request_var('id'), "graph_template_id=" . get_request_var('graph_template_id') . " AND local_graph_id=0", "next");

		move_item_down("grid_elim_templates_item", get_request_var('id'), "graph_template_id=" . get_request_var('graph_template_id') . " AND local_graph_id=0");

		$tmp_id = db_fetch_cell_prepared("SELECT sequence FROM grid_elim_templates_item WHERE id= ?", array(get_request_var('id')));
		db_execute_prepared("UPDATE grid_elim_templates_item SET sequence= ? WHERE local_graph_template_item_id= ?", array($tmp_id, get_request_var('id')));
		$tmp_id = db_fetch_cell_prepared("SELECT sequence FROM grid_elim_templates_item WHERE id= ?", array($next_item));
		db_execute_prepared("UPDATE grid_elim_templates_item SET sequence= ? WHERE local_graph_template_item_id= ?", array($tmp_id, $next_item));
	}

	/* handle cacti graph items */
	$items = db_fetch_assoc("SELECT id FROM grid_elim_templates_item WHERE graph_template_id=" . get_request_var('graph_template_id'));
	if (cacti_sizeof($items)) {
	foreach($items as $i) {
		elim_push_out_graph_item($i['id']);
	}
	}
}

function item_moveup() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('graph_template_id');
	/* ==================================================== */

	global $graph_item_types;

	$arr     = elim_get_graph_group(get_request_var('id'));
	$next_id = elim_get_graph_parent(get_request_var('id'), "previous");

	if ((!empty($next_id)) && (isset($arr[get_request_var('id')]))) {
		elim_move_graph_group(get_request_var('id'), $arr, $next_id, "previous");
	}elseif (preg_match("/(GPRINT|VRULE|HRULE|COMMENT)/", $graph_item_types[db_fetch_cell_prepared("SELECT graph_type_id FROM grid_elim_templates_item WHERE id=?", array(get_request_var('id')))])) {
		/* this is so we know the "other" graph item to propagate the changes to */
		$last_item = get_item("grid_elim_templates_item", "sequence", get_request_var('id'), "graph_template_id=" . get_request_var('graph_template_id') . " AND local_graph_id=0", "previous");

		move_item_up("grid_elim_templates_item", get_request_var('id'), "graph_template_id=" . get_request_var('graph_template_id') . " AND local_graph_id=0");

		$tmp_id = db_fetch_cell_prepared("SELECT sequence FROM grid_elim_templates_item WHERE id= ?", array(get_request_var('id')));
		db_execute_prepared("UPDATE grid_elim_templates_item SET sequence= ? WHERE local_graph_template_item_id= ?", array($tmp_id, get_request_var('id')));
		$tmp_id = db_fetch_cell_prepared("SELECT sequence FROM grid_elim_templates_item WHERE id= ?", array($last_item));
		db_execute_prepared("UPDATE grid_elim_templates_item SET sequence= ? WHERE local_graph_template_item_id= ?", array($tmp_id, $last_item));
	}

	/* handle cacti graph items */
	$items = db_fetch_assoc("SELECT id FROM grid_elim_templates_item WHERE graph_template_id=" . get_request_var('graph_template_id'));
	if (cacti_sizeof($items)) {
	foreach($items as $i) {
		elim_push_out_graph_item($i['id']);
	}
	}
}

function item_remove() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('graph_template_id');
	/* ==================================================== */

	db_execute_prepared("DELETE FROM grid_elim_templates_item WHERE id= ?", array(get_request_var('id')));
	//db_execute("delete from graph_templates_item where local_graph_template_item_id=" . get_request_var('id'));

	$graph_templates_item_items = db_fetch_assoc("SELECT graph_templates_item_id FROM grid_elim_templates_item_map WHERE grid_elim_templates_item_id=" . get_request_var('id'));
	$i = 0;
	if (cacti_sizeof($graph_templates_item_items) > 0) {
		foreach ($graph_templates_item_items as $item) {
			$include_items[$i] = $item["graph_templates_item_id"];
			$i++;
		}
	}
	if (isset($include_items)) {
		$sql_include_items = array_to_sql_or($include_items, "id");
	} else {
		$sql_include_items = "0=1";
	}
	db_execute("DELETE FROM graph_templates_item WHERE " . $sql_include_items);
}

/* draw edit form extra for blank option of resource name when the old resource is disapeared from mysql*/
function elim_draw_edit_form($array,$resource_not_exist) {

	if (cacti_sizeof($array) > 0) {
		foreach ($array as $top_branch => $top_children) {
			if ($top_branch == "config") {
				$config_array = $top_children;
			}elseif ($top_branch == "fields") {
				$fields_array = $top_children;
			}
		}
	}

	$i = 0;
	if (cacti_sizeof($fields_array) > 0) {
		foreach ($fields_array as $field_name => $field_array) {
			if ($i == 0) {
				if (!isset($config_array["no_form_tag"])) {
					print "<tr style='display:none;'><td><form method='post' autocomplete='off' action='" . html_escape(((isset($config_array["post_to"])) ? $config_array["post_to"] : basename($_SERVER["PHP_SELF"])) . "'" . ((isset($config_array["form_name"])) ? " name='" . $config_array["form_name"] . "'" : "") . ((isset($config_array["enctype"])) ? " enctype='" . $config_array["enctype"] . "'" : "")) . "></td></tr>\n";
				}
			}

			if ($field_array["method"] == "hidden") {
				form_hidden_box($field_name, $field_array["value"], ((isset($field_array["default"])) ? $field_array["default"] : ""));
				continue;
			}elseif ($field_array["method"] == "hidden_zero") {
				form_hidden_box($field_name, $field_array["value"], "0");
			}elseif ($field_array["method"] == "spacer") {
				print "<tr id='row_$field_name'><td colspan='2' class='tableSubHeaderColumn'>" . html_escape($field_array["friendly_name"]) . "</td></tr>\n";
			} else {
				if (isset($config_array["force_row_color"])) {
					print "<tr id='row_$field_name' bgcolor='#" . $config_array["force_row_color"] . "'>";
				} else {
					form_alternate_row_color('', '', $i, 'row_' . $field_name);
				}

				print "<td width='" . ((isset($config_array["left_column_width"])) ? $config_array["left_column_width"] : "50%") . "'>\n<font class='textEditTitle'>" . html_escape($field_array["friendly_name"]) . "</font><br>\n";

				if (isset($field_array["sub_checkbox"])) {
					form_checkbox($field_array["sub_checkbox"]["name"], $field_array["sub_checkbox"]["value"],
							$field_array["sub_checkbox"]["friendly_name"], "",
					    		((isset($field_array['sub_checkbox']['form_id']))   ? $field_array['sub_checkbox']['form_id'] : ''),
							((isset($field_array['sub_checkbox']['class'])) ? $field_array['sub_checkbox']['class'] : ''),
					    		((isset($field_array['sub_checkbox']['on_change'])) ? $field_array['sub_checkbox']['on_change'] : ''));
				}

				print ((isset($field_array["description"])) ? html_escape($field_array["description"]) : "") . "</td>\n";

				print "<td>";

				elim_draw_edit_control($field_name, $field_array,$resource_not_exist);
				if ($resource_not_exist && $field_name=='task_item_id') {
					print "<font color='#008000'> The old resource [$resource_not_exist] is NOT existing now</font>\n";
				}
				print "</td>\n</tr>\n";
			}

			$i++;
		}
	}
}

/* draw edit control extra for blank option of resource name when the old resource is disapeared from mysql*/
function elim_draw_edit_control($field_name, &$field_array, $resource_not_exist) {
	switch ($field_array["method"]) {
	case 'textbox':
		form_text_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'filepath':
		form_filepath_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'dirpath':
		form_dirpath_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'textbox_password':
		form_text_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "password");

		print "<br>";

		form_text_box($field_name . "_confirm", $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "password");

		break;
	case 'textarea':
		form_text_area($field_name, $field_array["value"], $field_array["textarea_rows"],
			$field_array["textarea_cols"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_array':
		form_dropdown($field_name, $field_array["array"], "", "", $field_array["value"],
			((isset($field_array["none_value"])) ? $field_array["none_value"] : ""),
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_sql':
		elim_form_dropdown($field_name,
			db_fetch_assoc($field_array["sql"]), "name", "id", $field_array["value"],
				((isset($field_array["none_value"])) ? $field_array["none_value"] : ""),
				((isset($field_array["default"])) ? $field_array["default"] : ""),
				((isset($field_array["class"])) ? $field_array["class"] : ""),
				((isset($field_array["on_change"])) ? $field_array["on_change"] : ""),
				$resource_not_exist);

		break;
	case 'drop_multi':
		form_multi_dropdown($field_name, $field_array["array"], db_fetch_assoc($field_array["sql"]), "id",
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_multi_rra':
		form_multi_dropdown($field_name, array_rekey(db_fetch_assoc("SELECT id,name FROM rra ORDER BY timespan"), "id", "name"),
			(empty($field_array["form_id"]) ? db_fetch_assoc($field_array["sql_all"]) : db_fetch_assoc($field_array["sql"])), "id",
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_tree':
		grow_dropdown_tree($field_array["tree_id"], $field_name, $field_array["value"]);

		break;
	case 'drop_color':
		form_color_dropdown($field_name, $field_array["value"], "None",
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'checkbox':
		form_checkbox($field_name,
			$field_array["value"],
			$field_array["friendly_name"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'checkbox_group':
		foreach ($field_array["items"] as $check_name => $check_array) {
			form_checkbox($check_name, $check_array["value"], $check_array["friendly_name"],
				((isset($check_array["default"])) ? $check_array["default"] : ""),
				((isset($check_array["form_id"])) ? $check_array["form_id"] : ""),
				((isset($field_array["class"])) ? $field_array["class"] : ""),
				((isset($check_array["on_change"])) ? $check_array["on_change"] : (((isset($field_array["on_change"])) ? $field_array["on_change"] : ""))));

			print "<br>";
		}

		break;
	case 'radio':
		foreach ($field_array["items"] as $radio_index => $radio_array) {
			form_radio_button($field_name, $field_array["value"], $radio_array["radio_value"], $radio_array["radio_caption"],
				((isset($field_array["default"])) ? $field_array["default"] : ""),
				((isset($field_array["class"])) ? $field_array["class"] : ""),
				((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

			print "<br>";
		}

		break;
	case 'custom':
		print html_escape($field_array["value"]);

		break;
	case 'template_checkbox':
		print "<em>" . html_escape(html_boolean_friendly($field_array["value"])) . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	case 'template_drop_array':
		print "<em>" . html_escape($field_array["array"][$field_array["value"]]) . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	case 'template_drop_multi_rra':
		$items = db_fetch_assoc($field_array["sql_print"]);

		if (cacti_sizeof($items) > 0) {
		foreach ($items as $item) {
			print html_escape($item["name"]) . "<br>";
		}
		}

		break;
	case 'font':
		form_font_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'file':
		form_file($field_name,
			((isset($field_array["size"])) ? $field_array["size"] : "40"));

		break;
	default:
		print "<em>" . html_escape($field_array["value"]) . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	}
}

/* draw form dropdown extra for blank option of resource name when the old resource is disapeared from mysql*/
function elim_form_dropdown($form_name, $form_data, $column_display, $column_id, $form_previous_value, $form_none_entry, $form_default_value, $class = "", $on_change = "",$resource_not_exist = '') {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= (strlen($class) ? " ":"") . "txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<select id='" . html_escape($form_name) . "' name='" . html_escape($form_name) . "'" . $class . $on_change . ">";

	if (!empty($form_none_entry)) {
		print "<option value='0'" . (empty($form_previous_value) ? " selected" : "") . ">$form_none_entry</option>\n";
	}
	if ($resource_not_exist) {
		$data_index=cacti_sizeof($form_data);
		$form_data[$data_index]["id"]=-1; //select the blank value.
		$form_data[$data_index]["name"]=" ";
	}
	html_create_list($form_data, $column_display, $column_id, html_escape($form_previous_value));

	print "</select>\n";
}

function item_edit() {
	global $struct_elim_graph_item, $graph_item_types, $consolidation_functions;

	form_start('grid_elim_graph_templates_items.php', 'grid_elim_graph_templates_items');

	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('graph_template_id');
	/* ==================================================== */

	if (!isempty_request_var('id')) {
		$name = db_fetch_cell_prepared('SELECT name
			FROM grid_elim_templates
			WHERE id = ?',
			array(get_request_var('graph_template_id')));

		$template_item = db_fetch_row_prepared('SELECT *
			FROM grid_elim_templates_item
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label  = __esc("ELIM Graph Template Items [edit graph: %s]", $name, 'grid');
	} else {
		$template_item = array();

		$header_label  = __esc('ELIM Graph Template Items [new]', 'grid');
	}


	html_start_box($header_label, '100%', '', '3', 'center', '');

	/* by default, select the LAST DS chosen to make everyone's lives easier */
	if (!isempty_request_var('graph_template_id')) {
		$default = db_fetch_row_prepared('SELECT task_item_id
			FROM grid_elim_templates_item
			WHERE graph_template_id = ?
			AND local_graph_id = 0
			ORDER BY sequence DESC',
			array(get_request_var('graph_template_id')));

		if (cacti_sizeof($default)) {
			$struct_elim_graph_item['task_item_id']['default'] = $default['task_item_id'];
		} else {
			$struct_elim_graph_item['task_item_id']['default'] = 0;
		}
	}

	/* modifications to the default graph items array */
	$struct_elim_graph_item['task_item_id']['sql'] = "SELECT DISTINCT
		CONCAT_WS('',resource_name,' - Total') AS name,
		CONCAT_WS('',resource_name,'-1') AS id
		FROM grid_hosts_resources
		WHERE resType=1 AND host <> 'ALLHOSTS'
		UNION SELECT DISTINCT
		CONCAT_WS('',resource_name,' - Avail') as name,
		CONCAT_WS('',resource_name,'-2') as id
		FROM grid_hosts_resources
		WHERE (resType=1 OR resType=2) AND host <> 'ALLHOSTS'
		UNION SELECT DISTINCT
		CONCAT_WS('',resource_name,' - Reserved') AS name,
		CONCAT_WS('',resource_name,'-3') AS id
		FROM grid_hosts_resources
		WHERE resType=1 AND host <> 'ALLHOSTS'
		ORDER BY name";

	$form_array = array();
	$resource_not_exist = '';

	foreach ($struct_elim_graph_item as $field_name => $field_array) {
		$form_array += array($field_name => $struct_elim_graph_item[$field_name]);

		if ($field_name == 'task_item_id' && !empty($template_item)) {
			$found = db_fetch_row_prepared('SELECT distinct resource_name
				FROM grid_hosts_resources
				WHERE resource_name = ?',
				array($template_item['resource_name']));

			if (!$found && !empty($template_item['resource_name']) ) {
				$form_array[$field_name]['value'] = -1; //select the blank value.
				$resource_not_exist= $template_item['resource_name'];
			} else {
				$form_array[$field_name]['value'] = (!empty($template_item) ? $template_item['resource_name'] . '-' . $template_item['resource_option'] : '');
			}
		} else {
			$form_array[$field_name]['value'] = (!empty($template_item) ? $template_item[$field_name] : '');
		}

		$form_array[$field_name]['form_id'] = (!empty($template_item) ? $template_item['id'] : '0');

	}

	elim_draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		),
		$resource_not_exist
	);

	html_end_box(true, true);

	form_hidden_box('graph_template_item_id', (!empty($template_item) ? $template_item['id'] : '0'), '');
	form_hidden_box('graph_template_id', get_request_var('graph_template_id'), '0');
	form_hidden_box('_sequence', (!empty($template_item) ? $template_item['sequence'] : '0'), '');
	form_hidden_box('_graph_type_id', (!empty($template_item) ? $template_item['graph_type_id'] : '0'), '');
	form_hidden_box('_task_item_id', (!empty($template_item) ? $template_item['resource_name']. '-'. $template_item['resource_option'] : '0'), '');
	form_hidden_box('save_component_item', '1', '');
	form_hidden_box('invisible_alpha', $form_array['alpha']['value'], 'FF');
	form_hidden_box('rrdtool_version', read_config_option('rrdtool_version'), '');

	form_save_button('grid_elim_templates.php?action=template_edit&id=' . get_request_var('graph_template_id'));

	?>
	<script type='text/javascript'>
	//borrow from graph_templates_items.php
	$(function() {
		$('#shift').click(function(data) {
			if ($('#shift').is(':checked')) {
				$('#row_value').show();
			} else {
				$('#row_value').hide();
			}
		});

		setRowVisibility();
		$('#graph_type_id').change(function(data) {
			setRowVisibility();
		});
	});

	/*
	columns - task_item_id color_id alpha graph_type_id consolidation_function_id cdef_id value gprint_id text_format hard_return

	graph_type_ids - 1 - Comment 2 - HRule 3 - Vrule 4 - Line1 5 - Line2 6 - Line3 7 - Area 8 - Stack 9 - Gprint 10 - Legend
	*/

	function changeColorId() {
		$('#alpha').prop('disabled', true);
		if ($('#color_id').val() != 0) {
			$('#alpha').prop('disabled', false);
		}
		switch($('#graph_type_id').val()) {
		case '4':
		case '5':
		case '6':
		case '7':
		case '8':
			$('#alpha').prop('disabled', false);
		}
	}

	function setRowVisibility() {
		switch($('#graph_type_id').val()) {
		case '1': // COMMENT
			$('#row_task_item_id').hide();
			$('#row_color_id').hide();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').hide();
			$('#row_shift').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').hide();
			$('#row_vdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '2': // HRULE
		case '3': // VRULE
			$('#row_task_item_id').hide();
			$('#row_color_id').show();
			$('#row_line_width').hide();
			$('#row_dashes').show();
			$('#row_dash_offset').show();
			$('#row_textalign').hide();
			$('#row_shift').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').hide();
			$('#row_vdef_id').hide();
			$('#row_value').show();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '4': // LINE1
		case '5': // LINE2
		case '6': // LINE3
			$('#row_task_item_id').show();
			$('#row_color_id').show();
			$('#row_line_width').show();
			$('#row_dashes').show();
			$('#row_dash_offset').show();
			$('#row_textalign').hide();
			$('#row_shift').show();
			$('#row_alpha').show();
			$('#row_consolidation_function_id').show();
			$('#row_cdef_id').show();
			$('#row_vdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '20': // LINE:STACK
			$('#row_task_item_id').show();
			$('#row_color_id').show();
			$('#row_line_width').show();
			$('#row_dashes').show();
			$('#row_dash_offset').show();
			$('#row_textalign').hide();
			$('#row_shift').show();
			$('#row_alpha').show();
			$('#row_consolidation_function_id').show();
			$('#row_cdef_id').show();
			$('#row_vdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '7': // AREA
		case '8': // STACK
			$('#row_task_item_id').show();
			$('#row_color_id').show();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').hide();
			$('#row_shift').show();
			$('#row_alpha').show();
			$('#row_consolidation_function_id').show();
			$('#row_cdef_id').show();
			$('#row_vdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '9':  // GPRINT
		case '11': // GPRINT:MAX
		case '12': // GPRINT:MIN
		case '13': // GPRINT:MIN
		case '14': // GPRINT:AVERAGE
			$('#row_task_item_id').show();
			$('#row_color_id').hide();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').hide();
			$('#row_shift').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').show();
			$('#row_cdef_id').show();
			$('#row_vdef_id').show();
			$('#row_value').hide();
			$('#row_gprint_id').show();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '15': // LEGEND
		case '10': // LEGEND
			$('#row_task_item_id').show();
			$('#row_color_id').hide();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').hide();
			$('#row_shift').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').show();
			$('#row_vdef_id').show();
			$('#row_value').hide();
			$('#row_gprint_id').show();
			$('#row_text_format').hide();
			$('#row_hard_return').hide();
			break;
		case '30': // TICK
			$('#row_task_item_id').show();
			$('#row_color_id').show();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').hide();
			$('#row_shift').hide();
			$('#row_alpha').show();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').show();
			$('#row_vdef_id').show();
			$('#row_value').show();
			$('#row_gprint_id').hide();
			$('#row_text_format').show();
			$('#row_hard_return').show();
			break;
		case '40': // TEXTALIGN
			$('#row_task_item_id').hide();
			$('#row_color_id').hide();
			$('#row_line_width').hide();
			$('#row_dashes').hide();
			$('#row_dash_offset').hide();
			$('#row_textalign').show();
			$('#row_shift').hide();
			$('#row_alpha').hide();
			$('#row_consolidation_function_id').hide();
			$('#row_cdef_id').hide();
			$('#row_vdef_id').hide();
			$('#row_value').hide();
			$('#row_gprint_id').hide();
			$('#row_text_format').hide();
			$('#row_hard_return').hide();
			break;
		}

		changeColorId();
	}
	</script>
	<?php
}

