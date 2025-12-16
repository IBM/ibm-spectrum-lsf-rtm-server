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

function disku_partition_prune_partitions($table) {
	$data_retention = read_config_option("disku_retention");
	$rotation       = read_config_option('disku_rotation_frequency');

	if ($rotation == 0) {
		$partition_size = 86400;
	}else{
		$partition_size = 86400*7;
	}

	$data_retention = time() - strtotime("-" . $data_retention);

	$max_partitions = min(array("1000", ceil($data_retention / $partition_size)));

	$total_partitions = db_fetch_cell_prepared("SELECT count(*)
		FROM grid_table_partitions
		WHERE table_name=?", array($table));

	$partitions_to_delete = $total_partitions - $max_partitions;

	if ($partitions_to_delete > 0) {
		disku_debug("Found '$partitions_to_delete' Partitions to Delete for '$table'");
		$tables = db_fetch_assoc_prepared("SELECT `partition`, table_name
				FROM grid_table_partitions
				WHERE table_name=?
				ORDER BY max_time
				LIMIT $partitions_to_delete", array($table));
		if (cacti_sizeof($tables)) {
			$partitions = array_rekey($tables, "partition", "table_name");

			disku_debug("About to Enter Remove Partitions Function");
			partition_destroy($partitions);
		}
	}else{
		disku_debug("No Partitions to Delete for '$table'");
	}
}
?>

