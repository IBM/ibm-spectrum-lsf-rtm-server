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
include_once('./lib/utility.php');
include_once('./lib/api_data_source.php');
include_once('./lib/api_tree.php');
include_once('./lib/api_graph.php');
include_once('./lib/data_query.php');
include_once('./lib/api_device.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$grid_mhosts_actions = array(
	1 => __('Delete')
);

/* set default action */
set_default_action();

switch (get_request_var('action')) {
case 'actions':
	form_actions();

	break;
default:
	top_header();
	grid_manage_hosts();
	bottom_footer();

	break;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $config, $grid_mhosts_actions;

	/* remember or default these values */
	load_current_session_value('type', 'sess_grid_view_manage_hosts_type', '0');
	load_current_session_value('integrated', 'sess_grid_view_manage_hosts_integrated', '-1');

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

		if ($selected_items != false) {
		if (get_request_var('drp_action') == '1') { /* delete */
			if ((isset_request_var('delete_type')) && (get_request_var('delete_type') == '2')) { // disable
				for ($i=0;($i<count($selected_items));$i++) {
					$vars      = explode('|', base64_decode($selected_items[$i]));
					$clusterid = $vars[0];
					$host      = $vars[1];

					/* error checking */
					if ($host == '' || $clusterid == '') continue;

					$host_id = db_fetch_cell_prepared('SELECT id
						FROM host
						WHERE clusterid = ?
						AND hostname = ?',
						array($clusterid, $host));

					if (!empty($host_id)) {
						/* disable the host */
						db_execute_prepared('UPDATE host SET disabled="on" WHERE id = ?', array($host_id));

						/* update poller cache */
						db_execute_prepared('DELETE from poller_item WHERE host_id = ?', array($host_id));
						db_execute_prepared('DELETE from poller_reindex WHERE host_id = ?', array($host_id));
					}

					api_grid_host_remove($host, $clusterid);
				}
			}else if ((isset_request_var('delete_type')) && (get_request_var('delete_type') == '1')) { // delete
				$data_sources_to_act_on = array();
				$graphs_to_act_on       = array();
				$devices_to_act_on      = array();

				for ($i=0;($i<count($selected_items));$i++) {
					$vars      = explode('|', base64_decode($selected_items[$i]));
					$clusterid = $vars[0];
					$host      = $vars[1];

					/* error checking */
					if ($host == '' || $clusterid == '') continue;

					$host_id = db_fetch_cell_prepared('SELECT id
						FROM host
						WHERE clusterid = ?
						AND hostname = ?',
						array($clusterid, $host));

					if (!empty($host_id)) {
						$devices_to_act_on[] = $host_id;

						$data_sources = db_fetch_assoc_prepared('SELECT
							data_local.id as local_data_id
							FROM data_local
							WHERE data_local.host_id = ?',
							array($host_id));

						if (cacti_sizeof($data_sources)) {
							foreach ($data_sources as $data_source) {
								$data_sources_to_act_on[] = $data_source['local_data_id'];
							}
						}

						$graphs = db_fetch_assoc_prepared('SELECT
							graph_local.id as local_graph_id
							FROM graph_local
							WHERE graph_local.host_id = ?',
							array($host_id));

						if (cacti_sizeof($graphs)) {
							foreach ($graphs as $graph) {
								$graphs_to_act_on[] = $graph['local_graph_id'];
							}
						}
					}

					api_grid_host_remove($host, $clusterid);
				}

				api_data_source_remove_multi($data_sources_to_act_on);
				api_graph_remove_multi($graphs_to_act_on);
				api_device_remove_multi($devices_to_act_on);
			}else{
				for ($i=0;($i<count($selected_items));$i++) {
					$vars      = explode('|', base64_decode($selected_items[$i]));
					$clusterid = $vars[0];
					$host      = $vars[1];

					/* error checking */
					if ($host == '' || $clusterid == '') continue;

					api_grid_host_remove($host, $clusterid);
				}
			}
		}
		}

		header('Location: grid_manage_hosts.php?header=false');
		exit;
	}

	if(!isset($grid_mhosts_actions[get_request_var('drp_action')])) {
		header('Location: grid_manage_hosts.php?header=false');
		exit;
	}

	/* setup some variables */
	$host_list = ''; $i = 0;

	/* loop through each of the hosts selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (substr_count($var, 'chk_')) {
			$var       = str_replace('chk_', '', $var);
			$vars      = explode('|', base64_decode($var));
			$clusterid = $vars[0];
			$host      = $vars[1];

			if ($val == 'on') {
				if (get_request_var('integrated') == '2') {
					$host_info = db_fetch_cell_prepared('SELECT hostname AS host
						FROM host
						WHERE clusterid = ?
						AND hostname = ?',
						array($clusterid, $host));
				}else{
					$host_info = db_fetch_cell_prepared('SELECT host
						FROM grid_hostinfo
						WHERE clusterid = ?
						AND host = ?',
						array($clusterid, $host));
				}

				$host_list .= '<li>' . html_escape($host_info) . '</li>';
				$host_array[$i] = $var;

				$i++;
			}
		}
	}

	top_header();

	form_start('grid_manage_hosts.php');

	html_start_box($grid_mhosts_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (get_request_var('drp_action') == '1') { /* delete */
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following hosts.') . "</p>
				<ul>$host_list</ul>";

		if (get_request_var('type') == 0) {
			form_radio_button('delete_type', '2', '2', __('Disable All Cacti Hosts. Preserving Legacy Graphs and Data Soures.'), '1'); print '<br>';
			form_radio_button('delete_type', '2', '1', __('Delete All Cacti Hosts. Removing Legacy Graphs and Data Sources'), '1'); print '<br>';
		}

		print '</td></tr>
			</td>
		</tr>';
	}

	if (!isset($host_array)) {
		raise_message(40);
		header('Location: grid_manage_hosts.php?header=false');
		exit;
	}else{
		$save_html = "<input type='submit' value='" . __('Continue') . "' alt=''>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($host_array) ? serialize($host_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . __('Return') . "' alt='' onClick='cactiReturnTo()'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function api_grid_host_remove($host, $clusterid) {
	db_execute_prepared('DELETE FROM grid_hostinfo WHERE clusterid = ? AND host = ?', array($clusterid, $host));
}

/* ---------------------
    Manage Hosts Functions
   --------------------- */

function grid_get_host_records(&$sql_where, &$sql_join, $apply_limits = TRUE, $rows = 30, &$sql_params = array()) {
	$sql_order = get_order_string();

	if (get_request_var('integrated') != '2') {
		/* clusterid sql where */
		if (get_request_var('clusterid') == '0') {
			/* Show all items */
		}else {
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_clusters.clusterid=?)';
			$sql_params[] = get_request_var('clusterid');
		}

		/* host type sql where */
		if (get_request_var('integrated') != '2') {
			if (get_request_var('type') == '-1') {
				/* Show all items */
			}else if (get_request_var('type') == '0') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="1")';
			}else if (get_request_var('type') == '1') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0" AND grid_hostinfo.licFeaturesNeeded=16)';
			}else if (get_request_var('type') == '2') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0" AND grid_hostinfo.licFeaturesNeeded=512)';
			}else{
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0")';
			}
		}

		/* integrated sql where */
		if (get_request_var('integrated') == '-1') {
			/* Show all items */
		}else if (get_request_var('integrated') == '0') {
			$sql_join   = 'INNER JOIN grid_hostinfo ON grid_clusters.clusterid=grid_hostinfo.clusterid)
				INNER JOIN host ON host.hostname=grid_hostinfo.host AND host.clusterid=grid_hostinfo.clusterid';
		}else if (get_request_var('integrated') == '1') {
			$sql_join   = 'INNER JOIN grid_hostinfo ON grid_clusters.clusterid=grid_hostinfo.clusterid)
				LEFT JOIN host ON host.hostname=grid_hostinfo.host AND host.clusterid=grid_hostinfo.clusterid';
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(host.clusterid IS NULL)';
		}

		/* integrated sql where */
		if (get_request_var('lastseen') == '-2') {
			/* don't do anything */
		}else if (get_request_var('lastseen') == '-1') {
			$date = date('Y-m-d H:i:s', time()-1200);
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen>?)';
			$sql_params[] = $date;
		}else if (get_request_var('lastseen') == '0') {
			$date1 = date('Y-m-d H:i:s', time()-(86400*30));
			$date2 = date('Y-m-d H:i:s', time()-(86400*7));
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen>?) AND (grid_hostinfo.last_seen<?)';
			$sql_params[] = $date1;
			$sql_params[] = $date2;
		}else if (get_request_var('lastseen') == '1') {
			$date = date('Y-m-d H:i:s', time()-(86400*30));
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen<?)';
			$sql_params[] = $date;
		}

		/* filter sql where */
		if (get_request_var('filter') != '') {
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(
				(grid_hostinfo.host LIKE ?) OR
				(grid_hostinfo.last_seen LIKE ?) OR
				(grid_hostinfo.first_seen LIKE ?) OR
				(grid_hostinfo.hostModel LIKE ?) OR
				(grid_hostinfo.hostType LIKE ?) OR
				(grid_clusters.clustername LIKE ?))';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}

		if (!strlen($sql_join)) {
			$sql_join .= ' INNER JOIN grid_hostinfo ON grid_clusters.clusterid=grid_hostinfo.clusterid)';
		}

		$sql_query = "SELECT grid_hostinfo.*, grid_clusters.clustername
			FROM (grid_clusters
			$sql_join
			$sql_where
			$sql_order";
	}else{
		/* filter sql where */
		if (get_request_var('filter') != '') {
			$sql_where .= ' AND ((host.description LIKE ?) OR (host.hostname LIKE ?))';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}

		$sql_query = "SELECT host.hostname as host, 'Unknown' as clustername, host.clusterid, 'N/A' as hostType, 'N/A' as hostModel,
			'N/A' as cpuFactor, 'N/A' as maxCpus, 'N/A' as maxMem, 'N/A' as maxSwap, 'N/A' as maxTmp, 'N/A' as nDisks, 'N/A' as resources,
			'N/A' as windows, '1' as isServer, 'N/A' as licensed, 'N/A' as rexPriority, 'N/A' as licFeaturesNeeded, 'N/A' as licClass,
			'N/A' as cores, 'N/A' as first_seen, 'N/A' as last_seen
			FROM host
			LEFT JOIN grid_hostinfo
			ON grid_hostinfo.clusterid=host.clusterid
			AND grid_hostinfo.host=host.hostname
			WHERE host.clusterid>0
			AND host.host_template_id!=12
			AND grid_hostinfo.host IS NULL
			AND host.hostname!='localhost' AND host.disabled=''
			$sql_where
			$sql_order";
	}

	if ($apply_limits) {
		 $sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//echo $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function manageHostsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Host Type');?>
					</td>
					<td>
						<select id='type'><?php if (get_request_var('integrated') != 2) {?>
							<option value='3'<?php if (get_request_var('type') == '3') {?> selected<?php }?>><?php print __('All Clients');?></option>
							<option value='1'<?php if (get_request_var('type') == '1') {?> selected<?php }?>><?php print __('Fixed Clients');?></option>
							<option value='2'<?php if (get_request_var('type') == '2') {?> selected<?php }?>><?php print __('Float Clients');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Servers');?></option>
							<?php }else{?>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Unknown');?></option>
							<?php }?>
						</select>
					</td>
					<td>
						<?php print __('Records');?>
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
					<td>
						<input type='submit' id='go' value='<?php print __('Go');?>' title='<?php print __('Search');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All');?></option>
							<?php
							$clusters = grid_get_clusterlist();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Integrated');?>
					</td>
					<td>
						<select id='integrated'>
							<option value='-1'<?php if (get_request_var('integrated') == '-1') {?> selected<?php }?>><?php print __('All');?></option>
							<option value='0'<?php if (get_request_var('integrated') == '0') {?> selected<?php }?>><?php print __('Fully Integrated');?></option>
							<option value='2'<?php if (get_request_var('integrated') == '2') {?> selected<?php }?>><?php print __('Not in LSF');?></option>
							<option value='1'<?php if (get_request_var('integrated') == '1') {?> selected<?php }?>><?php print __('Not in RTM');?></option>
						</select>
					</td>
					<td>
						<?php print __('Last Seen');?>
					</td>
					<td>
						<select id='lastseen'>
							<option value='-2'<?php if (get_request_var('lastseen') == '-2') {?> selected<?php }?>><?php print __('All');?></option>
							<option value='-1'<?php if (get_request_var('lastseen') == '-1') {?> selected<?php }?>><?php print __('Current');?></option>
							<option value='0'<?php if (get_request_var('lastseen') == '0') {?> selected<?php }?>><?php print __('%d Week', 1);?></option>
							<option value='1'<?php if (get_request_var('lastseen') == '1') {?> selected<?php }?>><?php print __('%d Month', 1);?></option>
						</select>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_manage_hosts() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_mhosts_actions, $config;
	$sql_params = array();

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
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			),
		'integrated' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'lastseen' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '0'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'host',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gmh');
	/* ================= input validation ================= */

	html_start_box(__('Manage Hosts'), '100%', '', '3', 'center', '');
	manageHostsFilter();
	html_end_box();

	$sql_where = '';
	$sql_join = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	}elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	}else{
		$rows = get_request_var('rows');
	}

	$hosts = grid_get_host_records($sql_where, $sql_join, TRUE, $rows, $sql_params);

	?>
	<script type='text/javascript'>
	$(function() {
		$('#clusterid, #type, #integrated, #lastseen, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	function applyFilter() {
		strURL  = 'grid_manage_hosts.php?header=false'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&integrated=' + $('#integrated').val();
		strURL += '&lastseen=' + $('#lastseen').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_manage_hosts.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}
	</script>
	<?php

	if (get_request_var('integrated') != '2') {
		$rows_query_string = "SELECT COUNT(*)
			FROM (grid_clusters
			$sql_join
			$sql_where";
	}else{
		$rows_query_string = "SELECT COUNT(*)
			FROM host
			LEFT JOIN grid_hostinfo ON grid_hostinfo.clusterid=host.clusterid AND grid_hostinfo.host=host.hostname
			WHERE host.clusterid>0 AND host.host_template_id!=12 AND grid_hostinfo.host IS NULL AND host.hostname!='localhost' AND host.disabled=''
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'clustername' => array(__('Cluster Name'), 'ASC'),
		'host'        => array(__('Host Name'), 'ASC'),
		'hostType'    => array(__('Host Type'), 'DESC'),
		'hostModel'   => array(__('Host Model'), 'DESC'),
		'isServer'    => array(__('Host License'), 'DESC'),
		'first_seen'  => array(__('First Seen'), 'DESC'),
		'last_seen'   => array(__('Last Seen'), 'ASC')
	);

	/* generate page list */
	 $nav = html_nav_bar('grid_manage_hosts.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Hosts'), 'page', 'main');

	form_start('grid_manage_hosts.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($hosts)) {
		if(strlen(get_request_var('filter')) >0){
			foreach ($hosts as $host) {
				$id = base64_encode($host['clusterid'] . '|' . $host['host']);
				form_alternate_row('line' . $id, false);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['clustername']);
				form_selectable_cell($display_value, $id);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['host']);
				form_selectable_cell($display_value, $id);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['hostType']);
				form_selectable_cell($display_value, $id);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['hostModel']);
				form_selectable_cell($display_value, $id);
				form_selectable_cell(($host['isServer'] == 1 ? 'Server' : (($host['licFeaturesNeeded'] == 16) ? __('Fixed Client') : __('Float Client'))), $id);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['first_seen']);
				form_selectable_cell($display_value, $id);
				$display_value = preg_replace('/(' . preg_quote(get_request_var('filter')) . ')/i', "<span style='background-color: #F8D93D;'>\\1</span>", $host['last_seen']);
				form_selectable_cell($display_value, $id);
				form_checkbox_cell($host['host'], $id);
				form_end_row();
			}
		}else{
			foreach ($hosts as $host) {
				$id = base64_encode($host['clusterid'] . '|' . $host['host']);
				form_alternate_row('line' . $id, false);
				form_selectable_cell($host['clustername'], $id);
				form_selectable_cell($host['host'], $id);
				form_selectable_cell(str_replace('UNKNOWN_AUTO_DETECT', __('Auto Detect'), $host['hostType']), $id);
				form_selectable_cell(str_replace('UNKNOWN_AUTO_DETECT', __('Auto Detect'), $host['hostModel']), $id);
				form_selectable_cell(($host['isServer'] == 1 ? 'Server' : (($host['licFeaturesNeeded'] == 16) ? __('Fixed Client') : __('Float Client'))), $id);
				form_selectable_cell($host['first_seen'], $id);
				form_selectable_cell($host['last_seen'], $id);
				form_checkbox_cell($host['host'], $id);
				form_end_row();
			}
		}
	}else{
		print "<tr><td colspan='8'><em>" . __('No Hosts Defined') . "</em></td></tr>";
	}

	html_end_box(false);

	if (cacti_sizeof($hosts)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($grid_mhosts_actions);

	form_end();
}

