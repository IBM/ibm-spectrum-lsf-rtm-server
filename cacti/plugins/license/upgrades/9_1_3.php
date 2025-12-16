<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

function upgrade_to_9_1_3() {
	global $config;

	include_once($config['library_path'] . '/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading license to v9.1.3 ...', true, 'UPGRADE');

	upgrade_standard_to_9_1_3();

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('lic_partitions_upgrade_status', 'scheduled')");

	return execute_sql_partitions('lic_daily_stats', 'Modify table lic_daily_stats',
		"ALTER TABLE `lic_daily_stats`
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0'");
}

function upgrade_standard_to_9_1_3() {
	/* Change FLEXlm to LM */
	execute_sql("Change FLEXlm to LM in data_template table",
		"UPDATE data_template
		SET name = REPLACE(name,'FLEXlm','LM')
		WHERE hash IN (
			'b578712ed4c320376f85b3e99f5f798d',
			'4e69c1e844b97cc1f53d7f6361e7b587',
			'01ef98b0c27f47c2273930677e92ba60',
			'0eafc8a3134dfe17337317b6cc0bca96',
			'0784dfd8d480ec05c45418e597478683',
			'6606671a013e2b8fcbf335757931a9c7',
			'55b6bf20807a09b9e97ac02f958025a1',
			'2773a868ffa8360cfbd7e06771c7d672'
		)");

	execute_sql("Change FLEXlm to LM in data_template_data table",
		"UPDATE data_template_data
		SET name = REPLACE(name,'FLEXlm','LM'), name_cache = REPLACE(name_cache,'FLEXlm','LM')
		WHERE data_template_id IN (
			SELECT id
			FROM data_template
			WHERE hash IN ('2773a868ffa8360cfbd7e06771c7d672')
		)");

	execute_sql("Change FLEXlm to LM in graph_templates table",
		"UPDATE graph_templates
		SET name = REPLACE(name,'FLEXlm','LM')
		WHERE hash IN (
			'9e25a00a6f9222d02389b6ecc97590f1',
			'620954e227a1972dd9de72b7b9edddd2',
			'dab23f124796196179eaefec678f3cbb',
			'1d2dedcb972def94556b55ee1717a833',
			'0a21c283961ea0f197d88e1edac8fcc4',
			'a32b454435cee6819bacb80d74e1d5b3',
			'a7dd1672ef3c91218208f3f6dfe90db2',
			'80049870e3c6f7a85188f327380a86d1',
			'b6f7e8df0fe1f326542d0489ef761708'
		)");

	execute_sql("Change FLEXlm to LM in graph_templates_graph table",
		"UPDATE graph_templates_graph
		SET title = REPLACE(title,'FLEXlm','LM'), title_cache = REPLACE(title_cache,'FLEXlm','LM')
		WHERE graph_template_id IN (
			SELECT id
			FROM graph_templates
			WHERE hash IN ('9e25a00a6f9222d02389b6ecc97590f1','b6f7e8df0fe1f326542d0489ef761708')
		)");

	execute_sql("Change FLEXlm to LM in host_template table",
		"UPDATE host_template
		SET name = REPLACE(name,'FLEXlm','LM')
		WHERE hash IN ('305007178521f03fde7e0a66c37784c8','4e7ef5bb8c546d214d565bb24763ab08')");

	execute_sql("Change FLEXlm to LM in snmp_query table",
		"UPDATE snmp_query SET name = REPLACE(name,'FLEXlm','LM'), description = REPLACE(description,'FLEXlm','LM')
		WHERE hash IN (
			'fadf071b0c4cb428c9ad83ff60f08203',
			'61b4fd11db421d90f1f599a4f2204c29',
			'80eec23a8ebd43b97d2cbd8102d71f73',
			'2467b9ab4bedbcb14c29b7142fe6eecf',
			'e19ac62de5a098278eeb78d40978e329',
			'd1b385d95af5b269b4e643c48e1e2702',
			'cac3fc5b68e160b0641cfe9e2f261ae4'
		)");

	execute_sql("Change FLEXlm to LM in snmp_query_graph table",
		"UPDATE snmp_query_graph
		SET name = REPLACE(name,'FLEXlm','LM')
		WHERE hash IN (
			'9c4403070a529eed04304d1fbd88f699',
			'a9b8db1140ba82262352a5790a6b7f89',
			'b4f418e3374064156b5110b1fb283fcc',
			'30b09edb646c6911e577eeba7c8bbbd0',
			'638a17c953083833e299f1b73ef18a15',
			'44ee8946db6965f9188a37dec9a9a5bb',
			'1089aa277f4d817651d7a9754d8511b1'
		)");

	//1. lic_services
	//   NOTE: for the case that uninstalled license plugin, then do the upgrade.
	if (db_table_exists('lic_services')) {
		execute_sql("Rename lic_services to lic_services_pre913",
			"RENAME TABLE `lic_services` to `lic_services_pre913`");
	}

	execute_sql("Rename lic_flexlm_servers to lic_services",
		"RENAME TABLE `lic_flexlm_servers` to `lic_services`");

	$column_arr= array(
		'poller_id' => "ADD COLUMN `poller_id` int(10) unsigned NOT NULL AFTER `poller_date`",
		'disabled' => "ADD COLUMN `disabled` varchar(2) NOT NULL default '' AFTER `timeout`",
		'options_path' => "ADD COLUMN `options_path` varchar(2048) NOT NULL DEFAULT '' AFTER `disabled`",
	);

	$status = add_columns("lic_services", $column_arr);

	if (!db_column_exists('lic_services', 'service_id')) {
		execute_sql("Modify table lic_services",
			"ALTER TABLE `lic_services`
			CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL auto_increment");
	}

	execute_sql("load poller_id and disabled from old table lic_servers",
		"UPDATE lic_services
		INNER JOIN `lic_servers`
		ON lic_servers.id = lic_services.service_id
		SET lic_services.poller_id = lic_servers.lic_poller_id, lic_services.disabled= lic_servers.disabled");

	//2. lic_servers
	//   NOTE: for the case that uninstalled license plugin, then do the upgrade.
	if (db_table_exists('lic_servers')) {
		execute_sql('Rename lic_servers to lic_servers_pre913',
			"RENAME TABLE `lic_servers` to `lic_servers_pre913`");
	}

	execute_sql('drop old lic_servers',
		"DROP TABLE IF EXISTS `lic_servers`");

	execute_sql('rename lic_flexlm_quorum_servers to lic_servers',
		"RENAME TABLE `lic_flexlm_quorum_servers` to `lic_servers`");

	execute_sql('Modify table lic_servers',
		"ALTER TABLE `lic_servers`
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0'");

	//3. lic_pollers
	add_column('lic_pollers', 'client_path',
		"ADD COLUMN `client_path` varchar(100) NOT NULL AFTER `poller_path`");

	$rtm_top = grid_get_path_rtm_top();

	execute_sql("Insert local RLM license poller entries",
		"INSERT INTO `lic_pollers`
		(poller_path, client_path, poller_description, poller_hostname, poller_type)
		VALUES (
			'$rtm_top/rtm/lic/bin',
			'$rtm_top/rlm/bin/rlmstat',
			'RLM poller',
			'local',
			3
		)");

	execute_sql("Update local FLEXlm license poller entries",
		"UPDATE lic_pollers
		SET client_path = '$rtm_top/flexlm/bin/lmstat'
		WHERE poller_type = 1");

	//4. lic_services_feature_details
	//   NOTE: for the case that uninstalled license plugin, then do the upgrade.
	if (db_table_exists('lic_services_feature_details')) {
		execute_sql("Rename lic_services_feature_details to lic_services_feature_details_pre913",
			"RENAME TABLE `lic_services_feature_details` to `lic_services_feature_details_pre913`");
	}

	execute_sql("rename lic_flexlm_servers_feature_details to lic_services_feature_details",
		"RENAME TABLE `lic_flexlm_servers_feature_details` to `lic_services_feature_details`");

	execute_sql("Modify table lic_services_feature_details",
		"ALTER TABLE `lic_services_feature_details`
		DROP COLUMN `poller_id`,
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0',
		MODIFY COLUMN `feature_name` varchar(50) NOT NULL default '0',
		MODIFY COLUMN `restype` int(10) unsigned NOT NULL default '0'");

	//5. lic_services_feature
	//   NOTE: for the case that uninstalled license plugin, then do the upgrade.
	if (db_table_exists('lic_services_feature')) {
		execute_sql("Rename lic_services_feature to lic_services_feature_pre913",
			"RENAME TABLE `lic_services_feature` to `lic_services_feature_pre913`");
	}

	execute_sql("rename lic_flexlm_servers_feature_expirations to lic_services_feature",
		"RENAME TABLE `lic_flexlm_servers_feature_expirations` to `lic_services_feature`");

	execute_sql("Modify table lic_services_feature",
		"ALTER TABLE `lic_services_feature`
		DROP COLUMN `poller_id`,
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0',
		ADD COLUMN `total_reserved_token` int(10) unsigned NOT NULL default '0' AFTER `feature_number_to_expire`");

	//6. lic_flexlm_servers_feature_use
	//   NOTE: for the case that uninstalled license plugin, then do the upgrade.
	if (db_table_exists('lic_services_feature_use')) {
		execute_sql("Rename lic_services_feature_use to lic_services_feature_use_pre913",
			"RENAME TABLE `lic_services_feature_use` to `lic_services_feature_use_pre913`");
	}

	execute_sql("rename lic_flexlm_servers_feature_use to lic_services_feature_use",
		"RENAME TABLE `lic_flexlm_servers_feature_use` to `lic_services_feature_use`");

	execute_sql("Modify table lic_services_feature_use",
		"ALTER TABLE `lic_services_feature_use`
		DROP COLUMN `poller_id`,
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0'");

	// 7. lic_interval_stats
	execute_sql("Modify table lic_interval_stats",
		"ALTER TABLE `lic_interval_stats`
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0',
		MODIFY COLUMN `feature` varchar(50) NOT NULL default ''");

	add_index('lic_interval_stats', 'interval_end_lic_count',
		"ADD INDEX `interval_end_lic_count`(`interval_end`,`total_license_count`)");

	// 8. lic_daily_stats
	execute_sql('Modify table lic_daily_stats',
		"ALTER TABLE `lic_daily_stats`
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0',
		MODIFY COLUMN `feature` varchar(50) NOT NULL default ''");

	// 9. lic_daily_stats_traffic
	execute_sql('Modify table lic_daily_stats_traffic',
		"ALTER TABLE `lic_daily_stats_traffic`
		CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL,
		MODIFY COLUMN `feature` varchar(50) NOT NULL");

	//10. view lic_flexlm_servers_feature_details
	execute_sql ('drop view if exists lic_flexlm_servers_feature_details', "DROP VIEW IF EXISTS `lic_flexlm_servers_feature_details`");

	execute_sql ('create view lic_flexlm_servers_feature_details',
		"CREATE VIEW lic_flexlm_servers_feature_details AS
		SELECT ls.poller_id, lsfd.service_id AS portatserver_id, lsfd.vendor_daemon, lsfd.feature_name, lsfd.subfeature,
		lsfd.feature_version, lsfd.username, lsfd.groupname,lsfd.hostname, lsfd.chkoutid, lsfd.restype,
		lsfd.status, lsfd.tokens_acquired, lsfd.tokens_acquired_date, lsfd.last_updated, lsfd.present
		FROM lic_services_feature_details AS lsfd
		JOIN lic_services AS ls
		ON lsfd.service_id = ls.service_id");

	//11. view lic_flexlm_servers_feature_use
	execute_sql ('drop view if exists lic_flexlm_servers_feature_use',
		"DROP VIEW IF EXISTS `lic_flexlm_servers_feature_use`");

	execute_sql ('create view lic_flexlm_servers_feature_use',
		"CREATE VIEW lic_flexlm_servers_feature_use AS
		SELECT ls.poller_id, lsfu.service_id AS portatserver_id, lsfu.feature_name, lsfu.feature_max_licenses,
		lsfu.feature_inuse_licenses, lsfu.feature_queued, lsfu.feature_reserved, lsfu.vendor_daemon,
		lsfu.present, lsfu.vendor_status, lsfu.vendor_version, lsfu.status
		FROM lic_services_feature_use AS lsfu
		INNER JOIN lic_services AS ls
		ON lsfu.service_id = ls.service_id");

	//12. drop old lumn tables;
	execute_sql('drop lic_lum_clusters',
		"DROP TABLE IF EXISTS `lic_lum_clusters`");

	execute_sql("drop lic_lum_events",
		"DROP TABLE IF EXISTS `lic_lum_events`");

	execute_sql("drop lic_lum_licenses",
		"DROP TABLE IF EXISTS `lic_lum_licenses`");

	execute_sql("drop lic_lum_licenses_usage",
		"DROP TABLE IF EXISTS `lic_lum_licenses_usage`");

	execute_sql("drop lic_lum_users",
		"DROP TABLE IF EXISTS `lic_lum_users`");

	//13. create options file tables
	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_feature`");

	create_table("lic_services_options_feature", "CREATE TABLE IF NOT EXISTS `lic_services_options_feature` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`keyword` varchar(60) NOT NULL,
		`borrow_lowwater` int(10) unsigned DEFAULT NULL,
		`linger` int(10) unsigned DEFAULT NULL,
		`max_borrow_hours` int(10) unsigned DEFAULT NULL,
		`max_overdraft` int(10) unsigned DEFAULT NULL,
		`timeout` int(10) unsigned DEFAULT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`feature`,`keyword`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Includes Feature Options'");

	if (db_table_exists('lic_flexlm_servers_options_feature')) {
		execute_sql("load data into new table lic_services_options_feature",
			"INSERT INTO lic_services_options_feature
			SELECT *
			FROM lic_flexlm_servers_options_feature");

		execute_sql("drop old table lic_flexlm_servers_options_feature",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_feature");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_feature_type`");

	create_table("lic_services_options_feature_type",
		"CREATE TABLE `lic_services_options_feature_type` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`keyword` varchar(60) NOT NULL,
		`variable` varchar(20) NOT NULL DEFAULT '',
		`otype` varchar(20) NOT NULL DEFAULT '',
		`name` varchar(40) NOT NULL DEFAULT '',
		`notes` varchar(255) DEFAULT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`feature`,`variable`,`otype`,`name`,`keyword`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Per Feature/Type Options'");

	$opt_table = db_fetch_assoc("SHOW TABLES LIKE 'lic_flexlm_servers_options_feature_type'");
	if (cacti_sizeof($opt_table)) {
		execute_sql("load data into new table lic_services_options_feature_type",
			"INSERT INTO lic_services_options_feature_type
			SELECT *
			FROM lic_flexlm_servers_options_feature_type");

		execute_sql("drop old table lic_flexlm_servers_options_feature_type",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_feature_type");
	}

	execute_sql("drop","DROP TABLE IF EXISTS `lic_services_options_incexcl_all`");

	create_table("lic_services_options_incexcl_all",
		"CREATE TABLE `lic_services_options_incexcl_all` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`incexcl` varchar(12) NOT NULL DEFAULT '',
		`otype` varchar(20) NOT NULL DEFAULT '',
		`name` varchar(40) NOT NULL DEFAULT '',
		`notes` varchar(255) DEFAULT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`incexcl`,`otype`,`name`),
		KEY `service_id` (`service_id`),
		KEY `incexcl` (`incexcl`),
		KEY `otype` (`otype`),
		KEY `name` (`name`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	if (db_table_exists('lic_flexlm_servers_options_incexcl_all')) {
		execute_sql("load data into new table lic_services_options_incexcl_all",
			"INSERT INTO lic_services_options_incexcl_all
			SELECT *
			FROM lic_flexlm_servers_options_incexcl_all");

		execute_sql("drop old table lic_flexlm_servers_options_incexcl_all",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_incexcl_all");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_global`");

	create_table("lic_services_options_global",
		"CREATE TABLE IF NOT EXISTS `lic_services_options_global` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`options_path` varchar(255) NOT NULL DEFAULT '',
		`debug_path` varchar(255) DEFAULT NULL,
		`report_path` varchar(255) DEFAULT NULL,
		`nolog_in` int(10) unsigned DEFAULT NULL,
		`nolog_out` int(10) unsigned DEFAULT NULL,
		`nolog_denied` int(10) unsigned DEFAULT NULL,
		`nolog_queued` int(10) unsigned DEFAULT NULL,
		`timeoutall` int(10) unsigned DEFAULT NULL,
		`groupcaseinsens` int(10) unsigned DEFAULT NULL,
		`present` tinyint(4) DEFAULT '1',
		PRIMARY KEY (`service_id`,`options_path`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Contains Per Options File Global Settings'");

	if (db_table_exists('lic_flexlm_servers_options_global')) {
		execute_sql("load data into new table lic_services_options_global",
			"INSERT INTO lic_services_options_global
			SELECT *
			FROM lic_flexlm_servers_options_global");

		execute_sql("drop old table lic_flexlm_servers_options_global",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_global");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_host_groups`");

	create_table("lic_services_options_host_groups", "CREATE TABLE IF NOT EXISTS `lic_services_options_host_groups` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`group` varchar(64) NOT NULL DEFAULT '',
		`host` varchar(64) NOT NULL DEFAULT '',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`group`,`host`),
		KEY `service_id` (`service_id`),
		KEY `group` (`group`),
		KEY `host` (`host`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Shows Host Group in FLEXlm'");

	if (db_table_exists('lic_flexlm_servers_options_host_groups')) {
		execute_sql("load data into new table lic_services_options_host_groups",
			"INSERT INTO lic_services_options_host_groups
			SELECT *
			FROM lic_flexlm_servers_options_host_groups");

		execute_sql("drop old table lic_flexlm_servers_options_host_groups",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_host_groups");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_max`");

	create_table("lic_services_options_max", "CREATE TABLE `lic_services_options_max` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`num_lic` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`keyword` varchar(60) NOT NULL,
		`otype` varchar(10) NOT NULL DEFAULT '',
		`name` varchar(40) NOT NULL DEFAULT '',
		`notes` varchar(255) DEFAULT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`feature`,`otype`,`name`,`keyword`),
		KEY `service_id` (`service_id`),
		KEY `feature` (`feature`),
		KEY `name` (`name`),
		KEY `otype` (`otype`),
		KEY `otype_name` (`otype`,`name`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	if (db_table_exists('lic_flexlm_servers_options_max')) {
		execute_sql("load data into new table lic_services_options_max",
			"INSERT INTO lic_services_options_max
			SELECT *
			FROM lic_flexlm_servers_options_max");

		execute_sql("drop old table lic_flexlm_servers_options_max",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_max");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_reserve`");

	create_table("lic_services_options_reserve",
		"CREATE TABLE IF NOT EXISTS `lic_services_options_reserve` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`num_lic` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(50) NOT NULL DEFAULT '',
		`keyword` varchar(60) NOT NULL,
		`otype` varchar(10) NOT NULL DEFAULT '',
		`name` varchar(40) NOT NULL DEFAULT '',
		`notes` varchar(255) DEFAULT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`feature`,`otype`,`name`,`keyword`),
		KEY `service_id` (`service_id`),
		KEY `feature` (`feature`),
		KEY `name` (`name`),
		KEY `otype` (`otype`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	if (db_table_exists('lic_flexlm_servers_options_reserve')) {
		execute_sql("load data into new table lic_services_options_reserve",
			"INSERT INTO lic_services_options_reserve
			SELECT *
			FROM lic_flexlm_servers_options_reserve");

		execute_sql("drop old table lic_flexlm_servers_options_reserve",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_reserve");
	}

	execute_sql("drop",
		"DROP TABLE IF EXISTS `lic_services_options_user_groups`");

	create_table("lic_services_options_user_groups",
		"CREATE TABLE IF NOT EXISTS `lic_services_options_user_groups` (
		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
		`group` varchar(64) NOT NULL DEFAULT '',
		`user` varchar(40) NOT NULL DEFAULT '',
		`present` tinyint(3) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`service_id`,`group`,`user`),
		KEY `service_id` (`service_id`),
		KEY `group` (`group`),
		KEY `user` (`user`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	if (db_table_exists('lic_flexlm_servers_options_user_groups')) {
		execute_sql("load data into new table lic_services_options_user_groups",
			"INSERT INTO lic_services_options_user_groups
			SELECT *
			FROM lic_flexlm_servers_options_user_groups");

		execute_sql("drop old table lic_flexlm_servers_options_user_groups",
			"DROP TABLE IF EXISTS lic_flexlm_servers_options_user_groups");
	}

	create_table("lic_ip_ranges",
		"CREATE TABLE IF NOT EXISTS `lic_ip_ranges` (
		`ip_range` varchar(16) NOT NULL,
		`hostname` varchar(64) NOT NULL,
		`ip_address` varchar(16) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`ip_range`,`hostname`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Stores IP range and host membership from DNS'");

	create_table("lic_ldap_to_flex_groups",
		"CREATE TABLE IF NOT EXISTS `lic_ldap_to_flex_groups` (
		`ldap_group` varchar(40) NOT NULL,
		`flex_group` varchar(40) NOT NULL,
		`present` tinyint(3) unsigned DEFAULT NULL,
		PRIMARY KEY (`ldap_group`,`flex_group`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='A Mapping Table of LDAP Groups to FLEXlm Group'");

	create_table("lic_ldap_groups",
		"CREATE TABLE IF NOT EXISTS `lic_ldap_groups` (
		`group` varchar(40) NOT NULL,
		`user` varchar(20) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY (`group`,`user`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Stores LDAP Group Information'");

	execute_sql("Reset the 'lic_db_upgrade' flag for license daily partition upgrade",
		"DELETE FROM settings WHERE name='lic_db_upgrade'");
}


function partition_tables_to_9_1_3() {
	return array(
		'lic_daily_stats' => array(
			'columns' => array(
				'feature' => "MODIFY COLUMN `feature` varchar(50) NOT NULL default ''",

			),
			'rename' => array(
				'columns' => array(
					'portatserver_id' => array(
						'name' => 'service_id',
						'altsql'  =>"CHANGE `portatserver_id` `service_id` int(10) unsigned NOT NULL default '0'"
					)
				)
			)
		)
	);
}
