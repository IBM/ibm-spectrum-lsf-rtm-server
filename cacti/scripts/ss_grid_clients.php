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
	print call_user_func_array('ss_grid_clients', $_SERVER['argv']);
}

function ss_grid_clients($clusterid = 0) {
	$last_update = db_fetch_cell_prepared('SELECT (UNIX_TIMESTAMP(max(last_seen))-3200)
		FROM grid_hostinfo
		WHERE clusterid = ?',
		array($clusterid));

	$numFloatClients = db_fetch_cell_prepared("SELECT COUNT(*)
	   	FROM grid_hostinfo
	   	WHERE clusterid = ?
		AND isServer = '0'
		AND licFeaturesNeeded = '512'
		AND UNIX_TIMESTAMP(last_seen) >= ?",
		array($clusterid, $last_update));

	$numStaticClients = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM grid_hostinfo
		WHERE clusterid = ?
		AND isServer = '0'
		AND licFeaturesNeeded = '16'
		AND UNIX_TIMESTAMP(last_seen) >= ?",
		array($clusterid, $last_update));

	if (empty($numFloatClients))  $numFloatClients  = 0;
	if (empty($numStaticClients)) $numStaticClients = 0;

	$result = 'numFloatClients:' . $numFloatClients . ' ' .
		'numStaticClients:' . $numStaticClients . "\n";

	return trim($result);
}
