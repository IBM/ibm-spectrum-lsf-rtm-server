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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$title = __('IBM Spectrum LSF RTM - Cluster Management', 'grid');

set_default_action();

if(strpos(get_request_var('action'), 'ajaxrefresh_') !== FALSE) {
	process_request_vars();
}

switch (get_request_var('action')){
	case 'actions':
		form_action();
		break;
	case 'ajaxsave':
		grid_clusterdb_ajax_save();
		break;
	case 'clear':
		process_request_vars();
		grid_set_minimum_page_refresh();

		print json_encode(array('clusterid' => get_request_var('clusterid'), 'limit' => get_request_var('limit'), 'refresh' => get_request_var('refresh'),
			'summary' => get_request_var('summary'), 'status' => get_request_var('status'), 'master' => get_request_var('master'),
			'tput' => get_request_var('tput'), 'perfmon' => get_request_var('perfmon'),
			'benchmark_exceptional_jobs' => get_request_var('benchmark_exceptional_jobs'), 'limstat' => get_request_var('limstat'),
			'batchstat' => get_request_var('batchstat'), 'gridstat' => get_request_var('gridstat'), 'memavastat' => get_request_var('memavastat'),
			'gorder' => get_request_var('gorder'),'vorder' => get_request_var('vorder')
			));

		break;
	case 'ajaxrefresh_views':
		show_views();
		break;
	case 'ajaxrefresh_div_graph_limstat':
		show_graphs_limstat();
		break;
	case 'ajaxrefresh_div_graph_batchstat':
		show_graphs_batchstat();
		break;
	case 'ajaxrefresh_div_graph_gridstat':
		show_graphs_gridstat();
		break;
	case 'ajaxrefresh_div_graph_memavastat':
		show_graphs_memavastat();
		break;
	case 'ajaxrefresh_div_graph_uncheckall':
		break;
	default:
		grid_clusters();
	break;
}

function show_views() {
	global $grid_cluster_control_actions, $config;

	$div_views = array (
		'div_view_summary' => array (
			'req_name' => 'summary',
			'hook_func' =>'view_cluster_summary'
		),
		'div_view_status' => array (
			'req_name' => 'status',
			'hook_func' =>'view_cluster_status'
		),
		'div_view_master' => array (
			'req_name' => 'master',
			'hook_func' =>'view_cluster_master'
		),
		'div_view_tput' => array (
			'req_name' => 'tput',
			'hook_func' =>'view_cluster_tput'
		),
		'div_view_perfmon' => array (
			'req_name' => 'perfmon',
			'hook_func' =>'view_cluster_perfmon'
		),
		'div_view_benchmark_exceptional_jobs' => array (
			'req_name' => 'benchmark_exceptional_jobs',
			'hook_func' =>'view_cluster_benchmark_exceptional_jobs'
		)
	);

	$clusterid = get_request_var('clusterid');
	$limit = get_request_var('limit');
	$refresh = get_request_var('refresh');

	$summary = get_request_var('summary');
	$status = get_request_var('status');
	$master = get_request_var('master');
	$tput = get_request_var('tput');
	$perfmon = get_request_var('perfmon');
	$benchmark_exceptional_jobs = get_request_var('benchmark_exceptional_jobs');

	if (isempty_request_var('vorder')) {
		set_request_var('vorder', 'div_view_summary,div_view_status,div_view_master,div_view_tput,div_view_perfmon,div_view_benchmark_exceptional_jobs');
	}

	$vorders = explode(',', get_request_var('vorder'));

	//print ($clusterid . '|' .$limit .'|' .$refresh .'|' .$summary .'|' .$status .'|' .$master .'|' .$tput .'|' .$perfmon .'|' .$benchmark_exceptional_jobs);

	foreach ($vorders as $vorder) {
		$req_name = $div_views[$vorder]['req_name'];
		$hook_func = $div_views[$vorder]['hook_func'];

		if (get_request_var($req_name) == 'true' ) {
			if ($req_name == 'summary') {
				$params = array('add_action');
				api_plugin_hook_function($hook_func, $params);
			} else {
				api_plugin_hook_function($hook_func);
			}
		}
	}

}

function show_graphs_limstat() {
	$clusterid = get_request_var('clusterid');

	if (get_request_var('limstat') == 'true' ) {
		api_plugin_hook_function('graph_cluster_limstat');
	}
}

function show_graphs_batchstat() {
	$clusterid = get_request_var('clusterid');

	if (get_request_var('batchstat') == 'true' ) {
		api_plugin_hook_function('graph_cluster_batchstat');
	}
}

function show_graphs_gridstat() {
	$clusterid = get_request_var('clusterid');

	if (get_request_var('gridstat') == 'true' ) {
		api_plugin_hook_function('graph_cluster_gridstat');
	}
}

function show_graphs_memavastat() {
	$clusterid = get_request_var('clusterid');

	if (get_request_var('memavastat') == 'true' ) {
		api_plugin_hook_function('graph_cluster_memavastat');
	}
}

function form_action(){
	global $config, $grid_cluster_control_actions;

	$action_level = 'cluster';
	$count_ok = 0;
	$count_fail = 0;
	$message = '';
	$pre_message='';

	if(isset_request_var('command') && get_request_var('command') == 'goback'){
		header('Location: grid_clusterdb.php');
		exit;
	}

	debug_log_clear('grid_admin');

	if ((get_request_var('drp_action') == '8' || get_request_var('drp_action') == '9') && isset_request_var('selected_items')) {
		$pre_message=get_request_var('message');
		if(read_config_option('grid_management_clusters') == 'on'){
			form_input_validate(trim(get_request_var('message')), 'message', '', false, 'error_mandatory_input_field');
			set_request_var('message', str_replace(' ', '\ ', get_request_var('message')));
		}else if(read_config_option('grid_management_clusters') != 'on' && isset_request_var('message')){
			set_request_var('message', str_replace(' ', '\ ', trim(get_request_var('message'))));
		}
	}

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items') && !isset($_SESSION['sess_error_fields']['message'])) {
		if (isset_request_var('message') && trim(get_request_var('message')) != ''){
			$message = "'" . trim(get_request_var('message')) . "' - by RTM User '" . get_username($_SESSION['sess_user_id']) . "'";
		}else{
			$message = '';
		}

		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);

		if ($selected_items_whole != false) {
		// if action = lim/res/mbd startup, use root user
		if (get_request_var('drp_action') == '1' || get_request_var('drp_action') == '4' || get_request_var('drp_action') == '7') {
			$json_return_format = sorting_json_format($selected_items_whole, $message, $action_level, get_request_var('password'), 'root');
		} else {
			$json_return_format = sorting_json_format($selected_items_whole, $message, $action_level, get_request_var('password'), get_request_var('admin_user'));
		}
		//print_r($json_return_format);
		}

		switch(get_request_var('drp_action')){
			case '1':
				$cluster_action = 'limstartup';
				break;
			case '2':
				$cluster_action = 'limrestart';
				break;
			case '3':
				$cluster_action = 'limshutdown';
				break;
			case '4':
				$cluster_action = 'resstartup';
				break;
			case '5':
				$cluster_action = 'resrestart';
				break;
			case '6':
				$cluster_action = 'resshutdown';
				break;
			case '7':
				$cluster_action = 'mbdstartup';
				break;
			case '8':
				$cluster_action = 'mbdrestart';
				break;
			case '9':
				$cluster_action = 'mbdshutdown';
				break;
			case '10':
				$cluster_action = 'limreconfig';
				break;
			case '11':
				$cluster_action = 'mbdreconfig';
				break;
		}
		$advocate_key = session_auth();

		$json_cluster_info = array (
			'key'    => $advocate_key,
			'action' => $cluster_action,
			'target' => $json_return_format,
		);

		$output = json_encode($json_cluster_info);

		$curl_output = exec_curl($action_level, $output); //pass to advocate for processing

		if ($curl_output['http_code'] == 400) {
			raise_message(134);
		} else if ($curl_output['http_code'] == 500) {
			raise_message(135);
		} else {
			if ($curl_output['http_code'] == 200){
				$log_action = $grid_cluster_control_actions[get_request_var('drp_action')];
				$json_output = json_decode($output);
				$username_log = get_username($_SESSION['sess_user_id']);
				foreach ($json_output->target as $target) {
					if (get_request_var('drp_action') == "8" || get_request_var('drp_action') == "9") {
						cacti_log("Cluster '{$target->name}', {$log_action} by '{$username_log}', comment: '{$pre_message}'.", false, 'LSFCONTROL');
					} else {
						cacti_log("Cluster '{$target->name}', {$log_action} by '{$username_log}'.", false, 'LSFCONTROL');
					}
				}
			} else {
				raise_message(136);
			}
		}

		$content_response = $curl_output['content']; //return response from advocate in json format

		//print_r($content_response);
		$json_decode_content_response = json_decode($content_response,true);

		$rsp_content = $json_decode_content_response['rsp'];

		//cacti_log("rsp_content=". print_r($rsp_content, true));
		if(is_array($rsp_content) && count($rsp_content) >0){
		for ($k=0;$k<count($rsp_content);$k++) {
			$key_sort[$k] = $rsp_content[$k]['clusterid'];
		}
		//print_r($rsp_content);
		asort($key_sort);

		foreach( $key_sort as $key => $val){
			foreach ($rsp_content as $key_rsp_content => $value) {
				if ($key_rsp_content == $key){
					if ($value['status_code'] == 0){
						$return_status = 'OK';
						$count_ok ++;
					}else{
						$count_fail ++;
						$return_status = 'Failed. Status Code : '. $value['status_code'] ;
					}
					$msg = html_escape($value['status_message']);
					debug_log_insert('grid_admin', 'Status:'.$return_status.' - Cluster ID:'.$value['clusterid'].'-  Cluster Message: '.$msg);
				}
			}
		}
		}
		header('Location: grid_clusterdb.php');
		exit;
	}

	/* setup some variables */
	$cluster_list = ''; $i = 0;
	if(isset_request_var('selected_items') && isset($_SESSION['sess_error_fields']['message'])){
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		if ($selected_items_whole != false) {
		foreach ($selected_items_whole as $selected_item){
			$cluster_whole_array[$i] = $selected_item;
			$cluster_details = explode(':',$selected_item);

			input_validate_input_number($cluster_details[1]);

			$cluster_array[$i] = $cluster_details[1];
			$cluster_whole_array[$i] = $cluster_details[0] . ':' . $cluster_details[1];

			$i++;
		}
		}
	}else{
		/* loop through each of the clusters selected on the previous page and get more info about them */
		foreach ($_POST as $var => $val) {
			if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number($matches[1]);
				/* ==================================================== */

				$cluster_info = db_fetch_cell_prepared('SELECT clustername
					FROM grid_clusters
					WHERE clusterid = ?',
					array($matches[1]));

				$cluster_list .= '<li>' . __('Cluster Name: ', 'grid') . html_escape($cluster_info) . '</li>';
				$cluster_array[$i] = $matches[1];
				$cluster_whole_array[$i] = $cluster_info . ':' . $cluster_array[$i];
				$i++;
			}
		}
	}

	switch(get_request_var('drp_action')){
		case '1':
			$header_message = __('LIM Startup', 'grid');
			$question = __('Are you sure you want to startup LIM on the following cluster(s)?', 'grid');
			break;
		case '2':
			$header_message = __('LIM Restart', 'grid');
			$question = __('Are you sure you want to restart LIM on the following cluster(s)?', 'grid');
			break;
		case '3':
			$header_message = __('LIM Shutdown', 'grid');
			$question = __('Are you sure you want to shutdown LIM on the following cluster(s)?', 'grid');
			break;
		case '4':
			$header_message = __('RES Startup', 'grid');
			$question = __('Are you sure you want to startup RES on the following cluster(s)?', 'grid');
			break;
		case '5':
			$header_message = __('RES Restart', 'grid');
			$question = __('Are you sure you want to restart RES on the following cluster(s)?', 'grid');
			break;
		case '6':
			$header_message = __('RES Shutdown', 'grid');
			$question = __('Are you sure you want to shutdown RES on the following cluster(s)?', 'grid');
			break;
		case '7':
			$header_message = __('MBD Startup', 'grid');
			$question = __('Are you sure you want to startup MBD on the following cluster(s)?', 'grid');
			break;
		case '8':
			$header_message = __('MBD Restart', 'grid');
			$question = __('Are you sure you want to restart MBD on the following cluster(s)?', 'grid');
			break;
		case '9':
			$header_message = __('MBD Shutdown', 'grid');
			$question = __('Are you sure you want to shutdown MBD on the following cluster(s)?', 'grid');
			break;
		case '10':
			$header_message = __('Lsadmin Reconfig', 'grid');
			$question = __('Are you sure you want to reconfigure LIM on the following cluster(s)?', 'grid');
			break;
		case '11':
			$header_message = __('Badmin Reconfig', 'grid');
			$question = __('Are you sure you want to reconfigure MBATCHD on the following cluster(s)?', 'grid');
			break;
	}

	general_header();

	form_start('grid_clusterdb.php');

	if ((get_request_var('drp_action') == '8' || get_request_var('drp_action') == '9') && isset($cluster_array)) {
		html_start_box($header_message, '60%', '', '3', 'center', '');

		print "<tr class='odd'>
			<td colspan='2' class='textArea'>
				<p>$question</p>
				<div class='itemslist'><ul>$cluster_list</ul></div>";
		print "</td></tr>";

		print "<tr class='even'>
			<td class='textArea'>";

		if (read_config_option('grid_management_clusters') != 'on'){
			print  __('Comments that are appended to LSF with the -C option.  Leave BLANK if no comment is required.', 'grid');
		} else {
			print __('Comments that are appended to LSF with the -C option.', 'grid');
		}

		$username = get_username($_SESSION['sess_user_id']);

		print '<br><br>' . __('&lt;RTM &lt; %s &gt;&gt; will be appended after your comments', $username, 'grid') . '</td>';

		print "<td class='textArea'><input ";

		if(isset($_SESSION['sess_error_fields']['message'])){
			print "class='txtErrorTextBox'";
			unset($_SESSION['sess_error_fields']['message']);
		}

		print "type='text' name='message' col='255' size='60' maxlength='512'></td></tr>";

		foreach ($cluster_array as $clusterarray){
			$get_cluster_key = db_fetch_row_prepared("SELECT username, clustername, communication, privatekey_path, lsf_admins, lsf_krb_auth
				FROM grid_clusters
				WHERE clusterid= ?
				ORDER by clustername ASC", array($clusterarray));

			if ($get_cluster_key['username'] == '') {
				$admins = trim($get_cluster_key['lsf_admins']);
				$admin_pre = explode(' ', $admins);
				if (substr_count($admin_pre[0], "\\")) {
					$admin_pre = explode("\\",$admin_pre[0]);
					$admin_user = $admin_pre[1];
				}else{
					$admin_user = $admin_pre[0];
				}
			}else{
				$admin_user = $get_cluster_key['username'];
			}

			if (($get_cluster_key['communication'] == 'ssh') && ($get_cluster_key['privatekey_path'] == '')){
				print "<tr class='odd'>
					<td>" . __esc('Enter password for the LSF Administrator (%s) of cluster %s before you proceed', $admin_user, $get_cluster_key['clustername'], 'grid') . "</td>
					<td>
						<input type='password' name='password[]' col='30' size='30'>
					</td>
				</tr>";
			}else{
				print "<input type='hidden' name='password[]' value=''>";
			}
			print "<input type='hidden' name='admin_user' value='" . html_escape($admin_user) . "'>";
		}

		$save_html = "<input type='submit' value='" . __('Yes', 'grid') . "' alt=''>";
		$button_false = __('No', 'grid');
	} elseif (!isset($cluster_array)) {
		html_start_box($header_message, '60%', '', '3', 'center', '');
		raise_message(40);
		header('Location: grid_clusterdb.php?header=false');
		exit;
	}else{
		html_start_box($header_message, '60%', '', '3', 'center', '');
		print "<tr class='odd'>
			<td colspan='2' class='textArea'>
				<p>$question</p>
				<div class='itemslist'><ul>$cluster_list</ul></div>";
		print "</td></tr>\n";

		foreach ($cluster_array as $clusterarray){
			$get_cluster_key = db_fetch_row_prepared("SELECT username, clustername, communication, privatekey_path, lsf_admins
				FROM grid_clusters
				WHERE clusterid= ?
				ORDER by clustername ASC", array($clusterarray));

			if ($get_cluster_key['username'] == '') {
				$admins = trim($get_cluster_key['lsf_admins']);
				$admin_pre = explode(' ', $admins);
				if (substr_count($admin_pre[0], "\\")) {
					$admin_pre = explode("\\",$admin_pre[0]);
					$admin_user = $admin_pre[1];
				}else{
					$admin_user = $admin_pre[0];
				}
			}else{
				$admin_user = $get_cluster_key['username'];
			}

			if (($get_cluster_key['communication'] == 'ssh') && ($get_cluster_key['privatekey_path']=="")){
				switch (get_request_var('drp_action')) {
				case '1':
				case '4':
				case '7':
					print "<tr class='even'>
						<td>" . __('Enter password for the <b>ROOT</b> user of cluster %s before you proceed.', html_escape($get_cluster_key['clustername']), 'grid') . "</td><td>
						<input type=password name='password[]' col=30 size=30>
						</td></tr>";
					break;

				default:
					print "<tr>
						<td>" . __esc('Enter password for the LSF Administrator (%s) of cluster %s before you proceed.', $admin_user, $clusterarray, 'grid') . "</td><td>
						<input type='password' name='password[]' col=30 size=30>
						<input type='hidden' name='admin_user' value='" . html_escape($admin_user) . "'>
						</td></tr>";
					break;
				}
			}else{
				print "<input type='hidden' name='password[]' value=''>";
			}
		}

		$save_html = "<input type='submit' value='" . __('Yes', 'grid') . "' alt=''>";
		$button_false = __('No', 'grid');
	}

	print " <tr>
		<td colspan='2' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='command' value=''>
			<input type='hidden' name='selected_items' value='" . (isset($cluster_array) ? serialize($cluster_array) : '') . "'>
			<input type='hidden' name='selected_items_whole' value='".(isset($cluster_whole_array) ? serialize($cluster_whole_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . $button_false."' alt='' onClick='cactiReturnTo();'></a>
			$save_html
		</td>
		</tr>";

	form_end();

	bottom_footer();
}

function clusterDashboardFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid_view_clusterdb' action='grid_clusterdb.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>All</option>
							<?php
							$clusters = db_fetch_assoc('SELECT * FROM grid_clusters ORDER BY clustername');
							if (cacti_sizeof($clusters) > 0) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Limit', 'grid');?>
					</td>
					<td>
						<select id='limit'>
							<option value='-1' <?php if (get_request_var('limit') == '-1') print ' selected';?>>All</option>
							<option value='5' <?php if (get_request_var('limit') == '5') print ' selected';?>>5 Records</option>
							<option value='10' <?php if (get_request_var('limit') == '10') print ' selected';?>>10 Records</option>
							<option value='20' <?php if (get_request_var('limit') == '20') print ' selected';?>>20 Records</option>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'grid');?>
					</td>
					<td>
						<select id='refresh'>
							<?php
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							foreach($grid_refresh_interval as $key => $value) {
								if ($key >= $max_refresh) {
									print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='button' id='go' value='Go'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear'>
					</td>
					<td>
						<input type='button' id='save' value='Save'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<?php api_plugin_hook_function('filter_views','grid_clusterdb', 'applyClusterdbFilterChange', 'form_grid_view_clusterdb'); ?>
					<?php api_plugin_hook_function('filter_graphs','grid_clusterdb', 'applyClusterdbFilterChange', 'form_grid_view_clusterdb'); ?>
				</tr>
			</table>
			<input type='hidden' id='gorder' value='<?php echo get_views_setting('grid_clusterdb', 'gorder', 'div_graph_limstat,div_graph_batchstat,div_graph_gridstat,div_graph_memavastat');?>'>
			<input type='hidden' id='vorder' value='<?php echo get_views_setting('grid_clusterdb', 'vorder', 'div_view_summary,div_view_status,div_view_master,div_view_tput,div_view_perfmon,div_view_benchmark_exceptional_jobs');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_clusters() {
	global $title, $refresh, $grid_cluster_control_actions;
	global $grid_refresh_interval, $config;

	process_request_vars();

	general_header();

	$debug_log = nl2br(debug_log_return('grid_admin'));

	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<div id ='action_message'>
		<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>
			<tr>
				<td style='padding: 3px; font-family: monospace;font-size:10pt;'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<input type='hidden' id='display_action_msg' value='1'><br>
		</div>
	<?php
	}

	/* views div */
	echo "<div style='display:table;width:100%;'>";
	echo "<div id='left_column' style='display:table-cell;position:relative;top:0px;overflow:auto;float:left;width:" . (one_column_display_type_clusterdb() ? '100%':'60%') . ";'>";

	html_start_box(__('Cluster Filter', 'grid') . "&nbsp;<div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div></span><span id='message'></span>", '100%', '', '3', 'center', '');
	clusterDashboardFilter();
	html_end_box();

	//views div
	echo "<div id='div_views'>";
	echo '</div>';  	// end of div id ='div_views'
	echo '</div>';  	// end of div 'left column'

	//graphs div
	echo "<div id='right' style='top:0px;float:right;width:" . (one_column_display_type_clusterdb() ? '100%':'39%') . ";'>";

	html_start_box(__('Charts', 'grid'), '100%', '', '3', 'center', '');
	echo "<tr><td><table width='100%' align='center'><tr><td>
		<div id='ctabs'>
			<ul>
				<li><a id='general' href='#graphs'>" . __('General', 'grid') . "</a></li>
			</ul>
			<div id='graphs' class='graphs' align='center' style='padding:4px;'>
				<div class='graphs' id='div_graphs'>
					<div class='graphs' id='template'></div>
				</div>
			</div>
		</div>
	</td></tr></table></td></tr>\n";

	html_end_box(FALSE);

	echo '</div>';   //end of div 'right column'
	echo '</div>';

	?>
	<script type='text/javascript'>
	<!--
	var gorder = '';
	var vorder = '';
	var timer3;
	function applyClusterdbFilterChange() {
		//Workaround before change refresh design to non-setTimeOut
		var page = basename(location.pathname);
		if(page != 'grid_clusterdb.php'){
			clearTimeout(timer3);
			return;
		}

		if ( $('#display_action_msg').val() =='1' ) {
			$('#display_action_msg').val('0');
		} else {
			//remove action returning message after one refresh cycle.
			$('#action_message').remove();
		}

		if (typeof timer3 != 'undefined') {
			clearTimeout(timer3);
		}

		strURL = '&clusterid=' + $('#clusterid').val();
		strURL += '&limit=' + $('#limit').val();
		strURL += '&refresh=' + $('#refresh').val();

		// get views url
		var views = document.getElementsByName('view_filter')[0];

		var strURL_views = '';
		for (i = 0; i < views.length; i++){
			if(views.options[i].selected){
				strURL_views = strURL_views + '&' + views.options[i].value + '=true';
			} else {
				strURL_views = strURL_views + '&' + views.options[i].value + '=false';
			}
		}

		if (vorder == '') {
			vorder = $('#vorder').val();
		}

		strURL_views = '?action=ajaxrefresh_views' + strURL + strURL_views + '&vorder=' + vorder;

		$('#status').show();

		Pace.track(function() {
			$.get(strURL_views, function(data){
				$('#div_views').html(data);
				applySkin();
				$(".cactiSwitchConstraintWrapper").hide();
				$('#status').hide();
			});
		});

		// get graphs url
		var graphs = document.getElementsByName('graph_filter')[0];
		var strURL_graphs = '';
		var if_select_graphs = false;
		var checked_graph_divs = [];

		for(i = 0; i < graphs.length; i++){
			if(graphs.options[i].selected){
				if_select_graphs = true;
				checked_graph_divs.push('div_graph_' + graphs.options[i].value);
				strURL_graphs = strURL_graphs + '&' + graphs.options[i].value + '=true';
			} else {
				strURL_graphs = strURL_graphs + '&' + graphs.options[i].value + '=false';
			}
		}

		show_charts_div(if_select_graphs);
		if (if_select_graphs == true) {
			var fw = $('#right').innerWidth()-30;

			if (gorder == '') gorder = $('#gorder').val();
			var div_ids = gorder.split(',');

			$('#status_graphs').html("<img src='images/wait-loader.gif' align='absmiddle' border='0'>");
			prev_div = 'template';
			for (index = 0; index < div_ids.length; index++) {
				div_id = div_ids[index];
				if (div_id == 'template') continue;  //skip template id
				$('#'+div_id).remove();

				if ($.inArray(div_id, checked_graph_divs) == -1) continue;  //if div_id is not selected, no need to create fusionchart

				strURL_graphs1 = '?action=ajaxrefresh_' +div_id +strURL +strURL_graphs +'&div_width=' +fw ;

				fc_id = 'fc_'+div_id;
				if (FusionCharts(fc_id)) FusionCharts(fc_id).dispose();
				$('#template').clone().insertAfter('#'+prev_div);
				$('#'+prev_div).next('div').attr('id', div_id);
				prev_div = div_id;

				if (div_id == 'div_graph_memavastat') {
					chart_name = 'Column3D';
				} else {
					chart_name = 'Pie3D';
				}

				var myChart = new FusionCharts(chart_name, fc_id, fw, 300);

				Pace.track(function() {
					myChart.setXMLUrl(strURL_graphs1);
					myChart.render(div_id);
				});
			};
			$('#status_graphs').html('');
		} else {
			//if no graph is selected, need to set REQUEST variables limstat, batchstat, gridstat, memavastat and their corresponding session varialbes to false
			Pace.ignore(function() {
				$.get('?action=ajaxrefresh_div_graph_uncheckall' + strURL_graphs);
			});
		}
		timer3=setTimeout(function() { applyClusterdbFilterChange() }, $('#refresh').val()*1000);
	}

	function show_charts_div(if_select_graphs) {
		if (if_select_graphs == false) {
			$('#left_column').css('width', '100%');
			$('#right').hide();
		} else {
			$('#right').show();
			if ($('#right')[0].style.width == '100%') {      //single column mode
				$('#left_column').css('width', '100%');
			} else {                                         //two columns mode
				$('#left_column').css('width', '60%');
			}
		}
	}
/*
	function filterClear() {
		strURL = '?action=clear&clear=true';

		$.get(strURL, function(data){
			var deffilter = $.parseJSON(data);

			$('#clusterid').val(deffilter.clusterid);
			$('#limit').val(deffilter.limit);
			$('#refresh').val(deffilter.refresh);

			$('#view_filter').multiselect('uncheckAll');
			$('#graph_filter').multiselect('uncheckAll');

			var dimensions = [];
			if (deffilter.summary == 'true') dimensions.push('summary');
			if (deffilter.status == 'true')  dimensions.push('status');
			if (deffilter.master == 'true')  dimensions.push('master');
			if (deffilter.tput == 'true')  dimensions.push('tput');
			if (deffilter.perfmon == 'true')  dimensions.push('perfmon');
			if (deffilter.benchmark_exceptional_jobs == 'true')  dimensions.push('benchmark_exceptional_jobs');

			i = 0, size = dimensions.length;
			for(i; i < size; i++){
				$('#view_filter').multiselect("widget").find(":checkbox[value='"+dimensions[i]+"']").prop("checked","checked");
				$("#view_filter option[value='" + dimensions[i] + "']").prop("selected", 1);
				$("#view_filter").multiselect("refresh");
			}

			dimensions = [];
			if (deffilter.limstat == 'true') dimensions.push("limstat");
			if (deffilter.batchstat == 'true') dimensions.push("batchstat");
			if (deffilter.gridstat == 'true') dimensions.push("gridstat");
			if (deffilter.memavastat == 'true') dimensions.push("memavastat");

			i = 0, size = dimensions.length;
			for(i; i < size; i++){
				$("#graph_filter").multiselect("widget").find(":checkbox[value='"+dimensions[i]+"']").prop("checked","checked");
				$("#graph_filter option[value='" + dimensions[i] + "']").prop("selected", 1);
				$("#graph_filter").multiselect("refresh");
			}

			gorder = "";
			$('#gorder').val(deffilter.gorder);

			vorder = "";
			$('#vorder').val(deffilter.vorder);

			applyClusterdbFilterChange();
		});

	}
*/
	function filterSave() {
		strURL  = 'grid_clusterdb.php?header=false&action=ajaxsave';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&limit=' + $('#limit').val();

		var views = document.getElementsByName('view_filter')[0];
		for(i = 0; i < views.length; i++){
			if(views.options[i].selected){
				strURL = strURL + '&' + views.options[i].value + '=true';
			} else {
				strURL = strURL + '&' + views.options[i].value + '=false';
			}
		}

		var graphs = document.getElementsByName('graph_filter')[0];
		for(i = 0; i < graphs.length; i++){
			if(graphs.options[i].selected){
				strURL = strURL + '&' + graphs.options[i].value + '=true';
			} else {
				strURL = strURL + '&' + graphs.options[i].value + '=false';
			}
		}

		strURL = strURL + '&gorder=' +gorder;
		strURL = strURL + '&vorder=' +vorder;

		$.get(strURL, function() {
			$('#message').text('').show().html('<?php print __('Filter Settings Saved', 'grid');?>').delay(2000).fadeOut(1000);
		});
	}

	function clearFilter() {
		strURL = 'grid_clusterdb.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$.ajaxSetup({cache: false});
		//FusionCharts.setCurrentRenderer('javascript');
		$('#ctabs').tabs();
		applyClusterdbFilterChange();
		$('#div_graphs').sortable({
			axis: 'y',
			scroll: true,
			start: function () {
				if (typeof timer3 != 'undefined') {
					clearTimeout(timer3);
				}
			},
			stop: function() {
				var gorder_array1= $(this).sortable('toArray');
				var gorder_array = [];
				gorder = '';

				//remove empty array elements
				//remove suffix 'Div' in each div id, which is added in fusionchart render function.
				for (var i = 0; i < gorder_array1.length; i++) {
					if (gorder_array1[i] == 'template') continue;
					if 	(gorder_array1[i] > '') {
						gorder_array.push(gorder_array1[i].replace('Div',''));
					}
				}

				//append other unused elements;
				if ($.inArray('div_graph_limstat', gorder_array) == -1)  gorder_array.push('div_graph_limstat');
				if ($.inArray('div_graph_batchstat', gorder_array) == -1)  gorder_array.push('div_graph_batchstat');
				if ($.inArray('div_graph_gridstat', gorder_array) == -1)  gorder_array.push('div_graph_gridstat');
				if ($.inArray('div_graph_memavastat', gorder_array) == -1)  gorder_array.push('div_graph_memavastat');

				gorder = gorder_array.join(',');
				//alert(gorder);
				timer3 = setTimeout(function() { applyClusterdbFilterChange() }, $('#refresh').val()*1000);
			}
		});
		$('#div_views').sortable({
			axis: 'y',
			scroll: true,
			start: function () {
				if (typeof timer3 != 'undefined') {
					clearTimeout(timer3);
				}
			},
			stop: function() {
				var vorder_array1= $(this).sortable('toArray');
				var vorder_array = [];
				vorder = '';

				//remove empty array elements
				//remove suffix 'Div' in each div id, which is added in fusionchart render function.
				for (var i = 0; i < vorder_array1.length; i++) {
					if 	(vorder_array1[i] > '') {
						vorder_array.push(vorder_array1[i].replace('Div',''));
					}
				}

				//append other unused elements;
				if ($.inArray('div_view_summary', vorder_array) == -1)  vorder_array.push('div_view_summary');
				if ($.inArray('div_view_status', vorder_array) == -1)  vorder_array.push('div_view_status');
				if ($.inArray('div_view_master', vorder_array) == -1)  vorder_array.push('div_view_master');
				if ($.inArray('div_view_tput', vorder_array) == -1)  vorder_array.push('div_view_tput');
				if ($.inArray('div_view_perfmon', vorder_array) == -1)  vorder_array.push('div_view_perfmon');
				if ($.inArray('div_view_benchmark_exceptional_jobs', vorder_array) == -1)  vorder_array.push('div_view_benchmark_exceptional_jobs');

				vorder = vorder_array.join(',');
				//alert(vorder);
				timer3 = setTimeout(function() { applyClusterdbFilterChange() }, $('#refresh').val()*1000);
			}
		});

		$('#clusterid, #limit, #refresh').change(function() {
			applyClusterdbFilterChange();
		});

		$('#go').click(function() {
			applyClusterdbFilterChange();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#save').click(function() {
			filterSave();
		});
	});

	-->
	</script>
	<?php
	bottom_footer();

}

function grid_clusterdb_ajax_save() {
	$settings =
		'clusterid='  . get_request_var('clusterid')  . '|' .
		'limit='      . get_request_var('limit')      . '|' .
		'refresh='    . get_request_var('refresh')    . '|' .
		'summary='    . get_request_var('summary')    . '|' .
		'status='     . get_request_var('status')     . '|' .
		'master='     . get_request_var('master')     . '|' .
		'tput='       . get_request_var('tput')       . '|' .
		'perfmon='    . get_request_var('perfmon')    . '|' .
		'benchmark_exceptional_jobs='  . get_request_var('benchmark_exceptional_jobs')  . '|' .
		'limstat='    . get_request_var('limstat')    . '|' .
		'batchstat='  . get_request_var('batchstat')  . '|' .
		'gridstat='   . get_request_var('gridstat')   . '|' .
		'memavastat=' . get_request_var('memavastat') . '|' .
		'gorder='     . get_request_var('gorder')     . '|' .
		'vorder='     . get_request_var('vorder');

	set_grid_config_option('grid_clusterdb', $settings);
}

function process_request_vars() {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'limit' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_views_setting('grid_clusterdb', 'limit', '5')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'summary' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'summary', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'status', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'master' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'master', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'tput' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'tput', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'perfmon' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'perfmon', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'benchmark_exceptional_jobs' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'benchmark_exceptional_jobs', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'limstat' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'limstat', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'batchstat' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'batchstat', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'gridstat' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'gridstat', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'memavastat' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'memavastat', 'true'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'gorder' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_clusterdb', 'gorder', 'div_graph_limstat,div_graph_batchstat,div_graph_gridstat,div_graph_memavastat'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'vorder' => array(
			'filter' => FILTER_UNSAFE_RAW,
			'default' => get_views_setting('grid_clusterdb', 'vorder', 'div_view_summary,div_view_status,div_view_master,div_view_tput,div_view_perfmon,div_view_benchmark_exceptional_jobs'),
			//'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
	);

	validate_store_request_vars($filters, 'sess_grid_view_clusterdb');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */
}

?>
