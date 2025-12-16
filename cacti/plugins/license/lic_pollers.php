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

chdir('../../');
include('./include/auth.php');
include_once('./plugins/license/include/lic_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

$lic_poller_actions = array(
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

		lic_poller_edit();

		bottom_footer();
		break;
	default:
		top_header();

		lic_pollers();

		bottom_footer();
		break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save() {
	input_validate_input_number(get_request_var('poller_id'));

	if ((isset_request_var('save_component_lic_poller')) && (isempty_request_var('add_dq_y'))) {
		$poller_id = api_lic_poller_save(get_request_var('poller_id'), get_request_var('lic_poller_path'),
			get_request_var('lic_poller_description'), get_request_var('lic_poller_hostname'),
			get_request_var('lic_poller_type'), get_request_var('client_path'));

		if ((is_error_message()) || (get_request_var('poller_id') != get_request_var('_poller_id'))) {
			header('Location: lic_pollers.php?action=edit&poller_id=' . (empty($poller_id) ? get_request_var('poller_id') : $poller_id));
		}else{
			header('Location: lic_pollers.php');
		}
	}
}

/* ------------------------
	The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $lic_poller_actions, $fields_lic_poller_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		if (get_request_var('drp_action') == '1') { /* delete */
			for ($i=0; $i<count($selected_items); $i++) {
			/* ================= input validation ================= */
			input_validate_input_number($selected_items[$i]);
			/* ==================================================== */

			api_lic_poller_remove($selected_items[$i]);
			}
		}
		}

		header('Location: lic_pollers.php');
		exit;
	}

	/* setup some variables */
	$lic_poller_list = ''; $lic_poller_array = array();

	/* loop through each of the pollers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$lic_poller_info = db_fetch_cell_prepared('SELECT poller_description FROM lic_pollers WHERE id=?', array($matches[1]));
			$lic_poller_list .= '<li>' . htmlspecialchars($lic_poller_info) . '</li>';
			$lic_poller_array[] = $matches[1];
		}
	}

	top_header();

	form_start('lic_pollers.php');

	html_start_box($lic_poller_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($lic_poller_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __("If you click 'Continue', the following License Poller(s) will be deleted.") . "</p>
					<ul>$lic_poller_list</ul>
				</td>
			</tr>\n";

			$title = 'Delete License Poller(s)';
		}

		$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='" . __esc($title) . "'>";
	}else{
		raise_message('licp40', __('You must select at least one License Poller.'), MESSAGE_LEVEL_ERROR);
		header('Location: lic_applications.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($lic_poller_array) ? serialize($lic_poller_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

/* ---------------------
	Poller Functions
   --------------------- */

function get_lic_poller_records() {
	$sort_order = get_order_string();

	$query_string = "SELECT lp.*, lm.name
		FROM lic_pollers AS lp
		LEFT JOIN lic_managers AS lm
		ON lp.poller_type=lm.id
		$sort_order";

	return db_fetch_assoc($query_string);
}

function lic_poller_edit() {
	global $fields_lic_poller_edit, $config;

	/* ================= input validation ================= */
	get_filter_request_var('poller_id');
	/* ==================================================== */

	if (!isempty_request_var('poller_id') && get_request_var('poller_id')>0 ) {
		$lic_poller = db_fetch_row_prepared('SELECT * FROM lic_pollers WHERE id=?', array(get_request_var('poller_id')));

		$header_label = 'RTM License Poller [edit: ' . htmlspecialchars($lic_poller['poller_description']) . ']';
		$fields_lic_poller_edit['lic_manager_command_path']['default']=$lic_poller['client_path'];
	}else{
		$header_label = 'RTM License Poller [new]';
	}

	/*file: lic_pollers.php action: edit*/
	$fields_lic_poller_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Information'
		),
		'lic_poller_description' => array(
			'method' => 'textbox',
			'friendly_name' => 'License Poller Name',
			'description' => 'Enter a descriptive name for this License Poller.',
			'value' => '|arg1:poller_description|',
			'max_length' => '255'
		),
		'lic_poller_type' => array(
			'method' => 'drop_sql',
			'friendly_name' => 'License Manager',
			'description' => 'Choose the License Manager that this License Poller will be used to collect data for.',
			'value' => '|arg1:poller_type|',
			'default' => '1',
			'sql' => 'SELECT id, name FROM lic_managers WHERE disabled <> 1 ORDER BY name'
		),
		'client_path' => array(
			'method' => 'textbox',
			'friendly_name' => 'Collector File Path',
			'description' => 'Enter the binary location path of the License Manager command. For example, for RLM poller, the path is <span class="filepath">/opt/IBM/rlm/bin/rlmstat</span> and for FlexLM poller, the path is <span class="filepath">/opt/IBM/flexlm/bin/lmstat</span>.',
			'value' => '|arg1:client_path|',
			'default' => '',
			'max_length' => '255',
			'size' => 80
		),
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => 'License Poller Path'
		),
		'lic_poller_path' => array(
			'method' => 'textbox',
			'friendly_name' => 'License Poller Bin Directory',
			'description' => 'Enter the path of the License Poller Binary that contains licflexpoller/liclmpoller/licjsonpoller. For example, <span class="filepath"> /opt/IBM/rtm/lic/bin</span>.<br/>><b>Note:</b> This is the location of the Collector Binary defined under License Managers.',
			'value' => '|arg1:poller_path|',
			'max_length' => '255'
		),
		'lic_poller_hostname' => array(
			'method' => 'textbox',
			'friendly_name' => 'Remote License Poller Location',
			'description' => 'Enter the location of remote poller.<br /><b>Note:</b> That this field must match the Poller_Loc setting in the<span class="filepath"> lic.conf</span> file.',
			'value' => '|arg1:poller_hostname|',
			'max_length' => '255'
		),
		'poller_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:id|'
		),
		'_poller_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:id|'
			),
		'save_component_lic_poller' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	form_start('lic_pollers.php', 'licp_form');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_lic_poller_edit, (isset($lic_poller) ? $lic_poller : array()))
		)
	);

	html_end_box();

	form_save_button('lic_pollers.php', '', 'poller_id');

	if (isempty_request_var('poller_id')) {
		$cacti_path = $config['base_path'];
		$real_path  = realpath("$cacti_path/..");
		$script_str = "
			<script type='text/javascript'>
			$().ready(function(data) {
				changeLicServertype();
			});
			function changeLicServertype(){
				type = document.getElementById('lic_poller_type').value;
				if (type == 1){
					$('#lic_manager_command_path').val('$real_path/flexlm/bin/');
				}else if(type == 3){
					$('#lic_manager_command_path').val('$real_path/rlm/bin/');
				}
			}
			if (document.getElementById('lic_poller_type')) {
				document.getElementById('lic_poller_type').onchange = changeLicServertype;
			}
			</script>\n";

		echo $script_str;
	}
}

function lic_pollers() {
	global $lic_poller_actions, $config;

	$filters = array(
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'poller_description',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'poller_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
		)
	);

	validate_store_request_vars($filters, 'sess_lp');

	$debug_log = debug_log_return('lic_pollers');

	if (!empty($debug_log)) {
		debug_log_clear('lic_pollers');
		?>
		<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>
			<tr class='odd'>
				<td style='padding: 3px; font-family: monospace;'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
	<?php
	}

	html_start_box(__('License Pollers') . rtm_hover_help('license_poller_config_rtm.html', __('Learn More')), '100%', '', '3', 'center', 'lic_pollers.php?action=edit&poller_id=' . get_request_var('poller_id'));
	html_end_box(true);

	$lic_pollers = get_lic_poller_records();

	$display_text = array(
		'poller_description' => array('display' => __('Name'), 'sort' => 'ASC'),
		'id'                 => array('display' => __('ID'), 'sort' => 'ASC'),
		'name'               => array('display' => __('Manager'), 'sort' => 'ASC'),
		'nosort0'            => array('display' => __('Status'), 'sort' => 'ASC'),
		'poller_hostname'    => array('display' => __('Location'), 'sort' => 'ASC'),
		'poller_path'        => array('display' => __('Path'), 'sort' => 'ASC'),
		'nosort1'            => array('display' => __('Last Launch'), 'align' => 'right', 'sort' => 'ASC')
	);

	$stats = array();
	$start_times = db_fetch_assoc("SELECT * FROM settings WHERE name LIKE 'lic_prev_start_server_%'");
	if (cacti_sizeof($start_times)) {
		foreach($start_times as $t) {
			$portatserver = str_replace('lic_prev_start_server_', '', $t['name']);
			$poller       = db_fetch_cell_prepared('SELECT poller_id FROM lic_services WHERE service_id = ?', array($portatserver));

			if (!empty($poller)) {
				if (!isset($stats[$poller])) {
					$stats[$poller] = $t['value'];
				}elseif ($t['value'] > $stats[$poller]) {
					$stats[$poller] = $t['value'];
				}
			}
		}
	}

	form_start('lic_pollers.php', 'chk');

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($lic_pollers)) {
		foreach ($lic_pollers as $lic_poller) {
			if (isset($stats[$lic_poller['id']])) {
				if (time() - $stats[$lic_poller['id']] < 300) {
					$status = '<font color=green>Up</font>';
					$date   = date('Y-m-d H:i:s', $stats[$lic_poller['id']]);
				}else{
					$status = '<font color=red>Down</font>';
					$date   = date('Y-m-d H:i:s', $stats[$lic_poller['id']]);
				}
			}else{
				$status = '<font color=blue>Unknown</font>';
				$date   = 'Unknown';
			}

			form_alternate_row('line' . $lic_poller['id'], true);

			$url = htmlspecialchars($config['url_path'] . 'plugins/license/lic_pollers.php?action=edit&poller_id=' . $lic_poller['id']) ;
			form_selectable_cell(filter_value($lic_poller['poller_description'], '', $url), $lic_poller['id']);

			form_selectable_cell($lic_poller['id'], $lic_poller['id']);
			form_selectable_cell($lic_poller['name'], $lic_poller['id']);
			form_selectable_cell($status, $lic_poller['id']);
			form_selectable_cell(htmlspecialchars($lic_poller['poller_hostname']), $lic_poller['id']);
			form_selectable_cell(htmlspecialchars($lic_poller['poller_path']), $lic_poller['id']);
			form_selectable_cell($date, $lic_poller['id'], '', 'text-align:right;');
			form_checkbox_cell($lic_poller['poller_description'], $lic_poller['id']);
			form_end_row();
		}
	}else{
		print "<tr><td colspan='5'><em>No RTM License Pollers Found</em></td></tr>";
	}

	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($lic_poller_actions);

	form_end();

	bottom_footer();
}
