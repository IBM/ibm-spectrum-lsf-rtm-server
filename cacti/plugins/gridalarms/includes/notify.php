<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2022                                          |
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

function gridalarms_notify_list_tabs($tabs) {
	$tabs += array(
		'alerts'     => __('Alerts', 'gridalarms'),
		'atemplates' => __('Alert Templates', 'gridalarms')
	);

	return $tabs;
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function gridalarms_notify_list_save($save) {
	global $actions, $assoc_actions;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		if (isset_request_var('save_alerts')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
			input_validate_input_number(get_request_var('notification_action'));

			if ($selected_items != false) {
				if (get_request_var('drp_action') == '1') { /* associate */
					for ($i=0;($i<count($selected_items));$i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						/* set the notification list */
						db_execute_prepared('UPDATE gridalarms_alarm
							SET notify_alert = ?
							WHERE id = ?',
							array(get_request_var('id'), $selected_items[$i]));
					}
				}elseif (get_request_var('drp_action') == '2') { /* disassociate */
					for ($i=0;($i<count($selected_items));$i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						/* set the notification list */
						db_execute_prepared('UPDATE gridalarms_alarm
							SET notify_alert = 0
							WHERE id = ?
							AND notify_alert = ?',
							array($selected_items[$i], get_request_var('id')));
					}
				}
			}

			header('Location: notify_lists.php?action=edit&header=false&tab=alerts&id=' . get_request_var('id'));

			exit;
		}elseif (isset_request_var('save_atemplates')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
			input_validate_input_number(get_request_var('notification_action'));

			if ($selected_items != false) {
				if (get_request_var('drp_action') == '1') { /* associate */
					for ($i=0;($i<count($selected_items));$i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						/* set the notification list */
						db_execute_prepared('UPDATE gridalarms_template
							SET notify_alert = ?
							WHERE id = ?',
							array(get_request_var('id'), $selected_items[$i]));
					}
				}elseif (get_request_var('drp_action') == '2') { /* disassociate */
					for ($i=0;($i<count($selected_items));$i++) {
						/* ================= input validation ================= */
						input_validate_input_number($selected_items[$i]);
						/* ==================================================== */

						/* set the notification list */
						db_execute_prepared('UPDATE gridalarms_template
							SET notify_alert = 0
							WHERE id = ?
							AND notify_alert = ?',
							array($selected_items[$i], get_request_var('id')));
					}
				}
			}

			header('Location: notify_lists.php?action=edit&header=false&tab=atemplates&id=' . get_request_var('id'));
			exit;
		}
	}

	return $save;
}

function gridalarms_notify_list_form_confirm($save) {
	global $assoc_actions;
	/* setup some variables */
	$list = ''; $array = array(); $list_name = '';

	if (isset_request_var('id')) {
		$list_name = db_fetch_cell_prepared('SELECT name
			FROM plugin_notification_lists
			WHERE id = ?',
			array(get_request_var('id')));
	}

	top_header();

	form_start('notify_lists.php', 'chk');

	if (isset_request_var('save_alerts')) {
		/* loop through each of the notification lists selected on the previous page and get more info about them */
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				$name = db_fetch_cell_prepared('SELECT name
					FROM gridalarms_alarm
					WHERE id = ?',
					array($matches[1]));

				$list .= '<li>' . html_escape($name) . '</li>';
				$array[] = $matches[1];
			}
		}

		html_start_box(__('%s Alerts', $assoc_actions[get_request_var('drp_action')], 'gridalarms'), '60%', '', '3', 'center', '');

		if (cacti_sizeof($array)) {
			if (get_request_var('drp_action') == '1') { /* associate */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Associate the Alerts below with the Notification List \'<b>%s\'.', html_escape($list_name), 'gridalarms') . "</p>
						<div class='itemlist'><ul>$list</ul></div>
					</td>
				</tr>\n";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Associate Notification Lists', 'gridalarms') . "'>";
			}elseif (get_request_var('drp_action') == '2') { /* disassociate */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Disassociate the Alerts below from the Notification List \'<b>%s</b>\'.', html_escape($list_name), 'gridalarms') . "</p>
						<div class='itemlist'><ul>$list</ul></div>
					</td>
				</tr>";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Disassociate Notification Lists', 'gridalarms') . "'>";
			}
		} else {
			raise_message(40);
			header('Location: notify_lists.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
			exit;
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='save_alerts' value='1'>
				<input type='hidden' name='selected_items' value='" . (isset($array) ? serialize($array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	}elseif (isset_request_var('save_atemplates')) {
		/* loop through each of the notification lists selected on the previous page and get more info about them */
		foreach ($_POST as $var => $val) {
			if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				$name = db_fetch_cell_prepared('SELECT name
					FROM gridalarms_template
					WHERE id = ?',
					array($matches[1]));

				$list .= '<li>' . html_escape($name) . '</li>';
				$array[] = $matches[1];
			}
		}

		html_start_box(__('%s Alert Templates', $assoc_actions[get_request_var('drp_action')], 'gridalarms'), '60%', '', '3', 'center', '');

		if (cacti_sizeof($array)) {
			if (get_request_var('drp_action') == '1') { /* associate */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Associate the Alert Templates below with the Notification List \'<b>%s</b>\'.', html_escape($list_name), 'gridalarms') . "</p>
						<div class='itemlist'><ul>$list</ul></div>
					</td>
				</tr>\n";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Associate Notification Lists', 'gridalarms') . "'>";
			}elseif (get_request_var('drp_action') == '2') { /* disassociate */
				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Disassociate the Alert Templates below from the Notification List \'<b>%s</b>\'.', html_escape($list_name), 'gridalarms') . "</p>
						<div class='itemlist'><ul>$list</ul></div>
					</td>
				</tr>";

				$save_html = "<input type='button' value='" . __esc('Cancel', 'gridalarms') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' title='" . __esc('Disassociate Notification Lists', 'gridalarms') . "'>";
			}
		} else {
			raise_message(40);
			header('Location: notify_lists.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
			exit;
		}

		print "<tr>
			<td class='saveRow'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='id' value='" . get_request_var('id') . "'>
				<input type='hidden' name='save_atemplates' value='1'>
				<input type='hidden' name='selected_items' value='" . (isset($array) ? serialize($array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
			</td>
		</tr>";

		html_end_box();
	}

	form_end();

	bottom_footer();

	return $save;
}

function gridalarms_notify_list_display($save) {
	global $tabs_thold, $config;

	$current_tab  = $save['current_tab'];
	$header_label = $save['header_label'];

	get_filter_request_var('id');

	if ($current_tab == 'alerts') {
		alerts($header_label);
	}elseif ($current_tab == 'atemplates') {
		atemplates($header_label);
	}
}

function alerts($header_label) {
	global $item_rows, $config, $assoc_actions;
	$sql_params = array();

	nl_alarm_request_validation();

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$statefilter='';
	if (isset_request_var('state')) {
		if (get_request_var('state') == '-1') {
			$statefilter = '';
		} else {
			if (get_request_var('state') == '0') { $statefilter = "gridalarms_alarm.alarm_enabled='off'"; }
			if (get_request_var('state') == '2') { $statefilter = "gridalarms_alarm.alarm_enabled='on'"; }
			if (get_request_var('state') == '1') { $statefilter = 'gridalarms_alarm.alarm_alert!=0'; }
		}
	}

	$sql_where = '';
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ",$rows";

	if (!isempty_request_var('template') && get_request_var('template') != '-1') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.template_id=?';
		$sql_params[] = get_request_var('template');
	}

	if (strlen(get_request_var('filter'))) {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . "gridalarms_alarm.name LIKE ?";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if ($statefilter != '') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . $statefilter;
	}

	if (get_request_var('associated') == 'true') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . '(notify_alert=?)';
		$sql_params[] = get_request_var('id');
	}

	$sql = "SELECT * FROM gridalarms_alarm
		$sql_where
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);

	$alarm_templates = db_fetch_assoc('SELECT gridalarms_template.id, gridalarms_template.name
		FROM gridalarms_template
		ORDER BY gridalarms_template.name');

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'notify_lists.php?action=edit&header=false&tab=alerts&id=<?php print get_request_var('id');?>'
		strURL += '&associated=' + $('#associated').is(':checked');
		strURL += '&state=' + $('#state').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&template=' + $('#template').val();
		strURL += '&filter=' + $('#filter').val();

		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'notify_lists.php?action=edit&header=false&tab=alerts&id=<?php print get_request_var('id');?>&clear=true'
		loadPageNoHeader(strURL);
	}

	</script>
	<?php

	html_start_box(__('Associated Alerts %s', html_escape($header_label)), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
			<form id='listthold' method='get' action='notify_lists.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridalarms');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print html_escape(get_request_var('filter'));?>' onChange='applyFilter()'>
					</td>
					<td>
						<?php print __('Template', 'gridalarms');?>
					</td>
					<td>
						<select id='template' onChange='applyFilter()'>
							<option value='-1'><?php print __('Any', 'gridalarms');?></option>
							<?php
							foreach ($alarm_templates as $row) {
								echo "<option value='" . $row['id'] . "'" . (isset_request_var('template') && $row['id'] == get_request_var('template') ? ' selected' : '') . '>' . html_escape($row['name']) . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('State', 'gridalarms');?>
					</td>
					<td>
						<select id='state' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('state') == '-1') {?> selected<?php }?>><?php print __('All', 'gridalarms');?></option>
							<option value='1'<?php if (get_request_var('state') == '1') {?> selected<?php }?>><?php print __('Triggered', 'gridalarms');?></option>
							<option value='2'<?php if (get_request_var('state') == '2') {?> selected<?php }?>><?php print __('Enabled', 'gridalarms');?></option>
							<option value='0'<?php if (get_request_var('state') == '0') {?> selected<?php }?>><?php print __('Disabled', 'gridalarms');?></option>
						</select>
					</td>
					<td>
						<?php print __('Alerts', 'gridalarms');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default:'.read_config_option('num_rows_table'), 'gridalarms');?></option>
							<?php
							if (cacti_sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' name='associated' id='associated' onClick='applyFilter()' <?php print (get_request_var('associated') == 'true' || get_request_var('associated') == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'><?php print __('Associated', 'gridalarms');?></label>
					</td>
					<td>
						<span>
							<input class='ui-button ui-corner-all ui-widget' type='button' id='go' value='<?php print __esc('Go', 'gridalarms');?>' onClick='applyFilter()' title='<?php print __esc('Set/Refresh Filters', 'gridalarms');?>'>
							<input class='ui-button ui-corner-all ui-widget' type='button' id='clear' value='<?php print __esc('Clear', 'gridalarms');?>' onClick='clearFilter()' title='<?php print __esc('Clear Filters', 'gridalarms');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = count(db_fetch_assoc_prepared("SELECT gridalarms_alarm.id
		FROM gridalarms_alarm
		$sql_where", $sql_params));

	$display_text = array(
		'name'          => array('display' => __('Name', 'gridalarms'),        'sort' => 'ASC'),
		'id'            => array('display' => __('ID', 'gridalarms'),          'sort' => 'ASC'),
		'nosort2'       => array('display' => __('Alert Lists', 'gridalarms'), 'sort' => 'ASC'),
		'alarm_type'    => array('display' => __('Type', 'gridalarms'),        'sort' => 'ASC'),
		'alarm_alert'   => array('display' => __('Triggered', 'gridalarms'),   'sort' => 'ASC'),
		'alarm_enabled' => array('display' => __('Enabled', 'gridalarms'),     'sort' => 'ASC')
	);

	$nav = html_nav_bar('notify_lists.php?action=edit&tab=alerts&id=' . get_request_var('id'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 10, __('Alerts', 'thold'), 'page', 'main');

	/* print checkbox form for validation */
	form_start('notify_lists.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'notify_lists.php?action=edit&tab=alerts&id=' . get_request_var('id'));

	$types = array(
		__('High/Low', 'gridalarms'),
		__('Time Based', 'gridalarms'));

	if (count($result)) {
		foreach ($result as $row) {
			$alertstat = __('No', 'gridalarms');

			$bgcolor='green';

			if ($row['alarm_alert'] != 0) {
				$alertstat = __('Yes', 'gridalarms');
			}

			/* show alert stats first */
			$alert_stat = '';
			$list = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM gridalarms_alarm_contacts
				WHERE alarm_id = ?',
				array($row['id']));

			if ($list > 0) {
				$alert_stat = "<span class='deviceUp'>" . __('Select Users', 'gridalarms') . "</span>";
			}

			if (strlen($row['notify_extra'])) {
				$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span class='deviceRecovering'>" . __('Specific Emails', 'gridalarms') . "</span>";
			}

			if (!empty($row['notify_alert'])) {
				if (get_request_var('id') == $row['notify_alert']) {
					$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span class='deviceUp'>" . __('Current List', 'gridalarms') . "</span>";
				}else{
					$alert_info = db_fetch_cell_prepared('SELECT name
						FROM plugin_notification_lists
						WHERE id = ?',
						array($row['notify_alert']));

					if ($alert_info != '') {
						$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span style='deviceDown'>" . $alert_info . '</span>';
					} else {
						$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span style='deviceDown'>" . __('Unknown Alert', 'gridalarms') . '</span>';
					}
				}
			}

			if (!strlen($alert_stat)) {
				$alert_stat = "<span class='deviceUnknown'>" . __('Log Only', 'gridalarms') . '</span>';
			}

			if ($row['name'] != '') {
				$name = $row['name'];
			}else{
				$name = $row['name'] . ' [' . $row['data_source_name'] . ']';
			}

			form_alternate_row('line' . $row['id'], true);

			form_selectable_cell(filter_value($name, get_request_var('filter')), $row['id']);
			form_selectable_cell($row['id'], $row['id']);
			form_selectable_cell($alert_stat, $row['id']);
			form_selectable_cell($types[$row['alarm_type']], $row['id']);
			form_selectable_cell($alertstat, $row['id']);
			form_selectable_cell((($row['alarm_enabled'] == 'off') ? 'Disabled': 'Enabled'), $row['id']);
			form_checkbox_cell($name, $row['id']);

			form_end_row();
		}
	} else {
		print '<tr><td colspan="8"><i>' . __('No Alerts Found', 'gridalarms') . '</i></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}

	form_hidden_box('action', 'actions', '');
	form_hidden_box('tab', 'alerts', '');
	form_hidden_box('id', get_request_var('id'), '');
	form_hidden_box('save_alerts', '1', '');

	draw_actions_dropdown($assoc_actions);

	form_end();
}

function atemplates($header_label) {
	global $config, $item_rows, $assoc_actions;
	$sql_params = array();

	nl_alarm_template_request_validation();

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';
	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ", $rows";

	if (get_request_var('associated') == 'true') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . "(notify_alert=?)";
		$sql_params[] = get_request_var('id');
	}

	if (strlen(get_request_var('filter'))) {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . "gridalarms_template.name LIKE ?";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql = "SELECT *
		FROM gridalarms_template
		$sql_where
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);

	html_start_box(__('Associated Alert Templates %s', html_escape($header_label), 'gridalarms'), '100%', '', '3', 'center', '');

	?>
	<tr class='even noprint'>
		<td>
			<form id='listthold' method='get' action='notify_lists.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridalarms');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print html_escape(get_request_var('filter'));?>' onChange='applyFilter()'>
					</td>
					<td>
						<?php print __('Alert Templates', 'gridalarms');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default:'.read_config_option('num_rows_table'), 'gridalarms');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='checkbox' id='associated' onClick='applyFilter()' <?php print (get_request_var('associated') == 'true' || get_request_var('associated') == 'on' ? 'checked':'');?>>
					</td>
					<td>
						<label for='associated'><?php print __('Associated', 'gridalarms');?></label>
					</td>
					<td>
						<span>
							<input class='' type='button' id='go' value='<?php print __esc('Go', 'gridalarms');?>' onClick='applyFilter()' title='<?php print __esc('Set/Refresh Filters', 'gridalarms');?>'>
							<input class='' type='button' id='clear' value='<?php print __esc('Clear', 'gridalarms');?>' onClick='clearFilter()' title='<?php print __esc('Clear Filters', 'gridalarms');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>
			function applyFilter() {
				strURL  = 'notify_lists.php?action=edit&header=false&tab=atemplates&id=<?php print get_request_var('id');?>'
				strURL += '&associated=' + $('#associated').is(':checked');
				strURL += '&rows=' + $('#rows').val();
				strURL += '&filter=' + $('#filter').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter(objForm) {
				strURL = 'notify_lists.php?action=edit&header=false&tab=atemplates&id=<?php print get_request_var('id');?>&clear=true'
				loadPageNoHeader(strURL);
			}

			</script>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT count(*)
		FROM gridalarms_template
		$sql_where", $sql_params);

	$display_text = array(
		'name'       => array('display' => __('Name', 'gridalarms'),        'sort' => 'ASC'),
		'id'         => array('display' => __('ID', 'gridalarms'),          'sort' => 'ASC'),
		'nosort2'    => array('display' => __('Alert Lists', 'gridalarms'), 'sort' => 'ASC'),
		'alarm_type' => array('display' => __('Type', 'gridalarms'),        'sort' => 'ASC')
	);

	$nav = html_nav_bar('notify_lists.php?action=edit&tab=atemplates&id=' . get_request_var('id'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 10, __('Templates', 'thold'), 'page', 'main');

	/* print checkbox form for validation */
	form_start('notify_lists.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, 'notify_lists.php?action=edit&tab=atemplates&id=' . get_request_var('id'));

	$types = array('High/Low', 'Baseline Deviation', 'Time Based');
	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			/* show alert stats first */
			$alert_stat = '';
			$list = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM gridalarms_template_contacts
				WHERE alarm_id = ?',
				array($row['id']));

			if ($list > 0) {
				$alert_stat = "<span class='deviceUp'>" . __('Select Users', 'gridalarms') . '</span>';
			}

			if (strlen($row['notify_extra'])) {
				$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span class='deviceRecovering'>" . __('Specific Emails', 'gridalarms') . '</span>';
			}

			if (!empty($row['notify_alert'])) {
				if (get_request_var('id') == $row['notify_alert']) {
					$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span class='deviceUp'>" . __('Current List', 'gridalarms') . '</span>';
				}else{
					$alert_info = db_fetch_cell_prepared('SELECT name
						FROM plugin_notification_lists
						WHERE id = ?',
						array($row['notify_alert']));

					if ($alert_info != '') {
						$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span style='deviceDown'>" . $alert_info . '</span>';
					} else {
						$alert_stat .= (strlen($alert_stat) ? ', ':'') . "<span style='deviceDown'>" . __('Unknown Template', 'gridalarms') . '</span>';
					}
				}
			}

			if (!strlen($alert_stat)) {
				$alert_stat = "<span class='deviceUnknown'>" . __('Log Only', 'gridalarms') . '</span>';
			}

			form_alternate_row('line' . $row['id'], true);

			form_selectable_cell(filter_value($row['name'], get_request_var('filter')), $row['id']);
			form_selectable_cell($row['id'], $row['id']);
			form_selectable_cell($alert_stat, $row['id']);
			form_selectable_cell($types[$row['alarm_type']], $row['id']);
			form_checkbox_cell(html_escape($row['name']), $row['id']);

			form_end_row();
		}
	} else {
		print '<tr><td colspan="5"><em>' . __('No Alert Templates Found', 'gridalarms') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}

	form_hidden_box('action', 'actions', '');
	form_hidden_box('tab', 'atemplates', '');
	form_hidden_box('id', get_request_var('id'), '');
	form_hidden_box('save_atemplates', '1', '');

	draw_actions_dropdown($assoc_actions);

	form_end();
}

function nl_alarm_template_request_validation() {
	global $title, $rows_selector, $config, $reset_multi;

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
		'associated' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_nlatt');
	/* ================= input validation ================= */
}

function nl_alarm_request_validation() {
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
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'state' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'template' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '',
			),
		'associated' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_nlat');
}
