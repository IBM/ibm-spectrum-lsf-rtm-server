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

$dir = dirname(__FILE__);
ini_set('max_execution_time', '0');

chdir($dir);

if (strpos($dir, 'grid') !== false) {
	chdir('../../');
}

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/lib/utility.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $database_default, $database_username, $database_password, $database_hostname, $database_port, $temp_dir;

$backup       = false;
$db_data_only = false;
$restore      = false;
$filename     = '';
$hostfile     = 'y';
$backup_path  = '';
$temp_dir	  = '';

if (cacti_sizeof($parms)) {
	/* setup defaults */

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

		switch ($arg) {
		case '--backup':
			$backup = true;
			break;
		case '--db_data_only':
			$db_data_only=true;
			break;
		case '--restore':
			$restore = true;
			break;
		case '--file':
			$filename = $value;
			break;
		case '--hostfile':
			$hostfile = strtolower($value);
			if ($hostfile == 'yes' || $hostfile == 'y') {
				$hostfile = 'y';
			} else if ($hostfile == 'no' || $hostfile == 'n') {
				$hostfile = 'n';
			} else {
				$hostfile = strtolower($value);
			}

			break;
		case '--backup_path':
			$backup_path = $value;
			break;
		case '--temp_dir':
			$temp_dir = $value;
			break;
		case '-v':
		case '-V':
		case '-h':
		case '-H':
		case '--help':
		case '--version':
			display_help();
			return 0;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}

	if ($temp_dir == '') {
		$temp_dir = '/tmp';
	}

	if (($backup && $restore) || ($backup && $db_data_only) || ($restore && $db_data_only)) {
		echo "Specify whether it is a backup,backup data only  or restore process\n\n";
		display_help();

		return 1;
	} else if ($backup && !$restore && !$db_data_only) {
		echo "INFO: Back UP option selected.\n\n";
	} else if (!$backup && $restore && !$db_data_only) {
		echo "INFO: Restore option selected.\n\n";
	} else {
		if ($db_data_only) {
			echo "INFO: Back UP DATA ONLY  option selected.\n\n";
		} else {
			echo "ERROR: No option selected. Exiting....\n\n";
			return 1;
		}
	}

	if ($backup) {
		if ($backup_path == '') {	//no backup path specified, we will put it to the default folder
			$backup_path = $_SERVER['PWD'];
		} else {
			if (!is_dir($backup_path)) {
				echo "FATAL: $backup_path is not a directory.\n\n";
				return 1;
			}

			if (!file_exists($backup_path)) {
				echo "FATAL: Directory does not exist.\n\n";
				return 1;
			}
		}

		backup_RTM($backup_path);
	}

	if($db_data_only) {
		if ($backup_path == '') {    //no backup path specified, we will put it to the default folder
			$backup_path = $_SERVER['PWD'];
		} else {
			if (!is_dir($backup_path)) {
				echo "FATAL: $backup_path is not a directory.\n\n";
				return 1;
			}

			if (!file_exists($backup_path)) {
				echo "FATAL: Directory does not exist.\n\n";
				return 1;
			}
		}

		backup_data($backup_path);
	}

	if ($restore) {
		if ($filename == '') {
			echo "FATAL: You must specify a tgz file.\n";
			echo "Eg. --file=/<path>/test.tgz\n\n";
			return 1;
		}

		if (!file_exists($filename)) {
			echo "FATAL: File does not exist.\n\n";
			return 1;
		}

		if (!strstr($filename, '.tgz')) {
			echo "FATAL: Not a tgz file\n\n";
			return 1;
		}

		if ($hostfile == 'y' || $hostfile == 'n') {
			// no need to do anything here.
		} else {
			echo "FATAL: INVALID value for hostfile option\n";
			return 1;
		}
		restore_RTM($filename, $hostfile);
	}
} else {
	echo "ERROR: No option selected.\n\n";
	display_help();
}

function backup_data($backup_path) {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $database_port;
	global $config, $temp_dir;
	$counter = 0;
	$dir_files = scandir($backup_path);

	foreach ($dir_files as $dir_file) {
		if ($dir_file == '.' && $dir_file == '..') {
			// do nothing.
		} else {
			if (strstr($dir_file, 'cacti_db_data_only_backup_')) {
				$counter ++;
			}
		}
	}

	$tmp_backup_dir = $temp_dir.'/cacti_backup';

	rmdirr($tmp_backup_dir);

	if (!mkdir($tmp_backup_dir, 0755)) {
		echo "FATAL: Unable to create temporary backup directory $tmp_backup_dir";
	    return 1;
    }

    $backup_file_tgz = $backup_path . '/cacti_db_data_only_backup_' . $counter . '.tgz';
	$backup_file_cacti_data = $tmp_backup_dir . '/cacti_db_data_only_backup.sql';
    $temp_tables = db_fetch_assoc('SHOW TABLES');
    $tables_to_backup = '';

	if (cacti_sizeof($temp_tables)) {
		foreach($temp_tables as $table) {
			switch ($table['Tables_in_' . $database_default]) {
			    case "grid_jobs":
			    case "grid_jobs_finished":
			    case "grid_jobs_reqhosts":
			    case "grid_jobs_reqhosts_finished":
			    case "grid_jobs_jobhosts":
			    case "grid_jobs_jobhosts_finished":
			    case "grid_jobs_pendreasons":
			    case "grid_jobs_pendreasons_finished":
			    case "grid_jobs_sla_loaning":
			    case "grid_jobs_sla_loaning_finished":
			    case "grid_jobs_rusage":
			    case "grid_jobs_host_rusage":
			    case "grid_jobs_gpu_rusage":
			    case "grid_job_interval_stats":
			    case "grid_jobs_memvio":
			    case "grid_arrays":
			    case "grid_arrays_finished":
			    case "grid_job_daily_stats":
			    case "grid_job_daily_stats_replay":
			    case "grid_job_daily_user_stats":
			    case "grid_job_daily_usergroup_stats":
				case 'grid_processes':
			    case "lic_daily_stats":
			    case "lic_daily_stats_traffic":
			    case "lic_flexlm_log":
			    case "lic_interval_stats":
			    case "lic_lum_events":
			    case "poller_output":
			    case "poller_output_boost":
			    case "syslog":
			    case "syslog_removed":
				case 'syslog_incoming':
			    case "lic_flexlm_servers_feature_details":
			    case "lic_flexlm_servers_feature_use":
			    case "disku_directory_totals":
			    case "disku_directory_totals_history":
			    case "disku_extension_totals":
			    case "disku_extension_totals_history":
			    case "disku_groups_totals":
			    case "disku_groups_totals_history":
			    case "disku_users_totals":
			    case "disku_users_totals_history":
			    case "grid_queues_shares":
			    case "lic_services_feature_history":
			    case "lic_daily_stats_today": //structure should not be ignored.
			    case "grid_heuristics":
			    case "grid_heuristics_percentiles":
			    case "grid_heuristics_user_history_today":
			    case "grid_heuristics_user_history_yesterday":
			    case "grid_heuristics_user_stats":
			    case "grid_jobs_pendhist_daily":
			    case "grid_jobs_pendhist_hourly":
			    case "grid_jobs_pendhist_yesterday":
			    case "disku_files_raw":
				case 'table_columns':
				case 'table_indexes ':
					break;
				default:
				    /* don't backup partition databases */
				    if (preg_match("/^grid_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
				        break;
				    }
				    /* don't backup partition databases */
				    if (preg_match("/^lic_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
				        break;
				    }
				    if (preg_match("/^disku_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
				        break;
				    }
				    if (preg_match("/^disku_files_raw_/", $table["Tables_in_" . $database_default])) {
				        break;
				    }
				    if (preg_match("/^lic_daily_stats_/", $table["Tables_in_" . $database_default])) {
				        $ignore_tables[] = $table["Tables_in_" . $database_default];
				        break;
				    }
				    if (preg_match("/^lic_interval_stats_/", $table["Tables_in_" . $database_default])) {
				        $ignore_tables[] = $table["Tables_in_" . $database_default];
				        break;
				    }
				    if (preg_match("/^poller_output_boost_arch_/", $table["Tables_in_" . $database_default])) {
				        break;
				    }
				    if (preg_match("/^grid_jobs_pendhist_hourly_/", $table["Tables_in_" . $database_default])) {
				        break;
				    }

				    if (strlen($tables_to_backup)) {
					   $tables_to_backup .= ' ' . $table['Tables_in_' . $database_default];
					} else {
					   $tables_to_backup .= $table['Tables_in_' . $database_default];
					}
			}
		}
	}

	/* perform the backup */
	if (is_writeable($backup_path)) {
		$backup_command = 'mysqldump' .
			' --lock-tables=false'              .
			' --host='     . cacti_escapeshellarg($database_hostname) .
			' --port='     . cacti_escapeshellarg($database_port) .
			' --user='     . cacti_escapeshellarg($database_username) .
			' --password=' . cacti_escapeshellarg($database_password) .
			' --no-create-info'                 .
			' '            . cacti_escapeshellarg($database_default)  .
			' '            . $tables_to_backup  .
			' > '          . cacti_escapeshellarg($backup_file_cacti_data);

		$result = exec($backup_command);

		if (strlen($result)) {
			echo "ERROR: Cacti Database Backup Failed.\n";
			echo "ERROR: $result\n";
			return 1;
		} else {
			echo "INFO: Cacti Database Backup Successfully\n";
		}

		// Tar up backup dir
		$backup_command = 'cd ' . dirname($tmp_backup_dir) .
			' && tar -czf ' . $backup_file_tgz . ' ' . basename($tmp_backup_dir);

		$result = exec($backup_command);
		if (strlen($result)) {
			echo "ERROR: Packing Backup Files Failed!!\n";
			echo "ERROR: $result\n\n";
			return 1;
		} else {
			echo "INFO: Packing Backup File Successfully!\n\n";
		}
	} else{
		echo "FATAL: Cacti Database Backup Failed! Backup Directory Not Writeable!!\n\n";
		return 1;
	}

	echo "INFO: Backup File Name is $backup_file_tgz\n\n";
	rmdirr($tmp_backup_dir);
}


function backup_RTM($backup_path) {
	global $database_type, $database_default, $database_hostname;
	global $database_username, $database_password, $database_port;
	global $config, $temp_dir;
	$counter = 0;

	$dir_files = scandir($backup_path);

	foreach ($dir_files as $dir_file) {
		if ($dir_file == '.' && $dir_file == '..') {
			// do nothing.
		} else {
			if (strstr($dir_file, 'cacti_db_backup_')) {
				$counter ++;
			}
		}
	}

	$tmp_backup_dir = $temp_dir.'/cacti_backup';

	rmdirr($tmp_backup_dir);
	if (!mkdir($tmp_backup_dir, 0755)) {
		echo "FATAL: Unable to create temporary backup directory $tmp_backup_dir";
		return 1;
	}

	$backup_file_tgz = $backup_path . '/cacti_db_backup_' . $counter . '.tgz';
	$backup_file_cacti = $tmp_backup_dir . '/cacti_db_backup.sql';
	$backup_file_cacti_struct = $tmp_backup_dir . '/cacti_db_struct_backup.sql';
	$backup_file_mysql = $tmp_backup_dir . '/mysql_db_backup.sql';

	$temp_tables = db_fetch_assoc('SHOW TABLES');
	$tables_to_backup = '';

	if (cacti_sizeof($temp_tables)) {
		foreach($temp_tables as $table) {
			switch ($table['Tables_in_' . $database_default]) {
			    case "grid_jobs":
			    case "grid_jobs_finished":
			    case "grid_jobs_reqhosts":
			    case "grid_jobs_reqhosts_finished":
			    case "grid_jobs_jobhosts":
			    case "grid_jobs_jobhosts_finished":
			    case "grid_jobs_pendreasons":
			    case "grid_jobs_pendreasons_finished":
			    case "grid_jobs_sla_loaning":
			    case "grid_jobs_sla_loaning_finished":
			    case "grid_jobs_rusage":
			    case "grid_jobs_host_rusage":
			    case "grid_jobs_gpu_rusage":
			    case "grid_job_interval_stats":
			    case "grid_jobs_memvio":
			    case "grid_arrays":
			    case "grid_arrays_finished":
			    case "grid_job_daily_stats":
			    case "grid_job_daily_stats_replay":
			    case "grid_job_daily_user_stats":
			    case "grid_job_daily_usergroup_stats":
				case 'grid_processes':
			    case "lic_daily_stats":
			    case "lic_daily_stats_traffic":
			    case "lic_flexlm_log":
			    case "lic_interval_stats":
			    case "lic_lum_events":
			    case "poller_output":
			    case "poller_output_boost":
			    case "syslog":
			    case "syslog_removed":
				case 'syslog_incoming':
			    case "lic_flexlm_servers_feature_details":
			    case "lic_flexlm_servers_feature_use":
			    case "disku_directory_totals":
			    case "disku_directory_totals_history":
			    case "disku_extension_totals":
			    case "disku_extension_totals_history":
			    case "disku_groups_totals":
			    case "disku_groups_totals_history":
			    case "disku_users_totals":
			    case "disku_users_totals_history":
			    case "grid_queues_shares":
			    case "lic_services_feature_history":
			    case "lic_daily_stats_today": //structure should not be ignored.
			    case "grid_heuristics":
			    case "grid_heuristics_percentiles":
			    case "grid_heuristics_user_history_today":
			    case "grid_heuristics_user_history_yesterday":
			    case "grid_heuristics_user_stats":
			    case "grid_jobs_pendhist_daily":
			    case "grid_jobs_pendhist_hourly":
			    case "grid_jobs_pendhist_yesterday":
			    case "disku_files_raw":
				case 'table_columns':
				case 'table_indexes':
			        break;
			    default:
			        /* don't backup partition databases */
			        if (preg_match("/^grid_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
			        /* don't backup partition databases */
			        if (preg_match("/^lic_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
			        if (preg_match("/^disku_\S+_v[0-9]/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
			        if (preg_match("/^disku_files_raw_/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
			        if (preg_match("/^lic_daily_stats_/", $table["Tables_in_" . $database_default])) {
			            $ignore_tables[] = $table["Tables_in_" . $database_default];
			            break;
			        }
			        if (preg_match("/^lic_interval_stats_/", $table["Tables_in_" . $database_default])) {
			            $ignore_tables[] = $table["Tables_in_" . $database_default];
			            break;
			        }
			        if (preg_match("/^poller_output_boost_arch_/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
			        if (preg_match("/^grid_jobs_pendhist_hourly_/", $table["Tables_in_" . $database_default])) {
			            break;
			        }
					if (strlen($tables_to_backup)) {
						$tables_to_backup .= ' ' . $table['Tables_in_' . $database_default];
					} else {
						$tables_to_backup .= $table['Tables_in_' . $database_default];
					}
			}
		}
	}

	/* perform the backup */
    if (is_writeable($backup_path)) {
		$backup_command = 'mysqldump' .
			' --lock-tables=false'              .
			' --host='     . cacti_escapeshellarg($database_hostname) .
			' --port='     . cacti_escapeshellarg($database_port) .
			' --user='     . cacti_escapeshellarg($database_username) .
			' --password=' . cacti_escapeshellarg($database_password) .
			' '            . cacti_escapeshellarg($database_default)  .
			' '            . $tables_to_backup  .
			' > '          . cacti_escapeshellarg($backup_file_cacti);
		$result = exec($backup_command);

		if (strlen($result)) {
			echo "ERROR: Cacti Database Backup Failed.\n";
			echo "ERROR: $result\n";
			return 1;
		} else {
			echo "INFO: Cacti Database Backup Successfully\n";
		}

		$backup_command = 'mysqldump' .
			' --lock-tables=false'              .
			' --host='     . cacti_escapeshellarg($database_hostname) .
			' --port='     . cacti_escapeshellarg($database_port) .
			' --user='     . cacti_escapeshellarg($database_username) .
			' --password=' . cacti_escapeshellarg($database_password) .
			' -d'          .
			' '            . cacti_escapeshellarg($database_default)  .
			' > '          . cacti_escapeshellarg($backup_file_cacti_struct);

		$result = exec($backup_command);

		if (strlen($result)) {
			echo "ERROR: Cacti Database Structure Backup Failed.\n";
			echo "ERROR: $result\n";
			return 1;
		} else {
			echo "INFO: Cacti Database Structure Backup Successfully\n";
		}

		// Backup /opt/rtm/etc
		$backup_command = 'cd ' . RTM_ROOT . '/etc && find ./ | cpio -mpdu ' . $tmp_backup_dir . '/rtm/etc';
		$result = exec($backup_command);
		if (strlen($result)) {
			echo 'ERROR: ' . RTM_ROOT . "/etc Backup Failed.\n";
			echo "ERROR: $result\n\n";
			return 1;
		} else {
			echo 'INFO: ' . RTM_ROOT . "/etc Backup Successfully.\n";
		}

		// Touch Version File
		$grid_version = read_config_option('grid_version', true);
		$result = exec("cd $tmp_backup_dir && touch " . $grid_version);
		if (strlen($result)) {
			echo "ERROR: Unable to stamp grid version in backup Dir";
			echo "ERROR: $result\n\n";
			return 1;
		} else {
			echo "INFO: Stamp grid version in backup dir is Successful!\n";
		}

		// Tar up backup dir
		$backup_command = 'cd ' . dirname($tmp_backup_dir) . ' && tar -czf ' . $backup_file_tgz . ' ' . basename($tmp_backup_dir);
		$result = exec($backup_command);
		if (strlen($result)) {
			echo "ERROR: Packing Backup Files Failed!!\n";
			echo "ERROR: $result\n\n";
			return 1;
		} else {
			echo "INFO: Packing Backup File Successfully!\n\n";
		}
	} else {
		echo "FATAL: Cacti Database Backup Failed! Backup Directory Not Writeable!!\n\n";
		return 1;
	}

	echo "INFO: Backup File Name is $backup_file_tgz\n\n";
	rmdirr($tmp_backup_dir);
}

function restore_RTM($filename, $hostfile) {
	global $config, $database_default, $database_username, $database_password, $database_hostname, $database_port, $temp_dir;

	$backupdir = 'cacti_backup';
	$path_info = pathinfo($filename);

	if ($path_info['dirname'] != $temp_dir) {
		$cmd = 'cd ' . $path_info['dirname'] . ' && cp -rf ' . $path_info['basename'] . ' '.$temp_dir;
		$result = exec($cmd);
		if (!strlen($result)) {
			echo "INFO: Copy file to ".$temp_dir."\n";
			$filename = $temp_dir.'/'.$path_info['basename'];
			$path_info = pathinfo($filename);
		} else {
			echo "FATAL: Unable to copy file to ".$temp_dir.".\n\n";
			return 1;
		}
	}

	chdir($temp_dir);

	//stop lsfpollerd
	echo "INFO: Stopping lsfpollerd NOW!!\n";
	exec(LSFPOLLERD_STOP, $output, $retval);

	$retval = 0;
	echo "INFO: Unpacking tgz file\n";
	$cmd = 'tar -xzf ' . $filename;
	exec($cmd, $output, $retval);

	if ($retval) {
		echo "FATAL: Unable to unpack file\n\n";
		start_polling();
		return ;
	}

	$grid_version = db_fetch_cell("SELECT value FROM settings WHERE name='grid_version'");

	echo $path_info['dirname'] . '/' . $backupdir . '/' . $grid_version . "\n";

	if (!file_exists($path_info['dirname'] . '/' . $backupdir . '/' . $grid_version)) {
		echo 'ERROR: Wrong Backup for RTM ' . $grid_version."\n";
		echo "ERROR: Unable to proceed. Exiting ...\n\n";
		start_polling();
		return 1;
	}

	// Restore database
	$retval = 0;
	unset($output);

	$cmd = 'cd ' . $path_info['dirname'] . '/' . $backupdir . ' && mysql' .
		' -u' . cacti_escapeshellarg($database_username) .
		' -h' . cacti_escapeshellarg($database_hostname) .
		' -P' . cacti_escapeshellarg($database_port) .
		' -p' . cacti_escapeshellarg($database_password) .
		' '   . $database_default . ' < cacti_db_backup.sql';

	exec($cmd, $output, $retval);
	if ($retval) {
		echo "ERROR: Unable to restore database.\n\n";
		start_polling();
		return ;
	}

	// Restore license file
	if (file_exists($path_info['dirname'] . '/' . $backupdir . '/rtm/etc/rtm.lic')) {
		$retval = 0;
		$cmd = 'cp -f ' . $path_info['dirname'] . '/' . $backupdir . '/rtm/etc/rtm.lic ' . RTM_ROOT . '/etc/';
		$result = exec($cmd);
		if (!strlen($result)) {
			echo "INFO: Copied license file successfully\n";
		} else {
			echo "ERROR: Unable to copy license file.\n\n";
			start_polling();
			return ;
		}
	}

	// Restore app key
	if (file_exists($path_info['dirname'] . '/' . $backupdir . '/rtm/etc/.appkey')) {
		$retval = 0;
		$cmd = 'cp -f ' . $path_info['dirname'] . '/' . $backupdir . '/rtm/etc/.appkey ' . RTM_ROOT . '/etc/';
		$result = exec($cmd);
		if (!strlen($result)) {
			echo "INFO : Copied app key successfully";
		} else {
			echo "ERROR: Unable to copy app key\n\n";
			start_polling();
			return ;
		}
	}

	$appkeystr = get_appkey();
	if (strlen($appkeystr) > 0) {
		set_config_option('app_key', $appkeystr);
	} else {
		echo "ERROR: Unable to restore appkey\n\n";
	}

	// Regenerate lsf.conf and /etc/hosts if needed
	$grid_clusters = db_fetch_assoc("SELECT * FROM grid_clusters");
	$advocate_port = read_config_option("advocate_port", True);
	$ch = curl_init();
	foreach ($grid_clusters as $cluster) {
		//regenerate lsf.conf
		$lsf_version = $cluster['lsf_version'];
		$dirname = $cluster['lsf_envdir'];
		if (strpos($dirname, "rtm/etc") === false) {
			continue;
		}

		$path_rtm_top=grid_get_path_rtm_top();
		$retval = 0;
		$cmd = "cd ".$path_info['dirname']."/".$backupdir." && \
				cp -rf rtm/etc/" . substr($cluster['lsf_envdir'], strlen("$path_rtm_top/rtm/etc/")) ." ".RTM_ROOT."/etc/.";
		exec($cmd, $output, $retval);
		if($retval) {
			echo "ERROR: Unable to copy lsf.conf file\n\n";
			start_polling();
			return;
		}

		if ($hostfile != 'n') {
			//regenerate /etc/hosts
			if ($cluster['ip'] != '') {
				$chosts = explode(' ', $cluster['lsf_master_hostname']);
				$cips = explode(' ', $cluster['ip']);
				for($i=0; $i<count($chosts); $i++) {
					$host = $chosts[$i];
					$ip = $cips[$i];
					curl_setopt($ch, CURLOPT_URL, 'https://127.0.0.1:' . $advocate_port . '/hostSettings/hosts');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 'false');
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_POST, 1);
					$line = $ip . ' ' . $host;
					curl_setopt($ch, CURLOPT_POSTFIELDS, 'op=add&line=' . $line);
					$out = curl_exec($ch);
					sleep(1);
				}
			}
		}
	}
	curl_close($ch);
	echo "INFO: Cleaing up Now.\n";
	rmdirr($path_info['dirname'] . '/' . $path_info['basename']);
	rmdirr($path_info['dirname'] . '/' . $backupdir);

	echo "INFO: Starting Lsfpoller Now.\n";
	start_polling();

	echo "INFO: Restore Complete!!\n\n";
}

function display_help() {
	echo 'RTM Restore database Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8').' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "This program allows you to backup/restore RTM database, rebuild cluster lsf.conf and the /etc/hosts file\n";
	echo "using the tgz file that was being backup previously by RTM.\n";
	echo "Usage:\n";
	echo "Backing Up RTM:\n";
	echo "	php grid_backup_restore_rtm.php --backup\n\n";
	echo "Backing Up RTM to specific directory\n";
	echo "	php grid_backup_restore_rtm.php --backup --backup_path=/path\n\n";
	echo "Backing Up RTM using specific temporary directory\n";
	echo "	php grid_backup_restore_rtm.php --backup --temp_dir=/path\n\n";
	echo "Backing Up RTM Data only:\n";
	echo "	php grid_backup_restore_rtm.php --db_data_only\n\n";
	echo "Backing Up RTM Data only to specific directory\n";
	echo "	php grid_backup_restore_rtm.php --db_data_only --backup_path=/path\n\n";
	echo "Restoring RTM:\n";
	echo "	php grid_backup_restore_rtm.php --restore --file=/path/filename.tgz\n\n";
	echo "Restoring RTM without rebuilding the /etc/hosts file\n";
	echo "	php grid_backup_restore_rtm.php --file=/path/filename.tgz --hostfile=n|N\n\n";
	echo "Options:\n";
	echo "	--backup	set this option to backup RTM\n";
	echo "	--restore	set this option to restore RTM\n";
	echo "	--backup_path	filepath to backup the tgz to. If undefined, will be save to the current working directory.\n";
	echo "	--temp_dir	temporary directory used in this script. If undefined, /tmp will be used.\n";
	echo "	--file		filepath and filename where the tgz resides in.\n";
	echo "	--hostfile	'n|N|No' if you do not wish to rebuild the /etc/hosts file\n";
	echo "			If you wish to rebuild the /etc/hosts file, leave out this option or put --hostfile=y|Y|Yes\n\n";
}

