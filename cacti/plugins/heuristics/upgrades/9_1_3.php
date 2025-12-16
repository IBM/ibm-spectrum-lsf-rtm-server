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

function upgrade_to_9_1_3() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../grid/lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../grid/include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading heirustics to v9.1.3 ...', true, 'UPGRADE');

	$cacti_pos=strrpos(realpath(dirname(__FILE__)), "/cacti/");
	$rtm_top=substr(realpath(dirname(__FILE__)),0,$cacti_pos);

	execute_sql("Modify table grid_heuristics_user_stats",
			"ALTER TABLE `grid_heuristics_user_stats`
			MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT '';");

	execute_sql("Modify table grid_heuristics_user_history_yesterday ",
			"ALTER TABLE `grid_heuristics_user_history_yesterday`
			MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT '';");

	execute_sql("Modify table grid_heuristics_user_history_today",
			"ALTER TABLE `grid_heuristics_user_history_today`
			MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT '';");
}
