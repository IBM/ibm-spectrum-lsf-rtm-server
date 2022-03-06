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

function upgrade_to_10_2_0_13() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');

	$data = array();
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '', 'after' => 'queue');
	$data['primary'] = array('clusterid','queue','custom','projectName','resReq','reqCpus');
	$data['charset'] = 'latin1';
	db_update_table('grid_heuristics', $data);
}
