<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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
	print call_user_func_array('ss_grid_tp_queues', $_SERVER['argv']);
}

function ss_grid_tp_queues($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_tp_queues_getnames($clusterid, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_tp_queues_getnames($clusterid, $arg1);
		$arr = ss_grid_tp_queues_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_tp_queues_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_tp_queues_getvalue($clusterid, $index, $column) {
	$return_arr = array();

	if ($column == 'started')       $column = 'STARTED';
	elseif ($column == 'submitted') $column = 'SUBMITTED';
	elseif ($column == 'ended')     $column = 'ENDED';
	elseif ($column == 'exited')    $column = 'EXITED';

	$max_date = db_fetch_cell_prepared('SELECT Max(date_recorded)
		FROM grid_job_interval_stats
		WHERE clusterid = ?',
		array($clusterid));

	$poller_interval = read_config_option('poller_interval');
	if (empty($poller_interval)) { $poller_interval = 300; }

	if ((time() - $poller_interval) > strtotime($max_date)) {
		$value = 0;
	} else {
		if ($column != 'total') {
			$value = db_fetch_cell_prepared('SELECT SUM(jobs_reaching_state)
				FROM grid_job_interval_stats
				WHERE stat = ?
				AND clusterid = ?
				AND queue = ?
				AND date_recorded = ?
				GROUP BY queue',
				array($column, $clusterid, $index, $max_date));
		} else {
			$value = db_fetch_cell_prepared('SELECT SUM(jobs_reaching_state)
				FROM grid_job_interval_stats
				WHERE clusterid = ?
				AND queue = ?
				AND date_recorded = ?
				GROUP BY queue',
				array($clusterid, $index, $max_date));
		}
	}

	if (!empty($value)) {
		return $value;
	} else {
		return '0';
	}
}

function ss_grid_tp_queues_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT queue
		FROM grid_queues_stats
		WHERE clusterid = ?
		ORDER BY queue',
		array($clusterid));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['queue'];
	}

	return $return_arr;
}

function ss_grid_tp_queues_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'description') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, description AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gqs.clusterid = gq.clusterid
			AND gqs.queue = gq.queuename
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	} elseif ($info_requested == 'queue') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, queue AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gqs.clusterid = gq.clusterid
			AND gqs.queue = gq.queuename
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	} elseif ($info_requested == 'prio') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, priority AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gqs.clusterid = gq.clusterid
			AND gqs.queue = gq.queuename
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
