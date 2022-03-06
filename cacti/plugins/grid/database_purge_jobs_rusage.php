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
include(dirname(__FILE__) . '/../../lib/rrd.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug   = false;
$confirm = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
		$debug = true;
		break;
	case '--confirm':
		$confirm = true;
		break;
	case '-h':
	case '-H':
	case '--help':
	case '-v':
	case '-V':
	case '--version':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

if (!$confirm) {
	print "ERROR: You must use the '--confirm' command line option to execute\n";
	print "       this script.\n\n";
	display_help();
	exit(1);
}

print "Removing old Job and RUsage Records\n";

/* remove stale records from the poller database */
$detail_retention = read_config_option('grid_detail_data_retention');

if (strlen($detail_retention)) {
	$detail_retention_date = date('Y-m-d H:i:s', strtotime("-$detail_retention"));
} else {
	$detail_retention_date = date('Y-m-d H:i:s', strtotime('-2 Months'));
}

/* how many records do we delete per pass */
$delete_size = read_config_option('grid_db_maint_delete_size');

/* remove stale records from the poller database */
$summary_retention = read_config_option('grid_summary_data_retention');

if (strlen($summary_retention)) {
	$summary_retention_date = date('Y-m-d H:i:s', strtotime('-' . $summary_retention));
} else {
	$summary_retention_date = date('Y-m-d H:i:s', strtotime('-1 Year'));
}

/* let's delete old records, but let's not delete records that are not finished */
grid_debug('Started deleting old records from the main jobs database.');

$rrd_cache_dir     = read_config_option('grid_cache_dir');
$unlink_count      = 0;
$jobs_rows_deleted = 0;

/* create temporary table to handle deleted records */
db_execute("CREATE TEMPORARY TABLE `simon` (
	`jobid` bigint(20) unsigned NOT NULL default '0',
	`indexid` int(10) unsigned NOT NULL default '0',
	`clusterid` int(10) unsigned NOT NULL default '0',
	`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	`last_updated` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`),
	KEY `alt_primary` (`clusterid`,`jobid`,`indexid`,`submit_time`)) ENGINE=InnoDB;");

db_execute("CREATE TEMPORARY TABLE `gus` (
	`jobid` bigint(20) unsigned NOT NULL default '0',
	`indexid` int(10) unsigned NOT NULL default '0',
	`clusterid` int(10) unsigned NOT NULL default '0',
	`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	`end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
	`last_updated` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`clusterid`,`jobid`,`indexid`,`submit_time`),
	KEY `alt_primary` (`clusterid`,`jobid`,`indexid`,`submit_time`)) ENGINE=InnoDB;");

/* add jobs to be deleted into the temporary table */
grid_debug('Placing job records whose full details are to be purged into temporary table for future use.');
db_execute_prepared("INSERT INTO `simon` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
	SELECT
		jobid,
		indexid,
		clusterid,
		submit_time,
		end_time,
		last_updated
	FROM grid_jobs_finished
	WHERE end_time < ?", array($summary_retention_date));

grid_debug("There are '" . db_affected_rows() . "' Job Records to Delete");

$max_date = db_fetch_cell("SELECT MAX(end_time) FROM simon");

/* add jobs whose rusage must be purged to the gus table */
grid_debug('Placing job records whose rusage is to be purged into temporary table for future use.');
db_execute_prepared("INSERT INTO `gus` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
	SELECT
		jobid,
		indexid,
		clusterid,
		submit_time,
		end_time,
		last_updated
	FROM grid_jobs_finished
	WHERE end_time < ?", array($detail_retention_date));

cacti_log("NOTE: The last date for Job Records will be '$summary_retention_date'", true, "GRID");

if (strlen($max_date)) {
	while (1) {
		grid_debug("Deleting <= '$delete_size' Records from grid_jobs_finished");

		/* delete from the jobs table */
		db_execute_prepared("DELETE FROM grid_jobs_finished
			WHERE end_time < ?
			LIMIT $delete_size", array($max_date));

		/* get the number of deleted rows */
		$jobs_rows_deleted += db_affected_rows();

		if ($jobs_rows_deleted == 0) {
			break;
		}
	}
} else {
	grid_debug("No Job Records found to Delete");
}

/* delete from the arrays */
grid_debug("Deleting Job Array Records");
db_execute("DELETE FROM grid_arrays_finished USING grid_arrays, simon
	WHERE grid_arrays.clusterid=simon.clusterid
	AND grid_arrays.jobid=simon.jobid
	AND grid_arrays.submit_time=simon.submit_time");

/* delete from the reqhosts table */
grid_debug("Deleting Job Requested Host Records");
db_execute("DELETE FROM grid_jobs_reqhosts_finished USING grid_jobs_reqhosts, simon
	WHERE grid_jobs_reqhosts.clusterid=simon.clusterid
	AND grid_jobs_reqhosts.jobid=simon.jobid
	AND grid_jobs_reqhosts.indexid=simon.indexid
	AND grid_jobs_reqhosts.submit_time=simon.submit_time");

/* delete from the fromhosts table */
grid_debug("Deleting Job Execution Host Records");
db_execute("DELETE FROM grid_jobs_jobhosts_finished USING grid_jobs_jobhosts, simon
	WHERE grid_jobs_jobhosts.clusterid=simon.clusterid
	AND grid_jobs_jobhosts.jobid=simon.jobid
	AND grid_jobs_jobhosts.indexid=simon.indexid
	AND grid_jobs_jobhosts.submit_time=simon.submit_time");

/* let's make sure we don't delete any rusage records for running jobs */
/* this query could take a while with 500k+ job records */
grid_debug("Start deleting job rusage records.");

grid_debug("Resetting Temporary Tables");
db_execute("TRUNCATE simon");
db_execute("INSERT INTO simon SELECT * FROM gus");
db_execute("TRUNCATE gus");

/* start rrdtool, in case we are making rrdfiles */
$rrdtool_pipe = rrd_init();

$rusage_jobs   = db_fetch_cell('SELECT COUNT(*) FROM simon');
$rusage_purged = 0;
$modulus       = 1000;

grid_debug('Deleting Rusage records now.');
if ($rusage_jobs > 0) {
	if (strtolower(read_config_option('grid_archive_rrd_files')) == 'on') {
		$i = 0;
		while (1) {
			$job = db_fetch_row('SELECT * FROM simon LIMIT 1');
			if (db_affected_rows() == 0) {
				break;
			}

			/* create/update RRD files if the user has choosen to do so */
			$rusage_records = db_fetch_row_prepared("SELECT *
				FROM grid_jobs_rusage
				WHERE clusterid=?
				AND jobid=?
				AND indexid=?
				AND submit_time=?
				LIMIT 1", array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

			if ($rusage_records) {
				grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
					strtotime($job['submit_time']), 'relative', $rrdtool_pipe);
				grid_maint_update_job_rrds($job['clusterid'], $job['jobid'], $job['indexid'],
					strtotime($job['submit_time']), 'absolute', $rrdtool_pipe);

				db_execute_prepared("DELETE
					FROM grid_jobs_rusage
					WHERE clusterid=?
					AND jobid=?
					AND indexid=?
					AND submit_time=?",
					array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

				$rusage_purged += db_affected_rows();
			}

			db_execute_prepared("DELETE
				FROM simon
				WHERE clusterid=?
				AND jobid=?
				AND indexid=?
				AND submit_time=?",
				array($job['clusterid'], $job['jobid'], $job['indexid'], $job['submit_time']));

			$i++;

			if (($i % $modulus) == 0) {
				grid_debug("Checked '$i' Job Records for RUsage Data");
			}
		}
	} else {
		while (1) {
			/* add jobs to be deleted into the temporary table */
			db_execute("INSERT INTO `gus` (jobid, indexid, clusterid, submit_time, end_time, last_updated)
				SELECT
					jobid,
					indexid,
					clusterid,
					submit_time,
					end_time,
					last_updated
				FROM simon
				LIMIT $delete_size");

			if (db_affected_rows() == 0) {
				break;
			}

			grid_debug("Deleting <= '$delete_size' Job Rusage Records from grid_jobs_rusage");

			/* delete rusage records */
			db_execute('DELETE FROM grid_jobs_rusage
				USING grid_jobs_rusage, gus
				WHERE grid_jobs_rusage.clusterid=gus.clusterid
				AND grid_jobs_rusage.jobid=gus.jobid
				AND grid_jobs_rusage.indexid=gus.indexid
				AND grid_jobs_rusage.submit_time=gus.submit_time');

			$rusage_purged += db_affected_rows();

			/* delete temporary records */
			db_execute('DELETE FROM simon
				USING simon, gus
				WHERE simon.clusterid=gus.clusterid
				AND simon.jobid=gus.jobid
				AND simon.indexid=gus.indexid
				AND simon.submit_time=gus.submit_time');

			db_execute('TRUNCATE TABLE gus');
		}
	}
} else {
	grid_debug('No more records found to delete, continuing');
}

/* remove temporary table */
db_execute('DROP TABLE IF EXISTS simon');
db_execute('DROP TABLE IF EXISTS gus');

/* provide a message for user */
print "Jobs Deleted: '" . $jobs_rows_deleted . "'\nRusage Delete: '" . $rusage_purged . "\n";

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Job and Rusage Purger ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "database_purge_jobs_rusage.php --confirm [-d] [-h] [--help] [-v] [-V] [--version]\n\n";
	print "--confirm        - Without confirm, this message will be displayed\n";
	print "-d               - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

