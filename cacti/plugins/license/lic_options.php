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

$title = 'IBM Spectrum LSF RTM - FlEXlm Usage Policies';

global $options_views_columns, $if_collect_option;

$options_views_columns = array(
	'limits' => array(
		'server_name' => 1,
		'feature'     => 1,
		'otype'       => 1,
		'name'        => 1,
		'num_lic'     => 1,
	),
	'acl' => array(
		'server_name' => 1,
		'feature'     => 1,
		'otype'       => 1,
		'name'        => 1,
		'variable'    => 1,
	),
	'lb' => array(
		'server_name'      => 1,
		'feature'          => 1,
		'borrow_lowwater'  => 1,
		'max_borrow_hours' => 1,
		'max_overdraft'    => 1,
		'linger'           => 1,
		'timeout'          => 1,
	)
);

$if_collect_option = read_config_option('enable_option_in_file_collection');

if (empty($if_collect_option)) {
	$if_collect_option = 'off';
}

if (!isset_request_var('action')) { set_request_var('action',''); }

switch(get_request_var('action')) {
case 'ajaxsave':
	ajax_save();
	break;
case 'ajaxsearch':
	ajax_search();
	break;
default:
	lic_optionsdb();
	break;
}

function lic_optionsdb() {
	global $title, $report, $lic_search_types, $lic_refresh_interval, $minimum_user_refresh_intervals;
	global $lic_license_server_actions, $config;

	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_setting('rows', '10')
			),
        'pagelb' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
            ),
        'pageacl' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
            ),
        'pageres' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
            ),
        'pagelim' => array(
            'filter' => FILTER_VALIDATE_INT,
            'default' => '1'
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
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('refresh', '300')
			),
		'user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_user_setting('user', ''),
			'options' => array('options' => 'sanitize_search_string')
			),
		'vendor' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_user_setting('vendor', '-1'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'feature' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'service_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'lim' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'res' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'acl' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'lb' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	validate_store_request_vars($filters, 'sess_lo');

	if (isset_request_var('changed') && get_request_var('changed') == 1){
		$_SESSION['sess_lo_pagelim'] = 1;
		$_SESSION['sess_lo_pageacl'] = 1;
		$_SESSION['sess_lo_pageres'] = 1;
		$_SESSION['sess_lo_pagelb']  = 1;
	}

	lic_set_minimum_page_refresh();

	if (get_request_var('rows') == -1) {
		$row_limit = '15';
	}elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	}else{
		$row_limit = get_request_var('rows');
	}

	general_header();

	/* setup javascript */
	initializeAjax();

	/* draw a bounding box */
	echo "<div style='position:relative;'>\n";

	html_start_box(__('License Policy Filter'), '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	echo "<div style='top:0px;position:relative;overflow:auto;float:left;width:" . (one_column_display_type() ? '100%':'57%') . ";'>\n";

	show_limits($row_limit);
	show_reservations($row_limit);

	if (!one_column_display_type()) {
		echo "</div>\n";
		echo "<div id='right' style='top:0px; position:relative;float:right;width:" . (one_column_display_type() ? '100%':'42%') . ";'>\n";
	}

	show_acls($row_limit);
	show_lendborrow($row_limit);

	echo "</div>\n";
	echo "</div>\n";

	bottom_footer();
}

function initializeAjax() {
	?>
	<script type='text/javascript'>

	$(function() {
		$('#louser').autocomplete({
			autoFocus: true,
			source: 'lic_options.php?header=false&action=ajaxsearch&type=user',
			minLength: 0,
			select: function( event, ui ) {
				$('#louser').val(ui.item.value);
				applyFilter();
			}
		});

		$('#feature').autocomplete({
			autoFocus: true,
			source: 'lic_options.php?header=false&action=ajaxsearch&type=feature',
			minLength: 0,
			select: function( event, ui ) {
				$('#feature').val(ui.item.value);
				applyFilter();
			}
		});

		$('#option_views_filter').multiselect({
			close: function(){
				applyFilter();
			}
		});

		var dimensions = [];
		if (<?php echo "'" .htmlspecialchars(get_request_var('lim')) ."'"; ?> == 'true') dimensions.push('lim');
		if (<?php echo "'" .htmlspecialchars(get_request_var('res')) ."'"; ?> == 'true') dimensions.push('res');
		if (<?php echo "'" .htmlspecialchars(get_request_var('acl')) ."'"; ?> == 'true') dimensions.push('acl');
		if (<?php echo "'" .htmlspecialchars(get_request_var('lb')) ."'"; ?> == 'true') dimensions.push('lb');

		i = 0, size = dimensions.length;
		for(i; i < size; i++){
			$('#option_views_filter').multiselect('widget').find(":checkbox[value='"+dimensions[i]+"']").prop('checked', true);
			$("#option_views_filter option[value='" + dimensions[i] + "']").prop('selected', 1);
			$('#option_views_filter').multiselect('refresh');
		}

		applySkin();
	});

	function applyFilter() {
		strURL  = 'lic_options.php?header=false';
		strURL += '&vendor='+$('#vendor').val();
		strURL += '&feature='+$('#feature').val();
		strURL += '&service_id='+$('#service_id').val();
		strURL += '&user='+$('#louser').val();
		strURL += '&rows='+$('#rows').val();
		strURL += '&refresh=' + $('#refresh').val();

		$('#option_views_filter option').each(function(){
			if($(this).prop('selected')){
				strURL = strURL + '&' + $(this).val() + '=true';
			} else {
				strURL = strURL + '&' + $(this).val() + '=false';
			}
		});
		loadPageNoHeader(strURL);
	}

	function filterSave(objForm) {
		strURL  = '?action=ajaxsave';
		strURL += '&vendor='+$('#vendor').val();
		strURL += '&feature='+$('#feature').val();
		strURL += '&service_id='+$('#service_id').val();
		strURL += '&user='+$('#louser').val();
		strURL += '&rows='+$('#rows').val();
		strURL += '&refresh='+$('#refresh').val();

		$('#option_views_filter option').each(function(){
			if($(this).prop('selected')){
				strURL = strURL + '&' + $(this).val() + '=true';
			} else {
				strURL = strURL + '&' + $(this).val() + '=false';
			}
		});

		$.get(strURL, function() {
			$('#message').text('').show().html('<strong>Filter Settings Saved</strong>').delay(2000).fadeOut(1000);
		});
	}

	function clearFilter(objForm) {
		strURL = '?header=false&clear=true';
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
}

function one_column_display_type() {
	$browsers = array(
		'OpenWeb',
		'Windows CE',
		'NetFront',
		'Palm OS',
		'Blazer',
		'Elaine',
		'WAP',
		'Plucker',
		'AvantGo',
		'iPhone',
		'iPad',
		'Mobile',
		'BlackBerry',
		'Opera Mobi',
		'Opera Mini',
	);

	foreach ($browsers as $b) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $b) !== false) {
			return true;
		}
	}

	return true;
	return false;
}

function ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case 'user':
			if (get_request_var('term') != '') {
				set_request_var('term',str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), get_request_var('term')));
				$values = db_fetch_assoc_prepared("SELECT DISTINCT name AS label, name AS value
					FROM lic_services_options_max
					WHERE name LIKE ?
					AND otype='USER' OR otype='IND_USER'
					ORDER BY label
					LIMIT 20", array("%" . get_request_var('term') . "%"));
			}else{
				$values = db_fetch_assoc("SELECT DISTINCT name AS label, name AS value
					FROM lic_services_options_max
					WHERE otype='USER' OR otype='IND_USER'
					ORDER BY label
					LIMIT 20");
			}
			print json_encode($values);

			break;
		case 'feature':
			if (get_request_var('term') != '') {
				set_request_var('term',str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), get_request_var('term')));
				$values = db_fetch_assoc_prepared("SELECT DISTINCT feature AS label, feature AS value
					FROM lic_services_options_max
					WHERE feature LIKE ?
					ORDER BY label
					LIMIT 20", array("%" . get_request_var('term') . "%"));
			}else{
				$values = db_fetch_assoc("SELECT DISTINCT feature AS label, feature AS value
					FROM lic_services_options_max
					ORDER BY label
					LIMIT 20");
			}
			print json_encode($values);

			break;
		}
	}
}

function ajax_save() {
	$settings =
		'vendor='     . get_request_var('vendor')     . '|' .
		'feature='    . get_request_var('feature')    . '|' .
		'service_id=' . get_request_var('service_id') . '|' .
		'user='       . get_request_var('user')       . '|' .
		'rows='       . get_request_var('rows')       . '|' .
		'res='        . get_request_var('res')        . '|' .
		'lim='        . get_request_var('lim')        . '|' .
		'acl='        . get_request_var('acl')        . '|' .
		'lb='         . get_request_var('lb')         . '|' .
		'refresh='    . get_request_var('refresh');

	set_lic_config_option('lic_options', $settings);
}

function get_user_setting($setting, $default) {
	if(!isset_request_var('reset')){
		return $default;
	}
	return get_lic_user_setting('lic_options', $setting, $default, true);
}

function show_acls($row_limit) {
	global $options_views_columns, $if_collect_option;
	$sql_params = array();
	$sql_params_inexcl = array();

	if (get_request_var('acl') == '' || get_request_var('acl') == 'false') {
		return;
	}

	$sql_where_inexcl = '';
	$sql_where_ftt    = '';
	$sql_where        = '';
	if (get_request_var('vendor') != '-1') {
		$sql_where = (strlen($sql_where) ? ' AND ':'WHERE ') . "service_id IN (SELECT service_id FROM lic_services WHERE server_vendor LIKE ?)";
		$sql_where_ftt    .= $sql_where;
		$sql_where_inexcl .= $sql_where;
		$sql_params[] = '%'. get_request_var('vendor') . '%';
		$sql_params_inexcl[] = '%'. get_request_var('vendor') . '%';
	}

	if (get_request_var('feature') != '') {
		$sql_where_ftt    .= (strlen($sql_where_ftt) ? ' AND ':'WHERE ') . "feature LIKE ?";
		$sql_where_inexcl .= (strlen($sql_where_inexcl) ? ' AND ':'WHERE ') . "service_id IN (SELECT service_id FROM lic_services_feature_details WHERE feature_name LIKE ?)";
		$sql_params[] = '%'. get_request_var('feature') . '%';
		$sql_params_inexcl[] = '%'. get_request_var('feature') . '%';
	}

	if (get_request_var('service_id') != '-1') {
		$sql_where_ftt    .= (strlen($sql_where_ftt) ? ' AND ':'WHERE ') . "service_id=?";
		$sql_where_inexcl .= (strlen($sql_where_inexcl) ? ' AND ':'WHERE ') . "service_id=?";
		$sql_params[] = get_request_var('service_id');
		$sql_params_inexcl[] = get_request_var('service_id');
	}

	if (get_request_var('user') != '') {
		$sql_where_ftt    .= (strlen($sql_where_ftt) ? ' AND ':'WHERE ') . "(otype='USER' AND name LIKE ?)";
		$sql_where_inexcl .= (strlen($sql_where_inexcl) ? ' AND ':'WHERE ') . "(otype='USER' AND name LIKE ?)";
		$sql_params[] = '%'. get_request_var('user') . '%';
		$sql_params_inexcl[] = '%'. get_request_var('user') . '%';
	}

	$sort_order = '';
	if ( isset($options_views_columns['acl'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	$sql_query  = "SELECT recordset.*, ls.server_name
		FROM (
			SELECT *
			FROM lic_services_options_feature_type
			$sql_where_ftt
			UNION
			SELECT service_id, 'All' AS feature, 'N/A' AS keyword, incexcl AS keyword, otype, name, notes, present
			FROM lic_services_options_incexcl_all
			$sql_where_inexcl
		) AS recordset
		INNER JOIN lic_services AS ls
		ON recordset.service_id=ls.service_id
		$sort_order";

	$sql_query .= " LIMIT " . ($row_limit*($_SESSION['sess_lo_pageacl']-1)) . "," . $row_limit;

	//echo $sql_query;

	$acls = db_fetch_assoc_prepared($sql_query, array_merge($sql_params, $sql_params_inexcl));

	$rows_query_string = "SELECT count(*) FROM (SELECT * FROM lic_services_options_feature_type $sql_where_ftt
		UNION
		SELECT service_id, 'All' AS feature, 'N/A' AS keyword, incexcl AS keyword, otype, name, notes, present
		FROM lic_services_options_incexcl_all $sql_where_inexcl) AS recordset
		INNER JOIN lic_services AS ls
		ON recordset.service_id=ls.service_id";

	$total_rows   = db_fetch_cell_prepared($rows_query_string, array_merge($sql_params, $sql_params_inexcl));
	$display_text = build_acl_display_array();

	/* generate page list */
	$nav = html_nav_bar('lic_options.php?type=acl&action=', MAX_DISPLAY_PAGES, $_SESSION['sess_lo_pageacl'], $row_limit, $total_rows, '', '', 'pageacl');

	html_start_box('Access Controls', '100%', '', '3', 'center', '');
	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($acls)) {
		foreach($acls as $a) {
			form_alternate_row();
			echo '<td style="white-space:nowrap;">' . $a['server_name'] . '</td>';
			echo '<td>' . $a['variable'] . '</td>';
			echo '<td>' . get_feature_name($a['feature'], $a['service_id']) . ((empty($a['keyword']) || $a['keyword']=='N/A')? '':' <strong>:</strong> ' . $a['keyword']) . '</td>';
			echo '<td title="' . get_otype_title($a['otype']) . '">' . $a['otype'] . '</td>';
			echo '<td>' . $a['name'] . '</td>';
			if ($if_collect_option == "on") {
				echo '<td>' . convert_notes_uri($a['notes']) . '</td>';
			}
			echo '</tr>';
		}
	}else{
		echo "<tr><td colspan=6>No Access Controls Found</td></tr>";
	}

	html_end_box(false);
}

function show_reservations($row_limit) {
	global $options_views_columns, $if_collect_option;
	$sql_params = array();

	if (get_request_var('res') == '' || get_request_var('res') == 'false') {
		return;
	}

	$sql_where = '';
	if (get_request_var('vendor') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.service_id IN (SELECT service_id FROM lic_services WHERE server_vendor LIKE ?)";
		$sql_params[] = '%'. get_request_var('vendor') . '%';
	}

	if (get_request_var('service_id') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.service_id=?";
		$sql_params[] = get_request_var('service_id');
	}

	if (get_request_var('feature') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "feature LIKE ?";
		$sql_params[] = '%'. get_request_var('feature') . '%';
	}

	if (get_request_var('user') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "(otype='USER' AND name LIKE ?)";
		$sql_params[] = '%'. get_request_var('user') . '%';
	}

	$sort_order = "";
	if ( isset($options_views_columns['limits'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	$sql_query  = "SELECT fo.*, ls.server_name
		FROM lic_services_options_reserve AS fo
		INNER JOIN lic_services AS ls
		ON fo.service_id=ls.service_id
		$sql_where $sort_order";

	$sql_query .= ' LIMIT ' . ($row_limit*($_SESSION['sess_lo_pageres']-1)) . ',' . $row_limit;

	//echo $sql_query;

	$reservations = db_fetch_assoc_prepared($sql_query, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM lic_services_options_reserve AS fo
		INNER JOIN lic_services AS ls
		ON fo.service_id=ls.service_id
		$sql_where";

	$total_rows   = db_fetch_cell_prepared($rows_query_string, $sql_params);
	$display_text = build_limits_display_array();

	/* generate page list */
	$nav = html_nav_bar('lic_options.php?type=res&action=', MAX_DISPLAY_PAGES, $_SESSION['sess_lo_pageres'], $row_limit, $total_rows, '', '', 'pageres');

	html_start_box('Token Reservations', '100%', '', '3', 'center', '');
	print $nav;

	$disabled = true;
	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($reservations)) {
		foreach($reservations as $r) {
			$name   = $r['name'];
			$in_use = get_inuse_values($r['service_id'], $r['feature'], $r['otype'], $r['name'], $name);
			$graphs = get_graph_href($r['feature'], $r['service_id']);

			if ($r['otype'] == 'USER') {
				$checkouts = get_checkout_href($r['feature'], $r['service_id'], $r['name']);
			}else{
				$checkouts = get_checkout_href($r['feature'], $r['service_id']);
			}

			$service = get_licdb_href($r['feature'], $r['server_name']);

			form_alternate_row();

			echo '<td width=100 style="white-space:nowrap;">' . $service . $graphs . $checkouts . '</td>';
			echo '<td style="white-space:nowrap;">' . $r['server_name'] . '</td>';
			echo '<td>' . get_feature_name($r['feature'], $r['service_id']) . ((empty($r['keyword']) || $r['keyword'] == 'N/A') ? '':' <strong>:</strong> ' . $r['keyword']) . '</td>';
			echo '<td title="' . get_otype_title($r['otype']) . '">' . $r['otype'] . '</td>';
			echo '<td>' . $name . '</td>';
			echo '<td style="text-align:right;">' . $r['num_lic'] . '</td>';
			echo '<td style="text-align:right;">' . $in_use . '</td>';

			if ($if_collect_option == "on") {
				echo '<td>' . convert_notes_uri($r['notes']) . '</td>';
			}

			echo '</tr>';
		}
	}else{
		echo "<tr><td colspan=6>No Token Reservations Found</td></tr>";
	}

	html_end_box(false);
}

function get_inuse_values($service_id, $feature, $otype, $oname, &$name = '') {
	$in_use = '-';

	$feature = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $feature);
	$otype   = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $otype);
	$oname   = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $oname);

	if ($otype == 'PER_USER') {
		$url = trim(read_config_option('lic_ldap_search_uri'));

		if (!empty($url)) {
			$name = "<a class='hyper' href='" . htmlspecialchars(str_replace('|group_name|',$oname,$url)) . "' title='View Group Information' target='_blank'>" . $oname . "</a>";
		}

		$in_use = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
			FROM lic_services_feature_details
			WHERE service_id=?
			AND feature_name=?
			AND status!='reserved'
			AND username IN (SELECT user FROM lic_ldap_groups WHERE `group`=?)",
			array($service_id, $feature, $oname));

		if (empty($in_use)) {
			$in_use = 0;
		}
	}elseif ($otype == 'USER' || $otype == 'IND_USER') {
		if ($oname != 'ALL_USERS') {
			$in_use = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
				FROM lic_services_feature_details
				WHERE service_id=?
				AND feature_name=?
				AND status!='reserved'
				AND username=?",
				array($service_id, $feature, $oname));
		}else{
			$in_use = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
				FROM lic_services_feature_details
				WHERE service_id=?
				AND feature_name=?
				AND status!='reserved'",
				array($service_id, $feature));
		}

		if (empty($in_use)) {
			$in_use = 0;
		}
	}elseif ($otype == 'GROUP') {
		$url = read_config_option('lic_ldap_search_uri');

		if (!empty($url)) {
			$ldap_group = db_fetch_cell_prepared("SELECT ldap_group
				FROM lic_ldap_to_flex_groups
				WHERE flex_group=?", array($oname));

			if (!empty($ldap_group)) {
				$name = "<a class='hyper' href='" . htmlspecialchars(str_replace('|group_name|',$ldap_group,$url)) . "' title='View Group Information' target='_blank'>" . $oname . "</a>";
			}
		}

		$in_use = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
			FROM lic_services_feature_details
			WHERE service_id=?
			AND feature_name=?
			AND status!='reserved'
			AND username IN (SELECT user FROM lic_services_options_user_groups WHERE `group`=?)",
			array($service_id, $feature, $oname));

		if (empty($in_use)) {
			$in_use = 0;
		}
	}elseif ($otype == 'HOST_GROUP') {
		$in_use1 = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
			FROM lic_services_feature_details
			WHERE service_id=?
			AND feature_name=?
			AND status!='reserved'
			AND hostname IN (
				SELECT SUBSTRING_INDEX(host,'.',1)
				FROM lic_services_options_host_groups
				WHERE `group`=?)",
			array($service_id, $feature, $oname));

		$in_use2 = db_fetch_cell_prepared("SELECT SUM(tokens_acquired)
			FROM lic_services_feature_details AS fd
			INNER JOIN (
				SELECT substring_index(ir.hostname,'.',1) AS hostname
				FROM lic_services_options_host_groups AS hg
				INNER JOIN lic_ip_ranges AS ir
				ON hg.host=ir.ip_range
				WHERE hg.`group`=?
			) AS gh
			ON gh.hostname=fd.hostname
			WHERE fd.service_id=?
			AND status!='reserved'
			AND feature_name=?",
			array($oname, $service_id, $feature));

		$in_use = $in_use1 + $in_use2;
	}

	if (empty($in_use)) {
		return '-';
	}else{
		return $in_use;
	}
}

function show_limits($row_limit) {
	global $config, $options_views_columns, $if_collect_option;
	$sql_params = array();

	if (get_request_var('lim') == '' || get_request_var('lim') == 'false') {
		return;
	}

	$sql_where = '';
	if (get_request_var('vendor') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.service_id IN (SELECT service_id FROM lic_services WHERE server_vendor LIKE ?)";
		$sql_params[] = '%'. get_request_var('vendor') . '%';
	}

	if (get_request_var('service_id') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.service_id=?";
		$sql_params[] = get_request_var('service_id');
	}

	if (get_request_var('feature') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "feature LIKE ?";
		$sql_params[] = '%'. get_request_var('feature') . '%';
	}

	$sort_order = "";

	if ( isset($options_views_columns['limits'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	if (get_request_var('user') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "(otype='USER' AND name LIKE ?)";
		$sql_params[] = '%'. get_request_var('user') . '%';
		$sql_query  = "SELECT fo.*, ls.server_name
			FROM lic_services_options_max AS fo
			INNER JOIN lic_services AS ls
			ON fo.service_id=ls.service_id
			$sql_where
			$sort_order";
	}else{
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "(otype!='USER' OR (otype='user' AND name='ALL_USERS'))";
		$sql_query  = "SELECT fo.*, ls.server_name
			FROM lic_services_options_max AS fo
			INNER JOIN lic_services AS ls
			ON fo.service_id=ls.service_id
			$sql_where
			$sort_order";
	}

	$sql_query .= ' LIMIT ' . ($row_limit*($_SESSION['sess_lo_pagelim']-1)) . ',' . $row_limit;

	//echo $sql_query;

	$limits = db_fetch_assoc_prepared($sql_query, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM lic_services_options_max AS fo
		INNER JOIN lic_services AS ls
		ON fo.service_id=ls.service_id
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);
	$display_text = build_limits_display_array();

	/* generate page list */
	$nav = html_nav_bar('lic_options.php?type=lim&action=', MAX_DISPLAY_PAGES, $_SESSION['sess_lo_pagelim'], $row_limit, $total_rows, '', '', 'pagelim');

	html_start_box('Usage Limits', '100%', '', '3', 'center', '');
	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($limits)) {
		foreach($limits as $l) {
			$name = $l['name'];
			$in_use = get_inuse_values($l['service_id'], $l['feature'], $l['otype'], $l['name'], $name);

			$graphs    = get_graph_href($l['feature'], $l['service_id']);
			if ($l['otype'] == 'USER' || $l['otype'] == 'IND_USER') {
				$checkouts = get_checkout_href($l['feature'], $l['service_id'], $l['name']);
			}else{
				$checkouts = get_checkout_href($l['feature'], $l['service_id']);
			}
			$service   = get_licdb_href($l['feature'], $l['server_name']);

			form_alternate_row();
			echo '<td width=100 style="white-space:nowrap;">' . $service . $graphs . $checkouts . '</td>';
			echo '<td style="white-space:nowrap;">' . $l['server_name'] . '</td>';
			echo '<td>' . $l['feature'] . ((empty($l['keyword']) || $l['keyword']=='N/A')? '':' <strong>:</strong> ' . $l['keyword']) . '</td>';
			echo '<td title="' . get_otype_title($l['otype']) . '">' . ($name == 'ALL_USERS' ? 'PER_USER':$l['otype']) . '</td>';
			echo '<td>' . $name . '</td>';
			echo '<td style="text-align:right;">' . $l['num_lic'] . '</td>';
			echo '<td style="text-align:right;">' . $in_use . '</td>';
			if ($if_collect_option == 'on') {
				echo '<td>' . convert_notes_uri($l['notes']) . '</td>';
			}
			echo '</tr>';
		}
	}else{
		echo "<tr><td colspan=6>No Limits Found</td></tr>";
	}

	html_end_box(false);
}

function convert_notes_uri($note) {
	if ($note == '') {
		return $note;
	}else{
		$uri = trim(read_config_option('lic_options_note_uri'));

		if ($uri != '') {
			$parts = explode(' ', trim($note));
			$uri = htmlspecialchars(str_replace('|ticket_number|', $parts[0], $uri));
			return "<a class='hyper' target='_blank' href='$uri' title='Information Releated to Rule'>$note</a>";
		}
	}
}

function get_otype_title($type) {
	switch($type) {
	case 'GROUP':
		return 'Usage Limit for this Group';
	case 'USER':
		return 'Usage Limit for this User';
	case 'PER_USER':
		return 'Per User Usage Limits for Users in the LDAP Group';
	case 'HOST_GROUP':
		return 'Usage Limit for this Host Group';
	case 'INTERNET':
		return 'Usage Limit for this IP Range';
	default:
		return 'Unknown Object Type';
	}
}

function build_lb_display_array() {
	$display_text = array();
	$display_text += array('server_name'      => array('display' => __('Service', 'license'), 'sort' => 'ASC'));
	$display_text += array('feature'          => array('display' => __('Feature', 'license'), 'sort' => 'ASC'));
	$display_text += array('borrow_lowwater'  => array('display' => __('Low Water', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('max_borrow_hours' => array('display' => __('Max Hours', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('max_overdraft'    => array('display' => __('Max Overdraft', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('linger'           => array('display' => __('Linger', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('timeout'          => array('display' => __('Timeout', 'license'), 'align' => 'right', 'sort' => 'DESC'));

	return $display_text;
}

function build_acl_display_array() {
	global $if_collect_option;

	$display_text = array();
	$display_text += array('server_name' => array('display' => __('Service', 'license'), 'sort' => 'ASC'));
	$display_text += array('variable'    => array('display' => __('ACL Type', 'license'), 'sort' => 'ASC'));
	$display_text += array('feature'     => array('display' => __('Feature', 'license'), 'sort' => 'ASC'));
	$display_text += array('otype'       => array('display' => __('Object Type', 'license'), 'sort' => 'ASC'));
	$display_text += array('name'        => array('display' => __('Object Name', 'license'), 'sort' => 'ASC'));
	if ($if_collect_option == 'on') {
		$display_text += array('notes'   => array('display' => __('Notes', 'license'), 'sort' => 'DESC'));
	}

	return $display_text;
}

function build_limits_display_array() {
	global $if_collect_option;

	$display_text = array();
	$display_text += array('nosort1'     => array('display' => __('Actions')));
	$display_text += array('server_name' => array('display' => __('Service', 'license'), 'sort' => 'ASC'));
	$display_text += array('feature'     => array('display' => __('Feature', 'license'), 'sort' => 'ASC'));
	$display_text += array('otype'       => array('display' => __('Object Type', 'license'), 'sort' => 'ASC'));
	$display_text += array('name'        => array('display' => __('Object Name', 'license'), 'sort' => 'ASC'));
	$display_text += array('num_lic'     => array('display' => __('Limit', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('nosort'      => array('display' => __('In Use', 'license'), 'align' => 'right', 'sort' => 'DESC'));
	if ($if_collect_option == 'on') {
		$display_text += array('notes'   => array('display' => __('Notes', 'license'), 'sort' => 'DESC'));
	}

	return $display_text;
}

function show_lendborrow($row_limit) {
	global $options_views_columns, $if_collect_option;
	$sql_params = array();

	if (get_request_var('lb') == '' || get_request_var('lb') == 'false') {
		return;
	}

	$sql_where = '';
	if (get_request_var('vendor') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.service_id IN (SELECT service_id FROM lic_services WHERE server_vendor LIKE ?)";
		$sql_params[] = '%'. get_request_var('vendor') . '%';
	}

	if (get_request_var('service_id') != '-1') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'fo.service_id=?';
		$sql_params[] = get_request_var('service_id');
	}

	if (get_request_var('feature') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "fo.feature LIKE ?";
		$sql_params[] = '%'. get_request_var('feature') . '%';
	}

	$sort_order = '';

	if ( isset($options_views_columns['lb'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	$sql_query  = "SELECT fo.*, ls.server_name
		FROM lic_services_options_feature AS fo
		INNER JOIN lic_services AS ls
		ON fo.service_id=ls.service_id
		$sql_where
		$sort_order";

	$sql_query .= ' LIMIT ' . ($row_limit*($_SESSION['sess_lo_pagelb']-1)) . ',' . $row_limit;

	//echo $sql_query;

	$lb = db_fetch_assoc_prepared($sql_query, $sql_params);

	$rows_query_string = "SELECT count(*)
		FROM lic_services_options_feature AS fo
		INNER JOIN lic_services AS ls
		ON fo.service_id=ls.service_id
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);
	$display_text = build_lb_display_array();

	/* generate page list */
	$nav = html_nav_bar('lic_options.php?type=lb&action=', MAX_DISPLAY_PAGES, $_SESSION['sess_lo_pagelb'], $row_limit, $total_rows, '', '', 'pagelb');

	html_start_box('Lending/Borrowing', '100%', '', '3', 'center', '');
	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($lb)) {
		foreach($lb as $r) {
			form_alternate_row();
			echo '<td style="white-space:nowrap;">' . $r['server_name'] . '</td>';
			echo '<td>' . get_feature_name($r['feature'], $r['service_id']) . ((empty($r['keyword']) || $r['keyword']=='N/A')? '':' <strong>:</strong> ' . $r['keyword']) . '</td>';
			echo '<td style="text-align:right;">' . (empty($r['borrow_lowwater']) ? '-':$r['borrow_lowwater']) . '</td>';
			echo '<td style="text-align:right;">' . (empty($r['max_borrow_hours']) ? '-':$r['max_borrow_hours']) . '</td>';
			echo '<td style="text-align:right;">' . (empty($r['max_overdraft']) ? '-':$r['max_overdraft']) . '</td>';
			echo '<td style="text-align:right;">' . (empty($r['linger']) ? '-':$r['linger']) . '</td>';
			echo '<td style="text-align:right;">' . (empty($r['timeout']) ? '-':$r['timeout']) . '</td>';
			echo '</tr>';
		}
	}else{
		echo "<tr><td colspan=6>No Lending and Borrowing Rules Found</td></tr>";
	}

	html_end_box(false);
}

function lic_filter() {
	global $lic_search_types, $lic_rows_selector, $lic_refresh_interval, $minimum_user_refresh_intervals, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_view' action='lic_options.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Feature', 'license');?>
					</td>
					<td>
						<input type='text' size='20' id='feature' value='<?php print get_request_var('feature');?>' onChange='applyFilter()'>
					</td>
					<td>
						<?php print __('User', 'license');?>
					</td>
					<td>
						<input type='text' size='20' id='louser' value='<?php print get_request_var('user');?>' onChange='applyFilter()'>
					</td>
					<td>
						<?php print __('Views', 'license');?>
					</td>
					<td>
						<select id='option_views_filter' multiple='multiple' >
							<option value='lim' <?php if (isset_request_var('lim') && get_request_var('lim')=='true') print ' selected';?>>Usage Limits</option>
							<option value='res' <?php if (isset_request_var('res') && get_request_var('res')=='true') print ' selected';?>>Token Reservations</option>
							<option value='acl' <?php if (isset_request_var('acl') && get_request_var('acl')=='true') print ' selected';?>>Access Controls</option>
							<option value='lb' <?php if (isset_request_var('lb') && get_request_var('lb')=='true') print ' selected';?>>Lending/Borrowing</option>
						</select>
					</td>

					<td>
						<input type='button' id='go' value='Go' title='Search' onClick='applyFilter()'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
					<td>
						<input type='button' id='save' value='Save' title='Save Filters' onClick='filterSave()'>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Service', 'license');?>
					</td>
					<td>
						<select id='service_id' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('service_id') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (get_request_var('vendor') != '-1') {
								$names = db_fetch_assoc_prepared("SELECT DISTINCT ls.service_id, server_name
									FROM lic_services AS ls
									INNER JOIN lic_services_options_global AS log
									ON ls.service_id=log.service_id
									WHERE (server_name != '' AND server_name IS NOT NULL)
									AND ls.server_vendor LIKE ?
									ORDER BY server_name", array('%'. get_request_var('vendor') . '%'));
							}else{
								$names = db_fetch_assoc("SELECT DISTINCT ls.service_id, server_name
									FROM lic_services AS ls
									INNER JOIN lic_services_options_global AS log
									ON ls.service_id=log.service_id
									WHERE (server_name != '' AND server_name IS NOT NULL)
									ORDER BY server_name");
							}


							if (cacti_sizeof($names)) {
								foreach ($names as $n) {
									print '<option value="' . $n['service_id'] .'"'; if (get_request_var('service_id') == $n['service_id']) { print ' selected'; } print '>' . $n['server_name'] . '</option>';
								}
							}
							?>
						</select>
					<td>
						<?php print __('Vendor', 'license');?>
					</td>
					<td>
						<select id='vendor' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('vendor') == '-1') {?> selected<?php }?>>All</option>
							<?php
							if (get_request_var('service_id') != '-1') {
								$vendors = db_fetch_assoc_prepared("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_services_options_global AS log
									ON ls.service_id=log.service_id
									WHERE (server_vendor != '' AND server_vendor IS NOT NULL)
									AND ls.service_id=?
									ORDER BY server_vendor", array(get_request_var('service_id')));
							}else{
								$vendors = db_fetch_assoc("SELECT DISTINCT server_vendor
									FROM lic_services AS ls
									INNER JOIN lic_services_options_global AS log
									ON ls.service_id=log.service_id
									WHERE (server_vendor != '' AND server_vendor IS NOT NULL)
									ORDER BY server_vendor");
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
						<?php print __('Rows', 'license');?>
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

function get_graph_href($feature, $service = '', $daemon = '') {
	global $config;
	$sql_params = array();

	$feature = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $feature);

	$anchor = "<img src='" . $config['url_path'] . "plugins/license/images/view_none.gif' style='padding:1px;' align='absmiddle' border='0'>";

	if (empty($service) && empty($daemon)) {
		$sql_where = "WHERE (host_snmp_cache.snmp_index LIKE ?)";
		$sql_params[] = "%-" . $feature . "-%";
	}elseif (!empty($service) && !empty($daemon)) {
		$sql_where = "WHERE (host_snmp_cache.snmp_index=?)";
		$sql_params[] = $service . "-" . $feature . "-" . str_replace(' ', '_', $daemon);
	}elseif (!empty($service)) {
		$sql_where = "WHERE (host_snmp_cache.snmp_index LIKE ?)";
		$sql_params[] = $service . "-" . $feature . "-%";
	}elseif (!empty($daemon)) {
		$sql_where = "WHERE (host_snmp_cache.snmp_index LIKE ?)";
		$sql_params[] = "%-" . $feature . "-" . str_replace(' ', '_', $daemon);
	}else{
		return $anchor;
	}

	$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT
		graph_local.id FROM host_snmp_cache
		INNER JOIN graph_local
		ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
		AND (host_snmp_cache.host_id=graph_local.host_id)
		AND (host_snmp_cache.snmp_index=graph_local.snmp_index)
		$sql_where
		AND (host_snmp_cache.field_name='gridFeatureName')", $sql_params);

	if (cacti_sizeof($local_graph_ids)) {
		$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

		foreach($local_graph_ids as $graph) {
			$graph_select .= $graph['id'] . '%2C';
		}

		$anchor = "<a href='" . htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/license/images/view_graphs.gif' alt='' title='View Feature Graphs' align='absmiddle' border='0'></a>\n";
	}

	return $anchor;
}

function get_checkout_href($feature, $service_id, $user = '') {
	global $config;

	$feature = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $feature);

	if ($user != 'ALL_USERS') {
		if ($user != '') {
			$count = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_details WHERE service_id=? AND feature_name=? AND username=?",
						array($service_id, $feature, $user));
		}else{
			$count = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_details WHERE service_id=? AND feature_name=?",
						array($service_id, $feature));
		}
	}else{
		$count = db_fetch_cell_prepared("SELECT count(*) FROM lic_services_feature_details WHERE service_id=? AND feature_name=?",
					array($service_id, $feature));
	}

	if (api_user_realm_auth('lic_checkouts.php')) {
		if ($count > 0) {
			return "<a href='" . htmlspecialchars($config['url_path'] . "plugins/license/lic_checkouts.php?service_id=$service_id&reset=true&filter=$feature") . ($user!='' && $user!='ALL_USERS' ? "&user=$user":'&user=-1') . "' title='View Checkouts'><img src='" .  $config['url_path'] . "plugins/license/images/view_checkouts.gif' align='absmiddle' border='0'></a>";
		}else{
			return "<img src='" .  $config['url_path'] . "plugins/license/images/view_none.gif' align='absmiddle' border='0'>";
		}
	}
}

function get_licdb_href($feature, $service_name = '') {
	global $config;

	$feature      = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $feature);
	$service_name = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $service_name);

	$vendor = db_fetch_cell_prepared("SELECT server_vendor
		FROM lic_services
		WHERE service_id IN (
			SELECT service_id
			FROM lic_services_feature_use
			WHERE feature_name=?
		)
		AND server_name =?
		AND server_vendor!=''
		LIMIT 1",
		array($feature, $service_name));

	$vendor = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $vendor);

	if (api_user_realm_auth('lic_servicedb.php')) {
		if (!empty($vendor)) {
			return "<a href='" . htmlspecialchars($config['url_path'] . "plugins/license/lic_servicedb.php?status=-1&vendor=$vendor" . (strlen($service_name) ? "&filter=$service_name":'') . "&reset=true") . "' title='View Service Status'><img src='" .  $config['url_path'] . "plugins/license/images/view_servicedb.gif' align='absmiddle' border='0' alt=''></a>";
		}elseif (!empty($service_name)) {
			return "<a href='" . htmlspecialchars($config['url_path'] . "plugins/license/lic_servicedb.php?&status=-1&vendor=-1&filter=$service_name&reset=true") . "' title='View Service Status'><img src='" .  $config['url_path'] . "plugins/license/images/view_servicedb.gif' align='absmiddle' border='0' alt=''></a>";
		}else{
			return "<img src='" .  $config['url_path'] . "plugins/license/images/view_none.gif' align='absmiddle' border='0'>";
		}
	}
}

