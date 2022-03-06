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

$title = __('IBM Spectrum LSF RTM - Batch Shared Resources', 'grid');

grid_view();

function grid_view_get_records(&$sql_where, $apply_limits = true, $rows, &$sql_params) {
	global $timespan, $grid_efficiency_sql_ranges;

	$sql_where    = 'WHERE host="ALLHOSTS"';
	$sql_group_by = '';

	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
		$clusterid = 'grid_clusters.clusterid,';
	} else if (get_request_var('clusterid') == '-1') {
		$clusterid = "'0' AS clusterid,";
		$sql_group_by= ' GROUP BY resource_name ';
	} else {
		$clusterid = 'grid_hosts_resources.clusterid,';
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'grid_hosts_resources.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . " (grid_hosts_resources.resource_name LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	if (get_request_var('clusterid') == 0 || get_request_var('clusterid') == -1) {
		$sql_query = "SELECT $clusterid resource_name, grid_clusters.clustername,
			resType, availValue, totalValue, reservedValue, value
			FROM grid_hosts_resources
			INNER JOIN grid_clusters
			ON grid_hosts_resources.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_group_by
			$sql_order";

	} else {
		$sql_query = "SELECT grid_clusters.clustername, grid_hosts_resources.*
			FROM grid_hosts_resources
			INNER JOIN grid_clusters
			ON grid_hosts_resources.clusterid=grid_clusters.clusterid
			$sql_where
			$sql_order";
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function sharedFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_shared.php'>
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
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Resources', 'grid');?>
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
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function grid_view() {
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
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'resource_name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'has_graphs' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => read_config_option('default_has') == 'on' ? 'true':'false'
			)
	);

	validate_store_request_vars($filters, 'sess_gsh');

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
		$('#clusterid, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#unused').click(function() {
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
		strURL  = 'grid_shared.php?header=false'
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_shared.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}
	</script>
	<?php

	html_start_box(__('Batch Shared Resources Filters', 'grid'), '100%', '', '3', 'center', '');
	sharedFilter();
	html_end_box();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$resources = grid_view_get_records($sql_where, true, $rows, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM grid_hosts_resources
		INNER JOIN grid_clusters
		ON grid_clusters.clusterid=grid_hosts_resources.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_display_array();

	/* generate page list */
	$nav = html_nav_bar('grid_shared.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Resources', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if ($resources!=false && cacti_sizeof($resources)) {
		foreach ($resources as $r) {
			if ($r['clusterid'] != '0') {
				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_clusters
					WHERE clusterid = ?',
					array($r['clusterid']));
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
						AND host_snmp_cache.field_name='resource_name'",
						array($cacti_host, $r['resource_name']));
				} else {
					$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.snmp_index = ?
						AND host_snmp_cache.field_name='resource_name'",
						array($r['resource_name']));
				}
				if (cacti_sizeof($local_graph_ids)) {
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

			$url_base = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1&page=1' .
				'&clusterid=' . $r['clusterid'];
			switch($r['resType']) {
			case '1':
				$res = __('Numeric', 'grid');
				$url_base .= '&resource_str=' . urlencode($r['resource_name'] . '>=0 && type=any');
				break;
			case '2':
				$res = __('Boolean', 'grid');
				$url_base .= '&resource_str=' . urlencode($r['resource_name'] . ' && type=any');
				break;
			case '3':
				$res = __('String', 'grid');
				$url_base = '';
				break;
			default:
				$res = __('Unknown', 'grid');
				$url_base = '';
				break;
			}

			?>
			<td class='nowrap'>
				<?php if (!empty($url_base)) {?>
					<a class='pic' href='<?php print html_escape($url_base . '&status=ACTIVE');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs by ResReq', 'grid');?>'></a>
					<?php if (isset($graph_select)) {?>
						<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Application Graphs', 'grid');?>'></a>
					<?php }
					}?>
			</td>
			<?php

			$url = '';
			if (!empty($url_base)) {
				$url = html_escape($url_base . '&status=RUNNING');
			}

			form_selectable_cell(filter_value($r['resource_name'], get_request_var('filter'), $url), $i, '', '', __esc($r['resource_name'], 'grid'));
			form_selectable_cell(((get_request_var('clusterid') == -1) ? __('N/A', 'grid') : $r['clustername']), $i);
			form_selectable_cell($res, $i);

			if ($r['resType'] == '1') {
				if($r['totalValue'] == '-'){
					form_selectable_cell('-', $i, '', 'right');
				}else{
					form_selectable_cell($r['totalValue'] == round($r['totalValue'],0) ? number_format_i18n(round($r['totalValue'])) : number_format_i18n($r['totalValue'],1), $i, '', 'right');
				}
				if($r['reservedValue'] == '-'){
					form_selectable_cell('-', $i, '', 'right');
				}else{
					form_selectable_cell($r['reservedValue'] == round($r['reservedValue'],0) ? number_format_i18n(round($r['reservedValue'])) : number_format_i18n($r['reservedValue'],1), $i, '', 'right');
				}
				if($r['value'] == '-'){
					form_selectable_cell('-', $i, '', 'right');
				}else{
					form_selectable_cell($r['value'] == round($r['value'],0) ? number_format_i18n(round($r['value'])) : number_format_i18n($r['value'],1), $i, '', 'right');
				}
			} else {
				form_selectable_cell($r['totalValue'], $i, '', 'right');
				form_selectable_cell($r['reservedValue'], $i, '', 'right');
				form_selectable_cell($r['value'], $i, '', 'right');
			}

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Shared Resource Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);


	if (cacti_sizeof($resources)) {
		print $nav;
	}

	bottom_footer();
}

function build_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' =>__('Action', 'grid')
		),
		'resource_name' => array(
			'display' =>__('Resource Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername'=> array(
			'display' =>__('Cluster Name', 'grid'),
			'sort'    => 'ASC'
		),
		'resType' => array(
			'display' =>__('Resource Type', 'grid'),
			'sort'    => 'DESC'
		),
		'totalValue' => array(
			'display' =>__('Available Value', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'reservedValue' => array(
			'display' =>__('Reserved Value', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'value' => array(
			'display' =>__('Total Value', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return $display_text;
}
