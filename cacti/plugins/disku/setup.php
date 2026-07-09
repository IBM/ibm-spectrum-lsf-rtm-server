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
		UNIQUE KEY `ext_contraint` (`extension`),
		KEY `ext` (`extension`))
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

	db_execute("CREATE TABLE IF NOT EXISTS `disku_extension_registry` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`extension` varchar(20) NOT NULL DEFAULT '',
		`notes` varchar(255) DEFAULT '',
		`monitor` char(3) NOT NULL DEFAULT 'on',
		PRIMARY KEY (`id`),
		UNIQUE KEY `ext_contraint` (`extension`),
		KEY `ext` (`extension`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

	db_execute("INSERT INTO `disku_extension_registry` VALUES
			(1,'$$$','Temporary file',''),
			(2,'001','FAX',''),
			(3,'1','A Unix nroff source file.',''),
			(4,'1st','Text Readme',''),
			(5,'2gr','A file that allows Windows to display text and graphics in standard mode- 286 or 386 comp
			uter',''),
			(6,'301','Fax',''),
			(7,'386','Swap file; enables computer running in enhanced mode to use Windows with virtual memory',
			''),
			(8,'3ds','Graphics (3D Studio)',''),
			(9,'3gr','A file that allows Windows to display text and graphics in standard mode- 386, 486, or Pe
			ntium computer',''),
			(10,'411','Sony Mavica Data file',''),
			(11,'4th','Forth (language) source code',''),
			(12,'8','A86 assembler source code file',''),
			(13,'8med','Amiga OcatMed music',''),
			(14,'8svx','Amiga 8-bit sound',''),
			(15,'669','669 MOD Music',''),
			(16,'906','Calcomp plotter',''),
			(17,'!im','Multimedia image file.',''),
			(18,'_ad','Multimedia audio file.',''),
			(19,'_au','Multimedia audio file.',''),
			(20,'_im','Multimedia image file.',''),
			(21,'a','Assembly Source Code',''),
			(24,'a3l','MacroMedia Authorware Windows Library',''),
			(25,'a4m','Unpackaged Authorware MacIntosh file',''),
			(26,'a4p','Authorware file packaged without runtime',''),
			(27,'a4w','Unpackaged Authorware Windows file',''),
			(28,'aam','Authorware (Adobe) file',''),
			(29,'aas','Authorware (Adobe) file',''),
			(30,'ab8','Datafile (ABStat)',''),
			(31,'aba','Address Book file (Palm)',''),
			(32,'abf','Adobe Binary Format',''),
			(33,'abk','AutoBackup (Corel Draw)',''),
			(34,'abm','PhotoPlus Album File',''),
			(35,'abr','Brush (Adobe Photoshop)',''),
			(36,'abs','MPEG sound file',''),
			(37,'acd','Sonic Foundry Acid sound file',''),
			(38,'ace','a compression file format for winace',''),
			(39,'acf','Agent Character File (Microsoft)',''),
			(40,'acl','Keyboard accelerator, Corel Draw',''),
			(41,'acm','Windows system directory file',''),
			(42,'acmv','Drivers for compressing or decompressing audio files, OS/2',''),
			(43,'ad','After Dark Screen Saver',''),
			(44,'ada','Ada source file',''),
			(45,'add','Adapter Driver (OS/2)',''),
			(46,'adi','Graphics (AutoCAD)',''),
			(47,'adm','AfterDark MultiModule',''),
			(48,'adn','Add-in (Lotus 1-2-3)',''),
			(49,'adr','AfterDark Randomizer',''),
			(50,'ads','Specification file type, Ada language',''),
			(51,'afi','Truevisiion bitmap graphic',''),
			(52,'afm','Adobe Font Metrics (Type 1)',''),
			(53,'ag4','Access G4',''),
			(54,'ai','PostScript',''),
			(56,'aif','Audio Interchange File Format or AIFF Sound',''),
			(57,'aifc','AIFF Sound Compressed',''),
			(58,'aiff','AIFF Sound',''),
			(59,'air','Adobe AIR Rich Internet applications',''),
			(61,'al','ALAW Sound',''),
			(62,'all','Arts and Letters Library',''),
			(64,'alt','Menu file (WordPerfect Win)',''),
			(65,'ana','underground text format; anarchy genre',''),
			(66,'ani','Animation',''),
			(68,'ans','ANSI text, graphics',''),
			(69,'apd','Aldus Printer Description',''),
			(70,'api','Application Program Interface',''),
			(71,'app','MacroMedia Authorware package',''),
			(74,'arc','Compressed file using PKARC',''),
			(75,'arj','A PC compressed format found on European sites',''),
			(76,'arr','Amber ARR Image',''),
			(77,'art','First Publisher',''),
			(79,'as','ActionScript Source File',''),
			(80,'asc','ASCII text file',''),
			(81,'asci','ASCII Text',''),
			(82,'ascii','ASCII Text',''),
			(83,'ascx','ASP.NET user control file (Microsoft)',''),
			(84,'asd','Autosave file (Word for Windows)',''),
			(85,'asf','Windows Media (Active Streaming File)',''),
			(87,'asi','Assembler include file',''),
			(88,'asm','Assembly language source',''),
			(89,'asmx','.NET Web Service file (Microsoft)',''),
			(90,'asp','Active Server Page (Microsoft)',''),
			(91,'aspx','ASP.NET file (Microsoft)',''),
			(92,'ast','Assistant file (Claris Works)',''),
			(93,'asx','Windows Media File',''),
			(94,'atm','Adobe Type Manager',''),
			(95,'au','A sound file format used on Sun Microsystems or other UNIX computers',''),
			(96,'avi','Audio Video Interleaved (AVI movie)',''),
			(97,'avs','Intel video capture format',''),
			(98,'b64','Base 64, encoding format used by the MIME.',''),
			(99,'bad','Bad file (Oracle)',''),
			(100,'bak','Backup file',''),
			(101,'bar','UNIX BAR Archive',''),
			(102,'bas','BASIC source file',''),
			(103,'bat','Batch job (MS-DOS)',''),
			(104,'bay','DOS batch file',''),
			(105,'bbs','Bulletin Board System',''),
			(106,'bcp','Borland C++ makefile',''),
			(107,'bcw','Borland C++ environment settings',''),
			(108,'bfc','Briefcase document (Windows)',''),
			(109,'bfm','Font metrics - UNIX',''),
			(110,'bga','OS/2 Bitmap',''),
			(111,'bib','Bibliography (ASCII)',''),
			(113,'bin','Driver',''),
			(115,'binary','Untyped Binary Data',''),
			(116,'bk','Backup file',''),
			(118,'bk!','Backup file (WordPerfect Windows)',''),
			(119,'bk$','Backup file',''),
			(120,'bkf','Backup file (Microsoft)',''),
			(121,'blk','Temporary file (WordPerfect Windows)',''),
			(122,'bm','Bitmap graphics file',''),
			(123,'bmp','Windows Bitmap',''),
			(124,'bnk','Sim City game file',''),
			(125,'boo','BOO Encoded File',''),
			(126,'brx','Browse Index (multimedia CD-ROMs)',''),
			(127,'bst','BibTex Style',''),
			(128,'btm','Batch file (Norton Utilities)',''),
			(129,'bup','Backup file',''),
			(130,'buy','Movie datafile',''),
			(131,'bw','SGI Image',''),
			(132,'c','C Source file',''),
			(133,'c++','C++ source',''),
			(134,'c00','Ventura Print file',''),
			(135,'ca','Cache data (root domain server)',''),
			(136,'cab','Microsoft compressed format',''),
			(137,'cal','Calendar or CALS',''),
			(139,'cap','Caption (Ventura Publisher)',''),
			(140,'cas','Comma-delimted ASCII file',''),
			(141,'cbl','Cobol source file',''),
			(142,'cbt','Computer-Based Training; a tutorial file',''),
			(143,'cc','C++ source code file',''),
			(144,'cch','Corel chart',''),
			(145,'ccm','Lotus cc:Mail',''),
			(146,'cct','Director Shockwave file (Macromedia)',''),
			(147,'cda','CD Audio track',''),
			(148,'cdr','Vector graphics file (Corel Draw)',''),
			(149,'cdt','Corel Draw Data',''),
			(150,'cdx','Visual FoxPro database index',''),
			(151,'cel','Graphics (Autodesk Animator)',''),
			(152,'cfb','Comptons multimedia encyclopedia',''),
			(153,'cfg','Configuration file',''),
			(154,'cfl','Corel Flow',''),
			(155,'cfm','ColdFusion Markup language',''),
			(156,'cgi','Common Gateway Interface (script)',''),
			(157,'cgm','Graphics Meta file',''),
			(158,'ch','Clipper Header file',''),
			(159,'chd','Font Description',''),
			(160,'chk','Checkdisk (CHKDSK) Recovered file',''),
			(162,'chl','Configuration History Log',''),
			(163,'chp','Chapter file (Ventura)',''),
			(164,'chr','ASCII character file (MegaZeux)',''),
			(165,'cif','Chapter Information File (Ventura)',''),
			(166,'cim','Sim City file',''),
			(167,'cit','Intergraph scanned image',''),
			(168,'ckb','Keyboard mapping (Borland C++)',''),
			(169,'cl','Common LISP source file',''),
			(170,'class','CodeWarrior Class',''),
			(172,'clp','Windows Clipboard',''),
			(174,'cls','C++ class definition file',''),
			(175,'cmd','file of commands, OS/2 batch file',''),
			(176,'cmf','Corel MetaFile',''),
			(179,'cmk','Card (Card Shop Plus)',''),
			(180,'cmp','LEAD Technologies graphics',''),
			(181,'cmv','Apple Viewer file',''),
			(185,'cnc','general program data',''),
			(186,'cnf','Configuration file',''),
			(187,'cnq','Compuworks Design Shop',''),
			(188,'cnv','data conversion support (Word for Windows)',''),
			(190,'cob','COBOL source code',''),
			(191,'cod','Program compiled code (FORTRAN)',''),
			(192,'col','Spreadsheet (Microsoft Multiplan)',''),
			(193,'com','Command file, excutable, smaller version of .EXE (DOS)',''),
			(194,'conf','Configuration file',''),
			(195,'cp','C++ Source file',''),
			(196,'cpe','Fax Cover document',''),
			(197,'cpl','Code Page (DOS)',''),
			(199,'cpp','C++ Source file',''),
			(200,'cpr','Knowledge Access graphics',''),
			(201,'cpt','Compact Pro compressed file',''),
			(202,'cpx','Corel Presentation Exchange compressed file',''),
			(203,'cpz','COMPOZ Music text file',''),
			(204,'crd','Windows Cardfile',''),
			(205,'crp','Encrypted database (dBASE IV)',''),
			(206,'crs','Conversion Resource (WordPerfect)',''),
			(207,'crt','Digital Certificate',''),
			(209,'csc','Corel Script',''),
			(210,'csm','Precompiled headers (Borland C++)',''),
			(211,'css','Cascading Style Sheet, Cascading StyleSheets',''),
			(212,'csv','Comma Separated Values text file (ASCII)',''),
			(214,'ct','Scitex-CT',''),
			(215,'ctl','Control file',''),
			(216,'cty','City file (SimCity)',''),
			(217,'ctx','Ciphertext file (PGP-RSA)',''),
			(218,'cuf','C Utilities Form (definition)',''),
			(219,'cul','Windows cursor library',''),
			(220,'cur','Windows cursor image',''),
			(221,'cut','Dr. Halo graphics',''),
			(222,'cv','Microsoft CodeView',''),
			(223,'cvp','Cover page (WinFax)',''),
			(224,'cvs','Canvas graphics file',''),
			(225,'cwk','Claris Works',''),
			(226,'cws','Claris Works Template',''),
			(227,'cxx','C++ source file (Zortech)',''),
			(228,'dat','Data file',''),
			(229,'db','Database file',''),
			(230,'dbc','Visual FoxPro database',''),
			(231,'dbf','Database file',''),
			(233,'dbg','Symbolic debugging info (C/C++)',''),
			(234,'dbk','Database backup',''),
			(235,'dbm','Datafile',''),
			(236,'dbo','Compiled Program (dBase IV)',''),
			(237,'dbs','Database (SQL Windows)',''),
			(239,'dbt','Database Text',''),
			(242,'dbw','DataBoss Windows file',''),
			(243,'dbx','DATABEAM graphics',''),
			(244,'dca','IBM text',''),
			(245,'dcf','Disk image file',''),
			(246,'dcp','Data CodePage (OS2)',''),
			(247,'dcr','Shockwave file',''),
			(248,'dcs','Color Separation (CMYK) EPS',''),
			(250,'dct','Database Dictionary',''),
			(252,'dcx','Bitmap graphics',''),
			(254,'dd','DiskDoubler compressed file',''),
			(255,'ddb','Bitmap graphics',''),
			(256,'ddi','Disk Doubler Image file',''),
			(258,'ddp','Device Driver Profile (OS2)',''),
			(259,'deb','Debug script (DOS)',''),
			(260,'def','Definition (C++)',''),
			(261,'dem','Demonstration',''),
			(262,'der','Certificate',''),
			(263,'dev','Device driver file',''),
			(264,'dfv','Printing form (Word)',''),
			(265,'dg','Autotrol graphics',''),
			(266,'dgn','Intergraph graphics',''),
			(267,'dgs','Diagnostics',''),
			(268,'dht','Datafile',''),
			(269,'dia','Diagraph graphics',''),
			(270,'dib','Device-independent bitmap graphics',''),
			(271,'dic','Dictionary (MS Word Custom Dictionary)',''),
			(272,'dif','Database (VisiCalc)',''),
			(274,'dip','Graphics',''),
			(275,'dir','Directory file (VAX)',''),
			(278,'dis','Distribution file (VAX)',''),
			(280,'diz','BBS Descriptive file',''),
			(281,'dkb','Raytraced graphics',''),
			(282,'dld','Data (Lotus 1-2-3)',''),
			(283,'dl','DL Animation',''),
			(284,'dll','Dynamic Link Library (Windows DLL)',''),
			(286,'dls','Disklock Setup (Norton)',''),
			(287,'dmg','Disk Image (Mac OS X)',''),
			(288,'dmo','Demo',''),
			(289,'dmp','Dump file',''),
			(290,'dms','Compressed file archive (Diskmasher/Amiga)',''),
			(291,'dng','Digital negative format (open standard)',''),
			(292,'doc','Document (ASCII or MS Word)',''),
			(293,'dos','Network driver',''),
			(295,'dot','Word for Windows Template',''),
			(297,'dox','MultiMate document',''),
			(298,'doz','Description Out Zip',''),
			(299,'dp','Calendar (Daily Planner)',''),
			(300,'dpi','Dots Per Inch (graphics)',''),
			(301,'dpr','Delphi project file (C++)',''),
			(302,'dqy','Excel Database Query',''),
			(303,'drs','Display Resource (WordPerfect)',''),
			(304,'drv','Device driver (printer)',''),
			(305,'drw','Graphics file (Micrografx)',''),
			(306,'ds4','Designer graphics, version 4',''),
			(307,'dsc','Discard file (Oracle)',''),
			(308,'dsd','Database (DataShaper)',''),
			(309,'dsk','Project desktop file (Borland C++)',''),
			(310,'dsf','Designer graphics, version 6',''),
			(311,'dsm','Digital Sound Module',''),
			(312,'dsn','Design',''),
			(313,'dsr','Driver Resource (WordPerfect)',''),
			(314,'dss','Sound file (Digital Soup)',''),
			(315,'dsw','Desktop Settings (Borland C++)',''),
			(316,'dta','Data file',''),
			(317,'dtd','Document Type Definition (SGML)',''),
			(318,'dtf','Database file (PFS)',''),
			(319,'dv','DV Video',''),
			(320,'dvc','Data (Lotus 1-2-3)',''),
			(321,'dvi','TeX DVI document',''),
			(324,'dvp','Device Paramater file (AutoCAD)',''),
			(325,'dw2','Drawing (DesignCAD)',''),
			(326,'dwc','Compressed file archive (DWC)',''),
			(327,'dwf','Drawing Web Format (Autodesk Whip! Viewer and Volo View)',''),
			(328,'dwg','Drawing file (AutoCAD)',''),
			(329,'dx','Text file (DEC)',''),
			(331,'dxf','Drawing Interchange File Format (AutoCAD 3D)',''),
			(332,'dxr','Shockwave Movie',''),
			(333,'dyn','Data (Lotus 1-2-3)',''),
			(334,'ebj','Error checking object file',''),
			(335,'ed5','EDMICS graphics',''),
			(336,'edt','Default Settings (VAX editor)',''),
			(337,'eeb','Equation Editor button bar (WordPerfect Windows)',''),
			(338,'efx','Fax (Everex)',''),
			(339,'ega','Display font (Ventura Publisher)',''),
			(340,'el','Elisp source file (Emacs lisp)',''),
			(341,'elc','Compiled Elisp code (Emacs lisp)',''),
			(342,'elm','Microsoft Theme',''),
			(343,'emf','Enhanced Metafile',''),
			(345,'eml','Electronic Mail (Outlook Express)',''),
			(346,'ems','Enhanced Menu System Config (PC Tools)',''),
			(347,'emu','Terminal emulation data',''),
			(348,'enc','Encoded file (UUENCODE)',''),
			(350,'end','Arrow-head definition file (CorelDRAW)',''),
			(351,'env','Environment file (WordPerfect Windows)',''),
			(352,'eps','Encapsulated PostScript (vector graphics)',''),
			(354,'epsf','Encapsulated PostScript File',''),
			(355,'eqn','Equation (WordPerfect Windows)',''),
			(356,'err','Error Messge/Log',''),
			(357,'esh','Extended Shell batch file',''),
			(358,'esi','Esri plot file',''),
			(359,'etx','SEText',''),
			(360,'evt','Event log',''),
			(361,'evy','Document (WordPerfect Envoy)',''),
			(362,'exe','Executable file (MS-DOS)',''),
			(363,'ext','Extension file (Norton)',''),
			(364,'f','Fortran source file',''),
			(365,'f01','Fax',''),
			(366,'f06-16','Screen text font, 6-16 pixels (DOS)',''),
			(367,'f77','Fortran 77 source file',''),
			(368,'f96','Frecom FAX96',''),
			(369,'faq','Frequently asked questions',''),
			(370,'fax','Fax file (raster graphics)',''),
			(371,'fc','Dictionary (Harvard Graphics)',''),
			(372,'fd','Fortran Declaration file (Microsoft)',''),
			(373,'fdf','Acrobat Forms Data Format',''),
			(374,'fdx','Force Index',''),
			(375,'ffl','Fast Find File (Microsoft)',''),
			(376,'fh3-4','Freehand 3-4 (vector graphics)',''),
			(377,'fi','Fortran Interface file',''),
			(378,'fif','Fractal Image format',''),
			(379,'fil','Files list object file (dBASE)',''),
			(381,'fit','File Index Table (WindowsNT)',''),
			(383,'fix','Patch file',''),
			(384,'fky','Macro file (FoxPro)',''),
			(385,'flc','Animation file (Autodesk, FLIC)',''),
			(386,'fld','Thumbnail folder (Hijaak)',''),
			(387,'fli','Animation file (Autodesk)',''),
			(388,'flo','Micrografx FlowCharter',''),
			(389,'flr','Live3D',''),
			(390,'flt','Filter (graphic conversion)',''),
			(391,'fm','Framemaker',''),
			(393,'fm3','Spreadsheet (Lotus 1-2-3 v3)',''),
			(395,'fmb','File Manager Button bar (WordPerfect Windows)',''),
			(396,'fmk','Fortran Makefile',''),
			(397,'fmo','Compiled format file (dBASE)',''),
			(398,'fmt','Format (dBASE)',''),
			(400,'fnc','Frogans Network Certificate',''),
			(401,'fnt','Font file',''),
			(402,'fo1-02','Font file (Borland)',''),
			(403,'fog','Fontographer font',''),
			(404,'fon','Bitmapped Font (Windows)',''),
			(406,'for','FORTRAN source code',''),
			(407,'fot','Windows TrueType font',''),
			(408,'fox','FoxBase',''),
			(409,'fp','FileMaker Pro',''),
			(410,'fp1','Flying Pigs',''),
			(411,'fp3-5','FileMaker v3-5',''),
			(412,'fpc','FoxPro Catalog',''),
			(413,'fpt','FoxPro Memo',''),
			(414,'fpx','FlashPix bitmap',''),
			(415,'frf','Font (FontMonger)',''),
			(416,'frm','Report form file (dBASE, VisualBasic)',''),
			(417,'frs','WordPerfect Graphics Driver',''),
			(418,'frt','Report memo (FoxPro)',''),
			(419,'frx','Report (FoxPro)',''),
			(420,'fsl','Form (Paradox)',''),
			(421,'fsx','Data (Lotus 1-2-3)',''),
			(422,'ftm','Font (Micrografx)',''),
			(423,'fts','Flexible Image Transport',''),
			(424,'fw','Database (Framework)',''),
			(425,'fxp','Compiled format (FoxPro)',''),
			(426,'fxs','Fax Transmit Format (WinFax)',''),
			(427,'g4','GTX RasterCAD',''),
			(428,'gbl','Global definitions',''),
			(429,'gc1','Lisp Source code',''),
			(430,'gca','GOCA graphics',''),
			(431,'gcd','Graphics file',''),
			(432,'gcf','Graphing Calculator file',''),
			(433,'gdf','Dictionary file',''),
			(434,'ged','Arts & Letters graphics',''),
			(436,'gem','GEM Metafile',''),
			(438,'gen','Compiled template (dBASE)',''),
			(440,'gep','Geode',''),
			(441,'geo','VideoScope',''),
			(442,'gib','Graph-in-the-Box Chart',''),
			(443,'gif','Graphics Interchange Format bitmap (CompuServe)',''),
			(444,'giw','Presentation (Graph-in-the-Box)',''),
			(445,'gl','GL Animation',''),
			(446,'gly','Glossary',''),
			(447,'gp3','CCITT compressed Group 3-4 TIFF file',''),
			(448,'gph','.Graph (Lotus 1-2-3)',''),
			(449,'gr2','.Screen driver (Windows 3.x)',''),
			(450,'gra','Microsoft Graph',''),
			(451,'grn','Ms-DOS Shell Monitor file',''),
			(452,'grf','Graphic file (Micrografx)',''),
			(453,'grp','Group definition (Windows)',''),
			(455,'gs1','Presentation',''),
			(456,'gsd','Vector graphics (Professional Draw)',''),
			(457,'gsw','GraphShow Worksheet',''),
			(458,'gx1','Show Partner graphics',''),
			(459,'gxl','Graphics Library',''),
			(460,'gz','Unix file compuressed by GZIP',''),
			(461,'h','C Header',''),
			(463,'h++','Header - C++',''),
			(464,'ha','HA Compressed file',''),
			(465,'hac','underground text format; hacking genre',''),
			(466,'hcom','SoundEdit Sound ex SOX',''),
			(467,'hdf','Hierarchical Data File',''),
			(469,'hdr','Datafile',''),
			(471,'hdw','Harvard Draw Vector graphics',''),
			(472,'hdx','Help index (AutoCAD - Zortech C++)',''),
			(473,'hed','HighEdit document',''),
			(474,'hel','Microsoft Hellbender game file',''),
			(475,'hex','Hex dump',''),
			(476,'hfi','HP font info',''),
			(477,'hgl','HP Graphics Language',''),
			(478,'hh','C++ header',''),
			(479,'hhh','Precompiled header',''),
			(480,'hhp','Help Information',''),
			(481,'hlb','Help Library (VAX)',''),
			(482,'hlp','Help file (Windows)',''),
			(483,'hlx','Hyper link extension',''),
			(484,'hof','Hall Of Fame (game scores)',''),
			(485,'hp','C Include file',''),
			(486,'hpf','HP LaserJet fonts',''),
			(487,'hpg','HPGL plotter file vector graphics (AutoCad,Harvard. Graphics)',''),
			(488,'hpgl','HP GL/2',''),
			(489,'hpi','Font information file',''),
			(490,'hpj','Help Project (Visual Basic)',''),
			(491,'hpk','HPACK compressed file',''),
			(492,'hpl','HP Graphics',''),
			(493,'hpm','Emm text (HP NewWave)',''),
			(494,'hpp','C++ header file',''),
			(496,'hqx','BinHexed Compressed Macintosh file.',''),
			(497,'hrf','Graphics file',''),
			(498,'hsi','Graphics file (Handmade Software)',''),
			(499,'hst','History file',''),
			(501,'ht','HyperTerminal',''),
			(502,'htm','HyperText',''),
			(504,'html','Hypertext Markup Language',''),
			(505,'htx','Hypertext file',''),
			(506,'hum','underground text format; humor genre',''),
			(507,'hwd','Presentation file (Hollywood)',''),
			(508,'hwp','Hangul Word Processor',''),
			(509,'hxx','C++ header file',''),
			(510,'hy1-2','Hyphenation algorithms (Ventura)',''),
			(511,'hyc','Hyphenation (WordPerfect)',''),
			(512,'hyp','Hyphenation dictionary',''),
			(514,'i','Intermediate file (C++)',''),
			(515,'i3','Modula 3 Interface',''),
			(516,'iax','Bitmap graphics (IBM)',''),
			(517,'ic1-3','Atari Image',''),
			(518,'ica','Bitmap graphics (Image Object Content Architecture)',''),
			(519,'icb','Targa bitmap graphics',''),
			(520,'icl','Icon Library',''),
			(521,'icm','Image Colour Matching',''),
			(522,'icn','Icon source file',''),
			(523,'ico','Icon file (Windows)',''),
			(524,'icon','Sun icon and cursor',''),
			(525,'id','Disk identification file',''),
			(526,'idd','Instrument Definition (MIDI)',''),
			(527,'ide','Integrated Development Environment configuration',''),
			(529,'idw','Vector graphics',''),
			(530,'idx','Index',''),
			(531,'ief','IEF Image',''),
			(532,'ifd','Form (JetForm)',''),
			(533,'iff','Interchange File Format (Amiga Image)',''),
			(535,'ifo','Graphics objects (ImageForge)',''),
			(536,'ifs','System file (OS/2)',''),
			(537,'igf','Inset Systems graphics (Hijaak)',''),
			(538,'iif','Interchange File (QuickBooks)',''),
			(539,'il','Icon library',''),
			(540,'ilbm','Amiga ILBM Image',''),
			(541,'im8','Raster graphics (Sun)',''),
			(542,'ima','Vector graphics (Mirage)',''),
			(544,'image','DiskCopy Disk Image (Macintosh)',''),
			(545,'img','ImageForge/IconForge file',''),
			(551,'imp','Spreadsheet (Lotus)',''),
			(552,'imq','Image Presentation',''),
			(553,'in3','Input device driver (Harvard Graphics)',''),
			(554,'inc','Include file',''),
			(555,'ind','Index (dBASE)',''),
			(556,'indd','Adobe InDesign Document',''),
			(557,'indt','Adobe InDesign template file',''),
			(558,'inf','Information file',''),
			(562,'ini','Initialization file',''),
			(564,'ink','Pantone reference file (CorelDRAW)',''),
			(565,'ins','Data (WordPerfect)',''),
			(567,'int','Borland Interface',''),
			(568,'inx','Index (Foxbase)',''),
			(569,'iob','Imagine file',''),
			(570,'ioc','Organizational chart',''),
			(571,'ipl','Pantone Spot reference palette',''),
			(572,'irs','Resource (WordPerfect)',''),
			(573,'isd','Spell checker dictionary',''),
			(574,'iso','ISO-9660 table',''),
			(575,'isu','Netscape file',''),
			(576,'it','MOD music file format',''),
			(577,'itl','iTools Commands',''),
			(578,'iw','Idlewild screensaver',''),
			(579,'iwa','Text file (IBM)',''),
			(580,'iwp','Text file (Wang)',''),
			(581,'ix','Index file (FrameMaker)',''),
			(582,'jar','Java classes',''),
			(583,'jax','Graphics',''),
			(584,'jav','Java source file',''),
			(585,'java','Java source code',''),
			(586,'jbx','Project file',''),
			(587,'jet','Fax (JetFax)',''),
			(588,'jff','JFIF, JPEG File Interchange Format',''),
			(589,'jfif','JFIF Image',''),
			(590,'jif','JPEG image format',''),
			(591,'jpe','JPEG bitmap file',''),
			(592,'jpeg','JPEG (Joint Photographic Experts Group)',''),
			(593,'jpg','JPEG image file',''),
			(594,'jpp','x-java-print',''),
			(595,'js','JavaScript',''),
			(596,'jtf','JPEG bitmap file',''),
			(597,'kar','MIDI Sound',''),
			(598,'kdc','Kodak Photo-Enhancer',''),
			(599,'kfx','Kofax',''),
			(600,'lar','Liquid Registration',''),
			(601,'lasso','Lasso web format file (Blue World Communications).',''),
			(602,'latex','Latex',''),
			(603,'lav','Liquid Voucher',''),
			(605,'lbl','Label description (dBASE)',''),
			(606,'lbm','Deluxe Paint graphics file',''),
			(608,'ldb','Lock file (Microsoft Access)',''),
			(609,'leg','Legacy document',''),
			(610,'lha','PC/Amiga compression format',''),
			(612,'lhs','Literate Haskell source file',''),
			(613,'lhx','LHA compression file',''),
			(614,'lib','Library',''),
			(615,'lic','License file (usually shareware)',''),
			(616,'log','Log of installations or usage',''),
			(617,'lst','List',''),
			(618,'ltf','Frogans Short-cut',''),
			(619,'lwp','Lotus Word Pro',''),
			(620,'lzh','LHArc Archive',''),
			(621,'lzs','PC/Amiga compression format',''),
			(622,'m1a','MPEG-1 audiostream',''),
			(623,'m1s','MPEG-1 systemstream',''),
			(624,'m1v','MPEG-1 IPB videostream',''),
			(625,'m15','MPEG videostream',''),
			(626,'m2','Modula 2 Source Code',''),
			(627,'m2v','MPEG-2 IPB videostream',''),
			(628,'m3','Modula 3 Source Code',''),
			(629,'m3u','MP3 PlayLists',''),
			(630,'m75','MPEG file',''),
			(631,'mac','Mac Paint (PICT picture)',''),
			(632,'maf','Microsoft Access Form',''),
			(633,'mak','Makefile (VisualBasic, etc)',''),
			(634,'mam','Microsoft Access Macro',''),
			(635,'map','Map - linkage editor',''),
			(636,'maq','Microsoft Access Query',''),
			(637,'mar','Microsoft Access Report',''),
			(638,'mat','Microsoft Access Table',''),
			(639,'max','Paperport scanner-generated image file',''),
			(640,'mbx','Message Base (Eudora)',''),
			(641,'mcs','MathCAD format',''),
			(642,'mcw','Microsoft Word for Macintosh',''),
			(643,'mdb','Database file (MS Access)',''),
			(644,'mdf','Microsoft SQL server database file',''),
			(645,'mdx','Multi-index',''),
			(646,'me','READ.ME text file',''),
			(647,'med','Amiga MED Sound',''),
			(648,'met','Metafile format',''),
			(649,'meu','DOS shell menu',''),
			(650,'mf','MetaFont document',''),
			(651,'mff','MIDI file format',''),
			(652,'mgf','Micrografx Draw Font',''),
			(653,'mi','Miscellaneous',''),
			(654,'mic','Microsoft Image Composer',''),
			(655,'mid','MIDI digitized music file',''),
			(656,'mif','FrameMaker MIF',''),
			(657,'miff','ImageMagick file format',''),
			(658,'mil','Group 4',''),
			(659,'ml','ML Source',''),
			(660,'mime','Multipurpose Internet Mail Extension (Message)',''),
			(661,'mix','Microsoft PictureIt!',''),
			(663,'mme','Mime encoded file',''),
			(664,'mmf','Microsoft Mail File',''),
			(665,'mmm','Macromind animation',''),
			(666,'mod','MOD Music file format',''),
			(667,'mol','MDL Molfile',''),
			(668,'mov','Movie file (Apple QuickTime)',''),
			(669,'moov','Movie file (Apple QuickTime)',''),
			(670,'moz','Mozilla Netscape cache file',''),
			(671,'mp2','MPEG-1 audiostream; MPEG-1 Layer 2',''),
			(672,'mp3','Sound file; MPEG-1 Layer 3',''),
			(673,'mpa','MPEG-1 audiostream',''),
			(674,'mpe','MPEG Movie file',''),
			(675,'mpeg','MPEG Movie (Moving Pictures Experts Group)',''),
			(676,'mpeg4','MPEG Movie (high quality, esp for wireless)',''),
			(677,'mpg','Encoded MPEG file',''),
			(678,'mpkg','meta package, a group of .pkg files',''),
			(679,'mpm','MPEG file',''),
			(680,'mpnt','Macintosh MacPaint',''),
			(681,'mpp','Microsoft Project project file',''),
			(682,'mpt','Microsoft Project template file',''),
			(683,'mpv','MPEG file',''),
			(684,'mpx','Microsoft Project export file',''),
			(685,'mrk','Markup (Informative Graphics)',''),
			(686,'msg','Message file',''),
			(687,'msi','Microsoft Installer file',''),
			(688,'msn','Microsoft Network document',''),
			(689,'msp','Microsoft Paint',''),
			(690,'mtm','MultiMOD Music',''),
			(691,'mus','Music file',''),
			(692,'mvb','Microsoft Multimedia Viewer',''),
			(693,'mw','MacWrite II document',''),
			(694,'mwii','MacWrite II document',''),
			(695,'mxi','Extension Information',''),
			(696,'mxp','Extension Package',''),
			(697,'mys','Myst saved game file',''),
			(698,'ndx','Index file (dBASE)',''),
			(699,'neo','Atari NeoChrome',''),
			(700,'netopia','Application Netopia',''),
			(701,'net','Network file',''),
			(702,'nfo','Info Text',''),
			(703,'ng','Norton Guides',''),
			(704,'nlm','NLM program (NetWare)',''),
			(705,'note','WordPerfect Help document',''),
			(706,'nsl','Network Services',''),
			(707,'nsp','Application x-nvo',''),
			(708,'nst','MOD Music',''),
			(709,'ntf','Notes Template File (Lotus Notes)',''),
			(710,'oab','Outlook Address Book (Microsoft)',''),
			(711,'oaz','OAZ Fax',''),
			(712,'obd','Microsoft Office Binder',''),
			(713,'obj','Object (DOS/Windows)',''),
			(714,'ocx','Object Linking and Embedding (OLE)',''),
			(715,'oda','ODA document',''),
			(716,'oga','audio files Xiph.Org',''),
			(717,'ogx','Ogg Vorbis Multiplex Profile (audio) Xiph.Org',''),
			(718,'okt','Oktalyser MOD Music',''),
			(719,'olb','OLE Object Library',''),
			(720,'old','a file that is now obsolete replaced by an upgraded or modified file.',''),
			(721,'ole','OLE object (Object Linking and Embedding)',''),
			(722,'onx','Onyx Graphics Postershop',''),
			(723,'ori','Original',''),
			(724,'otf','OpenType Font',''),
			(725,'out','Output file',''),
			(726,'ovl','Program overlay file (DOS/Windows)',''),
			(727,'ovr','Overlay module',''),
			(728,'p','Pascal source code file',''),
			(729,'p10','Tektronix Plot10',''),
			(730,'pac','Atari STAD Image',''),
			(731,'pab','Personal Address Book (Microsoft Outlook)',''),
			(732,'pak','Quake game data',''),
			(734,'pal','Paintbrush palette',''),
			(735,'pan','Printer-specific file',''),
			(736,'pas','Pascal program file',''),
			(737,'pat','Pattern (Corel Draw)',''),
			(739,'pb1','Document (First Publisher)',''),
			(740,'pba','Powerbasic BASIC source code',''),
			(741,'pbd','PowerBuilder runtime/compiled file',''),
			(742,'pbi','Powerbasic include file',''),
			(743,'pbl','Powerbasic library',''),
			(745,'pbk','Microsoft Phonebook',''),
			(746,'pbm','Portable Bitmap',''),
			(747,'pbo','Profiler Binary Output',''),
			(748,'pbt','Profiler Binary Table',''),
			(749,'pc1-2-3','Atari Degas Image',''),
			(750,'pcd','Bitmap graphics file (Photo CD format)',''),
			(751,'pcl','HP LaserJet',''),
			(752,'pcm','HP LaserJet cartridge information',''),
			(753,'pcs','PICS animation',''),
			(754,'pct ','Apple Macintosh QuickDraw/PICT bitmap graphics format',''),
			(756,'pcw','PC Write',''),
			(757,'pcx','PC Paintbrush',''),
			(759,'pdb','Palm database',''),
			(760,'pdd','Photo Deluxe Document (Adobe)',''),
			(761,'pdf','Portable Document Format (Acrobat)',''),
			(762,'pdg','PrintShop Deluxe',''),
			(763,'pdv','PC Paintbrush printer driver',''),
			(764,'pfa','Type 1 font (ASCII)',''),
			(765,'pfb','Type 1 font (encrypted)',''),
			(766,'pfm','Postscript Font Metrics; Windows Type 1 font metrics',''),
			(767,'pgl','HPGL 7475A plotter (vector graphics)',''),
			(768,'pgm','Portable Gray Map (bitmap)',''),
			(769,'phtml','HTML document with preprocessing instructions (PPIs)',''),
			(770,'pic','Picture file (Macintosh PICT)',''),
			(771,'pict','Picture file (Macintosh PICT)',''),
			(772,'pif','Program Informaton file',''),
			(773,'pit','PackIt (Mac compression)',''),
			(774,'pix','Inset Systems graphics',''),
			(775,'pjx','Project file (Visual FoxPro)',''),
			(776,'pkg','AppleLink Package compression format',''),
			(777,'pl','Perl Source Code',''),
			(778,'plt','Plotter drawing, HP GL/2',''),
			(779,'pm','Bitmap From XV',''),
			(781,'pm3-4-5','Page Layout file (PageMaker v3,v4,v5)',''),
			(782,'png','Portable Network Graphic (bitmap)',''),
			(784,'pntg','MacPaint Graphic',''),
			(785,'pov','Persistence Of Vision raytracer',''),
			(786,'ppa','PowerPoint Add-in (Microsoft)',''),
			(787,'ppd','Printer Description File',''),
			(789,'ppm','Portable Pixelmap',''),
			(790,'ppp','PagePlus document',''),
			(791,'pps','PowerPoint Slideshow',''),
			(792,'ppt','PowerPoint Presentation',''),
			(793,'ppx','PagePlus template',''),
			(794,'prc','Palm Resource Code (Application)',''),
			(795,'prd','Printer driver file (MS Word)',''),
			(796,'prg','Procedure or program file',''),
			(797,'prj','Project file',''),
			(798,'prn','Printer Output file (temporary)',''),
			(800,'prs','Printer file (WordPerfect)',''),
			(801,'prt','Formatted text',''),
			(802,'ps','PostScript (Page description file)',''),
			(803,'psd','Photoshop file',''),
			(804,'pst','MS Outlook personal folder',''),
			(805,'pt4-5','PageMaker Template',''),
			(806,'pub','Publication (Ventura, MS)',''),
			(807,'pwd','Pocket Word document (Microsoft)',''),
			(808,'pwz','PowerPoint Wizard (Microsoft)',''),
			(809,'pxl','Pocket Excel (Microsoft)',''),
			(810,'pxr','Pixar Image',''),
			(811,'qbw','QuickBooks - Windows',''),
			(812,'qbb','QuickBooks backup file',''),
			(813,'qdk','Quarterdeck Qemm',''),
			(814,'qdv','QDV Image',''),
			(815,'qlb','Quick Library',''),
			(816,'qlc','ATM font information',''),
			(817,'qry','Microsoft Query',''),
			(818,'qt','QuickTime movie',''),
			(819,'qtm','QuickTime movie',''),
			(820,'qxd','QuarkXPress document',''),
			(821,'qxl','QuarkXPress Library file',''),
			(822,'qxt','QuarkXPress Template',''),
			(823,'r8l','LaserJet landscape font',''),
			(824,'r8p','LaserJet portrait font',''),
			(825,'ra','RealAudio Player - sound or multimedia file',''),
			(826,'ram','RealAudio Player',''),
			(827,'rm','RealAudio Player',''),
			(828,'rar','WinRAR compressed file',''),
			(829,'ras','Raster graphics (Sun)',''),
			(830,'raw','Raw Image file (bitmap)',''),
			(831,'rc','run commands (Unix)',''),
			(832,'readme','Text Readme',''),
			(833,'rec','Windows Recorder macro file',''),
			(834,'reg','Registration file',''),
			(835,'rep','Report file',''),
			(836,'rft','RFT-DCA -  Revisable-Form Text (RFT), part of IBM\'s Document Content Architecture (DCA
			)',''),
			(837,'rgb','RGB image (SGI- Silicon Graphics) -  Red Green Blue',''),
			(838,'rgba','RGB image (SGI)',''),
			(839,'ria','Alpharel Group IV raster graphics',''),
			(840,'rib','Renderman 3D Data',''),
			(841,'ric','Roch FaxNet',''),
			(842,'rif','Painter bitmap',''),
			(843,'riff','Resource Interchange File Format (graphic/multimedia)',''),
			(844,'rla','Wavefront raster image',''),
			(845,'rlc','CAD Overlay ESP (Image Systems)',''),
			(846,'rle','Run Length Encoded (compressed bitmap file)',''),
			(847,'rmi','RIFF MIDI file',''),
			(848,'rme','Text Readme',''),
			(849,'rmp','Niku Portfolio Manager',''),
			(850,'rnd','AutoShade format',''),
			(851,'rnl','GTX Runlength raster graphics',''),
			(852,'rpl','Replica file',''),
			(853,'rpt','Report file',''),
			(854,'rsc','Resource file',''),
			(855,'rsrc','Resource file',''),
			(856,'rtf','Rich Text Format',''),
			(857,'rtx','Rich Text Format',''),
			(858,'rwv','Niku Portfolio Manage',''),
			(859,'s','Assembly language file (Unix)',''),
			(860,'s3m','ScreamTracker 3 MOD music file',''),
			(861,'sam','AmiPro file',''),
			(863,'sav','Saved file (games etc)',''),
			(864,'sbp','IBM Storyboard graphics',''),
			(865,'sc','Source Code (Paradox)',''),
			(866,'scc','MSX Picture',''),
			(867,'scg','ColoRIX',''),
			(868,'sci','ColoRIX',''),
			(869,'scp','ColoRIX',''),
			(870,'scr','ColoRIX',''),
			(871,'scm','ScreenCam Movie',''),
			(876,'scrn','StartupScreen Format Graphic',''),
			(877,'sct','Screen Capture Text (Lotus)',''),
			(878,'scu','ColoRIX',''),
			(879,'sdl','SmartDraw Library',''),
			(880,'sea','Self-Extracting Archive (Stuffit or Compact Pro)',''),
			(881,'seg','Segment (of a compressed file saved in segments)',''),
			(882,'sep','TIFF bitmap',''),
			(883,'set','Setup parameters',''),
			(884,'sf','IRCAM Sound',''),
			(885,'sf2','SoundBank file',''),
			(886,'sfl','LaserJet landscape font',''),
			(887,'sgi ',' SGI Image;  Silicon Graphics IRIS Graphic File (Silicon Graphics, Inc.)',''),
			(888,'sfp','LaserJet portrait font',''),
			(889,'sfs','PCL 5 scalable font',''),
			(890,'sha','UNIX shell archive',''),
			(891,'shar','UNIX shell archive',''),
			(892,'shp','Printmaster Icon Library',''),
			(893,'shtml','HTML file with embedded server-side includes (SSI)',''),
			(894,'si','SoftImage image file',''),
			(895,'sig','Signature file',''),
			(896,'sit','StuffIt Archive (Macintosh)',''),
			(897,'sithqx','BinHexed StuffIt Archive (Macintosh)',''),
			(898,'six','SIXEL Image',''),
			(899,'sld','Slide format file (AutoCAD)',''),
			(900,'slk','Symbolic Link (SYLK) spreadsheet',''),
			(901,'smi','self-mounting Disk Copy compressed image',''),
			(902,'snd','Sound file',''),
			(903,'snm','Netscape mail',''),
			(904,'spc','Atari Spectrum 512',''),
			(905,'spd','Speedo scalable font',''),
			(906,'spl','FutureSplash Player file',''),
			(907,'sqb','SyQuest Backup',''),
			(908,'sr','Sun Raster Image',''),
			(909,'src','Used with .INI files for configuration settings',''),
			(910,'sty','Style sheet file (Ventura)',''),
			(911,'sun','Sun Raster Image',''),
			(912,'sup','Startup Screen',''),
			(913,'svx','Amiga 8SVX IFF sound',''),
			(914,'swd','Schoolwide Database file',''),
			(915,'swf','Shockwave Flash file',''),
			(916,'swp','Swap - temporary file',''),
			(917,'syd','Sysedit backup file',''),
			(918,'syk','SYLK spreadsheet format',''),
			(919,'sylk','SYLK spreadsheet format',''),
			(920,'sys','System file',''),
			(921,'tal','Adobe Type Align',''),
			(922,'tar','Tape ARchive (UNIX)',''),
			(923,'tar.gz','Combined filename extension for a file that has been archived using tar and then gzi
			pped.',''),
			(924,'tar.z','Combined filename extension for a file that has been archived using tar and then zipp
			ed.',''),
			(925,'targa','Truevision Archive',''),
			(926,'taz','Compressed Tape Archive',''),
			(928,'tbk','Asymetrix Toolbook',''),
			(929,'tdf','Typeface definition (Speedo)',''),
			(930,'tex','TeX or LaTex document',''),
			(931,'texi','TeX document',''),
			(932,'texinf','TeX document',''),
			(933,'text','ASCII Text',''),
			(934,'tfm','Font metrics (Intellifont)',''),
			(935,'tga','TARGA graphics file',''),
			(937,'tgz','Gnu ZIPped Taped Archive (same as .tar.z and .tar.gz)',''),
			(938,'thn','Thumbnail',''),
			(939,'tif','Tagged Image File format (graphics)',''),
			(940,'tiff','Tagged Image File Format (graphics)',''),
			(941,'tlb','OLE Type Library',''),
			(942,'tmp','Temporary file',''),
			(943,'tny','Atari TINY Bitmap',''),
			(944,'toc','Table Of Contents file',''),
			(945,'tps','Topspeed data file',''),
			(946,'trm','Terminal settings',''),
			(947,'tsv','Tab Separated Values File',''),
			(948,'ttc','TrueType Compressed -font file',''),
			(949,'ttf','TrueType Font file',''),
			(950,'tx8','8-bit MS-DOS text file',''),
			(951,'txt','ASCII Text file',''),
			(952,'ul','µlaw Sound',''),
			(953,'url','URL Bookmark',''),
			(954,'usb','Universal Serial Bus',''),
			(955,'usl','LaserJet landscape font',''),
			(956,'usp','LaserJet portrait font',''),
			(957,'uu','UUEncode compressed file',''),
			(958,'uud','UUEncode file',''),
			(959,'uue','UUEncode file',''),
			(960,'vbp','Visual Basic Project',''),
			(961,'vbs','Visual Basic Script',''),
			(962,'vbx','Visual Basic Extension',''),
			(963,'vcd','Video CD (Compact Disc)',''),
			(964,'vda','Targa bitmap',''),
			(965,'vff','DESR VFF Greyscale Image',''),
			(966,'vga','OS/2 Bitmap',''),
			(967,'vgr','Chapter file (Ventura)',''),
			(968,'vid','Multimedia image file',''),
			(969,'vir','Underground text format; virus genre',''),
			(970,'viff','Khoros Visualization image file',''),
			(971,'vlb','Ventura Library',''),
			(972,'voc','Sound file',''),
			(973,'vp','Ventura Publisher file',''),
			(974,'vsd','Microsoft Visio Drawing',''),
			(975,'vst','Targa bitmap',''),
			(976,'vue','Relational View (dBASE)',''),
			(977,'vxd','Virtual Device Driver',''),
			(978,'w51','WordPerfect PC 5.1 document',''),
			(979,'wab','Windows Address Book',''),
			(980,'wav','Windows WAV Sound file',''),
			(981,'wbk','Microsoft Word Backup',''),
			(982,'wdb','Microsoft Works Database',''),
			(983,'wid','Ventura width table',''),
			(984,'win','Windows backup file',''),
			(985,'wiz','Microsoft Word Wizard',''),
			(986,'wk1','Lotus 1-2-3, up to 2.01',''),
			(987,'wk4','Lotus 1-2-3 version 4',''),
			(988,'wkq','Quattro spreadsheet',''),
			(989,'wks','Spreadsheet (Lotus 1-2-3, MS Works)',''),
			(990,'wlr','VRML file',''),
			(991,'wmf','Windows Metafile',''),
			(992,'wp','WordPerfect PC document',''),
			(993,'wp4-6','WordPerfect PC document',''),
			(994,'wpd','WordPerfect Document',''),
			(996,'wpg','WordPerfect Graphic',''),
			(997,'wpm','WordPerfect Macro',''),
			(998,'wps','Word processor file (MS Works)',''),
			(999,'wpt','WordPerfect Template',''),
			(1000,'wri','MSWrite Windows file',''),
			(1001,'wrk','Symphony spreadsheet',''),
			(1002,'ws1-7','WordStar for Windows 1-7',''),
			(1003,'wve','PSION Sound',''),
			(1004,'wvl','Wavelet compressed bitmap',''),
			(1005,'x','AVS X image file',''),
			(1006,'x10-11','X-Windows Dump',''),
			(1007,'xar','Corel Xara drawing',''),
			(1008,'xbm','X-Windows Bitmap',''),
			(1009,'xfx','JetFax',''),
			(1010,'xif','Xerox Image Format',''),
			(1011,'xl','Excel Spreadsheet',''),
			(1012,'xlc','Excel Chart',''),
			(1013,'xlk','Excel backup',''),
			(1014,'xlm','Excel macro',''),
			(1015,'xls','Excel Spreadsheet',''),
			(1016,'xlt','Excel Template',''),
			(1017,'xlw','Excel Workspace',''),
			(1018,'xm','FastTracker MOD music format',''),
			(1019,'xpm','X-Windows Pixelmap',''),
			(1020,'xwd','X-Windows Dump',''),
			(1021,'xwo','X-Windows System window dump image',''),
			(1022,'xxe','Xxencoded file',''),
			(1023,'yal','Arts & Letters click art',''),
			(1024,'z','UNIX Compress Archive',''),
			(1026,'zip','PZIP (PC ZIP) compressed archive file',''),
			(1027,'zoo','Zoo Archive',''),
			(1028,'zts','Z-Term Script',''),
			(1031,'aac','Advanced Audio Coding (Mac)',''),
			(1032,'abc','A Program\'s Master Index',''),
			(1044,'ajax','Asynchronous JavaScript And XML',''),
			(1045,'alb','JASC Image Commander',''),
			(1047,'ann','Windows help annotations',''),
			(1052,'asa','ASP info',''),
			(1053,'asax','ASP.NET file',''),
			(1063,'att','AT&T Group IV fax',''),
			(1066,'axd','Actrix drawing',''),
			(1072,'bm1','Apogee BioMenace file',''),
			(1073,'bml','Bookmark library (SyncURL)',''),
			(1075,'bmk','Windows help bookmarks',''),
			(1076,'bms','BlackMagic script',''),
			(1077,'brk','Brooktrout fax',''),
			(1078,'bs1','Apogee Blake Stone file',''),
			(1086,'ccb','Visual Basic animated button',''),
			(1095,'ch3','Harvard Graphics chart',''),
			(1100,'ck?','iD/Apogee Commander Keen ?',''),
			(1108,'cmx','Corel clip art',''),
			(1110,'cnt','Windows help contents',''),
			(1112,'cpd','Fax cover document',''),
			(1114,'cpi','DOS code page',''),
			(1129,'cv5','Canvas 5 vector/bitmap',''),
			(1137,'dcm','DICOM medical image',''),
			(1147,'disco','Publishing and Discovering Web Services',''),
			(1148,'dlg','Dialogue script',''),
			(1151,'docx','Document (MS Word 2008)',''),
			(1170,'fh3','Freehand 3',''),
			(1176,'fmv','FrameMaker raster & vector graphics',''),
			(1188,'ftg','Windows help file links',''),
			(1191,'gal','Corel Multimedia Manager album',''),
			(1196,'gfi','Genigraphics presentation link',''),
			(1197,'gfx','Genigraphics presentation link',''),
			(1198,'gid','Windows help global index',''),
			(1200,'gim','Genigraphics presentation link',''),
			(1201,'gix','Genigraphics presentation link',''),
			(1202,'gna','Genigraphics presentation link',''),
			(1203,'gnx','Genigraphics presentation link',''),
			(1204,'gp4','CALS Group IV - ITU Group IV',''),
			(1208,'gwx','Genigraphics presentation link',''),
			(1209,'gwz','Genigraphics presentation link',''),
			(1211,'gx2','Show Partner bitmap',''),
			(1229,'idc','Internet Database Connector',''),
			(1245,'jt','JT Fax',''),
			(1249,'kye','Kye game program data',''),
			(1254,'lit','Microsoft Reader file',''),
			(1256,'lnk','Windows 9x/NT shortcut',''),
			(1257,'lqt','Liquid Audio',''),
			(1258,'lsn','Topic list (CDE)',''),
			(1260,'lv','LaserView Group IV',''),
			(1268,'mb1','Apogee Moster Bash file',''),
			(1273,'mel','Maya script',''),
			(1288,'mp2v','MPEG file',''),
			(1300,'m3d','Corel Motion 3-D',''),
			(1301,'nap','NAPLPS format',''),
			(1302,'nav','Eudora script file',''),
			(1307,'ntz','InVircible antivirus blueprint',''),
			(1308,'o','UNIX machine language',''),
			(1313,'obz','Microsoft Office wizard',''),
			(1314,'oeb','Open eBook publication',''),
			(1317,'opf','Open package file',''),
			(1318,'org','Organizer file',''),
			(1322,'ozm','Sharp Organizer memo bank',''),
			(1323,'ozp','Sharp Organizer telephone bank',''),
			(1344,'pdw','HiJaak vector graphics',''),
			(1356,'pin','Epic Pinball data',''),
			(1362,'pmx','PageMaker document (x=ver.)',''),
			(1363,'pml','PageMaker library',''),
			(1366,'pp4','Picture Publisher 4',''),
			(1373,'pre','Lotus Freelance',''),
			(1382,'psr','Powersoft report',''),
			(1383,'ptx','PageMaker template (x=ver.)',''),
			(1386,'puz','Across puzzle',''),
			(1387,'pvk','Private Key',''),
			(1388,'pwl','Windows password list',''),
			(1402,'res','Programming resource',''),
			(1408,'rix','RIX virtual screen',''),
			(1411,'rlf','RichLink compiled format',''),
			(1414,'rmm','Real Media file',''),
			(1420,'rv','Real Video file',''),
			(1422,'sat','ACIS 3-D model',''),
			(1426,'sc?','ColoRIX raster (?=res.)',''),
			(1427,'sc2','Microsoft Schedule+ 7',''),
			(1428,'scd','Microsoft Schedule+ 7',''),
			(1429,'sch','Microsoft Schedule+ 1',''),
			(1439,'sdr','SmartDraw vector graphics',''),
			(1440,'sdt','SmartDraw template',''),
			(1446,'shb','Corel Show',''),
			(1447,'shg','Segmented HyperGraphic (imagemap Windows help)',''),
			(1448,'shs','OLE2 package (can contain executables)',''),
			(1449,'shw','Corel Show',''),
			(1459,'sy3','Harvard Graphics symbol',''),
			(1460,'syl','SYLK format (spreadsheets)',''),
			(1473,'torrent','bittorrent',''),
			(1483,'vcf','vCard file',''),
			(1487,'vox','Voxware compressed audio',''),
			(1493,'wax','WMA metafile (location of WMA file)',''),
			(1494,'wb?','Quattro Pro spreadsheet (?=version)',''),
			(1496,'wbt','WinBatch file',''),
			(1497,'wcm','WordPerfect macro',''),
			(1499,'wgp','Wild Board games data',''),
			(1501,'wif','Wavelet image',''),
			(1505,'wk3','Lotus ver. 3.x & Windows',''),
			(1508,'wma','Windows Media Audio (ASF file)',''),
			(1510,'wmv','Windows Media video (ASF file)',''),
			(1513,'wp?','WordPerfect document (?=version)',''),
			(1517,'wq?','Quattro Pro for DOS (?=version)',''),
			(1521,'wrl','VRML page',''),
			(1522,'ws?','WordStar for Windows (?=version)',''),
			(1523,'wsd','WordStar 2000 document',''),
			(1524,'wsh','Windows Scrip Host properties',''),
			(1526,'wvx','WMV metafile (location of WMV file)',''),
			(1530,'xla','Excel add-in',''),
			(1531,'xlb','Excel toolbar',''),
			(1533,'xld','Excel dialogue',''),
			(1539,'xml','XML file',''),
			(1541,'xsd','XML schema',''),
			(1543,'xy?','XyWrite document (?=version)',''),
			(1544,'xyw','XyWrite for Windows document','');");	

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
	if (isset($_SERVER['SCRIPT_NAME']) && !in_array(basename($_SERVER['SCRIPT_NAME']), $files)) {
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
		'message' => __('Need to provide an absolute Path to Monitor.', 'disku'),
		'type' => 'error');
	$messages[305] = array(
		'message' => __('Duplicated Path. The Path of the Poller has existed already.', 'disku'),
		'type' => 'error');
	$messages[306] = array(
		'message' => __('Need to provide a Path Name.', 'disku'),
		'type' => 'error');
	$messages[307] = array(
		'message' => __('The disk usage poller is not available.  Install the rtm-client.', 'disku'),
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
