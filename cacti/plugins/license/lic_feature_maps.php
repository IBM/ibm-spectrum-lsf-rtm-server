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

chdir('../../');
include('./include/auth.php');
include_once('./lib/api_device.php');
include_once('./lib/api_graph.php');
include_once('./lib/api_data_source.php');
include_once('./plugins/license/include/lic_functions.php');

$fm_actions = array(
	1 => 'Mass Mapping'
);

/* set default action */
if (!isset_request_var('action')) { set_request_var('action',''); }

lic_feature_request_validation();
switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'rebuild':
		rebuild_feature_maps();
		raise_message(216);
		?>
		<script type='text/javascript'>
			$(function() {
				loadPageNoHeader('lic_feature_maps.php?header=false');
			});
		</script>
		<?php

		break;
	case 'edit':
		top_header();

		lic_server_edit();

		bottom_footer();
		break;
	default:
		top_header();

		lic_feature_maps();

		bottom_footer();
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset_request_var('save_component_fm')) {
		input_validate_input_number(get_request_var('service_id'));
		$id = explode('|', get_request_var('id'));

		if ((is_error_message())) {
			header('Location: lic_feature_maps.php?error=' . get_request_var('poller_type'). '&action=edit&id=' .get_request_var('id'));
			exit(0);
		}

		$save = array();
		$save['service_id']        = $id[0];
		$save['feature_name']      = $id[1];
		$save['user_feature_name'] = get_request_var('user_feature_name');
		$save['application']       = get_request_var('application');
		$save['critical']          = get_request_var('critical');
		$save['user_id']           = $_SESSION['sess_user_id'];
		$save['last_updated']      = date('Y-m-d H:i:s');

		$id = sql_save($save, 'lic_application_feature_map', array('service_id', 'feature_name'));

		header('Location: lic_feature_maps.php');
	}
}

/* ------------------------
    The 'actions' function
   ------------------------ */

function form_actions() {
	global $config, $fm_actions, $fields_fm_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		include_once($config['base_path'] . '/lib/rtm_functions.php');
		$selected_items = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* mapp */
				for ($i=0; $i<count($selected_items); $i++) {
					api_update_feature($selected_items[$i]);
				}
			}
		}

		header('Location: lic_feature_maps.php');
		exit;
	}

	/* setup some variables */
	$feature_list = '';
	$feature_array = array();
	$feature_map_array = array();

	/* loop through each of the license servers selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([^.]+)$/', $var, $matches)) {
			$id = explode('|', $matches[1]);

			if (sizeof($id) == 2) {
				$service_id = $id[0];
				$feature    = $id[1];

				$info = db_fetch_row_prepared("SELECT feature_name, user_feature_name, application
					FROM lic_application_feature_map AS lafm
					WHERE service_id=?
					AND feature_name=?", array($service_id, $feature));

				$feature_array[]     = $matches[1];
				$feature_map_array[] = $info;
			} else {
				$service_id      = -1;
				$feature         = $id[0];
				$feature_array[] = $matches[1];

				$info = db_fetch_row_prepared("SELECT DISTINCT feature_name, user_feature_name, application
					FROM lic_application_feature_map AS lafm
					WHERE feature_name = ?
					LIMIT 1",
					array($feature));

				$feature_map_array[] = $info;
			}
		}
	}

	$app_name = "true";
	$feat_assigned_name = "";
	if(cacti_sizeof($feature_map_array) > 1){
		foreach($feature_map_array as $feature_map){
			if(!empty($feature_map) && isset($feature_map['user_feature_name']) && !empty($feature_map['user_feature_name']))
				$fline = $feature_map['user_feature_name'] . "(" . $feature_map['feature_name'] . ")";
			else
				$fline = $feature_map['feature_name'];
			$feature_list .= '<li>' . html_escape($fline) . '</li>';
			if($app_name == "true")
				$app_name = $feature_map['application'];
			else if($app_name != $feature_map['application'])
				$app_name = "";
		}
	}else if(cacti_sizeof($feature_map_array) > 0){
		$feature_list = '<li>' . html_escape($feature_map_array[0]['feature_name']) . '</li>';
		$feat_assigned_name = $feature_map_array[0]['user_feature_name'];
		$app_name = $feature_map_array[0]['application'];
	}

	top_header();

	form_start('lic_feature_maps.php');

	html_start_box($fm_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($feature_array)) {
		if (get_request_var('drp_action') == '1') { /* Mass Mapping  */
			$suffix = (cacti_sizeof($feature_array) > 1 ? "s": "" );
			print "<tr>
				<td class='textArea' colspan='2'>
				<p>Click 'Continue' to Map " . (cacti_sizeof($feature_array)>1 ? " all " : "") . "the selected Feature$suffix using the value$suffix below.</p>
				<ul>$feature_list</ul>
				</td>
			</tr>";

			if(cacti_sizeof($feature_array) == 1)
				print "<tr><td class='textArea' colspan='2'><p><div style='float:left;margin-left:10px;margin-right:10px'>Assigned Name per Feature:</div><div style='float:left'><input type='textbox' size='40' name='user_feature_name' id='user_feature_name' value='$feat_assigned_name'></div></p></td></tr>\n";
			print "<tr><td class='textArea' colspan='2'><p><div style='float:left;margin-left:10px;margin-right:10px'>Application:</div><div style='float:left'><input type='textbox' size='40' name='application' id='application' value='$app_name'></div></p></td></tr>\n";
			print "<tr><td class='textArea' colspan='2'><p><div style='float:left;margin-left:10px;margin-right:10px'>Key Feature:</div><div style='float:left'><select name='critical' id='critical'><option value='1'>Yes</option><option value='0'>No</option></div></p></td></tr>\n";

			$title = "Map Feature$suffix";
		}

		$save_html = "<input type='button' class='ui-button ui-corner-all ui-widget' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' class='ui-button ui-corner-all ui-widget' value='" . __esc('Continue') . "' title='" . __esc($title) . "'>";
	} else {
		raise_message('licfm40', __('You must select at least one License Feature.'), MESSAGE_LEVEL_ERROR);
		header('Location: lic_feature_maps.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($feature_array) ? serialize($feature_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function api_update_feature($id) {

	$id = explode('|', $id);

	if (sizeof($id == 2)) {
		$service_id = $id[0];
		$feature    = $id[1];
	} else {
		$service_id = -1;
		$feature    = $id[0];
	}

	$up_params = array();

	$up_params[] = get_request_var('application');
	$set_clause = 'SET application = ? ';

	if(isset_request_var('user_feature_name')){
		$up_params[] = get_request_var('user_feature_name');
		$set_clause .= ', user_feature_name = ? ';
	}

	$up_params[] = get_request_var('critical');
	$set_clause .= ', critical = ? ';

	$up_params[] = $_SESSION['sess_user_id'];
	$set_clause .= ', user_id = ? ';

	$set_clause .= ', last_updated=NOW() ';

	if ($service_id > 0) {
		$up_params[] = $service_id;
	}
	$up_params[] = $feature;

	if ($service_id > 0) {
		db_execute_prepared("UPDATE lic_application_feature_map
			$set_clause
			WHERE service_id = ?
			AND feature_name = ?",
			$up_params
		);
	} else {
		db_execute_prepared("UPDATE lic_application_feature_map
			$set_clause
			WHERE feature_name = ?",
			$up_params
		);
	}
}

/* ---------------------
    Site Functions
   --------------------- */

function lic_server_edit() {
	global $lic_minor_refresh_interval, $lic_max_nonjob_runtimes;

	/* file: lic_feature_maps.php, action: edit */
	$fields_fm_edit = array(
		'spacer1' => array(
			'method' => 'spacer',
			'friendly_name' => 'General Information'
			),
		'feature_name' => array(
			'friendly_name' => 'Vendor Feature Name',
			'description' => 'The Feature Name provided by the License Vendor.',
			'method' => 'other',
			'value' => '|arg1:feature_name|',
			),
		'user_feature_name' => array(
			'method' => 'textbox',
			'friendly_name' => 'Customer Name',
			'description' => 'A customer provided Feature Name recognizable by users.',
			'value' => '|arg1:user_feature_name|',
			'max_length' => '80',
			'size' => '60',
			'default' => ''
			),
		'application' => array(
			'method' => 'textbox',
			'friendly_name' => 'Application Name',
			'description' => 'A customer provided Application Name recognizable by users.',
			'value' => '|arg1:application|',
			'max_length' => '80',
			'size' => '60',
			'default' => ''
			),
		'critical' => array(
			'method' => 'drop_array',
			'friendly_name' => 'Key Feature',
			'description' => 'Is this a Key Feature for more critical monitoring?',
			'array' => array(1 => 'Yes', 0 => 'No'),
			'value' => '|arg1:critical|',
			'default' => '0'
			),
		'id' => array(
			'method' => 'hidden_zero',
			'value' => '|arg1:id|'
			),
		'save_component_fm' => array(
			'method' => 'hidden',
			'value' => '1'
			)
	);

	if (!isempty_request_var('id')) {
		$id = explode('|', get_request_var('id'));

		$fm = db_fetch_row_prepared('SELECT lafm.*, CONCAT(service_id, "|", feature_name) AS id
			FROM lic_application_feature_map AS lafm
			WHERE service_id=? AND feature_name=?', array($id[0], $id[1]));

		if ($fm){
			$header_label = 'Feature Application Mapping [edit: ' . html_escape($fm['feature_name']) . ']';
		}
	} else {
		$header_label = 'Feature Application Mapping [new]';
	}

	form_start('lic_feature_maps.php', 'lic_fmap_form');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_fm_edit, (isset($fm) ? $fm : array()))
		)
	);

	html_end_box();

	form_save_button('lic_feature_maps.php', '', 'id');
}

function lic_filter() {
	global $lic_search_types, $lic_rows_selector, $config;
	?>
	<tr class='odd'>
		<td>
		<form id='form_lic_config' action='lic_feature_maps.php'>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Manager', 'license');?>
					</td>
					<td width='1'>
						<select id='poller_type' onChange='applyFilter()'>
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
						<?php print __('Service', 'license');?>
					</td>
					<td width='1'>
						<select id='service' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('service') == '0') {?> selected<?php }?>>All</option>
							<option value='-1'<?php if (get_request_var('service') == '-1') {?> selected<?php }?>>Roll-Up</option>
							<?php
							$services = db_fetch_assoc_prepared('SELECT service_id AS id, server_name
								FROM lic_services ls
								INNER JOIN lic_pollers lp
								ON ls.poller_id=lp.id
								WHERE ls.disabled = ""
								AND lp.poller_type=?
								ORDER BY ls.server_name',
								array(get_request_var('poller_type')));

							if (cacti_sizeof($services)) {
								foreach ($services as $s) {
									print '<option value="' . $s['id'] .'"'; if (get_request_var('service') == $s['id']) { print ' selected'; } print '>' . html_escape($s['server_name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Application', 'license');?>
					</td>
					<td width='1'>
						<select id='application' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('application') == '0') {?> selected<?php }?>>All</option>
							<option value='-1'<?php if (get_request_var('application') == '-1') {?> selected<?php }?>>Unassigned</option>
							<?php
							$apps = db_fetch_assoc('SELECT DISTINCT application
								FROM lic_application_feature_map
								WHERE application!=""
								ORDER BY application');

							if (cacti_sizeof($apps)) {
								foreach ($apps as $app) {
									print '<option value="' . html_escape($app['application']) . '"'; if (get_request_var('application') == $app['application']) { print ' selected'; } print '>' . html_escape($app['application']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Mapping', 'license');?>
					</td>
					<td width='1'>
						<select id='status' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>Any</option>
							<option value='-3'<?php if (get_request_var('status') == '-3') {?> selected<?php }?>>Mapped</option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>>Unmapped</option>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Clear Filters' onClick='clearFilter()'>
					</td>
					<td>
						<input type='button' id='rebuild' value='Rebuild' title='Rebuild Feature List' onClick='rebuildMappings()'>
					</td>
				</tr>
			</table>
			<table cellpadding='2' cellspacing='0' class='filterTable'>
				<tr>
					<td style='width:60px;'>
						<?php print __('Search', 'license');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Key Feature', 'license');?>
					</td>
					<td>
						<select id='critical' onChange='applyFilter()'>
							<option value='-1'<?php if (get_request_var('critical') == '-1') {?> selected<?php }?>>All</option>
							<option value='1'<?php  if (get_request_var('critical') == '1') {?> selected<?php }?>>Yes</option>
							<option value='0'<?php  if (get_request_var('critical') == '0') {?> selected<?php }?>>No</option>
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
		</form>
		</td>
	</tr>
	<?php
}

function rebuild_feature_maps() {
	db_execute_prepared('INSERT IGNORE INTO lic_application_feature_map
		(service_id, feature_name, critical, user_id, last_updated)
		SELECT service_id, feature_name, "0" AS critical, ?, NOW()
		FROM lic_services_feature_use
		ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), last_updated=VALUES(last_updated)',
		array($_SESSION['sess_user_id']));
}

function lic_feature_map_records(&$sql_where, $apply_limits, $row_limit, &$sql_params) {

	if (get_request_var('application') == '0') {
		// Show all
	} elseif (get_request_var('application') == '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'lafm.application=""';
	} elseif (get_request_var('application') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'lafm.application=?';
		$sql_params[] = get_request_var('application');
	}

	if (get_request_var('service') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'lafm.service_id=?';
		$sql_params[] = get_request_var('service');
	}

	if (get_request_var('critical') >= 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'lafm.critical=?';
		$sql_params[] = get_request_var('critical');
	}

	if (get_request_var('poller_type') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'lp.poller_type=?';
		$sql_params[] = get_request_var('poller_type');
	}

	if (get_request_var('status') == -3) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(lafm.user_feature_name!="" AND lafm.application!="")';
	} elseif (get_request_var('status') == -2) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(lafm.user_feature_name="" OR lafm.application="")';
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ')
			. 'lafm.feature_name LIKE ?
			OR lafm.user_feature_name LIKE ?
			OR lafm.application LIKE ?';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();
	$sql_limit = '';

    if ($apply_limits) {
		$sql_limit .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
    }

	if (get_request_var('service') >= 0) {
	    $sql_query = "SELECT CONCAT(lafm.service_id, '|', lafm.feature_name) AS id, '1' AS services,
			lafm.service_id, lafm.feature_name, lafm.user_feature_name, lafm.application,
			lafm.manager_hint, lafm.critical, lafm.user_id, lafm.last_updated,
			ls.server_name, lsfu.feature_max_licenses, lsfu.feature_inuse_licenses,
			(lsfu.feature_inuse_licenses / lsfu.feature_max_licenses) * 100 AS utilization
			FROM lic_application_feature_map AS lafm
			INNER JOIN lic_services_feature_use AS lsfu
			ON lafm.service_id=lsfu.service_id
			AND lafm.feature_name=lsfu.feature_name
			INNER JOIN lic_services AS ls
			ON ls.service_id=lafm.service_id
			INNER JOIN lic_pollers AS lp
			ON ls.poller_id=lp.id
			$sql_where
			$sql_order
			$sql_limit";
	} else {
	    $sql_query = "SELECT lafm.feature_name AS id, COUNT(*) AS services,
			lafm.service_id, lafm.feature_name, lafm.user_feature_name, lafm.application,
            lafm.manager_hint, lafm.critical, lafm.user_id, MAX(lafm.last_updated) AS last_updated,
			ls.server_name,
			SUM(lsfu.feature_max_licenses) AS feature_max_licenses,
			SUM(lsfu.feature_inuse_licenses) AS feature_inuse_licenses,
			(SUM(lsfu.feature_inuse_licenses)/SUM(lsfu.feature_max_licenses)) * 100 AS utilization
			FROM lic_application_feature_map AS lafm
			INNER JOIN lic_services_feature_use AS lsfu
			ON lafm.service_id=lsfu.service_id
			AND lafm.feature_name=lsfu.feature_name
			INNER JOIN lic_services AS ls
			ON ls.service_id=lafm.service_id
			INNER JOIN lic_pollers AS lp
			ON ls.poller_id=lp.id
			$sql_where
			GROUP BY lafm.feature_name
			$sql_order
			$sql_limit";
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function get_first_lm(){
	$params = array();
	$sql_where = "";
	if (isset_request_var('reset') && isset_request_var('application')
		&& get_request_var('application') != '0' && get_request_var('application') != '-1') {
		$sql_where = ' AND lafm.application=?';
		$params[] = get_request_var('application');
	}
	$lmid = db_fetch_cell_prepared("SELECT DISTINCT lm.id FROM lic_managers lm
		JOIN lic_pollers lp ON lp.poller_type=lm.id
		JOIN lic_services ls ON ls.poller_id=lp.id
		JOIN lic_application_feature_map AS lafm ON ls.service_id=lafm.service_id
		WHERE ls.disabled='' $sql_where ORDER BY lm.name", $params);
	if(!isset($lmid) || empty($lmid)){
		return db_fetch_cell("SELECT DISTINCT lm.id FROM lic_managers lm
			WHERE lm.disabled <> 1 ORDER BY lm.name");
	}
	return $lmid;
}

function lic_feature_maps() {
	global $title, $report, $lic_search_types, $lic_rows_selector, $config;
	global $fm_actions, $config;
	$sql_params = array();

	if(check_changed('poller_type', 'sess_lfm_poller_type')){
		unset_request_var('service');
		kill_session_var('sess_lfm_service');
	}

	html_start_box(__('License Feature Mappings') , '100%', '', '3', 'center', '');
	lic_filter();
	html_end_box(true);

	$sql_where    = '';
	$apply_limits = true;

	if (get_request_var('rows_selector') == -1) {
		$row_limit = read_lic_config_option('grid_records');
	}elseif (get_request_var('rows_selector') == -2) {
		$row_limit = 99999999;
	} else {
		$row_limit = get_request_var('rows_selector');
	}

	$feature_maps = lic_feature_map_records($sql_where, $apply_limits, $row_limit, $sql_params);

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'lic_feature_maps.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&poller_type=' + $('#poller_type').val();
		strURL += '&application=' + $('#application').val();
		strURL += '&service=' + $('#service').val();
		strURL += '&critical=' + $('#critical').val();
		strURL += '&status=' + $('#status').val();
		strURL += '&rows_selector=' + $('#rows_selector').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'lic_feature_maps.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	function rebuildMappings() {
		strURL  = 'lic_feature_maps.php?action=rebuild';
		loadPageNoHeader(strURL);
	}


    $(function() {
        $('#form_lic_config').submit(function(event) {
            event.preventDefault();
            applyFilter();
        });
    });

	</script>
	<?php

	if (get_request_var('service') >= 0) {
		$rows_query_string = "SELECT COUNT(*)
			FROM lic_application_feature_map AS lafm
			INNER JOIN lic_services_feature_use AS lsfu
			ON lafm.service_id=lsfu.service_id
			AND lafm.feature_name=lsfu.feature_name
			INNER JOIN lic_services AS ls
			ON ls.service_id=lafm.service_id
			INNER JOIN lic_pollers AS lp
			ON ls.poller_id=lp.id
			$sql_where";
	} else {
		$rows_query_string = "SELECT COUNT(DISTINCT lafm.feature_name)
			FROM lic_application_feature_map AS lafm
			INNER JOIN lic_services_feature_use AS lsfu
			ON lafm.service_id=lsfu.service_id
			AND lafm.feature_name=lsfu.feature_name
			INNER JOIN lic_services AS ls
			ON ls.service_id=lafm.service_id
			INNER JOIN lic_pollers AS lp
			ON ls.poller_id=lp.id
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'feature_name'           => array('display' => __('Vendor Feature', 'license'), 'sort' => 'DESC'),
		'user_feature_name'      => array('display' => __('Assigned Name', 'license'), 'sort' => 'DESC'),
		'application'            => array('display' => __('Application', 'license'), 'sort' => 'ASC'),
		'server_name'            => array('display' => __('Service Name', 'license'), 'sort' => 'ASC'),
		'critical'               => array('display' => __('Key Feature', 'license'), 'align' => 'left', 'sort' => 'ASC'),
		'utilization'            => array('display' => __('Utilization', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'feature_max_licenses'   => array('display' => __('Max', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'feature_inuse_licenses' => array('display' => __('InUse', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'services'               => array('display' => __('Services', 'license'),'align'   => 'right','sort'    => 'DESC'),
		'user_id'                => array('display' => __('Modified By', 'license'), 'align' => 'right', 'sort' => 'ASC'),
		'last_updated'           => array('display' => __('Modification Date', 'license'), 'align' => 'right', 'sort' => 'DESC')
	);

	$nav = html_nav_bar('lic_feature_maps.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $row_limit, $total_rows, '', __('License Feature Maps'), 'page', 'main');

	form_start('lic_feature_maps.php', 'chk');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($feature_maps)) {
		foreach ($feature_maps as $fm) {
			form_alternate_row('line' . $fm['id'], true);

			if (get_request_var('service') >= 0) {
				$url = html_escape($config['url_path'] . 'plugins/license/lic_feature_maps.php?action=edit&id=' . $fm['id']) ;
				form_selectable_cell(filter_value($fm['feature_name'], get_request_var('filter'), $url), $fm['id']);
			} else {
				form_selectable_cell(filter_value($fm['feature_name'], get_request_var('filter')), $fm['id'], '', 'bold');
			}

			form_selectable_cell(filter_value($fm['user_feature_name'], get_request_var('filter')), $fm['id']);
			form_selectable_cell(filter_value($fm['application'], get_request_var('filter')), $fm['id']);

			if (get_request_var('service') >= 0) {
				form_selectable_cell($fm['server_name'], $fm['id']);
			} else {
				form_selectable_cell(__('N/A', 'license'), $fm['id']);
			}

			form_selectable_cell($fm['critical'] > 0 ? 'Yes':'No', $fm['id']);
			form_selectable_cell(number_format($fm['utilization'], 1) . ' %', $fm['id'], '', 'text-align:right');
			form_selectable_cell(number_format($fm['feature_max_licenses']), $fm['id'], '', 'text-align:right');
			form_selectable_cell(number_format($fm['feature_inuse_licenses']), $fm['id'], '', 'text-align:right');
			form_selectable_cell(number_format($fm['services']), $fm['id'], '', 'right');
			form_selectable_cell(html_escape(get_username($fm['user_id'])), $fm['id'], '', 'text-align:right');
			form_selectable_cell($fm['last_updated'], $fm['id'], '', 'text-align:right');
			form_checkbox_cell($fm['feature_name'], $fm['id']);
			form_end_row();
		}

		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='4'><em>No License Feature Maps Found</em></td></tr>";
		html_end_box(false);
	}

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($fm_actions);

	form_end();

	bottom_footer();
}

function lic_feature_request_validation() {
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
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'utilization',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'critical' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'service' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0',
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1',
			),
		'poller_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_first_lm(),
			'options' => array('options' => 'sanitize_search_string')
			),
		'application' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
		);

	validate_store_request_vars($filters, 'sess_lfm');
}

