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
include($config['library_path'] . '/rrd.php');
include($config['library_path'] . '/timespan_settings.php');
include($config['library_path'] . '/rtm_functions.php');
include($config['base_path'] . '/plugins/gridblstat/lib/functions.php');

$title = __('IBM Spectrum LSF RTM - License Scheduler Dashboard', 'gridblstat');

if (isset_request_var('action') && get_request_var('action') == 'ajaxsearch') {
	grid_blstat_ajax_search();
} elseif (isset_request_var('action') && get_request_var('action') == 'ajaxsave') {
	grid_blstat_ajax_save();
} else {
	set_request_var('action','summary');
	grid_view_db();
}

function build_feature_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'gridblstat')
		),
		'bfeature' => array(
			'display' => __('BLD Name', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('License Scheduler token name', 'gridblstat'),
		),
		'ffeature' => array(
			'display' => __('LM Name', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('Feature name in FLEXlm/RLM', 'gridblstat'),
		),
		'region' => array(
			'display' => __('Region', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('Region Name', 'gridblstat'),
		),
		'type' => array(
			'display' => __('Mode', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('Cluster mode or project mode set in License Scheduler', 'gridblstat'),
		),
		'maxavail' => array(
			'display' => __('LM Max', 'gridblstat'),
			'sort'    => 'ASC',
			'align'   => 'right',
			'tip'     => __('Max avail token', 'gridblstat'),
		),
		'inuse' => array(
			'display' => __('BLD Use', 'gridblstat'),
			'sort'    => 'ASC',
			'align'   => 'right',
			'tip'     => __('The number of licenses in use in License Scheduler', 'gridblstat'),
		),
		'flexuse' => array(
			'display' => __('LM Use', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('The number of licenses in use in FLEXlm/RLM', 'gridblstat'),
		),
		'queued' => array(
			'display' => __('LM Queued', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('The number of licenses queued in FLEXlm/RLM', 'gridblstat'),
		),
		'reserve' => array(
			'display' => __('BLD Reserve', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('The number of licenses reserved', 'gridblstat'),
		),
		'free' => array(
			'display' => __('BLD Free', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('The number of licenses has free', 'gridblstat'),
		),
		'demand' => array(
			'display' => __('BLD Demand', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('Numeric value indicating the number of tokens required', 'gridblstat'),
		),
		'others' => array(
			'display' => __('BLD Others', 'gridblstat'),
			'sort'    => 'DESC',
			'align'   => 'right',
			'tip'     => __('The number of licenses checked out by applications outside of License Scheduler', 'gridblstat'),
		)
	);

	return $display_text;
}

function build_distrib_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'gridblstat')
		),
		'service_domain' => array(
			'display' => __('Service Domain', 'gridblstat'),
			'sort'    => 'ASC'
		),
		'feature' => array(
			'display' => __('Feature Name', 'gridblstat'),
			'sort'    => 'ASC'
		),
		'region' => array(
			'display' => __('Region', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('Region Name', 'gridblstat')
		),
		'total' => array(
			'display' => __('Total', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'ASC'
		),
		'lsf_use' => array(
			'display' => __('LSF Use', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'ASC',
			'tip'     => __('The total number of licenses in use by License Scheduler projects in the LSF workload', 'gridblstat')
		),
		'lsf_deserve' => array(
			'display' => __('LSF Deserve', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The total number of licenses assigned to License Scheduler projects in the LSF workload', 'gridblstat')
		),
		'lsf_free' => array(
			'display' => __('LSF Free', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The total number of free licenses available to License Scheduler projects in the LSF workload', 'gridblstat')
		),
		'non_lsf_use' => array(
			'display' => __('Non-LSF Use', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The total number of licenses in use by projects in the non-LSF workload', 'gridblstat')
		),
		'non_lsf_deserve' => array(
			'display' => __('Non-LSF Deserve', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The total number of licenses assigned to projects in the non-LSF workload', 'gridblstat')
		),
		'non_lsf_free' => array(
			'display' => __('Non-LSF Free', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The total number of free licenses available to projects in the non-LSF workload', 'gridblstat')
		)
	);

	return $display_text;
}

function build_project_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'gridblstat')
		),
		'project' => array(
			'display' => __('Project', 'gridblstat'),
			'align'   => 'left',
			'sort'    => 'ASC'
		),
		'feature' => array(
			'display' => __('Feature Name', 'gridblstat'),
			'sort'    => 'ASC'
		),
		'region' => array(
			'display' => __('Region', 'gridblstat'),
			'sort'    => 'ASC',
			'tip'     => __('Region Name', 'gridblstat')
		),
		'service_domain' => array(
			'display' => __('Service Domain', 'gridblstat'),
			'sort'    => 'ASC'
		),
		'share' => array(
			'display' => __('Share', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'ASC',
			'tip'     => __('The shares assigned to the hierarchical group member projects', 'gridblstat')
		),
		'own' => array(
			'display' => __('Own', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('Numeric value indicating the number of tokens owned by each project', 'gridblstat')
		),
		'inuse' => array(
			'display' => __('In Use', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The number of licenses in use by the license project in License Scheduler', 'gridblstat')
		),
		'reserve' => array(
			'display' => __('Reserve', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The number of licenses reserved for the license project', 'gridblstat')
		),
		'free' => array(
			'display' => __('Free', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('The number of licenses the license project has free', 'gridblstat')
		),
		'demand' => array(
			'display' => __('Demand', 'gridblstat'),
			'align'   => 'right',
			'sort'    => 'DESC',
			'tip'     => __('Numeric value indicating the number of tokens required by each project', 'gridblstat')
		)
	);

	return $display_text;
}

function grid_view_db() {
	global $title, $grid_search_types, $grid_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	$sql_params = array();

	/* ================= input validation ================= */
	get_filter_request_var('refresh');
	get_filter_request_var('lsid');
	input_validate_input_regex(get_nfilter_request_var('tab'), '^([a-zA-Z0-9_]+)$');
	/* ==================================================== */

	if (isset_request_var('tab') && get_request_var('tab') != 'graphs') {
		load_current_session_value('refresh', 'sess_gbs_db_refresh', '-1');
	} else {
		set_request_var('refresh', read_config_option('poller_interval'));
	}

	grid_blstat_set_minimum_page_refresh();

	general_header();

	?>
	<script type='text/javascript'>
		shift = 1;
		$(function() {
			$('#feature').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=feature',
				minLength: 0,
				select: function(event, ui) {
					$('#feature').val(ui.item.value);
					applyFilter();
				}
			});
			$('#ls_user').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=user',
				minLength: 0,
				select: function(event, ui) {
					$('#ls_user').val(ui.item.value);
					applyFilter();
				}
			});
			$('#region').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=region',
				minLength: 0,
				select: function(event, ui) {
					$('#region').val(ui.item.value);
					applyFilter();
				}
			});
			$('#ls_host').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=host',
				minLength: 0,
				select: function(event, ui) {
					$('#ls_host').val(ui.item.value);
					applyFilter();
				}
			});
			$('#sd').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=sd',
				minLength: 0,
				select: function(event, ui) {
					$('#sd').val(ui.item.value);
					applyFilter();
				}
			});
			$('#project').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=project',
				minLength: 0,
				select: function(event, ui) {
					$('#project').val(ui.item.value);
					applyFilter();
				}
			});
			$('#resource').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=resource',
				minLength: 0,
				select: function(event, ui) {
					$('#resource').val(ui.item.value);
					applyFilter();
				}
			});

			if (typeof graphs != 'undefined') {
				var id_number = 0;
				for(var i in graphs) {
					id_number++;
					drawChart(id_number, graphs[i]);
				}

				$('.stripe:odd').addClass('odd');
				$('.graphimage').each(function() {
					$(this).attr('src', $(this).attr('src')+'&f=,xxx'); //Fix atob error in zoomFunction_init()
				});
			}
		});

		function drawCharts() {
			shift++;

			type=$('#gtype').val();

			$('#graphs').empty();
			var id_number = 0;
			for(var i in graphs) {
				id_number++;
				switch(graphs[i].type) {
				case 'cdemand':
				case 'pdemand':
					if (type == 2 || type == 0) {
						drawChart(id_number, graphs[i],shift);
					}
					break;
				default:
					if (type < 2) {
						drawChart(id_number, graphs[i],shift);
					}
					break;
				}
			}

			$('.stripe:odd').addClass('odd');
		}

		function drawChart(id_number, graph,shift) {
			ts = $('#predefined_timespan').val();

			if (navigator.userAgent.match(/msie/i)) {
				var D = (document.body.clientWidth)? document.body: document.documentElement;
				var width  = D.clientWidth - 250;
			} else {
				var width=window.innerWidth - 250;
			}
			width  = 600;
			height = 150;

			url = 'grid_blstat_image.php?feature='+graph.feature+
				'&pc='+graph.pc+
				'&type='+graph.type+
				'&lsid='+graph.lsid+
				'&region='+graph.region+
				'&height='+height+
				'&width='+width+
				'&timespan='+ts;

			if (shift) {
				url = url +
				'&shift='+shift;
			}

			//alert('url:'+url);
			$('#graphs').append('<tr class="tableRowGraph"></tr><tr class="stripe"><td width="25%"></td><td align="center" width="50%"><img image_width="100" id="graph_' + id_number + '" src="'+url+'" class="graphimage"></td><td width="25%" id="dd" class="noprint graphDrillDown"></td></tr>');
		}
	</script>
	<?php

	draw_ls_tabs();

	if (get_request_var('tab') == 'summary') {
    	/* ================= input validation and session storage ================= */
	    $filters = array(
			'rows' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'refresh' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'page' => array(
				'filter' => FILTER_VALIDATE_INT,
				'default' => '1'
				),
			'feature' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'region' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sort_column' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'inuse',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sort_direction' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'DESC',
				'options' => array('options' => 'sanitize_search_string')
				),
			'inuse' => array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' => array('options' => array('regexp' => '(true|false)')),
				'pageset' => true,
				'default' => get_user_setting('inuse', 'true')
			)
		);

		validate_store_request_vars($filters, 'sess_gbs_db');
		/* ================= input validation ================= */

		$sql_where = ' AND bls.present = 1';
		if (get_request_var('feature') != '') {
			$sql_where .= ' AND a.feature LIKE ?';
			$sql_params[] = "%" . str_replace('_','\_',get_request_var('feature')) . "%";
		}

		if (get_request_var('region') != '') {
			$sql_where .= ' AND a.region LIKE ?';
			$sql_params[] = '%'. get_request_var('region') . '%';
		}

		if (get_request_var('rows') == '-1') {
			$rows = read_config_option('num_rows_table');
		} else {
			$rows = get_request_var('rows');
		}

		if (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on') {
			$sql_where .= ' AND (a.inuse > 0 OR a.demand > 0 OR a.others > 0 OR a.`over` > 0)';
			$sql_having = 'HAVING flexuse > 0 OR queued > 0';
		} else {
			$sql_having = '';
		}

		$sql_order = get_order_string();
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

		/* GHE#584:implode lic_feature in LS dashboard to ensure one row per LS feature on dashbaord. */
		$featsql = "SELECT a.region, a.lsid, a.feature AS bfeature, GROUP_CONCAT(DISTINCT fm.lic_feature) AS ffeature,
			bls.service_domain, service_id, a.type,
			a.inuse, a.`over`, a.reserve, a.free, a.demand, a.others,
			SUM(feature_max_licenses) AS maxavail,
			SUM(feature_inuse_licenses) AS flexuse,
			SUM(feature_queued) AS queued
			FROM grid_blstat AS bls
			INNER JOIN grid_blstat_feature_map AS fm
			ON bls.feature=fm.bld_feature AND bls.lsid=fm.lsid
			INNER JOIN grid_blstat_service_domains AS sd
			ON bls.service_domain=sd.service_domain
			AND bls.lsid=sd.lsid
			RIGHT JOIN (
				SELECT gbc.lsid, gbc.region,recordset.feature,`type`, inuse, `over`, `reserve`, free, demand, others from grid_blstat_collectors AS gbc
				INNER JOIN (
					SELECT bc.lsid, bc.feature, CONCAT('Project', IF(`type`=1, ' (FD)', '')) AS `type`,
					SUM(inuse) AS inuse,
					SUM(`over`) AS `over`,
					SUM(`reserve`) AS `reserve`,
					SUM(free) AS free,
					SUM(need+demand) AS demand,
					others
					FROM grid_blstat_cluster_use AS bc
					INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others FROM grid_blstat WHERE present = 1 GROUP BY lsid, feature) AS bs
					ON bc.feature=bs.feature
					AND bc.lsid=bs.lsid WHERE bc.present = 1
					GROUP BY bc.lsid, feature, others
					UNION
					SELECT bc.lsid, bc.feature, 'Cluster' AS `type`,
					SUM(inuse) AS inuse,
					SUM(`over`) AS `over`,
					SUM(reserve) AS reserve,
					SUM(free) AS free,
					SUM(demand) AS demand,
					others
					FROM grid_blstat_clusters AS bc
					INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others FROM grid_blstat WHERE present = 1 GROUP BY lsid, feature) AS bs
					ON bc.feature=bs.feature
					AND bc.lsid=bs.lsid WHERE bc.present = 1
					GROUP BY bc.lsid, feature, others
				) AS recordset ON gbc.lsid=recordset.lsid
			) AS a ON fm.bld_feature=a.feature AND fm.lsid=a.lsid
			LEFT JOIN lic_services_feature_use AS fu
			ON fu.feature_name=fm.lic_feature AND fu.service_id=sd.lic_id
			WHERE (fu.service_id IN(SELECT DISTINCT lic_id FROM grid_blstat_service_domains) OR fu.service_id IS NULL)
			$sql_where
			GROUP BY bls.lsid, bfeature, `type`, others
			$sql_having
			$sql_order
			$sql_limit";

		//print $featsql;

		$features = db_fetch_assoc_prepared($featsql, $sql_params);

		$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM (
				SELECT a.region, a.lsid, a.feature AS bfeature, fm.lic_feature AS ffeature, lic_feature, bls.service_domain, service_id, a.type,
				a.inuse, a.`over`, a.`reserve`, a.free, a.demand, a.others,
				SUM(feature_max_licenses) AS maxavail,
				SUM(feature_inuse_licenses) AS flexuse,
				SUM(feature_queued) AS queued
				FROM grid_blstat AS bls
				INNER JOIN grid_blstat_feature_map AS fm
				ON bls.feature=fm.bld_feature AND bls.lsid=fm.lsid
				INNER JOIN grid_blstat_service_domains AS sd
				ON bls.service_domain=sd.service_domain
				AND bls.lsid=sd.lsid
				RIGHT JOIN
				(SELECT gbc.lsid, gbc.region, recordset.feature, `type`, inuse, `over`, `reserve`, free, demand, others from grid_blstat_collectors AS gbc
                INNER JOIN
                (SELECT bc.lsid, bc.feature, CONCAT('Project', IF(`type`=1, ' (FD)','')) AS `type`,
					SUM(inuse) AS inuse,
					SUM(`over`) AS `over`,
					SUM(reserve) AS reserve,
					SUM(free) AS free,
					SUM(need+demand) AS demand,
					others
					FROM grid_blstat_cluster_use AS bc
					INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others FROM grid_blstat WHERE present = 1 GROUP BY lsid, feature) AS bs
					ON bc.feature=bs.feature
					AND bc.lsid=bs.lsid WHERE bc.present = 1
					GROUP BY bc.lsid, feature, others
					UNION
					SELECT bc.lsid, bc.feature, 'Cluster' AS type,
					SUM(inuse) AS inuse,
					SUM(`over`) AS `over`,
					SUM(reserve) AS reserve,
					SUM(free) AS free,
					SUM(demand) AS demand,
					others
					FROM grid_blstat_clusters AS bc
					INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others FROM grid_blstat WHERE present = 1 GROUP BY lsid, feature) AS bs
					ON bc.feature=bs.feature
					AND bc.lsid=bs.lsid WHERE bc.present = 1
					GROUP BY bc.lsid, feature, others
				) AS recordset ON gbc.lsid=recordset.lsid
                ) AS a ON fm.bld_feature=a.feature AND fm.lsid=a.lsid
				LEFT JOIN lic_services_feature_use AS fu
				ON fu.feature_name=fm.lic_feature AND fu.service_id=sd.lic_id
				WHERE (fu.service_id IN(SELECT DISTINCT lic_id FROM grid_blstat_service_domains) OR fu.service_id IS NULL)
				$sql_where
				GROUP BY bls.lsid, bfeature, `type`, others
				$sql_having
			) AS fs", $sql_params);

		html_start_box(__('Summary Filter %s', grid_blstat_header(), 'gridblstat'), '100%', '', '3', 'center', '');
		grid_summary_filter();
		html_end_box(true);

		$i = 0;
		$na = false;

		$display_text = build_feature_display_array();

		$nav = html_nav_bar('grid_lsdashboard.php?action=summary&tab=summary', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Summary Data', 'gridblstat'), 'page', 'main');

		print $nav;

		/* draw the feature dashboard */
		html_start_box('', '100%', '', '3', 'center', '');

		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

		if (cacti_sizeof($features)) {
			foreach($features as $row) {
				$graph_select = false;

				if (get_data_query_id('51b53ceb3f70861c47fd169dae72a3bf')) {
					$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
						FROM grid_blstat_collectors
						WHERE lsid = ?',
						array($row['lsid']));

					if (!empty($cacti_host)) {
						$local_graph_ids = db_fetch_assoc_prepared("SELECT gl.id
							FROM host_snmp_cache AS hsc
							INNER JOIN graph_local AS gl
							ON gl.snmp_query_id = hsc.snmp_query_id
							AND hsc.host_id = gl.host_id
							WHERE gl.host_id = ?
							AND gl.snmp_index LIKE ?
							AND (gl.snmp_query_id IN (
								SELECT id
								FROM snmp_query
								WHERE hash
								IN ('51b53ceb3f70861c47fd169dae72a3bf',
								'e1458d6832066ae20b19f7dd6ba62002',
								'52beb3a749e29867b2d5d92f1a487e09',
								'276d7b3235d9fe1071346abfcbead663')
								)
							) LIMIT 1",
							array($cacti_host, $row['lsid']. '|' . $row['bfeature']. '%'));

						if (cacti_sizeof($local_graph_ids)) {
							$graph_select = true;
						}
					}
				}

				$fparts = explode('@', $row['bfeature']);
				$ffirst = $fparts[0];

				form_alternate_row();

				if ($row['flexuse'] == '') {
					$na = true;
				}

				$actions_url = "<a href='" . html_escape('grid_lsdashboard.php?action=features&tab=features&lsid=' . $row['lsid'] . '&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_featdb.gif' title='" . __esc('View Feature Dashboard', 'gridblstat') . "'></a>\n";

				if ($row['type'] == 'Project' || $row['type'] == 'Project (FD)') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=clusters&tab=clusters&filter=&project=&cluster=-1&lsid=' . $row['lsid'] . '&mode=1&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_cluster.gif' title='" . __esc('View Cluster Use', 'gridblstat') . "'></a>\n";
				} else if ($row['type'] == 'Cluster') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=clusters&tab=clusters&filter=&project=&cluster=-1&lsid=' . $row['lsid'] . '&mode=2&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_cluster.gif' title='" . __esc('View Cluster Use', 'gridblstat') . "'></a>\n";
				}

				if ($row['type'] != 'Cluster') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=projects&tab=projects&lsid=' . $row['lsid'] . '&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&sd=' . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_project.gif' title='" . __esc('View Project Use', 'gridblstat') . "'></a>\n";
				}

				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=distribution&tab=distribution&lsid=' . $row['lsid'] . '&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&sd=' . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_distribution.gif' title='" . __esc('View Distribution', 'gridblstat') . "'></a>\n";
				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=checkouts&tab=checkouts&lsid=' . $row['lsid'] . '&ffeature=' . $row['ffeature'] . '&region=' . $row['region'] . '&sd=&lsf=-1&host=&user=&except=-1') . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_checkouts.gif' title='" . __esc('View Feature Checkouts', 'gridblstat') . "'></a>\n";

				if ($graph_select) {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=graphs&tab=graphs&query=1&lsid=' . $row['lsid'] . '&feature=' . $row['bfeature'] . '&region=' . $row['region'] . '&inuse=' . get_request_var('inuse')) . "&template=0&cluster=-1'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' title='" . __esc('View User Graphs', 'gridblstat') . "'></a>";
				}

				form_selectable_cell($actions_url, $i, '1%', 'left');

				form_selectable_cell($row['bfeature'], $i);
				form_selectable_cell($row['ffeature'], $i);
				form_selectable_cell($row['region'], $i);
				form_selectable_cell($row['type'], $i);
				form_selectable_cell($row['maxavail']=='' ? __('N/A', 'gridblstat'):number_format_i18n($row['maxavail']), $i, '', 'right');
				form_selectable_cell(filter_value(number_format_i18n($row['inuse']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&region=' . $row['region'] . '&sd=' . urlencode($row['service_domain']) . '&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');

				form_selectable_cell(filter_value($row['flexuse'] == '' ? __('N/A', 'gridblstat'):number_format_i18n($row['flexuse']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&region=' . $row['region'] . '&ffeature=' . $row['ffeature'] . '&sd=&lsf=-1&host=&user=&except=-1'), $i, '', 'right');

				form_selectable_cell($row['queued'] == '' ? __('N/A', 'gridblstat'):($row['queued'] != 'N/A' ? number_format_i18n($row['queued']):$row['queued']), $i, '', 'right');
				form_selectable_cell(filter_value(number_format_i18n($row['reserve']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&sd=UNKNOWN&region=' . $row['region'] . '&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');
				form_selectable_cell($row['free'] == '' ? '-':number_format_i18n($row['free']), $i, '', 'right');
				form_selectable_cell(filter_value($row['demand'] == '' ? '-':number_format_i18n($row['demand']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&sd=-3&region=' . $row['region'] . '&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');
				form_selectable_cell(filter_value(number_format($row['others']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&region=' . $row['region'] . '&ffeature=' . $row['ffeature'] . '&sd=&lsf=-1&host=&user=&except=-2'), $i, '', 'right');

				form_end_row();

				$i++;
			}

			html_end_box(false);
			print $nav;
		} else {
			print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No License Scheduler Records Found', 'gridblstat') . "</em></td></tr>";
			html_end_box(false);
		}

		if ($na) {
			html_start_box('', '100%', '', '3', 'center', '');
			print "<tr><td colspn='" . (cacti_sizeof($display_text)) . "'>" . __('<b>NOTE:</b> N/A Indicates that the LM Server is not being monitored by RTM', 'gridblstat') . '</td></tr>';
			html_end_box();
		}
	} else if (get_request_var('tab') == 'distribution') {
    	/* ================= input validation and session storage ================= */
	    $filters = array(
			'rows' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'refresh' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'page' => array(
				'filter' => FILTER_VALIDATE_INT,
				'default' => '1'
				),
			'feature' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'region' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sd' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'rtm_filter_sanitize_search_string')
				),
			'sort_column' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'feature',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sort_direction' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'ASC',
				'options' => array('options' => 'sanitize_search_string')
				),
			'inuse' => array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' => array('options' => array('regexp' => '(true|false)')),
				'pageset' => true,
				'default' => get_user_setting('inuse', 'true')
			)
		);

		validate_store_request_vars($filters, 'sess_gbs_dis');
		/* ================= input validation ================= */

		$sql_where = '';

		if (get_request_var('sd') != '') {
			$sql_where = 'WHERE gbd.service_domain = ?';
			$sql_params[] = get_request_var('sd');
		}

		if (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on') {
			$sql_where .= ($sql_where != ''? ' AND ':'WHERE ') . ' (lsf_use>0 OR non_lsf_use>0)';
		}

		if (get_request_var('feature') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " gbd.feature LIKE ?";
			$sql_params[] = "%" . str_replace('_','\_',get_request_var('feature')) . "%";
		}

		if (get_request_var('region') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " gbc.region LIKE ?";
			$sql_params[] = '%'. get_request_var('region') . '%';
		}

		if (get_request_var('rows') == '-1') {
			$rows = read_config_option('num_rows_table');
		} else {
			$rows = get_request_var('rows');
		}

		$sql_order = get_order_string();
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

		$distrib  = db_fetch_assoc_prepared("SELECT gbd.*, gbc.region,
			CASE WHEN gc.clusterid is null THEN '" . __('Project', 'gridblstat') . "' ELSE '" . __('Cluster', 'gridblstat') . "' END AS type
			FROM grid_blstat_distribution AS gbd
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbd.lsid=gbc.lsid
			LEFT JOIN (
				SELECT clusterid, lsid, feature, service_domain
				FROM grid_blstat_clusters
				GROUP BY lsid, feature, service_domain
			) AS gc
			ON gbd.lsid = gc.lsid
			AND gbd.feature = gc.feature
			AND gbd.service_domain=gc.service_domain
			$sql_where
			$sql_order
			$sql_limit", $sql_params);

		$total_rows  = db_fetch_cell_prepared("SELECT count(*)
			FROM grid_blstat_distribution AS gbd
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbd.lsid=gbc.lsid
			LEFT JOIN (
				SELECT clusterid, lsid, feature, service_domain
				FROM grid_blstat_clusters
				GROUP BY lsid, feature, service_domain
			) AS gc
			ON gbd.lsid = gc.lsid
			AND gbd.feature = gc.feature
			AND gbd.service_domain=gc.service_domain
			$sql_where", $sql_params);

		html_start_box(__('Distribution Filter %s', grid_blstat_header(), 'gridblstat'), '100%', '', '3', 'center', '');
		grid_distribution_filter();
		html_end_box(true);

		$display_text = build_distrib_display_array();

		$nav = html_nav_bar('grid_lsdashboard.php?action=distribution&tab=distribution', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Distribution', 'gridblstat'), 'page', 'main');

        print $nav;

		/* draw the feature dashboard */
		html_start_box('', '100%', '', '3', 'center', '');

		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

		$i = 0;

		if (cacti_sizeof($distrib)) {
			foreach($distrib as $row) {
				$graph_select = '';

				if (get_graph_template_id('b40d45dffd84f39348c87c0ff9245930')) {
					$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
						FROM grid_blstat_collectors
						WHERE lsid = ?',
						array($row['lsid']));

					if (!empty($cacti_host)) {
						$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT gl.id
							FROM host_snmp_cache AS hsc
							INNER JOIN graph_local AS gl
							ON (gl.snmp_query_id=hsc.snmp_query_id)
							AND (hsc.host_id=gl.host_id)
							WHERE (gl.host_id=?)
							AND (gl.snmp_index LIKE ?)
							AND (gl.graph_template_id=?)",
							array($cacti_host, $row['lsid'] . '|' . $row['feature'] . "%", get_graph_template_id('b40d45dffd84f39348c87c0ff9245930')));

						if (cacti_sizeof($local_graph_ids)) {
							$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

							foreach($local_graph_ids as $graph) {
									$graph_select .= $graph['id'] . '%2C';
							}
						}
					}
				}

				form_alternate_row();

				$actions_url = "<a href='" . html_escape('grid_lsdashboard.php?action=features&tab=features&lsid=' . $row['lsid'] .'&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_featdb.gif' title='" . __esc('View Feature Dashboard', 'gridblstat') . "'></a>\n";

				if ($row['type'] == 'Project') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=clusters&tab=clusters&filter=&project=&cluster=-1&mode=1&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_cluster.gif' title='" . __esc('View Cluster Use', 'gridblstat') . "'></a>\n";
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=projects&tab=projects&sd=' . urlencode($row['service_domain']) . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_project.gif' title='" . __esc('View Project Use', 'gridblstat') . "'></a>\n";
				} elseif ($row['type'] == 'Cluster') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=clusters&tab=clusters&filter=&project=&cluster=-1&mode=2&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_cluster.gif' title='" . __esc('View Cluster Use', 'gridblstat') . "'></a>\n";
				}

				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=checkouts&tab=checkouts&ffeature=' . read_config_option('grid_blstats_flexname_' . $row['lsid'] . '_' . $row['feature']) . '&sd=&lsf=-1&host=&user=') . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_checkouts.gif' title='" . __esc('View Feature Checkouts', 'gridblstat') . "'></a>\n";

				if ($graph_select != '') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=graphs&tab=graphs&template=' . get_graph_template_id('b40d45dffd84f39348c87c0ff9245930') . '&query=1&region=' . get_request_var('region') . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "&project=&cluster=-1'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' title='" . __esc('View Feature Graphs', 'gridblstat') . "'></a>";
				}

				form_selectable_cell($actions_url, $i, '1%');

				form_selectable_cell($row['service_domain'], $i);
				form_selectable_cell($row['feature'], $i);
				form_selectable_cell($row['region'], $i);

				form_selectable_cell(number_format_i18n($row['total']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['lsf_use']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['lsf_deserve']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['lsf_free']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['non_lsf_use']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['non_lsf_deserve']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['non_lsf_free']), $i, '', 'right');

				form_end_row();

				$i++;
			}
		} else {
			print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Records Found', 'gridblstat') . "</em></td></tr>";
		}

		html_end_box();

		if (cacti_sizeof($distrib)) {
			print $nav;
		}
	} else if (get_request_var('tab') == 'projects') {
    	/* ================= input validation and session storage ================= */
	    $filters = array(
			'rows' => array(
				'filter' => FILTER_VALIDATE_INT,
				'pageset' => true,
				'default' => '-1'
				),
			'refresh' => array(
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
				'default' => '0'
				),
			'feature' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'region' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sd' => array(
				'filter' => FILTER_CALLBACK,
				'pageset' => true,
				'default' => '',
				'options' => array('options' => 'rtm_filter_sanitize_search_string')
				),
			'sort_column' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'feature',
				'options' => array('options' => 'sanitize_search_string')
				),
			'sort_direction' => array(
				'filter' => FILTER_CALLBACK,
				'default' => 'ASC',
				'options' => array('options' => 'sanitize_search_string')
				),
			'inuse' => array(
				'filter' => FILTER_VALIDATE_REGEXP,
				'options' => array('options' => array('regexp' => '(true|false)')),
				'pageset' => true,
				'default' => get_user_setting('inuse', 'true')
			)
		);

		validate_store_request_vars($filters, 'sess_gbs_pr');
		/* ================= input validation ================= */

		$sql_where = 'WHERE gbp.present = 1';

		if (get_request_var('rows') == '-1') {
			$rows = read_config_option('num_rows_table');
		} else {
			$rows = get_request_var('rows');
		}

		if (get_request_var('feature') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " feature LIKE ?";
			$sql_params[] = "%" . str_replace('_','\_',get_request_var('feature')) . "%";
		}

		if (get_request_var('region') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " region LIKE ?";
			$sql_params[] = '%'. get_request_var('region') . '%';
		}

		if (get_request_var('sd') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " service_domain=?";
			$sql_params[] = get_request_var('sd');
		}

		if (get_request_var('inuse') == 'on' || get_request_var('inuse') == 'true') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' inuse>0';
		}

		$projectName = 'project';
		$aggregatelevel = 0; //all

		if (isset_request_var('level') && get_request_var('level') != 0) {
			$aggregatelevel=get_request_var('level');
			$projectName = get_project_aggregation_string($aggregatelevel, "gridblstat");
			$projectName = str_replace('projectName', 'project', $projectName);
		}

		$sql_group_by = $projectName;

		$sql_order = get_order_string();
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

		$projects = db_fetch_assoc_prepared("SELECT gbc.region, gbc.lsid, $projectName AS project,
			service_domain, feature,
			SUM(share) AS share,
			SUM(own) AS own,
			SUM(inuse) AS inuse,
			SUM(reserve) AS reserve,
			SUM(free) AS free,
			SUM(demand) AS demand
			FROM grid_blstat_projects AS gbp
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=gbp.lsid
			$sql_where
			GROUP BY gbc.lsid, service_domain, feature, $sql_group_by
			$sql_order
			$sql_limit", $sql_params);

		$total_rows = db_fetch_cell_prepared("SELECT count(*) FROM
			(SELECT 1
				FROM grid_blstat_projects AS gbp
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid=gbp.lsid
				$sql_where
				GROUP BY gbc.lsid, service_domain, feature, $sql_group_by
			) AS `rows`", $sql_params);

		html_start_box(__('Project Filter %s', grid_blstat_header(), 'gridblstat'), '100%', '', '3', 'center', '');
		grid_project_filter();
		html_end_box(true);

		$display_text = build_project_display_array();

		$nav = html_nav_bar('grid_lsdashboard.php?action=projects&tab=projects', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Projects', 'gridblstat'), 'page', 'main');

		print $nav;

		html_start_box('', '100%', '', '3', 'center', '');

		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

		$i = 0;

		if (cacti_sizeof($projects)) {
			foreach($projects as $row) {
				$fparts = explode('@', $row['feature']);
				$ffirst = $fparts[0];
				$graph_select = false;

				if (get_data_query_id('276d7b3235d9fe1071346abfcbead663')) {
					$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
						FROM grid_blstat_collectors
						WHERE lsid = ?',
						array($row['lsid']));

					if (!empty($cacti_host)) {
						$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT gl.id
							FROM host_snmp_cache AS hsc
							INNER JOIN graph_local AS gl
							ON (gl.snmp_query_id=hsc.snmp_query_id)
							AND (hsc.host_id=gl.host_id)
							WHERE (gl.host_id=?)
							AND (gl.snmp_index=?)
							AND (field_name='project')
							AND (gl.snmp_query_id=?)",
							array($cacti_host, $row['lsid'] . '|' . $row['feature'] . '|' . $row['service_domain'] . '|' . $row['project'], get_data_query_id('276d7b3235d9fe1071346abfcbead663')));

						if (cacti_sizeof($local_graph_ids)) {
							$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

							foreach($local_graph_ids as $graph) {
								$graph_select .= $graph['id'] . '%2C';
							}
						}
					}
				} else {
					$clusterid = -1;
				}

				$service_id = read_config_option('grid_blstats_domain_' . $row['lsid'] . '_' . $row['service_domain']);
				if (empty($service_id)) {
					$service_id = '-1';
				}

				$actions_url = "<a href='" . html_escape('grid_lsdashboard.php?action=clusters&tab=clusters&filter=&cluster=-1&project=' . $row['project'] . '&mode=1&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_cluster.gif' title='" . __esc('View Cluster Use', 'gridblstat') . "'></a>\n";
				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=features&tab=features&lsid=' . $row['lsid'] .'&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_featdb.gif' title='" . __esc('View Feature Dashboard', 'gridblstat') . "'></a>\n";
				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=users&tab=users&cluster=-1&sd=' . urlencode($row['service_domain']) . '&resource=' . $ffirst . '&project=' . $row['project']) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_users.gif' title='" . __esc('View Users Jobs', 'gridblstat') . "'></a>\n";
				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=checkouts&tab=checkouts&ffeature=' . read_config_option('grid_blstats_flexname_' . $row['lsid'] . '_' . $row['feature']) . '&sd=' . urlencode($row['service_domain']) . '&lsf=-1&host=&user=') . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_checkouts.gif' title='" . __esc('View Feature Checkouts', 'gridblstat') . "'></a>\n";

				if ($graph_select) {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=graphs&tab=graphs&template=0&query=1&region=' . get_request_var('region') . '&project=' . $row['project'] . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "&cluster=-1'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' title='" . __esc('View Feature Graphs', 'gridblstat') . "'></a>";
				}

				form_alternate_row();

				form_selectable_cell($actions_url, $i, '1%');
				form_selectable_cell($row['project'], $i);
				form_selectable_cell($row['feature'], $i);
				form_selectable_cell($row['region'], $i);
				form_selectable_cell($row['service_domain'], $i);

				form_selectable_cell($row['share'] >= 100 ? '100 %' : round($row['share'], 2) . '%', $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['own']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['inuse']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['reserve']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['free']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['demand']), $i, '', 'right');

				form_end_row();

				$i++;
			}
		} else {
			 print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Project Records Found', 'gridblstat') . "</em></td></tr>";
		}

		html_end_box();

		if (cacti_sizeof($projects)) {
			print $nav;
		}
	} elseif (get_request_var('tab') == 'users') {
		grid_view_users();
	} elseif (get_request_var('tab') == 'clusters') {
		grid_view_clusters();
	} elseif (get_request_var('tab') == 'features') {
		grid_view_zen();
	} elseif (get_request_var('tab') == 'checkouts') {
		grid_view_checkouts();
	} elseif (get_request_var('tab') == 'graphs') {
		grid_blstat_view_graphs();
	}

	bottom_footer();
}

function grid_summary_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input size='20' type="text" id='feature' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input size='20' type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
						<input id='inuse' type='checkbox' onChange='applyFilter()' <?php print (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on' ? ' checked=checked':'');?>>
					</td>
					<td>
						<label for='inuse'><?php print __('In Use', 'gridblstat');?></label>
					</td>
					<td>
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridblstat');?>' onClick='applyFilter()'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridblstat');?>' onClick='clearFilter()'>
						</span>
					</td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=summary&tab=summary&header=false';
				strURL += '&feature=' + $('#feature').val();
				strURL += '&region=' + $('#region').val();
				strURL += '&inuse=' + $('#inuse').is(':checked');
				strURL += '&rows=' + $('#rows').val();
				strURL += '&refresh=' + $('#refresh').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=summary&tab=summary&header=false&clear=true';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_project_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_projectgroup_filter_levels;
	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='feature' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<input id='inuse' type='checkbox' onChange='applyFilter()' <?php print (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on' ? ' checked=checked':'');?>>
					</td>
					<td>
						<label for='inuse'><?php print __('In Use', 'gridblstat');?></label>
					</td>
					<td>
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridblstat');?>'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridblstat');?>' onClick='clearFilter()'>
						</span>
					</td>
					<td>
						<input type='hidden' id='lsid' name='lsid' value='<?php print (isset_request_var('lsid') ? get_request_var('lsid'):'');;?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Domain', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='sd' value='<?php print html_escape_request_var('sd');?>'>
					</td>
					<?php if (read_config_option('grid_blstat_project_group_aggregation') == 'on') { ?>
				 	<td>
						<?php print __('Level', 'gridblstat');?>
					</td>
					<td>
						<select id='level' onChange='applyFilter()'>
							<?php
							$max_level= read_config_option('grid_blstat_job_stats_project_level_number');

							foreach ($grid_projectgroup_filter_levels as $key => $value) {
								if (!isset($max_level) || ($max_level == 0) || ($max_level > 0 && $key <= $max_level)) {
									print '<option value="' . $key . '"'; if (get_request_var('level') == $key) { print ' selected'; } print '>    ' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php } else { ?>
					<td style='display:none;'><input type='hidden' id='level' value='0'></td>
					<?php } ?>
					<td>
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=projects&tab=projects&header=false';
				strURL +=  '&lsid=' + $('#lsid').val();
				strURL +=  '&feature=' + $('#feature').val();
				strURL +=  '&region=' + $('#region').val();
				strURL +=  '&rows=' + $('#rows').val();
				strURL +=  '&level=' + $('#level').val();
				strURL +=  '&sd=' + encodeURIComponent($('#sd').val());
				strURL +=  '&inuse=' + $('#inuse').is(':checked');
				strURL +=  '&refresh=' + $('#refresh').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=projects&tab=projects&feature=&rows=-1&region=&sd=&refresh=-1&level=0&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_distribution_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_projectgroup_filter_levels;
	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='feature' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<input id='inuse' type='checkbox' onChange='applyFilter()' <?php print (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on' ? ' checked=checked':'');?>>
					</td>
					<td>
						<label for='inuse'><?php print __('In Use', 'gridblatat');?></label>
					</td>
					<td>
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridblatat');?>'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridblatat');?>' onClick='clearFilter()'>
						</span>
					</td>
					<td>
						<input type='hidden' id='lsid' name='lsid' value='<?php print (isset_request_var('lsid') ? get_request_var('lsid'):'');?>'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Domain', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='sd' value='<?php print html_escape_request_var('sd');?>'>
					</td>
					<td>
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=distribution&tab=distribution&header=false'
				strURL += '&lsid=' + $('#lsid').val();
				strURL += '&feature=' + $('#feature').val();
				strURL += '&region=' + $('#region').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&inuse=' + $('#inuse').is(':checked');
				strURL +=  '&sd=' + encodeURIComponent($('#sd').val());
				strURL += '&refresh=' + $('#refresh').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=distribution&tab=distribution&clear=true&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_view_get_users_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	if (get_request_var('sd') == '-1') {
		/* show all service domains */
	} elseif (get_request_var('sd') == '-3') {
		/* show all service domains */
	} elseif (get_request_var('sd') == '-2') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.service_domain!='UNKNOWN'";
	} else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.service_domain=?";
		$sql_params[] = get_request_var('sd');
	}

	if (get_request_var('user') == '') {
		/* show all users */
	} else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.user=?";
		$sql_params[] = get_request_var('user');
	}

	if (get_request_var('sd') != '-3') {
		if (get_request_var('resource') == '') {
			/* show all users */
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.resource=?";
			$sql_params[] = get_request_var('resource');
		}
	}

	if (get_request_var('region') != '') {
		$clusters = array_rekey(db_fetch_assoc_prepared("SELECT DISTINCT gbcc.clusterid
			FROM grid_blstat_collector_clusters AS gbcc
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbcc.lsid=gbc.lsid
			WHERE region=?", array(get_request_var('region'))), 'clusterid', 'clusterid');

		if (cacti_sizeof($clusters)) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.clusterid IN (" . implode(',', $clusters) . ")";
		}

	}

	if (get_request_var('project') == '') {
		/* show all projects */
	} elseif (get_request_var('sd') != '-3') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.project=?";
		$sql_params[] = get_request_var('project');
	}

	if (get_request_var('host') == '') {
		/* show all projects */
	} elseif (get_request_var('sd') != '-3') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.host=?";
		$sql_params[] = get_request_var('host');
	}

	if (get_request_var('cluster') == '-1') {
		/* show all clusters */
	} else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " bu.cluster=?";
		$sql_params[] = get_request_var('cluster');
	}

	$sql_order = get_order_string();
	if (get_request_var('sd') != '-3') {
		$sql_query = "SELECT gbc.region, bu.*, gj.clusterid, gj.run_time, gj.start_time AS gjst
			FROM grid_blstat_users AS bu
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=bu.lsid
			LEFT JOIN grid_jobs AS gj
			ON bu.jobid=gj.jobid
			AND bu.indexid=gj.indexid
			AND bu.clusterid=gj.clusterid
			$sql_where
			$sql_order";
	} else {
		$clusters = get_region_clusters(get_request_var('region'));
		$ls_count = get_ls_count();
		if (cacti_sizeof($clusters) && $ls_count > 1) {
			$clwhere = 'AND gj.clusterid IN(' . implode(',', $clusters) . ')';
		} else {
			$clwhere = '';
		}
		$pendreason=read_config_option("grid_pendreason_full_collection");
		if (get_request_var('resource') != '') {
			$parts = explode('@', get_request_var('resource'));
			if ($pendreason != 'on') {
				$sql_reason =" AND substring_index(substring_index(pendReasons,'(', -1), ')', 1) ='". $parts[0] ."'";
			} else {
				$sql_reason = "AND subreason='" . $parts[0] . "'";
			}
		} else {
			if ($pendreason != 'on') {
				$sql_reason = " AND substring_index(substring_index(pendReasons,'(', -1), ')', 1) IN (SELECT DISTINCT substring_index(feature, '@', 1) FROM grid_blstat)";
			} else {
				$sql_reason = "AND subreason IN (SELECT DISTINCT substring_index(feature, '@', 1) FROM grid_blstat)";
			}
		}

		if ($pendreason != 'on') {
			/**
			 * Pre LSF 10.1:
			 * 		PEND_HOST_JOB_RUSAGE: Job's requirements for reserving resource (%s) not satisfied
			 * 		PEND_GUARANTEE_RSRC: Resource (%s) reserved for SLA guarantees, or not enough resources available
			 * LSF 10.1+:
			 * 		Job's requirements for resource reservation not satisfied: Resource: %s
			 * 		Resource reserved for SLA guarantees, or not enough resources available: Resource: %s
			 */
			$sql_query = "SELECT bu.*
				FROM (  SELECT * FROM (
					SELECT '-' AS region, gj.stat, gj.jobid, gj.indexid, gc.lsf_clustername AS cluster,
					gj.clusterid, gj.user, '-' AS host, gj.pendReasons,
					gj.licenseProject AS project, '0000-00-00 00:00:00' AS start_time,
					substring_index(substring_index(pendReasons,'(', -1), ')', 1) as resource,
					'Demanding' AS service_domain, '1' AS rusage, '0' AS run_time, '0000-00-00 00:00:00' AS gjst
					FROM grid_jobs AS gj
					INNER JOIN grid_clusters AS gc
					ON gj.clusterid=gc.clusterid
					WHERE ((pendReasons LIKE '%Job\'s requirements for reserving resource (%) not satisfied;%'  $sql_reason)
					OR (pendReasons LIKE '%Resource (%) reserved for SLA guarantees, or not enough resources available%'  $sql_reason))
					AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')
					$clwhere
					) AS a ) AS bu
				$sql_where
				$sql_order";


		} else {
			$sql_query = "SELECT bu.*
				FROM (
					SELECT '-' AS region, gj.jobid, gj.indexid, gc.lsf_clustername AS cluster, gj.clusterid, gj.user, '-' AS host,
					gj.licenseProject AS project, '0000-00-00 00:00:00' AS start_time, subreason AS resource,gj.stat,
					'Demanding' AS service_domain, '1' AS rusage, '0' AS run_time, '0000-00-00 00:00:00' AS gjst
					FROM grid_jobs AS gj
					INNER JOIN grid_clusters AS gc
					ON gj.clusterid=gc.clusterid
					INNER JOIN grid_jobs_pendreasons AS gjp
					ON gj.clusterid=gjp.clusterid
					AND gj.jobid=gjp.jobid
					AND gj.indexid=gjp.indexid
					AND gj.submit_time=gjp.submit_time
					WHERE reason IN (2601,6000)
					$clwhere
					$sql_reason
					AND gjp.end_time='0000-00-00'
					AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')
				) AS bu
				$sql_where
				$sql_order";
		}
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//cacti_log("DEBUG: " . str_replace("\n", " ", $sql_query));

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_user_display_array() {
	$display_text = array();

	$display_text += array('' => array('Actions', ''));
	$display_text += array('bu.user' => array('User', 'ASC'));
	$display_text += array('gbc.region' => array('Region', 'ASC'));
	$display_text += array('bu.service_domain' => array('Service Domain', 'ASC'));
	$display_text += array('bu.project' => array('Project', 'ASC'));
	$display_text += array('bu.cluster' => array('Cluster', 'ASC'));
	$display_text += array('bu.resource' => array('Resource', 'ASC', 'The name of the license requested by the job'));
	$display_text += array('bu.jobid' => array('JobID', 'ASC'));
	$display_text += array('bu.indexid' => array('IndexID', 'ASC'));
	$display_text += array('bu.host' => array('Host', 'ASC', 'The name of the host where the job has been started'));
	$display_text += array('bu.start_time' => array('Start Time', 'DESC', 'The job start time'));
	$display_text += array('gj.run_time' => array('Run Time', 'DESC', 'The job run time'));
	$display_text += array('bu.rusage' => array('RUsage', 'ASC','The number of licenses requested by the job'));

	return $display_text;
}

function grid_view_users() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

   	/* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
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
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sd' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'rtm_filter_sanitize_search_string')
			),
		'user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'resource' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'host' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'cluster' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'jobid',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gbs_u');
	/* ================= input validation ================= */

	html_start_box(__('User Filter [ Details may vary depending on license scheduler Poller status ]', 'gridblstat'), '100%', '', '3', 'center', '');
	grid_user_filter();
	html_end_box(true);

	$sql_where = '';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('region') != '' && get_request_var('sd') != '-3') {
		$lsids = array_rekey(
			db_fetch_assoc_prepared('SELECT lsid
				FROM grid_blstat_collectors
				WHERE region = ?',
				array(get_request_var('region'))),
			'lsid', 'lsid'
		);

		if (cacti_sizeof($lsids)) {
			$sql_where = 'WHERE gbc.lsid IN(' . implode(',', $lsids) . ')';
		} else {
			$sql_where = 'WHERE gbc.lsid < 0';
		}
	}

	$user_results = grid_view_get_users_records($sql_where, true, $rows, $sql_params);

	if (get_request_var('sd') != '-3') {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_blstat_users AS bu
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=bu.lsid
			LEFT JOIN grid_jobs AS gj
			ON bu.jobid=gj.jobid
			AND bu.indexid=gj.indexid
			AND bu.clusterid=gj.clusterid
			$sql_where";
	} else {
		$pendreason = read_config_option('grid_pendreason_full_collection');

		if (get_request_var('resource') != '') {
			$parts = explode('@', get_request_var('resource'));

			if ($pendreason != 'on') {
				$sql_reason = "substring_index(substring_index(pendReasons,'(', -1), ')', 1) ='". $parts[0] ."'";
			} else {
				$sql_reason = 'AND subreason=' . db_qstr($parts[0]);
			}
		} else {
			if ($pendreason != 'on') {
				$sql_reason = "substring_index(substring_index(pendReasons,'(', -1), ')', 1) IN (SELECT DISTINCT substring_index(feature, '@', 1) FROM grid_blstat)";
			} else {
				$sql_reason = "AND subreason IN (SELECT DISTINCT substring_index(feature, '@', 1) FROM grid_blstat)";
			}
		}

		if ($pendreason != 'on') {
			$rows_query_string = "SELECT COUNT(*)
				FROM (
					SELECT *
					FROM (
						SELECT '-' AS region,gj.pendReasons, gj.stat,
						gj.jobid, gj.indexid, gc.lsf_clustername AS cluster,
						gj.clusterid, gj.user, '-' AS host,
						gj.licenseProject AS project, '0000-00-00 00:00:00' AS start_time,
						substring_index(substring_index(pendReasons,'(', -1), ')', 1) as resource,
						'" . __('Demanding', 'gridblstat') . "' AS service_domain,
						'1' AS rusage, '0' AS run_time, '0000-00-00 00:00:00' AS gjst
						FROM grid_jobs AS gj
						INNER JOIN grid_clusters AS gc
						ON gj.clusterid=gc.clusterid
						WHERE (pendReasons LIKE 'Job\'s requirements for reserving resource (%) not satisfied%'
						AND $sql_reason)
						OR (pendReasons LIKE 'Resource (%) reserved for SLA guarantees, or not enough resources available%'
						AND $sql_reason)
						AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')
					) AS a
				) as bu
				$sql_where";
		} else {
			$rows_query_string = "SELECT COUNT(*) FROM (
				SELECT '-' AS region, gj.stat,gj.jobid, gj.indexid,
				gc.lsf_clustername AS cluster, gj.clusterid, gj.user, '-' AS host,
				gj.licenseProject AS project, '0000-00-00 00:00:00' AS start_time, subreason AS resource,
				'" . __('Demanding', 'gridblstat') . "' AS service_domain,
				'1' AS rusage, '0' AS run_time, '0000-00-00 00:00:00' AS gjst
				FROM grid_jobs AS gj
				INNER JOIN grid_clusters AS gc
				ON gj.clusterid=gc.clusterid
				INNER JOIN grid_jobs_pendreasons AS gjp
				ON gj.clusterid=gjp.clusterid
				AND gj.jobid=gjp.jobid
				AND gj.indexid=gjp.indexid
				AND gj.submit_time=gjp.submit_time
				WHERE reason IN (2601,6000)
				$sql_reason
				AND gjp.end_time='0000-00-00'
				AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')) AS bu
				$sql_where";
		}
	}

	//cacti_log("DEBUG: " . str_replace("\n", " ", $rows_query_string));
	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	/*RTC#222297: remove 'lsid'. In other 'ffeature' param usage, 'lsid' is used to get actual feature name by read_config_option('grid_blstats_flexname_' + lsid + feature)*/
	/* GHE#584: implode lic_feature in LS dashboard to ensure one row per LS feature on dashbaord. */
	$features = array_rekey(
		db_fetch_assoc('SELECT bld_feature, GROUP_CONCAT(DISTINCT lic_feature) AS lic_feature
			FROM grid_blstat_feature_map GROUP BY bld_feature'),
		'bld_feature', 'lic_feature'
	);

	$display_text = build_user_display_array();

	$nav = html_nav_bar('grid_lsdashboard.php?action=users&tab=users', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Users', 'gridblstat'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;
	if (cacti_sizeof($user_results)) {
		foreach ($user_results as $user) {
			$graph_select = '';

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($user['clusterid']));

			if (isset($cacti_host)) {
				$clusterid = (!empty($user['clusterid']) ? $user['clusterid']:0);

				$local_graph_ids = db_fetch_assoc_prepared('SELECT DISTINCT gl.id
					FROM host_snmp_cache AS hsc
					INNER JOIN graph_local AS gl
					ON gl.snmp_query_id=hsc.snmp_query_id
					AND hsc.host_id = gl.host_id
					WHERE gl.host_id = ?
					AND gl.snmp_index = ?
					AND hsc.field_name="user"',
					array($cacti_host, $user['user']));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = "page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=";

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph["id"] . "%2C";
					}
				}
			} else {
				$clusterid = -1;
			}

			$submit_time = strtotime(
				db_fetch_cell_prepared('SELECT submit_time
					FROM grid_jobs
					WHERE jobid = ?
					AND indexid = ?
					AND clusterid = ?',
					array($user['jobid'], $user['indexid'], $user['clusterid'])
				)
			);

			/*RTC#222297: remove 'lsid'. In other 'ffeature' param usage, 'lsid' is used to get actual feature name by read_config_option('grid_blstats_flexname_' + lsid + feature)*/
			if (isset($features[$user['resource']])) {
				$tmp_features=$features[$user['resource']];
			} else {
				if (isset($features[$user['resource'] . '@' . $user['cluster']])) {
					$tmp_features = $features[$user['resource'] . '@' . $user['cluster']];
				} else {
					$tmp_features = '';
				}
			}

			$actions_url = "";

			if ($clusterid > 0) {
				$actions_url .= "<a href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewjob&reset=true&clusterid=' . $clusterid . '&jobid=' . $user['jobid'] . '&indexid=' . $user['indexid'] . '&submit_time=' . $submit_time) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_jobs.gif' title='" . __esc('View Users Job', 'gridblstat') . "'></a>\n";
			}

			$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=checkouts&tab=checkouts&ffeature=' . $tmp_features . '&sd=' . urlencode($user['service_domain']) . '&user=' . $user['user'] . '&host=' . $user['host']) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_checkouts.gif' title='" . __esc('View User/Host License Checkouts', 'gridblstat') . "'></a>\n";

			if ($graph_select) {
				$actions_url .= "<a href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' title='" . __esc('View User Graphs', 'gridblstat') . "'></a>";
			}

			form_alternate_row();

			form_selectable_cell($actions_url, $i, '1%');
			form_selectable_cell($user['user'], $i);
			form_selectable_cell(title_trim($user['region'], read_config_option('max_title_length')), $i);
			form_selectable_cell(title_trim($user['service_domain'], read_config_option('max_title_length')), $i);
			form_selectable_cell($user['project'], $i);
			form_selectable_cell($user['cluster'], $i);
			form_selectable_cell($user['resource'], $i);

			if ($clusterid > 0) {
				form_selectable_cell(filter_value($user['jobid'], '', $config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewjob&reset=true&clusterid=' . $clusterid . '&jobid=' . $user['jobid'] . '&indexid=' . $user['indexid'] . '&submit_time=' . $submit_time), $i);
				form_selectable_cell($user['indexid'], $i);
				form_selectable_cell(filter_value($user['host'], '', $config['url_path'] . 'plugins/grid/grid_bzen.php?action=zoom&clusterid=' . $clusterid . '&exec_host=' . $user['host']), $i);
			} else {
				form_selectable_cell($user['jobid'], $i);
				form_selectable_cell($user['indexid'], $i);
				form_selectable_cell($user['host'], $i);
			}

			form_selectable_cell(!empty($user['gjst']) && $user['gjst'] != '0000-00-00 00:00:00' ? $user['gjst']:($user['gjst'] == '0000-00-00 00:00:00' ? __('N/A', 'gridblstat'):__('Not Monitored', 'gridblstat')), $i);

			form_selectable_cell(display_job_time($user['run_time'], 2, false), $i);
			form_selectable_cell($user['rusage'] == 0 ? __('No', 'gridblstat'):$user['rusage'], $i);

			form_end_row();

			$i++;
		}
	} else {
		print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No User Records Found', 'gridblstat') . '</em></td></tr>';
	}

	html_end_box();

	if (cacti_sizeof($user_results)) {
		print $nav;
	}

}

function grid_user_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Resource', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='resource' value='<?php print html_escape_request_var('resource');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input type="text" size='20' id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Domain', 'gridblstat');?>
					</td>
					<td>
						<select id='sd' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('sd') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<option value='-2'<?php if (get_request_var('sd') == '-2') {?> selected<?php }?>><?php print __('Consuming', 'gridblstat');?></option>
							<option value='-3'<?php if (get_request_var('sd') == '-3') {?> selected<?php }?>><?php print __('Demanding', 'gridblstat');?></option>
							<option value='UNKNOWN'<?php if (get_request_var('sd') == 'UNKNOWN') {?> selected<?php }?>><?php print __('Reserving', 'gridblstat');?></option>
							<?php
							$sds = db_fetch_assoc('SELECT DISTINCT service_domain
								FROM grid_blstat
								ORDER BY service_domain AND present = 1');

							if (cacti_sizeof($sds)) {
								foreach ($sds as $sd) {
									print '<option value="' . $sd['service_domain'] .'"'; if (get_request_var('sd') == $sd['service_domain']) { print ' selected'; } print '>' . $sd['service_domain'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridblstat');?>'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridblstat');?>' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('User', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='ls_user' value='<?php print html_escape_request_var('user');?>'>
					</td>
					<td>
						<?php print __('Host', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='ls_host' value='<?php print html_escape_request_var('host');?>'>
					</td>
					<td>
						<?php print __('Project', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='project' value='<?php print html_escape_request_var('project');?>'>
					</td>
					<td>
						<?php print __('Cluster', 'gridblstat');?>
					</td>
					<td>
						<select id='cluster' onChange='applyFilter()'>
							<option value='-1'<?php if (isset_request_var('cluster') && get_request_var('cluster') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT DISTINCT cluster
								FROM grid_blstat_cluster_use WHERE present = 1
								UNION
								SELECT DISTINCT cluster
								FROM grid_blstat_clusters  WHERE present = 1
								ORDER BY cluster');

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['cluster'] .'"'; if (isset_request_var('cluster') && get_request_var('cluster') == $cluster['cluster']) { print ' selected'; } print '>' . $cluster['cluster'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
						<input type='hidden' id='lsid' name='lsid' value='<?php print (isset_request_var('lsid') ? get_request_var('lsid'):'');?>'>
					</td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=users&tab=users&header=false'
				strURL +=  '&lsid=' + $('#lsid').val();
				strURL +=  '&cluster=' + $('#cluster').val();
				strURL +=  '&region=' + $('#region').val();
				strURL +=  '&refresh=' + $('#refresh').val();
				strURL +=  '&user=' + $('#ls_user').val();
				strURL +=  '&region=' + $('#region').val();
				strURL +=  '&host=' + $('#ls_host').val();
				strURL +=  '&sd=' + encodeURIComponent($('#sd').val());
				strURL +=  '&resource=' + $('#resource').val();
				strURL +=  '&project=' + $('#project').val();
				strURL +=  '&rows=' + $('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=users&tab=users&clear=true&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_view_get_cluster_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	if (get_request_var('cluster') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . ' a.cluster=' . db_qstr(get_request_var('cluster'));
		$sql_params[] = get_request_var('cluster');
	}

	if (get_request_var('feature') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " feature LIKE ?";
		$sql_params[] = "%" . str_replace('_','\_',get_request_var('feature')) . "%";
	}

	if (get_request_var('region') != "") {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . " region LIKE ?";
		$sql_params[] = "%" . get_request_var('region') . "%";
	}

	// Project Mode
	if (get_request_var('mode') == "1") {
		$cluster_table="grid_blstat_cluster_use ";
		$sql_select=", (demand+need) AS ndemand";

		if (get_request_var('project') != "") {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . ' project=?';
			$sql_params[] = get_request_var('project');
		}
	} else {
		$sql_select = ' ';
		//Cluster Mode
		$cluster_table = 'grid_blstat_clusters';

	}

	if (get_request_var('inuse') != 'true' && get_request_var('inuse') != 'on') {
		/* show all projects */
	} else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE') . ' (inuse>0 OR `over`>0)';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT region, a.* $sql_select
		FROM  $cluster_table AS a
		INNER JOIN grid_blstat_collectors AS gbc
		ON gbc.lsid=a.lsid
		$sql_where
		$sql_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_cluster_display_array() {
	$display_text = array();

	$display_text += array(
		'nosort' => array(
			'display' => __('Actions', 'gridblstat'),
		),
		'cluster' => array(
			'display' => __('Cluster', 'gridblatat'),
			'sort' => 'ASC'
		),
		'feature' => array(
			'display' => __('Feature Name', 'gridblatat'),
			'sort' => 'ASC'
		),
		'region' => array(
			'display' => __('Region', 'gridblatat'),
			'sort' => 'ASC',
			'tip' => __('Region Name', 'gridblstat')
		)
	);

	if (get_request_var('mode')=='1') {
		$display_text += array(
			'project' => array(
				'display' => __('Project', 'gridblatat'),
				'sort' => 'ASC'
			)
		);
	}

	$display_text += array(
		'inuse' => array(
			'display' => __('In Use', 'gridblatat'),
			'align' => 'right',
			'sort' => 'DESC',
			'tip' => __('The number of licenses checked out by jobs in the cluster', 'gridblstat')
		),
		'reserve' => array(
			'display' => __('Reserve', 'gridblatat'),
			'align' => 'right',
			'sort' => 'DESC',
			'tip' => __('The number of licenses reserved in the service domain for jobs running in the cluster', 'gridblstat')
		),
		'free' => array(
			'display' => __('Free', 'gridblatat'),
			'align' => 'right',
			'sort' => 'DESC',
			'tip' => __('The number of licenses the cluster has free', 'gridblstat')
		)
	);

	if (get_request_var('mode')=='1') {
		$display_text += array(
			'ndemand' => array(
				'display' => __('Demand', 'gridblatat'),
				'align' => 'right',
				'sort' => 'DESC',
				'tip' => __('Numeric value indicating the number of tokens required by each cluster', 'gridblstat')
			)
		);
	} else {
		$display_text += array(
			'demand' => array(
				'display' => __('Demand', 'gridblatat'),
				'align' => 'right',
				'sort' => 'DESC',
				'tip' => __('Numeric value indicating the number of tokens required by each cluster', 'gridblstat')
			)
		);
	}

	$display_text += array(
		'over' => array(
			'display' => __('Over', 'gridblatat'),
			'align' => 'right',
			'sort' => 'DESC',
			'tip' => __('The amount of license checkouts exceeding rusage, summed over all jobs', 'gridblstat')
		),
		'alloc' => array(
			'display' => __('Alloc', 'gridblatat'),
			'align' => 'right',
			'sort' => 'DESC',
			'tip' => __('The number of licenses currently allocated to the cluster by the bld', 'gridblstat')
		)
	);

	if (get_request_var('mode') == '1') {
		$display_text += array(
			'acum_use' => array(
				'display' => __('Accum Use', 'gridblatat'),
				'align' => 'right',
				'sort' => 'DESC',
				'tip' => __('The number of tokens accumulated by each consumer at runtime. It is the number of licenses assigned to a given consumer for a specific feature', 'gridblstat')
			),
			'scaled_acum' => array(
				'display' => __('Scaled Accum', 'gridblatat'),
				'align' => 'right',
				'sort' => 'DESC',
				'tip' => __('The number of tokens accumulated by each consumer at runtime divided by the SHARE value. License Scheduler uses this value to schedule the tokens for each project', 'gridblstat')
			),
			'avail' => array(
				'display' => __('Available', 'gridblatat'),
				'align' => 'right',
				'sort' => 'DESC',
				'tip' => __('The number of tokens available', 'gridblstat')
			)
		);
	}

	return $display_text;
}

function grid_view_clusters() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;
	$sql_params = array();

   	/* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'mode' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
			),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'cluster' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'inuse',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'inuse' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => get_user_setting('inuse', 'true')
		)
	);
	if (!isset_request_var('reset') && check_changed('mode', 'sess_gbs_clusters_mode')) {
		kill_session_var('sess_gbs_clusters_sort_column');
		unset_request_var('sort_column');
		update_order_string();
	}

	validate_store_request_vars($filters, 'sess_gbs_clusters');
	/* ================= input validation ================= */

	html_start_box(__('Cluster Filter %s', grid_blstat_header(), 'gridblstat'), '100%', '', '3', 'center', '');
	grid_cluster_filter();
	html_end_box(true);

	$sql_where = 'WHERE present = 1';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('mode') == '1') {
		$mode = 'Project';
	} else {
		$mode = 'Cluster';
	}

	$cluster_results = grid_view_get_cluster_records($sql_where, true, $rows, $sql_params);

	if ($mode == 'Project') {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_blstat_cluster_use AS a
			INNER JOIN grid_blstat_collectors AS gbc
			ON gbc.lsid=a.lsid
			$sql_where";
	} else if ($mode == 'Cluster') {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_blstat_clusters AS a
			INNER JOIN grid_blstat_collectors AS gbc
			ON a.lsid=gbc.lsid
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_cluster_display_array();

	$nav = html_nav_bar('grid_lsdashboard.php?action=clusters&tab=clusters', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Clusters'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

	$i = 0;

	if (cacti_sizeof($cluster_results)) {
		foreach ($cluster_results as $row) {
			$graph_select = false;
			//GRID - License Scheduler by Cluster and feature is Porject mode
			//GRID - License Scheduler by Cluster Mode and feature is Cluster mode
			if ((get_data_query_id('51b53ceb3f70861c47fd169dae72a3bf') && $mode=='Project') ||
				(get_data_query_id('0a47e16083078ce8f165cb220c2d0306') && $mode == 'Cluster')) {

				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_blstat_collectors
					WHERE lsid = ?',
					array($row['lsid']));

				if (!empty($cacti_host)) {
					if ($mode == 'Project') {
						$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT gl.id
							FROM host_snmp_cache AS hsc
							INNER JOIN graph_local AS gl
							ON (gl.snmp_query_id=hsc.snmp_query_id)
							AND (hsc.host_id=gl.host_id)
							WHERE (gl.host_id=?)
							AND (gl.snmp_index=?)
							AND (gl.snmp_query_id=?)",
							array($cacti_host, $row['lsid'] . '|' . $row['feature'] . '|' . $row['project'] . '|' . $row['cluster'], get_data_query_id('51b53ceb3f70861c47fd169dae72a3bf')));
					} else if ( $mode == 'Cluster') {
						$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT gl.id
							FROM host_snmp_cache AS hsc
							INNER JOIN graph_local AS gl
							ON (gl.snmp_query_id=hsc.snmp_query_id)
							AND (hsc.host_id=gl.host_id)
							WHERE (gl.host_id=?)
							AND (gl.snmp_index=?)
							AND (gl.snmp_query_id=?)",
							array($cacti_host, $row['lsid'] . '|' . $row['feature'] . '|' . '|' . $row['cluster'], get_data_query_id('0a47e16083078ce8f165cb220c2d0306')));
					}

					if (cacti_sizeof($local_graph_ids)) {
						$graph_select = true;
					}
				}
			}

			$parts = explode('@', $row['feature']);

			$actions_url = '';

			if ($graph_select) {
				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=features&tab=features&lsid=' . $row['lsid'] . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "'><img src='" . $config['url_path'] . "plugins/gridblstat/images/view_featdb.gif' title='" . __esc('View Feature Dashboard', 'gridblstat') . "'></a>\n";

				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=users&tab=users&cluster=' . $row['cluster'] . '&user=&host=&sd=-1&resource=' . $parts[0] . ($mode == 'Project' ?'&project='. $row['project'] .'':'&project=') . '&inuse=' . get_request_var('inuse')) . "' title='" . __esc('View Users Jobs', 'gridblstat') . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_users.gif'></a>\n";

				$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=checkouts&tab=checkouts&host=&user=&sd=&ffeature=' . read_config_option('grid_blstats_flexname_' . $row['lsid'] . '_' . $row['feature'])) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_checkouts.gif' title='" . __esc('View User/Host License Checkouts', 'gridblstat') . "'></a>\n";

				if ($mode == 'Project') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=graphs&tab=graphs&query=1&region=' . $row['region'] . '&project=' . $row['project'] . '&cluster=' . $row['cluster'] . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "&template=0'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'gridblstat') . "'></a>";
				} elseif ($mode == 'Cluster') {
					$actions_url .= "<a href='" . html_escape('grid_lsdashboard.php?action=graphs&tab=graphs&query=1&region=' . $row['region'] . '&cluster=' . $row['cluster'] . '&feature=' . $row['feature'] . '&inuse=' . get_request_var('inuse')) . "&template=0'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'gridblstat') . "'></a>";
				}
			}

			form_alternate_row();

			form_selectable_cell($actions_url, $i, '1%');

			form_selectable_cell($row['cluster'], $i);
			form_selectable_cell($row['feature'], $i);
			form_selectable_cell($row['region'], $i);

			if ($mode == 'Project') {
				form_selectable_cell($row['type'] == 0 ? $row['project']:__('%s (FD)', $row['project'], 'gridblstat'), $i);
			}

			form_selectable_cell(number_format_i18n($row['inuse']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($row['reserve']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($row['free']), $i, '', 'right');

			if ($mode == 'Project') {
				form_selectable_cell(number_format_i18n($row['ndemand']), $i, '', 'right');
				form_selectable_cell($row['type'] == 1 ? number_format_i18n($row['over']):__('N/A', 'gridblstat'), $i, '', 'right');
				form_selectable_cell($row['type'] == 1 ? number_format_i18n($row['alloc']):__('N/A', 'gridblstat'), $i, '', 'right');
				form_selectable_cell($row['type'] == 0 ? number_format_i18n($row['acum_use'], $row['acum_use']>1024? 1:0):__('N/A', 'gridblstat'), $i, '', 'right');
				form_selectable_cell($row['type'] == 0 ? number_format_i18n($row['scaled_acum'], $row['scaled_acum']>1024? 1:0):__('N/A', 'gridblstat'), $i, '', 'right');
				form_selectable_cell($row['type'] == 0 ? number_format_i18n($row['avail']):__('N/A', 'gridblstat'), $i, '', 'right');
			} else {
				form_selectable_cell(number_format_i18n($row['demand']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['over']), $i, '', 'right');
				form_selectable_cell(number_format_i18n($row['alloc']), $i, '', 'right');
			}

			form_end_row();

			$i++;
		}
	} else {
		print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Cluster Records Found', 'gridblstat') . "</em></td></tr>";
	}

	html_end_box();

	if (cacti_sizeof($cluster_results)) {
		print $nav;
	}

}

function grid_cluster_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='feature' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					 <td>
                        <input type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
                    </td>

					<td>
						<?php print __('Mode', 'gridblstat');?>
					</td>
					<td>
					<select id='mode' onChange='applyFilter()'>
						<option value='1' <?php if (get_request_var('mode')== 1) {print 'selected';}?>><?php print __('Project', 'gridblstat');?></option>
						<option value='2' <?php if (get_request_var('mode')== 2) {print 'selected';}?>><?php print __('Cluster', 'gridblstat');?> </option>
					</select>
					</td>

					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridblstat');?>'>
							<input id='cancel' type='button' value='<?php print __('Clear', 'gridblstat');?>' name='clear' onClick='clearFilter()'>
						</span>
					</td>
				</tr>
				<tr>
					<td>
						<?php print __('Cluster', 'gridblstat');?>
					</td>
					<td>
						<select id='cluster' onChange='applyFilter()'>
							<option value='-1'<?php if (isset_request_var('cluster') && get_request_var('cluster') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<?php
							if (get_request_var('mode')==1) {
								$clusters = db_fetch_assoc('SELECT DISTINCT cluster
									FROM grid_blstat_cluster_use WHERE present = 1
									ORDER BY cluster');
							} else {
								$clusters = db_fetch_assoc('SELECT DISTINCT cluster
									FROM grid_blstat_clusters WHERE present = 1
									ORDER BY cluster');
							}

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['cluster'] .'"'; if (isset_request_var('cluster') && get_request_var('cluster') == $cluster['cluster']) { print ' selected'; } print '>' . $cluster['cluster'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php if (get_request_var('mode') == 1) { ?>
					<td id='td_project'>
						<?php print __('Project', 'gridblstat');?>
					</td>
					<td id='td_project1'>
						<input type="text" id='project' value='<?php print html_escape_request_var('project');?>'>
					</td>
					<?php } else {?>
					<td id='td_project' style='display:none'>
						<?php print __('Project', 'gridblstat');?>
					</td>
					<td id='td_project1' style='display:none'>
						<input type="text" id='project' value='<?php print html_escape_request_var('project');?>'>
					</td>
					<?php }?>
					<td>
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
						&nbsp; &nbsp; &nbsp;
						<input id='inuse' type='checkbox' onChange='applyFilter()' <?php print (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on' ? ' checked=checked':'');?>>
					</td>
					<td>
						<label for='inuse'><?php print __('In Use', 'gridblstat');?></label>
					</td>
				</tr>
			</table>
			</form>
			<script type="text/javascript">

			function applyFilter() {
				if ($('#mode').val() == 2) {
					$('#td_project').hide();
					$('#td_project1').hide();
				} else if ($('#mode').val() == 1) {
					$('#td_project').show();
					$('#td_project1').show();
				}

				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=clusters&tab=clusters&header=false';
				strURL += '&cluster=' + $('#cluster').val();
				strURL += '&refresh=' + $('#refresh').val();
				strURL += '&feature=' + $('#feature').val();
				strURL += '&region='  + $('#region').val();
				strURL += '&project=' + $('#project').val();
				strURL += '&inuse='   + $('#inuse').is(':checked');
				strURL += '&rows='    + $('#rows').val();
				strURL += '&mode='    + $('#mode').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=clusters&tab=clusters&clear=true&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function build_feature_details_display_array() {
	$display_text = array();

	$display_text[] = array(
		'display' => __('Feature', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Region', 'gridblstat'),
		'align' => 'left',
		'tip' => 'Region Name'
	);

	$display_text[] = array(
		'display' => __('LM Feature', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Mode', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('LS Total', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('LM Total', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Padding', 'gridblstat'),
		'align' => 'right',
		'tip' => __('LS Total - LM Total', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('In Use', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses in use in License Scheduler', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('LM Use', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses in use in FLEXlm/RLM', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Others', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses checked out by applications outside of License Scheduler', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Reserve', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses reserved', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Free', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses has free', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Demand', 'gridblstat'),
		'align' => 'right',
		'tip' => __('Numeric value indicating the number of tokens required', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Taskman', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of jobs that is run by the LSF Task Manager (taskman) tool outside of LSF, but is scheduled by License Scheduler', 'gridblstat')
	);

	return $display_text;
}

function build_project_details_display_array() {
	$display_text = array();

	$display_text[] = array(
		'display' => __('Project', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Region', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Share', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Own', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('In Use', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of jobs / licenses currently in use in License Scheduler for each project and the percentage of licenses in use in each project out of total licenses in use in the Service Domain', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Reserve', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Free', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Demand', 'gridblstat'),
		'align' => 'right'
	);

	return $display_text;
}

function build_reservers_details_display_array() {
	$display_text = array(
		'username' => array(
			'display' => __('User', 'gridblstat'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'maxtokens' => array(
			'display' => __('User Total', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'tokens' => array(
			'display' => __('Cluster Total', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'duration' => array(
			'display' => __('MAX Duration', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'gridblstat'),
			'align' => 'right',
			'sort' => 'ASC'
		)
	);

	return $display_text;
}

function build_violation_details_display_array() {
	$display_text = array(
		'username' => array(
			'display' => __('User', 'gridblstat'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'maxtokens' => array(
			'display' => __('User Total', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'tokens' => array(
			'display' => __('Cluster Total', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'duration' => array(
			'display' => __('MAX Duration', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'clustername' => array(
			'display' => __('Cluster', 'gridblstat'),
			'align' => 'right',
			'sort' => 'ASC'
		)
	);

	return $display_text;
}

function build_cluster_details_display_array() {
	$display_text = array();

	$display_text[] = array(
		'display' => __('Cluster', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Region', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('In Use', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses in use in License Scheduler for each project', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Reserve', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Free', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Demand', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Over', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Alloc', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Accum', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Scaled Accum', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Avail', 'gridblstat'),
		'align' => 'right'
	);

	return $display_text;
}

function build_cluster1_details_display_array() {
	$display_text = array();

	$display_text[] = array(
		'display' => __('Cluster', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Region', 'gridblstat'),
		'align' => 'left'
	);

	$display_text[] = array(
		'display' => __('Share', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Allocation (%%% of total)', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('In Use', 'gridblstat'),
		'align' => 'right',
		'tip' => __('The number of licenses in use in License Scheduler for each cluster', 'gridblstat')
	);

	$display_text[] = array(
		'display' => __('Reserve', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Over', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Peak', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Free', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Demand', 'gridblstat'),
		'align' => 'right'
	);

	$display_text[] = array(
		'display' => __('Buffer', 'gridblstat'),
		'align' => 'right'
	);

	return $display_text;
}

function grid_view_zen() {
	global $title, $report, $graph_timespans, $grid_search_types, $grid_top, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;

   	/* ================= input validation and session storage ================= */
    $filters = array(
		'top' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_setting('top', 5)
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_setting('refresh', read_grid_config_option('refresh_interval'))
			),
		'gtype' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('gtype', '0')
			),
		'lsid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'display' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('display', '0')
			),
		'predefined_timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('predefined_timespan', '7')
			),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'radio' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_user_setting('radio', 'others'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'duration',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'inuse' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => get_user_setting('inuse', 'true')
		)
	);

	validate_store_request_vars($filters, 'sess_gbs_featdb');
	/* ================= input validation ================= */

	html_start_box(__('Feature Dashboard %s', grid_blstat_header(), 'gridblstat'), '100%', '', '3', 'center', '');
	grid_feature_filter();
	html_end_box(true);

	$sql_where = '';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	if (get_request_var('feature') != '-1') {
		$lic_ids = array();

		$fwhere = '';
		$f_params = array();
		$lidwhere = '';
		$lid_params = array();
		if (!isempty_request_var('lsid')) {
			$fwhere = ' WHERE lsid = ?';
			$f_params[] = get_request_var('lsid');
			$lidwhere = 'lsid = ? AND ';
			$lid_params[] = get_request_var('lsid');
		}

		if (!isempty_request_var('feature')) {
			$fwhere .= (strlen($fwhere)? ' AND':' WHERE') . ' feature = ?';
			$f_params[] = get_request_var('feature');
		}

		$service_domains = array_rekey(
			db_fetch_assoc_prepared("SELECT service_domain
				FROM grid_blstat
				$fwhere", $f_params),
			'service_domain', 'service_domain'
		);

		if (cacti_sizeof($service_domains) == 1) {
			foreach($service_domains as $sda) {
				$sd = $sda;
			}
		} else {
			$sd = '';
		}

		if (cacti_sizeof($service_domains)) {
			$lic_ids = array_rekey(
				db_fetch_assoc_prepared("SELECT lic_id
					FROM grid_blstat_service_domains
					WHERE
					$lidwhere service_domain IN('" . implode("','", $service_domains) . "')", $lid_params),
				'lic_id', 'lic_id'
			);
		}

		if (cacti_sizeof($lic_ids)) {
			$on_clause = 'AND fu.service_id IN(' . implode(',', $lic_ids) . ')';
			$no_lics = false;
		} else {
			$on_clause = 'AND fu.service_id IN(0)';
			$no_lics = true;
		}

		$task_where = '';
		$task_params = array();
		$main_where = '';
		$main_params = array();
		$bs_params = array();

		if (!isempty_request_var('lsid')) {
			$task_where = 'WHERE lsid = ?';
			$task_params[] = get_request_var('lsid');
			$bs_where = 'WHERE lsid = ? AND present = 1';
			$bs_params[] = get_request_var('lsid');
		} else {
			set_request_var('lsid', 0);
			$bs_where = 'WHERE present = 1';
		}

		if (!isempty_request_var('feature')) {
			$task_where .= (strlen($task_where) ? ' AND ':'WHERE ') . 'feature=?';
			$task_params[] = get_request_var('feature');
			$main_where  = 'WHERE feature=?';
			$main_params[] = get_request_var('feature');
		}

		if (!isempty_request_var('region')) {
			$main_where .= (strlen($task_where) ? ' AND ':'WHERE ') . "region LIKE ?";
			$main_params[] = '%' . get_request_var('region') . '%';
		}

		/* GHE#584: implode lic_feature in LS dashboard to ensure one row per LS feature on dashbaord. */
		$details = db_fetch_row_prepared("SELECT region, feature, GROUP_CONCAT(DISTINCT lic_feature) AS lic_feature, service_id, type,
			inuse, reserve, free, demand, ttokens, others,
			SUM(feature_max_licenses) AS maxavail,
			SUM(feature_inuse_licenses) AS flexuse,
			SUM(feature_queued) AS queued,
			(SELECT count(*) FROM grid_blstat_tasks $task_where) AS taskman
			FROM (
				SELECT gbc.lsid, gbc.region, bc.feature, CONCAT('Project', IF(type=1,' (FD)','')) AS type,
				SUM(inuse) AS inuse,
				SUM(reserve) AS reserve,
				SUM(free) AS free,
				SUM(need+demand) AS demand,
				ttokens,
				others
				FROM grid_blstat_cluster_use AS bc
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid=bc.lsid
				INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others, SUM(total_tokens) AS ttokens FROM grid_blstat $bs_where GROUP BY feature) AS bs
				ON bc.feature=bs.feature
				AND bc.lsid=bs.lsid WHERE bc.present = 1
				GROUP BY bc.lsid, feature, others
				UNION
				SELECT gbc.lsid, gbc.region, bc.feature, 'Cluster' AS type,
				SUM(inuse) AS inuse,
				SUM(reserve) AS reserve,
				SUM(free) AS free,
				SUM(demand) AS demand,
				ttokens,
				others
				FROM grid_blstat_clusters AS bc
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid=bc.lsid
				INNER JOIN (SELECT lsid, feature, SUM(total_others) AS others, SUM(total_tokens) AS ttokens FROM grid_blstat $bs_where GROUP BY feature) AS bs
				ON bc.feature=bs.feature
				AND bc.lsid=bs.lsid WHERE bc.present = 1
				GROUP BY bc.lsid, feature, others) AS recordset
			INNER JOIN grid_blstat_feature_map AS fm
			ON fm.bld_feature=recordset.feature
			AND fm.lsid=recordset.lsid
			LEFT JOIN lic_services_feature_use AS fu
			ON fu.feature_name=fm.lic_feature
			$on_clause
			$main_where
			GROUP BY region, feature, type, others", array_merge($task_params, $bs_params, $bs_params, $main_params));
	}

	if (get_request_var('feature') == '') {
		html_start_box(__('Summary for Feature', 'gridblstat'), '100%', '', '3', 'center', '');

		$display_text = build_feature_details_display_array();
		html_header($display_text);

		print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Feature Provided') . '</em></td></tr>';

		html_end_box();

		return;
	}

	if (get_request_var('display') < 2) {
		html_start_box(__('Summary for Feature', 'gridblstat'), '100%', '', '3', 'center', '');
		$display_text = build_feature_details_display_array();

		html_header($display_text);

		$i = 0;
		if (cacti_sizeof($details)) {
			$fparts = explode('@', $details['feature']);
			$ffirst = $fparts[0];

			form_alternate_row();

			form_selectable_cell(html_escape($details['feature']), $i);
			form_selectable_cell(html_escape($details['region']), $i);
			form_selectable_cell(html_escape($details['lic_feature']), $i);
			form_selectable_cell(html_escape($details['type']), $i);
			form_selectable_cell($no_lics == true ? __('N/A', 'gridblstat'):number_format_i18n($details['ttokens']), $i, '', 'right');
			form_selectable_cell($no_lics == true ? __('N/A', 'gridblstat'):number_format_i18n($details['maxavail']), $i, '', 'right');

			if ($no_lics == true) {
				$value = __('N/A', 'gridblstat');
			} else {
				if ($details['ttokens'] - $details['maxavail'] < 0) {
					$value = '-';
				} else {
					$value = number_format_i18n($details['ttokens'] - $details['maxavail']);
				}
			}

			form_selectable_cell($value, $i, '', 'right');

			form_selectable_cell(filter_value(number_format_i18n($details['inuse']), '', 'grid_lsdashboard.php?action=users&tab=users&lsid=' . get_request_var('lsid') . '&region=' . $details['region'] . '&cluster=-1&filter=&user=&host=&sd=-2&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');

			form_selectable_cell(filter_value($no_lics == true ? __('N/A', 'gridblstat'):number_format_i18n($details['flexuse']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&lsid=' . get_request_var('lsid') . '&region=' . $details['region'] . '&ffeature=' . $details['lic_feature'] . '&sd=' . urlencode($sd) . '&lsf=-1&host=&user=&except=-1'), $i, '', 'right');

			form_selectable_cell(filter_value(number_format_i18n($details['others']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&lsid=' . get_request_var('lsid') . '&region=' . $details['region'] . '&ffeature=' . $details['lic_feature'] . '&host=&user=&except=-2&lsf=-1&sd=' . urlencode($sd)), $i, '', 'right');
			form_selectable_cell(filter_value(number_format_i18n($details['reserve']), '', 'grid_lsdashboard.php?action=users&tab=users&lsid=' . get_request_var('lsid') . '&region=' . $details['region'] . '&cluster=-1&filter=&user=&host=&sd=UNKNOWN&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');
			form_selectable_cell(number_format_i18n($details['free']), $i, '', 'right');
			form_selectable_cell(filter_value( number_format($details['demand']), '', 'grid_lsdashboard.php?action=users&tab=users&lsid=' . get_request_var('lsid') . '&region=' . $details['region'] . '&cluster=-1&filter=&user=&host=&sd=-3&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');
			form_selectable_cell(number_format_i18n($details["taskman"]), $i, '', 'right');

			form_end_row();

			$i++;
		} else {
			print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Such Feature Found', 'gridblstat') . '</em></td></tr>';
		}

		html_end_box();

		if (!cacti_sizeof($details)) return;

		/* projects and violation details */
		print "<table class='cactiTable'>";

		if (substr($details["type"],0,7) == "Project") {
			print "<tr><td valign='top' width='60%' style='padding-right:2px;'>\n";

			html_start_box(__('License Project Usage', 'gridblstat'), '100%', '', '3', 'center', '');
			$display_text = build_project_details_display_array();

			html_header($display_text);

			if (isset_request_var('inuse') && (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on')) {
				$sql_having = 'HAVING inuse>0 OR reserve>0 OR demand>0 OR jobs>0';
			} else {
				$sql_having = '';
			}

			if (get_request_var('region') != '') {
				$sql_pwhere = "AND gbc.region LIKE '%" . get_request_var('region') . "%' AND gbp.present = 1";
			} else {
				$sql_pwhere = 'AND gbp.present = 1';
			}

			$projects = db_fetch_assoc("SELECT gbc.region, gbp.project,
				gbp.service_domain, share, SUM(own) AS own,
				SUM(inuse) AS inuse, SUM(reserve) AS reserve, SUM(free) AS free,
				jobs AS jobs, SUM(demand) AS demand
				FROM grid_blstat_projects AS gbp
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid=gbp.lsid
				LEFT JOIN (
					SELECT gbc.lsid, project, resource AS feature,
					service_domain, COUNT(jobid) AS jobs
					FROM grid_blstat_users AS gbu
					INNER JOIN grid_blstat_collectors AS gbc
					ON gbc.lsid=gbu.lsid
					GROUP BY gbu.lsid, project, feature, service_domain
				) AS jobs
				ON gbp.project=jobs.project
				AND gbp.lsid=jobs.lsid
				AND gbp.service_domain=jobs.service_domain
				AND gbp.feature=jobs.feature
				WHERE gbp.feature='" . $details['feature'] . "'
				$sql_pwhere
				GROUP BY gbc.region, gbp.service_domain, gbp.project
				$sql_having
				ORDER BY gbc.region, gbp.service_domain, inuse DESC");

			$totals = array_rekey(
				db_fetch_assoc("SELECT gbp.lsid,
					service_domain, SUM(inuse) AS inuse
					FROM grid_blstat_projects AS gbp
					INNER JOIN grid_blstat_collectors AS gbc
					ON gbp.lsid=gbc.lsid
					WHERE feature='" . $details['feature'] . "'
					$sql_pwhere
					GROUP BY gbp.lsid, service_domain"),
				'service_domain', 'inuse'
			);

			$i = 0;
			$pp  = '';
			$psd = '';

			if (cacti_sizeof($projects)) {
				$fparts = explode('@', $details['feature']);
                $ffirst = $fparts[0];

				foreach($projects as $p) {
					if ($psd != $p['service_domain']) {
						print "<tr class='tableHeader'><th colspan='8'>" . __('Service Domain: %s', $p['service_domain'], 'gridblstat') . '</th></tr>';
					}

					form_alternate_row();

					form_selectable_cell(filter_value($p['project'], '', 'grid_lsdashboard.php?action=graphs&tab=graphs&query=1&region=' . get_request_var('region') . '&project=' . $p['project'] . '&feature=' . get_request_var('feature') . '&inuse=' . get_request_var('inuse') . '&cluster=-1&template=0'), $i, '', $pp == $p['project'] && $psd == $p['service_domain'] ? 'background-color:grey':'', __('View Project Graphs', 'gridblstat'));

					form_selectable_cell(html_escape($p['region']), $i);
					form_selectable_cell($p['share'] . '%', $i, '', 'right');
					form_selectable_cell($p['own'] > 0 ? number_format_i18n($p['own']):'-', $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($p['jobs']) . '/' . number_format_i18n($p['inuse']) . ' (' . ($totals[$p['service_domain']] > 0 ? round(($p['inuse'] / $totals[$p['service_domain']]) * 100,0) . '%)':'-)'), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&sd=' . urlencode($p['service_domain']) . '&resource=' . $ffirst . '&project=' . $p['project'] . '&rows=-1'), $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($p['reserve']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&sd=UNKNOWN&resource=' . $ffirst . '&project=' . $p['project'] . '&rows=-1'), $i, '', 'right');

					form_selectable_cell(number_format_i18n($p['free']), $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($p['demand']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=-1&filter=&user=&host=&sd=-3&resource=' . $details['feature'] . '&project=' . $p['project'] . '&rows=-1'), $i, '', 'right');
					form_end_row();

					$pp  = $p['project'];
					$psd = $p['service_domain'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Projects Found', 'gridblstat') . '</em></td></tr>';
			}

			html_end_box(false);
		} else {
			print "<tr><td valign='top' width='60%' style='padding-right:2px;'>\n";

			html_start_box(__('Cluster Usage', 'gridblstat'), '100%', '', '3', 'center', '');

			$display_text = build_cluster1_details_display_array();

			html_header($display_text);

			$sql_where = '';
			if (isset_request_var('inuse') && (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on')) {
				$sql_where = 'AND (demand > 0 OR inuse > 0 OR reserve > 0)';
			}

			$cluse = db_fetch_assoc_prepared("SELECT gbc.region, gbcl.*
				FROM grid_blstat_clusters  AS gbcl
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbcl.lsid=gbc.lsid
				WHERE feature = ?
				AND gbcl.lsid = ?
				$sql_where
				ORDER BY region, cluster, inuse DESC",
				array($details['feature'], get_request_var('lsid')));

			$max_allocation = db_fetch_cell_prepared("SELECT SUM(alloc)
				FROM grid_blstat_clusters  AS gbcl
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbcl.lsid=gbc.lsid
				WHERE feature = ?
				AND gbcl.lsid = ?
				$sql_where",
				array($details['feature'], get_request_var('lsid')));

			$i  = 0;
			$psd = '';
			if (cacti_sizeof($cluse)) {
				$fparts = explode('@', $details['feature']);
            	$ffirst = $fparts[0];

				foreach($cluse as $c) {
					if ($psd != $c['service_domain']) {
						print "<tr class='tableHeader'><th colspan='12'>" . __('Service Domain: %s', $c['service_domain'], 'gridblstat') . '</th></tr>';
					}

					form_alternate_row();

					form_selectable_cell(filter_value($c['cluster'], '', 'grid_lsdashboard.php?action=graphs&tab=graphs&query=1&region=' . get_request_var('region') . '&cluster=' . $c['cluster'] . '&feature=' . get_request_var('feature') . '&inuse=' . get_request_var('inuse') . '&template=0'), $i, '', 'left', __('View Cluster Graphs', 'gridblstat'));

					form_selectable_cell(html_escape($c['region']), $i);
					form_selectable_cell(number_format_i18n($c['share'], 2) . '%', $i, '', 'right');

					if ($max_allocation > 0) {
						form_selectable_cell(number_format_i18n($c['alloc']) . ' (' . round(($c['alloc']/$max_allocation) * 100, 2)   . '%)', $i, '', 'right');
					} else {
						form_selectable_cell(__('N/A', 'gridblstat'), $i, '', '');
					}

					form_selectable_cell(filter_value(number_format_i18n($c['inuse']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=-2&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');
					form_selectable_cell(filter_value(number_format_i18n($c['reserve']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=UNKNOWN&resource=' . $ffirst . '&project=&rows=-1'), $i, '', 'right');

					form_selectable_cell(number_format_i18n($c['over']), $i, '', 'right');
					form_selectable_cell(number_format_i18n($c['peak']), $i, '', 'right');
					form_selectable_cell(number_format_i18n($c['free']), $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($c['demand']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=-3&resource=' . $details['feature'] . '&project=&rows=-1'), $i, '', 'right');
					form_selectable_cell(number_format_i18n($c['buffer']), $i, '', 'right');
					form_end_row();

					$psd = $c["service_domain"];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Cluster Use Found', 'gridblstat') . '</em></td></tr>';
			}

			html_end_box();
		}

		print '</td>';
		print "<td style='width:40%;vertical-align:top;white-space:nowrap;padding-left:2px;'>";

		$radio = "</span>&nbsp;<span id='radio' style='font-size:0.7em;'>
				<input type='radio' id='demand' " . (!isset_request_var('radio') || get_request_var('radio') == 'demand' ? "checked='checked'":"") . " name='radio'>
				<label for='demand'>" . __('Demanding', 'gridblstat') . "</label>
				<input type='radio' id='reserve' " . (isset_request_var('radio') && get_request_var('radio') == 'reserve' ? "checked='checked'":"") . " name='radio'>
				<label for='reserve'>" . __('Reserving', 'gridblstat') . "</label>
				<input type='radio' id='others' " . (!isset_request_var('radio') || get_request_var('radio') == 'others' ? "checked='checked'":"") . " name='radio'>
				<label for='others'>" . __('Others', 'gridblstat') . "</label>
				<input type='radio' id='lm' " . (isset_request_var('radio') && get_request_var('radio') == 'lm' ? "checked='checked'":"") . " name='radio'>
				<label for='lm'>" . __('LMTotals', 'gridblstat') . "</label>
			</span><span>";

		if (get_request_var('radio') == 'others') {
			html_start_box(__('Others (%s + Minutes)', (read_config_option('grid_blstat_exception_time') / 60), 'gridblstat') . $radio, '100%', '', '3', 'center', '');

			$display_text = build_violation_details_display_array();

			html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

			$sql_order = get_order_string();
			$sql_limit = ' LIMIT ' . get_request_var('top');

			$violators = db_fetch_assoc_prepared("SELECT recordset.username, gh.host,
				gc.lsf_clustername AS clustername, sum(tokens) AS tokens,
				maxtokens, MAX(duration) AS duration
				FROM grid_hosts AS gh
				RIGHT JOIN (
					SELECT fu.username, fu.hostname AS hostname, fu.tokens_acquired AS tokens,
					UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date) AS duration
					FROM (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
					RIGHT JOIN lic_services_feature_details AS fu
					ON fu.username = bu.user
					AND fu.hostname = bu.host
					WHERE fu.feature_name IN ('" . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . "')
					AND service_id IN(SELECT lic_id FROM grid_blstat_service_domains WHERE lsid = ?)
					AND bu.cnt IS NULL
				) AS recordset
				ON gh.host = recordset.hostname
				INNER JOIN grid_clusters AS gc
				ON gh.clusterid = gc.clusterid
				INNER JOIN (SELECT
					fu.username,
					SUM(fu.tokens_acquired) AS maxtokens
					FROM (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
					RIGHT JOIN lic_services_feature_details AS fu
					ON fu.username = bu.user
					AND fu.hostname = bu.host
					WHERE fu.feature_name IN ('" . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . "')
					AND service_id IN(SELECT lic_id FROM grid_blstat_service_domains WHERE lsid = ?)
					AND bu.cnt IS NULL
					GROUP BY fu.username
				) AS maxrs
				ON maxrs.username = recordset.username
				WHERE duration > ?
				GROUP BY clustername, recordset.username
				$sql_order
				$sql_limit",
				array(
					get_request_var('lsid'),
					get_request_var('lsid'),
					read_config_option('grid_blstat_exception_time')
				)
			);

			$i = 0;
			$pv = 'truffles';
			if (cacti_sizeof($violators)) {
				foreach($violators as $v) {
					if ($v['clustername'] == '') {
						$color = db_fetch_cell_prepared('SELECT hex
							FROM colors
							WHERE id = ?',
							array(read_config_option('grid_efficiency_alarm_bgcolor')));

						print "tr class='selectable' style='baground-color:#$color'>";
					} else {
						form_alternate_row();
					}

					if (!empty($v['username'])) {
						$user = $v['username'];
					} else {
						$user = __('Undefined', 'gridblstat');
					}

					if ($pv == $v["username"]) {
						form_selectable_cell($user, $i, '', 'background-color=grey');
					} else {
						form_selectable_cell($user, $i);
					}

					form_selectable_cell(filter_value(number_format_i18n($v['maxtokens']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&ffeature=' . $details['lic_feature'] . '&host=&user=' . $v['username'] . '&except=-2&lsf=-1&sd=' . urlencode($sd)), $i, '', 'right');

					form_selectable_cell(number_format_i18n($v['tokens']), $i, '', 'right');
					form_selectable_cell($v['duration'] > 1000000000 ? __('RESERVED', 'gridblstat'):display_job_time($v['duration'], 2, false), $i, '', 'right');

					if (empty($v['clustername'])) {
						$clusters = db_fetch_assoc_prepared('SELECT recordset.username, gh.host,
							gc.lsf_clustername AS clustername,
							SUM(tokens) AS tokens, MAX(duration) AS duration
							FROM grid_hostinfo AS gh
							INNER JOIN grid_clusters AS gc
							ON gh.clusterid=gc.clusterid
							RIGHT JOIN (
								SELECT fu.username, fu.hostname AS hostname,
								fu.tokens_acquired AS tokens,
								UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date) AS duration
								FROM (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
								RIGHT JOIN lic_services_feature_details AS fu
								ON fu.username=bu.user AND fu.hostname=bu.host
								WHERE fu.feature_name IN (\'' . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . '\')
								AND service_id IN(
									SELECT lic_id
									FROM grid_blstat_service_domains
									WHERE lsid = ?)
								AND username = ?
								AND bu.cnt IS NULL) AS recordset
							ON gh.host=recordset.hostname
							WHERE duration > ?
							AND isServer = 0
							GROUP BY clustername, recordset.username
							ORDER BY duration DESC, tokens DESC',
							array(
								get_request_var('lsid'),
								$v['username'],
								read_config_option('grid_blstat_exception_time')
							)
						);

						if (cacti_sizeof($clusters)) {
							$clusters = __('LSFCLIENT', 'gridblstat');
						} else {
							$clusters = __('UNKNOWN', 'gridblstat');
						}

						form_selectable_cell($clusters, $i, '', 'right');
					} else {
						form_selectable_cell($v['clustername'], $i, '', 'right');
					}

					form_end_row();

					$pv = $v['clustername'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Other Exceptions Found', 'gridblstat') . "</em></td></tr>";
			}

			html_end_box(false);
		} elseif (get_request_var('radio') == 'reserve') {
			html_start_box(__('Reservers (Current) %s', $radio, 'gridblstat'), '100%', '', '3', 'center', '');

			$display_text = build_reservers_details_display_array();

			html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

			$sql_order = get_order_string();
			$sql_limit = ' LIMIT ' . get_request_var('top');

			$violators = db_fetch_assoc_prepared("SELECT recordset.user AS username, host, gc.lsf_clustername AS clustername,
				maxtokens, SUM(tokens) AS tokens, MAX(duration) AS duration
				FROM grid_clusters AS gc
				INNER JOIN (SELECT bu.clusterid, bu.user,
					bu.host, bu.rusage AS tokens,
					CAST(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(bu.start_time) AS signed) AS duration
					FROM grid_blstat_users AS bu
					INNER JOIN grid_blstat_collectors AS gbc
					ON bu.lsid = gbc.lsid
					WHERE bu.resource = ?
					AND gbc.lsid = ?
					AND bu.service_domain = 'UNKNOWN'
				) AS recordset
				ON recordset.clusterid=gc.clusterid
				INNER JOIN (SELECT user, SUM(tokens) AS maxtokens
					FROM (
						SELECT bu.user, bu.rusage AS tokens
						FROM grid_blstat_users AS bu
						INNER JOIN grid_blstat_collectors AS gbc
						ON bu.lsid = gbc.lsid
						WHERE bu.resource = ?
						AND gbc.lsid = ?
						AND bu.service_domain = 'UNKNOWN'
					) AS rs
					GROUP BY user) AS umax
				ON umax.user = recordset.user
				GROUP BY clustername, recordset.user
				$sql_order
				$sql_limit",
				array(
					$details['feature'],
					get_request_var('lsid'),
					$details['feature'],
					get_request_var('lsid')
				)
			);

			$i = 0;
			$pv = 'truffles';

			if (cacti_sizeof($violators)) {
				foreach($violators as $v) {
					if ($v['clustername'] == '') {
						$color = db_fetch_cell_prepared('SELECT hex
							FROM colors WHERE id = ?',
							array(read_config_option('grid_efficiency_alarm_bgcolor')));

						print "tr class='selectable' style='baground-color:#$color'>";
					} else {
						form_alternate_row();
					}

					if (!empty($v['username'])) {
						$user = $v['username'];
					} else {
						$user = __('Undefined', 'gridblstat');
					}

					if ($pv == $v['username']) {
						form_selectable_cell($user, $i, '', 'background-color=grey');
					} else {
						form_selectable_cell($user, $i);
					}

					form_selectable_cell(filter_value(number_format_i18n($v['maxtokens']), '', 'grid_lsdashboard.php?query=1&action=users&tab=users&sd=UNKNOWN&resource=' . $details['feature'] . '&host=&user=' . $v['username'] . '&project=&sd=UNKNOWN&cluster=-1'), $i, '', 'right');

					form_selectable_cell(filter_value(number_format($v['tokens']), '', 'grid_lsdashboard.php?query=1&action=users&tab=users&sd=UNKNOWN&resource=' . $details['feature'] . '&host=&user=' . $v['username'] . '&project=&sd=UNKNOWN&cluster=' . $v['clustername']), $i, '', 'right');

					form_selectable_cell($v['duration'] > 1000000000 ? __('RESERVED', 'gridblstat'):display_job_time($v['duration'], 2, false), $i, '', 'right');

					if (empty($v['clustername'])) {
						$clusters = db_fetch_assoc_prepared('SELECT recordset.user, gh.host, gc.lsf_clustername AS clustername,
							SUM(tokens) AS tokens, MAX(duration) AS duration
							FROM grid_hostinfo AS gh
							INNER JOIN grid_clusters AS gc
							ON gh.clusterid=gc.clusterid
							RIGHT JOIN (
								SELECT bu.user, bu.host, bu.rusage AS tokens,
								CAST(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(bu.start_time) AS signed) AS duration
								FROM grid_blstat_users AS bu
								INNER JOIN grid_blstat_collectors AS gbc
								ON gbc.lsid=bu.lsid
								RIGHT JOIN grid_jobs AS gj
								ON gj.jobid=bu.jobid
								AND gj.indexid=bu.indexid
								AND gj.clusterid=bu.clusterid
								WHERE bu.resource = ?
								AND bu.lsid = ?
								AND bu.service_domain = "UNKNOWN"
								AND user = ?
							) AS recordset
							ON gh.host = recordset.host
							WHERE duration > ?
							AND isServer = 0
							GROUP BY clustername, recordset.user
							ORDER BY duration DESC, tokens DESC',
							array(
								$details['feature'],
								get_request_var('lsid'),
								$v['username'],
								read_config_option('grid_blstat_exception_time')
							)
						);

						if (cacti_sizeof($clusters)) {
							$clusters = 'LSFCLIENT';
						} else {
							$clusters = 'UNKNOWN';
						}

						print "<td align='right'>" . $clusters . "</td>\n";
						form_selectable_cell($clusters, $i, '', 'right');
					} else {
						form_selectable_cell($v['clustername'], $i, '', 'right');
					}

					form_end_row();

					$pv = $v['clustername'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Reservation Exceptions Found', 'gridblstat') . "</em></td></tr>";
			}

			html_end_box(false);
		} elseif (get_request_var('radio') == 'demand') {
			html_start_box(__('Cluster Demand %s', $radio, 'gridblstat'), '100%', '', '3', 'center', '');

			$display_text = build_violation_details_display_array();

			html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

			if (get_request_var('region') != '') {
				$clusters = get_region_clusters(get_request_var('region'));
				$ls_count = get_ls_count();

				if (cacti_sizeof($clusters) && $ls_count > 1) {
					$clwhere = 'AND gj.clusterid IN(' . implode(',', $clusters) . ')';
				} else {
					$clwhere = '';
				}
			} else {
				$clwhere = '';
			}

			$parts = explode('@', get_request_var('feature'));

			$pendreason=read_config_option('grid_pendreason_full_collection');

			if ($pendreason != 'on') {
				$sql_order = get_order_string();
				$sql_limit = ' LIMIT ' . get_request_var('top');

				$sql = "SELECT bu.user AS username, bu.clustername, COUNT(bu.num_cpus) AS tokens,
					maxtokens, MAX(bu.pend_time) AS duration
					FROM (
						SELECT gj.num_cpus, gj.pend_time, gc.lsf_clustername AS clustername, gj.clusterid, gj.user
						FROM grid_jobs AS gj
						INNER JOIN grid_clusters AS gc
						ON gj.clusterid=gc.clusterid
						WHERE (gj.pendReasons LIKE '%Job\'s requirements for reserving resource (".$parts[0].") not satisfied%'
						OR gj.pendReasons LIKE '%Resource (".$parts[0].") reserved for SLA guarantees, or not enough resources available%')
						$clwhere
						AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')
					) AS bu
					INNER JOIN (
						SELECT user, COUNT(num_cpus) AS maxtokens, gj.stat, gj.pendReasons
						FROM grid_jobs AS gj
						WHERE (gj.pendReasons LIKE '%Job\'s requirements for reserving resource (".$parts[0].") not satisfied%'
						OR gj.pendReasons LIKE '%Resource (".$parts[0].") reserved for SLA guarantees, or not enough resources available%')
						$clwhere
						AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP') GROUP BY user
					) AS bmu
					ON bmu.user=bu.user
					GROUP BY clustername, username
					$sql_order
					$sql_limit";
			} else {
				$sql_order = get_order_string();
				$sql_limit = ' LIMIT ' . get_request_var('top');

				$sql = "SELECT bu.user AS username, bu.clustername, COUNT(bu.num_cpus) AS tokens,
					maxtokens, MAX(bu.pend_time) AS duration
					FROM (
						SELECT gj.num_cpus, gj.pend_time, gc.lsf_clustername AS clustername, gj.clusterid, gj.user
						FROM grid_jobs AS gj
						INNER JOIN grid_clusters AS gc
						ON gj.clusterid=gc.clusterid
						INNER JOIN grid_jobs_pendreasons AS gjp
						ON gj.clusterid=gjp.clusterid
						AND gj.jobid=gjp.jobid
						AND gj.indexid=gjp.indexid
						AND gj.submit_time=gjp.submit_time
						WHERE reason IN (2601,6000)
						$clwhere
						AND subreason='" . $parts[0] . "'
						AND gjp.end_time='0000-00-00'
						AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP')
					) AS bu
					INNER JOIN (
						SELECT user, COUNT(num_cpus) AS maxtokens
						FROM grid_jobs AS gj
						INNER JOIN grid_jobs_pendreasons AS gjp
						ON gj.clusterid=gjp.clusterid
						AND gj.jobid=gjp.jobid
						AND gj.indexid=gjp.indexid
						AND gj.submit_time=gjp.submit_time
						WHERE reason IN (2601,6000)
						$clwhere
						AND subreason='" . $parts[0] . "'
						AND gjp.end_time='0000-00-00'
						AND gj.stat IN ('PEND','SSUSP','PSUSP','USUSP') GROUP BY user
					) AS bmu
					ON bmu.user=bu.user
					GROUP BY clustername, username
					$sql_order
					$sql_limit";
			}

			$violators = db_fetch_assoc($sql);

			//print $sql;

			$i = 0;
			$pv = 'truffles';
			if (cacti_sizeof($violators)) {
				foreach($violators as $v) {
					if ($v['clustername'] == '') {
						$color = db_fetch_cell_prepared('SELECT hex
							FROM colors
							WHERE id = ?',
							array(read_config_option('grid_efficiency_alarm_bgcolor')));

						print "tr class='selectable' style='baground-color:#$color'>";
					} else {
						form_alternate_row();
					}

					if (!empty($v['username'])) {
						$user = $v['username'];
					} else {
						$user = __('Undefined', 'gridblstat');
					}

					if ($pv == $v['username']) {
						form_selectable_cell($user, $i, '', 'background-color=grey');
					} else {
						form_selectable_cell($user, $i);
					}

					form_selectable_cell(filter_value(number_format_i18n($v['maxtokens']), '', 'grid_lsdashboard.php?action=users&tab=users&query=1&resource=' . get_request_var('feature') . '&cluster=-1&sd=-3&user=' . $v['username']), $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($v['tokens']), '', 'grid_lsdashboard.php?action=users&tab=users&query=1&resource=' . get_request_var('feature') . '&cluster=' . $v['clustername'] . '&sd=-3&user=' . $v['username']), $i, '', 'right');

					form_selectable_cell($v['duration'] > 1000000000 ? __('RESERVED', 'gridblstat'):display_job_time($v['duration'], 2, false), $i, '', 'right');
					form_selectable_cell($v['clustername'], $i, '', 'right');

					form_end_row();

					$pv = $v['clustername'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Demanding Users Found', 'gridblstat') . "</em></td></tr>";
			}

			html_end_box(false);
		} else {
			html_start_box(__('LM Totals %s', $radio, 'gridblstat'), '100%', '', '3', 'center', '');

			$display_text = build_violation_details_display_array();
			html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

			$sql_order = get_order_string();
			$sql_limit = ' LIMIT ' . get_request_var('top');

			$violators = db_fetch_assoc_prepared("SELECT recordset.username, gh.host, gc.lsf_clustername AS clustername,
				SUM(tokens) AS tokens, maxtokens, MAX(duration) AS duration
				FROM grid_hosts AS gh
				RIGHT JOIN (
					SELECT fu.username, fu.hostname AS hostname,
					fu.tokens_acquired AS tokens,
					UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date) AS duration
					FROM lic_services_feature_details AS fu
					WHERE fu.feature_name IN ('" . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . "')
					AND service_id IN (
						SELECT lic_id
						FROM grid_blstat_service_domains
						WHERE lsid = ?
						AND service_domain IN ('" . implode("','", $service_domains) . "')
					)
				) AS recordset
				ON gh.host = recordset.hostname
				INNER JOIN grid_clusters AS gc
				ON gh.clusterid = gc.clusterid
				INNER JOIN (SELECT
					fu.username,
					SUM(fu.tokens_acquired) AS maxtokens
					FROM lic_services_feature_details AS fu
					WHERE fu.feature_name IN ('" . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . "')
					AND service_id IN (
						SELECT lic_id
						FROM grid_blstat_service_domains
						WHERE lsid = ?
						AND service_domain IN ('" . implode("','", $service_domains) . "')
					)
					GROUP BY fu.username) AS maxrs
				ON maxrs.username=recordset.username
				GROUP BY clustername, recordset.username
				$sql_order
				$sql_limit",
				array(
					get_request_var('lsid'),
					get_request_var('lsid')
				)
			);

			$i = 0;
			$pv = 'truffles';

			if (cacti_sizeof($violators)) {
				foreach($violators as $v) {
					if ($v['clustername'] == '') {
						$color = db_fetch_cell_prepared('SELECT hex
							FROM colors
							WHERE id = ?',
							array(read_config_option('grid_efficiency_alarm_bgcolor')));

						print "tr class='selectable' style='baground-color:#$color'>";
					} else {
						form_alternate_row();
					}

					if (!empty($v['username'])) {
						$user = $v['username'];
					} else {
						$user = __('Undefined', 'gridblstat');
					}

					if ($pv == $v['username']) {
						form_selectable_cell($user, $i, '', 'background-color=grey');
					} else {
						form_selectable_cell($user, $i);
					}

					form_selectable_cell(number_format_i18n($v['maxtokens']), $i, '', 'right');

					form_selectable_cell(filter_value(number_format_i18n($v['tokens']), '', 'grid_lsdashboard.php?action=checkouts&tab=checkouts&query=1&ffeature=' . $details['lic_feature'] . '&host=&user=' . $v['username'] . '&except=-1&lsf=-1&sd=' . urlencode($sd)), $i, '', 'right');

					form_selectable_cell($v['duration'] > 1000000000 ? __('RESERVED', 'gridblstat'):display_job_time($v['duration'], 2, false), $i, '', 'right');

					if (empty($v['clustername'])) {
						$clusters = db_fetch_assoc_prepared('SELECT recordset.username, gh.host,
							gc.lsf_clustername AS clustername, sum(tokens) AS tokens, MAX(duration) AS duration
							FROM grid_hostinfo AS gh
							INNER JOIN grid_clusters AS gc
							ON gh.clusterid=gc.clusterid
							RIGHT JOIN (
								SELECT fu.username, fu.hostname AS hostname, fu.tokens_acquired AS tokens,
								UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date) AS duration
								FROM (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
								RIGHT JOIN lic_services_feature_details AS fu
								ON fu.username = bu.user AND fu.hostname=bu.host
								WHERE fu.feature_name IN (\'' . implode("','", preg_split('/[ ,]/', $details['lic_feature'], -1, PREG_SPLIT_NO_EMPTY)) . '\')
								AND service_id IN (
									SELECT lic_id
									FROM grid_blstat_service_domains
									WHERE lsid = ?
								)
								AND username = ?
								AND bu.cnt IS NULL) AS recordset
							ON gh.host = recordset.hostname
							WHERE duration > ?
							AND isServer = 0
							GROUP BY clustername, recordset.username
							ORDER BY duration DESC, tokens DESC',
							array(
								get_request_var('lsid'),
								 $v['username'],
								read_config_option('grid_blstat_exception_time')
							)
						);

						if (cacti_sizeof($clusters)) {
							$clusters = __('LSFCLIENT', 'gridblstat');
						} else {
							$clusters = __('UNKNOWN', 'gridblstat');
						}

						form_selectable_cell($clusters, $i, '', 'right');
					} else {
						form_selectable_cell($v['clustername'], $i, '', 'right');
					}

					form_end_row();

					$pv = $v['clustername'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No LM Checkouts Found', 'gridblstat') . "</em></td></tr>";
			}

			html_end_box(false);
		}

		print "</td></tr>\n";
		print "</table><br>\n";

		if (substr($details['type'],0,7) == 'Project') {
			html_start_box(__('Cluster Usage', 'gridblstat'), '100%', '', '3', 'center', '');

			$display_text = build_cluster_details_display_array();
			html_header($display_text);

			$sql_where = ' AND gbcu.present = 1';
			if (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on') {
				$sql_where .= ' AND (need > 0 OR inuse > 0 OR `over` > 0 OR `reserve` > 0 OR acum_use > 0 OR scaled_acum > 0)';
			}

			if (!isempty_request_var('lsid')) {
				$sql_where .= ' AND gbc.lsid=' . get_request_var('lsid');
			}

			$cluse = db_fetch_assoc('SELECT gbc.region, gbcu.*
				FROM grid_blstat_cluster_use AS gbcu
				INNER JOIN grid_blstat_collectors AS gbc
				ON gbc.lsid=gbcu.lsid
				WHERE feature=' . db_qstr($details['feature']) . "
				$sql_where
				ORDER BY project, inuse DESC");

			$i  = 0;
			$pp = '';
			if (cacti_sizeof($cluse)) {
				$fparts = explode('@', $details['feature']);
				$ffirst = $fparts[0];

				foreach($cluse as $c) {
					if ($pp != $c['project']) {
						print '<tr class="tableHeader">';
						print '<td colspan="' . (cacti_sizeof($display_text)) . '">' . __('Project: %s', $c['project']) . '</th>';
						print '</tr>';
					}

					form_alternate_row();

					form_selectable_cell(filter_value($c['cluster'], '', 'grid_lsdashboard.php?action=graphs&tab=graphs&query=1&region=' . get_request_var('region') . '&cluster=' . $c['cluster'] . '&project=' . $c['project'] . '&feature=' . get_request_var('feature') . '&inuse=' . get_request_var('inuse') . '&template=0'), __('View Cluster Graphs', 'gridblstat'), $i);
					form_selectable_cell(html_escape($c['region']), $i);
					form_selectable_cell(filter_value(number_format_i18n($c['inuse']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=-2&resource=' . $ffirst . '&project=' . $c['project'] . '&rows=-1'), $i, '', 'right');
					form_selectable_cell(filter_value(number_format_i18n($c['reserve']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=UNKNOWN&resource=' . $ffirst . '&project=' . $c['project'] . '&rows=-1'), $i, '', 'right');
					form_selectable_cell(number_format($c['free']), $i, '', 'right');
					form_selectable_cell(filter_value(number_format_i18n($c['need']), '', 'grid_lsdashboard.php?action=users&tab=users&cluster=' . $c['cluster'] . '&filter=&user=&host=&sd=-3&resource=' . $details['feature'] . '&project=' . $c['project'] . '&rows=-1'), $i, '', 'right');
					form_selectable_cell($c['type'] == 1 ? number_format($c['over']):__('N/A', 'gridblstat'), $i, '', 'right');
					form_selectable_cell($c['type'] == 1 ? number_format($c['alloc']):__('N/A', 'gridblstat'), $i, '', 'right');
					form_selectable_cell($c['type'] == 0 ? number_format($c['acum_use']):__('N/A', 'gridblstat'), $i, '', 'right');
					form_selectable_cell($c['type'] == 0 ? number_format($c['scaled_acum']):__('N/A', 'gridblstat'), $i, '', 'right');
					form_selectable_cell($c['type'] == 0 ? number_format($c['avail']):__('N/A', 'gridblstat'), $i, '', 'right');

					form_end_row();

					$pp = $c['project'];

					$i++;
				}
			} else {
				print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Cluster Project Use Found', 'gridblstat') . "</em></td></tr>";
			}

			html_end_box();
		}
	}

	if (get_request_var('display') == 0 || get_request_var('display') == 2) {
		html_start_box(__('Custom Graphs', 'gridblstat'), '100%', '', '3', 'center', '');

		?>
		<tr class='odd noprint'>
			<td>
				<form id='form_preset' action='grid_lsdashboard.php' method='post'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Presets', 'gridblstat');?>
						</td>
						<td>
							<select id='predefined_timespan' onChange='drawCharts()'>
								<?php
								if (cacti_sizeof($graph_timespans)) {
									foreach($graph_timespans as $value => $name) {
										if ($value > 19) break;
										print "<option value='$value'"; if (isset_request_var('predefined_timespan') && get_request_var('predefined_timespan') == $value) { print ' selected'; } print '>' . title_trim($graph_timespans[$value], 40) . '</option>';
									}
								}
								?>
							</select>
						</td>
						<td>
							<?php print __('Type', 'gridblstat');?>
						</td>
						<td>
							<select id='gtype' onChange='drawCharts()'>
								<option value='0' <?php print (get_request_var('gtype') == 0 ? 'selected':'');?>><?php print __('All', 'gridblstat');?></option>
								<option value='1' <?php print (get_request_var('gtype') == 1 ? 'selected':'');?>><?php print __('Use', 'gridblstat');?></option>
								<option value='2' <?php print (get_request_var('gtype') == 2 ? 'selected':'');?>><?php print __('Demand', 'gridblstat');?></option>
							</select>
						</td>
						<td>
							<input type='hidden' value='<?php print get_request_var('action');?>'>
							<input type='hidden' value='<?php print get_request_var('tab');?>'>
							<input type='hidden' id='lsid' value='<?php print get_request_var('lsid');?>'>
						</td>
					</tr>
				</table>
				</form>
			</td>
		</tr>
		<?php
		html_end_box();

		html_start_box('', '100%', '', '0', 'center', '');

		print "<tr><td><table width='100%' id='graphs' cellpadding='5'></table></td></tr>";

		print '<tr><td>';
		print "<script type='text/javascript'>\n";
		print " var graphs = {};\n";
		print " graphs[0] = {lsid: '" . get_request_var('lsid') . "', region:'" .get_request_var('region') . "', type : 'use', feature : '" . $details['feature'] . "', pc : '" . $details['type'] . "'};";
		if (substr($details['type'],0,7) == 'Project') {
			print " graphs[1] = {lsid: '" . get_request_var('lsid') . "', region:'" .get_request_var('region') . "', type : 'project', feature : '" . $details['feature'] . "', pc : '" . $details['type'] . "'};";
			print " graphs[2] = {lsid: '" . get_request_var('lsid') . "', region:'" .get_request_var('region') . "', type : 'pdemand', feature : '" . $details['feature'] . "', pc : '" . $details['type'] . "'};";
		}
		print " graphs[3] = {lsid: '" . get_request_var('lsid') . "', region:'" .get_request_var('region') . "', type : 'cluster', feature : '" . $details['feature'] . "', pc : '" . $details['type'] . "'};";
		print " graphs[4] = {lsid: '" . get_request_var('lsid') . "', region:'" .get_request_var('region') . "', type : 'cdemand', feature : '" . $details['feature'] . "', pc : '" . $details['type'] . "'};";
		print '</script>';
		print '</td></tr>';

		html_end_box();
	}

}

function grid_feature_filter() {
	global $config, $grid_top, $grid_refresh_interval;
	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='feature' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('LS Collector', 'gridblstat');?>
					</td>
                    <td>
                        <select id='lsid' onChange='applyFilter()'>
	                        <?php
							$lsnames = db_fetch_assoc('SELECT name, lsid
								FROM grid_blstat_collectors
								ORDER BY lsid');

							if (cacti_sizeof($lsnames)) {
		                        foreach($lsnames AS $l) {
									print "<option value='" . $l['lsid'] . "'"; if (get_request_var('lsid') == $l['lsid']) { print ' selected'; } print '>' . $l['name'] . '</option>';
								}
							}?>
                        </select>
                    </td>
					<td>
						<span>
	                        <input id='go' type='submit' value='<?php print __('Go', 'gridblstat');?>'>
                        	<input id='clear' type='button' value='<?php print __('Clear', 'gridblstat');?>' onClick='clearFilter()'>
                        	<input id='save' type='button' value='<?php print __('Save', 'gridblstat');?>' onClick='saveFilter()'>
						</span>
                    </td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
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
						<?php print __('Display', 'gridblstat');?>
					</td>
					<td>
						<select id='display' onChange='applyFilter()'>
							<option value='0' <?php print (get_request_var('display') == 0 ? 'selected':'');?>><?php print __('All', 'gridblstat');?></option>
							<option value='1' <?php print (get_request_var('display') == 1 ? 'selected':'');?>><?php print __('Stats', 'gridblstat');?></option>
							<option value='2' <?php print (get_request_var('display') == 2 ? 'selected':'');?>><?php print __('Graphs', 'gridblstat');?></option>
						</select>
					</td>
					<td>
                        <?php print __('TopX', 'gridblstat');?>
					</td>
					<td>
						<select id='top' onChange='applyFilter()'>
	                        <?php
							$rows = array(5, 10, 15, 20);

							foreach ($rows as $value) {
								print '<option value="' . $value . '"'; if (get_request_var('top') == $value) { print ' selected'; } print '>' . $value . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<input id='inuse' type='checkbox' onChange='applyFilter()' <?php print (get_request_var('inuse') == 'true' || get_request_var('inuse') == 'on' ? ' checked=checked':'');?>>
                    </td>
                    <td>
                        <label for='inuse'><?php print __('In Use', 'gridblstat');?></label>
                    </td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=features&tab=features&header=false';
				if($('#lsid').val() != null){
					strURL += '&lsid='    + $('#lsid').val();
				}
				strURL += '&inuse='   + $('#inuse').is(':checked');
				strURL += '&feature=' + $('#feature').val();
				strURL += '&region='  + $('#region').val();
				strURL += '&refresh=' + $('#refresh').val();
				if($('input[type=radio]:checked').length){
					strURL += '&radio=' + $('input[type=radio]:checked').attr('id');
				}
				strURL += '&top='     + $('#top').val();
				strURL += '&display=' + $('#display').val();
				loadPageNoHeader(strURL);
			}

			function saveFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=ajaxsave&top=' + $('#top').val();
				strURL +=  '&refresh=' + $('#refresh').val();
				strURL +=  '&feature=' + $('#feature').val();
				strURL +=  '&region=' + $('#region').val();
				strURL +=  '&lsid=' + $('#lsid').val();
				strURL +=  '&inuse=' + $('#inuse').is(':checked');
				strURL +=  '&display=' + $('#display').val();
				if($('input[type=radio]:checked').length){
					strURL += '&radio=' + $('input[type=radio]:checked').attr('id');
				}
				strURL +=  '&predefined_timespan=' + $('#predefined_timespan').val();
				strURL +=  '&gtype=' + $('#gtype').val();

				$.get(strURL, function() {
					$('#message').text('').show().html('<?php print __('Filter Settings Saved', 'gridblstat');?>').delay(2000).fadeOut(1000);
				});
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=features&tab=features&clear=true&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#radio').buttonset();
				$('input[type="radio"]').click(function() {
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_get_checkouts(&$sql_where, $apply_limits = true, $rows = 30, &$total_rows = 0) {
	$sql_params = array();
	if (get_request_var('host') != '') {
		$sql_where = "WHERE hostname LIKE ?";
		$sql_params[] = get_request_var('host') . '%';
	}

	if (get_request_var('user') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' fu.username=?';
		$sql_params[] = get_request_var('user');
	}

	if (get_request_var('ffeature') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' feature_name IN (\'' . implode("','", preg_split('/[ ,]/', get_request_var('ffeature'), -1, PREG_SPLIT_NO_EMPTY)) . '\')';
	}

	if (get_request_var('region') != '') {
		$licids = array_rekey(
			db_fetch_assoc_prepared("SELECT lic_id
				FROM grid_blstat_service_domains
				WHERE lsid IN (
					SELECT lsid
					FROM grid_blstat_collectors
					WHERE region LIKE ?)", array("%" . get_request_var('region') . "%")),
			'lic_id', 'lic_id'
		);

		if (cacti_sizeof($licids)) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (fu.service_id IN (' . implode(',', $licids) . '))';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (fu.service_id < 0)';
		}
	}
	/* license server sql where */
	if (get_request_var('sd') != '') {
		$lic_ids = array_rekey(
			db_fetch_assoc_prepared('SELECT lic_id
				FROM grid_blstat_service_domains
				WHERE service_domain = ?',
				array(get_request_var('sd'))),
			'lic_id', 'lic_id'
		);

		if (cacti_sizeof($lic_ids)) {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' fu.service_id IN(' . implode(',', $lic_ids) . ')';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' fu.service_id < 0';
		}
	} else {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' fu.service_id IN(SELECT lic_id FROM grid_blstat_service_domains)';
	}

	$sql_order = get_order_string();
	if (get_request_var('except') == -1) {
		$sql_join = 'RIGHT JOIN lic_services AS fs
			INNER JOIN lic_services_feature_details AS fu
			ON fu.service_id = fs.service_id
			INNER JOIN grid_blstat_feature_map AS fm
			ON fm.lic_feature = fu.feature_name
			ON gh.host = fu.hostname';

		if (get_request_var('lsf') == -1) {
			/* show all records */
		} elseif (get_request_var('lsf') == -2) {
			/* show LSF only hosts */
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE ') . ' (gh.host="" OR gh.host IS NOT NULL)';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE ') . ' (gh.host IS NULL)';
		}

		$sql_query = "SELECT DISTINCT feature_name, vendor_daemon, feature_version,
			server_name, username, hostname,
			(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date)) AS duration,
			fu.status, tokens_acquired, tokens_acquired_date, chkoutid
			FROM grid_hostinfo AS gh
			$sql_join
			$sql_where
			$sql_order";

		$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM (SELECT feature_name from
				grid_hostinfo AS gh
				$sql_join
				$sql_where
				GROUP BY feature_name, server_name, feature_version,
				username, hostname, fu.status,
				tokens_acquired, tokens_acquired_date, chkoutid) AS a", $sql_params);
	} else {
		if (get_request_var('lsf') == -1) {
			/* show all records */
			$sql_where_ex = '';
		} elseif (get_request_var('lsf') == -2) {
			/* show LSF only hosts */
			$sql_where_ex = ' WHERE (gh.host="" OR gh.host IS NOT NULL)';
		} else {
			$sql_where_ex=" WHERE (gh.host IS NULL)";
		}

		$sql_query = "SELECT DISTINCT feature_name, vendor_daemon, feature_version,
			server_name, recordset.username, hostname,
			(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date)) AS duration,
			status, tokens_acquired, tokens_acquired_date, chkoutid
			FROM grid_hostinfo AS gh
			INNER JOIN grid_clusters AS gc
			ON gh.clusterid=gc.clusterid
			RIGHT JOIN (
				SELECT fu.*, fs.server_name
				FROM lic_services_feature_details AS fu
				INNER JOIN grid_blstat_feature_map AS fm
				ON fm.lic_feature=fu.feature_name
				INNER JOIN lic_services AS fs
				ON fu.service_id=fs.service_id
				LEFT JOIN (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
				ON fu.username=bu.user AND fu.hostname=bu.host
				$sql_where
				AND bu.cnt IS " . (get_request_var('except') == "-3" ? "NOT ":"") . "NULL
			) AS recordset
			ON gh.host=recordset.hostname $sql_where_ex
			$sql_order";

		$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM (SELECT feature_name
				FROM grid_hostinfo AS gh
				INNER JOIN grid_clusters AS gc
				ON gh.clusterid=gc.clusterid
				RIGHT JOIN (
					SELECT fu.*, fs.server_name
					FROM lic_services_feature_details AS fu
					INNER JOIN grid_blstat_feature_map AS fm
					ON fm.lic_feature=fu.feature_name
					INNER JOIN lic_services AS fs
					ON fu.service_id=fs.service_id
					LEFT JOIN (SELECT user, host, COUNT(*) AS cnt FROM grid_blstat_users GROUP BY user, host) AS bu
					ON fu.username=bu.user AND fu.hostname=bu.host
					$sql_where
					AND bu.cnt IS " . (get_request_var('except') == "-3" ? "NOT ":"") . "NULL
				) AS recordset
				ON gh.host=recordset.hostname $sql_where_ex
				GROUP BY feature_name, server_name, feature_version,
				recordset.username, hostname, status,
				tokens_acquired, tokens_acquired_date, chkoutid) AS a", $sql_params);
	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}
	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function grid_view_checkouts() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;

   	/* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'lsf' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'except' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '-1'
			),
		'ffeature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'host' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sd' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'rtm_filter_sanitize_search_string')
			),
		'user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'feature_name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'inuse' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => get_user_setting('inuse', 'true')
		)
	);

	validate_store_request_vars($filters, 'sess_gbs_co');
	/* ================= input validation ================= */

	html_start_box(__('Feature Checkout Filter', 'gridblstat'), '100%', '', '3', 'center', '');
	grid_checkouts_filter();
	html_end_box(true);

	$sql_where = "";
	$total_rows = 0;

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$lic_checkout_results = grid_get_checkouts($sql_where, true, $rows, $total_rows);

	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'gridblstat')
		),
		'feature_name' => array(
			'display' => __('Feature Name', 'gridblstat'),
			'sort' => 'ASC',
			'tip' => __('Feature name in FLEXlm/RLM', 'gridblstat')
		),
		'vendor_daemon' => array(
			'display' => __('Vendor Daemon', 'gridblstat'),
			'sort' => 'ASC',
			'tip' => __('Vendor daemon in FLEXlm/RLM', 'gridblstat')
		),
		'server_name' => array(
			'display' => __('Service Name', 'gridblstat'),
			'sort' => 'ASC'
		),
		'feature_version' => array(
			'display' => __('Version', 'gridblstat'),
			'sort' => 'ASC',
			'tip' => __('The version of licenses', 'gridblstat')
		),
		'username' => array(
			'display' => __('User Name', 'gridblstat'),
			'sort' => 'ASC'
		),
		'hostname' => array(
			'display' => __('Host Name', 'gridblstat'),
			'sort' => 'ASC'
		),
		'status' => array(
			'display' => __('Status', 'gridblstat'),
			'align' => 'right',
			'sort' => 'DESC'
			),
		'tokens_acquired' => array(
			'display' => __('InUse', 'gridblstat'),
			'sort' => 'DESC',
			'align' => 'right',
			'tip' => __('The number of licenses in use', 'gridblstat')
		),
		'duration' => array(
			'display' => __('Duration', 'gridblstat'),
			'sort' => 'DESC',
			'align' => 'right',
			'tip' => __('Time duration the feature is checked out', 'gridblstat')
		),
		'tokens_acquired_date' => array(
			'display' => __('Date', 'gridblstat'),
			'sort' => 'DESC',
			'align' => 'right',
			'tip' => __('The time when the feature is checked out', 'gridblst')
		)
	);

	$nav = html_nav_bar('grid_lsdashboard.php?action=checkouts&tab=checkouts', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Checkouts'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), '', 'grid_lsdashboard.php?action=' . get_request_var('action') . '&tab=' . get_request_var('tab'));

	$i = 0;
	if (cacti_sizeof($lic_checkout_results)) {
		foreach ($lic_checkout_results as $lic) {
			// $clusterid = db_fetch_cell("SELECT clusterid
			// 	FROM grid_hosts
			// 	WHERE host LIKE '" . $lic['hostname'] . "%'
			// 	LIMIT 1");
			//
			// if (empty($clusterid)) {
			// 	$clusterid = 0;
			// }

			$actions_url = "<a href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=true&clusterid=0&exec_host=' . $lic['hostname'] . '&job_user=' . $lic['username'] . '&status=ACTIVE&page=1') . "'><img src='" . $config['url_path'] . "plugins/license/images/view_jobs.gif' alt='' title='" . __esc('View Active Jobs', 'gridblstat') . "'></a>";

			form_alternate_row();

			form_selectable_cell($actions_url, $i, '1%');
			form_selectable_cell($lic['feature_name'], $i);
			form_selectable_cell($lic['vendor_daemon'], $i);
			form_selectable_cell($lic['server_name'], $i);
			form_selectable_cell($lic['feature_version'] ? $lic['feature_version']:'--', $i);
			form_selectable_cell($lic['username'] ? $lic['username']:'--', $i);
			form_selectable_cell(filter_value($lic['hostname']? $lic['hostname']:'--', '', $config['url_path'] . 'plugins/grid/grid_bzen.php?action=zoom&reset=true&page=1&clusterid=0&exec_host=' . $lic['hostname']), $i);

			form_selectable_cell(strtoupper($lic['status']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($lic['tokens_acquired']), $i, '', 'right');
			form_selectable_cell( display_job_time($lic['duration'],2,false), $i, '', 'right');

			if ($lic['tokens_acquired_date'] == '0000-00-00 00:00:00') {
				$value = '--';
			} else {
				$value = substr($lic['tokens_acquired_date'], 0, -3);
			}

			form_selectable_cell($value, $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print "<tr><td colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No License Checkouts Found', 'gridblstat') . "</em></td></tr>";
	}

	html_end_box();

	if (cacti_sizeof($lic_checkout_results)) {
		print $nav;
	}

}

function grid_checkouts_filter() {
	global $config, $lic_refresh_interval, $lic_rows_selector;

	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid' action='grid_lsdashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='ffeature' size='20' value='<?php print html_escape_request_var('ffeature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='region' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Domain', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='sd' value='<?php print html_escape_request_var('sd');?>'>
					</td>
					<td>
						<?php print __('Refresh', 'gridblstat');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
						<?php
						$max_refresh = read_config_option('grid_minimum_refresh_interval');
						foreach($lic_refresh_interval as $key => $value) {
							if ($key >= $max_refresh) {
								print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<span>
							<input type='button' id='go' value='<?php print __('Go', 'gridblstat');?>' onClick='applyFilter()'>
							<input type='button' id='clear' value='<?php print __('Clear', 'gridblstat');?>' onClick='clearFilter()'>
						</span>
					</td>
					<td>
						<input type="hidden" value="checkouts" name="action">
						<input type="hidden" value="checkouts" name="tab">
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('User', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='ls_user' value='<?php print html_escape_request_var('user');?>'>
					</td>
					<td>
						<?php print __('Host', 'gridblstat');?>
					</td>
					<td>
						<input type="text" id='ls_host' value='<?php print html_escape_request_var('host');?>'>
					</td>
					<td>
						<?php print __('Exceptions', 'gridblstat');?>
					</td>
					<td>
						<select id='except' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('except') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<option value='-2'<?php if (get_request_var('except') == '-2') {?> selected<?php }?>><?php print __('Yes', 'gridblstat');?></option>
							<option value='-3'<?php if (get_request_var('except') == '-3') {?> selected<?php }?>><?php print __('No', 'gridblstat');?></option>
						</select>
					</td>
					<td>
						<?php print __('LSFHost', 'gridblstat');?>
					</td>
					<td>
						<select id='lsf' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('lsf') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<option value='-2'<?php if (get_request_var('lsf') == '-2') {?> selected<?php }?>><?php print __('Yes', 'gridblstat');?></option>
							<option value='-3'<?php if (get_request_var('lsf') == '-3') {?> selected<?php }?>><?php print __('No', 'gridblstat');?></option>
						</select>
					</td>
					<td>
						<?php print __('Records', 'gridblstat');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector)) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=checkouts&tab=checkouts&header=false';
				strURL +=  '&ffeature=' + $('#ffeature').val();
				strURL +=  '&sd=' + encodeURIComponent($('#sd').val());
				strURL +=  '&region=' + $('#region').val();
				strURL +=  '&lsf=' + $('#lsf').val();
				strURL +=  '&except=' + $('#except').val();
				strURL +=  '&host=' + $('#ls_host').val();
				strURL +=  '&user=' + $('#ls_user').val();
				strURL +=  '&refresh=' + $('#refresh').val();
				strURL +=  '&rows=' + $('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=checkouts&tab=checkouts&clear=true&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#ffeature').autocomplete({
					source: 'grid_lsdashboard.php?action=ajaxsearch&type=ffeature',
					minLength: 2,
					autoFocus: true,
					select: function(event, ui) {
						$('#ffeature').val(ui.item.value);
						applyFilter();
					}
				});

				$('#sd').keypress(function(event) {
					if (event.which == 13) {
						applyFilter();
					}
				});

				$('#region').keypress(function(event) {
					if (event.which == 13) {
						applyFilter();
					}
				});

				$('#ls_host').keypress(function(event) {
					if (event.which == 13) {
						applyFilter();
					}
				});

				$('#ls_user').keypress(function(event) {
					if (event.which == 13) {
						applyFilter();
					}
				});

				$('#ffeature').keypress(function(event) {
					if (event.which == 13) {
						applyFilter();
					}
				});
			});

			</script>
		</td>
	</tr>
	<?php
}

function grid_blstat_view_graphs() {
	global $current_user, $config;

	if (!isset_request_var('action')) {
		set_request_var('action','graphs');
	}

   	/* ================= input validation and session storage ================= */
    $filters = array(
		'columns' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
			),
		'graphs_per_page_count' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_graph_config_option('preview_graphs_per_page')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'template' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '0'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'cluster' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'project' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'thumbnails' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'inuse' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => get_user_setting('inuse', 'true')
		)
	);

	validate_store_request_vars($filters, 'sess_gbs_graph');
	/* ================= input validation ================= */

	$sql_where = '';

	html_start_box(__('Graph Filter', 'gridblstat'), '100%', '', '1', 'center', '');

	$graph_templates = grid_blstat_graph_view_filter();

	$gtids = implode(',', array_keys(array_rekey($graph_templates, 'id', 'id')));

	if (get_request_var('template') > 0) {
		$sql_where = 'WHERE gtg.graph_template_id=' . get_request_var('template');
	} elseif ($gtids != '') {
		$sql_where = "WHERE gtg.graph_template_id IN($gtids)";
	}

	if (get_request_var('region') != '') {
		$hostids = array_rekey(db_fetch_assoc_prepared("SELECT id
			FROM host WHERE (lic_server_id IN (
				SELECT lic_id FROM grid_blstat_service_domains
				WHERE lsid IN (
					SELECT lsid FROM grid_blstat_collectors
					WHERE region LIKE ?
				)
			)
			AND lic_server_id>0)
			OR id IN (
				SELECT cacti_host
				FROM grid_blstat_collectors
				WHERE region LIKE ?)", array("%" . get_request_var('region') . "%", "%" . get_request_var('region') . "%")),'id', 'id');

		if (cacti_sizeof($hostids)) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (gl.host_id IN (' . implode(',', $hostids) . '))';
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' (gl.host_id<0)';
		}
	}

	if (get_request_var('cluster') != '-1') {
		$ids[] = get_data_query_id('e1458d6832066ae20b19f7dd6ba62002');
		$ids[] = get_data_query_id('51b53ceb3f70861c47fd169dae72a3bf');
		$ids[] = get_data_query_id('0a47e16083078ce8f165cb220c2d0306');
		$ids   = implode(',',$ids);

		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gl.snmp_index LIKE '%" . get_request_var('cluster') . "'" . (strlen($ids) ? " AND snmp_query_id IN($ids))":')');

		if (get_request_var('inuse') == 'on' || get_request_var('inuse') == 'true') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gl.snmp_index IN(
				SELECT snmp_index
				FROM host_snmp_cache
				WHERE field_name='project'
				AND field_value IN(
					SELECT DISTINCT project
					FROM grid_blstat_cluster_use
					WHERE cluster='" . get_request_var('cluster') . "'
					AND (inuse>0 OR `over`>0 OR need>0) AND present = 1
				))
				OR (gl.snmp_index IN (
					SELECT CONCAT_WS('|', lsid, feature, cluster)
					FROM grid_blstat_clusters
					WHERE inuse > 0
				)))";
		}
	}

	if (get_request_var('inuse') == 'on' || get_request_var('inuse') == 'true') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gl.snmp_index IN(
			SELECT snmp_index
			FROM host_snmp_cache
			WHERE field_name='gridFeatureName'
			AND field_value IN (
				SELECT DISTINCT feature_name
				FROM lic_services_feature_use
				WHERE feature_inuse_licenses>0
			))
			OR (gl.snmp_index IN(
				SELECT snmp_index
				FROM host_snmp_cache
				WHERE field_name='feature'
				AND field_value IN(
					SELECT feature
					FROM grid_blstat
					WHERE total_inuse>0
					OR total_use>0
				)
			)))";
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " gtg.title_cache LIKE '%" . get_request_var('filter') . "%'";
	}

	if (get_request_var('project') != "") {
		$bp = get_data_query_id("276d7b3235d9fe1071346abfcbead663");
		$bc = get_data_query_id("51b53ceb3f70861c47fd169dae72a3bf");
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " ((gl.snmp_index LIKE '%|" . get_request_var('project') . "|%' AND gl.snmp_query_id='$bc')
			OR (gl.snmp_index LIKE '%|" . get_request_var('project') . "' AND gl.snmp_query_id='$bp'))";

		if (get_request_var('inuse') == 'on' || get_request_var('inuse') == 'true') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (gl.snmp_index IN(SELECT snmp_index FROM host_snmp_cache WHERE field_name='cluster' AND field_value IN(SELECT DISTINCT cluster FROM grid_blstat_cluster_use WHERE project='" . get_request_var('project') . "' AND (inuse>0 OR `over`>0 OR need>0) AND present = 1)))";
		}
	}

	if (get_request_var('feature') != "") {
		$array_feature = explode('@', get_request_var('feature'));

		if (cacti_sizeof($array_feature) > 1) {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(gl.snmp_index LIKE '%|" . str_replace("_","\_",get_request_var('feature')) . "|%' OR gl.snmp_index LIKE '%-" . str_replace("_","\_",get_request_var('feature')) ."-%' OR gl.snmp_index LIKE '%|" . str_replace("_","\_",$array_feature[0]) . "|%' OR gl.snmp_index LIKE '%-" . str_replace("_","\_",$array_feature[0]) ."-%')";
		} else {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(gl.snmp_index LIKE '%|" . str_replace("_","\_",get_request_var('feature')) . "|%' OR gl.snmp_index LIKE '%-" . str_replace("_","\_",get_request_var('feature')) ."-%')";
		}
	}

	$graphs = db_fetch_assoc("SELECT
		gtg.width, gtg.height,
		gtg.local_graph_id,
		gtg.title_cache
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id=gl.id
		$sql_where
		ORDER BY gtg.title_cache
		LIMIT " . (get_request_var('graphs_per_page_count')*(get_request_var('page')-1)) . "," . get_request_var('graphs_per_page_count'));

	$total_rows = db_fetch_cell("SELECT COUNT(*)
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id=gl.id
		$sql_where");

	/* reset the page if you have changed some settings */
	if (get_request_var('graphs_per_page_count') * (get_request_var('page')-1) >= $total_rows) {
		set_request_var('page',"1");
	}

	html_end_box();

	/* do some fancy navigation url construction so we don't have to try and rebuild the url string */
	if (preg_match("/page=[0-9]+/",basename($_SERVER["QUERY_STRING"]))) {
		$nav_url = str_replace('page=' . get_request_var('page'), 'page=<PAGE>', basename($_SERVER['PHP_SELF']) . '?' . $_SERVER['QUERY_STRING']);
	} else {
		$nav_url = str_replace('&clear=true', '', basename($_SERVER["PHP_SELF"]) . '?' . $_SERVER['QUERY_STRING']);//fix next page bug after click clear.
		$nav_url = $nav_url . '&page=<PAGE>';
	}

	html_start_box('', '100%', '', '3', 'center', '');
	$nav = html_nav_bar('grid_lsdashboard.php?action=graphs&tab=graphs', MAX_DISPLAY_PAGES, get_request_var('page'), get_request_var('graphs_per_page_count'), $total_rows, 5, __('Graphs', 'gridblstat'), 'page', 'main');
	print $nav;
	html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'), get_request_var('thumbnails'));

	html_end_box();
	if ($total_rows) {
		print $nav;
	}

}

function grid_blstat_nav_bar($page, $rows_per_page, $total_rows, $nav_url) {
	global $config;

	if ($total_rows) {
		?>
		<tr class='noprint'>
			<td colspan='<?php print get_request_var('columns');?>'>
				<table width='100%' cellspacing='0' cellpadding='2' border='0'>
					<tr>
						<div class='navBarNavigation'>
						<div class='navBarNavigationPrevious'>
							<?php if ($page > 1) {
								print "<a href='" . str_replace("<PAGE>", ($page-1), $nav_url) . "'><i class='fa fa-angle-double-left previous'></i>" . __('Previous'). "</a>";
							} ?>
						</div>
						<div class='navBarNavigationCenter'>
							Showing Graphs <?php print (($rows_per_page*($page-1))+1);?> to <?php print ((($total_rows < $rows_per_page) || ($total_rows < ($rows_per_page*$page))) ? $total_rows : ($rows_per_page*$page));?> of <?php print $total_rows;?>
						</div>
						<div class='navBarNavigationNext'>
							<?php if (($page * $rows_per_page) < $total_rows) {
								print "<a href='" . str_replace("<PAGE>", ($page+1), $nav_url) . "'>" . __('Next'). "<i class='fa fa-angle-double-right next'></i></a>";
							} ?>
						</div>
						</div>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}else{
		?>
		<tr class='noprint'>
			<td colspan='<?php print get_request_var('columns');?>'>
				<table width='100%' cellspacing='0' cellpadding='2' border='0'>
					<tr>
						<div class='navBarNavigation'>
						<div class='navBarNavigationNone'>
							No Graphs Found
						</div>
						</div>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

function grid_blstat_graph_view_filter() {
	global $config, $grid_rows_selector;
	global $graph_timespans, $graph_timeshifts;

	?>
	<tr class='odd noprint'>
		<td>
			<form id='form_grid'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Feature', 'gridblstat');?>
					</td>
					<td>
						<input id='feature' type='text' value='<?php print html_escape_request_var('feature');?>'>
					</td>
					<td>
						<?php print __('Region', 'gridblstat');?>
					</td>
					<td>
						<input id='region' type='text' value='<?php print html_escape_request_var('region');?>'>
					</td>
					<td>
						<?php print __('Project', 'gridblstat');?>
					</td>
					<td>
						<input id='project' type='text' value='<?php print html_escape_request_var('project');?>'>
					</td>
					<td>
						<?php print __('Cluster', 'gridblstat');?>
					</td>
					<td>
						<select id='cluster'>
							<option value='-1'<?php if (isset_request_var('cluster') && get_request_var('cluster') == '-1') {?> selected<?php }?>><?php print __('All', 'gridblstat');?></option>
							<?php
							$clusters = db_fetch_assoc('SELECT DISTINCT cluster
								FROM grid_blstat_cluster_use WHERE present = 1
								UNION
								SELECT DISTINCT cluster
								FROM grid_blstat_clusters WHERE present = 1
								ORDER BY cluster');

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['cluster'] . '"' . (get_request_var('cluster') == $cluster['cluster'] ? ' selected':'') . '>' . $cluster['cluster'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<input id='inuse' type='checkbox' <?php print (get_request_var('inuse') == 'true' ? ' checked':''); ?>>
					</td>
					<td>
						<label for='inuse'><?php print __('InUse', 'gridblstat');?></label>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'gridblstat');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'gridblstat');?>' onClick='clearFilter()'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridblstat');?>
					</td>
					<td>
						<input id='filter' type='text' size='25' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Template', 'gridblstat');?>
					</td>
					<td>
						<select id='template'>
							<option value='0'<?php if (get_request_var('template') == '0') {?> selected<?php }?>><?php print __('Any', 'gridblstat');?></option>
							<?php

							$graph_templates = get_graph_templates();

							if (cacti_sizeof($graph_templates)) {
								foreach ($graph_templates as $template) {
									$name = trim(str_replace('GRID - License Scheduler -', 'LS ', str_replace('LM License', 'LM ', str_replace('FLEX Feature', 'FLEX - Feature', $template['name']))),'- ');
									print "<option value='" . $template['id'] . "'" . (get_request_var('template') == $template['id'] ? ' selected':'') . '>' . html_escape($name) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Graphs', 'gridblstat');?>
					</td>
					<td>
						<select id='graphs_per_page_count'>
						<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"' . (get_request_var('graphs_per_page_count') == $key ? ' selected':'') . '>' . $value . '</option>';
								}
							}
						?>
						</select>
					</td>
					<td>
						<?php print __('Columns', 'gridblstat');?>
					</td>
					<td>
						<select id='columns'>
							<?php
							$values = array(1,2,3,4,5);

							foreach($values as $val) {
								print "<option value='$val'" . ($val == get_request_var('columns') ? ' selected':'') . '>' . $val . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<input id='thumbnails' type='checkbox' <?php print (get_request_var('thumbnails') == 'true' ? ' checked':''); ?>>
					</td>
					<td>
						<label for='thumbnails'><?php print __('Thumbnails', 'gridblstat');?></label>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Presets', 'gridblstat');?>
					</td>
					<td>
						<select id='predefined_timespan' onChange='applyTimespanFilterChange()'>
							<?php
							$graph_timespans[GT_CUSTOM] = __('Custom', 'gridblstat');

							if (cacti_sizeof($graph_timespans)) {
								foreach($graph_timespans as $value => $text) {
									print "<option value='$value'" . ($_SESSION['sess_current_timespan'] == $value ? ' selected':'') . '>' . html_escape($text) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'gridblstat');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print (isset($_SESSION['sess_current_date1']) ? $_SESSION['sess_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'gridblstat');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print (isset($_SESSION['sess_current_date2']) ? $_SESSION['sess_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward');?>'></i>
							<select id='predefined_timeshift' name='predefined_timeshift' title='<?php print __esc('Define Shifting Interval');?>'>
								<?php
								$start_val = 1;
								$end_val = cacti_sizeof($graph_timeshifts)+1;
								if (cacti_sizeof($graph_timeshifts) > 0) {
									for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
										print "<option value='$shift_value'"; if ($_SESSION['sess_current_timeshift'] == $shift_value) { print ' selected'; } print '>' . title_trim($graph_timeshifts[$shift_value], 40) . "</option>\n";
									}
								}
								?>
							</select>
							<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<input id='tsrefresh' type='button' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Refresh');?>' name='button_refresh_x' title='<?php print __esc('Refresh selected time span');?>' onClick='gridblstat_refreshGraphTimespanFilter()'>
							<input id='tsclear' type='button' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Clear');?>' title='<?php print __esc('Return to the default time span');?>' onClick='gridblstat_clearGraphTimespanFilter()'>
						</span>
					</td>
					<td>
						<span>
							<input type='hidden' name='action' value='<?php print get_request_var('action');?>'>
							<input type='hidden' name='tab' value='<?php print get_request_var('tab');?>'>
							<input type='hidden' id='lsid' name='lsid' value='<?php print (isset_request_var('lsid') ? get_request_var('lsid'):'');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
		<script type='text/javascript'>
		var date1Open       = false;
		var date2Open       = false;
		function initPage() {
			$('#startDate').click(function() {
				if (date1Open) {
					date1Open = false;
					$('#date1').datetimepicker('hide');
				} else {
					date1Open = true;
					$('#date1').datetimepicker('show');
				}
			});

			$('#endDate').click(function() {
				if (date2Open) {
					date2Open = false;
					$('#date2').datetimepicker('hide');
				} else {
					date2Open = true;
					$('#date2').datetimepicker('show');
				}
			});

			$('#date1').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});

			$('#date2').datetimepicker({
				minuteGrid: 10,
				stepMinute: 1,
				showAnim: 'slideDown',
				numberOfMonths: 1,
				timeFormat: 'HH:mm',
				dateFormat: 'yy-mm-dd',
				showButtonPanel: false
			});
		}

function gridblstat_refreshGraphTimespanFilter() {
	var json = {
		custom: 1,
		button_refresh_x: 1,
		date1: $('#date1').val(),
		date2: $('#date2').val(),
		predefined_timespan: $('#predefined_timespan').val(),
		predefined_timeshift: $('#predefined_timeshift').val(),
		__csrf_magic: csrfMagicToken
	};

	var href = appendHeaderSuppression('grid_lsdashboard.php?action='+pageAction);

	closeDateFilters();
	$.ajaxQ.abortAll();
	$.post(href, json).done(function(data) {
		checkForLogout(data);

		$('#main').empty().hide();
		$('div[class^="ui-"]').remove();
		$('#main').html(data);
		applySkin();
	});
}
function gridblstat_clearGraphTimespanFilter() {
	var json = {
		button_clear: 1,
		date1: $('#date1').val(),
		date2: $('#date2').val(),
		predefined_timespan: $('#predefined_timespan').val(),
		predefined_timeshift: $('#predefined_timeshift').val(),
		__csrf_magic: csrfMagicToken
	};

	var href = appendHeaderSuppression('grid_lsdashboard.php?action='+pageAction);

	closeDateFilters();
	$.ajaxQ.abortAll();
	$.post(href, json).done(function(data) {
		checkForLogout(data);

		$('#main').empty().hide();
		$('div[class^="ui-"]').remove();
		$('#main').html(data);
		applySkin();
	});
}

		function applyFilter(move_flag) {
			strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=graphs&tab=graphs&header=false';
			strURL += '&template=' + $('#template').val();
			strURL += '&columns=' + $('#columns').val();
			strURL += '&graphs_per_page_count=' + $('#graphs_per_page_count').val();
			strURL += '&thumbnails=' + $('#thumbnails').is(':checked');
			strURL += '&inuse=' + $('#inuse').is(':checked');
			strURL += '&region=' + $('#region').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&project=' + $('#project').val();
			strURL += '&cluster=' + $('#cluster').val();
			strURL += '&feature=' + $('#feature').val();
			if(move_flag==1 || move_flag==2){
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				if(move_flag == 1){
					strURL += '&move_left_x=move_left_x';
				}
				if(move_flag == 2){
					strURL += '&move_right_x=move_right_x';
				}
			}
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = urlPath + 'plugins/gridblstat/grid_lsdashboard.php?action=graphs&tab=graphs&header=false&clear=true';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#form_grid').submit(function(event) {
				event.preventDefault();
				applyFilter(0);
			});

			$('#feature').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=feature',
				minLength: 0,
				select: function(event, ui) {
					$('#feature').val(ui.item.value);
					applyFilter(0);
				}
			});

			$('#feature').keypress(function(event) {
				if (event.which == 13) {
					applyFilter(0);
				}
			});

			$('#project').autocomplete({
				autoFocus: true,
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=project',
				minLength: 0,
				select: function(event, ui) {
					$('#project').val(ui.item.value);
					applyFilter(0);
				}
			});

			$('#project').keypress(function(event) {
				if (event.which == 13) {
					applyFilter(0);
				}
			});

			$('#filter').autocomplete({
				source: 'grid_lsdashboard.php?action=ajaxsearch&type=graphs',
				minLength: 2,
				select: function(event, ui) {
					$('#filter').val(ui.item.value);
					applyFilter(0);
				}
			});

			$('#filter').keypress(function(event) {
				if (event.which == 13) {
					applyFilter(0);
				}
			});

			$('#cluster, #inuse, #go, #template, #graphs_per_page_count, #columns, #thumbnails').change(function() {
				applyFilter(0);
			});
			$('#move_left').click(function() {
				applyFilter(1);
			});
			$('#move_right').click(function() {
				applyFilter(2);
			});

			$.when(initPage())
			.pipe(function() {
				initializeGraphs();
			});
		});

		</script>
	</tr>
	<?php

	return $graph_templates;
}

function grid_blstat_graph_area(&$graph_array, $no_graphs_message = '', $extra_url_args = '', $header = '', $columns = 2, $thumbnails = 'true') {
	global $config;

	if ($thumbnails == 'true') {
		$th_option = '&graph_nolegend=true&graph_height=' . read_graph_config_option('default_height') . '&graph_width=' . read_graph_config_option('default_width');
	} else {
		$th_option = '';
	}

	$i = 0; $k = 0;
	if (cacti_sizeof($graph_array)) {
		if ($header != '') {
			print $header;
		}

		print '<tr>';

		foreach ($graph_array as $graph) {
			?>
			<td align='center' width='<?php print (98 / $columns);?>%'>
				<table width='1' cellpadding='0'>
					<tr>
						<td>
							<a href='<?php print html_escape($config['url_path'] . 'graph.php?action=view&rra_id=all&local_graph_id=' . $graph['local_graph_id']);?>'><img class='graphimage' id='graph_<?php print $graph['local_graph_id'] ?>' src='<?php print html_escape($config['url_path'] . 'graph_image.php?local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0' . $th_option . (($extra_url_args == '') ? '' : "&$extra_url_args"));?>' alt='' title='<?php print $graph['title_cache'];?>'></a>
						</td>
						<td style='vertical-align:top;padding: 3px;'>
							<a href='<?php print html_escape($config['url_path'] . 'graph.php?action=zoom&local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0&' . $extra_url_args);?>'><img src='<?php print $config['url_path']; ?>images/graph_zoom.gif' alt='' title='Zoom Graph'></a><br>
							<a href='<?php print html_escape($config['url_path'] . 'graph_xport.php?local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0&' . $extra_url_args);?>'><img src='<?php print $config['url_path']; ?>images/graph_query.png' alt='' title='CSV Export'></a><br>
							<a href='#page_top'><img src='<?php print $config['url_path']; ?>images/graph_page_top.gif' alt='' title='Page Top'></a><br>
							<?php api_plugin_hook('graph_buttons', array('hook' => 'thumbnails', 'local_graph_id' => $graph['local_graph_id'], 'rra' =>  0, 'view_type' => '')); ?>
						</td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i == $columns) && ($k < count($graph_array))) {
				$i = 0;
				print '</tr><tr>';
			}
		}

		print '</tr>';
	} else {
		if ($no_graphs_message != '') {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}

function grid_blstat_set_minimum_page_refresh() {
	global $config, $refresh;

	$minimum = read_config_option('grid_minimum_refresh_interval');

	if (isset_request_var('refresh')) {
		if (get_request_var('refresh') < $minimum) {
			set_request_var('refresh',$minimum);
		}

		/* automatically reload this page */
		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page'] = $_SERVER['REQUEST_URI'];
	}
}

function grid_blstat_header($suffix='') {
	$now = time();

	$last_run = db_fetch_row('SELECT UNIX_TIMESTAMP(MAX(blstat_lastrun)) AS max, UNIX_TIMESTAMP(MIN(blstat_lastrun)) AS min
		FROM grid_blstat_collectors
		WHERE disabled=""
		AND blstat_lastrun !="0000-00-00"');

	$coll = db_fetch_cell('SELECT COUNT(*)
		FROM grid_blstat_collectors
		WHERE disabled=""
		AND blstat_lastrun!="0000-00-00"');

	if (cacti_sizeof($last_run)) {
		$minMax = floor(($now-$last_run['max'])/3600) % 60;
		$minMin = floor(($now-$last_run['min'])/3600) % 60;
		$secMax = date('s', $now-$last_run['max']);
		$secMin = date('s', $now-$last_run['min']);
		$suffix = " $suffix</td><td align='right' style='min-width:0px;'><span id='message'></span></td><td width='0'>";

		if ($minMin == 0 && $secMin == 0) {
			return __(' [ Just Updated ] %s', $suffix, 'gridblstat');
		} else {
			return ' [ Updated ' . ($coll > 1 ? 'between ':' ') .
				($minMax > 0 ? ltrim($minMax,'0') . ' Minute' . ($minMax > 1 ? 's':''):'') .
				($minMax> 0 ? ', ':'') . ($secMax > 0 ? ltrim($secMax,'0') . ' Second' . ($secMax > 1 ? 's':''):'') .
				($coll > 1 ?  ' and ' .
				($minMin > 0 ? ltrim($minMin,'0') . ' Minute' . ($minMin > 1 ? 's':''):'') .
				($minMin> 0 ? ', ':'') . ($secMin > 0 ? ltrim($secMin,'0') . ' Second' . ($secMin > 1 ? 's':''):''):'') . ' Ago ]' . $suffix;
		}
	} else {
		return ' [ Unknown Last Update Time ]' . $suffix;
	}
}

function get_region_clusters($region) {
	$lsids = array_rekey(
		db_fetch_assoc_prepared('SELECT lsid
			FROM grid_blstat_collectors
			WHERE region = ?',
			array($region)),
		'lsid', 'lsid'
	);

	$clusters = array();
	if (cacti_sizeof($lsids)) {
		foreach($lsids as $lsid) {
			$clusters += array_rekey(
				db_fetch_assoc_prepared('SELECT clusterid
					FROM grid_blstat_collector_clusters
					WHERE lsid = ?',
					array($lsid)),
				'clusterid', 'clusterid'
			);
		}
	}

	return $clusters;
}

function get_ls_count() {
	$count = db_fetch_assoc('SELECT count(lsid)
			FROM grid_blstat_collectors');
	return $count;
}

function grid_blstat_ajax_save() {
	$settings =
		'top='                 . get_request_var('top')                 . '|' .
		'inuse='               . get_request_var('inuse')               . '|' .
		'refresh='             . get_request_var('refresh')             . '|' .
		'predefined_timespan=' . get_request_var('predefined_timespan') . '|' .
		'display='             . get_request_var('display')             . '|' .
		'gtype='               . (isset_request_var('gtype') ? get_request_var('gtype'): '0') ;


	set_grid_config_option('grid_blstat_db_filter', $settings);
}

function get_user_setting($setting, $default) {
	$settings = read_grid_config_option('grid_blstat_db_filter', true);

	if ($settings != '') {
		$setarray = explode('|', $settings);

		if (cacti_sizeof($setarray)) {
			foreach ($setarray as $s) {
				$iset = explode('=', $s);
				if ($iset[0] == $setting) {
					return $iset[1];
					break;
				}
			}
		}
	}

	return $default;
}
