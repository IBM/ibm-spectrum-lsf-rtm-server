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
	print call_user_func_array('ss_grid_cpu', $_SERVER['argv']);
}

function ss_grid_cpu($hostname = '', $clusterid = 0, $summary = 'no') {
	global $config;

	if ($hostname == '' || $hostname == 'localhost' || $summary == 'yes') {
		if (read_config_option('grid_cpu_leveling') == 'on') {
			if ($clusterid == 0) {
				$job_stats = db_fetch_row("SELECT
					SUM(cpuFactor*maxCpus*ut) / SUM(cpuFactor*maxCpus) AS a_ut
					FROM grid_hostinfo AS ghi
					LEFT JOIN grid_load AS gl
					ON ghi.host = gl.host
					AND ghi.clusterid = gl.clusterid
					WHERE isServer = '1'
					AND gl.status NOT LIKE 'U%'");
			} else {
				$job_stats = db_fetch_row_prepared("SELECT
					SUM(cpuFactor*maxCpus*ut) / SUM(cpuFactor*maxCpus) AS a_ut
					FROM grid_hostinfo AS ghi
					LEFT JOIN grid_load AS gl
					ON ghi.host = gl.host
					AND ghi.clusterid = gl.clusterid
					WHERE isServer = '1'
					AND gl.clusterid = ?
					AND gl.status NOT LIKE 'U%'",
					array($clusterid));
			}
		} else {
			if ($clusterid == 0) {
				$job_stats = db_fetch_row("SELECT
					SUM(maxCpus*ut) / SUM(maxCpus) AS a_ut
					FROM grid_hostinfo AS ghi
					LEFT JOIN grid_load AS gl
					ON ghi.host=gl.host
					AND ghi.clusterid=gl.clusterid
					WHERE isServer='1'
					AND gl.status NOT LIKE 'U%'");
			} else {
				$job_stats = db_fetch_row_prepared("SELECT
					SUM(maxCpus*ut) / SUM(maxCpus) AS a_ut
					FROM grid_hostinfo AS ghi
					LEFT JOIN grid_load AS gl
					ON ghi.host=gl.host
					AND ghi.clusterid=gl.clusterid
					WHERE isServer='1'
					AND gl.clusterid = ?
					AND gl.status NOT LIKE 'U%'",
					array($clusterid));
			}
		}
	} else {
		$job_stats = db_fetch_row_prepared("SELECT ut as a_ut
			FROM grid_load
			WHERE status NOT LIKE 'U%'
			AND clusterid = ?
			AND host = ?",
			array($clusterid, $hostname));
	}

	if (empty($job_stats['a_ut'])) $job_stats['a_ut'] = '0';

	$result = 'ut:' . $job_stats['a_ut'];

	return $result;
}
