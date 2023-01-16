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
include_once('./plugins/license/include/lic_functions.php');

lic_view_license_file_details();

function lic_view_get_lics_records(&$sql_where, &$sql_from_join, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	$current_time = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));

	/* license server sql where */
	if (get_request_var('service_id') == -1) {
		/* Show all items */
	} else {
		$sql_where = (strlen($sql_where) ? ' AND':'WHERE') . " (ls.service_id=?)";
		$sql_params[] = get_request_var('service_id');
	}

	/* license location sql where */
	if (get_request_var('location') == -1 || get_request_var('location') == '') {
		/* Show all items */
	} elseif (get_request_var('location') == -2) {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (ls.server_location='')";
	} else {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (ls.server_location=?)";
		$sql_params[] = get_request_var('location');
	}

	/* region location sql where */
	if (get_request_var('region') == -1 || get_request_var('region') == '') {
		/* Show all items */
	} elseif (get_request_var('region') == -2) {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (ls.server_region='')";
	} else {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (ls.server_region=?)";
		$sql_params[] = get_request_var('region');
	}

	if (get_request_var('poller_type') != 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' lp.poller_type=?';
		$sql_params[] = get_request_var('poller_type');
	}

	if (get_request_var('keyfeat') == 'true') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' lafm.critical = 1';
		$sql_from_join = " INNER JOIN lic_application_feature_map AS lafm
			ON lsf.service_id = lafm.service_id
			AND lsf.feature_name = lafm.feature_name ";
	}else{
		$sql_from_join = " LEFT JOIN lic_application_feature_map AS lafm
			ON lsf.service_id = lafm.service_id
			AND lsf.feature_name = lafm.feature_name ";
	}

	if (get_request_var('vendor') != -1) {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' (server_vendor=?)';
		$sql_params[] = get_request_var('vendor');
	}

	if (get_request_var('feature_status') != -1) {
		switch(get_request_var('feature_status')) {
		case '1':	// valid
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (UNIX_TIMESTAMP(lsf.feature_expiration_date) = 0
				OR UNIX_TIMESTAMP(lsf.feature_expiration_date) > ?)';
			$sql_params[] = $current_time;
			break;
		case '2':	// Expired
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' (UNIX_TIMESTAMP(lsf.feature_expiration_date) != 0
				AND UNIX_TIMESTAMP(lsf.feature_expiration_date) < ?)';
			$sql_params[] = $current_time;
			break;
		}
	}

	/* filter sql where */
	$filter = get_request_var('filter');

	if (strlen($filter)) {
		if (substr_count('perpetual', strtolower(get_request_var('filter')))) {
			$filter = '0000-%-01';
		}
	}

	if ($filter != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " (lsf.feature_version LIKE ?
			OR lsf.feature_name LIKE ?
			OR lafm.user_feature_name LIKE ?
			OR lsf.feature_expiration_date LIKE ?
			OR ls.server_name LIKE ?)";
		$sql_params[] = '%'. $filter . '%';
		$sql_params[] = '%'. $filter . '%';
		$sql_params[] = '%'. $filter . '%';
		$sql_params[] = '%'. $filter . '%';
		$sql_params[] = '%'. $filter . '%';
	}

	$sort_order = get_order_string();

	$sql_query = "SELECT
		lsf.*, UNIX_TIMESTAMP(lsf.feature_expiration_date) as expiry_date,
		ls.server_name, ls.poller_id, lp.poller_type, ls.server_vendor,
		ls.server_location, ls.server_region, ls.server_department,
		ls.server_licensetype
		FROM lic_services AS ls
		INNER JOIN lic_services_feature AS lsf
		ON ls.service_id=lsf.service_id
		INNER JOIN lic_pollers AS lp
		ON ls.poller_id=lp.id
		$sql_from_join
		$sql_where
		$sort_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function lic_view_license_file_details() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $config;
	$sql_params = array();

	$filters = array(
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'rows_selector' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
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
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'service_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'feature_status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'location' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'keyfeat' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'false',
			'options' => array('options' => 'sanitize_search_string')
			),
		'vendor' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	if (!isset_request_var('reset')) {
		if (check_changed('poller_type', 'sess_lvsd_poller_type') && get_request_var('poller_type')!='0') {
			kill_session_var('sess_lvsd_service_id');
			kill_session_var('sess_lvsd_region');
			kill_session_var('sess_lvsd_keyfeat');
			kill_session_var('sess_lvsd_vendor');

			unset_request_var('service_id');
			unset_request_var('keyfeat');
			unset_request_var('region');
			unset_request_var('vendor');
		}
	}

	validate_store_request_vars($filters, 'sess_lvsd');

	$title = 'IBM Spectrum LSF RTM - License File Details';
	general_header();

	html_start_box(__('License Inventory Filter'), '100%', '', '3', 'center', '');
	view_lics_inventory_filter_table();
	html_end_box(true);

	$sql_where = '';
	$sql_from_join = '';

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	} elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$lics_results = lic_view_get_lics_records($sql_where, $sql_from_join, true, $row_limit, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'lic_details.php?header=false&filter=' + $('#filter').val();
		strURL += '&location=' + $('#location').val();
		strURL += '&poller_type=' + $('#poller_type').val();
		strURL += '&region=' + $('#region').val();
		strURL += '&keyfeat=' + $('#keyfeat').is(':checked');
		strURL += '&service_id=' + $('#service_id').val();
		strURL += '&feature_status=' + $('#feature_status').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		strURL += '&vendor=' + $('#vendor').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'lic_details.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_lic_view').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});
	});

	</script>
	<?php

	html_start_box('', '100%', '', '3', 'center', '');

	$rows_query_string = "SELECT
		COUNT(*)
		FROM lic_services AS ls
		INNER JOIN lic_services_feature AS lsf
		ON ls.service_id=lsf.service_id
		INNER JOIN lic_pollers AS lp
		ON ls.poller_id=lp.id
		$sql_from_join
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'nosort'                   => array('display' => __('Actions')),
		'feature_name'             => array('display' => __('Feature Name', 'license'), 'sort' => 'ASC'),
		'server_vendor'            => array('display' => __('Vendor', 'license'), 'sort' => 'DESC'),
		'server_name'              => array('display' => __('Service Name', 'license'), 'sort' => 'ASC'),
		'server_location'          => array('display' => __('Location', 'license'), 'align' => 'left', 'sort' => 'DESC'),
		'server_region'            => array('display' => __('Region', 'license'), 'align' => 'left', 'sort' => 'DESC'),
		'server_department'        => array('display' => __('Department', 'license'), 'align' => 'left', 'sort' => 'DESC'),
		'server_licensetype'       => array('display' => __('Type'), 'license', 'align' => 'left', 'sort' => 'DESC'),
		'feature_version'          => array('display' => __('Version', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'feature_number_to_expire' => array('display' => __('Licenses', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'feature_expiration_date'  => array('display' => __('Expiring', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'nosort1'                  => array('display' => __('Status', 'license'), 'align' => 'right')
	);

	$nav = html_nav_bar('lic_details.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Features'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$inuse_details = array_rekey(db_fetch_assoc("SELECT DISTINCT
		CONCAT_WS('', service_id, '_', feature_name, '') AS id
		FROM lic_services_feature_details"), 'id', 'id');

	$inuse_events = array_rekey(db_fetch_assoc("SELECT service_id AS id
		FROM lic_services
		WHERE file_path!=''"), 'id', 'id');

	if (cacti_sizeof($lics_results) > 0) {
		foreach ($lics_results as $lic) {
			form_alternate_row();

			$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT
				graph_local.id FROM host_snmp_cache
				INNER JOIN graph_local
				ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
				AND (host_snmp_cache.host_id=graph_local.host_id)
				AND (host_snmp_cache.snmp_index=graph_local.snmp_index)
				WHERE (host_snmp_cache.snmp_index=?)
				AND (host_snmp_cache.field_name='gridFeatureName')", array($lic['service_id'] . '-' . $lic['feature_name'] . '-' . str_replace(' ', '_', $lic['vendor_daemon'])));

			if (cacti_sizeof($local_graph_ids)) {
				$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

				foreach($local_graph_ids as $graph) {
					$graph_select .= $graph['id'] . '%2C';
				}
			} else {
				unset($graph_select);
			}
			?>
			<td style='white-space:nowrap;'>
				<?php if (isset($graph_select)) {?>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/license/images/view_graphs.gif' alt='' title='View Feature Graphs' align='absmiddle' border='0'></a>
				<?php } else {?>
					<img src='<?php print $config['url_path']?>plugins/license/images/view_none.gif' border='0' align='absmiddle'>
				<?php }?>
				<a href='<?php print htmlspecialchars($config['url_path'] . 'plugins/license/lic_lm_fusion.php?reset=true&service_id=' . $lic['service_id'] . '&feature=' . $lic['feature_name']. '&user=-1&type=utilization&top_bottom=1&host=-1&vendor=-1&graphs=-2&poller_type='.$lic['poller_type']);?>' title='View Charts'><img src='<?php print $config['url_path'];?>plugins/license/images/view_charts.gif' align='absmiddle' border='0'></a>
				<?php if (lic_checkouts_enabled() && (isset($inuse_details[$lic['service_id'] . '_' . $lic['feature_name']]))) {?>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=true&filter=' . $lic['feature_name']. '&service_id=' . $lic['service_id'] . '&page=1&poller_type='.$lic['poller_type']);?>'><img src='<?php print $config['url_path'];?>plugins/license/images/view_checkouts.gif' alt='' title='View License Checkouts' align='absmiddle' border='0'></a>
				<?php } else {?>
					<img src='<?php print $config['url_path']?>plugins/license/images/view_none.gif' border='0' align='absmiddle'>
				<?php } ?>
			</td>
			<td style='white-space:nowrap;' title='<?php print htmlspecialchars(get_feature_name($lic['feature_name'], $lic['service_id']));?>'><?php print filter_value(title_trim(get_feature_name($lic['feature_name'], $lic['service_id']), 50), get_request_var('filter'));?></td>
			<td style='white-space:nowrap;'><?php print $lic['server_vendor'];?></td>
			<td style='white-space:nowrap;'><?php print filter_value($lic['server_name'], get_request_var('filter'));?></td>
			<td align='left'><?php print $lic['server_location'];?></td>
			<td align='left'><?php print $lic['server_region'];?></td>
			<td align='left'><?php print $lic['server_department'];?></td>
			<td align='left'><?php print $lic['server_licensetype'];?></td>
			<td align='right'><?php print $lic['feature_version'];?></td>
			<td align='right'><?php print number_format($lic['feature_number_to_expire']);?></td>
			<td align='right' style='white-space:nowrap;'><?php print filter_value(substr_count($lic['feature_expiration_date'], '0000') ? 'Perpetual' : substr(strtoupper($lic['feature_expiration_date']),0,10), get_request_var('filter'));?></td>
			<td align='right'>
			<?php
				switch(check_expirations_error($lic)) {
				case 0:
					print "<span style='color:green'>Valid</span>";
					break;
				case 1:
					print "<span style='color:blue'>Expired</span>";
					break;
				case 2:
					print "<span style='color:red'>Error</span>";
					break;
				}
			?>
			</td>
			</tr>
			<?php
		}
		html_end_box(false);
		/* put the nav bar on the bottom as well */
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No License Features Found</em></td></tr>";
		html_end_box(false);
	}

	bottom_footer();
}

function view_lics_inventory_filter_table() {
	global $config, $lic_rows_selector;

	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_details.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Service', 'license');?>
					</td>
					<td>
						<select id='service_id' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('service_id') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$servers = db_fetch_assoc('SELECT DISTINCT
									ls.service_id AS id, server_name AS name
									FROM lic_services AS ls
									INNER JOIN lic_services_feature AS lsf
									ON ls.service_id=lsf.service_id
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									ORDER BY server_name');
							} else {
								$servers = db_fetch_assoc_prepared('SELECT DISTINCT
									ls.service_id AS id, server_name AS name
									FROM lic_services AS ls
									INNER JOIN lic_services_feature AS lsf
									ON ls.service_id=lsf.service_id
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE lp.poller_type=?
									ORDER BY server_name', array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($servers)) {
								foreach ($servers as $server) {
									print '<option value="' . $server['id'] .'"'; if (get_request_var('service_id') == $server['id']) { print ' selected'; } print '>' . $server['name'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Manager', 'license');?>
					</td>
					<td>
						<select id='poller_type' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('poller_type') == '0') {?> selected<?php }?>>All</option>
							<?php
							$managers = db_fetch_assoc('SELECT id, name
								FROM lic_managers
								WHERE disabled=""
								ORDER BY name');

							if (cacti_sizeof($managers)) {
								foreach ($managers as $manager) {
									print '<option value="' . $manager['id'] .'"'; if (get_request_var('poller_type') == $manager['id']) { print ' selected'; } print '>' . html_escape($manager['name']) . '</option>';
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
							<option value='-1'<?php if (get_request_var('location') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('location') == '-2') {?> selected<?php }?>>None</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$locations = db_fetch_assoc("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									ORDER BY server_location");
							} else {
								$locations = db_fetch_assoc_prepared("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									AND lp.poller_type=?
									ORDER BY server_location", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($locations)) {
								foreach ($locations as $location) {
									print '<option value="' . $location['server_location'] .'"'; if (get_request_var('location') == $location['server_location']) { print ' selected'; } print '>' . $location['server_location'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Region', 'license');?>
					</td>
					<td>
						<select id='region' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('region') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('region') == '-2') {?> selected<?php }?>>None</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$regions = db_fetch_assoc("SELECT DISTINCT server_region
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_region != ''
									AND server_region IS NOT NULL
									ORDER BY server_region");
							} else {
								$regions = db_fetch_assoc_prepared("SELECT DISTINCT server_region
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_region != ''
									AND server_region IS NOT NULL
									AND lp.poller_type=?
									ORDER BY server_region", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($regions) > 0) {
								foreach ($regions as $region) {
									print '<option value="' . $region['server_region'] .'"'; if (get_request_var('region') == $region['server_region']) { print ' selected'; } print '>' . $region['server_region'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php key_features();?>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Vendor', 'license');?>
					</td>
					<td>
						<select id='vendor' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('vendor') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (isempty_request_var('poller_type')) {
								$vendors = db_fetch_assoc("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_vendor != ''
									AND disabled = ''
									AND server_vendor IS NOT NULL
									ORDER BY server_vendor");
							} else {
								$vendors = db_fetch_assoc_prepared("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_vendor != ''
									AND disabled = ''
									AND server_vendor IS NOT NULL
									AND lp.poller_type=?
									ORDER BY server_vendor", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($vendors)) {
								foreach ($vendors as $vendor) {
									print '<option value="' . $vendor['server_vendor'] .'"'; if (get_request_var('vendor') == $vendor['server_vendor']) { print ' selected'; } print '>' . $vendor['server_vendor'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Expiration', 'license');?>
					</td>
					<td>
						<select id='feature_status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('feature_status') == '-1') { print ' selected'; }?>>All</option>
							<option value='1'<?php if (get_request_var('feature_status') == '1') { print ' selected'; }?>>Valid</option>
							<option value='2'<?php if (get_request_var('feature_status') == '2') { print ' selected'; }?>>Expired</option>
						</select>
					</td>
					<td>
						<?php print __('Records', 'license');?>
					</td>
					<td>
						<select id='rows_selector' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($lic_rows_selector)) {
								foreach ($lic_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows_selector') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='35' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

