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

include_once($config['base_path'] . '/plugins/lichist/functions.php');

$guest_account = true;

global $lichist_views_columns;

$lichist_views_columns = array(
	'curr' => array(
		'vendor_daemon'             => 1,
		'feature_name'              => 1,
		'feature_version'           => 1,
		'username'                  => 1,
		'status'                    => 1,
		'hostname'                  => 1,
		'chkoutid'                  => 1,
		'tokens_acquired'           => 1,
		'tokens_acquired_date'      => 1,
		'duration'                  => 1
	),
	'hist' => array(
		'vendor_daemon'             => 1,
		'feature_name'              => 1,
		'feature_version'           => 1,
		'username'                  => 1,
		'hostname'                  => 1,
		'chkoutid'                  => 1,
		'tokens_acquired'           => 1,
		'tokens_acquired_date'      => 1,
		'duration'                  => 1,
		'last_poll_time'            => 1,
		'tokens_released_date'      => 1,
		'conflicting_jobids'        => 1,
		'conflicting_jobids_count'  => 1
	)
);


/* make sure that if you go off page, you done come here by default */
unset($_SESSION['sess_grid_bjobs_tab']);

echo "<link type='text/css' href='" . $config['url_path'] . "plugins/lichist/lichist.css' rel='stylesheet'>";

grid_licusage_params();
grid_licusage_filter();
grid_view_curr_licusage();
grid_view_hist_licusage();

bottom_footer();

function build_query_string() {
	$query_string = "header=false&action=viewjob&tab=lichist&cluster_tz=" . get_request_var('cluster_tz') .
		"&clusterid=" . get_request_var('clusterid') . "&indexid=" . get_request_var('indexid') . "&jobid=" . get_request_var('jobid') .
		"&submit_time=" . get_request_var('submit_time') . "&start_time=" . get_request_var('start_time') . "&end_time=" . get_request_var('end_time');
	return $query_string;

}


/* For running jobs, find all the (potential) license checkouts for that job
*/
function grid_get_curr_licusage_records(&$sql_where, &$row_count, $apply_limits = TRUE, $row_limit = 30) {
	global $job, $lichist_views_columns;

	$jobid      = $job['jobid'];
	$indexid    = $job['indexid'];
	$start_time = date('Y-m-d H:i:00', strtotime($job['start_time']));
	$user       = $job['user'];

	$aster = strpos($job['exec_host'], '*');
	if ($aster) {
		$exec_host = substr($job['exec_host'], $aster+1);
	}else{
		$exec_host = $job['exec_host'];
	}

	/* filter sql where */
	if (get_request_var('filter') == '') {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") .
			" hostname= ? AND username= ?" .
			" AND ((tokens_acquired_date >= ? AND status='start') OR (tokens_acquired_date='0000-00-00 00:00:00' AND status='queued'))";
		$sql_params[] = $exec_host;
		$sql_params[] = $user;
		$sql_params[] = $start_time;
	} else {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") .
			" hostname= ? AND username = ?" .
			" AND ((tokens_acquired_date >= ? AND status='start') OR (tokens_acquired_date='0000-00-00 00:00:00' AND status='queued'))" .
			" AND ((vendor_daemon LIKE ?) OR (feature_name LIKE ?))";
		$sql_params[] = $exec_host;
		$sql_params[] = $user;
		$sql_params[] = $start_time;
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';

	}

	$sort_order = '';
	if ( isset($lichist_views_columns['curr'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	// Now find all the license checkouts on the job host(s) and username
	$sql_query = "SELECT vendor_daemon, feature_name, status, feature_version,
		username, groupname, hostname, chkoutid, tokens_acquired, tokens_acquired_date,
		unix_timestamp()-unix_timestamp(tokens_acquired_date) AS duration
		FROM lic_services_feature_details
		$sql_where
		$sort_order";

	$rows = db_fetch_assoc_prepared($sql_query, $sql_params);
	$row_count = cacti_sizeof($rows);

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page_curr')-1)) . "," . $row_limit;
	}

	//printf($sql_query . "\n");
	$rows = db_fetch_assoc_prepared($sql_query, $sql_params);

	return $rows;
}

function grid_get_hist_licusage_records(&$sql_where, &$row_count, $apply_limits = TRUE, $row_limit = 30) {
	global $job, $lichist_views_columns;

	$jobid       = $job['jobid'];
	$indexid     = $job['indexid'];
	$clusterid   = $job['clusterid'];
	$submit_time = $job['submit_time'];
	$start_time  = $job['start_time'];
	$end_time    = $job['end_time'];
	$user        = $job['user'];

	$aster = strpos($job['exec_host'], '*');
	if ($aster) {
		$exec_host = substr($job['exec_host'], $aster+1);
	}else{
		$exec_host = $job['exec_host'];
	}

	// Because lmstat only returns minutes, not seconds, accuracy
	$start_time  = date('Y-m-d H:i:00', strtotime($job['start_time']));
	$end_time    = $job['end_time'];

	if ($job['stat'] == 'RUNNING') {
		$sql_query = "SELECT vendor_daemon, feature_name, status, feature_version, username,
			groupname, hostname, chkoutid, tokens_acquired, tokens_acquired_date,
			last_poll_time, tokens_released_date,
			unix_timestamp(last_poll_time)-unix_timestamp(tokens_acquired_date) AS duration,
			'N/A' as conflicting_jobids,
			'N/A' as conflicting_jobids_count
			FROM lic_services_feature_history c
			WHERE '$start_time' <= tokens_acquired_date
			AND c.hostname='$exec_host'
			AND c.username='$user'
			AND NOW() > last_poll_time ".
			(strlen(get_request_var('filter')) ? " AND (vendor_daemon LIKE '%" . get_request_var("filter") . "%' OR feature_name LIKE '%" . get_request_var("filter") . "%')":"");
	} else if ($job['stat'] == 'DONE' || $job['stat'] == 'EXIT') {
		// Now construct the query
		if ($start_time != '0000-00-00 00:00:00') {
			if (read_config_option('grid_partitioning_enable') == 'on') {
				$sql_query = generate_partition_union_query("SELECT count(*)
					FROM lic_services_feature_history_mapping
					WHERE clusterid=$clusterid
					AND jobid=$jobid
					AND indexid=$indexid
					AND submit_time='$submit_time'
					AND exec_host='$exec_host'",
					"lic_services_feature_history_mapping",
					"", $start_time, $end_time);
			}else{
				$sql_query = "SELECT count(*)
					FROM lic_services_feature_history_mapping
					WHERE jobid=$jobid
					AND indexid=$indexid
					AND clusterid=$clusterid
					AND submit_time='$submit_time'
					AND exec_host='$exec_host'";
			}
		}else{
			$sql_query = "SELECT '1' AS number WHERE 1 = 0";
		}
		if (empty($sql_query)) {
			return array();
		}

		$count = db_fetch_cell($sql_query);

		if ($count > 0) {
			if ($start_time != '0000-00-00 00:00:00') {
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$tables    = partition_get_partitions_for_query("lic_services_feature_history", $start_time, $end_time);
					$sql_query = '';
					if (cacti_sizeof($tables)) {
						foreach($tables as $table) {
							$partition = str_replace('lic_services_feature_history', '', $table);
							$sql_query .= (strlen($sql_query) ? ' UNION ':'') . "SELECT *
								FROM (
									SELECT b.id, b.vendor_daemon, b.feature_name, b.status , b.feature_version,
									b.username, b.groupname, b.hostname, b.chkoutid, b.tokens_acquired,
									b.tokens_acquired_date, b.last_poll_time, b.tokens_released_date,
									unix_timestamp(b.last_poll_time)-unix_timestamp(b.tokens_acquired_date) AS duration,
									0 AS conflicting_jobids, count(distinct a.jobid) - 1 as conflicting_jobids_count
									FROM lic_services_feature_history_mapping$partition a
									INNER JOIN lic_services_feature_history$partition b
									ON (a.history_event_id = b.id)
									WHERE a.history_event_id IN (
										SELECT history_event_id
										FROM lic_services_feature_history_mapping$partition c
										WHERE clusterid=$clusterid
										AND jobid=$jobid
										AND indexid=$indexid
										AND submit_time='$submit_time'
										AND exec_host='$exec_host')" .
										(strlen(get_request_var('filter')) ? " AND (b.vendor_daemon LIKE '%" . get_request_var("filter") . "%' OR b.feature_name LIKE '%" . get_request_var("filter") . "%')":"") . "
									AND a.exec_host='$exec_host'
									AND ((b.tokens_acquired_date >= '$start_time') AND (b.tokens_released_date <='$end_time' OR ('$end_time' BETWEEN b.last_poll_time AND b.tokens_released_date)))
									GROUP BY b.id, b.vendor_daemon, b.feature_name, b.username,
									b.hostname, b.tokens_acquired_date, b.tokens_released_date
								) c";
						}
					}
				}else{
					$sql_query = "SELECT *
						FROM (
							SELECT b.id, b.vendor_daemon, b.feature_name, b.status, b.feature_version,
							b.username, b.groupname, b.hostname, b.chkoutid, b.tokens_acquired,
							b.tokens_acquired_date, b.last_poll_time, b.tokens_released_date,
							unix_timestamp(b.last_poll_time)-unix_timestamp(b.tokens_acquired_date) AS duration,
							0 AS conflicting_jobids, count(distinct a.jobid) - 1 as conflicting_jobids_count
							FROM lic_services_feature_history_mapping a
							INNER JOIN lic_services_feature_history b
							ON (a.history_event_id = b.id)
							WHERE a.history_event_id IN (
								SELECT history_event_id
								FROM lic_services_feature_history_mapping c
								WHERE clusterid=$clusterid
								AND jobid=$jobid
								AND indexid=$indexid
								AND submit_time='$submit_time'
								AND exec_host='$exec_host')" .
								(strlen(get_request_var('filter')) ? " AND (b.vendor_daemon LIKE '%" . get_request_var("filter") . "%' OR b.feature_name LIKE '%" . get_request_var("filter") . "%')":"") . "
							AND ((b.tokens_acquired_date >= '$start_time') AND (b.tokens_released_date <='$end_time' OR ('$end_time' BETWEEN b.last_poll_time AND b.tokens_released_date)))
							AND a.exec_host='$exec_host'
							GROUP BY b.id, b.vendor_daemon, b.feature_name, b.username,
							b.hostname, b.tokens_acquired_date, b.tokens_released_date
						) c";
				}
			}else{
				$sql_query = "SELECT '1' AS number WHERE 1 = 0";
			}
		} else {
			$sql_query = "SELECT
				vendor_daemon, feature_name, status, feature_version,
				username, groupname, hostname, chkoutid, tokens_acquired,
				tokens_acquired_date, last_poll_time, tokens_released_date,
				unix_timestamp(last_poll_time)-unix_timestamp(tokens_acquired_date) AS duration,
				'N/A' as conflicting_jobids, 'N/A' as conflicting_jobids_count
				FROM lic_services_feature_history c
				WHERE '$start_time' <= tokens_acquired_date
				AND username='$user'
				AND hostname='$exec_host'
				AND '$end_time' > last_poll_time" .
				(strlen(get_request_var('filter')) ? " AND (c.vendor_daemon LIKE '%" . get_request_var("filter") . "%' OR c.feature_name LIKE '%" . get_request_var("filter") . "%')":"");

			if ($start_time != '0000-00-00 00:00:00') {
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$sql_query = generate_partition_union_query($sql_query, "lic_services_feature_history", "", $start_time, $end_time);
				}
			}else{
				$sql_query = "SELECT '1' AS number WHERE 1 = 0";
			}
		}
	}else{
		return array();
	}

	if (empty($sql_query)) {
		return array();
	}

	$sort_order = '';
	if ( isset($lichist_views_columns['hist'][get_request_var('sort_column')]) ) {
		$sort_order = get_order_string();
	}

	$sql_query .= ' ' . $sort_order;

	$rows = db_fetch_assoc($sql_query);
	$row_count = count($rows);

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($row_limit*(get_request_var('page_hist')-1)) . "," . $row_limit;
	}

	//printf($sql_query . "<br/>");
	$rows = db_fetch_assoc($sql_query);

	return $rows;
}

function grid_licusage_params() {
	global $report, $grid_search_types, $grid_rows_selector, $config;

	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option("grid_records")
			),
		'page_curr' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'page_hist' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'vendor_daemon',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	validate_store_request_vars($filters, 'sess_lichist');

	html_start_box(__('License Usage Filters'), '100%', '', '3', 'center', '');

}

function grid_licusage_filter() {
	global $config, $grid_rows_selector;

	?>
	<tr class='odd'>
		<td>
			<table cellpadding='2' cellspacing='0'>
				<tr>
					<td width='70'>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' onkeydown="if (event.keyCode == 13) document.getElementById('refresh').click()" size='30' value="<?php print get_request_var('filter');?>">
					</td>
					<td>
						<?php print __('Records');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilterChange()'>
						<?php
						if (cacti_sizeof($grid_rows_selector) > 0) {
						foreach ($grid_rows_selector as $key => $value) {
							print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print " selected"; } print ">" . $value . "</option>\n";
						}
						}
						?>
						</select>
					</td>
					<td>
						<button id='refresh' name='go' onClick='applyFilterChange()'>Go</button>
					</td>
					<td>
						<button id='clear' name='clear' onClick="clearFilter()">Clear</button>
					</td>
				</tr>
			</table>
			<script type="text/javascript">
			function applyFilterChange() {
				strURL = '<?php print basename($_SERVER['PHP_SELF']) . "?" . build_query_string(); ?>'
				strURL = strURL + '&filter=' + $('#filter').val();
				strURL = strURL + '&rows=' + $('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = '<?php print basename($_SERVER['PHP_SELF']) . "?" . build_query_string(); ?>'
				strURL = strURL + '&filter=';
				strURL = strURL + '&rows=-1';
				loadPageNoHeader(strURL);
			}
			</script>
		</td>
	</tr>
	<?php

	html_end_box(true);
}

function build_lichist_curr_display_array() {
	$display_text = array();

	$display_text += array('vendor_daemon' => array('display' => __('Vendor Daemon'), 'sort' => 'ASC') );
	$display_text += array('feature_name' => array('display' => __('Feature'), 'sort' => 'ASC') );
	$display_text += array('feature_version' => array('display' => __('Version'), 'sort' => 'ASC') );
	$display_text += array('username' => array('display' => __('User'), 'sort' => 'ASC') );
	$display_text += array('status' => array('display' => __('Status'), 'sort' => 'ASC') );
	$display_text += array('hostname' => array('display' => __('Host'), 'sort' => 'ASC') );
	$display_text += array('chkoutid' => array('display' => __('Checkout ID'), 'sort' => 'ASC') );
	$display_text += array('tokens_acquired' => array('display' => __('Tokens'), 'sort' => 'ASC') );
	$display_text += array('tokens_acquired_date' => array('display' => __('Acquired'), 'sort' => 'ASC') );
	$display_text += array('duration' => array('display' => __('Duration'), 'sort' => 'DESC') );

	return $display_text;
}


// Displays the table for the job's current licusage, if the job is running
function grid_view_curr_licusage() {
	global $job, $title, $report, $grid_search_types, $grid_rows_selector, $config;

	$job_stat = $job['stat'];
	if ($job_stat == 'EXIT' || $job_stat == 'DONE') {
		return;
	}

	$sql_where = "";
	$row_count = 0;

	if (get_request_var('rows') == -1) {
		$row_limit = read_grid_config_option('grid_records');
	}elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	}else{
		$row_limit = get_request_var('rows');
	}

	$lics = grid_get_curr_licusage_records($sql_where, $row_count, TRUE, $row_limit);

	html_start_box('Current License Usage', '100%', '', '3', 'center', '');

	$total_rows = $row_count;

	$fw_from = basename($_SERVER['PHP_SELF']);
	if($fw_from == "grid_bjobs.php"){
		$drilldown_url = "plugins/grid/grid_bjobs.php";
	}else if($fw_from == "heuristics_jobs.php"){
		$drilldown_url = "plugins/heuristics/heuristics_jobs.php";
	}

	$display_text = build_lichist_curr_display_array();

	/* generate page list */
	$nav = html_nav_bar(basename($_SERVER['PHP_SELF']) . '?' . build_query_string() . '&filter=' . get_request_var('filter') , MAX_DISPLAY_PAGES, get_request_var('page_curr'), $row_limit, $total_rows, '', '', 'page_curr', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, basename($_SERVER['PHP_SELF']) . '?' . build_query_string());

	$i = 0;
	if (cacti_sizeof($lics) > 0) {
		foreach ($lics as $lic) {
			form_alternate_row();

			?>
			<td><?php print (strlen(get_request_var('filter')) ? preg_replace("/" . "(" . preg_quote(get_request_var('filter')) . ")" . "/" . "i", "<span style='background-color: #F8D93D;'>\\1</span>", $lic['vendor_daemon']):$lic['vendor_daemon']);?></td>
			<td><?php print (strlen(get_request_var('filter')) ? preg_replace("/" . "(" . preg_quote(get_request_var('filter')) . ")" . "/" . "i", "<span style='background-color: #F8D93D;'>\\1</span>", $lic['feature_name']):$lic['feature_name']);?></td>
			<td><?php print $lic['feature_version'];?></td>
			<td><?php print $lic['username'];?></td>
			<td><?php print strtoupper($lic['status']);?></td>
			<td><?php print $lic['hostname'];?></td>
			<td><?php print $lic['chkoutid'];?></td>
			<td><?php print $lic['tokens_acquired'];?></td>
			<td><?php print $lic['tokens_acquired_date'];?></td>
		    <td><?php print ($lic['status'] == 'queued' ? 'N/A':(grid_format_seconds($lic['duration']/60)));?></td>
           <?php
		}

	}else{
		print "<tr><td colspan='4'><em>No License Usage Records Found</em></td></tr>";
	}

	html_end_box(false);

	if (cacti_sizeof($lics) > 0) {
		print $nav;
	}

	html_start_box("", '100%', '', '3', 'center', '');

	print "<tr><td>Last Refresh : " . date("g:i:s a", time()) . '</td></tr>';

	html_end_box();

}

function build_lichist_hist_display_array() {
	$display_text = array();

	$display_text += array('vendor_daemon' => array('display' => __('Vendor Daemon'), 'sort' => 'ASC') );
	$display_text += array('feature_name' => array('display' => __('Feature'), 'sort' => 'ASC') );
	$display_text += array('feature_version' => array('display' => __('Version'), 'sort' => 'ASC') );
	$display_text += array('username' => array('display' => __('User'), 'sort' => 'ASC') );
	$display_text += array('hostname' => array('display' => __('Host'), 'sort' => 'ASC') );
	$display_text += array('chkoutid' => array('display' => __('Checkout ID'), 'sort' => 'ASC') );
	$display_text += array('tokens_acquired' => array('display' => __('Tokens'), 'sort' => 'ASC') );
	$display_text += array('tokens_acquired_date' => array('display' => __('Acquired'), 'sort' => 'ASC') );
	$display_text += array('duration' => array('display' => __('Duration'), 'sort' => 'DESC') );
	$display_text += array('last_poll_time' => array('display' => __('Min Released'), 'sort' => 'ASC', 'tip' => 'The minimum possible time taken to release the license. (That is, the time when license poller found the license was released minus the license poller interval.)') );
	$display_text += array('tokens_released_date' => array('display' => __('Max Released'), 'sort' => 'ASC', 'tip' => 'The maximum possible time taken to release the license. (The time when license poller found the license was released.)') );
	$display_text += array('nosort1' => array('display' => __('Conflicting Jobs'), 'sort' => '', 'tip' => 'The Job IDs of jobs that may have used the license. This occurs when multiple jobs from the same user were executed concurrently on the same execution host.') );
	$display_text += array('conflicting_jobids_count' => array('display' => __('Conflicts'), 'sort' => 'ASC', 'tip' => 'Number of Job IDs that may have used the license. This occurs when multiple jobs from the same user were executed concurrently on the same execution host.') );

	return $display_text;
}

function grid_view_hist_licusage() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $config, $job;

	$sql_where = "";
	$row_count = 0;

	if (get_request_var('rows') == -1) {
		$row_limit = read_grid_config_option("grid_records");
	}elseif (get_request_var('rows') == -2) {
		$row_limit = 99999999;
	}else{
		$row_limit = get_request_var('rows');
	}

	$fw_from = basename($_SERVER['PHP_SELF']);
	if($fw_from == "grid_bjobs.php"){
		$drilldown_url = "plugins/grid/grid_bjobs.php";
	}else if($fw_from == "heuristics_jobs.php"){
		$drilldown_url = "plugins/heuristics/heuristics_jobs.php";
	}

	$lics = grid_get_hist_licusage_records($sql_where, $row_count, TRUE, $row_limit);

	html_start_box('Historic License Usage', '100%', '', '3', 'center', '');

	$total_rows = $row_count;

	$display_text = build_lichist_hist_display_array();

	/* generate page list */
	$nav = html_nav_bar(basename($_SERVER['PHP_SELF']) . '?' . build_query_string() . '&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page_hist'), $row_limit, $total_rows, '', '', 'page_hist', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, basename($_SERVER['PHP_SELF']) . '?' . build_query_string());

	$i = 0;
	if (cacti_sizeof($lics)) {
		foreach ($lics as $lic) {
			if ($lic["conflicting_jobids"] != 'N/A') {
				$history_id = $lic["id"];
				if (read_config_option('grid_partitioning_enable') == 'on') {
					$tables    = partition_get_partitions_for_query("lic_services_feature_history_mapping", $job["start_time"], $job["end_time"]);
					$sql_query = '';
					if (cacti_sizeof($tables)) {
						foreach($tables as $table) {
							$partition = str_replace('lic_services_feature_history_mapping', '', $table);
							$sql_query .= (strlen($sql_query) ? ' UNION ':'') . "SELECT jobid, indexid, clusterid, submit_time
								FROM lic_services_feature_history_mapping$partition
								WHERE history_event_id = " . $history_id;
						}
					}
				}else{
					$sql_query = "SELECT jobid, indexid, clusterid, submit_time
						FROM lic_services_feature_history_mapping
						WHERE history_event_id = " . $history_id;
				}

				$rows = db_fetch_assoc($sql_query);

				$jobids_string = "";
				if (cacti_sizeof($rows) > 0) {
					foreach ($rows as $row) {
						if ($row["jobid"] != $job["jobid"]) {
							if ($jobids_string == "") {
								$jobids_string .= "<a href='" . htmlspecialchars("$fw_from?action=viewjob&tab=general" .
									"&jobid="       . $row["jobid"] .
									"&indexid="     . $row["indexid"] .
									"&clusterid="   . $row["clusterid"] .
									"&submit_time=" . strtotime($row["submit_time"])) . "'>"
									. $row["jobid"] . "</a>";
							} else {
								$jobids_string .= ", <a href='" . htmlspecialchars("$fw_from?action=viewjob&tab=general" .
									"&jobid="       . $row["jobid"] .
									"&indexid="     . $row["indexid"] .
									"&clusterid="   . $row["clusterid"] .
									"&submit_time=" . strtotime($row["submit_time"])) . "'>"
									. $row["jobid"] . "</a>";
							}
						}
					}
					$lic["conflicting_jobids_count"]=cacti_sizeof($rows)-1;
				}else{
					$lic["conflicting_jobids_count"]=0;
				}
				if ($jobids_string == "") {
					$jobids_string = "No Conflicts";
				}
			} else {
				$jobids_string = "N/A";
			}

			form_alternate_row();

			?>
			<td><?php print (strlen(get_request_var('filter')) ? preg_replace("/" . "(" . preg_quote(get_request_var('filter')) . ")" . "/" . "i", "<span style='background-color: #F8D93D;'>\\1</span>", $lic["vendor_daemon"]):$lic["vendor_daemon"]);?></td>
			<td><?php print (strlen(get_request_var('filter')) ? preg_replace("/" . "(" . preg_quote(get_request_var('filter')) . ")" . "/" . "i", "<span style='background-color: #F8D93D;'>\\1</span>", $lic["feature_name"]):$lic["feature_name"]);?></td>
			<td><?php print $lic["feature_version"];?></td>
			<td><?php print $lic["username"];?></td>
			<td><a class='hyper' href='<?php print $config['url_path'] . '/plugins/grid/grid_bzen.php?query=1&clusterid=' . $job['clusterid'] . '&exec_host=' . $lic['hostname'] . '&date1=' . $job['start_time'] . '&date2=' . $job['end_time'];?>'><?php print $lic["hostname"];?></a></td>
			<td><?php print $lic["chkoutid"];?></td>
			<td><?php print $lic["tokens_acquired"];?></td>
			<td><?php print $lic["tokens_acquired_date"];?></td>
            <td><?php print grid_format_seconds($lic['duration']/60);?></td>
			<td><?php print $lic["last_poll_time"];?></td>
			<td><?php print $lic["tokens_released_date"];?></td>
			<td><?php print $jobids_string;?></td>
			<td><?php print $lic["conflicting_jobids_count"];?></td>
			<?php
		}

	}else{
		print "<tr><td colspan='4'><em>No License Usage Records Found</em></td></tr>";
	}

	html_end_box(false);

	if (cacti_sizeof($lics) > 0) {
		print $nav;
	}

	html_start_box('', '100%', '', '3', 'center', '');

	print "<tr><td>Last Refresh : " . date("g:i:s a", time()) . '</td></tr>';

	html_end_box();
}

?>
