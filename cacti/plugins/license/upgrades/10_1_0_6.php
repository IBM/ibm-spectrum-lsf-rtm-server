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

function upgrade_to_10_1_0_6() {
    global $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading license to v10.1.0.6 ...', true, 'UPGRADE');

	//For SUP#189661
	execute_sql("Append user auth realm of License Administrator for build-in Admin", "REPLACE INTO `user_auth_realm` VALUES ('45',1)");

	//For SUP#189978
	create_table("lic_users_winsp", "CREATE TABLE IF NOT EXISTS `lic_users_winsp` (
		`user` varchar(40) NOT NULL DEFAULT '',
		PRIMARY KEY (`user`)
		) ENGINE=InnoDB");
}
