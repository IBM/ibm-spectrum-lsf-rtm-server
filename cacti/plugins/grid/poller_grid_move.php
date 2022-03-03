#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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

include_once(dirname(__FILE__) . "/../../include/cli_check.php");
include_once(dirname(__FILE__) . "/lib/grid_functions.php");
include_once($config['library_path'] . '/rtm_functions.php');

$debug = false;

/* this needs lot's of memory */

$poller_interval = read_config_option("poller_interval");

$current_time = time();

$start = microtime(true);

if ((read_config_option("grid_system_collection_enabled") == "on") &&
	(read_config_option("grid_collection_enabled") == "on")) {
	if (detect_and_correct_running_processes(0, "GRIDMOVE", $poller_interval*12)) {
		move_records_into_finished_table();

		/* prune the jobs table of old records */
		delete_records_from_jobs_table($current_time, $start);

		/* remove the process entry */
		remove_process_entry(0, "GRIDMOVE");

		/* record the end time */
		$end                  = microtime(true);

		cacti_log("GRIDMOVE STATS: Time:" . round($end-$start,2) , true, "SYSTEM");
	}
}

