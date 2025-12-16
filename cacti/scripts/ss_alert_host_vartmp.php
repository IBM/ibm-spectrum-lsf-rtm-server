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
    Returns how many hosts exceed var tmp percentage used
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_alert_host_vartmp', $_SERVER['argv']);
}

function ss_alert_host_vartmp($clusterid = 0, $tmppercent = 0, $detail = 0) {
	$host_tmp = db_fetch_assoc_prepared("SELECT t1.host as HostName, t1.clusterid as ClusterID,
		v1 as AvailVarTmp, v2 as MaxVarTmp
		FROM (
			SELECT totalValue AS v1, host, clusterid
		    FROM grid_hosts_resources
		    WHERE clusterid = ?
		    AND resource_name = 'vartmp'
		) AS t1
		INNER JOIN (
			SELECT totalValue AS v2, host, clusterid
		    FROM grid_hosts_resources
		    WHERE clusterid = ?
		    AND resource_name = 'maxvartmp'
		) AS t2
		ON t1.host = t2.host
		AND t1.clusterid = t2.clusterid
		INNER JOIN host AS h
		ON h.hostname = t2.host
		AND h.clusterid = t2.host
		INNER JOIN grid_load AS gl
		ON gl.host = h.hostname
		AND gl.clusterid = h.clusterid
		WHERE gl.clusterid = ?
		AND h.disabled != 'on'
		AND gl.status != 'Unavail'
		AND (1-(v1/v2))*100 > ?",
		array($clusterid, $clusterid, $clusterid, $tmppercent));

	if ($detail == 1) {
		$result = $host_tmp;
	} else {
		$result = count($host_tmp);
	}

	return $result;
}
