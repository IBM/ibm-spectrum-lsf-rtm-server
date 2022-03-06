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
include(dirname(__FILE__) . "/lib/grid_partitioning.php");
include_once($config['base_path'] . '/lib/rtm_functions.php');

$debug = false;

/* this needs lot's of memory */
ini_set("memory_limit", "-1");

if (read_config_option("gridmemvio_enabled") == "on") {
		if (detect_and_correct_running_processes(0, "GRIDMEMVIO_NOTIFY", 3600*3)) {
			$range = read_config_option("gridmemvio_schedule");
			if ($range == 604800 ) {
				if (date("N") == 7) {
					gridmemvio_notify_users();
					db_execute("DELETE FROM grid_jobs_memvio WHERE notified=1");
				}
			}elseif ($range == 86400) {
				cacti_log("send memvio notification to users..");
				gridmemvio_notify_users();
				db_execute("DELETE FROM grid_jobs_memvio WHERE notified=1");
			}else {
				db_execute("truncate table grid_jobs_memvio;");
			}
			remove_process_entry(0, "GRIDMEMVIO_NOTIFY");
		}
}

