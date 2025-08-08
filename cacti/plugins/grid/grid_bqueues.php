<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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
include_once($config['library_path'] . '/rrd.php');
include_once($config['library_path'] . '/timespan_settings.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['library_path'] . '/rtm_plugins.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

/* get the grid polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

define('INF_INT', 2147483647);
define('INF_FLOAT', 2147483648);

define('Q_ATTRIB_EXCLUSIVE',                    0x01);
define('Q_ATTRIB_DEFAULT',                      0x02);
define('Q_ATTRIB_FAIRSHARE',                    0x04);
define('Q_ATTRIB_PREEMPTIVE',                   0x08);
define('Q_ATTRIB_NQS',                          0x10);
define('Q_ATTRIB_RECEIVE',                      0x20);
define('Q_ATTRIB_PREEMPTABLE',                  0x40);
define('Q_ATTRIB_BACKFILL',                     0x80);
define('Q_ATTRIB_HOST_PREFER',                 0x100);
define('Q_ATTRIB_NONPREEMPTIVE',               0x200);
define('Q_ATTRIB_NONPREEMPTABLE',              0x400);
define('Q_ATTRIB_NO_INTERACTIVE',              0x800);
define('Q_ATTRIB_ONLY_INTERACTIVE',           0x1000);
define('Q_ATTRIB_NO_HOST_TYPE',               0x2000);
define('Q_ATTRIB_IGNORE_DEADLINE',            0x4000);
define('Q_ATTRIB_CHKPNT',                     0x8000);
define('Q_ATTRIB_RERUNNABLE',                0x10000);
define('Q_ATTRIB_EXCL_RMTJOB',               0x20000);
define('Q_ATTRIB_MC_FAST_SCHEDULE',          0x40000);
define('Q_ATTRIB_ENQUE_INTERACTIVE_AHEAD',   0x80000);
define('Q_MC_FLAG',                         0xf00000);
define('Q_ATTRIB_LEASE_LOCAL',              0x100000);
define('Q_ATTRIB_LEASE_ONLY',               0x200000);
define('Q_ATTRIB_RMT_BATCH_LOCAL',          0x300000);
define('Q_ATTRIB_RMT_BATCH_ONLY',           0x400000);
define('Q_ATTRIB_RESOURCE_RESERVE',        0x1000000);
define('Q_ATTRIB_FS_DISPATCH_ORDER_QUEUE', 0x2000000);
define('Q_ATTRIB_BATCH',                   0x4000000);
define('Q_ATTRIB_ONLINE',                  0x8000000);
define('Q_ATTRIB_INTERRUPTIBLE_BACKFILL', 0x10000000);
define('Q_ATTRIB_APS',                    0x20000000);
define('Q_ATTRIB_NO_HIGHER_RESERVE',      0x40000000);
define('Q_ATTRIB_NO_HOST_VALID',          0x80000000);

$grid_queue_control_actions = array(
	1 => __('Open', 'grid'),
	2 => __('Close', 'grid'),
	3 => __('Activate', 'grid'),
	4 => __('InActivate', 'grid'),
	5 => __('Switch Queue', 'grid')
);

$queue_attribs = array(
	Q_ATTRIB_EXCLUSIVE               => __('Queue accepts jobs which request exclusive execution.', 'grid'),
	Q_ATTRIB_DEFAULT                 => __('Queue is a default LSF queue.', 'grid'),
	Q_ATTRIB_FAIRSHARE               => __('Queue uses the FAIRSHARE scheduling policy. The user shares are given in userShares.', 'grid'),
	Q_ATTRIB_PREEMPTIVE              => __('Queue uses the PREEMPTIVE scheduling policy.', 'grid'),
	Q_ATTRIB_NQS                     => __('NQS forwarding Queue. The target NQS queues are given in nqsQueues.  For NQS forward queues, the hostList, procJobLimit, windows, mig and windowsD fields are meaningless.', 'grid'),
	Q_ATTRIB_RECEIVE                 => __('Queue can receive jobs from other clusters.', 'grid'),
	Q_ATTRIB_PREEMPTABLE             => __('Queue uses a preemptable scheduling policy.', 'grid'),
	Q_ATTRIB_BACKFILL                => __('Queue uses a backfilling policy.', 'grid'),
	Q_ATTRIB_HOST_PREFER             => __('Queue uses a host preference policy.', 'grid'),
	Q_ATTRIB_NONPREEMPTIVE           => __('Queue can\'t preempt any other another queue.', 'grid'),
	Q_ATTRIB_NONPREEMPTABLE          => __('Queue can\'t be preempted from any queue.', 'grid'),
	Q_ATTRIB_NO_INTERACTIVE          => __('Queue does not accept batch interactive jobs.', 'grid'),
	Q_ATTRIB_ONLY_INTERACTIVE        => __('Queue only accepts batch interactive jobs.', 'grid'),
	Q_ATTRIB_NO_HOST_TYPE            => __('No host type related resource name specified in resource requirement.', 'grid'),
	Q_ATTRIB_IGNORE_DEADLINE         => __('Queue disables deadline constrained resource scheduling.', 'grid'),
	Q_ATTRIB_CHKPNT                  => __('Jobs may run as chkpntable in this queue.', 'grid'),
	Q_ATTRIB_RERUNNABLE              => __('Jobs may run as rerunnable in this queue.', 'grid'),
	Q_ATTRIB_EXCL_RMTJOB             => __('Excluding remote jobs when local jobs are present in the queue.', 'grid'),
	Q_ATTRIB_MC_FAST_SCHEDULE        => __('Turn on a MultiCluster fast scheduling policy.', 'grid'),
	Q_ATTRIB_ENQUE_INTERACTIVE_AHEAD => __('Push interactive jobs in front of other jobs in queue.', 'grid'),
	Q_MC_FLAG                        => __('Flags used by MultiCluster.', 'grid'),
	Q_ATTRIB_LEASE_LOCAL             => __('Lease and local.', 'grid'),
	Q_ATTRIB_LEASE_ONLY              => __('Lease only; no local.', 'grid'),
	Q_ATTRIB_RMT_BATCH_LOCAL         => __('Remote batch and local.', 'grid'),
	Q_ATTRIB_RMT_BATCH_ONLY          => __('Remote batch only.', 'grid'),
	Q_ATTRIB_RESOURCE_RESERVE        => __('Memory reservation.', 'grid'),
	Q_ATTRIB_FS_DISPATCH_ORDER_QUEUE => __('Cross-queue Fairshare queue.', 'grid'),
	Q_ATTRIB_BATCH                   => __('Batch queue/partition', 'grid'),
	Q_ATTRIB_ONLINE                  => __('Online partition', 'grid'),
	Q_ATTRIB_INTERRUPTIBLE_BACKFILL  => __('Interruptible backfill queue', 'grid'),
	Q_ATTRIB_APS                     => __('Absolute Priority scheduling (APS) queue.', 'grid'),
	Q_ATTRIB_NO_HIGHER_RESERVE       => __('No queue with RESOURCE_RESERVE or SLOT_RESERVE has higher priority than this queue.', 'grid'),
	Q_ATTRIB_NO_HOST_VALID           => __('No host valid in this queue.', 'grid')
);

$charts = array(
	'run_jobs'   => array('name' => __('Run Jobs', 'grid')),
	'run_slots'  => array('name' => __('Run Slots', 'grid')),
	'pend_jobs'  => array('name' => __('Pend Jobs', 'grid')),
	'pend_slots' => array('name' => __('Pend Slots', 'grid')),
	'started'    => array('name' => __('Started Slots', 'grid')),
	'times'      => array('name' => __('Run Time', 'grid')),
	'priority'   => array('name' => __('Priority', 'grid')),
	'share'      => array('name' => __('Share', 'grid'))
);

load_current_session_value('report', 'sess_gqv_report', 'jobs');

$title = __('IBM Spectrum LSF RTM - Queue Management', 'grid');

set_default_action();

if (!isset_request_var('tab')) {
	set_request_var('tab', 'general');
}

$grid_host_control_actions = array(
	1 => __('Open', 'grid'),
	2 => __('Close', 'grid')
);

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();

switch(get_request_var('action')) {
case 'actions':
	validate_bqueues_request_vars();
	form_action_queue();
	break;
case 'ajax_rtm_users':
	validate_bqueues_request_vars();
	$sql_where = '';
	if (get_request_var('clusterid') > 0) {
		$sql_where = 'clusterid = ' . get_request_var('clusterid');
	}
	rtm_autocomplete_ajax('grid_bqueues.php', 'job_user', $sql_where);
	break;
case 'save':
	validate_bqueues_request_vars();
	if (isset_request_var('save_queue_actions')) {
		form_action_queue();
	} else {
		form_action_host();
	}

	break;
case 'get_fairshare_tree':
	get_fairshare_tree_data();
	break;
case 'get_tree_data':
case 'get_tree_data2':
	validate_tree_variables();

	//show fairshare table
	show_faishare_tables();

	//show fairshare fusioncharts
	draw_fairshare_div();
	break;
case 'ajaxsave':
	grid_bqueues_ajax_save();
	break;
case 'ajaxsearch':
	grid_bqueues_ajax_search();
	break;
case 'ajaxcharts':
	fetch_rrd_data();
	break;
case 'ajaxheight':
	get_chart_height();
	break;
case 'viewqueue':
	$title = __esc('IBM Spectrum LSF RTM - Queue Details for \'%s\'', get_request_var('queue'), 'grid');

	/* ================= input validation ================= */
	get_filter_request_var('clusterid');
	/* ==================================================== */

	$clusterid = get_request_var('clusterid');
	$queue = db_fetch_row_prepared('SELECT *
		FROM grid_queues
		WHERE queuename = ?
		AND clusterid= ?',
		array(get_request_var('queue'), get_request_var('clusterid')));

	//when open tab graphs, set auto page refresh as cacti polling cycle, default value is 5 min
	if (get_request_var('tab') == 'graphs') {
		set_request_var('refresh', read_config_option('poller_interval'));
	}

	general_header();

	grid_queue_tabs($queue);

	switch(get_request_var('tab')) {
	case(''):
	case('general'):
		validate_bqueues_request_vars();
		grid_view_queue_detail($queue);
		break;
	case('share'):
		grid_view_queue_fairshare($queue);
		break;
	case('users'):
		validate_bqueues_request_vars();
		grid_view_queue_users($queue);
		break;
	case('hosts'):
		grid_view_queue_hosts($queue);
		break;
	case('jobs'):
		grid_view_queue_jobs($queue);
		break;
	case('graphs'):
		grid_view_queue_graphs($queue);
		break;
	}

	bottom_footer();

	break;
default:
	validate_bqueues_request_vars();
	$title = __('IBM Spectrum LSF RTM - Queue Batch Statistics', 'grid');

	grid_view_queues();

	bottom_footer();

	break;
}

function grid_bqueues_ajax_save() {
	global $views, $charts;

	// Filter Settings
	$settings =
		'filter='   . get_request_var('filter')   . '|' .
		'show='     . get_request_var('show')     . '|' .
		'timespan=' . get_request_var('timespan') . '|' .
		'unused='   . get_request_var('unused')   . '|' .
		'rows='     . get_request_var('rows')     . '|' .
		'refresh='  . get_request_var('refresh')  . '|' ;

	foreach($charts as $id => $chart) {
		if (isset_request_var($id)) {
			$settings .= "$id=" . get_request_var($id) . '|';
		}
	}

	$settings .= 'corder=' . trim(get_request_var('corder'));

	set_grid_config_option('grid_bqueues_fairshare', $settings);
}

function grid_bqueues_set_minimum_page_refresh() {
	global $config, $refresh;

	$minimum = read_config_option('grid_minimum_refresh_interval');

	if (isset_request_var('refresh')) {
		if (get_request_var('refresh') < $minimum) {
			set_request_var('refresh', $minimum);
		}

		/* automatically reload this page */
		$refresh['seconds'] = get_request_var('refresh');
		$refresh['page'] = $_SERVER['REQUEST_URI'];
	}
}

function get_chart_height() {
	$clusterid = strlen(get_request_var('clusterid'))? (get_request_var('clusterid')) : '';
	$queue = strlen(get_request_var('queue'))? (get_request_var('queue')) : '';
	$shareacctpath = strlen(get_request_var('shareacctpath'))? (get_request_var('shareacctpath')) : '';

	$sql_where = '';
	$sql_params = array();

	if (get_request_var('unused') == 'true') {
		$sql_where = '';
	} else {
		$sql_where = ' AND (started>0 OR reserved>0 OR run_jobs>0 Or pend_jobs>0) ';
	}

	if (isset_request_var('filter')) {
		$sql_where .= " AND gqs.user_or_group LIKE ? ";
		$sql_params[] = '%'. htmlspecialchars(get_request_var('filter')) . '%';
	}

	$group_count = db_fetch_cell_prepared("SELECT count(*) AS gcount
		FROM grid_queues_shares AS gqs
		INNER JOIN grid_users_or_groups AS gug
		ON gqs.user_or_group=gug.user_or_group
		AND gqs.clusterid=gug.clusterid
		AND gug.type='G'
		WHERE gqs.clusterid=?
		AND queue=?
		AND shareAcctPath=?
		$sql_where;", array_merge(array($clusterid, $queue, $shareacctpath), $sql_params));

	//get new fusionchart height given by (6 groups for 100px heights)
	$fh = 210;
	if ($group_count > 6) {
		$fh += ($group_count/6 - 1) * 100;
	}

	print $fh;
}

function fetch_rrd_data() {
	$clusterid = strlen(get_request_var('clusterid'))? (get_request_var('clusterid')) : '';
	$queue = strlen(get_request_var('queue'))? (get_request_var('queue')) : '';
	$shareacctpath = strlen(get_request_var('shareacctpath'))? (get_request_var('shareacctpath')) : '';
	$metric = strlen(get_request_var('metric'))? (get_request_var('metric')) : '';

	$timespan = strlen(get_request_var('timespan'))? (get_request_var('timespan')) : 14400;

	$start_time = time() -  $timespan;
	$end_time = time();

	$sql_where = '';
	$sql_params = array();

	if (get_request_var('unused') == 'true') {
		$sql_where = '';
	} else {
		$sql_where = ' AND (started>0 OR reserved>0 OR run_jobs>0 Or pend_jobs>0) ';
	}

	if (isset_request_var('filter')) {
		$sql_where .= " AND gqs.user_or_group LIKE ? ";
		$sql_params[] = '%'. htmlspecialchars(get_request_var('filter')) . '%';
	}

	//get max run time under current shareAcctPath for determining y axis value unit of run time
	$max_run_time = db_fetch_cell_prepared("SELECT max(run_time) AS max_run_time
		FROM grid_queues_shares AS gqs
		WHERE clusterid=?
		AND queue=?
		AND shareAcctPath=?
		$sql_where", array_merge(array($clusterid, $queue, $shareacctpath), $sql_params));

	$max_run_time_disp = display_job_time($max_run_time, 2, false);

	if (strpos($max_run_time_disp, 'Days') !== false) {
		$run_time_unit = 'Days';
		$run_time_base = 86400;
	} elseif  (strpos($max_run_time_disp, 'Hours') !== false) {
		$run_time_unit = 'Hours';
		$run_time_base = 3600;
	} elseif  (strpos($max_run_time_disp, 'Minutes') !== false) {
		$run_time_unit = 'Minutes';
		$run_time_base = 60;
	} elseif  (strpos($max_run_time_disp, 'Seconds') !== false) {
		$run_time_unit = 'Seconds';
		$run_time_base = 1;
	} else {
		$run_time_unit = 'Seconds';
		$run_time_base = 1;
	}

	$snmp_query_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='cf395279d717d8a77e45d18dfd3af2bd'");
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");
	$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE host_template_id=? AND clusterid =?", array($host_template_id, $clusterid));

	$y_axis_title = '';
	switch($metric) {
		case 'priority':
			$data_template_hash = 'd00c0978291b6426834d9a6abe08e79a';
			break;
		case 'share':
			$data_template_hash = 'ea216a045076aea9fd0fb7da2ce00b79';
			break;
		case 'started':
			$data_template_hash = '2e652e437d8dc60d4b11487c978198b8';
			$y_axis_title = 'Slots';
			break;
		case 'times':
			$data_template_hash = 'd69f56063064d92afa8156c8403c7d05';
			$y_axis_title = $run_time_unit;
			break;
		case 'run_jobs':
			$data_template_hash = '8c93d37684bf1b9f491ea351cca7634e';
			$y_axis_title = 'Jobs';
			break;
		case 'run_slots':
			$data_template_hash = '0fb2bb089a1deb51859ed37c3a48c8fd';
			$y_axis_title = 'Slots';
			break;
		case 'pend_jobs':
			$data_template_hash = '83414a172bff88e1469d23f35bdea10f';
			$y_axis_title = 'Jobs';
			break;
		case 'pend_slots':
			$data_template_hash = '7a853b308281f9f26764cb59960875ef';
			$y_axis_title = 'Slots';
			break;
	}
	$data_template_id = db_fetch_cell_prepared("SELECT id FROM data_template WHERE hash=?", array($data_template_hash));

	$snmp_indexs = db_fetch_assoc_prepared("SELECT concat(shareAcctPath, '/', gqs.user_or_group) AS share_index, gqs.user_or_group
		FROM grid_queues_shares AS gqs
		INNER JOIN grid_users_or_groups AS gug
		ON gqs.user_or_group=gug.user_or_group
		AND gqs.clusterid=gug.clusterid
		AND gug.type='G'
		WHERE gqs.clusterid=?
		AND queue=?
		AND shareAcctPath=?
		$sql_where
		ORDER BY user_or_group",
		array_merge(array($clusterid, $queue, $shareacctpath), $sql_params));

	switch($timespan) {
		case('14400'):
			$timespan_titile = '4 Hours';
			$rra_step = 300;  //daily 5minute rra
			break;
		case('28800'):
			$timespan_titile = '8 Hours';
			$rra_step = 300;  //daily 5minute rra
			break;
		case('43200'):
			$timespan_titile = '12 Hours';
			$rra_step = 300;  //daily 5minute rra
			break;
		case('86400'):
			$timespan_titile = '1 Day';
			$rra_step = 300;  //daily 5minute rra
			break;
		case('259200'):
			$timespan_titile = '3 Days';
			$rra_step = 1800;  //weekly 30 minutes rra
			break;
		case('604800'):
			$timespan_titile = '1 Week';
			$rra_step = 1800;  //weekly 30 minutes rra
			break;
	}

	$total_labels = $timespan / $rra_step;

	//show up to 10 lables on x axis
	if ($total_labels <= 10) {
		$labelsteps = 1;
	} else {
		$labelsteps = ceil($total_labels/10);
	}

	$catLabels  = '';
	$dataSeries = '';

	if (!cacti_sizeof($snmp_indexs)) return;

	$i = 0;  //user group count
	foreach($snmp_indexs as $snmp_index) {
		$j = 0; // graph rrd data count
		$dataSeries1 = '';

		$local_data_id = db_fetch_cell_prepared("SELECT id
			FROM data_local
			WHERE data_template_id = ?
			AND host_id = ?
			AND snmp_query_id = ?
			AND snmp_index = ?",
			array($data_template_id, $host_id, $snmp_query_id, "$queue|" .  $snmp_index['share_index']));

		if (!empty($local_data_id)) {
			$rrd_fetch_result = rrdtool_function_fetch($local_data_id, $start_time, $end_time, 0, true);
			if (cacti_sizeof($rrd_fetch_result)) {

				//sanitize the fetched data from rrd file
				$rrd_data = array();
				for ($k = 0; $k <= $total_labels -1; $k++ ) {
					if (isset($rrd_fetch_result['values'][0]) && is_array($rrd_fetch_result['values'][0])) {
						$rrd_value = current($rrd_fetch_result['values'][0]);
						if (!isset($rrd_value)) {
							$rrd_data[] = 0;
						} else if (is_numeric($rrd_value)) {
							$rrd_data[] = $rrd_value;
						} else if (is_numeric(end($rrd_data))) {
							$rrd_data[] = end($rrd_data);
						} else {
							$rrd_data[] = 0;
						}
						next($rrd_fetch_result['values'][0]);
					} else {
						$rrd_data[] = 0;
					}
				}
				//var_dump($rrd_data);

				$start_time2 = ceil($start_time/$rra_step)*$rra_step;

				foreach($rrd_data as $value) {
					if ($i == 0) {
						$catLabels .= ($j == 0 ? "<categories>\n":"") . "<category label='" . date("M-d",($start_time2+$j*$rra_step)) . "&#13;&#10;" . date("H:i",($start_time2+$j*$rra_step)). "' />\n";
					}

					if (!isset($rrd_fetch_result['data_source_names'][0])) {
						$dataSeries1 .= ($j == 0 ? "<dataset seriesName='" . $snmp_index['user_or_group'] . "'>\n":"") . "<set value='" . $value . "' />\n";
					} else {
						switch($rrd_fetch_result['data_source_names'][0]) {
							case 'priority':
							case 'share':
							case 'started':
							case 'run_jobs':
							case 'run_slots':
							case 'pend_jobs':
							case 'pend_slots':
								$dataSeries1 .= ($j == 0 ? "<dataset seriesName='" . $snmp_index['user_or_group'] . "'>\n":"") . "<set value='" . $value . "' />\n";
								break;
							case 'run_time':
								$dataSeries1 .= ($j == 0 ? "<dataset seriesName='" . $snmp_index['user_or_group'] . "'>\n":"") . "<set value='" . $value/$run_time_base . "' />\n";
								break;
						}
					}
					$j++;
				}
			}
			$dataSeries1 .= (strlen($dataSeries1) ? "</dataset>\n":"");
			$dataSeries .= $dataSeries1;
		}
		$i++;
	}

	$catLabels   .= (strlen($catLabels)   ? "</categories>\n":"");

	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	$chartXML = "<chart caption='" . get_graph_name($metric, $queue, $clusterid) ." (Last $timespan_titile)' " .
		"showValues='0' legendPosition='RIGHT' labelDisplay='NONE' " .
		"plotgradientcolor='' formatnumberscale='0' showplotborder='0'  " .
		"palettecolors='#FD9927,#FECE2F,#9DCD3F,#CECD42,#009999,#64D3D1,#9400D3,#b3b3ff,#ff3300,#660033,#9E8655,#9FA4EE,#A150AA,#AAABA1,#6DC8FE,#562B29,#157419,#FFC3C0,#00BD27,#000000' canvaspadding='0' " .
		"showalternatehgridcolor='0' " .
		"showcanvasborder='0' legendborderalpha='0' legendshadow='0'  " .
		"interactivelegend='0' showpercentvalues='0' showsum='0' yAxisName='$y_axis_title' " .
		"labelStep='$labelsteps' $fusion_theme exportEnabled='1'>";

	$chartXML .= $catLabels . $dataSeries . "</chart>\n";

	print $chartXML;

}

function get_clustername($clusterid) {
	return db_fetch_cell_prepared('SELECT clustername FROM grid_clusters WHERE clusterid=?', array($clusterid));
}

function get_graph_name($metric, $queue, $clusterid) {
	switch($metric) {
		case 'times':
			$metric = 'run time';
			break;
		case 'started':
			$metric = 'started slots';
			break;
		case 'run_jobs':
			$metric = 'run jobs';
			break;
		case 'run_slots':
			$metric = 'run slots';
			break;
		case 'pend_jobs':
			$metric = 'pend jobs';
			break;
		case 'pend_slots':
			$metric = 'pend slots';
			break;
	}

	$prefix = strtoupper($metric) . " in Queue &apos;$queue&apos;" ;

	if ( !empty($clusterid) && is_numeric($clusterid) ) {
		$prefix .= "&lt;br&gt;in Cluster &apos;" .get_clustername($clusterid) ."&apos;";
	}

	return $prefix;
}


function draw_fairshare_div() {
	if (get_request_var('show') != 1 ) return;

	$children = check_group_children(get_request_var('clusterid'), get_request_var('queue'), get_request_var('shareAcctPath'));
	if ($children == 0) return;

	print "<div id ='jstree_right_area_charts' class ='right_area'>";

	html_start_box(__('Charts', 'grid'), '100%', '', '3', 'left', '');

	print "<tr><td><table width='100%' align='center'><tr><td>
		<div id='pqgraphs' class='graphs' align='center' style='padding:4px;'>
			<div class='graphs' id='div_graphs'>
				<div tabindex=0 class='graphs' id='template' style='padding:2px;'></div>
			</div>
		</div>
		</td></tr></table></td></tr>";

	html_end_box();
	print '</div>';
}

function show_faishare_tables() {
	global $config;

	$sql_params = array();
	$sql_order = get_order_string();

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	if (get_request_var('unused') == 'true') {
		$sql_where = '';
	} else {
		$sql_where = 'AND (gqs.started>0 OR gqs.reserved>0 OR gqs.run_jobs>0 Or gqs.pend_jobs>0)';
	}

	if (isset_request_var('filter')) {
		$sql_where .= " AND gqs.user_or_group LIKE ?";
		$sql_params[] = '%'. htmlspecialchars(get_request_var('filter')) . '%';
	}

	$data = db_fetch_assoc_prepared("SELECT gqs.*, gug.type
		FROM grid_queues_shares AS gqs
		LEFT JOIN grid_users_or_groups AS gug
		ON gqs.clusterid = gug.clusterid
		AND gqs.user_or_group= gug.user_or_group
		WHERE gqs.clusterid=?
		AND gqs.queue=?
		AND gqs.shareAcctPath=?
		$sql_where $sql_order $sql_limit",
		array_merge(array(get_request_var('clusterid'), get_request_var('queue'), get_request_var('shareAcctPath')), $sql_params));

	//bug fix 145275: if there is no data fetched, reset page number to 1 and fetch data again
	//It could happen if new selected root user group fetches different results, so that the previous page number could on longer exist.
	if (!cacti_sizeof($data)) {
		set_request_var('page', 1);
		load_current_session_value('page', 'sess_gqt_current_page', '1');

		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
		$data = db_fetch_assoc_prepared("SELECT gqs.*, gug.type
			FROM grid_queues_shares AS gqs
			LEFT JOIN grid_users_or_groups AS gug
			ON gqs.clusterid = gug.clusterid
			AND gqs.user_or_group= gug.user_or_group
			WHERE gqs.clusterid=?
			AND gqs.queue=?
			AND gqs.shareAcctPath=?
			$sql_where $sql_order $sql_limit",
			array_merge(array(get_request_var('clusterid'), get_request_var('queue'), get_request_var('shareAcctPath')), $sql_params));
	}

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM grid_queues_shares AS gqs
		WHERE clusterid=?
		AND queue=?
		AND shareAcctPath=?
		$sql_where",
		array_merge(array(get_request_var('clusterid'), get_request_var('queue'), get_request_var('shareAcctPath')), $sql_params));

	$display_text = array(
		'nosort' => array(
			'display' => ''
		),
		'user_or_group' => array(
			'display' => __('User/Group', 'grid'),
			'align' => 'left',
			'sort' => 'ASC'
		),
		'shares' => array(
			'display' => __('Shares', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'priority' => array(
			'display' => __('Priority', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'started' => array(
			'display' => __('Started Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'reserved' => array(
			'display' => __('Reserved Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'cpu_time' => array(
			'display' => __('CPU Time', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'run_time' => array(
			'display' => __('Run Time', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'run_jobs' => array(
			'display' => __('Run Jobs', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'run_slots' => array(
			'display' => __('Run Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pend_jobs' => array(
			'display' => __('Pend Jobs', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'pend_slots' => array(
			'display' => __('Pend Slots', 'grid'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	/* generate page list */
	$nav = html_nav_bar('grid_bqueues.php?action=viewqueue&tab=share', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Queues', 'grid'), 'page', 'main');

	if (get_request_var('action') == 'get_tree_data') {
		print "<div id='my_table_wrap'>";
	}
	html_start_box('', '100%', '', '1', 'center', '');

	print $nav;

	$strURL = 'grid_bqueues.php?header=false&action=get_tree_data2&queue='. get_request_var('queue') . '&clusterid=' . get_request_var('clusterid');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, $strURL,'my_table_wrap');

	$i = 0;

	if (!empty($data)) {
		foreach($data as $d) {
			form_alternate_row();

			if ($d['type'] == 'G' || empty($d['type'])) {
				form_selectable_cell(show_fairshare_graphs($d['clusterid'], $d['queue'], $d['shareAcctPath'] . '/' . $d['user_or_group']), $i);
			} else {
				form_selectable_cell('', $i);
			}

			if ($d['type'] == 'G') {
				if (!check_group_children($d['clusterid'], $d['queue'], $d['shareAcctPath'] . '/' . $d['user_or_group'])) {
					form_selectable_cell($d['user_or_group'], $i);
				} else {
					$url = $config['url_path'] . 'plugins/grid/grid_bqueues.php' .
						'?action=viewqueue' .
						'&tab=share' .
						'&clusterid=' . get_request_var('clusterid') .
						'&queue=' . get_request_var('queue') .
						'&ugroup=' . $d['user_or_group'];

					$contents = "<a class='col_user_group pic' href='$url'>". $d['user_or_group']. "</a>";
					form_selectable_cell($contents, $i, '', '', __('Expand the user group in Fairshare Tree', 'grid'));
				}
			} else {
				form_selectable_cell($d['user_or_group'], $i);
			}

			form_selectable_cell(number_format_i18n($d['shares']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['priority'], 2), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['started']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['reserved']), $i, '', 'right');

			form_selectable_cell(display_job_time($d['cpu_time'], 2, true), $i, '', 'right');
			form_selectable_cell(display_job_time($d['run_time'], 2, true), $i, '', 'right');

			form_selectable_cell(number_format_i18n($d['run_jobs']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['run_slots']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['pend_jobs']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($d['pend_slots']), $i, '', 'right');

			form_end_row();
		}

		html_end_box();
		print $nav;
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Queue Records Found', 'grid') . '</em></td></tr>';
		html_end_box();
	}

	$children = check_group_children(get_request_var('clusterid'), get_request_var('queue'), get_request_var('shareAcctPath'));

	if (get_request_var('show') == 1) {
		if ($children == 0) {
			print "<input type='hidden' id='nochild' value='1'>";    //don't show charts
		} else {
			print "<input type='hidden' id='nochild' value='0'>";	//show charts
		}
	} else {
		print "<input type='hidden' id='nochild' value='1'>";    	//don't show charts
	}
	if (get_request_var('action') == 'get_tree_data') {
		print "</div>";
	}
}

function show_fairshare_graphs($clusterid, $queue, $shareAcctPath) {
	global $config;

	$cacti_host = db_fetch_cell_prepared("SELECT cacti_host
		FROM grid_clusters
		WHERE clusterid=?", array($clusterid));

	if (isset($cacti_host)) {
		$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
			FROM host_snmp_cache
			INNER JOIN graph_local
			ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
			AND (host_snmp_cache.host_id=graph_local.host_id)
			WHERE (graph_local.host_id=?)
			AND (graph_local.snmp_index=?)
			AND (host_snmp_cache.field_name ='gridQtree')", array($cacti_host, "$queue|$shareAcctPath"));
		if (!empty($local_graph_ids)) {
			$graph_select = "action=preview&page=1&graph_template_id=-1&rfilter=&style=selective&host_id=-1&graph_add=";
			foreach($local_graph_ids as $graph) {
				$graph_select .= $graph["id"] . "%2C";
			}
		} else {
			unset($graph_select);
		}
	} else {
		unset($graph_select);
	}

	if (isset($graph_select)) {
		return "<a class='pic' href='" . html_escape($config['url_path'] . "graph_view.php?" . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' title='" . __esc('View Fairshare Graphs for the user group', 'grid') . "'></a>";
	}
}

function check_group_children($clusterid, $queue, $shareAcctPath) {
	$children = db_fetch_cell_prepared("SELECT count(*)
		FROM grid_queues_shares AS gqs
		INNER JOIN grid_users_or_groups AS gug
		ON gqs.user_or_group=gug.user_or_group
		AND gqs.clusterid=gug.clusterid
		AND gug.type='G'
		WHERE shareAcctPath LIKE ?
		AND queue=?
		AND gqs.clusterid=?", array("$shareAcctPath%", $queue, $clusterid));

	return $children;
}

function validate_tree_variables() {
	global $charts;

	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'filter', ''),
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'priority',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'refresh', read_grid_config_option('refresh_interval')),
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'queue', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'shareAcctPath' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'id' => array(
			'filter' => FILTER_UNSAFE_RAW,
			'default' => '#',
			//'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'corder' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'corder', 'run_jobs pend_jobs'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'timespan' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'timespan', '14400'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'unused', ''),
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	validate_store_request_vars($filters, 'sess_gqt');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'clusterid', read_grid_config_option('default_grid'))
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */

	// Multiselect variables for Charts
	if (cacti_sizeof($charts)) {
		foreach($charts as $id => $chart) {
			if ($id =='run_jobs' || $id =='pend_jobs') {
				load_current_session_value($id, 'sess_gqt_chart_' . $id, get_user_page_setting('grid_bqueues_fairshare', $id, 'true'));
			} else {
				load_current_session_value($id, 'sess_gqt_chart_' . $id, get_user_page_setting('grid_bqueues_fairshare', $id, 'false'));
			}
		}
	}
	//print "<input type='hidden' id='corder' value='" . get_request_var('corder') . "'>";
}

function treeFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $charts;

	?>
	<tr class='odd'>
		<td>
			<form id='tree'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value="<?php print get_request_var('filter');?>">
					</td>
					<td>
						<?php print __('Rows');?>
					</td>
					<td>
						<select id='rows'>
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
						<?php print __('Refresh');?>
					</td>
					<td>
						<select id='refresh'>
						<?php
						$max_refresh = read_config_option("grid_minimum_refresh_interval");
						foreach($grid_refresh_interval as $key => $value) {
							if ($key >= $max_refresh) {
								print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print " selected"; } print ">" . $value . "</option>";
							}
						}
						?>
						</select>
					</td>
					<td>
						<input id='unused' type='checkbox' <?php if ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on')) print 'checked';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Users/Groups');?></label>
					</td>
					<td style='white-space:nowrap;'>
						<input type='hidden' id='page' value='1'>
						<input type='hidden' id='action' value='viewqueue'>
						<input type='hidden' id='tab' value='share'>
						<input type='hidden' id='queue' value='<?php print html_escape_request_var('queue');?>'>
						<input type='hidden' id='clusterid' value='<?php print html_escape_request_var('clusterid');?>'>

						&nbsp;<input type='button' id='go' value='Go' title='Search'>
						&nbsp;<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
						&nbsp;<input type='button' id='save' value='Save' title='Save all Filter Settings' onClick='filterSave();'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Display');?>
					</td>
					<td>
						<select id='show'>
							<option value='1' <?php print (get_request_var('show') == '1' ? ' selected':'');?>>Table and Graphs</option>
							<option value='3' <?php print (get_request_var('show') == '3' ? ' selected':'');?>>Table</option>
						</select>
					</td>
					<div id = 'chart_filters'>
					<td>
						<?php print __('Charts');?>
					</td>
					<td>
						<select id='charts' multiple='multiple' onChange='chartChange()'>
							<?php foreach($charts as $id => $chart) print "<option value='$id' " . (isset_request_var($id)  && get_request_var($id) == 'true' ? ' selected':'') . ">" . $chart['name'] . "</option>\n"; ?>
						</select>
					</td>
					<td>
						<?php print __('Chart TimeSpan');?>
					</td>
					<td>
						<select id='timespan'>
							<option value='14400' <?php if (get_request_var('timespan') == '14400') print ' selected';?>>4 Hours</option>
							<option value='28800' <?php if (get_request_var('timespan') == '28800') print ' selected';?>>8 Hours</option>
							<option value='43200' <?php if (get_request_var('timespan') == '43200') print ' selected';?>>12 Hours</option>
							<option value='86400' <?php if (get_request_var('timespan') == '86400') print ' selected';?>>1 Day</option>
							<option value='259200' <?php if (get_request_var('timespan') == '259200') print ' selected';?>>3 Days</option>
							<option value='604800' <?php if (get_request_var('timespan') == '604800') print ' selected';?>>1 Week</option>
						</select>
					</td>
					</div>
				<tr>
			</table>
		</td>
	</tr>
	<?php
}

function form_action_queue() {
	global $config, $grid_queue_control_actions;

	$count_ok = 0;
	$count_fail = 0;
	$action_level = 'queue';
	$message = '';

	if (isset_request_var('command') && get_request_var('command') == 'goback') {
		header('Location: grid_bqueues.php');
		exit;
	}

	if (isset_request_var('queue_array'))
		$queue_array = get_request_var('queue_array');
	else
		$queue_array = '';

	debug_log_clear('grid_admin');

	if (get_request_var('drp_action') != '5' && isset_request_var('selected_items') && read_config_option('grid_management_clusters') == 'on') {
		form_input_validate(trim(get_request_var('message')), 'message', '', false, 'error_mandatory_input_field');
	}
	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items') && !isset($_SESSION['sess_error_fields']['message'])) {

		if (isset_request_var('message') && trim(get_request_var('message')) != '') {
			$message = "'" . trim(get_request_var('message')) . "' - by RTM User '" . get_username($_SESSION['sess_user_id']) . "'";
		} else {
			$message = '';
		}

		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);

		if ($selected_items_whole != false) {
			if (get_request_var('drp_action') == '1' || get_request_var('drp_action') == '2' || get_request_var('drp_action') == '3' || get_request_var('drp_action') == '4') {
				$json_return_format = sorting_json_format($selected_items_whole, $message, $action_level);
			} else {
				$json_return_format = sorting_json_format($selected_items_whole, $queue_array);
			}
		}

		switch(get_request_var('drp_action')) {
			case '1':$queue_action = 'open'; /* Open Queue  */
				break;
			case '2':$queue_action = 'close'; /* Close Queue */
				break;
			case '3':$queue_action = 'activate'; /* Activate Queue */
				break;
			case '4':$queue_action = 'inactivate'; /* InActivate Queue */
				break;
			case '5':$queue_action = 'switch'; /* Switch all Jobs from Selected Queue To Another Queue*/
				break;
		}

		$advocate_key = session_auth();

		$json_queue_info = array (
			'key' => $advocate_key,
			'action' => $queue_action,
			'target' => $json_return_format,
		);

		$output = json_encode($json_queue_info);
//		print $output;

		$curl_output =  exec_curl($action_level, $output); //pass to advocate for processing

		if ($curl_output['http_code'] == 400)
			raise_message(134);
		else if ($curl_output['http_code'] == 500)
			raise_message(135);
		else{
			if ($curl_output['http_code'] == 200) {
				$log_action = $grid_queue_control_actions[get_request_var('drp_action')];
				$json_output = json_decode($output);
				$username_log = get_username($_SESSION['sess_user_id']);
				foreach ($json_output->target as $target) {
					$action_message = get_request_var('message');
					cacti_log("Queue '{$target->name}', {$log_action} by '{$username_log}', comment: '{$action_message}'.", false, 'LSFCONTROL');
				}
			}
			else
				raise_message(136);
		}

		$content_response = $curl_output['content']; //return response from advocate in json format

		//print_r($content_response);
		$json_decode_content_response = json_decode($content_response,true);

		$rsp_content = $json_decode_content_response['rsp'];

		if(is_array($rsp_content) && count($rsp_content) >0){
		for ($k=0;$k<count($rsp_content);$k++) {
			$key_sort[$k] = $rsp_content[$k]['clusterid'];
		}

		asort($key_sort);

		$output_message='';
		foreach( $key_sort as $key => $val) {
			foreach ($rsp_content as $key_rsp_content => $value) {
				if ($key_rsp_content == $key) {
					if ($value['status_code'] == 0) {
						$return_status = 'OK';
						$count_ok ++;
					}
					else{
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
		header('Location: grid_bqueues.php');
		exit;
	}

	$queue_list = '';
	$i = 0;

	if (isset_request_var('selected_items') && isset($_SESSION['sess_error_fields']['message'])) {
		$selected_items_whole = rtm_sanitize_unserialize_selected_items(get_request_var('selected_items_whole'), false);
		if ($selected_items_whole != false) {
		foreach ($selected_items_whole as $selected_item) {
			$queue_whole_array[$i] = $selected_item;
			$queue_details = explode(':',$selected_item);

			input_validate_input_number($queue_details[1]);

			$queue_list .= '<li>Queue: <strong>'. $queue_details[0] .'</strong> from Cluster Name: <strong>'. grid_get_clustername($queue_details[1]).'</strong></li>';
			$queue_array[$i] = $queue_details[1];
			$queue_array_queuename[$i] = $queue_details[0];

			$i++;
		}
		}
	} else {
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'chk_', '4') == 0) {
				$queue_whole_array[$i] = substr($key, 4);
				$queue_details = explode(':',substr($key, 4));

				/* ================= input validation ================= */
				input_validate_input_number($queue_details[1]);
				/* ================= input validation ================= */

				$queue_list .= '<li>Queue: <strong>'. $queue_details[0] .'</strong> from Cluster Name: <strong>'. grid_get_clustername($queue_details[1]).'</strong></li>';
				$queue_array[$i] = $queue_details[1];
				$queue_array_queuename[$i] = $queue_details[0];
				$i++;
			}
		}
	}

	general_header();

	form_start('grid_bqueues.php');

	if (get_request_var('drp_action') != 5) {
		html_start_box('<strong>' . $grid_queue_control_actions[get_request_var('drp_action')] . '</strong>', '60%', '', '3', 'center', '');
	}

	switch(get_request_var('drp_action')) {
		case '1':$action = 'open';
			break;
		case '2':$action = 'close';
			break;
		case '3':$action = 'activate';
			break;
		case '4':$action = 'inactivate';
			break;
	}

	if (!empty($queue_array)) {
		switch(get_request_var('drp_action')) {
			case '1':	// open
			case '2':	// close
			case '3':	// activate
			case '4':	// inactivate
				print " <tr><td colspan=2 class='textArea'>
					<p>Are you sure you want to $action the following queue(s)?</p>
					<p><ul>$queue_list</ul></p>
					</td></tr>";
				print "<tr>	<td>Comments that are appended to LSF with the -C option.";
				if (read_config_option("grid_management_clusters") != "on") {
					print "<br>Leave BLANK if no comment is required.";
				}
				print "<br>&lt;RTM&lt;<strong>" . get_username($_SESSION['sess_user_id']) . "</strong>&gt;&gt; will be appended after your comments.</td>";
				print "<td><input ";
				if (isset($_SESSION["sess_error_fields"]["message"])) {
					print "class='txtErrorTextBox'";
					unset($_SESSION["sess_error_fields"]["message"]);
				}
				print "type=text name='message' col=255 size=40 maxlength='512'></td></tr>";
				print "<tr><td colspan=2>
					<font color=red>NOTE:Please wait for the next polling cycle to see the changes on RTM after confirmation.</font>
					</td></tr>\n";
				break;
			case '5':	/* Switching all Jobs from one Queue to another queue*/
				print "<form action='grid_bqueues.php' method='post'>\n";
				$i = 0;
				foreach ($_POST as $key => $value) {
					if (strncmp($key, 'chk_', '4') == 0) {
						$queue_whole_array[$i] = substr($key, 4);
						$queue_details = explode(':',substr($key, 4));
						html_start_box("<strong>Switching Job(s) from " . $queue_details[0] . "</strong>", "60%", '', "3", "center", "");
						/* ================= input validation ================= */
						input_validate_input_number($queue_details[1]);
						/* ================= input validation ================= */

						$queue_result = db_fetch_assoc_prepared("SELECT queuename FROM grid_queues
								WHERE queuename!=? and clusterid =? ", array($queue_details[0], $queue_details[1]));

						print "<tr>
							<td>Switching Job(s)<br>Switching all jobs from the selected queue to another queue.</td><td>
							</td><td><select name='queue_array[]'>";
						foreach($queue_result as $result) {
							/*** create the options ***/
							print '<option value="'.$result['queuename'].'"';
							if ($result['queuename']=='normal') {
								 print ' selected';
							}
							print '>'. $result['queuename'] . '</option>'."\n";
						}
						print '</select></td></tr>';
						html_end_box(false);
						$i++;
					}
				}
				break;
		}
	}

	if (!isset($queue_array) || empty($queue_array)) {
		raise_message(40);
		header('Location: grid_bqueues.php?header=false');
		exit;
	} else {
		if (get_request_var('drp_action') == 5) {
			print "<table width=60% align='center'>";
			print "<tr>
				<td class='textArea'>
				<font color=red>NOTE:Please wait for the next polling cycle to see the changes on RTM after confirmation.</font>
				</td></tr>";
		}
		$save_html = "<input type='submit' value='Yes' alt='' align='absmiddle'>";
		$button_false = 'No';
	}

	print " <tr>
		<td colspan='2' align='right' bgcolor='#eaeaea'>
		<input type='hidden' name='action' value='actions'>
		<input type='hidden' name='command' value=''>
		<input type='hidden' name='selected_items' value='" . (isset($queue_array) ? serialize($queue_array) : '') . "'>
		<input type='hidden' name='selected_items_queuename' value='" . (isset($queue_array_queuename) ? serialize($queue_array_queuename) : '') . "'>
		<input type='hidden' name='selected_items_whole' value='" . (isset($queue_whole_array) ? serialize($queue_whole_array) : '') . "'>
		<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
		<input type='button' value='" . $button_false ."' alt='' onClick='this.form.command.value=\"goback\";this.form.submit();' align='absmiddle' border='0'>
		$save_html
		</td>
		</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function queue_attribs($qAttrib, $break = true) {
	$attrib = '';
	if ($break) {
		$break = '<br>';
	} else {
		$break = '';
	}

	if ($qAttrib & Q_ATTRIB_FAIRSHARE)   $attrib .= (strlen($attrib) ? ",$break ":'') . __('Fairshare', 'grid');
	if ($qAttrib & Q_ATTRIB_PREEMPTIVE)  $attrib .= (strlen($attrib) ? ",$break ":'') . __('Preemptive', 'grid');
	if ($qAttrib & Q_ATTRIB_PREEMPTABLE) $attrib .= (strlen($attrib) ? ",$break ":'') . __('Preemptable', 'grid');
	if ($qAttrib & Q_ATTRIB_BACKFILL)    $attrib .= (strlen($attrib) ? ",$break ":'') . __('Backfill', 'grid');
	if ($qAttrib & Q_ATTRIB_RERUNNABLE)  $attrib .= (strlen($attrib) ? ",$break ":'') . __('Rerunnable', 'grid');
	if ($qAttrib & Q_ATTRIB_CHKPNT)      $attrib .= (strlen($attrib) ? ",$break ":'') . __('Checkpointable', 'grid');
	if ($qAttrib & Q_ATTRIB_EXCLUSIVE)   $attrib .= (strlen($attrib) ? ",$break ":'') . __('Exclusive', 'grid');
	if ($qAttrib & Q_ATTRIB_APS)         $attrib .= (strlen($attrib) ? ",$break ":'') . __('APS', 'grid');
	if ($qAttrib & Q_MC_FLAG)            $attrib .= (strlen($attrib) ? ",$break ":'') . __('MC Send', 'grid');
	if ($qAttrib & Q_ATTRIB_RECEIVE)     $attrib .= (strlen($attrib) ? ",$break ":'') . __('MC Receive', 'grid');

	return $attrib;
}

function grid_view_get_queues_records(&$sql_where, $apply_limits = true, $rows = 30, &$sql_params = array()) {
	if (get_filter_request_var('clusterid') > '0') {
		$sql_where .= 'WHERE (gq.clusterid=?)';
		$sql_params[] = get_request_var('clusterid');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where == '' ? 'WHERE ':' AND ') . " (
			(queuename LIKE ?) OR
			(description LIKE ?) OR
			(status LIKE ?) OR
			(reason LIKE ?))";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('job_user') != -1) {
		$sql_where .= ($sql_where == '' ? 'WHERE ':' AND ') . ' user_or_group=?';
		$sql_params[] = get_request_var('job_user');
	}

	if (get_request_var('unused') == 'false' || get_request_var('unused') == '') {
		if (get_request_var('job_user') == -1) {
			$sql_where .= ($sql_where == '' ? 'WHERE ':' AND ') . ' gq.nojobs>0';
		} else {
			$sql_where .= ($sql_where == '' ? 'WHERE ':' AND ') . ' gqu.nojobs>0';
		}
	}

	// Hook for metadata
	$meta_where_piece = api_plugin_hook_function('grid_meta_param_where', 'grid_bqueues');
	if ($meta_where_piece != 'grid_bqueues' && strlen($meta_where_piece)) {
		$sql_where .= ($sql_where == '' ? 'WHERE ':' AND ') . ' (' . $meta_where_piece . ')';
	}

	$sql_order = get_order_string();
	if ($apply_limits) {
		$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	if (get_request_var('job_user') == -1) {
		$sql_query = "SELECT gq.*, gc.clustername
			FROM grid_queues AS gq
			INNER JOIN grid_clusters AS gc
			ON gq.clusterid=gc.clusterid
			$sql_where $sql_order
			$sql_limit";
	} else {
		$sql_query = "SELECT gq.queuename, gq.clusterid, gq.description, gq.priority, gq.nice,
			gq.status, gq.reason, gq.numslots, gq.maxjobs, gq.userJobLimit, gq.procJobLimit,
			gq.windows, gq.windowsD, gq.sharedSlots, gq.dedicatedSlots, gq.openSharedSlots,
			gq.openDedicatedSlots, gq.HostJobLimit, gqu.nojobs, gqu.pendjobs, gqu.runjobs, gqu.suspjobs,
			gq.qAttrib, gq.resReq, gq.sndJobsTo, gq.rcvJobsFrom, gq.hostJobLimit, gq.minProcLimit,
			gq.defProcLimit, gq.procLimit,
			'-' as avg_pend_time, '-' as max_pend_time, '-' as avg_psusp_time, '-' as max_psusp_time,
			'-' as avg_run_time, '-' as max_run_time, '-' as avg_ususp_time, '-' as max_ususp_time,
			'-' as avg_ssusp_time, '-' as max_ssusp_time, '-' as avg_unkwn_time, '-' as max_unkwn_time,
			'-' as hourly_started_jobs, '-' as hourly_done_jobs, '-' as hourly_exit_jobs,
			'-' as daily_started_jobs, '-' as daily_done_jobs, '-' as daily_exit_jobs,
			gqu.efficiency, '-' as avg_mem, '-' as max_mem, '-' as avg_swap, '-' as max_swap,
			'-' as total_cpu, gc.clustername
			FROM grid_queues AS gq
			INNER JOIN grid_queues_users_stats AS gqu
			ON gq.queuename=gqu.queue AND
			gq.clusterid=gqu.clusterid
			INNER JOIN grid_clusters AS gc
			ON gqu.clusterid=gc.clusterid
			$sql_where $sql_order
			$sql_limit";
	}
	//print $sql_query;

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_queue_display_array() {
	$display_text = array(
		'nosort' => array(
			'display' => __('Actions', 'grid')
		),
		'queuename' => array(
			'display' => __('Queue Name', 'grid'),
			'sort'    => 'ASC'
		),
		'clustername' => array(
			'display' => __('Cluster Name', 'grid'),
			'dbname'  => 'show_queue_clustername',
			'sort'    => 'ASC'
		)
	);

	// Hook for metadata columns
	$display_array_meta_piece = api_plugin_hook_function('grid_meta_column_header', 'grid_bqueues');
	if (is_array($display_array_meta_piece)) {
		$display_text = array_merge($display_text, $display_array_meta_piece);
	}

	$display_text += array(
		'description' => array(
			'display' => __('Description', 'grid'),
			'dbname'  => 'show_queue_description',
			'sort'    => 'ASC'
		),
		'nice' => array(
			'display' => __('Nice', 'grid'),
			'dbname'  => 'show_queue_nice',
			'sort'    => 'ASC'
		),
		'priority' => array(
			'display' => __('Priority', 'grid'),
			'dbname'  => 'show_queue_priority',
			'sort'    => 'DESC'
		),
		'status' => array(
			'display' => __('Status Reason', 'grid'),
			'sort'    => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Attribs', 'grid'),
			'dbname'  => 'show_queue_attribs',
			'sort'    => 'ASC'
		),
		'nosort2' => array(
			'display' => __('Run Window', 'grid'),
			'dbname'  => 'show_queue_run_window',
			'sort'    => 'ASC'
		),
		'nosort3' => array(
			'display' => __('Dispatch Window', 'grid'),
			'dbname'  => 'show_queue_dispatch_window',
			'sort'    => 'ASC'
		),
		'nosort4' => array(
			'display' => __('Resource Requirement', 'grid'),
			'dbname'  => 'show_queue_res_req',
			'sort'    => 'ASC'
		),
		'sndJobsTo' => array(
			'display' => __('Send Jobs To', 'grid'),
			'dbname'  => 'show_queue_send_to',
			'sort'    => 'ASC'
		),
		'rcvJobsFrom' => array(
			'display' => __('Receive Jobs From', 'grid'),
			'dbname'  => 'show_queue_recv_from',
			'sort'    => 'ASC'
		),
		'efficiency' => array(
			'display' => __('Avg Effic', 'grid'),
			'dbname'  => 'show_queue_efficiency',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nosort5' => array(
			'display' => __('Total %s', format_job_slots(), 'grid'),
			'dbname'  => 'show_queue_host_slots',
			'sort'    => 'ASC'
		),
		'numslots' => array(
			'display' => __('Avail %s', format_job_slots(), 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'maxjobs' => array(
			'display' => __('Max %s', format_job_slots(), 'grid'),
			'dbname'  => 'show_queue_maxjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'userJobLimit' => array(
			'display' => __('User Limit', 'grid'),
			'dbname'  => 'show_queue_userjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'hostJobLimit' => array(
			'display' => __('Host Limit', 'grid'),
			'dbname'  => 'show_queue_hostjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'procJobLimit' => array(
			'display' => __('Job Limit', 'grid'),
			'dbname'  => 'show_queue_procjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'minProcLimit' => array(
			'display' => __('Min %s', format_job_slots(), 'grid'),
			'dbname'  => 'show_queue_min_procjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'defProcLimit' => array(
			'display' => __('Default %s', format_job_slots(), 'grid'),
			'dbname'  => 'show_queue_def_procjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'procLimit' => array(
			'display' => __('Max %s', format_job_slots(), 'grid'),
			'dbname'  => 'show_queue_procjobs',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'sharedSlots' => array(
			'display' => __('Total Shared', 'grid'),
			'dbname'  => 'show_queue_shareddedicated',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'dedicatedSlots' => array(
			'display' => __('Total Dedicated', 'grid'),
			'dbname'  => 'show_queue_shareddedicated',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'openSharedSlots' => array(
			'display' => __('Open Shared', 'grid'),
			'dbname'  => 'show_queue_shareddedicated',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'openDedicatedSlots' => array(
			'display' => __('Open Dedicated', 'grid'),
			'dbname'  => 'show_queue_shareddedicated',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'nojobs' => array(
			'display' => __('Active %s', format_job_slots(), 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'runjobs' => array(
			'display' => __('Run %s', format_job_slots(), 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'pendjobs' => array(
			'display' => __('Pend %s', format_job_slots(), 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'suspjobs' => array(
			'display' => __('Suspend %s', format_job_slots(), 'grid'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_pend_time' => array(
			'display' => __('AVG Pend', 'grid'),
			'dbname'  => 'show_queue_pend_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_pend_time' => array(
			'display' => __('MAX Pend', 'grid'),
			'dbname'  => 'show_queue_pend_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_run_time' => array(
			'display' => __('AVG Run', 'grid'),
			'dbname'  => 'show_queue_run_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_run_time' => array(
			'display' => __('MAX Run', 'grid'),
			'dbname'  => 'show_queue_run_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_psusp_time' => array(
			'display' => __('AVG PSusp', 'grid'),
			'dbname'  => 'show_queue_psusp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_psusp_time' => array(
			'display' => __('MAX PSusp', 'grid'),
			'dbname'  => 'show_queue_psusp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_ssusp_time' => array(
			'display' => __('AVG SSusp', 'grid'),
			'dbname'  => 'show_queue_ssusp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_ssusp_time' => array(
			'display' => __('MAX SSusp', 'grid'),
			'dbname'  => 'show_queue_ssusp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_ususp_time' => array(
			'display' => __('AVG USusp', 'grid'),
			'dbname'  => 'show_queue_ususp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_ususp_time' => array(
			'display' => __('MAX USusp', 'grid'),
			'dbname'  => 'show_queue_ususp_times',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'total_cpu' => array(
			'display' => __('Total CPU', 'grid'),
			'dbname'  => 'show_queue_total_cpu',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_mem' => array(
			'display' => __('Max Memory', 'grid'),
			'dbname'  => 'show_queue_max_memory',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_mem' => array(
			'display' => __('Avg Memory', 'grid'),
			'dbname'  => 'show_queue_avg_memory',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'max_swap' => array(
			'display' => __('Max VM Size', 'grid'),
			'dbname'  => 'show_queue_max_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'avg_swap' => array(
			'display' => __('Avg VM Size', 'grid'),
			'dbname'  => 'show_queue_avg_swap',
			'align'   => 'right',
			'sort'    => 'DESC'
		)
	);

	return form_process_visible_display_text($display_text);
}

function queuesFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	?>
	<tr class='odd'>
		<td>
			<form id='form_grid' action='grid_bqueues.php?action=viewqueue&tab=general'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = grid_get_clusterlist();

							if (!empty($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_bqueues.php', __('User', 'grid'), 'job_user', get_request_var('job_user'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<?php
						// Hook for metadata parameters
						api_plugin_hook_function('grid_meta_param', 'grid_bqueues');
					?>
					<td>
						<?php print __('Queues', 'grid');?>
					</td>
					<td>
						<select id='rows'>
						<?php
							if (cacti_sizeof($grid_rows_selector) > 0) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
						?>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'grid');?>
					</td>
					<td>
						<select id='refresh'>
						<?php
						$max_refresh = read_config_option("grid_minimum_refresh_interval");
						foreach($grid_refresh_interval as $key => $value) {
							if ($key >= $max_refresh) {
								print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print " selected"; } print ">" . $value . "</option>";
							}
						}
						?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value="<?php print html_escape_request_var('filter');?>">
					</td>
					<td>
						<input type='checkbox' id='unused'<?php if ((get_request_var('unused') == 'true') || (get_request_var('unused') == 'on')) print ' checked="true"';?>>
					</td>
					<td>
						<label for='unused'><?php print __('Include Unused Queues', 'grid');?></label>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}
function validate_bqueues_request_vars() {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'nojobs',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval')
			),
		'job_user' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'queue_group' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'unused' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => 'true',
			'options' => array('options' => 'sanitize_search_string')
			),
		'drp_action' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gqv');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */
}
function grid_view_queues() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config, $grid_queue_control_actions;

	$sql_params = array();
	grid_set_minimum_page_refresh();

	general_header();
	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'grid_bqueues.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&job_user=' + $('#job_user').val();
		<?php
			// Hook for metadata
			$meta_string = api_plugin_hook_function("grid_meta_column_filter", "grid_bqueues");
			if ($meta_string != "grid_bqueues") {
				print "strURL = strURL + " . $meta_string . ";";
			}
		?>
		strURL += '&filter=' + $('#filter').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_bqueues.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #unused, #filter, #job_user, #refresh, #rows, #queue_group').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
		applySkinRTM();
	});

	</script>
	<?php

	$debug_log = nl2br(debug_log_return('grid_admin'));
	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table width='100%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center'>
			<tr>
				<td style='padding: 3px; font-family: monospace;font-size:10pt;'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box('<strong>Batch Queue Filters</strong>', '100%', '', '3', 'center', '');
	queuesFilter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$queue_results = grid_view_get_queues_records($sql_where, true, $rows, $sql_params);

	/* print checkbox form for validation */
	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		form_start('grid_bqueues.php', 'chk');
	}

	html_start_box('', '100%', '', '3', 'center', '');

	if (get_request_var('job_user') == -1) {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_queues AS gq
			INNER JOIN grid_clusters AS gc
			ON gq.clusterid=gc.clusterid
			$sql_where";
	} else {
		$rows_query_string = "SELECT
			COUNT(*)
			FROM grid_queues AS gq
			INNER JOIN grid_queues_users_stats AS gqu
			ON gq.queuename=gqu.queue AND
			gq.clusterid=gqu.clusterid
			INNER JOIN grid_clusters AS gc
			ON gqu.clusterid=gc.clusterid
			$sql_where";
	}

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	$display_text = build_queue_display_array();

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$colspan = cacti_sizeof($display_text) + 1;
	} else {
		$colspan = cacti_sizeof($display_text);
	}

	/* generate page list */
	$nav = html_nav_bar('grid_bqueues.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text), __('Queues', 'grid'), 'page', 'main');

	print $nav;

	if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
		$disabled = false;
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	} else {
		$disabled = true;
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	}

	$i = 0;
	if (!empty($queue_results)) {
		foreach ($queue_results as $queue) {
			$cacti_host = db_fetch_cell_prepared("SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid=?", array($queue["clusterid"]));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM host_snmp_cache
					INNER JOIN graph_local
					ON (graph_local.snmp_query_id=host_snmp_cache.snmp_query_id)
					AND (host_snmp_cache.host_id=graph_local.host_id)
					WHERE (graph_local.host_id=?)
					AND (graph_local.snmp_index=?)
					AND (host_snmp_cache.field_name IN('gridQName','queue'))", array($cacti_host, $queue["queuename"]));

				if (!empty($local_graph_ids)) {
					$graph_select = "action=preview&page=1&graph_template_id=-1&rfilter=&style=selective&host_id=-1&graph_add=";

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph["id"] . "%2C";
					}
				} else {
					unset($graph_select);
				}
			} else {
				unset($graph_select);
			}

			$row_id = $queue['queuename'] . ':' . $queue['clusterid'];

			form_alternate_row('line'.$row_id, true, $disabled);

			$url = "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php' .
				'?reset=1' .
				'&queue=' . $queue['queuename'] .
				'&clusterid=' . $queue['clusterid']) . "'>
				<img src='" . $config['url_path'] . "plugins/grid/images/view_hosts.gif' alt='' title='" . __esc('View Hosts', 'grid') . "'></a>";

			if (trees_exist($queue['clusterid'], $queue['queuename'])) {
				$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bqueues.php' .
					'?action=viewqueue' .
					'&tab=share' .
					'&reset=1' .
					'&queue=' . $queue['queuename'] .
					'&clusterid=' . $queue['clusterid']) . "'>
					<img src='" . $config['url_path'] . "plugins/grid/images/view_tree.png' title='" . __esc('View Fairshare Tree', 'grid') . "'></a>";
			} else {
				$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bqueues.php' .
					'?action=viewqueue' .
					'&clusterid=' . $queue['clusterid'] . '&queue=' . $queue['queuename']) . "'>
					<img src='" . $config['url_path'] . "plugins/grid/images/view_config.gif' alt='' title='" . __esc('Queue Configuration', 'grid') . "'></a>";
			}

			$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_busers.php' .
				'?reset=1' .
				'&queue=' . $queue['queuename'] . '&clusterid=' . $queue['clusterid']) . "'>
				<img src='" . $config['url_path'] . "plugins/grid/images/view_users.gif' alt='' title='" . __esc('View Users', 'grid') . "'></a>";

			$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $queue['clusterid'] .
				'&status=ACTIVE' .
				'&page=1' .
				'&queue=' . $queue['queuename']) . "'>
				<img src='" . $config['url_path'] . "plugins/grid/images/view_jobs.gif' alt='' title='" . __esc('View Active Jobs', 'grid') . "'></a>";

			if (isset($graph_select)) {
				$url .= "<a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'>
					<img src='" . $config['url_path'] . "plugins/grid/images/view_graphs.gif' alt='' title='" . __esc('View Queue Graphs', 'grid') . "'></a>";
			}

			$api_queue  = $queue['queuename'];
			$return_val = api_plugin_hook_function('show_charts', $api_queue);
			if ($api_queue != $return_val) {
				$url .= $return_val;
			}

			form_selectable_cell($url, $row_id, '10');

			$active_url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $queue['clusterid'] .
				'&status=ACTIVE&queue=' . $queue['queuename']);

			$run_url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $queue['clusterid'] .
				'&status=RUNNING&queue=' . $queue['queuename']);

			$pend_url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php' .
				'?action=viewlist' .
				'&reset=1' .
				'&clusterid=' . $queue['clusterid'] .
				'&status=PEND&queue=' . $queue['queuename']);

			$title = __('Show Queue Jobs', 'grid');

			form_selectable_cell_metadata('simple', 'queue', $queue['clusterid'], $queue['queuename'], '', '', $title, false, $run_url);
			form_selectable_cell_visible($queue['clustername'], 'show_queue_clustername');

			// Hook for metadata column content
			api_plugin_hook_function('grid_meta_column_content', array('page_name' => 'grid_bqueues', 'row_data' => $queue));

			form_selectable_cell_visible(filter_value($queue['description'], get_request_var('filter')), 'show_queue_description', $row_id);
			form_selectable_cell_visible($queue['nice'], 'show_queue_nice', $row_id);
			form_selectable_cell_visible($queue['priority'], 'show_queue_priority', $row_id);
			form_selectable_cell(filter_value($queue['status'] . ':' . $queue['reason'], get_request_var('filter')), $row_id);
			form_selectable_cell_visible(queue_attribs($queue['qAttrib']), 'show_queue_attribs', $row_id);
			form_selectable_cell_visible($queue['windows'], 'show_queue_run_window', $row_id);
			form_selectable_cell_visible($queue['windowsD'], 'show_queue_dispatch_window', $row_id);
			form_selectable_cell_visible($queue['resReq'], 'show_queue_res_req', $row_id);
			form_selectable_cell_visible($queue['sndJobsTo'], 'show_queue_send_to', $row_id);
			form_selectable_cell_visible($queue['rcvJobsFrom'], 'show_queue_recv_from', $row_id);
			form_selectable_cell_visible(display_job_effic($queue['efficiency'],1), 'show_queue_efficiency', $row_id, 'right');
			form_selectable_cell_visible(get_queue_host_slots($queue), 'show_queue_host_slots',  $row_id, 'right');
			form_selectable_cell(number_format_grid($queue['numslots'], -1), $row_id, '', 'right');
			form_selectable_cell_visible(number_format_grid($queue['maxjobs'], -1), 'show_queue_maxjobs', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['userJobLimit'], -1), 'show_queue_userjobs', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['hostJobLimit'], -1), 'show_queue_hostjobs', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['procJobLimit'], -1), 'show_queue_procjobs', $row_id, 'right');

			if ($queue['minProcLimit'] == '-1') {
				form_selectable_cell_visible('-', 'show_queue_min_procjobs', $row_id, 'right');
			} else {
				form_selectable_cell_visible(number_format_grid($queue['minProcLimit'], -1), 'show_queue_min_procjobs', $row_id, 'right');
			}

			if ($queue['defProcLimit'] == '-1') {
				form_selectable_cell_visible('-', 'show_queue_def_procjobs', $row_id, 'right');
			} else {
				form_selectable_cell_visible(number_format_grid($queue['defProcLimit'], -1), 'show_queue_def_procjobs', $row_id, 'right');
			}

			form_selectable_cell_visible(number_format_grid($queue['procLimit'], -1), 'show_queue_procjobs', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['sharedSlots'], -1), 'show_queue_shareddedicated', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['dedicatedSlots'], -1), 'show_queue_shareddedicated', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['openSharedSlots'], -1), 'show_queue_shareddedicated', $row_id, 'right');
			form_selectable_cell_visible(number_format_grid($queue['openDedicatedSlots'], -1), 'show_queue_shareddedicated', $row_id, 'right');
			form_selectable_cell(filter_value(number_format_grid($queue['nojobs'], -1), ''), $row_id, '', 'right');
			form_selectable_cell(filter_value(number_format_grid($queue['runjobs'], -1), ''), $row_id, '', 'right');
			form_selectable_cell(filter_value(number_format_grid($queue['pendjobs'], -1), ''), $row_id, '', 'right');
			form_selectable_cell(number_format_grid($queue['suspjobs'], -1), $row_id, '', 'right');
			form_selectable_cell_visible(display_job_time($queue['avg_pend_time'],1), 'show_queue_pend_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['max_pend_time'],1), 'show_queue_pend_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['avg_run_time'],1), 'show_queue_run_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['max_run_time'],1), 'show_queue_run_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['avg_psusp_time'],1), 'show_queue_psusp_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['max_psusp_time'],1), 'show_queue_psusp_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['avg_ssusp_time'],1), 'show_queue_ssusp_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['max_ssusp_time'],1), 'show_queue_ssusp_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['avg_ususp_time'],1), 'show_queue_ususp_times', $row_id, 'right');
			form_selectable_cell_visible(display_job_time($queue['max_ususp_time'],1), 'show_queue_ususp_times', $row_id, 'right');
			form_selectable_cell_visible($queue['total_cpu'], 'show_queue_total_cpu', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory($queue['max_mem'],2), 'show_queue_max_memory', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory($queue['avg_mem'],2), 'show_queue_avg_memory', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory($queue['max_swap'],2), 'show_queue_max_swap', $row_id, 'right');
			form_selectable_cell_visible(display_job_memory($queue['avg_swap'],2), 'show_queue_avg_swap', $row_id, 'right');

			if (api_plugin_user_realm_auth('LSF_Cluster_Control')) {
				form_checkbox_cell($queue['queuename'], $row_id, $disabled);
			}

			form_end_row();
		}

		html_end_box(false);

		/* put the nav bar on the bottom as well */
		print $nav;
	} else {
		print '<tr><td colspan="' . (cacti_sizeof($display_text)+1) . '"><em>' . __('No Queue Records Found', 'grid') . '</em></td></tr>';

		html_end_box(false);
	}

	if (!$disabled) {
		form_hidden_box('save_queue_actions','1','');
		draw_actions_dropdown($grid_queue_control_actions);
		form_end();
	}

	api_plugin_hook('grid_page_bottom');
}

function grid_print_limit($value) {
	if ($value == '-') {
		return '-';
	} elseif ($value == '-1') {
		return '-';
	} elseif ($value == INF_INT) {
		return '-';
	} elseif ($value == INF_FLOAT) {
		return '-';
	} else {
		return $value;
	}
}

function get_queue_host_slots($queue) {
	return db_fetch_cell_prepared("SELECT SUM(IF(maxCpus>maxJobs,maxCpus,maxJobs))
		FROM grid_queues_hosts AS gqh
		INNER JOIN grid_hostinfo AS ghi
		ON ghi.clusterid=gqh.clusterid AND ghi.host=gqh.host
		INNER JOIN grid_hosts AS gh
		ON ghi.clusterid=gh.clusterid AND ghi.host=gh.host
		WHERE gqh.queue=? AND gqh.clusterid=?", array($queue['queuename'], $queue['clusterid']));
}

function grid_queue_buttons($queue) {
	$authfull = api_plugin_user_realm_auth('LSF_Cluster_Control');

	$admins   = db_fetch_cell_prepared('SELECT lsf_admins
		FROM grid_clusters
		WHERE clusterid=?', array(get_request_var('clusterid'))) . ' ' . $queue['admins'];

	$curuser  = get_username($_SESSION['sess_user_id']);

	$output   = '';

	if (($authfull) ||
		(substr_count($admins, " $curuser")) ||
		(substr_count($admins, "$curuser ")) ||
		(substr_count($admins, "\\$curuser"))) {
		if ($queue['status'] == 'Open') {
			$output  = "<input style='padding:0px;margin:0px;' type='button' onClick='close_queue(\"" . $queue['queuename'] . "\")' value='Close'>";
		} else {
			$output  = "<input style='padding:0px;margin:0px;' type='button' onClick='open_queue(\"" . $queue['queuename'] . "\")' value='Open'>";
		}
		if ($queue['reason'] == 'Active') {
			$output .= "<input style='padding:0px;margin:0px;' type='button' onClick='inact_queue(\"" . $queue['queuename'] . "\")' value='Inactivate'>";
		} else {
			$output .= "<input style='padding:0px;margin:0px;' type='button' onClick='act_queue(\"" . $queue['queuename'] . "\")' value='Activate'>";
		}
	}

	return $output;
}

function trees_exist($clusterid, $queue) {
	$trees = db_fetch_cell_prepared("SELECT COUNT(SUBSTRING_INDEX(shareAcctPath, '/', 2))
		FROM grid_queues_shares
		WHERE shareAcctPath != user_or_group
		AND shareAcctPath!=''
		AND clusterid=?
		AND queue=?",
		array($clusterid, $queue));

	return $trees;
}

function graphs_exist($clusterid, $queue) {
	//get data query id "GRID - Queue - Information"
	$snmp_query_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='88ab1dc3a1dd69cf8eb76845f3bb957e'");

	//get cluster host id
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");
	$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE host_template_id=? AND clusterid=?", array($host_template_id, $clusterid));


	$total_graphs = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id=gl.id
		WHERE gl.snmp_query_id = ? AND gl.host_id = ? AND gl.snmp_index = ?",
		array($snmp_query_id, $host_id, $queue));

	return $total_graphs;
}


function grid_queue_tabs($queue) {
	global $config;

	$tabs_queue = array(
		'general' => 'Details',
		'share'   => 'Fairshare Tree',
		/*'users'   => 'Users',
		'hosts'   => 'Hosts',
		'jobs'    => 'Jobs', */
		'graphs'  => 'Graphs'
	);

	if (!trees_exist(get_request_var('clusterid'), get_request_var('queue'))) {
		unset($tabs_queue['share']);
	}

	if (!graphs_exist(get_request_var('clusterid'), get_request_var('queue'))) {
		unset($tabs_queue['graphs']);
	}

	if (!isset_request_var('tab')) {
		/* there is no selected tab; select the first one */
		$current_tab = array_keys($tabs_queue);
		$current_tab = $current_tab[0];
	} else {
		$current_tab = get_request_var('tab');
	}

	print "<table><tr><td style='padding-bottom:0px;'>\n";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>\n";

	if (cacti_sizeof($tabs_queue) > 0) {
		$i = 0;
		foreach (array_keys($tabs_queue) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == $current_tab) ? "class='selected pic'" : "class='pic'") . " href='" . html_escape($config['url_path'] .
				"plugins/grid/grid_bqueues.php?action=viewqueue" .
				"&tab=" . $tab_short_name .
				"&clusterid=" . $queue['clusterid']  .
				"&queue="     . $queue['queuename']) .
				"'>$tabs_queue[$tab_short_name]</a></li>\n";
			$i++;
		}
	}

	print "</ul></nav></div>\n";
	print "</tr></table>\n";
}

function get_fairshare_tree_data() {
	global $config;

	if (isset_request_var('clear')) {//backup avoid be killed by validate_tree_variables()
		$clusterid_backup     = get_request_var('clusterid');
		$queue_backup     = get_request_var('queue');
	}
	validate_tree_variables();
	if (isset_request_var('clear')) {
		set_request_var('clusterid', $clusterid_backup);
		set_request_var('queue', $queue_backup);
	}

	$clusterid     = get_request_var('clusterid');
	$queue         = get_request_var('queue');

	//paramater id is appended in jstree lazy load mode for loading the current node. root node id is '#', the other nodes id is their name.
	$shareAcctPath = get_request_var('id');

	if ($shareAcctPath == '#') {
		$shareAcctPath = '';
		$level = 0;
	} else {
		$level = -1;
	}

	$tree = '';
	get_fairshare_tree($clusterid, $queue, $shareAcctPath, $level, $tree);
	print $tree;
}

function get_header() {
	return ' ' . __('[ Queue: %s, Cluster: %s ]', html_escape_request_var('queue'), grid_get_clustername(get_request_var('clusterid')), 'grid');
}

function grid_view_queue_fairshare($queue) {
	global $config, $charts;

	if (isset_request_var('clear')) {//backup avoid be killed by validate_tree_variables()
		$clusterid_backup     = get_request_var('clusterid');
		$queue_backup     = get_request_var('queue');
	}
	validate_tree_variables();
	if (isset_request_var('clear')) {
		set_request_var('clusterid', $clusterid_backup);
		set_request_var('queue', $queue_backup);
	}
	grid_bqueues_set_minimum_page_refresh();

	$clusterid = get_request_var('clusterid');
	$queue     = get_request_var('queue');

	$tree = '';

	print "<table width='100%'>
			<tr>
				<td style='vertical-align:top;width:10%;max-width:20%;border:1px solid #9CA1A5;'>";
	print "<div id='jstree_container' style='max-width:250px;overflow-y:auto;overflow-x:auto;'><div id='jstree' style='padding-right:10px;'></div></div>";

	print "</td><td style='vertical-align:top;'><div id ='jstree_right_area_filter_table' class ='right_area'>";

	html_start_box(__('Filters %s', get_header(), 'grid') . "&nbsp;<div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div><span id='message'></span>", '100%', '', '3', 'center', '');
	treeFilter();
	html_end_box();

	print "<div id='tree_data' style='width:100%;'></div>";
	print '</div></td></tr></table>';
	?>
	<script type='text/javascript'>
	var current_leaf = '';

	<?php print "var clusterid=" . (isempty_request_var('clusterid')? '0':get_request_var('clusterid')) . ";\n"; ?>
	<?php print "var queue='" . (isempty_request_var('queue')? '0':get_request_var('queue')) . "';\n"; ?>
	var all_charts = new Array("<?php print implode('","', array_keys($charts));?>");
	var cur_charts = new Array("<?php if (isset_request_var('corder')) print implode('","', explode(' ', get_request_var('corder')));?>");
	var prev_charts = new Array();
	var doing_apply_filter = 0;

	$(function() {
		$('#jstree')
		.jstree({
			core : {
				data : {
					expand_selected_onload : true,
					url : 'grid_bqueues.php?action=get_fairshare_tree&clusterid=<?php print html_escape_request_var('clusterid');?>&queue=<?php print html_escape_request_var('queue');?>',
					data : function(node) {
						resizeTreeData();
						return { 'id' : node.id }
					}
				},
				themes : {
					name : 'default',
					responsive : true,
					//url : '<?php print $config['url_path'];?>/include/css/platform-theme/style.css',
					dots : false
				},
				animation : 0,
				multiple : false,
				check_callback : false
			},
			state : { key : 'bq_tree_<?php print get_request_var('queue') . '_' . get_request_var('clusterid');?>' },
			plugins : [ 'state', 'wholerow' ]
		})
		.on('select_node.jstree', function(e, data) {
			shareAcctPath = data.node.id;
			current_leaf = shareAcctPath;
			//tree.shareAcctPath.value = shareAcctPath;
			applyFilter('', '', '', 1);
		})
		.on('after_open.jstree', function(e, data) {
			resizeTreeData();
			//applyFilter();
		})
		.on('after_close.jstree', function(e, data) {
			resizeTreeData();
			//applyFilter();
		});

		$(window).resize(function() {
			resizeTreeData();
		});

		$('#filter').unbind().keydown(function(event) {
			if ( event.which == 13 ) {
				event.preventDefault();
				applyFilter();
			}
		});

		var charts_height = $('#charts').children('option').length * 28;

		$('#charts').multiselect({ height: charts_height, minWidth: 180, header: 'Choose your charts' });
	});

	function buildFilterRequest(action) {
		strURL =  'grid_bqueues.php?action=' + action;
		strURL += '&filter=' + $('#filter').val();
		strURL += '&show=' + $('#show').val();
		strURL += '&timespan=' + $('#timespan').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		strURL += '&rows=' + $('#rows').val();
		strURL += '&refresh=' + $('#refresh').val();

		return strURL;
	}

	function buildRequest(action) {
		var corderarray = new Array();
		var corder = '';

		$('.cpanel').each(function() {
			id = $(this).attr('id').replace('graphs_', '');
			if (id.length > 0) {
				if ($('#charts option[value='+id+']').is(':selected')) {
					corder = corder + (corder != '' ? ' ':'') + id;
					corderarray.push(id);
				}
			}
		});

		$.grep(all_charts, function (id) {
			if ($.inArray(id, corderarray) < 0) {
				if ($('#charts option[value='+id+']').is(':selected')) {
					corder = corder + (corder != '' ? ' ':'') + id;
				}
			}
		});

		var strURL = buildFilterRequest(action);

		strURL += '&corder=' + corder;

		for (var i = 0; i < all_charts.length; i++) {
			strURL = strURL + '&'+all_charts[i]+'=' + $('#charts option[value='+all_charts[i]+']').is(':selected');
		}

		return strURL;
	}

	function filterSave() {
		var strURL = buildRequest('ajaxsave');

		$('#status').show();

		$.get(strURL, function() {
			$('#message').text('').show().html('<?php print __('Filter Settings Saved', 'grid');?>').delay(2000).fadeOut(1000);
			$('#status').hide();
		});
	}

	function chartChange() {
		var cur_charts = new Array();

		$('.cpanel').each(function() {
			id = $(this).attr('id').replace('graphs_', '');
			if (id.length > 0) {
				if ($('#charts option[value='+id+']').is(':selected')) {
					cur_charts.push(id);
				}
			}
		});

		for (var i = 0; i < all_charts.length; i++) {
			if ($('#charts option[value='+all_charts[i]+']').is(':selected')) {
				if ($.inArray(all_charts[i], cur_charts) < 0) {
					cur_charts.push(all_charts[i]);
				}
			}
		}

		$('.cpanel').each(function() {
			id = $(this).attr('id').replace('graphs_', '');
			if (FusionCharts(id+'_fusion')) FusionCharts(id+'_fusion').dispose();
			$('#graphs_'+id).empty();
			$('#graphs_'+id).remove();
		});

		var url_get_group_count = 'grid_bqueues.php?action=ajaxheight&queue='+queue+'&clusterid='+clusterid+'&shareacctpath='+current_leaf+'&unused='+$('#unused').is(':checked')+'&filter='+$('#filter').val();

		$('#status').show();

		$.get(url_get_group_count, function(data) {
			var fh = data;
			prev_div = 'template';
			if ($('#template').length > 0) { //fix xxx_fusion.render() Error, Unable to find the container DOM element
				for (var i = 0; i < cur_charts.length; i++) {
					show_fairshare_chart(prev_div, clusterid, queue, current_leaf, $('#timespan').val(), cur_charts[i], $('#filter').val(), $('#unused').is(':checked'), fh);
					prev_div = 'graphs_'+cur_charts[i];
				}
			}

			$('#status').hide();
		});
	}

	function show_fairshare_chart(prev_div, clusterid, queue, shareacctpath, timespan, metric, filter_str, unused, fh) {
		var fw = '98%';
		fh = fh || 210;

		var url = encodeURIComponent('grid_bqueues.php?action=ajaxcharts&queue='+queue+'&clusterid='+clusterid+'&shareacctpath='+shareacctpath+'&metric='+metric+'&timespan='+timespan+'&unused='+unused+'&filter='+filter_str);

		//FusionCharts.debugMode.enabled(true);
		//FusionCharts.debugMode.outputTo(console.log);
		//FusionCharts.setCurrentRenderer('javascript');

		if (FusionCharts(metric+ '_fusion')) {
			FusionCharts(metric+ '_fusion').dispose();
		}

		$('#template').clone().insertAfter('#'+prev_div);
		$('#'+prev_div).next('div').attr('id', 'graphs_'+metric);
		$('#'+prev_div).next('div').attr('class', 'cpanel');

		//$('#template').append("<div tabindex=0 id='graphs_"+metric+"' class='cpanel'></div>");
		$('#graphs_'+metric).show();

		var myChart = new FusionCharts('StackedArea2D', metric+ '_fusion', fw, fh);

		myChart.setXMLUrl(url);
		myChart.render('graphs_'+metric);
	}

	function applyFilter(sort_column, sort_direction, remove_sort_column, page) {
		sort_column = sort_column || '';
		sort_direction = sort_direction || '';
		remove_sort_column = remove_sort_column || '';
		page = page || 0;

		if (doing_apply_filter > 0) {
			return;
		}

		doing_apply_filter = 1;

		if ($('#show').val() == 1) {  //show tables and graphs
			$('#charts').multiselect("enable");

			if ($('#timespan').selectmenu('instance') !== undefined) {
				$('#timespan').selectmenu('enable');
			}
		} else {  //only show tables;
			$('#charts').multiselect("disable");

			if ($('#timespan').selectmenu('instance') !== undefined) {
				$('#timespan').selectmenu('disable');
			}
		}
		$('#charts').multiselect("refresh");

		//save current chart order
		var cur_charts = new Array();

		$('.cpanel').each(function() {
			id = $(this).attr('id').replace('graphs_', '');
			if (id.length > 0) {
				if ($('#charts option[value='+id+']').is(':selected')) {
					cur_charts.push(id);
				}
			}
		})

		// save current charts order to variable pre_charts for recovering charts order when display filter is switched from "1 - table" to "3 - table and graphs"
		if (cur_charts.length > 0) prev_charts = cur_charts;

		if ($('#show').val() == 1 && cur_charts.length === 0) {
			cur_charts = prev_charts;
		}

		if (cur_charts.length === 0) {
			var corder = <?php print ("'" . get_request_var('corder') . "'");?>;
			cur_charts = corder.split(' ');
		}

		strURL = 'grid_bqueues.php?header=false&action=get_tree_data&queue=<?php print get_request_var('queue');?>&clusterid=<?php print get_request_var('clusterid');?>&shareAcctPath='+current_leaf;
		strURL += '&filter=' + $('#filter').val();
		strURL += '&show=' + $('#show').val();
		strURL += '&timespan=' + $('#timespan').val();
		strURL += '&unused=' + $('#unused').is(':checked');
		strURL += '&rows=' + $('#rows').val();
		strURL += '&refresh=' + $('#refresh').val();

		if (sort_column.length > 0) strURL += '&sort_column=' + sort_column;
		if (sort_direction.length > 0) strURL += '&sort_direction=' + sort_direction;
		if (remove_sort_column.length > 0) strURL += '&remove_sort_column=' + remove_sort_column;
		if (page > 0) strURL += '&page=' + page;

		$('#status').show();
		$.get(strURL, function(data) {
			$('#tree_data').html(data);
			if ($('#nochild').val() == 0) {
				var url_get_group_count = 'grid_bqueues.php?action=ajaxheight&queue='+queue+'&clusterid='+clusterid+'&shareacctpath='+current_leaf+'&unused='+$('#unused').is(':checked')+'&filter='+$('#filter').val();
				$.get(url_get_group_count, function(data) {
					var fh = data;

					prev_div = 'template';
					for (var i = 0; i < cur_charts.length; i++) {
						if (cur_charts[i].length > 0) {
							if ($('#charts option[value='+cur_charts[i]+']').is(':selected')) {
								show_fairshare_chart(prev_div, clusterid, queue, current_leaf, $('#timespan').val(), cur_charts[i], $('#filter').val(), $('#unused').is(':checked'), fh);
								prev_div = 'graphs_'+cur_charts[i];
							}
						}
					}
				});

			}
			//applyPTSkin();
			remove_duplicate_filter();
			applySkin();
			applySkinRTM();
			$('#status').hide();
			applyJS();
			resizeTreeData();
			doing_apply_filter = 0;
			clearTimeout(myRefresh);
			myRefresh=setTimeout(function() { applyFilter() }, $('#refresh').val()*1000);
		});

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#filter, #rows, #refresh, #unused, #show , #timespan').change(function() {
			applyFilter();
		});

		$('#go').click(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	}

	function remove_duplicate_filter() {
		// remove nice search filter to filters that added in applySkin()
		if ($('input[id="filter"]').length > 0) {
			$('input[id="filter"] + i').remove();
		}

		if ($('input[id="filterd"]').length > 0) {
			$('input[id="filterd"] + i').remove();
		}

		if ($('input[id="rfilter"]').length > 0) {
			$('input[id="rfilter"] + i').remove();
		}

	}

	function clearFilter() {
		var strURL = 'grid_bqueues.php?clear=1&action=viewqueue&header=false&tab=share&queue=<?php print get_request_var('queue');?>&clusterid=<?php print get_request_var('clusterid');?>';
		loadPageNoHeader(strURL);
	}

	function applyJS() {
		//redefine table view column header click event

		//redefine table view page navigator click event
		$('a.linkOverDark').unbind().click(function(event) {
			event.preventDefault();

			var url_vars = getUrlVars($(this).attr('href'));
			var page = url_vars["page"];

			applyFilter("", "", "", page);
		});

		//redefine user group column click event
		$('a.col_user_group').unbind().click(function(event) {
			event.preventDefault();

			var url_vars = getUrlVars($(this).attr('href'));
			var ugroup = url_vars["ugroup"];
			var new_node = current_leaf + "/" + ugroup;

			$('#jstree').jstree('open_node', current_leaf, function(e, data) {
				var childrens = $("#jstree").jstree("get_children_dom",current_leaf);

				for(var i=0;i<childrens.length;i++) {
					var child_node = childrens[i].id;
					if (child_node == new_node) {
						$('#jstree').jstree('open_node', child_node);
						$('#jstree').jstree('activate_node', child_node);
					} else {
						$('#jstree').jstree('close_node', child_node);
					}
				}
			}, true);
		});

		$("#div_graphs").sortable({
			axis: "y",
			scroll: true,
			start: function () {
			},
			stop: function() {
			}
		});
	}

	function getUrlVars(url) {
		var vars = {};
		var parts =  url.replace(/[?&]+([^=&]+)=([^&]*)/gi,
		function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}

	function sortMe() {
	}

	function resizeTreeData() {
		var windowHeight = $(window).height();
		var menuHeight   = 471;
		var menuTop      = 56;
		if ($('#navigation').length) {
			menuHeight   = $('#navigation').height();
			menuTop      = $('#navigation').position().top;
		}
		var positionTop  = 0;
		if ($('#jstree_container').length) {
			positionTop  = $('#jstree_container').position().top;
		}

		var relMenuHeight = windowHeight - menuTop;

		//console.log('menuHeight:'+menuHeight+', menuTop:'+menuTop+', positionTop:'+positionTop+', relMenuHeight:'+relMenuHeight);

		var new_height = menuHeight - (positionTop - menuTop) - 108;
		$('#jstree_container').css('border', '1px thin black');

		var width_menu = $('#grid_menu').outerWidth(true);
		var width_jstree = $('#jstree_container').outerWidth(true);

		var new_width = screen.width - width_menu - width_jstree - 30;

		$("#jstree_right_area_filter_table").css('width', new_width +"px");
		$("#jstree_right_area_charts").css('width', new_width +"px");
		//alert("1:" + $(window).width() +", 2:" + $('#menu').width() +", 3:" + $('#jstree_container').width() + ", 4:" + new_width + ", 5:" + $('#jstree_right_area_charts').width());
	}

	</script>
	<?php
}

function get_fairshare_tree($clusterid, $queue, $parent = '', $level = 0, &$tree = '') {
	global $config;

	if ($level < 0) {
		$parent .= '/';
		$level = substr_count(str_replace('//', '/', $parent), '/') + 1;
	} else {
		$level = substr_count($parent, '/') + 2;  //fairshare path starts from '/'. So root level is 2 as root path is sth like '/all-regr/'
	}

	$children = db_fetch_assoc_prepared("SELECT DISTINCT SUBSTRING_INDEX(shareAcctPath, '/', ?) AS branch
		FROM grid_queues_shares
		WHERE shareAcctPath LIKE ?
		AND queue=?
		AND shareAcctPath!=user_or_group
		AND shareAcctPath!=''
		AND clusterid=?
		ORDER BY shareAcctPath ASC",
		array($level, $parent. '%', $queue, $clusterid));

	if (!empty($children)) {
		foreach($children as $ochild) {
			$child_parts = explode('/', $ochild['branch']);
			$nchild      = $ochild['branch'];

			if (tree_has_children($clusterid, $queue, $ochild['branch'], $level) == true) {
				$tree .= "<ul><li data-jstree='{\"icon\":\"" . $config['url_path'] . "plugins/grid/images/group.gif\"}' class='jstree-closed' id='" . $nchild . "'>" . $child_parts[cacti_sizeof($child_parts)-1] . "</li></ul>\n";
			} else {
				$tree .= "<ul><li data-jstree='{\"icon\":\"" . $config['url_path'] . "plugins/grid/images/group.gif\"}' id='" . $nchild . "'>" . $child_parts[cacti_sizeof($child_parts)-1] . "</li></ul>\n";
			}
		}
	}
}

function tree_has_children($clusterid, $queue, $parent, $level) {
	$level++;
	$parent .= '/';

	$children = db_fetch_assoc_prepared("SELECT DISTINCT SUBSTRING_INDEX(shareAcctPath, '/', ?) AS branch
		FROM grid_queues_shares
		WHERE shareAcctPath LIKE ?
		AND queue=?
		AND shareAcctPath!=user_or_group
		AND shareAcctPath!=''
		AND clusterid=?
		ORDER BY shareAcctPath ASC",
		array($level, $parent. '%', $queue, $clusterid));

	if (!empty($children)) {
		return true;
	} else {
		return false;
	}
}

function grid_view_queue_jobs($queue) {
	global $config;
	html_start_box(__('Queue Job Details %s', get_header(), 'grid'), '100%', '', '3', 'center', '');
	html_end_box();
}

function grid_view_queue_graphs($queue) {
	global $config;

	$sql_params = array();
	/* ================= input validation and session storage ================= */
	$filters = array(
		'graphs' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_graph_config_option('preview_graphs_per_page')
			),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'columns' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
			),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_page_setting('grid_bqueues_fairshare', 'queue', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'tab' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'graphs',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'thumbnails' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	if (isset_request_var('clear')) {//backup avoid be killed by validate_tree_variables()
		$clusterid_backup     = get_request_var('clusterid');
		$queue_backup     = get_request_var('queue');
	}
	validate_store_request_vars($filters, 'sess_gbq_graph');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');

	if (isset_request_var('clear')) {
		set_request_var('clusterid', $clusterid_backup);
		set_request_var('queue', $queue_backup);
	}
	/* ==================================================== */

	?>
	<script type='text/javascript'>
	function applyFilter(move_flag) {
		strURL = '?action=viewqueue&tab=graphs&header=false';
		strURL = strURL + '&clusterid=' + $('#clusterid').val();
		strURL = strURL + '&queue=' + $('#queue').val();
		strURL = strURL + '&filter=' + $('#filter').val();
		strURL = strURL + '&graphs=' + $('#graphs').val();
		strURL = strURL + '&columns=' + $('#columns').val();
		strURL = strURL + '&thumbnails=' + $('#thumbnails').is(':checked');
		if (move_flag==1 || move_flag==2) {
			strURL += '&date1=' + $('#date1').val();
			strURL += '&date2=' + $('#date2').val();
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
		strURL = '?action=viewqueue&tab=graphs&header=false&clear=1';
		strURL = strURL + '&clusterid=' + $('#clusterid').val();
		strURL = strURL + '&queue=' + $('#queue').val();
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid_bqueues').submit(function(event) {
			event.preventDefault();
			applyFilter(0);
		});
		$('#form_grid_bqueues_graph').submit(function(event) {
			event.preventDefault();
			applyFilter(0);
		});

		$('#clusterid, #queue, #filter, #graphs, #columns, #thumbnails').change(function() {
			applyFilter(0);
		});

		$('#go').click(function() {
			applyFilter(0);
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Queue Graph Filters %s', get_header(), 'grid'), '100%', '', '3', 'center', '');
	grid_bqueues_graph_view_filter($queue);

	//get data query id "GRID - Queue - Information"
	$snmp_query_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='88ab1dc3a1dd69cf8eb76845f3bb957e'");

	//get cluster host id
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");
	$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE host_template_id=? AND clusterid = ?", array($host_template_id, $queue['clusterid']));

	$sql_where = "WHERE gl.snmp_query_id = ? AND gl.host_id = ? AND gl.snmp_index = ? ";
	$sql_params[] = $snmp_query_id;
	$sql_params[] = $host_id;
	$sql_params[] = $queue['queuename'];

	if (get_request_var('filter') != "") {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . " gtg.title_cache LIKE ?";
		$sql_params[] = '%'. htmlspecialchars(get_request_var('filter')) . '%';
	}

	$graphs = db_fetch_assoc_prepared("SELECT
		gtg.local_graph_id,
		gtg.width,
		gtg.height,
		gtg.title_cache
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id=gl.id
		$sql_where
		ORDER BY gtg.title_cache
		LIMIT " . (get_request_var('graphs')*(get_request_var('page')-1)) . "," . get_request_var('graphs'), $sql_params);

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM graph_templates_graph AS gtg
		INNER JOIN graph_local AS gl
		ON gtg.local_graph_id=gl.id
		$sql_where", $sql_params);

	/* reset the page if you have changed some settings */
	if (get_request_var('graphs') * (get_request_var('page')-1) >= $total_rows) {
		set_request_var('page',"1");
	}

	/* include time span selector */
	grid_bqueues_timespan_selector($queue);

	html_end_box();

	html_start_box('', '100%', '', '3', 'center', '');

	$nav = html_nav_bar('grid_bqueues.php?action=viewqueue&tab=graphs&clusterid=' . $queue['clusterid']. '&queue='. $queue['queuename'], MAX_DISPLAY_PAGES, get_request_var('page'), get_request_var('graphs'), $total_rows, 30, __('Graphs'), 'page', 'main');
	print $nav;

	html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end(), '', get_request_var('columns'), get_request_var('thumbnails'));

	html_end_box();
	if ($total_rows) {
		print $nav;
	}
}

function grid_bqueues_timespan_selector($queue) {
	global $config, $graph_timespans, $graph_timeshifts;

	?>
	<script type='text/javascript'>
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

	function grid_bqueues_refreshGraphTimespanFilter() {
		var json = {
			custom: 1,
			button_refresh_x: 1,
			date1: $('#date1').val(),
			date2: $('#date2').val(),
			predefined_timespan: $('#predefined_timespan').val(),
			predefined_timeshift: $('#predefined_timeshift').val(),
			__csrf_magic: csrfMagicToken
		};

		var href = appendHeaderSuppression('grid_bqueues.php?action=viewqueue&tab=graphs&header=false'+ '&clusterid=' + $('#clusterid').val() + '&queue=' + $('#queue').val());

		$.ajaxQ.abortAll();
		$.post(href, json).done(function(data) {
			checkForLogout(data);

			$('#main').empty().hide();
			$('div[class^="ui-"]').remove();
			$('#main').html(data);
			applySkin();
		});
	}

	function grid_bqueues_clearGraphTimespanFilter() {
		var json = {
			button_clear: 1,
			date1: $('#date1').val(),
			date2: $('#date2').val(),
			predefined_timespan: $('#predefined_timespan').val(),
			predefined_timeshift: $('#predefined_timeshift').val(),
			__csrf_magic: csrfMagicToken
		};

		var href = appendHeaderSuppression('grid_bqueues.php?action=viewqueue&tab=graphs&header=false'+ '&clusterid=' + $('#clusterid').val() + '&queue=' + $('#queue').val());

		$.ajaxQ.abortAll();
		$.post(href, json).done(function(data) {
			checkForLogout(data);

			$('#main').empty().hide();
			$('div[class^="ui-"]').remove();
			$('#main').html(data);
			applySkin();
		});
	}

	$(function() {
		$('#move_left').click(function() {
			applyFilter(1);
		});
		$('#move_right').click(function() {
			applyFilter(2);
		});

		$.when(initPage())
		.pipe(function() {
			initializeGraphs();
		});
	});

	function applyTimespanFilterChange() {
		var strURL;

		strURL = 'grid_bqueues.php?action=viewqueue&tab=graphs&header=false&predefined_timespan=' + $('#predefined_timespan').val();
		strURL = strURL + '&predefined_timeshift=' + $('#predefined_timeshift').val();
		strURL = strURL + '&clusterid=' + $('#clusterid').val();
		strURL = strURL + '&queue=' + $('#queue').val();
		loadPageNoHeader(strURL);
	}
	</script>
	<tr class='odd'>
		<td class='noprint'>
			<form id='form_grid_bqueues_graph' action= <?php print 'grid_bqueues.php?action=viewqueue&tab=graphs&clusterid=' . $queue['clusterid'] . '&queue=' . $queue['queuename'];?> id='form_timespan_selector' method='post'>
			<table cellpadding='2' cellspacing='0'>
				<tr>
					<td>
						<?php print __('Presets', 'grid');?>
					</td>
					<td>
						<select id='predefined_timespan' onChange='applyTimespanFilterChange()'>
							<?php
							if ($_SESSION['custom']) {
								$graph_timespans[GT_CUSTOM] = __('Custom', 'grid');
								$start_val = 0;
								$end_val = cacti_sizeof($graph_timespans);
							} else {
								if (isset($graph_timespans[GT_CUSTOM])) {
									asort($graph_timespans);
									array_shift($graph_timespans);
								}
								$start_val = 1;
								$end_val = cacti_sizeof($graph_timespans) + 1;
							}

							if (cacti_sizeof($graph_timespans)) {
								for ($value=$start_val; $value < $end_val; $value++) {
									print "<option value='$value'" . ($_SESSION['sess_current_timespan'] == $value ? ' selected':'') . '>' . title_trim($graph_timespans[$value], 40) . '</option>';
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
							<input type='text' class='ui-state-default ui-corner-all' id='date1' size='18' value='<?php print (isset($_SESSION['sess_current_date1']) ? $_SESSION['sess_current_date1'] : '');?>'>
							<i id='startDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('Start Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<?php print __('To', 'grid');?>
					</td>
					<td>
						<span>
							<input type='text' class='ui-state-default ui-corner-all' id='date2' size='18' value='<?php print (isset($_SESSION['sess_current_date2']) ? $_SESSION['sess_current_date2'] : '');?>'>
							<i id='endDate' class='calendar fa fa-calendar-alt' title='<?php print __esc('End Date Selector');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<i id='move_left' class='shiftArrow fa fa-backward' title='<?php print __esc('Shift Time Backward');?>'></i>
							<select id='predefined_timeshift' name='predefined_timeshift' title='<?php print __esc('Define Shifting Interval');?>'>
								<?php
								$start_val = 1;
								$end_val = cacti_sizeof($graph_timeshifts)+1;
								if (cacti_sizeof($graph_timeshifts) > 0) {
									for ($shift_value=$start_val; $shift_value < $end_val; $shift_value++) {
										print "<option value='$shift_value'"; if ($_SESSION['sess_current_timeshift'] == $shift_value) { print ' selected'; } print '>' . title_trim($graph_timeshifts[$shift_value], 40) . "</option>\n";
									}
								}
								?>
							</select>
							<i id='move_right' class='shiftArrow fa fa-forward' title='<?php print __esc('Shift Time Forward');?>'></i>
						</span>
					</td>
					<td>
						<span>
							<input id='tsrefresh' type='button' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Refresh');?>' name='button_refresh_x' title='<?php print __esc('Refresh selected time span');?>' onClick='grid_bqueues_refreshGraphTimespanFilter()'>
							<input id='tsclear' type='button' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Clear');?>' title='<?php print __esc('Return to the default time span');?>' onClick='grid_bqueues_clearGraphTimespanFilter()'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}

function grid_bqueues_graph_view_filter($queue) {
	global $config, $graphs_per_page;

	?>
	<tr class='odd'>
		<td class='noprint'>
			<form id='form_grid_bqueues'>
			<table class='filterTable'>
				<tr class='noprint'>
					<td width='60'>
						<?php print __('Search');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value="<?php print get_request_var('filter');?>">
					</td>
					<td>
						<?php print __('Graphs');?>
					</td>
					<td>
						<select id='graphs'>
						<?php
							if (cacti_sizeof($graphs_per_page)) {
							foreach ($graphs_per_page as $key => $value) {
								if ($key > 10) continue;
								print '<option valuse="' . $key . '"'; if (get_request_var('graphs') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
							}
							}
						?>
						</select>
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
						<input id='thumbnails' type='checkbox' <?php print (get_request_var('thumbnails') == "on" || get_request_var('thumbnails') == "true" ? " checked":""); ?>>
					</td>
					<td>
						<label for='thumbnails'><?php print __('Thumbnails');?></label>
					</td>
					<td>
						<input type='button' id='go' value='Go'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear'>
					</td>

					<td>
						<input type='hidden' id='clusterid' value="<?php print html_escape($queue['clusterid']);?>">
						<input type='hidden' id='queue' value="<?php print html_escape($queue['queuename']);?>">
						<input type='hidden' id='action' value="<?php print html_escape_request_var('action');?>">
						<input type='hidden' id='tab' value="<?php print html_escape_request_var('tab');?>">
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
}

function grid_bqueues_ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case 'graphs':
			//get data query id "GRID - Queue - Information"
			$snmp_query_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='88ab1dc3a1dd69cf8eb76845f3bb957e'");

			//get cluster host id
			$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");
			$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE host_template_id=? AND clusterid = ?", array($host_template_id, $_SESSION["sess_grid_view_clusterid"]));

			$queue = $_SESSION["sess_gqv_queue"];

			if (get_request_var('term') != "") {
				$values = db_fetch_assoc_prepared("SELECT title_cache AS label, title_cache AS value
					FROM graph_templates_graph AS gtg
					INNER JOIN graph_local AS gl
					ON gtg.local_graph_id=gl.id
					WHERE gl.snmp_query_id = ? AND gl.host_id = ? AND gl.snmp_index = ?
					AND gtg.title_cache LIKE '%" . htmlspecialchars(get_request_var('term')) . "%'
					ORDER BY title_cache
					LIMIT 20",
					array($snmp_query_id, $host_id, $queue, "%" . htmlspecialchars(get_request_var('term')) . "%"));
			} else {
				$values = db_fetch_assoc_prepared("SELECT title_cache AS label, title_cache AS value
					FROM graph_templates_graph AS gtg
					INNER JOIN graph_local AS gl
					ON gtg.local_graph_id=gl.id
					WHERE gl.snmp_query_id = ? AND gl.host_id = ? AND gl.snmp_index = ?
					ORDER BY title_cache
					LIMIT 20",
					array($snmp_query_id, $host_id, $queue));
			}
			print json_encode($values);

			break;
		}
	}
}

function  grid_view_queue_detail($queue) {
	if (cacti_sizeof($queue)) {
		$i = 0;

		html_start_box(__('Queue Details %s', get_header(), 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Name', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['queuename']; ?>
			</td>
			<td width='15%'>
				<?php print __('Description', 'grid');?>
			</td>
			<td width='60%' colspan='5'>
				<?php print $queue['description'];?>
			</td>
		</tr>
		<?php

		//<div float='right' valign='top' align='right'><?php print grid_queue_buttons($queue);

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Status/Reason', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['status'] . ':' . $queue['reason'];?>
			</td>
			<td width='15%'>
				<?php print __('Control Message', 'grid');?>
			</td>
			<td width='45%' colspan='5'>
				<?php print $queue['qCtrlMsg'];?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Attributes', 'grid');?>
			</td>
			<td width='85%' colspan='7'>
				<?php print queue_attribs($queue['qAttrib'], false); ?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Admins', 'grid');?>
			</td>
			<td width='35%' colspan='3'>
				<?php print $queue['admins']; ?>
			</td>
			<td width='15%'>
				<?php print __('Priority', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['priority']; ?>
			</td>
			<td width='15%'>
				<?php print __('Nice', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['nice'];?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Total Jobs', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['nojobs'], -1); ?>
			</td>
			<td width='15%'>
				<?php print __('Pending', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['pendjobs'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Running', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['runjobs'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Suspended', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['suspjobs'], -1);?>
			</td>
		</tr>
		<?php

		html_end_box(false);

		$i = 0;
		html_start_box(__('Slot Limits', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Available %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php $up_slots = $queue['openDedicatedSlots'] + $queue['openSharedSlots'] ?>
				<?php print $up_slots;?>
			</td>
			<td width='15%'>
				<?php print __('Max %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['maxjobs']);?>
			</td>
			<td width='15%'>
				<?php print __('User Run/Job %s Limit', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['userJobLimit']) . '/' . grid_print_limit($queue['procJobLimit']);?>
			</td>
			<td width='15%'>
				<?php print __('Host %s Limit', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['hostJobLimit']);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Shared %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['sharedSlots'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Open Shared %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['openSharedSlots'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Dedicated %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['dedicatedSlots'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Open Dedicated %s', format_job_slots(), 'grid');?>
			</td>
			<td width='10%' colspan='3'>
				<?php print number_format_grid($queue['openDedicatedSlots'], -1);?>
			</td>
		</tr>
		<?php

		html_end_box(false);

		$i = 0;
		html_start_box(__('Performance of Running Jobs', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Efficiency', 'grid');?>
			</td>
			<td width='10%'>
				<?php print round($queue['efficiency'],2) . ' %';?>
			</td>
			<td width='15%'>
				<?php print __('CPU Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['total_cpu'],2);?>
			</td>
			<td width='15%'>
				<?php print __('Memory Avg/Max', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['avg_mem']) . "/" . display_job_memory($queue['max_mem']);?>
			</td>
			<td width='15%'>
				<?php print __('Swap Avg/Max', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['avg_swap']) . "/" . display_job_memory($queue['max_swap']);?>
			</td>
		</tr>
		<?php

		html_end_box(false);

		$i = 0;
		html_start_box('Throughput', '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Avg/Max Dispatch', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_disp_time']) . "/" . display_job_time($queue['max_disp_time']);?>
			</td>
			<td width='15%'>
				<?php print __('Hourly Started', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['hourly_started_jobs'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Hourly Done', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['hourly_done_jobs'], -1);?>
			</td>
			<td width='15%'>
				<?php print __('Hourly Exited', 'grid');?>
			</td>
			<td width='10%'>
				<?php print number_format_grid($queue['hourly_exit_jobs'], -1);?>
			</td>
		</tr>
		<?php

		html_end_box(false);

		$i = 0;
		html_start_box(__('Time in State Performance (Average/Maximum)', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Pend Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_pend_time']) . '/' . display_job_time($queue['max_pend_time']);?>
			</td>
			<td width='15%'>
				<?php print __('Run Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_run_time']) . '/' . display_job_time($queue['max_run_time']);?>
			</td>
			<td width='15%'>
				<?php print __('PSUSP Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_psusp_time']) . '/' . display_job_time($queue['max_psusp_time']);?>
			</td>
			<td width='15%'>
				<?php print __('SSUSP Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_ssusp_time']) . '/' . display_job_time($queue['max_ssusp_time']);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('USUSP Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_time($queue['avg_ususp_time']) . '/' . display_job_time($queue['max_ususp_time']);?>
			</td>
			<td width='15%'>
				<?php print __('Unknown Time', 'grid');?>
			</td>
			<td width='65%' colspan='5'>
				<?php print display_job_time($queue['avg_unkwn_time']) . '/' . display_job_time($queue['max_unkwn_time']);?>
			</td>
		</tr>
		<?php

		if (trim($queue['userShares']) != '' || trim($queue['fairshareQueues']) != '' || $queue['slotPool'] > 0) {
			html_end_box(false);

			$i = 0;
			html_start_box(__('Fairshare Attributes', 'grid'), '100%', '', '3', 'center', '');

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('User Shares', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $queue['userShares'];?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Queues', 'grid');?>
				</td>
				<td width='85%' colspan='3'>
					<?php print $queue['fairshareQueues'];?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Slot Pool', 'grid');?>
				</td>
				<td width='30%'>
					<?php print $queue['slotPool'];?>
				</td>
				<td width='15%'>
					<?php print __('Slot Shares', 'grid');?>
				</td>
				<td width='40%'>
					<?php print $queue['slotShare'];?>%
				</td>
			</tr>
			<?php
		}

		html_end_box(false);

		$i = 0;
		html_start_box(__('Scheduling Attributes', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Resource Reqs', 'grid');?>
			</td>
			<td width='85%' colspan='3'>
				<?php print $queue['resReq'];?>
			</td>
		</tr>
		<?php

		if (trim($queue['stopCond']) != '' || trim($queue['resumeCond']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Stop Condition', 'grid');?>
				</td>
				<td width='85%'>
					<?php print $queue['stopCond'];?>
				</td>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Resume Condition', 'grid');?>
				</td>
				<td width='85%'>
					<?php print $queue['resumeCond'];?>
				</td>
			</tr>
			<?php
		}

		if ($queue['chunkJobSize'] > 0) {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Chunk Size', 'grid');?>
				</td>
				<td width='90%' colspan='3'>
					<?php print $queue['chunkJobSize'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['windows']) != '' || trim($queue['windowsD']) != '') {
			html_end_box(false);

			$i = 0;
			html_start_box(__('Schedule/Dispatch Windows', 'grid'), '100%', '', '3', 'center', '');

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Run', 'grid');?>
				</td>
				<td width='40%'>
					<?php print $queue['windows'];?>
				</td>
				<td width='15%'>
					<?php print __('Dispatch', 'grid');?>
				</td>
				<td width='40%'>
					<?php print $queue['windowsD'];?>
				</td>
			</tr>
			<?php
		}

		html_end_box(false);

		$i = 0;
		html_start_box(__('Job Limits', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Minimum Slot Limit', 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['minProcLimit']);?>
			</td>
			<td width='15%'>
				<?php print __('Default Slot Limit', 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['defProcLimit']);?>
			</td>
			<td width='15%'>
				<?php print __('Maximum Slot Limit', 'grid');?>
			</td>
			<td width='35%' colspan='3'>
				<?php print grid_print_limit($queue['procLimit']);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Max CPU Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['rlimit_max_cpu'];?>
			</td>
			<td width='15%'>
				<?php print __('Max Execution Time', 'grid');?>
			</td>
			<td width='10%'>
				<?php print $queue['rlimit_max_wallt'];?>
			</td>
			<td width='15%'>
				<?php print __('Max Swap', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_swap'], 3);?>
			</td>
			<td width='15%'>
				<?php print __('Max File Size', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_fsize'], 3);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Max Data Size', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_data'], 3);?>
			</td>
			<td width='15%'>
				<?php print __('Max Stack Size', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_stack'], 3);?>
			</td>
			<td width='15%'>
				<?php print __('Max Core Size', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_core'], 3);?>
			</td>
			<td width='15%'>
				<?php print __('Max RSS Size', 'grid');?>
			</td>
			<td width='10%'>
				<?php print display_job_memory($queue['rlimit_max_rss'], 3);?>
			</td>
		</tr>
		<?php

		html_end_box(false);

		$i = 0;
		html_start_box('Checkpoint/Restart Parameters', '100%', '', '3', 'center', '');

		if (trim($queue['preCmd']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Pre-Exec', 'grid');?>
				</td>
				<td width='85%' colspan='7'>
					<?php print $queue['preCmd'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['postCmd']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Post-Exec', 'grid');?>
				</td>
				<td width='85%' colspan='7'>
					<?php print $queue['postCmd'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['jobStarter']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Job Starter', 'grid');?>
				</td>
				<td width='85%' colspan='7'>
					<?php print $queue['jobStarter'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['suspendActCmd']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Suspend Command', 'grid');?>
				</td>
				<td width='85%' colspan='7'>
					<?php print $queue['suspendActCmd'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['resumeActCmd']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Resume Command', 'grid');?>
				</td>
				<td width='85%' colspan='7'>
					<?php print $queue['resumeActCmd'];?>
				</td>
			</tr>
			<?php
		}

		if (trim($queue['terminateActCmd']) != '') {
			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Terminate Command', 'grid');?>
				</td>
				<td width='85%' colspan='5'>
					<?php print $queue['terminateActCmd'];?>
				</td>
			</tr>
			<?php
		}

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Checkpoint Directory', 'grid');?>
			</td>
			<td width='35%'>
				<?php print $queue['chkpntDir'];?>
			</td>
			<td width='15%'>
				<?php print __('Checkpoint Period', 'grid');?>
			</td>
			<td width='35%'>
				<?php print grid_print_limit($queue['chkpntPeriod']);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Requeue Exit Values', 'grid');?>
			</td>
			<td width='35%'>
				<?php print $queue['requeueEValues'];?>
			</td>
			<td width='15%'>
				<?php print __('Max Requeue Times', 'grid');?>
			</td>
			<td width='35%'>
				<?php print ($queue['maxJobRequeue'] == INF_INT ? 'Unlimited':$queue['maxJobRequeue']) ;?>
			</td>
		</tr>
		<?php

		if (trim($queue['sndJobsTo']) != '' || trim($queue['rcvJobsFrom'])) {
			html_end_box(false);

			$i = 0;
			html_start_box(__('MultiCluster Settings', 'grid'), '100%', '', '3', 'center', '');

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Send Jobs To', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $queue['sndJobsTo'];?>
				</td>
				<td width='15%'>
					<?php print __('Receive Jobs From', 'grid');?>
				</td>
				<td width='35%'>
					<?php print $queue['rcvJobsFrom'];?>
				</td>
			</tr>
			<?php
		}

		html_end_box(false);

		$i = 0;
		html_start_box(__('Thresholds', 'grid'), '100%', '', '3', 'center', '');

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Underrun Condition', 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['underRCond']);?>
			</td>
			<td width='15%'>
				<?php print __('Overrun Condition', 'grid');?>
			</td>
			<td width='10%'>
				<?php print grid_print_limit($queue['overRCond']);?>
			</td>
			<td width='15%'>
				<?php print __('Idle Effic', 'grid');?>
			</td>
			<td width='35%'>
				<?php print grid_print_limit($queue['idleCond']);?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Underrun Jobs', 'grid');?>
			</td>
			<td width='5%'>
				<?php print $queue['underRJobs'];?>
			</td>
			<td width='15%'>
				<?php print __('Overrun Jobs', 'grid');?>
			</td>
			<td width='5%'>
				<?php print $queue['overRJobs'];?>
			</td>
			<td width='15%'>
				<?php print __('Idle Jobs', 'grid');?>
			</td>
			<td width='45%'>
				<?php print $queue['idleJobs'];?>
			</td>
		</tr>
		<?php

		form_alternate_row();?>
			<td width='15%'>
				<?php print __('Warning Time Period', 'grid');?>
			</td>
			<td width='5%'>
				<?php print grid_print_limit($queue['warningTimePeriod']);?>
			</td>
			<td width='15%'>
				<?php print __('Warning Action', 'grid');?>
			</td>
			<td width='65%' colspan='3'>
				<?php print $queue['warningAction'];?>
			</td>
		</tr>
		<?php

		if (trim($queue['mandExtSched']) != '' || trim($queue['defExtSched']) != '') {
			html_end_box(false);

			$i = 0;
			html_start_box(__('External Scheduler', 'grid'), '100%', '', '3', 'center', '');

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Mandatory Options', 'grid');?>
				</td>
				<td width='85%'>
					<?php print $queue['mandExtSched'];?>
				</td>
			</tr>
			<?php

			form_alternate_row();?>
				<td width='15%'>
					<?php print __('Default Options', 'grid');?>
				</td>
				<td width='85%'>
					<?php print $queue['defExtSched'];?>
				</td>
			</tr>
			<?php
		}

		html_end_box(false);
	}
}

function grid_view_queue_users(&$queue) {
}

function form_action_host() {
	global $config, $grid_host_control_actions;

	$count_ok = 0;
	$count_fail = 0;
	$action_level = 'host';
	$message = '';

	if (isset_request_var('command') && get_request_var('command') == 'goback') {
		header('Location: grid_bqueues.php?action=viewqueue&tab=hosts');
		exit;
	}

	debug_log_clear('grid_admin');
	debug_log_clear('grid_admin_ok');
	debug_log_clear('grid_admin_failed');

	if (isset_request_var('selected_items') && read_config_option('grid_management_clusters') == 'on') {
		form_input_validate(trim(get_request_var('message')), 'message', '', false, 'error_mandatory_input_field');
	}

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items') && !isset($_SESSION['sess_error_fields']['message'])) {
		if (isset_request_var('message') && trim(get_request_var('message')) != '') {
			$message = "'" . trim(get_request_var('message')) . "' - by RTM User '" . get_username($_SESSION['sess_user_id']) . "'";
		} else {
			$message = '';
		}

		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));
		$selected_items_whole = sanitize_unserialize_selected_items(get_request_var('selected_items_whole'));

		if ($selected_items_whole != false) {
		//print_r($selected_items_whole);
		$json_return_format = sorting_json_format($selected_items_whole, $message, $action_level); //sort the variables into required format
		}

		if (get_request_var('drp_action') == '1') { /* Open Host  */
			$host_action = 'open';
		} else if (get_request_var('drp_action') == '2') { /* Close Host */
			$host_action = 'close';
		}

		$advocate_key = session_auth();

		$json_host_info = array (
			'key' => $advocate_key,
			'action' => $host_action,
			'target' => $json_return_format,
		);

		$output = json_encode($json_host_info);

		$curl_output =  exec_curl($action_level, $output); //pass to advocate for processing

		if ($curl_output['http_code'] == 400) {
			raise_message(134);
		} else if ($curl_output['http_code'] == 500) {
			raise_message(135);
		} else {
			if ($curl_output['http_code'] == 200) {
				$log_action = $grid_host_control_actions[get_request_var('drp_action')];
				$json_output = json_decode($output);
				$username_log = get_username($_SESSION['sess_user_id']);
				foreach ($json_output->target as $target) {
					$action_message = get_request_var('message');
					cacti_log("Cluster '{$target->name}', {$log_action} by '{$username_log}', comment: '{$action_message}'.", false, 'LSFCONTROL');
				}
			} else {
				raise_message(136);
			}
		}

		$content_response = $curl_output['content']; //return response from advocate in json format

		$json_decode_content_response = json_decode($content_response,true);

		$rsp_content = $json_decode_content_response['rsp'];

		if(is_array($rsp_content) && count($rsp_content) >0){
		for ($k=0;$k<count($rsp_content);$k++) {
			$key_sort[$k] = $rsp_content[$k]['clusterid'];
		}

		asort($key_sort);

		$output_message='';
		foreach( $key_sort as $key => $val) {
			foreach ($rsp_content as $key_rsp_content => $value) {
				if ($key_rsp_content == $key) {
					if ($value['status_code'] == 0) {
						$return_status = 'OK';
						$count_ok ++;
					} else {
						$count_fail ++;
						$return_status = 'Failed. Status Code: '.$value['status_code'];
					}
					$message='Status:' . $return_status . ' - Cluster Name:' . grid_get_clustername($value['clusterid']) . ' - '.$value['status_message'].'<br/>';
					$output_message=$output_message.$message;
				}
			}
		}
		if($count_fail>0)
			raise_message('mymessage', $output_message, MESSAGE_LEVEL_ERROR);
		else
			raise_message('mymessage', $output_message, MESSAGE_LEVEL_INFO);
		}
		header('Location: grid_bqueues.php?action=viewqueue&tab=hosts');
		exit;
	}

	/* setup some variables */
	$host_list = '';
	$i = 0;

	if (isset_request_var('selected_items') && isset($_SESSION['sess_error_fields']['message'])) {
		$selected_items_whole = sanitize_unserialize_selected_items(get_request_var('selected_items_whole'));

		if ($selected_items_whole != false) {
			foreach ($selected_items_whole as $selected_item) {
				$host_whole_array[$i] = $selected_item;
				$host_details = explode(':',$selected_item);

				input_validate_input_number($host_details[1]);

				$host_list .= '<li>' . __('Host: %s from Cluster Name %s', $host_details[0], grid_get_clustername($host_details[1]), 'grid') . '</li>';

				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];

				$i++;
			}
		}
	} else {
		foreach ($_POST as $key => $value) {
			if (strncmp($key, 'chk_', '4') == 0) {
				$key = str_replace('@', '.', $key);
				$host_whole_array[$i] = substr($key, 4);
				$host_details = explode(':',substr($key, 4));

				/* ================= input validation ================= */
				input_validate_input_number($host_details[1]);
				/* ================= input validation ================= */

				$host_list .= '<li>' . __('Host: %s from Cluster Name %s', $host_details[0], grid_get_clustername($host_details[1]), 'grid') . '</li>';

				$host_array[$i] = $host_details[1];
				$host_array_hostname[$i] = $host_details[0];

				$i++;
			}
		}
	}

	general_header();

	html_start_box($grid_host_control_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	form_start('grid_bqueues.php?action=viewqueue&tab=hosts');

	switch(get_request_var('drp_action')) {
		case '1':$action = 'open';
			break;
		case '2':$action = 'close';
			break;
	}

	if (!empty($host_array)) {
		print " <tr>
			<td class='textArea'>
				<p>" . __('Are you sure you want to %s the following Host(s)?', $action, 'grid') . "</p>
				<div class='itemlist'><ul>$host_list</ul></div></td></tr>
			<tr>
			<td>" . __('Comments that are appended to LSF with the -C option.', 'grid');

			if (read_config_option("grid_management_clusters") != "on") {
				print '<br>' . __('Leave BLANK if no comment is required.', 'grid');
			}

			$username = get_username($_SESSION['sess_user_id']);

			print '<br>' . __('&lt;RTM&lt; %s &gt;&gt; will be appended after your comments.', 'grid') . '</td>';

			print '<td>	<input ';
			if (isset($_SESSION['sess_error_fields']['message'])) {
				print "class='txtErrorTextBox'";
				unset($_SESSION['sess_error_fields']['message']);
			}
			print "type=text name='message' col='255' size='40' maxlength='512'></td>
			</tr>
			<tr>
			<td class='deviceDown' colspan=3>
				" . __('NOTE: Please wait for the next polling cycle to see the changes on RTM after confirmation.', 'grid') . "
			</td>
		</tr>";
	}

	if (!isset($host_array)) {
		raise_message(40);
		header('Location: grid_bqueues.php?header=false');
		exit;
	} else {
		$save_html = "<input type='submit' value='" . __esc('Yes', 'grid') . "'>";
		$button_false = __esc('No', 'grid');
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='command' value=''>
			<input type='hidden' name='selected_items' value='" . (isset($host_array) ? serialize($host_array) : '') . "'>
			<input type='hidden' name='selected_items_hostname' value='".(isset($host_array_hostname) ? serialize($host_array_hostname) : '') . "'>
			<input type='hidden' name='selected_items_whole' value='".(isset($host_whole_array) ? serialize($host_whole_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			<input type='button' value='" . $button_false ."' alt='' onClick='cactiReturnTo();'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	bottom_footer();
}

function grid_view_queue_hosts(&$queue) {
}
