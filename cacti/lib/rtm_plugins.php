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

function plugin_rtm_migrate_realms($plugin, $old_realm, $description, $files, $rtm_version) {
	if (version_compare($rtm_version, 10.2, '<')) {
		api_plugin_register_realm($plugin, $files, $description, 0);

		$realm = db_fetch_cell_prepared('SELECT id
			FROM plugin_realms
			WHERE file = ?
			AND plugin = ?',
			array($files, $plugin));

		if ($realm) {
			db_execute_prepared('UPDATE user_auth_realm
				SET realm_id = ?
				WHERE realm_id = ?',
				array($realm+100, $old_realm));
		}
	}
}

function plugin_rtm_remove_realm_data($plugin, $old_realm) {
	db_execute_prepared('DELETE FROM user_auth_realm
		WHERE realm_id = ?',
		array($old_realm));
}

function rtm_autocomplete_ajax($page_name, $autocomplete_filter_id, $sql_where = '', $extra_options = array('-1' => 'All')) {
	$return = array();
	$sql_params = array();

	$term = get_filter_request_var('term', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));

	if ($term != '') {
		$lic_performance_search = read_config_option('lic_performance_search');

		switch($autocomplete_filter_id) {
		case 'lic_host':
			if ($page_name == 'lic_dailystats.php') {
				if ($lic_performance_search) {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "ldst.host LIKE '$term%'";
				} else {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "ldst.host LIKE '%$term%'";
				}
			}
			break;
		case 'lic_user':
			if ($page_name == 'lic_dailystats.php') {
				if ($lic_performance_search) {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "ldst.user LIKE '$term%'";
				} else {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "ldst.user LIKE '%$term%'";
				}
			}
			break;
		case 'lic_feature':
			if ($page_name == 'lic_dailystats.php') {
				if ($lic_performance_search) {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "(lsfu.feature_name LIKE '$term%' OR lafm.user_feature_name LIKE '$term%')";
				} else {
					$sql_where .= ($sql_where != '' ? ' AND ' : '') . "(lsfu.feature_name LIKE '%$term%' OR lafm.user_feature_name LIKE '%$term%')";
				}
			}
			break;
		case 'job_user':
			if ($page_name == 'grid_bjobs.php') {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "user LIKE ?";
			} else {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "user_or_group LIKE ?";
			}
			$sql_params[] = "%$term%";

			break;
		case 'usergroup':
			if ($page_name == 'grid_bjobs2.php') {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "userGroup LIKE ?";
			} else {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "user_or_group LIKE ?";
			}
			$sql_params[] = "%$term%";

			break;
		case 'hgroup':
			$sql_where .= ($sql_where != '' ? ' AND ' : '') . "groupName LIKE ?";
			$sql_params[] = "%$term%";

			break;
		case 'exec_host':
			if ($page_name == 'grid_bjobs.php') {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "exec_host LIKE ?";
			} else {
				$sql_where .= ($sql_where != '' ? ' AND ' : '') . "host LIKE ?";
			}
			$sql_params[] = "%$term%";

			break;
		case 'project':
			$sql_where .= ($sql_where != '' ? ' AND ' : '') . "projectName LIKE ?";
			$sql_params[] = "%$term%";

			break;
		case 'queue':
			$sql_where .= ($sql_where != '' ? ' AND ' : '') . "queuename LIKE ?";
			$sql_params[] = "%$term%";

			break;
		case 'app':
			$sql_where .= ($sql_where != '' ? ' AND ' : '') . "appName LIKE ?";
			$sql_params[] = "%$term%";

			break;
		}
	}

	if (get_request_var('term') == '') {
		if (cacti_sizeof($extra_options)) {
			foreach ($extra_options as $extra_option_key => $extra_option_value) {
				$return[] = array('label' => $extra_option_value, 'value' => $extra_option_value, 'id' => $extra_option_key);
			}
		}
	}

	$total_rows = -1;

	$hosts = rtm_get_allowed_devices($page_name, $autocomplete_filter_id, $sql_where, read_config_option('autocomplete_rows'), $sql_params);

	if (cacti_sizeof($hosts)) {
		foreach($hosts as $host) {
			$return[] = array('label' => $host['description'], 'value' => $host['description'], 'id' => $host['id']);
		}
	}

	print json_encode($return);
}

function rtm_get_allowed_devices($page_name, $autocomplete_filter_id, $sql_where = '', $limit = '', $sql_params = array()) {
	$items= array();

	if ($limit != '') {
		$limit = "LIMIT $limit";
	}

	switch($autocomplete_filter_id) {
	case 'lic_host':
		if ($page_name == 'lic_dailystats.php') {
			if ($sql_where != '') {
				$sql_where = "WHERE $sql_where";
			} else {
				$sql_where = "";
			}

			$ajaxquery = "SELECT DISTINCT host AS id, host AS description
				FROM lic_daily_stats_traffic AS ldst
				INNER JOIN lic_services AS ls
				ON ls.service_id=ldst.service_id
				INNER JOIN lic_pollers AS lp
				ON ls.poller_id=lp.id
				$sql_where
				ORDER BY host
				$limit";
		}
		$items = db_fetch_assoc($ajaxquery);
		break;
	case 'lic_user':
		if ($page_name == 'lic_dailystats.php') {
			if ($sql_where != '') {
				$sql_where = "WHERE $sql_where";
			} else {
				$sql_where = "";
			}

			$ajaxquery = "SELECT DISTINCT user AS id, user AS description
				FROM lic_daily_stats_traffic AS ldst
				INNER JOIN lic_services AS ls
				ON ls.service_id=ldst.service_id
				INNER JOIN lic_pollers AS lp
				ON ls.poller_id=lp.id
				$sql_where
				ORDER BY user
				$limit";
		}
		$items = db_fetch_assoc($ajaxquery);
		break;
	case 'lic_feature':
		if ($page_name == 'lic_dailystats.php') {
			if ($sql_where != '') {
				$sql_where = "WHERE $sql_where";
			} else {
				$sql_where = "";
			}

			$ajaxquery = "SELECT DISTINCT IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, CONCAT('rtmft_', lsfu.feature_name), CONCAT('rtmapp_', lafm.user_feature_name)) AS id,
					IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, lsfu.feature_name, lafm.user_feature_name) AS description
					FROM lic_services_feature_use AS lsfu
					INNER JOIN lic_services AS ls
					ON ls.service_id=lsfu.service_id
					INNER JOIN lic_pollers AS lp
					ON lp.id=ls.poller_id
					LEFT JOIN lic_application_feature_map AS lafm
					ON lsfu.feature_name=lafm.feature_name
					AND lsfu.service_id=lafm.service_id
					$sql_where
					HAVING description!= ''
					ORDER BY description ASC
					$limit";
		}
		$items = db_fetch_assoc($ajaxquery);
		break;
	case 'job_user':
		if ($page_name == 'grid_bjobs.php') {
			if ($sql_where != '') {
				$sql_where = "WHERE $sql_where";
			} else {
				$sql_where = "";
			}

			$ajaxquery = "SELECT DISTINCT user AS id, user AS description
				FROM grid_jobs_users
				$sql_where
				ORDER BY user
				$limit";
		} else {
			if ($sql_where != '') {
				//$sql_where = "WHERE (numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0) AND type='U' AND $sql_where";
				$sql_where = "WHERE type='U' AND $sql_where";
			} else {
				//$sql_where = "WHERE (numRUN > 0 OR numUSUSP > 0 or numSSUSP > 0) AND type='U'";
				$sql_where = "WHERE type='U'";
			}

			$ajaxquery = "SELECT DISTINCT user_or_group AS id, user_or_group AS description
				FROM grid_users_or_groups
				$sql_where
				ORDER BY user_or_group
				$limit";
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);

		break;
	case 'hgroup':
		if ($sql_where != '') {
			$sql_where = "WHERE $sql_where";
		} else {
			$sql_where = "";
		}

		if ($page_name == 'grid_queue_distrib.php') {

			$items = array();
			/* add meta data to the dropdown */
			if (cacti_sizeof(db_fetch_row("SHOW TABLES LIKE 'grid_metadata'"))) {
				$queue_list = db_fetch_assoc("SELECT * FROM grid_metadata_conf WHERE OBJECT_TYPE='queue-group'");
				if (cacti_sizeof($queue_list)) {
					foreach($queue_list as $ql) {
						switch($ql['DATA_TYPE']) {
						case 'display_name':
							$display_col = $ql['DB_COLUMN_NAME'];
							break;
						case 'queue_list':
							$queues      = $ql['DB_COLUMN_NAME'];
							break;
						}
					}

					$queue_groups = db_fetch_assoc("SELECT OBJECT_ID AS id, $display_col AS name, $queues AS list
						FROM grid_metadata
						WHERE OBJECT_TYPE='queue-group'" .
						(get_request_var('clusterid') != '0' ? ' AND CLUSTER_ID=' . get_request_var('clusterid'):''));

					if (cacti_sizeof($queue_groups)) {
						foreach($queue_groups as $q) {
							$items = array_merge($items, array(array('id' => 'QG-'.$q['id'], 'description' => 'QG-'.$q['name'])));
						}
					}
				}
			}

			$ajaxquery = "SELECT DISTINCT groupName AS id, groupName AS description
				FROM grid_hostgroups
				$sql_where
				ORDER BY groupName
				$limit";

			$items = array_merge($items, db_fetch_assoc_prepared($ajaxquery, $sql_params));
		} else {
			$ajaxquery = "SELECT DISTINCT groupName AS id, groupName AS description
				FROM grid_hostgroups
				$sql_where
				ORDER BY groupName
				$limit";

			$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		}

		break;
	case 'exec_host':
		if ($sql_where != '') {
			$sql_where = "WHERE $sql_where";
		} else {
			$sql_where = "";
		}

		if ($page_name == 'grid_bjobs.php') {
			$ajaxquery = "SELECT DISTINCT exec_host AS id, exec_host AS description
				FROM grid_jobs_exec_hosts
				$sql_where
				ORDER BY exec_host
				$limit";
		} else {
			$ajaxquery = "SELECT DISTINCT host AS id, host AS description
				FROM grid_hosts
				$sql_where
				ORDER BY host
				$limit";
		}

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);

		break;
	case 'usergroup':
		if ($page_name == 'grid_bjobs.php' || $page_name == 'grid_bjobs2.php') {
			if ($page_name == 'grid_bjobs2.php') {
				if ($sql_where != '') {
					$sql_where = "WHERE $sql_where";
				} else {
					$sql_where = "";
				}

				$ajaxquery = "SELECT DISTINCT userGroup
					FROM grid_user_group_stats
					$sql_where
					ORDER BY userGroup
					$limit";
			} else {
				if ($sql_where != '') {
					$sql_where = "WHERE type='G' AND $sql_where";
				} else {
					$sql_where = "WHERE type='G'";
				}

				$ajaxquery = "SELECT DISTINCT user_or_group AS userGroup
					FROM grid_users_or_groups
					$sql_where
					ORDER BY user_or_group
					$limit";
			}

			$items_tmp = array();
			$items_tmp = db_fetch_assoc_prepared($ajaxquery, $sql_params);
			$items_tmp = get_aggregated_user_groups($items_tmp, 'userGroup');
			if (cacti_sizeof($items_tmp)) {
				foreach ($items_tmp as $value) {
					array_push($items, array('id' => $value['userGroup'], 'description' => $value['userGroup']));
				}
			}
		} else {
			if ($sql_where != '') {
				$sql_where = "WHERE type='G' AND $sql_where";
			} else {
				$sql_where = "WHERE type='G'";
			}

			$ajaxquery = "SELECT DISTINCT user_or_group AS id, user_or_group AS description
				FROM grid_users_or_groups
				$sql_where
				ORDER BY user_or_group
				$limit";

			$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);
		}

		break;
	case 'project':
		if ($sql_where != '') {
			$sql_where = "WHERE $sql_where";
		} else {
			$sql_where = "";
		}

		$ajaxquery = "SELECT DISTINCT projectName AS id, projectName AS description
			FROM grid_projects
			$sql_where
			ORDER BY projectName
			$limit";

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);

		break;
	case 'queue':
		if ($sql_where != '') {
			$sql_where = "WHERE $sql_where";
		} else {
			$sql_where = "";
		}

		$ajaxquery = "SELECT DISTINCT queuename AS id, queuename AS description
			FROM grid_queues
			$sql_where
			ORDER BY queuename
			$limit";

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);

		break;
	case 'app':
		if ($sql_where != '') {
			$sql_where = "WHERE $sql_where";
		} else {
			$sql_where = "";
		}

		$ajaxquery = "SELECT DISTINCT appName AS id, appName AS description
			FROM grid_applications
			$sql_where
			ORDER BY appName
			$limit";

		$items = db_fetch_assoc_prepared($ajaxquery, $sql_params);

		break;
	}

	return $items;
}

function html_autocomplete_filter($page_name, $label_name, $autocomplete_filter_id, $host_id = '-1', $call_back = 'applyFilter', $sql_where = '', $extra_options = array('-1' => 'All'), $width = '') {
	$theme = get_selected_theme();

	if (isset($extra_options['-1']) && $extra_options['-1'] == 'All') {
		$extra_options['-1'] = __('All', 'grid');
	}

	if (strpos($call_back, '()') === false) {
		$call_back .= '()';
	}

	if ($host_id == '-1' && isset_request_var($autocomplete_filter_id)) {
		$host_id = get_filter_request_var($autocomplete_filter_id);
	}

	if ($theme == 'classic' || !read_config_option('autocomplete_enabled')) {
		?>
		<td>
			<?php print $label_name;?>
		</td>
		<td>
			<select id='<?php print $autocomplete_filter_id;?>' name='<?php print $autocomplete_filter_id;?>' onChange='<?php print $call_back;?>'>
				<?php
				if (cacti_sizeof($extra_options)) {
					foreach ($extra_options as $extra_option_key => $extra_option_value) {
						print "<option value='$extra_option_key'";

						if ($host_id == $extra_option_key) {
							print ' selected';
						}

						print '>' . html_escape($extra_option_value) . '</option>';
					}
				}
				$devices = rtm_get_allowed_devices($page_name, $autocomplete_filter_id, $sql_where);

				if (cacti_sizeof($devices)) {
					foreach ($devices as $device) {
						print "<option value='" . $device['id'] . "'";

						if ($host_id == $device['id']) {
							print ' selected';
						}

						print '>' . title_trim(html_escape($device['description']), 40) . '</option>';
					}
				}
				?>
			</select>
		</td>
		<?php
	} else {
		$hostname = $host_id;
		if (cacti_sizeof($extra_options) > 0) {
			foreach ($extra_options as $extra_option_key => $extra_option_value) {
			    if ((string)$extra_option_key == $host_id) {//Fix 0=="xxx" is true
					$hostname = $extra_option_value;
					break;
				}
			}
		}

		?>
		<td>
			<?php print $label_name;?>
		</td>
		<td>
			<span id='<?php print $autocomplete_filter_id;?>_wrapper' class='ui-selectmenu-button ui-selectmenu-button-closed ui-corner-all ui-corner-all ui-button ui-widget' <?php print ($width <> '' ? 'style="width: ' . $width . 'px"' : ''); ?>>
				<span id='rtm_<?php print $autocomplete_filter_id;?>_click' class='ui-selectmenu-icon ui-icon ui-icon-triangle-1-s'></span>
				<span class='ui-select-text'>
					<input type='text' id='rtm_<?php print $autocomplete_filter_id;?>' value='<?php print html_escape($hostname);?>'>
				</span>
			</span>
			<input type='hidden' id='<?php print $autocomplete_filter_id;?>' name='<?php print $autocomplete_filter_id;?>' value='<?php print $host_id;?>'>
			<input type='hidden' id='call_back_<?php print $autocomplete_filter_id;?>' value='<?php print $call_back;?>'>
		</td>
		<?php
	}
}
