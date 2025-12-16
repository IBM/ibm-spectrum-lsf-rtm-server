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

function upgrade_to_10_2_0_1() {
	global $system_type, $config;
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');

	cacti_log('NOTE: Upgrading gridblstat to v10.2.0.1 ...', true, 'UPGRADE');

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'fd_target');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = array('lsid','feature','service_domain');
	db_update_table('grid_blstat', $data);

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'max_reclaim');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = array('lsid','feature','service_domain', 'cluster');
	db_update_table('grid_blstat_clusters', $data);

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'avail');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = array('lsid','feature','project', 'cluster');
	db_update_table('grid_blstat_cluster_use', $data);

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'demand');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = array('lsid','feature','service_domain', 'project');
	db_update_table('grid_blstat_projects', $data);
}
