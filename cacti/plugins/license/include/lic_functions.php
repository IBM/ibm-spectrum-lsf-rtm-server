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

function make_partition_query($table_name, $start, $end, $sql_where, $join_map = '', $columns = '') {
	global $config;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

	$tables       = partition_get_partitions_for_query($table_name, $start, $end);
	$query        = '';
	if ($columns == '') {
		$query_prefix = 'SELECT * FROM ';
	} else {
		$query_prefix = 'SELECT ' . $columns . ' FROM ';
	}

	$i = 0;
	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			$sql_where2 = str_replace($table_name, $table, $sql_where);

			if ($i == 0) {
				$query  = '(' . $query_prefix . $table . " $join_map " . $sql_where2;
			} else {
				$query .= ' UNION ' . $query_prefix . $table . ' ' . $sql_where2;
			}

			$i++;
		}
	}

	$query = trim($query);

	if ($query == '') {
		if ($columns == '') {
			return $table_name;
		} else {
			return $table_name . ' ' . $sql_where;
		}
	}

	return  $query. ") AS $table_name";
}

function api_lic_poller_save($poller_id, $lic_poller_path, $lic_poller_description,
	$lic_poller_hostname, $lic_poller_type, $lic_manager_command_path= '') {
	if ($poller_id) {
		$save['id'] = $poller_id;
	} else {
		$save['id'] = '';
	}

	$save['poller_path']        = form_input_validate($lic_poller_path,          'lic_poller_path', '', false, 3);
	$save['poller_description'] = form_input_validate($lic_poller_description,   'lic_poller_description', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
	$save['poller_hostname']    = form_input_validate($lic_poller_hostname,      'lic_poller_hostname', '', false, 3);
	$save['poller_type']        = form_input_validate($lic_poller_type,          'lic_poller_type', '', false, 3);
	$save['client_path']        = form_input_validate($lic_manager_command_path, 'lic_manager_command_path', '', false, 3);

	if (!is_dir($save['poller_path'])) {
		raise_message(144);
		$_SESSION['sess_error_fields']['lic_poller_path'] = 'poller_path';
		$_SESSION['sess_field_values']['lic_poller_path'] = $lic_poller_path;
	} else {
		if (function_exists('scandir')) {
			$files = scandir($save['poller_path']);
		} elseif ($dh = opendir($save['poller_path'])) {
			while (($file = readdir($dh)) !== false) {
				$files[] = $file;
			}
			closedir($dh);
		}

		$found_file = 0;

		if (cacti_sizeof($files)) {
			foreach($files as $file) {
				if ($file == 'licpollerd') {
					$found_file = 1;
					break;
				}
			}
		}
	}

	$poller_id = 0;

	if (!is_error_message()) {
		$poller_id = sql_save($save, 'lic_pollers', 'id');
		if ($poller_id) {
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	return $poller_id;
}

function api_lic_manager_remove($manager_id) {
	$license_pollers = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM lic_pollers
		WHERE poller_type = ?', array($manager_id));

	if ($license_pollers > 0) {
		$_SESSION["sess_messages"] = 'Unable to delete manager while it is still in use';
		debug_log_insert('lic_manager', 'Status: Unable to delete manager while it is still in use');
	} else {
		db_execute_prepared('DELETE FROM lic_managers WHERE id = ?', array($manager_id));
	}
}

function api_lic_poller_remove($poller_id) {
	$license_servers = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM lic_services
		WHERE poller_id = ?', array($poller_id));

	if ($license_servers > 0) {
		$_SESSION["sess_messages"] = 'Unable to delete poller while it is still in use';
		debug_log_insert('lic_pollers', 'Status: Unable to delete poller while it is still in use');
	} else {
		db_execute_prepared('DELETE FROM lic_pollers WHERE id = ?', array($poller_id));
	}
}

function api_lic_server_save($id, $description, $poller_id, $disabled) {
	if ($id) {
		$save['service_id'] = $id;
	} else {
		$save['service_id'] = '';
	}

	$save['server_name'] = form_input_validate($description, 'server_name', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
	$save['poller_id']   = $poller_id;
	$save['disabled']    = $disabled;

	$id = 0;
	if (!is_error_message()) {
		$id = sql_save($save, 'lic_services', 'service_id');

		if ($id) {
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	return $id;
}

function api_lic_options_remove($service_id) {
	db_execute_prepared('DELETE FROM lic_services_options_feature WHERE service_id=?'      , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_feature_type WHERE service_id=?' , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_incexcl_all WHERE service_id=?'  , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_global WHERE service_id=?'       , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_host_groups WHERE service_id=?'  , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_max WHERE service_id=?'          , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_reserve WHERE service_id=?'      , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_user_groups WHERE service_id=?'  , array($service_id));
}

function api_lic_server_remove($service_id) {
	db_execute_prepared('DELETE FROM lic_services WHERE service_id=?'                 , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_feature WHERE service_id=?'         , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_feature_use WHERE service_id=?'     , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_feature_details WHERE service_id=?' , array($service_id));
	db_execute_prepared('DELETE FROM lic_interval_stats WHERE service_id=?'           , array($service_id));

	//remove all options data under this service_id
	db_execute_prepared('DELETE FROM lic_services_options_feature WHERE service_id=?'      , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_feature_type WHERE service_id=?' , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_global WHERE service_id=?'       , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_host_groups WHERE service_id=?'  , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_incexcl_all WHERE service_id=?'  , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_max WHERE service_id=?'          , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_reserve WHERE service_id=?'      , array($service_id));
	db_execute_prepared('DELETE FROM lic_services_options_user_groups WHERE service_id=?'  , array($service_id));

	/* for partitioning, let old data be cleaned up by data retention period */
	//db_execute('DELETE FROM lic_daily_stats WHERE service_id='.$service_id);

	if (!isset_request_var('delete_type')) { set_request_var('delete_type',2); }

	$cacti_host = db_fetch_cell_prepared('SELECT id FROM host WHERE lic_server_id=?', array($service_id));

	if ($cacti_host != '' && $cacti_host != 0) {
		$data_sources_to_act_on = array();
		$graphs_to_act_on       = array();
		$devices_to_act_on      = array();

		$data_sources = db_fetch_assoc_prepared("SELECT
			data_local.id AS local_data_id
			FROM data_local
			WHERE data_local.host_id=?
			OR data_local.host_id=?",
			array($cacti_host, $cacti_host));

		if (cacti_sizeof($data_sources)) {
			foreach ($data_sources as $data_source) {
				$data_sources_to_act_on[] = $data_source['local_data_id'];
			}
		}

		if (get_request_var('delete_type') == 2) {
			$graphs = db_fetch_assoc_prepared("SELECT
				graph_local.id AS local_graph_id
				FROM graph_local
				WHERE graph_local.host_id=?
				OR graph_local.host_id=?",
				array($cacti_host, $cacti_host));

			if (cacti_sizeof($graphs)) {
				foreach ($graphs as $graph) {
					$graphs_to_act_on[] = $graph['local_graph_id'];
				}
			}
		}

		switch (get_request_var('delete_type')) {
			case '1': /* leave tree/devices/graphs and data_sources in place, disable the hosts */
				/* disable all devices */
				db_execute_prepared("UPDATE host SET disabled='on' WHERE id=?", array($cacti_host));

				break;
			case '2': /* delete tree/devices/graphs/data sources tied to this device */
				api_data_source_remove_multi($data_sources_to_act_on);
				api_graph_remove_multi($graphs_to_act_on);

				/* remove all devices */
				api_device_remove($cacti_host);

				break;
		}
	}
}

function api_lic_server_disable($service_id) {
	db_execute_prepared("UPDATE lic_services SET disabled='on' WHERE service_id=?" , array($service_id));
	db_execute_prepared("UPDATE lic_services SET status=0 WHERE service_id=?"      , array($service_id));
}

function api_lic_server_enable($service_id) {
	db_execute_prepared("UPDATE lic_services SET disabled='' WHERE service_id=?" , array($service_id));
	db_execute_prepared("UPDATE lic_services SET status=0 WHERE service_id=?"    , array($service_id));
}

function api_lic_service_save($service_id, $poller_id, $server_name, $server_portatserver,
	$server_subisv, $server_timezone, $enable_checkouts, $timeout, $retries,
	$server_location, $server_support_info, $server_department, $server_licensetype,
	$poller_interval, $file_path, $options_path, $prefix, $server_vendor, $server_region) {

	if ($service_id) {
		$save['service_id'] = $service_id;
	} else {
		$save['service_id'] = '';
	}

	$save['server_name']	     = form_input_validate($server_name, 'server_name', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1');
	$save['poller_interval']     = $poller_interval;
	$save['server_portatserver'] = $server_portatserver;
	$save['server_subisv']       = $server_subisv;
	$save['server_timezone']     = $server_timezone;

	if ($enable_checkouts == 'on') {
		$save['enable_checkouts'] = $enable_checkouts;
	} else {
		$save['enable_checkouts'] = 'off';
	}

	$save['timeout']	         = $timeout;
	$save['retries']	         = $retries;
	$save['server_vendor']       = (strlen($server_vendor))? form_input_validate($server_vendor, 'server_vendor', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1'):$server_vendor;
	$save['server_location']     = (strlen($server_location))? form_input_validate($server_location, 'server_location', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1'):$server_location;
	$save['server_region']       = (strlen($server_region))? form_input_validate($server_region, 'server_region', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1'):$server_region;
	$save['server_support_info'] = $server_support_info;
	$save['server_department']   = (strlen($server_department))? form_input_validate($server_department, 'server_department', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1'):$server_department;
	$save['server_licensetype']  = (strlen($server_licensetype))? form_input_validate($server_licensetype, 'server_licensetype', '^[A-Za-z0-9\.\,_\\\@\ -]+$', false, 'field_input_save_1'):$server_licensetype;
	$save['file_path']  	     = $file_path;
	$save['options_path']  	     = $options_path;
	$save['prefix']              = $prefix;

	$service_id = 0;
	if (!is_error_message()) {
		$service_id = sql_save($save, 'lic_services', 'service_id', false);

		if ($service_id) {
			raise_message(1);
		} else {
			raise_message(2);
		}
	}

	return $service_id;
}

function lic_set_minimum_page_refresh() {
	global $config, $refresh;

	$minimum = read_config_option('grid_minimum_refresh_interval');

	if (isset_request_var('refresh')) {
		if (get_request_var('refresh') < $minimum) {
			set_request_var('refresh',$minimum);
		}

		/* automatically reload this page */
		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page']    = $config['url_path'] . 'plugins/license/' . basename($_SERVER['PHP_SELF']);
	} else {
		$refresh['seconds'] = '999999';
		$refresh['page']    = $config['url_path'] . 'plugins/license/' . basename($_SERVER['PHP_SELF']);
	}
}

function lic_checkouts_enabled() {
	if (db_fetch_cell('SELECT enable_checkouts FROM lic_services WHERE enable_checkouts="on" LIMIT 1') == 'on') {
		return true;
	} else {
		return false;
	}
}

function lic_strip_domain($host) {
	if($host != "N/A")
		return strtolower($host);
	return $host;
}

function lic_view_footer($trailing_br = false) {
?>
				</table>
			</td>
		</tr>
	</table>
	<?php if ($trailing_br == true) { print '<br>'; } ?>
<?php
}

function get_service_type($service_id) {
	$poller_type = db_fetch_row_prepared('SELECT CONCAT("\'", lm.hash, "\'") hash, lm.failover_hosts
		FROM lic_managers AS lm
		INNER JOIN lic_pollers AS lp ON lp.poller_type=lm.id
		INNER JOIN lic_services AS ls ON lp.id=ls.poller_id
		WHERE ls.service_id=?', array($service_id));
	if (isset($poller_type) && !empty($poller_type)) {
		return array($poller_type['hash'] => $poller_type['failover_hosts']);
	} else {
		return array('Unknown' => 'Unknown');
	}
}

function lic_get_quorum_status($id, $flage=true, &$server_title='') {
	$up        = 0;
	$down      = 0;
	$master_up = 0;
	$slave_up  = 0;
	$disabled  = false;

	if (db_fetch_cell_prepared('SELECT disabled FROM lic_services WHERE service_id=?', array($id)) == 'on') {
		$disabled = true;
	}

	$service_ha = get_service_type($id);

	if (isset($service_ha['9190583ab345af80da86efd5d683eb72']) || current($service_ha) == 3) { // FLEXlm
		$server_errors   = db_fetch_assoc_prepared("SELECT lic_servers.name, lic_servers.errorno,lic_errorcode_maps.error_text
						FROM lic_servers
						LEFT JOIN lic_errorcode_maps ON lic_servers.errorno = lic_errorcode_maps.errorno
						WHERE service_id=? AND lic_errorcode_maps.type=0", array($id));
		if (cacti_sizeof($server_errors)) {
		foreach ($server_errors as $server_error) {
			if ($server_error['errorno'] != '0') {
				$server_title .= "Server ". $server_error['name'] . " (". $server_error['errorno'] . ") ". $server_error['error_text'] . " ";
			}
		}
		}
		$sql_count = db_fetch_cell_prepared('SELECT COUNT(*) FROM lic_servers WHERE service_id=?', array($id));
		$queries   = db_fetch_assoc_prepared('SELECT * FROM lic_servers WHERE service_id=?', array($id));

		foreach ($queries as $query) {
			if ($query['status'] == 'UP') {
				$up ++;
			} else {
				$down ++;
			}
		}

		if ($sql_count == 0) {
			$server_title = "Cannot connect to license server system, Please check your configurations"; //fix no server info is returned.
			$lic_service_status=db_fetch_row_prepared('SELECT status, server_portatserver FROM lic_services WHERE service_id=?', array($id));
			if ($lic_service_status['status']==0) {
				return lic_get_colored_quorum_status('', '');
			} elseif ($lic_service_status['status'] == 1) {
				/*flag is for multi server status item show*/
				if ((substr_count($lic_service_status['server_portatserver'],',:')==2) && !$flage) {
					return lic_get_colored_quorum_status('', '');
				} else {
					return 'N/A';
				}
			}
		} elseif ($sql_count == 1) {
			return 'N/A';
		} elseif ($up == $sql_count) {
			return lic_get_colored_quorum_status($disabled, 'server_up');
		} elseif ($down>=2) {
			return lic_get_colored_quorum_status($disabled, 'server_down');
		} else {
			return lic_get_colored_quorum_status($disabled, 'server_partial');
		}
	} elseif (isset($service_ha['33fb6598074464bc113893507c623ee0']) || current($service_ha) == 2) {
		$sql_count = db_fetch_cell_prepared('SELECT COUNT(*) FROM lic_servers WHERE service_id=?', array($id));
		$queries   = db_fetch_assoc_prepared('SELECT * FROM lic_servers WHERE service_id=?', array($id));

		foreach ($queries as $query) {
			if ($query['status'] == 'UP' && $query['type'] == 'MASTER') {
				$master_up ++;
			} elseif ($query['status'] == 'UP' && $query['type'] == 'SERVER') {
				$slave_up ++;
			}else {
				$down ++;
			}
		}

		if ($sql_count == 0) {
			return lic_get_colored_quorum_status('', '');
		} elseif ($sql_count == 1) {
			return 'N/A';
		} elseif ((($master_up+$slave_up) == $sql_count) && $master_up != 0) {
			return lic_get_colored_quorum_status($disabled, 'server_up');
		} elseif ($down>=2 || $master_up == 0) {
			return lic_get_colored_quorum_status($disabled, 'server_down');
		} else {
			return lic_get_colored_quorum_status($disabled, 'server_partial');
		}
	} else {
		return 'N/A';
	}
}

function lic_get_colored_quorum_status($disabled, $status) {
	$disabled_color = 'a1a1a1';

	$status_colors = array(
		'server_down'    => 'ff0000',
		'server_partial' => 'ff8f1e',
		'server_up'      => '198e32'
	);

	if  ($disabled) {
		return "<span style='color: #$disabled_color'>Disabled</span>";
	} else {
		switch ($status) {
			case 'server_up':
				return "<span style='color: #" . $status_colors['server_up'] . "'>All Up</span>";
				break;
			case 'server_partial':
				return "<span style='color: #" . $status_colors['server_partial'] . "'>Partially Up</span>";
				break;
			case 'server_down':
				return "<span style='color: #" . $status_colors['server_down'] . "'>All Down</span>";
				break;
			default:
				return "<span style='color: #0000ff'>Unknown</span>";
				break;
		}
	}
}

function lic_get_license_service_records(&$sql_where, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	/* license server sql where */
	if (!isset_request_var('location') || get_request_var('location') == -1) {
		/* Show all items */
	} elseif (get_request_var('location') == -2) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (server_location='')";
	}else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (server_location=?)";
		$sql_params[] = get_request_var('location');
	}

	if (!isset_request_var('region') || get_request_var('region') == -1) {
		/* Show all items */
	} elseif (get_request_var('region') == -2) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (server_region='')";
	}else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (server_region=?)";
		$sql_params[] = get_request_var('region');
	}

	if (isset_request_var('poller_type') && get_request_var('poller_type') > 0) {
		$sql_where .= ($sql_where !='' ? ' AND ':'WHERE ') . ' (lp.poller_type=?)';
		$sql_params[] = get_request_var('poller_type');
	}

	/* license master sql where */
	if (get_request_var('vendor') != -1) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(server_vendor=?)";
		$sql_params[] = get_request_var('vendor');
	}

	/* license server status */
	if (get_request_var('status') == '-1') {
		/* Show all items */
	} elseif (get_request_var('status') == '-2') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " ls.disabled='on'";
	} elseif (get_request_var('status') == '-3') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " ls.disabled=''";
	} elseif (get_request_var('status') == '-4') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (status!='3' OR ls.disabled='on')";
	}else {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (status=? AND ls.disabled = '')";
		$sql_params[] = get_request_var('status');
	}

	/* license quorum and filter where */
	if (get_request_var('quorum') == -1) { // All
		if (get_request_var('filter') != '') {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_name LIKE ?
				OR server_licensetype LIKE ?
				OR server_department LIKE ?)";
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}
	} elseif (get_request_var('quorum') == -2) { // Quorums
		if (get_request_var('filter') != '') {
			$sql_where = $sql_where . ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_name LIKE ?
				OR server_licensetype LIKE ?
				OR server_department LIKE ?)";
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';

			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_portatserver LIKE '%;%'
				OR server_portatserver LIKE '%:%'
				OR server_portatserver LIKE '%,%')";
		} else {
			$sql_where = $sql_where . ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_portatserver LIKE '%;%'
				OR server_portatserver LIKE '%:%'
				OR server_portatserver LIKE '%,%')";
		}
	}else { // Standalone
		if (get_request_var('filter') != '') {
			$sql_where = $sql_where . ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_name LIKE ?
				OR server_licensetype LIKE ?
				OR server_department LIKE ?)";
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';
			$sql_params[] = '%'. get_request_var('filter') . '%';

			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_portatserver NOT LIKE '%;%'
				AND server_portatserver NOT LIKE '%:%'
				AND server_portatserver NOT LIKE '%,%')";
		} else {
			$sql_where = $sql_where . ($sql_where != '' ? ' AND ':'WHERE ') .
				" (server_portatserver NOT LIKE '%;%'
				AND server_portatserver NOT LIKE '%:%'
				AND server_portatserver NOT LIKE '%,%')";
		}
	}

	$sql_query = "SELECT service_id AS id, server_name AS name, lm.name AS manager_name,
		server_vendor, server_timezone, server_department, server_location, server_region,
		server_licensetype, server_support_info, poller_id, poller_description, poller_interval,
		status, cur_time, avg_time, max_time, availability, status_event_count, lp.poller_type,
		status_fail_date, poller_date, ls.disabled, ls.errorno
		FROM lic_services AS ls
		INNER JOIN lic_pollers AS lp
		ON ls.poller_id=lp.id
		INNER JOIN lic_managers AS lm
		ON lp.poller_type=lm.id
		$sql_where";

	$sort_order = get_order_string();

	$sql_query = 'SELECT * FROM (' . $sql_query . ") AS sally $sort_order";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function validate_post_field($poller_type, $description, $poller_interval,
	$portatserver = '', $subisv = '', $timezone = '', $retries = '',
	$filepath = '', $prefix = '') {

	$changed = false;

	$failover_hosts = db_fetch_cell_prepared('SELECT failover_hosts FROM lic_managers WHERE id = ?', array($poller_type));

	switch($poller_type) {
		case '1':
			form_input_validate($description, 'server_name', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
			form_input_validate($poller_interval, 'poller_interval', '', false, 3);
			form_input_validate($portatserver, 'server_portatserver', '', false, 3);
			form_input_validate($subisv, 'server_subisv', '', true,  3);
			form_input_validate($timezone, 'server_timezone', '', false, 3);
			form_input_validate($retries, 'retries', '', false, 3);
			form_input_validate($filepath, 'file_path', '', true,  3);
			form_input_validate($prefix, 'prefix', '', true,  3);

			$server_port = explode(':', $portatserver);
			$file_path   = explode(':', $filepath);
			$prefix      = explode(':', $prefix);

			switch (cacti_sizeof($server_port)) {
				case 1:
					if (cacti_sizeof($prefix) > 1 && cacti_sizeof($file_path) > 1) {
						raise_message(204);
						$_SESSION['sess_error_fields']['prefix'] = 'prefix';
						$_SESSION['sess_error_values']['prefix'] = $prefix;
						$_SESSION['sess_error_fields']['file_path'] = 'file_path';
						$_SESSION['sess_error_values']['file_path'] = $filepath;
					} elseif (cacti_sizeof($prefix) > 1) {
						raise_message(206);
						$_SESSION['sess_error_fields']['prefix'] = 'prefix';
						$_SESSION['sess_error_values']['prefix'] = $prefix;
					} elseif (cacti_sizeof($file_path) > 1) {
						raise_message(205);
						$_SESSION['sess_error_fields']['file_path'] = 'file_path';
						$_SESSION['sess_error_values']['file_path'] = $filepath;
					}

					break;
				case 2:
					raise_message(207);
					$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';
					$_SESSION['sess_error_values']['server_portatserver'] = $portatserver;
					break;
				case 3:
					if (cacti_sizeof($prefix) == 1 && cacti_sizeof($file_path) == 1) {
						// This is an acceptable setup
					} elseif (cacti_sizeof($prefix) == 3 && cacti_sizeof($file_path) == 3) {
						// This is an acceptable setup
					} elseif (cacti_sizeof($prefix) == 3 && cacti_sizeof($file_path) == 1) {
						// This is an acceptable setup
					} elseif (cacti_sizeof($prefix) == 1 && cacti_sizeof($file_path) == 3) {
						// This is an acceptable setup
					} elseif (cacti_sizeof($prefix) == 0 && cacti_sizeof($file_path) > 0) {
						raise_message(200);
						$_SESSION['sess_error_fields']['prefix'] = 'prefix';
						$_SESSION['sess_error_values']['prefix'] = $prefix;
					} elseif (cacti_sizeof($prefix) > 0 && cacti_sizeof($file_path) == 0) {
						raise_message(200);
						$_SESSION['sess_error_fields']['prefix'] = 'prefix';
						$_SESSION['sess_error_values']['prefix'] = $prefix;
					} elseif (cacti_sizeof($prefix) == 2) {
						raise_message(201);
						$_SESSION['sess_error_fields']['prefix'] = 'prefix';
						$_SESSION['sess_error_values']['prefix'] = $prefix;
					} elseif (cacti_sizeof($file_path) == 2) {
						raise_message(202);
						$_SESSION['sess_error_fields']['file_path'] = 'file_path';
						$_SESSION['sess_error_values']['file_path'] = $filepath;
					}
					break;
			}

			if (!is_error_message()) {
				if (isset_request_var('service_id') && get_request_var('service_id') > 0) {
					$server_path_prefix = db_fetch_row_prepared('SELECT file_path, prefix
						FROM lic_services
						WHERE service_id=?', array(get_request_var('service_id')));

					if ($server_path_prefix['prefix'] != $prefix && $server_path_prefix['file_path'] != $filepath) {
						$changed = true;
					} elseif ($server_path_prefix['prefix'] == $prefix && $server_path_prefix['file_path'] != $filepath) {
						$changed = true;
					} elseif ($server_path_prefix['prefix'] != $prefix && $server_path_prefix['file_path'] == $filepath) {
						$changed = true;
					} else {
						$changed = false;
					}
				}
			}

			if ($changed) {
				db_execute_prepared("DELETE FROM settings where name like ?", array("licflexlogparser_%_".get_request_var('service_id')));
			}

			break;
		default:
			form_input_validate($description, 'server_name', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
			form_input_validate($poller_interval, 'poller_interval', '', false, 3);
			form_input_validate($portatserver, 'server_portatserver', '', false, 3);
			form_input_validate($subisv, 'server_subisv', '', true,  3);
			form_input_validate($timezone, 'server_timezone', '', false, 3);
			form_input_validate($retries, 'retries', '', false, 3);
			form_input_validate($filepath, 'file_path', '', true, 3);
			form_input_validate($prefix, 'prefix', '.', true, 3);

			$server_port = explode(':', $portatserver);
			$file_path   = explode(':', $filepath);
			$prefix      = explode(':', $prefix);

			switch (cacti_sizeof($server_port)) {
				case 1:
					break;
				case 2:
					if ($failover_hosts != 2) {
						raise_message(217);
						$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';
						$_SESSION['sess_error_values']['server_portatserver'] = $portatserver;
					}
					break;
				case 3:
					if ($failover_hosts != 3) {
						raise_message(218);
						$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';
						$_SESSION['sess_error_values']['server_portatserver'] = $portatserver;
					}
					break;
				default:
					raise_message(213);
					$_SESSION['sess_error_fields']['server_portatserver'] = 'server_portatserver';
					$_SESSION['sess_error_values']['server_portatserver'] = $portatserver;
					break;
			}

			break;
	}
}

function check_expirations_error($lic) {
	$current_time = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));

	$expiration_time = db_fetch_cell_prepared("SELECT UNIX_TIMESTAMP(feature_expiration_date)
		FROM lic_services_feature
		INNER JOIN lic_services
		ON lic_services_feature.service_id=lic_services.service_id
		WHERE poller_id=?
		AND lic_services.service_id=?
		AND vendor_daemon=?
		AND feature_name =?
		AND feature_expiration_date =?",
		array($lic['poller_id'], $lic['service_id'], $lic['vendor_daemon'], $lic['feature_name'], $lic['feature_expiration_date']));

	$status = db_fetch_cell_prepared("SELECT lic_services_feature_use.status
		FROM lic_services_feature_use
		INNER JOIN lic_services
		ON lic_services_feature_use.service_id=lic_services.service_id
		WHERE poller_id=?
		AND lic_services.service_id=?
		AND feature_name=?
		AND vendor_daemon=?",
		array($lic['poller_id'], $lic['service_id'], $lic['feature_name'], $lic['vendor_daemon']));

	if ($status == 'VALID') {
		if ($expiration_time == 0 || $expiration_time > $current_time) {
			return 0;
		} else {
			return 1;
		}
	} elseif ($status == 'ERROR') {
		if (($expiration_time < $current_time) && $expiration_time != 0) {
			return 1;
		}
		return 2;
	}
}

function get_host_name($server_name) {
	$servername = explode(':', $server_name);
	return $servername[1];
}

function get_feature_name($feature, $service_id = "N/A") {
	global $cnn_id;

	static $mapped_features = array();
	$sql_params = array();

	if(strpos($feature, 'rtmft_') !== false || strpos($feature, 'rtmapp_') !== false)
		return substr($feature, strpos($feature, '_')+1);

	if ($service_id != 'N/A' && $service_id > 0) {
		$sql_where = ' AND lsfu.service_id = ?';
		$sql_params[] = $service_id;
	} else {
		$sql_where = '';
	}

	if (isset($mapped_features[$feature])) {
		return $mapped_features[$feature];
	} else {
		$mapped = db_fetch_cell_prepared("SELECT IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, ?, lafm.user_feature_name)
			FROM lic_services_feature_use AS lsfu
			LEFT JOIN lic_application_feature_map AS lafm
			ON lsfu.feature_name=lafm.feature_name
			AND lsfu.service_id=lafm.service_id
			WHERE lsfu.feature_name = ?
			$sql_where
			LIMIT 1", array_merge(array($feature, $feature), $sql_params));

		if (empty($mapped)) {
			$mapped_features[$feature] = $feature;
		} else {
			$mapped_features[$feature] = $mapped;
		}

		return $mapped_features[$feature];
	}
}

/**
 * return array as (rtmapp_appname, appname) or (rtmft_ftname, ftname)
 */
function get_feature_select_items($service_id, $poller_type = -1, $user = -1, $last_updated = -1, $search = '', $key = false) {
	global $cnn_id;
	$sql_params = array();
	$sql_params_join = array();

	if ($service_id != 'N/A' && $service_id > 0) {
		$sql_where = ' WHERE lsfu.service_id = ?';
		$sql_params[] = $service_id;
	} else {
		$sql_where = '';
	}

	if ($poller_type > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' lp.poller_type = ?';
		$sql_params[] = $poller_type;
	}

	if ($search != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "`value` LIKE ?";
		$sql_params[] = '%'. $search . '%';
	}

	if ($key) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' lafm.critical=1';
	}

	if (($user != -1 && $user != -2) || $last_updated != -1) {
		$join_where = '';

		if ($service_id != 'N/A' && $service_id > 0) {
			$join_where .= ($join_where != '' ? ' AND ':'WHERE ') . ' service_id = ?';
			$sql_params_join[] = $service_id;
		}

		if ($user != -1 && $user != -2) {
			$join_where .= ($join_where != '' ? ' AND ':'WHERE ') . ' user = ?';
			$sql_params_join[] = $user;
		}

		if ($last_updated != -1) {
			$join_where .= ($join_where != '' ? ' AND ':'WHERE ') . ' last_updated >= ?';
			$sql_params_join[] = $last_updated;
		}

		$sql_join = 'INNER JOIN (
			SELECT DISTINCT service_id, feature AS feature_name
			FROM lic_daily_stats_traffic ' . $join_where . ') AS ldst
			ON lsfu.feature_name=ldst.feature_name
			AND ldst.service_id=lsfu.service_id';
	} else {
		$sql_join = '';
	}

	$sql_query = "SELECT DISTINCT IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, CONCAT('rtmft_', lsfu.feature_name), CONCAT('rtmapp_', lafm.user_feature_name)) AS `index`,
		IF(lafm.user_feature_name = '' OR lafm.user_feature_name IS NULL, lsfu.feature_name, lafm.user_feature_name) AS `value`
		FROM lic_services_feature_use AS lsfu
		INNER JOIN lic_services AS ls
		ON ls.service_id=lsfu.service_id
		INNER JOIN lic_pollers AS lp
		ON lp.id=ls.poller_id
		$sql_join
		LEFT JOIN lic_application_feature_map AS lafm
		ON lsfu.feature_name=lafm.feature_name
		AND lsfu.service_id=lafm.service_id
		$sql_where
		HAVING `value`!= ''
		ORDER BY `value` ASC";
	//cacti_log("DEBUG: " . str_replace("\n", " ", $sql_query));
	return db_fetch_assoc_prepared($sql_query, array_merge($sql_params_join, $sql_params));
}

function get_first_feature($service_id, $poller_type, $user, $date, $search, $keyfeat) {
	$key = $keyfeat == 'true' ? true:false;

	$features = get_feature_select_items($service_id, $poller_type, $user, $date, $search, $key);
	if (cacti_sizeof($features)) {
		return $features[0]['index'];
	} else {
		return '';
	}
}

function get_first_feature_from_req($req_feature){
	if(strpos($req_feature, 'rtmft_') !== false)
		return substr($req_feature, strpos($req_feature, '_')+1);
	else if (strpos($req_feature, 'rtmapp_') !== false)
		return db_fetch_cell_prepared("SELECT feature_name FROM lic_application_feature_map WHERE user_feature_name = ? ORDER BY feature_name LIMIT 1", array(substr($req_feature, strpos($req_feature, '_')+1)));
	else
		return $req_feature;
}

function get_feature_where($feature_name){
	global $cnn_id;

	if (strpos($feature_name, 'rtmapp_') !== false){
		return "lafm.user_feature_name = " . db_qstr(substr($feature_name, strpos($feature_name, '_')+1));
	}else {
		if(strpos($feature_name, 'rtmft_') !== false)
			$feature_name = substr($feature_name, strpos($feature_name, '_')+1);
		return "feature = " . db_qstr($feature_name);
	}
}

function lic_adjust_predefined_timespan() {
	global $lic_timespans;

	foreach($lic_timespans as $key => $ts) {
		if ($key < '11') {
			unset($lic_timespans[$key]);
		} elseif ($key > '19') {
			if ($key < '22' || $key > '24') {
				unset($lic_timespans[$key]);
			} else {
				$new_timespans[$key] = $ts;
			}
		} else {
			$new_timespans[$key] = $ts;
		}
	}

	$lic_timespans = $new_timespans;
}


/**
 * format's a table row such that it can be highlighted using cacti's js actions
 *
 * @param mixed $contents the readable portion of the
 * @param mixed $id the id of the object that will be highlighted
 * @param string $width the width of the table element
 * @param string $style_or_class the style or class to apply to the table element
 * @param string $title optional title for the column
 * @return void
 */
function lic_selectable_cell($contents, $id, $width = '', $style_or_class = '', $title = '') {
	$output = '';

	if ($style_or_class != '') {
		if (strpos($style_or_class, ':') === false) {
			$output = "class='nowrap " . $style_or_class . "'";
			if ($width != '') {
				$output .= " style='width:$width;'";
			}
		} else {
			$output = "class='nowrap' style='" . $style_or_class;
			if ($width != '') {
				$output .= ";width:$width;";
			}
			$output .= "'";
		}
	} else {
		$output = 'class="nowrap"';

		if ($width != '') {
			$output .= " style='width:$width;'";
		}
	}

	if ($id != '') {
		$output .= ' id="' . $id . '"';
	}

	if ($title != '') {
		$wrapper = "<span class='cactiTooltipHint' style='padding:0px;margin:0px;' title='" . str_replace(array('"', "'"), '', $title) . "'>" . $contents . "</span>";
	} else {
		$wrapper = $contents;
	}

	print "\t<td " . $output . ">" . $wrapper . "</td>\n";
}


function lic_show_metadata_detailed($metatype, $type, $object_value, $searchable = false) {
	global $config;

	static $count = 0;

	include_once($config['base_path'] . '/plugins/meta/lib/metadata_api.php');

	$params = json_encode(
		array(
			'count'      => $count,
			'type'       => $type,
			'cluster-id' => 0,
			'object-key' => $object_value
		)
	);

	$display_value = meta_preprocess_object_json($params);

	if ($display_value == '') {
		if (empty($object_value) || $object_value == 'NULL') {
			$display_value = '-';
		} else {
			$display_value = $object_value;
		}
	}

	if ($metatype == 'simple') {
		$class = 'meta-simple';
	} else {
		$class = 'meta-detailed';
	}

	$class = trim($class);
	$id = lic_base64url_encode($params);

	if ($searchable) {
		lic_selectable_cell(filter_value($display_value, get_request_var('filter')), $id, '', $class);
	} else {
		lic_selectable_cell($display_value, $id, '', $class);
	}

	$count++;
}

function lic_base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function lic_adjust_predefined_timeshift() {
	global $lic_timeshifts;

	foreach($lic_timeshifts as $key => $ts) {
		if ($key < '11') {
			unset($lic_timeshifts[$key]);
		} elseif ($key > '19') {
			unset($lic_timeshifts[$key]);
		} else {
			$new_timeshifts[$key] = $ts;
		}
	}

	$lic_timeshifts = $new_timeshifts;
}

function lic_debug($message) {
	global $debug;

	if (defined('CACTI_DATE_TIME_FORMAT')) {
		$date = date(CACTI_DATE_TIME_FORMAT);
	} else {
		$date = date('Y-m-d H:i:s');
	}

	if ($debug) {
		print $date . ' - DEBUG: ' . trim($message) . PHP_EOL;
	}

	if (substr_count($message, 'ERROR:')) {
		cacti_log($message, false, 'LIC');
	}
}

/* substitute_lic_server_data - takes a string and substitutes all host variables contained in it
	@arg $string - the string to make host variable substitutions on
	@arg $l_escape_string - the character used to escape each variable on the left side
	@arg $r_escape_string - the character used to escape each variable on the right side
	@arg $cluster_id - (int) the host ID to match
	@returns - the original string with all of the variable substitutions made */
function substitute_lic_server_data($string, $l_escape_string, $r_escape_string, $lic_server_id) {
	if (!isset($_SESSION['sess_lic_server_cache_array'][$lic_server_id])) {
		$lic = db_fetch_row_prepared('SELECT *
			FROM lic_services
			WHERE service_id=?', array($lic_server_id));

		$_SESSION['sess_lic_server_cache_array'][$lic_server_id] = $lic;
	}

	$server_name = isset($_SESSION['sess_lic_server_cache_array'][$lic_server_id]['server_name']) ? $_SESSION['sess_lic_server_cache_array'][$lic_server_id]['server_name'] : '';
	$service_id  = isset($_SESSION['sess_lic_server_cache_array'][$lic_server_id]['service_id']) ?  $_SESSION['sess_lic_server_cache_array'][$lic_server_id]['service_id'] : '';
	$string = str_replace($l_escape_string . 'server_name'     . $r_escape_string, $server_name, $string); /* for compatability */
	$string = str_replace($l_escape_string . 'lic_description' . $r_escape_string, $server_name, $string); /* for compatability */
	$string = str_replace($l_escape_string . 'lic_server_id'   . $r_escape_string, $service_id, $string);

	return $string;
}


function lic_update_license_interval_stats($current_time) {
	global $cnn_id;

	db_execute_prepared("REPLACE INTO settings
		(name, value) VALUES
		('lic_interval_stats_start_time', ?)", array($current_time));

	if (read_config_option('poller_interval') == 300) {
		$minus_time = 5;
	}else {
		$minus_time = 1;
	}

	/*Fix Problem 236802 */
	$max_time    = db_fetch_cell('SELECT MAX(date_recorded) AS time FROM lic_interval_stats');

	if (empty($max_time)) {
		$max_time = '1970-01-01';
	}

	$earlier_time = mktime(date('H'), date('i')-$minus_time, date('s'), date('n'), date('j'), date('Y'));
	$earlier_time = date('Y-m-d H:i:s', $earlier_time);
	$current_time = date('Y-m-d H:i:s', floor($current_time));

	lic_debug("Tabulating interval stats between $earlier_time and $current_time!!!");

	$interim_table = 'lic_interval_stats_' . time();
	//drop the lic_interval_stats before insert, this makes the data in it easy to check.
	db_execute("CREATE TABLE IF NOT EXISTS `$interim_table` LIKE `lic_interval_stats`");
	db_execute("RENAME TABLE `lic_interval_stats` to `lic_interval_stats_old`, `$interim_table` TO `lic_interval_stats`");
	db_execute("DROP TABLE IF EXISTS `lic_interval_stats_old`");

	$sql_prefix = 'INSERT INTO lic_interval_stats
		(service_id, feature, user,
		host, action, count, total_license_count,
		vendor, interval_end, date_recorded, event_id) VALUES';

	$sql_suffix = '';

	/** This query is for utilization **/
	$rows = db_fetch_assoc_prepared('SELECT lsfd.service_id, lsfd.vendor_daemon, lsfd.feature_name,
		lsfd.username, lsfd.hostname, SUM(tokens_acquired) AS count, MAX(last_updated) AS last_updated,
		MAX(lsfu.feature_max_licenses) AS feature_max_licenses
		FROM lic_services_feature_details AS lsfd
		INNER JOIN lic_services_feature_use AS lsfu
		ON lsfu.service_id=lsfd.service_id
		AND lsfu.feature_name=lsfd.feature_name
		AND lsfu.vendor_daemon=lsfd.vendor_daemon
		WHERE lsfd.status="start"
		AND last_updated > ?
		GROUP BY `service_id`,`feature_name`,`username`,`hostname`,`vendor_daemon`', array($max_time));


	$i = 0;
	$sql_insert = '';
	foreach($rows as $row) {
		$sql_insert .= ($i > 0 ? ',':'')         . '(' .
			$row['service_id']                   . ', ' .
			db_qstr($row['feature_name'])  . ', ' .
			db_qstr($row['username'])      . ', ' .
			db_qstr($row['hostname'])      . ', ' .
			'"INUSE"'                            . ', ' .
			lic_nf($row['count'])      . ', ' .
			lic_nf($row['feature_max_licenses']) . ', ' .
			db_qstr($row['vendor_daemon']) . ', ' .
			db_qstr($current_time)         . ', ' .
			db_qstr($row['last_updated'])  . ', 0)';

		$i++;

		if ($i > 50) {
			$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
			db_execute($sql);

			$sql_insert = '';
			$i = 0;
		}
	}

	if (strlen($sql_insert)) {
		$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
		db_execute($sql);
	}

	lic_debug('Updating interval stats utilizaton.');
	lic_calculate_utilization($earlier_time);

	lic_calculate_inout_interval($earlier_time, $current_time);

	/* update the traffic numbers */
	db_execute_prepared("INSERT INTO lic_daily_stats_traffic
		(service_id, feature, user, host)
		SELECT DISTINCT service_id, feature, user, host
		FROM lic_interval_stats
		WHERE interval_end >= ?
		ON DUPLICATE KEY UPDATE last_updated=NOW()", array($earlier_time));

	/* remove old records that are 1 day older than the oldest record in daily stats */
	db_execute("DELETE FROM lic_daily_stats_traffic
		WHERE last_updated < (
			SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(MIN(interval_end)) - 604800)
			FROM lic_daily_stats
		)");

	$current = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));

	db_execute_prepared("REPLACE INTO settings
		(name, value)
		VALUES ('lic_interval_stats_end_time', ?)", array($current));
}

function lic_calculate_inout_interval($earlier_time, $current_time) {
	global $cnn_id;

	$temp_poller_interval = read_config_option('poller_interval');
	if ($temp_poller_interval) {
		$cycles_per_hour = 3600 / $temp_poller_interval;
	} else {
		$cycles_per_hour = 3600 / 300;
	}

	// Preparing some sql query, no type
	$sql_prefix_insert = 'INSERT INTO lic_daily_stats_today
		(service_id, feature, user, host,
		action, count, total_license_count,
		utilization, peak_ut, vendor, duration, transaction_count,
		interval_end, date_recorded) ';

	$sql_suffix = 'ON DUPLICATE KEY UPDATE
		count = VALUES(count),
		utilization = VALUES(utilization),
		peak_ut = VALUES(peak_ut),
		date_recorded = VALUES(date_recorded),
		interval_end = VALUES(interval_end)';

	db_execute("CREATE TEMPORARY TABLE IF NOT EXISTS `lic_daily_stats_temp` LIKE `lic_daily_stats`");
	/* utilization of the total availability is defined as the total license used over a
	 * period of a day which is defined as 12 samples an hour times 24 hours.
	 */
	db_execute("INSERT INTO lic_daily_stats_temp SELECT * FROM lic_daily_stats_today WHERE type='0'");
	$cal_query = "$sql_prefix_insert
		SELECT service_id, feature, user, host, 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor,
			SUM(duration) AS duration,
			SUM(count) AS transaction_count,
			interval_end, date_recorded
		FROM (
			SELECT service_id, feature, user, host, SUM(count) AS count,
				total_license_count,
				(CASE WHEN MAX(total_license_count) > 0 THEN SUM(count)/(MAX(total_license_count)*$cycles_per_hour*24) ELSE 0 END) as utilization,
				MAX(count/total_license_count) AS peak_ut,
				vendor,
				SUM(duration) AS duration,
				SUM(count) AS transaction_count,
				interval_end, date_recorded
			FROM lic_interval_stats
			where utilization != 0
			GROUP BY service_id, vendor, feature, user, host, total_license_count
			UNION ALL
			SELECT service_id, feature, user, host, count, total_license_count,utilization,peak_ut,vendor,duration, transaction_count, interval_end,date_recorded
			FROM lic_daily_stats_temp
			where type = '0'
		) as a GROUP BY service_id, vendor, feature, user, host, total_license_count
		$sql_suffix";
	db_execute($cal_query);
	db_execute("DROP TABLE IF EXISTS `lic_daily_stats_temp`");

	if(read_config_option('lic_calculate_inout_disable') == ''){
		lic_calc_peak_util_interval($earlier_time, $current_time, 1);
		lic_calc_peak_util_interval($earlier_time, $current_time, 2);
		lic_calc_peak_util_interval($earlier_time, $current_time, 3);
		lic_calc_peak_util_interval($earlier_time, $current_time, 4);
		lic_calc_peak_util_interval($earlier_time, $current_time, 5);
		lic_calc_peak_util_interval($earlier_time, $current_time, 6);
		lic_calc_peak_util_interval($earlier_time, $current_time, 7);
	}

}

function lic_calc_peak_util_interval($earlier_time, $current_time, $type) {
	global $cnn_id;

	$temp_poller_interval = read_config_option('poller_interval');
	if ($temp_poller_interval) {
		$cycles_per_hour = 3600 / $temp_poller_interval;
	} else {
		$cycles_per_hour = 3600 / 300;
	}

	$sql_prefix_insert = 'INSERT INTO lic_daily_stats_today
		(service_id, feature, user, host,
		action, count, total_license_count,
		utilization, peak_ut, vendor, duration, transaction_count, type,
		interval_end, date_recorded) ';

	$sql_suffix = 'ON DUPLICATE KEY UPDATE
		count = VALUES(count),
		utilization = VALUES(utilization),
		peak_ut = VALUES(peak_ut),
		date_recorded = VALUES(date_recorded),
		interval_end = VALUES(interval_end)';

	db_execute("CREATE TEMPORARY TABLE IF NOT EXISTS `lic_daily_stats_temp` LIKE `lic_daily_stats`");
	db_execute_prepared("INSERT INTO lic_daily_stats_temp SELECT * FROM lic_daily_stats_today WHERE type=?", array($type));

	switch ($type) {
	case 1:
		$cal_query = "$sql_prefix_insert
			SELECT '0', feature, 'N/A', 'N/A', 'INUSE', SUM(count) AS count,
			MAX(total_license_count) AS total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'1',
			interval_end, date_recorded
			FROM (
				SELECT raw.feature, SUM(raw.count) AS count,
			    sumTotal.total_license_count,
			    SUM(raw.count)/(sumTotal.total_license_count*$cycles_per_hour*24) AS utilization,
			    SUM(raw.count)/(sumTotal.total_license_count) AS peak_ut,
			    raw.vendor,
			    raw.interval_end, raw.date_recorded
				FROM
				(
					SELECT * FROM lic_interval_stats WHERE utilization != 0
				) raw
				JOIN
				(
					SELECT SUM(total_license_count) AS total_license_count, feature, vendor
					FROM
					(
						SELECT total_license_count, feature, vendor
						FROM lic_interval_stats
						WHERE utilization != 0
						GROUP BY feature, vendor, service_id
					) total
					GROUP BY feature, vendor
				) sumTotal
				ON
				raw.feature=sumTotal.feature AND raw.vendor=sumTotal.vendor
				WHERE raw.utilization != 0
				GROUP BY raw.feature, raw.vendor
				UNION ALL
				SELECT feature, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				WHERE type = '1'
			) AS a GROUP BY feature, vendor
			$sql_suffix, total_license_count = VALUES(total_license_count)";
		db_execute($cal_query);
		break;
	case 2:
		$cal_query = "$sql_prefix_insert
			SELECT service_id, feature, 'N/A', 'N/A', 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'2',
			interval_end, date_recorded
			FROM (
				SELECT service_id, feature, SUM(count) AS count,
					total_license_count,
					SUM(count)/(total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(count)/total_license_count AS peak_ut,
					vendor,
					interval_end, date_recorded
				FROM lic_interval_stats
				where utilization != 0
				GROUP BY feature, vendor, service_id
				UNION ALL
				SELECT service_id,feature, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				where type = '2'
			) as a GROUP BY feature, vendor, service_id
			$sql_suffix";
		db_execute($cal_query);

		break;
	case 3:
		$cal_query = "$sql_prefix_insert
			SELECT '0', feature, user, 'N/A', 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'3',
			interval_end, date_recorded
			FROM (
				SELECT raw.feature, raw.user, SUM(raw.count) AS count,
					sumTotal.total_license_count,
					SUM(raw.count)/(sumTotal.total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(raw.count)/sumTotal.total_license_count AS peak_ut,
					raw.vendor,
					raw.interval_end, raw.date_recorded
					FROM
					(
						SELECT * FROM lic_interval_stats WHERE utilization != 0
					) raw
					JOIN
					(
						SELECT SUM(total_license_count) AS total_license_count, feature, vendor
						FROM
						(
							SELECT total_license_count, feature, vendor
							FROM lic_interval_stats
							WHERE utilization != 0
							GROUP BY feature, vendor, service_id
						) total
						GROUP BY feature, vendor
					) sumTotal
					ON
					raw.feature=sumTotal.feature AND raw.vendor=sumTotal.vendor
					WHERE raw.utilization != 0
					GROUP BY raw.feature, raw.vendor, raw.user
					UNION ALL
					SELECT feature, user, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
					FROM lic_daily_stats_temp
					where type = '3'
			) as a GROUP BY feature, vendor, user
			$sql_suffix";
		db_execute($cal_query);

		break;
	case 4:
		$cal_query = "$sql_prefix_insert
			SELECT service_id, feature, user, 'N/A', 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'4',
			interval_end, date_recorded
			FROM (
				SELECT service_id, feature, user, SUM(count) AS count,
					total_license_count,
					SUM(count)/(total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(count)/total_license_count AS peak_ut,
					vendor,
					interval_end, date_recorded
				FROM lic_interval_stats
				where utilization != 0
				GROUP BY feature, vendor, user, service_id
				UNION ALL
				SELECT service_id, feature, user, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				where type = '4'
			) as a GROUP BY feature, vendor, user, service_id
			$sql_suffix";
		db_execute($cal_query);

		break;
	case 5:
		$cal_query = "$sql_prefix_insert
			SELECT '0', feature, 'N/A', host, 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'5',
			interval_end, date_recorded
			FROM (
				SELECT raw.feature, raw.host, SUM(raw.count) AS count,
					sumTotal.total_license_count,
					SUM(raw.count)/(sumTotal.total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(raw.count)/sumTotal.total_license_count AS peak_ut,
					raw.vendor,
					raw.interval_end, raw.date_recorded
				FROM
				(
					SELECT * FROM lic_interval_stats WHERE utilization != 0
				) raw
				JOIN
				(
					SELECT SUM(total_license_count) AS total_license_count, feature, vendor
					FROM
					(
						SELECT total_license_count, feature, vendor
						FROM lic_interval_stats
						WHERE utilization != 0
						GROUP BY feature, vendor, service_id
					) total
					GROUP BY feature, vendor
				) sumTotal
				ON
				raw.feature=sumTotal.feature AND raw.vendor=sumTotal.vendor
				WHERE raw.utilization != 0
				GROUP BY raw.feature, raw.vendor, raw.host
				UNION ALL
				SELECT feature, host, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				where type = '5'
			) as a GROUP BY feature, vendor, host
			$sql_suffix";
		db_execute($cal_query);

		break;
	case 6:
		$cal_query = "$sql_prefix_insert
			SELECT service_id, feature, 'N/A', host, 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'6',
			interval_end, date_recorded
			FROM (
				SELECT service_id, feature, host, SUM(count) AS count,
					total_license_count,
					SUM(count)/(total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(count)/total_license_count AS peak_ut,
					vendor,
					interval_end, date_recorded
				FROM lic_interval_stats
				where utilization != 0
				GROUP BY feature, vendor, host, service_id
				UNION ALL
				SELECT service_id, feature, host, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				where type = '6'
			) as a GROUP BY feature, vendor, host, service_id
			$sql_suffix";
		db_execute($cal_query);

		break;
	case 7:
		$cal_query = "$sql_prefix_insert
			SELECT '0', feature, user, host, 'INUSE', SUM(count) AS count,
			total_license_count,
			SUM(utilization),
			(CASE WHEN MAX(peak_ut) > 1 THEN 1 ELSE MAX(peak_ut) END) AS peak_ut,
			vendor, '0', '0',
			'7',
			interval_end, date_recorded
			FROM (
				SELECT raw.feature, raw.user, raw.host, SUM(raw.count) AS count,
					sumTotal.total_license_count,
					SUM(raw.count)/(sumTotal.total_license_count*$cycles_per_hour*24) AS utilization,
					SUM(raw.count)/sumTotal.total_license_count AS peak_ut,
					raw.vendor,
					raw.interval_end, raw.date_recorded
				FROM
				(
					SELECT * FROM lic_interval_stats WHERE utilization != 0
				) raw
				JOIN
				(
					SELECT SUM(total_license_count) AS total_license_count, feature, vendor
					FROM
					(
						SELECT total_license_count, feature, vendor
						FROM lic_interval_stats
						WHERE utilization != 0
						GROUP BY feature, vendor, service_id
					) total
					GROUP BY feature, vendor
				) sumTotal
				ON
				raw.feature=sumTotal.feature AND raw.vendor=sumTotal.vendor
				WHERE raw.utilization != 0
				GROUP BY raw.feature, raw.vendor, raw.user, raw.host
				UNION ALL
				SELECT feature, user, host, count, total_license_count,utilization,peak_ut,vendor,interval_end,date_recorded
				FROM lic_daily_stats_temp
				where type = '7'
			) as a GROUP BY feature, vendor, user, host
			$sql_suffix";
		db_execute($cal_query);

		break;
	default:
		break;
	}
	db_execute("DROP TABLE IF EXISTS `lic_daily_stats_temp`");

}

function lic_update_license_daily_stats($current_time) {
	db_execute("DELETE FROM settings
		WHERE name='lic_daily_stats_start_time'");

	db_execute_prepared("INSERT INTO settings
		(name, value)
		VALUES ('lic_daily_stats_start_time', ?)", array($current_time));

	$earlier_time = mktime(date('H'), date('i'), date('s'), date('n'), date('j')-1, date('Y'));
	$earlier_time = date('Y-m-d H:i:s', $earlier_time);
	$current_time = date('Y-m-d H:i:s', floor($current_time));

	lic_debug("Tabulating daily stats between $earlier_time and $current_time!!");

	// calculating another special row just for the avg utilization based on feature, service_id, vendor ONLY
	lic_cal_avg_util($earlier_time, $current_time);

	$interim_table = 'lic_daily_stats_today_' . time();
	// fix lic_daily_stats_today is missing
	db_execute("CREATE TABLE IF NOT EXISTS `$interim_table` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`user` varchar(40) NOT NULL DEFAULT '',
		`host` varchar(64) NOT NULL DEFAULT '',
		`action` varchar(20) NOT NULL DEFAULT '',
		`count` int(10) unsigned NOT NULL DEFAULT '0',
		`total_license_count` int(10) unsigned NOT NULL DEFAULT '0',
		`utilization` float NOT NULL DEFAULT '0',
		`peak_ut` float NOT NULL DEFAULT '0',
		`vendor` varchar(40) NOT NULL DEFAULT '0',
		`duration` int(10) unsigned NOT NULL DEFAULT '0',
		`transaction_count` int(10) unsigned NOT NULL DEFAULT '0',
		`type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
		`interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `interval_end` (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `vendor` (`vendor`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	db_execute("CREATE TABLE IF NOT EXISTS `lic_daily_stats_today` LIKE `$interim_table`");

	// move data to lic_daily_stats_today_old quicker, then new data can insert into the new lic_daily_stats_today.
	db_execute("DROP TABLE IF EXISTS `lic_daily_stats_today_old`");
	db_execute("RENAME TABLE `lic_daily_stats_today` TO `lic_daily_stats_today_old`, `$interim_table` TO `lic_daily_stats_today`");

	db_execute("INSERT INTO lic_daily_stats (service_id,feature,user,host,action,count,total_license_count,utilization,peak_ut,vendor,duration,transaction_count,type,interval_end,date_recorded)
		SELECT service_id,feature,user,host,action,count,total_license_count,utilization,peak_ut,vendor,duration,transaction_count,type,'$current_time','$current_time'
		FROM `lic_daily_stats_today_old`");
	db_execute("DROP TABLE IF EXISTS `lic_daily_stats_today_old`");

	// Updating the interval_stats end time
	$current = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));
	db_execute("DELETE FROM settings where name='lic_daily_stats_end_time'");
	db_execute_prepared("INSERT INTO settings (name, value) values ('lic_daily_stats_end_time', ?)", array($current));
}

function lic_update_servers_status() {
	lic_debug('Updating license servers status.');

	$service_list = db_fetch_assoc("SELECT
		ls.service_id, lp.poller_type, ls.poller_date,
		ls.poller_interval, ls.status
		FROM lic_services ls
		INNER JOIN lic_pollers lp
		ON ls.poller_id = lp.id
		WHERE ls.disabled != 'on'");

	if (cacti_sizeof($service_list)) {
		foreach ($service_list as $server) {
			if ($server['status'] != 0) { // only update non-unknown status
				$last_interval = strtotime($server['poller_date']);
				if (time() - $last_interval > $server['poller_interval'] * 3) {
					lic_debug('Service ID:'. $server['service_id'] .', Type: '. $server['poller_type'] . ' has not been polled for 3 polling interval, update license server to status: Unknown(0).');

					db_execute_prepared('UPDATE lic_services SET status=0 WHERE service_id=?', array($server['service_id']));
				}
			}
		}
	}
}

function lic_cal_avg_util($earlier_time, $current_time) {
	global $cnn_id;

	$i          = 0;
	$sql_insert = '';
	$sql        = '';
	$type       = '8';

	// Preparing some sql query
	$sql_prefix = 'INSERT INTO lic_daily_stats_today
		(service_id, feature, user, host,
		action, count, total_license_count,
		utilization, peak_ut,vendor, duration,
		transaction_count, type,
		interval_end, date_recorded) VALUES';

	$sql_suffix = 'ON DUPLICATE KEY UPDATE
		action = VALUES(action),
		count = VALUES(count),
		total_license_count = VALUES(total_license_count),
		duration = VALUES(duration),
		utilization = VALUES(utilization),
		peak_ut = VALUES(peak_ut),
		date_recorded = VALUES(date_recorded),
		interval_end = VALUES(interval_end),
		transaction_count = VALUES(transaction_count)';

	//type=8 is for fusion chart page
	$queries = db_fetch_assoc("SELECT a.service_id, a.feature, a.utilization, a.vendor,
		a.total_license_count, b.peak_ut, b.count
		FROM (
			SELECT service_id, feature, SUM(utilization) AS utilization,
			vendor, AVG(total_license_count) as total_license_count,date_recorded
			FROM lic_daily_stats_today
			WHERE user!=''
			AND type='0'
			GROUP BY service_id, feature, vendor
		) AS a
		LEFT JOIN (
			SELECT service_id, feature, vendor, peak_ut, count, date_recorded
			FROM lic_daily_stats_today
			WHERE type='2'
			GROUP BY service_id, feature, vendor
		) AS b
		ON a.service_id = b.service_id
		AND a.feature=b.feature
		AND a.vendor=b.vendor
		ORDER BY feature, vendor ASC");

	$i = 0;
	if (cacti_sizeof($queries)) {
		foreach ($queries as $query) {
			$sql_insert .= ($i > 0 ? ', ':'')         . '('.
				$query['service_id']                  . ', ' .
				db_qstr($query['feature'])      . ', ' .
				'""'                                  . ', ' .
				'""'                                  . ', ' .
				'"INUSE"'                             . ', ' .
				lic_nf($query['count'])               . ', ' .
				lic_nf($query['total_license_count']) . ', ' .
				lic_nf($query['utilization'])         . ', ' .
				lic_nf($query['peak_ut'])             . ', ' .
				db_qstr($query['vendor'])       . ', ' .
				'0'                                   . ', ' .
				'0'                                   . ', ' .
				db_qstr($type)                  . ', ' .
				db_qstr($current_time)          . ', ' .
				db_qstr($current_time)          . ')';

			$i++;

			if (($i % 50) == 0) {
				$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
				db_execute($sql);
				$sql_insert = '';

				$i = 0;
			}
		}
	}

	if (strlen($sql_insert)) {
		$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
		db_execute($sql);
	}
}

function lic_nf($value) {
	if (empty($value)) {
		return '0';
	} else {
		return $value;
	}
}

function lic_calculate_utilization($earlier_time) {
	db_execute_prepared("UPDATE lic_interval_stats
		SET utilization=(count/total_license_count)
		WHERE utilization = 0
		AND interval_end>=?", array($earlier_time));
}

function lic_count_query_size($results) {
	return (count($results));
}

function lic_purge_event($start_time, $last_maint_start) {
	lic_debug('About to enter lic data retention processing');

	if (read_config_option('lic_data_retention', true)) {
		$retention_period = date('Y-m-d H:i:s', strtotime('-' . read_config_option('lic_data_retention', true)));
	} else {
		$retention_period = date('Y-m-d H:i:s', strtotime('-2months'));
	}

	if (empty($last_maint_start)) {
		$last_maint_start=time()-86400;
	}

	// Rollup denials
	/*db_execute('INSERT IGNORE INTO lic_denials
		(service_id, vendor_daemon, feature, tokens, user, hostname, raw_count, start_time, end_time)
		SELECT service_id, vendor_daemon, feature, tokens,
		user, hostname, COUNT(*) AS raw_count,
		MIN(event_time) AS start_time, MAX(event_time) AS end_time
		FROM lic_log_events
		WHERE event_time > FROM_UNIXTIME(' . $last_maint_start . ')
		AND action = "DENIED"
		GROUP BY service_id, vendor_daemon, feature,
		tokens, user, hostname,
		UNIX_TIMESTAMP(event_time) DIV ' . read_config_option('lic_denial_duration'));*/

	if (read_config_option('grid_partitioning_enable') == '') {
		db_execute_prepared('DELETE FROM lic_daily_stats WHERE date_recorded < ?', array($retention_period));
	} else {
		global $config;

		include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

		/* determine if a new partition needs to be created */
		if (partition_timefor_create('lic_daily_stats', 'interval_end')) {
			partition_create('lic_daily_stats', 'interval_end', 'interval_end');
			//partition_create('lic_log_events', 'event_time', 'event_time');
		}

		/* remove old partitions if required */
		lic_debug("Pruning Partitions for 'lic_daily_stats'");
		partition_prune_partitions('lic_daily_stats');
		//partition_prune_partitions('lic_log_events');
	}

	/*delete old data from table lic_daily_stats_traffic*/
	if (read_config_option('grid_partitioning_enable') == '') {
		db_execute_prepared('DELETE FROM lic_daily_stats_traffic WHERE last_updated < ?', array($retention_period));
	} else {
		$date = db_fetch_cell('SELECT MIN(min_time) FROM grid_table_partitions WHERE table_name = "lic_daily_stats"');
		db_execute_prepared('DELETE FROM lic_daily_stats_traffic WHERE last_updated < ?', array($date));
	}

	/*optimize table lic_daily_stats and lic_daily_stats_traffic after delete*/
	if (grid_set_maintenance_mode('OPTIMIZE_LIC', true)) {
		/* add a heartbeat for optimization */
		make_heartbeat(0, 'OPTIMIZE_LIC');

		lic_debug('Optimizing the lic_daily_stats table.');
		db_execute('OPTIMIZE TABLE lic_daily_stats');
		lic_debug('Optimizing the lic_daily_stats_traffic table.');
		db_execute('OPTIMIZE TABLE lic_daily_stats_traffic');
		lic_debug('Optimizing the lic_services_feature_details table.');
		db_execute('OPTIMIZE TABLE lic_services_feature_details');

		/* put RTM in maintenance mode */
		grid_end_maintenance_mode('OPTIMIZE_LIC');
	}
}

function lic_calc_peak_util($peak_ut_tabname, $lic_use_tabname, $earlier_time, $current_time, $type) {
	global $cnn_id;

	$temp_poller_interval = read_config_option('poller_interval');
	if ($temp_poller_interval) {
		$cycles_per_hour = 3600 / $temp_poller_interval;
	} else {
		$cycles_per_hour = 3600 / 300;
	}

	$sql_query  = '';
	$sql_insert = '';
	$sql_prefix = 'INSERT INTO lic_daily_stats
		(service_id, feature, user, host,
		action, count, total_license_count,
		utilization, peak_ut, vendor, duration, transaction_count, type,
		interval_end, date_recorded) VALUES';

	$sql_suffix = 'ON DUPLICATE KEY UPDATE
		action = VALUES(action),
		count = VALUES(count),
		total_license_count = VALUES(total_license_count),
		duration = VALUES(duration),
		utilization = VALUES(utilization),
		peak_ut = VALUES(peak_ut),
		interval_end = VALUES(interval_end),
		type = VALUES(type),
		transaction_count = VALUES(transaction_count)';

	$i = 0;
	switch ($type) {
	case 1:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_count) AS peak_ut,
			SUM(count)/(total_count*$cycles_per_hour*24) AS utilization,
			total_count AS total_license_count, feature,vendor
			FROM (
				SELECT SUM(count) AS count, total_license_count,
				feature_max_licenses AS total_count,
				feature, vendor
				FROM $peak_ut_tabname AS a
				INNER JOIN (
					SELECT SUM(feature_max_licenses) AS feature_max_licenses, feature_name, vendor_daemon
					FROM $lic_use_tabname
					GROUP BY feature_name, vendor_daemon
				) AS b
				ON a.feature=b.feature_name
				AND a.vendor=b.vendor_daemon
				WHERE total_license_count > 0
				GROUP BY feature, vendor, interval_end
			) AS c
			GROUP BY feature, vendor";

		$i = 0;
		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] != 0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						'0'                                   . ', ' .
						db_qstr($query['feature'])      . ', ' .
						'"N/A"'                               . ', ' .
						'"N/A"'                               . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i++;
				}

				if ($i >= 50) {
					$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 2:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_license_count) AS peak_ut,
			SUM(count)/(MAX(total_license_count)*$cycles_per_hour*24) AS utilization,
			total_license_count, feature, vendor, service_id
			FROM (
				SELECT SUM(count) AS count, MAX(total_license_count) AS total_license_count,
				feature, vendor, service_id
				FROM $peak_ut_tabname
				WHERE total_license_count > 0
				GROUP BY feature, vendor, service_id, interval_end
			) AS it
			GROUP BY feature, vendor, service_id";

		$i = 0;
		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] != 0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						$query['service_id']                  . ', ' .
						db_qstr($query['feature'])      . ', ' .
						'"N/A"'                               . ', ' .
						'"N/A"'                               . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}

				if ($i >= 50) {
					$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 3:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_count) AS peak_ut,
			SUM(count)/(total_count*$cycles_per_hour*24) AS utilization,
			total_count AS total_license_count, feature, vendor, user
			FROM (
				SELECT SUM(count) AS count, total_license_count,
				feature_max_licenses AS total_count,
				feature, vendor, user
				FROM $peak_ut_tabname AS a
				INNER JOIN (
					SELECT SUM(feature_max_licenses) AS feature_max_licenses, feature_name, vendor_daemon
					FROM $lic_use_tabname
					GROUP BY feature_name, vendor_daemon
				) AS b
				ON a.feature=b.feature_name
				AND a.vendor=b.vendor_daemon
				WHERE total_license_count > 0
				GROUP BY feature, vendor, user, interval_end
			) AS C
			GROUP BY feature, vendor, user";

		$i = 0;
		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] !=0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						'0'                                   . ', ' .
						db_qstr($query['feature'])      . ', ' .
						db_qstr($query['user'])         . ', ' .
						'"N/A"'                               . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}

				if ($i >= 50) {
					$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 4:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_license_count) AS peak_ut,
			SUM(count)/(MAX(total_license_count)*$cycles_per_hour*24) AS utilization,
			total_license_count,feature,vendor,user,service_id
			FROM (
				SELECT SUM(count) AS count, MAX(total_license_count) AS total_license_count,
				feature, vendor, user, service_id
				FROM $peak_ut_tabname
				WHERE total_license_count > 0
				GROUP BY feature, vendor, user, service_id, interval_end
			) AS it
			GROUP BY feature, vendor, user, service_id";

		$i = 0;
		$querys  = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] !=0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						$query['service_id']                  . ', ' .
						db_qstr($query['feature'])      . ', ' .
						db_qstr($query['user'])         . ', ' .
						'"N/A"'                               . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}
				if ($i >= 50) {
					$sql = $sql_prefix. ' ' . $sql_insert.' '.$sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 5:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_count) AS peak_ut,
			SUM(count)/(total_count*$cycles_per_hour*24) AS utilization,
			total_count AS total_license_count, feature, vendor, host
			FROM (
				SELECT SUM(count) AS count, total_license_count,
				feature_max_licenses AS total_count,
				feature, vendor, host
				FROM $peak_ut_tabname AS a
				INNER JOIN (
					SELECT SUM(feature_max_licenses) AS feature_max_licenses, feature_name, vendor_daemon
					FROM $lic_use_tabname
					GROUP BY feature_name, vendor_daemon
				) AS b
				ON a.feature=b.feature_name AND a.vendor=b.vendor_daemon
				WHERE total_license_count > 0
				GROUP BY feature, vendor, host, interval_end
			) AS C
			GROUP BY feature, vendor, host";

		$i = 0;
		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] !=0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						'0'                                   . ', ' .
						db_qstr($query['feature'])      . ', ' .
						'"N/A"'                               . ', ' .
						db_qstr($query['host'])         . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}

				if ($i >= 50) {
					$sql = $sql_prefix. ' ' . $sql_insert.' '.$sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 6:
		$cal_query="SELECT SUM(count) AS count,
			MAX(count/total_license_count) AS peak_ut,
			SUM(count)/(MAX(total_license_count)*$cycles_per_hour*24) AS utilization,
			total_license_count, feature, vendor, host, service_id
			FROM (
				SELECT SUM(count) AS count, MAX(total_license_count) AS total_license_count,
				feature, vendor, host, service_id
				FROM $peak_ut_tabname
				WHERE total_license_count > 0
				GROUP BY feature, vendor, host, service_id, interval_end
			) AS it
			GROUP BY feature, vendor, host, service_id";

		$i = 0;
		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] !=0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						$query['service_id']                  . ', ' .
						db_qstr($query['feature'])      . ', ' .
						'"N/A"'                               . ', ' .
						db_qstr($query['host'])         . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}
				if ($i >= 50) {
					$sql = $sql_prefix. ' ' . $sql_insert.' '.$sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	case 7:
		$cal_query = "SELECT SUM(count) AS count,
			MAX(count/total_count) AS peak_ut,
			SUM(count)/(total_count*$cycles_per_hour*24) AS utilization,
			total_count AS total_license_count, feature, vendor, host, user
			FROM (
				SELECT SUM(count) AS count, total_license_count,
				feature_max_licenses AS total_count,
				feature, vendor, host, user
				FROM $peak_ut_tabname AS a
				INNER JOIN (
					SELECT SUM(feature_max_licenses) AS feature_max_licenses, feature_name, vendor_daemon
					FROM $lic_use_tabname
					GROUP BY feature_name, vendor_daemon
				) AS b
				ON a.feature=b.feature_name
				AND a.vendor=b.vendor_daemon
				WHERE total_license_count > 0
				GROUP BY feature, vendor, host, user, interval_end
			) AS C
			GROUP BY feature, vendor, host, user";

		$i = 0;

		$querys = db_fetch_assoc($cal_query);
		if (cacti_sizeof($querys)) {
			foreach ($querys as $query) {
				if ($query['utilization'] !=0) {
					$sql_insert .= ($i > 0 ? ', ':'')         . '('  .
						'0'                                   . ', ' .
						db_qstr($query['feature'])      . ', ' .
						db_qstr($query['user'])         . ', ' .
						db_qstr($query['host'])         . ', ' .
						'"INUSE"'                             . ', ' .
						lic_nf($query['count'])               . ', ' .
						lic_nf($query['total_license_count']) . ', ' .
						lic_nf($query['utilization'])         . ', ' .
						lic_nf($query['peak_ut'])             . ', ' .
						db_qstr($query['vendor'])       . ', ' .
						'0'                                   . ', ' .
						'0'                                   . ', ' .
						db_qstr($type)                  . ', ' .
						db_qstr($current_time)          . ', ' .
						db_qstr($current_time)          . ')';

					$i ++;
				}

				if ($i >= 50) {
					$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
					db_execute($sql);

					$sql_insert = '';
					$i = 0;
				}
			}
		}

		break;
	default:
		break;
	}

	lic_debug('Populating daily_stats table for type=' . $type . '.');

	if ($i > 0) {
		$sql = $sql_prefix . ' ' . $sql_insert . ' ' . $sql_suffix;
		db_execute($sql);
	}
}

function key_features() {
	?>
	<td>
		<input id='keyfeat' type='checkbox' <?php if (get_request_var('keyfeat') == 'true') { print 'checked'; }?> onClick='applyFilter()'>
	</td>
	<td>
		<label for='keyfeat'>Key Features</label>
	</td>
	<?php
}

function is_lsf_host($host) {
	static $hosts;

	if (!cacti_sizeof($hosts)) {
		$hosts = array_rekey(
			db_fetch_assoc('SELECT DISTINCT host
				FROM grid_hosts'),
			'host', 'host'
		);
	}

	if (!isset($hosts[$host])) {
		$lower_host = strtolower($host); //Fix 260444
		if (!isset($hosts[$lower_host])) {
			return false;
		}
	}

	return true;
}

function lic_format_seconds($time) {
	if ($time >=60) {
		$time=$time/60;
        	if ($time >= 60) {
			$time = $time / 60;

			if ($time >= 24) {
				$time = $time / 24;
				return round($time,1) . 'd';
			} else {
				return round($time,1) . 'h';
			}
		} else {
			return round($time,1) . 'm';
		}
	} else {
		return round($time,1) . 's';
	}
}

function lic_get_path_rtm_top() {
	$path_webroot = read_config_option('path_webroot', true);
	$pos          = strrpos($path_webroot, '/');
	$path_rtm_top = substr($path_webroot, 0, $pos);

	return $path_rtm_top;
}

function lic_trim_value(&$value) {
	$value = trim($value);
}

function explode_hosts_from_portatserver($server_portatservers) {
	$hosts   = array();
	$servers = explode(':', $server_portatservers);
	array_walk($servers, 'lic_trim_value');

	if (cacti_sizeof ($servers)) {
		foreach ($servers as $server) {
			if (empty($server)) continue;

			$port_host = explode('@', $server);
			array_walk($port_host, 'lic_trim_value');

			if (!empty($port_host[1])) {
				$hosts[] = $port_host[1];
			}
		}
	}

	return ($hosts);
}

function get_options_file_ssh($options_file, $server_portatservers) {
	$file_output = '';

	if (!file_exists($options_file)) {
		$hosts = explode_hosts_from_portatserver($server_portatservers);

		if (cacti_sizeof($hosts)) {
			foreach($hosts as $host) {
				$file_output = shell_exec("ssh -o ConnectTimeout=5 $host cat $options_file");
				if (strlen($file_output) == 0) {
					continue;
				} else {
					break;
				}
			}
		}
	}

	if (!empty($file_output)) {
		$file_output = preg_split('/\r\n|\n|\r/',$file_output);
	}

	return ($file_output);
}

function lic_get_level_name($level, $type = 'user') {
	return db_fetch_cell_prepared("SELECT DISPLAY_NAME
		FROM grid_metadata_conf
		WHERE db_column_name=?
		AND OBJECT_TYPE=?", array($level, $type));
}

function lic_level_filter() {
	global $cnn_id;

	$l1 = read_config_option('lic_level1');
	$l2 = read_config_option('lic_level2');
	$l3 = read_config_option('lic_level3');

	if ($l1 != '' || $l2 != '' | $l3 != '') {
		print '<table cellpadding="2" cellspacing="0"><tr>';

		if ($l1 != '') {
			print '<td style="width:60px;">' . lic_get_level_name($l1) . ':</td>';
			print '<td><select id="level1" onChange="applyFilter();">';
			print '<option value="0"' . (get_request_var('level1') == '0' ? ' selected':'') . '>All</option>';
			print '<option value="-1"' . (get_request_var('level1') == '-1' ? ' selected':'') . '>N/A</option>';

			$levels = db_fetch_assoc("SELECT DISTINCT $l1
				FROM grid_metadata AS gmd
				INNER JOIN lic_daily_stats_traffic AS ldst
				ON gmd.object_id=ldst.user
				WHERE gmd.object_type='user'
				AND $l1 != ''
				ORDER BY $l1 ASC");

			if (cacti_sizeof($levels)) {
				foreach($levels as $l) {
					print "<option value='" . htmlspecialchars($l[$l1]) . "'" . (get_request_var('level1') == $l[$l1] ? ' selected':'') . ">" . $l[$l1] . "</option>";
				}
			}

			print '</select></td>';
		} else {
			print '<td><input id="lic_level1" type="hidden" value="-1"></td>';
		}

		$sql_where_l1 = '';
		if (get_request_var('level1') != '') {
			if (get_request_var('level1') != '0' && get_request_var('level1') != '-1') {
				$sql_where_l1 = " AND $l1 =" . db_qstr(get_request_var('level1'));
			}
		}

		$sql_where_l2 = '';
		if (get_request_var('level2') != '') {
			if (get_request_var('level2') != '0' && get_request_var('level2') != '-1') {
				$sql_where_l1 = " AND $l2 =" . db_qstr(get_request_var('level2'));
			}
		}

		if ($l2 != '') {
			print '<td>' . lic_get_level_name($l2) . ':</td>';
			print '<td><select id="level2" onChange="applyFilter();">';
			print '<option value="0"' . (get_request_var('level2') == '0' ? ' selected':'') . '>All</option>';
			print '<option value="-1"' . (get_request_var('level2') == '-1' ? ' selected':'') . '>N/A</option>';

			$levels = db_fetch_assoc("SELECT DISTINCT $l2
				FROM grid_metadata AS gmd
				INNER JOIN lic_daily_stats_traffic AS ldst
				ON gmd.object_id=ldst.user
				WHERE gmd.object_type='user'
				AND $l2 != ''
				$sql_where_l1
				ORDER BY $l2 ASC");

			if (cacti_sizeof($levels)) {
				foreach($levels as $l) {
					print "<option value='" . htmlspecialchars($l[$l2]) . "'" . (get_request_var('level2') == $l[$l2] ? ' selected':'') . ">" . $l[$l2] . "</option>";
				}
			}

			print '</select></td>';
		} else {
			print '<td><input id="lic_level2" type="hidden" value="-1"></td>';
		}

		if ($l3 != '') {
			print '<td>' . lic_get_level_name($l3) . ':</td>';
			print '<td><select id="level3" onChange="applyFilter();">';
			print '<option value="0"' . (get_request_var('level3') == '0' ? ' selected':'') . '>All</option>';
			print '<option value="-1"' . (get_request_var('level3') == '-1' ? ' selected':'') . '>N/A</option>';

			$levels = db_fetch_assoc("SELECT DISTINCT $l3
				FROM grid_metadata AS gmd
				INNER JOIN lic_daily_stats_traffic AS ldst
				ON gmd.object_id=ldst.user
				WHERE gmd.object_type='user'
				AND $l3 != ''
				$sql_where_l1
				$sql_where_l2
				ORDER BY $l3 ASC");

			if (cacti_sizeof($levels)) {
				foreach($levels as $l) {
					print "<option value='" . htmlspecialchars($l[$l3]) . "'" . (get_request_var('level3') == $l[$l3] ? ' selected':'') . ">" . $l[$l3] . "</option>";
				}
			}

			print '</select></td>';
		} else {
			print '<td><input id="lic_level3" type="hidden" value="-1"></td>';
		}

		print '</tr></table>';
	}
}

/* lic_nav_bar - draws a navigation bar which includes previous/next links as well as current
     page information
   @arg $background_color - the background color of this navigation bar row
   @arg $colspan - the colspan for the entire row
   @arg $current_page - the current page in the navigation system
   @arg $rows_per_page - the number of rows that are displayed on a single page
   @arg $total_rows - the total number of rows in the navigation system
   @arg $nav_url - the url to use when presenting users with previous/next links.
   @arg $page_count - provide a page count */
function lic_nav_bar($background_color, $colspan, $current_page, $rows_per_page, $total_rows, $nav_url, $page_count = true) {
	if ($total_rows > 0 && $page_count) {

		if (strpos($nav_url, '?') === false) {
			$nav_url .= '?random=1';
		}

		$url_page_select = get_page_list($current_page, MAX_DISPLAY_PAGES, $rows_per_page, $total_rows, $nav_url);

		return "<tr bgcolor='#" . $background_color . "' class='noprint'>
			<td colspan='" . $colspan . "'>
				<table class='navBar' width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark' width='15%'>
							" . ($current_page > 1 ? "<strong><a class='linkOverDark' href='" . htmlspecialchars($nav_url . '&page=' . ($current_page-1)) . "'> &lt;&lt; Previous</a></strong>":'') . "
						</td>
						<td align='center' class='textHeaderDark' width='70%'>
							Showing Rows " . (($rows_per_page*($current_page-1))+1) . ' to ' . ((($total_rows < $rows_per_page) || ($total_rows < ($rows_per_page*$current_page))) ? $total_rows : ($rows_per_page*$current_page)) . ' of ' . $total_rows . ' [' . $url_page_select . "]
						</td>
						<td align='right' class='textHeaderDark' width='15%'>
							" . (($current_page * $rows_per_page) < $total_rows ? "<strong><a class='linkOverDark' href='" . htmlspecialchars($nav_url . '&page=' . ($current_page+1)) . "'> Next &gt;&gt; </a></strong>":'') . "
						</td>
					</tr>
				</table>
			</td>
		</tr>\n";
	} elseif ($total_rows > 0) {
		return "<tr bgcolor='#" . $background_color . "' class='noprint'>
			<td colspan='" . $colspan . "'>
				<table class='navBar' width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark' width='15%'>
							" . ($current_page > 1 ? "<strong><a class='linkOverDark' href='" . htmlspecialchars($nav_url . '&page=' . ($current_page-1)) . "'>&lt;&lt; Previous</a></strong>":'') . "
						</td>
						<td align='center' class='textHeaderDark' width='70%'>
							Current Page: " . $current_page . "
						</td>
						<td align='right' class='textHeaderDark' width='15%'>
							" . ($total_rows >= $rows_per_page ? "<strong><a class='linkOverDark' href='" . htmlspecialchars($nav_url . '&page=' . ($current_page+1)) . "'>Next &gt;&gt;</a></strong>":'') . "
						</td>
					</tr>
				</table>
			</td>
		</tr>\n";
	} else {
		return "<tr bgcolor='#" . $background_color . "' class='noprint'>
			<td colspan='" . $colspan . "'>
				<table width='100%' cellspacing='0' cellpadding='3' border='0'>
					<tr>
						<td align='center' class='textHeaderDark' width='70%'>
							No Rows Found
						</td>
					</tr>
				</table>
			</td>
		</tr>\n";
	}

	return $nav_url;
}

function lic_replay_license_denials() {
	echo "<span>Raw Denial to Effective Denial Recalculation Complete!</span><br>";
}

/* read_default_lic_config_option - finds the default value of a license configuration setting
   @arg $config_name - the name of the configuration setting as specified $settings array
     in 'include/config_settings.php'
   @returns - the default value of the configuration option */
function read_default_lic_config_option($config_name) {
	global $config, $grid_settings, $lic_settings;
	//TODO: combina grid_settings, grid_settings as rtm_settings for some RTM common options, e.g. RowsOfPage
    //      Except: default_main_screen

    if($config_name == 'default_main_screen')
		$tmp_settings = $lic_settings;
	else
		$tmp_settings = $grid_settings;

	reset($tmp_settings);

	foreach ($tmp_settings as $tab_name => $tab_array) {
		if ((isset($tab_array[$config_name])) && (isset($tab_array[$config_name]['default']))) {
			return $tab_array[$config_name]['default'];
		}else{
			foreach ($tab_array as $field_name => $field_array) {
				if ((isset($field_array['items'])) && (isset($field_array['items'][$config_name])) && (isset($field_array['items'][$config_name]['default']))) {
					return $field_array['items'][$config_name]['default'];
				}
			}
		}
	}
}

/* read_lic_config_option - finds the current value of a license configuration setting
   @arg $config_name - the name of the configuration setting as specified $grid_settings(TODO: $lic_settings) array
     in 'include/config_settings.php'
   @returns - the current value of the license configuration option */
function read_lic_config_option($config_name, $force = FALSE) {
	/* users must have cacti user auth turned on to use this */
	if ((read_config_option('auth_method') != 0) && (!isset($_SESSION['sess_user_id'])) || $config_name == 'default_main_screen') {
		return read_default_lic_config_option($config_name);
	}

	if ((isset($_SESSION['sess_lic_config_array']) && (!$force))) {
		$lic_config_array = $_SESSION['sess_lic_config_array'];
	}

	if (!isset($lic_config_array[$config_name])) {
		$db_setting = db_fetch_row_prepared('SELECT value
			FROM grid_settings
			WHERE name=?
			AND user_id=?', array($config_name, $_SESSION['sess_user_id']));

		if (isset($db_setting['value'])) {
			$lic_config_array[$config_name] = $db_setting['value'];
		}else{
			$lic_config_array[$config_name] = read_default_lic_config_option($config_name);
		}

		$_SESSION['sess_lic_config_array'] = $lic_config_array;
	}
	//cacti_log("DEBUG: lic_config_array[$config_name]: '" . $lic_config_array[$config_name] . "'");
	return $lic_config_array[$config_name];
}

function set_lic_config_option($config_name, $value, $user = -1) {
	if ($user == -1 && isset($_SESSION['sess_user_id'])) {
		$user = $_SESSION['sess_user_id'];
	}

	if ($user == -1) {
		cacti_log('Attempt to set user setting \'' . $config_name . '\', with no user id: ' . cacti_debug_backtrace('', false, false, 0, 1), false, 'WARNING:');
	} elseif (db_table_exists('grid_settings')) {
		db_execute_prepared('REPLACE INTO grid_settings
			(user_id, name, value)
			VALUES (?, ?, ?)',
			array($user, $config_name, $value));

		if (isset($_SESSION)){
			if(!isset($_SESSION['sess_lic_config_array'])){
				$_SESSION['sess_lic_config_array'] = array();
			}
			$_SESSION['sess_lic_config_array'][$config_name] = $value;
		}
	}
}

/* lic_config_value_exists - determines if a value exists for the current user/setting specified
   @arg $config_name - the name of the configuration setting as specified $grid_settings array
     in 'include/config_settings.php'
   @arg $user_id - the id of the user to check the configuration value for
   @returns (bool) - true if a value exists, false if a value does not exist */
function lic_config_value_exists($config_name, $user_id) {
	return cacti_sizeof(db_fetch_assoc_prepared('SELECT value
		FROM grid_settings
		WHERE name=?
		AND user_id=?', array($config_name, $user_id)));
}

/* Page filter settings, copy from  grid.get_views_setting */
function get_lic_user_setting($page_name, $setting, $default, $force = FALSe) {
	$settings = read_lic_config_option($page_name, $force);

	if ($settings != '') {
		$setarray = explode('|', $settings);

		if (cacti_sizeof($setarray)) {
			foreach ($setarray as $s) {
				$iset   = explode('=', $s);
				$pname  = trim($iset[0]);
				$pvalue = trim($iset[1]);

				if ($pname == $setting) {
					return $pvalue;
				}
			}
		}
	}

	return $default;
}

function lic_set_time_span_values(&$current, &$earlier, $for_filter=FALSE) {

	switch(get_request_var('timestamp')){
		case '-1'://daily
			lic_get_timespan('days', 1, $current, $earlier, $for_filter);

			break;
		case '-2': // last 3 days
			lic_get_timespan('days', 3, $current, $earlier, $for_filter);

			break;
		case '-3': // last 5 days
			lic_get_timespan('days', 5, $current, $earlier, $for_filter);

			break;
		case '-4': // last week
			lic_get_timespan('days', 7, $current, $earlier, $for_filter);

			break;
		case '-5': // last 2 weeks
			lic_get_timespan('days', 14, $current, $earlier, $for_filter);

			break;
		case '-6':// last 3 weeks
			lic_get_timespan('days', 21, $current, $earlier, $for_filter);

			break;
		case '-7': // last 4 weeks
			lic_get_timespan('days', 28, $current, $earlier, $for_filter);

			break;
		case '-8': //last 5 weeks
			lic_get_timespan('days', 35, $current, $earlier, $for_filter);

			break;
		case '-9': //last 10 weeks
			lic_get_timespan('days', 70, $current, $earlier, $for_filter);

			break;
		case '-10': // last 6 months - use 183 days to represent half a year
			lic_get_timespan('days', 183, $current, $earlier, $for_filter);

			break;
		case '-11':// last year
			lic_get_timespan('years', 1, $current, $earlier, $for_filter);

			break;
	}
}

function lic_get_timespan($precision, $value, &$current, &$earlier, $for_filter=FALSE){
	$current = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'));
	$current = date('Y-m-d H:i:s', $current);

	switch($precision){
		case 'seconds':
			$earlier = mktime(date('H'), date('i'), date('s')-$value, date('n'), date('j'), date('Y'));
			break;
		case 'mins':
			$earlier = mktime(date('H'), date('i')-$value, date('s'), date('n'), date('j'), date('Y'));
			break;
		case 'hours':
			$earlier = mktime(date('H')-$value, date('i'), date('s'), date('n'), date('j'), date('Y'));
			break;
		case 'days':
			$earlier = mktime(date('H'), date('i'), date('s'), date('n'), date('j')-$value, date('Y'));
			break;
		case 'months':
			$earlier = mktime(date('H'), date('i'), date('s'), date('n')-$value, date('j'), date('Y'));
			break;
		case 'years':
			$earlier = mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y')-$value);
			break;
	}

	/* for filter values, we need to take an additional day earlier because daily stats are
	 * calculated for the day before.  The traffic table timestamp will be from day before.
	 */
	if($for_filter == TRUE) {
		$earlier -=86400;
	}

	$earlier = date('Y-m-d H:i:s', $earlier);
}

function get_lic_managers() {
	return db_fetch_assoc('SELECT DISTINCT lm.id, lm.name FROM lic_managers AS lm INNER JOIN lic_pollers AS lp ON lm.id=lp.poller_type ORDER BY name');
}

function lic_cal_utilization($feature, $user, $host, $vendor, $portserver_id){

    lic_set_time_span_values($current, $earlier);

    $group_by = 'group by service_id, feature, user, host, vendor';
    $sql_where2 = "WHERE action='INUSE' AND feature='$feature'
            AND user='$user' AND host='$host' AND service_id= $portserver_id
            AND vendor='$vendor' AND date_recorded BETWEEN '$earlier' AND '$current'";

    if (read_config_option("grid_partitioning_enable") == "") {
        $partition_query = "lic_daily_stats";
    }
    else {
        $partition_query = make_partition_query("lic_daily_stats", $earlier, $current, $sql_where2);
    }

    $query = "SELECT avg(utilization) as utilization FROM $partition_query $sql_where2 $group_by";
    $result = db_fetch_row($query);

    if (empty($result))
        return '--';

    if ($result['utilization']){
		$util100 = $result['utilization']*100;
		if($util100 > 100) $util100 = 100;
		return number_format($util100, 2);
	} else {
		return '--';
	}
}
