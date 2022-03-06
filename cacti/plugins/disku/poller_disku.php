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
include_once($config['library_path'] . '/rtm_functions.php');
include_once(dirname(__FILE__) . '/include/disku_functions.php');
include_once(dirname(__FILE__) . '/include/disku_partitioning.php');
include_once(dirname(__FILE__) . '/../grid/lib/grid_partitioning.php');


/* take the start time to log performance data */
list($micro,$seconds) = preg_split('/ /', microtime());
$start = $seconds + $micro;

/* get the time the cacti poller started */
$disku_poller_start = read_config_option('disku_poller_start');

/* get the disku polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* process callling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug;

$debug          = false;
$forcerun       = false;
$forcerun_maint = false;
$run_maint      = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-fm':
		$forcerun_maint = true;
		break;
	case '-fr':
		$forcerun = true;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . '\n\n';
		display_help();
		exit;
	}
}

if (read_config_option('grid_collection_enabled') != 'on') {
	disku_debug('DB schema upgrade in process. Disku poller exit.');
	exit;
}

disku_debug('About to enter Disk Monitoring Poller Processing');

$time = time();
$poller_interval          = read_config_option('poller_interval');
$disku_rotation_frequency = read_config_option('disku_rotation_frequency');
$disku_rotation_time      = read_config_option('disku_rotation_time');
$disku_rotation_day       = read_config_option('disku_rotation_day');
$disku_prev_daytime       = read_config_option('disku_prev_daytime');

$day     = date('w', $time);
//$daytime = $time % 86400;

$ltime=localtime($time,true);
$daytime= $ltime['tm_hour'] * 3600 + $ltime['tm_min'] * 60 + $ltime['tm_sec'];

if (isset($disku_prev_daytime)) {
	if ($disku_rotation_frequency == 0) { // Daily rotation
		if ($disku_prev_daytime > $daytime ) { // Midnight has been struck
			if ($daytime > $disku_rotation_time) {
				$run_maint = true;
			}
		} elseif ($disku_prev_daytime < $disku_rotation_time && $daytime >= $disku_rotation_time) {
			$run_maint = true;
		}
	} elseif ($day == $disku_rotation_day) {
		if ($disku_prev_daytime > $daytime ) { // Midnight has been struck
			if ($daytime > $disku_rotation_time) {
				$run_maint = true;
			}
		} elseif ($disku_prev_daytime < $disku_rotation_time && $daytime >= $disku_rotation_time) {
			$run_maint = true;
		}
	}
}

if ($run_maint) {
	disku_debug('Time to Rotate Tables');
}

/* update the previous daytime entry */
db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('disku_prev_daytime', ?)", array($daytime));

/* get os users and groups */
if (detect_and_correct_running_processes(0, 'DISKUPOLLER', $poller_interval*3)) {
	get_os_groups();
	get_os_users();
	log_disku_statistics('interval');
	remove_process_entry(0, 'DISKUPOLLER');
}

if ($run_maint || $forcerun_maint) {
	if (detect_and_correct_running_processes(0, 'DISKUMAINT', $poller_interval*3)) {
		list($micro,$seconds) = preg_split('/ /', microtime());
		$start = $seconds + $micro;
		disku_history_rotation();
		remove_process_entry(0, 'DISKUMAINT');
		log_disku_statistics('maint');
	}
}
function disku_partition_create($table) {
	$sql="SELECT COUNT(*) FROM $table";
	$db_count   = db_fetch_cell($sql) + 1;
	if ($db_count>1) {
		return true;
	} else {
		return false;
	}
}

function disku_history_rotation() {
	/* disku_directory_totals_history, disku_extension_totals_history, disku_groups_totals_history, disku_users_totals_history */

	/* directory totals */
	disku_debug("Partiting for 'disku_directory_totals_history'");
	if (disku_partition_create('disku_directory_totals_history')) {
		partition_create('disku_directory_totals_history', 'intervalEnd', 'intervalEnd');
	}
	disku_partition_prune_partitions('disku_directory_totals_history');
	db_execute("INSERT INTO disku_directory_totals_history
		(`poller_id`, `path_id`, `dirName`, `group`, `groupid`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `intervalEnd`)
		SELECT `poller_id`, `path_id`, `dirName`, `group`, `groupid`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, NOW() AS intervalEnd
		FROM disku_directory_totals");

	db_execute("DELETE FROM disku_directory_totals WHERE delme=1");
	db_execute("OPTIMIZE TABLE disku_directory_totals");

	/* extension totals */
	disku_debug("Partitioning for 'disku_extension_totals_history'");
	if (disku_partition_create("disku_extension_totals_history")) {
		partition_create("disku_extension_totals_history", "intervalEnd", "intervalEnd");
	}
	disku_partition_prune_partitions("disku_extension_totals_history");
	db_execute("INSERT INTO disku_extension_totals_history (`poller_id`, `path_id`, `extension`, `userid`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `intervalEnd`) SELECT `poller_id`, `path_id`, `extension`, `userid`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, NOW() AS intervalEnd FROM disku_extension_totals");
	db_execute("DELETE FROM disku_extension_totals WHERE delme=1");
	db_execute("OPTIMIZE TABLE disku_extension_totals");

	/* groups totals */
	disku_debug("Partitioning for 'disku_groups_totals_history'");
	if (disku_partition_create("disku_groups_totals_history")) {
		partition_create("disku_groups_totals_history", "intervalEnd", "intervalEnd");
	}
	disku_partition_prune_partitions("disku_groups_totals_history");
	db_execute("INSERT INTO disku_groups_totals_history (`poller_id`, `path_id`, `groupid`, `group`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `directories`, `intervalEnd`) SELECT `poller_id`, `path_id`, `groupid`, `group`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `directories`, NOW() AS intervalEnd FROM disku_groups_totals");
	db_execute("DELETE FROM disku_groups_totals WHERE delme=1");
	db_execute("OPTIMIZE TABLE disku_groups_totals");

	/* users totals */
	disku_debug("Partitioning for 'disku_users_totals_history'");
	if (disku_partition_create("disku_users_totals_history")) {
		partition_create("disku_users_totals_history", "intervalEnd", "intervalEnd");
	}
	disku_partition_prune_partitions("disku_users_totals_history");
	db_execute("INSERT INTO disku_users_totals_history (`poller_id`, `path_id`, `userid`, `user`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `directories`, `intervalEnd`) SELECT `poller_id`, `path_id`, `userid`, `user`, `files`, `size`, `size0to6`, `size6to12`, `size12plus`, `directories`, NOW() AS intervalEnd FROM disku_users_totals");
	db_execute("DELETE FROM disku_users_totals WHERE delme=1");
	db_execute("OPTIMIZE TABLE disku_users_totals");
}

/*      display_help - displays the usage of the function */
function display_help () {
	global $config;

	print "Master Disk Monitoring Poller Process " . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
	print "usage: poller_disku.php [-fr] [-fm] [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "-fm		- Force the execution of the database maintenance process\n";
	print "-fr		- Force the interval process. Used in conjunction with -fm\n";
	print "-d		- Display verbose output during execution\n";
	print "-v -V --version	- Display this help message\n";
	print "-h --help	- display this help message\n\n";
}

function log_disku_statistics($type = "collect") {
	global $start;

	/* take time and log performance data */
	list($micro,$seconds) = preg_split("/ /", microtime());
	$end = $seconds + $micro;

	if ($type == "interval") {
		$users = db_fetch_cell("SELECT COUNT(*) FROM disku_users");
		$groups = db_fetch_cell("SELECT COUNT(*) FROM disku_groups");
		$cacti_stats = sprintf("Time:%01.4f Groups:%s Users:%s", round($end-$start,4), $groups, $users);

		/* log to the database */
		db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('stats_disku_interval', ?)", array($cacti_stats));

		/* log to the logfile */
		cacti_log("DISKU STATS: " . $cacti_stats , true, "SYSTEM");
	} elseif ($type == "daily") {
		$cacti_stats = sprintf("Time:%01.4f", round($end-$start,4));

		/* log to the database */
		db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('stats_disku_daily', ?)", array($cacti_stats));

		/* log to the logfile */
		cacti_log("DISKU DAILY STATS: " . $cacti_stats ,true,"SYSTEM");
	} else {
		$cacti_stats = sprintf("Time:%01.4f", round($end-$start,4));

		/* log to the database */
		db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('stats_disku_maint', ?)", array($cacti_stats));

		/* log to the logfile */
		cacti_log("DISKU MAINT STATS: " . $cacti_stats ,true,"SYSTEM");
	}
}

