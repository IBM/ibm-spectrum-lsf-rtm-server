<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2021 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2021                                          |
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

/* elim_push_out_graph - pushes out templated graph template fields to all matching children
   @arg $graph_template_graph_id - the id of the graph template to push out values for */
function elim_push_out_graph($graph_template_graph_id) {
	global $struct_graph;

	/* get information about this graph template */
	$graph_template_graph = db_fetch_row_prepared('SELECT *
		FROM grid_elim_templates_graph
		WHERE id = ?',
		array($graph_template_graph_id));

	/* must be a graph template */
	if ($graph_template_graph['graph_template_id'] == 0) { return 0; }

	/* loop through each graph column name (from the above array) */
	reset($struct_graph);
	foreach ($struct_graph as $field_name => $field_array) {
		/* are we allowed to push out the column? */
		if (isset($graph_template_graph['t_' . $field_name]) && empty($graph_template_graph['t_' . $field_name])) {
			if ($field_array['method'] != 'spacer') {
				$graph_templates_graph_items = db_fetch_assoc_prepared('SELECT graph_templates_graph_id
					FROM grid_elim_templates_graph_map
					WHERE grid_elim_templates_graph_id = ?',
					array($graph_template_graph['id']));

				$i = 0;
				if (cacti_sizeof($graph_templates_graph_items)) {
					foreach ($graph_templates_graph_items as $item) {
						$include_items[$i] = $item['graph_templates_graph_id'];
						$i++;
					}
				}
				if (isset($include_items)) {
					$sql_include_items = array_to_sql_or($include_items, 'id');
					db_execute_prepared("UPDATE graph_templates_graph
						SET $field_name = ?
						WHERE " . $sql_include_items,
						array($graph_template_graph[$field_name]));
				}

			}
			/* update the title cache */
			if ($field_name == 'title') {
				elim_update_graph_title_cache_from_template($graph_template_graph['graph_template_id']);
			}
		}
	}
}
/* elim_update_graph_title_cache_from_template - updates the title cache for all graphs
	that match a given graph template
   @arg $graph_template_id - (int) the ID of the graph template to match */
function elim_update_graph_title_cache_from_template($graph_template_id) {
	//$graphs = db_fetch_assoc("SELECT local_graph_id from graph_templates_graph where graph_template_id=$graph_template_id and local_graph_id>0");
	$graphs = db_fetch_assoc_prepared('SELECT local_graph_id
		FROM grid_elim_instance_graphs
		JOIN grid_elim_template_instances
		ON grid_elim_template_instances.id=grid_elim_instance_graphs.grid_elim_template_instance_id
		WHERE grid_elim_template_instances.grid_elim_template_id = ?',
		array($graph_template_id));

	if (cacti_sizeof($graphs)) {
		foreach ($graphs as $item) {
			update_graph_title_cache($item['local_graph_id']);
		}
	}
}

/* elim_get_graph_group - returns an array containing each item in the graph group given a single
     graph item in that group
   @arg $graph_template_item_id - (int) the ID of the graph item to return the group of
   @returns - (array) an array containing each item in the graph group */
function elim_get_graph_group($graph_template_item_id) {
	global $graph_item_types;
	$sql_params = array();

	$graph_item = db_fetch_row_prepared('SELECT graph_type_id, sequence, local_graph_id, graph_template_id
		FROM grid_elim_templates_item
		WHERE id = ?',
		array($graph_template_item_id));

	if (empty($graph_item['local_graph_id'])) {
		$sql_where = 'graph_template_id = ? AND local_graph_id = 0';
		$sql_params[] = $graph_item['graph_template_id'];
	} else {
		$sql_where = 'local_graph_id = ?';
		$sql_params[] = $graph_item['local_graph_id'];
	}

	/* parents are LINE%, AREA%, and STACK%. If not return */
	if (!preg_match('/(LINE|AREA|STACK)/', $graph_item_types[$graph_item['graph_type_id']])) {
		return array();
	}

	$graph_item_children_array = array();

	/* put the parent item in the array as well */
	$graph_item_children_array[$graph_template_item_id] = $graph_template_item_id;

	$graph_items = db_fetch_assoc_prepared("SELECT id, graph_type_id, text_format, hard_return
		FROM grid_elim_templates_item
		WHERE sequence > ?
		AND $sql_where
		ORDER BY sequence", array_merge(array($graph_item['sequence']), $sql_params));

	$is_hard = false;

	if (cacti_sizeof($graph_items)) {
		foreach ($graph_items as $item) {
			if ($is_hard) {
				return $graph_item_children_array;
			} elseif (strstr($graph_item_types[$item['graph_type_id']], 'GPRINT') !== false) {
				/* a child must be a GPRINT */
				$graph_item_children_array[$item['id']] = $item['id'];

				if ($item['hard_return'] == 'on') {
					$is_hard = true;
				}
			} elseif (strstr($graph_item_types[$item['graph_type_id']], 'COMMENT') !== false) {
				if (preg_match_all('/\|([0-9]{1,2}):(bits|bytes):(\d):(current|total|max|total_peak|all_max_current|all_max_peak|aggregate_max|aggregate_sum|aggregate_current|aggregate):(\d)?\|/', $item['text_format'], $matches, PREG_SET_ORDER)) {
					$graph_item_children_array[$item['id']] = $item['id'];
				} elseif (preg_match_all('/\|sum:(\d|auto):(current|total|atomic):(\d):(\d+|auto)\|/', $item['text_format'], $matches, PREG_SET_ORDER)) {
					$graph_item_children_array[$item['id']] = $item['id'];
				} else {
					/* if not a GPRINT or special COMMENT then get out */
					return $graph_item_children_array;
				}
			} else {
				/* if not a GPRINT or special COMMENT then get out */
				return $graph_item_children_array;
			}
		}
	}

	return $graph_item_children_array;
}

/* elim_get_graph_parent - returns the ID of the next or previous parent graph item id
   @arg $graph_template_item_id - (int) the ID of the current graph item
   @arg $direction - ('next' or 'previous') whether to find the next or previous parent
   @returns - (int) the ID of the next or previous parent graph item id */
function elim_get_graph_parent($graph_template_item_id, $direction) {
	$sql_params = array();
	$graph_item = db_fetch_row_prepared('SELECT sequence, local_graph_id, graph_template_id
		FROM grid_elim_templates_item
		WHERE id = ?',
		array($graph_template_item_id));

	if (empty($graph_item['local_graph_id'])) {
		$sql_where = 'graph_template_id = ? AND local_graph_id = 0';
		$sql_params[] = $graph_item['graph_template_id'];
	} else {
		$sql_where = 'local_graph_id = ?';
		$sql_params[] = $graph_item['local_graph_id'];
	}

	if ($direction == 'next') {
		$sql_operator = '>';
		$sql_order = 'ASC';
	} elseif ($direction == 'previous') {
		$sql_operator = '<';
		$sql_order = 'DESC';
	}

	$next_parent_id = db_fetch_cell_prepared("SELECT id
		FROM grid_elim_templates_item
		WHERE sequence $sql_operator ?
		AND graph_type_id IN (4, 5, 6, 7, 8, 20)
		AND $sql_where
		ORDER BY sequence $sql_order
		LIMIT 1", array_merge(array($graph_item['sequence']), $sql_params));

	if (empty($next_parent_id)) {
		return 0;
	} else {
		return $next_parent_id;
	}
}

/* elim_move_graph_group - takes a graph group (parent+children) and swaps it with another graph
     group
   @arg $graph_template_item_id - (int) the ID of the (parent) graph item that was clicked
   @arg $graph_group_array - (array) an array containing the graph group to be moved
   @arg $target_id - (int) the ID of the (parent) graph item of the target group
   @arg $direction - ('next' or 'previous') whether the graph group is to be swapped with
      group above or below the current group */
function elim_move_graph_group($graph_template_item_id, $graph_group_array, $target_id, $direction) {
	$graph_item = db_fetch_row_prepared('SELECT local_graph_id, graph_template_id
		FROM grid_elim_templates_item
		WHERE id = ?',
		array($graph_template_item_id));

	if (empty($graph_item['local_graph_id'])) {
		$sql_where = 'graph_template_id = ' . $graph_item['graph_template_id'] . ' AND local_graph_id = 0';
	} else {
		$sql_where = 'local_graph_id = ' . $graph_item['local_graph_id'];
	}

	/* get a list of parent+children of our target group */
	$target_graph_group_array = elim_get_graph_group($target_id);

	/* if this "parent" item has no children, then treat it like a regular gprint */
	if (cacti_sizeof($target_graph_group_array) == 0) {
		if ($direction == 'next') {
			move_item_down('grid_elim_templates_item', $graph_template_item_id, $sql_where);
		} elseif ($direction == 'previous') {
			move_item_up('grid_elim_templates_item', $graph_template_item_id, $sql_where);
		}

		return;
	}

	/* start the sequence at '1' */
	$sequence_counter = 1;

	$graph_items = db_fetch_assoc_prepared("SELECT id, sequence
		FROM grid_elim_templates_item
		WHERE $sql_where
		ORDER BY sequence");

	if (cacti_sizeof($graph_items)) {
		foreach ($graph_items as $item) {
			/* check to see if we are at the "target" spot in the loop; if we are, update the sequences and move on */
			if ($target_id == $item['id']) {
				if ($direction == 'next') {
					$group_array1 = $target_graph_group_array;
					$group_array2 = $graph_group_array;
				} elseif ($direction == 'previous') {
					$group_array1 = $graph_group_array;
					$group_array2 = $target_graph_group_array;
				}

				foreach ($group_array1 as $graph_template_item_id) {
					db_execute_prepared('UPDATE grid_elim_templates_item
						SET sequence = ?
						WHERE id = ?',
						array($sequence_counter, $graph_template_item_id));

					/* propagate to ALL graphs using this template */
					if (empty($graph_item['local_graph_id'])) {
						db_execute_prepared('UPDATE grid_elim_templates_item
							SET sequence = ?
							WHERE local_graph_template_item_id = ?',
							array($sequence_counter, $graph_template_item_id));
					}

					$sequence_counter++;
				}

				foreach ($group_array2 as $graph_template_item_id) {
					db_execute_prepared('UPDATE grid_elim_templates_item
						SET sequence = ?
						WHERE id = ?',
						array($sequence_counter, $graph_template_item_id));

					/* propagate to ALL graphs using this template */
					if (empty($graph_item['local_graph_id'])) {
						db_execute_prepared('UPDATE grid_elim_templates_item
							SET sequence = ?
							WHERE local_graph_template_item_id = ?',
							array($sequence_counter, $graph_template_item_id));
					}

					$sequence_counter++;
				}
			}

			/* make sure to "ignore" the items that we handled above */
			if ((!isset($graph_group_array[$item['id']])) && (!isset($target_graph_group_array[$item['id']]))) {
				db_execute_prepared('UPDATE grid_elim_templates_item
					SET sequence = ?
					WHERE id = ?',
					array($sequence_counter, $item['id']));

				$sequence_counter++;
			}
		}
	}
}


/* elim_draw_graph_items_list - draws a nicely formatted list of graph items for display
     on an edit form
   @arg $item_list - an array representing the list of graph items. this array should
     come directly from the output of db_fetch_assoc()
   @arg $filename - the filename to use when referencing any external url
   @arg $url_data - any extra GET url information to pass on when referencing any
     external url
   @arg $disable_controls - whether to hide all edit/delete functionality on this form */
function elim_draw_graph_items_list($item_list, $filename, $url_data, $disable_controls) {
	global $config;

	include($config['include_path'] . '/global_arrays.php');

	print "<tr class='tableHeader'>";
		DrawMatrixHeaderItem(__('Graph Item'),'',1);
		DrawMatrixHeaderItem(__('ELIM Source'),'',1);
		DrawMatrixHeaderItem(__('Graph Item Type'),'',1);
		DrawMatrixHeaderItem(__('CF Type'),'',1);
		DrawMatrixHeaderItem(__('Alpha %'),'',1);
		DrawMatrixHeaderItem(__('Item Color'),'',4);
	print '</tr>';

	$group_counter = 0; $_graph_type_name = ''; $i = 0;

	if (cacti_sizeof($item_list)) {
		foreach ($item_list as $item) {
			/* graph grouping display logic */
			$this_row_style   = '';
			$use_custom_class = false;
			$hard_return      = '';

			if (!preg_match('/(GPRINT|TEXTALIGN|HRULE|VRULE|TICK)/', $graph_item_types[$item['graph_type_id']])) {
				$this_row_style = 'font-weight: bold;';
				$use_custom_class = true;

				if ($group_counter % 2 == 0) {
					$customClass = 'graphItem';
				} else {
					$customClass = 'graphItemAlternate';
				}

				$group_counter++;
			}

			$_graph_type_name = $graph_item_types[$item['graph_type_id']];

			/* alternating row color */
			if ($use_custom_class == false) {
				print "<tr class='tableRowGraph'>\n";
			} else {
				print "<tr class='tableRowGraph $customClass'>";
			}

			print '<td>';
			if ($disable_controls == false) { print "<a class='linkEditMain' href='" . html_escape("$filename?action=item_edit&id=" . $item['id'] . "&$url_data") . "'>"; }
			print __('Item # %d', ($i+1));
			if ($disable_controls == false) { print '</a>'; }
			print '</td>';

			if (empty($item['data_source_name'])) { $item['data_source_name'] = __('No Resource defined'); }

			switch (true) {
			case preg_match('/(TEXTALIGN)/', $_graph_type_name):
				$matrix_title = 'TEXTALIGN: ' . ucfirst($item['textalign']);
				break;
			case preg_match('/(TICK)/', $_graph_type_name):
				$matrix_title = '(' . $item['data_source_name'] . '): ' . $item['text_format'];
				break;
			case preg_match('/(AREA|STACK|GPRINT|LINE[123])/', $_graph_type_name):
				$matrix_title = '(' . $item['data_source_name'] . '): ' . $item['text_format'];
				break;
			case preg_match('/(HRULE)/', $_graph_type_name):
				$matrix_title = 'HRULE: ' . $item['value'];
				break;
			case preg_match('/(VRULE)/', $_graph_type_name):
				$matrix_title = 'VRULE: ' . $item['value'];
				break;
			case preg_match('/(COMMENT)/', $_graph_type_name):
				$matrix_title = 'COMMENT: ' . $item['text_format'];
				break;
			}

			if (preg_match('/(TEXTALIGN)/', $_graph_type_name)) {
				$hard_return = '';
			} elseif ($item['hard_return'] == 'on') {
				$hard_return = "<span style='font-weight:bold;color:#FF0000;'>&lt;HR&gt;</span>";
			}

			/* data source */
			print "<td style='$this_row_style'>" . html_escape($matrix_title) . $hard_return . '</td>';

			/* graph item type */
			print "<td style='$this_row_style'>" . $graph_item_types[$item['graph_type_id']] . '</td>';
			if (!preg_match('/(TICK|TEXTALIGN|HRULE|VRULE)/', $_graph_type_name)) {
				print "<td style='$this_row_style'>" . $consolidation_functions[$item['consolidation_function_id']] . '</td>';
			} else {
				print '<td>' . __('N/A') . '</td>';
			}

			/* alpha type */
			if (preg_match('/(AREA|STACK|TICK|LINE[123])/', $_graph_type_name)) {
				print "<td style='$this_row_style'>" . round((hexdec($item['alpha'])/255)*100) . '%</td>';
			} else {
				print "<td style='$this_row_style'></td>\n";
			}


			/* color name */
			if (!preg_match('/(TEXTALIGN)/', $_graph_type_name)) {
				print "<td style='width:1%;" . ((!empty($item['hex'])) ? 'background-color:#' . $item['hex'] . ";'" : "'") . '></td>';
				print "<td style='$this_row_style'>" . $item['hex'] . '</td>';
			} else {
				print '<td></td><td></td>';
			}

			if ($disable_controls == false) {
				print "<td style='text-align:right;padding-right:10px;'>\n";
				if ($i != cacti_sizeof($item_list)-1) {
					print "<a class='moveArrow fa fa-caret-down' title='" . __esc('Move Down'). "' href='" . html_escape("$filename?action=item_movedown&id=" . $item["id"] . "&$url_data") . "'></a>\n";
				} else {
					print "<span class='moveArrowNone'></span>\n";
				}
				if ($i > 0) {
					print "<a class='moveArrow fa fa-caret-up' title='" . __esc('Move Up') . "' href='" . html_escape("$filename?action=item_moveup&id=" . $item["id"] . "&$url_data") . "'></a>\n";
				} else {
					print "<span class='moveArrowNone'></span>\n";
				}
				print "</td>\n";

				print "<td style='text-align:right;'><a class='deleteMarker fa fa-times' title='" . __esc('Delete') . "' href='" . html_escape("$filename?action=item_remove&id=" . $item["id"] . "&$url_data") . "'></a></td>\n";
			}

			print "</tr>";

			$i++;
		}
	} else {
		print "<tr class='tableRow'><td colspan='7'><em>" . __('No Items') . "</em></td></tr>";
	}
}

/* elim_get_hash_graph_template - returns the current unique hash for a grid_elim template
   @arg $grid_elim_template_id - (int) the ID of the grid_elim template to return a hash for
   @arg $sub_type (optional) return the hash for a particlar sub-type of this type
   @returns - a 128-bit, hexadecimal hash */
function elim_get_hash_graph_template($grid_elim_template_id, $sub_type = 'grid_elim_template') {
	if ($sub_type == 'grid_elim_template') {
		$hash = db_fetch_cell_prepared('SELECT hash FROM grid_elim_templates WHERE id = ?', array($grid_elim_template_id));
	}elseif ($sub_type == 'grid_elim_template_item') {
		$hash = db_fetch_cell_prepared('SELECT hash FROM grid_elim_templates_item WHERE id = ?', array($grid_elim_template_id));
	}elseif ($sub_type == 'grid_elim_template_input') {
		$hash = db_fetch_cell_prepared('SELECT hash FROM grid_elim_template_input WHERE id = ?', array($grid_elim_template_id));
	}

	if (preg_match('/[a-fA-F0-9]{32}/', $hash)) {
		return $hash;
	}else{
		return generate_hash();
	}
}
/* elim_change_graph_template - changes the graph template for a particular graph to
	$graph_template_id
   @arg $local_graph_id - the id of the graph to change the graph template for
   @arg $graph_template_id - id the of the graph template to change to. specify '0' for no
	graph template
   @arg $intrusive - (true) if the target graph template has more or less graph items than
	the current graph, remove or add the items from the current graph to make them equal.
	(false) leave the graph item count alone */
function elim_change_graph_template($local_graph_id, $graph_template_id, $intrusive) {
	global $struct_graph, $struct_graph_item;

	/* always update tables to new graph template (or no graph template) */
	/* get information about both the graph and the graph template we're using */
	$graph_list          = db_fetch_row_prepared('SELECT * FROM graph_templates_graph WHERE local_graph_id = ?', array($local_graph_id));
	$template_graph_list = db_fetch_row_prepared('SELECT * FROM grid_elim_templates_graph WHERE graph_template_id = ?', array($graph_template_id));

	$graph_count = db_fetch_cell_prepared('SELECT local_graph_id FROM grid_elim_templates_graph_map WHERE local_graph_id = ?', array($local_graph_id));
	if (cacti_sizeof($graph_count) > 0){
		$new_save = false;
	}else{
		$new_save = true;
	}

	/* some basic field values that ALL graphs should have */
	$save['id'] = (isset($graph_list['id']) ? $graph_list['id'] : 0);
	$save['local_graph_template_graph_id'] = 0;
	$save['local_graph_id'] = $local_graph_id;
	$save['graph_template_id'] = 0;

	/* loop through the 'templated field names' to find to the rest... */
	reset($struct_graph);
	foreach ($struct_graph as $field_name => $field_array) {
		$value_type = "t_$field_name";

		if ($field_array['method'] != 'spacer') {
			if ((!empty($template_graph_list[$value_type])) && ($new_save == false)) {
				$save[$field_name] = $graph_list[$field_name];
			}else{
				$save[$field_name] = $template_graph_list[$field_name];
			}
		}
	}

	$cache_array['graph_templates_graph_id'] = sql_save($save, 'graph_templates_graph');

	db_execute_prepared('REPLACE INTO grid_elim_templates_graph_map
		VALUES (?, ?, ?, ?)',
		array($local_graph_id, $cache_array['graph_templates_graph_id'], $graph_template_id, $template_graph_list['id']));

	$graph_items_list = db_fetch_assoc_prepared('SELECT *
		FROM graph_templates_item
		WHERE local_graph_id = ?
		ORDER BY sequence',
		array($local_graph_id));

	$template_items_list = db_fetch_assoc_prepared('SELECT *
		FROM grid_elim_templates_item
		WHERE graph_template_id = ?
		ORDER BY sequence',
		array($graph_template_id));

	$graph_template_inputs = db_fetch_assoc_prepared('SELECT
		graph_template_input.column_name,
		graph_template_input_defs.graph_template_item_id
		FROM (graph_template_input,graph_template_input_defs)
		WHERE graph_template_input.id=graph_template_input_defs.graph_template_input_id
		AND graph_template_input.graph_template_id = ?',
		array($graph_template_id));

	$k=0;
	if (cacti_sizeof($template_items_list)) {
		foreach ($template_items_list as $template_item) {
			unset($save);
			reset($struct_graph_item);

			$save['local_graph_template_item_id'] = 0;
			$save['local_graph_id'] = $local_graph_id;
			$save['graph_template_id'] = 0;

			if (isset($graph_items_list[$k])) {
				/* graph item at this position, 'mesh' it in */
				$save['id'] = $graph_items_list[$k]['id'];

				/* make a first pass filling in ALL values from template */
				foreach ($struct_graph_item as $field_name => $field_array) {
					$save[$field_name] = $template_item[$field_name];
				}
			}else{
				/* no graph item at this position, tack it on */
				$save['id'] = 0;
				$save['task_item_id'] = 0;

				if ($intrusive == true) {
					foreach ($struct_graph_item as $field_name => $field_array) {
						$save[$field_name] = $template_item[$field_name];
					}
				}else{
					unset($save);
				}
			}

			if (isset($save)) {
				$cache_array['graph_templates_item_id'] = sql_save($save, 'graph_templates_item');

				db_execute_prepared('REPLACE INTO grid_elim_templates_item_map
					VALUES (?, ?, ?, ?)',
					array($local_graph_id, $cache_array['graph_templates_item_id'], $graph_template_id, $template_items_list[$k]['id']));
			}

			$k++;
		}
	}

	/* if there are more graph items then there are items in the template, delete the difference */
	if ((cacti_sizeof($graph_items_list) > cacti_sizeof($template_items_list)) && ($intrusive == true)) {
		for ($i=(cacti_sizeof($graph_items_list) - (cacti_sizeof($graph_items_list) - cacti_sizeof($template_items_list))); ($i < count($graph_items_list)); $i++) {
			db_execute_prepared('DELETE FROM graph_templates_item
				WHERE id = ?',
				array($graph_items_list[$i]['id']));
		}
	}

	return true;
}

function elim_get_reource_option_name($resource_option_id) {
	switch($resource_option_id){
		case '1': //Total
			$resource_option = 'Total';
			break;
		case '2': //Avail
			$resource_option = 'Avail';
			break;
		case '3': //Reserved
			$resource_option = 'Reserved';
			break;
		default:
			$resource_option = '';
			break;
		}
		return $resource_option;
}

function elim_get_matching_hosts($elim_instance) {
	global $cnn_id;

	if (cacti_sizeof($elim_instance) <= 0) {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM){
			cacti_log("INFO: The ELIM Instance '" . $elim_instance['id'] . ': ' . $elim_instance['name'] . "' Not Found");
		}
		return;
	}

	/* Not deal with the host which base on disabled cluster */
	switch($elim_instance['hosttype_option']){
		case '1'://hostType
			$sql_where = ' AND (grid_hostinfo.hostType RLIKE ' . db_qstr($elim_instance['hosttype_value']) . ')';
			$get_hosts_sql = 'SELECT distinct host.id,host.hostname
				FROM host
				LEFT JOIN grid_clusters
				ON grid_clusters.clusterid=host.clusterid
				INNER JOIN grid_hostinfo
				ON grid_hostinfo.clusterid=host.clusterid
				AND grid_hostinfo.host=host.hostname
				WHERE grid_clusters.disabled=""
				AND grid_clusters.clusterid=' . $elim_instance['clusterid'] . $sql_where;
			break;
		case '2'://hostModel
			$sql_where = ' AND (grid_hostinfo.hostModel RLIKE ' . db_qstr($elim_instance['hosttype_value']) . ')';
			$get_hosts_sql = 'SELECT distinct host.id, host.hostname
				FROM host
				LEFT JOIN grid_clusters
				ON grid_clusters.clusterid=host.clusterid
				INNER JOIN grid_hostinfo
				ON grid_hostinfo.clusterid=host.clusterid
				AND grid_hostinfo.host=host.hostname
				WHERE grid_clusters.disabled=""
				AND grid_clusters.clusterid=' . $elim_instance['clusterid'] .  $sql_where;
			break;
		case '3':
			$sql_where = ' AND (grid_hostgroups.groupName RLIKE ' . db_qstr($elim_instance['hosttype_value']) . ')';
			$get_hosts_sql='SELECT distinct host.id,host.hostname
				FROM host
				LEFT JOIN grid_clusters
				ON grid_clusters.clusterid=host.clusterid
				INNER JOIN grid_hostgroups
				ON grid_hostgroups.clusterid=host.clusterid
				AND grid_hostgroups.host=host.hostname
				WHERE grid_clusters.disabled=""
				AND grid_clusters.clusterid=' . $elim_instance['clusterid'] .  $sql_where;
			break;
		default:
			return;
	}

	return db_fetch_assoc($get_hosts_sql);
}

function elim_check_add_graph($elim_instance_id){
	$elim_instance = db_fetch_row_prepared('SELECT *
		FROM grid_elim_template_instances
		WHERE id = ?',
		array($elim_instance_id));

	$hosts = elim_get_matching_hosts($elim_instance);

	if (cacti_sizeof($hosts) > 0) {
		foreach ($hosts as $host) {
			$snmp_query_array = '';
			$suggested_values_array = array();
			$found = db_fetch_row_prepared('SELECT grid_elim_instance_graphs.local_graph_id
				FROM grid_elim_instance_graphs
				JOIN graph_local
				ON grid_elim_instance_graphs.local_graph_id=graph_local.id
				LEFT JOIN grid_elim_template_instances
				ON grid_elim_template_instances.id=grid_elim_instance_graphs.grid_elim_template_instance_id
				WHERE grid_elim_template_instances.grid_elim_template_id = ?
				AND graph_local.host_id = ?',
				array($elim_instance['grid_elim_template_id'], $host['id']));

			if ($found){
				$is_this_instance = db_fetch_row_prepared('SELECT local_graph_id
					FROM grid_elim_instance_graphs
					WHERE local_graph_id = ?
					AND grid_elim_template_instance_id = ?',
					array($found['local_graph_id'], $elim_instance['id']));

				if(!$is_this_instance){
					/*set the local_graph_id belong to this instance. */
					db_execute_prepared('INSERT INTO grid_elim_instance_graphs
						VALUES (?, ?)',
						array($elim_instance['id'], $found['local_graph_id']));
				}
			}else{
				elim_create_complete_graph_from_template($elim_instance, $elim_instance['grid_elim_template_id'], $host['id'], $snmp_query_array, $suggested_values_array);

				if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM){
					cacti_log('INFO: ELIM Template ID : '.$elim_instance['grid_elim_template_id'].' has been added to '.$host['hostname']);
				}
			}
		}
	}
}

function elim_create_complete_graph_from_template($elim_instance, $graph_template_id, $host_id, $snmp_query_array, &$suggested_values_array) {
	global $config;
	global $elim_data_input_hash;

	include_once($config['library_path'] . '/data_query.php');

	$elim_data_input_id= db_fetch_cell_prepared('SELECT id
		FROM data_input
		WHERE hash= ?',
		array($elim_data_input_hash));

	if (empty($elim_data_input_id)) {
		cacti_log('ERROR: The Data Input Method of ELIM cannot be found, so the ELIM graphs are not created.');
		return ;
	}

	/* create the graph */
	$save['id'] = 0;
	$save['graph_template_id'] = 0;
	$save['host_id'] = $host_id;

	$cache_array['local_graph_id'] = sql_save($save, 'graph_local');

	db_execute_prepared('REPLACE INTO grid_elim_instance_graphs
		VALUES (?, ?)',
		array($elim_instance['id'], $cache_array['local_graph_id']));

	elim_change_graph_template($cache_array['local_graph_id'], $graph_template_id, true);

	update_graph_title_cache($cache_array['local_graph_id']);

	elim_push_out_graph_input($cache_array['local_graph_id'], $graph_template_id, $elim_instance ) ;

}

function elim_push_out_graph_input($local_graph_id, $graph_template_id, $elim_instance = array()) {
	global $elim_data_input_hash;

	$elim_data_input_id = db_fetch_cell_prepared('SELECT id
		FROM data_input
		WHERE hash = ?',
		array($elim_data_input_hash));

	$host_id = db_fetch_cell_prepared('SELECT host_id
		FROM graph_local
		WHERE id = ?',
		array($local_graph_id));

	$template_item_list = db_fetch_assoc_prepared('SELECT id, graph_template_id, resource_name, resource_option
		FROM grid_elim_templates_item
		WHERE grid_elim_templates_item.graph_template_id = ?
		AND resource_name IS NOT NULL',
		array($graph_template_id));

	if (cacti_sizeof($template_item_list) > 0) {
	foreach ($template_item_list as $template_item) {
		if( empty($template_item['resource_option']) || empty($template_item['resource_option']) ){
			continue;
		}

		$resource_option = elim_get_reource_option_name($template_item['resource_option']);

		$found = db_fetch_row_prepared('SELECT local_data_id
			FROM data_local
			JOIN data_template_data
			ON data_template_data.local_data_id= data_local.id
			JOIN data_input_data
			ON data_input_data.data_template_data_id=data_template_data.id
			WHERE data_local.host_id = ?
			AND data_template_data.data_input_id = ?
			AND data_input_data.value = ?',
			array($host_id, $elim_data_input_id, $template_item['resource_name']));

		if ($found){
			/* choose the old data source */
			$data_template_rrd_id=db_fetch_cell_prepared('SELECT id
				FROM data_template_rrd
				WHERE local_data_id = ?
				AND data_source_name = ?',
				array($found['local_data_id'], $resource_option));

			$graph_template_item_id = db_fetch_cell_prepared('SELECT graph_templates_item_id
				FROM grid_elim_templates_item_map
				WHERE grid_elim_templates_item_id = ?
				AND local_graph_id = ?',
				array($template_item['id'], $local_graph_id));

			if (!empty($data_template_rrd_id)) {
				db_execute_prepared("UPDATE graph_templates_item
					SET task_item_id = ?
					WHERE id= ?",
					array($data_template_rrd_id, $graph_template_item_id));
			}
		}else{
			/* create new data source of the ELIM */
			unset($save);
			$save['id'] = 0;
			$save['data_template_id'] = 0;
			$save['host_id'] = $host_id;
			$cache_array['local_data_id'] = sql_save($save, 'data_local');
			if(empty($elim_instance)) {
				$data_source_profile_id = db_fetch_cell_prepared('SELECT data_source_profile_id
					FROM grid_elim_template_instances
					WHERE id IN (SELECT grid_elim_template_instance_id FROM grid_elim_instance_graphs WHERE local_graph_id = ?);', array($local_graph_id));
				if(!empty($data_source_profile_id)) {
					$elim_instance['data_source_profile_id'] = $data_source_profile_id;
				}
			}

			$data_template_rrd_id = elim_create_data_source($cache_array['local_data_id'], $host_id, $template_item['resource_name'], $resource_option, $elim_instance);

			$graph_template_item_id = db_fetch_cell_prepared('SELECT graph_templates_item_id
				FROM grid_elim_templates_item_map
				WHERE grid_elim_templates_item_id = ?
				AND local_graph_id = ?',
				array($template_item['id'], $local_graph_id));

			if (!empty($data_template_rrd_id)) {
				db_execute_prepared('UPDATE graph_templates_item
					SET task_item_id = ?
					WHERE id = ?',
					array($data_template_rrd_id, $graph_template_item_id));

				update_poller_cache($cache_array['local_data_id'], true);
			}
		}
	}
	}
}
function elim_create_data_source($local_data_id, $host_id, $resource_name, $resource_option, $elim_instance) {
	global $elim_data_input_hash;

	$elim_data_input_id= db_fetch_cell_prepared('SELECT id
		FROM data_input
		WHERE hash = ?',
		array($elim_data_input_hash));

	if (empty($elim_data_input_id)) {
		cacti_log('WARNING: Data Input Method of ELIM cannot be found, so data source cannot be created for this resource: ' . $resource_name);
		return 0;
	}
	$host = db_fetch_row_prepared('SELECT clusterid, hostname
		FROM host
		WHERE id= ?',
		array($host_id));

	if(empty($host)){
		cacti_log('WARNING: host: ' . $host_id . ' cannot be found, so data source cannot be created for this resource: ' . $resource_name);
		return 0;
	}

	$save['id'] = 0;
	$save['local_data_template_data_id'] = 0;
	$save['local_data_id'] = $local_data_id;
	$save['data_template_id'] = 0;
	$save['data_input_id'] = $elim_data_input_id;
	$save['name'] = 'GRID - ' . $host['hostname'] . ' - ' . $resource_name;
	$save['name_cache'] = $save['name'];

	//$save["data_source_path"] = "<path_rra>/" . $host["hostname"] . "_" . $resource_name. "_" . $local_data_id . ".rrd"; //fix rtc64199 Structured Path. generate_data_source_path called if null.

	$save['active'] = 'on';
	$save['rrd_step'] = read_config_option('poller_interval');
	if(!empty($elim_instance)){
		$save['data_source_profile_id'] = $elim_instance['data_source_profile_id'];
	}
	$cache_array['data_template_data_id'] = sql_save($save, 'data_template_data');

	$data_input_fields = db_fetch_assoc_prepared('SELECT id, data_name
		FROM data_input_fields
		WHERE data_input_id = ?
		AND input_output = "in"',
		array($elim_data_input_id));

	if (empty($data_input_fields)){
		cacti_log('WARNING: Resource: ' . $resource_name . ' cannot be found, so failed to create data source for this resource on host: ' . $host['hostname']);
		return 0;
	}

	$data_input_fields = array_rekey($data_input_fields, 'data_name', 'id');// in

	if ($data_input_fields['clusterid'] && $data_input_fields['host'] && $data_input_fields['resource_name']) {
		unset($save);
		$save['data_input_field_id'] = $data_input_fields['clusterid'];;
		$save['data_template_data_id'] = $cache_array['data_template_data_id'];
		$save['t_value'] = '';
		$save['value'] = $host['clusterid'];

		sql_save($save, 'data_input_data');

		unset($save);
		$save['data_input_field_id'] = $data_input_fields['host'];;
		$save['data_template_data_id'] = $cache_array['data_template_data_id'];
		$save['t_value'] = '';
		$save['value'] = $host['hostname'];
		sql_save($save, 'data_input_data');

		unset($save);
		$save['data_input_field_id'] = $data_input_fields['resource_name'];
		$save['data_template_data_id'] = $cache_array['data_template_data_id'];
		$save['t_value'] = '';
		$save['value'] = $resource_name;
		sql_save($save, 'data_input_data');
	}

	$data_input_fields = db_fetch_assoc_prepared('SELECT id,data_name
		FROM data_input_fields
		WHERE data_input_id = ?
		AND input_output ="out"',
		array($elim_data_input_id));

	if (empty($data_input_fields)) {
		cacti_log('WARNING: Resource: ' . $resource_name . ' cannot be found, so failed to create data source for this resource on host: ' . $host['hostname']);
		return 0;
	}

	$data_input_fields = array_rekey($data_input_fields, 'data_name', 'id');

	unset($save);
	$save['id'] = 0;
	$save['local_data_template_rrd_id'] = 0;
	$save['local_data_id']              = $local_data_id;
	$save['data_template_id']           = 0;
	$save['rrd_maximum']                = 0;
	$save['rrd_minimum']                = 0;
	$save['rrd_heartbeat']              = read_config_option('poller_interval') * 2;
	$save['data_source_type_id']        = 1;
	$save['data_source_name']           = 'Avail';

	/*'totalValue' for 'Avail'*/

	$save['data_input_field_id'] = $data_input_fields['totalValue'];
	$cache_array['Avail']['data_template_rrd_id'] = sql_save($save, 'data_template_rrd');

	unset($save);
	$save['id'] = 0;
	$save['local_data_template_rrd_id'] = 0;
	$save['local_data_id']              = $local_data_id;
	$save['data_template_id']           = 0;
	$save['rrd_maximum']                = 0;
	$save['rrd_minimum']                = 0;
	$save['rrd_heartbeat']              = read_config_option('poller_interval') * 2;
	$save['data_source_type_id']        = 1;
	$save['data_source_name']           = 'Reserved';
	$save['data_input_field_id']        = $data_input_fields['reservedValue'];
	$cache_array['Reserved']['data_template_rrd_id'] = sql_save($save, 'data_template_rrd');

	unset($save);
	$save['id'] = 0;
	$save['local_data_template_rrd_id'] = 0;
	$save['local_data_id']              = $local_data_id;
	$save['data_template_id']           = 0;
	$save['rrd_maximum']                = 0;
	$save['rrd_minimum']                = 0;
	$save['rrd_heartbeat']              = read_config_option('poller_interval') * 2;
	$save['data_source_type_id']        = 1;
	$save['data_source_name']           = 'Total';
	/*'value' for 'Total'*/
	$save['data_input_field_id']        = $data_input_fields['value'];
	$cache_array['Total']['data_template_rrd_id'] = sql_save($save, 'data_template_rrd');
/*
	db_execute("REPLACE INTO data_template_data_rra VALUES ( "
		. $cache_array['data_template_data_id'] . ",1),("
		. $cache_array['data_template_data_id'] . ",2),("
		. $cache_array['data_template_data_id'] . ",3),("
		. $cache_array['data_template_data_id'] . ",4)
	");
*/
	if($resource_option){
		return $cache_array[$resource_option]['data_template_rrd_id'];
	}

	return 0;
}

/* array_remove - returns an array containing each value in the 'before' array and NOT in the 'minus' array.
   @arg $before - the array which want to be minus.
   @arg $minus -  the array minus.
   @returns - (array) an array containing each value in the 'before' array and NOT in the 'minus' array.
   And the index of the returns is begin from 0 */
function array_remove($befores, $minus) {
	$array_del = array();

	if (empty($befores)) {
		return $befores;
	}

	if (empty($minus)) {
		return $befores;
	}

	$i=0;
	if (cacti_sizeof($befores)) {
		foreach ($befores as $before) {
			$found = 0;

			foreach ($minus as $minu) {
				if($minu == $before) {
					$found = 1;
					break;
				}
			}

			if ($found == 0) {
				$array_del[$i]=$before;
				$i++;
			}
		}
	}

	return $array_del;
}

