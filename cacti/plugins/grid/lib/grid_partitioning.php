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

/* SQL Syntax
CREATE TABLE `grid_table_partitions` (
  `partition` varchar(5) NOT NULL,
  `table_name` varchar(45) NOT NULL,
  `min_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  `max_time` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`partition`,`table_name`),
  KEY `max_time` (`max_time`),
  KEY `min_time` (`min_time`)
) ENGINE=InnoDB;
*/

function partition_clean_temp_tables($table) {
	global $database_default;

	$tables = db_fetch_assoc_prepared("SHOW TABLES LIKE ?", array($table . "_%"));

	if (cacti_sizeof($tables)) {
		foreach($tables as $t) {
			$drop = false;
			foreach($t as $key => $value) {
				if (substr_count($key, "Tables_in_")) {
					$test = str_replace($table . "_", "", $value);

					if (($test != "") && ($test == "temp" || is_numeric($test))) {
						$drop_table = $value;
						$drop = true;
					}
				}
			}

			if ($drop) {
				cacti_log("WARNING: Detecting Temporary Table '$drop_table' Dropping!", true, "GRID");
				db_execute("DROP TABLE IF EXISTS $drop_table");
			}
		}
	}
}

function partition_sync_partitions($table) {
	global $database_default;

	$partition_tables = db_fetch_assoc_prepared("SHOW TABLES LIKE ?", array($table . "_v%"));
	$known_partitions = array_rekey(db_fetch_assoc_prepared("SELECT CONCAT_WS('', table_name, '_v', `partition`, '') AS table_name, '0' AS found
		FROM grid_table_partitions
		WHERE table_name= ?
		ORDER BY table_name", array($table)), "table_name", "found");

	if (cacti_sizeof($partition_tables)) {
		foreach($partition_tables as $table) {
			foreach($table as $key => $value) {
				if (substr_count($key, "Tables_in_")) {
					if (isset($known_partitions[$value])) {
						$known_partitions[$value] = true;
					} else {
						cacti_log("WARNING: Detecting Out-of-Sync Partitioning Table '$value' Dropped!", true, "GRID");
						db_execute("DROP TABLE IF EXISTS $value");
					}
				}
			}
		}

		foreach($known_partitions as $table => $exists) {
			if (!$exists) {
				cacti_log("WARNING: Partitiong '$table' Not Found, Removing Entry from Partition Table!", true, "GRID");
				$items = explode("_v", $table);
				db_execute_prepared("DELETE FROM grid_table_partitions WHERE table_name=? AND `partition`=?", array($items[0], $items[1]));
			}
		}
	}
}

function partition_create($table, $min_time_field, $max_time_field, $partition_version = '-1') {
	global $new_partition_tables;

	if ($partition_version == '-1') {
		$partition = partition_getnext($table);
		$new_table = $table . "_v" . $partition;
	} else {
		$partition = $partition_version;
		$new_table = $table."_v".$partition_version;
	}

	/* look for missing partitions out of sync and correct. can happen as a result of a restore */
	partition_sync_partitions($table);

	/* look for and remove temporary tables left over from last maintenance */
	partition_clean_temp_tables($table);

	$rand = rand();
	$ttbl = $table . "_" . $rand;

	cacti_log("NOTE: Creating New Partition for Table:'$table' as '$new_table'", true, "GRID");

	$success = db_execute("CREATE TABLE " . $ttbl . " LIKE " . $table);

	/* correct base table to address missing or otherwise indexes */
	partition_adjust_structure($table);

	if ($success) {
		$success = db_execute("RENAME TABLE $table TO $new_table, $ttbl TO $table");

		if ($success) {
			cacti_log("NOTE: New partition successfully created for '" . $table . "'", true, "GRID");

			if ($max_time_field != $min_time_field) {
				/* obtain the minimum abarrant end time */
				if ($min_time_field != "submit_time") {
					$alt_min_time1 = db_fetch_cell("SELECT MIN($max_time_field)
							FROM $new_table
							WHERE ($min_time_field<='1971-02-01')
							AND ($max_time_field>'1971-02-01')");

					$alt_min_time2 = db_fetch_cell("SELECT MIN($min_time_field)
							FROM $new_table
							WHERE ($min_time_field>'1971-02-01')");
				} else {
					$alt_min_time1 = db_fetch_cell("SELECT MIN($max_time_field)
							FROM $new_table
							WHERE $max_time_field>'1971-02-01'");

					$alt_min_time2 = db_fetch_cell("SELECT MIN($min_time_field)
							FROM $new_table");
				}

				$max_time = db_fetch_cell("SELECT MAX($max_time_field)
					FROM $new_table");

				if ((strtotime($alt_min_time1) < strtotime($alt_min_time2)) &&
					(strtotime($alt_min_time1) > 87000) && ($alt_min_time1 != "")) {
					$min_time = $alt_min_time1;
				} else {
					$min_time = $alt_min_time2;
				}

				if (strtotime($min_time) < 87000) {
					cacti_log("ERROR: Min Time Field is 0 for '$new_table'", true, "GRID");
				}
			} else {
				if ($min_time_field != "submit_time")  {
					$min_time = db_fetch_cell("SELECT MIN($min_time_field)
							FROM $new_table
							WHERE $min_time_field>'1971-02-01'");
				} else {
					$min_time = db_fetch_cell("SELECT MIN($min_time_field)
							FROM $new_table");
				}
				$max_time = db_fetch_cell("SELECT MAX($max_time_field)
					FROM $new_table");
			}

			/* record statistics in the partitions table */
			db_execute_prepared("INSERT INTO grid_table_partitions
				(table_name, `partition`, min_time, max_time)
				VALUES (?, ?, ?, ?)",
				array($table, $partition, $min_time, $max_time));

			set_config_option($table . '_partitioning_version', $partition);

			$new_partition_tables[] =$new_table;
		} else {
			cacti_log("ERROR: Unable to Rename Temp Table to '" . $table . "'", true, "GRID");
		}
	} else {
		cacti_log("ERROR: Unable to Create New Main Table '" . $ttbl . "'!", true, "GRID");
	}
}

function partition_adjust_structure($table) {
	if ($table == "grid_jobs_rusage") {
		$keys = array_rekey(db_fetch_assoc("SHOW INDEXES FROM $table"), "Key_name", "Key_name");
		if (!in_array("submit_time", $keys)) {
			db_execute("ALTER TABLE `$table`
				ADD INDEX `submit_time` (`submit_time`);");
		}
	}
}

function partition_destroy($partitions) {
	if (is_array($partitions)) {
		foreach($partitions as $partition => $table) {
			cacti_log("NOTE: Removing Partition:'" . $partition . "' from Table:'$table'", true, "GRID");
			db_execute("DROP TABLE IF EXISTS " . $table . "_v" . $partition);
			db_execute_prepared("DELETE FROM grid_table_partitions WHERE table_name=? AND `partition`=?", array($table, $partition));
		}
	}
}

function partition_getlatest($table) {
	$partition = db_fetch_cell_prepared("SELECT `partition` FROM grid_table_partitions WHERE table_name=? ORDER BY max_time DESC LIMIT 1", array($table));

	if (strlen($partition)) {
		return $table . "_v" . $partition;
	}
}

function partition_getnext($table) {
	$curmax = db_fetch_row_prepared("SELECT `partition`, max_time
		FROM grid_table_partitions
		WHERE table_name=?
		ORDER BY max_time DESC
		LIMIT 1", array($table));

	if (cacti_sizeof($curmax)) {
		$next_partition = "000" . (($curmax["partition"] + 1) % 1000);

		return substr($next_partition, strlen($next_partition)-3);
	} else {
		return "000";
	}
}

function partition_get_partitions_for_query($table, $min_time, $max_time, $type = 0) {
	if ($type == 0) {
		$partitions = array_rekey(db_fetch_assoc_prepared("SELECT table_name, `partition`
			FROM grid_table_partitions
			WHERE ((? BETWEEN min_time AND max_time)
			OR (? < min_time AND ? > max_time)
			OR (? > min_time AND ? < max_time)
			OR (? BETWEEN min_time AND max_time))
			AND table_name=?", array($min_time, $min_time, $max_time,$min_time, $max_time, $max_time, $table)), "partition", "table_name");
	} else {
		$partitions = array_rekey(db_fetch_assoc_prepared("SELECT table_name, `partition`
			FROM grid_table_partitions
			WHERE (? >= max_time)
			AND table_name=?", array($min_time, $table)), "partition", "table_name");
	}

	$max_partition_time = db_fetch_cell_prepared("SELECT MAX(max_time)
		FROM grid_table_partitions
		WHERE table_name=?", array($table));

	if (strtotime($max_partition_time) < strtotime($max_time)) {
		$partitions = array('MAIN' => $table) + $partitions;
	}

	/* process into a workable array */
	$union_tables = array();
	if (cacti_sizeof($partitions)) {
	foreach($partitions as $partition=>$table_name) {
		if ($partition != 'MAIN') {
			$union_tables[] = $table_name . "_v" . $partition;
		} else {
			$union_tables[] = $table_name;
		}
	}
	}

	return $union_tables;
}

function partition_timefor_create($table, $time_field) {
	global $config;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$time_interval = time() - strtotime("-" . read_config_option("grid_partitioning_time_range"));
	$sql = "SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MIN($time_field))
		FROM $table WHERE $time_field>'1971-02-01'";

	/* add some debugging */
	grid_debug($sql);

	$db_interval   = db_fetch_cell($sql) + 21600;

	/* add some debugging */
	grid_debug("Time Interval:$time_interval, DB Interval:$db_interval");

	if ($db_interval > $time_interval) {
		cacti_log("NOTE: Time To Create New Partition for Table:'$table'", true, "GRID");
		return true;
	} else {
		return false;
	}
}

function partition_prune_partitions($table, $retention = '') {
	global $config;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	switch($table) {
		case "grid_jobs_jobhosts_finished":
		case "grid_jobs_reqhosts_finished":
		case "grid_jobs_pendreasons_finished":
		case "grid_jobs_sla_loaning_finished":
		case "grid_jobs_finished":
		case "grid_arrays_finished":
			$data_retention = read_config_option("grid_summary_data_retention");
			break;
		case "grid_host_closure_events_finished":
			$data_retention = read_config_option("grid_host_closure_data_retention");
			break;
		case "grid_heuristics_percentiles":
			$data_retention = read_config_option("heuristics_days") . 'days';
			break;
		case "grid_jobs_rusage":
		case "grid_jobs_host_rusage":
		case "grid_jobs_gpu_rusage":
			$data_retention = read_config_option("grid_detail_data_retention");
			break;
		case "grid_job_daily_stats":
			$data_retention = read_config_option("grid_daily_data_retention");
			break;
		case "lic_daily_stats":
			if (read_config_option('lic_data_retention', true)) {
				$data_retention = read_config_option('lic_data_retention', true);
			} else {
				$data_retention = "2weeks";
			}
			break;
		default:
			$data_retention = read_config_option("grid_detail_data_retention");
			break;
	}

	if ($retention == '') {
		$data_retention = strtotime("-" . $data_retention);
	} else {
		$data_retention = strtotime("-" . $retention);
	}

	$partitions_to_delete = db_fetch_cell_prepared("SELECT count(*)
		FROM grid_table_partitions
		WHERE table_name=?
		AND max_time<FROM_UNIXTIME(?)", array($table, $data_retention));

	if ($partitions_to_delete > 0) {
		grid_debug("Found '$partitions_to_delete' Partitions to Delete for '$table'");
		$tables = db_fetch_assoc_prepared("SELECT `partition`, table_name
			FROM grid_table_partitions
			WHERE table_name=?
			AND max_time<FROM_UNIXTIME(?)
			ORDER BY max_time", array($table, $data_retention));
		if (cacti_sizeof($tables)) {
			$partitions = array_rekey($tables, "partition", "table_name");

			grid_debug("About to Enter Remove Partitions Function");
			partition_destroy($partitions);
		}
	} else {
		grid_debug("No Partitions to Delete for '$table'");
	}
}
