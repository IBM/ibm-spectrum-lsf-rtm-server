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
    Returns how many hosts with specified status
    Host Statuses:
        - Ok
        - Unavail
        - Closed-LIM
        - Unreach
        - Closed-Busy
        - Closed-Excl
        - Closed-Wind
        - Closed-Admin
        - Closed-Lock
        - Closed-Full
        - Closed-RMS
        - Closed-RES
        - Closed-Lease
        - Closed-RmtDis
        - Unlicensed
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_alert_host_status', $_SERVER['argv']);
}

function ss_alert_host_status($clusterid = 0, $status = 'Unavail', $detail = 0) {
	$paramarr = array($clusterid);

	$sql = "SELECT gh.host AS HostName, gh.clusterid as ClusterID, gh.status AS Status
		FROM grid_hosts AS gh
		INNER JOIN host AS h
		ON h.clusterid = gh.clusterid
		AND h.hostname = gh.host
		WHERE gh.clusterid = ?
		AND h.disabled != 'on' ";

	if (substr($status,0,1) == '-') {
		$sql = $sql . 'AND gh.status <> ? ';
		$paramarr[] = substr($status, 1);
	} else {
		$sql = $sql . 'AND gh.status = ?';
		$paramarr[] = $status;
	}

	$host_stat = db_fetch_assoc_prepared($sql, $paramarr);

	if ($detail == 1) {
		$result = $host_stat;
	} else {
		$result = count($host_stat, 0);
	}

	return $result;
}
