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

chdir('../../');
include('./include/auth.php');
include('./plugins/disku/include/disku_functions.php');

/* set default action */
set_default_action();

general_header();
disku_users();
bottom_footer();

function disku_users() {
	global $config, $disku_rows_selector;
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
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
			),
		'group_id' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '-1',
			'options' => array('options' => 'sanitize_search_string')
			),
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'user',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_disku_u');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	html_start_box(__('Operating System Users', 'disku'), '100%', '', '3', 'center', '');

	$sql_where = '';

	$users = get_disku_users($sql_where, true, $rows, $sql_params);

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>
			function applyFilter() {
				strURL  = 'disku_users.php?header=false&action=view';
				strURL += '&group_id=' + $('#group_id').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&type=' + $('#type').val();
				strURL += '&filter=' + $('#filter').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'disku_users.php?header=false&action=view&clear=true'
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#formname').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#filter, #rows, #type, #group_id').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});
			</script>
			<form id='formname' action='disku_users.php'>
			<table class='filterTable'>
				<tr class='odd'>
					<td>
						<?php print __('User', 'disku');?>
					</td>
					<td width='1'>
						<select id='group_id'>
							<option value='-1'<?php if (get_request_var('group_id') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<?php
							$groups = array_rekey(
								db_fetch_assoc("SELECT DISTINCT groupid, name AS `group`
									FROM disku_groups
									WHERE name NOT REGEXP '^-?[0-9]+$'
									ORDER BY name"),
								'groupid', 'group'
							);

							if (cacti_sizeof($groups)) {
								foreach ($groups as $key => $value) {
									$pos = strstr($value, '#');
									if ($pos === true) {
										if ($pos == 0) {
											$value = 'UNDEFINED';
										} else {
											$value = substr($value,0,$pos);
										}
									}
									print "<option value='" . $key . "'"; if (get_request_var('group_id') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>";
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Type', 'disku');?>
					</td>
					<td width='1'>
						<select id='type'>
							<option value='-1'<?php if (get_request_var('type') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<option value='0'<?php if (get_request_var('type') == '0') {?> selected<?php }?>><?php print __('System', 'disku');?></option>
							<option value='1'<?php if (get_request_var('type') == '1') {?> selected<?php }?>><?php print __('User', 'disku');?></option>
						</select>
					</td>
					<td>
						<?php print __('Users', 'disku');?>
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
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	if (get_request_var('group_id') >= 0) {
		$total_rows = db_fetch_cell_prepared("SELECT COUNT(DISTINCT u.userid)
		FROM disku_users AS u
		LEFT JOIN disku_users_totals AS ut
		ON u.userid=ut.userid
		LEFT JOIN disku_groups AS g
		ON u.groupid=g.groupid
		LEFT JOIN disku_groups_members as dgm
		ON u.userid=dgm.userid
		$sql_where", $sql_params);
	} else {
		$total_rows = db_fetch_cell_prepared("SELECT COUNT(DISTINCT u.userid)
		FROM disku_users AS u
		LEFT JOIN disku_users_totals AS ut
		ON u.userid=ut.userid
		LEFT JOIN disku_groups AS g
		ON u.groupid=g.groupid
		$sql_where", $sql_params);
	}

	html_start_box('', '100%', '', '4', 'center', '');

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'user' => array(
			'display' => __('User', 'disku'),
			'sort' => 'ASC'
		),
		'name' => array(
			'display' => __('Name', 'disku'),
			'sort' => 'ASC'
		),
		'group_name' => array(
			'display' => __('Default Group', 'disku'),
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Groups', 'disku'),
			'sort' => 'ASC'
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

	$nav = html_nav_bar('disku_users.php?action=view&page=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Users', 'disku'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='7cf1dba8b514a771435530e768366af7'");

	$i = 0;
	if (cacti_sizeof($users) > 0) {
		foreach ($users as $u) {
			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared('SELECT id
					FROM graph_local
					WHERE snmp_query_id = ?
					AND snmp_index= ?',
					array($dqid, $u['userid']));

				if (cacti_sizeof($graphs)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($graphs as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				} else {
					unset($graph_select);
				}
			}

			$groups = implode(', ', array_rekey(db_fetch_assoc_prepared("SELECT IF(dg.name IS NOT NULL, CONCAT_WS('',dg.name,' (',dg.groupid,')'),CONCAT_WS('Removed Group (',dgm.groupid,')')) AS name
				FROM disku_groups_members AS dgm
				LEFT JOIN disku_groups AS dg
				ON dgm.groupid=dg.groupid
				WHERE user=?
				ORDER BY name", array($u['user'])), 'name', 'name'));

			form_alternate_row();

			if (isset($graph_select)) {
				print "<td style='width:10px;'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'disku') . "'></a></td>";
			} else {
				print "<td style='width:10px;'></td>";
			}

			print '<td><b>' . html_escape($u['user']) . '</b></td>';
			print '<td>'    . html_escape($u['name']) . '</td>';

			print "<td><a class='pic' href='" . html_escape('disku_users.php?query=1&group_id=' . $u['groupid']) . "' title='" . __esc('View Users', 'disku') . "'><b><i>" . html_escape($u['group_name']) . '</i></b></a></td>';

			print '<td><i>'            . html_escape($groups)                     . '</i></td>';
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
		print "<tr><td colspan='11'><em>" . __('No Users Found', 'disku') . '</em></td></tr>';
		html_end_box(false);
	}
}

function get_disku_users(&$sql_where, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	if (get_request_var('type') >= 0) {
		$urange = read_config_option('disku_uid_range');
		$urange = explode('-', $urange);
		if (cacti_sizeof($urange) != 2) {
			raise_message('disku_urange', __('User Range in Settings is invalid. Upper and lower range must be separated with a hyphen.', 'disku'), MESSAGE_LEVEL_ERROR);
		} elseif (!is_numeric($urange[0]) || !is_numeric($urange[1])) {
			raise_message('disku_urange', __('User Range in Settings is invalid.  Ranges are not numeric.', 'disku'), MESSAGE_LEVEL_ERROR);;
		} elseif ($urange[0] > $urange[1]) {
			raise_message('disku_urange', __('User Range in Settings is invalid.  Lower range limit must be less than the upper range limit.', 'disku'), MESSAGE_LEVEL_ERROR);
		} elseif (get_request_var('type') == 0) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'u.userid < ?';
			$sql_params[] = $urange[0];
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'u.userid BETWEEN ? AND ?';
			$sql_params[] = $urange[0];
			$sql_params[] = $urange[1];
		}
	}

	if (get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') .
			'(u.user LIKE ?
			OR u.name LIKE ?)';
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$sort_order = get_order_string();

	if (get_request_var('group_id') >= 0) {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'dgm.groupid=?';
		$sql_params[] = get_request_var('group_id');

		$users_sql = "SELECT u.user, u.userid, u.name, g.name AS group_name, u.groupid,
		SUM(ut.files) AS files, SUM(ut.directories) AS directories,
		SUM(ut.size) AS size, SUM(ut.size0to6) AS size0to6,
		SUM(ut.size6to12) AS size6to12, SUM(ut.size12plus) AS size12plus
		FROM disku_users AS u
		LEFT JOIN disku_users_totals AS ut
		ON u.userid=ut.userid
		LEFT JOIN disku_groups AS g
		ON u.groupid=g.groupid
		LEFT JOIN disku_groups_members as dgm
		ON u.userid=dgm.userid
		$sql_where
		GROUP BY u.userid
		$sort_order";
	} else {
		$users_sql = "SELECT u.user, u.userid, u.name, g.name AS group_name, u.groupid,
		SUM(ut.files) AS files, SUM(ut.directories) AS directories,
		SUM(ut.size) AS size, SUM(ut.size0to6) AS size0to6,
		SUM(ut.size6to12) AS size6to12, SUM(ut.size12plus) AS size12plus
		FROM disku_users AS u
		LEFT JOIN disku_users_totals AS ut
		ON u.userid=ut.userid
		LEFT JOIN disku_groups AS g
		ON u.groupid=g.groupid
		$sql_where
		GROUP BY u.userid
		$sort_order";
	}

	if ($apply_limits) {
		$users_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//print $users_sql;

	return db_fetch_assoc_prepared($users_sql, $sql_params);
}
