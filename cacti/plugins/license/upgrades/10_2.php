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

function upgrade_to_10_2() {
	global $system_type, $config;

	include_once($config['library_path'] . '/rtm_db_upgrade.php');
	include_once($config['library_path'] . '/rtm_plugins.php');

	$version = '10.2';

	cacti_log('NOTE: Upgrading license to v10.2.0.0 ...', true, 'UPGRADE');

	//doing the rest db schama change in 10.2.0
	create_table("lic_daily_stats_today", "CREATE TABLE IF NOT EXISTS `lic_daily_stats_today` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`user` varchar(40) NOT NULL DEFAULT '',
		`host` varchar(64) NOT NULL DEFAULT '',
		`action` varchar(20) NOT NULL DEFAULT '',
		`count` int(10) unsigned NOT NULL DEFAULT '0',
		`total_license_count` int(10) unsigned NOT NULL DEFAULT '0',
		`utilization` float NOT NULL DEFAULT '0',
		`peak_ut` float NOT NULL DEFAULT '0',
		`vendor` varchar(40) NOT NULL DEFAULT '0',
		`duration` int(10) unsigned NOT NULL DEFAULT '0',
		`transaction_count` int(10) unsigned NOT NULL DEFAULT '0',
		`type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
		`interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `interval_end` (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `vendor` (`vendor`)
	) ENGINE=InnoDB");

	add_index("lic_application_feature_map", "feature_name", "ADD INDEX `feature_name` (`feature_name`);");

	modify_column('lic_services', 'server_name', "MODIFY COLUMN `server_name` varchar(256) NOT NULL default ''");

	execute_sql("Remove obsoleted RTM poller license info", "DELETE FROM `settings` WHERE name like 'licpoller\_%'");

	//license realm upgrade
	plugin_rtm_migrate_realms('license', 29, 'View License Admin Data', 'lic_lm_fusion.php', $version);
	plugin_rtm_migrate_realms('license', 30, 'View License Usage Data', 'lic_servicedb.php,lic_options.php,lic_details.php,lic_usage.php,lic_checkouts.php,lic_dailystats.php', $version);
	plugin_rtm_migrate_realms('license', 45, 'License Administration', 'lic_pollers.php,lic_servers.php,lic_managers.php,lic_feature_maps.php,lic_applications.php', $version);
}
