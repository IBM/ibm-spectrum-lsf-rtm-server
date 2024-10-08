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
disku_appview();
bottom_footer();

function disku_appview() {
	global $config, $extension_actions, $disku_rows_selector;
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

	validate_store_request_vars($filters, 'sess_disku_av');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';

	$extensions = get_disku_applications($sql_where, true, $rows, $sql_params);

	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = 'disku_appview.php?header=false&action=view';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'disku_appview.php?header=false&action=view&clear=true'
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#formname').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#filter, #rows').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Disk Monitoring by Application', 'disku'), '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
			<form id='formname' action='disku_appview.php'>
			<table class='filterTable'>
				<tr class='odd'>
					<td>
						<?php print __('Search', 'disku');?>
					</td>
					<td width='1'>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Applications', 'disku');?>
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
							<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Set/Refresh Filters', 'disku');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'disku');?>' title='<?php print __('Clear Filters', 'disku');?>'>
						</span>
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM disku_applications as a
		$sql_where", $sql_params);

	$url_page_select = get_page_list(get_request_var('page'), MAX_DISPLAY_PAGES, $rows, $total_rows, 'disku_appview.php?action=view');

	html_start_box('', '100%', '', '4', 'center', '');

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'application' => array(
			'display' => __('Application', 'disku'),
			'sort' => 'ASC'
		),
		'vendor' => array(
			'display' => __('Vendor', 'disku'),
			'sort' => 'ASC'
		),
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

	$nav = html_nav_bar('disku_appview.php?action=view&page=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Applications', 'disku'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='a1f6a75855fc8d1ed526031e1a0de7e7'");

	$i = 0;
	if (cacti_sizeof($extensions)) {
		foreach ($extensions as $e) {
			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared("SELECT id
					FROM graph_local
					WHERE snmp_query_id=?
					AND snmp_index=?", array($dqid, $e['application']));

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
				print "<td style='width:10px;'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'disku'). "'></a></td>";
			} else {
				print "<td style='width:10px;'>" . "" . "</td>";
			}

			print '<td>' . html_escape($e['application']) . '</td>';
			print '<td>' . html_escape($e['vendor']) . '</td>';
			print "<td class='right'>" . number_format_i18n($e['users'])          . '</td>';
			print "<td class='right'>" . number_format_i18n($e['files'])          . '</td>';
			print "<td class='right'>" . display_fs_memory($e['size']/1024)       . '</td>';
			print "<td class='right'>" . display_fs_memory($e['size0to6']/1024)   . '</td>';
			print "<td class='right'>" . display_fs_memory($e['size6to12']/1024)  . '</td>';
			print "<td class='right'>" . display_fs_memory($e['size12plus']/1024) . '</td>';
			print '</tr>';

			$i++;
		}

		html_end_box(false);

		print $nav;
	} else {
		print "<tr><td colspan='11'><em>" . __('No Application Found', 'disku') . '</em></td></tr>';

		html_end_box(false);
	}
}

function get_disku_applications(&$sql_where, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	$sort_order = get_order_string();

	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "a.application LIKE ? OR a.vendor LIKE ?";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	$extension_sql = "SELECT
       	a.application, a.vendor,
       	COUNT(DISTINCT userid) AS users,
       	SUM(e.files) AS files, SUM(e.size) AS size,
       	SUM(e.size0to6) AS size0to6,
       	SUM(e.size6to12) AS size6to12,
       	SUM(e.size12plus) AS size12plus
       	FROM disku_applications AS a
       	LEFT JOIN disku_extension_monitors AS em
       	ON em.application_id=a.id
		LEFT JOIN disku_extension_totals as e
		ON em.extension=e.extension
		$sql_where
		GROUP BY a.id
		$sort_order
		";

	if ($apply_limits) {
		$extension_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	return db_fetch_assoc_prepared($extension_sql, $sql_params);
}

