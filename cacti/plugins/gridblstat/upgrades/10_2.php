<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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

function upgrade_to_10_2() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading gridblstat to v10.2.0.0 ...', true, 'UPGRADE');

	add_index("grid_blstat_feature_map", "lic_feature", "ADD INDEX `lic_feature` USING BTREE (`lic_feature`);");

	//gridblstat
	$db_realm_id = db_fetch_cell_prepared('SELECT id+100 FROM plugin_realms WHERE plugin = ? AND display = ?', array('grid', 'LSF Administration'), false);
	execute_sql("Merge 'License Scheduler Administration' to 'LSF Administration'.", "INSERT IGNORE INTO user_auth_realm (realm_id, user_id) SELECT $db_realm_id realm_id, user_id FROM user_auth_realm WHERE realm_id=46");
}
