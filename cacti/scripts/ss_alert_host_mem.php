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
	print call_user_func_array('ss_alert_host_mem', $_SERVER['argv']);
}

function ss_alert_host_mem($clusterid = 0, $mempercent = 0, $detail = 0) {
	$host_mem_arr = db_fetch_assoc_prepared("SELECT ghi.host AS HostName,
		ghi.clusterid AS ClusterID, gl.mem AS AvailMem, ghi.maxMem AS MaxMem
		FROM grid_load AS gl
		INNER JOIN grid_hostinfo AS ghi
		ON gl.host = ghi.host
		AND gl.clusterid = ghi.clusterid
		INNER JOIN host AS h
		ON h.hostname = ghi.host
		AND h.clusterid = ghi.clusterid
		WHERE ghi.clusterid = ?
		AND gl.status != 'Unavail'
		AND h.disabled != 'on'
		AND (1-(gl.mem / ghi.maxMem))*100 > ?",
		array($clusterid, $mempercent));

	if ($detail == 1) {
		$result = $host_mem_arr;
	} else {
		$result = count($host_mem_arr);
		//$result = trim($result);
	}

	return $result;
}
