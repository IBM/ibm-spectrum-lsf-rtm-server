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

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include('./plugins/grid/lib/grid_partitioning.php');
include_once('./plugins/RTM/include/fusioncharts/fusioncharts.php');
include_once($config['library_path'] . '/rtm_functions.php');

$title = __('IBM Spectrum LSF RTM - Pending Reason History', 'gridpend');

if (isset_request_var('getdata')) {
	gridpend_return_xml();
} else {
	gridpend_view_pending();
}

function gridpend_return_xml() {
	$sql_params = array();
	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('clusterid', 'sess_grid_view_clusterid', '-1');
	load_current_session_value('timespan', 'sess_pend_timespan', '1');
	load_current_session_value('measure', 'sess_pend_measure', '1');
	load_current_session_value('type', 'sess_pend_type', '-1');
	load_current_session_value('top', 'sess_pend_top', '10');
	load_current_session_value('stat', 'sess_pend_stat', '1');
	load_current_session_value('filter', 'sess_pend_filter', '');
	load_current_session_value('porq', 'sess_pend_porq', 'p');
	load_current_session_value('projqueue', 'sess_pend_projqueue', '-1');
	load_current_session_value('exclude', 'sess_pend_exclude', 'true');
	load_current_session_value('others', 'sess_pend_others', 'true');
	load_current_session_value('direction', 'sess_pend_direction', 'close');

	$sql_where       = '';
	$sql_where_inner = '';
	$sql_groupby     = '';
	$strXML          = '';

	/* when using queue or project all, we need another order if you are excluding reasons */
	$order_where     = '';

	if (get_request_var('clusterid') > 0) {
		$sql_where_inner .= (strlen($sql_where_inner) ? ' AND':'WHERE') . " clusterid=" . get_request_var('clusterid');
	}

	$table2 = 'tabler';
	if (get_request_var('timespan') == '-1') {
		$table1 = "(SELECT * FROM grid_jobs_pendhist_hourly $sql_where_inner) AS tabler";
	} elseif (get_request_var('timespan') == '-2' || get_request_var('timespan') == '1') {
		$sql_where_inner .= (strlen($sql_where_inner) ? ' AND':'WHERE') . ' date_recorded>=FROM_UNIXTIME(' . (time()-86400) . ')';
		$table1 = "(SELECT * FROM grid_jobs_pendhist_hourly $sql_where_inner UNION SELECT * FROM grid_jobs_pendhist_yesterday $sql_where_inner) AS tabler";
	} else {
		$year   = date('Y');
		$day    = date('z');

		if ($day <= get_request_var('timespan')) {
			$year = $year - 1;
			$day  = (365 - (get_request_var('timespan') - $day));
		} else {
			$day  = date('z') - get_request_var('timespan');
		}

		$sql_where_hr    = $sql_where_inner;
		$sql_where_inner .= (strlen($sql_where_inner) ? ' AND':'WHERE') . ' year_day>=' . ($year . substr('00' . $day, -3));

		$table1 = "(SELECT * FROM grid_jobs_pendhist_hourly $sql_where_hr UNION SELECT * FROM grid_jobs_pendhist_daily $sql_where_inner) AS tabler";
	}

	$clusterid    = "'N/A' AS clusterid";
	if (get_request_var('clusterid') > 0) {
		$clusterid    = "$table2.clusterid";
	} elseif (get_request_var('clusterid') != '-1') {
		$clusterid    = "$table2.clusterid";
		$sql_groupby .= (strlen($sql_groupby) ? ',':'') . "$table2.clusterid";
	}

	if (get_request_var('type') != -1) {
		switch (get_request_var('type')) {
			case 0:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='Host'";
				break;
			case 1:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='Resource'";
				break;
			case 2:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='Queue'";
				break;
			case 3:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='Job Group'";
				break;
			case 4:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='User Group'";
				break;
			case 5:
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " $table2.detail_type='Limit %'";
				break;
		}
	}

	$topper = 0;
	if (get_request_var('top') == -1 && get_request_var('projqueue') != -1) {
		if (get_request_var('others') == 'false') {
			$row_limit = 10;
		} else {
			$row_limit = 99999999;
			$topper    = 10;
		}
	} elseif (get_request_var('others') == 'true' || get_request_var('projqueue') == '-1') {
		$row_limit = 99999999;
		$topper    = get_request_var('top');
	} else {
		$row_limit = get_request_var('top');
	}

	if (get_request_var('measure') == 1) {
		$stat = 'SUM(total_slots) AS value';
		$vlegend = 'Jobs';
	} else {
		$stat = 'SUM(total_pend)/3600 AS value';
		$vlegend = 'Hours';
	}

	if (get_request_var('projqueue') != '-1') {
		$element = "'" . get_request_var('projqueue') . "' AS filter";
		if (get_request_var('porq') == 'p') {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " projectName='" . get_request_var('projqueue') . "'";
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " queue='" . get_request_var('projqueue') . "'";
		}
		$orderby = 'ORDER BY value DESC';
	} else {
		$sql_groupby .= (strlen($sql_groupby) ? ',':'') . 'filter';
		if (get_request_var('porq') == 'p') {
			$element      = 'projectName AS filter';
		} else {
			$element      = 'queue AS filter';
		}
		$orderby = 'ORDER BY filter ASC, value DESC';
	}

	if (get_request_var('stat') == '-1') {
		/* include all status' */
	} elseif (get_request_var('stat') == '1') {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " stat='DONE'";
	} else {
		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " stat='EXIT'";
	}

	if (get_request_var('filter') != '') {
		$filters = explode(' ', get_request_var('filter'));
		$sf_where = '';
		foreach($filters as $sfilter) {
			if (get_request_var('porq') == 'p') {
				$sf_where .= (strlen($sf_where) ? ' OR ':'') . " projectName LIKE ?";
			} else {
				$sf_where .= (strlen($sf_where) ? ' OR ':'') . " queue LIKE ?";
			}
			$sql_params[] = '%'. $sfilter . '%';
		}
		$sf_where = ' (' . $sf_where . ')';

		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . $sf_where;
	}

	$sql_groupby = 'GROUP BY prm.reason, prm.sub_reason_code' . (strlen($sql_groupby) ? ", $sql_groupby":"");
	if ($row_limit != 99999999) {
		$limit = "LIMIT $row_limit";
	} else {
		$limit = '';
	}

	if (get_request_var('exclude') == 'false') {
		$reasons = db_fetch_assoc_prepared('SELECT present,reason,subreason FROM grid_pendreasons_ignore WHERE user_id=?', array($_SESSION['sess_user_id']));

		if (cacti_sizeof($reasons)) {
			$string = '';
			foreach($reasons as $r) {
				if ($r['present'] == '1') {
					$string .= (strlen($string) ? ',':'') . "'" . $r['reason'] . '|' . $r['subreason'] . "'";
				}
			}

			if ($string != '') {
				$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " CONCAT_WS('', reason_code, '|', sub_reason_code, '') NOT IN ($string)";
				$order_where .= (strlen($order_where) ? ' AND':'WHERE') . " CONCAT_WS('', $table2.reason, '|', $table2.subreason, '') NOT IN ($string)";
			}
		}
	}

	if (get_request_var('projqueue') == '-1') {
		if (get_request_var('top') == -1) {
			$top = 10;
		} else {
			$top = get_request_var('top');
		}

		$sql = "SELECT
			CONCAT_WS('', $table2.reason, '|', $table2.subreason, '') AS ctreason, $stat, IF(prm.sub_reason_code <> -1, concat_ws(': ', prm.reason, prm.sub_reason_code), prm.reason) AS reason_text
			FROM $table1
			INNER JOIN grid_jobs_pendreason_maps AS prm
			ON $table2.reason=prm.reason_code AND $table2.subreason=prm.sub_reason_code
			$order_where
			GROUP BY ctreason
			ORDER BY value DESC
			LIMIT $top";

		//cacti_log(str_replace("\t", " ", str_replace("\n", " ", $sql)));

		$reas = array_rekey(db_fetch_assoc($sql), 'ctreason', array('ctreason', 'reason_text'));

		if (cacti_sizeof($reas)) {
			$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " CONCAT_WS('', reason_code, '|', sub_reason_code, '') IN ('" . implode("','", array_keys($reas)) . "')";
		}
	} else {
		$reas = array();
	}

	$sql = "SELECT DISTINCT $element
		FROM $table1
		INNER JOIN grid_jobs_pendreason_maps AS prm
		ON $table2.reason=prm.reason_code AND $table2.subreason=prm.sub_reason_code
		$sql_where
		ORDER BY filter ASC
		LIMIT 5";

	//cacti_log(str_replace("\t", " ", str_replace("\n", " ", $sql)));

	$measures = db_fetch_assoc_prepared($sql, $sql_params);

	$sql = "SELECT $clusterid, IF(prm.sub_reason_code <> -1, concat_ws(': ', prm.reason, prm.sub_reason_code), prm.reason) AS reason, $element, $stat
		FROM $table1
		INNER JOIN grid_jobs_pendreason_maps AS prm
		ON $table2.reason=prm.reason_code AND $table2.subreason=prm.sub_reason_code
		$sql_where
		$sql_groupby
		$orderby $limit";

	//cacti_log(str_replace("\t", " ", str_replace("\n", " ", $sql)));
	$stats = db_fetch_assoc_prepared($sql, $sql_params);

	if (get_request_var('porq') == 'p') {
		$title = 'Project Based ';
	} else {
		$title = 'Queue Based ';
	}

	if (get_request_var('clusterid') == 0) {
		$title .= 'Pending Reasons for All Clusters';
	} elseif (get_request_var('clusterid') == -1) {
		$title .= 'Pending Reasons from All Clusters';
	} else {
		$title .= 'Pending Reasons for Cluster ' . title_trim(gridpend_get_clustername(get_request_var('clusterid')), 20);
	}

	if (get_request_var('projqueue') != '-1') {
		$title .= ' For ' . (get_request_var('porq') == 'p' ? 'Project ':'Queue ') . "\"" . get_request_var('projqueue') . "\"";
	}
	$fusion_theme = "theme='"  . get_selected_theme() . "'";

	$strXML = "<chart useRoundEdges='1' legendPosition='RIGHT' legendNumColumns='1' showValues='0' caption='" . ($row_limit != 99999999 ? 'Top ' . $row_limit . ' - ':'') . $title . "' yAxisName='$vlegend' formatNumberScale='0' $fusion_theme exportEnabled='1'>";
	/* set the categories */
	$categories = array();
	$i = 0;
	if (get_request_var('projqueue') == '-1') {
		if (cacti_sizeof($measures)) {
			$strXML .= '<categories>';
			foreach($measures as $s) {
				$strXML .= "<category label='" . title_trim($s['filter'],15) . "'/>";
				$categories[] = $s['filter'];
			}
			$strXML .= '</categories>';
		} elseif (cacti_sizeof($stats)) {
			$ls = '';
			$strXML .= '<categories>';
			foreach($stats as $s) {
				if ($s['filter'] != $ls) {
					$strXML .= "<category label='" . title_trim($s['filter'],15) . "'/>";
					$ls = $s['filter'];
					$categories[] = $s['filter'];
				}
			}
			$strXML .= '</categories>';
		}
	} else {
		$strXML .= "<categories><category label=''/></categories>";
	}

	$i      = 0;
	$others = 0;
	$lf     = '';
	$change = false;
	if (cacti_sizeof($stats) > 0) {
		/* get all the reasons */
		if (get_request_var('projqueue') == '-1') {
			if (cacti_sizeof($reas)) {
				foreach ($reas as $r) {
					$preasons[$r['reason_text']] = $r['reason_text'];
				}
			} else {
				foreach ($stats as $stat) {
					$preasons[$stat['reason']] = $stat['reason'];
				}
			}
			foreach($preasons as $r) {
				$strXML .= "<dataset seriesName='" . gridpend_encode_fusion_chars($r) . "'>";
				foreach($categories as $category) {
					$found = false;
					foreach($stats as $stat) {
						if ($stat['reason'] == $r && $stat['filter'] == $category) {
							$strXML .= "<set value='" . round($stat['value'],2) . "' tooltext='Reason: " . gridpend_encode_fusion_chars($r) . '{br}' . (get_request_var('porq') == 'p' ? 'Project ':'Queue ') . ": $category{br}Value: " . round($stat['value'],2) . " $vlegend'/>";
							$found = true;
							break;
						}
					}
					if (!$found) {
						$strXML .= "<set value='0' tooltext='Reason: " . gridpend_encode_fusion_chars($r) . '{br}' . (get_request_var('porq') == 'p' ? 'Project ':'Queue ') . ": $category{br}Value: 0 $vlegend'/>";
					}
				}
				$strXML .= '</dataset>';
			}
		} else {
			foreach ($stats as $stat) {
				if (get_request_var('clusterid') == '-1') {
					$clustername = 'N/A';
				} else {
					$clustername = title_trim(gridpend_get_clustername($stat['clusterid']), 20);
					if ($clustername == '') $clustername = 'Not Found';
				}

				if ($topper == 0 || $i < $topper) {
					$strXML .= "<dataset seriesName='" . gridpend_encode_fusion_chars($stat['reason']) . "'><set value='" . $stat['value'] . "' tooltext='Reason: " . gridpend_encode_fusion_chars($stat['reason']) . '{br}Value: ' . $stat['value'] . " $vlegend'/></dataset>";
				} elseif ($i >= $topper) {
					$others += $stat['value'];
				}
				$i++;

			}

			if ($others) {
				$strXML .= "<dataset seriesName='Others'><set value='" . round($others,2) . "' tooltext='Reason: All Other Reasons Combined{br}Value: " . round($others,2) . " $vlegend'/></dataset>";
			}
		}

		$strXML .= "<styles><definition><style name='HTMLMode' type='font' isHTML='1' /><style type='font' name='CaptionFont' color='666666' size='15' /><style type='font' name='SubCaptionFont' bold='0'/></definition><application><apply toObject='caption' styles='CaptionFont'/><apply toObject='SubCaption' styles='SubCaptionFont'/><apply toObject='TOOLTIP' styles='HTMLMode'/></application></styles>";
	}

	$strXML .= '</chart>';
	print $strXML;
}

function gridpend_encode_fusion_chars($s) {
	$s = str_replace('&', '&amp;', $s);
	$s = str_replace('<', '&lt;', $s);
	$s = str_replace('>', '&gt;', $s);
	$s = str_replace("'", '&apos;', $s);
	$s = str_replace('"', '&quot;', $s);

	return $s;
}

function gridpend_get_clustername($clusterid) {
	return db_fetch_cell_prepared("SELECT clustername FROM grid_clusters WHERE clusterid=?", array($clusterid));
}

function gridpend_view_pending() {
	global $title, $grid_search_types, $gridpend_rows_selector, $config;
	global $grid_timespans, $grid_timeshifts, $grid_weekdays, $timespan, $group_function, $summary_stats;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var('top'));
	input_validate_input_number(get_request_var('clusterid'));
	input_validate_input_number(get_request_var('measure'));
	input_validate_input_number(get_request_var('timespan'));
	input_validate_input_number(get_request_var('stat'));
	input_validate_input_number(get_request_var('page'));
	/* ==================================================== */

	if (isset_request_var('exclude'))   { set_request_var('exclude',sanitize_search_string(get_request_var('exclude'))); }
	if (isset_request_var('porq'))      { set_request_var('porq',sanitize_search_string(get_request_var('porq'))); }
	if (isset_request_var('filter'))    { set_request_var('filter',sanitize_search_string(get_request_var('filter'))); }
	if (isset_request_var('projqueue')) { set_request_var('projqueue',sanitize_search_string(get_request_var('projqueue'))); }
	if (isset_request_var('others'))    { set_request_var('others',sanitize_search_string(get_request_var('others'))); }

	/* if the user pushed the 'clear' button */
	if (isset_request_var('clear')) {
		kill_session_var('sess_pend_exclude');
		kill_session_var('sess_pend_porq');
		kill_session_var('sess_pend_projqueue');
		kill_session_var('sess_pend_others');
		kill_session_var('sess_pend_top');
		kill_session_var('sess_grid_view_clusterid');
		kill_session_var('sess_pend_measure');
		kill_session_var('sess_pend_type');
		kill_session_var('sess_pend_timespan');
		kill_session_var('sess_pend_stat');
		kill_session_var('sess_pend_filter');
		kill_session_var('sess_pend_direction');

		unset_request_var('exclude');
		unset_request_var('porq');
		unset_request_var('projqueue');
		unset_request_var('others');
		unset_request_var('top');
		unset_request_var('clusterid');
		unset_request_var('measure');
		unset_request_var('type');
		unset_request_var('timespan');
		unset_request_var('stat');
		unset_request_var('filter');
	} else {
		$changed = 0;
		$changed += check_changed('clusterid', 'sess_grid_view_clusterid');
		$changed += check_changed('timespan', 'sess_pend_timespan');
		$changed += check_changed('measure', 'sess_pend_measure');
		$changed += check_changed('type', 'sess_pend_type');
		$changed += check_changed('top', 'sess_pend_top');
		$changed += check_changed('stat', 'sess_pend_stat');
		$changed += check_changed('filter', 'sess_pend_filter');
		$changed += check_changed('projqueue', 'sess_pend_projqueue');
		$changed += check_changed('porq', 'sess_pend_porq');
		$changed += check_changed('exclude', 'sess_pend_exclude');
		$changed += check_changed('others', 'sess_pend_others');

		if (check_changed('measure', 'sess_pend_measure')) {
			set_request_var('filter','');
		}
	}

	if (isset_request_var('porq') && isset($_SESSION['sess_pend_porq'])) {
		if (get_request_var('porq') != $_SESSION['sess_pend_porq']) {
			set_request_var('projqueue', '-1');
		}
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value('clusterid', 'sess_grid_view_clusterid', read_grid_config_option('default_grid'));
	load_current_session_value('timespan', 'sess_pend_timespan', '1');
	load_current_session_value('measure', 'sess_pend_measure', '1');
	load_current_session_value('type', 'sess_pend_type', '-1');
	load_current_session_value('top', 'sess_pend_top', '10');
	load_current_session_value('stat', 'sess_pend_stat', '-1');
	load_current_session_value('filter', 'sess_pend_filter', '');
	load_current_session_value('porq', 'sess_pend_porq', 'p');
	load_current_session_value('projqueue', 'sess_pend_projqueue', '-1');
	load_current_session_value('exclude', 'sess_pend_exclude', 'true');
	load_current_session_value('others', 'sess_pend_others', 'true');
	load_current_session_value('direction', 'sess_pend_direction', 'close');

	general_header();

	/* main filter */
	html_start_box(__('Pending Reason Filters', 'gridpend'), '100%', '', '3', 'center', '');
	gridpend_filter_table();
	html_end_box(true);

	html_start_box(__('Pending Reason History', 'gridpend'), '100%', '', '3', 'center', '');
	print "<tr><td align='center'><div id='gridpendchartcontianer'></div></td></tr>";
	html_end_box();

	bottom_footer();
}

function gridpend_filter_table() {
	global $config, $gridpend_rows_selector;
	global $gridpend_time_range;

	$min_start = db_fetch_cell('SELECT MIN(year_day) FROM grid_jobs_pendhist_daily');

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>
			var gridpend_filter_table_height;
			var gridpend_filter_table_width;

			$(function() {
				$(function() {
					if ($('#projqueue').val() == '-1') {
						$('#others').attr('disabled', true);
					}
					gridpend_filter_table_height = $(window).height();
					gridpend_filter_table_width = $(window).width();
					drawChart();
				});

				$(window).resize(function() {
					current_gridpend_filter_table_height = $(window).height();
					current_gridpend_filter_table_width = $(window).width();
					if (Math.abs(current_gridpend_filter_table_height - gridpend_filter_table_height) > 10 || Math.abs(current_gridpend_filter_table_width - gridpend_filter_table_width > 10)) {
						gridpend_filter_table_height = current_gridpend_filter_table_height;
						gridpend_filter_table_width = current_gridpend_filter_table_width;
						drawChart();
					}
				});

				function drawChart() {
					if (navigator.userAgent.match(/msie/i)) {
						var D = (document.body.clientWidth)? document.body: document.documentElement;
						var fw  = D.clientWidth - 250;
					} else {
						var fw=window.innerWidth - 250;
					}
					fh = 400;

					//FusionCharts.debugMode.enabled(true);
					//FusionCharts.debugMode.outputTo(console.log);
					//FusionCharts.setCurrentRenderer('javascript');

					if (FusionCharts('gridpendchart')) {
						FusionCharts('gridpendchart').dispose();
					}
					chartobj = new FusionCharts('mscolumn3d', 'gridpendchart', fw, fh, 'gridpendchartcontianer');
					if ($('div #gridpendchartcontianer').length) {
						chartobj.setDataURL('<?php print urlencode('grid_pend.php?getdata');?>');
						chartobj.render('gridpendchartcontianer');
					}
				}

				function applyFilter() {
					strURL = 'grid_pend.php?header=false&clusterid=' + $('#clusterid').val();
					strURL = strURL + '&top=' + $('#top').val();
					strURL = strURL + '&timespan=' + $('#timespan').val();
					strURL = strURL + '&porq=' + $('#porq').val();
					<?php print (get_request_var('projqueue') == '-1' ? "strURL = strURL + '&filter=' + $('#filter').val();":"strURL = strURL + '&filter=';"); ?>
					strURL = strURL + '&projqueue=' + $('#projqueue').val();
					strURL = strURL + '&measure=' + $('#measure').val();
					strURL = strURL + '&type=' + $('#type').val();
					strURL = strURL + '&exclude=' + $('#exclude').is(':checked');
					strURL = strURL + '&others=' + $('#others').is(':checked');
					<?php print (read_config_option('gridpend_include_exited') == 'on' ? "strURL = strURL + '&stat=' + $('#stat').val();":"strURL = strURL + '&stat=-1';"); ?>
					loadPageNoHeader(strURL);
				}

				function clearFilter() {
					strURL = 'grid_pend.php?header=false&clear=1'
					loadPageNoHeader(strURL);
				}

				$('#formname').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#clusterid, #top, #timespan, #porq, #filter, #projqueue, #measure, #type, #exclude, #others, #stat').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});
			</script>
			<form action='grid_pend.php' id='formname'>
				<table class='filterTable'>
					<tr>
						<td>
							<?php print __('Cluster', 'gridpend');?>
						</td>
						<td>
							<select id='clusterid'>
								<option value='-1'<?php if (get_request_var('clusterid') == '-1') {?> selected<?php }?>><?php print __('N/A', 'gridpend');?></option>
								<?php
								$clusters = gridpend_getnames('clusterid', 0, get_request_var('timespan'));
								if (cacti_sizeof($clusters)) {
									$clusters = db_fetch_assoc('SELECT clusterid, clustername
										FROM grid_clusters
										WHERE clusterid IN (' . implode(',',$clusters) . ')');

									if (cacti_sizeof($clusters)) {
										foreach ($clusters as $cluster) {
											print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . html_escape($cluster['clustername']) . '</option>';
										}
									}
								}
								?>
							</select>
						</td>
						<td title='<?php print __('Select the Dimension to Display', 'gridpend');?>'>
							<?php print __('Display', 'gridpend');?>
						</td>
						<td>
							<select id='porq'>
								<option value='p'<?php if (get_request_var('porq') == 'p') {?> selected<?php }?>><?php print __('By Project', 'gridpend');?></option>
								<option value='q'<?php if (get_request_var('porq') == 'q') {?> selected<?php }?>><?php print __('By Queue', 'gridpend');?></option>
							</select>
						</td>
						<?php if (get_request_var('porq') == 'p') { ?>
						<td>
							<?php print __('Project', 'gridpend');?>
						</td>
						<td>
							<select id='projqueue'>
								<option value='-1'<?php if (get_request_var('projqueue') == '-1') {?> selected<?php }?>><?php print __('All', 'gridpend');?></option>
								<?php
								$projects = gridpend_getnames('projectName', get_request_var('clusterid'), get_request_var('timespan'));
								if (cacti_sizeof($projects)) {
									foreach($projects as $r) {
										print "<option value='" . $r . "'" . (get_request_var('projqueue') == $r ? ' selected':'') . '>' . $r . '</option>';
									}
								}
								?>
							</select>
						</td>
						<?php } else { ?>
						<td>
							<?php print __('Queue', 'gridpend');?>
						</td>
						<td>
							<select id='projqueue'>
								<option value='-1'<?php if (get_request_var('projqueue') == '-1') {?> selected<?php }?>><?php print __('All', 'gridpend');?></option>
								<?php
								$queues = gridpend_getnames('queue', get_request_var('clusterid'), get_request_var('timespan'));
								if (cacti_sizeof($queues)) {
									foreach($queues as $r) {
										print "<option value='" . $r . "'" . (get_request_var('projqueue') == $r ? ' selected':'') . '>' . $r . '</option>';
									}
								}
								?>
							</select>
						</td>
						<?php } ?>
						<?php if (read_config_option('gridpend_include_exited') == 'on') { ?>
						<td title='<?php print __('Job Finished Status', 'gridpend');?>'>
							<?php print __('Status', 'gridpend');?>
						</td>
						<td>
							<select id='stat'>
								<option value='-1'<?php if (get_request_var('stat') == '-1') {?> selected<?php }?>><?php print __('All', 'gridpend');?></option>
								<option value='1'<?php if (get_request_var('stat') == '1') {?> selected<?php }?>><?php print __('DONE', 'gridpend');?></option>
								<option value='2'<?php if (get_request_var('stat') == '2') {?> selected<?php }?>><?php print __('EXIT', 'gridpend');?></option>
							</select>
						</td>
						<?php } ?>
						<td>
							<span>
								<input type='submit' id='go' value='<?php print __('Go');?>' title='<?php print __('Search');?>'>
								<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
							</span>
						</td>
					</tr>
				</table>
				<table class='filterTable'>
					<tr>
						<td title='<?php print __('Timespan Limits controlled by history present in database', 'gridpend');?>'>
							<?php print __('Timespan', 'gridpend');?>
						</td>
						<td>
							<select id='timespan'>
								<option value='-1'<?php if (get_request_var('timespan') == '-1') {?> selected<?php }?>><?php print __('Today Only', 'gridpend');?></option>
								<option value='-2'<?php if (get_request_var('timespan') == '-2') {?> selected<?php }?>><?php print __('Last 24 Hrs', 'gridpend');?></option>
							<?php
							$step=1;
							$max_days = gridpend_getmaxdays();
							for ($i=1;$i<365;$i = $i + $step) {
								if ($i>$max_days) break;

								if ($i < 7) {
									if ($i == 1) {
										$range = __('Last %d Day', $i, 'gridpend');
									} else {
										$range = __('Last %d Days', $i, 'gridpend');
									}
								} elseif ($i < 30) {
									$step = 7;
									$meas = intval($i/$step);
									if ($meas == 1) {
										$range = __('Last %d Week', $meas, 'gridpend');
									} else {
										$range = __('Last %d Weeks', $meas, 'gridpend');
									}
								} elseif ($i < 365) {
									$step = 30;
									$meas = intval($i/$step);
									if ($meas == 1) {
										$range = __('Last %d Month', $meas, 'gridpend');
									} else {
										$range = __('Last %d Months', $meas, 'gridpend');
									}
								}

								print "<option value='$i' " . (get_request_var('timespan') == $i ? 'selected':'') . '>' . $range . '</option>';
							}
							?>
							</select>
						</td>
						<td title='<?php print __('Include the TopX Pending Reasons', 'gridpend');?>'>
							<?php print __('Top Reasons', 'gridpend');?>
						</td>
						<td>
							<select id='top'>
							<?php
							if (cacti_sizeof($gridpend_rows_selector)) {
								foreach ($gridpend_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('top') == $key) { print 'selected'; } print '>' . $value . '</option>';
								}
							}
							?>
							</select>
						</td>
						<td title='<?php print __('Currently only Projects and Queues are Included', 'gridpend');?>'>
							<?php print __('Measure', 'gridpend');?>
						</td>
						<td>
							<select id='measure'>
								<option value='2'<?php if (get_request_var('measure') == '2') {?> selected<?php }?>><?php print __('By Time', 'gridpend');?></option>
								<option value='1'<?php if (get_request_var('measure') == '1') {?> selected<?php }?>><?php print __('By Slots', 'gridpend');?></option>
							</select>
						</td>
						<td title='<?php print __('Pend reason detail', 'gridpend');?>'>
							<?php print __('Pend Reason Type', 'gridpend');?>
						</td>
						<td>
							<select id='type'>
								<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('N/A', 'gridpend');?></option>
								<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('Host', 'gridpend');?></option>
								<option value='1'<?php if (get_request_var('type') == '1') {?> selected<?php }?>><?php print __('Resource', 'gridpend');?></option>
								<option value='2'<?php if (get_request_var('type') == '2') {?> selected<?php }?>><?php print __('Queue', 'gridpend');?></option>
								<option value='3'<?php if (get_request_var('type') == '3') {?> selected<?php }?>><?php print __('Job Group', 'gridpend');?></option>
								<option value='4'<?php if (get_request_var('type') == '4') {?> selected<?php }?>><?php print __('User Group', 'gridpend');?></option>
								<option value='5'<?php if (get_request_var('type') == '5') {?> selected<?php }?>><?php print __('Limit', 'gridpend');?></option>
	                        </select>
	                    </td>
					</tr>
				</table>
				<table class='filterTable'>
					<tr>
						<?php if (get_request_var('projqueue') == '-1') {?>
						<td>
							<?php print __('Search', 'gridpend');?>
						</td>
						<td>
							<input id='filter' type='text' value='<?php print get_request_var('filter');?>' size='80' title='<?php print __esc('Enter a space delimited list of matching Queues/Projects. Otherwise, only 5 shown', 'gridpend');?>'>
						</td>
						<?php } ?>
						<td title='<?php print __('If checked, User excluded Pending Reasons will be included in report', 'gridpend');?>'>
							<input id='exclude' type='checkbox' <?php print (get_request_var('exclude') == 'true' ? 'checked':'');?>>Excluded Reasons</input>
						</td>
						<td title='<?php print __('Sum all other Pending Reasons to single Reason.  Disabled when Project/Queue All is Selected', 'gridpend');?>'>
							<input id='others' type='checkbox' <?php print (get_request_var('others') == 'true' ? 'checked':'');?>>Include Others</input>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
	</tr><?php
}

function gridpend_getnames($type, $clusterid, $days) {
	if ($clusterid > 0) {
		$sql_where = "WHERE clusterid=$clusterid";
	} else {
		$sql_where = '';
	}

	$now    = date('Y') . substr('000' . date('z'), -3);
	$nowday = date('z');

	if ($days == -1) {
		return array_rekey(db_fetch_assoc("SELECT DISTINCT $type
			FROM grid_jobs_pendhist_hourly $sql_where
			ORDER BY $type"), $type, $type);
	} else if ($days < 2) {
		return array_rekey(db_fetch_assoc("SELECT DISTINCT $type
			FROM grid_jobs_pendhist_hourly $sql_where
			UNION
			SELECT DISTINCT $type
			FROM grid_jobs_pendhist_yesterday
			$sql_where
			ORDER BY $type"), $type, $type);
	} else {
		if ($nowday - $days < 0) {
			$then = (date('Y')-1) . substr('000' . (365 - ($days - $nowday)), -3);
		} else {
			$then = date('Y') . substr('000' . ($nowday - $days), -3);
		}

		$sql_where_c = $sql_where;

		$sql_where .= (strlen($sql_where) ? ' AND':'WHERE') . " year_day>=$then";
		return array_rekey(db_fetch_assoc("SELECT DISTINCT $type
			FROM grid_jobs_pendhist_hourly $sql_where_c
			UNION
			SELECT DISTINCT $type
			FROM grid_jobs_pendhist_daily
			$sql_where
			ORDER BY $type"), $type, $type);
	}
}

function gridpend_getmaxdays() {
	$now    = date('Y') . substr('000' . date('z'), -3);
	$then   = db_fetch_cell('SELECT MIN(year_day) FROM grid_jobs_pendhist_daily');

	return ($now - $then);
}

