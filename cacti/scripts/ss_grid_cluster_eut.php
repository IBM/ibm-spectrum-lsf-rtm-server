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
	print call_user_func_array('ss_grid_cluster_eut', $_SERVER['argv']);
}

function ss_grid_cluster_eut($clusterid = 0) {
	$ut = db_fetch_row_prepared("SELECT
		IFNULL(memSlotUtil/totalSlots,0) AS memSlotUtil,
		IFNULL(slotUtil/totalSlots,0) AS slotUtil,
		IFNULL(cpuUtil/totalSlots, 0) AS cpuUtil
		FROM (
			SELECT
			SUM(memSlotUtil*totalSlots) AS memSlotUtil,
			SUM(slotUtil*totalSlots) AS slotUtil,
			SUM(cpuUtil*totalSlots) AS cpuUtil,
			SUM(totalSlots) AS totalSlots
			FROM (
				SELECT *, (totalSlots-(FLOOR(totalSlots*freeMem/maxMem)))/totalSlots*100 AS memSlotUtil,
				ROUND((numRun/totalSlots)*100,1) AS slotUtil
				FROM (
					SELECT ghr.host, numRun, ROUND(IF(ut>0, ut*100,0),1) AS cpuUtil,
					ROUND(totalValue,0) AS freeMem, ROUND(reservedValue,0) AS reservedMem,
					GREATEST(maxJobs, maxCpus) AS totalSlots, maxMem
					FROM grid_hosts_resources AS ghr
					INNER JOIN grid_hostinfo AS ghi
					ON ghi.host = ghr.host
					AND ghi.clusterid = ghr.clusterid
					INNER JOIN grid_hosts AS gh
					ON gh.host = ghr.host
					AND gh.clusterid = ghr.clusterid
					AND gh.status NOT IN ('Closed-Full', 'Closed-LIM', 'Unavail', 'Unreach')
					INNER JOIN grid_load AS gl
					ON gl.host = ghr.host
					AND gl.clusterid = ghr.clusterid
					WHERE resource_name = 'mem'
					AND ghr.clusterid = ?
				) AS results
				HAVING memSlotUtil IS NOT NULL
			) AS results2
		) AS results3",
		array($clusterid));

	$stats = db_fetch_row_prepared("SELECT
		(SUM(mem_used)/maxMem)*100 AS memUsed,
		(SUM(mem_requested)/maxMem)*100 AS memRequested,
		(SUM(mem_reserved)/maxMem)*100 AS memReserved
		FROM analytics_pool_stats AS aps
		INNER JOIN (
			SELECT gh.clusterid, SUM(maxMem*1024*1024) AS maxMem
			FROM grid_hostinfo AS ghi
			INNER JOIN grid_hosts AS gh
			ON ghi.clusterid = gh.clusterid
			AND ghi.host = gh.host
			WHERE gh.clusterid = ?
			AND gh.status NOT IN ('Closed-Full', 'Closed-LIM', 'Unavail', 'Unreach')
		) AS hi
		ON hi.clusterid = aps.clusterid
		WHERE aps.clusterid = ?",
		array($clusterid, $clusterid));

	if (empty($stats)) {
		$result = "memSlotUti:0 slotUtil:0 cpuUtil:0 memUsed:0 memRequested:0 memReserved:0\n";
	} else {
		$result =
		'memSlotUtil: ' . ss_grid_cluster_eut_value($ut['memSlotUtil'])  . ' ' .
		'slotUtil:'     . ss_grid_cluster_eut_value($ut['slotUtil'])     . ' ' .
		'cpuUtil:'      . ss_grid_cluster_eut_value($ut['cpuUtil'])      . ' ' .
		'memUsed:'      . ss_grid_cluster_eut_value($stats['memUsed'])      . ' ' .
		'memRequested:' . ss_grid_cluster_eut_value($stats['memRequested']) . ' ' .
		'memReserved:'  . ss_grid_cluster_eut_value($stats['memReserved'])  . "\n";
	}

	return trim($result);
}

function ss_grid_cluster_eut_value($value) {
	if ($value == '') {
    	$value = 0;
	}

	return round($value,2);
}
