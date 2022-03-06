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
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');

$title = __('IBM Spectrum LSF RTM - Batch User Statistics', 'grid');

validate_busers_request_vars();

switch (get_request_var('action')) {
	case 'ajax_rtm_usergroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_busers.php', 'usergroup', $sql_where);
		break;
	default:
		grid_view_users();
		break;
}

function grid_view_get_users_records(&$sql_where, &$sql_join, $apply_limits = true, $rows, &$sql_params) {
	$sql_order = get_order_string();

	/* here is the default 'non-drilldown query' */
	if (get_request_var('queue') == '-1') {
		/* type/clusterid sql where */
		if (get_request_var('clusterid') == '0') {
			$sql_where = 'WHERE type="U"';
		} else {
			$sql_where = 'WHERE type="U" AND gug.clusterid=?';
			$sql_params[] = get_request_var('clusterid');
		}

		/* group sql where */
		if (get_request_var('usergroup') == '-1' ) {
			$sql_join = '';
		} else {
			$sql_join = 'INNER JOIN grid_user_group_members AS gugm
				ON gug.clusterid=gugm.clusterid
				AND gug.user_or_group=gugm.username';
			$sql_where .= ' AND groupname=?';
			$sql_params[] = get_request_var('usergroup');
		}

		/* show pend only users sql where */
		if ((get_request_var('pendonly') == '') || (get_request_var('pendonly') == 'false')) {
			// Do nothing
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'numPEND>0';
		}

		/* show inactive users sql where */
		if ((get_request_var('noactivity') == '') || (get_request_var('noactivity') == 'false')) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'numJobs>0';
		}

		/* execution host sql where */
		if (get_request_var('filter') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' user_or_group LIKE ?';
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}

		$sql_query = "SELECT gug.*, gc.clustername
			FROM grid_users_or_groups AS gug
			INNER JOIN grid_clusters AS gc
			ON gug.clusterid=gc.clusterid
			$sql_join
			$sql_where
			$sql_order";
	} else {
		$sql_where = 'WHERE gqus.user_or_group IS NOT NULL AND gq.queuename=?';
		$sql_params[] = get_request_var('queue');

		if (get_request_var('clusterid') > 0) {
			$sql_where .= ' AND gq.clusterid=?';
			$sql_params[] = get_request_var('clusterid');
		}

		/* show pend only users sql where */
		if ((get_request_var('pendonly') == '') || (get_request_var('pendonly') == 'false')) {
			// Do nothing
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'gqus.pendJobs>0';
		}

		/* group sql where */
		if (get_request_var('usergroup') == '-1') {
			$sql_join = '';
		} else {
			$sql_join = 'INNER JOIN grid_user_group_members AS gugm
				ON gqus.clusterid=gugm.clusterid
				AND gqus.user_or_group=gugm.username';
			$sql_where .= ' AND groupname=?';
			$sql_params[] = get_request_var('usergroup');
		}

		$sql_query = "SELECT gc.clustername, gqus.clusterid, gqus.queue, gqus.user_or_group, gqus.efficiency, gqs.shares,
			gqs.shareAcctPath, gqs.priority, gqs.started AS numStartJobs, gqs.reserved AS numRESERVE, gqs.cpu_time, gqs.run_time,
			gqus.nojobs AS numJobs, gqus.pendjobs AS numPEND, gqus.runjobs AS numRUN, gqus.suspjobs, gq.maxjobs, gq.userJobLimit,
			gq.procJobLimit, gq.hostJobLimit
			FROM grid_clusters AS gc
			INNER JOIN grid_queues AS gq
			ON gc.clusterid=gq.clusterid
			LEFT JOIN grid_queues_users_stats AS gqus
			ON gqus.queue=gq.queuename
			AND gqus.clusterid=gq.clusterid
			LEFT JOIN grid_queues_shares AS gqs
			ON gqus.user_or_group=gqs.user_or_group
			AND gqus.queue=gqs.queue
			AND gqus.clusterid=gqs.clusterid
			$sql_join
			$sql_where
			$sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_user_display_array() {

	if (get_request_var('queue') == '-1') {
		$display_text = array(
			'nosort0' => array(
				'display' => __('Actions', 'grid')
			),
			'user_or_group' => array(
				'display' => __('User Name', 'grid'),
				'sort'    => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'grid'),
				'dbname'  => 'show_user_clustername',
				'sort'    => 'ASC'
			),
			'maxjobs' => array(
				'display' => __('Max %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_maxSlots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'maxPendJobs' => array(
				'display' => __('Max Pending', 'grid'),
				'dbname'  => 'show_user_maxpendjob',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'procJobLimit' => array(
				'display' => __('Proc Limit', 'grid'),
				'dbname'  => 'show_user_procjoblimit',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numJobs' => array(
				'display' => __('Num %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_numSlots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numStartJobs' => array(
				'display' => __('Started %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_started_slots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numPEND' => array(
				'display' => __('Pending %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_pendingslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numRUN' => array(
				'display' => __('Running %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_runningslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'efficiency' => array(
				'display' => __('Efic %%% %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_efficiency',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numSSUSP' => array(
				'display' => __('Sys Susp %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_syssusslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numUSUSP' => array(
				'display' => __('User Susp %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_usersusslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numRESERVE' => array(
				'display' => __('Reserve %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_reserveslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			)
		);
	} else {
		$display_text = array(
			'nosort' => array(
				'display' => 'Actions'
			),
			'user_or_group' => array(
				'display' => __('User Name', 'grid'),
				'sort'    => 'ASC'
			),
			'clustername' => array(
				'display' => __('Cluster Name', 'grid'),
				'dbname'  => 'show_user_clustername',
				'sort'    => 'ASC'
			),
			'shares' => array(
				'display' => __('User Shares', 'grid'),
				'dbname'  => 'show_user_shares',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'priority' => array(
				'display' => __('User Priority', 'grid'),
				'dbname'  => 'show_user_priority',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'userJobLimit' => array(
				'display' => __('User Limit', 'grid'),
				'dbname'  => 'show_user_userjoblimit',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numJobs' => array(
				'display' => __('Num Jobs', 'grid'),
				'dbname'  => 'show_user_numSlots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numStartJobs' => array(
				'display' => __('Started %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_started_slots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numPEND' => array(
				'display' => __('Pending %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_pendingslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numRUN' => array(
				'display' => __('Running %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_runningslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'efficiency' => array(
				'display' => __('Efic %%%', 'grid'),
				'dbname'  => 'show_user_efficiency',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'suspjobs' => array(
				'display' => __('Susp %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_syssusslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'numRESERVE' => array(
				'display' => __('Reserve %s', format_job_slots(), 'grid'),
				'dbname'  => 'show_user_reserveslots',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'cpu_time' => array(
				'display' => __('CPU Time', 'grid'),
				'dbname'  => 'show_user_cputime',
				'sort'    => 'DESC',
				'align'   => 'right'
			),
			'run_time' => array(
				'display' => __('Run Time', 'grid'),
				'dbname'  => 'show_user_runtime',
				'sort'    => 'DESC',
				'align'   => 'right'
			)
		);
	}

	return $display_text;
}

function usersFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_busers.php'>
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
						<?php print __('Queue', 'grid');?>
					</td>
					<td>
						<select id='queue'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$queues = db_fetch_assoc('SELECT DISTINCT queuename
									FROM grid_queues
									WHERE nojobs > 0
									ORDER BY queuename');
							} else {
								$queues = db_fetch_assoc_prepared('SELECT queuename
									FROM grid_queues
									WHERE nojobs > 0
									AND clusterid = ?
									ORDER BY queuename',
									array(get_request_var('clusterid')));
							}

							if (!empty($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue['queuename'] .'"'; if (get_request_var('queue') == $queue['queuename']) { print ' selected'; } print '>' . html_escape($queue['queuename']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_busers.php', __('Groups', 'grid'), 'usergroup', get_request_var('usergroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
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
					<td>
						<input type='checkbox' id='pendonly'<?php if ((get_request_var('pendonly') == 'true') || (get_request_var('pendonly') == 'on')) print ' checked';?>>
					</td>
					<td>
						<label for='pendonly'><?php print __('Show Pending Only', 'grid');?></label>
					</td>
					<td>
						<input type='checkbox' id='noactivity'<?php if ((get_request_var('noactivity') == 'true') || (get_request_var('noactivity') == 'on')) print ' checked';?>>
					</td>
					<td>
						<label for='noactivity'><?php print __('Show Inactive Users', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr><?php
}

function validate_busers_request_vars() {
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
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'noactivity' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'pendonly' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'usergroup' => array(
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
			'default' => 'numJobs',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	if (!isset_request_var('reset') && check_changed('queue', 'sess_gbu_queue')) {
		kill_session_var('sess_gbu_sort_column');
		unset_request_var('sort_column');
		update_order_string();
	}

	validate_store_request_vars($filters, 'sess_gbu');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function grid_view_users() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

	grid_set_minimum_page_refresh();

	$display_text = build_user_display_array();

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_busers.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&queue=' + $('#queue').val();
		strURL += '&usergroup=' + encodeURIComponent($('#usergroup').val());
		strURL += '&pendonly=' + $('#pendonly').is(':checked');
		strURL += '&noactivity=' + $('#noactivity').is(':checked');
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_busers.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#refresh, #queue, #pendonly, #noactivity, #rows, #usergroup, #clusterid, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	html_start_box(__('Batch User Filters', 'grid'), '100%', '', '3', 'center', '');
	usersFilter();
	html_end_box();

	$sql_where = '';
	$sql_join  = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$user_results = grid_view_get_users_records($sql_where, $sql_join, true, $rows, $sql_params);

	if (get_request_var('queue') == '-1') {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_users_or_groups AS gug
			INNER JOIN grid_clusters AS gc
			ON gug.clusterid=gc.clusterid
			$sql_join
			$sql_where";
	} else {
		$rows_query_string = "SELECT COUNT(*)
			FROM (grid_queues_users_stats AS gqus
			LEFT JOIN grid_queues_shares AS gqs
			ON (gqus.user_or_group=gqs.user_or_group)
			AND (gqus.queue=gqs.queue)
			AND (gqus.clusterid=gqs.clusterid))
			LEFT JOIN grid_queues AS gq
			ON (gqus.queue=gq.queuename)
			AND (gqus.clusterid=gq.clusterid)
			$sql_join
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = form_process_visible_display_text($display_text);
	/* generate page list */
	$nav = html_nav_bar('grid_busers.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Users', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (!empty($user_results)) {
		foreach ($user_results as $user) {
			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($user['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
					AND host_snmp_cache.host_id=graph_local.host_id
					WHERE graph_local.host_id = ?
					AND graph_local.snmp_index = ?
					AND host_snmp_cache.field_name='user'",
					array($cacti_host, $user['user_or_group']));

				if (!empty($local_graph_ids)) {
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

			if (get_request_var('queue') == '-1') {
				$user['queue'] = '-1';

				form_alternate_row();
				?>
				<td class='nowrap' style='width:20'>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php?reset=1&clusterid=' . $user['clusterid'] . '&job_user=' . $user['user_or_group'] . '&status=-1&resource_str=&filter=&type=-1&model=-1&hgroup=-1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hosts.gif' alt='' title='<?php print __esc('View Batch Hosts', 'grid');?>'></a>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bqueues.php?reset=1&clusterid=' . $user['clusterid'] . '&job_user=' . $user['user_or_group']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_queues.gif' alt='' title='<?php print __esc('View Batch Queues', 'grid');?>'></a>
					<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared('SELECT COUNT(*) FROM lic_services_feature_details WHERE username = ?', array($user['user_or_group']))) {?>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&couser=' . $user['user_or_group'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts', 'grid');?>'></a><?php }?>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $user['clusterid'] . '&queue=' . $user['queue'] . '&job_user=' . $user['user_or_group'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
					<?php if (isset($graph_select)) {?>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View User Graphs', 'grid');?>'></a>
					<?php }?>
					<?php api_plugin_hook_function('grid_busers_icon', $user); ?>
				</td>
				<?php
				$user_url   =  $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $user['clusterid'] .
					'&job_user=' . $user['user_or_group'] .
					'&status=RUNNING&page=1';

				form_selectable_cell_metadata('detailed', 'user', $user['clusterid'], $user['user_or_group'], '', '', __('Show User Jobs', 'grid'), true, $user_url);

				form_selectable_cell_visible($user['clustername'], 'show_user_clustername', $i, '');
				form_selectable_cell_visible(empty($user['maxJobs']) ? '-' : number_format_i18n($user['maxJobs']), 'show_user_maxSlots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['maxPendJobs']), 'show_user_maxpendjob', $i, 'right');
				form_selectable_cell_visible($user['procJobLimit'], 'show_user_procjoblimit', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numJobs']), 'show_user_numSlots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numStartJobs']), 'show_user_started_slots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numPEND']), 'show_user_pendingslots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numRUN']), 'show_user_runningslots', $i, 'right');
				form_selectable_cell_visible(strlen($user['efficiency']) == 0 ? __('N/A', 'grid') : display_ut($user['efficiency']/100,1), 'show_user_efficiency', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numSSUSP']), 'show_user_syssusslots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numUSUSP']), 'show_user_usersusslots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numRESERVE']), 'show_user_reserveslots', $i, 'right');

				form_end_row();

				$i++;
			} else {
				form_alternate_row();

				?>
				<td class='nowrap' style='width:20'>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php?reset=1&clusterid=' . $user['clusterid'] . '&job_user=' . $user['user_or_group'] . '&status=-1&resource_str=&filter=&type=-1&model=-1&hgroup=-1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hosts.gif' alt='' title='<?php print __esc('View Batch Hosts', 'grid');?>'></a>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bqueues.php?reset=1&clusterid=' . $user['clusterid'] . '&job_user=' . $user['user_or_group']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_queues.gif' alt='' title='<?php print __esc('View Batch Queues', 'grid');?>'></a>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $user['clusterid'] . '&queue=' . $user['queue'] . '&job_user=' . $user['user_or_group'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
					<?php if (isset($graph_select)) {?>
					<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View User Graphs', 'grid');?>'></a>
					<?php }?>
				</td>
				<?php
				$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $user['clusterid'] .
					'&job_user=' . $user['user_or_group'] .
					'&status=RUNNING&page=1';

				form_selectable_cell_metadata('detailed', 'user', $user['clusterid'], $user['user_or_group'], '', '', __('Show User Jobs', 'grid'), true, $url);

				$title_shareAcctPath = $user['shareAcctPath'] . (empty($user['shareAcctPath']) ? '' : '/');

				form_selectable_cell_visible($user['clustername'], 'show_user_clustername', $i, '');
				if ($user['shares'] == '') {
					form_selectable_cell_visible(__('N/A', 'grid'), 'show_user_shares', $i, 'right');
				} else {
					form_selectable_cell_visible(number_format_i18n($user['shares']), 'show_user_shares', $i, 'right', get_request_var('queue') . "/" . $title_shareAcctPath . $user['user_or_group']);
				}

				if ($user['priority'] == '') {
					form_selectable_cell_visible(__('N/A', 'grid'), 'show_user_priority', $i, 'right');
				} else {
					form_selectable_cell_visible(number_format_i18n($user['priority']), 'show_user_priority', $i, 'right', get_request_var('queue') . "/" . $title_shareAcctPath . $user['user_or_group']);
				}

				form_selectable_cell_visible($user['userJobLimit'] == 0 ? '-' : number_format_i18n($user['userJobLimit']), 'show_user_userjoblimit', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numJobs']), 'show_user_numSlots', $i, 'right');
				form_selectable_cell_visible($user['numStartJobs'] == '' ? __('N/A', 'grid') : number_format_i18n($user['numStartJobs']), 'show_user_started_slots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numPEND']), 'show_user_pendingslots', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['numRUN']), 'show_user_runningslots', $i, 'right');
				form_selectable_cell_visible($user['efficiency'] == '' ? __('N/A', 'grid') : display_ut($user['efficiency']/100,1), 'show_user_efficiency', $i, 'right');
				form_selectable_cell_visible(number_format_i18n($user['suspjobs']), 'show_user_syssusslots', $i, 'right');
				form_selectable_cell_visible($user['numRESERVE'] == '' ? __('N/A', 'grid') : number_format_i18n($user['numRESERVE']), 'show_user_reserveslots', $i, 'right');
				form_selectable_cell_visible($user['cpu_time'] == '' ? __('N/A', 'grid') : display_job_time($user['cpu_time'],2), 'show_user_cputime', $i, 'right');
				form_selectable_cell_visible($user['run_time'] == '' ? __('N/A', 'grid') :display_job_time($user['run_time'],2), 'show_user_runtime', $i, 'right');

				form_end_row();

				$i++;
			}
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No User Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($user_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}
