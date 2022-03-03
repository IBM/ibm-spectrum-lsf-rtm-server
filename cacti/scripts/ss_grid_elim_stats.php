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
	print call_user_func_array('ss_grid_elim_stats', $_SERVER['argv']);
}

function ss_grid_elim_stats($clusterid = 0, $host = '', $resource_name = '') {
	$row = db_fetch_row_prepared('SELECT totalValue, reservedValue, value
		FROM grid_hosts_resources
		WHERE host = ?
		AND clusterid = ?
		AND resource_name = ?',
		array($host, $clusterid, $resource_name));

	if ((!empty($row['totalValue'])) && (trim($row['totalValue']) != '-')) {
		if (!is_numeric($row['totalValue'])) {
			$totalValue = '0';
		} else {
			$totalValue = trim($row['totalValue']);
		}
	} else {
		$totalValue = '0';
	}
	if ((!empty($row['reservedValue'])) && (trim($row['reservedValue']) != '-')) {
		if (!is_numeric($row['reservedValue'])) {
			$reservedValue = '0';
		} else {
			$reservedValue = trim($row['reservedValue']);
		}
	} else {
		$reservedValue = '0';
	}

	if ((!empty($row['value'])) && (trim($row['value']) != '-')) {
		if (!is_numeric($row['value'])) {
			$value = '0';
		} else {
			$value = trim($row['value']);
		}
	} else {
		$value='0';
	}

	$result= "totalValue:$totalValue reservedValue:$reservedValue value:$value" . "\n";

	return trim($result);
}
