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
include_once('./plugins/license/include/lic_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

$lic_manager_actions = array(
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

	lic_manager_edit();

	bottom_footer();
	break;
default:
	top_header();

	lic_managers();

	bottom_footer();
	break;
}

/* --------------------------
	The Save Function
   -------------------------- */

function form_save() {
	input_validate_input_number(get_request_var('id'));

	if (isset_request_var('save_component_lic_manager')) {
		$id = api_lic_manager_save(get_request_var('id'), get_request_var('name'), get_request_var('description'),
			get_request_var('collector_binary'), get_request_var('lm_client'), get_request_var('lm_client_arg1'),
			get_request_var('failover_hosts'), isset_request_var('disabled') ? get_request_var('disabled'):'');

		if (is_error_message()) {
			header('Location: lic_managers.php?action=edit&id=' . (empty($id) ? get_request_var('id') : $id));
		}else{
			header('Location: lic_managers.php');
		}
	}
}

/* ------------------------
	The 'actions' function
   ------------------------ */

function api_lic_manager_save($id, $name, $description, $collector_binary, $lm_client, $lm_client_arg1, $failover_hosts, $disabled = '') {
	if ($id) {
		$save['id']   = $id;
	} else {
		$save['id']   = '';
		$save['hash'] = generate_hash();
	}

	$save['name']             = form_input_validate($name,             'name', '', false, 3);
	$save['description']      = form_input_validate($description,      'description', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 3);
	$save['collector_binary'] = form_input_validate($collector_binary, 'collector_binary', '^[A-Za-z0-9]+$', false, 3);
	$save['lm_client']        = form_input_validate($lm_client,        'lm_client', '', false, 3);
	$save['lm_client_arg1']   = form_input_validate($lm_client_arg1,   'lm_client_arg1', '', true, 3);
	$save['failover_hosts']   = form_input_validate($failover_hosts,   'failover_hosts', '^[0-9]+$', true, 3);

	$id = 0;

	if (!is_error_message()) {
		$id = sql_save($save, 'lic_managers', 'id');
		if ($id) {
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	return $id;
}

function form_actions() {
	global $config, $lic_manager_actions, $fields_lic_manager_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		if (get_request_var('drp_action') == '1') { /* delete */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_manager_remove($selected_items[$i]);
			}
		}
		}

		header('Location: lic_managers.php');
		exit;
	}

	if(!isset($lic_manager_actions[get_request_var('drp_action')])) {
		header('Location: lic_managers.php?header=false');
		exit;
	}

	/* setup some variables */
	$lic_manager_list = ''; $lic_manager_array = array();

	/* loop through each of the pollers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$lic_manager_info = db_fetch_cell_prepared('SELECT name FROM lic_managers WHERE id=?', array($matches[1]));
			$lic_manager_list .= '<li>' . htmlspecialchars($lic_manager_info) . '</li>';
			$lic_manager_array[] = $matches[1];
		}
	}

	top_header();

	form_start('lic_managers.php');

	html_start_box($lic_manager_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($lic_manager_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
			<td class='textArea'>
				<p>" . __("Click 'Continue' to Delete the following License Managers.") . "</p>
					<ul>$lic_manager_list</ul>
				</td>
			</tr>";

			$title = 'Delete License Manager(s)';
		}

		$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='" . __esc($title) . "'>";
	}else{
		raise_message('licmgr40', __('You must select at least one License Manager.'), MESSAGE_LEVEL_ERROR);
		header('Location: lic_managers.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($lic_manager_array) ? serialize($lic_manager_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

/* ---------------------
	Manager Functions
   --------------------- */

function get_lic_manager_records() {
	$sort_order = get_order_string();

	$query_string = "SELECT lm.*,
		COUNT(DISTINCT ls.service_id) AS totals,
		SUM(IF(ls.status IN (2, 3) AND ls.disabled='', 1, 0)) AS total_up,
		SUM(IF(ls.status IN (0, 1) AND ls.disabled='', 1, 0)) AS total_down,
		SUM(IF(ls.disabled='on', 1, 0)) AS total_disabled
		FROM lic_managers AS lm
		LEFT JOIN lic_pollers AS lp
		ON lm.id=lp.poller_type
		LEFT JOIN lic_services AS ls
		ON lp.id = ls.poller_id
		WHERE lm.disabled=''
		GROUP BY lm.id
		$sort_order";

	return db_fetch_assoc($query_string);
}

function lic_manager_edit() {
	global $fields_lic_manager_edit, $config;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if (!isempty_request_var('id') && get_request_var('id')>0) {
		$lic_manager = db_fetch_row_prepared('SELECT *
			FROM lic_managers
			WHERE id=?', array(get_request_var('id')));

		$header_label = 'RTM License Manager [edit: ' . htmlspecialchars($lic_manager['name']) . ']';
		$fields_lic_manager_edit['lic_manager_command_path']['default']=$lic_manager['lm_client'];
	}else{
		$header_label = 'RTM License Manager [new]';
	}

	$fields_lic_manager_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Information'
		),
		'name' => array(
			'method' => 'textbox',
			'friendly_name' => 'Name',
			'description' => 'Enter a short name or acronym for this License Manager.',
			'value' => '|arg1:name|',
			'max_length' => '20',
			'default' => 'New',
			'size' => '20'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => 'Description',
			'description' => 'Enter a description for this License Manager.',
			'value' => '|arg1:description|',
			'max_length' => '100',
			'default' => '',
			'size' => '80'
		),
		'failover_hosts' => array(
			'method' => 'drop_array',
			'friendly_name' => 'Failover Method',
			'description' => 'License Managers support multiple Failover methods.  Most Managers can be setup in a single server setup, but others support both two and three server setups.  Indicate which type of Failover that this License Manager supports.',
			'value' => '|arg1:failover_hosts|',
			'array' => array(
				'0' => 'None',
				'2' => 'Dual',
				'3' => 'Quorum'
				),
			'default' => '0'
			),
		'collector_binary' => array(
			'method' => 'textbox',
			'friendly_name' => 'Collector Binary',
			'description' => 'Enter the name of the RTM collector binary for this License Manager.  For customer created LM parser, the preferred entry is \'licjsonpoller\'',
			'value' => '|arg1:collector_binary|',
			'max_length' => '100',
			'default' => 'licjsonpoller',
			'size' => '80'
		),
		'lm_client' => array(
			'method' => 'textbox',
			'friendly_name' => 'License Manager Binary Path',
   			'description' => 'Enter the License Managers collector binary file path. If the full path is not given, the Pollers path will be used. For customer created LM parser, the preferred entry is full path.',
			'value' => '|arg1:lm_client|',
			'max_length' => '40',
			'default' => '',
			'size' => '40'
		),
		'lm_client_arg1' => array(
			'method' => 'textbox',
			'friendly_name' => 'Collector Binary Arguments',
   			'description' => 'Optional arguments that will be passed to the Collector binary.',
			'value' => '|arg1:lm_client_arg1|',
			'max_length' => '40',
			'default' => '',
			'size' => '40'
		),
		'id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:id|'
			),
		'save_component_lic_manager' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	form_start('lic_managers.php', 'lic_mgr_form');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(array(
		'config' => array('no_form_tag' => true),
		'fields' => inject_form_variables($fields_lic_manager_edit, (isset($lic_manager) ? $lic_manager : array()))
	));

	html_end_box();

	form_save_button('lic_managers.php', '', 'id');

	?>
	<script type="text/javascript">
		function set_disable(){
			if($('#id').val()==1 || $('#id').val()==3)
				$('#failover_hosts').attr('disabled', true);
		}
		$(function() {
			set_disable();
		});
	</script>
	<?php
}

function lic_managers() {
	global $lic_manager_actions, $config;

	$filters = array(
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
		'id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
		)
	);

	validate_store_request_vars($filters, 'sess_lm');

	html_start_box(__('License Managers') . rtm_hover_help('license_managers_config_rtm.html', __('Learn More')), '100%', '', '3', 'center', 'lic_managers.php?action=edit&id=' . get_request_var('id'));
	html_end_box(true);

	$lic_managers = get_lic_manager_records();

	$display_text = array(
		'name'             => array('display' => __('Name'), 'sort' => 'ASC'),
		'id'               => array('display' => __('ID'), 'sort' => 'ASC'),
		'description'      => array('display' => __('Description'), 'sort' => 'ASC'),
		'collector_binary' => array('display' => __('RTM Binary'), 'sort' => 'ASC'),
		'lm_client'        => array('display' => __('Manager Binary'), 'sort' => 'ASC'),
		'totals'           => array('display' => __('Services Defined'), 'align' => 'right', 'sort' => 'ASC'),
		'total_up'         => array('display' => __('Services Up'), 'align' => 'right', 'sort' => 'ASC'),
		'total_down'       => array('display' => __('Services Down'), 'align' => 'right', 'sort' => 'ASC'),
		'total_disabled'   => array('display' => __('Services Disabled'), 'align' => 'right', 'sort' => 'ASC')
	);

	form_start('lic_managers.php', 'chk');

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($lic_managers)) {
		foreach ($lic_managers as $lic_manager) {
			form_alternate_row('line' . $lic_manager['id'], true);

			$url = htmlspecialchars($config['url_path'] . 'plugins/license/lic_managers.php?action=edit&id=' . $lic_manager['id']) ;
			form_selectable_cell(filter_value($lic_manager['name'], '', $url), $lic_manager['id']);

			form_selectable_cell($lic_manager['id'], $lic_manager['id']);
			form_selectable_cell($lic_manager['description'], $lic_manager['id']);
			form_selectable_cell($lic_manager['collector_binary'], $lic_manager['id']);
			form_selectable_cell(html_escape($lic_manager['lm_client']), $lic_manager['id']);
			form_selectable_cell($lic_manager['totals'], $lic_manager['id'], '', 'text-align:right;');
			form_selectable_cell($lic_manager['total_up'], $lic_manager['id'], '', 'text-align:right;');
			form_selectable_cell($lic_manager['total_down'], $lic_manager['id'], '', 'text-align:right;');
			form_selectable_cell($lic_manager['total_disabled'], $lic_manager['id'], '', 'text-align:right;');
			form_checkbox_cell($lic_manager['name'], $lic_manager['id']);
			form_end_row();
		}
	}else{
		print "<tr><td colspan='5'><em>No RTM License Managers Found</em></td></tr>";
	}

	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($lic_manager_actions);

	form_end();

	bottom_footer();
}
