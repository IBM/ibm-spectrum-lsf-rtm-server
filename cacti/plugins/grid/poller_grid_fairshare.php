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

include_once(dirname(__FILE__) . "/../../include/cli_check.php");
include_once(dirname(__FILE__) . "/lib/grid_functions.php");
include_once($config['library_path'] . '/rtm_functions.php');

global $debug;

$debug = false;

/* this needs lot's of memory */
ini_set("memory_limit", "-1");

$poller_interval = read_config_option("poller_interval");

if ((read_config_option("grid_system_collection_enabled") == "on") &&
	(read_config_option("grid_collection_enabled") == "on")) {
	if (detect_and_correct_running_processes(0, "GRID_FAIRSHARE", $poller_interval*3)) {

		/* take the start time to log performance data */
		$start = microtime(true);

		/* determine fairshare tree statistics */
		update_fairshare_tree_information();

		/* take the end time to log performance data */
		$end = microtime(true);

		$cacti_stats = sprintf("Time:%01.4f", round($end-$start,4));
		cacti_log("FAIRSHARE STATS: " . $cacti_stats ,true,"SYSTEM");

		/* remove the process entry */
		remove_process_entry(0, "GRID_FAIRSHARE");
	}
}

