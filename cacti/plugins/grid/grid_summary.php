<?php
// $Id$
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

$guest_account = true;

chdir('../../');
include_once('./include/auth.php');
include_once('./plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
ini_set('max_execution_time', '40');

$title = __('IBM Spectrum LSF RTM - Host Summary Dashboard', 'grid');

$orders = array(
	'status' => array(
		'd' => __('Threshold', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.summary_status'
	),
	'lstatus' => array(
		'd' => __('Load Status', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.load_status'
	),
	'bstatus' => array(
		'd' => __('Batch Status', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.bhost_status'
	),
	'host' => array(
		'd' => __('Hostname', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.host'
	),
	'type' => array(
		'd' => __('Host Type', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.hostType'
	),
	'model' => array(
		'd' => __('Host Model', 'grid'),
		'o' => 'grid_summary.clustername, grid_summary.hostModel'
	)
);

/* set default action */
set_default_action();

$replace_time = 0;
$thold_time   = 0;

switch (get_request_var('action')) {
	case 'ajax':
		grid_ajax_hostinfo();

		break;
	case 'ajaxlegendall':
		process_request_vars();
		display_legend();

		break;
	case 'ajaxrefresh':
		process_request_vars();
		refresh_data($replace_time, $thold_time);

		show_summary();

		break;
	case 'ajaxlegend':
		process_request_vars();
		grid_ajax_legend();

		break;
	case 'ajaxsave':
		process_request_vars();
		grid_summary_ajax_save();

	case 'audiocontrol':
		process_request_vars();
		grid_ajax_audio_control();

		break;
	case 'ajaxonClusterChange':
		process_request_vars();
		grid_ajax_show_group();

		break;
	case 'clear':
		process_request_vars();
		grid_set_minimum_page_refresh();

		print json_encode(array('clusterid' => get_request_var('clusterid'), 'exfilter' => get_request_var('exfilter'), 'refresh' => get_request_var('refresh')));
		break;
	case 'ajax_rtm_hgroups':
		process_request_vars();
		$sql_where = '';
		if (get_request_var('clusterid') > 0) {
			$sql_where = 'clusterid = ' . get_request_var('clusterid');
		}
		rtm_autocomplete_ajax('grid_summary.php', 'hgroup', $sql_where);
		break;
	default:
		process_request_vars();
		refresh_data($replace_time, $thold_time);

		summary();

		break;
}

function grid_summary_ajax_save() {
	$settings =
		'clusterid='  . get_request_var('clusterid')  . '|' .
		'hgroup='     . get_request_var('hgroup')     . '|' .
		'cacti='      . get_request_var('cacti')      . '|' .
		'order='      . get_request_var('order')      . '|' .
		'limit='      . get_request_var('limit')      . '|' .
		'refresh='    . get_request_var('refresh')    . '|' .
		'lstatus='    . get_request_var('lstatus')    . '|' .
		'bstatus='    . get_request_var('bstatus')    . '|' .
		'tholds='     . get_request_var('tholds')     . '|' .
		'size='       . get_request_var('size')       . '|' .
		'exfilter='   . get_request_var('exfilter')   . '|' .
		'shostname='  . get_request_var('shostname')  . '|' .
		'filter='     . get_request_var('filter')     . '|' .
		'model='      . get_request_var('model')      . '|' .
		'type='       . get_request_var('type');

	set_grid_config_option('grid_summary', $settings);
}


function refresh_data($replace_time, $thold_time) {
	global $config;

	$hosts = db_fetch_cell('SELECT count(*) FROM grid_hosts');

	if ($hosts < 1000) {
		update_grid_summary_table(true, $replace_time, $thold_time);
	}
}

function grid_ajax_audio_control() {
	if (isset_request_var('muteme') && get_request_var('muteme') == 'true') {
		$_SESSION['sess_grid_view_summary_muteme'] = true;
		$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . get_request_var('clusterid')] = date('Y-m-d H:i:s');
	} elseif (!isset($_SESSION['sess_grid_view_summary_muteme'])) {
		$_SESSION['sess_grid_view_summary_muteme'] = false;
	}

	if (isset_request_var('muteme') && get_request_var('muteme') == 'true') {
		if (isset($_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')])) {
			$parts = explode(', Last Muted', $_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')]);
			$_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')] = trim($parts[0]) . ', Last Muted/Ack&#39;d at: ' . date('m-d H:i');
		}
	}
}

function grid_ajax_hostinfo() {
	global $config, $qc;

	get_filter_request_var('clusterid');
	get_filter_request_var('host', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));

	$tabs = array(
		'status'   => __('Status', 'grid'),
		'resource' => __('Resources', 'grid'),
		'alarm'    => __('Alerts', 'grid')
	);

	$conf_data   = array();
	$object_data = array();

	if (file_exists($config['base_path'] . '/plugins/meta/lib/metadata_api.php')) {
		include($config['base_path'] . '/plugins/meta/lib/metadata_api.php');

		$object_data = get_object_metadata('host', get_request_var('host'), get_request_var('clusterid'));

		if (cacti_sizeof($object_data)) {
			$conf_data = get_object_metadata_conf('host');
		}
	}

	/* Print the tabs themselves in two parts, first grid, then meta */
	print "<div style='padding:0px;margin:0px;font-size:11px;' id='summary_tabs'>";
	print '<ul>';

	$section_name = '';
	$i = 0;

	foreach ($tabs as $tab => $name) {
		print "<li><a href='#meta_tabs-$i'>$name</a></li>";
		$i++;
	}

	if (cacti_sizeof($conf_data)) {
		print "<li><a href='#meta_tabs-$i'>" . __('Metadata', 'grid') . '</a></li>';
		$i++;
	}
	print '</ul>';

	/* get the host record */
	$host = db_fetch_row_prepared('SELECT *
		FROM grid_summary
		WHERE clusterid = ?
		AND host = ?',
		array(get_request_var('clusterid'), get_request_var('host')));

	if (cacti_sizeof($host)) {
		$bhosts_queue_limits = array_rekey(
			db_fetch_assoc('SELECT
				sum(hostJobLimit) AS slots,
				host
				FROM grid_queues
				INNER JOIN grid_queues_hosts
				ON queuename=queue
				AND grid_queues.clusterid=grid_queues_hosts.clusterid
				GROUP BY host'),
			'host', 'slots'
		);

		/* loop through each host one by one */
		if (isset($host['description'])) {
			$name = $host['description'];
		} else {
			$name = grid_strip_domain($host['host']);
		}

		if (strlen($host['cacti_status']) > 0) {
			$cstatus = $host['cacti_status'];
		} else {
			$cstatus = 4;
		}

		$bstatus  = ucfirst($host['bhost_status']);
		$lstatus  = ucfirst($host['load_status']);
		$hostname =  $host['host'];

		if ($host['cacti_hostid'] == '') {
			$ptime = __('N/A', 'grid');
		} else {
			$ptime = __('%s ms', round($host['cur_time'],2), 'grid');
		}

		$hCtrlMsg = $host['hCtrlMsg'];
		$running  = $host['numRun'];
		$ssusp    = $host['numSSUSP'];
		$ususp    = $host['numUSUSP'];
		$reserve  = $host['numRESERVE'];

		if ($host['maxJobs'] == '-') {
			if (isset($bhosts_queue_limits[$host['host']])) {
				if ($bhosts_queue_limits[$host['host']] > 0) {
					$maxjobs = $bhosts_queue_limits[$host['host']];
				} else {
					if (($host['maxCpus'] == 0) || ($host['maxCpus'] == '-')) {
						$maxjobs = '-';
					} else {
						$maxjobs = $host['maxCpus'];
					}
				}
			} else {
				if (($host['maxCpus'] == 0) || ($host['maxCpus'] == '-')) {
					$maxjobs = '-';
				} else {
					$maxjobs = $host['maxCpus'];
				}
			}
		} else {
			$maxjobs = $host['maxJobs'];
		}

		if ($lstatus == 'Unavail') {
			$r15s    = '-';
			$r1m     = '-';
			$r15m    = '-';
			$ut      = '-';
			$it      = '-';
			$io      = '-';
			$pg      = '-';
			$tmp     = '-';
			$swp     = '-';
			$mem     = '-';
			$maxSwap = '-';
			$maxTmp  = '-';
			$maxMem  = '-';
		} else {
			$r15s    = display_load($host['r15s'],1);
			$r1m     = display_load($host['r1m'],1);
			$r15m    = display_load($host['r15m'],1);
			$ut      = display_ut($host['ut']);
			$it      = display_hours($host['it']);
			$io      = display_load($host['io']);
			$pg      = display_load($host['pg']);
			$tmp     = display_memory($host['tmp']);
			$maxTmp  = display_memory($host['maxTmp']);
			$swp     = display_memory($host['swp']);
			$maxSwap = display_memory($host['maxSwap']);
			$mem     = display_memory($host['mem']);
			$maxMem  = display_memory($host['maxMem']);
		}

		if ($host['cacti_hostid'] == '') {
			$d = __('Not Integrated', 'grid');
		} else {
			$d = $host['status_rec_date'];
			if ($d == '0000-00-00 00:00:00') {
				$d = __('Never', 'grid');
			}
		}

		$avail = round($host['availability'],2);

		$icolorsdisplay = array(
			__('Unknown', 'grid'),
			__('Down', 'grid'),
			__('Recovering', 'grid'),
			__('Up', 'grid'),
			__('N/A', 'grid')
		);

		$sdisplay = $icolorsdisplay[$cstatus];
		$dname  = html_escape(strtoupper(grid_strip_domain($name)));
		$gname  = html_escape(strtoupper($host['clustername']));
		$hmodel = html_escape(strtoupper($host['hostModel']));
		$htype  = html_escape(strtoupper($host['hostType']));

		$s = $s2 = '';
		if (($cstatus < 2) && ($cstatus > 0)) {
			// If the host is down, we bold the name
			$s = '<b>';
			$s2 = '</b>';
		}

		$jstart = $host['job_last_started'];
		$jdone  = $host['job_last_ended'];
		$jexit  = $host['job_last_exited'];

		if ($jstart == '0000-00-00 00:00:00') {
			$jstart = __('Never', 'grid');
		}

		if ($jdone == '0000-00-00 00:00:00') {
			$jdone = __('Never', 'grid');
		}

		if ($jexit == '0000-00-00 00:00:00') {
			$jexit = __('Never', 'grid');
		}

		/* hooks for customization */
		$menu_link_add    = '';
		$menu_content_add = '';

		api_plugin_hook_function('grid_summary_action_insert', $host);
		api_plugin_hook_function('grid_host_mouseover', $host);

		if ((substr($lstatus, 0, 1) == 'U') || (substr($bstatus, 0, 1) == 'U')) {
			$down = true;
		} else {
			$down = false;
		}

		$title = "<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('Host Summary Information', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Cluster', 'grid') . "</td>
				<td class='right'>$gname</td>
				<td class='nowrap'>" . __('Host', 'grid') . "</td>
				<td class='right'>$dname</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Model/Type', 'grid') . "</td>
				<td class='right'>$hmodel</td>
				<td class='nowrap'>" . __('Model/Type', 'grid') . "</td>
				<td class='right'>$htype</td>
			</tr>";

		print "<div style='padding:0px;margin:0px;font-size:11px;' id='meta_tabs-0'>
		<table class='metricTable'>
			$title
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('RTM Links', 'grid') . "</td>
			</tr>
			<tr class='selectable'>
				<td class='nowrap' colspan='4'>
					<div class='anchorContainer'>
						<div><a class='linkEditMain hyperLink' href='" . html_escape('grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $host['clusterid'] . '&ajax_host_query=' . $host['host'] . '&exec_host=' . $host['host'] . '&status=-1') . "'>" . __('All Jobs', 'grid') . "</a></div>
						<div><a class='linkEditMain hyperLink' href='" . html_escape('grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $host['clusterid'] . '&ajax_host_query=' .$host['host'] . '&exec_host=' . $host['host'] . '&status=RUNNING') . "'>" . __('Running Jobs', 'grid') . "</a></div>
						" . (($host['cacti_hostid']) ? "<div><a class='linkEditMain hyperLink' href='" . html_escape($config['url_path'] . 'graph_view.php?action=preview&graph_template_id=-1&rfilter=&host_id=' . $host['cacti_hostid']) . "'>" . __('Graphs', 'grid') . '</a></div>' : __('Graphs', 'grid')) .
						"<div><a class='linkEditMain hyperLink' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host']) . "'>" . __('Alerts', 'grid') . '</a></div>' .
						$menu_link_add . "
					</div>
				</td>
			</tr>
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('Load/Batch Status', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Load Status', 'grid') . "</td>
				<td class='right'>$lstatus</td>
				<td class='nowrap'>" . __('Batch Status', 'grid') . "</td>
				<td class='right'>$bstatus</td>
			</tr>" . (strlen($hCtrlMsg) ? "
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Admin Note', 'grid') . "</td>
				<td class='right' colspan='5'>$hCtrlMsg</td>
			</tr>" : "") . "
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('Batch Info', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Running / Maximum', 'grid') . "</td>
				<td class='right'>$running / $maxjobs</td>
				<td class='nowrap'>" . __('System / User/ Reserved', 'grid') . "</td>
				<td class='right'>$ssusp / $ususp / $reserve</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Job Last Started', 'grid') . "</td>
				<td class='right'>$jstart</td>
				<td class='nowrap'>" . __('Job Last Ended', 'grid') . "</td>
				<td class='right'>$jdone</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Job Last Exited', 'grid') . "</td>
				<td class='right'>$jexit</td>
				<td class='nowrap'></td>
				<td class='right'></td>
			</tr>
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('Cacti Status', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Device Status', 'grid') . "</td>
				<td class='right'>$sdisplay</td>
				<td class='nowrap'>" . __('Ping Time', 'grid') . "</td>
				<td class='right'>$ptime</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Last Failed', 'grid') . "</td>
				<td class='right'>$d</td>
				<td class='nowrap'></td>
				<td class='right'></td>
			</tr>" . (!$down ? "
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('Load Info', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('15s / 1m / 15m', 'grid') . "</td>
				<td class='right'>$r15s / $r1m / $r15m</td>
				<td class='nowrap'>" . __('CPU Percent', 'grid') . "</td>
				<td class='right'>$ut</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Memory (Free / Max)', 'grid') . "</td>
				<td class='right'>$mem / $maxMem</td>
				<td class='nowrap'>" . __('IO / Paging (KB / KPages)', 'grid') . "</td>
				<td class='right'>$io / $pg</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Temp (Free / Max)', 'grid') . "</td>
				<td class='right'>$tmp / $maxTmp</td>
				<td class='nowrap'>" . __('Swap (Free / Max)', 'grid') . "</td>
				<td class='right'>$swp / $maxSwap</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap'>" . __('Idle Time', 'grid') . "</td>
				<td class='right'>$it</td>
				<td class='nowrap'></td>
				<td class='right'></td>
			</tr>" : '') . $menu_content_add . '
		</table></div>';

		print "<div style='overflow-y:scroll;height:400px;display:none;padding:0px;margin:0px;font-size:21px;' id='meta_tabs-1'>
		<table class='metricTable'>
			$title";

		$bresources = db_fetch_assoc_prepared('SELECT *
			FROM grid_hostresources
			WHERE host = ?
			AND clusterid = ?
			ORDER BY resource_name',
			array($hostname, $host['clusterid']));

		if (cacti_sizeof($bresources)) {
			print "<tr class='tableHeader'><td class='tableSubHeaderColumn' colspan='4'>" . __('Boolean Resources', 'grid') . '</td></tr>';

			$res = '';
			foreach($bresources as $r) {
				if($r['rtype'] == 1) { //exclusive resource
					$res .= (strlen($res) ? ', ':'') . '!'. $r['resource_name'];
				} else {
					$res .= (strlen($res) ? ', ':'') . $r['resource_name'];
				}
			}
			print "<tr class='metricRow selectable'><td class='left' colspan='4'>" . html_escape($res) . '</td></tr>';
		}

		$sresources = db_fetch_assoc_prepared('SELECT *
			FROM grid_hosts_resources
			WHERE host = ?
			AND clusterid = ?
			AND resType=3
			ORDER BY resource_name',
			array($hostname, $host['clusterid']));

		if (cacti_sizeof($sresources)) {
			print "<tr class='tableHeader'><td class='tableSubHeaderColumn' colspan='4'>" . __('String Resources', 'grid') . '</td></tr>';

			$i = 0;
			foreach($sresources as $r) {

				if ($i % 2 == 0) {
					if ($i > 0) {
						print '</tr>';
					}
					print "<tr class='metricRow selectable'>";
				}

				print "<td class='nowrap'>" . html_escape($r['resource_name']) . "</td><td class='right'>" . html_escape($r['totalValue']) . '</td>';
				$i++;
			}

			if ($i % 2 != 0) {
				print "<td></td><td></td>";
				print "</tr>";
			}
		}

		$nresources = db_fetch_assoc_prepared('SELECT *
			FROM grid_hosts_resources
			WHERE host = ?
			AND clusterid = ?
			AND resType=1 ORDER BY resource_name',
			array($hostname, $host['clusterid']));

		$hinfo = db_fetch_row_prepared('SELECT *
			FROM grid_hostinfo
			WHERE host = ?
			AND clusterid = ?',
			array($hostname, $host['clusterid']));

		if (cacti_sizeof($nresources)) {
			print "<tr class='tableHeader'><td class='tableSubHeaderColumn' colspan='4'>" . __('Numeric Resources [ Available / Reserved / Total ]', 'grid') . '</td></tr>';

			$i = 0;
			$roundedValue = 0;
			foreach($nresources as $r) {
				if (is_numeric($r['totalValue'])) {
					$roundedValue = round($r['totalValue'], 0);
					$r['totalValue'] = ($r['totalValue'] == $roundedValue) ? $roundedValue : round($r['totalValue'], 3);
				}

				if (is_numeric($r['reservedValue'])) {
					$roundedValue = round($r['reservedValue'], 0);
					$r['reservedValue'] = ($r['reservedValue'] == $roundedValue) ? $roundedValue : round($r['reservedValue'], 3);
				}

				if ($i % 2 == 0) {
					if ($i > 0) {
						print '</tr>';
					}

					print "<tr class='metricRow selectable'>";
				}

				switch($r['resource_name']) {
				case 'io':
				case 'pg':
				case 'it':
				case 'ls':
				case 'ut':
				case 'r15m':
				case 'r15s':
				case 'r1m':
					print "<td class='nowrap'>" . $r['resource_name'] . "</td>
						<td class='right'>" . __('%s / %s / NA', number_format_i18n($r['totalValue']), number_format_i18n($r['reservedValue']), 'grid') . '</td>';
					break;
				case 'swp':
					print "<td class='nowrap'>" . $r['resource_name'] . "</td>
						<td class='right'>" . display_memory($r['totalValue']) . ' / ' . display_memory($r['reservedValue']) . ' / ' . display_memory($hinfo['maxSwap']) . '</td>';
					break;
				case 'tmp':
					print "<td class='nowrap'>" . $r['resource_name'] . "</td>
						<td class='right'>" . display_memory($r['totalValue']) . ' / ' . display_memory($r['reservedValue']) . ' / ' . display_memory($hinfo['maxTmp']) . '</td>';
					break;
				case 'mem':
					print "<td class='nowrap'>" . $r['resource_name'] . "</td>
						<td class='right'>" . display_memory($r['totalValue']) . ' / ' . display_memory($r['reservedValue']) . ' / ' . display_memory($hinfo['maxMem']) . '</td>';
					break;
				default:
					print "<td class='nowrap'>" . $r['resource_name'] . "</td>
						<td class='right'>" . __('%s / %s / NA', is_numeric($r['totalValue']) ? number_format_i18n($r['totalValue']) : '-', is_numeric($r['reservedValue']) ? number_format_i18n($r['reservedValue']) : '-', 'grid') . '</td>';
					break;
				}

				$i++;
			}

			if ($i % 2 != 0) {
				print "<td></td><td></td>";
				print "</tr>";
			}
		}

		print '</table>
		</div>';
		$grid_host_alarm_control_actions = array(
			1 => __('Acknowledge', 'grid')
		);


		print "<div style='display:none;padding:0px;margin:0px;font-size:11px;' id='meta_tabs-2'>
			<table class='metricTable'>
			$title";

		print "<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' colspan='4'>" . __('RTM Links', 'grid') . "</td>
			</tr>
			<tr class='metricRow selectable'>
				<td class='nowrap' colspan='4'>
					<div class='anchorContainer'>
						<div><a class='pic linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host'] . '&alarm=3') . "'>" . __('All Alerts', 'grid') . "</a></div>
						<div><a class='pic linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host'] . '&alarm=-2') . "'>" . __('Threshold', 'grid') . "</a></div>
						<div><a class='pic linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host'] . '&alarm=0') . "'>" . __('Syslog', 'grid') . "</a></div>
						<div><a class='pic linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host'] . '&alarm=1') . "'>" . __('Grid Alert', 'grid') . "</a></div>
 						<div><a class='pic linkEditMain' href='" . html_escape($config['url_path'] . 'plugins/grid/grid_summary.php?tab=alarm&reset=1&clusterid=' . $host['clusterid'] . '&hostname=' . $host['host'] . '&alarm=2') . "'>" . __('Batch Load', 'grid') . "</a></div>
					</div>
				</td>
			</tr>";
		print '</table>';

		print "<table class='metricTable'>
			<tr class='tableHeader'>
				<td width='25%' class='tableSubHeaderColumn'>" . __('Type', 'grid') . "</td>
				<td class='tableSubHeaderColumn'>" . __('Alert Message', 'grid') . '</td>';

		print "</tr>
			</table>
			<div style='padding:0px;margin:0px;font-size:11px;overflow:scroll;height:310px;' id='alarm'>
				<table class='metricTable'>";

			$results = db_fetch_assoc_prepared('SELECT *
				FROM grid_hosts_alarm
				WHERE hostname = ?
				AND clusterid = ?',
				array($host['host'], $host['clusterid']));

			foreach($results as $result) {
				print "<tr>
					<td width='15%'>" . display_alarmtype_name($result['type']) . '</td>
					<td>' . html_escape(title_trim(str_replace('ALERT: ', '', $result['message']),60)) . '</td>
				</tr>';
			}

		html_end_box(false);
		print '</div>';
		//draw_actions_dropdown($grid_host_alarm_control_actions);
		//print "</form>";

		// Print the tab content
		if (cacti_sizeof($conf_data)) {
			print "<div style='display:none;padding:0px;margin:0px;font-size:11px;' id='meta_tabs-3'>
				<table class='metricTable'>
				$title";

			$j = 0;
			$section_name = '';
			foreach ($conf_data as $conf_datum) {
				if ($conf_datum['section_name'] != $section_name && $j == 0) {
					$section_name = $conf_datum['section_name'];
					print "<tr class='tableHeader'><td class='tableSubHeaderColumn' colspan='4'>$section_name</td></tr>";
					$j++;
				}

				if ($conf_datum['section_name'] != $section_name && $j > 0) {
					$section_name = $conf_datum['section_name'];
					print "<tr class='tableHeader'><td class='tableSubHeaderColumn' colspan='4'>$section_name</td></tr>";
					$j++;
				}

				print '<tr class="metricRow selectable">';

				// Special formatting for specific data types, including URL
				$value = '<td class="nowrap" colspan="2">' . $conf_datum['display_name'] . '</td>  ';
				if ($conf_datum['data_type'] == 'url') {
					$url = $object_data[0][$conf_datum['db_column_name']];
					$value .= "<td colspan='2'><a target='_blank' href='" . html_escape($url) . "'>$url</a>";
					$value .= '</td>';
				} elseif ($conf_datum['data_type'] == 'CALLBACK') {
					//$contents = popup_callback($object_data[$conf_datum['db_column_name']);
					$value .= "<td colspan='2'>$contents</td>";
				} else {
					$value .= '<td colspan="2">' . $object_data[0][$conf_datum['db_column_name']] . '</td>';
				}
				print $value;
				print '</tr>';
			}
			print '</table>';
			print '</div>';
		}
	}

	print '</div>';
	?>
	<script type='text/javascript'>
	$(function() {
		//if the length of hostname larger then 30, the hostname will overstep the Div of width_tabs which is 300. So add the check.
		<?php if (strlen($dname)>30 ) {
			$width_tabs=300+(strlen($dname)-30)*20;
			print "$('#summary_tabs').width($width_tabs);";
		}
		?>

		$('.linkEditMain').off().on('click', function(event) {
			event.preventDefault();
			loadPage($(this).attr('href'));
		});
	});
	</script>
	<?php
}

function grid_ajax_show_group() {
	if (get_request_var('clusterid') == 0) {
		$groups = db_fetch_assoc('SELECT DISTINCT groupName
			FROM grid_hostgroups
			ORDER BY groupName');
	} else {
		$groups = db_fetch_assoc_prepared('SELECT DISTINCT groupName
			FROM grid_hostgroups
			WHERE clusterid = ?
			ORDER BY groupName',
			array(get_request_var('clusterid')));
	}

	if (cacti_sizeof($groups)) {
		print '<option value="-1">' . __('All', 'grid') . '</option>';
		foreach ($groups as $group) {
			print '<option value="' . $group['groupName'] . '"';  print '>' . html_escape($group['groupName']) . '</option>';
		}
	} else {
		print '<option value="-1">' . __('N/A', 'grid') . '</option>';
	}

	print '<br/><option value="-1">' . __('All', 'grid') . '</option>';
	if (get_request_var('clusterid') == 0) {
		$types = db_fetch_assoc('SELECT DISTINCT hostType
			FROM grid_hostinfo
			WHERE hostType <> "FLOATING"
			AND hostType NOT LIKE "U%"
			ORDER BY hostType');
	} else {
		$types = db_fetch_assoc_prepared('SELECT DISTINCT hostType
			FROM grid_hostinfo
			WHERE hostType <> "FLOATING"
			AND hostType NOT LIKE "U%"
			AND clusterid = ?
			ORDER BY hostType',
			array(get_request_var('clusterid')));
	}

	if (cacti_sizeof($types) > 0) {
		foreach ($types as $type) {
			print '<option value="' . $type['hostType'] .'"';  print '>' . html_escape($type['hostType']) . '</option>';
		}
	}

	print '<br/><option value="-1">' . __('All', 'grid') . '</option>';
	if (get_request_var('clusterid') == 0) {
		$models = db_fetch_assoc('SELECT DISTINCT hostModel
			FROM grid_hostinfo
			WHERE hostModel!="N/A"
			AND hostModel NOT LIKE "%UNKNOWN%"
			ORDER BY hostModel');
	} else {
		$models = db_fetch_assoc_prepared('SELECT DISTINCT hostModel
			FROM grid_hostinfo
			WHERE hostModel!="N/A"
			AND hostModel NOT LIKE "%UNKNOWN%"
			AND clusterid = ?
			ORDER BY hostModel',
			array(get_request_var('clusterid')));
	}

	if (cacti_sizeof($models) > 0) {
		foreach ($models as $model) {
			print '<option value="' . $model['hostModel'] .'"'; print '>' . $model['hostModel'] . '</option>';
		}
	}

	print '<br/><option value="-1">' . __('All', 'grid') . '</option>';
	if (get_request_var('clusterid') == 0) {
		$stati = db_fetch_assoc("SELECT DISTINCT status
			FROM grid_load
			ORDER BY status");
	} else {
		$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
			FROM grid_load
			WHERE clusterid = ?
			ORDER BY status',
			array(get_request_var('clusterid')));
	}

	$found = false;
	if (cacti_sizeof($stati)) {
		foreach ($stati as $status) {
			if ($status['status'] == get_request_var('lstatus')) {
				$found = true;
				break;
			}
		}
	}

	if (!$found && get_request_var('lstatus') != '-1') {
		print '<option value="' . get_request_var('lstatus') .'" selected>' . __('Custom', 'grid') . '</option>';
	}

	reset($stati);
	if (cacti_sizeof($stati)) {
		foreach ($stati as $status) {
			print '<option value="' . $status['status'] .'"'; if (get_request_var('lstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
		}
	}

	print '<br/><option value="-1">' . __('All', 'grid') . '</option>';
	if (get_request_var('clusterid') == 0) {
		$stati = db_fetch_assoc('SELECT DISTINCT status
			FROM grid_hosts
			ORDER BY status');
	} else {
		$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
			FROM grid_hosts
			WHERE clusterid = ?
			ORDER BY status',
			array(get_request_var('clusterid')));
	}

	$found = false;
	if (cacti_sizeof($stati)) {
		foreach($stati as $status) {
			if ($status['status'] == get_request_var('bstatus')) {
				$found = true;
				break;
			}
		}
	}

	if (!$found && get_request_var('bstatus') != '-1') {
		print '<option value="' . get_request_var('bstatus') . '" selected>' . __('Custom', 'grid') . '</option>';
	}

	reset($stati);
	if (cacti_sizeof($stati)) {
		foreach ($stati as $status) {
			print '<option value="' . $status['status'] .'"'; if (get_request_var('bstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
		}
	}

	print '<br/>';
	$resources = db_fetch_assoc_prepared('SELECT resource_name
		FROM (
			SELECT resource_name
			FROM grid_hostresources
			WHERE clusterid= ?
			UNION
			SELECT resource_name
			FROM grid_hosts_resources
			WHERE clusterid= ?
		) AS resources
		ORDER BY resource_name', array(get_request_var('clusterid'), get_request_var('clusterid')));

	$title = '';

	if (cacti_sizeof($resources)) {
		foreach($resources as $r) {
			$title .= (strlen($title) ? ', ' : __('Available Resources', 'grid')) . ' ' . $r['resource_name'];
		}
	}

	print $title;
}

function grid_ajax_legend() {
	global $config;

	$color = get_request_var('color');
	switch ($color) {
		case 'red':
		case 'red_small':
			$name = __('Unavailable', 'grid');
			$ttitle = __('Host Down/Unavailable', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that the host is unavailable either in Cacti, or to either the Base of Batch LSF sub-systems.  <b>This is a clear indication that the host should be checked for trouble.</b>If the host is going to be down for an extended period, you may elect to turn monitoring of this host off in Cacti and it will be removed from the display entirely.', 'grid') . '</p></td>
				</tr>
			</table>';
			break;
		case 'black':
		case 'black_small':
			$name = __('Admin Down', 'grid');
			$ttitle = __('Host Down for Maintenance', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This Host has been closed for maintenance.  You may hover over the host to verify as to why the administrator has closed it.', 'grid') . '</p></td>
				</tr>
			</table>';
			break;
		case 'ured':
		case 'ured_small':
			$name = __('Low Resources', 'grid');
			$ttitle = __('Host Critical', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=4><p>' . __('This Host is under heavy load due to many factors.  This can happen for numerous reasons.  Some include: The host is oversubscribed, has aberrant jobs running including runaway MPI processes, is low on memory, has jobs running outside the queuing system, or race conditions caused from runaway process.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=4><u>' . __('Thresholds', 'grid') . '</u></td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('R1M/CPUs', 'grid') . '</td>
					<td width=25%>>= ' . read_config_option('grid_thold_ured_r1m') . '%</td>
					<td width=25%>' . __('Physical Free', 'grid') . '</td>
					<td width=25%>< ' . read_config_option('grid_thold_ured_pmem') . '%</td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('Swap Free', 'grid') . '</td>
					<td width=25%>< ' . read_config_option('grid_thold_ured_swap') . '%</td>
					<td width=25%>' . __('Temp Free', 'grid') . '</td>
					<td width=25%>< ' . read_config_option('grid_thold_ured_temp') . '%</td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('Paging Rate', 'grid') . '</td>
					<td width=25%>> ' . __('%s pg/sec', number_format_i18n(read_config_option('grid_thold_ured_pg')), 'grid') . '</td>
					<td width=25%>' . __('IO Rate', 'grid') . '</td>
					<td width=25%>> ' . __('%s KB/sec', number_format_i18n(read_config_option('grid_thold_ured_io')), 'grid') . '</td>
				</tr>
			</table>';
			break;
		case 'orange':
		case 'orange_small':
			$name = __('Busy / Closed', 'grid');
			$ttitle = __('Host Recovering/Busy/Closed', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that either Cacti sees that host as recovering, Host load indices show the host as busy or the Grid Batch Metrics are something other than Ok.  This status indicates that a host is either loaded, or just coming on-line.', 'grid') . '</p></td>
				</tr>
			</table>';
			break;
		case 'cblue':
		case 'cblue_small':
			$name = __('Idle/Closed', 'grid');
			$ttitle = __('Host Idle/Closed', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=4><p>' . __('This status indicates that Host load indices show the host as idle, but Batch statistics show the host as not having any job slots available.  This status may indicate either idle jobs, zombie jobs on the host, or a locked sbatchd on the host.  If jobs on this system are aberrant, restart the host using the badmin hrestart command.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=4><u>' . __('Thresholds', 'grid') . '</u></td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('Job %s Allocated', format_job_slots(), 'grid') . '</td>
					<td width=25%>' . read_config_option('grid_thold_cblue_slots') . '%</td>
					<td width=25%>' . __('CPU Percent', 'grid') . '</td>
					<td width=25%>< ' . read_config_option('grid_thold_cblue_cpu') . '%</td>
				</tr>
			</table>';
			break;
		case 'yellow':
		case 'yellow_small':
			$name = __('Busy', 'grid');
			$ttitle = __('Host Busy', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=4><p>' . __('This status indicates that Host load indices show the host as busier than normal, but with Batch statistics showing lower than average or no jobs running.  This status indicates that a host is likely running jobs outside of the Grid management system.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=4><u>'. __('Thresholds', 'grid') . '</u></td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('R1M/CPUs', 'grid') . '</td>
					<td width=25%>>= ' . read_config_option('grid_thold_yellow_r1m') . '%</td>
					<td width=25%>' . __('CPU Percent', 'grid') . '</td>
					<td width=25%>> ' . read_config_option('grid_thold_yellow_cpu') . '%</td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('Paging Rate', 'grid') . '</td>
					<td width=25%>> ' . __('%s pg/sec', number_format_i18n(read_config_option('grid_thold_yellow_pg')), 'grid') . '</td>
					<td width=25%>' . __('IO Rate', 'grid') . '</td>
					<td width=25%>> ' . __('%s KB/sec', number_format_i18n(read_config_option('grid_thold_yellow_io')), 'grid') . '</td>
				</tr>
			</table>';
			break;
		case 'blue':
		case 'blue_small':
			$name = __('Idle w/Jobs', 'grid');
			$ttitle = __('Idle Job Host', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=4><p>' . __('This status indicates that Host load indices show the host does not show a significant load but the Batch statistics show more than normal jobs counts running on the system.  This status indicates that a host either has orphaned jobs or non-cpu intensive jobs running on it.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=4><u>' . __('Thresholds', 'grid') . '</u></td>
				</tr>
				<tr class="legendThreshold">
					<td width=25%>' . __('Job %s', format_job_slots(), 'grid') . '</td>
					<td width=25%>' . read_config_option('grid_thold_blue_slots') . '</td>
					<td width=25%>' . __('CPU Percent', 'grid') . '</td>
					<td width=25%>< ' . read_config_option('grid_thold_blue_cpu') . '%</td>
				</tr>
			</table>';
			break;
		case 'grey':
		case 'grey_small':
			$name = __('Starved', 'grid');
			$ttitle = __('Starved Host', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status that the host has not received jobs for some unknown reason in the predefined threshold periods.  You should check the host and audit your configuration to verify that this host is properly associated with cluster resources.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=2><u>' . __('Thresholds', 'grid') . '</u></td>
				</tr>
				<tr class="legendThreshold">
					<td width=40%>' . __('None for', 'grid') . '</td>
					<td width=60%>' . grid_minutes_to_days_hours_minutes(read_config_option('grid_thold_grey_duration')) . '</td>
				</tr>
			</table>';
			break;
		case 'black_hole_small':
		case 'black_hole':
			$name   = __('Black Hole', 'grid');
			$ttitle = __('Black Hole Host', 'grid');
			$title  = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that the host is consuming jobs and should be closed.  This can be caused by a number of factors such as a lack of available file descriptors, or sockets, NFS issues, in addition to poorly defined limits.', 'grid') . '</p></td>
				</tr>
				<tr>
					<td colspan=2><br><strong><u>' . __('Thresholds', 'grid') . '</u></strong></td>
				</tr>
				<tr class="legendThreshold">
					<td width=40%>' . __('Exits Per 5 Minutes:', 'grid') . '</td>
					<td width=60%>' . read_config_option('grid_thold_black_jobs_per5') . '</td>
				</tr>
				<tr class="legendThreshold">
					<td width=40%>' . __('Consecutive Exits:', 'grid') . '</td>
					<td width=60%>' . read_config_option('grid_thold_black_jobs_consecutive') . '</td>
				</tr>
			</table>';

			break;
		case 'alarm_blink':
		case 'alarm_blink_small':
			$name   = __('Alert', 'grid');
			$ttitle = __('Alert Host', 'grid');
			$title  = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that alarms are trigged on this host.', 'grid') . '</p></td>
				</tr>
			</table>';

			break;
		case 'alarm_static':
		case 'alarm_static_small':
			$name = __('Stop Alert', 'grid');
			$ttitle = __('Stop alert host', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that alert has been stopped on this host.', 'grid') . '</p></td>
				</tr>
			</table>';
			break;
		case 'green_small':
		case 'green':
		default:
			$name = __('Idle', 'grid');
			$ttitle = __('Idle Host', 'grid');
			$title = '<table class="legendTable">
				<tr>
					<td colspan=2><p>' . __('This status indicates that Host load indices and Batch statistics show the host as idle.', 'grid') . '</p></td>
				</tr>
			</table>';
			break;
	}

	print "<div style='padding:0px;margin:0px;font-size:11px;' id='meta_tabs-0'>
		<table class='metricTable'>
			<tr class='tableHeader'>
				<td class='tableSubHeaderColumn' coslpan='2'>" . $ttitle . '</td>
			</tr>
			<tr>
				<td>' . $title . '</td>
			</tr>';
}

function grid_view_get_summary_records($is_summary_alarm_log = false) {
	global $orders;
	$sql_params = array();

	/* cacti integration status sql where */
	if (get_request_var('cacti') == '-1') {
		$sql_where = "WHERE (isServer>'0' AND ((monitor='on' AND disabled='') OR (monitor IS NULL OR monitor='')))";
	} elseif (get_request_var('cacti') == '-2') {
		$sql_where = 'WHERE (isServer > 0 AND cacti_status IS NULL)';
	} else {
		$sql_where = 'WHERE (isServer > 0 AND cacti_status=?)';
		$sql_params[] = get_request_var('cacti');
	}

	/* tholds sql where */
	if (get_request_var('tholds') == '-1') {
		/* Show all items */
	} else {
		$sql_where .= ' AND (summary_status=?)';
		$sql_params[] = get_request_var('tholds');
	}

	/* clusterid sql where */
	if (get_request_var('clusterid') == '0' || !isset_request_var('clusterid')) {
		/* Show all items */
	} else {
		$sql_where .= ' AND (grid_summary.clusterid=?)';
		$sql_params[] = get_filter_request_var('clusterid');
	}

	/* host type sql where */
	if (get_request_var('type') == '-1') {
		/* Show all items */
	} else {
		$sql_where .= ' AND (hostType=?)';
		$sql_params[] = get_request_var('type');
	}

	/* host model sql where */
	if (get_request_var('model') == '-1') {
		/* Show all items */
	} else {
		$sql_where .= ' AND (hostModel=?)';
		$sql_params[] = get_request_var('model');
	}
	/* host group sql where */
	if (get_request_var('hgroup') == '-1') {
		/* Show all items */
	} else {
		$sql_where .= ' AND (groupName=?)';
		$sql_params[] = get_request_var('hgroup');
	}

	/* host load status sql where */
	if (get_request_var('lstatus') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND ' . grid_parse_request_var_into_sql_where(get_request_var('lstatus'), 'load_status');
		} else {
			$sql_where  = grid_parse_request_var_into_sql_where(get_request_var('lstatus'), 'load_status');
		}
	}

	/* host batch status sql where */
	if (get_request_var('bstatus') == '-1') {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= ' AND ' . grid_parse_request_var_into_sql_where(get_request_var('bstatus'), 'bhost_status');
		} else {
			$sql_where  = grid_parse_request_var_into_sql_where(get_request_var('bstatus'), 'bhost_status');
		}
	}

	if (get_request_var('resource_str') != '' && $is_summary_alarm_log == false) {
		if (get_request_var('clusterid') > 0) {
			$res_tool  = grid_get_res_tooldir(get_request_var('clusterid')) . '/gridhres';

			$cwd = getcwd();
			chdir(grid_get_res_tooldir(get_request_var('clusterid')));

			if (is_executable($res_tool)) {
				get_filter_request_var('clusterid');
				$res_cmd   = $res_tool . ' -C ' . get_request_var('clusterid') . ' -R ' . cacti_escapeshellarg(get_request_var('resource_str'));
				$ret_val   = 0;
				$ret_out   = array();
				$res_hosts = exec($res_cmd, $ret_out, $ret_val);
				//cacti_log("DEBUG: chdir = ". grid_get_res_tooldir(get_request_var('clusterid')) . " res_cmd = $res_cmd, ret_out = ". print_r($ret_out, true) . " res_hosts=$res_hosts ret_val = $ret_val");

				chdir($cwd);
				if (!$ret_val) {
					if (strlen($res_hosts)) {
						$parts = explode("\n", $res_hosts);
						if (cacti_sizeof($parts) > 1) {
							$res_hosts = $parts[1];
						}
						if (strlen($sql_where)) {
							$sql_where .= " AND grid_summary.host IN ($res_hosts)";
						} else {
							$sql_where = "WHERE grid_summary.host IN ($res_hosts)";
						}
					} else {
						if (strlen($sql_where)) {
							$sql_where .= ' AND grid_summary.host IS NULL';
						} else {
							$sql_where = 'WHERE grid_summary.host IN NULL';
						}
					}
				} else {
					if (strlen($sql_where)) {
						$sql_where .= ' AND grid_summary.host IS NULL';
					} else {
						$sql_where = 'WHERE grid_summary.host IN NULL';
					}

					if ($ret_val == 96) {
						print "summary_error:".  __('No hosts returned', 'grid');
						exit(1);
					} else if ($ret_val == 95) {
						print "summary_error:".  __('Invalid Resource String', 'grid');
						exit(1);
					} else {
						print "summary_error:".  __('Unknown LSF Error: %s', $ret_val, 'grid');
						exit(1);
					}
				}
			} else {
				cacti_log('ERROR: gridhres either does not exist or is not executable!');
			}
		} else {
			unset_request_var('resource_str');
			load_current_session_value('resource_str', 'sess_grid_view_summary_resource_str', '');
		}
	}

	/* determine if you should be showing the host or not */
	if ((get_request_var('exfilter') == 'true') ||
		(get_request_var('exfilter') == 'on')) {
		/* get the exclusion filter settings for the current user */
		$filters = db_fetch_assoc_prepared('SELECT value
			FROM grid_settings
			WHERE user_id = ?
			AND name LIKE "grid_filter%"',
			array($_SESSION['sess_user_id']));

		if (cacti_sizeof($filters)) {
			$sql_where .= ' AND (summary_status NOT IN(';
			$i=0;
			foreach($filters as $filter) {
				if ($i == 0) {
					$sql_where .= $filter['value'];
				} else {
					$sql_where .= ', ' . $filter['value'];
				}

				$filter_in[] = $filter['value'];
				$i++;
			}

			$sql_where .= '))';
		}
	}

	/* search filter sql where */
	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " ((grid_summary.host LIKE ?) OR
			(grid_summary.hostType LIKE ?) OR
			(grid_summary.hostModel LIKE ?))";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	/* create an order by clause */
	$order_by = 'ORDER BY ' . $orders[get_request_var('order')]['o'];

	if (get_request_var('hgroup') != -1) {
		$sql_query = "SELECT *
			FROM grid_summary
			INNER JOIN grid_hostgroups
			ON (grid_hostgroups.host=grid_summary.host)
			AND (grid_hostgroups.clusterid=grid_summary.clusterid)
			$sql_where
			$order_by";
	} else {
		$sql_query = "SELECT *
			FROM grid_summary
			$sql_where
			$order_by";
	}

	return db_fetch_assoc_prepared($sql_query, $sql_params);
}

function grid_build_legend($color) {
	global $config;

	$icon = '';

	switch ($color) {
		case 'red':
		case 'red_small':
			$name   = __('Unavailable', 'grid');
			$class  = 'hostDbDown';
			break;
		case 'black':
		case 'black_small':
			$name   = __('Admin Down', 'grid');
			$class  = 'hostDbAdminDown';
			break;
		case 'ured':
		case 'ured_small':
			$name   = __('Low Resources', 'grid');
			$class  = 'hostDbLowResources';
			break;
		case 'orange':
		case 'orange_small':
			$name   = __('Busy Closed', 'grid');
			$class  = 'hostDbBusyClosed';
			break;
		case 'cblue':
		case 'cblue_small':
			$name   = __('Idle Closed', 'grid');
			$class  = 'hostDbIdleClosed';
			break;
		case 'yellow':
		case 'yellow_small':
			$name   = __('Busy', 'grid');
			$class  = 'hostDbBusy';
			break;
		case 'blue':
		case 'blue_small':
			$name   = __('Idle w/Jobs', 'grid');
			$class  = 'hostDbIdleWithJobs';
			break;
		case 'grey':
		case 'grey_small':
			$name   = __('Starved', 'grid');
			$class  = 'hostDbStarved';
			break;
		case 'black_hole_small':
		case 'black_hole':
			$name   = __('Black Hole', 'grid');
			$class  = 'hostDbBlackHole';
			break;
		case 'alarm_blink':
		case 'alarm_blink_small':
   			$name   = __('Active Alert', 'grid');
			$class  = 'hostDbAlert';
			$icon   = 'fas fa-play unack';
			break;
		case 'alarm_static':
		case 'alarm_static_small':
			$name   = __('Paused Alert', 'grid');
			$class  = 'hostDbAlertSuspended';
			$icon   = 'fas fa-circle ack';
			break;
		case 'green_small':
		case 'green':
		default:
			$name   = __('Idle', 'grid');
			$class  = 'hostDbIdle';
			break;
	}

	switch(get_request_var('size')) {
	case 'exsmall':
	case 'small':
		$sclass = 'hostSmall';
		if ($icon != '') {
			$icon .= 'Small';
		}
		break;
	case 'medium':
		$sclass = 'hostMedium';
		if ($icon != '') {
			$icon .= 'Medium';
		}
		break;
	case 'large':
		$sclass = 'hostLarge';
		if ($icon != '') {
			$icon .= 'Large';
		}
		break;
	case 'xlarge':
		$sclass = 'hostExtraLarge';
		if ($icon != '') {
			$icon .= 'ExtraLarge fas fa-circle';
		}
		break;
	default:
		$sclass = 'hostMedium';
		if ($icon != '') {
			$icon .= 'Medium';
		}
		break;
	}

	if ($icon != '') {
		$icon = '<div class="ackIcon ' . $icon . '"></div>';
	}

	return "<div class='anchorLegend'><div id='legend:$color' class='fa fa-server $class $sclass'>$icon</div><br><div class='legendTitle'>" . html_escape($name) . '</div></div>';
}

function grid_minutes_to_days_hours_minutes($time) {
	$return = '';
	if ($time < 60) {
		$return = __('%s Min', $time, 'grid');
	} else {
		$hours = floor($time/60);
		$minutes = $time - ($hours * 60);

		if ($hours > 24) {
			$days = floor($hours/24);
			$hours = $hours - ($days * 24);

			$return = __('%s Days', $days, 'grid');

			if ($hours > 0) {
				$return .= ', ' . __('%d Hrs', $hours, 'grid');
			}

			if ($minutes > 0) {
				$return .= ', ' . __('%s Min', $minutes, 'grid');
			}
		} else {
			if ($hours > 0) {
				$return .= __('%s Hrs', $hours, 'grid');
			}

			if ($minutes > 0) {
				$return .= ', ' . __('%s Min', $minutes, 'grid');
			}
		}
	}

	return $return;
}

function grid_summary_check_audible() {
	$sql_params = array();

	/* get the current displayed cluster(s) */
	$cluster = get_request_var('clusterid');

	/* now, let's check for a change in host status */
	if (get_request_var('clusterid') == 0) {
		$sql_where = "WHERE ((monitor='on' AND disabled='') OR (monitor IS NULL))";
	} else {
		$sql_where = "WHERE ((monitor='on' AND disabled='') OR (monitor IS NULL))
			AND clusterid=?";
		$sql_params[] = get_filter_request_var('clusterid');
	}

	$sql_query = "SELECT host
		FROM grid_summary
		$sql_where
		AND (load_status IN('Unavail', 'SBD-Down' ,'Unlicensed'" .
		(read_config_option('grid_thold_resdown_status') == 1 ? ", 'RES-Down')":")") . "
		OR bhost_status IN ('Unavail', 'Closed-LIM', 'Unlicensed', 'Unreach'))
		ORDER BY host";

	$down_hosts = array_rekey(db_fetch_assoc_prepared($sql_query, $sql_params), 'host', 'host');

	/* get audible alerting status */
	$host_down = false;

	/* if any host is down, let's get ready to sound the alert */
	if (cacti_sizeof($down_hosts)) {
		/* check the session variable to see if we had any down hosts before */
		if (isset($_SESSION['sess_grid_view_summary_down_hosts_' . $cluster])) {
			foreach($down_hosts as $h) {
				if (isset($_SESSION['sess_grid_view_summary_down_hosts_' . $cluster][$h])) {
					$host_down++;
				}
			}
		} else {
			$host_down = cacti_sizeof($down_hosts);
		}

		$_SESSION['sess_grid_view_summary_down_hosts_' . $cluster] = $down_hosts;
	}

	return $host_down;
}

function display_hosts(&$hosts,$host_down,$host_alarm) {
	global $config, $row, $qc, $menu_link_add, $menu_content_add;

	$x = 0;
	$total_hosts = cacti_sizeof($hosts);

	$cluster_name   = '';
	$use_mouseovers = true;
	$display_links  = true;

	/* we will buffer all the output and then send as one block of data */
	ob_start();

	$newalarmlocal_voice = false;

	if ($total_hosts) {
		foreach ($hosts as $row) {
			$newalarmlocal_image = 0;

			if (!isset($_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $row['clusterid']])) {
				$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $row['clusterid']] = '0000-00-00';
			}

			$newalarmlocal_image = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM grid_hosts_alarm
				WHERE alert_time > ?
				AND acknowledgement="off"
				AND hostname != "lost_and_found"
				AND clusterid = ?
				AND hostname = ?',
				array(
					$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $row['clusterid']],
					$row['clusterid'],
					$row['host']
				));

			$newalarmlocal_voice += $newalarmlocal_image;

			$acknowledged_alarms = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM grid_hosts_alarm
				WHERE (alert_time < ? OR acknowledgement="on")
				AND hostname != "lost_and_found"
				AND clusterid = ?
				AND hostname= ?',
				array(
					$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $row['clusterid']],
					$row['clusterid'],
					$row['host']
				));

			if ($cluster_name != $row['clustername']) {
				if ($cluster_name != '') {
					print '</div></td></tr></table>';
				}

				print "<table class='cactiTable'>";

				html_header(array(__esc('Cluster: %s', strtoupper($row['clustername']), 'grid')), 100);

				print "<tr><td>\n<div class='anchorContainer'>";

				$cluster_name = $row['clustername'];
				$x = 0;
			}

			/* loop through each host one by one */
			if (isset($row['description'])) {
				$name = $row['description'];
			}else{
				$name = grid_strip_domain($row['host']);
			}

			$hostname =  $row['host'];
			$dname = strtoupper(grid_strip_domain($name));
			$gname = strtoupper($row['clustername']);

			$ttitle = "HOST: $dname <br>GRID: $gname";

			if ($total_hosts < read_grid_config_option('grid_icon_switch')) {
				print format_host_image($row, $acknowledged_alarms, $newalarmlocal_image);
			} else {
				print format_host_image($row, $acknowledged_alarms, $newalarmlocal_image);
			}

			$x++;
		}

		print '</div></td>';
	}

	if (read_grid_config_option('default_audible_alerting') == '') {
		print '<embed id="sound" type="application/x-mplayer2" src="' . $config['url_path'] . 'plugins/grid/images/attn-noc.wav" autostart="false" loop="true" volume="100" hidden="true">';
	} else {
		// If there are new alarms, you go off mute and alarm regardless
		if ($newalarmlocal_voice || $host_down || $host_alarm) {
			set_request_var('muteme', false);
			$_SESSION['sess_grid_view_summary_muteme'] = false;
			print '<embed id="sound" type="application/x-mplayer2" src="' . $config['url_path'] . 'plugins/grid/images/attn-noc.wav" autostart="true" loop="true" repeat="true" playcount="true" volume="100" hidden="true" enableJavaScript="true">';
		}
		// else, don't alarm
		else {
			print '<embed id="sound" type="application/x-mplayer2" src="' . $config['url_path'] . 'plugins/grid/images/attn-noc.wav" autostart="false" loop="true" repeat="true" playcount="true" volume="100" hidden="true" enableJavaScript="true">';
		}
	}

	// If we didn't close the table row above, do so now
	if ($x != 0) {
		print '</tr>';
	}

	ob_get_flush();
}

function display_legend() {
	print "<table class='cactiTable'><tr><td>";
	print '<div class="anchorContainer">';

	print grid_build_legend('red');
	print grid_build_legend('ured');
	print grid_build_legend('yellow');
	print grid_build_legend('orange');
	print grid_build_legend('cblue');
	print grid_build_legend('blue');
	print grid_build_legend('green');
	print grid_build_legend('grey');
	print grid_build_legend('black');
	print grid_build_legend('alarm_blink');
	print grid_build_legend('alarm_static');

	print '</div>';
	print "</td></tr></table>";
}

function summaryFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_summary_filters, $orders;

	?>
	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_summary.php'>
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
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_summary.php', 'Group', 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Cacti', 'grid');?>
					</td>
					<td>
						<select id='cacti'>
							<?php
							print '<option value="-1"'; if (get_request_var('cacti') == -1) { print ' selected'; } print '>' . __('All', 'grid') . '</option>';
							print '<option value="-2"'; if (get_request_var('cacti') == -2) { print ' selected'; } print '>' . __('Not Integrated', 'grid') . '</option>';
							print '<option value="0"'; if (get_request_var('cacti') ==  0) { print ' selected'; } print '>' . __('Unknown', 'grid') . '</option>';
							print '<option value="1"'; if (get_request_var('cacti') ==  1) { print ' selected'; } print '>' . __('Down', 'grid') . '</option>';
							print '<option value="2"'; if (get_request_var('cacti') ==  2) { print ' selected'; } print '>' . __('Recovering', 'grid') . '</option>';
							print '<option value="3"'; if (get_request_var('cacti') ==  3) { print ' selected'; } print '>' . __('Up', 'grid') . '</option>';
							?>
						</select>
					</td>
					<td>
						<?php print __('Order', 'grid');?>
					</td>
					<td>
						<select id='order'>
							<?php
							foreach($orders as $key => $value) {
								print "<option value='$key'"; if (get_request_var('order') == $key) { print ' selected'; } print '>' . $value['d'] . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='button' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
							<input type='button' id='save' value='<?php print __esc('Save', 'grid');?>' title='<?php print __esc('Save Dashboard Settings', 'grid');?>'>
					<?php if (read_grid_config_option('default_audible_alerting') == '') { /* do nothing */ ?>
					<?php } else {
					if (get_request_var('muteme') == true) {?>
							<input type='button' style='display:none' id='mute' value='<?php print __esc('Mute', 'grid');?>' title='<?php print __esc('Mute Alert Sound Acknowledging Host Down Conditions and Host Alerts. New Host Alerts and Down Conditions will Unmute.', 'grid');?>' onClick='muteFilter()'>
							<input type='button' style='display:' id='unmute' value='<?php print __esc('Unmute', 'grid');?>' title='<?php print __esc('Unmute Sounds', 'grid');?>' onClick='unmuteFilter()'>
					<?php } else {?>
							 <input type='button' style='display:' id='mute' value='<?php print __esc('Mute', 'grid');?>' title='<?php print __esc('Mute Alert Sound Acknowledging Host Down Conditions and Host Alerts. New Host Alerts and Down Conditions will Unmute.', 'grid');?>' onClick='muteFilter()'>
							<input type='button' style='display:none' id='unmute' value='<?php print __esc('Unmute', 'grid');?>' title='<?php print __esc('Unmute Sounds', 'grid');?>' onClick='unmuteFilter()'>
						<?php }?>
					</td>
					<?php }?>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Alerts', 'grid');?>
					</td>
					<td>
						<select id='tholds'>
							<?php
							print '<option value="-1"'; if (get_request_var('tholds') == -1) { print ' selected'; } print '>' . __('All', 'grid') . '</option>';
							foreach($grid_summary_filters as $key => $val) {
								print '<option value="' . $key . '"'; if (get_request_var('tholds') == $key) { print ' selected'; } print '>' . $val . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc('SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> "FLOATING"
									AND hostType NOT LIKE "U%"
									ORDER BY hostType');
							} else {
								$types = db_fetch_assoc_prepared('SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> "FLOATING"
									AND hostType NOT LIKE "U%"
									AND clusterid = ?
									ORDER BY hostType',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] .'"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc('SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel != "N/A"
									AND hostModel NOT LIKE "%UNKNOWN%"
									ORDER BY hostModel');
							} else {
								$models = db_fetch_assoc_prepared('SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel != "N/A"
									AND hostModel NOT LIKE "%UNKNOWN%"
									AND clusterid = ?
									ORDER BY hostModel',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($models)) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Size', 'grid');?>
					</td>
					<td>
						<select id='size'>
							<option value='exsmall'<?php if (get_request_var('size') == 'exsmall') {?> selected<?php }?>><?php print __('Extra Small', 'grid');?></option>
							<option value='small'<?php if (get_request_var('size') == 'small') {?> selected<?php }?>><?php print __('Small', 'grid');?></option>
							<option value='medium'<?php if (get_request_var('size') == 'medium') {?> selected<?php }?>><?php print __('Medium', 'grid');?></option>
							<option value='large'<?php if (get_request_var('size') == 'large') {?> selected<?php }?>><?php print __('Large', 'grid');?></option>
							<option value='xlarge'<?php if (get_request_var('size') == 'xlarge') {?> selected<?php }?>><?php print __('Extra Large', 'grid');?></option>
						</select>
					</td>
					<td>
						<span class='shost' style='display:none'>
							<input id='shostname' type='checkbox'<?php if ((get_request_var('shostname') == 'true') || (get_request_var('shostname') == 'on')) print ' checked';?>>
							<label for='shostname'><?php print __('Show Hostname', 'grid');?></label>
						</span>
					</td>
					<td>
						<input id='exfilter' type='checkbox'<?php if ((get_request_var('exfilter') == 'true') || (get_request_var('exfilter') == 'on')) print ' checked';?>>
					</td>
					<td>
						<label for='exfilter'><?php print __('Apply Exclusion Filter', 'grid');?></label>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Load', 'grid');?>
					</td>
					<td>
						<select id='lstatus'>
							<option value='-1'<?php if (get_request_var('lstatus') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT status
									FROM grid_load
									ORDER BY status');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
									FROM grid_load
									WHERE clusterid = ?
									ORDER BY status',
									array(get_request_var('clusterid')));
							}

							$found = false;
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									if ($status['status'] == get_request_var('lstatus')) {
										$found = true;
										break;
									}
								}
							}

							if (!$found && get_request_var('lstatus') != '-1') {
								print '<option value="' . get_request_var('lstatus') .'" selected>' . __('Custom', 'grid') . '</option>';
							}

							reset($stati);
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['status'] .'"'; if (get_request_var('lstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Batch', 'grid');?>
					</td>
					<td>
						<select id='bstatus'>
							<option value='-1'<?php if (get_request_var('bstatus') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT status
									FROM grid_hosts
									ORDER BY status');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
									FROM grid_hosts
									WHERE clusterid = ?
									ORDER BY status',
									array(get_request_var('clusterid')));
							}

							$found = false;
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									if ($status['status'] == get_request_var('bstatus')) {
										$found = true;
										break;
									}
								}
							}

							if (!$found && get_request_var('bstatus') != '-1') {
								print '<option value="' . get_request_var('bstatus') .'" selected>' . __('Custom', 'grid') . '</option>';
							}

							reset($stati);
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['status'] .'"'; if (get_request_var('bstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
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
						<?php print __('Alert Action', 'grid');?>
					</td>
					<td>
						<select id='ack' onChange='ackHostAlerts()'>
							<?php
							$authfull = api_plugin_user_realm_auth('LSF_Host_Alert_Stop');

							if ($authfull) {
								print '<option value="-1">' . __('Stop', 'grid') . '</option>';
								print '<option value="-2">' . __('Stop for All Users', 'grid') . '</option>';
								print '<option value="0">' . __('Resume Stop', 'grid') . '</option>';
								print '<option value="1">' . __('Resume Stop for All Users', 'grid') . '</option>';
								print '<option value="2" selected >' . __('No Action', 'grid') . '</option>';
							} else {
								print '<option value="-1">' . __('Stop', 'grid') . '</option>';
								print '<option value="0">' . __('Resume Stop', 'grid') . '</option>';
								print '<option value="2" selected>' . __('No Action', 'grid') . '</option>';
							} ?>
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
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<?php
					if (get_request_var('clusterid') == 0) {
						print '<td id="td_resource_str" style="display:none">';
						print '</td>';
						print '<td>';
						print '<input type="text" id="resource_str" name="resource_str" size="30" style="display:none" value="'; print get_request_var('resource_str'); print '" title="">';
						print '</td>';
					} else {
						$resources = db_fetch_assoc_prepared("SELECT resource_name
							FROM (
								SELECT resource_name
								FROM grid_hostresources
								WHERE clusterid= ?
								UNION
								SELECT resource_name
								FROM grid_hosts_resources
								WHERE clusterid= ?
							) AS resources
							ORDER BY resource_name", array(get_request_var('clusterid'),get_request_var('clusterid')));

						$title = '';
						if (cacti_sizeof($resources)) {
							foreach($resources as $r) {
								$title .= (strlen($title) ? ', ' : __('Available Resources', 'grid')) . ' ' .  $r['resource_name'];
							}
						}

						print '<td id="td_resource_str" style="display:true;white-space:nowrap;">';
						print __('ResReq', 'grid');
						print '</td>';
						print '<td width="1">';
						print '<input type="text" id="resource_str" name="resource_str" size="30" style="display:true;white-space:nowrap;" value="'; print get_request_var('resource_str'); print '" title="'; print $title; print '">';
						print '</td>';
					}
					?>
				</tr>
			</table>
			<input type='hidden' id='page' value='1'>
			</form>
		</td>
	</tr>
	<?php
}

function summary() {
	global $config;

	/* set the default tab */
	load_current_session_value('tab', 'sess_gridhosts_tab', 'host');
	$current_tab = get_request_var('tab');

	grid_set_minimum_page_refresh();

	if ($current_tab == 'host') {
		summary_host();
	} else {
		summary_alarm_log();
	}

	bottom_footer();
}

function summary_alarm_log() {
	global $config,$log_types;
	$sql_params = array();

	$hosts = grid_view_get_summary_records(true);

	general_header();

	/* present a tabbed interface */
	$tabs_gridhost = array(
		'host'  => __('Host', 'grid'),
		'alarm' => __('Alerts', 'grid')
	);

	/* draw the tabs */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";
	if (cacti_sizeof($tabs_gridhost)) {
		$i = 0;
		foreach (array_keys($tabs_gridhost) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == get_request_var('tab')) ? "class='pic selected'" : "class='pic '") . " href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_summary.php?' .
				'tab=' . $tab_short_name) .
				"'>" . html_escape($tabs_gridhost[$tab_short_name]) . '</a></li>';
			$i++;
		}
	}

	print '</ul></nav></div>';
	print '</tr></table>';

	/* build the user interface */
	html_start_box(__('Alert Filter Options', 'grid'), '100%', '', '3', 'center', '');
	summary_alarm_filter();
	html_end_box();

	$alarm_num_rows = read_config_option('alarm_num_rows');

	if ($alarm_num_rows < 1 || $alarm_num_rows > 999) {
		set_config_option('alarm_num_rows', 30);

		/* pull it again so it updates the cache */
		$alarm_num_rows = read_config_option('alarm_num_rows', true);
	}

	if (get_request_var('alarm') == -2) {
		$sql_where = 'WHERE type=0';
	} else if (get_request_var('alarm') == 0) {
		$sql_where = 'WHERE type=1';
	} else if (get_request_var('alarm') == 1) {
		$sql_where = 'WHERE type=2';
	} else if (get_request_var('alarm') == 2) {
		$sql_where = 'WHERE type=3';
	} else if (get_request_var('alarm') == 3) {
		$sql_where ='';
	}

	if (get_request_var('hostname') != 'no') {
		if ($sql_where == '') {
			$sql_where = 'WHERE hostname=?';
			$sql_params[] = get_request_var('hostname');
		} else {
			$sql_where = $sql_where . ' AND hostname=?';
			$sql_params[] = get_request_var('hostname');
		}
	}

	$hostname_sql = '';

	if (cacti_sizeof($hosts)>0) {
		foreach ($hosts as $host) {
			if ($hostname_sql == '') {
				$hostname_sql = "'" . $host['host'] . '_' . $host['clusterid'] . "'";
			} else {
				$hostname_sql = $hostname_sql . ",'" . $host['host'] . '_' . $host['clusterid'] . "'";
			}
			if (isset_request_var('query')) {

				$hostname_sql = "'" . get_request_var('hostname') . '_' . $host['clusterid'] ."'";
			}
		}
	}

	if ($hostname_sql != '') {
		if ($sql_where == '') {
			$sql_where = "WHERE CONCAT(hostname,'_',clusterid) IN (" . $hostname_sql . ')';
		} else {
			$sql_where=$sql_where . " AND CONCAT(hostname,'_',clusterid) IN (" . $hostname_sql . ')';
		}

		$limit = ' LIMIT ' . ($alarm_num_rows*(get_request_var('page')-1)) . ",$alarm_num_rows";
		$sort_order = get_order_string();

		$sql = "SELECT *  FROM grid_hosts_alarm
			$sql_where
			$sort_order
			$limit";

		$result = db_fetch_assoc_prepared($sql, $sql_params);

		$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM grid_hosts_alarm
			$sql_where", $sql_params);
	} else {
		$result=array();
		$total_rows=0;

	}

	?>
	<script type='text/javascript'>

	function summaryAlertFilterChange() {
		strURL  = urlPath + 'plugins/grid/grid_summary.php?header=false&tab=alarm';
		strURL += '&clusterid='    + $('#clusterid').val();
		strURL += '&hostname='     + $('#hostname').val();
		strURL += '&cacti='        + $('#cacti').val();
		strURL += '&hgroup='       + encodeURIComponent($('#hgroup').val());
		strURL += '&refresh='      + $('#refresh').val();
		strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
		strURL += '&lstatus='      + $('#lstatus').val();
		strURL += '&bstatus='      + $('#bstatus').val();
		strURL += '&tholds='       + $('#tholds').val();
		strURL += '&filter='       + $('#filter').val();
		strURL += '&type='         + $('#type').val();
		strURL += '&model='        + $('#model').val();
		strURL += '&alarm='        + $('#alarm').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = urlPath + 'plugins/grid/grid_summary.php?header=false&clear=true&tab=alarm';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			summaryAlertFilterChange();
		});

		$('#exfilter, #shostname').click(function() {
			summaryAlertFilterChange();
		});

		$('#clusterid, #hostname, #hgroup, #cacti, #tholds, #type, #model, #alarm, #lstatus, #bstatus, #refresh, #filter, #resource_str').change(function() {
			summaryAlertFilterChange();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		applySkinRTM();
	});

	</script>
	<?php

	$display_text = array(
		'type'	      => array('display' => __('Alert Type', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		'name'        => array('display' => __('Name', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		'hostname'    => array('display' => __('Host', 'grid'), 'align' => 'left', 'sort' => 'ASC'),
		'clusterid'   => array('display' => __('Cluster', 'grid'), 'align' => 'right', 'sort' => 'ASC'),
		'nosort'      => array('display' => __('Message', 'grid'), 'align' => 'left'),
		'alert_time'  => array('display' => __('Alert Time', 'grid'), 'align' => 'right', 'sort' => 'DESC')
	);

	$nav = html_nav_bar('grid_summary.php?tab=alarm&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $alarm_num_rows, $total_rows, cacti_sizeof($display_text)+1, __('Batch Hosts', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 1, 'grid_summary.php?tab=alarm');

	$i = 0;
	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			form_alternate_row('line' . $i); $i++;
			form_selectable_cell(display_alarmtype_name($row['type']), $i);
			form_selectable_cell((strlen($row['name']) ? html_escape($row['name']):__('Alert Removed', 'grid')), $i);
			form_selectable_cell(html_escape($row['hostname']), $i);
			form_selectable_cell($row['clusterid'], $i, '', 'right');

			if ($row['type'] == 0) {
				$hostid = db_fetch_cell_prepared('SELECT id
					FROM host
					WHERE hostname= ?
					AND clusterid = ?',
					array($row['hostname'], $row['clusterid']));

				form_selectable_cell(filter_value($row['message'], '', $config['url_path'] . 'plugins/thold/thold.php?hostid=' . $hostid), $i);
			} else if ($row['type'] == 1) {
				form_selectable_cell(filter_value($row['message'], '', $config['url_path'] . 'plugins/syslog/syslog.php?id=' . $row['type_id'] . '&tab=current'), $i);
			} else if ($row['type'] == 2) {
				form_selectable_cell(filter_value($row['message'], '', $config['url_path'] . 'plugins/gridalarms/gridalarms_alarm.php?tab=alarms&state=3'), $i);
			} else if ($row['type'] == 3) {
				form_selectable_cell(html_escape($row['message']), $i);
			}

			form_selectable_cell($row['alert_time'], $i, '', 'right');

			form_end_row();
		}

		html_end_box(false);

		print $nav;
	} else {
		form_alternate_row();
		print '<td colspan=15><center>' . __('No Alert Log Entries Found', 'grid') . '</center></td>';
		form_end_row();
		html_end_box(false);
	}
}

function summary_alarm_filter() {
	global $config, $grid_rows_selector, $grid_refresh_interval, $grid_summary_filters, $orders;

	?>
	<tr>
		<td>
		<form id='form_grid' action='grid_summary.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected <?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = grid_get_clusterlist();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Host', 'grid');?>
					</td>
					<td>
						<select id='hostname'>
							<option value='no'<?php if (get_request_var('hostname') == '') {?> selected <?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == '0') {
								$hosts = db_fetch_assoc('SELECT distinct host
									FROM grid_hosts
									ORDER BY host');
							} else {
								$hosts = db_fetch_assoc_prepared('SELECT host
									FROM grid_hosts
									WHERE clusterid = ?
									ORDER BY host',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($hosts)) {
								foreach($hosts as $host) {
									print '<option value="' . $host['host'] .'"'; if (get_request_var('hostname') == $host['host']) { print ' selected'; } print '>' . $host['host'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_summary.php', 'Group', 'hgroup', get_request_var('hgroup'), 'summaryAlertFilterChange', get_request_var('clusterid') >0 ? 'clusterid = ' . get_request_var('clusterid') : '');?>
					<td>
						<?php print __('Cacti', 'grid');?>
					</td>
					<td>
						<select id='cacti'>
							<?php
							print '<option value="-1"'; if (get_request_var('cacti') == -1) { print ' selected'; } print '>' . __('All', 'grid') . '</option>';
							print '<option value="-2"'; if (get_request_var('cacti') == -2) { print ' selected'; } print '>' . __('Not Integrated', 'grid') . '</option>';
							print '<option value="0"'; if (get_request_var('cacti') ==  0) { print ' selected'; } print '>' . __('Unknown', 'grid') . '</option>';
							print '<option value="1"'; if (get_request_var('cacti') ==  1) { print ' selected'; } print '>' . __('Down', 'grid') . '</option>';
							print '<option value="2"'; if (get_request_var('cacti') ==  2) { print ' selected'; } print '>' . __('Recovering', 'grid') . '</option>';
							print '<option value="3"'; if (get_request_var('cacti') ==  3) { print ' selected'; } print '>' . __('Up', 'grid') . '</option>';
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
						<?php print __('Alerts', 'grid');?>
					</td>
					<td>
						<select id='tholds'>
							<?php
							print '<option value="-1"'; if (get_request_var('tholds') == -1) { print ' selected'; } print '>' . __('All', 'grid') . '</option>';
							foreach($grid_summary_filters as $key => $val) {
								print '<option value="' . $key . '"'; if (get_request_var('tholds') == $key) { print ' selected'; } print '>' . $val . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc('SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> "FLOATING"
									AND hostType NOT LIKE "U%"
									ORDER BY hostType');
							} else {
								$types = db_fetch_assoc_prepared('SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> "FLOATING"
									AND hostType NOT LIKE "U%"
									AND clusterid = ?
									ORDER BY hostType',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($types)) {
								foreach ($types as $type) {
									print '<option value="' . $type['hostType'] .'"'; if (get_request_var('type') == $type['hostType']) { print ' selected'; } print '>' . $type['hostType'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc('SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!="N/A"
									AND hostModel NOT LIKE "%UNKNOWN%"
									ORDER BY hostModel');
							} else {
								$models = db_fetch_assoc_prepared('SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!="N/A"
									AND hostModel NOT LIKE "%UNKNOWN%"
									AND clusterid = ?
									ORDER BY hostModel',
									array(get_request_var('clusterid')));
							}

							if (cacti_sizeof($models)) {
								foreach ($models as $model) {
									print '<option value="' . $model['hostModel'] .'"'; if (get_request_var('model') == $model['hostModel']) { print ' selected'; } print '>' . $model['hostModel'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Alert', 'grid');?>
					</td>
					<td>
						<select id='alarm'>
							<?php
							print '<option value="3"'; if (get_request_var('alarm') ==  3) { print ' selected'; } print '>' . __('All', 'grid') . '</option>';
							print '<option value="-2"'; if (get_request_var('alarm') == -2) { print ' selected'; } print '>' . __('Thold', 'grid') . '</option>';
							print '<option value="0"'; if (get_request_var('alarm') ==  0) { print ' selected'; } print '>' . __('Syslog', 'grid') . '</option>';
							print '<option value="1"'; if (get_request_var('alarm') ==  1) { print ' selected'; } print '>' . __('Grid', 'grid') . '</option>';
							print '<option value="2"'; if (get_request_var('alarm') ==  2) { print ' selected'; } print '>' . __('Batch', 'grid') . '</option>';
							?>
						</select>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Load', 'grid');?>
					</td>
					<td>
						<select id='lstatus'>
							<option value='-1'<?php if (get_request_var('lstatus') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT status
									FROM grid_load
									ORDER BY status');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
									FROM grid_load
									WHERE clusterid = ?
									ORDER BY status',
									array(get_request_var('clusterid')));
							}

							$found = false;
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									if ($status['status'] == get_request_var('lstatus')) {
										$found = true;
										break;
									}
								}
							}

							if (!$found && get_request_var('lstatus') != '-1') {
								print '<option value="' . get_request_var('lstatus') .'" selected>' . __('Custom', 'grid') . '</option>';
							}

							reset($stati);
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['status'] .'"'; if (get_request_var('lstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Batch', 'grid');?>
					</td>
					<td>
						<select id='bstatus'>
							<option value='-1'<?php if (get_request_var('bstatus') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							if (get_request_var('clusterid') == 0) {
								$stati = db_fetch_assoc('SELECT DISTINCT status
									FROM grid_hosts
									ORDER BY status');
							} else {
								$stati = db_fetch_assoc_prepared('SELECT DISTINCT status
									FROM grid_hosts
									WHERE clusterid = ?
									ORDER BY status',
									array(get_request_var('clusterid')));
							}

							$found = false;
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									if ($status['status'] == get_request_var('bstatus')) {
										$found = true;
										break;
									}
								}
							}

							if (!$found && get_request_var('bstatus') != '-1') {
								print '<option value="' . get_request_var('bstatus') .'" selected>' . __('Custom', 'grid') . '</option>';
							}

							reset($stati);
							if (cacti_sizeof($stati)) {
								foreach ($stati as $status) {
									print '<option value="' . $status['status'] .'"'; if (get_request_var('bstatus') == $status['status']) { print ' selected'; } print '>' . $status['status'] . '</option>';
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
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							foreach($grid_refresh_interval as $key => $value) {
								if ($key >= $max_refresh) {
									print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
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
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<?php if (get_request_var('clusterid') > 0 && get_request_var('tab') == 'host') {
						resource_browser();
					} else {?>
						<?php print "<td><input type='hidden' value='' id='resource_str'></td>"; }?>
				</tr>
			</table>
			<input type='hidden' name='tab' value='alarm'>
			<input type='hidden' name='page' value='1'>
		</form>
		</td>
	</tr>
	<?php
}

function summary_host() {
	global $config;

	/* present a tabbed interface */
	$tabs_gridhost = array(
		'host'  => __('Host', 'grid'),
		'alarm' => __('Alerts', 'grid')
	);

	general_header();

	print "<div id='error'></div>";
	/* draw the tabs */
	print "<table><tr><td style='padding-bottom:0px;'>";
	print "<div class='tabs' style='float:left;'><nav><ul role='tablist'>";
	if (cacti_sizeof($tabs_gridhost)) {
		$i = 0;
		foreach (array_keys($tabs_gridhost) as $tab_short_name) {
			print "<li role='tab' tabindex='$i' aria-controls='tabs-" . ($i+1) . "' class='subTab'><a role='presentation' tabindex='-1' " . (($tab_short_name == get_request_var('tab')) ? "class='pic selected'" : "class='pic '") . " href='" . html_escape($config['url_path'] .
				'plugins/grid/grid_summary.php?' .
				'tab=' . $tab_short_name) .
				"'>" . html_escape($tabs_gridhost[$tab_short_name]) . '</a></li>';
			$i++;
		}
	}
	print '</ul></nav></div>';
	print '</tr></table>';

	/* build the user interface */
	html_start_box(__('Dashboard Filter', 'grid') . "</span>&nbsp;<div id='status' class='fa fa-spin fa-sync deviceUp' style='margin:0px;padding:0px;vertical-align:-10%'></div><span id='message'></span>", '100%', '', '3', 'center', '');
	summaryFilter();
	html_end_box();

	html_start_box(__('LSF Host Status', 'grid'), '100%', '', '0', 'center', '');
	print '<tr><td>';

	print "<div id='hostdiv'><table class='cactiTable'><tr><td>" . __('Rendering Page, Please Wait...', 'grid') . "</td></tr></table></div>";

	print '</td></tr>';

	html_end_box();

	/* display the legend */
	html_start_box(__('Host Status Legend', 'grid'), '100%', true, '3', 'center', '');

	print "<div id='legenddiv'>";

	display_legend();

	print "</div>";

	html_end_box(false, true);

	/* new popup method of host details */
	print "<div id='hddialog'></div>";
	?>
	<style type='text/css'>

	.metricTable {
		padding:0px;
		margin:0px;
		font-size:11px;
		width: 100%;
		margin-left: auto;
		margin-right: auto;
	}

	.metricTable .tableHeader {
		padding: 0px 5px;
	}

	.metricTable .tableSubHeaderColumn {
		padding: 0px 5px;
		line-height: 20px !important;
	}

	.metricRow {
	}

	.metricRow td {
		padding: 0px 5px !important;
	}

	.metricRow td:nth-child(odd) {
		font-weight: 600;
	}

	.anchorContainer {
		display: flex;
		flex-basis: 0;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.anchorChild {
		text-align: center;
		vertical-align: top;
//		width: 12px;
		padding: 0px 2px;
		display: inline-block;
		margin-left: auto;
		margin-right: auto;
	}

	.anchorLegend {
		text-align: center;
		vertical-align: top;
		width: 44px;
		padding: 0px 2px;
		display: inline-table;
		margin-left: auto;
		margin-right: auto;
	}

	#sound {
		display: none;
	}

	.legendTable td {
		padding: 0px 5px;
	}

	.legendThreshold td:nth-child(odd) {
		font-weight: bold;
	}

	.legendTable u {
		font-weight: bold;
	}

	.hostDbDown {
		position: relative;
		color: red;
		text-align: center;
	}

	.hostDbAdminDown {
		position: relative;
		color: blue;
		text-align: center;
	}

	.hostDbLowResources {
		position: relative;
		color: crimson;
		text-align: center;
	}

	.hostDbBusyClosed {
		position: relative;
		color: orange;
		text-align: center;
	}

	.hostDbIdleClosed {
		position: relative;
		color: darkcyan;
		text-align: center;
	}

	.hostDbBusy {
		position: relative;
		color: coral;
		text-align: center;
	}

	.hostDbIdleWithJobs {
		position: relative;
		color: DeepSkyBlue;
		text-align: center;
	}

	.hostDbIdle {
		position: relative;
		color: green;
		text-align: center;
	}

	.hostDbBlackHole {
		position: relative;
		color: black;
		text-align: center;
	}

	.hostDbStarved {
		position: relative;
		color: grey;
		text-align: center;
	}

	.hostDbAlert {
		position: relative;
		color: lightgrey;
		text-align: center;
	}

	.hostDbAlertSuspended {
		position: relative;
		color: lightgrey;
		text-align: center;
	}

	.ackIcon {
		position: absolute;
		z-index: 1;
	}

	.ackSmall {
		color: darkcyan;
		font-size: 11px;
		bottom: 2px;
		right: 0px;
	}

	.unackSmall {
		color: orange;
		font-size: 11px;
		transform: rotate(-90deg);
		bottom: 1px;
		right: 2px;
	}

	.ackMedium {
		color: darkcyan;
		font-size: 13px;
		bottom: 2px;
		right: 0px;
	}

	.unackMedium {
		color: orange;
		font-size: 13px;
		transform: rotate(-90deg);
		bottom: 1px;
		right: 2px;
	}

	.ackLarge {
		color: darkcyan;
		font-size: 18px;
		bottom: 2px;
		right: 0px;
	}

	.unackLarge {
		color: orange;
		font-size: 18px;
		transform: rotate(-90deg);
		bottom: 1px;
		right: 2px;
	}

	.fa-server {
		pointer: cursor;
	}

	.ackExtraLarge {
		color: darkcyan;
		font-size: 22px;
		bottom: 2px;
		right: 0px;
	}

	.unackExtraLarge {
		color: orange;
		font-size: 22px;
		transform: rotate(-90deg);
		bottom: 1px;
		right: 2px;
	}

	.legendTitle {
		font-size: 12px;
		font-weight: normal;
		text-align: center;
		line-height: 17px;
		width: 110px;
	}

	.hostExSmall {
		font-size: 15px;
		margin: auto;
	}

	.hostSmall {
		font-size: 30px;
		margin: auto;
	}

	.hostMedium {
		font-size: 45px;
	}

	.hostLarge {
		font-size: 60px;
	}

	.hostExtraLarge {
		font-size: 70px;
	}

	div .linkEditMain {
		clear: both;
		font-size: 12px;
		font-weight: bold;
	}

	@keyframes blink {
		0% {
			opacity: 1;
		}
		50% {
			opacity: 0;
		}
		100% {
			opacity: 1;
		}
	}
	.imgblink_notyet {
		animation: blink 2s;
		animation-iteration-count: infinite;
	}

	</style>
	<script type='text/javascript'>

	function saveFilter() {
		strURL  = urlPath + 'plugins/grid/grid_summary.php?header=false&action=ajaxsave';
		strURL += '&clusterid='    + $('#clusterid').val();
		strURL += '&hgroup='       + encodeURIComponent($('#hgroup').val());
		strURL += '&cacti='        + $('#cacti').val();
		strURL += '&order='        + $('#order').val();
		strURL += '&limit='        + $('#limit').val();
		strURL += '&refresh='      + $('#refresh').val();
		strURL += '&lstatus='      + $('#lstatus').val();
		strURL += '&bstatus='      + $('#bstatus').val();
		strURL += '&tholds='       + $('#tholds').val();
		strURL += '&size='         + $('#size').val();
		strURL += '&exfilter='     + $('#exfilter').is(':checked');
		strURL += '&shostname='    + $('#shostname').is(':checked');
		strURL += '&filter='       + $('#filter').val();
		strURL += '&model='        + $('#model').val();
		strURL += '&type='         + $('#type').val();

		if ($('#size').val() == 'exsmall') {
			$('.shost').hide();
		} else {
			$('.shost').show();
		}

		Pace.track(function() {
			$.get(strURL, function() {
				$('#message').html('').show().html('<?php print __('Filter Settings Saved', 'grid');?>').delay(2000).fadeOut(1000);
			});
		});
	}

	function applyFilter(clear_mode) {
		if (typeof $('#clusterid').val() == 'undefined') {
			if (typeof timer3 != 'undefined') {
				clearTimeout(timer3);
			}
			return;
		}

		clear_mode = (typeof clear_mode == 'undefined' ? false : true);

		strURL = urlPath + 'plugins/grid/grid_summary.php?action=ajaxrefresh';

		if (!clear_mode) {
			strURL += '&clusterid=' + $('#clusterid').val();
		}

		strURL += '&cacti='        + $('#cacti').val();
		strURL += '&order='        + $('#order').val();
		strURL += '&hgroup='       + encodeURIComponent($('#hgroup').val());
		strURL += '&refresh='      + $('#refresh').val();
		strURL += '&resource_str=' + encodeURIComponent($('#resource_str').val());
		strURL += '&lstatus='      + $('#lstatus').val();
		strURL += '&bstatus='      + $('#bstatus').val();
		strURL += '&tholds='       + $('#tholds').val();
		strURL += '&size='         + $('#size').val();
		strURL += '&exfilter='     + $('#exfilter').is(':checked');
		strURL += '&shostname='    + $('#shostname').is(':checked');
		strURL += '&filter='       + $('#filter').val();
		strURL += '&model='        + $('#model').val();
		strURL += '&type='         + $('#type').val();

		if ($('#ack').val() != 2) {
			strURL += '&ack=' + $('#ack').val();
		}

		if (typeof timer3 != 'undefined') {
			clearTimeout(timer3);
		}

		if ($('#size').val() == 'exsmall') {
			$('.shost').hide();
		} else {
			$('.shost').show();
		}

		$('#status').show();
		$.ajaxQ.abortAll();
		Pace.track(function() {
			$.get(strURL)
				.done(function(data) {
					if(data.substring(0,14) == "summary_error:"){
						var data_Message = "<span class=\"deviceDown\">" + data.substring(14) + "<\/span>";
						sessionMessage = {"level":3,"message":data_Message}; //3:MESSAGE_LEVEL_ERROR
						displayMessages();
						data = '';
					}
					checkForLogout(data);

					$('#hostdiv').html(data);
					$('#status').hide();

					initializeHostIcons();

					timer3 = setTimeout(function() { applyFilter() }, $('#refresh').val()*1000);

					mute = $('#mute').css('display');
					if ($('#alert_mute').val() == 'true' && mute != 'inline' && mute != '') {
						$('#mute').css('display','none');
						$('#unmute').css('display','');
					} else {
						$('#mute').css('display','');
						$('#unmute').css('display','none');
					}

					if (typeof $('#reason').val() != 'undefined') {
						$('#newreason').remove();
						$('#resource_str').parent().after("<td colspan='4' width='1000px' style='padding-left:10px;' id='newreason'><?php print __('Last Alert', 'grid');?> " + $('#reason').val() + '</td>');
					}

					$('#ack').val(2);

					updateLegend();

					return false;
				})
				.fail(function(data) {
					getPresentHTTPError(data);
				}
			);
		});
	}

	function updateLegend() {
		strURL = urlPath + 'plugins/grid/grid_summary.php?action=ajaxlegendall&size=' + $('#size').val();

		Pace.track(function() {
			$.get(strURL, function(data) {
				$('#legenddiv').html(data);
				initLegend();
			});
		});
	}

	function initLegend() {
		$('[id^="legend:"]').mouseover(function(event) {
			if (typeof timer1 != 'undefined') {
				clearTimeout(timer1);
			}
			closeHostDialog();
			timer2=setTimeout(function () { openHostDialog(event) }, 500);
		}).mouseout(function() {
			if (typeof timer2 != 'undefined') {
				clearTimeout(timer2);
			}
			timer1=setTimeout(function() { closeHostDialog() }, 500);
		}).click(function() {
			if (typeof timer2 != 'undefined') {
				clearTimeout(timer2);
			}
			timer1=setTimeout(function() { closeHostDialog() }, 500);
		});

		$('#hddialog').mouseenter(function() {
			clearTimeout(timer1);
			clearTimeout(timer2);
		});

		$('#hddialog').mouseleave(function() {
			closeHostDialog();
		});
	}

	function ackHostAlerts() {
		action=$('#ack').val();

		if (action== -1) {
			$('#dmessage').html('<?php print __('Stop All Hosts Alerts.', 'grid');?>');
		} else if (action == -2) {
			$('#dmessage').html('<?php print __('Stop All Host Alerts for All Users.', 'grid');?>');
		} else if (action == 0) {
			$('#dmessage').html('<?php print __('Resume All Host Alerts.', 'grid');?>');
		} else if (action == 1) {
			$('#dmessage').html('<?php print __('Resume All Host Alerts for All Users.', 'grid');?>');
		} else if (action == 2) {
			return 0;
		}

		$('#ddialog').dialog('open');
	}

	function ackHostAlertsExecute() {
		$('#ddialog').dialog('close');
		applyFilter();
		$('#ack').val(2);
	}

	function cancelHostAlertAck() {
		$('#ddialog').dialog('close');
		$('#ack').val(2);
	}

	function getGroupTypeMode(strURL) {
		hostgroup_value=$('#hgroup').val();
		type_value=$('#type').val();
		model_value=$('#model').val();
		lstatus_value=$('#lstatus').val();
		bstatus_value=$('#bstatus').val();
		strURL = strURL + '&lstatus=' + lstatus_value + '&bstatus=' + bstatus_value ;
		Pace.track(function() {
			$.get(strURL,function(data) {
				var data_array=data.split('<br/>');
				$('#hgroup').html(data_array[0]);
				$('#type').html(data_array[1]);
				$('#model').html(data_array[2]);
				$('#lstatus').html(data_array[3]);
				$('#bstatus').html(data_array[4]);
				$('#resource_str').attr('title',data_array[5]);
				$('#hgroup').val(hostgroup_value);

				if ( $('#hgroup').val() == null) {
					$('#hgroup').val( $('#hgroup option:first').val() );
				}

				$('#type').val(type_value);

				if ( $('#type').val() == null) {
					$('#type').val( $('#type option:first').val() );
				}

				$('#model').val(model_value);

				if ( $('#model').val() == null) {
					$('#model').val( $('#model option:first').val() );
				}
			}, 'html');
		});
	}

	function setResourceStr() {
		if ($('#clusterid').val() == 0) {
			$('#resource_str').val('');
			$('#td_resource_str').text('');
			$('#td_resource_str').hide();
			$('#resource_str').hide();
		} else {
			$('#td_resource_str').text('<?php print __('ResReq', 'grid');?>');
			$('#td_resource_str').show();
			$('#resource_str').show();
		}
	}

	function onClusterChange() {
		strURL = urlPath + 'plugins/grid/grid_summary.php?action=ajaxonClusterChange' + '&clusterid=' + $('#clusterid').val();
		getGroupTypeMode(strURL);

		setResourceStr();
	}

	function clearFilter() {
		strURL = urlPath + 'plugins/grid/grid_summary.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	function muteFilter() {
		Pace.track(function() {
			$.get(urlPath + 'plugins/grid/grid_summary.php?action=audiocontrol&muteme=true&clusterid='+$('#clusterid').val(), function(data) {
				$('#mute').css('display','none');
				$('#unmute').css('display','');
				$('#ack').val('-1');
				summaryFilterChange(document.form_grid);
				applyFilter();
			});
		});
	}

	function unmuteFilter() {
		Pace.track(function() {
			$.get(urlPath + 'plugins/grid/grid_summary.php?action=audiocontrol&muteme=false&clusterid='+$('#clusterid').val(), function(data) {
				$('#mute').css('display','');
				$('#unmute').css('display','none');
				summaryFilterChange(document.form_grid);
				applyFilter();
			});
		});
	}

	function soundAction(soundId, soundAction) {
		myobj=$('#'+soundId);
		switch (soundAction.toLowerCase()) {
		case 'play':
			if (typeof myobj.controls != undefined && $.isFunction(myobj.controls.play)) {
				myobj.controls.play();
			} else if ($.isFunction(myobj.play)) {
				myobj.play();
			} else {
				myobj.attr('autostart','true');
				myobj.attr('loop', 'true');
				myobj.attr('volume', '100');
				myobj.attr('repeat', 'true');
			}
			break;
		case 'pause':
			if (typeof myobj.controls != 'undefined' && $.isFunction(myobj.controls.pause)) {
				myobj.controls.pause();
			} else if ($.isFunction(myobj.pause)) {
				myobj.pause();
			}
			break;
		case 'stop':
			if (typeof myobj.controls != undefined && $.isFunction(myobj.controls.stop)) {
				myobj.stop();
			} else if ($.isFunction(myobj.stop)) {
				myobj.stop();
			} else {
				myobj.remove();
			}
			break;
		}
	}

	$(function() {
		applyFilter('clear_mode');

		$('#hddialog').dialog({
			autoOpen: false,
			autoResize: true,
			resizable: false,
			draggable: true,
			width: 550,
			maxWidth: 500,
			open: function() {
				$('.ui-dialog-titlebar').hide();
			}
		});

		$('#ddialog').dialog({
			autoOpen: false,
			height: 140,
			width: 550,
			draggable: true,
			resizable: false,
			modal: false,
			open: function() {
				$('.ui-dialog-titlebar').hide();
			}
		});

		$('#exfilter, #shostname').click(function() {
			applyFilter();
		});

		$('#clusterid').change(function() {
			applyFilter();
			onClusterChange();
		});

		$('#hgroup, #cluster_tz, #cacti, #size, #order, #tholds, #type, #model, #lstatus, #bstatus, #refresh, #filter, #resource_str').change(function() {
			applyFilter();
		});

		$('#go').click(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});

		$('#save').click(function() {
			saveFilter();
		});

		initLegend();

		applySkinRTM();

		setResourceStr();

		if ($('#size').val() == 'exsmall' || $('#size').val() == '') {
			$('.shost').hide();
		} else {
			$('.shost').show();
		}
	});

	function openHostDialog(event) {
		id = event.target.id;
		parts = id.split(':');

		if (parts.length == 3) {
			hostname = parts[1];
			clusterid = parts[2];
			script = urlPath + 'plugins/grid/grid_summary.php?action=ajax&host=' + hostname + '&clusterid=' + clusterid;
		} else {
			color = parts[1];
			script = urlPath + 'plugins/grid/grid_summary.php?action=ajaxlegend&color=' + color;

		}

		Pace.track(function() {
			$.get(script, function(data) {
				$('#hddialog').html(data);
				$('#summary_tabs').tabs({
					event: 'mouseover'
				});

				$('#hddialog').dialog('option', 'position', {
					my: 'left',
					at: 'right',
					of: event,
					offset: '15 15'
				});

				$('#hddialog').dialog('open');
			});
		});

		return false;
	}

	function closeHostDialog() {
		$('#hddialog').dialog('close');
	}

	function initializeHostIcons() {
		$('[id^="host:"]').mouseover(function(event) {
			if (typeof timer1 != 'undefined') {
				clearTimeout(timer1);
			}
			closeHostDialog();
			timer2=setTimeout(function () { openHostDialog(event) }, 500);
		}).mouseout(function() {
			if (typeof timer2 != 'undefined') {
				clearTimeout(timer2);
			}
			timer1=setTimeout(function() { closeHostDialog() }, 500);
		}).click(function(event) {
			if (typeof timer2 != 'undefined') {
				clearTimeout(timer2);
			}
			timer1=setTimeout(function() { closeHostDialog() }, 500);
			id = event.target.id;
			parts = id.split(":");
			if (parts[2]) {
				hostname = parts[1];
				clusterid = parts[2];
				document.location = 'grid_bjobs.php?reset=1&action=viewlist&clusterid='+clusterid+'&ajax_host_query='+hostname+'&exec_host='+hostname+'&status=RUNNING';
			}
		});
	}

	</script>
	<div id='ddialog' style='display:none;' title='<?php __esc('Host Alert Action Dialog', 'grid');?>'>
		<p id='dmessage' style='padding:10px;font-size:10pt;'></p>
		<form action=''>
		<table align='right'>
			<tr>
				<td>
					<input style='font-size:10pt;' type='button' name='yes' value='<?php print __esc('Yes', 'grid');?>' onClick='ackHostAlertsExecute()'>
					<input style='font-size:10pt;' type='button' name='no' value='<?php print __esc('No', 'grid');?>' onClick='cancelHostAlertAck()'>
				</td>
			</tr>
		</table>
		</form>
	</div>
	<?php

	bottom_footer();
}

function process_request_vars() {
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
			'default' => 'type',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_views_setting('grid_summary', 'refresh', read_grid_config_option('refresh_interval'))
			),
		'hgroup' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'hgroup', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'cacti' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_views_setting('grid_summary', 'cacti', '-1'),
			),
		'order' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'default' => get_views_setting('grid_summary', 'order', 'status'),
			'pageset' => true
			),
		'tholds' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_views_setting('grid_summary', 'tholds', '-1'),
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'type', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'model', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'size' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'size', 'medium'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'exfilter' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'exfilter', read_grid_config_option('grid_enable_exclusion_filter')),
			'options' => array('options' => 'sanitize_search_string')
			),
		'shostname' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'shostname', 'true'),
			'options' => array('options' => 'sanitize_search_string')
			),
		'lstatus' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'lstatus', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'bstatus' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_views_setting('grid_summary', 'bstatus', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'ack' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '2',
			),
		'resource_str' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'resource_sanitize_search_string'),
			'pageset' => true
			),
		'hostname' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'no',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'alarm' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '3',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'mute' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'unmute' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'host' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			)
	);

	validate_store_request_vars($filters, 'sess_grid_view_summary');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_views_setting('grid_summary', 'clusterid', read_grid_config_option('default_grid'))
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ==================================================== */
}

function show_summary() {
	global $title, $grid_search_types, $grid_refresh_interval;
	global $minimum_user_refresh_intervals, $grid_summary_filters, $config;
	$sql_params = array();

	$hosts = grid_view_get_summary_records();

	$sql_where = '';
	if (get_request_var('clusterid') > 0) {
		$sql_where = 'WHERE clusterid=?';
		$sql_params[] = get_filter_request_var('clusterid');
	}
	$hosts_with_alarms = array_rekey(db_fetch_assoc_prepared("SELECT
		DISTINCT CONCAT_WS('', clusterid, '|', hostname, '') AS clhost
		FROM grid_hosts_alarm " . $sql_where, $sql_params), 'clhost', 'clhost');

	$authfull = api_plugin_user_realm_auth('LSF_Host_Alert_Stop');

	if (cacti_sizeof($hosts_with_alarms) && get_request_var('ack') != 2) {
		foreach ($hosts_with_alarms as $host) {
			$parts     = explode('|', $host);
			$clusterid = $parts[0];
			$host      = $parts[1];

			// Stop Alerting for all users
			if (get_request_var('ack') == -2 && $authfull) {
				db_execute_prepared('UPDATE grid_hosts_alarm
					SET acknowledgement="on"
					WHERE hostname = ?
					AND clusterid = ?',
					array($host, $clusterid));
			} else if (get_request_var('ack') == -1) {
				// Stop Alerting only for me
				$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $clusterid] = date('Y-m-d H:i:s');
			} else if (get_request_var('ack') == 0) {
				// Rusume Alerting for me
				$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $clusterid] = '0000-00-00';
			} else if (get_request_var('ack') == 1 && $authfull) {
				// Resume Alerting for all users (that includes me)
				db_execute_prepared('UPDATE grid_hosts_alarm
					SET acknowledgement="off"
					WHERE hostname = ?
					AND clusterid = ?',
					array($host, $clusterid));

				/* restore alarming for me too */
				$_SESSION['sess_grid_summary_hostalarm_muteme_time_' . $clusterid] = false;
				if (isset($_SESSION['sess_grid_view_summary_host_alarms_' . $clusterid])) {
					kill_session_var('sess_grid_view_summary_host_alarms_' . $clusterid);
				}
			}
		}
	}

	/* get check audible */
	$host_down  = grid_summary_check_audible();
	$host_alarm = grid_hostalarm_check_audible();

	/* display the filtered set of hosts */
	display_hosts($hosts,$host_down,$host_alarm);

	if ($host_down && $host_alarm) {
		$message = __esc('%s New Hosts Down, %s New Host Alerts', $host_down, $host_alarm, 'grid');
	} elseif ($host_down) {
		$message = __esc('%s New Hosts Down', $host_down, 'grid');
	} elseif ($host_alarm) {
		$message = __esc('%s New Hosts Alerts', $host_alarm, 'grid');
	} else {
		$message = '';
	}

	$total = $host_down + $host_alarm;

	if ($message != '') {
		$message = $_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')] = $message . ', Logged at: ' . date('m-d H:i');;
	} elseif (isset($_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')])) {
		$message = $_SESSION['sess_grid_view_summary_message' . get_request_var('clusterid')];
	}

	if (get_request_var('muteme')) {
		print "<span style='display:none;'><input id='alert_mute' value='true'><input id='reason' value='$message'></span>\n";
	} else {
		print "<span style='display:none;'><input id='alert_mute' value='false'><input id='reason' value='$message'></span>\n";
	}
}

function format_host_image(&$row, $acknowledged_alarms, $newalarmlocal_image) {
	global $config;

	switch ($row['summary_status']) {
		case GRID_UNAVAIL:
			$class = 'hostDbDown';

			break;
		case GRID_LOWRES:
			$class = 'hostDbLowResources';

			break;
		case GRID_IDLECLOSE;
			$class = 'hostDbIdleClosed';

			break;
		case GRID_ADMINDOWN:
			$class = 'hostDbAdminDown';

			break;
		case GRID_BUSYCLOSE:
			$class = 'hostDbBusyClosed';

			break;
		case GRID_BUSY:
			$class = 'hostDbBusy';

			break;
		case GRID_IDLEWJOBS:
			$class = 'hostDbIdleWithJobs';

			break;
		case GRID_STARVED:
			$class = 'hostDbStarved';

			break;
		case GRID_BLACKHOLE:
			$class = 'hostDbBlackHole';

			break;
		default:
			$class = 'hostDbIdle';
	}

	$count = db_fetch_cell_prepared('SELECT count(*)
		FROM grid_hosts_alarm
		WHERE hostname = ?
		AND clusterid = ?',
		array($row['host'], $row['clusterid']));

	switch(get_request_var('size')) {
	case 'exsmall':
		$sclass = 'hostExSmall';
		$iclass = 'ExtraSmall';
		break;
	case 'small':
		$sclass = 'hostSmall';
		$iclass = 'Small';
		break;
	case 'medium':
		$sclass = 'hostMedium';
		$iclass = 'Medium';
		break;
	case 'large':
		$sclass = 'hostLarge';
		$iclass = 'Large';
		break;
	case 'xlarge':
		$sclass = 'hostExtraLarge';
		$iclass = 'ExtraLarge';
		break;
	default:
		$sclass = 'hostMedium';
		$iclass = 'Medium';
		break;
	}

	if ($count > 0 && read_grid_config_option('default_blink_alarm_hosts') == 'on') {
		if (($acknowledged_alarms > 0 && $newalarmlocal_image <= 0)) {
			$icon = '<div class="ackIcon ack' . $iclass . ' fas fa-circle"></div>';
		} else {
			$icon = '<div class="ackIcon unack' . $iclass . ' fas fa-play"></div>';
		}
	} else {
		$icon = '';
	}

	if ($sclass != 'hostExSmall') {
		if (get_request_var('shostname') == 'false') {
			return "<div class='anchorChild'><div id='host:" . $row['host'] . ":" . $row['clusterid'] . "' class='fa fa-server $class $sclass $iclass'>$icon</div></div>";
		} else {
			return "<div class='anchorChild'><div id='host:" . $row['host'] . ":" . $row['clusterid'] . "' class='fa fa-server $class $sclass $iclass'>$icon</div><br><div class='legendTitle' style='word-break:break-all;'>" . $row['host'] . '</div></div>';
		}
	} else {
		return "<div class='anchorChild'><div id='host:" . $row['host'] . ":" . $row['clusterid'] . "' class='fa fa-server $class $sclass $iclass'>$icon</div></div>";
	}
}

function display_alarmtype_name($type) {
	if ($type == 0) {
		return __('Threshold', 'grid');
	} else if ($type == 1) {
		return __('Syslog', 'grid');
	} else if ($type == 2) {
		return __('Alert', 'grid');
	} else if ($type == 3) {
		return __('Batch', 'grid');
	}
}

function grid_hostalarm_check_audible() {
	$sql_params = array();

	$cluster = get_filter_request_var('clusterid');

	/* now, let's check for a change in host status */
	 if (get_request_var('clusterid') == 0) {
		$sql_where = "WHERE ((monitor='on' AND disabled='') OR (monitor IS NULL or monitor =''))
			AND ga.hostname!='lost_and_found'
			AND ga.present=1";
	} else {
		$sql_where = "WHERE ((monitor='on' AND disabled='') OR (monitor IS NULL or monitor=''))
			AND ga.clusterid = ?
			AND ga.hostname!='lost_and_found'
			AND ga.present=1";
		$sql_params[] = $cluster;
	}

	$host_alarms = db_fetch_assoc_prepared("SELECT gs.host AS host,
		ga.type AS type,
		ga.message AS message
		FROM grid_summary AS gs
		INNER JOIN grid_hosts_alarm AS ga
		ON ga.hostname=gs.host
		AND ga.clusterid=gs.clusterid
		$sql_where", $sql_params);

	$hosts_with_alarms = db_fetch_assoc_prepared("SELECT DISTINCT gs.host AS host
		FROM grid_summary AS gs
		INNER JOIN grid_hosts_alarm AS ga
		ON ga.hostname=gs.host
		AND ga.clusterid=gs.clusterid
		$sql_where", $sql_params);

	$host_alarm = array();

	// if there are host alarms, let's check for new ones
	if (cacti_sizeof($host_alarms)) {
		$new_array = array();
		/* check the session variable to see if we had any down hosts before */
		foreach($host_alarms as $alarm) {
			$hash = base64_encode($alarm['host'] . $alarm['type'] . $alarm['message']);

			if (!isset($_SESSION['sess_grid_view_summary_host_alarms_' . $cluster][$hash])) {
				$host_alarm[$alarm['host']] = $alarm['host'];
			}

			$new_array[$hash] = 1;
		}

		$_SESSION['sess_grid_view_summary_host_alarms_' . $cluster] = $new_array;
	}

	return cacti_sizeof($host_alarm);
}
