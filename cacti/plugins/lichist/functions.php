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

// Get finished jobs from multiple partitions in the database
// @param $sql_query The SQL query to be updated with the partitions involved in the query
// @param $table_name The table name to be updated with the partitions involved
// @param $sql_where The sql_where to include in each partition selected
// @param $date1 The state date of the period
// @param $date2 The end date of the period
// @return The finished job array
function get_finished_jobs_partition($sql_query, $table_name, $sql_where, $date1, $date2) {
	$sql_query_set = "";
	$finished_jobs_array = array();
	$tables = partition_get_partitions_for_query($table_name, $date1, $date2);
	foreach ($tables as $table) {
		$query_replaced = str_replace($table_name, $table, $sql_query);

		$new_finished_jobs = db_fetch_assoc("$query_replaced");

		//merge new finished jobs without duplicate records, like SQL UNION operation
		$finished_jobs_array = array_unique(array_merge($finished_jobs_array,$new_finished_jobs), SORT_REGULAR);
	}

	return $finished_jobs_array;
}


// Generates SQL query that pulls data from multiple partitions in the database
// @param $sql_query The SQL query to be updated with the partitions involved in the query
// @param $table_name The table name to be updated with the partitions involved
// @param $sql_where The sql_where to include in each partition selected
// @param $date1 The state date of the period
// @param $date2 The end date of the period
// @return The updated SQL query, with $table_name replaced in $sql_query with the relevant partitions
function generate_partition_union_query($sql_query, $table_name, $sql_where, $date1, $date2) {
	$sql_query_set = "";
	$tables = partition_get_partitions_for_query($table_name, $date1, $date2);
	foreach ($tables as $table) {
		$query_replaced = "(" . str_replace($table_name, $table, $sql_query);
		if (strlen($sql_query_set) == 0) {
			$sql_query_set = $query_replaced . " " . $sql_where . ")";
		}else{
			$sql_query_set = $sql_query_set . " UNION " . $query_replaced . " " . $sql_where . ")";
		}
	}

	return $sql_query_set;
}
