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

/*
    Returns how many hosts exceed tmp percentage used
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_alert_host_tmp', $_SERVER['argv']);
}

function ss_alert_host_tmp($clusterid = 0, $tmppercent = 0, $detail = 0) {
	$host_tmp = db_fetch_assoc_prepared("SELECT ghi.host AS HostName, ghi.clusterid AS ClusterID,
		gl.tmp AS AvailTmp, ghi.maxTmp AS MaxTmp
		FROM grid_load AS gl
		INNER JOIN grid_hostinfo AS ghi
		ON gl.clusterid = ghi.clusterid
		AND gl.host = ghi.host
		INNER JOIN host AS h
		ON h.clusterid = ghi.clusterid
		AND h.hostname = ghi.host
		WHERE ghi.clusterid = ?
		AND h.disabled != 'on'
		AND gl.status != 'Unavail'
		AND (1-(gl.tmp / ghi.maxTmp))*100 > ?",
		array($clusterid, $tmppercent));

	if ($detail == 1) {
		$result = $host_tmp;
	} else {
		$result = count($host_tmp);
	}

	return $result;
}
