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

global $meta_valid_object_types, $valid_object_columns;

// Preset meta columns for queue groups
global $meta_qgroup_display_name_col, $meta_qgroup_queue_list_col;
$meta_qgroup_display_name_col = 'meta_col1';
$meta_qgroup_queue_list_col = 'meta_col2';

// The set of valid metadata object types
$meta_valid_object_types = array(
	'user'            => __('User', 'meta'),
	'user-group'      => __('User Group', 'meta'),
	'host'            => __('Host', 'meta'),
	'host-group'      => __('Host Group', 'meta'),
	'job-group'       => __('Job Group', 'meta'),
	'queue'           => __('Queue', 'meta'),
	'project'         => __('Project', 'meta'),
	'license-project' => __('License Project', 'meta'),
	'application'     => __('Application', 'meta'),
	'queue-group'     => __('Queue Group', 'meta')
);

// The set of valid metadata columns
$valid_object_columns = array(
	'object_id', 'cluster_id', 'meta_col1', 'meta_col2',
	'meta_col3', 'meta_col4', 'meta_col5', 'meta_col6',
	'meta_col7', 'meta_col8', 'meta_col9', 'meta_col10',
	'meta_col11', 'meta_col12', 'meta_col13', 'meta_col14',
	'meta_col15', 'meta_col16', 'meta_col17', 'meta_col18',
	'meta_col19', 'meta_col20'
);

/**
 * Hook function used to put in place the hidden div for the popup metadata dialog
 */
function meta_page_bottom() {
	print '<div id="metadialog" title="' . __('Metadata Details') . '"></div>';
	if (api_user_realm_auth('metadata.php')) {
		?>
		<script type='text/javascript'>
		$(function(){
			$('#metadialog').dialog({
				autoOpen: false,
				autoResize: true,
				resizable: false,
				minHeight: 80
			});

			$('#metadialog').mouseleave(function() {
				closeDialog();
			});

			$('#metadialog').mouseenter(function() {
				clearTimeout(timer1);
			});

			$('.meta-detailed, .meta-simple').on('mousemove', function(event){
				var id = $(this).attr('id');
				closeDialog();
				if( metashown != 1){
					metashown = 1;
					timer2=setTimeout(function() {
						openDialog(event, id)}, 200);
				}
			});

			$('.meta-detailed, .meta-simple').on('mouseout', function(event){
				clearTimeout(timer2);
				metashown = 0;
				timer1=setTimeout(function() {
					closeDialog();
				}, 500);
			});

			$('.meta-detailed, .meta-simple').on('click', function(event){
				clearTimeout(timer2);
				metashown = 0;
				timer1=setTimeout(function() {
					closeDialog();
				}, 500);
			});
		});
		</script>
		<?php
	}
}

/**
 * Hook function used to put in place the required Javascript for the popup metadata dialog
 */
function meta_page_head() {
	print get_md5_include_js('plugins/meta/include/main.js');
}

function meta_preprocess_object_json($object) {

	if (!api_user_realm_auth('metadata.php')) {
		return '';
	}

	$object = json_decode($object, true);

	if ($object['type'] == 'user') {
		$count = read_metadata_count($object['type'], $object['object-key']);

		// Is metadata configured for the user object at all?
		if ($count != 0) {
			// User object metadata has been loaded
			$full_name = db_fetch_cell("SELECT db_column_name
				FROM grid_metadata_conf
				WHERE object_type = 'user'
				AND display_name = 'Full Name'");

			$normal_name = array_rekey(
				db_fetch_assoc("SELECT db_column_name, 'last' AS part
					FROM grid_metadata_conf
					WHERE object_type = 'user' AND display_name='last name'
					UNION ALL
					SELECT db_column_name, 'first' AS part
					FROM grid_metadata_conf
					WHERE object_type = 'user' AND display_name='first name'"),
				'part', 'db_column_name'
			);

			if (!empty($full_name)) {
				$username = db_fetch_cell_prepared("SELECT $full_name
					FROM grid_metadata
					WHERE object_type = 'user'
					AND object_id = ?",
					array($object['object-key']));

				if (!empty($username)) {
					return $username . ' (' . $object['object-key'] . ')';
				} else {
					return $object['object-key'];
				}
			} elseif (cacti_sizeof($normal_name)) {
				$sql = '';

				foreach($normal_name as $part => $column) {
					$sql .= ($sql != '' ? ',' : 'SELECT') . " $column AS $part";
				}

				$sql .= " FROM grid_metadata
					WHERE object_type = 'user' AND object_id = ?";

				$data = db_fetch_row_prepared($sql, array($object['object-key']));

				if (cacti_sizeof($data)) {
					$username = '';
					if ($data['last'] != '') {
						$username = $data['last'];
					}

					if ($data['first'] != '') {
						$username = ($username != '' ? ', ':'') . $data['last'];
					}

					return $username . ' (' . $object['object-key'] . ')';
				} else {
					return $object['object-key'];
				}
			}
		} else {
			return $object['object-key'];
		}
	} else {
		return $object['object-key'];
	}
}

/**
 * Determines if the request paramater value is different from the session
 * parameter value
 * @$request
 * @$session
 */
function meta_check_changed($request, $session) {
	if ((isset_request_var($request)) && (isset($_SESSION[$session]))) {
		if (get_request_var($request) != $_SESSION[$session]) {
			return 1;
		}
	}
}

/**
 * Gets the metadata information, for adding a new object
 * @$object_type
 */
function get_add_object_data($object_type) {
	return db_fetch_assoc_prepared('SELECT conf.object_type, conf.db_column_name, conf.data_type,
		conf.section_name, conf.display_name, conf.description
		FROM grid_metadata_conf conf
		WHERE conf.object_type = ?
		ORDER BY position ASC',
		array($object_type));
}

/**
 * Returns the configuration and metadata for a class of objects, or a specific object
 *
 * @$object_type The type of metadata object to retrieve
 * @$object_id If passed, the specific object to retrieve
 * @cluster_id If passed, the cluster id for this object
 * @$sql_where If passed, the SQL where clause (without "WHERE") to append to the query
 * @$summary Should only summary = 1 columns be selected?
 * @$apply_limits Should the number of rows returned be limited?
 * @$row_limit If so, how many rows should be returned?
 */
function get_object_metadata($object_type, $object_id = '', $cluster_id = '', $sql_where = '', $summary = false, $apply_limits = false, $row_limit = 100000) {

	if (!api_user_realm_auth('metadata.php')) {
		return array();
	}

	$inner_sql = 'SELECT
		object_id, cluster_id, meta_col1, meta_col2, meta_col3, meta_col4, meta_col5, meta_col6, meta_col7,
		meta_col8, meta_col9, meta_col10, meta_col11, meta_col12, meta_col13, meta_col14, meta_col15, meta_col16,
		meta_col17, meta_col18, meta_col19, meta_col20
		FROM grid_metadata
		WHERE
		object_type = ' . db_qstr($object_type);

	// Filter the results
	if ($sql_where != '') {
		$inner_sql .= " AND $sql_where";
	}

	// Only show data for a specific object, if requested
	if ($object_id != '') {
		$inner_sql .= " AND object_id = " . db_qstr($object_id);
	}

	// Only show data for a specific cluster, is requested
	if ($cluster_id != '') {
		$inner_sql .= ' AND (cluster_id = ' . $cluster_id . ' OR cluster_id = 0)';
	}

	// Non sort for meta data display(popup)
	if (get_current_page() == 'metadata.php') {
		// Only sort when multiple objects are being returned
		$order_stmt=get_order_string();
		if(strlen($order_stmt) >= 13) //ORDER BY ``
			$inner_sql .= get_order_string();
	}

	// Limit the number of rows
	if ($apply_limits) {
		$inner_sql .= ' LIMIT ' . ($row_limit*(get_request_var('page')-1)) . ',' . $row_limit;
	}

	// Execute the SQL
	//cacti_log("DEBUG inner_sql: $inner_sql");
	return db_fetch_assoc($inner_sql);
}

/**
 * Returns the metadata configuration for a given metadata object type
 */
function get_object_metadata_conf($object_type) {
	return db_fetch_assoc_prepared('SELECT DISTINCT object_type, display_name, description, db_column_name,
		data_type, section_name, position, summary
		FROM grid_metadata_conf
		WHERE object_type = ?
		ORDER BY position',
		array($object_type));
}

/**
 * Gets a row count for the given object type
 *
 * @$sql_where The SQL where clause to append to the query
 * @$object_type The type of metadata object to search for
 * @$apply_limits Should limits be applied to the SQL query?
 * @$row_limit If so, what is the limit on the number of rows?
 */
function get_metadata_row_count(&$sql_where, $object_type, $apply_limits = true, $row_limit) {
	$sql = 'SELECT count(*) FROM grid_metadata WHERE object_type = ' . db_qstr($object_type);

	if (strlen($sql_where)) {
		$sql .= ' AND ' . $sql_where;
	}

	return db_fetch_cell($sql);
}

/**
 * Gets metadata record headers for the summary table
 *
 * @$object_type The type of metadata object
 */
function get_metadata_record_headers($object_type) {
	return db_fetch_assoc_prepared('SELECT db_column_name, display_name
		FROM grid_metadata_conf
		WHERE object_type = ?
		AND summary = 1
		ORDER BY position ASC',
		array($object_type));
}

/**
 * Gets the search fields for this object type
 *
 * @$object_type The type of metadata object
 */
function get_metadata_search_cols($object_type) {
	return db_fetch_assoc_prepared('SELECT db_column_name
		FROM grid_metadata_conf
		WHERE object_type = ?
		AND search = 1',
		array($object_type));
}

/**
 * Builds a SQL SELECT statement for the summary view, based on object_type
 */
function build_meta_select_summary($object_type) {
	$columns = db_fetch_assoc_prepared('SELECT db_column_name
		FROM grid_metadata_conf
		WHERE object_type = ?
		AND summary = 1
		ORDER BY position ASC',
		array($object_type));

	$select_sql = 'SELECT `object_id`';

	foreach ($columns as $column) {
		$select_sql .= ',`' . $column['db_column_name'] . '`';
	}

	$select_sql .= ' FROM grid_metadata WHERE `object_type` = ' . db_qstr($object_type);

	return $select_sql;
}

/**
 * Gets metadata record headers for the detail table
 */
function get_metadata_column_headers($object_type) {
	return db_fetch_assoc_prepared('SELECT db_column_name, section_name, display_name, description
		FROM grid_metadata_conf
		WHERE object_type = ?
		ORDER BY position ASC',
		array($object_type));
}

/**
 * Builds a SQL SELECT statement for the detail view, based on object_type
 */
function build_meta_select_detail($object_type) {
	return db_fetch_assoc_prepared('SELECT db_column_name, section_name, display_name, description
		FROM grid_metadata_conf
		WHERE object_type = ?
		ORDER BY position ASC',
		array($object_type));
}

/**
 * Creates metadata for the specified object
 */
 function create_metadata() {
	// Validate that the new object's id is unique
	$is_unique = db_fetch_cell_prepared('SELECT count(*)
		FROM grid_metadata
		WHERE object_type = ?
		AND object_id = ?
		AND cluster_id = ?',
		array(get_request_var('type'), get_request_var('object_id'), get_request_var('cluster_id')));


	// The object is not unique
	if ($is_unique != 0) {
		return 50120;
	}

	// Insert the metadata
	$sql = 'INSERT INTO grid_metadata(';
	$j = 0;
	foreach ($_POST AS $key => $value) {
		if ($key == 'action' || $key == 'edit' || $key =='__csrf_magic') {
			// do nothing
		} else {
			// Validate the object id
			if ($key == 'object_id') {
				if (!meta_validate_object_id($value) || $value == '') {
					return 50117;
				}
			}

			// Cluster id does not need to be validated due to the design of the UI
			if ($j == 0)
			{
				$sql .= $key;
				$j++;
			}
			else {
				$sql .= ', ' . $key;
			}
		}
	}
	$sql .= ') VALUES (';
	$i = 0;
	foreach ($_POST AS $key => $value) {
		if ($key == 'action' || $key == 'edit' || $key =='__csrf_magic') {
			// do nothing
		} else {
			$insert_value = $value;
			// Is this value the components of a queue list?
			if (get_request_var('type') == 'queue-group' && $key == 'meta_col2' && is_array($value)) {
				$insert_value = '';
				foreach ($value AS $queue) {
					$insert_value .= $queue . ' ';
				}
			}
			if ($i != 0) {
				$sql .= ', ';
			}
			$sql .= db_qstr($insert_value);
			$i++;
		}
	}
	$sql .= ')';
	$result = db_execute($sql);

	if (!$result) {
		return 50118;
	}

	return 0;

}

/**
 * Saves updated metadata for a specified object
 */
function save_metadata() {
	// Insert the metadata
	$sql = 'INSERT INTO grid_metadata(';
	$j = 0;
	foreach ($_POST AS $key => $value) {
		if ($key == 'action' || $key == 'edit' || $key =='__csrf_magic' || $key =='type') {
			// do nothing
		}
		else {
			// Validate the object id
			if ($key == 'object_id') {
				form_input_validate($value, 'object_id', "^[^\<\>'\"]+$", false, 'field_input_save_2');
				if (is_error_message()) {
					return 'field_input_save_2';
				}
			}

			// Cluster id does not need to be validated due to the design of the UI
			if ($j == 0) {
				$sql .= $key;
				$j++;
			} else {
				$sql .= ', ' . $key;
			}
		}
	}
	$sql .= ') VALUES (';
	$i = 0;
	foreach ($_POST AS $key => $value) {
		if ($key == 'action' || $key == 'edit' || $key =='__csrf_magic' || $key =='type') {
			// do nothing
		} else {
			$insert_value = $value;
			// Is this value the components of a queue list?
			if (get_request_var('type') == 'queue-group' && $key == 'meta_col2' && is_array($value)) {
				$insert_value = '';
				foreach ($value AS $queue) {
					$insert_value .= $queue . ' ';
				}
			}
			if ($i != 0) {
				$sql .= ', ';
			}
			$sql .= db_qstr($insert_value);
			$i++;
		}
	}
	$sql .= ') ON DUPLICATE KEY UPDATE ';

	$k = 0;
	foreach ($_POST AS $key => $value) {
		if ($key == 'action' || $key == 'edit' || $key =='__csrf_magic' || $key =='type') {
			// do nothing
		}
		else {
			if ($k == 0)
			{
				$sql .= $key . ' = VALUES(' . $key . ')';
				$k++;
			}
			else {
				$sql .= ', ' . $key . ' = VALUES(' . $key . ')';
			}
		}
	}
	$result = db_execute($sql);

	if (!$result) {
		return 50118;
	}

	return 0;
}

/**
 * Deletes metadata object rows based on information in $_POST
 */
function delete_metadata_rows() {
	global $config;

	include_once($config["library_path"] . '/rtm_functions.php');

	// Elements in the POST come in the form chk_<object_id>###<cluster_id>
	// Cacti inserts the chk_ on our behalf, whereas the ### is constructed
	// by metadata.php list action
	$object_list = explode(',', get_nfilter_request_var('selected_items'));

	foreach ($object_list as $var => $value) {
		if (preg_match('/^chk_(.*)$/', $value, $matches)) {
			$unmangled_elem_name = rtm_base64url_decode($matches[1]);
			$elements   = explode('###', $unmangled_elem_name);
			$object_id  = $elements[0];
			$cluster_id = $elements[1];

			$result = db_execute_prepared('DELETE
				FROM grid_metadata
				WHERE object_id = ?
				AND cluster_id = ?',
				array($object_id, $cluster_id));
		}
	}
}

/**
 * Takes an object representing a CALLBACK column and retrieves the content
 * from that URL. Replaces all instances of ##col_name## from that URL.
 *
 * @$object The metadata row from grid_metadata join grid_metadata_conf
 */
function popup_callback($object) {
	$pos = 0;
	$url = $object[$object['db_column_name']];
	while ($pos < strlen($url)) {
		if (!($pos = strpos($url, '##', $pos))) {
			$pos = strlen($url);
			break;
		}
		if (!$next_pos = strpos($url, '##', $pos + 1)) {
			$pos = strlen($url);
			break;
		}
		$replacement = substr($url, $pos + 2, $next_pos - $pos - 2);

		if (in_array($replacement, array_keys($object))) {
			$replacement_value = $object[$replacement];
			$url = str_replace('##' . $replacement . '##', $replacement_value, $url);
		}
		$pos = $next_pos + 1;
	}
	$contents = file_get_contents($url);
	return $contents;
}

/**
 * Builds a SQL SELECT statement for the detail view, based on object_type
 *
 * @return The number of columns required for this metadata object type
 */
function get_object_column_count($object_type) {
	return  db_fetch_cell_prepared('SELECT count(*) AS count_rows
		FROM grid_metadata_conf
		WHERE object_type = ?',
		array($object_type));
}

/**
 * Builds a SQL INSERT statement, based on grid_meta_user_conf
 *
 * @$object_type The type of metadata object
 */
function build_meta_insert($object_type, $row_data, &$insert_sql, &$duplicate_sql) {
	// Build the INSERT statement
	$insert_sql = 'INSERT INTO grid_metadata (object_type';

	foreach ($row_data as $row_datum) {
		$insert_sql .= ', ' . $row_datum;
	}

	$insert_sql .= ') VALUES (';

	$duplicate_sql .= 'ON DUPLICATE KEY UPDATE object_type = VALUES(object_type) ';

	foreach ($row_data as $row_datum) {
		$duplicate_sql .= ', ' . $row_datum . ' = VALUES(' . $row_datum . ')';
	}

	return;
}

/**
 * Parses the meta.conf file, and inserts data into grid_meta_user_conf
 * @$filename The metadata configuratin file to be loaded
 * @cleanup   Cleanup previous metadata configuratin
 */
function parse_metadata_conf($filename, $cleanup = FALSE, $debug = FALSE) {
	global $config, $meta_valid_object_types;

	$meta_conf_file     = $config['base_path'] . '/plugins/meta/' . basename($filename);
	$meta_conf_xsd_file = $config['base_path'] . '/plugins/meta/metadata.conf.xsd';

	// Avoid the parser spewing out error messages in error conditions
	libxml_use_internal_errors(true);

	// Load XML configuration file
	if (is_file($meta_conf_file)) {
		if (!($meta_xml = simplexml_load_file($meta_conf_file))) {
			cacti_log("ERROR: Could not successfully load XML file '$meta_conf_file'", $debug);
			return 108;
		}
	} else {
		cacti_log("ERROR: Could not find $meta_conf_file", $debug);
		return 106;
	}

	// Validate the document against the XSD
	$xdoc      = new DOMDocument();
	$xmlfile   = $meta_conf_file;
	$xmlschema = $meta_conf_xsd_file;
	$xdoc->Load($xmlfile);
	if ($xdoc->schemaValidate($xmlschema)) {
		// Nothing
	} else {
		cacti_log("ERROR: Could not successfully validate XSD file '$meta_conf_xsd_file'", $debug);
		return 108;
	}

	// Build the INSERT statement for each row in the CSV file
	// For each type of metadata
	foreach (array_keys($meta_valid_object_types) as $object_type) {

		// For each section
		$sections = $meta_xml->xpath("/meta/object[@type='" . $object_type . "']/section");

		// If there is no data for this object type, continue onto the next
		// xpath method returns false on error
		if (!$sections) {
			continue;
		}

		$exist = db_fetch_cell_prepared("SELECT count(*) FROM grid_metadata_conf WHERE OBJECT_TYPE=?", array($object_type));

		// Only delete meta configuration of a object type when inputfile include it.
		if($exist !== false && $exist > 0) {
			// Delete the grid_metadata_conf for force reinitialize
			if($cleanup) {
				if (!($result = db_execute_prepared("DELETE FROM grid_metadata_conf WHERE OBJECT_TYPE=?", array($object_type)))) {
					cacti_log('ERROR: Could not delete from grid_metadata_conf', $debug);
					return 107;
				}
			} else {
				//ignore object importing if force is false
				continue;
			}
		}

		// For each section for this object type
		foreach ($sections as $section_node) {

			$section_name = $section_node->xpath('[@name]');
			$columns = $section_node->xpath('column');

			// For each column
			foreach ($columns as $column_node) {
				$i = 0;
				$sql = "INSERT INTO grid_metadata_conf(
						OBJECT_TYPE,
						DB_COLUMN_NAME,
						SECTION_NAME,
						DISPLAY_NAME,
						DESCRIPTION,
						DATA_TYPE,
						POSITION,
						SUMMARY,
						SEARCH,
						POPUP
					)
					VALUES (";
				foreach ($column_node->children() as $config_item) {
					$config_item = str_replace("'", "\'", $config_item);
					if ($i == 0) {
						$sql .= "'" . $object_type . "', ";
						$sql .= db_qstr($config_item);
					}
					else if ($i == 1) {
						$sql .= ", '" . $section_node["name"] . "'";
						$sql .= ", " . db_qstr($config_item);
					}
					else {
						$sql .= ", " . db_qstr($config_item);
					}
					$i++;
				}
				$sql .= ")";
				$result = db_execute($sql);
			}
		}
	}

	return 0;
}

/**
 * Permits escape delimiters in the CSV file
 */
function explode_escaped($delimiter, $string){
	$exploded = explode($delimiter, $string);
	$fixed = array();
	for($k = 0, $l = count($exploded); $k < $l; ++$k){
		// Fixes issue regarding an unescaped "," in the first position of the row
		if (strlen($exploded[$k]) > 0) {
			if($exploded[$k][strlen($exploded[$k]) - 1] == '\\') {
				if($k + 1 >= $l) {
					$fixed[] = trim($exploded[$k]);
					break;
				}
				$exploded[$k][strlen($exploded[$k]) - 1] = $delimiter;
				$exploded[$k] .= $exploded[$k + 1];
				array_splice($exploded, $k + 1, 1);
				--$l;
				--$k;
			} else $fixed[] = trim($exploded[$k]);
		} else {
		   	$fixed[] = $exploded[$k];
		}
	}
	return $fixed;
}

/**
 * Deletes metadata from the database
 */
function delete_metadata($object_type) {
	global $meta_valid_object_types;

	if (!(in_array($object_type, array_keys($meta_valid_object_types)))) {
		return 118;
	} else {
		$result = db_execute_prepared('DELETE FROM grid_metadata WHERE object_type = ?', array($object_type));
		return 117;
	}
}

/**
 * Loads data from a CSV file into grid_meta_users
 *
 * @return mixed False if the load failed, True if the import succeeded without warnings,
 *		  integer if at least one row failed validation
 */
function load_metadata($filename, $object_type, $debug = FALSE) {
	global $cnn_id;
	global $meta_valid_object_types, $valid_object_columns;

	// Has configuration been defined for this object type? If not, reject the load
	$result = db_fetch_cell_prepared('SELECT count(*)
		FROM grid_metadata_conf
		WHERE object_type = ?',
		array($object_type));

	if ($result == 0) {
		cacti_log('ERROR: no configuration has been loaded for this metadata object type', $debug);
		raise_message(50119);
		return 50119;
	}

	// Open the CSV file for processing
	if (!(is_file($filename))) {
		cacti_log('ERROR: metadata import file could not be opened', $debug);
		raise_message(111);
		return 111;
	} else {
		$csv_file = fopen($filename, 'r');
		if (!$csv_file) {
			cacti_log('ERROR: metadata import file could not be opened', $debug);
			raise_message(111);
			return 111;
		}
	}

	//Check if the file is BOMed UTF-8 encoding
    $contents = fread($csv_file, 3);
    if ($contents == (chr(239) . chr(187) . chr(191))) {
        fseek($csv_file, 3);
    } else {
        fseek($csv_file, 0);
    }

	// Process the first row of the CSV file, containing column names
	$row_data = fgetcsv($csv_file);

	if (!cacti_sizeof($row_data)) {
		cacti_log('ERROR: object metadata import failed', $debug);
		raise_message(102);
		return 102;
	}

	$start_sql = '';
	$end_sql = '';

	// Validate the column headers
	// Also store the position of row headers for later use
	$row_headers = array();
	$i = 0;
	foreach ($row_data AS $row_datum) {
		$row_headers[$row_datum] = $i;
		$i++;
		if (!in_array($row_datum, $valid_object_columns)) {
			cacti_log('ERROR: invalid metadata column header encountered in the import file: ' . $row_datum, $debug);
			raise_message(114);
			return 114;
		}
	}

	// Validate the number of column headers
	$required_headers = get_object_column_count($object_type);
	//if ($required_headers + 2 != count($row_data)) {
	if ($required_headers + 2 < count($row_data)) {
		cacti_log('ERROR: invalid metadata column header encountered in the import file', $debug);
		raise_message(114);
		return 114;
	}

	// Validate that the object_id header is present
	if (!in_array('object_id', $row_data)) {
		cacti_log('ERROR: object_id header must be present in the import file', $debug);
		raise_message(115);
		return 115;
	}

	// Validate that the cluster_id header is present
	if (!in_array('cluster_id', $row_data)) {
		cacti_log('ERROR: cluster_id header must be present in the import file', $debug);
		raise_message(50116);
		return 50116;
	}

	// Everything is OK, build the insert statement from the column headers
	build_meta_insert(trim($row_data[0]), $row_data, $start_sql, $end_sql);

	// Process each remaining line in the csv file, containing object metadata
	$row_number = 1;
	$warnings = 0;
	while ($row_data = fgetcsv($csv_file)) {
		$insert_sql = '';

		$row_number++;
		// Validate the row
		// Validation 1: Does the row contain any data at all?
		if (count($row_data) == 0 || trim($row_data[0]) == '' || !isset($row_data[0])) {
			// Not worthy of a UI warning
			cacti_log('WARNING: invalid metadata on row ' . $row_number, $debug);
			continue;
		}

		// Validation 2: Does the row contain the correct number of fields for this object type?
		$required_columns = get_object_column_count($object_type);
		// Each row will contain object_id + cluster_id + meta columns
		if ($required_columns + 2 < count($row_data)) {
			$warnings++;
			cacti_log('WARNING: invalid number of metadata columns on row ' . $row_number, $debug);
			continue;
		}

		// Validation 3: Is the cluster id valid?
		$valid_cluster_id = db_fetch_cell_prepared('SELECT clusterid
			FROM grid_clusters
			WHERE clusterid = ?',
			array($row_data[$row_headers['cluster_id']]));

		if (!isset($valid_cluster_id) && $row_data[$row_headers['cluster_id']] != 0) {
			$warnings++;
			cacti_log('WARNING: invalid cluster id on row ' . $row_number, $debug);
			continue;
		}

		// Validation 4: If the row is a queue group, validate it now
		if ($object_type == 'queue-group') {
			$valid_queue_group = validate_queue_group($row_data, $row_headers);
			if (!$valid_queue_group) {
				$warnings++;
				cacti_log('WARNING: invalid queue group on row ' . $row_number, $debug);
				continue;
			}
		}

		// Build the final SQL statement and execute it
		$insert_sql .= "'" . $object_type . "'";
		foreach ($row_data as $row_datum) {
			$insert_sql .= ', ' . db_qstr($row_datum);
		}
		$insert_sql .= ') ';

		$result = db_execute($start_sql . $insert_sql . $end_sql);

		if (!$result) {
			$warnings++;
			cacti_log('WARNING: metadata could not be inserted on row ' . $row_number, $debug);
		}
	}

	// Close the CSV file
	fclose($csv_file);

	// Finished validating row
	if ($warnings > 0) {
		cacti_log("WARNING: metadata import completed successfully with $warnings warnings", $debug);
		raise_message(112);
		return 112;
	} else {
		cacti_log('Metadata import completed successfully with no warnings', $debug);
	}

	return 0;
}

/**
 * Validates that the object-id does not contain special control characters
 */
function meta_validate_object_id($object_id) {
	$strip = array('~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '=', '+', '[', '{', ']',
		'}', '\\', '|', ';', ':', '"', '\'', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8211;', '&#8212;',
		',', '<', '>', '?');
	$clean = trim(str_replace($strip, '', strip_tags($object_id)));

	if ($clean != $object_id) {
		return false;
	}
	return true;
}

/**
 * Adds metadata columns to the settings tab, for the given type
 * The incoming array to modify comes in through $_REQUEST, as the Cacti Plugin API
 * cannot do pass by reference
 */
function meta_settings_tab($tab_name) {
	if (api_user_realm_auth('metadata.php') || api_user_realm_auth('Edit_Metadata')) {
		if ($tab_name == 'Queues') {
			$grid_settings = get_request_var('meta_arr');

			$grid_settings['queue_metadata'] = array(
				'friendly_name' => __('Metadata'),
				'method' => 'spacer',
			);

			$columns = db_fetch_assoc("SELECT concat('queue_', db_column_name) AS key_value, display_name
				FROM grid_metadata_conf
				WHERE object_type = 'queue'");

			if (count($columns)) {
				foreach ($columns as $column) {
					$grid_settings[$column['key_value']] = array(
						'friendly_name' => $column['display_name'],
						'method' => 'checkbox',
						'default' => ''
					);
				}
			}

			$grid_settings['queue_group'] = array(
				'friendly_name' => __('Queue Group'),
				'method' => 'checkbox',
				'default' => ''
			);

			set_request_var('meta_arr', $grid_settings);
		}
	}
}

/**
 * Adds a metadata column to a tabular page display
 */
function meta_column_header($page_name) {
	$meta_display_text = array();

	if ($page_name == "grid_bqueues") {

		$columns = db_fetch_assoc("SELECT concat('queue_', db_column_name) AS key_value, db_column_name,
			display_name FROM grid_metadata_conf WHERE object_type = 'queue'");

		// Start $i high because there are already nosort# columns in the grid_bqueues page
		$i = 10;
		if (count($columns)) {
			foreach ($columns as $column) {
				if (read_grid_config_option($column["key_value"])) {
					$meta_display_text += array("nosort" . (string)$i++ => array(str_replace(" ", "<br>", $column["display_name"]), "ASC"));
				}
			}
		}

		if (read_grid_config_option("queue_group")) {
			$meta_display_text += array("nosort" . (string)$i++ => array("Queue<br>Group(s)<br>", "ASC"));
		}
	}

	return $meta_display_text;
}

/**
 * Draws the column content for a tabular page display
 */
function meta_column_content($row_data) {
	global $disabled;

	// For each queue, determine if any metadata fields are defined
	// and enabled
	if ($row_data["page_name"] == "grid_bqueues") {
		$columns = db_fetch_assoc("SELECT concat('queue_', db_column_name) AS key_value, db_column_name,
			display_name FROM grid_metadata_conf WHERE object_type = 'queue'");

		if (count($columns)) {
			foreach ($columns as $column) {
				if (read_grid_config_option($column["key_value"])) {
					$value = db_fetch_cell_prepared("SELECT " . $column["db_column_name"] . "
						FROM grid_metadata WHERE object_id = ?", array($row_data["row_data"]["queuename"]));

					if (isset($value)) {
						form_selectable_cell($value, $row_data["row_data"]["queuename"] . ':' . $row_data["row_data"]["clusterid"], '100', 'white-space:nowrap', $disabled);
					} else {
						form_selectable_cell("-", $row_data["row_data"]["queuename"] . ':' . $row_data["row_data"]["clusterid"], '20', 'white-space:nowrap', $disabled);
					}
				}
			}
		}

		// Queue group
		if (read_grid_config_option("queue_group")) {
			global $meta_qgroup_display_name_col, $meta_qgroup_cluster_id_col, $meta_qgroup_queue_list_col;

			$cluster_id = db_fetch_cell_prepared("SELECT clusterid FROM grid_clusters WHERE clustername = ?", array($row_data["row_data"]["clustername"]));

			$queue_groups = db_fetch_assoc_prepared("SELECT " . $meta_qgroup_display_name_col . " AS display_name FROM grid_metadata WHERE " .
				$meta_qgroup_queue_list_col . " like ? AND cluster_id = ?",
				array("%" . $row_data["row_data"]["queuename"] . "%", $cluster_id));

			if (count($queue_groups)) {
				$i = 0;
				$queue_group_text = "";
				foreach ($queue_groups AS $queue_group) {
					if ($i == 0) {
						$queue_group_text .= $queue_group["display_name"];
					} else {
						$queue_group_text .= ",<br> " . $queue_group["display_name"];
					}
					$i++;
				}
				form_selectable_cell($queue_group_text, $row_data["row_data"]["queuename"] . ':' . $row_data["row_data"]["clusterid"], '100', 'white-space:nowrap', $disabled);
			} else {
				form_selectable_cell("-", $row_data["row_data"]["queuename"] . ':' . $row_data["row_data"]["clusterid"], '20', 'white-space:nowrap', $disabled);
			}

		}
	}
}

/**
 * Draw the custom metadata parameters
 */
function meta_param($page_name) {
	if ($page_name == "grid_bqueues") {

		global $meta_qgroup_display_name_col,
			$meta_qgroup_cluster_id_col, $meta_qgroup_queue_list_col;

		input_validate_input_number(get_request_var('clusterid'));

		// If queue_group is not set on $_REQUEST, then we will show all queue groups
		// for the currently selected cluster (which may also be All), and select
		// All as the currently selected value of Queue Group

		if (isempty_request_var('queue_group')) {
			set_request_var('queue_group', get_request_var('clusterid') . "___-1");
		}

		// The next block determines if the currently selected queue group (incoming on
		// the $_REQUEST, is still valid for the selected cluster (also on the $_REQUEST).
		// If not, we'll reset it to -1 (All)
		$row_count = 0;
		$id_explode = explode("___", get_request_var('queue_group'));
		$cluster_id = $id_explode[0];
		$queue_group = $id_explode[1];
		if (get_request_var('clusterid') == 0) {
			$row_count = read_metadata_count('queue-group', $queue_group);
		} else {
			$row_count = read_metadata_count('queue-group', $queue_group, $cluster_id);
		}

		// The currently selected queue group was not valid for the new combination of parameters
		if ($row_count == 0) {
			set_request_var('queue_group', "-1");
		}

		// Draw custom parameter filters
		print "<td width='100'>";
		print "&nbsp;Queue Group&nbsp;";
		print "</td>";

		print "<td colspan='3' width='1'>";
		print "<select id='queue_group'>";
		print "<option value='" . get_request_var('clusterid') . "___-1' ";
		if (get_request_var('queue_group') == "-1") {
			print "selected ";
		};
		print ">All</option>";

			if (get_request_var('clusterid') <= 0) {
				$queue_groups = db_fetch_assoc("SELECT DISTINCT
					object_id, cluster_id, " . $meta_qgroup_display_name_col . " AS display_name
					FROM grid_metadata
					WHERE object_type = 'queue-group'
					ORDER BY " . $meta_qgroup_display_name_col);
			} else {
				$queue_groups = db_fetch_assoc("SELECT
					object_id, cluster_id, " . $meta_qgroup_display_name_col . " AS display_name
					FROM grid_metadata
					WHERE cluster_id = " . get_request_var('clusterid') . "
					AND object_type = 'queue-group'
					ORDER BY " . $meta_qgroup_display_name_col);
			}

			if (cacti_sizeof($queue_groups) > 0) {
				foreach ($queue_groups as $queue_group) {
					print '<option value="' . $queue_group["cluster_id"] . "___" . urlencode($queue_group["object_id"]) .'"'; if (urlencode(get_request_var('queue_group'))
						== $queue_group["cluster_id"] . "___" . urlencode($queue_group["object_id"])) { print " selected"; } print ">" . $queue_group["display_name"] . "</option>";
				}
			}

		print "</select>";
		print "</td>";
	}
}

/**
 * Construct the SQL WHERE clause component for custom metadata parameters
 */
function meta_param_where($page_name) {
	if ($page_name == "grid_bqueues") {
		global $meta_qgroup_display_name_col, $meta_qgroup_cluster_id_col, $meta_qgroup_queue_list_col;


		if (!isset_request_var('queue_group') || get_request_var('queue_group') == "-1") {
			set_request_var('queue_group', get_request_var('clusterid') . "___-1");
		}

		$id_explode = explode("___", get_request_var('queue_group'));
		$cluster_id = $id_explode[0];
		$queue_group = $id_explode[1];

		// If a specific cluster-id/queue-group is selected, filter by it
		if ($queue_group != "-1") {
			$queue_list = db_fetch_row_prepared("SELECT " . $meta_qgroup_queue_list_col . " AS queue_list, b.clusterid AS cluster_id
				FROM grid_metadata a INNER JOIN grid_clusters b ON (a.cluster_id = b.clusterid)
				WHERE object_type = 'queue-group'
				AND object_id = ?
				AND cluster_id = ?",
				array($queue_group, $cluster_id));

			if (isset($queue_list)) {
				$where_clause = "'" . str_replace(" ", "','", $queue_list["queue_list"]) . "'";
				return "queuename IN (" . $where_clause . ") AND gq.clusterid = " . $queue_list["cluster_id"];
			} else {
				// Nothing;
				return;
			}
		}
		else {
			// Nothing
			return;
		}
	}
}

/**
 * Add custom metadata parameters to the JS parameter refresh script
 */
function meta_column_filter($page_name) {
	if ($page_name == "grid_bqueues") {
		return "'&queue_group=' + $('#queue_group').val()";
	}
}

/**
 * Validate queue group row
 * Returns false for invalid, true for valid
 */
function validate_queue_group($queue_group_row_data, $row_headers) {
	global $meta_qgroup_cluster_id_col, $meta_qgroup_queue_list_col;

	// Validate the cluster name
	$cluster_id = $queue_group_row_data[$row_headers["cluster_id"]];

	if (!isset($cluster_id)) {
		// The cluster name is invalid
		return false;
	}

	// Validate the queues
	$queues = explode(" ", $queue_group_row_data[$row_headers[$meta_qgroup_queue_list_col]]);

	foreach ($queues as $queue) {
		$queue_name = db_fetch_cell_prepared("SELECT queuename FROM grid_queues WHERE clusterid = ?  AND queuename = ?", array($cluster_id, $queue));

		if (!isset($queue_name)) {
			// The queue name is invalid for this cluster
			return false;
		}
	}

	// All validation tests passed
	return true;
}

function read_metadata($type = 'user', $object_id, $column) {
	static $cachedata = array();

	if (isset($cachedata[$type][$object_id][$column]['data'])) {
		return $cachedata[$type][$object_id][$column]['data'];

	}else{
		$data = db_fetch_cell_prepared("SELECT " . $column . " FROM grid_metadata WHERE object_type = ? AND object_id = ?", array($type, $object_id));
		if (isset($data) && strlen($data)>0) {
			$cachedata[$type][$object_id]['data'] = $data;
		}
		return $data;
	}
}

function read_metadata_count($type = 'user', $object_id, $clusterid=0) {
	static $cachedata = array();
	static $cachedata_cluster = array();
	if($clusterid > 0){
		if (isset($cachedata[$type][$object_id][$clusterid])) {
			return $cachedata[$type][$object_id][$clusterid];
		}else{
			$data = db_fetch_cell_prepared("SELECT count(*) FROM grid_metadata WHERE object_type = ? AND object_id = ? AND cluster_id = ?", array($type, $object_id, $clusterid));
			if (isset($data) && strlen($data)>0) {
				$cachedata[$type][$object_id][$clusterid] = $data;
			}
			return $data;
		}
	}
	if (isset($cachedata[$type][$object_id]['all_cluster'])) {
		return $cachedata[$type][$object_id]['all_cluster'];
	}else{
		$data = db_fetch_cell_prepared("SELECT count(*) FROM grid_metadata WHERE object_type = ? AND object_id = ?", array($type, $object_id));

		if (isset($data) && strlen($data)>0) {
			$cachedata[$type][$object_id]['all_cluster'] = $data;
		}

		return $data;
	}
}

function get_default_type(){
	if (isset($_SESSION['sess_grid_meta_type'])) {
		return $_SESSION['sess_grid_meta_type'];
	}
	return db_fetch_cell("SELECT DISTINCT object_type FROM grid_metadata_conf ORDER BY object_type");
}
?>
