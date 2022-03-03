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

function upgrade_to_10_1() {
    global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../../grid/include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../lib/import.php');

	cacti_log('NOTE: Upgrading disku to v10.1.0.0 ...', true, 'UPGRADE');

	//disku plugin tables
	create_table("disku_processes", "CREATE TABLE IF NOT EXISTS `disku_processes` (
		`pid` int(10) unsigned NOT NULL default '0',
		`taskname` varchar(20) NOT NULL default '0',
		`taskid` int(10) unsigned NOT NULL default '0',
		`heartbeat` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`taskname`,`taskid`)
		) ENGINE=MEMORY
		COMMENT='Stored Client Processes in Disku Plugin'");

	create_table("disku_applications", "CREATE TABLE IF NOT EXISTS `disku_applications` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`application` varchar(40) NOT NULL default '',
		`vendor` varchar(40) NOT NULL default '',
		PRIMARY KEY  (`id`),
		KEY `application` (`application`),
		KEY `vendor` (`vendor`))
		ENGINE=MyISAM
		COMMENT='Stored Applications Used in Disk Monitoring'");

	create_table("disku_directory_totals", "CREATE TABLE IF NOT EXISTS `disku_directory_totals` (
		`poller_id` int(10) unsigned NOT NULL,
		`path_id` int(10) unsigned NOT NULL,
		`dirName` varchar(512) NOT NULL,
		`group` varchar(40) NOT NULL,
		`groupid` int(10) unsigned NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY  (`poller_id`,`path_id`,`dirName`(191),`groupid`),
		KEY `delme` (`delme`))
		ENGINE=MyISAM
		COMMENT='Totals Directory Use by Group'");

	create_table("disku_directory_totals_history", "CREATE TABLE IF NOT EXISTS `disku_directory_totals_history` (
		`poller_id` int(10) unsigned NOT NULL auto_increment,
		`path_id` int(10) unsigned NOT NULL,
		`dirName` varchar(512) NOT NULL,
		`group` varchar(40) NOT NULL,
		`groupid` int(10) unsigned NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`intervalEnd` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`poller_id`,`path_id`,`dirName`(191),`groupid`,`intervalEnd`))
		ENGINE=MyISAM
		COMMENT='Totals Directory Use by Group'");

	create_table("disku_extension_registry", "CREATE TABLE IF NOT EXISTS `disku_extension_registry` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`extension` varchar(20) NOT NULL default '',
		`notes` varchar(255) default '',
		`monitor` char(3) NOT NULL default 'on',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `ext_contraint` (`extension`),
		KEY `ext` (`extension`))
		ENGINE=MyISAM
		COMMENT='Registers File Extensions of Interest by Customer'");

	create_table("disku_extension_monitors", "CREATE TABLE IF NOT EXISTS `disku_extension_monitors` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`rid` int(10) unsigned NOT NULL default '0',
		`extension` varchar(20) NOT NULL default '',
		`application_id` int(10) unsigned NOT NULL default '0',
		`notes` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `app_ext_contraint` (`application_id`,`extension`),
		KEY `ext` (`extension`),
		KEY `app` (`application_id`),
		KEY `rid` (`rid`))
		ENGINE=MyISAM
		COMMENT='Holds the List of Applications Extensions to Monitor/Graph'");

	create_table("disku_extension_totals_simple", "CREATE TABLE IF NOT EXISTS `disku_extension_totals_simple` (
		`extension` varchar(20) NOT NULL default '',
		`userid` int(10) unsigned NOT NULL default '0',
		`files` bigint(20) unsigned NOT NULL default '0',
		`size` double NOT NULL default '0',
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY (`extension`),
		KEY `delme` (`delme`))
		ENGINE=MyISAM
		COMMENT='Disk utilization information by extension'");

	create_table("disku_extension_totals", "CREATE TABLE IF NOT EXISTS `disku_extension_totals` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`extension` varchar(20) NOT NULL default '',
		`userid` int(10) unsigned NOT NULL default '0',
		`files` bigint(20) unsigned NOT NULL default '0',
		`size` double NOT NULL default '0',
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY USING BTREE (`extension`,`userid`,`path_id`,`poller_id`),
		KEY `delme` (`delme`),
		KEY `userid` (`userid`))
		ENGINE=MyISAM
		COMMENT='Holds disk utilization information by extension'");

	create_table("disku_extension_totals_history", "CREATE TABLE IF NOT EXISTS `disku_extension_totals_history` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`extension` varchar(20) NOT NULL default '',
		`userid` int(10) unsigned NOT NULL default '0',
		`files` bigint(20) unsigned NOT NULL default '0',
		`size` double NOT NULL default '0',
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`intervalEnd` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY USING BTREE (`extension`,`userid`,`poller_id`,`path_id`),
		KEY `intervalEnd` (`intervalEnd`))
		ENGINE=MyISAM
		COMMENT='Holds disk utilization information history by extension'");

	create_table("disku_files_raw", "CREATE TABLE IF NOT EXISTS `disku_files_raw` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`thread_id` int(10) unsigned NOT NULL default '0',
		`device` int(10) unsigned NOT NULL default '0',
		`inode` bigint(20) unsigned NOT NULL default '0',
		`fileType` char(2) default NULL,
		`dirName` varchar(4096) default '',
		`fileName` varchar(256) default '',
		`fileSize` bigint(20) unsigned NOT NULL default '0',
		`accTime` timestamp NOT NULL default '0000-00-00 00:00:00',
		`statTime` timestamp NOT NULL default '0000-00-00 00:00:00',
		`modTime` timestamp NOT NULL default '0000-00-00 00:00:00',
		`user` varchar(20) NOT NULL default '',
		`userid` int(10) unsigned NOT NULL default '0',
		`group` varchar(40) NOT NULL default '',
		`groupid` int(10) unsigned NOT NULL default '0',
		`perms` varchar(12) NOT NULL,
		`extension` varchar(20) NOT NULL default '',
		`lastUpdated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`poller_id`,`path_id`,`device`,`inode`),
		KEY `groupid` (`groupid`),
		KEY `userid` (`userid`),
		KEY `dirName` (`dirName`(191)),
		KEY `fileType` (`fileType`),
		KEY `ext` (`extension`),
		KEY `lastUpdated` (`lastUpdated`))
		ENGINE=MyISAM
		COMMENT='File System Information'");

	create_table("disku_groups", "CREATE TABLE IF NOT EXISTS `disku_groups` (
		`domain` varchar(20) NOT NULL default '',
		`name` varchar(64) NOT NULL default '',
		`groupid` int(10) unsigned NOT NULL default '0',
		`firstSeen` timestamp NOT NULL default '0000-00-00 00:00:00',
		`lastSeen` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`domain`,`name`,`groupid`))
		ENGINE=MyISAM
		COMMENT='Holds registry of all know Groups'");

	create_table("disku_groups_members", "CREATE TABLE IF NOT EXISTS `disku_groups_members` (
		`groupid` int(10) unsigned NOT NULL default '0',
		`userid` int(10) NOT NULL default '-1',
		`user` varchar(20) NOT NULL default '',
		PRIMARY KEY  (`groupid`,`user`),
		KEY `userid` (`userid`))
		ENGINE=MyISAM
		COMMENT='Holds User Group Mappings'");

	create_table("disku_groups_totals", "CREATE TABLE IF NOT EXISTS `disku_groups_totals` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`groupid` int(10) unsigned NOT NULL default '0',
		`group` varchar(20) NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`directories` bigint(20) unsigned NOT NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY  USING BTREE (`poller_id`,`path_id`,`groupid`),
		KEY `delme` (`delme`),
		KEY `group` (`group`))
		ENGINE=MyISAM
		COMMENT='This Table will Track Disk Utilization by Group'");

	create_table("disku_groups_totals_history", "CREATE TABLE IF NOT EXISTS `disku_groups_totals_history` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`groupid` int(10) unsigned NOT NULL default '0',
		`group` varchar(20) NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`intervalEnd` timestamp NOT NULL default '0000-00-00 00:00:00',
		`directories` bigint(20) unsigned NOT NULL,
		PRIMARY KEY  USING BTREE (`poller_id`,`path_id`,`groupid`),
		KEY `group` (`group`),
		KEY `intervalEnd` (`intervalEnd`))
		ENGINE=MyISAM
		COMMENT='This Table will Track Disk Utilization by Group'");

	create_table("disku_managers", "CREATE TABLE IF NOT EXISTS `disku_managers` (
		`user` varchar(20) NOT NULL,
		`createDate` timestamp NOT NULL default '0000-00-00 00:00:00',
		`createdBy` varchar(20) NOT NULL,
		`lastChange` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`user`))
		ENGINE=MyISAM
		COMMENT='Identified Personnel that have certain administrative rights'");

	create_table("disku_managers_group", "CREATE TABLE IF NOT EXISTS `disku_managers_group` (
		`groupid` int(10) unsigned NOT NULL,
		`user` varchar(20) NOT NULL,
		`hasQuota` tinyint(3) unsigned NOT NULL,
		`hasDelete` tinyint(3) unsigned NOT NULL,
		`hasMove` tinyint(3) unsigned NOT NULL,
		`hasCopy` tinyint(3) unsigned NOT NULL,
		`hasAuthorize` tinyint(3) unsigned NOT NULL,
		`createDate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		`createdBy` varchar(20) NOT NULL,
		`lastChange` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`groupid`,`user`))
		ENGINE=MyISAM
		COMMENT='disku_group_managers'");

	create_table("disku_pollers", "CREATE TABLE IF NOT EXISTS `disku_pollers` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`hostname` varchar(64) NOT NULL default '',
		`location` varchar(40) default NULL,
		`disabled` char(3) default '',
		`last_started` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_ended` timestamp NOT NULL default '0000-00-00 00:00:00',
		`frequency` int(10) unsigned default '0',
		`dayOfWeek` int(10) unsigned default '6',
		`timeOfDay` int(10) unsigned default '0',
		`min_time` double default '9999999999',
		`max_time` double default '0',
		`cur_time` double default '0',
		`avg_time` double default '0',
		`total_polls` int(10) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`))
		ENGINE=MyISAM
		COMMENT='Defines collection frequencies for this poller'");

	create_table("disku_pollers_filesystems", "CREATE TABLE IF NOT EXISTS `disku_pollers_filesystems` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`device` varchar(255) NOT NULL default '',
		`blocks` bigint(20) unsigned NOT NULL default '0',
		`used` bigint(20) unsigned NOT NULL default '0',
		`available` bigint(20) unsigned NOT NULL default '0',
		`percentUsed` int(10) unsigned NOT NULL default '0',
		`mountPoint` varchar(128) NOT NULL default '',
		`present` tinyint(4) NOT NULL default '1',
		PRIMARY KEY  (`poller_id`,`mountPoint`))
		ENGINE=MyISAM
		COMMENT='Holds all known files systems per poller'");

	create_table("disku_pollers_paths", "CREATE TABLE IF NOT EXISTS `disku_pollers_paths` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`poller_id` int(10) unsigned NOT NULL default '0',
		`name` varchar(40) NOT NULL default '',
		`description` varchar(128) NOT NULL default '',
		`tagname` varchar(20) NOT NULL default '',
		`path` varchar(128) default '',
		`disabled` char(3) default '',
		`depth` int(10) unsigned NOT NULL default '2',
		`threads` int(10) unsigned NOT NULL default '5',
		`last_started` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_ended` timestamp NOT NULL default '0000-00-00 00:00:00',
		`min_time` double default '9999999999',
		`max_time` double default '0',
		`cur_time` double default '0',
		`avg_time` double default '0',
		`total_polls` int(10) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `pollerid_path_contraint` (`poller_id`,`path`),
		KEY `poller_id` (`poller_id`))
		ENGINE=MyISAM
		COMMENT='Defined paths to be scanned'");

	create_table("disku_pollers_threads", "CREATE TABLE IF NOT EXISTS `disku_pollers_threads` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`pid` int(10) unsigned NOT NULL default '0',
		`status` varchar(20) default '',
		`depth` int(11) NOT NULL default '0',
		`dir` varchar(255) NOT NULL default '',
		`start_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`))
		ENGINE=MEMORY
		COMMENT='Stores temporary running process information'");

	create_table("disku_users", "CREATE TABLE IF NOT EXISTS `disku_users` (
		`domain` varchar(20) NOT NULL default '',
		`user` varchar(20) NOT NULL default '',
		`userid` int(10) unsigned NOT NULL default '0',
		`groupid` int(10) NOT NULL default '-1',
		`name` varchar(60) NOT NULL default '',
		`path` varchar(40) NOT NULL default '',
		`shell` varchar(20) NOT NULL default '',
		`firstSeen` timestamp NOT NULL default '0000-00-00 00:00:00',
		`lastSeen` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`domain`,`user`,`userid`))
		ENGINE=MyISAM
		COMMENT='Stores all users in all known domains'");

	create_table("disku_users_totals", "CREATE TABLE IF NOT EXISTS `disku_users_totals` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`userid` int(10) unsigned NOT NULL default '0',
		`user` varchar(20) NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`directories` bigint(20) unsigned NOT NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY  USING BTREE (`poller_id`,`path_id`,`userid`),
		KEY `delme` (`delme`),
		KEY `user` (`user`))
		ENGINE=MyISAM
		COMMENT='This Table will Track Disk Utilization by User'");

	create_table("disku_users_totals_history", "CREATE TABLE IF NOT EXISTS `disku_users_totals_history` (
		`poller_id` int(10) unsigned NOT NULL default '0',
		`path_id` int(10) unsigned NOT NULL default '0',
		`userid` int(10) unsigned NOT NULL default '0',
		`user` varchar(20) NOT NULL,
		`files` bigint(20) unsigned NOT NULL,
		`size` double NOT NULL,
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`intervalEnd` timestamp NOT NULL default '0000-00-00 00:00:00',
		`directories` bigint(20) unsigned NOT NULL,
		PRIMARY KEY USING BTREE (`poller_id`,`path_id`,`userid`),
		KEY `user` (`user`),
		KEY `intervalEnd` (`intervalEnd`))
		ENGINE=MyISAM
		COMMENT='This Table will Track Disk Utilization History by User'");

	cacti_log('Importing Disku device templates...', true, 'UPGRADE');
	$disku_templates = array(
		"1" => array (
			'value' => 'Disku - Host Template',
			'name' => 'cacti_host_template_disk_monitoring_host.xml'
		),
	);
	foreach($disku_templates as $disku_template) {
		cacti_log(' - Importing ' . $disku_template['value'] . '.', true, 'UPGRADE');;
		$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_1/" . $disku_template['name']);
	}
	cacti_log('Disku templates import complete.', true, 'UPGRADE');;

	db_execute("REPLACE INTO settings set name='disku_device_add', value='on'");
	db_execute("REPLACE INTO settings set name='disku_bypass_directories', value='.snapshot'");
	db_execute("REPLACE INTO settings set name='disku_level1', value='meta_col4'");
	db_execute("REPLACE INTO settings set name='disku_level2', value='meta_col5'");
	db_execute("REPLACE INTO settings set name='disku_level3', value='meta_col6'");

	execute_sql("Add user auth realm of View Disk Admin Data for Admin", "REPLACE INTO `user_auth_realm` VALUES (8900,1);");
	execute_sql("Add user auth realm of View Disk Usage Data for Admin", "REPLACE INTO `user_auth_realm` VALUES (8901,1);");
}
