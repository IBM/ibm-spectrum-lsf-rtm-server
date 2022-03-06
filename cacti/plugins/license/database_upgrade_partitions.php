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
include_once(dirname(__FILE__) . '/setup.php');
include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['library_path'] . '/rtm_db_upgrade.php');

/* need to capture signals from users */
function sig_handler($signo) {
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
		case SIGABRT:
		case SIGQUIT:
		case SIGSEGV:
			if(read_config_option('lic_partitions_upgrade_status', true) == 'started'){
				set_config_option('lic_partitions_upgrade_status', 'scheduled');
			}
			cacti_log("LICENSE PARTITION DBUPGRADE - WARNING: database_upgrade_partitions.php is terminated. It has been rescheduled to restart after next database maintenance. Or it may be restarted manually at any time when database is not busy!", true);
			remove_process_entry('0', 'LICENSE_PARTITION_DBUPGRADE');
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

$force         = false;
$force_version = NULL;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

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
			set_config_option('lic_partitions_upgrade_status', 'scheduled');
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
					cacti_log("ERROR: Invalid license plugin version number: '$force_version'", true, 'UPGRADE');
					exit;
				case 0:
				default:
					cacti_log("NOTE: Upgrading license partitions from v$force_version", true, 'UPGRADE');
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
	$license_part_ver = NULL;
	$license_part_ver_min = RTM_VERSION_SUPPORTED_MIN;

	if(!empty($force_ver)){
		$license_part_ver = $force_ver;
		$license_part_ver_min = $force_ver;
	}
	if(empty($license_part_ver)){
		$license_part_ver = read_config_option('license_part_version');
	}
	if(empty($license_part_ver)){
		//Before RTM 10.2.0.1, license plugin partition version flag use 'gridlicense_db_version'
		$license_part_ver = read_config_option('gridlicense_db_version');
		if(!empty($license_part_ver)){
			set_config_option('license_part_version', $license_part_ver);
		}
	}
	if (empty($license_part_ver)) {
		//The first partition upgrade design come from 9.1.2
		$license_part_ver = RTM_VERSION_SUPPORTED_MIN;
	}

	$license_current_version = get_license_version();

	$upgrade_state = read_config_option('lic_partitions_upgrade_status', true);

	if(empty($force_ver) && rtm_version_compare($license_part_ver, $license_current_version) == 0){
		if ((!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
			cacti_log('LICENSE PARTITION DBUPGRADE - NOTICE: Altering partitioned tables already done!', true);
			set_config_option('lic_partitions_upgrade_status', 'done');
		}
		return;
	}

	if(!rtm_plugin_upgrade_partition_check($license_part_ver, $license_current_version, 'license', $license_part_ver_min)){
		if ((!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
			cacti_log("LICENSE PARTITION DBUPGRADE - INFO: Partitioned tables are not required to be alterred from '$license_part_ver' to '$license_current_version'.", true);
			set_config_option('lic_partitions_upgrade_status', 'done');
			set_config_option('license_part_version', $license_current_version);
		}
		return;
	}

	if(!$force && empty($upgrade_state)){
		cacti_log('LICENSE PARTITION DBUPGRADE - NOTICE: upgrading partitioned tables not scheduled!', true);
	}

	cacti_log ("LICENSE PARTITION DBUPGRADE - INFO: Initial License plugin partition upgrader version flag: license_part_version=$license_part_ver");

	$database_maint_time = strtotime(read_config_option('lic_db_maint_time', true));
	if (!strlen(read_config_option('lic_db_maint_time', true))) {
		$database_maint_time = strtotime(read_default_config_option('lic_db_maint_time'));
	}

	if (($database_maint_time -3600 >= $current_time || $current_time >= $database_maint_time+3600) &&
		(!empty($upgrade_state) && ($upgrade_state == 'scheduled' || $upgrade_state == 'started' )) || $force) {
		if ($upgrade_state == 'scheduled') {
			set_config_option('lic_partitions_upgrade_status', 'started');
		}
		if (detect_and_correct_running_processes('0', 'LICENSE_PARTITION_DBUPGRADE', 99999999) == true ) {
			if (rtm_plugin_upgrade_partition($license_part_ver, $license_current_version, 'license', $license_part_ver_min) != DB_STATUS_ERROR ) {
				set_config_option('lic_partitions_upgrade_status', 'done');
				set_config_option('license_part_version', $license_current_version);
				cacti_log('LICENSE PARTITION DBUPGRADE - NOTICE: Altering partitioned tables completed.', true);
			} else {
				cacti_log("LICENSE PARTITION DBUPGRADE - ERROR: License data upgrade error. RTM remained in license data upgrade mode. Please check cacti.log and database tables.");
			}
			remove_process_entry('0', 'LICENSE_PARTITION_DBUPGRADE');
		}
	}
}

function display_version() {
	print 'IBM Spectrum LSF RTM Partition Table Upgrader ' . get_license_version() . "\n";
	print rtm_copyright();
}

function display_help () {
	print "database_upgrade_partitions.php " . get_license_version() . "\n\n";
	print "Upgrade License Monitor plugin dababase partition tables.\n";
	print "Usage:\n";
	print "database_upgrade_partitions.php [--force-ver=VER] [-f|--force]\n";
	print "database_upgrade_partitions.php [-h|-H|--help] [-v|-V|--version]\n";
	print "database_upgrade_partitions.php [-s|--schedule ]\n\n";
	print "-h|-H|--help     - Display this help and exit.\n";
	print "-v|-V|--version  - Output version information and exit.\n";
	print "-f|--force       - Force the execution of the upgrade process for the database partition tables.\n";
	print "--force-ver      - Force the upgrade of the database partion tables from specified version number.\n";
	print "-s|--schedule    - Schedule the upgrade start time before or after maintanance 1 hour, and exit.\n";
}
