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
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');
include_once($config['library_path'] . '/rtm_functions.php');

$debug = false;

/* this needs lot's of memory */

$poller_interval = read_config_option("poller_interval");

$current_time = time();

$start = microtime(true);

if ((read_config_option("grid_system_collection_enabled") == "on") &&
	(read_config_option("grid_collection_enabled") == "on")) {
	if (detect_and_correct_running_processes(0, "GRIDHOSTCLOSUREMOVE", $poller_interval*12)) {
		grid_debug("NOTE: GRIDHOSTCLOSUREMOVE Transferring Recently Completed Host Close Info to Finished Table");
		/* create temporary table to handle deleted records */
		db_execute('DROP TABLE IF EXISTS `tmp_grid_host_closure_events`');
		$success = db_execute("CREATE TEMPORARY TABLE `tmp_grid_host_closure_events` LIKE grid_host_closure_events");
		if(!$success){
			grid_debug("ERROR: CREATE TEMPORARY TABLE tmp_grid_host_closure_events Failed");
			return;
		}

		$success = db_execute("INSERT INTO tmp_grid_host_closure_events
			SELECT *
			FROM grid_host_closure_events
			WHERE end_time <> '0000-00-00 00:00:00'");
		if(!$success){
			grid_debug("ERROR: INSERT INTO TABLE tmp_grid_host_closure_events Failed");
			return;
		}

		$success = db_execute("INSERT IGNORE INTO grid_host_closure_events_finished
			SELECT *
			FROM tmp_grid_host_closure_events");
		if(!$success){
			grid_debug("ERROR: INSERT IGNORE INTO grid_host_closure_events_finished");
			return;
		}

		/* delete hostrusage records */
		db_execute("DELETE FROM current
			USING grid_host_closure_events AS current, tmp_grid_host_closure_events AS tmp
			WHERE current.clusterid = tmp.clusterid
			AND current.host = tmp.host
			AND current.event_time = tmp.event_time
			AND current.lockid = tmp.lockid");

		/* remove stale records from the poller database */
		$summary_retention = read_config_option('grid_summary_data_retention');

		if (strlen($summary_retention)) {
			$summary_retention_date = date('Y-m-d H:i:s', strtotime('-' . $summary_retention));
		} else {
			$summary_retention_date = date('Y-m-d H:i:s', strtotime('-1 Year'));
		}
		/* how many records do we delete per pass */
		$delete_size     = read_config_option('grid_db_maint_delete_size');
		$jobs_rows_deleted     = 0;
		if (read_config_option('grid_partitioning_enable') == '') {
			$close_events = db_fetch_assoc_prepared("SELECT * FROM grid_host_closure_events_finished WHERE end_time < ? LIMIT 1", array($summary_retention_date));
			if (cacti_sizeof($close_events)) {
				while (1) {
					grid_debug("Deleting <= '$delete_size' Records from grid_host_closure_events_finished");
					/* delete from the jobs table */
					db_execute_prepared("DELETE FROM grid_host_closure_events_finished
						WHERE end_time < ?
						LIMIT $delete_size", array($summary_retention_date));

					/* get the number of deleted rows */
					$jobs_rows_deleted += db_affected_rows();

					$close_events = db_fetch_assoc_prepared("SELECT * FROM grid_host_closure_events_finished WHERE end_time < ? LIMIT 1", array($summary_retention_date));
					if (cacti_sizeof($close_events) == 0) {
						break;
					}
				}
			} else {
				grid_debug('No Closure Event Records found to Delete');
			}
		} else {
			/* determine if a new partition needs to be created */
			if (partition_timefor_create('grid_host_closure_events_finished', 'end_time')) {
				partition_create('grid_host_closure_events_finished', 'event_time', 'end_time');
			}

			/* remove old partitions if required */
			grid_debug("Pruning Partitions for 'grid_host_closure_events_finished'");
			partition_prune_partitions('grid_host_closure_events_finished');
		}
		/* delete LockId */
		db_execute_prepared("DELETE FROM grid_host_closure_lockids WHERE last_seen < ?", array($summary_retention_date));
		/* remove the process entry */
		remove_process_entry(0, "GRIDHOSTCLOSUREMOVE");

		/* record the end time */
		$end                  = microtime(true);

		cacti_log("GRIDHOSTCLOSUREMOVE STATS: Time:" . round($end-$start,2) , true, "SYSTEM");
	}
}

