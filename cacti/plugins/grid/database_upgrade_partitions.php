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
include_once(dirname(__FILE__) . '/setup.php');
include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['library_path'] . '/rtm_db_upgrade.php');

declare(ticks = 1);

/* need to capture signals from users */
function sig_handler($signo) {

	switch ($signo) {
		case SIGTERM:
		case SIGINT:
		case SIGABRT:
		case SIGQUIT:
		case SIGSEGV:
			set_config_option('grid_partitions_upgrade_status', 'scheduled');
			cacti_log("GRID PARTITION DBUPGRADE - WARNING: database_jobs_partions_upgarde is terminated. It has been rescheduled to restart after next database maintenance. Or it may be restarted manually at any time when database is not busy!", true);
			remove_process_entry('0', 'GRID PARTITION DBUPGRADE');
			exit;
			break;
		default:
			break;
	}
}

if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGABRT, 'sig_handler');
	pcntl_signal(SIGQUIT, 'sig_handler');
	pcntl_signal(SIGSEGV, 'sig_handler');
}


/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$force_version = '';
$force         = false;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
		case '-v':
		case '-V':
		case '--version':
			display_version();
			exit;
		case '-h':
		case '-H':
		case '--help':
			display_help();
			exit;
		case "-f":
		case "--force":
			$force = true;
			break;
		case "-s":
		case "--schedule":
			set_config_option('grid_partitions_upgrade_status', 'scheduled');
			exit;
		case "--force-ver":
			$force_version = trim($value);
			if (cacti_version_compare($force_version, '9.1', '<')) {
				$force_version = '8.3';
			}

			switch(rtm_plugin_ver_validate($force_version)){
				case -1:
					cacti_log("ERROR: version number '$force_version' is not supportted", true, 'UPGRADE');
					exit;
				case  1:
					cacti_log("ERROR: Invalid grid plugin version number: '$force_version'", true, 'UPGRADE');
					exit;
				case 0:
				default:
					cacti_log("NOTE: Upgrading grid partitions from v$force_version", true, 'UPGRADE');
			}
			break;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			exit;
	}
}

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

upgrade_partition_table($force, $force_version);

function upgrade_partition_table($force = false, $force_ver = NULL) {
	$current_time = time();
	$grid_part_ver = NULL;
	$grid_part_ver_min = RTM_VERSION_SUPPORTED_MIN;

	if(!empty($force_ver)){
		$grid_part_ver = $force_ver;
		$grid_part_ver_min = $force_ver;
	}
	if(empty($grid_part_ver)){
		$grid_part_ver = read_config_option('grid_part_version');
	}
	if (empty($grid_part_ver)) {
		//Before RTM 10.2.0.1, grid plugin partition table utility has not version flag.
		//The first partition upgrade design come from 9.1.2.
		$grid_part_ver = RTM_VERSION_SUPPORTED_MIN;
	}

	$grid_current_version = get_grid_version();

	$upgrade_state = read_config_option('grid_partitions_upgrade_status', true);

	if(empty($force_ver) && rtm_version_compare($grid_part_ver, $grid_current_version) == 0){
		if ((!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
			cacti_log('GRID PARTITION DBUPGRADE - NOTICE: Altering partitioned tables already done!', true);
			set_config_option('grid_partitions_upgrade_status', 'done');
		}
		return;
	}

	if(!rtm_plugin_upgrade_partition_check($grid_part_ver, $grid_current_version, 'grid', $grid_part_ver_min)){
		if ((!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
			cacti_log("GRID PARTITION DBUPGRADE - INFO: Partitioned tables are not required to be alterred from '$grid_part_ver' to '$grid_current_version'.", true);
			set_config_option('grid_partitions_upgrade_status', 'done');
			set_config_option('grid_part_version', $grid_current_version);
		}
		return;
	}

	if(!$force && empty($upgrade_state)){
		cacti_log('GRID PARTITION DBUPGRADE - NOTICE: upgrading partitioned tables not scheduled!', true);
	}

	cacti_log ("GRID PARTITION DBUPGRADE - INFO: Initial Grid plugin partition upgrader version flag: grid_part_version=$grid_part_ver");

	$database_maint_time = strtotime(read_config_option('grid_db_maint_time', true));
	if (!strlen(read_config_option('grid_db_maint_time', true))) {
		$database_maint_time = strtotime(read_default_config_option('grid_db_maint_time'));
	}

	if (($database_maint_time -3600 >= $current_time || $current_time >= $database_maint_time+3600) &&
		(!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
		if ($upgrade_state == 'scheduled') {
			set_config_option('grid_partitions_upgrade_status', 'started');
		}
		if (detect_and_correct_running_processes('0', 'GRID_PARTITION_DBUPGRADE', 99999999) == true ) {
			if (rtm_plugin_upgrade_partition($grid_part_ver, $grid_current_version, 'grid', $grid_part_ver_min) != DB_STATUS_ERROR ) {
				set_config_option('grid_partitions_upgrade_status', 'done');
				set_config_option('grid_part_version', $grid_current_version);
				cacti_log('GRID PARTITION DBUPGRADE - NOTICE: Altering partitioned tables completed.', true);
			}else{
				cacti_log("GRID PARTITION DBUPGRADE - ERROR: grid data upgrade error. RTM remained in grid data upgrade mode. Please check cacti.log and database tables.");
			}
			remove_process_entry('0', 'GRID_PARTITION_DBUPGRADE');
		}
	}
}

//ToDo: Move to Pre-10.2 upgrade scripts like 10_2_0_1.php
function finished_jobs_partition_db_upgrade () {
	$column_arr = array(
		'command'                           => array( 'type' => 'varchar(1024)', 'NULL' => true, 'default' => '', 'after' => 'licenseProject'),
		'res_requirements'                  => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'preExecCmd'),
		'jobDescription'                    => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'userGroup'),
		'combinedResreq'                    => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'jobDescription'),
		'effectiveResreq'                   => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'combinedResreq'),
		'chargedSAAP'                       => array( 'type' => 'varchar(256)', 'NULL' => true, 'default' => '', 'after' => 'effectiveResreq'),
		'ineligiblePendingTime'             => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'chargedSAAP'),
		'pendState'                         => array( 'type' => 'int(10)', 'NULL' => false, 'default' => '-1', 'after' => 'ineligiblePendingTime'),
		'effectivePendingTimeLimit'         => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'pendState'),
		'effectiveEligiblePendingTimeLimit' => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'effectivePendingTimeLimit'),
		'runtimeEstimation'                 => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0', 'after' => 'termTime'),
		'max_allocated_processes'           => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'comment' => 'job level allocated slot', 'after' => 'num_cpus'),
	);
}

function grid_jobs_rusage_partition_db_upgrade () {
	$column_arr = array(
		'mem_reserved' => array( 'type' => 'float', 'NULL' => false, 'default' => '0', 'after' => 'swap'),
		'num_cpus'     => array('type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'comment' => 'job level allocated slot', 'after' => 'nthreads')
	);
}

function grid_jobs_host_rusage_partition_db_upgrade () {
	$column_arr = array(
		'processes' => array('type' => 'int(11)', 'NULL' => false, 'default' => '0', 'comment' => 'job host level allocated slot', 'after' => 'swap')
	);
}

function grid_jobs_pendreasons_finished_partition_db_upgrade () {
	$column_arr = array(
		'type'   => array('type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'subreason'),
		'detail' => array('type' => 'varchar(128)', 'NULL' => false, 'default' => '', 'after' => 'type'),
		'ratio'  => array('type' => 'float', 'NULL' => false, 'default' => '0', 'after' => 'detail')
	);
}

function display_version() {
	print 'IBM Spectrum LSF RTM Partition Table Upgrader ' . get_grid_version() . "\n";
	print rtm_copyright();
}

/*	display_help - displays the usage of the function */
function display_help () {
	print "database_upgrade_partitions.php " . get_grid_version() . "\n\n";
	print "Upgrade Grid plugin dababase partition tables.\n";
	print "Usage:\n";
	print "database_upgrade_partitions.php [--force-ver=VER] [-f|--force]\n";
	print "database_upgrade_partitions.php [-h|-H|--help] [-v|-V|--version]\n";
	print "database_upgrade_partitions.php [-s|--schedule ]\n\n";
	print "-h|-H|--help     - Display this help and exit.\n";
	print "-v|-V|--version  - Output version information and exit.\n";
	print "-f|--force       - Force the execution of the upgrade process for the database partition tables.\n";
	print "--force-ver      - Force the upgrade of the database background process from specified version number.\n";
	print "-s|--schedule    - Schedule the upgrade start time before or after maintanance 1 hour, and exit.\n";
}
