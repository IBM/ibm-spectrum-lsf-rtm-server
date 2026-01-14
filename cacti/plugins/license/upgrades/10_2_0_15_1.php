<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2026                                          |
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

function upgrade_to_10_2_0_15_1() {
	global $system_type, $config;

	include_once($config['library_path'] . '/rtm_db_upgrade.php');
	include_once($config['library_path'] . '/rtm_plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/database.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');

	cacti_log('NOTE: Upgrading license to v10.2.0.15.1 ...', true, 'UPGRADE');

	// Turn on only processing the license key features
	db_execute("INSERT INTO settings VALUES ('lic_process_keyfeatures_enable', 'on')");

	// Enlarge version to allow extra digits
	db_execute("ALTER TABLE plugin_config MODIFY COLUMN version VARCHAR(12) NOT NULL DEFAULT '';");

	//update version
	db_execute("UPDATE plugin_config SET version='10.2.0.15.1' WHERE directory='license'");

	if (!db_column_exists('lic_services_feature_details', 'lm_job_pid')) {
		execute_sql("Add lm_job_pid column and others to lic_services_feature_details", "ALTER TABLE lic_services_feature_details 
						ADD COLUMN lm_job_pid int(10) unsigned DEFAULT 0, 
                  		ADD COLUMN clustername VARCHAR(128) DEFAULT '',
                  		ADD COLUMN jobid bigint(20) DEFAULT 0,
                  		ADD COLUMN indexid int(10)  DEFAULT 0,
                  		ADD COLUMN projectName VARCHAR(255) DEFAULT '';");
	}
	if (!db_column_exists('lic_services_feature_history', 'lm_job_pid')) {
		execute_sql("Add lm_job_pid column and others to lic_services_feature_history", "ALTER TABLE lic_services_feature_history 
						ADD COLUMN lm_job_pid int(10) unsigned DEFAULT 0, 
                  		ADD COLUMN clustername VARCHAR(128) DEFAULT '',
                  		ADD COLUMN jobid bigint(20) DEFAULT 0,
                  		ADD COLUMN indexid int(10)  DEFAULT 0,
                  		ADD COLUMN projectName VARCHAR(255) DEFAULT '';");
	}

	create_table("lic_daily_project_stats_today", "CREATE TABLE IF NOT EXISTS `lic_daily_project_stats_today` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`service_id` int(10) unsigned NOT NULL DEFAULT 0,
					`feature_name` varchar(50) NOT NULL DEFAULT '0',
					`projectName` varchar(50) NOT NULL DEFAULT '',
					`token_minutes` varchar(50) NOT NULL DEFAULT '',
					`feature_max_licenses` varchar(60) NOT NULL DEFAULT '',
					`poll_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY (`id`),
					KEY `idx_feature_name` (`feature_name`),
					KEY `idx_projectName` (`projectName`),
					KEY `idx_service_id` (`service_id`)
				) ENGINE=InnoDB");

	create_table("lic_daily_project_stats", "CREATE TABLE IF NOT EXISTS `lic_daily_project_stats` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`service_id` int(10) unsigned NOT NULL DEFAULT 0,
					`feature_name` varchar(50) NOT NULL DEFAULT '0',
					`projectName` varchar(50) NOT NULL DEFAULT '',
					`token_minutes` varchar(50) NOT NULL DEFAULT '',
					`feature_max_licenses` varchar(60) NOT NULL DEFAULT '',
					`poll_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY (`id`),
					KEY `idx_feature_name` (`feature_name`),
					KEY `idx_projectName` (`projectName`),
					KEY `idx_service_id` (`service_id`)
				) ENGINE=InnoDB");

    api_plugin_register_hook('license', 'page_head', 'license_page_head', 'setup.php', 1);
	api_plugin_register_realm('license', 'lic_servicedb.php,lic_options.php,lic_details.php,lic_usage.php,lic_checkouts.php,lic_dailystats.php,lic_service_summary.php,lic_daily_project_use.php', 'View License Usage Data', 1);
	db_execute("UPDATE plugin_realms set file='lic_servicedb.php,lic_options.php,lic_details.php,lic_usage.php,lic_checkouts.php,lic_dailystats.php,lic_service_summary.php,lic_daily_project_use.php' WHERE id = 30");
}
