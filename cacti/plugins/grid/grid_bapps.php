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
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');

$title = __('IBM Spectrum LSF RTM - Batch Application Details', 'grid');

grid_view_applications();

function grid_view_get_application_records(&$sql_where, $apply_limits = true, $rows = '30', &$sql_params = array()) {
	global $timespan, $grid_efficiency_sql_ranges;

	$sql_group_by = '';
	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'grid_clusters.clusterid,';
		$sql_group_by= ' GROUP BY grid_clusters.clusterid, appName ';
	} elseif (get_request_var('clusterid') == '-1') {
		$clusterid = '"0" AS clusterid,';
		$sql_group_by= ' GROUP BY appName ';
	} else {
		$clusterid = 'grid_applications.clusterid,';
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (grid_applications.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (grid_applications.appName LIKE ?)';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	if (get_request_var('clusterid') == 0 || get_request_var('clusterid') == -1) {
		$sql_query = "SELECT
			$clusterid
			appName,
			grid_clusters.clustername,
			sum(grid_applications.numRUN) as numRUN,
			sum(grid_applications.numJOBS) as numJOBS,
			sum(grid_applications.numPEND) as numPEND,
			avg(grid_applications.efficiency) as efficiency,
			avg(grid_applications.avg_mem) as avg_mem,
			max(grid_applications.max_mem) as max_mem,
			avg(grid_applications.avg_swap) as avg_swap,
			max(grid_applications.max_swap) as max_swap,
			sum(grid_applications.total_cpu) as total_cpu
			FROM grid_applications
			INNER JOIN grid_clusters
			ON grid_applications.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_group_by
			$sql_order";

	} else {
		$sql_query = "SELECT
			grid_clusters.clustername,
			grid_applications.* FROM grid_applications
			INNER JOIN grid_clusters
			ON grid_applications.clusterid=grid_clusters.clusterid
			$sql_where $sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function appsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_bapps.php'>
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
					<td>
						<?php print __('Applications', 'grid');?>
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
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<input type='checkbox' id='unused' <?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print " checked='true'";?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Applications', 'grid');?></label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_view_applications() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;

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
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gapp');

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

	$(function() {
		$('#clusterid, #rows, #unused, #filter').change(function() {
			applyFilter();
		});

		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	function applyFilter() {
		strURL  = 'grid_bapps.php?header=false&action=viewapps'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_bapps.php?header=false&clear=true'
		loadPageNoHeader(strURL);
	}

	</script>
	<?php

	html_start_box(__('Batch Applications Filters', 'grid'), '100%', '', '3', 'center', '');
	appsFilter();
	html_end_box();

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
		$sql_where = "WHERE grid_applications.numJOBS>0";
	}

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option("grid_records");
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$applications = grid_view_get_application_records($sql_where, true, $rows, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM grid_applications
		INNER JOIN grid_clusters
		ON grid_clusters.clusterid=grid_applications.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_application_display_application();

	/* generate page list */
	$nav = html_nav_bar('grid_bapps.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Applications', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;
	if (!empty($applications)) {
		foreach ($applications as $app) {
			if ($app['clusterid'] != '0') {
				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_clusters
					WHERE clusterid = ?',
					array($app['clusterid']));
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
						AND host_snmp_cache.field_name='appName'",
						array($cacti_host, $app['appName']));
				} else {
					$local_graph_ids = db_fetch_assoc("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.snmp_index = ?
						AND host_snmp_cache.field_name='appName'",
						array($app['appName']));
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

			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1&clusterid=' . $app['clusterid'].
				'&status=RUNNING&app=' . $app['appName']);

			form_alternate_row();

			$action_url = "<a class='pic' href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_bjobs.php?action=viewlist&reset=1' .
				'&clusterid=' . $app['clusterid'] .
				'&app=' . $app['appName'] .
				'&status=ACTIVE&page=1') .  "'>
				<img src='" . $config['url_path'] . "plugins/grid/images/view_jobs.gif' alt='' title='" . __esc('View Active Jobs', 'grid') . "'>
			</a>";

			if (isset($graph_select)) {
				$action_url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'>
					<img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' title='" . __esc('View Application Graphs', 'grid'). "'>
				</a>";
			}

			form_selectable_cell($action_url, $i, '20');
			form_selectable_cell_metadata('simple', 'application', $app['clusterid'], $app['appName'], '', '', '', true, $url);
			form_selectable_cell(((get_request_var('clusterid') == -1) ? __('N/A', 'grid') : $app['clustername']), $i);

			form_selectable_cell_visible($app['numJOBS'], 'app_jobs', $i, 'right');
			form_selectable_cell_visible($app['numPEND'], 'app_pend', $i, 'right');
			form_selectable_cell_visible($app['numRUN'], 'app_running', $i, 'right');
			form_selectable_cell_visible(display_job_effic($app['efficiency'], 2), 'app_effic', $i, 'right');
			form_selectable_cell_visible(display_job_memory($app['max_mem'], 2), 'app_maxmem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($app['avg_mem'], 2), 'app_avgmem', $i, 'right');
			form_selectable_cell_visible(display_job_memory($app['max_swap'], 2), 'app_maxswap', $i, 'right');
			form_selectable_cell_visible(display_job_memory($app['avg_swap'], 2), 'app_avgswap', $i, 'right');
			form_selectable_cell_visible(display_hours($app['total_cpu']/60, 2, false), 'app_totalcpu', $i, 'right');

			$i++;

			form_end_row();
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Application Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($app)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function build_application_display_application() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Action', 'grid')
		),
		'appName' => array(
			'display' => __('Application Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'grid'),
			'dbname'  => 'app_cluster',
			'sort'    => 'ASC'
		),
		'numJOBS' => array(
			'display' => __('Total %s', format_job_slots(), 'grid'),
			'dbname'  => 'app_jobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numPEND' => array(
			'display' => __('Pending %s', format_job_slots(), 'grid'),
			'dbname'  => 'app_pend',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'numRUN' => array(
			'display' => __('Running %s', format_job_slots(), 'grid'),
			'dbname'  => 'app_running',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'efficiency' => array(
			'display' => __('Avg Effic', 'grid'),
			'dbname'  => 'app_effic',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem' => array(
			'display' => __('Max Mem', 'grid'),
			'dbname'  => 'app_maxmem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem' => array(
			'display' => __('Avg Mem', 'grid'),
			'dbname'  => 'app_avgmem',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap' => array(
			'display' => __('Max Swap', 'grid'),
			'dbname'  => 'app_maxswap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swap' => array(
			'display' => __('Avg Swap', 'grid'),
			'dbname'  => 'app_avgswap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_cpu' => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  => 'app_totalcpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}
