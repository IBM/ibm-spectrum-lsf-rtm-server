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

$title = __('IBM Spectrum LSF RTM - Batch Project Details', 'grid');

grid_view_projects();

function grid_view_get_project_records($projectName, &$sql_where, $apply_limits = true, $rows = '30', &$sql_params) {
	global $timespan, $grid_efficiency_sql_ranges;

	$sql_group_by = '';
	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'grid_projects.clusterid,';
		$sql_group_by = " GROUP BY grid_clusters.clusterid, $projectName ";
	} else if (get_request_var('clusterid') == '-1') {
		$clusterid = "'0' AS clusterid,";
		$sql_group_by = " GROUP BY $projectName ";
	} else {
		$clusterid = 'grid_projects.clusterid,';
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' grid_projects.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
		$sql_group_by = " GROUP BY grid_clusters.clusterid, $projectName ";
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		/* use regex or not */
		if (isset_request_var('regex_filter') && (get_request_var('regex_filter') == 'true' || get_request_var('regex_filter') == 'on')) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "$projectName = ?";
			$sql_params[] = get_request_var('filter');
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "$projectName LIKE ?";
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}
	}

	$sql_order = get_order_string();

	if (get_request_var('level') != 0) {
		$sql_query = "SELECT $clusterid grid_clusters.clustername, $projectName as projectName, sum(grid_projects.numRUN) as numRUN,
			sum(grid_projects.numJOBS) as numJOBS, sum(grid_projects.numPEND) as numPEND, sum(grid_projects.runJOBS) as runJOBS,
			sum(grid_projects.pendJOBS) as pendJOBS, sum(grid_projects.totalJOBS) as totalJOBS, avg(grid_projects.efficiency) as efficiency,
			avg(grid_projects.avg_mem) as avg_mem, max(grid_projects.max_mem) as max_mem, avg(grid_projects.avg_swap) as avg_swap,
			max(grid_projects.max_swap) as max_swap, sum(grid_projects.total_cpu) as total_cpu
			FROM grid_projects
			INNER JOIN grid_clusters
			ON grid_projects.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_group_by
			$sql_order";
	} else {
		$sql_query = "SELECT grid_clusters.clustername, grid_projects.* FROM grid_projects
			INNER JOIN grid_clusters
			ON grid_projects.clusterid=grid_clusters.clusterid
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

function projectsFilter($aggregatelevel) {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_projectgroup_filter_levels;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_projects.php'>
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
							$clusters = grid_get_clusterlist();
							if (!empty($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php if (read_config_option('grid_project_group_aggregation') == 'on') { ?>
				 	<td>
						<?php print __('Level', 'grid');?>
					</td>
					<td>
						<select id='level'>
							<?php
							$max_level= read_config_option('grid_job_stats_project_level_number');
							foreach ($grid_projectgroup_filter_levels as $key => $value) {
								if (!isset($max_level) || ($max_level == 0) || ($max_level > 0 && $key <= $max_level)) {
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
						<?php print __('Projects', 'grid');?>
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
						<input type='checkbox' id='regex_filter'<?php if (isset_request_var('regex_filter') && ((get_request_var('regex_filter') == 'true') || (get_request_var('regex_filter') == 'on'))) print ' checked="true"';?>>
					</td>
					<td>
						<label for='regex_filter'><?php print __('Use Exact Match', 'grid');?></label>
					</td>
					<td>
						<input type='checkbox' id='unused'<?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Projects', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_projects() {
	global $title, $grid_search_types, $grid_rows_selector, $config, $grid_projectgroup_filter_levels;
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
			'default' => 1
			),
		'regex_filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
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

	validate_store_request_vars($filters, 'sess_gpr');

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
		strURL  = 'grid_projects.php?header=false&action=viewprojects';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&regex_filter=' + $('#regex_filter').is(':checked');
		strURL += '&unused=' + $('#unused').is(':checked');
		strURL += '&level=' + $('#level').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_projects.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #level, #clusterid, #regex_filter, #unused, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$aggregatelevel = get_request_var('level');

	html_start_box(__('Batch Projects Filters', 'grid'), '100%', '', '3', 'center', '');
	projectsFilter($aggregatelevel);
	html_end_box();

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
		$sql_where = 'WHERE grid_projects.numJOBS>0';
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$projectName = 'projectName';
	if (isset($aggregatelevel) && $aggregatelevel != 0) {
		$projectName = get_project_aggregation_string($aggregatelevel);
	}

	$projects = grid_view_get_project_records($projectName, $sql_where, true, $rows, $sql_params);

	$rows_query_string = 'SELECT count(DISTINCT ' . (get_request_var('clusterid') == '0' ? 'grid_clusters.clusterid, ' 	: '' ) . " $projectName)
		FROM grid_projects
		INNER JOIN grid_clusters
		ON grid_clusters.clusterid=grid_projects.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_project_display_project();

	/* generate page list */
	$nav = html_nav_bar('grid_projects.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Projects', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (!empty($projects)) {
		foreach ($projects as $project) {
			if ($project['clusterid'] != '0') {
				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_clusters
					WHERE clusterid = ?',
					array($project['clusterid']));
			} else {
				$cacti_host = '0';
			}

			$proj_sql_params = array();
			$projwhere = '(graph_local.snmp_index=?)';
			$proj_sql_params[] = $project['projectName'];

			if (isset($cacti_host)) {
				if ($cacti_host > 0) {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
						AND (host_snmp_cache.host_id=graph_local.host_id)
						WHERE (graph_local.host_id = ?)
						AND $projwhere
						AND (host_snmp_cache.field_name RLIKE '(projectLevel|projectName)')",
						array_merge(array($cacti_host), $proj_sql_params));
				} else {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
						AND (host_snmp_cache.host_id=graph_local.host_id)
						WHERE $projwhere
						AND (host_snmp_cache.field_name RLIKE '(projectLevel|projectName)')", $proj_sql_params);
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

			$proj_drilldown = api_plugin_hook_function('job_project_name_url', $project);
			if (is_array($proj_drilldown)) {
				$proj_drilldown = '&filter=' . urlencode($project['projectName']);
			}

			form_alternate_row();

			?>
			<td class='nowrap' style='width:1%'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $project['clusterid'] . $proj_drilldown . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
				<?php if (isset($graph_select)) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Project Graphs', 'grid');?>'></a>
				<?php }?>
			</td>
			<?php

			$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $project['clusterid'] .
				'&status=RUNNING' . $proj_drilldown;

			form_selectable_cell_metadata('simple', 'project', $project['clusterid'], $project['projectName'], '', '', __esc($project['projectName'], 'grid'), true, $url);
			form_selectable_cell(((get_request_var('clusterid') == -1) ? __('N/A', 'grid') : $project['clustername']), $i);

			$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $project['clusterid'] .
				'&status=ACTIVE' . $proj_drilldown;

			form_selectable_cell_visible(filter_value($project['numJOBS'], '', $url), 'pgroup_num_jobs', $i, 'right');

			$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $project['clusterid'] .
				'&status=PEND' . $proj_drilldown;

			form_selectable_cell_visible(filter_value($project['numPEND'], '', $url), 'pgroup_num_pend', $i, 'right');

			$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $project['clusterid'] .
				'&status=RUNNING' . $proj_drilldown;

			form_selectable_cell_visible(filter_value($project['numRUN'], '', $url), 'pgroup_num_run', $i, 'right');

			form_selectable_cell_visible(display_job_effic($project['efficiency'],2), 'pgroup_avg_effic', $i, 'right');
			form_selectable_cell_visible(display_job_memory($project['max_mem'],2), 'pgroup_max_mem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($project['avg_mem'],2), 'pgroup_avg_mem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($project['max_swap'],2), 'pgroup_max_swap', $i, 'right');
			form_selectable_cell_visible(display_job_memory($project['avg_swap'],2), 'pgroup_avg_swap', $i, 'right');
			form_selectable_cell_visible(display_hours($project['total_cpu']/60,2), 'pgroup_total_cpu', $i, 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Project Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($projects)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function build_project_display_project() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Action', 'grid')
		),
		'projectName' => array(
			'display' => __('Project Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'grid'),
			'sort'    => 'ASC'
		),
		'numJOBS' => array(
			'display' => __('Total %s', format_job_slots(), 'grid'),
			'dbname'  => 'pgroup_num_jobs',
			'align'   => 'right',
			'tip'     => __('Total %s of RUNNING, PROV, USUSP, PSUSP, SSUSP, PEND jobs', format_job_slots(), 'grid'),
			'sort'    => 'DESC'
		),
		'numPEND' => array(
			'display' => __('Pending %s', format_job_slots(), 'grid'),
			'dbname'  => 'pgroup_num_pend',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRUN' => array(
			'display' => __('Running %s', format_job_slots(), 'grid'),
			'dbname'  => 'pgroup_num_run',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'efficiency' => array(
			'display' => __('Avg Effic', 'grid'),
			'dbname'  => 'pgroup_avg_effic',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem' => array(
			'display' => __('Max Mem', 'grid'),
			'dbname'  => 'pgroup_max_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem' => array(
			'display' => __('Avg Mem', 'grid'),
			'dbname'  => 'pgroup_avg_mem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap' => array(
			'display' => __('Max Swap', 'grid'),
			'dbname'  => 'pgroup_max_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swap' => array(
			'display' => __('Avg Swap', 'grid'),
			'dbname'  => 'pgroup_avg_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_cpu' => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  => 'pgroup_total_cpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}

