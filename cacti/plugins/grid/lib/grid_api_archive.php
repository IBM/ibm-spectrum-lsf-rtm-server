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

function grid_perform_archive($now, $type = 'INTERVAL') {
	global $debug, $config, $arch_db_type;

	/* get archive connection information */
	$arch_db_type     = read_config_option('grid_archive_db_type');
	$arch_db_default  = read_config_option('grid_archive_name');
	$arch_db_hostname = read_config_option('grid_archive_host');
	$arch_db_username = read_config_option('grid_archive_user');
	$arch_db_password = read_config_option('grid_archive_password');
	$arch_db_port     = read_config_option('grid_archive_port');
	$arch_db_ssl      = read_config_option('grid_archive_ssl');
	$conn_id          = -1;

	if (strlen($arch_db_port)) {
		$conn_id = db_connect_real($arch_db_hostname, $arch_db_username, $arch_db_password,
			$arch_db_default, $arch_db_type, $arch_db_port, 20, $arch_db_ssl);
	} else {
		$conn_id = db_connect_real($arch_db_hostname, $arch_db_username, $arch_db_password,
			$arch_db_default, $arch_db_type, 3306, 20, $arch_db_ssl);
	}

	if (is_object($conn_id)) {
		if ($type == 'INTERVAL') {
			/* transfer minor tables */
			$cluster_rows     = grid_archive_refresh_records($conn_id, 'grid_clusters');
			$hostgroup_rows   = grid_archive_refresh_records($conn_id, 'grid_hostgroups');
			$hostinfo_rows    = grid_archive_refresh_records($conn_id, 'grid_hostinfo');
			$host_sres_rows   = grid_archive_refresh_records($conn_id, 'grid_hostresources');
			$bhosts_rows      = grid_archive_refresh_records($conn_id, 'grid_hosts');
			$host_jtraf_rows  = grid_archive_refresh_records($conn_id, 'grid_hosts_jobtraffic');
			$host_dres_rows   = grid_archive_refresh_records($conn_id, 'grid_hosts_resources');
			$poller_rows      = grid_archive_refresh_records($conn_id, 'grid_pollers');
			$cluster_res_rows = grid_archive_refresh_records($conn_id, 'grid_resources');

			/* transfer the grid_jobs table */
			$frequency        = read_config_option('grid_archive_frequency');
			$job_rows         = grid_archive_refresh_update_type_records($conn_id, $now, 'grid_jobs_finished', strtotime("-$frequency seconds"));

			$archive_stats = 'Time:' . round(time()-$now,2) . " " .
				"Pollers:$poller_rows " .
				"Clusters:$cluster_rows " .
				"ClusterRes:$cluster_res_rows " .
				"HostInfo:$hostinfo_rows " .
				"BatchHosts:$bhosts_rows " .
				"HostGroups:$hostgroup_rows " .
				"HostStatic:$host_sres_rows " .
				"HostDynamic:$host_dres_rows " .
				"HostJTraf:$host_jtraf_rows " .
				"Jobs:$job_rows";

			/* log statistics */
			cacti_log("GRID ARCHIVE STATS: $archive_stats", true, 'SYSTEM');

			/* store statistics */
			set_config_option('grid_archive_stats', $archive_stats);
		} else {
			$rows = db_fetch_assoc_prepared("SELECT *
				FROM grid_job_daily_stats
				WHERE date_recorded <= ?
				AND date_recorded > ?", array(date('Y-m-d H:i:s', $now), date('Y-m-d H:i:s',  strtotime('-1 days', $now))));

			$sql_suffix = '';
			$sql_prefix = grid_table_insert_format('grid_job_daily_stats', $sql_suffix);

			grid_archive_update_rows($conn_id, 'grid_job_daily_stats', $rows, $sql_prefix, $sql_suffix, 'false');
		}
	} else {
		cacti_log("ERROR: GRID ARCHIVE connect $arch_db_hostname Failed.", true, 'SYSTEM');
	}
}

function grid_archive_refresh_update_type_records($conn_id, $now, $table_name, $update_time = 0) {
	global $debug, $config;
	include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

	$total_rows = 0;

	if ($update_time == 0) {
		$update_time = strtotime('-3 Days');
	}

	if (($now - $update_time) > 3600) {
		$hourly = true;
	} else {
		$hourly = false;
	}

	if ($table_name == 'grid_jobs_finished') {
		db_execute("CREATE TEMPORARY TABLE `steve` (
			`clusterid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
			`jobid` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`indexid` INT UNSIGNED NOT NULL DEFAULT 0,
			`submit_time` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (`clusterid`, `jobid`, `indexid`, `submit_time`)) ENGINE = MEMORY;");

		db_execute("CREATE TEMPORARY TABLE `goran` LIKE grid_jobs_jobhosts_finished");

		db_execute("CREATE TEMPORARY TABLE `franklin` LIKE grid_jobs_reqhosts_finished");
	}

	$first_pass = true;
	$limits     = false;
	$limit      = '';
	$prev_limit = 0;

	/* transfer the records, in batches if required */
	while (1) {
		if ($update_time > ($now - 1200)) {
			$update_time = $update_time - 1200;
		}

		if ($debug && !$limits) {
			if ($hourly) {
				grid_debug("Source  Records from '$table_name', 1 Hour Inc, Start: " . date('Y-m-d H:i:s', $update_time-3600) . ', End: ' . date('Y-m-d H:i:s', $update_time));
			} else {
				grid_debug("Source  Records from '$table_name', 1 Pass, Start: " . date('Y-m-d H:i:s', $update_time) . ', End: ' . date('Y-m-d H:i:s', $now));
			}
		}

		/* get the insert prefix */
		$sql_suffix = '';
		$sql_insert_prefix = grid_table_insert_format($table_name, $sql_suffix);

		if ($first_pass) {
			/* see if there is a limit issue */
			/* since the actuall archiving has 10 minutes delay, the entry number counting should also use this 10 minutes delay*/
			if (read_config_option('grid_partitioning_enable') == '') {
				$remainder = db_fetch_cell_prepared("SELECT COUNT(*) FROM $table_name
					WHERE last_updated>= ?
					AND last_updated<?", array(date('Y-m-d H:i:s', $update_time - 600), date('Y-m-d H:i:s', $update_time + 3600 - 600)));
			} else {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', $update_time - 600), date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600));
				$sql_prefix = 'SELECT SUM(total) FROM (';
				$sql = '';
				$sql_params = array();
				if (cacti_sizeof($tables)) {
					foreach($tables as $table) {
						$sql .= (strlen($sql) ? ' UNION ':'') .
							"(SELECT COUNT(*) AS total
								FROM $table
								WHERE last_updated>= ?
								AND last_updated<?)";
						$sql_params[] = date('Y-m-d H:i:s', $update_time - 600);
						$sql_params[] = date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600);
					}
					$sql = $sql_prefix . $sql . ') AS gjf ';
					$remainder = db_fetch_cell_prepared($sql, $sql_params);
				} else {
					$remainder = 0;
				}
			}

			if ($remainder > 50000) {
				$limits = true;
				if (!empty($prev_limit)) {
					$limit = "LIMIT $prev_limit,50000";
				} else {
					$limit = 'LIMIT 50000';
				}
				$prev_limit += 50000;
			} else {
				$limits = false;
				$limit  = '';
			}
			$first_pass = false;
		} elseif ($limits) {
			/* change 50000 to 50001, otherwise 5 the logical in next 'if' will always be true and records more than 2*50000 will be ignored*/
			$limit = "LIMIT $prev_limit,50001";
			/* since the actuall archiving has 10 minutes delay, the entry number counting should also use this 10 minutes delay*/
			if (read_config_option('grid_partitioning_enable') == '') {
				$remainder = db_fetch_cell_prepared("SELECT COUNT(*) FROM $table_name
					WHERE last_updated>= ?
					AND last_updated< ? $limit", array(date('Y-m-d H:i:s', $update_time - 600), date('Y-m-d H:i:s', $update_time + 3600 - 600)));
			} else {
				$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', $update_time - 600), date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600));
				$sql_prefix = 'SELECT SUM(total) FROM (';
				$sql = '';
				$sql_params = array();
				if (cacti_sizeof($tables)) {
					foreach($tables as $table) {
						$sql .= (strlen($sql) ? ' UNION ':'') .
							"(SELECT COUNT(*) AS total
								FROM $table
								WHERE last_updated>= ?
								AND last_updated< ?)";
						$sql_params[] = date('Y-m-d H:i:s', $update_time - 600);
						$sql_params[] = date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600);
					}
					$sql = $sql_prefix . $sql . ') AS gjf ';
					$remainder = db_fetch_cell_prepared($sql, $sql_params);
				} else {
					$remainder = 0;
				}
			}

			$limit = "LIMIT $prev_limit, 50000";
			if ($remainder <= 50000) {
				$limits     = false;
				$prev_limit = 0;
			} else {
				$prev_limit += 50000;
			}
		} else {
			$limits     = false;
			$prev_limit = 0;
			$limit      = '';
		}

		switch ($table_name) {
			case 'grid_jobs_finished':
				$sql_params = array();
				/* don't get current records, always start 10 minute behind */
				if (read_config_option('grid_partitioning_enable') == '') {
					$sql  = "SELECT *
						FROM $table_name
						WHERE last_updated>= ?
						AND last_updated< ?
						ORDER BY jobid, indexid, submit_time $limit";
					$sql_params[] = date('Y-m-d H:i:s', $update_time - 600);
					$sql_params[] = date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600);
				} else {
					$tables = partition_get_partitions_for_query('grid_jobs_finished', date('Y-m-d H:i:s', $update_time - 600), date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600));
					$sql_prefix = 'SELECT * FROM (';
					$sql = '';
					if (cacti_sizeof($tables)) {
						foreach($tables as $table) {
							$sql .= (strlen($sql) ? ' UNION ':'') .
								"(SELECT *
									FROM $table
									WHERE last_updated>= ?
									AND last_updated< ?)";
							$sql_params[] = date('Y-m-d H:i:s', $update_time - 600);
							$sql_params[] = date('Y-m-d H:i:s', ($hourly ? $update_time:$now) + 3600 - 600);
						}
						$sql = $sql_prefix . $sql . ") AS gjf  ORDER BY jobid, indexid, submit_time $limit";
					}
				}
				if(strlen($sql)){
					$rows = db_fetch_assoc_prepared($sql, $sql_params);
				} else {
					$rows = array();
				}

				break;
			default:
		}

		/* add the rows to the table */
		/* since this function is only called for grid_jobs_finished, 200 is used to minimize memory usage when archiving job historical data*/
		$total_rows += grid_archive_update_rows($conn_id, $table_name, $rows, $sql_insert_prefix, $sql_suffix, $hourly, 200);

		if ($table_name == 'grid_jobs_finished') {
			if (cacti_sizeof($rows)) {
				$xfer_prefix = 'INSERT IGNORE INTO steve
					(`clusterid`, `jobid`, `indexid`, `submit_time`) VALUES ';

				$k = 0;
				$sql = '';
				foreach($rows as $row) {
					if (strlen($sql)) {
						$sql .= ", ('";
					} else {
						$sql .= " ('";
					}

					$sql .= $row['clusterid']   . "', '" .
							$row['jobid']       . "', '" .
							$row['indexid']     . "', '" .
							$row['submit_time'] . "')";

					if ($k > 500) {
						db_execute($xfer_prefix . $sql);
						$k = 0;
						$sql = '';
					} else {
						$k++;
					}
				}

				if ($k > 0) {
					db_execute($xfer_prefix . $sql);
				}

				db_execute('INSERT INTO goran
					SELECT grid_jobs_jobhosts_finished.*
					FROM grid_jobs_jobhosts_finished
					INNER JOIN steve
					ON (steve.clusterid=grid_jobs_jobhosts_finished.clusterid)
					AND (steve.jobid=grid_jobs_jobhosts_finished.jobid)
					AND (steve.indexid=grid_jobs_jobhosts_finished.indexid)
					AND (steve.submit_time=grid_jobs_jobhosts_finished.submit_time)');

				db_execute('INSERT INTO franklin
					SELECT grid_jobs_reqhosts_finished.*
					FROM grid_jobs_reqhosts_finished
					INNER JOIN steve
					ON (steve.clusterid=grid_jobs_reqhosts_finished.clusterid)
					AND (steve.jobid=grid_jobs_reqhosts_finished.jobid)
					AND (steve.indexid=grid_jobs_reqhosts_finished.indexid)
					AND (steve.submit_time=grid_jobs_reqhosts_finished.submit_time)');

				$rows       = db_fetch_assoc('SELECT * FROM goran');
				$sql_suffix = '';
				$sql_prefix = grid_table_insert_format('grid_jobs_jobhosts_finished', $sql_suffix);

				grid_archive_update_rows($conn_id, 'grid_jobs_jobhosts_finished', $rows, $sql_prefix, $sql_suffix, $hourly);

				$rows       = db_fetch_assoc('SELECT * FROM franklin');
				$sql_suffix = '';
				$sql_prefix = grid_table_insert_format('grid_jobs_reqhosts_finished', $sql_suffix);

				grid_archive_update_rows($conn_id, 'grid_jobs_reqhosts_finished', $rows, $sql_prefix, $sql_suffix, $hourly);

				/* reinitialize the temporary tables */
				db_execute('TRUNCATE TABLE steve');
				db_execute('TRUNCATE TABLE goran');
				db_execute('TRUNCATE TABLE franklin');
			}
		}

		/* see if it's time to break from the loop */
		if (!$limits) {
			if ($hourly) {
				if ($update_time < $now) {
					$update_time += 3600;
				} else {
					break;
				}
			} else {
				break;
			}
			$first_pass = true;
		} else {
			/* continue processing, limits in effect */
		}
	}

	return $total_rows;
}

function grid_archive_update_rows($conn_id, $table_name, &$rows, &$sql_prefix, &$sql_suffix, $hourly = false, $num_rows = 5000) {
	global $debug;

	$i          = 0;
	$total_rows = 0;

	if (cacti_sizeof($rows)) {
		foreach($rows as $row) {
			/* separator between inserts */
			if ($i == 0) {
				$sql_insert = $sql_prefix . ' (';
			} else {
				$sql_insert .= ', (';
			}

			$i++;
			$total_rows++;

			/* contents of row */
			$sql_row = '';

			if (cacti_sizeof($row)) {
				foreach($row as $field) {
					if (strlen($sql_row)) {
						$sql_row .= ', ' . db_qstr($field);
					} else {
						$sql_row = db_qstr($field);
					}
				}
			}

			/* row terminator */
			$sql_insert .= $sql_row . ')';

			/* perform an insert */
			if ($i > $num_rows) {
				$i = 0;
				/* insert sql rows */
				$result = db_execute($sql_insert . $sql_suffix, false, $conn_id);

				if ($debug) {
					if ($hourly) {
						grid_debug("Archive Records to   '$table_name' at 1 Hour Intervals, $num_rows Records at a Time" . ($result == 0 ? ' - Failed' : ' - Success'));
					} else {
						grid_debug("Archive Records to   '$table_name', $num_rows Records at a Time" . ($result == 0 ? ' - Failed' : ' - Success'));
					}
				}
			}
		}

		/* perform final insert */
		if ($i > 0) {
			/* insert sql rows */
			$result = db_execute($sql_insert . $sql_suffix, false, $conn_id);

			if ($debug) {
				if ($hourly) {
					grid_debug("Archive Records to   '$table_name' at 1 Hour Intervals, $num_rows Records at a Time" . ($result == 0 ? ' - Failed' : ' - Success'));
				} else {
					grid_debug("Archive Records to   '$table_name', $num_rows Records at a Time" . ($result == 0 ? ' - Failed' : ' - Success'));
				}
			}
		}
	}

	if ($debug) {
		grid_debug("Total Records for    '$table_name', " . $total_rows);
	}

	return $total_rows;
}

function grid_archive_refresh_records($conn_id, $table_name) {
	global $debug;

	$total_rows = 0;

	grid_debug("Source  Records from '$table_name'");

	/* get the rows from the source table */
	$rows = db_fetch_assoc("SELECT * FROM $table_name");

	/* calculate the insert/replace prefix */
	$sql_suffix = '';
	$sql_prefix = grid_table_insert_format($table_name, $sql_suffix);

	grid_debug('SQL Syntax Derived.  Inserting Rows');

	/* add the rows to the table */
	$total_rows = grid_archive_update_rows($conn_id, $table_name, $rows, $sql_prefix, $sql_suffix);

	return $total_rows;
}

function grid_table_insert_format($table_name, &$sql_suffix, $type = 'INSERT') {
	global $arch_db_type;

	$cluster_cols = db_fetch_assoc('SHOW COLUMNS FROM ' . $table_name);

	/* in mysql, replace is more effiecient */
	if ($arch_db_type == 'mysql') {
		$type = 'REPLACE';
	}

	$sql_prefix = $type . ' INTO ' . $table_name . ' (';
	$sql_format = '';
	$sql_suffix = '';

	if (cacti_sizeof($cluster_cols)) {
		foreach($cluster_cols as $column) {
			if (strlen($sql_format)) {
				$sql_format .= ', `' . $column['Field'] . '`';

				if ($type == 'INSERT') {
					$sql_suffix .= ', `' . $column['Field'] . '`=VALUES(`' . $column['Field'] . '`)';
				}
			} else {
				$sql_format .= '`' . $column['Field'] . '`';

				if ($type == 'INSERT') {
					$sql_suffix .= '`' . $column['Field'] . '`=VALUES(`' . $column['Field'] . '`)';
				}
			}
		}
	} else {
		return '';
	}

	$sql_insert = $sql_prefix . $sql_format . ') VALUES';

	if ($type == 'INSERT') {
		$sql_suffix = ' ON DUPLICATE KEY UPDATE ' . $sql_suffix;
	}

	return $sql_insert;
}
