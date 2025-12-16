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

function plugin_disku_install() {
	global $config;

	api_plugin_register_hook('disku', 'top_header_tabs', 'disku_show_tab', 'setup.php');
	api_plugin_register_hook('disku', 'top_graph_header_tabs', 'disku_show_tab', 'setup.php');
	api_plugin_register_hook('disku', 'config_arrays', 'disku_config_arrays', 'setup.php');
	api_plugin_register_hook('disku', 'config_settings', 'disku_config_settings', 'setup.php');
	api_plugin_register_hook('disku', 'config_form', 'disku_config_form', 'setup.php');
	api_plugin_register_hook('disku', 'api_device_save', 'disku_api_device_save', 'setup.php');
	api_plugin_register_hook('disku', 'draw_navigation_text', 'disku_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('disku', 'poller_bottom', 'disku_poller_bottom', 'setup.php');
	api_plugin_register_hook('disku', 'valid_host_fields', 'disku_valid_host_fields', 'setup.php');
	api_plugin_register_hook('disku', 'grid_tab_down', 'disku_grid_tab_down', 'setup.php');
	api_plugin_register_hook('disku', 'grid_menu', 'disku_grid_menu', 'setup.php');

	api_plugin_register_realm('disku', 'disku_orgview.php,disku_users.php,disku_groups.php,disku_managers.php,disku_extensions.php,disku_appview.php,disku_tagview.php', 'View Disk Admin Data', 1);
	api_plugin_register_realm('disku', 'disku_dashboard.php', 'View Disk Usage Data', 1);
	api_plugin_register_realm('disku', 'disku_pollers.php,disku_paths.php,disku_extenreg.php,disku_applications.php', 'Disk Usage Administration', 1);

	disku_setup_new_tables();
}

function disku_setup_new_tables() {
	db_execute("CREATE TABLE IF NOT EXISTS `disku_processes` (
		`pid` int(10) unsigned NOT NULL default '0',
		`taskname` varchar(20) NOT NULL default '0',
		`taskid` int(10) unsigned NOT NULL default '0',
		`heartbeat` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (`taskname`,`taskid`)
		) ENGINE=MEMORY
		COMMENT='Stored Client Processes in Disku Plugin' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_applications` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`application` varchar(40) NOT NULL default '',
		`vendor` varchar(40) NOT NULL default '',
		PRIMARY KEY  (`id`),
		KEY `application` (`application`),
		KEY `vendor` (`vendor`))
		ENGINE=MyISAM
		COMMENT='Stored Applications Used in Disk Monitoring' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_directory_totals` (
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
		COMMENT='Totals Directory Use by Group' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_directory_totals_history` (
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
		COMMENT='Totals Directory Use by Group' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_registry` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`extension` varchar(20) NOT NULL default '',
		`notes` varchar(255) default '',
		`monitor` char(3) NOT NULL default 'on',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `ext_contraint` (`extension`))
		ENGINE=MyISAM
		COMMENT='Registers File Extensions of Interest by Customer' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_monitors` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`rid` int(10) unsigned NOT NULL default '0',
		`extension` varchar(20) NOT NULL default '',
		`application_id` int(10) unsigned NOT NULL default '0',
		`notes` varchar(255) NOT NULL default '',
		PRIMARY KEY  (`id`),
		UNIQUE KEY `app_ext_contraint` (`application_id`,`extension`),
		KEY `ext` (`extension`),
		KEY `rid` (`rid`))
		ENGINE=MyISAM
		COMMENT='Holds the List of Applications Extensions to Monitor/Graph' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_totals_simple` (
		`extension` varchar(20) NOT NULL default '',
		`users` int(10) unsigned NOT NULL default '0',
		`files` bigint(20) unsigned NOT NULL default '0',
		`size` double NOT NULL default '0',
		`size0to6` double default NULL,
		`size6to12` double default NULL,
		`size12plus` double default NULL,
		`delme` tinyint(3) unsigned default '0',
		PRIMARY KEY (`extension`),
		KEY `delme` (`delme`))
		ENGINE=MyISAM
		COMMENT='Disk utilization information by extension' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_totals` (
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
		COMMENT='Holds disk utilization information by extension' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_totals_history` (
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
		COMMENT='Holds disk utilization information history by extension' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_files_raw` (
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
		COMMENT='File System Information' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_groups` (
		`domain` varchar(20) NOT NULL default '',
		`name` varchar(64) NOT NULL default '',
		`groupid` int(10) unsigned NOT NULL default '0',
		`firstSeen` timestamp NOT NULL default '0000-00-00 00:00:00',
		`lastSeen` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`domain`,`name`,`groupid`))
		ENGINE=MyISAM
		COMMENT='Holds registry of all know Groups' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_groups_members` (
		`groupid` int(10) unsigned NOT NULL default '0',
		`userid` int(10) NOT NULL default '-1',
		`user` varchar(20) NOT NULL default '',
		PRIMARY KEY  (`groupid`,`user`),
		KEY `userid` (`userid`))
		ENGINE=MyISAM
		COMMENT='Holds User Group Mappings' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_groups_totals` (
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
		COMMENT='This Table will Track Disk Utilization by Group' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_groups_totals_history` (
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
		COMMENT='This Table will Track Disk Utilization by Group' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_managers` (
		`user` varchar(20) NOT NULL,
		`createDate` timestamp NOT NULL default '0000-00-00 00:00:00',
		`createdBy` varchar(20) NOT NULL,
		`lastChange` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`user`))
		ENGINE=MyISAM
		COMMENT='Identified Personnel that have certain administrative rights' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_managers_group` (
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
		COMMENT='disku_group_managers' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_pollers` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`hostname` varchar(64) NOT NULL default '',
		`location` varchar(40) default NULL,
		`disabled` char(3) default '',
		`df_collect_flag` char(3) DEFAULT '',
		`cacti_host` int(10) unsigned NOT NULL DEFAULT '0',
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
		COMMENT='Defines collection frequencies for this poller' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_pollers_filesystems` (
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
		COMMENT='Holds all known files systems per poller' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_pollers_paths` (
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
		UNIQUE KEY `pollerid_path_contraint` (`poller_id`,`path`))
		ENGINE=MyISAM
		COMMENT='Defined paths to be scanned' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_pollers_threads` (
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
		COMMENT='Stores temporary running process information' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_users` (
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
		COMMENT='Stores all users in all known domains' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_users_totals` (
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
		COMMENT='This Table will Track Disk Utilization by User' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `disku_users_totals_history` (
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
		COMMENT='This Table will Track Disk Utilization History by User' DEFAULT CHARSET=latin1");
	db_execute("INSERT IGNORE INTO `settings` (name, value) VALUES ('disku_bypass_directories', '.snapshot')");
}

function plugin_disku_uninstall() {
}

function plugin_disku_check_config () {
	/* Here we will check to ensure everything is configured */
	disku_check_upgrade();
	return true;
}

function plugin_disku_upgrade () {
	/* Here we will upgrade to the newest version */
	disku_check_upgrade();
	return false;
}

function disku_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_disku_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='disku'");
	if (cacti_sizeof($old) && $current != $old['version']) {
		/* if the plugin is installed and/or active */
		if ($old['status'] == 1 || $old['status'] == 4) {
			/* re-register the hooks */
			plugin_disku_install();

			/* perform a database upgrade */
			disku_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_disku_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='disku'");
		db_execute_prepared("UPDATE plugin_config
			SET name=?,
			author=?,
			webpage=?,
			version=?
			WHERE id=?",
			array($info["longname"], $info["author"], $info["homepage"], $info["version"], $id));
	}
}

function disku_database_upgrade() {
	global $plugins, $config;
	return true;
}

function disku_config_arrays() {
	global $disku_organizational_graph_level;
	global $menu, $menu_glyphs, $nav;
	global $user_menu;
	global $user_auth_realms, $user_auth_realm_filenames;
	global $disku_rows_selector, $disku_detail_data_retention, $disku_frequencies, $disku_weekdays;
	global $messages, $disku_timesofday;

	$menu2 = array();
	foreach($menu as $temp => $temp2) {
		$menu2[$temp] = $temp2;
		if ($temp == __('Management')) {
			$menu2[__('Disk Monitoring', 'disku')]['plugins/disku/disku_pollers.php']      = __('File System Pollers', 'disku');
			$menu2[__('Disk Monitoring', 'disku')]['plugins/disku/disku_paths.php']        = __('File System Scan Paths', 'disku');
			$menu2[__('Disk Monitoring', 'disku')]['plugins/disku/disku_applications.php'] = __('Applications', 'disku');
			$menu2[__('Disk Monitoring', 'disku')]['plugins/disku/disku_extenreg.php']     = __('Extension Registry', 'disku');
		}
	}
	$menu = $menu2;

	$menu_glyphs[__('Disk Monitoring', 'disku')] = 'far fa-hdd';

	if (!api_plugin_is_enabled('grid') && strpos(get_current_page(), 'disku_') !== false) {
		$user_menu = disku_grid_menu();
	}

	$disku_frequencies = array(
		'0' => __('Weekly at Day/Time', 'disku'),
		'1' => __('Daily at Time', 'disku')
	);

	$disku_weekdays = array(
		'0'  => __('Sunday', 'disku'),
		'1'  => __('Monday', 'disku'),
		'2'  => __('Tuesday', 'disku'),
		'3'  => __('Wednesday', 'disku'),
		'4'  => __('Thursday', 'disku'),
		'5'  => __('Friday', 'disku'),
		'6'  => __('Saturday', 'disku')
	);

	$disku_organizational_graph_level = array(
		'1' => __('Level 1', 'disku'),
		'2' => __('Level 2', 'disku'),
		'3' => __('Level 1 + Level 2', 'disku'),
		'4' => __('Level 3', 'disku'),
		'5' => __('Level 1 + Level 3', 'disku'),
		'6' => __('Level 2 + Level 3', 'disku'),
		'7' => __('Level 1 + Level 2 + Level 3', 'disku')
	);

	$disku_timesofday = array(
		'0'     => '12:00 am',
		'1800'  => '12:30 am',
		'3600'  => ' 1:00 am',
		'5400'  => ' 1:30 am',
		'7200'  => ' 2:00 am',
		'9000'  => ' 2:30 am',
		'10800' => ' 3:00 am',
		'12600' => ' 3:30 am',
		'14400' => ' 4:00 am',
		'16200' => ' 4:30 am',
		'18000' => ' 5:00 am',
		'19800' => ' 5:30 am',
		'21600' => ' 6:00 am',
		'23400' => ' 6:30 am',
		'25200' => ' 7:00 am',
		'27000' => ' 7:30 am',
		'28800' => ' 8:00 am',
		'30600' => ' 8:30 am',
		'32400' => ' 9:00 am',
		'34200' => ' 9:30 am',
		'36000' => '10:00 am',
		'37800' => '10:30 am',
		'39600' => '11:00 am',
		'41400' => '11:30 am',
		'43200' => '12:00 pm',
		'45000' => '12:30 pm',
		'46800' => ' 1:00 pm',
		'48600' => ' 1:30 pm',
		'50400' => ' 2:00 pm',
		'52200' => ' 2:30 pm',
		'54000' => ' 3:00 pm',
		'55800' => ' 3:30 pm',
		'57600' => ' 4:00 pm',
		'59400' => ' 4:30 pm',
		'61200' => ' 5:00 pm',
		'63000' => ' 5:30 pm',
		'64800' => ' 6:00 pm',
		'66600' => ' 6:30 pm',
		'68400' => ' 7:00 pm',
		'70200' => ' 7:30 pm',
		'72000' => ' 8:00 pm',
		'73800' => ' 8:30 pm',
		'75600' => ' 9:00 pm',
		'77400' => ' 9:30 pm',
		'79200' => '10:00 pm',
		'81000' => '10:30 pm',
		'82800' => '11:00 pm',
		'84600' => '11:30 pm'
	);

	$disku_rows_selector = array(
		-1   => __('Default:'.((null!=read_config_option('num_rows_table'))?read_config_option('num_rows_table'):'30'), 'disku'),
		10   => '10',
		15   => '15',
		20   => '20',
		30   => '30',
		50   => '50',
		100  => '100',
		150  => '150',
		200  => '200',
		250  => '250',
		500  => '500',
		1000 => '1000',
		9999999 => __('All', 'disku')
	);

	$disku_detail_data_retention = array(
		'2days'   => __('%d Days', 2, 'disku'),
		'5days'   => __('%d Days', 5, 'disku'),
		'1week'   => __('%d Week', 1, 'disku'),
		'2weeks'  => __('%d Weeks', 2, 'disku'),
		'3weeks'  => __('%d Weeks', 3, 'disku'),
		'1month'  => __('%d Month', 1, 'disku'),
		'2months' => __('%d Months', 2, 'disku'),
		'3months' => __('%d Months', 3, 'disku'),
		'4months' => __('%d Months', 4, 'disku'),
		'6months' => __('%d Months', 6, 'disku'),
		'1year'   => __('%d Year', 1, 'disku'),
		'2year'   => __('%d Years', 2, 'disku'),
		'3year'   => __('%d Years', 3, 'disku')
	);
	$messages[301] = array(
		'message' => __('Duplicated Extension. The Extension has existed already.', 'disku'),
		'type' => 'error');
	$messages[302] = array(
		'message' => __('Duplicated Extension. And the Extension has mapped to the same application.', 'disku'),
		'type' => 'error');
	$messages[303] = array(
		'message' => __('Duplicated Extension. The Extension will map to more than one application.', 'disku'),
		'type' => 'info');
	$messages[304] = array(
		'message' => __('A absolute path is needed.', 'disku'),
		'type' => 'error');
	$messages[305] = array(
		'message' => __('Duplicated Path. The Path of the Poller has existed already.', 'disku'),
		'type' => 'error');
}

function disku_config_settings() {
	global $disku_organizational_graph_level;
	global $tabs, $settings, $disku_weekdays, $disku_timesofday, $disku_detail_data_retention;

	/* check for an upgrade */
	plugin_disku_check_config();

	$tabs['rtmpi'] = __('RTM Plugins');

	$temp = array(
		'disku_header' => array(
			'friendly_name' => __('Disk Monitoring General Settings', 'disku'),
			'method' => 'spacer'
		),
/*		'disku_enabled' => array(
			'friendly_name' => 'Enabled Disk Monitoring',
			'description' => 'Disk monitoring is enabled globally',
			'method' => 'checkbox',
			'default' => ''
		), */
		'disku_device_add' => array(
			'friendly_name' => __('Add Disk Monitoring Device Automatically', 'disku'),
			'description' => __('RTM will add device and the related graphs for monitoring', 'disku'),
			'default' => 'on',
			'method' => 'checkbox'
		),
		'disku_bypass_directories' => array(
			'friendly_name' => __('Bypass Directories', 'disku'),
			'description' => __('The directories and files of which path content these strings will be bypassed during raw data scanning. Some directory paths can lead to misleading data, and cause the scanning process to take an excessive amount of time.  One important path that should always be ignored are one that include ".snapshot" folders.  Scanning these folders will result in not only longer scan times, but erroneous data. If multiple strings need to be bypassed, separated them by semi-colon. For example, "ex1;/ex2/" will bypass directories and files of which path contents "ex1", and will bypass directories of which path contents "/ex2/".', 'disku'),
			'method' => 'textbox',
			'size' => '60',
			'default' => '.snapshot',
			'max_length' => '128'
		),
		'disku_header1' => array(
			'friendly_name' => __('Disk Monitoring Data Retention Settings', 'disku'),
			'method' => 'spacer'
		),
		'disku_retention' => array(
			'friendly_name' => __('Historical Data Retention', 'disku'),
			'description' => __('Select the period you want to store Daily Statistics data. Default is 1 year.', 'disku'),
			'method' => 'drop_array',
			'default' => '1year',
			'array' => $disku_detail_data_retention,
		),
		'disku_rotation_frequency' => array(
			'friendly_name' => __('Rotation Frequency', 'disku'),
			'description' => __('Select the data partition frequency. For example, if your Poller runs once a day, then select Daily, if it runs once a week, then select Weekly. Default is Daily.', 'disku'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => array(0 => __('Daily', 'disku'), 1 => __('Weekly', 'disku'))
		),
		'disku_rotation_day' => array(
			'friendly_name' => __('Data Storage Rotation Schedule ', 'disku'),
			'description' => __('Only when using Weekly rotation, what day should this rotation take place. Select a day to back up data. Default is Sunday.', 'disku'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => $disku_weekdays
		),
		'disku_rotation_time' => array(
			'friendly_name' => __('Rotation Time', 'disku'),
			'description' => __('Set the time for back up rotation schedule. Please make this of day a time when no Pollers are collecting data.', 'disku'),
			'method' => 'drop_array',
			'default' => '0',
			'array' => $disku_timesofday
		),
		'disku_header2' => array(
			'friendly_name' => __('Disk Monitoring Organizational Hierarchy', 'disku'),
			'method' => 'spacer'
		),
		'disku_level1' => array(
			'friendly_name' => __('Level 1 Hierarchy', 'disku'),
			'description' => __('Set the highest level of hierarchy', 'disku'),
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => __('Undefined', 'disku'),
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'disku_level2' => array(
			'friendly_name' => __('Level 2 Hierarchy', 'disku'),
			'description' => __('Set the second level of hierarchy', 'disku'),
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => __('Undefined', 'disku'),
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'disku_level3' => array(
			'friendly_name' => __('Level 3 Hierarchy', 'disku'),
			'description' => __('Set the third level of hierarchy', 'disku'),
			'method' => 'drop_sql',
			'default' => '',
			'none_value' => __('Undefined', 'disku'),
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME AS name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY DISPLAY_NAME'
		),
		'disku_org_graph_level' => array(
			'friendly_name' => __('Organizational Graph Level', 'disku'),
			'description' => __('Create graphs on which organization level.', 'disku'),
			'method' => 'drop_array',
			'default' => '1',
			'array' => $disku_organizational_graph_level,
		),
		'disku_header3' => array(
			'friendly_name' => __('Disk Monitoring User/Group Settings', 'disku'),
			'method' => 'spacer'
		),
		'disku_group_types' => array(
			'friendly_name' => __('User/Group Collection Method', 'disku'),
			'description' => __('What method should be used to determine users and group membership', 'disku'),
			'method' => 'drop_array',
			'default' => 'ent',
			//'array' => array('ent' => 'getent', 'nis' => 'NIS Only', 'wbind' => 'WinBind Only', 'nisbind' => 'NIS/WinBind'),
			'array' => array('ent' => 'getent'),
		),
		'disku_uid_range' => array(
			'friendly_name' => __('Valid Unique Identifier (UID) User ID Range', 'disku'),
			'description' => __('Define a range to uniquely identify users. By default, it is 1000 - 100000', 'disku'),
			'method' => 'textbox',
			'size' => '20',
			'default' => '1000-100000',
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '/(^[0-9]{1,9}-[0-9]{1,9}$)/')),
			'max_length' => '20'
		),
		'disku_gid_range' => array(
			'friendly_name' => __('Valid Unique Identifier (GID) Group ID Range', 'disku'),
			'description' => __('Define a range to uniquely identify user groups. By default, it is 500 - 10000', 'disku'),
			'method' => 'textbox',
			'size' => '20',
			'default' => '500-10000',
			'filter' => FILTER_VALIDATE_REGEXP,
			'options' => array('options' => array('regexp' => '/(^[0-9]{1,9}-[0-9]{1,9}$)/')),
			'max_length' => '20'
		),
	);

	if (isset($settings['rtmpi'])) {
		$settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
	} else {
		$settings['rtmpi'] = $temp;
	}
}

function plugin_disku_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/disku/INFO', true);
	return $info['info'];
}

function get_disku_version() {
	$info = plugin_disku_version();
	if (!empty($info) && isset($info['version'])) {
		return $info['version'];
	}
	return RTM_VERSION;
}

function disku_draw_navigation_text($nav) {
	$nav['disku_pollers.php:'] = array('title' => __('Disk Pollers', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_pollers.php', 'level' => '1');
	$nav['disku_pollers.php:edit'] = array('title' => __('(Edit)', 'disku'), 'mapping' => 'index.php:,disku_pollers.php:', 'url' => 'disku_pollers.php', 'level' => '2');
	$nav['disku_pollers.php:save'] = array('title' => __('(Save)', 'disku'), 'mapping' => 'index.php:,disku_pollers.php:', 'url' => 'disku_pollers.php', 'level' => '2');
	$nav['disku_pollers.php:actions'] = array('title' => __('Actions', 'disku'), 'mapping' => 'index.php:,disku_pollers.php:', 'url' => '', 'level' => '2');

	//$nav['disku_filesystems.php:'] = array('title' => 'Disk Pollers', 'mapping' => 'index.php:', 'url' => 'disku_filesystems.php', 'level' => '1');
	//$nav['disku_filesystems.php:edit'] = array('title' => '(Edit)', 'mapping' => 'index.php:,disku_filesystems.php:', 'url' => 'disku_filesystems.php', 'level' => '2');
	//$nav['disku_filesystems.php:save'] = array('title' => '(Save)', 'mapping' => 'index.php:,disku_filesystems.php:', 'url' => 'disku_filesystems.php', 'level' => '2');
	//$nav['disku_filesystems.php:actions'] = array('title' => 'Actions', 'mapping' => 'index.php:,disku_filesystems.php:', 'url' => '', 'level' => '2');

	$nav['disku_paths.php:'] = array('title' => __('Monitoring Paths', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_paths.php', 'level' => '1');
	$nav['disku_paths.php:edit'] = array('title' => __('(Edit)', 'disku'), 'mapping' => 'index.php:,disku_paths.php:', 'url' => 'disku_paths.php', 'level' => '2');
	$nav['disku_paths.php:save'] = array('title' => __('(Save)', 'disku'), 'mapping' => 'index.php:,disku_paths.php:', 'url' => 'disku_paths.php', 'level' => '2');
	$nav['disku_paths.php:actions'] = array('title' => __('Actions', 'disku'), 'mapping' => 'index.php:,disku_paths.php:', 'url' => '', 'level' => '2');

	$nav['disku_managers.php:'] = array('title' => __('Unix Group Data Managers', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_managers.php', 'level' => '1');
	$nav['disku_managers.php:edit'] = array('title' => __('(Edit)', 'disku'), 'mapping' => 'index.php:,disku_managers.php:', 'url' => 'disku_managers.php', 'level' => '2');
	$nav['disku_managers.php:save'] = array('title' => __('(Save)', 'disku'), 'mapping' => 'index.php:,disku_managers.php:', 'url' => 'disku_managers.php', 'level' => '2');
	$nav['disku_managers.php:actions'] = array('title' => __('Actions', 'disku'), 'mapping' => 'index.php:,disku_managers.php:', 'url' => '', 'level' => '2');

	$nav['disku_extenreg.php:'] = array('title' => __('File Extension Registry', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_extenreg.php', 'level' => '1');
	$nav['disku_extenreg.php:view'] = array('title' => __('File Extensions', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_extenreg.php', 'level' => '1');
	$nav['disku_extenreg.php:edit'] = array('title' => __('(Edit)', 'disku'), 'mapping' => 'index.php:,disku_extenreg.php:', 'url' => 'disku_extenreg.php', 'level' => '2');
	$nav['disku_extenreg.php:save'] = array('title' => __('(Save)', 'disku'), 'mapping' => 'index.php:,disku_extenreg.php:', 'url' => 'disku_extenreg.php', 'level' => '2');
	$nav['disku_extenreg.php:actions'] = array('title' => __('Actions', 'disku'), 'mapping' => 'index.php:,disku_extenreg.php:', 'url' => '', 'level' => '2');

	$nav['disku_extensions.php:'] = array('title' => __('Disk Usage by Extension', 'disku'), 'mapping' => '', 'url' => 'disku_extensions.php', 'level' => '0');
	$nav['disku_extensions.php:view'] = array('title' => __('Disk Usage by Extension', 'disku'), 'mapping' => '', 'url' => 'disku_extensions.php', 'level' => '0');

	$nav['disku_appview.php:'] = array('title' => __('Disk Usage by Application', 'disku'), 'mapping' => '', 'url' => 'disku_appview.php', 'level' => '0');
	$nav['disku_appview.php:view'] = array('title' => __('Disk Usage by Application', 'disku'), 'mapping' => '', 'url' => 'disku_appview.php', 'level' => '0');

	$nav['disku_tagview.php:'] = array('title' => __('Disk Usage by Tag Name', 'disku'), 'mapping' => '', 'url' => 'disku_tagview.php', 'level' => '0');
	$nav['disku_tagview.php:view'] = array('title' => __('Disk Usage by Tag Name', 'disku'), 'mapping' => '', 'url' => 'disku_tagview.php', 'level' => '0');

	$nav['disku_applications.php:'] = array('title' => __('Applications', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_applications.php', 'level' => '1');
	$nav['disku_applications.php:view'] = array('title' => __('Applications', 'disku'), 'mapping' => 'index.php:', 'url' => 'disku_applications.php', 'level' => '1');
	$nav['disku_applications.php:edit'] = array('title' => __('(Edit)', 'disku'), 'mapping' => 'index.php:,disku_applications.php:', 'url' => 'disku_applications.php', 'level' => '2');
	$nav['disku_applications.php:save'] = array('title' => __('(Save)', 'disku'), 'mapping' => 'index.php:,disku_applications.php:', 'url' => 'disku_applications.php', 'level' => '2');
	$nav['disku_applications.php:actions'] = array('title' => __('Actions', 'disku'), 'mapping' => 'index.php:,disku_applications.php:', 'url' => '', 'level' => '2');

	$nav['disku_users.php:'] = array('title' => __('Disk Usage by Operating System Users', 'disku'), 'mapping' => '', 'url' => 'disku_users.php', 'level' => '0');
	$nav['disku_users.php:view'] = array('title' => __('Disk Usage By Operating System Users', 'disku'), 'mapping' => '', 'url' => 'disku_users.php', 'level' => '0');

	$nav['disku_groups.php:'] = array('title' => __('Disk Usage by Operating System Groups', 'disku'), 'mapping' => '', 'url' => 'disku_groups.php', 'level' => '0');
	$nav['disku_groups.php:view'] = array('title' => __('Disk Usage By Operating System Groups', 'disku'), 'mapping' => '', 'url' => 'disku_groups.php', 'level' => '0');

	$nav['disku_orgview.php:'] = array('title' => __('Disk Usage by Organization', 'disku'), 'mapping' => '', 'url' => 'disku_orgview.php', 'level' => '0');
	$nav['disku_orgview.php:view'] = array('title' => __('Disk Usage By Organization', 'disku'), 'mapping' => '', 'url' => 'disku_orgview.php', 'level' => '0');

	$nav['disku_dashboard.php:'] = array('title' => __('Disk Monitoring Dashboard', 'disku'), 'mapping' => '', 'url' => 'disku_dashboard.php', 'level' => '0');
	$nav['disku_dashboard.php:view'] = array('title' => __('Disk Monitoring Dashboard', 'disku'), 'mapping' => '', 'url' => 'disku_dashboard.php', 'level' => '0');

	return $nav;
}

function disku_show_tab () {
	global $config, $disku_down, $tabs_left;

	if (api_user_realm_auth('disku_dashboard.php')) {
		if (!disku_tab_down()) {
			$disku_down = false;
		}	else {
			$disku_down = true;
		}

		if (!api_plugin_is_enabled('grid')) {
			if (get_selected_theme() != 'classic') {
				if (array_search('tab-disku', array_rekey($tabs_left, 'id', 'id')) === false) {
					$tab_disku = array(
							'title' => __('Disk Utilization', 'disku'),
							'id'	=> 'tab-disku',
							'url'   => html_escape($config['url_path'] . 'plugins/disku/disku_dashboard.php')
						);
					$tabs_left[] = &$tab_disku;
				} else {
					foreach($tabs_left as $tab_left) {
						if ($tab_left['id'] == 'tab-disku') {
							$tab_disku = &$tab_left;
						}
					}
				}
				if ($disku_down) {
					$tab_disku['selected'] = true;
				} else {
					unset($tab_disku['selected']);
				}
			} else {
				print '<a class="pic" href="' . html_escape($config['url_path'] . 'plugins/disku/disku_dashboard.php') . '"><img src="' . $config['url_path'] . 'plugins/disku/images/tab_disku' . ($disku_down ? '_down': '') . '.png" alt="File Systems"></a>';
			}
		}
	}
}

function disku_grid_tab_down($pages_grid_tab_down) {
	$disku_pages_grid_tab_down=array(
		'disku_dashboard.php' => '',
		'disku_orgview.php' => '',
		'disku_users.php' => '',
		'disku_groups.php' => '',
		'disku_extensions.php' => '',
		'disku_appview.php' => '',
		'disku_tagview.php' => ''
		);
	$pages_grid_tab_down += array("disku" => $disku_pages_grid_tab_down);
	return $pages_grid_tab_down;
}

function disku_grid_menu($grid_menu = array()) {
	global $menu_glyphs;

	$menu_glyphs[__('Disk Utilization', 'disku')] = 'far fa-hdd';

    $disk_menu = array(
        __('Disk Utilization', 'disku') => array(
			'plugins/disku/disku_dashboard.php'  => __('By Volume', 'disku'),
			'plugins/disku/disku_orgview.php'    => __('By Organization', 'disku'),
			'plugins/disku/disku_users.php'      => __('By OS User', 'disku'),
			'plugins/disku/disku_groups.php'     => __('By OS Group', 'disku'),
			'plugins/disku/disku_extensions.php' => __('By File Extension', 'disku'),
			'plugins/disku/disku_appview.php'    => __('By Application', 'disku'),
			'plugins/disku/disku_tagview.php'    => __('By Tag Name', 'disku')
		)
	);
	if (!empty($grid_menu)) {
		$menu2 = array();
		foreach($grid_menu as $gmkey => $gmval) {
			$menu2[$gmkey] = $gmval;
			if ($gmkey == __('Dashboards', 'grid')) {
				foreach($disk_menu[__('Disk Utilization', 'disku')] as $dmkey => $dmval) {
					$menu2[__('Disk Utilization', 'disku')][$dmkey] = $dmval;
				}
			}
		}
		return $menu2;
	}
	return $disk_menu;
}

function disku_tab_down() {
	$console_tabs = array(
		1  => 'disku_managers.php',
		2  => 'disku_pollers.php',
		3  => 'disku_extenreg.php',
		//4  => 'disku_filesystems.php',
		5  => 'disku_applications.php',
		6  => 'disku_paths.php'
	);

    if (strpos(get_current_page(), 'disku_') === false || array_search(get_current_page(), $console_tabs)) {
		return false;
	} else {
		return true;
	}
}


function disku_config_form() {
}

function disku_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q ' . $config['base_path'] . '/plugins/disku/poller_disku.php';

	exec_background($command_string, $extra_args);

	$extra_args = '-q ' . $config['base_path'] . '/plugins/disku/disku_add_device.php';
	exec_background($command_string, $extra_args);
}
