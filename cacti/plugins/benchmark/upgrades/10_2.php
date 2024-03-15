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

function upgrade_to_10_2() {
	global $config;

	include_once($config['library_path'] . '/rtm_plugins.php');

	plugin_rtm_migrate_realms('benchmark', 876525, 'View Benchmark Job', 'grid_benchmark_jobs.php,grid_benchmark_summary.php', $version);
	plugin_rtm_migrate_realms('benchmark', 876526, 'Edit Benchmark Job Configuration', 'benchmark.php', $version);
}
