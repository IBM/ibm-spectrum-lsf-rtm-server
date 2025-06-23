#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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
 * Sends back
 *
 * 2 if LSF OK and RTM OK
 * 1 if LSF not OK and RTM OK
 * 0 if RTM not OK and LSF OK

 * LSF state is based on lsf_ls_satus and lsf_base_status in the grid_clusters
 * table; LSf state=OK if both are=0, LSF state ont OK if one of them not equal 0.
 *
 * Exceptions:
 * if RTM  state =Admin down or Maintenance or cluster is disabled, returns 2
 * if LSF state is not OK, returns 1 even if RTM state is down
 */
$no_http_headers = true;

/* display ALL errors */
error_reporting(0);

if (!isset($called_by_script_server)) {
	include_once(__DIR__ . '/../../../include/global.php');

	array_shift($_SERVER['argv']);

	print call_user_func_array('ss_grid_status', $_SERVER['argv']);
}

function ss_grid_status($clusterid, $detail='') {
	$cluster = db_fetch_row_prepared('SELECT *
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	$stats = grid_get_cluster_collect_status($cluster);

	if ($detail == 'details') {

		$output = '<table>';
		$output .= '<tr>';
		$output .= '<td>Cluster ID</td>';
		$output .= '<td>Cluster Name</td>';
		$output .= '<td>State</td>';
		$output .='</tr>';
		$output .= '<tr>';
		$output .= '<td>'  . $cluster['clusterid'] . '</td>';
		$output .= '<td>'  . $cluster['clustername'] . '</td>';
		$output .= '<td>'  . $stats . '</td>';
		$output .='</tr>';
		$output .= '</table>';

		return $output;
	}

	//print_r($cluster);
	// get RTM collect status
	//echo $stats;
	if (empty($stats)) return 0;
	// filter out the disabled state and Maintenance and Admin down states
	if ($cluster['disabled'] == 'on') return 2;
	if ($stats=='Admin Down')  return 2;
	if ($stats=='Maintenance')  return 2;
	// Collect status OK now Check LSF status
	// return 2 only if both base and batch status are Ok, else return 1 (collect status Oki but LSF status not OK)
	if ($cluster['lsf_ls_error']!=0) return 1;
	if ($cluster['lsf_lsb_error']!=0) return 1;
	// Collect status not up return 0
	if ($stats!='Up') return 0;
	return 2;
}

