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
disku_groups();
bottom_footer();

function disku_groups() {
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
		'type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
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

	validate_store_request_vars($filters, 'sess_disku_g');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';

	$groups = get_disku_groups($sql_where, true, $rows, $sql_params);

	html_start_box(__('Disk Monitoring by Group', 'disku'), '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
			<script type="text/javascript">
			function applyFilter() {
				strURL  = 'disku_groups.php?header=false&action=view';
				strURL += '&type=' + $('#type').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&filter=' + $('#filter').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = 'disku_groups.php?header=false&action=view&clear=true'
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#formname').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#filter, #rows, #type').change(function() {
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});
			</script>
			<form id='formname' action='disku_groups.php'>
			<table class='filterTable'>
				<tr class='odd'>
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
						<?php print __('Groups', 'disku');?>
					</td>
					<td width='1'>
						<select id='rows'>
							<?php
							if (cacti_sizeof($disku_rows_selector)) {
								foreach ($disku_rows_selector as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>";
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Set/Refresh Filters', 'disku');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'disku');?>' title='<?php print __('Clear Filters', 'disku');?>'>
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
						<input type='text' id='filter' size='30' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*) FROM (
		SELECT dg.groupid
		FROM disku_groups AS dg
		LEFT JOIN (
			SELECT groupid, COUNT(userid) AS users
			FROM disku_groups_members
			WHERE userid != -1
			GROUP BY groupid
		) AS gm
		ON gm.groupid = dg.groupid
		LEFT JOIN (
			SELECT `group`, groupid, SUM(files) AS files
			FROM disku_groups_totals
			WHERE delme = 0
			GROUP BY groupid
		) AS g
		ON g.groupid = dg.groupid
		$sql_where
		GROUP BY dg.groupid) AS rs", $sql_params);

	$url_page_select = get_page_list(get_request_var('page'), MAX_DISPLAY_PAGES, $rows, $total_rows, 'disku_groups.php?action=view');

	html_start_box('', '100%', '', '4', 'center', '');

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'g.group' => array(
			'display' => __('Group Name', 'disku'),
			'sort' => 'ASC'
		),
		'groupid' => array(
			'display' => __('Group ID', 'disku'),
			'sort' => 'ASC'
		),
		'users' => array(
			'display' => __('Total Users', 'disku'),
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

	$nav = html_nav_bar('disku_groups.php?action=view&page=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Groups', 'disku'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='9150850f675973783afdb82684254948'");

	$i = 0;
	if (cacti_sizeof($groups)) {
		foreach ($groups as $g) {
			if (!empty($dqid)) {
				$graphs = db_fetch_assoc("SELECT id
					FROM graph_local
					WHERE snmp_query_id=$dqid
					AND snmp_index=" . $g['groupid']);

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
				print "<td width='10'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "/plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View Group Graphs', 'disku'). "'></a>";
			} else {
				print "<td width='10'>";
			}

			print "<a class='pic' href='" . html_escape('disku_users.php?query=1&group_id=' . $g['groupid']) . "'><img src='" . $config['url_path'] . "/plugins/disku/images/view_users.gif' alt='' title='" . __esc('View Group Members', 'disku') . "'></a></td>";

			print '<td><b>'               . html_escape($g['group'])                 . '</b></td>';
			print "<td class='right'><b>" . number_format_i18n($g['groupid'])        . '</b></td>';
			print "<td class='right'>"    . number_format_i18n($g['users'])          . '</td>';
			print "<td class='right'>"    . number_format_i18n($g['files'])          . '</td>';
			print "<td class='right'>"    . number_format_i18n($g['directories'])    . '</td>';
			print "<td class='right'>"    . display_fs_memory($g['size']/1024)       . '</td>';
			print "<td class='right'>"    . display_fs_memory($g['size0to6']/1024)   . '</td>';
			print "<td class='right'>"    . display_fs_memory($g['size6to12']/1024)  . '</td>';
			print "<td class='right'>"    . display_fs_memory($g['size12plus']/1024) . '</td>';
			form_end_row();
		}
		html_end_box(false);
		print $nav;
	} else {
		print "<tr><td colspan='10'><em>No Groups Found</em></td></tr>";
		html_end_box(false);
	}
}

function get_disku_groups(&$sql_where, $apply_limits = true, $row_limit = 30, &$sql_params) {
	$sql_where = "WHERE files IS NOT NULL ";

	if (isset_request_var('filter') && get_request_var('filter') != '') {
		//$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "`group` LIKE '%" . get_request_var('filter') . "%'";
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "dg.name LIKE ?";
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (get_request_var('type') >= 0) {
		$grange = read_config_option('disku_gid_range');
		$grange = explode('-', $grange);
		if (cacti_sizeof($grange) != 2) {
			$_SESSION['disku_error'] = "Group Range in Settings is invalid. Upper and lower range must be separated with a hypen.";
			raise_message('disku_error');
		} elseif (!is_numeric($grange[0]) || !is_numeric($grange[1])) {
			$_SESSION['disku_error'] = "Group Range in Settings is invalid.  Ranges are not numeric.";
			raise_message('disku_error');
		} elseif ($grange[0] > $grange[1]) {
			$_SESSION['disku_error'] = "Group Range in Settings is invalid.  Lower range limit must be less than the upper range limit.";
			raise_message('disku_error');
		} elseif (get_request_var('type') == 0) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'g.groupid<?';
			$sql_params[] = $grange[0];
		} else {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'g.groupid BETWEEN ? AND ?';
			$sql_params[] = $grange[0];
			$sql_params[] = $grange[1];
		}
	}

	$sort_order = get_order_string();

	$groups_sql = "SELECT dg.groupid, dg.name as `group`, SUM(users) AS users,
		g.files, g.directories, g.size, g.size0to6, g.size6to12, g.size12plus
		FROM disku_groups AS dg
		LEFT JOIN
		(
			SELECT groupid, COUNT(DISTINCT userid) AS users
			FROM disku_groups_members
			WHERE userid!=-1
			GROUP BY groupid
		) AS gm
		ON gm.groupid=dg.groupid
		LEFT JOIN (
			SELECT `group`, groupid, SUM(files) AS files,
			SUM(directories) AS directories, SUM(size) AS size,
			SUM(size0to6) AS size0to6, SUM(size6to12) AS size6to12,
			SUM(size12plus) AS size12plus
			FROM disku_groups_totals
			WHERE delme=0
			GROUP BY groupid
		) AS g
		ON g.groupid=dg.groupid
		$sql_where
		GROUP BY dg.groupid
		$sort_order";

	if ($apply_limits) {
		$groups_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	//print($groups_sql);

	return db_fetch_assoc_prepared($groups_sql, $sql_params);
}

