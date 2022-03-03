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
	print call_user_func_array('ss_grid_fstree', $_SERVER['argv']);
}

function ss_grid_fstree($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_fstree_getnames($clusterid, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_fstree_getnames($clusterid, $arg1);
		$arr = ss_grid_fstree_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_fstree_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_fstree_getvalue($clusterid, $index, $column) {
	global $grid_date_recorded;

	$return_arr = array();

	switch ($column) {
		case 'shares':
		case 'priority':
		case 'started':
		case 'reserved':
		case 'cpu_time':
		case 'run_time':
		case 'run_jobs':
		case 'run_slots':
		case 'pend_jobs':
		case 'pend_slots':
			if (isset($grid_date_recorded[$clusterid])) {
				$max_date = $grid_date_recorded[$clusterid];
			} else {
				$sql_string = "SELECT date_recorded AS date FROM grid_job_interval_stats WHERE clusterid=$clusterid ORDER BY date_recorded DESC LIMIT 1";
				$max_date = db_fetch_cell($sql_string);

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
				$parts = explode('|', $index);
				$queue = $parts[0];
				$shareAcctPath = $parts[1];
				$acctpaths = explode('/',$shareAcctPath);
				$group = end($acctpaths);

				$value = db_fetch_cell("SELECT $column
					FROM grid_queues_shares
					WHERE clusterid=$clusterid
					AND queue='$queue'
					AND user_or_group='$group'");
			}

			break;
		default:
			$value = 0;

			break;
	}

	if (!empty($value)) {
		return $value;
	} else {
		return '0';
	}
}

function ss_grid_fstree_get_tree($clusterid, $queue, $parent = '', $level = 0, &$tree) {
	if ($level > 0) {
		$parent .= '/';
		$level ++;
	} else {
		$level = substr_count($parent, '/') + 2;
	}

	$children = db_fetch_assoc_prepared("SELECT DISTINCT
		SUBSTRING_INDEX(shareAcctPath, '/', $level) AS branch
		FROM grid_queues_shares
		WHERE shareAcctPath LIKE '" . $parent . "%'
		AND queue = ?
		AND shareAcctPath != user_or_group
		AND shareAcctPath != ''
		AND clusterid = ?",
		array($queue, $clusterid));

	if (cacti_sizeof($children)) {
		foreach($children as $ochild) {
			$child_parts = explode('/', $ochild['branch']);

			$tree[$ochild['branch']] = $ochild['branch'];

			ss_grid_fstree_get_tree($clusterid, $queue, $ochild['branch'], $level, $tree);
		}
	}
}

function ss_grid_fstree_getnames($clusterid) {
	$return_arr = array();

	$queues = db_fetch_assoc_prepared('SELECT queuename
		FROM grid_queues
		WHERE clusterid = ?',
		array($clusterid));

	if (cacti_sizeof($queues)) {
		foreach($queues as $q) {
			$tree = array();
			ss_grid_fstree_get_tree($clusterid, $q['queuename'], '', 0, $tree);

			if (cacti_sizeof($tree)) {
				asort($tree);
				foreach($tree as $t) {
					$return_arr[] = $q['queuename'] . '|' . $t;
				}
			}
		}
	}

	return $return_arr;
}

function ss_grid_fstree_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'queue') {
		$arr = ss_grid_fstree_getnames($clusterid);

		if (cacti_sizeof($arr)) {
			foreach($arr as $a) {
				$return_arr[$a] = $a;
			}
		}
	} elseif ($info_requested == 'shareAcctPath') {
		$arr = ss_grid_fstree_getnames($clusterid);

		if (cacti_sizeof($arr)) {
			foreach($arr as $a) {
				$return_arr[$a] = $a;
			}
		}
	}

	return $return_arr;
}
