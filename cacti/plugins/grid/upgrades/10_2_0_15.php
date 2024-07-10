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

function upgrade_to_10_2_0_15() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');

	$column_arr= array(
		'cpuPeak' => "ADD COLUMN `cpuPeak` decimal(9,5) NOT NULL default '0.00000' AFTER `isLoaningGSLA`",
		'peakEfficiency' => "ADD COLUMN `peakEfficiency` decimal(9,5) NOT NULL default '0.00000' AFTER `cpuPeak`",
		'memEfficiency' => "ADD COLUMN `memEfficiency` decimal(9,5) NOT NULL default '0.00000' AFTER `peakEfficiency`",
		'cpuPeakReachedTime' => "ADD COLUMN `cpuPeakReachedTime` double NOT NULL default '0' AFTER `memEfficiency`"	  
	);
	//Do not use Cacti::db_update_table because db_update_table alter table column one by one
	add_columns("grid_jobs", $column_arr);
	add_columns("grid_jobs_finished", $column_arr);

	$data = array();
	$data['columns'][] = array('name' => 'avgCpuEffi', 'type' => 'decimal(9,5)', 'NULL' => false, 'default' => '0.00000');
	$data['primary'] = array('clusterid','jobid','indexid','submit_time','update_time');
	$data['type']  = 'InnoDB';
	db_update_table('grid_jobs_rusage', $data);

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.15' WHERE directory IN ('RTM', 'license', 'meta', 'lichist')");
}

function partition_tables_to_10_2_0_15(){
	return array(
		'grid_jobs_finished' => array(
			'columns' => array(
				'cpuPeak' => array('type' => 'decimal(9,5)', 'NULL' => false, 'default' => '0.00000', 'after' => 'isLoaningGSLA'),
				'peakEfficiency' => array('type' => 'decimal(9,5)', 'NULL' => false, 'default' => '0.00000', 'after' => 'cpuPeak'),
				'memEfficiency' => array('type' => 'decimal(9,5)', 'NULL' => false, 'default' => '0.00000', 'after' => 'peakEfficiency'),
				'cpuPeakReachedTime' => array('type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'memEfficiency')
			)
		)
	);
}