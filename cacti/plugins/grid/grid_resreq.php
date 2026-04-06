<?php
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

chdir('../../');
#include('./include/auth.php');
include('./include/global.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_messages.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

if (file_exists($config['base_path'] . '/plugins/lsfenh/lib/analytics.php')) {
	include_once($config['base_path'] . '/plugins/lsfenh/lib/analytics.php');
}

$title = __('IBM Spectrum LSF RTM - Resource Analytics', 'grid');

set_default_action();

switch(get_request_var('action')) {
	case 'defaultlayout':
		break;
	case 'savelayout':
		print save_resreq_layout();
		break;
	case 'deletelayout':
		print delete_resreq_layout();
		break;
	case 'changelayout':
		break;
	case 'getfilter':
		break;
	case 'getdetails':
		$data = base64_decode(get_request_var('data'));

		$details = json_decode($data, true);
		$bucket_id = $details['bucket_id'];
		$clusterid = $details['clusterid'];
		$user      = $details['user'];

		grid_get_details($bucket_id, $clusterid, $user);

		break;
	default:
		if (isset_request_var('layout')) {
			load_resreq_variables();
		}

		validate_request_vars();

		grid_view_resreq();
}

function load_resreq_variables() {
	$layout = db_fetch_row_prepared('SELECT *
		FROM grid_page_layouts
		WHERE id = ?',
		array(get_request_var('layout')));

	if (cacti_sizeof($layout)) {
		$variables = json_decode($layout['data'], true);

		foreach($variables as $name => $value) {
			set_request_var($name, $value);
		}
	}
}

function delete_resreq_layout() {
	db_execute_prepared('DELETE FROM grid_page_layouts
		WHERE id = ?
		AND user_id = ?',
		array(get_request_var('layout'), $_SESSION['sess_user_id']));

	return json_encode(array('layout' => get_request_var('layout')));
}

function save_resreq_layout() {
	validate_request_vars();

	$data = array();
	$save = array();

	$save['id'] = 0;

	// Add all the filter values
	foreach($_REQUEST as $index => $value) {
		switch($index) {
			case 'layout':
				if ($value > 0) {
					$save['id'] = $value;
				}
				break;
			case 'template':
			case '__csrf_magic':
			case 'template':
			case 'action':
				break;
			default:
				$data[$index] = $value;
		}
	}

	// Add the sort information to the save
	$data['sort_direction'] = get_request_var('sort_direction');
	$data['sort_column']    = get_request_var('sort_column');

	$save['pagename'] = basename(get_current_page());

	if ($save['id'] == 0) {
		$save['name'] = get_request_var('name');
		$save['default']  = '';
	} else {
		$save['name'] = get_request_var('name');
	}

	$save['data']     = json_encode($data);
	$save['user_id']  = $_SESSION['sess_user_id'];

	$layout_id = sql_save($save, 'grid_page_layouts');

	if ($layout_id > 0) {
		$message = __('Page Layout Saved', 'grid');
	} else {
		$message = __('Page Layout Save Failed', 'grid');
	}

	return json_encode(array('layout' => $layout_id, 'name' => $save['name'], 'message' => $message));
}

function grid_get_records(&$sql_where, $apply_limits = true, $rows, &$sql_params) {
	global $stats;

	$start = microtime(true);

	/* user id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= 'WHERE grr.clusterid = ?';

		$sql_params[] = get_request_var('clusterid');
	}

	/* last updated filter */
	if (get_request_var('last') == '-1') {
		$max_value = db_fetch_cell_prepared("SELECT MAX(last_updated) FROM grid_jobs_resreq_analysis " . str_replace('grr.', '', $sql_where), $sql_params);

		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' last_updated > DATE_SUB(CURDATE(), INTERVAL 10 MINUTE)';
	} elseif (get_request_var('last') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' last_updated = ?';

		$sql_params[] = get_request_var('last');
	}

	/* filter sql where */
	if (get_request_var('ffilter') != '') {
		$parts = explode(' ', get_request_var('ffilter'));

		foreach($parts as $index => $part) {
			if ($index == 0) {
				$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') .
					"((res_select_pend LIKE ? OR res_rusage LIKE ? OR unique_users LIKE ? OR unique_projects LIKE ? OR jobids LIKE ?)";
			} else {
				$sql_where .= ' OR (res_select_pend LIKE ? OR res_rusage LIKE ? OR unique_users LIKE ? OR unique_projects LIKE ? OR jobids LIKE ?)';
			}

			$sql_params[] = '%'. $part . '%';
			$sql_params[] = '%'. $part . '%';
			$sql_params[] = '%'. $part . '%';
			$sql_params[] = '%'. $part . '%';
			$sql_params[] = '%'. $part . '%';
		}

		$sql_where .= ')';
	}

	if (get_request_var('queue') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' queue = ?';

		$sql_params[] = get_request_var('queue');
	}

	if (get_request_var('job_user') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' unique_users LIKE ?';

		$sql_params[] = '%' . get_request_var('job_user') . '%';
	}

	if (get_request_var('sla') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' sla = ?';

		$sql_params[] = get_request_var('sla');
	}

	if (get_request_var('reqcpus') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' num_cpus = ?';

		$sql_params[] = get_request_var('reqcpus');
	}

	// Get the stats
	$stat_param = array_merge($sql_params, $sql_params, $sql_params, $sql_params, $sql_params);
	$stat_where = str_replace('grr.', '', $sql_where);

	$awhere = $stat_where . ($stat_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
	$dwhere = $stat_where . ($stat_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
	$swhere = $stat_where . ($stat_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
	$rwhere = $stat_where . ($stat_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
	$nwhere = $stat_where . ($stat_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';

	$stats = db_fetch_assoc_prepared("
		SELECT 'accel' AS stat, SUM(pendJobs) AS pendJobs, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots FROM grid_jobs_resreq_analysis $awhere
		UNION
		SELECT 'decel' AS stat, SUM(pendJobs) AS pendJobs, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots FROM grid_jobs_resreq_analysis $dwhere
		UNION
		SELECT 'stalled' AS stat, SUM(pendJobs) AS pendJobs, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots FROM grid_jobs_resreq_analysis $swhere
		UNION
		SELECT 'drain' AS stat, SUM(pendJobs) AS pendJobs, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots FROM grid_jobs_resreq_analysis $rwhere
		UNION
		SELECT 'ntput' AS stat, SUM(pendJobs) AS pendJobs, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots FROM grid_jobs_resreq_analysis $nwhere",
		$stat_param);

	if (get_request_var('status') == 0) { // All
		// Do nothing
	} elseif (get_request_var('status') == 1) { // Accelerating
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
	} elseif (get_request_var('status') == 2) { // Decelerating
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
	} elseif (get_request_var('status') == 3) { // Stalled
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
	} elseif (get_request_var('status') == 4) { // Draining
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
	} elseif (get_request_var('status') == 5) { // No Throughput
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';
	} elseif (get_request_var('status') == 6) { // Aging/Aged
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(runJobs = 0 AND pendJobs = 0)';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT grr.*, IF(pendJobs > 0 AND pendTime > 0, pendTime / pendJobs, 0) AS avgPendTime, gc.clustername
		FROM grid_jobs_resreq_analysis AS grr
		INNER JOIN grid_clusters AS gc
		ON grr.clusterid = gc.clusterid
		$sql_where
		$sql_order";

	$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$results = db_fetch_assoc_prepared($sql_query, $sql_params);

	$end = microtime(true);

	//cacti_log(sprintf('Total Time %1.2f', $end - $start));

	return $results;
}

function filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	/* last updated filter */
	if (get_request_var('last') == '-1') {
		if (get_request_var('clusterid') > 0) {
			$min_date = db_fetch_cell_prepared('SELECT MAX(last_updated) FROM grid_jobs_resreq_analysis WHERE clusterid = ?', array(get_request_var('clusterid')));
		} else {
			$min_date = db_fetch_cell('SELECT MIN(last_updated) FROM grid_jobs_resreq_analysis WHERE last_updated > DATE_SUB(CURDATE(), INTERVAL 10 MINUTE)');
		}
	} elseif (get_request_var('last') != '0') {
		$min_date = get_request_var('last');
	}

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_resreq.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Layout', 'grid');?>
					</td>
					<td>
						<select id='layout'>
							<option value='0'<?php if (get_request_var('layout') == '0') {?> selected<?php }?>><?php print __('Unnamed', 'grid');?></option>
							<?php
							$layouts = db_fetch_assoc_prepared('SELECT *
								FROM grid_page_layouts AS lo
								WHERE user_id = ?
								AND pagename = ?
								ORDER BY name',
								array($_SESSION['sess_user_id'], basename(get_current_page())));

							if (cacti_sizeof($layouts)) {
								foreach ($layouts as $lo) {
									print '<option value="' . $lo['id'] . '"' . (get_request_var('layout') == $lo['id'] ? ' selected':'') . '>' . html_escape($lo['name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = db_fetch_assoc_prepared('SELECT gc.clusterid, gc.clustername
								FROM grid_clusters AS gc
								WHERE disabled = ""
								AND clusterid IN (
									SELECT DISTINCT clusterid
									FROM grid_jobs_resreq_analysis
									WHERE last_updated >= ?)
								ORDER BY clustername',
								array($min_date));

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] . '"' . (get_request_var('clusterid') == $cluster['clusterid'] ? ' selected':'') . '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'grid');?>
					</td>
					<td>
						<select id='queue'>
							<option value='-1'<?php if (get_request_var('queue') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$sql_where = '';
							$sql_param = array();

							if (get_request_var('clusterid') > 0) {
								$sql_where   = 'WHERE clusterid = ?';
								$sql_param[] = get_request_var('clusterid');
							}

							if (get_request_var('sla') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' sla = ?';
								$sql_param[] = get_request_var('sla');
							}

							if (get_request_var('reqcpus') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' num_cpus = ?';
								$sql_param[] = get_request_var('reqcpus');
							}

							if (get_request_var('status') == 0) { // All
								// Do nothing
							} elseif (get_request_var('status') == 1) { // Accelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
							} elseif (get_request_var('status') == 2) { // Decelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
							} elseif (get_request_var('status') == 3) { // Stalled
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
							} elseif (get_request_var('status') == 4) { // Draining
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
							} elseif (get_request_var('status') == 5) { // No Throughput
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';
							} elseif (get_request_var('status') == 6) { // Aging/Aged
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(runJobs = 0 AND pendJobs = 0)';
							}

							$queues = array_rekey(
								db_fetch_assoc_prepared("SELECT DISTINCT queue
									FROM grid_jobs_resreq_analysis
									$sql_where
									ORDER BY queue",
									$sql_param),
								'queue', 'queue'
							);

							if (cacti_sizeof($queues)) {
								foreach ($queues as $queue) {
									print '<option value="' . html_escape($queue) . '"' . (get_request_var('queue') == $queue ? ' selected':'') . '>' . html_escape($queue) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('User', 'grid');?>
					</td>
					<td>
						<select id='job_user'>
							<option value='-1'<?php if (get_request_var('job_user') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$sql_where = '';
							$sql_param = array();

							if (get_request_var('clusterid') > 0) {
								$sql_where   = 'WHERE clusterid = ?';
								$sql_param[] = get_request_var('clusterid');
							}

							if (get_request_var('sla') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' sla = ?';
								$sql_param[] = get_request_var('sla');
							}

							if (get_request_var('reqcpus') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' num_cpus = ?';
								$sql_param[] = get_request_var('reqcpus');
							}

							if (get_request_var('status') == 0) { // All
								// Do nothing
							} elseif (get_request_var('status') == 1) { // Accelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
							} elseif (get_request_var('status') == 2) { // Decelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
							} elseif (get_request_var('status') == 3) { // Stalled
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
							} elseif (get_request_var('status') == 4) { // Draining
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
							} elseif (get_request_var('status') == 5) { // No Throughput
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';
							} elseif (get_request_var('status') == 6) { // Aging/Aged
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(runJobs = 0 AND pendJobs = 0)';
							}

							$grouped_users = array_rekey(
								db_fetch_assoc_prepared("SELECT DISTINCT unique_users
									FROM grid_jobs_resreq_analysis
									$sql_where",
									$sql_param),
								'unique_users', 'unique_users'
							);

							$users = array();

							if (cacti_sizeof($grouped_users)) {
								foreach ($grouped_users as $u) {
									$uu = explode(',', $u);
									foreach($uu as $u) {
										$u = trim($u);
										$users[$u] = $u;
									}
								}

								asort($users);

								foreach ($users as $user) {
									print '<option value="' . html_escape($user) . '"' . (get_request_var('job_user') == $user ? ' selected':'') . '>' . html_escape($user) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('SLA', 'grid');?>
					</td>
					<td>
						<select id='sla'>
							<option value='-1'<?php if (get_request_var('sla') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<option value=''<?php if (get_request_var('sla') == '') {?> selected<?php }?>><?php print __('N/A', 'grid');?></option>
							<?php
							$sql_where = '';
							$sql_param = array();
							if (get_request_var('clusterid') > 0) {
								$sql_where = 'WHERE clusterid = ?';
								$sql_param[] = get_request_var('clusterid');
							}

							if (get_request_var('queue') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' queue = ?';
								$sql_param[] = get_request_var('queue');
							}

							if (get_request_var('reqcpus') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' num_cpus = ?';
								$sql_param[] = get_request_var('reqcpus');
							}

							if (get_request_var('status') == 0) { // All
								// Do nothing
							} elseif (get_request_var('status') == 1) { // Accelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
							} elseif (get_request_var('status') == 2) { // Decelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
							} elseif (get_request_var('status') == 3) { // Stalled
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
							} elseif (get_request_var('status') == 4) { // Draining
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
							} elseif (get_request_var('status') == 5) { // No Throughput
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';
							} elseif (get_request_var('status') == 6) { // Aging/Aged
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(runJobs = 0 AND pendJobs = 0)';
							}

							$slas = array_rekey(
								db_fetch_assoc_prepared("SELECT DISTINCT sla
									FROM grid_jobs_resreq_analysis
									$sql_where
									ORDER BY sla",
									$sql_param),
								'sla', 'sla'
							);

							if (cacti_sizeof($slas)) {
								foreach ($slas as $sla) {
									print '<option value="' . html_escape($sla) . '"' . (get_request_var('sla') == $sla ? ' selected':'') . '>' . html_escape($sla) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('CPUs', 'grid');?>
					</td>
					<td>
						<select id='reqcpus'>
							<?php
							print '<option value="-1"' . (get_request_var('reqcpus') == '-1' ? ' selected':'') . '>' . __('All', 'grid') . '</option>';

							$sql_where = '';
							$sql_param = array();
							if (get_request_var('clusterid') > 0) {
								$sql_where = 'WHERE clusterid = ?';
								$sql_param[] = get_request_var('clusterid');
							}

							if (get_request_var('queue') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' queue = ?';
								$sql_param[] = get_request_var('queue');
							}

							if (get_request_var('sla') != '-1') {
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' sla = ?';
								$sql_param[] = get_request_var('sla');
							}

							if (get_request_var('status') == 0) { // All
								// Do nothing
							} elseif (get_request_var('status') == 1) { // Accelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) > round(tputHOUR)';
							} elseif (get_request_var('status') == 2) { // Decelerating
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'runJobs > 0 AND round(tput5MIN*12) < round(tputHOUR)';
							} elseif (get_request_var('status') == 3) { // Stalled
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND pendJobs > 0 AND pendTime/pendJobs > 300 AND runJobs = 0)';
							} elseif (get_request_var('status') == 4) { // Draining
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(pendJobs = 0 AND runJobs > 0)';
							} elseif (get_request_var('status') == 5) { // No Throughput
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(tput5MIN = 0 AND tputHOUR = 0 AND runJobs > 0 AND pendJobs > 0)';
							} elseif (get_request_var('status') == 6) { // Aging/Aged
								$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . '(runJobs = 0 AND pendJobs = 0)';
							}

							$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' last_updated >= ?';
							$sql_param[] = $min_date;

							$cpus = db_fetch_assoc_prepared("SELECT DISTINCT num_cpus
								FROM grid_jobs_resreq_analysis
								$sql_where
								ORDER BY num_cpus",
								$sql_param);

							if (cacti_sizeof($cpus)) {
								foreach ($cpus as $cpu) {
									print '<option value="' . $cpu['num_cpus'] .'"' . (get_request_var('reqcpus') == $cpu['num_cpus'] ? ' selected':'') . '>' . html_escape($cpu['num_cpus']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<?php
							print generateButton('go', __('Go', 'grid'), __('Refresh statistics', 'grid'), true);
							print generateButton('clear', __('Clear', 'grid'));
							print generateButton('save', __('New', 'grid'));
							print generateButton('update', __('Save', 'grid'));
							print generateButton('rename', __('Rename', 'grid'));
							print generateButton('delete', __('Delete', 'grid'));
							print generateButton('default', __('Default', 'grid'));
							?>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Status', 'grid');?>
					</td>
					<td>
						<select id='status'>
							<option value='0'<?php if (get_request_var('status') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<option value='1'<?php if (get_request_var('status') == '1') {?> selected<?php }?>><?php print __('Accelerating', 'grid');?></option>
							<option value='2'<?php if (get_request_var('status') == '2') {?> selected<?php }?>><?php print __('Decelerating', 'grid');?></option>
							<option value='3'<?php if (get_request_var('status') == '3') {?> selected<?php }?>><?php print __('Stalled', 'grid');?></option>
							<option value='4'<?php if (get_request_var('status') == '4') {?> selected<?php }?>><?php print __('Draining', 'grid');?></option>
							<option value='5'<?php if (get_request_var('status') == '5') {?> selected<?php }?>><?php print __('No Throughput', 'grid');?></option>
							<option value='6'<?php if (get_request_var('status') == '6') {?> selected<?php }?>><?php print __('Aging/Aged', 'grid');?></option>
						</select>
					</td>
					<td>
						<?php print __('Last Used', 'grid');?>
					</td>
					<td>
						<select id='last'>
							<option value='-1'<?php if (get_request_var('last') == '-1') {?> selected<?php }?>><?php print __('Current', 'grid');?></option>
							<option value='0'<?php if (get_request_var('last') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$rows = array_rekey(
								db_fetch_assoc('SELECT DISTINCT last_updated
									FROM grid_jobs_resreq_analysis
									ORDER BY last_updated DESC
									LIMIT 20'),
								'last_updated', 'last_updated'
							);

							if (cacti_sizeof($rows)) {
								foreach ($rows as $key => $value) {
									print '<option value="' . $key . '"' . (get_request_var('last') == $key ? ' selected':'') . '>' . substr($value,0, -3) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Reasons', 'grid');?>
					</td>
					<td>
						<select id='reasons'>
							<?php
							$records = array(
								'5'  => __('Top %d', 5,  'grid'),
								'6'  => __('Top %d', 6,  'grid'),
								'7'  => __('Top %d', 7,  'grid'),
								'8'  => __('Top %d', 8,  'grid'),
								'9'  => __('Top %d', 9,  'grid'),
								'10' => __('Top %d', 10, 'grid'),
								'11' => __('Top %d', 11, 'grid'),
								'12' => __('Top %d', 12, 'grid'),
								'13' => __('Top %d', 13, 'grid'),
								'14' => __('Top %d', 14, 'grid'),
								'15' => __('Top %d', 15, 'grid'),
								'16' => __('Top %d', 16, 'grid'),
								'17' => __('Top %d', 17, 'grid'),
								'18' => __('Top %d', 18, 'grid'),
								'19' => __('Top %d', 19, 'grid'),
								'20' => __('Top %d', 20, 'grid')
							);

							if (cacti_sizeof($records)) {
								foreach ($records as $key => $value) {
									print '<option value="' . $key . '"' . (get_request_var('reasons') == $key ? ' selected':'') . '>' . $value . '</option>';
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
									print '<option value="' . $key . '"' . (get_request_var('rows') == $key ? ' selected':'') . '>' . $value . '</option>';
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
						<input type='text' id='ffilter' size='55' value='<?php print html_escape_request_var('ffilter');?>' title='<?php print __esc('Enter space delimited Resource Requirements, Users, or Project Names', 'grid');?>' placeholder='<?php print __esc('Enter Resource Requirements, Users, or Project Names', 'grid');?>'>
					</td>
					<td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}

function generateButton($id, $name, $title = '', $submit = false) {
	return '<td><input id="' . $id . '" name="' . $id . '" type="' . ($submit ? 'submit':'button') . '" value="' . html_escape($name) . '"'  . ($title != '' ? 'title="' . html_escape($title) . '"':'') . '></td>';
}

function generateTextBox($id, $name, $value, $size = 80) {
	return '<td>' . $name . '</td><td><input class="ui-state-default ui-corner-all" id="' . $id . '" name="' . $id . '" type="textbox" value="' . html_escape($value) . '" size="' . $size . '"></td>';
}

function generateCheckbox($id, $name, $value) {
	return '<td><input class="ui-state-default ui-corner-all" id="' . $id . '" type="checkbox" ' . ($value == 'on' || $value == 'true' ? ' checked="checked"':'') . '></td><td><label for="' . $id . '">' . $name . '</label></td>';
}

function generateSelect($id, $label, $current, $list, $change = '', $multiple = false) {
	if ($change != '') {
		$change = " onChange='$change'";
	}

	if ($multiple && $current != '') {
		$selected = explode(',', $current);
	} else {
		$selected = array();
	}

	$output = '';

	$output .= "<td><label for='$id'>$label</label></td><td><select name='$id' id='$id'$change" . ($multiple ? ' multiple size="1" style="line-height:1px;height:1px;z-index:-1;width:200px;overflow:scroll;border:medium none;border-color:transparent;background-color:transparent;opacity:0.0"':'') . '>';

	if (sizeof($list)) {
		foreach($list as $key => $value) {
			if ($multiple === false) {
				$output .= "<option value='" . html_escape($key) . "' " . ($key == $current ? ' selected':'') . '>' . html_escape($value) . '</option>';
			} else {
				$output .= "<option value='" . html_escape($key) . "' " . (array_search($key, $selected) !== false ? ' selected':'') . '>' . html_escape($value) . '</option>';
			}
		}
	}

	$output .= '</select></td>';

	return $output;
}

function generateClusterSelect($id, $label, $current, $change, $all_clusters = false, $multiselect = false) {
	$sql = '';

	if ($all_clusters) {
		$clusters[0] = __('All Clusters', 'grid');
	}

	$sql .= "SELECT DISTINCT clusterid, clustername FROM grid_clusters WHERE disabled = '' AND lsf_version >= 10 ORDER BY clustername";

	$nclusters = array_rekey(
		db_fetch_assoc($sql),
		'clusterid', 'clustername'
	);
	asort($nclusters);

	$clusters += $nclusters;

	return generateSelect($id, $label, $current, $clusters, $change, $multiselect);
}

function validate_request_vars() {
    /* ================= input validation and session storage ================= */
    $filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'reasons' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '5'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '0'
		),
		'layout' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '0'
		),
		'sla' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'reqcpus' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'last' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'ffilter' => array(
			'filter' => FILTER_DEFAULT,
			'pageset' => true,
			'default' => '',
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'avgPendTime',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

	validate_store_request_vars($filters, 'sess_gresreq');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
		)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function grid_get_details($bucket_id, $bucket_clusterid, $bucket_user) {
	global $config;

	$cluster = db_fetch_row_prepared('SELECT *
		FROM grid_clusters
		WHERE clusterid = ?',
		array($bucket_clusterid));

	$bucket = db_fetch_row_prepared('SELECT *
		FROM grid_jobs_resreq_analysis
		WHERE id = ?',
		array($bucket_id));

	if (!cacti_sizeof($cluster)) {
		print "Unable to find Cluster Record";
		exit;
	}

	if (function_exists('set_lsf_environment')) {
		if (!set_lsf_environment($cluster)) {
			print "Unable to determine the LSF_SERVERIDR";
			exit;
		}

		$lsf_bindir = $config['base_path'] . '/plugins/lsfenh/';
	} else {
		$lsf_bindir = '';
	}

	if (cacti_sizeof($bucket)) {
		// Capture the standard output to a buffer
		ob_start();

		$output = '';

		$output .= '<tr><td><table class="cactiTable">
			<thead>
				<tr class="tableHeader">
					<th>' . __('Top 30 Pending Jobs', 'grid') . '</th>
				</tr>
			</thead>
			<tbody>';

		if ($bucket['jobids'] != '') {
			$clusterid = $bucket['clusterid'];
			$jobs      = explode(',', $bucket['jobids']);
			$found     = false;
			$count     = 0;

			foreach($jobs as $jobid) {
				$parts = explode('[', $jobid);

				$jobid   = $parts[0];
				$indexid = (isset($parts[1]) ? trim($parts[1],'] '):0);

				$submit_time = db_fetch_cell_prepared('SELECT UNIX_TIMESTAMP(submit_time)
					FROM grid_jobs
					WHERE clusterid = ?
					AND jobid = ?
					AND indexid = ?',
					array($clusterid, $jobid, $indexid));

				if ($submit_time > 0) {
					if (!$found) {
						$output .= '<tr class="tableRow"><td>';
					}

					$href = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
						'?action=viewjob' .
						'&reset=true'     .
						'&cluster_tz='    .
						'&clusterid='     . $clusterid .
						'&indexid='       . $indexid   .
						'&jobid='         . $jobid     .
						'&submit_time='   . $submit_time;

					$output .= ($found ? ', ':'') . "<a class='pic hyperLink' href='$href'>$jobid" . ($indexid > 0 ? "[$indexid]":'') . '</a>';

					$found = true;
					$count++;

					if ($count >= 30) {
						break;
					}
				}
			}

			if ($found) {
				$output .= '</td></tr></tbody></table>';
			}
		} else {
			$output .= '<tr><td colspan="2">' . __('No Pending Jobs', 'grid') . '</td></tr></tbody></table>';
		}

		if ($bucket['unique_projects'] != '') {
			$output .= '<table class="cactiTable">
				<thead>
					<tr class="tableHeader">
						<th>' . __('Project Tags', 'grid')    . '</th>
					</tr>
				</thead>
				<tbody>';

			$projects = explode(',', $bucket['unique_projects']);
			asort($projects);
			$projects = implode(', ', $projects);

			$output .= '<tr class="tableRow">
					<td style="vertical-align:text-top">' . html_escape($projects) . '</td>
				</tr>';

			$output .= '</table>';
		}

		if ($bucket['unique_users'] != '') {
			$output .= '<table class="cactiTable">
				<thead>
					<tr class="tableHeader">
						<th>' . __('Users (Top 10 Pending)', 'grid')           . '</th>
						<th class="right">' . __('Group Name', 'grid')         . '</th>
						<th class="right">' . __('Shares', 'grid')             . '</th>
						<th class="right">' . __('Priority', 'grid')           . '</th>
						<th class="right">' . __('Max Relative Share', 'grid') . '</th>
						<th class="right">' . __('Max Slot Share', 'grid')     . '</th>
					</tr>
				</thead>
				<tbody>';

			$max_seen = db_fetch_row_prepared('SELECT user_or_group, MAX(relative_share) AS relative_share, 
				MAX(gqs.shares) AS shares, MAX(priority) AS priority, MAX(slot_share) AS slotShare
				FROM grid_queues_shares
				WHERE clusterid = ?
				AND queue = ?
				GROUP BY user_or_group
				ORDER BY relative_share DESC
				LIMIT 1',
				array($bucket['clusterid'], $bucket['queue']));

			if (cacti_sizeof($max_seen)) {
				$output .= '<tr class="tableRow" style="font-weight:bold;">
					<td style="vertical-align:text-top">' . __('Highest User Group', 'grid')                            . '</td>
					<td class="right" style="vertical-align:text-top">' . html_escape($max_seen['user_or_group'])       . '</td>
					<td class="right" style="vertical-align:text-top">' . $max_seen['shares'] ?? 0                      . '</td>
					<td class="right" style="vertical-align:text-top">' . $max_seen['priority'] ?? 0                    . '</td>
					<td class="right" style="vertical-align:text-top">' . round($max_seen['relative_share'] ?? 0, 2)    . '</td>
					<td class="right" style="vertical-align:text-top">' . number_format_i18n($max_seen['slotShare'], 0) . '</td>
				</tr>';

				//$output .= '<tr><td colspan="4"><hr></td></tr>';
			}

			$users = explode(',', $bucket['unique_users']);
			asort($users);

			$count = 0;

			foreach($users as $user) {
				$max_shares = db_fetch_row_prepared('SELECT user_or_group, MAX(relative_share) AS relative_share,
					MAX(gqs.shares) AS shares, MAX(priority) AS priority, MAX(slot_share) AS slotShare
					FROM grid_user_group_members AS gugm
					INNER JOIN grid_queues_shares AS gqs
					ON gugm.clusterid = gqs.clusterid
					AND (gugm.groupname = user_or_group OR gugm.username = user_or_group)
					WHERE gqs.clusterid = ?
					AND gqs.queue = ?
					AND gugm.username = ?
					GROUP BY user_or_group
					ORDER BY relative_share DESC
					LIMIT 1',
					array($bucket['clusterid'], $bucket['queue'], $user));

				if (cacti_sizeof($max_shares)) {
					$output .= '<tr class="tableRow">
						<td style="vertical-align:text-top">' . html_escape($user)                                            . '</td>
						<td class="right" style="vertical-align:text-top">' . ($user == $max_shares['user_or_group'] ? 
							__('Current User'):html_escape($max_shares['user_or_group']))                                     . '</td>
						<td class="right" style="vertical-align:text-top">' . $max_shares['shares']                           . '</td>
						<td class="right" style="vertical-align:text-top">' . $max_shares['priority']                         . '</td>
						<td class="right" style="vertical-align:text-top">' . round($max_shares['relative_share'], 2)         . '</td>
						<td class="right" style="vertical-align:text-top">' . number_format_i18n($max_shares['slotShare'], 0) . '</td>
					</tr>';
				} else {
					$output .= '<tr class="tableRow">
						<td style="vertical-align:text-top">' . html_escape($user) . '</td>
						<td class="right" style="vertical-align:text-top">-</td>
						<td class="right" style="vertical-align:text-top">-</td>
						<td class="right" style="vertical-align:text-top">-</td>
						<td class="right" style="vertical-align:text-top">-</td>
						<td class="right" style="vertical-align:text-top">-</td>
					</tr>';
				}

				$count++;

				if ($count >= 10) {
					break;
				}
			}

			$output .= '</table>';
		}

		if ($bucket['unique_userGroups'] != '') {
			$output .= '<table class="cactiTable">
				<thead>
					<tr class="tableHeader">
						<th>' . __('Requested User Groups (Top 10)', 'grid')   . '</th>
						<th class="right">' . __('Max Relative Share', 'grid') . '</th>
						<th class="right">' . __('Max Slot Share', 'grid')     . '</th>
					</tr>
				</thead>
				<tbody>';

			$ugroups = explode(',', $bucket['unique_userGroups']);
			asort($ugroups);

			$count = 0;

			foreach($ugroups as $group) {
				$max_shares = db_fetch_row_prepared('SELECT user_or_group, MAX(relative_share) AS relative_share,
					MAX(slot_share) AS slotShare
					FROM grid_queues_shares AS gqs
					WHERE gqs.clusterid = ?
					AND gqs.queue = ?
					AND gqs.user_or_group = ?
					GROUP BY user_or_group
					ORDER BY relative_share DESC
					LIMIT 1',
					array($bucket['clusterid'], $bucket['queue'], $group));

				if (cacti_sizeof($max_shares)) {
					$output .= '<tr class="tableRow">
						<td style="vertical-align:text-top">' . html_escape($group)                                           . '</td>
						<td class="right" style="vertical-align:text-top">' . round($max_shares['relative_share'], 2)         . '</td>
						<td class="right" style="vertical-align:text-top">' . number_format_i18n($max_shares['slotShare'], 0) . '</td>
					</tr>';
				} else {
					$output .= '<tr class="tableRow">
						<td style="vertical-align:text-top">' . html_escape($user) . '</td>
						<td class="right" style="vertical-align:text-top">' . __('Not Found', 'grid') . '</td>
						<td class="right" style="vertical-align:text-top">' . __('Not Found', 'grid') . '</td>
					</tr>';
				}

				$count++;

				if ($count >= 10) {
					break;
				}
			}
		}

		if ($bucket['pendReasons'] != '' && $bucket['pendReasons'] != '[]' && $bucket['pendReasons'] != 'false') {
			$reasons = json_decode($bucket['pendReasons'], true);

			$pendhead = __esc('Top Pending Reasons [ for Pseudo Buckets with < 40,000 Jobs ]', 'grid');

			if (cacti_sizeof($reasons)) {
				$output .= '<table class="cactiTable">
					<thead>
						<tr class="tableHeader">
							<th colspan="5">' . $pendhead . '</th>
						</tr>
						<tr class="tableHeader">
							<th style="width:40px">' . __('Type', 'grid')      . '</th>
							<th>' . __('Reason', 'grid')    . '</th>
							<th>' . __('Detail', 'grid')    . '</th>
							<th class="right" style="width:60px">' . __('Jobs', 'grid')      . '</th>
							<th class="right" style="width:60px">' . __('Pend Time', 'grid') . '</th>
						</tr>
					</thead>
					<tbody>';

				foreach($reasons as $r) {
					if ($r['detail'] == '') {
						$r['detail'] = __('N/A', 'grid');
					}

					$output .= '<tr class="tableRow">
						<td style="vertical-align:text-top">' . ($r['type'] == 'active' ? __('Current', 'grid'):__('Aged', 'grid')) . '</td>
						<td style="vertical-align:text-top">' . html_escape($r['reason']) . '</td>
						<td style="vertical-align:text-top">' . html_escape($r['detail']) . '</td>
						<td class="right" style="vertical-align:text-top">' . number_format_i18n($r['jobs']) . '</td>
						<td class="right" style="vertical-align:text-top">' . display_job_time($r['pendTime']) . '</td>
					</tr>';
				}

				$output .= '</tbody></table>';
			}
		}

		$output .= '</td></tr>';

		html_start_box(__('Key Bucket Details', 'grid'), '100%', '', '3', 'center', '');

		print $output;

		html_end_box();

		$limits = '';

		if (strpos($bucket['unique_users'], ',') === false) {
			$bucket_user = $bucket['unique_users'];
		}

		if ($bucket_user == '-1') {
			$bjobs_cmd = -1;

			html_start_box(__("User/Queue/SLA Level Pending Reasons [ Not Run ]", 'grid'), '100%', '', '3', 'center', '');
		} elseif ($bucket['sla'] != 'N/A' && $bucket['sla'] != '') {
			$bjobs_cmd = "bjobs -psum -p2 -q {$bucket['queue']} -sla {$bucket['sla']} -u{$bucket_user}";

			html_start_box(__("User/Queue/SLA Level Pending Reasons [ $bjobs_cmd ]", 'grid'), '100%', '', '3', 'center', '');
		} else {
			$bjobs_cmd = "bjobs -psum -p2 -q {$bucket['queue']} -u{$bucket_user}";

			html_start_box(__("User/Queue Level Pending Reasons [ $bjobs_cmd ]", 'grid'), '100%', '', '3', 'center', '');
		}


		if ($bucket_user != '-1' && $lsf_bindir != '') {
			$return_var = 0;
			$output     = array();
			$last_line  = exec("$lsf_bindir/$bjobs_cmd;exit 0", $output, $return_var);

			$header = array(
				__('Reason', 'grid'),
				__('Jobs/Occurances', 'grid'),
				__('Count', 'grid')
			);

			html_header($header);

			if ($return_var == 0) {
				if (cacti_sizeof($output)) {
					foreach($output as $line) {
						if (strpos($line, 'Pending reason su') !== false) {
							continue;
						} elseif (strpos($line, 'Summar') !== false) {
							continue;
						} elseif (strpos($line, 'Individual host based reasons:') !== false) {
							continue;
						} elseif (strpos($line, 'Candidate') !== false) {
							continue;
						} elseif (trim($line) == '') {
							continue;
						}

						$parts = explode(' ', $line);
						$size  = count($parts);
						$jo    = $parts[$size-1];
						$count = $parts[$size-2];

						unset($parts[$size-1]);
						unset($parts[$size-2]);

						$nline  = implode(' ', $parts);

						$reason = $nline;

						print '<tr>';
						print '<td>' . $reason . '</td>';
						print '<td>' . $jo     . '</td>';
						print '<td>' . $count  . '</td>';

						//print '<tr><td>' . $reason . '</td></tr>';

						if (strpos($line, 'Limit Name:') !== false &&
							strpos($line, 'jlu.default') === false &&
							strpos($line, 'lsf.masters') === false) {
								$parts  = explode('Limit Name:', $line);
								$lpart  = $parts[1];
								$lparts = explode(',', $lpart);

								$limits .= ($limits != '' ? '|':'') . trim($lparts[0]);
						}
					}
				} else {
					print "<tr><td>No Summary Pending Reasons Returned</td></tr>";
				}
			} else {
				print "<tr><td>The bjobs command returned an error!</td></tr>";
			}
		} else {
			print "<tr><td>These details are available if you filter by a single user.</td></tr>";
		}

		html_end_box();

		if ($bucket_user != '-1') {
			$egrep = '(' . $bucket_user . ($limits != '' ? '|' . $limits:'') . ')';
		} else {
			$egrep = '';
		}

		if ($egrep != '') {
			//$blimits = "blimits -q {$bucket['queue']} -w | egrep '$egrep'";
			$blimits = "blimits -w | egrep '$egrep'";
			//$blimits_real = "blimits -q {$bucket['queue']} -o \"name users queues hosts projects apps slots jobs delimiter='|'\" | egrep '$egrep'";
			$blimits_real = "blimits -o \"name users queues hosts projects apps slots jobs delimiter='|'\" | egrep '$egrep'";
		} else {
			//$blimits = "blimits -q {$bucket['queue']} -w";
			$blimits = "blimits -w";
			//$blimits_real = "blimits -q {$bucket['queue']} -o \"name users queues hosts projects apps slots jobs delimiter='|'\"";
			$blimits_real = "blimits -o \"name users queues hosts projects apps slots jobs delimiter='|'\"";
		}

		if ($bucket_user != '-1') {
			html_start_box(__("Key Limits Data [ $blimits ] [ Cached for 60 Seconds ]", 'grid'), '100%', '', '3', 'center', '');
		} else {
			html_start_box(__("Key Limits Data [ Not Run ]", 'grid'), '100%', '', '3', 'center', '');
		}

		if ($bucket_user != '-1') {
			$return_var = 0;
			$output     = array();
			$time       = time();
			$runlimits  = true;

			if ($egrep != '') {
				if (isset($_SESSION['sess_blimits'][$egrep])) {
					if ($_SESSION['sess_blimits'][$egrep]['time'] + 60 > $time) {
						$output = $_SESSION['sess_blimits'][$egrep]['output'];
						$runlimits = false;
					}
				}
			} else {
				if (isset($_SESSION['sess_blimits']['nogrep'])) {
					if ($_SESSION['sess_blimits']['nogrep']['time'] + 60 > $time) {
						$output = $_SESSION['sess_blimits']['nogrep']['output'];
						$runlimits = false;
					}
				}
			}

			if ($runlimits && $lsf_bindir != '') {
				$last_line  = exec("$lsf_bindir/$blimits_real;exit 0", $output, $return_var);

				if (cacti_sizeof($output)) {
					if ($egrep != '') {
						$col = $egrep;
					} else {
						$col = 'nogrep';
					}

					$_SESSION['sess_blimits'][$col]['time']   = $time;
					$_SESSION['sess_blimits'][$col]['output'] = $output;
				}
			}

			$header = array(
				__('Limit Name', 'grid'),
				__('User', 'grid'),
				__('Queue', 'grid'),
				__('Hosts', 'grid'),
				__('Projects', 'grid'),
				__('Apps', 'grid'),
				__('Slots', 'grid'),
				__('Jobs', 'grid')
			);

			html_header($header);

			if ($return_var == 0) {
				if (cacti_sizeof($output)) {
					foreach($output as $line) {
						//$parts = preg_split('/[\s]+/', $line);
						$parts = explode('|', $line);

						print '<tr>';
						print '<td>' . $parts[0] . '</td>';
						print '<td>' . $parts[1] . '</td>';
						print '<td>' . $parts[2] . '</td>';
						print '<td>' . $parts[3] . '</td>';
						print '<td>' . $parts[4] . '</td>';
						print '<td>' . $parts[5] . '</td>';
						print '<td>' . $parts[6] . '</td>';
						print '<td>' . $parts[7] . '</td>';
						print '</tr>';
					}
				} else {
					print "<tr><td>No Limit Information Returned</td></tr>";
				}
			} else {
				print "<tr><td>The blimits command returned an error!</td></tr>";
			}
		} else {
			print "<tr><td>These details are available if you filter by a single user.</td></tr>";
		}

		html_end_box();

		ob_get_flush();
	} else {
		print "No matching Job Bucket Found!";
	}
}

function grid_view_resreq() {
	global $stats, $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config;

	if (!db_table_exists('grid_jobs_resreq_analysis')) {
		raise_message('grraw', __('Resource Analytics tables not yet created.  Please wait for one polling cycle before attempting to view', 'grid'), MESSAGE_LEVEL_WARN);

		header('Location: ' . $config['url_path'] . '/plugins/grid/grid_default.php');

		exit;
	}

	$sql_params = array();

	grid_set_minimum_page_refresh();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$default_lo = db_fetch_cell_prepared('SELECT id
		FROM grid_page_layouts
		WHERE `default` = "on"
		AND user_id = ?',
		array($_SESSION['sess_user_id']));

	$resreq_results = grid_get_records($sql_where, true, $rows, $sql_params);

	general_header();

	$spinner = '<span id="smessage"></span>';

	html_start_box(__('Resource Analytics Filter [ Enter Username, Rusage, Project or Select for Search ] ' . $spinner, 'grid'), '100%', '', '3', 'center', '');
	filter();
	html_end_box();

	// Location for save/rename dialog functions
	print "<div style='display:none;' id='losave' title='" . __esc('Save Layout', 'grid') . "'>
		<form style='padding:3px;margin:3px;' id='fsave' method='post' action='#'>
			<label style='margin:5px;' for='sname'>" . __('Name:', 'grid') . "</label>
			<br>
			<input style='margin:5px;' type='text' size='35' id='sname'>
			<br>
			<input style='margin:5px;' type='button' value='" . __esc('Save', 'grid') . "' id='ssave'>
			<input style='margin:5px;' type='button' value='" . __esc('Cancel', 'grid') . "' id='scancel'>
			<input type='hidden' id='snew' value='0'>
			<input type='hidden' id='srename' value='0'>
		</form>
	</div>";

	print "<div style='display:none;width:80%;height:80%;padding:5px;' class='cactiTable' id='fullscreen'></div>";
	print "<div style='display:none;padding:5px;margin:5px;' id='gendialog'></div>";

	?>
	<style>
	.ui-tooltip{
		max-width: 1024px !important;
		width: 1024px !important;
	}
	</style>
	<script type='text/javascript'>

	var projectTags='<?php print __esc('Project Tags', 'grid');?>';
	var default_lo = '<?php print $default_lo;?>';
	var layout     = '<?php print get_request_var('layout');?>';

	function applyFilter() {
		$('.pendTip').each(function() {
			$(this).replaceWith('<a href="#">'+'<?php print __('Details', 'grid');?>'+'</a>');
		});

		strURL = 'grid_resreq.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&status='    + $('#status').val();
		strURL += '&sla='       + $('#sla').val();
		strURL += '&reqcpus='   + $('#reqcpus').val();
		strURL += '&queue='     + $('#queue').val();
		strURL += '&job_user='  + $('#job_user').val();
		strURL += '&reasons='   + $('#reasons').val();
		strURL += '&ffilter='   + $('#ffilter').val();
		strURL += '&last='      + $('#last').val();
		strURL += '&rows='      + $('#rows').val();

		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_resreq.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	function clearStatus() {
		$('#status').val('0').selectmenu('refresh');;
		applyFilter();
	}

	function setStatus(stat) {
		$('#status').val(stat).selectmenu('refresh');
		applyFilter();
	}

	function filterCluster(clusterid) {
		$('#clusterid').val(clusterid);
		applyFilter();
	}

	function filterSLA(sla) {
		$('#sla').val(sla);
		applyFilter();
	}

	function filterQueue(queue) {
		$('#queue').val(queue);
		applyFilter();
	}

	function filterCpu(cpus) {
		$('#reqcpus').val(cpus);
		applyFilter();
	}

	function changeLayout() {
		console.log('Change Layout');

		$('.pendTip').each(function() {
			$(this).replaceWith('<a href="#">'+'<?php print __('Details', 'grid');?>'+'</a>');
		});

		strURL  = 'grid_resreq.php?header=false';
		strURL += '&layout=' + $('#layout').val();
		strURL += '&reset=true';

		loadPageNoHeader(strURL);
	}

	function saveLayout() {
		var layout = $('#layout').val();
		var name   = '';
		var rename = '';

		if ($('#snew').val() == '1') {
			var newlo  = true;
			var layout = '&layout=0&template=' + layout;
			var name   = '&name=' + $('#sname').val();
		} else {
			var newlo  = false;
			var layout = '&layout=' + layout;
			var name   = '&name='   + $( "#layout option:selected" ).text();
		}

		if ($('#srename').val() == '1') {
			var rename = '&rename=true';
			var name   = '&name=' + $('#sname').val();
		}

		$('#snew').val('0');
		$('#srename').val('0');

		$.ajaxQ.abortAll();

		var postData = {};

		var strURL = urlPath + 'plugins/grid/grid_resreq.php' +
			'?action=savelayout' +
			layout               +
			name                 +
			rename;

		postData = appendAllPosts(postData);

		$.post(strURL, postData, async function(data) {
			var layout  = data.layout;
			var message = data.message;
			var name    = data.name;

			$('#layout').val(layout);
			$('#smessage').html(message).show().delay(2000).fadeOut(1000);

			updateFilter(layout, name);

			$('#go').button('enable');

			Pace.stop();
		}, 'json');
	}

	function saveLayoutDialog() {
		$('#losave').dialog('option', 'title', '<?php print __('New Layout', 'grid');?>');
		$('#sname').attr('value', '');
		$('#snew').attr('value','1');
		$('#srename').attr('value','0');
		$('#losave').dialog('open');
	}

	function renameLayoutDialog() {
		$('#losave').dialog('option', 'title', '<?php print __('Rename Layout', 'grid');?>');
		$('#sname').attr('value', $('#layout option:selected').text());
		$('#snew').attr('value', '0');
		$('#srename').attr('value', '1');
		$('#losave').dialog('open');
	}

	function deleteLayout() {
		// Get layout and dashboard
		var layout = $('#layout').val();

		if (layout > 0) {
			var strURL = urlPath + 'plugins/grid/grid_resreq.php' +
				'?action=deletelayout' +
				'&layout=' + layout;

			$.getJSON(strURL, function(data) {
				var layout  = data.layout;

				$('#layout option[value="'+layout+'"]').remove();
				$('#layout').val('0').selectmenu('refresh');
			});
		}
	}

	function defaultLayout() {
		// Get layout and dashboard
		var layout = $('#layout').val();

		var strURL = urlPath + 'plugins/grid/grid_resreq.php' +
			'?action=defaultlayout' + '&layout=' + layout;

		$.get(strURL, function(data) {
			$('#smessage').html('<?php print __('Current Layout is now Default', 'grid');?>').show().delay(2000).fadeOut(1000);
			Pace.stop();

			default_lo = layout;
		});
	}

	function appendAllPosts(data) {
		// CSRF Protection
		data['__csrf_magic'] = csrfMagicToken;

		data = appendPost('clusterid', data);
		data = appendPost('queue', data);
		data = appendPost('job_user', data);
		data = appendPost('sla', data);
		data = appendPost('reqcpus', data);
		data = appendPost('status', data);
		data = appendPost('rows', data);
		data = appendPost('reasons', data);
		data = appendPost('ffilter', data);

		return data;
	}

	function appendPost(variable, data) {
		if ($('#'+variable).length) {
			if ($('#'+variable).attr('type') == 'checkbox') {
				data[variable] = $('#'+variable).is(':checked');
			} else if (variable != 'rfilter') {
				if ($.isArray($('#'+variable).val())) {
					data[variable] = $('#'+variable).val().join();
				} else {
					data[variable] = $('#'+variable).val();
				}
			} else {
				data[variable] = base64_encode($('#'+variable).val());
			}
		}

		return data;
	}

	function loadPagePost(strURL, postData, returnLocation) {
		$.post(strURL, postData, function(data) {
			if (returnLocation !== undefined) {
				$('#'+returnLocation).empty().html(data);
			} else {
				$('#main').empty().html(data);
			}

			applySkinLite();
			initFilters();

			Pace.stop();
		});
	}

	function updateFilter(layout, name) {
		if ($("#layout option[value='"+layout+"']").val() !== undefined) {
console.log('old item');
			$('#layout').val(layout).selectmenu('refresh');
		} else {
console.log('new item');
			$('#layout').append('<option selected="selected" value="'+layout+'">'+name+'</option>');
			$('#layout').selectmenu('refresh');
		}
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #queue, #job_user, #sla, #status, #reqcpus, #reasons, #last, #ffilter').change(function() {
			applyFilter();
		});

		$('#layout').change(function() {
			changeLayout();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#update').off('click').on('click', function() {
			saveLayout();
		});

		$('#rename').off('click').on('click', function() {
			renameLayoutDialog();
		});

		$('#save').off('click').on('click', function() {
			saveLayoutDialog();
		});

		$('#delete').off('click').on('click', function() {
			deleteLayout();
		});

		if (layout == default_lo) {
			$('#default').button('disable');
		} else {
			$('#default').button('enable');
			$('#default').off('click').on('click', function() {
				defaultLayout(layout);
				$('#default').button('disable');
			});
		}

		$('#losave').dialog({
			autoOpen: false,
			autoResize: true,
			modal: true,
			resizable: false,
			minHeight: 80,
			minWidth: 300
		});

		$('#ssave').off('click').on('click', function() {
			$('#losave').dialog('close');
			saveLayout();

			$('#sname').attr('value', '');
			return false;
		});

		$('#fsave').submit(function() {
			$('#losave').dialog('close');
			saveLayout();

			$('#sname').attr('value', '');
			return false;
		});

		$('#scancel').off('click').on('click', function() {
			$('#losave').dialog('close');
		});

		$('.pendTip').tooltip({
			items: '.pendTip',
			track: false,
			position: { my: 'left', of: event, at: 'right+10', collision: 'fit' },
			content: function(callback) {
				var params = $(this).attr('data-tip');
				$.get('grid_resreq.php?action=getdetails&data='+params, function(data) {
					callback(data);
				});
			},
			open: function(event, ui) {
				if (typeof(event.originalEvent) === 'undefined') {
					return false;
				}

				var $id = $(ui.tooltip).attr('id');

				// close any lingering tooltips
				$('div.ui-tooltip').not('#' + $id).remove();

				ui.tooltip.css('width', '800px');
				ui.tooltip.css('max-width', '800px');
			},
			close: function(event, ui) {
				ui.tooltip.hover(function() {
					$(this).stop(true).fadeTo(400, 1);
				},
				function() {
					$(this).fadeOut('400', function() {
						$(this).remove();
					});
				});
			}
		});
	});

	</script>
	<style type='text/css'>
	.resReq {
		white-space: normal !important;
	}
	.tableRow td {
		vertical-align: text-top;
	}
	</style>
	<?php

	if (cacti_sizeof($stats)) {
		html_start_box(__('Summary Statistics as Filtered [ Pending Jobs / Running Jobs / Running Slots ]', 'grid'), '100%', '', '3', 'center', '');

		$display_text = array(
			'nosort1' => array(
				'display' => __('Acclerating', 'grid'),
				'align' => 'right'
			),
			'nosort2' => array(
				'display' => __('Decelerating', 'grid'),
				'align' => 'right'
			),
			'nosort3' => array(
				'display' => __('Stalled (No Dispatch or Running)', 'grid'),
				'align' => 'right'
			),
			'nosort4' => array(
				'display' => __('Draining (No Pending)', 'grid'),
				'align' => 'right'
			),
			'nosort5' => array(
				'display' => __('No Throughput (Long Running)', 'grid'),
				'align' => 'right'
			)
		);

		html_header($display_text);

		$pstat = array();
		foreach($stats as $s) {
			$pstat[$s['stat']] = formatStat($s);
		}

		form_alternate_row();
		form_selectable_cell($pstat['accel'],   0, '20%', 'right logInfo');
		form_selectable_cell($pstat['decel'],   0, '20%', 'right logWarning');
		form_selectable_cell($pstat['stalled'], 0, '20%', 'right logCritical');
		form_selectable_cell($pstat['drain'],   0, '20%', 'right logInfo');
		form_selectable_cell($pstat['ntput'],   0, '20%', 'right logNotice');
		form_end_row();

		html_end_box();
	}

	$limit = get_request_var('reasons');

	if (db_table_exists('grid_jobs_reason_summary')) {
		if (get_request_var('clusterid') > 0) {
			$jreasons = db_fetch_assoc_prepared('SELECT reason, jobs_occurrences
				FROM grid_jobs_reason_summary
				WHERE clusterid = ?
				AND level = "cluster"
				AND type = 0
				AND issusp = 0
				AND jobs_occurrences > 0
				ORDER BY jobs_occurrences DESC
				LIMIT ' . $limit,
				array(get_request_var('clusterid')));

			$oreasons = db_fetch_assoc_prepared('SELECT reason, jobs_occurrences
				FROM grid_jobs_reason_summary
				WHERE clusterid = ?
				AND level = "cluster"
				AND type = 1
				AND issusp = 0
				AND jobs_occurrences > 0
				ORDER BY jobs_occurrences DESC
				LIMIT ' . $limit,
				array(get_request_var('clusterid')));

			$last_updated = db_fetch_cell_prepared('SELECT MAX(last_updated)
				FROM grid_jobs_reason_summary
				WHERE clusterid = ?',
				array(get_request_var('clusterid')));
		} else {
			$jreasons = db_fetch_assoc('SELECT reason, limit_value, SUM(jobs_occurrences) AS jobs_occurrences
				FROM grid_jobs_reason_summary
				WHERE jobs_occurrences > 0
				AND level = "cluster"
				AND type = 0
				AND issusp = 0
				GROUP BY reason
				ORDER BY jobs_occurrences DESC
				LIMIT ' . $limit);

			$oreasons = db_fetch_assoc('SELECT reason, SUM(jobs_occurrences) AS jobs_occurrences
				FROM grid_jobs_reason_summary
				WHERE jobs_occurrences > 0
				AND level = "cluster"
				AND type = 1
				AND issusp = 0
				GROUP BY reason
				ORDER BY jobs_occurrences DESC
				LIMIT ' . $limit);

			$last_updated = db_fetch_cell('SELECT MAX(last_updated)
				FROM grid_jobs_reason_summary');
		}

		if (!empty($last_updated)) {
			print '<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:stretch;width:100%"><div style="width:49.4%;flex-grow:1;padding:3px" class="cactiTable">';

			html_start_box(__('Top %s Pending by Job [ Last Checked %s ]', $limit, substr($last_updated, 5), 'grid'), '100%', '', '3', 'center', '');

			$display_text = array(
				'nosort1' => array(
					'display' => __('Reason', 'grid'),
					'align' => 'left'
				),
				'nosort2' => array(
					'display' => __('Limit Value', 'grid'),
					'align' => 'right'
				),
				'nosort3' => array(
					'display' => __('Jobs', 'grid'),
					'align' => 'right'
				)
			);

			html_header($display_text);

			if (cacti_sizeof($jreasons)) {
				$id = 0;
				foreach($jreasons as $r) {
					$ls_name = get_reason_resource($r['reason']);
					$link    = '';

					if ($ls_name != '') {
						$link = '<a href="' . html_escape($config['url_path'] . "plugins/gridblstat/grid_lsdashboard.php?reset=true&action=features&tab=features&lsid=1&feature={$ls_name}&inuse=false") . '" target="lswindow">' . html_escape($ls_name) . '</a>';
					}

					form_alternate_row('line_r_' . $id);

					if ($link != '') {
						$reason = str_replace($ls_name, $link, $r['reason']);

						form_selectable_cell($reason, $id);
					} else {
						form_selectable_cell($r['reason'], $id);
					}

					form_selectable_ecell($r['reason'], $id, '', 'right');
					form_selectable_cell(number_format_i18n($r['jobs_occurrences']), $id, '', 'right');

					form_end_row();

					$id++;
				}
			} else {
				print '<tr><td colspan="2">' . __('No Job Reasons Found', 'grid') . '</td></tr>';
			}

			html_end_box();

			print '</div><div style="width:49.4%;flex-grow:1;padding:3px" class="cactiTable">';

			html_start_box(__('Top %s Pending by Occurrence [ Last Checked %s ]', $limit, substr($last_updated, 5), 'grid'), '100%', '', '3', 'center', '');

			$display_text = array(
				'nosort1' => array(
					'display' => __('Reason', 'grid'),
					'align' => 'left'
				),
				'nosort3' => array(
					'display' => __('Occurrences', 'grid'),
					'align' => 'right'
				)
			);

			html_header($display_text);

			if (cacti_sizeof($oreasons)) {
				$id = 0;
				foreach($oreasons as $r) {
					form_alternate_row('line_r_' . $id);

					form_selectable_cell($r['reason'], $id);
					form_selectable_cell(number_format_i18n($r['jobs_occurrences']), $id, '', 'right');

					form_end_row();

					$id++;
				}
			} else {
				print '<tr><td colspan="2">' . __('No Occurrence Reasons Found', 'grid') . '</td></tr>';
			}

			html_end_box();

			print '</div></div>';
		}
	}

	$rows_query_string = "SELECT
		COUNT(*)
		FROM grid_jobs_resreq_analysis AS grr
		INNER JOIN grid_clusters AS gc
		ON grr.clusterid = gc.clusterid
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = array(
		'clustername' => array(
			'display' => __('Cluster', 'grid'),
			'sort'    => 'ASC'
		),
		'queue' => array(
			'display' => __('Queue', 'grid'),
			'sort'    => 'ASC'
		),
		'sla' => array(
			'display' => __('Service Level', 'grid'),
			'sort'    => 'ASC'
		),
		'num_cpus' => array(
			'display' => __('CPU\'s', 'grid'),
			'sort'    => 'DESC',
			'align'   => 'center'
		),
		'nosort1' => array(
			'display' => __('Details', 'grid'),
			'tip'     => __('Hover over Details to get information about the Resource Bucket', 'grid'),
			'align'   => 'center'
		),
		'res_select_pend' => array(
			'display' => __('Normalized Select String', 'grid'),
			'tip'     => __('The LSF Resource Requirement Select String dirived and normalized from the Combined Resource Requirements.  Redundant left and right parenthesis have been removed from the expression for readability.', 'grid'),
			'sort'    => 'ASC'
		),
		'res_rusage' => array(
			'display' => __('Rusage', 'grid'),
			'tip'     => __('The LSF Resource Reservation String from the Combined Resource Requirements', 'grid'),
			'sort'    => 'ASC'
		),
		'nosort88' => array(
			'display' => __('Finish Rate', 'grid'),
			'tip'     => __('The Finish Rate is the rate of acceleration or decelleration for the bucket.  Larger positive values indicate larger acceleration.  Larger negative values are indicative of a higher rate of deceleration, but may also be indicative of less jobs remaining pending in the bucket.', 'grid'),
			'align'   => 'left'
		),
		'stalledTime' => array(
			'display' => __('Stalled Time', 'grid'),
			'tip'     => __('The approximate time that RTM has observed the Resource Combination in a stalled condition', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_users' => array(
			'display' => __('Users', 'grid'),
			'tip'     => __('Users of all states including DONE and EXIT jobs', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pendJobs' => array(
			'display' => __('Pending', 'grid'),
			'tip'     => __esc('Jobs and Slots', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avgPendTime' => array(
			'display' => __('Avg Pend', 'grid'),
			'tip'     => __('The average pending time of all jobs that are currently pending in the bucket.  The distribution of pending time is not covered in this number, it\'s simply an average of all of the pending jobs', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'runJobs' => array(
			'display' => __('Running', 'grid'),
			'tip'     => __esc('Jobs and Slots', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'tput5MIN' => array(
			'display' => __('5Min TP', 'grid'),
			'tip'     => __('The number of Jobs that have finished either DONE or EXIT in the last 5 Minutes including a 10 minute delay', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'tputHOUR' => array(
			'display' => __('Hourly TP', 'grid'),
			'tip'     => __('The number of Jobs that have finished either DONE or EXIT in the last Hour including a 10 minute delay', 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
	);

	$display_text = form_process_visible_display_text($display_text);

	/* generate page list */
	$nav = html_nav_bar('grid_resreq.php?filter=' . get_request_var('ffilter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Buckets', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	$i = 0;

	if (cacti_sizeof($resreq_results)) {
		foreach ($resreq_results as $resreq) {
			if ($resreq['pendJobs'] == 0 && $resreq['runJobs'] > 0 && round($resreq['tput5MIN']*12) < round($resreq['tputHOUR'])) {
				print "<tr class='tableRow even logInfo'>";

				$drate = round(($resreq['tput5MIN']*12 / $resreq['tputHOUR']) * -1, 2);
				$rate  = __('Decel/Drain [ %s ]', $drate, 'grid');
			} elseif ($resreq['pendJobs'] == 0 && $resreq['runJobs'] > 0 && round($resreq['tput5MIN']*12) > round($resreq['tputHOUR'])) {
				print "<tr class='tableRow even logInfo'>";

				$arate = round(($resreq['tput5MIN']*12 / $resreq['tputHOUR']) * 1, 2);
				$rate  = __('Accel/Drain [ %s ]', $arate, 'grid');
			} elseif ($resreq['pendJobs'] == 0 && $resreq['runJobs'] == 0) {
				print "<tr class='tableRow even logInfo'>";

				$rate  = __('Aging/Aged', 'grid');
			} elseif ($resreq['pendJobs'] == 0) {
				print "<tr class='tableRow even logInfo'>";

				$rate  = __('Drain', 'grid');
			} elseif ($resreq['avgPendTime'] < 300 && $resreq['runJobs'] == 0) {
				print "<tr class='tableRow even logInfo'>";

				$rate = __('New Bucket', 'grid');
			} elseif ($resreq['tput5MIN'] == 0 && $resreq['tputHOUR'] == 0 && $resreq['pendJobs'] > 0 && $resreq['runJobs'] == 0) {
				print "<tr class='tableRow even logCritical'>";

				$rate = '<span style="font-size:1.5em">&#8734;</span>';
			} elseif (round($resreq['tput5MIN']*12) < round($resreq['tputHOUR'])) {
				print "<tr class='tableRow even logWarning'>";

				$drate = round(($resreq['tput5MIN']*12 / $resreq['tputHOUR']) * -1, 2);
				$rate  = __('Decel [ %s ]', $drate, 'grid');
			} elseif (round($resreq['tput5MIN']*12) > round($resreq['tputHOUR'])) {
				print "<tr class='tableRow even logInfo'>";

				$arate = round(($resreq['tput5MIN']*12 / $resreq['tputHOUR']), 2);
				$rate  = __('Accel [ %s ]', $arate, 'grid');
			} elseif ($resreq['runJobs'] > 0 && $resreq['tput5MIN'] == 0 && $resreq['tputHOUR'] == 0) {
				print "<tr class='tableRow even logNotice'>";

				$rate = __('No Throughput', 'grid');
			} else {
				print "<tr class='tableRow even logInfo'>";

				$rate = __('Stalled', 'grid');
			}

			if ($resreq['sla'] == '') {
				$resreq['sla'] = __('N/A', 'grid');
			}

			// Bucket criteria
			form_selectable_cell(makeClusterFilter($resreq['clusterid'], $resreq['clustername']), $i, '', 'left');
			form_selectable_cell(makeQueueFilter($resreq['queue']), $i, '', 'left');
			form_selectable_cell(makeSLAFilter($resreq['sla']), $i, '', 'left');
			form_selectable_cell(makeCpuFilter($resreq['num_cpus']), $i, '', 'center');

			form_selectable_cell('<a href="#" class="pendTip" data-tip="' . base64_encode(json_encode(array('clusterid' => $resreq['clusterid'], 'bucket_id' => $resreq['id'], 'user' => get_request_var('job_user')))) . '">' . __esc('Details', 'grid') . '</a>', $i, '30', 'center');

			// ResReq Data
			form_selectable_cell(filter_value($resreq['res_select_pend'], get_request_var('ffilter')), $i, '', 'resReq left');
			form_selectable_cell(str_replace(',', '<br>', filter_value($resreq['res_rusage'], get_request_var('ffilter'))), $i, '', 'resReq left');

			// Status information
			form_selectable_cell($rate, $i, '', 'left');

			// Stalled Metrics
			form_selectable_cell(display_job_time($resreq['stalledTime']), $i, '', 'right');

			// Users in bucket
			form_selectable_cell(number_format_i18n($resreq['total_users']), $i, '', 'right');

			// Pending Data
			form_selectable_cell(number_format_i18n($resreq['pendJobs']) . ' / ' . number_format_i18n($resreq['pendSlots']), $i, '', 'right');
			form_selectable_cell(display_job_time($resreq['avgPendTime']), $i, '', 'right');

			// Running Data
			form_selectable_cell(number_format_i18n($resreq['runJobs']) . ' / ' . number_format_i18n($resreq['runSlots']), $i, '', 'right');

			// Throughput Data
			form_selectable_cell(number_format_i18n($resreq['tput5MIN'],0), $i, '', 'right');
			form_selectable_cell(number_format_i18n($resreq['tputHOUR'],0), $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Host Load Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($resreq_results)) {
		print $nav;
	}

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

function get_reason_resource($reason) {
	if (str_contains($reason, '(Res: ')) {
		$reason = trim(explode(',', explode('(Res: ', $reason)[1])[0], ') ');

		$found = db_fetch_cell_prepared('SELECT COUNT(*)
			FROM grid_blstat
			WHERE feature = ?',
			array($reason));

		if ($found) {
			return $reason;
		}
	}

	return '';
}

function formatStat($stat) {
	if ($stat['pendJobs'] == '') {
		$output = '0 / ';
	} else {
		$output = number_format_i18n($stat['pendJobs']) . ' / ';
	}

	if ($stat['runJobs'] == '') {
		$output .= '0 / ';
	} else {
		$output .= number_format_i18n($stat['runJobs']) . ' / ';
	}

	if ($stat['runSlots'] == '') {
		$output .= '0';
	} else {
		$output .= number_format_i18n($stat['runSlots']);
	}

	switch($stat['stat']) {
		case 'accel':
			$output = "<a href='#' onClick='setStatus(1)'>" . $output . '</a>' .
				(get_request_var('status') == '1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
		case 'decel':
			$output = '<a href="#" onClick="$(\'#status\').val(2);applyFilter();">' . $output . '</a>' .
				(get_request_var('status') == '2' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
		case 'stalled':
			$output = '<a href="#" onClick="$(\'#status\').val(3);applyFilter();">' . $output . '</a>' .
				(get_request_var('status') == '3' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
		case 'drain':
			$output = '<a href="#" onClick="$(\'#status\').val(4);applyFilter();">' . $output . '</a>' .
				(get_request_var('status') == '4' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
		case 'ntput':
			$output = '<a href="#" onClick="$(\'#status\').val(5);applyFilter();">' . $output . '</a>' .
				(get_request_var('status') == '5' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
		case 'aging':
			$output = '<a href="#" onClick="$(\'#status\').val(6);applyFilter();">' . $output . '</a>' .
				(get_request_var('status') == '6' ? "&nbsp;&nbsp<a title='" . __esc('Clear Status Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='clearStatus()'></a>":'');
			break;
	}

	return $output;
}

function makeClusterFilter($clusterid, $clustername) {
	return "<a href='#' onClick='filterCluster(\"$clusterid\")'>" . html_escape($clustername) . '</a>' .
		(get_request_var('clusterid') != '0' ? "&nbsp;&nbsp<a title='" . __esc('Clear Cluster Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='filterCluster(\"0\")'></a>":'');
}

function makeSLAFilter($sla) {
	return "<a href='#' onClick='filterSLA(\"$sla\")'>" . html_escape($sla) . '</a>' .
		(get_request_var('sla') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear SLA Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='filterSLA(\"-1\")'></a>":'');
}

function makeQueueFilter($queue) {
	return "<a href='#' onClick='filterQueue(\"$queue\")'>" . html_escape($queue) . '</a>' .
		(get_request_var('queue') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Queue Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='filterQueue(\"-1\")'></a>":'');
}

function makeCpuFilter($cpu) {
	return "<a href='#' onClick='filterCpu(\"$cpu\")'>" . html_escape($cpu) . '</a>' .
		(get_request_var('reqcpus') != '-1' ? "&nbsp;&nbsp<a title='" . __esc('Clear Requested CPU Filter', 'grid') . "' href='#' class='far fa-trash-alt' onClick='filterCpu(\"-1\")'></a>":'');
}

