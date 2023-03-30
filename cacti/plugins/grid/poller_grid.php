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
include_once($config['base_path'] . '/plugins/grid/lib/grid_api_archive.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

$start = microtime(true);

/* get the time the cacti poller started */
$grid_poller_start = read_config_option('grid_poller_start');

/* get the grid polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* get the start time */
$start_time = time();

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug;

$debug          = false;
$forcerun       = false;
$forcerun_maint = false;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-d':
		$debug = true;
		break;
	case '-fm':
		$forcerun_maint = true;
		break;
	case '-fr':
		$forcerun       = true;
		break;
	case '-h':
	case '-H':
	case '--help':
	case '-v':
	case '-V':
	case '--version':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

grid_debug('About to enter Grid poller processing');

grid_debug('Set UI Defaults if Not set');
grid_set_defaults();

/* detect a crashed poller */
$last_maint_start = read_config_option('grid_db_maint_start_time');
$last_maint_end   = read_config_option('grid_db_maint_end_time');

/* find out if it's time to perform db maintenance */
$grid_db_maint_time = read_config_option('grid_db_maint_time');
$database_maint_time = strtotime($grid_db_maint_time);
if (!$database_maint_time) {
	$database_maint_time = strtotime('12:00am');
	set_config_option('grid_db_maint_time', '12:00am');
}

/* obtain the polleri interval if the user is using that Cacti mod */
$poller_interval = read_config_option('poller_interval');
if (empty($poller_interval)) { $poller_interval = 300; }

/* maint DB later one poller_interval than planned. make sure finished jobs are moved into grid_jobs_finished.*/
$database_maint_time += $poller_interval;
$current_time = time();

if ((read_config_option('grid_system_collection_enabled') == 'on') &&
	(read_config_option('grid_collection_enabled') == 'on')) {

	// catch the unlikely event that the grid_jobs_finished is missing
	if (!db_table_exists('grid_jobs_finished')) {
		db_execute('CREATE TABLE grid_jobs_finished LIKE grid_jobs;');
		db_execute('ALTER TABLE grid_jobs_finished ENGINE=InnoDB');
	}

	if (!$forcerun_maint || $forcerun) {
		if (detect_and_correct_running_processes(0, 'GRIDPOLLER', $poller_interval*3)) {

			/* do background idle job detection after job cleanup */
			$command_string = read_config_option('path_php_binary');
			$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_move.php');
			exec_background($command_string, $extra_args);

			/* do background idle job detection after job cleanup */
			$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_idled.php');
			exec_background($command_string, $extra_args);

			/* do background  job runtime detection after job cleanup */
			$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_runtime.php');
			exec_background($command_string, $extra_args);


			/* takes the job stat's for the last poller interval and summarizes them */
			summarize_grid_data($current_time, $start);

			/* setup some table information to detect for starved jobs */
			collect_host_starvation_data($current_time, $start);

			/* get some information on the number of jobs that have completed */
			update_cluster_jobtraffic($current_time, $start);

			if (read_config_option('grid_archive_enable') == 'on') {
				$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_archive.php');
				exec_background($command_string, $extra_args);
			}

			$replace_time = 0;
			$thold_time = 0;

			/* update host tholds */
			update_grid_summary_table(true, $replace_time, $thold_time);

			/* update batch load record in grid_host_alarm table*/
			update_grid_host_alarm();

			/* log flapping jobs */
			find_flapping_jobs();

			/* log pidalarm jobs */
			find_pidalarm_jobs();

			/* determine grid efficiencies */
			calculate_and_store_grid_efficiencies();

			/* update project level statistics */
			update_projects();

			/* update host group statistics */
			update_hostgroups_stats();

			/* update guarantee resource pool/service class*/
			update_guarantee_respool();

			/* update job group statistics */
			update_group();

			/* update application level statistics */
			update_application();

			/* update user group statistics */
			update_user_group_stats();

			/* update the hourly job statistics */
			update_cluster_queue_tputs();

			/* do background queue fairshare update */
			$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_fairshare.php');
			exec_background($command_string, $extra_args);

			/* update project level statistics */
			update_queues();

			if (read_config_option('grid_license_project_tracking') == 'on') {
				update_license_projects();
			}

			/* remove old license server records from the various databases */
			//if (db_fetch_cell("SELECT COUNT(*) FROM lic_services WHERE disabled='on'")) {
			//	prune_old_license_server_stats();
			//}

			/* update queue dispatch times */
			update_queues_dispatch_times();

			/* update cluster throughput information */
			update_cluster_tputs();

			/* get rid of old png files */
			prune_cached_pngs();

			set_config_option('grid_last_run_time', date('Y-m-d G:i:s', $current_time));

			set_config_option('grid_poller_prev_start', $grid_poller_start);

			remove_process_entry(0, 'GRIDPOLLER');

			log_grid_statistics('collect');
		}
	}

	// if db job upgrade process is running, do not run maint
	if (read_config_option('grid_db_upgrade',true) == '1') {
		grid_debug('DB job tables upgrade in progress, will not run DB maintenance routine.');
	} else {
		$current_date_maint_time = strtotime(date('Y-m-d',$current_time) . ' '.  $grid_db_maint_time);
		if( $current_date_maint_time > $current_time){
			$database_maint_time = $database_maint_time - 3600 * 24; //Fix skip run, such as maint time is 23:50, but real start run at 0:05
			//cacti_log("NOTE: Move time, current_date_maint_time=$current_date_maint_time, current_time=$current_time, database_maint_time=$database_maint_time");
		}
		// obtain previous DB maintain start time
		$grid_prev_db_maint_time = read_config_option("grid_prev_db_maint_time");
		if (!empty($grid_prev_db_maint_time)) {
			$grid_prev_db_maint_time = strtotime($grid_prev_db_maint_time);
		} else {
			$install_time = read_config_option('install_complete');
			$install_date = date('Y-m-d',floor($install_time));
			$grid_prev_db_maint_time = strtotime($install_date. ' '.  $grid_db_maint_time);
			$grid_prev_db_maint_time += $poller_interval;
			//cacti_log("NOTE: Install time, install_time=$install_time, grid_db_maint_time=$grid_db_maint_time, grid_prev_db_maint_time=$grid_prev_db_maint_time");
		}
		//cacti_log("NOTE: database_maint_time=$database_maint_time, current_time=$current_time, grid_prev_db_maint_time=$grid_prev_db_maint_time");
		if ($database_maint_time < $current_time && $grid_prev_db_maint_time < $database_maint_time) {
			$run_maint = true;
			grid_debug('The next database maintenance is NOW');
		} else {
			$run_maint = FALSE;
		}
	}

	if ($run_maint || $forcerun_maint) {
		/* allow to run for 20 hours */
		if (detect_and_correct_running_processes(0, 'GRIDMAINTENANCE', 72000)) {
			if ($run_maint || $forcerun_maint) {
				set_config_option('grid_prev_db_maint_time', date('Y-m-d G:i:s', $current_time));

				/* do background idle job detection after job cleanup */
				$command_string = read_config_option('path_php_binary');
				$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/poller_grid_host_close_move.php');
				exec_background($command_string, $extra_args);

				/* take time and log performance data */
				$start = microtime(true);

				$day_of_week = date('w');
				$day_of_month = date('d');
				if (read_config_option('grid_optimization_schedule') == 'n') {
					perform_grid_db_maint($current_time, false);
				} else if ((($day_of_week = read_config_option('grid_optimization_weekday')) &&
					(read_config_option('grid_optimization_schedule') == 'w')) ||
					(read_config_option('grid_optimization_schedule') == 'd')) {
					perform_grid_db_maint($current_time, true);
				} else if (($day_of_month == (int) read_config_option('grid_optimization_monthday'))
					&& (read_config_option('grid_optimization_schedule') == 'm')) {
					perform_grid_db_maint($current_time, true);
				} else {
					perform_grid_db_maint($current_time, false);
				}

				log_grid_statistics('maint');

				remove_process_entry(0, 'GRIDMAINTENANCE');

			}


			/* do background idle job detection after job cleanup */
			$command_string = read_config_option('path_php_binary');

			$extra_args = '-q ' . cacti_escapeshellarg($config['base_path'] . '/plugins/grid/grid_purge_device.php'). ' -a -C';

			exec_background($command_string, $extra_args);


			//After maint work, background start database_jobs_partitions_upgrade.sh if it is scheduled.
			$partition_upgrade_stat = read_config_option('grid_partitions_upgrade_status', true);
			if (!empty($partition_upgrade_stat) && $partition_upgrade_stat == 'scheduled') {
				$path_rtm_top = grid_get_path_rtm_top();
				exec ("$command_string $path_rtm_top/cacti/plugins/grid/database_upgrade_partitions.php >/dev/null &");
			}
		}
	}

	//After maint work, background start database_jobs_partitions_upgrade.sh if it is scheduled.
	$partition_upgrade_stat = read_config_option('grid_partitions_upgrade_status', true);
	if (!empty($partition_upgrade_stat) && $partition_upgrade_stat == 'scheduled') {
		$command_string = read_config_option('path_php_binary');
		$path_rtm_top = grid_get_path_rtm_top();
		exec ("$command_string $path_rtm_top/cacti/plugins/grid/database_upgrade_partitions.php >/dev/null &");
	}
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'IBM Spectrum LSF RTM Master Poller Process ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: poller_grid.php [-fm [-fr]] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-fm              - Force the execution of the database maintenance process\n";
	print "-fr              - Force the interval process.  Used in conjunction with -fm\n";
	print "-d               - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

function log_grid_statistics($type = 'collect') {
	global $start;

	$grid_hosts = db_fetch_cell('SELECT count(*) FROM grid_hosts');
	$grids      = db_fetch_cell('SELECT count(*) FROM grid_clusters');

	/* take time and log performance data */
	$end = microtime(true);

	if ($type == 'collect') {
		$cacti_stats = sprintf(
			'Time:%01.4f ' .
			'Grids:%s ' .
			'GridHosts:%s ',
			round($end-$start,4),
			$grids,
			$grid_hosts);

		/* log to the database */
		set_config_option('stats_grid', $cacti_stats);

		/* log to the logfile */
		cacti_log('GRID STATS: ' . $cacti_stats, true, 'SYSTEM');
	} else {
		$cacti_stats = sprintf('Time:%01.4f', round($end-$start,4));

		/* log to the database */
		set_config_option('stats_grid_maint', $cacti_stats);

		/* log to the logfile */
		cacti_log('GRID MAINT STATS: ' . $cacti_stats, true, 'SYSTEM');
	}
}
