<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

function upgrade_to_9_1_4() {
    global $config;

    include_once($config['library_path'] . '/rtm_functions.php');
    include_once($config['library_path'] . '/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v9.1.4 ...', true, 'UPGRADE');

    //-----fix 32977: The base time is not working in Grid Alert
 	modify_column('gridalarms_alarm','base_time',"MODIFY COLUMN `base_time` int(10) unsigned default '0'");
	modify_column('gridalarms_template','base_time',"MODIFY COLUMN `base_time` int(10) unsigned default '0'");

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridalarms_db_version', '9.1.4.0');");

    return 0;
}
