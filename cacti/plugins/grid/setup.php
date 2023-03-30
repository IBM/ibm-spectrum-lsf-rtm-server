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

function plugin_grid_install() {
	include_once(dirname(__FILE__) . '/../../lib/rtm_plugins.php');

	api_plugin_register_hook('grid', 'top_header_tabs', 'grid_show_tab', 'setup.php');
	api_plugin_register_hook('grid', 'rtm_landing_page', 'grid_rtm_landing_page', 'setup.php');
	api_plugin_register_hook('grid', 'console_after', 'grid_console_after', 'setup.php');
	api_plugin_register_hook('grid', 'top_graph_header_tabs', 'grid_show_tab', 'setup.php');
	api_plugin_register_hook('grid', 'page_head', 'grid_page_head', 'setup.php');

	api_plugin_register_hook('grid', 'config_form', 'grid_config_form', 'setup.php');
	api_plugin_register_hook('grid', 'api_device_save', 'grid_api_device_save', 'setup.php');
	api_plugin_register_hook('grid', 'copy_user', 'grid_copy_user', 'setup.php');
	api_plugin_register_hook('grid', 'user_remove', 'grid_user_remove', 'setup.php');

	api_plugin_register_hook('grid', 'config_arrays', 'grid_config_arrays', 'setup.php');
	api_plugin_register_hook('grid', 'draw_navigation_text', 'grid_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('grid', 'config_settings', 'grid_config_settings', 'setup.php');
	api_plugin_register_hook('grid', 'login_options_navigate', 'grid_login_navigate', 'setup.php');
	api_plugin_register_hook('grid', 'device_filter_start', 'grid_device_filter_start', 'setup.php');
	api_plugin_register_hook('grid', 'device_sql_where', 'grid_device_sql_where', 'setup.php');
	api_plugin_register_hook('grid', 'device_display_text', 'grid_device_display_text', 'setup.php');
	api_plugin_register_hook('grid', 'device_table_replace', 'grid_device_table_replace', 'setup.php');

	/* allow host substitution to accound for clusterid value */
	api_plugin_register_hook('grid', 'valid_host_fields', 'grid_valid_host_fields', 'setup.php');
	api_plugin_register_hook('grid', 'substitute_host_data', 'grid_substitute_host_data', 'setup.php');

	/* control grid settings at the user level */
	api_plugin_register_hook('grid', 'user_admin_run_action', 'grid_user_settings_edit', 'setup.php');
	api_plugin_register_hook('grid', 'user_admin_action', 'grid_user_edit', 'setup.php');
	api_plugin_register_hook('grid', 'user_admin_tab', 'grid_user_edit_show_tab', 'setup.php');
	api_plugin_register_hook('grid', 'user_admin_user_save', 'grid_user_save', 'setup.php');
	api_plugin_register_hook('grid', 'user_admin_setup_sql_save', 'grid_user_setup_sql_save', 'setup.php');

	/* add graph button that allows users to zoom to running jobs */
	api_plugin_register_hook('grid', 'graph_buttons', 'grid_graph_buttons', 'setup.php');
	api_plugin_register_hook('grid', 'graph_buttons_thumbnails', 'grid_graph_buttons', 'setup.php');

	api_plugin_register_hook('grid', 'poller_top', 'grid_poller_top', 'setup.php');
	api_plugin_register_hook('grid', 'poller_bottom', 'grid_poller_bottom', 'setup.php');

	api_plugin_register_hook('grid', 'config_insert', 'grid_config', 'setup.php');

	/* hooks to add dropdown to allow the assignment of a cluster resource */
	api_plugin_register_hook('grid', 'device_action_array', 'grid_device_action_array', 'setup.php');
	api_plugin_register_hook('grid', 'device_action_prepare', 'grid_device_action_prepare', 'setup.php');
	api_plugin_register_hook('grid', 'device_action_execute', 'grid_device_action_execute', 'setup.php');

	/* Hook to collect host level alarm from thold/syslog/gridalarm plugins */
	api_plugin_register_hook('grid', 'thold_update_hostsalarm', 'grid_thold_update_hostsalarm', 'setup.php');
	api_plugin_register_hook('grid', 'thold_reset_hostsalarm', 'grid_thold_reset_hostsalarm', 'setup.php');
	api_plugin_register_hook('grid', 'thold_delete_hostsalarm', 'grid_thold_delete_hostsalarm', 'setup.php');

	api_plugin_register_hook('grid', 'gridalarms_update_hostsalarm', 'grid_gridalarms_update_hostsalarm', 'setup.php');
	api_plugin_register_hook('grid', 'gridalarms_reset_hostsalarm', 'grid_gridalarms_reset_hostsalarm', 'setup.php');
	api_plugin_register_hook('grid', 'gridalarms_delete_hostsalarm', 'grid_gridalarms_delete_hostsalarm', 'setup.php');

	api_plugin_register_hook('grid', 'syslog_update_hostsalarm', 'grid_syslog_update_hostsalarm', 'setup.php');
	api_plugin_register_hook('grid', 'syslog_delete_hostsalarm', 'grid_syslog_delete_hostsalarm', 'setup.php');

	api_plugin_register_hook('grid', 'custom_version_info', 'grid_custom_version_info', 'setup.php');
	api_plugin_register_hook('grid', 'graphs_remove', 'grid_graphs_remove', 'setup.php');
	api_plugin_register_hook('grid', 'graphs_item_array', 'grid_graphs_item_array', 'setup.php');
	api_plugin_register_hook('grid', 'graph_edit_after', 'grid_graph_edit_after', 'setup.php');

	/* hooks to import/export ELIM template */
	api_plugin_register_hook('grid', 'resolve_dependencies', 'grid_elim_resolve_dependencies', 'setup.php');
	api_plugin_register_hook('grid', 'export_action', 'grid_elim_template_to_xml', 'setup.php');
	api_plugin_register_hook('grid', 'import_action', 'grid_elim_xml_to_template', 'setup.php');
	api_plugin_register_hook('grid', 'customize_graph', 'grid_elim_customize_graph', 'setup.php');
	api_plugin_register_hook('grid', 'customize_template_details', 'grid_elim_customize_template_details', 'setup.php');

	/*cluster dashboard: cluster multiselect views/graphs filters */
	api_plugin_register_hook('grid', 'filter_views', 'grid_filter_views', 'setup.php');
	api_plugin_register_hook('grid', 'filter_graphs', 'grid_filter_graphs', 'setup.php');

	/*cluster dashboard: cluster summary/status/master/tput/perfmon/benchmark view*/
	api_plugin_register_hook('grid', 'view_cluster_summary', 'grid_view_cluster_summary', 'setup.php');
	api_plugin_register_hook('grid', 'view_cluster_status', 'grid_view_cluster_status', 'setup.php');
	api_plugin_register_hook('grid', 'view_cluster_master', 'grid_view_cluster_master', 'setup.php');
	api_plugin_register_hook('grid', 'view_cluster_tput', 'grid_view_cluster_tput', 'setup.php');
	api_plugin_register_hook('grid', 'view_cluster_perfmon', 'grid_view_cluster_perfmon', 'setup.php');
	api_plugin_register_hook('grid', 'view_cluster_benchmark_exceptional_jobs', 'grid_view_cluster_benchmark_exceptional_jobs', 'setup.php');

	/*cluster dashboard: cluster lim/batch/grid status graph*/
	api_plugin_register_hook('grid', 'graph_cluster_limstat', 'grid_graph_cluster_limstat', 'setup.php');
	api_plugin_register_hook('grid', 'graph_cluster_batchstat', 'grid_graph_cluster_batchstat', 'setup.php');
	api_plugin_register_hook('grid', 'graph_cluster_gridstat', 'grid_graph_cluster_gridstat', 'setup.php');
	api_plugin_register_hook('grid', 'graph_cluster_memavastat', 'grid_graph_cluster_memavastat', 'setup.php');

	/*Attach Grid Setting(User) to Profile Editor */
	api_plugin_register_hook('grid', 'auth_profile_tabs', 'grid_auth_profile_tabs', 'setup.php');
	api_plugin_register_hook('grid', 'auth_profile_update_data', 'grid_auth_profile_update_data', 'setup.php');
	api_plugin_register_hook('grid', 'auth_profile_run_action', 'grid_auth_profile_run_action', 'setup.php');
	api_plugin_register_hook('grid', 'auth_profile_save', 'grid_auth_profile_save', 'setup.php');

	/*set permissions */
	api_plugin_register_realm('grid', 'grid_shared.php,grid_bjobs.php,grid_bzen.php,grid_bhosts.php,grid_bhosts_closed.php,grid_bqueues.php,grid_lsload.php,grid_lshosts.php,grid_bhpart.php,grid_busers.php,grid_jobgraph.php,grid_jobgraphzoom.php,grid_ajax.php', 'General LSF Data', 1);
	api_plugin_register_realm('grid', 'grid_dailystats.php,grid_clients.php,grid_params.php,grid_elim_graph_templates_items.php,grid_download.php', 'LSF Admin Data', 1);
	api_plugin_register_realm('grid', 'grid_bhgroups.php,grid_bjgroup.php,grid_lsgload.php,grid_bmgroup.php', 'LSF Host Group Data', 1);
	api_plugin_register_realm('grid', 'grid_clusterdb.php,grid_summary.php,grid_bresourcespool.php,grid_lsf_config.php', 'LSF Advanced Options', 1);
	api_plugin_register_realm('grid', 'grid_barrays.php', 'LSF Job Array Data', 1);
	api_plugin_register_realm('grid', 'grid_bugroup.php', 'LSF User Group Data', 1);
	api_plugin_register_realm('grid', 'grid_projects.php', 'LSF Project Data', 1);
	api_plugin_register_realm('grid', 'grid_license_projects.php', 'LSF License Project Data', 1);
	api_plugin_register_realm('grid', 'grid_bapps.php', 'LSF Application Data', 1);
	api_plugin_register_realm('grid', 'grid_bjgroups.php', 'LSF Job Group Data', 1);
	api_plugin_register_realm('grid', 'grid_queue_distrib.php', 'LSF Queue Distribution', 0);
	api_plugin_register_realm('grid', 'grid_manage_hosts.php,grid_utilities.php,grid_elim_graphs.php,grid_elim_templates.php,grid_clusters.php,grid_settings_system.php,grid_pollers.php', 'LSF Administration', 1);
	api_plugin_register_realm('grid', 'LSF_Extended_History', 'LSF Extended History', 1);
	api_plugin_register_realm('grid', 'LSF_Host_Alert_Stop', 'LSF Host Alert Stop/Resume', 0);
	api_plugin_register_realm('grid', 'LSF_Cluster_Control', 'LSF Cluster Control', 0);

	plugin_rtm_remove_realm_data('grid', 1047);

	plugin_grid_install_tables();
}

function plugin_grid_install_tables() {
	global $config;

	include_once($config['base_path'] . '/plugins/grid/include/database.php');

	grid_create_tables();
}

function plugin_grid_uninstall() {
	return true;
}

function plugin_grid_upgrade() {
	/* do nothing database_upgrade.php script to handle this*/
	return false;
}

function grid_graph_edit_after($graph_local_id = NULL) {
	if (basename($_SERVER['SCRIPT_NAME']) == 'graphs.php') {
		if (!empty($graph_local_id)) {
			$is_elim_graph = db_fetch_cell_prepared("SELECT distinct local_graph_id
				from grid_elim_templates_graph_map
				where local_graph_id=?", array($graph_local_id));

			if (!empty($is_elim_graph)) {
				print "<script type='text/javascript'>\n";
				print "$().ready(function() {\n";
				print "$('#row_graph_template_id').parentsUntil('.cactiTable').parent().hide();\n";
				print "$('#row_graph_template_id').parentsUntil('.cactiTable').parent().prev('br').hide();\n";
				print "});\n";
				print "</script>\n";
			}
			return $graph_local_id;
		}
	}
	if (!empty($graph_local_id)) {
		return $graph_local_id;
	}
}

function plugin_grid_check_config() {
	grid_setup_table();
	return true;
}

function plugin_grid_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/grid/INFO', true);
	return $info['info'];
}

function get_grid_version() {
	$info = plugin_grid_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}

function grid_login_navigate($login_opt) {
	global $config;

	if ($login_opt == '4') {
		$grid_default = get_grid_default_main_screen();
		header('Location: ' . $config['url_path'] . $grid_default);
	}

	return $login_opt;
}

function grid_device_filter_start() {
	global $config;
	print "<td>";
	print __('Cluster');
	print "</td>";
	print "<td>";
	print "	<select id='clusterid' onChange='applyFilter()'>";
	print "		<option value='-1'"; if (get_request_var('clusterid') == '-1') {print " selected";} print ">"; print __('Any'); print "</option>";
	print "		<option value='0'"; if (get_request_var('clusterid') == '0') {print " selected";} print ">"; print __('None'); print "</option>";
	$clusters = grid_get_clusterlist();
	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			print "<option value='" . $cluster['clusterid'] . "'"; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . "</option>\n";
		}
	}
	print "	</select>";
	print "</td>";
}

function grid_device_sql_where($sql_where) {
	global $config;

	if (get_request_var('clusterid') == "-1") {
		/* Show all items */
	}elseif (get_request_var('clusterid') == "0") {
		$sql_where .= (strlen($sql_where) ? " and host.clusterid=0" : "where host.clusterid=0");
	}elseif (!isempty_request_var("clusterid")) {
		$sql_where .= (strlen($sql_where) ? " and host.clusterid=" . get_request_var('clusterid'): "where host.clusterid=" . get_request_var('clusterid'));
	}
	return $sql_where;
}

function grid_device_display_text($display_text) {
	global $config;

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'host.php?host_status=' + $('#host_status').val();
		strURL += '&host_template_id=' + $('#host_template_id').val();
		strURL += '&site_id=' + $('#site_id').val();
		strURL += '&poller_id=' + $('#poller_id').val();
		strURL += '&location=' + $('#location').val();
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&header=false';
		loadPageNoHeader(strURL);
	}
	</script>
	<?php
	$new_display_text = array();
	if (cacti_sizeof($display_text)) {
		foreach ($display_text as $key => $display) {
			$new_display_text[$key] = $display;
			if($key == 'id'){
				$new_display_text['clusterid'] = array(
					'display' => __('Cluster'),
					'align' => 'left',
					'sort'    => 'ASC'
				);
			}
		}
	}
	$display_text = $new_display_text;
	return $display_text;
}

function grid_device_table_replace($hosts) {
	global $config;

	if (cacti_sizeof($hosts)) {
		foreach ($hosts as $host) {
			if ($host['disabled'] == '' &&
				($host['status'] == HOST_RECOVERING || $host['status'] == HOST_UP) &&
				($host['availability_method'] != AVAIL_NONE && $host['availability_method'] != AVAIL_PING)) {
				$uptime    = get_uptime($host);
			} else {
				$uptime    = __('N/A');
			}

			$graphs_url      = $config['url_path'] . 'graphs.php?reset=1&host_id=' . $host['id'];
			$data_source_url = $config['url_path'] . 'data_sources.php?reset=1&host_id=' . $host['id'];

			form_alternate_row('line' . $host['id'], true);
			form_selectable_cell(filter_value($host['description'], get_request_var('filter'), 'host.php?action=edit&id=' . $host['id']), $host['id']);
			form_selectable_cell(filter_value($host['hostname'], get_request_var('filter')), $host['id']);
			form_selectable_cell(filter_value($host['id'], get_request_var('filter')), $host['id'], '', 'right');
			form_selectable_cell((($host['clusterid'] == 0) ? 'N/A' : db_fetch_cell_prepared("SELECT clustername FROM grid_clusters WHERE clusterid=?", array($host["clusterid"]))), $host['id']);
			form_selectable_cell('<a class="linkEditMain" href="' . $graphs_url . '">' . number_format_i18n($host['graphs'], '-1') . '</a>', $host['id'], '', 'right');
			form_selectable_cell('<a class="linkEditMain" href="' . $data_source_url . '">' . number_format_i18n($host['data_sources'], '-1') . '</a>', $host['id'], '', 'right');
			form_selectable_cell(get_colored_device_status(($host['disabled'] == 'on' ? true : false), $host['status']), $host['id'], '', 'center');
			form_selectable_cell(get_timeinstate($host), $host['id'], '', 'right');
			form_selectable_cell($uptime, $host['id'], '', 'right');
			form_selectable_cell(round($host['polling_time'],2), $host['id'], '', 'right');
			form_selectable_cell(round(($host['cur_time']), 2), $host['id'], '', 'right');
			form_selectable_cell(round(($host['avg_time']), 2), $host['id'], '', 'right');
			form_selectable_cell(round($host['availability'], 2) . ' %', $host['id'], '', 'right');
			form_checkbox_cell($host['description'], $host['id']);
			form_end_row();
		}
	}
	return $hosts;
}
function grid_copy_user($ids) {
	$settings_grid = db_fetch_assoc_prepared('SELECT *
		FROM grid_settings
		WHERE user_id = ?',
		array($ids['template_id']));

	if (cacti_sizeof($settings_grid)) {
		foreach ($settings_grid as $row) {
			$row['user_id'] = $ids['new_id'];
			sql_save($row, 'grid_settings', array('user_id', 'name', 'value'), false);
		}
	}
	return $ids;
}

function grid_user_remove($user_id) {
	db_execute_prepared("DELETE FROM grid_settings WHERE user_id=?", array($user_id));
	return $user_id;
}

function grid_device_action_execute($action) {
	global $fields_host_edit;

	if ($action == 'plugin_grid') {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		for ($i=0;($i<count($selected_items));$i++) {
			/* ================= input validation ================= */
			input_validate_input_number($selected_items[$i]);
			/* ==================================================== */

			reset($fields_host_edit);
			foreach ($fields_host_edit as $field_name => $field_array) {
				if (isset_request_var("t_$field_name")) {
					db_execute_prepared("UPDATE host SET $field_name=? WHERE id=?", array(get_request_var($field_name), $selected_items[$i]));
				}
			}

			push_out_host($selected_items[$i]);
		}
		}
	} elseif ($action == 'plugin_grid_monitor') {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		for ($i=0;($i<count($selected_items));$i++) {
			/* ================= input validation ================= */
			input_validate_input_number($selected_items[$i]);
			/* ==================================================== */

			db_execute_prepared("UPDATE host SET monitor='on' WHERE id=?", array($selected_items[$i]));
		}
		}
	} elseif ($action == 'plugin_grid_unmonitor') {
		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
		for ($i=0;($i<count($selected_items));$i++) {
			/* ================= input validation ================= */
			input_validate_input_number($selected_items[$i]);
			/* ==================================================== */

			db_execute_prepared('UPDATE host
				SET monitor=""
				WHERE id = ?',
				array($selected_items[$i]));
		}
		}
	}
	return $action;
}

function grid_device_action_prepare($save) {
	global $config, $fields_host_edit;

	if ($save['drp_action'] == 'plugin_grid') { /* cluster management */
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Press \'Continue\' to associate the following Devices with the Cluster.', 'grid')
				. '<ul>' . $save['host_list'] . "</ul>
			</td>
		</tr>\n";

		$form_array = array();
		foreach ($fields_host_edit as $field_name => $field_array) {
			if ('clusterid' == $field_name) {
				$form_array += array($field_name => $fields_host_edit[$field_name]);

				$form_array[$field_name]['value'] = '';
				$form_array[$field_name]['description'] = '';
				$form_array[$field_name]['form_id'] = 0;
				$form_array[$field_name]['sub_checkbox'] = array(
					'name' => 't_' . $field_name,
					'friendly_name' => __('Update this Field', 'grid'),
					'value' => ''
				);
			}
		}

		draw_edit_form(
			array(
				'config' => array('no_form_tag' => true),
				'fields' => $form_array
			)
		);
	} elseif ($save['drp_action'] == 'plugin_grid_monitor') {
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Press \'Continue\' to Monitor these Devices on the Cluster Host Dashboard', 'grid') .
				'<ul>' . $save['host_list'] . "</ul>
			</td>
		</tr>\n";
	} elseif ($save['drp_action'] == 'plugin_grid_unmonitor') {
		print "<tr>
			<td colspan='2' class='textArea'>
				<p>" . __('Press \'Continue\' to turn off Monitoring of these Hosts on the Cluster Host Dashboard', 'grid') .
				'<ul>' . $save['host_list'] . "</ul>
			</td>
		</tr>\n";
	}
	return $save;
}

function grid_device_action_array($action) {
	$action['plugin_grid']           = __('Associate with Cluster', 'grid');
	$action['plugin_grid_monitor']   = __('Monitor Hosts', 'grid');
	$action['plugin_grid_unmonitor'] = __('Stop Monitoring Hosts', 'grid');

	return $action;
}

function grid_user_setup_sql_save($save) {
	$save['grid_settings'] = form_input_validate((isset_request_var('grid_settings') ? get_request_var('grid_settings') : ''), 'grid_settings', '', true, 3);

	return $save;
}

function grid_user_save() {
	global $config, $grid_settings;

	if (isset_request_var('save_component_grid_settings')) {
		collect_pending_ignore_setting();
		append_host_elim_name();
		foreach ($grid_settings as $tab_short_name => $tab_fields) {
			foreach ($tab_fields as $field_name => $field_array) {
				if ($field_name == 'grid_summary_filter') {
					if (isset_request_var('grid_summary_filter')) {
						/* remove stale records */
						db_execute_prepared("DELETE FROM grid_settings
							WHERE user_id = ?
							AND name LIKE 'grid_filter%'",
							array($_SESSION['sess_user_id']));

						for ($i=0; ($i < count(get_request_var('grid_summary_filter'))); $i++) {
							$tmp_grid_summary_filter = get_request_var('grid_summary_filter');
							set_grid_config_option('grid_filter_' . $tmp_grid_summary_filter[$i], $tmp_grid_summary_filter);
						}
					}
				} elseif ((isset($field_array['items'])) && (is_array($field_array['items']))) {
					foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
						db_execute_prepared('REPLACE INTO grid_settings
							(user_id, name, value)
							VALUES (?, ?, ?)',
							array(
								(!empty($user_id) ? $user_id : get_request_var('id')),
								$sub_field_name,
								(isset_request_var($sub_field_name) ? get_request_var($sub_field_name) : '')
							)
						);
					}
				} else {
					if ($tab_short_name == 'pendignore') {
						$field_value = (isset_request_var($field_name) ? trim(get_request_var($field_name)) : '');
						save_pending_ignore_setting($field_name, $field_value);
					} else {
						db_execute_prepared('REPLACE INTO grid_settings
							(user_id,name,value)
							VALUES (?, ?, ?)',
							array(
								(!empty($user_id) ? $user_id : get_request_var('id')),
								$field_name,
								(isset_request_var($field_name) ? get_request_var($field_name) : '')
							)
						);
					}
				}
			}
		}

		/* reset local settings cache so the user sees the new settings */
		kill_session_var('sess_grid_config_array');
		raise_message(1);
	}
}

function grid_user_edit_show_tab() {
	global $config;

	?>
	<li class='subTab'><a class='tab<?php print ((get_request_var('tab') == 'grid_settings_edit') ? " selected" : "");?>' href='<?php print html_escape($config['url_path'] . 'user_admin.php?action=user_edit&tab=grid_settings_edit&id=' . get_request_var('id'));?>'>RTM Settings</a></li>
	<?php
}

function grid_user_edit($action) {
	if ($action == 'grid_settings_edit') {
		top_header();
		user_edit();
		bottom_footer();

		return false;
	} else {
		return $action;
	}
}

function grid_user_settings_edit($action) {
	global $tabs_grid, $grid_settings;

	/* ================= input validation ================= */
	get_filter_request_var('id');
	/* ==================================================== */

	if ($action == 'grid_settings_edit') {
		if (!isempty_request_var('id')) {
		    $user = db_fetch_row_prepared('SELECT * FROM user_auth WHERE id = ?', array(get_request_var('id')));
		    $header_label = __esc('[edit: %s]', $user['username'], 'grid');
		} else {
		    $header_label = __('[new]');
		}
		form_start('user_admin.php');
		html_start_box(__('RTM Settings %s LSF settings control how LSF Clusters are displayed for this user.', $header_label, 'grid'), '100%', '', '3', 'center', '');
		collect_pending_ignore_setting();
		append_host_elim_name();

		foreach ($grid_settings as $tab_short_name => $tab_fields) {
			?>
			<div>
				<div colspan='2' class='formHeader' style='padding: 3px;'>
					<?php print (isset($tabs_grid[$tab_short_name]) ? $tabs_grid[$tab_short_name]: '');?>
				</div>
			</div>
			<?php

			$form_array = array();

			foreach ($tab_fields as $field_name => $field_array) {
				$form_array += array($field_name => $tab_fields[$field_name]);

				if ((isset($field_array['items'])) && (is_array($field_array['items']))) {
					foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
						if (grid_config_value_exists($sub_field_name, get_request_var('id'))) {
							$form_array[$field_name]['items'][$sub_field_name]['form_id'] = 1;
						}

						$form_array[$field_name]['items'][$sub_field_name]['value'] =  db_fetch_cell_prepared("SELECT value
							FROM grid_settings
							WHERE name = ?
							AND user_id = ?",
							array($sub_field_name, get_request_var('id')));
					}
				} else {
					if (grid_config_value_exists($field_name, get_request_var('id'))) {
						$form_array[$field_name]['form_id'] = 1;
					}
					if ($tab_short_name == 'pendignore') {
						$form_array[$field_name]['value'] = query_pending_ignore_setting_value($field_name);
					} else {
						$form_array[$field_name]['value'] = db_fetch_cell_prepared('SELECT value
							FROM grid_settings
							WHERE name = ?
							AND user_id = ?',
							array($field_name, get_request_var('id')));
					}
				}
			}

			draw_edit_form(
				array(
					'config' => array('no_form_tag' => true),
					'fields' => $form_array
				)
			);
		}

		html_end_box();

		form_hidden_box('id', get_request_var('id'), '');
		form_hidden_box('save_component_grid_settings', '1', '');

		form_save_button("user_admin.php", "return");

		return false;
	} else {
		return $action;
	}
}

function grid_valid_host_fields($fields) {
	$fields = str_replace(")", "", str_replace("(", "", $fields));
	$fields = explode('|', $fields);
	$fields[] = 'clusterid';

	return '(' . implode('|', $fields) . ')';
}

function grid_substitute_host_data($array) {
	global $config;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$string          = $array['string'];
	$l_escape_string = $array['l_escape_string'];
	$r_escape_string = $array['r_escape_string'];
	$host_id         = $array['host_id'];

	$clusterid = db_fetch_cell_prepared('SELECT clusterid FROM host WHERE id = ?', array($host_id));
	if (!empty($clusterid)) {
		$string = str_replace($l_escape_string . 'clusterid' . $r_escape_string, $clusterid, $string);
		$string = substitute_cluster_data($string, $l_escape_string, $r_escape_string, $clusterid);
	}
	$array['string'] = $string;

	return $array;
}

function grid_config () {
	global $config, $grid_refresh_interval, $minimum_user_refresh_intervals, $grid_license_info;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$grid_license_info = array(
		'contact'               => 'www.ibm.com',
		'lsfpoller_expiry_days' => 30,
		'lsfpoller_num_users'   => 0,
		'lsfpoller_expiry_date' => '',
		'licpoller_expiry_days' => 30,
		'licpoller_num_users'   => 0,
		'licpoller_expiry_date' => '',
	);
}

function grid_poller_top () {
	global $grid_poller_start;

	$grid_poller_start = date('Y-m-d H:i:00');

	/* record the previous poller start time in the settings table for interval stats */
	db_execute_prepared("REPLACE INTO settings
		(name, value)
		VALUES ('grid_poller_start', ?)",
		array($grid_poller_start));
}

function grid_graphs_remove ($selected_items) {
	if (cacti_sizeof($selected_items)) {
		foreach($selected_items as $id => $local_graph_id) {
			if (empty($sql_items)) {
				$sql_items = $local_graph_id;
			} else {
				$sql_items = $sql_items . ',' . $local_graph_id;
			}
		}

		db_execute("DELETE FROM grid_elim_instance_graphs WHERE local_graph_id in ($sql_items)");
		db_execute("DELETE FROM grid_elim_templates_graph_map WHERE local_graph_id in ($sql_items)");
		db_execute("DELETE FROM grid_elim_templates_item_map WHERE local_graph_id in ($sql_items)");
	}

	return $selected_items;
}

function grid_graphs_item_array ($template_item_list) {
	//if (basename($_SERVER['SCRIPT_NAME']) == 'graphs.php') {
		$i = 0;
		if (cacti_sizeof($template_item_list) > 0) {
			foreach ($template_item_list as $item) {
				if (!empty($item['data_source_name'])) {
					/* get the 'resource_name' of the item's father */
					$resource_name = db_fetch_cell_prepared("SELECT distinct resource_name
						from grid_elim_templates_item
						join grid_elim_templates_item_map on grid_elim_templates_item_map.grid_elim_templates_item_id=grid_elim_templates_item.id
						where grid_elim_templates_item_map.graph_templates_item_id=?", array($item["id"]));
					if (!empty($resource_name)) {
							$template_item_list[$i]['data_source_name'] = $resource_name . ' - '. $template_item_list[$i]['data_source_name'];
					}
				}
				$i++;
			}
		}
	//}
	return $template_item_list;
}

function grid_poller_bottom () {
	global $config, $grid_poller_start;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/grid/poller_grid.php"';
	exec_background($command_string, $extra_args);

	$extra_args_add_host = '-q "'.$config['base_path'] . '/plugins/grid/add_host.php"';
	exec_background($command_string, $extra_args_add_host);

	$extra_args_add_summary = '-q '.$config['base_path'] . '/plugins/grid/add_data_query.php';
	exec_background($command_string, $extra_args_add_summary);

	$extra_args_add_elim = '-q '.$config['base_path'] . '/plugins/grid/add_elim_graph.php';
	exec_background($command_string, $extra_args_add_elim);
}

function grid_graph_buttons ($graph_elements = array()) {
	global $config, $timespan, $graph_timeshifts;

	if (get_request_var('action') == 'view') return;

	if (isset_request_var('graph_end') && strlen(get_request_var('graph_end'))) {
		$date1 = date('Y-m-d H:i:s', get_request_var('graph_start'));
		$date2 = date('Y-m-d H:i:s', get_request_var('graph_end'));
	} else {
		$date1 = $timespan['current_value_date1'];
		$date2 = $timespan['current_value_date2'];
	}

	/* determine disable status */
	$max_allowed_span = read_config_option('grid_max_job_zoom_interval', false);

	/* find out how much time we have */
	$now_time = time();
	$prior_time = strtotime('-' . $graph_timeshifts[$max_allowed_span]);

	$total_time = $now_time - $prior_time;

	if ((strtotime($date2) - strtotime($date1)) > $total_time) {
		$disable = true;
	} else {
		$disable = false;
	}

	if (isset($graph_elements[1]['local_graph_id'])) {
		$graph_local = db_fetch_row_prepared("SELECT * FROM graph_local WHERE id=?", array($graph_elements[1]['local_graph_id']));

		if (isset($graph_local['host_id'])) {
			$host = db_fetch_row_prepared("SELECT * FROM host WHERE id=?", array($graph_local['host_id']));

			if (cacti_sizeof($host)) {
				if ($host['clusterid'] > 0) {
					$grid_host = db_fetch_row_prepared("SELECT * FROM grid_load WHERE host=? AND clusterid=?", array($host['hostname'], $host['clusterid']));

					if (cacti_sizeof($grid_host)) {
						if ($disable) {
							print "<img src='" . $config['url_path'] . "plugins/grid/images/grid_jobs_disable.gif'  alt='' title='Jobs in Range disabled, time window too large' style='padding: 3px;'><br>";
						} else {
							print "<a href='" . html_escape($config["url_path"] . "plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=" . $host["clusterid"] . "&status=-1&exec_host=" . $host["hostname"] . "&hgroup=-1&date1=" . $date1 . "&date2=" . $date2) . "'><img src='" . $config['url_path'] . "plugins/grid/images/grid_jobs.gif'  alt='' title='Display Jobs in Range' style='padding: 3px;'></a><br>";
						}
					} else {
						$queue = db_fetch_row_prepared("SELECT * FROM grid_queues WHERE queuename=? AND clusterid=?", array($graph_local["snmp_index"], $host["clusterid"]));

						if (cacti_sizeof($queue)) {
							if ($disable) {
								print "<img src='" . $config['url_path'] . "plugins/grid/images/grid_jobs_disable.gif'  alt='' title='Jobs in Range disabled, time window too large' style='padding: 3px;'><br>";
							} else {
								print "<a href='" . html_escape($config["url_path"] . "plugins/grid/grid_bjobs.php?action=viewlist&reset=1&status=-1&clusterid=" . $host["clusterid"] . "&date1=" . $date1 . "&date2=" . $date2 . "&queue=" . $queue["queuename"]) . "'><img src='" . $config['url_path'] . "plugins/grid/images/grid_jobs.gif'  alt='' title='Display Jobs in Range' style='padding: 3px;'></a><br>";
							}
						}
					}
				}
			}
		}
	}
}

function grid_config_form () {
	global $fields_host_edit, $fields_user_user_edit_host, $fields_grid_cluster_edit, $grid_license_server_types;
	global $fields_grid_license_server_edit, $fields_grid_poller_edit, $grid_license_server_threads;
	global $fields_grid_signals_edit, $grid_signal_actions;
	global $fields_grid_cluster_group_edit, $fields_grid_cluster_group_member_edit;
	global $ha_refresh_interval, $grid_minor_refresh_interval, $grid_max_nonjob_runtimes;
	global $grid_minor_refresh_interval, $grid_major_refresh_interval, $grid_addhost_frequency, $add_graph_frequency;
	global $grid_base_batch_timeout, $grid_job_info_timeout, $grid_job_info_retries;
	global $config, $grid_max_job_runtimes, $fields_user_user_edit_host;
	global $tabs_grid_lsf_config, $grid_lsf_config_cluster_edit, $grid_lsf_config_shared_edit, $grid_lsf_config_queues_edit;
	global $grid_lsf_config_edit, $grid_lsf_config_hosts_edit, $grid_lsf_config_load;
	global $export_types, $lsf_versions;

	include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
	include_once($config['base_path'] . '/plugins/grid/include/grid_messages.php');

	/* add grid as the default tab */
	$fields_user_user_edit_host['login_opts']['items'][3] = array(
		'radio_value' => '4',
		'radio_caption' => 'Show the default grid screen.'
	);

	$fields_host_edit2 = $fields_host_edit;
	$fields_host_edit3 = array();
	foreach ($fields_host_edit2 as $f => $a) {
		$fields_host_edit3[$f] = $a;
		if ($f == 'host_template_id') {
			$fields_host_edit3['clusterid'] = array(
				'method' => 'drop_sql',
				'sql' => 'SELECT clusterid AS id, clustername AS name FROM grid_clusters ORDER BY clustername ASC',
				'friendly_name' => __('LSF Cluster Association', 'grid'),
				'description' => __('The Cluster that this host belongs to.', 'grid'),
				'none_value' => __('N/A', 'grid'),
				'value' => '|arg1:clusterid|',
				'default' => '0'
			);
		}

		if ($f == 'disabled') {
			$fields_host_edit3['monitor'] = array(
				'method' => 'checkbox',
				'friendly_name' => __('Monitor Host', 'grid'),
				'description' => __('Check this box to monitor this host in the LSF Summary.', 'grid'),
				'value' => '|arg1:monitor|',
				'default' => '',
				'form_id' => false
			);
		}
	}
	$fields_host_edit = $fields_host_edit3;

	/* add the grid settings restriction */
	$fields_user_user_edit_host2 = $fields_user_user_edit_host['grp1']['items'];
	$fields_user_user_edit_host3 = array();
	foreach ($fields_user_user_edit_host2 as $f => $a) {
		$fields_user_user_edit_host3[$f] = $a;
		if ($f == 'graph_settings') {
			$fields_user_user_edit_host3['grid_settings'] = array(
				'value' => '|arg1:grid_settings|',
				'friendly_name' => __('Allow this User to Keep Custom RTM Settings', 'grid'),
				'form_id' => '|arg1:id|',
				'default' => 'on'
			);
		}
	}
	$fields_user_user_edit_host['grp1']['items'] = $fields_user_user_edit_host3;

	if (get_current_page() == 'grid_clusters.php' && isset_request_var('clusterid') && get_filter_request_var('clusterid') > 0) {
		$clusterInfo = db_fetch_row_prepared('SELECT lsf_envdir, advanced_enabled
			FROM grid_clusters
			WHERE clusterid = ?',
			array(get_request_var('clusterid')));

		if (!cacti_sizeof($clusterInfo)) {
			$LSF_ENV_PATH = '';
			$advanced_enabled = '';
			$display = 'display:none';
		} else {
			if ($clusterInfo['lsf_envdir']) {
				$LSF_ENV_PATH = $clusterInfo['lsf_envdir'];
			} else {
				$LSF_ENV_PATH = '';
			}

			if ($clusterInfo['advanced_enabled'] == 'on') {
				$advanced_enabled = 'CHECKED';
				$display = '';
			} else {
				$advanced_enabled = '';
				$display = 'display:none';
			}
		}
	} else {
		$LSF_ENV_PATH = '';
		$advanced_enabled = '';
		$display = 'display:none';
	}

	$class_value = '';
	if (!empty($_SESSION['sess_error_fields']['lsf_envdir'])) {
		$class_value='txtErrorTextBox';
	}

	$custom_advance_mode = "<input id='advance_mode' name='advance_mode' type='checkbox' $advanced_enabled>
		<input type='text' class='".$class_value."' size='40' id='lsf_envdir' value='$LSF_ENV_PATH' name='lsf_envdir' disabled='true'>
		<span class='cactiTooltipHint fa fa-check-circle' style='padding:5px;font-size:16px;color:green;$display' title='File Found' id='lsf_envdir_check'></span>";

	/* default Grid Host template */
	$default_grid_host_template_id = db_fetch_cell("SELECT id
		FROM host_template
		WHERE hash='284bbabef4bb6e161af7e123c7c90969'");

	/* file: grid_clusters.php, action: edit */
	$fields_grid_cluster_edit = array(
		'cluster_config' => array(
			'spacer0' => array(
				'method' => 'spacer',
				'friendly_name' => __('General Cluster Settings', 'grid')
			),
			'clustername' => array(
				'method' => 'textbox',
				'friendly_name' => __('Cluster Name', 'grid'),
				'description' => __('Enter a name for this Cluster.', 'grid'),
				'value' => '|arg1:clustername|',
				'max_length' => '250'
			),
			'cluster_lim_hostname' => array(
				'method' => 'textbox',
				'friendly_name' => __('LSF Master LIM Hostname', 'grid'),
				'description' => __('To enable LSF Fail over monitoring, enter the list of LSF Master Hostname(s) separated by either a colon or a space.', 'grid'),
				'value' => '|arg1:lsf_master_hostname|',
				'max_length' => '250'
			),
			'cluster_ip' => array(
				'method' => 'hidden',
				'friendly_name' => __('LSF Master IP Address', 'grid'),
				'description' => __('To enable Fail over capability, please enter the list of Lsf Master IP Address(es) separated by either a colon or a space.<br><font color=\'red\'>Note: Omit this field if you do not wish to allow RTM Host to edit the /etc/hosts file</font>', 'grid'),
				'value' => '|arg1:ip|',
				'max_length' => '250',
				'default' => ''
			),
			'cluster_lim_port' => array(
				'method' => 'textbox',
				'friendly_name' => __('LSF Master LIM Port', 'grid'),
				'description' => __('Specify the LIM port on the master. LSF 7 or above default = 7869.', 'grid'),
				'value' => '|arg1:lim_port|',
				'max_length' => '5',
				'default' => ''
			),
			'poller_id' => array(
				'method' => 'drop_sql',
				'sql' => 'SELECT poller_id AS id, poller_name AS name FROM grid_pollers ORDER BY lsf_version ASC',
				'friendly_name' => __('LSF Poller', 'grid'),
				'description' => __('The LSF Poller to poll this service.', 'grid'),
				'value' => '|arg1:poller_id|',
				'default' => '9'
			),
			'lsf_ego' => array(
				'method' => 'drop_array',
				'friendly_name' => __('EGO Enabled', 'grid'),
				'description' => __('Specify whether EGO is enabled if you are using LSF 7 and above', 'grid'),
				'value' => '|arg1:lsf_ego|',
				'array' => array(
					'Y' => __('Yes', 'grid'),
					'N' => __('No', 'grid')
				),
				'default' => 'N'
			),
			'lsf_strict_checking' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Strict Checking', 'grid'),
				'description' => __('Specify whether strict checking of communications between LSF daemons is enabled if you are using LSF 7 and above', 'grid'),
				'value' => '|arg1:lsf_strict_checking|',
				'array' => array(
					'ENHANCED' => __('Enhanced', 'grid'),
					'Y' => __('Yes', 'grid'),
					'N' => __('No', 'grid')
				),
				'default' => 'N'
			),
			'lsf_krb_auth' => array(
				'friendly_name' => __('Kerberos Authentication Enabled', 'grid'),
				'description' => __('Specify whether Kerberos Authentication is enabled if you are using LSF 10.1.0.11 and above', 'grid'),
				'method' => 'checkbox',
				'default' => '',
				'value' => '|arg1:lsf_krb_auth|'
			),
			'cluster_timezone' => array(
				'method' => 'drop_sql',
				'sql' => 'SELECT Name AS name, Name AS id FROM `mysql`.`time_zone_name` ORDER BY name',
				'friendly_name' => __('Cluster Time zone', 'grid'),
				'description' => __('The time zone of this monitored cluster.', 'grid'),
				'none_value' => __('Default', 'grid'),
				'value' => '|arg1:cluster_timezone|',
				'default' => '0'
			),
			'advance_mode' => array(
				'friendly_name' => __('LSF conf directory(Advanced)', 'grid'),
				'description' => __('Allow modification of the LSF_ENVDIR path.<br>Do not modify if you are unsure!', 'grid'),
				'method' => 'custom',
				'value' => $custom_advance_mode
			),
		),
		'cluster_control' => array(
			'user_authentication' => array(
				'friendly_name' => __('User Authentication settings (Required for Cluster Operation)', 'grid'),
				'method' => 'spacer',
			),
			'username' => array(
				'method' => 'textbox',
				'friendly_name' => __('Primary LSF Administrator Username', 'grid'),
				'description' => __('Specify the user name of the Primary LSF Administrator for the LSF machine. <br/> <strong>Note: </strong>Since LSF 10.1.0 Fix Pack 10, operations as \'root\' are rejected if \'LSF_ROOT_USER\' is \'N\' or is not configured, with an exception if the RTM server host is included in the LSF_ADDON_HOSTS on the LSF master.', 'grid'),
				'value' => '|arg1:username|',
				'max_length' => '100',
				'default' => ''
			),
			(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'credential' : 'privatekey_path') => array(
				'method' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'textbox_password' : 'textbox'),
				'friendly_name' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? __('Primary LSF Administrator Password', 'grid') : __('Private Key Path (SSH)', 'grid')),
				'description' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? __('Specify the password of the Primary LSF Administrator for the LSF machine. Retype the password in the second field to confirm.', 'grid') : __('Specify the Path which contains the private key for SSH connection. Leave blank for RTM to ask for SSH password where applicable.', 'grid')),
				'value' => (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? '|arg1:credential|' : '|arg1:privatekey_path|'),
				'max_length' => '100',
				'default' => ''
			),
			'communication' => array(
				'method' => 'hidden',
				'friendly_name' => __('Remote Connection Type', 'grid'),
				'description' => __('Remote connection type for LSF Control / Configuration. For RSH, only username is required. Modify rhosts file in LSF master to include RTM host IP and root username.<br>For SSH, username and password/private key are required.<br>For WinRS, both RTM and LSF Master host need to have WinRS set up correctly.<br>Local does not use remote protocol, commands are executed using local shell.', 'grid'),
				'value' => '|arg1:communication|',
				'array' => $grid_protocol,
				'default' => (strtoupper(substr(PHP_OS, 0, 3))=== 'WIN' ? 'winrs' : 'ssh')
			),
		),
		'cluster_automation' => array(
			'add_host_frequency' => array(
				'friendly_name' => __('Add Host Setting', 'grid'),
				'method' => 'spacer',
			),
			'add_frequency' => array(
				'friendly_name' => __('Add Host Frequency', 'grid'),
				'description' => __('Choose how often to add host to the device list.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:add_frequency|',
				'default' => 2400,
				'array' => $grid_addhost_frequency,
			),
			'host_template_id' => array(
				'method' => 'drop_sql',
				'friendly_name' => __('Device Template', 'grid'),
				'description' => __('Choose what type of Device, Device Template this is. The Device Template will govern what kinds of data should be gathered from this type of Device.', 'grid'),
				'value' => '|arg1:host_template_id|',
				'default' => $default_grid_host_template_id,
				'sql' => 'select id,name from host_template order by name',
			),
			'add_graph_frequency' => array(
				'friendly_name' => __('Add Graph Frequency', 'grid'),
				'description' => __('Choose how often to check for new graphs to be added to the Device.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:add_graph_frequency|',
				'default' => 86400,
				'array' => $add_graph_frequency,
			),
		),
		'cluster_poller' => array(
			'qhl_timings' => array(
				'friendly_name' => __('Queue/Host/Load Collection Settings', 'grid'),
				'method' => 'spacer',
			),
			'collection_timing' => array(
				'friendly_name' => __('Collection Frequency', 'grid'),
				'description' => __('Choose how often collect host and queue information from the LSF cluster.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:collection_timing|',
				'default' => 'disabled',
				'array' => $grid_minor_refresh_interval,
			),
			'max_nonjob_runtime' => array(
				'friendly_name' => __('Max Allowed Runtime', 'grid'),
				'description' => __('The maximum time, in seconds, that the task of refreshing Queue/Host/Load information should be allowed to run.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:max_nonjob_runtime|',
				'default' => '60',
				'array' => $grid_max_nonjob_runtimes,
			),
			'job_timings_header' => array(
				'friendly_name' => __('Job Collection Settings', 'grid'),
				'method' => 'spacer',
			),
			'job_minor_timing' => array(
				'friendly_name' => __('Minor Collection Frequency', 'grid'),
				'description' => __('Choose how often job records for All jobs in the schedulers memory be updated.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:job_minor_timing|',
				'default' => '30',
				'array' => $grid_minor_refresh_interval,
			),
			'job_major_timing' => array(
				'friendly_name' => __('Major Collection Frequency', 'grid'),
				'description' => __('Choose how often reference information for Running job records should be updated. This scan will also update rusage, and job filter pull downs.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:job_major_timing|',
				'default' => '60',
				'array' => $grid_major_refresh_interval,
				),
			'max_job_runtime' => array(
				'friendly_name' => __('Max Allowed Runtime', 'grid'),
				'description' => __('The maximum time, in seconds, that the task of refreshing Job information should be allowed to run.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:max_job_runtime|',
				'default' => '60',
				'array' => $grid_max_job_runtimes,
			),
			'job_performance_header' => array(
				'friendly_name' => __('LSF PerfMon Settings', 'grid'),
				'method' => 'spacer',
			),
			'perfmon_run' => array(
				'friendly_name' => __('Enable LSF Perfmon Collection', 'grid'),
				'description' => __('When enabled, the Poller periodically checks for performance data. The time window is the time range between <strong>Minor Collection Frequency</strong> and <strong>Minor Collection Frequency</strong> combined with <strong>Badmin Perfmon Interval</strong>. The Poller runs on each Minor Collection cycle. <br/> <strong>Note: </strong>Since LSF 10.1.0 Fix Pack 10, the Poller is rejected if all LSF administrators are not valid on Poller host, and \'LSF_ROOT_USER\' is \'N\' or is not configured, with an exception if the RTM Poller host is included in the LSF_ADDON_HOSTS on the LSF master.', 'grid'),
				'method' => 'checkbox',
				'default' => '',
				'value' => '|arg1:perfmon_run|'
			),
			'perfmon_interval' => array(
				'friendly_name' => __('Badmin Perfmon Interval', 'grid'),
				'description' => __('Set the badmin perfmon interval to this setting automatically.', 'grid'),
				'method' => 'drop_array',
				'value' => '|arg1:perfmon_interval|',
				'default' => '0',
				'array' => array(
					'0'   => __('Don\'t Change',  'grid'),
					'60'  => __('%d Minute',  1,  'grid'),
					'120' => __('%d Minutes', 2,  'grid'),
					'300' => __('%d Minutes', 5,  'grid'),
					'600' => __('%d Minutes', 10, 'grid')
				)
			)
		),
		'cluster_idledetect' => array(
			'grididle_header' => array(
				'friendly_name' => __('Idle Job Detection', 'grid'),
				'method' => 'spacer',
			),
			'grididle_enabled' => array(
				'friendly_name' => __('Enable Idle Job Detection', 'grid'),
				'description' => __('If you want to search for Idle jobs, please check this checkbox.', 'grid'),
				'method' => 'checkbox',
				'value' => '|arg1:grididle_enabled|'
			),
			'grididle_notify' => array(
				'friendly_name' => __('E-Mail Notification Type', 'grid'),
				'description' => __('Choose the notification type to use relative to idled jobs.', 'grid'),
				'method' => 'drop_array',
				'default' => 0,
				'value' => '|arg1:grididle_notify|',
				'array' => array(
					0 => __('None', 'grid'),
					1 => __('Notify User', 'grid'),
					2 => __('Notify User and Admin', 'grid'),
					3 => __('Notify Admin', 'grid')
				)
			),
			'grididle_runtime' => array(
				'friendly_name' => __('Minimum Runtime', 'grid'),
				'description' => __('Jobs must run longer than this period of time to be eligible for detection', 'grid'),
				'method' => 'drop_array',
				'default' => '3600',
				'value' => '|arg1:grididle_runtime|',
				'array' => array(
					'300'   => __('%d Minutes',  5,  'grid'),
					'600'   => __('%d Minutes',  10,  'grid'),
					'900'   => __('%d Minutes',  15,  'grid'),
					'1200'   => __('%d Minutes',  20,  'grid'),
					'1500'   => __('%d Minutes',  25,  'grid'),
					'1800'   => __('%d Minutes',  30,  'grid'),
					'3600'   => __('%d Hour',  1,  'grid'),
					'7200'   => __('%d Hours', 2,  'grid'),
					'14400'  => __('%d Hours', 4,  'grid'),
					'28800'  => __('%d Hours', 8,  'grid'),
					'57600'  => __('%d Hours', 16, 'grid'),
					'86400'  => __('%d Day',   1,  'grid'),
					'172800' => __('%d Days',  2,  'grid'),
					'345600' => __('%d Day',   4,  'grid'),
					'604800' => __('%d Week',  1,  'grid'))
			),
			'grididle_window' => array(
				'friendly_name' => __('Floating Window', 'grid'),
				'description' => __('The window if time that will be evaluated to determine if a job is idle.  For example, in the case of a one hour window, the difference in cpu time from the beginning of the last hour till the present time will be the window.', 'grid'),
				'method' => 'drop_array',
				'default' => '3600',
				'value' => '|arg1:grididle_window|',
				'array' => array(
					'600'   => __('%d Minutes',  10,  'grid'),
					'900'   => __('%d Minutes',  15,  'grid'),
					'1200'   => __('%d Minutes',  20,  'grid'),
					'1500'   => __('%d Minutes',  25,  'grid'),
					'1800'   => __('%d Minutes',  30,  'grid'),
					'3600'   => __('%d Hour',  1,  'grid'),
					'7200'   => __('%d Hours', 2,  'grid'),
					'14400'  => __('%d Hours', 4,  'grid'),
					'28800'  => __('%d Hours', 8,  'grid'),
					'57600'  => __('%d Hours', 16, 'grid'),
					'86400'  => __('%d Day',   1,  'grid'),
					'172800' => __('%d Days',  2,  'grid'),
					'345600' => __('%d Day',   4,  'grid'),
					'604800' => __('%d Week',  1,  'grid'))
			),
			'grididle_cputime' => array(
				'friendly_name' => __('CPU Time Threshold', 'grid'),
				'description' => __('If the job has accumulated less than this amount of CPU time during the Floating Window.  It will be marked Idle.', 'grid'),
				'method' => 'textbox',
				'default' => '24',
				'value' => '|arg1:grididle_cputime|',
				'max_length' => '10',
				'size' => '10'
			),
			'grididle_jobtypes' => array(
				'friendly_name' => __('Include Job Types', 'grid'),
				'description' => __('The types of jobs to include in the assessment. If you select \'All Matching Regex\' and leave the Regex value empty, all Idle jobs will be included.', 'grid'),
				'method' => 'drop_array',
				'default' => 'all',
				'value' => '|arg1:grididle_jobtypes|',
				'array' => array(
					'all'       => __('All Matching Regex', 'grid'),
					'inter'     => __('Interactive and Regex', 'grid'),
					'excl'      => __('Exclusive and Regex', 'grid'),
					'interexcl' => __('Exclusive/Interactive and Regex', 'grid'))
			),
			'grididle_jobcommands' => array(
				'friendly_name' => __('Job Commands', 'grid'),
				'description' => __('A Regex list of commands to flag in addition to the job types.  An example would be \'(xterm|emacs)\' to also flag X-Terminals E-Macs Sessions.', 'grid'),
				'method' => 'textarea',
				'textarea_rows' => '3',
				'textarea_cols' => '80',
				'class' => 'textAreaNotes',
				'max_length' => '255',
				'default' => '',
				'value' => '|arg1:grididle_jobcommands|'
			),
			'grididle_exclude_queues' => array(
				'method' => 'textarea',
				'friendly_name' => __('Idles Jobs Exclude Queues', 'grid'),
				'description' => __('A list of queues delimited by \'|\' to exclude from idle jobs detection.', 'grid'),
				'textarea_rows' => '3',
				'textarea_cols' => '80',
				'value' => '|arg1:grididle_exclude_queues|',
				'default' => '',
				'max_length' => '1024'
			)
		),
		'cluster_advanced' => array(
			'host_tree_mapping' => array(
				'method' => 'spacer',
				'friendly_name' => __('Device/Tree Mapping', 'grid')
			),
			'cacti_host' => array(
				'method' => 'drop_sql',
				'sql' => "SELECT CONCAT_WS('',h.description,' (',h.hostname,')') AS name, h.id AS id FROM host h join host_template ht on h.host_template_id=ht.id and ht.hash='d8ff1374e732012338d9cd47b9da18d4' ORDER BY description;",
				'friendly_name' => __('Cacti Host Mapping', 'grid'),
				'description' => __('Each Cluster must also have a corresponding Cacti host in order to graph metrics for the Overall Cluster, Queues, Host Groups, User Groups, Resources, Host Models and Types, etc.', 'grid'),
				'none_value' => __('N/A', 'grid'),
				'value' => '|arg1:cacti_host|',
				'default' => '0'
			),
			'cacti_tree' => array(
				'method' => 'drop_sql',
				'sql' => 'SELECT name, id FROM graph_tree ORDER BY name',
				'friendly_name' => __('Cacti Tree', 'grid'),
				'description' => __('Each Cluster can be associated with a Cacti Tree.  If this is completed, you will receive a graph link on the Cluster Dashboard link that will direct you to the requested tree.', 'grid'),
				'none_value' => __('N/A', 'grid'),
				'value' => '|arg1:cacti_tree|',
				'default' => '0'
			),
			'grid_email' => array(
				'method' => 'spacer',
				'friendly_name' => __('Notification', 'grid')
			),
			'email_domain' => array(
				'method' => 'textbox',
				'friendly_name' => __('LSF Cluster Email Domain', 'grid'),
				'description' => __('LSF Cluster mail domain to use for event reporting.  If not set, no event emails will be sent to cluster users.', 'grid'),
				'value' => '|arg1:email_domain|',
				'max_length' => '40',
			),
			'email_admin' => array(
				'method' => 'textarea',
				'friendly_name' => __('Administrator\'s Email', 'grid'),
				'description' => __('Comma separated list of administrator email address(es) for event reporting', 'grid'),
				'value' => '|arg1:email_admin|',
				'class' => 'textAreaNotes',
				'textarea_rows' => '4',
				'textarea_cols' => '80'
			),
			'cluster_effic' => array(
				'method' => 'spacer',
				'friendly_name' => __('Job Efficiency Exclude Queues', 'grid')
			),
			'efficiency_queues' => array(
				'method' => 'textbox',
				'friendly_name' => __('Job Efficiency Exclude Queues', 'grid'),
				'description' => __('A list of queues delimited by \'|\' to exclude from overall cluster job efficiency calculations.  This can be helpful in cases where you don\'t want account for interactive queue efficiency in your overall efficiency numbers.', 'grid'),
				'value' => '|arg1:efficiency_queues|',
				'default' => '',
				'max_length' => '255'
			),
			'spacer_fairshare_host_selection' => array(
				'method' => 'spacer',
				'friendly_name' => __('Fairshare Tree Host Slot Selection Criteria', 'grid')
			),
			'exec_host_res_req' => array(
				'method' => 'textbox',
				'friendly_name' => __('Fairshare Tree Host Slot Selection Criteria', 'grid'),
				'description' => __('Specify host resources to determine which hosts are included when updating share slots in queue Fairshare tree. Here is an example to show supported syntax: res_num > 4000, res_str = \'lic1\', res_bool1 = 1, res_bool2 = 0, res_bool3, (res_bool4|res_bool5).The name value pairs are comma separated combined.<br>1. As shown above in res_num and res_str, Numeric/String resource variables defined in table grid_hosts_resources support:\'variable [ <= | >= | < | > | = ] value\', string value is enclosed in quotes<br>2. As shown above in res_bool1 to res_bool5, Boolean resource variables defined in table grid_hostresources support:\'variable or variable = 0 or variable = 1 or variableA|variableB|variableC|...\'<br>In this example, the hosts containing res_bool2 resource are excluded, and the hosts containing resource res_bool1 or res_bool3 or any of res_bool4, res_bool5 are included.', 'grid'),
				'value' => '|arg1:exec_host_res_req|',
				'default' => '',
				'max_length' => '512'
			),
			'spacer1' => array(
				'method' => 'spacer',
				'friendly_name' => __('Cluster Connection Timeout Settings', 'grid')
			),
			'lim_timeout' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Base Timeout', 'grid'),
				'description' => __('Enter the base system connection/receive timeout in seconds.', 'grid'),
				'value' => '|arg1:lim_timeout|',
				'default' => '10',
				'array' => $grid_base_batch_timeout,
			),
			'mbd_timeout' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Batch Timeout', 'grid'),
				'description' => __('Enter the batch system connection/receive timeout in seconds.', 'grid'),
				'value' => '|arg1:mbd_timeout|',
				'default' => '10',
				'array' => $grid_base_batch_timeout,
			),
			'mbd_job_timeout' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Batch Job Info Timeout', 'grid'),
				'description' => __('Enter the batch system timeout in minutes.', 'grid'),
				'value' => '|arg1:mbd_job_timeout|',
				'default' => '1',
				'array' => $grid_job_info_timeout,
			),
			'mbd_job_retries' => array(
				'method' => 'drop_array',
				'friendly_name' => __('Batch Job Info Retries', 'grid'),
				'description' => __('How many times do you want the batch system to be repolled for job information?', 'grid'),
				'value' => '|arg1:mbd_job_retries|',
				'default' => '1',
				'array' => $grid_job_info_retries,
			),
			'clusterid' => array(
				'method' => 'hidden_zero',
				'value' => '|arg1:clusterid|'
			),
			'save_component_cluster' => array(
				'method' => 'hidden',
				'value' => '1'
			)
		)
	);

	/* file: grid_pollers.php, action: edit */
	$fields_grid_poller_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('General Information', 'grid')
		),
		'poller_name' => array(
			'method' => 'textbox',
			'friendly_name' => __('LSF Poller Name', 'grid'),
			'description' => __('Enter a name for this LSF Poller.', 'grid'),
			'value' => '|arg1:poller_name|',
			'max_length' => '250'
		),
		'lsf_version' => array(
			'method' => 'drop_array',
			'friendly_name' => __('LSF Version', 'grid'),
			'description' => __('Specify the LSF version for this grid.', 'grid'),
			'value' => '|arg1:lsf_version|',
			'array' => $lsf_versions
		),
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => __('Important Paths', 'grid')
		),
		'remote' => array(
			'method' => 'checkbox',
			'friendly_name' => __('Remote Poller'),
			'description' => __('Enable Remote Poller.'),
			'value' => '|arg1:remote|'
		),
		'poller_lbindir' => array(
			'method' => 'dirpath',
			'friendly_name' => __('Local RTM Poller Bin Directory', 'grid'),
			'description' => __('Please enter the RTM Poller binary directory.  This binary directory must be located both on the web server and any remote Poller hosts.', 'grid'),
			'value' => '|arg1:poller_lbindir|',
			'max_length' => '255'
		),
		'spacer2' => array(
			'method' => 'spacer',
			'friendly_name' => __('Support Information', 'grid')
		),
		'poller_location' => array(
			'method' => 'textbox',
			'friendly_name' => __('Poller Location', 'grid'),
			'description' => __('Please enter the physical location for the Poller.', 'grid'),
			'value' => '|arg1:poller_location|',
			'max_length' => '255'
		),
		'poller_support_info' => array(
			'method' => 'textbox',
			'friendly_name' => __('Support Contacts', 'grid'),
			'description' => __('Please enter relevant contact information for the Poller.', 'grid'),
			'value' => '|arg1:poller_support_info|',
			'max_length' => '255'
		),
		'spacer3' => array(
			'method' => 'spacer',
			'friendly_name' => __('Job Collection Settings', 'grid')
		),
		'poller_max_insert_packet_size' => array(
			'friendly_name' => __('Max Insert SQL string length', 'grid'),
			'description' => __('The maximum length of one INSERT SQL string for job or any generated/intermediate string. Increase this option is benefit for job Poller performance if there is network latency between Poller host and database server. The default is the global setting of \'Max Insert SQL string length\', upper limit to 16MB. ', 'grid'),
			'method' => 'textbox',
			'value' => '|arg1:poller_max_insert_packet_size|',
			'max_length' => '20'
		),
		'poller_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:poller_id|'
		),
		'save_component_poller' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	/* file: grid_pollers.php, action: edit */
	$fields_grid_signals_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('General Signal Information', 'grid')
		),
		'hostType_match' => array(
			'method' => 'textbox',
			'friendly_name' => __('Host Type Match', 'grid'),
			'description' => __('Enter a regular expression match string for all matching host types.', 'grid'),
			'value' => '|arg1:hostType_match|',
			'max_length' => '25'
		),
		'signal' => array(
			'method' => 'textbox',
			'friendly_name' => __('Signal ID', 'grid'),
			'description' => __('Enter the integer value for the signal that can be experienced by this host.', 'grid'),
			'value' => '|arg1:signal|',
			'max_length' => '3'
		),
		'abbreviation' => array(
			'method' => 'textbox',
			'friendly_name' => __('Signal Macro Name', 'grid'),
			'description' => __('Enter the Macro Name or abbreviation for this signal.  For example, \'SIGINT\', \'SIGTERM\', etc.', 'grid'),
			'value' => '|arg1:abbreviation|',
			'max_length' => '20'
		),
		'action' => array(
			'friendly_name' => __('Default LSF Cluster', 'grid'),
			'description' => __('The default action when the signal is experienced.', 'grid'),
			'method' => 'drop_array',
			'array' => $grid_signal_actions,
			'none_value' => '1',
			'default' => '1'
		),
		'description' => array(
			'method' => 'textbox',
			'friendly_name' => __('Signal Description', 'grid'),
			'description' => __('Describe the signal so that users can understand the return what it means.', 'grid'),
			'value' => '|arg1:description|',
			'max_length' => '255'
		),
		'signal_id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:signal_id|'
		),
		'save_component_signal' => array(
			'method' => 'hidden',
			'value' => '1'
		)
	);

	$custom = '<input type="hidden" value="0" id="hiddenvalue" /><p><a href="#custom_value" onClick="addInput(\'\', \'\')">' . __('Add Advanced Attributes', 'grid') . '</a></p><div id="mydiv"></div>';

	$custom_host = '<table><tr><td><select id="multi_hosts" style="display:none" name="multi_hosts" size="10" multiple>';

	if (isset_request_var('config_id')) {
		$get_host_lists = array();

		if (isset_request_var('item_id')) {
			$get_host_lists = db_fetch_cell_prepared('SELECT value_attr
				FROM grid_lsf_config_item_attribute
				INNER JOIN grid_lsf_config_item
				ON (grid_lsf_config_item_attribute.item_id=grid_lsf_config_item.item_id
				AND grid_lsf_config_item.config_id=?)
				WHERE grid_lsf_config_item_attribute.key_attr like "HOST%"
				AND grid_lsf_config_item_attribute.item_id=?', array(get_request_var('config_id'), get_request_var('item_id')));

			if (isset($get_host_lists)) {
				if ($get_host_lists != 'all') {
					$get_host_lists = explode(' ', $get_host_lists);
				} else {
					$new_value = $get_host_lists;
					$get_host_lists = array();
					array_push($get_host_lists, $new_value);
				}
			}
		}
	}

	$grid_lsf_config_hosts = array();
	if (isset_request_var('cluster_id')) {
		if (get_request_var('cluster_id') != '') {
			$grid_lsf_config_hosts['all'] = 'all';
			$get_grid_host = db_fetch_assoc_prepared('SELECT host FROM grid_hosts WHERE clusterid=?', array(get_request_var('cluster_id')));

			if (cacti_sizeof($get_grid_host)) {
				foreach ($get_grid_host as $key => $value) {
					$host_value = $value['host'];
					$grid_lsf_config_hosts[$host_value] = $host_value;
				}
			}
		}
	}

	if (isset($get_host_lists)) {
		$results = array_diff($grid_lsf_config_hosts, $get_host_lists);
	} else {
		$results = $grid_lsf_config_hosts;
	}

	if (cacti_sizeof($results)) {
		foreach($results as $result) {
			$custom_host .= '<option value="'.$result.'">'.$result.'</option>';
		}
	}

	$custom_host .= '</select></td>
		<td>
			<input type="button" name="Append" id="Append" value="' . __esc('Append >>', 'grid') . '" onclick="appendSelected(1);" /></br>
			<input type="button" name="Remove" id="Remove" value="' . __esc('<< Remove', 'grid') . '" onclick="appendSelected(2)" />
		</td>
		<td>
			<select id="add_hosts" name="add_hosts" size="10" MULTIPLE>';

	if (isset($get_host_lists)) {
		foreach ($get_host_lists as $host_list) {
			$custom_host .= '<option value="' . html_escape($host_list) . '">' . html_escape($host_list) . '</option>';
		}
	}

	$custom_host .=	'</select>
			</td></tr></table>';

	$grid_lsf_config_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('New LSF Configuration', 'grid')
			),
		'config_name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Configuration Name', 'grid'),
			'description' => __('The name that is assigned to the new configuration.', 'grid'),
			'size' => '50',
			'max_length' => '50',
			'value' => '|arg1:config_name|',
			'default' => ''
			),
		'cluster_id' => array(
			'method' => 'drop_sql',
			'sql' => 'SELECT clustername AS name, clusterid AS id FROM grid_clusters ORDER BY name',
			'friendly_name' => __('Cluster Name', 'grid'),
			'description' => __('Each template can be associated to a cluster only. Choose the correct cluster to link this configuration to the cluster', 'grid'),
			'value' => '|arg1:cluster_id|'
			),
		);

	$grid_lsf_config_load = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('Load LSF Configuration', 'grid')
			),
		'cluster_id' => array(
			'method' => 'drop_sql',
			'sql' => 'SELECT clustername AS name, clusterid AS id FROM grid_clusters ORDER BY name',
			'friendly_name' => __('Cluster Name', 'grid'),
			'description' => __('Choose the cluster to load the configuration to RTM', 'grid'),
			'value' => '|arg1:cluster_id|'
			),
		);

	$grid_lsf_config_shared_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('LSF Resource Configuration', 'grid')
			),
		'resourcename' => array(
			'method' => 'textbox',
			'friendly_name' => __('Resource Name *', 'grid'),
			'description' => __('An arbitrary character string.<br>A resource name cannot begin with a number.<br>Resource names can be up to 39 characters in length.', 'grid'),
			'size' => '30',
			'max_length' => '29',
			'value' => '|arg1:resourcename|',
			'default' => ''
			),
		'TYPE' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Type *', 'grid'),
			'description' => __('3 types: Boolean, Numeric, String', 'grid'),
			'default' => '0',
			'array' => array(
				'Boolean' => __('Boolean', 'grid'),
				'Numeric' => __('Numeric', 'grid'),
				'String'  => __('String', 'grid')
				),
			'value' => '|arg1:TYPE|'
			),
		'INTERVAL' => array(
			'method' => 'textbox',
			'friendly_name' => __('Interval', 'grid'),
			'description' => __('Number of seconds at which the dynamic resource is sampled by ELIM. (Optional)', 'grid'),
			'default' => '',
			'size' => '10',
			'max_length' => '9999999',
			'value' => '|arg1:INTERVAL|'
			),
		'INCREASING' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Increasing', 'grid'),
			'description' => __('Boolean for numeric resource. Y = Larger number means greater load, N = Larger number means lower load.', 'grid'),
			'default' => '0',
			'array' => array(
				'()' => __('Default', 'grid'),
				'Y'  => __('Yes', 'grid'),
				'N'  => __('No', 'grid')
				),
			'value' => '|arg1:INCREASING|'
			),
		'consumable' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Consumable', 'grid'),
			'description' => __('Specify resources as consumable to explicitly control if a resource is consumable. Static and dynamic numeric resources can be specified as consumable.', 'grid'),
			'default' => '0',
			'array' => array(
				'()' => __('Default', 'grid'),
				'!'  => __('Auto Detect', 'grid'),
				'Y'  => __('Yes', 'grid'),
				'N'  => __('No', 'grid')
				),
			'value' => '|arg1:consumable|'
			),
		'release' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Release', 'grid'),
			'description' => __('Boolean, for numeric shared resource. Y = release the resource when a job using it is suspended.', 'grid'),
			'default' => '0',
			'array' => array(
				'Default' => __('Default', 'grid'),
				'!'       => __('Auto Detect', 'grid'),
				'Y'       => __('Yes', 'grid'),
				'N'       => __('No', 'grid')
				),
			'value' => '|arg1:release|'
			),
		'DESCRIPTION' => array(
			'method' => 'textbox',
			'friendly_name' => __('Descriptions', 'grid'),
			'description' => __('Brief description of the resource.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:DESCRIPTION|'
			),
	);

	$grid_lsf_config_cluster_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('LSF Cluster Configuration', 'grid')
			),
		'hostname' => array(
			'method' => 'textbox',
			'friendly_name' => __('Hostname *', 'grid'),
			'description' => __('Official name of the host as returned by hostname.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '50',
			'value' => '|arg1:hostname|'
			),
		'RESOURCES' => array(
			'method' => 'textbox',
			'friendly_name' => __('Resources', 'grid'),
			'description' => __('The static Boolean resources available on this host.<br>You may list any number of resources, separated by spaces.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:RESOURCES|'
			),
		'server' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Server Type', 'grid'),
			'description' => __('Specify "Server" if the host can receive jobs from other hosts; specify client otherwise.', 'grid'),
			'array' => array(
				'0' => __('Client', 'grid'),
				'1' => __('Server', 'grid')
				),
			'default' => '1',
			'value' => '|arg1:server|'
			),
		'type' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Type', 'grid'),
			'description' => __('The strings used for host types are determined by the system administrator.<br>The host type is used to identify binary-compatible hosts.', 'grid'),
			'array' => $grid_lsf_host_type,
			'default' => '',
			'value' => '|arg1:type|'
			),
		'model' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Host Model', 'grid'),
			'description' => __('This determines the CPU speed scaling factor applied in load and placement calculations.', 'grid'),
			'array' => $grid_lsf_host_model,
			'default' => '',
			'value' => '|arg1:model|'
			),
		'custom_value' => array(
			'friendly_name' => __('Advanced Attributes', 'grid'),
			'description' => __('Other LSF attributes that are not built in IBM Spectrum LSF RTM.', 'grid'),
			'method' => 'custom',
			'default' => '',
			'value' => $custom
		),
	);

	$grid_lsf_config_queues_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('LSF Queues Configuration', 'grid')
			),
		'queuename' => array(
			'method' => 'textbox',
			'friendly_name' => __('Queue Name *', 'grid'),
			'description' => __('The queue name must be specified; all other parameters are optional.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '50',
			'value' => '|arg1:queuename|'
			),
		'QJOB_LIMIT' => array(
			'method' => 'textbox',
			'friendly_name' => __('QJOB_LIMIT', 'grid'),
			'description' => __('Total number of job slots that this queue can use.', 'grid'),
			'default' => '',
			'size' => '20',
			'max_length' => '9999999',
			'value' => '|arg1:QJOB_LIMIT|'
			),
		'UJOB_LIMIT' => array(
			'method' => 'textbox',
			'friendly_name' => __('UJOB_LIMIT', 'grid'),
			'description' => __('Maximum number of job slots that each user can use in this queue.', 'grid'),
			'default' => '',
			'size' => '20',
			'max_length' => '9999999',
			'value' => '|arg1:UJOB_LIMIT|'
			),
		'USERS' => array(
			'method' => 'textbox',
			'friendly_name' => __('Users', 'grid'),
			'description' => __('Space separated list of user names or user groups that can submit jobs to the queue.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:USERS|'
			),
		'NEW_HOSTS' => array(
			'friendly_name' => __('Hosts', 'grid'),
			'description' => __('Select the hosts on which jobs from this queue can run.', 'grid'),
			'method' => 'custom',
			'default' => '',
			'value' => $custom_host
			),
		'PREEMPTION' => array(
			'method' => 'textbox',
			'friendly_name' => __('Preemption', 'grid'),
			'description' => __('PREEMPTIVE[[queueX[+pref_level] ...]] specifies that this queue can preempt jobs running out of queueX. If no queues are listed, all queues can be preempted. If multiple queues are listed, pref_level specifies which to preempt first.<br>PREEMPTABLE[[queueY ...]] specifies that jobs started by this queue can be preempted by jobs from queueY.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:PREEMPTION|'
			),
		'DISPATCH_WINDOW' => array(
			'method' => 'textbox',
			'friendly_name' => __('Dispatch Window', 'grid'),
			'description' => __('The time window during which jobs are dispatched.<br>Time windows can be specified as up to 3 fields -- [day:]hour[:minute]. Note that day=[0-6]: 0 is Sunday, 1 is Monday and 6 is Saturday. If only one field exists, it is assumed to be hour; if two fields exist, it is assumed to be hour[:minute].<br>Eg. 5:19:00-1:8:30 20:00-8:30<b>', 'grid'),
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:DISPATCH_WINDOW|'
			),
		'RUN_WINDOW' => array(
			'method' => 'textbox',
			'friendly_name' => __('Run Window', 'grid'),
			'description' => __('The time window during which jobs in the queue are allowed to run and dispatched.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:RUN_WINDOW|'
			),
		'PRIORITY' => array(
			'method' => 'textbox',
			'friendly_name' => __('Priority', 'grid'),
			'description' => __('The queue priority. A higher value indicates a higher LSF dispatching priority, relative to other queues.<br>[1 (lowest possible priority)]', 'grid'),
			'default' => '',
			'size' => '10',
			'max_length' => '9999999',
			'value' => '|arg1:PRIORITY|'
			),
		'NICE' => array(
			'method' => 'drop_array',
			'friendly_name' => __('NICE', 'grid'),
			'description' => __('Adjusts the UNIX scheduling priority at which jobs from this queue execute.<br>The default value of 0 (zero) maintains the default scheduling priority for UNIX interactive jobs.', 'grid'),
			'default' => '0',
			'array' => $grid_lsf_nice,
			'value' => '|arg1:NICE|'
			),
		'RERUNNABLE' => array(
			'method' => 'drop_array',
			'friendly_name' => __('ReRunnable', 'grid'),
			'description' => __('If yes, enables automatic job rerun (Job is restarted if the host becomes unavailable).', 'grid'),
			'default' => '0',
			'array' => array(
				'YES' => __('Yes', 'grid'),
				'NO'  => __('No', 'grid')
				),
			'value' => '|arg1:RERUNNABLE|'
			),
		'RES_REQ' => array(
			'method' => 'textbox',
			'friendly_name' => __('Resource Requirements', 'grid'),
			'description' => __('Resource Requirement to be applied on jobs submitted to this queue.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:RES_REQ|'
			),
		'JOB_STARTER' => array(
			'method' => 'textbox',
			'friendly_name' => __('Job Starter', 'grid'),
			'description' => __('Allow specification of job environment or job starter helper program', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '999999',
			'value' => '|arg1:JOB_STARTER|'
			),
		'PRE_EXEC' => array(
			'method' => 'textbox',
			'friendly_name' => __('Pre Execute', 'grid'),
			'description' => __('Command to execute on job host right before the job starts.<br>Max length 4096 chars for Unix and 255 chars for Windows.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:PRE_EXEC|'
			),
		'POST_EXEC' => array(
			'method' => 'textbox',
			'friendly_name' => __('Post Execute', 'grid'),
			'description' => __('Command to execute on execution host after the job finishes.<br>Max length 4096 chars for Unix and 255 chars for Windows.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:POST_EXEC|'
			),
		'REQUEUE_EXIT_VALUES' => array(
			'method' => 'textbox',
			'friendly_name' => __('Requeue Exit Values', 'grid'),
			'description' => __('Enables automatic job requeue and sets the LSB_EXIT_REQUEUE environment variable. Use spaces to separate multiple exit codes', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '9999999',
			'value' => '|arg1:REQUEUE_EXIT_VALUES|'
			),
		'custom_value' => array(
			'friendly_name' => __('Advanced Attributes', 'grid'),
			'description' => __('Other LSF attributes that are not built in IBM Spectrum LSF RTM.', 'grid'),
			'method' => 'custom',
			'default' => '',
			'value' => $custom
		),
		'HOSTS' => array(
			'method' => 'hidden',
			'value' => '',
		)
	);

	$grid_lsf_config_hosts_edit = array(
		'spacer0' => array(
			'method' => 'spacer',
			'friendly_name' => __('LSF Hosts Configuration', 'grid')
			),
		'host_name' => array(
			'method' => 'textbox',
			'friendly_name' => __('Host Name *', 'grid'),
			'description' => __('Official name of the host as returned by hostname.', 'grid'),
			'default' => '',
			'size' => '60',
			'max_length' => '50',
			'value' => '|arg1:host_name|'
			),
		'MXJ' => array(
			'method' => 'textbox',
			'friendly_name' => __('Max Job Slots', 'grid'),
			'description' => __('Number of job slots on the host.<br>Use "!" to make the number of job slots equal to the number of Cores on a host.', 'grid'),
			'default' => '',
			'size' => '10',
			'max_length' => '999999',
			'value' => '|arg1:MXJ|'
			),
		'custom_value' => array(
			'friendly_name' => __('Advanced Attributes', 'grid'),
			'description' => __('Other LSF attributes that are not built in IBM Spectrum LSF RTM.', 'grid'),
			'method' => 'custom',
			'default' => '',
			'value' => $custom
			)
	);

	$export_types['elim_template']=array(
		'name' => __('ELIM Template', 'grid'),
		'title_sql' => 'select name from grid_elim_templates where id=|id|',
		'dropdown_sql' => 'select id,name from grid_elim_templates order by name'
	);
}

function grid_config_settings () {
	global $tabs, $config, $settings;
	global $grid_settings, $grid_settings_system, $tabs_grid, $tabs_grid_system_settings, $tabs_grid_clusters;
	global $grid_refresh_interval, $grid_rows_selector, $grid_display_rows_selector, $grid_db_delete_size;
	global $grid_out_of_services, $grid_clean_periods, $grid_main_screen;;
	global $grid_addhost_frequency, $add_graph_frequency, $grid_minor_refresh_interval, $grid_major_refresh_interval, $grid_max_nonjob_runtimes;
	global $grid_base_batch_timeout, $grid_job_info_timeout, $grid_job_info_retries;
	global $grid_db_max_runtime, $grid_license_server_threads;
	global $grid_timespans, $grid_timeshifts, $grid_jobzoom_timespans, $grid_weekdays;
	global $grid_efficiency_windows, $grid_efficiency_thresholds;
	global $grid_partition_time_range, $grid_detail_data_retention, $grid_summary_data_retention;
	global $grid_max_job_runtimes, $grid_flapping_thresholds, $grid_summary_filters;
	global $grid_jgroup_filter_levels, $grid_projectgroup_filter_levels;
	global $minimum_user_refresh_intervals, $grid_archive_frequencies;
	global $ha_refresh_interval, $tabs_grid_lsf_config, $grid_lsf_config;
	global $grid_cluster_control_actions;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		$log_path = $config['base_path'] . '\\log\\cacti.log';
	} else {
		$log_path = $config['base_path'] . '/log/cacti.log';
	}

	$grid_cluster_control_actions = array(
		1  => __('LIM Startup', 'grid'),
		2  => __('LIM Restart', 'grid'),
		3  => __('LIM Shutdown', 'grid'),
		4  => __('RES Startup', 'grid'),
		5  => __('RES Restart', 'grid'),
		6  => __('RES Shutdown', 'grid'),
		7  => __('MBD Startup', 'grid'),
		8  => __('MBD Restart', 'grid'),
		9  => __('MBD Shutdown', 'grid'),
		10 => __('Lsadmin Reconfig', 'grid'),
		11 => __('Badmin Reconfig', 'grid')
	);


	$tabs_grid_system_settings = array(
		'grid_general'      => __('General', 'grid'),
		'grid_visual'       => __('Visual', 'grid'),
		'grid_poller'       => __('Poller', 'grid'),
		'grid_maint'        => __('Maint', 'grid'),
		'grid_archive'      => __('Archiving', 'grid'),
		'grid_paths'        => __('Paths', 'grid'),
		'grid_thresholds'   => __('THold', 'grid'),
		'grid_aggregation'  => __('Aggregation', 'grid'),
		'grid_jobevents'    => __('Status/Events', 'grid'),
		'grid_idledetect'   => __('Idle Jobs', 'grid'),
		'grid_memviolation' => __('Memory Exceptions', 'grid'),
		'grid_runlimit'     => __('Runlimit Exceptions', 'grid')
	);

	$tabs_grid_clusters = array(
		'cluster_config'     => __('Configuration', 'grid'),
		'cluster_poller'     => __('Poller', 'grid'),
		'cluster_control'    => __('Control', 'grid'),
		'cluster_automation' => __('Automation', 'grid'),
		'cluster_idledetect' => __('Idle Jobs', 'grid'),
		'cluster_advanced'   => __('Advanced', 'grid')
	);

	$grid_lsf_config = array(
		'lsf_configurations' => __('LSF Configuration(s)', 'grid'),
		'lsf_audit'          => __('Audit Logs', 'grid')
	);

	$tabs_grid_lsf_config = array(
		'lsf_cluster' => __('Cluster Hosts', 'grid'),
		'lsf_shared'  => __('Resources', 'grid'),
		'lsf_queues'  => __('Queues', 'grid'),
		'lsf_hosts'   => __('Batch Hosts', 'grid')
	);

	$grid_settings_system['grid_thresholds'] = array(
		'grid_thold_cblue_header' => array(
			'friendly_name' => __('Idle/Closed Thresholds', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_cblue_slots' => array(
			'friendly_name' => __('Job Slot Percent', 'grid'),
			'description' => __('How full must the host be in terms of slots to qualify for idle consideration.', 'grid'),
			'method' => 'drop_array',
			'default' => '100',
			'array' => array(
				'100' => 'Full',
				'50' => '>= 50%')
		),
		'grid_thold_cblue_cpu' => array(
			'friendly_name' => __('CPU Percentage', 'grid'),
			'description' => __('If the CPU percentage is less that this value along with the above criteria, the host will be considered idle with jobs.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '50',
			'max_length' => '10'
		),
		'grid_thold_ured_header' => array(
			'friendly_name' => __('Low Resource Thresholds', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_ured_r1m' => array(
			'friendly_name' => __('R1M Load Average', 'grid'),
			'description' => __('When load average goes above this percentage relative to the number of CPUs for the host, it will be considered low on resources.', 'grid'),
			'method' => 'drop_array',
			'default' => '150',
			'array' => array(
				'80' => '80%',
				'90' => '90%',
				'100' => '100%',
				'120' => '120%',
				'150' => '150%',
				'200' => '200%',
				'300' => '300%',
				'400' => '400%')
		),
		'grid_thold_ured_pmem' => array(
			'friendly_name' => __('Physical Memory', 'grid'),
			'description' => __('How low, in percentage, does Physical memory need to be to be considered urgent.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '10',
			'max_length' => '10'
		),
		'grid_thold_ured_swap' => array(
			'friendly_name' => __('Swap Memory', 'grid'),
			'description' => __('How low, in percentage, does Swap memory need to be to be considered urgent.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '10',
			'max_length' => '10'
		),
		'grid_thold_ured_temp' => array(
			'friendly_name' => __('Temp Memory', 'grid'),
			'description' => __('How low, in percentage, does Temp space (/tmp or /scratch) need to be to be considered urgent.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '10',
			'max_length' => '10'
		),
		'grid_thold_ured_io' => array(
			'friendly_name' => __('I/O Rate', 'grid'),
			'description' => __('How high, in KBytes/Second, does the IO rate need to be in order to consider the host as having low resources.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '10000',
			'max_length' => '10'
		),
		'grid_thold_ured_pg' => array(
			'friendly_name' => __('Paging Rate', 'grid'),
			'description' => __('How high, in Pages/Second, the Paging rate need to be in order to consider the host as having low resources.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '10000',
			'max_length' => '10'
		),
		'grid_thold_yellow_header' => array(
			'friendly_name' => __('Busy Thresholds', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_yellow_cpu' => array(
			'friendly_name' => __('CPU Percentage', 'grid'),
			'description' => __('How high must CPU be, in percentage, to be considered busy.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '85',
			'max_length' => '10'
		),
		'grid_thold_yellow_r1m' => array(
			'friendly_name' => __('R1M Load Average', 'grid'),
			'description' => __('When load average goes above this percentage relative to the number of CPUs for the host, it will be considered busy.', 'grid'),
			'method' => 'drop_array',
			'default' => '80',
			'array' => array(
				'70'  => '70%',
				'80'  => '80%',
				'90'  => '90%',
				'100' => '100%',
				'120' => '120%')
		),
		'grid_thold_yellow_io' => array(
			'friendly_name' => __('I/O Rate', 'grid'),
			'description' => __('How high must the hosts I/O Rate be, in KBytes/Second, to be considered busy.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '1000',
			'max_length' => '10'
		),
		'grid_thold_yellow_pg' => array(
			'friendly_name' => __('Paging Rate', 'grid'),
			'description' => __('How high must the hosts Paging Rate be, in Pages/Second, to be considered busy.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '1000',
			'max_length' => '10'
		),
		'grid_thold_blue_header' => array(
			'friendly_name' => __('Idle w/Job Thresholds', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_blue_slots' => array(
			'friendly_name' => __('Job Slot Percent', 'grid'),
			'description' => __('How full must the host be in terms of slots to qualify for idle consideration.', 'grid'),
			'method' => 'drop_array',
			'default' => '50',
			'array' => array(
				'75' => __('75%%%', 'grid'),
				'50' => __('>= 50%%%', 'grid'),
				'25' => __('>= 25%%%', 'grid')
			)
		),
		'grid_thold_blue_cpu' => array(
			'friendly_name' => __('CPU Percentage', 'grid'),
			'description' => __('What must the maximum CPU percentage be to be considered idle.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '50',
			'max_length' => '10'
		),
		'grid_thold_grey_header' => array(
			'friendly_name' => __('Starved Host Thresholds', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_grey_duration' => array(
			'friendly_name' => __('Time Since Last Job', 'grid'),
			'description' => __('How long must a host go without running jobs before it is marked starved.', 'grid'),
			'method' => 'drop_array',
			'default' => '1440',
			'array' => array(
				'30'   => __('%d Minutes', 30, 'grid'),
				'60'   => __('%d Hour',    1,  'grid'),
				'240'  => __('%d Hours',   4,  'grid'),
				'480'  => __('%d Hours',   8,  'grid'),
				'960'  => __('%d Hours',   16, 'grid'),
				'1440' => __('%d Day',     1,  'grid'),
				'2880' => __('%d Days',    2,  'grid')
			)
		),
		'grid_thold_resdown_header' => array(
			'friendly_name' => __('Down Service Status', 'grid'),
			'method' => 'spacer',
		),
		'grid_thold_resdown_status' => array(
			'friendly_name' => __('RES Down Status', 'grid'),
			'description' => __('LSF uses the RES service for many activities.  However, in some situations a RES Down condition does not imply an Out of Service condition.  Please indicated here, how a RES Down Status should be treated.', 'grid'),
			'method' => 'drop_array',
			'default' => '1',
			'array' => array(
				1 => __('Out of Service', 'grid'),
				2 => __('In Service', 'grid')
			)
		),
	);

	$grid_settings_system['grid_general'] = array(
		'grid_general_header' => array(
			'friendly_name' => __('General Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_host_substitute' => array(
			'friendly_name' => __('Summary Hostname Substitute', 'grid'),
			'description' => __('A semi-colon separated list of host substrings that should be replaced with a blank for representation on the Host Dashboard to conserve screen real estate.', 'grid'),
			'method' => 'textbox',
			'default' => '',
			'size' => '60',
			'max_length' => '512'
		),
		'grid_slot_reference' => array(
			'friendly_name' => __('Slot Reference Method', 'grid'),
			'description' => __('Choose how to refer to Job Slots. Select Job Tasks to change the concept to task instead of slots.', 'grid'),
			'method' => 'drop_array',
			'default' => 'Slots',
			'array' => array(
				'Jobs'  => __('Jobs', 'grid'),
				'Slots' => __('Job Slots', 'grid'),
				'CPUs'  => __('Job CPUs', 'grid'),
				'Cores' => __('Job Cores', 'grid'),
				'Tasks' => __('Job Tasks', 'grid')
			)
		),
		'grid_minimum_refresh_interval' => array(
			'friendly_name' => __('Minimum User Screen Refresh Interval', 'grid'),
			'description' => __('This global setting will set the minimum refresh interval users are allowed to pick for pages.', 'grid'),
			'method' => 'drop_array',
			'default' => '60',
			'array' => $grid_refresh_interval,
		),
		'grid_max_job_zoom_interval' => array(
			'friendly_name' => __('Maximum Job Zoom Time Range', 'grid'),
			'description' => __('This setting limits the time range in graph view when users query for grid jobs in range. This is being implemented to help avoid issues with database response time in larger systems.', 'grid'),
			'method' => 'drop_array',
			'default' => GTS_1_DAY,
			'array' => $grid_jobzoom_timespans,
		),
		'grid_jobs_clean_period' => array(
			'friendly_name' => __('Jobs Table Clean Period', 'grid'),
			'description' => __('This setting controls how long jobs will be maintained in the main jobs table for users who do not have access to the archive data.  If using partitioning, this value must be less that the partitioning frequency!', 'grid'),
			'method' => 'drop_array',
			'default' => 7200,
			'array' => $grid_clean_periods,
		),
		'grid_xport_rows' => array(
			'friendly_name' => __('Maximum Export Rows', 'grid'),
			'description' => __('This defines the maximum rows that may be exported from jobs and other RTM exportable pages. And the "Limit" rows of UI is based on this value and the max_input_vars of PHP.', 'grid'),
			'method' => 'drop_array',
			'default' => '1000',
			'array' => array(
				'500'  => __('%d Rows', 500,  'grid'),
				'1000'  => __('%d Rows', 1000,  'grid'),
				'2000'  => __('%d Rows', 2000,  'grid'),
				'5000'  => __('%d Rows', 5000,  'grid'),
				'10000' => __('%d Rows', 10000, 'grid'),
				'20000' => __('%d Rows', 20000, 'grid'),
				'50000' => __('%d Rows', 50000, 'grid')
			),
		),
		'grid_cpu_leveling' => array(
			'friendly_name' => __('Cluster CPU Factor Leveling', 'grid'),
			'description' => __('When measuring overall CPU Utilization, RTM has the ability to consider each hosts \'CPU Factor\' in that calculation.  By default, this is disabled due the advanced nature of this setting.  Before enabling this, make sure you understand how CPU Factors work and only after you verify that they are correct for your Cluster(s).', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'add_summary_device' => array (
			'friendly_name' => __('Add Summary Device', 'grid'),
			'description' => __('When this option is enabled, RTM will automatically add a summary device using the host template grid summary. All graphs for this summary device will be added automatically. A graph tree will be created and all graphs will be appended to this newly created tree node.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'add_os_users' => array (
			'friendly_name' => __('Add OS Users', 'grid'),
			'description' => __('When creating a cluster, should RTM attempt to create the user accounts for the Cluster Control user.  This would not be required if your server is already setup in either NIS, LDAP or some other domain system', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'tooltip_disbled' => array (
			'friendly_name' => __('Disable Tooltip for Columns, Filters and Tabs', 'grid'),
			'description' => __('Tooltip is enabled by default. When it is disabled, Tooltip on GUI Columns, Filters and Tabs will be closed. Tooltips on GUI fields and Start box are always enabled', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		// 'rtm_help_location' => array (
		// 	'friendly_name' => __('RTM Help Location', 'grid'),
		// 	'description' => __('Specify the location of the help. If you have access to the Internet, specify Online Knowledge Center. If you do not have access to the Internet, specify local PDF file.', 'grid'),
		// 	'method' => 'drop_array',
		// 	'default' => '1',
		// 	'array' => array(
		// 		'1' => __('Online Knowledge Center', 'grid'),
		// 		'2' => __('Local PDF file', 'grid')
		// 	),
		// ),
		'grid_management_header' => array(
			'friendly_name' => __('Cluster Control Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_management_clusters' => array(
			'friendly_name' => __('Mandatory Cluster Action Control Comments', 'grid'),
			'description' => __('If you wish to enforce comments inputting when your grid admin take action on <strong>cluster/host/queue</strong>, check this box and they will have to input some comments before control action submitted ', 'grid'),
			'default' => 'off',
			'method' => 'checkbox'
		),
		'grid_global_opts_header' => array(
			'friendly_name' => __('Grid Performance User Settings Control', 'grid'),
			'description' => __('Check this checkbox if you wish to allow the user to enable following performance impacted options.', 'grid'),
			'method' => 'spacer',
		),
		'grid_global_opts_jobs_status_all' => array(
			'friendly_name' => __('Allow User to Enable \'All Job Status Option\' Option', 'grid'),
			'description' => __('The user will be able to enable \'All Job Status Option\'. <strong><u>NOTE:</strong>If this option is enabled, and then user enable \'Show Page Number\', RTM performance is generally affected by slowing down if user choose ALL option from the job status dropdown list.</u>', 'grid'),
			'default' => 'off',
			'method' => 'checkbox'
		),
		'grid_global_opts_jobs_pageno' => array(
			'friendly_name' => __('Allow User to Enable \'Show Page Number\' Option', 'grid'),
			'description' => __('The user will be able to enable \'Show Page Number\' for the historical jobs query. <strong><u>NOTE:</strong>If this option is enabled, and then user enable \'Show Page Number\', RTM performance is generally affected by slowing down.</u>', 'grid'),
			'default' => 'off',
			'method' => 'checkbox'
		)
	);

	$grid_settings_system['grid_visual'] = array(
		'grid_tree_header' => array(
			'friendly_name' => __('Tree Category - When a new cluster is added, display the selected categories in tree view.', 'grid'),
			'method' => 'spacer',
		),
		'grid_tree_queue_stats' => array(
			'friendly_name' => __('Queue Stats', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_tree_hostgroup_stats' => array(
			'friendly_name' => __('Host group Stats', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_tree_projects' => array(
			'friendly_name' => __('Projects', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_applications' => array(
			'friendly_name' => __('Applications', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_shared_resources' => array(
			'friendly_name' => __('Shared Resources', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_license_projects' => array(
			'friendly_name' => __('License Projects', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_job_groups' => array(
			'friendly_name' => __('Job Groups', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_guaranteed_slas' => array(
			'friendly_name' => __('Guaranteed SLAs', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_guaranteed_respools' => array(
			'friendly_name' => __('Guaranteed ResPools', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_tree_benchmarks' => array(
			'friendly_name' => __('Benchmarks', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		)
	);

	$grid_settings_system['grid_poller'] = array(
		'grid_general_header' => array(
			'friendly_name' => __('General Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_collection_enabled' => array(
			'friendly_name' => __('Should Daemons be Enabled', 'grid'),
			'description' => __('If you wish to stop your grid daemons from delivering data to the database for any reason, uncheck this box and they will stop forwarding information from the workload manager to the IBM Spectrum LSF RTM Plugin', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_system_collection_enabled' => array(
			'friendly_name' => __('Should Daemons be Enabled (Overrides User Setting', 'grid'),
			'description' => __('When the system performs database maintenance, it is important that the grid collection daemons be disabled.  This system setting controls that', 'grid'),
			'default' => 'on',
			'method' => 'hidden'
		),
		'grid_hq_header' => array(
			'friendly_name' => __('Queue/Host/Load Collection Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_host_autopurge' => array(
			'friendly_name' => __('Host Auto Purge Frequency', 'grid'),
			'description' => __('How long after server hosts are automatically purged from the database when they are removed from the clusters?<strong><u>NOTE:</strong> For security reasons, client hosts must always be manually purged.</u>', 'grid'),
			'method' => 'drop_array',
			'default' => '-1',
			'array' => array(
				'-1'  => __('Never', 'grid'),
				'1'   => __('1 Day', 'grid'),
				'7'   => __('1 Week', 'grid'),
				'14'  => __('2 Weeks', 'grid'),
				'30'  => __('1 Month', 'grid'),
				'180' => __('6 Months', 'grid')
			)
		),
		'grid_ls_load_option' => array(
			'friendly_name' => __('CPU Run Queue Length Load Indices Type', 'grid'),
			'description' => __('The load indices type for CPU Run Queue Length to be used by lsf Pollers. If EFFECTIVE is set, then the CPU run queue length load indices of each host returned are effective load.', 'grid'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(
				'0' => 'DEFAULT',
				'1' => 'EFFECTIVE',
				'2' => 'NORMALIZE'
			)
		),
		'grid_graph_header' => array(
			'friendly_name' => __('Cluster Graph Management', 'grid'),
			'method' => 'spacer',
		),
		'grid_graph_purge_static' => array(
			'friendly_name' => __('Queue, Host Group and Application Purge Frequency', 'grid'),
			'description' => __('How long after a Queue, Host Group or Application is removed from the system, do you wish to have the corresponding graphs removed?', 'grid'),
			'method' => 'drop_array',
			'default' => '-1',
			'array' => array(
				'-1'  => __('Never', 'grid'),
				'1'   => __('%d Day',    1, 'grid'),
				'7'   => __('%d Week',   1, 'grid'),
				'14'  => __('%d Weeks',  2, 'grid'),
				'30'  => __('%d Month',  1, 'grid'),
				'180' => __('%d Months', 6, 'grid')
			)
		),
		'grid_graph_purge_transient' => array(
			'friendly_name' => __('User, User Group, Job Group, Project, License Project Purge Frequency', 'grid'),
			'description' => __('How long must any of the following: Users, User Groups, Job Groups, Projects and License Projects are not updated, before the corresponding graphs will be removed automatically?  (If you do not choose \'Never\' and want to keep the data of users and projects for \'Daily Statistics\', Please keep this value the same as the option of \'Daily Statistics Retention Period\' in maint tab)', 'grid'),
			'method' => 'drop_array',
			'default' => '-1',
			'array' => (array('-1' => __('Never', 'grid')) + $grid_summary_data_retention)
		),
		'grid_job_header' => array(
			'friendly_name' => __('Job Collection Settings', 'grid'),
			'method' => 'spacer',
		),
		'rtm_max_insert_packet_size' => array(
			'friendly_name' => __('Max Insert SQL string length', 'grid'),
			'description' => __('The maximum length of one INSERT SQL string for job or any generated/intermediate string. Increase this option is benefit for job Poller performance if there is network latency between Poller host and database server. The default is 128KB, upper limit to 16MB. ', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '131064',
			'max_length' => '20'
		),
		'grid_pendreason_full_collection' => array(
			'friendly_name' => __('Enable Pending Reason History Reporting', 'grid'),
			'description' => __('If this option is enabled then the lsf Poller will collect and store all pending reasons throughout the life cycle of the job.  Pending reasons duration analysis will be performed and a graphical visual display will be available under the pending reason tab in the job\'s detail page.  Please be aware that enabling this option will cause a slowdown in the overall job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_utime_stime_zero_collection' => array(
			'friendly_name' => __('Enable Rusage Collection when the sum of utime/stime is zero', 'grid'),
			'description' => __('The lsf Poller will collect and store resource usage for a job when the sum of user time and system time is zero. If this option is enabled, then RTM performance is affected by slowing down the job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_host_rusage_collection' => array(
			'friendly_name' => __('Enable Host Level Rusage Collection', 'grid'),
			'description' => __('The lsf Poller will collect and store resource usage for a job when the job is submitted with \'blaunch\' and the cluster configured with \'LSF_HPC_EXTENSIONS = HOST_RUSAGE\'. If this option is enabled, then RTM performance is affected by slowing down the job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_pending_rusage_collection' => array(
			'friendly_name' => __('Enable Dynamic Reserved Memory Collection for PEND Jobs', 'grid'),
			'description' => __('The lsf Poller will collect and store dynamic reserved memory for a pending jobs when the job is submitted the LSF queue configured with \'RESOURCE_RESERVE = MAX_RESERVE_TIME\'. If this option is enabled, then RTM performance is affected by slowing down the job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_GPU_rusage_collection' => array(
			'friendly_name' => __('Enable GPU Collection', 'grid'),
			'description' => __('The lsf Poller will collect and store GPU usage for finished jobs under GPU exclusive mode and GPU messages for running and finished jobs. Enabling this option may affect overall RTM performance by slowing down the job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox',
		),
		'grid_GPU_rusage_simulation' => array(
			'friendly_name' => __('Enable GPU RUsage Simulation', 'grid'),
			'description' => __('Available for GPU exclusive mode (mode=exclusive_process) or Job exclusive (j_exclusive=yes) only. When enabled, the lsf Poller collects computing node GPU load indices and simulates those GPU load indices as job GPU Rusage for running jobs. Enabling this option may affect overall RTM performance by slowing down the job data collection.', 'grid'),
			'default' => '',
			'method' => 'checkbox',
		),
		'grid_jobhosts_collection' => array(
		    'friendly_name' => __('Enable Multiple Execution Hosts Collection', 'grid'),
		    'description' => __('When enabled, the lsf Poller collects and stores multiple execution hosts for parallel jobs even when the job does not span to more than one execution host.  Disabling this option may improve RTM job query performance with the host or host group filter and improve job data collection performance.', 'grid'),
		    'default' => 'on',
		    'method' => 'checkbox'
		),
		'grid_advocate_header' => array(
			'friendly_name' => __('Advocate Settings', 'grid'),
			'method' => 'spacer',
		),
		'advocate_port' => array(
			'friendly_name' => __('Advocate Port', 'grid'),
			'description' => __('Enter the port name that Advocate should use. Please remember to restart the advocate service after change the port.', 'grid'),
			'method' => 'textbox',
			'size' => '7',
			'default' => '8089',
			'max_length' => '6'
		)
	);

	$grid_settings_system['grid_archive'] = array(
		'grid_archive_header' => array(
			'friendly_name' => __('Database Archiving Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_archive_enable' => array(
			'friendly_name' => __('Enable Data Archiving', 'grid'),
			'description' => __('Check this box if you want the maintenance script move legacy job and job related data to an archive storage facility or database.', 'grid'),
			'method' => 'checkbox',
			'default' => ''
		),
		'grid_archive_frequency' => array(
			'friendly_name' => __('Archive Frequency', 'grid'),
			'description' => __('How often, in minutes, should the Archiver move Done, Exited and Unknown jobs from the active database to the Archive database?', 'grid'),
			'method' => 'drop_array',
			'default' => '3600',
			'array' => $grid_archive_frequencies,
		),
		'grid_archive_db_type' => array(
			'friendly_name' => __('Database Type', 'grid'),
			'description' => __('The type of database that will house the archive data.  RTM utilizes ADODB for connect and transfer data.', 'grid'),
			'method' => 'drop_array',
			'default' => 'mysql',
			'array' => array('mysql' => 'MySQL')
		),
		'grid_archive_host' => array(
			'friendly_name' => __('Database Host', 'grid'),
			'description' => __('Provide the hostname of the host that will be receiving this data. \'localhost\' for the same server as your current RTM repository.', 'grid'),
			'method' => 'textbox',
			'size' => '25',
			'default' => 'localhost',
			'max_length' => '25'
		),
		'grid_archive_name' => array(
			'friendly_name' => __('Database Name', 'grid'),
			'description' => __('Provide the name of the database that will receive the data.', 'grid'),
			'method' => 'textbox',
			'size' => '25',
			'default' => '',
			'max_length' => '25'
		),
		'grid_archive_user' => array(
			'friendly_name' => __('Database User', 'grid'),
			'description' => __('Provide the database account user name for connecting with the database. <b>WARNING: Must have a different user/password from the Cacti Database!!!</b>', 'grid'),
			'method' => 'textbox',
			'size' => '25',
			'default' => '',
			'max_length' => '25'
		),
		'grid_archive_password' => array(
			'friendly_name' => __('Database Password', 'grid'),
			'description' => __('Provide the database account user password for connecting with the database.', 'grid'),
			'method' => 'textbox',
			'size' => '25',
			'default' => '',
			'max_length' => '25'
		),
		'grid_archive_port' => array(
			'friendly_name' => __('Database Port', 'grid'),
			'description' => __('Provide the port to utilize in order to connect to this database.', 'grid'),
			'method' => 'textbox',
			'size' => '25',
			'default' => '3306',
			'max_length' => '25'
		)
	);

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
		$filetype = '.zip';
	} else {
		$filetype = '.tgz';
	}

	$grid_settings_system['grid_maint'] = array(
		'grid_maint_header' => array(
			'friendly_name' => __('System Maintenance Settings', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_db_maint_time' => array(
			'friendly_name' => __('Database Maintenance Time', 'grid'),
			'description' => __('When should old database records be removed from the database.  <strong><u>NOTE:</strong> During this maintenance process, access to job records will be impaired.</u>', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '12:00am',
			'max_length' => '10'
		),
		'grid_detail_data_retention' => array(
			'friendly_name' => __('Detail Job Data Retention Period', 'grid'),
			'description' => __('How long should Job Rusage details and Job interval runtime statistics be retained in the database? If checked below, RRD files will be created to store detailed CPU, Memory and PID/PGID usage prior to the details being deleted. <strong><u>NOTE:</strong> Once Job Interval data has been deleted, contention RRDfiles can no longer be re-generated before the deletion date.</u>', 'grid'),
			'method' => 'drop_array',
			'default' => '2weeks',
			'array' => $grid_detail_data_retention,
		),
		'grid_summary_data_retention' => array(
			'friendly_name' => __('Summary Job Data Retention Period', 'grid'),
			'description' => __('How long individual Job records be stored in the system.  This includes Job Records, and Job Collection Records.', 'grid'),
			'method' => 'drop_array',
			'default' => '1month',
			'array' => $grid_summary_data_retention,
		),
		'grid_daily_data_retention' => array(
			'friendly_name' => __('Daily Statistics Retention Period', 'grid'),
			'description' => __('How long should LSF Summary statistics be retained in the database.  This currently only includes Daily Job Statistics across four dimensions: User, Queue, Execution Host, and optionally Project and Submission Host.', 'grid'),
			'method' => 'drop_array',
			'default' => '1year',
			'array' => $grid_summary_data_retention,
		),
		'grid_host_closure_data_retention' => array(
			'friendly_name' => __('Host Closure Event Data Retention Period', 'grid'),
			'description' => __('How long should LSF host closure event records be retained in the database.  This currently only includes host closure event records.', 'grid'),
			'method' => 'drop_array',
			'default' => '1year',
			'array' => $grid_summary_data_retention,
		),
		'grid_db_maint_delete_size' => array(
			'friendly_name' => __('Maximum Delete Size', 'grid'),
			'description' => __('When performing database maintenance.  How many records can be deleted at a time.  This is effectively a commit size when pruning the database.', 'grid'),
			'method' => 'drop_array',
			'default' => '2000',
			'array' => $grid_db_delete_size,
		),
		'grid_db_maint_max_time' => array(
			'friendly_name' => __('Maximum DB Maintenance Runtime', 'grid'),
			'description' => __('Since the maintenance script disables daemons, how long should it be assumed to have run for before generating an error and re-enabling daemons.', 'grid'),
			'method' => 'drop_array',
			'default' => '1800',
			'array' => $grid_db_max_runtime,
		),
		'grid_partitioning_header' => array(
			'friendly_name' => __('Large System Settings', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_partitioning_enable' => array(
			'friendly_name' => __('Enable Record Partitioning', 'grid'),
			'description' => __('For Large Systems, the removal of old jobs records can impact system performance.  By checking this option, RTM can speed the database maintenance process through a table partitioning methodology.  <u><b>NOTE:</b> Please refer to RTM Documentation prior to enabling this setting.</u>', 'grid'),
			'method' => 'checkbox',
			'default' => ''
		),
		'grid_partitioning_time_range' => array(
			'friendly_name' => __('Partition Size', 'grid'),
			'description' => __('Approximately what period of time should elapse between partitions.', 'grid'),
			'method' => 'drop_array',
			'default' => '1week',
			'array' => $grid_partition_time_range,
		),
		'grid_maint_backup_header' => array(
			'friendly_name' => __('Database Backups', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_backup_enable' => array(
			'friendly_name' => __('Backup Cacti Database', 'grid'),
			'description' => __('Check this box if you want the maintenance script backup your Cacti database. <u><b>NOTE:</b> This option does not currently backup the grid_jobs, grid_jobs_rusage and grid_jobs_host_rusage tables due to size issues.</u>', 'grid'),
			'method' => 'checkbox',
			'default' => ''
		),
		'grid_backup_schedule' => array(
			'friendly_name' => __('Backup Schedule', 'grid'),
			'description' => __('Do you want to back up your Cacti Database \'Daily\' or \'Weekly\'?', 'grid'),
			'method' => 'drop_array',
			'default' => 'd',
			'array' => array(
				'd' => __('Daily', 'grid'),
				'w' => __('Weekly', 'grid')
			)
		),
		'grid_backup_method' => array(
			'friendly_name' => __('Backup Method', 'grid'),
			'description' => __('RTM allows for a Quick Backup which allows for rapid restore, however certain \'active\' job and license related tables are not backed up.  This method ensures a rapid restore, but also will result in some data loss depending on the age of the backup chosen for restore.  The Full Backup is intended for smaller sites, or sites that don\'t have very large daily volumes of jobs.  There will still be some data loss depending on the backup file chosen for restore.  However note that the time to restore/recovery will be longer if using the Full method.  In all cases, database partitions will only be backed up through the partition backup methodology.', 'grid'),
			'method' => 'drop_array',
			'default' => 'q',
			'array' => array(
				'q' => __('Quick', 'grid'),
				'f' => __('Full', 'grid')
			)
		),
		'grid_backup_weekday' => array(
			'friendly_name' => __('Weekly Backup Day', 'grid'),
			'description' => __('If you are backing up Weekly, what day do you want the backup to occur?', 'grid'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(
				'0' => __('Sunday',    'grid'),
				'1' => __('Monday',    'grid'),
				'2' => __('Tuesday',   'grid'),
				'3' => __('Wednesday', 'grid'),
				'4' => __('Thursday',  'grid'),
				'5' => __('Friday',    'grid'),
				'6' => __('Saturday',  'grid')
			)
		),
		'grid_backup_generations' => array(
			'friendly_name' => __('Backup Generations', 'grid'),
			'description' => __('How many copies of the backup file do you want to maintain?', 'grid'),
			'method' => 'drop_array',
			'default' => '4',
			'array' => array(
				'1' => __('%d Backup', 1, 'grid'),
				'2' => __('%d Backups', 2, 'grid'),
				'3' => __('%d Backups', 3, 'grid'),
				'4' => __('%d Backups', 4, 'grid'),
				'5' => __('%d Backups', 5, 'grid'),
				'6' => __('%d Backups', 6, 'grid'),
				'7' => __('%d Backups', 7, 'grid')
			)
		),
		'grid_backup_path' => array(
			'friendly_name' => __('Database Backup Location', 'grid'),
			'description' => __('What directory do you want the backups to be saved to?  This directory must be writable and accessible to the Cacti Poller userid.', 'grid'),
			'method' => 'dirpath',
			'default' => '',
			'max_length' => '255',
			'size' => '60'
		),
		'grid_backup_partitions' => array(
			'friendly_name' => __('Backup Table Partitions', 'grid'),
			'description' => __('If using Database Partitions, how would you like to handle Table Partition backups?  <font color=\'red\'><b>NOTE: Restoral of Table Partitions must be performed from the command line</b></font>', 'grid'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(
				'1' => __('Don\'t Backup', 'grid'),
				'2' => __('Backup, Remove Aged', 'grid'),
				'3' => __('Backup, Don\'t Remove Aged', 'grid')
			)
		),
		'grid_backup_command' => array(
			'friendly_name' => __('Post Backup Command', 'grid'),
			'description' => __('Once the backup has been completed, the following command will be executed.  The file entered must be both executable and readable by the RTM service account.', 'grid'),
			'method' => 'filepath',
			'default' => '',
			'max_length' => '255',
			'size' => '60'
		),
		'grid_maint_optimization_header' => array(
			'friendly_name' => __('Database Optimization', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_optimization_schedule' => array(
			'friendly_name' => __('Optimization Schedule', 'grid'),
			'description' => __('Do you want to optimize your Cacti Database \'Daily\', \'Weekly\' or \'Monthly\'?', 'grid'),
			'method' => 'drop_array',
			'default' => 'm',
			'array' => array(
				'n' => __('Disabled', 'grid'),
				'd' => __('Daily', 'grid'),
				'w' => __('Weekly', 'grid'),
				'm' => __('Monthly', 'grid')
			)
		),
		'grid_optimization_weekday' => array(
			'friendly_name' => __('Optimization Day of Week', 'grid'),
			'description' => __('If you are optimizing weekly, what day do you want the optimization to occur?', 'grid'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(
				'0' => __('Sunday', 'grid'),
				'1' => __('Monday', 'grid'),
				'2' => __('Tuesday', 'grid'),
				'3' => __('Wednesday', 'grid'),
				'4' => __('Thursday', 'grid'),
				'5' => __('Friday', 'grid'),
				'6' => __('Saturday', 'grid')
			)
		),
		'grid_optimization_monthday' => array(
			'friendly_name' => __('Optimization Date of Month', 'grid'),
			'description' => __('If you are optimizing monthly, what date of month do you want the optimization to occur?', 'grid'),
			'method' => 'drop_array',
			'default' => '1',
			'array' => array(
				'1'  => '1',
				'5'  => '5',
				'10' => '10',
				'15' => '15',
				'20' => '20',
				'25' => '25',
			)
		)
	);

	$grid_settings_system['grid_jobevents'] = array(
		'grid_host_header' => array(
			'friendly_name' => __('LSF Host Status Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_status_unavail_first' => array(
			'friendly_name' => __('Closed-Admin Priority', 'grid'),
			'description' => __('Select this checkbox for RTM to consider the "Unavail" and "Unreach" LSF host status as a higher priority than "Closed-Admin". If not selected, RTM considers the "Unavail" and "Unreach" LSF host status to be a lower priority than "Closed-Admin".', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_jobflapping_header' => array(
			'friendly_name' => __('Job Flapping Settings', 'grid'),
			'method' => 'spacer',
		),
		'grid_flapping_enabled' => array(
			'friendly_name' => __('Should Job Flapping Detection be Enabled', 'grid'),
			'description' => __('If you wish to have jobs that change state too many time to be highlighted on the Job details screen and generate log messages when they exceed the flapping threshold, check this box.', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_flapping_threshold' => array(
			'friendly_name' => __('Job Flapping Threshold', 'grid'),
			'description' => __('How many times must a Job changes states before it is considered in a flapping status?  <strong><u>NOTE:</strong> At a minimum, a job will always have at least 3 state changes from NEW, to RUNNING to DONE.</u>', 'grid'),
			'method' => 'drop_array',
			'default' => '6',
			'array' => $grid_flapping_thresholds,
		),
		'grid_flapping_bgcolor' => array(
			'friendly_name' => __('Job Flapping Background Color', 'grid'),
			'description' => __('Jobs that are in the Flapping Warning state will be highlighted using this color.', 'grid'),
			'default' => '74',
			'method' => 'drop_color'
		),
		'grid_efficiency_header' => array(
			'friendly_name' => __('LSF/Job Efficiency Settings', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_efficiency_enabled' => array(
			'friendly_name' => __('Should LSF/Job Efficiency Detection be Enabled', 'grid'),
			'description' => __('If you wish to have jobs that violate the Job efficiency threshold to be tracked in the user interface and to have cumulative LSF Job Efficiency be logged at the Cluster level, check this box.', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_efficiency_window' => array(
			'friendly_name' => __('Job Start Window', 'grid'),
			'description' => __('How long should a job run for before it is eligible to LSF Efficiency calculations? This measure is used to compensate for job startup delays', 'grid'),
			'method' => 'drop_array',
			'default' => '60',
			'array' => $grid_efficiency_windows,
		),
		'grid_efficiency_warning' => array(
			'friendly_name' => __('Warning Threshold', 'grid'),
			'description' => __('When the overall grid drops below this threshold, a WARNING log message will be issued to indicate so.', 'grid'),
			'method' => 'drop_array',
			'default' => '60',
			'array' => $grid_efficiency_thresholds,
		),
		'grid_efficiency_warning_bgcolor' => array(
			'friendly_name' => __('Warning Background Color', 'grid'),
			'description' => __('Jobs that are in the Warning state will be highlighted using this color.', 'grid'),
			'default' => '98',
			'method' => 'drop_color'
		),
		'grid_efficiency_alarm' => array(
			'friendly_name' => __('Alarm Threshold', 'grid'),
			'description' => __('When the overall grid drops below this threshold, an ERROR log message will be issued to indicate so.', 'grid'),
			'method' => 'drop_array',
			'default' => '40',
			'array' => $grid_efficiency_thresholds,
		),
		'grid_efficiency_alarm_bgcolor' => array(
			'friendly_name' => __('Alarm Background Color', 'grid'),
			'description' => __('Jobs that are in the Alarm state will be highlighted using this color.', 'grid'),
			'default' => '39',
			'method' => 'drop_color'
		),
		'grid_efficiency_event_trigger_count' => array(
			'friendly_name' => __('Event Trigger Count', 'grid'),
			'description' => __('How many consecutive WARNING/ERROR event\'s must have occurred prior to issuing either a WARNING/ERROR event message.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '3',
			'max_length' => '10'
		),
		'grid_efficiency_event_clear_count' => array(
			'friendly_name' => __('Event Clear Count', 'grid'),
			'description' => __('How many consecutive clear event\'s must have occurred prior to issuing a NOTICE event message.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '2',
			'max_length' => '10'
		),
		'grid_pidalarm_header' => array(
			'friendly_name' => __('Job PID Level Alarms', 'grid'),
			'method' => 'spacer',
		),
		'grid_pidalarm_enabled' => array(
			'friendly_name' => __('Should PID levels be Tracked', 'grid'),
			'description' => __('Some operating systems do not operate when there are large numbers of PID\'s in the system.  This is especially true for larger system.  If this feature is enabled, a log message will be generated whenever a job exceeds this threshold.', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_pidalarm_threshold' => array(
			'friendly_name' => __('Number of PID', 'grid'),
			'description' => __('How many PID\'s must a job generate prior to an alarm being generated.', 'grid'),
			'method' => 'textbox',
			'size' => '10',
			'default' => '1024',
			'max_length' => '10',
		),
		'grid_depend_header' => array(
			'friendly_name' => __('Job Highlighting', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_depend_bgcolor' => array(
			'friendly_name' => __('Dependency Background Color', 'grid'),
			'description' => __('Pending Jobs that have dependency conditions will be highlighted using this color.', 'grid'),
			'default' => '28',
			'method' => 'drop_color'
		),
		'grid_invalid_depend_bgcolor' => array(
			'friendly_name' => __('Invalid Dependency Background Color', 'grid'),
			'description' => __('Pending Jobs that have dependency conditions that can never be satisfied will be highlighted using this color.', 'grid'),
			'default' => '33',
			'method' => 'drop_color'
		),
		'grid_exit_bgcolor' => array(
			'friendly_name' => __('Exited Job Background Color', 'grid'),
			'description' => __('What background color do you want to use for exited jobs?', 'grid'),
			'default' => '38',
			'method' => 'drop_color'
		),
		'grid_exclusive_bgcolor' => array(
			'friendly_name' => __('Exclusive Job Background Color', 'grid'),
			'description' => __('What background color do you want to use for exclusive jobs?', 'grid'),
			'default' => '64',
			'method' => 'drop_color'
		),
		'grid_interactive_bgcolor' => array(
			'friendly_name' => __('Interactive Job Background Color', 'grid'),
			'description' => __('What background color do you want to use for interactive jobs?', 'grid'),
			'default' => '81',
			'method' => 'drop_color'
		),
		'grid_licsched_bgcolor' => array(
			'friendly_name' => __('Preempted by License Scheduler', 'grid'),
			'description' => __('What background color do you want to use jobs that are pending due to License Scheduler preemption?', 'grid'),
			'default' => '0',
			'method' => 'drop_color'
		),
		'grid_slaloaning_bgcolor' => array(
			'friendly_name' => __('Loaning Guaranteed Resource Background Color', 'grid'),
			'description' => __('What background color do you want to use Jobs that start through loaning guaranteed resource.', 'grid'),
			'default' => '0',
			'method' => 'drop_color'
		)
	);

	$grid_settings_system['grid_idledetect'] = array(
		'grididle_header' => array(
			'friendly_name' => __('Idle Job Detection', 'grid'),
			'method' => 'spacer',
		),
		'grididle_filter_name' => array(
			'friendly_name' => __('Filter Name', 'grid'),
			'description' => __('What do you want this filter to be displayed as in the legend and in the Job exception filter display', 'grid'),
			'method' => 'textbox',
			'max_length' => '20',
			'default' => __('Idle Jobs', 'grid'),
			'size' => '15'
		),
		'grididle_subject' => array(
			'method' => 'textbox',
			'friendly_name' => __('Email Subject', 'grid'),
			'description' => __('Subject for Idle Jobs Email Message.  You may use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'JOBID>, <'.chr(127).'INDEXID>, <'.chr(127).'USER>, <'.chr(127).'SUBMITTIME>, <'.chr(127).'CPUSECS>', 'grid'),
			'max_length' => '255',
			'size' => '80',
			'default' => __('RTM Idle Job Warning for <JOBID>[<INDEXID>] on Cluster <CLUSTERNAME>', 'grid')
		),
		'grididle_message' => array(
			'method' => 'textarea',
			'friendly_name' => __('Email Message', 'grid'),
			'description' => __('You may use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'JOBID>, <'.chr(127).'INDEXID>, <'.chr(127).'USER>, <'.chr(127).'SUBMITTIME>, <'.chr(127).'CPUSECS>', 'grid'),
			'default' => __('<p>RTM has detected that job <JOBID>[<INDEXID>] Submitted at \'<SUBMITTIME>\' on Cluster <CLUSTERNAME> by <USER> is Idled.<br>The Job has accumulated <CPUSECS> CPU seconds in the last <WINDOW> secs and has run for <RUNTIME> secs.<br>REF: <URL></p>', 'grid'),
			'class' => 'textAreaNotes',
			'textarea_rows' => '10',
			'textarea_cols' => '80'
		),
		'grididle_bgcolor' => array(
			'friendly_name' => __('Legend Background Color', 'grid'),
			'description' => __('The color of the legend.  If the color is set to none, this feature will be disabled.', 'grid'),
			'method' => 'drop_color',
			'default' => '0'
		)
	);

	$grid_settings_system['grid_memviolation'] = array(
		'gridmemvio_header' => array(
			'friendly_name' => __('Memory RUSAGE Violations', 'grid'),
			'method' => 'spacer',
		),
		'gridmemvio_enabled' => array(
			'friendly_name' => __('Enable Memory RUSAGE Job Detection', 'grid'),
			'description' => __('If you want to search for Memory Violation jobs, please check this checkbox.', 'grid'),
			'method' => 'checkbox',
			'default' => ''
		),
		'gridmemvio_notify' => array(
			'friendly_name' => __('Email Summary Reports', 'grid'),
			'description' => __('Choose the notification type to use relative to memory violation jobs.', 'grid'),
			'method' => 'drop_array',
			'default' => 0,
			'array' => array(
				0 => __('None', 'grid'),
				1 => __('User', 'grid'),
				2 => __('Admins and User', 'grid'),
				3 => __('Admins', 'grid')
			)
		),
		'gridmemvio_schedule' => array(
			'friendly_name' => __('Email Schedule', 'grid'),
			'description' => __('Choose the notification period that you would like to receive summary e-mails. Emails will be sent weekly on Sundays during database maintenance.', 'grid'),
			'method' => 'drop_array',
			'default' => 0,
			'array' => array(
				0      => __('None', 'grid'),
				86400  => __('Daily', 'grid'),
				604800 => __('Weekly', 'grid')
			)
		),
		'gridmemvio_filter_name' => array(
			'friendly_name' => __('Memory Over usage Filter Name', 'grid'),
			'description' => __('Please provide a filter to be displayed as in the job details legend for Memory Overuse.', 'grid'),
			'method' => 'textbox',
			'default' => __('Mem Over usage', 'grid'),
			'max_length' => '15',
			'size' => '20'
		),
		'gridmemvio_overage' => array(
			'friendly_name' => __('Memory Over usage Allocation', 'grid'),
			'description' => __('The Memory use percentage above the RUsage level that is acceptable prior to flagging the job.', 'grid'),
			'method' => 'drop_array',
			'default' => '.2',
			'array' => array(
				'.1'  => '10%',
				'.2'  => '20%',
				'.3'  => '30%',
				'.4'  => '40%',
				'.5'  => '50%',
				'.6'  => '60%',
				'.8'  => '80%',
				'1'   => '100%',
				'1.2' => '120%',
				'1.5' => '150%',
				'2'   => '200%',
				'3'   => '300%'
			)
		),
		'gridmemvio_bgcolor' => array(
			'friendly_name' => __('Memory Over usage Background Color', 'grid'),
			'description' => __('The color of the legend.  If the color is set to none, this feature will be disabled.', 'grid'),
			'method' => 'drop_color',
			'default' => '0'
		),
		'gridmemvio_us_filter_name' => array(
			'friendly_name' => __('Memory Underusage Filter Name', 'grid'),
			'description' => __('What do you want this filter to be displayed as in the legend and in the Job exception filter display', 'grid'),
			'method' => 'textbox',
			'default' => __('Mem Underusage', 'grid'),
			'max_length' => '15',
			'size' => '20'
		),
		'gridmemvio_us_allocation' => array(
			'friendly_name' => __('Memory Underusage Allocation', 'grid'),
			'description' => __('The Memory use percentage below the RUsage level that is acceptable prior to flagging the job.', 'grid'),
			'method' => 'drop_array',
			'default' => '.2',
			'array' => array(
				'.1'  => '10%',
				'.2'  => '20%',
				'.3'  => '30%',
				'.4'  => '40%',
				'.5'  => '50%',
				'.6'  => '60%',
				'.8'  => '80%',
				'1'   => '100%',
				'1.2' => '120%',
				'1.5' => '150%',
				'2'   => '200%',
				'3'   => '300%'
			)
		),
		'gridmemvio_us_bgcolor' => array(
			'friendly_name' => __('Memory Underusage Background Color', 'grid'),
			'description' => __('The color of the legend and row display.  If the color is set to none, this feature will be disabled.', 'grid'),
			'method' => 'drop_color',
			'default' => '0'
		),
		'gridmemvio_window' => array(
			'friendly_name' => __('Minimum Run Window', 'grid'),
			'description' => __('The time that a job must run before it is considered for memory exceptions.', 'grid'),
			'method' => 'drop_array',
			'default' => '900',
			'array' => array(
				'300'   => __('%d Minutes', 5,  'grid'),
				'600'   => __('%d Minutes', 10, 'grid'),
				'900'   => __('%d Minutes', 15, 'grid'),
				'1800'  => __('%d Minutes', 30, 'grid'),
				'3600'  => __('%d Hour',    1,  'grid'),
				'7200'  => __('%d Hours',   2,  'grid'),
				'14400' => __('%d Hours',   4,  'grid')
			)
		),
		'gridmemvio_min_memory' => array(
			'friendly_name' => __('Minimum Memory Limit', 'grid'),
			'description' => __('Jobs that consume less than this amount of memory will not be evaluated for memory exceptions.', 'grid'),
			'method' => 'drop_array',
			'default' => '262144',
			'array' => array(
				'-1'      => __('No Limit', 'grid'),
				'262144'  => __('%d MBytes', 256, 'grid'),
				'786432'  => __('%d MBytes', 512, 'grid'),
				'1048576' => __('%d MBytes', 768, 'grid'),
				'1048576' => __('%d GByte',  1,   'grid'),
				'2097152' => __('%d GBytes', 2,   'grid'),
				'4194304' => __('%d GBytes', 4,   'grid')
			)
		),
		'gridmemvio_subject' => array(
			'method' => 'textbox',
			'friendly_name' => __('Email Subject', 'grid'),
			'description' => __('Subject for the Memory Exceptions Email Message.  You may use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'OVERFILTER>, <'.chr(127).'UNDERFILTER>', 'grid'),
			'max_length' => '255',
			'size' => '80',
			'default' => __('RTM Memory <OVERFILTER>/<UNDERFILTER> Report for Cluster <CLUSTERNAME>', 'grid')
		),
		'gridmemvio_message' => array(
			'method' => 'textarea',
			'friendly_name' => __('Email Message', 'grid'),
			'description' => __('You may use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'REPORTTABLE>, <'.chr(127).'OVERSHOOT>, <'.chr(127).'UNDERSHOOT>, <'.chr(127).'OVERFILTER>, <'.chr(127).'UNDERFILTER>', 'grid'),
			'default' => __('<p>This report summarizes violations with mem resource req by user on <CLUSTERNAME>.<br/><ul><li>Memory over usage - the job uses <OVERSHOOT> more memory than was requested when job submitted.</li><li>Memory under used - the job uses <UNDERSHOOT> less memory than was requested when job submitted.</li></ul><REPORTTABLE></p>', 'grid'),
			'class' => 'textAreaNotes',
			'textarea_rows' => '10',
			'textarea_cols' => '80'
		),
	);

	$grid_settings_system['grid_runlimit'] = array(
		'gridrunlimit_header' => array(
			'friendly_name' => __('RunTime warning', 'grid'),
			'method' => 'spacer',
		),
		'gridrunlimitvio_enabled' => array(
			'friendly_name' => __('Enable job runtime detection', 'grid'),
			'description' => __('This displays all the jobs that exceeds Runtime Limit and/or Estimation.', 'grid'),
			'method' => 'drop_array',
			'default' => '1',
			'array' => array(
				'1' => __('Disabled', 'grid'),
				'2' => __('Runtime Estimation', 'grid'),
				'3' => __('Runtime Limit', 'grid'),
				'4' => __('Both Estimation and Limit', 'grid')
			)
		),
		'gridrunlimitkilled_enabled' => array(
			'friendly_name' => __('Enable job killed notification', 'grid'),
			'description' => __('This displays all the jobs that are killed because of Runtime Limit.', 'grid'),
			'method' => 'checkbox',
			'default' => ''
		),
		'gridrunlimitvio_threshold' => array(
			'friendly_name' => __('Set a runtime threshold in percentage', 'grid'),
			'description' => __('Send out a warning e-mail when the Runtime Limit and/or Runtime Estimation reach this threshold value.', 'grid'),
			'method' => 'drop_array',
			'default' => '.2',
			'array' => array(
				'.1' => '10%',
				'.2' => '20%',
				'.3' => '30%',
				'.4' => '40%',
				'.5' => '50%'
			)
		),
		'gridrunlimitvio_filter_name' => array(
			'friendly_name' => __('Provide a filter name to be displayed in the Jobs Detail page', 'grid'),
			'description' => '',
			'method' => 'textbox',
			'default' => __('Runlimit time violation', 'grid'),
			'max_length' => '15',
			'size' => '20'
		),

		'gridrunlimitvio_bgcolor' => array(
			'friendly_name' => __('Specify a color to highlight in the Job Details page', 'grid'),
			'description' => __('If the threshold of run time limit or estimation exceeds.', 'grid'),
			'method' => 'drop_color',
			'default' => '0'
		),
		'gridrunlimitmail_header' => array(
			'friendly_name' => __('RunTime Limit Warning Email', 'grid'),
			'method' => 'spacer',
		),
		'gridrunlimitvio_notify' => array(
			'friendly_name' => __('Notification for Runtime Limit', 'grid'),
			'description' => '',
			'method' => 'drop_array',
			'default' => 0,
			'array' => array(
				0 => __('None', 'grid'),
				1 => __('User', 'grid'),
				2 => __('Admins and User', 'grid'),
				3 => __('Admins', 'grid')
			)
		),

		'gridrunlimitvio_subject' => array(
			'method' => 'textbox',
			'friendly_name' => __('Email subject', 'grid'),
			'description' => __('You can use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'RUNTIMETHRESHOLD>, <'.chr(127).'USER>.', 'grid'),
			'max_length' => '255',
			'size' => '80',
			'default' => __('Runtime Limit Warning Report For the <CLUSTERNAME>', 'grid')
		),
		'gridrunlimitvio_message' => array(
			'method' => 'textarea',
			'friendly_name' => __('Email Message', 'grid'),
			'description' => __('You can use the following replacement tags<'.chr(127).'CLUSTERNAME>, <'.chr(127).'RUNTIMETHRESHOLD>,<'.chr(127).'USER>.', 'grid'),
			'default' => __('<p>This report summarizes warning with run limit time by user on <CLUSTERNAME>.<br/><ul><li> the following jobs are within threshold of Runtime Limit</li></ul><REPORTTABLE></p>', 'grid'),
			'class' => 'textAreaNotes',
			'textarea_rows' => '10',
			'textarea_cols' => '80'
		),
		'gridruntimeestmail_header' => array(
			'friendly_name' => __('RunTime Estimation Warning Email', 'grid'),
			'method' => 'spacer',
		),
		'gridruntimeest_notify' => array(
			'friendly_name' => __('Notification for Runtime Estimation', 'grid'),
			'description' => '',
			'method' => 'drop_array',
			'default' => 0,
			'array' => array(
				0 => __('None', 'grid'),
				1 => __('User', 'grid'),
				2 => __('Admins and User', 'grid'),
				3 => __('Admins', 'grid')
			)
		),
		'gridruntimeest_subject' => array(
			'method' => 'textbox',
			'friendly_name' => __('Email subject', 'grid'),
			'description' => __('You can use the following replacement tags <'.chr(127).'CLUSTERNAME>,<'.chr(127).'RUNTIMETHRESHOLD>,<'.chr(127).'USER>.', 'grid'),
			'max_length' => '255',
			'size' => '80',
			'default' => __('RTM Runtime Estimation Warning Report for Cluster <CLUSTERNAME>', 'grid')
		),
		'gridruntimeest_message' => array(
			'method' => 'textarea',
			'friendly_name' => __('Email Message', 'grid'),
			'description' => __('You can use the following replacement tags<'.chr(127).'CLUSTERNAME>,<'.chr(127).'RUNTIMETHRESHOLD>, <'.chr(127).'USER>.', 'grid'),
			'default' => __('<p>This report summarizes warning with runtime estimation by user on <CLUSTERNAME>.<br/><ul><li> the following jobs are within threshold of Runtime Estimation</li></ul><REPORTTABLE></p>', 'grid'),
			'class' => 'textAreaNotes',
			'textarea_rows' => '10',
			'textarea_cols' => '80'
		),
		'gridjobkilledmail_header' => array(
			'friendly_name' => __('Job Killed Email', 'grid'),
			'method' => 'spacer',
		),
		'gridjobkilled_notify' => array(
			'friendly_name' => __('Notification for job killed because of Runtime Limit', 'grid'),
			'description' => '',
			'method' => 'drop_array',
			'default' => 0,
			'array' => array(
				0 => __('None', 'grid'),
				1 => __('User', 'grid'),
				2 => __('Admins and User', 'grid'),
				3 => __('Admins', 'grid')
			)
		),
		'gridjobkilled_subject' => array(
			'method' => 'textbox',
			'friendly_name' => __('Email Subject of job killed', 'grid'),
			'description' => __('You can use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'USER>.', 'grid'),
			'max_length' => '255',
			'size' => '80',
			'default' => __('RTM Job killed Report for Cluster <CLUSTERNAME> because of runtime limit', 'grid')
		),
		'gridjobkilled_message' => array(
			'method' => 'textarea',
			'friendly_name' => __('Email Message of job killed', 'grid'),
			'description' => __('You can use the following replacement tags <'.chr(127).'CLUSTERNAME>, <'.chr(127).'USER>.', 'grid'),
			'default' => __('<p>This report summarizes  the killed jobs because of RUNLIMIT on <CLUSTERNAME>.<br/><REPORTTABLE></p>', 'grid'),
			'class' => 'textAreaNotes',
			'textarea_rows' => '10',
			'textarea_cols' => '80'
		),
	);
	$grid_settings_system['grid_aggregation'] = array(
		'grid_interval_stats_header' => array(
			'friendly_name' => __('General Aggregation Settings', 'grid'),
			'collapsible' => 'true',
			'method' => 'spacer',
		),
		'grid_job_wallclock_method' => array(
			'friendly_name' => __('Wall clock Calculation Method', 'grid'),
			'description' => __('There are essential two wall clock aggregation methods.  One that accounts for suspend time and one that does not.  Please select the method that you desire.  Statistics gathering using this method begins after it is selected.', 'grid'),
			'default' => 'wosuspend',
			'method' => 'drop_array',
			'array' => array(
				'wosuspend' => __('EndTime - StartTime', 'grid'),
				'wsuspend' => __('EndTime - StartTime - SuspendTime', 'grid')
			)
		),
		'grid_job_gpu_wallclock_cpuruntime' => array(
			'friendly_name' => __('Force GPU Wall clock time to use Job Runtime', 'grid'),
			'description' => __('Some GPU\'s currently do not support DCGM. Use this setting if you want RTM\'s Daily Statistics to simply use the Wall clock time times the number of GPU\'s to be used to calculate GPU Wall clock time', 'grid'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'grid_job_stats_project_enabled' => array(
			'friendly_name' => __('Should Project Names be Aggregated?', 'grid'),
			'description' => __('Allows you to track what projects are being submitted. As with the project name, large variations can cause the job interval and daily stat\'s table to become large.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_stats_fromhost_enabled' => array(
			'friendly_name' => __('Should Submission Host Information be Aggregated?', 'grid'),
			'description' => __('If you are not interested in where jobs are being submitted from uncheck this box.  Please note that if you have large job volumes being submitted from large quantities of hosts, your job interval and daily stat\'s table can become very large.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_stats_app_enabled' => array(
			'friendly_name' => __('Should Application Profile be Aggregated?', 'grid'),
			'description' => __('When enabled, tracks submitted application profiles. As with the application profile, large variations can cause the job interval and daily stat\'s table to become very large.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_interval_stats_project_header' => array(
			'friendly_name' => __('Project Aggregation', 'grid'),
			'method' => 'spacer',
		),
		'grid_project_group_aggregation' => array(
			'friendly_name' => __('Should Hierarchical Project Aggregation be Enabled?', 'grid'),
			'description' => __('Checking this option will enable Hierarchical Project statistics gathering based upon a predefined Project Hierarchy delimiter. You will then be able to view statistics at multiple levels within the Project Hierarchy.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_stats_project_delimiter' => array(
			'friendly_name' => __('Delimiter', 'grid'),
			'description' => __('If you utilize the Project field to represent a Project Hierarchy, what delimiter do you use to separate each level of the Hierarchy?', 'grid'),
			'method' => 'textbox',
			'default' => '_',
			'size' => '1',
			'max_length' => '2'
		),
		'grid_job_stats_project_level_number' => array(
			'friendly_name' => __('Maximum Aggregation Level', 'grid'),
			'description' => __('Project are oftentimes treated as Hierarchical objects in LSF.  This has been historically done to support Project level Fairshare.  This option determines the highest meaningful level in that Hierarchy.  Select the maximum level of Hierarchy here that is meaningful for reporting.  <strong>All</strong> will potentially generate several thousand graphs that may not be relevant for reporting.', 'grid'),
			'method' => 'drop_array',
			'default' => '3',
			'array' => $grid_projectgroup_filter_levels,
		),
		'grid_interval_stats_ugroup_header' => array(
			'friendly_name' => __('User Group Aggregation Details', 'grid'),
			'method' => 'spacer',
		),
		'grid_ugroup_group_aggregation' => array(
			'friendly_name' => __('Should Hierarchical User Group Aggregation be Enabled?', 'grid'),
			'description' => __('Checking this option will enable Hierarchical User Group statistics gathering based upon a predefined User Group delimiter.  You will then be able to view statistics at multiple levels within the User Group Hierarchy.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_usergroup_method' => array(
			'friendly_name' => __('User Group Aggregation Method', 'grid'),
			'description' => __('There are two ways to Aggregate jobs by User Group.  \'User Group Membership\' will display all jobs from all users in a group. \'Job Specification\' will display only jobs submitted using the bsub -G option.  Please set the method that matches your configuration.', 'grid'),
			'method' => 'drop_array',
			'default' => 'usermap',
			'array' => array(
				'usermap' => __('User Group Membership', 'grid'),
				'jobmap' => __('Job Specification', 'grid')
			)
		),
		'grid_job_stats_ugroup_delimiter' => array(
			'friendly_name' => __('Delimiter', 'grid'),
			'description' => __('If you utilize the User Group field for a User Group Hierarchy, what delimiter do you use to separate each level of the hierarchy?', 'grid'),
			'method' => 'textbox',
			'default' => '_',
			'size' => '1',
			'max_length' => '2'
		),
		'grid_job_stats_ugroup_level_number' => array(
			'friendly_name' => __('Maximum Aggregation Level', 'grid'),
			'description' => __('User Groups can be setup to be Hierarchical in LSF.  This is done mainly to support group based Fairshare scheduling in LSF.  By setting this option, you can create aggregate statistical graphs at each level of the User Group Hierarchy.  Select the maximum level of Hierarchy here that is meaningful for reporting.  <strong>All</strong> will potentially generate several thousand graphs that may not be relevant for reporting.', 'grid'),
			'method' => 'drop_array',
			'default' => '3',
			'array' => $grid_projectgroup_filter_levels,
		),
		'grid_license_project_stats_header' => array(
			'friendly_name' => __('License Project Tracking', 'grid'),
			'method' => 'spacer',
		),
		'grid_license_project_tracking' => array(
			'friendly_name' => __('Should License Project Job Performance be Tracked?', 'grid'),
			'description' => __('This option will allow the tracking of running jobs that are associated with a License Project in LSF.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_stats_group_level_header' => array(
			'friendly_name' => __('Job Group Aggregation', 'grid'),
			'method' => 'spacer',
		),
		'grid_job_group_aggregation' => array(
			'friendly_name' => __('Should Job Group Information be Tracked?', 'grid'),
			'description' => __('Checking this option will enable Job Group statistics to be tracked in RTM.  You will then be able to aggregate statistics at multiple levels of the Job Group hierarchy by setting the option below.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_stats_group_level_number' => array(
			'friendly_name' => __('Maximum Aggregation Level', 'grid'),
			'description' => __('Job Groups are, by their nature, Hierarchical.  By setting this option, you can control to what level in the interface and on the graphs, the various rollup statistics for LSF Job Groups are reported.  Select the maximum level of Hierarchy here that is meaningful for reporting.  Keep in mind that <strong>All</strong> will potentially generate several thousand graphs that may not be relevant for reporting.', 'grid'),
			'method' => 'drop_array',
			'default' => '3',
			'array' => $grid_jgroup_filter_levels,
		),
		'grid_mc_stats_header' => array(
			'friendly_name' => __('MultiCluster Resource Leasing Aggregation', 'grid'),
			'method' => 'spacer',
		),
		'grid_mc_resource_leasing' => array(
			'friendly_name' => __('Resource Leasing Cluster Stats Aggregation', 'grid'),
			'description' => __('If you are using LSF MultiCluster Resource Leasing, statistics at the Cluster level will be inaccurate unless this option is checked.  Please keep in mind that there is a performance penalty for large job volume environments.  Therefore, if you are not using Resource Leasing, you should not check this option.', 'grid'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_job_pend_hostgroup_header' => array(
			'friendly_name' => __('Pend Job on Host group Aggregation', 'grid'),
			'method' => 'spacer',
		),
		'grid_job_pend_hostgroup' => array(
			'friendly_name' => __('Host Group Pending Job Calculation Method', 'grid'),
			'description' => __('There are two ways to calculate host group pending jobs.  Host/Host groups associated with the job - calculates pending jobs for the specified host/host group by the bsub -m command.  Host groups associated with Queues - calculates pending jobs for a host group by virtue of their queue association.  Note: This method of calculating pending jobs can introduce performance problems in very large clusters. Therefore, before implementing, contact support to evaluate your specific case.', 'grid'),
			'method' => 'drop_array',
			'default' => 'disable',
			'array' => array(
				'disable' => __('Disable', 'grid'),
				'hostmap' => __('Host/Host groups associated with the job', 'grid'),
				'queuemap' => __('Host groups associated with queues', 'grid')
			)
		),
	);

	$grid_settings_system['grid_paths'] = array(
		'grid_path_header' => array(
			'friendly_name' => __('RTM Directories/File Paths', 'grid'),
			'method' => 'spacer',
		),
		'grid_logfile' => array(
			'friendly_name' => __('Log File Path', 'grid'),
			'description' => __('Specify the location of the log file that the RTM Binaries will log messages to.<br>Other log messages will still be log to $log_path', 'grid'),
			'method' => 'filepath',
			'default' => $log_path,
			'size' => '60',
			'max_length' => '255'
		),
		'grid_cache_dir' => array(
			'friendly_name' => __('RTM Cache Directory', 'grid'),
			'description' => __('When viewing job detail records, or contention data where should Cacti store your job rusage RRD and Image files?', 'grid'),
			'method' => 'dirpath',
			'default' => '',
			'size' => '60',
			'max_length' => '255'
		)
	);

	$grid_license_forms = array(
		'license_file' => array(
			'friendly_name' => __('Upload License File', 'grid'),
			'description' => __('Please specify your license file.', 'grid'),
			'method' => 'custom',
			'value' => '<input type=\'file\' name=\'license_file\' id=\'license_file\' />'
		),
		'license_cnp' => array(
			'friendly_name' => __('License Text', 'grid'),
			'description' => __('Please copy & paste your license in the textbox.', 'grid'),
			'method' => 'textarea',
			'value' => '|arg1:license_cnp|',
			'textarea_rows' => '20',
			'textarea_cols' => '80',
		),
		'tab' => array(
			'method' => 'hidden',
			'value' => 'license'
		)
	);

	/* combine the settings arrays */
	$settings = array_merge($settings, $grid_settings_system);
	/* please keep the same order as $grid_settings, grid_general timespans visual cluster queue users jobarrays jobdetail  jobexport jobgraphs application jobgroup host projectgroup hgroup ugroup */
	$tabs_grid = array(
		'grid_general' => __('RTM General', 'grid'),
		'timespans'    => __('Timespans', 'grid'),
		'visual'       => __('Visual', 'grid'),
		'cluster'      => __('Clusters', 'grid'),
		'queue'        => __('Queues', 'grid'),
		'users'        => __('Users', 'grid'),
		'jobarrays'    => __('Job Arrays', 'grid'),
		'jobdetail'    => __('Jobs', 'grid'),
		'jobexport'    => __('Job Export', 'grid'),
		'jobgraphs'    => __('Job Graphs', 'grid'),
		'application'  => __('Applications', 'grid'),
		'jobgroup'     => __('Job Groups', 'grid'),
		'host'         => __('Hosts', 'grid'),
		'projectgroup' => __('Project', 'grid'),
		'hgroup'       => __('Host Groups', 'grid'),
		'ugroup'       => __('User Groups', 'grid'),
		'pendignore'   => __('Reasons', 'grid')
	);

	$temp_main_screen = array(
		'grid_summary.php'               => __('Host Dashboard', 'grid'),
		'grid_clusterdb.php'             => __('Cluster Dashboard', 'grid'),
		'grid_dailystats.php'            => __('Daily Statistics', 'grid'),
		'grid_projects.php'              => __('Project View', 'grid'),
		'grid_license_projects.php'      => __('License Project View', 'grid'),
		'grid_bjobs.php?action=viewlist' => __('Jobs List', 'grid'),
		'grid_lsload.php'                => __('Host Load View', 'grid'),
		'grid_bhosts.php'                => __('Jobs by Host', 'grid'),
		'grid_bqueues.php'               => __('Queue View', 'grid')
	);

	$grid_settings = array(
		'grid_general' => array(
			'general_header' => array(
				'friendly_name' => __('General Settings', 'grid'),
				'method' => 'spacer',
			),
			'default_main_screen' => array(
				'friendly_name' => __('Your Main Screen', 'grid'),
				'description' => __('Which RTM Screen do you want to enter by default when selecting the Cluster Tab.', 'grid'),
				'method' => 'drop_array',
				'default' => 'grid_bhosts.php',
				'array' => $grid_main_screen
			),
			'default_grid' => array(
				'friendly_name' => __('Default Cluster', 'grid'),
				'description' => __('The default Cluster to use viewing Cluster Statistics.', 'grid'),
				'method' => 'drop_sql',
				'sql' => 'SELECT clusterid AS id, clustername AS name FROM grid_clusters ORDER BY clustername',
				'none_value' => __('All', 'grid'),
				'default' => '0'
				),
			'default_grid_tz' => array(
				'friendly_name' => __('Default Cluster Time zone', 'grid'),
				'description' => __('When viewing jobs, do you wish to view job event times in the Clusters time zone or the RTM servers?  By checking, you obtain the Clusters.  Note: this feature only works when viewing a single cluster.', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'default_job_status' => array(
				'friendly_name' => __('Default Job Status', 'grid'),
				'description' => __('When you first bring up the jobs display, what would you like to set the status filter to.', 'grid'),
				'method' => 'drop_array',
				'default' => 'RUNNING',
				'array' => array(
					'FINISHED' => __('FINISHED', 'grid'),
					'ACTIVE'   => __('ACTIVE', 'grid'),
					'RUNNING'  => __('RUNNING', 'grid'),
					'PEND'     => __('PEND', 'grid'),
					'DONE'     => __('DONE', 'grid'))
				),
			'default_grid_dynamic' => array(
				'friendly_name' => __('Make Job Filters Dynamic', 'grid'),
				'description' => __('If checked, whenever you change a filter value in the Job Details display, the display will be updated.', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'default_job_status_list' => array(
				'friendly_name' => __('All Job Status Option', 'grid'),
				'description' => __('If checked, user will be allowed to choose \'ALL\' option from the job status dropdown list', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'default_showinact_users' => array(
				'friendly_name' => __('Show Inactive Users', 'grid'),
				'description' => __('When viewing user details, should inactive users be displayed?', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'default_audible_alerting' => array(
				'friendly_name' => __('Audible Alerting', 'grid'),
				'description' => __('Should Audible Alerting be Enabled on the Dashboard?', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'default_blink_alarm_hosts' => array(
				'friendly_name' => __('Show Alert', 'grid'),
				'description' => __('Notify when an alert is triggered for syslog, thold, batchload, gridalarms', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'default_grid_jobs_pageno' => array(
				'friendly_name' => __('Show Page Number', 'grid'),
				'description' => __('Show Page Number for job status filter: "STARTED", "FINISHED", "DONE", "EXIT", "ALL" and "CUSTOM"', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			),
		'timespans' => array(
			'jobgraph_header' => array(
				'friendly_name' => __('Job Graph Settings', 'grid'),
				'method' => 'spacer',
				),
			'default_timespan' => array(
				'friendly_name' => __('Default RTM View Timespan', 'grid'),
				'description' => __('The default timespan you wish to be displayed when you display LSF specific graphs', 'grid'),
				'method' => 'drop_array',
				'array' => $grid_timespans,
				'default' => GT_LAST_DAY
				),
			'default_timeshift' => array(
				'friendly_name' => __('Default Graph View Time shift', 'grid'),
				'description' => __('The default time shift you wish to be displayed when you display graphs', 'grid'),
				'method' => 'drop_array',
				'array' => $grid_jobzoom_timespans,
				'default' => GTS_2_HOURS
				),
			'allow_graph_dates_in_future' => array(
				'friendly_name' => __('Allow Graph to extend to Future', 'grid'),
				'description' => __('When displaying Graphs, allow Graph Dates to extend \'to future\'', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'first_weekdayid' => array(
				'friendly_name' => __('First Day of the Week', 'grid'),
				'description' => __('The first Day of the Week for weekly Graph Displays', 'grid'),
				'method' => 'drop_array',
				'array' => $grid_weekdays,
				'default' => WD_MONDAY
				),
			'day_shift_start' => array(
				'friendly_name' => __('Start of Daily Shift', 'grid'),
				'description' => __('Start Time of the Daily Shift.', 'grid'),
				'method' => 'textbox',
				'size' => '10',
				'default' => '07:00',
				'max_length' => '5'
				),
			'day_shift_end' => array(
				'friendly_name' => __('End of Daily Shift', 'grid'),
				'description' => __('End Time of the Daily Shift.', 'grid'),
				'method' => 'textbox',
				'size' => '10',
				'default' => '18:00',
				'max_length' => '5'
				),
			),
		'visual' => array(
			'visual_header' => array(
				'friendly_name' => __('Visual Settings', 'grid'),
				'method' => 'spacer',
				),
			'refresh_interval' => array(
				'friendly_name' => __('Screen Refresh Interval', 'grid'),
				'description' => __('How often do you wish your screen to refresh with new data by default.', 'grid'),
				'method' => 'drop_array',
				'default' => '60',
				'array' => $grid_refresh_interval,
				),
			'grid_enable_exclusion_filter' => array(
				'friendly_name' => __('Exclusion Filter Status', 'grid'),
				'description' => __('Should the Exclusion Filter be enabled by default.', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'grid_summary_filter' => array(
				'friendly_name' => __('Exclude Host States', 'grid'),
				'description' => __('Which Host states are you not interested in viewing when the \'Exclusion Filter\' is applied?', 'grid'),
				'method' => 'drop_multi',
				'array' => $grid_summary_filters,
				'sql' => 'SELECT value AS id, value FROM grid_settings WHERE name LIKE "grid_filter%%"',
				),
			'grid_records' => array(
				'friendly_name' => __('Number of Records to Display', 'grid'),
				'description' => __('How many results do you want to display in tables by default.', 'grid'),
				'method' => 'drop_array',
				'default' => '30',
				'array' => $grid_display_rows_selector,
				),
			'max_length_jobname' => array(
				'friendly_name' => __('Maximum Display Length of Job Name', 'grid'),
				'description' => __('The maximum display length of job name in list view of job info. The maximum value should be less than 120.', 'grid'),
				'method' => 'textbox',
				'size' => '10',
				'max_length' => '20',
				'default' => '20'
				),
			'grid_pie_hostname_count' => array(
				'friendly_name' => __('Maximum Display Count of Host Name in Pie Graphs', 'grid'),
				'description' => __('The maximum display count of host name in Pie graphs in Cluster Dashboard (Grid > Dashboards > Cluster). It is ordered by the UT in descending. Default is 10. To avoid performance issue, the maximum value should be less than 100.', 'grid'),
				'method' => 'textbox',
				'size' => '3',
				'max_length' => '3',
				'default' => '10'
				)
			),
		'cluster' => array(
			'summary_header' => array(
				'friendly_name' => __('Cluster Summary', 'grid'),
				'method' => 'spacer',
				),
			'show_cluster_slot_percent' => array(
				'friendly_name' => __('Slot Percent Utilization', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_cluster_efic_status' => array(
				'friendly_name' => __('Efficiency Status', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_cluster_efic_percent' => array(
				'friendly_name' => __('Efficiency Percent', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_badmin_reconfig_time' => array(
				'friendly_name' => __('Badmin Reconfig Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_thold_alarm' => array(
				'friendly_name' => __('Active Tholds/Alerts', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'status_header' => array(
				'friendly_name' => __('Cluster Status', 'grid'),
				'method' => 'spacer',
				),
			'show_cluster_servers' => array(
				'friendly_name' => __('Total Hosts', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_cluster_clients' => array(
				'friendly_name' => __('Total Clients', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'master_header' => array(
				'friendly_name' => __('Master Status', 'grid'),
				'method' => 'spacer',
				),
			'show_cluster_master_host_r15sec' => array(
				'friendly_name' => __('RunQ 15sec', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_r1m' => array(
				'friendly_name' => __('RunQ 1min', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_r15m' => array(
				'friendly_name' => __('RunQ 15min', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_ut' => array(
				'friendly_name' => __('CPU %%%', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_pagerate' => array(
				'friendly_name' => __('Page Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_iorate' => array(
				'friendly_name' => __('I/O Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_logins' => array(
				'friendly_name' => __('Current Logins', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_idle_time' => array(
				'friendly_name' => __('Idle Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_temp' => array(
				'friendly_name' => __('Temp Available', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_cluster_master_host_mem' => array(
				'friendly_name' => __('Memory Available', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'queue' => array(
			'queue_header' => array(
				'friendly_name' => __('General', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_clustername' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_description' => array(
				'friendly_name' => __('Description', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_attribs' => array(
				'friendly_name' => __('Queue Attributes', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_nice' => array(
				'friendly_name' => __('Nice', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_priority' => array(
				'friendly_name' => __('Priority', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'queue_stats' => array(
				'friendly_name' => __('Performance', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_efficiency' => array(
				'friendly_name' => __('Job Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_total_cpu' => array(
				'friendly_name' => __('Job Total CPU', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_max_memory' => array(
				'friendly_name' => __('Maximum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_avg_memory' => array(
				'friendly_name' => __('Average Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_max_swap' => array(
				'friendly_name' => __('Maximum VM Size', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_avg_swap' => array(
				'friendly_name' => __('Average VM Size', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'queue_limits' => array(
				'friendly_name' => __('Job Limits', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_host_slots' => array(
				'friendly_name' => __('Total Host %s', format_job_slots(false), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_maxjobs' => array(
				'friendly_name' => __('Queue Job Slot Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_userjobs' => array(
				'friendly_name' => __('Per User Job Slot Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_hostjobs' => array(
				'friendly_name' => __('Per Host Job Slot Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_min_procjobs' => array(
				'friendly_name' => __('Minimum %s Per Job', format_job_slots(false,true), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_def_procjobs' => array(
				'friendly_name' => __('Default %s Per Job', format_job_slots(false,true), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_procjobs' => array(
				'friendly_name' => __('Maximum %s Per Job', format_job_slots(false,true), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'queue_distrib' => array(
				'friendly_name' => __('Slot Distribution', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_shareddedicated' => array(
				'friendly_name' => __('Shared and Dedicated Slot Stats', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'queue_tis' => array(
				'friendly_name' => __('Time in State Information', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_pend_times' => array(
				'friendly_name' => __('Pending Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_queue_run_times' => array(
				'friendly_name' => __('Run Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_queue_psusp_times' => array(
				'friendly_name' => __('Suspended while Pending (PSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_ssusp_times' => array(
				'friendly_name' => __('Suspended by System (SSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_ususp_times' => array(
				'friendly_name' => __('Suspended by User (USUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'queue_other' => array(
				'friendly_name' => __('Other Information', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_run_window' => array(
				'friendly_name' => __('Run Window', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_dispatch_window' => array(
				'friendly_name' => __('Dispatch Window', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_res_req' => array(
				'friendly_name' => __('Resource Requirements', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'queue_mc' => array(
				'friendly_name' => __('MultiCluster Information', 'grid'),
				'method' => 'spacer',
				),
			'show_queue_send_to' => array(
				'friendly_name' => __('Send Jobs To', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_recv_from' => array(
				'friendly_name' => __('Receive Jobs From', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				)
		),
		'users' => array(
			'Users_header' => array(
				'friendly_name' => __('Show Users columns', 'grid'),
				'method' => 'spacer',
			),
			'show_user_clustername' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_maxSlots' => array(
				'friendly_name' => __('Max Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_maxpendjob' => array(
				'friendly_name' => __('Max Pending', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_numSlots' => array(
				'friendly_name' => __('Num Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_started_slots' => array(
				'friendly_name' => __('Started Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_pendingslots' => array(
				'friendly_name' => __('Pending Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_runningslots' => array(
				'friendly_name' => __('Running Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_procjoblimit' => array(
				'friendly_name' => __('Processed Limit', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_efficiency' => array(
				'friendly_name' => __('Efficiency(%%%)', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_syssusslots' => array(
				'friendly_name' => __('System Suspend Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_usersusslots' => array(
				'friendly_name' => __('User Suspend Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_reserveslots' => array(
				'friendly_name' => __('Reserve Slots', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'Users_Queues' => array(
				'friendly_name' => __('Show Queue users columns ', 'grid'),
				'method' => 'spacer',
			),
			'show_user_shares' => array(
				'friendly_name' => __('User Shares', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_priority' => array(
				'friendly_name' => __('User Priority', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_userjoblimit' => array(
				'friendly_name' => __('User Limit', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_cputime' => array(
				'friendly_name' => __('Cpu Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_user_runtime' => array(
				'friendly_name' => __('Run Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'jobarrays' => array(
			'jobarrays_header' => array(
				'friendly_name' => __('Show Job Array Detail Columns', 'grid'),
				'method' => 'spacer',
				),
			'show_Ajobname' => array(
				'friendly_name' => __('Job Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Asubmit_time' => array(
				'friendly_name' => __('Submit Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_AuserGroup' => array(
				'friendly_name' => __('User Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Auser' => array(
				'friendly_name' => __('User', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Aqueue' => array(
				'friendly_name' => __('Queue', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Aproject' => array(
				'friendly_name' => __('Project', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'jobarrays_state_header' => array(
				'friendly_name' => __('Job Status Data', 'grid'),
				'method' => 'spacer',
				),
			'show_Ajobs'  => array(
				'friendly_name' => __('Total Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Apend'  => array(
				'friendly_name' => __('Pending Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Arunning'  => array(
				'friendly_name' => __('Running Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Adone'  => array(
				'friendly_name' => __('Done Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Aexit'  => array(
				'friendly_name' => __('Exited Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Assusp' => array(
				'friendly_name' => __('System Suspended (SSUSP) Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Aususp' => array(
				'friendly_name' => __('User Suspended (USUSP) Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Apsusp' => array(
				'friendly_name' => __('Pre-Exec Suspended (PSUSP) Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Aefficiency' => array(
				'friendly_name' => __('Job Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_AminMemory' => array(
				'friendly_name' => __('Minimum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_AmaxMemory' => array(
				'friendly_name' => __('Maximum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_AavgMemory' => array(
				'friendly_name' => __('Average Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_AminSwap' => array(
				'friendly_name' => __('Minimum Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_AmaxSwap' => array(
				'friendly_name' => __('Maximum Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_AavgSwap' => array(
				'friendly_name' => __('Average Swap', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Atotalcpu' => array(
				'friendly_name' => __('Total CPU Usage', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_Atotalutime' => array(
				'friendly_name' => __('Total CPU User Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_Atotalstime' => array(
				'friendly_name' => __('Total CPU System Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
		),
		'jobdetail' => array(
			'jobdetail_header' => array(
				'friendly_name' => __('Show Job Detail Columns', 'grid'),
				'method' => 'spacer',
				),
			'show_jobclusterid' => array(
				'friendly_name' => __('Cluster ID', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_jobclustername' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_jobname' => array(
				'friendly_name' => __('Job Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_queue' => array(
				'friendly_name' => __('Queue Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_sla' => array(
				'friendly_name' => __('Service Level (SLA)', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_user' => array(
				'friendly_name' => __('User Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_ugroup' => array(
				'friendly_name' => __('User Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_chargedsaap' => array(
				'friendly_name' => __('Charged SAAP', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_project' => array(
				'friendly_name' => __('Project Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_lproject' => array(
				'friendly_name' => __('License Project', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_app' => array(
				'friendly_name' => __('Application', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_jgroup' => array(
				'friendly_name' => __('Job Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_nice' => array(
				'friendly_name' => __('Default Nice Level', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_req_memory' => array(
				'friendly_name' => __('Requested Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_reserve_memory' => array(
				'friendly_name' => __('Reserved Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_wasted_memory' => array(
				'friendly_name' => __('Wasted Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_max_memory' => array(
				'friendly_name' => __('Max Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_max_swap' => array(
				'friendly_name' => __('Max V.Memory Size', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_cur_memory' => array(
				'friendly_name' => __('Current Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_cur_swap' => array(
				'friendly_name' => __('Current V.Memory Size', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_cur_gpu_memory' => array(
				'friendly_name' => __('Current GPU Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_gpu_max_memory' => array(
				'friendly_name' => __('Max GPU Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_exit' => array(
				'friendly_name' => __('Exit Code', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_state_changes' => array(
				'friendly_name' => __('State Changes', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_nodes_cpus' => array(
				'friendly_name' => __('Nodes/CPUs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_max_processors' => array(
				'friendly_name' => __('Max Processors', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_gpus' => array(
				'friendly_name' => __('GPUs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_runtime_estimation' => array(
				'friendly_name' => __('Runtime Estimation', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_exec_host' => array(
				'friendly_name' => __('Execution Host(s)', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_exec_host_type' => array(
				'friendly_name' => __('Execution Host Type', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_exec_host_model' => array(
				'friendly_name' => __('Execution Host Model', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_from_host' => array(
				'friendly_name' => __('Submission Host', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_queue_limit' => array(
				'friendly_name' => __('Queue %s Used/Limit', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_user_queue_limit' => array(
				'friendly_name' => __('User/Queue %s Used/Limit', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_user_cpu_limit' => array(
				'friendly_name' => __('User %s Used/Limit', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_run_pend_jobs' => array(
				'friendly_name' => __('Number of Run/Pend %s for User', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_avail_cpu' => array(
				'friendly_name' => __('Available/Total %s for Queue', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'jobtime_header' => array(
				'friendly_name' => __('Job Time State Options', 'grid'),
				'method' => 'spacer',
				),
			'show_submit_time' => array(
				'friendly_name' => __('Submit Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_start_time' => array(
				'friendly_name' => __('Start Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_end_time' => array(
				'friendly_name' => __('End Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_pend_time' => array(
				'friendly_name' => __('Pending Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_ineligiblependingtime' => array(
				'friendly_name' => __('Ineligible Pending Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_effectivependingtimelimit' => array(
				'friendly_name' => __('Effective Pending Time Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_effectiveeligiblependingtimelimit' => array(
				'friendly_name' => __('Effective Eligible Pending Time Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_run_time' => array(
				'friendly_name' => __('Run Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_gpu_exec_time' => array(
				'friendly_name' => __('GPU Execution Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_psusp_time' => array(
				'friendly_name' => __('Pre-Exec Pending (PSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_ssusp_time' => array(
				'friendly_name' => __('System Suspend (SSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'show_ususp_time' => array(
				'friendly_name' => __('User Suspend (USUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_unkwn_time' => array(
				'friendly_name' => __('Unknown Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'show_prov_time' => array(
				'friendly_name' => __('Total Prov Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				)
		),
		'jobexport' => array(
			'jobdetail_header' => array(
				'friendly_name' => __('Job Submission', 'grid'),
				'method' => 'spacer',
				),
			'export_clusterid' => array(
				'friendly_name' => __('Cluster ID', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_clustername' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_jobname' => array(
				'friendly_name' => __('Job Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_jobdescription' => array(
				'friendly_name' => __('Job Description', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_project' => array(
				'friendly_name' => __('Project Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_lproject' => array(
				'friendly_name' => __('License Project', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_queue' => array(
				'friendly_name' => __('Queue Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_user' => array(
				'friendly_name' => __('User Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_ugroup' => array(
				'friendly_name' => __('User Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_chargedsaap' => array(
				'friendly_name' => __('Charged SAAP', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_sla' => array(
				'friendly_name' => __('SLA Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_pgroup' => array(
				'friendly_name' => __('Parent Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_app' => array(
				'friendly_name' => __('Application', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_jgroup' => array(
				'friendly_name' => __('Job Group', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'jobcmdrelated_header' => array(
				'friendly_name' => __('Job Command Related', 'grid'),
				'method' => 'spacer',
				),
			'export_input_file' => array(
				'friendly_name' => __('Input File', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_output_file' => array(
				'friendly_name' => __('Output File', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_error_file' => array(
				'friendly_name' => __('Error File', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_command' => array(
				'friendly_name' => __('Job Command', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'jobenviron_header' => array(
				'friendly_name' => __('Job Environment', 'grid'),
				'method' => 'spacer',
				),
			'export_from_host' => array(
				'friendly_name' => __('Submission Host', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_exec_host' => array(
				'friendly_name' => __('Execution Host(s)', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_nice' => array(
				'friendly_name' => __('Default Nice Level', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_nodes_cpus' => array(
				'friendly_name' => __('Nodes/CPUs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_max_processors' => array(
				'friendly_name' => __('Max Processors', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_gpus' => array(
				'friendly_name' => __('GPUs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_runtime_estimation' => array(
				'friendly_name' => __('Runtime Estimation', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_resource_requirement' => array(
				'friendly_name' => __('Resource Requirement', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_combinedresreq' => array(
				'friendly_name' => __('Combined ResReq', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_effectiveresreq' => array(
				'friendly_name' => __('Effective ResReq', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_gpuresreq' => array(
				'friendly_name' => __('GPU ResReq', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_gpucombinedresreq' => array(
				'friendly_name' => __('Combined GPU ResReq', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_gpueffectiveresreq' => array(
				'friendly_name' => __('Effective GPU ResReq', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_jobperf_header' => array(
				'friendly_name' => __('Job Performance', 'grid'),
				'method' => 'spacer',
				),
			'export_status' => array(
				'friendly_name' => __('Status', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_exit' => array(
				'friendly_name' => __('Exit Code', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_exitinfo' => array(
				'friendly_name' => __('Exit Info', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_req_memory' => array(
				'friendly_name' => __('Requested Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_reserve_memory' => array(
				'friendly_name' => __('Reserved Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_wasted_memory' => array(
				'friendly_name' => __('Wasted Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_cur_gpu_memory' => array(
				'friendly_name' => __('Current GPU Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_gpu_max_memory' => array(
				'friendly_name' => __('Max GPU Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_jobtime_header' => array(
				'friendly_name' => __('Time Details', 'grid'),
				'method' => 'spacer',
				),
			'export_submit_time' => array(
				'friendly_name' => __('Submit Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_start_time' => array(
				'friendly_name' => __('Start Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_end_time' => array(
				'friendly_name' => __('End Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_pend_time' => array(
				'friendly_name' => __('Pending Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_ineligiblependingtime' => array(
				'friendly_name' => __('Ineligible Pending Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_pendstate' => array(
				'friendly_name' => __('Ineligible Pending Reason?', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_effectivependingtimelimit' => array(
				'friendly_name' => __('Effective Pending Time Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_effectiveeligiblependingtimelimit' => array(
				'friendly_name' => __('Effective Eligible Pending Time Limit', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_run_time' => array(
				'friendly_name' => __('Run Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_gpu_exec_time' => array(
				'friendly_name' => __('GPU Execution Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_psusp_time' => array(
				'friendly_name' => __('Pre-Exec Pending (PSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_ssusp_time' => array(
				'friendly_name' => __('System Suspend (SSUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'export_ususp_time' => array(
				'friendly_name' => __('User Suspend (USUSP) Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_unkwn_time' => array(
				'friendly_name' => __('Unknown Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'export_prov_time' => array(
				'friendly_name' => __('Total PROV Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				)
		),
		'jobgraphs' => array(
			'jobgraphs_header0' => array(
				'friendly_name' => __('Graph Size', 'grid'),
				'method' => 'spacer',
				),
			'job_graph_columns' => array(
				'friendly_name' => __('Graph Columns', 'grid'),
				'method' => 'drop_array',
				'default' => '1',
				'array' => array(1 => '1', 2 => '2')
				),
			'job_graph_type' => array(
				'friendly_name' => __('Graph Type', 'grid'),
				'method' => 'drop_array',
				'default' => '1',
				'array' => array(
					1 => __('Area', 'grid'),
					2 => __('Line', 'grid')
					)
				),
			'job_graph_width' => array(
				'friendly_name' => __('Width', 'grid'),
				'method' => 'textbox',
				'size' => '10',
				'default' => '600',
				'max_length' => '10'
				),
			'job_graph_height' => array(
				'friendly_name' => __('Height', 'grid'),
				'method' => 'textbox',
				'size' => '10',
				'default' => '170',
				'max_length' => '10'
				),
			'jobgraphs_header1' => array(
				'friendly_name' => __('Graph Colors', 'grid'),
				'method' => 'spacer',
				),
			'job_graph_mem_color' => array(
				'friendly_name' => __('Physical Memory', 'grid'),
				'method' => 'drop_color',
				'default' => '49'
				),
			'job_graph_swap_color' => array(
				'friendly_name' => __('Swap Memory', 'grid'),
				'method' => 'drop_color',
				'default' => '15'
				),
			'job_graph_stime_color' => array(
				'friendly_name' => __('System Time', 'grid'),
				'method' => 'drop_color',
				'default' => '24'
				),
			'job_graph_utime_color' => array(
				'friendly_name' => __('User Time', 'grid'),
				'method' => 'drop_color',
				'default' => '57'
				),
			'job_graph_pids_color' => array(
				'friendly_name' => __('PIDS', 'grid'),
				'method' => 'drop_color',
				'default' => '42'
				),
			'job_graph_pgids_color' => array(
				'friendly_name' => __('PGIDS', 'grid'),
				'method' => 'drop_color',
				'default' => '57'
				),
			'job_graph_threads_color' => array(
				'friendly_name' => __('Threads', 'grid'),
				'method' => 'drop_color',
				'default' => '77'
				),
			'job_graph_slots_color' => array(
				'friendly_name' => __('Slots', 'grid'),
				'method' => 'drop_color',
				'default' => '42'
				),
			"job_graph_gut_color" => array(
					"friendly_name" => "GPU Utilization",
					"method" => "drop_color",
					"default" => "55"
				),
			"job_graph_gmut_color" => array(
					"friendly_name" => "GPU Memory Utilization",
					"method" => "drop_color",
					"default" => "88"
				),
			"job_graph_gmem_color" => array(
				"friendly_name" => "Physical GPU Memory",
				"method" => "drop_color",
				"default" => "49"
				)
		),
		'application' => array(
			'gapp_header' => array(
				'friendly_name' => __('Application Columns', 'grid'),
				'method' => 'spacer'
			),
			'app_cluster' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_jobs' => array(
				'friendly_name' => __('Total Active Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_pend' => array(
				'friendly_name' => __('Total Pending Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_running' => array(
				'friendly_name' => __('Total Running Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_effic' => array(
				'friendly_name' => __('Average Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_maxmem' => array(
				'friendly_name' => __('Maximum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'app_avgmem' => array(
				'friendly_name' => __('Average Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'app_maxswap' => array(
				'friendly_name' => __('Maximum Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'app_avgswap' => array(
				'friendly_name' => __('Average Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'app_totalcpu' => array(
				'friendly_name' => __('Total CPU', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'jobgroup' => array(
			'gjgroup_header' => array(
				'friendly_name' => __('Job Group Columns', 'grid'),
				'method' => 'spacer'
			),
			'jgroup_cluster' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_jobs' => array(
				'friendly_name' => __('Total Active Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_pend' => array(
				'friendly_name' => __('Total Pending Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_running' => array(
				'friendly_name' => __('Total Running Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_ssusp' => array(
				'friendly_name' => __('Total SSUSP Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_ususp' => array(
				'friendly_name' => __('Total USUSP Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_effic' => array(
				'friendly_name' => __('Average Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_maxmem' => array(
				'friendly_name' => __('Maximum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'jgroup_avgmem' => array(
				'friendly_name' => __('Average Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'jgroup_maxswap' => array(
				'friendly_name' => __('Maximum Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'jgroup_avgswap' => array(
				'friendly_name' => __('Average Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'jgroup_totalcpu' => array(
				'friendly_name' => __('Total CPU', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'host' => array(
			'ghost_header' => array(
				'friendly_name' => __('General Host Columns', 'grid'),
				'method' => 'spacer'
			),
			'host_cluster' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_type' => array(
				'friendly_name' => __('Host Type', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_model' => array(
				'friendly_name' => __('Host Model', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_status' => array(
				'friendly_name' => __('Host Status', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'show_time_in_state' => array(
				'friendly_name' => __('Time In State (TIS)', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_ngpus' => array(
				'friendly_name' => __('GPU Count', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'bhost_header' => array(
				'friendly_name' => __('Host Job Info Columns', 'grid'),
				'method' => 'spacer'
			),
			'host_cpu_factor' => array(
				'friendly_name' => __('CPU Factor', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_gMaxFactor' => array(
				'friendly_name' => __('GPU Factor', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'host_max_slots' => array(
				'friendly_name' => __('Maximum Configured ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_num_slots' => array(
				'friendly_name' => __('Total Active ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_run_slots' => array(
				'friendly_name' => __('Running ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_ssusp_slots' => array(
				'friendly_name' => __('System Suspended (SSUSP) ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_ususp_slots' => array(
				'friendly_name' => __('User Suspended (USUSP) ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_reserve_slots' => array(
				'friendly_name' => __('Reserve ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_header1' => array(
				'friendly_name' => __('Host Load Info Columns', 'grid'),
				'method' => 'spacer'
			),
			'host_r15sec' => array(
				'friendly_name' => __('RunQ 15sec', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_r1m' => array(
				'friendly_name' => __('RunQ 1min', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_r15m' => array(
				'friendly_name' => __('RunQ 15min', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_ut' => array(
				'friendly_name' => __('CPU %%%', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_pagerate' => array(
				'friendly_name' => __('Page Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_iorate' => array(
				'friendly_name' => __('I/O Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_logins' => array(
				'friendly_name' => __('Current Logins', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_idle_time' => array(
				'friendly_name' => __('Idle Time', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_temp' => array(
				'friendly_name' => __('Temp Available', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_swap' => array(
				'friendly_name' => __('Swap Available', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'host_mem' => array(
				'friendly_name' => __('Memory Available', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'projectgroup' => array(
			'pgroup_header' => array(
				'friendly_name' => __('Common Project Group Columns (Updated Every 5 Minutes)', 'grid'),
				'method' => 'spacer'
			),
			'pgroup_num_jobs' => array(
				'friendly_name' => __('Total ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_num_pend' => array(
				'friendly_name' => __('Pending ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_num_run' => array(
				'friendly_name' => __('Running ' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_avg_effic' => array(
				'friendly_name' => __('Average Job Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_max_mem' => array(
				'friendly_name' => __('Maximum Job Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_avg_mem' => array(
				'friendly_name' => __('Average Job Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_max_swap' => array(
				'friendly_name' => __('Maximum Swap Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_avg_swap' => array(
				'friendly_name' => __('Average Swap Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'pgroup_total_cpu' => array(
				'friendly_name' => __('Total Running CPU', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			)
		),
		'hgroup' => array(
			'hgroup_header' => array(
				'friendly_name' => __('Host Group Columns', 'grid'),
				'method' => 'spacer',
				),
			'hgroup_cluster' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'hgroup_hosts' => array(
				'friendly_name' => __('Total Hosts', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
				),
			'hgroup_oos' => array(
				'friendly_name' => __('Out Of Service(%%%)', 'grid'),
				'method' => 'checkbox',
				'default' => ''
				),
			'hgroup_totalcpu' => array(
				'friendly_name' => __('Total CPU', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_job_header' => array(
				'friendly_name' => __('Host Group Job Data', 'grid'),
				'method' => 'spacer',
				),
			'hgroup_sumjobs' => array(
				'friendly_name' => __('Max Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_jobs' => array(
				'friendly_name' => __('Total Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_running' => array(
				'friendly_name' => __('Running Jobs', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_ssusp_slots' => array(
				'friendly_name' => __('System Suspended (SSUSP) %s', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_ususp_slots' => array(
				'friendly_name' => __('User Suspended (USUSP) %s', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_reserve_slots' => array(
				'friendly_name' => __('Reserve %s', format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_load_header' => array(
				'friendly_name' => __('Host Group Load Data', 'grid'),
				'method' => 'spacer',
				),
			'hgroup_avgut' => array(
				'friendly_name' => __('Average UT', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_avgr15sec' => array(
				'friendly_name' => __('Average r15s', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_avgr1m' => array(
				'friendly_name' => __('Average r1m', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_avgr15m' => array(
				'friendly_name' => __('Average r15m', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_effic' => array(
				'friendly_name' => __('Average Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_pagerate' => array(
				'friendly_name' => __('Average Paging Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_iorate' => array(
				'friendly_name' => __('Average Local I/O Rate', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_logins' => array(
				'friendly_name' => __('Total Login Sessions', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_idle_time' => array(
				'friendly_name' => __('Average Idle Time', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_avgtemp' => array(
				'friendly_name' => __('Average Free Temp', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_avgmem' => array(
				'friendly_name' => __('Average Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_maxmem' => array(
				'friendly_name' => __('Maximum Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'hgroup_avgswp' => array(
				'friendly_name' => __('Average Free Swap', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'hgroup_maxswp' => array(
				'friendly_name' => __('Maximum Virtual Memory', 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
		),
		'ugroup' => array(
			'ugroup_header' => array(
				'friendly_name' => __('User Group Columns', 'grid'),
				'method' => 'spacer'
			),
			'ugroup_cluster' => array(
				'friendly_name' => __('Cluster Name', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_jobs' => array(
				'friendly_name' => __('Total %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_run' => array(
				'friendly_name' => __('Running %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => ''
			),
			'ugroup_num_pend' => array(
				'friendly_name' => __('Pending %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_efficiency' => array(
				'friendly_name' => __('Job Efficiency', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_avg_mem' => array(
				'friendly_name' => __('Average Job Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_max_mem' => array(
				'friendly_name' => __('Maximum Job Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_avg_swap' => array(
				'friendly_name' => __('Average Job Virtual Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_max_swap' => array(
				'friendly_name' => __('Maximum Job Virtual Memory', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_total_cpu' => array(
				'friendly_name' => __('Total Running CPU', 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_user_header' => array(
				'friendly_name' => __('User Group Membership Associated Columns (Updated Frequently)', 'grid'),
				'method' => 'spacer'
			),
			'ugroup_max_jobs' => array(
				'friendly_name' => __('Defined Maximum %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_start_jobs' => array(
				'friendly_name' => __('Started %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_max_pendjob' => array(
				'friendly_name' => __('Maximum Pending/ProcLimit %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_ssusp' => array(
				'friendly_name' => __('System Suspended (SSUSP) %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_ususp' => array(
				'friendly_name' => __('User Suspended (USUSP) %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
			'ugroup_num_reserve' => array(
				'friendly_name' => __('Reserved %s' . format_job_slots(), 'grid'),
				'method' => 'checkbox',
				'default' => 'on'
			),
		)
	);
	if(read_config_option('grid_global_opts_jobs_pageno', true) != 'on'){
		unset($grid_settings['grid_general']['default_grid_jobs_pageno']);
	}
	if(read_config_option('grid_global_opts_jobs_status_all', true) != 'on'){
		unset($grid_settings['grid_general']['default_job_status_list']);
	}
}

function grid_draw_navigation_text($nav) {
	global $config;

	$grid_default = get_request_var('grid_default', 'grid_default.php');
	if (strpos($grid_default, 'plugins/grid/') === false) {
		$grid_default = $config['url_path'] . $grid_default;
	}

	$nav['grid_default.php:'] = array(
		'title' => __('Cluster'),
		'mapping' => '',
		'url' => 'grid_default.php',
		'level' => '0');

	$nav[basename($grid_default) . ':'] = array(
		'title' => __('Cluster', 'grid'),
		'mapping' => '',
		'url' => $grid_default,
		'level' => '0');

	$nav['grid_clusterdb.php:'] = array(
		'title' => __('Cluster Dashboard', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_clusterdb.php:',
		'level' => '1');

	$nav['grid_clusterdb.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => $grid_default . ':,grid_clusterdb.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_summary.php:'] = array(
		'title' => __('Host Dashboard', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_summary.php:',
		'level' => '1');

	$nav['grid_dailystats.php:'] = array(
		'title' => __('Job History Daily Statistics', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_dailystats.php',
		'level' => '1');

	$nav['grid_shared.php:'] = array(
		'title' => __('Shared Resources', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_shared.php',
		'level' => '1');

	$nav['grid_shared.php:viewshared'] = array(
		'title' => __('Shared Resources', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_shared.php',
		'level' => '1');

	$nav['grid_clusters.php:'] = array(
		'title' => __('Clusters', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_clusters.php',
		'level' => '1');

	$nav['grid_clusters.php:edit'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:,grid_clusters.php:',
		'url' => 'grid_clusters.php',
		'level' => '2');

	$nav['grid_clusters.php:save'] = array(
		'title' => __('(Save)', 'grid'),
		'mapping' => 'index.php:,grid_clusters.php:',
		'url' => 'grid_clusters.php',
		'level' => '2');

	$nav['grid_clusters.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_clusters.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_manage_hosts.php:'] = array(
		'title' => __('Manage Hosts', 'grid'),
		'mapping' => 'index.php:,grid_utilities.php:',
		'url' => 'grid_manage_hosts.php',
		'level' => '2');

	$nav['grid_manage_hosts.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_utilities.php:,grid_manage_hosts.php:',
		'url' => '',
		'level' => '3');

	$nav['grid_pollers.php:'] = array(
		'title' => __('RTM Pollers', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_pollers.php',
		'level' => '1');

	$nav['grid_pollers.php:edit'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:,grid_pollers.php:',
		'url' => 'grid_pollers.php',
		'level' => '2');

	$nav['grid_pollers.php:save'] = array(
		'title' => __('(Save)', 'grid'),
		'mapping' => 'index.php:,grid_pollers.php:',
		'url' => 'grid_pollers.php',
		'level' => '2');

	$nav['grid_pollers.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_pollers.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_bzen.php:zoom'] = array(
		'title' => __('Host Based Jobs Viewer', 'grid'),
		'mapping' => $grid_default . ':,grid_bjobs.php:viewlist',
		'url' => 'grid_bzen.php',
		'level' => '2');

	$nav['grid_bjobs.php:'] = array(
		'title' => __('View Job Listing', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjobs.php:viewlist',
		'level' => '1');

	$nav['grid_bjobs.php:viewlist'] = array(
		'title' => __('View Job Listing', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjobs.php',
		'level' => '1');

	$nav['grid_bjobs.php:viewjob'] = array(
		'title' => __('View Job Detail', 'grid'),
		'mapping' => $grid_default . ':,grid_bjobs.php:viewlist',
		'url' => 'grid_bjobs.php:viewlist',
		'level' => '2');

	$nav['grid_bjobs.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => $grid_default . ':,grid_bjobs.php',
		'url' => '',
		'level' => '2');

	$nav['grid_bjobs.php:id'] = array(
		'title' => __('View Job Detail', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjobs.php:id',
		'level' => '1');

	$nav['grid_barrays.php:'] = array(
		'title' => __('Job Array Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_barrays.php:viewlist',
		'level' => '1');

	$nav['grid_barrays.php:viewarray'] = array(
		'title' => __('View Job Array Listing', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_barrays.php',
		'level' => '1');

	$nav['grid_bhosts.php:'] = array(
		'title' => __('Host Job Statistics Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bhosts.php',
		'level' => '1');

	$nav['grid_bhosts.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => $grid_default . ':,grid_bhosts.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_bhosts_closed.php:'] = array(
		'title' => __('Closed Batch Hosts', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bhosts.php',
		'level' => '1');

	$nav['grid_bhosts_closed.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => $grid_default . ':,grid_bhosts.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_queue_distrib.php:'] = array(
		'title' => __('Batch Host Queue Distribution', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_queue_distrib.php',
		'level' => '1');

	$nav['grid_bhgroups.php:'] = array(
		'title' => __('Host Group Job Statistics', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bhgroups.php',
		'level' => '1');

	$nav['grid_bqueues.php:'] = array(
		'title' => __('Queue Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bqueues.php',
		'level' => '1');

	$nav['grid_bqueues.php:viewqueue'] = array(
		'title' => __('Queue Details', 'grid'),
		'mapping' => $grid_default . ':,grid_bqueues.php:',
		'url' => 'grid_bqueues.php:',
		'level' => '2');

	$nav['grid_bqueues.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => $grid_default . ':,grid_bqueues.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_projects.php:'] = array(
		'title' => __('Project Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_projects.php',
		'level' => '1');

	$nav['grid_projects.php:viewprojects'] = array(
		'title' => __('Project Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_projects.php',
		'level' => '1');

	$nav['grid_license_projects.php:'] = array(
		'title' => __('License Project Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_license_projects.php',
		'level' => '1');

	$nav['grid_license_projects.php:viewprojects'] = array(
		'title' => __('License Project Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_license_projects.php',
		'level' => '1');

	$nav['grid_bapps.php:'] = array(
		'title' => __('Application Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bapps.php',
		'level' => '1');

	$nav['grid_bapps.php:viewapps'] = array(
		'title' => __('Application Listing', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bapps.php',
		'level' => '1');

	$nav['grid_bjgroups.php:'] = array(
		'title' => __('Job Group Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjgroups.php',
		'level' => '1');

	$nav['grid_bjgroups.php:viewjgroups'] = array(
		'title' => __('View Job Group Listing', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjgroupys.php',
		'level' => '1');

	$nav['grid_bjgroup.php:'] = array(
		'title' => __('Group Queue Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bjgroup.php',
		'level' => '1');

	$nav['grid_lsload.php:'] = array(
		'title' => __('Host Load Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_lsload.php',
		'level' => '1');

	$nav['grid_lsgload.php:'] = array(
		'title' => __('Group Load Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_lsgload.php',
		'level' => '1');

	$nav['grid_lshosts.php:'] = array(
		'title' => __('Server Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_lshosts.php',
		'level' => '1');

	$nav['grid_lshosts.php:view']= array(
		'title' => __('Server Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_lshosts.php',
		'level' => '1');

	$nav['grid_clients.php:'] = array(
		'title' => __('Client Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_clients.php',
		'level' => '1');

	$nav['grid_bmgroup.php:'] = array(
		'title' => __('Host Group Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bmgroup.php',
		'level' => '1');

	$nav['grid_bhpart.php:'] = array(
		'title' => __('Host Partition Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bhpart.php',
		'level' => '1');

	$nav['grid_busers.php:'] = array(
		'title' => __('User Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_busers.php',
		'level' => '1');

	$nav['grid_bugroup.php:'] = array(
		'title' => __('User Groups Viewer', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bugroup.php',
		'level' => '1');

	$nav['grid_params.php:'] = array(
		'title' => __('Parameters', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_params.php',
		'level' => '1');

	$nav['grid_settings_system.php:'] = array(
		'title' => __('RTM System Settings', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_settings_system.php',
		'level' => '1');

	$nav['grid_settings_system.php:save'] = array(
		'title' => __('Save System Settings', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_settings_system.php',
		'level' => '1');

	$nav['grid_utilities.php:'] = array(
		'title' => __('Utilities', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_utilities.php',
		'level' => '1');

	$nav['grid_utilities.php:grid_utilities_perform_db_backup'] = array(
		'title' => __('Perform Database Maintenance', 'grid'),
		'mapping' => 'index.php:,grid_utilities.php:',
		'url' => 'grid_utilities.php',
		'level' => '2');

	$nav['grid_utilities.php:grid_utilities_manage_hosts'] = array(
		'title' => __('Manage LSF Hosts', 'grid'),
		'mapping' => 'index.php:,grid_utilities.php:',
		'url' => 'grid_utilities.php',
		'level' => '2');

	$nav['grid_utilities.php:grid_view_proc_status'] = array(
		'title' => __('View RTM Process Status', 'grid'),
		'mapping' => 'index.php:,grid_utilities.php:',
		'url' => 'grid_utilities.php',
		'level' => '2');

	$nav['user_admin.php:grid_settings_edit'] = array(
		'title' => __('Edit (RTM Settings)', 'grid'),
		'mapping' => 'index.php:,user_admin.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_clusters.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_clusters.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_lsf_config.php:'] = array(
		'title' => __('LSF Configuration', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_lsf_config.php',
		'level' => '1');

	$nav['grid_lsf_config.php:save'] = array(
		'title' => __('Save LSF Configuration', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_lsf_config.php',
		'level' => '1');

	$nav['grid_lsf_config.php:edit'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:,grid_lsf_config.php:',
		'url' => 'grid_lsf_config.php',
		'level' => '2');

	$nav['grid_lsf_config.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_lsf_config.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_lsf_config.php:tabs'] = array(
		'title' => __('Tabs', 'grid'),
		'mapping' => 'index.php:,grid_lsf_config.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_elim_templates.php:'] = array(
		'title' => __('ELIM Templates', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_elim_templates.php',
		'level' => '1');

	$nav['grid_elim_templates.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_elim_templates.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_elim_templates.php:template_edit'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:,grid_elim_templates.php:',
		'url' => 'grid_elim_templates.php',
		'level' => '2');

	$nav['grid_elim_graph_templates_items.php:'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_elim_graph_templates_items.php',
		'level' => '1');

	$nav['grid_elim_graph_templates_items.php:item_edit'] = array(
		'title' => __('(Item)', 'grid'),
		'mapping' => 'index.php:,grid_elim_templates.php:,grid_elim_graph_templates_items.php:',
		'url' => '',
		'level' => '3');

	$nav['grid_elim_graphs.php:'] = array(
		'title' => __('ELIMs', 'grid'),
		'mapping' => 'index.php:',
		'url' => 'grid_elim_graphs.php',
		'level' => '1');

	$nav['grid_elim_graphs.php:actions'] = array(
		'title' => __('Actions', 'grid'),
		'mapping' => 'index.php:,grid_elim_graphs.php:',
		'url' => '',
		'level' => '2');

	$nav['grid_elim_graphs.php:instance_edit'] = array(
		'title' => __('(Edit)', 'grid'),
		'mapping' => 'index.php:,grid_elim_graphs.php:',
		'url' => 'grid_elim_graphs.php',
		'level' => '2');

	$nav['grid_bresourcespool.php:'] = array(
		'title' => __('Resource Pool', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bresourcespool.php',
		'level' => '1');

	$nav['grid_bresourcespool.php:viewbrespool'] = array(
		'title' => __('Resource Pool', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bresourcespool.php',
		'level' => '1');

	$nav['grid_bresourcespool.php:viewbsla'] = array(
		'title' => __('Guaranteed Service Class', 'grid'),
		'mapping' => $grid_default . ':',
		'url' => 'grid_bresourcespool.php',
		'level' => '2');

	return $nav;
}

function grid_show_tab () {
	global $config, $tabs_left;

	$grid_default = get_grid_default_main_screen();
	if($grid_default === false){
		return;
	}

	load_current_session_value('grid_default', 'sess_grid_default', $grid_default);
	$grid_default = explode('?', basename(get_request_var('grid_default')));
	$grid_default = $grid_default[0];

	$grid_down = false;
	if (grid_tab_down()) {
		$grid_down = true;
	}
	if (get_selected_theme() != 'classic') {
		if (array_search('tab-grid', array_rekey($tabs_left, 'id', 'id')) === false) {
			$tab_grid = array(
				'title' => __('Cluster', 'grid'),
				'id'    => 'tab-grid',
				'url'   => html_escape($config['url_path'] . get_request_var('grid_default'))
			);
			$tabs_left[] = &$tab_grid;
		} else {
			foreach($tabs_left as $tab_left) {
				if ($tab_left['id'] == 'tab-grid') {
					$tab_grid = &$tab_left;
				}
			}
		}
		if ($grid_down) {
			$tab_grid['selected'] = true;
		} else {
			unset($tab_grid['selected']);
		}
	} else {
		print '<a id="grid" href="' . html_escape($config['url_path'] . get_request_var('grid_default')) . '"><img src="' . $config['url_path'] . 'plugins/grid/images/tab_grid.gif"' . ($grid_down? '_down' : '') . ' title="' . __esc('Clusters', 'grid') . '" alt="' . __esc('Clusters', 'grid') . '"></a>';
	}
}

function grid_rtm_landing_page() {
	global $config;
?>
			<li style='flex-grow:1;align-self:auto;flex-basis:30%;padding:5px;'>
				<table class='cactiTable'>
					<tr class='cactiTableTitle' style='width: 100%;'>
						<td class='landing_page_tile_large'><?php print __('Clusters', 'grid');?></td>
					</tr>
					<tr>
						<td class='print_underline'>&nbsp;</td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure Cluster Monitoring', 'grid');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Grid Management > Clusters', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/grid/grid_clusters.php'><?php print __('Set up monitoring of LSF clusters', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Create > New Graphs', 'grid');?>'><a href='<?php print $config['url_path']?>graphs_new.php'><?php print __('Create graphs for monitoring clusters', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Grid Management > License Scheduler', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/gridblstat/blstat_collectors.php'><?php print __('Configure License Scheduler to collect license usage', 'grid');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure Global Settings', 'grid');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Configuration > Settings > Mail/DNS', 'grid');?>'><a href='<?php print $config['url_path']?>settings.php?tab=mail'><?php print __('Set up email server for notifications', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Grid > Settings', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/grid/grid_settings_system.php?tab=grid_general'><?php print __('Set up grid display', 'grid');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Monitor Clusters and Jobs', 'grid');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Grid > Dashboards > Cluster', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/grid/grid_clusterdb.php'><?php print __('Monitor cluster performance', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('JobIQ tab', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/heuristics/heuristics.php'><?php print __('Monitor jobs', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Grid > Dashboards > License Scheduler', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/gridblstat/grid_lsdashboard.php'><?php print __('Monitor license usage using License Scheduler', 'grid');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Grid Management > Benchmark Jobs', 'grid');?>'><a href='<?php print $config['url_path']?>plugins/benchmark/benchmark.php'><?php print __('Monitor job throughput with benchmark jobs', 'grid');?></a></td>
					</tr>
				</table>
			</li>
<?php
}

function grid_console_after() {
	global $config;

	if (read_config_option('grid_db_upgrade', true) == '1') {
		print '<p>' . __('<b>Notice:</b> Database upgrading in progress, you may experience slower page loading and job search will be limited during this period.', 'grid') . '</p>';
	}
}

function grid_tab_down() {
	$console_tabs = array(
		'grid_pollers.php',
		'grid_manage_hosts.php',
		'grid_clusters.php',
		'grid_utilities.php',
		'grid_settings_system.php',
		'grid_elim_graphs.php',
		'grid_elim_templates.php',
		'grid_elim_graph_templates_items.php',
	);

	$vars = explode('/', $_SERVER['SCRIPT_NAME']);
	$var_size = cacti_sizeof($vars);
	if ($var_size < 2) return false;
	$pluginname = $vars[$var_size-2];
	$filename = $vars[$var_size-1];
	$grid_tab_down = false;

	if (!empty($pluginname) && !empty($filename)) {
		if ($pluginname == 'grid' && array_search($filename, $console_tabs) == false) {
			$grid_tab_down = true;
		} else {
			$pages_grid_tab_down = array();
			$pages_grid_tab_down = api_plugin_hook_function('grid_tab_down', $pages_grid_tab_down);
			if (isset($pages_grid_tab_down[$pluginname][$filename])) {
				$grid_tab_down = true;
			}
		}
	}
	return $grid_tab_down;
}

function get_grid_menu($user_menu = array()){
	global $menu_glyphs;

	$menu_glyphs[__('Dashboards', 'grid')]      = 'fa fa-tachometer-alt';
	$menu_glyphs[__('User/Group Info', 'grid')] = 'fa fa-users';
	$menu_glyphs[__('Host Info', 'grid')]       = 'fa fa-server';
	$menu_glyphs[__('Load Info', 'grid')]       = 'fa fa-heartbeat';
	$menu_glyphs[__('Job Info', 'grid')]        = 'fa fa-th-list';
	$menu_glyphs[__('Reports', 'grid')]         = 'fa fa-chart-area';

	$grid_menu = array(
		__('Dashboards', 'grid') => array(
			'plugins/grid/grid_clusterdb.php' => __('Cluster', 'grid'),
			'plugins/grid/grid_summary.php'   => __('Host', 'grid')
		),
		__('Job Info', 'grid') => array(
			'plugins/grid/grid_bhosts.php'   => __('By Host', 'grid'),
			'plugins/grid/grid_bhgroups.php' => __('By Host Group', 'grid'),
			'plugins/grid/grid_projects.php' => __('By Project', 'grid'),
			'plugins/grid/grid_license_projects.php' => __('By License Project', 'grid'),
			'plugins/grid/grid_bqueues.php'  => __('By Queue', 'grid'),
			'plugins/grid/grid_barrays.php'  => __('By Array', 'grid'),
			'plugins/grid/grid_bapps.php'    => __('By Application', 'grid'),
			'plugins/grid/grid_bjgroups.php' => __('By Group', 'grid'),
			'plugins/grid/grid_bjobs.php'    => __('Details', 'grid')
		),
		__('User/Group Info', 'grid') => array(
			'plugins/grid/grid_busers.php'   => __('Users', 'grid'),
			'plugins/grid/grid_bugroup.php'  => __('Groups', 'grid')
		),
		__('Load Info', 'grid') => array(
			'plugins/grid/grid_lsload.php'   => __('Host', 'grid'),
			'plugins/grid/grid_lsgload.php'  => __('Host Group', 'grid')
		),
		__('Host Info', 'grid') => array(
			'plugins/grid/grid_bhosts_closed.php' => __('Closed', 'grid'),
			'plugins/grid/grid_queue_distrib.php' => __('QDistrib', 'grid'),
			'plugins/grid/grid_lshosts.php' => __('Servers', 'grid'),
			'plugins/grid/grid_clients.php' => __('Clients', 'grid'),
			'plugins/grid/grid_bmgroup.php' => __('Groups', 'grid')
		),
		__('Reports', 'grid') => array(
			'plugins/grid/grid_shared.php'         => __('Shared Resources', 'grid'),
			'plugins/grid/grid_bresourcespool.php' => __('Resource Pools', 'grid'),
			'plugins/grid/grid_dailystats.php'     => __('Daily Statistics', 'grid'),
			'plugins/grid/grid_params.php'         => __('Parameters', 'grid')
		),
	);

	/* for future development */
	//'plugins/grid/grid_bhpart.php' => 'Partitions'

	$grid_menu = api_plugin_hook_function('grid_menu', $grid_menu);
	if (cacti_sizeof($user_menu)) {
		foreach($user_menu as $temp => $tempval) {
			if (($key_found = array_search($temp, array_keys($grid_menu))) !== false) {
				$grid_menu[$temp] = array_merge($grid_menu[$temp], $tempval);
			} else {
				$grid_menu[$temp] = $tempval;
			}
		}
	}
	return $grid_menu;
}

/**
 * Get privileged default url of Top 'Cluster' tab, order is:
 * 		grid_settings.default_main_screen
 * 		default_main_screen selection
 * 		any page under 'Cluster' tab
 *
 * @return void default_main_screen or false if login-user has not any realm under 'Cluster'
 */
function get_grid_default_main_screen(){
	global $grid_main_screen;

	$default_main_screen = get_request_var('grid_default');
	if(empty($default_main_screen)){
		$default_main_screen = 'plugins/grid/' . read_grid_config_option('default_main_screen');
	} else if(strpos($default_main_screen, 'plugins/') === false){
		//Extra checking for Pre-10.2.0 FixPack1 Upgrade Case
		$default_main_screen = 'plugins/grid/' . $default_main_screen;
	}

	if(api_user_realm_auth(strtok($default_main_screen, '?'))){
		return $default_main_screen;
	}

	if(cacti_sizeof($grid_main_screen)){
		foreach($grid_main_screen as $filename => $pagename){
			if(api_user_realm_auth(strtok($filename, '?'))){
				return 'plugins/grid/' . $filename;
			}
		}
	}

	$grid_menu = get_grid_menu();
	if(cacti_sizeof($grid_menu)){
		foreach($grid_menu as $label => $items){
			foreach($items as $menuurl => $menulabel){
				if(is_array($menulabel)){
					foreach($menulabel as $submenuurl => $submenulabel){
						if(api_user_realm_auth(strtok($submenuurl, '?'))){
							return $submenuurl;
						}
					}
				} else {
					if(api_user_realm_auth(strtok($menuurl, '?'))){
						return $menuurl;
					}
				}
			}
		}
	}
	return false;
}

function plugin_grid_update_realms() {
	global $config;
	include_once($config['library_path'] . '/rtm_plugins.php');

	$info    = plugin_grid_version();
	$version = $info['version'];

	plugin_rtm_migrate_realms('grid', 25, __('General LSF Data', 'grid'), 'grid_shared.php,grid_bjobs.php,grid_bzen.php,grid_bhosts.php,grid_bhosts_closed.php,grid_bqueues.php,grid_lsload.php,grid_lshosts.php,grid_bhpart.php,grid_busers.php,grid_jobgraph.php,grid_jobgraphzoom.php,grid_default.php,grid_ajax.php', $version);
	plugin_rtm_migrate_realms('grid', 26, __('LSF Admin Data', 'grid'), 'grid_dailystats.php,grid_clients.php,grid_params.php,grid_elim_graph_templates_items.php,grid_download.php', $version);
	plugin_rtm_migrate_realms('grid', 27, __('LSF Host Group Data', 'grid'), 'grid_bhgroups.php,grid_bjgroup.php,grid_lsgload.php,grid_bmgroup.php', $version);
	plugin_rtm_migrate_realms('grid', 28, __('LSF Advanced Options', 'grid'), 'grid_clusterdb.php,grid_summary.php,grid_bresourcespool.php,grid_lsf_config.php', $version);
	plugin_rtm_migrate_realms('grid', 33, __('LSF Job Array Data', 'grid'), 'grid_barrays.php', $version);
	plugin_rtm_migrate_realms('grid', 34, __('LSF User Group Data', 'grid'), 'grid_bugroup.php', $version);
	plugin_rtm_migrate_realms('grid', 35, __('LSF Project Data', 'grid'), 'grid_projects.php', $version);
	plugin_rtm_migrate_realms('grid', 36, __('LSF License Project Data', 'grid'), 'grid_license_projects.php', $version);
	plugin_rtm_migrate_realms('grid', 40, __('LSF Application Data', 'grid'), 'grid_bapps.php', $version);
	plugin_rtm_migrate_realms('grid', 41, __('LSF Job Group Data', 'grid'), 'grid_bjgroups.php', $version);
	plugin_rtm_migrate_realms('grid', 42, __('LSF Queue Distribution', 'grid'), 'grid_queue_distrib.php', $version);
	plugin_rtm_migrate_realms('grid', 44, __('LSF Administration', 'grid'), 'grid_manage_hosts.php,grid_utilities.php,grid_elim_graphs.php,grid_elim_templates.php,grid_clusters.php,grid_settings_system.php,grid_pollers.php', $version);
	plugin_rtm_migrate_realms('grid', 1046, __('LSF Extended History', 'grid'), 'LSF_Extended_History', $version);
	plugin_rtm_migrate_realms('grid', 1048, __('LSF Host Alert Stop/Resume', 'grid'), 'LSF_Host_Alert_Stop', $version);
	plugin_rtm_migrate_realms('grid', 98, __('LSF Cluster Control', 'grid'), 'LSF_Cluster_Control', $version);

	plugin_rtm_remove_realm_data('grid', 1047);
}

function grid_config_arrays () {
	global $menu, $menu_glyphs, $config, $grid_rows_selector;
	global $grid_display_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $grid_search_types, $user_menu;
	global $grid_major_refresh_interval, $grid_license_server_threads, $grid_addhost_frequency, $add_graph_frequency;
	global $grid_base_batch_timeout, $grid_job_info_timeout, $grid_job_info_retries;
	global $ha_refresh_interval, $grid_minor_refresh_interval, $grid_signal_actions, $grid_db_max_runtime;
	global $grid_timespans, $grid_timeshifts, $grid_jobzoom_timespans, $grid_weekdays;
	global $grid_clean_periods, $grid_summary_filters, $grid_main_screen;
	global $grid_max_nonjob_runtimes, $grid_max_job_runtimes;
	global $grid_efficiency_windows, $grid_efficiency_thresholds, $grid_flapping_thresholds;
	global $grid_detail_data_retention, $grid_partition_time_range, $grid_summary_data_retention;
	global $grid_builtin_resources, $grid_archive_frequencies, $grid_db_delete_size;
	global $grid_efficiency_display_ranges, $grid_efficiency_sql_ranges;
	global $grid_license_info, $messages, $grid_out_of_services;
	global $grid_job_pend_reasons_per_chart, $grid_job_pend_reasons_sortby, $grid_job_pend_reasons_filter;
	global $grid_jgroup_filter_levels, $grid_projectgroup_filter_levels, $no_http_header_files;
	global $graph_color_alpha, $graph_item_types, $consolidation_functions;
	global $struct_elim_graph_item, $elim_hosttype_options;
	global $elim_data_input_hash;
	global $hash_type_names,$hash_type_codes;
	global $grid_time_range;
	global $rrd_textalign;

	include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
	include_once($config['base_path'] . '/plugins/grid/lib/grid_validate.php');

	set_config_option('grid_copyright_year', '2006-2022');

	$elim_data_input_hash = '01cde0749ae68fd14b20fc2710d0d2ee';
	/* hook define in global_arrays.php for import/export */
	$hash_type_names['elim_template']      = __('ELIM Template', 'grid');
	$hash_type_codes['elim_template']      = '16';
	$hash_type_codes['elim_template_item'] = '17';

	/* there are no http headers required for several files */
	$no_http_header_files[] = 'poller_grid.php';
	$no_http_header_files[] = 'poller_grid_archive.php';
	$no_http_header_files[] = 'database_upgrade.php';
	$no_http_header_files[] = 'database_repair.php';
	$no_http_header_files[] = 'database_kill.php';
	$no_http_header_files[] = 'database_prune_invalid_rusage_records.php';
	$no_http_header_files[] = 'database_removemem.php';
	$no_http_header_files[] = 'grid_add_devices.php';
	$no_http_header_files[] = 'grid_add_graphs.php';
	$no_http_header_files[] = 'grid_add_cluster.php';
// Comment out the following line because RTM poller hs remove license control.
/*
	$messages[119] = array(
		'message' => __('LSF Poller requires a valid license. Please contact product support.', 'grid'),
		'type' => 'info');

	$messages[120] = array(
		'message' => __('LSF Poller and LIC Poller require a valid license. Please contact product support.', 'grid'),
		'type' => 'info');

	$messages[121] = array(
		'message' => __('Your LIC Poller license is expiring in %s days. Please contact product support.', $grid_license_info['licpoller_expiry_days'], 'grid'),
		'type' => 'info');

	$messages[122] = array(
		'message' => __('Your LSF Poller license is expiring in %s days. Please contact product support.', $grid_license_info['lsfpoller_expiry_days'], 'grid'),
		'type' => 'info');
*/

	$messages[125] = array(
		'message' => __('Restore Successful.', 'grid'),
		'type' => 'info');

	$messages[126] = array(
		'message' => __('Restore Failed.', 'grid'),
		'type' => 'error');

	$messages[128] = array(
		'message' => __('Save Successful. Remember to add RTM host to the cluster as an LSF client and reconfigure the cluster.', 'grid'),
		'type' => 'info');

	$messages[129] = array(
		'message' => __('Save Failed. LSF Poller and LIC Poller requires a valid license. Please contact product support.', 'grid'),
		'type' => 'error');

	$messages[130] = array(
		'message' => __('Save Successful. Remember to set the time zone/data/time correctly.', 'grid'),
		'type' => 'info');

	$messages[131] = array(
		'message' => __('Save Failed. Unable to locate lsf.conf/ego.conf. Please verify that the advocate server is running.', 'grid'),
		'type' => 'error');

	$messages[132] = array(
		'message' => __('Save Failed. Unable to locate lsf.conf. Please verify that the advocate server is running.', 'grid'),
		'type' => 'error');

	$messages[133] = array(
		'message' => __('Restore Failed. Backup is from a different version of RTM.', 'grid'),
		'type' => 'error');

	$messages[134] = array(
		'message' =>  __('400 Bad Request. Make sure that you have chosen the correct entry.', 'grid'),
		'type' => 'error');

	$messages[135] = array(
		'message' => __('500 internal server error. Restart Advocate.', 'grid'),
		'type' => 'error');

	$messages[136] = array(
		'message' => __('Unable to get response from Advocate. Make sure that advocate is started.', 'grid'),
		'type' => 'error');

	$messages[137] = array(
		'message' => __('Warning: Invalid LSF Poller license. Contact product support.', 'grid'),
		'type' => 'info');

	$messages[138] = array(
		'message' => __('Warning: Invalid LIC Poller license. Contact product support.', 'grid'),
		'type' => 'info');

	$messages[139] = array(
		'message' => __('Save Failed. No LSF Poller defined.', 'grid'),
		'type' => 'error');

	$messages[140] = array(
		'message' => __('Invalid LSF ENV PATH', 'grid'),
		'type' => 'error');

	$messages[141] = array(
		'message' => __('Error parsing lsf.conf file. Uncheck advance mode if necessary.', 'grid'),
		'type' => 'error');

	$messages[142] = array(
		'message' => __('Unable to locate lsf.conf in the given directory.', 'grid'),
		'type' => 'error');

	$messages[143] = array(
		'message' => __('You cannot delete this Poller while it is in use', 'grid'),
		'type' => 'error');

	$messages[144] = array(
		'message' => __('Invalid RTM Poller binary directory', 'grid'),
		'type' => 'error');

	$messages[145] = array(
		'message' => __('Duplicated cluster: One of master hostnames in list has already been added with the same port.', 'grid'),
		'type' => 'error');

	$messages['error_mandatory_input_field'] = array(
		'message' => __('Input Failed: You must input your comments in field which is mandatory(Check Red Fields)', 'grid'),
		'type' => 'error');

	$messages['field_input_save_1'] = array(
		"message" => "Save Failed: Field Input Error (Check Red Fields). Only 'A-Z', 'a-z', '0-9', '.', '_', '/', '@', '-' and ' ' characters are allowed to input.",
		"type" => "error");

	$messages[146] = array(
		'message' => __('ELIM Instance Saved: There are XXX Matching Hosts.', 'grid'),
		'type' => 'info');

	$messages[147] = array(
		'message' => __('Save Failed: Invalid Database Maintenance Time.', 'grid'),
		'type' => 'error');

	$messages[148] = array(
		'message' => __('Input Invalid: You must enter the characters in general (Check Red Fields)', 'grid'),
		'type' => 'error');

	$utilities = __('Utilities');
	$config_settings = __('Configuration');

	$menu_glyphs[__('Clusters', 'grid')]        = 'fa fa-th';

	$menu2 = array ();
	$console_menu = array(
		'plugins/grid/grid_pollers.php' => __('Pollers', 'grid'),
		'plugins/grid/grid_clusters.php' => __('Clusters', 'grid')
	);

	foreach ($menu as $temp => $temp2 ) {
		if ($temp == __('Management')) {
			$menu2[$temp] = array_merge($temp2, array('plugins/grid/grid_elim_graphs.php' => __('ELIMs', 'grid')));
			if (isset($menu[__('Clusters', 'grid')])) {
				$menu2[__('Clusters', 'grid')] = array_merge($menu[__('Clusters', 'grid')], $console_menu);
			} else {
				$menu2[__('Clusters', 'grid')] = $console_menu;
			}
		} elseif ($temp == __('Clusters', 'grid')) {
			continue;
		} elseif ($temp == __('Templates')) {
			$menu2[$temp] = array_merge($temp2, array('plugins/grid/grid_elim_templates.php' => __('ELIM', 'grid')));
		} elseif ($temp == __('Utilities')) {
			$menu2[$temp] = array_merge($temp2, array('plugins/grid/grid_utilities.php' => __('RTM Utilities', 'grid')));
		} elseif ($temp == __('Configuration')) {
			$menu2[$temp] = array_merge($temp2, array('plugins/grid/grid_settings_system.php' => __('RTM Settings', 'grid')));
		} else {
			$menu2[$temp] = $temp2;
		}
	}

	$menu = $menu2;

	if (strpos(get_current_page(), 'grid_') !== false || grid_tab_down()) {
		$user_menu = get_grid_menu($user_menu);
	}

	if (read_config_option('grid_thold_resdown_status') == 1) {
		$grid_out_of_services = array('Unavail', 'Unlicensed', 'Closed-LIM', 'Unreach', 'Closed-Admin', 'Closed-Wind', 'Closed-RES');
	} else {
		$grid_out_of_services = array('Unavail', 'Unlicensed', 'Closed-LIM', 'Unreach', 'Closed-Admin', 'Closed-Wind');
	}

	$grid_refresh_interval = array(
		'15'      => __('%d Seconds', 15, 'grid'),
		'20'      => __('%d Seconds', 20, 'grid'),
		'30'      => __('%d Seconds', 30, 'grid'),
		'60'      => __('%d Minute', 1, 'grid'),
		'120'     => __('%d Minutes', 2, 'grid'),
		'180'     => __('%d Minutes', 3, 'grid'),
		'240'     => __('%d Minutes', 4, 'grid'),
		'300'     => __('%d Minutes', 5, 'grid'),
		'9999999' => __('Never', 'grid')
	);

	$grid_max_job_runtimes = array(
		'15'   => __('%d Seconds', 15, 'grid'),
		'20'   => __('%d Seconds', 20, 'grid'),
		'30'   => __('%d Seconds', 30, 'grid'),
		'60'   => __('%d Minute', 1, 'grid'),
		'300'  => __('%d Minutes', 5, 'grid'),
		'600'  => __('%d Minutes', 10, 'grid'),
		'900'  => __('%d Minutes', 15, 'grid'),
		'1200' => __('%d Minutes', 20, 'grid')
	);

	$grid_max_nonjob_runtimes = array(
		'5'   => __('%d Seconds', 5, 'grid'),
		'10'  => __('%d Seconds', 10, 'grid'),
		'15'  => __('%d Seconds', 15, 'grid'),
		'20'  => __('%d Seconds', 20, 'grid'),
		'30'  => __('%d Seconds', 30, 'grid'),
		'60'  => __('%d Minute', 1, 'grid'),
		'120' => __('%d Minutes', 2, 'grid'),
		'180' => __('%d Minutes', 3, 'grid'),
		'240' => __('%d Minutes', 4, 'grid'),
		'300' => __('%d Minutes', 5, 'grid')
	);

	$grid_minor_refresh_interval = array(
		'15'  => __('%d Seconds', 15, 'grid'),
		'20'  => __('%d Seconds', 20, 'grid'),
		'30'  => __('%d Seconds', 30, 'grid'),
		'45'  => __('%d Seconds', 45, 'grid'),
		'60'  => __('%d Minute', 1, 'grid'),
		'120' => __('%d Minutes', 2, 'grid'),
		'180' => __('%d Minutes', 3, 'grid'),
		'240' => __('%d Minutes', 4, 'grid'),
		'300' => __('%d Minutes', 5, 'grid')
	);

	$ha_refresh_interval = array(
		'5'   => __('%d Seconds', 5, 'grid'),
		'10'  => __('%d Seconds', 10, 'grid'),
		'15'  => __('%d Seconds', 15, 'grid'),
		'20'  => __('%d Seconds', 20, 'grid'),
		'40'  => __('%d Seconds', 40, 'grid'),
		'60'  => __('%d Minute', 1, 'grid'),
		'120' => __('%d Minutes', 2, 'grid'),
		'180' => __('%d Minutes', 3, 'grid'),
		'240' => __('%d Minutes', 4, 'grid'),
		'300' => __('%d Minutes', 5, 'grid')
	);

	$grid_major_refresh_interval = array(
		'60'   => __('%d Minute', 1, 'grid'),
		'120'  => __('%d Minutes', 2, 'grid'),
		'180'  => __('%d Minutes', 3, 'grid'),
		'240'  => __('%d Minutes', 4, 'grid'),
		'300'  => __('%d Minutes', 5, 'grid'),
		'600'  => __('%d Minutes', 10, 'grid'),
		'900'  => __('%d Minutes', 15, 'grid'),
		'1200' => __('%d Minutes', 20, 'grid')
	);

	$addhost_interval = read_config_option('poller_interval');
	if ($addhost_interval = '300') {
		$grid_addhost_frequency = array(
			'0'     => __('Never', 'grid'),
			'300'   => __('%d Minutes', 5, 'grid'),
			'600'   => __('%d Minutes', 10, 'grid'),
			'1200'  => __('%d Minutes', 20, 'grid'),
			'2400'  => __('%d Minutes', 40, 'grid'),
			'3600'  => __('%d Hour', 1, 'grid'),
			'18000' => __('%d Hours', 5, 'grid')
		);
	} else {
		$grid_addhost_frequency = array(
			'0'    => __('Never', 'grid'),
			'60'   => __('%d Minute', 1, 'grid'),
			'120'  => __('%d Minutes', 2, 'grid'),
			'240'  => __('%d Minutes', 4, 'grid'),
			'600'  => __('%d Minutes', 10, 'grid'),
			'1200' => __('%d Minutes', 20, 'grid'),
			'2400' => __('%d Minutes', 40, 'grid')
		);
	}

	$add_graph_frequency = array(
		'0'      => __('Never', 'grid'),
		'21600'  => __('%d Hours', 6, 'grid'),
		'43200'  => __('%d Hours', 12, 'grid'),
		'86400'  => __('%d Day', 1, 'grid'),
		'172800' => __('%d Days', 2, 'grid'),
		'345600' => __('%d Days', 4, 'grid')
	);

	$grid_base_batch_timeout = array(
		'5'   => __('%d Seconds', 5, 'grid'),
		'10'  => __('%d Seconds', 10, 'grid'),
		'20'  => __('%d Seconds', 20, 'grid'),
		'30'  => __('%d Seconds', 30, 'grid'),
		'60'  => __('%d Minute', 1, 'grid'),
		'120' => __('%d Minutes', 2, 'grid')
	);

	$grid_job_info_timeout = array(
		'60'  => __('%d Minute', 1, 'grid'),
		'120' => __('%d Minutes', 2, 'grid'),
		'300' => __('%d Minutes', 5, 'grid'),
		'600' => __('%d Minutes', 10, 'grid')
	);

	$grid_job_info_retries = array(
		'1' => __('%d Retry', 1, 'grid'),
		'2' => __('%d Retries', 2, 'grid'),
		'3' => __('%d Retries', 3, 'grid'),
		'4' => __('%d Retries', 4, 'grid'),
		'5' => __('%d Retries', 5, 'grid')
	);

	$grid_db_max_runtime = array(
		'1800'  => __('%d Minutes', 30, 'grid'),
		'2400'  => __('%d Minutes', 40, 'grid'),
		'3000'  => __('%d Minutes', 50, 'grid'),
		'3600'  => __('%d Hour', 1, 'grid'),
		'5400'  => __('%s Hours', '1.5', 'grid'),
		'7200'  => __('%d Hours', 2, 'grid'),
		'10800' => __('%d Hours', 3.0, 'grid'),
		'14400' => __('%d Hours', 4.0, 'grid')
	);

	$grid_db_delete_size = array(
		'500'    => __('%s Records', number_format_i18n('500'), 'grid'),
		'1000'   => __('%s Records', number_format_i18n('1000'), 'grid'),
		'2000'   => __('%s Records', number_format_i18n('2000'), 'grid'),
		'5000'   => __('%s Records', number_format_i18n('5000'), 'grid'),
		'10000'  => __('%s Records', number_format_i18n('10000'), 'grid'),
		'20000'  => __('%s Records', number_format_i18n('20000'), 'grid'),
		'30000'  => __('%s Records', number_format_i18n('30000'), 'grid'),
		'50000'  => __('%s Records', number_format_i18n('100000'), 'grid'),
		'100000' => __('%s Records', number_format_i18n('100000'), 'grid')
	);

	$grid_efficiency_windows = array(
		'60'   => __('%d Minute', 1, 'grid'),
		'120'  => __('%d Minutes', 2, 'grid'),
		'300'  => __('%d Minutes', 5, 'grid'),
		'600'  => __('%d Minutes', 10, 'grid'),
		'900'  => __('%d Minutes', 15, 'grid'),
		'1200' => __('%d Minutes', 20, 'grid'),
		'2400' => __('%d Minutes', 40, 'grid')
	);

	$grid_archive_frequencies = array(
		'600'   => __('%d Minutes', 10, 'grid'),
		'1200'  => __('%d Minutes', 20, 'grid'),
		'1800'  => __('%d Minutes', 30, 'grid'),
		'3600'  => __('%d Hour', 1, 'grid'),
		'7200'  => __('%d Hours', 2, 'grid'),
		'14400' => __('%d Hours', 4, 'grid')
	);

	$grid_efficiency_thresholds = array(
		'10' => '< 10%',
		'15' => '< 15%',
		'20' => '< 20%',
		'25' => '< 25%',
		'30' => '< 30%',
		'35' => '< 35%',
		'40' => '< 40%',
		'45' => '< 45%',
		'50' => '< 50%',
		'55' => '< 55%',
		'60' => '< 60%',
		'65' => '< 65%',
		'70' => '< 70%',
		'75' => '< 75%');

	$grid_efficiency_display_ranges = array(
		'0' => '< 10%',
		'1' => '10% - 20%',
		'2' => '20% - 40%',
		'3' => '40% - 60%',
		'4' => '60% - 90%',
		'5' => '>= 90%');

	$grid_efficiency_sql_ranges = array(
		'0' => 'efficiency<10',
		'1' => '(efficiency>=10 AND efficiency<=20)',
		'2' => '(efficiency>=20 AND efficiency<=40)',
		'3' => '(efficiency>=40 AND efficiency<=60)',
		'4' => '(efficiency>=60 AND efficiency<=90)',
		'5' => '(efficiency>=90)');

	$grid_license_server_threads = array(
		1   => __('%d Thread', 1, 'grid'),
		2   => __('%d Threads', 2, 'grid'),
		3   => __('%d Threads', 3, 'grid'),
		4   => __('%d Threads', 4, 'grid'),
		5   => __('%d Threads', 5, 'grid'),
		6   => __('%d Threads', 6, 'grid'),
		7   => __('%d Threads', 7, 'grid'),
		8   => __('%d Threads', 8, 'grid'),
		9   => __('%d Threads', 9, 'grid'),
		10  => __('%d Threads', 10, 'grid'),
		12	=> __('%d Threads', 12, 'grid'),
		15  => __('%d Threads', 15, 'grid'),
		20  => __('%d Threads', 20, 'grid')
	);

	$grid_search_types = array(
		1 => __('Select Criteria', 'grid'),
		2 => __('Matches', 'grid'),
		3 => __('Contains', 'grid'),
		4 => __('Begins With', 'grid'),
		5 => __('Does Not Contain', 'grid'),
		6 => __('Does Not Begin With', 'grid'),
		7 => __('Is Null', 'grid'),
		8 => __('Is Not Null', 'grid')
	);

	$grid_builtin_resources = array(
		'r15s' =>  __('15-second load averaged over the last 15 seconds', 'grid'),
		'r1m'  =>  __('1-minute load averaged over the last minute', 'grid'),
		'r15m' =>  __('15-minute load averaged over the last 15 minutes', 'grid'),
		'ut'   =>  __('CPU utilization averaged over the last minute', 'grid'),
		'pg'   =>  __('Memory paging rate averaged over the last minute', 'grid'),
		'io'   =>  __('Disk I/O rate averaged over the last minute', 'grid'),
		'ls'   =>  __('Number of current login users', 'grid'),
		'it'   =>  __('Idle time of the host', 'grid'),
		'tmp'  =>  __('Available free disk space on file system containing the temporary directory', 'grid'),
		'swp'  =>  __('Currently available virtual memory (swap space)', 'grid'),
		'mem'  =>  __('Estimate of the currently available memory', 'grid'),
	);

	$grid_summary_filters = array(
		GRID_UNAVAIL   => __('Unavailable', 'grid'),
		GRID_LOWRES    => __('Low Resources', 'grid'),
		GRID_STARVED   => __('Starved', 'grid'),
		GRID_IDLECLOSE => __('Idle/Closed', 'grid'),
		GRID_IDLEWJOBS => __('Idle w/Jobs', 'grid'),
		GRID_BUSYCLOSE => __('Busy/Closed', 'grid'),
		GRID_BUSY      => __('Busy', 'grid'),
		GRID_IDLE      => __('Idle', 'grid'),
		GRID_ADMINDOWN => __('Admin Down', 'grid')
//		GRID_BLACKHOLE => 'Black Hole'
	);

	// Adjust the number of items rows based upon max_input_vars
	$max_size = ini_get('max_input_vars') - 100;
	if(read_config_option('grid_xport_rows') < $max_size) {
		$grid_xport_rows2 = read_config_option('grid_xport_rows');
	} else {
		$grid_xport_rows2 = 1000;
		$grid_settings_system_grid_xport_rows = array(
				'1000'  => __('%d Rows', 1000,  'grid'),
				'2000'  => __('%d Rows', 2000,  'grid'),
				'5000'  => __('%d Rows', 5000,  'grid'),
				'10000' => __('%d Rows', 10000, 'grid'),
				'20000' => __('%d Rows', 20000, 'grid'),
				'50000' => __('%d Rows', 50000, 'grid')
			);
		foreach($grid_settings_system_grid_xport_rows as $index => $row) {
			if ($index > $max_size) {
				break;
			}
			$grid_xport_rows2 = $index;
		}
	}
	$grid_rows_selector = array(
		-1   => __('Default:'.((null!=read_grid_config_option('grid_records'))?read_grid_config_option('grid_records'):'30'), 'grid'),
		10   => '10',
		15   => '15',
		20   => '20',
		30   => '30',
		50   => '50',
		100  => '100',
		150  => '150',
		200  => '200',
		250  => '250',
		500  => '500',
		1000 => '1000',
		$grid_xport_rows2 => __('Limit', 'grid')
	);

	$grid_display_rows_selector = array(
		10   => '10',
		15   => '15',
		20   => '20',
		30   => '30',
		50   => '50',
		100  => '100',
		150  => '150',
		200  => '200',
		250  => '250',
		500  => '500',
		1000 => '1000'
	);

	$grid_partition_time_range = array(
		'1day'   => __('%d Day', 1, 'grid'),
		'2days'  => __('%d Days', 2, 'grid'),
		'5days'  => __('%d Days', 5, 'grid'),
		'1week'  => __('%d Week', 1, 'grid'),
		'2weeks' => __('%d Weeks', 2, 'grid')
	);

	$grid_flapping_thresholds = array(
		6  => __('%d Changes', 6, 'grid'),
		7  => __('%d Changes', 7, 'grid'),
		8  => __('%d Changes', 8, 'grid'),
		9  => __('%d Changes', 9, 'grid'),
		10 => __('%d Changes', 10, 'grid'),
		15 => __('%d Changes', 15, 'grid'),
		20 => __('%d Changes', 20, 'grid')
	);

	$grid_detail_data_retention = array(
		'2days'   => __('%d Days', 2, 'grid'),
		'5days'   => __('%d Days', 5, 'grid'),
		'1week'   => __('%d Week', 1, 'grid'),
		'2weeks'  => __('%d Weeks', 2, 'grid'),
		'3weeks'  => __('%d Weeks', 3, 'grid'),
		'1month'  => __('%d Month', 1, 'grid'),
		'2months' => __('%d Months', 2, 'grid'),
		'3months' => __('%d Months', 3, 'grid'),
		'4months' => __('%d Months', 4, 'grid'),
		'6months' => __('%d Months', 6, 'grid'),
		'1year'   => __('%d Year', 1, 'grid')
	);

	$grid_summary_data_retention = array(
		'2days'   => __('%d Days', 2, 'grid'),
		'5days'   => __('%d Days', 5, 'grid'),
		'1week'   => __('%d Week', 1, 'grid'),
		'2weeks'  => __('%d Weeks', 2, 'grid'),
		'1month'  => __('%d Month', 1, 'grid'),
		'2months' => __('%d Months', 2, 'grid'),
		'3months' => __('%d Months', 3, 'grid'),
		'4months' => __('%d Months', 4, 'grid'),
		'6months' => __('%d Months', 6, 'grid'),
		'1year'   => __('%d Year', 1, 'grid'),
		'2years'  => __('%d Years', 2, 'grid'),
		'3years'  => __('%d Years', 3, 'grid'),
		'4years'  => __('%d Years', 4, 'grid'),
		'5years'  => __('%d Years', 5, 'grid'),
		'6years'  => __('%d Years', 6, 'grid')
	);

	$grid_timespans = array(
		GT_LAST_HALF_HOUR => __('Last Half Hour', 'grid'),
		GT_LAST_HOUR      => __('Last Hour', 'grid'),
		GT_LAST_2_HOURS   => __('Last %d Hours', 2, 'grid'),
		GT_LAST_4_HOURS   => __('Last %d Hours', 4, 'grid'),
		GT_LAST_6_HOURS   => __('Last %d Hours', 6, 'grid'),
		GT_LAST_12_HOURS  => __('Last %d Hours', 12, 'grid'),
		GT_LAST_DAY       => __('Last Day', 'grid'),
		GT_LAST_2_DAYS    => __('Last %d Days', 2, 'grid'),
		GT_LAST_3_DAYS    => __('Last %d Days', 3, 'grid'),
		GT_LAST_4_DAYS    => __('Last %d Days', 4, 'grid'),
		GT_LAST_WEEK      => __('Last Week', 'grid'),
		GT_LAST_2_WEEKS   => __('Last %d Weeks', 2, 'grid'),
		GT_LAST_MONTH     => __('Last Month', 'grid'),
		GT_LAST_2_MONTHS  => __('Last %d Months', 2, 'grid'),
		GT_LAST_3_MONTHS  => __('Last %d Months', 3, 'grid'),
		GT_LAST_4_MONTHS  => __('Last %d Months', 4, 'grid'),
		GT_LAST_6_MONTHS  => __('Last %d Months', 6, 'grid'),
		GT_LAST_YEAR      => __('Last Year', 'grid'),
		GT_LAST_2_YEARS   => __('Last %d Years', 2, 'grid'),
		GT_DAY_SHIFT      => __('Day Shift', 'grid'),
		GT_THIS_DAY       => __('This Day', 'grid'),
		GT_THIS_WEEK      => __('This Week', 'grid'),
		GT_THIS_MONTH     => __('This Month', 'grid'),
		GT_THIS_YEAR      => __('This Year', 'grid'),
		GT_PREV_DAY       => __('Previous Day', 'grid'),
		GT_PREV_WEEK      => __('Previous Week', 'grid'),
		GT_PREV_MONTH     => __('Previous Month', 'grid'),
		GT_PREV_YEAR      => __('Previous Year', 'grid')
	);

	$grid_jobzoom_timespans = array(
		GTS_HALF_HOUR => __('%d Min', 30, 'grid'),
		GTS_1_HOUR    => __('%d Hour', 1, 'grid'),
		GTS_2_HOURS   => __('%d Hours', 2, 'grid'),
		GTS_4_HOURS   => __('%d Hours', 4, 'grid'),
		GTS_6_HOURS   => __('%d Hours', 6, 'grid'),
		GTS_12_HOURS  => __('%d Hours', 12, 'grid'),
		GTS_1_DAY     => __('%d Day', 1, 'grid'),
		GTS_2_DAYS    => __('%d Days', 2, 'grid'),
		GTS_3_DAYS    => __('%d Days', 3, 'grid'),
		GTS_4_DAYS    => __('%d Days', 4, 'grid'),
		GTS_1_WEEK    => __('%d Week', 1, 'grid'),
		GTS_2_WEEKS   => __('%d Weeks', 2, 'grid')
	);

	$grid_clean_periods = array(
		3600  => __('%d Hour', 1, 'grid'),
		7200  => __('%d Hours', 2, 'grid'),
		14400 => __('%d Hours', 4, 'grid'),
		21600 => __('%d Hours', 6, 'grid'),
		36000 => __('%d Hours', 12, 'grid'),
		86400 => __('%d Day', 1, 'grid')
	);

	$grid_timeshifts = array(
		GTS_HALF_HOUR => __('%d Min', 30, 'grid'),
		GTS_1_HOUR    => __('%d Hour', 1, 'grid'),
		GTS_2_HOURS   => __('%d Hours', 2, 'grid'),
		GTS_4_HOURS   => __('%d Hours', 4, 'grid'),
		GTS_6_HOURS   => __('%d Hours', 6, 'grid'),
		GTS_12_HOURS  => __('%d Hours', 12, 'grid'),
		GTS_1_DAY     => __('%d Day', 1, 'grid'),
		GTS_2_DAYS    => __('%d Days', 2, 'grid'),
		GTS_3_DAYS    => __('%d Days', 3, 'grid'),
		GTS_4_DAYS    => __('%d Days', 4, 'grid'),
		GTS_1_WEEK    => __('%d Week', 1, 'grid')
	);

	$grid_weekdays = array(
		WD_SUNDAY    => date('l', strtotime('Sunday')),
		WD_MONDAY    => date('l', strtotime('Monday')),
		WD_TUESDAY   => date('l', strtotime('Tuesday')),
		WD_WEDNESDAY => date('l', strtotime('Wednesday')),
		WD_THURSDAY  => date('l', strtotime('Thursday')),
		WD_FRIDAY    => date('l', strtotime('Friday')),
		WD_SATURDAY  => date('l', strtotime('Saturday'))
	);

	$grid_signal_actions = array(
		'0' => __('Unknown/Undocumented behavior.', 'grid'),
		'1' => __('End the process immediately.', 'grid'),
		'2' => __('End the request.', 'grid'),
		'3' => __('Ignore the signal.', 'grid'),
		'4' => __('Stop the process.', 'grid'),
		'5' => __('Continue the process if it is currently stopped. Otherwise, ignore the signal.', 'grid')
	);

	$grid_job_pend_reasons_filter = array(
		'0' => __('Host Related', 'grid'),
		'1' => __('Job Related', 'grid'),
		'2' => __('User Related', 'grid'),
		'3' => __('Queue and System Related', 'grid'),
		'4' => __('Multi-Cluster Related', 'grid'),
		'5' => __('Other Reasons', 'grid')
	);

	$grid_job_pend_reasons_per_chart = array(
		'1000' => __('All', 'grid'),
		'5'    => __('Top 5 Pending Reasons', 'grid'),
		'10'   => __('Top 10 Pending Reasons', 'grid'),
		'15'   => __('Top 15 Pending Reasons', 'grid'),
		'20'   => __('Top 20 Pending Reasons', 'grid'),
		'30'   => __('Top 30 Pending Reasons', 'grid')
	);

	$grid_job_pend_reasons_sortby = array(
		'0' => __('Pending Reason Duration', 'grid'),
		'1' => __('Pending Reason Text', 'grid'),
		'2' => __('Start Time Of The Pending Reason', 'grid'),
		'3' => __('End Time Of The Pending Reason', 'grid')
	);

	$grid_jgroup_filter_levels = array(
		'1' => __('Level %d', 1, 'grid'),
		'2' => __('Level %d', 2, 'grid'),
		'3' => __('Level %d', 3, 'grid'),
		'4' => __('Level %d', 4, 'grid'),
		'5' => __('Level %d', 5, 'grid'),
		'6' => __('Level %d', 6, 'grid'),
		'7' => __('Level %d', 7, 'grid'),
		'8' => __('Level %d', 8, 'grid'),
		'9' => __('Level %d', 9, 'grid'),
		'0' => __('All', 'grid')
	);

	$grid_projectgroup_filter_levels = array(
		'1' => __('Level %d', 1, 'grid'),
		'2' => __('Level %d', 2, 'grid'),
		'3' => __('Level %d', 3, 'grid'),
		'4' => __('Level %d', 4, 'grid'),
		'5' => __('Level %d', 5, 'grid'),
		'6' => __('Level %d', 6, 'grid'),
		'7' => __('Level %d', 7, 'grid'),
		'8' => __('Level %d', 8, 'grid'),
		'0' => __('All', 'grid')
	);

	$struct_elim_graph_item = array(
	'graph_type_id' => array(
		'friendly_name' => __('Graph Item Type', 'grid'),
		'method' => 'drop_array',
		'array' => $graph_item_types,
		'default' => '4',
		'description' => __('How data for this item is represented visually on the graph.', 'grid')
		),
	'task_item_id' => array(
		'friendly_name' => __('ELIM Resource Name', 'grid'),
		'method' => 'drop_sql',
		'sql' => 'select distinct resource_name as name from grid_hosts_resources where host <> \'ALLHOSTS\' order by resource_name',
		'default' => '0',
		'none_value' => __('None', 'grid'),
		'description' => __('The ELIM resource that you want to use for the selected graph.', 'grid')
		),
	'color_id' => array(
		'friendly_name' => __('Color', 'grid'),
		'method' => 'drop_color',
		'default' => '0',
		'on_change' => 'changeColorId()',
		'description' => __('The color to use for the legend.', 'grid')
		),
	'alpha' => array(
		'friendly_name' => __('Opacity/Alpha Channel', 'grid'),
		'method' => 'drop_array',
		'default' => 'FF',
		'array' => $graph_color_alpha,
		'description' => __('The opacity/alpha channel of the color.', 'grid')
		),
	'consolidation_function_id' => array(
		'friendly_name' => __('Consolidation Function', 'grid'),
		'method' => 'drop_array',
		'array' => $consolidation_functions,
		'default' => '0',
		'description' => __('How data for this item is represented statistically on the graph.', 'grid')
		),
	'cdef_id' => array(
		'friendly_name' => __('CDEF Function', 'grid'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id, name FROM cdef WHERE name NOT LIKE "\_%" ORDER BY name',
		'default' => '0',
		'none_value' => __('None', 'grid'),
		'description' => __('A CDEF (math) function to apply to this item on the graph or legend.', 'grid')
		),
	'vdef_id' => array(
		'friendly_name' => __('VDEF Function', 'grid'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id, name FROM vdef ORDER BY name',
		'default' => '0',
		'none_value' => __('None', 'grid'),
		'description' => __('A VDEF (math) function to apply to this item on the graph legend.', 'grid')
		),
	'shift' => array(
		'friendly_name' => __('Shift Data', 'grid'),
		'method' => 'checkbox',
		'default' => '',
		'description' => __('Offset your data on the time axis (x-axis) by the amount specified in the \'value\' field.', 'grid'),
		),
	'value' => array(
		'friendly_name' => __('Value', 'grid'),
		'method' => 'textbox',
		'max_length' => '50',
		'size' => '40',
		'default' => '',
		'description' => __('[HRULE|VRULE]: The value of the graph item.<br/> [TICK]: The fraction for the tick line.<br/> [SHIFT]: The time offset in seconds.', 'grid')
		),
	'gprint_id' => array(
		'friendly_name' => __('GPRINT Type', 'grid'),
		'method' => 'drop_sql',
		'sql' => 'SELECT id, name FROM graph_templates_gprint ORDER BY name',
		'default' => '2',
		'description' => __('If this graph item is a GPRINT, you can optionally choose another format here. You can define additional types under "GPRINT Presets".', 'grid')
		),
	'textalign' => array(
		'friendly_name' => __('Text Alignment (TEXTALIGN)', 'grid'),
		'method' => 'drop_array',
		'value' => '|arg1:textalign|',
		'array' => $rrd_textalign,
		'default' => '',
		'description' => __('All subsequent legend line(s) will be aligned as given here.  You may use this command multiple times in a single graph.  This command does not produce tabular layout.<br/><strong>Note: </strong>You may want to insert a &lt;HR&gt; on the preceding graph item.<br/> <strong>Note: </strong>A &lt;HR&gt; on this legend line will obsolete this setting!', 'grid'),
		),
	'text_format' => array(
		'friendly_name' => __('Text Format', 'grid'),
		'method' => 'textbox',
		'max_length' => '255',
		'size' => '80',
		'default' => '',
		'description' => __('Text that will be displayed on the legend for this graph item.', 'grid')
		),
	'hard_return' => array(
		'friendly_name' => __('Insert Hard Return', 'grid'),
		'method' => 'checkbox',
		'default' => '',
		'description' => __('Forces the legend to the next line after this item.', 'grid')
		),
	'line_width' => array(
		'friendly_name' => __('Line Width (decimal)', 'grid'),
		'method' => 'textbox',
		'max_length' => '5',
		'default' => '1.00',
		'size' => '5',
		'description' => __('In case LINE was chosen, specify width of line here.  You must include a decimal precision, for example 2.00', 'grid'),
		),
	'dashes' => array(
		'friendly_name' => __('Dashes (dashes[=on_s[,off_s[,on_s,off_s]...]])', 'grid'),
		'method' => 'textbox',
		'max_length' => '40',
		'default' => '',
		'size' => '30',
		'description' => __('The dashes modifier enables dashed line style.', 'grid'),
		),
	'dash_offset' => array(
		'friendly_name' => __('Dash Offset (dash-offset=offset)', 'grid'),
		'method' => 'textbox',
		'max_length' => '4',
		'default' => '',
		'size' => '4',
		'description' => __('The dash-offset parameter specifies an offset into the pattern at which the stroke begins.', 'grid'),
		),
	'resource_name' => array(
		'method' => 'hidden',
		'description' => __('store the ELIM resource name.', 'grid')
		),
	'resource_option' => array(
		'method' => 'hidden',
		'description' => __('store the ELIM resource option.', 'grid')
		),
	'sequence' => array(
		'friendly_name' => __('Sequence', 'grid'),
		'method' => 'view'
		)
	);

	$elim_hosttype_options = array(1 =>
		'Host Type',
		'Host Model',
		'Host Group');

	$grid_time_range = array(
		'1day'      => __('%d Day', 1, 'grid'),
		'2days'     => __('%d Days', 2, 'grid'),
		'5days'     => __('%d Days', 5, 'grid'),
		'1week'     => __('%d Week', 1, 'grid'),
		'2weeks'    => __('%d Weeks', 2, 'grid'),
		'3weeks'    => __('%d Weeks', 3, 'grid'),
		'1month'    => __('%d Month', 1, 'grid'),
		'2months'   => __('%d Months', 2, 'grid'),
		'1quarter'  => __('%d Quarter', 1, 'grid'),
		'2quarters' => __('%d Quarters', 2, 'grid'),
		'3quarters' => __('%d Quarters', 3, 'grid'),
		'1year'     => __('%d Year', 1, 'grid'),
		'2years'    => __('%d Years', 2, 'grid'),
		'3years'    => __('%d Years', 3, 'grid'),
	);

	$grid_main_screen = array(
		'grid_summary.php' => __('Host Dashboard', 'grid'),
		'grid_clusterdb.php'  => __('Cluster Dashboard', 'grid'),
		'grid_dailystats.php' => __('Daily Statistics', 'grid'),
		'grid_projects.php'   => __('Project View', 'grid'),
		'grid_license_projects.php' => __('License Project View', 'grid'),
		'grid_bjobs.php?action=viewlist' => __('Jobs List', 'grid'),
		'grid_lsload.php'  => __('Host Load View', 'grid'),
		'grid_bhosts.php'  => __('Jobs by Host', 'grid'),
		'grid_bqueues.php' => __('Queue View', 'grid')
	);
}

function grid_api_device_save ($save) {
	if (isset_request_var('clusterid')) {
		$save['clusterid'] = form_input_validate(get_filter_request_var('clusterid'), 'clusterid', '', true, 3);
	} else {
		if (!isset($save['clusterid'])) {
			$save['clusterid'] = form_input_validate('', 'clusterid', '', true, 3);
		}
	}

	if (isset_request_var('monitor')) {
		$save['monitor'] = form_input_validate(get_nfilter_request_var('monitor'), 'monitor', '', true, 3);
	} else {
		$save['monitor'] = form_input_validate('', 'monitor', '', true, 3);
	}

	return $save;
}

function grid_setup_table () {
	global $config;
	include_once($config['library_path'] . '/plugins.php');

	api_plugin_db_add_column('grid', 'host', array(
		'name' => 'clusterid',
		'unsigned' => true,
		'type' => "int(10)",
		'NULL' => false,
		'default' => '0',
		'after' => 'host_template_id'
	));
	api_plugin_db_add_column('grid', 'host', array(
		'name' => 'monitor',
		'type' => "char(3)",
		'NULL' => false,
		'default' => 'on',
		'after' => 'disabled'
	));
	api_plugin_db_add_column('grid', 'user_auth', array(
		'name' => 'grid_settings',
		'type' => 'char(2)',
		'NULL' => false,
		'default' => 'on',
		'after' => 'graph_settings'
	));
}

function grid_custom_version_info() {
	$version = db_fetch_cell("SELECT value from settings where name='grid_version'");
	print "<tr class='even'>\n";
	print "     <td class='textArea'>" . __('RTM Version', 'grid') . "</td>\n";
	print "     <td class='textArea'>$version </td>\n";
	print "</tr>\n";
}

function grid_thold_update_hostsalarm($item) {
	// insert trigged record into grid_hosts_alarm table
	$host_info = db_fetch_row_prepared('SELECT clusterid, hostname
		FROM host
		WHERE id = ?
		AND clusterid > 0',
		array($item['host_id']));

	if (isset($host_info['clusterid'])) {
		db_execute_prepared("INSERT INTO grid_hosts_alarm
			(`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`present`)
			VALUES (?, ?, ?, ?, '0', ?, '1')
			ON DUPLICATE KEY UPDATE name=VALUES(name), hostname=VALUES(hostname),
			clusterid=VALUES(clusterid),message=VALUES(message),present=1",
			array($item['id'], $item['name_cache'], $host_info['hostname'], $host_info['clusterid'], $item['subject']));
	}
	return $item;
}

function grid_thold_reset_hostsalarm() {
	db_execute("UPDATE grid_hosts_alarm SET present='0' WHERE type=0");
}

function grid_thold_delete_hostsalarm($type_id = NULL) {
	if ($type_id) {
		db_execute_prepared("DELETE gha
			FROM grid_hosts_alarm AS gha
			LEFT JOIN thold_data AS td
			ON gha.type_id=td.id
			WHERE gha.type=0
			AND gha.type_id=?
			AND (td.thold_alert=0 OR td.thold_alert IS NULL OR td.thold_enabled='off')", array($type_id));
		return $type_id;
	} else {
		db_execute("DELETE gha
			FROM grid_hosts_alarm AS gha
			LEFT JOIN thold_data AS td
			ON gha.type_id=td.id
			WHERE gha.present=0 AND gha.type=0
			AND (td.thold_alert=0 OR td.thold_alert IS NULL OR td.thold_enabled='off')");
	}
}

function grid_syslog_update_hostsalarm($item) {
	global $cnn_id;

	$item['alert_name'] = db_qstr($item['alert_name']);
	$item['logmsg']     = db_qstr($item['logmsg']);

	//insert trigged record into grid_hosts_alarm
	$results = db_fetch_assoc_prepared('SELECT clusterid
		FROM host
		WHERE hostname = ?
		AND clusterid>0',
		array($item['host']));

	if (cacti_sizeof($results)>0) {
		foreach($results as $result) {
			db_execute_prepared("INSERT INTO grid_hosts_alarm
				(`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`alert_time`,`present`)
				VALUES (?, ?, ?, ?, '1', ?, ?, '1')
				ON DUPLICATE KEY UPDATE name=VALUES(name),message=VALUES(message),present=1",
				array($item['seq'], $item['alert_name'], $item['host'], $result['clusterid'], $item['logmsg'], $item['logtime']));
		}
	}
	return $item;
}

function grid_syslog_delete_hostsalarm($delete_time) {
	db_execute_prepared('DELETE t1
		FROM grid_hosts_alarm t1
		INNER JOIN syslog_logs t2
		ON t1.type_id=t2.seq
		WHERE t1.type=1
		AND t2.logtime < ?',
		array($delete_time));
	return $delete_time;
}

function grid_gridalarms_update_hostsalarm($item) {
	$item['time']          = date('Y-m-d H:i:s',$item['time']);
	$item['name']          = db_qstr($item['name']);
	$item['email_subject'] = db_qstr($item['email_subject']);

	if ($item['clusterid'] == 0) {
		$clusters = db_fetch_row_prepared('SELECT clusterid
			FROM host
			WHERE hostname = ?
			AND clusterid>0',
			array($item['alarmhost']));

		if (cacti_sizeof($clusters)) {
			foreach($clusters as $cluster) {
				// insert trigged record into grid_hosts_alarm table
				db_execute_prepared("INSERT INTO grid_hosts_alarm
					(`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`alert_time`,`present`)
					VALUES (?, ?, ?, ?, '2', ?, ?, '1')
					ON DUPLICATE KEY UPDATE name=VALUES(name), hostname=VALUES(hostname),
					clusterid=VALUES(clusterid),message=VALUES(message),present=1",
					array($item['alarm_id'], $item['name'], $item['alarmhost'], $cluster, $item['email_subject'], $item['time']));
			}
		}
	} else {
		// insert trigged record into grid_hosts_alarm table
		db_execute_prepared(" INSERT INTO grid_hosts_alarm
			(`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`alert_time`,`present`)
			VALUES (?, ?, ?, ?, '2', ?, ?, '1')
			ON DUPLICATE KEY UPDATE name=VALUES(name), hostname=VALUES(hostname),
			clusterid=VALUES(clusterid),message=VALUES(message),present=1",
			array($item['alarm_id'], $item['name'], $item['alarmhost'], $item['clusterid'], $item['email_subject'],  $item['time']));
	}
	return $item;
}
function grid_gridalarms_reset_hostsalarm() {
	db_execute("UPDATE grid_hosts_alarm SET present='0' WHERE type=2");
}

function grid_gridalarms_delete_hostsalarm($type_id = NULL) {
	if (!empty($type_id)) {
		db_execute_prepared("DELETE gha
			FROM grid_hosts_alarm AS gha
			LEFT JOIN gridalarms_alarm AS ga
			ON gha.type_id = ga.id
			WHERE gha.type = 2
			AND gha.type_id = ?
			AND (ga.alarm_alert = 0 OR ga.alarm_alert IS NULL OR ga.alarm_enabled = 'off')",
			array($type_id));
		return $type_id;
	} else {
		db_execute("DELETE gha
			FROM grid_hosts_alarm AS gha
			LEFT JOIN gridalarms_alarm AS ga
			ON gha.type_id = ga.id
			WHERE gha.present = 0
			AND gha.type = 2
			AND (ga.alarm_alert = 0 OR ga.alarm_alert IS NULL OR ga.alarm_enabled = 'off')");
	}
}

function grid_elim_template_to_xml($param) {
	global $struct_graph, $fields_graph_template_input_edit, $struct_elim_graph_item;
	global $config;

	include_once($config['base_path'] . '/plugins/grid/grid_elim_functions.php');

	$hash['graph_template'] = get_hash_version('elim_template') . elim_get_hash_graph_template($param['dep_id']);
	$xml_text = '';

	$graph_template = db_fetch_row_prepared('SELECT id,name
		FROM grid_elim_templates
		WHERE id = ?',
		array($param['dep_id']));

	$graph_template_graph = db_fetch_row_prepared('SELECT *
		FROM grid_elim_templates_graph
		WHERE graph_template_id = ?',
		array($param['dep_id']));

	$graph_template_items = db_fetch_assoc_prepared('SELECT *
		FROM grid_elim_templates_item
		WHERE graph_template_id = ?
		ORDER BY sequence',
		array($param['dep_id']));

	if ((empty($graph_template['id'])) || (empty($graph_template_graph['id']))) {
		return __('Invalid ELIM graph template.', 'grid');
	}

	$xml_text .= '<hash_' . $hash['graph_template'] . ">\n\t<name>" . xml_character_encode($graph_template['name']) . "</name>\n\t<graph>\n";

	/* XML Branch: <graph> */
	foreach ($struct_graph as $field_name => $field_array) {
		if ($field_array['method'] != 'spacer') {
			$xml_text .= "\t\t<t_$field_name>" . xml_character_encode($graph_template_graph['t_' . $field_name]) . "</t_$field_name>\n";
			$xml_text .= "\t\t<$field_name>" . xml_character_encode($graph_template_graph[$field_name]) . "</$field_name>\n";
		}
	}

	$xml_text .= "\t</graph>\n";

	/* XML Branch: <items> */

	$xml_text .= "\t<items>\n";

	$i = 0;
	if (cacti_sizeof($graph_template_items) > 0) {
		foreach ($graph_template_items as $item) {
			//$hash['graph_template_item'] = get_hash_version('graph_template_item') . get_hash_graph_template($item['id'], 'graph_template_item');
			$hash['graph_template_item'] = get_hash_version('elim_template_item') . elim_get_hash_graph_template($item['id'], 'grid_elim_template_item');

			$xml_text .= "\t\t<hash_" . $hash['graph_template_item'] . ">\n";

			reset($struct_elim_graph_item);
			foreach ($struct_elim_graph_item as $field_name => $field_array) {
				if (!empty($item[$field_name])) {
					switch ($field_name) {
						case 'task_item_id':
							$xml_text .= "\t\t\t<$field_name>hash_" . get_hash_version('data_template_item') . get_hash_data_template($item[$field_name], 'data_template_item') . "</$field_name>\n";
							break;
						case 'cdef_id':
							$xml_text .= "\t\t\t<$field_name>hash_" . get_hash_version('cdef') . get_hash_cdef($item[$field_name]) . "</$field_name>\n";
							break;
						case 'vdef_id':
							$xml_text .= "\t\t\t<$field_name>hash_" . get_hash_version('vdef') . get_hash_vdef($item[$field_name]) . "</$field_name>\n";
							break;
						case 'gprint_id':
							$xml_text .= "\t\t\t<$field_name>hash_" . get_hash_version('gprint_preset') . get_hash_gprint($item[$field_name]) . "</$field_name>\n";
							break;
						case 'color_id':
							$xml_text .= "\t\t\t<$field_name>" . db_fetch_cell_prepared('SELECT hex FROM colors WHERE id = ?', array($item[$field_name])) . "</$field_name>\n";
							break;
						default:
							$xml_text .= "\t\t\t<$field_name>" . xml_character_encode($item[$field_name]) . "</$field_name>\n";
							break;
					}
				} else {
					$xml_text .= "\t\t\t<$field_name>" . xml_character_encode($item[$field_name]) . "</$field_name>\n";
				}
			}

			$xml_text .= "\t\t</hash_" . $hash['graph_template_item'] . ">\n";

			$i++;
		}
	}

	$xml_text .= "\t</items>\n";

	/* XML Branch: <inputs> */
	$xml_text .= '</hash_' . $hash['graph_template'] . '>';

	$param['xml_text']= $xml_text;
	return $param;
}

function grid_elim_resolve_dependencies($param) {
	global $elim_data_input_hash;
	if ($param['type']== 'elim_template') {
		/* From data_template begin */
		/* dep: data input method */
		$item = db_fetch_row_prepared('SELECT id AS data_input_id
			FROM data_input
			WHERE hash = ?',
			array($elim_data_input_hash));

		if ((!empty($item)) && (!isset($param['dep_array']['data_input_method'][$item['data_input_id']]))) {
			$param['dep_array'] = resolve_dependencies('data_input_method', $item['data_input_id'], $param['dep_array']);
		}

		/* dep: round robin archive */
		/* From data_template end */

		/* dep: data template */
		/* dep: cdef */
		$graph_template_items = db_fetch_assoc_prepared('SELECT cdef_id
			FROM grid_elim_templates_item
			WHERE graph_template_id = ?
			AND cdef_id > 0
			GROUP BY cdef_id',
			array($param['id']));

		if (cacti_sizeof($graph_template_items)) {
			foreach ($graph_template_items as $item) {
				if (!isset($param['dep_array']['cdef'][$item['cdef_id']])) {
					$param['dep_array'] = resolve_dependencies('cdef', $item['cdef_id'], $param['dep_array']);
				}
			}
		}

		/* dep: gprint preset */
		$graph_template_items = db_fetch_assoc_prepared('SELECT gprint_id
			FROM grid_elim_templates_item
			WHERE graph_template_id = ?
			AND gprint_id > 0
			GROUP BY gprint_id',
			array($param['id']));

		if (cacti_sizeof($graph_template_items) > 0) {
		foreach ($graph_template_items as $item) {
			if (!isset($param['dep_array']['gprint_preset'][$item['gprint_id']])) {
				$param['dep_array'] = resolve_dependencies('gprint_preset', $item['gprint_id'], $param['dep_array']);
			}
		}
		}
	}
	return $param;
}

function grid_page_head() {
	global $config;

	if (file_exists($config['base_path'] . '/plugins/grid/include/main.js')) {
		print "<script type='text/javascript' src='" . $config['url_path'] . "plugins/grid/include/main.js'></script>\n";
	}
	print get_md5_include_css('/plugins/grid/include/main.css');
}

function grid_elim_customize_template_details($param) {
	global $config;
	global $grid_elim_customize_graph_flag;

	$grid_elim_customize_graph_flag = false;
	if(!isset($param['id'])) {
		return $param;
	}
	$local_graph_id = $param['id'];
	$grid_elim_template_id = db_fetch_cell_prepared('SELECT grid_elim_template_id
		FROM grid_elim_templates_graph_map
		WHERE local_graph_id = ?',
		array($local_graph_id));
	if(!empty($grid_elim_template_id)) {
		$grid_elim_name = db_fetch_cell_prepared('SELECT name
			FROM grid_elim_templates
			WHERE id = ?',
			array($grid_elim_template_id));
		if(!empty($grid_elim_name)) {
			$param['name'] = $grid_elim_name;
			$param['url'] = $config['url_path'] . 'plugins/grid/grid_elim_templates.php?action=template_edit&id=' . $grid_elim_template_id;
			$param['source'] = 999;
			$grid_elim_customize_graph_flag = true;
		}
	}
	return $param;

}

function grid_elim_customize_graph($param) {
	global $graph_sources;
	global $grid_elim_customize_graph_flag;

	if($grid_elim_customize_graph_flag == true) {
		$graph_sources[999] = __('ELIM Template', 'grid');
		$param['graph_source'] = 999;
	}
	return $param;
}

function grid_elim_xml_to_template($param) {
	global $struct_graph, $struct_elim_graph_item, $fields_graph_template_input_edit, $hash_version_codes, $import_debug_info;

	$hash       = $param['hash'];
	$xml_array  = $param['xml_array'];
	$hash_cache = $param['hash_cache'];

	if ($param['type']!='elim_template') {
		$import_debug_info['type'] = 'new';
		$import_debug_info['title'] = $xml_array['name'];
		$import_debug_info['result'] = 'fail';
		return $param;
	}

	/* import into: graph_templates */
	$_graph_template_id = db_fetch_cell_prepared('SELECT id
		FROM grid_elim_templates
		WHERE hash = ?',
		array($hash));

	$save['id']   = (empty($_graph_template_id) ? '0' : $_graph_template_id);
	$save['hash'] = $hash;
	$save['name'] = $xml_array['name'];

	//$graph_template_id = sql_save($save, 'graph_templates');
	$graph_template_id = sql_save($save, 'grid_elim_templates');

	$hash_cache['graph_template'][$hash] = $graph_template_id;

	/* import into: graph_templates_graph */
	unset($save);
	$save['id'] = (empty($_graph_template_id) ? '0' : db_fetch_cell_prepared('SELECT grid_elim_templates_graph.id
		FROM (grid_elim_templates, grid_elim_templates_graph)
		WHERE grid_elim_templates.id=grid_elim_templates_graph.graph_template_id
		AND grid_elim_templates.id = ?', array($graph_template_id)));

	$save['graph_template_id'] = $graph_template_id;

	/* parse information from the hash */
	/*$parsed_hash = parse_xml_hash($hash);*/

	reset($struct_graph);
	foreach ($struct_graph as $field_name => $field_array) {
		/* make sure this field exists in the xml array first */
		if (isset($xml_array['graph']['t_' . $field_name])) {
			$save['t_' . $field_name] = $xml_array['graph']['t_' . $field_name];
		}

		/* make sure this field exists in the xml array first */
		if (isset($xml_array['graph'][$field_name])) {
			if (($field_name == 'unit_exponent_value') && (get_version_index($param['version']) < get_version_index('0.8.5')) && ($xml_array['graph'][$field_name] == '0')) { /* backwards compatability */
				$save[$field_name] = '';
			} else {
				$save[$field_name] = addslashes(xml_character_decode($xml_array['graph'][$field_name]));
			}
		}
	}

	$graph_template_graph_id = sql_save($save, 'grid_elim_templates_graph');

	/* import into: graph_templates_item */
	if (is_array($xml_array['items'])) {
		foreach ($xml_array['items'] as $item_hash => $item_array) {
			/* parse information from the hash */
			$parsed_hash = parse_xml_hash($item_hash);

			/* invalid/wrong hash */
			if ($parsed_hash == false) {
				$import_debug_info['type'] = (empty($_graph_template_id) ? 'new' : 'update');
				$import_debug_info['title'] = $xml_array['name'];
				$import_debug_info['result'] = (empty($graph_template_id) ? 'fail' : 'success');
				return false;
			}

			unset($save);
			$_graph_template_item_id = db_fetch_cell_prepared('SELECT id
				FROM grid_elim_templates_item
				WHERE hash = ?
				AND graph_template_id = ?',
				array($parsed_hash['hash'], $graph_template_id));

			$save['id']   = (empty($_graph_template_item_id) ? '0' : $_graph_template_item_id);
			$save['hash'] = $parsed_hash['hash'];
			$save['graph_template_id'] = $graph_template_id;

			reset($struct_elim_graph_item);
			foreach ($struct_elim_graph_item as $field_name => $field_array) {
				/* make sure this field exists in the xml array first */
				if (isset($item_array[$field_name])) {
					/* is the value of this field a hash or not? */
					if (preg_match('/hash_([a-f0-9]{2})([a-f0-9]{4})([a-f0-9]{32})/', $item_array[$field_name])) {
						$save[$field_name] = resolve_hash_to_id($item_array[$field_name], $hash_cache, 'grid_elim_templates_item');
					} elseif (($field_name == 'color_id') && (preg_match('/^[a-fA-F0-9]{6}$/', $item_array[$field_name])) && (get_version_index($parsed_hash['version']) >= get_version_index('0.8.5'))) { /* treat the 'color' field differently */
						$color_id = db_fetch_cell_prepared('SELECT id FROM colors WHERE hex = ?', array($item_array[$field_name]));

						if (empty($color_id)) {
							db_execute_prepared('INSERT INTO colors
								(hex) VALUES (?)',
								array($item_array[$field_name]));

							$color_id = db_fetch_insert_id();
						}

						$save[$field_name] = $color_id;
					} else {
						$save[$field_name] = addslashes(xml_character_decode($item_array[$field_name]));
					}
				}
			}

			//$graph_template_item_id = sql_save($save, "graph_templates_item");
			$graph_template_item_id = sql_save($save, 'grid_elim_templates_item');

			//$hash_cache['graph_template_item']{$parsed_hash['hash']} = $graph_template_item_id;
			$hash_cache['grid_elim_template_item'][$parsed_hash['hash']] = $graph_template_item_id;
		}
	}

	/* import into: graph_template_input */
	/* status information that will be presented to the user */
	$import_debug_info['type'] = (empty($_graph_template_id) ? 'new' : 'update');
	$import_debug_info['title'] = $xml_array['name'];
	$import_debug_info['result'] = (empty($graph_template_id) ? 'fail' : 'success');

	$param['hash_cache']=$hash_cache;
	return $param;
}

/*grid cluster views filter hook function*/
function grid_filter_views($page_name='grid_clusterdb', $filter_change_func='applyClusterdbFilterChange', $form_name='form_grid_view_clusterdb') {
	$options = array(
		'summary' => __('Cluster Summary', 'grid'),
		'status'  => __('Cluster Status', 'grid'),
		'master'  => __('Master Status', 'grid'),
		'tput'    => __('Job Throughput Status', 'grid'),
		'perfmon' => __('Perfmon Status', 'grid'),
		'benchmark_exceptional_jobs' => __('Benchmark Jobs Exceptions', 'grid')
	);

	print "<td>" . __('Views', 'grid') . "</td>\n";
	print "<td><select id='view_filter' style='display:none' name='view_filter' multiple='multiple'>\n";
	foreach($options as $key => $value) {
		print "<option value='" . $key . "'" . (isset_request_var($key) && get_nfilter_request_var($key) == 'true' ? ' selected':'') . ">" . $value . "</option>\n";
	}
	print "</select>\n";
	print "</td>\n";

	?>
	<script type='text/javascript'>
	$(function() {
		$('#view_filter').multiselect({
			close: function() {
				window[<?php print "'$filter_change_func'";?>](document.getElementById(<?php echo "'$form_name'";?>));
			}
		});
	});

	</script>
	<?php
}

/*grid clusterdb graphs filter hook function*/
function grid_filter_graphs($page_name = 'grid_clusterdb', $filter_change_func = 'applyClusterdbFilterChange', $form_name = 'form_grid_view_clusterdb') {
	$options = array(
		'limstat'    => __('LIM Status', 'grid'),
		'batchstat'  => __('Batch Status', 'grid'),
		'gridstat'   => __('LSF Status', 'grid'),
		'memavastat' => __('Memory Slot Availability', 'grid'),
	);

	print "<td>" . __('Charts', 'grid') . "</td>\n";
	print "<td><select id='graph_filter' style='display:none' name='graph_filter' multiple='multiple'>\n";
	foreach($options as $key => $value) {
		print "<option value='" . $key . "'" . (isset_request_var($key) && get_nfilter_request_var($key) == 'true' ? ' selected':'') . ">" . $value . "</option>\n";
	}
	print "</select>\n";
	print "</td>\n";

	?>
	<script type='text/javascript'>
	$(function() {
		$('#graph_filter').multiselect({
			close: function() {
				window[<?php print "'$filter_change_func'";?>](document.getElementById(<?php echo "'$form_name'";?>));
			}
		});
	});
	</script>
	<?php
}

function grid_view_cluster_summary ($params) {
	show_view_summary($params[0]);
	return $params;
}

function grid_view_cluster_status () {
	show_view_status();
}

function grid_view_cluster_master () {
	show_view_master();
}

function grid_view_cluster_tput () {
	show_view_tput();
}

function grid_view_cluster_perfmon () {
	show_view_perfmon();
}

function grid_view_cluster_benchmark_exceptional_jobs () {
	show_view_benchmark_exceptional_jobs();
}

function grid_graph_cluster_limstat () {
	global $config;
	include_once($config['library_path'] . '/rtm_functions.php');

	show_graph_limstat();
}

function grid_graph_cluster_batchstat () {
	global $config;
	include_once($config['library_path'] . '/rtm_functions.php');

	show_graph_batchstat();
}

function grid_graph_cluster_gridstat () {
	global $config;
	include_once($config['library_path'] . '/rtm_functions.php');

	show_graph_gridstat();
}

function grid_graph_cluster_memavastat () {
	global $config;
	include_once($config['library_path'] . '/rtm_functions.php');

	show_graph_memavastat();
}

function grid_auth_profile_tabs (&$tabs) {
	global $config, $tabs_grid;

	if (cacti_sizeof($tabs_grid)) {
		$i = 0;

		foreach (array_keys($tabs_grid) as $tab_short_name) {
			$tabs[$tab_short_name] = array(
				'display' => __($tabs_grid[$tab_short_name]),
				'url'     => $config['url_path'] . "auth_profile.php?tab=$tab_short_name&header=false"
			);
		}
	}

	return $tabs;
}

function grid_auth_profile_run_action ($current_tab) {
	global $config;
	global $tabs_grid, $grid_settings;

	if (empty($current_tab) && isset_request_var('tab')) {
		$current_tab = get_request_var('tab');
	}

	/* set the default settings category */
	$rtm_tabs = array_keys($tabs_grid);
	if (empty($current_tab)) {
		/* there is no selected tab; select the first one */
		$current_tab = $rtm_tabs[0];
	}

	if (!in_array($current_tab, $rtm_tabs)) {
		return $current_tab;
	}

	if (isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];

		if (strpos($referer, 'auth_profile.php') === false) {
			$timespan_sel_pos = strpos($referer, '&predefined_timespan');
			if ($timespan_sel_pos) {
				$referer = substr($referer, 0, $timespan_sel_pos);
			}

			$_SESSION['profile_referer'] = $referer;
		}
	} elseif (!isset($_SESSION['profile_referer'])) {
		$_SESSION['profile_referer'] = 'graph_view.php';
	}

	form_start('auth_profile.php', 'chk');

	if ($current_tab == 'host') {
		append_host_elim_name();
	}

	print "<table style='width:100%;'><tr><td style='padding:0px;'>\n";

	html_start_box(__('RTM Settings (%s)', $tabs_grid[$current_tab], 'grid'), '100%', true, '3', 'center', '');

	$form_array = array();

	if ($current_tab == 'pendignore') {
		collect_pending_ignore_setting();
	}

	// Hook for metadata columns
	// Plugin API does not allow us to pass by reference. Pity. So we send around variables in $_REQUEST.
	set_request_var('meta_arr', $grid_settings[$current_tab]);

	api_plugin_hook_function('grid_meta_settings_tab', $tabs_grid[$current_tab]);

	$grid_settings[$current_tab] = get_request_var('meta_arr');

	foreach ($grid_settings[$current_tab] as $field_name => $field_array) {
		$form_array += array($field_name => $field_array);

		if ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
				if (grid_config_value_exists($sub_field_name, $_SESSION['sess_user_id'])) {
					$form_array[$field_name]['items'][$sub_field_name]['form_id'] = 1;
				}

				$form_array[$field_name]['items'][$sub_field_name]['value'] = read_grid_config_option($sub_field_name, true);
			}
		} else {
			if (grid_config_value_exists($field_name, $_SESSION['sess_user_id'])) {
				$form_array[$field_name]['form_id'] = 1;
			}
			if ($current_tab == 'pendignore') {
				$form_array[$field_name]['value'] = query_pending_ignore_setting_value($field_name);
			} else {
				$form_array[$field_name]['value'] = read_grid_config_option($field_name, true);
			}
		}
	}

	draw_edit_form(
		array(
			'config' => array(),
			'fields' => $form_array
		)
	);

	html_end_box(true, true);

	form_hidden_box('tab', $current_tab, '');

	//form_save_button('auth_profile.php?tab=' . $current_tab, 'save');
	form_save_buttons(array(array('id' => 'return', 'value' => __esc('Return'))));
	form_end();
	?>
	<script type='text/javascript'>
	var currentTab = '<?php print $current_tab;?>';

	$(function() {
		if (currentTab == 'visual') {
			$('#grid_summary_filter').multiselect({
				height: 300,
				noneSelectedText: '<?php print __('Select State(s)', 'grid');?>',
				selectedText: function(numChecked, numTotal, checkedItems) {
					myReturn = numChecked + ' <?php print __('States Selected', 'grid');?>';
					return myReturn;
				},
				checkAllText: '<?php print __('All', 'grid');?>',
				uncheckAllText: '<?php print __('None', 'grid');?>',
				uncheckall: function() {
					$(this).multiselect('widget').find(':checkbox:first').each(function() {
						$(this).prop('checked', true);
					});
				}
			}).multiselectfilter( {
				label: '<?php print __('Search', 'grid');?>',
				placeholder: '<?php print __('Enter keyword', 'grid');?>',
				width: '150'
			});

		}
		$('select, input[type!="button"]').unbind().keyup(function() {
			name  = $(this).attr('id');
			if ($(this).attr('type') == 'checkbox') {
				if ($(this).is(':checked')) {
					value = 'on';
				} else {
					value = '';
				}
			} else {
				value = $(this).val();
			}

			$.post('auth_profile.php?tab='+currentTab+'&action=update_data', {
				__csrf_magic: csrfMagicToken,
				name: name,
				value: value
			});
		}).change(function() {
			name  = $(this).attr('id');
			if ($(this).attr('type') == 'checkbox') {
				if ($(this).is(':checked')) {
					value = 'on';
				} else {
					value = '';
				}
			} else {
				value = $(this).val();
			}

			$.post('auth_profile.php?tab='+currentTab+'&action=update_data', {
				__csrf_magic: csrfMagicToken,
				name: name,
				value: value
				}, function() {
				if (name == 'selected_theme' || name == 'user_language') {
					document.location = 'auth_profile.php?action=edit';
				}
			});
		});
		$('#return').click(function() {
			document.location = '<?php print $_SESSION['profile_referer'];?>';
		});
	});

	</script>
	<?php

	return $current_tab;
}

function grid_auth_profile_update_data ($current_tab) {
	$name  = get_nfilter_request_var('name');
	$value = get_nfilter_request_var('value');

	$user = $_SESSION['sess_user_id'];
	if (!empty($user) && !empty($name)) {
		if ($current_tab == 'pendignore') {
			save_pending_ignore_setting($name, $value);
		} else {
			if ($name == 'grid_summary_filter') {
				db_execute_prepared("DELETE FROM grid_settings WHERE user_id = ? AND name LIKE 'grid_filter_%'", array($user));
				if (cacti_sizeof($value)) {
				    foreach ($value as $filter_number) {
						if (!empty($filter_number)) {
							db_execute_prepared('REPLACE INTO grid_settings
								(user_id, name, value)
								VALUES (?, ?, ?)',
								array($user, 'grid_filter_' . $filter_number, $filter_number));
						}
					}
				}
			} else {
				if ($name=='max_length_jobname') {
					if ($value <= 0 || $value > 119) {
						set_request_var($name, 20);
					}
				}
				db_execute_prepared('REPLACE INTO grid_settings
					(user_id, name, value)
					VALUES (?, ?, ?)',
					array($user, $name, $value));
			}
		}
	}
	/* reset local settings cache so the user sees the new settings */
	kill_session_var('sess_grid_config_array');
	if(empty($value) && (substr($name, 0, 10) =='host_elim_' || $name =='show_jobclustername')){
		if(cacti_sizeof($_SESSION)){
		    foreach ($_SESSION as $session_name => $session_value){
				if(substr($session_name, -11) =='sort_column' && (('host_elim_'. $session_value) == $name || 'show_'. $session_value == $name)){
					unset($_SESSION[$session_name]);
					$sort_direction_name = substr($session_name, 0, strlen($session_name)-11) . 'sort_direction';
					unset($_SESSION[$sort_direction_name]);
				}
		    }
		}
		if(cacti_sizeof($_SESSION['sort_data'])){
			if(substr($name, 0, 10) =='host_elim_') {
				$short_name = substr($name, 10);//delete host_elim_
			} else {
				$short_name = substr($name, 5);//delete show_
			}
			foreach ($_SESSION['sort_data'] as $page => $sort_data_array){
				if(array_key_exists($short_name, $sort_data_array)){
					unset($_SESSION['sort_data'][$page]);
					unset($_SESSION['sort_string'][$page]);
				}
			}
		}
	}
	return $current_tab;
}

function grid_auth_profile_save () {
	global $config;
	global $tabs_grid, $grid_settings;

	//grid_user_save();
	//reset_user_perms(get_filter_request_var('id'));
	//header('Location: auth_profile.php?tab=grid_settings_edit&header=false');
	input_validate_input_regex(get_nfilter_request_var('tab'), '^([a-zA-Z0-9_]+)$');

	if (isset_request_var('tab') && get_request_var('tab') == 'visual') {
		/* remove stale records */
		db_execute_prepared("DELETE FROM grid_settings WHERE user_id = ? AND name LIKE 'grid_filter%'", array($_SESSION['sess_user_id']));
	} else if (get_nfilter_request_var('tab') == 'pendignore') {
		db_execute_prepared("UPDATE grid_pendreasons_ignore SET present='0' WHERE user_id = ?", array($_SESSION['sess_user_id']));
		collect_pending_ignore_setting();
	} else if (get_nfilter_request_var('tab') == 'host') {
		db_execute_prepared("DELETE FROM grid_settings WHERE user_id = ? AND name LIKE 'host_elim_%'", array($_SESSION['sess_user_id']));
		append_host_elim_name();
	}

	// Hook for metadata columns
	// Plugin API does not allow us to pass by reference. Pity. So we send around variables in $_REQUEST.
	set_request_var('meta_arr', $grid_settings[get_nfilter_request_var('tab')]);

	api_plugin_hook_function('grid_meta_settings_tab', $tabs_grid[get_request_var('tab')]);

	$grid_settings[get_nfilter_request_var('tab')] = get_request_var('meta_arr');

	foreach ($grid_settings[get_request_var('tab')] as $field_name => $field_array) {
		if (($field_array['method'] == 'header') || ($field_array['method'] == 'spacer' )) {
			/* do nothing */
		} else if ($field_name == 'grid_summary_filter') {
			/* repopulate with new ones */
			if (isset_request_var('grid_summary_filter')) {
				foreach(get_request_var('grid_summary_filter') as $index => $value) {
					set_grid_config_option('grid_filter_' . $value, $value);
				}
			}
		} else if ((isset($field_array['items'])) && (is_array($field_array['items']))) {
			foreach ($field_array['items'] as $sub_field_name => $sub_field_array) {
				set_grid_config_option($sub_field_name, isset_request_var($sub_field_name) ? get_request_var($sub_field_name) : '');
			}
		} else if (get_request_var('tab') == 'pendignore') {
			$field_value = (isset_request_var($field_name) ? trim(get_request_var($field_name)) : '');
			save_pending_ignore_setting($field_name, $field_value);
		} else {
			if ($field_name=='max_length_jobname') {
				if (get_request_var($field_name) <= 0 || get_request_var($field_name)> 119) {
					set_request_var($field_name, 20);
				}
			}

			set_grid_config_option($field_name, isset_request_var($field_name) ? get_request_var($field_name) : '');
		}
	}

	raise_message(1);

	/* reset local settings cache so the user sees the new settings */
	kill_session_var('sess_grid_config_array');

	if (isset($_SESSION['grid_return_to'])) {
		$return_to = $_SESSION['grid_return_to'];
		kill_session_var('grid_return_to');
		header('Location: ' . $return_to);
	} else {
		header('Location: auth_profile.php?header=false&tab=' . get_request_var('tab'));
	}
}
