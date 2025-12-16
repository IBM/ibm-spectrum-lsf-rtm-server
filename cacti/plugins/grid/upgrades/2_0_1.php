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

function upgrade_to_2_0_1() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	$column_arr= array(
		'host_template_id' => "ADD COLUMN `host_template_id` mediumint(8) unsigned NOT NULL default '14' AFTER `add_frequency`",
		'add_graph_frequency' => "ADD COLUMN `add_graph_frequency` int(10) unsigned NOT NULL default '0' AFTER `host_template_id`"
		);
	add_columns_indexes("grid_clusters", $column_arr, NULL);
	add_index("grid_license_servers_feature_details", "status", "ALTER TABLE `grid_license_servers_feature_details` ADD INDEX `status`(`status`)");
}
