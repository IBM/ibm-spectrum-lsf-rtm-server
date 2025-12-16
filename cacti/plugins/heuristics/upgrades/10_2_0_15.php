<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2025                                                |
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
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/database.php');
	
	db_execute("ALTER TABLE grid_heuristics MODIFY COLUMN projectName VARCHAR(256) NOT NULL DEFAULT '';");
	db_execute("ALTER TABLE grid_heuristics_percentiles MODIFY COLUMN projectName VARCHAR(256) NOT NULL DEFAULT '';");
	db_execute("ALTER TABLE grid_heuristics_user_history_today MODIFY COLUMN projectName VARCHAR(256) NOT NULL DEFAULT '';");
	db_execute("ALTER TABLE grid_heuristics_user_stats MODIFY COLUMN projectName VARCHAR(256) NOT NULL DEFAULT '';");
}
