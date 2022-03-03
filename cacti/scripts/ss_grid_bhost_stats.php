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
	print call_user_func_array('ss_grid_bhost_stats', $_SERVER['argv']);
}

function ss_grid_bhost_stats($clusterid = 0) {
	$host_status = db_fetch_assoc_prepared('SELECT status, count(status) AS total
		FROM grid_hosts
		WHERE clusterid = ?
		GROUP BY status',
		array($clusterid));

	/* initialize variables */
	$host_ok         = 0;
	$host_closed     = 0;
	$host_full       = 0;
	$host_unreach    = 0;
	$host_unavail    = 0;
	$host_unlicensed = 0;
	$host_unknown    = 0;

	if (sizeof($host_status)) {
		foreach($host_status as $stat) {
			if (substr_count($stat['status'], 'Full'))           $host_full       = $stat['total'];
			elseif (substr_count($stat['status'], 'Closed'))     $host_closed     = $stat['total'];
			elseif (substr_count($stat['status'], 'Unlicensed')) $host_unlicensed = $stat['total'];
			elseif (substr_count($stat['status'], 'Unavail'))    $host_unavail    = $stat['total'];
			elseif (substr_count($stat['status'], 'Ok'))         $host_ok         = $stat['total'];
			elseif (substr_count($stat['status'], 'Unreach'))    $host_unreach    = $stat['total'];
			else                                                 $host_unknown    = $stat['total'];
		}
	}

	$result =
		'ok:'         . $host_ok         . ' ' .
		'closed:'     . $host_closed     . ' ' .
		'full:'       . $host_full       . ' ' .
		'unreach:'    . $host_unreach    . ' ' .
		'unavail:'    . $host_unavail    . ' ' .
		'unlicensed:' . $host_unlicensed . ' ' .
		'unknown:'    . $host_unknown    . "\n";

	return trim($result);
}
