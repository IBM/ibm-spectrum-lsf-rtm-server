<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');

$grid_host_control_actions = array(
	1 => __('Open', 'grid'),
	2 => __('Close', 'grid')
);

$title = __('IBM Spectrum LSF RTM - Batch Host Management', 'grid');

set_default_action();

validate_bhosts_request_vars();

switch (get_request_var('action')) {
	case 'actions':
		form_action();
		break;
	case 'ajax_rtm_users':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bhosts.php', 'job_user', $sql_where);
		break;
	case 'ajax_rtm_hgroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bhosts.php', 'hgroup', $sql_where);
		break;
	default:
		grid_view_bhosts();
	break;
}

function validate_bhosts_request_vars() {
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
			'default' => 'numRun',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'hgroup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'resource_str' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'resource_sanitize_search_string'),
			'pageset' => true
			),
		'load_page' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			)
	);

	validate_store_request_vars($filters, 'sess_gbh');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */
}

function form_action() {
	global $config, $grid_host_control_actions;

	$count_ok     = 0;
	$count_fail   = 0;
	$action_level = 'host';
	$message = '';

	if (isset_request_var('command') && get_nfilter_request_var('command') == 'goback') {
		header('Location: grid_bhosts.php');
		exit;
	}

	debug_log_clear('grid_admin');
	debug_log_clear('grid_admin_ok');
	debug_log_clear('grid_admin_failed');

	if (isset_request_var('selected_items') && read_config_option('grid_management_clusters') == 'on') {
		form_input_validate(trim(get_nfilter_request_var('message')), 'message', '', false, 'error_mandatory_input_field');
	}
	if (isset_request_var('message')) {
		form_input_validate(get_nfilter_request_var('message'),   'message', '^[A-Za-z0-9\._\\\@\ \/-]+$', true, '148');

	}
	if (isset_request_var('action_lockid')) {
		form_input_validate(trim(get_nfilter_request_var('action_lockid')),   'action_lockid', '^[A-Za-z0-9]+$', true, '148');
	}

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items') && !isset($_SESSION['sess_error_fields']['message'])&& !isset($_SESSION['sess_error_fields']['action_lockid'])) {
		if (isset_request_var('message') && trim(get_nfilter_request_var('message')) != '') {
			$message = __("'%s' - by RTM User '%s'", trim(get_nfilter_request_var('message')), get_username($_SESSION['sess_user_id']), 'grid');
		} else {
			$message = '';
		}
		if (isset_request_var('action_lockid')) { //change $message to array.
			$message_array = array();
			$message_array['message'] = $message;
			$message_array['action_lockid'] = trim(get_nfilter_request_var('action_lockid'));
			$message = $message_array;
		}

		if (get_request_var('drp_action') == '1') { /* Open Host  */
			$host_action = 'open';
		} else if (get_request_var('drp_action') == '2') { /* Close Host */
			$host_action = 'close';
		}
		//$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		$rsp_content = array();
		$advocate_max = 30;
		if ($selected_items_whole != false) {
		for($i=0; $i<cacti_sizeof($selected_items_whole); $i+=$advocate_max){
			$selected_items = array_slice($selected_items_whole, $i, $advocate_max);
			if(cacti_sizeof($selected_items)<=0) break;
			$json_return_format = sorting_json_format($selected_items, $message, $action_level); //sort the variables into required format

			$advocate_key = session_auth();

			$json_host_info = array (
				'key'    => $advocate_key,
				'action' => $host_action,
				'target' => $json_return_format,
			);
			$output = json_encode($json_host_info);

			$curl_output = exec_curl($action_level, $output); //pass to advocate for processing

			if ($curl_output['http_code'] == 400) {
				raise_message(134);
			} else if ($curl_output['http_code'] == 500) {
				raise_message(135);
			} else {
				if ($curl_output['http_code'] == 200) {
					$log_action = $grid_host_control_actions[get_request_var('drp_action')];
					$json_output = json_decode($output);
					$username_log = get_username($_SESSION['sess_user_id']);
					foreach ($json_output->target as $target) {
					    $action_message = get_request_var('message');
					    cacti_log("Host '{$target->name}', {$log_action} by '{$username_log}', comment: '{$action_message}'.", false, 'LSFCONTROL');
					}
				} else {
					raise_message(136);
				}
			}

			$content_response = $curl_output['content']; //return response from advocate in json format

			$json_decode_content_response = json_decode($content_response,true);

			$rsp_content_temp = $json_decode_content_response['rsp'];
			if(is_array($rsp_content_temp)){
				$rsp_content = array_merge($rsp_content, $rsp_content_temp);
			}
		}
		}

		for ($k=0;$k<count($rsp_content);$k++) {
			$key_sort[$k] = $rsp_content[$k]['clusterid'];
		}

                $output_message='';
		if(isset($key_sort)){
			asort($key_sort);
			foreach( $key_sort as $key => $val) {
				foreach ($rsp_content as $key_rsp_content => $value) {
					if ($key_rsp_content == $key) {
						if (strchr($value['name'], '|')) {
							$messages = explode("|", $value['status_message']);
							foreach ($messages as $message) {
								if(strlen($message) > 0) {
									if(strstr($message, "Unable to")) {
										$count_fail ++;
										$return_status = __('Failed. Status Code: %d', $value['status_code'], 'grid');
									} else {
										$return_status = __('Ok');
										$count_ok ++;
									}
									$message='Status:' . $return_status . ' - Cluster Name:' . grid_get_clustername($value['clusterid']) . ' - '.$message.'<br/>';
									$output_message=$output_message.$message;
								}
							}
						} else {
							if ($value['status_code'] == 0) {
								$return_status = __('Ok');
								$count_ok ++;
							} else {
								$count_fail ++;
								$return_status = __('Failed. Status Code: %d', $value['status_code'], 'grid');
							}
				                        $message='Status:' . $return_status . ' - Cluster Name:' . grid_get_clustername($value['clusterid']) . ' - '.$value['status_message'].'<br/>';
				                        $output_message=$output_message.$message;
						}
					}
				}
			}
			if($count_fail>0)
              			raise_message('mymessage', $output_message, MESSAGE_LEVEL_ERROR);
            		else
              			raise_message('mymessage', $output_message, MESSAGE_LEVEL_INFO);
		}
		header('Location: grid_bhosts.php');
		exit;
	}

	/* setup some variables */
	$host_list = '';
	$i = 0;

	if (isset_request_var('selected_items') && (isset($_SESSION['sess_error_fields']['action_lockid']) || isset($_SESSION['sess_error_fields']['message']))) {
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		if ($selected_items_whole != false) {
			foreach ($selected_items_whole as $selected_item) {
				$host_whole_array[$i] = $selected_item;
				$host_details = explode(':',$selected_item);

				input_validate_input_number($host_details[1]);

				$host_list .= '<li>' . __('Host %s from Cluster Name %s', $host_details[0], grid_get_clustername($host_details[1]), 'grid') . '</li>';

				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];

				$i++;
			}
		}
	} else {
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'chk_', '4') == 0) {
				$key = str_replace('@', '.', $key);
				$host_whole_array[$i] = substr($key, 4);
				$host_details = explode(':',substr($key, 4));

				/* ================= input validation ================= */
				input_validate_input_number($host_details[1]);
				/* ================= input validation ================= */

				$host_list .= '<li>' . __('Host %s from Cluster Name %s', $host_details[0], grid_get_clustername($host_details[1]), 'grid') . '</li>';

				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];

				$i++;
			}
		}
	}

	general_header();

	form_start('grid_bhosts.php');

	html_start_box($grid_host_control_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	switch(get_filter_request_var('drp_action')) {
	case '1':
		$action = 'open';
		break;
	case '2':
		$action = 'close';
		break;
	}

	if (!empty($host_array)) {
		print "<tr>
			<td class='textArea' colspan=2>
				<p>" . __('Are you sure you want to %s the following Host(s)?', $action, 'grid') . "</p>
				<div class='itemlist'><ul>$host_list</ul></div>
			</td>
		</tr>";
		if($action == 'open'){
			print "<tr>
				<td>Select LockId<br>"
					. __('LockId that are appended to LSF with the -i option.', 'grid')
					. "<br>Select the LockId for $action. Default value all. </td>
				<td><select name='action_lockid'><option value='all' selected>all</option>";
				$action_lockid_old= '';
				if (isset_request_var('action_lockid')) {
					$action_lockid_old= sanitize_search_string(get_nfilter_request_var('action_lockid'));
				}
				$lockid_result = db_fetch_assoc("SELECT DISTINCT lockid FROM grid_host_closure_lockids");
				foreach($lockid_result as $result) {
					print '<option value="'.$result['lockid'].'"'. ($action_lockid_old==$result['lockid'] ? ' selected':'') . '>'. $result['lockid'] . '</option>'."\n";
				}
			print "</select><br></td></tr>";
		} else {
			print "<tr>
				<td>Input LockId<br>"
					. __('LockId that are appended to LSF with the -i option. Characters in [A-Za-z0-9]', 'grid')
					. "<br>This option will be ignored for the old LSF, which do not support -i option. </td>
				<td><input ";
				if (isset($_SESSION['sess_error_fields']['message']) || isset($_SESSION['sess_error_fields']['action_lockid'])) {
					if (isset_request_var('action_lockid')) {
						print " value='". sanitize_search_string(get_nfilter_request_var('action_lockid')) . "' ";
					}
				}
				if (isset($_SESSION['sess_error_fields']['action_lockid'])) {
					print " class='txtErrorTextBox' ";
				}
			print "type=text name='action_lockid' maxlength='512'>";
			print "</td></tr>";
		}
		print "<tr>
			<td class='textArea'>
				" . __('Comments that are appended to LSF with the -C option.', 'grid');

			if (read_config_option('grid_management_clusters') != 'on') {
				print '<br>' . __('Leave BLANK if no comment is required.', 'grid');
			}

			print '<br>' . __('&lt;RTM&lt; %s &gt;&gt; will be appended after your comments.', get_username($_SESSION['sess_user_id']), 'grid') . '</td>';

			print '<td class="textArea"><input ';

			if (isset($_SESSION['sess_error_fields']['message']) || isset($_SESSION['sess_error_fields']['action_lockid'])) {
				if (isset_request_var('message')) {
					print " value='". sanitize_search_string(get_nfilter_request_var('message')) . "' ";
				}
			}
			if (isset($_SESSION['sess_error_fields']['message'])) {
				print " class='txtErrorTextBox' ";
				unset($_SESSION['sess_error_fields']['message']);
			}
			if (isset($_SESSION['sess_error_fields']['action_lockid'])) { //delay unset action_lockid.
				unset($_SESSION['sess_error_fields']['action_lockid']);
			}

			print "type=text name='message' col='255' size='40' maxlength='512'></td>
			</tr>
			<tr>
				<td colspan=2 class='deviceDown'>" .
					__('NOTE: Wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "
				</td>
			</tr>";
	}

	if (!isset($host_array)) {
		raise_message(40);
		header('Location: grid_bhosts.php?header=false');
		exit;
	} else {
		$save_html = "<input type='submit' value='" . __('Yes', 'grid') . "'>";
		$button_false = __('No', 'grid');
	}

	print " <tr>
		<td class='saveRow' colspan='2'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='command' value=''>
			<input type='hidden' name='selected_items' value='" . (isset($host_array) ? serialize($host_array) : '') . "'>
			<input type='hidden' name='selected_items_hostname' value='".(isset($host_array_hostname) ? serialize($host_array_hostname) : '') . "'>
			<input type='hidden' name='selected_items_whole' value='".(isset($host_whole_array) ? serialize($host_whole_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . $button_false ."' alt='' onClick='cactiReturnTo()'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function grid_view_get_bhosts_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	global $grid_out_of_services;

	get_filter_request_var('clusterid');

	if (get_request_var('clusterid') <= 0) {
		$status = db_fetch_assoc('SELECT DISTINCT gh.status AS bstatus, gl.status AS lstatus
			FROM grid_hosts AS gh
			INNER JOIN grid_load AS gl
			ON gh.host = gl.host
			AND gh.clusterid = gl.clusterid');
	} else {
		$status = db_fetch_assoc_prepared('SELECT DISTINCT gh.status AS bstatus, gl.status AS lstatus
			FROM grid_hosts AS gh
			INNER JOIN grid_load AS gl
			ON gh.host = gl.host
			AND gh.clusterid = gl.clusterid
			WHERE gh.clusterid = ?',
			array(get_request_var('clusterid')));
	}

	for ($i = 0, $il = count($status); $i < $il; $i++) {
		if ($status[$i]['lstatus'] . ':' . $status[$i]['bstatus'] == get_request_var('status')) break;
	}

	if ($i == $il && get_request_var('status') > 0) {
		set_request_var('status', '-1');
	}

	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'gh.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* user bhosts sql where */
	if (get_request_var('job_user') != '-1' && get_request_var('job_user') != '') {
		$hosts = grid_get_user_hosts(get_request_var('clusterid'), get_request_var('job_user'));

		if (strlen($hosts)) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gh.host IN ($hosts))";
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gh.host='')";
		}
	}

	/* host group sql where */
	if (get_request_var('hgroup') != '-1') {
		if (get_request_var('clusterid') == 0) {
			$hosts = db_fetch_assoc_prepared('SELECT DISTINCT host
				FROM grid_hostgroups
				WHERE groupName = ?',
				array(get_nfilter_request_var('hgroup')));
		} else {
			$hosts = db_fetch_assoc_prepared('SELECT host
				FROM grid_hostgroups
				WHERE groupName = ?
				AND clusterid = ?',
				array(get_request_var('hgroup'), get_request_var('clusterid')));
		}

		if (!empty($hosts)) {
			$hgroup_hosts = '';
			$num_hosts = 0;

			foreach($hosts as $host) {
				if ($num_hosts == 0) {
					$hgroup_hosts .= db_qstr($host['host']);
				} else {
					$hgroup_hosts .= ',' . db_qstr($host['host']);
				}

				$num_hosts++;
			}

			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "gh.host IN ($hgroup_hosts)";
		}
	}

	if (get_request_var('resource_str') != '') {
		if (get_request_var('clusterid') > 0) {
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
				$res_tool  = ".\\gridhres.exe";
				$res_tool_fullpath = grid_get_res_tooldir(get_request_var('clusterid')) . "\\gridhres.exe";
			} else {
				$res_tool  = "./gridhres";
				$res_tool_fullpath = grid_get_res_tooldir(get_request_var('clusterid')) . "/gridhres";
			}

			if (is_executable($res_tool_fullpath)) {
				get_filter_request_var('clusterid');

				$cwd = getcwd();
				chdir(grid_get_res_tooldir(get_request_var('clusterid')));


				$res_cmd   = $res_tool . ' -C ' . get_request_var('clusterid') . ' -R ' . cacti_escapeshellarg(get_request_var('resource_str'));
				$ret_val   = 0;
				$ret_out   = array();
				$res_hosts = exec($res_cmd, $ret_out, $ret_val);

				chdir($cwd);
				if (!$ret_val) {
					if (strlen($res_hosts)) {
						$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " gh.host IN ($res_hosts)";
					} else {
						$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' AND gh.host IS NULL';
					}
				} else {
					$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' gh.host IS NULL';

					if ($ret_val == 96) {
						$_SESSION['sess_messages'] = __('No hosts returned', 'grid');
					} else if ($ret_val == 95) {
						$_SESSION['sess_messages'] = __('Invalid Resource String', 'grid');
					} else {
						$_SESSION['sess_messages'] = __('Unknown LSF Error: %s', $ret_val, 'grid');
					}
				}
			} else {
				cacti_log('ERROR: gridhres either does not exist or is not executable!');
			}
		} else {
			unset_request_var('resource_str');
			load_current_session_value('resource_str', 'sess_grid_view_bhosts_resource_str', '');
		}
	}

	/* hostType sql where */
	if (get_request_var('type') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'hostType=?';
		$sql_params[] = get_request_var('type');
	}

	/* hostModel sql where */
	if (get_request_var('model') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'hostModel=?';
		$sql_params[] = get_request_var('model');
	}

	/* status sql where */
	if (get_request_var('status') != '-1') {
		if (get_request_var('status') == '-2') {
			$out_of_services = '(';
			foreach($grid_out_of_services as $grid_out_of_service) {
				$out_of_services .= '"'.$grid_out_of_service.'",';
			}
			$out_of_services = substr($out_of_services, 0, -1);
			$out_of_services .= ')';

			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' ((gh.status IN ' . $out_of_services . ') OR (gl.status IN ' . $out_of_services . '))';
		} else {
			$stats = explode(':',get_request_var('status'));
			$lstat = $stats[0];
			$bstat = $stats[1];

			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (gh.status=?) AND (gl.status=?)';
			$sql_params[] = $bstat;
			$sql_params[] = $lstat;
		}
	}

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
			"(gh.host LIKE ? OR
			gh.status LIKE ? OR
			ghi.hostType LIKE ? OR
			ghi.hostModel LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	/* queue sql where */
	if (get_request_var('queue') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'queue=?';
		$sql_params[] = get_request_var('queue');
	}

	$columns = get_custom_elim_columns();
	$sql_col = '';
	if (!empty($columns)) {
		foreach($columns as $c) {
			$sql_col .= ", MAX(CASE WHEN ghr.resource_name='$c' THEN totalValue ELSE NULL END) AS \"$c\"";
		}
	}

	$sql_order = get_order_string();
	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	$sql_query = "SELECT *
		FROM (
			SELECT DISTINCT gc.clusterid, gc.clustername, gh.host, ghi.hostType, ghi.hostModel, ghi.maxCpus, ghi.ngpus AS ghi_ngpus, ghi.gMaxFactor,
			CONCAT_WS('',gl.status,':',gh.status) AS status, gl.ut, gl.r1m,
			(CASE WHEN gl.status NOT LIKE 'U%' THEN ((ghi.maxMem - gl.mem) / ghi.maxMem) * 100 ELSE 0 END) AS memUsage,
			(CASE WHEN gl.status NOT LIKE 'U%' THEN ((ghi.maxSwap - gl.swp) / ghi.maxSwap) * 100 ELSE 0 END) AS swpUsage,
			gl.pg, gh.cpuFactor, gh.maxJobs, gh.numJobs, gh.numRun, gh.numSSUSP, gh.numUSUSP, gh.numRESERVE, gh.time_in_state
			$sql_col
			FROM grid_clusters AS gc
			INNER JOIN grid_hosts AS gh
			ON gc.clusterid=gh.clusterid
			INNER JOIN grid_hostinfo AS ghi
			ON gc.clusterid=ghi.clusterid
			AND gh.host=ghi.host".(get_request_var('queue') != '-1'?
			" LEFT JOIN grid_queues_hosts AS gqh
			ON gc.clusterid=gqh.clusterid
			AND gh.host = gqh.host ":" ")
			."INNER JOIN grid_load AS gl
			ON gc.clusterid = gl.clusterid
			AND gh.host = gl.host
			INNER JOIN grid_hosts_resources AS ghr
			ON gc.clusterid = ghr.clusterid
			AND gh.host=ghr.host
			$sql_where
			GROUP BY gh.clusterid, gh.host
		) AS rs
		$sql_order
		$sql_limit";

	//cacti_log("DEBUG: SQL: $sql_query");
	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function bhostsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_bhosts.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = grid_get_clusterlist();

							if (!empty($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_bhosts.php', 'Group', 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td>
						<select id='status'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>><?php print __('Out of Service', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') <= 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT
									grid_hosts.status AS bstatus,
									grid_load.status AS lstatus
									FROM grid_hosts
									INNER JOIN grid_load
									ON grid_hosts.host = grid_load.host
									AND grid_hosts.clusterid = grid_load.clusterid
									ORDER BY lstatus');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT
									grid_hosts.status AS bstatus,
									grid_load.status AS lstatus
									FROM grid_hosts
									INNER JOIN grid_load
									ON grid_hosts.host = grid_load.host
									AND grid_hosts.clusterid = grid_load.clusterid
									WHERE grid_hosts.clusterid = ?
									ORDER BY lstatus',
									array(get_request_var('clusterid')));
							}

							if (!empty($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['lstatus'] . ':' . $status['bstatus'] . '"'; if (get_request_var('status') == $status['lstatus'] . ':' . $status['bstatus']) { print ' selected'; } print '>' . html_escape(ucfirst($status['lstatus']) . ':' . ucfirst($status['bstatus'])) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'grid');?>
					</td>
					<td>
						<select id='queue'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') <= 0) {
								$queues = db_fetch_assoc('SELECT DISTINCT
									queuename
									FROM grid_queues
									ORDER BY queuename');
							} else {
								$queues = db_fetch_assoc_prepared('SELECT
									queuename
									FROM grid_queues
									WHERE clusterid = ?
									ORDER BY queuename',
									array(get_request_var('clusterid')));
							}

							if (!empty($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . html_escape($queue['queuename']) . '"'; if (get_request_var('queue') == $queue['queuename']) { print ' selected'; } print '>' . html_escape($queue['queuename']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'grid');?>
					</td>
					<td>
						<select id='refresh'>
							<?php
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							if (!empty($grid_refresh_interval)) {
								foreach($grid_refresh_interval as $key => $value) {
									if ($key >= $max_refresh) {
										print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc("SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									ORDER BY hostModel");
							} else {
								$models = db_fetch_assoc_prepared("SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									AND clusterid = ?
									ORDER BY hostModel",
									array(get_request_var('clusterid')));
							}

							if (!empty($models)) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc("SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									ORDER BY hostType");
							} else {
								$types = db_fetch_assoc_prepared("SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									AND clusterid = ?
									ORDER BY hostType",
									array(get_request_var('clusterid')));
							}

							if (!empty($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] . '"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_bhosts.php', 'User', 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Hosts', 'grid');?>
					</td>
					<td>
						<select id='rows'>
						<?php
							if (cacti_sizeof($grid_rows_selector)) {
							foreach ($grid_rows_selector as $key => $value) {
								print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
							}
						?>
						</select>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<?php resource_browser();?>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}

function get_custom_elim_columns() {
	static $cols;

	if (!is_array($cols)) {
		$cols    = array();
		$columns = db_fetch_assoc_prepared("SELECT name
			FROM grid_settings
			WHERE user_id = ?
			AND name LIKE 'host_elim_%'
			AND value='on'
			ORDER BY name", array($_SESSION['sess_user_id']));

		if (cacti_sizeof($columns)) {
			$elimvals  = array_rekey(db_fetch_assoc("SELECT DISTINCT resource_name
				FROM grid_hosts_resources
				WHERE resource_name NOT IN ('r15s','r1m','r15m','ut','pg','io','ls','it','tmp','swp','mem')
				AND host <> 'ALLHOSTS'
				ORDER BY resource_name"), "resource_name", "resource_name");

			foreach($columns as $c) {
				$resource_name = str_replace('host_elim_', '', $c['name']);
				if (isset($elimvals[$resource_name])) {
					$cols[] = $resource_name;
				}
			}

			return $cols;
		}
	} else {
		return $cols;
	}
}

function grid_view_bhosts() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config, $grid_host_control_actions;
	$sql_params = array();

	grid_set_minimum_page_refresh();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$bhosts_results = grid_view_get_bhosts_records($sql_where, true, $rows, $sql_params);

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = urlPath + 'plugins/grid/grid_bhosts.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&queue=' + $('#queue').val();

		if ($('#resource_str').length) {
			strURL += '&resource_str=' + escape($('#resource_str').val());
		}

		strURL += '&filter=' + $('#filter').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&model=' + $('#model').val();
		strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
		strURL += '&job_user=' + $('#job_user').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = urlPath + 'plugins/grid/grid_bhosts.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #status, #queue, #resource_str, #filter, #type, #model, #hgroup, #job_user, #refresh, #rows').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
		applySkinRTM();
	});

	</script>
	<?php

	$debug_log = debug_log_return('grid_admin');

	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table width='100%'>
			<tr class='debugLog'>
				<td>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box(__('Batch Host Filters', 'grid'), '100%', '', '3', 'center', '');
	bhostsFilter();
	html_end_box();

	if (get_request_var('queue') == -1) {
		$bhosts_queue_limits = array_rekey(db_fetch_assoc('SELECT
			SUM(hostJobLimit) AS slots,
			host
			FROM grid_queues
			INNER JOIN grid_queues_hosts
			ON queuename=queue
			AND grid_queues.clusterid=grid_queues_hosts.clusterid
			GROUP BY host'), 'host', 'slots');
	} else {
		$bhosts_queue_limits = array_rekey(db_fetch_assoc_prepared('SELECT SUM(hostJobLimit) AS slots, host
			FROM grid_queues
			INNER JOIN grid_queues_hosts
			ON queuename=queue
			AND grid_queues.clusterid=grid_queues_hosts.clusterid
			WHERE grid_queues_hosts.queue = ?
			GROUP BY host', array(get_request_var('queue'))), 'host', 'slots');
	}

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		form_start('grid_bhosts.php', 'chk');
	}

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(DISTINCT gh.host, gh.clusterid)
		FROM grid_clusters AS gc
		INNER JOIN grid_hosts AS gh
		ON gc.clusterid = gh.clusterid
		INNER JOIN grid_hostinfo AS ghi
		ON gh.clusterid = ghi.clusterid
		AND gh.host = ghi.host".(get_request_var('queue') != '-1'?
		" LEFT JOIN grid_queues_hosts AS gqh
		ON gc.clusterid=gqh.clusterid
		AND gh.host = gqh.host ":" ")
		."INNER JOIN grid_load as gl
		ON gh.clusterid = gl.clusterid
		AND gh.host = gl.host
		INNER JOIN grid_hosts_resources AS ghr
		ON gh.clusterid = ghr.clusterid
		AND gh.host=ghr.host
		$sql_where", $sql_params);

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'grid')
		),
		'host' => array(
			'display' => __('Host Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster'),
			'dbname'  => 'host_cluster',
			'sort'    => 'ASC'
		),
		'hostType' => array(
			'display' => __('Type'),
			'dbname'  => 'host_type',
			'sort'    => 'ASC'
		),
		'hostModel' => array(
			'display' => __('Model'),
			'dbname'  => 'host_model',
			'sort'    => 'ASC'
		),
		'status' => array(
			'display' => __('Load/Batch'),
			'dbname'  => 'host_status',
			'sort'    => 'DESC'
		),
		'time_in_state' => array(
			'display' => __('TIS'),
			'dbname'  => 'show_time_in_state',
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'cpuFactor' => array(
			'display' => __('CPU Fact'),
			'dbname'  => 'host_cpu_factor',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ut' => array(
			'display' => __('CPU Pct'),
			'dbname'  => 'host_ut',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ghi_ngpus' => array(
			'display' => __('GPU Count'),
			'dbname'  => 'host_ngpus',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'gMaxFactor' => array(
			'display' => __('GPU Fact'),
			'dbname'  => 'host_gMaxFactor',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'r1m' => array(
			'display' => __('RunQ 1m'),
			'dbname'  => 'host_r1m',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'memUsage' => array(
			'display' => __('Mem Usage'),
			'dbname'  => 'host_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'swpUsage' => array(
			'display' => __('Page Usage'),
			'dbname'  => 'host_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pg' => array(
			'display' => __('Page Rate'),
			'dbname'  => 'host_pagerate',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'maxJobs' => array(
			'display' => __('Max %s', format_job_slots()),
			'dbname'  => 'host_max_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numJobs' => array(
			'display' => __('Num %s', format_job_slots()),
			'dbname'  => 'host_num_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRun' => array(
			'display' => __('Run %s', format_job_slots()),
			'dbname'  => 'host_run_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numSSUSP' => array(
			'display' => __('SSUSP %s', format_job_slots()),
			'dbname'  => 'host_ssusp_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numUSUSP' => array(
			'display' => __('USUSP %s', format_job_slots()),
			'dbname'  => 'host_ususp_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRESERVE' => array(
			'display' => __('Reserve %s', format_job_slots()),
			'dbname'  => 'host_reserve_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	$columns = get_custom_elim_columns();
	if (!empty($columns)) {
		foreach($columns as $c) {
			$display_text += array(
				$c => array(
					'display' => ucfirst($c),
					'align'   => 'right',
					'sort'    => 'DESC'
				)
			);
		}
	}

	$display_text = form_process_visible_display_text($display_text);

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$colspan = cacti_sizeof($display_text) + 1;
	} else {
		$colspan = cacti_sizeof($display_text);
	}

	/* generate page list */
	$nav = html_nav_bar('grid_bhosts.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Batch Hosts', 'grid'), 'page', 'main');

	print $nav;

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$disabled = false;
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	} else {
		$disabled = true;
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	}


	if (!empty($bhosts_results)) {
		foreach ($bhosts_results as $bhosts) {
			$stat  = explode(':',$bhosts['status']);
			$lstat = ucfirst($stat[0]);
			$bstat = ucfirst($stat[1]);

			$host_id = db_fetch_cell_prepared('SELECT id
				FROM host
				WHERE clusterid = ?
				AND hostname = ?',
				array($bhosts['clusterid'], $bhosts['host']));

			if (!empty($host_id)) {
				$host_graphs = db_fetch_cell_prepared('SELECT count(*)
					FROM graph_local
					WHERE host_id = ?',
					array($host_id));
			} else {
				$host_graphs = 0;
			}

			$bhl = str_replace('.', '@', $bhosts['host'] . ':' . $bhosts['clusterid']);

			form_alternate_row('line' . $bhl, true, $disabled);

			?>
			<td class='nowrap' style='width:20px'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $bhosts['clusterid'] .'&ajax_host_query=' .$bhosts['host'] . '&exec_host=' . $bhosts['host'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs');?>'></a>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bzen.php?reset=1&action=zoom&clusterid=' . $bhosts['clusterid'] . '&exec_host=' . $bhosts['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hjobdetail.gif' alt='' title='<?php print __esc('View Host Job Detail');?>'></a>
				<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details WHERE hostname=?", array($bhosts["host"]))) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&host=' . $bhosts['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts');?>'></a>
				<?php } if ($host_graphs > 0) {?><a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&reset=1&graph_template_id=-1&rfilter=&host_id=' . $host_id);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Graphs');?>'></a><?php }?>
				<?php api_plugin_hook_function('grid_bhost_action_insert', $bhosts); ?>
			</td>
			<?php

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $bhosts['clusterid'] .
				'&ajax_host_query=' . $bhosts['host'] .
				'&exec_host=' . $bhosts['host'] .
				'&status=RUNNING&page=1');

			form_selectable_cell_metadata('simple', 'host', $bhosts['clusterid'], $bhosts['host'], '', '', html_escape($bhosts['host']), true, $url);

			form_selectable_cell_metadata('simple', 'cluster', $bhosts['clusterid'], $bhosts['clustername'], 'host_cluster', '', true);

			form_selectable_cell_visible(filter_value($bhosts['hostType'], get_request_var('filter')), 'host_type', $bhl);
			form_selectable_cell_visible(filter_value($bhosts['hostModel'], get_request_var('filter')), 'host_model', $bhl);
			form_selectable_cell_visible($lstat . ':' . $bstat, 'host_status', $bhl);
			form_selectable_cell_visible(display_time_in_state($bhosts['time_in_state']), 'show_time_in_state', $bhl, 'right');
			form_selectable_cell_visible($bhosts['cpuFactor'], 'host_cpu_factor', $bhl, 'right');
			form_selectable_cell_visible(display_ut($bhosts['ut']), 'host_ut', $bhl, 'right');
			form_selectable_cell_visible($bhosts['ghi_ngpus'], 'host_ngpus', $bhl, 'right');
			form_selectable_cell_visible($bhosts['gMaxFactor'], 'host_gMaxFactor', $bhl, 'right');
			form_selectable_cell_visible(display_load($bhosts['r1m']), 'host_r1m', $bhl, 'right');
			form_selectable_cell_visible(($bhosts['memUsage'] > 0 ? round($bhosts['memUsage'], 2):'0').'%', 'host_mem', $bhl, 'right');
			form_selectable_cell_visible(($bhosts['swpUsage'] > 0 ? round($bhosts['swpUsage'], 2):'0').'%', 'host_swap', $bhl, 'right');
			form_selectable_cell_visible(display_pg($bhosts['pg'],0), 'host_pagerate', $bhl, 'right');

			if (($bhosts['maxJobs'] == 0) || ($bhosts['maxJobs'] == '-')) {
				if (array_key_exists($bhosts['host'], $bhosts_queue_limits)) {
					if ($bhosts_queue_limits[$bhosts['host']] > 0) {
						$queue_max = $bhosts_queue_limits[$bhosts['host']];
					} else {
						$queue_max = 0;
					}

					if ($bhosts['maxCpus'] == '-') {
						$cpu_max = 0;
					} else {
						$cpu_max = $bhosts['maxCpus'];
					}

					if (max($cpu_max, $queue_max) == 0) {
						form_selectable_cell_visible('-', 'host_max_slots', $bhl, 'right');
					} else {
						form_selectable_cell_visible(max($cpu_max, $queue_max), 'host_max_slots', $bhl, 'right');
					}
				} else {
					if ($bhosts['maxCpus'] == '-') {
						form_selectable_cell_visible('-', 'host_max_slots', $bhl);
					} else {
						form_selectable_cell_visible($bhosts['maxCpus'], 'host_max_slots', $bhl);
					}
				}
			} elseif ($bhosts['maxJobs'] == -1) {
				form_selectable_cell_visible('-', 'host_max_slots', $bhl, 'right');
			} else {
				form_selectable_cell_visible($bhosts['maxJobs'], 'host_max_slots', $bhl, 'right');
			}

			form_selectable_cell_visible($bhosts['numJobs'], 'host_num_slots', $bhl, 'right');
			form_selectable_cell_visible($bhosts['numRun'], 'host_run_slots', $bhl, 'right');
			form_selectable_cell_visible($bhosts['numSSUSP'], 'host_ssusp_slots', $bhl, 'right');
			form_selectable_cell_visible($bhosts['numUSUSP'], 'host_ususp_slots', $bhl, 'right');
			form_selectable_cell_visible($bhosts['numRESERVE'], 'host_reserve_slots', $bhl, 'right');

			$columns = get_custom_elim_columns();
			$sql_col = '';
			if (!empty($columns)) {
				foreach($columns as $c) {
					form_selectable_cell(($bhosts[$c] == '' ? '-':(is_numeric($bhosts[$c]) ? number_format($bhosts[$c]):$bhosts[$c])), $bhl, '', 'right');
				}
			}

			if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
				form_checkbox_cell($bhosts['host'], $bhl, $disabled);
			}

			form_end_row();
		}

		html_end_box(false);
		/* put the nav bar on the bottom as well */
		print $nav;
	} else {
		print "<tr><td colspan='" . (cacti_sizeof($display_text)+1) . "'><em>" . __('No Batch Host Records Found', 'grid') . "</em></td></tr>";
		html_end_box(false);
	}

	if (!$disabled) {
		draw_actions_dropdown($grid_host_control_actions);
		form_end();
	} else {
		html_end_box();
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}
