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

	print call_user_func_array('ss_grid_perfmon', $_SERVER['argv']);
}

function ss_grid_perfmon($clusterid = 0, $metric = '') {
	$type = 0;
	switch($metric) {
	case 'mbatchdRequest':
		$type = 1;
		$metric = 'Processed requests: mbatchd';
		break;
	case 'jobInfo':
		$type = 1;
		$metric = 'Job information queries';
		break;
	case 'hostInfo':
		$type = 1;
		$metric = 'Host information queries';
		break;
	case 'queueInfo':
		$type = 1;
		$metric = 'Queue information queries';
		break;
	case 'submitRequest':
		$type = 1;
		$metric = 'Job submission requests';
		break;
	case 'jobsSubmitted':
		$type = 1;
		$metric = 'Jobs submitted';
		break;
	case 'jobsDispatched':
		$type = 1;
		$metric = 'Jobs dispatched';
		break;
	case 'jobsCompleted':
		$type = 1;
		$metric = 'Jobs completed';
		break;
	case 'jobsForwarded':
		$type = 1;
		$metric = 'Jobs sent to remote cluster';
		break;
	case 'jobsAccepted':
		$type = 1;
		$metric = 'Jobs accepted from  remote cluster';
		break;
	case 'jobsReordered':
		$type = 1;
		$metric = 'Jobs reordered';
		break;
	case 'schedInterval':
		$type = 2;
		$metric = 'Scheduling interval in second(s)';
		break;
	case 'matchCriteria':
		$type = 2;
		$metric = 'Matching host criteria';
		break;
	case 'jobBuckets':
		$type = 2;
		$metric = 'Job buckets';
		break;
	}

		$values = db_fetch_row_prepared("SELECT current, avg
			FROM grid_clusters_perfmon_metrics
			WHERE metric = ?
			AND clusterid = ?", array($metric, $clusterid));

	if (cacti_sizeof($values)) {
		return 'current:' . trim($values['current']) . ' avg:' . trim($values['avg']);
	}else{
		return 'current:0 avg:0';
	}
}
