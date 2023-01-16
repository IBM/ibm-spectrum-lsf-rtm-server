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

chdir('../../');
include('./include/auth.php');
include('./plugins/disku/include/disku_functions.php');

/* set default action */
set_default_action();

general_header();
disku_orgview();
bottom_footer();

function disku_orgview() {
	global $config, $disku_rows_selector;
	$sql_params2 = array();

	/* ================= input validation and session storage ================= */

	/* clean up level1 */
	if (isset_request_var('level1')) {
		if (!is_numeric(get_request_var('level1'))) {
			set_request_var('level1', sanitize_search_string(base64url_decode(get_request_var('level1'))));
		}
	}

	/* clean up level2 */
	if (isset_request_var('level2')) {
		if (!is_numeric(get_request_var('level2'))) {
			set_request_var('level2', sanitize_search_string(base64url_decode(get_request_var('level2'))));
		}
	}

	/* clean up level3 */
	if (isset_request_var('level3')) {
		if (!is_numeric(get_request_var('level3'))) {
			set_request_var('level3', sanitize_search_string(base64url_decode(get_request_var('level3'))));
		}
	}

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
		'level1' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'level2' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-2',
			'options' => array('options' => 'sanitize_search_string')
			),
		'level3' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-2',
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
			'default' => 'size',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_disku_orgview');
	/* ================= input validation ================= */

	$level1col  = trim(read_config_option('disku_level1'));
	$level2col  = trim(read_config_option('disku_level2'));
	$level3col  = trim(read_config_option('disku_level3'));
	$selectcols = 'OBJECT_ID';

	if (!empty($level1col)) {
		$level1 = db_fetch_cell_prepared("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME=?", array($level1col));
		$selectcols  .= ", $level1col";
	} else {
		$level1 = html_escape('Undefined (Set your Organizational Hierarchy in Console > Settings > RTM Pugins)', 'disku');
	}

	if (!empty($level2col)) {
		$level2 = db_fetch_cell_prepared("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME=?", array($level2col));
		$selectcols  .= ", $level2col";
	} else {
		$level2 = __('Undefined', 'disku');
	}

	if (!empty($level3col)) {
		$level3 = db_fetch_cell_prepared("SELECT DISPLAY_NAME
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'
			AND DB_COLUMN_NAME=?", array($level3col));
		$selectcols  .= ", $level3col";
	} else {
		$level3 = __('Undefined', 'disku');
	}

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	html_start_box(__('Organization View', 'disku'), '100%', '', '3', 'center', '');

	$sql_where = '';
	$group_by  = '';

	$users = get_orgview($selectcols, $sql_where, $group_by, true, $rows, $sql_params2);

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>
			function applyFilter() {
				strURL = 'disku_orgview.php?header=false&action=view';
				if ($('#level1') != undefined) {
					strURL += '&level1=' + $('#level1').val();
				}
				if ($('#level2') != undefined) {
					strURL += '&level2=' + $('#level2').val();
				}
				if ($('#level3') != undefined) {
					strURL += '&level3=' + $('#level3').val();
				}
				strURL += '&rows=' + $('#rows').val();
				strURL += '&filter=' + $('#filter').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'disku_orgview.php?header=false&clear=true&action=view';
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#formname').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#filter, #rows, #level1, #level2, #level3').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});
			</script>
			<form id='formname' action='disku_orgview.php'>
			<table class='filterTable'>
				<tr class='odd'>
					<?php if (!empty($level1col)) { ?>
					<td>
						<?php print $level1;?>
					</td>
					<td width='1'>
						<select id='level1'>
							<option value='-1'<?php if (get_request_var('level1') == '-1') {?> selected<?php }?>><?php print __('All' , 'disku');?></option>
							<option value='-2'<?php if (get_request_var('level1') == '-2') {?> selected<?php }?>><?php print __('N/A' , 'disku');?></option>
							<?php
							if (!empty($level1col)) {
								$sql_params = array();
								$l1w = "";
								if (!is_numeric(get_request_var('level2'))) {
									$l1w = "AND $level2col=?";
									$sql_params[] = get_request_var('level2');
								}

								if (!is_numeric(get_request_var('level3'))) {
									$l1w .= " AND $level3col=?";
									$sql_params[] = get_request_var('level3');
								}
								$members = db_fetch_assoc_prepared("SELECT DISTINCT $level1col
									FROM grid_metadata AS gm
									INNER JOIN disku_users_totals AS ut
									ON gm.OBJECT_ID=ut.user
									WHERE ut.delme=0 AND OBJECT_TYPE='user'
									AND $level2col IS NOT NULL
									$l1w
									ORDER BY $level1col", $sql_params);

								if (cacti_sizeof($members)) {
									foreach ($members AS $m) {
										print "<option value='" . base64url_encode($m[$level1col]) . "'"; if (get_request_var('level1') == $m[$level1col]) { print ' selected'; } print '>' . html_escape($m[$level1col]) . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<?php } ?>
					<?php if (!empty($level2col)) { ?>
					<td>
						<?php print $level2;?>
					</td>
					<td width='1'>
						<select id='level2'>
							<option value='-1'<?php if (get_request_var('level2') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<option value='-2'<?php if (get_request_var('level2') == '-2') {?> selected<?php }?>><?php print __('N/A', 'disku');?></option>
							<?php
							if (!empty($level2col)) {
								$sql_params = array();
								$l2w = "";
								if (!is_numeric(get_request_var('level1'))) {
									$l2w = "AND $level1col=?";
									$sql_params[] = get_request_var('level1');
								}

								if (!is_numeric(get_request_var('level3'))) {
									$l2w .= " AND $level3col=?";
									$sql_params[] = get_request_var('level3');
								}

								$members = db_fetch_assoc_prepared("SELECT DISTINCT $level2col
									FROM grid_metadata AS gm
									INNER JOIN disku_users_totals AS ut
									ON gm.OBJECT_ID=ut.user
									WHERE ut.delme=0 AND OBJECT_TYPE='user'
									AND $level2col IS NOT NULL
									$l2w
									ORDER BY $level2col", $sql_params);

								if (cacti_sizeof($members)) {
									foreach ($members AS $m) {
										print "<option value='" . base64url_encode($m[$level2col]) . "'"; if (get_request_var('level2') == $m[$level2col]) { print ' selected'; } print '>' . html_escape($m[$level2col]) . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<?php } ?>
					<?php if (!empty($level3col)) { ?>
					<td>
						<?php print $level3;?>
					</td>
					<td width='1'>
						<select id='level3'>
							<option value='-1'<?php if (get_request_var('level3') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<option value='-2'<?php if (get_request_var('level3') == '-2') {?> selected<?php }?>><?php print __('N/A', 'disku');?></option>
							<?php
							if (!empty($level3col)) {
								$sql_params = array();
								$l3w = "";
								if (!is_numeric(get_request_var('level1'))) {
									$l3w = "AND $level1col=?";
									$sql_params[] = get_request_var('level1');
								}

								if (!is_numeric(get_request_var('level2'))) {
									$l3w .= " AND $level2col=?";
									$sql_params[] = get_request_var('level2');
								}

								$members = db_fetch_assoc_prepared("SELECT DISTINCT $level3col
									FROM grid_metadata AS gm
									INNER JOIN disku_users_totals AS ut
									ON gm.OBJECT_ID=ut.user
									WHERE ut.delme=0 AND OBJECT_TYPE='user'
									AND $level3col IS NOT NULL
									$l3w
									ORDER BY $level3col", $sql_params);

								if (cacti_sizeof($members)) {
								foreach ($members AS $m) {
									print "<option value='" . base64url_encode($m[$level3col]) . "'"; if (get_request_var('level3') == $m[$level3col]) { print ' selected'; } print '>' . html_escape($m[$level3col]) . '</option>';
								}
								}
							}
							?>
						</select>
					</td>
					<?php } ?>
					<td>
						<?php print __('Organizations', 'disku');?>
					</td>
					<td width='1'>
						<select id='rows'>
							<?php
							if (cacti_sizeof($disku_rows_selector)) {
								foreach ($disku_rows_selector as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go');?>' title='<?php print __('Set/Refresh Filters');?>'>
							<input type='button' id='clear' value='<?php print __('Clear');?>' title='<?php print __('Clear Filters');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'disku');?>
					</td>
					<td width='1'>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if ($group_by != '') {
		$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM (
				SELECT $selectcols
				FROM disku_users_totals AS ut
				LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
				ON ut.user=gm.OBJECT_ID
				$sql_where
				$group_by
			) AS rs", $sql_params2);
	} else {
		$total_rows = 1;
	}

	$url_page_select = get_page_list(get_request_var('page'), MAX_DISPLAY_PAGES, $rows, $total_rows, 'disku_orgview.php?action=view');

	html_start_box('', '100%', '', '4', 'center', '');

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'level1' => array(
			'display' => $level1,
			'sort' => 'ASC'
		)
	);

	if (!empty($level2col)) {
		$display_text += array(
			'level2' => array(
				'display' => $level2,
				'sort' => 'ASC'
			)
		);
	}

	if (!empty($level3col)) {
		$display_text += array(
			'level3' => array(
				'display' => $level3,
				'sort' => 'ASC'
			)
		);
	}

	$display_text += array(
		'users' => array(
			'display' => __('Total Users', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'files' => array(
			'display' => __('Total Files', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'directories' => array(
			'display' => __('Total Dirs', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size' => array(
			'display' => __('Total Size', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size0to6' => array(
			'display' => __('Less 6 Months', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size6to12' => array(
			'display' => __('Between 6-12 Months', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'size12plus' => array(
			'display' => __('12 Months Plus', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	$nav = html_nav_bar('disku_orgview.php?action=view&page=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Organizations'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='f319b29a29f238701413d1bc1293f173'");

	$i = 0;
	if (cacti_sizeof($users)) {
		foreach ($users as $u) {
			$org_index="";
			$delim_cnt = 0;
			$have_level1 = false;
			$have_level2 = false;
			$have_level3 = false;

			if (get_request_var('level1') == -2) {
				$l1 = 'N/A';
			} elseif ($u['level1'] == '') {
				$l1 = 'Unregistered';
				$org_index .= $level1. ":";
				$have_level1 = true;
			} else {
				$l1 = html_escape($u['level1']);
				$org_index .= $level1. ":". str_replace(' ','',$u['level1']);
				$have_level1 = true;
			}

			if (get_request_var('level2') == -2) {
				$l2 = 'N/A';
			} elseif ($u['level2'] == '') {
				$l2 = 'Unregistered';
				$org_index .= (empty($org_index) ? "": "|") . $level2. ":";
				$have_level2 = true;
			} else {
				$l2 = html_escape($u['level2']);
				$org_index .= (empty($org_index) ? "": "|") . $level2. ":". str_replace(' ','',$u['level2']);
				$have_level2 = true;
			}

			if (get_request_var('level3') == -2) {
				$l3 = 'N/A';
			} elseif ($u['level3'] == '') {
				$l3 = 'Unregistered';
				$org_index .= (empty($org_index) ? "": "|") . $level3. ":";
				$have_level3 = true;
			} else {
				$l3 = html_escape($u['level3']);
				$org_index .= (empty($org_index) ? "": "|") . $level3. ":". str_replace(' ','',$u['level3']);
				$have_level3 = true;
			}

			$delim_cnt = substr_count($org_index, "|");
			if ($delim_cnt ==0) {
				if (empty($org_index)) {
					$org_index ="N_A";
				} else if ($have_level1 == true) {
					$org_index = "A|". $org_index;
				} else if ($have_level2 == true) {
					$org_index = "B|". $org_index;
				} else if ($have_level3 == true) {
					$org_index = "C|". $org_index;
				}
			} else if ($delim_cnt ==1) {
				if ($have_level1 == true && $have_level2 == true) {
					$org_index = "D|". $org_index;
				} else if ($have_level1 == true && $have_level3 == true) {
					$org_index = "E|". $org_index;
				} else if ($have_level2 == true && $have_level3 == true) {
					$org_index = "F|". $org_index;
				}
			} else if ($delim_cnt ==2) {
				$org_index = "G|". $org_index;
			}

			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared('SELECT id
					FROM graph_local
					WHERE snmp_query_id = ?
					AND snmp_index = ?',
					array($dqid, $org_index));

				if (cacti_sizeof($graphs)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($graphs as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				} else {
					unset($graph_select);
				}
			}

			form_alternate_row();

			if (isset($graph_select)) {
				print "<td width='10'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "/plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'disku') . "'></a></td>";
			} else {
				print "<td width='10'></td>";
			}

			print "<td style='white-space:nowrap;'><b>$l1</b></td>";
			print (!empty($level2col) ? "<td style='white-space:nowrap;'><b>$l2</b></td>":"");
			print (!empty($level3col) ? "<td style='white-space:nowrap;'><b>$l3</b></td>":"");
			print "<td class='right'>" . number_format_i18n($u['users'])          . '</td>';
			print "<td class='right'>" . number_format_i18n($u['files'])          . '</td>';
			print "<td class='right'>" . number_format_i18n($u['directories'])    . '</td>';
			print "<td class='right'>" . display_fs_memory($u['size']/1024)       . '</td>';
			print "<td class='right'>" . display_fs_memory($u['size0to6']/1024)   . '</td>';
			print "<td class='right'>" . display_fs_memory($u['size6to12']/1024)  . '</td>';
			print "<td class='right'>" . display_fs_memory($u['size12plus']/1024) . '</td>';

			form_end_row();

			$i++;
		}

		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='10'><em>" . __('No Disk Utilization Records Found', 'disku') . '</em></td></tr>';
		html_end_box(false);
	}
}

function get_orgview($selectcols, &$sql_where, &$group_by, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	$level1 = $level1s = trim(read_config_option('disku_level1'));
	$level2 = $level2s = trim(read_config_option('disku_level2'));
	$level3 = $level3s = trim(read_config_option('disku_level3'));

	$sql_where="WHERE ut.delme=0";
	if (empty($level1s)) {
		$level1s = "'Undefined'";
	}

	if (empty($level2s)) {
		$level2s = "'Undefined'";
	}

	if (empty($level3s)) {
		$level3s = "'Undefined'";
	}

	if (get_request_var('level1') >= 0) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "$level1=?";
		$sql_params[] = get_request_var('level1');
	} elseif (get_request_var('level1') == -1 && !empty($level1)) {
		$group_by = "GROUP BY $level1";
	} else {
		$level1 = "'N/A'";
	}

	if (get_request_var('level2') >= 0) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "$level2=?";
		$sql_params[] = get_request_var('level2');
	} elseif (get_request_var('level2') == -1 && !empty($level2)) {
		$group_by .= (strlen($group_by) ? ',':'GROUP BY ') . "$level2";
	} else {
		$level1 = "'N/A'";
	}

	if (get_request_var('level3') >= 0) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "$level3=?";
		$sql_params[] = get_request_var('level3');
	} elseif (get_request_var('level3') == -1 && !empty($level3)) {
		$group_by .= (strlen($group_by) ? ',':'GROUP BY ') . "$level3";
	} else {
		$level1 = "'N/A'";
	}

	if (get_request_var('filter') != '') {
		if (!empty($level1) || !empty($level2) || !empty($level3)) {
			$sql_where .= (strlen($sql_where) ? ' AND (':'WHERE (');
			$sql_where_filter = '';
		}
		if (!empty($level1)) {
			$sql_where_filter .= ($sql_where_filter != '' ? ' OR ':'') . "$level1 LIKE ?";
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}
		if (!empty($level2)) {
			$sql_where_filter .= ($sql_where_filter != '' ? ' OR ':'') . "$level2 LIKE ?";
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}
		if (!empty($level3)) {
			$sql_where_filter .= ($sql_where_filter != '' ? ' OR ':'') . "$level3 LIKE ?";
			$sql_params[] = '%'. get_request_var('filter') . '%';
		}
		if (!empty($level1) || !empty($level2) || !empty($level3)) {
			$sql_where .= $sql_where_filter;
			$sql_where .= ")";
		}
	}

	$sort_order = get_order_string();

	$users_sql = "SELECT $level1s AS level1, $level2s AS level2, $level3s AS level3,
		COUNT(DISTINCT userid) AS users,
		SUM(ut.files) AS files, SUM(ut.directories) AS directories,
		SUM(ut.size) AS size, SUM(ut.size0to6) AS size0to6,
		SUM(ut.size6to12) AS size6to12, SUM(ut.size12plus) AS size12plus
		FROM disku_users_totals AS ut
		LEFT JOIN (SELECT $selectcols FROM grid_metadata WHERE OBJECT_TYPE='user') AS gm
		ON ut.user=gm.OBJECT_ID
		$sql_where
		$group_by
		$sort_order";

	if ($apply_limits) {
		$users_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//print $users_sql;

	return db_fetch_assoc_prepared($users_sql, $sql_params);
}

