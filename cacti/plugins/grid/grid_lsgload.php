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
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

$title = __('IBM Spectrum LSF RTM - Host Group Load Statistics', 'grid');

validate_lsgload_request_vars();

switch (get_request_var('action')) {
	case 'ajax_rtm_hgroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_lsgload.php', 'hgroup', $sql_where);
		break;
	default:
		grid_view_load();
	break;
}

function validate_lsgload_request_vars() {
    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option("grid_records")
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
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'groupName',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_ggl');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
    /* ================= input validation and session storage ================= */
}

function grid_view_get_gload_records(&$sql_where, $apply_limits = true, $rows, &$sql_params) {
	global $config;

	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= 'WHERE (gl.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	/* hostType sql where */
	if (get_request_var('hgroup') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' groupName=?';
		$sql_params[] = get_request_var('hgroup');
	}

	/* status sql where */
	if (get_request_var('status') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' status=?';
		$sql_params[] = get_request_var('status');
	}

	/* don't report gload information for unavail/unknown hosts */
	$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' status NOT LIKE "U%"';

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (gl.status LIKE ? OR
			ghg.groupName LIKE ?)';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (read_config_option('grid_cpu_leveling') == 'on') {
		$query = '(SUM(ghi.cpuFactor*maxCpus*ut) / SUM(ghi.cpuFactor*maxCpus)) AS avg_ut,';
	} else {
		$query = '(SUM(maxCpus*ut) / SUM(maxCpus)) AS avg_ut,';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT gc.clusterid, gc.clustername, COUNT(gl.host) AS total_hosts,
		ghg.groupName, gl.status, AVG(gl.r15s) AS avg_r15s, AVG(gl.r1m) AS avg_r1m,
		AVG(gl.r15m) AS avg_r15m, $query AVG(gl.pg) AS avg_pg, AVG(gl.io) AS avg_io,
		Sum(gl.ls) AS sum_ls, MIN(gl.it) AS min_it, AVG(gl.it) AS avg_it, MAX(gl.it) AS max_it,
		MIN(gl.tmp) AS min_tmp, AVG(gl.tmp) AS avg_tmp, MAX(gl.tmp) AS max_tmp, MIN(gl.swp) AS min_swp,
		AVG(gl.swp) AS avg_swp, MAX(gl.swp) AS max_swp, MIN(gl.mem) AS min_mem, AVG(gl.mem) AS avg_mem,
		MAX(gl.mem) AS max_mem
		FROM grid_clusters AS gc
		INNER JOIN grid_load AS gl
		ON gc.clusterid=gl.clusterid
		INNER JOIN grid_hostinfo AS ghi
		ON gl.host=ghi.host
		AND gl.clusterid=ghi.clusterid
		INNER JOIN grid_hostgroups AS ghg
		ON ghi.clusterid=ghg.clusterid
		AND ghi.host=ghg.host
		$sql_where
		GROUP by ghg.groupName, ghg.clusterid
		$sql_order";

	//print $sql_query;

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function groupLoadFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_lsgload.php'>
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
							if (cacti_sizeof($clusters) > 0) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_lsgload.php', 'Group', 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
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
									if (strtoupper(substr($status['status'],0,1)) != 'U') {
										print '<option value="' . $status['status'] .'"'; if (get_request_var('status') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __('Go', 'grid');?>' title='<?php print __('Search', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear', 'grid');?>' title='<?php print __('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Groups', 'grid');?>
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
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_load() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

	grid_set_minimum_page_refresh();

	general_header();

	html_start_box('Host Group Load Filters', '100%', '', '3', 'center', '');
	groupLoadFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$gload_results = grid_view_get_gload_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL = 'grid_lsgload.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();

		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_lsgload.php?clear=true&header=false';

		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #hgroup, #status, #refresh, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	$rows_query_string = "SELECT
		COUNT(*)
		FROM (grid_clusters AS gc
		INNER JOIN grid_hostgroups AS ghg
		ON gc.clusterid=ghg.clusterid)
		INNER JOIN grid_load AS gl
		ON (ghg.host=gl.host)
		AND (gc.clusterid=gl.clusterid)
		$sql_where
		GROUP by ghg.groupName, ghg.clusterid";

	//print $rows_query_string;

	$total_rows = cacti_sizeof(db_fetch_assoc_prepared($rows_query_string, $sql_params));

	$display_text = array(
		'nosort'      => array(
			'display' => __('Actions', 'grid')
		),
		'groupName'   => array(
			'display' => __('Group Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'dbname'  => 'hgroup_cluster',
			'sort'    => 'ASC'
		),
		'status'      => array(
			'display' => __('Status', 'grid'),
			'tip'     => __('The Status of Hosts within the Host Group.', 'grid'),
			'sort'    => 'ASC'
		),
		'avg_r15s'    => array(
			'display' => __('Avg r15sec', 'grid'),
			'dbname'  => 'hgroup_avgr15sec',
			'tip'     => __('Average 15 Second Load Average for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_r1m'     => array(
			'display' => __('Avg r1min', 'grid'),
			'dbname'  => 'hgroup_avgr1m',
			'tip'     => __('Average 1 Minute Load Average for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_r15m'    => array(
			'display' => __('Avg r15m', 'grid'),
			'dbname'  => 'hgroup_avgr15m',
			'tip'     => __('Average 15 Minute Load Average for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_ut'      => array(
			'display' => __('Avg CPU%%%', 'grid'),
			'dbname'  => 'hgroup_avgut',
			'tip'     => __('Average CPU Utilization Rate for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_pg'      => array(
			'display' => __('Avg Pages', 'grid'),
			'dbname'  => 'hgroup_pagerate',
			'tip'     => __('Average Paging Rate for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_io'      => array(
			'display' => __('Avg I/O', 'grid'),
			'dbname'  => 'hgroup_iorate',
			'tip'     => __('Average local I/O rate for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_ls'      => array(
			'display' => __('Logins', 'grid'),
			'dbname'  => 'hgroup_logins',
			'tip'     => __('Total interactive Logins for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_it'      => array(
			'display' => __('Avg Idle', 'grid'),
			'dbname'  => 'hgroup_idle_time',
			'tip'     => __('Average Idle Time for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_tmp'     => array(
			'display' => __('Avg Temp', 'grid'),
			'dbname'  => 'hgroup_avgtemp',
			'tip'     => __('Average Temp Available for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swp'     => array(
			'display' => __('Avg Swap', 'grid'),
			'dbname'  => 'hgroup_avgswp',
			'tip'     => __('Average Swap Available for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem'     => array(
			'display' => __('Avg Mem Avail', 'grid'),
			'dbname'  => 'hgroup_avgmem',
			'tip'     => __('Average Memory Available for Hosts within the Host Group', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	$display_text = form_process_visible_display_text($display_text);
	/* generate page list */
	$nav = html_nav_bar('grid_lsgload.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Host Groups', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($gload_results)) {
		foreach ($gload_results as $load) {
			$groupName = $load['groupName'];
			if (get_request_var('status') == -1) {
				$load['status'] = __('All', 'grid');
			}

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($load['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared('SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
					AND host_snmp_cache.host_id=graph_local.host_id
					WHERE graph_local.host_id = ?
					AND graph_local.snmp_index = ?
					AND host_snmp_cache.field_name="groupName"',
					array($cacti_host, $load['groupName']));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				} else {
					unset($graph_select);
				}
			} else {
				unset($graph_select);
			}

			form_alternate_row();

			?>
			<td class='nowrap' style='width:1%;'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_lsload.php?reset=1&clusterid=' . $load['clusterid'] . '&ajax_hgroup_query=' . $groupName . '&hgroup=' . $groupName);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hosts.gif' alt='' title='<?php print __esc('View Batch Hosts', 'grid');?>'></a>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?reset=1&action=viewlist&reset=1&clusterid=' . $load['clusterid'] . '&ajax_hgroup_query=' . $groupName . '&hgroup=' . $groupName . '&status=RUNNING&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
				<?php if (isset($graph_select)) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Group Graphs', 'grid');?>'></a>
				<?php }?>
			</td>
			<?php

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_lsload.php' .
				'?reset=1&clusterid=' . $load['clusterid'] .
				'&page=1&hgroup=' . $groupName .
				'&ajax_hgroup_query=' . $groupName);

			form_selectable_cell_metadata('simple', 'host-group', $load['clusterid'], $load['groupName'], '', '', html_escape($load['groupName']), true, $url);
			form_selectable_cell_visible($load['clustername'], 'hgroup_cluster');

			form_selectable_cell_visible(filter_value($load['status'], get_request_var('filter')), '', $i, '', '');
			form_selectable_cell_visible(display_load($load['avg_r15s']), 'hgroup_avgr15sec', $i, 'right');
			form_selectable_cell_visible(display_load($load['avg_r1m']), 'hgroup_avgr1m', $i, 'right');
			form_selectable_cell_visible(display_load($load['avg_r15m']), 'hgroup_avgr15m', $i, 'right');
			form_selectable_cell_visible(display_ut($load['avg_ut']), 'hgroup_avgut', $i, 'right');
			form_selectable_cell_visible(display_pg($load['avg_pg']), 'hgroup_pagerate', $i, 'right');
			form_selectable_cell_visible(display_load($load['avg_io']), 'hgroup_iorate', $i, 'right');
			form_selectable_cell_visible(display_ls($load['sum_ls']), 'hgroup_logins', $i, 'right');
			form_selectable_cell_visible(display_hours($load['avg_it']), 'hgroup_idle_time', $i, 'right');
			form_selectable_cell_visible(display_memory($load['avg_tmp']), 'hgroup_avgtemp', $i, 'right');
			form_selectable_cell_visible(display_memory($load['avg_swp']), 'hgroup_avgswp', $i, 'right');
			form_selectable_cell_visible(display_memory($load['avg_mem']), 'hgroup_avgmem', $i, 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Group Load Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($gload_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}
