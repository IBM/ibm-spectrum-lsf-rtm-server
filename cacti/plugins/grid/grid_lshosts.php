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
include_once($config['base_path'] . '/lib/rtm_functions.php');

$title = __('IBM Spectrum LSF RTM - Host Information', 'grid');

grid_view_hosts();

function grid_view_get_hosts_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where = 'WHERE (gc.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	/* hostType sql where */
	$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(isServer>'0')";

	/* hostType sql where */
	if (get_request_var('type') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(hostType=?)';
		$sql_params[] = get_request_var('type');
	}

	/* hostModel sql where */
	if (get_request_var('model') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(hostModel=?)';
		$sql_params[] = get_request_var('model');
	}

	/* resource sql where */
	$resoure_name = get_request_var('resource');
	if ($resoure_name == '-1') {
		/* Show all items */
	} else if ($resoure_name == '-2') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(resources='-')";
	} else {
		if('!' == substr($resoure_name, 0, 1)) {//exclusive resource
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(excl_resources LIKE ?)";
			$sql_params[] = '%'. substr($resoure_name, 1) . '%';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(resources LIKE ?)";
			$sql_params[] = '%'. $resoure_name . '%';
		}
	}

	/* execution host sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "((ghi.hostType LIKE ?) OR
			(ghi.host LIKE ?) OR
			(ghi.hostModel LIKE ?))";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_order = str_replace(
		array('`cpuFactor`', '`maxSwap`', '`maxTmp`', '`maxMem`', '`maxCpus`', '`nDisks`'),
		array('CAST(`cpuFactor` AS UNSIGNED)', 'CAST(`maxSwap` AS UNSIGNED)', 'CAST(`maxTmp` AS UNSIGNED)', 'CAST(`maxMem` AS UNSIGNED)', 'CAST(`maxCpus` AS UNSIGNED)', 'CAST(`nDisks` AS UNSIGNED)'),
		$sql_order
	);

	$sql_query = "SELECT *
		FROM (grid_clusters AS gc
		INNER JOIN grid_hostinfo AS ghi
		ON gc.clusterid = ghi.clusterid)
		$sql_where
		$sql_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function hostsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_lshosts.php'>
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
					<td>
						<?php print __('Resources', 'grid');?>
					</td>
					<td>
						<select id='resource'>
							<option value='-1'<?php if (get_request_var('resource') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<option value='-2'<?php if (get_request_var('resource') == '-2') {?> selected<?php }?>><?php print __('None', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$resources = db_fetch_assoc('SELECT DISTINCT resource_name,rtype
									FROM grid_hostresources
									ORDER BY resource_name');
							} else {
								$resources = db_fetch_assoc_prepared('SELECT DISTINCT resource_name,rtype
									FROM grid_hostresources
									WHERE clusterid = ?
									ORDER BY resource_name',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($resources)) {
							foreach ($resources as $resource) {
								if($resource['rtype'] == 1) { //exclusive resource
									$resource['resource_name'] = '!'. $resource['resource_name'];
								}
								print '<option value="' . $resource['resource_name'] .'"'; if (get_request_var('resource') == $resource['resource_name']) { print ' selected'; } print '>' . $resource['resource_name'] . '</option>';
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
				<tr>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc("SELECT DISTINCT hostType
									FROM grid_hostinfo AS ghi
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									ORDER BY hostType");
							} else {
								$types = db_fetch_assoc_prepared("SELECT DISTINCT hostType
									FROM grid_hostinfo AS ghi
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									AND clusterid = ?
									ORDER BY hostType",
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] .'"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc("SELECT DISTINCT hostModel
									FROM grid_hostinfo AS ghi
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									ORDER BY hostModel");
							} else {
								$models = db_fetch_assoc_prepared("SELECT DISTINCT hostModel
									FROM grid_hostinfo AS ghi
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									AND clusterid = ?
									ORDER BY hostModel",
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($models)) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
								}
							}
							?>
						</select>
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

function grid_view_hosts() {
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
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'resource' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'resource_sanitize_search_string')
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

	validate_store_request_vars($filters, 'sess_glsh');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	general_header();

	html_start_box(__('Host Information Filters', 'grid'), '100%', '', '3', 'center', '');
	hostsFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$hosts_results = grid_view_get_hosts_records($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL = 'grid_lshosts.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&resource=' + $('#resource').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&type=' + $('#type').val();
		strURL += '&model=' + $('#model').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_lshosts.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #resource, #type, #model, #filter').change(function() {
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
		FROM (grid_clusters AS gc
		INNER JOIN grid_hostinfo AS ghi
		ON gc.clusterid = ghi.clusterid)
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'nosort0'     => array(
			'display' => __('Actions', 'grid')
			),
		'host'        => array(
			'display' => __('Host Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'sort'    => 'ASC'
		),
		'hostType'    => array(
			'display' => __('Type', 'grid'),
			'tip'     => __('Auto-detected or user defined Type of the host as defined in lsf.cluster file.', 'grid'),
			'sort'    => 'ASC',
			'dbname'  => 'host_type'
		),
		'hostModel'   => array(
			'display' => __('Model', 'grid'),
			'tip'     => __('Auto-detected or user defined Model of the host as defined in lsf.cluster file.', 'grid'),
			'sort'    => 'ASC',
			'dbname'  => 'host_model'
		),
		'cpuFactor'   => array(
			'display' => __('CPU Factor', 'grid'),
			'align'   => 'right',
			'tip'     => __('Relative strength of the CPU model installed on the host. Generally, consisting with Bogo MIPs.', 'grid'),
			'sort'    => 'DESC'
		),
		'maxCpus'     => array(
			'display' => __('Max Slots', 'grid'),
			'align'   => 'right',
			'tip'     => __('Default maximum scheduling slots based upon the LSF configuration.  The total of either sockets, cores, or threads on the system.', 'grid'),
			'sort'    => 'DESC'
		),
		'maxMem'      => array(
			'display' => __('Max Mem', 'grid'),
			'align'   => 'right',
			'tip'     => __('Maximum physical memory seen by LSF.', 'grid'),
			'sort'    => 'DESC'
		),
		'maxSwap'     => array(
			'display' => __('Max Swap', 'grid'),
			'align'   => 'right',
			'tip'     => __('Maximum space available in the swap partition.', 'grid'),
			'sort'    => 'DESC'
		),
		'maxTmp'      => array(
			'display' => __('Max Temp', 'grid'),
			'align'   => 'right',
			'tip'     => __('Maximum space in /tmp in Linux or UNIX', 'grid'),
			'sort'    => 'DESC'
		),
		'nDisks'      => array(
			'display' => __('Total Disks', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'resources'   => array(
			'display' => __('Resources', 'grid'),
			'tip'     => __('Space delimited list of Boolean resources', 'grid'),
			'align'   => 'right'
		),
		'excl_resources'   => array(
			'display' => __('Exclusive Resources', 'grid'),
			'tip'     => __('Space delimited list of Boolean resources', 'grid'),
			'align'   => 'right'
		),
		'nProcs'      => array(
			'display' => __('Sockets', 'grid'),
			'tip'     => __('Total physical CPU sockets', 'grid'),
			'sort'    => 'DESC'
		),
		'cores'       => array(
			'display' => __('Cores', 'grid'),
			'align'   => 'right',
			'tip'     => __('Cores per physical socket', 'grid'),
			'sort'    => 'DESC'
		),
		'nThreads'    => array(
			'display' => __('Threads', 'grid'),
			'align'   => 'right',
			'tip'     => __('Threads per physical core', 'grid'),
			'sort'    => 'DESC'
		)
	);

	$display_text = form_process_visible_display_text($display_text);

	/* generate page list */
	$nav = html_nav_bar('grid_lshosts.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Hosts', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($hosts_results)) {
		foreach ($hosts_results as $host) {
			$host_id = db_fetch_cell_prepared('SELECT id
				FROM host
				WHERE clusterid = ?
				AND hostname = ?',
				array($host['clusterid'], $host['host']));

			if (!empty($host_id)) {
				$host_graphs = db_fetch_cell_prepared('SELECT count(*)
					FROM graph_local
					WHERE host_id = ?',
					array($host_id));
			} else {
				$host_graphs = 0;
			}

			form_alternate_row();

			?>
			<td class='nowrap' style='width:1%'>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $host['clusterid'] . '&exec_host=' . $host['host'] . '&status=ACTIVE&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bzen.php?action=zoom&reset=1&clusterid=' . $host['clusterid'] . '&exec_host=' . $host['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hjobdetail.gif' alt='' title='<?php print __esc('View Host Job Detail', 'grid');?>'></a>
				<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details WHERE hostname=?", array($host['host']))) {?>
				<a class='pic' href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&host=' . $host['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts', 'grid');?>'></a>
				<?php } if ($host_graphs > 0) {?><a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&graph_template_id=-1&rfilter=&host_id=' . $host_id);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Graphs', 'grid');?>'></a><?php }?>
				<?php api_plugin_hook_function('grid_bhost_action_insert', $host); ?>
			</td>
			<?php
			$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist&reset=1' .
				'&clusterid=' . $host['clusterid'] .
				'&exec_host=' . $host['host'] .
				'&status=RUNNING&page=1');

			form_selectable_cell_metadata('simple', 'host', $host['clusterid'], $host['host'], '', '', html_escape($host['host']), true, $url);
			form_selectable_cell($host['clustername'], $i);
			form_selectable_cell_visible(filter_value($host['hostType'], get_request_var('filter')), 'host_type');
			form_selectable_cell_visible(filter_value($host['hostModel'], get_request_var('filter')), 'host_model');
			form_selectable_cell(number_format_i18n($host['cpuFactor'],1), $i, '', 'right');
			form_selectable_cell($host['maxCpus'], $i, '', 'right');
			form_selectable_cell(display_memory($host['maxMem']), $i, '', 'right');
			form_selectable_cell(display_memory($host['maxSwap']), $i, '', 'right');
			form_selectable_cell(display_memory($host['maxTmp']), $i, '', 'right');
			form_selectable_cell($host['nDisks'], $i, '', 'right');
			form_selectable_cell($host['resources'], $i, '', 'right');
			$excl_resources = trim($host['excl_resources']);
			if(strlen($excl_resources) > 0 && $excl_resources != '-') {
				$excl_resources = str_replace(' ', ' !', $excl_resources);
				$excl_resources = '!' . $excl_resources;
			} else {
				$excl_resources = $host['excl_resources'];
			}
			form_selectable_cell($excl_resources, $i, '', 'right');
			if (stristr($host['hostType'], 'UNKNOWN') !== false) {
				form_selectable_cell('-', $i, '', 'right');
			} else {
				form_selectable_cell($host['nProcs'], $i, '', 'right');
			}
			if (stristr($host['hostType'], 'UNKNOWN') !== false) {
				form_selectable_cell('-', $i, '', 'right');
			} else {
				form_selectable_cell($host['cores'], $i, '', 'right');
			}
			if (stristr($host['hostType'], 'UNKNOWN') !== false) {
				form_selectable_cell('-', $i, '', 'right');
			} else {
				form_selectable_cell($host['nThreads'], $i, '', 'right');
			}

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Host Records Found', 'grid') . '</em></td></tr>';
	}
	html_end_box(false);

	if (cacti_sizeof($hosts_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}
