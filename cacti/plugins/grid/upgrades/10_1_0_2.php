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

function upgrade_to_10_1_0_2() {
    global $config;

    include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../include/grid_constants.php');
    include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.2 ...', true, 'UPGRADE');

    add_column('grid_hosts_resources', 'last_updated', "ADD COLUMN `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP AFTER `present`;");
}
