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

function upgrade_to_10_2_0_11() {
	global $system_type, $config;
	global $database_hostname, $database_port, $database_username, $database_password, $database_default;

    include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/functions.php');

	execute_sql("Update timeout as the meaning of timeout is changed", "UPDATE lic_services SET timeout=poller_interval WHERE poller_interval>timeout");
	execute_sql("Update License Service Dashboard user realm", 'UPDATE plugin_realms SET file="lic_servicedb.php,lic_options.php,lic_details.php,lic_usage.php,lic_checkouts.php,lic_dailystats.php,lic_service_summary.php" WHERE plugin="license" AND file LIKE "%lic_servicedb.php%";');

}
