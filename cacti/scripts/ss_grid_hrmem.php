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
	print call_user_func_array('ss_grid_hrmem', $_SERVER['argv']);
}

function ss_grid_hrmem($hostname = '', $clusterid = 0) {
	$jstats = db_fetch_row_prepared("SELECT mem as a_mem, swp as a_swp
		FROM grid_load
		WHERE (status NOT LIKE 'U%')
		AND clusterid = ?
		AND host = ?",
		array($clusterid, $hostname));

	$rstats = db_fetch_cell_prepared("SELECT reservedValue
		FROM grid_hosts_resources
		WHERE resource_name='mem'
		AND host = ?
		AND clusterid = ?",
		array($hostname, $clusterid));

	if (empty($jstats['a_mem'])) $jstats['a_mem'] = 0;
	if (empty($jstats['a_swp'])) $jstats['a_swp'] = 0;
	if (empty($rstats))          $rstats          = 0;

	$result =
		'mem:' . $jstats['a_mem'] . ' ' .
		'res:' . $rstats          . ' ' .
		'swp:' . $jstats['a_swp'] . "\n";

	return trim($result);
}
