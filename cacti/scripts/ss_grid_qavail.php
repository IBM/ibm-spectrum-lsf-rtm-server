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
	print call_user_func_array('ss_grid_qavail', $_SERVER['argv']);
}

function ss_grid_qavail($clusterid = 0) {
	$max_queue_slots = db_fetch_assoc_prepared('SELECT grid_queues_hosts.queue,
		SUM(grid_hosts.maxjobs) AS maxslots
		FROM grid_queues_hosts
		INNER JOIN grid_hosts
       	ON grid_hosts.host=grid_queues_hosts.host
       	WHERE grid_hosts.clusterid = ?
		GROUP BY grid_queues_hosts.queue',
		array($clusterid));

	$queues = db_fetch_assoc_prepared('SELECT *
		FROM grid_queues
		WHERE clusterid = ?
		ORDER BY priority DESC, queuename ASC',
		array($clusterid));

	$availability = 1;

	if (cacti_sizeof($queues) && cacti_sizeof($max_queue_slots)) {
		$max_queue_slots = array_rekey($max_queue_slots, 'queue', 'maxslots');
		$max_of_slots = max($max_queue_slots);
	
		$i=0;
		foreach($queues as $queue) {
			if ($i == 0) {
				$availability = $queue['numslots'] / $max_of_slots;
			}

			if ($availability < 0) $availability = 0;

			switch ($queue['queuename']) {
				case 'immed_single':
					$immed_single = $availability * 100;
					$slot_availability = $queue['numslots'] / $max_of_slots * 100;
					break;
				case 'immed_multi':
					$immed_multi = $availability * 100;
					break;
				case 'overnight_single':
					$overnight_single = $availability * 100;
					break;
				case 'overnight_multi':
					$overnight_multi = $availability * 100;
					break;
				case 'parallel':
					$parallel = $availability * 100;
					break;
				case 'asavailable':
					$asavailable = $availability * 100;
					break;
				case 'fifo':
					$fifo = $availability * 100;
					break;
				default:
					break;
			}

			$availability = $availability - $queue['runjobs'] / $max_of_slots;

			$i++;
		}
	}

	$result =
		'available_slots:'  . round($slot_availability,1) . ' ' .
		'immed_single:'     . round($immed_single,1)      . ' ' .
		'immed_multi:'      . round($immed_multi,1)       . ' ' .
		'overnight_single:' . round($overnight_single,1)  . ' ' .
		'overnight_multi:'  . round($overnight_multi,1)   . ' ' .
		'parallel:'         . round($parallel,1)          . ' ' .
		'asavailable:'      . round($asavailable,1)       . ' ' .
		'fifo:'             . round($fifo,1) .  "\n";

	return trim($result);
}
