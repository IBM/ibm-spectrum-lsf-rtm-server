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

include(dirname(__FILE__) . "/../../include/cli_check.php");
include_once($config["library_path"] . '/rtm_db_upgrade.php');
include_once($config["library_path"] . '/rtm_functions.php');


/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

        switch ($arg) {
                case "-h":
                case "-v":
                case "-V":
                case "--version":
                case "--help":
                        display_version();
                        exit;
        case "-p":
            upgrade_gridpend_table();
            exit;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
			display_version();
			exit;
        }
}

/* set execution params */
ini_set("max_execution_time", "0");
ini_set("memory_limit", "-1");

upgrade_gridpend_table();

function upgrade_gridpend_table(){
    cacti_log("GRIDPEND_BUPGRADE - Running background gridpend data upgrade");
	if (detect_and_correct_running_processes("0", "GRIDPEND_DBUPGRADE", 9999) == TRUE ) {
		$bg_version = db_fetch_cell("SELECT value FROM settings WHERE name='gridpend_db_version'");
		if(empty($bg_version)){
			cacti_log ("Initial gridpend_db_version");
		}else{
			cacti_log ("Initial gridpend_db_version=$bg_version");
		}

		if (!isset($bg_version) || rtm_version_compare($bg_version, "10.1.0.0") < 0){
			$bg_version = '10.1.0.0';
			cacti_log("GRIDPEND_DBUPGRADE - set RTM to GridPend data upgrade mode.");
			$out=upgrade_gridpend_10_1_0_0();
			if ($out == 0 ){
				cacti_log("GRIDPEND_DBUPGRADE - GridPend tables conversion successful, set RTM to normal mode");
			}else{
				cacti_log("GRIDPEND_DBUPGRADE - ERROR: GridPend data upgrade error: code($out). RTM remained in license data upgrade mode. Please check database tables.");
                remove_process_entry("0", "GRIDPEND_DBUPGRADE");
                return 1;
            }

		}
	}
    remove_process_entry("0", "GRIDPEND_DBUPGRADE");
	cacti_log("GRIDPEND_DBUPGRADE - End of script.");
	return 0;
}

function upgrade_gridpend_10_1_0_0(){
	//Simiplied Pend Reason Project
	execute_sql("Modify table grid_jobs_pendhist_daily",
		"ALTER TABLE `grid_jobs_pendhist_daily` ADD COLUMN `detail_type` varchar(15) NOT NULL DEFAULT '' AFTER `subreason`");
	execute_sql("Modify table grid_jobs_pendhist_hourly",
		"ALTER TABLE `grid_jobs_pendhist_hourly` ADD COLUMN `detail_type` varchar(15) NOT NULL DEFAULT '' AFTER `subreason`");
	execute_sql("Modify table grid_jobs_pendhist_hourly",
		"ALTER TABLE `grid_jobs_pendhist_yesterday` ADD COLUMN `detail_type` varchar(15) NOT NULL DEFAULT '' AFTER `subreason`");

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridpend_db_version', '10.1.0.0')");
	return 0;
}

function display_version() {
    $version = read_config_option('grid_version');
    print "RTM gridpend plugin database upgrade Utility, Version $version, " . read_config_option('grid_copyright_year') . "\n";
}
