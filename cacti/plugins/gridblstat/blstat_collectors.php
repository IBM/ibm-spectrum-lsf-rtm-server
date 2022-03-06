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

chdir('../..');
include('./include/auth.php');
include($config['base_path'] . '/plugins/gridblstat/lib/functions.php');
include_once($config['library_path'] . '/rtm_functions.php');

$actions = array(
	1 => __('Delete', 'gridblstat'),
	2 => __('Enable', 'gridblstat'),
	3 => __('Disable', 'gridblstat')
);

validate_request_vars();
set_default_action();

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		include_once($config['base_path'] . '/lib/api_data_source.php');
		include_once($config['base_path'] . '/lib/api_graph.php');
		include_once($config['base_path'] . '/lib/api_device.php');
		form_actions();

		break;
	case 'edit':
		top_header();
		edit();
		bottom_footer();
		break;
	default:
		top_header();
		collectors();
		bottom_footer();
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	global $config;
	$lsid = 0;

	if (isset_request_var('save_component')) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var('lsid'));
		//input_validate_input_number(get_request_var('clusterid'));
		input_validate_input_number(get_request_var('ls_port'));
		input_validate_input_number(get_request_var('ls_version'));
		input_validate_input_number(get_request_var('poller_freq'));
		input_validate_input_number(get_request_var('graph_freq'));
		input_validate_input_regex_xss_attack(get_request_var('lsf_envdir'), 'lsf_envdir');
		/* ==================================================== */

		if (!isempty_request_var('lsid')) {
			$save['lsid'] = get_request_var('lsid');
		} else {
			$save['lsid'] = 0;
		}
		if (isset_request_var('advance_mode')) {
			$save['advanced_enabled'] = 'on';
			$save['ls_hosts']    = '';
			$save['ls_port']     = '9581'; //for default value show only
			$save['ls_admin']    = '';
			$save['lsf_strict_checking']    = '';
			$lsf_envpath = get_request_var('lsf_envdir');
			if (!is_dir($lsf_envpath)) {
				raise_message(140);	// not a valid directory
				$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
				$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
			} else {
				$dirs = scandir($lsf_envpath);
				$found = false;
				$found2 = false;
				foreach ($dirs as $dir) {
					if ($dir == 'lsf.conf') {
						$found = true;
					}
					if ($dir == 'lsf.licensescheduler') {
						$found2 = true;
					}
					if($found && $found2) {
						break;
					}
				}
				if ($found && $found2) {
					$save['lsf_envdir']		= form_input_validate($lsf_envpath, 'lsf_envdir', '', false, 3);
				} else {
					if(!$found) {
						raise_message(142);
					}
					if(!$found2) {
						raise_message('ls_conf_not_found');
					}
					$_SESSION['sess_error_fields']['lsf_envdir'] = 'lsf_envdir';
					$_SESSION['sess_field_values']['lsf_envdir'] = 'lsf_envdir';
				}
			}
		} else {
			$save['advanced_enabled'] = '';
			$save['ls_hosts']    = form_input_validate(get_request_var('ls_hosts'), 'ls_hosts', '^[A-Za-z0-9\:\._\\\@\ -]+$', false, 3);
			$save['ls_port']     = form_input_validate(get_request_var('ls_port'), 'ls_port', '^[0-9]{1,5}',  false,  3);
			$save['ls_admin']    = 'lsfadmin';
			$save['lsf_strict_checking']    = form_input_validate(get_request_var('lsf_strict_checking'), 'lsf_strict_checking', '', true, 3);
		}
		$save['name']        = form_input_validate(get_request_var('name'), 'name', '', false, 3);
		$save['region']      = form_input_validate(get_request_var('region'), 'region', '', true, 3);
		$save['ls_version']  = form_input_validate(get_request_var('ls_version'),'ls_version', '', false,3);
		$save['disabled']    = (isset_request_var('disabled') ? '':'on');
		$save['poller_freq'] = form_input_validate(get_request_var('poller_freq'), 'poller_freq', '', false, 3);
		$save['graph_freq']  = form_input_validate(get_request_var('graph_freq'), 'graph_freq', '', false, 3);

		if (strpos($save['ls_hosts'], ' ') !== false) {
			$hosts = explode(' ', $save['ls_hosts']);
		} else {
			$hosts = array($save['ls_hosts']);
		}

		$fetch_params = array('', $save['ls_port']);
		if ($save['lsid'] != 0) {
			$lsid_save = $save['lsid'];
			$where = ' AND lsid <> ?';
			$fetch_params[] = $lsid_save;
		} else {
			$where = '';
		}

		foreach ($hosts as $host) {
			$fetch_params[0] = $host;
			$ls=db_fetch_cell_prepared("SELECT COUNT(*) FROM grid_blstat_collectors
					WHERE FIND_IN_SET(?,replace(ls_hosts, ' ', ','))
					AND ls_port = ? $where", $fetch_params);
			if($ls > 0) {
				raise_message('duplicated_ls');
			}
		}

		if (!is_error_message()) {
			$lsid = sql_save($save, 'grid_blstat_collectors', 'lsid');

			if ($lsid) {
				if (empty($save['advanced_enabled'])) {
					$ls_envdir_realpath  = realpath($config['base_path'] . '/../rtm/etc/ls' . $lsid);
					if($ls_envdir_realpath == false) {
						if (!mkdir($config['base_path'] . '/../rtm/etc/ls' . $lsid, 0775)) {
							cacti_log("ERROR: Unable to create directory '" . $config['base_path'] . '/../rtm/etc/ls' . $lsid . "'", FALSE);
						} else {
							$ls_envdir_realpath  = realpath($config['base_path'] . '/../rtm/etc/ls' . $lsid);
						}
					}

					$contents  = "Begin Parameters\n";
					$contents .= "PORT = " . $save["ls_port"] . "\n";
					$contents .= "HOSTS = " . $save["ls_hosts"] . "\n";
					$contents .= "ADMIN = " . $save["ls_admin"] . "\n";
					$contents .= "End Parameters\n";
					if(!file_put_contents("$ls_envdir_realpath/lsf.licensescheduler", $contents)) {
						raise_message(2,  __("Error when writing to $ls_envdir_realpath/lsf.licensescheduler", "gridblstat"), MESSAGE_LEVEL_ERROR);
					} else {
						$fp = fopen("$ls_envdir_realpath/lsf.conf", "w" );

						if($fp == FALSE) {
							raise_message(2,  __("Cannot open file $ls_envdir_realpath/lsf.conf", "gridblstat"), MESSAGE_LEVEL_ERROR);
						} else {
							if ($save['lsf_strict_checking'] == 'Y' || $save['lsf_strict_checking'] == 'ENHANCED') {
								if(fwrite($fp,"LSF_STRICT_CHECKING=".$save['lsf_strict_checking']." \n") == FALSE) {
									raise_message(2,  __("You don't have WRITE permission to $ls_envdir_realpath/lsf.conf", "gridblstat"), MESSAGE_LEVEL_ERROR);
								}
							}

							fclose($fp);
							raise_message(1);
						}

						if (!file_exists("$ls_envdir_realpath/ego.conf")) {
							$fp = fopen("$ls_envdir_realpath/ego.conf", "w" );
							fclose($fp);
						}

						db_execute_prepared("UPDATE grid_blstat_collectors
							SET lsf_envdir=?
							WHERE lsid=?", array($ls_envdir_realpath, $lsid));
					}
				} else {
					raise_message(1);
				}
			} else {
				raise_message(2);
			}
			header('Location: blstat_collectors.php');
		} else {
			header('Location: blstat_collectors.php?action=edit&lsid=' . (empty($lsid) ? get_request_var('lsid') : $lsid));
		}
	}
	header('Location: blstat_collectors.php');
}

function form_actions() {
	global $actions, $assoc_actions;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		if (isset_request_var('save_list')) {
			$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

			if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				for ($i=0;($i<count($selected_items));$i++) {
					if (!isset_request_var('delete_type')) {
						set_request_var('delete_type',2);
					}

					$cacti_host = db_fetch_cell_prepared('SELECT cacti_host FROM grid_blstat_collectors WHERE lsid=?', array($selected_items[$i]));

					if (!empty($cacti_host)) {
						$data_sources_to_act_on = array();
						$graphs_to_act_on       = array();
						$devices_to_act_on      = array();

						$data_sources = db_fetch_assoc_prepared("SELECT
							data_local.id AS local_data_id
							FROM data_local
							WHERE data_local.host_id=?", array($cacti_host));

						if (cacti_sizeof($data_sources) > 0) {
							foreach ($data_sources as $data_source) {
								$data_sources_to_act_on[] = $data_source['local_data_id'];
							}
						}

						$graphs = db_fetch_assoc_prepared("SELECT
							graph_local.id AS local_graph_id
							FROM graph_local
							WHERE graph_local.host_id=?", array($cacti_host));

						if (cacti_sizeof($graphs) > 0) {
							foreach ($graphs as $graph) {
								$graphs_to_act_on[] = $graph['local_graph_id'];
							}
						}

						$devices_to_act_on = array_rekey(db_fetch_assoc_prepared("SELECT id FROM host WHERE id=?", array($cacti_host)), "id", "id");

						if (get_request_var('delete_type') == 2) {
							if (cacti_sizeof($data_sources_to_act_on)) {
								api_data_source_remove_multi($data_sources_to_act_on);
							}

							if (cacti_sizeof($graphs_to_act_on)) {
								api_graph_remove_multi($graphs_to_act_on);
							}

							if (cacti_sizeof($devices_to_act_on)) {
								api_device_remove_multi($devices_to_act_on);
							}
						}

						/* disable all devices */
						if (get_request_var('delete_type') == 1) {
							db_execute("UPDATE host SET disabled='on' WHERE id IN (" . implode(",", $devices_to_act_on) . ")");

						}
					}

					db_execute_prepared("DELETE FROM grid_blstat_collectors WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_cluster_use WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_clusters WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_distribution WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_feature_map WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_projects WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_service_domains WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_tasks WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_users WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat WHERE lsid=?", array($selected_items[$i]));
					db_execute_prepared("DELETE FROM grid_blstat_collector_clusters WHERE lsid=?", array($selected_items[$i]));
				}
			} elseif (get_request_var('drp_action') == '2') { /* enable */
				for ($i=0;($i<count($selected_items));$i++) {
					db_execute_prepared("UPDATE grid_blstat_collectors SET disabled='' WHERE lsid=?", array($selected_items[$i]));
				}
			} elseif (get_request_var('drp_action') == '3') { /* enable */
				for ($i=0;($i<count($selected_items));$i++) {
					db_execute_prepared("UPDATE grid_blstat_collectors SET disabled='on' WHERE lsid=?", array($selected_items[$i]));
				}
			}
			}

			header('Location: blstat_collectors.php');
			exit;
		}
	}

	/* setup some variables */
	$list = ''; $array = array(); $list_name = '';

	if (!isempty_request_var('lsid')) {
		$list_name = db_fetch_cell_prepared("SELECT name
			FROM grid_blstat_collectors
			WHERE lsid=?", array(get_request_var('lsid')));
	}

	if (isset_request_var('save_list')) {
		foreach ($_POST as $var => $val) {
			if (preg_match("/^chk_([A-Za-z0-9_\-| ]+)$/", $var, $matches)) {
				$list .= '<li><b>' . html_escape(db_fetch_cell_prepared("SELECT name
					FROM grid_blstat_collectors
					WHERE lsid=?", array($matches[1]))) . '</b></li>';

				$array[] = $matches[1];
			}
		}

		top_header();

		form_start('blstat_collectors.php');

		html_start_box($actions[get_request_var('drp_action')] . ' ' . html_escape($list_name), '60%', '', '3', 'center', '');

		if (cacti_sizeof($array)) {
			if (get_request_var('drp_action') == '1') { /* delete */
				print "<tr>
					<td class='textArea'>
						<p>". __('When you click \'Continue\', the following License Scheduler Collectors will be Deleted.', 'gridblstat'). "</p>
						<ul class='itemlist'>$list</ul>\n";

				form_radio_button('delete_type', '2', '1', __('Leave all Device(s), Graph(s) and Data Source(s) in place.  Devices will be disabled.', 'gridblstat'), '1');
				print '<br>';

				form_radio_button('delete_type', '2', '2', __('Delete all Device(s), Graph(s) and Data Source(s)', 'gridblstat'), '1');
				print '<br>';

				print "</td>
				</tr>\n";

				$save_html = "<input type='button' value='Cancel' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='Continue' title='Delete License Scheduler Data Collector(s)'>";
			} elseif (get_request_var('drp_action') == '2') { /* Enable */
				print "<tr>
					<td class='textArea'>
						<p>". __('When you click \'Continue\', the following License Scheduler Collectors will be Enabled.', 'gridblstat'). "</p>
						<ul class='itemlist'>$list</ul>
					</td>
				</tr>";

				$save_html = "<input type='button' value='Cancel' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='Continue' title='Enable License Scheduler Data Collector(s)'>";
			} elseif (get_request_var('drp_action') == '3') { /* Disable */
				print "<tr>
					<td class='textArea'>
						<p>". __('When you click \'Continue\', the following License Scheduler Collectors will be Disabled.', 'gridblstat'). "</p>
						<ul class='itemlist'>$list</ul>
					</td>
				</tr>";

				$save_html = "<input type='button' value='Cancel' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='Continue' title='Disable License Scheduler Data Collector(s)'>";
			}
		} else {
			raise_message(40);
			header('Location: blstat_collectors.php?header=false');
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

function get_header_label($collector) {
	if (!isempty_request_var('lsid')) {
		$header_label = __esc('License Scheduler Collector [edit: %s]', $collector['name'], 'gridblstat');
	} else {
		$header_label = __('License Scheduler Collector [new]', 'gridblstat');
	}

	return $header_label;
}

function edit() {
	global $config;

	if (!isempty_request_var('lsid')) {
		$collector = db_fetch_row_prepared("SELECT *
			FROM grid_blstat_collectors
			WHERE lsid=?", array(get_request_var('lsid')));
	} else {
		$collector = array();
	}

	$header_label = get_header_label($collector);

	form_start('blstat_collectors.php');

	html_start_box($header_label, '100%', '', '3', 'center', '');

	$disabled = '';
	if (!cacti_sizeof($collector) || $collector['disabled'] == '') {
		$disabled = 'on';
	}

	if (isset_request_var('lsid') && 0 < get_request_var('lsid')) {
		$clusterInfo = db_fetch_row_prepared("SELECT lsf_envdir, advanced_enabled
			FROM grid_blstat_collectors
			WHERE lsid=?", array(get_request_var('lsid')));

		if ($clusterInfo['lsf_envdir']) {
			$LSF_ENV_PATH = $clusterInfo['lsf_envdir'];
		} else {
			$LSF_ENV_PATH = '';
		}

		if ($clusterInfo['advanced_enabled'] == 'on') {
			$advanced_enabled = 'checked';
		} else {
			$advanced_enabled = '';
		}
	} else {
		$LSF_ENV_PATH = '';
		$advanced_enabled = '';
	}

	if (isset($_SESSION['sess_error_fields']['lsf_envdir'])) {
		$advanced = true;
		$class_value='txtErrorTextBox';
	} else {
		$advanced = false;
		$class_value = '';
	}

	unset($_SESSION['sess_error_fields']['lsf_envdir']);

	$custom_advance_mode = "<input id='advance_mode' name='advance_mode' type='checkbox' $advanced_enabled>
		<input type='text' class='".$class_value."' size='40' id='lsf_envdir' value='$LSF_ENV_PATH' name='lsf_envdir' disabled='true'>";

	$fields = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('General Information', 'gridblstat'),
		),
		'name' => array(
			'method' => 'textbox',
			'friendly_name' => __('License Scheduler Data Collector Name', 'gridblstat'),
			'value' => '|arg1:name|',
			'max_length' => '255'
		),
		'region' => array(
			'method' => 'textbox',
			'friendly_name' => __('Region Name', 'gridblstat'),
			'value' => '|arg1:region|',
			'max_length' => '255'
		),
		'disabled_content' => array(
			'method' => 'checkbox_group',
			'friendly_name' => __('Enable the License Scheduler Data Collector', 'gridblstat'),
			'items' => array(
				'disabled' => array('friendly_name' => '', 'value' => $disabled)
			)
		),
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => __('License Scheduler Batch Daemon (bld) Service Information', 'gridblstat'),
		),
		'ls_version' => array(
			'method' => 'drop_array',
			'friendly_name' => __('License Scheduler Version', 'gridblstat'),
			'value' => '|arg1:ls_version|',
			'array' => array(
				'91'  => __('Version 9.x', 'gridblstat'),
				'101' => __('Version 10.x', 'gridblstat'),
			),
			'default' => '91'
		),
		'ls_hosts' => array(
			'method' => 'textbox',
			'friendly_name' => __('License Scheduler Master Host(s)', 'gridblstat'),
			'description' => __('To enable failover, enter the list of LS master hostnames separated by a space.', 'gridblstat'),
			'value' => '|arg1:ls_hosts|',
			'max_length' => '255',
			'size' => '80'
		),
		'ls_port' => array(
			'method' => 'textbox',
			'friendly_name' => __('License Scheduler TCP Port for Communications', 'gridblstat'),
			'value' => '|arg1:ls_port|',
			'max_length' => '40',
			'default' => '9581',
			'size' => '15'
		),
		'lsf_strict_checking' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Strict Checking', 'gridblstat'),
			'description' => __('Please specify whether strict checking of communications between LSF daemons is enabled or not.', 'gridblstat'),
			'value' => '|arg1:lsf_strict_checking|',
			'array' => array(
				'ENHANCED' => __('Enhanced', 'gridblstat'),
				'Y' => __('Yes', 'gridblstat'),
				'N' => __('No', 'gridblstat')
			),
			'default' => 'N'
		),
		'spacer2' => array(
			'method' => 'spacer',
			'friendly_name' => __('Polling/Automation Settings', 'gridblstat'),
		),
		'poller_freq' => array(
			'friendly_name' => __('Frequency to Collect License Data', 'gridblstat'),
			'method' => 'drop_array',
			'value' => '|arg1:poller_freq|',
			'array' => array(
				'15'  => __('%d Seconds', 15,  'gridblstat'),
				'20'  => __('%d Seconds', 20,  'gridblstat'),
				'30'  => __('%d Seconds', 30,  'gridblstat'),
				'60'  => __('%d Minute',  1,   'gridblstat'),
				'150' => __('%0.1f Minutes', 2.5, 'gridblstat'),
				'300' => __('%d Minutes', 5,   'gridblstat')
			),
			'default' => '30'
		),
		'graph_freq' => array(
			'friendly_name' => __('Frequency to Create New License Scheduler Graphs', 'gridblstat'),
			'method' => 'drop_array',
			'value' => '|arg1:graph_freq|',
			'array' => array(
				'0'     => __('Disabled', 'gridblstat'),
				'14400' => __('%d Hours', 4, 'gridblstat'),
				'21600' => __('%d Hours', 6, 'gridblstat'),
				'43200' => __('%d Hours', 12, 'gridblstat'),
				'86400' => __('1 Day', 'gridblstat')
			),
			'default' => '0'
		),
		'advance_mode' => array(
			'friendly_name' => __('LSF conf directory(Advanced)', 'gridblstat'),
			'description' => __('Allow modification of the LSF_ENVDIR path (under which has lsf.conf and lsf.licensescheduler).<br>Do not modify if you are unsure!', 'gridblstat'),
			'method' => 'custom',
			'value' => $custom_advance_mode
		),
		'lsid' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:lsid|'
		),
		'save_component' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields, (isset($collector) ? $collector : array()))
		)
	);

	?>
	<script type='text/javascript'>

	function disable_advanced() {
		$('#lsf_envdir').prop('disabled', false);
		$('#row_ls_hosts').hide();
		$('#row_ls_port').hide();
		$('#row_lsf_strict_checking').hide();
	}

	function enable_advanced() {
		$('#lsf_envdir').prop('disabled', true);
		$('#row_ls_hosts').show();
		$('#row_ls_port').show();
		$('#row_lsf_strict_checking').show();
	}

	$(function() {
		var advanced = <?php print ($advanced ? 'true':'false');?>;

		$('#advance_mode').click(function() {
			if ($('#advance_mode').prop('checked')) {
				disable_advanced();
			} else {
				enable_advanced();
			}
		});

		if ($('#advance_mode').is(':checked')) {
			disable_advanced();
		} else {
			enable_advanced();
		}
	});

	</script>
	<?php

	html_end_box();

	form_save_button('blstat_collectors.php', 'return', 'lsid');
}

function validate_request_vars() {
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
			)
	);

	validate_store_request_vars($filters, 'sess_blsc');
	/* ================= input validation ================= */

}

function filter() {
	global $config, $item_rows;

	html_start_box(__('License Scheduler Collectors', 'gridblstat') . rtm_hover_help('console_license_scheduler_task.html', __('Learn More', 'gridblstat')), '100%', '', '3', 'center', 'blstat_collectors.php?action=edit');

	?>
	<tr class='odd'>
		<td>
		<form id='lists' action='blstat_collectors.php'>
			<table class='filterTable'>
				<tr>
					<td><?php print __('Search', 'gridblstat');?></td>
					<td>
						<input type='text' id='filter' size='20' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
					<td><?php print __('Rows', 'gridblstat');?></td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('rows') == '-1') {?> selected<?php }?>><?php print __('Default:'.read_config_option('num_rows_table'), 'gridblstat');?></option>
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
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'gridblstat');?>' title='<?php print __esc('Set/Refresh Filters', 'gridblstat');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'gridblstat');?>' title='<?php print __esc('Clear Filters', 'gridblstat');?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		<script type='text/javascript'>

		function applyFilter() {
			strURL  = 'blstat_collectors.php?header=false';
			strURL += '&rows=' + $('#rows').val();
			strURL += '&filter=' + $('#filter').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = 'blstat_collectors.php?header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#lists').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});

			$('#go').click(function() {
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});
		});

		</script>
		</td>
	</tr>
	<?php

	html_end_box();

}

function collectors() {
	global $config, $actions;
	$sql_params = array();

	validate_request_vars();

	/* form the 'where' clause for our main sql query */
	if (strlen(get_request_var('filter'))) {
		$sql_where = "WHERE (name LIKE ?) OR (region LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	} else {
		$sql_where = '';
	}

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
        $rows = read_config_option('num_rows_table');
	} else {
        $rows = get_request_var('rows');
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$total_rows = db_fetch_cell_prepared("SELECT
		COUNT(*)
		FROM grid_blstat_collectors
		$sql_where", $sql_params);

	$collectors = db_fetch_assoc_prepared("SELECT *
		FROM grid_blstat_collectors
		$sql_where
		$sql_order
		$sql_limit", $sql_params);

    $nav = html_nav_bar('blstat_collectors.php', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 10, __('Collectors', 'thold'), 'page', 'main');

	filter();

	form_start('blstat_collectors.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$display_text = array(
		'name'           => array('display' => __('Collector Name', 'gridblstat'),  'sort' => 'ASC'),
		'region'         => array('display' => __('Region Name', 'gridblstat'),     'sort' => 'ASC'),
		'disabled'       => array('display' => __('Status', 'gridblstat'),          'sort' => 'ASC'),
		'lsid'           => array('display' => __('ID', 'gridblstat'),              'sort' => 'ASC'),
		'ls_version'     => array('display' => __('LS Version', 'gridblstat'),      'sort' => 'ASC'),
		'poller_freq'    => array('display' => __('Poller Freq', 'gridblstat'),     'sort' => 'ASC'),
		'graph_freq'     => array('display' => __('Graph Freq', 'gridblstat'),      'sort' => 'ASC'),
		'blstat_lastrun' => array('display' => __('Poller Last Run', 'gridblstat'), 'sort' => 'DESC'),
		'graph_lastrun'  => array('display' => __('Graph Last Run', 'gridblstat'),  'sort' => 'DESC')
	);

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;
	if (cacti_sizeof($collectors)) {
		foreach ($collectors as $item) {
			$id = $item['lsid'];

			if ($item['blstat_lastrun'] != '0000-00-00 00:00:00') {
				$lastrun= time() -strtotime($item['blstat_lastrun']);
				if ($lastrun < 60) {
					$lastrun = __('%d secs Ago', $lastrun, 'gridblstat');
				} else {
					$lastrun = __('%s Ago', display_hours($lastrun/60), 'gridblstat');
				}
			} else {
				$lastrun = __('Never', 'gridblstat');
			}

			if ($item['graph_lastrun'] != '0000-00-00 00:00:00') {
				$lastgrun = __('%s Ago', display_hours((time() - strtotime($item['graph_lastrun'])) / 60), 'gridblstat');
			} else {
				$lastgrun = __('Never', 'gridblstat');
			}

			$hosts = explode(' ', $item['ls_hosts']);
			$lh = '';
			if (cacti_sizeof($hosts)) {
				foreach($hosts as $host) {
					$lhp = explode('.', $host);
					$lh .= (strlen($lh) ? ' ':'') . $lhp[0];
				}
			}

			form_alternate_row('line' . $id, true);

			form_selectable_cell(filter_value($item['name'], get_request_var('filter'), html_escape('blstat_collectors.php?action=edit&lsid=' . $id)), $id);
			form_selectable_cell(filter_value($item['region'], get_request_var('filter')), $id);
			form_selectable_cell($item['disabled'] == '' ? '<font class="deviceUp">' . __('Enabled', 'gridblstat') . '</font>':'<font class="deviceDown">' . __('Disabled', 'gridblstat') . '</font>', $id);
			form_selectable_cell($id, $id);
			form_selectable_cell($item['ls_version'], $id);
			form_selectable_cell($item['poller_freq'] . ' Seconds', $id);
			form_selectable_cell(($item['graph_freq'] == 0 ? __('Disabled', 'gridblstat'):__('%d Hours', round($item['graph_freq']/3600,2), 'gridblstat')), $id);
			form_selectable_cell($lastrun, $id);
			form_selectable_cell($lastgrun, $id);
			form_checkbox_cell($item['name'], $id);

			form_end_row();
		}

		html_end_box(false);
		print $nav;
	} else {
		print '<tr><td colspan="7"><em>' . __('No License Scheduler Collectors Found', 'gridblstat') . '</em></td></tr>';
		html_end_box(false);
	}


	form_hidden_box('save_list', '1', '');

	draw_actions_dropdown($actions);

	form_end();
}
