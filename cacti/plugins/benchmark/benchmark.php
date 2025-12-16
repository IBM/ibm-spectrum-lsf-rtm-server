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
include_once('./plugins/grid/include/grid_constants.php');
include_once('./plugins/grid/lib/grid_functions.php');
include_once('./plugins/benchmark/functions.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/rtm_functions.php');

$benchmark_actions = array(
	1 => __('Delete'),
	2 => __('Enable'),
	3 => __('Disable'),
	4 => __('Clear Counters')
);

set_default_action();

$_SESSION['sess_nav_level_cache'] = array();

/* changing to cluster tz if it is requested by user */
$tz_is_changed = false;
$orig_tz = date_default_timezone_get();

if (get_request_var('action') == 'view') {
	$title = __('IBM Spectrum LSF RTM - Benchmark Job Details');
	top_header();
	view_benchmark_details();
	bottom_footer();
}elseif (get_request_var('action') == 'edit') {
	$title = __('IBM Spectrum LSF RTM - Benchmark Job Management');
	top_header();

	edit_benchmark();
	bottom_footer();
} else if (get_request_var('action') == 'actions') {
	form_actions();
} else if (get_request_var('action') == 'save') {
	save_benchmark();
} else {
	$title = __('IBM Spectrum LSF RTM - Benchmark Job Management');
	view_benchmarks();
}

function form_actions() {
	global $config, $benchmark_actions, $fields_benchmark_edit;

	/* if we are to save this form, instead of display it */
	if (isset_request_var('selected_items')) {

		$selected_items = sanitize_unserialize_selected_items(get_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_request_var('drp_action') == '1') { /* delete */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					grid_benchmark_remove($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '2') { /* enable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					grid_benchmark_enable($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '3') { /* disable */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					grid_benchmark_disable($selected_items[$i]);
				}
			} else if (get_request_var('drp_action') == '4') { /* clear counters */
				for ($i=0; $i<count($selected_items); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($selected_items[$i]);
					/* ==================================================== */

					grid_benchmark_clear($selected_items[$i]);
				}
			}
		}

		header('Location: benchmark.php');
		exit;
	}

	/* setup some variables */
	$benchmark_list = ''; $benchmark_array = array();

	/* loop through each of the benchmarks selected on the previous page and get more info about them */
	foreach ($_POST as $var => $val) {
		if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$benchmark_info = db_fetch_cell_prepared('SELECT benchmark_name
				FROM grid_clusters_benchmarks
				WHERE benchmark_id = ?',
				array($matches[1]));

			$benchmark_list .= '<li>' . html_escape($benchmark_info) . '</li>';
			$benchmark_array[] = $matches[1];
		}
	}

	top_header();

	form_start('benchmark.php');

	html_start_box($benchmark_actions[get_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($benchmark_array)) {
		if (get_request_var('drp_action') == '1') { /* delete */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the following Grid Benchmark(s)') . "</p>
					<ul>$benchmark_list</ul>
				</td>
			</tr>";

			$title = __('Delete Benchmark(s)');
		} else if (get_request_var('drp_action') == '2') { /* enable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Enable the following Grid Benchmark(s)') . "</p>
					<ul>$benchmark_list</ul>
				</td>
			</tr>";

			$title = __('Enable Benchmark(s)');
		} else if (get_request_var('drp_action') == '3') { /* disable */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Disable the following Grid Benchmark(s)') . "</p>
					<ul>$benchmark_list</ul>
				</td>
			</tr>";

			$title = __('Disable Benchmark(s)');
		} else if (get_request_var('drp_action') == '4') { /* clear counters */
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Clear Counters for the following Grid Benchmark(s)') . "</p>
					<ul>$benchmark_list</ul>
				</td>
			</tr>";

			$title = __('Clear Counters for Benchmark(s)');
		}

		 $save_html = "<input type='button' value='" . __('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue') . "' title='" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "'";
	}else{
		raise_message('bench40', __('You must select at least one Grid Benchmark.'), MESSAGE_LEVEL_ERROR);
		header('Location: benchmark.php?header=false');
		exit;
	}

	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($benchmark_array) ? serialize($benchmark_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();
	form_end();

	bottom_footer();
}


/* Saves the information from the benchmark edit form */
function save_benchmark() {
	if ((isset_request_var('save_component_benchmark')) && (isempty_request_var('add_dq_y'))) {
		input_validate_input_number(get_request_var('benchmark_id'));
		$benchmark_id = api_grid_benchmark_save(get_request_var('benchmark_id'));

		if (is_error_message()) {
			header('Location: benchmark.php?action=edit&header=false&&benchmark_id=' . (empty($benchmark_id) ? get_request_var('benchmark_id') : $benchmark_id));
		} else {
			header('Location: benchmark.php?header=false');
		}
	}
}

function is_tasknum_valid($task_num_in_job) {
	if (0 == strlen($task_num_in_job)) {
		return true;
	}

	$task_num_array = explode(",", $task_num_in_job);
	$v1 = intval($task_num_array[0]);
	$v2 = $v1;
	if (count($task_num_array) > 1) {
		$v2 = intval($task_num_array[1]);
	}

	if ($v1 <= 0 ||
		$v2 <= 0) {
		raise_message('benchmark_numtask_should_be_greater_than_zero');
		return false;
	}
	if ($v1 > $v2) {
		raise_message('benchmark_numtask_min_greater_than_max');
		return false;
	}
	return true;
}

function api_grid_benchmark_save($benchmark_id) {
	if ($benchmark_id) {
		$save['benchmark_id'] = $benchmark_id;
	}else{
		$save['benchmark_id'] = '';
	}

	$save['benchmark_name'] = form_input_validate(get_request_var('benchmark_name'), 'benchmark_name', '^[A-Za-z0-9\._\\\@\ -]+$', false, 'field_input_save_1');
	$save['command']        = form_input_validate(get_request_var('command'), 'command', '', false,  3);
	$save['res_req']        = form_input_validate(get_request_var('res_req'), 'res_req', '', true,  3);
	$save['queue']          = form_input_validate(get_request_var('queue'), 'queue', '', false,  3);
	$save['username']       = form_input_validate(get_request_var('username'), 'username', '', false,  3);
	$save['run_interval']   = form_input_validate(get_request_var('run_interval'), 'run_interval', '', false,  3);
	$save['user_group']     = form_input_validate(get_request_var('user_group'), 'user_group', '^[A-Za-z0-9\._\\\@\ -]+$', true,  'field_input_save_1');
	$save['enabled']        = form_input_validate(get_request_var('enabled'), 'enabled', '', false,  3);
	$save['project']        = form_input_validate(get_request_var('project'), 'project', '^[A-Za-z0-9\._\\\@\ -]+$', true,  'field_input_save_1');
	$save['host_spec']      = form_input_validate(get_request_var('host_spec'), 'host_spec', '', true,  3);
	$save['clusterid']      = form_input_validate(get_request_var('clusterid'), 'clusterid', '', false,  3);
	$save['max_runtime']    = form_input_validate(get_request_var('max_runtime'), 'max_runtime', '[0-9]', false,  3);
	$save['alert_time']     = form_input_validate(get_request_var('alert_time'), 'alert_time', '[0-9]', false,  3);
	$save['warn_time']      = form_input_validate(get_request_var('warn_time'), 'warn_time', '[0-9]', false,  3);
	$save['task_num_in_job']	= form_input_validate(get_request_var('task_num_in_job'), 'task_num_in_job', '^[0-9]+(,[0-9])*$', true,  3);
	$save['exclusive_job']	= isset_request_var('exclusive_job') ? 'on': '';

	if (!isset($_SESSION['sess_error_fields']['task_num_in_job']) &&
		!is_tasknum_valid($save['task_num_in_job'])) {
		$_SESSION['sess_error_fields']['task_num_in_job'] = 'task_num_in_job';
	}

	if (round($save['max_runtime']) > 4*3600) {
		raise_message('benchmark_maxrun_over_4_hour');
		$_SESSION['sess_error_fields']['max_runtime'] = get_request_var('max_runtime');
		$_SESSION['sess_field_values']['max_runtime'] = get_request_var('max_runtime');
	}

	if( !($save['max_runtime'] >= $save['alert_time'] &&  $save['alert_time'] >= $save['warn_time'])) {
		raise_message('benchmark_maxrun_alert_warn_time');
		$_SESSION['sess_error_fields']['max_runtime'] = get_request_var('max_runtime');
		$_SESSION['sess_field_values']['max_runtime'] = get_request_var('max_runtime');

		$_SESSION['sess_error_fields']['alert_time'] = get_request_var('alert_time');
		$_SESSION['sess_field_values']['alert_time'] = get_request_var('alert_time');

		$_SESSION['sess_error_fields']['warn_time'] = get_request_var('warn_time');
		$_SESSION['sess_field_values']['warn_time'] = get_request_var('warn_time');
	}

	if ($save['res_req'] == '') {
		$save['res_req'] = 'select[type==any]';
	}

	//if the benchmark is set to be disabled, need to change status to disabled either
	if ($save['enabled'] == '0') {
		$save['status'] = 4;
	}

	if ($benchmark_id) {
		$benchmark_cur = db_fetch_row_prepared('SELECT *
			FROM grid_clusters_benchmarks
			WHERE benchmark_id = ?',
			array($benchmark_id));

		if (cacti_sizeof($benchmark_cur)) {
			//if the benchmark is set to be enabled and its current status is disabled, need to change status to unkown.
			if ($save['enabled'] == '1' && $benchmark_cur['status'] == '4' ) {
				$save['status'] = 99;
			}
		}
	}

	if ($save['benchmark_name'] == '') {
		$_SESSION['sess_error_fields']['benchmark_name'] = 'benchmark_name';
	}

	$benchmark_id = 0;
	if (!is_error_message()) {
		$benchmark_id = sql_save($save, 'grid_clusters_benchmarks', 'benchmark_id');

		if ($benchmark_id) {
			raise_message(1);
		} else {
			raise_message(1);
		}
	}

	return $benchmark_id;
}

/* Displays the edit benchmark form */
function edit_benchmark() {
	global $fields_benchmark_edit;

	/* ================= input validation ================= */
	get_filter_request_var('benchmark_id');
	/* ==================================================== */

	if (!isempty_request_var('benchmark_id')) {
		// Don't allow user to directly modify URL to edit a benchmark from a deleted cluster
		$count = db_fetch_cell_prepared('SELECT count(*)
			FROM grid_clusters_benchmarks
			WHERE benchmark_id = ?
			AND clusterid IN (SELECT clusterid FROM grid_clusters)',
			array(get_request_var('benchmark_id')));

		if ($count == 0) {
			exit;
		} else {
			$benchmark = db_fetch_row_prepared('SELECT *
				FROM grid_clusters_benchmarks
				WHERE benchmark_id = ?',
				array(get_request_var('benchmark_id')));

			$header_label = __esc('Benchmark Edit [edit: %s]', $benchmark['benchmark_name']);
		}
	} else {
			$header_label = __('Benchmark Edit [new]');
			$benchmark['run_interval'] = read_config_option('benchmark_run_interval_preset',true);
			$benchmark['max_runtime'] = read_config_option('benchmark_runtime_preset',true);
			$benchmark['warn_time'] = read_config_option('benchmark_warn_preset',true);
			$benchmark['alert_time'] = read_config_option('benchmark_alert_preset',true);
	}

	// Ensure we only start displaying the correct queues, based on the clusterid
	if (!isempty_request_var('benchmark_id')) {
		$first_queue_cell = db_fetch_cell_prepared('SELECT clusterid
			FROM grid_clusters_benchmarks
			WHERE benchmark_id = ?',
			array(get_request_var('benchmark_id')));

		$first_queue_queues = db_fetch_assoc_prepared('SELECT queuename
			FROM grid_queues
			WHERE clusterid = ?
			ORDER BY queuename ASC',
			array($first_queue_cell));
	} else {
		$first_queue_cell = db_fetch_cell('SELECT clusterid
			FROM grid_clusters
			ORDER BY clusterid DESC');

		$first_queue_queues = db_fetch_assoc_prepared('SELECT queuename
			FROM grid_queues
			WHERE clusterid = ?',
			array($first_queue_cell));
	}

	$queue_array = array();
	if (cacti_sizeof($first_queue_queues)) {
		foreach ($first_queue_queues as $first_queue_queue) {
			$queue_array[$first_queue_queue['queuename']] = $first_queue_queue['queuename'];
		}
	}

	$fields_benchmark_edit['queue']['array'] = $queue_array;

	form_start('benchmark.php');

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		array(
			'config' => array('no_form_tag' => true),
			'fields' => inject_form_variables($fields_benchmark_edit, (isset($benchmark) ? $benchmark : array()))
		)
	);

	html_end_box(true, true);

	$queue_rows = db_fetch_assoc('SELECT clusterid, queuename
		FROM grid_queues
		ORDER BY clusterid DESC, queuename ASC');

	$queue_strings = array();
	if (cacti_sizeof($queue_rows)) {
		foreach ($queue_rows as $queue_row) {
			$cluster_id = $queue_row['clusterid'];
			$queue_name = $queue_row['queuename'];

			if (!array_key_exists($cluster_id, $queue_strings)) {
				$queue_strings[$cluster_id] = "clusters[" . $cluster_id . "] = ['$queue_name'";
			} else {
				$queue_strings[$cluster_id] .=  ", '$queue_name'";
			}
		}

		foreach ($queue_strings as $key => &$val) {
			// Ignore the key, update the value
			$val = $val . "];";
		}
	}

	?>
	<script type='text/javascript'>
	$('#clusterid').change(function(event) {
		var cluster = $('#clusterid').val();
		var queue   = $('#queue').get(0);

		var clusters = new Array();

		<?php
		if (cacti_sizeof($queue_strings)) {
			foreach ($queue_strings as $key => &$val) {
				print $val . "\n";
			}
		}
		?>

		var clusterid = cluster;
		while (queue.firstChild) {
			queue.removeChild(queue.firstChild);
		}

		var queue_names = clusters[clusterid]; // Now we have an array of queue names
		for (i = 0; i < queue_names.length; i++) {
			queue.options[i] = new Option(queue_names[i], queue_names[i]);
		}
	});
	</script>
	<?php

	form_save_button('benchmark.php', '', 'benchmark_id');
}

function grid_benchmark_remove($benchmark_id) {
	/*1. delete associated graph and data source*/
	$clusterid = db_fetch_cell_prepared('SELECT clusterid
		FROM grid_clusters_benchmarks
		WHERE benchmark_id = ?',
		array($benchmark_id));

	$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	$local_graph_ids_1 = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
		FROM graph_local
		INNER JOIN graph_templates
		ON graph_templates.id=graph_local.graph_template_id
		WHERE graph_local.host_id = ?
		AND graph_templates.hash='6c8c4a6c27c0b73866f11748e17f5ed2'
		AND graph_local.snmp_index= ?",
		array($cacti_host, $benchmark_id));

	$i = 0;
	$local_graph_ids = array();
	if (cacti_sizeof($local_graph_ids_1)) {
		foreach ($local_graph_ids_1 as $local_graph_id) {
			$local_graph_ids[$i] = $local_graph_id['id'];
			$i++;
		}
	}

	if (isset($local_graph_ids) && cacti_sizeof($local_graph_ids)) {
		$data_sources = array_rekey(
			db_fetch_assoc("SELECT data_template_data.local_data_id
				FROM (data_template_rrd, data_template_data, graph_templates_item)
				WHERE graph_templates_item.task_item_id=data_template_rrd.id
				AND data_template_rrd.local_data_id=data_template_data.local_data_id
				AND " . array_to_sql_or($local_graph_ids, 'graph_templates_item.local_graph_id') . "
				AND data_template_data.local_data_id > 0"),
			'local_data_id', 'local_data_id'
		);
	}

	if (isset($data_sources) && cacti_sizeof($data_sources)) {
		api_data_source_remove_multi($data_sources);
		api_plugin_hook_function('data_source_remove', $data_sources);
	}

	if (isset($local_graph_ids) && cacti_sizeof($local_graph_ids)) {
		api_graph_remove_multi($local_graph_ids);
		api_plugin_hook_function('graphs_remove', $local_graph_ids);
	}

	/*2. delete the benmark id*/
	db_execute_prepared('DELETE FROM grid_clusters_benchmarks
		WHERE benchmark_id = ?',
		array($benchmark_id));

	/*3. delete the benmark summary data*/
	db_execute_prepared('DELETE FROM grid_clusters_benchmark_summary
		WHERE benchmark_id = ?',
		array($benchmark_id));
}

function grid_benchmark_enable($benchmark_id) {
	// Only benchmarks for clusters known to RTM can be enabled
	db_execute_prepared('UPDATE grid_clusters_benchmarks
		SET enabled = 1, status=99
		WHERE benchmark_id = ?
		AND clusterid IN (SELECT clusterid FROM grid_clusters)',
		array($benchmark_id));
}

function grid_benchmark_disable($benchmark_id) {
	db_execute_prepared('UPDATE grid_clusters_benchmarks
		SET enabled=0, status=4
		WHERE benchmark_id = ?',
		array($benchmark_id));
}

function grid_benchmark_clear($benchmark_id) {
	db_execute_prepared("UPDATE grid_clusters_benchmarks SET cur_time=0,
		min_time=0, max_time=0, avg_time=0,
		total_good_runs=0, total_failed_runs=0, total_errored_runs=0,
		status_fail_date='0000-00-00', status_rec_date='0000-00-00',
		status_last_error=''
		WHERE benchmark_id = ?",
		array($benchmark_id));
}

function grid_view_get_records(&$sql_where, $apply_limits = TRUE, $rows = 30, &$sql_params = array()) {
	global $_CACTI_REQUEST;
	// Job Status SQL Where
	benchmark_status_where($sql_where);

	/* cluster id sql where */
	if (get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') .' benchmark_name LIKE ?';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();

	$sql_query = "SELECT a.*, FROM_UNIXTIME(last_runtime) AS last_runtime_text
		FROM grid_clusters_benchmarks AS a
		$sql_where
		$sql_order";

	//echo $sql_query . "</br>";

	if ($apply_limits) {
		$sql_query .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function build_job_config_display_array() {
	$display_text = array();
	$display_text += array('nosort'             => array('display' => __('Actions'),        'align' => 'left'));
	$display_text += array('benchmark_name'     => array('display' => __('Benchmark Name'), 'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('benchmark_id'       => array('display' => __('ID'),             'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('clusterid'          => array('display' => __('Cluster Name'),   'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('status'             => array('display' => __('Status'),         'align' => 'left',  'sort' => 'ASC', 'tip' => __('Status is sorted by its code')));
	$display_text += array('run_interval'       => array('display' => __('Run Interval'),   'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('cur_time'           => array('display' => __('Last'),           'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('avg_time'           => array('display' => __('Average'),        'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('min_time'           => array('display' => __('Min'),            'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('max_time'           => array('display' => __('Max'),            'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_good_runs'    => array('display' => __('Done'),           'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_failed_runs'  => array('display' => __('Exited'),         'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('total_errored_runs' => array('display' => __('Failed'),         'align' => 'right', 'sort' => 'DESC'));
	$display_text += array('queue'              => array('display' => __('Queue'),          'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('project'            => array('display' => __('Project'),        'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('username'           => array('display' => __('User'),           'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('user_group'         => array('display' => __('Group'),          'align' => 'left',  'sort' => 'ASC'));
	$display_text += array('last_runtime'       => array('display' => __('Last Runtime'),   'align' => 'right', 'sort' => 'DESC'));

	return $display_text;
}

function job_config_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $benchmark_text_status;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='benchmark.php'>
			<table class='filterTable'>
				<tr>
					<td>
						Cluster
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>>All</option>
							<?php
							$clusters = grid_get_clusterlist();
							if (cacti_sizeof($clusters) > 0) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						Job Status
					</td>
					<td>
						<select id='status'>
							<option value='-1'<?php if (get_request_var('status') == '-1') {?> selected<?php }?>>All</option>
							<option value='-2'<?php if (get_request_var('status') == '-2') {?> selected<?php }?>>All Errors</option>
							<option value='-3'<?php if (get_request_var('status') == '-3') {?> selected<?php }?>>All Job Exits</option>
							<option value='-4'<?php if (get_request_var('status') == '-4') {?> selected<?php }?>>All Threshold Violations</option>
							<?php
								if (cacti_sizeof($benchmark_text_status)) {
									foreach ($benchmark_text_status as $key => $value) {
										print '<option value="' . $key . '"'; if (get_request_var('status') == $key) { print ' selected'; } print '>' . $value . '</option>';
									}
								}
							?>
						</select>
					</td>
					<td>
						Records
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
							foreach ($grid_rows_selector as $key => $value) {
								print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
							}
							?>
						</select>
					</td>
					<td>
						Refresh
					</td>
					<td>
						<select id='refresh'>
						<?php
						$max_refresh = read_config_option('grid_minimum_refresh_interval');
						foreach($grid_refresh_interval as $key => $value) {
							if ($key >= $max_refresh) {
								print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search'>
					</td>
					<td>
						<input type='button' id="clear"id="clear"  name='clear' value='Clear' title='Clear Filters'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						Search
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
	</tr>
	<?php
}

function view_benchmarks() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval;
	global $bm_run_intervals,  $minimum_user_refresh_intervals, $config, $benchmark_actions;
	$sql_params = array();

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
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => read_grid_config_option('refresh_interval'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'user' => array(
			'filter' => FILTER_CALLBACK,
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
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'benchmark_id',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_bbench');
	/* ================= input validation ================= */

	grid_set_minimum_page_refresh();

	top_header();

	?>
	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'benchmark.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&refresh=' + $('#refresh').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&status=' + $('#status').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL  = 'benchmark.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#rows, #clusterid, #refresh, #status').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php

	$debug_log = nl2br(debug_log_return('grid_admin'));
	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table class='debug'>
			<tr>
				<td>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box(__('Benchmark Management') . rtm_hover_help('grid_benchmark_jobs_panel_help.html', __esc('Learn More', 'grid')), '100%', '', '3', 'center', 'benchmark.php?action=edit');
	job_config_filter();
	html_end_box();

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	}elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	}else{
		$rows = get_request_var('rows');
	}

	$benchmark_results = grid_view_get_records($sql_where, TRUE, $rows, $sql_params);

	/* print checkbox form for validation */
	form_start('benchmark.php', 'chk');

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*) FROM grid_clusters_benchmarks AS a $sql_where", $sql_params);

	$display_text = build_job_config_display_array();

	/* generate page list */
	$nav = html_nav_bar('benchmark.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, '', __('Benchmarks'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	$disabled = false;
	html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));

	if (cacti_sizeof($benchmark_results)) {
		foreach ($benchmark_results as $benchmark) {
			form_alternate_row('line' . $benchmark['benchmark_id'], $disabled);

			$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
				FROM grid_clusters
				WHERE clusterid = ?',
				array($benchmark['clusterid']));

			if (isset($cacti_host)) {
				$local_graph_ids = db_fetch_assoc_prepared("SELECT DISTINCT graph_local.id
					FROM graph_local
					INNER JOIN graph_templates
					ON graph_templates.id=graph_local.graph_template_id
					WHERE graph_local.host_id = ?
					AND graph_templates.hash='6c8c4a6c27c0b73866f11748e17f5ed2'
					AND graph_local.snmp_index= ?",
					array($cacti_host, $benchmark['benchmark_id']));

				if (cacti_sizeof($local_graph_ids)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($local_graph_ids as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				}else{
					unset($graph_select);
				}
			}else{
				unset($graph_select);
			}

			$cluster_name = grid_get_clustername($benchmark['clusterid']);
			$hash = '6c8c4a6c27c0b73866f11748e17f5ed2';

			// It's possible that the cluster has been removed from RTM
			if ($cluster_name == "NOT FOUND") {
				?>
				<td class='nowrap' style='width:1%'>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_jobs.php?timespan=7&status=-1&filter=&benchmark_id=' . $benchmark['benchmark_id'] . '&clusterid=0');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Benchmark Results');?>'></a>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/benchmark.php?action=view&benchmark_id=' . $benchmark['benchmark_id']);?>'><img src='<?php print $config['url_path'];?>plugins/benchmark/images/view_job.gif' alt='' title='<?php print __('View Benchmark Job');?>'></a>
				<?php if (isset($graph_select)) { ?>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __('View Benchmark Graphs');?>'></a>
				<?php } ?>
				</td>
				<?php
				form_selectable_cell(filter_value($benchmark['benchmark_name'], get_request_var('filter')), $benchmark['benchmark_id'], '', 'nowrap');
			} else {
				?>
				<td class='nowrap' style='width:1%'>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/grid_benchmark_jobs.php?timespan=7&status=-1&filter=&benchmark_id=' . $benchmark['benchmark_id'] . '&clusterid=' . $benchmark['clusterid']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __('View Benchmark Results');?>'></a>
					<a class='pic' href='<?php print htmlspecialchars($config['url_path'] . 'plugins/benchmark/benchmark.php?action=view&benchmark_id=' . $benchmark['benchmark_id']);?>'><img src='<?php print $config['url_path'];?>plugins/benchmark/images/view_job.gif' alt='' title='<?php print __('View Benchmark Job');?>'></a>
				<?php if (isset($graph_select)) { ?>
					<a href='<?php print htmlspecialchars($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __('View Benchmark Graphs');?>'></a>
				<?php } ?>
				</td>
				<?php
				form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars($config['url_path'] . 'plugins/benchmark/benchmark.php?action=edit&benchmark_id=' . $benchmark['benchmark_id']) . "'>" . filter_value($benchmark['benchmark_name'], get_request_var('filter')) . '</a>', $benchmark['benchmark_id'], '', 'nowrap;');
			}

			form_selectable_cell($benchmark['benchmark_id'], $benchmark['benchmark_id'], '');
			form_selectable_cell($cluster_name, $benchmark['benchmark_id']);
			form_selectable_cell(benchmark_get_status($benchmark['status']), $benchmark['benchmark_id'], '', 'nowrap');
			form_selectable_cell($bm_run_intervals[$benchmark['run_interval']], $benchmark['benchmark_id'], '', 'nowrap');
			form_selectable_cell(bmt($benchmark['cur_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(bmt($benchmark['avg_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(bmt($benchmark['min_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(bmt($benchmark['max_time']), $benchmark['benchmark_id'], '', 'right nowrap');
			form_selectable_cell(number_format($benchmark['total_good_runs']), $benchmark['benchmark_id'], '', 'right');
			form_selectable_cell(number_format($benchmark['total_failed_runs']), $benchmark['benchmark_id'], '', 'right');
			form_selectable_cell(number_format($benchmark['total_errored_runs']), $benchmark['benchmark_id'], '', 'right');

			form_selectable_cell($benchmark['queue'], $benchmark['benchmark_id'], '');
			form_selectable_cell(($benchmark['project'] != '' ? $benchmark['project']:__('N/A')), $benchmark['benchmark_id'], '');
			form_selectable_cell($benchmark['username'], $benchmark['benchmark_id'], '');
			form_selectable_cell(($benchmark['user_group'] != '' ? $benchmark['user_group']:__('N/A')), $benchmark['benchmark_id'], '');

			if ($benchmark['last_runtime'] == 0) {
				$last_runtime_display = __('Never run');
			} else {
				$last_runtime_display = $benchmark['last_runtime_text'];
			}
			form_selectable_cell($last_runtime_display, $benchmark['benchmark_id'], '', 'right nowrap');

			form_checkbox_cell($benchmark['benchmark_name'], $benchmark['benchmark_id'], $disabled);

			form_end_row();
		}
	}else{
		print '<tr><td colspan="' . cacti_sizeof($display_text) . '"><em>' . __('No Benchmark Records Found') . '</em></td></tr>';
	}

	html_end_box();
	if (cacti_sizeof($benchmark_results)) {
		print $nav;
	}

	if (!$disabled) {
		draw_actions_dropdown($benchmark_actions);
		form_end();
	}

	bottom_footer();
}

function bmt($value) {
	return __('%d sec', number_format_i18n(round($value,1),1));
}
