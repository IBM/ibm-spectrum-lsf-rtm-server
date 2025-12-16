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

function upgrade_to_9_1_2() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../grid/lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../grid/include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading heirustics to v9.1.2 ...', true, 'UPGRADE');

	/*heuristic plugin table structure update*/
	/*1. run_70thp is wrongly named previously, change it to run_75thp if applicable*/
	$column_70thp = db_fetch_assoc("SHOW COLUMNS FROM grid_heuristics LIKE 'run_70thp'");
	if (cacti_sizeof($column_70thp)) {
		execute_sql ("change name","ALTER TABLE `grid_heuristics` CHANGE `run_70thp` `run_75thp` bigint(20) unsigned DEFAULT NULL;");
	}

	/*2. remove field type from table grid_heuristics; add a new column "run_25thp*/
	$column_25thp = db_fetch_assoc("SHOW COLUMNS FROM grid_heuristics LIKE 'run_25thp'");
	if (!cacti_sizeof($column_25thp)) {
		execute_sql("Modify table grid_heuristics",
		"ALTER TABLE `grid_heuristics` ADD COLUMN `run_25thp` bigint(20) unsigned DEFAULT NULL AFTER `run_median`;");
	}
	$column_type = db_fetch_assoc("SHOW COLUMNS FROM grid_heuristics LIKE 'type'");
	if (cacti_sizeof($column_type)) {
		execute_sql("Modify table grid_heuristics",
		"ALTER TABLE `grid_heuristics` DROP COLUMN `type`;");
	}
	$column_resReq = db_fetch_assoc("SHOW COLUMNS FROM grid_heuristics LIKE 'resreq'");
	if (cacti_sizeof($column_resReq)) {
		execute_sql("Modify table grid_heuristics",
		"ALTER TABLE `grid_heuristics` MODIFY COLUMN `resReq` varchar(512) NOT NULL;");
	}
}
