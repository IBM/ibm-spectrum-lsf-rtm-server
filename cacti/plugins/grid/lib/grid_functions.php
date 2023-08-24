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

function mysql_dump_no_passwd_check($username, $passwd) {
	$userinfo = posix_getpwuid(posix_getuid());
	if(!isset($userinfo['dir'])) {
		cacti_log("NOTE: Cannot get home directory for mysqldump start.");
	}
	if (file_exists($userinfo['dir']. "/.my.cnf")) {
		$my_file_content =  file_get_contents($userinfo['dir']. "/.my.cnf");
		if ($my_file_content === false) {
			cacti_log("NOTE: Cannot get contents from ~/.my.cnf for mysqldump.");
		} else if(stristr($my_file_content, "mysqldump") && stristr($my_file_content, "user") && stristr($my_file_content, "password")) {
			return $userinfo['dir']. "/.my.cnf";
		}
	}
	return '';
}

function make_heartbeat($pollerid, $taskname) {
	db_execute_prepared("REPLACE INTO grid_processes
		(pid, taskname, taskid, heartbeat)
		VALUES (?, ?, ?, NOW())", array(getmypid(), $taskname, $pollerid));
}

function grid_strip_domain($host) {
	return $host;
}

function grid_host_substitute($host) {
	$subs = read_config_option("grid_host_substitute");

	if (strlen($subs)) {
		$subs = explode(";", $subs);

		if (cacti_sizeof($subs)) {
			foreach ($subs as $sub) {
				$host = str_replace(trim($sub), "", $host);
			}
		}
	}

	return $host;
}

/**
 * Check a array item existing or not.
 *
 * @param array $job
 * @param string $column
 * @return void return '-' if item not existing.
 */
function jc(&$job, $column) {
	if (isset($job[$column])) {
		return $job[$column];
	} else {
		return '-';
	}
}

function grid_debug($message) {
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
		cacti_log($message, false, 'GRID');
	}
}

/**
 * Show or hide a column by user preference
 *
 * @param mixed $value current cell content
 * @param string $cfgname user preference option name for column show or hide
 * @param string $id
 * @param string $class
 * @param string $title
 * @return void
 */
function form_selectable_cell_visible($value, $cfgname = '', $id = '', $class = '', $title = '') {
	static $count = 0;

	if ($cfgname != '') {
		if (read_grid_config_option($cfgname) == 'on') {
			form_selectable_cell($value, $id, '', $class, $title);
		}
	} else {
		form_selectable_cell($value, $id, '', $class, $title);
	}

	$count++;
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
function grid_selectable_cell($contents, $id, $width = '', $style_or_class = '', $title = '') {
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

function form_process_visible_display_text($display_text) {
	$return_array = array();

	if (cacti_sizeof($display_text)) {
		foreach($display_text as $id => $column) {
			if (isset($column['dbname'])) {
				if (read_grid_config_option($column['dbname']) == 'on') {
					$return_array[$id] = $column;
				}
			} else {
				$return_array[$id] = $column;
			}
		}
	}

	return $return_array;
}
/**
 * Display readonly text with meta data. e.g. Job Detail page
 *
 * @param string $metatype:    one of 'simple' or 'detailed'. In Pre-10.2:
 * 								'simple':   Output: Object value with/out href, filterhighlight, and attach mouse event;
 * 								'detailed': Output user full name, e.g. Abb Cdd (acbd), and attach mouse event;
 * @param string $type:        Meta Data type by configuration. e.g. user, host, queue-grpoup, ....
 * @param int $clusterid:
 * @param mixed $value         Object value, e.g. queue name, host name, ....
 * @param string $class
 * @param string $title
 * @param boolean $searchable
 * @param string $url
 * @return void
 */
function grid_print_metadata($metatype, $type, $clusterid, $value, $class = '', $title = '', $searchable = false, $url = '') {
	global $config;

	static $count = 0;

	include_once($config['base_path'] . '/plugins/meta/lib/metadata_api.php');

	$params = json_encode(
		array(
			'count'      => $count,
			'type'       => $type,
			'cluster-id' => $clusterid,
			'object-key' => $value
		)
	);

	$display_value = meta_preprocess_object_json($params);

	if ($display_value == '') {
		if (empty($value) || $value == 'NULL') {
			$display_value = '-';
		} else {
			$display_value = $value;
		}
	}
	if ($metatype == 'simple') {
		$class .= 'meta-simple';
	} else {
		$class .= 'meta-detailed';
	}

	$class = trim($class);

	$id = base64url_encode($params);

	if ($searchable || $url != '') {
		print "<span id='$id' class='$class' title='$title'>" . filter_value($display_value, get_request_var('filter'), $url) . '</span>';
	} else {
		print "<span id='$id' class='$class' title='$title'>" . html_escape($display_value) . '</span>';
	}

	$count++;
}

/**
 * Like function grid_selectable_cell_visible, addtional support alternative meta data display
 *
 * @param string $metatype simple or ddetailed
 * @param string $type one of application,host,job-group,license-project,project,queue,queue-group,user
 * @param int $clusterid cluster id related current cell value
 * @param mixed $value original value, like hostname, queuename, username, ....
 * @param string $cfgname user preference option name
 * @param string $class html element classname
 * @param string $title html element title property
 * @param boolean $searchable if TRUE, data cell will be partially or full highlight with search filter input
 * @param string $url hyperlink on top of this cell content
 * @return void
 */
function form_selectable_cell_metadata($metatype, $type, $clusterid, $value, $cfgname = '', $class = '', $title = '', $searchable = false, $url = '') {
	global $config;

	static $count = 0;

	include_once($config['base_path'] . '/plugins/meta/lib/metadata_api.php');

	$params = json_encode(
		array(
			'count'      => $count,
			'type'       => $type,
			'cluster-id' => $clusterid,
			'object-key' => $value
		)
	);

	$display_value = meta_preprocess_object_json($params);

	if ($display_value == '') {
		if (empty($value) || $value == 'NULL') {
			$display_value = '-';
		} else {
			$display_value = $value;
		}
	}
	if ($metatype == 'simple') {
		$class .= 'meta-simple';
	} else {
		$class .= 'meta-detailed';
	}

	$class = trim($class);

	$id = base64url_encode($params);

	if ($searchable || $url != '') {
		grid_selectable_cell_visible(filter_value($display_value, get_request_var('filter'), $url), $cfgname, $id, $class, $title);
	} else {
		grid_selectable_cell_visible($display_value, $cfgname, $id, $class, $title);
	}

	$count++;
}

function grid_selectable_cell_visible($value, $db_variable = '', $id = '', $class = '', $title = '') {
	static $count = 0;

	if ($db_variable != '') {
		if (read_grid_config_option($db_variable) == 'on') {
			grid_selectable_cell($value, $id, '', $class, $title);
		}
	} else {
		grid_selectable_cell($value, $id, '', $class, $title);
	}

	$count++;
}

function grid_get_exit_code_details($exitStatus, $exceptMask, $exitInfo) {
	if ($exitInfo > 0 || $exceptMask > 0) {
		$exitReason = getExceptionStatus($exceptMask, $exitInfo);
		$parts = explode(':', $exitReason);
		return $parts[0];
	} elseif ($exitStatus > 0) {
		if ($exitStatus >> 8 & 0xFF) {
			$exit_status = $exitStatus >> 8 & 0xFF;
			$type = __('App', 'grid');
		} else {
			$exit_status = $exitStatus & 0x7F;
			$type = __('Sig', 'grid');
		}

		return $type . ': ' . $exit_status;
	} else {
		return '';
	}
}

function grid_get_exit_code($status) {
	if ($status >> 8 & 0xFF) {
		$exit_status = $status >> 8 & 0xFF;
	} else {
		$exit_status = $status & 0x7F;
	}

	return $exit_status;
}

function grid_get_term_signal($status) {
	$return_status = $status >> 8 & 0x7F;

	return $return_status;
}

function grid_get_askedHosts($job) {
	$new_hosts = '';
	if (!cacti_sizeof($job)) {
		return $new_hosts;
	}

	if (read_config_option('grid_partitioning_enable') == '') {
		// need to check both the grid_jobs_reqhosts and grid_jobs_reqhosts_finished tables

		$hosts = db_fetch_assoc_prepared("SELECT host
			FROM grid_jobs_reqhosts
			WHERE jobid= ?
			AND indexid= ?
			AND submit_time= ?
			AND clusterid= ?", array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

		/* first check grid_jobs_reqhosts */
		if (cacti_sizeof($hosts)) {
			foreach ($hosts as $host) {
				if (strlen($new_hosts)) {
					$new_hosts .= ', ';
				}
				$new_hosts .= $host['host'];
			}
		} else {
			/* if nothing is found, let's check grid_jobs_reqhosts_finished */
			$hosts = db_fetch_assoc_prepared("SELECT host
				FROM grid_jobs_reqhosts_finished
				WHERE jobid= ?
				AND indexid= ?
				AND submit_time= ?
				AND clusterid= ?", array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

			if (cacti_sizeof($hosts)) {
				foreach ($hosts as $host) {
					if (strlen($new_hosts)) {
						$new_hosts .= ', ';
					}
					$new_hosts .= $host['host'];
				}
			}
		}
	} else {
		/* check grid_jobs_reqhosts first */
		$hosts = db_fetch_assoc_prepared("SELECT host
			FROM grid_jobs_reqhosts
			WHERE jobid= ?
			AND indexid= ?
			AND submit_time= ?
			AND clusterid= ?", array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

		if (cacti_sizeof($hosts)) {
			foreach ($hosts as $host) {
				if (strlen($new_hosts)) {
					$new_hosts .= ', ';
				}

				$new_hosts .= $host['host'];
			}
		} else {
			/* if we don't find it, then check partition tables */
			$tables = partition_get_partitions_for_query('grid_jobs_reqhosts_finished', $job['submit_time'], $job['end_time'], 1);

			foreach ($tables as $table) {
				$hosts = db_fetch_assoc_prepared("SELECT host
					FROM $table
					WHERE jobid= ?
					AND indexid= ?
					AND submit_time= ?
					AND clusterid= ?", array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

				if (cacti_sizeof($hosts)) {
					foreach ($hosts as $host) {
						if (strlen($new_hosts)) {
							$new_hosts .= ', ';
						}

						$new_hosts .= $host['host'];
					}
				}
			}
		}
	}

	return $new_hosts;
}

function grid_get_user_hosts($clusterid, $userid) {
	$sql_params = array();

	if ($clusterid != 0) {
		$cluster_where = "AND (clusterid=?)";
		$sql_params[] = $clusterid;
	} else {
		$cluster_where = '';
	}

	/* get running and suspended jobs */
	$jobs = db_fetch_assoc_prepared("SELECT
		clusterid, jobid, indexid, submit_time, exec_host, num_cpus
		FROM grid_jobs
		WHERE (stat IN('RUNNING', 'USUSP', 'PSUSP', 'SSUSP', 'PROV'))
		AND (user=?)
		$cluster_where", array_merge(array($userid), $sql_params));

	/* check for any mpi jobs */
	$user_hosts =array();
	if (cacti_sizeof($jobs)) {
		foreach ($jobs as $job) {
			if ($job['num_cpus'] > 1) {
				$add_hosts = db_fetch_assoc_prepared("SELECT exec_host
					FROM grid_jobs_jobhosts
					WHERE jobid= ?
					AND indexid= ?
					AND clusterid= ?
					AND submit_time= ?", array($job['jobid'], $job['indexid'], $clusterid, $job['submit_time']));

				if (cacti_sizeof($add_hosts)) {
					foreach ($add_hosts as $mpi_host) {
						if (array_search($mpi_host['exec_host'], $user_hosts) === false) {
							array_push($user_hosts, $mpi_host['exec_host']);
						}
					}
				}

				$pos = strpos($job['exec_host'], '*');
				if(($pos = strpos($job['exec_host'], '*')) !== false){
					$new_host = substr($job['exec_host'], $pos+1);
				} else {
					$new_host = $job['exec_host'];
				}

				if (array_search($new_host, $user_hosts) === false){
					array_push($user_hosts, $new_host);
				}
			} else {
				if (array_search($job['exec_host'], $user_hosts) === false) {
					array_push($user_hosts, $job['exec_host']);
				}
			}
		}
	}

	$format_hosts = '';
	if (cacti_sizeof($user_hosts)) {
		$format_hosts = '';
		$num_hosts = 0;

		foreach ($user_hosts as $host) {
			if ($num_hosts == 0) {
				$format_hosts .= "'" . $host . "'";
			} else {
				$format_hosts .= ", '" . $host . "'";
			}

			$num_hosts++;
		}
	}

	return $format_hosts;
}

/**
 * Get cluster id/name list. ClusterName column is zone name if zone type is 'submission' or 'execution'.
 *
 * @return array DB fetch result, include cluster id/name per row
 */
function grid_get_clusterlist($enable_only = false, $clusterids = array()) {
	if (cacti_sizeof($clusterids)) {
		$cluster_where = ' clusterid IN (' . implode(',', $clusterids) . ')';
	} else {
		$cluster_where = '';
	}
	if ($enable_only) {
		$cluster_where .= (!empty($cluster_where) ? ' AND ' : '') . " disabled=''";
	}
	if(!empty($cluster_where)){
		$cluster_where = 'WHERE ' . $cluster_where;
	}
	$clusters = db_fetch_assoc_prepared("SELECT clusterid, clustername FROM grid_clusters $cluster_where ORDER BY clustername");
	return $clusters;
}

/**
 * Get clustername by id, Cluster id/name map is stored in session cache grid_clusternames that is updated per 10 mins or adding new cluster.
 *
 * @param int $clusterid
 * @return string clustername
 */
function grid_get_clustername($clusterid) {
	if (!isset($_SESSION['grid_clusternames'])) {
		$_SESSION['grid_clusternames'] = array_rekey(
			db_fetch_assoc('SELECT clusterid, clustername
				FROM grid_clusters
				ORDER BY clusterid'),
			'clusterid', 'clustername'
		);

		$_SESSION['grid_clusternames_last_update_time'] = time();
	} else {
		end($_SESSION['grid_clusternames']);

		$max          = key($_SESSION['grid_clusternames']);
		$current_time = time();

		/* add 5 seconds condition  to avoid that many select may be called by jobs
		 * on new added cluster or by jobs on the deleted cluster with the largest clusterid.
		 */
		if (($max< $clusterid && ($current_time - $_SESSION['grid_clusternames_last_update_time'] > 5)) ||
			($current_time - $_SESSION['grid_clusternames_last_update_time'] > 600) ) {

			//update the seesion in 10min, in case the cluster has been deleted.
			$_SESSION['grid_clusternames'] = array_rekey(
				db_fetch_assoc('SELECT clusterid, clustername
					FROM grid_clusters
					ORDER BY clusterid'),
				'clusterid', 'clustername'
			);

			$_SESSION['grid_clusternames_last_update_time'] = $current_time;
		}
	}

	if (array_key_exists($clusterid,$_SESSION['grid_clusternames'])) {
		return $_SESSION['grid_clusternames'][$clusterid];
	} else {
		return __('NOT FOUND', 'grid');
	}
}

function grid_parse_request_var_into_sql_where($variable, $db_field, $delim = ':') {
	$variables = explode($delim, $variable);
	$sql_where = '';

	if (cacti_sizeof($variables)) {
		foreach ($variables as $var) {
			if (substr_count($var, '@')) {
				$var = str_replace('@', '', $var);

				if (substr_count($var, '%')) {
					$response = $db_field . " NOT LIKE '" . $var . "'";
				} else {
					$response = $db_field . "!='" . $var . "'";
				}
			} else {
				if (substr_count($var, '%')) {
					$response = $db_field . " LIKE '" . $var . "'";
				} else {
					$response = $db_field . "='" . $var . "'";
				}
			}

			if (strlen($sql_where)) {
				$sql_where .= ' AND ' . $response;
			} else {
				$sql_where .= '(' . $response;
			}
		}
	}

	if (strlen($sql_where)) {
		$sql_where .= ')';
	}

	return $sql_where;
}

function grid_get_cluster_collect_status($cluster) {
	global $config;

	$now = time();

	$load          = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_lsload_" . $cluster['clusterid']));
	$load_result   = grid_get_cluster_runtime_status($load, $now, $cluster['max_nonjob_runtime']);

	$bhosts        = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_bhosts_" . $cluster['clusterid']));
	$bhosts_result = grid_get_cluster_runtime_status($bhosts, $now, $cluster['max_nonjob_runtime']);

	$queue        = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_bqueues_" . $cluster['clusterid']));
	$queue_result = grid_get_cluster_runtime_status($queue, $now, $cluster['max_nonjob_runtime']);

	$jobs               = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_bjobs_" . $cluster['clusterid'] . "_Minor"));
	$jobs_minor_result  = grid_get_cluster_runtime_status($jobs, $now, $cluster['max_job_runtime']);

	$jobs               = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_bjobs_" . $cluster['clusterid'] . "_Major"));
	$jobs_major_result  = grid_get_cluster_runtime_status($jobs, $now, $cluster['max_job_runtime']);

	$jpend              = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_update_time_bpend_" . $cluster['clusterid']));
	$jpend_result       = grid_get_cluster_runtime_status($jpend, $now, $cluster['max_job_runtime']);


	$system_maint       = read_config_option('grid_system_collection_enabled', true);
	$admin_disable      = read_config_option('grid_collection_enabled', true);

	//print "cluster->" . $cluster["clustername"] . ", load->" . $load_result . ", bhosts->" . $bhosts_result . ", bqueue->" . $queue_result . ", jobs_major->" . $jobs_major_result . ", jobs_minor->" . $jobs_minor_result;

	if (empty($system_maint)) {
		return 'Maintenance';
	} elseif (empty($admin_disable)) {
		return 'Admin Down';
	} elseif ($jobs_minor_result == 1 &&
		$jpend_result  == 1 &&
		$queue_result  == 1 &&
		$bhosts_result == 1 &&
		$load_result   == 1) {

		return 'Up';
	} elseif ($jobs_minor_result == 0 &&
		$jpend_result  == 0 &&
		$queue_result  == 0 &&
		$bhosts_result == 0 &&
		$load_result   == 0) {

		return 'Down';
	} elseif (($jobs_minor_result == 0) && ($jobs_major_result == 0) && ($jpend_result == 0)) {
		return 'Jobs Down';
	} elseif ($jobs_minor_result == 1 ||
		$jpend_result  == 1 ||
		$queue_result  == 1 ||
		$bhosts_result == 1 ||
		$load_result   == 1) {

		return 'Diminished';
	} elseif ($jobs_minor_result == 2 &&
		$jpend_result  == 2 &&
		$queue_result  == 2 &&
		$bhosts_result == 2 &&
		$load_result   == 2) {

		return 'Diminished';
	} else {
		return 'No Data';
	}
}

function grid_get_cluster_runtime_status($measure, $current_time, $polling_frequency) {
	if (strlen($measure)) {
		$result = explode(' ', $measure);

		if (strlen($result[0])) {
			$pos = strpos($result[0], ':');

			if ($pos) {
				$result_time = substr($result[0], $pos+1);
			} else {
				return 1;
			}

			if (strlen($result_time)) {
				$result_time  = str_replace('_', ' ', $result_time);
				$last_runtime = strtotime($result_time);

				if ($last_runtime + ($polling_frequency * 3) < $current_time) {
					return 0;
				} else {
					return 1;
				}
			}
		}
	}

	return 2;
}

function grid_show_efficiency_state($state, $cluster_status, $clusterid) {
	if (read_config_option('grid_efficiency_enabled') == 'on') {
		if ($cluster_status == 'Up') {
			if ($state == CLUSTER_OK) {
				form_selectable_cell_visible('Ok', 'show_cluster_efic_status', $clusterid, 'left deviceUp');
			} elseif ($state == CLUSTER_RECOVERING) {
				form_selectable_cell_visible('Recovering', 'show_cluster_efic_status', $clusterid, 'left deviceRecovering');
			} elseif ($state == CLUSTER_WARNING) {
				form_selectable_cell_visible('Warn', 'show_cluster_efic_status', $clusterid, 'left deviceWarning');
			} else {
				form_selectable_cell_visible('Alarm', 'show_cluster_efic_status', $clusterid, 'left deviceDown');
			}
		} else {
			form_selectable_cell_visible('Unk', 'show_cluster_efic_status', $clusterid, 'left deviceUnknown');
		}
	} else {
		form_selectable_cell_visible('N/A', 'show_cluster_efic_status', $clusterid, 'left deviceDisabled');
	}
}

function grid_get_group_jobname($job) {
	/* let's display job arrays better */
	$max_display_length = 119;
	if ($job['indexid'] > 0) {
		/* find out where we need to break the jobname for display */
		$split_pos = strpos($job['jobname'], '[');
		if ($split_pos + strlen($job['indexid']) > ($max_display_length-2)) {
			$split_pos = ($max_display_length-2) - strlen($job['indexid']);
		}

		if (!empty($split_pos) && $split_pos >= 0) {
			$jobname = substr($job['jobname'],0,$split_pos) . '[' . $job['indexid'] . ']';
		} else {
			$jobname = substr($job['jobname'],0) . '*' . '[' . $job['indexid'] . ']';
		}
	} else {
		$jobname = substr($job['jobname'],0,$max_display_length);
	}

	return $jobname;
}

function grid_checkouts_enabled() {
	if (db_fetch_cell("SELECT status FROM plugin_config WHERE directory='license'") == '1') {
		if (db_fetch_cell("SELECT enable_checkouts FROM lic_services WHERE enable_checkouts='on' LIMIT 1") == 'on') {
			return true;
		}
	}
	return false;
}

function grid_get_jobid($job) {
	/* let's display job arrays better */
	if ($job['indexid'] > 0) {
		return trim($job['jobid']) . '[' . $job['indexid'] . ']';
	} else {
		return $job['jobid'];
	}
}

function grid_get_poller_information($clusterid) {
	if (!empty($clusterid)) {
		$poller_id = db_fetch_cell_prepared('SELECT poller_id
			FROM grid_clusters
			WHERE clusterid = ?',
			array($clusterid));

		if (!empty($poller_id)) {
			return db_fetch_row_prepared('SELECT *
				FROM grid_pollers
				WHERE poller_id = ?',
				array($poller_id));
		}
	}

	return array();
}

function grid_get_res_tooldir($clusterid) {
	$poller_info = grid_get_poller_information($clusterid);

	if (cacti_sizeof($poller_info)) {
		if (isset($poller_info['poller_lbindir'])) {
			return $poller_info['poller_lbindir'];
		}
	}
}

function grid_get_master_lsf_status($clusterid) {
	global $config;

	include_once($config['base_path'] . '/plugins/grid/include/grid_messages.php');

	$cluster_status = db_fetch_row_prepared('SELECT disabled, lsf_master, lsf_masterhosts,
		lsf_ls_error, lsf_lsb_error, lsf_lsb_jobs_error FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	$status = db_fetch_row_prepared('SELECT grid_load.status AS load_status,
		grid_hosts.status AS batch_status
		FROM grid_load
		INNER JOIN grid_hosts
		ON grid_load.host=grid_hosts.host
		AND grid_load.clusterid=grid_hosts.clusterid
		WHERE grid_load.host = ?
		AND grid_load.clusterid = ?',
		array($cluster_status['lsf_master'], $clusterid));

	if ($cluster_status['disabled'] == 'on') {
		$status['batch_status'] = __('N/A', 'grid');
	} elseif (!isset($status['batch_status'])) {
		$status['batch_status'] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	} elseif (substr_count($status['batch_status'], 'Closed')) {
		$status['batch_status'] = "<span class='deviceUnknown'>" . __('Closed', 'grid') . '</span>';
	} elseif (substr_count($status['batch_status'], 'Busy')) {
		$status['batch_status'] = "<span class='deviceRecovering'>" . __('Busy', 'grid') . '</span>';
	} elseif (substr_count($status['batch_status'], 'Ok')) {
		$status['batch_status'] = "<span title='" . __esc('Masters should be Closed', 'grid') . "' class='deviceUp'>" . __('Ok', 'grid') . '</span>';
	} elseif (strlen($status['batch_status'])) {
		$status['batch_status'] = "<span class='deviceDown'>" . html_escape($status['batch_status']) . '</span>';
	} else {
		$status['batch_status'] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	}

	if ($cluster_status['disabled'] == 'on') {
		$status['load_status'] = '';
	} elseif (!isset($status['load_status'])) {
		$status['load_status'] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	} elseif (substr_count($status['load_status'], 'Ok')) {
		$status['load_status'] = "<span class='deviceUp'>" . __('Ok', 'grid') . '</span>';
	} elseif (substr_count($status['load_status'], 'Busy')) {
		$status['load_status'] = "<span class='deviceRecovering;'>" . __('Busy', 'grid') . '</span>';
	} elseif (strlen($status['load_status'])) {
		$status['load_status'] = "<span class='deviceDown'>" . $status['load_status'] . '</span>';
	} else {
		$status['load_status'] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	}

	if ($cluster_status['lsf_master'] == '') {
		$is_master = 'U';
} elseif (strpos(trim($cluster_status['lsf_masterhosts']), $cluster_status['lsf_master']) > 0) {
		$is_master = 'A';
	} else {
		$is_master = 'P';
	}

	if ($cluster_status['disabled'] == 'on') {
		$lsf_status = __('N/A', 'grid');
	} elseif ($cluster_status['lsf_ls_error'] == 0) {
		$lsf_status = "<span class='deviceUp'>" . __('Ok', 'grid') . '</span>';
	} elseif (isset($grid_base_errors[$cluster_status['lsf_ls_error']])) {
		$lsf_status = "<span title='" . html_escape($grid_base_errors[$cluster_status['lsf_ls_error']][1]) . "' class='deviceDown'>" . $grid_base_errors[$cluster_status['lsf_ls_error']][0] . '</span>';
	} else {
		$lsf_status = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	}

	if ($cluster_status['disabled'] == 'on') {
		/* do nothing */
	} elseif ($cluster_status['lsf_lsb_error'] == 0) {
		$lsf_status .= ' / <span class="deviceUp">' . __('Ok', 'grid') . '</span>';
	} elseif (isset($grid_batch_errors[$cluster_status['lsf_lsb_error']])) {
		$lsf_status .= " / <span title='" . html_escape($grid_base_errors[$cluster_status["lsf_lsb_error"]][1]) . "' class='deviceDown'>" . html_escape($grid_batch_errors[$cluster_status['lsf_lsb_error']][0]) . '</span>';
	} else {
		$lsf_status .= " / <span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
	}

	return array($lsf_status, $status['load_status'], $status['batch_status'], $is_master);
}

function build_order_string($sort_column_array, $sort_direction_array, $append_index_id=true) {
	$length = cacti_sizeof($sort_column_array);
	$result = '';
	for($i=0; $i<$length; $i++) {
		/*
		 * special case where the sorting column is numeric
		 */
		 if (!isset($_SESSION['sess_res_types'])) {
			$_SESSION['sess_res_types'] = array_rekey(
				db_fetch_assoc('SELECT DISTINCT resource_name, resType
					FROM grid_hosts_resources'),
				'resource_name', 'resType'
			);
		}

		if (array_key_exists($sort_column_array[$i], $_SESSION['sess_res_types'])) {
			if (preg_match('/ut|r1m|r15m|r15s/', $sort_column_array[$i])) {
				$result .= $sort_column_array[$i] . ' ' . $sort_direction_array[$i];
			} elseif ($_SESSION['sess_res_types'][$sort_column_array[$i]] != '2') {
				$result .= 'CAST(' . $sort_column_array[$i] . ' AS SIGNED)' . ' ' . $sort_direction_array[$i];
			} else {
				$result .= $sort_column_array[$i] . ' ' . $sort_direction_array[$i];
			}
		} elseif (preg_match('/cpuFactor|maxCpus|maxMem|maxSwap|maxTmp|nDisks|nProcs|cores|nThreads|totalValue|reservedValue|value/', $sort_column_array[$i])) {
			$result .= 'CAST(' . $sort_column_array[$i] . ' AS UNSIGNED)' . ' ' . $sort_direction_array[$i];
		} else {
			$result .= $sort_column_array[$i] . ' ' . $sort_direction_array[$i];
		}

		/* special case when jobid is selected.  In this case we always also use indexid as secondary sort.
		 * This is because job arrays all have same jobid.  But on job array page, we should not append this.
		 */
		if ($append_index_id && $sort_column_array[$i] == 'jobid') {
			$result .= ', indexid '  . get_request_var('sort_direction');
		}

		if ($i != $length -1) {
			$result .= ', ';
		}
	}

	return $result;
}

function grid_set_minimum_page_refresh() {
	global $config, $refresh;

	$minimum = read_config_option('grid_minimum_refresh_interval');

	if (isset_request_var('refresh')) {
		if (get_request_var('refresh') < $minimum) {
			set_request_var('refresh', $minimum);
		}

		/* automatically reload this page */
		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page']    = get_current_page();
	}
}

function display_hours($value) {
	if ($value < 0) {
		return '-';
	} else {
		if ($value < 60) {
			return __('%s min', round($value,0), 'grid');
		} else {
			$value = $value / 60;
			if ($value < 24) {
				return __('%s hr', round($value,0), 'grid');
			} else {
				$value = $value / 24;
				if ($value < 7) {
					return __('%s day', round($value,0), 'grid');
				} else {
					$value = $value / 7;
					return __('%s wk', round($value,0), 'grid');
				}
			}
		}
	}
}

function check_time($time, $boolean = false) {
	if ((substr_count($time, '0000')) ||
		(substr_count($time, '1969')) ||
		(substr_count($time, '1970'))) {
		if ($boolean) {
			return false;
		} else {
			return '-';
		}
	} else {
		if ($boolean) {
			return true;
		} else {
			return $time;
		}
	}
}

function display_job_time($value, $round = 0, $short_form = true) {
	if ($value <= 0) {
		return '-';
	} else {
		if ($value < 60) {
			return round($value,$round) . ($short_form ? 's' : ' Seconds');
		} else {
			$value = $value / 60;
			if ($value < 60) {
				return round($value,$round) . ($short_form ? 'm' : ' Minutes');
			} else {
				$value = $value / 60;
				if ($value < 24) {
					return round($value,$round) . ($short_form ? 'h' : ' Hours');
				} else {
					$value = $value / 24;
					return round($value,$round) . ($short_form ? 'd' : ' Days');
				}
			}
		}
	}
}

function display_time_in_state($value) {
	$days    = 0;
	$hours   = 0;
	$minutes = 0;
	$seconds = 0;

	$one_day = 60*60*24;

	$days = intval($value/$one_day);
	if ($days != 0) {	// more than 1 days
		return __('%s Day(s)', $days, 'grid');
	}

	$hours = intval($value/(60*60));

	if ($hours != 0) {	// more than an hour
		return __('%s Hour(s)', $hours, 'grid');
	}

	$minutes = intval($value/60);

	if ($minutes != 0) {	// more than a minute
		return __('%s Min(s)', $minutes, 'grid');
	}

	return __('%s Secs', $value, 'grid');
}

function display_load($value, $round = 2) {
	if ($value < 0) {
		return '-';
	} else {
		return add_dec($value,$round);
	}
}

function display_ls($value) {
	if ($value < 0) {
		return '-';
	} else {
		return round($value,0);
	}
}

function display_ut($value, $round = 2) {
	if ($value < 0) {
		return '-';
	} else {
		return add_dec($value*100,$round) . '%';
	}
}

function display_job_effic($value, $run_time, $round = 2) {
	if (($value <= 0) || ($run_time == 0)) {
		return '-';
	} else {
		return add_dec($value,$round) . '%';
	}
}

/**
 * TODO: identify actual usage, From LSF definition, 'pg' value scropt should be (0,1), that should not be adjustable by unit.
 * @param float $value page/io rate, value scope should be (0,1)
 * @param integer $round
 * @return float adjusted value. To Be identify in future
 */
function display_pg($value, $round = 2) {
	global $config;

	if ($value < 0) {
		return '-';
	} else {
		if ($value > 1024) {
			include_once($config["library_path"] . '/rtm_functions.php');

			return display_byte_by_unit($value, $round, '');
		} else {
			return add_dec($value, $round);
		}
	}
}

/**
 * Adjust host mem/swap/tmp size to human readable with proper UNIT.
 * @param long $value memory size by MB
 * @param integer $round
 * @return string adjusted value with unit
 */
function display_memory($value, $round = 1) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	return display_byte_by_unit($value, $round, 'M');
}

 /**
 * Adjust job mem/swap size to human readable with proper UNIT.
 * @param long $value memory size by KB
 * @param integer $round
 * @return string adjusted value with unit
 */
function display_job_memory($value, $round = 1) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	return display_byte_by_unit($value, $round);
}

function format_job_slots($break = false, $force_slots = false) {
	$jobs = read_config_option('grid_slot_reference');

	if ($jobs == 'Jobs' && $force_slots) {
		$jobs = 'CPUs';
	}

	if (!$break)  {
		return $jobs;
	} elseif ($break == 'before') {
		return '<br>' . $jobs;
	} else {
		return $jobs . '<br>';
	}
}

function grid_format_seconds($time, $second_base_flag = false) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');
	if(!isset($time) || !strlen($time) || $time == '-'){
		return '-';
	}
	if($second_base_flag){
		if ($time >= 60) {
			$time = $time / 60;
			if ($time >= 60) {
				$time = $time / 60;
				if ($time >= 24) {
					$time = $time / 24;
					return round($time,1) . " Days";
				} else {
					return round($time,1) . " Hrs";
				}
			}else{
				return round($time,1) . " Mins";
			}
		}else{
			return round($time,1) . " Secs";
		}
	}else{
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
	}
}

function grid_format_time($time, $twoline = false) {
if (!substr_count($time, '0000-00-00')) {
	if ($twoline) {
		if (date('Y') == substr($time, 0, 4)) {
			return substr($time,5,5) . '<br>' . substr($time,11);
		} else {
			return substr($time,0,10) . '<br>' . substr($time,11);
		}
	} else {
		if (date('Y') == substr($time,0,4)) {
			return substr($time,5);
		} else {
			return $time;
		}
	}
} else {
	return '-';
}
}

function add_dec($value, $places = 1) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	return rtm_add_dec($value, $places);
}

/* grid_config_value_exists - determines if a value exists for the current user/setting specified
   @arg $config_name - the name of the configuration setting as specified $grid_settings array
 in 'include/config_settings.php'
   @arg $user_id - the id of the user to check the configuration value for
   @returns (bool) - true if a value exists, false if a value does not exist */
function grid_config_value_exists($config_name, $user_id) {
	return cacti_sizeof(db_fetch_assoc_prepared("SELECT value FROM grid_settings WHERE name=? AND user_id=?", array($config_name, $user_id)));
}

/* grid_checkbox_cell - format's a tables checkbox form element so that the cacti js actions work on it
   @arg $title - the text that will be displayed if your hover over the checkbox */
function grid_checkbox_cell($title, $id, $disabled=false) {
	if (!$disabled) {
		print "\t<td onClick='select_line(\"$id\", true)' style='" . get_checkbox_style() . "' width='1%' class='right'>";
		print "\t\t<input type='checkbox' style='margin: 0px;' id='chk_" . $id . "' name='chk_" . $id . "'>";
		print "\t</td>";
	}
}

/* read_default_grid_config_option - finds the default value of a grid configuration setting
   @arg $config_name - the name of the configuration setting as specified $settings array
 in 'include/config_settings.php'
   @returns - the default value of the configuration option */
function read_default_grid_config_option($config_name) {
	global $config, $grid_settings;

	if (isset($grid_settings)) {
		reset($grid_settings);

		foreach ($grid_settings as $tab_name => $tab_array) {
			if ((isset($tab_array[$config_name])) && (isset($tab_array[$config_name]['default']))) {
				return $tab_array[$config_name]['default'];
			} else {
				foreach ($tab_array as $field_name => $field_array) {
					if ((isset($field_array['items'])) && (isset($field_array['items'][$config_name])) && (isset($field_array['items'][$config_name]['default']))) {
						return $field_array['items'][$config_name]['default'];
					}
				}
			}
		}
	} else {
		return null;
	}
}

/* read_grid_config_option - finds the current value of a grid configuration setting
   @arg $config_name - the name of the configuration setting as specified $grid_settings array
 in 'include/config_settings.php'
   @returns - the current value of the grid configuration option */
function read_grid_config_option($config_name, $force = false) {
	/* users must have cacti user auth turned on to use this */
	if (!isset($_SESSION['sess_user_id'])) {
		return read_default_grid_config_option($config_name);
	}

	if ((isset($_SESSION['sess_grid_config_array']) && (!$force))) {
		$grid_config_array = $_SESSION['sess_grid_config_array'];
	}

	if (!isset($grid_config_array[$config_name])) {
		$db_setting = db_fetch_row_prepared('SELECT value
			FROM grid_settings
			WHERE name = ?
			AND user_id = ?',
			array($config_name, $_SESSION['sess_user_id']));

		if (isset($db_setting['value'])) {
			$grid_config_array[$config_name] = $db_setting['value'];
		} else {
			$grid_config_array[$config_name] = read_default_grid_config_option($config_name);
		}

		$_SESSION['sess_grid_config_array'] = $grid_config_array;
	}

	return $grid_config_array[$config_name];
}

function set_grid_config_option($config_name, $value, $user = -1) {
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
			if(!isset($_SESSION['sess_grid_config_array'])){
				$_SESSION['sess_grid_config_array'] = array();
			}
			$_SESSION['sess_grid_config_array'][$config_name] = $value;
		}
	}
}

function grid_set_maintenance_mode($mode = 'BACKUP', $disable = false) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	if (detect_and_correct_running_processes(0, $mode, read_config_option('grid_db_maint_max_time'))) {
		/* disable polling during the maintenance period */
		if ($disable) {
			set_config_option('grid_system_collection_enabled', '');
		}

		cacti_log("NOTE: GRID Maintenance Mode Started for '$mode'", true, 'GRID');

		return true;
	} else {
		return false;
	}
}

function grid_end_maintenance_mode($mode = 'BACKUP') {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	/* enable polling after the maintenance period */
	set_config_option('grid_system_collection_enabled', 'on');

	cacti_log("NOTE: GRID Maintenance Mode Ended for '$mode'", true, 'GRID');
	remove_process_entry(0, $mode);
}

function rm_duplicated_records($table_name) {
	global $database_default;

	$last_partition = db_fetch_cell_prepared("SELECT `partition` FROM grid_table_partitions WHERE table_name=? ORDER BY max_time DESC LIMIT 1", array($table_name)); //Ref: partition_getlatest()
	if (!empty($last_partition)) {
		if($last_partition == "000"){
			$pre_partition = "999";
		}else{
			$pre_partition = $last_partition - 1;
			$tmp_pre_partition = "000". $pre_partition;
			$pre_partition = substr($tmp_pre_partition, strlen($tmp_pre_partition)-3);
		}
		$last_table = $table_name . "_v" . $last_partition;
		$pre_table = $table_name . "_v" . $pre_partition;
		$partitions = db_fetch_assoc_prepared("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema=? AND table_name IN (?, ?)", array($database_default, $last_table, $pre_table));
		if (cacti_sizeof($partitions) == 2) {//if the two tables exist
			$primary_columns = array();
			$db_indexes = array();
			$indexes = db_fetch_assoc("SHOW KEYS FROM $table_name");
			if(cacti_sizeof($indexes)) {
				foreach($indexes as $index) {
					if($index["Key_name"] == 'PRIMARY'){
						$primary_columns[] = $index["Column_name"];
					}else{
						$db_indexes[] = $index["Key_name"];
					}
				}
				$db_indexes = array_unique($db_indexes);
			}
			$alter_sql = "";
			$drop_index_sql = implode("`, DROP INDEX `", $db_indexes);
			if(!empty($drop_index_sql)){
				$drop_index_sql = "DROP INDEX `$drop_index_sql`";
				$alter_sql = "ALTER TABLE `tmp_$table_name` $drop_index_sql";
			}
			$table_columns = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table_name"), 'Field', 'Field');
			$un_pk_columns = array_diff($table_columns, $primary_columns);
			$drop_column_sql = implode("`, DROP COLUMN `", $un_pk_columns);
			if(!empty($drop_column_sql)){
				$drop_column_sql = "DROP COLUMN `$drop_column_sql`";
				if(!empty($drop_index_sql)){
					$alter_sql = "ALTER TABLE `tmp_$table_name` $drop_index_sql, $drop_column_sql";
				}else{
					$alter_sql = "ALTER TABLE `tmp_$table_name` $drop_column_sql";
				}
			}
			/* create temporary table to handle deleted records */
			$success = db_execute("CREATE TEMPORARY TABLE `tmp_$table_name` LIKE $table_name");
			if(!empty($alter_sql) && $success){
				$success = db_execute("$alter_sql");
			}
			if ($success && !empty($primary_columns)) {
				$match_sql = '';
				$value_sql = '';
				$select_sql = '';
				$first_column_flag = true;
				foreach ($primary_columns as $primary_column) {
					if($first_column_flag){
						$first_column_flag = false;
						$match_sql = "pre.$primary_column = last.$primary_column";
						$value_sql = $primary_column;
						$select_sql = "pre.$primary_column";
					}else{
						$match_sql .= " AND pre.$primary_column = last.$primary_column";
						$value_sql .= ", $primary_column";
						$select_sql .= ", pre.$primary_column";
					}
				}
				db_execute("INSERT INTO `tmp_$table_name` ($value_sql)
					SELECT $select_sql
					FROM $last_table AS last
					JOIN $pre_table AS pre
					ON $match_sql");
				db_execute("DELETE FROM pre USING $pre_table AS pre, tmp_$table_name AS last
					WHERE $match_sql");
			}
			db_execute("DROP TABLE IF EXISTS tmp_$table_name");
		}
	}
}

/*	perform_grid_db_maint - This utility removes stale records from the database.
*/
function perform_grid_db_maint($start_time, $optimize = false) {
	global $config, $debug, $cnn_id, $new_partition_tables;
	$new_partition_tables = array();
	$job_finished_partition_version = '-1';

	include_once($config['library_path'] . '/rrd.php');

	$clusters = array_rekey(db_fetch_assoc('SELECT clusterid, clustername FROM grid_clusters'), 'clusterid', 'clustername');
	$pollers  = array_rekey(db_fetch_assoc('SELECT poller_id, poller_name FROM grid_pollers'), 'poller_id', 'poller_name');

	// get last maint start time, if empty  assign it as "current time - 1 day"
	$last_maint_start = read_config_option('grid_db_maint_start_time');
	if (empty($last_maint_start)) $last_maint_start = $start_time - 24*3600;

	/*
	 * grid_db_maint_process_status:
	 *    There are five main processes in the rtm database maintenance task.
	 *    1. Check for memory violators
	 *    2. backup the cacti database
	 *    3. remove stale records and summary daily data
	 *    4. maint db partition
	 *    5. optimize the tables
	 * value:
	 *    1: check for memory vilators done.
	 *    2. backup the cacti db done.
	 *    3. remove stale records and summary daily data done
	 *    4. maint db partition done.
	 *    0. optimize the tables done.
	 *       All five main processed done
	 *
	 */
	$maint_process_status = read_config_option('grid_db_maint_process_status');

	if ($maint_process_status == null) {
		$maint_process_status = 0;
	}

	grid_debug('Last maint status: ' . $maint_process_status);

	set_config_option('grid_db_maint_process_status', '-1');

	if ($maint_process_status == 0) {
		/* Check for memory violators */
		if (read_config_option('gridmemvio_enabled') == 'on') {
			gridmemvio_check_for_memvio_jobs($last_maint_start);
		}

		/* remove any no longer valid statistics */
		$stats = db_fetch_assoc("SELECT * FROM settings
			WHERE (name LIKE '%%grid_update_time%'
			OR name LIKE 'grid_prev%%'
			OR name LIKE 'grid_jobs_start%%'
			OR name LIKE 'grid_arrays_start%%'
			OR name LIKE 'grid_rusage_last%%'
			OR name LIKE 'grid_license_update%%')
			AND name!='grid_prev_db_maint_time';");

		$removal = '';
		if (cacti_sizeof($stats)) {
			foreach ($stats as $stat) {
				$cluster    = 0;
				$lic_poller = 0;
				$stat       = $stat['name'];
				if ((substr_count($stat, 'Minor')) ||
					(substr_count($stat, 'Major'))) {
					$cluster = explode('_', strrev($stat));
					$cluster = intval(strrev($cluster[1]));
				} elseif ((substr_count($stat, 'grid_update_')) ||
					(substr_count($stat, 'grid_jobs_start_')) ||
					(substr_count($stat, 'grid_prev_major_')) ||
					(substr_count($stat, 'grid_prev_minor_')) ||
					(substr_count($stat, 'grid_prev_nonjob_')) ||
					(substr_count($stat, 'grid_rusage')) ||
					(substr_count($stat, 'grid_arrays_start_'))) {
					$cluster = explode('_', strrev($stat));
					$cluster = intval(strrev($cluster[0]));
				} elseif ((substr_count($stat, 'grid_prev_license')) ||
					(substr_count($stat, 'grid_license_update')) ||
					(substr_count($stat, 'grid_prev_license'))) {
					$lic_poller = explode('_', strrev($stat));
					$lic_poller = intval(strrev($lic_poller[0]));
				}

				if ($cluster) {
					if (!isset($clusters[$cluster])) {
						$remove = true;
					} else {
						$remove = false;
					}
				} elseif ($lic_poller) {
					if (!isset($pollers[$lic_poller])) {
						$remove = true;
					} else {
						$remove = false;
					}
				} else {
					$remove = false;
				}

				if ($remove) {
					if (strlen($removal)) {
						$removal .= ",'$stat'";
					} else {
						$removal = "name IN('$stat'";
					}
				}
			}
		}

		/* finish off the IN clause */
		if (strlen($removal)) {
			$removal .= ')';
		}

		/* remove old stats */
		if (strlen($removal)) {
			db_execute("DELETE FROM settings WHERE $removal");
		}

	}

	/* Check for memory violators done*/
	set_config_option('grid_db_maint_process_status', '1');

	/* place a timestamp in the database to help detect a abarrent maintenance run */
	set_config_option('grid_db_maint_start_time', time());

	/* remove the end time to help detect a failed poller */
	db_execute("DELETE FROM settings WHERE name='grid_db_maint_end_time'");

	/* take time and log performance data */
	$begin_time = microtime(true);

	/* remove stale records from the poller database */
	$detail_retention = read_config_option('grid_detail_data_retention');

	if (strlen($detail_retention)) {
		$detail_retention_date = date('Y-m-d H:i:s', strtotime("-$detail_retention"));
	} else {
		$detail_retention_date = date('Y-m-d H:i:s', strtotime('-2 Months'));
	}

	/* how many records do we delete per pass */
	$delete_size     = read_config_option('grid_db_maint_delete_size');

	if ($maint_process_status == 0) {
		/* backup the cacti database, if required (maintenance will be on)*/
		grid_debug('Backup the Cacti database as required.');
		grid_backup_cacti_db();

		/* if grid_db_maint_backup_cacti_db is flase, don't need to backup cacti database */
		set_config_option('grid_db_maint_backup_cacti_db', 'false');
	}

	/* backup the cacti database done*/
	set_config_option('grid_db_maint_process_status', '2');

	/* remove stale records from the poller database */

	$summary_retention = read_config_option('grid_summary_data_retention');

	if (strlen($summary_retention)) {
		$summary_retention_date = date('Y-m-d H:i:s', strtotime('-' . $summary_retention));
	} else {
		$summary_retention_date = date('Y-m-d H:i:s', strtotime('-1 Year'));
	}

	/* collect statistics about all jobs in the last 24 hours */
	$scan_date                  = date('Y-m-d H:i:s', strtotime(read_config_option('grid_db_maint_time')));
	$daily_added = 0;
	if ($maint_process_status == 0) {
		summarize_grid_data_bytime($scan_date, $start_time);

		/* get the number of deleted rows */
		$daily_added = db_affected_rows();

		/* archive daily stat's if enabled */
		if (read_config_option('grid_archive_enable') == 'on') {
			grid_debug('Archiving Daily Stats.');
			grid_perform_archive($start_time, 'DAILY');
		}

		grid_debug('Start deleting done and exited array jobs who are in grid_jobs but not in grid_arrays.');
		$array_job_rows_deleted = 0;
		while (1) {
			$rows_to_delete = db_fetch_cell("SELECT count(*)
				FROM grid_jobs AS gj
				LEFT JOIN grid_arrays AS ga
				ON gj.clusterid=ga.clusterid
				AND gj.jobid=ga.jobid
				AND gj.submit_time=ga.submit_time
				WHERE ga.jobid is null
				AND gj.indexid>0
				AND gj.stat in ('DONE', 'EXIT')");

			if ($rows_to_delete > 0) {
				db_execute("DELETE gj2
				FROM grid_jobs AS gj2
				INNER JOIN
				(
					SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time
					FROM grid_jobs AS gj
					LEFT JOIN grid_arrays AS ga
					ON gj.clusterid=ga.clusterid
					AND gj.jobid=ga.jobid
					AND gj.submit_time=ga.submit_time
					WHERE ga.jobid is null
					AND gj.indexid>0
					AND gj.stat in ('DONE', 'EXIT')
					ORDER BY gj.clusterid, gj.jobid, gj.indexid, gj.submit_time
					LIMIT $delete_size
				) sub1
				ON gj2.clusterid=sub1.clusterid
				AND gj2.jobid=sub1.jobid
				AND gj2.submit_time=sub1.submit_time
				AND gj2.indexid=sub1.indexid");

				$array_job_rows_deleted += db_affected_rows();

			} else {
				break;
			}
		}

		grid_debug("$array_job_rows_deleted done and exited array jobs deleted.");
	}

	set_config_option('grid_db_maint_process_status', '3');

	/* maint db partiton process*/
	grid_debug('Start deleting old job interval records from the job interval database.');

	if ($maint_process_status < 4) {
		$jobs_interval_stat_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_job_interval_stats');
		$interval_purged         = 0;

		if ($jobs_interval_stat_rows > 100000) {
			db_execute('DROP TABLE IF EXISTS `gus`');
			db_execute('DROP TABLE IF EXISTS `gus1`');

			/* for large systems, it is important to purge the grid_job_interval_stats daily */
			db_execute('CREATE TABLE `gus` LIKE `grid_job_interval_stats`');

			/* use myisam as this table grows very large with many indexes */
			db_execute('ALTER TABLE `gus` ENGINE="MyISAM"');
			db_execute('INSERT INTO `gus` SELECT * FROM `grid_job_interval_stats` WHERE date_recorded>NOW()-INTERVAL 1 hour');
			db_execute('RENAME TABLE `grid_job_interval_stats` TO `gus1`, `gus` TO `grid_job_interval_stats`');
			db_execute('DROP TABLE IF EXISTS `gus1`');

			$interval_purged = $jobs_interval_stat_rows - 100000;
		} else {
			while (1) {
				$rows_to_delete = db_fetch_cell_prepared("SELECT count(*)
					FROM grid_job_interval_stats
					WHERE date_recorded<?", array($detail_retention_date));

				if ($rows_to_delete > 0) {
					db_execute_prepared("DELETE
						FROM grid_job_interval_stats
						WHERE date_recorded<?
						LIMIT $delete_size", array($detail_retention_date));
				} else {
					break;
				}

				$interval_purged += $rows_to_delete;
			}
		}

		grid_debug('Start deleting daily interval records from the database.');

		/* remove stale records from the poller database */
		$daily_retention = read_config_option('grid_daily_data_retention');

		if (strlen($daily_retention)) {
			$daily_retention_date = date('Y-m-d H:i:s', strtotime("-$daily_retention"));
		} else {
			$daily_retention_date = date('Y-m-d H:i:s', strtotime('-1 Year'));
		}

		cacti_log("NOTE: The last date for Daily Records will be '$daily_retention_date'", true, 'GRID');
		if (read_config_option('grid_partitioning_enable') == '') {
			db_execute_prepared("DELETE
				FROM grid_job_daily_stats
				WHERE date_recorded < ?", array($daily_retention_date));
		} else {
			/* determine if a new partition needs to be created */
			if (partition_timefor_create('grid_job_daily_stats', 'interval_end')) {
				partition_create('grid_job_daily_stats', 'interval_start', 'interval_end');
			}
			/* remove old partitions if required */
			grid_debug("Pruning Partitions for 'grid_job_daily_stats'");
			partition_prune_partitions('grid_job_daily_stats');
		}


		/* get the number of deleted rows */
		$dstat_rows_deleted = db_affected_rows();

		/* let's delete old records, but let's not delete records that are not finished */
		grid_debug('Started deleting old records from the main jobs database.');

		$rrd_cache_dir         = read_config_option('grid_cache_dir');
		$unlink_count          = 0;
		$jobs_rows_deleted     = 0;

		if ((read_config_option('grid_partitioning_enable') == '') ||
			(strtolower(read_config_option('grid_archive_rrd_files')) == 'on')) {

			/* create temporary table to handle deleted records */
			db_execute("CREATE TEMPORARY TABLE `simon` (
				`jobid` bigint(20) unsigned NOT NULL default '0',
				`indexid` int(10) unsigned NOT NULL default '0',
				`clusterid` int(10) unsigned NOT NULL default '0',
				`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
				`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
				`last_updated` timestamp NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`),
				KEY `alt_primary` (`clusterid`,`jobid`,`indexid`,`submit_time`))
				ENGINE=InnoDB DEFAULT CHARSET=latin1;");

			db_execute("CREATE TEMPORARY TABLE `gus` (
				`jobid` bigint(20) unsigned NOT NULL default '0',
				`indexid` int(10) unsigned NOT NULL default '0',
				`clusterid` int(10) unsigned NOT NULL default '0',
				`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
				`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
				`last_updated` timestamp NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`),
				KEY `alt_primary` (`clusterid`,`jobid`,`indexid`,`submit_time`))
				ENGINE=InnoDB DEFAULT CHARSET=latin1;");

			/* add jobs to be deleted into the temporary table */
			grid_debug('Placing job records whose full details are to be purged into temporary table for future use.');
			db_execute_prepared("INSERT INTO `simon` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
				SELECT
					jobid,
					indexid,
					clusterid,
					submit_time,
					end_time,
					last_updated
				FROM grid_jobs_finished
				WHERE end_time < ?", array($summary_retention_date));

			grid_debug("There are '" . db_affected_rows() . "' Job Records to Delete");

			$max_date = db_fetch_cell('SELECT MAX(end_time) FROM simon');

			/* add jobs whose rusage must be purged to the gus table */
			grid_debug('Placing job records whose rusage is to be purged into temporary table for future use.');
			db_execute_prepared("INSERT INTO `gus` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
				SELECT
					jobid,
					indexid,
					clusterid,
					submit_time,
					end_time,
					last_updated
				FROM grid_jobs_finished
				WHERE end_time < ?", array($detail_retention_date));
		}

		cacti_log("NOTE: The last date for Job Records will be '$summary_retention_date'", true, 'GRID');

		if (read_config_option('grid_partitioning_enable') == '') {
			if (rtm_strlen($max_date)) {
				while (1) {
					grid_debug("Deleting <= '$delete_size' Records from grid_jobs_finished");
					$deleted_rows = db_fetch_assoc_prepared("SELECT clusterid, jobid,
							indexid, submit_time FROM grid_jobs_finished
							WHERE end_time< ? LIMIT $delete_size", array($max_date));

					/* delete from the jobs table */
					db_execute_prepared("DELETE FROM grid_jobs_finished
						WHERE end_time < ?
						LIMIT $delete_size", array($max_date));

					grid_debug("Deleting Records from job subtable: grid_jobs_jobhosts/grid_jobs_reqhosts/grid_jobs_pendreasons.");
					foreach ($deleted_rows as $deleted_row) {
						db_execute_prepared("DELETE FROM grid_jobs_jobhosts_finished
							WHERE clusterid=?  AND submit_time=?  AND jobid=?  AND indexid=?",
							array($deleted_row['clusterid'], $deleted_row['submit_time'], $deleted_row['jobid'], $deleted_row['indexid']));

						db_execute_prepared("DELETE FROM grid_jobs_reqhosts_finished
							WHERE clusterid=?  AND submit_time=?  AND jobid=?  AND indexid=?",
							array($deleted_row['clusterid'], $deleted_row['submit_time'], $deleted_row['jobid'], $deleted_row['indexid']));

						db_execute_prepared("DELETE FROM grid_jobs_pendreasons_finished
							WHERE clusterid=?  AND submit_time=?  AND jobid=?  AND indexid=?",
							array($deleted_row['clusterid'], $deleted_row['submit_time'], $deleted_row['jobid'], $deleted_row['indexid']));

						db_execute_prepared("DELETE FROM grid_jobs_sla_loaning_finished
							WHERE clusterid=?  AND submit_time=?  AND jobid=?  AND indexid=?",
							array($deleted_row['clusterid'], $deleted_row['submit_time'], $deleted_row['jobid'], $deleted_row['indexid']));
					}

					/* get the number of deleted rows */
					$jobs_rows_deleted += db_affected_rows();

					if (cacti_sizeof($deleted_rows) == 0) {
						break;
					}
				}
			} else {
				grid_debug('No Job Records found to Delete');
			}
		} else {
			/*Only delete duplicated records when there is no optimization runs*/
			if (read_config_option('run_optimization') <= 0) {
				/* determine if a new partition needs to be created */
				if (partition_timefor_create('grid_jobs_finished', 'end_time')) {
					partition_create('grid_jobs_finished', 'start_time', 'end_time');
					rm_duplicated_records('grid_jobs_finished');
					if (read_config_option('grid_jobs_finished_partitioning_version', 'true')) {
						$job_finished_partition_version = read_config_option('grid_jobs_finished_partitioning_version', 'TRUE');
					}
					partition_create('grid_jobs_jobhosts_finished', 'submit_time', 'submit_time', $job_finished_partition_version);
					rm_duplicated_records('grid_jobs_jobhosts_finished');
					partition_create('grid_jobs_reqhosts_finished', 'submit_time', 'submit_time', $job_finished_partition_version);
					rm_duplicated_records('grid_jobs_reqhosts_finished');
					partition_create('grid_jobs_pendreasons_finished', 'submit_time', 'submit_time', $job_finished_partition_version);
					rm_duplicated_records('grid_jobs_pendreasons_finished');
					partition_create('grid_jobs_sla_loaning_finished', 'submit_time', 'submit_time', $job_finished_partition_version);
					rm_duplicated_records('grid_jobs_sla_loaning_finished');
				}
			}

			/* remove old partitions if required */
			grid_debug("Pruning Partitions for 'grid_jobs_finished'");
			partition_prune_partitions('grid_jobs_finished');
			grid_debug("Pruning Partitions for 'grid_jobs_jobhosts_finished'");
			partition_prune_partitions('grid_jobs_jobhosts_finished');
			grid_debug("Pruning Partitions for 'grid_jobs_reqhosts_finished'");
			partition_prune_partitions('grid_jobs_reqhosts_finished');
			grid_debug("Pruning Partitions for 'grid_jobs_pendreasons_finished'");
			partition_prune_partitions('grid_jobs_pendreasons_finished');
			grid_debug("Pruning Partitions for 'grid_jobs_sla_loaning_finished'");
			partition_prune_partitions('grid_jobs_sla_loaning_finished');
		}


		/* delete from the arrays */
		if (read_config_option('grid_partitioning_enable') == '') {
			grid_debug('Deleting Job Array Records');
			db_execute('DELETE FROM grid_arrays_finished USING grid_arrays_finished, simon
				WHERE grid_arrays_finished.clusterid=simon.clusterid
				AND grid_arrays_finished.jobid=simon.jobid
				AND grid_arrays_finished.submit_time=simon.submit_time');
		} else {
			if (partition_timefor_create('grid_arrays_finished', 'last_updated')) {
				partition_create('grid_arrays_finished', 'submit_time', 'last_updated');

				/* transfer active arrays to non-partition, and remove active
				 * arrays from the active partitions
				 */
				$partition = partition_getlatest('grid_arrays_finished');
			}

			/* remove old partitions if required */
			grid_debug("Pruning Partitions for 'grid_arrays_finished'");
			partition_prune_partitions('grid_arrays_finished');
		}


		/* let's make sure we don't delete any rusage records for running jobs */
		/* this query could take a while with 500k+ job records */
		grid_debug('Start deleting job rusage records.');

		if ((read_config_option('grid_partitioning_enable') == '') ||
			(strtolower(read_config_option('grid_archive_rrd_files')) == 'on')) {

			grid_debug('Resetting Temporary Tables');
			db_execute('TRUNCATE simon');
			db_execute('INSERT INTO simon SELECT * FROM gus');
			db_execute('TRUNCATE gus');

			/* start rrdtool, in case we are making rrdfiles */
			$rrdtool_pipe = rrd_init();

			$rusage_jobs        = db_fetch_cell('SELECT COUNT(*) FROM simon');
			$rusage_purged      = 0;
			$host_rusage_purged = 0;
			$gpu_rusage_purged  = 0;
			$modulus            = 1000;

			grid_debug('Deleting Rusage records now.');
			if ($rusage_jobs > 0) {
				if (strtolower(read_config_option('grid_archive_rrd_files')) == 'on') {
					/* let's handle the job records that have no rusage first */
					$min_submit_time = db_fetch_cell('SELECT MIN(submit_time) FROM grid_jobs_rusage');
					$min_simon_time  = db_fetch_cell('SELECT MIN(submit_time) FROM simon');

					/* delete simon records for jobs that have no rusage */
					if (strtotime($min_simon_time) < strtotime($min_submit_time)) {
						while (1) {
							/* run in small batches */
							db_execute_prepared("DELETE FROM simon
								WHERE submit_time<?
								LIMIT 10000", array($min_submit_time));

							if (db_affected_rows() == 0) {
								break;
							}
						}
					}

					$i = 0;
					while (1) {
						$job = db_fetch_row('SELECT * FROM simon LIMIT 1');
						if (db_affected_rows() == 0) {
							break;
						}

						/* create/update RRD files if the user has choosen to do so */
						$rusage_records = db_fetch_row_prepared("SELECT *
							FROM grid_jobs_rusage
							WHERE clusterid= ?
							AND jobid= ?
							AND indexid= ?
							AND submit_time= ?
							LIMIT 1", array($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"]));

						if ($rusage_records) {
							grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
								strtotime($job['submit_time']), 'relative', $rrdtool_pipe);
							grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
								strtotime($job['submit_time']), 'absolute', $rrdtool_pipe);

							if (read_config_option('grid_partitioning_enable') == '') {
								db_execute_prepared("DELETE
									FROM grid_jobs_rusage
									WHERE clusterid=?
									AND jobid=?
									AND indexid=?
									AND submit_time=?",
									array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));
							}

							$rusage_purged += db_affected_rows();
						}
						/* create/update RRD files if the user has choosen to do so */
						$host_rusage_record = db_fetch_row_prepared("SELECT host
							FROM grid_jobs_host_rusage
							WHERE clusterid= ?
							AND jobid= ?
							AND indexid= ?
							AND submit_time= ?
							GROUP BY host LIMIT 1", array($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"]));

						if ($host_rusage_record) {
							grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
								strtotime($job['submit_time']), 'relative', $rrdtool_pipe, $host_rusage_record['host']);
							grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
								strtotime($job['submit_time']), 'absolute', $rrdtool_pipe, $host_rusage_record['host']);

							if (read_config_option('grid_partitioning_enable') == '') {
								db_execute_prepared("DELETE
									FROM grid_jobs_host_rusage
									WHERE clusterid=?
									AND jobid=?
									AND indexid=?
									AND submit_time=?",
									array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));
							}

							$host_rusage_purged += db_affected_rows();
						}

						//TODO: ignore grid_jobs_gpu_rusage operation because 'option.grid_archive_rrd_files' is obsoleted

						db_execute_prepared("DELETE
							FROM simon
							WHERE clusterid=?
							AND jobid=?
							AND indexid=?
							AND submit_time=?",
							array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

						$i++;

						if (($i % $modulus) == 0) {
							grid_debug("Checked '$i' Jobs for RUsage Data");
						}
					}
				} else {
					if (read_config_option('grid_partitioning_enable') == '') {
						while (1) {
							/* add jobs to be deleted into the temporary table */
							db_execute("INSERT INTO `gus` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
								SELECT
									jobid,
									indexid,
									clusterid,
									submit_time,
									end_time,
									last_updated
								FROM simon
								LIMIT $delete_size");

							if (db_affected_rows() == 0) {
								break;
							}

							grid_debug("Deleting <= '$delete_size' Job Rusage Records from grid_jobs_rusage");

							/* delete rusage records */
							db_execute("DELETE FROM grid_jobs_rusage
								USING grid_jobs_rusage, gus
								WHERE grid_jobs_rusage.clusterid=gus.clusterid
								AND grid_jobs_rusage.jobid=gus.jobid
								AND grid_jobs_rusage.indexid=gus.indexid
								AND grid_jobs_rusage.submit_time=gus.submit_time");

							$rusage_purged += db_affected_rows();

							grid_debug("Deleting <= '$delete_size' Job hostRusage Records from grid_jobs_host_rusage");

							/* delete hostrusage records */
							db_execute('DELETE FROM grid_jobs_host_rusage
								USING grid_jobs_host_rusage, gus
								WHERE grid_jobs_host_rusage.clusterid=gus.clusterid
								AND grid_jobs_host_rusage.jobid=gus.jobid
								AND grid_jobs_host_rusage.indexid=gus.indexid
								AND grid_jobs_host_rusage.submit_time=gus.submit_time');

							$host_rusage_purged += db_affected_rows();

							grid_debug("Deleting <= '$delete_size' Job GPURusage Records from grid_jobs_gpu_rusage");

							/* delete hostrusage records */
							db_execute("DELETE FROM grid_jobs_gpu_rusage
								USING grid_jobs_gpu_rusage, gus
								WHERE grid_jobs_gpu_rusage.clusterid=gus.clusterid
								AND grid_jobs_gpu_rusage.jobid=gus.jobid
								AND grid_jobs_gpu_rusage.indexid=gus.indexid
								AND grid_jobs_gpu_rusage.submit_time=gus.submit_time");

							$gpu_rusage_purged += db_affected_rows();

							/* delete temporary records */
							db_execute('DELETE FROM simon
								USING simon, gus
								WHERE simon.clusterid=gus.clusterid
								AND simon.jobid=gus.jobid
								AND simon.indexid=gus.indexid
								AND simon.submit_time=gus.submit_time');

							db_execute('TRUNCATE TABLE gus');
						}
					}
				}
			} else {
				grid_debug('No more records found to delete, continuing');
			}

			/* remove temporary table */
			db_execute('DROP TABLE IF EXISTS simon');
			db_execute('DROP TABLE IF EXISTS gus');
		} elseif (read_config_option('grid_partitioning_enable') == 'on') {
			$rusage_purged      = 0;
			$host_rusage_purged = 0;
			$gpu_rusage_purged  = 0;

			if (partition_timefor_create('grid_jobs_rusage', 'update_time')) {
				partition_create('grid_jobs_rusage', 'submit_time', 'update_time');

				/* let's make sure new partitions are using Innodb engine */
				alter_table_engine('grid_jobs_rusage', 'Innodb');
			}
			if (partition_timefor_create('grid_jobs_host_rusage', 'update_time')) {
				partition_create('grid_jobs_host_rusage', 'submit_time', 'update_time');

				/* let's make sure new partitions are using Innodb engine */
				alter_table_engine('grid_jobs_host_rusage', 'Innodb');
			}

			if (partition_timefor_create("grid_jobs_gpu_rusage", "update_time")) {
				partition_create("grid_jobs_gpu_rusage", "submit_time", "update_time");

				//TODO: Should take similar action for grid_jobs_rusage/grid_jobs_host_rusage
				//      To Keep one job rusage data into one partition table.
				foreach($new_partition_tables as $new_partition_table){
					if(strstr($new_partition_table , "grid_jobs_gpu_rusage")){
						//keep history for the requeue job which use less GPU after requeue.
						$new_grid_jobs_finished = "grid_jobs_finished";
						if($job_finished_partition_version != -1)
							$new_grid_jobs_finished .= "_v" . $job_finished_partition_version;
						db_execute("INSERT IGNORE INTO grid_jobs_gpu_rusage (clusterid, jobid, indexid, submit_time,
							start_time, update_time, host, gpu_id, exec_time, energy, sm_ut_avg, sm_ut_max,
							sm_ut_min, mem_ut_avg, mem_ut_max, mem_ut_min, gpu_mused_max)
							SELECT gjgr.clusterid, gjgr.jobid, gjgr.indexid, gjgr.submit_time,
								gjgr.start_time, gjgr.update_time, gjgr.host, gjgr.gpu_id, gjgr.exec_time, gjgr.energy, gjgr.sm_ut_avg,
								gjgr.sm_ut_max, gjgr.sm_ut_min, gjgr.mem_ut_avg, gjgr.mem_ut_max, gjgr.mem_ut_min, gjgr.gpu_mused_max
							FROM $new_partition_table as gjgr
							LEFT JOIN $new_grid_jobs_finished as grid_jobs
							ON grid_jobs.clusterid=gjgr.clusterid
							AND grid_jobs.jobid=gjgr.jobid
							AND grid_jobs.indexid=gjgr.indexid
							AND grid_jobs.submit_time=gjgr.submit_time
							WHERE grid_jobs.jobid IS NULL;");

						//delete duplicate records for the jobs that run cross partitions.
						db_execute("DELETE FROM gjgr USING $new_partition_table as gjgr
							LEFT JOIN $new_grid_jobs_finished as grid_jobs
							ON grid_jobs.clusterid=gjgr.clusterid
							AND grid_jobs.jobid=gjgr.jobid
							AND grid_jobs.indexid=gjgr.indexid
							AND grid_jobs.submit_time=gjgr.submit_time
							WHERE grid_jobs.jobid IS NULL;");
					}
				}

				/* let's make sure new partitions are using Innodb engine */
				alter_table_engine("grid_jobs_gpu_rusage", "Innodb");
			}

			/* remove old partitions if required */
			grid_debug("Pruning Partitions for 'grid_jobs_rusage'");
			partition_prune_partitions('grid_jobs_rusage');
			grid_debug("Pruning Partitions for 'grid_jobs_host_rusage'");
			partition_prune_partitions('grid_jobs_host_rusage');
			grid_debug("Pruning Partitions for 'grid_jobs_gpu_rusage'");
			partition_prune_partitions("grid_jobs_gpu_rusage");
		}
	}

	/* maint db partition done*/
	set_config_option('grid_db_maint_process_status', '4');

	/* lastly optimize the tables */
	if ($maint_process_status == 0) {
		/*
			lastly optimize the tables. There are two optimization schedules.
			1. run_optimization - run scdeduled optimization based on maint -> optimization scheule setting (once a month by default)
			2. run_partition_optimization - All newly created partition tables should be optimized right away in daily maint period.
		*/
		if ($optimize) {
			set_config_option('run_optimization', '1');
		}

		if (cacti_sizeof($new_partition_tables)) {
			//remove table grid_job_daily_stats_vxxx, because they do not need to be optimized
			foreach($new_partition_tables as $key => $new_partition_table) {
				if (strpos($new_partition_table, "grid_job_daily_stats")) {
					unset($new_partition_tables[$key]);
				}
			}

			$new_partition_tables_1 = implode(',', $new_partition_tables);
			set_config_option('run_partition_optimization', $new_partition_tables_1);
		}

		if (read_config_option('gridmemvio_enabled') == 'on') {
			$php_binary_path = read_config_option('path_php_binary');
			exec_background($php_binary_path, dirname(__FILE__) . '/../poller_memvio_notify.php');
		}

		/* remove old data query graphs */
		grid_debug('Removing Stale Pending Reasons');
		if (detect_and_correct_running_processes(0, 'REMOVING_STALE_PENDING_REASONS', read_config_option('grid_db_maint_max_time'))) {
			db_execute('DELETE gjp
				FROM grid_jobs_pendreasons AS gjp
				LEFT JOIN grid_jobs as gj
				ON gjp.clusterid=gj.clusterid
				AND gjp.jobid=gj.jobid
				AND gjp.indexid=gj.indexid
				AND gjp.submit_time=gj.submit_time
				WHERE gj.jobid IS NULL');
		}

		/* remove old data query graphs */
		grid_debug('Removing Aged Cluster Graphs');
		grid_remove_old_graphs();

		/* let's rebuild the queue distribution data */
		grid_debug('Rebuilding Queue Distribution Table');
		rebuild_queue_distrib_table();

		/* remove stale records from the job and requested hosts table */
		purge_from_and_request_hosts();

	}
	/* place a timestamp in the database to help detect a abarrent maintenance run */
	set_config_option('grid_db_maint_end_time', time());

	/* all db maint process done*/
	set_config_option('grid_db_maint_process_status', '0');

	/* take time and log performance data */
	$end_time = microtime(true);
	/*Quick temp fix for RTC#227730 */
	if(!isset($jobs_rows_deleted))  $jobs_rows_deleted  = 0;
	if(!isset($interval_purged))    $interval_purged    = 0;
	if(!isset($dstat_rows_deleted)) $dstat_rows_deleted = 0;
	if(!isset($rusage_purged))      $rusage_purged      = 0;
	if(!isset($host_rusage_purged)) $host_rusage_purged = 0;
	if (!isset($gpu_rusage_purged))  $gpu_rusage_purged  = 0;
	/*Temp fix end*/
	$cacti_stats = sprintf(
		"REPLACE INTO settings SET name='stats_grid_maint_details', value='Time:%01.4f " .
		"JobDailyStatAdded:%s " .
		"JobsPurged:%s " .
		"JobIntervalPurged:%s " .
		"JobDailyStatPurged:%s " .
		"JobsRusagePurged:%s " .
		"JobsHostRusagePurged:%s " .
		"JobsGPURusagePurged:%s'",
		$end_time-$begin_time,
		number_format_i18n($daily_added),
		number_format_i18n($jobs_rows_deleted),
		number_format_i18n($interval_purged),
		number_format_i18n($dstat_rows_deleted),
		number_format_i18n($rusage_purged),
		number_format_i18n($host_rusage_purged),
		number_format_i18n($gpu_rusage_purged)
	);

	db_execute($cacti_stats);
}

function get_days($timespan) {
	if (empty($timespan)) {
		return 0;
	}
	if (substr_count($timespan, 'day')) {
		$group_function = 1;
	} elseif (substr_count($timespan, 'week')) {
		$group_function = 7;
	} elseif (substr_count($timespan, 'month')) {
		$group_function = 31;
	} elseif (substr_count($timespan, 'quarter')) {
		$group_function = 93;
	} elseif (substr_count($timespan, 'year')) {
		$group_function = 365;
	} else {
		if (is_numeric($timespan)) {//for 232185 issue 1
			return $timespan;
		} else {
			return 0;
		}
	}
	$span = substr($timespan, 0, 1);
	return $span * $group_function;
}

function grid_remove_old_graphs() {
	global $debug, $config;

	include_once($config['library_path'] . '/api_graph.php');
	include_once($config['library_path'] . '/api_data_source.php');

	$cluster_hosts = array_rekey(db_fetch_assoc('SELECT DISTINCT clusterid, cacti_host FROM grid_clusters'), 'clusterid', 'cacti_host');

	$static = read_config_option('grid_graph_purge_static');
	if ($static == '-1') {
		/* do nothing, static deletion disabled */
	} else {
		/* execute queues first */
		grid_debug('Removing old Queue Graphs');
		find_purge_graphs('gridQname', $cluster_hosts, 'grid_queues_stats', 'queue', $static);

		/* next do the host groups */
		grid_debug('Removing old Host Group Graphs');
		find_purge_graphs('groupName', $cluster_hosts, 'grid_hostgroups_stats', 'groupName', $static);

		/* next applications */
		grid_debug('Removing old Application Graphs');
		find_purge_graphs('appName', $cluster_hosts, 'grid_applications', 'appName', $static);
	}

	$transi = read_config_option('grid_graph_purge_transient');
	if ($transi == '-1') {
		/* do nothing, transient deletion disabled */
	} else {
		$transi=get_days($transi);
		/* execute users first */
		grid_debug('Removing old User Graphs');
		find_purge_graphs('user', $cluster_hosts, 'grid_users_or_groups', 'user_or_group', $transi);

		/* next user groups */
		grid_debug('Removing old User Group Graphs');
		find_purge_graphs('groupName', $cluster_hosts, 'grid_user_group_stats', 'userGroup', $transi);

		/* next projects */
		grid_debug('Removing old Project Graphs');
		find_purge_graphs('projectLevel1', $cluster_hosts, 'grid_projects', 'projectName', $transi);
		find_purge_graphs('projectName', $cluster_hosts, 'grid_projects', 'projectName', $transi);

		/* next license projects */
		grid_debug('Removing old License Project Graphs');
		find_purge_graphs('licenseProject', $cluster_hosts, 'grid_license_projects', 'licenseProject', $transi);

		/* next job groups */
		grid_debug('Removing old Job Group Graphs');
		find_purge_graphs('groupName', $cluster_hosts, 'grid_groups', 'groupName', $transi);
	}
}

function find_purge_graphs($variable, $cluster_hosts, $table, $column, $time) {
	global $debug, $config;

	if (cacti_sizeof($cluster_hosts) == 0)
		return;

	switch($table) {
		case 'grid_users_or_groups':
			$addsql = "AND type='U'";
			break;
		default:
			$addsql = '';
			break;
	}

	$date = date('Y-m-d H:i:s', time()-($time*86400));

	$local_graph_ids = array();
	foreach ($cluster_hosts as $clusterid => $host_id) {
		if (empty($host_id)) {
			continue;
		}
		$snmp_queries = db_fetch_assoc_prepared("SELECT snmp_query_id FROM host_snmp_query WHERE sort_field=? AND host_id=?", array($variable, $host_id));

		if (cacti_sizeof($snmp_queries)==0) {
			continue;
		}

		$lists = db_fetch_assoc_prepared("SELECT $column AS snmp_index
			FROM $table
			WHERE clusterid=? AND last_updated<? $addsql", array($clusterid, $date));

		if (cacti_sizeof($lists)) {
			$list_5k = array_chunk($lists, 5000);

			foreach ($snmp_queries as $query) {
				foreach ($list_5k as $items) {
					$nq = array();
					foreach ($items as $item) {
						$nq[] = $item;
					}
					$array_sql = rtm_array_to_sql_or($nq, "snmp_index");

					$local_graph_ids += array_rekey(db_fetch_assoc_prepared("SELECT id
						FROM graph_local
						WHERE host_id=? AND snmp_query_id=?
						AND " . $array_sql, array($host_id, $query["snmp_query_id"])), "id", "id");
				}
			}
			db_execute_prepared("DELETE FROM $table WHERE clusterid=? AND last_updated<? $addsql ", array($clusterid, $date));
			if ($table == "grid_user_group_stats") {
				db_execute_prepared("DELETE FROM grid_user_group_members
							WHERE clusterid = ?
							AND present = 0
							AND groupname
							IN (SELECT user_or_group FROM grid_users_or_groups
								WHERE clusterid = ?
								AND last_updated < ?
								AND type = 'G'
								AND present = 0 )", array($clusterid,$clusterid,$date));
				db_execute_prepared("DELETE FROM grid_users_or_groups
					WHERE clusterid=? AND last_updated<? AND type='G'", array($clusterid, $date));
			}
		}
	}

	if (cacti_sizeof($local_graph_ids)) {
		grid_purge_graphs($local_graph_ids);
	}
}

function grid_purge_graphs($local_graph_ids) {
	global $debug, $config;

include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/api_data_source.php');

	$data_sources_to_purge = array();

	if (cacti_sizeof($local_graph_ids)) {
		foreach ($local_graph_ids as $local_graph_id) {
			grid_debug("Removing old Graph '" . get_graph_title($local_graph_id) . "'");
			$data_sources = array_rekey(db_fetch_assoc_prepared("SELECT data_template_data.local_data_id
				FROM (data_template_rrd, data_template_data, graph_templates_item)
				WHERE graph_templates_item.task_item_id=data_template_rrd.id
				AND data_template_rrd.local_data_id=data_template_data.local_data_id
				AND graph_templates_item.local_graph_id= ?
				AND data_template_data.local_data_id>0", array($local_graph_id)), "local_data_id", "local_data_id");

			$data_sources_to_purge = array_merge($data_sources_to_purge, $data_sources);

			if (cacti_sizeof($data_sources_to_purge) > 500) {
				api_data_source_remove_multi($data_sources_to_purge);
				$data_sources_to_purge = array();
			}
		}

		if (cacti_sizeof($data_sources_to_purge)) {
			api_data_source_remove_multi($data_sources_to_purge);
			$data_sources_to_purge = array();
		}

		api_graph_remove_multi($local_graph_ids);
	}
}

function bubblesortBackups(&$backups){
	$temp = array();

	for ($i=0; $i < count($backups); $i++) {
		for($j=0; $j < count($backups) - $i - 1; $j++) {
			if ($backups[$j]["fm_time"] > $backups[$j+1]["fm_time"]) {
				$temp = $backups[$j];
				$backups[$j] = $backups[$j+1];
				$backups[$j+1] = $temp;
			}
		}
	}
}

function grid_backup_cacti_db($poller = true, $force = false, $backup_path = '') {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $debug;
	global $database_port;
	global $config;
	include_once($config["base_path"] . "/lib/utility.php");
	include_once($config["base_path"] . "/plugins/grid/include/grid_constants.php");
	if ((read_config_option("grid_backup_enable") == "on") || ($force)) {
		$now = time();
		$day_of_week = date("w", $now);
		$backup_db = false;
		if (read_config_option("grid_backup_schedule")) {
			if ((read_config_option("grid_backup_schedule") == "w") &&
				(read_config_option("grid_backup_weekday") == $day_of_week)) {
				$backup_db = true;
			} elseif (read_config_option("grid_backup_schedule") == "d") {
				$backup_db = true;
			}
		}
		/* check to see if now it time to backup */
		if (($backup_db) || ($force)) {
			/* disable polling */
			if (grid_set_maintenance_mode("BACKUP", false)) {
				$i                    = 0;
				$max_generation       = -1;
				$unlink_oldest        = false;
				$old_filename_cacti   = "";
				$old_filename_mysql   = "";
				$old_filename_tgz     = "";
				$old_file_date        = 99999999999;
				$backups              = array();
				$newest_file_date     = 0;
				/* open the directory */
				if (!empty($backup_path)) {
					$d = dir($backup_path);
				} else {
					$d = dir(read_config_option("grid_backup_path"));
					$backup_path = read_config_option("grid_backup_path");
				}
				/* check if directory exists */
				if ($d) {
					/* check to see how many backups we have */
					while (false !== ($entry = $d->read())) {
						if (substr_count($entry, "cacti_db_backup_")) {
							$backups[$i]["fm_time"] = filemtime($backup_path . "/" . $entry);
							if ($config["cacti_server_os"] == "win32") {
								$backups[$i]["generation"] = str_replace(".zip", "", str_replace("cacti_db_backup_", "", $entry));
							} else {
										$backups[$i]["generation"] = str_replace(".tgz", "", str_replace("cacti_db_backup_", "", $entry));
							}
							$backups[$i]["filename"] = $entry;
							/* look for the last generation by date */
							if ($backups[$i]["fm_time"] > $newest_file_date) {
								$max_generation    = $backups[$i]["generation"];
								$newest_file_date  = $backups[$i]["fm_time"];
							}
							/* look for the oldest file */
							if ($backups[$i]["fm_time"] < $old_file_date) {
								$old_file_date    = $backups[$i]["fm_time"];
								$old_filename_tgz = $backup_path . "/" . $entry;
							}
							$i++;
						}
					}
					$d->close();
					/* check if we need to delete the oldest file after the backup is complete */
					$generations = cacti_sizeof($backups);
					$backup_generations = read_config_option("grid_backup_generations");
					if ($generations >= $backup_generations) {
						$unlink_oldest = true;
						//Sort the backups array and delete the oldest $delete_generations backup files.
						$delete_generations = $generations - $backup_generations + 1;
						bubblesortBackups($backups);
					}
					if ($max_generation >= 99) {
						$max_generation = -1;
					}
					/* set the next generation */
					$max_generation++;
					$next_generation = $max_generation % 100;
					if ($next_generation < 10) {
						$next_generation = "0" . $next_generation;
					}
					if ($config["cacti_server_os"] == "win32") {
						$tmp_backup_dir = getenv("TEMP") . "\\cacti_backup";
					} else {
						$tmp_backup_dir = "/tmp/cacti_backup";
					}
					/* remove the directory structure if it exists */
					if (is_dir($tmp_backup_dir)) {
						$result=rmdirr($tmp_backup_dir);
						$old_tmp_backup_dir = $tmp_backup_dir;
						if (!$result) {
							$old_tmp_backup_dir = $tmp_backup_dir;
							$tmp_backup_dir = $tmp_backup_dir ."_". time() ."/cacti_backup";
							cacti_log("WARNING: Can not remove the existed directory" .$old_tmp_backup_dir. " failed and change the directory to " .$tmp_backup_dir);
						} else {
							cacti_log("WARNING: Remove the existed directory" .$old_tmp_backup_dir. " successfully");
						}
					}
					if (!mkdir($tmp_backup_dir, 0755,true)) {
						cacti_log("ERROR: Cacti Database Backup Failed!  Unable to create temporary bcakup dir $tmp_backup_dir.", true, "GRID");
					} else {
						if ($config["cacti_server_os"] == "win32") {
							$backup_file_tgz      = $backup_path . "/cacti_db_backup_"        . $next_generation . ".zip";
						} else {
							$backup_file_tgz      = $backup_path . "/cacti_db_backup_"        . $next_generation . ".tgz";
						}
						$backup_file_cacti        = $tmp_backup_dir . "/cacti_db_backup.sql";
						$backup_file_cacti_struct = $tmp_backup_dir . "/cacti_db_struct_backup.sql";
						$backup_file_mysql        = $tmp_backup_dir . "/mysql_db_backup.sql";
						/* obtain a list of tables to backup */
						$temp_tables      = db_fetch_assoc("SHOW TABLES");
						$tables_to_backup = '';
						$tables_to_backup_struct = '';
						/* flag to backup database success or failure */
						$backup_database_success = true;
						/* get the backup method */
						$backup_method = read_config_option('grid_backup_method');
						if (empty($backup_method)) {
							$backup_method = 'q';
							set_config_option('grid_backup_method', 'q');
						}
						if (cacti_sizeof($temp_tables)) {
							foreach ($temp_tables as $table) {
								//Ignore temp table that is end with a timestamp string.
								if (!preg_match("/_\d{10}$/", $table['Tables_in_' . $database_default])) {
									$tables_to_backup_struct .= ($tables_to_backup_struct != '' ? ' ':'') . $table['Tables_in_' . $database_default];
								}

								$backup = true;
								switch ($table['Tables_in_' . $database_default]) {
									// Grid Tables
									case 'grid_jobs':
									case 'grid_jobs_finished':
									case 'grid_jobs_reqhosts':
									case 'grid_jobs_reqhosts_finished':
									case 'grid_jobs_jobhosts':
									case 'grid_jobs_jobhosts_finished':
									case 'grid_jobs_pendreasons':
									case 'grid_jobs_pendreasons_finished':
									case 'grid_jobs_sla_loaning':
									case 'grid_jobs_sla_loaning_finished':
									case 'grid_jobs_rusage':
									case 'grid_jobs_host_rusage':
									case 'grid_jobs_gpu_rusage':
									case 'grid_job_interval_stats':
									case 'grid_jobs_memvio':
									case 'grid_arrays':
									case 'grid_arrays_finished':
// Anything with last_updated 		case 'grid_queues_shares':
									case 'grid_job_daily_stats':
									case 'grid_job_daily_stats_replay':
									case 'grid_job_daily_user_stats':
									case 'grid_job_daily_usergroup_stats':
									case 'grid_processes':
									// Cacti Tables
									case 'poller_output':
									case 'poller_output_boost':
									// Syslog Tables
									case 'syslog':
									case 'syslog_removed':
									case 'syslog_incoming':
// Always backup these tables		// Disku Tables
// Check my math but theses are		case 'disku_directory_totals':
// relatively small tables?			case 'disku_directory_totals_history':
//									case 'disku_extension_totals':
//									case 'disku_extension_totals_history':
//									case 'disku_groups_totals':
//									case 'disku_groups_totals_history':
// 									case 'disku_users_totals':
//									case 'disku_users_totals_history':
//									case 'disku_files_raw':
									// License Tables
									case 'lic_services_feature_history':
									case 'lic_daily_stats_today':
									case 'lic_flexlm_servers_feature_details':
									case 'lic_flexlm_servers_feature_use':
									case 'lic_daily_stats':
									case 'lic_daily_stats_today':
// Critical table					case 'lic_daily_stats_traffic':
									case 'lic_flexlm_log':
									case 'lic_interval_stats':
									case 'lic_lum_events':
									case 'lic_services_feature_history_mapping':
									case 'lic_services_feature_history':
									// Heuristics Tables
									case 'grid_heuristics':
									case 'grid_heuristics_percentiles':
									case 'grid_heuristics_user_history_today':
									case 'grid_heuristics_user_history_yesterday':
									case 'grid_heuristics_user_stats':
// Pendhist tables must bakup		// Pendhist Tables
//									case 'grid_jobs_pendhist_daily':
//									case 'grid_jobs_pendhist_hourly':
//									case 'grid_jobs_pendhist_yesterday':
									case 'table_columns':
									case 'table_indexes ':
										if ($backup_method == 'q') {
											$backup = false;
										}
										break;
									default:
										break;
								}
								if ($backup) {
									/* don't backup grid partition tables */
									if (preg_match('/^grid_\S+_v[0-9]/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup license partition tables */
									if (preg_match('/^lic_\S+_v[0-9]/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup disku partition tables */
									if (preg_match('/^disku_\S+_v[0-9]/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup disku raw tables */
									if (preg_match('/^disku_files_raw_/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup license daily stats tables */
									if (preg_match('/^lic_daily_stats_/', $table['Tables_in_' . $database_default])) {
										if ($backup_method == 'q') {
											$backup = false;
										}
									}
									/* don't backup license interval stats tables regardless */
									if (preg_match('/^lic_interval_stats_/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup boost archive tables regardless */
									if (preg_match('/^poller_output_boost_/', $table['Tables_in_' . $database_default])) {
										$backup = false;
									}
									/* don't backup job pending history tables regardless */
									if (preg_match('/^grid_jobs_pendhist_hourly_/', $table['Tables_in_' . $database_default])) {
										if ($backup_method == 'q') {
											$backup = false;
										}
									}
									if (preg_match("/^grid_heuristics_user_history_today_/", $table["Tables_in_" . $database_default])) {
										$backup = false;
									}
									if ($backup) {
										$tables_to_backup .= ($tables_to_backup != '' ? ' ':'') . $table['Tables_in_' . $database_default];
									}
								}
							}
						} else {
							$backup_database_success = false;
						}
						/* perform the backup */
						if (is_writeable($backup_path) && $backup_database_success) {
							if ($config['cacti_server_os'] == 'win32') {
								$mysqldmp = read_config_option('path_mysql_bin') . '\\mysqldump.exe';
							} else {
								$mysqldmp = 'mysqldump';
							}
							$is_cluster_log_bin = db_fetch_row("SHOW VARIABLES LIKE 'log_bin'");
							//$is_cluster_wsrep_on = db_fetch_row("SHOW VARIABLES LIKE 'wsrep_on'");
							if (cacti_sizeof($is_cluster_log_bin) && $is_cluster_log_bin['Value'] == 'ON'){
								$is_cluster_log_bin = true;
							} else {
								$is_cluster_log_bin = false;
							}
							$is_cluster_grants = db_fetch_row("SHOW GRANTS FOR CURRENT_USER");
							$is_cluster_grant_reload = false;
							$is_cluster_grant_super = false;
							$is_cluster_grant_binlog_monitor = false;
							$is_cluster_grant_replication_client = false;

							foreach ($is_cluster_grants as $fname => $grant) {
								if (strpos($grant, 'RELOAD') !== false) {
									$is_cluster_grant_reload = true;
								}
								if (strpos($grant, 'SUPER') !== false) {
									$is_cluster_grant_super = true;
								}
								if (strpos($grant, 'BINLOG MONITOR') !== false) {
									$is_cluster_grant_binlog_monitor = true;
								}
								if (strpos($grant, 'REPLICATION CLIENT') !== false) {
									$is_cluster_grant_replication_client = true;
								}
							}
							if ($is_cluster_log_bin && $is_cluster_grant_reload
									&& ($is_cluster_grant_super || $is_cluster_grant_binlog_monitor || $is_cluster_grant_replication_client)) {
								$can_dump_master_data = true;
							} else {
								$can_dump_master_data = false;
							}

							$start_return = mysql_dump_no_passwd_check(cacti_escapeshellarg($database_username), cacti_escapeshellarg($database_password));
							$backup_command = cacti_escapeshellcmd($mysqldmp) .
								($start_return ? " --defaults-extra-file='$start_return'" : ' --user='     . cacti_escapeshellarg($database_username) .  ' --password=' . cacti_escapeshellarg($database_password)) .
								($can_dump_master_data ? ' --master-data' : '' ) .
								' --lock-tables=false' .
								' --host='     . cacti_escapeshellarg($database_hostname) .
								' --port='     . cacti_escapeshellarg($database_port) .
								($database_hostname == 'localhost' ? ' --protocol=socket' : '' ) .
								' -f'       .
								' '            . cacti_escapeshellarg($database_default)  .
								' '            . $tables_to_backup .
								' > '          . $backup_file_cacti;
							$result = grid_shell_exec($backup_command, $stdoutput, $stderror);
							if ($result) {
								cacti_log("ERROR: Cacti Database Backup Failed!  Message: '" . str_replace("\n", "; ", $stderror) . "'; ExitCode: '$result'", true, "GRID");
								$backup_database_success = false;
							} else {
								cacti_log("NOTE: Cacti Database Backup Successful!", true, "GRID");
							}
							$backup_command = cacti_escapeshellcmd($mysqldmp) .
								($start_return ? " --defaults-extra-file='$start_return'" : ' --user='     . cacti_escapeshellarg($database_username) .  ' --password=' . cacti_escapeshellarg($database_password)) .
								' --lock-tables=false'         .
								' --host='     . cacti_escapeshellarg($database_hostname) .
								' --port='     . cacti_escapeshellarg($database_port)     .
								($database_hostname == 'localhost' ? ' --protocol=socket' : '' ) .
								' -d -f'       .
								' '            . cacti_escapeshellarg($database_default)  .
								' '            . $tables_to_backup_struct .
								' > '          . $backup_file_cacti_struct;
							$result = grid_shell_exec($backup_command, $stdoutput, $stderror);
							if ($result) {
								cacti_log("ERROR: Cacti Database Structure Backup Failed!  Message: '" . str_replace("\n", "; ", $stderror) . "'; ExitCode: '$result'", true, "GRID");
								$backup_database_success = false;
							} else {
								cacti_log("NOTE: Cacti Database Structure Backup Successful!", true, "GRID");
							}
							//Backup RTM_ROOT/etc
							if ($config['cacti_server_os'] == 'win32') {
								$backup_command = "xcopy \"" . RTM_ROOT . "\\etc\" \"$tmp_backup_dir\\etc\" /SEVIY";
								$result = exec($backup_command);
							} else {
								$backup_command = 'cd ' . RTM_ROOT . '/etc && find ./ | cpio -mpdu '.$tmp_backup_dir.'/rtm/etc';
								$result = exec($backup_command);
								if (strlen($result)) {
									cacti_log('ERROR: ' . RTM_ROOT . "/etc Backup Failed!  Message is '$result'", true, 'GRID');
								} else {
									cacti_log('NOTE: ' . RTM_ROOT . '/etc Backup Successful!', true, 'GRID');
								}
							}
							// Touch VERSION file
							$grid_version = db_fetch_cell("SELECT value FROM settings WHERE name='grid_version'");
							if (!touch($tmp_backup_dir . '/' . $grid_version)) {
								cacti_log("ERROR: Unable to stamp grid version in backup dir! Message is '$result'", true, 'GRID');
							} else {
								cacti_log('NOTE: Stamp grid version in backup dir is Successful!', true, 'GRID');
							}
							//Tar up backup dir
							if ($config['cacti_server_os'] == 'win32') {
								$zip_cmd = read_config_option('path_7z');
								$backup_command = 'cd ' . dirname($tmp_backup_dir) . ' && ' . $zip_cmd . ' a -r '  . $backup_file_tgz . ' ' . basename($tmp_backup_dir);
							} else {
								$backup_command = 'cd ' . dirname($tmp_backup_dir) . ' && tar -czf ' . $backup_file_tgz . ' ' . basename($tmp_backup_dir);
							}
							$result = exec($backup_command);
							rmdirr($tmp_backup_dir);
							//Delete parent dir file if tmp dir using as $tmp_backup_dir ."_". time() ."/cacti_backup" format
							if (preg_match('/cacti_backup_+\d*/', $tmp_backup_dir)){
							    $parent_backup_dir = dirname($tmp_backup_dir);
							    rmdirr($parent_backup_dir);
							}
							if ($config['cacti_server_os'] == 'win32') {
								if (!substr_count($result, 'Everything is Ok')) {
									cacti_log("ERROR: Packing Backup Files Failed!  Message is '$result'", true, 'GRID');
								} else {
									cacti_log('NOTE: Packing Backup Files Successful!', true, 'GRID');
								}
							} else {
								if (strlen($result)) {
									cacti_log("ERROR: Packing Backup Files Failed!  Message is '$result'", true, 'GRID');
								} else {
									cacti_log('NOTE: Packing Backup Files Successful!', true, 'GRID');
								}
							}
							if ($unlink_oldest && $backup_database_success) {
								for ($i = 0; $i < $delete_generations; $i++) {
									@unlink($backup_path . "/" . $backups[$i]["filename"]);
								}
							} else if ($unlink_oldest) {
								//In case backup not success this time, but there are backup files need to be deleted.
								//Maybe the backup failed due to full disk space, so we need to delete the redundant files at first.
								//Because backup not success this time, deleting count should minus 1.
								for ($i = 0; $i < $delete_generations - 1; $i++) {
									@unlink($backup_path . "/" . $backups[$i]["filename"]);
								}
							}
						} else {
							cacti_log('ERROR: Cacti Database Backup Failed!  Backup Directory Not Writeable!', true, 'GRID');
						}
					}
				} else {
					cacti_log('FATAL: Unable to Access Backup Directory Location', true, 'GRID');
				}
				$backup_command = trim(read_config_option('grid_backup_command'));
				if (strlen($backup_command)) {
					$parts = explode(' ', $backup_command);
					if (is_readable(trim($parts[0])) && is_executable(trim($parts[0]))) {
						cacti_log("NOTE: Executing Post Backup Command '$backup_command'", false, 'GRID');
						$result = exec_background($backup_command);
					} else {
						cacti_log("WARNING: Unable to Execute Post Backup Command '" . $parts[0] . "' Is Either Not Readable or Not Executable", false, 'GRID');
					}
				}
				/* enable polling */
				grid_end_maintenance_mode("BACKUP");
				/* partition backup can be done in the background */
				if (read_config_option("grid_backup_partitions") > 1) {
					set_config_option('run_partition_backup', '1');
				}
			}
		} else {
			print 'NOTE: Not Time for Backup.  Use the Force Option to Override';
		}
	} else {
		print 'NOTE: Database Backup Disabled.  Use the Force Option to Override';
	}
}

function start_polling() {
	global $config;

include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

	/* enable polling */
	grid_end_maintenance_mode('BACKUP');

	// start lsfpollerd
	exec(LSFPOLLERD_START, $output, $retval);
}

function grid_restore_cacti_db($tgz_file) {
	global $config, $database_default, $database_username, $database_password, $database_hostname, $database_port, $messages;
	global $rtm;

	include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

	if ($tgz_file['error'] > 0) {
		raise_message(126);
		cacti_log('ERROR: File upload error: '.$tgz_file['error'], true, 'GRID');
	} else {
		// stop lsfpollerd
		exec(LSFPOLLERD_STOP, $output, $retval);

		$backupdir = 'cacti_backup';
		if ($config['cacti_server_os'] == 'win32') {
			$pathinfo  = pathinfo(getenv('TEMP') . '\\' . basename($tgz_file['name']));
		} else {
			$pathinfo  = pathinfo('/tmp/'.basename($tgz_file['name']));
		}

		if (!array_key_exists('filename', $pathinfo)) {
			$f = explode('.', basename($tgz_file['name']));
			$pathinfo['filename'] = $f[0];
		}

		if (move_uploaded_file($tgz_file['tmp_name'], $pathinfo['dirname']. '/' . $pathinfo['basename'])) {
			//unpack file
			$retval = 0;
			$rmdir=$pathinfo['dirname'] . '/' . $backupdir;
			if (is_dir($pathinfo['dirname'] . '/' . $backupdir)) {
				$result= rmdirr($pathinfo['dirname'] . '/' . $backupdir);
						if (!$result) {
							$old_backupdir = $backupdir;
							$backupdir =  $backupdir . '_' . time() .'/cacti_backup';
					if (mkdir($pathinfo['dirname'] .'/'. $backupdir,0755, true)) {
						 cacti_log('WARNING: Can not remove the existed directory ' . $old_backupdir . ' failed and change the directory to ' .$pathinfo['dirname'] .'/'. $backupdir);
						$rmdir = $pathinfo['dirname'] . '/' . dirname($backupdir);
					} else {
								cacti_log('FATAL: Remove the existed directory ' . $old_backupdir . ' failed and can nott change the directory to ' .$pathinfo['dirname'] .'/'. $backupdir);
						start_polling();
						return;

					}
							} else {

					 cacti_log('WARNING: Remove the existed directory ' . $old_backupdir . ' successfully');
				}

			}

			if ($config['cacti_server_os'] == 'win32') {
				$zip_cmd = read_config_option('path_7z');
				$cmd = 'cd ' . $pathinfo['dirname'] . ' && ' . $zip_cmd . ' x ' . escapeshellcmd($pathinfo['basename']);
			} else {
				$cmd = 'cd ' . $pathinfo['dirname'] . ' && tar -xzf ' . escapeshellcmd($pathinfo['basename']) . ' -C ' . dirname($pathinfo['dirname'] . '/' . $backupdir);
			}
			exec($cmd, $output, $retval);

			if ($retval) {
				raise_message(126);
				cacti_log("ERROR: $cmd failed: $output", true, 'GRID');
				start_polling();
				return;
			}

			//check for grid version
			$grid_version = db_fetch_cell("SELECT value FROM settings WHERE name='grid_version'");
			if (!file_exists($pathinfo['dirname'] . '/' . $backupdir . '/' . $grid_version)) {
				raise_message(133);
				cacti_log('ERROR: Wrong backup for RTM ' . $grid_version, true, 'GRID');
				start_polling();
				return;
			}

			//restore db
			$retval = 0;
			unset($output);
			$cmd = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir .
				' && mysql' .
				' -u' . cacti_escapeshellarg($database_username) .
				' -h' . cacti_escapeshellarg($database_hostname) .
				' -P' . cacti_escapeshellarg($database_port)     .
				' -p' . cacti_escapeshellarg($database_password) .
				' '   . cacti_escapeshellarg($database_default)  .
				' < cacti_db_backup.sql';

			exec($cmd, $output, $retval);

			if ($retval) {
				raise_message(126);
				cacti_log("ERROR: $cmd failed: $output", true, 'GRID');
				start_polling();
				return;
			}

			//restore /opt/rtm/etc/rtm.lic
			if ($config['cacti_server_os'] == 'win32') {
				if (file_exists($pathinfo['dirname'] . '/' . $backupdir . '/etc/rtm.lic')) {
					$retval = 0;
					$cmd    = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir . " && copy /Y \"etc\\rtm.lic\" \"" .
						RTM_ROOT."/etc/\"";
				}
			} else {
				if (file_exists($pathinfo['dirname'] . '/' . $backupdir . '/rtm/etc/rtm.lic')) {
					$retval = 0;
					$cmd    = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir . ' && cp -f rtm/etc/rtm.lic ' .
						RTM_ROOT . '/etc/.';
				}
			}

			exec($cmd, $output, $retval);

			if ($retval) {
				raise_message(126);
				cacti_log("ERROR: $cmd failed: $output", true, 'GRID');
				start_polling();
				return;
			}

			// restore appkey
			if (file_exists($pathinfo['dirname'] . '/' . $backupdir . '/rtm/etc/.appkey')) {
				$retval = 0;
				$cmd = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir . ' && cp -fp rtm/etc/.appkey ' .
					RTM_ROOT . '/etc/.';

				exec($cmd, $output, $retval);

				if ($retval) {
					raise_message(126);
					cacti_log("ERROR: $cmd failed: $output", true, 'GRID');
					start_polling();
					return;
				}
			}
			$appkeystr = get_appkey();
			if (strlen($appkeystr) > 0 ) {
				set_config_option('app_key', $appkeystr);
			} else {
				cacti_log('ERROR: unable to restore appkey.', true, 'GRID');
			}

			//regenerate lsf.conf and /etc/hosts
			$grid_clusters = db_fetch_assoc('SELECT * FROM grid_clusters');
			$advocate_port = read_config_option('advocate_port', True);
			$ch = curl_init();
			foreach ($grid_clusters as $cluster) {
				//regenerate lsf.conf
				$lsf_version = $cluster['lsf_version'];
				$dirname     = $cluster['lsf_envdir'];
				if (strpos(strtolower(str_replace("\\", '/', $dirname)), 'rtm/etc') === false) {
					continue;
				}

				$retval = 0;
				if ($config['cacti_server_os'] == 'win32') {
					$cmd    = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir .
						" && xcopy \"etc/" . $cluster['clusterid'] . "\" \"" . RTM_ROOT . '/etc/' . $cluster['clusterid'] . "\" /SEVYI";
				} else {
					$path_rtm_top = grid_get_path_rtm_top();
					$cmd    = 'cd ' . $pathinfo['dirname'] . '/' . $backupdir . ' && \
						cp -rf rtm/etc/' . substr($cluster['lsf_envdir'], strlen("$path_rtm_top/rtm/etc/")) . ' ' . RTM_ROOT . '/etc/.';
				}
				exec($cmd, $output, $retval);
				if ($retval) {
					raise_message(126);
					cacti_log("ERROR: $cmd failed: $output", true, 'GRID');
					start_polling();
					return;
				}

				if (isset_request_var('grid_backup_restore_host_file')) {
					//regenerate /etc/hosts
					if ($config['cacti_server_os'] != 'win32' && $cluster['ip'] != '') {
						$chosts = explode(' ', $cluster['lsf_master_hostname']);
						$cips   = explode(' ', $cluster['ip']);
						for($i=0; $i<count($chosts); $i++) {
							$host = $chosts[$i];
							$ip   = $cips[$i];
							curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:' . $advocate_port . '/hostSettings/hosts');
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 'false');
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
							curl_setopt($ch, CURLOPT_POST, 1);
							$line = $ip . ' ' . $host;
							curl_setopt($ch, CURLOPT_POSTFIELDS, 'op=add&line=' . $line);
							$out = curl_exec($ch);
							sleep(1);
						}
					}

					set_config_option('grid_backup_restore_host_file', get_request_var('grid_backup_restore_host_file'));
				} else {
					set_config_option('grid_backup_restore_host_file', '');
				}
			}
			curl_close($ch);

			rmdirr($pathinfo['dirname'] . '/' . $pathinfo['basename']);
			rmdirr($rmdir);
			raise_message(125);
			//print_r($pathinfo);
		} else {
			raise_message(126);
			cacti_log('ERROR: Move uploaded file error', true, 'GRID');
		}

		start_polling();
	}
}

function update_cluster_queue_tputs() {
	grid_debug('Updating Hourly Job Rates');

	/* get the list of queues */
	$queues = db_fetch_assoc('SELECT * FROM grid_queues');

	/* first we determine the queue rates, then we get
	 * the cluster rates from the queue rates */
	$queue_rates = db_fetch_assoc_prepared("SELECT
		clusterid,
		queue,
		stat,
		SUM(jobs_reaching_state) as totalJobs
		FROM grid_job_interval_stats
		WHERE date_recorded> ?
		GROUP BY clusterid, queue, stat", array(date('Y-m-d H:i:s', time()-3600)));

	if (cacti_sizeof($queue_rates)) {
		$new_rates = array();

		foreach ($queue_rates as $rate) {
			$new_rates[$rate['clusterid'] . '-' . $rate['queue'] . '-' . $rate['stat']] = $rate;
		}

		$i = 0;
		if (cacti_sizeof($queues)) {
		foreach ($queues as $queue) {
			if (isset($new_rates[$queue['clusterid'] . '-' . $queue['queuename'] . '-STARTED'])) {
				$queues[$i]['started'] = $new_rates[$queue['clusterid'] . '-' .
					$queue['queuename'] . '-STARTED']['totalJobs'];
			} else {
				$queues[$i]['started'] = 0;
			}

			if (isset($new_rates[$queue['clusterid'] . '-' . $queue['queuename'] . '-ENDED'])) {
				$queues[$i]['ended'] = $new_rates[$queue['clusterid'] . '-' .
					$queue['queuename'] . '-ENDED']['totalJobs'];
			} else {
				$queues[$i]['ended'] = 0;
			}

			if (isset($new_rates[$queue['clusterid'] . '-' . $queue['queuename'] . '-EXITED'])) {
				$queues[$i]['exited'] = $new_rates[$queue['clusterid'] . '-' .
					$queue['queuename'] . '-EXITED']['totalJobs'];
			} else {
				$queues[$i]['exited'] = 0;
			}

			$i++;
		}
		}

		if (cacti_sizeof($queues)) {
			foreach ($queues as $queue) {
				db_execute_prepared("UPDATE grid_queues SET
					hourly_started_jobs=?,
					hourly_done_jobs=?,
					hourly_exit_jobs=?
					WHERE clusterid=?
					AND queuename=?",
					array($queue['started'], $queue['ended'], $queue['exited'], $queue['clusterid'], $queue['queuename']));
			}
		}
	} else {
		if (cacti_sizeof($queues)) {
			foreach ($queues as $queue) {
				db_execute_prepared("UPDATE grid_queues SET
					hourly_started_jobs='0',
					hourly_done_jobs='0',
					hourly_exit_jobs='0'
					WHERE clusterid=?
					AND queuename=?",
					array($queue['clusterid'], $queue['queuename']));
			}
		}
	}
}

function prune_cached_pngs() {
	grid_debug('Pruning old Cached PNGs');
	$cache_directory = read_config_option('grid_cache_dir');
	$remove_time = time() - 20;

	$directory_contents = array();

	if ($handle = opendir($cache_directory)) {
		/* This is the correct way to loop over the directory. */
		while (false !== ($file = readdir($handle))) {
			$directory_contents[] = $file;
		}

		closedir($handle);
	}

	/* remove age old files */
	if (cacti_sizeof($directory_contents)) {
		/* goto the cache directory */
		chdir($cache_directory);

		/* check and fry as applicable */
		foreach ($directory_contents as $file) {
			if (is_writable($file)) {
				$modify_time = filemtime($file);
				if ($modify_time < $remove_time) {
					/* only remove jpeg's and png's */
					if (substr_count(strtolower($file), '.png')) {
						unlink($file);
					}
				}
			}
		}
	}
}

function calculate_and_store_grid_efficiencies() {
	grid_debug('Calculating Grid Efficiencies');
	$clusters                = db_fetch_assoc('SELECT * FROM grid_clusters');
	$cluster_warn_threshold  = read_config_option('grid_efficiency_warning');
	$cluster_alarm_threshold = read_config_option('grid_efficiency_alarm');
	/* perform efficiency calculations at the entire cluster first */

	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			if (strlen($cluster['efficiency_queues'])) {
				$new_queues = '';
				$queues     = explode('|', $cluster['efficiency_queues']);

				foreach ($queues as $queue) {
					if (strlen($new_queues)) {
						$new_queues .= ", '" . $queue . "'";
					} else {
						$new_queues .= "'" . $queue . "'";
					}
				}

				$effic_where = 'AND queue NOT IN (' . $new_queues . ')';
			} else {
				$effic_where = '';
			}

			$cluster_state = CLUSTER_OK;
			$clear_count   = 0;
			$alarm_count   = 0;
			$warn_count    = 0;

			$cluster_efficiency = db_fetch_cell_prepared("SELECT (SUM(stime+utime)/SUM(run_time*max_allocated_processes))*100
				FROM grid_jobs
				WHERE stat IN ('RUNNING','PROV')
				$effic_where
				AND clusterid= ?
				AND run_time> ?", array($cluster['clusterid'], read_config_option('grid_efficiency_window')));

			$running_efficiency = db_fetch_row_prepared("SELECT SUM(run_time*max_allocated_processes) AS total_time,
				SUM(stime+utime) AS cpu_time
				FROM grid_jobs
				WHERE stat IN ('RUNNING','PROV')
				$effic_where
				AND clusterid= ?
				AND run_time> ?", array($cluster['clusterid'], read_config_option('grid_efficiency_window')));

			$done_efficiency = db_fetch_row_prepared("SELECT SUM(run_time*max_allocated_processes) AS total_time,
				SUM(stime+utime) AS cpu_time
				FROM grid_jobs
				WHERE stat IN ('DONE', 'EXIT')
				$effic_where
				AND clusterid= ?
				AND end_time> ?", array($cluster['clusterid'],date('Y-m-d H:i:s', time()-300)));

			$total_time = $running_efficiency['total_time'] + $done_efficiency['total_time'];
			$cpu_time   = $running_efficiency['cpu_time']   + $done_efficiency['cpu_time'];

			if ($total_time > 0) {
				$cluster_efficiency = $cpu_time / $total_time * 100;
			}


			if (empty($cluster_efficiency)) {
				$cluster_efficiency = 100;
			}

			if (read_config_option('grid_efficiency_enabled') == 'on') {
				if ($cluster_efficiency < $cluster_alarm_threshold) {
					$alarm_count = $cluster['efficiency_alarm_count'] + 1;
					if ($cluster['efficiency_state'] != CLUSTER_ALARM) {
						if ($alarm_count == read_config_option('grid_efficiency_event_trigger_count')) {
							$cluster_state = CLUSTER_ALARM;
							cacti_log("ERROR: Cluster '" . $cluster['clustername'] . "' Has gone below it's ALARM threshold.  Please investigate", false, 'GRID');
						}
					} elseif ($alarm_count > read_config_option('grid_efficiency_event_trigger_count')) {
						$cluster_state = CLUSTER_ALARM;
					} elseif ($cluster['efficiency_state'] == CLUSTER_RECOVERING) {
						$cluster_state = CLUSTER_ALARM;
					} else {
						/* cluster has not reached alarm state */
					}
				} elseif ($cluster_efficiency < $cluster_warn_threshold) {
					$warn_count = $cluster['efficiency_warn_count'] + $cluster['efficiency_alarm_count'] + 1;
					if ($cluster['efficiency_state'] != CLUSTER_WARNING) {
						if ($warn_count == read_config_option('grid_efficiency_event_trigger_count')) {
							$cluster_state = CLUSTER_WARNING;
							cacti_log('WARINING: Cluster \'' . $cluster['clustername'] . '\' Has gone below its WARN threshold.  Please investigate', false, 'GRID');
						}
					} elseif ($warn_count > read_config_option('grid_efficiency_event_trigger_count')) {
						$cluster_state = CLUSTER_WARNING;
					} elseif ($cluster['efficiency_state'] == CLUSTER_RECOVERING) {
						$cluster_state = CLUSTER_WARNING;
					} else {
						/* cluster has not reached alarm state */
					}
				} elseif ($cluster['efficiency_alarm_count'] > 0) {
					$clear_count = $cluster['efficiency_clear_count'] + 1;

					if ($cluster['efficiency_state'] != CLUSTER_OK) {
						if ($clear_count > read_config_option('grid_efficiency_event_clear_count')) {
							$cluster_state = CLUSTER_OK;
							$alarm_count = 0;
						} else {
							if ($cluster['efficiency_state'] != CLUSTER_RECOVERING) {
								$cluster_state = CLUSTER_RECOVERING;
								cacti_log('NOTICE: Cluster \'' . $cluster['clustername'] . '\' Is Recovering', false, 'GRID');
							}
						}
					}
				} elseif ($cluster['efficiency_warn_count'] > 0) {
					$clear_count = $cluster['efficiency_clear_count'] + 1;

					if ($cluster['efficiency_state'] != CLUSTER_OK) {
						if ($clear_count > read_config_option('grid_efficiency_event_clear_count')) {
							$cluster_state = CLUSTER_OK;
							$warn_count = 0;
						} else {
							if ($cluster['efficiency_state'] != CLUSTER_RECOVERING) {
								$cluster_state = CLUSTER_RECOVERING;
								cacti_log('NOTICE: Cluster \'' . $cluster['clustername'] . '\' Is Recovering', false, 'GRID');
							}
						}
					}
				} elseif ($cluster['efficiency_clear_count'] > 0) {
					$clear_count = $cluster['efficiency_clear_count'] + 1;

					if ($cluster['efficiency_clear_count'] > read_config_option('grid_efficiency_event_clear_count')) {
						$clsuter_state = CLUSTER_OK;
						$clear_count = 0;
						$alarm_count = 0;
						$warn_count = 0;
					}
				}
			}

			/* Check for negative values */
			if ($cluster_efficiency < 0)
				$cluster_efficiency = 0;

			db_execute_prepared("UPDATE grid_clusters
				SET efficiency_state=?,
					efficiency=?,
					efficiency_alarm_count=?,
					efficiency_warn_count=?,
					efficiency_clear_count=?
				WHERE clusterid=?",
				array($cluster_state, $cluster_efficiency, $alarm_count, $warn_count, $clear_count, $cluster['clusterid']));
		}
	}

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	/* next set all the queue efficiency and memory values */
	$queue_sql = "SELECT
		clusterid,
		queue,
		AVG(max_memory) AS avg_mem,
		MAX(max_memory) AS max_mem,
		AVG(max_swap) AS avg_swap,
		MAX(max_swap) AS max_swap,
		(SUM(stime+utime)/SUM(run_time*max_allocated_processes))*100 AS efficiency,
		SUM(cpu_used) AS total_cpu
		FROM grid_jobs
		WHERE (stat='RUNNING' OR stat='PROV' OR stat='SSUSP' OR stat='SSUSP')
		AND run_time>'"  . read_config_option('grid_efficiency_window') . "'
		$clsql
		GROUP BY clusterid, queue";

	$queue_stats = db_fetch_assoc($queue_sql);

	$new_queues = array();
	if (cacti_sizeof($queue_stats)) {
	foreach ($queue_stats as $stat) {
		$new_queues[$stat['clusterid'] . '||' . $stat['queue']] = $stat;
	}
	}

	$queues = db_fetch_assoc('SELECT
		clusterid,
		queuename
		FROM grid_queues');

	/* now store them in the database */
	if (cacti_sizeof($queues)) {
	foreach ($queues as $queue) {
		if (isset($new_queues[$queue['clusterid'] . '||' . $queue['queuename']])) {
			db_execute_prepared("UPDATE grid_queues
				SET avg_mem=?,
				max_mem=?,
				avg_swap=?,
				max_swap=?,
				efficiency=?,
				total_cpu=?
				WHERE clusterid=?
				AND queuename=?",
				array(
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['avg_mem'],
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['max_mem'],
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['avg_swap'],
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['max_swap'],
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['efficiency'],
				$new_queues[$queue['clusterid'] . '||' . $queue['queuename']]['total_cpu'],
				$queue['clusterid'], $queue['queuename']));
		} else {
			db_execute_prepared("UPDATE grid_queues
				SET avg_mem='0',
				max_mem='0',
				avg_swap='0',
				max_swap='0',
				efficiency='0',
				total_cpu='0'
				WHERE clusterid=?
				AND queuename=?",
				array($queue['clusterid'], $queue['queuename']));
		}
	}
	}
}

function update_fairshare_tree_information() {
	$clusters = array_rekey(db_fetch_assoc('SELECT clusterid
		FROM grid_clusters
		WHERE disabled=""'), 'clusterid', 'clusterid');

	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $clusterid) {
			grid_debug("Processing Cluster $clusterid");

			$queues = array_rekey(db_fetch_assoc_prepared('SELECT DISTINCT queue
				FROM grid_queues_shares
				WHERE clusterid= ?' , array($clusterid)), 'queue', 'queue');

			if (cacti_sizeof($queues)) {
				foreach ($queues as $queue) {
					grid_debug("Processing Queue '$queue'");
					$level = 2;
					$prev_branches = array();

					/*
						Fairshare Tree Host Slot Selection Criteria
						Specify host resources to determine which hosts are included when updating queue fairshare tree.
						support the following syntax:

						centrify = 1, health = 0, tmp > 4000, swp > 1000, fstsimd, (gb32|gb64|gb128|gb256), csbatch = 1, client = 0

						The name value pairs are comma separated combined using and from a SQL perspective assumed to be an 'AND' clause.

						1. Numeric/String resource variables defined in table grid_hosts_resources support,
						"variable [ <= | >= | < | > | = ] value".  For example, "tmp > 4000"

						In particular, string resource variables support the same with the value enclosed in quotes
						"variable [ <= | >= | < | > | = ] 'value'". For example, "s = 'string1'"

						2. Boolean resource variables defined in table grid_hostresources support
						variable or variable = 0 or variable = 1

						Boolean variables also support the following 'OR' clause syntax.
						variableA|variableB|variableC|...

						For example, "health = 0", meaning the hosts who get health resource defined are excluded
						"centrify = 1, fstsimd", meaning the hosts who get centrify and fstsimd resource defined are included
						"b1|b2|b3|...", meaning the hosts who get any of b1, b2, b3 ... resources defined are included
					*/

					$res_string = db_fetch_cell_prepared("SELECT exec_host_res_req FROM grid_clusters WHERE clusterid=?", array($clusterid));

					grid_debug("res_string=" . $res_string);

					$union_sql = '';
					$sql_params = array();
					if ($res_string != '') {
						$nbool_resources = array_rekey(db_fetch_assoc('SELECT DISTINCT resource_name FROM grid_hosts_resources'), 'resource_name', 'resource_name');
						$bool_resources  = array_rekey(db_fetch_assoc('SELECT DISTINCT resource_name FROM grid_hostresources'), 'resource_name', 'resource_name');
						$operators       = array('<=', '>=', '<', '>', '=');

						$queries = explode(',', $res_string);

						$count     = 0;
						$doperator = '';
						foreach ($queries as $query) {
							foreach ($operators as $operator) {
								$nquery = explode($operator, $query);
								if (cacti_sizeof($nquery) > 1 || array_key_exists($nquery[0], $bool_resources) || strpos($nquery[0], '|')) {
									$doperator = $operator;
									break;
								}
							}

							// Numeric Resources
							if (array_key_exists(trim($nquery[0]), $nbool_resources)) {
								$count++;

								$union_sql .= ($union_sql != '' ? "\n UNION ALL \n":'') . "SELECT clusterid, host
									FROM grid_hosts_resources
									WHERE clusterid=?
									AND resource_name=?
									AND CAST(totalValue AS SIGNED) $doperator ?";
								$sql_params[] = $clusterid;
								$sql_params[] = trim($nquery[0]);
								$sql_params[] = trim($nquery[1]);

								grid_debug("Numeric $query");
							} elseif (array_key_exists(trim($nquery[0]), $bool_resources)) {
								$count++;

								if (isset($nquery[1]) && trim($nquery[1]) == '0') {
									$union_sql .= ($union_sql != '' ? "\n UNION ALL \n":'') . "SELECT DISTINCT clusterid, host
										FROM grid_hostresources
										WHERE clusterid=?
										AND host NOT IN (
											SELECT host
											FROM grid_hostresources
											WHERE resource_name=?
											AND clusterid=?
										)";
									$sql_params[] = $clusterid;
									$sql_params[] = trim($nquery[0]);
									$sql_params[] = $clusterid;
								} else {
									$union_sql .= ($union_sql != '' ? "\n UNION ALL \n":'') . "SELECT clusterid, host
										FROM grid_hostresources
										WHERE clusterid=?
										AND resource_name=?";
									$sql_params[] = $clusterid;
									$sql_params[] = trim($nquery[0]);
								}

								grid_debug("Boolean " . $query);
							} elseif (strpos($nquery[0], '|') !== false) {
								$count++;

								$resources = explode('|', trim($query,'() '));
								$union_sql .= ($union_sql != '' ? "\n UNION ALL \n":'') . "SELECT DISTINCT clusterid, host
									FROM grid_hostresources
									WHERE clusterid=? AND (";
								$sql_params[] = $clusterid;

								$sql_where = '';
								foreach ($resources as $resource) {
									$sql_where .= ($sql_where != '' ? ' OR ':'') . " resource_name=?";
									$sql_params[] = $resource;
								}
								$sql_where .= ')';

								$union_sql .= $sql_where;

								grid_debug("Boolean Or " . $query);
							} else {
								cacti_log("ERROR: Fairshare Resource Defined Does not Exist '" . $query . "'");

								grid_debug("Invalid " . $query);
							}
						}

						if ($union_sql != '') {
							$union_sql = "INNER JOIN (
								SELECT clusterid, host, count(*) AS counter
									FROM ( $union_sql ) AS totem
									GROUP BY clusterid, host
								) AS usql
								ON ghi.clusterid=usql.clusterid AND ghi.host=usql.host";
						}
					}

					$total_slots = db_fetch_cell_prepared("SELECT SUM(IF(maxJobs='-',maxCpus, maxJobs))
						FROM grid_queues_hosts AS gqh
						INNER JOIN grid_hosts AS gh
						ON gqh.clusterid=gh.clusterid
						AND gqh.host=gh.host
						INNER JOIN grid_hostinfo AS ghi
						ON ghi.clusterid=gh.clusterid
						AND ghi.host=gh.host
						$union_sql
						WHERE gh.status IN('Ok', 'Closed-Full')
						AND gqh.clusterid=?
						AND gqh.queue=?", array_merge($sql_params, array($clusterid, $queue)));

					grid_debug("Total Slots $total_slots");

					//print str_replace("\n", " ", str_replace("\t", "", "SELECT SUM(IF(maxJobs='-',maxCpus, maxJobs)) FROM grid_queues_hosts AS gqh INNER JOIN grid_hosts AS gh ON gqh.clusterid=gh.clusterid AND gqh.host=gh.host INNER JOIN grid_hostinfo AS ghi ON ghi.clusterid=gh.clusterid AND ghi.host=gh.host $union_sql WHERE gh.status IN('Ok', 'Closed-Full') AND gqh.clusterid=$clusterid AND gqh.queue='$queue'")) . "\n";

					while(true) {
						if (cacti_sizeof($prev_branches)) {
							$sql_where .= "AND shareAcctPath NOT IN('" . implode("','", $prev_branches) . "')";
						} else {
							$sql_where  = '';
						}

						$branches = db_fetch_assoc_prepared("SELECT DISTINCT SUBSTRING_INDEX(shareAcctPath, '/', ?) AS level, gqs.user_or_group, shares
							FROM grid_queues_shares AS gqs
							INNER JOIN grid_users_or_groups AS gug
							ON gqs.user_or_group=gug.user_or_group
							AND gqs.clusterid=gug.clusterid
							WHERE SUBSTRING_INDEX(shareAcctPath, '/', ?) = shareAcctPath
							AND gug.type='G'
							$sql_where
							AND gqs.queue=? AND gqs.clusterid=?
							AND shareAcctPath!=''
							ORDER BY level, user_or_group", array($level, $level, $queue, $clusterid));

						if (cacti_sizeof($branches)) {
							grid_debug("Processing '" . cacti_sizeof($branches) . "' Branches");

							if (cacti_sizeof($prev_branches)) {
								$sql_where = "AND shareAcctPath NOT IN('" . implode("','", $prev_branches) . "')";
							} else {
								$sql_where = '';
							}

							grid_debug("Branches Start ------------------------------");

							$leaf_shares = array();

							foreach ($branches as $branch) {
								$prev_branches[$branch['level']] = $branch['level'];

								// Find total shares for the leaf level
								if (!isset($leaf_shares[$branch['level']])) {
									$leaf_shares[$branch['level']] = db_fetch_cell_prepared("SELECT SUM(shares)
										FROM grid_queues_shares AS gqs
										INNER JOIN grid_users_or_groups AS gug
										ON gqs.clusterid=gug.clusterid
										AND gqs.user_or_group=gug.user_or_group
										WHERE type='G'
										AND shareAcctPath=?
										AND queue=?
										AND gqs.clusterid=?", array($branch['level'], $queue, $clusterid));
								}
								$total_shares = $leaf_shares[$branch['level']];

								// Find the parent slots and relative share
								if ($level == 2) {
									$parent_share = 1;
									$parent_slots = $total_slots;
								} else {
									$level_parts   = explode('/', $branch['level']);
									$user_or_group = $level_parts[cacti_sizeof($level_parts)-1];
									unset($level_parts[cacti_sizeof($level_parts)-1]);

									$shareAcctPath = implode('/', $level_parts);
									$parent        = db_fetch_row_prepared("SELECT relative_share, slot_share
										FROM grid_queues_shares
										WHERE user_or_group=?
										AND queue=?
										AND shareAcctPath=?
										AND clusterid=?", array($user_or_group, $queue, $shareAcctPath, $clusterid));

									$parent_share = $parent['relative_share'];
									$parent_slots = $parent['slot_share'];
								}

								$total_shares   = $leaf_shares[$branch['level']];     // As an integer share value
								$leaf_share     = ($total_shares == 0 ? 0 : $branch['shares'] / $total_shares);  // As a fractional percentage
								$relative_share = $leaf_share * $parent_share;        // As a fractional percentage
								$slot_share     = ceil($parent_slots * $leaf_share);  // As a integer number of slots

								grid_debug(sprintf("Branch:'%s', Group:'%s', ParentSlots:%s', TotalShares:'%s', Shares:'%s', ShareSlots:'%s', ParentShare:'%.2f', LeafShare:'%.2f', RelativeShare:'%.2f'",
									$branch['level'],
									$branch['user_or_group'],
									$parent_slots,
									$total_shares,
									$branch['shares'],
									$slot_share,
									$parent_share,
									$leaf_share,
									$relative_share));

								db_execute_prepared("UPDATE grid_queues_shares
									SET relative_share=?,
									slot_share=?,
									parent_slots=?,
									leaf_share=?
									WHERE queue=?
									AND clusterid=?
									AND user_or_group=?
									AND shareAcctPath=?",
									array($relative_share, $slot_share, $parent_slots, $leaf_share, $queue, $clusterid, $branch['user_or_group'], $branch['level']));
							}

							grid_debug("Branches End ------------------------------");
						} else {
							break;
						}

						$level++;
					}
				}
			}

		}
	}

	//create memory temp tables for saving grid_jobs pending/running jobs/slots aggregation result
	$temp_table = "temp_grid_jobs_aggregation";
	db_execute("DROP TABLE IF EXISTS $temp_table;");
	db_execute("CREATE TEMPORARY TABLE $temp_table (
		`clusterid` int(10) unsigned NOT NULL,
		`queue` varchar(30) NOT NULL default '',
		`shareAcctPath` varchar(256) default '',
		`user` varchar(60) NOT NULL default '',
		`run_jobs` int(10) unsigned NOT NULL default '0',
		`run_slots` int(10) unsigned NOT NULL default '0',
		`pend_jobs` int(10) unsigned NOT NULL default '0',
		`pend_slots` int(10) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`,`queue`,`shareAcctPath`, `user`),
		KEY `clusterid_queue_user` (`clusterid`, `queue`, `user`))
		ENGINE=MEMORY DEFAULT CHARSET=latin1;");

	db_execute("INSERT IGNORE INTO $temp_table
		SELECT clusterid, queue, REPLACE(chargedSAAP, CONCAT('/', user), '') AS shareAcctPath, user,
		SUM(CASE WHEN stat='RUNNING' THEN 1 ELSE 0 END) AS run_jobs,
		SUM(CASE WHEN stat='RUNNING' THEN num_cpus ELSE 0 END) AS run_slots,
		SUM(CASE WHEN stat='PEND' THEN 1 ELSE 0 END) AS pend_jobs,
		SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) AS pend_slots
		FROM grid_jobs
		WHERE stat in ('RUNNING', 'PEND')
		GROUP BY clusterid, queue, shareAcctPath, user;");

	$temp_table_records = db_fetch_cell("select count(clusterid) from $temp_table;");
	grid_debug("Aggregate running/pending jobs/slots to memory temp table per clusterid, queue, shareAcctPath, user. Size = $temp_table_records.");

	if (read_config_option("grid_usergroup_method") == "jobmap") {
		db_execute("DROP TABLE IF EXISTS temp_grid_jobs_jobmap;");
		db_execute("CREATE TEMPORARY TABLE temp_grid_jobs_jobmap (
			`clusterid` int(10) unsigned NOT NULL,
			`queue` varchar(30) NOT NULL default '',
			`usergroup` varchar(60) NOT NULL default '',
			`user` varchar(60) NOT NULL default '',
			`pend_jobs` int(10) unsigned NOT NULL default '0',
			`pend_slots` int(10) unsigned NOT NULL default '0',
			PRIMARY KEY  (`clusterid`,`queue`,`usergroup`, `user`),
			KEY `clusterid_queue_user_jobmap` (`clusterid`, `queue`, `user`))
			ENGINE=MEMORY DEFAULT CHARSET=latin1;");

		db_execute("INSERT IGNORE INTO temp_grid_jobs_jobmap
			SELECT clusterid, queue, usergroup, user,
			SUM(CASE WHEN stat='PEND' THEN 1 ELSE 0 END) AS pend_jobs,
			SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) AS pend_slots
			FROM grid_jobs
			WHERE stat = 'PEND'
			GROUP BY clusterid, queue, usergroup, user;");

		$temp_table_jobmap_records = db_fetch_cell("select count(clusterid) from temp_grid_jobs_jobmap;");
		grid_debug("Aggregate pending jobs/slots to memory temp table per clusterid, queue, usergroup, user. Size = $temp_table_jobmap_records.");

	}

	//update all users running jobs/slots in table grid_queues_shares. users not in temp table will be assigned to null by left join
	grid_debug("grid_queues_shares users running jobs/slots update");

	$clusters = db_fetch_assoc("SELECT DISTINCT clusterid FROM grid_clusters WHERE disabled!='on'");

	if (cacti_sizeof($clusters)) {
		grid_debug("Processing '" . cacti_sizeof($clusters) . "' Clusters");
		foreach($clusters as $cluster) {
			db_execute_prepared("UPDATE grid_queues_shares AS gqs
				LEFT JOIN $temp_table AS tt
				ON tt.clusterid=gqs.clusterid
				AND gqs.queue=tt.queue
				AND gqs.shareAcctPath = tt.shareAcctPath
				AND gqs.user_or_group=tt.user
				SET gqs.run_jobs = tt.run_jobs, gqs.run_slots = tt.run_slots
				WHERE gqs.clusterid=?", array($cluster['clusterid']));

			//update all users pending jobs/slots in table grid_queues_shares. users not in temp table will be assigned to null by left join
			grid_debug("grid_queues_shares users pending jobs/slots update");

			if (read_config_option("grid_usergroup_method") == "jobmap") {
				db_execute_prepared("UPDATE grid_queues_shares AS gqs
					LEFT JOIN temp_grid_jobs_jobmap AS tt FORCE INDEX (clusterid_queue_user_jobmap)
					ON gqs.clusterid=tt.clusterid
					AND gqs.queue=tt.queue
					AND gqs.shareAcctPath LIKE CONCAT('%/', tt.usergroup)
					AND gqs.user_or_group=tt.user
					SET gqs.pend_jobs = tt.pend_jobs, gqs.pend_slots = tt.pend_slots
					WHERE gqs.clusterid=?", array($cluster['clusterid']));
			} else {
				db_execute_prepared("UPDATE grid_queues_shares AS gqs
					LEFT JOIN $temp_table AS tt FORCE INDEX (clusterid_queue_user)
					ON tt.clusterid=gqs.clusterid
					AND gqs.queue=tt.queue
					AND gqs.shareAcctPath = tt.shareAcctPath
					AND tt.shareAcctPath = ''
					AND gqs.user_or_group=tt.user
					SET gqs.pend_jobs = tt.pend_jobs, gqs.pend_slots = tt.pend_slots
					WHERE gqs.clusterid=?", array($cluster['clusterid']));
			}
		}
	}

	//update group running/pending jobs/slots in table grid_queues_shares
	$count_total = 0;

	$sql_prefix_group = "INSERT INTO grid_queues_shares (clusterid, queue, shareAcctPath, user_or_group, run_jobs, run_slots, pend_jobs, pend_slots) VALUES ";
	$sql_suffix_group = " ON DUPLICATE KEY UPDATE run_jobs = VALUES(run_jobs), run_slots = VALUES(run_slots), pend_jobs=VALUES(pend_jobs), pend_slots=VALUES(pend_slots)";
	$sql_group = '';
	$sql_group_cnt = 0;

	$grid_queue_shares = db_fetch_assoc("SELECT gqs.clusterid, queue, gqs.user_or_group, shareAcctPath,
		gqs.run_jobs, gqs.run_slots, gqs.pend_jobs, gqs.pend_slots, type
		FROM grid_queues_shares AS gqs FORCE INDEX (clusterid_user_or_group)
		INNER JOIN grid_users_or_groups AS gug
		ON gqs.clusterid = gug.clusterid
		AND gqs.user_or_group= gug.user_or_group
		AND type ='G';");

	grid_debug("grid_queues_shares groups running/pending jobs/slots update. total= " . cacti_sizeof($grid_queue_shares));

	if (cacti_sizeof($grid_queue_shares)) {
	foreach ($grid_queue_shares as $grid_queue_share) {
		$count_total ++;

		$clusterid = $grid_queue_share['clusterid'];
		$queue = $grid_queue_share['queue'];
		$user_or_group = $grid_queue_share['user_or_group'];
		$share_acct_path = $grid_queue_share['shareAcctPath'];
		$type = $grid_queue_share['type'];

		$pre_run_jobs = empty($grid_queue_share['run_jobs'])? 0 : $grid_queue_share['run_jobs'];
		$pre_run_slots = empty($grid_queue_share['run_slots'])? 0 : $grid_queue_share['run_slots'];
		$pre_pend_jobs = empty($grid_queue_share['pend_jobs'])? 0 : $grid_queue_share['pend_jobs'];
		$pre_pend_slots = empty($grid_queue_share['pend_slots'])? 0 : $grid_queue_share['pend_slots'];

		//current user_or_group is group name
		$group = $user_or_group;
		$charged_group = $share_acct_path . "/" . $user_or_group;

		//get group running jobs/slots
		$result = db_fetch_row_prepared("SELECT SUM(run_jobs) AS run_jobs, SUM(run_slots) AS run_slots
			FROM $temp_table AS gj
			WHERE gj.clusterid = ?
			AND gj.queue = ?
			AND (gj.shareAcctPath = ? OR gj.shareAcctPath LIKE CONCAT(?, '/', '%'))", array($clusterid, $queue, $charged_group, $charged_group));

		$run_jobs = empty($result['run_jobs'])? 0 : $result['run_jobs'];
		$run_slots = empty($result['run_slots'])? 0 : $result['run_slots'];


		if (read_config_option("grid_usergroup_method") == "jobmap") {
			$result['pend_jobs'] = 0;
			$result['pend_slots'] = 0;
		} else {
			//get group pending jobs/slots
			$result = db_fetch_row_prepared("SELECT SUM(pend_jobs) AS pend_jobs, SUM(pend_slots) AS pend_slots
				FROM $temp_table AS gj
				INNER JOIN (
					SELECT ugm.clusterid, ugm.username, ugm.groupname
					FROM grid_user_group_members AS ugm
					INNER JOIN grid_users_or_groups AS uog
					ON ugm.groupname=uog.user_or_group
					AND ugm.clusterid=uog.clusterid
					WHERE type='G'
					AND ugm.clusterid = ?
					AND ugm.groupname = ?
				) AS ug
				ON gj.clusterid=ug.clusterid
				AND gj.user=ug.username
				WHERE gj.clusterid = ?
				AND gj.queue = ? ", array($clusterid, $group, $clusterid, $queue));
		}

		$pend_jobs = empty($result['pend_jobs'])? 0 : $result['pend_jobs'];
		$pend_slots = empty($result['pend_slots'])? 0 : $result['pend_slots'];

		if (!($run_jobs == $pre_run_jobs && $run_slots == $pre_run_slots && $pend_jobs == $pre_pend_jobs && $pend_slots == $pre_pend_slots)) {
			$sql_group .= ($sql_group_cnt == 0 ? "(":",(") . "'$clusterid', '$queue', '$share_acct_path', '$group', '$run_jobs', '$run_slots', '$pend_jobs', '$pend_slots')";
			$sql_group_cnt ++;
		}

		if ($sql_group_cnt > 0 && $sql_group_cnt % 1000 == 0) {
			db_execute($sql_prefix_group . $sql_group . $sql_suffix_group);
			$sql_group = "";
			$sql_group_cnt = 0;
		}

		if ($count_total % 1000 == 0) {
			grid_debug("Group Count:$count_total...");
		}
	}
	}

	if ($sql_group_cnt > 0) {
		db_execute($sql_prefix_group . $sql_group . $sql_suffix_group);
	}

	//update groups pending jobs/slots when user group aggregation menthod is "job specification"
	if (read_config_option("grid_usergroup_method") == "jobmap") {
		$pend_jobmap_groups = db_fetch_assoc("SELECT * FROM temp_grid_jobs_jobmap;");
		grid_debug("jobmap aggregation update groups pending jobs/slots total= " . cacti_sizeof($pend_jobmap_groups));

		if (cacti_sizeof($pend_jobmap_groups)) {
		foreach ($pend_jobmap_groups as $pend_jobmap_group) {
			$clusterid = $pend_jobmap_group['clusterid'];
			$queue = $pend_jobmap_group['queue'];
			$usergroup = $pend_jobmap_group['usergroup'];
			$user = $pend_jobmap_group['user'];
			$pend_jobs = $pend_jobmap_group['pend_jobs'];
			$pend_slots = $pend_jobmap_group['pend_slots'];

			//aggregate each user pending jobs/slots to all of its charged user groups
			$share_acct_path = db_fetch_cell_prepared("SELECT shareAcctPath from grid_queues_shares
				WHERE clusterid = ?
				AND queue = ?
				AND user_or_group = ?
				AND shareAcctPath LIKE CONCAT('%/', ?)", array($clusterid, $queue, $user, $usergroup));

			if (empty($share_acct_path)) continue;

			$str_groups = str_replace("'',", "", "'" . implode("','", explode("/",$share_acct_path)) . "'");

			db_execute_prepared("UPDATE grid_queues_shares
				SET pend_jobs = IFNULL(pend_jobs,0) + ?, pend_slots = IFNULL(pend_slots,0) + ?
				WHERE clusterid = ?
				AND queue = ?
				AND user_or_group IN ($str_groups);", array($pend_jobs, $pend_slots, $clusterid, $queue));
		}
		}

	}

	grid_debug("Fairshare update end." );
}

function prune_old_license_server_stats() {
	$disabled_servers = db_fetch_assoc("SELECT service_id AS id FROM lic_services WHERE disabled='on'");

	$sql_where = "";
	if (cacti_sizeof($disabled_servers)) {
		foreach ($disabled_servers as $server) {
			if (strlen($sql_where)) {
				$sql_where .= ", " . $server["id"];
			} else {
				$sql_where  = "WHERE service_id IN (" . $server["id"];
			}
		}

		$sql_where .= ")";

		db_execute("DELETE FROM lic_services_feature_use $sql_where");
		db_execute("DELETE FROM lic_services_feature $sql_where");
		db_execute("DELETE FROM lic_services_feature_details $sql_where");
	}
}

function delete_jobtype_records_by_loop($temp_table, $reference_table, $delete_size) {
	$total_rows = db_fetch_cell("SELECT COUNT(*) FROM $temp_table");
	$deleted = 0;
	if ($total_rows > 0) {
		$start  = 0;
		// Delete with limit
		while($start <= $total_rows) {
			$deleteref_table = "grid_jobs_removalloop_" . time();

			db_execute_prepared("CREATE TEMPORARY TABLE IF NOT EXISTS $deleteref_table LIKE $temp_table");

			db_execute_prepared("INSERT IGNORE INTO $deleteref_table
				SELECT clusterid, jobid, indexid, submit_time
				FROM $temp_table
				ORDER BY jobid
				LIMIT $start, $delete_size");

			db_execute_prepared("DELETE rt
				FROM $reference_table AS rt
				INNER JOIN $deleteref_table AS tt
				ON rt.clusterid = tt.clusterid
				AND rt.jobid = tt.jobid
				AND rt.indexid = tt.indexid
				AND rt.submit_time = tt.submit_time");
			$deleted += db_affected_rows();
			$start += $delete_size;

			db_execute("DROP TABLE IF EXISTS $deleteref_table");
		}
		// Just in case.  Likely not required
		db_execute("DELETE rt
			FROM $reference_table AS rt
			INNER JOIN (SELECT * FROM $temp_table ORDER BY jobid LIMIT $start, $delete_size) AS tt
			ON rt.clusterid = tt.clusterid
			AND rt.jobid = tt.jobid
			AND rt.indexid = tt.indexid
			AND rt.submit_time = tt.submit_time");
		$deleted += db_affected_rows();
	}
	return $deleted;
}

function delete_records_by_loop($count_sql, $delete_sql) {
	$array_job_rows_deleted = 0;
	while (1) {
		db_execute("$delete_sql");
		$array_job_rows_deleted += db_affected_rows();

		$rows_to_delete = db_fetch_cell("$count_sql");
		if ($rows_to_delete <= 0) {
			break;
		}
	}
	return $array_job_rows_deleted;
}

function delete_records_base_temp_table($temp_table, $delete_size) {
	$num_found = delete_jobtype_records_by_loop($temp_table, 'grid_jobs_reqhosts', $delete_size);

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of ReqHosts Removed :" . $num_found);
	}

	$num_found = delete_jobtype_records_by_loop($temp_table, 'grid_jobs_jobhosts', $delete_size);

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of JobHosts Removed :" . $num_found);
	}

	$num_found = delete_jobtype_records_by_loop($temp_table, 'grid_jobs_pendreasons', $delete_size);

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of Prending Removed :" . $num_found);
	}
}

function delete_records_from_jobs_table($start_time, $start) {
	global $cnn_id;

	grid_debug("Deleting Finished Job/Array Records - Begin");

	/* get the delete range */
	$scan_date = date("Y-m-d H:i:00", time()-read_config_option("grid_jobs_clean_period"));

	/* allocate a temporary table to hold jobs to remove */
	$temp_table = "grid_jobs_removal_" . time();

	$delete_size = read_config_option('grid_db_maint_delete_size');

	db_execute("CREATE TEMPORARY TABLE $temp_table (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` int(10) unsigned NOT NULL,
		`indexid` int(10) unsigned NOT NULL,
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`clusterid`,`jobid`,`indexid`, `submit_time`),
		KEY `jobid` (`jobid`))
		ENGINE=InnoDB DEFAULT CHARSET=latin1");

	/* add non-array jobs first */
	db_execute_prepared("INSERT IGNORE INTO $temp_table
		SELECT clusterid, jobid, indexid, submit_time
		FROM grid_jobs
		WHERE job_end_logged=1
		AND stat in ('DONE', 'EXIT')
		AND indexid=0
		AND end_time<?", array($scan_date));

	$num_found = db_affected_rows();

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of Normal Jobs Ready for Removal :" . $num_found);
	}

	db_execute_prepared("INSERT IGNORE INTO $temp_table
		SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time
		FROM grid_jobs AS gj
		JOIN grid_arrays AS ga
		ON gj.clusterid=ga.clusterid AND gj.jobid=ga.jobid AND gj.submit_time=ga.submit_time
		WHERE ga.stat>0 AND ga.last_updated<?", array($scan_date));

	$num_found = db_affected_rows();

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of Array Jobs Ready for Removal :" . $num_found);
	}

	$num_found = delete_jobtype_records_by_loop($temp_table, 'grid_jobs', $delete_size);

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		grid_debug("Number of Job Records Removed :" . $num_found);
	}

	delete_records_base_temp_table($temp_table, $delete_size);

	$count_sql = "SELECT count(*) FROM grid_arrays WHERE stat='1' AND last_updated<='$scan_date'";

	$delete_sql = "DELETE FROM grid_arrays WHERE stat='1' AND last_updated<='$scan_date' LIMIT $delete_size";

	$num_found = delete_records_by_loop($count_sql, $delete_sql);

	grid_debug("Deleting Finished Job/Array Records - Complete");

	db_execute("TRUNCATE TABLE $temp_table");

	$clusters   = db_fetch_assoc("SELECT clusterid FROM grid_clusters where disabled !='on'");
	$check_time = time() - 3600;
	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			$prev_time_job = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_jobs_start_time_" . $cluster["clusterid"]));
			if (empty($prev_time_job)) {
				$prev_time_job  = $start_time - 300;
			}

			$prev_time_pend = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array("grid_pend_start_time_" . $cluster["clusterid"]));
			if (empty($prev_time_pend)) {
				$prev_time_pend = $start_time - 300;
			}

			$check_time = min($check_time, $prev_time_job, $prev_time_pend);
		}
	}

	$last_update_check_str = date("Y-m-d H:i:s", $check_time);

	$found_jobs = db_fetch_cell_prepared("SELECT count(*) FROM grid_jobs WHERE stat NOT IN ('DONE', 'EXIT') AND last_updated<?", array($last_update_check_str));

	if ($found_jobs) {
		/* add non-array jobs first */
		db_execute_prepared("INSERT IGNORE INTO $temp_table
			SELECT clusterid, jobid, indexid, submit_time
			FROM grid_jobs
			WHERE stat NOT IN ('DONE', 'EXIT')
			AND last_updated<?", array($last_update_check_str));

		grid_debug("NOTE: GRIDMOVE Remove Orphan Jobs from system");

		delete_records_base_temp_table($temp_table, $delete_size);

		$num_found = db_fetch_cell_prepared("SELECT COUNT(clusterid)
			FROM grid_jobs
			WHERE last_updated < ?
			AND stat NOT IN ('DONE', 'EXIT', 'ZOMBI')",
			array($last_update_check_str));

		if ($num_found > 0) {
			$count_sql = "SELECT count(*) FROM grid_jobs
				WHERE last_updated<'$last_update_check_str'
				AND stat NOT IN ('DONE', 'EXIT', 'ZOMBI')";

			$delete_sql = "DELETE FROM grid_jobs
				WHERE last_updated<'$last_update_check_str'
				AND stat NOT IN ('DONE', 'EXIT', 'ZOMBI') LIMIT $delete_size";

			$num_found = delete_records_by_loop($count_sql, $delete_sql);
		}

		grid_debug("NOTE: GRIDMOVE Removed '$num_found' Orphan Jobs ");
	}

	db_execute("DROP TABLE IF EXISTS $temp_table");
}

function grid_pump_records($records, $table, $format = array(), $ignore = false, $duplicate = "") {
	global $cnn_id;

	$first      = true;
	$sql_prefix = "INSERT " . ($ignore ? "IGNORE":"") . " INTO $table (";
	$sql_out    = array();
	$columns    = array();

	if (cacti_sizeof($format)) {
		$sql_prefix .= implode(",", $format) . ") VALUES ";
		$first = false;
		$columns = $format;
	}

	if (cacti_sizeof($records)) {
		foreach ($records as $r) {
			if ($first) {
				$columns = array_keys($r);
				foreach ($columns as $c) {
					$sql_prefix .= ($first ? $c:",$c");
					$first = false;
				}
				$sql_prefix .= ") VALUES ";
			}

			$j = 0;
			$sql = "";
			foreach ($columns as $c) {
				$sql .= ($j == 0 ? "(" : ",") . ($r[$c] == null ? "''" : db_qstr($r[$c]));
				$j++;
			}
			$sql .= ")";
			$sql_out[] = $sql;
		}

		$i=0;

		$new_sql = array_chunk($sql_out, 500);
		foreach ($new_sql as $sql) {
			$osql = implode(",", $sql);
			db_execute("$sql_prefix $osql $duplicate");
		}
	}

	return cacti_sizeof($records);
}

function summarize_grid_data($start_time, $start) {
	global $max_run_duration, $config, $debug;

	/* get the start time */
	$grid_poller_start = read_config_option("grid_poller_start");

	/* save the scan date information */
	$scan_date = $grid_poller_start;
	if (empty($scan_date)) {
		$scan_date = date("Y-m-d H:i:00");
		cacti_log("ERROR: Default Scan Date Used", false, "GRID");
	}

	/* determine the poller frequency */
	$poller_frequency = read_config_option("poller_interval");
	if (empty($poller_frequency)) {
		$poller_frequency = 300;
	}

	/* determine the prior polling time for interval stats */
	$previous_date = read_config_option("grid_poller_prev_start");
	if (empty($previous_date)) {
		$previous_date = date("Y-m-d H:i:00", strtotime($scan_date) - $poller_frequency);

		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log("WARNING: Default Previous Scan Date Used", false, "GRID");
		}
	}

	db_execute_prepared("REPLACE INTO settings SET name='grid_summary_date', value=?", array($scan_date));

	if (read_config_option("grid_job_stats_fromhost_enabled") == "") {
		$from_hosts = "'Not Collected' as from_host";
		$from_hosts_groupby = "";
	} else {
		$from_hosts = "SUBSTRING_INDEX(grid_jobs.from_host, ':', 1) AS from_host";
		$from_hosts_groupby = ", SUBSTRING_INDEX(grid_jobs.from_host, ':', 1)";
	}

	if (read_config_option("grid_job_stats_app_enabled") == "") {
		$app = "'Not Collected' as app";
		$app_groupby = "";
	} else {
		$app = "grid_jobs.app";
		$app_groupby = "grid_jobs.app, ";
	}

	if (read_config_option("grid_job_stats_project_enabled") == "") {
		$project_names = "'Not Collected'";
		$project_groupby = "";
	} else {
		$project_names = get_project_aggregation_string("0", "grid_project_group_aggregation", true);
		$project_groupby = ", " . $project_names;
	}

	if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_running = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time) > 0 AND UNIX_TIMESTAMP()>UNIX_TIMESTAMP(start_time) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) END) AS jobs_wall_time";
		$wallt_method_susp    = "SUM(CASE WHEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";

	} else {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_running = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time) > 0 AND UNIX_TIMESTAMP()>UNIX_TIMESTAMP(start_time) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) END) AS jobs_wall_time";
		$wallt_method_susp    = "SUM(CASE WHEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";

	}

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$format = array("clusterid", "user", "stat", "queue", "app", "from_host", "exec_host",
		"projectName", "jobs_reaching_state", "jobs_wall_time", "jobs_stime", "jobs_utime",
		"slots_in_state", "max_memory", "avg_memory",
		"interval_start", "interval_end", "date_recorded");

	grid_debug("Entering Submitted Jobs to Database");
	pump_interval_recrods("SUBMITTED", $previous_date, $scan_date);

	grid_debug("Entering Started Jobs to Database");
	pump_interval_recrods("STARTED", $previous_date, $scan_date);

	grid_debug("Entering Ended Jobs to Database");
	pump_interval_recrods("ENDED", $previous_date, $scan_date);

	grid_debug("Entering Exited Jobs to Database");
	pump_interval_recrods("EXITED", $previous_date, $scan_date);

	grid_debug("Entering Currently Running Jobs to Database");
	pump_interval_recrods("RUNNING", $previous_date, $scan_date);

	grid_debug("Entering Pre-Exec Suspended Jobs to Database");
	pump_interval_recrods("PSUSP", $previous_date, $scan_date);

	grid_debug("Entering System Suspended Jobs to Database");
	pump_interval_recrods("SSUSP", $previous_date, $scan_date);

	grid_debug("Entering User Suspended Jobs to Database");
	pump_interval_recrods("USUSP", $previous_date, $scan_date);

	grid_debug("Entering Pending Jobs to Database");
	pump_interval_recrods("PEND", $previous_date, $scan_date);

	grid_debug("Updating Cluster Summary Information Page");
	$clusters = db_fetch_assoc("SELECT * FROM grid_clusters");
	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			/* we will only show current information */
			$last_update = db_fetch_cell_prepared("SELECT (UNIX_TIMESTAMP(max(last_seen))-86400) FROM grid_hostinfo WHERE clusterid=?", array($cluster["clusterid"]));
			$clients = db_fetch_cell_prepared("SELECT count(*) FROM grid_hostinfo WHERE clusterid=? AND isServer='0' AND UNIX_TIMESTAMP(last_seen)>?", array($cluster["clusterid"], $last_update));
			$servers = db_fetch_cell_prepared("SELECT count(*) FROM grid_hostinfo WHERE clusterid=? AND isServer>'0' AND UNIX_TIMESTAMP(last_seen)>?", array($cluster["clusterid"], $last_update));
			$cpus    = db_fetch_cell_prepared("SELECT sum(maxCpus) FROM grid_hostinfo WHERE clusterid=? AND isServer>'0' AND UNIX_TIMESTAMP(last_seen)>?", array($cluster["clusterid"], $last_update));

			/* update the database */
			db_execute_prepared("UPDATE grid_clusters SET total_hosts=?, total_cpus=?, total_clients=? WHERE clusterid=?", array($servers, $cpus, $clients, $cluster["clusterid"]));
		}
	}
}

function pump_interval_recrods($type, $previous_date, $scan_date) {
	if (read_config_option("grid_job_stats_fromhost_enabled") == "") {
		$from_hosts = "'Not Collected' as from_host";
		$from_hosts_groupby = "";
	} else {
		$from_hosts = "SUBSTRING_INDEX(grid_jobs.from_host, ':', 1) AS from_host";
		$from_hosts_groupby = ", SUBSTRING_INDEX(grid_jobs.from_host, ':', 1)";
	}

	if (read_config_option("grid_job_stats_app_enabled") == "") {
		$app = "'Not Collected' as app";
		$app_groupby = "";
	} else {
		$app = "grid_jobs.app";
		$app_groupby = "grid_jobs.app, ";
	}

	if (read_config_option("grid_job_stats_project_enabled") == "") {
		$project_names = "'Not Collected'";
		$project_groupby = "";
	} else {
		$project_names = get_project_aggregation_string("0", "grid_project_group_aggregation", true);
		$project_groupby = ", " . $project_names;
	}

	if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_running = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time) > 0 AND UNIX_TIMESTAMP()>UNIX_TIMESTAMP(start_time) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) END) AS jobs_wall_time";
		$wallt_method_susp    = "SUM(CASE WHEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), ((CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed), 0)) AS gpu_wall_time";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN (gpu_exec_time-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed) ELSE 0 END) AS gpu_wall_time";
		}
	} else {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_running = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time) > 0 AND UNIX_TIMESTAMP()>UNIX_TIMESTAMP(start_time) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*CAST(num_cpus AS signed) END) AS jobs_wall_time";
		$wallt_method_susp    = "SUM(CASE WHEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) > 0 THEN (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(start_time)-CAST(ssusp_time AS signed)-CAST(ususp_time AS signed))*CAST(num_cpus AS signed) ELSE 0 END) AS jobs_wall_time";
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), (CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)*CAST(num_gpus AS signed), 0)) AS gpu_wall_time";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN gpu_exec_time*CAST(num_gpus AS signed) ELSE 0 END) AS gpu_wall_time";
		}
	}

	/* limit the clusters */
	$clsql_grid_jobs = $clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
		$clsql_grid_jobs = str_replace('clusterid', "grid_jobs.clusterid", $clsql);
	}

	$format = array("clusterid", "user", "stat", "queue", "app", "from_host", "exec_host",
		"projectName", "jobs_reaching_state", "jobs_wall_time", "gpu_wall_time", "jobs_stime", "jobs_utime",
		"slots_in_state", "gpus_in_state", "max_memory", "avg_memory", "gpu_avg_mem", "gpu_max_mem",
		"interval_start", "interval_end", "date_recorded");

	switch($type) {
	case "SUBMITTED":
		$run_type       = 'pre';
		$jobs_wall_time = "'0' AS jobs_wall_time";
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "'0' AS jobs_stime";
		$jobs_utime     = "'0' AS jobs_utime";
		$stat           = "'SUBMITTED' AS stat";
		$max_memory     = "'0' AS max_memory";
		$avg_memory     = "'0' AS avg_memory";
		$gpu_max_mem    = "'0' AS gpu_max_mem";
		$gpu_avg_mem    = "'0' AS gpu_avg_mem";
		$sql_where      = "WHERE (grid_jobs.submit_time>='$previous_date') AND (grid_jobs.submit_time<='$scan_date')";
		$exec_host      = "'-' AS exec_host";

		break;
	case "PEND":
		$run_type       = 'pre';
		$jobs_wall_time = "'0' AS jobs_wall_time";
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "'0' AS jobs_stime";
		$jobs_utime     = "'0' AS jobs_utime";
		$stat           = "'PEND' AS stat";
		$max_memory     = "'0' AS max_memory";
		$avg_memory     = "'0' AS avg_memory";
		$gpu_max_mem    = "'0' AS gpu_max_mem";
		$gpu_avg_mem    = "'0' AS gpu_avg_mem";
		$sql_where      = "WHERE (stat='PEND')";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "STARTED":
		$run_type       = 'post';
		$jobs_wall_time = "'0' AS jobs_wall_time";
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "'0' AS jobs_stime";
		$jobs_utime     = "'0' AS jobs_utime";
		$stat           = "'STARTED' AS stat";
		$max_memory     = "'0' AS max_memory";
		$avg_memory     = "'0' AS avg_memory";
		$gpu_max_mem    = "'0' AS gpu_max_mem";
		$gpu_avg_mem    = "'0' AS gpu_avg_mem";
		$sql_where      = "WHERE (grid_jobs.start_time>='$previous_date') AND (grid_jobs.start_time<='$scan_date')";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "EXITED":
		$run_type       = 'post';
		$jobs_wall_time = $wallt_method_exit;
		$gpu_wall_time  = $gpu_wallt_method_excl;
		$jobs_stime     = "SUM(stime) AS jobs_stime";
		$jobs_utime     = "SUM(utime) AS jobs_utime";
		$stat           = "'EXITED' AS stat";
		$max_memory     = "MAX(grid_jobs.mem_used) AS max_memory";
		$avg_memory     = "AVG(grid_jobs.mem_used) AS avg_memory";
		$gpu_max_mem    = "MAX(grid_jobs.gpu_max_memory) AS gpu_max_mem";
		$gpu_avg_mem    = "AVG(grid_jobs.gpu_max_memory) AS gpu_avg_mem";
		$sql_where      = "WHERE ((grid_jobs.end_time>='$previous_date') AND (grid_jobs.end_time<='$scan_date') AND (stat = 'EXIT'))";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "RUNNING":
		$run_type       = 'post';
		$jobs_wall_time = $wallt_method_running;
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "SUM(stime) AS jobs_stime";
		$jobs_utime     = "SUM(utime) AS jobs_utime";
		$stat           = "'RUNNING' AS stat";
		$max_memory     = "MAX(grid_jobs.mem_used) AS max_memory";
		$avg_memory     = "AVG(grid_jobs.mem_used) AS avg_memory";
		$gpu_max_mem    = "MAX(grid_jobs.gpu_max_memory) AS gpu_max_mem";
		$gpu_avg_mem    = "AVG(grid_jobs.gpu_max_memory) AS gpu_avg_mem";
		$sql_where      = "WHERE (stat IN ('RUNNING','PROV'))";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "ENDED":
		$run_type       = 'post';
		$jobs_wall_time = $wallt_method_done;
		$gpu_wall_time  = $gpu_wallt_method_excl;
		$jobs_stime     = "SUM(stime) AS jobs_stime";
		$jobs_utime     = "SUM(utime) AS jobs_utime";
		$stat           = "'ENDED' AS stat";
		$max_memory     = "MAX(grid_jobs.mem_used) AS max_memory";
		$avg_memory     = "AVG(grid_jobs.mem_used) AS avg_memory";
		$gpu_max_mem    = "MAX(grid_jobs.gpu_max_memory) AS gpu_max_mem";
		$gpu_avg_mem    = "AVG(grid_jobs.gpu_max_memory) AS gpu_avg_mem";
		$sql_where      = "WHERE ((grid_jobs.end_time>='$previous_date') AND (grid_jobs.end_time<='$scan_date') AND (stat != 'EXIT'))";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "PSUSP":
		$run_type       = 'pre';
		$jobs_wall_time = "'0' AS jobs_wall_time";
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "'0' AS jobs_stime";
		$jobs_utime     = "'0' AS jobs_utime";
		$stat           = "'PSUSP' AS stat";
		$max_memory     = "'0' AS max_memory";
		$avg_memory     = "'0' AS avg_memory";
		$gpu_max_mem    = "'0' AS gpu_max_mem";
		$gpu_avg_mem    = "'0' AS gpu_avg_mem";
		$sql_where      = "WHERE (stat='PSUSP')";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "SSUSP":
		$run_type       = 'post';
		$jobs_wall_time = $wallt_method_susp;
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "SUM(stime) AS jobs_stime";
		$jobs_utime     = "SUM(utime) AS jobs_utime";
		$stat           = "'SSUSP' AS stat";
		$max_memory     = "MAX(grid_jobs.mem_used) AS max_memory";
		$avg_memory     = "AVG(grid_jobs.mem_used) AS avg_memory";
		$gpu_max_mem    = "MAX(grid_jobs.gpu_max_memory) AS gpu_max_mem";
		$gpu_avg_mem    = "AVG(grid_jobs.gpu_max_memory) AS gpu_avg_mem";
		$sql_where      = "WHERE (stat='SSUSP')";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	case "USUSP":
		$run_type       = 'post';
		$jobs_wall_time = $wallt_method_susp;
		$gpu_wall_time  = "'0' AS gpu_wall_time";
		$jobs_stime     = "SUM(stime) AS jobs_stime";
		$jobs_utime     = "SUM(utime) AS jobs_utime";
		$stat           = "'USUSP' AS stat";
		$max_memory     = "MAX(grid_jobs.mem_used) AS max_memory";
		$avg_memory     = "AVG(grid_jobs.mem_used) AS avg_memory";
		$gpu_max_mem    = "MAX(grid_jobs.gpu_max_memory) AS gpu_max_mem";
		$gpu_avg_mem    = "AVG(grid_jobs.gpu_max_memory) AS gpu_avg_mem";
		$sql_where      = "WHERE (stat='USUSP')";
		$exec_host      = "grid_jobs.exec_host AS exec_host";

		break;
	}

	if ($run_type == 'pre') {
		$records = db_fetch_assoc("SELECT
			grid_jobs.clusterid,
			grid_jobs.user,
			$stat,
			grid_jobs.queue,
			$app,
			$from_hosts,
			$exec_host,
			$project_names AS projectName,
			Count(grid_jobs.jobid) AS jobs_reaching_state,
			$jobs_wall_time,
			$gpu_wall_time,
			$jobs_stime,
			$jobs_utime,
			SUM(num_cpus) AS slots_in_state,
			SUM(num_gpus) AS gpus_in_state,
			$max_memory,
			$avg_memory,
			$gpu_max_mem,
			$gpu_avg_mem,
			'$previous_date' AS interval_start,
			'$scan_date' AS interval_end,
			'$scan_date' AS date_recorded
			FROM grid_jobs
			$sql_where
			$clsql
			GROUP BY grid_jobs.clusterid,
			grid_jobs.user,
			grid_jobs.queue,
			$app_groupby
			grid_jobs.exec_host
			$from_hosts_groupby
			$project_groupby");
	} else {
		$records = db_fetch_assoc("SELECT
			grid_jobs.clusterid,
			grid_jobs.user,
			$stat,
			grid_jobs.queue,
			$app,
			$from_hosts,
			$exec_host,
			$project_names AS projectName,
			SUM(job_count) AS jobs_reaching_state,
			$jobs_wall_time,
			$gpu_wall_time,
			$jobs_stime,
			$jobs_utime,
			SUM(num_cpus) AS slots_in_state,
			SUM(num_gpus) AS gpus_in_state,
			$max_memory,
			$avg_memory,
			$gpu_max_mem,
			$gpu_avg_mem,
			'$previous_date' AS interval_start,
			'$scan_date' AS interval_end,
			'$scan_date' AS date_recorded
			FROM (
				SELECT clusterid, jobid, indexid, submit_time, user, stat, queue, app,
				from_host, exec_host AS exec_host, projectName, start_time, end_time, ususp_time, ssusp_time,
				mem_used, stime, utime, num_cpus, 1 AS job_count, num_cpus AS total_cpus,
				run_time, gpu_mode, gpu_exec_time, gpu_max_memory, num_gpus
				FROM grid_jobs
				$sql_where
				AND num_nodes=1
				$clsql
				UNION ALL
				SELECT grid_jobs.clusterid, grid_jobs.jobid, grid_jobs.indexid, grid_jobs.submit_time, user, stat, queue, app,
				from_host, gjj.exec_host AS exec_host, projectName, start_time, end_time, ususp_time, ssusp_time,
				mem_used/num_nodes AS mem_used, stime/num_nodes AS stime, utime/num_nodes AS utime, gjj.processes AS num_cpus, 1 AS job_count, num_cpus AS total_cpus,
				run_time, gpu_mode, gpu_exec_time, gpu_max_memory/num_nodes AS gpu_max_memory, gjj.ngpus AS num_gpus
				FROM grid_jobs
				INNER JOIN grid_jobs_jobhosts AS gjj
				ON grid_jobs.clusterid=gjj.clusterid AND grid_jobs.jobid=gjj.jobid AND grid_jobs.indexid=gjj.indexid AND grid_jobs.submit_time=gjj.submit_time
				$sql_where
				AND num_nodes>1 AND grid_jobs.exec_host=gjj.exec_host
				$clsql_grid_jobs
				UNION ALL
				SELECT grid_jobs.clusterid, grid_jobs.jobid, grid_jobs.indexid, grid_jobs.submit_time, user, stat, queue, app,
				from_host, gjj.exec_host AS exec_host, projectName, start_time, end_time, ususp_time, ssusp_time,
				mem_used/num_nodes AS mem_used, stime/num_nodes AS stime, utime/num_nodes AS utime, gjj.processes AS num_cpus, 0 AS job_count, num_cpus AS total_cpus,
				run_time, gpu_mode, gpu_exec_time, gpu_max_memory/num_nodes AS gpu_max_memory, gjj.ngpus AS num_gpus
				FROM grid_jobs
				INNER JOIN grid_jobs_jobhosts AS gjj
				ON grid_jobs.clusterid=gjj.clusterid AND grid_jobs.jobid=gjj.jobid AND grid_jobs.indexid=gjj.indexid AND grid_jobs.submit_time=gjj.submit_time
				$sql_where
				AND num_nodes>1 AND grid_jobs.exec_host!=gjj.exec_host
				$clsql_grid_jobs
			) AS grid_jobs
			GROUP BY grid_jobs.clusterid,
			grid_jobs.user,
			grid_jobs.queue,
			$app_groupby
			grid_jobs.exec_host
			$from_hosts_groupby
			$project_groupby");
	}

	grid_pump_records($records, "grid_job_interval_stats", $format, true);
}

/**
 * Due to support grid_dailystats.php, "app, app_groupby" have to be insert into patamlist.
 */
function get_daily_stats_parameters(&$table_name_part, &$table_name_union,
									&$from_hosts, &$from_hosts_groupby,
									&$app_name, &$app_groupby,
									&$project_names, &$project_groupby,
									&$wallt_method_done, &$wallt_method_exit,
									$look_in_partition=false, $type = "END",
									$date_start="", $date_end="") {

	global $config;

	if ($look_in_partition == false) {
		$table_name_part  = "pluto";
		/**
		 * The key different between three SQL: num_nodes vs 1, gj.exec_host vs gjj.exec_host
		 * TODO: RTC#240679
		 */
		$table_name_union = "(SELECT * FROM (SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
			gj.from_host, gjj.exec_host AS exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
			gj.mem_used/gj.num_nodes AS mem_used, gj.stime/gj.num_nodes AS stime, gj.utime/gj.num_nodes AS utime, gj.run_time, gj.gpu_mode,
			gjj.processes AS num_cpus, gjj.ngpus AS num_gpus, gj.gpu_exec_time, gj.gpu_max_memory/gj.num_nodes AS gpu_max_memory,
			1 AS job_count, num_cpus AS total_cpus
			FROM grid_jobs_finished as gj
			INNER JOIN grid_jobs_jobhosts_finished as gjj
			ON gj.clusterid=gjj.clusterid and gj.jobid=gjj.jobid and gj.indexid=gjj.indexid and gj.submit_time=gjj.submit_time
			WHERE num_nodes>1
			AND gj.exec_host=gjj.exec_host " . ($date_start!= '' && $date_end!='' ? "
			AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end'":"") . "
			UNION ALL
			SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
			gj.from_host, gjj.exec_host AS exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
			gj.mem_used/gj.num_nodes AS mem_used, gj.stime/gj.num_nodes AS stime, gj.utime/gj.num_nodes AS utime, gj.run_time, gj.gpu_mode,
			gjj.processes AS num_cpus, gjj.ngpus AS num_gpus, gj.gpu_exec_time, gj.gpu_max_memory/gj.num_nodes AS gpu_max_memory,
			0 AS job_count, num_cpus AS total_cpus
			FROM grid_jobs_finished as gj
			INNER JOIN grid_jobs_jobhosts_finished as gjj
			ON gj.clusterid=gjj.clusterid and gj.jobid=gjj.jobid and gj.indexid=gjj.indexid and gj.submit_time=gjj.submit_time
			WHERE num_nodes>1
			AND gj.exec_host!=gjj.exec_host " . ($date_start!= '' && $date_end!='' ? "
			AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end'":"") . "
			UNION ALL
			SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
			gj.from_host, gj.exec_host AS exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
			gj.mem_used, gj.stime, gj.utime, gj.run_time, gj.gpu_mode, gj.num_cpus, gj.num_gpus, gj.gpu_exec_time, gj.gpu_max_memory,
			1 AS job_count, num_cpus AS total_cpus
			FROM grid_jobs_finished AS gj
			WHERE num_nodes=1 " . ($date_start!= '' && $date_end!='' ? "
			AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end')":")") ." AS sally)";
	} else {
		include_once($config["base_path"] . "/plugins/grid/lib/grid_partitioning.php");

		$tables = partition_get_partitions_for_query("grid_jobs_finished", $date_start, $date_end);
		if ( (isset_request_var('status') && get_request_var('status') == "END") || $type=="END"  ) {
			if (cacti_sizeof($tables)) {
				$i = 0;
				foreach ($tables as $table) {
					if ($table!="grid_jobs_finished") {
						$end_range = db_fetch_row("SELECT MIN(end_time) AS min_end, MAX(end_time) AS max_end FROM $table");
						if ($date_start>$end_range["max_end"] || $date_end<$end_range["min_end"]) {
							unset($tables[$i]);
						}
					}
					$i++;
				}
			}
		}
		$table_name_part   = "pluto";
		$table_name_union  = "";
		if (cacti_sizeof($tables)) {
			$i = 1;
			foreach ($tables as $table) {
				$assoc_jobhost_table = str_replace('grid_jobs_finished', "grid_jobs_jobhosts_finished", $table);
				$assoc_table_exist = db_fetch_row_prepared("SHOW TABLES LIKE ?", array($assoc_jobhost_table));
				if (cacti_sizeof($assoc_table_exist) == 0) {
					$jobset = "SELECT * FROM (
						SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
						gj.from_host, gj.exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
						gj.mem_used, gj.stime, gj.utime, gj.run_time, gj.gpu_mode, gj.num_cpus, gj.num_gpus, gj.gpu_exec_time, gj.gpu_max_memory,
						1 AS job_count, num_cpus AS total_cpus
						FROM $table AS gj
						WHERE num_nodes=1 " . ($date_start!= '' && $date_end!='' ? "
						AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end')":")") . " AS jobset$i";
				} else {
					$jobset = "SELECT * FROM (SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
						gj.from_host, gjj.exec_host AS exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
						gj.mem_used/gj.num_nodes AS mem_used, gj.stime/gj.num_nodes AS stime, gj.utime/gj.num_nodes AS utime, gj.run_time, gj.gpu_mode,
						gjj.processes AS num_cpus, gjj.ngpus AS num_gpus, gj.gpu_exec_time, gj.gpu_max_memory/gj.num_nodes AS gpu_max_memory,
						1 AS job_count, num_cpus AS total_cpus
						FROM $table as gj
						INNER JOIN $assoc_jobhost_table as gjj
						ON gj.clusterid=gjj.clusterid and gj.jobid=gjj.jobid and gj.indexid=gjj.indexid and gj.submit_time=gjj.submit_time
						WHERE num_nodes>1
						AND gj.exec_host=gjj.exec_host " . ($date_start!= '' && $date_end!='' ? "
						AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end'":"") . "
						UNION ALL
						SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
						gj.from_host, gjj.exec_host AS exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
						gj.mem_used/gj.num_nodes AS mem_used, gj.stime/gj.num_nodes AS stime, gj.utime/gj.num_nodes AS utime, gj.run_time, gj.gpu_mode,
						gjj.processes AS num_cpus, gjj.ngpus AS num_gpus, gj.gpu_exec_time, gj.gpu_max_memory/gj.num_nodes AS gpu_max_memory,
						0 AS job_count, num_cpus AS total_cpus
						FROM $table as gj
						INNER JOIN $assoc_jobhost_table as gjj
						ON gj.clusterid=gjj.clusterid and gj.jobid=gjj.jobid and gj.indexid=gjj.indexid and gj.submit_time=gjj.submit_time
						WHERE num_nodes>1
						AND gj.exec_host!=gjj.exec_host " . ($date_start!= '' && $date_end!='' ? "
						AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end'":"") . "
						UNION ALL
						SELECT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time, gj.user, gj.stat, gj.queue, gj.app,
						gj.from_host, gj.exec_host, gj.projectName, gj.start_time, gj.end_time, gj.ususp_time, gj.ssusp_time,
						gj.mem_used, gj.stime, gj.utime, gj.run_time, gj.gpu_mode, gj.num_cpus, gj.num_gpus, gj.gpu_exec_time, gj.gpu_max_memory,
						1 AS job_count, num_cpus AS total_cpus
						FROM $table AS gj
						WHERE num_nodes=1 " . ($date_start!= '' && $date_end!='' ? "
						AND " . ($type == "END" ? "end_time":"start_time") . " BETWEEN '$date_start' AND '$date_end')":")") . " AS jobset$i";
				}

				if ($table_name_union == '') {
					$table_name_union = "($jobset ";
				} else {
					$table_name_union .= "UNION ALL $jobset ";
				}

				$i++;
			}

			$table_name_union .= ") ";
		}
	}

	if (read_config_option("grid_job_stats_fromhost_enabled") == "") {
		$from_hosts = "'Not Collected' as from_host";
		$from_hosts_groupby = "";
	} else {
		$from_hosts = "SUBSTRING_INDEX(from_host, ':', 1) AS from_host";
		$from_hosts_groupby = ", SUBSTRING_INDEX(from_host, ':', 1)";
	}

	if (read_config_option("grid_job_stats_app_enabled") == "") {
		$app_name = "'Not Collected' as app";
		$app_groupby = "";
	} else {
		$app_name = "app";
		$app_groupby = "app, ";
	}

	if (read_config_option("grid_job_stats_project_enabled") == "") {
		$project_names = "'Not Collected'";
		$project_groupby = "";
	} else {
		//$project_names = get_project_aggregation_string();
		$project_names = get_project_aggregation_string("0", "grid_project_group_aggregation", true);
		$project_groupby = ", " . $project_names;
	}

	if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-ususp_time-ssusp_time)*num_cpus ELSE 0 END)";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time)-ssusp_time-ususp_time)*num_cpus END)";
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), ((CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN (gpu_exec_time-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	} else {
		$wallt_method_done    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*num_cpus ELSE 0 END)";
		$wallt_method_exit    = "SUM(CASE WHEN UNIX_TIMESTAMP(start_time)=0 AND UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN 0 ELSE (UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time))*num_cpus END)";
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), (CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN gpu_exec_time*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	}
}

function summarize_grid_data_bytime($scan_date, $current_date) {
	$previous_date = date("Y-m-d H:i:s", strtotime($scan_date) - 86400);
	$data_recorded = date("Y-m-d H:i:s", $current_date);

	grid_debug("Updating Daily Statistics");

	$table_name_part    = '';
	$table_name_union   = '';
	$app_name           = '';
	$app_groupby        = '';
	$from_hosts         = '';
	$from_hosts_groupby = '';
	$project_names      = '';
	$project_groupby    = '';
	$wallt_method_done  = '';
	$wallt_method_exit  = '';

	get_daily_stats_parameters($table_name_part, $table_name_union,
		$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
		$project_names, $project_groupby, $wallt_method_done,
		$wallt_method_exit, false, "END", $previous_date, $scan_date);

	$on_duplicate = "ON DUPLICATE KEY UPDATE jobs_wall_time=VALUES(jobs_wall_time), jobs_utime=VALUES(jobs_utime),
		slots_in_state=VALUES(slots_in_state), max_memory=VALUES(max_memory), avg_memory=VALUES(avg_memory), jobs_stime=VALUES(jobs_stime),
		gpu_wall_time=VALUES(gpu_wall_time), gpu_avg_mem=VALUES(gpu_avg_mem), gpu_max_mem=VALUES(gpu_max_mem), gpus_in_state=VALUES(gpus_in_state),
		date_recorded=VALUES(date_recorded)";
	$on_duplicate_started = "ON DUPLICATE KEY UPDATE slots_in_state=VALUES(slots_in_state), gpus_in_state=VALUES(gpus_in_state),
		date_recorded=VALUES(date_recorded)";

	$clusters = array_rekey(db_fetch_assoc("SELECT clusterid FROM grid_clusters WHERE disabled=''"), "clusterid", "clusterid");

	$clsql = get_cluster_list();

	grid_debug("Entering Ended/Exited Jobs to Database for $clsql");

	/**
	 * ToDo: Keep '$gpu_wallt_method_excl' generation for patch/hotfix. It should be moved into function#get_daily_stats_parameters during FixPack release
	 */
	if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), ((CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN (gpu_exec_time-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	} else {
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), (CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN gpu_exec_time*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	}
	db_execute("INSERT INTO grid_job_daily_stats
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, jobs_wall_time, gpu_wall_time, jobs_stime, jobs_utime,
		slots_in_state, gpus_in_state, max_memory, avg_memory, gpu_max_mem, gpu_avg_mem,
		interval_start, interval_end, date_recorded)
		SELECT
		$table_name_part.clusterid,
		$table_name_part.user,
		(CASE WHEN stat = 'EXIT' THEN 'EXITED' ELSE 'ENDED' END) as stat,
		$table_name_part.queue,
		$app_name,
		$from_hosts,
		$table_name_part.exec_host,
		$project_names AS projectName,
		SUM($table_name_part.job_count) AS jobs_in_state,
		(CASE WHEN stat = 'EXIT' THEN $wallt_method_exit ELSE $wallt_method_done END) AS jobs_wall_time,
		$gpu_wallt_method_excl AS gpu_wall_time,
		SUM(stime) AS jobs_stime,
		SUM(utime) AS jobs_utime,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		MAX($table_name_part.mem_used) AS max_memory,
		AVG($table_name_part.mem_used) AS avg_memory,
		MAX($table_name_part.gpu_max_memory) AS gpu_max_memory,
		AVG($table_name_part.gpu_max_memory) AS gpu_avg_memory,
		'$previous_date' AS interval_start,
		'$scan_date' AS interval_end,
		'$data_recorded' AS date_recorded
		FROM  $table_name_union AS $table_name_part " .
		(strlen($clsql) ? "WHERE $clsql ":"") . "
		GROUP BY $table_name_part.clusterid,
		$table_name_part.user,
		$table_name_part.queue,
		$app_groupby
		$table_name_part.exec_host,
		$table_name_part.stat
		$from_hosts_groupby
		$project_groupby
		$on_duplicate");

	grid_debug("Entering Started Jobs to Database for $clsql");

	get_daily_stats_parameters($table_name_part, $table_name_union,
		$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
		$project_names, $project_groupby, $wallt_method_done,
		$wallt_method_exit, false, "START", $previous_date, $scan_date);

	/* Do jobs table first */
	db_execute("INSERT INTO grid_job_daily_stats
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, slots_in_state, gpus_in_state,
		interval_start, interval_end, date_recorded)
		SELECT
		clusterid,
		user,
		'STARTED' as stat,
		queue,
		$app_name,
		$from_hosts,
		exec_host,
		$project_names AS projectName,
		COUNT(jobid) AS jobs_in_state,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		'$previous_date' AS interval_start,
		'$scan_date' AS interval_end,
		'$data_recorded' AS date_recorded
		FROM grid_jobs
		WHERE stat NOT IN ('DONE', 'EXIT') " . (strlen($clsql) ? "AND $clsql ":"") . "
		GROUP BY
		clusterid,
		user,
		queue,
		$app_groupby
		exec_host
		$from_hosts_groupby
		$project_groupby
		$on_duplicate_started");

	/* now let's do job finished table */
	db_execute("INSERT INTO grid_job_daily_stats
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, slots_in_state, gpus_in_state,
		interval_start, interval_end, date_recorded)
		SELECT
		clusterid,
		user,
		'STARTED' as stat,
		queue,
		$app_name,
		$from_hosts,
		exec_host,
		$project_names AS projectName,
		COUNT(jobid) AS jobs_in_state,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		'$previous_date' AS interval_start,
		'$scan_date' AS interval_end,
		'$data_recorded' AS date_recorded
		FROM grid_jobs_finished " .
		(strlen($clsql) ? "WHERE $clsql ":"") . "
		GROUP BY
		clusterid,
		user,
		queue,
		$app_groupby
		exec_host
		$from_hosts_groupby
		$project_groupby
		$on_duplicate_started");
}

function grididle_check_for_idle_jobs() {
	global $job;

	/* record the start time */
	$start_time           = microtime(true);

	$clusters = db_fetch_assoc("SELECT * FROM grid_clusters where disabled !='on'");
	$idle_jobs=0;

	/* prep for subsequent deletion */
	db_execute("UPDATE grid_jobs_idled SET present=0 WHERE present=1");
	$grididle_prefix = " INSERT INTO grid_jobs_idled (clusterid, jobid, indexid, submit_time, cumulative_cpu, present) VALUES ";
	$grididle_suffix = " ON DUPLICATE KEY UPDATE cumulative_cpu=VALUES(cumulative_cpu), present=VALUES(present)";

	foreach ($clusters as $cluster) {

		$grididle_out_buffer = "";

		/* no enabled for the cluster.  just proceed to the next cluster */
		if ($cluster["grididle_enabled"] != 'on') {

			continue;
		}

		$sql_where = "";
		$sql_params = array();

		$run_time    = $cluster["grididle_runtime"];
		$idle_window = $cluster["grididle_window"];
		$cpu_time    = $cluster["grididle_cputime"];

		if (strlen(trim($cluster["grididle_exclude_queues"]))) {
			$new_queues = "";
			$queues     = explode("|", $cluster["grididle_exclude_queues"]);

			foreach ($queues as $queue) {
				if (strlen($new_queues)) {
					$new_queues .= ", '" . $queue . "'";
				} else {
					$new_queues .= "'" . $queue . "'";
				}
			}

			$exclude_queue_where = " AND queue NOT IN (" . $new_queues . ") ";
		} else {
			$exclude_queue_where = "";
		}

		$sql_where .= (strlen($sql_where) ? " AND " : "WHERE ") . "clusterid=?" . " AND run_time>?" . " AND stat IN('RUNNING','PROV') " . $exclude_queue_where;
		$sql_params[] = $cluster["clusterid"];
		$sql_params[] = $run_time;

		/* get jobs that match the criteria */
		$jobs = db_fetch_assoc_prepared("SELECT clusterid, jobid, indexid, submit_time, jobname, options, command FROM grid_jobs $sql_where", $sql_params);

		grid_debug("NOTE: GRIDIDLE Returned '" . cacti_sizeof($jobs) . "' Jobs for Cluster:" . $cluster["clustername"]);


		if (cacti_sizeof($jobs)) {
			$first_idled_job = 1;

			foreach ($jobs as $job) {

				if ($first_idled_job == 1) {
					$delim = " ";
				} else {
					$delim = ", ";
				}

				$capture      = false;
				$idle_capture = false;
				switch($cluster["grididle_jobtypes"]) {
				case "excl":
					if ($job["options"] & SUB_EXCLUSIVE) {
						$capture = true;
					}
					break;
				case "inter":
					if ($job["options"] & SUB_INTERACTIVE) {
						$capture = true;
					}
					break;
				case "all":
					if (!strlen($cluster["grididle_jobcommands"])) {
						$capture = true;
					}
					break;
				case "interexcl":
					if ($job["options"] & SUB_INTERACTIVE || $job["options"] & SUB_EXCLUSIVE) {
						$capture = true;
					}
					break;
				}

				if (strlen($cluster["grididle_jobcommands"]) && preg_match("/" . $cluster["grididle_jobcommands"] . "/", $job["command"]) ) {
					$capture = true;
				}

				if (!$capture) {
					$capture = api_plugin_hook_function("grididle_verify_command", $capture);
				}

				$date = date("Y-m-d H:i:s" , time()-$idle_window);

				$curcpu_result = db_fetch_row_prepared("SELECT UNIX_TIMESTAMP(last_updated) AS update_time, stime+utime AS total_cpu
					FROM grid_jobs
					WHERE clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?", array($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"]));

				$lastcpu_result = get_rusage_records($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"], $date, 1, true);

				if (cacti_sizeof($curcpu_result) == 0 || cacti_sizeof($lastcpu_result) == 0) {
					if (cacti_sizeof($curcpu_result) != 0 && $curcpu_result["total_cpu"]==0) {
						$curcpu = 0;
						$lastcpu = 0;
						$idle_capture = true;
					} else {

					grid_debug("NOTE: GRIDIDLE - ClusterId:" . $job["clusterid"] . ", JobId:" . $job["jobid"] . ", IndexId:" . $job["indexid"] . ", SubmitTime:" . $job["submit_time"] . " is missing rusage data");
					continue;
					}
				}
				/* check if we actually have enough rusage to meetin time windows.  give it a 10 minute buffer time*/
				elseif ($curcpu_result["update_time"] - $lastcpu_result["update_time"] < ($idle_window - 600)) {
					grid_debug("NOTE: GRIDIDLE - ClusterId:" . $job["clusterid"] . ", JobId:" . $job["jobid"] . ", IndexId:" . $job["indexid"] . ", SubmitTime:" . $job["submit_time"] . " does not have enough rusage data to satisfy idle job window");
					continue;
				}
				else {

					$curcpu = $curcpu_result["total_cpu"];
					$lastcpu = $lastcpu_result["total_cpu"];
				}

				if ($curcpu-$lastcpu < $cpu_time) {
					$idle_capture = true;
				}

				if ($idle_capture && $capture) {

					if ($first_idled_job == 1) {
						$first_idled_job = 0;
					}

					$grididle_out_buffer .= $delim . " (" . $job["clusterid"] . "," . $job["jobid"] . "," . $job["indexid"] . ",'" . $job["submit_time"] . "'," . ($curcpu-$lastcpu) . ",1) ";

					/* do bulk insert of 640k */
					if (strlen($grididle_prefix) + strlen($grididle_out_buffer) + strlen($grididle_suffix) > 640000 ) {

						db_execute($grididle_prefix . $grididle_out_buffer . $grididle_suffix);
						$grididle_out_buffer = "";
						$first_idled_job = 1;
					}

					$idle_jobs++;
				}
			}
		}

		if (strlen($grididle_out_buffer) > 0) {

			db_execute($grididle_prefix . $grididle_out_buffer . $grididle_suffix);
		}
	}

	/* get rid of old jobs */
	db_execute("DELETE FROM grid_jobs_idled WHERE present=0");

	/* record the end time */
	$end_time             = microtime(true);

	grididle_notify_users();
	cacti_log("GRIDIDLE STATS: Time:" . round($end_time-$start_time,2) . " IdleJobs:" . $idle_jobs, true, "SYSTEM");
}

function grididle_notify_users() {
	global $config;

	/* get a list of idle jobs w/o notification */
	$idle_jobs = db_fetch_assoc("SELECT
		gji.*, gj.user, gj.command,
		gc.grididle_notify, grididle_runtime, grididle_window, grididle_cputime,
		gj.queue, gj.max_memory, gj.mem_reserved, gj.mem_requested, gj.start_time, gj.end_time
		FROM grid_jobs gj
		INNER JOIN grid_jobs_idled gji
		INNER JOIN grid_clusters gc
		ON gj.jobid=gji.jobid
		AND gj.indexid=gji.indexid
		AND gj.clusterid=gji.clusterid
		AND gj.clusterid=gc.clusterid
		WHERE gc.grididle_notify > 0 AND notified=0");

	$taginc    = false;
	$output    = "";
	$formatok  = false;
	$subject   = read_config_option("grididle_subject");
	$message   = read_config_option("grididle_message");

	if (cacti_sizeof($idle_jobs)) {
		/* load up the format file */
		$formatok  = grid_load_format_file("idle.format", $output, $taginc);

		$cluster = array();
		foreach ($idle_jobs as $job) {

			$notify    = $job["grididle_notify"];
			$runtime   = $job["grididle_runtime"];
			$window    = $job["grididle_window"];
			$cpusecs   = $job["grididle_cputime"];

			if ($notify > 0) {
				if (!isset($cluster[$job["clusterid"]])) {
					$cluster[$job["clusterid"]] = db_fetch_row_prepared("SELECT * FROM grid_clusters WHERE clusterid=?", array($job["clusterid"]));
				}
				$cc = $cluster[$job["clusterid"]];

				$email = grid_format_email_addresses($cc, $job, $notify);

				$url = read_config_option("base_url") . "plugins/grid/grid_bjobs.php?action=viewjob" .
					"&clusterid=" . $job["clusterid"] .
					"&jobid=" . $job["jobid"] .
					"&indexid=" . $job["indexid"] .
					"&submit_time=" . strtotime($job["submit_time"]) .
					"&start_time=" . strtotime($job['start_time']) . '&end_time=' . strtotime($job['end_time']);
				$url = "<a class='pic' href='" . html_escape($url) . "'>".$url."</a>";

				/* replace all tags in the e-mail */
				$outmessage = grid_replace_tags($message, $cc, $job, array("<CPUSECS>" => $job["cumulative_cpu"], "<URL>" => $url, "<WINDOW>" => $window, "<RUNTIME>" => $runtime));
				$outsubject = grid_replace_tags($subject, $cc, $job, array("<CPUSECS>" => $job["cumulative_cpu"], "<URL>" => $url, "<WINDOW>" => $window, "<RUNTIME>" => grid_format_minutes($runtime, true)));

				/* merge the e-mail with the format */
				if ($formatok) {
					if ($taginc) {
						$htmlmessage = str_replace("<REPORT>", $outmessage, $output);
					} else {
						$htmlmessage = $output . $outmessage;
					}
				}
				$htmlmessage = "<html>".$htmlmessage."</html>";
				$txtmessage = str_replace('<br>',  "\n", $outmessage);
				$txtmessage = str_replace('<BR>',  "\n", $txtmessage);
				$txtmessage = str_replace('</BR>', "\n", $txtmessage);
				$txtmessage = strip_tags($txtmessage);

				if (strlen($email)) {
					grid_send_mail($email, read_config_option("thold_from_email"), read_config_option("thold_from_name"), $outsubject, $htmlmessage, $txtmessage);
				}

				/* only notify once */
				db_execute_prepared("UPDATE grid_jobs_idled SET notified=1
					WHERE clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?",
					array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));
			}
		}
	}
}

function move_records_into_finished_table() {
	global $cnn_id;
	if (!defined("TERM_REQUEUE_ADMIN")) {
		define('TERM_REQUEUE_ADMIN',11);
	}
	if (!defined("TERM_REQUEUE_OWNER")) {
		define('TERM_REQUEUE_OWNER',10);
	}
	if (!defined("TERM_PREEMPT")) {
		define('TERM_PREEMPT',1);
	}

	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		cacti_log("INFO: Moving Finished Job Records Begein");
	}

	$efficiency_window    = read_config_option('grid_efficiency_window');
	$refresh_query        = "";
	$current_time         = time();
	$prev_time            = db_fetch_cell("SELECT MAX(`value`) FROM settings WHERE `name` LIKE 'poller_lastrun%'", false);
	if (empty($prev_time)) {
		$prev_time      = $current_time - 300;
		$interval_time  = 300;
	} else {
		$interval_time  = $current_time-$prev_time+300;
	}

	$counter = 0;
	$wait = true;
	while ($wait) {
		$counter++;
		$row = db_fetch_row("show tables like 'grid_jobs_finished'");
		if ((isset($row) && cacti_sizeof($row) >= 1) || $counter > 60) {/*here we wait at most 1 minute*/
			$wait = false;
		} else {
			/*grid_jobs_finished does not exist due to db maintain, so let's wait for a while.*/
			sleep(1);
		}
	}

	grid_debug("NOTE: GRIDMOVE Transferring Recently Completed Jobs to Jobs Finished");
	db_execute_prepared("INSERT INTO grid_jobs_finished
		SELECT *
		FROM grid_jobs
		WHERE stat IN ('DONE', 'EXIT')
		AND exitInfo NOT IN (" . TERM_REQUEUE_ADMIN . "," . TERM_REQUEUE_OWNER . ")
		AND grid_jobs.last_updated>=FROM_UNIXTIME(UNIX_TIMESTAMP()-?)
		ON DUPLICATE KEY UPDATE
			pend_time=VALUES(pend_time),
			stat=VALUES(stat),
			run_time=VALUES(run_time),
			exitInfo=VALUES(exitInfo),
			end_time=VALUES(end_time),
			last_updated=NOW()", array($interval_time));

	grid_debug("NOTE: GRIDMOVE Transferring Recently Completed Jobs Information to Jobs Hosts");
	db_execute_prepared("INSERT INTO grid_jobs_jobhosts_finished
		SELECT grid_jobs_jobhosts.*
		FROM grid_jobs_jobhosts
		INNER JOIN grid_jobs
		ON grid_jobs_jobhosts.clusterid=grid_jobs.clusterid
		AND grid_jobs_jobhosts.jobid=grid_jobs.jobid
		AND grid_jobs_jobhosts.indexid=grid_jobs.indexid
		AND grid_jobs_jobhosts.submit_time=grid_jobs.submit_time
		WHERE grid_jobs.stat IN ('DONE', 'EXIT')
		AND grid_jobs.last_updated>=FROM_UNIXTIME(UNIX_TIMESTAMP()-?)
		ON DUPLICATE KEY UPDATE
			exec_host=VALUES(exec_host),
			processes=VALUES(processes)", array($interval_time));

	grid_debug("NOTE: GRIDMOVE Transferring Recently Completed Jobs Information to Jobs ReqHosts");
	db_execute_prepared("INSERT INTO grid_jobs_reqhosts_finished
		SELECT grid_jobs_reqhosts.*
		FROM grid_jobs_reqhosts
		INNER JOIN grid_jobs
		ON grid_jobs_reqhosts.clusterid=grid_jobs.clusterid
		AND grid_jobs_reqhosts.jobid=grid_jobs.jobid
		AND grid_jobs_reqhosts.indexid=grid_jobs.indexid
		AND grid_jobs_reqhosts.submit_time=grid_jobs.submit_time
		WHERE grid_jobs.stat IN ('DONE', 'EXIT')
		AND grid_jobs.last_updated>=FROM_UNIXTIME(UNIX_TIMESTAMP()-?)
		ON DUPLICATE KEY UPDATE
			host=values(host)", array($interval_time));

	grid_debug("NOTE: GRIDMOVE Transferring Recently Completed Jobs Information to Jobs Pending Reasons");
	db_execute_prepared("INSERT INTO grid_jobs_pendreasons_finished
		SELECT grid_jobs_pendreasons.*
		FROM grid_jobs_pendreasons
		INNER JOIN grid_jobs
		ON grid_jobs_pendreasons.clusterid=grid_jobs.clusterid
		AND grid_jobs_pendreasons.jobid=grid_jobs.jobid
		AND grid_jobs_pendreasons.indexid=grid_jobs.indexid
		AND grid_jobs_pendreasons.submit_time=grid_jobs.submit_time
		WHERE grid_jobs.stat IN ('DONE', 'EXIT')
		AND grid_jobs.last_updated>=FROM_UNIXTIME(UNIX_TIMESTAMP()-?)
		ON DUPLICATE KEY UPDATE
			reason=values(reason),
			subreason=values(subreason)", array($interval_time));

	grid_debug("NOTE: GRIDMOVE Transferring Recently Completed Jobs Information to Jobs SLA Loaning");
	db_execute_prepared("INSERT INTO grid_jobs_sla_loaning_finished
		SELECT grid_jobs_sla_loaning.*
		FROM grid_jobs_sla_loaning
		INNER JOIN grid_jobs
		ON grid_jobs_sla_loaning.clusterid=grid_jobs.clusterid
		AND grid_jobs_sla_loaning.jobid=grid_jobs.jobid
		AND grid_jobs_sla_loaning.indexid=grid_jobs.indexid
		AND grid_jobs_sla_loaning.submit_time=grid_jobs.submit_time
		WHERE grid_jobs.stat IN ('DONE', 'EXIT')
		AND grid_jobs.last_updated>=FROM_UNIXTIME(UNIX_TIMESTAMP()-?)
		ON DUPLICATE KEY UPDATE
			numRsrc=values(numRsrc),
			mem=values(mem)", array($interval_time));

	grid_debug("NOTE: GRIDMOVE Prepping User Level Queue Stats");
	db_execute("UPDATE grid_queues_users_stats SET present=0");

	grid_debug("NOTE: GRIDMOVE Set Queue Level User Statistics");
	db_execute("INSERT INTO grid_queues_users_stats (clusterid, queue, user_or_group, nojobs,
		pendjobs, runjobs, suspjobs, efficiency, present)
		SELECT clusterid, queue, user as user_or_group,
			SUM(CASE WHEN stat IN ('PEND', 'PSUSP') THEN maxNumProcessors ELSE num_cpus END) AS 'nojobs',
			SUM(CASE WHEN stat IN ('PEND', 'PSUSP') THEN maxNumProcessors ELSE 0 END) AS 'pendjobs',
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) AS 'runjobs',
			SUM(CASE WHEN stat IN ('SSUSP', 'USUSP') THEN num_cpus ELSE 0 END) AS 'suspjobs',
			AVG(CASE WHEN stat='RUNNING' THEN efficiency ELSE 0 END) AS 'efficiency',
			'1' as present
			FROM grid_jobs
			WHERE job_end_logged=0
			GROUP BY clusterid, queue, user
		ON DUPLICATE KEY UPDATE present=VALUES(present),
			nojobs=VALUES(nojobs),
			pendjobs=VALUES(pendjobs),
			runjobs=VALUES(runjobs),
			suspjobs=VALUES(suspjobs),
			efficiency=VALUES(efficiency)");

	grid_debug("NOTE: GRIDMOVE Clear Inactive Users");
	db_execute("DELETE FROM grid_queues_users_stats WHERE present=0");

	grid_debug("NOTE: GRIDMOVE Set User Level Efficiency Statistics");

	db_execute_prepared("INSERT INTO grid_users_or_groups (clusterid, user_or_group, efficiency)
		SELECT clusterid, user AS user_or_group, AVG(efficiency)
		FROM grid_jobs
		WHERE stat='RUNNING' AND run_time>?
		GROUP BY clusterid, user_or_group
		ON DUPLICATE KEY UPDATE
			efficiency=VALUES(efficiency)", array($efficiency_window));
}

function grid_check_for_runtime_jobs() {
	global $job;
	if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
		cacti_log("INFO: Checking job RUMLIMIT alert");
	}

	/* record the start time */
	$start_time           = microtime(true);

	$jobs='';
	$jobs_runtime='';

	$clusters = db_fetch_assoc("SELECT * FROM grid_clusters where disabled !='on'");
	$runtime_jobs=0;

	/* prep for subsequent deletion */
	db_execute("UPDATE grid_jobs_runtime SET present=0");
	$gridruntime_prefix = " INSERT INTO grid_jobs_runtime (clusterid, jobid, indexid, submit_time, rlimit_max_wallt, runtimeEstimation,run_time,type,present) ";
	$gridruntime_suffix = " ON DUPLICATE KEY UPDATE run_time=VALUES(run_time),present=VALUES(present)";

	foreach ($clusters as $cluster) {
		$sql_where = "";
		$gridruntime_out_buffer = "";
		$runtimevio=read_config_option("gridrunlimitvio_threshold");

		/* no enabled for the cluster.  just proceed to the next cluster */
		if (read_config_option("gridrunestivio_enabled")==1) {
			continue;
		} elseif (read_config_option("gridrunlimitvio_enabled") == 4) {
			$sql_where = "WHERE clusterid=" . $cluster["clusterid"] . "
				AND rlimit_max_wallt != 0
				AND run_time> (1-" .$runtimevio. ")*rlimit_max_wallt
				AND stat IN ('RUNNING','PROV')" ;

			$type=1;

			/* get jobs that match the criteria */
			$jobs = "SELECT clusterid, jobid, indexid, submit_time, rlimit_max_wallt,
				0 AS runtimeEstimation, run_time,$type AS type,
				1 AS present
				FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);

			$sql_where = "WHERE clusterid=" . $cluster["clusterid"] . "
				AND runtimeEstimation!=0
				AND rlimit_max_wallt!=0
				AND run_time > (1-" .$runtimevio. ")*runtimeEstimation
				AND run_time < (1-" .$runtimevio. ")*rlimit_max_wallt
				AND stat IN ('RUNNING','PROV')" ;

			$type=2;

			/* get jobs that match the criteria */

			$jobs = "SELECT clusterid, jobid, indexid, submit_time, rlimit_max_wallt,
				runtimeEstimation, run_time, $type AS type,
				1 as present FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);

			$sql_where = "WHERE clusterid=" . $cluster["clusterid"] . "
				AND runtimeEstimation!=0
				AND rlimit_max_wallt =0
				AND (1-" .$runtimevio. ")*runtimeEstimation < run_time
				AND stat IN ('RUNNING','PROV')" ;

			$type=2;

			/* get jobs that match the criteria */

			$jobs = "SELECT clusterid, jobid, indexid, submit_time,
				0 AS rlimit_max_wallt, runtimeEstimation, run_time,$type AS type,
				1 AS present
				FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);
		} elseif (read_config_option("gridrunlimitvio_enabled") == 2) {
			$sql_where = "WHERE clusterid=" . $cluster["clusterid"] . "
				AND runtimeEstimation!=0
				AND run_time> (1-" .$runtimevio. ")*runtimeEstimation
				AND stat IN ('RUNNING','PROV')" ;

			$type=2;

			/* get jobs that match the criteria */
			$jobs = "SELECT clusterid, jobid, indexid, submit_time, 0 as rlimit_max_wallt,
				runtimeEstimation, run_time,$type AS type,
				1 AS present
				FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);
		} elseif (read_config_option("gridrunlimitvio_enabled") == 3) {
			$sql_where ="WHERE clusterid=" . $cluster["clusterid"] . "
				AND rlimit_max_wallt !=0
				AND run_time> (1-" .$runtimevio. ")*rlimit_max_wallt
				AND stat IN ('RUNNING','PROV')" ;

			$type=1;

			/* get jobs that match the criteria */
			$jobs = "SELECT clusterid, jobid, indexid, submit_time, rlimit_max_wallt,
				0 AS runtimeEstimation, run_time,$type AS type,
				1 AS present
				FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);
		}

		if (read_config_option("gridrunlimitkilled_enabled") =='on') {
			$sql_where = "WHERE exitinfo=5 AND stat='EXIT'";

			$type=3;

			/* get jobs that match the criteria */
			$jobs = "SELECT clusterid, jobid, indexid, submit_time, rlimit_max_wallt,
				runtimeEstimation, run_time, $type AS type,
				1 as present
				FROM grid_jobs ". $sql_where;

			db_execute($gridruntime_prefix .  $jobs . $gridruntime_suffix);
		}

		grid_debug("NOTE: GRIDRUNTIME Returned '" . cacti_sizeof($jobs) . "' Jobs for Cluster:" . $cluster["clustername"]);
	}

	/* get rid of old jobs */
	db_execute("DELETE FROM grid_jobs_runtime WHERE present=0");

	/* record the end time */
	$end_time = microtime(true);

	gridruntime_notify_users();

	cacti_log("GRIDRUNTIME STATS: Time:" . round($end_time-$start_time,2) . " Jobruntime violation:" . $jobs_runtime, true, "SYSTEM");
}

function 	gridruntime_notify_users() {
	global $config;

	$runlimit_message=read_config_option("gridrunlimitvio_message");
	$runlimit_subject=read_config_option("gridrunlimitvio_subject");
	$runlimit_notify=read_config_option("gridrunlimitvio_notify");
	$runest_message=read_config_option("gridruntimeest_message");
	$runest_subject=read_config_option("gridruntimeest_subject");
	$runest_notify=read_config_option("gridruntimeest_notify");
	$jobkilled_message=read_config_option("gridjobkilled_message");
	$jobkilled_subject=read_config_option("gridjobkilled_subject");
	$jobkilled_notify=read_config_option("gridjobkilled_notify");
	$runtime_threshold=read_config_option("gridrunlimitvio_threshold");



	$job_summary =db_fetch_assoc("SELECT COUNT(gj.jobid) as total_jobs, gj.clusterid, gj.mailuser, gj.user,grj.type from grid_jobs gj
		INNER JOIN grid_jobs_runtime grj
		ON grj.clusterid =gj.clusterid
		AND grj.jobid =gj.jobid
		AND grj.indexid = gj.indexid
		AND grj.submit_time=gj.submit_time
		WHERE grj.notified=0
		GROUP BY gj.mailuser, gj.user, grj.type");

	if (cacti_sizeof($job_summary)) {
		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
			cacti_log("job_summary is not 0");
		}

		foreach ($job_summary as $record) {
			$outstr="";
			$clusters[$record["clusterid"]] = db_fetch_row_prepared("SELECT * FROM grid_clusters WHERE clusterid=?", array($record["clusterid"]));
			$cc = $clusters[$record["clusterid"]];
			$jobs=db_fetch_assoc_prepared("SELECT gj.mailUser,gj.user,gj.rlimit_max_wallt as runlimit,gj.runtimeEstimation as runestimation,grj.* FROM grid_jobs_runtime grj
				INNER JOIN grid_jobs gj
				ON grj.clusterid =gj.clusterid
				AND grj.jobid =gj.jobid
				AND grj.indexid = gj.indexid
				AND grj.submit_time=gj.submit_time
				WHERE gj.clusterid=?
				AND gj.mailuser=?
				AND gj.user=?
				AND grj.type=?
				AND grj.notified=0", array($record["clusterid"], $record["mailuser"], $record["user"], $record["type"]));
			if ($record["type"] ==1) {
				$email_message=$runlimit_message;
				$email_subject=$runlimit_subject;
				$notify=$runlimit_notify;
			} elseif ($record["type"] ==2) {
				$email_message=$runest_message;
				$email_subject=$runest_subject;
				$notify=$runest_notify;
			} elseif ($record["type"] ==3) {
				$email_message=$jobkilled_message;
				$email_subject=$jobkilled_subject;
				$notify=$jobkilled_notify;
			} else {
				cacti_log("INFO: gridruntime_notify_users can not get the email message and subject");
			}
			if (read_config_option("log_verbosity") > POLLER_VERBOSITY_DEBUG)
				cacti_log("job is ".$jobs[0]["jobid"]);

			$job=$jobs[0];
			if (read_config_option("log_verbosity") > POLLER_VERBOSITY_DEBUG)
				cacti_log("job actually is ".$job["jobid"]);

			$email = grid_format_email_addresses($cc, $job, $notify);
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)
			cacti_log("email is ".$email);

			if ($notify < 3 && strlen($cc["username"])) {
				if ($cc["email_domain"] != "") {
					$email .= (strlen($email) ? ", ":"") . $cc["username"] . "@" . $cc["email_domain"];
				} else {
					$email .= (strlen($email) ? ", ":"") . $cc["username"];
				}
			}



			$formatok  = grid_load_format_file("runtime.format", $output, $taginc);
			/* format the table */
			$table_header = array(
				"Cluster<br>Name", "Jobid","Job<br>Index", "Submit<br>Time", "Runtime",
				"Runtime<br>Limit", "Runtime<br>Estimation", "Submit<br>User","Runtime<br>Threshold");
			$table_header  = api_plugin_hook_function("grid_rumtime_header", $table_header);

			/* create the header */
			$tablehead = "<table id='report_table'>";
			$tablehead .= "<tr id='report_header'>";
			foreach ($table_header as $item) {
				$tablehead .= "<th>" . $item . "</th>";
			}
			$tablehead .= "</tr>";
			$tablefoot  = "</table>";

			$tag_array = array(
				"<CLUSTERNAME>"   => $cc["clustername"],
				"<RUNTIMETHRESHOLD>"     => $runtime_threshold*100 . " %",
				"<User>"    => $job['user'],
				);


			/* replace all tags in the e-mail */
			$outmessage = grid_replace_tags($email_message, $cc, $job, $tag_array);
			$outsubject = grid_replace_tags($email_subject, $cc, $job, $tag_array);

			/* merge the e-mail with the format */
			if ($formatok) {
				if ($taginc) {
					$htmlmessage = str_replace("<REPORT>", $outmessage, $output);
				} else {
					$htmlmessage = $output . $outmessage;
				}
			}
			$htmlmessage = "<html>".$htmlmessage."</html>";
			$txtmessage = str_replace('<br>',  "\n", $outmessage);
			$txtmessage = str_replace('<BR>',  "\n", $txtmessage);
			$txtmessage = str_replace('</BR>', "\n", $txtmessage);
			$txtmessage = strip_tags($txtmessage);

			$jobdetail_table_content = api_plugin_hook_function("grid_runtime_content",
				"<tr id='report_row'><td><CLUSTERNAME></td><td><JOBID></td><td><JOBINDEX></td><td><SUBMITTIME></td>" .
				"<td><RUNTIME></td><td><RUNTIMELIMIT></td><td><RUNTIME ESTIMATION></td><td><USER></td><td><RUNTIME THRESHOLD></td></tr>");
			foreach ($jobs as $jjob) {
				if (read_config_option("log_verbosity") > POLLER_VERBOSITY_DEBUG) {

					cacti_log("the foreach is jjob");
					cacti_log(str_replace("\n", "", print_r($jjob, true)) . "\n");
				}
				$jobdetail_tag_array = array(
					"<CLUSTERNAME>"   => $cc["clustername"],
					"<JOBID>"	  => $jjob["jobid"] ,
					"<JOBINDEX>"  	  => $jjob["indexid"],
					"<SUBMITTIME>"     => $jjob["submit_time"],
					"<RUNTIME>"       => $jjob["run_time"],
					"<USER>"          => $jjob["user"],
					"<RUNTIMELIMIT>"     => $jjob["runlimit"],
					"<RUNTIME ESTIMATION>"    => $jjob["runestimation"],
					"<RUNTIME THRESHOLD>"     => $runtime_threshold *100 . " %");

				/* replace all tags in the e-mail */
				$outstr .= grid_replace_tags($jobdetail_table_content, $cc, $job_summary, $jobdetail_tag_array);
				if (read_config_option("log_verbosity") > POLLER_VERBOSITY_DEBUG) {
					cacti_log($outstr);
				}
			}
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
				cacti_log("the whole table is ".$outstr);
			}
			$tag_array = api_plugin_hook_function("grid_memvio_tags", $jobdetail_tag_array);


			/* append the table to the report */
			$outstr = $tablehead . $outstr . $tablefoot;
			$htmlmessage = str_replace("<REPORTTABLE>", $outstr, $htmlmessage);

			if (strlen($email)) {
				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG)
					cacti_log("GRIDRUNTIME: send mail");
				grid_send_mail($email, read_config_option("thold_from_email"), read_config_option("thold_from_name"), $outsubject, $htmlmessage, $txtmessage);
			}
			/* only notify once */
			foreach ($jobs as $job) {
				db_execute_prepared("UPDATE grid_jobs_runtime SET notified=1
					WHERE clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?", array($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"]));
			}

		}
	}

}


function gridmemvio_notify_users() {
	global $config;

	$notify     = read_config_option("gridmemvio_notify");
	$taginc     = false;
	$output     = "";
	$outstr 	= "";
	$outstr2 	= "";
	$outmessage = "";
	$formatok   = false;

	$overshoot  = read_config_option("gridmemvio_overage");
	$undershoot = read_config_option("gridmemvio_us_allocation");
	$schedule   = read_config_option("gridmemvio_schedule");

	$subject    = read_config_option("gridmemvio_subject");
	$message    = read_config_option("gridmemvio_message");

	$over_filter     = read_config_option("gridmemvio_filter_name");
	$under_filter     = read_config_option("gridmemvio_us_filter_name");
	$now_time = date("Y-m-d H:i:s", time());
	$end_time   = date("Y-m-d H:i:s", time() - $schedule);

	if (read_config_option("grid_partitioning_enable") == "") {
		/* get a list of idle jobs w/o notification */
		$jobs = db_fetch_assoc_prepared("SELECT gjm.* FROM grid_jobs_memvio AS gjm
			INNER JOIN grid_jobs_finished AS gjf
			ON gjf.clusterid=gjm.clusterid
			AND gjf.jobid=gjm.jobid
			AND gjf.indexid=gjm.indexid
			AND gjf.submit_time=gjf.submit_time
			WHERE end_time>?", array($end_time));

		$job_summary = db_fetch_assoc_prepared("SELECT gjm.clusterid, gjf.user,
			COUNT(gjm.jobid) AS total_jobs,
			SUM(num_cpus) AS total_slots,
			SUM(CASE WHEN gjm.max_memory > mem_reserved THEN 1 ELSE NULL END) AS overage_jobs,
			SUM(CASE WHEN gjm.max_memory < mem_reserved THEN 1 ELSE NULL END) AS underage_jobs,
			SUM(CASE WHEN gjm.max_memory > mem_reserved THEN num_cpus ELSE NULL END) AS overage_slots,
			SUM(CASE WHEN gjm.max_memory < mem_reserved THEN num_cpus ELSE NULL END) AS underage_slots,
			AVG(CASE WHEN gjm.max_memory > mem_reserved THEN mem_reserved-gjm.max_memory ELSE NULL END) AS avg_overage,
			AVG(CASE WHEN gjm.max_memory < mem_reserved THEN gjm.max_memory-mem_reserved ELSE NULL END) AS avg_underage,
			SUM(CASE WHEN gjm.max_memory > mem_reserved THEN mem_reserved-gjm.max_memory ELSE NULL END) AS total_overage,
			SUM(CASE WHEN gjm.max_memory < mem_reserved THEN gjm.max_memory-mem_reserved ELSE NULL END) AS total_underage,
			AVG(CASE WHEN gjm.max_memory > mem_reserved THEN mem_reserved ELSE NULL END) AS avg_overreserve,
			AVG(CASE WHEN gjm.max_memory < mem_reserved THEN mem_reserved ELSE NULL END) AS avg_underreserve,
			GROUP_CONCAT(
				CASE WHEN gjm.indexid=0 THEN gjm.jobid ELSE CONCAT(gjm.jobid,'[',gjm.indexid,']') END
				ORDER BY gjm.jobid,gjm.indexid SEPARATOR ', '
			) as jobids
			FROM grid_jobs_memvio AS gjm
			INNER JOIN grid_jobs_finished AS gjf
			ON gjf.clusterid=gjm.clusterid
			AND gjf.jobid=gjm.jobid
			AND gjf.indexid=gjm.indexid
			AND gjf.submit_time=gjm.submit_time
			WHERE end_time>?
			GROUP BY gjm.clusterid, user
			ORDER BY gjm.clusterid DESC", array($end_time));

		$user_jobs = array_rekey(db_fetch_assoc("SELECT CONCAT_WS('', clusterid, '-', user, '') AS id, COUNT(jobid) AS total_jobs, SUM(num_cpus) AS total_slots FROM grid_jobs_finished
			WHERE end_time>'$end_time'
			GROUP BY CONCAT_WS('', clusterid, '-', user, '')"), "id", array("total_jobs", "total_slots"));
	} else {
		$tables = partition_get_partitions_for_query("grid_jobs_finished", $end_time, $now_time);

		$i = 0;
		$jobs_sql = "";
		$user_jobs_sql = "";
		$job_summary_sql = "";

		if (cacti_sizeof($tables)) {
			foreach ($tables as $table) {

				if ($i==0) {
					$jobs_sql .= " (SELECT gjm.* FROM grid_jobs_memvio AS gjm
						INNER JOIN $table AS gjf
						ON gjf.clusterid=gjm.clusterid
						AND gjf.jobid=gjm.jobid
						AND gjf.indexid=gjm.indexid
						AND gjf.submit_time=gjf.submit_time
						WHERE end_time>'$end_time') ";

					$job_summary_sql .= " (SELECT gjm.clusterid, gjm.jobid, gjm.indexid, gjf.user,
						num_cpus,
						gjm.max_memory,
						mem_reserved
						FROM grid_jobs_memvio AS gjm
						INNER JOIN $table AS gjf
						ON gjf.clusterid=gjm.clusterid
						AND gjf.jobid=gjm.jobid
						AND gjf.indexid=gjm.indexid
						AND gjf.submit_time=gjf.submit_time
						WHERE end_time>'$end_time') ";

					$user_jobs_sql .= " (SELECT clusterid, user, num_cpus  FROM $table WHERE end_time>'$end_time') ";

				}
				else {
					$jobs_sql .= " UNION (SELECT gjm.* FROM grid_jobs_memvio AS gjm
						INNER JOIN $table AS gjf
						ON gjf.clusterid=gjm.clusterid
						AND gjf.jobid=gjm.jobid
						AND gjf.indexid=gjm.indexid
						AND gjf.submit_time=gjf.submit_time
						WHERE end_time>'$end_time') ";

					$job_summary_sql .= " UNION (SELECT gjm.clusterid, gjm.jobid, gjm.indexid, gjf.user,
						num_cpus,
						gjm.max_memory,
						mem_reserved
						FROM grid_jobs_memvio AS gjm
						INNER JOIN $table AS gjf
						ON gjf.clusterid=gjm.clusterid
						AND gjf.jobid=gjm.jobid
						AND gjf.indexid=gjm.indexid
						AND gjf.submit_time=gjf.submit_time
						WHERE end_time>'$end_time') ";

					$user_jobs_sql .= " UNION ALL (SELECT clusterid, user, num_cpus  FROM $table WHERE end_time>'$end_time') ";
				}

				$i++;
			}

			if (strlen($jobs_sql)) {
				$jobs = db_fetch_assoc($jobs_sql);
			}
			else {
				$jobs = array();
			}

			if (strlen($user_jobs_sql)) {

				$user_jobs_sql = "SELECT CONCAT_WS('', clusterid, '-', user, '') AS id, COUNT(clusterid) AS total_jobs, SUM(num_cpus) AS total_slots FROM ($user_jobs_sql) AS jobs_record GROUP BY CONCAT_WS('', clusterid, '-', user, '')";
				$user_jobs = array_rekey(db_fetch_assoc($user_jobs_sql), "id", array("total_jobs", "total_slots"));
			}
			else {
				$user_jobs = array();
			}

			if (strlen($job_summary_sql)) {

				$job_summary_sql = "SELECT clusterid, user,
						COUNT(clusterid) AS total_jobs,
						SUM(num_cpus) AS total_slots,
						SUM(CASE WHEN max_memory > mem_reserved THEN 1 ELSE NULL END) AS overage_jobs,
						SUM(CASE WHEN max_memory < mem_reserved THEN 1 ELSE NULL END) AS underage_jobs,
						SUM(CASE WHEN max_memory > mem_reserved THEN num_cpus ELSE NULL END) AS overage_slots,
						SUM(CASE WHEN max_memory < mem_reserved THEN num_cpus ELSE NULL END) AS underage_slots,
						AVG(CASE WHEN max_memory > mem_reserved THEN mem_reserved-max_memory ELSE NULL END) AS avg_overage,
						AVG(CASE WHEN max_memory < mem_reserved THEN max_memory-mem_reserved ELSE NULL END) AS avg_underage,
						SUM(CASE WHEN max_memory > mem_reserved THEN mem_reserved-max_memory ELSE NULL END) AS total_overage,
						SUM(CASE WHEN max_memory < mem_reserved THEN max_memory-mem_reserved ELSE NULL END) AS total_underage,
						AVG(CASE WHEN max_memory > mem_reserved THEN mem_reserved ELSE NULL END) AS avg_overreserve,
						AVG(CASE WHEN max_memory < mem_reserved THEN mem_reserved ELSE NULL END) AS avg_underreserve,
						GROUP_CONCAT(
							CASE WHEN job_record.indexid=0 THEN job_record.jobid ELSE CONCAT(job_record.jobid,'[',job_record.indexid,']') END
							ORDER BY job_record.jobid,job_record.indexid SEPARATOR ', '
						) as jobids
						FROM ($job_summary_sql) AS job_record
						GROUP BY clusterid, user
						ORDER BY clusterid DESC";

				$job_summary = db_fetch_assoc($job_summary_sql);
			}
			else {
				$job_summary = array();
			}
		}
	}

	/* format the table */
	$table_header = array(
		"Rank", "User", "Total<br>Jobs", "Total<br>Slots",
		"Under Requests", "Over Requests", "Total<br>Overage", "Total<br>Underage",
		"Avg<br>Underage", "Avg<br>Overrage", "Avg<br>Under Reserve",
		"Avg<br>Over Reserve");
	$table_header  = api_plugin_hook_function("grid_memvio_header", $table_header);

	/* create the header */
	$tablehead = "<table id='report_table'>";
	$tablehead .= "<tr id='report_header'>";
	foreach ($table_header as $item) {
		$tablehead .= "<th>" . $item . "</th>";
	}
	$tablehead .= "</tr>";
	$tablefoot  = "</table>";

	/* go through rows, make table row and build a user array for emails if required */
	$last_cluster = 0;
	$rank = 1;
	if (cacti_sizeof($job_summary)) {

		/* load up the format file */
		$formatok  = grid_load_format_file("memvio.format", $output, $taginc);

		$cluster = array();
		foreach ($job_summary as $record) {
			if (!isset($cluster[$record["clusterid"]])) {
				$cluster[$record["clusterid"]] = db_fetch_row_prepared("SELECT * FROM grid_clusters WHERE clusterid=?", array($record["clusterid"]));
			}
			$cc = $cluster[$record["clusterid"]];

			if ($record["clusterid"] != $last_cluster) {
				/* output an e-mail */
				if ($last_cluster > 0) {
					/* replace tags in main message */

					$tag_array = array(
						"<CLUSTERNAME>"   => $cc["clustername"],
						"<OVERSHOOT>"     => ($overshoot * 100) . " %",
						"<UNDERSHOOT>"    => ($undershoot * 100) . " %",
						"<OVERFILTER>"    => $over_filter,
						"<UNDERFILTER>"    => $under_filter
					);
					$tag_array = api_plugin_hook_function("grid_memvio_tags", $tag_array);

					/* replace all tags in the e-mail */
					$job         = array();
					$outmessage .= grid_replace_tags($message, $last_cluster_details, $jobs, $tag_array);
					$outsubject  = grid_replace_tags($subject, $last_cluster_details, $jobs, $tag_array);

					/* merge the e-mail with the format */
					if ($formatok) {
						if ($taginc) {
							$htmlmessage = str_replace("<REPORT>", $outmessage, $output);
						} else {
							$htmlmessage = $output . $outmessage;
						}
					}

					$txtmessage = str_replace('<br>',  "\n", $outmessage);
					$txtmessage = str_replace('<BR>',  "\n", $txtmessage);
					$txtmessage = str_replace('</BR>', "\n", $txtmessage);
					$txtmessage = strip_tags($txtmessage);

					/* format the admin emails */
					$job   = array();
					$email = grid_format_email_addresses($last_cluster_details, $job, $notify);

					/* format the user emails */
					if ($notify < 3 && cacti_sizeof($users)) {
						foreach ($users as $user) {
							if (read_config_option("gridalarm_user_map") == 2) {
								$column_name = read_config_option("gridalarm_metadata_user_email_map");
								if (isset($column_name)) {
									$email_meta = db_fetch_cell_prepared("SELECT " .$column_name . " FROM grid_metadata WHERE object_id=? AND object_type='user' AND cluster_id=?" , array($user, $cc['clusterid']));
									if (!empty($email_meta)) {
										$email .= $email_meta;
									}
								}
							} elseif ($cc['email_domain'] != "") {
								$email .= (strlen($email) ? ", ":"") . $user . "@" . $cc["email_domain"];
							} else {
								$email .= (strlen($email) ? ", ":"") . $user;
							}
						}
					}

					/* append the table to the report */
					$outstr = $tablehead . $outstr . $tablefoot;
					$outstr .= "<br><table id='report_table'>". $outstr2 . $tablefoot;
					$htmlmessage = str_replace("<REPORTTABLE>", $outstr, $htmlmessage);

					if (strlen($email)) {
						grid_send_mail($email, read_config_option("thold_from_email"), read_config_option("thold_from_name"), $outsubject, $htmlmessage, $txtmessage);
					}

					/* initialize variables */
					$last_cluster = $record["clusterid"];
					$last_cluster_details = $cc;
					$outstr = "";
					$outstr2 = "";
					$outmessage = "";
					$rank         = 1;
				} else {
					$users        = array();
					$last_cluster = $record["clusterid"];
					$last_cluster_details = $cc;
				}
			}


			$table_content = api_plugin_hook_function("grid_memvio_content",
				"<tr id='report_row'><td><RANK></td><td><USER></td><td><TOTALJOBS></td><td><TOTALSLOTS></td>" .
				"<td><UNDERAGEJOBS></td><td><OVERAGEJOBS></td><td><TOTALOVERAGE></td><td><TOTALUNDERAGE></td>" .
				"<td><AVGUNDERAGE></td><td><AVGOVERAGE></td><td><AVGUNDERRES></td><td><AVGOVERRES></td></tr>");
			$table_content2="<tr id='report_header'><th>Rank $rank, finished violation jobs for ". $record['user']. "</th></tr><tr id='report_row'><td><RANK_JOBS></td></tr>";

			$tag_array = array(
				"<RANK>"          => $rank,
				"<CLUSTERNAME>"   => $cc["clustername"],
				"<OVERSHOOT>"     => ($overshoot * 100) . " %",
				"<UNDERSHOOT>"    => ($undershoot * 100) . " %",
				"<OVERFILTER>"    => $over_filter,
				"<UNDERFILTER>"    => $under_filter,
				"<USER>"          => $record["user"],
				"<TOTALJOBS>"     => $user_jobs[$record["clusterid"] . "-" . $record["user"]]["total_jobs"],
				"<TOTALSLOTS>"    => $user_jobs[$record["clusterid"] . "-" . $record["user"]]["total_slots"],
				"<OVERAGEJOBS>"   => $record["overage_jobs"],
				"<UNDERAGEJOBS>"  => $record["underage_jobs"],
				"<OVERAGESLOTS>"  => $record["overage_slots"],
				"<UNDERAGESLOTS>" => $record["underage_slots"],
				"<TOTALOVERAGE>"    => display_job_memory(abs($record["total_overage"]), 3),
				"<TOTALUNDERAGE>"   => display_job_memory(abs($record["total_underage"]),3),
				"<AVGOVERAGE>"    => display_job_memory(abs($record["avg_overage"]), 3),
				"<AVGUNDERAGE>"   => display_job_memory(abs($record["avg_underage"]),3),
				"<AVGOVERRES>"    => display_job_memory($record["avg_overreserve"], 3),
				"<AVGUNDERRES>"   => display_job_memory($record["avg_underreserve"], 3)
			);
			$tag_array = api_plugin_hook_function("grid_memvio_tags", $tag_array);

			$outstr2 .= str_replace('<RANK_JOBS>', $record['jobids'], $table_content2);
			/* replace all tags in the e-mail */
			$outstr .= grid_replace_tags($table_content, $cc, $job_summary, $tag_array);

			if ($notify < 3) {
				$users[$record["user"]] = $record["user"];
			}
			$rank++;
		}
		// send the last report
		if ($last_cluster > 0) {
			/* replace tags in main message */
			$tag_array = array(
				"<CLUSTERNAME>"   => $cc["clustername"],
				"<OVERSHOOT>"     => ($overshoot * 100) . " %",
				"<UNDERSHOOT>"    => ($undershoot * 100) . " %",
				"<OVERFILTER>"    => $over_filter,
				"<UNDERFILTER>"    => $under_filter
			);
			$tag_array = api_plugin_hook_function("grid_memvio_tags", $tag_array);

			/* replace all tags in the e-mail */
			$job         = array();
			$outmessage .= grid_replace_tags($message, $last_cluster_details, $jobs, $tag_array);
			$outsubject  = grid_replace_tags($subject, $last_cluster_details, $jobs, $tag_array);

			/* merge the e-mail with the format */
			if ($formatok) {
				if ($taginc) {
					$htmlmessage = str_replace("<REPORT>", $outmessage, $output);
				} else {
					$htmlmessage = $output . $outmessage;
				}
			}
			$txtmessage = str_replace('<br>',  "\n", $outmessage);
			$txtmessage = str_replace('<BR>',  "\n", $txtmessage);
			$txtmessage = str_replace('</BR>', "\n", $txtmessage);
			$txtmessage = strip_tags($txtmessage);
			/* format the admin emails */
			$job   = array();
			$email = grid_format_email_addresses($last_cluster_details, $job, $notify);
			/* format the user emails */
			if ($notify < 3 && cacti_sizeof($users) && $notify > 0) {
				foreach ($users as $user) {
					if (read_config_option("gridalarm_user_map") == 2) {
						$column_name = read_config_option("gridalarm_metadata_user_email_map");
						if (isset($column_name)) {
							$email_meta = db_fetch_cell_prepared("SELECT " .$column_name . " FROM grid_metadata WHERE object_id=? AND object_type='user' AND cluster_id=?", array($user, $cc['clusterid']));
							if (!empty($email_meta)) {
								$email .= $email_meta;
							}
						}
					} elseif ($cc["email_domain"] != "") {
						$email .= (strlen($email) ? ", ":"") . $user . "@" . $cc["email_domain"];
					} else {
						$email .= (strlen($email) ? ", ":"") . $user;
					}
				}
			}
			/* append the table to the report */
			$outstr = $tablehead . $outstr . $tablefoot;
			$outstr .= "<br><table id='report_table'>" . $outstr2 . $tablefoot;
			$htmlmessage = str_replace("<REPORTTABLE>", $outstr, $htmlmessage);
			if (strlen($email)) {
				grid_send_mail($email, read_config_option("thold_from_email"), read_config_option("thold_from_name"), $outsubject, $htmlmessage, $txtmessage);
			}
			/* initialize variables */
			$users        = array();
			$last_cluster = $record["clusterid"];
			$outstr       = "";
			$outstr2       = "";
			$rank         = 1;
		}

	}
	foreach ($jobs as $j) {
		db_execute_prepared("UPDATE grid_jobs_memvio SET notified=1
		WHERE clusterid=?
		AND jobid=?
		AND indexid=?
		AND submit_time=?", array($j["clusterid"], $j["jobid"], $j["indexid"], $j["submit_time"]));
	}
}

function grid_format_email_addresses($cc, $job, $notify) {
	$email     = "";
	$addresses = array();

	/* notify 1 -> User, 2 -> User and Admins, 3 -> Admins only */

	if ($notify == 2 || $notify == 3) {
		if (strlen($cc["email_admin"])) {
			$addresses = explode(",", $cc["email_admin"]);
		}
	}

	if (isset($job["jobid"]) && isset($job["indexid"]) && isset($job["clusterid"]) && isset($job["submit_time"])) {
		$jjob = db_fetch_row_prepared("SELECT *
			FROM grid_jobs
			WHERE jobid=?
			AND indexid=?
			AND clusterid=?
			AND submit_time=?", array($job["jobid"], $job["indexid"], $job["clusterid"], $job["submit_time"]));
	} elseif (cacti_sizeof($job)) {
		$jjob["mailUser"] = "";
		$jjob["user"]     = $job["user"];
	}

	if (isset($jjob)) {
		if ($notify == 1 || $notify == 2) {
			//append user email from metadata plugin only when User Notification Method is 'mail to meta address'
			if (read_config_option("gridalarm_user_map") == 2) {
				$column_name = read_config_option("gridalarm_metadata_user_email_map");
				if (isset($column_name)) {
					$email_meta = db_fetch_cell_prepared("SELECT " .$column_name . " FROM grid_metadata WHERE object_id=? AND object_type='user' AND cluster_id=?" , array($job["user"], $job['clusterid']));
					if (!empty($email_meta)) {
						$addresses[] = $email_meta;
					}
				}
			} elseif (isset($jjob["mailUser"]) && strlen($jjob["mailUser"])) {
				if (substr_count($jjob["mailUser"], "@")) {
					$addresses[] = $jjob["mailUser"];
				} elseif (strlen($cc["email_domain"])) {
					$addresses[] = $jjob["mailUser"] . "@" . $cc["email_domain"];
				} else {
					$addresses[] = $jjob['mailUser'];
				}
			} elseif (strlen($cc["email_domain"])) {
				$addresses[] = $jjob["user"] . "@" . $cc["email_domain"];
			} else {
				$addresses[] = $jjob["user"];
			}
		} else {
			/* no e-mail to user */
		}
	}

	/* format the e-mail addresses */
	if (cacti_sizeof($addresses)) $email = implode(", ", $addresses);

	return $email;
}

function grid_replace_tags($message, $cluster, $job, $data_array) {
	/* format the e-mail subject and message */

	/* cluster replacements */
	if (isset($cluster["clustername"])) $message = str_replace("<CLUSTERNAME>", $cluster["clustername"], $message);

	/* job replacements */
	if (isset($job["jobid"]))       $message = str_replace("<JOBID>", $job["jobid"], $message);
	if (isset($job["indexid"]))     $message = str_replace("<INDEXID>", $job["indexid"], $message);
	if (isset($job["submit_time"])) $message = str_replace("<SUBMITTIME>", $job["submit_time"], $message);
	if (isset($job["command"]))     $message = str_replace("<COMMAND>", $job["command"], $message);
	if (isset($job["user"]))        $message = str_replace("<USER>", $job["user"], $message);
	if (isset($job["queue"]))       $message = str_replace("<QUEUE>", $job["queue"], $message);
	if (isset($job["exec_host"]))   $message = str_replace("<EXECHOST>", $job["exec_host"], $message);

	/* job memory */
	if (isset($job["max_memory"]))      $message = str_replace("<MAXMEM>", $job["max_memory"], $message);
	if (isset($job["mem_reserved"]))    $message = str_replace("<MEMRESERVE>", $job["mem_reserved"], $message);
	if (isset($job["mem_requested"]))   $message = str_replace("<MEMREQUEST>", $job["mem_requested"], $message);

	/* custom replacements */
	if (cacti_sizeof($data_array)) {
	foreach ($data_array as $tag => $value) {
		$message = str_replace($tag, $value, $message);
	}
	}
	return $message;
}

/* This function is stolen from SETTINGS
 * and modified in a way, that rrdtool graph is kept outside this send_mail function
 * Using this function with current MAIL servers using extended authentication throws errors.
 */
function grid_send_mail($to, $from, $fromname, $subject, $message, $txtmessage) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	if ($from == '') {
		$from = rtm_get_defalt_mail_addr();
	}
	if ($fromname == '') {
		$fromname = rtm_get_defalt_mail_alias();
	}

	cacti_log("Sending Email to: '" . $to . "' - '" . $subject . "'", true, "GRID");

	$message = str_replace('<SUBJECT>', $subject, $message);
	$message = str_replace('<TO>', $to, $message);
	$message = str_replace('<FROM>', $from, $message);

	/* send the e-mail */
	$error = mailer(array($from, $fromname), $to, '', '', '', $subject, $message, $txtmessage);
	if (strlen($error)) {
		cacti_log('ERROR: Sending Email Failed.  Error was ' . $error, true, 'grid');
		return $error;
	}
	return '';
}

function grid_load_format_file($format_file, &$output, &$report_tag_included) {
	global $config;

	$contents = file($config["base_path"] . "/plugins/grid/formats/" . $format_file);
	$output   = "";
	$report_tag_included = false;

	if (cacti_sizeof($contents)) {
		foreach ($contents as $line) {
			$line = trim($line);
			if (substr_count($line, "<REPORT>")) {
				$report_tag_included = true;
			}
			if (substr($line, 0, 2) != "##") {
				$output .= $line . "\n";
			}
		}
	} else {
		return false;
	}

	return true;
}

function gridmemvio_check_for_memvio_jobs($last_maint_start = 0) {
	if (read_config_option("gridmemvio_schedule") == 0) return;
	if ($last_maint_start == 0) $last_maint_start = time() - 24*3600;


	/* record the start time */
	$start_time           = microtime(true);

	$mem_limit = read_config_option("gridmemvio_min_memory");

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$sql_where = "WHERE stat IN ('RUNNING','PROV','DONE' ,'EXIT') AND res_requirements!='' " . ($mem_limit != -1 ? " AND max_memory>$mem_limit":"") . $clsql;

	/* prep for subsequent deletion */
	//db_execute("UPDATE grid_jobs_memvio SET present=0 WHERE present=1");

	$i = 0;
	$increment=20000;
	$exceptional_jobs=0;

	while (true) {
		/* get jobs that match the criteria */
		$jobs = db_fetch_assoc_prepared("select * from (SELECT clusterid, jobid, indexid, submit_time, max_memory, max_swap, res_requirements, run_time,
								mem_requested, mem_reserved FROM grid_jobs FORCE INDEX (end_time) $sql_where And end_time > FROM_UNIXTIME(? -1200) UNION
								SELECT clusterid, jobid, indexid, submit_time, max_memory, max_swap, res_requirements, run_time,
								mem_requested, mem_reserved FROM grid_jobs_finished FORCE INDEX (end_time) $sql_where And end_time > FROM_UNIXTIME(? -1200)) as query limit $i, $increment",
						array($last_maint_start, $last_maint_start));

		$rows_returned = cacti_sizeof($jobs);
		if ($rows_returned <=0 ) break;
		grid_debug("NOTE: GRIDMEMVIO Returned '" . $rows_returned . "' Jobs");

		$overage         = read_config_option("gridmemvio_overage");
		$underage        = read_config_option("gridmemvio_us_allocation");

		$sql_prefix = "INSERT INTO grid_jobs_memvio (clusterid, jobid, indexid, submit_time, rusage_memory, rusage_swap, max_memory, max_swap, run_time, type, present) VALUES ";
		$sql_suffix = " ON DUPLICATE KEY UPDATE max_memory=VALUES(max_memory), max_swap=VALUES(max_swap), run_time=VALUES(run_time), type=VALUES(type), present=VALUES(present)";
		$sql        = "";
		$sql_cnt    = 0;

		if ($rows_returned) {
		foreach ($jobs as $job) {
			$capture_os = false;
			$capture_us = false;

			/* get memory */
			$mem_os_reserve = $job["mem_reserved"]*(1+$overage);
			$mem_us_reserve = $job["mem_reserved"]*(1-$underage);

			/* get memory */
			if ($mem_os_reserve > 0 && $mem_os_reserve < $job["max_memory"]) {
				if ($job["run_time"] > read_config_option("gridmemvio_window")) $capture_os = true;
			}

			if ($mem_us_reserve > 0 && $mem_us_reserve > $job["max_memory"]) {
				if ($job["run_time"] > read_config_option("gridmemvio_window")) $capture_us = true;
			}

			if ($capture_os || $capture_us) {
				if ($job["max_memory"] == null) $job["max_memory"] = 0;
				if ($job["max_swap"] == null) $job["max_swap"] = 0;
				$sql .= ($sql_cnt == 0 ? "(":",(") .
						$job["clusterid"]    . "," .
						$job["jobid"]        . "," .
						$job["indexid"]      . ",'" .
						$job["submit_time"]  . "'," .
						$job["mem_reserved"] . "," .
						"0," .
						$job["max_memory"]   . "," .
						$job["max_swap"]     . "," .
						$job["run_time"]     . "," .
						($capture_os ? "1":"2") . ",1)";

				$exceptional_jobs++;
				$sql_cnt++;

				if ($sql_cnt == 1000) {
					db_execute($sql_prefix . $sql . $sql_suffix);
					$sql     = "";
					$sql_cnt = 0;
				}
			}
		}
		}

		if ($sql_cnt) {
			db_execute($sql_prefix . $sql . $sql_suffix);
		}

		if ($rows_returned < $increment) break;
		$i += $increment;
	}

	/* record the end time */
	$end_time             = microtime(true);

	cacti_log("GRIDMEMVIO STATS: Time:" . round($end_time-$start_time,2) . " ExceptionalJobs:" . $exceptional_jobs, true, "SYSTEM");
}

function grid_memvio_get_memory_from_resreq($job, &$mem_request, &$mem_reserve, $type = "mem") {
	/* get requested and reserved memory */
	$res_req = parse_res_req_into_array($job['res_requirements']);
	if (isset($res_req["select"])) {
		$memory_requested = parse_res_req_select_string($res_req["select"], $type);
		if (cacti_sizeof($memory_requested)) {
			if ($memory_requested["value"] != "N/A") {
				$mem_request  = $memory_requested["value"] * 1024;
			}
		}
	}

	if (isset($res_req["rusage"])) {
		$memory_reserved = parse_res_req_rusage_string($res_req["rusage"], $type);
		if ($memory_reserved["value"] != "") {
			$mem_reserve = $memory_reserved["value"] * 1024;
		}
	}
}

function parse_res_req_rusage_string($res_req, $res_val) {
	$start    = strpos($res_req, $res_val);
	$duration = "";
	$decay    = "";
	$value    = "";

	$resources = explode(",", $res_req);

	if (cacti_sizeof($resources)) {
	foreach ($resources as $resource) {
		if (substr_count($resource, $res_val)) {
			$elements = explode(":", $resource);

			if (cacti_sizeof($elements)) {
			foreach ($elements as $element) {
				$item = explode("=", $element);

				if ($item[0] == $res_val) {
					$value    = $item[1];
				}

				if ($item[0] == "duration") {
					$duration = $item[1];
				}

				if ($item[0] == "decay") {
					$decay    = $item[1];
				}
			}
			}

			return array("value" => $value, "duration" => $duration, "decay" => $decay);
		}
	}
	}
}

function parse_res_req_select_string($res_req, $res_val) {
	if (($start = strpos($res_req, $res_val)) !== false) {
		/* peal off the value */
		$val  = trim(substr($res_req, $start+strlen($res_val)));

		if (strlen($val)) {
			$operator = substr($val, 0, 1);

			switch($operator) {
			case ")":
				$operator = "defined";
				$val      = "N/A";

				break;
			case "<":
				$next_part = substr($val,1,1);

				if ($next_part == "=") {
					$operator = "<=";
					$val = trim(substr($val,2));
				} else {
					$val = trim(substr($val,1));
				}

				break;
			case ">":
				$next_part = substr($val,1,1);

				if ($next_part == "=") {
					$operator = ">=";
					$val = trim(substr($val,2));
				} else {
					$val = trim(substr($val,1));
				}

				break;
			case "=":
				$val = trim(substr($val,1));

				break;
			default:
				$operator = "defined";
				$val      = "N/A";
			}

			/* check for an boolean operand */
			$and     = strpos($val, "&&");
			$or      = strpos($val, "||");
			$operand = "";
			$pos     = 0;

			if (($and > 0) && ($or > 0)) {
				if ($and > $or) {
					$operand = "or";
					$pos     = $or;
				} else {
					$operand = "and";
					$pos     = $and;
				}
			} elseif ($and > 0) {
				$operand = "and";
				$pos     = $and;
			} elseif ($or > 0) {
				$operand = "or";
				$pos     = $or;
			}

			if (strlen($operand)) {
				$val = trim(substr($val, 0, $pos));
			}
		} else {
			$operator= "defined";
			$val     = "N/A";
		}

		return array("value" => $val, "operator" => $operator);
	} else {
		return array();
	}
}

function parse_res_req_into_array($res_req) {
	$res_req = str_replace(" ", "", $res_req);

	if (strlen($res_req)) {
		/* convert to lower case first */
		$nres_req  = strtolower($res_req);

		/* initialize some variables */
		$res_array = array();
		$min_val   = 99999;

		if (substr_count($nres_req, "order")) {
			$start  = strpos($nres_req, "order");

			if ($start < $min_val) {
				$min_val = $start;
			}

			$res_array["order"] = parse_res_req_item($res_req, $start, "order");
		}

		if (substr_count($nres_req, "rusage")) {
			$start = strpos($nres_req, "rusage");

			if ($start < $min_val) {
				$min_val = $start;
			}

			$res_array["rusage"] = parse_res_req_item($res_req, $start, "rusage");
		}

		if (substr_count($nres_req, "span")) {
			$start = strpos($nres_req, "span");

			if ($start < $min_val) {
				$min_val = $start;
			}

			$res_array["span"] = parse_res_req_item($res_req, $start, "span");
		}

		if (substr_count($nres_req, "same")) {
			$start = strpos($nres_req, "same");

			if ($start < $min_val) {
				$min_val = $start;
			}

			$res_array["same"] = parse_res_req_item($res_req, $start, "same");
		}

		/* determine start location for select and implicit select */
		if (substr_count($nres_req, "select")) {
			$start = strpos($nres_req, "select");

			$res_array["select"] = parse_res_req_item($res_req, $start, "select");
		} else {
			if ($min_val < 99999) {
				if ($min_val > 0) {
					$res_array["select"] = parse_res_req_item($res_req, $min_val, "iselect");
				}
			} else {
				$res_array["select"] = parse_res_req_item($res_req, strlen($res_req), "iselect");
			}
		}

		return $res_array;
	} else {
		return array();
	}
}

function parse_res_req_item($res, $res_item_start, $res_type) {
	switch($res_type) {
	case "rusage":
	case "order":
	case "same":
	case "select":
	case "span":
		$res       = substr($res, $res_item_start);
		$res_start = strpos($res, "[");
		$res       = substr($res, $res_start + 1);
		$res_end   = strpos($res, "]");
		$res       = trim(substr($res, 0, $res_end));

		return $res;
	case "iselect":
		$res       = trim(substr($res, 0, $res_item_start));

		return $res;
	default:
		return "";
	}
}

function update_cluster_jobtraffic($start_time, $start) {
	global $max_run_duration, $config, $debug;

	grid_debug("Updating Host Throughput Stats - Begin");

	db_execute("DELETE jt FROM grid_hosts_jobtraffic AS jt LEFT JOIN grid_hosts AS gh ON jt.clusterid=gh.clusterid AND jt.host=gh.host WHERE gh.clusterid IS NULL");

	/* get the start time */
	$grid_poller_start = read_config_option("grid_poller_start");

	/* save the scan date information */
	$scan_date = $grid_poller_start;
	if (empty($scan_date)) {
		$scan_date = date("Y-m-d H:i:00");
		cacti_log("WARNING: Default Scan Date Used", false, "GRID");
	}

	/* determine the poller frequency */
	$poller_frequency = read_config_option("poller_interval");
	if (empty($poller_frequency)) {
		$poller_frequency = 300;
	}

	/* determine the prior polling time for interval stats */
	$previous_date = read_config_option("grid_poller_prev_start");
	if (empty($previous_date)) {
		$previous_date = date("Y-m-d H:i:00", strtotime($scan_date) - $poller_frequency);
		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log("WARNING: Default Previous Scan Date Used", false, "GRID");
		}
	}

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	db_execute_prepared("UPDATE (SELECT
			clusterid,
			exec_host AS host,
			SUM(CASE WHEN stat='DONE' THEN 1 ELSE 0 END) AS doneJobs,
			SUM(CASE WHEN stat='EXIT' THEN 1 ELSE 0 END) AS exitJobs
			FROM grid_jobs
			WHERE start_time>0
			AND grid_jobs.end_time>=?
			AND grid_jobs.end_time<=?
			GROUP BY clusterid, exec_host) AS interval_data
		INNER JOIN grid_hosts_jobtraffic
		ON (grid_hosts_jobtraffic.clusterid=interval_data.clusterid) AND (grid_hosts_jobtraffic.host=interval_data.host)
		SET
			grid_hosts_jobtraffic.jobs_done=grid_hosts_jobtraffic.jobs_done+doneJobs,
			grid_hosts_jobtraffic.jobs_exited=grid_hosts_jobtraffic.jobs_exited+exitJobs;", array($previous_date, $scan_date));


	grid_debug("Updating Host Throughput Stats - Complete");
}

function find_pidalarm_jobs() {
	if (read_config_option("grid_pidalarm_enabled") == "on") {
		grid_debug("Checking for Jobs in a PID Alarm Condition");

		$pidalarm_thold = read_config_option("grid_pidalarm_threshold");

		$new_pidalarm_jobs = db_fetch_assoc_prepared("SELECT
			jobname, clusterid, jobid, indexid, submit_time, user, queue, numPIDS, numPGIDS, numThreads
			FROM grid_jobs
			WHERE (numPIDS>=?
			OR numPGIDS>=?
			OR numThreads>=?)
			AND pid_alarm_logged='0'
			AND (stat IN('RUNNING', 'PROV', 'SSUSP', 'PSUSP', 'USUSP'))", array($pidalarm_thold, $pidalarm_thold, $pidalarm_thold));

		if (cacti_sizeof($new_pidalarm_jobs)) {
			foreach ($new_pidalarm_jobs AS $job) {
				$numpids = '';
				if ($job['numPIDS'] == max($job['numPIDS'], $job['numPGIDS'], $job['numThreads'])) {
					$numpids = 'numPIDS:' . $job['numPIDS'];
				} else if ($job['numPIDS'] == max($job['numPIDS'], $job['numPGIDS'], $job['numThreads'])) {
					$numpids = 'numPGIDS:' . $job['numPGIDS'];
				} else {
					$numpids = 'numThreads:' . $job['numThreads'];
				}
				cacti_log("WARNING: PID Alarm Detected on JobID:'" . $job["jobid"] . ($job["indexid"] > 0 ? "[" . $job["indexid"] . "]'" : "'") . ", ClusterID:'" . $job["clusterid"] . "', ClusterName:'" . grid_get_clustername($job["clusterid"]) . "', $numpids, JobName:'" . $job["jobname"] . "'", false, "GRID");

				db_execute_prepared('UPDATE grid_jobs
					SET pid_alarm_logged=1
					WHERE clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?', array($job["clusterid"], $job["jobid"], $job["indexid"], $job["submit_time"]));
			}
		}
	}
}

/**
 * Get SQL IN expression for enabled cluster Ids. Field name is 'clusterid' by default.
 *
 * @param string $field Field name
 * @return SQL IN expression
 */
function get_cluster_list($field = "clusterid") {
	$clusters = array_rekey(db_fetch_assoc("SELECT clusterid FROM grid_clusters WHERE disabled=''"), "clusterid", "clusterid");

	if (cacti_sizeof($clusters)) {
		return "$field IN (" . implode(",",$clusters) . ")";
	} else {
		return "";
	}
}

function get_project_aggregation_string($level_override = 0, $config_option_name = "grid_project_group_aggregation", $from_jobtbl = false) {
	/* obtain the projects collection methodology */
	if ($config_option_name=="grid_project_group_aggregation") {
		$config_option_name="grid_project_group_aggregation";
		$config_delim="grid_job_stats_project_delimiter";
		$config_count="grid_job_stats_project_level_number";
	} else {
		$config_option_name="grid_blstat_project_group_aggregation";
		$config_delim="grid_blstat_job_stats_project_delimiter";
		$config_count="grid_blstat_job_stats_project_level_number";
	}

	$method = read_config_option($config_option_name);

	if ($method == "on") {
		if ($level_override == 0) {
			$delim = read_config_option($config_delim);
			$delim_count = read_config_option($config_count);
		} else {
			$delim = read_config_option($config_delim);
			$delim_count = $level_override;
		}
		// fix #132036
		if ($delim_count == 0) {
			$projMagic = "projectName";
		} else {
			$projMagic = "SUBSTRING_INDEX(projectName, '$delim', $delim_count)";
		}
	} else { // Aggregation turned off
		$projMagic = "projectName";
	}
	if ($from_jobtbl)
		return api_plugin_hook_function("job_project_name_expr", $projMagic);
	else
		return $projMagic;
}

function get_ugroup_aggregation_string($level_override = 0) {
	/* obtain the projects collection methodology */
	$method = read_config_option("grid_ugroup_group_aggregation");

	if ($method == "on") {
		if ($level_override == 0) {
			$delim = read_config_option("grid_job_stats_ugroup_delimiter");
			$delim_count = read_config_option("grid_job_stats_ugroup_level_number");
		} else {
			$delim = read_config_option("grid_job_stats_ugroup_delimiter");
			$delim_count = $level_override;
		}
		// fix #132036
		if ($delim_count == 0) {
			$groupMagic = "userGroup";
		} else{
			$groupMagic = "SUBSTRING_INDEX(userGroup, '$delim', $delim_count)";
		}
	} else { // Aggregation turned off
		$groupMagic = "userGroup";
	}

	return $groupMagic;
}

function get_group_aggregation_string($level_override = 0) {
	/* obtain the projects collection methodology */
	$max_level = $level_override;
	if (!isset($level_override) || $level_override == 0) {
		$max_level = read_config_option("grid_job_stats_group_level_number");
	}

	/* prepare the projectName operator */
	if ($max_level <= 0) {
		$grpMagic = "groupName";
	} else {
		/*$delim_count++ because the first delim of JobGroup is slash*/
		$delim = "/";
		$delim_count = $max_level + 1;

		$grpMagic = "SUBSTRING_INDEX(groupName, '$delim', $delim_count)";
	}
	return $grpMagic;
}

function update_user_group_stats() {
	grid_debug("Updating User Group Stats - Begin");

	$method = read_config_option("grid_ugroup_group_aggregation");
	if ($method == "on") {
		$delim = read_config_option("grid_job_stats_ugroup_delimiter");
		if (($delim=='') || !isset($delim)) {
			cacti_log("WARNING: Empty User Group Delimiter! User Group aggregation therefore can not Continue", true, "GRID");
			return -1;
		}
	}

	/* setup for deletion */
	db_execute("UPDATE grid_user_group_stats SET present=0 WHERE present=1");

	/* remove blank groups */
	db_execute("DELETE FROM grid_user_group_stats WHERE userGroup=''");

	$groupMagic = get_ugroup_aggregation_string();

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	/* format the SQL insert syntax */
	if (read_config_option("grid_usergroup_method") == "jobmap") {
		$format = array("clusterid", "userGroup", "numRUN", "numPEND", "numJOBS",
			"efficiency", "avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu",
			"last_updated", "present");

		$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
			numJOBS=VALUES(numJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
			max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
			total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

		$records = db_fetch_assoc("SELECT clusterid,
			(CASE WHEN userGroup='' THEN 'default' ELSE userGroup END) AS userGroup,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
			SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
			SUM(num_cpus) as numJOBS,
			(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as avg_mem,
			MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as max_mem,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) as avg_swap,
			MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) as max_swap,
			SUM(cpu_used) as total_cpu,
			NOW() AS last_updated,
			'1' as present
			FROM grid_jobs
			WHERE stat IN ('RUNNING','PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
			$clsql
			GROUP BY clusterid, userGroup");
		grid_pump_records($records, "grid_user_group_stats", $format, false, $duplicate);
	} else {
		$groupMagic = str_replace("userGroup", "grid_user_group_members.groupname", $groupMagic);

		/* format the SQL insert syntax */
		$format = array("clusterid", "userGroup", "numRUN", "numPEND", "numJOBS", "efficiency",
			"avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

		$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND), numJOBS=VALUES(numJOBS),
			efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem), max_mem=VALUES(max_mem),
			avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap), total_cpu=VALUES(total_cpu),
			last_updated=VALUES(last_updated), present=1";

		$records = db_fetch_assoc("SELECT teddy.clusterid,
			$groupMagic as userGroup,
			sum(numRUN) AS numRUN,
			sum(numPEND) AS numPEND,
			sum(numJOBS) AS numJOBS,
			avg(avg_efficiency) AS efficiency,
			avg(avg_mem) AS avg_mem,
			max(max_mem) AS max_mem,
			avg(avg_max_swap) AS avg_swap,
			max(max_max_swap) AS max_swap,
			sum(total_cpu) AS total_cpu,
			NOW() AS last_updated,
			'1' as present
			FROM (SELECT
				clusterid,
				user,
				SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
				SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
				SUM(num_cpus) as numJOBS,
				(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as avg_efficiency,
				AVG(max_memory) as avg_mem,
				MAX(max_memory) as max_mem,
				AVG(max_swap) as avg_max_swap,
				MAX(max_swap) as max_max_swap,
				SUM(cpu_used) as total_cpu
				FROM grid_jobs
				WHERE stat IN('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
				$clsql
				GROUP BY clusterid, user) as teddy
			INNER JOIN grid_user_group_members
			ON (grid_user_group_members.clusterid=teddy.clusterid
			AND (grid_user_group_members.username=teddy.user))
			WHERE grid_user_group_members.present = 1
			GROUP BY grid_user_group_members.clusterid, grid_user_group_members.groupname");

		grid_pump_records($records, "grid_user_group_stats", $format, false, $duplicate);
	}

	/* remove stale records */
	db_execute("UPDATE grid_user_group_stats
		SET numRUN=0,
			numPEND=0,
			numJOBS=0,
			efficiency=0,
			avg_mem=0,
			max_mem=0,
			avg_swap=0,
			max_swap=0,
			total_cpu=0
		WHERE present=0");

	/* remove non-compliant user group stats when aggregation is enabled */
	if (read_config_option("grid_ugroup_group_aggregation") == 'on') {
		$delim = read_config_option("grid_job_stats_ugroup_delimiter");
		$count = read_config_option("grid_job_stats_ugroup_level_number");
		//fix #132036
		if ($count > 0) {
			db_execute_prepared("DELETE FROM grid_user_group_stats WHERE LENGTH(SUBSTRING_INDEX(userGroup, ?, ?)) < LENGTH(userGroup)", array($delim, $count));
		}
	}

	/* log end event */
	grid_debug("Updating User Group Stats - Complete");
}

function update_license_projects() {
	grid_debug("Updating License Project Stats - Begin");

	/* setup for deletion */
	db_execute("UPDATE grid_license_projects SET present=0 WHERE present=1");

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	/* format the SQL insert syntax */
	$format = array("clusterid", "licenseProject", "numRUN", "numPEND", "numJOBS", "efficiency",
		"avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
		numJOBS=VALUES(numJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

	$records = db_fetch_assoc("SELECT clusterid,
		licenseProject,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
		SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
		SUM(num_cpus) as numJOBS,
		(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
		AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as avg_mem,
		MAX(CASE WHEN stat IN ('RUNNING', 'PROV') THEN max_memory ELSE 0 END) as max_mem,
		AVG(CASE WHEN stat IN ('RUNNING', 'PROV') THEN max_swap ELSE 0 END) as avg_swap,
		MAX(CASE WHEN stat IN ('RUNNING', 'PROV') THEN max_swap ELSE 0 END) as max_swap,
		SUM(cpu_used) as total_cpu,
		NOW() AS last_updated,
		'1' as present
		FROM grid_jobs
		WHERE stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
		AND licenseProject != 'NULL'
		$clsql
		GROUP BY clusterid, licenseProject");
	grid_pump_records($records, "grid_license_projects", $format, false, $duplicate);

	/* reset old records */
	db_execute("UPDATE grid_license_projects
		SET numRUN=0,
			numPEND=0,
			numJOBS=0,
			efficiency=0,
			avg_mem=0,
			max_mem=0,
			avg_swap=0,
			max_swap=0,
			total_cpu=0
		WHERE present=0");

	/* log end event */
	grid_debug("Updating Licensed Project Stats - Complete");
}

function update_projects() {
	grid_debug("Updating Project Stats - Begin");

	$method = read_config_option("grid_project_group_aggregation");
	if ($method == "on") {
		$delim = read_config_option("grid_job_stats_project_delimiter");
		if (($delim=='') || !isset($delim)) {
			cacti_log("WARNING: Empty Project Delimiter! Project aggregation therefore can not Continue", true, "GRID");
			return -1;
		}
	}

	/* setup for deletion */
	db_execute("UPDATE grid_projects SET present=0 WHERE present=1");
	$projMagic = get_project_aggregation_string("0", "grid_project_group_aggregation", true);
	//$projMagic = get_project_aggregation_string();

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	/* format the SQL insert syntax */
	/*$
	format = array("clusterid", "projectName", "numRUN", "numPEND", "numJOBS", "efficiency",
		"avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
		numJOBS=VALUES(numJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

	$records = db_fetch_assoc("SELECT clusterid,
		$projMagic AS projectName,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE NULL END) as numRUN,
		SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE NULL END) as numPEND,
		SUM(num_cpus) as numJOBS,
		(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE NULL END) / SUM(CASE WHEN stat='RUNNING' AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE NULL END)) * 100 as efficiency,
		AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE NULL END) as avg_mem,
		MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE NULL END) as max_mem,
		AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE NULL END) as avg_swap,
		MAX(CASE WHEN stat IN ('RUNNING', 'PROV') THEN max_swap ELSE NULL END) as max_swap,
		SUM(cpu_used) as total_cpu,
		NOW() AS last_updated,
		'1' as present
		FROM grid_jobs
		WHERE stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
		$clsql
		GROUP BY clusterid, $projMagic");
	grid_pump_records($records, "grid_projects", $format, false, $duplicate);
	*/
	$format = array("clusterid", "projectName", "numRUN", "numPEND", "numJOBS", "runJOBS", "pendJOBS", "totalJOBS",
		"efficiency", "avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
		numJOBS=VALUES(numJOBS), runJOBS=VALUES(runJOBS), pendJOBS=VALUES(pendJOBS),
		totalJOBS=VALUES(totalJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

	$proj_sql = "SELECT clusterid,
			$projMagic AS projectName,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
			SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
			SUM(num_cpus) as numJOBS,
			COUNT(IF(stat IN ('RUNNING','PROV'), stat, 0)) as runJOBS,
			COUNT(IF(stat='PEND', stat, 0)) as pendJOBS,
			COUNT(stat) as totalJOBS,
			(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat='RUNNING' AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as avg_mem,
			MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as max_mem,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) as avg_swap,
			MAX(CASE WHEN stat IN ('RUNNING', 'PROV') THEN max_swap ELSE 0 END) as max_swap,
			SUM(cpu_used) as total_cpu,
			NOW() AS last_updated,
			'1' as present
			FROM grid_jobs
			WHERE stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
			$clsql
			GROUP BY clusterid, $projMagic";
	//print $proj_sql;
	$records = db_fetch_assoc("$proj_sql");
	grid_pump_records($records, "grid_projects", $format, false, $duplicate);
	/* reset old records */
	db_execute("UPDATE grid_projects
		SET numRUN=0,
			numPEND=0,
			numJOBS=0,
			runJOBS=0,
			pendJOBS=0,
			totalJOBS=0,
			efficiency=0,
			avg_mem=0,
			max_mem=0,
			avg_swap=0,
			max_swap=0,
			total_cpu=0
		WHERE present=0");

	/* remove non-compliant user group stats when aggregation is enabled */
	if (read_config_option("grid_project_group_aggregation") == "on") {
		$delim = read_config_option("grid_job_stats_project_delimiter");
		$count = read_config_option("grid_job_stats_project_level_number");
		if ($count > 0) {
			db_execute_prepared("DELETE FROM grid_projects WHERE LENGTH(SUBSTRING_INDEX(projectName, ?, ?)) < LENGTH(projectName)", array($delim, $count));
		}
	}

	/* log end event */
	grid_debug("Updating Project Stats - Complete");
}

function update_queues() {
	grid_debug("Updating Queue Stats - Begin");

	/* setup for deletion */
	db_execute("UPDATE grid_queues_stats SET present=0 WHERE present=1");

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$format = array("clusterid", "queue", "numRUN", "numPEND", "numJOBS", "efficiency",
		"avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "memReserved", "memUsed",
		"memRequested", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
		numJOBS=VALUES(numJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), memReserved=VALUES(memReserved), memUsed=VALUES(memUsed),
		memRequested=VALUES(memRequested), last_updated=VALUES(last_updated), present=VALUES(present)";

	/* format the SQL insert syntax */
	$records = db_fetch_assoc("SELECT clusterid,
		queue,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
		SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
		SUM(num_cpus) as numJOBS,
		(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat='RUNNING' AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
		AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) AS avg_mem,
		MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) AS max_mem,
		AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) AS avg_swap,
		MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) AS max_swap,
		SUM(cpu_used) as total_cpu,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_reserved ELSE 0 END) AS memReserved,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_used ELSE 0 END) AS memUsed,
		SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_requested ELSE 0 END) AS memRequested,
		NOW() AS last_updated,
		'1' as present
		FROM grid_jobs
		WHERE stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
		$clsql
		GROUP BY clusterid, queue");
	grid_pump_records($records, "grid_queues_stats", $format, false, $duplicate);
	/* reset old records */
	db_execute("UPDATE grid_queues_stats
		SET numRUN=0, numPEND=0, numJOBS=0, efficiency=0, avg_mem=0, max_mem=0, avg_swap=0,
			max_swap=0, total_cpu=0, memReserved=0, memUsed=0, memRequested=0 WHERE present=0");

	$format = array("clusterid", "queue", "memSlotUtil", "slotUtil", "cpuUtil", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE memSlotUtil=VALUES(memSlotUtil), slotUtil=VALUES(slotUtil),
		cpuUtil=VALUES(cpuUtil), efficiency=VALUES(efficiency),
		last_updated=VALUES(last_updated), present=VALUES(present)";

	/* limit the clusters */
	$clsql = get_cluster_list('ghi.clusterid');
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}
	/* format the SQL insert syntax */
	$records = db_fetch_assoc("SELECT clusterid, queue,
		SUM(memSlotUtil*totalSlots)/SUM(totalSlots) AS memSlotUtil,
		SUM(slotUtil*totalSlots)/SUM(totalSlots) AS slotUtil,
		SUM(cpuUtil*totalSlots)/SUM(totalSlots) AS cpuUtil,
		NOW() AS last_updated,
		'1' as present
		FROM (
			SELECT clusterid, queue, host, memSlotUtil, cpuUtil, slotUtil, totalSlots
			FROM (
				SELECT *, (totalSlots-(FLOOR(totalSlots*freeMem/maxMem)))/totalSlots*100 AS memSlotUtil,
				ROUND((numRun/totalSlots)*100,1) AS slotUtil
				FROM (
					SELECT gqh.clusterid, gqh.queue, ghr.host, numRun, ROUND(IF(ut>0, ut*100,0),1) AS cpuUtil,
					ROUND(totalValue,0) AS freeMem, ROUND(reservedValue,0) AS reservedMem,
					GREATEST(maxJobs, maxCpus) AS totalSlots, maxMem
					FROM grid_hosts_resources AS ghr
					INNER JOIN grid_hostinfo AS ghi
					ON ghi.host=ghr.host
					AND ghi.clusterid=ghr.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host=ghr.host
					AND gh.clusterid=ghr.clusterid
					AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
					INNER JOIN grid_load AS gl
					ON gl.host=ghr.host
					AND gl.clusterid=ghr.clusterid
					INNER JOIN grid_queues_hosts AS gqh
					ON ghr.host=gqh.host
					AND ghr.clusterid=gqh.clusterid
					WHERE resource_name='mem' $clsql
				) AS results
			) AS results2
		) AS results3
		GROUP BY clusterid, queue");
	grid_pump_records($records, "grid_queues_stats", $format, false, $duplicate);

	/* log end event */
	grid_debug("Updating Queue Stats - Complete");
}

function update_hostgroups_stats() {
	grid_debug("Updating Host Group Stats - Begin");
	$job_pend_hostgroup = read_config_option('grid_job_pend_hostgroup');
	if ($job_pend_hostgroup == 'disable') {
		grid_debug("Updating Host Group Stats - Disabled - Complete");
		return;
	}

	/* limit the clusters */
	$clsql = get_cluster_list('ghg.clusterid');
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}
	$clsql_j = str_replace("ghg.clusterid", "clusterid", $clsql);

	$format = array("clusterid", "groupName", "numPEND", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numPEND=VALUES(numPEND),
		last_updated=VALUES(last_updated), present=VALUES(present)";

	// numPEND for Host Group Statistics
	if ($job_pend_hostgroup == 'queuemap'){
		$records = db_fetch_assoc("SELECT rset.clusterid, rset.groupName, SUM(num_cpus) AS numPEND,
			NOW() AS last_updated, '1' as present
			FROM (
				SELECT DISTINCT ghg.groupName, gqh.queue, gqh.clusterid
				FROM grid_hostgroups AS ghg
				INNER JOIN grid_queues_hosts AS gqh
				ON gqh.host=ghg.host
				AND gqh.clusterid=ghg.clusterid
			) AS rset
			INNER JOIN (
				SELECT clusterid, queue, SUM(num_cpus) AS num_cpus
				FROM grid_jobs AS gj
				WHERE stat='PEND' $clsql_j
				GROUP BY clusterid, queue
			) AS rset2
			ON rset2.clusterid=rset.clusterid
			AND rset2.queue=rset.queue
			GROUP BY rset.clusterid, rset.groupName");
	} else {
		$records = db_fetch_assoc("SELECT ghg.clusterid, ghg.groupName, SUM(num_cpus) AS numPEND,
			NOW() AS last_updated, '1' as present
			FROM grid_jobs AS gj
			INNER JOIN grid_jobs_reqhosts AS gjrh
				ON gjrh.jobid = gj.jobid
				AND gjrh.clusterid = gj.clusterid
				AND gjrh.indexid = gj.indexid
				AND gjrh.submit_time = gj.submit_time
			INNER JOIN grid_hostgroups AS ghg
				ON ghg.clusterid = gjrh.clusterid
				AND (ghg.host = gjrh.host OR ghg.groupName = gjrh.host)
			WHERE gj.stat = 'PEND' $clsql
			GROUP BY ghg.clusterid, ghg.groupName");
	}
	db_execute("UPDATE grid_hostgroups_stats SET present=0 WHERE present=1");
	grid_pump_records($records, "grid_hostgroups_stats", $format, false, $duplicate);
	db_execute("UPDATE grid_hostgroups_stats SET numPEND=0 WHERE present=0");

	$format = array("clusterid", "groupName", "memSlotUtil", "slotUtil", "cpuUtil",
		"last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE memSlotUtil=VALUES(memSlotUtil), slotUtil=VALUES(slotUtil),
		cpuUtil=VALUES(cpuUtil), last_updated=VALUES(last_updated), present=VALUES(present)";

	$records = db_fetch_assoc("SELECT clusterid, groupName,
		SUM(memSlotUtil*totalSlots)/SUM(totalSlots) AS memSlotUtil,
		SUM(slotUtil*totalSlots)/SUM(totalSlots) AS slotUtil,
		SUM(cpuUtil*totalSlots)/SUM(totalSlots) AS cpuUtil,
		NOW() AS last_updated, '1' as present
		FROM (
			SELECT clusterid, groupName, host, memSlotUtil, cpuUtil, slotUtil, totalSlots
			FROM (
				SELECT *, (totalSlots-(FLOOR(totalSlots*freeMem/maxMem)))/totalSlots*100 AS memSlotUtil,
				ROUND((numRun/totalSlots)*100,1) AS slotUtil
				FROM (
					SELECT ghg.clusterid, ghg.groupName, ghr.host, numRun, ROUND(IF(ut>0, ut*100,0),1) AS cpuUtil,
					ROUND(totalValue,0) AS freeMem, ROUND(reservedValue,0) AS reservedMem,
					GREATEST(maxJobs, maxCpus) AS totalSlots, maxMem
					FROM grid_hosts_resources AS ghr
					INNER JOIN grid_hostinfo AS ghi
					ON ghi.host=ghr.host
					AND ghi.clusterid=ghr.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host=ghr.host
					AND gh.clusterid=ghr.clusterid
					AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
					INNER JOIN grid_load AS gl
					ON gl.host=ghr.host
					AND gl.clusterid=ghr.clusterid
					INNER JOIN grid_hostgroups AS ghg
					ON ghr.host=ghg.host
					AND ghr.clusterid=ghg.clusterid
					WHERE resource_name='mem' $clsql
				) AS results
			) AS results2
		) AS results3
		GROUP BY clusterid, groupName");
	grid_pump_records($records, "grid_hostgroups_stats", $format, false, $duplicate);

	$format = array("clusterid", "groupName", "efficiency", "avg_mem", "max_mem",
		"avg_swap", "max_swap", "total_cpu", "numRUN", "numJOBS", "memReserved",
		"memUsed", "memRequested", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numJOBS=VALUES(numJOBS),
		efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), memReserved=VALUES(memReserved), memUsed=VALUES(memUsed),
		memRequested=VALUES(memRequested), last_updated=VALUES(last_updated), present=VALUES(present)";

	/* format the SQL insert syntax */
	$records = db_fetch_assoc("SELECT grid_hostgroups.clusterid,
		grid_hostgroups.groupName,
		avg(avg_efficiency) AS efficiency,
		avg(avg_mem) AS avg_mem,
		max(max_mem) AS max_mem,
		avg(avg_max_swap) AS avg_swap,
		max(max_max_swap) AS max_swap,
		sum(total_cpu) AS total_cpu,
		SUM(sum_numRUN) AS numRUN,
		SUM(sum_numJOBS) AS numJOBS,
		SUM(mem_reserved) AS memReserved,
		SUM(mem_used) AS memUsed,
		SUM(mem_requested) AS memRequested,
		NOW() AS last_updated,
		'1' as present
		FROM (SELECT
			clusterid,
			exec_host,
			(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as avg_efficiency,
			AVG(max_memory) as avg_mem,
			MAX(max_memory) as max_mem,
			AVG(max_swap) as avg_max_swap,
			MAX(max_swap) as max_max_swap,
			SUM(cpu_used) as total_cpu,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as sum_numRUN,
			SUM(num_cpus) as sum_numJOBS,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_reserved ELSE 0 END) as mem_reserved,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_used ELSE 0 END) as mem_used,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN mem_requested ELSE 0 END) as mem_requested
			FROM grid_jobs
			WHERE stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP')
			$clsql_j
			GROUP BY clusterid, exec_host) as teddy
		INNER JOIN grid_hostgroups
		ON (grid_hostgroups.clusterid=teddy.clusterid
		AND grid_hostgroups.host=teddy.exec_host)
		GROUP BY grid_hostgroups.clusterid, grid_hostgroups.groupName");
	db_execute("UPDATE grid_hostgroups_stats SET present=0 WHERE present=1");
	grid_pump_records($records, "grid_hostgroups_stats", $format, false, $duplicate);

	/* reset old records */
	db_execute("UPDATE grid_hostgroups_stats
		SET efficiency=0, avg_mem=0, max_mem=0, avg_swap=0, max_swap=0, total_cpu=0,
			numRUN=0, numJOBS=0, memReserved=0, memUsed=0, memRequested=0
		WHERE present=0");

	/* log end event */
	grid_debug("Updating Host Group Stats - Complete");
}

function update_guarantee_respool() {
	grid_debug("Updating Guarantee Resource Pool - Begin");

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$format = array("clusterid", "name", "consumer", "runJobs", "runSlots");

	$duplicate = "ON DUPLICATE KEY UPDATE runJobs=VALUES(runJobs), runSlots=VALUES(runSlots)";

	$records = db_fetch_assoc("SELECT gpd.clusterid, gpd.name, gpd.consumer,
		SUM(gj.runJobs) as runJobs, SUM(gj.runSlots) AS runSlots
		FROM grid_guarantee_pool_distribution AS gpd
		INNER JOIN grid_guarantee_pool_hosts AS gph
		ON gpd.name=gph.name
		AND gpd.clusterid=gph.clusterid
		INNER JOIN (
			SELECT clusterid, sla, exec_host, COUNT(jobid) AS runJobs, SUM(num_cpus) AS runSlots
			FROM grid_jobs AS gj
			WHERE stat='RUNNING'
			AND sla!='' $clsql
			GROUP BY clusterid, sla, exec_host
		) AS gj
		ON gj.exec_host = gph.host
		AND gj.clusterid = gph.clusterid
		AND gj.sla = gpd.consumer
		GROUP BY gpd.clusterid, gpd.name, gpd.consumer");
	db_execute("UPDATE grid_guarantee_pool_distribution SET runJobs=0, runSlots=0");
	grid_pump_records($records, "grid_guarantee_pool_distribution", $format, false, $duplicate);

	$format = array("clusterid", "name", "consumer", "pendJobs", "pendSlots");

	$duplicate = "ON DUPLICATE KEY UPDATE pendJobs=VALUES(pendJobs), pendSlots=VALUES(pendSlots)";

	$records = db_fetch_assoc("SELECT gpd.clusterid, gpd.name, gpd.consumer,
		SUM(gj.pendJobs) as pendJobs, SUM(gj.pendSlots) AS pendSlots
		FROM grid_guarantee_pool_distribution AS gpd
		INNER JOIN (
			SELECT clusterid, sla, COUNT(jobid) AS pendJobs, SUM(num_cpus) AS pendSlots
			FROM grid_jobs AS gj
			WHERE stat='PEND'
			AND sla!='' $clsql
			GROUP BY clusterid, sla
		) AS gj
		ON gj.clusterid = gpd.clusterid
		AND gj.sla = gpd.consumer
		GROUP BY gpd.clusterid, gpd.name, gpd.consumer");
	db_execute("UPDATE grid_guarantee_pool_distribution SET pendJobs=0, pendSlots=0");
	grid_pump_records($records, "grid_guarantee_pool_distribution", $format, false, $duplicate);

	$format = array("clusterid", "name", "runJobs", "runSlots", "pendJobs", "pendSlots");

	$duplicate = "ON DUPLICATE KEY UPDATE runJobs=VALUES(runJobs), runSlots=VALUES(runSlots),
		pendJobs=VALUES(pendJobs), pendSlots=VALUES(pendSlots)";

	$records = db_fetch_assoc("SELECT gpd.clusterid, gpd.name, SUM(runJobs) AS runJobs, SUM(runSlots) AS runSlots,
		SUM(pendJobs) AS pendJobs, SUM(pendSlots) AS pendSlots
		FROM grid_guarantee_pool_distribution AS gpd
		GROUP BY clusterid, name");
	grid_pump_records($records, "grid_guarantee_pool", $format, false, $duplicate);

	$format = array("clusterid", "name", "memReserved", "memUsed", "memRequested");

	$duplicate = "ON DUPLICATE KEY UPDATE memReserved=VALUES(memReserved), memUsed=VALUES(memUsed),
		memRequested=VALUES(memRequested)";

	$records = db_fetch_assoc("SELECT gph.clusterid, gph.name,
		SUM(mem_reserved*1024) AS memReserved,
		SUM(mem_used*1024) AS memUsed,
		SUM(mem_requested*1024) AS memRequested
		FROM (
			SELECT clusterid, exec_host,
			SUM(mem_reserved) as mem_reserved,
			SUM(mem_used) as mem_used,
			SUM(mem_requested) as mem_requested
			FROM grid_jobs
			WHERE stat='RUNNING'
			AND sla!=''
			GROUP BY clusterid, exec_host
		) AS aj
		INNER JOIN grid_guarantee_pool_hosts AS gph
		ON gph.clusterid = aj.clusterid
		AND aj.exec_host = gph.host
		GROUP BY gph.clusterid, gph.name");
	db_execute("UPDATE grid_guarantee_pool SET memReserved=0, memUsed=0, memRequested=0");
	grid_pump_records($records, "grid_guarantee_pool", $format, false, $duplicate);

	/* limit the clusters */
	$clsql = get_cluster_list('ghi.clusterid');
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$format = array("clusterid", "name", "memSlotUtil", "slotUtil", "cpuUtil");

	$duplicate = "ON DUPLICATE KEY UPDATE memSlotUtil=VALUES(memSlotUtil), slotUtil=VALUES(slotUtil),
		cpuUtil=VALUES(cpuUtil)";

	$records = db_fetch_assoc("SELECT clusterid, name,
		SUM(memSlotUtil*totalSlots)/SUM(totalSlots) AS memSlotUtil,
		SUM(slotUtil*totalSlots)/SUM(totalSlots) AS slotUtil,
		SUM(cpuUtil*totalSlots)/SUM(totalSlots) AS cpuUtil
		FROM (
			SELECT clusterid, name, host, memSlotUtil, cpuUtil, slotUtil, totalSlots
			FROM (
				SELECT *, (totalSlots-(FLOOR(totalSlots*freeMem/maxMem)))/totalSlots*100 AS memSlotUtil,
				ROUND((numRun/totalSlots)*100,1) AS slotUtil
				FROM (
					SELECT gph.clusterid, gph.name, ghr.host, numRun, ROUND(IF(ut>0, ut*100,0),1) AS cpuUtil,
					ROUND(totalValue,0) AS freeMem, ROUND(reservedValue,0) AS reservedMem,
					GREATEST(maxJobs, maxCpus) AS totalSlots, maxMem
					FROM grid_hosts_resources AS ghr
					INNER JOIN grid_hostinfo AS ghi
					ON ghi.host=ghr.host
					AND ghi.clusterid=ghr.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host=ghr.host
					AND gh.clusterid=ghr.clusterid
					AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
					INNER JOIN grid_load AS gl
					ON gl.host=ghr.host
					AND gl.clusterid=ghr.clusterid
					INNER JOIN grid_guarantee_pool_hosts AS gph
					ON ghr.host=gph.host
					AND ghr.clusterid=gph.clusterid
					WHERE resource_name='mem' $clsql
				) AS results
			) AS results2
		) AS results3
		GROUP BY clusterid, name");
	grid_pump_records($records, "grid_guarantee_pool", $format, false, $duplicate);

	/* log end event */
	grid_debug("Updating Guarantee Resource Pool - Complete");
}

function update_group() {
	if (read_config_option("grid_job_group_aggregation") == "on") {
		grid_debug("Updating Job Group Stats - Begin");

		/* setup for deletion */
		db_execute("UPDATE grid_groups SET present=0 WHERE present=1");

		$tmpname = "groupName";

		$grpMagic = str_replace("groupName", "jobGroup", $tmpname);

		/* limit the clusters */
		$clsql = get_cluster_list();
		if (strlen($clsql)) {
			$clsql = " AND $clsql";
		}

		$format = array("clusterid", "groupName", "numRUN", "numPEND", "numJOBS", "numSSUSP",
			"numUSUSP", "efficiency", "avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

		$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
			numJOBS=VALUES(numJOBS), numSSUSP=VALUES(numSSUSP), numUSUSP=VALUES(numUSUSP),
			efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem), max_mem=VALUES(max_mem),
			avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
			total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

		/* format the SQL insert syntax */
		$records = db_fetch_assoc("SELECT clusterid,
			$grpMagic AS groupName,
			SUM(CASE WHEN stat IN ('RUNNING','PROV') THEN num_cpus ELSE 0 END) as numRUN,
			SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
			SUM(num_cpus) as numJOBS,
			SUM(CASE WHEN stat='SSUSP' THEN num_cpus ELSE 0 END) as numSSUSP,
			SUM(CASE WHEN stat='USUSP' THEN num_cpus ELSE 0 END) as numUSUSP,
			(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as avg_mem,
			MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_memory ELSE 0 END) as max_mem,
			AVG(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) as avg_swap,
			MAX(CASE WHEN stat IN ('RUNNING','PROV') THEN max_swap ELSE 0 END) as max_swap,
			SUM(cpu_used) as total_cpu,
			NOW() AS last_updated,
			'1' as present
			FROM grid_jobs
			WHERE jobGroup!='' AND stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
			$clsql
			GROUP BY clusterid, $grpMagic");
		grid_pump_records($records, "grid_groups", $format, false, $duplicate);

		/* reset old records */
		db_execute("UPDATE grid_groups
			SET numRUN=0,
				numPEND=0,
				numJOBS=0,
				numSSUSP=0,
				numUSUSP=0,
				efficiency=0,
				avg_mem=0,
				max_mem=0,
				avg_swap=0,
				max_swap=0,
				total_cpu=0
			WHERE present=0");

		/* log end event */
		grid_debug("Updating Job Group Stats - Complete");
	}
}

function update_application() {
	grid_debug("Updating Applications Stats - Begin");

	/* setup for deletion */
	db_execute("UPDATE grid_applications SET present=0 WHERE present=1");

	/* limit the clusters */
	$clsql = get_cluster_list();
	if (strlen($clsql)) {
		$clsql = " AND $clsql";
	}

	$format = array("clusterid", "appName", "numRUN", "numPEND", "numJOBS", "efficiency",
		"avg_mem", "max_mem", "avg_swap", "max_swap", "total_cpu", "last_updated", "present");

	$duplicate = "ON DUPLICATE KEY UPDATE numRUN=VALUES(numRUN), numPEND=VALUES(numPEND),
		numJOBS=VALUES(numJOBS), efficiency=VALUES(efficiency), avg_mem=VALUES(avg_mem),
		max_mem=VALUES(max_mem), avg_swap=VALUES(avg_swap), max_swap=VALUES(max_swap),
		total_cpu=VALUES(total_cpu), last_updated=VALUES(last_updated), present=VALUES(present)";

	/* format the SQL insert syntax */
	$records = db_fetch_assoc("SELECT clusterid,
		app AS appName,
		SUM(CASE WHEN stat IN ('RUNNING','PROV')  THEN num_cpus ELSE 0 END) as numRUN,
		SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) as numPEND,
		SUM(num_cpus) as numJOBS,
		(SUM(CASE WHEN stat IN ('RUNNING','PROV') AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (stime+utime) ELSE 0 END) / SUM(CASE WHEN stat='RUNNING' AND run_time>'"  . read_config_option('grid_efficiency_window') . "' THEN (run_time*max_allocated_processes) ELSE 0 END)) * 100 as efficiency,
		AVG(CASE WHEN stat IN ('RUNNING','PROV')  THEN max_memory ELSE 0 END) as avg_mem,
		MAX(CASE WHEN stat IN ('RUNNING','PROV')  THEN max_memory ELSE 0 END) as max_mem,
		AVG(CASE WHEN stat IN ('RUNNING','PROV')  THEN max_swap ELSE 0 END) as avg_swap,
		MAX(CASE WHEN stat IN ('RUNNING','PROV')  THEN max_swap ELSE 0 END) as max_swap,
		SUM(cpu_used) as total_cpu,
		NOW() AS last_updated,
		'1' as present
		FROM grid_jobs
		WHERE app!='' AND stat IN ('RUNNING', 'PROV', 'USUSP', 'PSUSP', 'SSUSP', 'PEND')
		$clsql
		GROUP BY clusterid, app");
	grid_pump_records($records, "grid_applications", $format, false, $duplicate);

	/* reset old records */
	db_execute("UPDATE grid_applications
		SET numRUN=0,
			numPEND=0,
			numJOBS=0,
			efficiency=0,
			avg_mem=0,
			max_mem=0,
			avg_swap=0,
			max_swap=0,
			total_cpu=0
		WHERE present=0");

	/* log end event */
	grid_debug("Updating Application Stats - Complete");
}

function find_flapping_jobs() {
	if (read_config_option("grid_flapping_enabled") == "on") {
		grid_debug("Checking for Flapping Jobs");

		$flapping_threshold = read_config_option("grid_flapping_threshold");

		$new_flapping_jobs = db_fetch_assoc("SELECT
			jobname, clusterid, jobid, indexid, submit_time, user, queue
			FROM grid_jobs
			WHERE stat_changes>='$flapping_threshold'
			AND flapping_logged='0'
			AND stat!='EXIT' AND stat!='DONE'");

		/* update the flapping logged status */
		db_execute_prepared("UPDATE grid_jobs SET flapping_logged='1'
			WHERE stat !='EXIT' AND stat!='DONE'
			AND stat_changes>=?
			AND flapping_logged='0'", array($flapping_threshold));

		if (cacti_sizeof($new_flapping_jobs)) {
			foreach ($new_flapping_jobs AS $job) {
				cacti_log("WARNING: Flapping Detected on JobID:'" . $job["jobid"] . ($job["indexid"] > 0 ? "[" . $job["indexid"] . "]'" : "'") . ", ClusterID:'" . $job["clusterid"] . "', ClusterName:'" . grid_get_clustername($job["clusterid"]) . "', JobName:'" . $job["jobname"] . "'", false, "GRID");
			}
		}
	}
}

function collect_host_starvation_data($start_time, $start) {
	grid_debug("Looking at Job Counts for Starvation Calculations");

	/* save the scan date information */
	$scan_date = date("Y-m-d H:i:00", $start_time);

	/* add new hosts to the grid_hosts_jobtraffic table as required */
	db_execute("INSERT INTO
		grid_hosts_jobtraffic (host, clusterid)
		SELECT grid_hosts.host, grid_hosts.clusterid
		FROM grid_hosts
		LEFT JOIN grid_hosts_jobtraffic
		ON (grid_hosts.clusterid = grid_hosts_jobtraffic.clusterid)
		AND (grid_hosts.host = grid_hosts_jobtraffic.host)
		WHERE grid_hosts_jobtraffic.host IS NULL");

	/* get the maximum date from the interval stat's table */
	$max_date = db_fetch_cell("SELECT date_recorded FROM grid_job_interval_stats ORDER BY date_recorded DESC LIMIT 1");
	$max_date_ts = 0;
	if ($max_date !== false){
		$max_date_ts = strtotime($max_date);
	}

	if ($max_date_ts > 0 && (($max_date_ts - 30) < $start_time) && (($max_date_ts + 30) > $start_time)) {
		/* get new job flow records */
		$records = db_fetch_assoc("SELECT DISTINCT clusterid, stat, exec_host
			FROM grid_job_interval_stats
			WHERE date_recorded='$max_date'");

		if (cacti_sizeof($records)) {
			foreach ($records as $record) {
				switch ($record['stat']) {
				case "ENDED" :
					db_execute_prepared("UPDATE grid_hosts_jobtraffic
						SET
						job_last_ended=?,
						date_recorded=?
						WHERE
						host=?
						AND
						clusterid=?",
						array($scan_date, $scan_date, $record['exec_host'], $record['clusterid']));

					break;
				case "STARTED" :
					db_execute_prepared("UPDATE grid_hosts_jobtraffic
						SET
						job_last_started=?,
						date_recorded=?
						WHERE
						host=?
						AND
						clusterid=?",
						array($scan_date, $scan_date, $record['exec_host'], $record['clusterid']));

					break;
				case "EXITED" :
					db_execute_prepared("UPDATE grid_hosts_jobtraffic
						SET
						job_last_exited=?,
						date_recorded=?
						WHERE
						host=?
						AND
						clusterid=?",
						array($scan_date, $scan_date, $record['exec_host'], $record['clusterid']));

					break;
				case "SUBMITTED" :
				default:
					/* don't care, not on a host */
				}
			}
		}
	}
}

function curve_fitting_rusage_records($rusage_records = array()) {
	if (!cacti_sizeof($rusage_records)) return array();

	//get the last rusage record in last job graphing if it exists and assgin it to $prev. $prev could be empty.
	$first_record = current($rusage_records);

	$prev = db_fetch_row_prepared("SELECT * FROM grid_jobs_rusage
		WHERE (jobid=?
		AND indexid=?
		AND clusterid=?
		AND submit_time=?
		AND update_time<?)
		ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['update_time']));

	if (!cacti_sizeof($prev)) {
		$pt = db_fetch_row_prepared("select * from grid_table_partitions where table_name = 'grid_jobs_rusage'
				  and min_time <=?
				  and max_time >=?
				  order by max_time asc limit 1 ", array($first_record['submit_time'], $first_record['update_time']));
		if (cacti_sizeof($pt)) {
			$tablename   = 'grid_jobs_rusage_v' . $pt['partition'];
			$select_list = build_partition_select_list($tablename, "grid_jobs_rusage");
			$prev = db_fetch_row_prepared("SELECT $select_list FROM $tablename
				WHERE (jobid=?
				AND indexid=?
				AND clusterid=?
				AND submit_time=?
				AND update_time<?)
				ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['update_time']));
		}
	}

	$new_rusage_records = array();
	foreach ($rusage_records as $cur) {
		// if $prev is empty, meaning $cur is the first point to draw in all time. no way to do curv fitting.
		if (!cacti_sizeof($prev)) {
			$new_rusage_records[] = $cur;
			$prev = $cur;
			continue;
		}

		//add adtionial points between [$prev, $cur]
		$prev_update_time = strtotime($prev["update_time"]);
		$cur_update_time = strtotime($cur["update_time"]);

		$slope_utime        = ($cur['utime'] - $prev['utime'])/($cur_update_time - $prev_update_time);
		$slope_stime        = ($cur['stime'] - $prev['stime'])/($cur_update_time - $prev_update_time);
		$slope_mem          = ($cur['mem'] - $prev['mem'])/($cur_update_time - $prev_update_time);
		$slope_swap         = ($cur['swap'] - $prev['swap'])/($cur_update_time - $prev_update_time);
		$slope_mem_reserved = ($cur['mem_reserved'] - $prev['mem_reserved'])/($cur_update_time - $prev_update_time);
		$slope_npids        = ($cur['npids'] - $prev['npids'])/($cur_update_time - $prev_update_time);
		$slope_npgids       = ($cur['npgids'] - $prev['npgids'])/($cur_update_time - $prev_update_time);
		$slope_nthreads     = ($cur['nthreads'] - $prev['nthreads'])/($cur_update_time - $prev_update_time);
		$slope_num_cpus     = ($cur['num_cpus'] - $prev['num_cpus'])/($cur_update_time - $prev_update_time);

		for ($i = floor($prev_update_time/150)*150+150; $i < floor($cur_update_time/150)*150; $i += 150 ) {
			$new_utime = $prev['utime'] + ($i - $prev_update_time) * $slope_utime ;
			$new_stime = $prev['stime'] + ($i - $prev_update_time) * $slope_stime ;
			$new_mem = $prev['mem'] + ($i - $prev_update_time) * $slope_mem ;
			$new_swap = $prev['swap'] + ($i - $prev_update_time) * $slope_swap ;
			$new_mem_reserved = $prev['mem_reserved'] + ($i - $prev_update_time) * $slope_mem_reserved ;
			$new_npids = $prev['npids'] + ($i - $prev_update_time) * $slope_npids ;
			$new_npgids = $prev['npgids'] + ($i - $prev_update_time) * $slope_npgids ;
			$new_nthreads = $prev['nthreads'] + ($i - $prev_update_time) * $slope_nthreads ;
			$new_num_cpus = $prev['num_cpus'] + ($i - $prev_update_time) * $slope_num_cpus ;

			$new_point = array(
				'clusterid' => $cur['clusterid'],
				'jobid'=> $cur['jobid'],
				'indexid'=> $cur['indexid'],
				'submit_time'=> $cur['submit_time'],
				'update_time'=> date('Y-n-d H:i:s',$i),
				'utime' => round($new_utime),
				'stime' => round($new_stime),
				'mem' => round($new_mem),
				'swap' => round($new_swap),
				'mem_reserved' => round($new_mem_reserved),
				'npids' => round($new_npids),
				'npgids' => round($new_npgids),
				'nthreads' => round($new_nthreads),
				'num_cpus' => round($new_num_cpus),
				'pids'=> $cur['pids'],
				'pgids' => $cur['pgids']
			);

			$new_rusage_records[] = $new_point;
		}

		$new_rusage_records[] = $cur;
		$prev = $cur;
	}

	return ($new_rusage_records);
}

function curve_fitting_rusage_records_host($rusage_records = array()) {
	if (!cacti_sizeof($rusage_records)) return array();

	//get the last rusage record in last job graphing if it exists and assgin it to $prev. $prev could be empty.
	$first_record = current($rusage_records);

	$prev = db_fetch_row_prepared("SELECT * FROM grid_jobs_host_rusage
		WHERE (jobid=?
		AND indexid=?
		AND clusterid=?
		AND submit_time=?
		AND host=?
		AND update_time<?)
		ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['host'], $first_record['update_time']));

	if (!cacti_sizeof($prev)) {
		$pt = db_fetch_row_prepared("select * from grid_table_partitions where table_name = 'grid_jobs_host_rusage'
		          and min_time <=?
		          and max_time >=?
		          order by max_time asc limit 1", array($first_record['submit_time'], $first_record['update_time']));
		if (cacti_sizeof($pt)) {
			$tablename = 'grid_jobs_host_rusage_v' . $pt['partition'];
			$select_list=build_partition_select_list($tablename, "grid_jobs_host_rusage");
			$prev = db_fetch_row_prepared("SELECT $select_list FROM $tablename
				WHERE (jobid=?
				AND indexid=?
				AND clusterid=?
				AND submit_time=?
				AND host=?
				AND update_time<?)
				ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['host'], $first_record['update_time']));
		}
	}

	$new_rusage_records = array();
	foreach ($rusage_records as $cur) {
		// if $prev is empty, meaning $cur is the first point to draw in all time. no way to do curv fitting.
		if (!cacti_sizeof($prev)) {
			$new_rusage_records[] = $cur;
			$prev = $cur;
			continue;
		}

		//add adtionial points between [$prev, $cur]
		$prev_update_time = strtotime($prev["update_time"]);
		$cur_update_time = strtotime($cur["update_time"]);

		$slope_utime        = ($cur['utime'] - $prev['utime'])/($cur_update_time - $prev_update_time);
		$slope_stime        = ($cur['stime'] - $prev['stime'])/($cur_update_time - $prev_update_time);
		$slope_mem          = ($cur['mem'] - $prev['mem'])/($cur_update_time - $prev_update_time);
		$slope_swap         = ($cur['swap'] - $prev['swap'])/($cur_update_time - $prev_update_time);
		$slope_processes    = ($cur['processes'] - $prev['processes'])/($cur_update_time - $prev_update_time);

		for ($i = floor($prev_update_time/150)*150+150; $i < floor($cur_update_time/150)*150; $i += 150 ) {
			$new_utime = $prev['utime'] + ($i - $prev_update_time) * $slope_utime ;
			$new_stime = $prev['stime'] + ($i - $prev_update_time) * $slope_stime ;
			$new_mem = $prev['mem'] + ($i - $prev_update_time) * $slope_mem ;
			$new_swap = $prev['swap'] + ($i - $prev_update_time) * $slope_swap ;
			$new_processes = $prev['processes'] + ($i - $prev_update_time) * $slope_processes ;

			$new_point = array("clusterid" => $cur['clusterid'],
			                   "jobid"=> $cur['jobid'],
			                   "indexid"=> $cur['indexid'],
			                   "submit_time"=> $cur['submit_time'],
			                   "update_time"=> date("Y-n-d H:i:s",$i),
			                   "host" => $cur['host'],
			                   "utime" => round($new_utime),
			                   "stime" => round($new_stime),
			                   "mem" => round($new_mem),
			                   "swap" => round($new_swap),
			                   "processes" => round($new_processes));
			$new_rusage_records[] = $new_point;
		}
		$new_rusage_records[] = $cur;
		$prev = $cur;
	}

	return ($new_rusage_records);
}

function curve_fitting_rusage_records_gpu($rusage_records = array()) {
	if (!cacti_sizeof($rusage_records)) return array();

	//get the last rusage record in last job graphing if it exists and assgin it to $prev. $prev could be empty.
	$first_record = current($rusage_records);

	$prev = db_fetch_row_prepared("SELECT * FROM grid_jobs_gpu_rusage
		WHERE (jobid=?
		AND indexid=?
		AND clusterid=?
		AND submit_time=?
		AND host=?
		AND gpu_id=?
		AND update_time <?)
		ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['host'], $first_record['gpu_id'], $first_record['update_time']));

	if (!cacti_sizeof($prev)) {
		$pt = db_fetch_row_prepared("select * from grid_table_partitions where table_name = 'grid_jobs_gpu_rusage'
		          and min_time <=?
		          and max_time >=?
		          order by max_time asc limit 1", array($first_record['submit_time'], $first_record['update_time']));
		if (cacti_sizeof($pt)) {
			$tablename = 'grid_jobs_gpu_rusage_v' . $pt['partition'];
			$select_list=build_partition_select_list($tablename, "grid_jobs_gpu_rusage");
			$prev = db_fetch_row_prepared("SELECT $select_list FROM $tablename
				WHERE (jobid=?
				AND indexid=?
				AND clusterid=?
				AND submit_time=?
				AND host=?
				AND gpu_id=?
				AND update_time <?)
				ORDER BY update_time DESC LIMIT 1", array($first_record['jobid'], $first_record['indexid'], $first_record['clusterid'], $first_record['submit_time'], $first_record['host'], $first_record['gpu_id'], $first_record['update_time']));
		}
	}

	$new_rusage_records = array();
	foreach ($rusage_records as $cur) {
		// if $prev is empty, meaning $cur is the first point to draw in all time. no way to do curv fitting.
		if (!cacti_sizeof($prev)) {
			$new_rusage_records[] = $cur;
			$prev = $cur;
			continue;
		}

		//add adtionial points between [$prev, $cur]
		$prev_update_time = strtotime($prev["update_time"]);
		$cur_update_time = strtotime($cur["update_time"]);

		$slope_gut  = ($cur['sm_ut_avg'] - $prev['sm_ut_avg'])/($cur_update_time - $prev_update_time);
		$slope_gmut = ($cur['mem_ut_avg'] - $prev['mem_ut_avg'])/($cur_update_time - $prev_update_time);
		$slope_gmem = ($cur['gpu_mused_max'] - $prev['gpu_mused_max'])/($cur_update_time - $prev_update_time);

		for ($i = floor($prev_update_time/150)*150+150; $i < floor($cur_update_time/150)*150; $i += 150 ) {
			$new_gut = $prev['sm_ut_avg'] + ($i - $prev_update_time) * $slope_gut;
			$new_gmut = $prev['mem_ut_avg'] + ($i - $prev_update_time) * $slope_gmut;
			$new_gmem = $prev['gpu_mused_max'] + ($i - $prev_update_time) * $slope_gmem;

			$new_point = array("clusterid" => $cur['clusterid'],
			                   "jobid"=> $cur['jobid'],
			                   "indexid"=> $cur['indexid'],
			                   "submit_time"=> $cur['submit_time'],
			                   "update_time"=> date("Y-n-d H:i:s",$i),
			                   "host" => $cur['host'],
			                   "gpu_id" => $cur['gpu_id'],
			                   "sm_ut_avg" => round($new_gut),
			                   "mem_ut_avg" => round($new_gmut),
			                   "gpu_mused_max" => round($new_gmem));
			$new_rusage_records[] = $new_point;
		}
		$new_rusage_records[] = $cur;
		$prev = $cur;
	}

	return ($new_rusage_records);
}

/**
 * Build select list of partitioned table, set default value of columns that not exist.
 * This is to avoid partition table must upgrade immediately, and to avoid SQL error.
 */
function build_partition_select_list($table, $reference_table, $column_default_value=0) {

	$fields = "";

	$rcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $reference_table"), "Field", "Field");
	$tcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table"), "Field", "Field");

	if (cacti_sizeof($rcols)) {
		foreach ($rcols as $col) {
			if (isset($tcols[$col])) {
				$fields .= (strlen($fields) ? ", ":" ") . "$table.$col";
			} else {
				$fields .= (strlen($fields) ? ", ":" ") . "$column_default_value AS $col";
			}
		}
	}

	return $fields;
}

function update_job_rrds($table_name, $cache_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe) {
	/* validate the job record */
	$job = get_rusage_job_record($table_name, $jobid, $indexid, $clusterid, $submit_time);

	if (cacti_sizeof($job)) {
		$cache_file = $cache_directory . "/" . $file_base . ".rrd";
		if (file_exists($cache_file)) {
			//Skip update rrd and re-create graph if the last_updated time of rrd file is in rusage collection frequency.
			if(time() - filemtime($cache_file) <= 180){
				return FALSE;
			}
			$mod_time = date("Y-n-d H:i:s", filemtime($cache_file));
		} else {
			if ($job['stat']=='PEND') {
				$mod_time = $job["submit_time"];
			} else {
				$mod_time = $job["start_time"];
			}
			create_job_rrd_file($cache_file, $mod_time, $data_class, $rrdtool_pipe);
		}

		/* get the rusage records */
		$update_records = get_rusage_records($clusterid, $jobid, $indexid, date("Y-n-d H:i:s", $submit_time), $mod_time);

		/*append rrd points by curve fitting algorithm*/
		$update_records = curve_fitting_rusage_records ($update_records);

		/* set the rrdtool update prefix */
		$update_prefix  = "update " . $cache_file . " --template utime:stime:mem:swap:mem_reserved:npids:npgids:threads:num_cpus ";

		if (cacti_sizeof($update_records)) {
			$len = 0;
			$update = $update_prefix;
			$last_record=null;
			foreach ($update_records as $record) {
				if (!empty($last_record) && strtotime($last_record['update_time']) == strtotime($record['update_time'])) {
					continue;
				} else {
					$last_record = $record;
				}

				$update .=  strtotime($record["update_time"]) . ":" .
							trick64($record["utime"]) . ":" .
							trick64($record["stime"]) . ":" .
							trick64($record["mem"]) . ":" .
							trick64($record["swap"]) . ":" .
							trick64($record["mem_reserved"]) . ":" .
							$record["npids"] . ":" .
							$record["npgids"] . ":" .
							$record["nthreads"] . ":" .
							$record["num_cpus"] . " ";

				if (strlen($update) > 2000) {
					rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
					$update = $update_prefix;
				}
			}

			if (strlen($update) > strlen($update_prefix)) {
				rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
			}

			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function update_job_rrds_host($table_name, $cache_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe, $host_name) {
	/* validate the job record */
	$job = get_rusage_job_record($table_name, $jobid, $indexid, $clusterid, $submit_time);

	if (cacti_sizeof($job)) {
		$cache_file = $cache_directory . "/" . $file_base . ".rrd";
		if (file_exists($cache_file)) {
			//Skip update rrd and re-create graph if the last_updated time of rrd file is in rusage collection frequency.
			if(time() - filemtime($cache_file) <= 180){
				return FALSE;
			}
			$mod_time = date("Y-n-d H:i:s", filemtime($cache_file));
		} else {
			$mod_time = $job["start_time"];
			create_job_rrd_file_host($cache_file, $mod_time, $data_class, $rrdtool_pipe);
		}

		/* get the rusage records */
		$update_records = get_rusage_records_host($jobid, $indexid, $clusterid, date("Y-n-d H:i:s", $submit_time), $host_name, $mod_time);

		/*append rrd points by curve fitting algorithm*/
		$update_records = curve_fitting_rusage_records_host($update_records);

		/* set the rrdtool update prefix */
		$update_prefix  = "update " . $cache_file . " --template utime:stime:mem:swap:processes ";

		if (cacti_sizeof($update_records)) {
			$len = 0;
			$update = $update_prefix;
			$last_record=null;
			foreach ($update_records as $record) {
				if (!empty($last_record) && strtotime($last_record['update_time']) == strtotime($record['update_time'])) {
					continue;
				} else {
					$last_record = $record;
				}

				$update .=  strtotime($record["update_time"]) . ":" .
							trick64($record["utime"]) . ":" .
							trick64($record["stime"]) . ":" .
							trick64($record["mem"]) . ":" .
							trick64($record["swap"]) . ":" .
							trick64($record["processes"]) . " " ;

				if (strlen($update) > 2000) {
					rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
					$update = $update_prefix;
				}
			}

			if (strlen($update) > strlen($update_prefix)) {
				rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
			}

			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function update_job_rrds_gpu($table_name, $cache_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class,
							$rrdtool_pipe, $host_name, $gpu_id) {
	/* validate the job record */
	$job = get_rusage_job_record($table_name, $jobid, $indexid, $clusterid, $submit_time);

	if (cacti_sizeof($job)) {
		$cache_file = $cache_directory . "/" . $file_base . ".rrd";
		if (file_exists($cache_file)) {
			//Skip update rrd and re-create graph if the last_updated time of rrd file is in rusage collection frequency.
			if(time() - filemtime($cache_file) <= 180){
				return FALSE;
			}
			$mod_time = date("Y-n-d H:i:s", filemtime($cache_file));
		}else{
			$mod_time = $job["start_time"];
			create_job_rrd_file_gpu($cache_file, $mod_time, $data_class, $rrdtool_pipe);
		}

		/* get the rusage records */
		$update_records = get_rusage_records_gpu($jobid, $indexid, $clusterid, date("Y-n-d H:i:s", $submit_time), $host_name, $gpu_id, $mod_time);

		/*append rrd points by curve fitting algorithm*/
		$update_records = curve_fitting_rusage_records_gpu($update_records);

		/* set the rrdtool update prefix */
		$update_prefix  = "update " . $cache_file . " --template gut:gmut:gmem ";

		if (cacti_sizeof($update_records)) {
			$len = 0;
			$update = $update_prefix;
			$last_record=null;
			foreach ($update_records as $record) {
				if (!empty($last_record) && strtotime($last_record['update_time']) == strtotime($record['update_time'])) {
				    continue;
				} else {
				    $last_record = $record;
				}

				$update .=  strtotime($record["update_time"]) . ":" .
							trick64($record["sm_ut_avg"]) . ":" .
							trick64($record["mem_ut_avg"]) . ":" .
							trick64($record["gpu_mused_max"]) . " " ;

				if (strlen($update) > 2000) {
					rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
					$update = $update_prefix;
				}
			}

			if (strlen($update) > strlen($update_prefix)) {
				rrdtool_execute($update, true, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
			}

			return TRUE;
		}else{
			return FALSE;
		}
	}else{
		return FALSE;
	}
}

/**
 * Query job by job.PK from specified table, include partition tables
 * @param string $table_name
 * @param int $jobid
 * @param int $indexid
 * @param int $clusterid
 * @param timestamp $submit_time
 * @return job record as array type
 */
function get_rusage_job_record($table_name, $jobid, $indexid, $clusterid, $submit_time) {
	global $config;

	include_once($config["base_path"] . "/plugins/grid/lib/grid_partitioning.php");

	if (read_config_option("grid_partitioning_enable") == "") {
		return db_fetch_row_prepared("SELECT stat, submit_time, start_time, end_time
			FROM $table_name
			WHERE clusterid=?
			AND submit_time=?
			AND jobid=?
			AND indexid=?", array($clusterid, date("Y-m-d H:i:s", $submit_time), $jobid, $indexid));
	} else {
		$query  = "";
		$sql_params = array();
		$tables = partition_get_partitions_for_query($table_name, date("Y-m-d H:i:s", $submit_time), date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach ($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ";
				}

				$query .= "SELECT stat, submit_time, start_time, end_time
					FROM $table
					WHERE clusterid=?
					AND submit_time=?
					AND jobid=?
					AND indexid=?";
				$sql_params[] = $clusterid;
				$sql_params[] = date("Y-m-d H:i:s", $submit_time);
				$sql_params[] = $jobid;
				$sql_params[] = $indexid;
			}

			return db_fetch_row_prepared($query, $sql_params);
		} else {
			return array();
		}
	}
}

/**
 * Query one or more job rusage data
 *
 * @param integer $clusterid
 * @param integer $jobid
 * @param integer $indexid
 * @param string  $submit_time "Y-m-d H:i:s" format
 * @param string  $mod_time
 * @param integer $oldest 1: get the oldest rusage after $mod_time, e.g. idle checking; -1: get the newest rusage, e.g. Job->Detail display
 * @param boolean $unixtime
 * @param string $end_time If NULL, query partition table until now
 * @param boolean $isrun job status, skip partition table query if job stat is RUNNING and query target is the newest rusage.
 * @return array[job rusage]
 */
function get_rusage_records($clusterid, $jobid, $indexid, $submit_time, $mod_time, $oldest = 0, $unixtime = false, $end_time = NULL, $isrun = false) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	$build_flag   = false;
	$limitstr     = "";
	$orderstr     = "";
	$order        = "";
	$where_update_time  = "AND update_time > ";

	if ( $oldest != 0 ) {
		$update_time_field  = "MIN(update_time) AS update_time";
		$where_update_time  = "AND update_time = ";
		if($oldest < 0){
			$update_time_field  = "MAX(update_time) AS update_time";
		}
		$limitstr = "LIMIT 1";
	} else{
		$orderstr = "ORDER BY update_time ASC";
	}

	if ( $unixtime ) {
		$fieldlist = " UNIX_TIMESTAMP(update_time) AS update_time, stime+utime AS total_cpu ";
	} else {
		$fieldlist = " * ";
		$build_flag=true;
	}

	if (($isrun && $oldest < 0) || read_config_option("grid_partitioning_enable") == "") {
		$update_time =  $mod_time;
		if ( $oldest != 0 ) {
			$query_update_time = "SELECT $update_time_field FROM grid_jobs_rusage
				WHERE (clusterid = ? AND jobid = ? AND indexid = ? AND submit_time = ? AND update_time > ?)";
			$update_time = db_fetch_cell_prepared($query_update_time, array($clusterid, $jobid, $indexid, $submit_time, $mod_time));
		}

		if(rtm_strlen($update_time)){
			$where_update_time .= '?';
			$query = "SELECT $fieldlist FROM grid_jobs_rusage
				WHERE (clusterid = ? AND jobid = ? AND indexid = ? AND submit_time = ? $where_update_time)
				$orderstr $limitstr";

			$param_arr = array($clusterid, $jobid, $indexid, $submit_time, $update_time);

			if ($oldest != 0) {
				return db_fetch_row_prepared($query, $param_arr);
			} else {
				return db_fetch_assoc_prepared($query, $param_arr);
			}
		}
	} else {
		include_once($config["base_path"] . "/plugins/grid/lib/grid_partitioning.php");
		$query  = "";
		if($end_time == NULL){
			$end_time = date("Y-m-d H:i:s");
		}
		$tables = partition_get_partitions_for_query("grid_jobs_rusage", $mod_time, $end_time);

		$update_time =  $mod_time;

		if (cacti_sizeof($tables)) {
			if ( $oldest != 0 ) {
				$query_update_time = "";
				foreach ($tables as $table) {
					if (strlen($query_update_time)) {
						$query_update_time .= " UNION ";
					}
					$query_update_time .= "SELECT $update_time_field FROM $table
						WHERE (clusterid ='$clusterid' AND jobid = '$jobid' AND indexid = '$indexid' AND submit_time = '$submit_time' AND update_time > '$mod_time')";
				}
				$update_time = db_fetch_cell_prepared("SELECT $update_time_field FROM ($query_update_time) AS rusage_record");
			}

			if(rtm_strlen($update_time)){
				$where_update_time .= "'$update_time'";

				foreach ($tables as $table) {
					if (strlen($query)) {
						$query .= " UNION ";
					}

					if ($build_flag) {
						$fieldlist = build_partition_select_list($table, "grid_jobs_rusage");
					}

					$query .= "SELECT $fieldlist FROM $table WHERE (clusterid ='$clusterid' AND jobid = '$jobid' AND indexid = '$indexid' AND submit_time = '$submit_time' $where_update_time)";
				}

				$query = "SELECT * FROM ($query) AS rusage_record $orderstr $limitstr";

				//cacti_log("DEBUG: SQL: " . str_replace("\n, " ", $query));
				if ($oldest != 0) {
					return db_fetch_row($query);
				} else {
					return db_fetch_assoc($query);
				}
			}
		}
	}
	return array();
}

function get_rusage_records_exist($jobid, $indexid, $clusterid, $submit_time) {
	if(!is_numeric($submit_time)){
		$submit_time = strtotime($submit_time);
	}
	return get_grid_job_x_rusage_total_rows($jobid, $indexid, $clusterid, $submit_time);
}

function get_rusage_records_host($jobid, $indexid, $clusterid, $submit_time, $host_name, $update_time) {
	if (read_config_option("grid_partitioning_enable") == "") {
		return db_fetch_assoc_prepared("SELECT *
			FROM grid_jobs_host_rusage
			WHERE (clusterid=?
			AND jobid=?
			AND indexid=?
			AND submit_time=?
			AND host=?
			AND update_time>?)
			ORDER BY update_time ASC", array($clusterid, $jobid, $indexid, $submit_time, $host_name, $update_time));
	} else {
		$query  = "";
		$sql_params = array();
		$tables = partition_get_partitions_for_query("grid_jobs_host_rusage", $update_time, date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach ($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ";
				}

				$select_list = build_partition_select_list($table, "grid_jobs_host_rusage");

				$query .= "SELECT $select_list
					FROM $table
					WHERE (clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?
					AND host=?
					AND update_time>?)";
				$sql_params[] = $clusterid;
				$sql_params[] = $jobid;
				$sql_params[] = $indexid;
				$sql_params[] = $submit_time;
				$sql_params[] = $host_name;
				$sql_params[] = $update_time;
			}

			$query .= " ORDER BY update_time ASC";

			return db_fetch_assoc_prepared($query, $sql_params);
		} else {
			return array();
		}
	}
}

function get_rusage_records_gpu($jobid, $indexid, $clusterid, $submit_time, $host_name, $gpu_id, $update_time) {
	if (read_config_option("grid_partitioning_enable") == "") {
		return db_fetch_assoc_prepared("SELECT *
			FROM grid_jobs_gpu_rusage
			WHERE (clusterid=?
			AND jobid=?
			AND indexid=?
			AND submit_time=?
			AND host=?
			AND gpu_id=?
			AND update_time >?)
			ORDER BY update_time ASC", array($clusterid, $jobid, $indexid, $submit_time, $host_name, $gpu_id, $update_time));
	}else{
		$query  = "";
		$sql_params = array();
		$tables = partition_get_partitions_for_query("grid_jobs_gpu_rusage", $update_time, date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ";
				}

				$select_list=build_partition_select_list($table, "grid_jobs_gpu_rusage");
				$query .= "SELECT $select_list
					FROM $table
					WHERE (clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?
					AND host=?
					AND gpu_id=?
					AND update_time>?)";
				$sql_params[] = $clusterid;
				$sql_params[] = $jobid;
				$sql_params[] = $indexid;
				$sql_params[] = $submit_time;
				$sql_params[] = $host_name;
				$sql_params[] = $gpu_id;
				$sql_params[] = $update_time;
			}

			$query .= " ORDER BY update_time ASC";

			return db_fetch_assoc_prepared($query, $sql_params);
		}else{
			return array();
		}
	}
}

/**
 * Get the latest GPU allocation info(hostname,  gpu id) for job.
 */
function get_rusage_records_gpu_per_job($clusterid, $jobid, $indexid, $submit_time, $mod_time, $end_time, $isrun) {
	global $config;
	include_once($config["library_path"] . '/rtm_functions.php');

	$update_time_field  = "MAX(update_time) AS update_time";
	$where_update_time  = "AND update_time = ";

	if ($isrun && read_config_option("grid_partitioning_enable") == "") {
        $query_update_time = "SELECT $update_time_field FROM grid_jobs_gpu_rusage
								WHERE (clusterid = ? AND jobid = ? AND indexid = ? AND submit_time = ? AND update_time >= ?)";
        $update_time = db_fetch_cell_prepared($query_update_time, array($clusterid, $jobid, $indexid, $submit_time, $mod_time));

        if(rtm_strlen($update_time)){
            $where_update_time .= '?';
            $query = "SELECT host, gpu_id, exec_time, energy, sm_ut_avg, mem_ut_avg, gpu_mused_max FROM grid_jobs_gpu_rusage
                WHERE (clusterid = ? AND jobid = ? AND indexid = ? AND submit_time = ? $where_update_time) ORDER BY host, gpu_id";

            $param_arr = array($clusterid, $jobid, $indexid, $submit_time, $update_time);

            return db_fetch_assoc_prepared($query, $param_arr);
		}
	}else{
		$query  = "";
		$tables = partition_get_partitions_for_query("grid_jobs_gpu_rusage", $mod_time, $end_time);

		if (cacti_sizeof($tables)) {
			$query_update_time = "";
			foreach ($tables as $table) {
				if (strlen($query_update_time)) {
					$query_update_time .= " UNION ";
				}
				$query_update_time .= "SELECT $update_time_field FROM $table
					WHERE (clusterid ='$clusterid' AND jobid = '$jobid' AND indexid = '$indexid' AND submit_time = '$submit_time' AND update_time >= '$mod_time')";
			}
			$update_time = db_fetch_cell_prepared("SELECT $update_time_field FROM ($query_update_time) AS rusage_record");

			if(rtm_strlen($update_time)){
				$where_update_time .= "'$update_time'";

				foreach($tables as $table) {
					if (strlen($query)) {
						$query .= " UNION ";
					}

					$query .= "SELECT host, gpu_id, exec_time, energy, sm_ut_avg, mem_ut_avg, gpu_mused_max FROM $table
						WHERE (clusterid ='$clusterid' AND jobid = '$jobid' AND indexid = '$indexid' AND submit_time = '$submit_time' $where_update_time)";
				}

				$query = "SELECT host, gpu_id, exec_time, energy, sm_ut_avg, mem_ut_avg, gpu_mused_max FROM ($query) AS rusage_record ORDER BY host, gpu_id";
				return db_fetch_assoc($query);
			}
		}
	}
	return array();
}


function trick64($number) {
	$string_val = trim(sprintf("%20.0f",$number));
	if (strlen($string_val)) {
		return $string_val;
	} else {
		return "U";
	}
}

function create_job_rrd_file($filename, $start_time, $data_class, $rrdtool_pipe) {
	$steps = read_config_option("poller_interval");

	if (!isset($steps)) {
		$steps = 300;
	}
	$heart_beat = $steps * 10;

	if ($data_class == "absolute") {
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:utime:GAUGE:" . $heart_beat . ":U:U" .
		" DS:stime:GAUGE:" . $heart_beat . ":U:U" .
		" DS:mem:GAUGE:" . $heart_beat . ":U:U" .
		" DS:swap:GAUGE:" . $heart_beat . ":U:U" .
		" DS:mem_reserved:GAUGE:" . $heart_beat . ":U:U" .
		" DS:npids:GAUGE:" . $heart_beat . ":U:U" .
		" DS:npgids:GAUGE:" . $heart_beat . ":U:U" .
		" DS:threads:GAUGE:" . $heart_beat . ":U:U" .
		" DS:num_cpus:GAUGE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	} else {
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:utime:DERIVE:" . $heart_beat . ":U:U" .
		" DS:stime:DERIVE:" . $heart_beat . ":U:U" .
		" DS:mem:DERIVE:" . $heart_beat . ":U:U" .
		" DS:swap:DERIVE:" . $heart_beat . ":U:U" .
		" DS:mem_reserved:DERIVE:" . $heart_beat . ":U:U" .
		" DS:npids:DERIVE:" . $heart_beat . ":U:U" .
		" DS:npgids:DERIVE:" . $heart_beat . ":U:U" .
		" DS:threads:DERIVE:" . $heart_beat . ":U:U" .
		" DS:num_cpus:DERIVE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	}

	rrdtool_execute($syntax, false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
}

function create_job_rrd_file_host($filename, $start_time, $data_class, $rrdtool_pipe) {
	$steps = read_config_option("poller_interval");

	if (!isset($steps)) {
		$steps = 300;
	}
	$heart_beat = $steps * 10;

	if ($data_class == "absolute") {
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:utime:GAUGE:" . $heart_beat . ":U:U" .
		" DS:stime:GAUGE:" . $heart_beat . ":U:U" .
		" DS:mem:GAUGE:" . $heart_beat . ":U:U" .
		" DS:swap:GAUGE:" . $heart_beat . ":U:U" .
		" DS:processes:GAUGE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	} else {
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:utime:DERIVE:" . $heart_beat . ":U:U" .
		" DS:stime:DERIVE:" . $heart_beat . ":U:U" .
		" DS:mem:DERIVE:" . $heart_beat . ":U:U" .
		" DS:swap:DERIVE:" . $heart_beat . ":U:U" .
		" DS:processes:DERIVE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	}

	rrdtool_execute($syntax, false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
}

function create_job_rrd_file_gpu($filename, $start_time, $data_class, $rrdtool_pipe) {
	$steps = read_config_option("poller_interval");

	if (!isset($steps)) {
		$steps = 300;
	}
	$heart_beat = $steps * 10;

	if ($data_class == "absolute") {
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:gut:GAUGE:" . $heart_beat . ":U:U" .
		" DS:gmut:GAUGE:" . $heart_beat . ":U:U" .
		" DS:gmem:GAUGE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	}else{
		$syntax = "create " .
		$filename .
		" --start " . strtotime($start_time) . " --step " . $steps .
		" DS:gut:DERIVE:" . $heart_beat . ":U:U" .
		" DS:gmut:DERIVE:" . $heart_beat . ":U:U" .
		" DS:gmem:DERIVE:" . $heart_beat . ":U:U" .
		" RRA:AVERAGE:0.5:1:8640" .
		" RRA:AVERAGE:0.5:6:8640" .
		" RRA:MAX:0.5:1:8640" .
		" RRA:MAX:0.5:6:8640" .
		" RRA:LAST:0.5:1:8640" .
		" RRA:LAST:0.5:6:8640";
	}

	rrdtool_execute($syntax, false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe, "GRID");
}

function create_job_graph($file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $graph_type, $date1, $date2,
							$legend, $rrdtool_pipe, $cache_directory_rrd, $host_name = null, $gpu_id = null) {
	/* we need this for pid variation */
	$interval = read_config_option("poller_interval");
	if (empty($interval)) {
		$interval = 300;
	}

	$filename        = $cache_directory_rrd . "/" . $file_base;
	$rrd_filename = str_replace(":", "\\:", $filename . ".rrd");

	$cache_directory = read_config_option("grid_cache_dir");
	$filename        = $cache_directory . "/" . $file_base;

	$png_filename = $filename . "_" . $graph_type . ".png";


	/* get the job record */
	$job = db_fetch_row_prepared("SELECT stat, submit_time, start_time, end_time, mem_requested, mem_reserved, max_swap
		FROM grid_jobs
		WHERE clusterid=?
		AND jobid=?
		AND indexid=?
		AND submit_time=?" , array($clusterid, $jobid, $indexid, date("Y-m-d H:i:s", $submit_time)));

	//TODO: $grid_hostinfo should directly get from grid_hostinfo when $host_name is not empry for host rusage graph.
	if (!cacti_sizeof($job)) {
		if (read_config_option("grid_partitioning_enable") == "") {
			$job = db_fetch_row_prepared("SELECT stat, submit_time, start_time, end_time, mem_requested, mem_reserved, max_swap
				FROM grid_jobs_finished
				WHERE clusterid=?
				AND jobid=?
				AND indexid=?
				AND submit_time=?" , array($clusterid, $jobid, $indexid, date("Y-m-d H:i:s", $submit_time)));

			$physical_mem = db_fetch_cell_prepared("SELECT maxMem FROM grid_hostinfo
				WHERE host IN (SELECT exec_host FROM grid_jobs_finished
				WHERE jobid=? AND indexid=?) AND clusterid=?", array($jobid, $indexid, $clusterid)) * 1024;

		}else{
			$query  = "";
			$tables = partition_get_partitions_for_query("grid_jobs_finished", date("Y-m-d H:i:s", $submit_time), date("Y-m-d H:i:s"));

			if (cacti_sizeof($tables)) {
				foreach($tables as $table) {
					if (strlen($query)) {
						$query .= " UNION ";
					}

					$query .= "SELECT stat, submit_time, start_time, end_time, mem_requested, mem_reserved, max_swap
						FROM $table
						WHERE clusterid='$clusterid'
						AND submit_time='" . date("Y-m-d H:i:s", $submit_time) . "'
						AND jobid='$jobid'
						AND indexid='$indexid'";
				}

				$job = db_fetch_row($query);

				foreach($tables as $table){
					$exec_host = '';
					$exec_host = db_fetch_cell_prepared("SELECT exec_host FROM $table
							WHERE clusterid=? AND jobid=? AND indexid=?", array($clusterid, $jobid, $indexid));
					if (isset($exec_host)){
						break;
					}
				}

				if (isset($exec_host)){
					$physical_mem = db_fetch_cell_prepared("SELECT maxMem FROM grid_hostinfo
						WHERE host IN (?) AND clusterid=?", array($exec_host, $clusterid)) * 1024;
				}
			}
		}
	}else{
		$physical_mem = db_fetch_cell_prepared("SELECT maxMem FROM grid_hostinfo
			WHERE host IN (SELECT exec_host FROM grid_jobs
			WHERE jobid=? AND indexid=?) AND clusterid=?", array($jobid, $indexid, $clusterid)) * 1024;
	}
	if(!empty($host_name) && strlen($_GET["gpu_id"]) > 0){
		//GPU Physical Memory unit is MB default.
		$physical_gmem = db_fetch_cell_prepared("SELECT totalValue FROM grid_hosts_resources WHERE clusterid=? AND host=? AND resource_name=?", array($clusterid, $host_name, "gpu_mtotal$gpu_id"));
		$physical_gmem_hrule = $physical_gmem * 1024 * 1024;
	}

	/* determine default start and end times (this will change) */
	$start_time  = $date1;
	$end_time    = $date2;

	/* get requested and reserved memory */
	if (isset($job["mem_requested"])) {
		$mem_request  = $job["mem_requested"];
	}else{
		$mem_request = 0;
	}

	if (isset($job["mem_reserved"])) {
		$mem_reserve = $job["mem_reserved"];
	}else{
		$mem_reserve = 0;
	}

	if(isset($job["max_swap"])) {
		$max_swap = $job["max_swap"];
	}else{
		$max_swap = 0;
	}

	$mem_reserve_hrule  = $mem_reserve * 1024;
	$physical_mem_hrule = $physical_mem * 1024;
	$max_swap           = $max_swap * 1024;

	/* set the rrdtool default font */
	if (read_config_option("path_rrdtool_default_font")) {
		putenv("RRD_DEFAULT_FONT=" . read_config_option("path_rrdtool_default_font"));
	}

	/* setup date format */
	$date_fmt = read_graph_config_option("default_date_format");
	$datechar = read_graph_config_option("default_datechar");

	if ($datechar == GDC_HYPHEN) {
		$datechar = "-";
	}else {
		$datechar = "/";
	}

	switch ($date_fmt) {
		case GD_MO_D_Y:
			$graph_date = "m" . $datechar . "d" . $datechar . "Y H:i:s";
			break;
		case GD_MN_D_Y:
			$graph_date = "M" . $datechar . "d" . $datechar . "Y H:i:s";
			break;
		case GD_D_MO_Y:
			$graph_date = "d" . $datechar . "m" . $datechar . "Y H:i:s";
			break;
		case GD_D_MN_Y:
			$graph_date = "d" . $datechar . "M" . $datechar . "Y H:i:s";
			break;
		case GD_Y_MO_D:
			$graph_date = "Y" . $datechar . "m" . $datechar . "d H:i:s";
			break;
		case GD_Y_MN_D:
			$graph_date = "Y" . $datechar . "M" . $datechar . "d H:i:s";
			break;
	}

	/* display the timespan for zoomed graphs */
	$graph_legend = "";
	$graph_opts   = "";
	$rrdtool_version=read_config_option("rrdtool_version");
	if ((isset($start_time)) && (isset($end_time))) {
		if (($start_time < 0) && ($end_time < 0)) {
			if ($rrdtool_version != "rrd-1.0.x") {
				$graph_legend .= "COMMENT:\"From " . str_replace(":", "\:", date($graph_date, time()+$start_time)) . " To " . str_replace(":", "\:", date($graph_date, time()+$end_time)) . "\\c\"" . RRD_NL . "COMMENT:\"  \\n\"" . RRD_NL;
			}else {
				$graph_legend .= "COMMENT:\"From " . date($graph_date, time()+$start_time) . " To " . date($graph_date, time()+$end_time) . "\\c\"" . RRD_NL . "COMMENT:\"  \\n\"" . RRD_NL;
			}
		}else if (($start_time >= 0) && ($end_time >= 0)) {
			if ($rrdtool_version != "rrd-1.0.x") {
				$graph_legend .= "COMMENT:\"From " . str_replace(":", "\:", date($graph_date, $start_time)) . " To " . str_replace(":", "\:", date($graph_date, $end_time)) . "\\c\"" . RRD_NL . "COMMENT:\"  \\n\"" . RRD_NL;
			}else {
				$graph_legend .= "COMMENT:\"From " . date($graph_date, $start_time) . " To " . date($graph_date, $end_time) . "\\c\"" . RRD_NL . "COMMENT:\"  \\n\"" . RRD_NL;
			}
		}
	}

	/* rrdtool 1.2 font options */
	if ($rrdtool_version == "rrd-1.2.x") {
		/* title fonts */
		if (file_exists(read_graph_config_option("title_font"))) {
			$title_size = read_graph_config_option("title_size");
			if (!empty($title_size)) {
				if ($title_size <= 0) {
					$title_size = 10;
				}
			}elseif (($title_size <= 0) || ($title_size == "")) {
				$title_size = 10;
			}

			$graph_opts .= "--font TITLE:" . $title_size . ":" . read_graph_config_option("title_font") . RRD_NL;
		}elseif (file_exists(read_config_option("title_font"))) {
			$title_size = read_config_option("title_size");
			if (!empty($title_size)) {
				if ($title_size <= 0) {
					$title_size = 10;
				}
			}elseif (($title_size <= 0) || ($title_size == "")) {
				$title_size = 10;
			}

			$graph_opts .= "--font TITLE:" . $title_size . ":" . read_config_option("title_font") . RRD_NL;
		}

		/* axis fonts */
		if (file_exists(read_graph_config_option("axis_font"))) {
			$graph_opts .= "--font AXIS:" . read_graph_config_option("axis_size") . ":" . read_graph_config_option("axis_font") . RRD_NL;
		}elseif (file_exists(read_config_option("axis_font"))) {
			$graph_opts .= "--font AXIS:" . read_config_option("axis_size") . ":" . read_config_option("axis_font") . RRD_NL;
		}

		/* legend fonts */
		$graph_width = read_grid_config_option("job_graph_width");

		if ($graph_width < 400) {
			$nl = "\\n";
		}else{
			$nl = "";
		}

		if (file_exists(read_graph_config_option("legend_font"))) {
			$graph_opts .= "--font LEGEND:" . round(read_config_option("legend_size")+(($graph_width-400)/$graph_width),1) . ":" . read_graph_config_option("legend_font") . RRD_NL;
		}elseif (file_exists(read_config_option("legend_font"))) {
			$graph_opts .= "--font LEGEND:" . round(read_config_option("legend_size")+(($graph_width-400)/$graph_width),1) . ":" . read_config_option("legend_font") . RRD_NL;
		}

		/* unit fonts */
		if (file_exists(read_graph_config_option("unit_font"))) {
			$graph_opts .= "--font UNIT:" . read_graph_config_option("unit_size") . ":" . read_graph_config_option("unit_font") . RRD_NL;
		}elseif (file_exists(read_config_option("unit_font"))) {
			$graph_opts .= "--font UNIT:" . read_config_option("unit_size") . ":" . read_config_option("unit_font") . RRD_NL;
		}
	}

	switch ($graph_type) {
	case "memory" :
		if ($data_class == "absolute") {
			$title = "Memory Consumption for Job";
			if(!empty($host_name)){
				$title .= " - ". $host_name;
			}
			$stack = " " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefe#" . db_fetch_cell_prepared("select hex from colors where id=?", array(read_grid_config_option("job_graph_swap_color"))) . ":\"VM Size \"";
			$limit = " --lower-limit=0";
			usleep(50000);//wait rrd updated over. otherwize rrdtool xport return NaN in xml in grid_get_rrdcfvalue().
			$max_mem_yaxis = grid_get_rrdcfvalue("mem", "MAX", $rrd_filename, $start_time, $end_time);
			if(empty($max_mem_yaxis) || $max_mem_yaxis == "U")
				$max_mem_yaxis = 0;
			$max_mem_yaxis *= 1024;

			$max_swap_yaxis = grid_get_rrdcfvalue("swap", "MAX", $rrd_filename, $start_time, $end_time);
			if(empty($max_swap_yaxis) || $max_swap_yaxis == "U")
				$max_swap_yaxis = 0;
			$max_swap_yaxis *= 1024;

			if(empty($host_name)){
				$mem_reserve_hrule2 = grid_get_rrdcfvalue("mem_reserved", "MAX", $rrd_filename, max($submit_time,$start_time), min(time(),$end_time));
				if(empty($mem_reserve_hrule2) || $mem_reserve_hrule2 == "U")
					$mem_reserve_hrule2 = 0;
				$mem_reserve_hrule2 *= 1024;
				$mem_reserve_hrule = max($mem_reserve_hrule, $mem_reserve_hrule2);
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --upper-limit=" . max($max_mem_yaxis, $max_swap_yaxis, $max_swap, $mem_reserve_hrule)*1.1 .
				$limit .
				" --vertical-label=\"memory bytes\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend:"") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":mem:LAST" .
				" DEF:b=\"" . $rrd_filename . "\":swap:LAST" .
				(empty($host_name)? " DEF:c=\"" . $rrd_filename . "\":mem_reserved:LAST" : "") .
				" CDEF:cdefa=a,1024,*" .
				" CDEF:cdefe=b,1024,*" .
				(empty($host_name)? " CDEF:cdeff=c,1024,*" : "") .
				$stack .
				($legend == "true" ? " GPRINT:cdefe:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefe:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefe:MAX:\"Max\:%8.2lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_mem_color"))) . ":\"Physical\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%8.2lf %s\\n\"": "");
			if(isset($mem_reserve_hrule) && $mem_reserve_hrule > 0 && empty($host_name)){
				$syntax .= " LINE2:cdeff#412381:\"Reserved\"" .
				($legend == "true" ? " GPRINT:cdeff:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdeff:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdeff:MAX:\"Max\:%8.2lf %s\\n\"": "");
				if($job['stat']!='PEND'){
					$syntax .= " HRULE:" . ($physical_mem_hrule) . "#00BF47:Physical_Mem\:" . display_job_memory($physical_mem, 2);
				}
			}
		}else{
			$title = "Memory Variation for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			$stack = " " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") .":cdefe#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_swap_color"))) . ":\"VM Size \"";
			$limit = "";

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				$limit .
				" --vertical-label=\"memory bytes\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":mem:LAST" .
				" DEF:b=\"" . $rrd_filename . "\":swap:LAST" .
				" CDEF:cdefa=a,1024,*," . $interval . ",*" .
				" CDEF:cdefe=b,1024,*," . $interval . ",*" .
				$stack .
				($legend == "true" ? " GPRINT:cdefe:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefe:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefe:MAX:\"Max\:%8.2lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_mem_color"))) . ":\"Physical\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}

		break;
	case "cpu" :
		if ($data_class == "absolute") {
			$title = "CPU Time for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			$stack = " " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":b#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_utime_color"))) . ":\"User Time  \":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"");

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --lower-limit=0" .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"cpu seconds\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":stime:LAST" .
				" DEF:b=\"" . $rrd_filename . "\":utime:LAST" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":a#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_stime_color"))) . ":\"System Time\"" .
				($legend == "true" ? " GPRINT:a:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:MAX:\"Max\:%8.2lf %s\\n\"": "") .
				$stack .
				($legend == "true" ? " GPRINT:b:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:b:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:b:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}else{
			$title = "CPU Time Variation for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			$stack = " " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefb#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_utime_color"))) . ":\"User Time  \":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"");

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"cpu seconds\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":stime:LAST" .
				" DEF:b=\"" . $rrd_filename . "\":utime:LAST" .
				" CDEF:cdefa=a," . $interval . ",*" .
				" CDEF:cdefb=b," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_stime_color"))) . ":\"System Time\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%8.2lf %s\\n\"": "") .
				$stack .
				($legend == "true" ? " GPRINT:cdefb:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefb:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefb:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}

		break;
	case "pids" :
		if ($data_class == "absolute") {
			$title = "Running PID's for Job";

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --lower-limit 0" .
				" --vertical-label=\"active pids\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":npids:LAST" .
				" DEF:b=\"" . $rrd_filename . "\":npgids:LAST" .
				" DEF:c=\"" . $rrd_filename . "\":threads:LAST" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":a#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_pids_color"))) . ":\"PIDS   \"" .
				($legend == "true" ? " GPRINT:a:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:MAX:\"Max\:%4.0lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":b#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_pgids_color"))) . ":\"PGIDS  \":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"") .
				($legend == "true" ? " GPRINT:b:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:b:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:b:MAX:\"Max\:%4.0lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":c#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_threads_color"))) . ":\"Threads\":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"") .
				($legend == "true" ? " GPRINT:c:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:c:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:c:MAX:\"Max\:%4.0lf %s\\n\"": "");
		}else{
			$title = "PID Variation for Job";
			$stack = " LINE1:cdefe#00FF00:\"PID Groups\"";

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"active pids\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":npids:AVERAGE" .
				" DEF:b=\"" . $rrd_filename . "\":npgids:AVERAGE" .
				" DEF:c=\"" . $rrd_filename . "\":threads:AVERAGE" .
				" CDEF:cdefa=a," . $interval . ",*" .
				" CDEF:cdefb=b," . $interval . ",*" .
				" CDEF:cdefc=c," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_pids_color"))) . ":\"PIDS   \"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%4.0lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefb#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_pgids_color"))) . ":\"PGIDS  \":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"") .
				($legend == "true" ? " GPRINT:cdefb:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefb:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefb:MAX:\"Max\:%4.0lf %s\\n\"": "") .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefc#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_threads_color"))) . ":\"Threads\":" . (read_grid_config_option("job_graph_type") == 1 ? "STACK":"") .
				($legend == "true" ? " GPRINT:cdefc:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefc:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefc:MAX:\"Max\:%4.0lf %s\\n\"": "");
		}

		break;
	case "slots" :
		if ($data_class == "absolute") {
			$title = "Slots for Job";
			if(empty($host_name)){
				$theDEF=" DEF:a=\"" . $rrd_filename . "\":num_cpus:LAST";
			}else{
				$title= $title . " - ". $host_name;
				$theDEF=" DEF:a=\"" . $rrd_filename . "\":processes:LAST";
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --lower-limit=0" .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"slots\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" " . $theDEF .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":a#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_slots_color"))) . ":\"Slots\"" .
				($legend == "true" ? " GPRINT:a:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:MAX:\"Max\:%8.2lf %s\\n\"": "") ;
		}else{
			$title = "Slots Variation for Job";
			if(empty($host_name)){
				$theDEF=" DEF:a=\"" . $rrd_filename . "\":num_cpus:AVERAGE";
			}else{
				$title= $title . " - ". $host_name;
				$theDEF=" DEF:a=\"" . $rrd_filename . "\":processes:AVERAGE";
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"slots\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" " . $theDEF .
				" CDEF:cdefa=a," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_slots_color"))) . ":\"Slot\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%4.0lf %s\\n\"": "") ;
		}

		break;
	case "gut" :
		if ($data_class == "absolute") {
			$title = "GPU Utilization for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}
			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --lower-limit 0" .
				" --vertical-label=\"GPU  Utilization %\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gut:LAST" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":a#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gut_color"))) . ":\"GPU UT   \"" .
				($legend == "true" ? " GPRINT:a:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}else{
			$title = "GPU UT Variation for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"GPU  Utilization %\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gut:AVERAGE" .
				" CDEF:cdefa=a," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gut_color"))) . ":\"GPU UT   \"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%4.0lf %s\\n\"": "");
		}
		break;
	case "gmut" :
		if ($data_class == "absolute") {
			$title = "GPU Memory Utilization for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}
			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --lower-limit 0" .
				" --vertical-label=\"GPU Memory UT %\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gmut:LAST" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":a#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gmut_color"))) . ":\"GPU Memory UT   \"" .
				($legend == "true" ? " GPRINT:a:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:a:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}else{
			$title = "GPU Memory UT Variation for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"GPU Memory UT %\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gmut:AVERAGE" .
				" CDEF:cdefa=a," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gmut_color"))) . ":\"GPU Memory UT   \"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%4.0lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%4.0lf %s\\n\"": "");
		}
		break;
	case "gmemory" :
		if ($data_class == "absolute") {
			$title = "GPU Memory Consumption for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}

			usleep(50000);//wait rrd updated over. otherwize rrdtool xport return NaN in xml in grid_get_rrdcfvalue().
			$max_gmem_yaxis = grid_get_rrdcfvalue("gmem", "MAX", $rrd_filename, $start_time, $end_time);
			if(empty($max_gmem_yaxis) || $max_gmem_yaxis == "U")
				$max_gmem_yaxis = 0;
			//Convert MBytes to Bytes: 1048576, but GPU Max Mem Used unit is byte
			$max_gmem_yaxis *= 1;

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --upper-limit=" . max($max_gmem_yaxis, $physical_gmem_hrule)*1.1 .
				" --lower-limit=0" .
				" --vertical-label=\"memory bytes\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend:"") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gmem:LAST" .
				" CDEF:cdefa=a,1,*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gmem_color"))) . ":\"Physical\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%8.2lf %s\\n\"": "");
				if($job['stat']!='PEND'){
					$syntax .= " HRULE:" . ($physical_gmem_hrule) . "#00BF47:Physical_Mem\:" . display_job_memory($physical_gmem_hrule/1024, 2);
				}
		}else{
			$title = "GPU Memory Variation for Job";
			if(!empty($host_name)){
				$title= $title . " - ". $host_name;
			}
			if(strlen($_GET["gpu_id"]) > 0){
				$title= $title . " - GPU#". $gpu_id;
			}

			$syntax = " graph " . $png_filename .
				" --imgformat=PNG" .
				" --start=" . $start_time .
				" --end=" . $end_time .
				" --title=\"" . $title . "\"" .
				" --rigid" .
				" --base=1024" .
				" --height=" . read_grid_config_option("job_graph_height") .
				" --width=" . read_grid_config_option("job_graph_width") .
				" --alt-autoscale-max" .
				($legend != "true" ? " --no-legend":"") .
				" --vertical-label=\"memory bytes\"" .
				" --slope-mode" .
				" " . ($legend == "true" ? $graph_legend: "") .
				" " . $graph_opts .
				" DEF:a=\"" . $rrd_filename . "\":gmem:LAST" .
				" CDEF:cdefa=a,1,*," . $interval . ",*" .
				" " . (read_grid_config_option("job_graph_type") == 1 ? "AREA":"LINE2") . ":cdefa#" . db_fetch_cell_prepared("select hex from colors where id=?", array( read_grid_config_option("job_graph_gmem_color"))) . ":\"Physical\"" .
				($legend == "true" ? " GPRINT:cdefa:LAST:\"Last\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:AVERAGE:\"Avg\:%8.2lf %s\"": "") .
				($legend == "true" ? " GPRINT:cdefa:MAX:\"Max\:%8.2lf %s\\n\"": "");
		}

	}

	//cacti_log("DEBUG: rrdgraph syntax: '" . str_replace("\n", " ", $syntax));
	rrdtool_execute($syntax, false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe);

	/* close the rrdtool pipe */
	rrd_close($rrdtool_pipe);

	return $png_filename;
}

/**
 * ! option.grid_archive_rrd_location had been deprecated. Leave related code for further performance tuning. e.g. rra/png cache
 * ! Keep grid_jobs_rusage code only. host/gpu level should be removed to improve code readability now.
 * @param int $clusterid
 * @param int $jobid
 * @param int $indexid
 * @param timestamp $submit_time
 * @param string $data_class
 * @param string $graph_type
 * @param string $host_name
 * @param int $gpu_id
 * @return rra file path if option.grid_archive_rrd_location is configured
 */
function find_archive_jobgraph_rrdfile($clusterid, $jobid, $indexid, $submit_time, $data_class, $graph_type, $host_name = null, $gpu_id = null) {
	global $config;

	/* location of archive RRDfiles */
	$archive_base_directory = read_config_option("grid_archive_rrd_location");
	$archive_directory = $archive_base_directory . "/" .
		date("Y", $submit_time) . "/" .
		date("m", $submit_time) . "/" .
		date("d", $submit_time);

	/* all files have this naming convention */
	$file_name = $jobid . "_" . $indexid . "_" . $clusterid . "_" . $submit_time . "_" . $data_class;
	if($host_name != null){
		$file_name .= "_" . $host_name;
	}
	if($gpu_id != null){
		$file_name .= "_" . $gpu_id;
	}
	$file_name .= ".rrd";

	if (@is_file($archive_directory . "/" . $file_name)) {
		return $archive_directory . "/" . $file_name;
	}else{
		return "";
	}
}

function grid_maint_update_job_rrds($clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe, $host_name = null, $gpu_id = null) {
	global $config;

	$active_cache_directory  = read_config_option("grid_cache_dir");
	$archive_cache_directory = read_config_option("grid_archive_rrd_location");

	/* exit if cache directory is not set */
	if (!strlen($archive_cache_directory)) {
		cacti_log("ERROR: Can not generate Archive RRD Files, Grid RRD Archive Directory not set.");
		return;
	}

	$archive_directory = $archive_cache_directory . "/" .
		date("Y", $submit_time) . "/" .
		date("m", $submit_time) . "/" .
		date("d", $submit_time);

	$file_base = $jobid . "_" . $indexid . "_" . $clusterid . "_" . $submit_time . "_" . $data_class;
	if ($host_name !=null){
		$file_base .= "_" . $host_name;
	}
	if ($gpu_id !=null){
		$file_base .= "_" . $gpu_id;
	}

	if (strlen($archive_directory)) {
		if (!is_dir($archive_directory)) {
			mkdir($archive_directory, 0774, true);
		}

		/* move existing rrdfiles */
		$types = array("memory", "cpu", "pids", "gut", "gmut", "gmemory");
		foreach($types as $type) {
			if (is_file($active_cache_directory . "/" . $file_base . "_" . $type . ".rrd")) {
				rename($active_cache_directory . "/" . $file_base . "_" . $type . ".rrd", $archive_directory . "/" . $file_base . "_" . $type . ".rrd");
			}
		}
		if (is_file($active_cache_directory . "/" . $file_base . ".rrd")) {
			rename($active_cache_directory . "/" . $file_base . ".rrd", $archive_directory . "/" . $file_base . ".rrd");
		}

		/* update the rrdfiles */
		if (is_dir($archive_directory)) {
			if($gpu_id != null){
				$recreate_png = update_job_rrds_gpu("grid_jobs_finished", $archive_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe, $host_name, $gpu_id);
			}else if($host_name != null){
				$recreate_png = update_job_rrds_host("grid_jobs_finished", $archive_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe, $host_name);
			}else{
				$recreate_png = update_job_rrds("grid_jobs_finished", $archive_directory, $file_base, $clusterid, $jobid, $indexid, $submit_time, $data_class, $rrdtool_pipe);
			}
		}else{
			cacti_log("ERROR: Can not generate RRD Files, Grid RRD Archive Directory '" . $archive_directory . "' does not exist");
		}
	}
}
/**
 * Undocumented function
 * @param array $job include clusterid, jobid, indexid, submit_time
 * @param [type] $start_time
 * @param [type] $end_time
 * @param [type] $rrd_interval
 * @param string $data_class one of 'relative' and 'absolute'
 * @param string $graph_type one of '', '', '', '', '', ''
 * @param [type] $legend
 * @param [type] $cluster_tz
 * @param string $host_name
 * @param int $gpu_id
 * @return job graph image element html code
 */
function get_jobgraph_imgele($job, $start_time, $end_time, $rrd_interval,
							$data_class, $graph_type, $legend, $cluster_tz, $host_name = null, $gpu_id = null) {
	static $i = 0;

	$img_base_url = '../grid/grid_jobgraph.php?'
					. 'clusterid=' . $job['clusterid']
					. '&jobid=' . $job['jobid']
					. '&indexid=' . $job['indexid']
					. '&submit_time=' . strtotime($job['submit_time'])
					. '&date1=' . $start_time
					. '&date2=' . ($end_time+$rrd_interval)
					. '&data_class=' . $data_class
					. '&graph_type=' . $graph_type
					. '&legend=' . $legend
					. '&cluster_tz=' . $cluster_tz;
	if($host_name != null){
		$img_base_url .= '&host=' . $host_name;
	}
	if($gpu_id != null){
		$img_base_url .= '&gpu_id=' . $gpu_id;
	}
	$alt_str = ucfirst($data_class);
	switch(strtolower($graph_type)){
		case "cpu":
			$alt_str .= " " . strtoupper($graph_type);
			break;
		case "gut":
			$alt_str .= " GPU UT";
			break;
		case "gmut":
			$alt_str .= " GPU Memory UT";
			break;
		case "pids":
			$alt_str .= " PIDs";
			break;
		case "gmemory":
			$alt_str .= " GPU Memory";
			break;
		default:
			//"memory", "slots"
			$alt_str .= " " . ucfirst($graph_type);
			break;
	}
	$alt_str .= " Stats";
	$graph_width  = read_grid_config_option('job_graph_width');
	$graph_height = read_grid_config_option('job_graph_height');

	return "<div class='graphWrapper' style='width:100%;' id='wrapper_$i' graph_height='$graph_height' graph_width='$graph_width'><img graph_width='$graph_width' image_width='$graph_width' canvas_width='$graph_width' graph_height='$graph_height' image_height='$graph_height' canvas_height='$graph_height' graph_top='0' graph_left='0' id='graph_" . $i . "'  class='graphimage' src='" . html_escape($img_base_url) . "' title='" . $alt_str . "'></div>";
	$i++;
}

function apply_grid_summary_tholds($force = false) {
	/* get summary rows */
	$rows = db_fetch_assoc('SELECT * FROM grid_summary');

	$update_sql  = '';
	$update_sql2 = array();

	$start = microtime(true);

	if (cacti_sizeof($rows)) {
		foreach ($rows as $row) {
			/* unavailable host */
			if (($row['cacti_status'] == '1') ||
				($row['load_status'] == 'Unavail') ||
				($row['load_status'] == 'SBD-Down') ||
				(($row['load_status'] == 'RES-Down') && read_config_option('grid_thold_resdown_status') == 1) ||
				($row['load_status'] == 'Unlicensed') ||
				($row['bhost_status'] == 'Unavail') ||
				($row['bhost_status'] == 'Closed-LIM') ||
				($row['bhost_status'] == 'Unlicensed') ||
				(($row['bhost_status'] == 'Closed-RES') && read_config_option('grid_thold_resdown_status') == 1) ||
				($row['bhost_status'] == 'Unreach')) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_UNAVAIL, $force);
				continue;
			}

			/* correct for possible divide by zero issues */
			if (($row['maxMem'] == 0) || ($row['maxMem'] == '-')) {
				$ured = false;
			} elseif ($row['mem'] == 0) {
				$ured = false;
			} else {
				if ((($row['mem'] / $row['maxMem'])) < (read_config_option('grid_thold_ured_pmem')/100)) {
					$ured = true;
				} else {
					$ured = false;
				}
			}

			if ($ured == false) {
				if (($row['maxSwap'] == 0) || ($row['maxSwap'] == '-')) {
					$ured = false;
				} elseif ($row['swp'] == 0) {
					$ured = false;
				} else {
					if ((($row['swp'] / $row['maxSwap'])) < (read_config_option('grid_thold_ured_swap')/100)) {
						$ured = true;
					} else {
						$ured = false;
					}
				}
			}

			if ($ured == false) {
				if (($row['maxTmp'] == 0) || ($row['maxTmp'] == '-')) {
					$ured = false;
				} elseif ($row['tmp'] == 0) {
					$ured = false;
				} else {
					if ((($row['tmp'] / $row['maxTmp'])) < (read_config_option('grid_thold_ured_tmp')/100)) {
						$ured = true;
					} else {
						$ured = false;
					}
				}
			}

			if ($ured == false) {
				if (($row['maxCpus'] == 0) || ($row['maxCpus'] == '-')) {
					$ured = false;
				} elseif ($row['r1m'] == 0) {
					$ured = false;
				} else {
					if ((($row['r1m'] / $row['maxCpus'])) >= (read_config_option('grid_thold_ured_r1m')/100)) {
						$ured = true;
					} else {
						$ured = false;
					}
				}
			}

			/* closed by Administrator */
			if (substr_count($row['bhost_status'], 'Closed-Admin')) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_ADMINDOWN, $force);
				continue;
			}

			if (($ured) ||
				($row['pg'] > read_config_option('grid_thold_ured_pg')) ||
				($row['io'] > read_config_option('grid_thold_ured_io'))) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_LOWRES, $force);
				continue;
			}

			/* closed but idle host */
			if ((substr_count($row['bhost_status'], 'Closed-Full')) &&
				($row['ut'] < (read_config_option('grid_thold_cblue_cpu')/100))) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_IDLECLOSE, $force);
				continue;
			}

			/* closed busy host */
			if (($row['cacti_status'] == '1') ||
				($row['bhost_status'] != 'Ok')) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_BUSYCLOSE, $force);
				continue;
			}

			$uyellow = false;
			if ($uyellow == false) {
				if (($row['maxCpus'] == 0) || ($row['maxCpus'] == '-')) {
					$uyellow = false;
				} elseif ($row['r1m'] == 0) {
					$uyellow = false;
				} else {
					if ((($row['r1m'] / $row['maxCpus'])) >= (read_config_option('grid_thold_yellow_r1m')/100)) {
						$uyellow = true;
					} else {
						$uyellow = false;
					}
				}
			}

			/* busy host */
			if (($uyellow) ||
				($row['ut'] > (read_config_option('grid_thold_yellow_cpu')/100)) ||
				($row['io'] > read_config_option('grid_thold_yellow_io')) ||
				($row['pg'] > read_config_option('grid_thold_yellow_pg')) ||
				($row['load_status'] == 'Busy')) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_BUSY, $force);
				continue;
			}

			/* idle host with jobs */
			if ((($row['maxJobs'] == $row['numRun']) && (read_config_option('grid_thold_blue_slots') == '100')) &&
				($row['ut'] < (read_config_option('grid_thold_blue_cpu')/100))) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_IDLEWJOBS, $force);
				continue;
			}

			/* idle host with jobs */
			if ($row['maxJobs'] > 0) {
				if ((($row['numRun']/$row['maxJobs']) >= (read_config_option('grid_thold_blue_slots')/100)) &&
					($row['ut'] <= (read_config_option('grid_thold_blue_cpu')/100))) {
					update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_IDLEWJOBS, $force);
					continue;
				}
			}

			/* starved */
			$now = time();
			if (($row['numRun'] == 0) && ((($now - strtotime($row['job_last_started'])) > (read_config_option('grid_thold_grey_duration') * 60)) &&
				(date('Y', strtotime($row['job_last_started'])) > '2000')) &&
				((($now - strtotime($row['job_last_ended'])) > (read_config_option('grid_thold_grey_duration') * 60)) &&
				(date('Y', strtotime($row['job_last_ended'])) > '2000')) &&
				((($now - strtotime($row['job_last_exited'])) > (read_config_option('grid_thold_grey_duration') * 60)) &&
				(date('Y', strtotime($row['job_last_exited'])) > '2000'))) {
				update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_STARVED, $force);
				continue;
			}

			update_grid_summary_row_status($update_sql, $update_sql2, $row, GRID_IDLE, $force);
		}
	}

	if (strlen($update_sql) > 0) {
		db_execute($update_sql);
	}

	if (cacti_sizeof($update_sql2)) {
		$new_array = array_chunk($update_sql2, 4000);
		if (cacti_sizeof($new_array)) {
			foreach ($new_array as $anarray) {
				db_execute('INSERT INTO grid_summary
					(clusterid, host, summary_status)
					VALUES ' . implode(',', $anarray) . '
					ON DUPLICATE KEY UPDATE summary_status=VALUES(summary_status)');
			}
		}
	}

	$end = microtime(true);

	cacti_log('HOST DASHBOARD STATS: Time:' . round($end-$start,2) . ' Hosts:' . cacti_sizeof($rows), false, 'SYSTEM');
}

function update_grid_summary_row_status(&$update_sql, &$update_sql2, $row, $status, $force) {
	static $lines = 0;

	$update_sql2[] = '(' . $row['clusterid'] . ", '" . $row['host'] . "'," . $status . ')';

	if ($force) {
		$poller_interval = read_config_option('poller_interval');
		if (empty($poller_interval)) {
			$poller_interval = 300;
		}

		$last_value = db_fetch_row_prepared('SELECT * FROM grid_summary_timeinstate
			WHERE clusterid=?
			AND host=?', array($row['clusterid'], db_qstr($row['host'])));

		if (!cacti_sizeof($last_value)) {
			$last_value['clusterid'] = $row['clusterid'];
			$last_value['host']      = $row['host'];
			$last_value['unavail']   = 0;
			$last_value['busyclose'] = 0;
			$last_value['idleclose'] = 0;
			$last_value['lowres']    = 0;
			$last_value['busy']      = 0;
			$last_value['idlewjobs'] = 0;
			$last_value['idle']      = 0;
			$last_value['starved']   = 0;
			$last_value['admindown'] = 0;
			$last_value['blackhole'] = 0;
		}

		switch ($status) {
		case GRID_UNAVAIL:
			$last_value['unavail'] += $poller_interval;
			break;
		case GRID_LOWRES:
			$last_value['lowres'] += $poller_interval;
			break;
		case GRID_STARVED:
			$last_value['starved'] += $poller_interval;
			break;
		case GRID_IDLECLOSE:
			$last_value['idleclose'] += $poller_interval;
			break;
		case GRID_IDLEWJOBS:
			$last_value['idlewjobs'] += $poller_interval;
			break;
		case GRID_BUSYCLOSE:
			$last_value['busyclose'] += $poller_interval;
			break;
		case GRID_BUSY:
			$last_value['busy'] += $poller_interval;
			break;
		case GRID_IDLE:
			$last_value['idle'] += $poller_interval;
			break;
		case GRID_ADMINDOWN:
			$last_value['admindown'] += $poller_interval;
			break;
		case GRID_BLACKHOLE:
			$last_value['blackhole'] += $poller_interval;
			break;
		}

		if ($lines == 0) {
			$update_sql = 'REPLACE INTO grid_summary_timeinstate
				(clusterid, host, unavail, busyclose, idleclose, lowres, busy, idlewjobs, idle, starved, admindown, blackhole)
				VALUES (' .
				$last_value['clusterid'] . ", '" .
				$last_value['host']      . "', " .
				$last_value['unavail']   . ', ' .
				$last_value['busyclose'] . ', ' .
				$last_value['idleclose'] . ', ' .
				$last_value['lowres']    . ', ' .
				$last_value['busy']      . ', ' .
				$last_value['idlewjobs'] . ', ' .
				$last_value['idle']      . ', ' .
				$last_value['starved']   . ', ' .
				$last_value['admindown'] . ', ' .
				$last_value['blackhole'] . ')';
		} else {
			$update_sql .= ',(' .
				$last_value['clusterid'] . ", '" .
				$last_value['host']      . "', " .
				$last_value['unavail']   . ', ' .
				$last_value['busyclose'] . ', ' .
				$last_value['idleclose'] . ', ' .
				$last_value['lowres']    . ', ' .
				$last_value['busy']      . ', ' .
				$last_value['idlewjobs'] . ', ' .
				$last_value['idle']      . ', ' .
				$last_value['starved']   . ', ' .
				$last_value['admindown'] . ', ' .
				$last_value['blackhole'] . ')';
		}

		/* write string after 640k */
		if ($lines >= 1000) {
			db_execute($update_sql);
			$update_sql = '';
			$lines = 0;
		} else {
			$lines++;
		}
	}
}

function update_grid_summary_table($force, &$replace_time, &$thold_time) {
	global $config, $debug;
	include_once($config["library_path"] . '/rtm_functions.php');

	grid_debug('Updating Summary Table');

	$last_update = read_config_option('grid_summary_update_time', true);
	$update_frequency = 60;

	if (($last_update + $update_frequency < time()) ||
		($force) ||
		(empty($last_update))) {

		/* check for update lock */
		grid_debug('Attempting Table Lock');
		$table_locked = detect_and_correct_running_processes(0, 'SUMMARY_UPDATE', '300', true);
		grid_debug('Completed Table Lock');

		if ($table_locked) {
			/* set the present bit to 0 for deletion */
			db_execute('UPDATE grid_summary SET present=0 WHERE present=1');

			$replace_sql = "INSERT INTO grid_summary (clusterid, clustername, host,
				load_status, r15s, r1m, r15m, ut, pg, io,ls, it, tmp, swp, mem,
				hStatus, hCtrlMsg, bhost_status, cpuFactor, windows, userJobLimit,
				maxJobs, numJobs, numRun, numSSUSP, numUSUSP, numRESERVE,
				hostType, hostModel, maxCpus, maxMem, maxSwap, maxTmp, nDisks,
				isServer, licensed, rexPriority, licFeaturesNeeded, first_seen,
				last_seen, job_last_started, job_last_ended, job_last_suspended,
				job_last_exited, date_recorded, cacti_hostid, monitor, disabled, cacti_status,
				status_rec_date, status_fail_date, min_time,
				max_time, cur_time, avg_time, availability, present)
				SELECT
				grid_load.clusterid,
				grid_clusters.clustername,
				grid_load.host,
				grid_load.status AS load_status,
				grid_load.r15s,
				grid_load.r1m,
				grid_load.r15m,
				grid_load.ut,
				grid_load.pg,
				grid_load.io,
				grid_load.ls,
				grid_load.it,
				grid_load.tmp,
				grid_load.swp,
				grid_load.mem,
				grid_hosts.hStatus,
				grid_hosts.hCtrlMsg,
				grid_hosts.status AS bhost_status,
				grid_hosts.cpuFactor,
				grid_hosts.windows,
				grid_hosts.userJobLimit,
				grid_hosts.maxJobs,
				grid_hosts.numJobs,
				grid_hosts.numRun,
				grid_hosts.numSSUSP,
				grid_hosts.numUSUSP,
				grid_hosts.numRESERVE,
				grid_hostinfo.hostType,
				grid_hostinfo.hostModel,
				grid_hostinfo.maxCpus,
				grid_hostinfo.maxMem,
				grid_hostinfo.maxSwap,
				grid_hostinfo.maxTmp,
				grid_hostinfo.nDisks,
				grid_hostinfo.isServer,
				grid_hostinfo.licensed,
				grid_hostinfo.rexPriority,
				grid_hostinfo.licFeaturesNeeded,
				grid_hostinfo.first_seen,
				grid_hostinfo.last_seen,
				grid_hosts_jobtraffic.job_last_started,
				grid_hosts_jobtraffic.job_last_ended,
				grid_hosts_jobtraffic.job_last_suspended,
				grid_hosts_jobtraffic.job_last_exited,
				grid_hosts_jobtraffic.date_recorded,
				host.id AS cacti_hostid,
				host.monitor,
				host.disabled,
				host.status AS cacti_status,
				host.status_rec_date,
				host.status_fail_date,
				host.min_time,
				host.max_time,
				host.cur_time,
				host.avg_time,
				host.availability,
				'1' AS present
				FROM host
				RIGHT JOIN (grid_hosts_jobtraffic
				INNER JOIN (((grid_clusters
				INNER JOIN grid_load
				ON grid_clusters.clusterid=grid_load.clusterid)
				INNER JOIN grid_hosts
				ON (grid_load.clusterid=grid_hosts.clusterid)
				AND (grid_load.host=grid_hosts.host))
				INNER JOIN grid_hostinfo
				ON (grid_load.clusterid=grid_hostinfo.clusterid)
				AND (grid_load.host=grid_hostinfo.host))
				ON (grid_hosts_jobtraffic.clusterid=grid_load.clusterid)
				AND (grid_hosts_jobtraffic.host=grid_load.host))
				ON (host.clusterid=grid_load.clusterid)
				AND (host.hostname=grid_load.host)
				ON DUPLICATE KEY UPDATE
				clustername=VALUES(clustername),
				load_status=VALUES(load_status),
				r15s=VALUES(r15s),
				r1m=VALUES(r1m),
				r15m=VALUES(r15m),
				ut=VALUES(ut),
				pg=VALUES(pg),
				io=VALUES(io),
				ls=VALUES(ls),
				it=VALUES(it),
				tmp=VALUES(tmp),
				swp=VALUES(swp),
				mem=VALUES(mem),
				hStatus=VALUES(hStatus),
				hCtrlMsg=VALUES(hCtrlMsg),
				bhost_status=VALUES(bhost_status),
				cpuFactor=VALUES(cpuFactor),
				windows=VALUES(windows),
				userJobLimit=VALUES(userJobLimit),
				maxJobs=VALUES(maxJobs),
				numJobs=VALUES(numJobs),
				numRun=VALUES(numRun),
				numSSUSP=VALUES(numSSUSP),
				numUSUSP=VALUES(numUSUSP),
				numRESERVE=VALUES(numRESERVE),
				hostType=VALUES(hostType),
				hostModel=VALUES(hostModel),
				maxCpus=VALUES(maxCpus),
				maxMem=VALUES(maxMem),
				maxSwap=VALUES(maxSwap),
				maxTmp=VALUES(maxTmp),
				nDisks=VALUES(nDisks),
				isServer=VALUES(isServer),
				licensed=VALUES(licensed),
				rexPriority=VALUES(rexPriority),
				licFeaturesNeeded=VALUES(licFeaturesNeeded),
				first_seen=VALUES(first_seen),
				last_seen=VALUES(last_seen),
				job_last_started=VALUES(job_last_started),
				job_last_ended=VALUES(job_last_ended),
				job_last_suspended=VALUES(job_last_suspended),
				job_last_exited=VALUES(job_last_exited),
				date_recorded=VALUES(date_recorded),
				cacti_hostid=VALUES(cacti_hostid),
				monitor=VALUES(monitor),
				disabled=VALUES(disabled),
				cacti_status=VALUES(cacti_status),
				status_rec_date=VALUES(status_rec_date),
				status_fail_date=VALUES(status_fail_date),
				min_time=VALUES(min_time),
				max_time=VALUES(max_time),
				cur_time=VALUES(cur_time),
				avg_time=VALUES(avg_time),
				availability=VALUES(availability),
				present=VALUES(present)";

			//print $replace_sql;

			/* timestamps for the user interface */
			$start_time = time();

			grid_debug('Updating Table');
			db_execute($replace_sql);

			/* final timestamp for user interface */
			$replace_time = time() - $start_time;

			/* remove stale records */
			db_execute('DELETE FROM grid_summary WHERE present=0');

			/* timestamps for the user interface */
			$start_time = time();

			/* apply system thresholds */
			grid_debug('Applying THolds');
			apply_grid_summary_tholds($force);

			/* final timestamp for user interface */
			$thold_time = time() - $start_time;

			/* add statistics */
			set_config_option('grid_summary_update_time', time());

			/* remove the process entry */
			db_execute("DELETE FROM grid_processes WHERE taskname='SUMMARY_UPDATE'");
		}
	}
}

function get_defaul_lsfadmin($lsfadmins){
	if(!empty($lsfadmins)){
		$admin_arr = explode(' ', $lsfadmins);
		if (substr_count($admin_arr[0], "\\")) {
			$admin_arr = explode("\\",$admin_arr[0]);
			return $admin_arr[1];
		}else{
			return $admin_arr[0];
		}
	}
	return '';
}

function sorting_json_format($selected_items_whole, $args_lists='', $action_level='', $password='', $user='') {
	global $config, $rtm;

	include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

	//Since LSF 10.1.0.10(With LSF 10.1.0.7 Poller),  RTM use new LSF API, so we bulk operating the batch hosts
	$bulkctrl_first_location = array();
	for ($i=0;$i<count($selected_items_whole);$i++) {
		$explode_selected_items = explode(':',$selected_items_whole[$i]);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$get_dir = db_fetch_row_prepared('SELECT clusterid, username, lsf_admins, aes_decrypt(credential, ' . CREDKEY . ') as credential,
				communication, ip, lsf_master, privatekey_path, lsf_confdir,
				lsf_envdir, lsf_ego, poller_lbindir, grid_pollers.lsf_version, lim_port
				FROM grid_clusters
				LEFT JOIN grid_pollers
				ON grid_clusters.poller_id = grid_pollers.poller_id
				WHERE grid_clusters.clusterid = ?', array($explode_selected_items[1]));
		} else {
			$get_dir = db_fetch_row_prepared('SELECT clusterid, username, lsf_admins, communication, ip, lsf_master,
				privatekey_path, lsf_confdir, lsf_envdir, lsf_ego, poller_lbindir,
				grid_pollers.lsf_version, lim_port
				FROM grid_clusters
				LEFT JOIN grid_pollers
				ON grid_clusters.poller_id = grid_pollers.poller_id
				WHERE grid_clusters.clusterid = ?', array($explode_selected_items[1]));
		}

		if(!cacti_sizeof($get_dir)){
			cacti_log('WARN: Can not found cluster info with Id[' . $explode_selected_items[1] . ']');
			continue;
		}

		$RTM_POLLER_BINDIR = $get_dir['poller_lbindir'];
		$LSF_ENVDIR        = $get_dir['lsf_envdir'];
		$LSF_SERVERDIR     = $get_dir['poller_lbindir'] . "/../etc/";
		$communication     = $get_dir['communication'];

		//Since LSF 10.1.0.10, root operation will be reject if 'LSF_ROOT_USER=N', exception LSF_ADDON_HOSTS configured
		if (strtolower($communication) == 'rsh' || strtolower($communication) == 'ssh') {
			if (is_array($user) && isset($user[$i]) && strlen($user[$i]) > 0) {
				$username = $user[$i];
			} else if (strlen($user) > 0) {
				$username = $user;
			} else {
				$username = $get_dir['username'];
			}
		} else {
			$username = $get_dir['username'];
		}

		if(empty($username)){
			$lsfadmins = $get_dir['lsf_admins'];
			$username  = get_defaul_lsfadmin($lsfadmins);
		}
		if(empty($username)){
			cacti_log('WARN: RTM can not get LSF Administror of cluster[\'' . $explode_selected_items[1]  . '\']');
			continue;
		}

		$ip              = $get_dir['ip'];
		$lsf_master      = $get_dir['lsf_master'];
		$privatekey_path = $get_dir['privatekey_path'];
		$lim_port        = $get_dir['lim_port'];
		$lsf_version     = $get_dir['lsf_version'];
		$lsf_version_str = 'lsf' . $get_dir['lsf_version'];
		$lsf_ego         = $get_dir['lsf_ego'];
		$lsf_confdir     = (empty($get_dir['lsf_confdir']) ? $get_dir['lsf_envdir']:$get_dir['lsf_confdir']);

		if ($action_level == 'cluster') {
			if (($privatekey_path == '') && ($communication=='ssh')) {
				$passkey = $password[$i];
			} elseif ($communication=='winrs') {
				if ($get_dir['credential'] != 'NULL' ) {
					$passkey = $get_dir['credential'];
				} else {
					$passkey = '';
				}
			} else {
				$passkey = '';
			}

			//debug_log_insert('grid_admin', $passkey);

			$json_cluster[$i] = array(
				'clusterid'         => $explode_selected_items[1],
				'name'              => $explode_selected_items[0],
				'args'              => $args_lists,
				'LSF_SERVERDIR'     => $LSF_SERVERDIR,
				'LSF_ENVDIR'        => $LSF_ENVDIR,
				'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR,
				'type'              => $communication,
				'username'          => $username,
				'password'          => $passkey,
				'ip'                => $lsf_master,
				'lsf_ego'           => $lsf_ego,
				'lsf_confdir'       => $lsf_confdir,
				'privatekey_path'   => $privatekey_path,
				'lim_port'          => $lim_port
			);
		} elseif ($action_level == 'signal') {
			$json_cluster[$i] = array(
				'clusterid'         => $explode_selected_items[1],
				'name'              => $explode_selected_items[0],
				'args'              => $args_lists,
				'username'          => $username,
				'LSF_SERVERDIR'     => $LSF_SERVERDIR,
				'LSF_ENVDIR'        => $LSF_ENVDIR,
				'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR
			);
		} elseif ($action_level == 'host') {
			//Support bulk host operation since LSF 10.1.0.10
			if (lsf_version_not_lower_than($lsf_version,'1017')) {
				$clusterid = $explode_selected_items[1];
				if(!isset($bulkctrl_first_location[$clusterid])) {
					$json_cluster[] = array(
						'clusterid'         => $clusterid,
						'name'              => $explode_selected_items[0],
						'args'              => $args_lists,
						'username'          => $username,
						'LSF_SERVERDIR'     => $LSF_SERVERDIR,
						'LSF_ENVDIR'        => $LSF_ENVDIR,
						'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR
					);
					$bulkctrl_first_location[$clusterid] = cacti_sizeof($json_cluster) - 1;
				} else {
					$json_cluster[$bulkctrl_first_location[$clusterid]]['name'] .= "|" . $explode_selected_items[0];
					continue;
				}
			} else {
				$json_cluster[] = array(
					'clusterid'         => $explode_selected_items[1],
					'name'              => $explode_selected_items[0],
					'args'              => $args_lists,
					'username'          => $username,
					'LSF_SERVERDIR'     => $LSF_SERVERDIR,
					'LSF_ENVDIR'        => $LSF_ENVDIR,
					'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR
				);
			}
		} elseif ($action_level == 'queue') {// if action is not 'switch'
			$json_cluster[$i] = array(
				'clusterid'         => $explode_selected_items[1],
				'name'              => $explode_selected_items[0],
				'args'              => $args_lists,
				'username'          => $username,
				'LSF_SERVERDIR'     => $LSF_SERVERDIR,
				'LSF_ENVDIR'        => $LSF_ENVDIR,
				'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR
			);
		} else {
			$json_cluster[$i] = array(
				'clusterid'         => $explode_selected_items[1],
				'name'              => $explode_selected_items[0],
				'args'              => isset($args_lists[$i])? $args_lists[$i]:'',
				'username'          => $username,
				'LSF_SERVERDIR'     => $LSF_SERVERDIR,
				'LSF_ENVDIR'        => $LSF_ENVDIR,
				'RTM_POLLER_BINDIR' => $RTM_POLLER_BINDIR
			);
		}
		//print_r($json_cluster);
	}

	return $json_cluster;
}

function getlsfconf($master_host, $master_port, $enable_ego, $strict_checking, $pollerid) {
	global $rtm;

	$action_level = 'getlsfconf';
	$action = 'getconf';

	$pollerinfo = db_fetch_row_prepared('SELECT poller_lbindir, lsf_version FROM grid_pollers WHERE poller_id=?', array($pollerid));
	$lsf_version_str = 'lsf' . $pollerinfo['lsf_version'];

	if(in_array($lsf_version_str, $rtm)) {
		$LSF_SERVERDIR = $rtm[$lsf_version_str]['LSF_SERVERDIR'];
	} else {
		$LSF_SERVERDIR='.';
	}

	$request = array();
	$request['ip'] = $master_host;
	$request['lim_port'] = $master_port;
	$request['lsf_ego'] = $enable_ego;
	$request['lsf_strict_checking'] = $strict_checking;
	$request['RTM_POLLER_BINDIR'] = $pollerinfo['poller_lbindir'];
	$request['LSF_SERVERDIR'] = $LSF_SERVERDIR;
	$request['name'] = '';
	$request['clusterid'] = '';

	$target[0] = $request;

	$advocate_key = session_auth();
	$json_info = array(
		'key'    => $advocate_key,
		'action' => $action,
		'target' => $target
	);

	$output = json_encode($json_info);

	$curl_output = exec_curl($action_level, $output);

	if ($curl_output['http_code'] == 400) {
		raise_message(134);
	} elseif ($curl_output['http_code'] == 500) {
		raise_message(135);
	} elseif ($curl_output['http_code'] == 200) {
		//if response is 200, do not raise message.
	} else {
		raise_message(136);
	}

	$content_response = $curl_output['content']; //return response from advocate in json format

	$json_decode_content_response = json_decode($content_response,true);

	if (isset($json_decode_content_response) && isset($json_decode_content_response['rsp'])) {
		$rsp_content = (array)$json_decode_content_response['rsp'];
		if (isset($rsp_content[0])) {
			if (isset($rsp_content[0]['status_code'])) {
				$status_code = $rsp_content[0]['status_code'];
				$status_message = $rsp_content[0]['status_message'];
				if ($status_code != 0) {
					debug_log_insert('gridadmin', $status_message);
					return '';
				}
				if (isset($rsp_content[0]['LSF_CONFDIR'])) {
					$lsf_confdir = $rsp_content[0]['LSF_CONFDIR'];
					return $lsf_confdir;
				}
			}
		}
	}
	debug_log_insert('gridadmin', $content_response);
	return '';
}

function exec_curl($action_level, $output) {
	$advocate_port = read_config_option('advocate_port', True);

	$options = array(
		CURLOPT_URL            => 'https://127.0.0.1:' . $advocate_port . '/lsf/control/' . $action_level,
		CURLOPT_RETURNTRANSFER => true,     // return web page
		CURLOPT_HEADER         => false,    // don't return headers
		CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		CURLOPT_ENCODING       => '',       // handle all encodings
		CURLOPT_USERAGENT      => '',       // who am i
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 300,      // timeout on connect
		CURLOPT_TIMEOUT        => 300,      // timeout on response
		CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		CURLOPT_POST           => true,
		CURLOPT_SSL_VERIFYHOST => 'false',
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_POSTFIELDS     => 'json=' . $output);

	$ch      = curl_init();
	curl_setopt_array($ch, $options);
	$content = curl_exec($ch);
	$err     = curl_errno($ch);
	$errmsg  = curl_error($ch);
	$header  = curl_getinfo($ch);
	curl_close($ch);

	$header['errno']   = $err;
	$header['errmsg']  = $errmsg;
	$header['content'] = $content;

	return $header;
}

function session_auth() {
	$advocate_app_key = read_config_option('app_key', True);
	$advocate_port = read_config_option('advocate_port', True);

	$json_advocate_app_key = array(
		'action' => 'auth',
		'appkey' => $advocate_app_key,
	);

	$output = json_encode($json_advocate_app_key);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:' . $advocate_port . '/lsf/auth');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 'false');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'json=' . $output);
	$out = curl_exec($ch);

	if(!empty($out)) {
		$json_decode_content_response = json_decode($out,true);
		$rsp_content = $json_decode_content_response['rsp'];
		return $rsp_content['key'];
	} else {
		$errno   = curl_errno($ch);
		$errmsg  = curl_error($ch);
		cacti_log('DEBUG: Connect advocate service fail with errno["' . $errno . '"], errmsg["' . $errmsg . '"]', false, 'ADVOCATE', POLLER_VERBOSITY_DEBUG);
	}
	return '';
}

/* substitute_cluster_data - takes a string and substitutes all host variables contained in it
	@arg $string - the string to make host variable substitutions on
	@arg $l_escape_string - the character used to escape each variable on the left side
	@arg $r_escape_string - the character used to escape each variable on the right side
	@arg $cluster_id - (int) the host ID to match
	@returns - the original string with all of the variable substitutions made */
function substitute_cluster_data($string, $l_escape_string, $r_escape_string, $cluster_id) {
	if (!isset($_SESSION['sess_cluster_cache_array'][$cluster_id])) {
		$cluster = db_fetch_row_prepared("SELECT * FROM grid_clusters WHERE clusterid=?", array($cluster_id));
		if (!empty($cluster)) {
			$_SESSION['sess_cluster_cache_array'][$cluster_id] = $cluster;
		}
	}

	if (isset($_SESSION['sess_cluster_cache_array']) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]['clustername']))
	$string = str_replace($l_escape_string . 'cluster_name' . $r_escape_string, $_SESSION['sess_cluster_cache_array'][$cluster_id]['clustername'], $string); /* for compatability */
	if (isset($_SESSION['sess_cluster_cache_array']) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]['lsf_master']))
	$string = str_replace($l_escape_string . 'cluster_lsfmaster' . $r_escape_string, $_SESSION['sess_cluster_cache_array'][$cluster_id]['lsf_master'], $string);
	if (isset($_SESSION['sess_cluster_cache_array']) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]['lsf_version']))
	$string = str_replace($l_escape_string . 'cluster_version' . $r_escape_string, $_SESSION['sess_cluster_cache_array'][$cluster_id]['lsf_version'], $string);
	if (isset($_SESSION['sess_cluster_cache_array']) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]) && isset($_SESSION['sess_cluster_cache_array'][$cluster_id]['lim_port']))
	$string = str_replace($l_escape_string . 'cluster_limport' . $r_escape_string, $_SESSION['sess_cluster_cache_array'][$cluster_id]['lim_port'], $string);

	return $string;
}

function create_user($username) {
	// will not create user under Windows
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		return;
	}

	/* don't create the OS user unless the admin requests that to happen */
	if (read_config_option('add_os_users') != 'on') return;

	$advocate_port = read_config_option('advocate_port', True);
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:' . $advocate_port . '/usermgmt/useradd');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 'false');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=' . $username . '&group=&homedir=0');

	$out = curl_exec($ch);
	if ($out == 0) {
		cacti_log('INFO: User:'.$username.' has been added successfully', true, 'GRID');
	} elseif ($out == 2) {
		cacti_log('INFO: User:'.$username.' already exists on local system', true, 'GRID');
	} elseif ($out == 9) {
		cacti_log('INFO: User:'.$username.' exist, unable to create existing user', true, 'GRID');
	} else {
		cacti_log('INFO: Unable to create user:'.$username, true, 'GRID');
	}
}

function get_jobs_query($table_name, $apply_limits = true, &$jobsquery = '', &$rowsquery = '', $resreq_query = '1') {
	global $timespan, $grid_efficiency_sql_ranges;
	global $job_id, $index_id, $config, $authfull;
	global $ws_abrev, $we_abrev, $graph_timeshifts;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

	$rows = 0;
	$pend_hgroup_flag = false;
	$detail_flag      = false;
	$pend_reason_flag = false;
	$has_row_query    = false;
	$non_single_exechost = (read_config_option('grid_jobhosts_collection') == 'on');

	if (!preg_match('/(DONE|EXIT|FINISHED|STARTED)/', get_request_var('status'))
		|| (read_config_option('grid_global_opts_jobs_pageno') == 'on' && read_grid_config_option('default_grid_jobs_pageno') == 'on')) {
		$has_row_query = true;
	}

	if (get_request_var('rows_selector') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows_selector') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows_selector');
	}

	$sql_where = $sql_where1 = $sql_where2 = '';

	if (get_request_var('exception') == '-1') {
		/* Show all items */
	} else {
		switch(get_request_var('exception')) {
		case 'alarm':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "($table_name.efficiency<='" . read_config_option('grid_efficiency_alarm') . "' AND stat!='PEND')";
			break;
		case 'warn':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "($table_name.efficiency<='" . read_config_option('grid_efficiency_warning') . "' AND stat!='PEND')";
			break;
		case 'excl':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . '(options & ' . SUB_EXCLUSIVE . ')';
			break;
		case 'invdep':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "(pendReasons LIKE '%invalid or never satisfied%')";
			break;
		case 'licsch':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "(pendReasons LIKE '%preempted by the License Scheduler%')";
			break;
		case 'dep':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . '(LENGTH(dependCond)!=0)';
			break;
		case 'inter':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . '(options & ' . SUB_INTERACTIVE . ')';
			break;
		case 'flap':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "(stat_changes>='" . read_config_option('grid_flapping_threshold') . "')";
			break;
		case 'gpuonly':
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . '(num_gpus > 0)';
			break;
		case 'pwht'://pend with host
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'Host:%'";
			break;
		case 'pwre'://pend with resource
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'Resource:%'";
			break;
		case 'pwqe'://pend with queue
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'Queue:%'";
			break;
		case 'pwjg'://pend with jobgroup
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'Job Group:%'";
			break;
		case 'pwug'://pend with usrgroup
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'User Group:%'";
			break;
		case 'pwlm'://pend with limit
			$detail_flag=true;
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "gjp.detail LIKE 'Limit %'";
			break;
		case 'slaloaning'://Guaranteed Resource Loaning
			$sql_where .= (strlen($sql_where) ? ' AND ' : 'WHERE ') . "isLoaningGSLA = 1";
			break;
		}
	}

	/* usergroup sql where */
	if (get_request_var('usergroup') == '-1') {
		/* Show all items */
	} else {
		$delim = read_config_option('grid_job_stats_ugroup_delimiter');
		if (read_config_option('grid_usergroup_method') == 'jobmap') {
			if (get_request_var('usergroup') == 'default') {
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . "(userGroup='')";
			} else {
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ((read_config_option('grid_ugroup_group_aggregation') == 'on') ? " (userGroup='" . get_request_var('usergroup') . "' OR userGroup LIKE '" . get_request_var('usergroup') . "\\$delim%')" : " (userGroup='" . get_request_var('usergroup') . "')");
			}
		} else {
			if (read_config_option('grid_ugroup_group_aggregation') == 'on') {
				$users = db_fetch_assoc_prepared("SELECT username FROM grid_user_group_members WHERE groupname=? OR groupname LIKE ?", array(get_request_var('usergroup'), get_request_var('usergroup') . "\\$delim%"));
			} else {
				$users = db_fetch_assoc_prepared("SELECT username FROM grid_user_group_members WHERE groupname=?", array(get_request_var('usergroup')));
			}

			if (cacti_sizeof($users)) {
				$usercount = 0;
				foreach ($users as $user) {
					if ($user['username'] == 'all') {
						break;
					}

					if ($usercount == 0) {
						if (strlen($sql_where)) {
							$sql_where .= " AND ($table_name.user IN (";
						} else {
							$sql_where = "WHERE ($table_name.user IN (";
						}

						$sql_where .= "'" . $user["username"] . "'";
					} else {
						$sql_where .= ", '" . $user["username"] . "'";
					}

					$usercount++;
				}

				if ($usercount > 0) {
					$sql_where .= '))';
				}
			}
		}
	}

	if (orderby_clustername()) {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.clusterid=grid_clusters.clusterid)";
		} else {
			$sql_where = "WHERE ($table_name.clusterid=grid_clusters.clusterid)";
		}
	}

	/* jobid  id sql where */
	if (get_request_var('jobid') == '') {
		/* Show all items */
	} else {
		if (empty($job_id)) {
			$tjob = explode('[', get_request_var('jobid'));
			$job_id = $tjob[0];
			if (isset($tjob[1])) {
				$index_id = trim($tjob[1], "] \r\n");
			} else {
				$index_id = '';
			}
		}

		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.jobid=" . $job_id . ")";

			if (strlen($index_id)) {
				$sql_where .= " AND ($table_name.indexid='" . $index_id . "')";
			}
		} else {
			$sql_where = "WHERE ($table_name.jobid=" . $job_id . ")";

			if (strlen($index_id)) {
				$sql_where .= " AND ($table_name.indexid='" . $index_id . "')";
			}
		}
	}

	/* user id sql where */
	if (get_request_var('job_user') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.user='" . get_request_var('job_user') . "')";
		} else {
			$sql_where = "WHERE ($table_name.user='" . get_request_var('job_user') . "')";
		}
	}

	/* efficiency sql where */
	if (get_request_var('efficiency') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND (' . str_replace('efficiency', $table_name . '.efficiency', $grid_efficiency_sql_ranges[get_request_var('efficiency')]) . ')';
		} else {
			$sql_where = 'WHERE (' . str_replace('efficiency', $table_name . '.efficiency', $grid_efficiency_sql_ranges[get_request_var('efficiency')]) . ')';
		}
	}

	/* clusterid sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where  .= " AND ($table_name.clusterid='" . get_request_var('clusterid') . "')";
		} else {
			$sql_where  = "WHERE ($table_name.clusterid='" . get_request_var('clusterid') . "')";
		}
	}

	/* job status sql where */
	if ((get_request_var('status') == '-1') || (get_request_var('status')  == 'ALL')) {
		/* Show all items */
	} else {
		if ((get_request_var('status') == 'ACTIVE')) {
			/* Merge SUP#196208 to show ZOMBIE jobs under ACTIVE*/
			if (strlen($sql_where)) {
				$sql_where .= " AND (stat NOT IN ('DONE', 'EXIT'))";
			} else {
				$sql_where  = "WHERE (stat NOT IN ('DONE', 'EXIT'))";
			}
		} elseif ((get_request_var('status') == 'STARTED')) {
			/* do nothing, all status' wanted */
		} elseif ((get_request_var('status') == 'FINISHED')) {
			if (!substr_count($table_name, 'finished')) {
				if (strlen($sql_where)) {
					$sql_where .= " AND (stat IN ('DONE', 'EXIT'))";
				} else {
					$sql_where  = "WHERE (stat IN ('DONE', 'EXIT'))";
				}
			}
		} elseif ((get_request_var('status') == 'RUNNING')) {
			 if (strlen($sql_where)) {
				$sql_where .= " AND (stat IN ('RUNNING', 'PROV'))";
			} else {
				$sql_where  = "WHERE (stat IN ('RUNNING', 'PROV'))";
			}

		} else {
			if (strlen($sql_where)) {
				$sql_where .= " AND (stat='" . get_request_var('status') . "')";
			} else {
				$sql_where  = "WHERE (stat='" . get_request_var('status') . "')";
			}
		}
	}

	/* queue sql where */
	if (get_request_var('queue') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.queue='" . get_request_var('queue') . "')";
		} else {
			$sql_where  = "WHERE ($table_name.queue='" . get_request_var('queue') . "')";
		}
	}

	/* project sql where */
	if (get_request_var('project') == '' || get_request_var('project') == 'Not Collected') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.projectName='" . get_request_var('project') . "')";
		} else {
			$sql_where  = "WHERE ($table_name.projectName='" . get_request_var('project') . "')";
		}
	}

	/* submission host sql where */
	if (get_request_var('sub_host') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.from_host='" . get_request_var('sub_host') . "')";
		} else {
			$sql_where  = "WHERE ($table_name.from_host='" . get_request_var('sub_host') . "')";
		}
	}

	/* search filter sql where */
	/* if (get_request_var('filter') != '') {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.jobname LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.projectName LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.licenseProject LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.jobGroup LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.jobid LIKE '%" . get_request_var('filter') . "%')";
		} else {
			$sql_where  = "WHERE ($table_name.jobname LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.projectName LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.licenseProject LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.jobGroup LIKE '%" . get_request_var('filter') . "%' OR
								$table_name.jobid LIKE '%" . get_request_var('filter') . "%')";
		}
	}
  	*/
  	if (get_request_var('filter') != '') {
		if ($sql_where != '') {
			$sql_where .= ' AND ';
		} else {
			$sql_where  = 'WHERE ';
		}

		/*
		if (isset_request_var('exactly_match') && (get_request_var('exactly_match') == 'true' || get_request_var('exactly_match') == 'on')) {
			$sql_where_proj = "$table_name.projectName = '" . get_request_var('filter') . "'";
		} else {
			$sql_where_proj = "$table_name.projectName LIKE '%" . get_request_var('filter') . "%'";
		}
		*/

		$sql_where_proj = "$table_name.projectName LIKE '%" . get_request_var('filter') . "%'";
		$sql_where .= " ($table_name.jobname LIKE '%" . get_request_var('filter') . "%' OR
			$sql_where_proj OR
			$table_name.licenseProject LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.jobGroup LIKE '%" . get_request_var('filter') . "%' OR
			$table_name.jobid LIKE '%" . get_request_var('filter') . "%')";
	}

	/* host group sql where */
	if (get_request_var('hgroup') == '-1') {
		/* Show all items */
	} elseif ((get_request_var('hgroup') != '-1') && (get_request_var('status')=='PEND' ||  get_request_var('status') == 'PSUSP')) {
		$pend_hgroup_flag=true;
		if (strlen($sql_where)) {
			$sql_where .= " AND (groupName='".get_request_var('hgroup')."')";
		} else {
			$sql_where  = "WHERE (groupName='".get_request_var('hgroup')."')";
		}
	}

	/* resource string where clause */
	if (get_request_var('resource_str') != '') {
		if (get_request_var('clusterid') > 0) {
			$resource_search_str = '%' . get_nfilter_request_var('resource_str') . '%';

			switch ($resreq_query) {
				case '1'://host level
					$res_tool  = grid_get_res_tooldir(get_request_var('clusterid')) . '/gridhres';

					$cwd = getcwd();
					chdir(grid_get_res_tooldir(get_request_var('clusterid')));

					if (is_executable($res_tool)) {
						$res_cmd   = $res_tool . ' -C ' . get_filter_request_var('clusterid') . ' -R ' . cacti_escapeshellarg(get_nfilter_request_var('resource_str'));
						$ret_val   = 0;
						$ret_out   = array();
						$res_hosts = exec($res_cmd, $ret_out, $ret_val);

						chdir($cwd);
						if (!$ret_val) {
							if (strlen($res_hosts)) {
								if (strlen($sql_where)) {
									$sql_where .= " AND $table_name.exec_host IN ($res_hosts)";
								} else {
									$sql_where = "WHERE $table_name.exec_host IN ($res_hosts)";
								}
							} else {
								$jobsquery = '';
								return ;
							}
						} else {
							if ($ret_val == 96) {
								$_SESSION['sess_messages'] = __('No hosts returned', 'grid');
							} elseif ($ret_val == 95) {
								$_SESSION['sess_messages'] = __('Invalid Resource String', 'grid');
							} else {
								$_SESSION['sess_messages'] = __('Unknown LSF Error: %s', $ret_val, 'grid');
							}

							$jobsquery ='';
							return ;
						}
					} else {
						cacti_log('ERROR: gridhres either does not exist or is not executable!', false, 'GRID');
					}
					break;
				case '2'://job level, res_requirements
					if (strlen($sql_where)) {
						$sql_where .= " AND $table_name.res_requirements LIKE ".db_qstr($resource_search_str);
					} else {
						$sql_where = "WHERE $table_name.res_requirements LIKE ".db_qstr($resource_search_str);
					}
					break;
				case '3'://job level, combinedResreq
					if (strlen($sql_where)) {
						$sql_where .= " AND $table_name.combinedResreq LIKE ".db_qstr($resource_search_str);
					} else {
						$sql_where = "WHERE $table_name.combinedResreq LIKE ".db_qstr($resource_search_str);
					}
					break;
				case '4'://job level, effectiveResreq
					if (strlen($sql_where)) {
						$sql_where .= " AND $table_name.effectiveResreq LIKE ".db_qstr($resource_search_str);
					} else {
						$sql_where = "WHERE $table_name.effectiveResreq LIKE ".db_qstr($resource_search_str);
					}
					break;
			}
		} else {
			unset_request_var('resource_str');
			load_current_session_value('resource_str', 'sess_grid_view_jobs_resource_str', '');
		}
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING' || get_request_var('status') == 'PROV') {
		if (read_config_option('grididle_bgcolor') > 0 && get_request_var('exception') == 'hogs') {
				if (get_request_var('clusterid')>0) {
					$idle_jobs = db_fetch_assoc_prepared('SELECT * FROM grid_jobs_idled WHERE clusterid=?', array(get_filter_request_var('clusterid')));
				} else {
					$idle_jobs = db_fetch_assoc('SELECT * FROM grid_jobs_idled');
				}

				$in_clause = '(';
				if (cacti_sizeof($idle_jobs)) {
					foreach ($idle_jobs as $job) {
						if ($in_clause == '(') {
							$in_clause .= "'" . $job['clusterid'] . '_' . $job['jobid'] . '_' . $job['indexid'] . "'";
						} else {
							$in_clause .= ",'" . $job['clusterid'] . '_' . $job['jobid'] . '_' . $job['indexid'] . "'";
						}
					}
				}
				$in_clause .= ')';

			/* if empty set then put empty string for in clause */
				if ($in_clause == '()') {

					$in_clause = "('')";
				}

				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . "CONCAT_WS('',$table_name.clusterid,'_',$table_name.jobid,'_',$table_name.indexid,'') IN $in_clause";
		}
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING' || get_request_var('status') == 'PROV' ||
		get_request_var('status') == 'DONE' || get_request_var('status') == 'EXIT' || get_request_var('status') == 'FINISHED') {
		if (read_config_option('gridmemvio_bgcolor') > 0 && get_request_var('exception') == 'memvio') {  //overusage
			$mem_limit     = read_config_option('gridmemvio_min_memory');
			$memvio_window = read_config_option('gridmemvio_window');
			$overage       = read_config_option('gridmemvio_overage');

			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .
			   "max_memory > mem_reserved * (1+$overage) AND
			   mem_reserved * (1+$overage) > 0 AND
			   run_time > $memvio_window" .
			   ($mem_limit != -1 ? " AND max_memory>$mem_limit":"");
		} elseif (read_config_option('gridmemvio_us_bgcolor') > 0 && get_request_var('exception') == 'memviou') {//underusage
			$mem_limit     = read_config_option("gridmemvio_min_memory");
			$memvio_window = read_config_option("gridmemvio_window");
			$underage      = read_config_option("gridmemvio_us_allocation");

			$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') .
			   "max_memory < mem_reserved * (1-$underage) AND
			   mem_reserved * (1-$underage) > 0 AND
			   run_time > $memvio_window" .
			   ($mem_limit != -1 ? " AND max_memory>$mem_limit":"");
		}
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING' || get_request_var('status') == 'PROV') {
		if (read_config_option('gridrunlimitvio_bgcolor') > 0 && get_request_var('exception') == 'runtimevio') {
			if (get_request_var('clusterid')>0) {
				$runtime_jobs = db_fetch_assoc_prepared("SELECT * FROM grid_jobs_runtime WHERE present=1 AND (type=2 OR type=1) AND clusterid=?", array(get_filter_request_var('clusterid')));
			} else {
				$runtime_jobs = db_fetch_assoc("SELECT * FROM grid_jobs_runtime WHERE present=1 AND (type=2 OR type=1) ");
			}

			$in_clause = '(';
			if (cacti_sizeof($runtime_jobs)) {
				foreach ($runtime_jobs as $job) {
					if ($in_clause == '(') {
						$in_clause .= "'" . $job['clusterid'] . '_' . $job['jobid'] . '_' . $job['indexid'] . "'";
					} else {
						$in_clause .= ",'" . $job['clusterid'] . '_' . $job['jobid'] . '_' . $job['indexid'] . "'";
						}
					}
				}
				$in_clause .= ')';

				/* if empty set then put empty string for in clause */
				if ($in_clause == '()') {

					$in_clause = "('')";
				}

				$sql_where .= (strlen($sql_where) ? ' AND ': 'WHERE ') . "CONCAT_WS('',$table_name.clusterid,'_',$table_name.jobid,'_',$table_name.indexid,'') IN $in_clause";

		}
	}

	if (get_request_var('status') == 'PEND') {
		if (get_request_var('reasonid') != '' || get_request_var('level')!=-1 || $detail_flag ) {
			$pend_reason_flag=true;
			if ($pend_hgroup_flag) {
				$join_reason_sql=" grid_jobs_pendreasons as gjp
					ON ( gjp.indexid=grid_jobs.indexid AND gjp.jobid=grid_jobs.jobid AND gjp.clusterid=grid_jobs.clusterid)";

			} else {
				$join_reason_sql=" grid_jobs_pendreasons as gjp
					INNER JOIN grid_jobs AS grid_jobs
					ON ( gjp.indexid=grid_jobs.indexid AND gjp.jobid=grid_jobs.jobid AND gjp.clusterid=grid_jobs.clusterid) ";
			}

			$where_reason_sql = "gjp.end_time='0000-00-00 00:00:00'";

			/* pend reason sql where */
			if (get_request_var('reasonid') != '') {
				$reason_where= "gjp.reason IN ( SELECT reason_code FROM grid_jobs_pendreason_maps
					WHERE reason LIKE '%" .get_request_var('reasonid') ."%' AND issusp = 0) " ;

				if (strlen($sql_where)) {
					$sql_where .= " AND ($reason_where)";
				} else {
					$sql_where  = " WHERE ($reason_where)";
				}
			}

			if (get_request_var('level') != -1 && strlen(get_request_var('level')) > 0) {
				switch(get_request_var('level')) {
					case 0:
						$where_reason_sql .=(strlen($sql_where) ? ' AND ' : 'WHERE ') . '(gjp.type != 2)';
						break;
					case 1:
						$where_reason_sql .=(strlen($sql_where) ? ' AND ' : 'WHERE ') . '(gjp.type IN(15,2))';
						break;
					case 2:
						$where_reason_sql .=(strlen($sql_where) ? ' AND ' : 'WHERE ') . '(gjp.type IN(15,13))';
						break;
				}
			}
		}
	}


	/* app sql where */
	if (get_request_var('app') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($table_name.app='" . get_request_var('app') . "')";
		} else {
			$sql_where  = "WHERE ($table_name.app='" . get_request_var('app') . "')";
		}
	}

	$jgroup_where = $table_name . ".jobGroup='" . get_request_var('jgroup') . "'";

	/* job group sql where */
	if (get_request_var('jgroup') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND ($jgroup_where)";
		} else {
			$sql_where  = " WHERE ($jgroup_where)";
		}
	}

	$sql_where = api_plugin_hook_function('grid_jobs_sql_where', $sql_where);

	/* initialize some arrays */
	$jobs1 = $jobs2 = array();

	/* code to determine jobs in range */
	$jobs_where          = '';
	$jobs_finished_where = '';
	$jobs_query          = '';
	$jobs_finished_query = '';
	$jobs_finished_query_array = array();
	$timespan_set = false;

	$job_zoom_interval = $graph_timeshifts[read_config_option('grid_max_job_zoom_interval', false)];

	/* simplify code */
	$ws       = date('Y-m-d H:i:s', strtotime($timespan['current_value_date1']));
	$we       = date('Y-m-d H:i:s', strtotime($timespan['current_value_date2']));
	$ws_abrev = date('Y-m-d H:i:s', strtotime('-' . $job_zoom_interval, strtotime($timespan['current_value_date1'])));
	$we_abrev = date('Y-m-d H:i:s', strtotime('+' . $job_zoom_interval, strtotime($timespan['current_value_date2'])));

	$zoom_condition = "	((start_time BETWEEN '$ws' AND '$we') ||
		(end_time BETWEEN '$ws' AND '$we') ||
		((start_time BETWEEN '$ws_abrev' AND '$ws') AND (end_time BETWEEN '$we' AND '$we_abrev')))";

	/* add the timespan first */
	if ($authfull) {
		/* job zoom can only come from host and queue graphs. */
		if ((get_request_var('status') == '-1') && (get_request_var('clusterid') != '-1' ) &&
			((isset_request_var('queue') && get_request_var('queue') != '-1') || (isset_request_var('exec_host') && get_request_var('exec_host') != '-1'))) {

			if (strlen($sql_where)) {
				$jobs_where          = $sql_where . " AND (stat NOT IN ('DONE', 'EXIT', 'PEND') AND start_time BETWEEN '$ws_abrev' AND  '$we')";
				$jobs_finished_where = $sql_where . " AND $zoom_condition";
			} else {
				$jobs_where          = " WHERE (stat NOT IN ('DONE', 'EXIT', 'PEND') AND start_time BETWEEN '$ws_abrev' AND '$we')";
				$jobs_finished_where = " WHERE $zoom_condition";
			}

			$timespan_set = true;
			/* status=ALL then get all jobs from grid_jobs table except FINISHED jobs */
		} elseif (get_request_var('status') == "ALL" && get_request_var('clusterid') != "-1") {
			if (strlen($sql_where)) {
				$jobs_where = $sql_where . " AND ((stat IN ('DONE', 'EXIT') AND (end_time BETWEEN '$ws' AND '$we')) OR (stat NOT IN ('DONE', 'EXIT') AND (grid_jobs.submit_time BETWEEN '$ws_abrev' AND '$we')))";
				$jobs_finished_where = $sql_where . " AND (end_time BETWEEN '$ws' AND '$we')";
			} else {
				$jobs_where = "WHERE ((stat IN ('DONE', 'EXIT') AND (end_time BETWEEN '$ws' AND '$we')) OR (stat NOT IN ('DONE', 'EXIT') AND (grid_jobs.submit_time BETWEEN '$ws_abrev' AND '$we')))";
				$jobs_finished_where = "WHERE (end_time BETWEEN '$ws' AND '$we')";
			}
			$timespan_set = true;
		} elseif (get_request_var('status') == "STARTED" && get_request_var('clusterid') != "-1") {
			if (strlen($sql_where)) {
				$jobs_where = $sql_where . " AND (start_time BETWEEN '$ws' AND '$we')";
				$jobs_finished_where = $sql_where . " AND (start_time BETWEEN '$ws' AND '$we')";
			} else {
				$jobs_where = "WHERE (start_time BETWEEN '$ws' AND '$we')";
				$jobs_finished_where = "WHERE (start_time BETWEEN '$ws' AND '$we')";
			}
			$timespan_set = true;
		} elseif ((preg_match('/^(DONE|EXIT|FINISHED)$/', get_request_var('status'))) && (get_request_var('clusterid') != "-1")) {
			if ($table_name == "grid_jobs_finished") {
				if (strlen($sql_where)) {
					$jobs_finished_where = $sql_where . " AND (end_time BETWEEN '$ws' AND '$we')";
				} else {
					$jobs_finished_where = "WHERE (end_time BETWEEN '$ws' AND '$we')";
				}
				$timespan_set = true;
			} elseif ($table_name == "grid_jobs") {
				if (strlen($sql_where)) {
					$jobs_where = $sql_where . " AND (end_time BETWEEN '$ws' AND '$we')";
				} else {
					$jobs_where = "WHERE (end_time BETWEEN '$ws' AND '$we')";
				}
				$timespan_set = true;
			}
		} else {
			$timespan_set = false;
		}

		$select = build_jobs_select_list($table_name, $table_name);

		if (get_request_var('exec_host') == -1 || (get_request_var('status') == 'PEND' ||  get_request_var('status') == 'PSUSP')) {
			if ($timespan_set) {
				if (strlen($jobs_where)) {
					$jobs_where = str_replace("grid_jobs_finished", "grid_jobs", $jobs_where);

					$jobs_query = "SELECT $select
						FROM " . (orderby_clustername() ? "grid_clusters,":"") ." grid_jobs
						$jobs_where";
						if ($apply_limits) {
							$jobs_query .= " LIMIT " . ($rows*(get_request_var('page') - 1)) . "," . $rows;
						}

					//if (!preg_match('/(DONE|EXIT|FINISHED|STARTED)/', get_request_var('status')))
					if ($has_row_query) {
						$rowsquery .= (strlen($rowsquery) ? " UNION ALL ":"") . "SELECT COUNT(grid_jobs.clusterid) AS total FROM " . (orderby_clustername() ? "grid_clusters,":"") ." grid_jobs
							$jobs_where";
					} else {
						$rowsquery = "";
					}
				}

				if (strlen($jobs_finished_where)) {
					$jobs_finished_where = str_replace("grid_jobs.", "grid_jobs_finished.", $jobs_finished_where);
					if (read_config_option('grid_partitioning_enable') == '') {
						$jobs_finished_query = "SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") ." grid_jobs_finished
								$jobs_finished_where";
						$jobs_finished_query_array = array();

						//if (preg_match('/(DONE|EXIT|FINISHED|STARTED)/', get_request_var('status')))
						if ($has_row_query) {
							$rowsquery .= (strlen($rowsquery) ? " UNION ALL ":"") . "SELECT COUNT(grid_jobs_finished.clusterid) AS total FROM " . (orderby_clustername() ? "grid_clusters,":"") ." grid_jobs_finished
									$jobs_finished_where";
						} else {
							$rowsquery = "";
						}
					} else {
						$union_tables = partition_get_partitions_for_query('grid_jobs_finished', $ws_abrev, $we_abrev);
						$jobs_finished_query = '';
						$jobs_finished_query_array = array();

						if (cacti_sizeof($union_tables)) {
							foreach ($union_tables as $table) {
								/* if table does have app column and app filter is selected, don't need to get any data from this table */
								if (table_contains_app($table) == false and get_request_var('app') != -1) {
									continue;
								}

								$partno = "orig";
								if ((strlen($table) - strrpos($table, '_v')) == 5) {
									$partno = substr($table, -4);
								}

								$select = build_jobs_select_list($table, $table_name);

								$jobs_finished_query_item = 'SELECT '. $select .' FROM ' . (orderby_clustername() ? 'grid_clusters,':'') . $table . ' ' . str_replace("$table_name.", "$table.", $jobs_finished_where);
								$rowsquery_item = "SELECT COUNT($table.clusterid) AS total FROM " . (orderby_clustername() ? "grid_clusters,":"") . $table . ' ' . str_replace("$table_name.", "$table.", $jobs_finished_where);

								$jobs_finished_query_array[$partno] = $jobs_finished_query_item;
								$jobs_finished_query .= (strlen($jobs_finished_query) ? " UNION ALL ":"") . $jobs_finished_query_item;
								$rowsquery .= (strlen($rowsquery) ? " UNION ALL ":"") . $rowsquery_item;

							}

							//if (!preg_match('/(DONE|EXIT|FINISHED|STARTED)/', get_request_var('status')))
							if ($has_row_query) {
								 $rowsquery = "SELECT SUM(total) FROM ($rowsquery) AS a";
							} else {
								$rowsquery = "";
							}
						}
					}
					if ($apply_limits) {
						$jobs_finished_query .= " LIMIT " . ($rows*(get_request_var('page') -1)) . "," . $rows;
					}
				}
			} else {
				if ($table_name != 'grid_jobs' && read_config_option('grid_partitioning_enable')) {
					$union_tables = partition_get_partitions_for_query('grid_jobs_finished', $ws_abrev, $we_abrev);
					$jobs_query = '';
					if (cacti_sizeof($union_tables)) {
						foreach ($union_tables as $table) {
							/* if table does have app column and app filter is selected, don't need to get any data from this table */
							if (table_contains_app($table) == false and get_request_var('app') != -1) {
								continue;
							}

							$select = build_jobs_select_list($table, $table_name);
							if (strlen($jobs_finished_query)) {
								$jobs_query .= " UNION ALL " . $select . " FROM " . (orderby_clustername() ? "grid_clusters, ":"") . $table . " " . str_replace("grid_jobs_finished", $table, $jobs_finished_where);
								$rowsquery .= (strlen($rowsquery) ? " UNION ALL ":"") . "SELECT COUNT($table.clusterid) AS total FROM " . (orderby_clustername() ? "grid_clusters,":"") . $table . " " . str_replace("grid_jobs_finished", $table, $jobs_finished_where);
							} else {
								$jobs_query .= " UNION ALL " . $select . " FROM " . (orderby_clustername() ? "grid_clusters, ":"") . $table . " " . str_replace("grid_jobs_finished", $table, $jobs_finished_where);
								$rowsquery .= (strlen($rowsquery) ? " UNION ALL ":"") . "SELECT COUNT($table.clusterid) AS total FROM " . (orderby_clustername() ? "grid_clusters,":"") . $table . " " . str_replace("grid_jobs_finished", $table, $jobs_finished_where);
							}
						}

						$rowsquery = "SELECT SUM(total) FROM ($rowsquery) AS a";
					}
				} else {
					if ($pend_hgroup_flag) {
						$join_groupName_sql=" grid_hostgroups AS ghg
							INNER JOIN grid_jobs_reqhosts AS gjrh
							ON (ghg.groupName=gjrh.host AND ghg.clusterid=gjrh.clusterid)
							INNER JOIN grid_jobs AS grid_jobs
							ON (gjrh.jobid=grid_jobs.jobid AND gjrh.clusterid=grid_jobs.clusterid
							AND gjrh.indexid=grid_jobs.indexid AND gjrh.submit_time=grid_jobs.submit_time) ";
						$join_host_sql=" grid_hostgroups AS ghg
							INNER JOIN grid_jobs_reqhosts AS gjrh
							ON (ghg.host=gjrh.host AND ghg.clusterid=gjrh.clusterid)
							INNER JOIN grid_jobs AS grid_jobs
							ON (gjrh.jobid=grid_jobs.jobid AND gjrh.clusterid=grid_jobs.clusterid
							AND gjrh.indexid=grid_jobs.indexid AND gjrh.submit_time=grid_jobs.submit_time) ";

						if ($pend_reason_flag) {
							$jobs_query="SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . $join_host_sql.
								"INNER JOIN". $join_reason_sql.
								$sql_where . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql .
								" UNION ALL SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . $join_groupName_sql.
								"INNER JOIN" . $join_reason_sql.
								$sql_where . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql;

							$rowsquery = "SELECT count(*) FROM (
									SELECT DISTINCT grid_jobs.jobid,grid_jobs.indexid,grid_jobs.clusterid,grid_jobs.submit_time
									FROM  $join_host_sql INNER JOIN $join_reason_sql $sql_where "
									 . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql . "
									UNION ALL
									SELECT DISTINCT grid_jobs.jobid,grid_jobs.indexid,grid_jobs.clusterid,grid_jobs.submit_time
									FROM   $join_groupName_sql INNER JOIN $join_reason_sql
									$sql_where"  . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql . ") as temp";
						} else {
							$jobs_query = "SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . $join_host_sql.
								$sql_where.
								" UNION ALL SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . $join_groupName_sql.
								$sql_where;

							$rowsquery = "SELECT count(*) FROM (
									SELECT grid_jobs.jobid,grid_jobs.indexid,grid_jobs.clusterid,grid_jobs.submit_time
									FROM  $join_host_sql $sql_where
									UNION ALL
									SELECT grid_jobs.jobid,grid_jobs.indexid,grid_jobs.clusterid,grid_jobs.submit_time
									FROM   $join_groupName_sql
									$sql_where) as temp";
						}
					} else {
						if ($pend_reason_flag) {
							$jobs_query = "SELECT " . $select . "
							 FROM " . (orderby_clustername() ? "grid_clusters,":"") . " $join_reason_sql
							$sql_where" . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql;
							$rowsquery = "SELECT COUNT(clusterid) AS total
							FROM (SELECT " . $select . "
							FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$join_reason_sql $sql_where "
							. (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql . ") AS temp";
						} else {
							$jobs_query = "SELECT " . $select . "
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$table_name
								$sql_where";

							$rowsquery = "SELECT COUNT($table_name.clusterid) AS total
								FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$table_name
								$sql_where";
						}
					}
				}

				if ($apply_limits) {
					$jobs_query .= " LIMIT " . ($rows*(get_request_var('page') -1)) . "," . $rows;
				}
			}
		} else {
			if ($timespan_set) {
				if (strlen($jobs_where)) {
					$jobs_sql_where1 = $jobs_where . " AND (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
					if ($non_single_exechost)
						$jobs_sql_where2 = $jobs_where . " AND (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
				} else {
					$jobs_sql_where1  = "WHERE (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
					if ($non_single_exechost)
						$jobs_sql_where2  = "WHERE (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
				}

				if (strlen($jobs_finished_where)) {
					$jobs_finished_sql_where1 = $jobs_finished_where . " AND (grid_jobs_finished.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs_finished.num_nodes <= 1)";
					if ($non_single_exechost)
						$jobs_finished_sql_where2 = $jobs_finished_where . " AND (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs_finished.num_nodes > 1)";
				} else {
					$jobs_finished_sql_where1  = "WHERE (grid_jobs_finished.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs_finished.num_nodes <= 1)";
					if ($non_single_exechost)
						$jobs_finished_sql_where2  = "WHERE (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs_finished.num_nodes > 1)";
				}

				if (strlen($jobs_where)) {
					$jobs_sql_where1 = str_replace("grid_jobs_finished.", "grid_jobs.", $jobs_sql_where1);
					$jobs_sql_where2 = str_replace("grid_jobs_finished.", "grid_jobs.", $jobs_sql_where2);

					$jobs_query  = grid_jobs_make_sql_union("grid_jobs", $jobs_sql_where1, $jobs_sql_where2, $apply_limits, $rows);
				}

				if (strlen($jobs_finished_where)) {
					$jobs_finished_sql_where1 = str_replace("grid_jobs.", "grid_jobs_finished.", $jobs_finished_sql_where1);
					$jobs_finished_sql_where2 = str_replace("grid_jobs.", "grid_jobs_finished.", $jobs_finished_sql_where2);

					$jobs_finished_query_array = array();
					$jobs_finished_query = grid_jobs_make_sql_union("grid_jobs_finished", $jobs_finished_sql_where1, $jobs_finished_sql_where2, $apply_limits, $rows, $jobs_finished_query_array);
				}

				/*if ((strlen($rowsquery1)) && strlen($rowsquery2)) {
					$rowsquery = "SELECT SUM(total) FROM ($rowsquery1 UNION $rowsquery2) AS a";
				} elseif (strlen($rowsquery1)) {
					$rowsquery = "SELECT SUM(total) FROM $rowsquery1";
				} elseif (strlen($rowsquery2)) {
					$rowsquery = "SELECT SUM(total) FROM $rowsquery2";
				}*/
			  if ((strlen($jobs_query)) && strlen($jobs_finished_query)) {
					$rowsquery = "SELECT COUNT(DISTINCT a.clusterid, a.jobid, a.indexid, a.submit_time) FROM ($jobs_query UNION ALL $jobs_finished_query) AS a";
				} elseif (strlen($jobs_query)) {
					$rowsquery = "SELECT COUNT(DISTINCT a.clusterid, a.jobid, a.indexid, a.submit_time) FROM ($jobs_query) AS a";
				} elseif (strlen($jobs_finished_query)) {
					$rowsquery = "SELECT COUNT(DISTINCT a.clusterid, a.jobid, a.indexid, a.submit_time) FROM ($jobs_finished_query) AS a";
				}
				//print "rowsquery11: $rowsquery<br/>";
			} else {
				if (strlen($sql_where)) {
					$sql_where1 = $sql_where . " AND (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
					if ($non_single_exechost)
						$sql_where2 = $sql_where . " AND (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
				} else {
					$sql_where1  = "WHERE (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
					if ($non_single_exechost)
						$sql_where2  = "WHERE (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
				}

				if ($table_name == "grid_jobs"){
					$union_sql = 'UNION';
				} else {
					$union_sql = 'UNION ALL';
				}
				/*Fix ProblemDB#235038 No Row found*/
				$rowsquery = "SELECT COUNT(a.clusterid) AS total
						FROM (SELECT clusterid, jobid, indexid, exec_host, submit_time from $table_name $sql_where1 "
						. ($non_single_exechost ? "
						$union_sql (SELECT grid_jobs_jobhosts.clusterid, grid_jobs_jobhosts.jobid, grid_jobs_jobhosts.indexid, grid_jobs_jobhosts.exec_host, grid_jobs_jobhosts.submit_time
						FROM " . (orderby_clustername() ? "grid_clusters,":"") . " grid_jobs_jobhosts
						INNER join $table_name
						ON $table_name.jobid=grid_jobs_jobhosts.jobid
						AND $table_name.indexid=grid_jobs_jobhosts.indexid
						AND $table_name.clusterid=grid_jobs_jobhosts.clusterid
						AND $table_name.submit_time=grid_jobs_jobhosts.submit_time
						$sql_where2 )" : "" )
						. ") AS a";

				$jobs_finished_query_array = array();
				$jobs_query = grid_jobs_make_sql_union($table_name, $sql_where1, $sql_where2, $apply_limits, $rows, $jobs_finished_query_array);
			}
		}
	} else {
		if ((get_request_var('status') == "-1") && (get_request_var('clusterid') != "-1" ) &&
			((isset_request_var('queue') && get_request_var('queue') != "-1") || (isset_request_var('exec_host') && get_request_var('exec_host') != "-1"))) {

			/* we don't have to look at the finished table if the job ended less than 2 hours ago */
			if (strlen($sql_where)) {
				$sql_where          = $sql_where . " AND $zoom_condition";
			} else {
				$sql_where          = " WHERE $zoom_condition";
			}
			$timespan_set = true;

		/* status=ALL then get all jobs from grid_jobs table.  since we are in simple view.  Get all FINISHED jobs as well*/
		} elseif (get_request_var('status') == "ALL" && get_request_var('clusterid') != "-1") {
			if (strlen($sql_where)) {
				$sql_where .= " AND (((stat NOT IN ('DONE', 'EXIT')) AND (grid_jobs.submit_time BETWEEN '$ws' AND '$we')) OR (end_time BETWEEN '$ws' AND '$we'))";
			} else {
				$sql_where = "WHERE (((stat NOT IN ('DONE', 'EXIT')) AND (grid_jobs.submit_time BETWEEN '$ws' AND '$we')) OR (end_time BETWEEN '$ws' AND '$we'))";
			}
			$timespan_set = true;
		} elseif (get_request_var('status') == "STARTED" && get_request_var('clusterid') != "-1") {
			if (strlen($sql_where)) {
				$jobs_where = $sql_where . " AND (start_time BETWEEN '$ws' AND '$we')";
				$jobs_finished_where = $sql_where . " AND (start_time BETWEEN '$ws' AND '$we')";
			} else {
				$jobs_where = "WHERE (start_time BETWEEN '$ws' AND '$we')";
				$jobs_finished_where = "WHERE (start_time BETWEEN '$ws' AND '$we')";
			}
			$timespan_set = true;
		} elseif ((preg_match('/^(DONE|EXIT|FINISHED)$/', get_request_var('status'))) &&
			(get_request_var('clusterid') != "-1")) {
			if (strlen($sql_where)) {
				$sql_where = $sql_where . " AND (end_time BETWEEN '$ws' AND '$we')";
			} else {
				$sql_where = "WHERE (end_time BETWEEN '$ws' AND '$we')";
			}
			$timespan_set = true;
		} else {
			$timespan_set = false;
		}

		if (get_request_var('exec_host') == -1 || (get_request_var('status') == 'PEND' ||  get_request_var('status') == 'PSUSP')) {
			if ($pend_reason_flag) {
				$jobs_query = "SELECT " . build_jobs_select_list($table_name, $table_name) . "
					FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$join_reason_sql
					$sql_where" . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql;
				$rowsquery = "SELECT COUNT(clusterid) AS total
				FROM (SELECT " . build_jobs_select_list($table_name, $table_name) . "
				FROM" . (orderby_clustername() ? "grid_clusters,":"") . "$join_reason_sql
				$sql_where" . (strlen($sql_where) ? ' AND ' : ' WHERE ') . $where_reason_sql . ") AS temp";
			} else {
				$jobs_query = "SELECT " . build_jobs_select_list($table_name, $table_name) . "
					FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$table_name
					$sql_where";
				$rowsquery = "SELECT COUNT($table_name.clusterid) AS total
				FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$table_name
				$sql_where";
			}

			if ($apply_limits) {
				$jobs_query .= " LIMIT " . ($rows*(get_request_var('page') -1)) . "," . $rows;
			}
		} else {
			if (strlen($sql_where)) {
				$sql_where1 = $sql_where . " AND (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
				if ($non_single_exechost)
					$sql_where2 = $sql_where . " AND (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
			} else {
				$sql_where1  = "WHERE (exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes <= 1)";
				if ($non_single_exechost)
					$sql_where2  = "WHERE (grid_jobs_jobhosts.exec_host='" . get_request_var('exec_host') . "' AND grid_jobs.num_nodes > 1)";
			}

			if ($table_name == "grid_jobs"){
				$union_sql = 'UNION';
			} else {
				$union_sql = 'UNION ALL';
			}
			/*Fix ProblemDB#235038 No Row found*/
			$rowsquery = "SELECT COUNT(a.clusterid) AS total
					FROM (SELECT clusterid, jobid, indexid, exec_host, submit_time from $table_name $sql_where1 "
					. ($non_single_exechost ? "
					$union_sql (SELECT grid_jobs_jobhosts.clusterid, grid_jobs_jobhosts.jobid, grid_jobs_jobhosts.indexid, grid_jobs_jobhosts.exec_host, grid_jobs_jobhosts.submit_time
					FROM " . (orderby_clustername() ? "grid_clusters,":"") . " grid_jobs_jobhosts
					INNER join $table_name
					ON $table_name.jobid=grid_jobs_jobhosts.jobid
					AND $table_name.indexid=grid_jobs_jobhosts.indexid
					AND $table_name.clusterid=grid_jobs_jobhosts.clusterid
					AND $table_name.submit_time=grid_jobs_jobhosts.submit_time
					$sql_where2 )" : "" )
					. ") AS a";
			$jobs_finished_query_array = array();
			$jobs_query = grid_jobs_make_sql_union($table_name, $sql_where1, $sql_where2, $apply_limits, $rows, $jobs_finished_query_array);
		}
	}

	if ($table_name == "grid_jobs") {
		$jobsquery = $jobs_query;
	} elseif ($table_name == "grid_jobs_finished") {
		$jobsquery = $jobs_finished_query;
	}

	//When host group filter is applied, job SQL query needs to union table grid_jobs_jobhosts, grid_jobs_jobhosts_finished and its partition tables
	//for fetching parallel jobs running on multiple hosts
	if ((get_request_var('hgroup') != '-1') && (get_request_var('status')!='PEND')) {
		grid_jobs_host_group ($table_name, get_request_var('hgroup'), $apply_limits, $rows, $jobsquery, $rowsquery, $jobs_finished_query_array);
	}

	//Final workaround to remove one of "DISTINCT" and "UNION"
	if (stripos($jobsquery, 'UNION') !== false
		&& stripos($jobsquery, 'UNION ALL') === false
		&& stripos($jobsquery, 'DISTINCT') !== false) {
		$jobsquery = str_ireplace(' DISTINCT ', ' ', $jobsquery);
	}

	//cacti_log("DEBUG: jobsquery: " . str_replace("\n", " ", $jobsquery));
}

function str_replace_last($search, $replace, $str) {
	if (($pos = strrpos($str, $search)) !== false) {
		$search_length = strlen($search);
		$str = substr_replace($str, $replace, $pos, $search_length);
	}
	return $str;
}

function grid_jobs_host_group($table_name, $hostgroup, $apply_limits, $rows, &$jobsquery, &$rowsquery, $jobsqueryarray = array()) {
	global $ws_abrev, $we_abrev;

	$non_single_exechost = (read_config_option("grid_jobhosts_collection") == "on");

	$ifclusterid = (get_request_var('clusterid')>0 ? "AND clusterid=" .  get_request_var('clusterid') . " ":"");
	$fields = str_replace("$table_name.","jobs.", build_jobs_select_list($table_name, $table_name, false, true));

	if ($apply_limits) {
		$limit_query = " LIMIT " . ($rows*(get_request_var('page') -1)) . "," . $rows;
		if (cacti_sizeof($jobsqueryarray) > 0) {
			foreach ($jobsqueryarray as $partno => $jobsqueryitem) {
				$jobsqueryarray[$partno] = str_replace_last($limit_query, " ", $jobsqueryitem);
			}
		} else {
			$jobsquery = str_replace_last($limit_query, " ", $jobsquery);
		}
	}

	$new_where = "WHERE exec_host IN (SELECT host FROM grid_hostgroups WHERE groupName='$hostgroup' $ifclusterid )";
	if ($non_single_exechost) {
		$new_where .= " AND num_nodes=1";
	}

	$new_jobsquery = "";
	if($table_name != 'grid_jobs'){
		if(cacti_sizeof($jobsqueryarray)){
			foreach ($jobsqueryarray as $partno => $jobsqueryitem) {
				$new_jobsquery .= ($new_jobsquery == "" ? "" : " UNION ALL ") . "SELECT $fields FROM ($jobsqueryitem) AS jobs $new_where";
			}
		}
	} else {
		$new_jobsquery = "SELECT $fields FROM ($jobsquery) AS jobs $new_where";
	}

	if ($non_single_exechost) {
		if($table_name != 'grid_jobs'){
			$grid_jobs_hosts_tables = array('grid_jobs_jobhosts');
		} else {
			$grid_jobs_hosts_tables = array('grid_jobs_jobhosts');
			if(cacti_sizeof($jobsqueryarray) > 0){
				$grid_jobs_hosts_tables[] = 'grid_jobs_jobhosts_finished';
			}
		}

		$grid_jobs_hosts_partition_tables = array();
		if ($table_name != "grid_jobs" && read_config_option("grid_partitioning_enable")) {
			$grid_jobs_hosts_partition_tables  = partition_get_partitions_for_query('grid_jobs_jobhosts_finished', $ws_abrev, $we_abrev);
		}

		if (cacti_sizeof($grid_jobs_hosts_partition_tables)) {
			foreach ($grid_jobs_hosts_partition_tables as $grid_jobs_hosts_partition_table) {
				if ($grid_jobs_hosts_partition_table != $grid_jobs_hosts_tables[0]){
					$grid_jobs_hosts_tables[] = $grid_jobs_hosts_partition_table;
				}
			}
		}

		//Note: $table_name = grid_jobs include two cases
		//   1. Active job query, two SQL clause from grid_jobs w/o grid_jobs_jobhosts
		//   2. All/Started job query, four SQL clauses from grid_jobs w/o grid_jobs_jobhosts,
		//      and grid_jobs_finished and grid_jobs_jobhosts_finished
		$jobsquery_tbl = $jobsquery;
		foreach ($grid_jobs_hosts_tables as $table) {
			if ((strlen($table) - strrpos($table, '_v')) == 5) {
				$partno = substr($table, -4);
			} else if($table == 'grid_jobs_jobhosts_finished'){
				$partno = "orig";
			}

			if(cacti_sizeof($jobsqueryarray) > 0 && $table != 'grid_jobs_jobhosts'){
				if (isset($jobsqueryarray[$partno])) {
					$jobsquery_tbl = $jobsqueryarray[$partno];
				} else {
					continue;
				}
			}

			$sql_join = "INNER join $table
				ON jobs.jobid=$table.jobid
				AND jobs.indexid=$table.indexid
				AND jobs.clusterid=$table.clusterid
				AND jobs.submit_time=$table.submit_time ";

			$new_jobsquery .= ($new_jobsquery == "" ? "" : " UNION ALL ") . "SELECT $fields FROM ($jobsquery_tbl) AS jobs " .
				$sql_join .
				"WHERE $table.exec_host IN (SELECT host FROM grid_hostgroups where groupName='$hostgroup' $ifclusterid ) AND num_nodes>1 ";
		}
	}

	$jobsquery = $new_jobsquery;
	$rowsquery = "SELECT COUNT(jobid) FROM ($new_jobsquery) AS a";
}

/**
  *
  * @sql_where2: where sub-clause for grid_jobs_jobhosts
  */
  function grid_jobs_make_sql_union($table_name, $sql_where1, $sql_where2, $apply_limits, $rows, &$query_arr = array()) {
	global $ws_abrev, $we_abrev;

	$non_single_exechost = (read_config_option("grid_jobhosts_collection") == "on");

	$sql_query1 = ""; //query job by grid_jobs
	$sql_query2 = ""; //query job by grid_jobs_jobhosts for exec_host/host_group/....
	$sql_query = "";

	$fields = build_jobs_select_list($table_name, $table_name);

	if ($table_name != "grid_jobs" && read_config_option("grid_partitioning_enable")) {
		$union_tables = partition_get_partitions_for_query($table_name, $ws_abrev, $we_abrev);
		$sql_query1 = "";
		if (cacti_sizeof($union_tables)) {
			foreach ($union_tables as $table) {
				/* if table does have app column and app filter is selected, don't need to get any data from this table */
				if (table_contains_app($table) == false and get_request_var('app') != -1) {
					continue;
				}

				$partno = "orig";
				if ((strlen($table) - strrpos($table, '_v')) == 5) {
					$partno = substr($table, -4);
				}

				$fields = build_jobs_select_list($table, $table_name);
				$query = "SELECT " . $fields . " FROM " . (orderby_clustername() ? "grid_clusters,":"") . $table . " " . (str_replace($table_name, $table, $sql_where1));

				if (strlen($sql_query1)) {
					$sql_query1 .= " UNION ALL " . $query;
				} else {
					$sql_query1  = $query;
				}

				$query_arr[$partno] = $query;
			}
		}
	} else {
		$sql_query1 = "SELECT $fields
			FROM " . (orderby_clustername() ? "grid_clusters,":"") . "$table_name
			$sql_where1";
	}


	if ($non_single_exechost) {
		if ($table_name != "grid_jobs" && read_config_option("grid_partitioning_enable")) {
			$union_tables  = partition_get_partitions_for_query($table_name, $ws_abrev, $we_abrev);
			$sql_query2 = "";

			if (cacti_sizeof($union_tables)) {
				foreach ($union_tables as $table) {
					/* if table does have app column and app filter is selected, don't need to get any data from this table */
					if (table_contains_app($table) == false and get_request_var('app') != -1) {
						continue;
					}

					$partno = "orig";
					if ((strlen($table) - strrpos($table, '_v')) == 5) {
						$partno = substr($table, -4);
					}

					$fields2 = build_jobs_select_list($table, $table_name);

					$sql_join = " INNER join grid_jobs_jobhosts
						ON $table.jobid=grid_jobs_jobhosts.jobid
						AND $table.indexid=grid_jobs_jobhosts.indexid
						AND $table.clusterid=grid_jobs_jobhosts.clusterid
						AND $table.submit_time=grid_jobs_jobhosts.submit_time " .
						str_replace($table_name, $table, $sql_where2);

					$query = "SELECT " . $fields2 . " FROM " . (orderby_clustername() ? "grid_clusters, ":"") . $table . $sql_join;

					/* jobhosts generation and version will be the same as the finished table */
					$jobhosts = 'grid_jobs_jobhosts_finished' . str_replace($table_name, "", $table);
					if (check_partitioned_table($jobhosts)) {
						/* do nothing */
					} else {
						$jobhosts = 'grid_jobs_jobhosts_finished';
					}

					$query = str_replace('grid_jobs_jobhosts', $jobhosts, $query);

					if (strlen($sql_query2)) {
						$sql_query2 .= " UNION ALL " . $query;
					} else {
						$sql_query2  = $query;
					}

					$query_arr[$partno] = $query;
				}
			}
		} else {
			$fields2 = build_jobs_select_list($table_name, $table_name);

			$sql_query2 = "SELECT $fields2
				FROM " . (orderby_clustername() ? "grid_clusters,":"") . " grid_jobs_jobhosts
				INNER join $table_name
				ON $table_name.jobid=grid_jobs_jobhosts.jobid
				AND $table_name.indexid=grid_jobs_jobhosts.indexid
				AND $table_name.clusterid=grid_jobs_jobhosts.clusterid
				AND $table_name.submit_time=grid_jobs_jobhosts.submit_time
				$sql_where2";
		}
	}

	$sql_query1=trim($sql_query1);
	$sql_query2=trim($sql_query2);

	if (strlen($sql_query1)) {
		$sql_query  = $sql_query1;
		if (strlen($sql_query2)) {
			if ($table_name == "grid_jobs"){
				$sql_query  .= " UNION $sql_query2";
			}else{
				$sql_query  .= " UNION ALL $sql_query2";
			}
		}
	} elseif (strlen($sql_query2)) {
		$sql_query  = $sql_query2;
	}
	if (!strlen($sql_query)) return "";

	if (get_request_var('exec_host')  == -1) {
		$sql_query .= get_order_string();
	}

	if ($apply_limits) {
		$sql_query .= " LIMIT " . ($rows*(get_request_var('page') -1)) . "," . $rows;
	}

	return $sql_query;
}

function table_contains_app($table) {
	$cols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table"), "Field", "Field");
	if (in_array("app", $cols)) {
		return true;
	}
	return false;
}

function build_select_list($table, $reference_table, $plugin_name='grid') {
	$fields = "";

	$rcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $reference_table"), "Field", "Field");
	$tcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table"), "Field", "Field");
	if (cacti_sizeof($rcols)) {
		foreach ($rcols as $col) {
			if (($plugin_name == 'heuristics') && ($col == 'last_updated')) continue;
			if (isset($tcols[$col])) {
				$fields .= (strlen($fields) ? ",":" ") . "$table.$col";
			} else {
				$fields .= (strlen($fields) ? ",":" ") . "'N/A' AS $col";
			}
		}
	}

	return $fields;
}

/**
  * @table: Target query table, field list might be less than @reference_table, Output 'N/A' if field is not in @table;
  * @reference_table: Field list is the latest version;
  * @full:   true: Return full field list of @reference_table;
  *         false: Return fixed field list plus selected field list in user grid settings
  * @outer: Ignore calc field for outer SQL field list;
  */
function build_jobs_select_list($table, $reference_table, $full = false, $outer = false) {
	$fields = '';

	$rcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $reference_table"), 'Field', 'Field');
	$tcols  = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table"), 'Field', 'Field');

	if (!$full) {
		if (!isset_request_var('export')) {
			$jcols  = build_job_display_array();
		} else {
			$jcols  = grid_job_export_display_array();
		}
	}

	$fields = "$table.jobid, $table.indexid, $table.clusterid, $table.submit_time, $table.jobname, $table.start_time, $table.end_time, $table.pend_time, $table.run_time, $table.queue, $table.user, $table.userGroup, $table.options, $table.options2, $table.exec_host, $table.dependCond, $table.pendReasons, $table.exitStatus, $table.exitInfo, $table.exceptMask, $table.num_cpus, $table.num_nodes, $table.maxNumProcessors, $table.max_memory, $table.mem_reserved, $table.projectName, $table.max_allocated_processes, $table.isLoaningGSLA";

	if (cacti_sizeof($rcols)) {
		foreach ($rcols as $col) {
			if ($full && !preg_match('/^(jobid|indexid|clusterid|submit_time|jobname|start_time|end_time|pend_time|run_time|queue|user$|userGroup|options|options2|exec_host|dependCond|pendReasons|exitStatus|exitInfo|exceptMask|num_cpus|num_nodes|maxNumProcessors|max_memory|mem_reserved|projectName|isLoaningGSLA)$/', $col)) {
				if (isset($tcols[$col])) {
					$fields .= (strlen($fields) ? ', ':' ') . "$table.$col";
				} else {
					$fields .= (strlen($fields) ? ', ':' ') . "'N/A' AS $col";
				}
			} elseif (array_key_exists($col, $jcols) && !preg_match('/^(jobid|indexid|clusterid|submit_time|jobname|start_time|end_time|pend_time|run_time|queue|user$|userGroup|options|options2|exec_host|dependCond|pendReasons|exitStatus|exitInfo|exceptMask|num_cpus|num_nodes|maxNumProcessors|max_memory|mem_reserved|projectName|isLoaningGSLA)$/', $col)) {
				if (isset($tcols[$col])) {
					$fields .= (strlen($fields) ? ', ':' ') . "$table.$col";
				} else {
					$fields .= (strlen($fields) ? ', ':' ') . "'N/A' AS $col";
				}
			}
		}
	}

	if (!$full) {
		if (array_key_exists('wasted_memory', $jcols)) {
			if ($outer) {
				$fields .= ', wasted_memory';
			} else {
				$fields .= ', (CASE WHEN stat="PEND" THEN 0 ELSE mem_reserved-max_memory END) AS wasted_memory';
			}
		}
		if (array_key_exists('jobclustername', $jcols)) {
			if ($outer) {
				$fields .= ', jobclustername';
			} else {
				$fields .= (orderby_clustername() ? ', grid_clusters.clustername as jobclustername ':', "" AS jobclustername');
			}
		}
		if (array_key_exists('jobclusterid', $jcols)) {
			if ($outer) {
				$fields .= ', jobclusterid';
			} else {
				$fields .= ", $table.clusterid as jobclusterid";
			}
		}
		if (array_key_exists('jobPriority', $jcols)) {
			/* 
			 * jobPriority has been added to $fields via the "foreach" above.
			 * Only add userPriority here.
			 */
			$fields .= ", $table.userPriority"; 
		}
	}

	//$fields = 'DISTINCT ' . $fields;

	return $fields;
}

/**
 * Never used
 */
function build_jobs_full_select_list($table, $reference_table) {
	return build_jobs_select_list($table, $reference_table, true) .
		", (CASE WHEN stat='PEND' THEN 0 ELSE mem_reserved-max_memory END) AS wasted_memory
		, $table.clusterid as jobclusterid
		" . (orderby_clustername() ? ", grid_clusters.clustername as jobclustername ":", '' AS jobclustername");
}

function orderby_clustername() {
	$page = get_order_string_page();

	if (isset($_SESSION['sort_data'][$page])) {
		if (in_array('jobclustername', $_SESSION['sort_data'][$page])) {
			return true;
		}
	}

	return false;
}

function union_grids($grid_jobs_query, $grid_jobs_finished_query, $apply_limits = true, $rows = 30, &$total_rows = 0) {
	$sort_order = get_order_string();

	//if ((get_request_var('status')  == 'ALL') && (get_request_var('exec_host')  != -1))
	$sql_query = trim($grid_jobs_query);
	if (strlen($grid_jobs_finished_query)) {
		$pos = stripos($grid_jobs_finished_query, 'UNION ALL');
		if($pos ===  false){//only one finished table
			$sql_query .= (strlen($sql_query) ? ' UNION ' : '') . $grid_jobs_finished_query . $sort_order;
		}else{//multiple finished tables
			if(strlen($sql_query)){
				$finished_sql = substr($grid_jobs_finished_query, 0, $pos);
				$left_sql = substr($grid_jobs_finished_query, $pos);
				$sql_query = "SELECT * FROM ($sql_query UNION $finished_sql) AS tmp2table $left_sql $sort_order";
			}else{
				$sql_query = $grid_jobs_finished_query . $sort_order;
			}
		}
	}

	if (read_config_option('grid_global_opts_jobs_pageno') == 'on' && read_grid_config_option('default_grid_jobs_pageno') == 'on') {
		$total_rows = db_fetch_cell("SELECT COUNT(*) FROM ($sql_query) AS a");
	}

	if ($apply_limits) {
		if (get_request_var('rows') == -1) {
			$rows_per_page = read_grid_config_option('grid_records');
		} elseif (get_request_var('rows') == -2) {
			$rows_per_page = 99999999;
		} else {
			$rows_per_page = get_request_var('rows');
		}

		$sql_query .= ' LIMIT ' . ($rows_per_page*(get_request_var('page') -1)) . ',' . $rows;
	}

	return $sql_query;
}

function do_import($xml_file) {
	return rtm_do_import($xml_file);
}

function do_insert($result, $data_source_friendly, $counter=0) {
	$array_key = array_keys($result['graph_template'][0]['dep']);
	$title = $result['graph_template'][0]['title'];

	foreach ($array_key as $arraykey) {
		$explode_arraykey = explode('_', $arraykey);
		if (substr_compare($explode_arraykey[1], '08', 0, 2) == 0) {
			$hash = substr($explode_arraykey[1], 6);
			$get_id = db_fetch_row_prepared("SELECT id, data_template_id, data_source_name FROM data_template_rrd where hash=?", array($hash));
			db_execute("INSERT INTO thold_template
				(id, name, data_template_id, data_template_name, data_source_id, data_source_name, data_source_friendly,
				time_hi, time_low, time_fail_trigger, time_fail_length, thold_hi, thold_low, thold_fail_trigger,
				thold_enabled, thold_type, bl_enabled, bl_ref_time, bl_ref_time_range, bl_pct_down, bl_pct_up,
				bl_fail_trigger, bl_fail_count, bl_alert, repeat_alert, notify_default, notify_extra, data_type,
				cdef, percent_ds, exempt, restored_alert, reset_ack, email_body, syslog_priority, syslog_facility,
				syslog_enabled)
				VALUES (".$counter.", '".$title." [".$get_id['data_source_name'] ."]',".$get_id['data_template_id'].", '".$title."',
				".$get_id['id'].", '".$get_id['data_source_name']."', '".$data_source_friendly."',
				'', '', 1, 1, '10', '', 1, 'on', 0, 'off', 86400, 10800, NULL, NULL, 3,NULL, NULL, 15, NULL, '', 0, 0,'".$get_id['data_source_name']."', 'off', 'off', 'off', '<html><body>Alert name: <THRESHOLDNAME><br>Details: <DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html><html><body>Alert name: <THRESHOLDNAME><br>Details: <DETAILS_URL><br><br><SUBJECT><br><br><GRAPH></body></html>', NULL, NULL, '')");
		}
	}
}

function get_appkey() {
	$link = file(RTM_ROOT . '/etc/.appkey');

	foreach ($link as $link_num => $line) {
		$line_value = trim($line);
	}
	return $line_value;
}

function update_queues_dispatch_times() {
	grid_debug('Updating queue dispatch times...');

	$grid_poller_start = read_config_option('grid_poller_start');
	$scan_date = $grid_poller_start;

	if (empty($scan_date)) {
		$scan_date = date('Y-m-d H:i:00');
		cacti_log('WARNING: Default Scan Date Used', false, 'GRID');
	}

	$poller_frequency = read_config_option('poller_interval');

	if (empty($poller_frequency)) {
		$poller_frequency = 300;
	}

	$previous_date = read_config_option('grid_poller_prev_start');

	if (empty($previous_date)) {
		$previous_date = date('Y-m-d H:i:00', strtotime($scan_date) - $poller_frequency);
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log('WARNING: Default Previous Scan Date Used', false, 'GRID');
		}
	}

	grid_debug('reset all queues dispatch times to 0');

	db_execute('UPDATE grid_queues SET avg_disp_time=0, max_disp_time=0');

	$clust_arr   = db_fetch_assoc("SELECT clusterid FROM grid_clusters WHERE disabled=''");

	if (cacti_sizeof($clust_arr)) {
		foreach ($clust_arr as $cl) {
			$sql_params = array();
			$sql_params[] = $cl["clusterid"];
			$sql_params[] = $previous_date;

			$rows = db_fetch_assoc_prepared("SELECT clusterid, queue,
				AVG(UNIX_TIMESTAMP(start_time)-UNIX_TIMESTAMP(submit_time)) AS avgdisp,
				MAX(UNIX_TIMESTAMP(start_time)-UNIX_TIMESTAMP(submit_time)) AS maxdisp
				FROM grid_jobs
				WHERE stat in ('RUNNING', 'PROV', 'DONE', 'EXIT')
				AND clusterid=?
				AND start_time >=?
				GROUP BY clusterid, queue;", $sql_params);

			if (cacti_sizeof($rows)) {
				// update the dispatch times
				foreach ($rows as $r) {
					$avg_disp = round($r["avgdisp"], 0);
					$max_disp = round($r["maxdisp"], 0);

					db_execute_prepared("UPDATE grid_queues SET
						avg_disp_time=?,
						max_disp_time=?
						WHERE queuename=?
						AND clusterid=?", array($avg_disp, $max_disp, $r["queue"], $r["clusterid"]));

				}
			} else {
				grid_debug('No jobs for dispatch times in cluster id:' . $cl['clusterid']. '.');
			}
		}
	} else {
		grid_debug('No queues with jobs in all clusters, not updating dispatch times.');
	}
}

function update_cluster_tputs() {
	$date  = date('Y-m-d H:i:s', time()-3600);

	$clusterids = implode(', ', array_rekey(db_fetch_assoc('SELECT clusterid FROM grid_clusters WHERE disabled=""'), 'clusterid', 'clusterid'));
	if(empty($clusterids)) {
		return;
	}

	$stats = db_fetch_assoc_prepared("SELECT clusterid,
		SUM(CASE WHEN stat='STARTED' THEN slots_in_state ELSE 0 END) AS hourly_started_jobs,
		SUM(CASE WHEN stat='ENDED' THEN slots_in_state ELSE 0 END) AS hourly_done_jobs,
		SUM(CASE WHEN stat='EXITED' THEN slots_in_state ELSE 0 END) AS hourly_exit_jobs
		FROM grid_job_interval_stats
		WHERE interval_start>=?
		AND clusterid IN ($clusterids)
		GROUP BY clusterid", array($date));

	if (cacti_sizeof($stats)) {
		foreach ($stats as $s) {
			db_execute_prepared('UPDATE grid_clusters SET
				hourly_started_jobs = ?,
				hourly_done_jobs = ?,
				hourly_exit_jobs = ?
				WHERE clusterid = ?',
				array(
					$s['hourly_started_jobs'],
					$s['hourly_done_jobs'],
					$s['hourly_exit_jobs'],
					$s['clusterid']
				)
			);
		}
	}
}

function grid_format_minutes($time) {
	if ($time >= 60) {
		$time = $time / 60;
		if ($time >= 24) {
			$time = $time / 24;
			return __('%s days', round($time,1), 'grid');
		} else {
			return __('%s hrs', round($time,1), 'grid');
		}
	} else {
		return __('%s mins', round($time,1), 'grid');
	}
}

function check_partitioned_table($table) {
	$table_exist = db_fetch_assoc_prepared("SHOW TABLES LIKE ?", array($table));
	if (!empty($table_exist)) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * grid_get_rrdcfvalue($ds, $cf, $rrdfile, $start, $end)
 *
 * param @ds - RRDfile internal data source name example 'traffic_in'
 * param @cf - RRDfile consolidation function MAX, MIN, AVERAGE
 * param @rrdfile - The RRDfile to inspect
 * param @start - The start time in epoctime
 * param @end - The end time in epoctime
*/
function grid_get_rrdcfvalue($ds, $cf, $rrdfile, $start, $end) {
	if ($end - $start >= 86400*30) {
		$step = 1800;
	} else {
		$step = 300;
	}

	if (!preg_match('/(AVERAGE|MAX|MIN)/', $cf)) {
		print 'FATAL: Only AVERAGE, MAX, and MIN Consolidation Functions Permitted';
	}

	$xport_data = @rrdtool_execute("xport --start $start --end $end DEF:a=\"" .
		$rrdfile . "\":$ds:$cf XPORT:a --step $step --enumds", false, RRDTOOL_OUTPUT_STDOUT);

	if ($xport_data != '') {
		$xp_arr  = explode("\n", $xport_data);
		$average = '';
		$max     = '';
		$min     = '';
		$samples = 0;
		$total   = 0;

		if (cacti_sizeof($xp_arr)) {
			foreach ($xp_arr as $line) {
				if (strstr($line, '<row>')) {
					$v_start = strpos($line, '<v0>') + 4;
					$v_end   = strpos($line, '</v0>');

					$value = substr($line, $v_start, ($v_end - $v_start));

					if ($value != 'NaN') {
						if ($cf == 'AVERAGE') {
							$total += $value;
							$samples++;
						} elseif ($cf == 'MAX' && ($max == '' || $value > $max)) {
							$max = $value;
						} elseif ($cf == 'MIN' && ($min == '' || $value < $min)) {
							$min = $value;
						}
					}
				}
			}

			switch($cf) {
			case 'MAX':
				return number_format_i18n((float)$max,0,'.','');
				break;
			case 'MIN':
				return number_format_i18n((float)$min,0,'.','');
				break;
			case 'AVERAGE':
				return $total / $samples;
				break;
			}
		} else {
			return 'U';
		}
	}
}

function collect_pending_ignore_setting() {
	global $grid_settings, $grid_builtin_resources;

	$grid_settings['pendignore'] = array();

	$grid_settings['pendignore']['general_pending_ignore_header'] = array(
		'friendly_name' => __('General Setting(by RegExp)', 'grid'),
		'method' => 'spacer'
	);

	$grid_settings['pendignore']['general_pending_ignore'] = array(
		'friendly_name' => __('Ignore Pending Reason by', 'grid'),
		'method' => 'textbox',
		'size' => '50',
		'max_length' => '255',
		'default' => ''
	);

	$grid_settings['pendignore']['pendreason_header'] = array(
		'friendly_name' => __('Pending Reason', 'grid'),
		'method' => 'spacer'
	);

	$pendignores = db_fetch_assoc('SELECT reason_code, sub_reason_code, reason
		FROM grid_jobs_pendreason_maps
		WHERE issusp="0"
		ORDER BY reason');

	if (cacti_sizeof($pendignores)) {
		foreach ($pendignores as $pendignore) {
			$option_id = 'pend_' . $pendignore['reason_code'] . '_' . $pendignore['sub_reason_code'];

			if ($pendignore['sub_reason_code'] != -1) {
				$grid_settings['pendignore'][$option_id] = array(
					'friendly_name' => $pendignore['reason'] . ':' . $pendignore['sub_reason_code'],
					'method' => 'checkbox',
					'default' => ''
				);
			} else {
				$grid_settings['pendignore'][$option_id] = array(
					'friendly_name' => $pendignore['reason'],
					'method' => 'checkbox',
					'default' => ''
				);
			}
		}
	}

	$grid_settings['pendignore']['suspreason_header'] = array(
		'friendly_name' => __('Suspend Reason', 'grid'),
		'method' => 'spacer'
	);

	$pendignores = db_fetch_assoc('SELECT reason_code, sub_reason_code, reason
		FROM grid_jobs_pendreason_maps
		WHERE issusp="1"
		ORDER BY reason');

	if (cacti_sizeof($pendignores)) {
		foreach ($pendignores as $pendignore) {
			$option_id = 'susp_' . $pendignore['reason_code'] . '_' . $pendignore['sub_reason_code'];

			$grid_settings['pendignore'][$option_id] = array(
				'friendly_name' => $pendignore['reason'],
				'method' => 'checkbox',
				'default' => ''
			);
		}
	}

	$shared_resources = array_rekey(
		db_fetch_assoc("SELECT resource_name, description
			FROM grid_sharedresources
			LEFT JOIN grid_jobs_pendreason_maps
			ON resource_name=sub_reason_code
			WHERE resource_name IN ('" . implode("', '", array_keys($grid_builtin_resources)) . "')
			OR (
				sub_reason_code<>''
				AND sub_reason_code IS NOT NULL
			)
			GROUP BY resource_name
			ORDER BY resource_name"),
		'resource_name', 'description'
	);

	if (!cacti_sizeof($shared_resources)) {
		$shared_resources = $grid_builtin_resources;
	}

	$grid_settings['pendignore']['resource_header'] = array(
		'friendly_name' => __('Load Indices', 'grid'),
		'method' => 'spacer'
	);

	foreach ($shared_resources as $resname => $resdesc) {
		$grid_settings['pendignore']["resource_0_$resname"] = array(
			"friendly_name" => "$resname",
			"description" => "$resdesc",
			"method" => "checkbox",
			"default" => ""
		);
	}
}

function append_host_elim_name() {
	global $grid_settings;

	$elimarray = array();
	$elimvals  = db_fetch_assoc("SELECT DISTINCT resource_name
		FROM grid_hosts_resources
		WHERE resource_name NOT IN ('r15s','r1m','r15m','ut','pg','io','ls','it','tmp','swp','mem')
		AND host <> 'ALLHOSTS'
		ORDER BY resource_name");

	if (cacti_sizeof($elimvals)) {
		$elimarray = array(
			'hostelim_header' => array(
				'friendly_name' => __('Host Custom ELIM Values', 'grid'),
				'method' => 'spacer'
			)
		);
		foreach ($elimvals as $e) {
			$elimarray += array(
				'host_elim_' . $e['resource_name'] => array(
					'friendly_name' => __('%s ELIM Value', $e['resource_name'], 'grid'),
					'method' => 'checkbox',
					'default' => ''
				)
			);
		}
	}

	$grid_settings['host'] += $elimarray;
}

function save_pending_ignore_setting($field_name, $field_value) {
	$pend_reason_ids = explode('_', $field_name);
	if ($pend_reason_ids[1] != 'header') {
		if ($pend_reason_ids[0] == 'general') {
			set_grid_config_option($field_name, $field_value);
		} else {
			//cacti_log("DEBUG: Save pending ignore setting: name='" . $field_name . "'; value='" . get_request_var($field_name) . "'");
			$present = ($field_value == 'on' ? 1 : 0);
			$reason_code = $pend_reason_ids[1];
			$sub_reason_code = substr($field_name, strpos($field_name, "_", strpos($field_name, "_") + 1) + 1);
			switch($pend_reason_ids[0]) {
				case 'pend':
					$issusp = 0;
					break;
				case 'susp':
					$issusp = 1;
					break;
				case 'resource':
					$issusp = 0;
					break;
			}

			//user_id,issusp,reason,subreason,last_updated, present
			db_execute_prepared("REPLACE INTO grid_pendreasons_ignore
				(user_id, issusp, reason, subreason, last_updated, present)
				VALUES (?, ?, ?, ?, ?, ?)",
				array($_SESSION['sess_user_id'], $issusp, $reason_code, $sub_reason_code, date("Y-m-d H:i:s", time()), $present));
		}
	}
}

function query_pending_ignore_setting_value($field_name) {
	$pend_reason_ids = explode('_', $field_name);
	if ($pend_reason_ids[1] != 'header') {
		if ($pend_reason_ids[0] == 'general') {
			return read_grid_config_option($field_name, true);
		} else {
			$reason_code = $pend_reason_ids[1];
			$sub_reason_code = substr($field_name, strpos($field_name, "_", strpos($field_name, "_") + 1) + 1);
			switch($pend_reason_ids[0]) {
				case 'pend':
					$issusp = 0;
					break;
				case 'susp':
					$issusp = 1;
					break;
				case 'resource':
					$issusp = 0;
					break;
			}
		}

		$return_value = db_fetch_cell_prepared('SELECT COUNT(*)
			FROM grid_pendreasons_ignore
			WHERE issusp=? AND reason=? AND subreason=? AND user_id=? AND present=?',
			array($issusp, $reason_code, $sub_reason_code,  $_SESSION['sess_user_id'], '1'));

		if ($return_value > 0) {
			return 'on';
		}
	}

	return '';;
}

function create_pending_sqlwhere_suffix($job_pend_reason_filtes) {
	$job_pend_sqlwhere_suffix = "";
	if (cacti_sizeof($job_pend_reason_filtes) > 0) {
		$job_pend_sqlwhere_pend_suffix = "";
		$job_pend_sqlwhere_susp_suffix = "";

		$job_pend_in_pend = array();
		$job_pend_in_susp = array();
		foreach ($job_pend_reason_filtes as $pend_reason_code) {
			if ($pend_reason_code['issusp'] == '0') {

				if ($pend_reason_code['subreason'] == '' || $pend_reason_code['subreason'] == '-1') {
					$job_pend_in_pend[] = $pend_reason_code['reason'];
				} else {
					$job_pend_sqlwhere_pend_suffix .= " OR ";
					$job_pend_sqlwhere_pend_suffix .= " reason='" . $pend_reason_code['reason'] . "' AND subreason='" . $pend_reason_code['subreason'] . "'";
				}
			} else {
				if ($pend_reason_code['subreason'] == '0') {
					$job_pend_in_susp[] = $pend_reason_code['reason'];
				} else {
					$job_pend_sqlwhere_susp_suffix .= " OR ";
					$job_pend_sqlwhere_susp_suffix .= " reason='" . $pend_reason_code['reason'] . "' AND subreason='" . $pend_reason_code['subreason'] . "'";
				}
			}

		}
		reset($job_pend_reason_filtes);

		if (strlen($job_pend_sqlwhere_pend_suffix) > 0) {
			$job_pend_sqlwhere_pend_suffix = substr($job_pend_sqlwhere_pend_suffix, 3);
		}

		if (strlen($job_pend_sqlwhere_pend_suffix) > 0 && cacti_sizeof($job_pend_in_pend) > 0) {
			$job_pend_sqlwhere_pend_suffix .= " OR ";
		}

		if (cacti_sizeof($job_pend_in_pend) > 0) {
			$job_pend_sqlwhere_pend_suffix .= "reason IN (" . implode(",", $job_pend_in_pend) . ")";
		}

		if (strlen(trim($job_pend_sqlwhere_pend_suffix)) > 0) {
			$job_pend_sqlwhere_pend_suffix = "issusp = '0' AND (" . $job_pend_sqlwhere_pend_suffix . ")";
		}

		if (strlen($job_pend_sqlwhere_susp_suffix) > 0) {
			$job_pend_sqlwhere_susp_suffix = substr($job_pend_sqlwhere_susp_suffix, 3);
		}

		if (strlen($job_pend_sqlwhere_susp_suffix) > 0 && cacti_sizeof($job_pend_in_susp) > 0) {
			$job_pend_sqlwhere_susp_suffix .= " OR ";
		}

		if (cacti_sizeof($job_pend_in_susp) > 0) {
			$job_pend_sqlwhere_susp_suffix .= "reason IN (" . implode(",", $job_pend_in_susp) . ")";
		}
		if (strlen(trim($job_pend_sqlwhere_susp_suffix)) > 0) {
			$job_pend_sqlwhere_susp_suffix = "issusp = '1' AND (" . $job_pend_sqlwhere_susp_suffix . ")";
		}


		$job_pend_sqlwhere_suffix .= $job_pend_sqlwhere_pend_suffix;

		if (strlen(trim($job_pend_sqlwhere_suffix)) > 0 && strlen(trim($job_pend_sqlwhere_susp_suffix)) > 0) {
			$job_pend_sqlwhere_suffix .= " OR ";
		}

		$job_pend_sqlwhere_suffix .= $job_pend_sqlwhere_susp_suffix;

		if (strlen(trim($job_pend_sqlwhere_suffix)) > 0) {
			$job_pend_sqlwhere_suffix = " WHERE (" . $job_pend_sqlwhere_suffix . ")";
		}
	}

	//cacti_log("DEBUG:Job Pending SQL where is: " . $job_pend_sqlwhere_suffix);
	return $job_pend_sqlwhere_suffix;
}

function create_pending_filter_sqlwhere_ignore() {
	$job_pend_proc_sqlwhere_ignore = "";
	$job_pend_ignore_settings = db_fetch_assoc_prepared('SELECT issusp,reason,subreason
		FROM grid_pendreasons_ignore
		WHERE user_id=? AND present=?
		ORDER BY issusp, reason, subreason',
		array($_SESSION['sess_user_id'], '1'));

	if (cacti_sizeof($job_pend_ignore_settings) > 0) {
		$job_pend_proc_sqlwhere_pend_ignore = "";
		$job_pend_proc_sqlwhere_susp_ignore = "";

		$job_pend_ignore_res = array();
		$job_pend_ignore_susp = array();
		foreach ($job_pend_ignore_settings as $pend_ignore) {
			//cacti_log("DEBUG pend_ignore['issusp'] == '0': " . ($pend_ignore['issusp'] == '0'));
			if ($pend_ignore['issusp'] == '0') {
				if ($pend_ignore['reason'] == '0') {
					$job_pend_ignore_res[] = "'" . $pend_ignore['subreason'] . "'";
				} elseif (!in_array($pend_ignore['subreason'], $job_pend_ignore_res)) {
					$job_pend_proc_sqlwhere_pend_ignore .= " AND ";
					$job_pend_proc_sqlwhere_pend_ignore .= "(rrec.reason <> '" . $pend_ignore['reason'] . "'
															OR rrec.subreason <> '" . $pend_ignore['subreason'] . "')";
				}
			} else {
				if ($pend_ignore['subreason'] == '0') {
					$job_pend_ignore_susp[] = $pend_ignore['reason'];
				} else {
					$job_pend_proc_sqlwhere_susp_ignore .= " AND ";
					$job_pend_proc_sqlwhere_susp_ignore .= "(rrec.reason <> '" . $pend_ignore['reason'] . "'
															OR rrec.subreason <> '" . $pend_ignore['subreason'] . "')";
				}
			}
		}
		if (cacti_sizeof($job_pend_ignore_res) > 0) {
			$job_pend_proc_sqlwhere_pend_ignore .= " AND rrec.subreason NOT IN (" . implode(",", $job_pend_ignore_res) . ")";
		}

		//if (strlen($job_pend_proc_sqlwhere_pend_ignore) > 0) {
			$job_pend_proc_sqlwhere_pend_ignore = "(rrec.issusp = '0' " . $job_pend_proc_sqlwhere_pend_ignore . ")";
			$job_pend_proc_sqlwhere_ignore .= $job_pend_proc_sqlwhere_pend_ignore;
		//}

		if (cacti_sizeof($job_pend_ignore_susp) > 0) {
			$job_pend_proc_sqlwhere_susp_ignore .= " AND rrec.reason NOT IN (" . implode(",", $job_pend_ignore_susp) . ")";
		}

		//if (strlen($job_pend_proc_sqlwhere_susp_ignore) > 0) {
			$job_pend_proc_sqlwhere_susp_ignore = "(rrec.issusp = '1' " . $job_pend_proc_sqlwhere_susp_ignore . ")";
			if (strlen($job_pend_proc_sqlwhere_ignore) > 0) {
				$job_pend_proc_sqlwhere_ignore .= " OR ";
			}
			$job_pend_proc_sqlwhere_ignore .= $job_pend_proc_sqlwhere_susp_ignore;
		//}

		if (strlen($job_pend_proc_sqlwhere_ignore) > 0) {
			$job_pend_proc_sqlwhere_ignore = " (" . $job_pend_proc_sqlwhere_ignore . ")";
		}
	}

	$job_pend_ignore_regexp = read_grid_config_option('general_pending_ignore', true);

	if (strlen($job_pend_ignore_regexp) > 0) {
		if (strlen($job_pend_proc_sqlwhere_ignore) > 0) {
			$job_pend_proc_sqlwhere_ignore .= " AND ";
		}
		$job_pend_proc_sqlwhere_ignore .= " rmap.reason NOT RLIKE '" . $job_pend_ignore_regexp . "'";
	}
	//cacti_log("DEBUG:Job Pending Filter SQL where Ignore Setting is: " . $job_pend_proc_sqlwhere_ignore);
	return 	$job_pend_proc_sqlwhere_ignore;
}

/**
 * get Exception Status message by exceptMask or exitInfo;
 * @param unknown_type exceptMask
 * @param unknown_type $exitInfo
 * @return string message base on exceptMask or exitInfo
 */
function getExceptionStatus($exceptMask, $exitInfo) {
	global $grid_job_term_messages, $grid_job_exception_messages;

	$exception_msg = "";
	foreach ($grid_job_exception_messages as $expkey => $expmsg) {
		if ($exceptMask & $expkey) {
			$exception_msg .= SPACE . $expmsg;
		}
	}

	/* RTC#85157: Term unknown is also known ad 'DONE'. We should never display TERM_UNKNOWN */
	if(!isset($grid_job_term_messages[$exitInfo])) {
		$exception_msg .= $grid_job_term_messages[TERM_UNKNOWN];
	} else if($exitInfo != TERM_UNKNOWN) {
		$exception_msg .= $grid_job_term_messages[$exitInfo];
	}
	return $exception_msg;
}

function rebuild_queue_distrib_table() {
	global $config;

	$dqueues = array_rekey(db_fetch_assoc("SELECT DISTINCT queuename
		FROM grid_queues
		ORDER BY queuename"), "queuename", "queuename");

	$queues  = db_fetch_assoc("SELECT clusterid, queuename FROM grid_queues ORDER BY clusterid, queuename");
	$records = array();

	if (cacti_sizeof($queues)) {
		foreach ($queues as $queue) {
			$hosts = db_fetch_assoc_prepared("SELECT host
				FROM grid_queues_hosts
				WHERE clusterid=?
				AND queue=?", array($queue["clusterid"], $queue["queuename"]));

			if (cacti_sizeof($hosts)) {
			foreach ($hosts as $host) {
				$records[$queue["clusterid"] . "|" . strtolower($host["host"])][$queue["queuename"]] = 1;
			}
			}
		}
	}

	$csql       = "";
	$sql_prefix = "INSERT INTO grid_queues_distrib (clusterid, host";
	if (cacti_sizeof($dqueues)) {
		foreach ($dqueues as $queue) {
			$sql_prefix .= ", `$queue`";
			$csql       .= " `$queue` TINYINT DEFAULT 0,";
		}
		$sql_prefix .= ") VALUES ";
	}

	db_execute("DROP TABLE IF EXISTS grid_queues_distrib");
	db_execute("CREATE TABLE grid_queues_distrib (
		`clusterid` INTEGER UNSIGNED DEFAULT '0',
		`host` VARCHAR(64) NOT NULL DEFAULT '',
		$csql
		PRIMARY KEY (`clusterid`, `host`),
		KEY `host` (`host`))
		ENGINE = InnoDB
		COMMENT = 'Show Queue Distribution for Queue';");

	$sql = "";$i = 0;
	if (cacti_sizeof($records)) {
	foreach ($records as $key => $record) {
		$explode   = explode("|", $key);
		$clusterid = $explode[0];
		$host      = $explode[1];
		$sql      .= ($i == 0 ? "(":",(") . $clusterid . ",'" . $host . "'";

		foreach ($dqueues as $queue) {
			$sql .= "," . (isset($record[$queue]) ? "1":"0");
		}
		$sql      .= ")";
		$i++;

		if ($i % 100 == 0) {
			db_execute($sql_prefix . $sql);
			$sql = "";
			$i   = 0;
		}
	}
	}

	if ($i > 0) {
		db_execute($sql_prefix . $sql);
	}

	/* now get Unused hosts */
	db_execute("INSERT INTO grid_queues_distrib (clusterid, host)
		SELECT grid_hosts.clusterid, grid_hosts.host
		FROM grid_hosts
		LEFT JOIN grid_queues_hosts
		ON grid_hosts.host=grid_queues_hosts.host
		AND grid_hosts.clusterid=grid_queues_hosts.clusterid
		WHERE grid_queues_hosts.host IS NULL");

	db_execute("REPLACE INTO settings (name,value) VALUES ('grid_queue_distrib_lastrun', UNIX_TIMESTAMP())");
}

function purge_from_and_request_hosts() {
	global $cnn_id;

	grid_debug("Removing Stale Requested Hosts");
	db_execute("INSERT INTO grid_jobs_reqhosts_finished
		SELECT gjh.*
		FROM grid_jobs_reqhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL
		ON DUPLICATE KEY UPDATE host=VALUES(host)");

	db_execute("DELETE gjh.*
		FROM grid_jobs_reqhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL");

	$num_found = db_affected_rows();

	grid_debug("Removed '$num_found' Stale Requested Hosts");

	grid_debug("Removing Stale Job Hosts");
	db_execute("INSERT INTO grid_jobs_jobhosts_finished
		SELECT gjh.*
		FROM grid_jobs_jobhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL
		ON DUPLICATE KEY UPDATE exec_host=VALUES(exec_host)");

	db_execute("DELETE gjh.*
		FROM grid_jobs_jobhosts AS gjh
		LEFT JOIN grid_jobs AS gj
		ON gjh.clusterid=gj.clusterid
		AND gjh.jobid=gj.jobid
		AND gjh.indexid=gj.indexid
		AND gjh.submit_time=gj.submit_time
		WHERE gj.clusterid IS NULL");

	$num_found = db_affected_rows();

	grid_debug("Removed '$num_found' Stale Job Hosts");

	//ToDo: Also purge pendingreason/slaloadning table
}

function grid_set_defaults() {
	grid_set_default("grid_xport_rows");
}

function grid_set_default($name) {
	$current = db_fetch_cell_prepared("SELECT value FROM settings WHERE name=?", array($name));

	if ($current == "") {
		$current = read_config_option($name);
		if ($current != "") {
			db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, ?)", array($name, $current));
		}
	}
}

function base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Never used in grid plugin, should move to rtm_function.php later
 * @param string $data
 * @return string
 */
function base64url_decode($data) {
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function update_grid_host_alarm() {
	db_execute("UPDATE grid_hosts_alarm set present='0' where type=3");

	$grid_host_alarms=db_fetch_assoc("select * from grid_host_threshold where busyStop != 0 or busySched != 0");
	if (cacti_sizeof($grid_host_alarms)) {
	foreach ($grid_host_alarms as $grid_host_alarm) {
			if ($grid_host_alarm['resource_name']=='mem' || $grid_host_alarm['resource_name']=='swp') {
					$loadsched=display_memory($grid_host_alarm['loadSched']);
					$loadstop=display_memory($grid_host_alarm['loadStop']);
			} else {
					$loadsched=$grid_host_alarm['loadSched'];
					$loadstop=$grid_host_alarm['loadStop'];
			}
			if ($grid_host_alarm['busySched'] == 1 && $grid_host_alarm['busyStop'] ==1) {
					$name=$grid_host_alarm['hostname']. " " .$grid_host_alarm['resource_name'] . " LoadStop With " . $loadstop . " and LoadSched with " . $loadsched;
					$message=$grid_host_alarm['hostname']. " breaching LoadSched/LoadStop threshold";
					db_execute_prepared("INSERT INTO grid_hosts_alarm
				   (`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`present`)
				   VALUES (?, ?, ?,
				   ?, '3', ?, '1'
				   ) ON DUPLICATE KEY UPDATE name=VALUES(name),message=VALUES(message),present=1", array($grid_host_alarm['id'], $name, $grid_host_alarm['hostname'], $grid_host_alarm['clusterid'],  $message));

			} elseif ($grid_host_alarm['busySched'] == 1) {
					$name=$grid_host_alarm['hostname']. " " .$grid_host_alarm['resource_name'] . " LoadSched with " . $loadsched;
					$message=$grid_host_alarm['hostname']. " breaching LoadSched threshold";
					db_execute_prepared("INSERT INTO grid_hosts_alarm
				   (`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`present`)
				   VALUES (?, ?, ?,
				   ?, '3', ?, '1'
				   ) ON DUPLICATE KEY UPDATE name=VALUES(name),message=VALUES(message),present=1", array($grid_host_alarm['id'], $name, $grid_host_alarm['hostname'], $grid_host_alarm['clusterid'],  $message));


			} elseif ($grid_host_alarm['busyStop'] ==1 ) {
					$name=$grid_host_alarm['hostname']. " " .$grid_host_alarm['resource_name'] . " LoadStop with " . $loadstop;
					$message=$grid_host_alarm['hostname']. " breaching LoadStop threshold";
					db_execute_prepared("INSERT INTO grid_hosts_alarm
				   (`type_id`,`name`,`hostname`,`clusterid`,`type`,`message`,`present`)
				   VALUES (?, ?, ?,
				   ?, '3', ?, '1'
				   ) ON DUPLICATE KEY UPDATE name=VALUES(name),message=VALUES(message),present=1", array($grid_host_alarm['id'], $name, $grid_host_alarm['hostname'], $grid_host_alarm['clusterid'],  $message));


			} else {
					continue;
			}
	}
	}

	db_execute("DELETE from grid_hosts_alarm where type=3 and present=0");
}

function get_grid_job_hosts_total_rows($jobid, $indexid, $clusterid, $submit_time){
	return get_grid_job_x_rusage_total_rows($jobid, $indexid, $clusterid, $submit_time, "host");
}

function get_grid_job_gpus_total_rows($jobid, $indexid, $clusterid, $submit_time){
	return get_grid_job_x_rusage_total_rows($jobid, $indexid, $clusterid, $submit_time, "gpu");
}

function get_grid_job_x_rusage_total_rows($jobid, $indexid, $clusterid, $submit_time, $rusage_type = "job"){
	switch ($rusage_type){
		case 'job':	/* Job Level Rusage  */
			$tbl_name = "grid_jobs_rusage";
			$tbl_field = "COUNT(*)";
			break;
		case 'host':	/* Host Level Rusage  */
			$tbl_name = "grid_jobs_host_rusage";
			$tbl_field = "COUNT(DISTINCT host)";
			break;
		case 'gpu':	/* GPU Level Rusage  */
			$tbl_name = "grid_jobs_gpu_rusage";
			$tbl_field = "COUNT(DISTINCT host, gpu_id)";
			break;
	}

	if (read_config_option("grid_partitioning_enable") == "") {
        	$sql_query = "SELECT $tbl_field
        		FROM $tbl_name
        		WHERE jobid=" . $jobid . "
        			AND indexid=" . $indexid . "
        			AND submit_time='" . date("Y-m-d H:i:s", $submit_time) . "'
        			AND clusterid=" . $clusterid;
        	return db_fetch_cell($sql_query);
	}else{
		$query  = "";
		$tables = partition_get_partitions_for_query($tbl_name, $submit_time, date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ALL";
				}

				$query .= " SELECT $tbl_field as jobCnt
        				FROM $table
        				WHERE (jobid=" . $jobid . "
        				AND indexid=" . $indexid . "
        				AND submit_time='" . date("Y-m-d H:i:s", $submit_time) . "'
        				AND clusterid=" . $clusterid . "
					)";
			}
        		$sql_query = "SELECT SUM(jobCnt) FROM ($query) as temptbl";
        		return db_fetch_cell($sql_query);
		}else{
			return 0;
		}
	}
}

function parse_resreq_get($resreq,$rsect,$res) {
	$sect_start = strstr($resreq,$rsect);

	if (!$sect_start) {
		return false;
	}

	$res_str=substr($sect_start, 0, strpos($sect_start, ']'));

	if (empty($res_str) || $res_str == false) {
		return false;
	}

	if (strstr($res_str,$res)) {
		return true;
	} else {
		return false;
	}
}

function get_grid_job_hosts($rows) {
	$sql_params = array();
	if (read_config_option("grid_partitioning_enable") == "") {
		$sql_query = "SELECT host
			FROM grid_jobs_host_rusage
			WHERE jobid=?
			AND indexid=?
			AND submit_time=?
			AND clusterid=?
			GROUP BY host";
		$sql_params[] = get_request_var('jobid');
		$sql_params[] = get_request_var('indexid');
		$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
		$sql_params[] = get_request_var('clusterid');

			$sql_query .= " LIMIT " . ($rows*(get_request_var('page')-1)) . "," . $rows;

			$job_hosts = db_fetch_assoc_prepared($sql_query, $sql_params);

			return $job_hosts;
	} else {
		$query  = "";
		$tables = partition_get_partitions_for_query("grid_jobs_host_rusage", get_request_var('submit_time'), date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach ($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ";
				}

				$query .= "SELECT host
					FROM $table
					WHERE (jobid=?
					AND indexid=?
					AND submit_time=?
					AND clusterid=?
				)";
				$sql_params[] = get_request_var('jobid');
				$sql_params[] = get_request_var('indexid');
				$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
				$sql_params[] = get_request_var('clusterid');
			}

			$sql_query = " $query GROUP BY host LIMIT " . ($rows*(get_request_var('page')-1)) . "," . $rows;

			$job_hosts = db_fetch_assoc_prepared($sql_query, $sql_params);

			return $job_hosts;
		} else {
			return null;
		}
	}
}

/**
 * ! Deperacated method, do not use because option.grid_archive_rrd_location was removed.
 */
function get_grid_job_hosts_rraarchive($job, $row_limit, $pageno, &$total_rows = 0){
	$total_rows = 0;
	$job_hosts = null;
	return $job_hosts;
}

function get_grid_job_gpus($row_limit){
	$sql_params = array();
	if (read_config_option("grid_partitioning_enable") == "") {
        	$sql_query = "SELECT host, gpu_id"
	        	. " FROM grid_jobs_gpu_rusage"
        		. " WHERE jobid=" . $_REQUEST["jobid"]
        		. " AND indexid=" . $_REQUEST["indexid"]
        		. " AND submit_time='" . date("Y-m-d H:i:s", $_REQUEST["submit_time"]) . "'"
        		. " AND clusterid=" . $_REQUEST["clusterid"]
        		. " GROUP BY host, gpu_id";
		$sql_params[] = $_REQUEST['jobid'];
		$sql_params[] = $_REQUEST['indexid'];
		$sql_params[] = date("Y-m-d H:i:s", $_REQUEST['submit_time']);
		$sql_params[] = $_REQUEST['clusterid'];

        	$sql_query .= " LIMIT " . ($row_limit*($_REQUEST["page"]-1)) . "," . $row_limit;
        	$job_gpus = db_fetch_assoc_prepared($sql_query, $sql_params);
        	return $job_gpus;
	}else{
		$query  = "";
		$tables = partition_get_partitions_for_query("grid_jobs_gpu_rusage", $_REQUEST["submit_time"], date("Y-m-d H:i:s"));

		if (cacti_sizeof($tables)) {
			foreach($tables as $table) {
				if (strlen($query)) {
					$query .= " UNION ";
				}

				$query .= "SELECT host, gpu_id"
        				. " FROM $table"
        				. " WHERE (jobid=?"
        				. " AND indexid=?"
        				. " AND submit_time=?"
        				. " AND clusterid=?)";
				$sql_params[] = $_REQUEST['jobid'];
				$sql_params[] = $_REQUEST['indexid'];
				$sql_params[] = date("Y-m-d H:i:s", $_REQUEST['submit_time']);
				$sql_params[] = $_REQUEST['clusterid'];
			}
        	$sql_query = " $query GROUP BY host, gpu_id LIMIT " . ($row_limit*($_REQUEST["page"]-1)) . "," . $row_limit;
        	$job_gpus = db_fetch_assoc_prepared($sql_query, $sql_params);
        	return $job_gpus;
		}else{
			return null;
		}
	}
}

function grid_view_job_detail($job_page = 'grid_bjobs.php') {
	global $config, $job, $tz_is_changed, $current_user;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan;
	global $grid_job_pend_reasons_per_chart, $grid_job_pend_reasons_sortby;
	global $graph_template_type;

	/* ================= input validation ================= */
	get_filter_request_var('jobid');
	get_filter_request_var('indexid');
	get_filter_request_var('clusterid');
	get_filter_request_var('submit_time');
	get_filter_request_var('predefined_timespan');
	get_filter_request_var('predefined_timeshift');
	/* ==================================================== */

	/* clean up date1 string */
	if (isset_request_var('date1')) {
		set_request_var('date1', sanitize_search_string(get_request_var('date1')));
	}

	/* clean up date2 string */
	if (isset_request_var('date2')) {
		set_request_var('date2', sanitize_search_string(get_request_var('date2')));
	}

	/* clean up legend string */
	if (isset($_POST) && cacti_sizeof($_POST)) {
		set_request_var('legend', sanitize_search_string(get_request_var("legend", "")));
	} else {
		set_request_var('legend', sanitize_search_string(get_request_var('legend')));
	}

	load_current_session_value('legend', 'sess_jg_legend', '');
	load_current_session_value('tab', 'sess_grid_bjobs_tab', 'general');
	load_current_session_value('cluster_tz', 'sess_grid_bjobs_cluster_tz', '');
	load_current_session_value('legend', 'sess_grid_view_jobs_legend', '');

	if (get_request_var('legend') == 'on') set_request_var('legend', 'true');

	general_header();

	if (get_request_var('clusterid') > 0) {
		if (isset_request_var('cluster_tz') && (get_request_var('cluster_tz') == 'on' || get_request_var('cluster_tz') == 'true')) {
			$cluster_tz = db_fetch_cell_prepared("SELECT cluster_timezone
				FROM grid_clusters
				WHERE clusterid=?", array(get_request_var('clusterid')));

			if ($cluster_tz) {
				db_execute_prepared("SET SESSION time_zone=?", array($cluster_tz));
				date_default_timezone_set($cluster_tz);
				$tz_is_changed = true;
			} else {
				db_execute("SET SESSION time_zone='SYSTEM'");
			}
		} else {
			db_execute("SET SESSION time_zone='SYSTEM'");
		}
	} else {
		db_execute("SET SESSION time_zone='SYSTEM'");
	}
	if (!isempty_request_var('submit_time')) {
		$job = db_fetch_row_prepared("SELECT grid_jobs.*, grid_clusters.clustername
		FROM grid_clusters
		INNER JOIN (grid_jobs)
		ON (grid_clusters.clusterid = grid_jobs.clusterid)
		WHERE grid_jobs.jobid=?
		AND indexid=?
		AND submit_time=?
		AND grid_clusters.clusterid=?",
		array(get_request_var('jobid'), get_request_var('indexid'), date("Y-m-d H:i:s", get_request_var('submit_time')), get_request_var('clusterid')));
	}

	$job_pend_reasons_count = 0;
	$pendreason_tables = array();
	$pendreason_tables[0] = "grid_jobs_pendreasons";

	if (!cacti_sizeof($job) && !isempty_request_var('submit_time')) {
		if (read_config_option('grid_partitioning_enable') == '') {
			$job = db_fetch_row_prepared("SELECT grid_jobs_finished.*, grid_clusters.clustername
				FROM grid_clusters
				INNER JOIN (grid_jobs_finished)
				ON (grid_clusters.clusterid = grid_jobs_finished.clusterid)
				WHERE (grid_jobs_finished.jobid=?
				AND indexid=?
				AND submit_time=?
				AND grid_clusters.clusterid=?)",
				array(get_request_var('jobid'), get_request_var('indexid'), date("Y-m-d H:i:s", get_request_var('submit_time')), get_request_var('clusterid')));

			$pendreason_tables[0] = 'grid_jobs_pendreasons_finished';
		} else {
			/* add 3600 seconds to end time in order to compensate for partition rotations */
			if (isset_request_var('start_time') && isset_request_var('end_time') && get_request_var('end_time') > 0) {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', get_request_var('start_time')), date('Y-m-d H:i:s', get_request_var('end_time')+3600));
			} else {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', get_request_var('submit_time')), date('Y-m-d H:i:s'));
			}

			$query_prefix = '(SELECT * FROM ';
			$i = 0;

			if (cacti_sizeof($tables)) {
				foreach ($tables as $table) {
					$query_prefix = ' (SELECT '  . build_select_list($table, 'grid_jobs_finished') . ' FROM ';
					$sql_where = " WHERE ($table.jobid='" . get_request_var('jobid') . "'
						AND $table.indexid='" . get_request_var('indexid') . "'
						AND $table.submit_time='" . date("Y-m-d H:i:s", get_request_var('submit_time')) . "'
						AND $table.clusterid='" . get_request_var('clusterid') . "')) ";

					if ($i == 0) {
						$sql_query  = 'SELECT job_record.*, grid_clusters.clustername FROM (' . $query_prefix . $table . $sql_where;
					} else {
						$sql_query .= ' UNION ALL ' . $query_prefix . $table . $sql_where;
					}

					$pendreason_tables[$i] = str_replace('grid_jobs_finished', 'grid_jobs_pendreasons_finished', $table);

					$i++;
				}

				$sql_query = trim($sql_query) . ') AS job_record
					INNER JOIN (grid_clusters)
					ON (grid_clusters.clusterid = job_record.clusterid)';

				$job = db_fetch_row($sql_query);
			}
		}
	}

	/* handle multi-cluster */
	if (cacti_sizeof($job)) {
		$from_host = explode(':', $job['from_host']);

		if (cacti_sizeof($from_host) > 1) {
			$job['remote_jobid'] = $from_host[1];
			$from_host = explode('@', $from_host[0]);
			if (cacti_sizeof($from_host) > 1) {
				$job['from_host'] = $from_host[0];
				$job['remote_clusterid'] = db_fetch_cell_prepared("SELECT clusterid
					FROM grid_clusters
					WHERE UPPER(lsf_clustername)=?", array(strtoupper($from_host[1])));
			}
		}
	}

	if (cacti_sizeof($pendreason_tables) && cacti_sizeof($job)) {
		$pend_query_prefix = '(SELECT count(*) as pcount FROM ';
		$pend_sql_query = '';

		$j = 0;
		foreach ($pendreason_tables as $pend_table) {
			$table_in_db = db_fetch_cell_prepared("SHOW TABLES LIKE ?", array($pend_table));

			/* this may happen if partitioning was turned on in 2.1.2 so there are no matching grid_jobs_pendreasons_finished_v000 */
			if ($table_in_db == "") {
				continue;
			}

			$pend_sql_where = ' WHERE (clusterid=' . $job['clusterid'] . "
				AND jobid=" . $job["jobid"] . " AND indexid=" . $job["indexid"] . "
				AND submit_time='" . $job["submit_time"] . "'))";

			if ($j == 0) {
				$pend_sql_query = 'SELECT SUM(pcount) FROM (' . $pend_query_prefix . $pend_table . $pend_sql_where;
			} else {
				$pend_sql_query .= ' UNION ALL ' . $pend_query_prefix . $pend_table . $pend_sql_where;
			}
			$j++;
		}

		if ($pend_sql_query != '') {
			$pend_sql_query .= ') AS PendCount';
			$job_pend_reasons_count = db_fetch_cell($pend_sql_query);
		}
		else {
			$job_pend_reasons_count = 0;
		}
	}

	$job_rusage      = array();
	$job_gpu_rusages = array();
	if (cacti_sizeof($job) && (get_request_var('tab') == '' || get_request_var('tab') == 'general')) {
		if($job['stat'] == 'RUNNING' || $job['stat'] == 'SSUSP' || $job['stat'] == 'USUSP' || $job['end_time'] == '00-00-00 00:00:00'){
			$start_range = $job['start_time'];
		}else {
			$start_range = date('Y-m-d H:i:s', strtotime($job['end_time'])-3600);
		}
		$job_rusage = get_rusage_records(get_request_var('clusterid'), get_request_var('jobid'),
			get_request_var('indexid'), date("Y-m-d H:i:s", get_request_var('submit_time')), $start_range,
			-1, false, $job['last_updated'], $job['stat'] == 'RUNNING') ;
		$job_gpu_rusages = get_rusage_records_gpu_per_job(get_request_var('clusterid'), get_request_var('jobid'),
			get_request_var('indexid'), date("Y-m-d H:i:s", get_request_var('submit_time')), $start_range, $job['last_updated'], $job['stat'] == 'RUNNING');
	}

	if (cacti_sizeof($job) && (get_request_var('tab') == '' || get_request_var('tab') == 'general')
			|| (isset($job['stat']) && ($job['stat'] == 'PEND' || $job['stat'] == 'PSUSP')) || !isset($job['start_time']) || $job['start_time'] == '0000-00-00 00:00:00') {
		/* get the asked hosts list */
		$askedHosts = grid_get_askedHosts($job);
	}

	$exec_hosts = array();
	if (cacti_sizeof($job) && (get_request_var('tab') == '' || get_request_var('tab') == 'general' || get_request_var('tab') == 'hostgraph')) {
		/* get all execution hosts, if this was a multi CPU job */
		if (cacti_sizeof($job) && ((substr_count($job['exec_host']?:'', '*')) || ($job['num_nodes'] > 1) || ($job['maxNumProcessors'] > $job['num_cpus']))) {
			include_once($config['library_path'] . '/sort.php');
			$sql_params = array();
			if (read_config_option('grid_partitioning_enable') == '') {
				$sql_query = "SELECT processes, exec_host, isborrowed
					FROM grid_jobs_jobhosts
					WHERE jobid=?
					AND indexid=?
					AND submit_time=?
					AND clusterid=?";
				$sql_params[] = get_request_var('jobid');
				$sql_params[] = get_request_var('indexid');
				$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
				$sql_params[] = get_request_var('clusterid');

				$sql_query_finished = "SELECT processes, exec_host, isborrowed
					FROM grid_jobs_jobhosts_finished
					WHERE jobid=?
					AND indexid=?
					AND submit_time=?
					AND clusterid=?";
				$sql_params[] = get_request_var('jobid');
				$sql_params[] = get_request_var('indexid');
				$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
				$sql_params[] = get_request_var('clusterid');

				$query = $sql_query . ' UNION ' . $sql_query_finished;
				$exec_hosts = db_fetch_assoc_prepared($query, $sql_params);
			} else {
				$tables = partition_get_partitions_for_query('grid_jobs_jobhosts_finished', $job['submit_time'], $job['end_time']);
				$sql_query_finished = '';
				$sql_query_finished_2 = '';
				$sql_query = "SELECT processes, exec_host, isborrowed
					FROM grid_jobs_jobhosts
					WHERE jobid=?
					AND indexid=?
					AND submit_time=?
					AND clusterid=?";
				$sql_params[] = get_request_var('jobid');
				$sql_params[] = get_request_var('indexid');
				$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
				$sql_params[] = get_request_var('clusterid');

				foreach ($tables as $table) {
					$sql_query_finished = "SELECT processes, exec_host, isborrowed
						FROM $table
						WHERE jobid=?
						AND indexid=?
						AND submit_time=?
						AND clusterid=?";
					$sql_params[] = get_request_var('jobid');
					$sql_params[] = get_request_var('indexid');
					$sql_params[] = date("Y-m-d H:i:s", get_request_var('submit_time'));
					$sql_params[] = get_request_var('clusterid');

					if (strlen($sql_query_finished_2)) {
						$sql_query_finished_2 .= ' UNION '.$sql_query_finished;
					} else {
						$sql_query_finished_2 = $sql_query_finished;
					}
				}

				if (strlen($sql_query_finished_2)) {
					$query = $sql_query . ' UNION ' . $sql_query_finished_2;
				} else {
					$query = $sql_query;
				}
				$exec_hosts = db_fetch_assoc_prepared($query, $sql_params);
			}

			sort_by_subkey($exec_hosts, 'isborrowed');

			if (!substr_count($job['exec_host']?:'', '*')) {
				$job['exec_host'] = $job['max_allocated_processes'] . '*' . $job['exec_host'];
			}
		}
	}

	if (cacti_sizeof($job)) {
		/* set the detail title */
		if ($job['indexid'] > 0) {
			$display_name = api_plugin_hook_function('grid_jobs_jobname', $job['jobname'] . ' (' . $job['jobid'] . '[' . $job['indexid'] . '])');
		} else {
			$display_name = api_plugin_hook_function('grid_jobs_jobname', $job['jobname'] . ' (' . $job['jobid'] . ')');
		}
	}

	if (isset_request_var('tab') && get_request_var('tab') != 'general') {
		/* set variables for first time use */
		$timespan  = initialize_jg_timespan();
		$timeshift = grid_set_jg_timeshift();

		/* process the timespan/timeshift settings */
		process_jg_html_variables();
		process_jg_user_input($timespan, $timeshift);

		/* save session variables */
		finalize_jg_timespan($timespan, $job);

		/* determine default start and end times (this will change) */
		$date1      = $_SESSION['sess_jg_current_timespan_begin_now'];
		$date2      = $_SESSION['sess_jg_current_timespan_end_now'];
		$end_time   = $timespan['end_now'];
		$start_time = $timespan['begin_now'];

		if ((cacti_sizeof($job)) && ($job['stat'] != 'PEND')) {
			if ($end_time < $start_time) {
				$end_time   = '-150';
			} elseif (get_request_var('predefined_timespan') == '-99') {
				$now        = time();
				$start_time = strtotime($job['start_time']);
				if ($job['end_time'] > 0) {
					$end_time = strtotime($job['end_time']);
				} else {
					$end_time = $now;
				}
				$timespan['end_now']   = $end_time;
				$timespan['begin_now'] = $start_time;
			} else {
				$interval   = $date2 - $date1;
				$now        = time();

				if (($date2+5) > $now) {
					$preset = true;
				} else {
					$preset = false;
					$offset = $now - $date2;
				}

				/* set the times per the requirements */
				if ($end_time < $date2) {
					if ($preset) {
						$date1 = $end_time - $interval;

						if ($date1 > $start_time) {
							$start_time = $date1;
						}
					} else {
						if (($end_time - $offset) > $start_time) {
							$end_time = $end_time - $offset;
						}

						if (($end_time - $interval) > $start_time) {
							$start_time = $end_time - $interval;
						}
					}
				} else {
					$end_time = $date2;

					if (($end_time - $interval) > $start_time) {
						$start_time = $end_time - $interval;
					}
				}

				$timespan['end_now']   = $end_time;
				$timespan['begin_now'] = $start_time;

				set_request_var('predefined_timespan', GT_CUSTOM);
			}
		}
	}

	if (cacti_sizeof($job)) {
		$title = __('IBM Spectrum LSF RTM - Jobs Details for \'%s\'', $display_name, 'grid');

		$tabs_job = array('general' => __('Job Detail', 'grid'));

		if ($job['stat'] != 'PEND' && $job['stat'] != 'PSUSP' && $job['start_time'] != '0000-00-00 00:00:00') {
			$tabs_job['jobgraph'] = __('Job Graphs', 'grid');
			$tabs_job['hostgraph'] = __('Host Graphs', 'grid');
		} else {
			$grid_pending_rusage_collection = db_fetch_cell("SELECT value
				FROM settings
				WHERE name='grid_pending_rusage_collection'
				AND value='on'");

			if ($grid_pending_rusage_collection ) {
				$lsf_version = db_fetch_cell_prepared('SELECT grid_pollers.lsf_version
					FROM grid_pollers
					INNER JOIN grid_clusters
					ON grid_clusters.poller_id=grid_pollers.poller_id
					WHERE grid_clusters.clusterid=?', array($job['clusterid']));

				$temp_mem_reserved=0;

				if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'91')) {
					$temp_mem_reserved = parse_resreq_get($job['combinedResreq'], 'rusage', 'mem');
				} else {
					$temp_mem_reserved = parse_resreq_get($job['res_requirements'], 'rusage', 'mem');
				}

				if ($temp_mem_reserved) {
					$tabs_job['jobgraph'] = __('Job Graphs', 'grid');
				}
			} else {
				$rusage_records_exist = get_rusage_records_exist($job['jobid'], $job['indexid'], $job['clusterid'], $job['submit_time']);
				if (!empty($rusage_records_exist)) {
					$tabs_job['jobgraph'] = __('Job Graphs', 'grid');
				}
			}
		}

		if (isset($job_pend_reasons_count) && $job_pend_reasons_count > 0) {
			$tabs_job['jobpendhist'] = __('Pending Reasons', 'grid');
		}

		$tabs_job = api_plugin_hook_function('grid_jobs_tabs', $tabs_job);

		/* set the default settings category */
		if (!isset_request_var('tab')) {
			/* there is no selected tab; select the first one */
			$current_tab = array_keys($tabs_job);
			$current_tab = $current_tab[0];
		} else {
			if (array_key_exists(get_request_var('tab'),$tabs_job)) {
				$current_tab = get_request_var('tab');
			} else {
				$current_tab = array_keys($tabs_job);
				$current_tab = $current_tab[0];
			}
		}
	}

	/* draw the categories tabs on the top of the page */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";

	if (isset($tabs_job) && cacti_sizeof($tabs_job) && cacti_sizeof($job)) {
		$i = 0;
		foreach (array_keys($tabs_job) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == $current_tab) ? "class='selected pic'" : "class='pic'") . " href='" . html_escape($job_page .
				'?action=viewjob' .
				'&tab='           . $tab_short_name .
				'&clusterid='     . $job['clusterid'] .
				'&indexid='       . $job['indexid']   .
				'&jobid='         . $job['jobid']     .
				'&cluster_tz='    . get_request_var('cluster_tz') .
				'&submit_time='   . strtotime($job['submit_time']) .
				'&start_time='    . strtotime($job['start_time']) .
				'&end_time='      . strtotime($job['end_time'])) .
				"'>" . $tabs_job[$tab_short_name] . '</a></li>';
			$i++;
		}
	}

print '</ul></nav></div>';
print '</tr></table>';

	if (cacti_sizeof($job)) {
		if ($current_tab == 'general') {
			$i = 0;

			$exit_status = grid_get_exit_code($job['exitStatus']);

			$lsf_version = db_fetch_cell_prepared("SELECT grid_pollers.lsf_version
				FROM grid_pollers JOIN grid_clusters ON grid_clusters.poller_id=grid_pollers.poller_id
				WHERE grid_clusters.clusterid=?", array($job['clusterid']));

			html_start_box(__('General Information', 'grid'), '100%', '', "3", "center", "");

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Job ID', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['jobid']; if ($job['indexid'] > 0) print '[' . $job['indexid'] . ']'; if (isset($job['remote_jobid']) && isset($job['remote_clusterid'])) print ' (' . "<a class='pic linkEditMain nowrap' title='" . html_escape($job['remote_jobid']) . "' href='" . html_escape($job_page . '?action=viewjob&clusterid=' . $job['remote_clusterid'] . '&indexid=0&jobid=' . $job['remote_jobid'] . '&submit_time=' . strtotime($job['submit_time'])) . "' class='linkEditMain'>" . $job['remote_jobid'] . '</a>)'; ?>
				</td>
				<td width='15%'>
					<?php print __('Status', 'grid');?>
				</td>
				<td width='35%'>
				<?php
				$status_url = html_escape($job_page . '?action=viewlist&reset=1&clusterid=' . $job['clusterid'] .'&status=' . $job['stat'] . '&page=1');
				print "<a class='pic linkEditMain nowrap' href='$status_url' title='" . __esc('Show Jobs with the status', 'grid') . "'><span class='nowrap' id='status'>". $job['stat']. '</span></a>';
				?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Job Name', 'grid');?>
				</td>
				<td colspan='3' width='85%'>
					<?php print html_escape(str_replace(";", "; ", str_replace(":", ": ", api_plugin_hook_function("grid_jobs_jobname", grid_get_group_jobname($job)))));?>
				</td>
			<?php

			if (!empty($job['jobDescription'] )) {
			form_alternate_row();?>
				<td width='15%' >
					<?php print __('Job Description', 'grid');?>
				</td>
				<td colspan='3' width='85%' style="word-break:break-all;" >
					<?php print html_escape($job['jobDescription']);?>
				</td>
			</tr>
			<?php }

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Project', 'grid');?>
				</td>
				<td width='35%'>
					<?php
					$proj_showname = api_plugin_hook_function("job_project_name_show", $job);
					if (!is_array($proj_showname) && ($proj_showname != $job['projectName'])) {
						print $proj_showname;
					} else {
						grid_print_metadata('simple', 'project', $job['clusterid'], $job['projectName']);
					}?>
				</td>
				<td width='15%'>
					<?php print __('License Project', 'grid');?>
				</td>
				<td width='35%'>
					<?php grid_print_metadata('simple', 'license-project', $job['clusterid'], $job['licenseProject']); ?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Queue', 'grid');?>
				</td>
				<td width='35%'>
					<?php grid_print_metadata('simple', 'queue', $job['clusterid'], $job['queue']); ?>
				</td>
				<td width='15%'>
					<?php print __('Cluster Name', 'grid');?>
				</td>
				<td width='35%'>
					<?php grid_print_metadata('simple', 'cluster', $job['clusterid'], $job['clustername']); ?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('User', 'grid');?>
				</td>
				<td width='35%'>
				<?php
				$url =  $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $job['clusterid'] .
					'&job_user=' . $job['user'] .
					'&status=RUNNING&page=1';

				grid_print_metadata('detailed', 'user', $job['clusterid'], $job['user'], '', __esc('Show User Jobs', 'grid'), false, $url);
				?>
				</td>
				<td width='15%'>
					<?php print __('User Group', 'grid');?>
				</td>
				<td width='35%'>
					<?php grid_print_metadata('simple', 'user-group', $job['clusterid'], $job['userGroup']); ?>
				</td>
			</tr>
			<?php

			if (strlen($job['mailUser']) || strlen($job['sla'])) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Service Class', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['sla'];?>
				</td>
				<td width='15%'>
					<?php print __('Mail User', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['jobPriority'];?>
				</td>
			</tr>
			<?php }

			if ($job['userPriority'] > 0 || $job['jobPriority'] > 0) {
			form_alternate_row();
				if ($job['userPriority'] > 0) {?>
				<td width='15%'>
					<?php print __('User Priority', 'grid');?>
				</td>
				<td width='35%'>
					<?php print ($job['userPriority'] == -1 ? "N/A" : $job['userPriority']);?>
				</td>
				<?php }
				if ($job['jobPriority'] > 0) {?>
				<td width='15%'>
					<?php print __('Job Priority', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['jobPriority'];?>
				</td>
			</tr>
			<?php }
			}

			if ((isset($job['app']) && strlen($job['app'])) || strlen($job['jobGroup'])) {
				form_alternate_row();?>
				<td width='15%'>
					<?php print __('Application', 'grid');?>
				</td>
				<td width='35%'>
					<?php (strlen($job['app']) ? grid_print_metadata('simple', 'application', $job['clusterid'], $job['app']):'-'); ?>
				</td>
				<td width='15%'>
					<?php print __('Job Group', 'grid');?>
				</td>
				<td width='35%'>
					<?php (strlen($job['jobGroup']) ? grid_print_metadata('simple', 'job-group', $job['clusterid'], $job['jobGroup']):'-'); ?>
				</td>
			</tr>
			<?php }

			html_end_box(false);

			$i = 0;
			html_start_box("Submission Details", "100%", '', "3", "center", "");

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Submit Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['submit_time'];?>
				</td>
				<td width='15%'>
					<?php print __('Number of CPUs', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['num_cpus'];?>
				</td>
			</tr>
			<?php

			if ($job['num_gpus'] > 0) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('Number of GPUs', 'grid');?>
					</td>
					<td width='35%'>
						<?php print $job['num_gpus'];?>
					</td>
					<td width='15%'>
					</td>
					<td width='35%'>
					</td>
				</tr>
				<?php
			}

			if ((check_time($job['predictedStartTime'], true)) ||
				(check_time($job['beginTime'], true))) {
				form_alternate_row();?>
				<td width='15%'>
					<?php print __('Predicted Start Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print check_time($job['predictedStartTime']);?>
				</td>
				<td width='15%'>
					<?php print __('Begin Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print check_time($job['beginTime']);?>
				</td>
			</tr>
			<?php }

			if ((check_time($job['termTime'], true)) ||
				(check_time($job['reserveTime'], true))) {
				form_alternate_row();?>
				<td width='15%'>
					<?php print __('Termination Deadline', 'grid');?>
				</td>
				<td width='35%'>
					<?php print check_time($job['termTime']);?>
				</td>
				<td width='15%'>
					<?php print __('Reserved Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print check_time($job['reserveTime']);?>
				</td>
			</tr>
			<?php }

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Submit Host', 'grid');?>
				</td>
				<td width='35%'>
					<?php grid_print_metadata('simple', 'host', $job['clusterid'], $job['from_host']); ?>
				</td>
				<?php

				if (strlen($job['loginShell'])) { ?>
				<td width='15%'>
					<?php print __('Login Shell', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['loginShell']; ?>
				</td>
				<?php } else { ?>
				<td width='15%'>
				</td>
				<td width='35%'>
				</td>
				<?php } ?>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Asked Hosts/Groups', 'grid');?>
				</td>
				<td width='35%'>
					<?php print (isset($askedHosts) && strlen($askedHosts) ? $askedHosts:'-');?>
				</td>
				<td width='15%'>
					<?php print __('Runtime Estimate', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['runtimeEstimation'], 2, false);?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Combined ResReq', 'grid');?>
				</td>
				<td colspan="3" width='85%' style="word-break:break-all;" >
					<?php print ( (empty ($job['combinedResreq']))? '-' : $job['combinedResreq']);?>
				</td>
			</tr>
			<?php

			if (strlen($job['gpuCombinedResreq'])) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('Combined GPU ResReq', 'grid');?>
					</td>
					<td colspan="3" width='85%' style="word-break:break-all;" >
						<?php print ( (empty ($job['gpuCombinedResreq']))? '-' : $job['gpuCombinedResreq']);?>
					</td>
				</tr>
				<?php
			}

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Submit Command', 'grid');?>
				</td>
				<td colspan='3' width='85%' style="word-break:break-all;">
					<?php print str_replace(";", "<br>", str_replace(":", ": ", $job['command']));?>
				</td>
			</tr>
			<?php

			if (strlen($job['cwd'])) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('Submit Directory', 'grid');?>
					</td>
					<td colspan='3' width='85%'>
						<?php print $job['cwd']; ?>
					</td>
				</tr>
			<?php }

			api_plugin_hook('grid_show_job_command');

			if (strlen($job['res_requirements'])) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('Resource Requirements', 'grid');?>
					</td>
					<td colspan='3' width='85%' style="word-break:break-all;" >
						<?php print $job['res_requirements'];?>
					</td>
				</tr>
			<?php }

			if (strlen($job['gpuResReq'])) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('GPU ResReq', 'grid');?>
					</td>
					<td colspan="3" width='85%' style="word-break:break-all;" >
						<?php print ( (empty ($job['gpuResReq']))? '-' : $job['gpuResReq']);?>
					</td>
				</tr>
				<?php
			}

			if (strlen($job['dependCond'])) {
				form_alternate_row();?>
				<td width='15%'>
					<?php print __('Dependencies', 'grid');?>
				</td>
				<td colspan='3' width='85%'>
					<?php print $job['dependCond'];?>
				</td>
			</tr>
			<?php }

			if (strlen($job['outFile'])) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Output File', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $job['outFile'];?>
				</td>
			</tr>
			<?php }

			if (strlen($job['errFile'])) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Error File', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $job['errFile'];?>
				</td>
			</tr>
			<?php }

			if (strlen($job['inFile']) && $job['inFile'] != "/dev/null") {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Input File', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $job['inFile'];?>
				</td>
			</tr>
			<?php }

			if (strlen($job['preExecCmd'])) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Pre Exec', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $job['preExecCmd'];?>
				</td>
			</tr>
			<?php }

			if (strlen($job['postExecCmd'])) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Post Exec', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $job['postExecCmd'];?>
				</td>
			</tr>
			<?php }

			html_end_box(false);
			$i = 0;

			if ($job['pendReasons'] != "") {
			html_start_box("Pending/Suspended Reasons", "100%", '', "3", "center", "");

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Reasons', 'grid');?>
				</td>
				<td colspan=3 align="left" width='85%'>
					<?php print str_replace(";", "<br>", $job['pendReasons']);?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td nowrap style='white-space: nowrap;' width='15%'>
					<?php print __('Pending/Suspended Time', 'grid');?>
				</td>
				<td colspan=3 align="left" width='85%'>
					<?php
						if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1010')) {
							print display_job_time($job['pend_time'], 2, false);
						} else {
							print display_job_time(($job['pend_time'] + $job['ususp_time'] + $job['ssusp_time']), 2, false);
						}
					?>
				</td>
			</tr>
			<?php
			if (cluster_check_ineligible_pend_reason($job['clusterid']) > 0) {
			form_alternate_row();?>
				<td nowrap style='white-space: nowrap;'  width='15%'>
					<?php print __('Ineligible Pending Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['ineligiblePendingTime'], 2, false);?>
				</td>
				<td nowrap style='white-space: nowrap;' width='15%'>
					<?php print __('Ineligible Pending Reason?', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_ineligible_pend_reason($job['pendState']);?>
				</td>
			</tr>
			<?php
			}

			if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1010')) {
			form_alternate_row();?>
				<td nowrap style='white-space: nowrap;'  width='15%'>
					<?php print __('Effective Pending Time Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['effectivePendingTimeLimit'], 2, false);?>
				</td>
			<?php
			if (cluster_check_ineligible_pend_reason($job['clusterid']) > 0) { ?>
				<td nowrap style='white-space: nowrap;' width='15%'>
					<?php print __('Effective Eligible Pending Time Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['effectiveEligiblePendingTimeLimit'], 2, false);?>
				</td>
			<?php
			} else {
				print ("<td colspan='2' width='50%'></td>");
			}
			print ("</tr>");
			}
			html_end_box(false);
			}

			$i = 0;
			if ($job['stat'] != 'PEND') {
			html_start_box('Execution Environment', '100%', '', '3', 'center', '');

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Exec Host', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php
					$aster = strpos($job['exec_host'], '*');
					if ($aster) {
						$exec_host = substr($job['exec_host'], $aster+1);
					} else {
						$exec_host = $job['exec_host'];
					}

					$url = $config['url_path'] . 'plugins/grid/grid_bzen.php' .
						'?reset=1&action=zoom' .
						'&clusterid=' . $job['clusterid'] .
						'&exec_host=' . $exec_host;

					grid_print_metadata('simple', 'host', $job['clusterid'], $job['exec_host'], '', __esc('View Jobs and Graphs', 'grid'), false, $url);

					if (isset($exec_hosts) && cacti_sizeof($exec_hosts)) {
						print ' (';
						$i=0;
						$pos_loading = false;
						foreach ($exec_hosts as $exechosts) {
							$url = $config['url_path'] . 'plugins/grid/grid_bzen.php' .
								'?reset=1&action=zoom' .
								'&clusterid=' . $job['clusterid'] .
								'&exec_host=' . $exechosts['exec_host'];

							if ($i > 0) {
								if($pos_loading == false && $exechosts['isborrowed'] == 1){
									$pos_loading = true;
									print '; <b>' . __('Loaning') . ': ';
								} else {
									print ', ';
								}
							} else {
								if($pos_loading == false && $exechosts['isborrowed'] == 1){
									$pos_loading = true;
									print '<b>' . __('Loaning') . ': ';
								}
							}

							grid_print_metadata('simple', 'host', $job['clusterid'], grid_strip_domain($exechosts['processes'] . '*' . $exechosts['exec_host']), '', __esc('View Jobs and Graphs', 'grid'), false, $url);
							$i++;
						}
						print ')';
					} else if ($job['isLoaningGSLA']) {
						print ' (Loaning)';
					}
					?>
				</td>
			</tr>
			<?php

			if (isset($job_gpu_rusages) && cacti_sizeof($job_gpu_rusages)) {
				$gpu_alloc_str = "";
				$curr_ghost = "";
				foreach($job_gpu_rusages as $jg_rusage){
					if($curr_ghost == ""){
						$curr_ghost = $jg_rusage['host'];
						$gpu_alloc_str = $jg_rusage['host'] . ":gpus=";
					} else if($curr_ghost == $jg_rusage['host']){
						$gpu_alloc_str .= ",";
					} else {
						$gpu_alloc_str .= ";" . $jg_rusage['host'] . ":gpus=";
					}
					$gpu_alloc_str .= $jg_rusage['gpu_id'];
				}
				$gpu_alloc_str .= ";";

				form_alternate_row();?>
					<td width='15%'>
						<?php print __('GPU Allocation', 'grid');?>
					</td>
					<td width='85%' colspan='3'>
						<?php
							print $gpu_alloc_str;
						?>
					</td>
				</tr>
				<?php
			}

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Start Time', 'grid');?>
				</td>
				<td width='35%'>
					<?php print check_time($job['start_time']);?>
				</td>
				<td width='15%'>
				</td>
				<td width='35%'>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Username', 'grid');?>
				</td>
				<td width='35%'>
				<?php
				$url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
					'?action=viewlist&reset=1' .
					'&clusterid=' . $job['clusterid'] .
					'&job_user=' . $job['execUsername'] .
					'&status=RUNNING&page=1';

				grid_print_metadata('detailed', 'user', $job['clusterid'], $job['execUsername'], '', __esc('Show User Jobs', 'grid'), false, $url);
				?>
				</td>
				<td width='15%'>
					<?php print __('UID String', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['execUid'];?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('User Home', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['execHome'];?>
				</td>
				<td width='15%'>
					<?php print __('Working Dir', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['execCwd'];?>
				</td>
			</tr>
			<?php

			if ($job['maxNumProcessors'] != 0 || $job['max_allocated_processes']) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Max Processors', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['maxNumProcessors']; ?>
				</td>
				<td width='15%'>
					<?php print __('Max Allocated Slots', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $job['max_allocated_processes']; ?>
				</td>
			</tr>
			<?php }

			if ($job['rlimit_max_cpu'] != 0 || $job['rlimit_max_wallt'] != 0) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('CPU Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['rlimit_max_cpu'], 2, false); ?>
				</td>
				<td width='15%'>
					<?php print __('Run Time Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_time($job['rlimit_max_wallt'], 2, false);?>
				</td>
			</tr>
			<?php }

			if ($job['rlimit_max_rss'] != 0 || $job['rlimit_max_swap'] != 0) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Memory Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_rss'], 3);?>
				</td>
				<td width='15%'>
					<?php print __('Swap Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_swap'], 3);?>
				</td>
			</tr>
			<?php }

			if ($job['rlimit_max_stack'] != 0 || $job['rlimit_max_core'] != 0) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Stack Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_stack'], 3);?>
				</td>
				<td width='15%'>
					<?php print __('Core File Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_core'], 3);?>
				</td>
			</tr>
			<?php }

			if ($job['rlimit_max_data'] != 0 || $job['rlimit_max_fsize'] != 0) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Data Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_data'], 3);?>
				</td>
				<td width='15%'>
					<?php print __('File Size Limit', 'grid');?>
				</td>
				<td width='35%'>
					<?php print display_job_memory($job['rlimit_max_fsize'], 3);?>
				</td>
			</tr>
			<?php }

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Effective ResReq', 'grid');?>
				</td>
				<td colspan="3" width='85%' style="word-break:break-all;" >
					<?php print ( (empty ($job['effectiveResreq']))? '-' : $job['effectiveResreq']);?>
				</td>
			</tr>
			<?php

			if (strlen($job['gpuEffectiveResreq'])) {
				form_alternate_row();?>
					<td width='15%'>
						<?php print __('Effective GPU ResReq', 'grid');?>
					</td>
					<td colspan="3" width='85%' style="word-break:break-all;" >
						<?php print ( (empty ($job['gpuEffectiveResreq']))? '-' : $job['gpuEffectiveResreq']);?>
					</td>
				</tr>
				<?php
			}

			if (!empty($job['chargedSAAP'] )) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Charged SAAP', 'grid');?>
				</td>
				<td colspan='3' width='85%'>
					<?php print html_escape($job['chargedSAAP']);?>
				</td>
			</tr>
			<?php }

			html_end_box(false);

			$i = 0;

			if (isset($job_rusage['update_time']) && $job['stat'] != 'DONE' && $job['stat'] != 'EXIT') {
				$lupdate = strtotime($job_rusage['update_time']);
				$now     = time();
				$updated = " [ Last Updated: " . round(($now - $lupdate)/60,1) . " Minutes ago ]";
			} else {
				$updated = '';
			}

			html_start_box("Current/Last Status" . $updated, "100%", "", "3", "center", "");
			form_alternate_row();?>
				<td width='16%'>
					<?php print __('PGIDS', 'grid');?>
				</td>
				<td width='84%' colspan='5'>
					<?php print ((isset($job_rusage['pgids'])) ? $job_rusage['pgids'] : "-");?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('PIDS', 'grid');?>
				</td>
				<td width='84%' colspan='5'>
					<?php print ((isset($job_rusage['pids'])) ? $job_rusage['pids'] : "-");?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Threads', 'grid');?>
				</td>
				<td width='84%' colspan='5'>
					<?php print ((isset($job_rusage['nthreads'])) ? $job_rusage['nthreads'] : "-");?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Pend Time', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_time($job['pend_time'], 2, false);?>
				</td>
				<td width='16%'>
					<?php print __('PROV Time', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_time($job['prov_time'], 2, false);?>
				</td>
				<td width='16%'>
					<?php print __('Run Time', 'grid');?>
				</td>
				<td width='20%'>
					<?php print display_job_time($job['run_time'], 2, false);?>
				</td>
			</tr>
			<?php
			if (cluster_check_ineligible_pend_reason($job['clusterid']) > 0) {
			form_alternate_row();?>
				<td nowrap style='white-space: nowrap;' width='16%'>
					<?php print __('Ineligible Pending Time', 'grid');?>
				</td>
				<td colspan='5' width='84%'>
					<?php print display_job_time($job['ineligiblePendingTime'], 2, false);?>
				</td>
			</tr>
			<?php
			}

			if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1010')) {
			form_alternate_row();?>
				<td nowrap style='white-space: nowrap;'  width='16%'>
					<?php print __('Effective Pending Time Limit', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_time($job['effectivePendingTimeLimit'], 2, false);?>
				</td>
			<?php
			if (cluster_check_ineligible_pend_reason($job['clusterid']) > 0) { ?>
				<td nowrap style='white-space: nowrap;' width='16%'>
					<?php print __('Effective Eligible Pending Time Limit', 'grid');?>
				</td>
				<td colspan='3' width='52%'>
					<?php print display_job_time($job['effectiveEligiblePendingTimeLimit'], 2, false);?>
				</td>
			<?php
			} else {
				print ("<td colspan='4' width='68%'></td>");
			}
				print ("</tr>");
			}

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('PSUSP Time', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_time($job['psusp_time'], 2, false);?>
				</td>
				<td width='16%'>
					<?php print __('USUSP Time', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_time($job['ususp_time'], 2, false);?>
				</td>
				<td width='16%'>
					<?php print __('SSUSP Time', 'grid');?>
				</td>
				<td width='20%'>
					<?php print display_job_time($job['ssusp_time'], 2, false);?>
				</td>
			</tr>
			<?php
			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Unknown Time', 'grid');?>
				</td>
				<td width='84%' colspan='5'>
					<?php print display_job_time($job['unkwn_time'], 2, false);?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Cumulative CPU', 'grid');?>
				</td>
				<td width='16%'>
					<?php print (($job['cpu_used'] >= 0) ? display_job_time($job['cpu_used'],2,false) : "-");?>
				</td>
				<td width='16%'>
					<?php print __('System Time', 'grid');?>
				</td>
				<td width='16%'>
					<?php print (($job['stime'] >= 0) ? display_job_time($job['stime'],2,false) : "-");?>
				</td>
				<td width='16%'>
					<?php print __('User Time', 'grid');?>
				</td>
				<td width='20%'>
					<?php print (($job['utime'] >= 0) ? display_job_time($job['utime'],2,false) : "-");?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Cur Memory Used', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_memory(isset($job_rusage['mem'])?$job_rusage['mem']:0,3);?>
				</td>
				<td width='16%'>
					<?php print __('Max Memory Used', 'grid');?>
				</td>
				<td  width='16%'>
					<?php print display_job_memory($job['max_memory'],3);?>
				</td>
				<td width='16%'>
					<?php print __('Job Efficiency', 'grid');?>
				</td>
				<td width='20%'>
					<?php print display_job_effic($job["efficiency"], $job["run_time"], 2);?>
				</td>
			</tr>
			<?php

			if($job['num_gpus'] > 0 && (read_config_option('grid_GPU_rusage_simulation') || ($job['stat'] == 'DONE' || $job['stat'] == 'EXIT'))) {
				if (cacti_sizeof($job_gpu_rusages)) {
					$gpu_exec_time = 0;
					$gpu_energy = 0;
					$gpu_sm_ut = 0;
					$gpu_mem_ut = 0;
					$gpu_mx_mused = 0;
					$ngrusages = cacti_sizeof($job_gpu_rusages);
					foreach($job_gpu_rusages as $jg_rusage){
						If($jg_rusage['exec_time'] > $gpu_exec_time){
							$gpu_exec_time = $jg_rusage['exec_time'];
						}
						$gpu_energy += $jg_rusage['energy'];
						$gpu_sm_ut += $jg_rusage['sm_ut_avg'];
						$gpu_mem_ut += $jg_rusage['mem_ut_avg'];
						$gpu_mx_mused += $jg_rusage['gpu_mused_max'];
					}
					$gpu_sm_ut = $gpu_sm_ut/$ngrusages;
					$gpu_mem_ut = $gpu_mem_ut/$ngrusages;

					form_alternate_row();?>
						<td width='16%'>
							<?php print __('Max GPU Memory Used', 'grid');?>
						</td>
						<td width='16%'>
							<?php print display_byte_by_unit($gpu_mx_mused, 3, '');?>
						</td>
						<td width='16%'>
							<?php print __('GPU Execution Time', 'grid');?>
						</td>
						<td  width='16%'>
							<?php print display_job_time($gpu_exec_time, 2, false);?>
						</td>
						<td width='16%'>
							<?php print __('GPU Energy Consumed', 'grid');?>
						</td>
						<td width='20%'>
							<?php print human_display_by_unit($gpu_energy, 3, '', 1000, 'J');?>
						</td>
					</tr>
					<?php

					if ($gpu_sm_ut > 0 || $gpu_mem_ut > 0){
						form_alternate_row();
						if ($gpu_sm_ut > 0){?>
						<td width='16%'>
							<?php print __('GPU SM Utilization', 'grid');?>
						</td>
						<td width='16%'>
							<?php print rtm_add_dec($gpu_sm_ut, 3) . '%';?>
						</td>
						<?php
						}
						if ($gpu_mem_ut > 0){?>
						<td width='16%'>
							<?php print __('GPU Memory Utilization', 'grid');?>
						</td>
						<td width='16%'>
							<?php print rtm_add_dec($gpu_mem_ut, 3) . '%';?>
						</td>
						<?php
						}
						if ($gpu_sm_ut <= 0 || $gpu_mem_ut <= 0){?>
						<td width='16%'>
						</td>
						<td width='16%'>
						</td>
						<?php
						}?>
						<td width='16%'>
						</td>
						<td  width='16%'>
						</td>
					</tr>
					<?php
					}
				}
			}

			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Cur V.Memory Size', 'grid');?>
				</td>
				<td width='16%'>
					<?php print display_job_memory(isset($job_rusage['swap'])?$job_rusage['swap']:0,3);?>
				</td>
				<td width='16%'>
					<?php print __('Max V.Memory Size', 'grid');?>
				</td>
				<td colspan='3' width='52%'>
					<?php print display_job_memory($job['max_swap'],3);?>
				</td>
			</tr>

			<?php
			if (($job['stat'] == "DONE") || ($job['stat'] == "EXIT") || ($job['stat'] == "UNKWN")) {
			form_alternate_row();?>
				<td width='16%'>
					<?php print __('Exit Code', 'grid');?>
				</td>
				<td width='16%'>
					<?php print grid_get_exit_code($job["exitStatus"]);?>
				</td>
				<td width='16%'>
					<?php print __('End Time', 'grid');?>
				</td>
				<td width='52%' colspan='3'>
					<?php print ((substr_count($job['end_time'], '0000')) ? "-" : $job['end_time']);?>
				</td>
			</tr>
			<?php }

			if ((isset($job['exceptMask']) && $job['exceptMask'] > 0) || (isset($job['stat']) && isset($job['exitInfo']) && $job['stat'] == "EXIT" && $job['exitInfo'] >= 0)) {
				$tmp_msg=getExceptionStatus($job['exceptMask'], $job['stat'] == "EXIT" ? $job['exitInfo'] : -1);
				if (!empty($tmp_msg)) {
					form_alternate_row();?>
						<td width='15%'>
							<?php print __('Exception Status', 'grid');?>
						</td>
						<td width='86%' colspan='5'>
							<?php print $tmp_msg; ?>
						</td>
					<?php
					form_end_row();
				}
			}
			}

			html_end_box();
		} elseif ($current_tab == 'jobgraph') {
			?>
			<script type='text/javascript'>
			$(function() {
				$('.graphimage').each(function() {
					$(this).attr('src', $(this).attr('src')+'&tmpparam=,xxx'); //Fix atob error in zoomFunction_init()
				});
			});
			</script>
			<?php
			get_filter_request_var('page');
			get_filter_request_var('predefined_graph_type');
			if ((isset_request_var('clear')) ||
				(isset($_SESSION['sess_graph_job_view_jobid']) && $job['jobid'] != $_SESSION['sess_graph_job_view_jobid'])) {
				kill_session_var('sess_job_graphs_page');
				kill_session_var('sess_predefined_graph_type');

				unset_request_var('page');
				unset_request_var('predefined_graph_type');
			}

			load_current_session_value('columns', 'sess_graph_job_view_columns', read_grid_config_option('job_graph_columns'));

			load_current_session_value('jobid', 'sess_graph_job_view_jobid', $job['jobid']);
			load_current_session_value('page', 'sess_job_graphs_page', '1');
			load_current_session_value('predefined_graph_type', 'sess_predefined_graph_type', '0');

			html_start_box(__('Job Graph Timespan Selector', 'grid'), '100%', '', '3', 'center', '');
			jobGraphFilter($job, $job_page);
			html_end_box(false);

			/* fetch the number of columns */
			$columns = get_request_var('columns');

			$i = 0;

			$graph_types = array(
				array(
					'graph_type' => 'memory',
					'data_class' => 'absolute',
					'title' => __('Absolute Memory Stats', 'grid')
				),
				array(
					'graph_type' => 'memory',
					'data_class' => 'relative',
					'title' => __('Relative Memory Stats', 'grid')
				),
				array(
					'graph_type' => 'cpu',
					'data_class' => 'absolute',
					'title' => __('Absolute CPU Stats', 'grid')
				),
				array(
					'graph_type' => 'cpu',
					'data_class' => 'relative',
					'title' => __('Relative CPU Stats', 'grid')
				),
				array(
					'graph_type' => 'slots',
					'data_class' => 'absolute',
					'title' => __('Absolute Slot Stats', 'grid')
				),
				array(
					'graph_type' => 'slots',
					'data_class' => 'relative',
					'title' => __('Relative Slot Stats', 'grid')
				)
			);

			/* rrd file update interval*/
			$rrd_interval = read_config_option('poller_interval');

			html_start_box(__('Graphical History [ %s through %s ]', date('Y-m-d H:i:s', $start_time), date('Y-m-d H:i:s', $end_time+$rrd_interval), 'grid') . ($_SESSION['date_bounds'] ? ' ' . __('NOTE: Timespan Exceeds Job Run Window - Limits In Place', 'grid') : ''), '100%', '', '3', 'center', '');
			html_end_box();
			$graph_col_width = 100;
			if(get_request_var('columns') > 1){
				$graph_col_width = $graph_col_width/get_request_var('columns');
			}
			$columnStyle = "style='width:$graph_col_width%;'";
			html_start_box('', '100%', '', '3', 'center', '');

			if (isset_request_var('predefined_graph_type') && get_request_var('predefined_graph_type') != 0) {
				$jhg_url = $job_page . '?action=viewjob&tab=jobgraph&clusterid=' . get_request_var('clusterid')
						. '&indexid=' . get_request_var('indexid') . '&jobid=' . get_request_var('jobid')
						. '&cluster_tz=' . get_request_var('cluster_tz') . '&submit_time=' . get_request_var('submit_time')
						. '&start_time=' . get_request_var('start_time') . '&end_time=' . get_request_var('end_time')
						. '&legend='.get_request_var('legend');
				$jhg_nav = '';
				if(get_request_var('predefined_graph_type') == 1){
					$obj_cnt = 2;
					$num_g_per_obj = 4;

					$total_rows=get_grid_job_hosts_total_rows($_REQUEST["jobid"], $_REQUEST["indexid"], $_REQUEST["clusterid"], $_REQUEST["submit_time"]);
					if($total_rows >0){
						$grid_job_hosts = get_grid_job_hosts($obj_cnt);
					}

					if (empty($grid_job_hosts)) {
						print '<tr><td><em>' . __('No Host Level Rusage Graph Data for The Job', 'grid') . '</em></td></tr>';
						html_end_box();
						bottom_footer();
						return;
					}

					if ($total_rows > $obj_cnt){
						$jhg_nav = html_nav_bar($jhg_url, MAX_DISPLAY_PAGES, get_request_var('page'), $obj_cnt*$num_g_per_obj, $total_rows*$num_g_per_obj, 0, __('Host Level Rusage Graphs', 'grid'), 'page', 'main');
					}
					print '<tr><td colspan=\'' . get_request_var('columns')*2 . '\'>' . $jhg_nav . '</td></tr>';

					print '<tr class=\'tableRowGraph\'>';
					if (cacti_sizeof($grid_job_hosts)) {
						foreach($graph_types as $graph) {
							$j = 0;
							foreach($grid_job_hosts as $host) {
								if ($i % get_request_var('columns') == 0) {
									if ($i > 0) {
										form_end_row();
									}
									print "<tr class='tableRowGraph'>\n";
								}

								print "<td class='center' $columnStyle>";
								print get_jobgraph_imgele($job, $start_time, $end_time, $rrd_interval, $graph['data_class'],
											$graph['graph_type'], get_request_var('legend'), get_request_var('cluster_tz'), $host["host"]);
								print '</td>';
								print "<td class='noprint graphDrillDown'></td>";
								$i++;
								$j++;
							}
						}
					}

					print '</tr>';
					html_end_box();
					bottom_footer();

					return;
				} else if(get_request_var('predefined_graph_type') == 2){
					$gpu_graph_types = array(
						array(
							'graph_type' => 'gut',
							'data_class' => 'absolute',
						),
						array(
							'graph_type' => 'gut',
							'data_class' => 'relative',
						),
						array(
							'graph_type' => 'gmut',
							'data_class' => 'absolute',
						),
						array(
							'graph_type' => 'gmut',
							'data_class' => 'relative',
						),
						array(
							'graph_type' => 'gmemory',
							'data_class' => 'absolute',
						),
						array(
							'graph_type' => 'gmemory',
							'data_class' => 'relative',
						)
					);

					$obj_cnt = 2;
					$num_g_per_obj = 6;

					$total_rows=get_grid_job_gpus_total_rows($_REQUEST["jobid"], $_REQUEST["indexid"], $_REQUEST["clusterid"], $_REQUEST["submit_time"]);
					if($total_rows > 0){
						$grid_job_gpus = get_grid_job_gpus($obj_cnt);
					}

					if (empty($grid_job_gpus)) {
						print '<tr><td><em>' . __('No GPU Level Rusage Graph Data for The Job', 'grid') . '</em></td></tr>';
						html_end_box();
						bottom_footer();
						return;
					}

					if ($total_rows > $obj_cnt){
						$jhg_nav = html_nav_bar($jhg_url, MAX_DISPLAY_PAGES, get_request_var('page'), $obj_cnt*$num_g_per_obj, $total_rows*$num_g_per_obj, 0, __('GPU Level Rusage Graphs', 'grid'), 'page', 'main');
					}
					print '<tr><td colspan=\'' . get_request_var('columns')*2 . '\'>' . $jhg_nav . '</td></tr>';

					print "<tr class='tableRowGraph'>\n";
					if (cacti_sizeof($grid_job_gpus)) {
						$i = 0;
						foreach($gpu_graph_types as $graph) {
							$j = 0;
							foreach($grid_job_gpus as $gpu) {
								if ($i % get_request_var('columns') == 0) {
									if ($i > 0) {
										form_end_row();
									}
									print "<tr class='tableRowGraph'>\n";
								}

								print "<td class='center' $columnStyle>";
								print get_jobgraph_imgele($job, $start_time, $end_time, $rrd_interval, $graph['data_class'],
														$graph['graph_type'], get_request_var('legend'), get_request_var('cluster_tz'),
														$gpu["host"], $gpu["gpu_id"]);
								print '</td>';
								print "<td class='noprint graphDrillDown'></td>";
								$i++;
								$j++;
							}
						}
					}
					print '</tr>';
					html_end_box();
					bottom_footer();
					return;
				}
			}

			$graph_types[] = array(
					'graph_type' => 'pids',
					'data_class' => 'absolute',
					'title' => __('Absolute PID/PGID/Tread Stats', 'grid')
				);
			$graph_types[] = array(
					'graph_type' => 'pids',
					'data_class' => 'relative',
					'title' => __('Relative PID/PGID/Thread Stats', 'grid')
				);

			foreach($graph_types as $graph) {
				if ($i % get_request_var('columns') == 0) {
					if ($i > 0) {
						form_end_row();
					}
					print "<tr class='tableRowGraph'>\n";
				}

				print "<td class='center' $columnStyle>";
				print get_jobgraph_imgele($job, $start_time, $end_time, $rrd_interval, $graph['data_class'], $graph['graph_type'],
											get_request_var('legend'), get_request_var('cluster_tz'));
				print '</td>';
				print "<td class='noprint graphDrillDown'></td>";

				$i++;
			}

			print '</tr>';
			html_end_box();
			bottom_footer();

			return;
		} elseif ($current_tab == 'hostgraph') {
			/* ================= input validation ================= */
			get_filter_request_var('host_id');
			get_filter_request_var('page');

			$graph_template_type = 'gt';

			if (!isempty_request_var('graph_template_id') && strlen(get_request_var('graph_template_id')) > 2) {
				$graph_template_type = substr(get_request_var('graph_template_id'), 0,2);
				set_request_var('graph_template_id', substr(get_request_var('graph_template_id'), 2));
			}
			/* ==================================================== */

			/* clean up search string */
			if (isset_request_var('filter')) {
				set_request_var('filter', sanitize_search_string(get_request_var('filter')));
			}

			$x = 0;

			global $host_string;

			if (cacti_sizeof($exec_hosts)) {
				foreach ($exec_hosts as $exec_host) {
					if ($x == 0) {
						$host_string = '(' . db_qstr($exec_host['exec_host']);
					} else {
						$host_string .= ', ' . db_qstr($exec_host['exec_host']);
					}

					$x++;
				}

				$host_string .= ')';
			} else {
				$host_string = '(' . db_qstr($job['exec_host']) . ')';
			}

			$host_ids = array_rekey(
				db_fetch_assoc_prepared('SELECT id
					FROM host
					WHERE clusterid=?
					AND hostname IN ' . $host_string, array($job['clusterid'])),
				'id', 'id'
			);

			$x = 0;

			if (cacti_sizeof($host_ids)) {
				$host_string = '(' . implode(', ', $host_ids) . ')';
			} else {
				$host_string = "(-1)";
			}

			define('ROWS_PER_PAGE', read_graph_config_option('preview_graphs_per_page'));

			$sql_or = ''; $sql_where = '';

			if (empty($current_user['show_preview'])) {
				print "<font size='+1' color='FF0000'>" . __('YOU DO NOT HAVE RIGHTS FOR PREVIEW VIEW', 'grid') . '</font>';
				bottom_footer();
				exit;
			}

			/* if the user pushed the 'clear' button */
			if ((isset_request_var('clear')) ||
				(isset($_SESSION['sess_graph_job_view_jobid']) && $job['jobid'] != $_SESSION['sess_graph_job_view_jobid'])) {
				kill_session_var('sess_graph_job_graph_current_page');
				kill_session_var('sess_graph_job_graph_filter');
				kill_session_var('sess_graph_job_graph_graph_template');
				kill_session_var('sess_graph_job_graph_host');

				unset_request_var('page');
				unset_request_var('filter');
				unset_request_var('host_id');
				unset_request_var('graph_template_id');
				unset_request_var('graph_list');
				unset_request_var('graph_add');
				unset_request_var('graph_remove');
				$graph_template_type='gt';
			}

			/* reset the page counter to '1' if a search in initiated */
			if (isset_request_var('filter') && strlen(get_request_var('filter'))>0) {
				set_request_var('page', '1');
			}

			load_current_session_value('host_id', 'sess_graph_job_graph_host', '0');
			load_current_session_value('columns', 'sess_graph_job_graph_columns', '2');
			load_current_session_value('jobid', 'sess_graph_job_view_jobid', $job['jobid']);
			load_current_session_value('graph_template_id', 'sess_graph_job_graph_graph_template', '0');
			load_current_session_value('filter', 'sess_graph_job_graph_filter', '');
			load_current_session_value('page', 'sess_graph_job_graph_current_page', '1');

			$sql_where = '';

			if (get_filter_request_var('host_id') > 0) {
				$sql_where .= ($sql_where != '' ? ' AND ':'') . 'gl.host_id = ' . get_request_var('host_id');
			} else {
				$sql_where .= ($sql_where != '' ? ' AND ':'') . 'gl.host_id IN ' . $host_string;
			}

			if (get_filter_request_var('graph_template_id') > 0) {
				$sql_where .= ($sql_where != '' ? ' AND ':'') . 'gl.graph_template_id = ' . get_request_var('graph_template_id');
			}

			if (get_request_var('filter') != '') {
				$sql_where .= ($sql_where != '' ? ' AND ':'') . "gtg.title_cache LIKE '%" . get_request_var('filter'). "%'";
			}

			$total_rows = 0;

			$limit      = (ROWS_PER_PAGE*(get_request_var('page')-1)) . ',' . ROWS_PER_PAGE;

			if ($graph_template_type == 'gt') {
				$graphs = get_allowed_graphs($sql_where, '', $limit, $total_rows);
			} elseif ($graph_template_type=='et') {
				$sql_base = "FROM grid_elim_templates as et
					INNER JOIN grid_elim_template_instances as eti
					ON eti.grid_elim_template_id=et.id
					INNER JOIN grid_elim_instance_graphs as etg
					ON etg.grid_elim_template_instance_id=eti.id
					INNER JOIN graph_local AS gl
					ON gl.id=etg.local_graph_id
					INNER JOIN graph_templates_graph AS gtg
					ON gtg.local_graph_id=gl.id
					WHERE gtg.local_graph_id > 0
					AND gtg.title_cache LIKE '%" . get_request_var('filter') . "%'
					" . (isempty_request_var('host_id') ? ' AND gl.host_id IN ' . $host_string : ' AND gl.host_id=' . get_request_var('host_id')) . '
					' . (isempty_request_var('graph_template_id') ? '' : ' AND et.id=' . get_request_var('graph_template_id')) . "
					$sql_or";

				$total_rows = count(db_fetch_assoc("SELECT
					gtg.local_graph_id
					$sql_base"));

				/* reset the page if you have changed some settings */
				if (ROWS_PER_PAGE * (get_request_var('page')-1) >= $total_rows) {
					set_request_var('page', '1');
				}

				$graphs = db_fetch_assoc("SELECT gtg.local_graph_id, gtg.title_cache
					$sql_base
					GROUP BY gtg.local_graph_id
					ORDER BY gtg.title_cache
					LIMIT " . (ROWS_PER_PAGE*(get_request_var('page')-1)) . "," . ROWS_PER_PAGE);
			}

			if (cacti_sizeof($graphs)) {
				foreach($graphs as $index => $graph) {
					$graphs[$index]['height'] = read_config_option('default_graph_height');
					$graphs[$index]['width'] = read_config_option('default_graph_width');
				}
			}

			/* include graph view filter selector */
			html_start_box(__('Job Host Graphical History [ %s through %s ]', date('Y-m-d H:i:s', $start_time), date('Y-m-d H:i:s', $end_time), 'grid') . ($_SESSION['date_bounds'] ? ' ' . __('NOTE: Timespan Exceeds Job Run Window - Limits In Place', 'grid') : ''), '100%', '', '3', 'center', '');
			graphViewFilter($job_page);
			html_end_box(false);

			$nav_url = $job_page . '?action=viewjob&tab=hostgraph&clusterid=' . get_request_var('clusterid')
					. '&indexid=' . get_request_var('indexid') . '&jobid=' . get_request_var('jobid')
					. '&cluster_tz=' . get_request_var('cluster_tz') . '&submit_time=' . get_request_var('submit_time')
					. '&start_time=' . get_request_var('start_time') . '&end_time=' . get_request_var('end_time')
					. '&legend='.get_request_var('legend');

			html_start_box('', '100%', '', '3', 'center', '');
			if (cacti_sizeof($graphs)) {
				$nav = html_nav_bar($nav_url, read_graph_config_option('num_columns'), get_request_var('page'), ROWS_PER_PAGE, $total_rows, 0, __('Host Graphs', 'grid'), 'page', 'main');
				print $nav;
			} else {
				print "<tr><td class='textHeaderDark' align='center'>" . __('No Graphs Found', 'grid') . "</td></tr><tr><td><i>" . __('No Host Graphs Found. Please verify Host Automation has been enabled for this cluster.', 'grid') . "</i></td></tr>";
			}

			if (get_request_var('thumbnails') == 'true') {
				grid_graph_thumbnail_area($graphs, $timespan['begin_now'], $timespan['end_now'], get_request_var('columns'));
			} else {
				grid_graph_area($graphs, $timespan['begin_now'], $timespan['end_now'], get_request_var('columns'));
			}

			html_end_box();

			if (cacti_sizeof($graphs)) {
				$nav = html_nav_bar($nav_url, read_graph_config_option('num_columns'), get_request_var('page'), ROWS_PER_PAGE, $total_rows, 0, __('Host Graphs', 'grid'), 'page', 'main');
				print $nav;
			}

			print "<br><br>";
		} elseif ($current_tab == 'jobpendhist') {
			include_once($config['base_path'] . '/plugins/RTM/include/fusioncharts/fusioncharts.php');

			$filters = array(
				'grid_pend_ignore_applied' => array(
					'filter' => FILTER_CALLBACK,
					'default' => 'on',
					'options' => array('options' => 'sanitize_search_string'),
					'pageset' => true
					),
				'top_x_reason' => array(
					'filter' => FILTER_VALIDATE_INT,
					'pageset' => true,
					'default' => '20'
					),
				'level' => array(
					'filter' => FILTER_VALIDATE_INT,
					'pageset' => true,
					'default' => '0'
					),
				'pending_sortby' => array(
					'filter' => FILTER_VALIDATE_INT,
					'pageset' => true,
					'default' => '0'
					),
			);
			validate_store_request_vars($filters, 'sess_grid_bjobs_jobpendhist');
			?>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = '<?php print $job_page;?>?top_x_reason=' + $('#top_x_reason').val();
				strURL += '&level=' + $('#level').val();
				strURL += '&pending_sortby=' + $('#pending_sortby').val();
				strURL += '&grid_pend_ignore_applied=' + ($('#grid_pend_ignore_applied')[0].checked ? 'on' : 'off');
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&indexid=' + $('#indexid').val();
				strURL += '&submit_time=' + $('#submit_time').val();
				strURL += '&action=' + $('#action').val();
				strURL += '&tab=' + $('#tab').val();
				strURL += '&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_grid_view_jpendhist').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});
			});

			function clearFilter(objForm) {
				strURL  = '<?php print $job_page;?>?&header=false';
				strURL += '&action=' + $('#action').val();
				strURL += '&tab=' + $('#tab').val();
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&indexid=' + $('#indexid').val();
				strURL += '&submit_time=' + $('#submit_time').val();
				strURL += '&clear=true';
				loadPageNoHeader(strURL);
			}

			</script>
			<?php
			/* if the user pushed the 'clear' button */
			if (isset_request_var('clear')) {
				unset_request_var('top_x_reason');
				unset_request_var('level');
				unset_request_var('pending_sortby');
				unset_request_var('grid_pend_ignore_applied');
			}
			/* include pending reason filter selector */

			if (!isset_request_var('top_x_reason')) {
				$default_num_of_pendreason = '20';
			} else {
				$default_num_of_pendreason = get_request_var('top_x_reason');
			}

			if (!isset_request_var('level')) {
				$default_pend_level = '0';
			} else {
				$default_pend_level = get_request_var('level');
			}

			if (!isset_request_var('pending_sortby') && !isset_request_var('top_x_reason')) {
				$grid_pend_ignore_applied = 'on';
			} elseif (isset_request_var('grid_pend_ignore_applied')) {
				$grid_pend_ignore_applied = get_request_var('grid_pend_ignore_applied');
			} else {
				$grid_pend_ignore_applied = 'off';
			}

			if (!isset_request_var('pending_sortby')) {
				$default_pendreason_sortby = '0';
			} else {
				$default_pendreason_sortby = get_request_var('pending_sortby');
			}

			html_start_box(__('Job Pending Reason Filter', 'grid'), '100%', '', '3', 'center', '');
			pendingReasonFilter($grid_pend_ignore_applied, $default_num_of_pendreason, $default_pend_level, $default_pendreason_sortby, $job_page);
			html_end_box(false);

			html_start_box(__('Job Pending Reason History', 'grid'), '100%', '', '3', 'center', '');
			print "<tr><td align='center'><div id='pcontainer' sytle='padding:5px;display:block;'></div></td></tr>";

			pendingFusionFilter($job, $pendreason_tables, $pend_sql_query, $grid_pend_ignore_applied, $default_num_of_pendreason, $default_pend_level, $default_pendreason_sortby);

			html_end_box(false);;
		} else {
			api_plugin_hook('grid_jobs_show_tab');
		}
	} else {
		print __('Unable to find job record', 'grid');
	}

	bottom_footer();
}

function build_job_display_array($jobs_page = '') {
	$display_text = array(
		'jobid' => array(
			'display' => __('JobID', 'grid'),
			'sort'    => 'ASC'
		),
		'jobclusterid' => array(
			'display' => __('Cluster ID', 'grid'),
			'dbname'  => 'show_jobclusterid',
			'sort'    => 'ASC'
		),
		'jobclustername' => array(
			'display' => __('Cluster', 'grid'),
			'dbname'  => 'show_jobclustername',
			'sort'    => 'ASC'
		),
		'jobname' => array(
			'display' => __('Job Name', 'grid'),
			'dbname'  => 'show_jobname',
			'sort'    => 'ASC'
		),
		'queue' => array(
			'display' => __('Queue', 'grid'),
			'dbname'  => 'show_queue',
			'sort'    => 'ASC'
		),
		'sla' => array(
			'display' => __('SLA', 'grid'),
			'dbname'  => 'show_sla',
			'sort'    => 'ASC'
		),
		'user' => array(
			'display' => __('User', 'grid'),
			'dbname'  => 'show_user',
			'sort'    => 'ASC'
		),
		'userGroup' => array(
			'display' => __('UGroup', 'grid'),
			'dbname'  => 'show_ugroup',
			'sort'    => 'DESC'
		),
		'chargedSAAP' => array(
			'display' => __('Charged SAAP', 'grid'),
			'dbname'  => 'show_chargedsaap',
			'sort'    => 'DESC'
		),
		'projectName' => array(
			'display' => __('Project', 'grid'),
			'dbname'  => 'show_project',
			'sort'    => 'ASC'
		),
		'licenseProject' => array(
			'display' => __('Lic Proj', 'grid'),
			'dbname'  => 'show_lproject',
			'sort'    => 'ASC'
		),
		'app' => array(
			'display' => __('App', 'grid'),
			'dbname'  => 'show_app',
			'sort'    => 'DESC'
		),
		'jobGroup' => array(
			'display' => __('JGroup', 'grid'),
			'dbname'  => 'show_jgroup',
			'sort'    => 'DESC'
		),
		'stat' => array(
			'display' => __('Status', 'grid'),
			'sort'    => 'DESC'
		),
		'exec_host' => array(
			'display' => __('Exec Host', 'grid'),
			'dbname'  => 'show_exec_host',
			'sort'    => 'ASC',
		),
		'nosort1' => array(
			'display' => __('Type', 'grid'),
			'dbname'  => 'show_exec_host_type',
			'sort'    => 'ASC',
		),
		'nosort2' => array(
			'display' => __('Model', 'grid'),
			'dbname'  => 'show_exec_host_model',
			'sort'    => 'ASC',
		),
		'from_host' => array(
			'display' => __('SubHost', 'grid'),
			'dbname'  => 'show_from_host',
			'sort'    => 'ASC',
		)
	);

	if (basename($jobs_page) == 'heuristics_jobs.php') {
		$display_text += array('nosort101' => array(__('Lic'), 'DESC'));
		$display_text += array('nosort99' => array(__('Idle Job'), 'DESC'));
		$display_text += array('nosort98' => array(__('Long RJob'), 'DESC'));
		$display_text += array('nosort97' => array(__('Pend Dpnd'), 'DESC'));
		$display_text += array('nosort96' => array(__('Mem Use'), 'DESC'));
	}

	$display_text += array(
		'exitStatus' => array(
			'display' => __('Exit Code', 'grid'),
			'dbname'  => 'show_exit',
			'sort'    => 'ASC'
		),
		'stat_changes' => array(
			'display' => __('State Changes', 'grid'),
			'dbname'  => 'show_state_changes',
			'sort'    => 'DESC'
		),
		'jobPriority' => array(
			'display' => __('J(U) Pri', 'grid'),
			'dbname'  => 'show_job_user_priority',
			'tip'     => __('Job Priority (User Priority)', 'grid'),
			'align'	  => 'right',
			'sort'    => 'ASC',
		),
		'nosort0' => array(
			'display' => __('Nice', 'grid'),
			'dbname'  => 'show_nice',
			'sort'    => 'ASC'
		),
		'mem_requested' => array(
			'display' => __('Mem Req', 'grid'),
			'dbname'  => 'show_req_memory',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'mem_reserved' => array(
			'display' => __('Mem Res', 'grid'),
			'dbname'  => 'show_reserve_memory',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'wasted_memory' => array(
			'display' => __('Mem Wasted', 'grid'),
			'dbname'  => 'show_wasted_memory',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
 		'max_memory' => array(
			'display' => __('Max Mem', 'grid'),
			'dbname'  => 'show_max_memory',
			'align'	  => 'right',
			'sort'    => 'ASC'
		),
 		'max_swap' => array(
			'display' => __('Max VMem', 'grid'),
			'dbname'  => 'show_max_swap',
			'align'	  => 'right',
			'sort'    => 'ASC'
		),
 		'mem_used' => array(
			'display' => __('Mem Usage', 'grid'),
			'dbname'  => 'show_cur_memory',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
 		'swap_used' => array(
			'display' => __('VMem', 'grid'),
			'dbname'  => 'show_cur_swap',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'gpu_max_memory' => array(
			'display' => __('Max GPU Mem', 'grid'),
			'dbname'  => 'show_gpu_max_memory',
			'align'	  => 'right',
			'sort'    => 'ASC'
		),
		'gpu_mem_used' => array(
			'display' => __('GPU Mem Usage', 'grid'),
			'dbname'  => 'show_cur_gpu_memory',
			'align'	  => 'right',
			'sort'    => 'ASC'
		),
		'cpu_used' => array(
			'display' => __('CPU Usage', 'grid'),
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'efficiency' => array(
			'display' => __('Core Eff', 'grid'),
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'num_nodes' => array(
			'display' => __('Num Nodes', 'grid'),
			'dbname'  => 'show_nodes_cpus',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'num_cpus' => array(
			'display' => __('Num CPUs', 'grid'),
			'dbname'  => 'show_nodes_cpus',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'maxNumProcessors' => array(
			'display' => __('Max Procs', 'grid'),
			'dbname'  => 'show_max_processors',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'num_gpus' => array(
			'display' => __('Num GPUs', 'grid'),
			'dbname'  => 'show_gpus',
			'align'	  => 'right',
			'sort'    => 'DESC'
		),
		'submit_time' => array(
			'display' => __('Submit Time', 'grid'),
			'dbname'  => 'show_submit_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'start_time' => array(
			'display' => __('Start Time', 'grid'),
			'dbname'  => 'show_start_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'end_time' => array(
			'display' => __('End Time', 'grid'),
			'dbname'  => 'show_end_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pend_time' => array(
			'display' => __('Pend', 'grid'),
			'dbname'  => 'show_pend_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ineligiblePendingTime' => array(
			'display' => __('Ineli Pend', 'grid'),
			'dbname'  => 'show_ineligiblependingtime',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'effectivePendingTimeLimit' => array(
			'display' => __('PTL', 'grid'),
			'dbname'  => 'show_effectivependingtimelimit',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'effectiveEligiblePendingTimeLimit' => array(
			'display' => __('Eligible PTL', 'grid'),
			'dbname'  => 'show_effectiveeligiblependingtimelimit',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'run_time' => array(
			'display' => __('Run Time', 'grid'),
			'dbname'  => 'show_run_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'runtimeEstimation' => array(
			'display' => __('Run Est', 'grid'),
			'dbname'  => 'show_runtime_estimation',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'gpu_exec_time' => array(
			'display' => __('GPU Exec Time', 'grid'),
			'dbname'  => 'show_gpu_exec_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ssusp_time' => array(
			'display' => __('SSusp', 'grid'),
			'dbname'  => 'show_ssusp_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'ususp_time' => array(
			'display' => __('USusp', 'grid'),
			'dbname'  => 'show_ususp_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'psusp_time' => array(
			'display' => __('PSusp', 'grid'),
			'dbname'  => 'show_psusp_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'unkwn_time' => array(
			'display' => __('Unknown', 'grid'),
			'dbname'  => 'show_unkwn_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'prov_time' => array(
			'display' => __('Prov', 'grid'),
			'dbname'  => 'show_prov_time',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nosort3' => array(
			'display' => __('Q-%s Used/Lim', format_job_slots('after'), 'grid'),
			'tip'     => __('Queue Level Job Slot Limit and the amount consumed in the Queue.', 'grid'),
			'dbname'  => 'show_queue_limit',
			'align'   => 'right'
		),
		'nosort4' => array(
			'display' => __('UQ-%s Used/Lim', format_job_slots('after'), 'grid'),
			'tip'     => __('User Queue Level Job Slot Limit, and the number that the User has consumed of that Limit.', 'grid'),
			'dbname'  => 'show_user_queue_limit',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nosort5' => array(
			'display' => __('U-%s Used/Lim', format_job_slots('after'), 'grid'),
			'tip'     => __('User Level Job Slot Limit, and the number that the User has consumed of that Limit.', 'grid'),
			'dbname'  => 'show_user_cpu_limit',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nosort6' => array(
			'display' => __('U-%s Run/Pend', format_job_slots('after'), 'grid'),
			'tip'     => __('The number of Jobs the User has Running and Pending at the moment.', 'grid'),
			'dbname'  => 'show_run_pend_jobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nosort7' => array(
			'display' => __('Q-%s Avail/Total', format_job_slots('after'), 'grid'),
			'tip'     => __('The number of Job Slots available for pending Jobs for the Queue and the Total defined.', 'grid'),
			'dbname'  => 'show_avail_cpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}

function grid_job_export_display_array() {
	global $config;

	$display_array[] = 'jobid';
	$display_array[] .= 'jobarrayindex';

	if (read_grid_config_option('export_clusterid') == 'on') {
		$display_array[] .= 'clusterid';
	}
	if (read_grid_config_option('export_clustername') == 'on') {
		$display_array[] .= 'clustername';
	}
	if (read_grid_config_option('export_jobname') == 'on') {
		$display_array[] .= 'jobname';
	}
	if (read_grid_config_option('export_jobdescription') == 'on') {
		$display_array[] .= 'jobDescription';
	}
	if (read_grid_config_option('export_user') == 'on') {
		$display_array[] .= 'user';
	}
	if (read_grid_config_option('export_queue') == 'on') {
		$display_array[] .= 'queue';
	}
	if (read_grid_config_option('export_project') == 'on') {
		$display_array[] .= 'projectName';
	}
	if (read_grid_config_option('export_command') == 'on') {
		$display_array[] .= 'command';
	}
	if (read_grid_config_option('export_working_directory') == 'on') {
		$display_array[] .= 'cwd';
	}
	if (read_grid_config_option('export_error_file') == 'on') {
		$display_array[] .= 'errFile';
	}
	if (read_grid_config_option('export_input_file') == 'on') {
		$display_array[] .= 'inFile';
	}
	if (read_grid_config_option('export_output_file') == 'on') {
		$display_array[] .= 'outFile';
	}
	if (read_grid_config_option('export_from_host') == 'on') {
		$display_array[] .= 'from_host';
	}
	if (read_grid_config_option('export_exec_host') == 'on') {
		$display_array[] .= 'exec_host';
	}
	if (read_grid_config_option('export_nice') == 'on') {
		$display_array[] .= 'nice';
	}
	if (read_grid_config_option('export_nodes_cpus') == 'on') {
		$display_array[] .= 'num_nodes';
		$display_array[] .= 'num_cpus';
	}
	if (read_grid_config_option('export_max_processors') == 'on') {
		$display_array[] .= 'maxNumProcessors';
	}
	if (read_grid_config_option('export_status') == 'on') {
		$display_array[] .= 'stat';
	}
	if (read_grid_config_option('export_exit') == 'on') {
		$display_array[] .= 'exitcode';
	}
	if (read_grid_config_option('export_exitinfo') == 'on') {
		$display_array[] .= 'exitInfo';
	}
	if (read_grid_config_option('export_req_memory') == 'on') {
		$display_array[] .= 'mem_requested';
	}
	if (read_grid_config_option('export_reserve_memory') == 'on') {
		$display_array[] .= 'mem_reserved';
	}
	if (read_grid_config_option('export_wasted_memory') == 'on') {
		$display_array[] .= 'wasted_memory';
	}
	if (read_grid_config_option('export_resource_requirement') == 'on') {
		$display_array[] .= 'res_requirements';
	}
	if (read_grid_config_option('export_submit_time') == 'on') {
		$display_array[] .= 'submit_time';
	}
	if (read_grid_config_option('export_start_time') == 'on') {
		$display_array[] .= 'start_time';
	}
	if (read_grid_config_option('export_end_time') == 'on') {
		$display_array[] .= 'end_time';
	}
	if (read_grid_config_option('export_pend_time') == 'on') {
		$display_array[] .= 'pend_time';
	}
	if (read_grid_config_option('export_run_time') == 'on') {
		$display_array[] .= 'run_time';
	}
	if (read_grid_config_option('export_runtime_estimation') == 'on') {
		$display_array[] .= 'runtimeEstimation';
	}
	if (read_grid_config_option('export_combinedresreq') == 'on') {
		$display_array[] .= 'combinedResreq';
	}
	if (read_grid_config_option('export_effectiveresreq') == 'on') {
		$display_array[] .= 'effectiveResreq';
	}
	if (read_grid_config_option('export_psusp_time') == 'on') {
		$display_array[] .= 'psusp_time';
	}
	if (read_grid_config_option('export_ssusp_time') == 'on') {
		$display_array[] .= 'ssusp_time';
	}
	if (read_grid_config_option('export_ususp_time') == 'on') {
		$display_array[] .= 'ususp_time';
	}
	if (read_grid_config_option('export_unkwn_time') == 'on') {
		$display_array[] .= 'unkwn_time';
	}
	if (read_grid_config_option('export_prov_time') == 'on') {
		$display_array[] .= 'prov_time';
 	}
	if (read_grid_config_option('export_ineligiblependingtime') == 'on') {
		$display_array[] .= 'ineligiblePendingTime';
 	}
	if (read_grid_config_option('export_pendstate') == 'on') {
		$display_array[] .= 'pendState';
 	}
	if (read_grid_config_option('export_effectivependingtimelimit') == 'on') {
		$display_array[] .= 'effectivePendingTimeLimit';
 	}
	if (read_grid_config_option('export_effectiveeligiblependingtimelimit') == 'on') {
		$display_array[] .= 'effectiveEligiblePendingTimeLimit';
 	}
	if (read_grid_config_option('export_lproject') == 'on') {
		$display_array[] .= 'licenseProject';
	}
	if (read_grid_config_option('export_ugroup') == 'on') {
		$display_array[] .= 'userGroup';
	}
	if (read_grid_config_option('export_chargedsaap') == 'on') {
		$display_array[] .= 'chargedSAAP';
	}
	if (read_grid_config_option('export_sla') == 'on') {
		$display_array[] .= 'sla';
	}
	if (read_grid_config_option('export_pgroup') == 'on') {
		$display_array[] .= 'parentGroup';
	}
	if (read_grid_config_option('export_app') == 'on') {
		$display_array[] .= 'app';
	}
	if (read_grid_config_option('export_jgroup') == 'on') {
		$display_array[] .= 'jobGroup';
	}
	if (read_grid_config_option('export_job_priority_from_job_detail') == 'on') {
		$display_array[] .= 'jobPriority';
	}
	if (read_grid_config_option('export_user_priority_from_job_detail') == 'on') {
		$display_array[] .= 'userPriority';
	}

	$display_array[] .= 'mem_used';
	$display_array[] .= 'swap_used';
	$display_array[] .= 'max_memory';
	$display_array[] .= 'max_swap';
	$display_array[] .= 'cpu_used';
	$display_array[] .= 'efficiency';
	$display_array[] .= 'stime';
	$display_array[] .= 'utime';

	if (read_grid_config_option('export_gpus') == 'on') {
		$display_array[] .= 'num_gpus';
	}
	if (read_grid_config_option('export_cur_gpu_memory') == 'on') {
		$display_array[] .= 'gpu_mem_used';
	}
	if (read_grid_config_option('export_gpu_max_memory') == 'on') {
		$display_array[] .= 'gpu_max_memory';
	}
	if (read_grid_config_option('export_gpu_exec_time') == 'on') {
		$display_array[] .= 'gpu_exec_time';
	}
	if (read_grid_config_option('export_gpuresreq') == 'on') {
		$display_array[] .= 'gpuResReq';
	}
	if (read_grid_config_option('export_gpucombinedresreq') == 'on') {
		$display_array[] .= 'gpuCombinedResreq';
	}
	if (read_grid_config_option('export_gpueffectiveresreq') == 'on') {
		$display_array[] .= 'gpuEffectiveResreq';
	}
	if (read_grid_config_option('export_dependCond') == 'on') {
		$display_array[] .= 'dependCond';
	}

	foreach ($display_array as $row) {
		$new[$row] = $row;
	}

	return $new;
}

function grid_jobs_build_export_header() {
	global $config;

	$display_array = grid_job_export_display_array();
	$i = 0;
	$xport_row = "";

	if (cacti_sizeof($display_array)) {
	foreach ($display_array as $item) {
		if ($item == 'efficiency') {
			$xport_row .= ($i > 0 ? ",":"") . '"core_eff"';
		} else {
			$xport_row .= ($i > 0 ? ",":"") . '"' . $item . '"';
		}
		$i++;
	}
	}

	return $xport_row;
}

function grid_jobs_build_export_row($job, &$queue_nice_levels) {
	global $config;

	$jobname     = grid_get_group_jobname($job);
	$exitstatus  = grid_get_exit_code($job["exitStatus"]);
	$clustername = grid_get_clustername($job["clusterid"]);
	/*
	$mem_request = "0";
	$mem_reserve = "0";

	grid_job_get_mem_reserve_from_resreq($job, &$mem_request, &$mem_reserve);
	*/

	$xport_row   = '"' . $job['jobid'] . '","';
	$xport_row .= $job["indexid"] . '","';

	if (read_grid_config_option("export_clusterid") == "on") {
		$xport_row .= $job["clusterid"] . '","';
	}
	if (read_grid_config_option("export_clustername") == "on") {
		$xport_row .= $clustername . '","';
	}
	if (read_grid_config_option("export_jobname") == "on") {
		$cmdstring = $jobname;
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_jobdescription") == "on") {
		$xport_row .= $job["jobDescription"] . '","';
	}
	if (read_grid_config_option("export_user") == "on") {
		$xport_row .= $job["user"] . '","';
	}
	if (read_grid_config_option("export_queue") == "on") {
		$xport_row .= $job["queue"] . '","';
	}
	if (read_grid_config_option("export_project") == "on") {
		$xport_row .= $job["projectName"] . '","';
	}
	if (read_grid_config_option("export_command") == "on") {
		$cmdstring = $job["command"];
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_working_directory") == "on") {
		$cmdstring = $job["cwd"];
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_error_file") == "on") {
		$cmdstring = $job["errFile"];
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_input_file") == "on") {
		$cmdstring = $job["inFile"];
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_output_file") == "on") {
		$cmdstring = $job["outFile"];
		$cmdstring = str_replace('"','""',$cmdstring);
		$xport_row .= $cmdstring . '","';
	}
	if (read_grid_config_option("export_from_host") == "on") {
		$xport_row .= $job["from_host"] . '","';
	}
	if (read_grid_config_option("export_exec_host") == "on") {
		$xport_row .= $job["exec_host"] . '","';
	}
	if (read_grid_config_option("export_nice") == "on") {
		$xport_row .= $queue_nice_levels[$job["clusterid"] . "-" . $job["queue"]] . '","';
	}
	if (read_grid_config_option("export_nodes_cpus") == "on") {
		$xport_row .= $job["num_nodes"] . '","';
		$xport_row .= $job["num_cpus"] . '","';
	}
	if (read_grid_config_option("export_max_processors") == "on") {
		$xport_row .= $job['maxNumProcessors'] . '","';
	}
	if (read_grid_config_option("export_status") == "on") {
		$xport_row .= $job["stat"] . '","';
	}
	if (read_grid_config_option("export_exit") == "on") {
		$exit_code = (($job['stat'] == "DONE") || ($job['stat'] == "EXIT") || ($job['stat'] == "UNKWN")) ? $exitstatus : '0';
		$xport_row .= $exit_code . '","';
	}
	if (read_grid_config_option("export_exitinfo") == "on") {
		$exit_info = ((isset($job['exceptMask']) && $job['exceptMask'] > 0) || (isset($job['stat']) && isset($job['exitInfo']) && $job['stat'] == "EXIT" && $job['exitInfo'] >= 0))? getExceptionStatus($job['exceptMask'], $job['stat'] == "EXIT" ? $job['exitInfo'] : -1):'';
		$xport_row .= $exit_info . '","';
	}
	if (read_grid_config_option("export_req_memory") == "on") {
		$xport_row .= $job["mem_requested"] . '","';
	}
	if (read_grid_config_option("export_reserve_memory") == "on") {
		$xport_row .= $job["mem_reserved"] . '","';
	}
	if (read_grid_config_option("export_wasted_memory") == "on") {
		if ($job["stat"] == 'PEND') {
			$xport_row .= 0 . '","';
		}
		else {
			$wasted_mem = $job["mem_reserved"] - $job["max_memory"];
			if ($wasted_mem >= 0 ) {
				$negative_wasted_mem = -1 * $wasted_mem;
				$xport_row .= $negative_wasted_mem . '","';
			}
			else {
				$xport_row .= "0" . '","';
			}
		}
	}
	if (read_grid_config_option("export_resource_requirement") == "on") {
		$xport_row .= $job["res_requirements"] . '","';
	}
	if (read_grid_config_option("export_submit_time") == "on") {
		$xport_row .= $job["submit_time"] . '","';
	}
	if (read_grid_config_option("export_start_time") == "on") {
		$xport_row .= $job["start_time"] . '","';
	}
	if (read_grid_config_option("export_end_time") == "on") {
		$xport_row .= $job["end_time"] . '","';
	}
	if (read_grid_config_option("export_pend_time") == "on") {
		$xport_row .= $job["pend_time"] . '","';
	}
	if (read_grid_config_option("export_run_time") == "on") {
		$xport_row .= $job["run_time"] . '","';
	}
	if (read_grid_config_option("export_runtime_estimation") == "on") {
		$xport_row .= ($job["runtimeEstimation"] > 0 ? $job["runtimeEstimation"]:"N/A") . '","';
	}
	if (read_grid_config_option("export_combinedresreq") == "on") {
		$xport_row .= $job["combinedResreq"] . '","';
	}
	if (read_grid_config_option("export_effectiveresreq") == "on") {
		$xport_row .= $job["effectiveResreq"] . '","';
	}
	if (read_grid_config_option("export_psusp_time") == "on") {
		$xport_row .= $job["psusp_time"] . '","';
	}
	if (read_grid_config_option("export_ssusp_time") == "on") {
		$xport_row .= $job["ssusp_time"] . '","';
	}
	if (read_grid_config_option("export_ususp_time") == "on") {
		$xport_row .= $job["ususp_time"] . '","';
	}
	if (read_grid_config_option("export_unkwn_time") == "on") {
		$xport_row .= $job["unkwn_time"] . '","';
	}
	if (read_grid_config_option("export_prov_time") == "on") {
		$xport_row .= $job["prov_time"] . '","';
	}
	if (read_grid_config_option("export_ineligiblependingtime") == "on") {
		$xport_row .= $job["ineligiblePendingTime"] . '","';
	}
	if (read_grid_config_option("export_pendstate") == "on") {
		$xport_row .= display_ineligible_pend_reason($job["pendState"]) . '","';
	}
	if (read_grid_config_option("export_effectivependingtimelimit") == "on") {
		$xport_row .= $job["effectivePendingTimeLimit"] . '","';
 	}
	if (read_grid_config_option("export_effectiveeligiblependingtimelimit") == "on") {
		$xport_row .= $job["effectiveEligiblePendingTimeLimit"] . '","';
 	}
	if (read_grid_config_option("export_lproject") == "on") {
		$xport_row .= $job["licenseProject"] . '","';
	}
	if (read_grid_config_option("export_ugroup") == "on") {
		$xport_row .= $job["userGroup"] . '","';
	}
	if (read_grid_config_option("export_chargedsaap") == "on") {
		$xport_row .= $job["chargedSAAP"] . '","';
	}
	if (read_grid_config_option("export_sla") == "on") {
		$xport_row .= $job["sla"] . '","';
	}
	if (read_grid_config_option("export_pgroup") == "on") {
		$xport_row .= $job["parentGroup"] . '","';
	}
	if (read_grid_config_option("export_app") == "on") {
		$xport_row .= $job["app"] . '","';
	}
	if (read_grid_config_option("export_jgroup") == "on") {
		$xport_row .= $job["jobGroup"] . '","';
	}
	if (read_grid_config_option("export_job_priority_from_job_detail") == "on") {
		$xport_row .= $job["jobPriority"] . '","';
	}
	if (read_grid_config_option("export_user_priority_from_job_detail") == "on") {
		$xport_row .= $job["userPriority"] . '","';
	}

	$xport_row .= $job["mem_used"] . '","';
	$xport_row .= $job["swap_used"] . '","';
	$xport_row .= $job["max_memory"] . '","';
	$xport_row .= $job["max_swap"] . '","';
	$xport_row .= $job["cpu_used"] . '","';
	$xport_row .= $job["efficiency"] . '","';
	$xport_row .= $job["stime"] . '","';
	$xport_row .= $job["utime"] . '"';

	if (read_grid_config_option('export_gpus') == 'on') {
		$xport_row .= ',"' . $job['num_gpus'] . '"';
	}
	if (read_grid_config_option('export_cur_gpu_memory') == 'on') {
		$xport_row .= ',"' . $job['gpu_mem_used'] . '"';
	}
	if (read_grid_config_option('export_gpu_max_memory') == 'on') {
		$xport_row .= ',"' . $job['gpu_max_memory'] . '"';
	}
	if (read_grid_config_option('export_gpu_exec_time') == 'on') {
		$xport_row .= ',"' . $job['gpu_exec_time'] . '"';
	}
	if (read_grid_config_option('export_gpuresreq') == 'on') {
		$xport_row .= ',"' . $job['gpuResReq'] . '"';
	}
	if (read_grid_config_option('export_gpucombinedresreq') == 'on') {
		$xport_row .= ',"' . $job['gpuCombinedResreq'] . '"';
	}
	if (read_grid_config_option('export_gpueffectiveresreq') == 'on') {
		$xport_row .= ',"' . $job['gpuEffectiveResreq'] . '"';
	}
	if (read_grid_config_option('export_dependCond') == 'on') {
		$xport_row .= ',"' . $job['dependCond'] . '"';
	}

	return $xport_row;
}

function bjobs_form_action($job_page) {
	global $config, $grid_job_control_actions, $signal;

	if (basename($job_page) == 'heuristics_jobs.php') {
		if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
			$username="";
		} else {
			$username = get_username($_SESSION['sess_user_id']);
		}
	} elseif (basename($job_page) == 'grid_bjobs.php') {
		$username = '';
	}

	$action_level = 'job';
	$count_ok = 0;
	$count_fail = 0;
	debug_log_clear('grid_admin');

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {
		$selected_items = unserialize(stripslashes(get_request_var('selected_items')));
		$selected_items_whole = unserialize(stripslashes(get_request_var('selected_items_whole')));

		if (api_plugin_hook_function('job_actions_execute', get_request_var('drp_action')) == get_request_var('drp_action')) {
			if (isset_request_var('signal')) {
				$json_return_format = sorting_json_format($selected_items_whole, get_request_var('signal'), 'signal', '', $username);
			} elseif (isset_request_var('job_array')) {
				if (get_request_var('drp_action') == 3) { //Bswitch
					$sort_job_array = array(); //need to assign jobs of the same cluster with the same arg to be passed to advocate
					for($i=0;$i<count(get_request_var('job_array'));$i++) {
						$tmp_job_array = get_request_var('job_array');
						$clusterid_info_check = explode(':', $tmp_job_array[$i]);
						for($j=0;$j<count($selected_items_whole);$j++) {
							$explode_cluster_whole_array = explode(':', $selected_items_whole[$j]);
							if ($explode_cluster_whole_array[1] == $clusterid_info_check[1]) {
								$sort_job_array[] = $clusterid_info_check[0];
							}
						}
					}
					$json_return_format = sorting_json_format($selected_items_whole, $sort_job_array, '', '', $username);
				} else
					$json_return_format = sorting_json_format($selected_items_whole, get_request_var('job_array'), '', '', $username);
			} else {
				$json_return_format = sorting_json_format($selected_items_whole, '', '', '', $username);
			}

			switch(get_request_var('drp_action')) {
				case '1':
					$job_action = 'btop';
					break;
				case '2':
					$job_action = 'bbot';
					break;
				case '3':
					$job_action = 'switch';
					break;
				case '4':
					$job_action = 'run';
					break;
				case '5':
					$job_action = 'stop';
					break;
				case '6':
					$job_action = 'resume';
					break;
				case '7':
					$job_action = 'kill';
					break;
				case '8':
					$job_action = 'forcekill';
					break;
				case '9':
					$job_action = 'sigkill';
					break;
				case '10':
					$job_action = 'killdone';
					break;
				}

			$advocate_key = session_auth();

			$json_cluster_info = array (
				'key' => $advocate_key,
				'action' => $job_action,
				'target' => $json_return_format,
			);

			$output = json_encode($json_cluster_info);

			$curl_output =  exec_curl($action_level, $output); //pass to advocate for processing

			if ($curl_output['http_code'] == 400) {
				raise_message(134);
			} elseif ($curl_output['http_code'] == 500) {
				raise_message(135);
			} else {
				if ($curl_output['http_code'] == 200) {
					$log_action = $grid_job_control_actions[get_request_var('drp_action')];
					$json_output = json_decode($output);
					$username_log = get_username($_SESSION['sess_user_id']);
					foreach ($json_output->target as $target) {
						cacti_log("Job '{$target->name}', {$log_action} by '{$username_log}'.", false, 'LSFCONTROL');
					}
				} else {
					raise_message(136);
				}
			}

			$content_response = $curl_output['content']; //return response from advocate in json format

			$json_decode_content_response = json_decode($content_response,true);
			$rsp_content = $json_decode_content_response['rsp'];

			if (!empty($rsp_content)) {
				for ($k=0;$k<count($rsp_content);$k++) {
					$key_sort[$k] = (array)$rsp_content[$k]['clusterid'];
				}

				asort($key_sort);
				$count_ok = 0;
				$count_fail = 0;

				$output_message='';
				foreach ( $key_sort as $key => $val) {
					foreach ($rsp_content as $key_rsp_content => $value) {
						if ($key_rsp_content == $key) {
							if ($value['status_code'] == 0) {
								$return_status = 'OK';
								$count_ok ++;
							} else {
								$count_fail ++;
								$return_status = 'Failed. Status Code: '.$value['status_code'] ;
							}
							$message='Status:'.$return_status.' - Cluster ID:'.$value['clusterid'].' - '.$value['status_message'].'<br/>';
                                                        $output_message=$output_message.$message;
						}
					}
				}
				if($count_fail>0)
              				raise_message('mymessage', $output_message, MESSAGE_LEVEL_ERROR);
            			else
              				raise_message('mymessage', $output_message, MESSAGE_LEVEL_INFO);
			}
		}
		header('Location: ' . $job_page);
		exit;
	}

	$cluster_list = '';
	$i = 0;

	foreach ($_POST as $key => $value) {
		if (strncmp($key, 'chk_', '4') == 0) {
			$cluster_whole_array[$i] = str_replace('_', '[', substr($key, 4));
			$cluster_details = explode(':',substr($key, 4));
			$cluster_details[0] = str_replace('_', '[', $cluster_details[0]);

			/* ================= input validation ================= */
			input_validate_input_number($cluster_details[1]);
			/* ================= input validation ================= */

			$cluster_list .= '<li>' . __('Job ID: %s from Cluster Name: %s', $cluster_details[0], grid_get_clustername($cluster_details[1]), 'grid') . '</li>';
			$cluster_array[$i] = $cluster_details[1];
			$cluster_array_job[$i] = $cluster_details[0];

			$i++;
		}
	}
	//include_once($include_file);
	general_header();

	form_start($job_page);

	if ((get_request_var('drp_action') != 9)&&(get_request_var('drp_action') != 3)&&(get_request_var('drp_action') != 4)) {
		html_start_box($grid_job_control_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');
	}

	switch(get_request_var('drp_action')) {
		case '1':	/* Btop  */
			$question = __('Are you sure you want to move the following job(s) to the top of the queue?', 'grid') . '</p>';
			break;
		case '2':	/* Bbot */
			$question = __('Are you sure you want to move the following job(s) to the bottom of the queue?', 'grid') . '</p>';
			break;
		case '3':	/* BSwitch */
			$question = __('Are you sure you want to switch the following job(s) to another queue?', 'grid') . '</p>';
			break;
		case '4':	/* BRun */
			$question = __('Are you sure you want to run the following job(s)', 'grid') . '</p>';
			break;
		case '5':	/* BStop*/
			$question = __('Are you sure you want to stop the following job(s)', 'grid') . '</p>';
			break;
		case '6':	/* BResume*/
			$question = __('Are you sure you want to resume the following job(s)', 'grid') . '</p>';
			break;
		case '7':	/* Bkill*/
			$question = __('Are you sure you want to kill the following job(s)', 'grid') . '</p>';
			break;
		case '8':	/* forcekill*/
			$question = __('Are you sure you want to force kill the following job(s)', 'grid') . '</p>';
			break;
		case '9':	/* sigkill*/
			$question = __('Are you sure you want to kill the following job(s) with a signal', 'grid') . '</p>';
			break;
		case '10':	/* killdone */
			$question = __('Are you sure you want to kill the following job(s) as DONE', 'grid') . '</p>';
			break;
	}

	$i = 0;
	if (!empty($cluster_array)) {
		switch(get_request_var('drp_action')) {
			case '1':
			case '2':
			case '5':
			case '6':
			case '7':
			case '8':
			case '10':
				print " <tr>
					<td class='textArea'>
					<p>$question</p>
					<div class='itemlist'><ul>$cluster_list</ul></div>";
				print " </td></tr></td></tr>
					<tr><td class='textArea deviceDown'>
					" . __('NOTE: Please wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "
					</td></tr>\n";
				break;
			case '3':	// Bswitch
				$job_list = '';
				$cluster_count = 0;
				$old_cluster = 0;
				$clusterid_info = array();
				$cluster_whole_array = array();
				$i = 0;
				foreach ($_POST as $key => $value) {
					if (strncmp($key, 'chk_', '4') == 0) {
						$cluster_whole_array[$i] = str_replace('_', '[', substr($key, 4));
						$cluster_details = explode(':',substr($key, 4));

						if ($old_cluster != $cluster_details[1]) {
							$old_cluster = $cluster_details[1];
							$clusterid_info[] = $cluster_details[1];
							$cluster_count += 1;
						}
						$i++;
					}
				}

				//differentiate the jobs that are from different clusters
				$i = 0;
				for ($j=0;$j<count($clusterid_info);$j++) {
					foreach ($_POST as $key => $value) {
						if (strncmp($key, 'chk_', '4') == 0) {
							$cluster_whole_array[$i] = str_replace('_', '[', substr($key, 4));
							$cluster_details = explode(':',substr($key, 4));

							$cluster_details[0] = str_replace('_', '[', $cluster_details[0]);
							if (strstr($cluster_details[0], '[')) {
								$cluster_job = explode('[', $cluster_details[0]);
								$job_indexid = substr_replace($cluster_job[1], '', -1);
								$jobid = $cluster_job[0];
								$job_info =  db_fetch_row_prepared('SELECT queue, user, stat FROM grid_jobs WHERE jobid=? AND indexid=?', array($jobid, $job_indexid));
							} else {
								$job_info =  db_fetch_row_prepared('SELECT queue, user, stat FROM grid_jobs WHERE jobid=?', array($cluster_details[0]));
							}

							if ($clusterid_info[$j] == $cluster_details[1]) {
								$job_list .= '<li>'.str_replace('_', '[', $cluster_details[0]).'<br>Queue : '.$job_info['queue'].' - Submitted by:'.$job_info['user'].' - Current Status:'.$job_info['stat'].'</li>';
							}
							$i++;
						}
					}

					$cluster_result = db_fetch_assoc_prepared("SELECT DISTINCT queuename
						FROM grid_jobs
						INNER JOIN grid_queues
						ON grid_jobs.clusterid = grid_queues.clusterid
						AND grid_jobs.queue != grid_queues.queuename
						WHERE grid_jobs.clusterid=?
						AND grid_queues.clusterid=?", array($clusterid_info[$j], $clusterid_info[$j]));

					html_start_box(__('Switching Job(s) in Cluster %s', $clusterid_info[$j], 'grid'), '60%', '', '3', 'center', '');

					print " <tr>
						<td class='textArea'>
						<p>Choose the queue that the following jobs will be switched to?</p>
						<ul>$job_list</ul></td>";
					print " <td class='textArea' width=30%><select name='job_array[]' style='width:70%'>";

					foreach ($cluster_result as $result) {
						print '<option value="'.$result['queuename'].':'.$clusterid_info[$j].'"';
						if ($result['queuename']=='normal') {
							print ' selected';
						}
						print '>'. $result['queuename'] . '</option>'."\n";
					}
					$job_list = '';
					print '</select></td></tr>';
				}
				$cluster_whole_array = array_unique($cluster_whole_array);
				print " <tr><td colspan='2' class='textArea'>
					<font class='textError'>" . __('NOTE: Please wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "</font>
					</td></tr>";
				break;
			case '4':	// Brun
				$i = 0;
				foreach ($_POST as $key => $value) {
					if (strncmp($key, 'chk_', '4') == 0) {
						$cluster_whole_array[$i] = str_replace('_', '[', substr($key, 4));
						$cluster_details = explode(':',substr($key, 4));
						$cluster_result = db_fetch_assoc_prepared('SELECT host from grid_hosts where clusterid=? ORDER BY host asc', array($cluster_details[1]));
						html_start_box(__('Running Job [ %s ] on other machine', str_replace('_', '', $cluster_details[0]), 'grid'), '60%', '', '3', 'center', '');
						print " <tr>
							<td>Running job on the following machine.</td>
							<td><select name='job_array[]'>";
						foreach ($cluster_result as $result) {
							/*** create the options ***/
							print '<option value="'.$result['host'].'"';
							print '>'. $result['host'] . '</option>'."\n";
						}
						print "</select></td></tr>";
						html_end_box(false);
						$i++;
					}
				}
				html_start_box('', '60%', '', '3', 'center', '');
				print " <tr><td class='textArea'>
					<font class='textError'>" . __('NOTE: Please wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "</font>
					</td></tr>";
				break;
			case '9':	// Sigkill
				html_start_box($grid_job_control_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');
				print "<tr>
					<td>" . __('Select the signal to send to job', 'grid') . "
					</td>
					<td><select id='signal' name='signal'>";

				foreach ($signal as $key => $value) {
					print"<option value=$key>$value</option>";
				}

				print " </select></td></tr>";
				print " <tr><td colspan='2' class='textArea'>
					<font class='textError'>" . __('NOTE: Please wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "</font>
					</td></tr>";
				break;
			default:
				$options = array(
					'cluster_list' => $cluster_list,
					'cluster_array' => $cluster_array,
					'cluster_job_array' => $cluster_array_job
				);

				api_plugin_hook_function('job_actions_question', $options);

				break;
		}
	}

	if (!isset($cluster_array)) {
		raise_message(40);
		header('Location: grid_bjobs.php?header=false');
		exit;
	} else {
		$save_html = "<input type='submit' value='" . __esc('Yes', 'grid') . "' alt=''>";
		$button_false = "No";
	}

	print "<tr>
		<td colspan='2' class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($cluster_array) ? serialize($cluster_array) : '') . "'>
			<input type='hidden' name='selected_items_job' value='" . (isset($cluster_array_job) ? serialize($cluster_array_job) : '') . "'>
			<input type='hidden' name='selected_items_whole' value='" . (isset($cluster_whole_array) ? serialize($cluster_whole_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . $button_false ."' alt='' onClick='cactiReturnTo()'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();
	bottom_footer();
}

function grid_view_get_host_records() {
	$hosts = db_fetch_assoc("SELECT CONCAT(clusterid, '_', host) AS id, hostType, hostModel FROM grid_hostinfo");
	return $hosts;
}

function grid_view_get_user_records() {
	$users = db_fetch_assoc("SELECT CONCAT(clusterid, '_', user_or_group) AS id, maxJobs, numJobs, numRUN, numPEND FROM grid_users_or_groups");
	return $users;

}

function grid_view_get_queue_records() {
	$queues = db_fetch_assoc("SELECT CONCAT(clusterid, '_', queuename) AS id, numslots, runjobs, maxjobs,
		userJobLimit, openDedicatedSlots + openSharedSlots AS availSlots FROM grid_queues");
	return $queues;
}

function grid_view_get_user_queue_slot_records() {
	$user_queues = db_fetch_assoc("SELECT CONCAT(clusterid, '_', user, '_', queue) AS id, SUM(num_cpus) AS total_cpus
		FROM grid_jobs WHERE stat IN ('RUNNING','PROV') GROUP BY clusterid, user, queue");
	return $user_queues;
}

function grid_make_rows_query($sql_query) {
	$order_pos = strpos($sql_query, "ORDER ");
	if ($order_pos == 0) {
		$limit_pos = strpos($sql_query, "LIMIT ");
	} else {
		$limit_pos = $order_pos;
	}

	$rows_query = "SELECT COUNT(*) FROM (" . substr($sql_query, 0, $limit_pos) . ") AS tmp";
	return $rows_query;
}

function graphViewFilter($job_page = 'grid_bjobs.php') {
	global $config, $grid_rows_selector, $grid_refresh_interval, $current_user;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull, $host_string;
	global $graph_template_type;

	get_filter_request_var('clusterid');
	get_filter_request_var('jobid');
	get_filter_request_var('indexid');
	get_filter_request_var('submit_time');

	?>
	<tr class='noprint'>
		<td class='noprint'>
			<form name='form_graph_view' method='post'>
			<table class='filterTable'>
				<tr class='noprint'>
					<td>
						<?php print __('Host', 'grid');?>
					</td>
					<td>
						<select id='host_id' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('host_id') == '0') {?> selected<?php }?>><?php print __('Any', 'grid');?></option>

							<?php
							/* get policy information for the sql where clause */
							$hosts = get_allowed_devices('gl.host_id IN ' . $host_string);

							if (cacti_sizeof($hosts)) {
								foreach ($hosts as $host) {
									print "<option value='" . $host['id'] . "'"; if (get_request_var('host_id') == $host['id']) { print ' selected'; } print '>' . $host['description'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Template', 'grid');?>
					</td>
					<td>
						<select id='graph_template_id' onChange='applyFilter()'>
							<option value='0'<?php if (get_request_var('graph_template_id') == '0') {?> selected<?php }?>><?php print __('Any', 'grid');?></option>

							<?php
							$graph_templates = get_allowed_graph_templates('gl.host_id IN ' . $host_string);

							$elim_templates  = db_fetch_assoc('SELECT DISTINCT concat("et", et.id) AS id, et.name
								FROM grid_elim_templates as et
								INNER JOIN grid_elim_template_instances AS eti
								ON eti.grid_elim_template_id=et.id
								INNER JOIN grid_elim_instance_graphs AS etg
								ON etg.grid_elim_template_instance_id=eti.id
								INNER JOIN graph_local AS gl
								ON gl.id=etg.local_graph_id
								ORDER BY name');

							$new_templates = array();

							if (cacti_sizeof($graph_templates)) {
								foreach($graph_templates as $gt) {
									$new_templates[] = array(
										'id' => 'gt' . $gt['id'],
										'name' => $gt['name']
									);
								}
							}

							if (cacti_sizeof($elim_templates)) {
								$new_templates += $elim_templates;
							}

							if (cacti_sizeof($new_templates)) {
								foreach ($new_templates as $template) {
									print "<option value='" . $template['id'] . "'"; if ($graph_template_type.get_request_var('graph_template_id') == $template['id']) { print ' selected'; } print '>' . html_escape($template['name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Columns');?>
					</td>
					<td>
						<select id='columns'>
							<?php
							print "<option value='1'"; if (get_request_var('columns') == 1) { print " selected"; } print ">1</option>\n";
							print "<option value='2'"; if (get_request_var('columns') == 2) { print " selected"; } print ">2</option>\n";
							print "<option value='3'"; if (get_request_var('columns') == 3) { print " selected"; } print ">3</option>\n";
							print "<option value='4'"; if (get_request_var('columns') == 4) { print " selected"; } print ">4</option>\n";
							print "<option value='5'"; if (get_request_var('columns') == 5) { print " selected"; } print ">5</option>\n";
							?>
						</select>
					</td>
					<td>
						<label for='thumbnails'><?php print __('Thumbnails');?></label>
					</td>
					<td>
						<input type='checkbox' id='thumbnails' onChange='applyFilter()' <?php print get_request_var('thumbnails') == 'true' ? 'checked':'';?>>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>' onClick='clearFilter();'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr id='timespan'>
					<td>
						<?php print __('Presets', 'grid');?>
					</td>
					<td>
						<select id='predefined_timespan' onChange='applyGraphTimespan()'>
							<?php
							print "<option value='-99' " . (get_request_var('predefined_timespan') == -99 ? 'selected':'') . '>' . __('Job Range', 'grid') . '</option>';

							$grid_timespans[GT_CUSTOM] = __('Custom', 'grid');
							$start_val = 0;
							$end_val = cacti_sizeof($grid_timespans);

							if (cacti_sizeof($grid_timespans) > 0) {
								for ($value=$start_val; $value < $end_val; $value++) {
									print "<option value='" . $value . "'"; if (get_request_var('predefined_timespan') == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('From', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print $timespan['current_value_date1'];?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print $timespan['current_value_date2'];?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<i class='shiftArrow fa fa-backward' onClick='timeshiftGraphFilterLeft()' title='<?php print __esc('Shift Time Backward');?>'></i>
							<select id='predefined_timeshift' name='predefined_timeshift' title='<?php print __esc('Define Shifting Interval');?>'>
								<?php
								$start_val = 1;
								$end_val = cacti_sizeof($grid_timeshifts)+1;
								if (cacti_sizeof($grid_timeshifts) > 0) {
									for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
										print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print " selected"; } print ">" . title_trim($grid_timeshifts[$shift_value], 40) . "</option>\n";
									}
								}
								?>
							</select>
							<i class='shiftArrow fa fa-forward' onClick='timeshiftGraphFilterRight()' title='<?php print __esc('Shift Time Forward');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<input id='tsclear' type='button' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Clear');?>' title='<?php print __esc('Return to the default time span');?>' onClick='clearGraphTimespanFilter()'>
						</span>
					</td>
				</tr>
			</table>
			<input type='hidden' name='date1' value='<?php print $timespan['current_value_date1'];?>'>
			<input type='hidden' name='date2' value='<?php print $timespan['current_value_date2'];?>'>
			<input type='hidden' id='clusterid' value='<?php print get_request_var('clusterid');?>'>
			<input type='hidden' id='jobid' value='<?php print get_request_var('jobid');?>'>
			<input type='hidden' id='indexid' value='<?php print get_request_var('indexid');?>'>
			<input type='hidden' id='submit_time' value='<?php print get_request_var('submit_time');?>'>
			</form>
			<script type='text/javascript'>

			var refreshIsLogout = false;
			var refreshMSeconds = <?php print read_user_setting('page_refresh')*1000;?>;
			var graph_start     = <?php print grid_get_current_graph_start($timespan);?>;
			var graph_end       = <?php print grid_get_current_graph_end($timespan);?>;
			var timeOffset      = <?php print date('Z');?>;
			var pageAction      = 'viewjob&tab=hostgraph';
			pageAction += '&clusterid=' + $('#clusterid').val();
			pageAction += '&jobid=' + $('#jobid').val();
			pageAction += '&indexid=' + $('#indexid').val();
			pageAction += '&submit_time=' + $('#submit_time').val();
			var graphPage       = '<?php print $job_page;?>';
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

			$(function() {
				$.when(initPage())
				.pipe(function() {
					initializeGraphs();
				});

				$('form').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#columns').off().on('change', function() {
					applyFilter();
				});

				$('#thumbnails').off().on('click', function() {
					applyFilter();
				});
			});

			function applyFilter() {
				strURL  = graphPage + '?action=' + pageAction;
				strURL += '&filter=' + $('#filter').val();
				strURL += '&host_id=' + $('#host_id').val();
				strURL += '&graph_template_id=' + $('#graph_template_id').val();
				strURL += '&columns=' + $('#columns').val();
				strURL += '&thumbnails=' + $('#thumbnails').is(':checked');
				strURL += '&date1=' + $('#date1').val();
				strURL += '&date2=' + $('#date2').val();
				strURL += '&header=false';
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = graphPage + '?action=' + pageAction;
				strURL += '&filter=';
				strURL += '&host_id=-1';
				strURL += '&graph_template_id=-1';
				strURL += '&columns=2';
				strURL += '&page=1';
				strURL += '&thumbnails=false';
				strURL += '&header=false';
				loadPageNoHeader(strURL);
			}

			</script>
		</td>
	</tr>
	<?php
}

function jobGraph_build_query_string() {
	global $timespan;
	$query_string = 'header=false&action=viewjob&tab=jobgraph&cluster_tz=' . get_request_var('cluster_tz') .
		'&clusterid=' . get_request_var('clusterid') . '&indexid=' . get_request_var('indexid') . '&jobid=' . get_request_var('jobid') .
		'&submit_time=' . get_request_var('submit_time') . '&date1=' . $timespan['current_value_date1'] . '&date2=' . $timespan['current_value_date2'];
	return $query_string;

}

function jobGraphFilter($job, $job_page) {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull;

	get_filter_request_var('columns');
	get_filter_request_var('clusterid');
	get_filter_request_var('jobid');
	get_filter_request_var('indexid');
	get_filter_request_var('submit_time');

	?>
	<tr id='filter'>
		<td class='noprint'>
			<form name='form_graph_view' method='post'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Presets', 'grid');?>
					</td>
					<td>
						<select id='predefined_timespan'>
							<?php
							print "<option value='-99' " . ($_SESSION['sess_jg_current_timespan'] == -99 ? 'selected':'') . '>' . __('Job Range', 'grid') . '</option>';

							$grid_timespans[GT_CUSTOM] = __('Custom', 'grid');
							$start_val = 0;
							$end_val = cacti_sizeof($grid_timespans);

							if (cacti_sizeof($grid_timespans) > 0) {
								for ($value=$start_val; $value < $end_val; $value++) {
									print "<option value='" . $value . "'"; if ($_SESSION['sess_jg_current_timespan'] == $value) { print ' selected'; } print '>' . title_trim($grid_timespans[$value], 40) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward');?>'></i>
					</td>
					<td>
						<select id='predefined_timeshift'>
						<?php
						$start_val = 1;
						$end_val = cacti_sizeof($grid_timeshifts)+1;
						if (cacti_sizeof($grid_timeshifts) > 0) {
							for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
								print "<option value='" . $shift_value . "'"; if (get_request_var('predefined_timeshift') == $shift_value) { print ' selected'; } print '>' . title_trim($grid_timeshifts[$shift_value], 40) . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward', 'grid');?>'></i>
					</td>
					<td>
						<?php print __('Graph Type', 'grid');?>
					</td>
					<td>
						<select id='predefined_graph_type' title='Define Graph Type'>
							<?php
							$total_hrusage_rows = 0;
							$total_grusage_rows = 0;
							if ($job['stat'] != 'PEND') {
								$grid_host_rusage_collection = db_fetch_cell("SELECT value FROM settings WHERE name='grid_host_rusage_collection' and value='on'");
								$grid_gpu_rusage_collection = db_fetch_cell("SELECT value FROM settings WHERE name='grid_GPU_rusage_collection' and value='on'");
        						if(!empty($grid_host_rusage_collection) || !empty($grid_gpu_rusage_collection)){
									$lsf_version = db_fetch_cell_prepared("SELECT grid_pollers.lsf_version
										FROM grid_pollers JOIN grid_clusters ON grid_clusters.poller_id=grid_pollers.poller_id
										WHERE grid_clusters.clusterid=?", array($job['clusterid']));

									if (!empty($lsf_version)){
										if (lsf_version_not_lower_than($lsf_version,'8')){
											$total_hrusage_rows = 1;// as a flag
										}
										if (lsf_version_not_lower_than($lsf_version,'1010')){
											$total_grusage_rows = 1;// as a flag
										}
									}
								}
								if($total_hrusage_rows <= 0){
									$total_hrusage_rows = get_grid_job_hosts_total_rows($_REQUEST["jobid"], $_REQUEST["indexid"], $_REQUEST["clusterid"], $_REQUEST["submit_time"]);
								}
								if($total_grusage_rows <= 0){
									$total_grusage_rows = get_grid_job_gpus_total_rows($_REQUEST["jobid"], $_REQUEST["indexid"], $_REQUEST["clusterid"], $_REQUEST["submit_time"]);
								}
							}
							if($job['stat'] == 'PEND' || ($total_hrusage_rows <= 0 && $total_grusage_rows <= 0)){
								set_request_var('predefined_graph_type', 0);
							}
							print "<option value='0'"; if (get_request_var('predefined_graph_type') == 0) { print " selected"; } print ">Job Level Graphs</option>\n";
							if($total_hrusage_rows > 0){
								print "<option value='1'"; if (get_request_var('predefined_graph_type') == 1) { print " selected"; } print ">Host Level Graphs</option>\n";
							}
							if($total_grusage_rows > 0){
								print "<option value='2'"; if (get_request_var('predefined_graph_type') == 2) { print " selected"; } print ">GPU Level Graphs</option>\n";
							}
						?>
						</select>
					</td>
					<td>
						<?php print __('Columns');?>
					</td>
					<td>
						<select id='columns' onChange='applyFilter()'>
							<?php
							print "<option value='1'"; if (get_request_var('columns') == 1) { print " selected"; } print ">1</option>\n";
							print "<option value='2'"; if (get_request_var('columns') == 2) { print " selected"; } print ">2</option>\n";
							?>
						</select>
					</td>
					<td>
						<label for='legend'><?php print __('Legend', 'grid');?></label>
					</td>
					<td>
						<input id='legend' name='legend' type='checkbox' <?php print (((get_request_var('legend') == 'true') || (get_request_var('legend') == 'on')) ? 'checked': '');?> />
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>'>
							<input type='button' id='clear' onClick='clearFilter()' value='<?php print __esc('Clear', 'grid');?>'>
						</span>
					</td>
				</tr>
			</table>
			<input type='hidden' name='date1' value='<?php print $timespan['current_value_date1'];?>'>
			<input type='hidden' name='date2' value='<?php print $timespan['current_value_date2'];?>'>
			<input type='hidden' id='clusterid' value='<?php print get_request_var('clusterid');?>'>
			<input type='hidden' id='jobid' value='<?php print get_request_var('jobid');?>'>
			<input type='hidden' id='indexid' value='<?php print get_request_var('indexid');?>'>
			<input type='hidden' id='submit_time' value='<?php print get_request_var('submit_time');?>'>
			</form>
			<script type='text/javascript'>
			var pageAction      = 'action=viewjob&tab=jobgraph';
			var graphPage       = '<?php print $job_page;?>';

			function applyFilter(move_flag) {
				strURL =  graphPage + '?' + pageAction;
				strURL += '&predefined_timespan=' + $('#predefined_timespan').val();
				strURL += '&predefined_timeshift=' + $('#predefined_timeshift').val();
				strURL += '&predefined_graph_type=' + $('#predefined_graph_type').val();
				strURL += '&legend=' + $('#legend').is(':checked');
				strURL += '&columns=' + $('#columns').val();
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&indexid=' + $('#indexid').val();
				strURL += '&submit_time=' + $('#submit_time').val();
				strURL += '&header=false';
				if (move_flag==1 || move_flag==2) {
					if (move_flag == 1) {
						strURL += '&move_left_x=move_left_x';
					}
					if (move_flag == 2) {
						strURL += '&move_right_x=move_right_x';
					}
				}
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL =  graphPage + '?' + pageAction;
				strURL += '&filter=';
				strURL += '&graph_template_id=-1';
				strURL += '&rows=-1';
				strURL += '&host_id=-1';
				strURL += '&columns=2';
				strURL += '&clusterid=' + $('#clusterid').val();
				strURL += '&jobid=' + $('#jobid').val();
				strURL += '&indexid=' + $('#indexid').val();
				strURL += '&submit_time=' + $('#submit_time').val();
				strURL += '&header=false';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#move_left').click(function() {
					applyFilter(1);
				});
				$('#move_right').click(function() {
					applyFilter(2);
				});
				$('#go').click(function() {
					applyFilter();
				});
				$('#legend').click(function() {
					applyFilter();
				});
				$('#predefined_timespan, #predefined_timeshift, #predefined_graph_type').change(function() {
					applyFilter();
				});
			});
			</script>
		</td>
	</tr>
	<?php
}

function pendingReasonFilter($grid_pend_ignore_applied, $default_num_of_pendreason, $default_pend_level, $default_pendreason_sortby, $job_page = 'grid_bjobs.php') {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull;
	global $grid_job_pend_reasons_per_chart, $grid_job_pend_reasons_sortby;

	get_filter_request_var('clusterid');
	get_filter_request_var('jobid');
	get_filter_request_var('indexid');
	get_filter_request_var('submit_time');

	?>
	<tr id='filter'>
		<td>
			<form name='form_grid_view_jpendhist' id='form_grid_view_jpendhist' action='<?php print $job_page;?>'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Reasons', 'grid');?>
					</td>
					<td>
						<select id='top_x_reason' onChange='applyFilter()'>
							<?php
							foreach ($grid_job_pend_reasons_per_chart as $value => $text) {
								print "<option value='" . $value . "'"; if ($default_num_of_pendreason == $value) { print ' selected'; } print '>' . $text . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Pend Level', 'grid');?>
					</td>
					<td>
						<select id='level' onChange='applyFilter()'>
							<option value="0" <?php if (isset_request_var('level') && get_request_var('level') == "0") print " selected";?>>Uncategorized Reason(-p0)</option>
						<?php
						$lsf_version=db_fetch_cell_prepared("SELECT grid_pollers.lsf_version
							FROM grid_pollers
							JOIN grid_clusters
							ON grid_clusters.poller_id=grid_pollers.poller_id
							WHERE grid_clusters.clusterid=?", array(get_request_var('clusterid')));

						if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1010')) {
						?>
							<option value="1" <?php if (isset_request_var('level') && get_request_var('level') == "1") print " selected";?>>Single Key Reason (-p1)</option>
							<option value="2" <?php if (isset_request_var('level') && get_request_var('level') == "2") print " selected";?>>Candidated Host Reason(-p2)</option>
						<?php }?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>' onClick='clearFilter();'>
						</span>
				</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Sort by', 'grid');?>
					</td>
					<td>
						<select id='pending_sortby' onChange='applyFilter()'>
						<?php
							foreach ($grid_job_pend_reasons_sortby as $value => $text) {
								print "<option value='" . $value . "'"; if ($default_pendreason_sortby == $value) { print " selected"; } print ">" . $text . "</option>\n";
							}
						?>
						</select>
					</td>
					<td>
						<input type='checkbox' id='grid_pend_ignore_applied' onClick='applyFilter()' <?php if (($grid_pend_ignore_applied == 'true') || ($grid_pend_ignore_applied == 'on')) print ' checked="true"';?>/>
						<label for="grid_pend_ignore_applied">Apply Ignore Setting</label>
					</td>
				</tr>
			</table>
			<input type='hidden' id='clusterid' value='<?php print get_request_var('clusterid');?>'>
			<input type='hidden' id='jobid' value='<?php print get_request_var('jobid');?>'>
			<input type='hidden' id='indexid' value='<?php print get_request_var('indexid');?>'>
			<input type='hidden' id='submit_time' value='<?php print get_request_var('submit_time');?>'>
			<input type='hidden' id='action' value='viewjob'>
			<input type='hidden' id='tab' value='jobpendhist'>
			</form>
		</td>
	</tr>
	<?php
}

function pendingFusionFilter($job, $pendreason_tables, $pend_sql_query, $grid_pend_ignore_applied, $default_num_of_pendreason,$default_pend_level, $default_pendreason_sortby) {
	global $config, $grid_rows_selector, $grid_refresh_interval;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $authfull;

	print '<tr>';
	print "<td align='center' colspan='2'>";

	/* Generate Chart XML Beginning */
	/* Filter reason/subreason by condition*/
	if (cacti_sizeof($pendreason_tables) && cacti_sizeof($job)) {
		$pend_db_query_prefix = "(SELECT issusp, reason, ratio, subreason, start_time, end_time FROM ";

		$j = 0;
		foreach ($pendreason_tables as $pend_table) {

			$table_in_db = db_fetch_cell_prepared("SHOW TABLES LIKE ?", array($pend_table));

			/* this may happen if partitioning was turned on in 2.1.2 so there are no matching grid_jobs_pendreasons_finished_v000 */
			if ($table_in_db == "") {
				continue;
			}

			$pend_db_sql_where = " WHERE (clusterid='" . $job["clusterid"] . "'
				AND jobid='" . $job["jobid"] . "' AND indexid='" . $job["indexid"] . "'
				AND submit_time='" . $job["submit_time"] . "'";

			//ignore end_time = '0000-00-00 00:00:00' records from table grid_jobs_pendreasons_finished and its partitioned tables.
			if (substr_count($pend_table,'grid_jobs_pendreasons_finished')) {
				$ignore_pend_empty_endtime = db_fetch_cell("SELECT count(jobid) AS job_count FROM $pend_table $pend_db_sql_where AND end_time >'0000-00-00 00:00:00')");
				if ($ignore_pend_empty_endtime > 0) {
						$pend_db_sql_where .= " AND end_time >'0000-00-00 00:00:00'";
				}
			}

			switch ($default_pend_level) {
				case '0':
					$pend_db_sql_where .=" AND type IN (15,13,9,0)))";
					break;
				case '1':
					$pend_db_sql_where .= " AND type IN (15, 2)))";
					break;
				case '2':
					$pend_db_sql_where .= " AND type IN (15, 13)))";
					break;
				default:
					$pend_db_sql_where .= "))";
					break;
			};

			if ($j == 0) {
				$pend_sql_query = $pend_db_query_prefix . $pend_table . $pend_db_sql_where;
			} else {
				$pend_sql_query .= " UNION ALL " . $pend_db_query_prefix . $pend_table . $pend_db_sql_where;
			}
			$j++;
		}
	}
	$job_pend_proc_sqlselect = "SELECT rrec.issusp, rrec.reason, AVG(rrec.ratio) as avg_ratio, rrec.subreason, sum(timestampdiff(second, rrec.start_time, (case when rrec.end_time <= '0000-00-00 00:00:00' then NOW() else rrec.end_time end))) as duration,
				MIN(rrec.start_time) as minstarttime, MAX(case when rrec.end_time <= '0000-00-00 00:00:00' then NOW() else rrec.end_time end) as maxendtime, rmap.reason as reasontext ";

	$job_pend_proc_sqlfrom = " FROM (" . $pend_sql_query . ") as rrec JOIN grid_jobs_pendreason_maps rmap
				ON rrec.issusp = rmap.issusp AND rrec.reason=rmap.reason_code AND rrec.subreason=rmap.sub_reason_code ";

	//$job_pend_proc_sqlwhere = " WHERE clusterid='" . $job["clusterid"] . "' AND jobid='" . $job["jobid"] . "'
	//			AND indexid='" . $job["indexid"] . "' AND submit_time='" . $job["submit_time"] . "' ";

	if (($grid_pend_ignore_applied == 'true') || ($grid_pend_ignore_applied == 'on')) {
		$job_pend_proc_sqlwhere_ignore = create_pending_filter_sqlwhere_ignore();
	} else {
		$job_pend_proc_sqlwhere_ignore = "";
	}

	if (strlen($job_pend_proc_sqlwhere_ignore)) {
		$job_pend_proc_sqlwhere = " WHERE ";
	} else {
		$job_pend_proc_sqlwhere = "";
	}

	$job_pend_proc_sqlgroupby = " GROUP BY rrec.issusp, rrec.reason, rrec.subreason ";

	$job_pend_proc_sqllimit = " LIMIT " . $default_num_of_pendreason;

	switch($default_pendreason_sortby) {
		case '0':
			$job_pend_proc_orderby = " ORDER BY duration DESC ";
			break;
		case '1':
			$job_pend_proc_orderby = " ORDER BY reasontext ";
			break;
		case '2':
			$job_pend_proc_orderby = " ORDER BY minstarttime ";
			break;
		case '3':
			$job_pend_proc_orderby = " ORDER BY maxendtime DESC ";
			break;
	};

	//cacti_log("DEBUG: ". $job_pend_proc_sqlselect . $job_pend_proc_sqlfrom . $job_pend_proc_sqlwhere . $job_pend_proc_sqlwhere_ignore . $job_pend_proc_sqlgroupby . $job_pend_proc_orderby . $job_pend_proc_sqllimit);
	$left_time = $right_time = time();

	$job_pend_proc_reason_codes=db_fetch_assoc($job_pend_proc_sqlselect . $job_pend_proc_sqlfrom . $job_pend_proc_sqlwhere . $job_pend_proc_sqlwhere_ignore . $job_pend_proc_sqlgroupby . $job_pend_proc_orderby . $job_pend_proc_sqllimit);

	if (cacti_sizeof($job_pend_proc_reason_codes) > 0) {
		$left_time = strtotime($job_pend_proc_reason_codes[0]['minstarttime']);
		$right_time = strtotime($job_pend_proc_reason_codes[0]['maxendtime']);

		$job_pend_in_pend = array();
		$job_pend_in_susp = array();
		foreach ($job_pend_proc_reason_codes as $pend_reason_code) {
			$tmp_time = strtotime($pend_reason_code['maxendtime']);
			if ($right_time < $tmp_time)
				$right_time = $tmp_time;

			$tmp_time = strtotime($pend_reason_code['minstarttime']);
			if ($left_time > $tmp_time)
				$left_time = $tmp_time;
		}
	}

	/*Query Job Pending History by filterd reason/subreason*/
	//cacti_log("DEBUG: SELECT issusp, reason,subreason,start_time,end_time FROM ($pend_sql_query) as rrec $job_pend_sqlwhere_suffix ");
	if (cacti_sizeof($job_pend_proc_reason_codes) > 0) {
		$job_pend_sqlwhere_suffix = create_pending_sqlwhere_suffix($job_pend_proc_reason_codes);

		$job_pend_hists = db_fetch_assoc("SELECT issusp, reason,subreason,start_time,end_time FROM ($pend_sql_query) as rrec $job_pend_sqlwhere_suffix ");
	} else {
		$job_pend_hists = array();
	}
	/*Generate Chart general configruation*/
	$left_time_str=date("Y/m/d H:i:s", $left_time);

	$right_time_str = date("Y/m/d H:i:s", $right_time);

	$time_interval = getdate($right_time - $left_time - date("Z", 0));

	if ($time_interval['yday'] > 0) {
		$ti_unit = "d";
		$current_unit_key = "yday";
		$multi_seconds = 60*60*24;
		$catigory_label_fmt = "M j";
		$left_time_str = date("Y/m/d", $left_time) . " 00:00:00";
		$right_time_str = date("Y/m/d", $right_time) . " 23:59:59";
	} elseif ($time_interval['hours'] > 0) {
		$ti_unit = "h";
		$current_unit_key = "hours";
		$multi_seconds = 60*60;
		$catigory_label_fmt = "g a";
		$left_time_str = date("Y/m/d H", $left_time) . ":00:00";
		$right_time_str = date("Y/m/d H", $right_time) . ":59:59";
	} elseif ($time_interval['minutes'] > 0) {
		$ti_unit = "mn";
		$current_unit_key = "minutes";
		$multi_seconds = 60;
		$catigory_label_fmt = "g:i a";
		$left_time_str = date("Y/m/d H:i", $left_time) . ":00";
		$right_time_str = date("Y/m/d H:i", $right_time) . ":59";
	} else {
		$ti_unit = "s";
		$current_unit_key = "seconds";
		$multi_seconds = 1;
		$catigory_label_fmt = "g:i:s a";
		$left_time_str = date("Y/m/d H:i:s", $left_time-1);
		$right_time_str = date("Y/m/d H:i:s", $right_time+1);
	}

	$job_id_indexid = $job["jobid"];
	if (isset($job["indexid"]) && $job["indexid"] != 0)
	 $job_id_indexid .= "[" . $job["indexid"] . "]";

	/*Generate Chart Categories*/
	$chart_categories = "<categories vAlign='bottom' fontSize='11'>";

	$current_unit_step = ceil($time_interval[$current_unit_key]/15);
	if ($current_unit_step == 0) $current_unit_step = 1;
	$current_category_time = strtotime($left_time_str);
	$last_category_end_time = strtotime($right_time_str);

	//cacti_log("DEBUG: Begin to rander Chart Category element: $left_time_str vs $right_time_str; $current_category_time vs $last_category_end_time; $current_unit_step * $multi_seconds");
	$max_label_size = 0;
	while($current_category_time < $last_category_end_time) {
		$category_label = date($catigory_label_fmt, $current_category_time);
		/*get label text size to arrange ganttPanelDuration at later*/
		$label_size =strlen($category_label);
		/*adapte lable size for full width char "W", "M"*/
		if (stripos($category_label, "M") || stripos($category_label, "W")) {
			$label_size++;
		}
		$max_label_size = max(array($label_size, $max_label_size));
		$category_start = date("Y/m/d H:i:s", $current_category_time);
		$current_category_time += $current_unit_step*$multi_seconds;
		$category_end = date("Y/m/d H:i:s", $current_category_time-1);
		$chart_categories .= "<category start='" . $category_start . "' end='" . $category_end . "' label='" . $category_label . "'/>";
	}


	$chart_categories .= "</categories>";

	/*Generate Chart Processes/Text*/
	$chart_processes= "<processes headerText='Reason' headerFontSize='11' headerVAlign='bottom' headerAlign='left' isAnimated='1' align='left' isBold='1' bgAlpha='25'>";
	$chart_dataTables = "<dataTable showProcessName='1' nameAlign='left' vAlign='middle' align='center' headerVAlign='bottom' headerAlign='center'>";
	$chart_dataColumns = "<dataColumn headerText='Duration' isAnimated='1' width='120'>";
	if ($default_pend_level==2) {
		$chart_dataColumns_1 = "<dataColumn headerText='Ratio(avg)' isAnimated='1' width='120'>";
	}

	//$idprefixs = array("a", "b", "c", "d", "e", "f");
	//foreach ($idprefixs as $idprefix)
	$procid_array=array();
	if (cacti_sizeof($job_pend_proc_reason_codes) > 0) {
		reset($job_pend_proc_reason_codes);
		foreach ($job_pend_proc_reason_codes as $pend_reason_code) {
			$procid_key = "id_" . $pend_reason_code['issusp'] . "_" . $pend_reason_code['reason'] . "_" . $pend_reason_code['subreason'];
			$procid = strtoupper(md5($pend_reason_code['reasontext']));
			if (!in_array($procid, $procid_array)) {
				$chart_processes .= "<process id='" .$procid . "' label='" . html_escape($pend_reason_code['reasontext'] . (($pend_reason_code['subreason'] != -1) ? '(' . $pend_reason_code['subreason'] . ')' : '')) . "' />";
				$duration=date('z\d H\h i\m s\s ', $pend_reason_code['duration'] - date("Z", 0));
				$search=array("0d","00h","00m","00s");
				$replace=array("","","","");
				$new_duration=str_replace($search,$replace,$duration);
				if (empty($new_duration)) {
					$new_duration="0s";
				}
				$chart_dataColumns .= "<text label='" . $new_duration . "' />";
				if ($default_pend_level==2) {
					$chart_dataColumns_1 .= "<text label='" .add_dec($pend_reason_code['avg_ratio']*100,2) . "%'/>";
				}
			}
			$procid_array[$procid_key] =  $procid;
		}
	}
	$chart_dataColumns = $chart_dataColumns . "</dataColumn>";
	if ($default_pend_level==2) {
		$chart_dataColumns_1 = $chart_dataColumns_1 . "</dataColumn>";
		$chart_dataTables = $chart_dataTables . $chart_dataColumns . $chart_dataColumns_1. "</dataTable>";
	} else {
		$chart_dataTables = $chart_dataTables . $chart_dataColumns . "</dataTable>";
	}
	$chart_processes = $chart_processes . "</processes>";

	/*Generate Chart Tasks bar*/
	$chart_tasks = "<tasks>";
	$hasunfinished = false;
	$counter = 1;
	$proc_number;
	$proc_number_array = array();
	if (cacti_sizeof($job_pend_hists) > 0) {
		foreach ($job_pend_hists as $job_pend_hist) {
			$isunfinished = !check_time($job_pend_hist['end_time'], true);
			if (!$hasunfinished) {
				$hasunfinished = $isunfinished;
			}

			$procid_key = "id_" . $job_pend_hist['issusp'] . "_" . $job_pend_hist['reason'] . "_" . $job_pend_hist['subreason'];
			$procid = $procid_array[$procid_key];

			if (isset($proc_number_array[$procid])) {
				$proc_number = $proc_number_array[$procid];
			} else {
				$proc_number = $counter++;
				$proc_number_array[$procid] = $proc_number;
			}

			$job_pend_hist['start_time'] = date('Y/m/d H:i:s', strtotime($job_pend_hist['start_time']));
			$job_pend_hist['end_time']   = date('Y/m/d H:i:s', strtotime($job_pend_hist['end_time']));

			$chart_tasks .= "<task name='Reason #". $proc_number ."' processId='" .$procid . "' start='" . $job_pend_hist['start_time'] . "' end='" . ($isunfinished ? date("Y/m/d H:i:s", $right_time) : $job_pend_hist['end_time']) . "' />";
		}
	}
	//}
	$chart_tasks .= '</tasks>';

	/*Generate Trendline if there are some unfinished pending reason*/
	$chart_trends = '';
	if ($hasunfinished) {
		$chart_trends .= '<trendlines>';
		$chart_trends .= "<line start='" . date('Y/m/d H:i:s', $right_time) . "' displayValue='Now' thickness='2' color='770000'/>";
		$chart_trends .= '</trendlines>';
	}

	/*calculate ganttPaneDuration, otherwise some char will be wrap*/
	$gantt_duration = floor(56/$max_label_size)*$current_unit_step;

	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	$chart_head ="<chart dateFormat='yyyy/mm/dd' GanttWidthPercent='50' showFullDataTable='0' outputDateFormat='yyyy/mm/dd hh:mn:ss' caption='Pending Reason History for Job &lt;$job_id_indexid&gt;' subCaption='From $left_time_str to $right_time_str' $fusion_theme exportEnabled='1' >";

	$strXML = '';
	if (cacti_sizeof($job_pend_proc_reason_codes) > 0) {
		$strXML = $chart_head . $chart_categories . $chart_processes . $chart_dataTables . $chart_tasks . $chart_trends . "</chart>";
	}

	/*Generate Chart XML End*/
	?>
		<script type='text/javascript'>
			var divWidth;
			var chartobj = null;
			$(function() {
				drawChart();
				$(window).resize(function() {
					divWidth = $(window).width() - 30;
					if ($('#navigation').length) {
						divWidth -= $('#navigation').width();
					}

					if ($('#pcontainer').length && chartobj != null) {
						chartobj.resizeTo(divWidth);
					}
				});
			});

			function drawChart() {
				divWidth  = $(window).width() - 30;
				if ($('#navigation').length) {
					divWidth -= $('#navigation').width();
				}

				if (chartobj != null) {
					chartobj.dispose();
				}

				chartobj = new FusionCharts({
					type: 'gantt',
					renderAt: 'pcontainer',
					width: divWidth
				});
				chartobj.setXMLData("<?php print $strXML;?>");

				if ($('#pcontainer').length && chartobj != null) {
					chartobj.render('pcontainer');
				}
			}

		</script>
	<?php
	print '</td>';
	print '</tr>';
}

function grid_job_get_mem_reserve_from_resreq($job, &$mem_request, &$mem_reserve) {
	/* get requested and reserved memory */
	$res_req = parse_res_req_into_array($job['res_requirements']);
	if (isset($res_req["select"])) {
		$memory_requested = parse_res_req_select_string($res_req["select"], "mem");
		if (cacti_sizeof($memory_requested)) {
			if ($memory_requested["value"] != "N/A") {
				$mem_request  = $memory_requested["value"] * 1024;
			}
		}
	}

	if (isset($res_req["rusage"])) {
		$memory_reserved = parse_res_req_rusage_string($res_req["rusage"], "mem");
		if ($memory_reserved["value"] != "") {
			$mem_reserve = $memory_reserved["value"] * 1024;
		}
	}
}

/* grid_get_current_graph_start - determine the correct graph start time selected using
   the timespan selector
   @returns - the number of seconds relative to now where the graph should begin */
function grid_get_current_graph_start($timespan) {
	if (isset($timespan['begin_now'])) {
		return $timespan['begin_now'];
	} else {
		return '-' . DEFAULT_TIMESPAN;
	}
}

/* grid_get_current_graph_end - determine the correct graph end time selected using
   the timespan selector
   @returns - the number of seconds relative to now where the graph should end */
function grid_get_current_graph_end($timespan) {
	if (isset($timespan['end_now'])) {
		return $timespan['end_now'];
	} else {
		return '0';
	}
}

function grid_get_path_rtm_top() {
	$path_webroot = read_config_option('path_webroot');
	$pos = strrpos($path_webroot, '/');
	$path_rtm_top=substr($path_webroot, 0, $pos);
	return $path_rtm_top;
}

/*
return code
 1 - over usage
 2 - under usage
 0 - normal usage
*/
function check_job_memvio ($max_memory, $mem_reserved, $run_time) {
	$type =0; // type =1 over usage; 2- under usage; 0 - normal usage

	$mem_limit = read_config_option('gridmemvio_min_memory');
	$memvio_window = read_config_option('gridmemvio_window');
	$overage   = read_config_option('gridmemvio_overage');
	$underage  = read_config_option('gridmemvio_us_allocation');

	if (($max_memory > ($mem_reserved * (1+$overage))) && (($mem_reserved *(1+$overage)) >0) && ($run_time >$memvio_window)) {
		if ($mem_limit != -1) {
			if ($max_memory > $mem_limit) {
				$type = 1;
			}
		} else {
			$type =1;
		}
	}

	if (($max_memory < ($mem_reserved * (1-$underage))) && (($mem_reserved *(1-$underage)) >0) && ($run_time >$memvio_window)) {
		if ($mem_limit != -1) {
			if ($max_memory > $mem_limit) {
				$type = 2;
			}
		} else {
			$type =2;
		}
	}

	return $type;
}

function get_views_setting($page_name, $setting, $default) {
	$settings = read_grid_config_option($page_name, true);

	if ($settings != '') {
		$setarray = explode("|", $settings);
		if (cacti_sizeof($setarray)) {
		foreach ($setarray as $s) {
			$iset = explode("=", $s);
			$pname = trim($iset[0]);
			$pvalue = trim($iset[1]);
			if ($pname == $setting) {
				if ($pname == "vorder" && empty($pvalue)) {
					$pvalue = "div_view_summary,div_view_status,div_view_master,div_view_tput,div_view_perfmon";
				}
				if ($pname == "gorder" && empty($pvalue)) {
					$pvalue = "div_graph_limstat,div_graph_batchstat,div_graph_gridstat,div_graph_memavastat";
				}
				return $pvalue;
				break;
			}
		}
		}
	}

	return $default;
}

function one_column_display_type_clusterdb() {
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

	return false;
}

function get_cluster_array (&$clusters, &$cluster_cpu, &$job_details, &$host_slots, $clusterid = 0 ) {
	$clusters = array();
	$cluster_cpu = array();
	$job_details = array();
	$host_slots = array();
	$sql_params1 = array();
	$sql_params2 = array();

	if (($clusterid == 0) && (get_request_var('clusterid') >0)) {
		$clusterid = get_request_var('clusterid');
	}

	if ($clusterid > 0) {
		$sql_where1 = "WHERE grid_clusters.clusterid=?";
		$sql_params1[] = $clusterid;
		$sql_where2 = "WHERE grid_hosts.clusterid=? AND grid_hosts.maxJobs > 0";
		$sql_params2[] = $clusterid;
	} else {
		$sql_where1 = "";
		$sql_where2 = "WHERE grid_hosts.maxJobs > 0";
	}

	/* get cluster level details */
	$clusters = db_fetch_assoc_prepared("SELECT *
		FROM grid_clusters
		$sql_where1
		ORDER BY clustername", $sql_params1);

	/* get host slot details */
	$host_slots = array_rekey(db_fetch_assoc_prepared("SELECT
		grid_hosts.clusterid,
		SUM(grid_hosts.maxJobs) as totalSlots
		FROM grid_hosts
		$sql_where2
		GROUP BY clusterid", $sql_params2), "clusterid", "totalSlots");

	if (read_config_option("grid_cpu_leveling") == "on") {
		$cluster_cpus = db_fetch_assoc("SELECT
			grid_load.clusterid,
			SUM(cpuFactor*maxCpus) AS tcapacity,
			SUM(cpuFactor*maxCpus*ut) AS tload
			FROM grid_hostinfo
			LEFT JOIN grid_load
			ON (grid_hostinfo.host=grid_load.host)
			AND (grid_hostinfo.clusterid=grid_load.clusterid)
			WHERE isServer='1'
			AND grid_load.status NOT LIKE 'U%'
			GROUP BY grid_load.clusterid");
	} else {
		$cluster_cpus = db_fetch_assoc("SELECT
			grid_load.clusterid,
			SUM(maxCpus) AS tcapacity,
			SUM(maxCpus*ut) AS tload
			FROM grid_hostinfo
			LEFT JOIN grid_load
			ON (grid_hostinfo.host=grid_load.host)
			AND (grid_hostinfo.clusterid=grid_load.clusterid)
			WHERE isServer='1'
			AND grid_load.status NOT LIKE 'U%'
			GROUP BY grid_load.clusterid");
	}
	foreach ($cluster_cpus AS $cluster_cap) {
		$cluster_cpu[$cluster_cap["clusterid"]] = $cluster_cap["tload"] / $cluster_cap["tcapacity"];
	}

	/* get job details */
	if (read_config_option("grid_mc_resource_leasing") == "on") {
		$job_details = db_fetch_assoc_prepared("SELECT
			grid_clusters.clusterid,
			grid_clusters.clustername,
			grid_clusters.hourly_started_jobs AS startedJobs,
			grid_clusters.hourly_done_jobs AS doneJobs,
			grid_clusters.hourly_exit_jobs AS exitJobs,
			(SELECT SUM(num_cpus) FROM grid_jobs AS gj WHERE stat NOT IN ('EXIT', 'DONE') AND gj.clusterid=grid_clusters.clusterid) AS totalSlots,
			(SELECT COUNT(*) FROM grid_jobs AS gj WHERE stat NOT IN ('EXIT', 'DONE') AND gj.clusterid=grid_clusters.clusterid) AS totalJobs,
			(SELECT SUM(maxNumProcessors) FROM grid_jobs AS gj WHERE stat IN ('PEND') AND gj.clusterid=grid_clusters.clusterid) AS pendJobs,
			(SELECT SUM(num_cpus) FROM grid_jobs AS gj WHERE stat IN ('RUNNING','PROV') AND gj.clusterid=grid_clusters.clusterid) AS runJobs,
			(SELECT SUM(num_cpus) FROM grid_jobs AS gj WHERE stat LIKE '%SUSP%' AND gj.clusterid=grid_clusters.clusterid) AS suspJobs
			FROM grid_clusters
			$sql_where1
			GROUP BY grid_clusters.clusterid
			ORDER BY clustername", $sql_params1);
	} else {
		$job_details = db_fetch_assoc_prepared("SELECT
			grid_clusters.clusterid, grid_clusters.clustername,
			rs.totalSlots, rs.totalJobs, rs.runJobs, rs.suspJobs, rs1.pendJobs,
			grid_clusters.hourly_started_jobs AS startedJobs,
			grid_clusters.hourly_done_jobs AS doneJobs,
			grid_clusters.hourly_exit_jobs AS exitJobs
			FROM grid_clusters
			INNER JOIN (
				SELECT clusterid,
				SUM(maxJobs) AS totalSlots,
				SUM(numJobs) AS totalJobs,
				SUM(numRun) AS runJobs,
				SUM(numUSUSP+numSSUSP) AS suspJobs
				FROM grid_hosts
				WHERE status NOT LIKE 'U%'
				GROUP BY clusterid
			) AS rs
			ON grid_clusters.clusterid=rs.clusterid
			LEFT JOIN (
				SELECT clusterid, SUM(maxNumProcessors) AS pendJobs
				FROM grid_jobs
				WHERE stat='PEND'
				GROUP BY clusterid
			) AS rs1
			ON grid_clusters.clusterid=rs1.clusterid
			$sql_where1
			ORDER BY clustername", $sql_params1);
	}

}

function build_view_summary_display_array() {
	$display_text = array(
		array(
			'display' => __('Actions'),
			'align' => 'left'
		),
		array(
			'display' => __('Cluster'),
			'align' => 'left'
		),
		array(
			'display' => __('Cluster Status'),
			'align' => 'left'
		),
		array(
			'display' => __('Master Status'),
			'align' => 'left'
		),
		array(
			'display' => __('PAU'),
			'tip' => __('The type of the host currently controlling the cluster. Valid values are as follows: P-Primary host, A-Alternate host, U-Unknown.'),
			'align' => 'center'
		),
		array(
			'display' => __('Collect Status'),
			'tip' => __('The data collection status for the cluster.'),
			'align' => 'left'
		),
		array(
			'display' => __('CPU %'),
			'tip' => __('Overall utilization of CPU in the cluster.'),
			'align' => 'right'
		),
		array(
			'display' => __('Slot %'),
			'tip' => __('The rate of slot utilization in the cluster.'),
			'dbname'  => 'show_cluster_slot_percent',
			'align' => 'right'
		),
		array(
			'display' => __('Effic Status'),
			'tip' => __('Cluster efficiency status including OK, Recovering, Warn, Alarm, and N/A.'),
			'dbname'  => 'show_cluster_efic_status',
			'align' => 'left'
		),
		array(
			'display' => __('Effic %'),
			'tip' => __('Cluster CPU efficiency for running jobs. Efficiency is calculated with this formula: CPU_time / (run_time * #_of_CPUs).'),
			'dbname'  => 'show_cluster_efic_percent',
			'align' => 'right'
		),
		array(
			'display' => __('Last Start'),
			'dbname'  => 'show_badmin_reconfig_time',
			'align' => 'center'
		),
		array(
			'display' => __('Last Reconfig'),
			'tip' => __('Time when the last reconfiguration take effect.'),
			'dbname'  => 'show_badmin_reconfig_time',
			'align' => 'center'
		),
	);

	if (is_realm_allowed('8')) {  // have cosole access
		$display_text[] = array(
			'display' => __('Tholds / Alerts'),
			'tip' => __('Number of Thresholds and Alerts active in the cluster.'),
			'dbname'  => 'show_thold_alarm',
			'align' => 'center'
		);
	}

	return form_process_visible_display_text($display_text);
}

function show_view_summary($if_add_action='') {
	global $config;
	global $grid_cluster_control_actions;

	get_cluster_array ($clusters, $cluster_cpu, $job_details, $host_slots);

	print "<div id='div_view_summary'>";

	if ($if_add_action == 'add_action') {
		if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
			form_start('grid_clusterdb.php', 'chk');
		}
	}

	print '<tr>';

	html_start_box(__('Cluster Summary', 'grid'), '100%', '', '3', 'center', '');
	$display_text = build_view_summary_display_array();

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$disabled = false;
		html_header_checkbox($display_text);
	} else {
		$disabled = true;
		html_header($display_text);
	}

	if (cacti_sizeof($clusters)) {
		$i = 0;
		$j = 0;
		$limit_count = 0;
		foreach ($clusters as $cluster) {
			$collect_status = grid_get_cluster_collect_status($cluster);
			$cluster_status = grid_get_master_lsf_status($cluster['clusterid']);

			$found = false;
			$k = 0;
			foreach ($job_details as $job_detail) {
				if ($job_detail['clusterid'] == $cluster['clusterid']) {
					if ($cluster['disabled'] == "on") {
						$found = false;
					} else {
						$found = true;
					}

					$j = $k;
					break;
				}

				$k++;
			}

			form_alternate_row('line' . $cluster['clusterid'], true, $disabled);

			$url = '';

			if (is_realm_allowed('8')) {  // have cosole access
				$url .= '<a href="' . html_escape($config['url_path'] . 'plugins/grid/grid_clusters.php' .
					'?action=edit' .
					'&clusterid=' . $cluster['clusterid']) . '">
					<img src="' . $config['url_path'] . 'plugins/grid/images/view_clusters.gif" alt="" title="' . __esc('Edit Cluster', 'grid') . '"></a>';
			}

			$url .= '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/grid/grid_bqueues.php' .
				'?reset=1' .
				'&clusterid=' . $cluster['clusterid']) . '">
				<img src="' . $config['url_path'] . 'plugins/grid/images/view_queues.gif" alt="" title="' . __esc('View Queues', 'grid') . '"></a>';

			$url .= '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/grid/grid_busers.php' .
				'?reset=1' .
				'&clusterid=' . $cluster['clusterid']) . '">
				<img src="' . $config['url_path'] . 'plugins/grid/images/view_users.gif" alt="" title="' . __esc('View Users', 'grid') . '"></a>';

			$url .= '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php' .
				'?reset=1' .
				'&clusterid=' . $cluster['clusterid']) . '">
				<img src="' . $config['url_path'] . 'plugins/grid/images/view_hosts.gif" alt="" title="' . __esc('View Batch Hosts', 'grid') . '"></a>';

			$url .= '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/grid/grid_bhgroups.php' .
				'?reset=1' .
				'&clusterid=' . $cluster['clusterid']) . '">
				<img src="' . $config['url_path'] . 'plugins/grid/images/view_hgroups.gif" alt="" title="' . __esc('View Batch Host Groups', 'grid') . '"></a>';

			$url .= '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $cluster['clusterid'] .
				'&status=ACTIVE&page=1') . '">
				<img src="' . $config['url_path'] . 'plugins/grid/images/view_jobs.gif" alt="" title="' . __esc('View Active Jobs', 'grid') . '"></a>';

			if ($cluster['cacti_tree'] > 0) {
				$url .= '<a href="' . html_escape($config['url_path'] . 'graph_view.php' .
					'?action=tree' .
					'&node=tree_anchor-' . $cluster['cacti_tree']) . '&site_id=-1&host_id=-1&host_template_id=-1&hyper=true">
					<img src="' . $config['url_path'] . 'plugins/grid/images/view_graphs.gif" alt="" title="' . __esc('View Cluster Graphs', 'grid') . '"></a>';
			}

			form_selectable_cell($url, $cluster['clusterid'], '1%');

			$host_url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $cluster['clusterid'] .
				'&status=RUNNING&page=1';

			form_selectable_cell(filter_value($cluster['clustername'], '', $host_url), $cluster['clusterid']);

			if ($cluster['disabled'] == 'on') {
				$cluster_status[0] = "<span class='deviceDisabled'>" . __('Disabled', 'grid') . '</span>';
				$cluster_status[1] = "<span class='deviceDisabled'>" . __('Disabled', 'grid') . '</span>';
				$cluster_status[2] = "<span class='deviceDisabled'>" . __('Disabled', 'grid') . '</span>';
			} elseif ($collect_status == 'Down') {
				$cluster_status[0] = "<span class='deviceDown'>" . __('Unk/Unk', 'grid') . '</span>';
				$cluster_status[1] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
				$cluster_status[2] = "<span class='deviceDown'>" . __('Unk', 'grid') . '</span>';
			}

			form_selectable_cell($cluster_status[0] != '' ? $cluster_status[0] : __('Unk', 'grid'), $cluster['clusterid'], '', 'nowrap');

			form_selectable_cell($cluster_status[1] != '' ? $cluster_status[1] . ' / ' . $cluster_status[2] : ($cluster_status[2] == __('N/A', 'grid') ? $cluster_status[2] : __('Unk', 'grid')), $cluster['clusterid']);

			form_selectable_cell($cluster_status[3], $cluster['clusterid'], '', 'center');

			//collect status
			if ($cluster['disabled'] == 'on') {
				form_selectable_cell(__('Disabled', 'grid'), $cluster['clusterid'], '', 'deviceDisabled');
			} elseif ($collect_status == 'Up') {
				form_selectable_cell(__('Up', 'grid'), $cluster['clusterid'], '', 'deviceUp');
			} elseif ($collect_status == 'Jobs Down') {
				$found = false;
				form_selectable_cell(__('Jobs Down', 'grid'), $cluster['clusterid'], '', 'deviceDown');
			} elseif ($collect_status == 'Down') {
				form_selectable_cell(__('Down', 'grid'), $cluster['clusterid'], '', 'deviceDown');
			} elseif ($collect_status == 'Diminished') {
				form_selectable_cell(__('Diminished', 'grid'), $cluster['clusterid'], '', 'deviceUnknown');
			} elseif ($collect_status == 'Admin Down') {
				form_selectable_cell(__('Admin Down', 'grid'), $cluster['clusterid'], '', 'color:midnightblue');
			} elseif ($collect_status == 'Maintenance') {
				form_selectable_cell(__('Maintenance', 'grid'), $cluster['clusterid'], '', 'deviceRecovering');
			} else {
				form_selectable_cell(__('Unk', 'grid'), $cluster['clusterid'], '', 'deviceDown');
			}

			//cpu%
			form_selectable_cell(($found && isset($cluster_cpu[$cluster['clusterid']])) ? display_load(round($cluster_cpu[$cluster['clusterid']]*100, 1),1) . '%' : __('N/A', 'grid'), $cluster['clusterid'], '', 'right');

			//slot%
			if (isset($job_details[$j]['runJobs'])) {
				if (isset($host_slots[$cluster['clusterid']]) && $host_slots[$cluster['clusterid']] > 0) {
					$value = display_load(round($job_details[$j]['runJobs']/$host_slots[$cluster['clusterid']]*100, 1),1) . '%';
				} else {
					$value = __('N/A', 'grid');
				}
			} else {
				$value = '0.0 %';
			}

			form_selectable_cell_visible($found ? $value : __('N/A', 'grid'), 'show_cluster_slot_percent', $cluster['clusterid'], 'right');

			grid_show_efficiency_state($cluster['efficiency_state'], $collect_status, $cluster['clusterid']);

			form_selectable_cell_visible($found ? $job_details[$j]['runJobs'] ? display_load($cluster['efficiency'], 1) . '%' : __('N/A', 'grid') : __('N/A', 'grid'), 'show_cluster_efic_percent', $cluster['clusterid'], 'right');

			//badmin reconfig time
			$mbdstat = db_fetch_row_prepared('SELECT last_mbatchd_reconfig, last_mbatchd_start
				FROM grid_clusters_perfmon_status AS gcp, grid_clusters As gc
				WHERE gcp.clusterid = gc.clusterid
				AND gc.perfmon_run = "on"
				AND gcp.clusterid = ?',
				array($cluster['clusterid']));

			if (cacti_sizeof($mbdstat)) {
				if (empty($mbdstat['last_mbatchd_reconfig']) || $mbdstat['last_mbatchd_reconfig'] == '0000-00-00 00:00:00') {
					$last_mbatchd_reconfig = __('N/A', 'grid');
				} else {
					$cur_year      = date('Y');
					$reconfig_year = date('Y', strtotime($mbdstat['last_mbatchd_reconfig']));

					if ($reconfig_year == $cur_year) {
						$last_mbatchd_reconfig = date('m-d H:i', strtotime($mbdstat['last_mbatchd_reconfig']));
					} else {
						$last_mbatchd_reconfig = date('Y-m-d H:i', strtotime($mbdstat['last_mbatchd_reconfig']));
					}
				}

				if (empty($mbdstat['last_mbatchd_start']) || $mbdstat['last_mbatchd_start'] == '0000-00-00 00:00:00') {
					$last_mbatchd_start = __('N/A', 'grid');
				} else {
					$cur_year   = date('Y');
					$start_year = date('Y', strtotime($mbdstat['last_mbatchd_start']));

					if ($start_year == $cur_year) {
						$last_mbatchd_start = date('m-d H:i', strtotime($mbdstat['last_mbatchd_start']));
					} else {
						$last_mbatchd_start = date('Y-m-d H:i', strtotime($mbdstat['last_mbatchd_start']));
					}
				}

				form_selectable_cell_visible($last_mbatchd_start, 'show_badmin_reconfig_time', $cluster['clusterid'], 'center');
				form_selectable_cell_visible($last_mbatchd_reconfig, 'show_badmin_reconfig_time', $cluster['clusterid'], 'center');
			} else {
				form_selectable_cell_visible(__('N/A', 'grid'), 'show_badmin_reconfig_time', $cluster['clusterid'], 'center');
				form_selectable_cell_visible(__('N/A', 'grid'), 'show_badmin_reconfig_time', $cluster['clusterid'], 'center');
			}

			//active tholds and alarms
			if (read_grid_config_option('show_thold_alarm')) {
				$tholds = db_fetch_cell_prepared("SELECT count(h.id) AS tholds
					FROM host AS h
					INNER JOIN thold_data AS td
					ON h.id=td.host_id
					WHERE thold_enabled='on'
					AND clusterid=?", array($cluster['clusterid']));

				$alarms = db_fetch_cell_prepared("SELECT count(id) AS alerts
					FROM gridalarms_alarm
					WHERE alarm_enabled='on'
					AND clusterid=?", array($cluster['clusterid']));

				//get non-associated-cluster tholds and alers. add it to every cluster
				$tholds_no_associate_cluster = db_fetch_cell("SELECT count(h.id) AS tholds
					FROM host AS h
					INNER JOIN thold_data AS td
					ON h.id=td.host_id
					WHERE thold_enabled='on'
					AND clusterid <=0");

				$alarms_no_associate_cluster = db_fetch_cell("SELECT count(id) AS alerts
					FROM gridalarms_alarm
					WHERE alarm_enabled='on'
					AND clusterid<=0");

				$tholds = $tholds +	$tholds_no_associate_cluster;
				$alarms = $alarms +	$alarms_no_associate_cluster;

				if ($tholds > 0) {
					$tholds_url = html_escape($config['url_path'] . 'plugins/thold/thold.php');
					$turl = "<a class='nowrap pic linkEditMain' href='". $tholds_url ."'>" . $tholds . '</a> / ';
				} else {
					$turl = $tholds . ' / ';
				}

				if ($alarms > 0) {
					$alarms_url = html_escape($config['url_path'] . 'plugins/gridalarms/gridalarms_alarm.php' .
						'?tab=alarms' .
						'&state=0' .
						'&alarm_ds_type=-1' .
						'&alarm_type=-1' .
						'&rows=-1' .
						'&clusterid=' . ($alarms_no_associate_cluster ? 0 : $cluster['clusterid']));

					$turl .= "<a class='nowrap pic linkEditMain' href='" . $alarms_url . "'>" . $alarms . '</a>';
				} else {
					$turl .= $alarms;
				}

				if (is_realm_allowed('8')) {  // have cosole access
					form_selectable_cell($turl, $cluster['clusterid'], '', 'center');
				}
			}

			if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
				form_checkbox_cell($cluster['clustername'], $cluster['clusterid'], $disabled);
			}

			form_end_row();

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
				break;
			}
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Clusters Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if ($if_add_action == 'add_action') {
		if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
			draw_actions_dropdown($grid_cluster_control_actions);
			form_end();
		}
	}

	print '<br>';

	print '</div>';
}

function build_view_status_display_array () {
	$display_text = array();

	$display_text[] = array('display' => __('Cluster', 'grid'), 'align' => 'left');

	if (read_grid_config_option('show_cluster_servers')) {
		$display_text[] = array('display' => __('Total Hosts', 'grid'), 'align' => 'right');
	}

	$display_text[] = array('display' => __('Total CPUs', 'grid'), 'align' => 'right');

	if (read_grid_config_option('show_cluster_clients')) {
		$display_text[] = array('display' => __('Total Clients', 'grid'), 'align' => 'right');
	}

	$display_text[] = array(
		'display' => __('Max Slots', 'grid'),
		'tip' => __('The total number of slots available to run jobs in the cluster.', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array('display' => __('Pend Jobs', 'grid'), 'align' => 'right');
	$display_text[] = array('display' => __('Run Jobs', 'grid'),  'align' => 'right');
	$display_text[] = array(
		'display' => __('Susp Jobs', 'grid'),
		'tip' => __('The total number of suspended jobs in the cluster (including system suspended and user suspended jobs).', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array(
		'display' => __('Jobs', 'grid'),
		'tip' => __('Performance monitored jobs in the cluster.', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array(
		'display' => __('Users', 'grid'),
		'tip' => __('Total number of users in the cluster.', 'grid'),
		'align' => 'right'
	);

	return $display_text;
}

function get_graphs_from_hash($clusterid, $hash) {
	global $config;

	$cacti_host   = db_fetch_cell_prepared("SELECT cacti_host FROM grid_clusters WHERE clusterid=?", array($clusterid));
	$graph_select = '';

	if (isset($cacti_host)) {
		$local_graph_ids = db_fetch_assoc_prepared("SELECT gl.id
			FROM graph_local AS gl
			INNER JOIN graph_templates AS gt
			ON gt.id=gl.graph_template_id
			WHERE gl.host_id=?
			AND gt.hash=?", array($cacti_host, $hash));

		if (cacti_sizeof($local_graph_ids)) {
			$graph_select = $config['url_path'] . "graph_view.php?page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&graph_add=";

			foreach($local_graph_ids as $graph) {
				$graph_select .= $graph["id"] . "%2C";
			}

			$graph_select = "<a class='pic hyper' href='" . htmlspecialchars($graph_select) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' border='0' alt='' align='absmiddle'></a>\n";
		}
	}

	return $graph_select;
}

function get_perfmon_graph_link($clusterid, $metric) {
	$hashes = array(
		'mbatchdRequest' => 'c05903282cf4a29aaa9eef5259dc3f84',
		'jobInfo'        => '3ace1eb9b108acf85cab8fa264897076',
		'hostInfo'       => '917512ae743df5fef7a01ed039543613',
		'queueInfo'      => '10917ca0f707f91949e53642cfb32d6f',
		'submitRequest'  => '39f014fe2a2b9dd8cbaf09396f4606ce',
		'jobsSubmitted'  => '99ac5f8aeb0f9ea0f3089534090d5ba9',
		'jobsDispatched' => '2a0008a59b4d531d9a006671c79e6959',
		'jobsCompleted'  => '4b6af3347503c9bfe90e4c876986b185',
		'jobsForwarded'  => 'fe5282c92c9d1173bfbea743ff18fb80',
		'jobsAccepted'   => '7136b2ca4b552f808d5ec622a56b4d91',
		'schedInterval'  => '57742988364531d11a96732a2ec044a4',
		'matchCriteria'  => '1ca158b33644192348fd2faf999aec5d',
		'jobBuckets'     => '34fe506a41da00ab3eafb648a987554a',
		'fileDescriptor' => '68288407f001671ca9e2c6356bafba6a',
		'jobsReordered'  => 'd439a14bbc212ab1bd85731257da0921',
		'slotUT'         => 'f90a362c7d3d10ecb354e96dea7c6373',
		'memoryUT'       => '3d0c9fa791119c0e9ed8149dc3c3ffb7'
	);

	switch($metric) {
		case 'Processed requests: mbatchd':
			return get_graphs_from_hash($clusterid, $hashes['mbatchdRequest']);
			break;
		case 'Job information queries':
			return get_graphs_from_hash($clusterid, $hashes['jobInfo']);
			break;
		case 'Host information queries':
			return get_graphs_from_hash($clusterid, $hashes['hostInfo']);
			break;
		case 'Queue information queries':
			return get_graphs_from_hash($clusterid, $hashes['queueInfo']);
			break;
		case 'Job submission requests':
			return get_graphs_from_hash($clusterid, $hashes['submitRequest']);
			break;
		case 'Jobs submitted':
			return get_graphs_from_hash($clusterid, $hashes['jobsSubmitted']);
			break;
		case 'Jobs dispatched':
			return get_graphs_from_hash($clusterid, $hashes['jobsDispatched']);
			break;
		case 'Jobs completed':
			return get_graphs_from_hash($clusterid, $hashes['jobsCompleted']);
			break;
		case 'Jobs sent to remote cluster':
			return get_graphs_from_hash($clusterid, $hashes['jobsForwarded']);
			break;
		case 'Jobs accepted from remote cluster':
			return get_graphs_from_hash($clusterid, $hashes['jobsAccepted']);
			break;
		case 'Scheduling interval in second(s)':
			return get_graphs_from_hash($clusterid, $hashes['schedInterval']);
			break;
		case 'Matching host criteria':
			return get_graphs_from_hash($clusterid, $hashes['matchCriteria']);
			break;
		case 'Job buckets':
			return get_graphs_from_hash($clusterid, $hashes['jobBuckets']);
			break;
		case 'MBD file descriptor usage':
			return get_graphs_from_hash($clusterid, $hashes['fileDescriptor']);
			break;
		case 'Jobs reordered':
			return get_graphs_from_hash($clusterid, $hashes['jobsReordered']);
			break;
		case 'Slot utilization':
			return get_graphs_from_hash($clusterid, $hashes['slotUT']);
			break;
		case 'Memory utilization':
			return get_graphs_from_hash($clusterid, $hashes['memoryUT']);
			break;
	}
}

function show_view_status() {
	global $config;

	get_cluster_array ($clusters, $cluster_cpu, $job_details, $host_slots);

	print "<div id='div_view_status'>";

	print '<tr>';

	html_start_box(__('Cluster Status', 'grid'), '100%', '', '3', 'center', '');

	$display_text = build_view_status_display_array();

	$disabled = true;
	html_header($display_text);

	$totals['total_hosts'] = 0;
	$totals['total_cpus'] = 0;
	$totals['total_clients'] = 0;
	$totals['max_slots'] = 0;
	$totals['pend_jobs'] = 0;
	$totals['run_jobs'] = 0;
	$totals['susp_jobs'] = 0;
	$totals['perfmon_jobs'] = 0;
	$totals['perfmon_users'] = 0;

	if (cacti_sizeof($clusters)) {
		$i = 0;
		$j = 0;
		$limit_count = 0;

		foreach ($clusters as $cluster) {
			$collect_status = grid_get_cluster_collect_status($cluster);
			$cluster_status = grid_get_master_lsf_status($cluster['clusterid']);

			$found = false;
			$k = 0;

			foreach ($job_details as $job_detail) {
				if ($job_detail['clusterid'] == $cluster['clusterid']) {
					if ($cluster['disabled'] == 'on') {
						$found = false;
					} else {
						$found = true;
					}

					$j = $k;

					break;
				}

				$k++;
			}

			if ($collect_status == 'Jobs Down') {
				$found = false;
			}

			form_alternate_row();

			//cluster name
			form_selectable_cell($cluster['clustername'], $cluster['clusterid']);

			//total hosts
			if (read_grid_config_option('show_cluster_servers')) {
				form_selectable_cell(($found ? number_format_i18n($cluster['total_hosts']): __('N/A', 'grid')), $cluster['clusterid'], '', 'right');
			}

			//total cpus
			form_selectable_cell(($found ? number_format_i18n($cluster['total_cpus']) : __('N/A', 'grid')), $cluster['clusterid'], '', 'right');

			//total clients
			if (read_grid_config_option('show_cluster_clients')) {
				form_selectable_cell(($found ? number_format_i18n($cluster['total_clients']): __('N/A', 'grid')), $cluster['clusterid'], '', 'right');
			}

			//host slots
			if (isset($host_slots[$cluster['clusterid']])) {
				$value = number_format_i18n($host_slots[$cluster['clusterid']]);
				$max_slots = $host_slots[$cluster['clusterid']];
			} else {
				$value = __('N/A', 'grid');
				$max_slots = 0;
			}
			form_selectable_cell(($found ? $value : __('N/A', 'grid')), $cluster['clusterid'], '', 'right');

			// pend jobs
			form_selectable_cell(($found ? number_format_i18n($job_details[$j]['pendJobs']) : __('N/A', 'grid')), $cluster['clusterid'], '', 'right');

			// run jobs
			form_selectable_cell(($found ? number_format_i18n($job_details[$j]['runJobs']) : __('N/A', 'grid')), $cluster['clusterid'], '', 'right');

			// suspend jobs
			form_selectable_cell(($found ? number_format_i18n($job_details[$j]['suspJobs']) : __('N/A', 'grid')), $cluster['clusterid'], '', 'right');

			//perfmon jobs
			if ($cluster['perfmon_run'] == 'on') {
				$perfmon_jobs = db_fetch_cell_prepared("SELECT IF (SUM(num_jobs) IS NULL, 'N/A', SUM(num_jobs))
					FROM grid_clusters_perfmon_status
					WHERE clusterid = ?", array($cluster['clusterid']));

				if ($perfmon_jobs == 'N/A') {
					$perfmon_jobs = __('N/A', 'grid');
				}
			} else {
				$perfmon_jobs = __('N/A', 'grid');
			}

			form_selectable_cell($perfmon_jobs, $cluster['clusterid'], '', 'right');

			//perfmon users
			if ($cluster['perfmon_run'] == 'on') {
				$perfmon_users = db_fetch_cell_prepared("SELECT IF (SUM(num_users) IS NULL, 'N/A', SUM(num_users))
					FROM grid_clusters_perfmon_status
					WHERE clusterid = ?", array($cluster['clusterid']));

				if ($perfmon_jobs == 'N/A') {
					$perfmon_jobs = __('N/A', 'grid');
				}
			} else {
				$perfmon_users = __('N/A', 'grid');;
			}

			form_selectable_cell($perfmon_users, $cluster['clusterid'], '', 'right');

			$totals['total_hosts']   += ($found ? $cluster['total_hosts'] : 0);
			$totals['total_cpus']    += ($found ? $cluster['total_cpus'] : 0);
			$totals['total_clients'] += ($found ? $cluster['total_clients'] : 0);
			$totals['max_slots']     += $max_slots;
			$totals['pend_jobs']     += ($found ? $job_details[$j]['pendJobs'] : 0);
			$totals['run_jobs']      += ($found ? $job_details[$j]['runJobs'] : 0);
			$totals['susp_jobs']     += ($found ? $job_details[$j]['suspJobs'] : 0);
			$totals['perfmon_jobs']  += (is_numeric($perfmon_jobs)? $perfmon_jobs : 0);
			$totals['perfmon_users'] += (is_numeric($perfmon_users)? $perfmon_users : 0);

			$limit_count++;
			$i++;

			form_end_row();

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) break;
		}

		print '<tr>';
		print '<td>' . __('Totals', 'grid') . '</td>';

		if (read_grid_config_option('show_cluster_servers')) {
			print "<td class='right'>" . number_format_i18n($totals['total_hosts'])   . "</td>";
		}

		print "<td class='right'>" . number_format_i18n($totals["total_cpus"])    . "</td>";

		if (read_grid_config_option("show_cluster_clients")) {
			print "<td class='right'>" . number_format_i18n($totals["total_clients"]) . "</td>";
		}

		print "<td class='right'>" . number_format_i18n($totals["max_slots"])      . "</td>";
		print "<td class='right'>" . number_format_i18n($totals["pend_jobs"])      . "</td>";
		print "<td class='right'>" . number_format_i18n($totals["run_jobs"])       . "</td>";
		print "<td class='right'>" . number_format_i18n($totals["susp_jobs"])      . "</td>";
		print "<td class='right'>" . number_format_i18n($totals["perfmon_jobs"])   . "</td>";
		print "<td class='right'>" . number_format_i18n($totals["perfmon_users"])  . "</td>";
		print "</tr>";

	} else {
		print "<tr><td colspan='15'><em>" . __('No Cluster Found', 'grid') . "</em></td></tr>";
	}

	html_end_box();

	print "</div>";
}

function build_view_master_display_array() {
	$display_text = array(
		array(
			'display' => __('Actions', 'grid'),
			'align' => 'left'
		),
		array(
			'display' => __('Host Name', 'grid'),
			'align' => 'left'
		),
		array(
			'display' => __('Cluster', 'grid'),
			'align' => 'left'
		),
		array(
			'display' => __('Status', 'grid'),
			'align' => 'left'
		),
		array(
			'display' => __('Type', 'grid'),
			'tip' => __('The type of master hosts. Valid values are as follows:  Primary, Secondary, Other. An asterisk next to the type indicates it is the current master.', 'grid'),
			'align' => 'left'
		),
		array(
			'display' => __('RunQ 15sec', 'grid'),
			'tip' => __('The r15s load indices is the 15-second average CPU run queue length. This is the average number of processes ready to use the CPU during the given interval.', 'grid'),
			'dbname'  => 'show_cluster_master_host_r15sec',
			'align' => 'right'
		),
		array(
			'display' => __('RunQ 1min', 'grid'),
			'tip' => __('The r1m load indices is the 1-minute average CPU run queue length. This is the average number of processes ready to use the CPU during the given interval.', 'grid'),
			'dbname'  => 'show_cluster_master_host_r1m',
			'align' => 'right'
		),
		array(
			'display' => __('RunQ 15m', 'grid'),
			'tip' => __('The r1m load indices is the 15-minute average CPU run queue length. This is the average number of processes ready to use the CPU during the given interval.', 'grid'),
			'dbname'  => 'show_cluster_master_host_r15m',
			'align' => 'right'
		),
		array(
			'display' => __('CPU %%%', 'grid'),
			'dbname'  => 'show_cluster_master_host_ut',
			'align' => 'right'
		),
		array(
			'display' => __('Page Rate', 'grid'),
			'tip' => __('The pg index gives the virtual memory paging rate in pages per second. This index is closely tied to the amount of available RAM memory and the total size of the processes running on a host; if there is not enough RAM to satisfy all processes, the paging rate is high.', 'grid'),
			'dbname'  => 'show_cluster_master_host_pagerate',
			'align' => 'right'
		),
		array(
			'display' => __('I/O Rate', 'grid'),
			'tip' => __('The I/O index measures I/O throughput to disks attached directly to this host, in KB per second. It does not include I/O to disks that are mounted from other hosts.', 'grid'),
			'dbname'  => 'show_cluster_master_host_iorate',
			'align' => 'right'
		),
		array(
			'display' => __('Cur Logins', 'grid'),
			'tip' => __('This gives the number of users logged in. Each user is counted once, no matter how many times they have logged into the host.', 'grid'),
			'dbname'  => 'show_cluster_master_host_logins',
			'align' => 'right'
		),
		array(
			'display' => __('Idle Time', 'grid'),
			'tip' => __('The interactive idle time of the host, in minutes. Idle time is measured from the last input or output on a directly attached terminal or a network pseudo-terminal supporting a login session.', 'grid'),
			'dbname'  => 'show_cluster_master_host_idle_time',
			'align' => 'right'
		),
		array(
			'display' => __('Temp Avail', 'grid'),
			'tip' => __('The space available in MB or in units set in LSF_UNIT_FOR_LIMITS(with LSF_ENABLE_TMP_UNIT=Y) in lsf.conf on the file system that contains the temporary directory.', 'grid'),
			'dbname'  => 'show_cluster_master_host_temp',
			'align' => 'right'
		),
		array(
			'display' => __('Mem Avail', 'grid'),
			'tip' => __('An estimate of the real memory currently available to user processes, measured in MB or in units set in LSF_UNIT_FOR_LIMITS in lsf.conf.', 'grid'),
			'dbname'  => 'show_cluster_master_host_mem',
			'align' => 'right'
		)
	);

	return form_process_visible_display_text($display_text);
}

function show_view_master() {
	global $config;

	$sql_params = array();
	$sql_where = "";
	if (get_request_var('clusterid') > 0) {
		$sql_where .= "AND (gl.clusterid=?)";
		$sql_params[] = get_request_var('clusterid');
	}

	$sql_query = "SELECT gc.clusterid, gc.clustername, gc.lsf_masterhosts, gc.lsf_master,
		gl.host, gl.time_in_state, gl.status, gl.r15s, gl.r1m, gl.r15m, gl.ut,
		gl.pg, gl.io, gl.ls, gl.it, gl.tmp, gl.mem
		FROM grid_load AS gl, grid_clusters AS gc
		WHERE gl.clusterid = gc.clusterid
		$sql_where";

	$load_results = db_fetch_assoc_prepared($sql_query, $sql_params);

	print "<div id='div_view_master'>";

	print '<tr>';

	html_start_box(__('Master Status', 'grid'), '100%', '', '1', 'center', '');
	$display_text = build_view_master_display_array();
	html_header($display_text);

	$i = 0;
	if (cacti_sizeof($load_results) > 0) {
		$limit_count = 0;
		foreach ($load_results as $load) {
			//hosts in grid_clusters.lsf_masterhosts are master hosts and only master hosts are displayed here
			if (getMasterRank($load['host'], $load['lsf_masterhosts'], $host_type, $load['lsf_master']) <= 0) {
				 continue;
			}

			$host_id = db_fetch_cell_prepared('SELECT id
				FROM host
				WHERE clusterid = ?
				AND hostname = ?',
				array($load['clusterid'], $load['host']));

			if (!empty($host_id)) {
				$host_graphs = db_fetch_cell_prepared('SELECT count(*)
					FROM graph_local
					WHERE host_id = ?',
					array($host_id));

				$graph_url = '<a class="pic" href="' . html_escape($config['url_path'] . 'graph_view.php' .
					'?action=preview' .
					'&graph_template_id=-1' .
					'&rfilter=' .
					'&reset=true' .
					'&host_id=' . $host_id .
					'&ajax_host_query=' . $load['host']) . '">
					<img src="' . $config['url_path'] . 'plugins/grid/images/view_graphs.gif" alt="" title="' . __esc('View Host Graphs', 'grid') . '"></a>';
			} else {
				$host_graphs = 0;
				$graph_url = '';
			}

			form_alternate_row();

			$hook_output = api_plugin_hook_function('grid_load_action_insert', $load);
			if (is_array($hook_output)) {
				$hook_output = '';
			}

			form_selectable_cell($graph_url . $hook_output, $load['clusterid'], '1%');

			$host_url = $config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $load['clusterid'] .
				'&ajax_host_query=' .$load['host'] .
				'&exec_host=' . $load['host'] .
				'&status=RUNNING';

			form_selectable_cell(filter_value($load['host'], '', $host_url), $load['clusterid']);
			form_selectable_cell($load['clustername'], $load['clusterid']);
			form_selectable_cell($load['status'], $load['clusterid']);
			form_selectable_cell($host_type, $load['clusterid']);

			form_selectable_cell_visible(display_load($load['r15s']),  'show_cluster_master_host_r15sec', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_load($load['r1m']),   'show_cluster_master_host_r1m', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_load($load['r15m']),  'show_cluster_master_host_r15m', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_ut($load['ut']),      'show_cluster_master_host_ut', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_pg($load['pg']),      'show_cluster_master_host_pagerate', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_pg($load['io']),      'show_cluster_master_host_iorate', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_ls($load['ls']),      'show_cluster_master_host_logins', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_hours($load['it']),   'show_cluster_master_host_idle_time', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_memory($load['tmp']), 'show_cluster_master_host_temp', $load['clusterid'], 'right');
			form_selectable_cell_visible(display_memory($load['tmp']), 'show_cluster_master_host_mem', $load['clusterid'], 'right');
			form_end_row();

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
				break;
			}
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Masters Found', 'grid') . '</em></td></tr>';
	}

	html_end_box();

	print "</div>";
}

function build_view_tput_display_array() {
	$display_text = array();

	$display_text[] = array('display' => __('Cluster'), 'align' => 'left');

	$display_text[] = array(
		'display' => __('Hourly Started'),
		'tip' => __('The total number of jobs that are started during the last hour.', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array(
		'display' => __('Hourly Done'),
		'tip' => __('The total number of jobs that are completed during the last hour.', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array(
		'display' => __('Hourly Exit'),
		'tip' => __('The total number of jobs that are canceled during the last hour (unsuccessful completion).', 'grid'),
		'align' => 'right'
	);
	$display_text[] = array('display' => __('Hourly Throughput'), 'align' => 'right');

	$display_text[] = array('display' => __('24Hr Started'), 'align' => 'right');
	$display_text[] = array('display' => __('24Hr Done'), 'align' => 'right');
	$display_text[] = array('display' => __('24Hr Exit'), 'align' => 'right');
	$display_text[] = array('display' => __('24Hr Throughput'), 'align' => 'right');

	return $display_text;
}

function calculate_job_throughput(&$count_tput1hour=0, &$count_tput24hour=0) {
	$last_hour_query = "SELECT gc.clusterid, gc.clustername,
		IF(SUM(numDone) IS NULL, 0, SUM(numDone)) AS numDone_hour,
		IF(SUM(numExit) IS NULL, 0, SUM(numExit)) AS numExit_hour,
		IF(SUM(numStarted) IS NULL, 0, SUM(numStarted)) AS numStarted_hour,
		IF(SUM(tput) IS NULL, 0, SUM(tput)) AS tput_hour
		FROM (
			SELECT gj.clusterid,
			SUM(CASE WHEN stat='DONE' AND end_time>FROM_UNIXTIME(UNIX_TIMESTAMP()-3600) THEN 1 ELSE 0 END) AS numDone,
			SUM(CASE WHEN stat='EXIT' AND end_time>FROM_UNIXTIME(UNIX_TIMESTAMP()-3600) THEN 1 ELSE 0 END) AS numExit,
			SUM(CASE WHEN start_time > FROM_UNIXTIME(UNIX_TIMESTAMP()-3600) THEN 1 ELSE 0 END) AS numStarted,
			SUM(CASE WHEN stat IN ('DONE','EXIT') AND end_time > FROM_UNIXTIME(UNIX_TIMESTAMP()-3600) THEN 1 ELSE 0 END) AS tput
			FROM grid_jobs AS gj
			WHERE end_time >= FROM_UNIXTIME(UNIX_TIMESTAMP()-3600)
			OR start_time >= FROM_UNIXTIME(UNIX_TIMESTAMP()-3600)
			GROUP BY gj.clusterid
		) AS rs
		RIGHT JOIN grid_clusters AS gc
		ON rs.clusterid = gc.clusterid
		GROUP BY gc.clusterid ORDER BY gc.clustername";

	$last_24hours_query = "SELECT
		gc.clusterid, gc.clustername,
		IF(SUM(numDone) IS NULL, 0, SUM(numDone)) AS numDone_24hour,
		IF(SUM(numExit) IS NULL, 0, SUM(numExit)) AS numExit_24hour,
		IF(SUM(numStarted) IS NULL, 0, SUM(numStarted)) AS numStarted_24hour,
		IF(SUM(tput) IS NULL, 0, SUM(tput)) AS tput_24hour
		FROM (
			SELECT gj.clusterid,
			'0' AS numDone,
			'0' AS numExit,
			COUNT(jobid) AS numStarted,
			'0' As tput
			FROM grid_jobs AS gj
			WHERE start_time>FROM_UNIXTIME(UNIX_TIMESTAMP()-86400)
			AND stat NOT IN ('DONE','EXIT')
			GROUP BY gj.clusterid
			UNION
			SELECT gj.clusterid,
			SUM(CASE WHEN stat='DONE' THEN 1 ELSE 0 END) AS numDone,
			SUM(CASE WHEN stat='EXIT' THEN 1 ELSE 0 END) AS numExit,
			SUM(CASE WHEN start_time>FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) THEN 1 ELSE 0 END) AS numStarted,
			COUNT(jobid) AS tput
			FROM grid_jobs_finished AS gj
			WHERE end_time>FROM_UNIXTIME(UNIX_TIMESTAMP()-86400)
			GROUP BY gj.clusterid
		) AS rs
		RIGHT JOIN grid_clusters AS gc
		ON rs.clusterid = gc.clusterid
		GROUP BY gc.clusterid ORDER BY clustername";

	$last_hour_results = db_fetch_assoc($last_hour_query);
	$last_24hours_results = db_fetch_assoc($last_24hours_query);

	if (cacti_sizeof($last_hour_results)) {
		$count_tput1hour = cacti_sizeof($last_hour_results) *4;
		$sql_prefix = "INSERT INTO grid_clusters_reportdata (clusterid, reportid, name, value, present) VALUES ";
		$sql_suffix = "ON DUPLICATE KEY UPDATE value=VALUES(value), present=1";
		$report     = 'tput1hour';
		$sql        = '';
		$i          = 0;

		foreach ($last_hour_results as $row) {
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numDone',"    . $row['numDone_hour']    . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numExit',"    . $row['numExit_hour']    . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numStarted'," . $row['numStarted_hour'] . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','tput',"       . $row['tput_hour'] . ',1), ';
			$i++;
		}
		$sql = trim($sql, ', ');

		db_execute($sql_prefix . $sql . $sql_suffix);
	}

	if (cacti_sizeof($last_24hours_results)) {
		$count_tput24hour = cacti_sizeof($last_24hours_results) *4;
		$sql_prefix = "INSERT INTO grid_clusters_reportdata (clusterid, reportid, name, value, present) VALUES ";
		$sql_suffix = "ON DUPLICATE KEY UPDATE value=VALUES(value), present=1";
		$report     = 'tput24hour';
		$sql        = '';
		$i          = 0;

		foreach ($last_24hours_results as $row) {
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numDone',"    . $row['numDone_24hour']    . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numExit',"    . $row['numExit_24hour']    . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','numStarted'," . $row['numStarted_24hour'] . ',1), ';
			$sql .= '(' . $row['clusterid'] . ",'" . $report . "','tput'," . $row['tput_24hour'] . ',1), ';
			$i++;
		}
		$sql = trim($sql, ', ');

		db_execute($sql_prefix . $sql . $sql_suffix);
	}
}

function show_view_tput() {
	global $config;

	$sql_where = "";
	$sql_params = array();
	if (get_request_var('clusterid') > 0) {
		$sql_where = "AND rd.clusterid=?";
		$sql_params[] = get_request_var('clusterid');
	}

	$last_hour_query = "SELECT rd.clusterid, gc.clustername,
		SUM(CASE WHEN name='numDone' THEN value ELSE 0 END) AS numDone_hour,
		SUM(CASE WHEN name='numExit' THEN value ELSE 0 END) AS numExit_hour,
		SUM(CASE WHEN name='numStarted' THEN value ELSE 0 END) AS numStarted_hour,
		SUM(CASE WHEN name='tput' THEN value ELSE 0 END) AS tput_hour
		FROM grid_clusters_reportdata AS rd
		INNER JOIN grid_clusters AS gc
		ON gc.clusterid=rd.clusterid
		WHERE reportid='tput1hour'
		$sql_where
		GROUP BY rd.clusterid ORDER BY clustername";

	$last_24hours_query = "SELECT rd.clusterid, gc.clustername,
		SUM(CASE WHEN name='numDone' THEN value ELSE 0 END) AS numDone_24hour,
		SUM(CASE WHEN name='numExit' THEN value ELSE 0 END) AS numExit_24hour,
		SUM(CASE WHEN name='numStarted' THEN value ELSE 0 END) AS numStarted_24hour,
		SUM(CASE WHEN name='tput' THEN value ELSE 0 END) AS tput_24hour
		FROM grid_clusters_reportdata AS rd
		INNER JOIN grid_clusters AS gc
		ON gc.clusterid=rd.clusterid
		WHERE reportid='tput24hour'
		$sql_where
		GROUP BY rd.clusterid ORDER BY clustername";

	$last_hour_results = db_fetch_assoc_prepared($last_hour_query, $sql_params);
	$last_24hours_results = db_fetch_assoc_prepared($last_24hours_query, $sql_params);

	$cluster_results = array();

	if (cacti_sizeof($last_hour_results)) {
	foreach ($last_hour_results as $last_hour_result) {
		$clusterid = $last_hour_result['clusterid'];

		$cluster_results[$clusterid]['numDone_hour']    = $last_hour_result['numDone_hour'];
		$cluster_results[$clusterid]['numExit_hour']    = $last_hour_result['numExit_hour'];
		$cluster_results[$clusterid]['numStarted_hour'] = $last_hour_result['numStarted_hour'];
		$cluster_results[$clusterid]['tput_hour']       = $last_hour_result['tput_hour'];

		if (empty ($cluster_results[$clusterid]['numDone_24hour']))    $cluster_results[$clusterid]['numDone_24hour'] = 0;
		if (empty ($cluster_results[$clusterid]['numExit_24hour']))    $cluster_results[$clusterid]['numExit_24hour'] = 0;
		if (empty ($cluster_results[$clusterid]['numStarted_24hour'])) $cluster_results[$clusterid]['numStarted_24hour'] = 0;
		if (empty ($cluster_results[$clusterid]['tput_24hour']))       $cluster_results[$clusterid]['tput_24hour'] = 0;
	}
	}

	if (cacti_sizeof($last_24hours_results)) {
	foreach ($last_24hours_results as $last_24hours_result) {
		$clusterid = $last_24hours_result['clusterid'];

		$cluster_results[$clusterid]['numDone_24hour']    = $last_24hours_result['numDone_24hour'];
		$cluster_results[$clusterid]['numExit_24hour']    = $last_24hours_result['numExit_24hour'];
		$cluster_results[$clusterid]['numStarted_24hour'] = $last_24hours_result['numStarted_24hour'];
		$cluster_results[$clusterid]['tput_24hour']       = $last_24hours_result['tput_24hour'];

		if (empty ($cluster_results[$clusterid]['numDone_hour']))    $cluster_results[$clusterid]['numDone_hour'] = 0;
		if (empty ($cluster_results[$clusterid]['numExit_hour']))    $cluster_results[$clusterid]['numExit_hour'] = 0;
		if (empty ($cluster_results[$clusterid]['numStarted_hour'])) $cluster_results[$clusterid]['numStarted_hour'] = 0;
		if (empty ($cluster_results[$clusterid]['tput_hour']))       $cluster_results[$clusterid]['tput_hour'] = 0;
	}
	}

	print "<div id='div_view_tput'>";

	print "<tr>";

	html_start_box(__('Job Throughput Status', 'grid'), '100%', '', '1', 'center', '');
	$display_text = build_view_tput_display_array();

	$disabled = true;
	html_header($display_text);

	$totals['numStarted_hour']   = 0;
	$totals['numDone_hour']      = 0;
	$totals['numExit_hour']      = 0;
	$totals['tput_hour']         = 0;
	$totals['numStarted_24hour'] = 0;
	$totals['numDone_24hour']    = 0;
	$totals['numExit_24hour']    = 0;
	$totals['tput_24hour']       = 0;

	if (cacti_sizeof($cluster_results) > 0) {
		$i = 0;
		$limit_count = 0;
		foreach ($cluster_results as $clusterid => $cluster) {
			form_alternate_row();

			//cluster name
			$cluster_name = clusterdb_get_clustername($clusterid);
			form_selectable_cell($cluster_name, $clusterid, '', 'nowrap');

			//Hourly Started
			form_selectable_cell(number_format_i18n($cluster['numStarted_hour']), $clusterid, '', 'right');

			//Hourly Done
			form_selectable_cell(number_format_i18n($cluster['numDone_hour']), $clusterid, '', 'right');

			//Hourly Exit
			form_selectable_cell(number_format_i18n($cluster['numExit_hour']), $clusterid, '', 'right');

			//Hourly TT
			form_selectable_cell(number_format_i18n($cluster['tput_hour']), $clusterid, '', 'right');

			//24Hr Started
			form_selectable_cell(number_format_i18n($cluster['numStarted_24hour']), $clusterid, '', 'right');

			//24Hr Done
			form_selectable_cell(number_format_i18n($cluster['numDone_24hour']), $clusterid, '', 'right');

			//24Hr Exit
			form_selectable_cell(number_format_i18n($cluster['numExit_24hour']), $clusterid, '', 'right');

			//24Hr TT
			form_selectable_cell(number_format_i18n($cluster['tput_24hour']), $clusterid, '', 'right');

			form_end_row();

			$totals['numStarted_hour']   += $cluster['numStarted_hour'];
			$totals['numDone_hour']      += $cluster['numDone_hour'];
			$totals['numExit_hour']      += $cluster['numExit_hour'];
			$totals['tput_hour']         += $cluster['tput_hour'];
			$totals['numStarted_24hour'] += $cluster['numStarted_24hour'];
			$totals['numDone_24hour']    += $cluster['numDone_24hour'];
			$totals['numExit_24hour']    += $cluster['numExit_24hour'];
			$totals['tput_24hour']       += $cluster['tput_24hour'];

			$limit_count++;
			$i++;

			form_end_row();

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) break;
		}

		print '<tr>';
		print '<td>' . __('Totals', 'grid') . '</td>';

		print "<td class='right'>" . number_format_i18n($totals['numStarted_hour'])   . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['numDone_hour'])      . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['numExit_hour'])      . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['tput_hour'])         . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['numStarted_24hour']) . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['numDone_24hour'])    . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['numExit_24hour'])    . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['tput_24hour'])       . '</td>';
		print '</tr>';

	} else {
		print "<tr><td colspan='15'><em>" . __('No Job Throughput Found', 'grid') . '</em></td></tr>';
	}

	html_end_box();

	print '</div>';
}

function build_view_perfmon_display_array($metric='non_usage') {
	if ($metric == 'usage') {
		$display_text = array(
			array(
				'display' => __('Actions', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Cluster', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Metric', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Used', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Free', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Total', 'grid'),
				'align' => 'right'
			)
		);
	} elseif ($metric == 'utilization') {
		$display_text = array(
			array(
				'display' => __('Actions', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Cluster', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Metric', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Current', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Total', 'grid'),
				'align' => 'right'
			)
		);
	} elseif ($metric == 'scheduler') {
		$display_text = array(
			array(
				'display' => __('Actions', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Cluster', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Metric', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Current', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Max', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Min', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Average', 'grid'),
				'align' => 'right'
			)
		);
	} else {
		$display_text = array(
			array(
				'display' => __('Actions', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Cluster', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Metric', 'grid'),
				'align' => 'left'
			),
			array(
				'display' => __('Current', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Max', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Min', 'grid'),
				'align' => 'right'
			),
			array(
				'display' => __('Total', 'grid'),
				'align' => 'right'
			)
		);
	}

	return $display_text;
}

function show_view_perfmon() {
	global $config;

	$have_utilization_flag = 1; //All clusters show utilization metrics
	$sql_where = '';
	$sql_params = array();
	if (get_request_var('clusterid') > 0) {
		$sql_where = 'AND gc.clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
		$lsf_version = db_fetch_cell_prepared('SELECT grid_pollers.lsf_version
			FROM grid_pollers
			INNER JOIN grid_clusters
			ON grid_clusters.poller_id=grid_pollers.poller_id
			WHERE grid_clusters.clusterid=?', array(get_filter_request_var('clusterid')));

		if (!empty($lsf_version)  && lsf_version_not_lower_than($lsf_version,'1017')) {
			$have_utilization_flag = 1;
		} else {
			$have_utilization_flag = 0;
		}
	}

	$sql_query = "SELECT m.clusterid, gc.clustername, m.metric, m.current, m.max, m.min, m.total
		FROM grid_clusters_perfmon_metrics AS m
		JOIN grid_clusters As gc ON m.clusterid = gc.clusterid
		JOIN grid_clusters_perfmon_metrics_type as mt ON mt.metric = m.metric
		WHERE mt.type = 1
		AND gc.perfmon_run = 'on'
		$sql_where;";

	$mbatchd_metrics = db_fetch_assoc_prepared($sql_query, $sql_params);

	$sql_query = "SELECT m.clusterid, gc.clustername, m.metric, m.current, m.max, m.min, m.avg
		FROM grid_clusters_perfmon_metrics AS m
		JOIN grid_clusters As gc ON m.clusterid = gc.clusterid
		JOIN grid_clusters_perfmon_metrics_type as mt ON mt.metric = m.metric
		WHERE mt.type = 2
		AND gc.perfmon_run = 'on'
		$sql_where;";

	$scheduler_metrics = db_fetch_assoc_prepared($sql_query, $sql_params);

	$sql_query = "SELECT m.clusterid, gc.clustername, m.metric, m.current as used, m.total-m.current as free, m.total
		FROM grid_clusters_perfmon_metrics AS m
		JOIN grid_clusters As gc ON m.clusterid = gc.clusterid
		JOIN grid_clusters_perfmon_metrics_type as mt ON mt.metric = m.metric
		WHERE mt.type = 3
		AND gc.perfmon_run = 'on'
		$sql_where;";

	$usage_metrics =  db_fetch_assoc_prepared($sql_query, $sql_params);

	$sql_query = "SELECT m.clusterid, gc.clustername, m.metric, m.current, m.total
		FROM grid_clusters_perfmon_metrics AS m
		JOIN grid_clusters As gc ON m.clusterid = gc.clusterid
		JOIN grid_clusters_perfmon_metrics_type as mt ON mt.metric = m.metric
		WHERE mt.type = 4
		AND gc.perfmon_run = 'on'
		$sql_where;";

	$utilization_metrics =  db_fetch_assoc_prepared($sql_query, $sql_params);

	print "<div id='div_view_perfmon'>";

	$disabled = true;

	html_start_box(__('Performance Monitoring (Perfmon) Status', 'grid'), '100%', '', '1', 'center', '');

	html_start_box(__('Batch Daemon Metrics', 'grid'), '100%', '', '1', 'center', '');

	$display_text = build_view_perfmon_display_array();

	html_header($display_text);

	$totals['cur']   = 0;
	$totals['max']   = 0;
	$totals['min']   = 0;
	$totals['total'] = 0;

	if (cacti_sizeof($mbatchd_metrics)) {
		$limit_count = 0;

		foreach ($mbatchd_metrics as $metric) {
			form_alternate_row();
			$link = get_perfmon_graph_link($metric['clusterid'], $metric['metric']);
			form_selectable_cell($link, $metric['clusterid'], "1%");
			form_selectable_cell($metric['clustername'], $metric['clusterid']);
			form_selectable_cell($metric['metric'], $metric['clusterid']);
			form_selectable_cell(number_format_i18n($metric['current']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['max']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['min']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['total']), $metric['clusterid'], '', 'right');
			form_end_row();

			$totals['cur']   += $metric['current'];
			$totals['max']   += $metric['max'];
			$totals['min']   += $metric['min'];
			$totals['total'] += $metric['total'];

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
				break;
			}
		}

		print '<tr>';
		print '<td colspan=3>' . __('Totals', 'grid') . '</td>';

		print "<td class='right'>" . number_format_i18n($totals['cur'])   . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['max'])   . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['min'])   . '</td>';
		print "<td class='right'>" . number_format_i18n($totals['total']) . '</td>';
		print '</tr>';

	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Batch Daemon Metrics Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	html_start_box(__('Scheduler Daemon Metrics', 'grid'), '100%', '', '1', 'center', '');
	$display_text = build_view_perfmon_display_array('scheduler');
	html_header($display_text);

	if (cacti_sizeof($scheduler_metrics)) {
		$limit_count = 0;

		foreach ($scheduler_metrics as $metric) {
			form_alternate_row();
			$link = get_perfmon_graph_link($metric['clusterid'], $metric['metric']);
			form_selectable_cell($link, $metric['clusterid'], "1%");
			form_selectable_cell($metric['clustername'], $metric['clusterid']);
			form_selectable_cell($metric['metric'], $metric['clusterid']);
			form_selectable_cell(number_format_i18n($metric['current']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['max']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['min']), $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['avg']), $metric['clusterid'], '', 'right');
			form_end_row();

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
				break;
			}
		}
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Scheduler Daemon Metrics Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	html_start_box(__('Usage Metrics', 'grid'), '100%', '', '1', 'center', '');
	$display_text = build_view_perfmon_display_array('usage');
	html_header($display_text);

	if (cacti_sizeof($usage_metrics)) {
		$limit_count = 0;

		foreach ($usage_metrics as $metric) {
			form_alternate_row();
			$link = get_perfmon_graph_link($metric['clusterid'], $metric['metric']);
			form_selectable_cell($link, $metric['clusterid'], "1%");
			form_selectable_cell($metric['clustername'], $metric['clusterid']);
			form_selectable_cell($metric['metric'], $metric['clusterid']);
			form_selectable_cell(number_format_i18n($metric['used']),  $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['free']),  $metric['clusterid'], '', 'right');
			form_selectable_cell(number_format_i18n($metric['total']), $metric['clusterid'], '', 'right');
			form_end_row();

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
				break;
			}
		}
	} else {
		print "<tr><td colspan='15'><em>" . __('No Usage Metrics Found', 'grid') . "</em></td></tr>";
	}
	html_end_box(false);

	if($have_utilization_flag) {
		html_start_box(__('Utilization Metrics', 'grid'), '100%', '', '1', 'center', '');
		$display_text = build_view_perfmon_display_array('utilization');
		html_header($display_text);

		if (cacti_sizeof($utilization_metrics)) {
			$limit_count = 0;

			foreach ($utilization_metrics as $metric) {
				form_alternate_row();
				$link = get_perfmon_graph_link($metric['clusterid'], $metric['metric']);
				form_selectable_cell($link, $metric['clusterid'], "1%");
				form_selectable_cell($metric['clustername'], $metric['clusterid']);
				form_selectable_cell($metric['metric'], $metric['clusterid']);
				form_selectable_cell(display_ut($metric['current']), $metric['clusterid'], '', 'right');
				form_selectable_cell(display_ut($metric['total']), $metric['clusterid'], '', 'right');
				form_end_row();

				$limit_count++;

				if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) {
					break;
				}
			}
		} else {
			print "<tr><td colspan='15'><em>" . __('No Utilization Metrics Found', 'grid') . "</em></td></tr>";
		}
		html_end_box(false);
	}

	html_end_box();

	print '</div>';
}

function build_view_benchmark_display_array() {
	$display_text = array(
		array(
			'display' => __('Cluster', 'grid'),
			'align'   => 'left'
		),
		array(
			'display' => __('Benchmark Name', 'grid'),
			'align'   => 'left'
		),
		array(
			'display' => __('Job ID', 'grid'),
			'align'   => 'left'
		),
		array(
			'display' => __('Status', 'grid'),
			'align'   => 'left'
		),
		array(
			'display' => __('Status Reason', 'grid'),
			'align'   => 'left'
		)
	);

	return $display_text;
}

function show_view_benchmark_exceptional_jobs() {
	global $config;

	include_once($config['base_path'] . '/plugins/benchmark/functions.php');

	$sql_where = '';
	$sql_params = array();
	if (get_request_var('clusterid') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' gc.clusterid = ?';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	$bm_check_period = read_config_option('benchmark_exceptional_job_check_period');

	if (!empty($bm_check_period)) {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' start_time >= FROM_UNIXTIME(?) ';
		$sql_params[] = time() - $bm_check_period;
	}

	$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' a.status !=1 ';

	$sql_query = "SELECT a.*, b.benchmark_name, gc.clustername
		FROM grid_clusters_benchmark_summary a
		INNER JOIN grid_clusters_benchmarks b
		ON (a.benchmark_id = b.benchmark_id)
		INNER JOIN grid_clusters gc
		ON (a.clusterid = gc.clusterid)
		$sql_where
		ORDER BY start_time desc";

	$results = db_fetch_assoc_prepared($sql_query, $sql_params);


	print "<div id='div_view_benchmark_exceptional_jobs'>";

	$disabled = true;

	html_start_box(__('Benchmark Jobs Exceptions', 'grid'), '100%', '', '1', 'center', '');

	$display_text = build_view_benchmark_display_array();
	html_header($display_text);

	if (!empty($results)) {
		$i = 0;
		$limit_count = 0;
		foreach ($results as $result) {
			form_alternate_row();

			//cluster name
			form_selectable_cell($result['clustername'], $result['clusterid'], '20%', 'nowrap', '');

			//benchmark name
			form_selectable_cell("<a class='hyper' href='" . html_escape($config['url_path'] . 'plugins/benchmark/grid_benchmark_summary.php?action=view&benchmark_id=' . $result['benchmark_id']) . "'>"  . $result['benchmark_name'] . '</a>', $result['clusterid'], '', 'nowrap', '');

			//benchmark jobid

			/* if the submit time is real, use it */
			if (isset($result['pjob_submit_time']) && $result['pjob_submit_time'] != '0000-00-00 00:00:00') {
				$submit_time = strtotime($result['pjob_submit_time']);
			} else {
				$submit_time = strtotime($result['start_time']);
			}
			$start_time  = round($submit_time - 86400,0);
			$end_time    = round($submit_time + 86400,0);

			form_selectable_cell((($result['pjob_jobid']>0)? "<a class='hyper' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewjob&cluster_tz&clusterid=' . $result['clusterid'] . '&jobid=' . $result['pjob_jobid'] . "&indexid=0&submit_time=$submit_time&start_time=$start_time&end_time=$end_time") . "'>"  . $result['pjob_jobid'] . '</a>': $result['pjob_jobid']), $result['clusterid'], '', 'nowrap', '');

			//job status
			form_selectable_cell(benchmark_get_status($result['status']), $result['clusterid'], '20%', '', '');

			//status reason
			$job_exitinfo = -1;
			if ($result['status'] == 5  ||
				$result['status'] == 14 ||
				$result['status'] == 15 ||
				$result['status'] == 16 ||
				$result['status'] == 17
				) {
				$job_exitinfo = $result['exitInfo'];  //for all exit jobs, use their actual exitinfo
			}

			$reason = getExceptionStatus($result['exceptMask'], $job_exitinfo);
			if (empty($reason))  {
				$reason = __('N/A', 'grid');
			}

			form_selectable_cell($reason, $result['clusterid'], '20%', '', '');

			$limit_count++;

			if (get_request_var('limit') != '-1' && $limit_count >= get_request_var('limit')) break;
		}
	} else {
		print "<tr><td colspan='15'><em>" . __('No Benchmark Jobs Exceptions Found', 'grid') . '</em></td></tr>';
	}

	html_end_box();

	print '</div>';
}

function clusterdb_get_host_status(&$host_status, $type_flag, &$host_names) {
	$host_status = array();
	$host_names  = array();

	$sql_where = '';
	$sql_params = array();
	if (get_request_var('clusterid') > 0) {
		$sql_where = 'WHERE clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	$host_status = db_fetch_row_prepared("SELECT
		SUM(CASE WHEN summary_status='1'   THEN 1 ELSE 0 END) AS GRID_UNAVAIL,
		SUM(CASE WHEN summary_status='2'   THEN 1 ELSE 0 END) AS GRID_BUSYCLOSE,
		SUM(CASE WHEN summary_status='3'   THEN 1 ELSE 0 END) AS GRID_IDLECLOSE,
		SUM(CASE WHEN summary_status='4'   THEN 1 ELSE 0 END) AS GRID_LOWRES,
		SUM(CASE WHEN summary_status='5'   THEN 1 ELSE 0 END) AS GRID_BUSY,
		SUM(CASE WHEN summary_status='6'   THEN 1 ELSE 0 END) AS GRID_IDLEWJOBS,
		SUM(CASE WHEN summary_status='7'   THEN 1 ELSE 0 END) AS GRID_IDLE,
		SUM(CASE WHEN summary_status='8'   THEN 1 ELSE 0 END) AS GRID_STARVED,
		SUM(CASE WHEN summary_status='9'   THEN 1 ELSE 0 END) AS GRID_ADMINDOWN,
		SUM(CASE WHEN summary_status='10'  THEN 1 ELSE 0 END) AS GRID_BLACKHOLE,
		SUM(CASE WHEN load_status='Ok'     THEN 1 ELSE 0 END) AS LOAD_OK,
		SUM(CASE WHEN load_status='Busy'   THEN 1 ELSE 0 END) AS LOAD_BUSY,
		SUM(CASE WHEN load_status LIKE 'Lock%' THEN 1 ELSE 0 END) AS LOAD_LOCKED,
		SUM(CASE WHEN load_status NOT IN ('Ok', 'Busy') AND load_status NOT LIKE 'Lock%' THEN 1 ELSE 0 END) AS LOAD_OTHER,
		SUM(CASE WHEN bhost_status='Ok'   THEN 1 ELSE 0 END) AS BHOST_OK,
		SUM(CASE WHEN bhost_status LIKE 'Closed%' THEN 1 ELSE 0 END) AS BHOST_CLOSED,
		SUM(CASE WHEN bhost_status NOT LIKE 'Closed%' AND bhost_status!='Ok' THEN 1 ELSE 0 END) AS BHOST_OTHER
		FROM grid_summary
		$sql_where", $sql_params);

	$grid_pie_hostname_count = read_grid_config_option('grid_pie_hostname_count');
	if (!empty($grid_pie_hostname_count) && $grid_pie_hostname_count>0) {
		$limit="ORDER BY ut DESC LIMIT $grid_pie_hostname_count";

		if ($type_flag == 'load') {
			if (!empty($host_status['LOAD_OK'])) {
				$sql_where_ok = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(load_status='Ok')";
				$host_names['Ok'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_ok $limit;", $sql_params));
				if ($host_status['LOAD_OK'] > $grid_pie_hostname_count) {
					$host_names['Ok'] .= '...';
				}
			}

			if (!empty($host_status['LOAD_BUSY'])) {
				$sql_where_busy = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(load_status='Busy')";
				$host_names['Busy'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_busy $limit;", $sql_params));
				if ($host_status['LOAD_BUSY'] > $grid_pie_hostname_count) {
					$host_names['Busy'] .= '...';
				}
			}

			if (!empty($host_status['LOAD_LOCKED'])) {
				$sql_where_lock = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(load_status LIKE 'Lock%')";
				$host_names['Lock'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_lock $limit;", $sql_params));
				if ($host_status['LOAD_LOCKED'] > $grid_pie_hostname_count) {
					$host_names['Lock'] .= '...';
				}
			}

			if (!empty($host_status['LOAD_OTHER'])) {
				$sql_where_other = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(load_status NOT IN ('Ok', 'Busy') AND load_status NOT LIKE 'Lock%')";
				$host_names['Other'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_other $limit;", $sql_params));
				if ($host_status['LOAD_OTHER'] > $grid_pie_hostname_count) {
					$host_names['Other'] .= '...';
				}
			}
		}

		if ($type_flag == 'host') {
			if (!empty($host_status['BHOST_OK'])) {
				$sql_where_ok = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(bhost_status='Ok')";
				$host_names['Ok'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_ok $limit;", $sql_params));
				if ($host_status['BHOST_OK'] > $grid_pie_hostname_count) {
					$host_names['Ok'] .= '...';
				}
			}

			if (!empty($host_status['BHOST_CLOSED'])) {
				$sql_where_close = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(bhost_status LIKE 'Closed%')";
				$host_names['Closed'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_close $limit;", $sql_params));
				if ($host_status['BHOST_CLOSED'] > $grid_pie_hostname_count) {
					$host_names['Closed'] .= '...';
				}
			}

			if (!empty($host_status['BHOST_OTHER'])) {
				$sql_where_other = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(bhost_status NOT LIKE 'Closed%' AND bhost_status !='Ok')";
				$host_names['Other'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_other $limit;", $sql_params));
				if ($host_status['BHOST_OTHER'] > $grid_pie_hostname_count) {
					$host_names['Other'] .= '...';
				}
			}
		}

		if ($type_flag == 'grid') {
			if (!empty($host_status['GRID_UNAVAIL'])) {
				$sql_where_1 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='1')";
				$host_names['GRID_UNAVAIL']   = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_1 $limit;", $sql_params));
				if ($host_status['GRID_UNAVAIL'] > $grid_pie_hostname_count) {
					$host_names['GRID_UNAVAIL'] .= '...';
				}
			}

			if (!empty($host_status['GRID_BUSYCLOSE'])) {
				$sql_where_2 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='2')";
				$host_names['GRID_BUSYCLOSE'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_2 $limit;", $sql_params));
				if ($host_status['GRID_BUSYCLOSE'] > $grid_pie_hostname_count) {
					$host_names['GRID_BUSYCLOSE'] .= '...';
				}
			}

			if (!empty($host_status['GRID_IDLECLOSE'])) {
				$sql_where_3 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='3')";
				$host_names['GRID_IDLECLOSE'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_3 $limit;", $sql_params));
				if ($host_status['GRID_IDLECLOSE'] > $grid_pie_hostname_count) {
					$host_names['GRID_IDLECLOSE'] .= '...';
				}
			}

			if (!empty($host_status['GRID_LOWRES'])) {
				$sql_where_4 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='4')";
				$host_names['GRID_LOWRES']    = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_4 $limit;", $sql_params));
				if ($host_status['GRID_LOWRES'] > $grid_pie_hostname_count) {
					$host_names['GRID_LOWRES'] .= '...';
				}
			}

			if (!empty($host_status['GRID_BUSY'])) {
				$sql_where_5 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='5')";
				$host_names['GRID_BUSY']      = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_5 $limit;", $sql_params));
				if ($host_status['GRID_BUSY'] > $grid_pie_hostname_count) {
					$host_names['GRID_BUSY'] .= '...';
				}
			}

			if (!empty($host_status['GRID_IDLEWJOBS'])) {
				$sql_where_6 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='6')";
				$host_names['GRID_IDLEWJOBS'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_6 $limit;", $sql_params));
				if ($host_status['GRID_IDLEWJOBS'] > $grid_pie_hostname_count) {
					$host_names['GRID_IDLEWJOBS'] .= '...';
				}
			}

			if (!empty($host_status['GRID_IDLE'])) {
				$sql_where_7 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='7')";
				$host_names['GRID_IDLE']      = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_7 $limit;", $sql_params));
				if ($host_status['GRID_IDLE'] > $grid_pie_hostname_count) {
					$host_names['GRID_IDLE'] .= '...';
				}
			}

			if (!empty($host_status['GRID_STARVED'])) {
				$sql_where_8 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='8')";
				$host_names['GRID_STARVED']   = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_8 $limit;", $sql_params));
				if ($host_status['GRID_STARVED'] > $grid_pie_hostname_count) {
					$host_names['GRID_STARVED'] .= '...';
				}
			}

			if (!empty($host_status['GRID_ADMINDOWN'])) {
				$sql_where_9 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='9')";
				$host_names['GRID_ADMINDOWN'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_9 $limit;", $sql_params));
				if ($host_status['GRID_ADMINDOWN'] > $grid_pie_hostname_count) {
					$host_names['GRID_ADMINDOWN'] .= '...';
				}
			}

			if (!empty($host_status['GRID_BLACKHOLE'])) {
				$sql_where_10 = $sql_where . (strlen($sql_where)  ? ' AND ':' WHERE ') . "(summary_status='10')";
				$host_names['GRID_BLACKHOLE'] = get_string_from_array(db_fetch_assoc_prepared("select host from grid_summary $sql_where_10 $limit;", $sql_params));
				if ($host_status['GRID_BLACKHOLE'] > $grid_pie_hostname_count) {
					$host_names['GRID_BLACKHOLE'] .= '...';
				}
			}
		}
	}
}
function get_string_from_array($records, $colnum_name = "host") {
	$str ='';

	if (is_array($records) && cacti_sizeof($records)) {
		foreach ($records AS $record) {
			$str .= $record[$colnum_name] . ' ';
		}
	}

	return $str;
}

function clusterdb_get_clustername($clusterid) {
	return db_fetch_cell_prepared('SELECT clustername FROM grid_clusters WHERE clusterid = ?', array($clusterid));
}

function show_graph_limstat() {
	$host_status = array();
	$host_names  = array();

	$grid_pie_hostname_count = read_grid_config_option("grid_pie_hostname_count");

	if (!empty($grid_pie_hostname_count) && $grid_pie_hostname_count>0) {
		$type_flag = 'load';
	} else {
		$type_flag = '';
	}

	clusterdb_get_host_status($host_status, $type_flag, $host_names);

	$lim_load_ok     = $host_status["LOAD_OK"];
	$lim_load_busy   = $host_status["LOAD_BUSY"];
	$lim_load_locked = $host_status["LOAD_LOCKED"];
	$lim_load_other  = $host_status["LOAD_OTHER"];

	$title = "LIM Status";

	if (get_request_var('clusterid') > 0) {
		$title .= " for Cluster &apos;" . clusterdb_get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " for All Clusters";
	}

	if (!empty($lim_load_ok) || !empty($lim_load_busy) || !empty($lim_load_locked) || !empty($lim_load_other)) {
		$tooltip_ok = "tooltext='Load OK $lim_load_ok " . (isset($host_names['Ok']) ? $host_names['Ok']:"") . "'";
		$tooltip_busy = "tooltext='Load Busy $lim_load_busy " . (isset($host_names['Busy']) ? $host_names['Busy']:"") . "'";
		$tooltip_lock = "tooltext='Load Locked $lim_load_locked " . (isset($host_names['Lock']) ? $host_names['Lock']:"") . "'";
		$tooltip_other = "tooltext='Load Other $lim_load_other " . (isset($host_names['Other']) ? $host_names['Other']:"") . "'";

		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='$title' palette='2' animation='1'
			YAxisName='load_stat' showValues='1' pieYScale='40' pieRadius='110' pieSliceDepth='15' startingAngle='120'
			numberPrefix='' formatNumberScale='0' showPercentInToolTip='0' showLabels='0' showLegend='1' $fusion_theme exportEnabled='1'> ";

		$chartXML .= "<set label='Load OK' value='$lim_load_ok' $tooltip_ok isSliced='0' />  ";
		$chartXML .= "<set label='Load Busy' value='$lim_load_busy' $tooltip_busy isSliced='0' /> ";
		$chartXML .= "<set label='Load Locked' value='$lim_load_locked' $tooltip_lock isSliced='0' /> ";
		$chartXML .= "<set label='Load Other' value='$lim_load_other' $tooltip_other isSliced='0' /> ";
		$chartXML .= "<styles>
					<definition>
						<style type='font' name='CaptionFont' color='666666' size='15' />
						<style type='font' name='SubCaptionFont' bold='0' />
					</definition>
					<application>
						<apply toObject='caption' styles='CaptionFont' />
						<apply toObject='SubCaption' styles='SubCaptionFont' />
					</application>
					</styles>
					</chart>";
		print ($chartXML);
	}
}

function show_graph_batchstat() {
	$host_status = array();
	$host_names  = array();

	$grid_pie_hostname_count = read_grid_config_option('grid_pie_hostname_count');

	if (!empty($grid_pie_hostname_count) && $grid_pie_hostname_count>0) {
		$type_flag = 'host';
	} else {
		$type_flag = '';
	}

	clusterdb_get_host_status($host_status, $type_flag, $host_names);

	$batch_ok     = $host_status['BHOST_OK'];
	$batch_closed = $host_status['BHOST_CLOSED'];
	$batch_other  = $host_status['BHOST_OTHER'];

	$title = 'Batch Status';
	if (get_request_var('clusterid') > 0) {
		$title .= " for Cluster &apos;" . clusterdb_get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " for All Clusters";
	}

	if (!empty($batch_ok) || !empty($batch_closed) || !empty($batch_other) ) {
		$tooltip_ok = "tooltext='Batch OK $batch_ok " . (isset($host_names['Ok']) ? $host_names['Ok']:"") . "'";
		$tooltip_closed = "tooltext='Batch Closed $batch_closed " . (isset($host_names['Closed']) ? $host_names['Closed']:"") . "'";
		$tooltip_other = "tooltext='Batch Other $batch_other " . (isset($host_names['Other']) ? $host_names['Other']:"") . "'";

		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='$title' palette='2' animation='1'
			YAxisName='load_stat' showValues='1' pieYScale='40' pieRadius='110' pieSliceDepth='15' startingAngle='120'
			numberPrefix='' formatNumberScale='0' showPercentInToolTip='0' showLabels='0' showLegend='1' $fusion_theme exportEnabled='1'> ";
		$chartXML .= "<set label='Batch OK' value='$batch_ok' $tooltip_ok isSliced='0' /> ";
		$chartXML .= "<set label='Batch Closed' value='$batch_closed' $tooltip_closed isSliced='0' /> ";
		$chartXML .= "<set label='Batch Other' value='$batch_other' $tooltip_other isSliced='0' /> ";
		$chartXML .= "<styles>
					<definition>
						<style type='font' name='CaptionFont' color='666666' size='15' />
						<style type='font' name='SubCaptionFont' bold='0' />
					</definition>
					<application>
						<apply toObject='caption' styles='CaptionFont' />
						<apply toObject='SubCaption' styles='SubCaptionFont' />
					</application>
					</styles>
					</chart>";
		print ($chartXML);
	}
}

function show_graph_gridstat() {
	$host_status = array();
	$host_names  = array();

	$grid_pie_hostname_count = read_grid_config_option('grid_pie_hostname_count');

	if (!empty($grid_pie_hostname_count) && $grid_pie_hostname_count>0) {
		$type_flag = 'grid';
	} else {
		$type_flag = '';
	}

	clusterdb_get_host_status($host_status, $type_flag, $host_names);

	$grid_unavail   = $host_status['GRID_UNAVAIL'];
	$grid_busyclose = $host_status['GRID_BUSYCLOSE'];
	$grid_idleclose = $host_status['GRID_IDLECLOSE'];
	$grid_lowres    = $host_status['GRID_LOWRES'];
	$grid_busy      = $host_status['GRID_BUSY'];
	$grid_idlewjobs = $host_status['GRID_IDLEWJOBS'];
	$grid_idle      = $host_status['GRID_IDLE'];
	$grid_starved   = $host_status['GRID_STARVED'];
	$grid_admindown = $host_status['GRID_ADMINDOWN'];

	$title = 'Grid Status';
	if (get_request_var('clusterid') > 0) {
		$title .= " for Cluster &apos;" . clusterdb_get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " for All Clusters";
	}

	if (!empty($grid_unavail) || !empty($grid_busyclose) || !empty($grid_idleclose) || !empty($grid_lowres) || !empty($grid_busy) ||
		!empty($grid_idlewjobs) || !empty($grid_idle) || !empty($grid_starved) || !empty($grid_admindown)
		) {
		$tooltip_unavail = "tooltext='Down&#47;Diminished Hosts $grid_unavail " . (isset($host_names['GRID_UNAVAIL']) ? $host_names['GRID_UNAVAIL']:"") . "'";
		$tooltip_busyclose = "tooltext='Busy&#47;Closed Hosts $grid_busyclose " . (isset($host_names['GRID_BUSYCLOSE']) ? $host_names['GRID_BUSYCLOSE']:"") . "'";
		$tooltip_idleclose = "tooltext='Idle&#47;Closed Hosts $grid_idleclose " . (isset($host_names['GRID_IDLECLOSE']) ? $host_names['GRID_IDLECLOSE']:"") . "'";
		$tooltip_lowres = "tooltext='Low Resources Hosts $grid_lowres " . (isset($host_names['GRID_LOWRES']) ? $host_names['GRID_LOWRES']:"") . "'";
		$tooltip_busy = "tooltext='Busy Hosts $grid_busy " . (isset($host_names['GRID_BUSY']) ? $host_names['GRID_BUSY']:"") . "'";
		$tooltip_idlewjobs = "tooltext='Idle w&#47;Jobs Hosts $grid_idlewjobs " . (isset($host_names['GRID_IDLEWJOBS']) ? $host_names['GRID_IDLEWJOBS']:"") . "'";
		$tooltip_idle = "tooltext='Idle Hosts $grid_idle " . (isset($host_names['GRID_IDLE']) ? $host_names['GRID_IDLE']:"") . "'";
		$tooltip_starved = "tooltext='Starved Hosts $grid_starved " . (isset($host_names['GRID_STARVED']) ? $host_names['GRID_STARVED']:"") . "'";
		$tooltip_admindown = "tooltext='Admin Down Hosts $grid_admindown " . (isset($host_names['GRID_ADMINDOWN']) ? $host_names['GRID_ADMINDOWN']:"") . "'";

		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='$title' palette='2' animation='1'
			YAxisName='load_stat' showValues='1' pieYScale='40' pieRadius='110' pieSliceDepth='15' startingAngle='120'
			numberPrefix='' formatNumberScale='0' showPercentInToolTip='0' showLabels='0' showLegend='1' $fusion_theme exportEnabled='1'> ";

		$chartXML .= "<set label='Down&#47;Diminished Hosts' value='$grid_unavail' $tooltip_unavail isSliced='0' /> ";
		$chartXML .= "<set label='Busy&#47;Closed Hosts' value='$grid_busyclose' $tooltip_busyclose isSliced='0' /> ";
		$chartXML .= "<set label='Idle&#47;Closed Hosts' value='$grid_idleclose' $tooltip_idleclose isSliced='0' /> ";
		$chartXML .= "<set label='Low Resources Hosts' value='$grid_lowres' $tooltip_lowres isSliced='0' /> ";
		$chartXML .= "<set label='Busy Hosts' value='$grid_busy' $tooltip_busy isSliced='0' /> ";
		$chartXML .= "<set label='Idle w&#47;Jobs Hosts' value='$grid_idlewjobs' $tooltip_idlewjobs isSliced='0' /> ";
		$chartXML .= "<set label='Idle Hosts' value='$grid_idle' $tooltip_idle isSliced='0' /> ";
		$chartXML .= "<set label='Starved Hosts' value='$grid_starved' $tooltip_starved isSliced='0' /> ";
		$chartXML .= "<set label='Admin Down Hosts' value='$grid_admindown' $tooltip_admindown isSliced='0' /> ";
		$chartXML .= "<styles>
					<definition>
						<style type='font' name='CaptionFont' color='666666' size='15' />
						<style type='font' name='SubCaptionFont' bold='0' />
					</definition>
					<application>
						<apply toObject='caption' styles='CaptionFont' />
						<apply toObject='SubCaption' styles='SubCaptionFont' />
					</application>
					</styles>
					</chart>";
		print ($chartXML);
	}
}

function show_graph_memavastat() {
	global $config;

	$sql_params = array();
	$sql_where = "WHERE reportid='fmemslots' ";
	if (get_request_var('clusterid') > 0) {
		$sql_where .= 'AND clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	$fmemslot_columns = array(
		'free1gSlots',
		'free2gSlots',
		'free4gSlots',
		'free8gSlots',
		'free16gSlots',
		'free32gSlots',
		'free64gSlots',
		'free128gSlots',
		'free256gSlots',
		'free512gSlots',
		'free1024gSlots'
	);

	foreach ($fmemslot_columns AS $c) {
		$hist[$c] = 0;
	}

	$records = db_fetch_assoc_prepared("SELECT * FROM grid_clusters_reportdata  $sql_where", $sql_params);
	if (cacti_sizeof($records)) {
		foreach ($records AS $record) {
			$hist[$record['name']] += $record['value'];
		}
	}

	$title = "Free Memory Slots Availability";
	if (get_request_var('clusterid') > 0) {
		$title .= " for Cluster &apos;" . clusterdb_get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " for All Clusters";
	}

	if (cacti_sizeof($hist)) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='$title' palette='2' animation='1' formatNumberScale='0' numberPrefix='' labeldisplay='ROTATE' showValues='1' slantLabels='1' seriesNameInToolTip='0' sNumberSuffix='' plotSpacePercent='0' labelDisplay='STAGGER' $fusion_theme exportEnabled='1'>";
		$chartXML .= "<set label='1G' value='"   . $hist['free1gSlots']    . "' />";
		$chartXML .= "<set label='2G' value='"   . $hist['free2gSlots']    . "' />";
		$chartXML .= "<set label='4G' value='"   . $hist['free4gSlots']    . "' />";
		$chartXML .= "<set label='8G' value='"   . $hist['free8gSlots']    . "' />";
		$chartXML .= "<set label='16G' value='"  . $hist['free16gSlots']   . "' />";
		$chartXML .= "<set label='32G' value='"  . $hist['free32gSlots']   . "' />";
		$chartXML .= "<set label='64G' value='"  . $hist['free64gSlots']   . "' />";
		$chartXML .= "<set label='128G' value='" . $hist['free128gSlots']  . "' />";
		$chartXML .= "<set label='256G' value='" . $hist['free256gSlots']  . "' />";
		$chartXML .= "<set label='512G' value='" . $hist['free512gSlots']  . "' />";
		$chartXML .= "<set label='1T' value='"   . $hist['free1024gSlots'] . "' />";
		$chartXML .= "<styles>
			<definition>
				<style type='font' name='CaptionFont' size='15' color='666666' />
				<style type='font' name='SubCaptionFont' bold='0' />
			</definition>
			<application>
				<apply toObject='caption' styles='CaptionFont' />
				<apply toObject='SubCaption' styles='SubCaptionFont' />
			</application>
   	 	</styles>
		</chart>";
	}

	if (strlen($chartXML) >0) {
		print ($chartXML);
	}
}

function simple_strip_host_domain ($host) {
	return current(explode('.', strtolower($host)));
}

/**
 *  function lsf_version_not_lower_than($cluster_lsf_version,$the_lsf_version)
 *  input value
 *        - cluster_lsf_version: the lsf_version want to check.
 *        - the_lsf_version: the lsf_version compare to.
 *  return value
 *        - true: if $cluster_lsf_version >= $the_lsf_version
 *        - false: if $cluster_lsf_version < $the_lsf_version
 *
 *  Because the lsf_version in DB is as below, and we think the future lsf_version will as 1000, 1012, 1134 , 2233 ...
 *
 *  +-------------+
 *  | lsf_version |
 *  +-------------+
 *  |          91 |
 *  |        1010 |
 *  |        1017 |
 *  |    10010013 |
 *  +-------------+
 *  If the first bit of $cluster_lsf_version < 6, the version is higher than 91, So extend it to 4 bits,
 *  others we extend it to 3 bits.  for example,
 *
 *  701  ==> 701
 *  8    ==> 800
 *  91   ==> 910
 *  1122 ==> 1122
 *  5123 ==> 5123
 *  604  ==> 604
 *  607  ==> 607
 */
function lsf_version_not_lower_than($cluster_lsf_version,$the_lsf_version) {
	$zero_string = "0000000";

	$cluster_len = strlen($cluster_lsf_version);
	$cluster_1 = substr($cluster_lsf_version,0,1);
	if ($cluster_1 < 6 ) { //higher than 91, extend to 4 bits
		if ($cluster_len < 4) {
			$need_add_string= substr($zero_string, 0, 4 - $cluster_len);
			$cluster_lsf_version .= $need_add_string;
		}
	} else { //extend to 3 bits.
		if ($cluster_len < 3) {
			$need_add_string= substr($zero_string, 0, 3 - $cluster_len);
			$cluster_lsf_version .= $need_add_string;
		}
	}

	$the_len = strlen($the_lsf_version);
	$the_1 = substr($the_lsf_version,0,1);

	if ($the_1 < 6 ) { //higher than 91, extend to 4 bits
		if ($the_len < 4) {
			$need_add_string= substr($zero_string, 0, 4 - $the_len);
			$the_lsf_version .= $need_add_string;
		}
	} else { //extend to 3 bits.
		if ($the_len < 3) {
			$need_add_string= substr($zero_string, 0, 3 - $the_len);
			$the_lsf_version .= $need_add_string;
		}
	}

	if ($cluster_lsf_version >= $the_lsf_version) {
		return true;
	} else {
		return false;
	}
}

/**
 *  function grid_version_compare($version1,$version2)
 *  input value
 *        - version1: the grid_version want to check.
 *        - version2: the grid_version compare to.
 *  return value
 *        - -1: if $version1 < $version2
 *        - 0: if $version1 = $version2
 *        - 1: if $version1 > $version2
 *  For example, all the calls as below return true.
 *  grid_version_compare("8.0", "8.0.1");
 *  grid_version_compare("9.1.3.0", "9.1.4.0");
 *  grid_version_compare("9.1.3.0", "10.1.0.0");
 */
function grid_version_compare($version1, $version2) {
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	return rtm_version_compare($version1, $version2);
}

/**
 * function getMasterRank
 * 	1. &$host_type - host type ('Primary', 'Secondary', 'other')
 * 	2. function return value
 * 		- 1:  host is in master list
 * 		- 0:  host is not in master list
 */
function getMasterRank($host, $lsf_masterhosts, &$host_type, $lsf_master) {
	$host_type  = '';
	$host       = simple_strip_host_domain($host);
	$lsf_master = simple_strip_host_domain($lsf_master);

	$master_hosts = explode(' ', $lsf_masterhosts);

	$i   = 0;
	$ret = 0;

	if (cacti_sizeof($master_hosts)) {
		foreach ($master_hosts as $master_host) {
			$master_host = simple_strip_host_domain($master_host);

			if ($master_host == $host) {
				if ($i == 0) {
					$host_type = __('Primary', 'grid');
				} elseif ($i == 1) {
					$host_type = __('Secondary', 'grid');
				} else {
					$host_type = __('Other', 'grid');
				}

				if ($host == $lsf_master ) {
					$host_type = $host_type . '*';
				}

				$ret = 1;
				break;
			}

			$i++;
		}
	}

	return ($ret);
}

/**
 * Get all User Group Levels based on User Group aggregation setting.
 * For example, if delimiter is "_", and there is a user group "AB_CD_EF_GH", and the significant level is 3,
 * then the User Group Filter dropdown needs to include: "AB, AB_CD, AB_CD_EF
 */
function get_aggregated_user_groups ($usergroups, $fieldname ='userGroup') {
	if (read_config_option('grid_ugroup_group_aggregation') != 'on') {
		return $usergroups;
	}

	$delim = read_config_option('grid_job_stats_ugroup_delimiter');

	if (!isset($delim)) {
		$delim = '_';
	}

	$max_level = read_config_option('grid_job_stats_ugroup_level_number');

	if ($max_level < 1) {
		$max_level = 1;
	}

	$new_usergroups = array();

	foreach ($usergroups as $usergroup) {
		$subs = explode($delim, $usergroup[$fieldname]);

		if (!$subs || empty($subs) || !cacti_sizeof($subs)) {
			$new_usergroups[] = $usergroup;
			continue;
		}

		$real_level = min(cacti_sizeof($subs), $max_level);

		$combine_subs = array();

		for ($i = 1; $i <= $real_level; $i++ ) {
			$combine_subs[] = $subs[$i-1];
			$new_usergroups[] = implode($delim, $combine_subs);
		}
	}

	$new_usergroups = array_unique($new_usergroups);

	asort($new_usergroups);

	$new_usergroups2= array();

	foreach ($new_usergroups as $new_usergroup) {
		$new_usergroups2[][$fieldname] = $new_usergroup;
	}

	return ($new_usergroups2);

}

function display_ineligible_pend_reason ($pend_state) {
	$ineligible_pend_reason = '-';

	if ($pend_state > 0) {
		$ineligible_pend_reason = 'Y';
	} elseif ($pend_state == 0) {
		$ineligible_pend_reason = 'N';
	} else {
		$ineligible_pend_reason = '-';
	}

	return $ineligible_pend_reason;
}

function cluster_check_ineligible_pend_reason ($clusterid = 0) {
	$ineligible_pend_flag = db_fetch_cell_prepared("SELECT parameter_value
		FROM grid_params
		WHERE clusterid=?
		AND parameter ='TRACK_ELIGIBLE_PENDINFO'", array($clusterid));

	if ($ineligible_pend_flag == 'Y') {
		return 1;
	} else {
		return -1;
	}
}

function grid_shell_exec($cmd, &$stdout=null, &$stderr=null) {
$cactides = array(
		1 => array('pipe', 'w'),
		2 => array('pipe', 'w')
	);

	$proc = proc_open($cmd, $cactides ,$pipes);

	$exitcode = 0;
	stream_set_blocking($pipes[1], 0);
	stream_set_blocking($pipes[2], 0);

	while(true) {
		$pstat = proc_get_status($proc);

		if ($pstat['running'] == false) {
			$exitcode = $pstat['exitcode'];
			break;
		}

		sleep(1);
	}

	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[2]);
	proc_close($proc);

	return $exitcode;
}

function alter_table_engine($tablename, $engine) {
	if (!check_table_engine($tablename, $engine)) {
		db_execute_prepared("ALTER TABLE `$tablename` ENGINE=?", array($engine));;
	}
}

function check_table_engine($tablename, $engine) {
	$ret = db_fetch_cell_prepared("SELECT 1 FROM INFORMATION_SCHEMA.TABLES
		WHERE TABLE_TYPE='BASE TABLE'
		AND TABLE_SCHEMA='cacti'
		AND TABLE_NAME=?
		AND ENGINE=?", array($tablename, $engine));

	if (!isset($ret) || empty($ret) || $ret != '1') {
		return false;
	} else {
		return true;
	}
}

function rmdirr($dirname) {
	// Sanity check
	if (!file_exists($dirname)) {
		return false;
	}

	// Simple delete for a file
	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}

	// Loop through the folder
	$dir = dir($dirname);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if ($entry == '.' || $entry == '..') {
			continue;
		}

		// Recurse
		rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
	}

	// Clean up
	$dir->close();

	return rmdir($dirname);
}

function display_job_results($jobs_page, $table_name, $job_results, $rows, $total_rows) {
	global $config;

	// Pull lookup tables into memory; improve page performance at the expense of per-request memory
	$host_lookup       = array_rekey(grid_view_get_host_records(), 'id', array('hostType', 'hostModel'));
	$queue_lookup      = array_rekey(grid_view_get_queue_records(), 'id', array('maxjobs', 'userJobLimit', 'availSlots', 'runjobs', 'numslots'));
	$user_lookup       = array_rekey(grid_view_get_user_records(), 'id', array('maxJobs', 'numJobs', 'numRUN', 'numPEND'));
	$user_queue_lookup = array_rekey(grid_view_get_user_queue_slot_records(), 'id', array('total_cpus'));

	$queue_nice_levels = array_rekey(
		db_fetch_assoc("SELECT
			CONCAT_WS('',clusterid,'-',queuename,'') AS cluster_queue, nice
			FROM grid_queues"),
		'cluster_queue', 'nice'
	);

	$display_text = build_job_display_array($jobs_page);

	if (basename($jobs_page) == 'heuristics_jobs.php') {
		$if_show_actions = check_user_status();
	} else {
		$if_show_actions = false;
	}

	$if_lsf_cluster_control = api_plugin_user_realm_auth('LSF_Cluster_Control');
	if (basename($jobs_page) == 'grid_bzen.php') {
		$if_lsf_cluster_control = false;
	}
	/* print checkbox form for validation */
	if ($if_lsf_cluster_control) {
		form_start($jobs_page, 'chk');
	}

	if ($if_lsf_cluster_control || $if_show_actions) {
		$colspan = cacti_sizeof($display_text)+1;
	} else {
		$colspan = cacti_sizeof($display_text)+1;
	}

	/* generate page list */
	if ((!preg_match('/(-1|STARTED)/', get_request_var('status'))
				&& (!preg_match('/(DONE|EXIT|FINISHED|ALL)/', get_request_var('status')) || ($table_name == 'grid_jobs')))
			|| (read_config_option('grid_global_opts_jobs_pageno') == 'on' && read_grid_config_option('default_grid_jobs_pageno') == 'on')) {
		$nav = html_nav_bar($jobs_page, MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $colspan, __('Jobs', 'grid'), 'page', 'main');
	} else {
		$nav = html_nav_bar($jobs_page, MAX_DISPLAY_PAGES, get_request_var('page'), $rows, cacti_sizeof($job_results), $colspan, __('Jobs', 'grid'), 'page', 'main', false);
	}

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	if ($if_lsf_cluster_control || $if_show_actions) {
		$disabled = false;
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	} else {
		$disabled = true;
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	}

	$new_length_jobname = read_grid_config_option('max_length_jobname', true);

	$i = 0;
	if (!empty($job_results)) {
		foreach ($job_results as $job) {
			$row_color = '';

			if (read_config_option('grid_depend_bgcolor') > 0) {
				if ($job['dependCond'] != '') {
					$row_color = get_color(read_config_option('grid_depend_bgcolor'));
				}

				if (($job['dependCond'] != '') && (substr_count($job['pendReasons'], 'invalid or never satisfied'))) {
					$row_color = get_color(read_config_option('grid_invalid_depend_bgcolor'));
				}
			}

			if (read_config_option('grid_flapping_enabled') && read_config_option('grid_flapping_bgcolor') > 0) {
				if (isset($job['stat_changes']) && $job['stat_changes'] >= read_config_option('grid_flapping_threshold')) {
					$row_color = get_color(read_config_option('grid_flapping_bgcolor'));
				}
			}

			if (read_config_option('grid_efficiency_enabled') && $job['run_time'] > read_config_option('grid_efficiency_window')
					&& (read_config_option('grid_efficiency_alarm_bgcolor') > 0 || read_config_option('grid_efficiency_warning_bgcolor') > 0)) {
				if ($job['efficiency']<read_config_option('grid_efficiency_alarm') && read_config_option('grid_efficiency_alarm_bgcolor') > 0 ) {
					$row_color = get_color(read_config_option('grid_efficiency_alarm_bgcolor'));
				} else if ($job['efficiency']<read_config_option('grid_efficiency_warning') && read_config_option('grid_efficiency_warning_bgcolor') > 0) {
					$row_color = get_color(read_config_option('grid_efficiency_warning_bgcolor'));
				}
			}

			if ($job['options'] & SUB_EXCLUSIVE && read_config_option('grid_exclusive_bgcolor') > 0) {
				$row_color = get_color(read_config_option('grid_exclusive_bgcolor'));
			}

			if ((($job['options'] & SUB_INTERACTIVE) || ($job['options2'] & SUB2_TSJOB)) && read_config_option('grid_interactive_bgcolor') > 0) {
				$row_color = get_color(read_config_option('grid_interactive_bgcolor'));
			}

			if (substr_count($job['pendReasons'], 'preempted by the License Scheduler') && read_config_option('grid_licsched_bgcolor') > 0) {
				$row_color = get_color(read_config_option('grid_licsched_bgcolor'));
			}

			if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING') {
				$idled = db_fetch_cell_prepared('SELECT jobid
					FROM grid_jobs_idled
					WHERE clusterid = ?
					AND jobid = ?
					AND indexid = ?
					AND submit_time = ?',
					array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

				if ($idled && read_config_option('grididle_bgcolor') > 0) {
					$row_color = get_color(read_config_option('grididle_bgcolor'));
				}
			}

			if ($job['stat'] == 'EXIT' && read_config_option('grid_exit_bgcolor') > 0) {
				$row_color = get_color(read_config_option('grid_exit_bgcolor'));
			}

			if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING' || get_request_var('status') == 'PROV' ||
				get_request_var('status') == 'DONE' || get_request_var('status') == 'EXIT' || get_request_var('status') == 'FINISHED') {
				$type = check_job_memvio($job['max_memory'], $job['mem_reserved'], $job['run_time']);

				if ($type) {
					if ($type == 1) {
						if (read_config_option('gridmemvio_bgcolor')) {
							$row_color = get_color(read_config_option('gridmemvio_bgcolor'));
						}
					} elseif (read_config_option('gridmemvio_us_bgcolor')) {
						$row_color = get_color(read_config_option('gridmemvio_us_bgcolor'));
					}
				}
			}

			if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING') {
				$runtime_jobs = db_fetch_cell_prepared('SELECT jobid
					FROM grid_jobs_runtime
					WHERE clusterid = ?
					AND jobid = ?
					AND indexid = ?
					AND submit_time = ?',
					array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

				if ($runtime_jobs && read_config_option('gridrunlimitvio_bgcolor') > 0) {
					$row_color = get_color(read_config_option('gridrunlimitvio_bgcolor'));
				}
			}


			if ($job['isLoaningGSLA'] == '1' && read_config_option('grid_slaloaning_bgcolor') > 0) {
				$row_color = get_color(read_config_option('grid_slaloaning_bgcolor'));
			}

			$jobname = api_plugin_hook_function('grid_jobs_jobname', grid_get_group_jobname($job));
			$jobid   = grid_get_jobid($job);
			$jobid2  = str_replace('[', '_', $jobid);

			api_plugin_hook('grid_jobs_row_color');

			$row_id = $jobid2 . ':' . $job['clusterid'];

			if ($row_color != '') {
				grid_form_alternate_row('line' . $row_id, $row_color, $disabled);
			} else {
				form_alternate_row('line' . $row_id, true, $disabled);
			}
			if($jobs_page == $config['url_path'] . 'plugins/grid/grid_bzen.php'){
				$tmp_href = $config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewjob&cluster_tz=' . get_request_var('cluster_tz') . '&clusterid=' . $job['clusterid'] . '&indexid=' . $job['indexid'] . '&jobid=' . $job['jobid'] . '&submit_time=' . strtotime($job['submit_time']) . '&start_time=' . strtotime($job['start_time']) . '&end_time=' . strtotime($job['end_time']);
			} else {
				$tmp_href = $jobs_page . '?action=viewjob&cluster_tz=' . get_request_var('cluster_tz') . '&clusterid=' . $job['clusterid'] . '&indexid=' . $job['indexid'] . '&jobid=' . $job['jobid'] . '&submit_time=' . strtotime($job['submit_time']) . '&start_time=' . strtotime($job['start_time']) . '&end_time=' . strtotime($job['end_time']);
			}
			form_selectable_cell(filter_value($jobid, get_request_var('filter'), $tmp_href), $row_id, '', 'nowrap');

			form_selectable_cell_visible($job['clusterid'], 'show_jobclusterid', $row_id);

			form_selectable_cell_visible(grid_get_clustername($job['clusterid']), 'show_jobclustername', $row_id);

			if (($new_length_jobname <= 0)||($new_length_jobname > 119)) {
				$new_length_jobname= 20;
			}

			form_selectable_cell_visible(filter_value(title_trim($jobname, $new_length_jobname), get_request_var('filter')), 'show_jobname', $row_id, '', $jobname);
			form_selectable_cell_metadata('simple', 'queue', $job['clusterid'], jc($job, 'queue'), 'show_queue');
			form_selectable_cell_visible(jc($job, 'sla'), 'show_sla', $row_id);
			form_selectable_cell_metadata('detailed', 'user', $job['clusterid'], $job['user'], 'show_user');
			form_selectable_cell_visible(jc($job, 'userGroup'), 'show_ugroup', $row_id);
			form_selectable_cell_visible(jc($job, 'chargedSAAP'), 'show_chargedsaap', $row_id);

			$proj_showname = api_plugin_hook_function('job_project_name_show', $job);
			if (is_array($proj_showname) && isset($proj_showname['projectName'])) {
				$job['projectName'] = $proj_showname['projectName'];
			}

			if ($job['projectName'] == 'default') {
				form_selectable_cell_visible('-', 'show_project', $row_id);
			} else {
				form_selectable_cell_metadata('simple', 'project', $job['clusterid'], jc($job, 'projectName'), 'show_project', '', '', true);
			}

			if (!preg_match('(NULL|-)', jc($job, 'licenseProject'))) {
				form_selectable_cell_metadata('simple', 'license-project', $job['clusterid'], jc($job, 'licenseProject'), 'show_lproject', '', '', true);
			} else {
				form_selectable_cell_visible('-', 'show_lproject', $row_id);
			}

			form_selectable_cell_metadata('simple', 'application', $job['clusterid'], jc($job, 'app'), 'show_app', '', '', true);
			form_selectable_cell_metadata('simple', 'job-group', $job['clusterid'], jc($job, 'jobGroup'), 'show_jgroup', '', '', true);

			form_selectable_cell($job['stat'], $row_id, '');

			if (read_grid_config_option('show_exec_host')) {
				if ($job['num_nodes'] > 1) {
					if (substr_count($job['exec_host']?:'', '*')) {
						$exechost = $job['exec_host'];
					} else {
						$exechost = $job['max_allocated_processes'] . '*' . $job['exec_host'];
					}
				} elseif ($job['maxNumProcessors'] > $job['num_cpus']) {
						$exechost = $job['max_allocated_processes'] . '*' . $job['exec_host'];
				} else {
					$exechost = $job['exec_host'];
				}

				$url = $config['url_path'] . 'plugins/grid/grid_bzen.php' .
					'?action=zoom&reset=1' .
					'&clusterid=' . $job['clusterid'] .
					'&exec_host=' . $job['exec_host'];

				form_selectable_cell_metadata('simple', 'host', $job['clusterid'], $exechost, 'show_exec_host', '', __esc('View Jobs and Graphs', 'grid'), false, $url);
			}

			/* Assume only a single instance of * to represent the number of slots used
			 * on the first node of a parallel job */
			$field_value = '-';
			if(isset($job['exec_host'])) {
				$lookup_exec_host = substr($job['exec_host'], strpos($job['exec_host'], '*'));

				$host_key = $job['clusterid'] . '_' . $lookup_exec_host;
				if (isset($host_lookup[$host_key]['hostType'])) {
					$field_value = $host_lookup[$host_key]['hostType'];
				}
			}

			form_selectable_cell_visible($field_value, 'show_exec_host_type', $row_id);

			$field_value = '-';
			if(isset($job['exec_host'])) {
				if (isset($host_lookup[$host_key]['hostModel'])) {
					$field_value = $host_lookup[$host_key]['hostModel'];
				}
			}

			form_selectable_cell_visible($field_value, 'show_exec_host_model', $row_id);

			form_selectable_cell_metadata('simple', 'host', $job['clusterid'], jc($job,'from_host'), 'show_from_host');

			if (basename($jobs_page) == 'heuristics_jobs.php') {
				form_selectable_cell(is_checkout($job), $row_id);
				form_selectable_cell(is_idled_job($job), $row_id, '', 'center');
				form_selectable_cell(is_long_job($job), $row_id, '', 'center');
				form_selectable_cell(is_pend_depend_job($job), $row_id, '', 'center');
				form_selectable_cell(is_memvio_job($job), $row_id, '', 'center');
			}

			form_selectable_cell_visible(grid_get_exit_code_details($job['exitStatus'], $job['exceptMask'], $job['exitInfo']), 'show_exit', $row_id, 'right', '');

			form_selectable_cell_visible(jc($job, 'stat_changes'), 'show_state_changes', $row_id, 'right');

			$job_user_priority = '-' ;
			if (isset($job['jobPriority']) && 
				isset($job['userPriority'])) {
				$job_user_priority = number_format_i18n($job['jobPriority']) . ' (' . number_format_i18n($job['userPriority']) . ')';
			}
			form_selectable_cell_visible($job_user_priority, 'show_job_user_priority', $row_id, 'right');
	
			if (isset($queue_nice_levels[$job['clusterid'] . '-' . $job['queue']])) {
				form_selectable_cell_visible($queue_nice_levels[$job['clusterid'].'-'.$job['queue']], 'show_nice', $row_id, 'right');
			} else {
				form_selectable_cell_visible(__('Unkn', 'grid'), 'show_nice', $row_id, 'right');
			}

			form_selectable_cell_visible(display_job_memory(jc($job,'mem_requested'),2), 'show_req_memory', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory(jc($job,'mem_reserved'),2), 'show_reserve_memory', $row_id, 'right');

			if ($job['stat'] != 'PEND') {
				form_selectable_cell_visible("-" . display_job_memory(jc($job,'wasted_memory'),2), 'show_wasted_memory', $row_id, 'right');
			} else {
				form_selectable_cell_visible('-', 'show_wasted_memory', $row_id, 'right');
			}

			form_selectable_cell_visible(display_job_memory(jc($job,'max_memory'),2), 'show_max_memory', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory(jc($job,'max_swap'),2), 'show_max_swap', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory(jc($job,'mem_used'),2), 'show_cur_memory', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory(jc($job,'swap_used'),2), 'show_cur_swap', $row_id, 'right');
			form_selectable_cell_visible(display_byte_by_unit(jc($job,'gpu_max_memory'),2, ''), 'show_gpu_max_memory', $row_id, 'right');
			form_selectable_cell_visible(display_byte_by_unit(jc($job,'gpu_mem_used'),2, ''), 'show_cur_gpu_memory', $row_id, 'right');

			form_selectable_cell(display_job_time(jc($job,'cpu_used'), 2), $row_id, '', 'right');
			form_selectable_cell(display_job_effic(jc($job,'efficiency'), $job['run_time'], 2), $row_id, '', 'right');

			form_selectable_cell_visible(jc($job,'num_nodes'), 'show_nodes_cpus', $row_id, 'right');
			form_selectable_cell_visible(jc($job,'num_cpus'), 'show_nodes_cpus', $row_id, 'right');
			form_selectable_cell_visible(jc($job,'maxNumProcessors'), 'show_max_processors', $row_id, 'right');
			form_selectable_cell_visible(jc($job,'num_gpus'), 'show_gpus', $row_id, 'right');
			form_selectable_cell_visible(grid_format_time(jc($job,'submit_time'), false), 'show_submit_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_time(jc($job,'start_time'), false), 'show_start_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_time(jc($job,'end_time'), false), 'show_end_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'pend_time'), true), 'show_pend_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'ineligiblePendingTime'), true), 'show_ineligiblependingtime', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'effectivePendingTimeLimit'), true), 'show_effectivependingtimelimit', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'effectiveEligiblePendingTimeLimit'), true), 'show_effectiveeligiblependingtimelimit', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'run_time'), true), 'show_run_time', $row_id, 'right');
			form_selectable_cell_visible((jc($job,'runtimeEstimation') == 0 ? __('N/A'):grid_format_seconds(jc($job,'runtimeEstimation'), true)), 'show_runtime_estimation', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job,'gpu_exec_time'), true), 'show_gpu_exec_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job, 'ssusp_time'), true), 'show_ssusp_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job, 'ususp_time'), true), 'show_ususp_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job, 'psusp_time'), true), 'show_psusp_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job, 'unkwn_time'), true), 'show_unkwn_time', $row_id, 'right');
			form_selectable_cell_visible(grid_format_seconds(jc($job, 'prov_time'), true),  'show_prov_time', $row_id, 'right');

			$queue_key      = $job['clusterid'] . '_' . $job['queue'];
			$user_key       = $job['clusterid'] . '_' . $job['user'];
			$user_queue_key = $job['clusterid'] . '_' . $job['user'] . '_' . $job['queue'];

			$field_value = (isset($queue_lookup[$queue_key]['runjobs']) && $queue_lookup[$queue_key]['runjobs'] > 0 ? $queue_lookup[$queue_key]['runjobs']:'-') . ' / ';
			$field_value .= (isset($queue_lookup[$queue_key]['maxjobs']) && $queue_lookup[$queue_key]['maxjobs'] > 0 ? $queue_lookup[$queue_key]['maxjobs']:'-');
			form_selectable_cell_visible($field_value, 'show_queue_limit', $row_id, 'right');

			$field_value = (isset($user_queue_lookup[$user_queue_key]['total_cpus']) && $user_queue_lookup[$user_queue_key]['total_cpus'] > 0 ? $user_queue_lookup[$user_queue_key]['total_cpus']:'-') . ' / ';
			$field_value .= (isset($queue_lookup[$queue_key]['userJobLimit']) && $queue_lookup[$queue_key]['userJobLimit'] > 0 ? $queue_lookup[$queue_key]['userJobLimit']:'-');
			form_selectable_cell_visible($field_value, 'show_user_queue_limit', $row_id, 'right');

			$field_value = (isset($user_lookup[$user_key]['numRUN']) && $user_lookup[$user_key]['numRUN'] > 0 ? $user_lookup[$user_key]['numRUN']:'-') . ' / ';
			if (!isset($user_lookup[$user_key]['maxJobs']) || $user_lookup[$user_key]['maxJobs'] == '0') {
				$field_value .= '-';
			} else {
				$field_value .= $user_lookup[$user_key]['maxJobs'];
			}
			form_selectable_cell_visible($field_value, 'show_user_cpu_limit', $row_id, 'right');

			$field_value = (isset( $user_lookup[$user_key]['numRUN']) && $user_lookup[$user_key]['numRUN'] > 0 ? $user_lookup[$user_key]['numRUN']:'-') . ' / ';
			$field_value .= (isset($user_lookup[$user_key]['numPEND']) && $user_lookup[$user_key]['numPEND'] > 0 ?  $user_lookup[$user_key]['numPEND']:'-');
			form_selectable_cell_visible($field_value, 'show_run_pend_jobs', $row_id, 'right');

			$field_value = (isset($queue_lookup[$queue_key]['availSlots']) && $queue_lookup[$queue_key]['availSlots'] > 0 ? $queue_lookup[$queue_key]['availSlots']:'-') . ' / ';
			$field_value .= (isset($queue_lookup[$queue_key]['availSlots']) && $queue_lookup[$queue_key]['availSlots'] > 0 ? $queue_lookup[$queue_key]['numslots']:'-');
			form_selectable_cell_visible($field_value, 'show_avail_cpu', $row_id, 'right');

			if ($if_lsf_cluster_control || $if_show_actions) {
				form_checkbox_cell($job['jobid'], $row_id, $disabled);
			}
		}

		form_end_row();
	} else {
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Job Records Found', 'grid') . '</em></td></tr>';
	}

	html_end_box(false);

	if (!empty($job_results)) {
		print $nav;
	}
}

function display_job_legend() {
	html_start_box('', '100%', '', '3', 'center', '');

	print '<tr><td><div class="footerContainer">';
	if (read_config_option('grid_efficiency_warning_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_efficiency_warning_bgcolor')) . ";'>" . __('Warning Efficiency', 'grid') . '</div>';
	}

	if (read_config_option('grid_efficiency_alarm_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_efficiency_alarm_bgcolor')) . ";'>" . __('Alarm Efficiency', 'grid') . '</div>';
	}

	if (read_config_option('grid_flapping_enabled') && read_config_option('grid_flapping_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_flapping_bgcolor')) . ";'>" . __('Flapping', 'grid') . '</div>';
	}

	if (read_config_option('grid_depend_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_depend_bgcolor')) . ";'>" . __('Dependencies', 'grid') . '</div>';
	}

	if (read_config_option('grid_invalid_depend_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_invalid_depend_bgcolor')) . ";'>" . __('Invalid Dependencies', 'grid'). '</div>';
	}

	if (read_config_option('grid_exit_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_exit_bgcolor')) . ";'>" . __('Exited', 'grid') . '</div>';
	}

	if (read_config_option('grid_exclusive_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_exclusive_bgcolor')) . ";'>" . __('Exclusive', 'grid') . '</div>';
	}

	if (read_config_option('grid_interactive_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_interactive_bgcolor')) . ";'>" . __('Interactive', 'grid') . '</div>';
	}

	if (read_config_option('grid_licsched_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_licsched_bgcolor')) . ";'>" . __('Susp Lic Sched', 'grid') . '</div>';
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING') {
		if (read_config_option('grididle_bgcolor') > 0) {
			print "<div class='nowrap center' style='text-align:center; background-color:#" . get_color(read_config_option('grididle_bgcolor')) . ";'>" . read_config_option('grididle_filter_name') . '</div>';
		}
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING' || get_request_var('status') == 'PROV' ||
		get_request_var('status') == 'DONE' || get_request_var('status') == 'EXIT' || get_request_var('status') == 'FINISHED') {
		if (read_config_option('gridmemvio_bgcolor') > 0) {
			print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('gridmemvio_bgcolor')) . ";'>" . read_config_option('gridmemvio_filter_name') . '</div>';
		}
		if (read_config_option('gridmemvio_us_bgcolor') > 0) {
			print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('gridmemvio_us_bgcolor')) . ";'>" . read_config_option('gridmemvio_us_filter_name') . '</div>';
		}
	}

	if (get_request_var('status') == 'ACTIVE' || get_request_var('status') == 'RUNNING') {
		if (read_config_option('gridrunlimitvio_bgcolor') > 0) {
			print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('gridrunlimitvio_bgcolor')) . ";'>" . read_config_option('gridrunlimitvio_filter_name') . '</div>';
		}
	}

	if (read_config_option('grid_slaloaning_bgcolor') > 0) {
		print "<div class='nowrap center' style='background-color:#" . get_color(read_config_option('grid_slaloaning_bgcolor')) . ";'>" . __('Loaning', 'grid') . '</div>';
	}

	api_plugin_hook('grid_jobs_legend');

	print '</div></td></tr>';

	html_end_box();
}

function grid_form_alternate_row($row_id, $color_or_class, $disabled = false) {
	static $i = 1;

	if ($i % 2 == 1) {
		$odd_even_class = 'odd';
	} else {
		$odd_even_class = 'even';
	}
	$i++;

	$class = '';
	$style = '';
	if (ctype_xdigit($color_or_class)) {
		$style = 'style="background-color:#' . $color_or_class . '"';
	} else {
		$class = $color_or_class;
	}

	if ($row_id != '') {
		print "<tr class='tableRow $odd_even_class". ($disabled ? '':' selectable') . ($class != '' ? ' ' . $class:'') . "' $style id='$row_id'>\n";
	} else {
		print "<tr class='tableRow $odd_even_class". ($disabled ? '':' selectable') . ($class != '' ? ' ' . $class:'') . "' $style>\n";
	}

}

function get_user_page_setting($page, $setting, $default) {
	$settings = read_grid_config_option($page, true);

	if ($settings != '') {
		$setarray = explode('|', $settings);

		if (cacti_sizeof($setarray)) {
			foreach ($setarray as $s) {
				$iset = explode('=', $s);
				if ($iset[0] == $setting) {
					return $iset[1];
				}
			}
		}
	}

	return $default;
}

function number_format_grid($data, $decimals = 0) {
	if ($data == '-') {
		return $data;
	} else {
		return number_format_i18n($data, $decimals);
	}
}

/* grid_graph_area - draws an area the contains full sized graphs
   @arg $graph_array - the array to contains graph information. for each graph in the
	array, the following two keys must exist
	$arr[0]["local_graph_id"] // graph id
	$arr[0]["title_cache"] // graph title
   @arg $graph_start - start time for graph
   @arg $graph_end - end time for graph
   @arg $columns - the number of columns to present */
function grid_graph_area(&$graph_array, $graph_start, $graph_end, $columns = 0) {
	global $config;
	$i = 0; $k = 0; $j = 0;

	$num_graphs = cacti_sizeof($graph_array);

	if ($columns == 0) {
		$columns = read_user_setting('num_columns');
	}

	?>
	<script type='text/javascript'>
	var refreshMSeconds = <?php print read_user_setting('page_refresh')*1000;?>;
	var graph_start     = <?php print $graph_start;?>;
	var graph_end       = <?php print $graph_end;?>;
	</script>
	<?php

	if ($num_graphs > 0) {
		foreach ($graph_array as $graph) {
			if ($i == 0) {
				print "<tr class='tableRowGraph'>\n";
			}

			?>
			<td style='width:<?php print round(100 / $columns, 2);?>%;'>
				<div>
				<table style='text-align:center;margin:auto;'>
					<tr>
						<td>
							<a class='pic' href='<?php print html_escape($config['url_path'] . "graph.php?action=view&rra_id=all&local_graph_id=" . $graph["local_graph_id"]);?>'>
							<div class='graphWrapper' style='width:100%;' id='wrapper_<?php print $graph['local_graph_id']?>' graph_width='<?php print $graph['width'];?>' graph_height='<?php print $graph['height'];?>' title_font_size='<?php print ((read_user_setting('custom_fonts') == 'on') ? read_user_setting('title_size') : read_config_option('title_size'));?>'></div>
							</a>
							<?php print (read_user_setting('show_graph_title') == 'on' ? "<span class='center'>" . html_escape($graph['title_cache']) . '</span>' : '');?>
						</td>
					</tr>
				</table>
				<div>
			</td>
			<td id='dd<?php print $graph['local_graph_id'];?>' class='noprint graphDrillDown'></td>
			<?php

			$i++;

			if (($i % $columns) == 0) {
				$i = 0;
				print "</tr>\n";
			}
		}

		while(($i % $columns) != 0) {
			print "<td style='text-align:center;width:" . round(100 / $columns, 2) . "%;'></td>";
			$i++;
		}

		print "</tr>\n";
	}
}

/* html_graph_thumbnail_area - draws an area the contains thumbnail sized graphs
   @arg $graph_array - the array to contains graph information. for each graph in the
                       array, the following two keys must exist
                       $arr[0]["local_graph_id"] // graph id
                       $arr[0]["title_cache"] // graph title
   @arg $graph_start - start time for graph
   @arg $graph_end - end time for graph
   @arg $columns - the number of columns to present */
function grid_graph_thumbnail_area(&$graph_array, $graph_start, $graph_end, $columns = 0) {
	global $config;
	$i = 0; $k = 0; $j = 0;

	$num_graphs = cacti_sizeof($graph_array);

	if ($columns == 0) {
		$columns = read_user_setting('num_columns');
	}

	?>
	<script type='text/javascript'>
	var refreshMSeconds = <?php print read_user_setting('page_refresh')*1000;?>;
	var graph_start     = <?php print $graph_start;?>;
	var graph_end       = <?php print $graph_end;?>;
	</script>
	<?php

	if ($num_graphs > 0) {
		$start = true;
		foreach ($graph_array as $graph) {
			if (isset($graph['graph_template_name'])) {
				if (isset($prev_graph_template_name)) {
					if ($prev_graph_template_name != $graph['graph_template_name']) {
						$prev_graph_template_name = $graph['graph_template_name'];
					}
				} else {
					$prev_graph_template_name = $graph['graph_template_name'];
				}
			} elseif (isset($graph['data_query_name'])) {
				if (isset($prev_data_query_name)) {
					if ($prev_data_query_name != $graph['data_query_name']) {
						$print  = true;
						$prev_data_query_name = $graph['data_query_name'];
					} else {
						$print = false;
					}
				} else {
					$print  = true;
					$prev_data_query_name = $graph['data_query_name'];
				}

				if ($print) {
					if (!$start) {
						while(($i % $columns) != 0) {
							print "<td style='text-align:center;width:" . round(100 / $columns, 3) . "%;'></td>";
							$i++;
						}

						print "</tr>\n";
					}

					print "<tr class='tableHeader'>
							<td class='graphSubHeaderColumn textHeaderDark' colspan='$columns'>" . __('Data Query:') . ' ' . $graph['data_query_name'] . "</td>
						</tr>\n";
					$i = 0;
				}
			}

			if ($i == 0) {
				print "<tr class='tableRowGraph'>\n";
				$start = false;
			}

			?>
			<td style='width:<?php print round(100 / $columns, 2);?>%;'>
				<table style='text-align:center;margin:auto;'>
					<tr>
						<td>
							<a class='pic' href='<?php print html_escape($config['url_path'] . "graph.php?action=view&rra_id=all&local_graph_id=" . $graph["local_graph_id"]);?>'>
							<div class='graphWrapper' id='wrapper_<?php print $graph['local_graph_id']?>' graph_width='<?php print read_user_setting('default_width');?>' graph_height='<?php print read_user_setting('default_height');?>'></div>
							</a>
							<?php print (read_user_setting('show_graph_title') == 'on' ? "<span class='center'>" . html_escape($graph['title_cache']) . '</span>' : '');?>
						</td>
						<td id='dd<?php print $graph['local_graph_id'];?>' class='noprint graphDrillDown'></td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i % $columns) == 0 && ($k < $num_graphs)) {
				$i=0;
				$j++;
				print "</tr>\n";
				$start = true;
			}
		}

		if (!$start) {
			while(($i % $columns) != 0) {
				print "<td style='text-align:center;width:" . round(100 / $columns, 2) . "%;'></td>";
				$i++;
			}

			print "</tr>\n";
		}
	}
}

function grid_get_var_value_from_line($line, $arg) {
	if (strpos($line, $arg) === 0) {
		$value = explode('=', $line);
		return trim($value[1], "\"\n");
	}
	return '';
}

/**
 * Get LSF configuration value for one or more equivalent configuration name from lsf.conf.
 *
 * @param string $lsf_envdir LSF enviroment directory
 * @param [type] $arg array/string of LSF configuration name
 * @return string LSF configuration value
 */
function grid_get_lsf_conf_variable_value($lsf_envdir, $arg) {
	$lines = file($lsf_envdir.'/lsf.conf');

	foreach ($lines as $line_num => $line) {
		if(is_array($arg)) {
			foreach ($arg as $key) {
				$value = grid_get_var_value_from_line($line, $key);
				if(!empty($value)) {
					return $value;
				}
			}
		} else {
			$value = grid_get_var_value_from_line($line, $arg);
			if(!empty($value)) {
				return $value;
			}
		}
	}

	return '';
}
