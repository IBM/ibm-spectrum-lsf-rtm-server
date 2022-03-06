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

function heuristics_lock_process($taskname = 'HEURISTICS', $max_runtime = 120, $pollerid = 0) {
	global $cnn_id;

	$now = time();
	$pollerid = 0;

	$row = db_fetch_row_prepared('SELECT pid, UNIX_TIMESTAMP(heartbeat) AS heartbeat
		FROM grid_processes
		WHERE taskid = ?
		AND taskname = ?',
		array($pollerid, $taskname));

	if (cacti_sizeof($row)) {
		if (($now - $row['heartbeat']) > $max_runtime) {
			db_execute_prepared('UPDATE grid_processes
				SET heartbeat=NOW()
				WHERE taskname = ?
				AND taskid = ?
				AND UNIX_TIMESTAMP(heartbeat)= ?',
				array($taskname, $pollerid, $row['heartbeat']));

			if (db_affected_rows() == 0) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
	} else {
		$success = db_execute_prepared('INSERT INTO grid_processes
			(pid, taskname, taskid, heartbeat)
			VALUES ("0", ?, ?, NOW())',
			array($taskname, $pollerid));

		return $success;
	}

	return FALSE;
}

function heuristics_unlock_process($taskname = 'HEURISTICS', $pollerid = 0) {
	db_execute_prepared('DELETE FROM grid_processes
		WHERE taskid = ?
		AND taskname = ?',
		array($pollerid, $taskname));
}

function update_user_statistics($user = '', $timeout = 30) {
	$sql_params = array();

	$sql_where1 = $sql_where2 = '';
	if ($user != '') {
		$sql_where1 = 'WHERE user = ?';
		$sql_where2 = 'AND user = ?';
		$sql_params[] = $user;
	}

	if (heuristics_lock_process()) {
		$last_updated = db_fetch_cell_prepared("SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(last_updated)) AS last_updated
			FROM grid_heuristics_user_stats
			$sql_where1
			LIMIT 1", $sql_params);

		$custom = read_config_option('heuristics_custom_column');

		$group_by_custom = true;
		if ($custom != 'none' && $custom != 'chargedSAAP') {
			$custom .= ',';
		} else {
			$custom = '"",';
			$group_by_custom = false;
		}

		if (empty($last_updated) || $last_updated > $timeout) {
			db_execute_prepared("REPLACE INTO grid_heuristics_user_stats
				SELECT clusterid,
				queue,
				$custom
				projectName,
				num_cpus,
				user,
				SUM(CASE WHEN stat='PEND' THEN num_cpus ELSE 0 END) AS numPEND,
				SUM(CASE WHEN stat='RUNNING' THEN num_cpus ELSE 0 END) AS numRUN,
				SUM(CASE WHEN stat IN ('PSUSP','USUSP','SSUSP') THEN num_cpus ELSE 0 END) AS numSUSP,
				SUM(CASE WHEN stat='DONE' THEN num_cpus ELSE 0 END) AS numDONE,
				SUM(CASE WHEN stat='EXIT' THEN num_cpus ELSE 0 END) AS numEXIT,
				SUM(CASE WHEN stat IN ('DONE','EXIT') THEN num_cpus ELSE 0 END) AS tputHOUR,
				SUM(CASE WHEN stat IN ('DONE','EXIT') AND end_time>=FROM_UNIXTIME(UNIX_TIMESTAMP()-300) THEN num_cpus ELSE 0 END) AS tput5MIN,
				NOW() AS last_updated
				FROM grid_jobs
				WHERE end_time = '0000-00-00'
				$sql_where2
				OR end_time >= FROM_UNIXTIME(UNIX_TIMESTAMP()-3600)
				GROUP BY clusterid, queue, ". ($group_by_custom ? $custom : ""). "projectName, num_cpus, user", $sql_params);

			$last_updated = 0;
		}

		heuristics_unlock_process();
	} else {
		$last_updated = db_fetch_cell_prepared("SELECT UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(last_updated))
			FROM grid_heuristics_user_stats
			$sql_where1
			LIMIT 1", $sql_params);
	}

	return $last_updated;
}
