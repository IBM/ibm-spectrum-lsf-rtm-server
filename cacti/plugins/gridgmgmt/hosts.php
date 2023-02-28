<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | (C) Copyright International Business Machines Corp, 2006-2022.          |
 | Portions Copyright (C) 2004-2023 The Cacti Group                        |
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
 | This code is designed, written, and maintained by the International     |
 | Business Machines Corp.                                                 |
 +-------------------------------------------------------------------------+
 | - IBM Corporation - http://www.ibm.com/                                 |
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
include_once('./plugins/gridgmgmt/functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$grid_mhosts_actions = array(
	1 => __('Delete', 'gridgmgmt')
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
						db_execute_prepared('DELETE FROM poller_item WHERE host_id = ?', array($host_id));
						db_execute_prepared('DELETE FROM poller_reindex WHERE host_id = ?', array($host_id));
					}

					api_grid_host_remove($host, $clusterid);
				}
			} else if ((isset_request_var('delete_type')) && (get_request_var('delete_type') == '1')) { // delete
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
			} else {
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

		header('Location: hosts.php?header=false');
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
				$host_info = db_fetch_cell_prepared('SELECT hostname AS host
					FROM host
					WHERE clusterid = ?
					AND hostname = ?',
					array($clusterid, $host));

				if ($host_info == '') {
					$host_info = db_fetch_cell_prepared('SELECT host
						FROM grid_hostinfo
						WHERE clusterid = ?
						AND host = ?',
						array($clusterid, $host));
				}

				$host_list .= '<li>' . $host_info . '</li>';
				$host_array[$i] = $var;

				$i++;
			}
		}
	}

	top_header();

	form_start('hosts.php');

	html_start_box($grid_mhosts_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (get_request_var('drp_action') == '1') { /* delete */
		print "<tr>
			<td class='textArea'>
				<p>" . __('Click \'Continue\' to Delete the following hosts.', 'gridgmgmt') . "</p>
				<div class='itemlist'><ul>$host_list</ul></div>";

		if (get_request_var('type') == 0) {
			form_radio_button('delete_type', '2', '2', __('Disable All Cacti Hosts. Preserving Legacy Graphs and Data Soures.', 'gridgmgmt'), '1'); print '<br>';
			form_radio_button('delete_type', '2', '1', __('Delete All Cacti Hosts. Removing Legacy Graphs and Data Sources', 'gridgmgmt'), '1'); print '<br>';
		}

		print '</td>
			</tr>';
	}

	if (!isset($host_array)) {
		raise_message(40);
		header('Location: hosts.php?header=false');
		exit;
	} else {
		$save_html = "<input type='submit' value='" . __('Continue', 'gridgmgmt') . "' alt=''>";
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($host_array) ? serialize($host_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . __('Return', 'gridgmgmt') . "' alt='' onClick='cactiReturnTo()'>
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

function grid_get_host_records(&$sql_where, &$sql_join, $apply_limits = true, $rows = 30) {
	$sql_order = get_order_string();

	if (get_request_var('integrated') != '2') {
		/* clusterid sql where */
		if (get_request_var('clusterid') == '0') {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_clusters.clusterid=' . get_request_var('clusterid') . ')';
		}

		/* host type sql where */
		if (get_request_var('integrated') != '2') {
			if (get_request_var('type') == '-1') {
				/* Show all items */
			} else if (get_request_var('type') == '0') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="1")';
			} else if (get_request_var('type') == '1') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0" AND grid_hostinfo.licFeaturesNeeded=16)';
			} else if (get_request_var('type') == '2') {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0" AND grid_hostinfo.licFeaturesNeeded=512)';
			} else {
				$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.isServer="0")';
			}
		}

		/* integrated sql where */
		if (get_request_var('integrated') == '-1') {
			/* Show all items */
		} else if (get_request_var('integrated') == '0') {
			$sql_join   = 'INNER JOIN grid_hostinfo ON grid_clusters.clusterid=grid_hostinfo.clusterid)
				INNER JOIN host ON host.hostname=grid_hostinfo.host AND host.clusterid=grid_hostinfo.clusterid';
		} else if (get_request_var('integrated') == '1') {
			$sql_join   = 'INNER JOIN grid_hostinfo ON grid_clusters.clusterid=grid_hostinfo.clusterid)
				LEFT JOIN host ON host.hostname=grid_hostinfo.host AND host.clusterid=grid_hostinfo.clusterid';
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(host.clusterid IS NULL)';
		}

		/* integrated sql where */
		if (get_request_var('lastseen') == '-2') {
			/* don't do anything */
		} else if (get_request_var('lastseen') == '-1') {
			$date = date('Y-m-d H:i:s', time()-1200);
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen>"' . $date . '")';
		} else if (get_request_var('lastseen') == '0') {
			$date1 = date('Y-m-d H:i:s', time()-(86400*30));
			$date2 = date('Y-m-d H:i:s', time()-(86400*7));
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen>"' . $date1 . '") AND (grid_hostinfo.last_seen<"' . $date2 . '")';
		} else if (get_request_var('lastseen') == '1') {
			$date = date('Y-m-d H:i:s', time()-(86400*30));
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(grid_hostinfo.last_seen<"' . $date . '")';
		}

		/* filter sql where */
		if (!strlen(get_request_var('filter'))) {
			/* Show all items */
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ' ) . '(
				(grid_hostinfo.host LIKE "%'        . get_request_var('filter') . '%") OR
				(grid_hostinfo.last_seen LIKE "%'   . get_request_var('filter') . '%") OR
				(grid_hostinfo.first_seen LIKE "%'  . get_request_var('filter') . '%") OR
				(grid_hostinfo.hostModel LIKE "%'   . get_request_var('filter') . '%") OR
				(grid_hostinfo.hostType LIKE "%'    . get_request_var('filter') . '%") OR
				(grid_clusters.clustername LIKE "%' . get_request_var('filter') . '%"))';
		}

		if (!strlen($sql_join)) {
			$sql_join .= ' INNER JOIN grid_hostinfo
				ON grid_clusters.clusterid=grid_hostinfo.clusterid)';
		}

		$sql_query = "SELECT grid_hostinfo.*, grid_clusters.clustername
			FROM (grid_clusters
			$sql_join
			$sql_where
			$sql_order";
	} else {
		/* filter sql where */
		if (get_request_var('filter') != '') {
			$sql_where .= ' AND (h.description LIKE "%' . get_request_var('filter') . '%" OR h.hostname LIKE "%' . get_request_var('filter') . '%")';
		}

		/* clusterid sql where */
		if (get_request_var('clusterid') == '0') {
			$sql_where .= ' AND (h.clusterid > 0)';
		} else {
			$sql_where .= ' AND (h.clusterid = ' . get_request_var('clusterid') . ')';
		}

		$host_templates = db_fetch_cell('SELECT
			GROUP_CONCAT(id)
			FROM host_template
			WHERE hash IN ("284bbabef4bb6e161af7e123c7c90969","7972b0b7c4b67da7ba7ebd020cf54f87")');

		$sql_query = "SELECT h.id, h.hostname as host, clustername, h.clusterid, 'N/A' as hostType, 'N/A' as hostModel,
			'N/A' as cpuFactor, 'N/A' as maxCpus, 'N/A' as maxMem, 'N/A' as maxSwap, 'N/A' as maxTmp, 'N/A' as nDisks, 'N/A' as resources,
			'N/A' as windows, '1' as isServer, 'N/A' as licensed, 'N/A' as rexPriority, 'N/A' as licFeaturesNeeded, 'N/A' as licClass,
			'N/A' as cores, 'N/A' as first_seen, 'N/A' as last_seen
			FROM host AS h
			LEFT JOIN grid_clusters AS gc
			ON h.clusterid = gc.clusterid
			LEFT JOIN grid_load AS gl
			ON h.clusterid = gl.clusterid
			AND h.hostname = gl.host
			WHERE h.host_template_id IN ($host_templates)
			AND gl.host IS NULL
			$sql_where
			$sql_order";
	}

	if ($apply_limits) {
		 $sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc($sql_query);
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
						<?php print __('Search', 'gridgmgmt');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Host Type', 'gridgmgmt');?>
					</td>
					<td>
						<select id='type'><?php if (get_request_var('integrated') != 2) {?>
							<option value='3'<?php if (get_request_var('type') == '3') {?> selected<?php }?>><?php print __('All Clients', 'gridgmgmt');?></option>
							<option value='1'<?php if (get_request_var('type') == '1') {?> selected<?php }?>><?php print __('Fixed Clients', 'gridgmgmt');?></option>
							<option value='2'<?php if (get_request_var('type') == '2') {?> selected<?php }?>><?php print __('Float Clients', 'gridgmgmt');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Servers', 'gridgmgmt');?></option>
							<?php } else {?>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Unknown', 'gridgmgmt');?></option>
							<?php }?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'gridgmgmt');?>
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
						<input type='submit' id='go' value='<?php print __('Go', 'gridgmgmt');?>' title='<?php print __('Search', 'gridgmgmt');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear', 'gridgmgmt');?>' title='<?php print __('Clear Filters', 'gridgmgmt');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridgmgmt');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'gridgmgmt');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT * FROM grid_clusters ORDER BY clustername');

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Integrated', 'gridgmgmt');?>
					</td>
					<td>
						<select id='integrated'>
							<option value='-1'<?php if (get_request_var('integrated') == '-1') {?> selected<?php }?>><?php print __('All', 'gridgmgmt');?></option>
							<option value='0'<?php if (get_request_var('integrated') == '0') {?> selected<?php }?>><?php print __('Fully Integrated', 'gridgmgmt');?></option>
							<option value='2'<?php if (get_request_var('integrated') == '2') {?> selected<?php }?>><?php print __('Not in LSF', 'gridgmgmt');?></option>
							<option value='1'<?php if (get_request_var('integrated') == '1') {?> selected<?php }?>><?php print __('Not in RTM', 'gridgmgmt');?></option>
						</select>
					</td>
					<td>
						<?php print __('Last Seen', 'gridgmgmt');?>
					</td>
					<td>
						<select id='lastseen'>
							<option value='-2'<?php if (get_request_var('lastseen') == '-2') {?> selected<?php }?>><?php print __('All', 'gridgmgmt');?></option>
							<option value='-1'<?php if (get_request_var('lastseen') == '-1') {?> selected<?php }?>><?php print __('Current', 'gridgmgmt');?></option>
							<option value='0'<?php if (get_request_var('lastseen') == '0') {?> selected<?php }?>><?php print __('%d Week', 1, 'gridgmgmt');?></option>
							<option value='1'<?php if (get_request_var('lastseen') == '1') {?> selected<?php }?>><?php print __('%d Month', 1, 'gridgmgmt');?></option>
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

	html_start_box(__('Host Graph Management', 'gridgmgmt'), '100%', '', '3', 'center', '');
	manageHostsFilter();
	html_end_box();

	$sql_where = '';
	$sql_join = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$hosts = grid_get_host_records($sql_where, $sql_join, true, $rows);

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
		strURL  = 'hosts.php?header=false'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&integrated=' + $('#integrated').val();
		strURL += '&lastseen=' + $('#lastseen').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'hosts.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}
	</script>
	<?php

	if (get_request_var('integrated') != '2') {
		$rows_query_string = "SELECT COUNT(*)
			FROM (grid_clusters
			$sql_join
			$sql_where";
	} else {
		$rows_query_string = "SELECT COUNT(*)
			FROM host AS h
			LEFT JOIN grid_load AS gl
			ON h.clusterid = gl.clusterid
			AND h.hostname = gl.host
			WHERE gl.host IS NULL
			$sql_where";
	}

	$total_rows = db_fetch_cell($rows_query_string);

	$display_text = array(
		'host' => array(
			'display' => __('Host Name', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'status' => array(
			'display' => __('Cluster Status', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'hostType' => array(
			'display' => __('Host Type', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'DESC'
		),
		'hostModel' => array(
			'display' => __('Host Model', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'DESC'
		),
		'isServer' => array(
			'display' => __('Host License', 'gridgmgmt'),
			'align'   => 'left',
			'sort'    => 'DESC'
		),
		'first_seen' => array(
			'display' => __('First Seen', 'gridgmgmt'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'last_seen'   => array(
			'display' => __('Last Seen', 'gridgmgmt'),
			'align'   => 'right',
			'sort'    => 'ASC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('hosts.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Hosts', 'gridgmgmt'), 'page', 'main');

	form_start('hosts.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($hosts)) {
		if (get_request_var('filter') != '') {
			foreach ($hosts as $host) {
				$id = base64_encode($host['clusterid'] . '|' . $host['host']);
				$status_url = get_cluster_status_url($host['clusterid']);

				form_alternate_row('line' . $id, false);
				form_selectable_cell(filter_value($host['host'], get_request_var('filter')), $id);
				form_selectable_cell(filter_value($host['clustername'], get_request_var('filter')), $id);
				form_selectable_cell($status_url, $id);
				form_selectable_cell(filter_value($host['hostType'], get_request_var('filter')), $id);
				form_selectable_cell(filter_value($host['hostModel'], get_request_var('filter')), $id);
				form_selectable_cell(($host['isServer'] == 1 ? 'Server' : (($host['licFeaturesNeeded'] == 16) ? __('Fixed Client', 'gridgmgmt') : __('Float Client', 'gridgmgmt'))), $id);
				form_selectable_cell(filter_value($host['first_seen'], get_request_var('filter')), $id, '', 'right');
				form_selectable_cell(filter_value($host['last_seen'], get_request_var('filter')), $id, '', 'right');
				form_checkbox_cell($host['host'], $id);
				form_end_row();
			}
		} else {
			foreach ($hosts as $host) {
				$id = base64_encode($host['clusterid'] . '|' . $host['host']);
				$status_url = get_cluster_status_url($host['clusterid']);

				form_alternate_row('line' . $id, false);
				form_selectable_cell($host['host'], $id);
				form_selectable_cell($host['clustername'], $id);
				form_selectable_cell($status_url, $id);
				form_selectable_cell(str_replace('UNKNOWN_AUTO_DETECT', __('Auto Detect', 'gridgmgmt'), $host['hostType']), $id);
				form_selectable_cell(str_replace('UNKNOWN_AUTO_DETECT', __('Auto Detect', 'gridgmgmt'), $host['hostModel']), $id);
				form_selectable_cell(($host['isServer'] == 1 ? 'Server' : (($host['licFeaturesNeeded'] == 16) ? __('Fixed Client', 'gridgmgmt') : __('Float Client', 'gridgmgmt'))), $id);
				form_selectable_cell($host['first_seen'], $id, '', 'right');
				form_selectable_cell($host['last_seen'], $id, '', 'right');
				form_checkbox_cell($host['host'], $id);
				form_end_row();
			}
		}
	} else {
		print "<tr><td colspan='8'><em>" . __('No Hosts Defined', 'gridgmgmt') . "</em></td></tr>";
	}

	html_end_box(false);

	if (cacti_sizeof($hosts)) {
		print $nav;
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($grid_mhosts_actions);

	form_end();
}
