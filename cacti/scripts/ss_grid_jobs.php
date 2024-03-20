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
	print call_user_func_array('ss_grid_jobs', $_SERVER['argv']);
}

function ss_grid_jobs($hostname = '', $clusterid = 0, $summary='no') {
	if ($hostname == '' || $hostname == 'localhost' || $summary == 'yes') {
		if ($clusterid == 0) {
			$job_stats = db_fetch_row("SELECT " . SQL_NO_CACHE . " SUM(maxJobs) as t_maxJobs, SUM(numJobs) as t_numJobs,
				SUM(numRun) as t_numRun, SUM(numSSUSP) as t_numSSUSP, SUM(numUSUSP) as t_numUSUSP,
				SUM(numRESERVE) as t_numRESERVE
				FROM grid_hosts
				WHERE status != 'Closed-Admin'");
		} else {
			$job_stats = db_fetch_row_prepared("SELECT " . SQL_NO_CACHE . " SUM(maxJobs) as t_maxJobs, SUM(numJobs) as t_numJobs,
				SUM(numRun) as t_numRun, SUM(numSSUSP) as t_numSSUSP, SUM(numUSUSP) as t_numUSUSP,
				SUM(numRESERVE) as t_numRESERVE
				FROM grid_hosts
				WHERE clusterid = ?
				AND status != 'Closed-Admin'",
				array($clusterid));
		}
	} else {
		if ($clusterid == 0) {
			$job_stats = db_fetch_row_prepared("SELECT " . SQL_NO_CACHE . " SUM(maxJobs) as t_maxJobs, SUM(numJobs) as t_numJobs,
				SUM(numRun) as t_numRun, SUM(numSSUSP) as t_numSSUSP, SUM(numUSUSP) as t_numUSUSP,
				SUM(numRESERVE) as t_numRESERVE
				FROM grid_hosts
				WHERE host = ?
				AND status != 'Closed-Admin'",
				array($hostname));
		} else {
			$job_stats = db_fetch_row_prepared("SELECT " . SQL_NO_CACHE . " maxJobs as t_maxJobs, numJobs as t_numJobs,
				numRun as t_numRun, numSSUSP as t_numSSUSP, numUSUSP as t_numUSUSP,
				numRESERVE as t_numRESERVE
				FROM grid_hosts
				WHERE clusterid = ?
				AND host = ?",
				array($clusterid, $hostname));
		}
	}

	if (empty($job_stats['t_maxJobs']))    $job_stats['t_maxJobs']    = 0;
	if (empty($job_stats['t_numJobs']))    $job_stats['t_numJobs']    = 0;
	if (empty($job_stats['t_numRun']))     $job_stats['t_numRun']     = 0;
	if (empty($job_stats['t_numSSUSP']))   $job_stats['t_numSSUSP']   = 0;
	if (empty($job_stats['t_numUSUSP']))   $job_stats['t_numUSUSP']   = 0;
	if (empty($job_stats['t_numRESERVE'])) $job_stats['t_numRESERVE'] = 0;

	$result =
		'max_jobs:'      . (!empty($job_stats['t_maxJobs']) ? $job_stats['t_maxJobs'] : 0)   . ' ' .
		'num_jobs:'      . (!empty($job_stats['t_numJobs']) ? $job_stats['t_numJobs'] : 0)   . ' ' .
		'run_jobs:'      . (!empty($job_stats['t_numRun']) ? $job_stats['t_numRun'] : 0)     . ' ' .
		'sys_susp_jobs:' . (!empty($job_stats['t_numSSUSP']) ? $job_stats['t_numSSUSP'] : 0) . ' ' .
		'usr_susp_jobs:' . (!empty($job_stats['t_numUSUSP']) ? $job_stats['t_numUSUSP'] : 0) . ' ' .
		'res_jobs:'      . (!empty($job_stats['t_numRESERVE']) ? $job_stats['t_numRESERVE'] : 0);

	return trim($result);
}
