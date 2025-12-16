<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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
disku_extensions();
bottom_footer();

function disku_extensions() {
	global $config, $disku_rows_selector, $extension_actions;
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
		'monitor' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '1'
			),
		'application' => array(
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
			'default' => 'size',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_disku_e');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';

	$extensions = get_disku_extensions($sql_where, true, $rows, $sql_params);
	?>
	<script type='text/javascript'>
	function applyFilter() {
		strURL  = 'disku_extensions.php?header=false&action=view';
		strURL += '&rows=' + $('#rows').val();
		strURL += '&monitor=' + $('#monitor').val();
		strURL += '&application=' + $('#application').val();
		strURL += '&filter=' + $('#filter').val();
		strURL += '&page=1';
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'disku_extensions.php?header=false&action=view&clear=true'
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#formname').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#filter, #rows, #monitor, #application').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});
	</script>
	<?php

	html_start_box(__('Disk Monitoring by Extension', 'disku'), '100%', '', '3', 'center', '');

	?>
	<tr class='odd'>
		<td>
			<form id='formname' action='disku_extensions.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('App', 'disku');?>
					</td>
					<td>
						<select id='application'>
							<option value='-1'<?php if (get_request_var('application') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<option value='0'<?php if (get_request_var('application') == '0') {?> selected<?php }?>><?php print __('Undefined', 'disku');?></option>
							<?php
							$apps = array_rekey(
								db_fetch_assoc("SELECT id, application
									FROM disku_applications
									ORDER BY application"),
								'id', 'application'
							);

							if (cacti_sizeof($apps)) {
								foreach ($apps as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var('application') == $key) { print ' selected'; } print '>' . html_escape($value) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Monitored', 'disku');?>
					</td>
					<td>
						<select id='monitor'>
							<option value='-1'<?php if (get_request_var('monitor') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
							<option value='0'<?php if (get_request_var('monitor') == '0') {?> selected<?php }?>><?php print __('Yes', 'disku');?></option>
							<option value='1'<?php if (get_request_var('monitor') == '1') {?> selected<?php }?>><?php print __('No', 'disku');?></option>
						</select>
					</td>
					<td>
						<?php print __('Extensions', 'disku');?>
					</td>
					<td>
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

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM disku_extension_registry AS r
		LEFT JOIN disku_extension_monitors as em
		ON r.id=em.rid
		LEFT JOIN disku_applications AS a
		ON a.id=em.application_id
		LEFT JOIN disku_extension_totals_simple as e
		ON r.extension=e.extension
		$sql_where", $sql_params);

	html_start_box('', '100%', '', '4', 'center', '');

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
			'sort' => 'ASC'
		),
		'extension' => array(
			'display' => __('Extension', 'disku'),
			'sort' => 'ASC'
		),
		'application' => array(
			'display' => __('Application', 'disku'),
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Monitored', 'disku'),
			'sort' => 'ASC'
		),
		'nosort2' => array(
			'display' => __('Description and Application Level Description', 'disku'),
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

	$nav = html_nav_bar('disku_extensions.php?action=view&page=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('Extensions', 'disku'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='439617ebef50ab9be20c168f882d187e'");

	$i = 0;
	if (cacti_sizeof($extensions) > 0) {
		foreach ($extensions as $e) {
			if (is_numeric($e['mid'])) {
				$id = $e['mid'];
			} else {
				$id = 'new:' . html_escape($e['extension']);
			}
			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared("SELECT id
					FROM graph_local
					WHERE snmp_query_id=?
					AND snmp_index=?", array($dqid, str_replace('!', '.', $e['extension'])));

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
				print "<td style='width:10px;'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View User Graphs', 'disku') . "'></a></td>";
			} else {
				print "<td style='width:10px;'></td>";
			}

			print '<td>' . html_escape($e['extension']) . '</td>';
			print '<td>' . ($e['application_id'] == '' ? __('Undefined', 'disku'):html_escape($e['application'])) . '</td>';
			print '<td>' . (empty($e['monitor']) ? "<span class='deviceDown'>" . __('No', 'disku') . '</span>':"<span class='deviceUp'>" . __('Yes', 'disku'). '</span>') . '</td>';
			print '<td>' . (html_escape($e['rnotes']) . (empty($e['mnotes']) ? '': '; ' . html_escape($e['mnotes']))) . '</td>';
			print "<td class='right'>" . number_format_i18n($e['users']) . '</td>';
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
		print '<tr><td colspan="11"><em>' . __('No Extensions Found', 'disku') . '</em></td></tr>';

		html_end_box(false);
	}
}

function get_disku_extensions(&$sql_where, $apply_limits = true, $row_limit = 30, &$sql_params = array()) {
	$sort_order = get_order_string();

	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') .
			'(r.extension LIKE ? OR r.notes LIKE ? OR em.notes LIKE ?)';
		$sql_params[] = '%' . get_request_var('filter') . '%';
		$sql_params[] = '%' . get_request_var('filter') . '%';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	}

	if (isset_request_var('application')) {
		if (get_request_var('application') == 0) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . '(application_id IS NULL OR application_id=0)';
		} else if (get_request_var('application') > 0) {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . 'application_id=?';
			$sql_params[] = get_request_var('application');
		}
	}

	if (isset_request_var('monitor')) {
		if (get_request_var('monitor') == '0') {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "r.monitor='on'";
		} elseif (get_request_var('monitor') == '1') {
			$sql_where .= (strlen($sql_where) ? ' AND ':'WHERE ') . "(r.monitor IS NULL OR r.monitor!='on')";
		}
	}

	$extension_sql = "SELECT r.id AS rid, em.id AS mid,
		r.monitor, r.extension, em.application_id,
		a.application, em.notes AS mnotes, r.notes AS rnotes,
		e.users, e.files, e.size, e.size0to6, e.size6to12, e.size12plus
		FROM disku_extension_registry AS r
		LEFT JOIN disku_extension_monitors as em
		ON r.id=em.rid
		LEFT JOIN disku_applications AS a
		ON a.id=em.application_id
		LEFT JOIN disku_extension_totals_simple as e
		ON r.extension=e.extension
		$sql_where
		$sort_order";

	if ($apply_limits) {
		$extension_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	return db_fetch_assoc_prepared($extension_sql, $sql_params);
}

