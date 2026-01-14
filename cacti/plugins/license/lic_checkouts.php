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
include_once('./plugins/license/include/lic_functions.php');

$title = 'IBM Spectrum LSF RTM - License Server Feature Checkouts';

if (!isset_request_var('action')) { set_request_var('action', ''); }

switch(get_request_var('action')) {
case 'ajaxsearch':
	ajax_search();
	break;
default:
	view_checkouts();
	break;
}

function ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case 'couser':
			$values = _ajax_search("username");
			print json_encode($values);
			break;
		case 'feature':
			$values = _ajax_search("feature_name");
			$nvalues = array();
			if (cacti_sizeof($values)) {
				foreach($values as $value) {
					$nvalues[] = array(
						'label' => get_feature_name($value['label']),
						'value' => $value['value']
					);
				}
			}
			print json_encode($nvalues);
			break;
		}
	}
}

function _ajax_search($column_name) {
	$sql_where = '';
	$sql_where_args = array();
	$sql_join_lsf = '';
	$sql_join_lafm = '';

	// Make the SELECT portion of the query
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		$sql_select = "SELECT DISTINCT lsfd.$column_name AS label, lsfd.$column_name AS value
			FROM lic_services_feature_details AS lsfd ";

		$sql_postfix = " GROUP BY lsfd.feature_name, feature_version, server_name,
			username, hostname, lsfd.status, tokens_acquired, tokens_acquired_date, chkoutid ";
	} else {
		$sql_select = "SELECT DISTINCT lsfd.$column_name AS label, lsfd.$column_name AS value
			FROM lic_services_feature_details AS lsfd ";

		$sql_postfix = " GROUP BY lsfd.feature_name, username, lsfd.status ";
	}

	// Make the WHERE clause for the lsfd.service_id
	$sql_svc_id = "WHERE lsfd.service_id IN ( SELECT ls.service_id FROM lic_services AS ls INNER JOIN lic_pollers AS lp ON ls.poller_id=lp.id ";
	$sql_svc_id_len = strlen($sql_svc_id);
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		if (get_request_var('service_id') != -1) {
			$sql_svc_id .= (strlen($sql_svc_id) ? ' AND ':'WHERE ') . ' lsfd.service_id=? ';
			$sql_where_args[] = get_request_var('service_id');
		}
	}

	if (get_request_var('poller_type') != 0) {
		$sql_svc_id .= (strlen($sql_svc_id)  ? ' AND ':'WHERE ') . ' lp.poller_type=? ';
		$sql_where_args[] =  get_request_var('poller_type');
	}
	/* Close the first WHERE clause.  */
	if ($sql_svc_id_len != strlen($sql_svc_id)) {
		$sql_where .= $sql_svc_id . ') ';
	}

	// Add the WHERE section for particular Host or User.  Only one host and one user allowed
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		if (get_request_var('host') != -1) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " lsfd.hostname=? ";
			$sql_where_args[] = get_request_var('host');
		}
	}
	if (get_request_var('couser') != -1) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " lsfd.username=? ";
		$sql_where_args[] = get_request_var('couser');
	}

	if (get_request_var('keyfeat') == 'true' || get_request_var('term') != '') {
		$sql_join_lafm = " LEFT JOIN lic_application_feature_map AS lafm
			ON lafm.service_id=lsfd.service_id
			AND lafm.feature_name=lsfd.feature_name";

		if (get_request_var('keyfeat') == 'true') {
			$sql_where .= (strlen($sql_where)  ? ' AND ':'WHERE ') . ' lafm.critical=1';
		}

		if (get_request_var('term') != '') {
			set_request_var('term', str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), get_request_var("term")));
			switch(get_request_var('type')) {////(lsfd.feature_name=? OR lafm.user_feature_name=?)
				case 'user':
				$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " (lsfd.username LIKE '%".get_request_var('term')."%') ";
				break;
				case 'feature':
				$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " (lsfd.feature_name LIKE '%".get_request_var('term')."%' OR lafm.user_feature_name LIKE '%".get_request_var('term')."%') ";
				break;
			}
		}
	}

	$sort_order = '';

	if (isset_request_var('sort_column_array') && cacti_sizeof(get_request_var('sort_column_array')) > 0) {
		$sort_order .= ' ORDER BY ' . lic_build_order_string(get_request_var('sort_column_array'), get_request_var('sort_direction_array'));
	}

	$sql_query = $sql_select . $sql_join_lafm . $sql_where . " GROUP BY lsfd.feature_name, lsfd.username " . " LIMIT 20";

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_where_args);
}

function lic_view_get_lic_checkouts($apply_limits = true, $row_limit = 30, &$total_rows = array()) {
	$sql_where = '';
	$sql_where_args = array();
	$sql_join_lsf = '';
	$sql_join_lafm = '';
	$add_group_order = 0;        // Flag to remove ORDER and GROUP BY when no filter is provided
	$add_sort = 0;               // Flag to add the sort ORDER when filters are applied

	// Make the SELECT portion of the query.  When Roll-Up is used sum up the usage by feature and username
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		// With roll-up
		$sql_select = "SELECT ls.service_id, lsfd.feature_name, feature_version,
			server_name, username, hostname, lsfd.status, tokens_acquired,
			tokens_acquired_date, chkoutid, UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date) AS duration
			FROM lic_services_feature_details AS lsfd
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfd.service_id ";

		$sql_postfix = " GROUP BY feature_name, chkoutid ";

		$sql_rows_select = "SELECT COUNT(*) FROM (
			SELECT lsfd.feature_name
			FROM lic_services_feature_details AS lsfd
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfd.service_id ";

	} else {
		// No roll-up query
		$sql_select = "SELECT lsfd.feature_name, 'N/A' AS feature_version,
			'N/A' AS server_name, username, 'N/A' AS hostname, lsfd.status,
			COUNT(*) AS instances,
			SUM(tokens_acquired) AS tokens_acquired,
			MIN(tokens_acquired_date) AS min_date,
			MAX(tokens_acquired_date) AS max_date, 'N/A' AS chkoutid,
			SUM(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(tokens_acquired_date)) AS duration
			FROM lic_services_feature_details AS lsfd
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfd.service_id ";

		$sql_postfix = " GROUP BY feature_name, username, lsfd.status ";

		$sql_rows_select = "SELECT COUNT(*) FROM (
			SELECT lsfd.feature_name
			FROM lic_services_feature_details AS lsfd
			INNER JOIN lic_services AS ls
			ON ls.service_id=lsfd.service_id " ;

	}

	/* Make the WHERE clause for the lsfd.service_id and poller_type.  Note:
		If rollup is selected the service_id is ignored in the GUI BUT the 
		service_id is still set in the GET request.  */
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		// This is NOT the Roll-Up. 
		if (get_request_var('service_id') != -1) {
			// We have a service_id.  This means the Poller_type is not needed
			$sql_where = 'WHERE lsfd.service_id=? ';
			$sql_where_args[] = get_request_var('service_id');
		} else {
			if (get_request_var('poller_type') != 0) { 
				// We do not have a service_id but have a poller_type
				$sql_where = "WHERE lsfd.service_id IN ( SELECT ls.service_id FROM lic_services AS ls INNER JOIN lic_pollers AS lp ON ls.poller_id=lp.id AND lp.poller_type=? )";
				$sql_where_args[] = get_request_var('poller_type');
			}
		}
	} else {
		// This is the Roll-Up case.  The service_id is ignored.  Only the poller_type is considered
		if (get_request_var('poller_type') != 0) { 
			$sql_where = "WHERE lsfd.service_id IN ( SELECT ls.service_id FROM lic_services AS ls INNER JOIN lic_pollers AS lp ON ls.poller_id=lp.id AND lp.poller_type=? )";
			$sql_where_args[] = get_request_var('poller_type');
		}
		$add_group_order = 1;
	}

	// Add the WHERE section for particular Host or User.  Only one host and one user allowed
	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		if (get_request_var('host') != -1) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " lsfd.hostname=? ";
			$sql_where_args[] = get_request_var('host');
			$add_group_order = 1;
		}
	}
	if (get_request_var('couser') != -1) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " lsfd.username=? ";
		$sql_where_args[] = get_request_var('couser');
		$add_group_order = 1;
	}

	// Add Key Features to Where clause.  Note filter will work slightly different 
	if (get_request_var('keyfeat') == 'true') {
		$sql_where .= (strlen($sql_where) == 0) ? ' WHERE ' : ' AND ';
		if (get_request_var('filter') != '') {
			$sql_where .= "lsfd.feature_name IN (SELECT lsfd.feature_name FROM lic_services_feature_details AS lsfd
							INNER JOIN lic_application_feature_map AS lafm
							ON lsfd.service_id = lafm.service_id AND lsfd.feature_name = lafm.feature_name
							WHERE lafm.critical=1 AND lafm.feature_name LIKE ?)";
			$sql_where_args[] = '%' . get_request_var('filter') . '%' ;
		} else {
			$sql_where .= "lsfd.feature_name IN (SELECT lsfd.feature_name FROM lic_services_feature_details AS lsfd
							INNER JOIN lic_application_feature_map AS lafm
							ON lsfd.service_id = lafm.service_id AND lsfd.feature_name = lafm.feature_name WHERE lafm.critical=1 )";
		}
		$add_group_order = 1;
	} else {
		if (get_request_var('filter') != '') {
			$sql_where .= (strlen($sql_where) == 0) ? ' WHERE ' : ' AND ';
			$sql_where .= "lsfd.feature_name LIKE ? ";
			$sql_where_args[] = '%' . get_request_var('filter') . '%' ;
		}
		$add_sort = 1;
	}

	if (get_request_var('type') == -1) {
		// All types
	} elseif (get_request_var('type') == -2) {
		$sql_where .= (strlen($sql_where)  ? ' AND ':'WHERE ') . ' (lsfd.status="START" OR lsfd.status="")';
	} else {
		$sql_where .= (strlen($sql_where)  ? ' AND ':'WHERE ') . ' lsfd.status="RESERVED"';
	}

	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		if (get_request_var('lsf') == -2) {
			/* show LSF only hosts */
			$sql_join_lsf = 'LEFT JOIN grid_hostinfo AS ghi ON ghi.host = lsfd.hostname ';
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE ') . " (ghi.host='' OR ghi.host IS NOT NULL)";

		} else if (get_request_var('lsf') == -3){
			$sql_join_lsf = 'LEFT JOIN grid_hostinfo AS ghi ON ghi.host = lsfd.hostname ';
			$sql_where .= ($sql_where != '' ? ' AND':'WHERE ') . ' (ghi.host IS NULL)';
		}
	}

	// GROUP and ORDER BY has a significant impact on performance
	if ($add_group_order) {
		$sort_order = get_order_string();
		$sql_query = $sql_select . $sql_join_lsf . $sql_where . $sql_postfix . $sort_order ;
		$sql_total_rows = $sql_rows_select . $sql_join_lsf . $sql_where . $sql_postfix . " ) AS a" ;
	} else {
		// Add the ORDER if there filters would reduce the amount of selected records enough to respond
		if($add_sort) {
			$sort_order = get_order_string();
			$sql_query = $sql_select . $sql_join_lsf . $sql_where . $sort_order ;
			$sql_total_rows = $sql_rows_select . $sql_join_lsf . $sql_where . " ) AS a" ;
		} else {
			$sql_query = $sql_select . $sql_join_lsf . $sql_where ;
			$sql_total_rows = $sql_rows_select . $sql_join_lsf . $sql_where . " ) AS a" ;
		}

	}

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	} else {
		$sql_query .= ' LIMIT 0,30';
	}

	// echo "SORT  = $sort_order  <br>";
	// echo "ROWS  SQL = $sql_total_rows  <br>" ;
	// echo "QUERY  SQL = $sql_query <br>" ;
	$total_rows = db_fetch_cell_prepared($sql_total_rows, $sql_where_args);

	//cacti_log("DEBUG: " . str_replace("\n", " ", $sql_query));

	return db_fetch_assoc_prepared($sql_query, $sql_where_args);
}

function lic_start_window($lic) {
	// TODO: Needs config to enable/disable and set shift width per feature
	$use_window = true;
	$window_width = 1200;

	$time_window = read_config_option('lic_time_window', true);
	if (!isset($time_window)) {
		$window_width = 1200;
	} elseif ($time_window == -1) {
		$use_window = false;
	} else {
		$window_width = $time_window;
	}

	$time = date('Y-m-d H:i:s', strtotime($lic['tokens_acquired_date']));

	$ret = 'ACTIVE';

	if ($use_window) {
		$utime = strtotime($time);
		$utime -= $window_width;
		$time = date('Y-m-d H:i:s', strtotime($lic['tokens_acquired_date'])+300);
		$ret = 'STARTED&date1=' . date('Y-m-d H:i:s', $utime) . "&date2=$time";
	}
	return $ret;
}

function view_checkouts() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;

	$filters = array(
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => 30
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
		'rows_selector' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'lsf' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0',
			),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-2',
			),
		'service_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'host' => array(
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
		'couser' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'addslashes')
			),
		'rollup' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'false',
			'options' => array('options' => 'sanitize_search_string')
			),
		'keyfeat' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '0',
			'options' => array('options' => 'sanitize_search_string')
			),
	);


	if (!isset_request_var('reset')) {
		if (check_changed('poller_type', 'sess_lvco_poller_type') && get_request_var('poller_type')!='0') {
			kill_session_var('sess_lvco_service_id');
			kill_session_var('sess_lvco_host');
			kill_session_var('sess_lvco_couser');

			unset_request_var('service_id');
			unset_request_var('host');
			unset_request_var('couser');
		}
	}

	if (isset_request_var('host') && strpos(get_request_var('host'), '.')) {
		$pieces = explode('.', get_request_var('host'));
		set_request_var('host',$pieces[0]);
	}

	if (check_changed('rollup', 'sess_lvco_rollup')) {
		set_request_var('sort_column', 'feature_name');
		set_request_var('sort_direction', 'ASC');
		remove_column_from_order_string('instances');
	// } else {
	// 	set_request_var('lsf',-1);
	// 	set_request_var('host',-1);
	}

	validate_store_request_vars($filters, 'sess_lvco');

	lic_set_minimum_page_refresh();

	general_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		if ($('#myfilter_value').val() != $('#myfilter').val() && $('#myfilter_label').val() != $('#myfilter').val() ) {
			$('#myfilter_value').val('');
		}

		if ($('#myfilter').val().length > 0) {
			$.ajax({
				url: 'lic_checkouts.php?action=ajaxsearch&type=feature&term=' + $('#myfilter').val()
				+ '&rollup=' + $('#rollup').is(':checked')
				+ '&host=' + $('#host').val()
				+ '&user=' + $('#couser').val()
				+ '&service_id=' + $('#service_id').val()
				+ '&poller_type=' + $('#poller_type').val()
				+ '&keyfeat=' + $('#keyfeat').is(':checked')
				+ '&filter=' + $('#myfilter_value').val()
				+ '&lsf=' + $('#lsf').val()
				+ '&raw_type=' + $('#type').val(),
				async: false,
				type: "GET",
				success: function (results) {
					$('#myfilter_value').val($('#myfilter').val());
					$.each(JSON.parse(results), function(index, result){
						if (result.label == $('#myfilter').val() || result.value == $('#myfilter').val()) {
							$('#myfilter_value').val(result.value);
							$('#myfilter').val(result.label);
							$('#myfilter_label').val(result.label);
							return;
						}
					});
					go();
				}
			});
		} else {
			go();
		}
	}

	function go() {
		strURL  = 'lic_checkouts.php?header=false';
		strURL += '&poller_type='   + $('#poller_type').val();
		strURL += '&service_id='    + $('#service_id').val();
		strURL += '&lsf='           + $('#lsf').val();
		strURL += '&type='          + $('#type').val();
		strURL += '&host='          + $('#host').val();
		strURL += '&filter='        + $('#myfilter_value').val();
		strURL += '&rollup='        + $('#rollup').is(':checked');
		strURL += '&keyfeat='       + $('#keyfeat').is(':checked');
		strURL += '&couser='          + $('#couser').val();
		strURL += '&refresh='       + $('#refresh').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'lic_checkouts.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	if ($('#rollup').is(':checked')) {
		$('#service_id').prop('disabled', true);
		$('#lsf').prop('disabled', true);
		$('#host').prop('disabled', true);
	}

	$(function() {
		$('#form_lic_view').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#myfilter').autocomplete({
			autoFocus: true,
			source: 'lic_checkouts.php?header=false&action=ajaxsearch&type=feature'
			+ '&rollup=' + $('#rollup').is(':checked')
			+ '&host=' + $('#host').val()
			+ '&user=' + $('#couser').val()
			+ '&service_id=' + $('#service_id').val()
			+ '&poller_type=' + $('#poller_type').val()
			+ '&keyfeat=' + $('#keyfeat').is(':checked')
			+ '&lsf=' + $('#lsf').val()
			+ '&raw_type=' + $('#type').val(),
			minLength: 0,
			select: function(event, ui) {
				$('#myfilter').val(ui.item.label);
				$('#myfilter_value').val(ui.item.value);
				$('#myfilter_label').val(ui.item.label);
				applyFilter();
			}
		});
	});

	</script>
	<?php

	html_start_box(__('Feature Checkout Filter'), '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	$sql_where  = '';
	$sql_from_join = '';
	$total_rows = 0;

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$lic_checkout_results = lic_view_get_lic_checkouts(true, $row_limit, $total_rows);

	html_start_box('', '100%', '', '3', 'center', '');

	if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {
		$display_text = array(
			'nosort'               => array('display' => __('Actions')),
			'feature_name'         => array('display' => __('Feature Name', 'license'), 'sort' => 'ASC'),
			'server_name'          => array('display' => __('Service Name', 'license'), 'sort' => 'ASC'),
			'feature_version'      => array('display' => __('Version', 'license'), 'sort' => 'ASC'),
			'username'             => array('display' => __('User Name', 'license'), 'sort' => 'ASC'),
			'hostname'             => array('display' => __('Host Name', 'license'), 'sort' => 'ASC'),
			'status'               => array('display' => __('Status', 'license'), 'sort' => 'DESC'),
			'tokens_acquired'      => array('display' => __('Tokens', 'license'), 'align' => 'right', 'sort' => 'DESC'),
			'duration'             => array('display' => __('Duration', 'license'), 'align' => 'right', 'sort' => 'DESC'),
			'tokens_acquired_date' => array('display' => __('Date', 'license'), 'align' => 'right', 'sort' => 'DESC')
		);
	} else {
		$display_text = array(
			'nosort'               => array('display' => __('Actions')),
			'feature_name'         => array('display' => __('Feature Name'), 'sort' => 'ASC'),
			'server_name'          => array('display' => __('Service Name'), 'sort' => 'ASC'),
			'feature_version'      => array('display' => __('Version'), 'sort' => 'ASC'),
			'username'             => array('display' => __('User Name'), 'sort' => 'ASC'),
			'hostname'             => array('display' => __('Host Name'), 'sort' => 'ASC'),
			'status'               => array('display' => __('Status'), 'sort' => 'DESC'),
			'instances'            => array('display' => __('Instances'), 'align' => 'right', 'sort' => 'DESC'),
			'tokens_acquired'      => array('display' => __('Tokens'), 'align' => 'right', 'sort' => 'DESC'),
			'duration'             => array('display' => __('Duration'), 'align' => 'right', 'sort' => 'DESC'),
			'min_date'             => array('display' => __('Min Date'), 'align' => 'right', 'sort' => 'DESC'),
			'max_date'             => array('display' => __('Max Date'), 'align' => 'right', 'sort' => 'DESC')
		);
	}

	$nav = html_nav_bar('lic_checkouts.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Checkouts'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($lic_checkout_results)) {
		foreach ($lic_checkout_results as $lic) {
			form_alternate_row();

			$hostname = $lic['hostname'];
			$clinf = db_fetch_row_prepared("SELECT host, clusterid
				FROM grid_hosts
				WHERE host=?", array($hostname));

			if (!empty($clinf)) {
				$info = '&clusterid=0&exec_host=' . $clinf['host'];
			} else {
				$info = '&clusterid=0&exec_host=' . $lic['hostname'];
			}

			?>
			<td style='width:1%;white-space:nowrap;'>
				<?php if (is_lsf_host($lic['hostname'])) { ?>
				<a href='<?php print htmlspecialchars($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&query=1'. $info . '&ajax_host_query=' . $lic['hostname'] . '&user=' . $lic['username'] .'&ajax_user_query=' .$lic['username'] . '&status=' . lic_start_window($lic) . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/license/images/view_jobs.gif' alt='' title='View Associated Jobs' align='absmiddle' border='0'></a>
				<?php }?>
			</td>
			<?php $displayed_ftname = get_feature_name($lic['feature_name'], (isset($lic['service_id']) ? $lic['service_id'] : 'N/A'));?>
			<td style='white-space:nowrap;' title='<?php print htmlspecialchars($displayed_ftname);?>'><?php print title_trim($displayed_ftname, 50);?></td>
			<td style='white-space:nowrap;'><?php print $lic['server_name'];?></td>
			<td><?php print ($lic['feature_version']? $lic['feature_version'] : '--');?></td>
			<?php lic_show_metadata_detailed('detailed', 'user', $lic['username']);?>
			<?php lic_show_metadata_detailed('simple', 'host', (lic_strip_domain($lic['hostname']) ? lic_strip_domain($lic['hostname']):'--'));?>
			<td><?php  print strtoupper($lic['status']);?></td>
			<?php if (get_request_var('rollup') != 'true' && get_request_var('rollup') != 'on') {?>
			<td style='text-align:right;'><?php  print $lic['tokens_acquired'];?></td>
			<td style='text-align:right;'><?php
				if ($lic['tokens_acquired_date'] == '0000-00-00 00:00:00') {
					print 'N/A';
				} elseif ($lic['duration'] < 0) {
					print 'Just Now';
				} else {
					print format_timing($lic['duration']);
				} ?>
			</td>
			<td style='text-align:right;white-space:nowrap;'><?php
				if ($lic['tokens_acquired_date'] == '0000-00-00 00:00:00'){
					print '--';
				} else {
					print substr($lic['tokens_acquired_date'], 0, -3);
				}
				?>
			</td>
			<?php } else {?>
			<td style='text-align:right;'><?php print "<a class='linkEditMain' href='lic_checkouts.php?reset=true&rollup=false&couser=" . $lic['username'] . "&filter=" . $lic['feature_name'] . "'>" . $lic['instances'] . "</a>";?></td>
			<td style='text-align:right;'><?php print $lic['tokens_acquired'];?></td>
			<td style='text-align:right;'><?php print format_timing($lic['duration']);?></td>
			<td style='text-align:right;white-space:nowrap;'><?php
				if ($lic['min_date'] == '0000-00-00 00:00:00'){
					print '--';
				} else {
					print substr($lic['min_date'], 0, -3);
				}
				?>
			</td>
			<td style='text-align:right;white-space:nowrap;'><?php
				if ($lic['max_date'] == '0000-00-00 00:00:00'){
					print '--';
				} else {
					print substr($lic['max_date'], 0, -3);
				}
				?>
			</td>
			<?php } ?>
			</tr>
			<?php
		}

		html_end_box(false);
		/* put the nav bar on the bottom as well */
		print $nav;
	} else {
		print "<tr><td colspan='11'><em>No License Checkouts Found</em></td></tr>";
		html_end_box(false);
	}

	api_plugin_hook('lic_page_bottom');

	bottom_footer();
}

function lic_filter() {
	global $config, $lic_refresh_interval, $lic_rows_selector;

	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_checkouts.php'>
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
									INNER JOIN lic_services_feature_details AS lsf
									ON ls.service_id=lsf.service_id
									INNER JOIN lic_pollers AS lp
									ON ls.poller_id=lp.id
									ORDER BY server_name');
							} else {
								$servers = db_fetch_assoc_prepared('SELECT DISTINCT
									ls.service_id AS id, server_name AS name
									FROM lic_services AS ls
									INNER JOIN lic_services_feature_details AS lsf
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
						<?php print __('Host', 'license');?>
					</td>
					<td>
						<select id='host' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('host') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (get_request_var('service_id') == -1) {
								if (isempty_request_var('poller_type')){
									$lhosts = db_fetch_assoc("SELECT DISTINCT hostname
										FROM lic_services_feature_details
										WHERE hostname!=''
										ORDER BY hostname");
								} else {
									$lhosts = db_fetch_assoc_prepared("SELECT DISTINCT hostname
										FROM lic_services_feature_details AS lsfd
										JOIN lic_services AS ls
										ON ls.service_id=lsfd.service_id
										JOIN lic_pollers AS lp
										ON ls.poller_id=lp.id
										WHERE hostname!=''
										AND lp.poller_type=?
										ORDER BY hostname", array(get_request_var('poller_type')));
								}
							} else {
								$lhosts = db_fetch_assoc_prepared("SELECT DISTINCT hostname
									FROM lic_services_feature_details
									WHERE service_id=(
										SELECT service_id
										FROM lic_services
										WHERE service_id=?
									)
									ORDER BY hostname", array(get_request_var('service_id')));
							}

							if (cacti_sizeof($lhosts) > 0) {
								foreach ($lhosts as $lhost) {
								print '<option value="' . $lhost['hostname'] .'"'; if (get_request_var('host') == $lhost['hostname']) { print ' selected'; } print '>' . lic_strip_domain($lhost['hostname']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'license');?>
					</td>
					<td>
						<select id='type' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('type') == '-2') {?> selected<?php }?>>Checkouts</option>
							<option value='-3'<?php if (get_request_var('type') == '-3') {?> selected<?php }?>>Reservations</option>
						</select>
					</td>
					<td>
						<input id='rollup' type='checkbox' <?php if ((get_request_var('rollup') == 'true') || (get_request_var('rollup') == 'on')) print ' checked="true"';?> onClick='applyFilter()'>
					</td>
					<td>
						<label for='rollup'>Roll-Up</label>
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
						<?php print __('Feature', 'license');?>
					</td>
					<td>
						<input type='text' id='myfilter' size='35' value='<?php print get_feature_name(get_request_var('filter'));?>'>
						<input type='hidden' id='myfilter_label' value='<?php print get_feature_name(get_request_var('filter'));?>'>
						<input type='hidden' id='myfilter_value' value='<?php print get_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('User', 'license');?>
					</td>
					<td>
						<select id='couser' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('couser') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (get_request_var('service_id') == -1) {
								if (isempty_request_var('manger')){
									$users = db_fetch_assoc('SELECT DISTINCT username
										FROM lic_services_feature_details
										WHERE username!=""
										ORDER BY username');
								} else {
									$users = db_fetch_assoc_prepared('SELECT DISTINCT username
										FROM lic_services_feature_details AS lsfd
										INNER JOIN lic_services AS ls
										ON ls.service_id=lsfd.service_id
										INNER JOIN lic_pollers AS lp
										ON lp.id=ls.poller_id
										WHERE username!=""
										AND lp.poller_type=?
										ORDER BY username', array(get_request_var('manger')));
								}
							} else {
								$users = db_fetch_assoc_prepared('SELECT DISTINCT username
									FROM lic_services_feature_details
									WHERE service_id=?
									ORDER BY username', array(get_request_var('service_id')));
							}

							if (cacti_sizeof($users)) {
								foreach ($users as $user) {
									print '<option value="' . urlencode($user['username']) .'"'; if (get_request_var('couser') == $user['username']) { print ' selected'; } print '>' . $user['username'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('LSFHost', 'license');?>
					</td>
					<td>
						<select id='lsf' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('lsf') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('lsf') == '-2') {?> selected<?php }?>>Yes</option>
							<option value='-3'<?php if (get_request_var('lsf') == '-3') {?> selected<?php }?>>No</option>
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

function format_timing($time) {
	if ($time > 86400) {
		$days  = floor($time/86400);
		$time %= 86400;
	} else {
		$days  = 0;
	}

	if ($time > 3600) {
		$hours = floor($time/3600);
		$time  %= 3600;
	} else {
		$hours = 0;
	}

	$minutes = floor($time/60);

	return $days . "d " . $hours . "h " . $minutes . "m";
}
