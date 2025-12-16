#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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
include_once($config['base_path'] . '/lib/rrd.php');
include_once($config['base_path'] . '/plugins/grid/setup.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

/* take the start time to log performance data */
$start = microtime(true);

/* let this script run for 200 minutes */
ini_set('max_execution_time', 12000);
set_time_limit(12000);

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$force           = false;
$debug           = false;
$delete          = false;
$forcerun        = false;
$partitioning    = false;
$scan_start_time = 0;
$scan_end_time   = 0;
$optimize        = false;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-f':
	case '--force':
		$force = true;
		break;
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '--start':
		$scan_start_time = strtotime($value);
		break;
	case '--end':
		$scan_end_time = strtotime($value);
		break;
	case '--optimize':
		$optimize = true;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

if (($scan_end_time == 0) || ($scan_end_time == 0)) {
	print "ERROR: You must specify both a start date and an end date\n\n";
	display_help();
	exit(-1);
}

$grid_daily_data_retention=read_config_option('grid_daily_data_retention');

if ($scan_start_time > $scan_end_time || $scan_end_time > time() ) {
	print 'ERROR: You must specify both a valid start date and an end date, today is ' . date('Y-m-d ', time()). "\n";
	exit(-1);
}

if ($scan_start_time < strtotime('-' . $grid_daily_data_retention)) {
	print "Note: Because the max daily statistics retention period is '$grid_daily_data_retention', you must extend it to fit the --start '$scan_start_time'\n\n";
	print "Do you want to extend the max daily statistics retention period? [Y/N]\n\n";
	$line = fgets(STDIN);
	$line = trim($line);
	if ("Y" == $line || "y" == $line) {
		$found = false;
		$new_grid_daily_data_retention = "";
		$longest = $grid_daily_data_retention;
		foreach($grid_summary_data_retention as $key => $value) {
			$longest = $key;
			if ($scan_start_time >= strtotime('-' . $key)) {
				$found = true;
				$new_grid_daily_data_retention = $key;
				break;
			}
		}
		if ($found) {
			set_config_option('grid_daily_data_retention', $new_grid_daily_data_retention);
			print "Note: The daily statistics retention period is set to '$new_grid_daily_data_retention'\n";
		} else {
			print "ERROR: Current start date exceeds the longest daily statistics retention period: '$longest'. You must specify a valid start date\n";
			exit(-1);
		}
	} else {
		print "ERROR: You must specify a valid start date\n";
		exit(-1);
	}
}

if ($scan_end_time >= strtotime(date('Y-m-d ', time()))) {
	$base_time_value = date('H:i:00', strtotime(read_config_option('grid_db_maint_time')));
	$yesterday_maint_time = strtotime(date('Y-m-d ', time()- 86400). $base_time_value);
	$today_maint_time = strtotime(date('Y-m-d ', time()) . $base_time_value);

	if (time() < $today_maint_time) {
		$scan_end_time = $yesterday_maint_time;

		print 'Note: Cannot replay for today, because the maint time of today is not reached, so the end date move to yesterday '. date('Y-m-d ', time()- $oneday). "\n";
	}
}

if (detect_and_correct_running_processes(0, 'GRIDREPLAYUH', 36000)) {
	grid_debug('About to start RTM daily statistics rebuild process');
} elseif ($force) {
	remove_process_entry(0, 'GRIDREPLAYUH');

	detect_and_correct_running_processes(0, 'GRIDREPLAYUH', 36000);
} else {
	print "ERROR: Another RTM daily statistics rebuild process appears to be running.  Use --force to override!\n";

	exit(1);
}

/* scan recent jobs for job collections */
if (read_config_option('grid_partitioning_enable') != '') {
	replay_daily_stats_partition($scan_start_time, $scan_end_time);
} else {
	replay_daily_stats($scan_start_time, $scan_end_time);
}

remove_process_entry(0, 'GRIDREPLAYUH');

exit(0);

function replay_daily_stats_partition($scan_start_time, $scan_end_time) {
	global $debug, $delete, $optimize;

	$partition_tables = array();
	$partition_table_name = '';
	$partition_max_time   = 0;

	$all_rows   = 0;
	$first_flag = true;

	/* one day */
	$oneday = 60 * 60 * 24;

	$scan_start_time_left = 0;
	$scan_end_time_bak    = $scan_end_time;

	/* normalize the time value */
	$base_time_value = date('H:i:00', strtotime(read_config_option('grid_db_maint_time')));

	print "NOTE: Base Maint Time is '$base_time_value'\n";

	$scan_start_date = date('Y-m-d ', $scan_start_time) . $base_time_value;
	$scan_end_date   = date('Y-m-d ', $scan_end_time) . $base_time_value;
	$days            = 0;
	$not_first_last  = false;
	$all_new         = false;

	print "NOTE: Replay Start Date is '$scan_start_date'\n";
	print "NOTE: Replay End Date is '$scan_end_date'\n";

	$scan_end_time  = strtotime($scan_end_date);

	$last_partition_time = db_fetch_cell("SELECT MAX(max_time)
		FROM grid_table_partitions
		WHERE table_name LIKE 'grid_job_daily_stats%'");

	$grid_partitioning_time_range= time() - strtotime('-' . read_config_option('grid_partitioning_time_range'));

	if (!empty($last_partition_time)) {
		if ( strtotime($scan_end_date) > (time()-$grid_partitioning_time_range-$oneday) ) { //exceed into last partition range.
			if (strtotime($last_partition_time)< strtotime($scan_end_date)) {
				$left_time=(strtotime($scan_end_date)-strtotime($last_partition_time))% $grid_partitioning_time_range;
				if (!empty($left_time)) {
					$scan_end_time = strtotime($scan_end_date)-$left_time;
					$scan_start_time_left = $scan_end_time;
					$scan_end_date = date('Y-m-d ', $scan_end_time) . $base_time_value;

					print "NOTE: The data from '" .date('Y-m-d ', $scan_start_time_left) . " to '" . date('Y-m-d ', $scan_end_time_bak) . "' will be replay into  'grid_job_daily_stats' table\n";
				}
			}
		}
	} else {
		$min_interval_start = db_fetch_cell('SELECT MIN(interval_start) FROM grid_job_daily_stats');

		if ($min_interval_start != NULL) {
			if ( strtotime($scan_end_date) > strtotime($min_interval_start) && strtotime($scan_start_date)< strtotime($min_interval_start)) {
				$scan_end_date   = date('Y-m-d ', strtotime($min_interval_start)) . $base_time_value;
				$scan_end_time=strtotime($scan_end_date);
				$scan_start_time_left=$scan_end_time;
				print "NOTE: The data from '" . date('Y-m-d ', $scan_start_time_left) . " to '" . date('Y-m-d ', $scan_end_time_bak) . "' will be replay into 'grid_job_daily_stats' table\n";
			} elseif (strtotime($scan_start_date) >= strtotime($min_interval_start)) {
				print "NOTE: All the data from '" . date('Y-m-d ', $scan_start_time) . " to '" . date('Y-m-d ', $scan_end_time_bak) . "' will be replay into 'grid_job_daily_stats' table\n";
				replay_daily_stats($scan_start_time, $scan_end_time);

				return;
			}
		}
	}

	/* set the initial timestamp */
	$scan_cur_time  = strtotime($scan_start_date);
	$scan_begin_time  = strtotime($scan_start_date);

	db_execute('DROP TABLE IF EXISTS grid_job_daily_stats_replay');
	db_execute('CREATE TABLE grid_job_daily_stats_replay LIKE grid_job_daily_stats');

	while ($scan_cur_time < $scan_end_time) {
		$sql_start_date = date('Y-m-d H:i:s', $scan_begin_time + ($days * 86400));
		$sql_end_date   = date('Y-m-d H:i:s', $scan_begin_time + ($days * 86400) + $oneday);

		if (is_saving_time_changed(strtotime($sql_start_date), strtotime($sql_end_date), $changed_time)) {
			$sql_end_date     = date('Y-m-d H:i:s', strtotime($sql_end_date) + $changed_time);
			$scan_start_time += $changed_time;
		}

		if ($first_flag == true) {
			$first_flag = false;

			update_table_partitions($scan_start_date, $scan_end_date, $all_new);

			$partition_table_name=get_partition_table_name($sql_start_date, $sql_end_date, $scan_start_date,$scan_end_date, $not_first_last);

			$partition_max_time=get_partition_max_time($partition_table_name);
		}

		/* if the date is out of current temp table range, rename the table to pariton table name, create a new temp table */
		if ($partition_max_time < strtotime($sql_end_date)) {
			$rows = db_fetch_cell('SELECT count(*) FROM grid_job_daily_stats_replay');
			if ($rows>0) {
				if ($not_first_last == true) {
					//if found, and begin date < min interval_end of the partitioned table
					db_execute("DROP TABLE IF EXISTS $partition_table_name");
					db_execute("RENAME TABLE grid_job_daily_stats_replay TO $partition_table_name");
					db_execute('CREATE TABLE grid_job_daily_stats_replay LIKE grid_job_daily_stats');
				} else {
					insert_partition_table($partition_table_name );
					db_execute('TRUNCATE TABLE grid_job_daily_stats_replay');
				}

				update_grid_table_partitions($partition_table_name);

				$all_rows+=$rows;

				print "Records Replayed '$rows' into $partition_table_name\n";
			} else {
				db_execute_prepared("DELETE FROM grid_table_partitions
					WHERE table_name ='grid_job_daily_stats'
					AND  `partition`= ?", array(substr($partition_table_name, strlen($partition_table_name)-3)));

				if ($partition_table_name == 'grid_job_daily_stats_v000' && $all_new == true) {
					update_partition_index();
				}
			}

			$partition_table_name = get_partition_table_name($sql_start_date, $sql_end_date, $scan_start_date, $scan_end_date, $not_first_last);
			$partition_max_time = get_partition_max_time($partition_table_name);
		}

		print "NOTE: ----------------------------------------------------------------\n";
		print "NOTE: Interval Run Started from '$sql_start_date' to '$sql_end_date'\n";

		replay_summarize_grid_data($sql_start_date, $sql_end_date);

		print "NOTE: Interval Run Completed from '$sql_start_date' to '$sql_end_date'\n";

		$days++;

		$scan_cur_time += $oneday;
		$partition_tables[] = $partition_table_name;
	}

	$rows = db_fetch_cell('SELECT count(*) FROM grid_job_daily_stats_replay');

	if ($rows > 0) {
		if ($not_first_last == true) {
			//if found, and begin date < min interval_end of the partitioned table
			db_execute("DROP TABLE IF EXISTS $partition_table_name");
			db_execute("RENAME TABLE grid_job_daily_stats_replay TO $partition_table_name");
		} else {
			insert_partition_table($partition_table_name );
		}

		update_grid_table_partitions($partition_table_name);

		$all_rows += $rows;

		print "Records Replayed '$rows' into $partition_table_name\n";
	} else {
		db_execute_prepared("DELETE FROM grid_table_partitions
			WHERE `partition`=?". substr($partition_table_name, strlen($partition_table_name)-3));
	}

	if (!empty($scan_start_time_left)) {
		print "NOTE: The data from '" .date('Y-m-d H:i:s', $scan_start_time_left) . " to '" . date('Y-m-d ', $scan_end_time_bak) . "' replay begin\n";

		$rows=replay_daily_stats($scan_start_time_left,$scan_end_time_bak);

		$all_rows+=$rows;

		print "NOTE: The data from '" .date('Y-m-d ', $scan_start_time_left) . " to '" . date('Y-m-d ', $scan_end_time_bak) . "' replay end\n";
	}

	if ($optimize) {
		$partition_tables_1 = implode(',', $partition_tables);
		$new_partition_tables = get_config_option('run_partition_optimization');
		if (!empty($new_partition_tables)) {
			$partition_tables_1 = $new_partition_tables.','.$partition_tables_1;
		}
		set_config_option('run_partition_optimization', $partition_tables_1);
	}

	print "Total Records Replayed '$all_rows'\n";
}

function update_partition_index() {
	$records_count=db_fetch_cell("SELECT COUNT(*)
		FROM grid_table_partitions
		WHERE table_name LIKE 'grid_job_daily_stats%'");

	if ($records_count > 0) {
		$partition_number = 1;
		while ($partition_number <= $records_count) {
			$new_number = $partition_number - 1;
			$next_partition = '000' .$new_number;

        	$new_number = substr($next_partition, strlen($next_partition)-3);

			db_execute_prepared("UPDATE grid_table_partitions
				SET `partition`=?
				WHERE `partition`=?
				AND table_name = 'grid_job_daily_stats'", array($new_number, $partition_number));

			$partition_number++;
		}
	}
}

function insert_partitions($low_partition_num, $high_partition_num, $time_start, $time_end) {
	$changed = false;

	$grid_partitioning_time_range = time() - strtotime('-' . read_config_option('grid_partitioning_time_range'));

	$gap_time = strtotime($time_end) - strtotime($time_start);

	if ($gap_time<=0) {
		return;// no change the partition.
	}

	if ($high_partition_num=='000') {
		insert_one_partition(0, $time_start, $time_end);//reset range of the 000 partition for later use.
		return;
	}

	$gap_number = $high_partition_num - $low_partition_num;

	if ($gap_number < 1) {
		return;
	} elseif ($gap_number == 1) {
		insert_one_partition($high_partition_num, $time_start, NULL); //no extend next partitioin.
		return;
	}

	$can_create_count = $gap_number - 1;
	$new_partition_number = $low_partition_num + 1;
	$new_partition_min_time = strtotime($time_start);

	while($can_create_count > 1 && $gap_time > 0) {
		//left one partition number for last insert.
		$new_partition_max_time = $new_partition_min_time + $grid_partitioning_time_range;

		if (is_saving_time_changed($new_partition_min_time, $new_partition_max_time, $changed_time)) {
			$new_partition_max_time += $changed_time;
			$changed = true;
		}
		if (strtotime($time_end)<$new_partition_max_time) {
			insert_one_partition($new_partition_number,date('Y-m-d H:i:s', $new_partition_min_time), $time_end);
		} else {
			insert_one_partition($new_partition_number,date('Y-m-d H:i:s', $new_partition_min_time), date('Y-m-d H:i:s', $new_partition_max_time));
		}

		$can_create_count--;
		$new_partition_number++;

		if ($changed) {
			$changed = false;
			$gap_time += $changed_time;
			$new_partition_min_time += $changed_time;
		}

		$new_partition_min_time = $new_partition_min_time + $grid_partitioning_time_range;
		$gap_time = $gap_time - $grid_partitioning_time_range;
	}

	if ($gap_time > 0) {
		//insert all left time range into the left partition number.
		if (is_saving_time_changed($new_partition_min_time, strtotime($time_end), $changed_time)) {
			$time_end+=$changed_time;
		}

		insert_one_partition($new_partition_number, date('Y-m-d H:i:s', $new_partition_min_time), $time_end);
	}
}

function is_saving_time_changed($first_time, $second_time, &$changed_time) {
	$first_flag  = date('I', $first_time);
	$second_flag = date('I', $second_time);

	if ($first_flag == 0 && $second_flag == 1) {
		$changed_time = -3600;

		return true;
	} elseif ($first_flag == 1 && $second_flag == 0) {
		$changed_time = 3600;
		return true;
	} else {
		return false;
	}
}

/* get the partitions in the replaying range */
function update_table_partitions($scan_start_date, $scan_end_date, &$all_new) {
	$all_new = false;
	$i = 0;

	$exsit_partitions=db_fetch_assoc_prepared("select * from grid_table_partitions
		where table_name like 'grid_job_daily_stats%'
		and min_time<=?
		and max_time>=?
		order by `partition` ASC", array($scan_end_date, $scan_start_date));

	$low_partition=db_fetch_cell_prepared("select `partition` from grid_table_partitions
		where table_name like 'grid_job_daily_stats%'
		and max_time<?
		order by `partition` DESC
		limit 1", array($scan_start_date));

	if ($low_partition == NULL) {
		$low_partition = -1;
	}

	$high_partition=db_fetch_cell_prepared("select `partition` from grid_table_partitions
		where table_name like 'grid_job_daily_stats%'
		and max_time>?
		order by `partition` ASC
		limit 1", array($scan_end_date));

	if ($high_partition==NULL) {
		$high_partition=999;
	}

	$partition_count=cacti_sizeof($exsit_partitions);

	if ($partition_count==0) {  //if None partition exsit in the range.
		if ($low_partition==-1) {
			if ($high_partition==999) {
				$all_new=true;
				insert_partitions(-1,999,$scan_start_date, $scan_end_date);
			} else if ($high_partition==0) {
				print "The time range of input is lower than the partition 000\n";
				exit(-1);
			} else {
				insert_partitions(-1,$high_partition,$scan_start_date, $scan_end_date);
			}
		} else {
			insert_partitions($low_partition,$high_partition,$scan_start_date,$scan_end_date);
		}
	} else {
		if (strtotime($exsit_partitions[0]["min_time"])>strtotime($scan_start_date)) {
			if ($exsit_partitions[0]["partition"]==0) {//extend the current partition start_date.
				insert_partitions(-1,0,$scan_start_date,$exsit_partitions[0]["max_time"]);
			} else {
				insert_partitions($low_partition,$exsit_partitions[0]["partition"],$scan_start_date,$exsit_partitions[0]["min_time"]);
			}
		}

		$last=$exsit_partitions[0];
		$i=1;

		while (!empty($exsit_partitions[$i])) {
			$current=$exsit_partitions[$i];
			$gap_time=strtotime($current["min_time"])-strtotime($last["max_time"]);
			$gap_number=$current["partition"]-$last["partition"];
			if ($gap_time >0) {
				insert_partitions($last["partition"],$current["partition"],$last["max_time"], $current["min_time"]);
			}
			$last=$current;
			$i++;
		}
		if (strtotime($exsit_partitions[$i-1]["max_time"])<strtotime($scan_end_date)) {
			insert_partitions($exsit_partitions[$i-1]["partition"],$high_partition,$exsit_partitions[$i-1]["max_time"], $scan_end_date);
		}
	}
}

function insert_one_partition($partition_number,$min_time,$max_time) {
	$next_partition = "000" .$partition_number;
	$partition_number = substr($next_partition, strlen($next_partition)-3);
	if ($max_time==NULL) {
		db_execute_prepared("UPDATE grid_table_partitions set min_time=? where `partition`=? and table_name='grid_job_daily_stats'", array($min_time, $partition_number));
	} else {
		db_execute_prepared("REPLACE INTO grid_table_partitions
                            (table_name, `partition`, min_time, max_time)
				VALUES ('grid_job_daily_stats', ?, ?, ?)",
				array($partition_number, $min_time, $max_time));
	}
}

function insert_partition_table( $partition_table_name ) {
	if (!cacti_sizeof(db_fetch_assoc_prepared("SHOW TABLES LIKE ?", array($partition_table_name)))) {
		db_execute("CREATE TABLE $partition_table_name LIKE grid_job_daily_stats");
	}
	print "Removing Old Daily Stats Records for Range\n";
	$range = db_fetch_row("SELECT MIN(interval_start) AS min_start, MAX(interval_end) AS max_start FROM grid_job_daily_stats_replay");
	if (cacti_sizeof($range)) {
		db_execute_prepared("DELETE FROM $partition_table_name
			WHERE interval_start>=DATE_FORMAT(?, '%Y-%m-%d 00:00:00')
			AND interval_end<DATE_FORMAT(DATE_ADD(?, INTERVAL 1 DAY), '%Y-%m-%d 00:00:00')", array($range["min_start"], $range["max_start"]));
	}
	print "Inserting New Daily Stats Records for Range\n";
	db_execute("INSERT INTO $partition_table_name
		SELECT *
		FROM grid_job_daily_stats_replay
		ON DUPLICATE KEY UPDATE jobs_in_state=VALUES(jobs_in_state), jobs_wall_time=VALUES(jobs_wall_time),
		jobs_stime=VALUES(jobs_stime), jobs_utime=VALUES(jobs_utime), slots_in_state=VALUES(slots_in_state),
		avg_memory=VALUES(avg_memory), max_memory=VALUES(max_memory);");
}
function update_grid_table_partitions( $partition_table_name ) {
	$partition_number=substr($partition_table_name, strlen($partition_table_name)-3);
	$min_time = db_fetch_cell("SELECT MIN(interval_start) FROM $partition_table_name");
	$max_time = db_fetch_cell("SELECT MAX(interval_end) FROM $partition_table_name");

	db_execute_prepared("REPLACE INTO grid_table_partitions
		    (table_name, `partition`, min_time, max_time)
			VALUES ('grid_job_daily_stats', ?, ?, ?)",
			array($partition_number, $min_time, $max_time));
}

function get_partition_max_time ($partition_table_name) {
	$ret_time=0;
	$partition_number=substr($partition_table_name, strlen($partition_table_name)-3);
	$partition_max_time = db_fetch_cell_prepared("select max_time from grid_table_partitions
								where table_name='grid_job_daily_stats'
								and `partition`=?",
								array($partition_number));
	if (empty($partition_max_time)) {//if the table have not created yet.
		$ret_time= 0;
	} else {
		$ret_time=strtotime($partition_max_time);
	}
	return $ret_time;
}
/* Get the partitioned table name
   if the date is found in the partition table, get the partition table name of grid_job_daily_stats.
   else define a new partition table name of grid_job_daily_stats, and a record insert into grid_table_partitions.
*/
function get_partition_table_name ($sql_start_date, $sql_end_date, $scan_start_date,$scan_end_date, &$not_first_last) {
	$not_first_last=false;
	$grid_job_daily_stats_name="";
	$partition_number = db_fetch_cell_prepared("select `partition` from grid_table_partitions
								where table_name='grid_job_daily_stats'
								and max_time>=?
								order by max_time ASC
								limit 1",
								array($sql_end_date));
	if ($partition_number==NULL) {
		/* If not find the target partition table, then call insert_one_partition to insert a new partition table in grid_table_partitions
		   and create partition table
		*/
		$partition_number = db_fetch_cell("select `partition` from grid_table_partitions
									where table_name='grid_job_daily_stats'
									order by max_time DESC
									limit 1
									");
		if ($partition_number==NULL) {//which means there is no partition table by far
			insert_one_partition("000", $sql_start_date, $sql_end_date);
			$grid_job_daily_stats_name="grid_job_daily_stats" . "_v000";
			db_execute("CREATE TABLE $grid_job_daily_stats_name LIKE grid_job_daily_stats");
		} else {
			$new_partition_number = $partition_number + 1;
			$next_partition = "000" .$new_partition_number;
			$new_partition_number = substr($next_partition, strlen($next_partition)-3);
			insert_one_partition($new_partition_number, $sql_start_date, $sql_end_date);
			$grid_job_daily_stats_name="grid_job_daily_stats" . "_v". $new_partition_number;
			db_execute("CREATE TABLE $grid_job_daily_stats_name LIKE grid_job_daily_stats");
		}
		print "Note: No matched table found in grid_table_partitions, create new partition table: ".$grid_job_daily_stats_name."\n";
		$not_first_last=false;
	} else {
		$next_partition = "000" .$partition_number;
		$partition_number = substr($next_partition, strlen($next_partition)-3);
		$grid_job_daily_stats_name="grid_job_daily_stats" . "_v". $partition_number;
		if ($sql_start_date==$scan_start_date || $sql_end_date==$scan_end_date) {
			$not_first_last=false;//if first or last, not new name , need to insert
		} else {
			$not_first_last=true;
		}
	}
	return $grid_job_daily_stats_name;
}

function replay_daily_stats($scan_start_time, $scan_end_time) {
	global $debug, $delete;

	/* one day */
	$oneday = 60 * 60 * 24;

	/* normalize the time value */
	$base_time_value = date("H:i:00", strtotime(read_config_option("grid_db_maint_time")));
	print "NOTE: Base Maint Time is '$base_time_value'\n";

	$scan_start_date = date("Y-m-d ", $scan_start_time) . $base_time_value;
	$scan_end_date   = date("Y-m-d ", $scan_end_time) . $base_time_value;
	$days            = 0;

	print "NOTE: Replay Start Date is '$scan_start_date'\n";
	print "NOTE: Replay End Date is '$scan_end_date'\n";

	/* set the initial timestamp */
	$scan_cur_time  = strtotime($scan_start_date);
	$scan_end_time  = strtotime($scan_end_date);

	if (!cacti_sizeof(db_fetch_assoc("SHOW TABLES LIKE 'grid_job_daily_stats_replay'"))) {
		db_execute("CREATE TABLE grid_job_daily_stats_replay LIKE grid_job_daily_stats");
	} else {
		db_execute("TRUNCATE TABLE grid_job_daily_stats_replay");
	}

	while ($scan_cur_time < $scan_end_time) {
		$sql_start_date = date("Y-m-d H:i:s", $scan_start_time + ($days * 86400));
		$sql_end_date   = date("Y-m-d H:i:s", $scan_start_time + ($days * 86400) + $oneday);

		print "NOTE: ----------------------------------------------------------------\n";
		print "NOTE: Interval Run Started from '$sql_start_date' to '$sql_end_date'\n";
		replay_summarize_grid_data($sql_start_date, $sql_end_date);
		print "NOTE: Interval Run Completed from '$sql_start_date' to '$sql_end_date'\n";

		$days++;
		$scan_cur_time += $oneday;
	}

	$rows = db_fetch_cell("SELECT count(*) FROM grid_job_daily_stats_replay");

	print "Total Records Replayed '$rows'\n";

	print "Removing Old Daily Stats Records for Range\n";
	$range = db_fetch_row("SELECT MIN(interval_start) AS min_start, MAX(interval_end) AS max_start
		FROM grid_job_daily_stats_replay");

	if (cacti_sizeof($range)) {
		//Current $range["min_start"] maybe 2020-01-01 05:00:00, but the existing record in grid_job_daily_stats in day 2020-01-01 maybe
		//2020-01-01 01:00:00, then, this record will NOT be deleted
		//So, we need to compare the date in day level
		db_execute_prepared("DELETE FROM grid_job_daily_stats
			WHERE interval_start>=DATE_FORMAT(?, '%Y-%m-%d 00:00:00')
			AND interval_end<DATE_FORMAT(DATE_ADD(?, INTERVAL 1 DAY), '%Y-%m-%d 00:00:00')", array($range["min_start"], $range["max_start"]));
	}

	print "Inserting New Daily Stats Records for Range\n";
	db_execute("INSERT INTO grid_job_daily_stats
		SELECT *
		FROM grid_job_daily_stats_replay
		ON DUPLICATE KEY UPDATE jobs_in_state=VALUES(jobs_in_state), jobs_wall_time=VALUES(jobs_wall_time),
		jobs_stime=VALUES(jobs_stime), jobs_utime=VALUES(jobs_utime), slots_in_state=VALUES(slots_in_state),
		avg_memory=VALUES(avg_memory), max_memory=VALUES(max_memory);");

	return $rows;
}

function replay_summarize_grid_data($sql_start_date, $sql_end_date) {
	global $config, $debug;

 	$table_name_part    = '';
	$table_name_union   = '';
	$app_name           = '';
	$app_groupby        = '';
	$from_hosts         = '';
	$from_hosts_groupby = '';
	$project_names      = '';
	$project_groupby    = '';
	$wallt_method_done  = '';
	$wallt_method_exit  = '';

	if (read_config_option('grid_partitioning_enable') == '') {
		get_daily_stats_parameters($table_name_part, $table_name_union,
			$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
			$project_names, $project_groupby, $wallt_method_done,
			$wallt_method_exit, false, 'START', $sql_start_date, $sql_end_date);
	} else {
		get_daily_stats_parameters($table_name_part, $table_name_union,
			$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
			$project_names, $project_groupby, $wallt_method_done,
			$wallt_method_exit, true, 'START', $sql_start_date, $sql_end_date);

		if ($table_name_union == '') {
			print "WARNING: Records do NOT exist for period\n";
			return;
		}
	}

	grid_debug('Entering Started Jobs to Database');
	$sql_query = "INSERT INTO grid_job_daily_stats_replay
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, slots_in_state, gpus_in_state,
		interval_start, interval_end, date_recorded)
		SELECT
		$table_name_part.clusterid,
		$table_name_part.user,
		'STARTED' as stat,
		$table_name_part.queue,
		$app_name,
		$from_hosts,
		$table_name_part.exec_host,
		$project_names,
		SUM($table_name_part.job_count) AS jobs_in_state,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		'$sql_start_date' AS interval_start,
		'$sql_end_date' AS interval_end,
		'$sql_end_date' AS date_recorded
		FROM $table_name_union AS $table_name_part
		GROUP BY $table_name_part.clusterid,
		$table_name_part.user,
		$table_name_part.queue,
		$app_groupby
		$table_name_part.exec_host
		$from_hosts_groupby
		$project_groupby";
	db_execute($sql_query);

	if (read_config_option('grid_partitioning_enable') == '') {
		get_daily_stats_parameters($table_name_part, $table_name_union,
			$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
			$project_names, $project_groupby, $wallt_method_done,
			$wallt_method_exit, false, 'END', $sql_start_date, $sql_end_date);
	} else {
		get_daily_stats_parameters($table_name_part, $table_name_union,
			$from_hosts, $from_hosts_groupby, $app_name, $app_groupby,
			$project_names, $project_groupby, $wallt_method_done,
			$wallt_method_exit, true, 'END', $sql_start_date, $sql_end_date);

		if ($table_name_union == '') {
			print "WARNING: Records do NOT exist for period\n";

			return;
		}
	}
	/**
	 * ToDo: Keep '$gpu_wallt_method_excl' generation for patch/hotfix. It should be moved into function#get_daily_stats_parameters during FixPack release
	 */
	if (read_config_option("grid_job_wallclock_method") == "wsuspend") {
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), ((CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN (gpu_exec_time-CAST(ususp_time AS signed)-CAST(ssusp_time AS signed))*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	} else {
		if (read_config_option("grid_job_gpu_wallclock_cpuruntime") == "on") {
			$gpu_wallt_method_excl = "SUM(IF(gpu_exec_time>0 OR (run_time>0 AND gpu_mode & 258), (CASE WHEN gpu_exec_time>0 THEN gpu_exec_time WHEN run_time>0 AND num_gpus>0 THEN run_time END)*CAST(num_gpus AS signed), 0))";
		} else {
			$gpu_wallt_method_excl = "SUM(CASE WHEN gpu_exec_time>0 THEN gpu_exec_time*CAST(num_gpus AS signed) ELSE 0 END)";
		}
	}

	grid_debug('Entering Ended Jobs to Database');
	$sql_query = "INSERT INTO grid_job_daily_stats_replay
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, jobs_wall_time, gpu_wall_time, jobs_stime, jobs_utime,
		slots_in_state, gpus_in_state, max_memory, avg_memory, gpu_max_mem, gpu_avg_mem,
		interval_start, interval_end, date_recorded)
		SELECT
		$table_name_part.clusterid,
		$table_name_part.user,
		'ENDED' as stat,
		$table_name_part.queue,
		$app_name,
		$from_hosts,
		$table_name_part.exec_host,
		$project_names,
		SUM($table_name_part.job_count) AS jobs_in_state,
		$wallt_method_done,
		$gpu_wallt_method_excl AS gpu_wall_time,
		SUM(stime) AS jobs_stime,
		SUM(utime) AS jobs_utime,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		MAX($table_name_part.mem_used) AS max_memory,
		AVG($table_name_part.mem_used) AS avg_memory,
		MAX($table_name_part.gpu_max_memory) AS gpu_max_memory,
		AVG($table_name_part.gpu_max_memory) AS gpu_avg_memory,
		'$sql_start_date' AS interval_start,
		'$sql_end_date' AS interval_end,
		'$sql_end_date' AS date_recorded
		FROM $table_name_union as $table_name_part
		WHERE (stat != 'EXIT')
		GROUP BY $table_name_part.clusterid,
		$table_name_part.user,
		$table_name_part.queue,
		$app_groupby
		$table_name_part.exec_host
		$from_hosts_groupby
		$project_groupby";

	db_execute($sql_query);

	grid_debug('Entering Exited Jobs to Database');
	$sql_query = "INSERT INTO grid_job_daily_stats_replay
		(clusterid, user, stat, queue, app, from_host, exec_host,
		projectName, jobs_in_state, jobs_wall_time, gpu_wall_time, jobs_stime, jobs_utime,
		slots_in_state, gpus_in_state, max_memory, avg_memory, gpu_max_mem, gpu_avg_mem,
		interval_start, interval_end, date_recorded)
		SELECT
		$table_name_part.clusterid,
		$table_name_part.user,
		'EXITED' as stat,
		$table_name_part.queue,
		$app_name,
		$from_hosts,
		$table_name_part.exec_host,
		$project_names,
		SUM($table_name_part.job_count) AS jobs_in_state,
		$wallt_method_exit,
		$gpu_wallt_method_excl AS gpu_wall_time,
		SUM(stime) AS jobs_stime,
		SUM(utime) AS jobs_utime,
		SUM(num_cpus) AS slots_in_state,
		SUM(num_gpus) AS gpus_in_state,
		MAX($table_name_part.mem_used) AS max_memory,
		AVG($table_name_part.mem_used) AS avg_memory,
		MAX($table_name_part.gpu_max_memory) AS gpu_max_memory,
		AVG($table_name_part.gpu_max_memory) AS gpu_avg_memory,
		'$sql_start_date' AS interval_start,
		'$sql_end_date' AS interval_end,
		'$sql_end_date' AS date_recorded
		FROM $table_name_union as $table_name_part
		WHERE (stat = 'EXIT')
		GROUP BY $table_name_part.clusterid,
		$table_name_part.user,
		$table_name_part.queue,
		$app_groupby
		$table_name_part.exec_host
		$from_hosts_groupby
		$project_groupby";

	db_execute($sql_query);
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM - Daily Statistics Replay Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: database_replay_daily_stats.php --start='YYYY-MM-DD' --end='YYYY-MM-DD'\n";
	print "        [--optimize] [--debug] [--force] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "--start          - The first day to replay statistics\n";
	print "--end            - The last day to replay statistics\n";
	print "--force          - Force a restart of the task\n";
	print "--optimize       - Optimize new partition tables\n";
	print "--debug          - Log verbose information to standard output\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}
