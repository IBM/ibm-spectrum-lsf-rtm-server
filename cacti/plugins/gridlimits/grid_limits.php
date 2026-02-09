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

$guest_account = true;
chdir('../../');
include('./include/auth.php');
include_once('./plugins/grid/lib/grid_filter_functions.php');
include_once('./plugins/grid/lib/grid_partitioning.php');
include_once('./plugins/gridlimits/functions.php');

$title = __('LSF Limits', 'gridlimits');

set_default_action('limits');

switch(get_request_var('action')) {
	case 'limits':
		drawLimitsTable();

		break;
	case 'usage':
		drawUsePage();

		break;
	default:
		drawChartAjax();
}

function drawChartAjax() {
	validateUseVariables();

	if (isset_request_var('sequenceid')) {
		$sequenceid = get_filter_request_var('sequenceid');
	} else {
		return;
	}

	if (isset_request_var('action')) {
		$action = get_request_var('action');

		if ($action == 'getChartCount') {
			$count = 0;

			$row = db_fetch_row_prepared('SELECT *
				FROM grid_limits_history
				WHERE sequenceid = ?',
				array($sequenceid));

			$result = '';

			$strings = array(
				'slots_usage',
				'mem_usage',
				'swp_usage',
				'tmp_usage',
				'jobs_usage',
				'resources',
				'fwd_tasks_usage'
			);

			foreach ($strings as $string) {
				if ($string != 'resources') {
					if ($row[$string] != 0) {
						if ($result != '') {
							$result .= ",\"" . $string . "\": \"Y\"\n";
						} else {
							$result .= "\"" . $string . "\": \"Y\"\n";
						}
					}
				} elseif ($row[$string] != NULL && $row[$string] != '') {
					$arr = preg_split('/\s+/', trim($row[$string]));

					$i = 0;
					foreach ($arr as $a) {
						$i++;
						if ($i % 2 != 0) {
							if ($result != '') {
								$result .= ",\"" . $a . "\": \"Y\"\n";
							} else {
								$result .= "\"" . $a . "\": \"Y\"\n";
							}
						}
					}
				}
			}

			$result = '{' . $result . '}';

			print($result);
		} else if ($action == 'getData') {
			$newcat     = array();
			$newseries1 = array();
			$newseries2 = array();

			if (isset_request_var('resource') && strpos(get_nfilter_request_var('resource'), '_usage') == false) {
				$resource = get_nfilter_request_var('resource');
			} else {
				$resource = get_limit_type($sequenceid);
			}

			$row = db_fetch_row_prepared('SELECT *
				FROM grid_limits_history
				WHERE sequenceid = ?',
				array($sequenceid));

			$clusterid    = $row['clusterid'];
			$limit_name   = $row['limit_name'];
			$users        = $row['users'];
			$queues       = $row['queues'];
			$hosts        = $row['hosts'];
			$projects     = $row['projects'];
			$lic_projects = $row['lic_projects'];
			$clusters     = $row['clusters'];

			$time_period  = get_filter_request_var('time_period');
			$now          = time();

			$where = '';
			switch ($_REQUEST['time_period']) {
				case 1:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 2 hour)) ';
					$start  = date('Y-m-d H:i:s', $now - (3600 * 2));
					break;
				case 2:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 6 hour)) ';
					$start  = date('Y-m-d H:i:s', $now - (3600 * 6));
					break;
				case 3:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 12 hour)) ';
					$start  = date('Y-m-d H:i:s', $now - (3600 * 12));
					break;
				case 4:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 24 hour)) ';
					$start  = date('Y-m-d H:i:s', $now - 86400);
					break;
				case 5:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 2 day)) ';
					$start  = date('Y-m-d H:i:s', $now - (86400 * 2));
					break;
				case 6:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 7 day)) ';
					$start  = date('Y-m-d H:i:s', $now - (86400 * 7));
					break;
				case 7:
					$where .= ' AND (last_updated > DATE_SUB(now(), INTERVAL 14 day)) ';
					$start  = date('Y-m-d H:i:s', $now - (86400 * 14));
					break;
			}

			$resource_types = array(
				'slots',
				'mem',
				'swp',
				'tmp',
				'jobs',
				'fwd_tasks'
			);

			$params = array(
				$clusterid,
				$limit_name,
				$users,
				$queues,
				$hosts,
				$projects,
				$lic_projects,
				$clusters
			);

			if (in_array($resource, $resource_types)) {
				$sql = 'SELECT limit_name, users, last_updated,
					last_updated as last_updated2,
					' . $resource . '_usage AS col1,
					' . $resource . '_limit as col2
					FROM grid_limits_usage
					WHERE clusterid = ?
					AND limit_name = ?
					AND users = ?
					AND queues = ?
					AND hosts = ?
					AND projects = ?
					AND lic_projects = ?
					AND clusters = ?';
			} else {
				$usage = "SUBSTRING_INDEX(SUBSTRING_INDEX(LTRIM(SUBSTRING_INDEX(resources, '$resource', -1)), ' ', 1), '/', 1)";
				$limit = "SUBSTRING_INDEX(SUBSTRING_INDEX(LTRIM(SUBSTRING_INDEX(resources, '$resource', -1)), ' ', 1), '/', -1)";

				$sql = 'SELECT limit_name, users, last_updated,
					last_updated as last_updated2,
					' . $usage . ' AS col1,
					' . $limit . ' as col2
					FROM grid_limits_usage
					WHERE clusterid = ?
					AND limit_name = ?
					AND users = ?
					AND queues = ?
					AND hosts = ?
					AND projects = ?
					AND lic_projects = ?
					AND clusters = ?';
			}

			$sql  = $sql;
			$end  = date('Y-m-d H:i:s');

			$return = generate_partition_union_query($sql, 'grid_limits_usage', $where, $start, $end, $params);

			$return['sql'] .= ' ORDER BY last_updated2 ASC';

			//sql_param_debug($return['sql'], $return['params']);

			$rows = db_fetch_assoc_prepared($return['sql'], $return['params']);

			$series1 = '';
			$series2 = '';

			if (cacti_sizeof($rows)) {
				foreach ($rows as $row) {
					if ($series1 == '') {
						$series1 .= "{\n";
					} else {
						$series1 .= ",{\n";
					}

					if ($series2 == '') {
						$series2 .= "{\n";
					} else {
						$series2 .= ",{\n";
					}

					$series1 .= "\"value\": \"" . $row['col1'] . "\"\n";
					$series2 .= "\"value\": \"" . $row['col2'] . "\"\n";

					$series1 .= "}\n";
					$series2 .= "}\n";

					$newseries1[]['value'] = $row['col1'];
					$newseries2[]['value'] = $row['col2'];
				}

				$categories = '';
				foreach ($rows as $row) {
					if ($categories == '') {
						$categories .= "{\n";
					} else {
						$categories .= ",{\n";
					}

					$categories .= "\"label\": \"" . substr($row['last_updated'], 5, 11) . "\"\n";
					$categories .= "}\n";
					$newcat[]['label'] = substr($row['last_updated'], 5, 11);
				}

				$name  = '';
				$users = '';

				foreach ($rows as $row) {
					$name  = $row['limit_name'];
					$users = $row['users'];
					break;
				}

				$caption = '';
				if ($users != '') {
					$caption = __esc('Limit Usage - %s - %s', $limit_name, $users, 'gridlimits') . '<br/>' . $resource;
				} else {
					$caption = __esc('Limit Usage - %s', $limit_name, 'gridlimits') . '<br/> ' . $resource;
				}

				header('Content-Type: application/json');

				$myChart = array(
					'chart' => array(
						'showvalues'   => '0',
						'animation'    => '0',
						'labelDisplay' => 'auto',
						'yAxisName'    => __esc('Usage', 'gridlimits'),
						'xAxisName'    => __esc('Date', 'gridlimits'),
						'labelStep'    => '3',
						'drawAnchors'  => '0',
						'caption'      => $caption
					),
					'categories' => array(
						array(
							'category' => $newcat
						)
					),
					'dataset' => array(
						array(
							'seriesname' => __esc('Usage', 'gridlimits'),
							'data' => $newseries1
						),
						array(
							'seriesname' => __esc('Limit', 'gridlimits'),
							'data' => $newseries2
						),
					)
				);

				$chart = '"chart": { "showvalues": "0", "animation": "0", "labelDisplay": "auto", "yAxisName": "Usage",
					"xAxisName": "Date", "labelStep": "3", "drawAnchors": "0", "caption": "' . $caption . '"}';

				$series1    = '{ "seriesname": ' . __esc('Usage', 'gridlimits') . ', "data": [' . $series1 . '] }';
				$series2    = '{ "seriesname": ' . __esc('Limit', 'gridlimits') . ', "data": [' . $series2 . '] }';
				$categories = '"categories": [ { "category":  [ ' . $categories . ' ] } ]';
				$data       = '"dataset": [ ' . $series1 . ', ' . $series2 . ']';
				$output     = '{' . $chart . ',' . $categories . ',' . $data . '}';

				//printf($output);
				print json_encode($myChart);
			} else {
				return;
			}
		} else {
			return;
		}
	} else {
		return;
	}
}

function drawTabs() {
    global $config;

    /* present a tabbed interface */
    $tabs = array(
        'limits' => __('Limits', 'gridlimits'),
        'usage'  => __('Limit Usage', 'gridlimits'),
    );

    /* set the default tab */
    $current_tab = get_nfilter_request_var('action');

	if (empty($current_tab)) {
		$current_tab = 'limits';
	}

    /* draw the tabs */
    print "<div class='tabs'><nav><ul>";

    if (cacti_sizeof($tabs)) {
        foreach ($tabs as $action => $tab_name) {
            print '<li><a class="tab pic' . (($action == $current_tab) ? ' selected"' : '"') . ' href="' . html_escape($config['url_path'] .
                'plugins/gridlimits/grid_limits.php' .
                '?action=' . $action) .
                '">' . html_escape($tab_name) . '</a></li>';
        }
    }

    print '</ul></nav></div>';
}

function drawLimitsFilter() {
	global $config, $grid_rows_selector, $title;

	html_start_box($title, '100%', '', '3', 'center', '');

	$measure = get_request_var('measure');

	if ($measure == '-1') {
		$measures = get_in_use_measures(get_request_var('clusterid'));

		if (sizeof($measures)) {
			foreach($measures as $column => $name) {
				$measure = $column;
				set_request_var('measure', $measure);

				break;
			}
		}
	}

	if (!isset_request_var('limit_name') || get_nfilter_request_var('limit_name') == -1) {
		$clusters = db_fetch_assoc("SELECT gc.clusterid, gc.clustername
			FROM grid_clusters AS gc
			INNER JOIN (SELECT DISTINCT clusterid FROM grid_limits WHERE $measure > 0) AS gl
			ON gc.clusterid = gl.clusterid
			WHERE gc.disabled = ''
			ORDER BY clustername");
	} else {
		$clusters = db_fetch_assoc_prepared("SELECT DISTINCT gc.clusterid, gc.clustername
			FROM grid_clusters AS gc
			INNER JOIN grid_limits AS gl
			ON gc.clusterid = gl.clusterid
			WHERE gc.disabled = ''
			AND gl.limit_name = ?
			AND $measure > 0
			ORDER BY clustername",
			array(get_nfilter_request_var('limit_name')));
	}

	$classes = array(
		'host'        => __esc('Host', 'gridlimits'),
		'lic_project' => __esc('License Project', 'gridlimits'),
		'project'     => __esc('Project', 'gridlimits'),
		'queue'       => __esc('Queue', 'gridlimits'),
		'user'        => __esc('User', 'gridlimits'),
	);

	?>
	<tr class='odd'>
		<td>
			<form id='form_limits'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridlimits');?>
					</td>
					<td>
						<select id='clusterid' onChange='applyFilter()'>
							<?php
							print '<option value="-1"' . (get_request_var('clusterid') <= 0 ? ' selected':'') . '>' . __esc('All', 'gridlimits') . '</option>';
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] . '"' . (get_request_var('clusterid') == $cluster['clusterid'] ? ' selected':'') . '>' . html_escape($cluster['clustername']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Measure', 'gridlimits');?>
					</td>
					<td>
						<select id='measure' onChange='applyFilter()'>
							<?php
							$measures = get_in_use_measures(get_request_var('clusterid'));

							if (cacti_sizeof($measures)) {
								foreach ($measures as $key => $class) {
									print '<option value="' . $key . '"' . (get_request_var('measure') == $key ? ' selected':'') . '>' . $class . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Class', 'gridlimits');?>
					</td>
					<td>
						<select id='class' onChange='applyFilter()'>
							<?php
							print '<option value="-1"' . (get_request_var('class') == -1 ? ' selected':'') . '>' . __esc('All', 'gridlimits') . '</option>';
							if (cacti_sizeof($classes)) {
								foreach ($classes as $key => $class) {
									print '<option value="' . $key . '"' . (get_request_var('class') == $key ? ' selected':'') . '>' . $class . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridlimits');?>'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridlimits');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridlimits');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Records', 'gridlimits');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
			</form>
			<script type="text/javascript">

			function applyFilter() {
				strURL = 'grid_limits.php?header=false';
				strURL += '&action=limits';
				strURL += '&filter='    + $('#filter').val();
				strURL += '&measure='   + $('#measure').val();
				strURL += '&class='     + $('#class').val();
				strURL += '&rows='      + $('#rows').val();
				strURL += '&clusterid=' + $('#clusterid').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = 'grid_limits.php?header=false';
				strURL += '&action=limits';
				strURL += '&clear=true';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_limits').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});

			</script>
		</td>
	</tr>

	<?php

	html_end_box();
}

function get_in_use_measures($clusterid) {
	$measures = array(
		'slots'      => __('Slots', 'gridlimits'),
		'jobs'       => __('Jobs', 'gridlimits'),
		'mem'        => __('Memory', 'gridlimits'),
		'swp'        => __('Virtual Memory', 'gridlimits'),
		'tmp'        => __('Tmp Use', 'gridlimits'),
		'fwd_tasks'  => __('Forwarded Tasks', 'gridlimits'),
		'resources'  => __('Resource', 'gridlimits')
	);

	if ($clusterid > 0) {
		$limits = db_fetch_row_prepared('SELECT MAX(slots) AS slots,
			MAX(jobs) AS jobs,
			MAX(mem) AS mem,
			MAX(swp) AS swp,
			MAX(tmp) AS tmp,
			MAX(fwd_tasks) AS fwd_tasks,
			SUM(CASE WHEN resources != "" THEN 1 ELSE 0 END) AS resources
			FROM grid_limits
			WHERE clusterid = ?',
			array($clusterid));
	} else {
		$limits = db_fetch_row_prepared('SELECT MAX(slots) AS slots,
			MAX(jobs) AS jobs,
			MAX(mem) AS mem,
			MAX(swp) AS swp,
			MAX(tmp) AS tmp,
			MAX(fwd_tasks) AS fwd_tasks,
			SUM(CASE WHEN resources != "" THEN 1 ELSE 0 END) AS resources
			FROM grid_limits');
	}

	if (cacti_sizeof($limits)) {
		foreach($measures as $key => $name) {
			if ($limits[$key] == 0) {
				unset($measures[$key]);
			}
		}

		return $measures;
	} else {
		return array();
	}
}

function showLimitColumn($name, $limit_name, $clusterid) {
	static $limit_data = array();

	if (isset($limit_data[$limit_name . '|' . $clusterid])) {
		$limits = $limit_data[$limit_name . '|' . $clusterid];
	} elseif ($clusterid > 0) {
		$limits = db_fetch_row_prepared('SELECT
			SUM(CASE WHEN resources != "" THEN 1 ELSE 0 END) AS resources,
			SUM(CASE WHEN users != "" THEN 1 ELSE 0 END) AS users,
			SUM(CASE WHEN hosts != "" THEN 1 ELSE 0 END) AS hosts,
			SUM(CASE WHEN queues != "" THEN 1 ELSE 0 END) AS queues,
			SUM(CASE WHEN projects != "" THEN 1 ELSE 0 END) AS projects,
			SUM(CASE WHEN lic_projects != "" THEN 1 ELSE 0 END) AS lic_projects,
			SUM(CASE WHEN clusters != "" THEN 1 ELSE 0 END) AS clusters,
			MAX(slots_limit) AS slots,
			MAX(jobs_limit) AS jobs,
			MAX(mem_limit) AS mem,
			MAX(swp_limit) AS swp,
			MAX(tmp_limit) AS tmp,
			MAX(fwd_tasks_limit) AS fwd_tasks
			FROM grid_limits_history
			WHERE clusterid = ?
			AND limit_name = ?',
			array($clusterid, $limit_name));

		$limit_data[$limit_name . '|' . $clusterid] = $limits;
	} else {
		$limits = db_fetch_row_prepared('SELECT
			SUM(CASE WHEN resources != "" THEN 1 ELSE 0 END) AS resources,
			SUM(CASE WHEN users != "" THEN 1 ELSE 0 END) AS users,
			SUM(CASE WHEN hosts != "" THEN 1 ELSE 0 END) AS hosts,
			SUM(CASE WHEN queues != "" THEN 1 ELSE 0 END) AS queues,
			SUM(CASE WHEN projects != "" THEN 1 ELSE 0 END) AS projects,
			SUM(CASE WHEN lic_projects != "" THEN 1 ELSE 0 END) AS lic_projects,
			SUM(CASE WHEN clusters != "" THEN 1 ELSE 0 END) AS clusters,
			MAX(slots_limit) AS slots,
			MAX(jobs_limit) AS jobs,
			MAX(mem_limit) AS mem,
			MAX(swp_limit) AS swp,
			MAX(tmp_limit) AS tmp,
			MAX(fwd_tasks_limit) AS fwd_tasks
			FROM grid_limits_history
			WHERE limit_name = ?',
			array($limit_name));

		$limit_data[$limit_name . '|' . $clusterid] = $limits;
	}

	if (cacti_sizeof($limits)) {
		$name = str_replace('_usage', '', $name);

		if (isset($limits[$name]) && $limits[$name] == 0) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}

function showColumn($name, $clusterid) {
	$measure = get_request_var('measure');
	$columns = getViewableColumns($clusterid, $measure);

	if (strpos($name, '_usage') !== false) {
		if ($name != $measure . '_usage') {
			return false;
		}
	}

	if (isset($columns[$name]) && $columns[$name] > 0) {
		return true;
	} elseif (!isset($columns[$name])) {
		return true;
	} else {
		return false;
	}
}

function getViewableColumns($clusterid, $measure) {
	if (isset($_SESSION['sess_limits_cols'][$clusterid . '|' . $measure])) {
		return $_SESSION['sess_limits_cols'][$clusterid . '|' . $measure];
	} else {
		$sql_where = '';

		$sql = "SELECT
			SUM(CASE WHEN per_user != '' THEN 1 ELSE 0 END) AS per_user,
			SUM(CASE WHEN per_host != '' THEN 1 ELSE 0 END) AS per_host,
			SUM(CASE WHEN per_queue != '' THEN 1 ELSE 0 END) AS per_queue,
			SUM(CASE WHEN per_project != '' THEN 1 ELSE 0 END) AS per_project,
			SUM(CASE WHEN per_lic_project != '' THEN 1 ELSE 0 END) AS per_lic_project,
			SUM(CASE WHEN users != '' THEN 1 ELSE 0 END) AS users,
			SUM(CASE WHEN hosts != '' THEN 1 ELSE 0 END) AS hosts,
			SUM(CASE WHEN queues != '' THEN 1 ELSE 0 END) AS queues,
			SUM(CASE WHEN projects != '' THEN 1 ELSE 0 END) AS projects,
			SUM(CASE WHEN lic_projects != '' THEN 1 ELSE 0 END) AS lic_projects,
			MAX(slots) AS slots,
			MAX(mem) AS mem,
			MAX(swp) AS swp,
			MAX(tmp) AS tmp,
			MAX(jobs) AS jobs,
			MAX(fwd_tasks) AS fwd_tasks
			FROM grid_limits";

		if ($clusterid > 0) {
			$sql_where .= ' WHERE clusterid = ' . $clusterid;
		}

		if ($measure != '' && $measure != 'resources') {
			$sql_where .= ($sql_where != '' ? ' AND ':' WHERE ') . $measure . ' > 0';
		} elseif ($measure != '' && $measure == 'resources') {
			$sql_where .= ($sql_where != '' ? ' AND ':' WHERE ') . 'resources != ""';
		}

		$_SESSION['sess_limits_cols'][$clusterid . '|' . $measure] = db_fetch_row($sql . $sql_where);

		return $_SESSION['sess_limits_cols'][$clusterid . '|' . $measure];
	}
}

function nf18n($string, $digits = 0) {
	if ($string == '-') {
		return $string;
	} elseif ($string === null) {
		return '-';
	} else {
		return number_format_i18n($string, $digits);
	}
}

function drawLimitsRows($rows) {
	global $config;

	$sql = getLimitsSQL();

	$total_rows = 0;

	# Add the row limits to the query
	$sql .= ' LIMIT ' . ($rows*($_REQUEST['page']-1)) . ',' . $rows;

	$data = getLimitsRows($sql, $total_rows);

	$filter = get_request_var('filter');
	$cid    = get_request_var('clusterid');

	$i = 0;
	foreach ($data as $row) {
		$url = $config['url_path'] . 'plugins/gridlimits/grid_limits.php' .
			'?action=usage' .
			'&reset=true' .
			'&clusterid=' . $row['clusterid'] .
			'&limit_name=' . $row['limit_name'];

		if ($row['resources'] != '') {
			$parts = explode(' / ', $row['resources']);
			$url .= '&resource=' . $parts[0];
		}

		form_alternate_row('line_' . $i, true); $i++;

		form_selectable_ecell($row['cluster_name'], $i);
		form_selectable_cell(filter_value($row['limit_name'], get_request_var('filter'), $url), $i);

		if (showColumn('slots_usage', $cid)) {
			form_selectable_cell(nf18n($row['slots_usage']) . '/' . nf18n($row['slots']) . ($row['slots_per_processor'] == 'Y' ? ' per processor':''), $i, '', 'right');
		}

		if (showColumn('mem_usage', $cid)) {
			form_selectable_cell(nf18n($row['mem_usage']) . ' / ' . nf18n($row['mem']) . ($row['mem_percent'] == 'Y' ? '%':' ' . $row['unit_for_limits']), $i, '', 'right');
		}

		if (showColumn('swp_usage', $cid)) {
			form_selectable_cell(nf18n($row['swp_usage']) . ' / ' . nf18n($row['swp']) . ($row['swp_percent'] == 'Y' ? '%':' ' . $row['unit_for_limits']), $i, '', 'right');
		}

		if (showColumn('tmp_usage', $cid)) {
			form_selectable_cell(nf18n($row['tmp_usage']) . ' / ' . nf18n($row['tmp']) . ($row['tmp_percent'] == 'Y' ? '%':' ' . $row['unit_for_limits']), $i, '', 'right');
		}

		if (showColumn('jobs_usage', $cid)) {
			form_selectable_cell(nf18n($row['jobs_usage']) . '/' . nf18n($row['jobs']), $i, '', 'right');
		}

		if (showColumn('fwd_tasks_usage', $cid)) {
			form_selectable_cell(nf18n($row['fwd_tasks_usage']) . ' / ' . nf18n($row['fwd_tasks']), $i, '', 'right');
		}

		if (showColumn('users', $cid)) {
			form_selectable_cell(filter_value($row['users'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('per_user', $cid)) {
			form_selectable_cell(filter_value($row['per_user'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('hosts', $cid)) {
			form_selectable_cell(filter_value($row['hosts'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('per_host', $cid)) {
			form_selectable_cell(filter_value($row['per_host'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('queues', $cid)) {
			form_selectable_cell(filter_value($row['queues'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('per_queue', $cid)) {
			form_selectable_cell(filter_value($row['per_queue'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('projects', $cid)) {
			form_selectable_cell(filter_value($row['projects'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('per_project', $cid)) {
			form_selectable_cell(filter_value($row['per_project'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('lic_projects', $cid)) {
			form_selectable_cell(filter_value($row['lic_projects'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		if (showColumn('per_lic_project', $cid)) {
			form_selectable_cell(filter_value($row['per_lic_project'], get_request_var('filter')), $i, '', 'white-space:normal !important');
		}

		form_selectable_cell(filter_value($row['resources'], get_request_var('filter')), $i, '', 'white-space:normal !important;text-align:right;');

		form_selectable_cell($row['last_use'], $i, '', 'right');

		form_end_row();
	}
}

function drawLimitsTable() {
	global $config;

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
			'default' => '-1'
		),
		'class' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'users',
			'options' => array('options' => 'sanitize_search_string')
		),
		'measure' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'limit_name',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'limit_name' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

	validate_store_request_vars($filters, 'sess_limit');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('default_grid')
		)
	);

	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	general_header();

	drawTabs();

	drawLimitsFilter();

	$sql = 'SELECT COUNT(*) FROM (' . getLimitsSQL() . ') a';

	$total_rows = db_fetch_cell($sql);

	$headers = getTableHeaders();

	$nav = html_nav_bar($config['url_path'] . 'plugins/gridlimits/grid_limits.php?action=limits&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($headers), __('Limits', 'gridlimits'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($headers, get_request_var('sort_column'), get_request_var('sort_direction'), 1, $config['url_path'] . 'plugins/gridlimits/grid_limits.php?action=limits');

	if ($total_rows) {
		drawLimitsRows($rows);
	} else {
		print '<tr><td colspan="' . cacti_sizeof($headers) . '"><em>' . __('No Matching Limit Records Found', 'gridlimits') . '</em></td></tr>';
	}

	html_end_box(false);

	if ($total_rows) {
		print $nav;
	}

	bottom_footer();
}

function getLimitsSQL() {
	$date = date('Y-m-d H:i:s', time() - 86400);

	$measure = get_request_var('measure');

	$sql_query = "SELECT gc.clustername as cluster_name, gl.clusterid, gl.limit_name,
		IFNULL(gl.users, '-') AS users, IFNULL(gl.per_user, '-') AS per_user,
		IFNULL(gl.hosts, '-') AS hosts, IFNULL(gl.per_host, '-') AS per_host,
		IFNULL(gl.queues, '-') AS queues, IFNULL(gl.per_queue, '-') AS per_queue,
		IFNULL(gl.projects, '-') AS projects, IFNULL(gl.per_project, '-') AS per_project,
		IFNULL(gl.lic_projects, '-') AS lic_projects, IFNULL(gl.per_lic_project, '-') AS per_lic_project,
		slots, slots_usage, slots_per_processor,
		mem, mem_usage, mem_percent,
		swp, swp_usage, swp_percent,
		tmp, tmp_usage, tmp_percent,
		jobs, jobs_usage, IFNULL(resources, '-') AS resources,
		fwd_tasks, fwd_tasks_usage,
		unit_for_limits,
		last_updated AS last_use
		FROM grid_limits AS gl
		INNER JOIN grid_clusters AS gc
		ON gl.clusterid = gc.clusterid";

	if ($measure == 'resources') {
		$sql_where = 'WHERE resources != ""';
	} else {
		$sql_where = "WHERE $measure > 0";
	}

	if (get_nfilter_request_var('filter') != '') {
		$fields = array(
			'limit_name',
			'users',
			'per_user',
			'queues',
			'per_queue',
			'hosts',
			'per_host',
			'projects',
			'per_project',
			'lic_projects',
			'per_lic_project',
			'resources'
		);

		if (get_nfilter_request_var('filter') != '') {
			$sql_where .= ' AND (';
			$i = 0;

			foreach($fields as $field) {
				$sql_where .= ($i == 0 ? '':' OR ') . $field . ' LIKE ' . db_qstr('%' . get_nfilter_request_var('filter') . '%');
				$i++;
			}

			$sql_where .= ')';
		}
	}

	if (get_filter_request_var('clusterid') > 0) {
		$sql_where .= ' AND gl.clusterid = ' . get_request_var('clusterid');
	}

	if (get_request_var('class') != -1) {
		switch (get_request_var('class')) {
			case 'user':
				$sql_where .= ' AND (gl.users != "" OR gl.per_user != "")';

				break;
			case 'project':
				$sql_where .= ' AND (gl.projects != "" OR gl.per_project != "")';

				break;
			case 'lic_project':
				$sql_where .= ' AND (gl.lic_projects != "" OR gl.per_lic_project != "")';

				break;
			case 'host':
				$sql_where .= ' AND (gl.hosts != "" OR gl.per_host != "")';

				break;
			case 'queue':
				$sql_where .= ' AND (gl.queues != "" OR gl.per_queue != "")';

				break;
		}
	}

	$sql_order = get_order_string();

	$sql = "$sql_query $sql_where $sql_order";

	//cacti_log(str_replace("\n", ' ', str_replace("\t", '', $sql)));

	return $sql;
}

function getLimitsRows($sql_query, &$total_rows = 0) {
	$rows = db_fetch_assoc($sql_query);
	$new_rows = array();

	if (cacti_sizeof($rows)) {
		foreach($rows as $r) {
			if ($r['resources'] != '') {
				$resources = explode(' ', trim($r['resources']));

				foreach($resources as $res) {
					if (!is_numeric($res)) {
						$r['resources'] = $res . ' / ';
					} else {
						$r['resources'] .= $res;
						$new_rows[] = $r;
					}
				}
			} else {
				$new_rows[] = $r;
			}
		}
	}

	$total_rows = cacti_sizeof($total_rows);

	return $new_rows;
}

function checkChanged($request, $session) {
	if ((isset($_REQUEST[$request])) && (isset($_SESSION[$session]))) {
		if ($_REQUEST[$request] != $_SESSION[$session]) {
			return 1;
		}
	}
}

function getTableHeaders() {
	$headers = array(
		'cluster_name' => array(
			'display' => __('Cluster Name', 'gridlimits'),
			'sort' => 'ASC'
		),
		'limit_name' => array(
			'display' => __('Limit Name', 'gridlimits'),
			'sort' => 'ASC'
		),
		'slots_usage' => array(
			'display' => __('Slots', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'mem_usage' => array(
			'display' => __('Memory', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'swp_usage' => array(
			'display' => __('Swap', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'tmp_usage' => array(
			'display' => __('Tmp', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'jobs_usage' => array(
			'display' => __('Jobs', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'fwd_tasks_usage' => array(
			'display' => __('Forwarded Tasks', 'gridlimits'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'users' => array(
			'display' => __('Users', 'gridlimits'),
			'sort' => 'ASC'
		),
		'per_user' => array(
			'display' => __('Per User', 'gridlimits'),
			'sort' => 'ASC'
		),
		'hosts' => array(
			'display' => __('Hosts', 'gridlimits'),
			'sort' => 'ASC'
		),
		'per_host' => array(
			'display' => __('Per Host', 'gridlimits'),
			'sort' => 'ASC'
		),
		'queues' => array(
			'display' => __('Queues', 'gridlimits'),
			'sort' => 'ASC'
		),
		'per_queue' => array(
			'display' => __('Per Queue', 'gridlimits'),
			'sort' => 'ASC'
		),
		'projects' => array(
			'display' => __('Projects', 'gridlimits'),
			'sort' => 'ASC'
		),
		'per_project' => array(
			'display' => __('Per Project', 'gridlimits'),
			'sort' => 'ASC'
		),
		'lic_projects' => array(
			'display' => __('License Project', 'gridlimits'),
			'sort' => 'ASC'
		),
		'per_lic_project' => array(
			'display' => __('Per License Project', 'gridlimits'),
			'sort' => 'ASC'
		),
		'resources' => array(
			'display' => __('Resources', 'gridlimits'),
			'sort' => 'ASC'
		),
		'last_use' => array(
			'display' => __('Last Seen', 'gridlimits'),
			'sort' => 'DESC',
			'align' => 'right'
		)
	);

	$cid = get_request_var('clusterid');

	foreach($headers as $name => $data) {
		if (!showColumn($name, $cid)) {
			unset($headers[$name]);
		}
	}

	return $headers;
}

function drawUsePage() {
	general_header();

	drawTabs();

	# Draw the data table for this page
	drawUseTable();

	# Draw the Javascript we need for the dashboard updates
	drawUseJavascript();

	# Draw the dashboard for the limit
	drawUseDashboard();

	bottom_footer();
}

function drawUseFilter() {
	global $config, $grid_rows_selector, $title;

	html_start_box($title, '100%', '', '3', 'center', '');

	if (!isset_request_var('limit_name') || get_nfilter_request_var('limit_name') == -1) {
		$clusters = db_fetch_assoc('SELECT gc.clusterid, gc.clustername
			FROM grid_clusters AS gc
			INNER JOIN (SELECT DISTINCT clusterid FROM grid_limits) AS gl
			ON gc.clusterid = gl.clusterid
			WHERE gc.disabled = ""
			ORDER BY clustername');
	} else {
		$clusters = db_fetch_assoc_prepared('SELECT DISTINCT gc.clusterid, gc.clustername
			FROM grid_clusters AS gc
			INNER JOIN grid_limits AS gl
			ON gc.clusterid = gl.clusterid
			WHERE gc.disabled = ""
			AND gl.limit_name = ?
			ORDER BY clustername',
			array(get_nfilter_request_var('limit_name')));
	}

	if (get_request_var('clusterid') == '-1') {
		$limits = db_fetch_assoc('SELECT limit_name, clusterid
			FROM grid_limits
			WHERE clusterid IN (SELECT clusterid FROM grid_clusters WHERE disabled="")');
	} else {
		$limits = db_fetch_assoc_prepared('SELECT limit_name, clusterid
			FROM grid_limits
			WHERE clusterid = ?',
			array(get_request_var('clusterid')));
	}

	?>
	<tr class='odd'>
		<td>
			<form id='form_limit'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridlimits');?>
					</td>
					<td>
						<select id='clusterid' onChange='applyFilter()'>
						<?php

						print '<option value="-1"' . (get_request_var('clusterid') == -1 ? ' selected':'') . '>' . __esc('All', 'gridlimits') . '</option>';

						if (cacti_sizeof($clusters)) {
							foreach ($clusters as $cluster) {
								print '<option value="' . $cluster['clusterid'] . '"' . (get_request_var('clusterid') == $cluster['clusterid'] ? ' selected':'') . '>' . html_escape($cluster['clustername']) . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('Limit', 'gridlimits');?>
					</td>
					<td>
						<select id='limit_name' onChange='applyFilter()'>
							<?php

							print '<option value="-1"' . (get_request_var('limit_name') == -1 ? ' selected':'') . '>' . __esc('All', 'gridlimits') . '</option>';

							if (cacti_sizeof($limits)) {
								foreach ($limits as $limit) {
									print '<option value="' . html_escape($limit['limit_name']) . '"' . (get_request_var('limit_name') == $limit['limit_name'] && get_request_var('clusterid') == $limit['clusterid'] ? ' selected':'') . '>' . html_escape($limit['limit_name']) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input id='go' type='submit' value='<?php print __('Go', 'gridlimits');?>'>
							<input id='clear' type='button' value='<?php print __('Clear', 'gridlimits');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridlimits');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Records', 'gridlimits');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
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
			</form>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = 'grid_limits.php?header=false';
				strURL += '&action=usage';
				strURL += '&rows='       + $('#rows').val();
				strURL += '&clusterid='  + $('#clusterid').val();
				strURL += '&filter='     + $('#filter').val();
				strURL += '&limit_name=' + $('#limit_name').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = 'grid_limits.php?header=false';
				strURL += '&action=usage';
				strURL += '&clear=true';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#form_limit').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});

				$('.fa-search').click(function() {
					applyFilter();
				});
			});

			</script>
		</td>
	</tr>

	<?php

	html_end_box();
}

function drawUseRows($rows) {
	global $config;

	$sql = getUseSQL();

	# Add the row limits to the query
	$sql .= " LIMIT " . ($rows*($_REQUEST["page"]-1)) . "," . $rows;

	//cacti_log(str_replace("\n", ' ', str_replace("\t", '', $sql)));

	$data  = getUseRows($sql);
	$limit = get_request_var('limit_name');
	$cid   = get_request_var('clusterid');

	foreach ($data as $row) {
		form_alternate_row('line_' . $row['sequenceid']);

		$url = '<i class="tholdGlyphChart fas fa-chart-area" title="' . __('Show Usage History') . '"></i>' . $row['last_updated'];

		form_selectable_cell($url, $row['sequenceid']);
		form_selectable_cell($row['cluster_name'], $row['sequenceid']);
		form_selectable_cell($row['limit_name'], $row['sequenceid']);

		if (showLimitColumn('users', $limit, $cid)) {
			form_selectable_cell(filter_value($row['users'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('hosts', $limit, $cid)) {
			form_selectable_cell(filter_value($row['hosts'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('queues', $limit, $cid)) {
			form_selectable_cell(filter_value($row['queues'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('projects', $limit, $cid)) {
			form_selectable_cell(filter_value($row['projects'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('lic_projects', $limit, $cid)) {
			form_selectable_cell(filter_value($row['lic_projects'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('clusters', $limit, $cid)) {
			form_selectable_cell(filter_value($row['clusters'], get_request_var('filter')), $row['sequenceid']);
		}

		if (showLimitColumn('slots', $limit, $cid)) {
			form_selectable_cell($row['slots'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('mem', $limit, $cid)) {
			form_selectable_cell($row['mem'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('swp', $limit, $cid)) {
			form_selectable_cell($row['swp'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('tmp', $limit, $cid)) {
			form_selectable_cell($row['tmp'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('jobs', $limit, $cid)) {
			form_selectable_cell($row['jobs'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('fwd_tasks', $limit, $cid)) {
			form_selectable_cell($row['fwd_tasks'], $row['sequenceid'], '', 'right');
		}

		if (showLimitColumn('resources', $limit, $cid)) {
			form_selectable_cell(filter_value($row['resources'], get_request_var('filter')), $row['sequenceid'], '', 'text-align:right;white-space:pre-wrap');
		}

		form_end_row();
	}
}

function validateUseVariables() {
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
		'time_period' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '3'
		),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'last_updated',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'limit_name' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
		)
	);

	validate_store_request_vars($filters, 'sess_limit_usage');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => read_grid_config_option('default_grid')
		)
	);

	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function drawUseTable() {
	global $config;

	validateUseVariables();

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	drawUseFilter();

	$sql = 'SELECT COUNT(*) FROM (' . getUseSQL() . ') a';

	$total_rows = db_fetch_cell($sql);

	$headers = getUseTableHeaders();

	$nav = html_nav_bar($config['url_path'] . 'plugins/gridlimits/grid_limits.php?action=usage&filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($headers), __('Limits', 'gridlimits'), 'page', 'main');

	print "<div id='limitTab'>";

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	html_header_sort($headers, get_request_var('sort_column'), get_request_var('sort_direction'), 1, $config['url_path'] . 'plugins/gridlimits/grid_limits.php?action=usage');

    if ($total_rows) {
        drawUseRows($rows);
    } else {
        print '<tr><td colspan="' . cacti_sizeof($headers) . '"><em>' . __('No Matching Limit Records Found', 'gridlimits') . '</em></td></tr>';
    }

    html_end_box(false);

	if ($total_rows) {
		print $nav;
	}

	print '</div>';
}

function getUseSQL() {
	$sql_query = "SELECT glh.sequenceid, glh.last_seen AS last_updated,
		gc.clustername as cluster_name, glh.clusterid, glh.limit_name,
		IFNULL(glh.users, '-') AS users,
		IFNULL(glh.hosts, '-') AS hosts,
		IFNULL(glh.queues, '-') AS queues,
		IFNULL(glh.projects, '-') AS projects,
		IFNULL(glh.lic_projects, '-') AS lic_projects,
		IFNULL(glh.clusters, '-') AS clusters,
		CONCAT(slots_usage, '/', slots_limit) AS slots,
		CONCAT(mem_usage, '/', mem_limit, ' ', unit_for_limits) AS mem,
		CONCAT(swp_usage, '/', swp_limit, ' ', unit_for_limits) AS swp,
		CONCAT(tmp_usage, '/', tmp_limit, ' ', unit_for_limits) AS tmp,
		CONCAT(jobs_usage, '/', jobs_limit) AS jobs,
		IFNULL(resources, '-') AS resources,
		CONCAT(fwd_tasks_usage, '/', fwd_tasks_limit) AS fwd_tasks
		FROM grid_limits_history AS glh
		INNER JOIN grid_clusters AS gc
		ON glh.clusterid = gc.clusterid";

	# Set the WHERE clause for this query
	$sql_where = '';

	# Add the filter to the query
	if (get_request_var('limit_name') != '' && get_request_var('limit_name') != '-1') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' glh.limit_name = ' . db_qstr(get_request_var('limit_name'));
	}

	# Add the clusterid to the query
	if (get_request_var('clusterid') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . ' glh.clusterid = ' . get_request_var('clusterid');
	}

	# Add the the filter to the query
	if (get_request_var('filter') != "") {
		$filter = db_qstr('%' . get_request_var('filter') . '%');

		if ($sql_where != "") {
			$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . " (
				glh.users LIKE $filter OR
				glh.queues LIKE $filter OR
				glh.hosts LIKE $filter OR
				glh.projects LIKE $filter OR
				glh.lic_projects LIKE $filter OR
				glh.clusters LIKE $filter OR
				glh.resources LIKE $filter
			)";
		}
	}

	# Set the ORDER BY clause for this query
	$sql_order = get_order_string();

	$sql = "$sql_query $sql_where $sql_order";

	return $sql;
}

function getUseRows($sql_query) {
	return db_fetch_assoc($sql_query);
}

function getUseTableHeaders() {
	$headers = array(
		'last_updated' => array(
			'display' => __('Last Seen Date', 'gridlimits'),
			'sort'    => 'DESC'
		),
		'cluster_name' => array(
			'display' => __('Cluster Name', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'limit_name' => array(
			'display' => __('Limit Name', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'users' => array(
			'display' => __('Users', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'hosts' => array(
			'display' => __('Hosts', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'queues' => array(
			'display' => __('Queues', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'projects' => array(
			'display' => __('Projects', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'lic_projects' => array(
			'display' => __('License Project', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'clusters' => array(
			'display' => __('Clusters', 'gridlimits'),
			'sort'    => 'ASC'
		),
		'slots_usage' => array(
			'display' => __('Slots', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'mem_usage' => array(
			'display' => __('Memory', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'swp_usage' => array(
			'display' => __('Swap', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'tmp_usage' => array(
			'display' => __('Tmp', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'jobs_usage' => array(
			'display' => __('Jobs', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'fwd_tasks_usage' => array(
			'display' => __('Fwd Tasks', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'DESC'
		),
		'resources' => array(
			'display' => __('Resources', 'gridlimits'),
			'align'   => 'right',
			'sort'    => 'ASC'
		)
	);

	$limit = get_request_var('limit_name');
	$cid   = get_request_var('clusterid');

	foreach($headers as $name => $data) {
		if (!showLimitColumn($name, $limit, $cid)) {
			unset($headers[$name]);
		}
	}

	return $headers;
}

function drawUseDashboard() {
	global $title;

	$title = __('Limit Usage History', 'gridlimits');

	$time_period = array(
		1 => __esc('%d Hours', 2,  'gridlimits'),
		2 => __esc('%d Hours', 6,  'gridlimits'),
		3 => __esc('%d Hours', 12, 'gridlimits'),
		4 => __esc('%d Day',   1,  'gridlimits'),
		5 => __esc('%d Days',  2,  'gridlimits'),
		6 => __esc('%d Week',  1,  'gridlimits'),
		7 => __esc('%d Weeks', 2,  'gridlimits')
	);

	html_start_box($title, '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
			<form name='form_limit_graph'>
				<table class='filterTable'>
					<tr>
						<td>
							Time Period
						</td>
						<td>
							<select id='time_period' onChange='applyGraphFilterChange()'>
								<?php
								foreach ($time_period as $key => $value) {
									print '<option value="' . $key . '"' . (get_request_var('time_period') == $key ? ' selected':'') . '>' . $value . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
	<script type='text/javascript'>

	function applyGraphFilterChange() {
		if (sequenceid == null) {
			return;
		}

		var time_period = $('#time_period').val();

		$('#chartContainer').html('');

		$.ajax({
			url: urlPath+'plugins/gridlimits/grid_limits.php?action=getChartCount&sequenceid='+sequenceid,
			dataType: 'json',
			data: '',
			success: function(data, status, xhr) {
				for (var key in data) {
					if (data.hasOwnProperty(key)) {
						var val = data[key];

						// Get the historic data for this key
						renderChart(key, 'chartContainer', sequenceid, time_period);
					}
				}
			},
			error: function(xhr, status, error) {
				;
			}
		});
	}

	</script>

	<?php

	html_end_box(false);

	print "<tr>
		<td class='cactiTable'>
			<div id='chartContainer' style='text-align:center'>
				<table class='cactiTable'>
					<tr>
						<td>Select a limit to display</td>
					</tr>
				</table>
			</div>
		</td>
	</tr>";

	html_end_box(false);
}

function drawUseJavascript() {
	?>
	<script type='text/javascript'>
	var sequenceid;

	function renderChart(resource, container, sequenceid, time_period) {
		$('#'+container).append('<br/><div class="center" id="'+sequenceid+'_'+resource+'"></div><br/>');

		var chart = new FusionCharts({
			'type': 'msline',
        	'renderAt': sequenceid+'_'+resource,
        	'width': $('#main').width()-40,
        	'height': '500',
        	'dataFormat': 'jsonurl',
        	'dataSource': urlPath+'plugins/gridlimits/grid_limits.php?action=getData&sequenceid='+sequenceid+'&resource='+resource+'&time_period='+time_period
      	});

		chart.render();
	}

	$(function() {
		$('.selectable').on('click', function(event) {
			sequenceid = $(this).attr('id').replace('line_', '');

			$('.selectable').not('#line_' + sequenceid).removeClass('selected');

			var time_period = $('#time_period').val();

			$('#chartContainer').html('');

			$.ajax({
				url: urlPath+'plugins/gridlimits/grid_limits.php?action=getChartCount&sequenceid='+sequenceid,
				dataType: 'json',
				data: '',
				success: function(data, status, xhr) {
					for (var key in data) {
						if (data.hasOwnProperty(key)) {
							var val = data[key];

							// Get the historic data for this key
							renderChart(key, 'chartContainer', sequenceid, time_period);
						}
					}
				},
				error: function(xhr, status, error) {
					;
				}
			});
		});
	});
	</script>
	<?php
}
