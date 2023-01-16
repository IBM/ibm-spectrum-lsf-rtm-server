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
	print call_user_func_array('ss_grid_user_stats', $_SERVER['argv']);
}

function ss_grid_user_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_user_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_user_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_user_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_user_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_user_stats_getvalue($clusterid, $index, $column) {
	global $grid_date_recorded;

	$return_arr = array();
	$value = 0;

	switch ($column) {
		case 'numSTARTED':
		case 'numSUBMITTED':
		case 'numDONE':
		case 'numEXIT':
			if ($column == 'numSTARTED')       $column = 'STARTED';
			elseif ($column == 'numSUBMITTED') $column = 'SUBMITTED';
			elseif ($column == 'numDONE')      $column = 'ENDED';
			elseif ($column == 'numEXIT')      $column = 'EXITED';

			if (isset($grid_date_recorded[$clusterid])) {
				$max_date = $grid_date_recorded[$clusterid];
			} else {
				$max_date = db_fetch_cell_prepared('SELECT date_recorded AS date
					FROM grid_job_interval_stats
					WHERE clusterid = ?
					ORDER BY date_recorded DESC
					LIMIT 1',
					array($clusterid));

				if ($max_date == '') {
					$max_date = '2008-01-01 00:00:00';
				}

				$grid_date_recorded[$clusterid] = $max_date;
			}

			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 4*$poller_interval) > strtotime($max_date)) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared('SELECT SUM(jobs_reaching_state)
					FROM grid_job_interval_stats
					WHERE stat = ?
					AND clusterid = ?
					AND user = ?
					AND date_recorded = ?',
					array($column, $clusterid, $index, $max_date));
			}

			break;
		case 'numJobs':
			$value = db_fetch_cell_prepared('SELECT numJobs
				FROM grid_users_or_groups
				WHERE clusterid = ?
				AND user_or_group = ?',
				array($clusterid, $index));
			break;
		case 'numPEND':
			$value = db_fetch_cell_prepared('SELECT numPEND
				FROM grid_users_or_groups
				WHERE clusterid = ?
				AND user_or_group = ?',
				array($clusterid, $index));

			break;
		case 'numRUN':
			$value = db_fetch_cell_prepared('SELECT numRUN
				FROM grid_users_or_groups
				WHERE clusterid = ?
				AND user_or_group = ?',
				array($clusterid, $index));

			break;
		case 'numSUSP':
			$value = db_fetch_cell_prepared('SELECT numSSUSP + numUSUSP AS suspended
				FROM grid_users_or_groups
				WHERE clusterid = ?
				AND user_or_group = ?',
				array($clusterid, $index));

			break;
		case 'minEffic':
			$value = db_fetch_cell_prepared("SELECT MIN((cpu_used/run_time)*100)
				FROM grid_jobs
				WHERE ((stat = 'RUNNING' AND run_time > 120) OR (stat = 'DONE' AND UNIX_TIMESTAMP(end_time) + 300 > UNIX_TIMESTAMP()))
				AND clusterid = ?
				AND user = ?",
				array($clusterid, $index));

			break;
		case 'maxEffic':
			$value = db_fetch_cell_prepared("SELECT MAX((cpu_used/run_time)*100)
				FROM grid_jobs
				WHERE ((stat = 'RUNNING' AND run_time > 120) OR (stat = 'DONE' AND UNIX_TIMESTAMP(end_time) + 300 > UNIX_TIMESTAMP()))
				AND clusterid = ?
				AND user = ?",
				array($clusterid, $index));

			break;
		case 'avgEffic':
			$value = db_fetch_cell_prepared("SELECT (SUM(cpu_used)/SUM(run_time))*100
				FROM grid_jobs
				WHERE ((stat='RUNNING' AND run_time>120) OR (stat='DONE' AND UNIX_TIMESTAMP(end_time)+300>UNIX_TIMESTAMP()))
				AND clusterid = ?
				AND user = ?",
				array($clusterid, $index));

			break;
	}

	if ($value != '') {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_user_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared("SELECT user_or_group
		FROM grid_users_or_groups
		WHERE clusterid = ?
		AND type='U'
		ORDER BY user_or_group",
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['user_or_group'];
	}

	return $return_arr;
}

function ss_grid_user_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'user') {
		$arr = db_fetch_assoc_prepared("SELECT user_or_group AS qry_index,
			user_or_group AS qry_value
			FROM grid_users_or_groups
			WHERE clusterid = ?
			AND type='U'
			ORDER BY user_or_group",
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
