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
include_once($config['base_path'] . '/lib/rtm_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug        = false;
$system_type  = '';
$grid_version = 0;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
		case '--force-ver':
			echo "NOTE: Upgrading from version '$value'\n";
			$grid_version = $value - .001;
			break;
		case '-v':
		case '-V':
		case '--version':
		case '-h':
		case '-H':
		case '--help':
			display_help();
			exit;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			exit;
	}
}

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

cacti_log('NOTE: Running background jobs tables upgrade', true, 'UPGRADE');

if (detect_and_correct_running_processes('0', 'DBUPGRADE', 9999) == true ) {
	if ($grid_version == 0) {
		$grid_version = db_fetch_cell("SELECT value FROM settings WHERE name='grid_version'");
	}

	if (!isset($grid_version)) {
		cacti_log('ERROR: Unable to identify RTM Version.', true, 'UPGRADE');
		exit;
	}

	$bg_version = db_fetch_cell("SELECT value FROM settings WHERE name='grid_db_version'");

	// 2.1 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '2.1') < 0 ) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_2_1();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 2.1.1 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '2.1.1') < 0 ) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_2_1_1();

		cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
		set_config_option('grid_db_version', '2.1.1');

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}

		cacti_log('NOTE: Set RTM to normal mode', true, 'UPGRADE');
	}

	// 2.1.1 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '2.1.2') < 0 ) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_2_1_2();

		cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
		set_config_option('grid_db_version', '2.1.2');

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}

		cacti_log('NOTE: Set RTM to normal mode', true, 'UPGRADE');
	}

	// 8.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '8.0') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_8_0();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 8.0.1 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '8.0.1') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_8_0_1();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 8.0.2 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '8.0.2') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_8_0_2();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 8.3 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '8.3') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_8_3();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 9.1.0.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '9.1.0.0') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_9_1();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 9.1.2.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '9.1.2.0') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_9_1_2();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 9.1.3.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '9.1.3.0') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_9_1_3();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	// 9.1.4.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '9.1.4.0') < 0) {
		cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
		set_db_upgrade_state(true);

		$out = upgrade_job_tables_9_1_4();

		if ($out == 0) {
			set_db_upgrade_state(false);
			cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
		} else {
			cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
			remove_process_entry('0', 'DBUPGRADE');
			exit(1);
		}
	}

	//10.1.0.0 upgrade
	if (!isset($bg_version) || grid_version_compare($bg_version, '10.1.0.0') < 0) {
        cacti_log('NOTE: set RTM to DB upgrade mode.', true, 'UPGRADE');
        set_db_upgrade_state(true);

        $out = upgrade_job_tables_10_1_0();

        if ($out == 0) {
            set_db_upgrade_state(false);
            cacti_log('NOTE: Job tables conversion successful, set RTM to normal mode', true, 'UPGRADE');
        } else {
            cacti_log("ERROR: Jobs table upgrade error: code($out). RTM remained in DB upgrade mode. Please check database tables.", true, 'UPGRADE');
            remove_process_entry('0', 'DBUPGRADE');
            exit(1);
        }
    }

	remove_process_entry('0', 'DBUPGRADE');
} else {
	cacti_log('NOTE: Another upgrade process is running. quitting...', true, 'UPGRADE');
}

cacti_log('NOTE: End of script.', true, 'UPGRADE');
exit(0);

function upgrade_job_tables_10_1_0() {
	cacti_log("NOTE: upgrading DB Job Tables to v10.1.0.0", true, 'UPGRADE');
	cacti_log("NOTE: updating grid_db_version.", true, 'UPGRADE');
	set_config_option('grid_db_version', '10.1.0.0');

    return 0;
}

function upgrade_job_tables_9_1_4() {
	cacti_log('NOTE: upgrading DB Job Tables to v9.1.4.0', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '9.1.4.0');

	return 0;
}
function upgrade_job_tables_9_1_3() {
	cacti_log('NOTE: upgrading DB Job Tables to v9.1.3.0', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '9.1.3.0');

	return 0;
}

function upgrade_job_tables_9_1_2() {
	cacti_log('NOTE: upgrading DB Job Tables to v9.1.2.0', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '9.1.2.0');

	return 0;
}

function upgrade_job_tables_9_1() {
	cacti_log('NOTE: upgrading DB Job Tables to v9.1.0.0', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '9.1.0.0');

	return 0;
}

function upgrade_job_tables_8_3() {
	cacti_log('NOTE: upgrading DB Job Tables to v8.3', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '8.3');

	return 0;
}

function upgrade_job_tables_8_0_2() {
	cacti_log('NOTE: upgrading DB Job Tables to v8.0.2', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '8.0.2');

	return 0;
}

function upgrade_job_tables_8_0_1() {
	cacti_log('NOTE: upgrading DB Job Tables to v8.0.1', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '8.0.1');

	return 0;
}

function upgrade_job_tables_8_0() {
	cacti_log('NOTE: upgrading DB Job Tables to v8.0', true, 'UPGRADE');
	cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
	set_config_option('grid_db_version', '8.0');

	return 0;
}

function upgrade_job_tables_2_1_2() {
	include_once(dirname(__FILE__) . '/../../plugins/grid/lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../lib/functions.php');

	cacti_log('NOTE: upgrading DB Job Tables to v2.1.2', true, 'UPGRADE');
	$failure_count = 0;
	$table_suffix = 'pre212';

	db_execute('RENAME TABLE `grid_arrays` to `grid_arrays_pre212`;');
	db_execute('CREATE TABLE `grid_arrays` LIKE `grid_arrays_pre212`;');

	$create_sql_finished = 'CREATE TABLE IF NOT EXISTS `grid_arrays_finished` LIKE `grid_arrays`';
	$drop_sql_arrays = "DROP TABLE IF EXISTS grid_arrays_$table_suffix";

	/* Copy unfinished job arrays into finished table */
	$last_update = date('Y-m-d H:i:s', time()-7200);
	$copy_sql_live_arrays = "
		REPLACE INTO grid_arrays
		SELECT * FROM grid_arrays_$table_suffix
		WHERE stat!='1' OR last_updated>='$last_update'";
	cacti_log('NOTE: Copy back unfinished job arrays.', true, 'UPGRADE');
	db_execute($copy_sql_live_arrays);

	/* Copy finished job arrays into finished table */
	$copy_sql_arrays = "
		REPLACE INTO grid_arrays_finished
		SELECT * FROM grid_arrays_$table_suffix
		WHERE stat='1' AND last_updated<'$last_update'";

	cacti_log('NOTE: Creating new job arrays finished table.', true, 'UPGRADE');
	db_execute($create_sql_finished);

	cacti_log('NOTE: Copying existing job arrays entries into new table.', true, 'UPGRADE');
	//print $copy_sql_arrays ."\n\n";
	db_execute($copy_sql_arrays);

	$oldtbl_cnt_jobarrays = db_fetch_cell("SELECT COUNT(*) FROM grid_arrays_$table_suffix
											WHERE stat='1' AND last_updated<'$last_update'");
	$newtbl_cnt_jobarrays = db_fetch_cell('SELECT COUNT(*) FROM grid_arrays_finished');

	if (($newtbl_cnt_jobarrays >= $oldtbl_cnt_jobarrays)) {
		cacti_log('NOTE: Job Arrays table copied successfully, dropping old table', true, 'UPGRADE');
		db_execute($drop_sql_arrays);
	} else {
		cacti_log("ERROR: Job Arrays tables not copied completely, old tables are grid_arrays_$table_suffix.", true, 'UPGRADE');
		cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
		$failure_count++;
	}

	if (read_config_option('grid_partitioning_enable') != '') {
		$partitions = array_rekey(
			db_fetch_assoc("SELECT table_name, `partition`
				FROM grid_table_partitions
				WHERE table_name='grid_arrays'"),
			'partition', 'table_name'
		);

		foreach ($partitions as $partition=>$table_name) {	// taking care of the partition-ed tables here
			$table_partition = $table_name . '_v' . $partition;
			$finished_table_partition = $table_name . '_finished_v' . $partition;
			$table_finished_name = $table_name . '_finished';

			cacti_log("NOTE: Renameing $table_partition table to $finished_table_partition", true, 'UPGRADE');
			db_execute("RENAME TABLE `$table_partition` to `$finished_table_partition`;");

			cacti_log("NOTE: Rename table_name in grid_table_partitions from '$table_partition' to $table_finished_name", true, 'UPGRADE');
			db_execute("UPDATE grid_table_partitions SET table_name='$table_finished_name' WHERE table_name='$table_name' AND `partition`='$partition'");

			cacti_log("NOTE: Tranferring of information successful for table($table_partition)", true, 'UPGRADE');
		}

		cacti_log('NOTE: Job Arrays partitions table renamed successfully.', true, 'UPGRADE');
	}

	if ($failure_count > 0) {
		return(1);
	}

	return 0;
}

function upgrade_job_tables_2_1_1() {
	include_once(dirname(__FILE__) . '/../../plugins/grid/lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../lib/functions.php');

	cacti_log('NOTE: upgrading DB Job Tables to v2.1.1', true, 'UPGRADE');
	$failure_count = 0;
	$table_suffix = 'pre211';

	$tables = array('grid_jobs_jobhosts', 'grid_jobs_reqhosts');

	$drop_sql_jobhosts = "DROP TABLE IF EXISTS grid_jobs_jobhosts_$table_suffix";
	$drop_sql_reqhosts = "DROP TABLE IF EXISTS grid_jobs_reqhosts_$table_suffix";

	$create_sql_jobhosts = 'CREATE TABLE IF NOT EXISTS `grid_jobs_jobhosts_finished` LIKE `grid_jobs_jobhosts`';
	$create_sql_reqhosts = 'CREATE TABLE IF NOT EXISTS `grid_jobs_reqhosts_finished` LIKE `grid_jobs_reqhosts`';

	if (read_config_option('grid_partitioning_enable') == '') {
		/* Copy finished job's job hosts into finished table */
		$copy_sql_jobhosts = "
			REPLACE INTO
				grid_jobs_jobhosts_finished
			SELECT
				grid_jobs_jobhosts_$table_suffix.jobid AS jobid,
				grid_jobs_jobhosts_$table_suffix.indexid AS indexid,
				grid_jobs_jobhosts_$table_suffix.clusterid AS clusterid,
				grid_jobs_jobhosts_$table_suffix.exec_host AS exec_host,
				grid_jobs_jobhosts_$table_suffix.submit_time AS submit_time,
				grid_jobs_jobhosts_$table_suffix.processes AS processes
			FROM
				grid_jobs_finished
			INNER JOIN
				grid_jobs_jobhosts_$table_suffix
			USING
				(jobid, indexid, clusterid, submit_time)";

		/* Copy finished job's req hosts into finished table */
		$copy_sql_reqhosts = "
			REPLACE INTO
				grid_jobs_reqhosts_finished
			SELECT
				grid_jobs_reqhosts_$table_suffix.jobid AS jobid,
				grid_jobs_reqhosts_$table_suffix.indexid AS indexid,
				grid_jobs_reqhosts_$table_suffix.clusterid AS clusterid,
				grid_jobs_reqhosts_$table_suffix.host AS hosts,
				grid_jobs_reqhosts_$table_suffix.submit_time AS submit_time
			FROM
				grid_jobs_finished
			INNER JOIN
				grid_jobs_reqhosts_$table_suffix
			USING
				(jobid, indexid, clusterid, submit_time)";

		cacti_log('NOTE: Creating new job host table.', true, 'UPGRADE');
		db_execute($create_sql_jobhosts);
		db_execute($create_sql_reqhosts);
		cacti_log('NOTE: Copying existing job host entries into new table.', true, 'UPGRADE');
		//print $copy_sql_jobhosts ."\n\n";
		db_execute($copy_sql_jobhosts);
		//print $copy_sql_reqhosts . "\n\n";
		db_execute($copy_sql_reqhosts);

		$oldtbl_cnt_jobhosts = db_fetch_cell("SELECT COUNT(*) FROM grid_jobs_finished
						INNER JOIN grid_jobs_jobhosts_$table_suffix
						USING (jobid, indexid, clusterid, submit_time)");
		$oldtbl_cnt_reqhosts = db_fetch_cell("SELECT COUNT(*) FROM grid_jobs_finished
						INNER JOIN grid_jobs_reqhosts_$table_suffix
						USING (jobid, indexid, clusterid, submit_time)");

		$newtbl_cnt_jobhosts = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs_jobhosts_finished');
		$newtbl_cnt_reqhosts = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs_reqhosts_finished');

		if (($newtbl_cnt_jobhosts >= $oldtbl_cnt_jobhosts) && ($newtbl_cnt_reqhosts >= $oldtbl_cnt_reqhosts)) {
			cacti_log('NOTE: Job table copied successfully, dropping old table', true, 'UPGRADE');

			db_execute($drop_sql_jobhosts);
			db_execute($drop_sql_reqhosts);
		} else {
			cacti_log("ERROR: Job tables not copied completely, old tables are grid_jobs_jobhosts_$table_suffix and grid_jobs_reqhosts_$table_suffix.", true, 'UPGRADE');

			cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');

			return(1);
		}
		return(0);
	} else {
		$partitions = array_rekey(
			db_fetch_assoc("SELECT table_name, `partition`
				FROM grid_table_partitions
				WHERE table_name='grid_jobs_finished'"),
			'partition', 'table_name'
		);

		foreach ($tables as $table) {
			if ($table == 'grid_jobs_jobhosts') {
				$duplicate = ' ON DUPLICATE KEY UPDATE exec_host=VALUES(exec_host), processes=VALUES(processes)';
			} else {
				$duplicate = ' ON DUPLICATE KEY UPDATE host=values(host)';
			}

			foreach ($partitions as $partition=>$table_name) {
				// taking care of the partition-ed tables here
				$table_partition = $table . '_finished_v' . $partition;
				$jobs_partition = $table_name . '_v' . $partition;
				$pre211_table = $table . '_' . $table_suffix;

				$size = db_fetch_assoc('SELECT ' . $pre211_table . ".*
					FROM $pre211_table
					INNER JOIN $jobs_partition
					ON ($jobs_partition.jobid=$pre211_table.jobid
					AND $jobs_partition.clusterid=$pre211_table.clusterid
					AND $jobs_partition.indexid=$pre211_table.indexid
					AND $jobs_partition.submit_time=$pre_211table.submit_time)");

				if (cacti_sizeof($size) > 0) {
					// proceed
				} else {
					$continue;
				}

				db_execute('CREATE TABLE IF NOT EXISTS ' . $table_partition . ' LIKE ' . $table);

				db_execute("INSERT INTO $table_partition
					SELECT " . $pre211_table . ".*
					FROM $pre211_table
					INNER JOIN $jobs_partition
					ON ($jobs_partition.jobid=$pre211_table.jobid
					AND $jobs_partition.clusterid=$pre211_table.clusterid
					AND $jobs_partition.indexid=$pre211_table.indexid
					AND $jobs_partition.submit_time=$pre211_table.submit_time) $duplicate");

				$time = db_fetch_row("SELECT MIN(submit_time) AS min_submit_time,
					MAX(submit_time) AS max_submit_time
					FROM $table_partition");

				db_execute("INSERT INTO grid_table_partitions VALUES
					('".$partition."', '".$table."_finished', '".$time['min_submit_time']."', '".$time['max_submit_time']."')");

				$oldtbl_cnt = db_fetch_cell("SELECT COUNT(*)
					FROM $pre211_table
					INNER JOIN $jobs_partition
					ON ($jobs_partition.jobid=$pre211_table.jobid
					AND $jobs_partition.clusterid=$pre211_table.clusterid
					AND $jobs_partition.indexid=$pre211_table.indexid
					AND $jobs_partition.submit_time=$pre211_table.submit_time)");

				$newtbl_cnt = db_fetch_cell("SELECT COUNT(*) FROM $table_partition");

				if ($oldtbl_cnt != $newtbl_cnt) {
					cacti_log("ERROR Job Table($table_partition) not copied completely from $jobs_partition", true, 'UPGRADE');
					$failure_count ++;
				} else {
					cacti_log("NOTE: Tranferring of information successful for table($table_partition)", true, 'UPGRADE');
				}
			}

			// Need to take care of the finished table here.
			db_execute('INSERT INTO ' . $table . '_finished
				SELECT ' . $pre211_table . ".*
				FROM $pre211_table
				INNER JOIN grid_jobs_finished
				ON (grid_jobs_finished.jobid=$pre211_table.jobid
				AND grid_jobs_finished.clusterid=$pre211_table.clusterid
				AND grid_jobs_finished.indexid=$pre211_table.indexid
				AND grid_jobs_finished.submit_time=$pre211_table.submit_time) $duplicate");
		}

		if ($failure_count == 0 ) {
			cacti_log('NOTE: Job table copied successfully, dropping old table', true, 'UPGRADE');
			db_execute($drop_sql_jobhosts);
			db_execute($drop_sql_reqhosts);
		} else {
			cacti_log('NOTE: Job table copied operation incomplete.', true, 'UPGRADE');
		}
		return 0;
	}
}

function upgrade_job_tables_2_1() {
	cacti_log('NOTE: upgrading DB Job Tables to v2.1', true, 'UPGRADE');

	$table_suffix = 'pre21';
	$rename_sql = 'RENAME TABLE grid_jobs_finished TO grid_jobs_finished_' . $table_suffix;
	$create_sql = 'CREATE TABLE `grid_jobs_finished` LIKE `grid_jobs`;';
	$copy_sql = "REPLACE INTO grid_jobs_finished (`jobid`, `indexid`, `clusterid`, `options`, `options2`, `options3`, `user`, `stat`, `prev_stat`,
		`stat_changes`, `flapping_logged`, `exitStatus`, `pendReasons`, `queue`, `nice`, `from_host`, `exec_host`, `execUid`, `loginShell`, `execHome`,
		 `execCwd`, `execUsername`, `mailUser`, `jobname`, `jobPriority`, `jobPid`, `userPriority`, `projectName`, `parentGroup`, `sla`, `jobGroup`,
		 `licenseProject`, `command`, `inFile`, `outFile`, `errFile`, `preExecCmd`, `res_requirements`, `dependCond`, `mem_used`, `swap_used`, `max_memory`,
		 `max_swap`, `cpu_used`, `utime`, `stime`, `efficiency`, `effic_logged`, `numPIDS`, `numPGIDS`, `numThreads`, `pid_alarm_logged`, `num_nodes`,
		 `num_cpus`, `maxNumProcessors`, `submit_time`, `reserveTime`, `predictedStartTime`, `start_time`, `end_time`, `beginTime`, `termTime`, `pend_time`,
		 `psusp_time`, `run_time`, `ususp_time`, `ssusp_time`, `unkwn_time`, `hostSpec`, `rlimit_max_cpu`, `rlimit_max_wallt`, `rlimit_max_swap`,
		 `rlimit_max_fsize`, `rlimit_max_data`, `rlimit_max_stack`, `rlimit_max_core`, `rlimit_max_rss`, `job_start_logged`, `job_end_logged`, `job_scan_logged`,
		 `userGroup`, `last_updated`) SELECT `jobid`, `indexid`, `clusterid`, `options`, `options2`, `options3`, `user`, `stat`, `prev_stat`, `stat_changes`,
		 `flapping_logged`, `exitStatus`, `pendReasons`, `queue`, `nice`, `from_host`, `exec_host`, `execUid`, `loginShell`, `execHome`, `execCwd`, `execUsername`,
		 `mailUser`, `jobname`, `jobPriority`, `jobPid`, `userPriority`, `projectName`, `parentGroup`, `sla`, `jobGroup`, `licenseProject`, `command`, `inFile`,
		 `outFile`, `errFile`, `preExecCmd`, `res_requirements`, `dependCond`, `mem_used`, `swap_used`, `max_memory`, `max_swap`, `cpu_used`, `utime`, `stime`,
		 `efficiency`, `effic_logged`, `numPIDS`, `numPGIDS`, `numThreads`, `pid_alarm_logged`, `num_nodes`, `num_cpus`, `maxNumProcessors`, `submit_time`,
		 `reserveTime`, `predictedStartTime`, `start_time`, `end_time`, `beginTime`, `termTime`, `pend_time`, `psusp_time`, `run_time`, `ususp_time`, `ssusp_time`,
		 `unkwn_time`, `hostSpec`, `rlimit_max_cpu`, `rlimit_max_wallt`, `rlimit_max_swap`, `rlimit_max_fsize`, `rlimit_max_data`, `rlimit_max_stack`, `rlimit_max_core`,
		 `rlimit_max_rss`, `job_start_logged`, `job_end_logged`, `job_scan_logged`, `userGroup`, `last_updated` FROM grid_jobs_finished_".$table_suffix;

	$drop_sql = "DROP TABLE IF EXISTS grid_jobs_finished_$table_suffix";

	cacti_log('NOTE: Rename old job table.', true, 'UPGRADE');
	db_execute($rename_sql);
	cacti_log('NOTE: Creating new job table.', true, 'UPGRADE');
	db_execute($create_sql);
	cacti_log('NOTE: Copying existing job entries into new table.', true, 'UPGRADE');
	db_execute($copy_sql);
	$oldtbl_cnt = db_fetch_cell("SELECT COUNT(*) FROM grid_jobs_finished_$table_suffix");
	$newtbl_cnt = db_fetch_cell('SELECT COUNT(*) FROM grid_jobs_finished');

	if ($newtbl_cnt >= $oldtbl_cnt) {
		cacti_log('NOTE: Job table copied successfully, dropping old table', true, 'UPGRADE');
		db_execute($drop_sql);
		cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
		set_config_option('grid_db_version', '2.1');
	} else {
		cacti_log("ERROR: Job tables not copied completely, old table is grid_jobs_finished_$table_suffix.", true, 'UPGRADE');
		cacti_log('NOTE: updating grid_db_version.', true, 'UPGRADE');
		set_config_option('grid_db_version', '2.1');

		return(1);
	}

	return(0);
}

function set_db_upgrade_state($state) {
	if ($state == true) {
		set_config_option('grid_db_upgrade', '1');
	} else {
		set_config_option('grid_db_upgrade', '1');
	}
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	echo "\nIBM Spectrum LSF RTM Background Jobs Tables Upgrader " . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "Usage:\n";
	echo "database_jobs_upgrade.php [--type=[standard|large]] [--force-ver=VER] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	echo "--force-ver      - Forces the upgrade to begin at the specified version\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n\n";
}

