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
include_once('./include/auth.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');
include_once($config["base_path"] . '/lib/rtm_functions.php');

/* set default action */
set_default_action();

get_filter_request_var('id');

if (isset_request_var('id')) {
	if (get_request_var('id') > 0) {
		if (strpos(get_nfilter_request_var('action'), 'check_') === false) {
			$gl_clusterid = db_fetch_cell_prepared('SELECT clusterid
				FROM gridalarms_template
				WHERE id = ?', array(get_request_var('id')));

			$gl_clustername = "''";

			if ($gl_clusterid > 0) $gl_clustername = "'" . get_clustername($gl_clusterid) . "'";
		} else {
			$gl_clusterid = db_fetch_cell_prepared('SELECT clusterid
				FROM gridalarms_template
				WHERE expression_id = ?',
				array(get_request_var('id')));

			$gl_clustername = "''";
			if ($gl_clusterid > 0) $gl_clustername = "'" . get_clustername($gl_clusterid) . "'";
		}
	}
} else {
	$gl_clusterid = "''";
	$gl_clustername = "''";
}

$gridalarms_expression_actions = array(
	1 => __('Delete', 'gridalarms')
);

$expression_types = array(
	0 => __('Table', 'gridalarms'),
	1 => __('SQL Query', 'gridalarms'),
	2 => __('Script', 'gridalarms')
);

set_default_action();

get_filter_request_var('id');
get_filter_request_var('alarm_id');

switch (get_nfilter_request_var('action')) {
	case 'getmetrics':
		get_metrics_for_alarm_form();
		exit;
	case 'metric_save':
		if (gridalarms_metric_save() == true) {
			header('Location: gridalarms_template_edit.php?header=false');
		} else {
			header('Location: gridalarms_template_edit.php?action=metric_edit&header=false&session=1');
		}

		break;
	case 'gettables':
		gettables();

		break;
	case 'getcolumns':
		getcolumns();

		break;
	case 'check_syntax':
		check_expression_syntax(get_filter_request_var('id'));

		break;
	case 'check_syntax_sql':
		check_expression_syntax(get_filter_request_var('id'), true, true);

		break;
	case 'check_sql_syntax':
		check_sql_syntax(get_filter_request_var('id'));

		break;
	case 'check_script_threshold':
		check_script(get_filter_request_var('id'), 'thold');

		break;
	case 'check_script_data':
		check_script(get_filter_request_var('id'), 'data');

		break;
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'metric_edit':
		top_header();
		gridalarms_metric_edit();
		bottom_footer();

		break;
	case 'layout_movedown':
		layout_movedown();
		header('Location: gridalarms_template_edit.php?tab=layout&header=false&id=' . get_request_var('id'));

		break;
	case 'layout_moveup':
		layout_moveup();
		header('Location: gridalarms_template_edit.php?tab=layout&header=false&id=' . get_request_var('id'));

		break;
	case 'layout_remove':
		if (isset_request_var('confirm_layout_remove') && get_nfilter_request_var('confirm_layout_remove') == 1) {
			layout_remove();
			header('Location: gridalarms_template_edit.php?tab=layout&header=false&id=' . get_request_var('id'));
		} else {
			confirm_layout_remove();
		}

		break;
	case 'layout_edit':
		top_header();

		layout_item_edit();

		bottom_footer();

		break;
	case 'layout_add':
		top_header();

		layout_item_edit(true);

		bottom_footer();

		break;
	case 'item_movedown':
		item_movedown();
		header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_filter_request_var('alarm_id'));

		break;
	case 'item_moveup':
		item_moveup();
		header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_filter_request_var('alarm_id'));

		break;
	case 'item_remove':
		if ((isset_request_var('confirm_item_remove') && get_nfilter_request_var('confirm_item_remove') == 1) ||
			(isset_request_var('confirm_metric_remove') && get_nfilter_request_var('confirm_metric_remove') == 1) ||
			(isset_request_var('confirm_input_remove') && get_nfilter_request_var('confirm_input_remove') == 1)) {
			item_remove();
			header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_filter_request_var('alarm_id'));
		} else {
			confirm_item_remove();
		}

		break;
	case 'input_edit':
		top_header();

		gridalarms_input_edit();

		bottom_footer();

		break;
	case 'item_edit':
		top_header();

		item_edit();

		bottom_footer();

		break;
	default:
		top_header();

		/* set the default tab */
		load_current_session_value('tab', 'sess_gat_tab', 'general');

		$current_tab = get_nfilter_request_var('tab');

		gridalarms_display_tabs($current_tab);

		switch (get_nfilter_request_var('tab')) {
			case 'general':
			case 'actions':
				edit_general_actions();
				break;
			case 'data':
				gridalarms_expression_edit();
				break;
			case 'layout':
				layout_edit();
				break;
			case 'breached':
				alarm_breached_items(get_request_var('id'), false, true, false, true);
				break;
		}

		bottom_footer();

		break;
}

function check_expression_syntax($id, $ajax = true, $rsql = false) {
	global $gl_clusterid, $gl_clustername;

	if (!empty($id)) {
		$sql_from_where = build_template_expression_string_for_sql_from_where($id);
		$sql_from_where = str_replace('|alert_clusterid|', $gl_clusterid, $sql_from_where);
		$sql_from_where = str_replace('|alert_clustername|', $gl_clustername, $sql_from_where);

		if (strlen($sql_from_where)) {
			$sql    = 'SELECT * ' . $sql_from_where . ' LIMIT 1';
			$dsql   = 'SELECT * ' . $sql_from_where;
			if (!$rsql) {
				$result = db_execute($sql, false);
			}

			if ($rsql) {
				if (!$ajax) return true;
				print "<p style='margin:5px 0px;min-height:15px' class='deviceUp'>" . html_escape($dsql) . '</p>';
			} elseif ($result) {
				if (!$ajax) return true;
				print "<p style='margin:5px 0px;min-height:15px' class='deviceUp'>" . __('OK', 'gridalarms') . '</p>';
			} else {
				if (!$ajax) return false;
				print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('Bad Syntax! Error:\'%s\'', html_escape(db_error()), 'gridalarms') . '</p>';
			}
		} else {
			if (!$ajax) return false;
			print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No Data Source Items Defined!', 'gridalarms') . '</p>';
		}
	} else {
		if (!$ajax) return false;
		print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No Data Source Items Defined!', 'gridalarms') . '</p>';
	}

	if ($ajax) {
		usleep(500000);
	}
}

function check_sql_syntax($id, $ajax = true) {
	global $gl_clusterid, $gl_clustername;

	if (!empty($id)) {
		$sql = trim(db_fetch_cell_prepared('SELECT sql_query
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($id)), '; ');

		$sql = gridalarms_template_replace_custom_input($id, $sql);
		$sql = str_replace('|alert_clusterid|', $gl_clusterid, $sql);
		$sql = str_replace('|alert_clustername|', $gl_clustername, $sql);

		if (strlen($sql)) {
			$sql = $sql .' LIMIT 1';
			$result = db_execute($sql, false);

			if ($result) {
				$res2 = db_fetch_assoc($sql);
				if (cacti_sizeof($res2)) {
					if (!$ajax) return true;
					print "<p style='margin:5px 0px;min-height:15px' class='deviceUp'>" . __('OK', 'gridalarms') . "</p>";
				} else {
					if (!$ajax) return true;
					print "<p style='margin:5px 0px;min-height:15px' class='deviceRecovering'>" . __('Syntax is OK, but no results returned.', 'gridalarms') . '</p>';
				}
			} else {
				if (!$ajax) return false;
				print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('Bad Syntax! Error: \'%s\'', html_escape(db_error()), 'gridalarms') . '</p>';
			}
		} else {
			if (!$ajax) return false;
			print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No SQL Defined, You must Save Alert First!', 'gridalarms') . '</p>';
		}
	} else {
		if (!$ajax) return false;
		print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No SQL Defined, You must Save Alert First!', 'gridalarms') . '</p>';
	}

	if ($ajax) {
		usleep(500000);
	}
}

function check_script($id, $type = 'thold', $ajax = true) {
	global $gl_clusterid, $gl_clustername;
	if (!empty($id)) {
		if ($type == 'thold') {
			$command = trim(db_fetch_cell_prepared('SELECT script_thold
				FROM gridalarms_template_expression
				WHERE id = ?',
				array($id)) . '; ');
		} else {
			$command = trim(db_fetch_cell_prepared('SELECT script_data
				FROM gridalarms_template_expression
				WHERE id = ?',
				array($id)), '; ');
		}

		$command = gridalarms_template_replace_custom_input($id, $command);
		$command = str_replace('|alert_clusterid|', $gl_clusterid, $command);
		$command = str_replace('|alert_clustername|', $gl_clustername, $command);

		$path_webroot = db_fetch_cell("SELECT value
			FROM settings
			WHERE name='path_webroot'");

		$command = str_replace('|path_cacti|', $path_webroot, $command);

		if (strlen($command)) {
			$return_code = 0;
			$output      = array();
			$result      = exec($command, $output, $return_code);

			if (strlen($result) && $return_code == 0) {
				if (!$ajax) return true;

				if ($type == 'thold') {
					print "<p style='margin:5px 0px;min-height:15px' class='deviceUp'>" . __('OK', 'gridalarms') . '<br>' . __('Output: \'%s\'', trim(implode("\n", $output)), 'gridalarms') . '</p>';
				} else{
					print "<p style='margin:5px 0px;min-height:15px' class='deviceUp'>" . __('OK', 'gridalarms') . '<br>' . __('Output: \'%s\'', "<pre style='margin:0px;'>" . html_escape(trim(implode("\n", $output)) . '</pre>'), 'gridalarms') . '</p>';
				}
			} else {
				if (!$ajax) return false;

				if ($return_code == 127) {
					print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('Error: Script File Not Found!', 'gridalarms') . '</p>';
				} else {
					if ($type == 'thold') {
						print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('Error!', 'gridalarms') . '<br>' . __('Return Code: \'%s\'', $return_code, 'gridalarms') . '<br>' . __('Output: \'%s\'', html_escape(trim(implode("\n", $output))), 'gridalarms') . '</p>';
					} else{
						print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('Error!', 'gridalarms') . '<br>' . __('Return Code: \'%s\'', $return_code, 'gridalarms') . '<br>' . __('Output: %s', "<pre style='margin:0px;'>" . html_escape(trim(implode("\n", $output))) . '</pre>', 'gridalarms') . '</p>';
					}
				}
			}
		} else {
			if (!$ajax) return false;
			print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No Command Defined, You must Save Alert First!', 'gridalarms') . '</p>';
		}
	} else {
		if (!$ajax) return false;
		print "<p style='margin:5px 0px;min-height:15px' class='deviceDown'>" . __('No Command Defined, You must Save Alert First!', 'gridalarms') . '</p>';
	}

	if ($ajax) {
		usleep(500000);
	}
}

/**
 * Build edit form elements.  Use expression values if it already exists.
 * @param Array $expression
 */
function build_expression_edit_form($expression) {
	global $metric_types, $expression_types, $job_tables;

	$name_value        = '|arg1:name|';
	$description_value = '|arg1:description|';
	$type_value        = '|arg1:type|';
	$ds_type_value     = '|arg1:ds_type|';
	$db_table_value    = '|arg1:db_table|';
	$sql_query         = '';
	$script_thold      = '';
	$script_data       = '';
	$script_data_type  = '';

	/* load from session variables */
	if (isset($expression) && cacti_sizeof($expression)) {
		$db_tables = get_db_tables_form($expression['type']);
		$isNew = false;
	} else {
		$db_tables = $job_tables;
		$isNew = true;
	}

	$fields_edit = array(
		'name' => array(
			'method' => 'hidden',
			'friendly_name' => __('Name', 'gridalarms'),
			'description' => __('Enter a useful name for this Data Source.', 'gridalarms'),
			'value' => $isNew ? 'Auto Generated Name' : $name_value,
			'max_length' => '255',
			'size' => '60'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => __('Description', 'gridalarms'),
			'description' => __('Enter a meaningful description of this Data Source.', 'gridalarms'),
			'value' => $description_value,
			'max_length' => '255',
			'size' => '60'
		),
		'ds_type_display' => array(
			'method' => $isNew ? 'drop_array' : '',
			'friendly_name' => __('Data Type', 'gridalarms'),
			'description' => __('Select the type of expression to use. <ul><li>Table - Grid table names are listed from the database. Choose a table to base the alert.</li><li>SQL Query - You can create your own SQL query statements.</li><li>Script - You can create custom scripts that return threshold values. Your script can have complex queries.</li></ul>', 'gridalarms'),
			'array' => $expression_types,
			'value' => $isNew ? $ds_type_value : $expression_types[$expression['ds_type']]
		),
		'script_thold' => array(
			'friendly_name' => __('Threshold Script', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'description' => __('The script that must be run to get the current value of the alert. You must specify the path to this threshold script. Maximum length of the script\'s path, name, and arguments must not exceed 255 characters.', 'gridalarms'),
			'value' => $isNew ? $script_thold : (isset($expression['script_thold']) ? $expression['script_thold'] : '')
		),
		'script_data' => array(
			'friendly_name' => __('Breached Item Data Script', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'description' => __('The script that must be run to get the breached items. The output can be in plain text or HTML. RTM automatically includes the output into an email alert or the Alarm Details page. The maximum length of the script\'s path, name, and arguments must not exceed 255 characters.', 'gridalarms'),
			'value' => $isNew ? $script_data : (isset($expression['script_data']) ? $expression['script_data'] : '')
		),
		'script_data_type' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Breached Items Data Format', 'gridalarms'),
			'description' => __('What format will the output from the script be in.', 'gridalarms'),
			'array' => array(
				'0' => __('CSV with Header', 'gridalarms'),
				'1' => __('CSV w/o Header', 'gridalarms'),
				'2' => __('HTML', 'gridalarms'),
				'3' => __('Plain Text', 'gridalarms')
			),
			'value' => $isNew ? $script_data_type : (isset($expression['script_data_type']) ? $expression['script_data_type'] : '0')
		),
		'ds_type' => array(
			'method' => 'hidden_zero',
			'value' => $isNew ? $ds_type_value : $expression['ds_type']
		),
		'type_display' => array(
			'method' => $isNew ? 'drop_array' : '',
			'friendly_name' => __('Table Type', 'gridalarms'),
			'description' => __('Select the Metric type you are using for this expression.', 'gridalarms'),
			'array' => $metric_types,
			'value' => $isNew ? $type_value : $metric_types[$expression['type']]
		),
		'db_table' => array(
			'method' => $isNew ? 'drop_array' : '',
			'friendly_name' => __('Table Name', 'gridalarms'),
			'description' => __('Select the database table for the selected Data Type.', 'gridalarms'),
			'array' => $db_tables,
			'value' => $db_table_value,
			'disabled' => true
		),
		'sql_query' => array(
			'friendly_name' => __('SQL Query', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '15',
			'textarea_cols' => '90',
			'description' => __('Select the type of expression to use. If you are using SQL Query type Data Sources, then the Data Source returns rows of information about exceptional information in the cluster. When you define an Alert, you can select a column to aggregate within the table.', 'gridalarms'),
			'value' => $isNew ? $sql_query : (isset($expression['sql_query']) ? $expression['sql_query'] : '')
		),
		'type' => array(
			'method' => 'hidden_zero',
			'value' => $isNew ? $type_value : $expression['type']
		),
		'id' => array(
			'method' => 'hidden',
			'value' => isset($expression['id']) ? $expression['id'] : ''
		),
		'alarm_id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('id') ? get_request_var('id') : ''
		),
		'save_component_gridalarms_expression' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	return $fields_edit;
}

/* --------------------------
    Global Form Functions
   -------------------------- */

function draw_gridalarms_expression_preview($expression_id) {
	if (isset_request_var('tab') && get_request_var('tab') == 'data') { ?>
	<tr class='even'>
		<td id='syntax_message'><p style='margin:5px 0px;min-height:15px;'></p></td>
	</tr>
	<?php }?>
	<tr class='odd'>
		<td style='padding:5px;'>
			<pre style='margin:0px;'><b><?php print __('Expression Syntax', 'gridalarms');?></b> = <?php print html_escape(gridalarms_template_replace_custom_input($expression_id, get_gridalarms_template_expression_string($expression_id, true)));?></pre>
		</td>
	</tr>
	<?php if (isset_request_var('tab') && get_request_var('tab') == 'data') { ?>
	<tr class='even'>
		<td>
			<input id='check_syntax' type='button' name='check_syntax' value='<?php print __('Check Syntax', 'gridalarms');?>'/>
			<input id='check_syntax_sql' type='button' name='check_syntax_sql' value='<?php print __('Get SQL', 'gridalarms');?>'/>
			<?php
			if (get_request_var('id')) {
				print "<input type='hidden' id='check_syntax_expression_id' name='check_syntax_expression_id' value='" . $expression_id . "'/>";
			} else {
				print "<input type='hidden' id='check_syntax_expression_id' name='check_syntax_expression_id' value=''/>";
			}
			?>
		</td>
	</tr>
	<?php }
}

function draw_gridalarms_sql_preview($expression) {
	if (cacti_sizeof($expression)) { ?>
	<tr class='even'>
		<td id='syntax_message'><p style='margin:5px 0px;min-height:15px;'></p></td>
	</tr>
	<tr class='odd'>
		<td style='word-wrap:break-word;padding:5px'>
			<pre style='margin:0px;'><i><?php print __('SQL Syntax', 'gridalarms');?></i> = <?php print html_escape(gridalarms_template_replace_custom_input($expression['id'], $expression['sql_query']));?></pre>
		</td>
	</tr>
	<tr class='even'>
		<td>
			<input id='check_sql_syntax' type='button' name='check_sql_syntax' value='<?php print __('Check SQL', 'gridalarms');?>'/>
			<?php
			print "<input type='hidden' id='check_syntax_expression_id' name='check_syntax_expression_id' value='" . $expression['id'] . "'/>";
			?>
		</td>
	</tr>
	<?php
	}
}

function draw_gridalarms_script_preview($expression) {
	if (cacti_sizeof($expression)) { ?>
		<tr class='even'>
			<td id='syntax_message'><p style='margin:5px 0px;min-height:15px;'><?php print __('Not Run', 'gridalarms');?></p></td>
		</tr>
		<tr class='odd'>
			<td style='padding:5px'>
				<input id='check_script_threshold' type='button' name='check_script_threshold' value='<?php print __('Check Threshold Script', 'gridalarms');?>'/>
				<input id='check_script_data' type='button' name='check_script_data' value='<?php print __('Check Items Script', 'gridalarms');?>'/>
				<?php
				print "<input type='hidden' id='check_syntax_expression_id' name='check_syntax_expression_id' value='" . $expression['id'] . "'/>";
				?>
			</td>
		</tr>
		<?php
	}
}

function gridalarms_metric_remove($id, $expression) {
	$all_exp = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM gridalarms_template_expression_item
		WHERE type=3 AND value = ?',
		array($id));

	$my_exp  = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM gridalarms_template_expression_item
		WHERE type = 3
		AND value = ?
		AND expression_id = ?',
		array($id, $expression));

	if ($my_exp == 0) {
		db_execute_prepared('DELETE FROM gridalarms_template_metric_expression
			WHERE metric_id = ?
			AND expression_id = ?',
			array($id, $expression));
	} else {
		raise_message('gridalarms_metric_in_use');
		return;
	}

	if ($all_exp == 0) {
		db_execute_prepared('DELETE FROM gridalarms_template_metric
			WHERE id = ?',
			array($id));
	} else {
		raise_message('gridalarms_metric_in_use');
	}
}

function gridalarms_input_remove($id, $expression) {
	$name = db_fetch_cell_prepared('SELECT name
		FROM gridalarms_template_expression_input
		WHERE id = ?',
		array($id));

	$all_exp = db_fetch_cell_prepared('SELECT COUNT(id)
		FROM gridalarms_template_expression_item
		WHERE type = 7
		AND value = ?',
		array($name));

	if ($all_exp == 0) {
		db_execute_prepared('DELETE FROM gridalarms_template_expression_input
			WHERE id = ?',
			array($id));

		db_execute_prepared('DELETE FROM gridalarms_expression_input
			WHERE template_id = ?
			AND name = ?',
			array($expression, $name));
	} else {
		raise_message('gridalarms_input_in_use');
	}
}

function form_save() {
	input_validate_input_regex(get_nfilter_request_var('tab'), "^([a-zA-Z0-9_]+)$");

	get_filter_request_var('alarm_id');
	get_filter_request_var('expression_id');
	get_filter_request_var('id');

	if (isset_request_var('save_component_gridalarms_expression')) {
		$save['id']   = get_request_var('id');
		$save['name'] = form_input_validate(get_nfilter_request_var('name'), 'name', '', false, 3);
		$save['hash'] = get_hash_alert_template(get_request_var('id'), 'expression');

		if (!isempty_request_var('id')) {
			$save['ds_type']      = form_input_validate(get_nfilter_request_var('ds_type'), 'ds_type', '', false, 3);
			$save['type']         = form_input_validate(get_nfilter_request_var('type'), 'type', '', false, 3);
		} else {
			$save['ds_type']      = form_input_validate(get_nfilter_request_var('ds_type_display'), 'ds_type_display', '', false, 3);
			$save['type']         = form_input_validate(get_nfilter_request_var('type_display'), 'type_display', '', false, 3);
		}

		$save['db_table']         = form_input_validate(get_nfilter_request_var('db_table'), 'db_table', '', true, 3);
		$save['sql_query']        = form_input_validate(get_nfilter_request_var('sql_query'), 'sql_query', '', true, 3);
		$save['script_thold']     = form_input_validate(get_nfilter_request_var('script_thold'), 'script_thold', '', true, 3);
		$save['script_data']      = form_input_validate(get_nfilter_request_var('script_data'), 'script_data', '', true, 3);
		$save['script_data_type'] = form_input_validate(get_nfilter_request_var('script_data_type'), 'script_data_type', '', true, 3);
		$save['description']      = get_nfilter_request_var('description');

		$save['sql_query']        = str_replace('"','\'',$save['sql_query']);
		$save['sql_query']        = str_replace(';',' ',$save['sql_query']);
		$save['script_thold']     = str_replace('"','\'',$save['script_thold']);
		$save['script_data']      = str_replace('"','\'',$save['script_data']);

		if (!is_error_message()) {
			//Clean metrics cache when expression is modified/saved;
			if (!isempty_request_var('id')) {
				$expr_id = get_request_var('id');
				$expression_old = get_template_expression_by_id($expr_id);
				if (isset($_SESSION['sess_gat_expr']['expr_tmpl_' . $expr_id])
					&& ($expression_old['db_table'] != $save['db_table']
						|| $expression_old['sql_query'] != $save['sql_query'])) {
					unset($_SESSION['sess_gat_expr']['expr_tmpl_' . $expr_id]);
				}
			}

			$expression_id = sql_save($save, 'gridalarms_template_expression');
			$alarm_id      = get_request_var('alarm_id');

			if ($expression_id) {
				raise_message(1);

				template_propagation ('gridalarms_template', $alarm_id);

				db_execute_prepared('UPDATE gridalarms_template
					SET expression_id = ?
					WHERE id = ?',
					array($expression_id, $alarm_id));

				$items_1 = db_fetch_assoc_prepared('SELECT *
					FROM gridalarms_template_layout
					WHERE alarm_id = ?',
					array($alarm_id));

				if (!cacti_sizeof($items_1)) {
					$alarm_1 = get_template_by_id($alarm_id);

					populate_template_default_layout($alarm_1);
				}
			} else {
				raise_message(2);
			}

			header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . (empty($alarm_id) ? get_request_var('alarm_id') : $alarm_id));
		} else {
			header('Location: gridalarms_template_edit.php?action=expression_edit&header=false&tab=data&id='. get_request_var('alarm_id') . '&session=1');
		}

		exit;
	} elseif (isset_request_var('save_component_item')) {
		$sequence = get_sequence(get_request_var('id'), 'sequence', 'gridalarms_template_expression_item', 'expression_id=' . get_request_var('expression_id'));

		$save['id']            = get_request_var('id');
		$save['hash']          = get_hash_alert_template(get_request_var('id'), 'expression_item');
		$save['expression_id'] = get_request_var('expression_id');
		$save['sequence']      = $sequence;
		$save['type']          = get_nfilter_request_var('type');

		get_request_var('value');

		if ($save['type'] == 5) {	//type ='string'
			$save['value'] = trim(get_nfilter_request_var('value'), '"\' ');
		} else {
			$save['value'] = get_nfilter_request_var('value');
		}

		if ($save['type'] == 3 && trim($save['value']) == '') {
			raise_message('gridalarms_expression_empty_metric');
		}

		if (!is_error_message()) {
			$gridalarms_expression_item_id = sql_save($save, 'gridalarms_template_expression_item');

			if ($gridalarms_expression_item_id) {
				/* update the mapping of expressions */
				if ($save['type'] == 3) {
					db_execute_prepared('REPLACE INTO gridalarms_template_metric_expression
						(metric_id, expression_id) VALUES
						(?, ?)',
						array($save['value'], $save['expression_id']));
				}

				raise_message(1);

				template_propagation ('gridalarms_template', get_request_var('alarm_id'));
			} else {
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: gridalarms_template_edit.php?action=item_edit&header=false&expression_id=' . get_request_var('expression_id') . '&id=' . (empty($gridalarms_expression_item_id) ? get_request_var('id') : $gridalarms_expression_item_id) . '&alarm_id=' . get_request_var('alarm_id'));
		} else {
			header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_request_var('alarm_id'));
		}

		exit;
	} elseif (isset_request_var('save_component_metric')) {
		$save = array();

		if (!isempty_request_var('id')) {
			$save['id'] = get_request_var('id');
		} else {
			$save['id'] = 0;
		}

		$save['hash']          = get_hash_alert_template(get_request_var('id'), 'metric');
		$save['name']          = form_input_validate(get_nfilter_request_var('name'), 'name', '', false, 3);
		$save['description']   = form_input_validate(get_nfilter_request_var('description'), 'description', '', true, 3);
		$save['type']          = form_input_validate(get_nfilter_request_var('type'), 'type', '', false, 3);
		$save['db_table']      = form_input_validate(get_nfilter_request_var('db_table'), 'db_table', '', false, 3);
		$save['db_column']     = form_input_validate(get_nfilter_request_var('db_column'), 'db_column', '', false, 3);

		if (!is_error_message()) {
			$metric_id = sql_save($save, 'gridalarms_template_metric');

			if ($metric_id) {
				db_execute_prepared('REPLACE INTO gridalarms_template_metric_expression
					(metric_id, expression_id) VALUES
					(? , ?)',
					array($metric_id, get_request_var('expression_id')));

				raise_message(1);
				template_propagation ('gridalarms_template', get_request_var('alarm_id'));
			} else {
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: gridalarms_template_edit.php?action=metric_edit&header=false&expression_id=' . get_request_var('expression_id') . '&id=' . (empty($metric_id) ? get_request_var('id') : $metric_id) . '&alarm_id=' . get_request_var('alarm_id'));
		} else {
			header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_request_var('alarm_id'));
		}

		exit;
	} elseif (isset_request_var('save_component_data_input')) {
		$save                  = array();
		$save['id']            = get_request_var('id');
		$save['hash']          = get_hash_alert_template(get_request_var('id'), 'expression_input');
		$save['name']          = trim(get_request_var('name'));
		$save['description']   = get_request_var('description');
		$save['value']         = trim(get_request_var('value'));
		$save['expression_id'] = get_request_var('expression_id');

		$orig_name = db_fetch_cell_prepared('SELECT name
			FROM gridalarms_template_expression_input
			WHERE id = ?',
			array(get_request_var('id')));

		if (!is_error_message()) {
			$id = sql_save($save, 'gridalarms_template_expression_input');

			if ($id) {
				raise_message(1);
				if (!empty($orig_name) && ($orig_name != $save['name'])){
					db_execute_prepared('UPDATE gridalarms_expression_input
						SET name = ?
						WHERE name = ?
						AND template_id = ?',
						array($save['name'], $orig_name, get_request_var('alarm_id')));
				}

				template_propagation ('gridalarms_template', get_request_var('alarm_id'));
			} else {
				raise_message(2);
			}
		}

		header('Location: gridalarms_template_edit.php?tab=data&header=false&id=' . get_request_var('alarm_id'));

		exit;
	} elseif (isset_request_var('save_component_layout')) {
		$save = array();

		if (get_filter_request_var('sequence') == '') {
			$save['sequence'] = db_fetch_cell_prepared('SELECT MAX(sequence)+1
				FROM gridalarms_template_layout
				WHERE alarm_id = ?',
				array(get_filter_request_var('alarm_id')));
		} else {
			$save['sequence'] = get_request_var('sequence');
		}

		$save['id']            = get_filter_request_var('id');
		$save['hash']          = get_hash_alert_template(get_request_var('id'), 'layout_item');
		$save['alarm_id']      = get_filter_request_var('alarm_id');
		$save['display_name']  = form_input_validate(get_request_var('display_name'), 'display_name', '', false, 3);
		$save['column_name']   = get_nfilter_request_var('column_name');
		$save['sortposition']  = get_filter_request_var('sortposition');
		$save['sortdirection'] = get_filter_request_var('sortdirection');

		if (!is_error_message()) {
			$id = sql_save($save, 'gridalarms_template_layout');

			if ($save['sortposition'] > 0) {
				db_execute_prepared('UPDATE gridalarms_template_layout
					SET sortposition=IF(sortposition+1>4,0,sortposition+1)
					WHERE column_name != ?
					AND alarm_id = ?
					AND sortposition >= ?',
					array($save['column_name'], $save['alarm_id'], $save['sortposition']));
			}

			raise_message(1);

			template_propagation ('gridalarms_template', get_request_var('alarm_id'));

			header('Location: gridalarms_template_edit.php?tab=layout&header=false&id=' . get_request_var('alarm_id'));
		} else {
			header('Location: gridalarms_template_edit.php?action=layout_edit&header=false&id=' . get_request_var('alarm_id') . '&column=' . get_request_var('column_name'));
		}

		exit;
	} elseif (isset_request_var('save_component_alarm')) {
		if ((isset_request_var('alarm_hi') && trim(get_request_var('alarm_hi')) != '' && !is_numeric(get_request_var('alarm_hi'))) ||
			(isset_request_var('alarm_low') && trim(get_request_var('alarm_low')) != '' && !is_numeric(get_request_var('alarm_low'))) ||
			(isset_request_var('time_hi') && trim(get_request_var('time_hi')) != '' && !is_numeric(get_request_var('time_hi'))) ||
			(isset_request_var('time_low') && trim(get_request_var('time_low')) != '' && !is_numeric(get_request_var('time_low')))) {

			raise_message('gridalarms_alarm_hilo_numeric');
			header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
			exit;
		}

		if (get_request_var('tab') == 'general') {
			if ((get_request_var('alarm_type') == 0 && (!isset_request_var('alarm_hi') ||
				trim(get_request_var('alarm_hi')) == '')) && (get_request_var('alarm_type') == 0 &&
				(!isset_request_var('alarm_low') || trim(get_request_var('alarm_low')) == ''))) {

				raise_message('gridalarms_alarm_hilo');
				header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
				exit;
			}

			if (get_request_var('alarm_type') == 0 && isset_request_var('alarm_hi') && isset_request_var('alarm_low') &&
				trim(get_request_var('alarm_hi')) != '' && trim(get_request_var('alarm_low')) != '' &&
				round(get_request_var('alarm_low'),4) >= round(get_request_var('alarm_hi'),4)) {

				raise_message('gridalarms_alarm_hilo_imp');
				header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
				exit;
			}

			if (get_request_var('alarm_type') == 1 && isset_request_var('time_hi') && isset_request_var('time_low') &&
				trim(get_request_var('time_hi')) != '' && trim(get_request_var('time_low')) != '' &&
				round(get_request_var('time_low'),4) >= round(get_request_var('time_hi'),4)) {

				raise_message('gridalarms_alarm_hilo_imp');
				header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
				exit;
			}
		}

		if (get_request_var('tab') == 'actions') {
			if (isempty_request_var('id') ) {
				raise_message('gridalarms_alarm_no_alarm');
				header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
				exit;
			}
		}

		get_filter_request_var('id');
		get_filter_request_var('alarm_hi');
		get_filter_request_var('clusterid');
		get_filter_request_var('alarm_low');
		get_filter_request_var('alarm_fail_trigger');
		get_filter_request_var('repeat_alert');
		get_filter_request_var('alarm_type');
		get_filter_request_var('time_hi');
		get_filter_request_var('time_low');
		get_filter_request_var('time_fail_trigger');
		get_filter_request_var('time_fail_length');
		get_filter_request_var('data_type');
		get_filter_request_var('frequency');

		// grid_alarms Settings
		$save['id']   = get_request_var('id');
		$save['hash'] = get_hash_alert_template(get_request_var('id'), 'alert_template');

		if (get_request_var('tab') == 'general') {
			// Default Settings
			set_request_var('name', str_replace(array("\\", '"', "'"), '', get_request_var('name')));

			$save['name']              = (trim(get_request_var('name'))) == '' ? __('New Template', 'gridalarms') : get_request_var('name');
			$save['clusterid']         = get_request_var('clusterid');
			$save['expression_id']     = isset_request_var('expression_id') ? get_request_var('expression_id') : '0';
			$save['aggregation']       = get_nfilter_request_var('aggregation');
			$save['metric']            = isset_request_var('metric') ? get_nfilter_request_var('metric') : '';

			$save['exempt']            = isset_request_var('exempt') ? 'on' : '';
			$save['restored_alert']    = isset_request_var('restored_alert') ? 'on' : '';
			$save['req_ack']           = isset_request_var('req_ack') ? 'on' : '';
			$save['alarm_type']        = get_request_var('alarm_type');

			// Frequency
			$save['base_time_display'] = (trim(get_nfilter_request_var('base_time_display'))) == '' ? '12:00am' : get_nfilter_request_var('base_time_display');
			$save['base_time']          = strtotime($save['base_time_display']);

			if ( $save['base_time'] === false ) {
				raise_message('gridalarms_invalid_timestamp');
				header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . get_request_var('id'));
				exit;
			}

			$save['frequency']          = (trim(get_request_var('frequency'))) == '' ? '1' : get_request_var('frequency');

			// High / Low
			$save['alarm_hi']           = (trim(get_request_var('alarm_hi'))) == '' ? '' : round(get_request_var('alarm_hi'),4);
			$save['alarm_low']          = (trim(get_request_var('alarm_low'))) == '' ? '' : round(get_request_var('alarm_low'),4);
			$save['alarm_fail_trigger'] = (trim(get_request_var('alarm_fail_trigger'))) == '' ? '' : get_request_var('alarm_fail_trigger');
			// Time Based
			$save['time_hi']            = (trim(get_request_var('time_hi'))) == '' ? '' : round(get_request_var('time_hi'),4);
			$save['time_low']           = (trim(get_request_var('time_low'))) == '' ? '' : round(get_request_var('time_low'),4);
			$save['time_fail_trigger']  = (trim(get_request_var('time_fail_trigger'))) == '' ? '' : get_request_var('time_fail_trigger');
			$save['time_fail_length']   = (trim(get_request_var('time_fail_length'))) == '' ? '' : get_request_var('time_fail_length');
			$save['repeat_alert']       = (trim(get_request_var('repeat_alert'))) == '' ? '' : get_request_var('repeat_alert');

			// Alert Severity/Priority and Facility
			$save['syslog_priority']    = get_request_var('alarm_syslog_priority');
			$save['syslog_facility']    = get_request_var('alarm_syslog_facility');
		} else {
			if (trim(get_request_var('email_subject')) == '') {
				$save['email_subject']  = 'ALERT: <NAME> Breached Threshold Limits';
			} else {
				$save['email_subject']	= get_request_var('email_subject');
			}

			if (trim(get_request_var('email_body')) == '') {
				$save['email_body']     = 'An Alert has been issued that requires your attention. <br><br><strong><NAME></strong>: has breached threshold limits HI:<HI>/LOW:<LOW> with Current Value:<VALUE><br><DETAILS><br><br>';
			} else {
				$save['email_body']     = get_request_var('email_body');
			}

			$save['trigger_cmd_high']      = str_replace('"', '\'', str_replace("\r", '', get_request_var('trigger_cmd_high')));
			$save['trigger_cmd_low']       = str_replace('"', '\'', str_replace("\r", '', get_request_var('trigger_cmd_low')));
			$save['trigger_cmd_norm']      = str_replace('"', '\'', str_replace("\r", '', get_request_var('trigger_cmd_norm')));
			$save['cmd_retrigger_enabled'] = isset_request_var('cmd_retrigger_enabled') ? get_request_var('cmd_retrigger_enabled') : '';

			// Notification
			$save['syslog_enabled'] = isset_request_var('alarm_syslog_enabled') ? get_request_var('alarm_syslog_enabled') : '';

			$save['notify_extra']   = (trim(get_request_var('notify_extra'))) == '' ? '' : get_request_var('notify_extra');
			$save['notify_alert']   = (trim(get_request_var('notify_alert'))) == '' ? '' : get_request_var('notify_alert');
			$save['notify_users']   = isset_request_var('notify_users') ? 'on':'';

			if (isset_request_var('notify_cluster_admin') && get_request_var('notify_cluster_admin') == 'on') {
				$save['notify_cluster_admin'] = 1;
			} else {
				$save['notify_cluster_admin'] = 0;
			}
		}

		if (isempty_request_var('id')) {
			$save['email_subject'] = 'ALERT: <NAME> Breached Threshold Limits';
			$save['email_body']  = 'An Alert has been issued that requires your attention. <br><br><strong><NAME></strong>: has breached threshold limits HI:<HI>/LOW:<LOW> with Current Value:<VALUE><br><DETAILS><br><br>';
		}

		$id = sql_save($save , 'gridalarms_template');

		if (get_request_var('tab') == 'actions') {
			if (isset_request_var('notify_accounts') && trim(get_request_var('notify_accounts')) != '') {
				alarm_save_contacts ($id, get_request_var('notify_accounts'));
			} else {
				db_execute_prepared('DELETE FROM gridalarms_template_contacts
					WHERE alarm_id = ?',
					array($id));
			}
		}

		/* check for valid ds syntax */
		if ($id && !isempty_request_var('expression_id')) {
			$valid      = false;

			$expression = get_template_expression_by_id(get_request_var('expression_id'));
			switch ($expression['ds_type']) {
			case '0': // Expression
				if (check_expression_syntax(get_request_var('expression_id'), false)) {
					$valid = true;
				}

				break;
			case '1': // SQL Query
				if (check_sql_syntax(get_request_var('expression_id'), false)) {
					$valid = true;
				}

				break;
			case '2': // Script
				if (check_script(get_request_var('expression_id'), 'thold', false) && check_script(get_request_var('expression_id'), 'data', false)) {
					$valid = true;
				}

				break;
			}

			if (!$valid) {
				raise_message('gridalarms_alarm_save_disable');
			} else {
				raise_message('gridalarms_alarm_save');
			}
		} elseif ($id && isempty_request_var('expression_id')) {
			raise_message('gridalarms_alarm_save_nods');
			header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . $id);
			exit;
		}else {
			raise_message('gridalarms_alarm_save_nods');
		}

		if ($id) {
			template_propagation('gridalarms_template', $id);
		}

		header('Location: gridalarms_template_edit.php?header=false&tab=' . get_request_var('tab') . '&id=' . $id);
	}
}

function push_out_alarm_template($id) {
	$template   = get_template_by_id($id);
	$expression = get_template_expression_by_id($template['expression_id']);

	$alarms = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_alarm
		WHERE template_id = ?
		AND template_enabled="on"',
		array($id));

	if (cacti_sizeof($alarms)) {
		foreach($alarms as $alarm) {
			push_out_template_to_alarm($alarm, 0, true);
		}
	}
}

function delete_gridalarms_expression($expression_id) {
	$is_used = db_fetch_row_prepared('SELECT *
		FROM gridalarms_template
		WHERE expression_id = ?',
		array($expression_id));

	if (cacti_sizeof($is_used)) {
		raise_message('gridalarms_expression_in_use');
	} else {
		db_execute_prepared('DELETE FROM gridalarms_template_expression
			WHERE id = ?',
			array($expression_id));

		db_execute_prepared('DELETE FROM gridalarms_template_expression_item
			WHERE expression_id = ?',
			array($expression_id));
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $gridalarms_expression_actions, $gridalarms_metric_actions;

	if ($in_expression == true) {
		/* if we are to save this form, instead of display it */
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if ($selected_items != false) {
				if (get_request_var('drp_action') == '1') { /* delete */
					for ($i=0; $i<count($selected_items); $i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						delete_gridalarms_expression($selected_items[$i]);
					}
				}
			}

			header('Location: gridalarms_template_edit.php?header=false');
		}

		/* setup some variables */
		$gridalarms_expression_list = ''; $i = 0;

		/* loop through each of the graphs selected on the previous page and get more info about them */
		foreach ($_POST as $var => $val) {
			if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				$name = db_fetch_cell_prepared('SELECT name
					FROM gridalarms_template_expression
					WHERE id = ?',
					array($matches[1]));

				$gridalarms_expression_list .= '<li>' . html_escape($name) . '</li>';
				$gridalarms_expression_array[$i] = $matches[1];

				$i++;
			}
		}

		top_header();

		form_start('gridalarms_template_edit.php');

		html_start_box($gridalarms_expression_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		if (isset($gridalarms_expression_array) && cacti_sizeof($gridalarms_expression_array)) {
			if (get_request_var('drp_action') == '1') { /* delete */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Delete the following Expressions.', 'gridalarms') . "</p>
						<div class='itemlist'><ul>$gridalarms_expression_list</ul></div>
					</td>
				</tr>\n";

				$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete expressions'>";
			} elseif (get_request_var('drp_action') == '2') { /* Duplicate */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Duplicate the following Expressions. You can optionally change the Title format for the new expressions.', 'gridalarms') . "</p>
						<div class='itemlist'><ul>$gridalarms_expression_list</ul></div>
						<p><strong>" . __('Title Format', 'gridalarms') . "</strong><br>"; form_text_box('title_format', '<gridalarms_expression_title> (1)', '', '255', '30', 'text'); print "</p>
					</td>
				</tr>\n";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Duplicate Expressions', 'gridalarms') . "'>";
			}
		} else {
			raise_message(40);
			header('Location: gridalarms_template_edit.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
			exit;
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($gridalarms_expression_array) ? serialize($gridalarms_expression_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	} else {
		/* if we are to save this form, instead of display it */
		if (isset_request_var('selected_items')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if ($selected_items != false) {
				if (get_request_var('drp_action') == '1') { /* delete */
					for ($i=0; $i<count($selected_items); $i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						gridalarms_metric_remove($selected_items[$i], get_request_var('expression_id'));
					}
				}
			}

			header('Location: gridalarms_template_edit.php?header=fasle');
		}

		/* setup some variables */
		$metric_list = ''; $i = 0;

		/* loop through each of the resources selected on the previous page and get more info about them */
		foreach ($_POST as $var => $val) {
			if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				$metric_info = db_fetch_cell_prepared('SELECT name
					FROM gridalarms_template_metric
					WHERE id = ?',
					array($matches[1]));

				$metric_list .= '<li>' . html_escape($metric_info) . '</li>';
				$metric_array[$i] = $matches[1];
			}

			$i++;
		}

		top_header();

		html_start_box($gridalarms_metric_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

		form_start('gridalarms_template_edit.php');

		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following Metrics.', 'gridalarms') . "</p>
					<div class='itemlist'><ul>$metric_list</ul></div>
				</td>
			</tr>\n";
		}

		if (!isset($metric_array)) {
			raise_message(40);
			header('Location: gridalarms_template_edit.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
			exit;
		}

		$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Delete Metric', 'gridalarms') . "'>";

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($metric_array) ? serialize($metric_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	}

	bottom_footer();
}

/* --------------------------
    gridalarms_template_expression Item Functions
   -------------------------- */

function item_movedown() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('expression_id');
	/* ==================================================== */

	move_item_down('gridalarms_template_expression_item', get_request_var('id'), 'expression_id=' . get_request_var('expression_id'));
	template_propagation ("gridalarms_template", get_request_var('alarm_id'));
}

function item_moveup() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	get_filter_request_var('expression_id');
	/* ==================================================== */

	move_item_up('gridalarms_template_expression_item', get_request_var('id'), 'expression_id=' . get_request_var('expression_id'));

	template_propagation ("gridalarms_template", get_request_var('alarm_id'));
}

function confirm_layout_remove() {
	global $config, $gridalarms_expression_item_types;

	get_filter_request_var('id');

	top_header();

	form_start('gridalarms_template_edit.php');

	html_start_box(__('Layout Column Remove', 'gridalarms'), '60%', '', '3', 'center', '');

	print "<tr>
		<td class='textArea'>
			<p>" . __('Click \'Continue\' to Delete this Layout Column <b>\'%s\'</b>.', html_escape_request_var('column'), 'gridalarms') . "</p>
		</td>
	</tr>\n";

	$cancel_url = html_escape('gridalarms_template_edit.php?action=edit&tab=layout&id=' . get_request_var('id'));

	$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo(\"$cancel_url\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Delete Layout Column', 'gridalarms') . "'>";

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='layout_remove'>
			<input type='hidden' name='id' value='" . get_request_var('id') . "'>
			<input type='hidden' name='column' value='" . html_escape_request_var('column') . "'>
			<input type='hidden' name='confirm_layout_remove' value='1'>
			$save_html
		</td>
	</tr>\n";

	html_end_box();

	form_end();

	bottom_footer();
}

function confirm_item_remove() {
	global $config, $gridalarms_expression_item_types;

	get_filter_request_var('id');
	get_filter_request_var('alarm_id');
	get_filter_request_var('expression_id');

	top_header();

	$cancel_url = html_escape('gridalarms_template_edit.php?action=edit&tab=data&id=' . get_request_var('alarm_id'));

	form_start('gridalarms_template_edit.php', 'chk');

	if (get_request_var('type') == 'expression') {
		$item = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_expression_item
			WHERE id = ?',
			array(get_request_var('id')));

		$name = '<em>' . $gridalarms_expression_item_types[$item['type']] . ':</em> ';

		if ($item['type'] == 3) {
			$metric = get_metric($item['value']);
			$name  .= html_escape($metric['name']);
		} else {
			$name  .= html_escape($item['value']);
		}

		html_start_box(__('Expression Item Delete', 'gridalarms'), '60%', '', '3', 'center', '');

		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete this Data Source Item <b>\'%s\'</b>.', $name, 'gridalarms') . "</p>
			</td>
		</tr>";

		$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo(\"$cancel_url\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Delete Expression Item', 'grialarms') . "'>";

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='item_remove'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='alarm_id' value='" . get_request_var('alarm_id') . "'>
				<input type='hidden' name='expression_id' value='" . get_request_var('expression_id') . "'>
				<input type='hidden' name='confirm_item_remove' value='1'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	} else if (get_request_var('type') == 'input'){
		html_start_box(__('Custom Data Input Item Remove', 'gridalarms'), '60%', '', '3', 'center', '');

		$name = db_fetch_cell_prepared('SELECT name
			FROM gridalarms_template_expression_input
			WHERE id = ?',
			array(get_request_var('id')));

		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete this Custom Data Input Item <b>\'%s\'</b>.', html_escape($name), 'gridalarms') . "</p>
			</td>
		</tr>";

		$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo(\"$cancel_url\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Delete Custom Data Input Item', 'grialarms') . "'>";

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='item_remove'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='alarm_id' value='" . get_request_var('alarm_id') . "'>
				<input type='hidden' name='expression_id' value='" . get_request_var('expression_id') . "'>
				<input type='hidden' name='confirm_input_remove' value='1'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	} else {
		html_start_box(__('Expression Metric Remove', 'gridalarms'), '60%', '', '3', 'center', '');

		$name = db_fetch_cell_prepared('SELECT name
			FROM gridalarms_template_metric
			WHERE id = ?',
			array(get_request_var('id')));

		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete this Data Source Metric <b>\'%s\'</b>.', html_escape($name), 'gridalarms') . "</p>
			</td>
		</tr>\n";

		$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo(\"$cancel_url\")'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Delete Expression Metric', 'grialarms') . "'>";

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='item_remove'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='alarm_id' value='" . get_request_var('alarm_id') . "'>
				<input type='hidden' name='expression_id' value='" . get_request_var('expression_id') . "'>
				<input type='hidden' name='confirm_metric_remove' value='1'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	}

	form_end();

	bottom_footer();
}

function item_remove() {
	if (isset_request_var('confirm_item_remove')) {
		/* ================= input validation ================= */
		get_filter_request_var('id');
		get_filter_request_var('expression_id');
		/* ==================================================== */

		db_execute_prepared('DELETE FROM gridalarms_template_expression_item
			WHERE id = ?',
			array(get_request_var('id')));

		template_propagation ("gridalarms_template", get_request_var('alarm_id'));
	}  else if (isset_request_var('confirm_input_remove')){
		/* ================= input validation ================= */
		get_filter_request_var('id');
		get_filter_request_var('expression_id');
		/* ==================================================== */

		gridalarms_input_remove(get_request_var('id'), get_request_var('expression_id'));
		template_propagation ("gridalarms_template", get_request_var('alarm_id'));
	} else {
		/* ================= input validation ================= */
		get_filter_request_var('id');
		get_filter_request_var('expression_id');
		/* ==================================================== */

		gridalarms_metric_remove(get_request_var('id'), get_request_var('expression_id'));

		template_propagation ("gridalarms_template", get_request_var('alarm_id'));
	}
}

function get_template_data_input_by_id($expression_id) {
	return array_rekey(
		db_fetch_assoc_prepared('SELECT name
			FROM gridalarms_template_expression_input
			WHERE expression_id = ?
			ORDER BY name',
			array($expression_id)),
		'name', 'name'
	);
}

function item_edit() {
	global $gridalarms_expression_item_types, $gridalarms_expression_operators, $gridalarms_expression_comparison_operators, $gridalarms_expression_join_operators, $gridalarms_expression_brackets_operators;

	get_filter_request_var('id');
	get_filter_request_var('expression_id');
	get_filter_request_var('alarm_id');

	if (!isempty_request_var('id')) {
		$expression_item = get_template_expression_item(get_request_var('id'));
		$current_type = $expression_item['type'];
		$values[$current_type] = $expression_item['value'];
	}

	if ((isset($expression_item) && $expression_item['type'] == 7) || (isset_request_var('type_select') && get_request_var('type_select') == 7)) {
		$replacement_vars['clusterid'] = 'clusterid';
		$replacement_vars['clustername'] = 'clustername';
		$replacement_vars += get_template_data_input_by_id(get_request_var('expression_id'));
	}

	if (!isempty_request_var('expression_id')) {
		$expression = get_template_expression_by_id(get_request_var('expression_id'));
	}

	html_start_box('', '100%', '', '3', 'center', '');
	draw_gridalarms_expression_preview(get_request_var('expression_id'));
	html_end_box();

	form_start('gridalarms_template_edit.php', 'chk');

	$name = db_fetch_cell_prepared('SELECT name
		FROM gridalarms_template_expression
		WHERE id = ?',
		array(get_request_var('expression_id')));

	if ($name != '') {
		$header_label = __esc('Expression Items [edit: %s]', $name, 'gridalarms');
	} else {
		$header_label = __('Expression Items [new]', 'gridalarms');
	}

	html_start_box($header_label, '100%', '', '3', 'center', '');

	if (isset_request_var('type_select')) {
		$current_type = get_request_var('type_select');
	} elseif (isset($expression_item['type'])) {
		$current_type = $expression_item['type'];
	} else {
		$current_type = '0';
	}

	$fields_edit = array(
		'type_select' => array(
			'method' => 'drop_array',
			'friendly_name' =>  __('Item Type', 'gridalarms'),
			'description' => __('Choose what type of expression item this is.', 'gridlarms'),
			'default' => 0,
			'on_change' => 'applyExpression()',
			'array' => $gridalarms_expression_item_types,
			'value' => $current_type
		)
	);

	switch ($current_type) {
	case '0': // Operators
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Operator Value', 'gridalarms'),
				'description' => __('Operators include simple math functions that will be included as a part of the eventual SQL syntax.', 'gridlarms'),
				'default' => '',
				'array' => $gridalarms_expression_operators,
				'value' => (isset($expression_item['value']) ? $expression_item['value']:'')
			)
		);

		break;
	case '1': // Comparison Operators
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Comparison Value', 'gridalarms'),
				'description' => __('Comparison Operators allow you to compare two Metrics to one another or a Metric to a known value..', 'gridlarms'),
				'default' => '',
				'array' => $gridalarms_expression_comparison_operators,
				'value' => (isset($expression_item['value']) ? $expression_item['value']:'')
			)
		);

		break;
	case '2': // Join values
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Join Value', 'gridalarms'),
				'description' => __('Join Values include are ways to connect two logical conditions together in the SQL WHERE.', 'gridlarms'),
				'default' => '',
				'array' => $gridalarms_expression_join_operators,
				'value' => (isset($expression_item['value']) ? $expression_item['value']:'')
			)
		);

		break;
	case '3': // Metric Value
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Metric Value', 'gridalarms'),
				'description' => __('The Metric Value includes columns from the specific Table included in the Expression.', 'gridlarms'),
				'default' => '',
				'array' => get_gridalarms_template_metrics(isset($expression) ? $expression['db_table'] : ''),
				'value' => (isset($expression_item['value']) ? $expression_item['value'] : '')
			)
		);

		break;
	case '4': // Numerical List
		$fields_edit += array(
			'value' => array(
				'method' => 'textbox',
				'friendly_name' =>  __('Numeric/List Value', 'gridalarms'),
				'description' => __('A numeric value list includes a number of numbers that you want included in a SQL IN clause.', 'gridlarms'),
				'value' => (isset($expression_item['value']) ? $expression_item['value'] : ''),
				'max_length' => '255',
				'size' => '60'
	        )
		);

		break;
	case '5': // String Value
		$fields_edit += array(
			'value' => array(
				'method' => 'textbox',
				'friendly_name' =>  __('String Value', 'gridalarms'),
				'description' => __('A string value to be compared to Metric or computed value.', 'gridlarms'),
				'value' => (isset($expression_item['value']) ? $expression_item['value'] : ''),
				'max_length' => '255',
				'size' => '60'
	        )
		);

		break;
	case '6': // Bracket Operator
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Bracket Operator Value', 'gridalarms'),
				'description' => __('Bracket Operators allows the construction of complete SQL WHERE clauses.', 'gridlarms'),
				'default' => '',
				'array' => $gridalarms_expression_brackets_operators,
				'value' => (isset($expression_item['value']) ? $expression_item['value']:'')
			)
		);

		break;
	case '7':
		$fields_edit += array(
			'value' => array(
				'method' => 'drop_array',
				'friendly_name' =>  __('Custom Data Value', 'gridalarms'),
				'description' => __('Custom values are created with the Alert is created, but can default to the value in the Alert Template.', 'gridlarms'),
				'default' => '',
				'array' => $replacement_vars,
				'value' => (isset($expression_item['value']) ? $expression_item['value']:'')
			)
		);

		break;
	}

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
   			'fields' => $fields_edit
        )
	);

	$url = 'gridalarms_template_edit.php?action=item_edit&header=false' . (isset_request_var('id') ? '&id=' . get_request_var('id') : '') . '&expression_id=' . get_request_var('expression_id') . '&alarm_id=' . get_request_var('alarm_id');

	?>
	<script type='text/javascript'>
	function applyExpression() {
		loadPageNoHeader('<?php print $url;?>&type_select=' + $('#type_select').val());
	}

	$(function() {
		$('#type_select').change(function() {
			applyExpression();
		});
	});
	</script>
	<?php

	form_hidden_box('id', (isset_request_var('id') ? get_request_var('id') : '0'), '');
	form_hidden_box('type', $current_type, '');
	form_hidden_box('expression_id', get_request_var('expression_id'), '');
	form_hidden_box('alarm_id', get_request_var('alarm_id'), '');
	form_hidden_box('save_component_item', '1', '');

	html_end_box();

	form_save_button('gridalarms_template_edit.php?tab=data&header=false&id=' . get_request_var('alarm_id'));
}

/* ---------------------
   Expression Functions
   --------------------- */

function gridalarms_expression_edit() {
	global $config, $gridalarms_expression_item_types;

	get_filter_request_var('id');

	$expression    = array();
	$header_label  = __('Data Source Edit [new]', 'gridalarms');

	if (!isempty_request_var('id')) {
		$expression_id = db_fetch_cell_prepared('SELECT expression_id
			FROM gridalarms_template
			WHERE id = ?',
			array(get_request_var('id')));

		if ($expression_id > 0) {
			$expression    = db_fetch_row_prepared('SELECT *
				FROM gridalarms_template_expression
				WHERE id = ?',
				array($expression_id));

			$header_label  = __('Data Source Edit', 'gridalarms');
		}
	}

	form_start('gridalarms_template_edit.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$form_array = build_expression_edit_form($expression);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($form_array, $expression)
		)
	);

	html_end_box();

	form_save_button('gridalarms_template_edit.php', 'save');

	print '<br>';

	if (!empty($expression['id']) && $expression['ds_type'] == 0) {
		html_start_box('', '100%', '', '3', 'center', '');
		draw_gridalarms_expression_preview($expression['id']);
		html_end_box();

		$display_text = array(
			__('Item', 'gridalarms'),
			__('Item Value', 'gridalarms'),
		);

		html_start_box($header_label, '100%', '', '3', 'center', 'gridalarms_template_edit.php?action=item_edit&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id'));

		html_header($display_text, 2);

		$items = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_expression_item
			WHERE expression_id = ?
			ORDER BY sequence',
			array($expression['id']));

		$i = 1;
		$total_items = cacti_sizeof($items);
		if ($total_items) {
			foreach ($items as $item) {
				form_alternate_row();

				form_selectable_cell(filter_value(__('Item #%d', $i, 'gridalarms'), '', 'gridalarms_template_edit.php?action=item_edit&id=' . $item['id'] . '&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id')), $item['id']);

				$type = $gridalarms_expression_item_types[$item['type']] . ": ";
				if ($item['type'] == 3) {
					$metric = get_template_metric($item['value']);
					$output = html_escape($metric['name']) ;
				} elseif ($item['type'] == 5) {
					$output = html_escape("'" . trim($item['value'], '\'" ') . "'");
				} elseif ($item['type'] == 7) {
					switch ($item['value']) {
						case 'clusterid':
						case 'clustername':
							$output = '|alert_' . html_escape($item['value']) . '|';
							break;
						default:
							$output = '|input_' . html_escape($item['value']) . '|';
							break;
					}
				} else {
					$output = html_escape($item['value']);
				}

				form_selectable_cell('<em>' . $type . '</em> <b>' . $output . '</b>', $item['id']);

				$move = '';
				if ($i < $total_items) {
					$move .= "<a class='pic fa fa-caret-down moveArrow' href='" . html_escape('gridalarms_template_edit.php?action=item_movedown&id=' . $item['id'] . '&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id')) . "'></a>";
				} else {
					$move .= '<span class="moveArrowNone"></span>';
				}

				if ($i > 1 && $i <= $total_items) {
					$move .= "<a class='pic fa fa-caret-up moveArrow' href='" . html_escape('gridalarms_template_edit.php?action=item_moveup&id=' . $item['id'] . '&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id')) . "'></a>";
				} else {
					$move .= '<span class="moveArrowNone"></span>';
				}

				$move .= "<a class='pic fa fa-times deleteMarker' href='" . html_escape('gridalarms_template_edit.php?action=item_remove&id=' . $item['id'] . '&expression_id=' . $expression['id'] . '&type=expression' . '&alarm_id=' . get_request_var('id')) . "'></a>";

				form_selectable_cell($move, $item['id'], '', 'right');

				form_end_row();

				$i++;
			}
		} else {
			echo "<tr><td colspan='4'><em>" . __('Data Source Items Found', 'gridalarms') . "</em></td></tr>";
		}

		html_end_box();

		html_start_box(__('Data Source Metrics', 'gridalarms'), '100%', '', '3', 'center', 'gridalarms_template_edit.php?action=metric_edit&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id'));

		$display_text = array(
			__('Item', 'gridalarms'),
			__('Display Name', 'gridalarms'),
			__('Column Name', 'gridalarms'),
			__('Descriptions', 'gridalarms'),
			__('Shared', 'gridalarms')
		);

		html_header($display_text, 2);

		$metrics = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_metric AS gm
			INNER JOIN gridalarms_template_metric_expression AS gme
			ON gm.id=gme.metric_id
			WHERE gme.expression_id = ?
			ORDER BY db_column',
			array($expression['id']));

		$i = 1;
		if (cacti_sizeof($metrics)) {
			foreach ($metrics as $m) {
				$shared = db_fetch_cell_prepared('SELECT count(*)
					FROM gridalarms_template_metric_expression
					WHERE metric_id = ?',
					array($m['id']));

				$del_url = html_escape('gridalarms_template_edit.php' .
					'?action=item_remove' .
					'&id=' . $m['id'] .
					'&expression_id=' . $expression['id'] .
					'&type=metric' .
					'&alarm_id=' . get_request_var('id'));

				form_alternate_row('line' . $m['id'], true);

				form_selectable_cell(filter_value(__('Item #%s', $i, 'gridalarms'), '', 'gridalarms_template_edit.php?action=metric_edit&id=' . $m['id'] . '&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id')), $m['id']);
				form_selectable_cell(html_escape($m['name']), $m['id']);
				form_selectable_cell(html_escape($m['db_column']), $m['id']);
				form_selectable_cell(html_escape($m['description']), $m['id']);
				form_selectable_cell($shared > 1 ? __('Yes', 'gridalarms'):__('No', 'gridalarms'), $m['id']);
				form_selectable_cell('<a class="pic delete deleteMarker fa fa-times" href="' . $del_url . '"></a>', $m['id'], '', 'right');

				form_end_row();

				$i++;
			}
		} else {
			echo "<tr><td colspan='6'><em>" . __('No Metrics Found', 'gridalarms') . "</em></td></tr>";
		}

		html_end_box();
	}

	if (!empty($expression['id']) && $expression['ds_type'] == 1) {
		html_start_box('', '100%', '', '3', 'center', '',true);
		draw_gridalarms_sql_preview($expression);
		html_end_box();
	}

	if (!empty($expression['id']) && $expression['ds_type'] == 2) {
		html_start_box('', '100%', '', '3', 'center', '');
		draw_gridalarms_script_preview($expression);
		html_end_box();
	}

	if (!empty($expression['id'])) {
		//Before html_start_box support tooltip of caption, hardcode combine HTML format tooltip with caption
		$cdii_caption_tip = __('Custom Data Input Items', 'gridalarms') . '<div class="formTooltip">' . display_tooltip(__('You can define two types of Custom Data Input items in your Queries, Scripts, and Tables. <ul> <li>To pull data from an instantiated Alert: you can include<b> |alert_clusterid| </b> and <b>|alert_clustername|</b> (must be cluster associated). </li> <li>To define input variables: you can use the format <b>|input_variable_name|</b>, where \'variable_name\' is replaced with the input variable name. </li></ul>', 'gridalarms')) . '</div>';
		html_start_box($cdii_caption_tip, '100%', '', '3', 'center', 'gridalarms_template_edit.php?action=input_edit&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id'));

		$display_text = array(
			__('Item', 'gridalarms'),
			__('Name', 'gridalarms'),
			__('Description', 'gridalarms'),
			__('Value', 'gridalarms')
		);

		html_header($display_text, 2);

		$eitems = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_expression_input AS ei
			WHERE ei.expression_id = ?
			ORDER BY name',
			array($expression['id']));

		$i = 1;
		if (cacti_sizeof($eitems)) {
			foreach ($eitems as $e) {
				$del_url = html_escape('gridalarms_template_edit.php' .
					'?action=item_remove' .
					'&id=' . $e['id'] .
					'&expression_id=' . $expression['id'] .
					'&type=input' .
					'&alarm_id=' . get_request_var('id'));

				form_alternate_row('line' . $e['id']);

				form_selectable_cell(filter_value(__('Item #%s', $i, 'gridalarms'), '', 'gridalarms_template_edit.php?action=input_edit&id=' . $e['id'] . '&expression_id=' . $expression['id'] . '&alarm_id=' . get_request_var('id')), $e['id']);
				form_selectable_cell(html_escape($e['name']), $e['id']);
				form_selectable_cell(html_escape($e['description']), $e['id']);
				form_selectable_cell(html_escape($e['value']), $e['id']);
				form_selectable_cell('<a class="pic delete deleteMarker fa fa-times" href="' . $del_url . '"></a>', $e['id'], '', 'right');

				form_end_row();

				$i++;
			}
		} else {
			echo "<tr><td colspan='5'><em>" . __('No Data Input Items Found', 'gridalarms') . "</em></td></tr>";
		}

		html_end_box();
	}

	include($config['base_path'] . '/plugins/gridalarms/includes/arrays.php');
	?>
	<script type='text/javascript'>

	$(function() {
		$('select#type_display').change(function() {
			$('select#db_table').html('<?php print urlencode('<option>' . __('Please wait ...', 'gridalarms') . '</option>');?>');
			var type_value = $(this).val();
			if (type_value != '') {
				$('#type').val(type_value);
				$.get('gridalarms_template_edit.php',{'action':'gettables', 'type': type_value}, function(data){
					$('#db_table').html(data).trigger('change').selectmenu('refresh');
					// Get first visible option text and update the displayed text
    				$("#db_table-button .ui-selectmenu-text").text($("#db_table option:selected").text());  
				});
			}
		});

		$('select#ds_type_display').change(function() {
			setDsType('ds_type_display');
			$('#ds_type').val($('#ds_type_display').val());
		});

        function syntaxChecker(expression_id, action, file) {
            if (expression_id != '') {
                $('#syntax_message').empty().html("<p style='margin:5px 0px;min-height:15px'><span class='deviceUp fa fa-sync fa-spin'></span>&nbsp;<?php print __('Processing ...', 'gridalarms');?></p>");
                $.get(file, {'action':action, 'id': expression_id}, function(data) {
                    $('#syntax_message').empty().html(data);
                });
            }
        }

		$('#check_syntax').click(function() {
			expression_id = $('#check_syntax_expression_id').val();
			syntaxChecker(expression_id, 'check_syntax', 'gridalarms_template_edit.php');
		});

		$('#check_syntax_sql').click(function() {
			expression_id = $('#check_syntax_expression_id').val();
			syntaxChecker(expression_id, 'check_syntax_sql', 'gridalarms_template_edit.php');
		});

		$('#check_sql_syntax').click(function() {
			expression_id = $('#check_syntax_expression_id').val();
			syntaxChecker(expression_id, 'check_sql_syntax', 'gridalarms_template_edit.php');
		});

		$('#check_script_threshold').click(function() {
			expression_id = $('#check_syntax_expression_id').val();
			syntaxChecker(expression_id, 'check_script_threshold', 'gridalarms_template_edit.php');
		});

		$('#check_script_data').click(function() {
			expression_id = $('#check_syntax_expression_id').val();
			syntaxChecker(expression_id, 'check_script_data', 'gridalarms_template_edit.php');
		});

		setDsType('ds_type');
	});

	</script>
	<?php
}

function gridalarms_metric_save() {
	if (!isset_request_var('id') || trim(get_request_var('id')) == '') {
		$exists = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_metric
			WHERE name = ?',
			array(get_request_var('name')));

		if (cacti_sizeof($exists)) {
			raise_message('gridalarms_metric_name_exists');
			return false;
		}
	}

	$save['id']          = get_request_var('id');
	$save['name']        = form_input_validate(get_request_var('name'), 'name', '', false, 3);
	$save['description'] = get_request_var('description');
	$save['type']        = form_input_validate(get_request_var('type'), 'type', '', false, 3);
	$save['db_table']    = form_input_validate(get_request_var('db_table'), 'db_table', '', false, 3);
	$save['db_column']   = form_input_validate(get_request_var('db_column'), 'db_column', '', false, 3);

	if (!is_error_message()) {
		$id = sql_save($save, 'gridalarms_template_metric', 'id');

		if ($id) {
			raise_message('gridalarms_metric_save_successful');

			kill_session_var('metric_session');

			return true;
		} else {
			raise_message('gridalarms_metric_save_failed');

			return false;
		}
	} else {

		return false;
	}
}

function get_metric_type($db_table) {
	global $job_tables, $host_tables, $queue_tables, $user_tables, $cluster_tables, $license_tables;

	if (isset($job_tables[$db_table])) {
		return 1;
	} elseif (isset($host_tables[$db_table])) {
		return 2;
	} elseif (isset($queue_tables[$db_table])) {
		return 4;
	} elseif (isset($user_tables[$db_table])) {
		return 8;
	} elseif (isset($cluster_tables[$db_table])) {
		return 16;
	} elseif (isset($license_tables[$db_table])) {
		return 32;
	} else {
		return 64;
	}
}

function build_edit_input_form($input, $expression_id) {
	$name_value        = '|arg1:name|';
	$description_value = '|arg1:description|';
	$value             = '|arg1:value|';

	$fields_edit = array(
		'name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Name', 'gridalarms'),
			'description' => __('Enter the name for this Custom Input Item. When used in Queries and Scripts, the replacement value will appear as <em>|input_variable_name|</em>', 'gridalarms'),
			'value' => $name_value,
			'size' => 20,
			'max_length' => '20'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => __('Description', 'gridalarms'),
			'description' => __('Enter a description for this Custom Input Item.', 'gridalarms'),
			'value' => $description_value,
			'size' => '60',
			'max_length' => '120'
		),
		'value' => array(
			'method' => 'textbox',
			'friendly_name' => __('Default Value', 'gridalarms'),
			'description' => __('Enter a value specific (example: host name, # of hosts) to Custom Data Input items. This value can be changed while instantiating an alert.', 'gridalarms'),
			'value' => $value,
			'size' => '50',
			'max_length' => '128'
		),
		'id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('id') ? get_request_var('id') : ''
		),
		'expression_id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('expression_id') ? get_request_var('expression_id') : ''
		),
		'alarm_id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('alarm_id') ? get_request_var('alarm_id') : ''
		),
		'save_component_data_input' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	return $fields_edit;
}

function build_edit_form($metric, $expression_id) {
	global $metric_types, $cluster_tables, $host_tables, $queue_tables, $user_tables, $job_tables;

	$name_value        = '|arg1:name|';
	$description_value = '|arg1:description|';
	$type_value        = '|arg1:type|';
	$db_table_value    = '|arg1:db_table|';
	$db_column_value   = '|arg1:db_column|';

	/* load from session variables */
	if (isset($metric) && cacti_sizeof($metric)) { /* existing metric */
		$ds_type = db_fetch_cell_prepared('SELECT ds_type
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($expression_id));

		$db_table_value = db_fetch_cell_prepared('SELECT db_table
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($expression_id));

		$type_value     = get_metric_type($db_table_value);
		$db_tables      = get_db_tables_form($metric['type']);
		$db_columns     = get_free_expression_table_columns($expression_id, $db_table_value, 'template', $metric['id']);
	} else { /* new metric */
		$ds_type = db_fetch_cell_prepared('SELECT ds_type
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($expression_id));

		if ($ds_type == 0) {
			$db_table_value = db_fetch_cell_prepared('SELECT db_table
				FROM gridalarms_template_expression
				WHERE id = ?',
				array($expression_id));

			$type_value     = get_metric_type($db_table_value);
			$db_tables      = get_db_tables_form($type_value);
			$db_columns     = get_free_expression_table_columns($expression_id, $db_table_value, 'template');
		} elseif ($ds_type == 1) {
			$db_tables      = array();
			$db_columns     = gridalarms_get_template_sql_columns($expression_id);
			$db_table_value = '';
			$type_value     = '';
		} elseif ($ds_type == 2) {
			$db_tables      = array();
			$db_columns     = gridalarms_get_template_script_columns($expression_id);
			$db_table_value = '';
			$type_value     = '';
		}
	}

	$fields_edit = array(
		'name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Name', 'gridalarms'),
			'description' => __('Enter a meaningful name for this metric.', 'gridalarms'),
			'value' => $name_value,
			'max_length' => '255'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => __('Description', 'gridalarms'),
			'description' => __('Enter a meaningful description for this metric.', 'gridalarms'),
			'value' => $description_value,
			'max_length' => '255'
		),
		'type_display' => array(
			'method' => '',
			'friendly_name' => __('Data Type', 'gridalarms'),
			'description' => __('The type of Metric you want to collect.  This will determine the available tables for selection.', 'gridalarms'),
			'value' => $metric_types[$type_value]
		),
		'type' => array(
			'method' => 'hidden',
			'value' => $type_value
		),
		'db_table_display' => array(
			'method' => '',
			'friendly_name' => __('Table Name', 'gridalarms'),
			'description' => __('The database table which this Metric belongs to.  This will determine the available columns for selection.', 'gridalarms'),
			'value' => $db_table_value
		),
		'db_table' => array(
			'method' => 'hidden',
			'value' => $db_table_value
		),
		'db_column' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Column Name', 'gridalarms'),
			'description' => __('The Column name that this Metric represents.', 'gridalarms'),
			'array' => $db_columns,
			'value' => $db_column_value
		),
		'id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('id') ? get_request_var('id') : ''
		),
		'expression_id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('expression_id') ? get_request_var('expression_id') : ''
		),
		'alarm_id' => array(
			'method' => 'hidden',
			'value' => isset_request_var('alarm_id') ? get_request_var('alarm_id') : ''
		),
		'save_component_metric' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	return $fields_edit;
}

function gridalarms_input_edit() {
	$expression_id = get_request_var('expression_id');

	if (isset_request_var('id') && !isempty_request_var('id')) {
		$input = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_expression_input
			WHERE id = ?',
			array(get_request_var('id')));

		$header_label = __esc('Custom Data Input Item [edit: %s]', $input['name'], 'gridalarms');
	} else {
		$input = array();
		$header_label = __esc('Custom Data Input Item [new]', 'gridalarms');
	}

	form_start('gridalarms_template_edit.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$form_array = build_edit_input_form($input, $expression_id);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($form_array, $input)
		)
	);

	html_end_box();

	form_save_button('gridalarms_template_edit.php?tab=data&id=' . get_request_var('alarm_id'), '', 'id');
}

function gridalarms_metric_edit() {
	$expression_id = get_filter_request_var('expression_id');

	$ds_type = db_fetch_cell_prepared('SELECT ds_type
		FROM gridalarms_template_expression
		WHERE id = ?',
		array($expression_id));

	?>
	<script type='text/javascript'>

	var ds_type=<?php print $ds_type;?>;

	$(function() {
		if (ds_type == 1) {
			$('#row_db_table').hide();
			$('#row_db_table_display').hide();
			$('#row_type').hide();
			$('#row_type_display').hide();
		}else if (ds_type == 2) {
			$('#row_db_table').hide();
			$('#row_db_table_display').hide();
			$('#row_type').hide();
			$('#row_type_display').hide();
		}

		$('select#type').change(function() {
			var type_value = $(this).val();
			$('select#db_table').html('<?php print urlencode('<option>' . __('Please wait ...', 'gridalarms') . '</option>');?>');
			$('select#db_column').html('<?php print urlencode('<option>' . __('Please wait ...', 'gridalarms') . '</option>');?>');
			if (type_value != '') {
				$.get('gridalarms_template_edit.php',{'action':'gettables', 'type': type_value}, function(data){
					$('select#db_table').html(data);
				});

				var table_name = '';
				if (type_value == '0') {
					table_name = 'grid_jobs';
				}

				if (type_value == '1') {
					table_name = 'grid_hosts';
				}

				if (type_value == '2') {
					table_name = 'grid_queues';
				}

				if (type_value == '3') {
					table_name = 'grid_user_group_stats';
				}

				if (type_value == '4') {
					table_name = 'grid_clusters';
				}

				if (type_value == '5') {
					table_name = 'cdef';
				}

				$.get('gridalarms_template_edit.php',{'action':'getcolumns', 'db_table':table_name}, function(data){
					$('select#db_column').html(data);
				});
			}
		});

		$('select#db_table').change(function() {
			$('select#db_column').html('<?php print urlencode('<option>' . __('Please wait ...', 'gridalarms') . '</option>');?>');
			var db_table_value = $(this).val();
			if (db_table_value != '') {
				$.get('gridalarms_template_edit.php',{'action':'getcolumns', 'db_table': db_table_value}, function(data){
					$('select#db_column').html(data);
				});
			}
		});
	});

	</script>
	<?php

	if (isset_request_var('id') && !isempty_request_var('id')) {
		$metric = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_metric
			WHERE id = ?',
			array(get_request_var('id')));

		if (cacti_sizeof($metric)) {
			$header_label = __esc('Alert Metric [edit: %s]', $metric['name'], 'gridalarms');
		} else {
			$header_label = __('Alert Metric [edit: %s]', __('Unknown Metric', 'gridalarms'), 'gridalarms');
		}
	} else {
		$metric = array();

		$header_label = __('Alert Metric [new]', 'gridalarms');
	}

	form_start('gridalarms_template_edit.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$form_array = build_edit_form($metric, $expression_id);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($form_array, $metric)
		)
	);

	html_end_box();

	form_save_button('gridalarms_template_edit.php?tab=data&id=' . get_request_var('alarm_id'), '', 'id');
}

function get_metrics_for_alarm_form() {
	if (isset_request_var('expression_id') && trim(get_request_var('expression_id')) != '') {
		$expression_metrics = get_metrics_from_cache_by_expression_template_id(get_request_var('expression_id'));

		foreach($expression_metrics as $metric) {
			print "<option value='" . html_escape($metric) . "'>" . html_escape($metric) . '</option>';
		}
	}
}

function alarm_save_contacts ($id, $contacts) {
	db_execute_prepared('DELETE FROM gridalarms_template_contacts
		WHERE alarm_id = ?',
		array($id));

	// ADD SOME SECURITY!!
	$users = explode(' ', $contacts);
	foreach ($users as $user) {
		if (trim($user) != '') {
			db_execute_prepared('INSERT INTO gridalarms_template_contacts
				(alarm_id, contact_id) VALUES
				(?, ?)',
				array($id, $user));
		}
	}
}

/**
 * Format expression into form format.
 * @param Array $expressions
 */
function format_expression_for_form($expressions) {
	$result = array();
	foreach ($expressions as $expression) {
		$result[$expression['id']] = $expression['name'];
	}
	return $result;
}

function gridalarms_display_tabs($current_tab) {
	global $config;

	get_filter_request_var('id');

	/* present a tabbed interface */
	$tabs = array(
		'general' => __('General', 'gridalarms'),
		'actions' => __('Actions', 'gridalarms')
	);

	/* if the alarm has a data source, show the tab */
	if (get_request_var('id') > 0 || get_request_var('action') == 'expression_edit') {
		$dsid       = 0;
		$alarm      = get_template_by_id(get_request_var('id'));
		$expression = get_template_expression_by_id($alarm['expression_id']);

		if (isset_request_var('id') && get_request_var('id') > 0) {
			$dsid = db_fetch_cell_prepared('SELECT expression_id
				FROM gridalarms_template
				WHERE id = ?',
				array(get_request_var('id')));
		}

		if ($dsid > 0 || get_request_var('action') == 'expression_edit' || ($alarm['id'] > 0 && empty($alarm['expression_id'])) ) {
			$tabs['data'] = __('Data Source', 'gridalarms');
		}

		if ($dsid > 0) {
			if (cacti_sizeof($expression) && $expression['ds_type'] < 2) {
				$tabs['layout'] = __('Layout', 'gridalarms');
			}

			$tabs['breached'] = __('Breached Items', 'gridalarms');
		}
	}

	if (isset_request_var('tab')) {
		$current_tab = get_request_var('tab');
		if (get_request_var('tab') == 'current') {
			$tabs['current'] = __('Selected Alert', 'gridalarms');
		}
	} else {
		$current_tab = 'general';
	}

	/* draw the tabs */
	print "<div class='tabs'><nav><ul>\n";

	if (cacti_sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li><a class='tab" . (($tab_short_name == $current_tab) ? " selected'" : "'") .
				" href='" . html_escape($config['url_path'] .
				'/plugins/gridalarms/gridalarms_template_edit.php?action=edit' .
				'&tab=' . $tab_short_name .
				'&id=' . get_request_var('id')) .
				"'>" . $tabs[$tab_short_name] . '</a></li>';
		}
	}

	print "</ul></nav></div>";
}

function get_metrics_from_cache_by_expression_template_id($expression_id) {
	if (isset($_SESSION['sess_gat_expr']['expr_tmpl_' . $expression_id])){
		$expression_metrics = $_SESSION['sess_gat_expr']['expr_tmpl_' . $expression_id];
	} else {
		$expression_metrics = get_template_metrics_from_expression_by_id($expression_id);
		$_SESSION['sess_gat_expr']['expr_tmpl_' . $expression_id] = $expression_metrics;
	}
	return $expression_metrics;
}

function edit_general_actions() {
	global $config, $gridalarms_types, $alarm_types, $aggregation, $repeatarray, $alertarray, $timearray, $frequencies;
	$sql_params = array();

	//Initial alarm-expr-metrics in session
	if (!isset($_SESSION['sess_gat_expr'])){
		$_SESSION['sess_gat_expr'] = array();
	}

	if (!isempty_request_var('id')) {
		if(get_request_var('action') != 'save'){
			$alarm_item_data = get_template_by_id(get_request_var('id'));

			//Initial alarm-expr-metrics in session
			if (!empty($alarm_item_data['expression_id'])
				&& (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'gridalarms_templates.php') !== false)){
				$expr_id = $alarm_item_data['expression_id'];
				if (isset($_SESSION['sess_gat_expr']['expr_tmpl_' . $expr_id])){
					unset($_SESSION['sess_gat_expr']['expr_tmpl_' . $expr_id]);
				}
			}
		} else {
			//TODO: security issue
			$alarm_item_data = $_POST;
		}

		$selected_users_where = ' AND plugin_thold_contacts.id IN (
			SELECT contact_id
			FROM gridalarms_template_contacts
			WHERE alarm_id = ?)';

		$not_selected_users_where = ' AND plugin_thold_contacts.id NOT IN (
			SELECT contact_id
			FROM gridalarms_template_contacts
			WHERE alarm_id = ?)';
		$sql_params[] = get_request_var('id');
	} else {
		$selected_users_where = '';
		$not_selected_users_where = '';
	}

	$send_notification_array = array();
	$users_custom_list = "<table><tr><td><select id='not_selected_users' size='10' MULTIPLE>";

	$not_users = db_fetch_assoc_prepared("SELECT DISTINCT plugin_thold_contacts.id, plugin_thold_contacts.data,
		plugin_thold_contacts.type, user_auth.full_name
		FROM plugin_thold_contacts, user_auth
		WHERE user_auth.id=plugin_thold_contacts.user_id
		AND plugin_thold_contacts.data != ''
		AND user_auth.full_name != '' $not_selected_users_where
		ORDER BY user_auth.full_name ASC, plugin_thold_contacts.type ASC", $sql_params);

	if ($selected_users_where != '') {
		$users = db_fetch_assoc_prepared("SELECT DISTINCT plugin_thold_contacts.id, plugin_thold_contacts.data,
			plugin_thold_contacts.type, user_auth.full_name
			FROM plugin_thold_contacts, user_auth
			WHERE user_auth.id=plugin_thold_contacts.user_id
			AND plugin_thold_contacts.data!=''
			AND user_auth.full_name != ''
			$selected_users_where
			ORDER BY user_auth.full_name ASC, plugin_thold_contacts.type ASC", $sql_params);
	} else {
		$users = array();
	}

	if (!empty($not_users)) {
		foreach ($not_users as $not_user) {
			$send_notification_array[$not_user['id']] = $not_user['full_name'] . ' - ' . ucfirst($not_user['type']);
			$users_custom_list .= "<option value='". $not_user['id'] . "'>" . resource_sanitize_search_string($not_user['full_name']) . " - " . ucfirst($not_user['type']) . " </option>";
		}
	}

	$users_custom_list .="</select></td>
		<td>
			<input type='button' name='Append' id='Append' value='Append >>' onclick='appendSelected(1);' /></br>
			<input type='button' name='Remove' id='Remove' value='<< Remove' onclick='appendSelected(2)' />
		</td>
		<td>
			<select id='selected_users' name='selected_users' size='10' MULTIPLE>";

	$users_list = '';

	if (!empty($users)) {
		foreach ($users as $users) {
			$send_notification_array[$users['id']] = $users['full_name'] . ' - ' . ucfirst($users['type']);
			$users_custom_list .= "<option value='". $users['id'] . "'>" . resource_sanitize_search_string($users['full_name']) . " - " . ucfirst($users['type']) . " </option>";
			$users_list .= $users['id'] . ' ';
		}
	}

	$users_custom_list .= '</select></td></tr></table>';

	/* set to expression id if defined, otherwise, leave unset */
	$expression_id      = '';
	$expression_metrics = array();
	$expression_desc    = '';
	$expression_ds      = -1;
	if (!empty($alarm_item_data['expression_id'])) {
		$expression_id   = $alarm_item_data['expression_id'];
		$expression      = get_template_expression_by_id($alarm_item_data['expression_id']);
		$expression_desc = $expression['name'];
		$expression_ds   = $expression['ds_type'];
		//Query metrics for 'non-Script-DS'(PDB#235497) under 'General' tab
		if($expression_ds != 2 && get_request_var('tab') == 'general'){
			$expression_metrics = get_metrics_from_cache_by_expression_template_id($expression_id);
		}
	}

	if (isset($alarm_item_data['notify_cluster_admin']) && $alarm_item_data['notify_cluster_admin'] == 1) {
		$notify_cluster_admin = 'on';
	} else {
		$notify_cluster_admin = '';
	}

	if (function_exists('define_syslog_variables')) define_syslog_variables();

	$general_array = array(
		'general_header' => array(
			'friendly_name' => __('General Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'name' => array(
			'friendly_name' => __('Template Name', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 100,
			'size' => 65,
			'default' => __('New Template', 'gridalarms'),
			'description' => __('The name for this Alert Template. Available substitutions include <b><em>|alert_clustername|</em></b>, <b><em>|alert_clusterid|</em></b>.', 'gridalarms'),
			'value' => isset($alarm_item_data['name']) ? $alarm_item_data['name'] : 'New Template'
		),
		'alarm_syslog_priority' => array(
			'friendly_name' => __('Alert Severity', 'gridalarms'),
			'description' => __('Assign a severity to the Alert Template.', 'gridalarms'),
			'method' => 'drop_array',
			'default' => read_config_option('gridalarm_severity'),
			'array' => array(LOG_EMERG => 'Emergency', LOG_ALERT => 'Alert', LOG_CRIT => 'Critical', LOG_ERR => 'Error', LOG_WARNING => 'Warning', LOG_NOTICE => 'Notice', LOG_INFO => 'Info', LOG_DEBUG => 'Debug'),
			'value' => isset($alarm_item_data['syslog_priority']) ? $alarm_item_data['syslog_priority'] : ''
		),
		'clusterid' => array(
			'friendly_name' => __('Cluster Association', 'gridalarms'),
			'method' => 'drop_array',
			'on_change' => 'changecluster()',
			'array' => get_clusters_for_form_dropdown(),
			'default' => 0,
			'description' => __('If you select a cluster, then this Alert template is applied only to that cluster and you must include clusterid. For example, SELECT COUNT(0) FROM (select count(*) as ttt,clusterid from grid_jobs) AS query WHERE clusterid=1; If you do not want this template to be cluster-specific, then select N/A.', 'gridalarms'),
			'value' => isset($alarm_item_data['clusterid']) ? $alarm_item_data['clusterid'] : 0
		),
		'expression_id_display' => array(
			'friendly_name' => __('Data Source', 'gridalarms'),
			'method' => '',
			'description' => __('Specify how you want to retrieve data to determine if there is an Alert. Save this page to see the <b>Data Source</b> tab.', 'gridalarms'),
			'value' => $expression_desc
		),
		'aggregation' => array(
			'friendly_name' => __('Data Source Aggregation Method', 'gridalarms'),
			'method' => 'drop_array',
			'array' => $aggregation,
			'description' => __('The type of aggregation method you want to use.', 'gridalarms'),
			'value' => isset($alarm_item_data['aggregation']) ? $alarm_item_data['aggregation'] : ''
		),
		'metric' => array(
			'friendly_name' => __('Data Source Column', 'gridalarms'),
			'method' => 'drop_array',
			'array' => $expression_metrics,
			'none_value' => __('None', 'gridalarms'),
			'description' => __('Assign a column name when using internal SQL functions. For example, change <span class="codeph">select count(*) from grid_jobs</span> to <span class="codeph">select count(*) as col_name from grid_jobs</span>.', 'gridalarms'),
			'value' => isset($alarm_item_data['metric']) ? $alarm_item_data['metric'] : ''
		),
		'alarm_type' => array(
			'friendly_name' => __('Alert Type', 'gridalarms'),
			'method' => 'drop_array',
			'on_change' => 'changealarmType()',
			'array' => $alarm_types,
			'default' => read_config_option('alarm_type'),
			'description' => __('Specify the type of Alert that is being monitored.', 'gridalarms'),
			'value' => isset($alarm_item_data['alarm_type']) ? $alarm_item_data['alarm_type'] : ''
		),
		'sched_header' => array(
			'friendly_name' => __('Schedule Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'frequency' => array(
			'friendly_name' => __('Check Frequency', 'gridalarms'),
			'method' => 'drop_array',
			'on_change' => 'changealarmFrequency()',
			'array' => $frequencies,
			'description' => __('You can set the frequency to see if an alert is breached. The frequency can be up to a day; and the Alert Check time defines the time of day for the initial check.', 'gridalarms'),
			'value' => isset($alarm_item_data['frequency']) ? $alarm_item_data['frequency'] : ''
		),
		'base_time_display' => array(
			'friendly_name' => __('Alert Check Time', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 15,
			'size' => 10,
			'description' => __('Enter a time to run the Alert check', 'gridalarms'),
			'default' => '12:00am',
			'value' => isset($alarm_item_data['base_time_display']) ? $alarm_item_data['base_time_display'] : ''
		),
		'alarm_header' => array(
			'friendly_name' => __('High / Low Settings', 'gridalarms'),
			'description' => __('If a High value is set, then alert is triggered if the value goes over the High Value. Similarly, if you set Low value, then alert is triggered if the value goes below the Low Value.', 'gridalarms'),
			'method' => 'spacer',
		),
		'alarm_hi' => array(
			'friendly_name' => __('High Value', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 100,
			'size' => 6,
			'description' => __('If set and the measured value (current value) goes above this number, an Alert is triggered', 'gridalarms'),
			'value' => (!isset($alarm_item_data['alarm_hi']) && !isset($alarm_item_data['alarm_low']))? '0': (isset($alarm_item_data['alarm_hi']) ? $alarm_item_data['alarm_hi'] : '')
		),
		'alarm_low' => array(
			'friendly_name' => __('Low Value', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 100,
			'size' => 6,
			'description' => __('If set and the measured value (current value) goes below this number, an Alert is triggered', 'gridalarms'),
			'value' => isset($alarm_item_data['alarm_low']) ? $alarm_item_data['alarm_low'] : ''
		),
		'time_header' => array(
			'friendly_name' => __('Time Based Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'time_hi' => array(
			'friendly_name' => __('High Value', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 100,
			'size' => 6,
			'description' => __('If set and the measured value goes above this number, Alert will be triggered', 'gridalarms'),
			'value' => (!isset($alarm_item_data['time_hi']) && !isset($alarm_item_data['time_low']))? '0': (isset($alarm_item_data['time_hi']) ? $alarm_item_data['time_hi'] : '')
		),
		'time_low' => array(
			'friendly_name' => __('Low Value', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 100,
			'size' => 6,
			'description' => __('If set and the measured value goes below this number, Alert will be triggered', 'gridalarms'),
			'value' => isset($alarm_item_data['time_low']) ? $alarm_item_data['time_low'] : ''
		),
		'breach_header' => array(
			'friendly_name' => __('Breach Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'time_fail_trigger' => array(
			'friendly_name' => __('Breach Count', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 5,
			'size' => 6,
			'default' => read_config_option('alarm_time_fail_trigger'),
			'description' => __('The number of times the measured value must be in breach prior to triggering an Alert.', 'gridalarms'),
			'value' => isset($alarm_item_data['time_fail_trigger']) ? $alarm_item_data['time_fail_trigger'] : ''
		),
		'time_fail_length' => array(
			'friendly_name' => __('Breach Window', 'gridalarms'),
			'method' => 'drop_array',
			'array' => $timearray,
			'default' => (read_config_option('alarm_time_fail_length') > 0 ? read_config_option('alarm_time_fail_length') : 1),
			'description' => __('The amount of time in the past to check for Alert breaches.', 'gridalarms'),
			'value' => isset($alarm_item_data['time_fail_length']) ? $alarm_item_data['time_fail_length'] : ''
		),
		'alarm_fail_trigger' => array(
			'friendly_name' => __('Breach Duration', 'gridalarms'),
			'method' => 'drop_array',
			'array' => $alertarray,
			'default' => read_config_option('alarm_trigger'),
			'description' => __('The time in which the value must be in breach before an alert is triggered.', 'gridalarms'),
			'value' => isset($alarm_item_data['alarm_fail_trigger']) ? $alarm_item_data['alarm_fail_trigger'] : ''
		),
		'repeat_alert' => array(
			'friendly_name' => __('Repeat Alert Cycle', 'gridalarms'),
			'method' => 'drop_array',
			'array' => $repeatarray,
			'default' => read_config_option('alarm_repeat'),
			'description' => __('If an alert stays breached for a long time, specify how often you want to be notified.', 'gridalarms'),
			'value' => isset($alarm_item_data['repeat_alert']) ? $alarm_item_data['repeat_alert'] : ''
		),
		'mandotory_header' => array(
			'friendly_name' => __('Additional Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'exempt' => array(
			'friendly_name' => __('Weekend Exemption', 'gridalarms'),
			'description' => __('Check this box if you do not want Alerts to trigger on weekends.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => 'off',
			'value' => isset($alarm_item_data['exempt']) ? $alarm_item_data['exempt'] : ''
		),
		'restored_alert' => array(
			'friendly_name' => __('Disable Restoration Email', 'gridalarms'),
			'description' => __('Check this box if you do not want to receive restoration Email.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => 'off',
			'value' => isset($alarm_item_data['restored_alert']) ? $alarm_item_data['restored_alert'] : ''
		),
		'req_ack' => array(
			'friendly_name' => __('Acknowledge a Triggered Alert', 'gridalarms'),
			'description' => __('Select the check box if you want to acknowledge a triggered alert. Acknowledging an alert lets other users know that you are taking ownership of the issue.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => 'off',
			'value' => isset($alarm_item_data['req_ack']) ? $alarm_item_data['req_ack'] : ''
		),
		'alarm_syslog_facility' => array(
			'method' => 'hidden',
			'default' => read_config_option('gridalarm_facility'),
			'value' => isset($alarm_item_data['syslog_facility']) ? $alarm_item_data['syslog_facility'] : ''
		)
	);

	/*fix problem 235497 undefined index*/
	if($expression_ds==2){
		unset($general_array['metric']);
	}

	$actions_array = array(
		'email_message_body' => array(
			'friendly_name' => __('Email Message', 'gridalarms'),
			'method' => 'spacer',
		),
		'email_subject' => array(
			'friendly_name' => __('Email Subject', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 255,
			'size' => 65,
			'description' => __('In your email subject, you can use descriptors such as, <span class="keyword">&lt;NAME></span> <span class="keyword">&lt;TYPE></span> <span class="keyword">&lt;VALUE></span><span class="keyword"> &lt;HI></span><span class="keyword"> &lt;LOW></span> <span class="keyword">&lt;TRIGGER></span><span class="keyword"> &lt;DURATION></span> <span class="keyword">&lt;DATE></span> and <span class="keyword">&lt;TIME></span>. You can also use both |alert_clusterid| and |alert_clustername|, and any |input_variable_name| replacement fields.', 'gridalarms'),
			'value' => isset($alarm_item_data['email_subject']) ? $alarm_item_data['email_subject'] : 'ALERT: <NAME> Breached Threshold Limits'
		),
		'email_body' => array(
			'friendly_name' => __('Email Body', 'gridalarms'),
			'description' => __('Only 255 characters are allowed. In your email body, you can use descriptors such as, <span class="keyword">&lt;NAME></span> <span class="keyword">&lt;TYPE></span> <span class="keyword">&lt;VALUE></span><span class="keyword"> &lt;HI></span><span class="keyword"> &lt;LOW></span> <span class="keyword">&lt;TRIGGER></span><span class="keyword"> &lt;DURATION></span> <span class="keyword">&lt;DATE></span> <span class="keyword">&lt;URL></span> <span class="keyword">&lt;DETAILS_URL></span> <span class="keyword">&lt;DETAILS></span> and <span class="keyword">&lt;TIME></span>. You can also use both |alert_clusterid| and |alert_clustername|, and any |input_variable_name| replacement fields.', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'value' => isset($alarm_item_data['email_body']) ? $alarm_item_data['email_body'] : 'An Alert has been issued that requires your attention. <br><br><strong><NAME></strong>: has breached threshold limits HI:<HI>/LOW:<LOW> with Current Value:<VALUE><br><DETAILS><br><br>'
		),
	);

	$actions_array += array(
		'other_header' => array(
			'friendly_name' => __('Email Notification Settings', 'gridalarms'),
			'method' => 'spacer',
		),
		'notify_cluster_admin' => array(
			'friendly_name' => __('Notify Cluster Admins', 'gridalarms'),
			'description' => __('Check this box if you want to send emails to the configured administrators for the selected cluster.', 'gridalarms'),
			'method' => 'checkbox',
			'value' => $notify_cluster_admin
		),
		'notify_users' => array(
			'friendly_name' => __('Notify Job Users', 'gridalarms'),
			'description' => __('Check this box if you want to send emails to users whose jobs are affected by this Alert. Only job and execution host data types are currently supported.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => '',
            'value' => isset($alarm_item_data['notify_users']) ? $alarm_item_data['notify_users'] : '',
		),
        'notify_alert' => array(
            'friendly_name' => __('Notification List', 'gridalarms'),
            'method' => 'drop_sql',
            'description' => __('Select a notification list to receive emails for this Alert.', 'gridalarms'),
            'value' => isset($alarm_item_data['notify_alert']) ? $alarm_item_data['notify_alert'] : '',
            'none_value' => 'None',
            'sql' => 'SELECT id, name FROM plugin_notification_lists ORDER BY name'
        )
	);

	if (read_config_option('gridalarm_disable_legacy') == '') {
		$actions_array += array(
			'users' => array(
				'friendly_name' => __('Notify Accounts', 'gridalarms'),
				'method' => 'custom',
				'description' => __('This is a listing of accounts that will be notified when this Alert is breached.<br><br><br><br>', 'gridalarms'),
				'value' => $users_custom_list
			),
			'notify_accounts' => array(
				'method' => 'hidden',
				'value' => $users_list
			),
			'notify_extra' => array(
				'friendly_name' => __('Additional Emails (separated by comma)', 'gridalarms'),
				'method' => 'textarea',
				'class' => 'textAreaNotes',
				'textarea_rows' => '4',
				'textarea_cols' => '60',
				'description' => __('You may specify here extra Emails to receive alerts for this Alert (comma separated)', 'gridalarms'),
				'value' => isset($alarm_item_data['notify_extra']) ? $alarm_item_data['notify_extra'] : ''
			),
		);
	} else {
		$actions_array += array(
			'users' => array(
				'method' => 'hidden',
				'value' => ''
			),
			'notify_accounts' => array(
				'method' => 'hidden',
				'value' => ''
			),
			'notify_extra' => array(
				'method' => 'hidden',
				'value' => ''
			)
		);
	}

	$actions_array += array(
		'event_trigger' => array(
			'friendly_name' => __('Event Triggering (Shell Command)', 'gridalarms'),
			'description' => __('Write a script to trigger when High, Low, or Normal Threshold is breached. Use script\'s full path, for example, /bin/sh/opt/mytest.sh. It does not support use of redirection operators. You can use the following meta tags for expression type Alerts: <span class="keyword">&lt;NAME></span>, <span class="keyword">&lt;ID></span>,<span class="keyword"> &lt;HI></span>, <span class="keyword">&lt;LOW></span>, <span class="keyword">&lt;VALUE></span>, <span class="keyword">&lt;CLUSTERID></span>, <span class="keyword">&lt;ITEMS_HEADER></span>, <span class="keyword">&lt;ITEMS_LIST></span>. You can also use both |alert_clusterid| and |alert_clustername|, and any |input_variable_name| defined replacement fields.<span class="keyword">&lt;ITEMS_HEADER></span> returns a comma-separated list of breached item headers.<span class="keyword"> &lt;ITEM_LIST></span> returns a \'|\' separated list of records and each record is a comma-separated list of items. <span class="keyword"> &lt;CACTI_ROOT></span> returns current RTM cacti root directory.', 'gridalarms'),
			'method' => 'spacer',
		),
		'cmd_retrigger_enabled' => array(
			'friendly_name' => __('Run the Event Triggering Command on the Retriggered Alert', 'gridalarms'),
			'description' => '',
			'method' => 'checkbox',
			'value' => isset($alarm_item_data['cmd_retrigger_enabled']) ? $alarm_item_data['cmd_retrigger_enabled'] : ''
		),
		'trigger_cmd_high' => array(
			'friendly_name' => __('High Threshold Trigger Command / Script', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'description' => __('If set and high threshold is breached, this command will be run in the background.  It will not trigger on re-alerts.  Enter full path to all scripts/executables.  e.g.  /bin/sh /opt/mytest.sh.  It does not support use of redirection operators.  You may also use the following meta tags for expression type Alerts: &#060NAME&#062, &#060ID&#062, &#060HI&#062, &#060LOW&#062, &#060VALUE&#062, &#060CLUSTERID&#062, &#060ITEMS_HEADER&#062, &#060ITEMS_LIST&#062.  You may also use both <b>|alert_clusterid|</b> and <b>|alert_clustername|</b>, and any <b>|input_variable_name|</b> replacement fields defined.  &#060ITEMS_HEADER&#062 will return a comma separated list of breached item headers.  &#060ITEMS_LIST&#062 will return a \'|\' separated list of records and each record is a comma separated list of items.', 'gridalarms'),
			'value' => isset($alarm_item_data['trigger_cmd_high']) ? $alarm_item_data['trigger_cmd_high'] : ''
		),
		'trigger_cmd_low' => array(
			'friendly_name' => __('Low Threshold Trigger Command / Script', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'description' => __('If set and low threshold is breached, this command will be run in the background.  It will not trigger on re-alerts.  Enter full path to all scripts/executables.  e.g.  /bin/sh /opt/mytest.sh.  It does not support use of redirection operators.  You may also use the following meta tags for expression type Alerts: &#060NAME&#062, &#060ID&#062, &#060HI&#062, &#060LOW&#062, &#060VALUE&#062, &#060CLUSTERID&#062, &#060ITEMS_HEADER&#062, &#060ITEMS_LIST&#062.  You may also use both <b>|alert_clusterid|</b> and <b>|alert_clustername|</b>, and any <b>|input_variable_name|</b> replacement fields defined.  &#060ITEMS_HEADER&#062 will return a comma separated list of breached item headers.  &#060ITEMS_LIST&#062 will return a \'|\' separated list of records and each record is a comma separated list of items.', 'gridalarms'),
			'value' => isset($alarm_item_data['trigger_cmd_low']) ? $alarm_item_data['trigger_cmd_low'] : ''
		),
		'trigger_cmd_norm' => array(
			'friendly_name' => __('Normal Threshold Trigger Command / Script', 'gridalarms'),
			'method' => 'textarea',
			'class' => 'textAreaNotes',
			'textarea_rows' => '4',
			'textarea_cols' => '60',
			'description' => __('If set and a threshold breach is restored to normal, this command will be run in the background.  Enter full path to all scripts/executables.  e.g.  /bin/sh /opt/mytest.sh.  It does not support use of redirection operators.  You may also use the following meta tags for expression type Alerts: &#060NAME&#062, &#060ID&#062, &#060HI&#062, &#060LOW&#062, &#060VALUE&#062, &#060CLUSTERID&#062, &#060ITEMS_HEADER&#062, &#060ITEMS_LIST&#062.  You may also use both <b>|alert_clusterid|</b> and <b>|alert_clustername|</b>, and any <b>|input_variable_name|</b> replacement fields defined.  &#060ITEMS_HEADER&#062 will return a comma separated list of breached item headers.  &#060ITEMS_LIST&#062 will return a \'|\' separated list of records and each record is a comma separated list of items.', 'gridalarms'),
			'value' => isset($alarm_item_data['trigger_cmd_norm']) ? $alarm_item_data['trigger_cmd_norm'] : ''
		),
		'syslog_settings' => array(
			'friendly_name' => __('Event System Logging', 'gridalarms'),
			'method' => 'spacer',
		),
		'alarm_syslog_enabled' => array(
			'friendly_name' => __('Enable Syslog', 'gridalarms'),
			'description' => __('Check this box if you want to send messages to your local syslog. If you prefer to send the messages to a remote logging server, then setup your local syslog to forward events to that server.', 'gridalarms'),
			'method' => 'checkbox',
			'value' => isset($alarm_item_data['syslog_enabled']) ? $alarm_item_data['syslog_enabled'] : ''
		),
	);

	if (get_request_var('tab') == 'general') {
		$form_array = $general_array;

	} else {
		$form_array = $actions_array;
	}

	form_start('gridalarms_template_edit.php');

	html_start_box(__('Alert Settings', 'gridalarms'), '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($form_array, (isset($alarm_item_data) ? $alarm_item_data : array()))
		)
	);

	form_hidden_box('tab', get_request_var('tab'), 'general');
	form_hidden_box('id', isset($alarm_item_data['id']) ? $alarm_item_data['id'] : 0, '0');
	form_hidden_box('expression_id', isset($alarm_item_data['expression_id']) ? $alarm_item_data['expression_id'] : '0', '0');
	form_hidden_box('expression_ds', $expression_ds, '0');
//	form_hidden_box('clusterid', isset($alarm_item_data['clusterid']) ? $alarm_item_data['clusterid'] : '0', '0');
	form_hidden_box('save_component_alarm', '1', '1');

	html_end_box(true, true);

	if (isset($alarm_item_data['id'])) {
		form_save_button('gridalarms_alarm.php', 'save');
	} else {
		form_save_button('gridalarms_alarm.php?new=-1', 'save');
	}

	include($config['base_path'] . '/plugins/gridalarms/includes/arrays.php');
	?>
	<script type='text/javascript'>
	$(function() {
		$('select#expression_id').change(function() {
			$('select#metric').html('<option>' + pleaseWait + '</option>');
			var expression_id = $(this).val();
			if (expression_id != '') {
				$.get('gridalarms_template_edit.php',{'action':'getmetrics', 'expression_id':expression_id}, function(data){
					$('select#metric').html(data);
				});
			}
		});

		if ($('#expression_id').val() == 0) {
			if ($('#id').val() == 0) {
				$('#row_expression_id_display > div:last').append('<em><?php print __('Save Alert first before adding a Data Source', 'gridalarms');?></em>');
			}
			$('#row_aggregation').hide();
			$('#row_metric').hide();
		}

		if ($('#expression_ds').val() == 2) {
			$('#row_aggregation').hide();
			$('#row_notify_users').hide();
		}

		changealarmType();
		changecluster();
		changealarmFrequency();
	});

	</script>
	<?php
}

function layout_edit() {
	get_filter_request_var('id');

	$alarm      = get_template_by_id(get_request_var('id'));
	$expression = get_template_expression_by_id($alarm['expression_id']);

	$atrl_caption_tip = __('Alert Template Report Layout', 'gridalarms') . '<div class="formTooltip">' . display_tooltip(__('Change your layout if you want to have different columns: <ul><li>For Job drill down -  include the following columns: clusterid, jobid, indexid, and submit_time.</li><li>For Queue or User drill down -  include the clusterid column and either the Queue, Queuename, or User columns.</li><li>For Host drill down -  include the clusterid column and either the Exec Host, Host, or From Host columns. </li></ul>If you have included clusterid column in your SQL queries and Tables, then you can also select the cluster name column. Note that the sort does not work unless the cluster name column is returned in the SQL Query. ')) . '</div>';

	html_start_box($atrl_caption_tip, '100%', '', '3', 'center', 'gridalarms_template_edit.php?action=layout_add&id=' . get_request_var('id'));

	$display_text = array(
		__('Item Number', 'gridalarms'),
		__('Display Name', 'gridalarms'),
		__('Column Name', 'gridalarms'),
		__('Sort Column', 'gridalarms'),
		__('Sort Order', 'gridalarms')
	);

	html_header($display_text, 2);

	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_layout
		WHERE alarm_id = ?
		ORDER BY sequence',
		array(get_request_var('id')));

	if (!cacti_sizeof($items)) {
		populate_template_default_layout($alarm);

		$items = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_layout
			WHERE alarm_id = ?
			ORDER BY sequence',
			array(get_request_var('id')));
	}

	$i = 1;
	$total_items = cacti_sizeof($items);
	if ($total_items) {
		foreach ($items as $item) {
			form_alternate_row();

			form_selectable_cell(filter_value(__('Item #%d', $i, 'gridalarms'), '', 'gridalarms_template_edit.php?action=layout_edit&id=' . get_request_var('id') . '&column=' . $item['column_name']), $item['id']);
			form_selectable_cell('<em>' . html_escape($item['display_name']) . '</em>', $item['id']);
			form_selectable_cell(html_escape($item['column_name']), $item['id']);
			form_selectable_cell($item['sortposition'] > 0 ? html_escape($item['sortposition']) : __('N/A', 'gridalarms'), $item['id']);
			form_selectable_cell($item['sortposition'] > 0 ? ($item['sortdirection'] == 0 ? __('Ascending', 'gridalarms'):__('Descending', 'gridalarms')):__('N/A', 'gridalarms'), $item['id']);

			$move = '';
			if ($i < $total_items) {
				$move .= "<a class='pic fa fa-caret-down moveArrow' href='" . html_escape('gridalarms_template_edit.php?action=layout_movedown&id=' . get_request_var('id') . '&column=' . $item['column_name']) . "'></a>";
			} else {
				$move .= '<span class="moveArrowNone"></span>';
			}

			if ($i > 1 && $i <= $total_items) {
				$move .= "<a class='pic fa fa-caret-up moveArrow' href='" . html_escape('gridalarms_template_edit.php?action=layout_moveup&id=' . get_request_var('id') . '&column=' . $item['column_name']) . "'></a>";
			} else {
				$move .= '<span class="moveArrowNone"></span>';
			}

			$move .= "<a class='pic fa fa-times deleteMarker' href='" . html_escape('gridalarms_template_edit.php?action=layout_remove&id=' . get_request_var('id') . '&column=' . $item['column_name']) . "'></a>";

			form_selectable_cell($move, $item['id'], '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		echo "<tr><td colspan='5'><em>" . __('No Columns Found', 'gridalarms') . "</em></td></tr>\n";
	}

	html_end_box();
}

function layout_movedown() {
	get_filter_request_var('id');

	$curr_col = get_request_var('column');
	$alarm_id = get_request_var('id');

	$curr_seq = db_fetch_cell_prepared('SELECT sequence
		FROM gridalarms_template_layout
		WHERE column_name = ?
		AND alarm_id = ?',
		array($curr_col, $alarm_id));

	$next_seq = db_fetch_cell_prepared('SELECT sequence
		FROM gridalarms_template_layout
		WHERE alarm_id = ?
		AND sequence > ?
		ORDER BY sequence ASC',
		array($alarm_id, $curr_seq));

	$next_col = db_fetch_cell_prepared('SELECT column_name
		FROM gridalarms_template_layout
		WHERE alarm_id = ?
		AND sequence = ?',
		array($alarm_id, $next_seq));

	if ($next_seq != '' && $curr_seq != $next_seq) {
		db_execute_prepared('UPDATE gridalarms_template_layout
			SET sequence = ?
			WHERE column_name = ?
			AND alarm_id = ?',
			array($next_seq, $curr_col, $alarm_id));

		db_execute_prepared('UPDATE gridalarms_template_layout
			SET sequence = ?
			WHERE column_name = ?
			AND alarm_id = ?',
			array($curr_seq, $next_col, $alarm_id));

		template_propagation('gridalarms_template', $alarm_id);
	}
}

function layout_moveup() {
	get_filter_request_var('id');

	$curr_col = get_request_var('column');
	$alarm_id = get_request_var('id');

	$curr_seq = db_fetch_cell_prepared('SELECT sequence
		FROM gridalarms_template_layout
		WHERE column_name = ?
		AND alarm_id = ?',
		array($curr_col, $alarm_id));

	$next_seq = db_fetch_cell_prepared('SELECT sequence
		FROM gridalarms_template_layout
		WHERE alarm_id = ?
		AND sequence < ?
		ORDER BY sequence DESC',
		array($alarm_id, $curr_seq));

	$next_col = db_fetch_cell_prepared('SELECT column_name
		FROM gridalarms_template_layout
		WHERE alarm_id = ?
		AND sequence = ?',
		array($alarm_id, $next_seq));

	if ($next_seq != '' && $curr_seq != $next_seq) {
		db_execute_prepared('UPDATE gridalarms_template_layout
			SET sequence = ?
			WHERE column_name = ?
			AND alarm_id = ?',
			array($next_seq, $curr_col, $alarm_id));

		db_execute_prepared('UPDATE gridalarms_template_layout
			SET sequence = ?
			WHERE column_name = ?
			AND alarm_id = ?',
			array($curr_seq, $next_col, $alarm_id));

		template_propagation ("gridalarms_template", $alarm_id);
	}
}

function layout_remove() {
	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	db_execute_prepared('DELETE FROM gridalarms_template_layout
		WHERE alarm_id = ?
		AND column_name = ?',
		array(get_request_var('id'), get_request_var('column')));

	template_propagation('gridalarms_template', get_request_var('id'));
}

function layout_item_edit($new = false) {
	global $cdef_item_types, $cdef_functions, $cdef_operators, $custom_data_source_types;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('column')) {
		$layout = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_layout
			WHERE column_name = ?
			AND alarm_id = ?',
			array(get_request_var('column'), get_request_var('id')));
	} else {
		$columns = array_rekey(
			db_fetch_assoc_prepared("SELECT column_name
				FROM gridalarms_template_layout
				WHERE alarm_id = ?",
				array(get_request_var('id'))),
			'column_name', 'column_name'
		);

		$available_columns = get_template_metrics_from_expression_by_alarm_id(get_request_var('id'));

		if (isset($available_columns['clusterid'])) {
			$available_columns['clustername'] = 'clustername';
		}

		$drop_columns = array_diff($available_columns, $columns);
		sort($drop_columns);

		$new_drop_columns = array();
		foreach ($drop_columns as $drop_column_1 ) {
			$new_drop_columns[$drop_column_1] = $drop_column_1;
		}

		$layout['display_name']  = '';
		$layout['sortposition']  = 0;
		$layout['sortdirection'] = -1;
	}

	form_start('gridalarms_template_edit.php', 'chk');

	html_start_box(__('Layout Item', 'gridalarms'), '100%', '', '3', 'center', '');

	$layout_items_array = array(
		'display_name' => array(
			'friendly_name' => __('Display Name', 'gridalarms'),
			'method' => 'textbox',
			'max_length' => 20,
			'size' => 20,
			'default' => '',
			'description' => __('The name that is displayed in all reports.', 'gridalarms'),
			'value' => isset($layout['display_name']) ? $layout['display_name'] : ''
		)
	);

	if (!$new) {
		$layout_items_array += array(
			'column_name' => array(
				'friendly_name' => __('Column Name', 'gridalarms'),
				'method' => '',
				'description' => __('The name of the column from the Expression Table or SQL Query.', 'gridalarms'),
				'value' => isset ($layout['column_name']) ? $layout['column_name']:''
			)
		);
	} else {
		$layout_items_array += array(
			'column_name' => array(
				'friendly_name' => __('Column Name', 'gridalarms'),
				'method' => 'drop_array',
				'on_change' => '',
				'array' => $new_drop_columns,
				'default' => 0,
				'description' => __('The name of the column from the Expression Table or SQL Query.', 'gridalarms'),
				'value' => ''
			)
		);
	}

	$layout_items_array += array(
		'sortposition' => array(
			'friendly_name' => __('Sort Order', 'gridalarms'),
			'method' => 'drop_array',
			'array' => array(
				0 => __('N/A', 'gridalarms'),
				1 => '1',
				2 => '2',
				3 => '3',
				4 => '4'
			),
			'description' => __('Select the desired sort order for this column. You can have up to four order variables. The order that is selected pushes down equal or lower valued sort columns.', 'gridalarms'),
			'value' => isset($layout['sortposition'] ) ? $layout['sortposition']  : ''
		),
		'sortdirection' => array(
			'friendly_name' => __('Sort Direction', 'gridalarms'),
			'method' => 'drop_array',
			'array' => array(
				-1 => __('N/A', 'gridalarms'),
				0  => __('Ascending', 'gridalarms'),
				1  => __('Descending', 'gridalarms'),
			),
			'description' => __('Select the desired sort direction for this column.', 'gridalarms'),
			'value' => isset($layout['sortdirection'] ) ? $layout['sortdirection']  : ''
		)
	);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($layout_items_array, (isset($layout) ? $layout : array()))
		)
	);

	form_hidden_box('id', (isset($layout['id']) ? $layout['id'] : '0'), '');
	form_hidden_box('alarm_id', (isset_request_var('id') ? get_request_var('id') : '0'), '');
	form_hidden_box('sequence', (isset($layout['sequence']) ? $layout['sequence'] : ''), '');
	form_hidden_box('save_component_layout', '1', '');

	html_end_box();

	form_save_button('gridalarms_template_edit.php?tab=layout&id=' . get_request_var('id'));
}
