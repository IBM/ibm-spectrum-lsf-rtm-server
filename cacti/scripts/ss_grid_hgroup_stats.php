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
	print call_user_func_array('ss_grid_hgroup_stats', $_SERVER['argv']);
}

function ss_grid_hgroup_stats($clusterid = 0, $cmd = 'index', $arg1 = '', $arg2 = '') {
	if ($cmd == 'index') {
		$return_arr = ss_grid_hgroup_stats_getnames($clusterid, $arg1);

		for ($i=0;($i<cacti_sizeof($return_arr));$i++) {
			print $return_arr[$i] . "\n";
		}
	} elseif ($cmd == 'query') {
		$arr_index = ss_grid_hgroup_stats_getnames($clusterid, $arg1);
		$arr = ss_grid_hgroup_stats_getinfo($clusterid, $arg1, $arg2);
		for ($i=0;($i<cacti_sizeof($arr_index));$i++) {
			if (isset($arr[$arr_index[$i]])) {
				print $arr_index[$i] . '!' . $arr[$arr_index[$i]] . "\n";
			}
		}
	} elseif ($cmd == 'get') {
		$arg = $arg1;
		$index = $arg2;
		return ss_grid_hgroup_stats_getvalue($clusterid, $index, $arg);
	}
}
function ss_grid_hgroup_stats_getvalue($clusterid, $index, $column) {
	global $config;

	$return_arr = array();
	switch ($column) {
		case 'r15s':
		case 'r1m':
		case 'r15m':
		case 'ls':
			$max_date = ss_grid_hgroup_get_maxdate($clusterid);
			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 2*$poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT
					Sum($column) AS value
					FROM grid_hosts AS gh
					INNER JOIN grid_hostgroups AS ghg
					ON ghg.clusterid = gh.clusterid
					AND ghg.host = gh.host
					INNER JOIN grid_load AS gl
					ON gh.clusterid = gl.clusterid
					AND gh.host = gl.host
					WHERE ghg.groupName = ?
					AND ghg.clusterid = ?
					AND gh.status NOT IN('Unavail','Unlicensed','Unreach', 'Closed-Admin', 'Closed-LIM')",
					array($index, $clusterid));
			}

			break;
		case 'pg':
		case 'io':
		case 'it':
		case 'swp':
		case 'mem':
		case 'tmp':
			$max_date = ss_grid_hgroup_get_maxdate($clusterid);
			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 2 * $poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT
					Avg($column) AS value
					FROM grid_hosts AS gh
					INNER JOIN grid_hostgroups AS ghg
					ON ghg.clusterid = gh.clusterid
					AND ghg.host = gh.host
					INNER JOIN grid_load gl
					ON gh.clusterid = gl.clusterid
					AND gh.host = gl.host
					WHERE ghg.groupName = ?
					AND ghg.clusterid = ?
					AND gh.status NOT IN('Unavail','Unlicensed','Unreach', 'Closed-Admin', 'Closed-LIM')",
					array($index, $clusterid));
			}

			break;
		case 'total_mem':
		case 'total_swp':
		case 'total_tmp':
			switch ($column) {
				case 'total_mem':
					$tmp_column = 'mem';
					break;
				case 'total_swp':
					$tmp_column = 'swp';
					break;
				case 'total_tmp':
					$tmp_column = 'tmp';
					break;
			}

			$max_date = ss_grid_hgroup_get_maxdate($clusterid);
			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 2 * $poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT SUM($tmp_column) AS value
					FROM grid_hosts AS gh
					INNER JOIN grid_hostgroups AS ghg
					ON ghg.clusterid = gh.clusterid
					AND ghg.host = gh.host
					INNER JOIN grid_load AS gl
					ON gh.clusterid = gl.clusterid
					AND gh.host = gl.host
					WHERE ghg.groupName = ?
					AND ghg.clusterid = ?
					AND gh.status NOT IN('Unavail','Unlicensed','Unreach', 'Closed-Admin', 'Closed-LIM')",
					array($index, $clusterid));
			}

			break;
		case 'total_maxMem':
		case 'total_maxSwap':
		case 'total_maxTmp':
			switch ($column) {
				case 'total_maxMem':
					$tmp_column = 'maxMem';
					break;
				case 'total_maxSwap':
					$tmp_column = 'maxSwap';
					break;
				case 'total_maxTmp':
					$tmp_column = 'maxTmp';
					break;
			}

			$max_date = ss_grid_hgroup_get_maxdate($clusterid);
			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 2 * $poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT SUM($tmp_column) AS value
					FROM grid_hosts AS gh
					INNER JOIN grid_hostgroups AS ghg
					ON ghg.clusterid = gh.clusterid
					AND ghg.host = gh.host
					INNER JOIN grid_hostinfo AS ghi
					ON gh.clusterid = ghi.clusterid
					AND gh.host = ghi.host
					WHERE ghg.groupName = ?
					AND ghg.clusterid = ?
					AND gh.status NOT IN ('Unavail','Unlicensed','Unreach','Closed-Admin')",
					array($index, $clusterid));
			}

			break;
		case 'total_slots':
			$value = db_fetch_cell_prepared('SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghi.host
				AND ghg.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE maxJobs >= 0
				AND ghg.groupName = ?
				AND ghg.clusterid = ?',
				array($index, $clusterid));

			break;
		case 'ok_slots':
			$value = db_fetch_cell_prepared("SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghi.host
				AND ghg.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE maxJobs >= 0
				AND ghg.groupName = ?
				AND ghg.clusterid = ?
				AND gh.status = 'Ok'",
				array($index, $clusterid));

			break;
		case 'avail_slots':
			$max_date = ss_grid_hgroup_get_maxdate($clusterid);
			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if ((time() - 2 * $poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
					FROM
					(SELECT clusterid,
					        host,
					        maxCpus
					   FROM grid_hostinfo
				   	   WHERE clusterid = ?) AS ghi
					INNER JOIN
					(SELECT clusterid,
					        host,
					        groupName
							FROM grid_hostgroups
							WHERE groupName = ? AND clusterid = ? ) AS ghg
					ON ghg.host = ghi.host
					AND ghg.clusterid = ghi.clusterid
					INNER JOIN
					(SELECT clusterid,
							host,
							status,
							maxJobs
							FROM grid_hosts
							WHERE maxJobs >= 0 AND status NOT IN( 'Unavail', 'Unlicensed', 'Unreach', 'Closed-Admin', 'Closed-LIM' ) ) AS gh
					ON ghg.host = gh.host
					AND ghg.clusterid = gh.clusterid",
					array($clusterid, $index, $clusterid));
			}

			break;
		case 'closed_slots':
			$value = db_fetch_cell_prepared("SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghi.host
				AND ghg.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE maxJobs >= 0
				AND ghg.groupName = ?
				AND ghg.clusterid = ?
				AND gh.status IN ('Closed-Busy', 'Closed-Full', 'Closed-Limits', 'Closed-Excl', 'Closed-Wind', 'Closed-Lease')",
				array($index, $clusterid));

			break;
		case 'out_slots':
			$value = db_fetch_cell_prepared("SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghi.host
				AND ghg.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE maxJobs >= 0
				AND ghg.groupName = ?
				AND ghg.clusterid = ?
				AND gh.status IN ('Closed-Admin', 'Closed-Lock', 'Closed-RMS', 'Closed-RES', 'Closed-Lease', 'Closed-RmtDis')",
				array($index, $clusterid));

			break;
		case 'unavail_slots':
			$value = db_fetch_cell_prepared("SELECT SUM(GREATEST(maxJobs, maxCpus)) AS value
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghi.host
				AND ghg.clusterid = ghi.clusterid
				INNER JOIN grid_hosts AS gh
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE maxJobs >= 0
				AND ghg.groupName = ?
				AND ghg.clusterid = ?
				AND gh.status IN ('Closed-LIM', 'Unavail', 'Unreach', 'Unlicensed')",
				array($index, $clusterid));

			break;
		case 'total_jobs':
			$value = db_fetch_cell_prepared("SELECT SUM(numJobs) AS value
				FROM grid_hosts AS gh
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = gh.host
				AND ghg.clusterid = gh.clusterid
				WHERE ghg.groupName = ?
				AND ghg.clusterid = ?",
				array($index, $clusterid));

			break;
		case 'ut':
			$max_date = ss_grid_hgroup_get_maxdate($clusterid);

			$poller_interval = read_config_option('poller_interval');

			if (empty($poller_interval)) { $poller_interval = 300; }

			if (read_config_option('grid_cpu_leveling') == 'on') {
				$query = 'SUM(ghi.cpuFactor*maxCpus*ut) / SUM(ghi.cpuFactor*maxCpus)';
			} else {
				$query = 'SUM(maxCpus*ut) / SUM(maxCpus)';
			}

			if ((time() - 2 * $poller_interval) > $max_date) {
				$value = 0;
			} else {
				$value = db_fetch_cell_prepared("SELECT $query
					FROM grid_hostinfo AS ghi
					INNER JOIN grid_load AS gl
					ON ghi.host = gl.host
					AND ghi.clusterid = gl.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host = gl.host
					AND gh.clusterid = gl.clusterid
					INNER JOIN grid_hostgroups AS ghg
					ON ghg.host = gh.host
					AND ghg.clusterid = gh.clusterid
					WHERE ghg.groupName = ?
					AND ghg.clusterid = ?
					AND gl.status NOT IN ('Unavail','Unlicensed','Unreach')
					AND gh.status NOT IN('Closed-Admin', 'Closed-LIM')
					AND isServer = '1'",
					array($index, $clusterid));
			}

			break;
		case 'capacity':
		case 'load':
			if ($column == 'capacity') $query = 'SUM(ghi.cpuFactor*maxCpus) AS tcapacity';
			if ($column == 'load')     $query = 'SUM(ghi.cpuFactor*maxCpus*ut) AS tload';

			$value = db_fetch_cell_prepared("SELECT $query
				FROM grid_hostinfo AS ghi
				INNER JOIN grid_load AS gl
				ON ghi.host = gl.host
				AND ghi.clusterid = gl.clusterid
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = gl.host
				AND ghg.clusterid = gl.clusterid
				WHERE ghg.groupName = ?
				AND ghg.clusterid = ?
				AND gl.status NOT IN ('Unavail','Unlicensed','Unreach')
				AND isServer='1'",
				array($index, $clusterid));

			break;
		case 'numDONE':
		case 'numEXIT':
			if ($column == 'numDONE') $query = 'SUM(ghjt.jobs_done) AS numDONE';
			if ($column == 'numEXIT') $query = 'SUM(ghjt.jobs_exited) AS numEXIT';

			$value = db_fetch_cell_prepared("SELECT $query
				FROM grid_hosts_jobtraffic AS ghjt
				INNER JOIN grid_hostgroups AS ghg
				ON ghg.host = ghjt.host
				AND ghg.clusterid = ghjt.clusterid
				WHERE ghg.groupName = ?
				AND ghg.clusterid = ?",
				array($index, $clusterid));

			break;
		case 'numPEND':
			$value = db_fetch_cell_prepared('SELECT numPEND
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?',
				array($index, $clusterid));

			break;
		case 'numRUN':
			$value = db_fetch_cell_prepared('SELECT numRUN
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?',
				array($index, $clusterid));

			break;
		case 'max_mem':
		case 'max_swap':
		case 'avg_swap':
		case 'avg_mem':
			$value = db_fetch_cell_prepared("SELECT
				$column AS value
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'memSlotUtil':
			$value = db_fetch_cell_prepared("SELECT memSlotUtil
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case "slotUtil":
			$value = db_fetch_cell_prepared("SELECT slotUtil
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'cpuUtil':
			$value = db_fetch_cell_prepared("SELECT cpuUtil
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'memUsed':
			$value = db_fetch_cell_prepared("SELECT memUsed
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'memRequested':
			$value = db_fetch_cell_prepared("SELECT memRequested
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
		case 'memReserved':
			$value = db_fetch_cell_prepared("SELECT memReserved
				FROM grid_hostgroups_stats
				WHERE groupName = ?
				AND clusterid = ?",
				array($index, $clusterid));

			break;
	}

	if (!empty($value)) {
		return trim($value);
	} else {
		return '0';
	}
}

function ss_grid_hgroup_stats_getnames($clusterid) {
	$return_arr = array();
	$arr = db_fetch_assoc_prepared('SELECT DISTINCT groupName
		FROM grid_hostgroups
		WHERE clusterid = ?
		ORDER BY groupName',
		array($clusterid));

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$i] = $arr[$i]['groupName'];
	}

	return $return_arr;
}

function ss_grid_hgroup_stats_getinfo($clusterid, $info_requested) {
	$return_arr = array();

	if ($info_requested == 'hosts') {
		$arr = db_fetch_assoc_prepared('SELECT groupName AS qry_index, COUNT(host) AS qry_value
			FROM grid_hostgroups
			WHERE clusterid = ?
			GROUP BY groupName
			ORDER BY groupName',
			array($clusterid));
	} elseif ($info_requested == 'groupName') {
		$arr = db_fetch_assoc_prepared('SELECT DISTINCT groupName AS qry_index, groupName AS qry_value
			FROM grid_hostgroups
			WHERE clusterid = ?
			GROUP BY groupName
			ORDER BY groupName',
			array($clusterid));
	}

	for ($i=0;($i<cacti_sizeof($arr));$i++) {
		$return_arr[$arr[$i]['qry_index']] = addslashes($arr[$i]['qry_value']);
	}

	return $return_arr;
}

function ss_grid_hgroup_get_maxdate($clusterid) {
	global $config;

	$last_string = read_config_option('grid_update_time_lsload_' . $clusterid);
	$last_array = explode(' ', $last_string);
	$date_string = str_replace('_', ' ', str_replace('EndDate:', '', $last_array[0]));
	return strtotime($date_string);
}
