<?php
// $Id: 4ef60934810fd06db82026f15746a2cffb8e4d86 $
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

$title = __('IBM Spectrum LSF RTM - Client History', 'grid');

general_header();
grid_view_clients();
bottom_footer();

function grid_view_get_clients_records(&$sql_where, $apply_limits = true, $rows, &$sql_params) {
	if (get_request_var('clusterid') > '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'grid_clusters.clusterid = ?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* make sure we are looking at clients only */
	$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " isServer = 0";

	if (get_request_var('type') > 0) {
		if (get_request_var('type') == 1) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'licFeaturesNeeded = 16';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'licFeaturesNeeded != 16';
		}
	}

	/* filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= " AND ((grid_hostinfo.host LIKE ?) OR
			(grid_hostinfo.last_seen LIKE ?) OR
			(grid_hostinfo.first_seen LIKE ?) OR
			(grid_clusters.clustername LIKE ?))";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT *
		FROM (grid_clusters
		INNER JOIN grid_hostinfo
		ON grid_clusters.clusterid = grid_hostinfo.clusterid)
		$sql_where
		$sql_order";

	//printf($sql_query . "\n");

	if ($apply_limits) {
		$sql_query .= " LIMIT " . ($rows*(get_request_var('page')-1)) . "," . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function clientsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_clients.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Client Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<?php
							$types = array(
								-1 => __esc('All Clients', 'grid'),
								1  => __esc('Fixed Client', 'grid'),
								2  => __esc('Float Client', 'grid')
							);

							if (cacti_sizeof($types)) {
								foreach ($types as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('type') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT * FROM grid_clusters ORDER BY clustername');
							if (cacti_sizeof($clusters)) {
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
						<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function grid_view_clients() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $config;
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
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'host',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gcl');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	html_start_box(__('Client History Filters', 'grid'), '100%', '', '3', 'center', '');
	clientsFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	?>
	<script type='text/javascript'>
	$(function() {
		$('#clusterid, #rows, #type, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	function applyFilter() {
		strURL  = 'grid_clients.php?header=false'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_clients.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}
	</script>
	<?php

	$clients_results = grid_view_get_clients_records($sql_where, true, $rows, $sql_params);

	$rows_query_string = "SELECT
		COUNT(*)
		FROM (grid_clusters
		INNER JOIN grid_hostinfo
		ON grid_clusters.clusterid = grid_hostinfo.clusterid)
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'host'              => array(
			'display' => __('Client Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername'       => array(
			'display' => __('Cluster', 'grid'),
			'sort'    => 'ASC'
		),
		'licFeaturesNeeded' => array(
			'display' => __('Client Type', 'grid'),
			'sort'    => 'ASC'
		),
		'first_seen'        => array(
			'display' => __('First Seen', 'grid'),
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'last_seen'         => array(
			'display' => __('Last Seen', 'grid'),
			'align'   => 'right',
			'sort'    => 'ASC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('grid_clients.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, 5, __('Clients', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	//html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;

	if (cacti_sizeof($clients_results)) {
		foreach ($clients_results as $host) {
			form_alternate_row();

			form_selectable_cell_metadata('simple', 'host', $host['clusterid'], $host['host'], '', '', '', true);
			form_selectable_cell(filter_value($host['clustername'], get_request_var('filter')), '');
			form_selectable_cell($host['licFeaturesNeeded'] == 16 ? __('Fixed Client', 'grid'): __('Float Client', 'grid'), $i);
			form_selectable_cell($host['first_seen'], $i, '', 'right');
			form_selectable_cell($host['last_seen'], $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print "<tr><td colspan='5'><em>" . __('No Client Records Found', 'grid') . "</em></td></tr>";
	}

	html_end_box(false);

	if (cacti_sizeof($clients_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');
}
