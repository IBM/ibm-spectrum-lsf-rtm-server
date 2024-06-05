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

$title = __('IBM Spectrum LSF RTM - Guaranteed Resource Pool', 'grid');

view_gsla();

function view_gsla() {
	global $config;

	if (!isset_request_var('currenttab')) {
		set_request_var('currenttab', 'brespool');
	}

	/* set the default tab */
	load_current_session_value('tab', 'sess_grid_view_bresources_tab', 'brespool');

	$current_tab = get_request_var('tab');

	if ($current_tab == 'brespool') {
		grid_view_bresources();
	} else {
		grid_view_bsla();
	}

	bottom_footer();
}

function draw_tabs() {
	global $config;

	$tabs_gridbrespool = array(
		'brespool' => __('Resource Pools', 'grid'),
		'bsla'     => __('Service Classes', 'grid')
	);

	$current_tab = get_request_var('tab');

	general_header();

	/* draw the tabs */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";

	if (cacti_sizeof($tabs_gridbrespool)) {
		$i = 0;
		foreach (array_keys($tabs_gridbrespool) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == get_request_var('tab')) ? "class='pic selected'" : "class='pic '") . " href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_bresourcespool.php?' .
				'action=view' . $tab_short_name . '&tab=' . $tab_short_name) .
				"'>" . $tabs_gridbrespool[$tab_short_name] . '</a></li>';

			$i++;
		}
	}
	print '</ul></nav></div>';
	print '</tr></table>';
}

function grid_view_get_bresources_records(&$sql_where, &$sql_groupby, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	global $config;

	$sql_where = '';

	/* user id sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . '(gp.clusterid=?)';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . 'gp.guar_used>0';
	}

	/* filter sql where */
	if (strlen(get_request_var('filter'))) {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . 'gp.name LIKE ?';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	/* status sql where */
	if (get_request_var('status') != '-1') {
		$sql_where  .= (!strlen($sql_where) ? 'WHERE ':' AND ') . 'gp.status LIKE ?';
		$sql_params[] = '%'. get_request_var('status') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT  gc.clusterid, gc.clustername, gp.name, gp.poolType, gp.rsrcName,
		gp.status, gp.res_select, gp.policies, gp.loan_duration, gp.retain,
		gp.total, gp.free, gp.guar_config, gp.guar_used, gp.runJobs, gp.runSlots, gp.pendJobs, gp.pendSlots
		FROM grid_clusters AS gc
		INNER JOIN grid_guarantee_pool AS gp
		ON gc.clusterid=gp.clusterid
		$sql_where
		$sql_groupby
		$sql_order ";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	$rows = db_fetch_assoc_prepared($sql_query, $sql_params);
	return $rows;
}

function guarPoolFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
<tr class='odd'>
    <td>
        <form id='form_grid' action='grid_bresourcespool.php'>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Cluster', 'grid');?>
                    </td>
                    <td>
                        <select id='clusterid'>
                            <option value='0' <?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <?php
							$clusterids = db_fetch_assoc('SELECT DISTINCT clusterid FROM grid_guarantee_pool');
							if (cacti_sizeof($clusterids)) {
								$clusterids = array_rekey($clusterids, 'clusterid', 'clusterid');
								$clusters = grid_get_clusterlist(false, $clusterids);

								if (cacti_sizeof($clusters)) {
									foreach ($clusters as $cluster) {
										print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
									}
								}
							}
							?>
                        </select>
                    </td>
                    <td>
                        <?php print __('Status', 'grid');?>
                    </td>
                    <td>
                        <select id="status">
                            <option value='-1' <?php if (get_request_var('status') == '-1') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <option value='ok' <?php if (get_request_var('status') == 'ok') {?> selected<?php }?>>
                                <?php print __('Ok', 'grid');?></option>
                            <option value='overcommitted' <?php if (get_request_var('status') == 'overcommitted') {?>
                                selected<?php }?>><?php print __('Over Committed', 'grid');?></option>
                            <option value='close_loans' <?php if (get_request_var('status') == 'close_loans') {?>
                                selected<?php }?>><?php print __('Close Loans', 'grid');?></option>
                            <option value='unknown' <?php if (get_request_var('status') == 'unknown') {?>
                                selected<?php }?>><?php print __('Unknown', 'grid');?></option>

                        </select>
                    </td>
                    <td>
                        <span>
                            <input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>'
                                title='<?php print __esc('Search', 'grid');?>'>
                            <input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>'
                                title='<?php print __esc('Clear Filters', 'grid');?>'>
                        </span>
                    </td>
                </tr>
            </table>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Refresh', 'grid');?>
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
                </tr>
            </table>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Search', 'grid');?>
                    </td>
                    <td>
                        <input type='text' id='filter' size='30'
                            value='<?php print html_escape_request_var('filter');?>'>
                    </td>
                    <td>
                        <input type='checkbox' id='unused'
                            <?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked="true"';?>>
                    </td>
                    <td>
                        <label for='unused'><?php print __('Include Unused Resource Pools', 'grid');?></label>
                    </td>
                </tr>
            </table>
            <input type='hidden' id='tab' value='<?php print html_escape_request_var('tab');?>'>
            <input type='hidden' id='page' value='1'>
        </form>
    </td>
</tr>
<?php
}

function grid_view_bresources() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

	set_request_var('action', "viewbrespool");

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option("grid_records")
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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'status' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_grid_view_bresources');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	grid_set_minimum_page_refresh();

	draw_tabs();

	?>
<script type="text/javascript">
<!--
function applyBResourcesFilterChange() {
    strURL = 'grid_bresourcespool.php?header=false&clusterid=' + $('#clusterid').val();
    strURL += '&status=' + $('#status').val();
    strURL += '&refresh=' + $('#refresh').val();
    strURL += '&rows=' + $('#rows').val();
    strURL += '&filter=' + $('#filter').val();
    strURL += '&unused=' + $('#unused').is(':checked');
    strURL += '&tab=' + $('#tab').val();
    loadPageNoHeader(strURL);
}

function clearFilter() {
    strURL = 'grid_bresourcespool.php?header=false&clear=true';
    strURL += '&tab=' + $('#tab').val();
    loadPageNoHeader(strURL);
}

$(function() {
    $('#form_grid').submit(function(event) {
        event.preventDefault();
        applyBResourcesFilterChange();
    });

    $('#clusterid, #status, #unused, #filter, #refresh, #rows').change(function() {
        applyBResourcesFilterChange();
    });

    $('#clear').click(function() {
        clearFilter();
    });
});
-->
</script>
<?php

	html_start_box(__('Batch Guaranteed Resource Pool Filters [ Last Refresh : %s ]', date('g:i:s a', time()), 'grid'), '100%', '', '3', 'center', '');
	guarPoolFilter();
	html_end_box();

	$sql_where = "";
	$sql_groupby = "GROUP BY name,gp.clusterid";

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option("grid_records");
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$bresources_results = grid_view_get_bresources_records($sql_where, $sql_groupby, true, $rows, $sql_params);

	html_start_box("", '100%', '', "3", "center", "");

	$total_rows_sql = "SELECT COUNT(*) as TotalHosts
		FROM (SELECT gc.clusterid FROM grid_guarantee_pool AS gp
		INNER JOIN grid_clusters AS gc
		ON gc.clusterid=gp.clusterid
		$sql_where
		$sql_groupby) AS temptable";

	//print $total_rows_sql;

	$total_rows = db_fetch_cell_prepared($total_rows_sql, $sql_params);

	$display_text = array(
		'nosort' => array(
			'display' => __('Actions'),
			'sort' => ''
		),
		'name' => array(
			'display' => __('Resource Pool'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'poolType' => array(
			'display' => __('Type'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'status' => array(
			'display' => __('Load/Batch'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'res_select' => array(
			'display' => __('Res Req'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'nosort1' => array(
			'display' => __('Duration/Retain'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'guar_config' => array(
			'display' => __('Guarantee'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'total' => array(
			'display' => __('Total'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'free' => array(
			'display' => __('Free'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'guar_used' => array(
			'display' => __('Allocated'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'runJobs' => array(
			'display' => __('Run Jobs'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'runSlots' => array(
			'display' => __('Run Slots'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pendJobs' => array(
			'display' => __('Pend Jobs'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pendSlots' => array(
			'display' => __('Pend Slots'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('grid_bresourcespool.php?action=viewbrespool', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Pools'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;
	if (cacti_sizeof($bresources_results) > 0) {
		foreach ($bresources_results as $bresources) {
			$poolstats = explode(" ",$bresources["status"]);

			$cacti_host = db_fetch_cell_prepared("SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid=?", array($bresources["clusterid"]));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
					AND (host_snmp_cache.host_id=graph_local.host_id)
					WHERE (graph_local.host_id=?)
					AND (graph_local.snmp_index like ? OR graph_local.snmp_index=?)
					AND (host_snmp_cache.field_name IN ('gridGsla', 'gridGResPool'))",
					array($cacti_host, $bresources["name"] . "/%", $bresources["name"]));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = "page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=";

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph["id"] . "%2C";
					}
				} else {
					unset($graph_select);
				}
			} else {
				unset($graph_select);
			}

			form_alternate_row();
			?>
<td nowrap style='width:1%;white-space:nowrap;'>
    <?php if (isset($graph_select)) {?>
    <a class='pic' href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img
            src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt=''
            title='View Guarantee Resource Pool Graphs'></a>
    <?php }?>
</td>
<?php
				$url = html_escape($config['url_path']. 'plugins/grid/grid_bresourcespool.php' .
					'?tab=bsla&poolName=' . $bresources['name'] .
				    '&reset=1&clusterid=' . $bresources['clusterid']. '&unused='. get_request_var('unused'));

				form_selectable_cell(filter_value($bresources['name'], get_request_var('filter'), $url), '');
				form_selectable_cell($bresources['clustername'], '');
			?>
<td><?php
				print ($bresources['poolType'] != 'unknown' ? $bresources['poolType']:'package');
				if ($bresources['poolType'] == 'package' || $bresources['poolType'] == 'resource' || $bresources['poolType'] == 'unknown') {
					print '[' . $bresources['rsrcName'] . ']';
				}
				?>
</td>
<td><?php print $poolstats[0]; ?></td>
<td><?php print $bresources['res_select'];?></td>
<td align='right'><?php print ($bresources['loan_duration']>0 ? $bresources['loan_duration']:'-') . ' / ';?>
    <?php
				if ($bresources['policies'] & GUAR_RESOURCE_POOL_POLICIES_RETAIN_PERCENT) {
					print $bresources['retain'] . '%';
				} else {
					print ($bresources['retain']>0 ? $bresources['retain']:'-');
				}
			?>
</td>
<td align='right'><?php print number_format($bresources['guar_config']);?></td>
<td align='right'><?php print number_format($bresources['total']);?></td>
<td align='right'><?php print number_format($bresources['free']);?></td>
<td align='right'><?php print number_format($bresources['guar_used']);?></td>
<td align='right'><?php print number_format($bresources['runJobs']);?></td>
<td align='right'><?php print number_format($bresources['runSlots']);?></td>
<td align='right'><?php print number_format($bresources['pendJobs']);?></td>
<td align='right'><?php print number_format($bresources['pendSlots']);?></td>
</tr>
<?php
		}

		html_end_box(false);
		/* put the nav bar on the bottom as well */
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No Guaranteed Resource Pool Records Found</em></td></tr>";
		html_end_box(false);
	}
}

function guarSLAFilter($respools) {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
<tr class='odd'>
    <td>
        <form id='form_grid' action='grid_bresourcespool.php'>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Cluster', 'grid');?>
                    </td>
                    <td>
                        <select id='clusterid'>
                            <option value='0' <?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <?php
							$clusterids = db_fetch_assoc('SELECT DISTINCT clusterid FROM grid_service_class');
							if (cacti_sizeof($clusterids)) {
								$clusterids = array_rekey($clusterids, 'clusterid', 'clusterid');
								$clusters = grid_get_clusterlist(false, $clusterids);

								if (cacti_sizeof($clusters)) {
									foreach ($clusters as $cluster) {
										print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
									}
								}
							}
							?>
                        </select>
                    </td>
                    <td>
                        <?php print __('ResPool', 'grid');?>&nbsp;
                    </td>
                    <td>
                        <select id='poolName'>
                            <option value='-1' <?php if (get_request_var('poolName') == '-1') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <?php
							if (cacti_sizeof($respools)) {
								foreach ($respools as $respool) {
									print '<option value="' . $respool['name'] .'"'; if (get_request_var('poolName') == $respool['name']) { print ' selected'; } print '>' . html_escape($respool['name']) . '</option>';
								}
							}
							?>
                        </select>
                    </td>
                    <td>
                        <?php print __('Pool Status', 'grid');?>
                    </td>
                    <td>
                        <select id='poolstatus'>
                            <option value='-1' <?php if (get_request_var('poolstatus') == '-1') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <option value='ok' <?php if (get_request_var('poolstatus') == 'ok') {?> selected<?php }?>>
                                <?php print __('Ok', 'grid');?></option>
                            <option value='overcommitted'
                                <?php if (get_request_var('poolstatus') == 'overcommitted') {?> selected<?php }?>>
                                <?php print __('Over Committed', 'grid');?></option>
                            <option value='close_loans' <?php if (get_request_var('poolstatus') == 'close_loans') {?>
                                selected<?php }?>><?php print __('Close Loans', 'grid');?></option>
                            <option value='unknown' <?php if (get_request_var('poolstatus') == 'unknown') {?>
                                selected<?php }?>><?php print __('Unknown', 'grid');?></option>
                        </select>
                    </td>
                    <td>
                        <span>
                            <input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>'
                                title='<?php print __esc('Search', 'grid');?>'>
                            <input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>'
                                title='<?php print __esc('Clear Filters', 'grid');?>'>
                        </span>
                    </td>
                </tr>
            </table>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Member', 'grid');?>
                    </td>
                    <td>
                        <select id='queue'>
                            <option value='-1' <?php if (get_request_var('queue') == '0') {?> selected<?php }?>>
                                <?php print __('All', 'grid');?></option>
                            <?php
							$sql_params = array();
							$queue_sql_where = 'WHERE acl_type=1';

							if (get_request_var('clusterid') != '0') {
								$queue_sql_where .= ' AND clusterid = ?';
								$sql_params[] = get_filter_request_var('clusterid');
							}

							$queues = db_fetch_assoc_prepared("SELECT DISTINCT acl_member
								FROM grid_service_class_access_control
								$queue_sql_where
								ORDER BY acl_member", $sql_params);

							if (cacti_sizeof($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . $queue["acl_member"] .'"'; if (get_request_var('queue') == $queue["acl_member"]) { print " selected"; } print ">" . $queue["acl_member"] . "</option>";
								}
							}
							?>
                        </select>
                    </td>
                    <td>
                        <?php print __('Refresh', 'grid');?>&nbsp;
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
                </tr>
            </table>
            <table class='filterTable'>
                <tr>
                    <td>
                        <?php print __('Search', 'grid');?>
                    </td>
                    <td>
                        <input type='text' id='filter' size='30'
                            value='<?php print html_escape_request_var('filter');?>'>
                    </td>
                    <td>
                        <input type='checkbox' id='unused'
                            <?php if (isset_request_var('unused') && ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on'))) print ' checked="true"';?>>
                    </td>
                    <td>
                        <label for='unused'><?php print __('Include Unused Resource Pools', 'grid');?></label>
                    </td>
                </tr>
            </table>
            <input type='hidden' id='tab' value='<?php print html_escape_request_var('tab');?>'>
            <input type='hidden' id='page' value='1'>
        </form>
    </td>
</tr>
<?php
}

function grid_view_get_bgsla_records(&$sql_where, &$sql_groupby, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	global $config;

	$sql_groupby = ' GROUP BY clusterid, poolName, name';

	$sql_params_acl = array();
	$sql_where = '';
	$sql_whereacl = $sql_where;

	/* user id sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . 'sc.clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
		$sql_whereacl .= (strlen($sql_whereacl) ? ' AND ':'WHERE ') . 'clusterid=?';
		$sql_params_acl[] = get_request_var('clusterid');
	}

	if (!isset_request_var('unused') || (get_request_var('unused') != 'true' && get_request_var('unused') != 'on')) {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . 'gpd.guarantee_used>0';
	}

	/* filter sql where */
	if (strlen(get_request_var('filter'))) {
		$sql_where .= (!strlen($sql_where) ? 'WHERE ':' AND ') . '(sc.name LIKE ? OR sc.consumer LIKE ?)';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	/* poolstatus sql where */
	if (get_request_var('poolstatus') != '-1') {
		$sql_where  .= (!strlen($sql_where) ? 'WHERE ':' AND ') . '(gp.status LIKE ?)';
		$sql_params[] = '%'. get_request_var('poolstatus') . '%';
	}

	/* poolName sql where */
	if (get_request_var('poolName') != '-1') {
		$sql_where  .= (!strlen($sql_where) ? 'WHERE ':' AND ') . '(gp.name=?)';
		$sql_params[] = get_request_var('poolName');
		$sql_whereacl .= (strlen($sql_whereacl) ? ' AND ':'WHERE ') . 'name IN (SELECT DISTINCT consumer FROM grid_guarantee_pool_distribution WHERE name=?)';
		$sql_params_acl[] = get_request_var('poolName');
	}

	/* queue sql where */
	if (get_request_var('queue') != '-1') {
		$sql_where  .= (!strlen($sql_where) ? 'WHERE ':' AND ') . '(ac.acl_member=?)';
		$sql_params[] = get_request_var('queue');
		$sql_whereacl .= (strlen($sql_whereacl) ? ' AND ':'WHERE ') . 'acl_member=?';
		$sql_params_acl[] = get_request_var('queue');
	}

	$sql_order = get_order_string();

	//This condition might be duplicate with SLA.name because sla job must match sla acl configuration.
	$acl_members = array_rekey(
		db_fetch_assoc_prepared("SELECT acl_member
			FROM grid_service_class_access_control AS ac
			$sql_whereacl", $sql_params_acl),
		'acl_member', 'acl_member'
	);

	if (cacti_sizeof($acl_members)) {
		$jobswhere = "WHERE gj.sla!='' AND gj.queue IN('" . implode("','", $acl_members) . "')" . (get_request_var('clusterid') > 0 ? ' AND gj.clusterid=' . get_request_var('clusterid'):'');
	} else {
		$jobswhere = "WHERE gj.sla!='' " . (get_request_var('clusterid') > 0 ? ' AND gj.clusterid=' . get_request_var('clusterid'):'');
	}

	$sql_query = "SELECT gc.clusterid, gc.clustername, gp.name AS poolName, gp.status AS poolstatus,
		sc.name, sc.auto_attach, gpd.runJobs, gpd.runSlots, gpd.pendJobs, gpd.pendSlots,
		gp.poolType, gp.rsrcName, MIN(ac.acl_member) AS queue,
		GROUP_CONCAT(ac.acl_member ORDER BY acl_member SEPARATOR ', ') AS acl_members, gpd.alloc,
		gpd.alloc_type, gpd.guarantee_config, gpd.guarantee_used, gpd.total_used
		FROM grid_clusters AS gc
		INNER JOIN grid_service_class AS sc
		ON gc.clusterid=sc.clusterid
		INNER JOIN grid_guarantee_pool_distribution AS gpd
		ON sc.name=gpd.consumer
		AND sc.clusterid=gpd.clusterid
		INNER JOIN grid_guarantee_pool AS gp
		ON gp.name=gpd.name
		AND gp.clusterid=gpd.clusterid
		INNER JOIN grid_service_class_access_control AS ac
		ON sc.name=ac.name
		AND ac.clusterid=sc.clusterid
		$sql_where
		$sql_groupby
		$sql_order ";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	$rows = db_fetch_assoc_prepared($sql_query, $sql_params);
	return $rows;
}

function grid_view_bsla() {
	global $title, $report, $grid_search_types, $grid_rows_selector;
	global $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

	set_request_var('action', 'viewbsla');

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('grid_records')
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
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'poolstatus' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'poolName' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_grid_view_bgsla');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	grid_set_minimum_page_refresh();

	draw_tabs();

	?>
<script type='text/javascript'>
function applyBGSLAFilterChange() {
    strURL = 'grid_bresourcespool.php?header=false&clusterid=' + $('#clusterid').val();
    strURL += '&poolstatus=' + $('#poolstatus').val();
    strURL += '&poolName=' + $('#poolName').val();
    strURL += '&queue=' + $('#queue').val();
    strURL += '&unused=' + $('#unused').is(':checked');
    strURL += '&refresh=' + $('#refresh').val();
    strURL += '&rows=' + $('#rows').val();
    strURL += '&filter=' + $('#filter').val();
    strURL += '&tab=' + $('#tab').val();
    loadPageNoHeader(strURL);
}

function clearFilter() {
    strURL = 'grid_bresourcespool.php?header=false&clear=true';
    strURL += '&tab=' + $('#tab').val();
    loadPageNoHeader(strURL);
}

$(function() {
    $('#form_grid').submit(function(event) {
        event.preventDefault();
        applyBGSLAFilterChange();
    });

    $('#clusterid, #poolstatus, #poolName, #queue, #unused, #filter, #refresh, #rows').change(function() {
        applyBGSLAFilterChange();
    });

    $('#clear').click(function() {
        clearFilter();
    });
});
</script>
<?php

	html_start_box(__('Batch Guaranteed Service Class Filters [ Last Refresh : %s ]', date('g:i:s a', time()), 'grid'), '100%', '', '3', 'center', '');

	if (get_request_var('clusterid') == '0') {
		$respools_sql_where = ' ';
	} else {
		$respools_sql_where = 'WHERE clusterid = ?';
		$sql_params[] = get_filter_request_var('clusterid');

	}

	$respools = db_fetch_assoc_prepared("SELECT DISTINCT name
		FROM grid_guarantee_pool
		$respools_sql_where
		ORDER BY name", $sql_params);

	guarSLAFilter($respools);

	html_end_box();

	$sql_where = '';
	$sql_groupby = '';
	$sql_params = array();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$bgslas_results = grid_view_get_bgsla_records($sql_where, $sql_groupby, true, $rows, $sql_params);

	html_start_box('', '100%', '', '3', 'center', '');

	$total_rows_sql = "SELECT COUNT(*) as TotalGSLA
		FROM (SELECT DISTINCT gc.clusterid, gp.name AS poolName, sc.name
			FROM grid_clusters AS gc
			INNER JOIN grid_service_class AS sc
			ON gc.clusterid=sc.clusterid
			INNER JOIN grid_guarantee_pool_distribution AS gpd
			ON sc.name=gpd.consumer
			AND sc.clusterid=gpd.clusterid
			INNER JOIN grid_guarantee_pool AS gp
			ON gp.name=gpd.name
			AND gp.clusterid=gpd.clusterid
			INNER JOIN grid_service_class_access_control AS ac
			ON sc.name=ac.name
			AND ac.clusterid=sc.clusterid
			$sql_where
			$sql_groupby
		) AS temptable";

	//print $total_rows_sql;

	$total_rows = db_fetch_cell_prepared($total_rows_sql, $sql_params);

	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'grid')
		),
		'name' => array(
			'display' => __('SLA', 'grid'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'poolstatus' => array(
			'display' => __('Pool Status', 'grid'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'auto_attach' => array(
			'display' => __('Auto Attach', 'grid'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'acl_members' => array(
			'display' => __('ACL Members', 'grid'),
			'align' => 'left',
			'sort' => 'DESC'
		),
		'alloc' => array(
			'display' => __('Share', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'guarantee_config' => array(
			'display' => __('Guarantee', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'guarantee_used' => array(
			'display' => __('Allocated', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'total_used' => array(
			'display' => __('Total Used', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'runJobs' => array(
			'display' => __('Run Jobs', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'runSlots' => array(
			'display' => __('Run Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pendJobs' => array(
			'display' => __('Pend Jobs', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pendSlots' => array(
			'display' => __('Pend Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('grid_bresourcespool.php?action=viewbsla', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('SLAs', 'grid'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$i = 0;

	if (cacti_sizeof($bgslas_results)) {
		foreach ($bgslas_results as $bgsla) {
			$poolstats = explode(' ', $bgsla['poolstatus']);

			if ($bgsla['poolType'] == 'package' || $bgsla['poolType'] == 'resource' || $bgsla['poolType'] == 'unknown') {
				$parts = explode(':',$bgsla['rsrcName']);
				$slotspp = 1;
				$mempp = -1;
				foreach($parts as $p) {
					if (strpos($p, 'slots') !== false) {
						$pp = explode('=', $p);
						$slotspp = $pp[1];
					}

					if (strpos($p, 'mem') !== false) {
						$pp = explode('=', $p);
						$mempp = $pp[1];
					}
				}
			} else {
				$slotspp = 1;
				$mempp = -1;
			}

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid= ?',
				array($bgsla['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
					AND host_snmp_cache.host_id=graph_local.host_id
					WHERE graph_local.host_id = ?
					AND graph_local.snmp_index= ?
					AND host_snmp_cache.field_name='gridGsla'",
					array($cacti_host,  $bgsla['poolName'] . '/' . $bgsla['name']));

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

			if (isset($graph_select)) {
				$url = '<a class="pic" href="' . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . '"><img src="' . $config['url_path'] . 'plugins/grid/images/view_graphs.gif" alt="" title="' . __esc('View Guarantee SLA Graphs', 'grid') . '"></a>';
			} else {
				$url = '';
			}

			form_alternate_row();

			form_selectable_cell($url, $i, '20');
			form_selectable_cell(filter_value($bgsla['name'], get_request_var('filter')), $i);
			form_selectable_cell($poolstats[0], $i);
			form_selectable_cell($bgsla['auto_attach'] ? __('Yes', 'grid') : __('No', 'grid'), $i);
			form_selectable_cell($bgsla['queue'], $i, '', '', $bgsla['acl_members']);
			form_selectable_cell(number_format_i18n($bgsla['alloc']) . ($bgsla['alloc_type'] & GUAR_CONSUMER_SHARE_TYPE_PERCENT ? '%':''), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['guarantee_config']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['guarantee_used']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['total_used']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['runJobs']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['runSlots']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['pendJobs']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($bgsla['pendSlots']), $i, '', 'right');

			form_end_row();

			$i++;
		}

		html_end_box(false);

		if (cacti_sizeof($bgslas_results)) {
			print $nav;
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text) + 1) . '"><em>' . __('No Guaranteed Service Class Records Found', 'grid') . '</em></td></tr>';
		html_end_box(false);
	}
}
