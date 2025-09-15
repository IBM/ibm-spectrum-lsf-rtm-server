<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2025 The Cacti Group                                 |
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
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

chdir('../..');
include('./include/auth.php');

$actions = array(
	1 => __('Delete', 'exlist'),
);

set_default_action();

switch (get_nfilter_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'check':
		if (get_nfilter_request_var('sql') != '') {
			$rows = db_fetch_assoc_prepared('SELECT COUNT(*)
				FROM grid_jobs
				WHERE ' . get_nfilter_request_var('sql') . ' LIMIT 1', false);

			if ($rows !== false) {
				print 'ok';
			} else {
				print 'failed';
			}
		} else {
			print 'failed';
		}

		break;
	case 'actions':
		form_actions();

		break;
	case 'edit':
		top_header();
		edit();
		bottom_footer();

		break;
	default:
		top_header();
		exlists();
		bottom_footer();

		break;
}

function form_save() {
	if (isset_request_var('save_component')) {
		if (isempty_request_var('id')) {
			$save['id'] = 0;
		} else {
			$save['id'] = get_filter_request_var('id');
		}

		$save['name']             = form_input_validate(get_nfilter_request_var('name'), 'name', '', false, 3);
		$save['sql_where']        = form_input_validate(get_nfilter_request_var('sql_where'), 'sql_where', '', false, 3);
		$save['enabled']          = (isset_request_var('enabled') ? 'on':'');

		if (!is_error_message()) {
			$id = sql_save($save, 'grid_jobs_exlists', 'id');
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	header('Location: exlists.php?action=edit&header=false&id=' . (empty($id) ? get_request_var('id') : $id));
	exit;
}

function get_exlist_name($id) {
	return db_fetch_cell_prepared('SELECT name FROM grid_jobs_exlists WHERE id = ?', array($id));
}

function form_actions() {
	global $colors, $actions, $cnn_id;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		if (isset_request_var('save_list')) {
			$selected_items = unserialize(stripslashes(get_nfilter_request_var('selected_items')));

			if ($selected_items != false) {
				for ($i=0; $i<count($selected_items); $i++) {
					$id = $selected_items[$i];

					db_execute_prepared('DELETE FROM grid_jobs_exlists WHERE id = ?', array($id));
				}
			}

			header('Location: exlists.php');
			exit;
		}
	}

	/* setup some variables */
	$list = ''; $array = array(); $list_name = '';

	if (isset($_POST['id'])) {
		$id = $_POST['id'];

		$list_name = get_exlist_name($id);
	}

	if (isset($_POST['save_list'])) {
		while (list($var,$val) = each($_POST)) {
			if (preg_match('/^chk_([A-Za-z0-9_\-| ]+)$/', $var, $matches)) {
				$list .= '<li><b>' . html_escape(get_exlist_name($matches[1])) .  '</b></li>';
				$array[] = $matches[1];
			}
		}

		include_once('./include/top_header.php');

		html_start_box($actions[get_nfilter_request_var('drp_action')] . ' ' . html_escape($list_name), '60%', '', '3', 'center', '');

		form_start('exlists.php');

		if (sizeof($array)) {
			if ($_POST['drp_action'] == '1') { /* delete */
				print "<tr>
					<td class='textArea'>
						<p>Click \'Continue\' to Delete the following Exception Filter(s).</p>
						<ul>$list</ul>
					</td>
				</tr>";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'exlist') . "' onClick='window.history.back()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'exlist') . "'>";
			}
		} else {
			raise_message(40);
			exit;
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='save_list' value='1'>
				<input type='hidden' name='selected_items' value='" . (isset($array) ? serialize($array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();

		form_end();

		bottom_footer();
	}
}

function get_header_label() {
	if (!isempty_request_var('id')) {
		$name = get_exlist_name(get_filter_request_var('id'));
		$header_label = __esc('Exception Filter [edit: %s]', $name, 'exlist');
	} else {
		$header_label = __esc('Exception Filter [new]', 'exlist');
	}

	return $header_label;
}

function edit() {
	global $colors, $config;

	$header_label = get_header_label();

	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$exlist = db_fetch_row("SELECT * FROM grid_jobs_exlists WHERE id=" . $_REQUEST['id']);
	} else {
		$exlist = array();
	}

	exlist_edit($exlist);
}

function exlist_edit($exlist) {
	global $config, $colors;

	$header_label = get_header_label();

	form_start('exlists.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$columns = array_rekey(db_fetch_assoc('SHOW COLUMNS FROM grid_jobs'), 'Field', 'Field');

	ksort($columns);

	$fields_exlist = array(
		'name' => array(
			'method'        => 'textbox',
			'friendly_name' => __('Filter Name', 'exlist'),
			'description'   => __('Name this filter.  It will appear as both the dropdown and Legend value', 'exlist'),
			'default'       => 'New Filter',
			'max_length'    => '40',
			'value'         => '|arg1:name|',
		),
		'enabled' => array(
			'method'        => 'checkbox',
			'friendly_name' => __('Enable Filter', 'exlist'),
			'description'   => __('When checked this Exception Filter will be enabled.', 'exlist'),
			'value'         => '|arg1:enabled|',
			'default'       => 'on'
		),
		'sql_where' => array(
			'method'        => 'textarea',
			'friendly_name' => __('Database SQL Where', 'exlist'),
			'description'   => __('The SQL Where Clause to use for the Exception list.  For example <b>"max_memory>65000000 AND num_cpus>20"</b>. The Exceoption Filter is combined with all other Filters on the Job details page.', 'exlist'),
			'value'         => '|arg1:sql_where|',
			'textarea_rows' => 4,
			'textarea_cols' => 60
		)
	);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_exlist, (sizeof($exlist) ? $exlist : array()))
		)
	);

	html_end_box();

	html_start_box(__('Select Column name to Append to SQL Where', 'exlist'), '100%', '', '3', 'center', '');

	print "<tr><td style='font-weight:bold;'>";

	$i = 0;
	foreach($columns as $c) {
		print ($i > 0 ? ', ':'');
		print "<i class='column' style='color:#060;cursor:pointer;'>$c</i>";
		$i++;
	}

	html_end_box();

	if (sizeof($exlist)) {
		form_hidden_box('id', $exlist['id'], '');
		form_hidden_box('save_component', '1', '');
	} else {
		form_hidden_box('save_component', '1', '');
	}

	form_save_button('exlists.php', 'return');

	?>
	<script type='text/javascript'>
	$(function() {
		$('.column').click(function() {
			value = $('#sql_where').val();
			value += ' ' + $(this).html();
			value.trim();

			$('#sql_where').val(value).change();
		});

		$('#sql_where').change(function() {
			$('#sqlcheck').html("<i style='vertical-align:top;cursor:pointer;font-size:15px;' title='<?php print __esc('Check SQL Syntax', 'exlist');?>' class='fas fa-check-circle deviceRecovering'></i>");
		}).keyup(function() {
			$('#sqlcheck').html("<i style='vertical-align:top;cursor:pointer;font-size:15px;' title='<?php print __esc('Check SQL Syntax', 'exlist');?>' class='fas fa-check-circle deviceRecovering'></i>");
		});

		$('#sql_where').after("&nbsp;<span id='sqlcheck'><i style='vertical-align:top;cursor:pointer;font-size:15px;' title='<?php print __esc('Check SQL Syntax', 'exlist');?>' class='fas fa-check-circle deviceRecovering'> </i></span>");

		$('#sqlcheck').click(function() {
			$.get(urlPath+'/plugins/exlist/exlists.php?action=check&sql='+$('#sql_where').val(), function(data) {
				console.log(data);
				if (data.trim() == 'ok') {
					$('#sqlcheck').html("<i style='vertical-align:top;cursor:pointer;font-size:15px;' title='<?php print __esc('Check SQL Syntax', 'exlist');?>' class='fas fa-check-circle deviceUp'></i>");
				} else {
					$('#sqlcheck').html("<i style='vertical-align:top;cursor:pointer;font-size:15px;' title='<?php print __esc('Check SQL Syntax', 'exlist');?>' class='fas fa-check-circle deviceDown'></i>");
				}
			});
		});
	});
	</script>
	<?php
}

function exlists() {
	global $config, $colors, $actions, $item_rows;

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
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => ''
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

	validate_store_request_vars($filters, 'sess_exlist');
	/* ================= input validation ================= */

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = urlPath + 'plugins/exlist/exlists.php?header=false';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = urlPath + 'plugins/exlist/exlists.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#clear').click(function() {
			clearFilter();
		});

		$('#lists').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Job Detail Exception Lists', 'exlist'), '100%', '', '3', 'center', 'exlists.php?action=edit');

	?>
	<tr class='odd'>
		<td>
		<form id='lists' action='exlists.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'exlist');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Rows', 'exlist');?>
					</td>
					<td width='1'>
						<select name='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var_request('rows') == '-1') {?> selected<?php }?>><?php print __('Default', 'exlist');?></option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'" . (get_request_var_request('rows') == $key ? ' selected':'') . '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input id='go' type='submit' value='<?php print __esc('Go', 'exlist');?>' title='<?php print __esc('Set/Refresh Filters', 'exlist');?>'>
					</td>
					<td>
						<input id='clear' type='button' value='<?php print __esc('Clear', 'exlist');?>' title='<?php print __esc('Clear Filters', 'exlist');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	if (get_request_var('filter') != '') {
		$sql_where = 'WHERE name LIKE ' . db_qstr('%'. get_request_var('filter') . '%');
	} else {
		$sql_where = '';
	}

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell("SELECT COUNT(*) FROM grid_jobs_exlists AS gcon $sql_where");

	$exlists = db_fetch_assoc("SELECT * FROM grid_jobs_exlists $sql_where $sql_order $sql_limit");

	$display_text = array(
		'name'    => array(
			'display' => __('List Name', 'exlist'),
			'sort'    => 'ASC'
		),
		'enabled' => array(
			'display' => __('Enabled', 'exlist'),
			'sort'    => 'ASC'
		),
		'nosort1' => array(
			'display' => __('SQL Where', 'exlist'),
			'sort'    => 'ASC'
		)
	);

	$nav = html_nav_bar('exlists.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, (cacti_sizeof($display_text)+1), __('Filters', 'analytics'), 'page', 'main');

	form_start('exlists.php?action=edit&id=' . get_request_var('id'), 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var_request('sort_column'), get_request_var_request('sort_direction'), false);

	if (sizeof($exlists)) {
		foreach ($exlists as $c) {
			$id = $c['id'];
			form_alternate_row('line' . $id);
			form_selectable_cell(filter_value($c['name'], get_request_var('filter'), 'exlists.php?action=edit&tab=general&id=' . $id), $id);
			form_selectable_cell($c['enabled'] == 'on' ? __('Yes', 'exlist'):__('No', 'exlist'), $id, '5%');
			form_selectable_cell(html_escape($c['sql_where']), $id);
			form_checkbox_cell($c['name'], $id);
			form_end_row();
		}
	} else {
		print "<tr class='tableRow'><td colspan='" . (cacti_sizeof($display_text)+1) . "'><em>" . __('No Job Detail Exception Filters Found', 'exlist') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($exlists)) {
		print $nav;
	}

	form_hidden_box('save_list', '1', '');

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($actions);

	form_end();
}

