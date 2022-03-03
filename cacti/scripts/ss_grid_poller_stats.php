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
	print call_user_func_array('ss_grid_poller_stats', $_SERVER['argv']);
}

function ss_grid_poller_stats($clusterid = 0) {
	global $config;

	$poller_stats = '';
	$date_minor = 0;
	$date_major = 0;

	$job_stats_minor = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Minor');
	$job_stats_major = read_config_option('grid_update_time_bjobs_' . $clusterid . '_Major');
	$job_stats_pend = read_config_option('grid_update_time_bpend_' . $clusterid );
	$lshosts_stats = read_config_option('grid_update_time_lshosts_'. $clusterid );
	$lsload_stats = read_config_option('grid_update_time_lsload_'. $clusterid );
	$bhostgroups_stats = read_config_option('grid_update_time_bhostgroups_'. $clusterid );
	$array_stats = read_config_option('grid_update_time_array_'. $clusterid );
	$params_stats = read_config_option('grid_update_time_params_'. $clusterid );
	$usergroups_stats = read_config_option('grid_update_time_usergroups_'. $clusterid );
	$bhosts_stats = read_config_option('grid_update_time_bhosts_'. $clusterid );
	$bqueues_stats = read_config_option('grid_update_time_bqueues_'. $clusterid );
	$busers_stats = read_config_option('grid_update_time_busers_'. $clusterid );

	if (strlen($job_stats_minor)) {
		$stats = explode(' ', $job_stats_minor);
		$date_minor = strtotime(substr_replace('_', ' ', $stats[0]));
	}

	if (strlen($job_stats_major)) {
		$stats = explode(' ', $job_stats_major);
		$date_major = strtotime(substr_replace('_', ' ', $stats[0]));
	}

	if ($date_major > $date_minor) {
		$job_stats = $job_stats_major;
	}else{
		$job_stats = $job_stats_minor;
	}

	if (strlen($job_stats)) {
		$stat_array = explode(' ', $job_stats);

		$runtime   = 0;
		$mbdtime   = 0;
		$mbdpptime = 0;
		$running   = 0;
		$done      = 0;

		if (sizeof($stat_array)) {
			foreach($stat_array as $stat) {
				$job_stat = explode(':', $stat);

				$variable = $job_stat[0];
				$value    = $job_stat[1];

				switch ($variable) {
				case 'Runtime':
					$runtime   = $value;
					break;
				case 'MBDtime':
					$mbdtime   = $value;
					break;
				case 'MBDppMax':
					$mbdpptime = $value;
					break;
				case 'Running':
					$running   = $value;
					break;
				case 'Done':
					$done      = $value;
				}

			}

			$poller_stats =
				'Runtime:'   . $runtime   .
				' MBDtime:'  . $mbdtime   .
				' MBDppMax:' . $mbdpptime .
				' Running:'  . $running   .
				' Done:'     . $done . ' ';
		} else {
			$poller_stats =  'Runtime:0 MBDtime:0 MBDppMax:0 Running:0 Done:0 ';
		}
	} else {
		$poller_stats = 'Runtime:0 MBDtime:0 MBDppMax:0 Running:0 Done:0 ';
	}

	if (strlen($job_stats_pend)) {
		$stat_array_pend = explode(' ', $job_stats_pend);

		$runtime   = 0;
		$mbdtime   = 0;
		$mbdpptime = 0;
		$pending   = 0;
		$Suspended   = 0;

		if (sizeof($stat_array_pend)) {
			foreach($stat_array_pend as $stat) {
				$job_stat = explode(':', $stat);

				$variable = $job_stat[0];
				$value    = $job_stat[1];

				switch ($variable) {
				case 'Runtime':
					$runtime   = $value;
					break;
				case 'MBDtime':
					$mbdtime   = $value;
					break;
				case 'MBDppMax':
					$mbdpptime = $value;
					break;
				case 'Pending':
					$pending   = $value;
					break;
				case 'Suspended':
					$Suspended   = $value;
					break;
				}

			}

			$poller_stats .=
				'Pend_Runtime:'   . $runtime   .
				' Pend_MBDtime:'  . $mbdtime   .
				' Pend_MBDppMax:' . $mbdpptime .
				' Pending:'       . $pending   .
				' Suspended:'     . $Suspended . ' ';
		} else {
			$poller_stats .=  'Pend_Runtime:0 Pend_MBDtime:0 Pend_MBDppMax:0 Pending:0 Suspended:0 ';
		}
	} else {
		$poller_stats .=  'Pend_Runtime:0 Pend_MBDtime:0 Pend_MBDppMax:0 Pending:0 Suspended:0 ';
	}

	// other poller stats
	$poller_stats = $poller_stats . 'lshosts_'     . get_runtime($lshosts_stats);
	$poller_stats = $poller_stats . 'lsload_'      . get_runtime($lsload_stats);
	$poller_stats = $poller_stats . 'bhostgroups_' . get_runtime($bhostgroups_stats);
	$poller_stats = $poller_stats . 'barray_'      . get_runtime($array_stats);
	$poller_stats = $poller_stats . 'bparams_'     . get_runtime($params_stats);
	$poller_stats = $poller_stats . 'busergroups_' . get_runtime($usergroups_stats);
	$poller_stats = $poller_stats . 'bhosts_'      . get_runtime($bhosts_stats);
	$poller_stats = $poller_stats . 'bqueues_'     . get_runtime($bqueues_stats);
	$poller_stats = $poller_stats . 'busers_'      . get_runtime($busers_stats);

	return trim($poller_stats);
}

function get_runtime($statsvalue) {
	$stat_array = explode(' ', $statsvalue);
	$runtime   = 0;

	if (sizeof($stat_array)) {
		foreach($stat_array as $stat) {
			$stats = explode(':', $stat);

			$variable = $stats[0];

			if (isset($stats[1])) {
				$value    = $stats[1];
			}else{
				$value    = 'U';
			}

			if ($variable == 'Runtime') {
				$runtime   = $value;
			}
		}
	}
	return 'Runtime:' . $runtime . ' ';
}
