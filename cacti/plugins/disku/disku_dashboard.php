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
disku_filesystems();
bottom_footer();

function disku_filesystems() {
	global $config;
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
		'location' => array(
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
			'default' => 'blocks',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
			)
	);

	validate_store_request_vars($filters, 'sess_disku_fs');
	/* ================= input validation ================= */

	html_start_box(__('Disk Monitoring File Systems', 'disku'), '100%', '', '3', 'center', '');
	disku_filter();
	html_end_box();

	$sql_where  = '';

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	} elseif (get_request_var('rows') == -2) {
		$rows = 99999999;
	} else {
		$rows = get_request_var('rows');
	}

	$filesystems = get_disku_filesystems($sql_where, TRUE, $rows, $sql_params);

	?>
	<script type='text/javascript'>
	function filterChange() {
		strURL  = 'disku_dashboard.php?header=false';
		strURL += '&filter=' + $('#filter').val();
		strURL += '&location=' + $('#location').val();
		strURL += '&rows=' + $('#rows').val();
		loadPageNoHeader(strURL);
	}
	</script>
	<?php

	html_start_box('', '100%', '', '3', 'center', '');

	$rows_query_string = "SELECT COUNT(*)
		FROM disku_pollers_filesystems AS fs
		INNER JOIN disku_pollers AS dp
		ON dp.id=fs.poller_id
		$sql_where";

	$total_rows = db_fetch_cell_prepared($rows_query_string, $sql_params);

	/* generate page list */
	$url_page_select = get_page_list(get_request_var('page'), MAX_DISPLAY_PAGES, $rows, $total_rows, 'disku_dashboard.php?filter=' . get_request_var('filter'));

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'disku'),
		),
		'mountPoint' => array(
			'display' => __('Mount Point', 'disku'),
			'sort' => 'ASC'
		),
		'device' => array(
			'display' => __('Device', 'disku'),
			'sort' => 'ASC'
		),
		'location' => array(
			'display' => __('Location', 'disku'),
			'sort' => 'ASC'
		),
		'hostname' => array(
			'display' => __('Hostname', 'disku'),
			'sort' => 'ASC'
		),
		'blocks' => array(
			'display' => __('Size', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'used' => array(
			'display' => __('Used', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'available' => array(
			'display' => __('Available', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		),
		'percentUsed' => array(
			'display' => __('Percent Used', 'disku'),
			'align' => 'right',
			'sort' => 'DESC'
		)
	);

	$nav = html_nav_bar('disku_dashboard.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text)+1, __('File Systems', 'disku'), 'page', 'main');

	print $nav;

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	$dqid = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='ad574050ad411342d40035133cdef860'");

	$i = 0;
	if (cacti_sizeof($filesystems)) {
		foreach ($filesystems as $fs) {
			if (!empty($dqid)) {
				$graphs = db_fetch_assoc_prepared('SELECT id
					FROM graph_local
					WHERE snmp_query_id = ?
					AND snmp_index = ?',
					array($dqid, $fs['poller_id'] . '|' . $fs['mountPoint']));

				if (cacti_sizeof($graphs)) {
					$graph_select = 'page=1&graph_template_id=-1&rfilter=&style=selective&action=preview&host_id=-1&graph_add=';

					foreach($graphs as $graph) {
						$graph_select .= $graph['id'] . '%2C';
					}
				}else{
					unset($graph_select);
				}
			}

			form_alternate_row();

			if (isset($graph_select)) {
				print "<td width='10'><a class='pic' href='" . html_escape($config['url_path'] . 'graph_view.php?' . $graph_select) . "'><img src='" . $config['url_path'] . "/plugins/disku/images/view_graphs.gif' alt='' title='" . __esc('View File Systems Graphs', 'disku') . "'></a></td>";
			}else{
				print "<td width='10'></td>";
			}

			print '<td>' . html_escape($fs['mountPoint']) . '</td>';
			print "<td>" . html_escape($fs['device']) . '</td>';
			print '<td>' . html_escape($fs['location']) . '</td>';
			print '<td>' . html_escape($fs['hostname']) . '</td>';
			print "<td class='right'>" . display_fs_memory($fs['blocks']) . '</td>';
			print "<td class='right'>" . display_fs_memory($fs['used']) . '</td>';
			print "<td class='right'>" . display_fs_memory($fs['available']) . '</td>';
			print "<td class='right'>" . $fs['percentUsed'] . ' %</td>';

			form_end_row();
			$i++;
		}

		html_end_box(false);

		if (cacti_sizeof($filesystems)) {
			print $nav;
		}
	}else{
		print "<tr><td colspan='5'><em>No File Systems Found</em></td></tr>";
		html_end_box(false);
	}
}

function disku_filter() {
	global $config, $disku_rows_selector;
	?>
	<tr class='odd'>
		<td>
		<form id='form_disku' action='disku_dashboard.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Location', 'disku');?>
					</td>
					<td>
						<select id='location'>
						<option value='-1'<?php if (get_request_var('location') == '-1') {?> selected<?php }?>><?php print __('All', 'disku');?></option>
						<?php
						$locations = db_fetch_assoc("SELECT DISTINCT id, CONCAT_WS('',location,' (',hostname,')') AS location
							FROM disku_pollers
							ORDER BY location");

						if (cacti_sizeof($locations)) {
							foreach ($locations as $l) {
								print '<option value="' . $l['id'] .'"'; if (get_request_var('location') == $l['id']) { print ' selected'; } print '>' . html_escape($l['location']) . '</option>';
							}
						}
						?>
						</select>
					</td>
					<td>
						<?php print __('File Systems', 'disku');?>
					</td>
					<td>
						<select id='rows'>
							<?php
							if (cacti_sizeof($disku_rows_selector)) {
								foreach ($disku_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<span>
							<input type='submit' id='go' value='<?php print __('Go', 'disku');?>' title='<?php print __('Search', 'disku');?>'>
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
					<td>
						<input type='text' id='filter' size='30' value='<?php print get_request_var('filter');?>'>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>

	<script type='text/javascript'>

	function applyFilter() {
		strURL  = 'disku_dashboard.php?header=false';
		strURL += '&location=' + $('#location').val();
		strURL += '&rows=' + $('#rows').val();
		strURL += '&filter=' + $('#filter').val();
		loadPageNoHeader(strURL);
	}

	function clearFilter() {
		strURL = 'disku_dashboard.php?header=false&clear=true';
		loadPageNoHeader(strURL);
	}

	$(function() {
		$('#form_disku').submit(function(event) {
			event.preventDefault();
			applyFilter();
		});

		$('#filter, #location, #rows').change(function() {
			applyFilter();
		});

		$('#clear').click(function() {
			clearFilter();
		});
	});

	</script>
	<?php
}

function get_disku_filesystems(&$sql_where, $apply_limits = TRUE, $rows = 20, &$sql_params = array()) {
	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . "(mountPoint LIKE ? OR device LIKE ?)";
		$sql_params[] = '%'. get_request_var('filter') . '%';
		$sql_params[] = '%'. get_request_var('filter') . '%';
	}

	if (isset_request_var('location') && get_request_var('location') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'dp.id=?';
		$sql_params[] = get_request_var('location');
	}

	$sort_order = get_order_string();

	$fs_sql = "SELECT fs.*, dp.id, dp.hostname, dp.location
		FROM disku_pollers_filesystems AS fs
		INNER JOIN disku_pollers AS dp
		ON dp.id=fs.poller_id
		$sql_where
		$sort_order";

	if ($apply_limits) {
		$fs_sql .= ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;
	}

	return db_fetch_assoc_prepared($fs_sql, $sql_params);
}

