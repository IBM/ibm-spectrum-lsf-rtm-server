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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_timein_state', $_SERVER['argv']);
}

function ss_grid_timein_state($hostname = '', $clusterid = 0, $summary = 'no') {
	if ($hostname == '' || $hostname == 'localhost' || $summary == 'yes') {
		if ($clusterid == 0) {
			$timein_state = db_fetch_row_prepared('SELECT SUM(unavail) AS a_unavail, SUM(busyclose) AS a_busyclose,
				SUM(idleclose) AS a_idleclose, SUM(lowres) AS a_lowres, SUM(busy) AS a_busy,
				SUM(idlewjobs) AS a_idlewjobs, SUM(idle) AS a_idle, SUM(starved) AS a_starved,
				SUM(admindown) AS a_admindown, SUM(blackhole) AS a_blackhole
				FROM grid_summary_timein_state');
		} else {
			$timein_state = db_fetch_row_prepared('SELECT SUM(unavail) AS a_unavail, SUM(busyclose) AS a_busyclose,
				SUM(idleclose) AS a_idleclose, SUM(lowres) AS a_lowres, SUM(busy) AS a_busy,
				SUM(idlewjobs) AS a_idlewjobs, SUM(idle) AS a_idle, SUM(starved) AS a_starved,
				SUM(admindown) AS a_admindown, SUM(blackhole) AS a_blackhole
				FROM grid_summary_timein_state
				WHERE clusterid = ?',
				array($clusterid));
		}
	} else {
		if ($clusterid == 0) {
			$timein_state = db_fetch_row_prepared('SELECT SUM(unavail) AS a_unavail, SUM(busyclose) AS a_busyclose,
				SUM(idleclose) AS a_idleclose, SUM(lowres) AS a_lowres, SUM(busy) AS a_busy,
				SUM(idlewjobs) AS a_idlewjobs, SUM(idle) AS a_idle, SUM(starved) AS a_starved,
				SUM(admindown) AS a_admindown, SUM(blackhole) AS a_blackhole
				FROM grid_summary_timein_state
				WHERE host = ?',
				array($hostname));
		} else {
			$timein_state = db_fetch_row_prepared('SELECT unavail AS a_unavail, busyclose AS a_busyclose,
				idleclose AS a_idleclose, lowres AS a_lowres, busy AS a_busy, idlewjobs AS a_idlewjobs,
				idle AS a_idle, starved AS a_starved, admindown AS a_admindown, blackhole AS a_blackhole
				FROM grid_summary_timein_state
				WHERE clusterid = ?
				AND host = ?',
				array($clusterid, $hostname));
		}
	}

	if (empty($job_stats['a_unavail'])) $job_stats['a_unavail'] = 0;
	if (empty($job_stats['a_busyclose'])) $job_stats['a_busyclose'] = 0;
	if (empty($job_stats['a_idleclose'])) $job_stats['a_idleclose'] = 0;
	if (empty($job_stats['a_lowres'])) $job_stats['a_lowres'] = 0;
	if (empty($job_stats['a_busy'])) $job_stats['a_busy'] = 0;
	if (empty($job_stats['a_idlewjobs'])) $job_stats['a_idlewjobs'] = 0;
	if (empty($job_stats['a_idle'])) $job_stats['a_idle'] = 0;
	if (empty($job_stats['a_starved'])) $job_stats['a_'] = 0;
	if (empty($job_stats['a_'])) $job_stats['a_'] = 0;

	$result =
		'mem:'      . $job_stats['a_mem'] . ' ' .
		'swp:'      . $job_stats['a_swp']  . "\n";

	return trim($result);
}
