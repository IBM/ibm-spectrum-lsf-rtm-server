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

	print call_user_func_array('ss_grid_perfmon_ut', $_SERVER['argv']);
}

function ss_grid_perfmon_ut($clusterid = 0, $metric = '') {
	$type = 0;
	switch($metric) {
	case 'memoryUT':
		$metric = 'Memory utilization';
		break;
	case 'slotUT':
		$metric = 'Slot utilization';
		break;
	}

		$values = db_fetch_row("SELECT current, total
			FROM grid_clusters_perfmon_metrics
			WHERE metric='$metric'
			AND clusterid=$clusterid");

	if (sizeof($values)) {
		return 'current:' . $values['current'] . ' total:' . $values['total'];
	}else{
		return 'current:0 total:0';
	}
}
