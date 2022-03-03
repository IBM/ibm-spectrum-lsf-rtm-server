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

function upgrade_to_9_1_2() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading grid to v9.1.2 ...', true, 'UPGRADE');

	add_index("data_input_data", "data_template_data_id",
		"ADD INDEX `data_template_data_id`(`data_template_data_id`)");

	add_index("grid_elim_template_instances", "grid_elim_template_id",
		"ADD INDEX `grid_elim_template_id`(`grid_elim_template_id`)");

	execute_sql("Modify table grid_jobs",
		"ALTER TABLE `grid_jobs` ADD COLUMN `jobDescription` varchar(512) default '' AFTER `userGroup` ,
		ADD COLUMN `combinedResreq` varchar(512) default '' AFTER `jobDescription` ,
		ADD COLUMN `effectiveResreq` varchar(512) default '' AFTER `combinedResreq`,
		MODIFY COLUMN `command` varchar(1024) default NULL,
		MODIFY COLUMN `res_requirements` varchar(512) default NULL,
		DROP COLUMN `completion_time`,
		DROP INDEX `completion_time`,
		DROP COLUMN `newCommand`");

	execute_sql("Modify table grid_jobs_finished",
		"ALTER TABLE `grid_jobs_finished` ADD COLUMN `jobDescription` varchar(512) default '' AFTER `userGroup` ,
		ADD COLUMN `combinedResreq` varchar(512) default '' AFTER `jobDescription` ,
		ADD COLUMN `effectiveResreq` varchar(512) default '' AFTER `combinedResreq`,
		MODIFY COLUMN `command` varchar(1024) default NULL,
		MODIFY COLUMN `res_requirements` varchar(512) default NULL,
		DROP COLUMN `completion_time`,
		DROP INDEX `completion_time`,
		DROP COLUMN `newCommand`");

	/*3. enlarge table grid_setting.value to 1024*/
	execute_sql("Modify table grid_settings",
		"ALTER TABLE `grid_settings` MODIFY COLUMN `value` varchar(1024) NOT NULL default ''");

	execute_sql("Modify table grid_clusters",
		"ALTER TABLE `grid_clusters` MODIFY COLUMN `lsf_ego` char(3) default 'N'");

	execute_sql("Modify table host_snmp_cache",
		"ALTER TABLE `host_snmp_cache` MODIFY COLUMN `field_value` varchar(512) default NULL");

	execute_sql("Modify table settings",
		"ALTER TABLE `settings` MODIFY COLUMN `value` varchar(1024) NOT NULL default ''");

	add_column("host", "device_threads",
		"ADD COLUMN `device_threads` tinyint(2) unsigned NOT NULL DEFAULT '1' AFTER `max_oids`");

	cacti_log("NOTE: Upgrade table: lic_daily_stats", true, 'UPGRADE');

	db_execute("ALTER TABLE `lic_daily_stats`
		ADD COLUMN `type` ENUM('0','1','2','3','4','5','6','7','8')  NOT NULL default '0' AFTER `transaction_count`,
		ADD COLUMN `peak_ut` float  NOT NULL default '0' AFTER `utilization`");

	db_execute("UPDATE lic_daily_stats SET type='8' WHERE user='' AND host=''");
}

