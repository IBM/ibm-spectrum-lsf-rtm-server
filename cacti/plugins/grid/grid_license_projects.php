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

$title = __('IBM Spectrum LSF RTM - Batch License Project Details');

grid_view_projects();

function grid_view_get_project_records(&$sql_where, $apply_limits = true, $rows = '30', &$sql_params = array()) {
	global $timespan, $grid_efficiency_sql_ranges;

	$sql_group_by = '';

	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'grid_license_projects.clusterid,';
		$sql_group_by = ' GROUP BY grid_clusters.clusterid, licenseProject ';
	} else if (get_request_var('clusterid') == '-1') {
		$clusterid = "'0' AS clusterid,";
		$sql_group_by = ' GROUP BY licenseProject ';
	} else {
		$clusterid = 'grid_license_projects.clusterid,';
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(grid_license_projects.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (grid_license_projects.licenseProject LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	if (get_request_var('clusterid') == 0 || get_request_var('clusterid') == -1) {
		$sql_query = "SELECT $clusterid grid_clusters.clustername, grid_license_projects.licenseProject,
			sum(grid_license_projects.numRUN) as numRUN, sum(grid_license_projects.numJOBS) as numJOBS,
			sum(grid_license_projects.numPEND) as numPEND, avg(grid_license_projects.efficiency) as efficiency,
			avg(grid_license_projects.avg_mem) as avg_mem, max(grid_license_projects.max_mem) as max_mem,
			avg(grid_license_projects.avg_swap) as avg_swap, max(grid_license_projects.max_swap) as max_swap,
			sum(grid_license_projects.total_cpu) as total_cpu
			FROM grid_license_projects
			INNER JOIN grid_clusters
			ON grid_license_projects.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_group_by
			$sql_order";
	} else {
		$sql_query = "SELECT grid_clusters.clustername, grid_license_projects.*
			FROM grid_license_projects
			INNER JOIN grid_clusters
			ON grid_license_projects.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function projectsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_license_projects.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All');?></option>
							<option value='-1'<?php if (get_request_var('clusterid') == '-1') {?> selected<?php }?>><?php print __('N/A');?></option>
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
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<input type='checkbox' id='unused'<?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused License Projects');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_projects() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
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

	validate_store_request_vars($filters, 'sess_glp');

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
		strURL  = 'grid_license_projects.php?action=viewprojects&header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_license_projects.php?action=viewprojects&header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #unused, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	html_start_box(__('Batch License Projects Filters'), '100%', '', '3', 'center', '');
	projectsFilter();
	html_end_box();

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
		$sql_where = 'WHERE grid_license_projects.numJOBS>0';
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$projects = grid_view_get_project_records($sql_where, true, $rows, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM grid_license_projects
		INNER JOIN grid_clusters
		ON grid_clusters.clusterid=grid_license_projects.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_project_display_project();

	/* generate page list */
	$nav = html_nav_bar('grid_license_projects.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Projects'), 'page', 'main');

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

			if (isset($cacti_host)) {
				if ($cacti_host > 0) {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.host_id = ?
						AND graph_local.snmp_index = ?
						AND host_snmp_cache.field_name='licenseProject'",
						array($cacti_host, $project['licenseProject']));
				} else {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.snmp_index= ?
						AND host_snmp_cache.field_name='licenseProject'",
						array($project['licenseProject']));
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
			<td class='nowrap' style='width:1%'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $project['clusterid'] . '&filter=' . $project['licenseProject'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Active Jobs');?>'></a>
				<?php if (isset($graph_select)) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __('View License Project Graphs');?>'></a>
				<?php }?>
			</td>
				<?php
				$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $project['clusterid'] .
					'&status=RUNNING&filter=' . $project['licenseProject']);

				form_selectable_cell_metadata('simple', 'license-project', $project['clusterid'], $project['licenseProject'], '', '', __esc($project['licenseProject']), true, $url);
				?>
			<?php
			print '<td>' . ((get_request_var('clusterid') == -1) ? __('N/A') : $project['clustername']) . '</td>';

			print '<td>' . number_format_i18n($project['numJOBS'], '-1')   . '</td>';
			print '<td>' . number_format_i18n($project['numPEND'], '-1')   . '</td>';
			print '<td>' . number_format_i18n($project['numRUN'], '-1')    . '</td>';
			print '<td>' . display_job_effic($project['efficiency'],2)     . '</td>';
			print '<td>' . display_job_memory($project['max_mem'],2)      . '</td>';
			print '<td>' . display_job_memory($project['avg_mem'],2)      . '</td>';
			print '<td>' . display_job_memory($project['max_swap'],2)     . '</td>';
			print '<td>' . display_job_memory($project['avg_swap'],2)     . '</td>';
			print '<td>' . display_hours($project['total_cpu']/60,2,false) . '</td>';
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No License Project Records Found') . '</em></td></tr>';
	}
	html_end_box(false);

	if (!empty($projects)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function build_project_display_project() {
	$display_text  = array();
	$display_text += array('nosort'         => array(__('Action'), ''));
	$display_text += array('licenseProject' => array(__('License Project'), 'ASC'));
	$display_text += array('clustername'    => array(__('Cluster Name'), 'ASC'));
	$display_text += array('numJOBS'        => array(__('Total %s', format_job_slots()), 'DESC'));
	$display_text += array('numPEND'        => array(__('Pending %s', format_job_slots()), 'DESC'));
	$display_text += array('numRUN'         => array(__('Running %s', format_job_slots()), 'DESC'));
	$display_text += array('efficiency'     => array(__('Avg Effic'), 'DESC'));
	$display_text += array('max_mem'        => array(__('Max Mem'), 'DESC'));
	$display_text += array('avg_mem'        => array(__('Avg Mem'), 'DESC'));
	$display_text += array('max_swap'       => array(__('Max Swap'), 'DESC'));
	$display_text += array('avg_swap'       => array(__('Avg Swap'), 'DESC'));
	$display_text += array('total_cpu'      => array(__('Total CPU'), 'DESC'));

	return $display_text;
}

