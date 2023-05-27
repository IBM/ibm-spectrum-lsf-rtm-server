<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

function upgrade_to_10_2_0_14() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	$data = array();
	$data['columns'][] = array('name' => 'maxjobs', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['primary'] = array('queuename','clusterid');
	db_update_table('grid_queues', $data);

	$data = array();
	$data['columns'][] = array('name' => 'resource_name', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['primary'] = array('clusterid','hostname','resource_name');
	db_update_table('grid_host_threshold', $data);

	$data = array();
	$data['columns'][] = array('name' => 'resource_name', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['primary'] = array('clusterid','resource_name');
	db_update_table('grid_sharedresources', $data);

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.14' WHERE directory IN ('RTM', 'benchmark', 'disku', 'gridalarms', 'gridblstat', 'gridcstat', 'gridpend', 'heuristics', 'license', 'lichist', 'meta')");
}
