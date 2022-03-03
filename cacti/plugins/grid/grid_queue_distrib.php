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
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/lib/rtm_plugins.php');

$title = __('IBM Spectrum LSF RTM - Host Queue Distribution', 'grid');

/* this page needs to run for a while */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

set_default_action();

if (isset_request_var('export')) {
	grid_export_records();
} elseif (isset_request_var('action')) {
	switch(get_request_var('action')) {
		case 'ajax_rtm_hgroups':
			grid_request_validation();
			$sql_where = '';

			if (get_request_var('clusterid') > 0) {
				$sql_where = 'clusterid = ' . get_request_var('clusterid');
			}

			rtm_autocomplete_ajax('grid_queue_distrib.php', 'hgroup', $sql_where);
			break;
		default:
			grid_view_bhosts();
			break;
	}
} else {
	grid_view_bhosts();
}

function grid_export_records() {
	grid_request_validation();

	header('Content-type: application/csv');
	header('Cache-Control: max-age=15');
	header('Content-Disposition: attachment; filename=grid_qdistrib_xport.csv');

	$queues      = array();
	$records     = get_records($sql_where, $queues);
	$new_records = array();
	if (cacti_sizeof($records)) {
		$i = 0;
		foreach ($records as $bhosts) {
			/* determine shared/dedicated/unused */
			$bhosts['type'] = 'Unused';
			if (cacti_sizeof($queues)) {
			foreach($queues as $queue) {
				if (isset($bhosts[$queue]) && $bhosts[$queue] > 0) {
					if ($bhosts['type'] == 'Unused') {
						$bhosts['type'] = 'Dedicated';
					} elseif ($bhosts['type'] == 'Dedicated') {
						$bhosts['type'] = 'Shared';
						break;
					}
				}
			}
			}

			if (get_request_var('utype') == -1) {
				$new_records[] = $bhosts;
			} elseif (get_request_var('utype') == -2 && $bhosts['type'] != 'Shared') {
				continue;
			} elseif (get_request_var('utype') == -3 && $bhosts['type'] != 'Dedicated') {
				continue;
			} elseif (get_request_var('utype') == -4 && $bhosts['type'] == 'Unused') {
				continue;
			} elseif (get_request_var('utype') == -5 && $bhosts['type'] != 'Unused') {
				continue;
			} else {
				$new_records[] = $bhosts;
			}

			if (get_request_var('filter') != '' && $bhosts['type'] == 'Unused') {
				continue;
			}
		}
	}
	$records = $new_records;

	if (get_request_var('hgroup') == '-2') {
		$output = 'Group Name,Cluster,Use Type,Max Jobs,Running';
		foreach($queues as $queue) {
			$output .= ',' . $queue;
		}
		print $output . '\n';

		$hgstats = array_rekey(db_fetch_assoc('SELECT groupName, SUM(maxJobs) AS maxJobs, SUM(numRun) AS numRun
			FROM grid_hostgroups AS ghg
			INNER JOIN grid_hosts AS gh
			ON ghg.host=gh.host AND ghg.clusterid=gh.clusterid
			GROUP BY groupName'), 'groupName', array('maxJobs', 'numRun'));

		$i = 0;
		if (cacti_sizeof($records) > 0) {
			foreach ($records as $bhosts) {
				$output  = '';
				$output .= '"' . $bhosts['groupName']   . '",';
				$output .= '"' . $bhosts['clustername'] . '",';
				$output .= '"' . $bhosts['type']        . '",';
				$output .= '"' . $hgstats[$bhosts['groupName']]['maxJobs'] . '",';
				$output .= '"' . $hgstats[$bhosts['groupName']]['numRun']  . '"';
				if (cacti_sizeof($queues)) {
				foreach($queues as $queue) {
					$output .= ($bhosts[$queue] == '0' ? ',0':',' . $bhosts[$queue]);
				}
				}
				print $output . "\n";
			}
		}
	} else {
		$output = 'Host,Cluster,Use Type,Max Jobs,Model,Type,Memory,Running,Status';

		if (read_grid_config_option('show_time_in_state')) {
			$output .= ',Time In State,TIS Seconds';
		}

		if (substr_count(get_request_var('hgroup'), 'QG-')) {
			$queue_groups = true;
			$qgroup     = str_replace('QG-','', get_request_var('hgroup'));
			$queue_list = db_fetch_assoc("SELECT * FROM grid_metadata_conf WHERE OBJECT_TYPE='queue-group'");
			if (cacti_sizeof($queue_list)) {
				foreach($queue_list as $ql) {
					switch($ql['DATA_TYPE']) {
					case 'display_name':
						$display_col = $ql['DB_COLUMN_NAME'];
						break;
					case 'queue_list':
						$queues      = $ql['DB_COLUMN_NAME'];
						break;
					}
				}

				$gq_sql_where = '';
				$gq_sql_params = array($qgroup);
				if (get_request_var('clusterid') != '0') {
					$gq_sql_where = ' AND CLUSTER_ID=?';
					$gq_sql_params[] = get_request_var('clusterid');
				}
				$group_queues = db_fetch_cell_prepared("SELECT $queues AS list
					FROM grid_metadata
					WHERE OBJECT_TYPE='queue-group'
					AND OBJECT_ID=?" .  $gq_sql_where, $gq_sql_params);

				$queues = array();
				if (strlen($group_queues)) {
					$group_queues = explode(' ', trim($group_queues));
					foreach($group_queues as $q) {
						$queues[$q] = $q;
					}
				}
			}
		}

		foreach($queues as $queue) {
			$output .= ',' . $queue;
		}
		print $output . "\n";

		$i = 0;
		if (cacti_sizeof($records) > 0) {
			foreach ($records as $bhosts) {
				$output  = '';
				$output .= '"' . $bhosts['host']        . '",';
				$output .= '"' . $bhosts['clustername'] . '",';
				$output .= '"' . $bhosts['type']        . '",';
				$output .= '"' . $bhosts['maxJobs']     . '",';
				$output .= '"' . $bhosts['hostModel']   . '",';
				$output .= '"' . $bhosts['hostType']    . '",';
				$output .= '"' . $bhosts['maxMem']      . '",';
				$output .= '"' . $bhosts['numRun']      . '",';
				$output .= '"' . $bhosts['status']      . '"';
				if (read_grid_config_option('show_time_in_state')) {
					$output .= ',"' . display_time_in_state($bhosts['time_in_state']) . '",' . $bhosts['time_in_state'];
				}
				if (cacti_sizeof($queues)) {
				foreach($queues as $queue) {
					$output .= ',' . (isset($bhosts[$queue]) && $bhosts[$queue] == 1 ? $bhosts['maxJobs']:0);
				}
				}
				print $output . "\n";
			}
		}
	}
}

function get_records(&$sql_where, &$queues) {
	global $grid_out_of_services;
	$sql_params = array();
	$qsql_params = array();

	/* user id sql where */
	if (get_request_var('clusterid') == '0') {
		/* Show all items */
	} else {
		$sql_where .= "WHERE (gh.clusterid=?)";
		$sql_params[] = get_request_var('clusterid');
	}

	/* host and queue group sql where */
	$queue_groups = false;
	if (is_numeric(get_request_var('hgroup')) && get_request_var('hgroup') < 0) {
		/* Show all items */
	} else {
		if (substr_count(get_request_var('hgroup'), 'QG-')) {
			$queue_groups = true;
			$qgroup     = str_replace("QG-","", get_request_var('hgroup'));
			$queue_list = db_fetch_assoc("SELECT * FROM grid_metadata_conf WHERE OBJECT_TYPE='queue-group'");
			if (cacti_sizeof($queue_list)) {
				foreach($queue_list as $ql) {
					switch($ql['DATA_TYPE']) {
					case 'display_name':
						$display_col = $ql['DB_COLUMN_NAME'];
						break;
					case 'queue_list':
						$queues      = $ql['DB_COLUMN_NAME'];
						break;
					}
				}

				$gq_sql_where = '';
				$gq_sql_params = array($qgroup);
				if (get_request_var('clusterid') != '0') {
					$gq_sql_where = ' AND CLUSTER_ID=?';
					$gq_sql_params[] = get_request_var('clusterid');
				}
				$group_queues = db_fetch_cell_prepared("SELECT $queues AS list
					FROM grid_metadata
					WHERE OBJECT_TYPE='queue-group'
					AND OBJECT_ID=?" .  $gq_sql_where, $gq_sql_params);

				if (strlen($group_queues)) {
					$group_queues = explode(" ", trim($group_queues));
				}
			}
		} else {
			if (get_request_var('clusterid') == 0) {
				$hosts = db_fetch_assoc_prepared("SELECT host
					FROM grid_hostgroups
					WHERE groupName=?", array(get_request_var('hgroup')));
			} else {
				$hosts = db_fetch_assoc_prepared("SELECT host
					FROM grid_hostgroups
					WHERE groupName=?
					AND clusterid=?", array(get_request_var('hgroup'), get_request_var('clusterid')));
			}

			if (cacti_sizeof($hosts)) {
				$hgroup_hosts = '';
				$num_hosts = 0;

				foreach($hosts as $host) {
					if ($num_hosts == 0) {
						$hgroup_hosts .= "'" . $host["host"] . "'";
					} else {
						$hgroup_hosts .= ", '" . $host["host"] . "'";
					}

					$num_hosts++;
				}

				if (strlen($sql_where)) {
					$sql_where .= " AND (gh.host IN ($hgroup_hosts))";
				} else {
					$sql_where  = "WHERE (gh.host IN ($hgroup_hosts))";
				}
			}
		}
	}

	/* hostType sql where */
	if (get_request_var('type') == "-1") {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND (hostType=?)";
			$sql_params[] = get_request_var('type');
		} else {
			$sql_where = "WHERE (hostType=?)";
			$sql_params[] = get_request_var('type');
		}
	}

	/* hostModel sql where */
	if (get_request_var('model') == "-1") {
		/* Show all items */
	} else {
		if (strlen($sql_where)) {
			$sql_where .= " AND (hostModel=?)";
			$sql_params[] = get_request_var('model');
		} else {
			$sql_where = "WHERE (hostModel=?)";
			$sql_params[] = get_request_var('model');
		}
	}

	if (get_request_var('clusterid') <= 0) {
		$qsql_where = '';
	} else {
		$qsql_where = 'WHERE gqd.clusterid=?';
		$qsql_params[] = get_request_var('clusterid');
	}

	/* filter sql where */
	if (!strlen(get_request_var('filter'))) {
		/* Show all items */
	} else {
		$values = explode(" ", trim(get_request_var('filter')));

		$qsql_where .= strlen($qsql_where) ? " AND (":"WHERE (";
		$i = 0;
		foreach($values as $value) {
			if (get_request_var('clusterid') == 0) {
				$qsql_where .= ($i == 0 ? "":" OR ") . "(queuename LIKE ?)";
				$qsql_params[] = '%'. $value . '%';
			} else {
				$qsql_where .= ($i == 0 ? "":" OR ") . "(queuename LIKE ? AND clusterid=?)";
				$qsql_params[] = '%'. $value . '%';
				$qsql_params[] = get_request_var('clusterid');
			}
			$i++;
		}
		$qsql_where .= ")";
	}

	$aqueues = array_rekey(db_fetch_assoc("SELECT DISTINCT queuename
		FROM grid_queues
		ORDER BY queuename"), "queuename", "queuename");

	$queues = array_rekey(db_fetch_assoc_prepared("SELECT DISTINCT queuename
		FROM grid_queues AS gqd
		$qsql_where
		ORDER BY queuename", $qsql_params), "queuename", "queuename");

	if (isset_request_var('rebuild') || read_config_option('grid_queue_distrib_lastrun', true) < (time()-86400)) {
		rebuild_queue_distrib_table();
	}

	$sql_order = get_order_string();

	if (get_request_var('hgroup') == "-2") {
		$sql = 'SELECT gqd.clusterid, gc.clustername, ghg.groupName';
		$columns = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM grid_queues_distrib"), 'Field', 'Field');
		$i=0;
		foreach($queues as $queue) {
			if(in_array($queue, $columns)) {
				$sql .= ", SUM(CASE WHEN gqd.`$queue` = 1 THEN gh.maxJobs ELSE 0 END) AS `$queue`";
				$i++;
			}
		}

		$sql .= " FROM grid_clusters AS gc
			INNER JOIN grid_queues_distrib AS gqd
			ON gc.clusterid=gqd.clusterid
			INNER JOIN grid_hosts AS gh
			ON gh.host=gqd.host AND gh.clusterid=gqd.clusterid
			INNER JOIN grid_hostinfo AS ghi
			ON gh.host=ghi.host AND gh.clusterid=ghi.clusterid
			INNER JOIN grid_hostgroups AS ghg
			ON ghi.host=ghg.host AND ghi.clusterid=ghg.clusterid
			$sql_where
			GROUP BY clusterid, groupName
			ORDER BY groupName ASC";

		return db_fetch_assoc_prepared($sql, $sql_params);
	} elseif (!$queue_groups) {
		$sql = "SELECT gqd.*, gc.clustername,
			gh.maxJobs, gh.numRun, ghi.maxMem, ghi.hostModel, ghi.hostType,
			gh.time_in_state, gh.status, gh.hCtrlMsg
			FROM grid_clusters AS gc
			INNER JOIN grid_queues_distrib AS gqd
			ON gc.clusterid=gqd.clusterid
			INNER JOIN grid_hosts AS gh
			ON gh.clusterid=gqd.clusterid AND gh.host=gqd.host
			INNER JOIN grid_hostinfo AS ghi
			ON gh.host=ghi.host AND gh.clusterid=ghi.clusterid
			$sql_where
			ORDER BY gh.host ASC";

		return db_fetch_assoc_prepared($sql, $sql_params);
	} elseif ($queue_groups) {
		$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . "gh.host IN (SELECT DISTINCT host FROM grid_queues_hosts WHERE queue IN ('" . implode("','", $group_queues) . "'))";

		$sql = "SELECT gqd.*, gc.clustername,
			gh.maxJobs, gh.numRun, ghi.maxMem, ghi.hostModel, ghi.hostType,
			gh.time_in_state, gh.status, gh.hCtrlMsg
			FROM grid_clusters AS gc
			INNER JOIN grid_queues_distrib AS gqd
			ON gc.clusterid=gqd.clusterid
			INNER JOIN grid_hosts AS gh
			ON gh.clusterid=gqd.clusterid AND gh.host=gqd.host
			INNER JOIN grid_hostinfo AS ghi
			ON gh.host=ghi.host AND gh.clusterid=ghi.clusterid
			$sql_where
			ORDER BY gh.host ASC";

		return db_fetch_assoc_prepared($sql, $sql_params);
	}
}

function bhostsFilter() {
	global $config, $grid_rows_selector, $grid_refresh_interval;

	$filterChange = "applyLoadFilterChange(document.form_grid_view_bhosts)"; ?>

	<script type='text/javascript'>
	$(function() {
		$('#main').show();
		$.get('grid_dailystats.php?ajaxstats=1', function(data) {
			$('#stats_content').html(data);
			$('#status').hide();
			applySkin();
			applySkinRTM();
		});
	});
	</script>

	<tr class='odd'>
		<td>
		<form id='form_grid' action='grid_queue_distrib.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'grid');?>
					</td>
					<td>
						<select id='clusterid'>
							<option value='0'<?php if (get_request_var('clusterid') == '0') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php
							$clusters = db_fetch_assoc("SELECT * from grid_clusters ORDER BY clustername");
							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									print '<option value="' . $cluster['clusterid'] .'"'; if (get_request_var('clusterid') == $cluster['clusterid']) { print ' selected'; } print '>' . $cluster['clustername'] . '</option>';
								}
							}
							?>
						</select>
					</td>
					<?php print html_autocomplete_filter('grid_queue_distrib.php', __('Group', 'grid'), 'hgroup', get_request_var('hgroup'), 'applyFilter', get_request_var('clusterid') > 0 ? 'clusterid = ' . get_request_var('clusterid') : '', array('-1' => __('All', 'grid'), '-2' => __('HG: Roll-Up', 'grid')));?>
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
						<span>
							<input type='submit' id='go' value='<?php print __esc('Go', 'grid');?>' title='<?php print __esc('Search', 'grid');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'grid');?>' title='<?php print __esc('Clear Filters', 'grid');?>'>
							<input type='button' id='export' value='<?php print __esc('Export', 'grid');?>' title='<?php print __esc('Export Records', 'grid');?>'>
							<input type='button' id='rebuild' value='<?php print __esc('Rebuild', 'grid');?>' title='<?php print __esc('Rebuild Distribution Table', 'grid');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Model', 'grid');?>
					</td>
					<td>
						<select id='model'>
							<option value='-1'<?php if (get_request_var('model') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php

							if (get_request_var('clusterid') == 0) {
								$models = db_fetch_assoc("SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									ORDER BY hostModel");
							} else {
								$models = db_fetch_assoc_prepared("SELECT DISTINCT hostModel
									FROM grid_hostinfo
									WHERE hostModel!='N/A'
									AND hostModel NOT LIKE '%UNKNOWN%'
									AND clusterid = ?
									ORDER BY hostModel",
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
						<?php print __('Type', 'grid');?>
					</td>
					<td>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
							<?php

							if (get_request_var('clusterid') == 0) {
								$types = db_fetch_assoc("SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									ORDER BY hostType");
							} else {
								$types = db_fetch_assoc_prepared("SELECT DISTINCT hostType
									FROM grid_hostinfo
									WHERE hostType <> 'FLOATING'
									AND hostType NOT LIKE 'U%'
									AND clusterid = ?
									ORDER BY hostType",
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
						<?php print __('Records', 'grid');?>
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
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('UType', 'grid');?>
					</td>
					<td>
						<select id='utype'>
						<option value='-1'<?php if (get_request_var('utype') == '-1') {?> selected<?php }?>><?php print __('All', 'grid');?></option>
						<option value='-2'<?php if (get_request_var('utype') == '-2') {?> selected<?php }?>><?php print __('Shared', 'grid');?></option>
						<option value='-3'<?php if (get_request_var('utype') == '-3') {?> selected<?php }?>><?php print __('Dedicated', 'grid');?></option>
						<option value='-4'<?php if (get_request_var('utype') == '-4') {?> selected<?php }?>><?php print __('Used', 'grid');?></option>
						<option value='-5'<?php if (get_request_var('utype') == '-5') {?> selected<?php }?>><?php print __('Unused', 'grid');?></option>
						</select>
					</td>
					<td>
						<?php print __('Search', 'grid');?>
					</td>
					<td>
						<input type='text' id='filter' size='45' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' id='page' value='<?php print get_request_var('page');?>'>
		</form>
		</td>
	</tr>
	<?php
}

function grid_request_validation() {
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
		'utype' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'refresh' => array(
			'filter' => FILTER_CALLBACK,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'type' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'model' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'hgroup' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
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
			'default' => 'numRun',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_gqd');

	$filters = array(
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => read_grid_config_option('default_grid')
			)
	);
	validate_store_request_vars($filters, 'sess_grid');
	/* ================= input validation ================= */
}

function grid_view_bhosts() {
	global $title, $report, $grid_search_types, $grid_rows_selector, $grid_refresh_interval, $minimum_user_refresh_intervals, $config, $grid_host_control_actions;
	$sql_params = array();

	grid_request_validation();

	grid_set_minimum_page_refresh();

	general_header();

	?>
	<script type="text/javascript">

	function applyFilter() {
		strURL  = 'grid_queue_distrib.php?header=false';
		strURL += '&clusterid=' + $('#clusterid').val();
		strURL += '&filter='    + $('#filter').val();
		strURL += '&type='      + $('#type').val();
		strURL += '&utype='     + $('#utype').val();
		strURL += '&model='     + $('#model').val();
		strURL += '&hgroup='    + encodeURIComponent($('#hgroup').val());
		strURL += '&refresh='   + $('#refresh').val();
		strURL += '&rows='      + $('#rows').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'grid_queue_distrib.php?clear=true&header=false';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_grid').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#clusterid, #hgroup, #refresh, #model, #type, #utype, #filter, #rows').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
		$('#rebuild').click(function() {
			strURL  = 'grid_queue_distrib.php?header=false';
			strURL += '&clusterid=' + $('#clusterid').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&type=' + $('#type').val();
			strURL += '&utype=' + $('#utype').val();
			strURL += '&model=' + $('#model').val();
			strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
			strURL += '&refresh=' + $('#refresh').val();
			strURL += '&rows=' + $('#rows').val();
			strURL += '&rebuild=Rebuild';
			loadPageNoHeader(strURL);
		});
		$('#export').click(function() {
			strURL  = 'grid_queue_distrib.php?header=false';
			strURL += '&clusterid=' + $('#clusterid').val();
			strURL += '&filter=' + $('#filter').val();
			strURL += '&type=' + $('#type').val();
			strURL += '&utype=' + $('#utype').val();
			strURL += '&model=' + $('#model').val();
			strURL += '&hgroup=' + encodeURIComponent($('#hgroup').val());
			strURL += '&refresh=' + $('#refresh').val();
			strURL += '&rows=' + $('#rows').val();
			strURL += '&export=Export';
			document.location = strURL;
			Pace.stop();
		});
	});
	</script>
	<?php

	$sql_where = '';

	if (get_request_var('rows') == -1) {
		$rows = read_grid_config_option('grid_records');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$queues  = array();
	$records = get_records($sql_where, $queues);

	$debug_log = nl2br(debug_log_return('grid_admin'));

	if (!empty($debug_log)) {
		debug_log_clear('grid_admin');
		?>
		<table class='cactiTable'>
			<tr>
				<td class='debug'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box(__('Queue Host Distribution Matrix', 'grid'), '100%', '', '3', 'center', '');
	bhostsFilter();
	html_end_box();

	$new_records = array();
	if (!empty($records)) {
		$i = 0;
		foreach ($records as $bhosts) {
			/* determine shared/dedicated/unused */
			$bhosts['type'] = 'Unused';
			if (cacti_sizeof($queues)) {
			foreach($queues as $queue) {
				if (isset($bhosts[$queue]) && $bhosts[$queue] > 0) {
					if ($bhosts['type'] == 'Unused') {
						$bhosts['type'] = 'Dedicated';
					} elseif ($bhosts['type'] == 'Dedicated') {
						$bhosts['type'] = 'Shared';
						break;
					}
				}
			}
			}

			if (get_request_var('utype') == -1) {
				/* all good, all records */
			} elseif (get_request_var('utype') == -2 && $bhosts['type'] != 'Shared') {
				continue;
			} elseif (get_request_var('utype') == -3 && $bhosts['type'] != 'Dedicated') {
				continue;
			} elseif (get_request_var('utype') == -4 && $bhosts['type'] == 'Unused') {
				continue;
			} elseif (get_request_var('utype') == -5 && $bhosts['type'] != 'Unused') {
				continue;
			}

			if (get_request_var('filter') != '' && $bhosts['type'] == 'Unused') {
				continue;
			} else {
				$new_records[] = $bhosts;
			}
		}
	}
	$records = $new_records;

	$total_rows = cacti_sizeof($records);

	if (read_grid_config_option('show_time_in_state')) {
		$colspan = cacti_sizeof($queues) + 11;
		$totspan = 11;
	} else {
		$colspan = cacti_sizeof($queues) + 10;
		$totspan = 10;
	}

	/* generate page list */
	$nav = html_nav_bar('grid_queue_distrib.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $colspan, __('Queues', 'grid'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	if (get_request_var('hgroup') == "-2") {
		print "<tr class='tableHeader'>";
		print "<th class='bottom left tableSubHeaderColumn'>" . __('Actions', 'grid') . "</th>
			<th class='bottom left tableSubHeaderColumn'>" . __('Group Name', 'grid') . "</th>
			<th class='bottom left tableSubHeaderColumn'>" . __('Cluster', 'grid') . "</th>
			<th class='bottom left tableSubHeaderColumn'>" . __('Use Type', 'grid') . "</th>
			<th class='bottom right tableSubHeaderColumn'>" . __('Max %s', format_job_slots(), 'grid') . "</th>
			<th class='bottom right tableSubHeaderColumn'>" . __('Running', 'grid') . "</th>";

		foreach($queues as $queue) {
			$output = '';
			for($i = 0; $i < strlen($queue); $i++) {
				$output .= (strlen($output) ? '<br>':'') . strtoupper($queue[$i]);
			}
			print "<th style='width:20px;' class='bottom center textSubHeaderDark'><span class='bottom'>$output</span></th>";
		}
		print '</tr>';

		$hgstats = array_rekey(db_fetch_assoc('SELECT groupName, SUM(maxJobs) AS maxJobs, SUM(numRun) AS numRun
			FROM grid_hostgroups AS ghg
			INNER JOIN grid_hosts AS gh
			ON ghg.host=gh.host AND ghg.clusterid=gh.clusterid
			GROUP BY groupName'), 'groupName', array('maxJobs', 'numRun'));

		$i = 0;
		if (cacti_sizeof($records) > 0) {
			foreach ($records as $bhosts) {
				if ($i < (get_request_var('page')-1) * $rows) {
					$i++;
					continue;
				} elseif ($i >= get_request_var('page') * $rows) {
					break;
				}

				$cacti_host = db_fetch_cell_prepared('SELECT cacti_host
					FROM grid_clusters
					WHERE clusterid= ?',
					array($bhosts['clusterid']));

				if (isset($cacti_host)) {
					$local_graph_ids = db_fetch_assoc_prepared('SELECT DISTINCT graph_local.id
						FROM host_snmp_cache
						INNER JOIN graph_local
						ON graph_local.snmp_query_id=host_snmp_cache.snmp_query_id
						AND host_snmp_cache.host_id=graph_local.host_id
						WHERE graph_local.host_id = ?
						AND graph_local.snmp_index = ?
						AND host_snmp_cache.field_name="groupName"',
						array($cacti_host, $bhosts['groupName']));

					if (cacti_sizeof($local_graph_ids)) {
						$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

						foreach($local_graph_ids as $graph) {
							$graph_select .= $graph['id'] . '%2C';
						}
					} else {
						unset($graph_select);
					}
				} else {
					unset($graph_select);
				}

				$bhosts_line = str_replace('.', '@', $bhosts['groupName'].':'.$bhosts['clusterid']);

				form_alternate_row();
				?>
				<td class='nowrap' style='width:1%'>
					<a href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bhosts.php?reset=1&clusterid=' . $bhosts['clusterid'] . '&ajax_hgroup_query=' . $bhosts['groupName'] . '&hgroup=' . $bhosts['groupName']);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_hosts.gif' alt='' title='<?php print __esc('View Batch Hosts', 'grid');?>'></a>
					<a href='<?php print html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $bhosts['clusterid'] . '&ajax_hgroup_query=' . $bhosts['groupName'] . '&hgroup=' . $bhosts['groupName'] .'&status=RUNNING&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_jobs.gif' alt='' title='<?php print __esc('View Active Jobs', 'grid');?>'></a>
					<?php if (isset($graph_select)) {?>
					<a href='<?php print html_escape($config['url_path'] . 'graph_view.php?' . $graph_select);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Group Graphs', 'grid');?>'></a>
					<?php }?>
				</td>
				<?php

				$url = html_escape($config['url_path'] . 'plugins/grid/grid_queue_distrib.php' .
					'?reset=1&clusterid=' . $bhosts['clusterid'] .
					'&hgroup=' . $bhosts['groupName'] .
					'&filter=' . get_request_var('filter') . '&page=1');

				form_selectable_cell_metadata('simple', 'host-group', $bhosts['clusterid'], $bhosts['groupName'], '', '', html_escape($bhosts['groupName']), false, $url);
				print "<td align='left'>"  . $bhosts['clustername'] . '</td>';
				print "<td align='left'>"  . $bhosts['type'] . '</td>';
				print "<td align='right'>" . $hgstats[$bhosts['groupName']]['maxJobs'] . '</td>';
				print "<td align='right'>" . $hgstats[$bhosts['groupName']]['numRun']  . '</td>';

				if (cacti_sizeof($queues)) {
					foreach($queues as $queue) {
						print "<td align='center'>" . ($bhosts[$queue] == '0' ? ' ':$bhosts[$queue]) . '</td>';
					}
				}

				form_end_row();
			}
		} else {
			print '<tr><td colspan="11"><em>' . __('No Host Groups Found for Queue Distribution', 'grid') . '</em></td></tr>';
		}
	} else {
		print "<tr class='tableHeader'>";
		print "<th class='left bottom tableSubHeaderColumn'>" . __('Actions', 'grid') . "</th>
			<th class='left bottom tableSubHeaderColumn'>" . __('Host', 'grid') . "</th>
			<th class='left bottom tableSubHeaderColumn'>" . __('Cluster', 'grid') . "</th>
			<th class='left bottom tableSubHeaderColumn'>" . __('Use Type', 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Max %s', format_job_slots(), 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Model', 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Type', 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Memory', 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Running', 'grid') . "</th>
			<th class='right bottom tableSubHeaderColumn'>" . __('Status', 'grid') . '</th>';

		if (read_grid_config_option('show_time_in_state')) {
			print "<th class='right bottom tableSubHeaderColumn'>" . __('TIS', 'grid') . '</th>';
		}

		if (substr_count(get_request_var('hgroup'), 'QG-')) {
			$queue_groups = true;
			$qgroup     = str_replace('QG-','', get_request_var('hgroup'));
			$queue_list = db_fetch_assoc("SELECT * FROM grid_metadata_conf WHERE OBJECT_TYPE='queue-group'");
			if (cacti_sizeof($queue_list)) {
				foreach($queue_list as $ql) {
					switch($ql['DATA_TYPE']) {
					case 'display_name':
						$display_col = $ql['DB_COLUMN_NAME'];
						break;
					case 'queue_list':
						$queues      = $ql['DB_COLUMN_NAME'];
						break;
					}
				}

				$gq_sql_where = '';
				$gq_sql_params = array($qgroup);
				if (get_request_var('clusterid') != '0') {
					$gq_sql_where = ' AND CLUSTER_ID=?';
					$gq_sql_params[] = get_request_var('clusterid');
				}
				$group_queues = db_fetch_cell_prepared("SELECT $queues AS list
					FROM grid_metadata
					WHERE OBJECT_TYPE='queue-group'
					AND OBJECT_ID=?" .  $gq_sql_where, $gq_sql_params);

				$queues = array();
				if (strlen($group_queues)) {
					$group_queues = explode(' ', trim($group_queues));
					foreach($group_queues as $q) {
						$queues[$q] = $q;
					}
				}
			}
		}

		foreach($queues as $queue) {
			$output = '';
			for($i = 0; $i < strlen($queue); $i++) {
				$output .= (strlen($output) ? '<br>':'') . strtoupper($queue[$i]);
			}
			print "<th class='bottom center textSubHeaderDark'><span class=''>$output</span></th>";
		}
		print '</tr>';

		$i = 0;
		if (cacti_sizeof($records) > 0) {
			foreach ($records as $bhosts) {
				if ($i < (get_request_var('page')-1) * $rows) {
					$i++;
					continue;
				} elseif ($i >= get_request_var('page') * $rows) {
					break;
				}

				$host_id = db_fetch_cell_prepared('SELECT id
					FROM host
					WHERE clusterid = ?
					AND hostname = ?',
					array($bhosts['clusterid'], $bhosts['host']));

				if (!empty($host_id)) {
					$host_graphs = db_fetch_cell_prepared('SELECT count(*)
						FROM graph_local
						WHERE host_id = ?',
						array($host_id));
				} else {
					$host_graphs = 0;
				}

				form_alternate_row();
				?>
				<td classs='nowrap' style='width:1%'>
					<?php if (grid_checkouts_enabled() && db_fetch_cell_prepared("SELECT COUNT(*) FROM lic_services_feature_details WHERE hostname=?", array($bhosts['host']))) {?>
					<a href='<?php print html_escape($config['url_path'] . 'plugins/license/lic_checkouts.php?reset=1&host=' . $bhosts['host'] . '&page=1');?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_checkouts.gif' alt='' title='<?php print __esc('View License Checkouts');?>'></a>
					<?php } if ($host_graphs > 0) {?><a href='<?php print html_escape($config['url_path'] . 'graph_view.php?action=preview&graph_template_id=-1&rfilter=&host_id=' . $host_id);?>'><img src='<?php print $config['url_path'];?>plugins/grid/images/view_graphs.gif' alt='' title='<?php print __esc('View Host Graphs');?>'></a><?php }?>
					<?php api_plugin_hook_function('grid_bhost_action_insert', $bhosts); ?>
				</td>
				<?php

				$url = html_escape($config['url_path'] . 'plugins/grid/grid_bjobs.php?action=viewlist&reset=1&clusterid=' . $bhosts['clusterid'] .'&ajax_host_query=' . $bhosts['host'] . '&exec_host=' . $bhosts['host'] . '&status=RUNNING&page=1');

				form_selectable_cell_metadata('simple', 'host', $bhosts['clusterid'], $bhosts['host'], '', '', html_escape($bhosts['host']), true, $url);
				print "<td align='left'>" . $bhosts['clustername'] . '</td>';
				print "<td align='left'>"  . $bhosts['type'] . '</td>';
				print "<td align='right'>" . ($bhosts['maxJobs'] > 0 ? number_format_i18n($bhosts['maxJobs']):'-') . '</td>';
				print "<td align='right'>" . $bhosts['hostModel']  . '</td>';
				print "<td align='right'>" . $bhosts['hostType']  . '</td>';
				print "<td align='right'>" . display_memory($bhosts['maxMem'])  . '</td>';
				print "<td align='right'>" . number_format_i18n($bhosts['numRun'])  . '</td>';
				print "<td align='right'>" . $bhosts['status']  . '</td>';

				if (read_grid_config_option('show_time_in_state')) {
					print "<td class='nowrap right'>" . display_time_in_state($bhosts['time_in_state']) . '</td>';
				}

				if (cacti_sizeof($queues)) {
					foreach($queues as $queue) {
						print "<td align='center'>" . (isset($bhosts[$queue]) && $bhosts[$queue] == 1 ? $bhosts['maxJobs']:'') . '</td>';
					}
				}
				form_end_row();
			}

			/* print totals by queue at the bottom */
			if (get_request_var('clusterid') != '0') {
				$sql_where = 'WHERE gh.clusterid=?';
				$sql_params[] = get_request_var('clusterid');
			} else {
				$sql_where = '';
			}

			$qht = array_rekey(db_fetch_assoc_prepared("SELECT queue, SUM(IF(ghi.maxCpus>gh.maxJobs, ghi.maxCpus, gh.maxJobs)) AS slots
				FROM grid_queues_hosts AS gqh
				INNER JOIN grid_hostinfo AS ghi
				ON ghi.host=gqh.host AND ghi.clusterid=gqh.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghi.host=gh.host AND ghi.clusterid=gh.clusterid
				$sql_where
				GROUP BY queue", $sql_params), 'queue', 'slots');

			echo "<tr><td  style='border-top:thin solid black;' colspan='" . $totspan . "'><b><i>" . __('Totals', 'grid') . "</i></b></td>";

			if (cacti_sizeof($queues)) {
				foreach($queues as $queue) {
					print "<td  style='border-top:thin solid black;' align='center'><b><i>" . (isset($qht[$queue]) && $qht[$queue] > 0 ? number_format($qht[$queue]):'-') . '</i></b></td>';
				}
			}

			echo '</tr>';
		} else {
			print '<tr><td colspan="11"><em>' . __('No Hosts Found for Queue Distribution', 'grid') . '</em></td></tr>';
		}
	}
	html_end_box(false);

	html_start_box('', '100%', '', '3', 'center', '');

	print '<tr><td>' . __('Table Last Rebuilt : %s', date("Y-m-d H:i:s", read_config_option('grid_queue_distrib_lastrun', true)), 'grid') . '</td></tr>';
	print '<tr><td><b>' . __('NOTE:', 'grid') . '</b> ' . __('To view a summary report of used and total slot counts by Host Group, select \'HG: Roll-Up\' from the Group dropdown.  You may also search for groups of queues by entering values separated by a space.', 'grid') . '</td></tr>';

	html_end_box();

	api_plugin_hook('grid_page_bottom');

	bottom_footer();
}

