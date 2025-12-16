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

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_grid_queues', $_SERVER['argv']);
}

function ss_grid_queues($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {

	if ($cmd == 'index') {
		$return_arr = ss_grid_queues_getnames($clusterid);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_queues_getnames($clusterid);
		$arr = ss_grid_queues_getinfo($clusterid, $arg1);

		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_queues_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_queues_getvalue($clusterid, $queuename, $column) {
	$return_arr = array();

	if ($column == 'pending') $column = 'pendjobs';
	elseif ($column == 'running') $column = 'runjobs';
	elseif ($column == 'suspended') $column = 'suspjobs';
	elseif ($column == 'total') $column = 'nojobs';

	$arr = db_fetch_cell_prepared("SELECT
		$column
		FROM grid_queues
		WHERE clusterid = ?
		AND queuename = ?",
		array($clusterid, $queuename));

	if (empty($arr)) {
		$arr = 0;
	}

	return trim($arr);
}

function ss_grid_queues_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT queue
		FROM grid_queues_stats AS gqs
		LEFT JOIN grid_queues AS gq
		ON gq.queuename = gqs.queue
		AND gq.clusterid = gqs.clusterid
		WHERE gq.clusterid = ?
		ORDER BY queue',
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]["queue"];
	}

	return $return_arr;
}

function ss_grid_queues_getinfo($clusterid, $info_requested) {
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
			FROM grid_queues_stats
			LEFT JOIN grid_queues
			ON gqs.clusterid = gq.clusterid
			AND gqs.queue = gq.queuename
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	} elseif ($info_requested == 'prio') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, priority AS qry_value
			FROM grid_queues_stats
			LEFT JOIN grid_queues
			ON gqs.clusterid = gq.clusterid
			AND gqs.queue = gq.queuename
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
