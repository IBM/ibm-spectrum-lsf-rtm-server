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
include_once('./lib/api_device.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_data_source.php');
include_once('./plugins/license/include/lic_functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

$lic_server_actions = array(
	1 => 'Delete',
	2 => 'Disable',
	3 => 'Enable',
	4 => 'Clear Stats',
	5 => 'Update Options File'
);

/* set default action */
if (!isset_request_var('action')) { set_request_var('action',''); }

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'get_pollers':
		lic_get_pollers();

		break;
	case 'actions':
		form_actions();

		break;
	case 'edit':
		top_header();

		lic_server_edit();

		bottom_footer();
		break;
	default:
		top_header();

		lic_services();

		bottom_footer();
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */
function lic_get_pollers() {
	$output = '';

	if (!isempty_request_var('poller_type')) {
		input_validate_input_number(get_request_var('poller_type'));
		input_validate_input_number(get_request_var('lic_poller'));

		$pollers = db_fetch_assoc_prepared("SELECT *
			FROM lic_pollers
			WHERE poller_type = ?", array(get_request_var('poller_type')));

		if (cacti_sizeof($pollers)) {
			foreach($pollers as $p) {
				if ($p['id'] == get_request_var('lic_poller')) {
					$output .= '<option value="' . $p['id'] . '" selected>' . $p['poller_description'] . '</option>';
				} else {
					$output .= '<option value="' . $p['id'] . '">' . $p['poller_description'] . '</option>';
				}
			}
		}
	}

	print $output;
}

function form_save() {
	if (isset_request_var('save_component_license_server')) {
		validate_post_field(get_request_var('poller_type'), get_request_var('server_name'), get_request_var('poller_interval'),
			get_request_var('server_portatserver'), get_request_var('server_subisv'), get_request_var('server_timezone'),
			get_request_var('retries'), (isset_request_var('file_path') ? get_request_var('file_path'):''),
			(isset_request_var('prefix') ? get_request_var('prefix'):''));

		form_input_validate(get_request_var('server_vendor'), 'server_vendor', "^[A-Za-z0-9\._\\\@\ -]+$", true, 'field_input_save_1');
		form_input_validate(get_request_var('server_location'), 'server_location', "^[A-Za-z0-9\._\\\@\ -]+$", true,'field_input_save_1');
		form_input_validate(get_request_var('server_region'), 'server_region', "^[A-Za-z0-9\._\\\@\ -]+$", true, 'field_input_save_1');
		form_input_validate(get_request_var('server_department'), 'server_department', "^[A-Za-z0-9\._\\\@\ -]+$", true, 'field_input_save_1');
		form_input_validate(get_request_var('server_licensetype'), 'server_licensetype', "^[A-Za-z0-9\._\\\@\ -]+$", true, 'field_input_save_1');
		if(get_request_var('poller_interval') > get_request_var('timeout')){
		    form_input_validate(get_request_var('timeout'), 'timeout',"^[A]+$", true, 219);
		}

		input_validate_input_number(get_request_var('service_id'));
		input_validate_input_number(get_request_var('poller_id'));

		if ((is_error_message())) {
			header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));

			exit(0);
		}

		if (get_request_var('poller_id') <= 0) {
			header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));
			exit(0);
		}

		$server_portatserver 	= get_request_var('server_portatserver');
		$service_id          	= get_request_var('service_id');
		$server_subisv		 	= get_request_var('server_subisv');
		if (empty($server_subisv)) {
			$server_subisv_filter = " ";
		} else {
			$server_subisv_filter	= " AND (server_subisv='' OR server_subisv=?) ";
		}

		if (substr_count($server_portatserver, ':')) {
			$pass = explode(':', $server_portatserver);

			if (cacti_sizeof($pass)) {
				foreach($pass as $pas) {
					$portatsvc = trim($pas);
					if (empty($server_subisv)) {
						$pas_likes = array('%' . $portatsvc, '%' . $portatsvc . ';%', '%' . $portatsvc . ':%', '%' . $portatsvc . ',%', $service_id);
					} else {
						$pas_likes = array('%' . $portatsvc, '%' . $portatsvc . ';%', '%' . $portatsvc . ':%', '%' . $portatsvc . ',%', $server_subisv, $service_id);
					}
					$pas_man = db_fetch_cell_prepared("SELECT service_id
						FROM lic_services
						WHERE (server_portatserver LIKE ?
							OR server_portatserver LIKE ?
							OR server_portatserver LIKE ?
							OR server_portatserver LIKE ?)
							" . $server_subisv_filter . "
							AND service_id != ?", $pas_likes);

					if (!empty($pas_man) && $pas_man != $service_id) {
						raise_message(210);

						$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';

						header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));

						exit(0);
					}
				}
			}
		} else {
			$portatsvc = trim($server_portatserver);
			if (empty($server_subisv)) {
				$pas_likes = array('%' . $portatsvc, '%' . $portatsvc . ';%', '%' . $portatsvc . ':%', '%' . $portatsvc . ',%', $service_id);
			} else {
				$pas_likes = array('%' . $portatsvc, '%' . $portatsvc . ';%', '%' . $portatsvc . ':%', '%' . $portatsvc . ',%', $server_subisv, $service_id);
			}
			$pas_man = db_fetch_cell_prepared("SELECT service_id
				FROM lic_services
				WHERE (server_portatserver LIKE ?
					OR server_portatserver LIKE ?
					OR server_portatserver LIKE ?
					OR server_portatserver LIKE ?)
					" . $server_subisv_filter . "
					AND service_id != ?", $pas_likes);

			if (!empty($pas_man) && $pas_man != $service_id) {
				raise_message(208);

				$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';

				header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));

				exit(0);
			}
		}

		//validate options_path
		$options_files = explode(';', get_request_var('options_path'));
		if (cacti_sizeof($options_files)) {
			foreach($options_files as $options_file) {
				$options_file = trim ($options_file);

				if (empty($options_file)) continue;

				$if_invalid_options_file = 0;

				if (file_exists($options_file)) {  // for local options file
					if ( !is_readable($options_file) ) {
						$if_invalid_options_file = 1;
					}
				} else { // for remote optioins file via ssh
					if ( !cacti_sizeof(get_options_file_ssh($options_file, $server_portatserver)) ) {
						$if_invalid_options_file = 2;
					}
				}

				if ($if_invalid_options_file == 1) {
					raise_message(212);

					$_SESSION['sess_error_fields']['options_path'] = 'options_path';
					$_SESSION['sess_field_values']['options_path'] = get_request_var('options_path');

					header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));

					exit(0);
				}

				if ($if_invalid_options_file == 2) {
					raise_message(214);
				}
			}
		}

		$id = api_lic_server_save(
			get_request_var('service_id'), get_request_var('server_name'),
			get_request_var('poller_id'), '');

		if ((is_error_message()) || (get_request_var('service_id') != get_request_var('_service_id'))){
			header('Location: lic_servers.php?error=1&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));
		} else {
			$id = api_lic_service_save($id, get_request_var('poller_id'), get_request_var('server_name'),
				get_request_var('server_portatserver'), get_request_var('server_subisv'), get_request_var('server_timezone'),
				get_request_var('enable_checkouts'), get_request_var('timeout'), get_request_var('retries'),
				get_request_var('server_location'), get_request_var('server_support_info'), get_request_var('server_department'),
				get_request_var('server_licensetype'), get_request_var('poller_interval'), (isset_request_var('file_path') ? get_request_var('file_path'):''),
				get_request_var('options_path'), (isset_request_var('prefix') ? get_request_var('prefix'): ''), get_request_var('server_vendor'), get_request_var('server_region'));

			if (is_error_message()){
				header('Location: lic_servers.php?error='.get_request_var('poller_type'). '&action=edit&service_id=' . (empty($id) ? get_request_var('service_id') : $id));
			} else {
				if (isempty_request_var('options_path')) {
					api_lic_options_remove($id);
				}

				$poller_info = db_fetch_row_prepared("SELECT client_path,poller_hostname
					FROM lic_pollers
					INNER JOIN lic_services
					ON lic_services.poller_id=lic_pollers.id
					WHERE service_id=?", array($id));

				if (cacti_sizeof($poller_info)) {
					if ($poller_info['poller_hostname']=='local' && !file_exists($poller_info['client_path'])) {
						api_lic_server_disable($id);
						raise_message(215);
					}
				} else {
					api_lic_server_disable($id);
					raise_message(215);
				}

				//remove 'save sucessful' message if there is other save messages
				if ( !empty($_SESSION['sess_messages'][214]) || !empty($_SESSION['sess_messages'][215]) ) {
					unset($_SESSION['sess_messages'][1]);
				}

				header('Location: lic_servers.php');
			}
		}
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $lic_server_actions, $fields_lic_server_edit;

	/* ================= input validation ================= */
	get_filter_request_var('drp_action');
	/* ==================================================== */

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		if (get_request_var('drp_action') == '1') { /* delete */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_server_remove($selected_items[$i]);
			}
		}else if (get_request_var('drp_action') == '2') { /* disable */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_server_disable($selected_items[$i]);
			}
		}else if (get_request_var('drp_action') == '3') { /* enable */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_server_enable($selected_items[$i]);
			}
		}else if (get_request_var('drp_action') == '4') { /* clear stats */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_server_clear_stats($selected_items[$i]);
			}
		}else if (get_request_var('drp_action') == '5') { /* update options files now */
			for ($i=0; $i<count($selected_items); $i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				api_lic_server_update_options($selected_items[$i]);
			}
		}
		}

		header('Location: lic_servers.php');
		exit;
	}

	/* setup some variables */
	$license_server_list = ''; $license_server_array = array();

	/* loop through each of the license services selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$license_server_info = db_fetch_cell_prepared('SELECT server_name FROM lic_services WHERE service_id=?', array($matches[1]));
			$license_server_list .= '<li>' . htmlspecialchars($license_server_info) . '</li>';
			$license_server_array[] = $matches[1];
		}
	}

	top_header();

	form_start('lic_servers.php');

	html_start_box($lic_server_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (isset($license_server_array) && cacti_sizeof($license_server_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Are you sure you want to delete the following license services?') . "</p>
					<div class='itemlist'><ul>$license_server_list</ul></div>\n";

					form_radio_button('delete_type', '2', '1', 'Leave all Device(s), Graph(s) and Data Source(s).  Note: Devices will be disabled.', '1'); print '<br>';
					form_radio_button('delete_type', '2', '2', 'Delete all Device(s), Graph(s) and Data Source(s).', '1'); print '<br>';

			print "</td></tr>
				</td>
			</tr>\n";

			$title = 'Delete License Service(s)';
		}else if (get_request_var('drp_action') == '2') { /* disable */
			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to disable polling of the following license services?</p>
					<ul>$license_server_list</ul>
				</td>
			</tr>";

			$title = 'Disable License Service(s)';
		}else if (get_request_var('drp_action') == '3') { /* enable */
			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to enable the following license services?</p>
					<ul>$license_server_list</ul>
				</td>
			</tr>";

			$title = 'Enable License Service(s)';
		}else if (get_request_var('drp_action') == '4') { /* clear stats */
			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to clear the statistics for the following license services?</p>
					<ul>$license_server_list</ul>
				</td>
			</tr>";

			$title = 'Clear License Service Stats';
		}else if (get_request_var('drp_action') == '5') { /* update options file now */
			print "<tr>
				<td class='textArea'>
					<p>Are you sure you want to update the database from the options file for the following license services?</p>
					<ul>$license_server_list</ul>
				</td>
			</tr>";

			$title = 'Update License Service Options File Data';
		}

		$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='" . __esc($title) . "'>";
	} else {
		raise_message('lics40', __('You must select at least one License Service.'), MESSAGE_LEVEL_ERROR);
		header('Location: lic_servers.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($license_server_array) ? serialize($license_server_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function api_lic_server_clear_stats($service_id) {
	db_execute_prepared("UPDATE
		lic_services
		SET cur_time='0.0', min_time='999.99', max_time='0.00',
		avg_time='0.00', total_polls='0', failed_polls='0',
		status_fail_date='0000-00-00 00:00:00',
		status_rec_date='0000-00-00 00:00:00', availability='100.00'
		WHERE service_id=?", array($service_id));
}

function api_lic_server_update_options($lic_service) {
	global $config;

	$command  = $config['base_path'] . '/plugins/license/poller_options.php --gui --force --id=' . $lic_service;
	$php_path = read_config_option('path_php_binary');
	$return   = 0;

	$results = system($php_path . ' -q ' . $command, $return);

	raise_message(211);
}

/* ---------------------
    Edit Functions
   --------------------- */

function lic_server_edit() {
	global $fields_lic_server_edit, $lic_minor_refresh_interval, $lic_max_nonjob_runtimes;

	/* ================= input validation ================= */
	get_filter_request_var('service_id');
	/* ==================================================== */

	/* file: lic_servers.php, action: edit */
	$fields_lic_server_edit = array(
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Information'
			),
		'poller_type' => array(
			'friendly_name' => 'License Manager',
			'description' => 'The License Manager that this Poller utilizes.',
			'method' => 'drop_sql',
			'default' => '1',
			'value' => '|arg1:poller_type|',
			'sql' => 'SELECT DISTINCT lm.id, lm.name FROM lic_managers AS lm INNER JOIN lic_pollers AS lp ON lm.id=lp.poller_type ORDER BY name'
			),
		'server_name' => array(
			'method' => 'textbox',
			'friendly_name' => 'License Service Name',
			'description' => 'Enter a general name for this License Service.',
			'value' => '|arg1:server_name|',
			'max_length' => '250'
			),
		'poller_id' => array(
			'method' => 'drop_array',
			'friendly_name' => 'License Poller',
			'description' => 'The name of the poller that is associated with this license service.',
			'value' => '|arg1:poller_id|',
			'default' => '1',
			'array' => array()
			),
		'server_portatserver' => array(
			'method' => 'textarea',
			'friendly_name' => 'Service Port@Server Notation',
			'description' => 'Enter the port@server settings of the license service daemon. FLEXlm service daemon is <span class="codeph">lmgrd</span> or <span class="codeph">lmadmin</span>. RLM servie daemon is <span class="codeph">rlm</span>. If you are supporting a triad or failover server configuration, separate each port@server with a colon \':\' character. For RLM failover, the first one you enter port@server must be a master host and the second port@server is a failover host.',
			'textarea_rows' => '2',
			'textarea_cols' => '80',
			'value' => '|arg1:server_portatserver|',
			'max_length' => '255'
			),
		'server_subisv' => array(
			'method' => 'textbox',
			'friendly_name' => 'Optional ISV/Daemon',
			'description' => 'When running multiple ISV\'s for a single License Managers, if this Service requires naming that ISV/Daemon to poll, please enther that here.  Examples include the Reprise License Manager and the FLEXlm License Manager.',
			'size' => '20',
			'value' => '|arg1:server_subisv|',
			'max_length' => '40'
			),
		'server_timezone' => array(
			'method' => 'drop_sql',
			'sql' => 'SELECT Name AS name, Name AS id FROM `mysql`.`time_zone_name` ORDER BY name',
			'friendly_name' => 'License Service Timezone',
			'description' => 'The timezone of this monitored License Service.',
			'none_value' => 'Default',
			'value' => '|arg1:server_timezone|',
			'default' => '0'
		),
		'spacer2' => array(
			'method' => 'spacer',
			'friendly_name' => 'Option and Debug Information'
		),
		'options_path' => array(
			'method' => 'textarea',
			'friendly_name' => 'Options File Paths',
			'description' => 'If you have defined Options File for your FLEXlm server, enter its full path. Separate file paths by a semi colon \';\' and make sure FLEXlm service host is accessible from the RTM server or over SSH. If you are not using NFS server, then make sure that the web server user has the authorized_keys file to access the remote file.',
			'value' => '|arg1:options_path|',
			'textarea_rows' => '2',
			'textarea_cols' => '80',
			'class' => 'textAreaNotes',
			'max_length' => '2048',
			),
		'file_path' => array(
			'method' => 'textbox',
			'friendly_name' => 'Debug Log Directory',
			'description' => 'Enter the Directory that contains the License Service Debug log files.  When using Quorum servers, this path either be identical on each license server, or you have to enter a separate path for each server separated by a colon \':\' character.  When using the multiple column syntax, you must have the same number of port@server as you have Debug Log Directories.</font>',
			'value' => '|arg1:file_path|',
			'max_length' => '255',
			'size' => '80'
			),
		'prefix' => array(
			'method' => 'textbox',
			'friendly_name' => 'Debug Log Filename',
			'description' => 'Enter the Debug Log Filename.  If you are rotating the Debug Logs, you can force a reload of these archive Debug Log files from the command line from the License Server in the case of a connectivity problem.  Examples would be if the Log Filename were lmgrd.log, and you rotated to lmgrd.log.1, lmgrd.log.2, lmgrd.log.3, etc.  If all the Log log files are in the same location but have different name, for example, if they are on NFS, you may enter a colon \':\' delmited series of Debug Log Filenames.  When using the multiple Debug Log Filename syntax, you must have the same number of port@server as you have Debug Log Filenames.',
			'value' => '|arg1:prefix|',
			'max_length' => '40',
			'size' => '40'
			),
		'spacer3' => array(
			'method' => 'spacer',
			'friendly_name' => 'Connection Settings'
			),
		'enable_checkouts' => array(
			'friendly_name' => 'Monitor License Checkouts',
			'description' => 'Monitor License Checkouts for this License Service?',
			'default' => 'on',
			'method' => 'checkbox',
			'value' => '|arg1:enable_checkouts|'
			),
		'poller_interval' => array(
			'friendly_name' => 'Poller Interval',
			'description' => 'The License Poller interval.',
			'method' => 'drop_array',
			'default' => '120',
			'value' => '|arg1:poller_interval|',
			'array' => $lic_minor_refresh_interval
			),
		'timeout' => array(
			'method' => 'drop_array',
			'friendly_name' => 'Poller Timeout',
			'description' => 'Enter time in seconds for the License Service data collection timeout.',
			'value' => '|arg1:timeout|',
			'default' => '180',
			'array' => $lic_max_nonjob_runtimes
			),
		'retries' => array(
			'method' => 'textbox',
			'friendly_name' => 'Connection Retries',
			'description' => 'Enter the connection retry count for this License Service.',
			'value' => '|arg1:retries|',
			'default' => '2',
			'max_length' => '10'
			),
		'spacer4' => array(
			'method' => 'spacer',
			'friendly_name' => 'Support Information'
			),
		'server_vendor' => array(
			'method' => 'textbox',
			'friendly_name' => 'Service Vendor',
			'description' => 'Define a vendor for this License Service.',
			'value' => '|arg1:server_vendor|',
			'max_length' => '255'
			),
		'server_location' => array(
			'method' => 'textbox',
			'friendly_name' => 'Location',
			'description' => 'Enter the physical location for this Licenses Service.',
			'value' => '|arg1:server_location|',
			'max_length' => '255'
			),
		'server_region' => array(
			'method' => 'textbox',
			'friendly_name' => 'Region',
			'description' => 'Enter the region for this Licenses Service.',
			'value' => '|arg1:server_region|',
			'max_length' => '255'
			),
		'server_department' => array(
			'method' => 'textbox',
			'friendly_name' => 'Department',
			'description' => 'Department responsible for this License Service.',
			'value' => '|arg1:server_department|',
			'max_length' => '255'
			),
		'server_licensetype' => array(
			'method' => 'textbox',
			'friendly_name' => 'License Type',
			'description' => 'License Service purpose.',
			'value' => '|arg1:server_licensetype|',
			'max_length' => '255'
			),
		'server_support_info' => array(
			'method' => 'textbox',
			'friendly_name' => 'Support Contacts',
			'description' => 'Enter relevant contact information for the License Service.',
			'value' => '|arg1:server_support_info|',
			'max_length' => '255'
			),
		'service_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:service_id|'
			),
		'_service_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:service_id|'
			),
		'_poller_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:poller_id|'
			),
		'save_component_license_server' => array(
			'method' => 'hidden',
			'value' => '1'
			)
	);

	$fields_lic_server_edit2=$fields_lic_server_edit;
	if (!isempty_request_var('service_id')) {
		$license_server = db_fetch_row_prepared('SELECT ls.*, lp.poller_type poller_type
			FROM lic_services ls JOIN lic_pollers lp ON ls.poller_id=lp.id
			WHERE service_id=?', array(get_request_var('service_id')));

		if ($license_server){
			$header_label = 'License Service [edit: ' . htmlspecialchars($license_server['server_name']) . ']';
			$fields_lic_server_edit2['poller_id']['default']=$license_server['poller_id'];
		}
	} else {
		$header_label = 'License Service [new]';
		if (isset_request_var('error')){
			$fields_lic_server_edit2['poller_type']['default'] = get_request_var('error');
		}
	}

	form_start('lic_servers.php', 'lic_svc_form');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_lic_server_edit2, (isset($license_server) ? $license_server : array()))
		)
	);

	html_end_box();

	form_save_button('lic_servers.php', '', 'service_id');

	?>
	<script type='text/javascript'>

	function set_visibility(){
		if ($('#poller_type').val() != 1) {
			$('#row_options_path').hide();
			//$("#row_file_path").attr("class", "even");
			//$("#row_file_path").attr("bgcolor", null);
			//$("#row_prefix").attr("class", null);
			//$("#row_prefix").attr("bgcolor", "#E5E5E5");
		} else {
			$('#row_options_path').show();
			//$("#row_file_path").attr("class", null);
			//$("#row_file_path").attr("bgcolor", "#E5E5E5");
			//$("#row_prefix").attr("class", "even");
			//$("#row_prefix").attr("bgcolor", null);
		}

		$.get('lic_servers.php?header=false&action=get_pollers' +
			'&poller_type=' + $('#poller_type').val() +
			'&lic_poller=' + $('#_poller_id').val(), function(data) {
			$('#poller_id').html(data);
			$( "#poller_id" ).selectmenu( "refresh" );
		});
	}

	$(function() {
		$('#poller_type').change(function() {
			set_visibility();
		}).trigger('change');
	});

	</script>
	<?php
}

function lic_filter() {
	global $lic_search_types, $lic_rows_selector, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_config' action='lic_servers.php'>
			<table cellpadding='1' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Manager', 'license');?>
					</td>
					<td width='1'>
						<select id='poller_type' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('poller_type') == '0') {?> selected<?php }?>>All</option>
							<?php
							$managers = get_lic_managers();
							if (cacti_sizeof($managers)) {
								foreach ($managers as $manager) {
									print '<option value="' . $manager['id'] .'"'; if (get_request_var('poller_type') == $manager['id']) { print ' selected'; } print '>' . html_escape($manager['name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'license');?>
					</td>
					<td width='1'>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>Any</option>
							<option value='-3'<?php if (get_request_var('status') == '-3') {?> selected<?php }?>>Enabled</option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>>Disabled</option>
							<option value='-4'<?php if (get_request_var('status') == '-4') {?> selected<?php }?>>Not Up</option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>>Up</option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>>Down</option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>>Recovering</option>
							<option value='0'<?php if (get_request_var('status') == '0') {?> selected<?php }?>>Unknown</option>
						</select>
					</td>
					<td>
						<?php print __('Location', 'license');?>
					</td>
					<td>
						<select id='location' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('location') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('location') == '-2') {?> selected<?php }?>>None</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$locations = db_fetch_assoc("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									ORDER BY server_location");
							} else {
								$locations = db_fetch_assoc_prepared("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									AND lp.poller_type= ?
									ORDER BY server_location", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($locations) > 0) {
								foreach ($locations as $location) {
									print '<option value="' . $location['server_location'] .'"'; if (get_request_var('location') == $location['server_location']) { print ' selected'; } print '>' . htmlspecialchars($location['server_location']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Region', 'license');?>
					</td>
					<td width='1'>
						<select id='region' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('region') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('region') == '-2') {?> selected<?php }?>>None</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$regions = db_fetch_assoc("SELECT DISTINCT server_region
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_region != ''
									AND server_region IS NOT NULL
									ORDER BY server_region");
							} else {
								$regions = db_fetch_assoc_prepared("SELECT DISTINCT server_region
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_region != ''
									AND server_region IS NOT NULL
									AND lp.poller_type= ?
									ORDER BY server_region", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($regions)) {
								foreach ($regions as $region) {
									print '<option value="' . $region['server_region'] .'"'; if (get_request_var('region') == $region['server_region']) { print ' selected'; } print '>' . htmlspecialchars($region['server_region']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<table cellpadding='1' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print htmlspecialchars(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Vendor', 'license');?>
					</td>
					<td>
						<select id='vendor' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('vendor') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$vendors = db_fetch_assoc("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_vendor != ''
									AND disabled = ''
									AND server_vendor IS NOT NULL
									ORDER BY server_vendor");
							} else {
								$vendors = db_fetch_assoc_prepared("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_vendor != ''
									AND disabled = ''
									AND server_vendor IS NOT NULL
									AND lp.poller_type= ?
									ORDER BY server_vendor", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($vendors)) {
								foreach ($vendors as $vendor) {
									print '<option value="' . $vendor['server_vendor'] .'"'; if (get_request_var('vendor') == $vendor['server_vendor']) { print ' selected'; } print '>' . htmlspecialchars($vendor['server_vendor']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Multi-server', 'license');?>
					</td>
					<td>
						<select id='quorum' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('quorum') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('quorum') == '-2') {?> selected<?php }?>>Multi-server</option>
							<option value='-3'<?php if (get_request_var('quorum') == '-3') {?> selected<?php }?>>Standalone</option>
						</select>
					</td>
					<td>
						<?php print __('Records', 'license');?>
					</td>
					<td>
						<select id='rows_selector' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector)) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows_selector') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function get_txt_service_level($errorno){
	$error_text = db_fetch_cell_prepared("SELECT error_text FROM lic_errorcode_maps WHERE (type=9 OR type=0 AND errorno < 0) AND errorno=?", array($errorno));
	return $error_text;
}

function lic_services() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $config;
	global $lic_server_actions, $config;
	$sql_params = array();

	$filters = array(
		'rows_selector' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
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
		'quorum' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0',
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-3',
			),
		'location' => array(
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
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'vendor' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	if (check_changed('poller_type', 'sess_ls_poller_type') && get_request_var('poller_type')!='0') {
		kill_session_var('sess_ls_location');
		kill_session_var('sess_ls_region');
		kill_session_var('sess_ls_vendor');

		unset_request_var('location');
		unset_request_var('region');
		unset_request_var('vendor');
	}

	validate_store_request_vars($filters, 'sess_ls');

	html_start_box(__('License Service') . rtm_hover_help('license_server_config_rtm.html', __('Learn More')), '100%', '', '3', 'center', 'lic_servers.php?action=edit');
	lic_filter();
	html_end_box(true);

	$sql_where  = '';

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$license_services = lic_get_license_service_records($sql_where, $apply_limits = TRUE, $row_limit, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = '?header=false&filter=' + $('#filter').val();
		strURL += '&poller_type=' + $('#poller_type').val();
		strURL += '&location=' + $('#location').val();
		strURL += '&region=' + $('#region').val();
		strURL += '&quorum=' + $('#quorum').val();
		strURL += '&vendor=' + $('#vendor').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter(){
		strURL  = '?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_lic_config').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
		$('.cactiTooltipHint').tooltip(); //related with cacti issue#3149 fixed. If it be fixed, this line can be removed.
	});

	</script>
	<?php

	$rows_query_string = "SELECT COUNT(*) FROM lic_services ls INNER JOIN lic_pollers lp ON lp.id=ls.poller_id $sql_where";
	//echo $rows_query_string;
	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'name'               => array('display' => __('Service Name', 'license'), 'sort' => 'ASC'),
		'id'                 => array('display' => __('ID', 'license'), 'sort' => 'ASC'),
		'poller_description' => array('display' => __('Poller', 'license'), 'sort' => 'ASC'),
		'poller_interval'    => array('display' => __('Frequency', 'license'), 'sort' => 'DESC'),
		'server_vendor'      => array('display' => __('Vendor', 'license'), 'align' => 'left', 'sort' => 'ASC'),
		'server_department'  => array('display' => __('Department', 'license'), 'align' => 'left', 'sort' => 'ASC'),
		'server_licensetype' => array('display' => __('Type', 'license'), 'align' => 'left', 'sort' => 'ASC'),
		'status'             => array('display' => __('Status', 'license'), 'sort' => 'ASC'),
		'cur_time'           => array('display' => __('Current', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'avg_time'           => array('display' => __('Average', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'max_time'           => array('display' => __('Max', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'availability'       => array('display' => __('Availability', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'status_fail_date'   => array('display' => __('Last Failed', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'poller_date'        => array('display' => __('Last Updated', 'license'), 'align' => 'right', 'sort' => 'DESC')
	);

	$nav = html_nav_bar('lic_servers.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Services'), 'page', 'main');

	form_start('lic_servers.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($license_services)) {
		foreach ($license_services as $server) {
			form_alternate_row('line' . $server['id'], true);

			$url = htmlspecialchars($config['url_path'] . 'plugins/license/lic_servers.php?action=edit&service_id=' . $server['id']) ;
			form_selectable_cell(filter_value($server['name'], get_request_var('filter'), $url), $server['id']);

			form_selectable_cell($server['id'], $server['id']);
			form_selectable_cell(htmlspecialchars($server['poller_description']), $server['id']);
			form_selectable_cell(lic_format_seconds($server['poller_interval']), $server['id']);
			form_selectable_cell(htmlspecialchars($server['server_vendor']), $server['id']);
			form_selectable_cell(filter_value($server['server_department'], get_request_var('filter')), $server['id']);
			form_selectable_cell(filter_value($server['server_licensetype'], get_request_var('filter')), $server['id']);

			$server_title = '';
			$server_status = lic_get_quorum_status($server['id'], true, $server_title);
			if (strstr($server_status,'Up')){
				$server_status = 3;
			}else if (strstr($server_status,'Down')){
				$server_status = 1;
			}

			if ($server_status == 'N/A'){
				if(empty($server_title) && $server['status']!=HOST_UP && $server['status']!=HOST_RECOVERING && $server['disabled']!='on'){//server is UP, but service is not UP and RECOVERING.
					$server_title = "No errors of server found, it could be the license poller stopped";
				}
				if($server['errorno'] != 0){
					$server_title = get_txt_service_level($server['errorno']);
				}
				form_selectable_cell(get_colored_device_status(($server['disabled'] == 'on' ? true : false), $server['status']), $server['id'], '', '', $server_title);
			} else {
				if($server['errorno'] != 0){
					$server_title = get_txt_service_level($server['errorno']);
				}
				form_selectable_cell(get_colored_device_status(($server['disabled'] == 'on' ? true : false), $server_status), $server['id'], '', '', $server_title);
			}

			form_selectable_cell(round($server['cur_time'],1), $server['id'], '', 'text-align:right;');
			form_selectable_cell(round($server['avg_time'],1), $server['id'], '', 'text-align:right;');
			form_selectable_cell(round($server['max_time'],1), $server['id'], '', 'text-align:right;');
			form_selectable_cell(round($server['availability'],1) . '%', $server['id'], '', 'text-align:right;');
			form_selectable_cell((substr_count($server['status_fail_date'], '0000') ? 'N/A' : $server['status_fail_date']) , $server['id'], '', 'text-align:right;');
			form_selectable_cell((substr_count($server['poller_date'], '0000') ? 'N/A' : $server['poller_date']) , $server['id'], '', 'text-align:right;');
			form_checkbox_cell($server['poller_description'], $server['id']);
			form_end_row();
		}
		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No License Services Found</em></td></tr>";
		html_end_box(false);
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($lic_server_actions);

	form_end();

	bottom_footer();
}
