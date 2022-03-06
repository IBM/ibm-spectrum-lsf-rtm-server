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

$title = __('IBM Spectrum LSF RTM - Batch User Group Statistics', 'grid');

grid_view_ugroups();

function grid_get_ugroup_records($userGroup, &$sql_where, $method, $apply_limits = true, $rows, &$sql_params) {
	if ($method == 'jobmap') {
		$sql_where = "WHERE ugs.userGroup = $userGroup";
	} else {
		$sql_where = "WHERE (uog.type='G' OR uog.type IS NULL) AND ugs.userGroup = $userGroup";
	}

	$sql_group_by = '';

	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'ugs.clusterid,';
		$sql_group_by = " GROUP BY ugs.clusterid, $userGroup ";
	} else if (get_request_var('clusterid') == '-1') {
		$clusterid = "'0' AS clusterid,";
		$sql_group_by = " GROUP BY $userGroup ";
	} else {
		$clusterid = 'ugs.clusterid,';
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (ugs.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
		$sql_group_by = " GROUP BY ugs.clusterid, $userGroup ";
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " $userGroup LIKE ?";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	/* show inactive ugroups sql where */
	if ((get_request_var('noactivity') == '') || (get_request_var('noactivity') == 'false')) {
		if ($method == 'jobmap') {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (ugs.numJOBS>0)';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (uog.numJobs>0 OR ugs.numJOBS>0)';
		}
	}

	if ($method == 'jobmap') {
		$sql_query = "SELECT $clusterid gc.clustername, $userGroup as userGroup, sum(ugs.numRUN) as numRUN,
			sum(ugs.numJOBS) as numJOBS, sum(ugs.numPEND) as numPEND, avg(ugs.efficiency) as efficiency,
			avg(ugs.avg_mem) as avg_mem, max(ugs.max_mem) as max_mem, avg(ugs.avg_swap) as avg_swap,
			max(ugs.max_swap) as max_swap, sum(ugs.total_cpu) as total_cpu
			FROM grid_user_group_stats AS ugs
			INNER JOIN grid_clusters AS gc
			ON ugs.clusterid=gc.clusterid
			$sql_where
			$sql_group_by
			$sql_order";
	} else {
		$sql_query = "SELECT $clusterid gc.clustername, $userGroup as userGroup, sum(ugs.numRUN) as numRUN,
			sum(ugs.numJOBS) as numJOBS, sum(ugs.numPEND) as numPEND, avg(ugs.efficiency) as efficiency,
			avg(ugs.avg_mem) as avg_mem, max(ugs.max_mem) as max_mem, avg(ugs.avg_swap) as avg_swap,
			max(ugs.max_swap) as max_swap, sum(ugs.total_cpu) as total_cpu, sum(uog.maxJobs) as maxJobs,
			sum(uog.numJobs) as uog_numJOBS, sum(uog.numRUN) as uog_numRUN, sum(uog.numPEND) as uog_numPEND,
			sum(uog.numStartJobs) as numStartJobs, sum(uog.numSSUSP) as numSSUSP, sum(uog.numUSUSP) as numUSUSP,
			sum(uog.procJobLimit) as procJobLimit, sum(uog.maxPendJobs) as maxPendJobs, sum(uog.numRESERVE) as numRESERVE
			FROM grid_user_group_stats AS ugs
			INNER JOIN grid_clusters AS gc
			ON ugs.clusterid=gc.clusterid
			LEFT JOIN grid_users_or_groups AS uog
			ON ugs.clusterid=uog.clusterid
			AND ugs.userGroup=uog.user_or_group
			AND (uog.type='G' or uog.type IS NULL)
			$sql_where
			$sql_group_by
			$sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_ugroup_display_array($method) {
	$display_text  = array(
		'nosort0' => array(
			'display' => __('Actions', 'grid')
		),
		'userGroup' => array(
			'display' => __('Group Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'grid'),
			'dbname'  => 'ugroup_cluster',
			'sort'    => 'ASC'
		)
	);

	if ($method != 'jobmap') {
		$display_text += array(
			'maxjobs' => array(
				'display' => __('Max %s', format_job_slots(), 'grid'),
				'dbname'  => 'ugroup_max_jobs',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'maxPendJobs' => array(
				'display' => __('Max Pending', 'grid'),
				'dbname'  =>  'ugroup_max_pendjob',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'procJobLimit' => array(
				'display' => __('Proc Limit', 'grid'),
				'dbname'  =>  'ugroup_max_pendjob',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'numStartJobs' => array(
				'display' => __('Started %s', format_job_slots(), 'grid'),
				'dbname'  =>  'ugroup_num_start_jobs',
				'align'   => 'right',
				'sort'    => 'DESC'
			)
		);
	}

	$display_text += array(
		'numJOBS' => array(
			'display' => __('Num %s', format_job_slots(), 'grid'),
			'dbname'  =>  'ugroup_num_jobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numPEND' => array(
			'display' => __('Pending %s', format_job_slots(), 'grid'),
			'dbname'  =>  'ugroup_num_pend',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRUN' => array(
			'display' => __('Running %s', format_job_slots(), 'grid'),
			'dbname'  =>  'ugroup_num_run',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	if ($method != 'jobmap') {
		$display_text += array(
			'numSSUSP' => array(
				'display' => __('SSUSP %s', format_job_slots(), 'grid'),
				'dbname'  =>  'ugroup_num_ssusp',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'numUSUSP' => array(
				'display' => __('USUSP %s', format_job_slots(), 'grid'),
				'dbname'  =>  'ugroup_num_ususp',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
			'numRESERVE' => array(
				'display' => __('Reserve %s', format_job_slots(), 'grid'),
				'dbname'  =>  'ugroup_num_reserve',
				'align'   => 'right',
				'sort'    => 'DESC'
			),
		);
	}

	$display_text += array(
		'efficiency' => array(
			'display' => __('Efic %%%', 'grid'),
			'dbname'  =>  'ugroup_efficiency',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem' => array(
			'display' => __('Avg Mem', 'grid'),
			'dbname'  =>  'ugroup_avg_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem' => array(
			'display' => __('Max Mem', 'grid'),
			'dbname'  =>  'ugroup_max_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swap' => array(
			'display' => __('Avg VMem', 'grid'),
			'dbname'  =>  'ugroup_avg_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap' => array(
			'display' => __('Max VMem', 'grid'),
			'dbname'  =>  'ugroup_max_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_cpu' => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  =>  'ugroup_total_cpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}

function userGroupFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_projectgroup_filter_levels;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_bugroup.php'>
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
					<?php if (read_config_option('grid_ugroup_group_aggregation') == 'on') { ?>
				 	<td>
						<?php print __('Level', 'grid');?>
					</td>
					<td>
						<select id='level'>
							<?php
							$max_level = read_config_option('grid_job_stats_ugroup_level_number');
							$aggregatelevel = get_request_var('level');
							foreach ($grid_projectgroup_filter_levels as $key => $value) {
								if (empty($max_level) || $key <= $max_level) {
									print '<option value="' . $key . '"'; if ($aggregatelevel == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php } else { ?>
					<td><input type='hidden' name='level' value='0'></td>
					<?php } ?>
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
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<input type='checkbox' id='noactivity'<?php if ((get_request_var('noactivity') == 'true') || (get_request_var('noactivity') == 'on')) print ' checked=';?>>
					</td>
					<td>
						<label for='noactivity'><?php print __('Show Inactive Groups', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_view_ugroups() {
	global $title, $report, $grid_search_types, $minimum_user_refresh_intervals, $config;
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
		'level' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_config_option('grid_ugroup_group_aggregation') == 'on' ? '1':'0'
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'noactivity' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
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
			'default' => 'userGroup',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gug');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	$method = read_config_option('grid_usergroup_method');

	$display_array = build_ugroup_display_array($method);

	grid_set_minimum_page_refresh();

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bugroup.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&level=' + $('#level').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&noactivity=' + $('#noactivity').is(':checked');
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_bugroup.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #refresh, #level, #noactivity, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	html_start_box(__('Batch User Group Filters', 'grid'), '100%', '', '3', 'center', '');
	userGroupFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$userGroup = 'userGroup';
	if (get_request_var('level') > 0) {
		$userGroup = get_ugroup_aggregation_string(get_request_var('level'));
	}

	$ugroup_results = grid_get_ugroup_records($userGroup, $sql_where, $method, true, $rows, $sql_params);

	if ($method == 'jobmap') {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_user_group_stats AS ugs
			INNER JOIN grid_clusters AS gc
			ON gc.clusterid=ugs.clusterid
			$sql_where";
	} else {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_user_group_stats AS ugs
			INNER JOIN grid_clusters AS gc
			ON gc.clusterid=ugs.clusterid
			LEFT JOIN grid_users_or_groups AS uog
			ON ugs.clusterid=uog.clusterid
			AND ugs.userGroup=uog.user_or_group
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_ugroup_display_array($method);

	/* generate page list */
	$nav = html_nav_bar('grid_bugroup.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Groups', 'grid'), 'page', 'main');

	print $nav;

	html_start_box("", '100%', '', "3", "center", "");

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($ugroup_results)) {
		foreach ($ugroup_results as $ugroup) {
			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($ugroup['clusterid']));

			$group = $ugroup['userGroup'];

			if ($group == '') {
				$group = __('default', 'grid');
			}

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
					AND host_snmp_cache.host_id=graph_local.host_id
					WHERE graph_local.host_id = ?
					AND graph_local.snmp_index = ?
					AND host_snmp_cache.field_name='groupName'",
					array($cacti_host, $group));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = '&graph_add=';

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
			<td class='nowrap'>
				<?php if ($method != 'jobmap') { ?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_busers.php?reset=1&usergroup=' . $group . '&clusterid=' . $ugroup['clusterid']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_users.gif' alt='' title='<?php print __esc('View Users', 'grid');?>'></a>
				<?php } ?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $ugroup['clusterid'] . '&usergroup=' . $group . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>

				<?php if (isset($graph_select)) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&page=1&graph_template_id=-1&rfilter=&style=selective&host_id=-1&' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View User Group Graphs', 'grid');?>'></a>
				<?php }?>
			</td>
			<?php
			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $ugroup['clusterid'] .
				'&usergroup=' . $group . '&status=RUNNING&page=1');

			form_selectable_cell_metadata('simple', 'user-group', $ugroup['clusterid'], $group, '', '', '', true, $url);

			form_selectable_cell_visible($ugroup['clustername'], 'ugroup_cluster');

			if ($method != 'jobmap') {
				form_selectable_cell_visible(empty($ugroup['maxJobs']) ? '-':number_format_i18n($ugroup['maxJobs'], '-1'), 'ugroup_max_jobs', $i, 'right');
				form_selectable_cell_visible(empty($ugroup['maxPendJobs']) ? '-':number_format_i18n($ugroup['maxPendJobs'], '-1'), 'ugroup_max_pendjob', $i, 'right');
				form_selectable_cell_visible(empty($ugroup['procJobLimit']) ? '-':$ugroup['procJobLimit'], 'ugroup_max_pendjob', $i, 'right');
				form_selectable_cell_visible(empty($ugroup['numStartJobs']) ? '-':number_format_i18n($ugroup['numStartJobs'],'-1'), 'ugroup_num_start_jobs', $i, 'right');
			}

			if ($method != 'jobmap') {
				form_selectable_cell_visible(number_format_i18n(max($ugroup['numJOBS'], $ugroup['uog_numJOBS'], '-1')), 'ugroup_num_jobs', $i, 'right');
			} else {
				form_selectable_cell_visible(number_format_i18n($ugroup['numJOBS'], '-1'), 'ugroup_num_jobs', $i, 'right');
			}

			if ($method != 'jobmap') {
				form_selectable_cell_visible(number_format_i18n(max($ugroup['numPEND'], $ugroup['uog_numPEND'], '-1')), 'ugroup_num_pend', $i, 'right');
			} else {
				form_selectable_cell_visible(number_format_i18n($ugroup['numPEND'], '-1'), 'ugroup_num_pend', $i, 'right');
			}

			if ($method != 'jobmap') {
				form_selectable_cell_visible(number_format_i18n(max($ugroup['numRUN'], $ugroup['uog_numRUN'], '-1')), 'ugroup_num_run', $i, 'right');
			} else {
				form_selectable_cell_visible(number_format_i18n($ugroup['numRUN'], '-1'), 'ugroup_num_run', $i, 'right');
			}

			if ($method != 'jobmap') {
				form_selectable_cell_visible(empty($ugroup['numSSUSP']) ? '-':number_format_i18n($ugroup['numSSUSP'], '-1'), 'ugroup_num_ssusp', $i, 'right');
				form_selectable_cell_visible(empty($ugroup['numUSUSP']) ? '-':number_format_i18n($ugroup['numUSUSP'], '-1'), 'ugroup_num_ususp', $i, 'right');
				form_selectable_cell_visible(empty($ugroup['numRESERVE']) ? '-':number_format_i18n($ugroup['numRESERVE'], -1), 'ugroup_num_reserve', $i, 'right');
			}

			form_selectable_cell_visible(round($ugroup['efficiency'],2), 'ugroup_efficiency', $i, 'right');
			form_selectable_cell_visible(display_job_memory($ugroup['avg_mem']), 'ugroup_avg_mem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($ugroup['max_mem']), 'ugroup_max_mem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($ugroup['avg_swap']), 'ugroup_avg_swap', $i, 'right');
			form_selectable_cell_visible(display_job_memory($ugroup['max_swap']), 'ugroup_max_swap', $i, 'right');
			form_selectable_cell_visible(display_job_time($ugroup['total_cpu']), 'ugroup_total_cpu', $i, 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No User Group Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($ugroup_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}
