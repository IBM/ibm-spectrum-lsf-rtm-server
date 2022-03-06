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

function upgrade_to_8_3() {
	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	add_index("data_template_data", "data_input_id", "ALTER TABLE `data_template_data` ADD INDEX `data_input_id`(`data_input_id`);");
	add_index("graph_templates_item", "graph_template_task_item_id", "ALTER TABLE `graph_templates_item` ADD INDEX `graph_template_task_item_id`(`graph_template_id`,`task_item_id`);");
	execute_sql("Change grid_jobs_rusage to InnoDB", "ALTER TABLE `grid_jobs_rusage` ENGINE=InnoDB");
	execute_sql("Change lic_flexlm_servers_feature_details to InnoDB", "ALTER TABLE `lic_flexlm_servers_feature_details` ENGINE=InnoDB");
	execute_sql("Add user auth realm for Nectar Reports Admin", "REPLACE INTO `user_auth_realm` VALUES (114,1);");
}
