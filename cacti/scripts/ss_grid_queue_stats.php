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
	print call_user_func_array('ss_grid_queue_stats', $_SERVER['argv']);
}

function ss_grid_queue_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_queue_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_queue_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_queue_stats_getinfo($clusterid, $arg1, $arg2);

		for ($i=0;($i<sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;

		return ss_grid_queue_stats_getvalue($clusterid, $index, $arg);
	}
}

function ss_grid_queue_stats_getvalue($clusterid, $index, $column) {
	global $grid_date_recorded;

	$return_arr = array();

	switch ($column) {
		case 'started':
		case 'submitted':
		case 'ended':
		case 'exited':
		case 'total':
			if ($column == 'started')       $column = 'STARTED';
			elseif ($column == 'submitted') $column = 'SUBMITTED';
			elseif ($column == 'ended')     $column = 'ENDED';
			elseif ($column == 'exited')    $column = 'EXITED';

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
				if ($column != 'total') {
					$value = db_fetch_cell_prepared('SELECT
						Sum(grid_job_interval_stats.slots_in_state)
						FROM grid_job_interval_stats
						WHERE stat = ?
						AND clusterid = ?
						AND queue = ?
						AND date_recorded = ?
						GROUP BY queue',
						array($column, $clusterid, $index, $max_date));
				} else {
					$value = db_fetch_cell_prepared('SELECT
						SUM(grid_job_interval_stats.slots_in_state)
						FROM grid_job_interval_stats
						WHERE clusterid = ?
						AND queue = ?
						AND date_recorded = ?
						GROUP BY queue',
						array($clusterid, $index, $max_date));
				}
			}

			break;
		case 'sharedSlots':
		case 'dedicatedSlots':
		case 'openDedicatedSlots':
		case 'openSharedSlots':
		case 'numslots':
			$value = db_fetch_cell_prepared("SELECT $column
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?",
				array($clusterid, $index));
			break;
		case 'pending':
			$value = db_fetch_cell_prepared('SELECT pendjobs
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'running':
			$value = db_fetch_cell_prepared('SELECT runjobs
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'suspended':
			$value = db_fetch_cell_prepared('SELECT suspjobs
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgpend':
			$value = db_fetch_cell_prepared('SELECT avg_pend_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxpend':
			$value = db_fetch_cell_prepared('SELECT max_pend_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgpsusp':
			$value = db_fetch_cell_prepared('SELECT avg_psusp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxpsusp':
			$value = db_fetch_cell_prepared('SELECT max_psusp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgrun':
			$value = db_fetch_cell_prepared('SELECT avg_run_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxrun':
			$value = db_fetch_cell_prepared('SELECT max_run_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgssusp':
			$value = db_fetch_cell_prepared('SELECT avg_ssusp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxssusp':
			$value = db_fetch_cell_prepared('SELECT max_ssusp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgususp':
			$value = db_fetch_cell_prepared('SELECT avg_ususp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxususp':
			$value = db_fetch_cell_prepared('SELECT max_ususp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgunkwn':
			$value = db_fetch_cell_prepared('SELECT avg_unkwn_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxunkwn':
			$value = db_fetch_cell_prepared('SELECT max_unkwn_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'avgdisp':
			$value = db_fetch_cell_prepared('SELECT avg_disp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'maxdisp':
			$value = db_fetch_cell_prepared('SELECT max_disp_time
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'queuemax':
			$value = db_fetch_cell_prepared('SELECT IF(maxjobs='-',0,maxjobs) AS value
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?',
				array($clusterid, $index));
			break;
		case 'users':
			if (isset($grid_date_recorded[$clusterid])) {
				$max_date = $grid_date_recorded[$clusterid];
			} else {
				$max_date = db_fetch_cell_prepared('SELECT MAX(date_recorded) AS date
					FROM grid_job_interval_stats
					WHERE clusterid = ?',
					array($clusterid));

				if ($max_date == '') {
					$max_date = '2008-01-01 00:00:00';
				}

				$grid_date_recorded[$clusterid] = $max_date;
			}

			$value = db_fetch_cell_prepared('SELECT COUNT(DISTINCT user)
				FROM grid_job_interval_stats
				WHERE clusterid = ?
				AND date_recorded = ?
				AND queue = ?',
				array($clusterid, $max_date, $index));

			break;
		case 'hstart':
		case 'hexit':
		case 'hdone':
			if (isset($grid_date_recorded[$clusterid])) {
				$max_date = $grid_date_recorded[$clusterid];
			} else {
				$max_date = db_fetch_cell_prepared('SELECT MAX(date_recorded) AS date
					FROM grid_job_interval_stats
					WHERE clusterid = ?',
					array($clusterid));

				if ($max_date == '') {
					$max_date = '2008-01-01 00:00:00';
				}

				$grid_date_recorded[$clusterid] = $max_date;
			}

			if ($column == 'hstart') {
				$column = 'hourly_started_jobs';
			} elseif ($column == 'hexit') {
				$column = 'hourly_exit_jobs';
			} else {
				$column = 'hourly_done_jobs';
			}

			$value = db_fetch_cell_prepared("SELECT $column
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?",
				array($clusterid, $index));

			break;
		case 'hadmin':
			$value = db_fetch_cell_prepared("SELECT SUM(maxJobs)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?
				AND gh.status = 'Admin-Down'",
				array($clusterid, $index));

			break;
		case 'htotal':
			$value = db_fetch_cell_prepared('SELECT SUM(maxJobs)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?',
				array($clusterid, $index));

			break;
		case 'hfree':
			$value = db_fetch_cell("SELECT SUM(maxJobs)-SUM(numRun)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?
				AND gh.status = 'Ok'",
				array($clusterid, $index));

			break;
		case 'hdown':
			$value = db_fetch_cell("SELECT SUM(maxJobs)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?
				AND gh.status LIKE 'U%'",
				array($clusterid, $index));

			break;
		case 'hused':
			$value = db_fetch_cell('SELECT SUM(numRun)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?',
				array($clusterid, $index));

			break;
		case 'hmax':
			$queue_max  = db_fetch_cell("SELECT maxjobs
				FROM grid_queues
				WHERE clusterid = ?
				AND queuename = ?
				AND maxjobs != '-'",
				array($clusterid, $index));

			$commit_max = db_fetch_cell("SELECT META_COL2
				FROM grid_metadata
				WHERE OBJECT_TYPE = 'queue'
				AND CLUSTER_ID = ?
				AND OBJECT_ID = ?",
				array($clusterid, $index));

			$hmax = db_fetch_cell('SELECT SUM(maxJobs)
				FROM grid_hosts AS gh
				INNER JOIN grid_queues_hosts AS gqh
				ON gh.clusterid = gqh.clusterid
				AND gh.host = gqh.host
				WHERE gqh.clusterid = ?
				AND gqh.queue = ?',
				array($clusterid, $index));

			if (!empty($queue_max)) {
				$value = $queue_max;
			} elseif (!empty($commit_max)) {
				$value = $commit_max;
			} elseif (!empty($hmax)) {
				$value = $hmax;
			} else {
				$value = 0;
			}

			break;
		case 'memSlotUtil':
			$value = db_fetch_cell_prepared('SELECT memSlotUtil
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		case 'slotUtil':
			$value = db_fetch_cell_prepared('SELECT slotUtil
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		case 'cpuUtil':
			$value = db_fetch_cell_prepared('SELECT cpuUtil
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		case 'memUsed':
			$value = db_fetch_cell_prepared('SELECT memUsed
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		case 'memRequested':
			$value = db_fetch_cell_prepared('SELECT memRequested
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		case 'memReserved':
			$value = db_fetch_cell_prepared('SELECT memReserved
				FROM grid_queues_stats
				WHERE clusterid = ?
				AND queue = ?',
				array($clusterid, $index));

			break;
		default:
			$value = 0;

			break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_queue_stats_getnames($clusterid) {
	$return_arr = array();

	$arr = db_fetch_assoc_prepared('SELECT queue
		FROM grid_queues_stats AS gqs
		LEFT JOIN grid_queues AS gq
		ON gq.queuename=gqs.queue
		AND gq.clusterid=gqs.clusterid
		WHERE gq.clusterid = ?
		ORDER BY queue',
		array($clusterid));

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['queue'];
	}

	return $return_arr;
}

function ss_grid_queue_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'description') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, description AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gq.queuename = gqs.queue
			AND gq.clusterid = gqs.clusterid
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	} else if ($info_requested == 'queue') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, queue AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gq.queuename = gqs.queue
			AND gq.clusterid = gqs.clusterid
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	} else if ($info_requested == 'prio') {
		$arr = db_fetch_assoc_prepared('SELECT queue AS qry_index, priority AS qry_value
			FROM grid_queues_stats AS gqs
			LEFT JOIN grid_queues AS gq
			ON gq.queuename = gqs.queue
			AND gq.clusterid = gqs.clusterid
			WHERE gq.clusterid = ?
			ORDER BY queue',
			array($clusterid));
	}

	for ($i=0;($i<sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}
