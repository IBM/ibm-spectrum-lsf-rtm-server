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

	array_shift($_SERVER['argv']);

	print call_user_func_array('ss_grid_perfmon_usage', $_SERVER['argv']);
}

function ss_grid_perfmon_usage($clusterid = 0, $metric = '') {
	$type = 0;
	switch($metric) {
	case 'fileDescriptor':
		$type = 1;
		$metric = 'MBD file descriptor usage';
		break;
	}

	if ($type == 1) {
		$values = db_fetch_row_prepared('SELECT total,
			current AS used,
			total - current AS free
			FROM grid_clusters_perfmon_metrics
			WHERE metric = ?
			AND clusterid = ?',
			array($metric, $clusterid));
	}

	if (sizeof($values)) {
		return 'total:' . $values['total'] . ' used:' . $values['used'] . ' free:' . $values['free'];
	}else{
		return 'total:0 used:0 free:0';
	}
}
