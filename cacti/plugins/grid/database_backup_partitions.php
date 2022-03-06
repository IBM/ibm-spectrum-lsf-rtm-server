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

/* Start Initialization Section */
include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug   = false;
$confirm = true;
$force   = false;

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
	case '-h':
	case '-H':
	case '--help':
		display_help();
		exit;
	case '-v':
	case '-V':
	case '--version':
		display_version();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

print "IBM Spectrum LSF RTM Partition Backup Tool\n\n";

$stdin = fopen('php://stdin', 'r');
if ($confirm) {
	while (1) {
		print 'Are you sure you wish to backup partitioned tables from the database? [y/n] ';
		$result = trim(strtolower(fgets($stdin)));

		if ($result == 'y') {
			fclose($stdin);
			break;
		} else if ($result == 'n') {
			print "\nOperation Canceled, Exiting!\n";
			fclose($stdin);
			exit(1);
		} else {
			print "You must enter either y or n.  Please try again\n";
		}
	}
}

grid_debug("NOTE: Backing up RTM tables using custom partitioning!");

grid_backup_cacti_db_partition(false, $force);

function grid_backup_cacti_db_partition($poller = true, $force = false, $backup_path = '') {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $debug;
	global $database_port;
	global $config;

	include_once($config['base_path'] . '/lib/utility.php');
	include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

	if ((read_config_option('grid_backup_enable') == 'on') || ($force)) {
		$now = time();
		$day_of_week = date('w', $now);

		/* create the backup path */
		if (empty($backup_path)) {
			$backup_path = read_config_option('grid_backup_path') . '/partition_backups';
		}
		@mkdir($backup_path);

		/* check if directory exists */
		if (file_exists($backup_path) && is_writable($backup_path)) {
			if ($config['cacti_server_os'] == 'win32') {
				$tmp_backup_dir = getenv('TEMP');
			} else {
				$tmp_backup_dir = '/tmp';
			}

			/* obtain a list of tables to backup */
			$partitioned_tables = db_fetch_assoc("SHOW TABLES LIKE '%\_v___'");

			foreach($partitioned_tables as $t) {
				$keys = array_keys($t);
				$t = $t[$keys[0]];

				if ($config['cacti_server_os'] == 'win32') {
					$backup_file_tgz      = $backup_path . '/' . $t . '.zip';
				} else {
					$backup_file_tgz      = $backup_path . '/' . $t . '.tgz';
				}

				if (!file_exists($backup_file_tgz)) {
					/* perform the backup */
					if (is_writeable($backup_path)) {
						if ($config['cacti_server_os'] == 'win32') {
							$mysqldmp = "\"" . read_config_option('path_mysql_bin') . "\\mysqldump.exe\"";
						} else {
							$mysqldmp = 'mysqldump';
						}

						$start_return = mysql_dump_no_passwd_check(cacti_escapeshellarg($database_username), cacti_escapeshellarg($database_password));
						$backup_command = cacti_escapeshellcmd($mysqldmp) .
							' --lock-tables=false'              .
							' --host='     . cacti_escapeshellarg($database_hostname) .
							' --port='     . cacti_escapeshellarg($database_port)     .
							($start_return ? '' : ' --user='     . cacti_escapeshellarg($database_username) .  ' --password=' . cacti_escapeshellarg($database_password)) .
							' '            . cacti_escapeshellarg($database_default)  .
							' '            . $t .
							' > '          . cacti_escapeshellarg($tmp_backup_dir . '/' . $t . '.sql');

						$result = exec($backup_command);

						if (strlen($result)) {
							cacti_log("ERROR: RTM Partitioned Table Backup Failed!  Message is '$result'", true, "GRID");
						} else {
							grid_debug('NOTE: RTM Partitioned Table Backup Successful!');
						}

						/* Tar up backup dir */
						if ($config['cacti_server_os'] == 'win32') {
							$zip_cmd = read_config_option('path_7z');
							$backup_command = cacti_escapeshellcmd($zip_cmd) . ' a -r '  . cacti_escapeshellarg($backup_file_tgz) . ' ' . cacti_escapeshellarg($tmp_backup_dir . '/' . $t . '.sql');
						} else {
							$backup_command = 'tar -czf ' . cacti_escapeshellcmd($backup_file_tgz) . ' -C ' . cacti_escapeshellarg($tmp_backup_dir) . ' ' .$t . '.sql';
						}

						$result = exec($backup_command);

						if ($config['cacti_server_os'] == 'win32') {
							if (!substr_count($result, 'Everything is Ok')) {
								cacti_log("ERROR: Packing Backup Table File '$t' Failed!  Message is '$result'", true, 'GRID');
							} else {
								grid_debug("NOTE: Packing Backup Table File '$t' Successful!");
							}
						} else {
							if (strlen($result)) {
								cacti_log("ERROR: Packing Backup Table File '$t' Failed!  Message is '$result'", true, 'GRID');
							} else {
								grid_debug("NOTE: Packing Backup Table File '$t' Successful!");
							}
						}

						unlink($tmp_backup_dir . '/' . $t . '.sql');
					} else {
						cacti_log('ERROR: Cacti Database Backup Failed!  Backup Directory Not Writeable!', true, 'GRID');
					}
				} else {
					grid_debug("NOTE: Backup Files Aready Exists for '$t'");
				}
			}

			if (read_config_option('grid_backup_partitions') == 2) {
				if (is_writeable($backup_path)) {
					$contents = scandir($backup_path);

					if (cacti_sizeof($contents)) {
					foreach($contents as $file) {
						if (substr_count($file, '.tgz')) {
							$nfile = str_replace('.tgz', '', $file);

							$p = grid_get_partition_number($nfile);
							$k = grid_get_table_name($nfile);

							if (cacti_sizeof(db_fetch_row_prepared("SELECT * FROM grid_table_partitions WHERE table_name=? AND `partition`=?", array($k, $p)))) {
								/* don't remove the partition as the table exists */
							} else {
								cacti_log("NOTE: Removing old Database Partition Backup '" . $backup_path . "/" . $file . "'", true, 'GRID');
								unlink($backup_path . '/' . $file);
							}
						}
					}
					}
				}
			}
		} else {
			print "ERROR: Unable to Write to Backup Location or Backup Location Does Not Exist!\n";
		}
	} else {
		print "NOTE: Database Backup Disabled.  Use the Force Option to Override\n";
	}
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

/*	display_version - displays the version of the function */
function display_version () {
	global $config;

	print 'RTM Partition Table Backup Tool ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	display_version();

	print "Usage:\n";
	print "database_backup_partitions.php [-d | --debug] [-f | --force] [-y] [-H | --help] [-V | --version]\n\n";
	print "-d | --debug   - Display verbose output during execution\n";
	print "-f | --force   - Force the backup even though it is disabled from the UI\n";
	print "-y             - Do not prompt for confirmation\n";
	print "-V | --version - Display version message\n";
	print "-H | --help    - Display this help message\n";
}

