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

include_once(dirname(__FILE__) . "/../../include/cli_check.php");

$old_realm = db_fetch_cell("SELECT id FROM plugin_realms WHERE file LIKE '%notify_lists.php%' AND plugin='gridalarms'");
$new_realm = db_fetch_cell("SELECT id FROM plugin_realms WHERE file LIKE '%notify_lists.php%' AND plugin='thold'");
$old_realm = $old_realm + 100;
$new_realm = $new_realm + 100;

//For pre-10.2 to 10.2 only
echo "Migrate notify_lists.php permission from gridalarms[$old_realm] to thold[$new_realm].\n";

if($old_realm != 100 && $new_realm != 100){
	db_execute_prepared('INSERT IGNORE INTO user_auth_realm (realm_id, user_id) SELECT ? realm_id, user_id FROM user_auth_realm WHERE realm_id=?', array($new_realm, $old_realm));
}
