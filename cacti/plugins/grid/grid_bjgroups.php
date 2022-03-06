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
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');

$title = __('IBM Spectrum LSF RTM - Batch Job Group Statistics', 'grid');

grid_view_groups();

function grid_view_get_group_records($groupName, &$sql_where, $apply_limits = true, $rows = '30', &$sql_params) {
	global $timespan, $grid_efficiency_sql_ranges;

	$sql_group_by = '';
	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'grid_groups.clusterid,';
		$sql_group_by = " GROUP BY grid_clusters.clusterid, $groupName ";
	} else if (get_request_var('clusterid') == "-1") {
		$clusterid = "'0' AS clusterid,";
		$sql_group_by = " GROUP BY $groupName ";
	} else {
		$clusterid = "grid_groups.clusterid,";
		$sql_group_by = " GROUP BY $groupName ";
		$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") .  "(grid_groups.clusterid=?)";
		$sql_params[] = get_request_var('clusterid');
	}

	/* search filter sql where */
	if (!strlen(get_request_var('filter'))) {
		/* Show all items */
	} else {
		$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") .  " (groupName LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT
		$clusterid
		grid_clusters.clustername,
		$groupName as groupName,
		sum(grid_groups.numRUN) as numRUN,
		sum(grid_groups.numJOBS) as numJOBS,
		sum(grid_groups.numPEND) as numPEND,
		sum(grid_groups.numSSUSP) as numSSUSP,
		sum(grid_groups.numUSUSP) as numUSUSP,
		avg(grid_groups.efficiency) as efficiency,
		avg(grid_groups.avg_mem) as avg_mem,
		max(grid_groups.max_mem) as max_mem,
		avg(grid_groups.avg_swap) as avg_swap,
		max(grid_groups.max_swap) as max_swap,
		sum(grid_groups.total_cpu) as total_cpu
		FROM grid_groups
		INNER JOIN grid_clusters
		ON grid_groups.clusterid=grid_clusters.clusterid
		$sql_where
		$sql_group_by
		$sql_order";

	if ($apply_limits) {
		$sql_query .= " LIMIT " . ($rows*(get_request_var('page')-1)) . "," . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function groupFilter(&$agragatelevel) {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_jgroup_filter_levels;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_bjgroups.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<option value='-1'<?php if (get_request_var('clusterid') == '-1') {?> selected<?php }?>><?php print __('N/A', 'grid');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT * FROM grid_clusters ORDER BY clustername');
							if (!empty($clusters) > 0) {
							foreach ($clusters as $cluster) {
								print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Level', 'grid');?>
					</td>
					<td>
						<select id='level'>
							<?php
							$max_level= read_config_option('grid_job_stats_group_level_number');
							foreach ($grid_jgroup_filter_levels as $key => $value) {
								if (!isset($max_level) || ($max_level == 0) || ($max_level > 0 && $key <= $max_level)) {
									print '<option value="' . $key . '"'; if ($agragatelevel == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Job Groups', 'grid');?>
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
						<input type='checkbox' id='unused'<?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Groups', 'grid');?></label>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_groups() {
	global $title, $grid_search_types, $grid_rows_selector, $config, $grid_jgroup_filter_levels;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
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
			'default' => read_config_option('grid_job_stats_group_level_number')
			),
		'unused' => array(
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
			'default' => 'numJOBS',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gjg');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bjgroups.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		strURL += '&level=' + $('#level').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_bjgroups.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #unused, #level, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$agragatelevel = get_request_var('level');

	html_start_box(__('Batch Job Groups Filters', 'grid'), '100%', '', '3', 'center', '');
	groupFilter($agragatelevel);
	html_end_box();

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on' )) {
		$sql_where = 'WHERE grid_groups.numJOBS>0';
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}
	$groupName = 'groupName';
	if (isset($agragatelevel) && $agragatelevel != 0) {
		$groupName = get_group_aggregation_string($agragatelevel);
	}

	$groups = grid_view_get_group_records($groupName, $sql_where, true, $rows, $sql_params);

	$rows_query_string = 'SELECT count(DISTINCT ' . (get_request_var('clusterid') == '0' ? 'grid_clusters.clusterid, ' : '' ) . " $groupName)
		FROM grid_groups
		INNER JOIN grid_clusters
		ON grid_clusters.clusterid=grid_groups.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_group_display_group();

	$display_text = form_process_visible_display_text($display_text);
	/* generate page list */
	$nav = html_nav_bar('grid_bjgroups.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Job Groups', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;

	if (!empty($groups)) {
		foreach ($groups as $group) {
			if ($group['clusterid'] != '0') {
				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_clusters
					WHERE clusterid = ?',
					array($group['clusterid']));
			} else {
				$cacti_host = '0';
			}

			if (isset($cacti_host)) {
				if ($cacti_host > 0) {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.host_id = ?
						AND graph_local.snmp_index = ?
						AND host_snmp_cache.field_name='groupName'",
						array($cacti_host, $group['groupName']));
				} else {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.snmp_index = ?
						AND host_snmp_cache.field_name='groupName'",
						array($group['groupName']));
				}

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

			form_alternate_row();
			?>
			<td class='nowrap' style='width:20px'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $group['clusterid'] . '&jgroup=' . $group['groupName'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
				<?php if (isset($graph_select)) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('ViewJob GroupGraphs', 'grid');?>'></a>
				<?php }?>
			</td>
			<?php
				$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $group['clusterid'] .
					'&status=RUNNING' .
					'&jgroup=' . $group['groupName']);

				form_selectable_cell_metadata('simple', 'job-group', $group['clusterid'], $group['groupName'], '', '', html_escape($group['groupName']), true, $url);
			?>
			<?php
			if (get_request_var('clusterid') == -1) {
				$clustername = 'N/A';
			} else {
				$clustername = $group['clustername'];
			}
			form_selectable_cell_visible($clustername, 'jgroup_cluster', $i);

			form_selectable_cell_visible(number_format_i18n($group['numJOBS']), 'jgroup_jobs', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($group['numPEND']), 'jgroup_pend', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($group['numRUN']), 'jgroup_running', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($group['numSSUSP']), 'jgroup_ssusp', $i, 'right');
			form_selectable_cell_visible(number_format_i18n($group['numUSUSP']), 'jgroup_ususp', $i, 'right');
			form_selectable_cell_visible(display_job_effic($group['efficiency'],2), 'jgroup_effic', $i, 'right');
			form_selectable_cell_visible(display_job_memory($group['max_mem'],2), 'jgroup_maxmem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($group['avg_mem'],2), 'jgroup_avgmem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($group['max_swap'],2), 'jgroup_maxswap', $i, 'right');
			form_selectable_cell_visible(display_job_memory($group['avg_swap'],2), 'jgroup_avgswap', $i, 'right');
			form_selectable_cell_visible(display_hours($group['total_cpu']/60,2,false), 'jgroup_totalcpu', $i, 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Job Group Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($groups)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function build_group_display_group() {
	$display_text = array(
		'nosort'      => array(
			'display' => __('Action', 'grid')
		),
		'groupName'   => array(
			'display' => __('Group Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'grid'),
			'dbname'  => 'jgroup_cluster',
			'sort'    => 'ASC'
		),
		'numJOBS'     => array(
			'display' => __('Total %s', format_job_slots(), 'grid'),
			'dbname'  => 'jgroup_jobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numPEND'     => array(
			'display' => __('Pending %s', format_job_slots(), 'grid'),
			'dbname'  => 'jgroup_pend',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRUN'      => array(
			'display' => __('Running %s', format_job_slots(), 'grid'),
			'dbname'  => 'jgroup_running',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numSSUSP'    => array(
			'display' => __('SSUSP %s', format_job_slots(), 'grid'),
			'dbname'  => 'jgroup_ssusp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numUSUSP'    => array(
			'display' => __('USUSP %s', format_job_slots(), 'grid'),
			'dbname'  => 'jgroup_ususp',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'efficiency'  => array(
			'display' => __('Avg Effic', 'grid'),
			'dbname'  => 'jgroup_effic',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem'     => array(
			'display' => __('Max Mem', 'grid'),
			'dbname'  => 'jgroup_maxmem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem'     => array(
			'display' => __('Avg Mem', 'grid'),
			'dbname'  => 'jgroup_avgmem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap'    => array(
			'display' => __('Max Swap', 'grid'),
			'dbname'  => 'jgroup_maxswap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swap'    => array(
			'display' => __('Avg Swap', 'grid'),
			'dbname'  => 'jgroup_avgswap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_cpu'   => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  => 'jgroup_totalcpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return $display_text;
}

