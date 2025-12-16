<?php
// $Id$
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

$action = '';
$guest_account = true;

chdir('../../');
include_once('./include/auth.php');
include_once('./plugins/meta/lib/metadata_api.php');

$title = __('IBM Spectrum LSF RTM - Metadata', 'meta');

/**
 * Filter for MetaData
 */
function filter() {
	global $config, $meta_valid_object_types, $item_rows;

	?>
	<tr class='odd'>
		<td>
			<script type='text/javascript'>
			$(function() {
				$('#clear').click(function() {
					clearFilter();
				});

				$('#type, #rows').change(function() {
					applyFilter();
				});

				$('#form_meta').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#fakebutton').click(function() {
					$('#csvfile').trigger('click');
				});
			});

			function applyFilter() {
				strURL  = 'metadata.php?header=false&type=' + $('#type').val();
				strURL += '&filter=' + encodeURIComponent($('#filter').val());
				strURL += '&rows=' + $('#rows').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL  = 'metadata.php?header=false&clear=true';
				loadPageNoHeader(strURL);
			}
			</script>
			<form id='form_meta' action='metadata.php'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'meta');?>
					</td>
					<td>
						<input type='text' id='filter' size='30' value='<?php print html_escape_request_var('filter');?>'>
					</td>
					<td>
						<?php print __('Type', 'meta');?>
					</td>
					<td>
						<select id='type'>
							<?php
							$defined_object_types = db_fetch_assoc('SELECT DISTINCT object_type
								FROM grid_metadata_conf
								ORDER by object_type');
							if (cacti_sizeof($defined_object_types)) {
								foreach ($defined_object_types AS $defined_object) {
									print '<option value="' . $defined_object['object_type'] . '" ';
									if (get_request_var('type') == $defined_object['object_type']) {
										print ' selected';
										$cur_type = $defined_object['object_type'];
									}
									print '>' . htmlspecialchars($meta_valid_object_types[$defined_object['object_type']]) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Items', 'meta');?>
					</td>
					<td>
						<select id='rows'>
							<option value='-1'<?php print (get_request_var('rows') == '-1' ? ' selected>':'>') . __('Default:'.read_config_option('num_rows_table'), 'meta');?></option>
							<?php
							if (cacti_sizeof($item_rows)) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='<?php print __('Go', 'meta');?>' title='<?php print __('Search MetaData', 'meta');?>'>
					</td>
					<td>
						<input type='button' id='clear' value='<?php print __('Clear', 'meta');?>' title='<?php print __('Clear Filters', 'meta');?>'>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='<?php print get_request_var('page');?>'>
			</form>
		</td>
		<?php

		// Has configuration been loaded for this object type? If not, do not allow objects to be added
		$result = db_fetch_cell_prepared('SELECT count(*)
			FROM grid_metadata_conf
			WHERE object_type = ?',
			array(get_request_var('type')));

		if ($result != 0) {
		?>
		<td>
			<form name='import_form' enctype='multipart/form-data' action='metadata.php' method='post'>
			<table>
				<tr>
					<td>
						<?php
							form_file('csvfile', 40);
						?>
					</td>
					<td>
						<input type='submit' value='<?php print __esc('Import', 'meta');?>'>
					</td>
					<td>
						<input name='type' type='hidden' value='<?php print htmlspecialchars($cur_type);?>'>
						<input name='action' type='hidden' value='load'>
					</td>
				</tr>
			</table>
			</form>
		</td>
		<?php } ?>
	</tr>
	<?php
}

/**
 * Loads metadata from a CSV file
 */
function load() {
	if (api_user_realm_auth('Edit_Metadata')) {
		$loaded = true;

		// Check if there were any file errors
		if ($_FILES['csvfile']['error'] != 0) {
			cacti_log('ERROR: metadata file error in PHP');
			raise_message(102);
			$loaded = false;
		} else {
			// Get the file from PHP's temp location
			$filename = $_FILES['csvfile']['tmp_name'];
			if (!isset($filename) || $filename == '') {
				cacti_log('ERROR: metadata filename not specified');
				raise_message(109);
				$loaded = false;
			} else {
				load_current_session_value('type', 'sess_grid_meta_type', 'user');
				$loaded = load_metadata($filename, get_request_var('type'));
			}
		}

		if (is_numeric($loaded) && $loaded > 0) {
			raise_message(113);
		} else if (is_numeric($loaded) && $loaded == 0) {
			// Import was successful
			raise_message(100);
		} else if (!$loaded) {
			// Import was not successful
			raise_message(102, '', MESSAGE_LEVEL_ERROR);
		}
	}

	set_request_var('action', 'list');
}

/**
 * Creates metadata
 */
function create() {
	if (api_user_realm_auth('Edit_Metadata')) {
		$result = create_metadata();
		if ($result == 0) {
			raise_message(103);
			set_request_var('action', 'list');
		} else {
			raise_message($result);
			if ($result == 50120) {
				$_SESSION['sess_error_fields']['object_id'] = 'object_id';
				$_SESSION['sess_error_fields']['cluster_id'] = 'cluster_id';
			} else {
				$_SESSION['sess_error_fields']['object_id'] = 'object_id';
			}

			set_request_var('action', 'add');
		}
	}
}

/**
 * Saves updated metadata
 */
function save() {
	if (api_user_realm_auth('Edit_Metadata')) {
		$result = save_metadata();
		if ($result == 0) {
			raise_message(103);
			set_request_var('action', 'list');
		} else {
			raise_message($result);
			if ($result == 50120) {
				$_SESSION['sess_error_fields']['object_id'] = 'object_id';
				$_SESSION['sess_error_fields']['cluster_id'] = 'cluster_id';
			} else {
				$_SESSION['sess_error_fields']['object_id'] = 'object_id';
			}

			set_request_var('action', 'add');
		}
	}
}

/**
 * Deletes rows of metadata
 */
function metadata_delete() {
	global $config;

	include_once($config["library_path"] . '/rtm_functions.php');

	if (!isset_request_var('confirm')) {
		// Build the set of objects to delete
		$object_list = '';
		$object_text = '';
		foreach ($_POST as $key => $value) {
			if (preg_match('/^chk_(.*)?/', $key, $matches)) {
				$object_list .= $key . ',';
				$unmangled_elem_name = rtm_base64url_decode($matches[1]);
				$elements     = explode('###', $unmangled_elem_name);
				$object_id    = $elements[0];
				$cluster_id   = $elements[1];
				$clustername  = db_fetch_cell_prepared('SELECT clustername FROM grid_clusters WHERE clusterid = ?', array($cluster_id));

				$object_text .= '<li><strong>' . html_escape($object_id) . '</strong> from ' . ($cluster_id > 0 ? __esc('Cluster \'%s\'', $clustername, 'meta'):__('All Clusters', 'meta')) . '</li>';
			}
		}

		// Show the delete confirmation dialog
		top_header();

		html_start_box(__('Delete Metadata Object(s)', 'meta'), '100%', '', '3', 'center', '');

		if (!empty($object_list)){
			print "<tr>
				<td class='textArea'>
					<p>" . __('Click \'Continue\' to Delete the selected MetaData Object(s).', 'meta') . '</p>
					<ul>' . $object_text . '</ul>';

			print '</td></tr>';
		}

		$title = __('Delete Metadata Object(s)', 'meta');

		if (empty($object_list)) {
			raise_message('meta40', __('You must select at least one Meta Object.'), MESSAGE_LEVEL_ERROR);
			header('Location: metadata.php?header=false');
			exit;
		}else{
			$save_html = "<input type='button' value='" . __('Cancel', 'meta') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __('Continue', 'meta') . "' title='" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "'>";
		}

		print "<tr>
			<td class='saveRow'>
				<form action='metadata.php?action=delete&amp;confirm=y' method='post'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . $object_list . "'>
				<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
				$save_html
				</form>
			</td>
		</tr>";

		html_end_box();

		bottom_footer();
		exit;
	} else {	// If confirmed, delete the object(s)
		if (api_user_realm_auth('Edit_Metadata')) {
			delete_metadata_rows();
			raise_message(105);
		}
	}

	set_request_var('action', 'list');
}

set_default_action('list');

// Sanitize incoming request parameters
if (isset_request_var('filter')) {
	set_request_var('filter', sanitize_search_string(get_request_var('filter')));
}

if (isset_request_var('type')) {
	set_request_var('type', sanitize_search_string(get_request_var('type')));
	load_current_session_value("type", "sess_grid_meta_type", "application");
} else {
	load_current_session_value("type", "sess_grid_meta_type", "application");
}

// Process the requested action
switch(get_request_var('action')) {
	case 'load':
		load();
		break;
	case 'create':
		create();
		break;
	case 'save':
		save();
		break;
	case 'delete':
		metadata_delete();
		break;
	case 'detail':
		metadata_details();
		break;
	case 'add':
		metadata_add();
		break;
	case 'popup':
		metadata_popup();
		break;
	case 'queue_list':
		metadata_queue_list();
		break;
}

if(!isempty_request_var('drp_action')) {
	switch(get_filter_request_var('drp_action')) {
		case 1:
			metadata_delete();
			break;
	}
}

//Forward to list page after create/delete action
switch(get_request_var('action')) {
	case 'save':
	case 'delete':
	case 'list':
		list_metadata();
		break;
}

function list_metadata() {
	global $config;

	include_once($config["library_path"] . '/rtm_functions.php');

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
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => get_default_type(),
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
			'default' => 'object_id',
			'options' => array('options' => 'sanitize_search_string')
			),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
			),
	);

	validate_store_request_vars($filters, 'sess_gmeta');
	/* ================= input validation ================= */

	if (get_request_var('rows') == '-1') {
		$rows = read_config_option('num_rows_table');
	}else{
		$rows = get_request_var('rows');
	}

	/////////////////////////////////////////////////////////////////////////
	// Prepare and run the SQL statements for the page
	$sql_where = '';

	// Build the WHERE clause for our SQL statement, based on the filter
	// We compare the filter to all columns defined as searchable for
	// the object type
	$search_cols = get_metadata_search_cols(get_request_var('type', get_default_type()));

	if (get_request_var('filter') != '') {
		$sql_where .= " (object_id LIKE '%" . get_request_var('filter') . "%' ";

		foreach ($search_cols as $search_col) {
			$sql_where .= ' OR ' . $search_col['db_column_name'] . " LIKE '%" . get_request_var('filter') . "%'";
		}

		$sql_where .= ')';
	}

	$total_rows       = get_metadata_row_count($sql_where, get_request_var('type'), TRUE, $rows);
	$metadata_results = get_object_metadata(get_request_var('type'), '', '', $sql_where, TRUE, TRUE, $rows);

	/////////////////////////////////////////////////////////////////////////
	// Draw the list screen
	top_header();

	// Check if the user is authorized to edit metadata; the result is used in many places
	$disabled = !api_user_realm_auth('Edit_Metadata');

	// Prepare to map from cluster ids to cluster names
	$clusters_mapping = array_rekey(
		db_fetch_assoc('SELECT clusterid, clustername FROM grid_clusters'),
		'clusterid', 'clustername'
	);

	$clusters_mapping[0] = __('All', 'meta');

	// Filter table header
	if (!$disabled) {
		// Has configuration been loaded for this object type? If not, do not allow objects to be added
		$result = db_fetch_cell_prepared('SELECT count(*)
			FROM grid_metadata_conf
			WHERE object_type = ?',
			array(get_request_var('type')));

		if ($result == 0) {
			html_start_box(__('Metadata Filters', 'meta'), '100%', '', '3', 'center', '');
		} else {
			html_start_box(__('Metadata Filters', 'meta'), '100%', '', '3', 'center', 'metadata.php?action=add&type=' . get_request_var('type'));
		}
	} else {
		html_start_box(__('Metadata Filters', 'meta'), '100%', '', '3', 'center', '');
	}

	filter();

	html_end_box(false);

	if (!$disabled) {
		form_start('metadata.php?action=delete', 'chk');
	}

	$nav = html_nav_bar('metadata.php?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, '', __('Items', 'meta'), 'page', 'main');

	print $nav;

	html_start_box('', '100%', '', '3', 'center', '');

	// Every object has an object id and a cluster id
	$display_text = array(
		'object_id'  => array(__('Object ID', 'meta'), 'ASC'),
		'cluster_id' => array(__('Cluster ID', 'meta'), 'ASC'));

	// Get all the other fields defined for this object type
	$headers = get_metadata_record_headers(get_request_var('type'));

	// Add them to the array of headers
	foreach ($headers as $header) {
		$display_name = $header['display_name'];
		$display_text[$header['db_column_name']] = array($display_name, 'ASC');
	}

	if (!$disabled) {
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), '', false);
	} else {
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), 'metadata.php?action=delete');
	}

	// If there are rows returned for the current object type and filter
	if (cacti_sizeof($metadata_results)) {
		$object_id    = '';
		$row_count    = 0;
		$column_count = 0;
		$object_type  = get_request_var('type');

		// For each row of results
		foreach ($metadata_results as $result) {
			// Start a new row
			$object_id  = $result['object_id'];
			$cluster_id = $result['cluster_id'];

			$id = 'metadata_elem_' . $row_count++;
			// Used to identify the line, both for highlighting and deletion
			$mangled_elem_name = rtm_base64url_encode($object_id . '###' . $cluster_id);

			form_alternate_row('line' . $mangled_elem_name, $disabled, $disabled);

			// What is the user authorized to do?
			// Print the object id field
		    $meta_param = json_encode(
 		    	array(
		            'type'       => $object_type,
    		        'cluster-id' => $cluster_id,
        		    'object-key' => $object_id
		        )
		    );

			$meta_id = rtm_base64url_encode($meta_param);

			if (api_user_realm_auth('Edit_Metadata')) {
				rtm_selectable_cell(filter_value($object_id, get_request_var('filter'), 'metadata.php?action=detail&type=' . $object_type . '&object_id=' . urlencode($object_id) . '&cluster_id=' . $cluster_id), $meta_id, '', 'meta-simple');
			} else {
				rtm_selectable_cell(filter_value($object_id, get_request_var('filter')), $meta_id, '', 'meta-simple');
			}

			// Draw the cluster id field
			form_selectable_cell($clusters_mapping[$cluster_id], $mangled_elem_name);

			// Draw the custom columns for this table
			$metadata_conf = get_object_metadata_conf($object_type);
			foreach ($metadata_conf as $metadata_conf_elem) {
				if ($metadata_conf_elem['summary'] == 1) {
					$value = $result[$metadata_conf_elem['db_column_name']];
					$data_type = $metadata_conf_elem['data_type'];

					$url = metadata_url($data_type, $value);

					form_selectable_cell(filter_value($value, get_request_var('filter'), $url), $mangled_elem_name);
				}
			}

			// Complete the row with the checkbox
			// The metadata object id may contain characters that POST will mangle, so
			// encode the object id here
			if (!$disabled) {
				form_checkbox_cell($mangled_elem_name, $mangled_elem_name);
			}
			form_end_row();
		}

	} else {
		print "<tr><td colspan='11'><em>" . __('No metadata records found', 'meta') . "</em></td></tr>";
	}

	/////////////////////////////////////////////////////////////////////////
	// Finalize drawing the table body
	html_end_box(false);

	if (cacti_sizeof($metadata_results))
		print $nav;

	$metadata_actions = array(
		1 => __('Delete', 'meta')
	);

	if (!$disabled) {
		draw_actions_dropdown($metadata_actions);
		form_end();
	}

	meta_page_bottom();

	// Draw the page footer
	bottom_footer();
}

function metadata_url($data_type, &$value){
	$url = '';
	if ($data_type == 'url' && strlen($value)) {
		if (strstr($value, ' ') === false && (strstr($value, '://') !== false || strstr($value, 'mailto') !== false)) {
			$url = "$value";
			$value = str_replace('mailto:', '', $value);
		}
	}
	return $url;
}

function metadata_details() {
	global $meta_valid_object_types;
	top_header();

	$url = "'?action=detail&object_id=' + objForm.object_id.value + '&type=' + objForm.type.value +
		'&cluster_id=' + objForm.cluster_id.value + '&meta_col1=' + objForm.meta_col1.value\n";
	if (get_request_var('type') == 'queue-group') {
	?>
<script type='text/javascript'>
	<!--
	$(function() {
		$('#meta_col2').multiselect({
			height: 240,
			noneSelectedText: '<?php print __('Select Queue(s)');?>',
			selectedText: function(numChecked, numTotal, checkedItems) {
				myReturn = numChecked + ' <?php print __('Queues Selected');?>';
				return myReturn;
			},
			checkAllText: '<?php print __('All');?>',
			uncheckAllText: '<?php print __('None');?>',
			uncheckall: function() {
				$(this).multiselect('widget').find(':checkbox:first').each(function() {
					$(this).prop('checked', true);
				});
			}
		}).multiselectfilter( {
			label: '<?php print __('Search');?>',
			placeholder: '<?php print __('Enter keyword');?>',
			width: '150'
		});
	});

	-->
</script>
<?php
	}

	// Configuration for the form
	$config_array = array();
	$config_array["no_form_tag"] = true;

	// Draw the edit form for the details
	$form_array = array();

	// Get the object data to display in the form
	$object_data = get_object_metadata(get_request_var('type'),
		rawurldecode(get_request_var('object_id')), get_request_var('cluster_id'));
	$conf_data = get_object_metadata_conf(get_request_var('type'));

	// It's possible that no configuration has been loaded for this type
	if (cacti_sizeof($conf_data) == 0) {
		raise_message(50119);
		top_header();
		return;
	}

	if (api_user_realm_auth('Edit_Metadata')) {
		form_start('metadata.php?action=save&type=' . get_request_var('type'), 'medit');
	}

	if (api_user_realm_auth('Edit_Metadata')) {
		html_start_box(__('Edit Object - %s - %s', $meta_valid_object_types[get_request_var('type')], htmlspecialchars(get_request_var('object_id')), 'meta'), '100%', '', '3', 'center', '');
	} else {
		html_start_box(__('View Object - %s - %s', $meta_valid_object_types[get_request_var('type')], htmlspecialchars(get_request_var('object_id')), 'meta'), '100%', '', '3', 'center', '');
	}

	// If the object id is present in the db (it may have been deleted asynchronously)
	if (cacti_sizeof($object_data) > 0) {

		$section_header = '';
		$section_number = 0;

		// Add the identifier section first
		$section_header = __('Identifier', 'meta');
		$form_array[$section_header . $section_number]['method'] = 'spacer';
		$form_array[$section_header . $section_number]['friendly_name'] = $section_header;
		$section_number++;

		// Insert the hidden object type form element
		$form_array['object_type']['value'] = get_request_var('type');
		$form_array['object_type']['method'] = 'hidden';
		$form_array['object_type']['form_id'] = 'object_type';

		// Insert the hidden object id form element
		$form_array['object_id']['value'] = get_request_var('object_id');
		$form_array['object_id']['method'] = 'hidden';
		$form_array['object_id']['form_id'] = 'object_id';

		// Insert the displayed object id form element
		$form_array['object_id_display']['value'] = $object_data[0]['object_id'];
		$form_array['object_id_display']['method'] = 'custom';
		$form_array['object_id_display']['friendly_name'] = __('Object Identifier', 'meta');
		$form_array['object_id_display']['description'] = __('The object identifier for this object', 'meta');

		// Insert the hidden cluster id form element
		$form_array['cluster_id']['value'] = get_request_var('cluster_id');
		$form_array['cluster_id']['method'] = 'hidden';
		$form_array['cluster_id']['form_id'] = 'cluster_id';

		// Insert the displayed cluster id form element
		if ($object_data[0]['cluster_id'] != 0) {
			$form_array['cluster_id_display']['value'] = db_fetch_cell_prepared('SELECT clustername
				FROM grid_clusters
				WHERE clusterid = ?',
				array($object_data[0]['cluster_id']));
		} else {
			$form_array['cluster_id_display']['value'] = __('All', 'meta');
		}

		$form_array['cluster_id_display']['method'] = 'custom';
		$form_array['cluster_id_display']['friendly_name'] = __('Cluster Identifier', 'meta');
		$form_array['cluster_id_display']['description'] = __('The cluster identifier for this object', 'meta');

		// Print each data field for the metadata type
		foreach($conf_data as $conf_datum) {

			// If we hit an element with a new section header, we print out the section header
			if ($conf_datum['section_name'] != $section_header) {
				$form_array[$section_header . $section_number]['method'] = 'spacer';
				$form_array[$section_header . $section_number]['friendly_name'] = $conf_datum['section_name'];
				$section_header = $conf_datum['section_name'];
				$section_number++;
			}

			$col_name = $conf_datum['db_column_name'];

			// For the queue group object type, special handling for the list of queues
			if ($conf_datum['data_type'] == 'queue_list') {
				$form_array[$col_name]['method'] = 'drop_multi';

				// If the cluster_id on the object is invalid, it will have been updated to the
				// first found cluster is stored by RTM; if there are no clusters stored in the database,
				// this query will return zero rows
				$queues = db_fetch_assoc_prepared('SELECT queuename
					FROM grid_queues
					WHERE clusterid = ?',
					array(get_request_var('cluster_id')));

				$queue_arr = array();
				foreach ($queues as $queue) {
					$queue_arr[$queue['queuename']] = $queue['queuename'];
				}
				$form_array[$col_name]['array'] = $queue_arr;

				// Build the dummy SQL statement to get the right elements selected in
				// the multi-select
				$queue_components = explode(' ', $object_data[0][$col_name]);

				$i = 0;
				$sql = '';
				foreach ($queue_components as $queue_component) {
					if ($i == 0) {
						$sql .= "SELECT '" . $queue_component . "' AS id";
					} else {
						$sql .= ' UNION ';
						$sql .= "SELECT '" . $queue_component . "' AS id";
					}
					$i++;
				}
				$form_array[$col_name]['sql'] = $sql;
				$form_array[$col_name]['value'] = '';
			} else {
				$form_array[$col_name]['value'] =
					isset_request_var($col_name) ? get_request_var($col_name) : $object_data[0][$col_name];
				$form_array[$col_name]['method'] = 'textbox';
			}
			$form_array[$col_name]['friendly_name'] = $conf_datum['display_name'];
			$form_array[$col_name]['description'] = $conf_datum['description'];
			$form_array[$col_name]['max_length'] = '1000';
			$form_array[$col_name]['form_id'] = $conf_datum['db_column_name'];
		}

		draw_edit_form(array('config' => $config_array,'fields' => $form_array));

		form_hidden_box('edit', '', '');

		// Finalize drawing the table body
		html_end_box();

		if (api_user_realm_auth('Edit_Metadata')) {
			form_save_button('metadata.php', '', 'object_id');
		} else {
			?>
			<form action='metadata.php'>
				<table class='odd center cactiTable'>
					<tr>
						<td>
							<input type='submit' value='<?php print __('Ok', 'meta');?>'>
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	} else {
		print '<tr><td colspan="4"><em>' . __('This object no longer exists', 'meta') . '</em></td></tr>';

		html_end_box();

		?>
		<form action='metadata.php'>
			<table class='odd center cactiTable'>
				<tr>
					<td>
						<input type='submit' value='<?php print __('Ok', 'meta');?>'>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}

	// Draw the page footer
	bottom_footer();
}

function metadata_add() {
	global $meta_valid_object_types;
	top_header();

	if (get_request_var('type') == 'queue-group') {
	?>
<script type='text/javascript'>
	$(function() {
		$('select#cluster_id').change(function() {
			var id=$(this).val();
			var dataString = 'cluster_id=' + id;

			$.get('metadata.php?action=queue_list' +
				'&cluster_id=' + $('#cluster_id').val(), function(data) {
				$('#meta_col2').html(data);
				$('#meta_col2').multiselect('refresh');
			});
		}).change();

		$('#meta_col2').multiselect({
			height: 300,
			noneSelectedText: '<?php print __('Select Queue(s)');?>',
			selectedText: function(numChecked, numTotal, checkedItems) {
				myReturn = numChecked + ' <?php print __('Queues Selected');?>';
				return myReturn;
			},
			checkAllText: '<?php print __('All');?>',
			uncheckAllText: '<?php print __('None');?>',
			uncheckall: function() {
				$(this).multiselect('widget').find(':checkbox:first').each(function() {
					$(this).prop('checked', true);
				});
			}
		}).multiselectfilter( {
			label: '<?php print __('Search');?>',
			placeholder: '<?php print __('Enter keyword');?>',
			width: '150'
		});

	});
</script>
	<?php
	}

	form_start('metadata.php?action=create&type=' . get_request_var('type'), 'medit');

	html_start_box(__('Add Object - %s', $meta_valid_object_types[get_request_var('type')], 'meta'), '100%', '', '3', 'center', '');

	// Configuration for the form
	$config_array = array();
	$config_array['no_form_tag'] = true;

	// Draw the edit form for the details
	$form_array = array();

	$object_data = get_add_object_data(get_request_var('type'));

	// Insert the hidden object type form element
	$form_array['object_type']['value'] = get_request_var('type');
	$form_array['object_type']['method'] = 'hidden';
	$form_array['object_type']['form_id'] = 'object_type';

	$section_header = '';
	$section_number = 0;

	// Add the identifier section first
	$section_header = __('Identifier', 'meta');
	$form_array[$section_header . $section_number]['method'] = 'spacer';
	$form_array[$section_header . $section_number]['friendly_name'] = $section_header;
	$section_number++;

	// Object ID field
	$form_array['object_id']['method'] = 'textbox';
	$form_array['object_id']['friendly_name'] = __('Object Identifier', 'meta');
	$form_array['object_id']['description'] = __('The object identifier for this object', 'meta');
	$form_array['object_id']['max_length'] = '1000';
	$form_array['object_id']['form_id'] = 'object_id';

	// Set the default for the object id field
	if (isset_request_var('object_id')) {
		$form_array['object_id']['value'] = get_request_var('object_id');
	} else if (isset_request_var('object_id')) {
		$form_array['object_id']['value'] = urldecode(get_request_var('object_id'));
	} else {
		// If this is a queue-group, set the queue group id automatically
		$matches[] = array();
		if (get_request_var('type') == 'queue-group') {
			// Go looking for qgroups of the form ^<qgroup><number>$. A complicating factor
			// is that the user may have created groups like 'ggrouper' or 'qgroupy'
			$max_id = 'qgroup' . db_fetch_cell_prepared("SELECT max(CAST(substr(object_id, 7, length(object_id) - 6) AS SIGNED))
				FROM grid_metadata WHERE object_type = 'queue-group' AND object_id REGEXP '^qgroup[0-9]+$'");
			// The query strips out the qgroup part, so we add it back

			if (!isset($max_id) || $max_id == '') {
				// No queue groups of this form exist
				$form_array['object_id']['value'] = 'qgroup1';
			} else {
				// Each generated id will be of the form (qgroup)(number)
				// So we look for the number portion of the max id, increment it by one, and use that value as the new id
				preg_match('/(^qgroup)(\d+$)/', $max_id, $matches);

				if (isset($matches[0])) {
					$numberportion = ((int)$matches[2]) + 1;
					$form_array['object_id']['value'] = 'qgroup' . $numberportion;
				} else {
					// qgroup% matched something else the user created
					$form_array['object_id']['value'] = 'qgroup1';
				}
			}
		} else {
			$form_array['object_id']['value'] = '';
		}
	}

	// Cluster ID field
	$form_array['cluster_id']['method'] = 'drop_sql';
	$form_array['cluster_id']['friendly_name'] = __('Cluster Identifier', 'meta');
	$form_array['cluster_id']['description'] = __('The cluster identifier for this object', 'meta');
	$form_array['cluster_id']['form_id'] = 'cluster_id';

	// 'All' clusters not allowed for the queue-group type
	if (get_request_var('type') == 'queue-group') {
		$form_array['cluster_id']['sql'] = 'SELECT clusterid AS id,
			clustername AS name FROM grid_clusters ORDER by id';
	} else {
		$form_array['cluster_id']['sql'] = "SELECT 0 AS id, '" . __('All', 'meta') . "' AS name UNION ALL
			SELECT clusterid AS id, clustername AS name FROM grid_clusters ORDER by id";
	}

	// Set the default for the cluster id field
	if (isset_request_var('cluster_id')) {
		$form_array['cluster_id']['value'] = get_request_var('cluster_id');
	} else if (isset_request_var('cluster_id')) {
		$form_array['cluster_id']['value'] = get_request_var('cluster_id');
	} else {
		$form_array['cluster_id']['value'] = '';
	}

	// Print the remaining custom-defined fields for the object
	foreach($object_data as $object_datum) {

		// If we hit an element with a new section header, we print out the section header
		if ($object_datum['section_name'] != $section_header) {
			$form_array[$section_header . $section_number]['method'] = 'spacer';
			$form_array[$section_header . $section_number]['friendly_name'] = $object_datum['section_name'];
			$section_header = $object_datum['section_name'];
			$section_number++;
		}

		// Now to print the object metadatum itself
		$col_name = $object_datum['db_column_name'];
		$form_array[$col_name]['value'] = '';

		if ($object_datum['data_type'] == 'queue_list') {
			$form_array[$col_name]['method'] = 'drop_multi';

			// Get queues for the first cluster
			if (isset_request_var('cluster_id') && get_request_var('cluster_id') > 0) {
				$queues = db_fetch_assoc_prepared('SELECT queuename
					FROM grid_queues
					WHERE clusterid = ?
					ORDER BY queuename',
					array(get_request_var('cluster_id')));
			}else{
				$queues = db_fetch_assoc('SELECT DISTINCT queuename
					FROM grid_queues ORDER BY queuename');
			}

			$queue_arr = array();
			foreach ($queues AS $queue) {
				$queue_arr[$queue['queuename']] = $queue['queuename'];
			}
			$form_array[$col_name]['array'] = $queue_arr;
			$form_array[$col_name]['sql'] = "SELECT '' AS id, '' AS name";
			$form_array[$col_name]['value'] = '';

			// Cacti's multi-element dropdown code is not friendly to use here; we need to construct
			// a dummy SQL statement
			if (isset_request_var('meta_col3')) {
				$sql = '';
				$i = 0;
				foreach (get_request_var('meta_col3') as $key => $value) {
					if ($i == 0) {
						$sql .= "SELECT '" . $value . "' AS id ";
						$i++;
					} else {
						$sql .= "UNION SELECT '" . $value . "' AS id ";
					}
				}
				$form_array[$col_name]['sql'] = $sql;
			}
		}
		else {
			$form_array[$col_name]['method'] = 'textbox';
			$form_array[$col_name]['max_length'] = '1000';
			$form_array[$col_name]['value'] = isset_request_var($col_name) ? get_request_var($col_name) : '';
		}
		$form_array[$col_name]['friendly_name'] = $object_datum['display_name'];
		$form_array[$col_name]['description'] = $object_datum['description'];
		$form_array[$col_name]['form_id'] = $object_datum['db_column_name'];

		// Did we arrive here because of errors in the original POST?
		if (isset_request_var($object_datum['db_column_name'])) {
			$form_array[$col_name]['value'] = get_request_var($object_datum['db_column_name']);
		}
	}

	draw_edit_form(array('config' => $config_array,'fields' => $form_array));

	// Finalize drawing the table body
	form_hidden_box('edit', '', '');

	html_end_box();

	form_save_button('metadata.php', '', 'object_id');

	bottom_footer();
}

function metadata_popup() {
	global $config;

	include_once($config["library_path"] . '/rtm_functions.php');

	$meta_type  = '';
	$meta_key   = '';
	$cluster_id = '';

	if(isset_request_var('meta_id') && !isempty_request_var('meta_id')){
		$meta_pk = (array)json_decode(rtm_base64url_decode(get_request_var('meta_id')));

		$meta_type  = $meta_pk['type'];
		$meta_key   = $meta_pk['object-key'];
		$cluster_id = $meta_pk['cluster-id'];
	} else {
		$meta_type  = get_request_var('type');
		$meta_key   = get_request_var('object_id');
		$cluster_id = get_request_var('cluster_id');
	}
	$object_data = get_object_metadata($meta_type, $meta_key, $cluster_id);

	// The object may have been deleted during display
	if (cacti_sizeof($object_data) == 0) {
		//print __('This object no longer exists', 'meta');
		return;
	}

	$conf_data = get_object_metadata_conf($meta_type);

	// It's possible that no configuration has been loaded for this object type
	if (cacti_sizeof($conf_data) == 0) {
		//print __('No configuration is currently loaded for this object type', 'meta');
		return;
	}

	// Print the tabs themselves
	print "<div id='meta_tabs'>";
	print '<ul>';
	$i = 0;
	$section_name = '';
	foreach ($conf_data as $conf_datum) {
		if ($conf_datum['section_name'] != $section_name) {
			$section_name = htmlspecialchars($conf_datum['section_name']);
			print "<li><a href='#meta_tabs-$i'>$section_name</a></li>";
			$i++;
		}
	}
	print '</ul>';

	// Print the tab content
	$j = 0;
	$section_name = '';
	foreach ($conf_data as $conf_datum) {

		if ($conf_datum['section_name'] != $section_name && $j == 0) {
			print "<div id='meta_tabs-$j'>";
			print '<table>';

			$section_name = $conf_datum['section_name'];
			$j++;
		}

		if ($conf_datum['section_name'] != $section_name && $j > 0) {

			print '</table>';
			print '</div>';

			print "<div id='meta_tabs-$j'>";
			print '<table>';

			$section_name = $conf_datum['section_name'];
			$j++;
		}

		print '<tr>';

		// Special formatting for specific data types, including URL
		$value = '<td><strong>' . $conf_datum['display_name'] . ':</strong></td>  ';
		$meta_value=$object_data[0][$conf_datum['db_column_name']];
		$url = metadata_url($conf_datum['data_type'], $meta_value);
		if ($url != '') {
			$value .= "<td><a class='linkEditMain' target='_blank' href='" . $url ."'>" .htmlspecialchars($meta_value) . '</a>';
			$value .= '</td>';
		} else if ($conf_datum['data_type'] == 'CALLBACK') {
			//$contents = popup_callback($object_data[$conf_datum["db_column_name"]);
			$value .= '<td>' . $contents . '</td>';
		}
		else {
			$value .= '<td>' . htmlspecialchars($object_data[0][$conf_datum['db_column_name']]) . '</td>';
		}
		print $value;
		print '</tr>';

	}
	print '</table>';
	// Overcomes an issue with automatic initial resizing to content of the dialog
	// using IE 8 (at least), and a metadata object with only one column of metadata
	// (dialog would initially be too low to show all the content of the dialog)
	print '</div>';

	print '</div>';
}

function metadata_queue_list() {
	if (get_filter_request_var('cluster_id') > 0) {
		$results = db_fetch_assoc_prepared('SELECT queuename
			FROM grid_queues
			WHERE clusterid = ?',
			array(get_request_var('cluster_id')));

		foreach ($results as $result) {
			echo "<option value='" . htmlspecialchars($result['queuename']) . "'>" . htmlspecialchars($result['queuename']) . '</option>';
		}
	}
}
