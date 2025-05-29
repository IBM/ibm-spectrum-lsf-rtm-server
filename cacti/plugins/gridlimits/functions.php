<?php
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

/**
 * Get table partitions in the database for a query
 *
 * @param $sql_query The SQL query to be updated with the partitions involved in the query
 * @param $table_name The table name to be updated with the partitions involved
 * @param $sql_where The sql_where to include in each partition selected
 * @param $date1 The state date of the period
 * @param $date2 The end date of the period
 *
 * @return The updated query
 */
function get_records_partition($sql_query, $table_name, $sql_where, $date1, $date2, $params) {
	$output = array();

	$tables = partition_get_partitions_for_query($table_name, $date1, $date2);

	foreach ($tables as $table) {
		$query_replaced = str_replace($table_name, $table, $sql_query);

		$records = db_fetch_assoc_prepared($query_replaced, $params);
		$output  = array_unique(array_merge($output,$records), SORT_REGULAR);
	}

	return $output;
}


/**
 * Generates SQL query that pulls data from multiple partitions in the database
 *
 * @param $sql_query The SQL query to be updated with the partitions involved in the query
 * @param $table_name The table name to be updated with the partitions involved
 * @param $sql_where The sql_where to include in each partition selected
 * @param $date1 The state date of the period
 * @param $date2 The end date of the period
 * @param $params For prepared statements
 * @param $all Boolean to use UNION ALL vs UNION
 *
 * @return The updated SQL query, with $table_name replaced in $sql_query with the relevant partitions
 */
function generate_partition_union_query($sql_query, $table_name, $sql_where, $date1, $date2, $params = array(), $all = true) {
	$return = array(
		'params' => array(),
		'sql'    => ''
	);

	$tables = partition_get_partitions_for_query($table_name, $date1, $date2);

	foreach ($tables as $table) {
		$query_replaced = '(' . str_replace($table_name, $table, $sql_query);

		$return['sql'] .= ($return['sql'] == '' ? '':($all ? ' UNION ALL ':' UNION ')) . $query_replaced . ' ' . $sql_where . ')';

		if (cacti_sizeof($params)) {
			foreach($params as $p) {
				$return['params'][] = $p;
			}
		}
	}

	return $return;
}

function sql_param_debug($sql, $params) {
	if (cacti_sizeof($params)) {
		$sql = str_replace('?', '"%s"', $sql);
		$format_cnt = substr_count($sql, '%s');
		$param_cnt  = cacti_sizeof($params);
		cacti_log('FormatCNT: ' . $format_cnt . ', ParamCNT: ' . $param_cnt, false);
		cacti_log('SQL: ' . $sql, false);
		cacti_log('PARAMS: ' . implode(', ', $params), false);
		cacti_log('WITH PARAMS: ' . vsprintf($sql, $params), false);
	} else {
		cacti_log('NO PARAMS: ' . $sql, false);
	}
}

function get_limit_type($sequenceid) {
	$row = db_fetch_row_prepared('SELECT *
		FROM grid_limits_history
		WHERE sequenceid = ?',
		array($sequenceid));

	if ($row['slots_usage'] > 0 || $row['slots_limit'] > 0) {
		return 'slots';
	} elseif ($row['mem_usage'] > 0 || $row['mem_limit'] > 0) {
		return 'mem';
	} elseif ($row['swp_usage'] > 0 || $row['swp_limit'] > 0) {
		return 'swp';
	} elseif ($row['tmp_usage'] > 0 || $row['tmp_limit'] > 0) {
		return 'tmp';
	} elseif ($row['jobs_usage'] > 0 || $row['jobs_limit'] > 0) {
		return 'jobs';
	} elseif ($row['fwd_tasks_usage'] > 0 || $row['fwd_tasks_limit'] > 0) {
		return 'fwd_tasks';
	} else {
		return $row['resources'];
	}
}
