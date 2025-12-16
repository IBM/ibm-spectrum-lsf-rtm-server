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
	print call_user_func_array('ss_alert_jobs_pending', $_SERVER['argv']);
}

function ss_alert_jobs_pending($clusterid = 0, $secpending = '0', $queuename = '', $detail = 0) {
	if ($secpending == '0') {
		return 0;
	}

	$alert_where = '';
	$paramarr    = array($clusterid);

	if ($queuename != '') {
		$alert_where = ' AND queue = ? ';
		$paramarr[]  = $queuename;
	}

	$paramarr[] = $secpending;

	$job_pend = db_fetch_assoc_prepared("SELECT jobid as JobID, indexid as IndexID,
		clusterid as ClusterID, submit_time as SubmitTime, user as User,
		exec_host as ExecHost, stat as Status, queue as Queue,
		timestampdiff(second,submit_time,sysdate()) as SecondsPending
		FROM grid_jobs
		WHERE clusterid = ?
		AND stat = 'PEND'
		AND start_time = 0" .
		$alert_where . "
		AND timestampdiff(second,submit_time,sysdate()) > ?",
		$paramarr);

	if ($detail == 1) {
		$result = $job_pend;
	} else {
		$result = count($job_pend,0);
	}

	return $result;
}
