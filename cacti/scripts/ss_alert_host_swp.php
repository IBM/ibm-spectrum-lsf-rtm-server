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

/*
    Returns how many hosts exceed swap percentage used
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_alert_host_swp', $_SERVER['argv']);
}

function ss_alert_host_swp($clusterid = 0, $mempercent = 0, $detail = 0) {
	$host_mem = db_fetch_assoc_prepared("SELECT
		grid_hostinfo.host AS HostName,
		grid_hostinfo.clusterid AS ClusterID,
		grid_load.swp AS AvailSwap,
		grid_hostinfo.maxSwap AS MaxSwap
		FROM grid_load
		INNER JOIN grid_hostinfo
		ON grid_load.host=grid_hostinfo.host
		AND grid_load.clusterid=grid_hostinfo.clusterid
		INNER JOIN host
		ON host.hostname=grid_hostinfo.host
		AND host.clusterid=grid_hostinfo.clusterid
		WHERE grid_hostinfo.clusterid = ?
		AND host.disabled != 'on'
		AND grid_load.status != 'Unavail'
		AND (1-(grid_load.swp / grid_hostinfo.maxSwap))*100 > ?",
		array($clusterid, $mempercent));

	if ($detail == 1) {
		$result = $host_mem;
	} else {
		$result = count($host_mem);
	}

	return $result;
}
