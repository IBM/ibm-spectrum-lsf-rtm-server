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
	print call_user_func_array('ss_alert_hgroup_free_slots', $_SERVER['argv']);
}

function ss_alert_hgroup_free_slots($clusterid = 0, $hgroup = 'Unspecified', $numslots = 0, $detail = 0) {
	$paramarr = array($clusterid, $hgroup, $numslots);

	$slot_free = db_fetch_assoc_prepared("
		SELECT t_groupName as HostGroup,
			t_clusterid as ClusterID,
			t_numRun as RunJobs,
			t_maxJobs as MaxJobs
		FROM (
			SELECT g.groupName as t_groupName,
				h.clusterid as t_clusterid,
				sum(case when h.maxJobs >= 0 then h.maxJobs else 0 end) as t_maxJobs,
				sum(h.numRun) as t_numRun
			FROM grid_hosts h, grid_hostgroups g
			WHERE h.host = g.host
			AND h.clusterid = g.clusterid
			AND h.clusterid = ?
			AND status != 'Unavail'
			AND g.groupName = ?
			GROUP BY g.groupName
		) hgroupJobs
		WHERE t_maxJobs - t_numRun < ? ",
		$paramarr);

	if ($detail==1) {
		$result = $slot_free;
	} else {
		$result = count($slot_free);
	}

	return $result;
}
