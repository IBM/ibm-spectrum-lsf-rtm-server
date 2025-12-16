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

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/functions.php');

	cacti_log('NOTE: Upgrading license to v10.1.0.5 ...', true, 'UPGRADE');

	$cacti_pos=strrpos(realpath(dirname(__FILE__)), "/cacti/");
	$rtm_top=substr(realpath(dirname(__FILE__)),0,$cacti_pos);

	create_table("lic_application_accounting", "CREATE TABLE IF NOT EXISTS `lic_application_accounting` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`application` varchar(40) DEFAULT '',
		`monthly_cost` double DEFAULT '0',
		`user_id` int(10) unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`id`),
		UNIQUE KEY `application` (`application`))
		ROW_FORMAT=Dynamic
		ENGINE=InnoDB");

	create_table("lic_application_feature_map", "CREATE TABLE IF NOT EXISTS `lic_application_feature_map` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`feature_name` varchar(50) NOT NULL DEFAULT '',
		`user_feature_name` varchar(80) DEFAULT '',
		`application` varchar(40) DEFAULT '',
		`manager_hint` varchar(255) DEFAULT '',
		`critical` tinyint(3) unsigned DEFAULT '0',
		`user_id` int(10) unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`service_id`,`feature_name`))
		ROW_FORMAT=Dynamic
		ENGINE=InnoDB");

	create_table("lic_managers", "CREATE TABLE IF NOT EXISTS `lic_managers` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`hash` char(32) DEFAULT NULL,
		`name` varchar(15) NOT NULL,
		`description` varchar(100) NOT NULL DEFAULT '',
		`type` tinyint(3) DEFAULT '0',
		`logparser_binary` varchar(127) NOT NULL,
		`collector_binary` varchar(127) DEFAULT NULL,
		`lm_client` varchar(127) DEFAULT NULL,
		`lm_client_arg1` varchar(127) DEFAULT NULL,
		`failover_hosts` tinyint(3) unsigned DEFAULT '1',
		`disabled` tinyint(3) DEFAULT '0',
		PRIMARY KEY (`id`))
		ROW_FORMAT=Dynamic
		ENGINE=InnoDB");

	execute_sql("Initial license managers", "INSERT INTO `lic_managers`
		(`id`, `hash`, `name`, `description`, `type`, `logparser_binary`, `collector_binary`, `lm_client`, `lm_client_arg1`, `failover_hosts`, `disabled`) VALUES
		(1,'9190583ab345af80da86efd5d683eb72','FLEXlm','Flexera License Manager',0,'','licflexpoller','lmstat','',3,0),
		(2,'db3168fc7be049ea84efe3cebd189cf0','LUM','LUM License Manager',0,'','liclumpoller','i4blt','',3,1),
		(3,'33fb6598074464bc113893507c623ee0','RLM','Reprise License Manager',0,'','liclmpoller','rlmstat','',2,0)");

	execute_sql("Upgrade license poller table",
		"ALTER TABLE lic_pollers
		MODIFY COLUMN `poller_type` smallint(5) NOT NULL DEFAULT '0',
		ADD COLUMN `poller_exechost` varchar(64) NOT NULL DEFAULT '' AFTER `poller_hostname`");

	execute_sql("Drop obsoleted columns from license service table",
		"ALTER TABLE lic_services
		DROP COLUMN `daemon_type`,
		DROP COLUMN `server_querybin_path`");

	execute_sql("Add vendor column column to license  servuce table",
		'ALTER TABLE lic_services
		ADD COLUMN server_subisv varchar(40) DEFAULT NULL AFTER server_portatserver');

	add_index('lic_pollers', 'poller_type',
		'ADD INDEX `poller_type` (`poller_type`)');

	create_table("lic_settings",
		'CREATE TABLE IF NOT EXISTS lic_settings LIKE grid_settings');

	$command_string = read_config_option("path_php_binary");
	$extra_args = "-q \"" . $config["base_path"] . "/plugins/grid/lib/database_table_upgrade.php\"";

	exec_background($command_string, $extra_args);
}
