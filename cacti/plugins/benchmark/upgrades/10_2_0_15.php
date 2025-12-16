<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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

function upgrade_to_10_2_0_15() {
	global $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');

	api_plugin_register_hook('benchmark', 'grid_cluster_remove', 'benchmark_grid_cluster_remove', 'setup.php', 1);

	$column_arr = array(
		'task_num_in_job' => "ADD COLUMN `task_num_in_job` varchar(64) default NULL AFTER `pjob_startTime`",
		'exclusive_job' => "ADD COLUMN `exclusive_job` char(2) default '' AFTER `task_num_in_job`" 
	);
	add_columns("grid_clusters_benchmarks", $column_arr);

	return 0;
}
