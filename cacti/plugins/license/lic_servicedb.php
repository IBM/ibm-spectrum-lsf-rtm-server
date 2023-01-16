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

lic_services();

function get_edit_href($id) {
	global $config;

	if (api_user_realm_auth('lic_servers.php')) {
		return "<a href='" . $config['url_path'] . "plugins/license/lic_servers.php?action=edit&service_id=$id' title='Edit License Service'><img src='" .  $config['url_path'] . "plugins/license/images/view_config.gif' border='0' align='absmiddle'></a>";
	}
}

function lic_services() {
	global $title, $report, $lic_search_types, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;
	global $lic_license_server_actions, $config;
	$sql_params = array();


	$filters = array(
		'rows_selector' => array(
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
			'default' => 30
			),
		'quorum' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0',
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'location' => array(
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
			),
		'region' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'vendor' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
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
	);

	validate_store_request_vars($filters, 'sess_lsdb');

	lic_set_minimum_page_refresh();

	$title = 'IBM Spectrum LSF RTM - License Service Dashboard';

	general_header();

	html_start_box(__('License Service Filters'), '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	$sql_where  = '';

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$services = lic_get_license_service_records($sql_where, true, $row_limit, $sql_params);

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = urlPath + 'plugins/license/lic_servicedb.php?header=false';
		strURL += '&filter='        + $('#filter').val();
		strURL += '&poller_type='   + $('#poller_type').val();
		strURL += '&location='      + $('#location').val();
		strURL += '&region='        + $('#region').val();
		strURL += '&quorum='        + $('#quorum').val();
		strURL += '&vendor='        + $('#vendor').val();
		strURL += '&status='        + $('#status').val();
		strURL += '&quorum='        + $('#quorum').val();
		strURL += '&refresh='       + $('#refresh').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = urlPath + 'plugins/license/lic_servicedb.php?header=false&clear=true';
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

	$rows_query_string = "SELECT COUNT(*)
		FROM lic_services AS ls
		INNER JOIN lic_pollers AS lp
		ON ls.poller_id=lp.id
		INNER JOIN lic_managers AS lm
		ON lp.poller_type=lm.id
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'nosorta'            => array('display' => __('Actions')),
		'name'               => array('display' => __('Service Name', 'license'), 'sort' => 'ASC'),
		'server_vendor'      => array('display' => __('Vendor', 'license'), 'sort' => 'ASC'),
		'server_department'  => array('display' => __('Department', 'license'), 'sort' => 'ASC'),
		'manager_name'       => array('display' => __('Manager', 'license'), 'sort' => 'ASC'),
		'status'             => array('display' => __('Status', 'license'), 'sort' => 'ASC'),
		'nosort'             => array('display' => __('Multi-server Status', 'license')),
		'cur_time'           => array('display' => __('Current', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'avg_time'           => array('display' => __('Average', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'max_time'           => array('display' => __('Max', 'license'), 'align' => 'right', 'sort' => 'DESC'),
		'nosort1'            => array('display' => __('Features', 'license'), 'align' => 'right'),
		'nosort3'            => array('display' => __('Features Used', 'license'), 'align' => 'right'),
		'nosort2'            => array('display' => __('Licenses Used', 'license'), 'align' => 'right'),
		'availability'       => array('display' => __('Availability', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'status_fail_date'   => array('display' => __('Last Failed', 'license'), 'align' => 'right', 'sort' => 'ASC')
	);

	$nav = html_nav_bar('lic_servicedb.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Services'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$disabled = true;
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$inuse_pas = array_rekey(
		db_fetch_assoc('SELECT DISTINCT service_id AS id
			FROM lic_services_feature_details'),
		'id', 'id'
	);

	$inuse_events = array_rekey(
		db_fetch_assoc('SELECT service_id AS id
			FROM lic_services
			WHERE file_path!=""'),
		'id', 'id'
	);

	$inven_pas = array_rekey(
		db_fetch_assoc('SELECT DISTINCT service_id AS id
			FROM lic_services_feature'),
		'id', 'id'
	);

	$stats_pas = array_rekey(
		db_fetch_assoc('SELECT DISTINCT service_id AS id
			FROM lic_daily_stats_traffic AS ldst'),
		'id', 'id'
	);

	if (cacti_sizeof($services)) {
		foreach ($services as $service) {
			$title = '';

			$service_ha = get_service_type($service['id']);
			$server_port_query = db_fetch_cell_prepared('SELECT server_portatserver
				FROM lic_services
				WHERE service_id=?', array($service['id']));
			$portatservers = explode(':', $server_port_query);
			$server_count = db_fetch_cell_prepared('SELECT COUNT(*) FROM lic_servers WHERE service_id=?', array($service['id']));

			if (($server_count >= 2 || cacti_sizeof($portatservers) >= 2)
				&& (isset($service_ha['9190583ab345af80da86efd5d683eb72'])
					|| current($service_ha) == 3)) { // FLEXlm or Quorum

				$quorum_sql_query = db_fetch_assoc_prepared('SELECT name, status, type
					FROM lic_servers
					WHERE service_id=?', array($service['id']));

				foreach($quorum_sql_query as $result){
					$title .= $result['name'] . '(' . $result['type'] .'):' . $result['status'] . '; ';
				}
			}else if (($server_count ==2 || cacti_sizeof($portatservers) == 2)
				&& (isset($service_ha['33fb6598074464bc113893507c623ee0'])
					|| current($service_ha) == 2)) { // RLM or Dual

				$i=0;
				foreach ($portatservers as $pserver){
					$tservers=explode('@', $pserver);

					$server_status= db_fetch_cell_prepared('SELECT status
						FROM lic_servers
						WHERE service_id= ?
						AND name = ?', array($service['id'], $tservers[1]));

					if ($i==0) {
						$title .= $tservers[1] . '(PRIMARY):' . $server_status . '; ';
					} else {
						$title .= $tservers[1] . '(FAILOVER):' . $server_status . '; ';
					}

					$i++;
				}
			} else {
				$title = '';
			}

			form_alternate_row();

			$details_page  = htmlspecialchars($config['url_path'] . 'plugins/license/lic_details.php?reset=true&status=-1&filter=&location=-1&feature_stats=-1&vendor=-1&service_id='.$service['id'] . '&poller_type=' . $service['poller_type']);

			$usage_page    = htmlspecialchars($config['url_path'] . 'plugins/license/lic_usage.php?reset=true&filter=&vendor=-1&location=-1&status=yes&refresh=60&rows_selector=-1&service_id=' . $service['id'] . '&poller_type=' . $service['poller_type']);

			$checkout_page = htmlspecialchars($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=true&filter=&lsf=-1&host=-1&user=-1&service_id=' . $service['id'] . '&poller_type=' . $service['poller_type']);

			$distrib_page  = htmlspecialchars($config['url_path'] .
				'plugins/license/lic_dist_reports.php' .
				'?service_id='            . $service['id'] .
				'&poller_type=0'          .
				'&user=-1'                .
				'&predefined_timespan=11' .
				'&host=-2'                .
				'&keyfeat=false'          .
				'&measure=-2'             .
				'&show=-1'                .
				'&rows_selector=10'       .
				'&feature=-1');

			$reports_page  = htmlspecialchars($config['url_path'] .
				'plugins/license/lic_lm_fusion.php' .
				'?reset=true'                .
				'&service_id='            . $service['id'] .
				'&poller_type=0'          .
				'&user=-1'                .
				'&predefined_timespan=11' .
				'&feature=-1');

			?>
			<td style='white-space:nowrap;'>
				<?php print get_edit_href($service['id']);?>
				<?php if (isset($inven_pas[$service['id']])) {?>
				<a href='<?php print $details_page;?>' title='View License Inventory'><img src='<?php print $config['url_path'];?>plugins/license/images/view_inventory.gif' align='absmiddle' border='0'></a>
				<?php } else {?>
				<img src='<?php print $config['url_path'];?>plugins/license/images/view_none.gif' align='absmiddle' border='0'>
				<?php }?>

				<?php if (isset($stats_pas[$service['id']])) {?>
				<a href='<?php print $usage_page;?>' title='View License Usage'><img src='<?php print $config['url_path'];?>plugins/license/images/view_usage.png' align='absmiddle' border='0'></a>
				<a href='<?php print $reports_page;?>' title='View Reports'><img src='<?php print $config['url_path'];?>plugins/license/images/view_charts.gif' align='absmiddle' border='0'></a>
				<!--a href='<?php print $distrib_page;?>' title='View License Distribution'><img src='<?php print $config['url_path'];?>plugins/license/images/view_distribution.png' align='absmiddle' border='0'></a-->
				<?php } else {?>
				<img src='<?php print $config['url_path'];?>plugins/license/images/view_none.gif' align='absmiddle' border='0'>
				<img src='<?php print $config['url_path'];?>plugins/license/images/view_none.gif' align='absmiddle' border='0'>
				<!--img src='<?php print $config['url_path'];?>plugins/license/images/view_none.gif' align='absmiddle' border='0'-->
				<?php }?>


				<?php if (isset($inuse_pas[$service['id']])) {?>
				<a href='<?php print $checkout_page;?>' title='View License Checkouts'><img src='<?php print $config['url_path'];?>plugins/license/images/view_checkouts.gif' align='absmiddle' border='0'></a>
				<?php } else {?>
				<img src='<?php print $config['url_path'];?>plugins/license/images/view_none.gif' align='absmiddle' border='0'>
				<?php }?>
			</td>
			<td style='white-space:nowrap;'><strong><?php print filter_value($service['name'], get_request_var('filter'));?></strong></td>
			<td style='white-space:nowrap;'><?php print $service['server_vendor'];?></td>
			<td style='white-space:nowrap;'><?php print filter_value($service['server_department'], get_request_var('filter'));?></td>
			<td style='white-space:nowrap;'><?php print $service['manager_name'];?></td>
			<td><?php
				$service_status = lic_get_quorum_status($service['id']);

				if (strstr($service_status, 'Up')){
					$service_status = 3;
				}else if (strstr($service_status, 'Down')){
					$service_status = 1;
				}

				if ($service_status == 'N/A'){
					print get_colored_device_status(($service['disabled'] == 'on' ? true : false), $service['status']);
				} else {
					print get_colored_device_status(($service['disabled'] == 'on' ? true : false),  $service_status);
				}
				?>
			</td>
			<td title='<?php print $title;?>'><?php
				if ($service['status'] == 0){
					print lic_get_colored_quorum_status(($service['disabled'] == 'on' ? true:false), '');
				} else {
					print lic_get_quorum_status($service['id'], false);
				}
				?>
			</td>
			<td style='white-space:nowrap;' align='right'><?php print round($service['cur_time'],1);?></td>
			<td style='white-space:nowrap;' align='right'><?php print round($service['avg_time'],1);?></td>
			<td style='white-space:nowrap;' align='right'><?php print round($service['max_time'],1);?></td>
			<td style='white-space:nowrap;' align='right'>
				<?php if (lic_get_count($service['id'], 'feature')) {?>
				<a class='linkEditMain' href='<?php print $details_page;?>'><?php print lic_get_count($service['id'], 'feature');?></a></td>
				<?php } else { print 'N/A</td>'; }?>
			<td style='white-space:nowrap;' align='right'>
				<?php if (lic_get_count($service['id'], 'checkouts')) {?>
				<a class='linkEditMain' href='<?php print $usage_page;?>'><?php print lic_get_count($service['id'], 'checkouts');?></a></td>
				<?php } else { print 'N/A</td>'; }?>
			<td style='white-space:nowrap;' align='right'>
				<?php if (lic_get_count($service['id'], 'inuse')) {?>
				<a class='linkEditMain' href='<?php print $checkout_page;?>'><?php print lic_get_count($service['id'], 'inuse');?></a></td>
				<?php } else { print 'N/A</td>'; }?>
			<td style='white-space:nowrap;' align='right'><?php print round($service['availability'],1). ' %';?></td>
			<td style='white-space:nowrap;' align='right'><?php print (substr_count($service['status_fail_date'], '0000') ? 'N/A' : $service['status_fail_date']);?></td>
			</tr>
			<?php
		}
		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No License Services Defined</em></td></tr>";
		html_end_box(false);
	}

	bottom_footer();
}

function lic_get_count($id, $feature) {
	$query = '';

	$feature_table   = 'lic_services_feature';
	$inuse_table     = 'lic_services_feature_use';
	$inuse_field     = 'feature_inuse_licenses';
	$checkouts_table = 'lic_services_feature_details';
	$field           = 'service_id';

	switch($feature){
		case 'feature':
			$query = 'SELECT COUNT(*) FROM '.$feature_table.' WHERE '.$field.' = '.$id;
			break;
		case 'inuse':
			$query = 'SELECT SUM('.$inuse_field.') AS sum FROM '.$inuse_table.' WHERE '.$field.' = '.$id;
			break;
		case 'checkouts':
			$query = 'SELECT COUNT(*) FROM (SELECT DISTINCT feature_name FROM '.$checkouts_table.' WHERE '.$field.' = '.$id.') AS A';
			break;
	};
	return number_format(db_fetch_cell($query)?:0);
}

function lic_filter() {
	global $lic_search_types, $lic_rows_selector, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_servicedb.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td width='60'>
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
						<?php print __('Status', 'license');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>Any</option>
							<option value='-3'<?php if (get_request_var('status') == '-3') {?> selected<?php }?>>Enabled</option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>>Disabled</option>
							<option value='-4'<?php if (get_request_var('status') == '-4') {?> selected<?php }?>>Not Up</option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>>Up</option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>>Down</option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>>Recovering</option>
							<option value='0'<?php if (get_request_var('status') == '0') {?> selected<?php }?>>Unknown</option>
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
								$servers = db_fetch_assoc("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									ORDER BY server_location");
							} else {
								$servers = db_fetch_assoc_prepared("SELECT DISTINCT server_location
									FROM lic_services AS ls
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									WHERE server_location != ''
									AND server_location IS NOT NULL
									AND lp.poller_type= ?
									ORDER BY server_location", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($servers)) {
								foreach ($servers as $server) {
									print '<option value="' . $server['server_location'] .'"'; if (get_request_var('location') == $server['server_location']) { print ' selected'; } print '>' . $server['server_location'] . '</option>';
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
									AND lp.poller_type= ?
									ORDER BY server_region", array(get_request_var('poller_type')));
							}

							if (cacti_sizeof($regions)) {
								foreach ($regions as $region) {
									print '<option value="' . $region['server_region'] .'"'; if (get_request_var('region') == $region['server_region']) { print ' selected'; } print '>' . $region['server_region'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Quorum', 'license');?>
					</td>
					<td>
						<select id='quorum' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('quorum') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('quorum') == '-2') {?> selected<?php }?>>Quorums</option>
							<option value='-3'<?php if (get_request_var('quorum') == '-3') {?> selected<?php }?>>Standalone</option>
						</select>
					</td>
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
					<td width='60'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
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
									AND lp.poller_type= ?
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
					<td>
						<?php print __('Refresh', 'license');?>
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
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

