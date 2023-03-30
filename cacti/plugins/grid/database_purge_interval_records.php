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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
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
    }
}
/* remove stale records from the poller database */
$detail_retention = read_config_option('grid_detail_data_retention');

if (strlen($detail_retention)) {
	$detail_retention_date = date('Y-m-d H:i:s', strtotime($detail_retention));
} else {
	$detail_retention_date = date('Y-m-d H:i:s', strtotime('-2 Months'));
}

/* remove stale records from the poller database */
$summary_retention = read_config_option('grid_summary_data_retention');

if (strlen($summary_retention)) {
	$summary_retention_date = date('Y-m-d H:i:s', strtotime($summary_retention));
} else {
	$summary_retention_date = date('Y-m-d H:i:s', strtotime('-1 Year'));
}

grid_debug('Start deleting old job interval records from the job interval database.');

/* get an initial count of rows */
$begin_jobs_interval_stat_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_job_interval_stats');

if ($begin_jobs_interval_stat_rows > 100000) {
	/* for large systems, it is important to purge the grid_job_interval_stats daily */
	db_execute('CREATE TEMPORARY TABLE `gus` LIKE `grid_job_interval_stats`');
	db_execute('INSERT INTO `gus` SELECT * FROM `grid_job_interval_stats` WHERE date_recorded>NOW()-INTERVAL 1 hour');
	db_execute('TRUNCATE TABLE `grid_job_interval_stats`');
	db_execute('INSERT INTO `grid_job_interval_stats` SELECT * FROM `gus`');
	db_execute('DROP TABLE IF EXISTS `gus`');
} else {
	$delete_size = read_config_option("grid_db_maint_delete_size");
	while (1) {
		$rows_to_delete = db_fetch_cell_prepared('SELECT count(*)
			FROM grid_job_interval_stats
			WHERE date_recorded < ?',
			array($detail_retention_date));

		if ($rows_to_delete > 0) {
			db_execute_prepared("DELETE
				FROM grid_job_interval_stats
				WHERE date_recorded < ?
				LIMIT $delete_size",
				array($detail_retention_date));
		} else {
			break;
		}
	}
}

/* get a final count of rows */
$end_jobs_interval_stat_rows = db_fetch_cell('SELECT COUNT(*) FROM grid_job_interval_stats');

function display_version() {
    $version = read_config_option('grid_version');
    print "RTM Job Interval Stats Data Purge Utility, Version $version, " . read_config_option('grid_copyright_year') . "\n";
}
