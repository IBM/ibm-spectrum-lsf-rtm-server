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
	Returns how many hosts exceed mem percentage used
*/

error_reporting(0);

if (!isset($called_by_script_server)) {
	include(dirname(__FILE__) . '/../include/cli_check.php');
	include_once(dirname(__FILE__) . '/../lib/functions.php');
	array_shift($_SERVER['argv']);
	print call_user_func_array('ss_lic_server_poller_stats', $_SERVER['argv']);
}

function ss_lic_server_poller_stats($lic_server_id = 0) {
	$poller_stat = '';
	$res         = array();

	$lic_type = db_fetch_cell_prepared('SELECT lp.poller_type
		FROM lic_services ls
		INNER JOIN lic_pollers lp
		ON lp.id = ls.poller_id
		WHERE ls.service_id = ?',
		array($lic_server_id));

	$res = db_fetch_row_prepared('SELECT availability, cur_time
		FROM lic_services
		WHERE service_id = ?',
		array($lic_server_id));

	if (cacti_sizeof($res)) {
		$poller_avail   = $res['availability'];
		$poller_runtime = $res['cur_time'];
		$poller_stat    = 'avail:' . number_format($poller_avail, 2) . ' runtime:' . number_format($poller_runtime, 3);
	} else {
		$poller_stat    = 'avail:0 runtime:0';
	}

	return $poller_stat;
}
