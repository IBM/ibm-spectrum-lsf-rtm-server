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


function benchmark_status_where(&$sql_where) {
	/* status sql where */
	if (get_request_var('status') >= 0) {
		$sql_where = 'WHERE a.status=' . get_request_var('status');
	}elseif (get_request_var('status') == -2) {
		$sql_where = 'WHERE a.status NOT IN (0,1,2,3)';
	}elseif (get_request_var('status') == -3) {
		$sql_where = 'WHERE a.status IN (5,15,16)';
	}elseif (get_request_var('status') == -4) {
		$sql_where = 'WHERE a.status IN (2,3,15,16)';
	}
}

function benchmark_status_filter($location = 'summary') {
	global $config, $grid_rows_selector, $grid_refresh_interval, $benchmark_text_status;

	?>
	<td>
		Job Status:
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
						if ($location != 'summary') {
							if ($value != 'Disabled' && $value != 'Never Run') {
								print '<option value="' . $key . '"'; if (get_request_var('status') == $key) { print ' selected'; } print '>' . $value . '</option>';
							}
						}else{
							print '<option value="' . $key . '"'; if (get_request_var('status') == $key) { print ' selected'; } print '>' . $value . '</option>';
						}
					}
				}
			?>
		</select>
	</td>
	<?php
}

function benchmark_get_time($value) {
	return __('%s sec', number_format_i18n($value, 2));
}

function benchmark_get_exit_code($status, $reason) {
	if ($status == 0) {
	}

	if ($status >> 8 & 0xFF) {
		$exit_status = $status >> 8 & 0xFF;
		$type = __('App Exit, Code');
	}else{
		$exit_status = $status & 0x7F;
		$type = __('Signal');
	}

	if ($exit_status == 0) {
		if ($reason == 'N/A') {
			return '<span>' . __('N/A') . '</span>';
		} else {
			return "<span>$exit_status</span>";
		}
	}else{
		return "<span class='deviceDown'>$type:$exit_status</span>";
	}
}

function benchmark_get_status($status) {
	global $benchmark_text_status, $benchmark_colors;

	if ($status >= 0 && $status <= 20) {
		return "<span style='color:" . $benchmark_colors[$status] . ";font-weight:bold;'>" . $benchmark_text_status[$status] . "</span>";
	}else{
		return "<span class='deviceUnknown'>" . __('Unknown') . "</span>";
	}
}

function view_benchmark_details() {
	global $config, $bm_run_intervals;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('benchmark_id'));
	/* ==================================================== */

	$bm = db_fetch_row_prepared('SELECT *
		FROM grid_clusters_benchmarks
		WHERE benchmark_id = ?',
		array(get_request_var('benchmark_id')));

	// General Settings
	html_start_box(__('General'), '100%', '', '3', 'center', '');

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Name') . "</td>\n";
	echo "<td style='width:15%;'>" . htmlspecialchars($bm['benchmark_name']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('ID') . "</td>\n";
	echo "<td style='width:15%;'>" . $bm['benchmark_id'] . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Benchmark Type') . "</td>\n";
	echo "<td style='width:15%;'>" . __('LSF Performance') . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Cluster Name') . "</td>\n";
	echo "<td style='width:15%;'>" . grid_get_clustername($bm['clusterid']) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Run Interval') . "</td>\n";
	echo "<td style='width:15%;'>" . $bm_run_intervals[$bm['run_interval']] . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Status') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_status($bm['status']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Last Runtime') . "</td>\n";
	echo "<td style='width:15%;'>" . date('Y-m-d H:i:s', $bm['last_runtime']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Last Errored') . "</td>\n";
	echo "<td style='width:15%;'>" . $bm['status_fail_date'] . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Last Error') . "</td>\n";
	echo "<td colspan='5'>" . $bm["status_last_error"] . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Last Recovered') . "</td>\n";
	echo "<td style='width:15%;'>" . $bm['status_rec_date'] . "</td>\n";
	form_end_row();

	html_end_box();

	// Configuration Settings
	html_start_box(__('Configuration Settings'), '100%', '', '3', 'center', '');

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Queue Name') . "</td>\n";
	echo "<td style='width:15%;'>" . htmlspecialchars($bm['queue']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('User Name') . "</td>\n";
	echo "<td style='width:15%;'>" . htmlspecialchars($bm['username']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('User Group') . "</td>\n";
	echo "<td style='width:15%;'>" . ($bm['user_group'] == '' ? __('N/A'):htmlspecialchars($bm['user_group'])) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Project Name') . "</td>\n";
	echo "<td style='width:15%;'>" . ($bm['project'] == '' ? __('default'):htmlspecialchars($bm['project'])) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Host Spec') . "</td>\n";
	echo "<td style='width:15%;'>" . ($bm['host_spec'] == '' ? __('N/A'):htmlspecialchars($bm['host_spec'])) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Warning Thold') . "</td>\n";
	echo "<td style='width:15%;'>" . __('%d seconds', $bm['warn_time']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Alert Thold') . "</td>\n";
	echo "<td style='width:15%;'>" . __('%d seconds', $bm['alert_time']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Run Limit') . "</td>\n";
	echo "<td style='width:15%;'>" . __('%d seconds', $bm['max_runtime']) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Command') . "</td>\n";
	echo "<td style='width:40%;' colspan='3'>" . htmlspecialchars($bm['command']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('ResReq') . "</td>\n";
	echo "<td style='width:40%;' colspan='3'>" . htmlspecialchars($bm['res_req']) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Task Number') . "</td>\n";
	echo "<td style='width:15%;'>" . ($bm['task_num_in_job'] == '' ? __('N/A'):htmlspecialchars($bm['task_num_in_job'])) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Exclusive Job') . "</td>\n";
	echo "<td style='width:65%;' colspan='5'>" . ($bm['exclusive_job'] == 'on' ? __('Yes'):__('No')) . "</td>\n";
	form_end_row();

	html_end_box();

	// Runtime History
	html_start_box(__('Runtime History (since last reset)'), '100%', '', '3', 'center', '');

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Last Runtime') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['cur_time']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Avg Runtime') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['avg_time']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Min Runtime') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['min_time']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Max Runtime') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['max_time']) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Done Runs') . "</td>\n";
	echo "<td style='width:15%;'>" . number_format($bm['total_good_runs']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Exited Runs') . "</td>\n";
	echo "<td style='width:15%;'>" . number_format($bm['total_failed_runs']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Start Failures') . "</td>\n";
	echo "<td style='width:40%;' colspan='3'>" . number_format($bm['total_errored_runs']) . "</td>\n";
	form_end_row();

	html_end_box();

	// Current/Last Stats
	html_start_box('Current Runtime Status', '100%', '', '3', 'center', '');

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Submit Time') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['pjob_bsubTime']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Seen Time') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['pjob_seenTime']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Start Time') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['pjob_startTime']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Run Time') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['pjob_runTime']) . "</td>\n";
	form_end_row();

	form_alternate_row();
	echo "<td style='width:10%;font-weight:bold;'>" . __('Done Time') . "</td>\n";
	echo "<td style='width:15%;'>" . benchmark_get_time($bm['pjob_doneTime']) . "</td>\n";
	echo "<td style='width:10%;font-weight:bold;'>" . __('Seen Done') . "</td>\n";
	echo "<td style='width:65%;' colspan='5'>" . benchmark_get_time($bm['pjob_seenDoneTime']) . "</td>\n";
	form_end_row();

	html_end_box();

	html_start_box(__('Graphs'), '100%', '', '3', 'left', '');

	$cacti_host = db_fetch_cell_prepared("SELECT cacti_host
		FROM grid_clusters, grid_clusters_benchmarks
		WHERE grid_clusters.clusterid = grid_clusters_benchmarks.clusterid
		AND benchmark_id = ?",
		array(get_request_var('benchmark_id')));

	if (isset($cacti_host)) {
		$local_graph_ids = array_rekey(
			db_fetch_assoc_prepared('SELECT DISTINCT graph_local.id
				FROM graph_local
				INNER JOIN graph_templates
				ON graph_templates.id=graph_local.graph_template_id
				WHERE graph_local.host_id = ?
				AND graph_templates.hash="6c8c4a6c27c0b73866f11748e17f5ed2"
				AND graph_local.snmp_index = ?',
				array($cacti_host, get_request_var('benchmark_id'))),
			'id', 'id');

		if (!empty($local_graph_ids)) {
			$graphs = db_fetch_assoc('SELECT gtg.local_graph_id, gtg.title_cache,
				gtg.height, gtg.width
				FROM graph_templates_graph AS gtg
				INNER JOIN graph_local AS gl
				ON gl.id=gtg.local_graph_id
				WHERE gtg.local_graph_id  IN (' . implode(',',$local_graph_ids) . ')
				GROUP BY gtg.local_graph_id
				ORDER BY gtg.title_cache ');
		}

		benchmark_html_graph_area($graphs, '', 'graph_start=' . get_current_graph_start() . '&graph_end=' . get_current_graph_end());
	}

	html_end_box();

	load_current_session_value('graph',   'sess_bmd_graph',   'all');
	load_current_session_value('date',    'sess_bmd_date',    'weekly');
	load_current_session_value('measure', 'sess_bmd_measure', 'done');

	return;
}

function benchmark_html_graph_area(&$graph_array, $no_graphs_message = '', $extra_url_args = '', $header = '', $columns = 0) {
    global $config;
    $i = 0; $k = 0; $j = 0;

    $num_graphs = cacti_sizeof($graph_array);

    if ($columns == 0) {
            $columns = read_user_setting('num_columns');
    }

    ?>
    <script type='text/javascript'>
    var refreshMSeconds = <?php print read_user_setting('page_refresh')*1000;?>;
    var graph_start     = <?php print get_current_graph_start();?>;
    var graph_end       = <?php print get_current_graph_end();?>;
	$(function() {
		$('.graphimage').each(function() {
			$(this).attr('src', $(this).attr('src')+'&f=,xxx'); //Fix atob error in zoomFunction_init()
		});
	});
    </script>
    <?php

    if ($num_graphs > 0) {
        if ($header != '') {
            print $header;
        }

        foreach ($graph_array as $graph) {
            print "<tr class='tableRowGraph'>\n";
            ?>
		<td align='center' style='width:25%'> </td>
		<td align='center' style='width:50%'>
		<a class='pic' href='<?php print html_escape($config['url_path'] . 'graph.php?action=view&rra_id=all&local_graph_id=' . $graph['local_graph_id']);?>'><img image_width='100' class='graphimage' id='graph_<?php print $graph['local_graph_id'] ?>' src='<?php print html_escape($config['url_path'] . 'graph_image.php?local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0' . (($extra_url_args == '') ? '' : "&$extra_url_args"));?>' alt='' title='<?php print $graph['title_cache'];?>'></a>
		</td>
		<td style='width: 25%' id='dd<?php print $graph['local_graph_id'];?>' class='noprint graphDrillDown'></td>
            <?php
            print "</tr>\n";
        }
    } else {
        if ($no_graphs_message != '') {
            print "<td><em>$no_graphs_message</em></td>";
        }
    }
}

function benchmark_draw_histogram() {
	$id = get_request_var('benchmark_id');

	switch (get_request_var('date')) {
	case 'hourly':
		$group_by = "DATE_FORMAT(start_time, '%Y_%m_%d_%H')";
		$interval = "DATE_SUB(CURDATE(), INTERVAL 24 HOUR)";
		$limit    = 24;
		break;
	case 'daily':
		$group_by = "DATE_FORMAT(start_time, '%Y_%m_%d')";
		$interval = "DATE_SUB(CURDATE(), INTERVAL 10 DAY)";
		$limit    = 7;
		break;
	case 'weekly':
		$group_by = "DATE_FORMAT(start_time, '%U')";
		$interval = "DATE_SUB(CURDATE(), INTERVAL 10 WEEK)";
		$limit    = 8;
		break;
	case 'monthly':
		$group_by = "DATE_FORMAT(start_time, '%Y_%m_%d')";
		$interval = "DATE_SUB(CURDATE(), INTERVAL 367 DAY)";
		$limit    = 12;
		break;
	default:
		break;
	}

	switch(get_request_var('measure')) {
	case 'submit':
		$column = 'pjob_bsubTime';
		break;
	case 'start':
		$column = 'pjob_startTime';
		break;
	case 'run':
		$column = 'pjob_runTime';
		break;
	case 'done':
		$column = 'pjob_doneTime';
		break;
	default:
		break;
	}

	db_execute("SET SESSION group_concat_max_len=1073741824");
	$sql = "SELECT * FROM (
		SELECT
		$group_by AS date_range,
		ROUND(MIN($column),4) AS `MIN`,
		ROUND(AVG($column),4) AS `AVG`,
		ROUND(MAX($column),4) AS `MAX`,
		ROUND(STDDEV($column),4) AS `STDDEV`,
		ROUND(SUBSTRING_INDEX(
			SUBSTRING_INDEX(
				GROUP_CONCAT($column ORDER BY $column SEPARATOR ','),
				',', 25/100 * COUNT(*) + 1),
			',',   -1
		),4) as P25,
		ROUND(SUBSTRING_INDEX(
			SUBSTRING_INDEX(
				GROUP_CONCAT($column ORDER BY $column SEPARATOR ','),
				',', 50/100 * COUNT(*) + 1),
			',', -1
		),4) as P50,
		ROUND(SUBSTRING_INDEX(
			SUBSTRING_INDEX(
				GROUP_CONCAT($column ORDER BY $column SEPARATOR ','),
				',', 75/100 * COUNT(*) + 1),
			',', -1
		),4) as P75,
		ROUND(SUBSTRING_INDEX(
			SUBSTRING_INDEX(
				GROUP_CONCAT($column ORDER BY $column SEPARATOR ','),
				',', 90/100 * COUNT(*) + 1),
			',', -1
		),4) as P90,
		COUNT(benchmark_id) AS SAMPLES
		FROM grid_clusters_benchmark_summary
		WHERE start_time>$interval
		AND benchmark_id=$id
		AND $column>0
		GROUP BY $group_by
		ORDER BY $group_by DESC
		LIMIT $limit
		) AS rs
		ORDER BY date_range ASC";

	echo $sql;
}

