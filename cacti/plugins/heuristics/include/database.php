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

function heuristics_setup_table_new() {
	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'resReq', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reqCpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'jobs', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'cores', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'run_avg', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_max', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_min', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_stddev', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_median', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_25thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_75thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_90thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_avg', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_max', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_min', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_stddev', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_median', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_25thp', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_75thp', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_90thp', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_avg', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_max', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_min', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_stddev', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_median', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_25thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_75thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_90thp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jph_avg', 'type' => 'float', 'NULL' => false);
	$data['columns'][] = array('name' => 'jph_3std', 'type' => 'float', 'NULL' => false);
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`queue`,`custom`,`projectName`,`resReq`,`reqCpus';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['row_format'] = 'Dynamic';
	$data['comment'] = 'Captures throughput history both recent and long term';
	api_plugin_db_table_create('heuristics', 'grid_heuristics', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'resReq', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reqCpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'run_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_memory', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem_used', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pend_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'partition', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Table used for percentile calculations';
	api_plugin_db_table_create ('grid', 'grid_heuristics_percentiles', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reqCpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numDONE', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numEXIT', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tputHOUR', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tput5MIN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`queue`,`custom`,`projectName`,`user`,`reqCpus`,`last_updated';
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'last_updated', 'columns' => 'last_updated');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Throughput Statistics on User Jobs for Todays Records';
	api_plugin_db_table_create('heuristics', 'grid_heuristics_user_history_today', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reqCpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numDONE', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numEXIT', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tputHOUR', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tput5MIN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`queue`,`custom`,`projectName`,`user`,`reqCpus`,`last_updated';
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'last_updated', 'columns' => 'last_updated');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Throughput Statistics on User Jobs for Todays Records';
	api_plugin_db_table_create('heuristics', 'grid_heuristics_user_history_yesterday', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'custom', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reqCpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numDONE', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numEXIT', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tputHOUR', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tput5MIN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`queue`,`custom`,`projectName`,`user`,`reqCpus';
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'last_updated', 'columns' => 'last_updated');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Throughput Statistics on User Jobs';
	api_plugin_db_table_create('heuristics', 'grid_heuristics_user_stats', $data);
}
