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

	array_shift($_SERVER['argv']);

	print call_user_func_array('ss_grid_slot_ut', $_SERVER['argv']);
}

function ss_grid_slot_ut($host = '', $clusterid = 0) {
	$sql_where = '';

	if ($host != 'localhost') {
		$sql_where = 'AND ghr.host = ' . db_qstr($host);
	}

	if ($host != 'localhost' ) {
		$ut = db_fetch_row_prepared("SELECT
			IFNULL(memSlotUtil, 0) AS memSlotUtil,
			IFNULL(slotUtil, 0) AS slotUtil,
			IFNULL(cpuUtil, 0) AS cpuUtil
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
					AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
					INNER JOIN grid_load AS gl
					ON gl.host = ghr.host
					AND gl.clusterid = ghr.clusterid
					WHERE resource_name = 'mem'
					AND ghr.clusterid = ?
					$sql_where
				) AS results
				HAVING memSlotUtil IS NOT NULL
			) AS results2",
			array($clusterid));
	}else{
		$ut = db_fetch_row_prepared("SELECT
			IFNULL(memSlotUtil/totalSlots, 0) AS memSlotUtil,
			IFNULL(slotUtil/totalSlots, 0) AS slotUtil,
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
						AND gh.status NOT IN ('Unavail', 'Unreach', 'Closed-Admin', 'Closed-LIM')
						INNER JOIN grid_load AS gl
						ON gl.host = ghr.host
						AND gl.clusterid = ghr.clusterid
						WHERE resource_name = 'mem'
						AND ghr.clusterid = ?
						$sql_where
					) AS results
					HAVING memSlotUtil IS NOT NULL
				) AS results2
			) AS results3",
			array($clusterid));
	}

	$memSlotUtil = 0;
	$slotUtil    = 0;
	$cpuUtil     = 0;

	if (cacti_sizeof($ut)) {
		if(!empty($ut['memSlotUtil'])) $memSlotUtil = $ut['memSlotUtil'];
		if(!empty($ut['slotUtil']))    $slotUtil    = $ut['slotUtil'];
		if(!empty($ut['cpuUtil']))     $cpuUtil     = $ut['cpuUtil'];
	}
	return 'memSlotUtil:' . $memSlotUtil . ' slotUtil:' . $slotUtil . ' cpuUtil:' . $cpuUtil;
}
