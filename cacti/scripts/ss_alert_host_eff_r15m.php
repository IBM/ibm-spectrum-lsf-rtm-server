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
	print call_user_func_array('ss_alert_host_eff_r15m', $_SERVER['argv']);
}

function ss_alert_host_eff_r15m($clusterid = 0, $load_factor = 0, $detail = 0) {
	$host_load = db_fetch_assoc_prepared("SELECT host AS HostName, grid_load.clusterid AS ClusterID, grid_load.r15m AS r15m
		FROM grid_load
		INNER JOIN host
		ON grid_load.host = host.hostname
		AND grid_load.clusterid = host.clusterid
		WHERE grid_load.clusterid = ?
		AND host.disabled != 'on'
		AND grid_load.status != 'Unavail'
		AND grid_load.r15m > ?",
		array($clusterid, $load_factor));

	if ($detail == 1) {
		$result = $host_load;
	} else {
		$result = count($host_load);
	}

	return $result;
}
