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
include_once($config['library_path'] . '/rtm_functions.php');
include_once('./plugins/heuristics/functions.php');
include_once('./plugins/heuristics/heuristics_webapi.php');
include_once('./plugins/grid/include/grid_messages.php');
include_once('./plugins/grid/lib/grid_partitioning.php');
include_once('./plugins/grid/lib/grid_validate.php');
include_once('./plugins/grid/lib/grid_functions.php');

set_default_action();

if (!isset($_SESSION['sess_heur_tab'])) {
	$_SESSION['sess_heur_tab'] = 0;  //0 - tab general, 1 - tab queue/project
}

$views = array(
	'summary'   => array('name' => __('User Summary', 'heuristics'),     'display' => 'true', 'function' => 'show_cluster_stats'),
	'queues'    => array('name' => __('Queue/Project', 'heuristics'),    'display' => 'true', 'function' => 'show_queue_stats'),
	'tput'      => array('name' => __('Daily Throughput', 'heuristics'), 'display' => 'true', 'function' => 'show_daily_stats'),
	'checkouts' => array('name' => __('Feature Checkouts', 'heuristics'),   'display' => 'true', 'function' => 'show_license_checkouts'),
	'pendr'     => array('name' => __('Pending Reasons', 'heuristics'),  'display' => 'true', 'function' => 'show_pending_reasons'),
	'exitanal'  => array('name' => __('Exit Analysis', 'heuristics'),    'display' => 'true', 'function' => 'show_exit_stats'),
	'health'    => array('name' => __('Cluster Health', 'heuristics'),   'display' => 'true', 'function' => 'show_cluster_health')
);

$charts = array(
	'memslots'  => array('name' => __('Free Memory Slots', 'heuristics'), 'display' => 'true',  'fusionfile' => 'Column3D'),
	'memhist'   => array('name' => __('Memory Histogram', 'heuristics'),  'display' => 'true',  'fusionfile' => 'Column3D'),
	'runhist'   => array('name' => __('Runtime Histogram', 'heuristics'), 'display' => 'true',  'fusionfile' => 'Column3D'),
	'jobstats'  => array('name' => __('Job Statistics', 'heuristics'),    'display' => 'true',  'fusionfile' => 'Pie3D'),
	'timestats' => array('name' => __('Time Distribution', 'heuristics'), 'display' => 'true',  'fusionfile' => 'Pie3D'),
//	'queuefree' => array('name' => 'Queue Utilization', 'display' => 'false', 'fusionfile' => 'StackedBar3D'),
);

// get charts from plugins
$views  = api_plugin_hook_function('heuristics_view_list', $views);
//$charts = api_plugin_hook_function('heuristics_chart_list', $charts);

if (cacti_sizeof($charts)) {
	foreach($charts as $id => $chart) {
		$charts_fusion[] = $chart['fusionfile'];
	}
}

switch(get_request_var('action')) {
	case 'ajax_jobstats':
		today_pie();
		break;
	case 'ajax_memhist':
		draw_memory_histogram();
		break;
	case 'ajax_memslots':
		draw_free_memory_slots();
		break;
	case 'ajax_timestats':
		today_sum_time_pie();
		break;
	case 'ajax_runhist':
		draw_runtime();
		break;
	case 'ajax_visibility':
		change_pending_reason_visibility();
		break;
	case 'ajax_queuestats':
		draw_queue_stats();
		break;
	case 'ajaxcharts':
		heuristics_process_input_variables();
		draw_trend_chart();
		remove_graphs_from_session();
		break;
	case 'ajaxsave':
		heuristics_ajax_save();
		break;
	case 'ajaxsearch':
		ajax_search();
		break;
	case 'ajaxmcharts':
		heuristics_process_input_variables();
		cadcmd_matrix_trend_chart();
		break;
	case 'ajaxview':
		heuristics_show_panel();
		break;
	case 'ajaxtab':
		if (isset_request_var('tab')) {
			input_validate_input_regex(get_request_var('tab'), '^([a-zA-Z0-9_]+)$');
		}

		$_SESSION['sess_heur_tab'] = get_request_var('tab');

		break;
	case 'ajaxdb':
		heuristics_dashboard_ajax();
		break;
	case 'ajaxupdate':
		heuristics_process_input_variables();
		break;
	case 'export':
		heuristics_export_panel();
		break;
	default:
		heuristics_dashboard();
		break;
}

