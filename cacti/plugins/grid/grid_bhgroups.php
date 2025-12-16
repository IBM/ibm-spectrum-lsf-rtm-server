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

$title = __('IBM Spectrum LSF RTM - Host Group Batch Statistics', 'grid');

grid_view_bhg();

function grid_view_get_bhg_records(&$sql_where_in, &$sql_groupby, $apply_limits = true, $rows = 30) {
        global $config, $grid_out_of_services;

	$DISTINCT = '';

	$sql_where_in = '';
	$sql_where_out = '';

	$sql_groupby = 'GROUP BY gl.clusterid, ghg.groupName';

        /* user id sql where */
        if (get_request_var('clusterid') != '0') {
                $sql_where_in .= ($sql_where_in == '' ? 'WHERE ':' AND ') . 'gh.clusterid=' . get_request_var('clusterid');
        }

        if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
                $sql_where_out .= ($sql_where_out == '' ? 'WHERE ':' AND ') . 'ghs.numJOBS>0';
        }

        /* filter sql where */
        if (strlen(get_request_var('filter'))) {
                $sql_where_in .= ($sql_where_in == '' ? 'WHERE ':' AND ') . 'ghg.groupName LIKE "%' . get_request_var('filter') . '%"';
        }

	if (read_config_option('grid_cpu_leveling') == 'on') {
		$sql_field = '(SUM(ghi.cpuFactor*maxCpus*ut) / SUM(ghi.cpuFactor*maxCpus)) AS avg_ut,';
	} else {
		$sql_field = '(SUM(maxCpus*ut) / SUM(maxCpus)) AS avg_ut,';
	}

        /* status sql where */
        if (get_request_var('status') == '-1') {
                /* Show all items */
                $sql_where_in .= ($sql_where_in == '' ? 'WHERE ':' AND ') . '(gl.ut>=0 AND gl.r1m>=0)';
                $sql_status  = "'N/A' AS status";
        } else if (get_request_var('status') == '-2') {
                $out_of_services = '(';

				foreach($grid_out_of_services as $grid_out_of_service) {
					$out_of_services .= '"'.$grid_out_of_service.'",';
				}

				$out_of_services = substr($out_of_services, 0, -1);
				$out_of_services .= ')';

                $DISTINCT = 'DISTINCT';
                $sql_status  = "'N/A' AS status";
                $sql_where_in .= ($sql_where_in == '' ? 'WHERE ':' AND ') .
                        '((gh.status IN ' . $out_of_services . ') OR (gl.status IN ' . $out_of_services . '))';
        } else {
                $stats = explode(':',get_request_var('status'));
                $lstat = $stats[0];
                $bstat = $stats[1];
                $sql_status = "CONCAT_WS('',gl.status,':',gh.status) AS status";
                $sql_where_in  .= ($sql_where_in == '' ? 'WHERE ':' AND ') .
                        '(gh.status=' . db_qstr($bstat) . ') AND (gl.status=' . db_qstr($lstat) . ')';
        }

		$sql_order = get_order_string();

        $sql_query = "SELECT $DISTINCT gc.clusterid, gc.clustername, ghgsub.groupName,
                ghgsub.status, ghs.efficiency, ghs.avg_mem, ghs.max_mem,
                ghs.avg_swap, ghs.max_swap, ghs.total_cpu,
                ghgsub.sum_maxJobs,
                CASE WHEN ghs.numJOBS IS NOT NULL THEN ghs.numJOBS ELSE 0 END AS sum_numJobs,
                CASE WHEN ghs.numRUN IS NOT NULL THEN ghs.numRUN ELSE 0 END AS sum_numRun,
                ghgsub.sum_numSSUSP, ghgsub.sum_numUSUSP, ghgsub.sum_numRESERVE, ghgsub.num_hosts,
                ghgsub.avg_ut, ghgsub.avg_r1m
                FROM grid_clusters AS gc
                INNER JOIN (
                        SELECT gl.clusterid, ghg.groupName, $sql_status,
                        SUM(CASE WHEN gh.maxJobs <> -1 THEN gh.maxJobs ELSE 0 END) AS sum_maxJobs,
                        SUM(gh.numSSUSP) AS sum_numSSUSP, SUM(gh.numUSUSP) AS sum_numUSUSP,
                        SUM(gh.numRESERVE) AS sum_numRESERVE, Count(gl.host) AS num_hosts,
                        $sql_field AVG(gl.r1m) AS avg_r1m
                        FROM grid_load AS gl
                        INNER JOIN grid_hosts AS gh ON gl.clusterid=gh.clusterid AND gl.host=gh.host
                        INNER JOIN grid_hostinfo AS ghi ON ghi.clusterid=gh.clusterid AND ghi.host=gh.host
                        INNER JOIN grid_hostgroups AS ghg ON ghi.clusterid=ghg.clusterid AND ghi.host=ghg.host
                        $sql_where_in
                        $sql_groupby
                ) AS ghgsub
                ON ghgsub.clusterid=gc.clusterid
                LEFT JOIN grid_hostgroups_stats AS ghs
                ON ghs.groupName=ghgsub.groupName
                AND ghs.clusterid=ghgsub.clusterid
                $sql_where_out
                $sql_order ";

        //cacti_log(str_replace("\n", "", str_replace("\t", " ", $sql_query)));

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	$groups = db_fetch_assoc($sql_query);

	return $groups;
}

function hostGroupFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_bhgroups.php'>
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
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
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
									WHERE grid_hosts.clusterid= ?
									ORDER BY lstatus;',
									array(get_request_var('filter')));
							}

							if (!empty($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['lstatus'] . ':' . $status['bstatus'] .'"'; if (get_request_var('status') == $status['lstatus'] . ':' . $status['bstatus']) { print ' selected'; } print '>' . ucfirst($status['lstatus']) . ':' . ucfirst($status['bstatus']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Refresh', 'grid');?>
					</td>
					<td>
						<select id='refresh'>
						<?php
						$max_refresh = read_config_option('grid_minimum_refresh_interval');

						if (cacti_sizeof($grid_refresh_interval)) {
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
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<input type='checkbox' id='unused'<?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Host Groups', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_bhg() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;

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
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'unused' => array(
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
			'default' => 'avg_ut',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'has_graphs' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => read_config_option('default_has') == 'on' ? 'true':'false'
			)
	);

	validate_store_request_vars($filters, 'sess_gbhg');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	grid_set_minimum_page_refresh();

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bhgroups.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_bhgroups.php?clear=true&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#status, #unused, #clusterid, #rows, #refresh, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	html_start_box(__('Batch Host Group Filters', 'grid'), '100%', '', '3', 'center', '');
	hostGroupFilter();
	html_end_box();

	$sql_where = '';
	$sql_groupby = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$bhg_results = grid_view_get_bhg_records($sql_where, $sql_groupby, true, $rows);

	$bhg_queue_limits = array_rekey(db_fetch_assoc('SELECT sum(hostJobLimit) AS slots, host
		FROM grid_queues
		INNER JOIN grid_queues_hosts
		ON queuename=queue
		AND grid_queues.clusterid=grid_queues_hosts.clusterid
		GROUP BY host'), 'host', 'slots');

	$total_rows_sql = "SELECT count(*)
		FROM grid_clusters as gc
		INNER JOIN grid_hostgroups as ghg
		ON gc.clusterid=ghg.clusterid
		INNER JOIN grid_load as gl
		ON gl.clusterid=ghg.clusterid
		AND gl.host=ghg.host
		INNER JOIN grid_hosts as gh
		ON gl.clusterid=gh.clusterid
		AND gl.host=gh.host
		INNER JOIN grid_hostinfo as ghi
		ON ghi.clusterid=gh.clusterid
		AND ghi.host=gh.host
		LEFT JOIN grid_hostgroups_stats as ghs
		ON ghs.groupName=ghg.groupName
		AND ghs.clusterid=ghg.clusterid
		$sql_where
		$sql_groupby";

	//print $total_rows_sql;

	$total_rows = cacti_sizeof(db_fetch_assoc($total_rows_sql));

	if (get_request_var('status') != -1) {
		$numhosts_col = 'num_hosts';
	} else {
		$numhosts_col = 'nosort2';
	}

	$display_text = array(
		'nosort0'     => array(
			'display' => __('Actions', 'grid')
		),
		'groupName'   => array(
			'display' => __('Host Group', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'sort'    => 'ASC'
		),
		'nosort1'     => array(
			'display' => __('Load/Batch', 'grid'),
		),
		$numhosts_col => array(
			'display' => __('Num Hosts', 'grid'),
			'dbname'  => 'hgroup_hosts',
			'tip'     => __('Total Hosts/Ok Hosts/Closed Hosts/Un* Hosts', 'grid'),
			'align'   => 'right',
		),
		'nosort3' => array(
			'display' => __('Out of Service(%%%)', 'grid'),
			'align'   => 'right',
			'dbname'  => 'hgroup_oos'
		),
		'avg_ut'      => array(
			'display' => __('Avg CPU %%%', 'grid'),
			'dbname'  => 'hgroup_avgut',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_r1m'     => array(
			'display' => __('Avg r1m', 'grid'),
			'dbname'  => 'hgroup_avgr1m',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'efficiency'  => array(
			'display' => __('Avg Effic', 'grid'),
			'align'   => 'right',
			'dbname'  => 'hgroup_effic',
		),
		'total_cpu'   => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  => 'hgroup_totalcpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem'     => array(
			'display' => __('Max Memory', 'grid'),
			'dbname'  => 'hgroup_maxmem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap'    => array(
			'display' => __('Max Swap', 'grid'),
			'dbname'  => 'hgroup_maxswp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_maxJobs' => array(
			'display' => __('Max %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_sumjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_numJobs' => array(
			'display' => __('Num %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_jobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_numRun'  => array(
			'display' => __('Run %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_running',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_numSSUSP' => array(
			'display' => __('SSUSP %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_ssusp_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_numUSUSP' => array(
			'display' => __('USUSP %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_ususp_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sum_numRESERVE' => array(
			'display' => __('Reserve %s', format_job_slots(), 'grid'),
			'dbname'  => 'hgroup_reserve_slots',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	/* hide columns that are not displayed */
	$display_text = form_process_visible_display_text($display_text);

	/* generate page list */
	$nav = html_nav_bar('grid_bhgroups.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Hosts', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;
	if (!empty($bhg_results)) {
		foreach ($bhg_results as $bhg) {
			$groupName = $bhg['groupName'];

			if ((get_request_var('status') != -1) && (get_request_var('status') != -2)) {
				$stat = explode(':',$bhg['status']);
				$lstat = ucfirst($stat[0]);
				$bstat = ucfirst($stat[1]);
			}

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($bhg['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
					AND host_snmp_cache.host_id=graph_local.host_id
					WHERE graph_local.host_id = ?
					AND graph_local.snmp_index = ?
					AND host_snmp_cache.field_name='groupName'",
					array($cacti_host, $bhg['groupName']));

                if (!empty($local_graph_ids)) {
	                $graph_select = 'reset=1&page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

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

			$groupName = urlencode($groupName);

                        $action_url = "<a class='pic' href='". html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php' .
                                '?reset=1' .
                                '&clusterid=' . $bhg['clusterid'] .
                                '&hgroup=' . $groupName) . "'>" .
                                "<img src='" . $config['url_path'] . "plugins/grid/images/view_hosts.gif' alt='' " .
                                "title='" . __esc('View Batch Hosts', 'grid') . "'></a>";

                        $action_url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
                                '?action=viewlist' .
                                '&reset=1' .
                                '&clusterid=' . $bhg['clusterid'] .
                                '&hgroup=' . $groupName .
                                '&status=ACTIVE&page=1') . "'>" .
                                "<img src='" . $config['url_path'] . "plugins/grid/images/view_jobs.gif' alt='' " .
                                "title='" . __esc('View Active Jobs', 'grid') . "'></a>";

			if (isset($graph_select)) {
				$action_url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' .
					$graph_select) . "'>" .
					"<img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' " .
					"title='" . __esc('View Host Group Graphs', 'grid') . "'></a>";
			}

			form_selectable_cell($action_url, $i, '20');

                        $url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
                                '?action=viewlist' .
                                '&reset=1' .
                                '&clusterid=' . $bhg['clusterid'] .
                                '&hgroup=' . $groupName .
                                '&status=RUNNING&page=1');

			form_selectable_cell_metadata('simple', 'host-group', $bhg['clusterid'], $bhg['groupName'], '', '', '', true, $url);

			form_selectable_cell($bhg['clustername'], $i);

			if (get_request_var('status') != -1 && get_request_var('status') != -2) {
				form_selectable_cell($lstat . ':' . $bstat, $i);
			} else {
				form_selectable_cell(__('N/A', 'grid'), $i);
			}

			if (get_request_var('status') == -1) {
				form_selectable_cell_visible(display_total_hosts($bhg['groupName'], $bhg['clusterid']), 'hgroup_hosts', $i, 'right');
			} else {
				form_selectable_cell_visible(number_format_i18n($bhg['num_hosts']), 'hgroup_hosts', $i, 'right');
			}

                        $display = filter_value(display_out_of_service($bhg['groupName'], $bhg['clusterid']), '', html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php?reset=1&clusterid=' . $bhg['clusterid'] . '&status=-2&hgroup=' . $groupName . '&page=1'));

			form_selectable_cell_visible($display, 'hgroup_oos', $i, 'right');

			form_selectable_cell_visible(display_ut($bhg['avg_ut']), 'hgroup_avgut', $i, 'right');
			form_selectable_cell_visible(display_load($bhg['avg_r1m']), 'hgroup_avgr1m', $i, 'right');
			form_selectable_cell_visible(display_job_effic($bhg['efficiency'], 9999, 2), 'hgroup_effic', $i, 'right');
			form_selectable_cell_visible(display_job_time($bhg['total_cpu']), 'hgroup_totalcpu', $i, 'right');
			form_selectable_cell_visible(display_job_memory($bhg['max_mem']), 'hgroup_maxmem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($bhg['max_swap']), 'hgroup_maxswp', $i, 'right');

			if ($bhg['sum_maxJobs'] == 0 || $bhg['sum_maxJobs'] == '-') {
				if (array_key_exists($bhg['groupName'], $bhg_queue_limits)) {
					form_selectable_cell_visible($bhg_queue_limits[$bhg['groupName']], 'hgroup_sumjobs', $i, 'right');
				} else {
					form_selectable_cell_visible('-', 'hgroup_sumjobs', $i, 'right');
				}
			} else {
				form_selectable_cell_visible(number_format_i18n($bhg['sum_maxJobs']), 'hgroup_sumjobs', $i, 'right');
			}

			form_selectable_cell_visible(number_format_i18n($bhg['sum_numJobs']), 'hgroup_jobs', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($bhg['sum_numRun']), 'hgroup_running', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($bhg['sum_numSSUSP']), 'hgroup_ssusp_slots', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($bhg['sum_numUSUSP']), 'hgroup_ususp_slots', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($bhg['sum_numRESERVE']), 'hgroup_reserve_slots', $i, 'right');

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Host Group Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($bhg_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function display_out_of_service($groupName, $clusterid) {
	global $grid_out_of_services;

	$out_of_services = '(';
	foreach($grid_out_of_services as $grid_out_of_service) {
		$out_of_services .= '"' . $grid_out_of_service . '",';
	}
	$out_of_services = substr($out_of_services, 0, -1);
	$out_of_services .= ')';

	$query = 'SELECT COUNT(*)
		FROM grid_hostgroups
		WHERE host IN (
			SELECT DISTINCT grid_load.host
			FROM grid_load
			INNER JOIN grid_hosts
			ON (grid_load.host=grid_hosts.host
			AND grid_load.clusterid=grid_hosts.clusterid
			AND grid_load.clusterid=' . $clusterid .')
			WHERE (grid_hosts.status IN ' . $out_of_services . ' OR grid_load.status IN ' . $out_of_services . ')
		)
		AND groupName=' . db_qstr($groupName);

	$value = db_fetch_cell($query);

	return round(($value/display_total_hosts($groupName, $clusterid, true))*100, 2) . '%';
}

function display_total_hosts($groupName, $clusterid, $total = false) {
	$select_sql_by_status="SUM(CASE WHEN grid_hosts.status = 'OK' THEN 1 ELSE 0 END) AS num_okhost,
		SUM(CASE WHEN grid_hosts.status LIKE 'CLOSE%' THEN 1 ELSE 0 END) AS num_clshost,
		SUM(CASE WHEN grid_hosts.status LIKE 'UN%' THEN 1 ELSE 0 END) AS num_unhost";

	$from_sql= 'FROM grid_hostgroups JOIN grid_hosts ON grid_hostgroups.host=grid_hosts.host AND grid_hostgroups.clusterid=grid_hosts.clusterid';

	$where_sql="WHERE grid_hostgroups.clusterid={$clusterid}";

	if ($groupName == 'all')
		$query = "SELECT COUNT(DISTINCT(host))-1 AS num_host, $select_sql_by_status $from_sql $where_sql";
	else
		$query = "SELECT COUNT(*) AS num_host, $select_sql_by_status $from_sql $where_sql AND groupName='".$groupName."'";

	$query_result = db_fetch_row($query);

	if ($total)
		return $query_result['num_host'];
	else
		return number_format_i18n($query_result['num_host']) . ' / ' .
			number_format_i18n($query_result['num_okhost'])  . ' / ' .
			number_format_i18n($query_result['num_clshost']) . ' / ' .
			number_format_i18n($query_result['num_unhost']);
}

