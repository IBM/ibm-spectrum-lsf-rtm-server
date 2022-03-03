<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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
include_once('./plugins/license/include/lic_functions.php');

lic_view_lics();

function lic_view_get_lics_records(&$vendors, &$sql_where, &$sql_from_join) {
	$sql_params = array();
	$sql_where = 'WHERE feature_inuse_licenses>0';

	/* license master sql where */
	if (get_request_var('vendor') != -1 && get_request_var('vendor') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ');

		if (get_request_var('vendor') == -2) {
			$sql_where .= 'server_vendor=""';
		} else {
			$sql_where .= 'server_vendor=?';
			$sql_params[] = get_request_var('vendor');
		}
	}

	/* license server sql where */
	if (get_request_var('location') != -1 && get_request_var('location') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ');

		if (get_request_var('location') == -2) {
			$sql_where .= 'ls.server_location=""';
		} else {
			$sql_where .= 'ls.server_location=?';
			$sql_params[] = get_request_var('location');
		}
	}

	/* license server sql where */
	if (get_request_var('region') != -1 && get_request_var('region') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ');

		if (get_request_var('region') == -2) {
			$sql_where .= 'ls.server_region=""';
		} else {
			$sql_where .= 'ls.server_region=?';
			$sql_params[] = get_request_var('region');
		}
	}

	if (get_request_var('keyfeat') == 'true') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' critical=1';
		$sql_from_join = ' INNER JOIN lic_application_feature_map AS lafm
			ON lsfu.service_id = lafm.service_id
			AND lsfu.feature_name = lafm.feature_name ';
	} else {
		$sql_from_join = ' LEFT JOIN lic_application_feature_map AS lafm
			ON lsfu.service_id = lafm.service_id
			AND lsfu.feature_name = lafm.feature_name ';
	}

	$sort_order = 'ORDER BY server_name, lsfu.feature_name';

	$sql_query = "SELECT lsfu.*, ls.*, lp.*, (feature_inuse_licenses/feature_max_licenses)*100 AS utilization
		FROM lic_services_feature_use AS lsfu
		INNER JOIN lic_services AS ls
		ON ls.service_id = lsfu.service_id
		INNER JOIN lic_pollers AS lp
		ON ls.poller_id=lp.id
		$sql_from_join
		$sql_where
		$sort_order";

	$vendors = db_fetch_cell_prepared("SELECT COUNT(DISTINCT server_name)
        FROM lic_services_feature_use AS lsfu
        INNER JOIN lic_services AS ls
        ON ls.service_id = lsfu.service_id
        INNER JOIN lic_pollers AS lp
        ON ls.poller_id=lp.id
        $sql_from_join
        $sql_where
        $sort_order", $sql_params);

	//printf($sql_query . "\n");

	//print "<pre>";
	//print_r(db_fetch_assoc($sql_query));
	//print "</pre>";

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function lic_view_lics() {
	global $title, $report, $colors, $lic_search_types, $lic_rows_selector, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;

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
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '30'
			),
		'columns' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '3'
			),
		'filter' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => ''
			),
		'vendor' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'location' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'keyfeat' => array(
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '(true|false)')),
			'pageset' => true,
			'default' => 'false'
			)
	);

	validate_store_request_vars($filters, 'sess_lss');
	/* ================= input validation ================= */

	lic_set_minimum_page_refresh();

	$title = 'IBM Spectrum LSF RTM - License Service Current Use Overview';
	general_header();

	html_start_box(__('Usage Filters', 'license'), '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	$sql_where = '';
	$sql_from_join = '';
	$vendors = 0;

	$results = lic_view_get_lics_records($vendors, $sql_where, $sql_from_join);

	?>
	<script type='text/javascript'>

	function resize_vendor_div() {
		waitForFinalEvent(function() {
		lic_table_width = $("tr#lic_vendor_tr").width();
		col_count = $('#columns').val();
		div_width = Math.floor((lic_table_width-col_count*2*4.5)/col_count);
		$("div.div_lic_vendor").each(function () {
			$(this).width(div_width);
			my_width = $(this).width();
			child_width = $(this).children("table").width();
			if(child_width > my_width) {
				$(this).css({ 'overflow-x': 'scroll' });
			}
		});
		}, 200, 'lic-resize-vendor-div');
	}

	function applyFilter() {
		strURL  = 'lic_service_summary.php?header=false';
		strURL += '&vendor='        + $('#vendor').val();
		strURL += '&location='      + $('#location').val();
		strURL += '&region='        + $('#region').val();
		strURL += '&columns='       + $('#columns').val();
		strURL += '&keyfeat='       + $('#keyfeat').is(':checked');
		strURL += '&refresh='       + $('#refresh').val();
		loadPageNoHeader(strURL);
		resize_vendor_div();
	}

	function clearFilter() {
		strURL  = 'lic_service_summary.php?header=false&clear=1';
		loadPageNoHeader(strURL);
		resize_vendor_div();
	}

	$(function() {
		$('#form_view_lics').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
		resize_vendor_div();
		$(window).resize(function (event) {
			resize_vendor_div();
		});
		$('#tab-license').click(function() {
			resize_vendor_div();
		});
	});

	</script>
	<?php

	$header_right_text = __('inuse / queued / total', 'license') . ' / % ' . __('used', 'license');
	html_start_box(__('Current License Overview', 'license'), '100%', '', '1', 'center', '', '');

	if (cacti_sizeof($results)) {
		$prev_service = '';
		$vendorspc    = get_request_var('columns');
		$width        = floor(100 / get_request_var('columns')) ;
		$lineno       = 0;
		$vendno       = -1;

		foreach($results as $l) {
			if ($prev_service != $l['server_name']) {
				$lineno       = 0;
				$vendno++;
				if ($prev_service != '') {
					print "</table></div>";
				}
				if ($vendno%$vendorspc ==0) {
					if ($vendno==0) {
						print '<tr id="lic_vendor_tr"><td style="padding: 0px">';
					} else {
						print '</td></tr><tr><td style="padding: 0px">'; //fix td padding 2px in paper-plane theme
					}
				}
				print "<div class='div_lic_vendor' id='div_lic_vendor_$vendno' style='padding:3.5px;border:1px solid #161616;float:left;'>";

				print "<table style='width:100%'>";

				$host = db_fetch_cell_prepared('SELECT id
					FROM host
					WHERE lic_server_id = ?',
					array($l['service_id']));

				$lic_url = $config['url_path'] . 'plugins/license/';

				$graph_url = '&nbsp;<a class="pic" href="' . html_escape($config['url_path'] . 'graph_view.php?action=preview&host_id=' . $host . '&page=1&graph_template=0&rfilter=') . '"><img src="' . $lic_url . 'images/view_graphs.gif" alt="" title="' . __esc('View Feature Graphs', 'license') . '"></a>';

				$chart_url = '&nbsp;<a class="pic" href="' . html_escape($lic_url . 'lic_lm_fusion.php?reset=1&service_id=' . $l['service_id'] . '&poller_type=' . $l['poller_type'] . '&users=-1&graphs=-1&show=-1&timestamp=-4&rows_selector=-1&top_bottom=1&feature=-1') . '"><img src="' . $lic_url . 'images/view_charts.gif" alt="" title="' . __esc('View Reports', 'license') . '"></a>';

				$usage_url = '&nbsp;<a class="pic" href="' . html_escape($lic_url . 'lic_usage.php?reset=1&query=1&filter=&vendor=-1&location=-1&status=yes&refresh=60&rows_selector=-1&service_id=' . $l['service_id'] . '&poller_type=' . $l['poller_type']) . '"><img src="' . $lic_url . 'images/view_usage.png" alt="" title="' . __esc('View License Usage', 'license') . '"></a>';

				print '<tr><td style="line-height:25px;vertical-align:middle"><a class="pic" href="' . html_escape($lic_url . 'lic_checkouts.php?query=1&filter=&type=-1&service_id=' . $l['service_id']) . '" class="hyper" style="font-size:1.5em" title="' . __esc('View License Checkouts', 'license') . '">' . html_escape($l['server_name']) . '</a><span style="padding-left:10px;vertical-align:15%">' . $graph_url . $chart_url . $usage_url . '</span></tr>';

				print '<tr class ="tableRow odd"><td>'. html_escape($l['server_portatserver']) . '</td><td style="white-space:nowrap;text-align:right">'. $header_right_text . '</td></tr>';
			}

			if (is_fully_utilized($l)) {
				$color = 'background-color:red';
			} else {
				$color = '';
			}
			if($lineno%2 == 0) {
				$line_class = 'class ="tableRow even"';
			} else {
				$line_class = 'class ="tableRow odd"';
			}

			if ($l['feature_max_licenses'] > 0) {
				$chart_url = '&nbsp;<a class="hyper" href="' . html_escape($lic_url . 'lic_lm_fusion.php?reset=1&service_id=' . $l['service_id'] . '&poller_type=' . $l['poller_type'] . '&users=-1&graphs=-2&show=-1&timestamp=-4&rows_selector=-1&top_bottom=1&feature=' . $l['feature_name']) . '"><i>' . html_escape($l['feature_name']) . '</i></a>';

				//print "<tr $line_class>" . '
				print "<tr $line_class". 'style="' . $color . '">
					<td style="white-space:nowrap">' . $chart_url . '</td><td class="nowrap right">' . ($l['feature_inuse_licenses'] > 0 ?
					'<a class="hyper linkEditMain" href="' . html_escape($lic_url . 'lic_checkouts.php?query=1&service_id=' . $l['service_id'] . '&type=-1&filter=' . $l['feature_name']) . '">' . number_format($l['feature_inuse_licenses']) . '</a> / ':number_format($l['feature_inuse_licenses'])) .
					number_format($l['feature_queued'])         . ' / ' .
					number_format($l['feature_max_licenses'])   . ' / ' .
					number_format($l['feature_inuse_licenses'] / $l['feature_max_licenses'] * 100, 1) . ' %' .
				'</td></tr>';
				$lineno++;
			}

			$prev_service = $l['server_name'];
		}
	} else {
		print '<tr><td>';
		print __('No License Records Found', 'license');
	}

	print '</td></tr>';

	html_end_box(false);

	bottom_footer();
}

function time_for_new_column(&$results, $breakno, $lineno) {
	$service_name = $results[$lineno]['server_name'];

	$linestogo = $breakno - ($lineno % $breakno);

	for ($i = $lineno; $i < $linestogo; $i++) {
		if ($service_name != $results[$i]['server_name']) {
			return true;
		}
	}

	return false;
}

function is_fully_utilized($result) {
	return $result['feature_max_licenses'] == $result['feature_inuse_licenses'];
}

function lic_filter() {
	global $colors, $config, $lic_refresh_interval, $lic_rows_selector;

	?>
	<tr class='odd'>
		<td>
			<form id='form_view_lics' action='lic_service_summary.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Vendor', 'license');?>
					</td>
					<td>
						<select id='vendor' onChange='applyFilter()'>
							<?php
							print '<option value="-1"' . (get_request_var('vendor') == '-1' ? ' selected':'') . '>' . __('All', 'license') . '</option>';
							print '<option value="-2"' . (get_request_var('vendor') == '-2' ? ' selected':'') . '>' . __('None', 'license') . '</option>';

							$vendors = db_fetch_assoc("SELECT DISTINCT server_vendor
								FROM lic_services AS ls
								INNER JOIN lic_pollers AS lp
								ON ls.poller_id=lp.id
								WHERE server_vendor != ''
								AND disabled = ''
								AND server_vendor IS NOT NULL
								ORDER BY server_vendor");

							if (cacti_sizeof($vendors)) {
								foreach ($vendors as $vendor) {
									print '<option value="' . html_escape($vendor['server_vendor']) .'"' . (get_request_var('vendor') == $vendor['server_vendor'] ? ' selected':'') . '>' . html_escape($vendor['server_vendor']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Location', 'license');?>
					</td>
					<td>
						<select id='location' onChange='applyFilter()'>
							<?php
							print '<option value="-1"' . (get_request_var('location') == '-1' ? ' selected':'') . '>' . __('All', 'license') . '</option>';
							print '<option value="-2"' . (get_request_var('location') == '-2' ? ' selected':'') . '>' . __('None', 'license') . '</option>';

							$servers = db_fetch_assoc("SELECT DISTINCT server_location
								FROM lic_services AS ls
								INNER JOIN lic_pollers AS lp
								ON ls.poller_id=lp.id
								WHERE server_location != ''
								AND server_location IS NOT NULL
								ORDER BY server_location");

							if (cacti_sizeof($servers)) {
								foreach ($servers as $server) {
									print '<option value="' . $server['server_location'] .'"'; if (get_request_var('location') == $server['server_location']) { print ' selected'; } print '>' . $server['server_location'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php key_features();?>
					<td>
						<input type='button' id='go' value='<?php print __esc('Go', 'license');?>' title='<?php print __esc('Search', 'license');?>' onClick='applyFilter()'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __esc('Clear', 'license');?>' title='<?php print __esc('Clear Filters', 'license');?>' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Region', 'license');?>
					</td>
					<td>
						<select id='region' onChange='applyFilter()'>
							<?php
							print '<option value="-1"' . (get_request_var('region') == '-1' ? ' selected':'') . '>' . __('All', 'license') . '</option>';
							print '<option value="-2"' . (get_request_var('region') == '-2' ? ' selected':'') . '>' . __('None', 'license') . '</option>';

							$regions = db_fetch_assoc("SELECT DISTINCT server_region
								FROM lic_services AS ls
								INNER JOIN lic_pollers AS lp
								ON ls.poller_id=lp.id
								WHERE server_region != ''
								AND server_region IS NOT NULL
								ORDER BY server_region");

							if (cacti_sizeof($regions)) {
								foreach ($regions as $region) {
									print '<option value="' . html_escape($region['server_region']) . '"' . (get_request_var('region') == $region['server_region'] ? ' selected':'') . '>' . html_escape($region['server_region']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Columns', 'license');?>
					</td>
					<td>
						<select id='columns' onChange='applyFilter()'>
							<?php
							$columns = array(1, 2, 3, 4, 5);

							if (cacti_sizeof($columns)) {
								foreach ($columns as $c) {
									print '<option value="' . $c .'"' . (get_request_var('columns') == $c ? ' selected':'') . '>' . $c . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'license');?>
					</td>
					<td>
						<select id='refresh' onChange='applyFilter()'>
							<?php
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							foreach($lic_refresh_interval as $key => $value) {
								if ($key >= $max_refresh) {
									print '<option value="' . $key . '"' . (get_request_var('refresh') == $key ? ' selected':'') . '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

