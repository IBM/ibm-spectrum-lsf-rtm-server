<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$title = __('IBM Spectrum LSF RTM - Host Load Statistics', 'grid');

validate_lsload_request_vars();

switch (get_request_var('action')) {
	case 'ajax_rtm_hgroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_lsload.php', 'hgroup', $sql_where);
		break;
	default:
		grid_view_load();
	break;
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

function grid_view_get_load_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= 'WHERE grid_load.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* hostType sql where */
	if (get_request_var('type') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'hostType=?';
		$sql_params[] = get_request_var('type');
	}

	/* hgroup sql where */
	if (get_request_var('hgroup') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'groupName=?';
		$sql_params[] = get_request_var('hgroup');
	}

	/* hostModel sql where */
	if (get_request_var('model') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' hostModel=?';
		$sql_params[] = get_request_var('model');
	}

	/* status sql where */
	if (get_request_var('status') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' grid_load.status=?';
		$sql_params[] = get_request_var('status');
	}

	if (get_request_var('resource_str') != '') {
		if (get_request_var('clusterid') > 0) {
			$res_tool  = grid_get_res_tooldir(get_request_var('clusterid')) . '/gridhres';

			$cwd = getcwd();
			$resdir = grid_get_res_tooldir(get_request_var('clusterid'));

			if (is_dir($resdir)) {
				chdir($resdir);
			}

			if (is_executable($res_tool)) {
				get_filter_request_var('clusterid');

				$res_cmd   = $res_tool . ' -C ' . get_request_var('clusterid') . ' -R ' . cacti_escapeshellarg(get_request_var('resource_str'));
				$ret_val   = 0;
				$ret_out   = array();
				$res_hosts = exec($res_cmd, $ret_out, $ret_val);

				chdir($cwd);

				if (!$ret_val) {
					if (strlen($res_hosts)) {
						if (strlen($sql_where)) {
							$sql_where .= " AND grid_load.host IN ($res_hosts)";
						} else {
							$sql_where = "WHERE grid_load.host IN ($res_hosts)";
						}
					} else {
						if (strlen($sql_where)) {
							$sql_where .= ' AND grid_load.host IS NULL';
						} else {
							$sql_where = 'WHERE grid_load.host IN NULL';
						}
					}
				} else {
					if (strlen($sql_where)) {
						$sql_where .= ' AND grid_load.host IS NULL';
					} else {
						$sql_where = 'WHERE grid_load.host IN NULL';
					}

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
			load_current_session_value('resource_str', 'sess_grid_view_load_resource_str', '');
		}
	}

	/* filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
			"(grid_load.host LIKE ? OR
			grid_load.status LIKE ? OR
			grid_hostinfo.hostType LIKE ? OR
			grid_hostinfo.hostModel LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$columns = get_custom_elim_columns();
	$sql_col = '';
	if (cacti_sizeof($columns)) {
		foreach($columns as $c) {
			$sql_col .= ", MAX(CASE WHEN ghr.resource_name='$c' THEN totalValue ELSE NULL END) AS \"$c\"";
		}
	}

	if (get_request_var('hgroup') == -1) {
		$sql_query = "SELECT *
			FROM (
				SELECT DISTINCT grid_clusters.clusterid, grid_clusters.clustername, grid_load.host, grid_hostinfo.hostType,
				grid_hostinfo.hostModel, grid_load.time_in_state, grid_load.status, grid_load.r15s, grid_load.r1m,
				grid_load.r15m, grid_load.ut, grid_load.pg, grid_load.io, grid_load.ls, grid_load.it,
				grid_load.tmp, grid_load.swp, grid_load.mem $sql_col
				FROM grid_clusters
				INNER JOIN grid_hosts
				ON grid_clusters.clusterid=grid_hosts.clusterid
				INNER JOIN grid_hostinfo
				ON grid_clusters.clusterid = grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				INNER JOIN grid_load
				ON grid_clusters.clusterid = grid_load.clusterid
				AND grid_hosts.host = grid_load.host
				INNER JOIN grid_hosts_resources AS ghr
				ON grid_clusters.clusterid = ghr.clusterid
				AND grid_hosts.host=ghr.host
				$sql_where
				GROUP BY grid_hosts.clusterid, grid_hosts.host
			) AS rs
			$sql_order";
	} else {
		$sql_query = "SELECT *
			FROM (
				SELECT DISTINCT grid_clusters.clusterid, grid_clusters.clustername, grid_load.host, grid_hostgroups.groupName,
				grid_hostinfo.hostType, grid_hostinfo.hostModel, grid_load.time_in_state, grid_load.status, grid_load.r15s,
				grid_load.r1m, grid_load.r15m, grid_load.ut, grid_load.pg, grid_load.io, grid_load.ls, grid_load.it,
				grid_load.tmp, grid_load.swp, grid_load.mem $sql_col
				FROM grid_clusters
				INNER JOIN grid_hosts
				ON grid_clusters.clusterid=grid_hosts.clusterid
				INNER JOIN grid_hostinfo
				ON grid_clusters.clusterid = grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				INNER JOIN grid_load
				ON grid_clusters.clusterid = grid_load.clusterid
				AND grid_hosts.host = grid_load.host
				LEFT JOIN grid_hostgroups
				ON grid_clusters.clusterid=grid_hostgroups.clusterid
				AND grid_hosts.host=grid_hostgroups.host
				INNER JOIN grid_hosts_resources AS ghr
				ON grid_clusters.clusterid = ghr.clusterid AND grid_hosts.host=ghr.host
				$sql_where
				GROUP BY grid_hosts.clusterid, grid_hosts.host
			) AS rs
			$sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function hostLoadFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_lsload.php'>
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
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_lsload.php', 'Group', 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td>
						<select id='status'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT status
									FROM grid_load
									ORDER BY status');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
									FROM grid_load
									WHERE clusterid = ?
									ORDER BY status',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['status'] .'"'; if (get_request_var('status') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'grid');?>
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
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
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

							if (cacti_sizeof($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] .'"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
								}
							}
							?>
						</select>
					</td>
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
									AND clusterid = ? ORDER BY hostModel",
									array(get_request_var('clusterid')));
							}
							if (cacti_sizeof($models) > 0) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
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
							foreach($grid_refresh_interval as $key => $value) {
								if ($key >= $max_refresh) {
									print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
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
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}


function validate_lsload_request_vars() {
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
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'hgroup' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'resource_str' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'resource_sanitize_search_string'),
			'pageset' => true
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ut',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_glsl');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function grid_view_load() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

	grid_set_minimum_page_refresh();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$load_results = grid_view_get_load_records($sql_where, true, $rows, $sql_params);

	general_header();

	html_start_box(__('Host Load Filters', 'grid'), '100%', '', '3', 'center', '');
	hostLoadFilter();
	html_end_box();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL = 'grid_lsload.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
		strURL += '&model=' + $('#model').val();
		strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_lsload.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #status, #clusterid, #type, #hgroup, #model, #refresh, #filter, #resource_str').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	if (get_request_var('hgroup') == "-1") {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM (grid_clusters
			INNER JOIN grid_hostinfo
			ON grid_clusters.clusterid=grid_hostinfo.clusterid)
			INNER JOIN grid_load
			ON (grid_hostinfo.host=grid_load.host)
			AND (grid_clusters.clusterid=grid_load.clusterid)
			$sql_where";
	} else {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM (grid_clusters
			INNER JOIN grid_hostinfo
			ON grid_clusters.clusterid=grid_hostinfo.clusterid)
			INNER JOIN grid_load
			ON (grid_hostinfo.host=grid_load.host)
			AND (grid_clusters.clusterid=grid_load.clusterid)
			LEFT JOIN grid_hostgroups
			ON (grid_load.host=grid_hostgroups.host)
			AND (grid_clusters.clusterid=grid_hostgroups.clusterid)
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'grid')
		),
		'host' => array(
			'display' => __('Host Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'dbname'  => 'host_cluster',
			'sort'    => 'ASC'
		),
		'hostType' => array(
			'display' => __('Type', 'grid'),
			'dbname'  => 'host_type',
			'sort'    => 'ASC'
		),
		'hostModel' => array(
			'display' => __('Model', 'grid'),
			'dbname'  => 'host_model',
			'sort'    => 'ASC'
		),
		'status' => array(
			'display' => __('Status', 'grid'),
			'dbname'  => 'host_status',
			'sort'    => 'ASC'
		),
		'time_in_state' => array(
			'display' => __('TIS', 'grid'),
			'dbname'  => 'host_time_in_state',
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'r15s' => array(
			'display' => __('RunQ 15sec', 'grid'),
			'dbname'  => 'host_r15sec',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'r1m' => array(
			'display' => __('RunQ 1min', 'grid'),
			'dbname'  => 'host_r1m',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'r15m' => array(
			'display' => __('RunQ 15m', 'grid'),
			'dbname'  => 'host_r15m',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ut' => array(
			'display' => __('CPU %%%', 'grid'),
			'dbname'  => 'host_ut',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pg' => array(
			'display' => __('Page Rate', 'grid'),
			'dbname'  => 'host_pagerate',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'io' => array(
			'display' => __('I/O Rate', 'grid'),
			'dbname'  => 'host_iorate',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ls' => array(
			'display' => __('Cur Logins', 'grid'),
			'dbname'  => 'host_logins',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'it' => array(
			'display' => __('Idle Time', 'grid'),
			'dbname'  => 'host_idle_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'tmp' => array(
			'display' => __('Temp Avail', 'grid'),
			'dbname'  => 'host_temp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'swp' => array(
			'display' => __('Swap Avail', 'grid'),
			'dbname'  => 'host_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'mem' => array(
			'display' => __('Mem Avail', 'grid'),
			'dbname'  => 'host_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	$columns = get_custom_elim_columns();
	if (cacti_sizeof($columns)) {
		foreach($columns as $c) {
			$display_text += array(
				$c => array(
					'display' => ucfirst($c),
					'dbname'  => 'host_elim_' . strtolower($c),
					'align'   => 'right',
					'sort'    => 'ASC'
				)
			);
		}
	}

	$display_text = form_process_visible_display_text($display_text);

	/* generate page list */
	$nav = html_nav_bar('grid_lsload.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Hosts', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($load_results)) {
		foreach ($load_results as $load) {
			$host_id = db_fetch_cell_prepared('SELECT id
				FROM host
				WHERE clusterid = ?
				AND hostname = ?',
				array($load['clusterid'], $load['host']));

			if (!empty($host_id)) {
				$host_graphs = db_fetch_cell_prepared('SELECT count(*)
					FROM graph_local
					WHERE host_id = ?',
					array($host_id));
			} else {
				$host_graphs = 0;
			}

			form_alternate_row();

			?>
			<td class='nowrap' style='width:1%'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $load['clusterid'] .'&ajax_host_query=' .$load['host'] . '&exec_host=' . $load['host'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bzen.php?action=zoom&reset=1&clusterid=' . $load['clusterid'] . '&exec_host=' . $load['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hjobdetail.gif' alt='' title='<?php print __esc('View Host Job Detail', 'grid');?>'></a>
				<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details WHERE hostname=?", array($load['host']))) {?>
				<a href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&host=' . $load['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts', 'grid');?>'></a>
				<?php } if ($host_graphs > 0) {?><a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&graph_template_id=-1&rfilter=&host_id=' . $host_id);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Graphs', 'grid');?>'></a><?php }?>
				<?php api_plugin_hook_function('grid_load_action_insert', $load); ?>
			</td>
			<?php

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $load['clusterid'] .
				'&exec_host=' . $load['host'] .
				'&status=RUNNING');

			form_selectable_cell_metadata('simple', 'host', $load['clusterid'], $load['host'], '', '', html_escape($load['host']), true, $url);
			form_selectable_cell_visible($load['clustername'], 'host_cluster', $i);

			form_selectable_cell_visible(filter_value($load['hostType'], get_request_var('filter')), 'host_type', $i);
			form_selectable_cell_visible(filter_value($load['hostModel'], get_request_var('filter')), 'host_model', $i);
			form_selectable_cell_visible(filter_value($load['status'], get_request_var('filter')), 'host_status', $i);
			form_selectable_cell_visible(display_time_in_state($load['time_in_state']), 'host_time_in_state', $i, 'right');
			form_selectable_cell_visible(display_load($load['r15s']), 'host_r15sec', $i, 'right');
			form_selectable_cell_visible(display_load($load['r1m']), 'host_r1m', $i, 'right');
			form_selectable_cell_visible(display_load($load['r15m']), 'host_r15m', $i, 'right');
			form_selectable_cell_visible(display_ut($load['ut']), 'host_ut', $i, 'right');
			form_selectable_cell_visible(display_pg($load['pg'],0), 'host_pagerate', $i, 'right');
			form_selectable_cell_visible(display_pg($load['io'],0), 'host_iorate', $i, 'right');
			form_selectable_cell_visible(display_ls($load['ls']), 'host_logins', $i, 'right');
			form_selectable_cell_visible(display_hours($load['it']), 'host_idle_time', $i, 'right');
			form_selectable_cell_visible(display_memory($load['tmp']), 'host_temp', $i, 'right');
			form_selectable_cell_visible(display_memory($load['swp']), 'host_swap', $i, 'right');
			form_selectable_cell_visible(display_memory($load['mem']), 'host_mem', $i, 'right');

			$columns = get_custom_elim_columns();
			$sql_col = '';
			if (cacti_sizeof($columns)) {
				foreach($columns as $c) {
					form_selectable_cell($load[$c] == '' ? '-':(is_numeric($load[$c]) ? number_format($load[$c]):$load[$c]), $i, '', 'right');
				}
			}

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Host Load Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($load_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

