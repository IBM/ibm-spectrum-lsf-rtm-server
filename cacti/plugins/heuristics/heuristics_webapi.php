<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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

function check_user_status () {
	global $grid_job_control_actions;

	$if_cluster_crontol_user = false;
	$if_login_filtered_user  = false;
	$if_lsf_admin_user       = false;
	$if_show_actions         = false;

	$login_user = get_username($_SESSION['sess_user_id']);

	$clusterid  = get_filter_request_var('clusterid');

	if (api_plugin_user_realm_auth('LSF_Cluster_Control') ) {
		$if_cluster_crontol_user = true;
	}

	if ($login_user == get_nfilter_request_var('job_user')) {
		$if_login_filtered_user = true;
	}

	$if_lsf_admin_user = is_lsf_admin($login_user, $clusterid );

	if ($if_login_filtered_user) {
		$if_show_actions = true;

		if (!$if_lsf_admin_user && !$if_cluster_crontol_user) {
			unset($grid_job_control_actions[4]);
		}
	} else {
		if ($if_lsf_admin_user || $if_cluster_crontol_user) {
			$if_show_actions = true;
		}
	}

	return $if_show_actions;
}

function is_lsf_admin($user, $clusterid = '') {
	$sql_where  = '';
	$sql_params = array();
	if ($clusterid > 0) {
		$sql_where = ' WHERE clusterid = ?';
		$sql_params[] = $clusterid;

	}

	$lsfadmininfo = db_fetch_assoc_prepared("SELECT lsf_admins
		FROM grid_clusters $sql_where", $sql_params);

	foreach($lsfadmininfo as $lsfadminrow) {
		$admin_str = trim($lsfadminrow['lsf_admins']);
		$admin_arr = explode(' ', $admin_str);

		foreach($admin_arr as $admin) {
			if (substr_count($admin, "\\")) {
				$admin = explode("\\",$admin);
				$admin_user = $admin[1];
			} else {
				$admin_user = $admin;
			}

			if ($user == $admin_user) {
				return true;
			}
		}
	}

	return false;
}

function one_column_display_type() {
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

	if (get_request_var('tab') == 1) {
		return false;
	}

	if (get_request_var('health') == '' || get_request_var('health') == 'false') {
		if (get_request_var('charts') == '' || get_request_var('charts') == 'false') {
			return true;
		}

		if (get_request_var('corder') == '' && get_request_var('tab') =='0') {
			return true;
		}
	}

	foreach ($browsers as $b) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $b) !== FALSE) {
			return true;
		}
	}

	return false;
}

function heuristics_panel_build_export_header($panel, $header) {
	global $config;

	$i = 0;
	$xport_row = "";

	if (cacti_sizeof($header)) {
	foreach ($header as $item) {
		$xport_row .= ($i > 0 ? ",":"") . '"' . $item['name'] . '"';
		$i++;
	}
	}

	return $xport_row;
}

function heuristics_panel_build_export_row($panel, $row, $others, $heuristics_num_clusters) {
	global $config;
	$xport_row   = '';
	if($panel == 'summary') {
		$pend = number_format_i18n($row['numPEND']). ' / ' . number_format_i18n($others['numPEND']);
		$run  = number_format_i18n($row['numRUN']). ' / ' . number_format_i18n($others['numRUN']);
		$susp = number_format_i18n($row['numSUSP']). ' / ' . number_format_i18n($others['numSUSP']);
		$done = number_format_i18n($row['numDONE']). ' / ' . number_format_i18n($others['numDONE']);
		$exit = number_format_i18n($row['numEXIT']). ' / ' . number_format_i18n($others['numEXIT']);
		if ($heuristics_num_clusters > 1) {
			$xport_row .= '"' . $row['clustername'] . '","';
			$xport_row .= $pend . '","';
		} else {
			$xport_row .= '"' . $pend . '","';
		}
		$xport_row .= $run . '","';
		$xport_row .= $susp . '","';
		$xport_row .= $done . '","';
		$xport_row .= $exit . '","';
		$tputHOUR = number_format_i18n($row['tputHOUR']) . ' / ' . number_format_i18n($others['tputHOUR']);
		$tput5MIN = number_format_i18n($row['tput5MIN']) . ' / ' . number_format_i18n($others['tput5MIN']);
		$xport_row .= $tputHOUR . '","';
		$xport_row .= $tput5MIN . '"';
	} else if($panel == 'checkouts') {
		$xport_row   .= '"' . $row['feature_name'] . '","';
		$xport_row .= $row['server_name'] . '","';
		$xport_row .= $row['server_vendor'] . '","';
		$xport_row .= $row['server_licensetype'] . '","';
		$xport_row .= number_format_i18n($row['max_tokens']) . '","';
		$xport_row .= number_format_i18n($row['free_tokens']) . '","';
		$xport_row .= number_format_i18n($row['tokens']) . '","';
		$xport_row .= display_job_time($row['total_time']) . '","';
		$xport_row .= display_job_time($row['max_time']) . '","';
		$xport_row .= display_job_time($row['avg_time']) . '"';
	} else if($panel == 'pendr') {
		if ($heuristics_num_clusters > 1) {
			$xport_row .= '"' . $row['clustername'] . '","';
			$xport_row .= $row['queue'] . '","';
		} else {
			$xport_row .= '"' . $row['queue'] . '","';
		}
		$xport_row .= format_reason_heur($row) . '","';

		$issusp = $row['issusp'] == 0 ? __('Pending', 'heuristics'):__('Suspended', 'heuristics');
		if (heuristics_is_hidden_reason($row['reason_code'], $row['sub_reason_code'])) {
			$visibility =  __('Hidden', 'heuristics');
		} else {
			$visibility = __('Visible', 'heuristics');
		}
		$jobs = number_format_i18n($row['jobs']);
		$total_pend = display_job_time($row['total_pend']);
		$max_pend = display_job_time($row['max_pend']);
		$avg_pend = display_job_time($row['avg_pend']);

		$xport_row .= $issusp . '","';
		$xport_row .= $visibility . '","';
		$xport_row .= $jobs . '","';
		$xport_row .= $total_pend . '","';
		$xport_row .= $max_pend . '","';
		$xport_row .= $avg_pend . '"';
	} else if($panel == 'exitanal') {
		if ($heuristics_num_clusters > 1) {
			$xport_row .= '"' . $row['clustername'] . '","';
			$xport_row .= $row['queue'] . '","';
		} else {
			$xport_row .= '"' . $row['queue'] . '","';
		}
		$xport_row .= $row['projectName'] . '","';
		$xport_row .= number_format_i18n($row['jobs']) . '","';
		$reason = heuristics_get_exit_code($row['exitStatus']) . getExceptionStatus($row['exceptMask'], $row['exitInfo']);
		$xport_row .= $reason . '"';
	} else if($panel == 'tput') {
		$na = __('N/A', 'heuristics');
		$cq = explode('|', $others);
		if ($heuristics_num_clusters > 1) {
			$xport_row   .= '"' . $cq[0] . '","';
			$xport_row   .= $cq[1] . '","';
		} else {
			$xport_row   .= '"' . $cq[1] . '","';
		}

		$tENDED = isset($row['tENDED']) ? number_format_i18n($row['tENDED']):$na;
		$tEXITED = isset($row['tEXITED']) ? number_format_i18n($row['tEXITED']):$na;
		$ttt= isset($row['ttt']) ? display_job_time($row['ttt'], 0, FALSE):$na;
		$yENDED = isset($row['yENDED']) ? number_format_i18n($row['yENDED']):$na;
		$yEXITED = isset($row['yEXITED']) ? number_format_i18n($row["yEXITED"]):$na;

		$xport_row .= $tENDED . '","';
		$xport_row .= $tEXITED . '","';
		$xport_row .= $ttt . '","';
		$xport_row .= $yENDED . '","';
		$xport_row .= $yEXITED . '"';
	} else if($panel == 'health') {
		$i = 0;
		foreach($row as $filed) {
			if($i == 0) {
				$xport_row   .= '"' . $filed . '",';
			} else {
				$xport_row   .= '"' . $filed . '",';
			}
		}
		if(!empty($xport_row)){
			$xport_row = substr_replace($xport_row, '', -1);//delete last , to fix one extra column added on mac.
		}
	} else if($panel == 'queues') {
		$severity = '';

		$ahead    = get_pend_ahead($row, get_request_var('user_iq'));
		$estimate = get_estimate($row, $ahead, $others);
		get_idled_jobs($row, get_request_var('user_iq'), $severity);
		$idled = empty($severity) ? 'ok' : $severity ;
		get_depend_jobs($row, get_request_var('user_iq'), $severity);
		$depend = empty($severity) ? 'ok' : $severity ;
		get_memvio_jobs($row, get_request_var('user_iq'), $severity);
		$memvio = empty($severity) ? 'ok' : $severity ;
		get_long_jobs($row, get_request_var('user_iq'), $severity);
		$slow = empty($severity) ? 'ok' : $severity ;
		if ($estimate['units'] == 'numeric') {
			$testimate = __('%s Hrs', $estimate['estimate'], 'heuristics');
		} else {
			$testimate = $estimate['estimate'];
		}
		$cq = $row['clustername'] . '|' . $row['queue'] . '|' . $row['projectName'];
		if ($heuristics_num_clusters > 1) {
			$xport_row .= '"' . $row['clustername'] . '","';
			$xport_row .= $row['queue'] . '","';
		} else {
			$xport_row .= '"' . $row['queue'] . '","';
		}
		$xport_row .= $row['projectName'] . '","';
		$xport_row .= $row['reqCpus'] . '","';
		$pend   = number_format_i18n($row['numPEND']) . ' / ' . ($ahead != 'NA' && $ahead != 'FCFS' ? number_format_i18n($ahead):$ahead);
		$run    = number_format_i18n($row['numRUN']) . ' / ' . (isset($others[$cq]['numRUN']) ? number_format_i18n($others[$cq]['numRUN']):'-');
		$susp   = number_format_i18n($row['numSUSP']) . ' / ' . (isset($others[$cq]['numSUSP']) ? number_format_i18n($others[$cq]['numSUSP']):'-');
		$tputHr = number_format_i18n($row['tputHOUR']) . ' / ' . (isset($others[$cq]['tputHOUR']) ? number_format_i18n($others[$cq]['tputHOUR']):'-');
		$tput5m = number_format_i18n($row['tput5MIN']) . ' / ' . (isset($others[$cq]['tput5MIN']) ? number_format_i18n($others[$cq]['tput5MIN']):'-');
		$xport_row .= $pend . '","';
		$xport_row .= $run . '","';
		$xport_row .= $susp . '","';
		$xport_row .= $tputHr . '","';
		$xport_row .= $tput5m . '","';

		$xport_row .= $testimate . '","';
		$xport_row .= $estimate['method'] . '","';
		$xport_row .= $idled . '","';
		$xport_row .= $slow . '","';
		$xport_row .= $depend . '","';
		$xport_row .= $memvio . '"';
	}
	return $xport_row;
}

function heuristics_export_panel() {
	global $views;

	heuristics_process_input_variables();

	$panel = get_request_var('panel');

	//header('Content-Type: application/json');

	$header = array();
	$data = '';
	$other_data = '';
	if (isset($views[$panel]['function'])) {
		//print call_user_func($views[$panel]['function'], true, $data, $other_data);
		print call_user_func_array($views[$panel]['function'], array(true, &$header, &$data, &$other_data));
	}
	$xport_array = array();
	/* build header */
	array_push($xport_array, heuristics_panel_build_export_header($panel, $header));

	if (!empty($data)) {
		if($panel == 'health') {
			foreach($data as $row) {
				array_push($xport_array, heuristics_panel_build_export_row($panel, $row, $other_data, NULL));
			}
		} else if($panel == 'tput') {
			$heuristics_num_clusters = heuristics_num_clusters($data);
			foreach($data as $cluster_queue => $row) {
				array_push($xport_array, heuristics_panel_build_export_row($panel, $row, $cluster_queue, $heuristics_num_clusters));
			}
		} else if($panel == 'summary') {
			$heuristics_num_clusters = heuristics_num_clusters($data);
			foreach($data as $clusterid => $row) {
				$row_other_data = $other_data[$clusterid];
				array_push($xport_array, heuristics_panel_build_export_row($panel, $row, $row_other_data, $heuristics_num_clusters));
			}
		} else {
			$heuristics_num_clusters = heuristics_num_clusters($data);
			foreach($data as $row) {
				array_push($xport_array, heuristics_panel_build_export_row($panel, $row, $other_data, $heuristics_num_clusters));
			}
		}
	}

	header('Content-type: application/csv');
	header('Cache-Control: max-age=15');
	header("Content-Disposition: attachment; filename=heuristics_panel_$panel.csv");
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}

function heuristics_show_panel() {
	global $views;

	heuristics_process_input_variables();

	$panel = get_request_var('panel');

	header('Content-Type: application/json');

	if (isset($views[$panel]['function'])) {
	    $header = array();
	    $data = '';
	    $other_data = '';
	    print call_user_func_array($views[$panel]['function'], array(false, &$header, &$data, &$other_data));
	}
}

function heuristics_dashboard() {
	global $config, $log_tail_lines, $refresh, $page_title;

	$graphs = heuristics_process_input_variables();

	get_user_title();

	$page_title = __('RTM Grid User Heuristics', 'heuristics');

	general_header();

	heuristics_dashboard_ajax();

	bottom_footer();
}

function heuristics_dashboard_ajax() {
	global $config, $log_tail_lines, $refresh, $page_title, $views, $charts, $charts_fusion;

	$graphs = heuristics_process_input_variables();

	get_user_title();

	?>
	<script type='text/javascript'>
	var fw = 0;
	var cur_graphs = new Array(<?php print $graphs;?>);
	var cur_charts = new Array("<?php print implode('","', explode(' ', get_request_var('corder')));?>");
	var cur_views  = new Array("<?php print implode('","', explode(' ', get_request_var('order')));?>");
	var cur_tab    = '<?php print get_request_var('tab');?>';

	var all_views  = new Array("<?php print implode('","', array_keys($views));?>");
	var all_charts = new Array("<?php print implode('","', array_keys($charts));?>");
	var all_charts_fusion = new Array("<?php print implode('","', $charts_fusion);?>");

	var excluded   = '<?php print get_request_var('excluded');?>';
	var panelMove  = false;
	var deferreds  = [];
	var refreshTimer;
	var timerTimer;

	<?php api_plugin_hook('heuristics_graph_vars'); ?>

	$(function() {
		$.ajaxSetup({cache: false});
		$('.panels, .graphs').sortable({
			cancel: '.panels div div table',
			axis: 'y',
			scroll: true,
			start: function(event, ui) { panelMove = true; },
			deactivate: function(event, ui) {
				$.get(buildRequest('ajaxupdate'), function() {
					Pace.stop();
				});
				panelMove = false;
			}
		});

		// Set the height of the multiselect
		var charts_height = $('#charts').children('option').length * 31;
		var views_height = $('#views').children('option').length * 31;

		$('#views').multiselect({ height:views_height, minWidth: 180, header: '<?php print __('Choose your Views', 'heuristics');?>' });
		$('#charts').multiselect({ height: charts_height, minWidth: 180, header: '<?php print __('Choose your Charts', 'heuristics');?>' });
		$('#user_iq').autocomplete({
			autoFocus: true,
			minLength:0,
			source: 'heuristics.php?action=ajaxsearch&type=user_iq',
			select: function(event, ui) {
				if (ui.item.value == '') {
					$('#message').text('').show().html('<strong>Please Enter a Valid User Account</strong>', 'heuristics').delay(2000).fadeOut(1000);
					return false;
				}
				$('#user_iq').val(ui.item.value);
				let curr_user_link = "<?php print $config['url_path'];?>plugins/heuristics/heuristics_jobs.php?reset=true&job_user=" + ui.item.value;
				$('#curr_user_iq').attr('href', curr_user_link);

				filterChange();
			}
		});

		if (navigator.userAgent.match(/msie/i)) {
			$('input:radio, input:checkbox').click(function() {
				this.blur();
				this.focus();
			});
		}

		$('#ctabs').tabs({ beforeActivate:setTab });

		//select general tab in charts area
		$('#general').addClass('selected');
		$('#queueg').removeClass('selected');
		$('#queueg').hide();

		$('#mtgr').hide();

		strURL = buildRequest('ajaview');

		<?php api_plugin_hook('heuristics_page_init');?>

		applySkin();

		$('#ctabs').tabs('option','active',<?php print get_nfilter_request_var('tab');?>);  //jquery 1.10

		$('.link_on_start_box').unbind('click').click(function(event) {
			event.stopPropagation();
		});

		filterChange();

		$('#form_cluster').submit(function(event) {
			event.preventDefault();

			if ($('#user_iq').val() == '') {
				$('#message').text('').show().html('<?php print __('Please Enter a Valid User Account', 'heuristics');?>').delay(2000).fadeOut(1000);
				return false;
			}

			filterChange();
		});

		$(document).resize(function() {
			$('#panelleft').height($(document).height());
		});

		$(window).resize(function() {
			graphSizer();
		});
	});

	function graphSizer() {
		$('.fusioncharts-container').each(function() {
			var width = parseInt($('#right').width());
			var attr = $(this).attr('id');
			FusionCharts(attr).resizeTo(width, 300);
		});
	}

	function setTab(event,ui) {
		$.get('heuristics.php?action=ajaxtab&tab='+ui.newTab.index(), function(data) {
			if (ui.newTab.index() == 0) {
				initializeGeneralCharts();
				$('#general').addClass('selected');
				$('#queueg').removeClass('selected');
			} else {
				initializeProjectQueueCharts();
				$('#queueg').addClass('selected');
				$('#general').removeClass('selected');
			}
		});
	}

	function changeReasonVisibility(subaction, reason, subreason) {
		$.get('heuristics.php?action=ajax_visibility&subaction='+subaction+'&reason='+reason+'&subreason='+subreason, function() {
			initializePanel('pendr', '', true);
		});
	}

	function exportPanelData(panel) {
		strURL  = urlPath + 'plugins/heuristics/heuristics.php?action=export&header=false&panel='+panel;
		document.location = strURL;
		Pace.stop();
	}
	function forcePanelRefresh(panel) {
		if(panel == 'health') {
			initializeHealthView('&force=true', true);
		} else {
			initializePanel(panel, '&force=true', true);
		}
	}

	function changeRefresh() {
		clearTimeout(refreshTimer);
		refreshTimer = setTimeout(filterChange, $('#refresh').val() * 1000);
	}

	function changeReasonExclusion() {
		if (excluded == 'false') {
			excluded = 'true';
		} else {
			excluded = 'false';
		}

		strURL = buildFilterRequest('ajaxupdate');

		$.get(strURL, function(data) {
			initializePanel('pendr', '&force=true', true);
		});
	}

	function filterChange(refresh) {
		if (typeof refresh == 'undefined') refresh = true;

		$('#views').multiselect('close');
		$('#charts').multiselect('close');

		clearTimeout(refreshTimer);
		let page  = basename(location.pathname);
		if (page != 'heuristics.php') {
			return;
		}

		strURL = buildRequest((refresh ? 'ajaxdb':'ajaxupdate'));

		if (strURL.indexOf('clusterid=undefined') > 0){
			return;
		}

		$('#views').multiselect("close");
		$('#charts').multiselect("close");

		if (refresh) {
			initializeViewsAndCharts();
		} else {
			$.ajaxQ.abortAll();
			$.get(strURL, function() {
				Pace.stop();
			});
		}

		refreshTimer = setTimeout(filterChange, $('#refresh').val() * 1000);
	}

	function showMemoryUseJobs(id) {
		loadRTMPage('<?php print $config['url_path'];?>plugins/heuristics/heuristics_jobs.php?reset=true&status=DONE&timespan='+$('#timespan').val()+'&memsize='+id+'&job_user='+$('#user_iq').val()+($('#queue').val() != '-1' ? '&queue='+$('#queue').val():'')+($('#clusterid').val() != '-1' ? '&clusterid='+$('#clusterid').val():''));
	}

	function showRunTimeJobs(id) {
		loadRTMPage('<?php print $config['url_path'];?>plugins/heuristics/heuristics_jobs.php?reset=true&status=DONE&timespan='+$('#timespan').val()+'&runtime='+id+'&job_user='+$('#user_iq').val()+($('#queue').val() != '-1' ? '&queue='+$('#queue').val():'')+($('#clusterid').val() != '-1' ? '&clusterid='+$('#clusterid').val():''));
	}

	function showJobsByStat(id) {
		//loadRTMPage('<?php print $config['url_path'];?>plugins/heuristics/heuristics_jobs.php?reset=true&status='+id+'&timespan='+$('#timespan').val()+'&job_user='+$('#user_iq').val()+($('#queue').val() != '-1' ? '&queue='+$('#queue').val():'')+($('#clusterid').val() != '-1' ? '&clusterid='+$('#clusterid').val():''));
	}

	function buildFilterRequest(action) {
		strURL  = urlPath + 'plugins/heuristics/heuristics.php?header=false&action='+action;
		strURL += '&user_iq='        + $('#user_iq').val();
		strURL += '&clusterid='   + $('#clusterid').val();
		strURL += '&refresh='     + $('#refresh').val();
		strURL += '&queue='       + $('#queue').val();
		strURL += '&severity='    + $('#severity').val();
		strURL += '&limit='       + $('#limit').val();
		strURL += '&timespan='    + $('#timespan').val();
		strURL += '&charts=true';
		strURL += '&excluded=' + excluded;

		return strURL;
	}

	function buildRequest(action) {
		var orderarray = new Array();
		var order = '';
		var corderarray = new Array();
		var corder = '';

		$('.panels').find('.panel').each(function() {
			id = $(this).attr('id').replace('panel_', '');
			if ($('#views option[value='+id+']').is(':selected')) {
				if (id != 'health') {
					order = order + (order != '' ? ' ':'') + id;
					orderarray.push(id);
				}
			}
		});

		$('.cpanel').each(function() {
			id = $(this).attr('id').replace('cpanel_', '');
			if ($('#charts option[value='+id+']').is(':selected')) {
				corder = corder + (corder != '' ? ' ':'') + id;
				corderarray.push(id);
			}
		});

		$.grep(all_views, function (id) {
			if ($.inArray(id, orderarray) < 0) {
				if ($('#views option[value='+id+']').is(':selected')) {
					if (id != 'health') {
						order = order + (order != '' ? ' ':'') + id;
					}
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

		strURL = strURL + '&order=' + order;
		strURL = strURL + '&corder=' + corder;

		for (var i = 0; i < all_views.length; i++) {
			strURL = strURL + '&'+all_views[i]+'=' + $('#views option[value='+all_views[i]+']').is(':selected');
		}

		for (var i = 0; i < all_charts.length; i++) {
			strURL = strURL + '&'+all_charts[i]+'=' + $('#charts option[value='+all_charts[i]+']').is(':selected');
		}

		return strURL;
	}

	function filterSave() {
		strURL = buildRequest('ajaxsave');

		$.get(strURL, function() {
			$('#message').text('').show().html('<?php print __('Filter Settings Saved', 'heuristics');?>').delay(2000).fadeOut(1000);
			Pace.stop();
		});
	}

	function filterClear() {
		strURL = urlPath + 'plugins/heuristics/heuristics.php?action=ajaxdb&clear=1';

		$('#views').multiselect("close");
		$('#charts').multiselect("close");

		$.get(strURL, function(data) {
			$('#main').html(data);
			applySkin();
			Pace.stop();
		});
	}

	function severityChange() {
		if ($('#views option[value="queues"]').is(':selected')) {
			initializePanel('queues', '', true);
		}
	}

	function viewChange() {
		var num_views       = cur_views.length;
		var health_selected = false
		var old_views       = cur_views;

		cur_views = new Array();
		order     = '';

		for (var i = 0; i < all_views.length; i++) {
			if ($('#views option[value='+all_views[i]+']').is(':selected')) {
				if (all_views[i] != 'health') {
					cur_views.push(all_views[i]);
					order += (order != '' ? ' ':'') + all_views[i];
				} else {
					health_selected = true;
				}
			}
		}

		// Update the backend session variables
		strURL = buildRequest('ajaxupdate');

		// find out what was in old that is not in new
		diff = $(old_views).not(cur_views).get();

		for (var i = 0; i < diff.length; i++) {
			$('#panel_'+diff[i]).empty();
		}

		// Now find the views that are newly selected
		diff = $(cur_views).not(old_views).get();

		for (var i = 0; i < diff.length; i++) {
			initializePanel(diff[i], '', false);
		}

		if (health_selected && $('#remove_health').length == 0) {
			initializeHealthView('', false);
		} else if (!health_selected) {
			$('#rpanel_health').empty();
		}
	}

	function chartChange() {
		var old_charts = cur_charts;

		cur_charts = new Array();
		corder     = '';

		for (var i = 0; i < all_charts.length; i++) {
			if ($('#charts option[value='+all_charts[i]+']').is(':selected')) {
				cur_charts.push(all_charts[i]);
				corder += (corder != '' ? ' ':'') + all_charts[i];
			}
		}

		// Update the backend session variables
		strURL = buildRequest('ajaxupdate');

		$.get(strURL, function() {
			Pace.stop();
		});

		// find out what was in old that is not in new
		diff = $(old_charts).not(cur_charts).get();

		for (var i = 0; i < diff.length; i++) {
			if (FusionCharts(diff[i]+'_fusion')) FusionCharts(diff[i]+'_fusion').dispose();
			$('#cpanel_'+diff[i]).empty();
		}

		// Now find the views that are newly selected
		diff = $(cur_charts).not(old_charts).get();

		for (var i = 0; i < diff.length; i++) {
			fusionFile = getFusionFile(diff[i]);
			drawChart(diff[i], fusionFile);
		}
	}

	function showTrendChart(queue, project, clusterid, user, metric, graph_id) {
		if (fw == 0) fw = $('#right').innerWidth()-30;

		var fh = 300;

		url=escape('heuristics.php?action=ajaxcharts&queue='+queue+'&project='+project+'&clusterid='+clusterid+'&metric='+metric+'&user_iq='+user+'&timespan='+$('#timespan').val());

		//FusionCharts.debugMode.enabled(true);
		//FusionCharts.debugMode.outputTo(console.log);
		//FusionCharts.setCurrentRenderer('javascript');

		if (FusionCharts('cont'+graph_id)) FusionCharts('cont'+graph_id).dispose();

		if ($('#graphs'+graph_id).length != 0) {
			$('#graphs'+graph_id).empty();
		} else {
			$('#pqgraphs').append("<div id='graphs"+graph_id+"' class='cpanel'></div>");
		}

		var myChart = new FusionCharts('LogMSLine', 'cont'+graph_id, fw, fh);
		myChart.setXMLUrl(url);

		if (('#graphs'+graph_id).length) {
			myChart.render('graphs'+graph_id);
		}
	}

	function drawChart(chart, fusionFile) {
		if (fw == 0) fw = $('#right').innerWidth()-30;

		if (fw > 1000) {
			var fh = 300;
		} else {
			var fh = 300;
		}

		url=escape('heuristics.php?header=false&action=ajax_'+chart+'&queue='+$('#queue').val()+'&user_iq='+$('#user_iq').val()+'&clusterid='+$('#clusterid').val()+'&timespan='+$('#timespan').val());

		//FusionCharts.debugMode.enabled(true);
		//FusionCharts.debugMode.outputTo(console.log);
		//FusionCharts.setCurrentRenderer('javascript');

		if (FusionCharts(chart+'_fusion')) FusionCharts(chart+'_fusion').dispose();

		if ($('#cpanel_'+chart).length > 0) {
			$('#cpanel_'+chart).empty();
		} else {
			$('#graphs').append("<div id='cpanel_"+chart+"' class='cpanel'></div>");
		}

		var myChart = new FusionCharts(fusionFile, chart+'_fusion', fw, fh);
		myChart.setXMLUrl(url);

		if ($('#cpanel_'+chart).length) {
			myChart.render('cpanel_'+chart);
		}

		graphSizer();
	}

	<?php api_plugin_hook('heuristics_javascript');?>

	function getFusionFile(chart) {
		var fusionFile = '';

		for(index=0; index<all_charts.length; index++) {
			if (all_charts[index] == chart) {
				fusionFile = all_charts_fusion[index];
				break;
			}
		}

		return fusionFile;
	}

	function initializeHealthView(extra_args, resolve) {
		if (typeof extra_args == 'undefined') extra_args = '';

		if ($('#views option[value="health"]').is(':selected')) {
			strURL = buildFilterRequest('ajaxview');

			$.getJSON(strURL+'&panel=health'+extra_args, function(data) {
				if (data != '') {
					if (data.output) {
						var output = data.output;
					}

					if (data.panel) {
						var view = data.panel;
					}

					if ($(document).find('#rpanel_'+view).length != 0) {
						$('#rpanel_'+view).empty();
						$('#rpanel_'+view).html(output);
					}

					var storage = Storages.localStorage;
					var key = 'heuristics_tps_' + view;
					if (storage.isSet(key)) {
						var pvis = storage.get(key);
					} else {
						var pvis = null;
					}
					if (pvis == 'hidden') {
						$('#rpanel_'+view).find('.cactiTableButton').find('span').first().html(
							'<a href="#" id="hider_'+view+'" class="linkOverDark hider" title="<?php print __esc('Hide Panel Contents', 'heuristics');?>"><i class="far fa-window-maximize"></i></a>' +
							'<a href="#" id="refresher_'+view+'" class="linkOverDark refresher" title="<?php print __esc('Force Refresh of Panel Data', 'heuristics');?>"><i class="fa fa-retweet"></i></a>' +
							'<a href="#" id="export_'+view+'" class="linkOverDark export" title="<?php print __esc('Export Panel Data', 'heuristics');?>"><i class="fa fa-chevron-down"></i></a>' +
							'<a href="#" id="remover_'+view+'" class="linkOverDark remover" title="<?php print __esc('Remove Dashboard Panel', 'heuristics');?>"><i class="fa fa-times"></i></a>'
						);
						$('#rpanel_'+view).find('table').slideUp();
					} else {
						$('#rpanel_'+view).find('.cactiTableButton').find('span').first().html(
							'<a href="#" id="hider_'+view+'" class="linkOverDark hider" title="<?php print __esc('Hide Panel Contents', 'heuristics');?>"><i class="far fa-window-minimize"></i></a>' +
							'<a href="#" id="refresher_'+view+'" class="linkOverDark refresher" title="<?php print __esc('Force Refresh of Panel Data', 'heuristics');?>"><i class="fa fa-retweet"></i></a>' +
							'<a href="#" id="export_'+view+'" class="linkOverDark export" title="<?php print __esc('Export Panel Data', 'heuristics');?>"><i class="fa fa-chevron-down"></i></a>' +
							'<a href="#" id="remover_'+view+'" class="linkOverDark remover" title="<?php print __esc('Remove Dashboard Panel', 'heuristics');?>"><i class="fa fa-times"></i></a>'
						);
						$('#rpanel_'+view).find('table').slideDown();
					}

					$('#remover_'+view).click(function() {
						panel = $(this).attr('id').replace('remover_','');
						$('#rpanel_'+panel).html('');
						$('input[value="'+panel+'"]').prop('checked', false);
						$('#views > option[value="'+panel+'"]').prop('selected', false);
						$('#views').multiselect('refresh');

						filterChange(false);
					});

					$('#export_'+view).click(function() {
						panel = $(this).attr('id').replace('export_','');
						exportPanelData(panel)
					});
					$('#refresher_'+view).click(function() {
						panel = $(this).attr('id').replace('refresher_','');
						forcePanelRefresh(panel)
					});

					$('#hider_'+view).click(function() {
						var storage = Storages.localStorage
						key = 'heuristics_tps_' + view;
						if ($('#rpanel_'+view).find('table').is(':visible')) {
							storage.set(key, 'hidden');
							$('#rpanel_'+view).find('table').slideUp();
							$('#hider_'+view).prop('title', "<?php print __esc('Show Panel Contents', 'heuristics');?>");
							$('#hider_'+view).find('i').removeClass('fa-window-minimize').addClass('fa-window-maximize');
						} else {
							storage.set(key, 'visible');
							$('#rpanel_'+view).find('table').slideDown();
							$('#hider_'+view).prop('title', "<?php print __esc('Hide Panel Contents', 'heuristics');?>");
							$('#hider_'+view).find('i').removeClass('fa-window-maximize').addClass('fa-window-minimize');
						}
					});

					applySkin();
				}

				if (typeof resolve == 'function') {
					resolve();
				} else if (typeof resolve == 'boolean' && resolve) {
					Pace.stop();
				}
			});
		}
	}

	function initializePanel(view, extra_args, resolve) {
		if (typeof extra_args == 'undefined') extra_args = '';

		strURL = buildRequest('ajaxview');

		$.getJSON(strURL+'&panel='+view+extra_args, function(data) {
			if (data != '') {
				if (data.output) {
					var output = data.output;
				}

				if (data.panel) {
					var view = data.panel;
				}

				var panelExists = $('#panel_'+view).length;

				if ($(document).find('#panel_'+view).length != 0) {
					$('#panel_'+view).empty();
					$('#panel_'+view).html(output);
				} else {
					$('.panels').append("<div id='panel_"+view+"' class='panel'></div>");
					$('#panel_'+view).html(output);
				}

				var storage = Storages.localStorage;
				var key = 'heuristics_tps_' + view;
				if (storage.isSet(key)) {
					var pvis = storage.get(key);
				} else {
					var pvis = null;
				}
				if (pvis == 'hidden') {
					$('#panel_'+view).find('.cactiTableButton').find('span').first().html(
						'<a href="#" id="hider_'+view+'" class="linkOverDark hider" title="<?php print __esc('Hide Panel Contents', 'heuristics');?>"><i class="far fa-window-maximize"></i></a>' +
						'<a href="#" id="refresher_'+view+'" class="linkOverDark refresher" title="<?php print __esc('Force Refresh of Panel Data', 'heuristics');?>"><i class="fa fa-retweet"></i></a>' +
						'<a href="#" id="export_'+view+'" class="linkOverDark export" title="<?php print __esc('Export Panel Data', 'heuristics');?>"><i class="fa fa-chevron-down"></i></a>' +
						'<a href="#" id="remover_'+view+'" class="linkOverDark remover" title="<?php print __esc('Remove Dashboard Panel', 'heuristics');?>"><i class="fa fa-times"></i></a>'
					);
					$('#panel_'+view).find('table').slideUp();
				} else {
					$('#panel_'+view).find('.cactiTableButton').find('span').first().html(
						'<a href="#" id="hider_'+view+'" class="linkOverDark hider" title="<?php print __esc('Hide Panel Contents', 'heuristics');?>"><i class="far fa-window-minimize"></i></a>' +
						'<a href="#" id="refresher_'+view+'" class="linkOverDark refresher" title="<?php print __esc('Force Refresh of Panel Data', 'heuristics');?>"><i class="fa fa-retweet"></i></a>' +
						'<a href="#" id="export_'+view+'" class="linkOverDark export" title="<?php print __esc('Export Panel Data', 'heuristics');?>"><i class="fa fa-chevron-down"></i></a>' +
						'<a href="#" id="remover_'+view+'" class="linkOverDark remover" title="<?php print __esc('Remove Dashboard Panel', 'heuristics');?>"><i class="fa fa-times"></i></a>'
					);
					$('#panel_'+view).find('table').slideDown();
				}

				$('#remover_'+view).click(function() {
					panel = $(this).attr('id').replace('remover_','');
					$('#panel_'+panel).remove();
					$('input[value="'+panel+'"]').prop('checked', false);
					$('#views > option[value="'+panel+'"]').prop('selected', false);
					$('#views').multiselect('refresh');

					cur_views = new Array();
					order     = '';
					for (var i = 0; i < all_views.length; i++) {
						if ($('#views option[value='+all_views[i]+']').is(':selected')) {
							if (all_views[i] != 'health') {
								cur_views.push(all_views[i]);
								order += (order != '' ? ' ':'') + all_views[i];
							}
						}
					}

					filterChange(false);
				});

				$('#export_'+view).click(function() {
					panel = $(this).attr('id').replace('export_','');
					exportPanelData(panel)
				});
				$('#refresher_'+view).click(function() {
					panel = $(this).attr('id').replace('refresher_','');
					forcePanelRefresh(panel)
				});

				$('#hider_'+view).click(function() {
					var storage = Storages.localStorage
					key = 'heuristics_tps_' + view;
					if ($('#panel_'+view).find('table').is(':visible')) {
						storage.set(key, 'hidden');
						$('#panel_'+view).find('table').slideUp();
						$('#hider_'+view).prop('title', "<?php print __esc('Show Panel Contents', 'heuristics');?>");
						$('#hider_'+view).find('i').removeClass('fa-window-minimize').addClass('fa-window-maximize');
					} else {
						storage.set(key, 'visible');
						$('#panel_'+view).find('table').slideDown();
						$('#hider_'+view).prop('title', "<?php print __esc('Hide Panel Contents', 'heuristics');?>");
						$('#hider_'+view).find('i').removeClass('fa-window-maximize').addClass('fa-window-minimize');
					}
				});

				applySkin();

				if (typeof resolve == 'function') {
					resolve();
				} else if (typeof resolve == 'boolean' && resolve) {
					Pace.stop();
				}
			}
		});
	}

	function initializeAllViews() {
		// Reinitialize deferred array
		deferreds = [];

		Pace.start();

		deferreds[0] = new Promise((resolve) => {
			initializeHealthView('', resolve);
		});

		$.each(cur_views, function(index, panel) {
			if (panel != '') {
				deferreds[index+1] = new Promise((resolve) => {
					initializePanel(panel, '', resolve);
				});
			}
		});

		Promise.allSettled(deferreds).then((value) => {
			console.log('value:' + value);
			Pace.stop();
		});
	}

	function initializeGeneralCharts() {
		cur_tab = 0;

		for(chart=0; chart<cur_charts.length; chart++) {
			if (cur_charts[chart] != "") {
				fusionFile = getFusionFile(cur_charts[chart]);
				drawChart(cur_charts[chart], fusionFile);
			}
		}
	}

	function initializeProjectQueueCharts() {
		cur_tab = 1;

		var i=1;
		var graph;
		if (cur_graphs.length > 0) {
			$('#queueg').show();
			$('#pqgraphs').empty();
			for(graph=0; graph<cur_graphs.length; graph++) {
				// split is type|clusterid|type_name|metric
				// for example 'myQueue|myProjectName|myClusterid|running|username'
				items=cur_graphs[graph].split('|');
				queue=items[0];
				project=items[1];
				clusterid=items[2];
				user=items[3];
				metric=items[4];
				showTrendChart(queue, project, clusterid, user, metric, i);
				i++;
			}
		}
	}

	function initializeViews() {
		initializeAllViews();
	}

	function initializeViewsAndCharts() {
		initializeGeneralCharts();

		initializeAllViews();
	}

	function showCharts(queue, project, user, clusterid) {
		$('#queueg').show();
		$('#pqgraphs').empty();
		$('#ctabs').tabs('option','active','1');  //jquery 1.10

		$.get('heuristics.php?action=ajaxtab&tab=1');

		showTrendChart(queue, project, clusterid, user, 'pending', '1');
		showTrendChart(queue, project, clusterid, user, 'running', '2');
		showTrendChart(queue, project, clusterid, user, 'tput', '4');
		showTrendChart(queue, project, clusterid, user, 'finished', '3');
	}

	</script>
	<?php

	/* draw a bounding box */
	print "<div style='position:relative;padding-right:5px;'>\n";

	if (get_request_var('order') != '' && get_request_var('order') != 'health') {
		print "<div id='panelleft' style='top:0px;display:block;height:100%;position:relative;overflow:hidden;float:left;width:" . (one_column_display_type() ? '100%':'60%') . ";'>\n";

		show_page_filter();

		print "<div class='panels'>\n";

		api_plugin_hook('heuristics_show_portlets');

		$order = explode(' ', get_request_var('order'));
		$corder = explode(' ', get_request_var('corder'));

		foreach($order as $panel) {
			print "<div id='panel_" . $panel . "' class='panel'></div>\n";
		}

		print "</div>\n";
		print "</div>\n";

		print "<div id='right' style='top:0px; position:relative;float:right;width:" . (one_column_display_type() ? '100%':'39.6%') . ";'>\n";
	} elseif (get_request_var('health') != 'false' || get_request_var('corder') != '' || get_request_var('tab') == 1) {
		print "<div id='right' style='top:0px;position:relative;float:" . (one_column_display_type() ? 'left':'right') . ";width:" . (one_column_display_type() ? '100%':'60%') . "'>\n";

		show_page_filter();
	}

	print "<div id='rpanel_health'></div>\n";

	print "<div id='rpanel_charts'>\n";
	show_charts();
	print "</div>\n";

	print "</div>\n";
	print "</div>\n";

	table_cache_prune();
}

function show_page_filter() {
	global $config;

	html_start_box(__('User Filter for User %s', get_request_var('user_iq'), 'heuristics') .
		' [ ' . (api_plugin_user_realm_auth('grid_busers.php') ? "<a id='curr_user_grid' href='" . $config['url_path'] . "plugins/grid/grid_busers.php'>" . __('RTM Batch Users', 'heuristics') . '</a>, ':'') .
		"<a class='pic' id='curr_user_iq' href='" . $config['url_path'] . "plugins/heuristics/heuristics_jobs.php?reset=true&job_user=" . get_request_var('user_iq') . "'>" . __('Current Users Jobs', 'heuristics') . "</a>, " .
		"<a class='pic' href='" . $config['url_path'] . "plugins/heuristics/heuristics_jobs.php?reset=true&job_user=-1'>" . __('All User Jobs', 'heuristics') . "</a> ] " .
		"<span id='message' style='position:absolute;right:20px;top:5px;line-height:20px;'></span>", '100%', '', '3', 'center', '');

	heuristics_filter();

	html_end_box(false);
}

function show_charts() {
	global $config;

	if (get_request_var('health') == 'true' || get_request_var('corder') != '') {
		html_start_box(__('Charts', 'heuristics'), '100%', true, '3', 'center', '');

		print "<tr><td><table class='cactiTable'><tr><td>
			<div id='ctabs' class='tabs'>
				<ul>
					<li><a id='general' href='#graphs'>" . __('General', 'heuristics') . "</a></li>
					<li><a id='queueg' href='#pqgraphs'>" . __('Project/Queue', 'heuristics') . "</a></li>
				</ul>
				<div id='graphs' class='graphs center' style='padding:4px;'>
				</div>
				<div id='pqgraphs' class='graphs center' style='padding:4px;'>
				</div>
			</div>
			<div class='graphs' id='template' style='display:none;'></div>
		</td></tr></table></td></tr>\n";

		html_end_box(false, true);
	}
}

function show_cluster_health($export=false, &$header, &$data, &$others) {
	global $config;

	$start = microtime(true);

	/* Cluster Stats */
	$display_text = build_health_display_array();

	// set panel variable
	$panel = 'health';

	// grap output from browser
	ob_start();


	$sql_where = '';

	if (get_request_var('clusterid') != '-1') {
		$sql_where = ' AND us.clusterid = ' . db_qstr(get_request_var('clusterid'));
	}

	$key1 = 'chealth_clusters_' . get_request_var('user_iq') . '_' . get_request_var('clusterid');
	$key2 = 'chealth_down_' . get_request_var('user_iq') . '_' . get_request_var('clusterid');

	$clusters = table_cache_get($key1);

	if ($clusters == 'fetch') {
		$clusters = db_fetch_assoc_prepared("SELECT DISTINCT gc.clusterid, gc.clustername
			FROM grid_clusters AS gc
			INNER JOIN grid_heuristics_user_stats AS us
			ON gc.clusterid=us.clusterid
			WHERE user = ?
			$sql_where",
			array(get_nfilter_request_var('user_iq')));

		table_cache_add($key1, $clusters);
	}

	$down_hosts = table_cache_get($key2);

	if ($down_hosts == 'fetch') {
		$down_hosts = array_rekey(
			db_fetch_assoc('SELECT clusterid,
				(SUM(CASE WHEN status LIKE "U%" THEN 1 ELSE 0 END)/COUNT(clusterid))*100 AS down_percent,
				SUM(CASE WHEN status LIKE "U%" THEN 1 ELSE 0 END) AS down_hosts
				FROM grid_hosts
				GROUP BY clusterid'),
			'clusterid', array('down_percent', 'down_hosts')
		);

		table_cache_add($key2, $down_hosts);
	}

	$warn_percent     = read_config_option('heuristics_down_warn');
	$alarm_percent    = read_config_option('heuristics_down_alarm');
	$hourly_tput_warn = read_config_option('heuristics_throughput_warning');

	html_start_box(__('Cluster Health', 'heuristics'), '100%', '', '3', 'center', '');

	html_header($display_text);

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $c) {
			$cluster = db_fetch_row_prepared('SELECT *
				FROM grid_clusters
				WHERE clusterid = ?',
				array($c['clusterid']));
			if(!isset($cluster['clusterid'])){ //deleted just now.
				continue;
			}
			form_alternate_row('line' . $panel, true, true);

			$collect_status = grid_get_cluster_collect_status($cluster);
			$cluster_status = grid_get_master_lsf_status($cluster['clusterid']);

			if (isset($down_hosts[$c['clusterid']]) && $down_hosts[$c['clusterid']]['down_percent'] >= $alarm_percent) {
				$down = 'red-ball.png';
			} elseif (isset($down_hosts[$c['clusterid']]) && $down_hosts[$c['clusterid']]['down_percent'] >= $warn_percent) {
				$down = 'yellow-ball.png';
			} else {
				$down = 'green-ball.png';
			}

			switch($collect_status) {
				case 'Maintenance':
					$collect_status = "<font style='color:orange;'>$collect_status</font>\n";
					break;
				case 'Admin Down':
				case 'No Data':
					$collect_status = "<font style='color:blue;'>$collect_status</font>\n";
					break;
				case 'Up':
					$collect_status = "<font style='color:green;'>$collect_status</font>\n";
					break;
				case 'Down':
				case 'Job Down':
				case 'Diminished':
					$collect_status = "<font style='color:red;'>$collect_status</font>\n";
					break;
			}

			$tholds = db_fetch_assoc_prepared('SELECT clusterid, syslog_priority, count(h.id) AS tholds
				FROM host AS h
				INNER JOIN thold_data AS td
				ON h.id=td.host_id
				WHERE thold_enabled="on"
				AND clusterid = ?
				AND td.thold_alert>0
				GROUP BY h.clusterid, syslog_priority',
				array($c['clusterid']));

			$alarms = db_fetch_assoc_prepared('SELECT clusterid, syslog_priority, count(id) AS alerts
				FROM gridalarms_alarm
				WHERE alarm_enabled="on"
				AND clusterid = ?
				AND alarm_alert>0
				GROUP BY clusterid, syslog_priority',
				array($c['clusterid']));

			$tholds_count=0;
			foreach($tholds as $t1) {
				$tholds_count += $t1['tholds'];
			}
			$alarms_count=0;
			foreach($alarms as $a1) {
				$alarms_count += $a1['alerts'];
			}

			if (cacti_sizeof($tholds) || cacti_sizeof($alarms)) {
				$alrm = '';
				$export_alrm = 'ok';
				if (cacti_sizeof($tholds)) {
				foreach($tholds as $t) {
					switch($t['syslog_priority']) {
					case '0':
					case '1':
					case '2':
					case '3':
						$alrm = 'red';
						break 2;
					case '4':
						$alrm = 'yellow';
						break;
					case '5':
						if ($alrm != 'yellow') $alrm = 'blue';
						break;
					default:
						break;
					}
				}
				}

				if ($alrm != 'red') {
					if (cacti_sizeof($alarms)) {
					foreach($alarms as $a) {
						switch($a['syslog_priority']) {
						case '0':
						case '1':
						case '2':
						case '3':
							$alrm = 'red';
							break 2;
						case '4':
							$alrm = 'yellow';
							break;
						case '5':
							if ($alrm != 'yellow') $alrm = 'blue';
							break;
						default:
							break;
						}
					}
					}
				}

				if ($alrm == '') {
					$alrm = "<img title='There are " . $tholds_count . " Thresholds and " . $alarms_count . " Alarms Triggered, All are Informational' src='images/green-ball.png'>";
					$export_alrm = 'all informational';
				} elseif ($alrm == 'red') {
					$alrm = "<img title='There are " . $tholds_count . " Thresholds and " . $alarms_count . " Alarms Triggered, Some Are Error or Above' src='images/red-ball.png'>";
					$export_alrm = 'some error';
				} elseif ($alrm == 'yellow') {
					$alrm = "<img title='There are " . $tholds_count . " Thresholds and " . $alarms_count . " Alarms Triggered, Some Are Warning' src='images/yellow-ball.png'>";
					$export_alrm = 'some warn';
				} else {
					$alrm = "<img title='There are " . $tholds_count . " Thresholds and " . $alarms_count . " Alarms Triggered, Some Are Notice' src='images/blue-ball.png'>";
					$export_alrm = 'some notice';
				}
			} else {
				$alrm = "<img title='No Thresholds or Alarms Triggered' src='images/green-ball.png'>";
				$export_alrm = 'not triggered';
			}

			$sum_hourly_tput = db_fetch_cell_prepared('SELECT sum(tputHOUR)
				FROM grid_heuristics_user_stats
				WHERE clusterid= ?',
				array($c['clusterid']));

			if ($sum_hourly_tput >= $hourly_tput_warn) {
				$tput_warn_title = "<img title='Hourly Throughput: $sum_hourly_tput, Hourly Throughput Warning Threshold: $hourly_tput_warn reached.' src='images/yellow-ball.png'>";
				$export_tput_warn_title = "reached";
			} else {
				$tput_warn_title = "<img title='Hourly Throughput: $sum_hourly_tput, Hourly Throughput Warning Threshold: $hourly_tput_warn.' src='images/green-ball.png'>";
				$export_tput_warn_title = "not reached";
			}

			?>
			<td class='nowrap'><?php print $c['clustername'];?></td>
			<td class='left'><?php print $cluster_status[0];?></td>
			<td class='center'><?php print $collect_status;?></td>
			<td class='left'><?php print $cluster_status[1] . "/" . $cluster_status[2];?></td>
			<td class='center'><?php print $alrm;?></td>
			<td class='center'><img title='There are <?php print isset($down_hosts[$c['clusterid']])?$down_hosts[$c['clusterid']]['down_hosts']:'N/A';?> Down Hosts or <?php print isset($down_hosts[$c['clusterid']])?round($down_hosts[$c['clusterid']]['down_percent'],0):0;?> %' src='images/<?php print $down;?>'></td>
			<td class='center'><?php print $tput_warn_title;?></td>
			<?php
			if($export == true) {
				$data[$c['clusterid']][]=$c['clustername'];
				$data[$c['clusterid']][]=preg_replace('/<(.*)>/sU', '', $cluster_status[0]);
				$data[$c['clusterid']][]=preg_replace('/<(.*)>/sU', '', trim($collect_status));
				$data[$c['clusterid']][]=preg_replace('/<(.*)>/sU', '', $cluster_status[1]) . "/" . preg_replace('/<(.*)>/sU', '', $cluster_status[2]);
				$data[$c['clusterid']][]=$export_alrm;
				$data[$c['clusterid']][]=isset($down_hosts[$c['clusterid']])?$down_hosts[$c['clusterid']]['down_hosts']:'N/A';
				$data[$c['clusterid']][]=$export_tput_warn_title;
			}
			form_end_row();
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	html_end_box(false);

	$output = ob_get_clean();
	if($export == true) {
		foreach($display_text as $head) {
		    $header[]['name']= $head['display'];
		}
		return;
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function heuristics_get_limit_suffix(&$stats) {
	if (get_request_var('limit') == '-1' || get_request_var('limit') >= cacti_sizeof($stats)) {
		$suffix = ' [ ' . '<i class="normal">' . __('Showing All Rows', 'heuristics') . '</i> ]';
	} else {
		$suffix = ' [ ' . '<i class="normal">' . __('Showing First %s Rows', get_request_var('limit'), 'heuristics') . '</i> ]';
		$stats = array_slice($stats, 0, get_request_var('limit'));
	}

	return $suffix;
}

function show_license_checkouts($export=false, &$header, &$stats, &$others) {
	global $config;

	$start = microtime(true);

	// set panel variable
	$panel = 'checkouts';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	if (!db_table_exists('lic_services')) {
		return;
	}

	heuristics_build_header($header, __('Feature', 'heuristics'),       'feature_name',       'ASC',  'left',  __esc('License Service Feature Name', 'heuristics'));
	heuristics_build_header($header, __('Service', 'heuristics'),       'server_name',        'ASC',  'left',  __esc('License Service Name', 'heuristics'));
	heuristics_build_header($header, __('Vendor', 'heuristics'),        'server_vendor',      'ASC',  'left',  __esc('Service Vendor', 'heuristics'));
	heuristics_build_header($header, __('Type', 'heuristics'),          'server_licensetype', 'ASC',  'left',  __esc('Services License Type', 'heuristics'));
	heuristics_build_header($header, __('Max Tokens', 'heuristics'),    'max_tokens',         'DESC', 'right', __esc('Total Tokens for this Feature', 'heuristics'));
	heuristics_build_header($header, __('Free Tokens', 'heuristics'),   'free_tokens',        'ASC',  'right', __esc('Free Tokens for this Feature', 'heuristics'));
	heuristics_build_header($header, __('My Tokens', 'heuristics'),     'tokens',             'DESC', 'right', __esc('Total Tokens in use by this User', 'heuristics'));
	heuristics_build_header($header, __('Checkout Time', 'heuristics'), 'total_time',         'DESC', 'right', __esc('Total Token Time', 'heuristics'));
	heuristics_build_header($header, __('Max Time', 'heuristics'),      'max_time',           'DESC', 'right', __esc('Maximum Token Time', 'heuristics'));
	heuristics_build_header($header, __('Avg Time', 'heuristics'),      'avg_time',           'DESC', 'right', __esc('Average Token Time', 'heuristics'));

	$limit = heuristics_get_limit();

	$order_data     = heuristics_get_order(array('column' => 'tokens', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$key = 'checkouts_' . get_request_var('user_iq');

	$stats = table_cache_get($key, $sort_column, $sort_direction, $limit);

	if ($stats == 'fetch') {
		$sql = "SELECT ls.server_name, ls.server_vendor, ls.server_licensetype, lsfd.feature_name,
			lsfu.feature_max_licenses AS max_tokens, lsfu.feature_max_licenses-lsfu.feature_inuse_licenses AS free_tokens,
			SUM(tokens_acquired) AS tokens,
			SUM(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(lsfd.tokens_acquired_date)) AS total_time,
			MAX(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(lsfd.tokens_acquired_date)) AS max_time,
			AVG(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(lsfd.tokens_acquired_date)) AS avg_time
			FROM lic_services AS ls
			INNER JOIN lic_services_feature_details AS lsfd
			ON ls.service_id=lsfd.service_id
			INNER JOIN lic_services_feature_use AS lsfu
			ON lsfd.service_id=lsfu.service_id
			AND lsfd.feature_name=lsfu.feature_name
			WHERE lsfd.username=?
			AND lsfd.username <> ''
			GROUP BY ls.server_name, ls.server_vendor, ls.server_licensetype, lsfd.feature_name
			$order
			$limit";

		$stats = db_fetch_assoc_prepared($sql, array(get_request_var('user_iq')));

		table_cache_add($key, $stats, 300, $sort_column, $sort_direction, $limit);
	}

	$suffix = heuristics_get_limit_suffix($stats);

	if($export == true) {
		return;
	} else {
		html_start_box(__('Feature Checkouts for %s %s', get_user_title(), $suffix, 'heuristics'), '100%', '', '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, $panel);
	}

	if (cacti_sizeof($stats)) {
		foreach($stats as $row) {
			form_alternate_row('line' . $panel, true, true);
			?>
			<td><?php print $row['feature_name'];?></td>
			<td><?php print $row['server_name'];?></td>
			<td><?php print $row['server_vendor'];?></td>
			<td><?php print $row['server_licensetype'];?></td>
			<td class='right'><?php print number_format_i18n($row['max_tokens']);?></td>
			<td class='right'><?php print number_format_i18n($row['free_tokens']);?></td>
			<td class='right'><?php print number_format_i18n($row['tokens']);?></td>
			<td class='right'><?php print display_job_time($row['total_time']);?></td>
			<td class='right'><?php print display_job_time($row['max_time']);?></td>
			<td class='right'><?php print display_job_time($row['avg_time']);?></td>
			<?php

			form_end_row();
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function heuristics_pend_where($sql_where, $excluded = true) {
	$excluded_reasons = db_fetch_assoc_prepared('SELECT reason, subreason
		FROM grid_pendreasons_ignore
		WHERE user_id=?', array($_SESSION['sess_user_id']));

	if (cacti_sizeof($excluded_reasons)) {
		if ($excluded) {
			$sql_where .= ' AND NOT (';
		} else {
			$sql_where .= ' AND (';
		}
		$count = 0;

		foreach($excluded_reasons as $reason) {
			$sql_where .= ($count > 0 ? ' OR ':'') . '(gjpm.reason_code=' . $reason['reason'] . ' AND gjpm.sub_reason_code="' . $reason['subreason'] . '")';
			$count++;
		}

		$sql_where .= ')';
	}

	return $sql_where;
}

function heuristics_is_hidden_reason($reason, $subreason) {
	return db_fetch_cell_prepared('SELECT COUNT(*)
		FROM grid_pendreasons_ignore
		WHERE reason=?
		AND subreason=?
		AND user_id=?', array($reason, $subreason, $_SESSION['sess_user_id']));
}

function change_pending_reason_visibility() {
	if (isset_request_var('subreason')) {
		$subreason = sanitize_search_string(get_request_var('subreason'));
	} else {
		return false;
	}

	if (!is_numeric(get_request_var('reason'))) {
		return false;
	} else {
		$reason = get_request_var('reason');
	}

	if (get_request_var('subaction') == 'hide') {
		db_execute_prepared('REPLACE INTO grid_pendreasons_ignore
			(reason, subreason, user_id, issusp, last_updated, present)
			VALUES (?, ?, ?, 0, NOW(), 1)', array($reason, $subreason, $_SESSION['sess_user_id']));
	} else {
		db_execute_prepared('DELETE FROM grid_pendreasons_ignore
			WHERE reason=?
			AND subreason=?
			AND user_id=?', array($reason, $subreason, $_SESSION['sess_user_id']));
	}
}

function heuristics_excluded_reasons() {
	$excluded_reasons = db_fetch_assoc_prepared('SELECT reason, subreason
		FROM grid_pendreasons_ignore
		WHERE user_id = ?',
		array($_SESSION['sess_user_id']));

	$sql_where = '';
	if (get_request_var('clusterid') != '-1') {
		$sql_where = ' AND gj.clusterid = ' . db_qstr(get_request_var('clusterid'));
	}

	if (get_request_var('queue') != '-1') {
		$sql_where .= ' AND `queue` = ' . db_qstr(get_request_var('queue'));
	}

	$sql_where = heuristics_pend_where($sql_where, false);

	$sql = "SELECT COUNT(DISTINCT gjp.reason, gjp.subreason) AS total
		FROM grid_jobs AS gj
		INNER JOIN grid_clusters AS gc
		ON gj.clusterid=gc.clusterid
		INNER JOIN grid_jobs_pendreasons AS gjp
		ON gjp.clusterid=gj.clusterid
		AND gjp.jobid=gj.jobid
		AND gjp.indexid=gj.indexid
		AND gjp.submit_time=gj.submit_time
		INNER JOIN grid_jobs_pendreason_maps AS gjpm
		ON gjpm.issusp=gjp.issusp
		AND gjpm.reason_code=gjp.reason
		AND gjpm.sub_reason_code=gjp.subreason
		WHERE gj.stat IN ('PEND','USUSP','SSUSP','PSUSP')
		AND gj.user = ?
		$sql_where";

	//cacti_log(str_replace("\n", "", $sql));

	return db_fetch_cell_prepared($sql, array(get_request_var('user_iq')));
}

function show_pending_reasons($export=false, &$header, &$stats, &$others) {
	global $config;

	$start = microtime(true);

	// set panel variable
	$panel = 'pendr';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	$limit = heuristics_get_limit(false);

	$suffix         = '';
	$order_data     = heuristics_get_order(array('column' => 'jobs', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$key = 'preasons_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('excluded');

	$stats = table_cache_get($key, $sort_column, $sort_direction, $limit);

	if ($stats == 'fetch') {
		$sql_where = '';

		if (get_request_var('clusterid') != '-1') {
			$sql_where = ' AND gj.clusterid=' . db_qstr(get_request_var('clusterid'));
		}

		if (get_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue`=' . db_qstr(get_request_var('queue'));
		}

		if (get_request_var('excluded') == 'true') {
			$sql_where .= heuristics_pend_where($sql_where);
		}

		$sql = "SELECT gj.clusterid, clustername, queue, user,
			gjpm.reason, gjpm.sub_reason_code, gjpm.reason_code, gjp.issusp, COUNT(DISTINCT gj.clusterid, gj.jobid, gj.indexid, gj.submit_time) AS jobs,
			SUM(IF(gjp.end_time='0000-00-00 00:00:00',UNIX_TIMESTAMP()-UNIX_TIMESTAMP(gjp.start_time), UNIX_TIMESTAMP(gjp.end_time)-UNIX_TIMESTAMP(gjp.start_time))) AS total_pend,
			MAX(IF(gjp.end_time='0000-00-00 00:00:00',UNIX_TIMESTAMP()-UNIX_TIMESTAMP(gjp.start_time), UNIX_TIMESTAMP(gjp.end_time)-UNIX_TIMESTAMP(gjp.start_time))) AS max_pend,
			AVG(IF(gjp.end_time='0000-00-00 00:00:00',UNIX_TIMESTAMP()-UNIX_TIMESTAMP(gjp.start_time), UNIX_TIMESTAMP(gjp.end_time)-UNIX_TIMESTAMP(gjp.start_time))) AS avg_pend
			FROM grid_jobs AS gj
			INNER JOIN grid_clusters AS gc
			ON gj.clusterid=gc.clusterid
			INNER JOIN grid_jobs_pendreasons AS gjp
			ON gjp.clusterid=gj.clusterid
			AND gjp.jobid=gj.jobid
			AND gjp.indexid=gj.indexid
			AND gjp.submit_time=gj.submit_time
			INNER JOIN grid_jobs_pendreason_maps AS gjpm
			ON gjpm.issusp=gjp.issusp
			AND gjpm.reason_code=gjp.reason
			AND gjpm.sub_reason_code=gjp.subreason
			WHERE gj.stat IN ('PEND','USUSP','SSUSP','PSUSP') AND gj.user=?
			$sql_where
			GROUP BY gj.clusterid, gj.queue, gjp.reason, gjp.subreason
			$order
			$limit";

		//cacti_log(str_replace("\n", "", $sql));

		$stats = db_fetch_assoc_prepared($sql, array(get_request_var('user_iq')));

		table_cache_add($key, $stats, 300, $sort_column, $sort_direction, $limit);
	}

	if (get_request_var('excluded') == 'true') {
		$excluded = heuristics_excluded_reasons();

		if (!empty($excluded)) {
			$suffix .= ' [ <i class="normal">' . __('%s Reasons Hidden', $excluded, 'heuristics') . '</i> ]';
		}

		$suffix .= ' [ <a href="#" onClick="changeReasonExclusion()">' . (get_request_var('excluded') == 'true' ? __('Show Excluded Reasons', 'heuristics'):__('Hide Excluded Reasons', 'heuristics')) . '</a> ]';
	} else {
		$suffix .= ' [ <i class="normal">' . __('All Reasons Shown', 'heuristics') . '</i> ] [ <a href="#" onClick="changeReasonExclusion()">' . __('Hide Excluded Reasons', 'heuristics') . '</a> ]';
	}

	$heuristics_num_clusters = heuristics_num_clusters($stats);
	if ($heuristics_num_clusters > 1) {
		heuristics_build_header($header, __('Cluster', 'heuristics'), 'clustername', 'ASC', 'left', __esc('The name of your Cluster', 'heuristics'));
	}

	heuristics_build_header($header, __('Queue', 'heuristics'),      'queue',      'ASC',  'left',  __esc('Your Jobs run Queue', 'heuristics'));
	heuristics_build_header($header, __('Reason', 'heuristics'),     'reason',     'ASC',  'left',  __esc('The Pending Reasons impacting Jobs', 'heuristics'));
	heuristics_build_header($header, __('Type', 'heuristics'),       'issusp',     'ASC',  'left',  __esc('Is this a Pending Reason or a Suspended Reason', 'heuristics'));
	heuristics_build_header($header, __('Status', 'heuristics'),     'nosort',     '',     'left',  __esc('If Hidden then if you hide reasons this reason will be Hidden', 'heuristics'));
	heuristics_build_header($header, __('Jobs', 'heuristics'),       'jobs',       'DESC', 'right', __esc('The total number of Pending Jobs', 'heuristics'));
	heuristics_build_header($header, __('Total Time', 'heuristics'), 'total_pend', 'DESC', 'right', __esc('Total Pending Time', 'heuristics'));
	heuristics_build_header($header, __('Max Time', 'heuristics'),   'max_pend',   'DESC', 'right', __esc('Maximum Job Pending Time', 'heuristics'));
	heuristics_build_header($header, __('Avg Time', 'heuristics'),   'avg_pend',   'DESC', 'right', __esc('Average Job Pending Time', 'heuristics'));

	if($export == true) {
		return;
	} else {
		html_start_box(__('Pending Reasons by Queue for %s %s', get_user_title(), $suffix, 'heuristics'), '100%', '', '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, 'pendr');
	}

	if (cacti_sizeof($stats)) {
		$i = 0;

		foreach($stats as $row) {
			if (heuristics_is_hidden_reason($row['reason_code'], $row['sub_reason_code'])) {
				$visibility = "<a href='#' title='" . __esc('Click to make Visible', 'heuristics') . "' onClick='changeReasonVisibility(\"show\", " . $row['reason_code'] . ",\"" . $row['sub_reason_code'] . "\")'>" . __('Hidden', 'heuristics') . "</a>";
			} else {
				$visibility = "<a href='#' title='" . __esc('Click to Hide', 'heuristics') . "' onClick='changeReasonVisibility(\"hide\", " . $row['reason_code'] . ",\"" . $row['sub_reason_code'] . "\")'>" . __('Visible', 'heuristics') . "</a>";
			}

			form_alternate_row('line' . $panel, true, true);

			form_selectable_cluster($heuristics_num_clusters, $row['clustername']);
			form_selectable_cell($row['queue'], $i);
			form_selectable_cell(format_reason_heur($row), $i);
			form_selectable_cell($row['issusp'] == 0 ? __('Pending', 'heuristics'):__('Suspended', 'heuristics'), $i);
			form_selectable_cell($visibility, $i);
			form_selectable_cell(number_format_i18n($row['jobs']), $i, '', 'right');
			form_selectable_cell(display_job_time($row['total_pend']), $i, '', 'right');
			form_selectable_cell(display_job_time($row['max_pend']), $i, '', 'right');
			form_selectable_cell(display_job_time($row['avg_pend']), $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function format_reason_heur($row) {
	if (strpos($row['reason'], 'for resource reservation') !== false) {
		return sprintf('Jobs reservation for (%s) not satisfied', $row['sub_reason_code']);
	} elseif (strpos($row['reason'], 'Load threshold reached') !== false) {
		return sprintf('Load threshold breached for (%s)', $row['sub_reason_code']);
	} else {
		return $row['reason'];
	}
}

function show_exit_stats($export=false, &$header, &$stats, &$others) {
	global $config;

	$start = microtime(true);

	// set panel variable
	$panel = 'exitanal';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	// Handle Time Ranges
	$time       = time() - get_request_var('timespan');
	$dend_time  = date('m-d H:i', $time);

	/* Exit Analysis */
	$limit = heuristics_get_limit();

	$order_data     = heuristics_get_order(array('column' => 'jobs', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$key = 'exit_stats_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');

	$stats = table_cache_get($key, $sort_column, $sort_direction, $limit);

	if ($stats == 'fetch') {
		$sql_where = 'WHERE stat = "EXIT" AND user = ' . db_qstr(get_request_var('user_iq'));

		if (get_request_var('clusterid') != '-1') {
			$sql_where .= ' AND clusterid = ' . db_qstr(get_request_var('clusterid'));
		}

		if (get_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue` = ' . db_qstr(get_request_var('queue'));
		}

		$grid_jobs_finished = build_heuristics_db_union($sql_where, array('exceptMask', 'exitStatus', 'exitInfo'), get_request_var('timespan'));

		$sql = "SELECT gjf.clusterid, clustername, queue, projectName, user,
			exceptMask, exitStatus, exitInfo, count(jobid) AS jobs
			FROM ($grid_jobs_finished) AS gjf
			INNER JOIN grid_clusters AS gc
			ON gjf.clusterid=gc.clusterid
			GROUP BY gjf.clusterid, queue, projectName, user, exceptMask, exitStatus, exitInfo
			$order $limit";

		//cacti_log(str_replace("\n", "", $sql));

		$stats = db_fetch_assoc($sql);

		table_cache_add($key, $stats, 300, $sort_column, $sort_direction, $limit);
	}

	$suffix = heuristics_get_limit_suffix($stats);
	$heuristics_num_clusters = heuristics_num_clusters($stats);
	if ($heuristics_num_clusters > 1) {
		heuristics_build_header($header, __('Cluster', 'heuristics'), 'clustername', 'ASC', 'left', __esc('The name of your cluster', 'heuristics'));
	}

	heuristics_build_header($header, __('Queue', 'heuristics'),   'queue',       'ASC',  'left',  __esc('Your Jobs Run Queue', 'heuristics'));
	heuristics_build_header($header, __('Project', 'heuristics'), 'projectName', 'DESC', 'left',  __esc('Your Jobs Run Project', 'heuristics'));
	heuristics_build_header($header, __('Jobs', 'heuristics'),    'jobs',        'DESC', 'right', __esc('The total number of exited Jobs', 'heuristics'));
	heuristics_build_header($header, __('Reason', 'heuristics'),  '',            '',     'left',  __esc('The Reason that these Jobs Exited', 'heuristics'));

	if($export == true) {
		return;
	} else {
		html_start_box(__('Exit Analysis Since \'%s\' by Queue/Project for %s %s', $dend_time, get_user_title(), $suffix, 'heuristics'), '100%', '', '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, $panel);
	}

	if (cacti_sizeof($stats)) {
		$i = 0;

		foreach($stats as $row) {
			$url = 'heuristics_jobs.php' .
				'?reset=true&job_user=' . get_request_var('user_iq') .
				'&status=EXIT&timespan=' . get_request_var('timespan') .
				'&exitcode=' . $row['exitStatus'] . '|' . $row['exceptMask'] . '|' . $row['exitInfo'] .
				'&project=' . $row['projectName'] .
				'&queue=' . $row['queue'] .
				'&clusterid=' . $row['clusterid'];

			form_alternate_row('line' . $panel, true, true);

			form_selectable_cluster($heuristics_num_clusters, $row['clustername']);
			form_selectable_cell($row['queue'], $i);
			form_selectable_cell($row['projectName'], $i);
			form_selectable_cell(filter_value(number_format_i18n($row['jobs']), '', $url), $i, '', 'right');
			form_selectable_cell(heuristics_get_exit_code($row['exitStatus']) . getExceptionStatus($row['exceptMask'], $row['exitInfo']), $i);

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function form_selectable_cluster($heuristics_num_clusters, $clustername, $id = 0) {
	if ($heuristics_num_clusters > 1) {
		form_selectable_cell($clustername, $id);
	}
}

function show_daily_stats($export=false, &$header, &$data, &$others) {
	global $config;
	$sql_params = array();

	$start = microtime(true);

	// set panel variable
	$panel = 'tput';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	$limit = heuristics_get_limit();

	$order_data     = heuristics_get_order(array('column' => 'numDONE', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$key1 = 'daily_today_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue');;
	$key2 = 'daily_yesterday_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue');;

	$today = table_cache_get($key1, $sort_column, $sort_direction, $limit);

	if ($today == 'fetch') {
		$start_time = date('Y-m-d H:i:s', strtotime('12:00am'));

		if (get_request_var('queue') == '-1') {
			$sql_where = "WHERE user = ?
				AND stat IN ('EXIT','DONE')
				AND end_time > ?";
			$sql_params[] = get_request_var('user_iq');
			$sql_params[] = $start_time;
		} else {
			$sql_where = "WHERE user = ?
				AND `queue` = ?
				AND stat IN ('EXIT','DONE')
				AND end_time > ?";
			$sql_params[] = get_request_var('user_iq');
			$sql_params[] = get_request_var('queue');
			$sql_params[] = $start_time;
		}

		if (get_request_var('clusterid') != '-1') {
			$sql_where .= ' AND gjf.clusterid = ?';
			$sql_params[] = get_request_var('clusterid');
		}

		$today = db_fetch_assoc_prepared("SELECT gjf.clusterid, clustername, queue,
			SUM(CASE WHEN stat='EXIT' THEN num_cpus ELSE 0 END) AS numEXIT,
			SUM(CASE WHEN stat='DONE' THEN num_cpus ELSE 0 END) AS numDONE,
			AVG(CASE WHEN start_time>'1971-00-00' THEN UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(submit_time) ELSE NULL END) AS tt
			FROM grid_jobs_finished AS gjf
			INNER JOIN grid_clusters AS gc
			ON gjf.clusterid=gc.clusterid
			$sql_where
			GROUP BY gjf.clusterid, queue
			$order
			$limit", $sql_params);

		table_cache_add($key1, $today, 600, $sort_column, $sort_direction, $limit);
	}

	$yesterday = table_cache_get($key2, $sort_column, $sort_direction, $limit);

	if ($yesterday == 'fetch') {
		if (read_config_option('grid_partitioning_enable') == 'on') {
			$sql_where = ' ';
			$sql_params_where = array();

			if (get_request_var('clusterid') != '-1') {
				$sql_where = ' AND clusterid = ?';
				$sql_params_where[] = get_request_var('clusterid');
			}

			$time = date('Y-m-d H:i:s', time() - 172800);  //172800 - 2 days, make sure all possible partition tables containg yesterday job data are included.

			$partitions = array_rekey(
				db_fetch_assoc_prepared('SELECT `partition`
					FROM grid_table_partitions
					WHERE table_name = "grid_job_daily_stats"
					AND min_time >= ?',
					array($time)),
				'partition' , 'partition'
			);
			$yesterday  = array();

			if (get_request_var('queue') == '-1') {
				$ysql = "SELECT gjds.clusterid, clustername, queue, stat, SUM(jobs_in_state) AS slots
					FROM (
						SELECT * FROM grid_job_daily_stats
						WHERE user= ?
						AND stat IN ('EXITED','ENDED')
						AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end
						$sql_where";
				$sql_params = array_merge(array(get_request_var('user_iq')), $sql_params_where);
			} else {
				$ysql = "SELECT gjds.clusterid, clustername, queue, stat, SUM(jobs_in_state) AS slots
					FROM (
						SELECT * FROM grid_job_daily_stats
						WHERE user= ?
						AND `queue` = ?
						AND stat IN ('EXITED','ENDED')
						AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end
						$sql_where";
				$sql_params = array_merge(array(get_request_var('user_iq'), get_request_var('queue')), $sql_params_where);
			}

			if (cacti_sizeof($partitions)) {
				foreach($partitions as $p) {
					$p = substr('000' . $p, -3);

					if (get_request_var('queue') == '-1') {
						$ysql .= (strlen($ysql) ? ' UNION ':'') . "(SELECT *
							FROM grid_job_daily_stats_v$p
							WHERE user = ?
							AND stat IN ('EXITED','ENDED')
							$sql_where
							AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end) AS gjds$p";
						$sql_params = array_merge($sql_params, array(get_request_var('user_iq')), $sql_params_where);
					} else {
						$ysql .= (strlen($ysql) ? ' UNION ':'') . "(SELECT *
							FROM grid_job_daily_stats_v$p
							WHERE user = ?
							AND `queue` = ?
							AND stat IN ('EXITED','ENDED')
							$sql_where
							AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end) AS gjds$p";
						$sql_params = array_merge($sql_params, array(get_request_var('user_iq'), get_request_var('queue')), $sql_params_where);
					}
				}
			}

			$ysql .= ') AS gjds
				INNER JOIN grid_clusters AS gc
				ON gjds.clusterid=gc.clusterid
				GROUP BY gjds.clusterid, queue, stat
				ORDER BY clustername, queue, stat';

			//cacti_log(str_replace("\n", "", $ysql));

			$yesterday = db_fetch_assoc_prepared($ysql, $sql_params);

			table_cache_add($key2, $yesterday, 3600, $sort_column, $sort_direction, $limit);
		} else {
			$sql_where = '';
			$sql_params = array();

			if (get_request_var('clusterid') != '-1') {
				$sql_where = ' AND gjds.clusterid = ?';
				$sql_params[] = get_request_var('clusterid');
			}

			if (get_request_var('queue') == '-1') {
				$yesterday = db_fetch_assoc_prepared("SELECT gjds.clusterid, clustername, queue, stat, SUM(jobs_in_state) AS slots
					FROM grid_job_daily_stats AS gjds
					INNER JOIN grid_clusters AS gc
					ON gjds.clusterid=gc.clusterid
					WHERE user = ?
					$sql_where
					AND stat IN ('EXITED','ENDED')
					AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end
					GROUP BY gjds.clusterid, queue, stat
					ORDER BY clustername, queue, stat", array_merge(array(get_request_var('user_iq')), $sql_params));
			} else {
				$yesterday = db_fetch_assoc_prepared("SELECT gjds.clusterid, clustername, queue, stat, SUM(jobs_in_state) AS slots
					FROM grid_job_daily_stats AS gjds
					INNER JOIN grid_clusters AS gc
					ON gjds.clusterid=gc.clusterid
					WHERE user = ?
					AND `queue` = ?
					$sql_where
					AND stat IN ('EXITED','ENDED')
					AND FROM_UNIXTIME(UNIX_TIMESTAMP()-86400) BETWEEN interval_start AND interval_end
					GROUP BY gjds.clusterid, queue, stat
					ORDER BY clustername, queue, stat", array_merge(array(get_request_var('user_iq'), get_request_var('queue')), $sql_params));
			}

			table_cache_add($key2, $yesterday, 3600, $sort_column, $sort_direction, $limit);
		}
	}

	$data = array();

	if (cacti_sizeof($today)) {
		foreach($today as $t) {
			$data[$t['clustername'] . '|' . $t['queue']]['tEXITED'] = $t['numEXIT'];
			$data[$t['clustername'] . '|' . $t['queue']]['tENDED']  = $t['numDONE'];
			$data[$t['clustername'] . '|' . $t['queue']]['ttt']     = $t['tt'];
			$data[$t['clustername'] . '|' . $t['queue']]['clusterid']     = $t['clusterid'];
		}
	}

	if (cacti_sizeof($yesterday)) {
		foreach($yesterday as $y) {
			if ($y['stat'] == 'EXITED') {
				$data[$y['clustername'] . '|' . $y['queue']]['yEXITED'] = $y['slots'];
				$data[$y['clustername'] . '|' . $y['queue']]['clusterid'] = $y['clusterid'];
			} else {
				$data[$y['clustername'] . '|' . $y['queue']]['yENDED'] = $y['slots'];
				$data[$y['clustername'] . '|' . $y['queue']]['clusterid'] = $y['clusterid'];
			}
		}
	}

	$suffix = heuristics_get_limit_suffix($data);

	$heuristics_num_clusters = heuristics_num_clusters($data);
	if ($heuristics_num_clusters > 1) {
		heuristics_build_header($header, __('Cluster', 'heuristics'), 'clustername', 'ASC', 'left', __esc('The name of your cluster', 'heuristics'));
	}

	heuristics_build_header($header, __('Queue', 'heuristics'),            'queue',   'ASC',  'left',  __esc('The name of your Queue', 'heuristics'));
	heuristics_build_header($header, __('Ended Today', 'heuristics'),      'numDONE', 'DESC', 'right', __esc('Your Done Jobs', 'heuristics'));
	heuristics_build_header($header, __('Exited Today', 'heuristics'),     'numEXIT', 'DESC', 'right', __esc('Your Exited Jobs', 'heuristics'));
	heuristics_build_header($header, __('Average TT Today', 'heuristics'), 'tt',      'DESC', 'right', __esc('Your Average Turnaround Time for Ended/Exited Jobs Today', 'heuristics'));
	heuristics_build_header($header, __('Ended Yesterday', 'heuristics'),  '',        '',     'right', __esc('Your Ended Jobs Yesterday', 'heuristics'));
	heuristics_build_header($header, __('Exited Yesterday', 'heuristics'), '',        '',     'right', __esc('Your Exited Jobs Yesterday', 'heuristics'));

	if($export == true) {
		return;
	} else {
		//Remove original JS event, instead of href + class.pic, need more improvement to refresh top-tab
		html_start_box(__('Daily Throughput by Cluster for %s %s ', get_user_title(), $suffix, 'heuristics') . (api_plugin_user_realm_auth('grid_dailystats.php') ? "[ <a id='history' href='" . $config['url_path'] . 'plugins/grid/grid_dailystats.php?clusterid=-1&stat=-1&rows_selector=15&user=' . get_request_var('user_iq') . "&queue=-1&project=-2&exec_host=-1&filter=&timespan=1&summarize=true'>" . __('RTM Summary Job History', 'heuristics') . "</a> ]":''), '100%', '', '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, $panel);
	}

	$na = __('N/A', 'heuristics');

	if (cacti_sizeof($data)) {
		$i = 0;
		foreach($data as $cluster => $d) {
			$cq = explode('|', $cluster);

			form_alternate_row('line' . $panel, true, true);

			form_selectable_cluster($heuristics_num_clusters, $cq[0]);
			form_selectable_cell($cq[1], $i);
			form_selectable_cell(isset($d['tENDED']) ? number_format_i18n($d['tENDED']):$na, $i, '', 'right');
			form_selectable_cell(isset($d['tEXITED']) ? number_format_i18n($d['tEXITED']):$na, $i, '', 'right');
			form_selectable_cell(isset($d['ttt']) ? display_job_time($d['ttt'], 0, FALSE):$na, $i, '', 'right');
			form_selectable_cell(isset($d['yENDED']) ? number_format_i18n($d['yENDED']):$na, $i, '', 'right');
			form_selectable_cell(isset($d['yEXITED']) ? number_format_i18n($d["yEXITED"]):$na, $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="5"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}
	?>
	<?php

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function show_cluster_stats($export=false, &$header, &$data, &$other_data) {
	global $config;
	$sql_params = array();

	$start = microtime(true);

	// set panel variable
	$panel = 'summary';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	$limit  = heuristics_get_limit();

	$order_data     = heuristics_get_order(array('column' => 'numPEND', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$sql_where = '';
	if (get_request_var('clusterid') != '-1') {
		$sql_where = ' AND ghus.clusterid=?';
		$sql_params[] = get_request_var('clusterid');
	}

	$key1 = 'cur_stats_' . get_request_var('user_iq') . '_' . get_request_var('clusterid');
	$key2 = 'other_stats_' . get_request_var('user_iq') . '_' . get_request_var('clusterid');

	$data = table_cache_get($key1, $sort_column, $sort_direction, $limit);

	if ($data == 'fetch') {
		$column_array = array(
			'clusterid',
			'clustername',
			'user',
			'numPEND',
			'numRUN',
			'numSUSP',
			'tputHOUR',
			'tput5MIN',
			'numDONE',
			'numEXIT'
		);

		$data = array_rekey(
			db_fetch_assoc_prepared("SELECT ghus.clusterid, gc.clustername, ghus.user,
				SUM(numPEND) AS numPEND,
				SUM(numRUN) AS numRUN,
				SUM(numSUSP) AS numSUSP,
				SUM(tputHOUR) AS tputHOUR,
				SUM(tput5MIN) AS tput5MIN,
				SUM(numDONE) AS numDONE,
				SUM(numEXIT) AS numEXIT
				FROM grid_heuristics_user_stats AS ghus
				INNER JOIN grid_clusters AS gc
				ON gc.clusterid = ghus.clusterid
				WHERE user = ?
				$sql_where
				GROUP BY ghus.clusterid
				$order
				$limit", array_merge(array(get_nfilter_request_var('user_iq')), $sql_params)),
			'clusterid', $column_array
		);

		table_cache_add($key1, $data, 300, $sort_column, $sort_direction, $limit);
	}

	$other_data = table_cache_get($key2, $sort_column, $sort_direction, $limit);

	if ($other_data == 'fetch') {
		$other_data = array_rekey(
			db_fetch_assoc_prepared("SELECT ghus.clusterid, gc.clustername, ghus.user,
				SUM(numPEND) AS numPEND,
				SUM(numRUN) AS numRUN,
				SUM(numSUSP) AS numSUSP,
				SUM(tputHOUR) AS tputHOUR,
				SUM(tput5MIN) AS tput5MIN,
				SUM(numDONE) AS numDONE,
				SUM(numEXIT) AS numEXIT
				FROM grid_heuristics_user_stats AS ghus
				INNER JOIN grid_clusters AS gc
				ON gc.clusterid = ghus.clusterid
				WHERE user != ?
				$sql_where
				GROUP BY ghus.clusterid
				$order", array_merge(array(get_nfilter_request_var('user_iq')), $sql_params)),
			'clusterid', $column_array
		);

		table_cache_add($key2, $other_data, 300, $sort_column, $sort_direction, $limit);
	}

	$update_time  = table_cache_gettime($key1, $sort_column, $sort_direction, $limit);
	$last_updated = time() - $update_time;
	$update_text  = '<i class="normal">' .
		($last_updated > 0 ? __('Last Updated %s Seconds Ago', $last_updated, 'heuristics'):__('Just Updated', 'heuristics')) . '</i>';

	$all_queues   = '<i class="normal">' . __('All Queues', 'heuristics') . '</i>';

	$suffix = heuristics_get_limit_suffix($data);

	$heuristics_num_clusters = heuristics_num_clusters($data);
	if ($heuristics_num_clusters > 1) {
		heuristics_build_header($header, __('Cluster', 'heuristics'), 'clustername', 'ASC', 'left', __esc('The name of your cluster', 'heuristics'));
	}

	heuristics_build_header($header, __('Pending', 'heuristics'),     'numPEND',  'DESC', 'right', __esc('Your Pending Jobs over Others', 'heuristics'));
	heuristics_build_header($header, __('Running', 'heuristics'),     'numRUN',   'DESC', 'right', __esc('Your Running Jobs over Others', 'heuristics'));
	heuristics_build_header($header, __('Suspended', 'heuristics'),   'numSUSP',  'DESC', 'right', __esc('Your Suspended Jobs over Others', 'heuristics'));
	heuristics_build_header($header, __('Finished/Hr', 'heuristics'), 'numDONE',  'DESC', 'right', __esc('Your Done Jobs over Others in last hour', 'heuristics'));
	heuristics_build_header($header, __('Exited/Hr', 'heuristics'),   'numEXIT',  'DESC', 'right', __esc('Your Exited Jobs over Others in last hour', 'heuristics'));
	heuristics_build_header($header, __('Hourly TPut', 'heuristics'), 'tputHOUR', 'DESC', 'right', __esc('Your Hourly Throughput over Others', 'heuristics'));
	heuristics_build_header($header, __('5Min TPut', 'heuristics'),   'tput5MIN', 'DESC', 'right', __esc('Your 5 Minute Throughput over Others', 'heuristics'));

	if($export == true) {
		foreach($data as $clusterid => $row) {
			if (!isset($other_data[$clusterid])) {
				$other_data[$clusterid]['numPEND']  = 0;
				$other_data[$clusterid]['numRUN']   = 0;
				$other_data[$clusterid]['numSUSP']  = 0;
				$other_data[$clusterid]['numDONE']  = 0;
				$other_data[$clusterid]['numEXIT']  = 0;
				$other_data[$clusterid]['tputHOUR'] = 0;
				$other_data[$clusterid]['tput5MIN'] = 0;
			}
		}
		return;
	} else {
		html_start_box(__('Current Status by Cluster [ %s ] %s [ %s ]', $all_queues, $suffix, $update_text, 'heuristics'), '100%', '', '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, $panel);
	}

	if (cacti_sizeof($data)) {
		$i = 0;

		foreach($data as $clusterid => $row) {
			$jobs_url = 'heuristics_jobs.php?reset=true&timespan=3600&job_user=' . get_request_var('user_iq') . '&clusterid=' . $row['clusterid'];
			$pend_url = $jobs_url . '&status=PEND';
			$run_url  = $jobs_url . '&status=RUNNING';
			$susp_url = $jobs_url . '&status=SUSP';
			$done_url = $jobs_url . '&status=DONE';
			$exit_url = $jobs_url . '&status=EXIT';

			if (!isset($other_data[$clusterid])) {
				$other_data[$clusterid]['numPEND']  = 0;
				$other_data[$clusterid]['numRUN']   = 0;
				$other_data[$clusterid]['numSUSP']  = 0;
				$other_data[$clusterid]['numDONE']  = 0;
				$other_data[$clusterid]['numEXIT']  = 0;
				$other_data[$clusterid]['tputHOUR'] = 0;
				$other_data[$clusterid]['tput5MIN'] = 0;
			}

			form_alternate_row('line' . $panel, true, true);

			if (api_plugin_hook_function('heuristics_summary_isaction') == true) {
				form_selectable_cell(api_plugin_hook_function('heuristics_summary_action', $row), $i);
			}

			form_selectable_cluster($heuristics_num_clusters, $row['clustername']);

			$pend = filter_value(number_format_i18n($row['numPEND']), '', $pend_url) . ' / ' . number_format_i18n($other_data[$clusterid]['numPEND']);
			$run  = filter_value(number_format_i18n($row['numRUN']), '', $run_url)   . ' / ' . number_format_i18n($other_data[$clusterid]['numRUN']);
			$susp = filter_value(number_format_i18n($row['numSUSP']), '', $susp_url) . ' / ' . number_format_i18n($other_data[$clusterid]['numSUSP']);
			$done = filter_value(number_format_i18n($row['numDONE']), '', $done_url) . ' / ' . number_format_i18n($other_data[$clusterid]['numDONE']);
			$exit = filter_value(number_format_i18n($row['numEXIT']), '', $exit_url) . ' / ' . number_format_i18n($other_data[$clusterid]['numEXIT']);

			form_selectable_cell($pend, $i, '', 'right');
			form_selectable_cell($run,  $i, '', 'right');
			form_selectable_cell($susp, $i, '', 'right');
			form_selectable_cell($done, $i, '', 'right');
			form_selectable_cell($exit, $i, '', 'right');

			form_selectable_cell(number_format_i18n($row['tputHOUR']) . ' / ' . number_format_i18n($other_data[$clusterid]['tputHOUR']), $i, '', 'right');
			form_selectable_cell(number_format_i18n($row['tput5MIN']) . ' / ' . number_format_i18n($other_data[$clusterid]['tput5MIN']), $i, '', 'right');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function heuristics_build_header(&$header, $name, $dbcolumn = '', $order = '', $align = '', $title = '') {
	static $nosort = 1;

	$col = cacti_sizeof($header);
	$header[$col]['name'] = $name;

	if (strlen($dbcolumn)) {
		$header[$col]['dbcolumn'] = $dbcolumn;
	} else {
		$header[$col]['dbcolumn'] = 'nosort' . $nosort;
		$nosort++;
	}

	if (strlen($order)) {
		$header[$col]['order'] = $order;
	} else {
		$header[$col]['order'] = '';
	}

	if (strlen($align)) {
		$header[$col]['align'] = $align;
	}
	if (strlen($title)) {
		$header[$col]['title'] = $title;
	}

	return $header;
}

/* heuristics_html_start_box - draws the start of an HTML box with an optional title
   @arg $title - the title of this box ("" for no title)
   @arg $id - id of the table
   @arg $width - the width of the box in pixels or percent
   @arg $div - present the table as a div
   @arg $align - the HTML alignment to use for the box (center, left, or right)
   @arg $add_text - the url to use when the user clicks 'Add' in the upper-right
        corner of the box ("" for no 'Add' link)
        This function has two method.  This first is for legacy behavior where you
        you pass in a href to the function, and an optional label as $add_label
        The new format accepts an array of hrefs to add to the start box.  The format
        of the array is as follows:

        $add_text = array(
            array(
                'id' => 'uniqueid',
                'href' => 'value',
                'title' => 'title',
                'callback' => true|false,
                'class' => 'fa fa-icon'
            ),
            ...
        );

        If the callback is true, the Cacti attribute will be added to the href
        to present only the contents and not to include both the headers.  If
        the link must go off page, simply make sure $callback is false.  There
        is a requirement to use fontawesome icon sets for this class, but it
        can include other classes.  In addition, the href can be a hash '#' if
        your page has a ready function that has it's own javascript.
   @arg $add_label - used with legacy behavior to add specific text to the link.
        This parameter is only used in the legacy behavior.
 */
function heuristics_html_start_box($title = '', $id = null, $width = '100%', $div = true, $align = 'center', $add_text = '', $add_label = false) {
	static $table_suffix = 1;

	if ($add_label === false) {
		$add_label = __('Add');
	}

	if (defined('CACTI_VERSION_BETA') && $title != '') {
		$title .= ' [ ' . get_cacti_version_text(false) . ' ]';
	}

	if ($id == null) {
		$table_prefix = basename(get_current_page(), '.php');;
		if (!isempty_request_var('action')) {
			$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('action'));
		} elseif (!isempty_request_var('report')) {
			$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('report'));
		} elseif (!isempty_request_var('tab')) {
			$table_prefix .= '_' . clean_up_name(get_nfilter_request_var('tab'));
		}
		$table_id = $table_prefix . $table_suffix;
	} else {
		$table_id = $id;
	}

	$child_id = $table_id . '_child';

	if ($title != '') {
		print "<div class='panel' id='$table_id'><div id='$child_id' class='cactiTable heuristicsTable' style='width:$width;text-align:$align;'>";
		print '<div>';
		print "<div class='cactiTableTitle'><span>" . ($title != '' ? $title:'') . '</span></div>';
		print "<div class='cactiTableButton'>";
		if ($add_text != '' && !is_array($add_text)) {
			print "<span class='cactiFilterAdd' style='padding:0px' title='$add_label'><a class='linkOverDark' href='" . html_escape($add_text) . "'><i class='fa fa-plus'></i></a></span>";
		} else {
			if (is_array($add_text)) {
				if (cacti_sizeof($add_text)) {
					foreach($add_text as $icon) {
						if (isset($icon['callback']) && $icon['callback'] === true) {
							$classo = 'linkOverDark';
						} else {
							$classo = '';
						}

						if (isset($icon['class']) && $icon['class'] !== '') {
							$classi = $icon['class'];
						} else {
							$classi = 'fa fa-plus';
						}

						if (isset($icon['style']) && $icon['style'] !== '') {
							$style = "style='" . $icon['style'] . "'";
						} else {
							$style = '';
						}

						if (isset($icon['href'])) {
							$href = html_escape($icon['href']);
						} else {
							$href = '#';
						}

						if (isset($icon['title'])) {
							$title = $icon['title'];
						} else {
							$title = $add_label;
						}

						print "<span class='cactiFilterAdd' style='padding:0px' title='" . html_escape($title) . "'><a" . (isset($icon['id']) ? " id='" . $icon['id'] . "'":'') . " class='$classo' href='$href'><i $style class='$classi'></i></a></span>";
					}
				}
			} else {
				print '<span> </span>';
			}
		}
		print '</div></div>';

		if ($div === true) {
			print "<div id='$child_id' class='cactiTable heuristicsTable'>";
		} else {
			print "<table id='$child_id' class='cactiTable'>";
		}
	} else {
		print "<div class='panel' id='$table_id'><div class='cactiTable heuristicsTable' style='width:$width;text-align:$align;'>";

		if ($div === true) {
			print "<div id='$child_id' class='cactiTable'>";
		} else {
			print "<table id='$child_id' class='cactiTable'>";
		}
	}

	if ($id == null) {
		$table_suffix++;
	}
}

function heuristics_get_order($default, $panel) {
	if (isset_request_var('sort_column') && isset_request_var('sort_direction')) {
		if (isset_request_var('panel') && get_request_var('panel') == $panel) {
			$_SESSION['sess_heur_' . $panel . '_sc'] = get_request_var('sort_column');
			$_SESSION['sess_heur_' . $panel . '_sd'] = get_request_var('sort_direction');
		}
	} elseif (empty($_SESSION['sess_heur_' . $panel . '_sc'])) {
		$_SESSION['sess_heur_' . $panel . '_sc'] = $default['column'];
		$_SESSION['sess_heur_' . $panel . '_sd'] = $default['direction'];
	}

	$sort_column     = $_SESSION['sess_heur_' . $panel . '_sc'];
	$sort_direction  = $_SESSION['sess_heur_' . $panel . '_sd'];

	return array('sql' => "ORDER BY $sort_column $sort_direction", 'sort_column' => $sort_column, 'sort_direction' => $sort_direction);
}

function heuristics_get_limit($add_one_more = true) {
	if (get_request_var('limit') != '-1') {
		if($add_one_more) {
			$limit = 'LIMIT ' . (1 + get_request_var('limit'));
		} else {
			$limit = 'LIMIT ' . get_request_var('limit');
		}
	} else {
		$limit = '';
	}

	return $limit;
}

function show_queue_stats($export=false, &$header, &$data, &$others) {
	global $heuristics_severities;
	$sql_params = array();

	$start = microtime(true);

	// set panel variable
	$panel = 'queues';

	// grap output from browser
	if($export==false) ob_start();

	if (!isset_request_var($panel) || get_request_var($panel) == 'false') {
		return;
	}

	$last_updated = update_user_statistics(get_request_var('user_iq'), (get_request_var('force') ? 0:300));

	$limit = heuristics_get_limit();

	$order_data     = heuristics_get_order(array('column' => 'numPEND', 'direction' => 'DESC'), $panel);
	$sort_column    = $order_data['sort_column'];
	$sort_direction = $order_data['sort_direction'];
	$order          = $order_data['sql'];

	$key1 = 'cur_me_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue');
	$key2 = 'cur_others_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue');

	$data = table_cache_get($key1, $sort_column, $sort_direction, $limit);

	if ($data == 'fetch') {
		$sql_where = '';
		if (get_request_var('clusterid') != '-1') {
			$sql_where = ' AND gj.clusterid = ?';
			$sql_params[] = get_request_var('clusterid');
		}

		if (get_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue` = ?';
			$sql_params[] = get_request_var('queue');
		}

		$sql = "SELECT gj.clusterid, gc.clustername, queue, projectName, reqCpus,
			numPEND, numRUN, numSUSP, tputHOUR, tput5MIN, numDONE, numEXIT
			FROM grid_heuristics_user_stats AS gj
			INNER JOIN grid_clusters AS gc
			ON gc.clusterid=gj.clusterid
			WHERE user = ?
			$sql_where
			HAVING numPEND > 0 OR numRUN > 0
			$order
			$limit";

		$data = db_fetch_assoc_prepared($sql, array_merge(array(get_request_var('user_iq')), $sql_params));

		table_cache_add($key1, $data, 300, $sort_column, $sort_direction, $limit);
	}

	$others = table_cache_get($key2, $sort_column, $sort_direction, $limit);

	if ($others == 'fetch') {
		/* -------------------------------------------------------------- */
		/* Important Note:                                                */
		/* -------------------------------------------------------------- */
		/* Others are at the entire queue level and not the project level */
		/* -------------------------------------------------------------- */
		$sql_where = '';
		$sql_params = array();
		if (get_request_var('clusterid') != '-1') {
			$sql_where = " AND gj.clusterid=?";
			$sql_params[] = get_request_var('clusterid');
		}

		$sql = "SELECT CONCAT(gc.clustername,'|',queue,'|',projectName) AS cq,
			SUM(numPEND) AS numPEND,
			SUM(numRUN) AS numRUN,
			SUM(numSUSP) AS numSUSP,
			SUM(tputHOUR) AS tputHOUR,
			SUM(tput5MIN) AS tput5MIN
			FROM grid_heuristics_user_stats AS gj
			INNER JOIN grid_clusters AS gc
			ON gc.clusterid = gj.clusterid
			WHERE user != ?
			$sql_where
			GROUP BY clustername, queue, projectName";

		//print $sql;

		$others = array_rekey(
			db_fetch_assoc_prepared($sql, array_merge(array(get_request_var('user_iq')), $sql_params)),
			'cq', array('numPEND', 'numRUN', 'numSUSP', 'tputHOUR', 'tput5MIN')
		);

		table_cache_add($key2, $others, 300, $sort_column, $sort_direction, $limit);
	}

	$suffix  = heuristics_get_limit_suffix($data);
	$suffix .= ' [ <i class="normal">' . ($last_updated > 0 ? __('Last Updated %s Seconds Ago', $last_updated, 'heuristics'):__('Just Updated', 'heuristics')) . '</i> ]';

	$severity_text = (get_request_var('severity')!='-1')? 'Severity ' .$heuristics_severities[get_request_var('severity')]:'';

	$heuristics_num_clusters = heuristics_num_clusters($data);
	if ($heuristics_num_clusters > 1) {
		heuristics_build_header($header, __('Cluster', 'heuristics'), 'clustername', 'ASC', 'left', __esc('The name of your cluster', 'heuristics'));
	}

	heuristics_build_header($header, __('Queue', 'heuristics'),      'queue',       'ASC',  'left',   __esc('The name of your Queue', 'heuristics'));
	heuristics_build_header($header, __('Project', 'heuristics'),    'projectName', 'ASC',  'left',   __esc('The name of your Project', 'heuristics'));
	heuristics_build_header($header, __('CPUs', 'heuristics'),       'reqCpus',     'DESC', 'right',  __esc('The Requested CPUS', 'heuristics'));
	heuristics_build_header($header, __('Pending', 'heuristics'),    'numPEND',     'DESC', 'right',  __esc('Your Pending Jobs over Others Pending Ahead', 'heuristics'));
	heuristics_build_header($header, __('Running', 'heuristics'),    'numRUN',      'DESC', 'right',  __esc('Your Running Jobs over Others Running', 'heuristics'));
	heuristics_build_header($header, __('Suspended', 'heuristics'),  'numSUSP',     'DESC', 'right',  __esc('Your Suspended Jobs over Others Suspended', 'heuristics'));
	heuristics_build_header($header, __('TPut(1Hr)', 'heuristics'),  'tputHOUR',    'DESC', 'right',  __esc('Your Hourly Throughput over Others', 'heuristics'));
	heuristics_build_header($header, __('TPut(5Min)', 'heuristics'), 'tput5MIN',    'DESC', 'right',  __esc('Your 5 Minute Throughput over Others', 'heuristics'));
	heuristics_build_header($header, __('Estimate', 'heuristics'),   '',            '',     'right',  __esc('Your estimated time to complete based upon Runtime Heuristics', 'heuristics'));
	heuristics_build_header($header, __('Method', 'heuristics'),     '',            '',     'center', __esc('The method used to calculate the completion estimate. Vel-JP5M: Velocity of the queue based upon the throughput of jobs over the last 5 minutes.  Vel-JPH: Velocity of the queue based upon the throughput of jobs over the last hour.  Heur-QP-JPH: Velocity of the queue and project based upon the historical throughput of jobs from the queue and project.  Heur-Q-JPH: Velocity of the queue based upon the historical throughput of jobs from the queue.  Heur-QG-75th: Expected completion time of the job based upon historical 75th percentile of runtime for the queue.', 'heuristics'));

	heuristics_build_header($header, __('Idle Jobs', 'heuristics'),  '',            '',     'center', __esc('Red if you have jobs that are currently running, but have been flagged as Idle by RTM which means that they have not used CPU in some time', 'heuristics'));

	heuristics_build_header($header, __('Long Jobs', 'heuristics'),  '',            '',     'center', __esc('Blue if RTM has no historical runtime information, Yellow if any of your jobs exceed the 75th percentile, and Red if any of your jobs exceed the 90th percentile for jobs from this Queue, Project, and Requested CPUS', 'heuristics'));

	heuristics_build_header($header, __('Pend Dpnd', 'heuristics'),  '',            '',     'center', __esc('Blue if you have any jobs with dependencies, and Red if your jobs have invalid Job Dependencies', 'heuristics'));

	heuristics_build_header($header, __('Mem Use', 'heuristics'),    '',            '',     'center', __esc('Red if your jobs have used more memory than reserved by the Memory Exception threshold, and Yellow if using much less than reserved below the Memory Exception threshold', 'heuristics'));

	if($export == true) {
		return;
	} else {
		html_start_box(__('Current Status by Queue/Project', 'heuristics') . ($severity_text != '' ?  " [ $severity_text ] ":'') . $suffix, '100%', false, '3', 'center', '');
		heuristics_header_sort($header, $sort_column, $sort_direction, $panel);
	}

	if (cacti_sizeof($data)) {
		$i = 0;

		foreach($data as $row) {
			form_alternate_row('line' . $panel, true, true);

			$queueClick   = 'showCharts("' . $row['queue'] . '","","' . get_request_var('user_iq') . '","' . $row['clusterid'] . '")';
			$projectClick = 'showCharts("' . $row['queue'] . '","' . $row['projectName'] . '","' . get_request_var('user_iq') . '","' . $row['clusterid'] . '")';

			$severity = '';

			$ahead    = get_pend_ahead($row, get_request_var('user_iq'));
			$estimate = get_estimate($row, $ahead, $others);
			$idled    = get_idled_jobs($row, get_request_var('user_iq'), $severity);
			$depend   = get_depend_jobs($row, get_request_var('user_iq'), $severity);
			$memvio   = get_memvio_jobs($row, get_request_var('user_iq'), $severity);
			$slow     = get_long_jobs($row, get_request_var('user_iq'), $severity);

			if ($estimate['units'] == 'numeric') {
				$testimate = __('%s Hrs', $estimate['estimate'], 'heuristics');
			} else {
				$testimate = $estimate['estimate'];
			}

			// Perform short circuit logic to bypass skipped jobs
			if (get_request_var('severity') == 'alarm'  && $severity != 'alarm') continue;
			if (get_request_var('severity') == 'warn'   && ($severity == 'notice' || $severity == '')) continue;
			if (get_request_var('severity') == 'notice' && $severity == '') continue;

			// Jobs URLs
			$jobs_url = 'heuristics_jobs.php' .
				'?reset=true' .
				'&project='   . $row['projectName'] .
				'&queue='     . $row['queue'] .
				'&job_user='  . get_request_var('user_iq') .
				'&clusterid=' . $row['clusterid'];

			$pend_url = $jobs_url . '&status=PEND';
			$run_url  = $jobs_url . '&status=RUNNING';
			$susp_url = $jobs_url . '&status=SUSP';

			$cq = $row['clustername'] . '|' . $row['queue'] . '|' . $row['projectName'];

			form_selectable_cluster($heuristics_num_clusters, $row['clustername']);
			form_selectable_cell(filter_value_heur($row['queue'], '', '#', __esc('Show Queue Based Charts', 'heuristics'), 'pic offpage', $queueClick), $i);
			form_selectable_cell(filter_value_heur(heuristics_trim_project($row['projectName']), '', '#', __esc('Show Queue/Project Based Charts for Project: %s', $row['projectName'], 'heuristics'), 'pic offpage', $projectClick), $i);
			form_selectable_cell($row['reqCpus'], $i, '', 'right');

			$pend   = filter_value(number_format_i18n($row['numPEND']), '', $pend_url) . ' / ' . ($ahead != 'NA' && $ahead != 'FCFS' ? number_format_i18n($ahead):$ahead);
			$run    = filter_value(number_format_i18n($row['numRUN']), '', $run_url) . ' / ' . (isset($others[$cq]['numRUN']) ? number_format_i18n($others[$cq]['numRUN']):'-');
			$susp   = filter_value(number_format_i18n($row['numSUSP']), '', $susp_url) . ' / ' . (isset($others[$cq]['numSUSP']) ? number_format_i18n($others[$cq]['numSUSP']):'-');
			$tputHr = number_format_i18n($row['tputHOUR']) . ' / ' . (isset($others[$cq]['tputHOUR']) ? number_format_i18n($others[$cq]['tputHOUR']):'-');
			$tput5m = number_format_i18n($row['tput5MIN']) . ' / ' . (isset($others[$cq]['tput5MIN']) ? number_format_i18n($others[$cq]['tput5MIN']):'-');

			form_selectable_cell($pend, $i, '', 'right');
			form_selectable_cell($run, $i, '', 'right');
			form_selectable_cell($susp, $i, '', 'right');
			form_selectable_cell($tputHr, $i, '', 'right');
			form_selectable_cell($tput5m, $i, '', 'right');

			form_selectable_cell($testimate, $i , '', 'right');
			form_selectable_cell($estimate['method'], $i, '', 'center');

			form_selectable_cell($idled, $i, '', 'center');
			form_selectable_cell($slow, $i, '', 'center');
			form_selectable_cell($depend, $i, '', 'center');
			form_selectable_cell($memvio, $i, '', 'center');

			form_end_row();

			$i++;
		}
	} else {
		print '<tr><td colspan="7"><i>' . __('No Records Found', 'heuristics') . '</i></td></tr>';
	}

	print '<tr><td colspan="15">' . __('Estimates are based upon current conditions.  Actual runtime, individual job Resource Requirements, runtime, Fairshare and future job submissions can affect estimates.', 'heuristics') . '</td></tr>';

	html_end_box(false);

	$output = ob_get_clean();

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	return json_encode(array('panel' => $panel, 'output' => $output));
}

function heuristics_trim_project($project, $chars = 20) {
	$agg = read_config_option('grid_project_group_aggregation');

	if ($agg == 'on') {
		$delim = read_config_option('grid_job_stats_project_delimiter');
		$levels = read_config_option('grid_job_stats_project_level_number');

		$parts = explode($delim, $project);

		if (sizeof($parts) > $levels) {
			$project = implode($delim, array_slice($parts, 0, $levels));

			if (strlen($project) > $chars) {
				return substr($project, 0, $chars) . '...';
			} else {
				return $project;
			}
		} elseif (strlen($project) > $chars) {
			return substr($project, 0, $chars) . '...';
		} else {
			return $project;
		}
	} elseif (strlen($project) > $chars) {
		return substr($project, 0, $chars) . '...';
	}

	return $project;
}

/* filter_value_heur - a quick way to highlight text in a table from general filtering
   @arg $text - the string to filter
   @arg $filter - the search term to filter for
   @arg $href - the href if you wish to have an anchor returned
   @arg $title - augment the the url class
   @arg $class - augment the the url class
   @arg $onClick - add an onclick event
   @returns - the filtered string */
function filter_value_heur($value, $filter, $href = '', $title = '', $class = '', $onclick = '') {
	static $charset;

	if ($charset == '') {
		$charset = ini_get('default_charset');
	}

	if ($charset == '') {
		$charset = 'UTF-8';
	}

	$value =  htmlspecialchars($value, ENT_QUOTES, $charset, false);

	if ($filter != '') {
		$value = preg_replace('#(' . $filter . ')#i', "<span class='filteredValue'>\\1</span>", $value);
	}

	if ($class != '') {
		$class = ' class="' . $class . '"';
	} else {
		$class = ' class="linkEditMain"';
	}

	if ($title != '') {
		$title = ' title="' . $title . '"';
	}

	if ($onclick != '') {
		$onclick = " onClick='" . $onclick . "'";
	}

	if ($href != '') {
		$value = '<a' . $class . $onclick . $title . ' href="' . htmlspecialchars($href, ENT_QUOTES, $charset, false) . '">' . $value  . '</a>';
	}

	return $value;
}

function table_cache_add($key, $data, $timeout = '', $column = '', $direction = '', $limit = '') {
	return table_cache_control('add', $key, $data, $timeout, $column, $direction, $limit);
}

function table_cache_get($key, $column = '', $direction = '', $limit = '') {
	return table_cache_control('get', $key, '', '', $column, $direction, $limit);
}

function table_cache_gettime($key, $column = '', $direction = '', $limit = '') {
	return table_cache_control('gettime', $key, '', '', $column, $direction, $limit);
}

function table_cache_refresh_time($key) {
	$insert_time = table_cache_gettime($key);
	$timeout     = table_cache_control('gettimeout', $key);
	$expire_time = $insert_time + $timeout;
	$delta       = $expire_time - time();

	if ($delta > 0) {
		return get_daysfromtime($delta, true, ' ', DAYS_FORMAT_MEDIUM);
	} else {
		return __('Expired', 'heuristics');
	}
}

function table_cache_prune($prefix = 'htc_') {
	$close_session = false;

	if (session_status() == PHP_SESSION_NONE) {
		session_start();
		$close_session = true;
	}

	if (cacti_sizeof($_SESSION)) {
		foreach($_SESSION as $key => $value) {
			if (strpos($key, $prefix) !== false) {
				//cacti_log('Found Table Cache Key: ' . $key);

				$insert_time = $value['time'];
				$timeout     = $value['timeout'];
				$expire_time = $insert_time + $timeout;
				$delta       = $expire_time - time();

				if ($delta <= 0) {
					//cacti_log('Purging Table Cache for Key: ' . $key);
					kill_session_var($key);
				}
			}
		}
	}

	if ($close_session) {
		session_write_close();
	}
}

function table_cache_control($action, $key, $data = array(), $timeout = '', $column = '', $direction = '', $limit = '') {
	if ($timeout == '') {
		$timeout = 600;
	}

	// Cleanup
	$limit         = str_replace('LIMIT ', '', $limit);
	$column        = strtolower($column);
	$direction     = strtolower($direction);
	$close_session = false;

	$key = $key .
		($column != ''    ? '_' . $column:'') .
		($direction != '' ? '_' . $direction:'') .
		($limit != ''     ? '_' . $limit:'');

	if ($action == 'add') {
		if (session_status() == PHP_SESSION_NONE) {
			$close_session = true;
			session_start();
		}

		$_SESSION['htc_' . $key]['time']      = time();
		$_SESSION['htc_' . $key]['timeout']   = $timeout;
		$_SESSION['htc_' . $key]['data']      = $data;
		$_SESSION['htc_' . $key]['rows']      = cacti_sizeof($data);
		$_SESSION['htc_' . $key]['key']       = $key;
		$_SESSION['htc_' . $key]['column']    = $column;
		$_SESSION['htc_' . $key]['direction'] = $direction;
		$_SESSION['htc_' . $key]['limit']     = $limit;

		if ($close_session) {
			session_write_close();
		}

		return true;
	} elseif ($action == 'get') {
		$now = time();
		$force = get_request_var('force') ? true:false;

		if (!$force) {
			if (isset($_SESSION['htc_' . $key])) {
				$insert_time = $_SESSION['htc_' . $key]['time'];
				$timeout     = $_SESSION['htc_' . $key]['timeout'];
				$expire_time = $insert_time + $timeout;
				$delta       = $expire_time - time();

				if ($delta > 0)  {
					return $_SESSION['htc_' . $key]['data'];
				}
			}
		}

		return 'fetch';
	} elseif ($action == 'purge') {
		if (session_status() == PHP_SESSION_NONE) {
			$close_session = true;
			session_start();
		}

		if (isset($_SESSION['htc_' . $key])) {
			kill_session_var('htc_' . $key);
		}

		if ($close_session) {
			session_write_close();
		}

		return true;
	} elseif ($action == 'gettime') {
		if (isset($_SESSION['htc_' . $key])) {
			return $_SESSION['htc_' . $key]['time'];
		} else {
			return time();
		}
	} elseif ($action == 'gettimeout') {
		if (isset($_SESSION['htc_' . $key])) {
			return $_SESSION['htc_' . $key]['timeout'];
		} else {
			return time();
		}
	}
}

function get_idled_jobs($row, $user, &$severity) {
	global $config;

	$start = microtime(true);

	$clusterid = $row['clusterid'];
	$queue     = $row['queue'];
	$project   = $row['projectName'];
	$key       = 'idled_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project;

	$jobs = table_cache_get($key);

	if ($jobs == 'fetch') {
		$jobs = db_fetch_cell_prepared('SELECT count(gj.jobid) FROM grid_jobs AS gj
			INNER JOIN grid_jobs_idled AS gi
			ON gj.clusterid = gi.clusterid
			AND gj.jobid = gi.jobid
			AND gj.indexid = gi.indexid
			AND gj.submit_time = gi.submit_time
			WHERE user = ?
			AND stat = "RUNNING"
			AND `queue` = ?
			AND projectName = ?
			AND gj.clusterid = ?',
			array($user, $queue, $project, $clusterid)
		);

		table_cache_add($key, $jobs);
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	if ($jobs > 0) {
		$severity = 'alarm';

		return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&exception=hogs&status=RUNNING&queue=$queue") . "'><img title='" . __esc('You have %s Idle Job(s) running', $jobs, 'heuristics'). "' src='images/red-ball.png'></a>";
	} else {
		return "<img title='" . __esc('No Idle Jobs found', 'heuristics') . "' src='images/green-ball.png'>";
	}
}

function is_checkout(&$job) {
	global $config;

	if ($job['stat'] == 'PEND' || $job['stat'] == 'PSUSP' || $job['stat'] == 'EXIT' || $job['stat'] == 'DONE') {
		return '';
	} else {
		if (db_table_exists('lic_services')) {
			$checkouts = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM lic_services_feature_details
				WHERE hostname = ?
				AND username = ?',
				array($job['exec_host'], $job['user']));

			if ($checkouts > 0) {
				return "<a class='pic' href='" . html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&filter=&servers=-1&page=1&user=' . $job['user'] . '&host=' . $job['exec_host']) . "'><img src='" . $config['url_path'] . "plugins/license/images/view_checkouts.gif' alt='' title='" . __esc('View License Checkouts', 'heuristics') . "'></a>";
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
}

function is_idled_job(&$job) {
	if ($job['stat'] == 'PEND' || $job['stat'] == 'PSUSP') {
		return "<img title='" . __esc('Job is Pending', 'heuristics') . "' src='images/blue-ball.png'>";
	} else {
		$idle = db_fetch_cell_prepared('SELECT count(*)
			FROM grid_jobs_idled
			WHERE jobid = ?
			AND indexid = ?
			AND submit_time = ?
			AND clusterid = ?',
			array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

		if (!empty($idle)) {
			return "<img title='" . __esc('Idle Job', 'heuristics') . "' src='images/red-ball.png'>";
		} else {
			return "<img title='" . __esc('Not an Idle Job', 'heuristics') . "' src='images/green-ball.png'>";
		}
	}
}

function is_long_job(&$job) {
	$user      = $job['user'];
	$clusterid = $job['clusterid'];
	$queue     = $job['queue'];
	$cpus      = $job['num_cpus'];
	$project   = $job['projectName'];
	$run_time  = $job['run_time'];
	$type      = 'project';

	if ($job['stat'] == 'PEND' || $job['stat'] == 'PSUSP') {
		return "<img title='" . __esc('Job is Pending', 'heuristics') . "' src='images/blue-ball.png'>";
	} else {
		$metrics = db_fetch_row_prepared('SELECT *
			FROM grid_heuristics
			WHERE `queue` = ?
			AND projectName = ?
			AND reqCpus = ?
			AND clusterid = ?',
			array($queue, $project, $cpus, $clusterid));

		if (!cacti_sizeof($metrics)) {
			$metrics = db_fetch_row_prepared('SELECT *
				FROM grid_heuristics
				WHERE `queue` = ?
				AND projectName="-"
				AND reqCpus = ?
				AND clusterid = ?',
				array($queue, $cpus, $clusterid));

			$type = 'queue';
		}

		if (!cacti_sizeof($metrics)) {
			return "<img title='" . __esc('No Heuristics Found for This Cluster, Queue and Project combination', 'heuristics') . "' src='images/blue-ball.png'>";
		} else {
			if ($run_time < $metrics['run_75thp']) {
				return "<img title='" . __esc('Job is Running under the 75th Percentile based upon %s Heuristics', ($type == 'project' ? __('Project', 'heuristics'):__('Queue', 'heuristics')), 'heuristics') . "' src='images/green-ball.png'>";
			} elseif ($run_time >= $metrics['run_75thp'] && $run_time < $metrics['run_90thp']) {
				return "<img title='" . __esc('Job is Running longer than the 75th Percentile based upon %s Heuristics', ($type == 'project' ? __('Project', 'heuristics'):__('Queue', 'heuristics')), 'heuristics') . "' src='images/yellow-ball.png'>";
			} elseif ($run_time >= $metrics['run_90thp']) {
				return "<img title='" . __('Job is Running longer than the 90th Percentile based upon %s Heuristics', ($type == 'project' ? __('Project', 'heuristics'):__('Queue', 'heuristics')), 'heuristics') . "' src='images/red-ball.png'>";
			}
		}
	}
}

function is_pend_depend_job(&$job) {
	$depend = db_fetch_cell_prepared('SELECT pendReasons
		FROM grid_jobs
		WHERE jobid = ?
		AND indexid = ?
		AND submit_time = ?
		AND clusterid = ?',
		array($job['jobid'], $job['indexid'], $job['submit_time'], $job['clusterid']));

	if (preg_match('/Job dependency condition not satisfied/', $depend)) {
		return "<img title='" . __esc('Job is Pending on Dependency', 'heuristics') . "' src='images/blue-ball.png'>";
	} elseif (preg_match('/Dependency condition invalid/', $depend)) {
		return "<img title='" . __esc('Invalid Dependencies Found', 'heuristics') . "' src='images/red-ball.png'>";
	} else {
		return "<img title='" . __esc('No Current Dependencies', 'heuristics') . "' src='images/green-ball.png'>";
	}
}

function is_memvio_job(&$job) {
	if ($job['stat'] == 'PEND' || $job['stat'] == 'PSUSP') {
		return "<img title='" . __esc('Job is Pending', 'heuristics') . "' src='images/blue-ball.png'>";
	} else {
		$type = check_job_memvio ($job['max_memory'], $job['mem_reserved'], $job['run_time']);

		if ($type == 0) {
			return "<img title='" . __esc('No Memory Exception', 'heuristics') . "' src='images/green-ball.png'>";
		} elseif ($type == 1) {
			return "<img title='" . __esc('Memory Over Usage Reported', 'heuristics') . "' src='images/red-ball.png'>";
		} elseif ($type == 2) {
			return "<img title='" . __esc('Memory Under Usage Reported', 'heuristics') . "' src='images/yellow-ball.png'>";
		}
	}
}

function get_memvio_jobs($row, $user, &$severity) {
	global $config;

	$start = microtime(true);

	$clusterid = $row['clusterid'];
	$queue     = $row['queue'];
	$project   = $row['projectName'];

	$key1 = 'memvio_over_'  . $user . '_' . $clusterid . '_' . $queue . '_' . $project;
	$key2 = 'memvio_unser_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project;

	$vio = table_cache_get($key1);

	if ($vio == 'fetch') {
		$vio = db_fetch_cell_prepared("SELECT COUNT(jobid)
			FROM grid_jobs
			WHERE user = ?
			AND stat = 'RUNNING'
			AND `queue` = ?
			AND projectName = ?
			AND max_memory > ?
			AND run_time > ?
			AND mem_reserved > 0
			AND max_memory > (1+" . read_config_option('gridmemvio_overage') . ")*mem_reserved
			AND clusterid = ?",
			array($user, $queue, $project, read_config_option('gridmemvio_min_memory'), read_config_option('gridmemvio_window'), $clusterid));

		table_cache_add($key1, $vio);
	}

	if ($vio) {
		$severity = 'alarm';

		$end = microtime(true);
		//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

		return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&exception=memvio&status=RUNNING&queue=$queue") . "'><img title='" . __('You have %s Job(s) that are Using Excessive Memory', $vio) . "' src='images/red-ball.png'></a>";
	} else {
		$vio = table_cache_get($key2);

		if ($vio == 'fetch') {
			$vio = db_fetch_cell_prepared("SELECT COUNT(jobid)
				FROM grid_jobs
				WHERE user = ?
				AND stat = 'RUNNING'
				AND `queue` = ?
				AND projectName = ?
				AND max_memory > ?
				AND run_time > ?
				AND mem_reserved > 0
				AND max_memory < (1-" . read_config_option('gridmemvio_us_allocation') . ")*mem_reserved
				AND clusterid = ?",
				array($user, $queue, $project, read_config_option('gridmemvio_min_memory'), read_config_option('gridmemvio_window'), $clusterid));

			table_cache_add($key2, $vio);
		}

		if ($vio) {
			$severity = 'warn';

			$end = microtime(true);
			//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

			return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&exception=memviou&status=RUNNING&queue=$queue") . "'><img title='" . __esc('You have %s Job(s) that are Under Using Memory', $vio) ."' src='images/yellow-ball.png'></a>";
		} else {
			$end = microtime(true);
			//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

			return "<img title='" . __esc('No Memory Violations Found', 'heuristics') . "' src='images/green-ball.png'>";
		}
	}
}

function get_depend_jobs($row, $user, &$severity) {
	global $config;

	$clusterid = $row['clusterid'];
	$queue     = $row['queue'];
	$project   = $row['projectName'];

	$key = 'depend_jobs_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project;

	$jobs = table_cache_get($key);

	if ($jobs == 'fetch') {
		$jobs = db_fetch_cell_prepared("SELECT COUNT(gj.jobid)
			FROM grid_jobs AS gj
			WHERE user = ?
			AND stat = 'PEND'
			AND dependCond != ''
			AND `queue` = ?
			AND projectName = ?
			AND gj.clusterid = ?",
			array($user, $queue, $project, $clusterid));

		table_cache_add($key, $jobs);
	}

	if ($jobs > 0) {
		$key = 'inv_depend_jobs_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project;

		$invalid = table_cache_get($key);

		if ($invalid == 'fetch') {
			$invalid = db_fetch_cell_prepared("SELECT COUNT(gj.jobid)
				FROM grid_jobs AS gj
				WHERE user = ?
				AND stat = 'PEND'
				and dependCond != ''
				AND pendReasons LIKE '%invalid or never satisfied%'
				AND projectName = ?
				AND gj.clusterid = ?",
				array($user, $project, $clusterid));

			table_cache_add($key, $invalid);
		}

		if ($invalid > 0) {
			$severity = 'alarm';

			return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&exception=invdep&status=PEND&queue=$queue") . "'><img title='" . __esc('%s out of your %s Dependent Pending Jobs have Invalid Dependency Contintions', $invalid, $jobs, 'heuristics') . "' src='images/red-ball.png'></a>";
		} else {
			if ($severity != 'alarm' && $severity != 'warn') {
				$severity = 'notice';
			}

			return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&exception=dep&status=PEND&queue=$queue") . "'><img title='" . __esc('You have %s Pending Dependent Job(s)', $jobs, 'heuristics') . "' src='images/blue-ball.png'></a>";
		}
	} else {
		return "<img title='" . __esc('No dependent jobs found', 'heuristics') . "' src='images/green-ball.png'>";
	}
}

function get_long_jobs($row, $user, &$severity) {
	global $config;

	$clusterid = $row['clusterid'];
	$queue     = $row['queue'];
	$project   = $row['projectName'];
	$cpus      = $row['reqCpus'];

	$key1 = 'log_jobs_maxrun_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project . '_' . $cpus;
	$key2 = 'log_jobs_expoected_' . $user . '_' . $clusterid . '_' . $queue . '_' . $project . '_' . $cpus;

	$max_run = table_cache_get($key1);

	if ($max_run == 'fetch') {
		$max_run = db_fetch_cell_prepared("SELECT MAX(run_time)
			FROM grid_jobs
			WHERE user = ?
			AND stat = 'RUNNING'
			AND `queue` = ?
			AND num_cpus = ?
			AND projectName = ?
			AND clusterid = ?",
			array($user, $queue, $cpus, $project, $clusterid));

		table_cache_add($key1, $max_run);
	}

	$expected = table_cache_get($key2);

	if ($expected == 'fetch') {
		$expected = db_fetch_cell_prepared("SELECT *
			FROM grid_heuristics
			WHERE `queue`=?
			AND projectName=?
			AND reqCpus=?
			AND resReq='-'
			AND clusterid=?",
			array($queue, $project, $cpus, $clusterid));

		table_cache_add($key2, $expected);
	}

	if (cacti_sizeof($expected)) {
		if ($max_run > $expected['run_90thp']) {
			if ($severity != 'alarm') {
				$severity = 'alarm';
			}

			return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&status=RUNNING&queue=$queue&force_sort=1&sort_column=run_time&sort_direction=DESC") . "'><img title='" . __esc('Your Job with a max runtime of %s, is above the 90th Percentils of %s for Jobs from the Queue/Project', display_job_time($max_run), display_job_time($expected['run_90thp']), 'heuristics') . "' src='images/red-ball.png'></a>";
		} elseif ($max_run > $expected['run_75thp']) {
			if ($severity == '' || $severity == 'notice') {
				$severity = 'warn';
			}

			return "<a class='pic' href='" . html_escape($config['url_path'] . "plugins/heuristics/heuristics_jobs.php?action=viewlist&reset=true&job_user=$user&clusterid=$clusterid&project=$project&status=RUNNING&queue=$queue&force_sort=1&sort_column=run_time&sort_direction=DESC") . "'><img title='" . __esc('Your Job with a max runtime of %s, is above the 75th Percentils of %s for Jobs from this Queue/Project', display_job_time($max_run), display_job_time($expected['run_75thp']), 'heuristics') . "' src='images/yellow-ball.png'></a>";
		} else {
			return "<img title='" . __esc('Your Job with a max runtime of %s, has not exceeded the 75th Percentils of %s for jobs from this Queue/Project', display_job_time($max_run), display_job_time($expected['run_median']), 'heuristics') . "' src='images/green-ball.png'>";
		}
	} else {
		if ($severity == '') {
			$severity = 'notice';
		}

		return "<img title='" . __esc('Your Job with a max runtime of %s.  There is no historiacl data for these job types, so we canno determine if there are long running jobs.', display_job_time($max_run), 'heuristics') . "' src='images/blue-ball.png'>";
	}
}

function get_estimate(&$record, $ahead, &$others) {
	$start = microtime(true);

	$c     = $record['clusterid'];
	$q     = $record['queue'];
	$p     = $record['projectName'];
	$cq    = $record['clustername'] . '|' . $q . '|' . $p;
	$cpu   = $record['reqCpus'];
	$ahead = number_format_i18n((float)$ahead);

	// Method1: Velocity Based Estimate
	$fivm_tp     = $record['tput5MIN'] * 12;
	$oth_fivm_tp = (isset($others[$cq]['tput5MIN']) ? $others[$cq]['tput5MIN'] * 12:'0');
	$tot_fivm_tp = $fivm_tp + $oth_fivm_tp;

	$hr_tp       = $record['tputHOUR'];
	$oth_hr_tp   = (isset($others[$cq]['tputHOUR']) ? $others[$cq]['tputHOUR']:'0');
	$tot_hr_tp   = $hr_tp + $oth_hr_tp;

	/* calculate the estimate based upon 5 minute tput */
	if (!empty($oth_fiv_mtp) && $record['numPEND'] > 0) {
		$efm = (float)(($ahead + $record['numRUN']) / ($tot_fivm_tp)) + (float)($record['numPEND'] / ($tot_fivm_tp));
	} elseif (!empty($fivm_tp) && $record['numPEND'] > 0) {
		$efm = (float)($record['numRUN'] / $tot_fivm_tp) + (float)($record['numPEND'] / ($tot_fivm_tp));
	} else {
		$efm = 0;
	}

	/* calculate the estimate based upon 1 hour tput */
	if (!empty($oth_hr_tp) && $record['numPEND'] > 0) {
		$ehr = (float)(($ahead + $record['numRUN']) / ($tot_hr_tp)) + (float)($record['numPEND'] / ($tot_hr_tp));
	} elseif (!empty($hr_tp) && $record['numPEND'] > 0) {
		$ehr = (float)($record['numRUN'] / $tot_hr_tp) + (float)($record['numPEND'] / ($tot_hr_tp));
	} else {
		$ehr = 0;
	}

	if ($efm != 0 || $ehr != 0) {
		if ($efm != 0 && $ehr != 0) {
			if ($efm < $ehr) {
				return array('estimate' => '~ ' . round($efm,1), 'method' => 'Vel-JP5M', 'units' => 'numeric');
			} else {
				return array('estimate' => '~ ' . round($ehr,1), 'method' => 'Vel-JPH', 'units' => 'numeric');
			}
		} else {
			if ($efm > 0) {
				return array('estimate' => '~ ' . round($efm,1), 'method' => 'Vel-JP5M', 'units' => 'numeric');
			} else {
				return array('estimate' => '~ ' . round($ehr,1), 'method' => 'Vel-JPH', 'units' => 'numeric');
			}
		}
	}

	//Method2: Huristics JPH by Queue and Project
	$heuristics = db_fetch_row_prepared("SELECT *
		FROM grid_heuristics
		WHERE clusterid = ?
		AND `queue` = ?
		AND projectName = ?
		AND reqCpus = ?
		AND resReq = '-'
		AND jph_avg > 0
		AND jph_3std > 0",
		array($c, $q, $p, $cpu));

	if (cacti_sizeof($heuristics)) {
		if (!empty($heuristics['jph_avg']) && !empty($heuristics['jph_3std'])) {
			$estimate_avg   = round(($ahead + $record['numPEND']) / $heuristics['jph_avg'],2);
			$estimate_long  = round(($ahead + $record['numPEND']) / $heuristics['jph_3std'],2);

			if ($estimate_long > (4*24)) {
				return array('estimate' => '> 4 Days', 'method' => 'Heur-QP-JPH', 'units' => 'text');
			} else {
				if ($estimate_avg > 5) {
					$around = 0;
				} elseif ($estimate_avg > 1) {
					$around = 1;
				} else {
					$around = 2;
				}

				return array('estimate' => '~ ' . round($estimate_avg,$around) . '-' . round($estimate_long,$around), 'method' => 'Heur-QP-JPH', 'units' => 'numeric');
			}
		}
	}

	//Method2: Huristics JPH by Queue and Project
	$heuristics = db_fetch_row_prepared("SELECT AVG(jph_avg) AS jph_avg, AVG(jph_3std) AS jph_3std
		FROM grid_heuristics
		WHERE clusterid = ?
		AND `queue` = ?
		AND reqCpus = ?
		AND resReq = '-'
		AND jph_avg > 0
		AND jph_3std > 0",
		array($c, $q, $cpu));

	if (cacti_sizeof($heuristics)) {
		if (!empty($heuristics['jph_avg']) && !empty($heuristics['jph_3std'])) {
			$estimate_avg   = round(($ahead + $record['numPEND']) / $heuristics['jph_avg'],2);
			$estimate_long  = round(($ahead + $record['numPEND']) / $heuristics['jph_3std'],2);
			if ($estimate_long > (4*24)) {
				return array('estimate' => '> 4 Days', 'method' => 'Heur-Q-JPH', 'units' => 'text');
			} else {
				if ($estimate_avg > 5) {
					$around = 0;
				} elseif ($estimate_avg > 1) {
					$around = 1;
				} else {
					$around = 2;
				}

				return array('estimate' => '~ ' . round($estimate_avg,$around) . '-' . round($estimate_long,$around), 'method' => 'Heur-Q-JPH', 'units' => 'numeric');
			}
		}
	}

	//Method3: Huristics Magic
	$e1=$e1_num=0;
	$e3=$e3_num=0;

	// If there is no queue, then error out
	$cur_qp = db_fetch_cell_prepared('SELECT priority
		FROM grid_queues
		WHERE clusterid = ?
		AND queuename = ?',
		array($c, $q));

	if (empty($cur_qp)) {
		return array(
			'estimate' => "<font style='color:red;'>&infin;</font>",
			'method' => 'Undef',
			'units' => 'text'
		);
	}

	// Grap all queues with greater or equal priority
	$queues = db_fetch_assoc_prepared('SELECT queuename, priority, pendjobs, runjobs, numslots, sharedSlots
		FROM grid_queues
		WHERE clusterid = ?
		AND queuename != ?
		AND priority >= ?',
		array($c, $q, $cur_qp));

	if (cacti_sizeof($queues)) {
		foreach($queues as $queue) {
			$other_q   = $queue['queuename'];
			$run_75thp = db_fetch_cell_prepared('SELECT AVG(run_75thp)
				FROM grid_heuristics
				WHERE clusterid = ?
				AND `queue` = ?',
				array($c, $other_q));

			if ($run_75thp == 0) continue;

			if ($queue['priority'] == $cur_qp && $queue['sharedSlots'] > 0) {
				$e3_num++;
				$e3 += $queue['pendjobs'] * $run_75thp / $queue['sharedSlots'];
			}
			if ($queue['priority'] > $cur_qp && $queue['sharedSlots'] > 0 && $queue['numslots'] > 0) {
				$e1_num++;
				$e1 += ($queue['pendjobs'] * $run_75thp / $queue['sharedSlots'] + $queue['runjobs'] * $run_75thp / $queue['numslots']);
			}
		}
	}

	$fe = (($e3_num>0? $e3/$e3_num:0) + ($e1_num>0? $e1/$e1_num:0))/3600;

	return array('estimate' => '~ ' . round($fe,3), 'method' => 'Heur-QG-75h', 'units' => 'numeric');
}

function get_pend_ahead($record, $user) {
	$c = $record['clusterid'];
	$q = $record['queue'];

	$userdp = db_fetch_cell_prepared('SELECT priority
		FROM grid_queues_shares
		WHERE user_or_group = ?
		AND clusterid = ?
		AND `queue` = ?',
		array($user, $c, $q));

	if ($record['numPEND'] == 0) {
		$ahead = 'NA';
	} elseif (strlen($userdp)) {
		$ahead = db_fetch_cell_prepared('SELECT SUM(pendJobs)
			FROM grid_queues_users_stats AS gqus
			INNER JOIN grid_queues_shares AS gqs
			ON gqus.clusterid = gqs.clusterid
			AND gqus.queue = gqs.queue
			AND gqus.user_or_group=gqs.user_or_group
			WHERE priority >= ?
			AND gqus.queue = ?
			AND gqus.user_or_group != ?
			AND gqus.clusterid = ?',
			array($userdp, $q, $user, $c));
	} else {
		$ahead = 'FCFS';
	}

	return $ahead;
}

function build_health_display_array() {
	$display_text = array();

	$display_text += array(
		array('display' => __('Cluster', 'heuristics'),        'align' => 'left'),
		array('display' => __('Cluster Status', 'heuristics'), 'align' => 'left'),
		array('display' => __('RTM Status', 'heuristics'),     'align' => 'left'),
		array('display' => __('Master Status', 'heuristics'),  'align' => 'left'),
		array('display' => __('Active Alerts', 'heuristics'),  'align' => 'center'),
		array('display' => __('Down Hosts', 'heuristics'),     'align' => 'center'),
		array('display' => __('Throughput', 'heuristics'),     'align' => 'center')
	);

	return $display_text;
}

function heuristics_get_exit_code($status) {
	if ($status >> 8 & 0xFF) {
		$exit_status = $status >> 8 & 0xFF;
		$type = __('App Exited, Code', 'heuristics');
	} else {
		$exit_status = $status & 0x7F;
		$type = __('Signal', 'heuristics');
	}

	if ($exit_status == 0) {
		return __('Exit: 0, Likely Preexec Retry Failure', 'heuristics');
	} else {
		return $type . ': ' . $exit_status . ', ';
	}
}

function heuristics_filter() {
	global $grid_refresh_interval, $views, $charts, $heuristics_history;

	?>
	<tr class='odd'>
		<td>
		<form id='form_cluster' action='heuristics.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('User', 'heuristics');?>
					</td>
					<td>
						<input type='text' id='user_iq' size='9' maxlength='20' value='<?php print get_request_var('user_iq');?>'>
					</td>
					<td>
						<?php print __('Cluster', 'heuristics');?>
					</td>
					<td>
						<select id='clusterid' onChange='filterChange()'>
							<option value='-1'<?php if (get_request_var('clusterid') == '-1') print ' selected';?>><?php print __('All', 'heuristics');?></option>
							<?php
							if (isempty_request_var('user_iq')) {
								$clusters = db_fetch_assoc('SELECT DISTINCT h.clusterid, clustername
									FROM grid_heuristics_user_stats h, grid_clusters c
									WHERE h.clusterid=c.clusterid
									ORDER BY h.clusterid');
							} else {
								$clusters = db_fetch_assoc_prepared('SELECT DISTINCT h.clusterid, clustername
									FROM grid_heuristics_user_stats h, grid_clusters c
									WHERE h.clusterid=c.clusterid
									AND h.user=?
									ORDER BY h.clusterid', array(get_request_var('user_iq')));
							}

							if (cacti_sizeof($clusters)) {
								foreach($clusters as $c) {
									print "<option value='" . $c['clusterid'] . "'"; if (get_filter_request_var('clusterid') == $c['clusterid']) { print ' selected'; } print '>' . html_escape($c['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Queue', 'heuristics');?>
					</td>
					<td>
						<select id='queue' onChange='filterChange()'>
							<option value='-1' <?php if (get_request_var('queue') == '-1') print ' selected';?>>All</option>
							<?php
							if (get_request_var('clusterid')>0) {
								$queues = db_fetch_assoc_prepared('SELECT DISTINCT queue
									FROM grid_jobs
									WHERE clusterid=?
									ORDER BY queue', array(get_request_var('clusterid')));
							} else {
								$queues = db_fetch_assoc('SELECT DISTINCT queue
									FROM grid_jobs
									ORDER BY queue');
							}

							if (cacti_sizeof($queues)) {
								foreach($queues as $q) {
									print "<option value='" . $q['queue'] . "'"; if (get_nfilter_request_var('queue') == $q['queue']) { print ' selected'; } print '>' . html_escape($q['queue']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='button' id='go' class='ui-button ui-corner-all ui-widget' value='<?php print __esc('Go', 'heuristics');?>' title='<?php print __esc('Refresh all Views and Charts', 'heuristics');?>' onClick='filterChange()'>
							<input type='button' id='clear' class='ui-button ui-corner-all ui-widget' title='<?php print __esc('Revert to My Last Saved Settings', 'heuristics');?>' value='<?php print __esc('Clear', 'heuristics');?>' onClick='filterClear()'>
							<input type='button' id='save' class='ui-button ui-corner-all ui-widget' title='<?php print __esc('Save all Filter Settings', 'heuristics');?>' value='<?php print __esc('Save', 'heuristics');?>' onClick='filterSave()'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Severity', 'heuristics');?>
					</td>
					<td>
						<select id='severity' onChange='severityChange()'>
							<option value='-1' <?php if (get_request_var('severity') == '-1') print ' selected';?>><?php print __('All', 'heuristics');?></option>
							<option value='notice' <?php if (get_request_var('severity') == 'notice') print ' selected';?>><?php print __('Notice+', 'heuristics');?></option>
							<option value='warn' <?php if (get_request_var('severity') == 'warn') print ' selected';?>><?php print __('Warning+', 'heuristics');?></option>
							<option value='alarm' <?php if (get_request_var('severity') == 'alarm') print ' selected';?>><?php print __('Alarm', 'heuristics');?></option>
						</select>
					</td>
					<td>
						<?php print __('History', 'heuristics');?>
					</td>
					<td>
						<select id='timespan' onChange='filterChange()'>
							<?php
							foreach($heuristics_history as $time => $name) {
								print "<option value='" . $time . "'"; if (get_nfilter_request_var('timespan') == $time) { print ' selected'; } print '>' . $name . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Limit', 'heuristics');?>
					</td>
					<td>
						<select id='limit' onChange='filterChange()'>
							<option value='-1' <?php if (get_request_var('limit') == '-1') print ' selected';?>><?php print __('All', 'heursitcs');?></option>
							<option value='5' <?php if (get_request_var('limit') == '5') print ' selected';?>><?php print __('%d Rows', 5, 'heuristics');?></option>
							<option value='10' <?php if (get_request_var('limit') == '10') print ' selected';?>><?php print __('%d Rows', 10, 'heuristics');?></option>
							<option value='20' <?php if (get_request_var('limit') == '20') print ' selected';?>><?php print __('%d Rows', 20, 'heuristics');?></option>
						</select>
					</td>
					<td>
						<?php print __('Refresh', 'heuristics');?>
					</td>
					<td>
						<select id='refresh' onChange='filterChange()'>
							<?php
							$max_refresh = read_config_option('grid_minimum_refresh_interval');
							if (cacti_sizeof($grid_refresh_interval)) {
								foreach($grid_refresh_interval as $key => $value) {
									if ($key >= $max_refresh) {
										print '<option value="' . $key . '"'; if (get_request_var('refresh') == $key) { print ' selected'; } print '>' . $value . '</option>';
									}
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
						<?php print __('Views', 'heuristics');?>
					</td>
					<td>
						<select id='views' style='display:none;' multiple='multiple' onChange='viewChange()'>
							<?php foreach($views as $id => $view) print "<option value='$id' " . (get_request_var($id) == 'true' ? ' selected':'') . '>' . $view['name'] . '</option>'; ?>
						</select>
					</td>
					<td>
						<?php print __('Charts', 'heuristics');?>
					</td>
					<td>
						<select id='charts' style='display:none;' multiple='multiple' onChange='chartChange()'>
							<?php foreach($charts as $id => $chart) print "<option value='$id' " . (get_request_var($id) == 'true' ? ' selected':'') . '>' . $chart['name'] . '</option>'; ?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php
}

function heuristics_process_input_variables() {
	global $views, $charts;

	/* ================= input validation and session storage ================= */
	input_validate_input_regex_xss_attack(get_request_var('resreq'));
	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_setting('clusterid', '-1')
			),
		'limit' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => get_user_setting('limit', '5')
			),
		'refresh' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('refresh', '60'),
			'pageset' => true
			),
		'timespan' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => get_user_setting('timespan', '14400'),
			'pageset' => true
			),
		'user_iq' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('user_iq', heuristics_set_default_user())
			),
		'queue' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_setting('queue', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'severity' => array(
			'filter' => FILTER_CALLBACK,
			'default' => get_user_setting('severity', '-1'),
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true
			),
		'summary' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('summary', 'true')
			),
		'queues' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('queues', 'true')
			),
		'tput' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('tput', 'true')
			),
		'checkouts' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('checkouts', 'true')
			),
		'pendr' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('pendr', 'true')
			),
		'exitanal' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('exitanal', 'true')
			),
		'health' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('health', 'true')
			),
		'order' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('order', 'summary queues tput checkouts pendr exitanal')
			),
		'memslots' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('memslots', 'true')
			),
		'memhist' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('memhist', 'true')
			),
		'runhist' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('runhist', 'true')
			),
		'jobstats' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('jobstats', 'true')
			),
		'timestats' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('timestats', 'true')
			),
		'corder' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('corder', 'jobstats memhist timestats runhist memslots')
			),
		'charts' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('charts', 'true')
			),
		'tab' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
			),
		'show_project' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '0'
			),
		'show_queue' => array(
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => '0'
			),
		'excluded' => array(		//show excluded pending reasons in pending reasons view
			'filter' => FILTER_CALLBACK,
			'options' => array('options' => 'sanitize_search_string'),
			'pageset' => true,
			'default' => get_user_setting('excluded', 'true')
			)
	);

	validate_store_request_vars($filters, 'sess_heur');
	/* ==================================================== */

	remove_graphs_from_session();

	/* let's construct an array for building charts */
	$graphs = '';
	if (!empty($_SESSION['sess_heur_user_graphs']) && cacti_sizeof($_SESSION['sess_heur_user_graphs'])) {
		$graphs = array_keys($_SESSION['sess_heur_user_graphs']);
		$ng = '';
		foreach($graphs as $graph) {
			$stuff = explode('|', $graph);
			$ng .= (strlen($ng) ? ',"':'"') . $graph . '"';
		}
		$graphs = $ng;
	}

	return $graphs;
}

function get_user_setting($setting, $default) {
	$settings = read_grid_config_option('grid_heuristics', true);
	if (!empty($settings)) {
		$setarray = explode("|", $settings);
		if (cacti_sizeof($setarray)) {
			foreach ($setarray as $s) {
				$iset = explode("=", $s);
				if ($iset[0] == $setting) {
					return $iset[1];
				}
			}
		}
	}

	return $default;
}

function heuristics_ajax_save() {
	global $views, $charts;

	// Filter Settings
	$settings =
		'clusterid='. get_request_var('clusterid'). '|' .
		'user_iq='  . get_request_var('user_iq')  . '|' .
		'queue='    . get_request_var('queue')    . '|' .
		'severity=' . get_request_var('severity') . '|' .
		'limit='    . get_request_var('limit')    . '|' .
		'refresh='  . get_request_var('refresh')  . '|' .
		'timespan=' . get_request_var('timespan') . '|';

	foreach($views as $id => $view) {
		if (isset_request_var($id)) {
			$settings .= "$id=" . get_request_var($id) . '|';
		}
	}

	foreach($charts as $id => $chart) {
		if (isset_request_var($id)) {
			$settings .= "$id=" . get_request_var($id) . '|';
		}
	}

	$settings .=
		'charts=' . 'true'                          . '|' .
		'order='  . trim(get_request_var('order'))  . '|' .
		'corder=' . trim(get_request_var('corder'));

	set_grid_config_option('grid_heuristics', $settings);
}

function build_heuristics_db_union($sql_where, $add_columns, $timespan) {
	/* limit to 7 days */
	if ($timespan > 604800) {
		$timespan = 604800;
	}

	$columns = array('clusterid', 'jobid', 'indexid', 'submit_time', 'end_time', 'start_time', 'stat', 'user', 'queue', 'projectName');
	if (cacti_sizeof($add_columns)) {
		foreach($add_columns as $column) {
			if (!in_array($column, $columns)) {
				$columns[] = $column;
			}
		}
	}

	$coldata = implode(', ', $columns);

	if (read_config_option('grid_partitioning_enable') == 'on') {
		$table_name = 'SELECT * FROM (';

		$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', time() - $timespan), date('Y-m-d H:i:s'));
		if (cacti_sizeof($tables)) {
			$i = 0;
			foreach($tables as $table) {
				$table_name .= ($i > 0 ? ' UNION ':'') . "SELECT $coldata FROM $table $sql_where AND end_time>='" . date('Y-m-d H:i:s', time() - $timespan) . "'";
				$i++;
			}
		}

		$table_name .= ') as gjf';
	} else {
		$table_name = "SELECT $coldata FROM grid_jobs_finished $sql_where AND end_time>='" . date("Y-m-d H:i:s", time() - $timespan) . "'";
	}

	return $table_name;
}

function today_pie() {
	global $config;

	$start = microtime(true);

	// Handle Time Ranges
	$time       = time() - get_request_var('timespan');
	$dend_time  = date('m-d H:i', $time);
	$end_time   = date('Y-m-d H:i:s', $time);

	$key1 = 'today_finished_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');
	$key2 = 'today_active_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');

	$sql_where = '';
	if (get_request_var('user_iq') != '-1') {
		$sql_where = ' WHERE user=' . db_qstr(get_request_var('user_iq'));
	}

	if (get_request_var('clusterid') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':' WHERE') . ' clusterid=' . db_qstr(get_request_var('clusterid'));
	}

	if (get_request_var('queue') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND':'WHERE') . ' `queue` = ' . db_qstr(get_request_var('queue'));
	}

	$finished = table_cache_get($key1);

	if ($finished == 'fetch') {
		$grid_jobs_finished = build_heuristics_db_union($sql_where, array('num_cpus'), get_request_var('timespan'));

		$finished = db_fetch_row_prepared("SELECT
			SUM(CASE WHEN stat='DONE' AND end_time>=? THEN num_cpus ELSE 0 END) AS numDONE,
			SUM(CASE WHEN stat='EXIT' AND end_time>=? THEN num_cpus ELSE 0 END) AS numEXIT,
			SUM(CASE WHEN start_time>=? THEN num_cpus ELSE 0 END) AS numSTART
			FROM ($grid_jobs_finished) AS gjf", array($end_time, $end_time, $end_time));

		table_cache_add($key1, $finished);
	}

	$active = table_cache_get($key2);

	if ($active == 'fetch') {
		$active = db_fetch_row_prepared("SELECT
			SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) AS numPEND,
			SUM(CASE WHEN stat='RUNNING' THEN num_cpus ELSE 0 END) AS numRUN,
			SUM(CASE WHEN stat IN ('PSUSP','USUSP','SSUSP') THEN num_cpus ELSE 0 END) AS numSUSP,
			SUM(CASE WHEN stat='RUNNING' AND start_time>=? THEN num_cpus ELSE 0 END) AS numSTART
			FROM grid_jobs
			$sql_where", array($end_time));

		table_cache_add($key2, $active);
	}

	if (get_request_var('queue') == '-1') {
		$title = 'for All Queues';
	} else {
		$title = 'for Queue &apos;' . get_request_var('queue') . '&apos;';
	}

	if (get_request_var('clusterid') != '-1') {
		$title .= ' in Cluster &apos;' . get_clustername(get_request_var('clusterid')) . '&apos;';
	} else {
		$title .= ' in All Clusters';
	}

	if (!empty($finished['numDONE']) || !empty($finished['numEXIT']) || !empty($active['numPEND']) || !empty($active['numRUN'])) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		print "<chart caption='" . get_user_title() . " Job Statistcs Since &apos;$dend_time&apos; $title' " .
			"palette='2' " .
			"animation='0' " .
			"YAxisName='Jobs' " .
			"showValues='1' " .
			"pieYScale='40' " .
			"pieRadius='100' " .
			"pieSliceDepth='15' " .
			"startingAngle='120' " .
			"numberPrefix='' " .
			"formatNumberScale='0' " .
			"showPercentInToolTip='0' " .
			"showLabels='0' " .
			"showLegend='1' " .
			"$fusion_theme " .
			"exportEnabled='1'>\n";

		print "<set label='Pending'   value='" . $active['numPEND'] . "' isSliced='0' link='j-showJobsByStat-PEND' />\n";
		print "<set label='Running'   value='" . $active['numRUN']  . "' isSliced='0' link='j-showJobsByStat-RUNNING' />\n";
		print "<set label='Suspended' value='" . $active['numSUSP'] . "' isSliced='0' link='j-showJobsByStat-SUSP' />\n";
		print "<set label='Started'   value='" . ($active['numSTART']+$finished['numSTART']) . "' isSliced='0' link='j-showJobsByStat-STARTED' />\n";
		print "<set label='Ended'     value='" . $finished['numDONE'] . "' isSliced='0' link='j-showJobsByStat-DONE' />\n";
		print "<set label='Exited'    value='" . $finished['numEXIT'] . "' isSliced='0' link='j-showJobsByStat-EXIT' />\n";
		print "<styles>\n";
		print "	<definition>\n";
		print "		<style type='font' name='CaptionFont' color='666666' size='12' />\n";
		print "		<style type='font' name='SubCaptionFont' bold='0' site='8' />\n";
		print "	</definition>\n";
		print "	<application>\n";
		print "		<apply toObject='caption' styles='CaptionFont' />\n";
		print "		<apply toObject='SubCaption' styles='SubCaptionFont' />\n";
		print "	</application>\n";
		print "</styles>\n";

		print "</chart>\n";
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');
}

function today_sum_time_pie() {
	$start = microtime(true);

	$time       = time() - get_request_var('timespan');
	$dend_time  = date('m-d H:i', $time);

	// Get id for table check
	$key = 'today_pie_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');

	$finished = table_cache_get($key);

	if ($finished == 'fetch') {
		$sql_where = 'WHERE stat = "DONE" AND run_time > 0';

		if (get_request_var('user_iq') != '-1') {
			$sql_where .= ' AND user = ' . db_qstr(get_request_var('user_iq'));
		}

		if (get_request_var('clusterid') != '-1') {
			$sql_where .= ' AND clusterid = ' . db_qstr(get_request_var('clusterid'));
		}

		if (get_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue` = ' . db_qstr(get_request_var('queue'));
		}

		$grid_jobs_finished = build_heuristics_db_union($sql_where, array(), get_request_var('timespan'));

		$finished = db_fetch_row("SELECT
			SUM(CASE WHEN UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(submit_time) THEN UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(submit_time) ELSE 0 END) AS tt,
			SUM(CASE WHEN UNIX_TIMESTAMP(start_time)>UNIX_TIMESTAMP(submit_time) THEN UNIX_TIMESTAMP(start_time)-UNIX_TIMESTAMP(submit_time) ELSE 0 END) AS pt,
			SUM(CASE WHEN UNIX_TIMESTAMP(end_time)>UNIX_TIMESTAMP(start_time) THEN UNIX_TIMESTAMP(end_time)-UNIX_TIMESTAMP(start_time) ELSE 0 END) AS rt
			FROM ($grid_jobs_finished) AS gjf");

		table_cache_add($key, $finished);
	}

	if (get_request_var('queue') == '-1') {
		$title = 'for All Queues';
	} else {
		$title = 'for Queue &apos;' . get_request_var('queue') . '&apos;';
	}

	if (get_request_var('clusterid') != '-1') {
		$title .= " in Cluster &apos;" . get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " in All Clusters";
	}

	if (!empty($finished['tt']) || !empty($finished['pt']) || !empty($finished['rt']) ) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		print "<chart caption='" . get_user_title() . " Job Time Statistcs Since &apos;$dend_time&apos; for DONE Jobs $title in Minutes ' palette='2' animation='0' YAxisName='Jobs' showValues='1'
			pieYScale='40' pieRadius='110' pieSliceDepth='15' startingAngle='120'
			numberPrefix='' formatNumberScale='0' showPercentInToolTip='0' showLabels='0' showLegend='1' $fusion_theme exportEnabled='1'>\n";

		print "<set label='Pending Time' value='" . round($finished['pt']/60) . "' isSliced='0' />\n";
		print "<set label='Running Time' value='" . round($finished['rt']/60) . "' isSliced='0' />\n";
		print "<set label='Turnaround Time' value='" . round($finished['tt']/60) . "' isSliced='0' />\n";
		print "<styles>\n";
		print "	<definition>\n";
		print "		<style type='font' name='CaptionFont' color='666666' size='12' />\n";
		print "		<style type='font' name='SubCaptionFont' bold='0' site='8' />\n";
		print "	</definition>\n";
		print "	<application>\n";
		print "		<apply toObject='caption' styles='CaptionFont' />\n";
		print "		<apply toObject='SubCaption' styles='SubCaptionFont' />\n";
		print "	</application>\n";
		print "</styles>\n";

		print "</chart>\n";
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');
}

/*remove graph types from the session*/
function remove_graphs_from_session() {
	if (empty($_SESSION['sess_heur_user_graphs'])) return ;
	if (cacti_sizeof($_SESSION['sess_heur_user_graphs'])) {
		$graphs = array_keys($_SESSION['sess_heur_user_graphs']);
		foreach($graphs as $graph) {
			$stuff = explode('|', $graph);
			if (empty($stuff[1])) {  // delete queue graph session data, only need to show project graph
				if (!empty($_SESSION['sess_heur_show_project'])) {
					unset ($_SESSION['sess_heur_user_graphs'][$graph]);
				}
			} else {// delete project graph session data, only need to show queue graph
				if (!empty($_SESSION['sess_heur_show_queue'])) {
					unset ($_SESSION['sess_heur_user_graphs'][$graph]);
				}
			}
		}
	}
}

function draw_queue_stats() {
	heuristics_process_input_variables();

	$sql_where = '';
	$sql_params = array();

	if (get_request_var('queue') != '') {
		$sql_where = 'WHERE `queue` = ?';
		$sql_params[] = get_request_var('queue');
	}

	if (get_request_var('clusterid') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' clusterid = ?';
		$sql_params[] = get_request_var('clusterid');
	}

	$data = db_fetch_assoc_prepared("SELECT
		queuename,
		SUM(IF(maxjobs='-' AND userJobLimit='-', 0, IF(maxjobs='-', userJobLimit, IF(userJobLimit='-', maxjobs, userJobLimit)))) user_max,
		SUM(numslots) AS totslots,
		SUM(openDedicatedSlots) AS open_dedicated,
		SUM(openSharedSlots) AS open_shared,
		SUM(dedicatedSlots) AS free_decicated,
		SUM(sharedSlots) AS free_shared
		FROM grid_queues
		$sql_where
		GROUP BY queuename", $sql_params);
}

function draw_trend_chart() {
	heuristics_process_input_variables();

	$start = microtime(true);

	// id field for cache control
	$key = 'trend_' . get_request_var('metric') . '_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('project') . '_' . get_request_var('timespan');

	$data = table_cache_get($key);

	if ($data == 'fetch') {
		$sql_where = '';

		if (get_request_var('queue') != '') {
			$sql_where = 'WHERE `queue` = ' . db_qstr(get_request_var('queue'));
		}

		if (get_request_var('clusterid') != '') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . ' clusterid = ' . db_qstr(get_request_var('clusterid'));
		}

		switch(get_request_var('metric')) {
		case 'running':
			$case = 'SUM(CASE WHEN user=' . db_qstr(get_request_var('user_iq')) . (strlen(get_request_var('project')) ? ' AND projectName=' . db_qstr(get_request_var('project')):'') . ' THEN numRUN ELSE 0 END) AS numRUN, SUM(CASE WHEN user!=' . db_qstr(get_request_var('user_iq')) . ' THEN numRUN ELSE 0 END) AS otherRUN';

			break;
		case 'pending':
			$case = 'SUM(CASE WHEN user=' . db_qstr(get_request_var('user_iq')) . (strlen(get_request_var('project')) ? ' AND projectName=' . db_qstr(get_request_var('project')):'') . ' THEN numPEND ELSE 0 END) AS numPEND, SUM(CASE WHEN user!=' . db_qstr(get_request_var('user_iq')) . ' THEN numPEND ELSE 0 END) AS otherPEND';

			break;
		case 'finished':
			$case = 'SUM(CASE WHEN user=' . db_qstr(get_request_var('user_iq')) . (strlen(get_request_var('project')) ? ' AND projectName=' . db_qstr(get_request_var('project')):'') . ' THEN tput5MIN ELSE 0 END) AS numDONE, SUM(CASE WHEN user!=' . db_qstr(get_request_var('user_iq')) . ' THEN tput5MIN ELSE 0 END) AS otherDONE';

			break;
		case 'tput':
			$case = 'SUM(CASE WHEN user=' . db_qstr(get_request_var('user_iq')) . (strlen(get_request_var('project')) ? ' AND projectName=' . db_qstr(get_request_var('project')):'') . ' THEN tputHOUR ELSE 0 END) AS tputHOUR, SUM(CASE WHEN user!=' . db_qstr(get_request_var('user_iq')) . ' THEN tputHOUR ELSE 0 END) AS othertputHOUR';

			break;
		}

		/* register the graph type in the session */
		if (!isset($_SESSION['sess_heur_user_graphs'][get_request_var('queue') . '|' . get_request_var('project') . '|' .  get_request_var('clusterid') . '|' . get_request_var('user_iq') . '|' . get_request_var('metric')])) {
			$_SESSION['sess_heur_user_graphs'][get_request_var('queue') . '|' . get_request_var('project') . '|' . get_request_var('clusterid') . '|' . get_request_var('user_iq') . '|' . get_request_var('metric')] = 1;
		}

		if (strlen(get_request_var('project'))) {
			$_SESSION['sess_heur_show_project'] = 1;
			$_SESSION['sess_heur_show_queue'] = 0;
		} else {
			$_SESSION['sess_heur_show_project'] = 0;
			$_SESSION['sess_heur_show_queue'] = 1;
		}

		$time  = time();
		$today = date('z', $time);
		$year  = date('Y', $time);
		$fday  = date('z', $time - get_request_var('timespan'));
		$fyear = date('Y', $time - get_request_var('timespan'));

		// Check for new year in range
		if ($fyear < $year) {
		} else {
			if ($today - $fday == 0) {
				$partitions = array();
			} else {
				while ($fday <= $today) {
					$partitions[] = $year . $fday;
					$fday++;
				}
			}
		}

		switch(get_request_var('timespan')) {
		case '3600':
		case '7200':
		case '14400':
			$group_by = 'GROUP BY last_updated';
			break;
		case '28800':
		case '43200':
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 144)';
			break;
		case '86400': // 1 Day
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 421)';
			break;
		case '172800': // 2 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 864)';
			break;
		case '259200': // 3 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 1296)';
			break;
		case '345600': // 4 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 1728)';
			break;
		case '432000': // 5 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 2160)';
			break;
		case '518400': // 6 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 2592)';
			break;
		case '604800': // 7 Days
			$group_by = 'GROUP BY round(UNIX_TIMESTAMP(last_updated) / 3024)';
			break;
		}

		if (cacti_sizeof($partitions)) {
			$sql = "SELECT *
				FROM (
					SELECT last_updated, $case
					FROM grid_heuristics_user_history_today
					$sql_where
					AND last_updated>='" . date("Y-m-d H:i:s", time()-get_request_var('timespan')) . "'
					$group_by";

			if (cacti_sizeof($partitions)) {
				foreach($partitions as $p) {
					if (cacti_sizeof(db_fetch_row_prepared("SHOW TABLES LIKE ?", array("grid_heuristics_user_history_v". $p)))) {
						$sql .= " UNION SELECT last_updated, $case
						FROM grid_heuristics_user_history_v" . $p . "
						$sql_where
						AND last_updated>='" . date("Y-m-d H:i:s", time()-get_request_var('timespan')) . "'
						$group_by";
					}
				}
			}

			$sql .= ") AS recordset
				ORDER BY last_updated ASC";

			$data = db_fetch_assoc($sql);
		} else {
			$data = db_fetch_assoc("SELECT *
				FROM (
					SELECT last_updated,
					$case
					FROM grid_heuristics_user_history_today
					$sql_where
					AND last_updated>='" . date("Y-m-d H:i:s", time()-get_request_var('timespan')) . "'
					$group_by
				) AS recordset
				ORDER BY last_updated ASC");
		}

		table_cache_add($key, $data);
	}

	switch(get_request_var('metric')) {
		case 'running':
			$myColor     = '00FF00';
			$othersColor = 'EAAF00';
			break;
		case 'pending':
			$myColor = '4123A1';
			$othersColor = 'EAAF00';
			break;
		case 'finished':
			$myColor = '7EE600';
			$othersColor = '942D0C';
			break;
		case 'tput':
			$myColor = '6DC8FE';
			$othersColor = '2E3127';
			break;
	}

	$samples = cacti_sizeof($data);

	$spacing = $samples / 10;

	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	$chartXML = "<chart caption='" . get_graph_name(get_request_var('queue'), get_request_var('project'), get_request_var('metric'), get_request_var('clusterid')) . " (Last " . (get_request_var('timespan') / 3600) . " Hours)' " .
		"yAxisName='Jobs' " .
		"showAnchors='1' " .
		"anchorRadius='2' " .
		"formatNumberScale='1' " .
		"decimalPrecision='3' " .
		"showValues='0' " .
		"showNames='1' " .
		"labelDisplay='none' " .
		"axis='log' " .
		"connectNullData='1' " .
		"logBase='10' " .
		"showLegend='1' " .
		"animation='0' " .
		"showhovercap='1' " .
		"labelStep='" . floor($spacing) . "' " .
		"$fusion_theme " .
		"exportEnabled='1'>";

	$catLabels   = "";
	$dataSeries1 = "";
	$dataSeries2 = "";
	$dataSeries3 = "";
	$dataSeries4 = "";

	$i = 0;
	if (cacti_sizeof($data)) {
		foreach($data as $d) {
			$catLabels .= ($i == 0 ? "<categories>\n":"") . "<category label='" . date("H:i",strtotime($d['last_updated'])) . "' />\n";

			switch(get_request_var('metric')) {
				case 'running':
					$dataSeries1 .= ($i == 0 ? "<dataset seriesName='" . get_user_title() . " Running' color='$myColor' anchorBorderColor='$myColor'>\n":"") . "<set value='" . $d['numRUN'] . "' />\n";
					$dataSeries2 .= ($i == 0 ? "<dataset seriesName='Others Running' color='$othersColor' anchorBorderColor='$othersColor'>\n":"") . "<set value='" . $d['otherRUN'] . "' />\n";
					break;
				case 'pending':
					$dataSeries1 .= ($i == 0 ? "<dataset seriesName='" . get_user_title() . " Pending' color='$myColor' anchorBorderColor='$myColor'>\n":"") . "<set value='" . $d['numPEND'] . "' />\n";
					$dataSeries2 .= ($i == 0 ? "<dataset seriesName='Others Pending' color='$othersColor' anchorBorderColor='$othersColor'>\n":"") . "<set value='" . $d['otherPEND'] . "' />\n";
					break;
				case 'finished':
					$dataSeries1 .= ($i == 0 ? "<dataset seriesName='" . get_user_title() . " Done' color='$myColor' anchorBorderColor='$myColor'>\n":"") . "<set value='" . $d['numDONE'] . "' />\n";
					$dataSeries2 .= ($i == 0 ? "<dataset seriesName='Others Done' color='$othersColor' anchorBorderColor='$othersColor'>\n":"") . "<set value='" . $d['otherDONE'] . "' />\n";
//					$dataSeries3 .= ($i == 0 ? "<dataset seriesName='My Exited' color='$myExitColor' anchorBorderColor='$myExitColor'>\n":"") . "<set value='" . $d['numEXIT'] . "' />\n";
//					$dataSeries4 .= ($i == 0 ? "<dataset seriesName='Others Exited' color='$othersExitColor' anchorBorderColor='$othersExitColor'>\n":"") . "<set value='" . $d['otherEXIT'] . "' />\n";
					break;
				case 'tput':
					$dataSeries1 .= ($i == 0 ? "<dataset seriesName='" . get_user_title() . " Finished/Hour' color='$myColor' anchorBorderColor='$myColor'>\n":"") . "<set value='" . $d['tputHOUR'] . "' />\n";
					$dataSeries2 .= ($i == 0 ? "<dataset seriesName='Others Finished/Hour' color='$othersColor' anchorBorderColor='$othersColor'>\n":"") . "<set value='" . $d['othertputHOUR'] . "' />\n";
					break;
			}
			$i++;
		}
	}

	$catLabels   .= (strlen($catLabels)   ? "</categories>\n":"");
	$dataSeries1 .= (strlen($dataSeries1) ? "</dataset>\n":"");
	$dataSeries2 .= (strlen($dataSeries2) ? "</dataset>\n":"");
	$dataSeries3 .= (strlen($dataSeries3) ? "</dataset>\n":"");
	$dataSeries4 .= (strlen($dataSeries4) ? "</dataset>\n":"");

	$chartXML .= $catLabels . $dataSeries1 . $dataSeries2 . $dataSeries3 . $dataSeries4 . "</chart>\n";

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');

	print $chartXML;
}

function draw_memory_histogram() {
	global $config;

	$start = microtime(true);

	// Handle Time Ranges
	$time       = time() - get_request_var('timespan');
	$dend_time  = date("m-d H:i", $time);

	$key = 'mem_hist_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');

	$hist = table_cache_get($key);

	if ($hist == 'fetch') {
		$sql_where = 'WHERE stat="DONE" AND run_time>0';

		if (get_request_var('clusterid') != '-1') {
			$sql_where .= ' AND clusterid = ' . db_qstr(get_request_var('clusterid'));
		}

		if (get_request_var('user_iq') != '-1') {
			$sql_where .= ' AND user = ' . db_qstr(get_request_var('user_iq'));
		}

		if (get_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue` = ' . db_qstr(get_request_var('queue'));
		}

		$grid_jobs_finished = build_heuristics_db_union($sql_where, array('max_memory', 'num_cpus'), get_request_var('timespan'));

		$sql = "SELECT
			SUM(CASE WHEN max_memory BETWEEN 0 AND 1048576 THEN num_cpus ELSE 0 END) AS C0to1,
			SUM(CASE WHEN max_memory BETWEEN 1048576 AND 2097152 THEN num_cpus ELSE 0 END) AS C1to2,
			SUM(CASE WHEN max_memory BETWEEN 2097152 AND 4194304 THEN num_cpus ELSE 0 END) AS C2to4,
			SUM(CASE WHEN max_memory BETWEEN 4194304 AND 8388608 THEN num_cpus ELSE 0 END) AS C4to8,
			SUM(CASE WHEN max_memory BETWEEN 8388608 AND 16777216 THEN num_cpus ELSE 0 END) AS C8to16,
			SUM(CASE WHEN max_memory BETWEEN 16777216 AND 25165824 THEN num_cpus ELSE 0 END) AS C16to24,
			SUM(CASE WHEN max_memory BETWEEN 25165824 AND 33554432 THEN num_cpus ELSE 0 END) AS C24to32,
			SUM(CASE WHEN max_memory BETWEEN 33554432 AND 67108864 THEN num_cpus ELSE 0 END) AS C32to64,
			SUM(CASE WHEN max_memory>67108864 THEN num_cpus ELSE 0 END) AS C64more
			FROM ($grid_jobs_finished) AS rs";

		//cacti_log(str_replace("\n", '', $sql));

		$hist = db_fetch_row($sql);

		table_cache_add($key, $hist);
	}

	if (get_request_var('queue') == '-1') {
		$title = 'for All Queues';
	} else {
		$title = 'for Queue &apos;' . get_request_var('queue') . '&apos;';
	}

	if (get_request_var('clusterid') != '-1') {
		$title .= ' in Cluster &apos;' . get_clustername(get_request_var('clusterid')) . '&apos;';
	} else {
		$title .= ' in All Clusters';
	}


	if (cacti_sizeof($hist)) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='" . get_user_title() . " Memory Use Since &apos;$dend_time&apos; for DONE Jobs $title' palette='2' animation='0' formatNumberScale='0' numberPrefix='' labeldisplay='ROTATE' showValues='1' slantLabels='1' seriesNameInToolTip='0' sNumberSuffix='' plotSpacePercent='0' labelDisplay='STAGGER' $fusion_theme exportEnabled='1'>\n";
		$chartXML .= "<set label='0G to 1G' value='"   . $hist['C0to1']   . "' link='j-showMemoryUseJobs-1' />\n";
		$chartXML .= "<set label='1G to 2G' value='"   . $hist['C1to2']   . "' link='j-showMemoryUseJobs-2' />\n";
		$chartXML .= "<set label='2G to 4G' value='"   . $hist['C2to4']   . "' link='j-showMemoryUseJobs-3' />\n";
		$chartXML .= "<set label='4G to 8G' value='"   . $hist['C4to8']   . "' link='j-showMemoryUseJobs-4' />\n";
		$chartXML .= "<set label='8G to 16G' value='"  . $hist['C8to16']  . "' link='j-showMemoryUseJobs-5' />\n";
		$chartXML .= "<set label='16G to 24G' value='" . $hist['C16to24'] . "' link='j-showMemoryUseJobs-6' />\n";
		$chartXML .= "<set label='24G to 32G' value='" . $hist['C24to32'] . "' link='j-showMemoryUseJobs-7' />\n";
		$chartXML .= "<set label='32G to 64G' value='" . $hist['C32to64'] . "' link='j-showMemoryUseJobs-8' />\n";
		$chartXML .= "<set label='64G+' value='"       . $hist['C64more'] . "' link='j-showMemoryUseJobs-9' />\n";
		$chartXML .= "<styles>
			<definition>
				<style type='font' name='CaptionFont' color='666666' size='12' />
				<style type='font' name='SubCaptionFont' bold='0' site='8' />
			</definition>
			<application>
				<apply toObject='caption' styles='CaptionFont' />
				<apply toObject='SubCaption' styles='SubCaptionFont' />
			</application>
   	 	</styles>
		</chart>\n";

		print $chartXML;
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');
}

function draw_free_memory_slots() {
	global $config;
	$sql_params = array();

	$start = microtime(true);

	if (get_request_var('queue') == '-1') {
		show_graph_memavastat();
		return;
	} else {
		$sql_where = "WHERE reportid = 'fmemslots' ";
		$sql_where .= 'AND `queue` = ?';
		$sql_params[] = get_request_var('queue');
	}

	if (get_request_var('clusterid') > 0) {
		$sql_where .= 'AND clusterid = ?';
		$sql_params[] = get_request_var('clusterid');
	}

	$fmemslot_columns = array (
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

	$records = db_fetch_assoc_prepared("SELECT * FROM
		grid_clusters_queue_reportdata
		$sql_where", $sql_params);

	if (cacti_sizeof($records)) {
		foreach ($records AS $record) {
			$hist[$record['name']] += $record['value'];
		}
	}

	if (get_request_var('clusterid') > 0) {
		$title = __esc('Free Memory Slots Availability for Cluster \'%s\'', clusterdb_get_clustername(get_request_var('clusterid')), 'heurisitics');
	} else {
		$title = __esc('Free Memory Slots Availability for All Clusters', 'heurisitics');
	}

	if (cacti_sizeof($hist)) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='$title' palette='2' animation='0' formatNumberScale='0' numberPrefix='' labeldisplay='ROTATE' showValues='1' slantLabels='1' seriesNameInToolTip='0' sNumberSuffix='' plotSpacePercent='0' labelDisplay='STAGGER' $fusion_theme exportEnabled='1'>";
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

	if (strlen($chartXML)) {
		print ($chartXML);
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');
}

function draw_runtime() {
	global $config, $heuristics_runtimes;

	$start = microtime(true);

	// Handle Time Ranges
	$time       = time() - get_request_var('timespan');
	$dend_time  = date('m-d H:i', $time);

	$key = 'runtime_' . get_request_var('user_iq') . '_' . get_request_var('clusterid') . '_' . get_request_var('queue') . '_' . get_request_var('timespan');

	$hist = table_cache_get($key);

	if ($hist == 'fetch') {
		$sql_where = "WHERE stat='DONE' AND run_time>0 ";

		if (get_nfilter_request_var('user_iq') != '-1') {
			$sql_where .= ' AND user = ' . db_qstr(get_nfilter_request_var('user_iq'));
		}

		if (get_filter_request_var('clusterid') != '-1') {
			$sql_where .= ' AND clusterid = ' . db_qstr(get_filter_request_var('clusterid'));
		}

		if (get_nfilter_request_var('queue') != '-1') {
			$sql_where .= ' AND `queue` = ' . db_qstr(get_nfilter_request_var('queue'));
		}

		$sql = 'SELECT ';
		if (cacti_sizeof($heuristics_runtimes)) {
			foreach($heuristics_runtimes as $id => $data) {
				if ($id > 0) {
					$sql .= ($id >=2 ? ', ':'') . ' SUM(CASE WHEN ' . substr(trim($data['sql']), 4) . " THEN 1 ELSE 0 END) AS C$id";
				}
			}
		}

		$grid_jobs_finished = build_heuristics_db_union($sql_where, array('run_time'), get_nfilter_request_var('timespan'));

		$sql .= " FROM ($grid_jobs_finished) AS gjf";

		//cacti_log($sql);

		$hist = db_fetch_row($sql);

		table_cache_add($key, $hist);
	}

	if (get_nfilter_request_var('queue') == '-1') {
		$title = 'in All Queues';
	} else {
		$title = 'in Queue &apos;' . get_request_var('queue') . '&apos;';
	}

	if (get_request_var('clusterid') != '-1') {
		$title .= " in Cluster &apos;" . get_clustername(get_request_var('clusterid')) . "&apos;";
	} else {
		$title .= " in All Clusters";
	}


	if (cacti_sizeof($hist)) {
		$fusion_theme = "theme='"  . get_selected_theme() . "'";

		$chartXML = "<chart caption='" . get_user_title() . " RunTime Distribution Since &apos;$dend_time&apos; for DONE Jobs $title' palette='2' animation='0' formatNumberScale='0' numberPrefix='' labeldisplay='ROTATE' showValues='1' slantLabels='1' seriesNameInToolTip='0' sNumberSuffix='' plotSpacePercent='0' labelDisplay='STAGGER' $fusion_theme exportEnabled='1'>\n";
		$chartXML .= "<set label='0M-5M' value='"   . $hist['C1']   . "' link='j-showRunTimeJobs-1' />\n";
		$chartXML .= "<set label='5M-15M' value='"  . $hist['C2']   . "' link='j-showRunTimeJobs-2' />\n";
		$chartXML .= "<set label='15M-30M' value='" . $hist['C3']   . "' link='j-showRunTimeJobs-3' />\n";
		$chartXML .= "<set label='30M-1H' value='"  . $hist['C4']   . "' link='j-showRunTimeJobs-4' />\n";
		$chartXML .= "<set label='1H-2H' value='"   . $hist['C5']   . "' link='j-showRunTimeJobs-5' />\n";
		$chartXML .= "<set label='2H-6H' value='"   . $hist['C6']   . "' link='j-showRunTimeJobs-6' />\n";
		$chartXML .= "<set label='6H-12H' value='"  . $hist['C7']   . "' link='j-showRunTimeJobs-7' />\n";
		$chartXML .= "<set label='12H-1D' value='"  . $hist['C8']   . "' link='j-showRunTimeJobs-8' />\n";
		$chartXML .= "<set label='1D-2D' value='"   . $hist['C9']   . "' link='j-showRunTimeJobs-9' />\n";
		$chartXML .= "<set label='>2D' value='"     . $hist['C10']  . "' link='j-showRunTimeJobs-10' />\n";
		$chartXML .= "<styles>
			<definition>
				<style type='font' name='CaptionFont' color='666666' size='12' />
				<style type='font' name='SubCaptionFont' bold='0' site='8' />
			</definition>
			<application>
				<apply toObject='caption' styles='CaptionFont' />
				<apply toObject='SubCaption' styles='SubCaptionFont' />
			</application>
   	 	</styles>
		</chart>\n";

		print $chartXML;
	}

	$end = microtime(true);

	//cacti_log(__FUNCTION__ . ' took ' . round($end - $start, 2) . ' seconds');
}

function get_graph_name($queue, $project, $metric, $clusterid) {
	switch($metric) {
	case 'running':
		$prefix = __esc('Running for Queue \'%s\'', $queue, 'heuristics');
		break;
	case 'pending':
		$prefix = __esc('Pending for Queue \'%s\'', $queue, 'heuristics');
		break;
	case 'finished':
		$prefix = __esc('Finished for Queue \'%s\'', $queue, 'heuristics');
		break;
	case 'tput':
		$prefix = __esc('Throughput for Queue \'%s\'', $queue, 'heuristics');
		break;
	}

	if (strlen($project)) {
		$prefix .= __esc(' in Project \'%s\'', $project, 'heuristics');
	}

	if (!empty($clusterid) && is_numeric($clusterid)) {
		$prefix .= __esc(' in Cluster \'%s\'', get_clustername($clusterid), 'heurisitics');
	}

	return $prefix;
}

function ajax_search() {
	if (isset_request_var('type')) {
		switch(get_request_var('type')) {
		case "user_iq":
			if (get_request_var('term') != "") {
				$values = db_fetch_assoc_prepared("SELECT DISTINCT user_or_group AS label, user_or_group AS value
					FROM grid_users_or_groups
					WHERE user_or_group LIKE ?
					AND type ='U'
					AND last_updated > now() - interval 1 week
					ORDER BY label
					LIMIT 20", array(get_request_var('term') . '%'));
			} else {
				$values = db_fetch_assoc("SELECT DISTINCT user_or_group AS label, user_or_group AS value
					FROM grid_users_or_groups
					WHERE type ='U'
					AND last_updated > now() - interval 1 week
					ORDER BY label
					LIMIT 20");
			}
			print json_encode($values);

			break;
		}
	}
}

function heuristics_set_default_user() {
	if (read_config_option('grid_set_default_user') == 'on') {
		$username = db_fetch_cell_prepared('SELECT DISTINCT username
			FROM grid_users_or_groups JOIN user_auth ON grid_users_or_groups.user_or_group=user_auth.username
			WHERE user_auth.id = ?',
			array($_SESSION['sess_user_id']));

		if ($username != '') {
			return $username;
		}
	}

	return '';
}

/* heuristics_header_sort - draws a header row suitable for display inside of a box element.  When
	 a user selects a column header, the collback function 'filename' will be called to handle
	 the sort the column and display the altered results.
   @arg $header_items - an array containing a list of column items to display.  The
		format is similar to the html_header, with the exception that it has three
		dimensions associated with each element (db_column => display_text, default_sort_order)
   @arg $sort_column - the value of current sort column.
   @arg $sort_direction - the value the current sort direction.  The actual sort direction
		will be opposite this direction if the user selects the same named column.
   @arg $panel - the id of the div that contains the table to be sorted

   The header items array must confirm to the following format:
	$header[]['name'] => Column Name as it should appear,
	$header[]['dbcolumn'] => Table column to use for sorting,
	$header[]['title'] => Onhover help text,
	$header[]['order'] => Sort Order,
	$header[]['align'] => Text Alignment

   The current sort order for all tables will be saved in a session variable.
*/
function heuristics_header_sort($header, $sort_column, $sort_direction, $panel, $last_item_colspan = 1) {
	/* reverse the sort direction */
	if ($sort_direction == 'ASC') {
		$new_sort_direction = 'DESC';
	} else {
		$new_sort_direction = 'ASC';
	}

	print "<thead><tr class='tableHeader'>" . PHP_EOL;

	$i = 1;
	foreach ($header as $col) {
		/* by default, you will always sort ascending, with the exception of an already sorted column */
		if ($sort_column == $col['dbcolumn']) {
			$direction = $new_sort_direction;
			$text      = $col['name'] . '**';
			$align     = " class='" . (isset($col['align']) ? $col['align']:'left') . "'";
			$title     = (strlen($col['title']) ? "title='" . $col['title'] . "'":'');
		} else {
			$text      = $col['name'];
			$align     = " class='" . (isset($col['align']) ? $col['align']:'left') . "'";
			$direction = (strlen($col['order']) ? $col['order']:$new_sort_direction);
			$title     = (strlen($col['title']) ? "title='" . $col['title'] . "'":'');
		}

		if (($col['dbcolumn'] == '') || (substr_count($col['dbcolumn'], 'nosort'))) {
			print "<th $title $align " . ((($i+1) == count($header)) ? "colspan='$last_item_colspan' " : '') . ">" . PHP_EOL;
			print "<span class='textSubHeaderDark'>" . $text . "</span>" . PHP_EOL;
			print '</th>' . PHP_EOL;
		} else {
			print "<th $title $align " . ((($i) == count($header)) ? "colspan='$last_item_colspan'>" : '>');
			print "<span style='cursor:pointer;display:block;' class='textSubHeaderDark' onClick='sortMe(\"" . $col['dbcolumn'] . "\", \"" . $direction . "\",\"" . $panel . "\")'>" . $text . '</span>';
			print '</th>' . PHP_EOL;
		}

		$i++;
	}

	print '</tr></thead>' . PHP_EOL;
}

/*
 flag=0 only select in finished job table(default)
 flag=1 only select in job table, for show_pending_reasons()
 flag=2 only select in job and finished job table
*/
function heuristics_num_clusters($stats) {
	$previous_clusterid = 0;
	if (cacti_sizeof($stats)) {
		foreach($stats as $row) {
			if($previous_clusterid == 0) {
				if(isset($row['clusterid'])) {
					$previous_clusterid = $row['clusterid'];
				} else {
					cacti_log("Warning: No clusterid in heuristics function.");
				}
			} else if($row['clusterid'] != $previous_clusterid) {
				return 2; //big than 1;
			}
		}
		return 1;
	}
	return 0;
}

function get_clustername($clusterid) {
	static $clusters = array();

	if (!isset($clusters[$clusterid])) {
		$clusters[$clusterid] = db_fetch_cell_prepared('SELECT clustername
			FROM grid_clusters
			WHERE clusterid = ?',
			array($clusterid));
	}

	return $clusters[$clusterid];
}

function get_user_title () {
	if (!isset($_SESSION['current_username'])) {
		$_SESSION['current_username'] = db_fetch_cell_prepared("SELECT username
			FROM user_auth
			WHERE id = ?",
			array($_SESSION['sess_user_id']));
	}

	if ($_SESSION['current_username'] == get_nfilter_request_var('user_iq')) {
		return __esc('My User', 'heuristics');
	} else {
		return __esc('User %s', get_request_var('user_iq'), 'heurisitics');
	}
}
