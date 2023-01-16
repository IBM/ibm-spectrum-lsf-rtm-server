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
include_once('./plugins/grid/include/grid_constants.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/benchmark/functions.php');

$title = __('IBM Spectrum LSF RTM - Benchmark Job Management');

set_default_action();

$_SESSION['sess_nav_level_cache'] = array();

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();

if (get_request_var('action') == 'view') {
	$title = __('IBM Spectrum LSF RTM - Benchmark Job Dashboard');
	general_header();
	view_benchmark_details();
	bottom_footer();
}else{
	$title = __('IBM Spectrum LSF RTM - Benchmark Job Dashboard');
	view_benchmarks();
}

function grid_view_get_records(&$sql_where, $apply_limits = TRUE, $rows = 30, &$sql_params = array()) {
	benchmark_status_where($sql_where);

	/* cluster id sql where */
	if (get_request_var('clusterid') <= 0) {
		/* Show all items */
	}else {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' benchmark_name LIKE ?';
		$sql_params[] = '%'. get_request_var('filter') . '%';
    }

	$sql_order = get_order_string();

	$sql_query = "SELECT a.*, FROM_UNIXTIME(last_runtime) AS last_runtime_text
		FROM grid_clusters_benchmarks AS a
		$sql_where
		$sql_order";

	// echo $sql_query;

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_job_config_display_array() {
	$display_text = array();
	$display_text += array('nosort'             => array('display' => __('Actions'),        'align' => 'left',  'sort' => ''));
	$display_text += array('benchmark_name'     => array('display' => __('Benchmark Name'), 'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('benchmark_id'       => array('display' => __('ID'),             'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('clusterid'          => array('display' => __('Cluster Name'),   'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('status'             => array('display' => __('Status'),         'align' => 'left',  'sort' => 'ASC', 'tip' => 'Status is sorted by its code'));
	$display_text += array('run_interval'       => array('display' => __('Run Interval'),   'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('cur_time'           => array('display' => __('Last'),           'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('avg_time'           => array('display' => __('Average'),        'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('min_time'           => array('display' => __('Min'),            'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('max_time'           => array('display' => __('Max'),            'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_good_runs'    => array('display' => __('Done'),           'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_failed_runs'  => array('display' => __('Exited'),         'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_errored_runs' => array('display' => __('Failed'),         'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('queue'              => array('display' => __('Queue'),          'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('project'            => array('display' => __('Project'),        'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('username'           => array('display' => __('User Name'),      'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('user_group'         => array('display' => __('User Group'),     'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('last_runtime'       => array('display' => __('Last Runtime'),   'align' => 'right', 'sort' => 'DESC'));

	return $display_text;
}

function job_config_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $benchmark_text_status;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_benchmark_summary.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster');?>
					</td>
					<td>
						<select id='clusterid'>
						<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All');?></option>
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
					<td>
						<?php print __('Job Status');?>
					</td>
					<td>
						<select id='status'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>><?php print __('All');?></option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>><?php print __('All Errors');?></option>
							<option value='-3'<?php if (get_request_var('status') == '-3') {?> selected<?php }?>><?php print __('All Job Exits');?></option>
							<option value='-4'<?php if (get_request_var('status') == '-4') {?> selected<?php }?>><?php print __('All Threshold Violations');?></option>
							<?php
							if (cacti_sizeof($benchmark_text_status)) {
								foreach ($benchmark_text_status as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('status') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Rows');?>
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
						<?php print __('Refresh');?>
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
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function view_benchmarks() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval;
	global $bm_run_intervals,  $minimum_user_refresh_intervals, $config, $benchmark_actions;
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
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'default' => read_grid_config_option('refresh_interval'),
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
			'default' => 'benchmark_id',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_bmsum');
	/* ================= input validation ================= */

	grid_set_minimum_page_refresh();


	general_header();

	?>
	<script type='text/javascript'>

	function applyBenchmarkFilterChange() {
		strURL  = 'grid_benchmark_summary.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&status=' + $('#status').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_benchmark_summary.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyBenchmarkFilterChange();
		});

		$('#clusterid, #rows, #refresh, #status').change(function() {
			applyBenchmarkFilterChange();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$debug_log = nl2br(debug_log_return('grid_admin'));
	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table class='debug'>
			<tr>
				<td>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box(__('Benchmark Jobs'), '100%', '', '3', 'center', '');
	job_config_filter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	}elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	}else{
		$rows = get_request_var('rows');
	}

	$benchmark_results = grid_view_get_records($sql_where, TRUE, $rows, $sql_params);

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*) FROM grid_clusters_benchmarks As a $sql_where", $sql_params);

	$display_text = build_job_config_display_array();

	/* generate page list */
	$nav = html_nav_bar('grid_benchmark_summary.php?clusterid=' . get_request_var('clusterid'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, '', __('Benchmarks Jobs'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($benchmark_results)) {
		foreach ($benchmark_results as $benchmark) {
			form_alternate_row('line' . $benchmark['benchmark_id']);

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($benchmark['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM graph_local
					INNER JOIN graph_templates
					ON graph_templates.id=graph_local.graph_template_id
					WHERE graph_local.host_id=?
					AND graph_templates.hash='6c8c4a6c27c0b73866f11748e17f5ed2'
					AND graph_local.snmp_index=?",
					array($cacti_host, $benchmark['benchmark_id']));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				}else{
					unset($graph_select);
				}
			}else{
				unset($graph_select);
			}

			$cluster_name = grid_get_clustername($benchmark['clusterid']);
			$hash = '6c8c4a6c27c0b73866f11748e17f5ed2';
		 	print "<tr class='odd tableRow'>\n";
			// It's possible that the cluster has been removed from RTM
			if ($cluster_name == 'NOT FOUND') {
				?>
				<td class='nowrap' style='width:1%'>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_summary.php?action=view&benchmark_id=' . $benchmark['benchmark_id']);?>'><img src='<?php print $config['url_path'];?>plugins/benchmark/images/view_job.gif' alt='' title='<?php print __('View Benchmark Results');?>'></a>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_jobs.php?timespan=7&status=-1&filter=&benchmark_id=' . $benchmark['benchmark_id'] . '&clusterid=0');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Benchmark Job');?>'></a>
				<?php if (isset($graph_select)) { ?>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __('View Benchmark Graphs');?>'></a>
				<?php } ?>
				</td>
				<?php
				form_selectable_cell($benchmark['benchmark_name'], $benchmark['benchmark_id'], '');
			} else {
				?>
				<td class='nowrap' style='width:1%'>
					<?php if (api_user_realm_auth('benchmark.php')) { ?>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/benchmark.php?action=edit&benchmark_id=' . $benchmark['benchmark_id']);?>'><img src='<?php print $config['url_path'];?>plugins/benchmark/images/edit_job.gif' alt='' title='<?php print __('Edit Benchmark Job');?>'></a>
					<?php } ?>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_summary.php?action=view&benchmark_id=' . $benchmark['benchmark_id']);?>'><img src='<?php print $config['url_path'];?>plugins/benchmark/images/view_job.gif' alt='' title='<?php print __('View Benchmark Job');?>'></a>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_jobs.php?timespan=7&status=-1&filter=&benchmark_id=' . $benchmark['benchmark_id'] . '&clusterid=' . $benchmark['clusterid']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Benchmark Results');?>'></a>
				<?php if (isset($graph_select)) { ?>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __('View Benchmark Graphs');?>'></a>
				<?php } ?>
				</td>
				<?php
				form_selectable_cell($benchmark['benchmark_name'], $benchmark['benchmark_id'], '', 'nowrap');
			}

			form_selectable_cell($benchmark['benchmark_id'], $benchmark['benchmark_id'], '');
			form_selectable_cell($cluster_name, $benchmark['benchmark_id'], '', 'nowrap');
			form_selectable_cell(benchmark_get_status($benchmark['status']), $benchmark['benchmark_id'], '', 'nowrap');
			form_selectable_cell($bm_run_intervals[$benchmark['run_interval']], $benchmark['benchmark_id'], '', 'nowrap');
			form_selectable_cell(benchmark_get_time($benchmark['cur_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(benchmark_get_time($benchmark['avg_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(benchmark_get_time($benchmark['min_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(benchmark_get_time($benchmark['max_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(number_format($benchmark['total_good_runs']), $benchmark['benchmark_id'], '4%', 'right');
			form_selectable_cell(number_format($benchmark['total_failed_runs']), $benchmark['benchmark_id'], '4%', 'right');
			form_selectable_cell(number_format($benchmark['total_errored_runs']), $benchmark['benchmark_id'], '4%', 'right');

			form_selectable_cell($benchmark['queue'], $benchmark['benchmark_id'], '');
			form_selectable_cell(($benchmark['project'] != '' ? $benchmark['project']:__('N/A')), $benchmark['benchmark_id'], '');
			form_selectable_cell($benchmark['username'], $benchmark['benchmark_id'], '');
			form_selectable_cell(($benchmark['user_group'] != '' ? $benchmark['user_group']:__('N/A')), $benchmark['benchmark_id'], '');

			if ($benchmark['last_runtime'] == 0) {
				$last_runtime_display = __('Never run');
			} else {
				$last_runtime_display = $benchmark['last_runtime_text'];
			}
			form_selectable_cell($last_runtime_display, $benchmark['benchmark_id'], '', 'right nowrap');

			form_end_row();
		}
	}else{
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Benchmark Jobs Found') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($benchmark_results)) {
		print $nav;
	}

	bottom_footer();
}

