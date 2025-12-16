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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

$title = __('IBM Spectrum LSF RTM - Batch System Parameters');

general_header();
grid_view_params();
bottom_footer();

function grid_view_get_params_records(&$sql_where, $apply_limits = TRUE, $rows = 30, &$sql_params = array()) {
	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= 'WHERE grid_params.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(grid_params.parameter LIKE ? OR
   			grid_params.description LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT *
		FROM grid_clusters
		INNER JOIN grid_params
		ON (grid_clusters.clusterid = grid_params.clusterid)
		$sql_where
		$sql_order";


	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function paramsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>

	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_params.php'>
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
							if (cacti_sizeof($clusters)) {
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
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __('Go');?>' title='<?php print __('Search');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view_params() {
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
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'parameter',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gpar');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	html_start_box(__('Batch System Parameter Filters'), '100%', '', '3', 'center', '');
	paramsFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	}elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	}else{
		$rows = get_request_var('rows');
	}

	$params_results = grid_view_get_params_records($sql_where, TRUE, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_params.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_params.php?clear=true&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$rows_query_string = "SELECT
		COUNT(*)
		FROM grid_clusters
		INNER JOIN grid_params
		ON (grid_clusters.clusterid = grid_params.clusterid)
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'parameter'       => array(__('Name'), 'ASC'),
 		'clustername'     => array(__('Cluster'), 'ASC'),
		'description'     => array(__('Description'), 'ASC'),
		'parameter_value' => array(__('Value'), 'ASC')
	);

	/* generate page list */
	$nav = html_nav_bar('grid_params.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Settings'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($params_results)) {
		foreach ($params_results as $param) {
			form_alternate_row();
			?>
			<td><strong><?php print filter_value($param['parameter'], get_request_var('filter'));?></strong></td>
			<td><?php print $param['clustername']; ?></td>
			<td><?php print filter_value($param['description'], get_request_var('filter'));?></td>
			<td title='<?php print $param['parameter_value'];?>'><strong><?php print filter_value($param['parameter_value'], get_request_var('filter'));?></strong></td>
			<?php
		}
	}else{
		print '<tr><td colspan="4"><em>' . __('No Batch System Parameters Found') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($params_results)) {
		print $nav;
	}
}
