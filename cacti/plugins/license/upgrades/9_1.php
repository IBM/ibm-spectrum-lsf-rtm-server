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

function upgrade_to_9_1() {
	global $system_type, $config;
    global $rtm;

	cacti_log('NOTE: Upgrading license to v9.1 ...', true, 'UPGRADE');

    execute_sql("Modify table lic_flexlm_servers_feature_details",
		"ALTER TABLE `lic_flexlm_servers_feature_details`
        MODIFY COLUMN `feature_name` varchar(40) NOT NULL default '0',
        MODIFY COLUMN `username` varchar(40) NOT NULL default ''");

    execute_sql("Modify table lic_interval_stats",
		"ALTER TABLE `lic_interval_stats`
        MODIFY COLUMN `feature` varchar(50) NOT NULL default '',
        MODIFY COLUMN `user` varchar(40) NOT NULL default '',
        MODIFY COLUMN `host` varchar(64) NOT NULL default '',
        MODIFY COLUMN `vendor` varchar(20) NOT NULL default '0'");

    execute_sql("Modify table lic_daily_stats",
		"ALTER TABLE `lic_daily_stats`
        MODIFY COLUMN `feature` varchar(50) NOT NULL default '',
        MODIFY COLUMN `user` varchar(40) NOT NULL default '',
        MODIFY COLUMN `vendor` varchar(40) NOT NULL default '0'");

    execute_sql("Modify table lic_daily_stats_traffic",
		"ALTER TABLE `lic_daily_stats_traffic`
        MODIFY COLUMN `feature` varchar(50) NOT NULL,
        MODIFY COLUMN `user` varchar(40) NOT NULL");
}

