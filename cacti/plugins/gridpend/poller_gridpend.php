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

global $cnn_id;

/* take the start time to log performance data */
$start = microtime(true);

/* get the srm polling cycle */
ini_set("max_execution_time", "0");
gridpend_memory_limit();

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$debug          = FALSE;
$forcerun       = FALSE;
$forcerun_maint = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-f":
	case "--force":
		$forcerun = TRUE;
		break;
	case "-fm":
	case "--force-maint":
		$forcerun_maint = TRUE;
		break;
	case "-h":
	case "-v":
	case "-V":
	case "--version":
	case "--help":
		display_help();
		exit;
	default:
		print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}
if (read_config_option('grid_collection_enabled') != 'on') {
	gridpend_debug('DB schema upgrade in process. Gridpend poller exit.');
	exit;
}

/* record the start time */
$start = microtime(true);

/* we have two time counters.  The first determines the maintenance time
 * the second determines how far to look back depending on the number
 * of finished jobs.
 */

/* this time counter will tell us if its time to run maintenance */
$prev_start = read_config_option('gridpend_last_runtime');
if (empty($prev_start)) {
	$prev_start = time() - 300;
}
$run_time = time();
db_execute_prepared("REPLACE INTO settings (name, value) VALUES ('gridpend_last_runtime', ?)", array($run_time));

/* this counter tells us how far to look back for finished jobs */
$last_start_time = read_config_option("gridpend_last_start");
$now             = time();
$base_start_time = $now - 300;
if (empty($last_start_time)) {
	$last_start_time = $now - 3600;
	$start_time      = $base_start_time;
}else{
	$start_time      = $last_start_time + 300;

	/* check for a delayed poller run, and force runs, then update the last_start */
	if ($start_time > $base_start_time) {
		$start_time = $base_start_time;
	}elseif ($start_time < $base_start_time && $base_start_time > $last_start_time) {
		$start_time = $base_start_time;
	}
}
gridpend_debug("DEBUG: Job End Time Start: " . date("Y-m-d H:i:s", $last_start_time));
gridpend_debug("DEBUG: Job End Time   End: " . date("Y-m-d H:i:s", $start_time));
db_execute_prepared("REPLACE INTO settings (name, value) VALUES ('gridpend_last_start', ?)", array($start_time));

/* see if it's time for maintenance too */
if (date("d", $prev_start) != date("d", $run_time)) {
	$run_maint = true;
	$year_day = date("Y", $prev_start) . substr("000" . date("z", $prev_start), -3);
}elseif ($forcerun_maint) {
	$run_maint = true;
	$year_day = date("Y", $prev_start) . substr("000" . date("z", $prev_start), -3);
}else{
	$run_maint = false;
}

/* convert start time and end time to dates for mysql */
$sql_start_time = date("Y-m-d H:i:s", $start_time);
$sql_last_time  = date("Y-m-d H:i:s", $last_start_time);

/* lets send a debug message */
gridpend_debug("DEBUG: About to Enter GRIDPEND Poller Processing");

if (read_config_option("gridpend_include_exited") == "on") {
	$stat = "'DONE', 'EXIT'";
}else{
	$stat = "'DONE'";
}

if ($run_maint) {
	$records = db_fetch_assoc_prepared("SELECT *
		FROM
			(SELECT
				reason,
				subreason,
				detail_type,
				clusterid,
				stat,
				projectName,
				queue,
				user,
				SUM(total_pend) AS total_pend,
				SUM(total_slots) AS total_slots,
				? AS year_day
			FROM grid_jobs_pendhist_hourly
			GROUP BY reason, subreason, stat, clusterid, projectName, queue, user) AS pend", array($year_day));

	$mrecords = pump_records($records, "grid_jobs_pendhist_daily", array("reason", "subreason","detail_type", "clusterid", "stat", "projectName", "queue", "user", "total_pend", "total_slots", "year_day"));

	$interim_table = 'grid_jobs_pendhist_hourly_' . time();
	db_execute("DROP TABLE IF EXISTS grid_jobs_pendhist_yesterday");
	db_execute("CREATE TABLE IF NOT EXISTS $interim_table LIKE grid_jobs_pendhist_hourly");
	db_execute("RENAME TABLE grid_jobs_pendhist_hourly TO grid_jobs_pendhist_yesterday, $interim_table TO grid_jobs_pendhist_hourly");

	/* remove old records */
	$retention_time = strtotime("-" . read_config_option("grid_daily_data_retention"));
	$retention_year = date("Y", $retention_time);
	$retention_day  = substr("000" . date("z", $retention_time), "-3");
	$purge_year_day = $retention_year . $retention_day;

	db_execute_prepared("DELETE FROM grid_jobs_pendhist_daily WHERE year_day<?", array($purge_year_day));

	$drecords = db_affected_rows();

	/* record the end time */
	$maint = microtime(true);

	$log_text = sprintf("Time:%01.4f Records:%s Purged:%s", ($maint-$start), $mrecords, $drecords);

	cacti_log("STATS GRIDPEND MAINT: $log_text", false, "SYSTEM");
	echo "STATS MAINT: $log_text\n";
}else{
	$maint = 0;
}

$records = db_fetch_assoc_prepared("SELECT *
	FROM
		(SELECT
			gjp.reason,
			gjp.subreason,
			substring_index(gjp.detail,':',1) as detail_type,
			gj.clusterid,
			gj.stat,
			gj.projectName,
			gj.queue,
			gj.user,
			SUM(UNIX_TIMESTAMP(gjp.end_time)-UNIX_TIMESTAMP(gjp.start_time)) AS total_pend,
			SUM(gj.maxNumProcessors) AS total_slots,
			NOW() AS date_recorded
		FROM grid_jobs AS gj
		INNER JOIN grid_jobs_pendreasons AS gjp
		ON gj.clusterid=gjp.clusterid AND gj.jobid=gjp.jobid AND gj.indexid=gjp.indexid AND gj.submit_time=gjp.submit_time
		WHERE gj.start_time>'1971-01-01'
		AND gj.stat IN ($stat)
		AND gj.end_time BETWEEN ? AND ?
		GROUP BY reason, subreason, stat, clusterid, projectName, queue, user) AS pend
	WHERE total_pend>0", array($sql_last_time, $sql_start_time));

$precords = pump_records($records, "grid_jobs_pendhist_hourly", array("reason", "subreason", "detail_type", "clusterid", "stat", "projectName", "queue", "user", "total_pend", "total_slots", "date_recorded"));

if ($precords == 0) {
	db_execute_prepared("REPLACE INTO settings (name, value) VALUES ('gridpend_last_start', ?)", array($last_start_time));
	gridpend_debug("DEBUG: No Records Found Therefore, Reverting Last Start Time to Previous");
}

/* record the end time */
$end = microtime(true);

$log_text = sprintf("Time:%01.4f Records:%s", ($end-$start), $precords);
cacti_log("STATS GRIDPEND: $log_text", false, "SYSTEM");
echo "STATS: $log_text\n";

function pump_records($records, $table, $format = array()) {
	global $cnn_id;

	$first      = true;
	$sql_prefix = "INSERT INTO $table (";
	$sql_out    = array();
	$columns    = array();

	if (cacti_sizeof($format)) {
		$sql_prefix .= implode(",", $format) . ") VALUES ";
		$first = false;
		$columns = $format;
	}

	if (cacti_sizeof($records)) {
		foreach($records as $r) {
			if ($first) {
				$columns = array_keys($r);
				foreach($columns as $c) {
					$sql_prefix .= ($first ? $c:",$c");
					$first = false;
				}
				$sql_prefix .= ") VALUES ";
			}

			$j = 0;
			$sql = "";
			foreach($columns as $c) {
				$sql .= ($j == 0 ? "(":",") . db_qstr($r[$c]);
				$j++;
			}
			$sql .= ")";
			$sql_out[] = $sql;
		}

		$i=0;

		$new_sql = array_chunk($sql_out, 500);
		foreach($new_sql as $sql) {
			$osql = implode(",", $sql);
			db_execute("$sql_prefix $osql");
		}
	}

	return cacti_sizeof($records);
}

function gridpend_debug($message) {
	global $debug;

	if ($debug) {
		echo $message . "\n";
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	echo "RTM Pending Reason Poller " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	echo "Usage:\n";
	echo "poller_gridpend.php [--force] [--force-maint] [--debug] [--help]\n\n";
	echo "--force       - Force execution even though it's not time\n";
	echo "--force-maint - For maintenance execution even though it's not time\n";
	echo "--debug       - Display verbose output during execution\n";
	echo "--help        - Display this help message\n";
}

?>
