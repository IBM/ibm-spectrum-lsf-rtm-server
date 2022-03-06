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

function upgrade_get_table_engine($table) {
	$status = db_fetch_row("SHOW TABLE STATUS LIKE '$table'");

	if (isset($status["Engine"])) {
		return $status["Engine"];
	} else {
		return "Unknown";
	}
}

function execute_sql($message, $syntax) {
	if(!isset($syntax) || empty($syntax))
		$result = db_execute($message);
	else
		$result = db_execute($syntax);

	if ($result) {
		cacti_log("SUCCESS: Execute SQL, $message, Succeeded.", true, 'UPGRADE');
		return DB_STATUS_SUCCESS;
	} else {
		cacti_log("ERROR: Execute SQL, $message, Failed.", true, 'UPGRADE');
		return DB_STATUS_ERROR;
	}
}


function create_table($table, $syntax) {
	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");

	if (!cacti_sizeof($tables)) {
		$result = db_execute($syntax);
		if ($result) {
			cacti_log("SUCCESS: Create Table, Table -> $table, Succeeded.", true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Create Table, Table -> $table, Failed.", true, 'UPGRADE');
		}
	} else {
		cacti_log("NOTE: Create Table, Table -> $table, Already Exists.", true, 'UPGRADE');
	}
}

function add_column($table, $column, $syntax) {
	return add_columns_indexes($table, array($column => $syntax));
}

/**
 * Add multiple-columns to table
 *
 * @param string$table name
 * @param array$columns(column_name => syntax)
 * @return void
 */
function add_columns($table, $columns) {
	return add_columns_indexes($table,  $columns);
}

/* Add columns and indexes in one SQL, For better performnce and checked if exist for each column and index */
function add_columns_indexes($table, $column_arr=NULL, $index_arr=NULL) {
	$status = DB_STATUS_SUCCESS;

	$add_sql="ALTER TABLE `$table` ";
	$will_add_columns="";
	$will_add_indexes="";
	$first_flag=true;

	if (cacti_sizeof($column_arr)) {
		// get the db_columns
		$db_columns = array();
		$result = db_fetch_assoc("SHOW COLUMNS FROM $table");
		if(cacti_sizeof($result)) {
			foreach($result as $index => $arr) {
				$db_columns[] = $arr["Field"];
			}
		} else {
			cacti_log("ERROR: Add Column, Table -> $table, Table does not exist.", true, 'UPGRADE');
			return DB_STATUS_ERROR;
		}

		foreach($column_arr as $column => $syntax) {
			if (in_array($column, $db_columns)) {
				cacti_log("INFO: Add Column, Table -> $table, Column -> $column, Column already exists.", true, 'UPGRADE');
			} else {
				if($first_flag==true) {
					$first_flag=false;
					$add_sql .= $syntax;
					$will_add_columns .= $column;
				} else {
					$add_sql .= "," .$syntax;
					$will_add_columns .= "," .$column;
				}
			}
		}
	}

	if (cacti_sizeof($index_arr)) {
		// get the db_indexes
		$db_indexes = array();
		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
		if(cacti_sizeof($indexes)) {
			foreach($indexes as $index => $arr) {
				$db_indexes[] = $arr["Key_name"];
			}
		}
		foreach($index_arr as $index => $syntax) {
			if (in_array($index, $db_indexes)) {
				cacti_log("INFO: Add Index, Table -> $table, Index -> $index, Column already exists.", true, 'UPGRADE');
			} else {
				if($first_flag==true) {
					$first_flag=false;
					$add_sql .= $syntax;
					$will_add_indexes .= $index;
				} else {
					$add_sql .= "," .$syntax;
					$will_add_indexes .= "," .$index;
				}
			}
		}
	}

	if ($first_flag == true) {
		cacti_log("INFO: Table -> $table, Unchanged.", true, 'UPGRADE');
		return DB_STATUS_SUCCESS;
	}
	$result = db_execute($add_sql);
	if ($result) {
		if(!empty($will_add_columns)) {
			cacti_log("SUCCESS: Add Column, Table -> $table, Columns -> $will_add_columns, Succeeded.", true, 'UPGRADE');
		}
		if(!empty($will_add_indexes)) {
			cacti_log("SUCCESS: Add Index, Table -> $table, Indexes -> $will_add_indexes, Succeeded.", true, 'UPGRADE');
		}
		$status =  DB_STATUS_SUCCESS;
	} else {
		if(!empty($will_add_columns)) {
			cacti_log("ERROR: Add Column, Table -> $table, Columns -> $will_add_columns, Failed.", true, 'UPGRADE');
		}
		if(!empty($will_add_indexes)) {
			cacti_log("ERROR: Add Index, Table -> $table, Indexes -> $will_add_indexes, Failed.", true, 'UPGRADE');
		}
		$status =  DB_STATUS_ERROR;
	}
	return $status;
}

function add_index($table, $index, $syntax) {
	return add_columns_indexes($table,  NULL, array($index => $syntax));
}

function modify_column($table, $column, $syntax) {
	return modify_columns($table, array($column => $syntax));
}

function modify_columns($table, $column_arr) {
	$status = DB_STATUS_SUCCESS;

	$modify_sql="ALTER TABLE `$table` ";
	$will_modify_columns="";
	$first_flag=true;

	if (cacti_sizeof($column_arr)) {
		// get the db_columns
		$db_columns = array();
		$result = db_fetch_assoc("SHOW COLUMNS FROM $table");
		if(cacti_sizeof($result)) {
			foreach($result as $index => $arr) {
				$db_columns[] = $arr["Field"];
			}
		} else {
			cacti_log("ERROR: Add Column, Table -> $table, Table does not exist.", true, 'UPGRADE');
			return DB_STATUS_ERROR;
		}

		foreach($column_arr as $column => $syntax) {
			if (in_array($column, $db_columns)) {
				if($first_flag==true) {
					$first_flag=false;
					$modify_sql .= $syntax;
					$will_modify_columns .= $column;
				} else {
					$modify_sql .= "," .$syntax;
					$will_modify_columns .= "," .$column;
				}
			} else {
				cacti_log("ERROR: Modify Column, Table -> $table, Column -> $column, Column does not exist.", true, 'UPGRADE');
			}
		}
	}

	if ($first_flag == true) {
		cacti_log("INFO: Table -> $table, Unchanged.", true, 'UPGRADE');
		return DB_STATUS_SUCCESS;
	}
	$result = db_execute($modify_sql);
	if ($result) {
		if(!empty($will_modify_columns)) {
			cacti_log("SUCCESS: Add Column, Table -> $table, Columns -> $will_modify_columns, Succeeded.", true, 'UPGRADE');
		}
		$status =  DB_STATUS_SUCCESS;
	} else {
		if(!empty($will_modify_columns)) {
			cacti_log("ERROR: Add Column, Table -> $table, Columns -> $will_modify_columns, Failed.", true, 'UPGRADE');
		}
		$status =  DB_STATUS_ERROR;
	}
	return $status;
}

/**
 *  RTC#99080, Resolve PHP-->version_compare issue:
 * 		10.2.0.0 > 10.2
 * 		10.2.0.0 > 10.2.0
 *  function rtm_version_compare($version1, $version2)
 *  input value
 *        - version1: the grid_version want to check.
 *        - version2: the grid_version compare to.
 *  return value
 *        - -1: if $version1 < $version2
 *        - 0: if $version1 = $version2
 *        - 1: if $version1 > $version2
 *  For example, all the calls as below return true.
 *  rtm_version_compare("8.0", "8.0.1");
 *  rtm_version_compare("9.1.3.0", "9.1.4.0");
 *  rtm_version_compare("9.1.3.0", "10.1.0.0");
 */
function rtm_version_compare($version1,$version2) {
	$grid_pieces   = explode('.', $version1);
	$the_pieces    = explode('.', $version2);
	$grid_p_length = cacti_sizeof($grid_pieces);
	$the_p_length  = cacti_sizeof($the_pieces);

	if ($grid_p_length > $the_p_length) {
		for($i=0; $i<$the_p_length; $i++) {//loop min
			if ($grid_pieces[$i] > $the_pieces[$i]) {
				return 1;
			} elseif ($grid_pieces[$i] < $the_pieces[$i]) {
				return -1;
			}
		}

		return 1; //all =, but $grid_p_length is longer.
	} else {
		for($i=0; $i<$grid_p_length; $i++) {//loop min
			if ($grid_pieces[$i] > $the_pieces[$i]) {
				return 1;
			} elseif ($grid_pieces[$i] < $the_pieces[$i]) {
				return -1;
			}
		}

		if ($grid_p_length == $the_p_length) {
			return 0; //all =, and length is the same.
		} else {
			return -1; //all =, but $the_p_length is longer.
		}
	}
}

function rtm_plugin_upgrade_fp($before_upgrade, $after_upgrade, $plugin_name = 'grid'){
	return rtm_plugin_upgrade($before_upgrade, $after_upgrade, $plugin_name, RTM_VERSION_LATEST_GA);
}

function rtm_plugin_upgrade_ga($before_upgrade, $after_upgrade, $plugin_name = 'grid'){
	return rtm_plugin_upgrade($before_upgrade, $after_upgrade, $plugin_name, RTM_VERSION_SUPPORTED_MIN);
}

function rtm_plugin_upgrade($before_upgrade, $after_upgrade, $plugin_name = 'grid', $supported_min = RTM_VERSION_LATEST_GA){
	global $rtm_version_numbers, $config;

	$status = DB_STATUS_SUCCESS;

	if (rtm_version_compare($before_upgrade, $supported_min) < 0) {
		print "ERROR: This upgrade script only support version $supported_min and above, you have version $before_upgrade\n";
		return DB_STATUS_ERROR;
	}

	$prev_version = $before_upgrade;
	$plugin_dir = $config['base_path'] . '/plugins/' . $plugin_name;

	cacti_log("NOTE: Upgrading $plugin_name plugin database ...", true, 'UPGRADE');

	foreach($rtm_version_numbers as $curr_version){
		if(rtm_version_compare($curr_version, $supported_min) <= 0 ){
			continue;
		}
		if(rtm_version_compare($before_upgrade, $curr_version) >= 0 ){
			continue;
		}

		// construct version upgrade include path
		$upgrade_file = $plugin_dir . '/upgrades/' . str_replace('.', '_', $curr_version) . '.php';
		$upgrade_function = 'upgrade_to_' . str_replace('.', '_', $curr_version);

		// check for upgrade version file, then include, check for function and execute
		if (file_exists($upgrade_file)) {
			cacti_log("NOTE: Upgrading $plugin_name from v$prev_version to v$curr_version", true, 'UPGRADE');

			include($upgrade_file);
			if (function_exists($upgrade_function)) {
				call_user_func($upgrade_function);
				$status = DB_STATUS_SUCCESS;
			} else {
				$status = DB_STATUS_ERROR;
				cacti_log('ERROR: upgrade function ' . $upgrade_function . ' not found in \'' . $upgrade_file . '\'', true, 'UPGRADE');
			}

			if ($status == DB_STATUS_ERROR) {
				break;
			}
		}

		db_execute("REPLACE INTO settings set name='" . $plugin_name . "_version', value='$curr_version'");
		db_execute("UPDATE plugin_config SET version='$curr_version' WHERE directory ='$plugin_name'");
		$prev_version = $curr_version;

		if ($after_upgrade == $curr_version) {
			break;
		}
	}
	if ($status != DB_STATUS_ERROR) {
		cacti_log("NOTE: Plugin '$plugin_name' Database Upgrade Complete.", true, 'UPGRADE');
	}
	return $status;
}

/**
 * Validate version string for database upgrade utility.
 * @param string $verno
 * @param string $supported_min
 * @return int -1: Not supported for current release;
 *              0: valid version number;
 *              1: invalid version number. e.g. 7.8, 8.9
 */
function rtm_plugin_ver_validate($verno, $supported_min = RTM_VERSION_SUPPORTED_MIN){
	global $rtm_version_numbers;

	if(empty($verno) || !in_array($verno, $rtm_version_numbers)){
		return 1;
	}
	if(rtm_version_compare($verno, $supported_min) < 0 ){
		return -1;
	}
	return 0;
}

function rtm_plugin_upgrade_partition_check($before_upgrade_ver, $after_upgrade_ver, $plugin_name = 'grid', $supported_min = RTM_VERSION_LATEST_GA){
	global $rtm_version_numbers, $config;

	$prev_version = $before_upgrade_ver;
	$plugin_dir = $config['base_path'] . '/plugins/' . $plugin_name;

	foreach($rtm_version_numbers as $curr_version){
		if(rtm_version_compare($curr_version, $supported_min) <= 0 ){
			continue;
		}
		if(rtm_version_compare($before_upgrade_ver, $curr_version) >= 0 ){
			continue;
		}

		// construct version upgrade include path
		$upgrade_file = $plugin_dir . '/upgrades/' . str_replace('.', '_', $curr_version) . '.php';
		$upgrade_function = 'partition_tables_to_' . str_replace('.', '_', $curr_version);

		// check for upgrade version file, then include, check for function and execute
		if (file_exists($upgrade_file)) {
			include_once($upgrade_file);
			if (function_exists($upgrade_function)) {
				$tmp_part_tables = call_user_func($upgrade_function);
				if(cacti_sizeof($tmp_part_tables)){
					return true;
				}
			}
		}
	}
	return false;
}

function rtm_plugin_upgrade_partition($before_upgrade, $after_upgrade, $plugin_name = 'grid', $supported_min = RTM_VERSION_LATEST_GA){
	global $rtm_version_numbers, $config;

	$status = DB_STATUS_SUCCESS;

	if (rtm_version_compare($before_upgrade, $supported_min) < 0) {
		print "ERROR: This background upgrade script only support version $supported_min and above, you have version $before_upgrade\n";
		return DB_STATUS_ERROR;
	}

	$prev_version = $before_upgrade;
	$plugin_dir = $config['base_path'] . '/plugins/' . $plugin_name;

	cacti_log("NOTE: Upgrading $plugin_name plugin database partition tables ...", true, 'UPGRADE');;

	$part_tables = array();

	foreach($rtm_version_numbers as $curr_version){
		if(rtm_version_compare($curr_version, $supported_min) <= 0 ){
			continue;
		}
		if(rtm_version_compare($before_upgrade, $curr_version) >= 0 ){
			continue;
		}

		// construct version upgrade include path
		$upgrade_file = $plugin_dir . '/upgrades/' . str_replace('.', '_', $curr_version) . '.php';
		$upgrade_function = 'partition_tables_to_' . str_replace('.', '_', $curr_version);

		// check for upgrade version file, then include, check for function and execute
		if (file_exists($upgrade_file)) {
			include_once($upgrade_file);
			if (function_exists($upgrade_function)) {
				cacti_log("NOTE: Get '$plugin_name' partition table upgrade info from v$prev_version to v$curr_version", true, 'UPGRADE');
				$tmp_part_tables = call_user_func($upgrade_function);
				if(cacti_sizeof($tmp_part_tables)){
					$part_tables = array_merge_recursive($part_tables, $tmp_part_tables);
				} else {
					cacti_log('WARNING: Upgrade function ' . $upgrade_function . ' did not return partition table info in \'' . $upgrade_file . '\'', true, 'UPGRADE');
				}
			}
			$prev_version = $curr_version;
		}
		if ($after_upgrade == $curr_version) {
			break;
		}
	}
	$status = rtm_update_tables($part_tables, $plugin_name);

	if ($status != DB_STATUS_ERROR) {
		cacti_log("NOTE: Plugin '$plugin_name' Database partition tables Upgrade Complete.", true, 'UPGRADE');
	}
	return $status;
}

/**
 * Concat two SQL string, and optional prefix.
 *     e.g. "ADD COLUMN " . <column#1 definition> . ", " . "MODIFY COLUMN ". <column#2 definition>
 * @param string $sql1
 * @param string $sql2
 * @param string $sql1_prefix
 * @param string $sql2_prefix
 * @return void
 */
function rtm_sql_concat($sql1, $sql2, $sql1_prefix = "", $sql2_prefix = ""){
	$sql = '';
	if(strlen(trim($sql2))){
		$l_sql2 = $sql2;
		if(strlen(trim($sql2_prefix))){
			$l_sql2 = "$sql2_prefix $sql2";
		}
		if(strlen(trim($sql1))){
			$l_sql1 = $sql1;
			if(strlen(trim($sql1_prefix))){
				$l_sql1 = "$sql1_prefix $sql1";
			}
			$sql = $l_sql1 . ", ";
		}
		$sql .= $l_sql2;
	}
	return $sql;
}

function rtm_sql_update_column_type($column){
	$sql = '';

	if (isset($column['type'])) {
		$sql .= ' ' . $column['type'];
	}

	if (isset($column['unsigned'])) {
		$sql .= ' unsigned';
	}

	return $sql;
}

function rtm_sql_update_column_null($column){
	if (isset($column['NULL']) && $column['NULL'] == false) {
		return 'NO';
	}
	return 'YES';
}

function rtm_sql_update_column($column){
	$sql = rtm_sql_update_column_type($column);

	if (isset($column['NULL']) && $column['NULL'] == false) {
		$sql .= ' NOT NULL';
	}

	if (isset($column['NULL']) && $column['NULL'] == true && !isset($column['default'])) {
		$sql .= ' default NULL';
	}

	if (isset($column['default'])) {
		if (strtolower($column['type']) == 'timestamp' && $column['default'] === 'CURRENT_TIMESTAMP') {
			$sql .= ' default CURRENT_TIMESTAMP';
		} else {
			$sql .= ' default ' . (is_numeric($column['default']) ? $column['default'] : "'" . $column['default'] . "'");
		}
	}

	if (isset($column['auto_increment'])) {
		$sql .= ' auto_increment';
	}

	return $sql;
}

function rtm_sql_add_column($column){
	$sql = '';

	$sql .= rtm_sql_update_column($column);

	if (isset($column['after'])) {
		$sql .= ' AFTER ' . $column['after'];
	}
	return $sql;
}

function rtm_sql_update_table_columns($table_name, $column_arr) {
	if(!cacti_sizeof($column_arr) || empty($table_name)){
		return;
	}

	$tbl_columns = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table_name"), 'Field', array('Type', 'Null', 'Default'));
    if (!cacti_sizeof($tbl_columns)) {
		return;
	}

	$sql = '';

	foreach($column_arr as $colname => $coldef){
		$issql = (!is_array($coldef));
		if(array_key_exists($colname, $tbl_columns)){
			if($issql){
				$tmp_sql = $coldef;
				if (stripos($tmp_sql, "ADD COLUMN") !== false){
					$tmp_sql = str_replace("ADD COLUMN", "MODIFY COLUMN", $tmp_sql);
				}
				$sql = rtm_sql_concat($sql, $tmp_sql);
			} else if($tbl_columns[$colname]['Type'] != trim(rtm_sql_update_column_type($coldef))
						|| $tbl_columns[$colname]['Null'] != rtm_sql_update_column_null($coldef)
						|| $tbl_columns[$colname]['Default'] != $coldef['default']){
				$tmp_sql = rtm_sql_update_column($coldef);
				$sql = rtm_sql_concat($sql, $tmp_sql, '', "MODIFY COLUMN `$colname`");
			}
		}else {
			if($issql){
				$tmp_sql = $coldef;
				if (stripos($tmp_sql, "MODIFY COLUMN") !== false){
					$tmp_sql = str_replace("MODIFY COLUMN", "ADD COLUMN", $tmp_sql);
				}
				$sql = rtm_sql_concat($sql, $tmp_sql);
			} else {
				$tmp_sql = rtm_sql_add_column($coldef);
				$sql = rtm_sql_concat($sql, $tmp_sql, '', "ADD COLUMN `$colname`");
			}
		}
	}

	return $sql;
}

function rtm_sql_drop_columns($table_name, $column_arr) {
	$sql = '';

	if(!cacti_sizeof($column_arr) || empty($table_name)){
		return $sql;
	}

	$tbl_columns = array_rekey(db_fetch_assoc("SHOW COLUMNS FROM $table_name"), 'Field', 'Field');
    if (!cacti_sizeof($tbl_columns)) {
		return $sql;
	}

	$drop_col_prefix = "DROP COLUMN";
	foreach($column_arr as $colkey => $colvalue){
		$issql = (stripos($colvalue, $drop_col_prefix) !== false);
		$colname = $colvalue;
		if($issql){
			$colname = $colkey;
		}
		if(in_array($colname, $tbl_columns)){
			if($issql){
				$sql = rtm_sql_concat($sql, $colvalue);
			}else{
				$sql = rtm_sql_concat($sql, $colvalue, "", $drop_col_prefix);
			}
		}
	}

	return $sql;
}

function rtm_sql_drop_indexes($table_name, $index_arr) {
	$sql = '';

	if(!cacti_sizeof($index_arr) || empty($table_name)){
		return $sql;
	}

	$tbl_indexes = array_rekey(db_fetch_assoc("SHOW INDEXES FROM $table_name"), "Key_name", "Key_name");
    if (!cacti_sizeof($tbl_indexes)) {
		return $sql;
	}

	foreach($index_arr as $idxkey => $idxvalue){
		$idxname = $idxvalue;

		$issql = preg_match("/^DROP (INDEX|KEY)/i", $idxvalue);
		if($issql){
			$idxname = $idxkey;
		}
		if(in_array($idxname, $tbl_indexes)){
			if($issql){
				$sql = rtm_sql_concat($sql, $idxvalue);
			}else{
				$sql = rtm_sql_concat($sql, $idxvalue, "", "DROP INDEX");
			}
		}
	}

	return $sql;
}

/**
 * Append indexes to table if not exist
 *
 * @param string $table_name
 * @param array $index_arr array('index__name' => 'ADD INDEX ...', ...)
 * @return void
 */
function rtm_sql_add_indexes($table_name, $index_arr) {
	$sql = '';

	if(!cacti_sizeof($index_arr) || empty($table_name)){
		return $sql;
	}

	$tbl_indexes = array_rekey(db_fetch_assoc("SHOW INDEXES FROM $table_name"), "Key_name", "Key_name");
    if (!cacti_sizeof($tbl_indexes)) {
		return $sql;
	}

	foreach($index_arr as $idxkey => $idxvalue){
		$issql = preg_match("/^ADD (PRIMARY |UNIQUE |)(INDEX|KEY)/i", $idxvalue);

		if($idxkey == 'primary'){
			$add_idx_prefix = "ADD PRIMARY KEY";
			if(strlen(trim($idxvalue))){
				$sql = rtm_sql_concat($sql, "DROP PRIMARY KEY");
			}
		} else {
			$add_idx_prefix = "ADD INDEX";
		}
		if(!in_array($idxkey, $tbl_indexes)){
			if($issql){
				$sql = rtm_sql_concat($sql, $idxvalue);
			}else{
				$sql = rtm_sql_concat($sql, $idxvalue, "", $add_idx_prefix);
			}
		}
	}

	return $sql;
}

/**
 * Note: Do not support:
 *      - RENAME {INDEX|KEY} old_index_name TO new_index_name
 *      - ADD [w/o COLUMN] (col_name column_definition,...)
 * @param array $tables array(
 *                         'table_name' => array(
 * 	                           'columns' => mixed,
 *                             'indexes' => mixed,
 *                             'drop' => array(
 *                                 'columns' => array(columnname, ...),
 *                                 'indexes' => array(indexname, ...)
 *                             )
 *                         ),
 *                         ...
 *                     )
 * @return int DB_STATUS
 */
function rtm_update_tables($tables, $plugin_name = 'grid') {
	if(!cacti_sizeof($tables)){
		return DB_STATUS_SUCCESS;
	}

	foreach ($tables as $tblname => $tbldef){
		$part_tables = db_fetch_assoc_prepared("SELECT `partition`
				FROM grid_table_partitions
				WHERE table_name = ?
				ORDER BY `partition`", array($tblname));

		if (cacti_sizeof($part_tables)) {
			foreach ($part_tables as $part_table) {
				$part_tblname = $tblname . '_v' . $part_table['partition'];
				$alter_sql = '';

				if(isset($tbldef['drop'])){
					$drop_sql = '';
					if(isset($tbldef['drop']['columns'])){
						$drop_columns = $tbldef['drop']['columns'];
						/**
						 * drop->columns format must be one of below:
						 *  - array(<col_name>,...),
						 *  - array(<col_name> => <drop_sql>, ...)
						 *  - column_name
						 */
						if(!is_array($drop_columns)){
							$drop_columns = array($drop_columns);
						}
						$drop_sql_col = rtm_sql_drop_columns($part_tblname, $drop_columns);
						$drop_sql = rtm_sql_concat($drop_sql, $drop_sql_col);
					}
					if(isset($tbldef['drop']['indexes'])){
						$drop_indexes = $tbldef['drop']['indexes'];
						if(!is_array($drop_indexes)){
							$drop_indexes = array($drop_indexes);
						}
						$drop_sql_idx = rtm_sql_drop_indexes($part_tblname, $drop_indexes);
						$drop_sql = rtm_sql_concat($drop_sql, $drop_sql_idx);
					}
					$alter_sql = rtm_sql_concat($alter_sql, $drop_sql);
				}
				if(isset($tbldef['columns'])){
					$mod_columns = $tbldef['columns'];
					if(!is_array($mod_columns)){
						$mod_columns = array($mod_columns);
					}
					$mod_sql_col = rtm_sql_update_table_columns($part_tblname, $mod_columns);
					$alter_sql = rtm_sql_concat($alter_sql, $mod_sql_col);
				}
				if(isset($tbldef['indexes'])){
					$mod_indexes = $tbldef['indexes'];
					if(!is_array($mod_indexes)){
						$mod_indexes = array($mod_indexes);
					}
					$mod_sql_idx = rtm_sql_add_indexes($part_tblname, $mod_indexes);
					$alter_sql = rtm_sql_concat($alter_sql, $mod_sql_idx);
				}

				if(strlen(trim($alter_sql))){
					$alter_sql = "ALTER TABLE `$part_tblname` $alter_sql";
					echo "======Current Alter SQL is ======\n$alter_sql\n";
					$return_code = db_execute($alter_sql);
					if (!$return_code) {
						cacti_log("ERROR: Partition table[$part_tblname] failed.  SQL:'" . clean_up_lines($alter_sql) . "'", false, 'DBCALL');
						return DB_STATUS_ERROR;
					} else {
						cacti_log('SUCCESS: Plugin ' . strtoupper($plugin_name) . ' Partition Upgrade completed for table ' . $part_tblname, true);
					}
				}

			}
		}
	}
	return DB_STATUS_SUCCESS;
}

/**
 * Note: Only support three digit partition no '###' now.
 * @param string $table_name partition table base name
 * @param string $message output to log
 * @param string $syntax SQL clause with base table name
 * @param boolean $only_partition
 * @return void
 */
function execute_sql_partitions($table_name, $message, $syntax, $only_partition = true) {
	$status = DB_STATUS_SUCCESS;

	if (!$only_partition) {
		$status = execute_sql($message, $syntax);
	}

	if (!$status) {
		return $status;
	}

	$partition_tables = db_fetch_assoc("SHOW TABLES LIKE '". $table_name. "\_v___'");
	if (cacti_sizeof($partition_tables)) {
		foreach ($partition_tables as $table) {
			$keys = array_keys($table);
			$table = $table[$keys[0]];
			$new_syntax = str_replace($table_name, $table, $syntax);
			$new_message = str_replace($table_name, $table, $message);
			$status = execute_sql($new_message, $new_syntax);
			if (!$status) {
				return $status;
			}
		}
	}
	return $status;
}

function add_column_partitions($table, $column, $syntax, $only_partition = true) {
	$status = DB_STATUS_SUCCESS;

	if (!$only_partition) {
		$status = add_column($table, $column, $syntax);
	}

	if (!$status) {
		return $status;
	}

	$partition_tables = db_fetch_assoc("SHOW TABLES LIKE '". $table. "\_v___'");
	if (cacti_sizeof($partition_tables)) {
		foreach ($partition_tables as $part_table) {
			$keys = array_keys($part_table);
			$part_table = $part_table[$keys[0]];
			$new_syntax = str_replace($table, $part_table, $syntax);
			$status = add_column($table, $column, $syntax);
			if (!$status) {
				return $status;
			}
		}
	}
	return $status;
}

/**
 * Add multiple-columns to table
 *
 * @param string$table table name
 * @param array$columns(column_name => syntax)
 * @return void
 */
function add_columns_partitions($table, $columns, $only_partition = true) {
	$status = DB_STATUS_SUCCESS;

	if (!$only_partition) {
		$status = add_columns($table, $columns);
	}

	if (!$status) {
		return $status;
	}

	$partition_tables = db_fetch_assoc("SHOW TABLES LIKE '". $table. "\_v%'");
	if (cacti_sizeof($partition_tables)) {
		foreach ($partition_tables as $part_table) {
			$keys = array_keys($part_table);
			$part_table = $part_table[$keys[0]];
			foreach ($columns as $col => $syntax){
				$new_syntax = str_replace($table, $part_table, $syntax);
				$columns[$col] = $new_syntax;
			}
			$status = add_columns($table, $columns);
			if (!$status) {
				return $status;
			}
		}
	}
	return $status;
}
