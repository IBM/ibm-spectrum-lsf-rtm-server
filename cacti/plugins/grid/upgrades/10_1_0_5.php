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

function upgrade_to_10_1_0_5() {
    global $config;

    include_once(dirname(__FILE__) . '/../../../lib/functions.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.5 ...', true, 'UPGRADE');

	//Update Auto-Purge Device for Grid Hosts
	if(read_config_option("grid_host_autopurge") == 2)
		set_config_option("grid_host_autopurge", "-1");
	else if(read_config_option("grid_host_autopurge") == 1)
		set_config_option("grid_host_autopurge", "180");

	//Since 10.1.0 FP5, lic.realm[39] was coverred by to lic.realm[30]
	//execute_sql("Update user auth realm of License Checkout page", "UPDATE `user_auth_realm` SET realm_id=31 WHERE realm_id=39;");
}
