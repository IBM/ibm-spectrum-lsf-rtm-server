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
include('./plugins/disku/include/disku_functions.php');
include_once('./lib/rtm_functions.php');

$extension_actions = array(
	1 => __('Monitor', 'disku'),
	2 => __('Unmonitor', 'disku'),
	3 => __('Delete', 'disku'),
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
		extension_edit();
		bottom_footer();

		break;
	default:
		top_header();
		disku_extenreg();
		bottom_footer();

		break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save($batch = false) {
	global $cnn_id;

	$registered_id=0;
	$emid=0;
	if ((isset_request_var('save_component')) && (isempty_request_var('add_dq_y'))) {
		get_request_var('id');
		input_validate_input_number(get_request_var('application_id'));
		if (!isempty_request_var('id')) {
			$parts = explode(':', get_request_var('id'));
			if (is_numeric($parts[0])) {
				$emid=$parts[0];
			}
		}
		$save['extension'] = form_input_validate(get_request_var('extension'), 'extension', '^[A-Za-z0-9\._\\\@\ -]+$', false, 3);
		$save['notes']     = form_input_validate(get_request_var('notes'), 'notes', '^[A-Za-z0-9\._\\\@\ -\(\)]+$', false, 3);

		form_input_validate(get_request_var('application_id'), 'application_id', '^[0-9]+$', false, 3);
		form_input_validate(get_request_var('app_level_notes'), 'app_level_notes', '^[A-Za-z0-9\._\\\@\ -]+$', true, 3);

		$ext               = db_qstr($save['extension']);
		$notes             = db_qstr($save['notes']);
		$app_level_notes   = db_qstr(get_request_var('app_level_notes'));

		if (!isset_request_var('rid') || isempty_request_var('rid')) { //new
			$registered_id=db_fetch_cell_prepared('SELECT id
				FROM disku_extension_registry
				WHERE extension= ?',
				array($ext));

			if (!empty($registered_id)) {
				if (isset_request_var('application_id') && !isempty_request_var('application_id')) {
					$mapped_same_id = db_fetch_cell_prepared('SELECT id
						FROM disku_extension_monitors
						WHERE application_id = ?
						AND extension = ?',
						array(get_request_var('application_id'), $ext));

					if (!empty($mapped_same_id)) {
						raise_message(302);
					} else { //add a exist Extention
						$mapped_flag=db_fetch_cell_prepared("SELECT count(*) FROM disku_extension_monitors WHERE extension=?", array($ext));
						if (!empty($mapped_flag)) {
							raise_message(303);
						}
					}
				} else{
					raise_message(301);
				}
			}
		} else{
			if (isset_request_var('application_id') && !isempty_request_var('application_id')) {
				if (!empty($emid)) { //not new, edit
					$mapped_same_id = db_fetch_cell_prepared("SELECT id
						FROM disku_extension_monitors
						WHERE application_id=?
						AND extension=?", array(get_request_var('application_id'), $ext));

					if (!empty($mapped_same_id) && $emid!=$mapped_same_id) { //map to another exist application
						raise_message(302);
					}
				}
			}
		}

		if (!is_error_message()) {
			if (!isset_request_var('rid') || isempty_request_var('rid')) { //new
				if (empty($registered_id)) {
					$rid = sql_save($save, 'disku_extension_registry');
				} else{ //add a exist Extention
					db_execute_prepared("UPDATE disku_extension_registry SET monitor='on', notes=? WHERE id=?", array($notes, $registered_id));
					$rid = $registered_id;
				}

				$map_save['rid'] = $rid;
			} else{
				db_execute_prepared("UPDATE disku_extension_registry SET monitor='on', notes=? WHERE id=?", array($notes, get_request_var('rid')));
				$map_save['rid'] = get_request_var('rid');
			}

			if (isset_request_var('application_id') && !isempty_request_var('application_id')) {
				if (!empty($emid)) { //not new, edit
					db_execute_prepared("UPDATE disku_extension_monitors SET application_id=?, notes=? WHERE id=?", array(get_request_var('application_id'), $app_level_notes, $emid));
				} else{ //new
					$mapped_same_id = db_fetch_cell_prepared("SELECT id FROM disku_extension_monitors WHERE application_id=? AND extension=?", array(get_request_var('application_id'), $ext));
					if (empty($mapped_same_id)) {
						$map_save['extension']      = $save['extension'];
						$map_save['application_id'] = get_request_var('application_id');
						$map_save['notes']          = get_request_var('app_level_notes');
						sql_save($map_save, 'disku_extension_monitors');
					} else{
						db_execute_prepared("UPDATE disku_extension_monitors SET notes=? WHERE id=?", array($app_level_notes, $mapped_same_id));
					}
				}
			} elseif (isset_request_var('application_id') && isset_request_var('_application_id')) {//un-map
				if (get_request_var('application_id')==0 && get_request_var('application_id')!= get_request_var('_application_id')) {
					db_execute_prepared("DELETE FROM disku_extension_monitors WHERE application_id=? AND extension=?", array(get_request_var('_application_id'), $ext));
				}
			}

			$graphs_exts = array();
			$graphs_exts[] = $save['extension'];
			create_graphs_for_extension($graphs_exts);
			raise_message(1);
		}

		if (!$batch) {
			if (is_error_message()) {
				/*if (isempty_request_var('id')) {
					header('Location: disku_extenreg.php?action=edit');
				} else{*/
					header('Location: disku_extenreg.php?action=edit&id=' . (!isempty_request_var('id') ? get_request_var('id') : ''). (!isempty_request_var('application_id') ? "&application_id=". get_request_var('application_id') : ''));
				//}
			} else{
				header("Location: disku_extenreg.php");
			}
		}
	}
}

function disku_data_query_update_host_cache_from_buffer($host_id, $snmp_query_id, &$output_array) {
	/* setup the database call */
	$sql_prefix   = "INSERT INTO host_snmp_cache (host_id, snmp_query_id, field_name, field_value, snmp_index, oid, present) VALUES";
	$sql_suffix   = " ON DUPLICATE KEY UPDATE field_value=VALUES(field_value), oid=VALUES(oid), present=VALUES(present)";

	/* use a reasonable insert buffer, the default is 1MByte */
	$max_packet   = 256000;

	/* setup somme defaults */
	$overhead     = strlen($sql_prefix) + strlen($sql_suffix);
	$buf_len      = 0;
	$buf_count    = 0;
	$buffer       = "";

	foreach($output_array as $record) {
		if ($buf_count == 0) {
			$delim = " ";
		} else {
			$delim = ", ";
		}

		$buffer .= $delim . $record;

		$buf_len += strlen($record);

		if (($overhead + $buf_len) > ($max_packet - 1024)) {
			db_execute($sql_prefix . $buffer . $sql_suffix);

			$buffer    = "";
			$buf_len   = 0;
			$buf_count = 0;
		} else {
			$buf_count++;
		}
	}

	if ($buf_count > 0) {
		db_execute($sql_prefix . $buffer . $sql_suffix);
	}
}
function remove_graphs_for_extensions($extensions) {
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
		WHERE hash='439617ebef50ab9be20c168f882d187e'");

	if (!empty($dqid) && !empty($hostid)) {
		$query_field_name = db_fetch_cell_prepared("SELECT sort_field FROM host_snmp_query WHERE host_id=? AND snmp_query_id=?", array($hostid, $dqid));

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
					AND snmp_index IN ('" . implode("','",str_replace('!', '.', $extensions)) . "')", array($hostid, $snmp_query_type['graph_template_id'], $dqid));
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

function create_graphs_for_extension($exts) {
	global  $config;

	include_once($config["base_path"]."/lib/data_query.php");
	$hostid = db_fetch_cell("SELECT h.id
		FROM host AS h
		INNER JOIN host_template AS ht
		ON h.host_template_id=ht.id
		WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='439617ebef50ab9be20c168f882d187e'");

	if (!empty($dqid) && !empty($hostid)) {
		//$query_field_name = get_disku_query_field_name($host_id, $snmp_query['id']);

		run_data_query($hostid, $dqid);
		$query_field_name = db_fetch_cell_prepared("SELECT sort_field FROM host_snmp_query WHERE host_id=? AND snmp_query_id=?", array($hostid, $dqid));
		/*replace run_data_query($hostid, $dqid);
		$output_array = array();
		$output_array[] = data_query_format_record($hostid, $dqid, $query_field_name, $ext, $ext, '');
		if (cacti_sizeof($output_array)) {
			disku_data_query_update_host_cache_from_buffer($hostid, $dqid, $output_array);
		}
		//replace run_data_query($hostid, $dqid); */

		$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
			FROM snmp_query_graph
			WHERE snmp_query_id=?
			ORDER BY id", array($dqid));

		if (cacti_sizeof($snmp_query_types)) {
			foreach ($snmp_query_types as $snmp_query_type) {
				//add_disku_extension_graphs($hostid, $snmp_query_type['graph_template_id'], $dqid, $snmp_query_type['id'], $query_field_name, $snmp_query_type['name'],array($ext));
				add_disku_extension_graphs($hostid, $snmp_query_type['graph_template_id'], $dqid, $snmp_query_type['id'], $query_field_name, $snmp_query_type['name'],$exts);

			}
		}
	}
}

function add_disku_extension_graphs($host_id, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $snmp_query_name='', $items) {
	global $php_bin, $path_web, $path_disku, $graphs, $debug;
	$php_bin    = read_config_option('path_php_binary');
	$path_web   = read_config_option('path_webroot');

	if (cacti_sizeof($items)) {
		foreach($items as $item) {
			$item = str_replace('!', '.', $item);
			/* see if graph exists */
			$exists = db_fetch_cell_prepared("SELECT count(*)
				FROM graph_local
				WHERE host_id=?
				AND graph_template_id=?
				AND snmp_query_id=?
				AND snmp_index=?",
				array($host_id, $graph_template_id, $snmp_query_id, $item));

			if (!$exists) {
				if (empty($snmp_query_type_id)||empty($snmp_query_id)||empty($snmp_field_name)||empty($item)) {
					cacti_log("Warning: Add graph for empty item:$snmp_query_type_id, $snmp_query_id, $snmp_field_name, $item");
				} else{
					$command = "$php_bin -q $path_web/cli/add_graphs.php" .
					" --graph-template-id=$graph_template_id --graph-type=ds"     .
					" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $host_id .
					" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
					" --snmp-value='$item'";

					passthru($command);
				}
			} else {
				cacti_log("NOTE: Already Exists item: $item for Query Type ID: $snmp_query_type_id");
			}
		}
	}
}
/* ------------------------
	The "actions" function
   ------------------------ */

function form_actions() {
	global $config, $extension_actions;

	input_validate_input_regex(get_request_var('drp_action'), "^([a-zA-Z0-9_]+)$");

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

		if ($selected_items != false) {
			if (get_request_var('drp_action') == "1") { /* monitor */
				api_disku_extension_monitor($selected_items);
			} elseif (get_request_var('drp_action') == "2") { /* unmonitor */
				api_disku_extension_unmonitor($selected_items);
			} elseif (get_request_var('drp_action') == "3") { /* delete */
				api_disku_extension_delete($selected_items);
			}
		}

		header("Location: disku_extenreg.php");
		exit;
	}

	/* setup some variables */
	$extension_list = ""; $extension_array = array(); $extension_info = "";
	$extensions = array();

	/* loop through each of the Scanner Path(s) selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match("/^chk_([^.]+)$/", $var, $matches)) {
			$parts = explode(":", $matches[1]);
			if (is_numeric($parts[0])) {
				/* ================= input validation ================= */
				input_validate_input_number($parts[0]);
				/* ==================================================== */
				$extension_info = db_fetch_cell_prepared("SELECT CONCAT_WS('', application, ' (', r.extension, ') - ', r.notes,' - ', em.notes)
					FROM disku_extension_monitors AS em
					INNER JOIN disku_applications AS a
					ON em.application_id=a.id
					INNER JOIN disku_extension_registry AS r
					ON em.rid=r.id
					WHERE em.id=?", array($parts[0]));

				$monitor_row  = db_fetch_row_prepared("SELECT extension, application_id, notes
					FROM disku_extension_monitors
					WHERE id=?", array($parts[0]));

				$app_id = $monitor_row['application_id'];
				$app_level_notes = $monitor_row['notes'];

				$r_row  = db_fetch_row_prepared("SELECT extension, notes
					FROM disku_extension_registry
					WHERE id=?", array($parts[1]));

				$extensions[] = $r_row['extension'];
				$notes = $r_row['notes'];
			} else{
				$extension_info = db_fetch_cell_prepared("SELECT CONCAT_WS('', 'Undefined', ' (', extension, ') - ', notes,'')
					FROM disku_extension_registry
					WHERE id=?", array($parts[2]));

				$app_id = 0;
				$app_level_notes = "";

				$r_row  = db_fetch_row_prepared("SELECT extension, notes
					FROM disku_extension_registry
					WHERE id=?", array($parts[2]));

				$extensions[] = $r_row['extension'];
				if (strchr($parts[1], '_')) { //Fix 102507, PHP auto-replaces '.' with '_'
					$parts[1]= $r_row['extension'];
					$matches[1] = "new:". $parts[1] . ":". $parts[2];
				}
				$notes = $r_row['notes'];
				if (empty($notes)) {
					$notes = 'New Registry for Extension (' . $parts[1] . ')';
				}
			}

			$extension_list .= "<li>" . html_escape($extension_info) . "</li>";
			$extension_array[] = $matches[1];
		}
	}

	top_header();

	form_start('disku_extenreg.php', 'chk');

	html_start_box($extension_actions[get_request_var('drp_action')], "60%", '', "3", "center", "");

	if (cacti_sizeof($extension_array)) {
		if (get_request_var('drp_action') == "1") { /* monitor */
			print "<tr>
				<td colspan='2' class='textArea'" . ">
					<p>Are you sure you want to monitor the following extension(s)?</p>
					<p><ul>$extension_list</ul></p>";

			print "</td></tr>";
			print "<tr id='row_notes'><td class='textArea' width='20%'>Extension Description:</td><td class='textArea'>";
			if (cacti_sizeof($extension_array) ==1) {
				form_text_box("notes", $notes, $notes, "128", "70", "text");
			} else{
				print "Use orignal Description for each Extension in batch monitor mode.</td><td class='textArea'>";
			}
			print "</td></tr>";
			print "<tr><td class='textArea' width='20%'>Application:</td><td class='textArea'>";
			form_dropdown("application_id",array_merge(array(0 => array("id" => "0", "name" => "None")),db_fetch_assoc("SELECT id, application AS name FROM disku_applications ORDER BY application")),"name","id","","",$app_id);
			print "</td></tr>";
			print "<tr id='row_app_level_notes'><td class='textArea' width='30%'>Application Level Extension Description:</td><td class='textArea'>";
			form_text_box("app_level_notes", $app_level_notes, $app_level_notes, "128", "70", "text");
			print "</td></tr>";

			$title = "Monitor Extension(s)";
		} elseif (get_request_var('drp_action') == "2") { /* unmonitor */
			$graphs = array();
			$hostid = db_fetch_cell("SELECT h.id
				FROM host AS h
				INNER JOIN host_template AS ht
				ON h.host_template_id=ht.id
				WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");
			$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='439617ebef50ab9be20c168f882d187e'");
			if (!empty($dqid) && !empty($hostid)) {
				$query_field_name = db_fetch_cell_prepared("SELECT sort_field FROM host_snmp_query WHERE host_id=? AND snmp_query_id=?", array($hostid, $dqid));
				$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
					FROM snmp_query_graph
					WHERE snmp_query_id=?
					ORDER BY id", array($dqid));
				if (cacti_sizeof($snmp_query_types)) {
					foreach ($snmp_query_types as $snmp_query_type) {
						$a_extension_graphs = db_fetch_assoc_prepared("SELECT graph_templates_graph.title_cache
							FROM graph_local
							JOIN graph_templates_graph
							ON graph_local.id = graph_templates_graph.local_graph_id
							WHERE host_id=?
							AND graph_local.graph_template_id=?
							AND snmp_query_id=?
							AND snmp_index IN ('" . implode("','",str_replace('!', '.', $extensions)) . "')",
							array($hostid, $snmp_query_type['graph_template_id'], $dqid));
						$graphs = array_merge($graphs, $a_extension_graphs);
					}
				}
			}
			print "	<tr>
				<td class='textArea'>
					<p>Are you sure you want to unmonitor the following extension(s)?</p>
					<p><ul>$extension_list</ul></p>";
						if (cacti_sizeof($graphs) > 0) {
							print "<tr><td class='textArea'><p class='textArea'>The following graphs are related with these extension(s) will be deleted.</p>";

							print "<ul>";
							foreach ($graphs as $graph) {
								print "<li>" . html_escape($graph["title_cache"]) . "</li>";
							}
							print "</ul>";

							print "<br>";
							print "</td></tr>";
						}
			print "</td></tr>";

			$title = "Unmonitor Extension(s)";
		} elseif (get_request_var('drp_action') == "3") { /* delete */
			//$applications=array_rekey(db_fetch_assoc("SELECT DISTINCT application FROM disku_applications WHERE id IN ('" . implode("','",$app_array) . "')"), 'application', 'application');
			$graphs = array();

			$hostid = db_fetch_cell("SELECT h.id
				FROM host AS h
				INNER JOIN host_template AS ht
				ON h.host_template_id=ht.id
				WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");

			$dqid = db_fetch_cell("SELECT id
				FROM snmp_query
				WHERE hash='439617ebef50ab9be20c168f882d187e'");

			if (!empty($dqid) && !empty($hostid)) {
				$query_field_name = db_fetch_cell_prepared("SELECT sort_field
					FROM host_snmp_query
					WHERE host_id=?
					AND snmp_query_id=?", array($hostid, $dqid));

				$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
					FROM snmp_query_graph
					WHERE snmp_query_id=?
					ORDER BY id", array($dqid));

				if (cacti_sizeof($snmp_query_types)) {
					foreach ($snmp_query_types as $snmp_query_type) {
						$a_extension_graphs = db_fetch_assoc_prepared("SELECT graph_templates_graph.title_cache
							FROM graph_local
							JOIN graph_templates_graph
							ON graph_local.id = graph_templates_graph.local_graph_id
							WHERE host_id=?
							AND graph_local.graph_template_id=?
							AND snmp_query_id=?
							AND snmp_index IN ('" . implode("','",str_replace('!', '.', $extensions)) . "')",
							array($hostid, $snmp_query_type['graph_template_id'], $dqid));
						$graphs = array_merge($graphs, $a_extension_graphs);
					}
				}
			}

			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to delete the following extension(s)?</p>
					<p><ul>$extension_list</ul></p>";

					if (cacti_sizeof($graphs) > 0) {
						print "<tr><td class='textArea'><p class='textArea'>The following graphs are related with these extension(s):</p>";

						print '<ul>';
						foreach ($graphs as $graph) {
							print '<li>' . html_escape($graph['title_cache']) . '</li>';
						}
						print '</ul>';

						print '<br>';
						form_radio_button('delete_type', '3', '1', 'Leave the Graph(s) and related Data source(s) untouched.', '1'); print '<br>';
						form_radio_button('delete_type', '2', '2', 'Delete all Graph(s) and related Data source(s) that reference these extension(s).', '1'); print '<br>';
						print '</td></tr>';
					}

			print '</td></tr>';

			$title = __esc('Delete Extension(s)', 'disku');
		}

		$save_html = "<input type='button' value='" . __esc('Cancel', 'disku') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'disku') . "' title='$title'>";
	} else{
		raise_message(40, __('You must select at least one Extension.', 'disku'), MESSAGE_LEVEL_ERROR);
		header('Location: disku_extenreg.php?header=false');
		exit;
	}

	print " <tr>
		<td colspan='2' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($extension_array) ? serialize($extension_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	?>
	<script type='text/javascript'>
	function hasApp() {
		var _f = document.chk;
		if (document.getElementById('application_id') && document.getElementById('application_id').value != 0) {		//has
			document.getElementById('row_app_level_notes').style.display = '';
		}
	}
	function hasNoApp() {
		var _f = document.chk;

		if (document.getElementById('application_id') && document.getElementById('application_id').value == 0) {		//has no
			document.getElementById('row_app_level_notes').style.display = 'none';
		}
	}

	function changeApp() {
		type = document.getElementById('application_id').value;
		if (type == 0)
			hasNoApp();
		else
			hasApp();
	}

	if (document.getElementById('application_id')) {
		document.getElementById('application_id').onchange = changeApp;
	}
	hasNoApp();
	hasApp();
	</script>
	<?php

	bottom_footer();
}

function api_disku_extension_monitor($selected_items) {
	global $cnn_id;

	$app_level_notes="''";
	if (isset_request_var('app_level_notes') && !isempty_request_var('app_level_notes')) {
		$app_level_notes   = db_qstr(get_request_var('app_level_notes'));
	}
	$notes="''";
	if (isset_request_var('notes') && !isempty_request_var('notes')) {
		$notes   = db_qstr(get_request_var('notes'));
	}
	/* setup some variables */
	$extensions = array(); $emids = array();

	for ($i=0; $i<count($selected_items); $i++) {
		$parts = explode(":", $selected_items[$i]);
		if (!is_numeric($parts[0])) { //Undefined, new:extension:rid ==> new:exe:1576
			//get_request_var('extension') = $parts[1];
			//get_request_var('rid')       = $parts[2];
			$extensions[] = $parts[1];
			if (!isempty_request_var('application_id')) { //map
				$map_save['rid']            = $parts[2];
				$map_save['extension']      = $parts[1];
				$map_save['application_id'] = get_request_var('application_id');
				$map_save['notes']          = get_request_var('app_level_notes');
				sql_save($map_save, 'disku_extension_monitors');
			}
		} elseif (!empty($parts[0]) && !empty($parts[1])) { //Monitored, emid:rid ==> 46:1575
			/* ================= input validation ================= */
			input_validate_input_number($parts[0]);
			/* ==================================================== */
			$emids[] = $parts[0];
			$ext=db_fetch_cell_prepared("SELECT extension FROM disku_extension_registry WHERE id=?", array($parts[1]));
			if (!empty($ext)) {
				$extensions[] = $ext;
				$ext   = db_qstr($ext);
			}
			if (!isempty_request_var('application_id')) { //map
				$mapped_same_id=db_fetch_cell_prepared("SELECT id FROM disku_extension_monitors WHERE application_id=? AND extension=?", array(get_request_var('application_id'), $ext));
				if (empty($mapped_same_id)) {
					db_execute_prepared("UPDATE disku_extension_monitors SET application_id=?, notes=? WHERE id=?", array(get_request_var('application_id'), $app_level_notes, $parts[0]));
				} elseif ($mapped_same_id == $parts[0]) {
					db_execute_prepared("UPDATE disku_extension_monitors SET notes=? WHERE id=?", array($app_level_notes, $mapped_same_id));
				} else{
					db_execute_prepared("UPDATE disku_extension_monitors SET notes=? WHERE id=?", array($app_level_notes, $mapped_same_id));
					db_execute_prepared("DELETE FROM disku_extension_monitors WHERE id=?", array($parts[0]));
				}
			}
		}
	}
	if (isempty_request_var('application_id') && cacti_sizeof($emids)) { //un-map
		db_execute("DELETE FROM disku_extension_monitors WHERE id IN ('" . implode("','",$emids) . "')");
	}
	if (cacti_sizeof($extensions)) {
		if (cacti_sizeof($extensions) ==1 && !empty($notes)) {
			db_execute_prepared("UPDATE disku_extension_registry SET monitor='on', notes=? WHERE extension=?", array($notes, $extensions[0]));
		} else{
			$extensions=array_unique($extensions);
			db_execute("UPDATE disku_extension_registry SET monitor='on' WHERE extension IN ('" . implode("','",$extensions) . "')");
		}
		create_graphs_for_extension($extensions);
	}
}

function api_disku_extension_unmonitor($selected_items) {
	/* setup some variables */
	$extensions = array(); $emids = array(); $rids = array();

	for ($i=0; $i<count($selected_items); $i++) {
		$parts = explode(":", $selected_items[$i]);
		if (!is_numeric($parts[0])) { //Undefined, new:extension:rid ==> new:exe:1576
			$extensions[] = $parts[1];
			$rids[] = $parts[2];
		} elseif (!empty($parts[0]) && !empty($parts[1])) { //Monitored, emid:rid ==> 46:1575
			$emids[] = $parts[0];
			$ext=db_fetch_cell_prepared("SELECT extension FROM disku_extension_registry WHERE id=?", array($parts[1]));
			if (!empty($ext)) {
				$extensions[] = $ext;
			}
			$rids[] = $parts[1];
		}
	}
	if (cacti_sizeof($rids)) { //umonitor
		$rids=array_unique($rids);
		$extensions=array_unique($extensions);
		db_execute("UPDATE disku_extension_registry SET monitor='' WHERE id IN ('" . implode("','",$rids) . "')");
		remove_graphs_for_extensions($extensions);
	}
}

function api_disku_extension_delete($selected_items) {
	/* setup some variables */
	$extensions = array(); $emids = array(); $rids = array();

	for ($i=0; $i<count($selected_items); $i++) {
		$parts = explode(":", $selected_items[$i]);
		if (!is_numeric($parts[0])) { //Undefined, new:extension:rid ==> new:exe:1576
			$extensions[] = $parts[1];
			$rids[] = $parts[2];
		} elseif (!empty($parts[0]) && !empty($parts[1])) { //Monitored, emid:rid ==> 46:1575
			$emids[] = $parts[0];
			$ext=db_fetch_cell_prepared("SELECT extension FROM disku_extension_registry WHERE id=?", array($parts[1]));
			if (!empty($ext)) {
				$extensions[] = $ext;
			}
			$rids[] = $parts[1];
		}
	}

	if (cacti_sizeof($emids)) {
		db_execute("DELETE FROM disku_extension_monitors WHERE id IN ('" . implode("','",$emids) . "')");
	}

	if (!isset_request_var('delete_type')) { set_request_var('delete_type',1); }
	if (cacti_sizeof($rids)) {
		$rids=array_unique($rids);
		$extensions=array_unique($extensions);
		$still_mapped=array_rekey(db_fetch_assoc("SELECT DISTINCT extension FROM disku_extension_monitors WHERE extension IN ('" . implode("','",$extensions) . "')"), 'extension', 'extension');
		$delete_exts=array_diff($extensions, $still_mapped);
		if (cacti_sizeof($delete_exts)) {
			if (get_request_var('delete_type') == 2) {
				remove_graphs_for_extensions($delete_exts);
			}
			db_execute("DELETE FROM disku_extension_registry WHERE extension IN ('" . implode("','",$delete_exts) . "')");
		}
	}

}

function extension_edit() {
	global $config;

	if (isset_request_var('id') && substr_count(get_request_var('id'), 'new:')) {
		$parts = explode(':', get_request_var('id'));
		$ext   = $parts[1];
		$rid = $parts[2];

		$data  = db_fetch_row_prepared("SELECT id, notes FROM disku_extension_registry WHERE id=?", array($rid));

		$extension['extension'] = $ext;
		$extension['rid']       = $data['id'];
		$extension['notes']     = $data['notes'];

		$header_label = __('[edit]', 'disku');
	} elseif (!isset_request_var('id') || isempty_request_var('id')) {
		$extension = array();
		$header_label = __('[new]', 'disku');
		$rid = 0;
	} else {
		/* ================= input validation ================= */
		//input_validate_input_number(get_request_var('id'));
		$parts = explode(':', get_request_var('id'));
		input_validate_input_number($parts[0]);
		/* ==================================================== */

		$extension = db_fetch_row_prepared("SELECT em.id, em.rid, em.extension, em.application_id,
			em.notes as app_level_notes, a.application, r.notes
			FROM disku_extension_monitors AS em
			LEFT JOIN disku_applications AS a
			ON em.application_id=a.id
			LEFT JOIN  disku_extension_registry AS r
			ON r.id=em.rid
			WHERE em.id=?", array($parts[0]));

		$rid = $parts[1];

		$header_label = __esc('[edit: %s]', $extension['application_id'] == '' ? __('Undefined', 'disku'):$extension['application'] . ' (' . $extension['extension'] . ')', 'disku');
	}

	$fields_extension_edit = array(
		'extension' => array(
			'method' => ($rid == 0 ? 'textbox':'other'),
			'friendly_name' => __('Registry Extension Name', 'disku'),
			'value' => '|arg1:extension|',
			'default' => '',
			'size' => '10',
			'max_length' => '20'
		),
		'notes' => array(
			'method' => 'textbox',
			'friendly_name' => __('Extension Description', 'disku'),
			'value' => '|arg1:notes|',
			'default' => '',
			'size' => '90',
			'max_length' => '128'
		),
		'application_id' => array(
			'method' => 'drop_sql',
			'friendly_name' => __('Application', 'disku'),
			'value' => '|arg1:application_id|',
			'none_value' => 'None',
			'default' => '',
			'sql' => "SELECT id, application AS name FROM disku_applications AS rs ORDER BY application"
		),
		'_application_id' => array(
			'method' => 'hidden',
			'value' => isset($extension['application_id']) ? $extension['application_id'] : "0"
		),
		'app_level_notes' => array(
			'method' => 'textbox',
			'friendly_name' => __('Application Level Extension Description', 'disku'),
			'value' => '|arg1:app_level_notes|',
			'default' => '',
			'size' => '90',
			'max_length' => '128'
		),
		/*'registryaction' => array(
			'method' => 'drop_array',
			'friendly_name' => 'Global Registry Change',
			'description' => 'What Action do you wish to take on the Extension Registry?  Choices are a) Register/Update Registry, b) Leave Registry Unchanged, c) Register/Update and Remove Duplicates',
			'default' => '0',
			'value' => '0',
			'array' => array(0 => 'Register/Update Registry', 1 => 'Leave Registry Unchanged', 2 => 'Register/Update and Remove Duplicates')
		),*/
		'rid' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:rid|'
		),
		'id' => array(
			'method' => 'hidden_zero',
			//'value' => $id
			//'value' => get_request_var('id')
			'value' => (isset_request_var('id') ? get_request_var('id') : 0)
		),
		'save_component' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	form_start('disku_extenreg.php', 'chk');

	html_start_box(__esc('Disk Monitoring Extension Edit %s', $header_label), '100%', '', '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('form_name' => 'chk'),
			'fields' => inject_form_variables($fields_extension_edit, (isset($extension) ? $extension : array()))
		)
	);

	html_end_box();

	form_save_button('disku_extenreg.php', '', 'id');

	?>
	<script type='text/javascript'>
	function hasApp() {
		var _f = document.chk;
		if (document.getElementById('application_id') && document.getElementById('application_id').value != 0) {		//has
			document.getElementById('row_app_level_notes').style.display = '';
		}
	}
	function hasNoApp() {
		var _f = document.chk;

		if (document.getElementById('application_id') && document.getElementById('application_id').value == 0) {		//has no
			document.getElementById('row_app_level_notes').style.display = 'none';
		}
	}

	function changeApp() {
		type = document.getElementById('application_id').value;
		if (type == 0)
			hasNoApp();
		else
			hasApp();
	}

	if (document.getElementById('application_id')) {
		document.getElementById('application_id').onchange = changeApp;
	}
	hasNoApp();
	hasApp();
	</script>
	<?php
}

function disku_extenreg() {
	global $config, $disku_rows_selector, $extension_actions;

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
		'monitor' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'application' => array(
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
			'default' => 'size',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

  	validate_store_request_vars($filters, 'sess_disku_extenreg');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';

	$extensions = get_disku_extenreg($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = 'disku_extenreg.php?header=false&action=view';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&monitor=' + $('#monitor').val();
		strURL += '&application=' + $('#application').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&page=1';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'disku_extenreg.php?header=false&action=view&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#formname').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #filter, #monitor, #application').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Disk Monitoring by Extension', 'disku'), '100%', '', '3', 'center', 'disku_extenreg.php?action=edit');

	?>
	<tr class='odd'>
		<td>
			<form id='formname' method='get' action='disku_extenreg.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('App', 'disku');?>
					</td>
					<td>
						<select id='application'>
							<option value='-1'<?php if (get_request_var('application') == '-1') {?> selected<?php }?>>All</option>
							<option value='0'<?php if (get_request_var('application') == '0') {?> selected<?php }?>>Undefined</option>
							<?php
							$apps = array_rekey(
								db_fetch_assoc('SELECT id, application
									FROM disku_applications
									ORDER BY application'),
								'id', 'application'
							);

							if (cacti_sizeof($apps)) {
								foreach ($apps as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('application') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Monitored', 'disku');?>
					</td>
					<td width='1'>
						<select id='monitor'>
							<option value='-1'<?php if (get_request_var('monitor') == '-1') {?> selected<?php }?>>All</option>
							<option value='0'<?php if (get_request_var('monitor') == '0') {?> selected<?php }?>>Yes</option>
							<option value='1'<?php if (get_request_var('monitor') == '1') {?> selected<?php }?>>No</option>
						</select>
					</td>
					<td>
						<?php print __('Extensions', 'disku');?>
					</td>
					<td width='1'>
						<select id='rows'>
							<?php
							if (cacti_sizeof($disku_rows_selector) > 0) {
								foreach ($disku_rows_selector as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Set/Refresh Filters', 'disku');?>'>
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
					<td width='1'>
						<input type='text' id='filter' size='30' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	form_start('disku_extenreg.php', 'chk');

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM disku_extension_registry AS r
		LEFT JOIN disku_extension_monitors as em
		ON r.id=em.rid
		LEFT JOIN disku_applications AS a
		ON a.id=em.application_id
		LEFT JOIN disku_extension_totals_simple as e
		ON r.extension=e.extension
		$sql_where", $sql_params);

	html_start_box('', '100%', '', '4', 'center', '');

	$nav = html_nav_bar('disku_extenreg.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Extensions'), 'page', 'main');

	print $nav;

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'extension' => array(
			'display' => __('Extension', 'disku'),
			'sort' => 'ASC'
		),
		'application' => array(
			'display' => __('Application', 'disku'),
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Monitored', 'disku'),
			'sort' => 'ASC'
		),
		'nosort2' => array(
			'display' => __('Description and Application Level Description', 'disku'),
			'sort' => 'ASC'
		),
		'users' => array(
			'display' => __('Total Users', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'files' => array(
			'display' => __('Total Files', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size' => array(
			'display' => __('Total Size', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size0to6' => array(
			'display' => __('Less 6 Months', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size6to12' => array(
			'display' => __('Between 6-12 Months', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size12plus' => array(
			'display' => __('12 Months Plus', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='439617ebef50ab9be20c168f882d187e'");

	$i = 0;
	if (cacti_sizeof($extensions)) {
		foreach ($extensions as $e) {
			if (is_numeric($e['mid'])) {
				$id = $e['mid']. ':'. $e['rid'];
			} else{
				$id = 'new:' . html_escape($e['extension']). ':'. $e['rid'];
			}

			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared('SELECT id
					FROM graph_local
					WHERE snmp_query_id = ?
					AND snmp_index = ?',
					array($dqid, str_replace('!', '.', $e['extension'])));

				if (cacti_sizeof($graphs)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($graphs as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				} else{
					unset($graph_select);
				}
			}

			form_alternate_row('line' . $id);

			if (isset($graph_select)) {
				print "<td><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "/plugins/disku/images/view_graphs.gif' title='" . __esc('View User Graphs', 'disku') . "'></a></td>";
			} else{
				print "<td></td>";
			}

			form_selectable_cell("<a class='linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/disku/disku_extenreg.php?action=edit&id=' . $id) . "'>" . html_escape($e['extension']) . '</a>', $id);
			form_selectable_cell((empty($e['application_id']) ? __('Undefined'): html_escape($e['application'])), $id);
			form_selectable_cell((empty($e['monitor']) ? "<span class='deviceDown'>" . __('No') . '</span>':"<span class='deviceUp'>" . __('Yes') . '</span>'), $id);
			form_selectable_cell(html_escape($e['rnotes']) . (empty($e['mnotes']) ? '': '; ' . html_escape($e['mnotes'])), $id);
			form_selectable_cell(number_format_i18n($e['users']), $id, '', 'right');
			form_selectable_cell(number_format_i18n($e['files']), $id, '', 'right');
			form_selectable_cell(display_fs_memory($e['size']/1024), $id, '', 'right');
			form_selectable_cell(display_fs_memory($e['size0to6']/1024), $id, '', 'right');
			form_selectable_cell(display_fs_memory($e['size6to12']/1024), $id, '', 'right');
			form_selectable_cell(display_fs_memory($e['size12plus']/1024), $id, '', 'right');
			form_checkbox_cell(html_escape($e['extension']), $id);

			form_end_row();
		}
	} else{
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Extensions Found', 'disku') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($extensions)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($extension_actions);

	form_end();
}

function get_disku_extenreg(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params) {
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(r.extension LIKE ? OR r.notes LIKE ? OR em.notes LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('application') == 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(application_id IS NULL OR application_id=0)';
	} elseif (get_request_var('application') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'application_id=?';
		$sql_params[] = get_request_var('application');
	}

	if (get_request_var('monitor') == '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'r.monitor="on"';
	} elseif (get_request_var('monitor') == '1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(r.monitor IS NULL OR r.monitor!="on")';
	}

	$sql_order = get_order_string();

	$extension_sql = "SELECT r.id AS rid, em.id AS mid,
		r.monitor, r.extension, em.application_id,
		a.application, em.notes AS mnotes, r.notes AS rnotes,
		e.users, e.files, e.size, e.size0to6, e.size6to12, e.size12plus
		FROM disku_extension_registry AS r
		LEFT JOIN disku_extension_monitors as em
		ON r.id=em.rid
		LEFT JOIN disku_applications AS a
		ON a.id=em.application_id
		LEFT JOIN disku_extension_totals_simple as e
		ON r.extension=e.extension
		$sql_where
		$sql_order";

	if ($apply_limits) {
		$extension_sql .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($extension_sql, $sql_params);
}

