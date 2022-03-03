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

function upgrade_to_2_0() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../lib/import.php');

	execute_sql("Adding new index", "ALTER TABLE grid_hosts_resources ADD INDEX `resource_name_host` (`resource_name`, `host`);");
	execute_sql("Add new columns in grid_clusters for grid control", "ALTER TABLE `grid_clusters`
		ADD COLUMN `username` VARCHAR(255) NOT NULL default '' AFTER `lsf_master_hostname`,
		ADD COLUMN `communication` VARCHAR(10) NOT NULL default '' AFTER `username`,
		ADD COLUMN `privatekey_path` VARCHAR(255) NOT NULL default '' AFTER `communication`,
		ADD COLUMN `LSF_TOP` VARCHAR(255) NOT NULL default '' AFTER `privatekey_path`,
		ADD COLUMN `add_frequency` int(10) unsigned NOT NULL default '0' AFTER `LSF_TOP`");

	execute_sql("Add new column in grid_hosts for black host detection", "ALTER TABLE `grid_hosts`
		ADD COLUMN `exceptional` TINYINT(1) unsigned NOT NULL default '0' AFTER `present`");

	// Syslog plugin tables
	create_table("syslog", "CREATE TABLE syslog (
		facility varchar(10) default NULL,
		priority varchar(10) default NULL,
		`date` date default NULL,
		`time` time default NULL,
		host varchar(128) default NULL,
		message text,
		seq int(10) unsigned NOT NULL auto_increment,
		PRIMARY KEY  (seq),
		KEY `date` (`date`),
		KEY `time` (`time`),
		KEY host (host),
		KEY `priority` (`priority`),
		KEY `facility` (`facility`)
	) ENGINE=InnoDB;");
	create_table("syslog_alert", "CREATE TABLE syslog_alert (
		id int(10) NOT NULL auto_increment,
		name varchar(255) NOT NULL default '',
		`type` varchar(16) NOT NULL default '',
		message text NOT NULL,
		`user` varchar(32) NOT NULL default '',
		`date` int(16) NOT NULL default '0',
		email text NOT NULL,
		notes text NOT NULL,
		PRIMARY KEY  (id)
	) ENGINE=InnoDB;");
	create_table("syslog_incoming", "CREATE TABLE syslog_incoming (
		facility varchar(10) default NULL,
		priority varchar(10) default NULL,
		`date` date default NULL,
		`time` time default NULL,
		host varchar(128) default NULL,
		message text,
		seq int(10) unsigned NOT NULL auto_increment,
		`status` tinyint(4) NOT NULL default '0',
		PRIMARY KEY  (seq),
		KEY `status` (`status`)
	) ENGINE=InnoDB;");
	create_table("syslog_remove", "CREATE TABLE syslog_remove (
		id int(10) NOT NULL auto_increment,
		name varchar(255) NOT NULL default '',
		`type` varchar(16) NOT NULL default '',
		message text NOT NULL,
		`user` varchar(32) NOT NULL default '',
		`date` int(16) NOT NULL default '0',
		notes text NOT NULL,
		PRIMARY KEY  (id)
	) ENGINE=InnoDB;");
	create_table("syslog_reports", "CREATE TABLE syslog_reports (
		id int(10) NOT NULL auto_increment,
		name varchar(255) NOT NULL default '',
		`type` varchar(16) NOT NULL default '',
		timespan int(16) NOT NULL default '0',
		lastsent int(16) NOT NULL default '0',
		hour int(6) NOT NULL default '0',
		min int(6) NOT NULL default '0',
		message text NOT NULL,
		`user` varchar(32) NOT NULL default '',
		`date` int(16) NOT NULL default '0',
		email text NOT NULL,
		notes text NOT NULL,
		PRIMARY KEY  (id)
	) ENGINE=InnoDB;");
	execute_sql("Add user auth realm for syslog plugin", "INSERT INTO `user_auth_realm` VALUES
		(37,1),
		(38,1);");

	// Thold 0.4.0.1
	create_table("thold_data", "CREATE TABLE `thold_data` (
		`id` int(11) NOT NULL auto_increment,
		`name` varchar(100) NOT NULL default '',
		`name_cache` varchar(100) NOT NULL default '',
		`rra_id` int(11) NOT NULL default '0',
		`data_id` int(11) NOT NULL default '0',
		`graph_id` int(11) NOT NULL default '0',
		`graph_template` int(11) NOT NULL default '0',
		`data_template` int(11) NOT NULL default '0',
		`thold_hi` varchar(100) default NULL,
		`thold_low` varchar(100) default NULL,
		`thold_fail_trigger` int(10) unsigned default NULL,
		`thold_fail_count` int(11) NOT NULL default '0',
		`thold_alert` int(1) NOT NULL default '0',
		`thold_enabled` enum('on','off') NOT NULL default 'on',
		`thold_type` int(3) NOT NULL default '0',
		`time_hi` varchar(100) NOT NULL default '',
		`time_low` varchar(100) NOT NULL default '',
		`time_fail_trigger` int(12) NOT NULL default '1',
		`time_fail_length` int(12) NOT NULL default '1',
		`bl_enabled` enum('on','off') NOT NULL default 'off',
		`bl_ref_time` int(50) unsigned default NULL,
		`bl_ref_time_range` int(10) unsigned default NULL,
		`bl_pct_down` int(10) unsigned default NULL,
		`bl_pct_up` int(10) unsigned default NULL,
		`bl_fail_trigger` int(10) unsigned default NULL,
		`bl_fail_count` int(11) unsigned default NULL,
		`bl_alert` int(2) NOT NULL default '0',
		`lastread` varchar(100) default NULL,
		`oldvalue` varchar(100) NOT NULL default '',
		`repeat_alert` int(10) unsigned default NULL,
		`notify_extra` varchar(255) default NULL,
		`data_type` int(3) NOT NULL default '0',
		`host_id` int(10) default NULL,
		`syslog_priority` int(2) default NULL,
		`syslog_facility` int(2) default NULL,
		`syslog_enabled` char(3) NOT NULL default '',
		`cdef` int(11) NOT NULL default '0',
		`percent_ds` varchar(64) NOT NULL default '0',
		`template` int(11) NOT NULL default '0',
		`template_enabled` char(3) NOT NULL default '',
		`tcheck` int(1) NOT NULL default '0',
		`exempt` char(3) NOT NULL default 'off',
		`acknowledgement` char(3) NOT NULL default 'off',
		`restored_alert` char(3) NOT NULL default 'off',
		`reset_ack` char(3) NOT NULL default 'off',
		`email_body` text NOT NULL default '',
		`trigger_cmd_high` varchar(255) NOT NULL default '',
		`trigger_cmd_low` varchar(255) NOT NULL default '',
		`trigger_cmd_norm` varchar(255) NOT NULL default '',
		`host_action_high` char(10) NOT NULL default '',
		`host_action_low` char(10) NOT NULL default '',
		`job_action_high` char(10) NOT NULL default '',
		`job_signal_high` char(10) NOT NULL default '',
		`job_target_high` varchar(100) NOT NULL default '',
		`job_action_low` char(10) NOT NULL default '',
		`job_signal_low` char(10) NOT NULL default '',
		`job_target_low` varchar(100) NOT NULL default '',
		PRIMARY KEY  (`id`),
		KEY `host_id` (`host_id`),
		KEY `rra_id` (`rra_id`),
		KEY `data_id` (`data_id`),
		KEY `graph_id` (`graph_id`),
		KEY `graph_template` (`graph_template`),
		KEY `data_template` (`data_template`),
		KEY `template` (`template`),
		KEY `template_enabled` (`template_enabled`),
		KEY `thold_enabled` (`thold_enabled`)
		) ENGINE=InnoDB;");

	create_table("thold_template", "CREATE TABLE thold_template (
		id int(11) NOT NULL auto_increment,
		name varchar(100) NOT NULL default '',
		data_template_id int(32) NOT NULL default '0',
		data_template_name varchar(100) NOT NULL default '',
		data_source_id int(10) NOT NULL default '0',
		data_source_name varchar(100) NOT NULL default '',
		data_source_friendly varchar(100) NOT NULL default '',
		time_hi varchar(100) default NULL,
		time_low varchar(100) default NULL,
		time_fail_trigger int(12) default '1',
		time_fail_length int(12) default '1',
		thold_hi varchar(100) default NULL,
		thold_low varchar(100) default NULL,
		thold_fail_trigger int(10) NOT NULL default '1',
		thold_enabled enum('on','off') NOT NULL default 'on',
		thold_type int(3) NOT NULL default '0',
		bl_enabled enum('on','off') NOT NULL default 'off',
		bl_ref_time int(50) default NULL,
		bl_ref_time_range int(10) default NULL,
		bl_pct_down int(10) default NULL,
		bl_pct_up int(10) default NULL,
		bl_fail_trigger int(10) default NULL,
		bl_fail_count int(11) default NULL,
		bl_alert int(2) default NULL,
		repeat_alert int(10) NOT NULL default '12',
		notify_default enum('on', 'off') default NULL,
		notify_extra varchar(255) NOT NULL default '',
		data_type int(3) NOT NULL default '0',
		cdef int(11) NOT NULL default '0',
		percent_ds varchar(64) NOT NULL,
		exempt char(3) NOT NULL default 'off',
		restored_alert char(3) NOT NULL default 'off',
		`reset_ack` char(3) NOT NULL default 'off',
		`email_body` text NOT NULL default '',
		`syslog_priority` int(2) default NULL,
		`syslog_facility` int(2) default NULL,
		`syslog_enabled` char(3) NOT NULL default '',
		PRIMARY KEY  (id),
		KEY `data_template_id` (`data_template_id`),
		KEY `data_source_id` (`data_source_id`)
		) ENGINE=InnoDB COMMENT='Table of thresholds defaults for graphs';");

	create_table("plugin_thold_template_contact", "CREATE TABLE plugin_thold_template_contact (
		template_id int(12) NOT NULL,
		contact_id int(12) NOT NULL,
		KEY template_id (template_id),
		KEY contact_id (contact_id)
	) ENGINE=InnoDB COMMENT='Table of Tholds Template Contacts';");

	create_table("plugin_thold_threshold_contact", "CREATE TABLE plugin_thold_threshold_contact (
		thold_id int(12) NOT NULL,
		contact_id int(12) NOT NULL,
		KEY thold_id (thold_id),
		KEY contact_id (contact_id)
	) ENGINE=InnoDB COMMENT='Table of Tholds Threshold Contacts';");

	create_table("plugin_thold_contacts", "CREATE TABLE plugin_thold_contacts (
		`id` int(12) NOT NULL auto_increment,
		`user_id` int(12) NOT NULL,
		`type` varchar(32) NOT NULL,
		`data` text NOT NULL,
		PRIMARY KEY  (`id`),
		KEY `type` (`type`),
		KEY `user_id` (`user_id`)
	) ENGINE=InnoDB;");
	create_table("plugin_thold_log", "CREATE TABLE `plugin_thold_log` (
		`id` int(12) NOT NULL auto_increment,
		`time` int(24) NOT NULL,
		`host_id` int(10) NOT NULL,
		`graph_id` int(10) NOT NULL,
		`threshold_id` int(10) NOT NULL,
		`threshold_value` varchar(64) NOT NULL,
		`current` varchar(64) NOT NULL,
		`status` int(5) NOT NULL,
		`type` int(5) NOT NULL,
		`description` varchar(255) NOT NULL,
		PRIMARY KEY  (`id`),
		KEY `time` (`time`),
		KEY `host_id` (`host_id`),
		KEY `graph_id` (`graph_id`),
		KEY `threshold_id` (`threshold_id`),
		KEY `status` (`status`),
		KEY `type` (`type`)
	) ENGINE=InnoDB COMMENT='Table of All Threshold Breaches';");
	execute_sql("Adding user realm", "REPLACE INTO user_auth_realm VALUES (18, 1);");
	execute_sql("Adding user realm", "REPLACE INTO user_auth_realm VALUES (19, 1);");
	execute_sql("Adding plugin config", "INSERT INTO `plugin_config` (directory, name, status, author, webpage, version) VALUES
				('thold', 'Thresholds', 1, 'Jimmy Conner', 'http://cactiusers.org', '0.4.0.1');");
	execute_sql("Adding plugin hooks", "INSERT INTO `plugin_hooks` (name, hook, function, file, status) VALUES
		('thold', 'top_header_tabs', 'thold_show_tab', 'includes/tab.php', 1),
		('thold', 'top_graph_header_tabs', 'thold_show_tab', 'includes/tab.php',1),
		('thold', 'config_arrays', 'thold_config_arrays', 'includes/settings.php',1),
		('thold', 'config_settings', 'thold_config_settings', 'includes/settings.php',1),
		('thold', 'draw_navigation_text', 'thold_draw_navigation_text', 'includes/settings.php',1),
		('thold', 'data_sources_table', 'thold_data_sources_table', 'setup.php',1),
		('thold', 'graphs_new_top_links', 'thold_graphs_new', 'setup.php',1),
		('thold', 'api_device_save', 'thold_api_device_save', 'setup.php',1),
		('thold', 'update_host_status', 'thold_update_host_status', 'includes/polling.php',1),
		('thold', 'poller_output', 'thold_poller_output', 'includes/polling.php',1),
		('thold', 'device_action_array', 'thold_device_action_array', 'setup.php',1),
		('thold', 'device_action_execute', 'thold_device_action_execute', 'setup.php',1),
		('thold', 'device_action_prepare', 'thold_device_action_prepare', 'setup.php',1),
		('thold', 'user_admin_setup_sql_save', 'thold_user_admin_setup_sql_save', 'setup.php',1),
		('thold', 'poller_bottom', 'thold_poller_bottom', 'includes/polling.php',1),
		('thold', 'user_admin_edit', 'thold_user_admin_edit', 'setup.php',1),
		('thold', 'rrd_graph_graph_options', 'thold_rrd_graph_graph_options', 'setup.php',1),
		('thold', 'graph_buttons', 'thold_graph_button', 'setup.php',1),
		('thold', 'data_source_action_array', 'thold_data_source_action_array', 'setup.php',1),
		('thold', 'data_source_action_prepare', 'thold_data_source_action_prepare', 'setup.php',1),
		('thold', 'data_source_action_execute', 'thold_data_source_action_execute', 'setup.php',1),
		('thold', 'graphs_action_array', 'thold_graphs_action_array', 'setup.php',1),
		('thold', 'graphs_action_prepare', 'thold_graphs_action_prepare', 'setup.php',1),
		('thold', 'graphs_action_execute', 'thold_graphs_action_execute', 'setup.php',1),
		('thold', 'update_data_source_title_cache', 'thold_update_data_source_title_cache', 'setup.php',1);");
	execute_sql("Adding plugin realms", "INSERT INTO `plugin_realms` (plugin, file, display) VALUES
		('thold', 'thold_add.php,thold.php,thold.php', 'Configure Thresholds'),
		('thold', 'thold_templates.php', 'Configure Threshold Templates'),
		('thold', 'thold_graph.php,graph_thold.php,thold_view_failures.php,thold_view_normal.php,thold_view_recover.php,thold_view_recent.php,thold_view_host.php,thold_display.php', 'View Thresholds');");
	execute_sql("Adding user realm", "INSERT INTO `user_auth_realm` VALUES
		(102,1),
		(103,1),
		(104,1);");
	// end Thold 0.4.0.1
	// Thold Templates
	$thold_templates = array(
		"1" => array (
			'value' => 'Number of host exceeds /tmp percentage used',
			'name' => 'cacti_graph_template_alert_-_host_with_tmp_exceed_capacity.xml'
		),
		"2" => array (
			'value' => 'Number of Hosts exceed /var/tmp percentage usage',
			'name' => 'cacti_graph_template_alert_-_host_with_vartmp_exceed_capacity.xml'
		),
		"3" => array (
			'value' => 'return 1 when free slots less than limit, 0 when equals or more than limit',
			'name' => 'cacti_graph_template_alert_-_hostgroup_with_low_number_of_free_slots.xml'
		),
		"4" => array (
			'value' => 'Number of Hosts exceed maximum run queue length indicator',
			'name' => 'cacti_graph_template_alert_-_hosts_with_effective_r15m_x.xml'
		),
		"5" => array (
			'value' => 'Number of Hosts exceed mem usage',
			'name' => 'cacti_graph_template_alert_-_hosts_with_used_mem_.xml'
		),
		"6" => array (
			'value' => 'Number of hosts exceed swp usage',
			'name' => 'cacti_graph_template_alert_-_hosts_with_used_swp_.xml'
		),
		"7" => array (
			'value' => 'Number of Unavailable Hosts',
			'name' => 'cacti_graph_template_alert_-_host_with_x_status.xml'
		),
		"8" => array (
			'value' => 'Number of jobs below minimum efficiency',
			'name' => 'cacti_graph_template_alert_-_idle_jobs.xml'
		),
		"9" => array (
			'value' => 'Number of pending jobs exceed maximum time',
			'name' => 'cacti_graph_template_alert_-_jobs_pending_for_x_seconds.xml'
		)
	);
	$counter = 1;
	foreach($thold_templates as $tholdtemplates) {

		$results = do_import($config["base_path"]."/plugins/thold/extras/".$tholdtemplates['name']);
		do_insert($results, $tholdtemplates['value'], $counter);
		$counter += 1;
	}
	// end Thold Templates

	// RTMSSH
	 execute_sql("Adding user realm", "INSERT INTO `user_auth_realm` VALUES
			(908,1);");
	// End RTMSSH

}
