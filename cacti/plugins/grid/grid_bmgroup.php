<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

$title = __('IBM Spectrum LSF RTM - Host Group Information', 'grid');

grid_validate_bmgroup_variables();

switch (get_request_var('action')) {
	case 'ajax_rtm_hgroups':
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_bmgroup.php', 'hgroup', $sql_where);
		break;
	default:
		general_header();
		grid_view_hgroups();
		bottom_footer();
		break;
}

function grid_view_get_hgroup_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	/* clusterid sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'gc.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	/* host group sql where */
	if (get_request_var('hgroup') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'ghg.groupName=?';
		$sql_params[] = get_request_var('hgroup');
	}

	/* filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(ghg.host LIKE ? OR
			ghg.groupName LIKE ? OR
			ghi.hostModel LIKE ? OR
			ghi.hostType LIKE ? OR
			gc.clustername LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT gc.clusterid, gc.clustername,
		ghg.groupName, ghg.host,
		ghi.hostType, ghi.hostModel, ghi.cpuFactor, ghi.maxCpus, ghi.maxMem,
		ghi.maxSwap, ghi.maxTmp
		FROM grid_clusters AS gc
		INNER JOIN grid_hostgroups AS ghg
		ON gc.clusterid=ghg.clusterid
		INNER JOIN grid_hostinfo AS ghi
		ON ghg.clusterid=ghi.clusterid AND ghg.host=ghi.host
		$sql_where $sql_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function hostGroupFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_bmgroup.php'>
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
					<?php print html_autocomplete_filter('grid_bmgroup.php', __('Group', 'grid'), 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
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
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}


function grid_validate_bmgroup_variables() {
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
		'hgroup' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => '-1',
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

	validate_store_request_vars($filters, 'sess_gbmg');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function grid_view_hgroups() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $config;
	$sql_params = array();

	html_start_box(__('Host Group Information Filters', 'grid'), '100%', '', '3', 'center', '');
	hostGroupFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$hgroups_results = grid_view_get_hgroup_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bmgroup.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'grid_bmgroup.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #hgroup, #rows, #filter').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	$rows_query_string = "SELECT COUNT(*)
		FROM grid_clusters AS gc
		INNER JOIN grid_hostgroups AS ghg
		ON gc.clusterid=ghg.clusterid
		INNER JOIN grid_hostinfo AS ghi
		ON ghg.clusterid=ghi.clusterid
		AND ghg.host=ghi.host
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'groupName'   => array(
			'display' => __('Group Name', 'grid'),
			'sort' => 'ASC'),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'sort' => 'ASC'),
		'host'        => array(
			'display' => __('Host Name', 'grid'),
			'sort' => 'ASC'),
		'hostType'    => array(
			'display' => __('Host Type', 'grid'),
			'sort' => 'ASC'),
		'hostModel'   => array(
			'display' => __('Host Model', 'grid'),
			'sort' => 'ASC'),
		'cpuFactor'   => array(
			'display' => __('CPU Factor', 'grid'),
			'align'   => 'right',
			'sort' => 'ASC'),
		'maxCpus'     => array(
			'display' => __('Max CPUS', 'grid'),
			'align'   => 'right',
			'sort' => 'DESC'),
		'maxMem'      => array(
			'display' => __('Max Mem', 'grid'),
			'align'   => 'right',
			'sort' => 'DESC'),
		'maxSwap'     => array(
			'display' => __('Max Swap', 'grid'),
			'align'   => 'right',
			'sort' => 'DESC'),
		'maxTmp'      => array(
			'display' => __('Max Tmp', 'grid'),
			'align'   => 'right',
			'sort' => 'DESC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('grid_bmgroup.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Host Groups', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($hgroups_results)) {
		foreach ($hgroups_results as $hgroup) {
			form_alternate_row();

			form_selectable_cell_metadata('simple', 'host-group', $hgroup['clusterid'], $hgroup['groupName'], '', '', '', true);
			form_selectable_cell($hgroup['clustername'], $i);
			form_selectable_cell_metadata('simple', 'host', $hgroup['clusterid'], $hgroup['host'], '', '', '', true);

			form_selectable_cell(filter_value($hgroup['hostType'], get_request_var('filter')), $i);
			form_selectable_cell(filter_value($hgroup['hostModel'], get_request_var('filter')), $i);
			form_selectable_cell(round($hgroup['cpuFactor'],3), $i, '', 'right');
			form_selectable_cell(is_numeric($hgroup['maxCpus']) ? number_format_i18n($hgroup['maxCpus'], -1) : $hgroup['maxCpus'], $i, '', 'right');
			form_selectable_cell(display_memory($hgroup['maxMem'],3), $i, '', 'right');
			form_selectable_cell(display_memory($hgroup['maxSwap'],3), $i, '', 'right');
			form_selectable_cell(display_memory($hgroup['maxTmp'],3), $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Host Group Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($hgroups_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');
}
