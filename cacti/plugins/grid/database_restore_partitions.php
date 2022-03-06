#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug       = false;
$confirm     = true;
$force       = false;
$tables      = 'all';
$partitions  = 'all';
$rephosts    = false;
$hlimit      = false;
$replication = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-f':
	case '--force':
		$force = true;
		break;
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-y':
		$confirm = false;
		break;
	case '--tables':
		$tables = $value;
		break;
	case '--partitions':
		$partitions = $value;
		break;
	case '--rephosts':
	case '-r':
		$rephosts = $value;
		break;
	case '--hlimit':
		$hlimit = $value;
		break;
	case '-H':
	case '--help':
		display_help();
		exit;
	case '-V':
	case '-v':
	case '--version':
		display_version();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
		display_help();
		exit;
	}
}

print 'IBM Spectrum LSF RTM Partition Restoral Tool' . PHP_EOL . PHP_EOL;

if (db_binlog_enabled()) {
	if ($rephosts == false) {
		$rephosts = db_get_active_replicas();

		if (cacti_sizeof($rephosts)) {
			$rephosts = implode(',', $rephosts);
		} else {
			$rephosts = false;
		}

	}

	if ($rephosts === false) {
		print 'FATAL: When using MySQL/MariaDB Replication you must provide all replication'. PHP_EOL;
		print '       slaves as the restore tool will restore the backup to each slave independently' . PHP_EOL;
		print '       Please ensure that you specify all replication slaves using the' . PHP_EOL;
		print '       --rephosts=hostA,hostB,hostC,... option.  You need not include the' . PHP_EOL;
		print '       MASTER host as it will be restored automatically.  If you are using' . PHP_EOL;
		print '       MaxScale, you can pull the hostnames of the replicas by issuing the' . PHP_EOL;
		print '      \'maxctrl list servers\' command.' . PHP_EOL . PHP_EOL;
		exit(1);
	} else {
		$rephosts = explode(',', $rephosts);

		foreach($rephosts as $key => $host) {
			if (strpos($database_hostname, $host) !== false) {
				print "WARNING: Replication hosts option includes the master $host and will be ignored." . PHP_EOL;
				unset($rephosts[$key]);
			}
		}

		if (!cacti_sizeof($rephosts)) {
			print 'FATAL: The --rephosts variables must include include all replication slaves.' . PHP_EOL . PHP_EOL;
			exit(1);
		}

		print 'Restore will restore partitions to the following hosts.  Please ensure this is correct.' . PHP_EOL;
		foreach($rephosts as $host) {
			print 'Replication Host: ' . $host . PHP_EOL;
		}
		print PHP_EOL;
	}
} else {
	print 'NOTE: No Binary replication detected.' . PHP_EOL;
}

$stdin = fopen('php://stdin', 'r');
if ($confirm) {
	while (1) {
		print 'Are you sure you wish to Restore Partitioned tables from the database? [y/n] ';
		$result = trim(strtolower(fgets($stdin)));

		if ($result == 'y') {
			fclose($stdin);
			break;
		} else if ($result == 'n') {
			print PHP_EOL . 'Operation Canceled, Exiting!' . PHP_EOL;
			fclose($stdin);
			exit(1);
		} else {
			print 'You must enter either y or n.  Please try again' . PHP_EOL;
		}
	}
}

print PHP_EOL;

partition_log('NOTE: Restoring RTM Partitioned Tables!!', false, 'GRID');

grid_restore_cacti_db_partitions($rephosts, $force, $tables, $partitions, $hlimit);

function db_binlog_enabled() {
	$enabled = db_fetch_row('SHOW GLOBAL VARIABLES LIKE "log_bin"');

	if (cacti_sizeof($enabled)) {
		if (strtolower($enabled['Value']) == 'on' || $enabled['Value'] == 1) {
			return true;
		}
	}

	return false;
}

function db_get_active_replicas() {
	return array_rekey(
		db_fetch_assoc("SELECT SUBSTRING_INDEX(HOST, ':', 1) AS host
			FROM information_schema.processlist
			WHERE command = 'Binlog Dump'"),
		'host', 'host'
	);
}

function grid_get_partition_number($t) {
	$parts = explode('_', $t);
	$parts = array_reverse($parts);
	if (substr($parts[0],0,1) == 'v') {
		return str_replace('v', '', $parts[0]);
	} else {
		return '';
	}
}

function grid_get_table_name($t) {
	$parts = explode('_', $t);
	$parts = array_reverse($parts);
	array_shift($parts);
	$parts = array_reverse($parts);
	return implode('_', $parts);
}

function grid_restore_cacti_db_partitions($rephosts, $force = false, $tables = 'all', $partitions = 'all', $hlimit) {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $database_retries, $debug;
	global $database_port, $database_ssl, $database_ssl_key, $database_ssl_cert, $database_ssl_ca;
	global $config;

	include_once($config['base_path'] . '/lib/utility.php');
	include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

	$cnn = array();

	if (cacti_sizeof($rephosts)) {
		$replication = true;

		print "NOTE: Turning off binary logging on master for session." . PHP_EOL;

		foreach($rephosts as $host) {
			if ($hlimit !== false && array_search($host, $hlimit) === false) {
				continue;
			}

			$cnn[$host] = db_connect_real($host, $database_username, $database_password, $database_default, $database_type, $database_port, $database_retries,
				$database_ssl, $database_ssl_key, $database_ssl_cert, $database_ssl_ca);

			if (!is_object($cnn[$host])) {
				$error = db_error();

				print "FATAL: Unable to connect to replication host $host.  Error messages was '$error'" . PHP_EOL;

				exit(1);
			} else {
				// Disable binary logging and read-only
				print "NOTE: Turning off binary logging and enabling writes on slave $host." . PHP_EOL;

				db_execute('SET @@global.read_only=0', true, $cnn[$host]);
			}
		}
	} else {
		$replication = false;
	}

	if ((read_config_option('grid_backup_enable') == 'on') || ($force)) {
		print "NOTE: Backup is enabled!  Seeking out information on backup location." . PHP_EOL;

		$now = time();
		$day_of_week = date('w', $now);

		/* create the backup path */
		$backup_path = read_config_option('grid_backup_path') . '/partition_backups';

		/* check if directory exists */
		if (file_exists($backup_path) && is_readable($backup_path)) {
			print "NOTE: Backup path $backup_path is readable.  Continuing." . PHP_EOL;

			if ($config['cacti_server_os'] == 'win32') {
				$tmp_backup_dir = getenv('TEMP');
			} else {
				$tmp_backup_dir = '/tmp';
			}

			if ($tables != 'all' || $partitions != 'all') {
				if ($tables != 'all' && $partitions == 'all') {
					print "FATAL: When specifying a Table or Tables to restore, you must also provide a partition or partitions" . PHP_EOL;
					exit(1);
				} elseif ($tables == 'all' && $partitions != 'all') {
					print "FATAL: When specifying a Partition or Partitions to restore, you must also provide a Table or Tables" . PHP_EOL;
					exit(1);
				}

				$tables     = explode(',', $tables);
				$partitions = explode(',', $partitions);
				$newparts   = array();

				// Pre-process partitions
				foreach($tables as $table) {
					foreach($partitions as $partition) {
						if (strpos($partition, '-') !== false) {
							$parts = explode('-', $partition);
							if (cacti_sizeof($parts) == 2) {
								if ($parts[0] < $parts[1]) {
									$range = range($parts[0], $parts[1]);

									if (cacti_sizeof($range)) {
										foreach($range as $np) {
											$newparts[$np] = $np;
										}
									}
								} else {
									print "FATAL: Partition range format requires the first range element to be less than the first.  Can not continue." . PHP_EOL;
									exit(1);
								}
							} else {
								print "FATAL: Partition range format unexpected.  Can not continue." . PHP_EOL;
								exit(1);
							}
						} elseif (!is_numeric($partition)) {
							print "FATAL: Partition $partition is not numeric.  Can not continue." . PHP_EOL;
							exit(1);
						} else {
							$newparts[$partition] = $partition;
						}
					}
				}

				// Post-process partitions
				foreach($tables as $table) {
					foreach($newparts as $partition) {
						$partitioned_tables[] = array('table_name' => trim($table) . '_v' . ($partition));
					}
				}

			} else {
				/* obtain a list of tables to restore */
				$partitioned_tables = db_fetch_assoc("SELECT CONCAT(table_name,'_v',`partition`) AS table_name
					FROM grid_table_partitions
					ORDER BY table_name");
			}

			/* check for a filter */
			if ($tables != 'all' || $partitions != 'all') {
				if ($tables != 'all')     $tables = explode(',', $tables);
				if ($partitions != 'all') $partitions = explode(',', $partitions);
			}

			foreach($partitioned_tables as $table_data) {
				$t = $table_data['table_name'];
				$p = grid_get_partition_number($t);
				$k = grid_get_table_name($t);

				if (is_array($tables) || is_array($partitions)) {
					if (is_array($partitions) && array_search($p, $partitions) !== false) {
						$continue = true;
					} elseif ($partitions == 'all') {
						$continue = true;
					} elseif (is_valid_partition_table($k)) {
						$continue = true;
					} else {
						$continue = false;
					}

					if ($continue) {
						if (is_array($tables) && array_search($k, $tables) !== false) {
							$continue = true;
						} elseif ($tables == 'all') {
							$continue = true;
						} else {
							$continue = false;
						}
					}
				} else {
					$continue = true;
				}

				if (!$continue) {
					continue;
				}

				if ($config['cacti_server_os'] == 'win32') {
					$backup_file_tgz = $backup_path . '/' . $t . '.zip';
				} else {
					$backup_file_tgz = $backup_path . '/' . $t . '.tgz';
				}

				/* perform the restore */
				if (!is_readable($backup_file_tgz)) {
					partition_log("WARNING: Restore file '$backup_file_tgz' is not readable for partition '$t'", true, 'GRID');
				} elseif (!file_exists($backup_file_tgz)) {
					partition_log("WARNING: Restore file '$backup_file_tgz' does not exist partition '$t'", true, 'GRID');
				} elseif (!is_writable($tmp_backup_dir)) {
					partition_log("WARNING: Temp restore location '$tmp_backup_dir' is not writable for partition '$t'", true, 'GRID');
				} else {
					/* untar up backup file */
					if ($config['cacti_server_os'] == 'win32') {
						$zip_cmd = read_config_option('path_7z');
						$restore_command = cacti_escapeshellcmd($zip_cmd) . ' e -r '  . cacti_escapeshellarg($backup_file_tgz) . ' ' . $tmp_backup_dir;
					} else {
						$restore_command = "tar -zxvf $backup_file_tgz -C $tmp_backup_dir";
					}

					$result = exec($restore_command);

					if ($config['cacti_server_os'] == 'win32') {
						if (!substr_count($result, 'Everything is Ok')) {
							partition_log("ERROR: Un-Packing Restore File '$t' Failed!  Message is '$result'", true, 'GRID');
							continue;
						} else {
							partition_log("NOTE: Un-Packing Restore File '$t' Successful!", true, 'GRID');
						}
					} else {
						if ($result == "$t.sql") {
							partition_log("NOTE: Un-Packing Restore File '$t' Successful!", true, 'GRID');
						} else {
							partition_log("ERROR: Un-Packing Restore File '$t' Failed!  Message is '$result'", true, 'GRID');
							continue;
						}
					}

					if ($hlimit == false || array_search($database_hostname, $hlimit)) {
						partition_log("NOTE: Restoring Partitionted table '$t' to Main Server '" . $database_hostname . "'", true, 'GRID');
						$result = grid_restore_partitioned_table("$tmp_backup_dir/$t.sql", $t, $database_hostname, $replication);

						if ($result) {
							grid_register_partitioned_table($k, $p);
						}
					} else {
						partition_log("NOTE: Skipping Restoring Partitionted table '$t' to Main Server '" . $database_hostname . "' by hlimit", true, 'GRID');
					}

					foreach($cnn as $key => $cnn_id) {
						if ($hlimit !== false && array_search($key, $hlimit) === false) {
							partition_log("NOTE: Skipping Restoring Partitionted table '$t' to Replica Server '" . $key . "' by hlimit", true, 'GRID');
							continue;
						}

						partition_log("NOTE: Restoring Partitionted table '$t' to Replica Server '" . $key . "'", true, 'GRID');
						$result = grid_restore_partitioned_table("$tmp_backup_dir/$t.sql", $t, $key, $replication);

						if ($result) {
							grid_register_partitioned_table($k, $p);
						}
					}

					unlink($tmp_backup_dir . '/' . $t . '.sql');
				}
			}
		} else {
			print "ERROR: Partition backup directory does not exist or is not readable!" . PHP_EOL;
		}

		if ($replication) {
			foreach($rephosts as $host) {
				if ($hlimit !== false && array_search($host, $hlimit) === false) {
					continue;
				}

				// Enable binary logging and read-only
				print "NOTE: Re-enabling read only mode on slave $host." . PHP_EOL;

				db_execute('SET @@global.read_only=1', true, $cnn[$host]);
			}
		}
	} else {
		print "NOTE: Database Restore Disabled.  Use the Force Option to Override" . PHP_EOL;
	}
}

function grid_restore_partitioned_table($file_name, $table_name, $hostname, $replication) {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $database_retries, $debug;
	global $database_port, $database_ssl, $database_ssl_key, $database_ssl_cert, $database_ssl_ca;
	global $config;

	if ($config['cacti_server_os'] == 'win32') {
		$mysqldmp = "\"" . read_config_option('path_mysql_bin') . "\\mysql.exe\"";
	} else {
		$mysqldmp = 'mysql';
	}

	$restore_command = cacti_escapeshellcmd($mysqldmp) .
		' --host='     . cacti_escapeshellarg($hostname) .
		' --port='     . cacti_escapeshellarg($database_port)     .
		' --user='     . cacti_escapeshellarg($database_username) .
		' --password=' . cacti_escapeshellarg($database_password) .
		($replication ? ' --init-command="SET @@global.read_only=0;SET @@session.sql_log_bin=0"':'') .
		' '            . cacti_escapeshellarg($database_default)  .
		' < '          . cacti_escapeshellarg($file_name);

	$result = exec($restore_command);

	if ($result == '') {
		partition_log("NOTE: Partitioned Table Restore Successful for Table '$table_name' to Server '$hostname'!", true, 'GRID');

		return true;
	} else {
		partition_log("ERROR: Partitioned Table Restore Failed for Table '$table_name' to Server '$hostname'!  Message is '$result'", true, 'GRID');

		return false;
	}
}

function is_valid_partition_table($table) {
	$number = db_fetch_cell_prepared('SELECT COUNT(*)
		FROM grid_table_partitions
		WHERE table_name = ?',
		array($table));

	if ($number > 0) {
		return true;
	} else {
		return false;
	}
}

function grid_detect_partition_range_columns($table) {
	// Handle the normal tables
	switch($table) {
	case 'grid_job_daily_stats':
		$min_time = 'interval_start';
		$max_time = 'interval_end';

		break;
	case 'grid_jobs_finished':
	case 'grid_jobs_jobhosts_finished':
	case 'grid_jobs_reqhosts_finished':
	case 'grid_jobs_pendreasons_finished':
	case 'grid_jobs_sla_loaning_finished':
		$min_time = 'submit_time';
		$max_time = 'end_time';

		break;
	case 'grid_arrays_finished':
		$min_time = 'submit_time';
		$max_time = 'last_updated';

		break;
	case 'grid_jobs_rusage':
	case 'grid_jobs_host_rusage':
	case 'grid_jobs_gpu_rusage':
		$min_time = 'submit_time';
		$max_time = 'update_time';

		break;
	case 'grid_host_closure_events_finished':
		$min_time = 'event_time';
		$max_time = 'event_time';

		break;
	case 'grid_limits_usage':
		$min_time = 'last_updated';
		$max_time = 'last_updated';

		break;
	case 'lic_services_feature_history':
	case 'lic_services_feature_history_mapping':
		$min_time = 'tokens_released_date';
		$max_time = 'tokens_released_date';

		break;
	case 'lic_log_events':
		$min_time = 'event_time';
		$max_time = 'event_time';

		break;
	case 'lic_daily_stats':
		$min_time = 'interval_end';
		$max_time = 'interval_end';

		break;
	case 'disku_directory_totals_history':
	case 'disku_extension_totals_history':
	case 'disku_groups_totals_history':
	case 'disku_users_totals_history':
		$min_time = 'intervalEnd';
		$max_time = 'intervalEnd';

		break;
	default:
		$columns = db_fetch_assoc_prepared('SHOW COLUMNS IN ? WHERE type="timestamp"', array($table));

		if (cacti_sizeof($columns)) {
			foreach($columns as $c) {
				if (strpos(strtolower($c['Extra']), 'current_timestamp') !== false) {
					$min_time = $c['Field'];
					$max_time = $c['Field'];

					break;
				} elseif ($c['Field'] == 'last_updated') {
					$min_time = 'last_updated';
					$max_time = 'last_updated';

					break;
				} else {
					$min_time = 'last_updated';
					$max_time = 'last_updated';

					break;
				}
			}
		}
	}

	return array('min_time_field' => $min_time, 'max_time_field' => $max_time);
}

function grid_register_partitioned_table($table, $partition) {
	$times = grid_detect_partition_range_columns($table);

	if (!cacti_sizeof($times)) {
		cacti_log('FATAL: Partition Restore could not detect partition column', false, 'RESTORE');
		return false;
	}

	$new_table = $table . '_v' . $partition;

	$min_time_field = $times['min_time_field'];
	$max_time_field = $times['max_time_field'];

	if ($max_time_field != $min_time_field) {
		/* obtain the minimum abarrant end time */
		if ($min_time_field != 'submit_time') {
			$alt_min_time1 = db_fetch_cell_prepared("SELECT MIN($max_time_field)
				FROM $new_table
				WHERE (? <='1971-02-01')
				AND (? >'1971-02-01')",
				array($min_time_field, $max_time_field));

			$alt_min_time2 = db_fetch_cell_prepared("SELECT MIN($min_time_field)
				FROM $new_table
				WHERE (? > '1971-02-01')",
				array($min_time_field));
		} else {
			$alt_min_time1 = db_fetch_cell_prepared("SELECT MIN($max_time_field)
				FROM $new_table
				WHERE ?>'1971-02-01'",
				array($max_time_field));

			$alt_min_time2 = db_fetch_cell("SELECT MIN($min_time_field) FROM $new_table");
		}

		$max_time = db_fetch_cell("SELECT MAX($max_time_field) FROM $new_table");

		if ((strtotime($alt_min_time1) < strtotime($alt_min_time2)) &&
			(strtotime($alt_min_time1) > 87000) && ($alt_min_time1 != '')) {
			$min_time = $alt_min_time1;
		} else {
			$min_time = $alt_min_time2;
		}

		if (strtotime($min_time) < 87000) {
			cacti_log("ERROR: Min Time Field is 0 for '$new_table'", true, "GRID");
		}
	} else {
		if ($min_time_field != "submit_time")  {
			$min_time = db_fetch_cell_prepared("SELECT MIN($min_time_field)
				FROM $new_table
				WHERE ? > '1971-02-01'",
				array($min_time_field));
		} else {
			$min_time = db_fetch_cell("SELECT MIN($min_time_field) FROM $new_table");
		}

		$max_time = db_fetch_cell("SELECT MAX($max_time_field) FROM $new_table");
	}

	/* record statistics in the partitions table */
	db_execute_prepared('INSERT INTO grid_table_partitions
		(table_name, `partition`, min_time, max_time)
		VALUES (?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE min_time=VALUES(min_time), max_time=VALUES(max_time)',
		array($table, $partition, $min_time, $max_time));

	return true;
}

function partition_log($message, $stdout, $facility) {
	print trim($message) . PHP_EOL;
	cacti_log($message, false, $facility);
}

/*	display_version - displays the version of the function */
function display_version () {
	print 'RTM Partition Table Restoral Tool ' . read_config_option('grid_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . PHP_EOL . PHP_EOL;
}

/*	display_help - displays the usage of the function */
function display_help () {
	display_version();

	print 'Usage:' . PHP_EOL;
	print 'database_restore_partitions.php [ --tables=A,B,C ] [ --partitions=A,B1-B2,C ] [ --rephosts=A,B,C ] [-d | --debug] [-f | --force]' . PHP_EOL . PHP_EOL;

	print 'Options:' . PHP_EOL;
	print '--tables=A,B,C         - Comma delimited list of base tables to retore.' . PHP_EOL;
	print '--partitions=A,B1-B2,C - Comma delimited list of table partitions to restore.' . PHP_EOL;
	print '                         Ranges are supported when connected via a dash character.' . PHP_EOL;
	print '--rephosts=A,B,C       - Comma delimited list of replication hosts to restore to.' . PHP_EOL;
	print '--hlimit=A,B,C         - Limit the restore to only the following hosts.' . PHP_EOL;
	print '-y                     - Do not prompt for confirmation' . PHP_EOL . PHP_EOL;

	print '-d | --debug           - Display verbose output during execution.' . PHP_EOL;
	print '-f | --force           - Force the backup even though it is disabled from the UI.' . PHP_EOL . PHP_EOL;

	print 'Notes:' . PHP_EOL;
	print 'This utilitiy is a backup restore utility for RTM.  It will restore RTM\'s proprietary' . PHP_EOL;
	print 'partition data for tables that were archived for backup purposes.  If the utility detects' . PHP_EOL;
	print 'binary logging, indicating a MySQL/MariaDB cluster, then it will not proceed with a restore' . PHP_EOL;
	print 'until you provide a list of restoral hosts.  When it performs the restore, it will disable' . PHP_EOL;
	print 'binary logging and restore to each server individually starting with the primary server in' . PHP_EOL;
	print 'the case that the table needs to be recreated.' . PHP_EOL . PHP_EOL;

	print 'If you use the \'all\' option to restore tables and partitions, this utility will only' . PHP_EOL;
	print 'restore partitions RTM already knows about, that are already in the database schema.  If you wish' . PHP_EOL;
	print 'to restore a table that has been removed from the database schema, you must use both the' . PHP_EOL;
	print '--tables=A,B,C and --partitions=A,B,C options.  When you use these options, RTM will create' . PHP_EOL;
	print 'a list of partition tables to restore, look for it\'s backup file and both restore and register' . PHP_EOL;
	print 'it with RTM so that the RTM interface can utilize the table.' . PHP_EOL . PHP_EOL;
}
