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
	print call_user_func_array('ss_grid_capacity', $_SERVER['argv']);
}

function ss_grid_capacity($clusterid = 0, $baselineCpuFactor = 1, $include_groups = '') {
	/* initialized the include string */
	$include = '';

	/* build a compatible list */
	if (strlen($include_groups)) {
		$groups  = explode('|', $include_groups);

		if (cacti_sizeof($groups)) {
			foreach($groups as $group) {
				if (strlen($include)) {
					$include .= ", '" . trim($group) . "'";
				} else {
					$include .= "'" . trim($group) . "'";
				}
			}
		}
	}

	if ($clusterid > 0) {
		$sql_where = "WHERE ghi.clusterid='" . $clusterid . "' AND isServer='1'";
	} else {
		$sql_where = "WHERE isServer='1'";
	}

	if (strlen($include)) {
		$capload = db_fetch_row("SELECT SUM(cpuFactor*maxCpus) AS tcapacity, SUM(cpuFactor*maxCpus*ut) AS tload
			FROM grid_hostinfo AS ghi
			LEFT JOIN grid_load AS gl
			ON ghi.host = gl.host
			AND ghi.clusterid = gl.clusterid
			LEFT JOIN grid_hostgroups AS ghg
			ON ghi.host = ghg.host
			AND ghi.clusterid = ghg.clusterid
			$sql_where
			AND gl.status NOT LIKE 'U%'
			AND ghg.groupName IN ($include)");
	} else {
		$capload = db_fetch_row("SELECT
			SUM(cpuFactor*maxCpus) AS tcapacity,
			SUM(cpuFactor*maxCpus*ut) AS tload
			FROM grid_hostinfo AS ghi
			LEFT JOIN grid_load AS gl
			ON ghi.host=gl.host
			AND ghi.clusterid = gl.clusterid
			$sql_where
			AND gl.status NOT LIKE 'U%'");
	}

	if (!isset($capload)) {
		$capacity = 0;
		$load     = 0;
	} else {
		$capacity = round(($capload['tcapacity']/$baselineCpuFactor),2);
		$load     = round(($capload['tload']/$baselineCpuFactor),2);
	}

	return 'capacity:' . $capacity . ' load:' . $load;
}

