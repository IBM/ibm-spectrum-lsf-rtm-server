#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

if (function_exists('pcntl_async_signals')) {
	pcntl_async_signals(true);
} else {
	declare(ticks = 100);
}

ini_set('output_buffering', 'Off');

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');
include_once($config['base_path'] . '/plugins/gridalarms/setup.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

/** sig_handler - provides a generic means to catch exceptions to the Cacti log.
 * @arg $signo  - (int) the signal that was thrown by the interface.
 * @return      - null */
function sig_handler($signo) {
	global $task_id, $main;

    switch ($signo) {
        case SIGTERM:
        case SIGINT:
			if (!$main) {
				$pids = array_rekey(db_fetch_assoc_prepared("SELECT pid
					FROM grid_processes
					WHERE taskid = ?
					AND taskname='GRIDALARMS'",
					array($task_id)), 'pid', 'pid');

				if (cacti_sizeof($pids)) {
					foreach($pids as $pid) {
						posix_kill($pid, SIGTERM);
					}
				}

				clearTask($task_id, getmypid());

				sleep(5);

				db_execute_prepared("DELETE
					FROM grid_processes
					WHERE taskid = ?
					AND taskname='GRIDALARMS'",
					array($task_id));
			} else {
				$pids = array_rekey(
					db_fetch_assoc("SELECT pid
						FROM grid_processes
						WHERE taskname = 'GRIDALARMS'
						AND taskid = 0"),
					'pid', 'pid'
				);

				if (cacti_sizeof($pids)) {
					foreach($pids as $pid) {
						posix_kill($pid, SIGTERM);
					}
				}

				clearTask($task_id, getmypid());
			}

            exit(0);

            break;
        default:
            /* ignore all other signals */
    }
}

/* get the gridalarms polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug, $force, $debug, $task_id, $main;

$debug       = false;
$force       = false;
$force_maint = false;
$task_id     = -1;
$main        = false;

if (cacti_sizeof($parms)) {
	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch ($arg) {
		case '-d':
		case '--debug':
			$debug = true;
			break;
		case '-M':
		case '--main':
			$main = true;
			break;
		case '--alarm-id':
			$task_id = $value;
			break;
		case '-fr':
			$force = true;
			break;
		case '-fm':
			$force_maint = true;
			break;
		case '-v':
		case '-V':
		case '--version':
			display_version();
			exit(0);
		case '-h':
		case '-H':
		case '--help':
			display_help();
			exit(0);
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
			display_help();
			exit(1);
		}
	}
}

if (read_config_option('thold_disable_all') == 'on') {
	gridalarms_debug('WARNING: All Alerting is disabled.  Exiting.');
	cacti_log('WARNING: All Alerting and Thrsholding is Disabled.  If this is not intended. Please correct at Console > Configuration > Settings > Alerting/Thold', false, 'GRID');
	exit(0);
}

/* install signal handlers for UNIX only */
if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
}

/* Let's ensure that we were called correctly */
if (!$main) {
	if ($task_id == -1) {
		print "FATAL: You must specify -M to Start the Master Control Process, or the Alarm ID using --alarm-id" . PHP_EOL;
		exit(1);
	}

	/* perform interim upgrade if required */
	$cur_version = get_gridalarms_version();
	$db_version  = db_fetch_cell_prepared('SELECT version FROM plugin_config WHERE directory = ?', array('gridalarms'));

	if ($cur_version != $db_version) {
		cacti_log("About to try an upgrade from '$db_version' to '$cur_version'", true, 'UPGRADE');

		plugin_gridalarms_install(true);
	}
}

// if db alarm upgrade process is running, do not run alarm check
if ($main) {
	$start  = microtime(true);
	$alarms = 0;

	if (read_config_option('gridalarms_db_upgrade', true) == '1') {
		gridalarms_debug('DB alarm data upgrade in progress, will not run alarm check.');
	} else {
		gridalarms_debug('NOTE   - About to enter gridalarms poller processing');

		/* obtain the polleri interval if the user is using that Cacti mod */
		$poller_interval = read_config_option('poller_interval');
		if (empty($poller_interval)) {
			$poller_interval = 300;
		}

		/* find out when it's time to perform maintenance */
		$current_time = time();

		$database_maint_time = strtotime('12:00am');
		if ($database_maint_time < $current_time) {
			if ($current_time - $poller_interval < $database_maint_time) {
				$next_db_maint_time = $current_time;
			} else {
				$next_db_maint_time = $database_maint_time + 3600*24;
			}
		} else {
			$next_db_maint_time = $database_maint_time;
		}

		$time_till_next_db_maint = $next_db_maint_time - $current_time;
		if (($time_till_next_db_maint <= 0) || ($force_maint)) {
			$run_maint = true;
			gridalarms_debug('The next gridalerts database maintenance is NOW');
		} else {
			$run_maint = false;
			gridalarms_debug('The next gridalerts database maintenance is "'. date('Y-m-d G:i:s', $next_db_maint_time) . '"');
		}

		gridalarms_debug('START  - gridalarms Polling Process');

		if (detect_and_correct_running_processes(0, 'GRIDALERTSPOLLER', $poller_interval*3) || $force) {
			db_execute_prepared('REPLACE INTO settings
				(name, value) VALUES
				("gridalarms_last_run_time", ?)',
				array(date('Y-m-d G:i:s', $current_time)));

			/* process alarms */
			gridalarms_debug('START  - Alerts Poller');
			$alarms = gridalarms_alarm_parallel_poller();
			gridalarms_debug('FINISH - Alerts Poller');
			remove_process_entry(0, 'GRIDALERTSPOLLER');
		}

		if ($run_maint || $force_maint) {
			gridalarms_debug('START - Alerts Log Cleanup');
			gridalarms_alarm_log_cleanup();
			gridalarms_debug('FINISH - Alerts Log Cleanup');
		}
	}

	$end = microtime(true);

	$threads = read_config_option('gridalarm_parallel');

	/* log statistics */
	$gridalarms_alarm_stats = sprintf('Time:%01.4f Processes:%s Alerts:%s', $end - $start, $threads, $alarms);

	cacti_log('GRIDALERTS STATS: ' . $gridalarms_alarm_stats, false, 'SYSTEM');

	set_config_option('stats_gridalarms_alarm', $gridalarms_alarm_stats);
} else {
	$alarm = db_fetch_row_prepared('SELECT *
		FROM gridalarms_alarm
		WHERE id = ?',
		array($task_id));

	if (cacti_sizeof($alarm)) {
		registerTask($task_id, getmypid());
		gridalarms_check_alarm($alarm);
		endTask($task_id, getmypid());
	}
}

function gridalarms_alarm_parallel_poller() {
	global $config, $debug, $force;

	$alarms = db_fetch_assoc('SELECT *
		FROM gridalarms_alarm
		WHERE alarm_enabled = "on"
		AND frequency > 0');

	$total_alarms = cacti_sizeof($alarms);
	$threads      = read_config_option('gridalarm_parallel');

	gridalarms_debug("START  - Processing $total_alarms Alerts");

	api_plugin_hook_function('gridalarms_reset_hostsalarm');

	if (cacti_sizeof($alarms)) {
		while (true) {
			$running = runningTasks();

			foreach ($alarms as $index => $alarm) {
				if ($running < $threads) {
					exec_background(read_config_option('path_php_binary'), '-q ' . read_config_option('path_webroot') . '/plugins/gridalarms/poller_gridalarms.php --alarm-id=' . $alarm['id'] . ($force ? ' -fr':'') . ($debug ? ' --debug':''));
					$running++;

					unset($alarms[$index]);
				}
			}

			usleep(500000);

			if (!cacti_sizeof($alarms) && $running == 0) {
				break;
			}
		}
	}

	api_plugin_hook_function('gridalarms_delete_hostsalarm');
	gridalarms_debug("FINISH - Processed $total_alarms Alerts");

	return $total_alarms;
}

/**
 * display_version - Display useful version information.
 */
function display_version() {
	print 'Grid Alerts Poller Process ' . read_config_option('gridalarms_db_version') . PHP_EOL;
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8').' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . PHP_EOL . PHP_EOL;
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	display_version();

	print "usage: poller_gridalarms.php [-M] [-fr] [-fm] [--debug] | [--alarm-id=N] [--debug]" . PHP_EOL . PHP_EOL;
	print "-M               - Start the main poller process" . PHP_EOL;
	print "--alarm-id       - Collect check this specific alarm-id" . PHP_EOL;
	print "-fr              - Force the interval process.  Used in conjunction with -fm" . PHP_EOL;
	print "-fm              - Force the maintenance process" . PHP_EOL;
	print "-d | --debug     - Display verbose output during execution" . PHP_EOL;
}

function isProcessRunning($pid) {
    return posix_kill($pid, 0);
}

function killProcess($pid) {
    return posix_kill($pid, SIGTERM);
}

function runningTasks() {
	return db_fetch_cell("SELECT COUNT(*)
		FROM grid_processes
		WHERE taskname = 'GRIDALARMS'
		AND taskid > 0");
}

function registerTask($task_id, $pid) {
	db_execute_prepared("REPLACE INTO grid_processes
        (pid, taskname, taskid, heartbeat)
        VALUES (?, ?, ?, NOW())",
        array($pid, 'GRIDALARMS', $task_id));
}

function endTask($task_id, $pid) {
	db_execute_prepared("DELETE FROM grid_processes
        WHERE pid = ?
		AND taskname = 'GRIDALARMS'
        AND taskid = ?",
        array($pid, $task_id));
}

function clearTask($task_id, $pid) {
	db_execute_prepared("DELETE
		FROM grid_processes
		WHERE pid = ?
		AND taskname = 'GRIDALARMS'
		AND taskid = ?",
		array($pid, $task_id));
}

function clearAllTasks($task_id) {
	db_execute_prepared("DELETE FROM grid_processes
		WHERE taskid = ?
		AND taskname = 'GRIDALARMS'",
		array($task_id));
}

