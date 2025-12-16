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

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading grid to v9.1 ...', true, 'UPGRADE');

	add_index("data_template_data", "local_data_template_data_id",
		"ADD INDEX `local_data_template_data_id`(`local_data_template_data_id`);");

	add_index("graph_templates_graph", "local_graph_template_graph_id",
		"ADD INDEX `local_graph_template_graph_id`(`local_graph_template_graph_id`);");

	add_index("graph_templates_item", "local_graph_template_item_id",
		"ADD INDEX `local_graph_template_item_id`(`local_graph_template_item_id`);");

	create_table("grid_elim_templates", "CREATE TABLE grid_elim_templates (
  		id mediumint(8) unsigned NOT NULL auto_increment,
  		hash char(32) NOT NULL default '',
  		name char(255) NOT NULL default '',
  		PRIMARY KEY  (id),
  		KEY name (name(191))
		) ENGINE=InnoDB COMMENT='Contains each ELIM graph template name.';");

	create_table("grid_elim_templates_graph", "CREATE TABLE grid_elim_templates_graph (
  		id mediumint(8) unsigned NOT NULL auto_increment,
  		local_graph_template_graph_id mediumint(8) unsigned NOT NULL default '0',
  		local_graph_id mediumint(8) unsigned NOT NULL default '0',
  		graph_template_id mediumint(8) unsigned NOT NULL default '0',
  		t_image_format_id char(2) default '0',
  		image_format_id tinyint(1) NOT NULL default '0',
  		t_title char(2) default '0',
  		title varchar(255) NOT NULL default '',
  		title_cache varchar(255) NOT NULL default '',
  		t_height char(2) default '0',
  		height mediumint(8) NOT NULL default '0',
  		t_width char(2) default '0',
  		width mediumint(8) NOT NULL default '0',
  		t_upper_limit char(2) default '0',
  		upper_limit varchar(20) NOT NULL default '0',
  		t_lower_limit char(2) default '0',
  		lower_limit varchar(20) NOT NULL default '0',
  		t_vertical_label char(2) default '0',
  		vertical_label varchar(200) default NULL,
  		t_slope_mode char(2) default '0',
  		slope_mode char(2) default 'on',
  		t_auto_scale char(2) default '0',
  		auto_scale char(2) default NULL,
  		t_auto_scale_opts char(2) default '0',
  		auto_scale_opts tinyint(1) NOT NULL default '0',
  		t_auto_scale_log char(2) default '0',
  		auto_scale_log char(2) default NULL,
  		t_scale_log_units char(2) default '0',
  		scale_log_units char(2) default NULL,
  		t_auto_scale_rigid char(2) default '0',
  		auto_scale_rigid char(2) default NULL,
  		t_auto_padding char(2) default '0',
  		auto_padding char(2) default NULL,
  		t_base_value char(2) default '0',
  		base_value mediumint(8) NOT NULL default '0',
  		t_grouping char(2) default '0',
  		grouping char(2) NOT NULL default '',
  		t_export char(2) default '0',
  		export char(2) default NULL,
  		t_unit_value char(2) default '0',
  		unit_value varchar(20) default NULL,
  		t_unit_exponent_value char(2) default '0',
  		unit_exponent_value varchar(5) NOT NULL default '',
  		PRIMARY KEY  (id),
  		KEY local_graph_id (local_graph_id),
  		KEY graph_template_id (graph_template_id),
  		KEY title_cache (title_cache(191))
		) ENGINE=InnoDB COMMENT='Stores the actual ELIM graph data.';");

	create_table("grid_elim_templates_item", "CREATE TABLE grid_elim_templates_item (
  		id int(12) unsigned NOT NULL auto_increment,
  		hash varchar(32) NOT NULL default '',
  		local_graph_template_item_id int(12) unsigned NOT NULL default '0',
  		local_graph_id mediumint(8) unsigned NOT NULL default '0',
  		graph_template_id mediumint(8) unsigned NOT NULL default '0',
  		task_item_id mediumint(8) unsigned NOT NULL default '0',
  		color_id mediumint(8) unsigned NOT NULL default '0',
  		alpha char(2) default 'FF',
  		graph_type_id tinyint(3) NOT NULL default '0',
  		cdef_id mediumint(8) unsigned NOT NULL default '0',
  		consolidation_function_id tinyint(2) NOT NULL default '0',
  		text_format varchar(255) default NULL,
  		value varchar(255) default NULL,
  		hard_return char(2) default NULL,
  		gprint_id mediumint(8) unsigned NOT NULL default '0',
  		sequence mediumint(8) unsigned NOT NULL default '0',
  		resource_name varchar(40) default '',
  		resource_option tinyint(3) NOT NULL default '0',
  		PRIMARY KEY  (id),
  		KEY graph_template_id (graph_template_id),
  		KEY local_graph_id (local_graph_id),
  		KEY task_item_id (task_item_id)
		) ENGINE=InnoDB COMMENT='Stores the actual ELIM graph item data.';");

	create_table("grid_elim_template_instances", "CREATE TABLE grid_elim_template_instances (
  		id mediumint(8) unsigned NOT NULL auto_increment,
  		name char(255) NOT NULL default '',
  		grid_elim_template_id mediumint(8) unsigned NOT NULL default '0',
  		clusterid int(10) unsigned NOT NULL default '0',
  		hosttype_option tinyint(3) unsigned  NOT NULL default '0',
  		hosttype_value varchar(40) default NULL,
  		PRIMARY KEY  (id)
		) ENGINE=InnoDB COMMENT='Stores the instances of  ELIM graph template.';");

	create_table("grid_elim_instance_graphs", "CREATE TABLE grid_elim_instance_graphs (
  		grid_elim_template_instance_id mediumint(8) unsigned NOT NULL,
  		local_graph_id mediumint(8) unsigned NOT NULL,
  		PRIMARY KEY  (grid_elim_template_instance_id,local_graph_id)
		) ENGINE=InnoDB COMMENT='Stores the Map of ELIM template instance to the cacti graph local.';");

	create_table("grid_elim_templates_graph_map", "CREATE TABLE grid_elim_templates_graph_map (
  		local_graph_id mediumint(8) unsigned NOT NULL,
  		graph_templates_graph_id mediumint(8) unsigned NOT NULL,
  		grid_elim_template_id mediumint(8) unsigned NOT NULL,
  		grid_elim_templates_graph_id mediumint(8) unsigned NOT NULL,
  		PRIMARY KEY  (local_graph_id,graph_templates_graph_id)
		) ENGINE=InnoDB COMMENT='Stores the Map of ELIM grid_elim_templates_graph to cacti graph_templates_graph.';");

	create_table("grid_elim_templates_item_map", "CREATE TABLE grid_elim_templates_item_map (
  		local_graph_id mediumint(8) unsigned NOT NULL,
  		graph_templates_item_id mediumint(8) unsigned NOT NULL,
  		grid_elim_template_id mediumint(8) unsigned NOT NULL,
  		grid_elim_templates_item_id mediumint(8) unsigned NOT NULL,
  		PRIMARY KEY  (local_graph_id,graph_templates_item_id)
		) ENGINE=InnoDB COMMENT='Stores the Map of ELIM grid_elim_templates_item to cacti graph_templates_item.';");

	create_table("grid_host_threshold", "CREATE TABLE `grid_host_threshold`
		(`id` mediumint(8) unsigned NOT NULL auto_increment,
		`clusterid` int(10) unsigned NOT NULL default '0',
		`hostname`  varchar(64) NOT NULL default '',
		`resource_name` varchar(20) NOT NULL default '',
		`loadSched` double NOT NULL default '0',
		`loadStop` int NOT NULL default '0',
		`busySched` int NOT NULL default '0',
		`busyStop` int NOT NULL default '0',
		`present` int NOT NULL default '0',
		PRIMARY KEY (`clusterid`,`hostname`,`resource_name`),
		KEY`id`(`id`),
		KEY `clusterid`(`clusterid`),
		KEY `hostname` (`hostname`),
		KEY `resource_name` (`resource_name`)) ENGINE=InnoDB;");

	create_table("grid_hosts_alarm", "CREATE TABLE `grid_hosts_alarm`
		(`type_id` bigint NOT NULL default '0' ,
		`name` varchar(100) NOT NULL default '',
		`hostname`  varchar(64) NOT NULL default '',
		`clusterid`  int(10) unsigned NOT NULL default '0',
		`type` int NOT NULL default '0',
		`message` varchar(1024) NOT NULL,
		`acknowledgement` char(3) NOT NULL default 'off',
		`alert_time` timestamp NOT NULL default CURRENT_TIMESTAMP,
		`present` int NOT NULL default '0',
		PRIMARY KEY  (`type`,`type_id`,`clusterid`,`hostname`),
		KEY `hostname` (`hostname`),
		KEY`clusterid`(`clusterid`),
		KEY `type`(`type`)) ENGINE=InnoDB;");

	execute_sql("Add user auth realm for Nectar Reports Admin",
		"REPLACE INTO `user_auth_realm` VALUES (115,1);");

	execute_sql("Add user auth realm for Nectar Reports User",
		"REPLACE INTO `user_auth_realm` VALUES (119,1);");

	cacti_log('NOTE: Create Table for SLA Feature.', true, 'UPGRADE');

	create_table("grid_guarantee_pool", "CREATE TABLE  `grid_guarantee_pool` (
  		`clusterid` int(10) unsigned NOT NULL default '0',
  		`name` varchar(60) NOT NULL default '',
  		`poolType` varchar(32) NOT NULL default '',
  		`rsrcName` varchar(128) default NULL,
  		`status` varchar(32) NOT NULL,
  		`res_select` varchar(128) NOT NULL,
  		`slots_per_host` int(10) unsigned NOT NULL default '0',
  		`policies` tinyint(3) unsigned NOT NULL default '0',
  		`loan_duration` int(10) unsigned NOT NULL default '0',
  		`retain` int(10) unsigned NOT NULL default '0',
  		`total` int(10)  NOT NULL default '0',
  		`free` int(10)  NOT NULL default '0',
  		`guar_config` int(10)  NOT NULL default '0',
  		`guar_used` int(10)  NOT NULL default '0',
  		`present` tinyint(3) unsigned NOT NULL default '0',
  		PRIMARY KEY  (`clusterid`,`name`)
		) ENGINE=MEMORY COMMENT='Stores Configuration of Guarantee Resource Pools';");

	create_table("grid_guarantee_pool_distribution", "CREATE TABLE  `grid_guarantee_pool_distribution` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`consumer` varchar(60) NOT NULL,
  		`alloc` int(10) unsigned NOT NULL,
  		`alloc_type` int(10) unsigned NOT NULL,
  		`guarantee_config` int(10) unsigned NOT NULL,
  		`guarantee_used` int(10) unsigned NOT NULL,
  		`total_used` int(10) unsigned NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`,`consumer`)
		) ENGINE=MEMORY COMMENT='Stores Distribution Consumer Information for Guarantee Pool';");

	create_table("grid_guarantee_pool_hosts", "CREATE TABLE  `grid_guarantee_pool_hosts` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`host` varchar(64) NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`,`host`),
  		KEY `host` (`host`)
		) ENGINE=MEMORY COMMENT='Stores Normalized Host Membership for Guarantee Pool';");

	create_table("grid_guarantee_pool_loan_queues", "CREATE TABLE  `grid_guarantee_pool_loan_queues` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`queue` varchar(60) NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`,`queue`),
  		KEY ` queue` (`queue`)
		) ENGINE=MEMORY COMMENT='Stores Loan Policies for Guarantee Pool';");

	create_table("grid_service_class", "CREATE TABLE  `grid_service_class` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`description` varchar(255) NOT NULL,
  		`consumer` varchar(255) NOT NULL,
  		`priority` int(10) unsigned NOT NULL,
  		`control_action` varchar(128) NOT NULL,
  		`auto_attach` tinyint(3) unsigned NOT NULL,
  		`ego_res_req` varchar(255) NOT NULL,
  		`max_host_idle_time` int(10) unsigned NOT NULL,
  		`throughput` double NOT NULL default '0',
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`)
		) ENGINE=MEMORY COMMENT='Stores Service Class Definitions';");

	create_table("grid_service_class_goals", "CREATE TABLE  `grid_service_class_goals` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`goal_seq` int(10) default 0,
  		`goalType` varchar(20) default NULL,
  		`goal_window` varchar(1024) default NULL,
  		`status` varchar(64) default NULL,
  		`min_config` int(10) unsigned default NULL,
  		`goal_config` int(10) unsigned default NULL,
  		`actual` int(10) unsigned default NULL,
  		`optimum` int(10) unsigned default NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`, `goal_seq`)
		) ENGINE=MEMORY COMMENT='Stores Service Class Goals';");

	create_table("grid_service_class_access_control", "CREATE TABLE  `grid_service_class_access_control` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`acl_type` int(10) unsigned NOT NULL,
  		`acl_member` varchar(60) NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`,`acl_type`, `acl_member`),
  		KEY `acl_member` (`acl_member`)
		) ENGINE=MEMORY COMMENT='Stores Access Control Information for Service Class';");

	create_table("grid_service_class_groups", "CREATE TABLE  `grid_service_class_groups` (
  		`clusterid` int(10) unsigned NOT NULL,
  		`name` varchar(60) NOT NULL,
  		`user_or_group` varchar(40) NOT NULL,
  		`present` tinyint(3) unsigned NOT NULL,
  		PRIMARY KEY  (`clusterid`,`name`,`user_or_group`)
		) ENGINE=MEMORY COMMENT='User or User Groups Permitted to Use Service Class';");

	cacti_log('NOTE: Alter tables for DC Feature.', true, 'UPGRADE');

	$column_arr= array(
		'prov_time' => "ADD COLUMN `prov_time` int(10) unsigned NOT NULL DEFAULT '0' AFTER `unkwn_time`",
		'acJobWaitTime' => "ADD COLUMN `acJobWaitTime` int(10) unsigned NOT NULL DEFAULT '0' AFTER `prov_time`"
		);

	$index_arr= array(
		'prov_time' => "ADD INDEX `prov_time`(`prov_time`)"
		);

	add_columns_indexes("grid_jobs", $column_arr, $index_arr);
	add_columns_indexes("grid_jobs_finished", $column_arr, $index_arr);

	cacti_log('NOTE: Importing RTM templates for 9.1 ...', true, 'UPGRADE');

	$RTM_templates = array(
		"1" => array (
			'value' => 'ELIM - gpfs_bandwidth',
			'name' => 'cacti_elim_template_gpfs_bandwidth.xml'
		),
		"2" => array (
			'value' => 'ELIM - gpfs_file_operations',
			'name' => 'cacti_elim_template_gpfs_file_operations.xml'
		),
		"3" => array (
			'value' => 'GRID - Guarantee SLA Resource Usage',
			'name' => 'cacti_data_query_grid_-_guarantee_sla_resource_usage.xml'
		),
		"4" => array (
			'value' => 'GRID - Guarantee Resource Pool Usage',
			'name' => 'cacti_data_query_grid_-_guarantee_resource_pool_usage.xml'
		),
	);

	foreach($RTM_templates as $rtmtemplates) {
		if (file_exists($config["base_path"]."/templates/".$rtmtemplates['name'])) {
			cacti_log('NOTE: Importing ' . $rtmtemplates['value'], true, 'UPGRADE');
			$results = do_import($config["base_path"]."/templates/".$rtmtemplates['name']);
		}
	}

	cacti_log('NOTE: Templates Import Complete.', true, 'UPGRADE');;

	execute_sql("Add the SLA data query to host template 'Grid Summary'",
		"REPLACE INTO host_template_snmp_query
		SELECT ht.id, sq.id
		FROM host_template AS ht, snmp_query AS sq
		WHERE ht.hash='d8ff1374e732012338d9cd47b9da18d4'
		AND sq.hash='9d364ee4fd3b15ddeac9453ff046b4f2';");

	execute_sql("Add the ResPool data query to host template 'Grid Summary'",
		"REPLACE INTO host_template_snmp_query
		SELECT ht.id, sq.id
		FROM host_template AS ht, snmp_query AS sq
		WHERE ht.hash='d8ff1374e732012338d9cd47b9da18d4'
		AND sq.hash='15798e52a849f6d3738fc517eef4dd4c';");

	execute_sql("Fix Problem 209639",
		"UPDATE graph_templates_item
		SET sequence=5
		WHERE hash='b24f7d00c9d00190f0fd398debf979f7';");

	execute_sql("Fix Problem 209639",
		"UPDATE graph_templates_item
		SET sequence=6
		WHERE hash='c3b262a25f4f5b255dab0488d36b3117';");

        $gprint_id= db_fetch_cell("SELECT id
			FROM graph_templates_gprint
			WHERE hash='e9c43831e54eca8069317a2ce8c6f751'");//Normal

        $graph_templates=db_fetch_assoc("SELECT id, name
			FROM graph_templates
			WHERE hash in (
				'4f4137bf00100cf3ca070426a60647ec',
				'66887c7d08c865f607ed04ed370b1613',
				'56a192fb6464178c238600e4df6cdad9'
			)");

	if (cacti_sizeof($graph_templates)) {
		foreach($graph_templates as $graph_template) {
			execute_sql("Change GPRINT type for :". $graph_template['name'],
				"UPDATE graph_templates_item
				SET gprint_id=$gprint_id
				WHERE graph_template_id=" .$graph_template['id']);
		}
	}

	$email_exsit=db_fetch_cell("SELECT DISPLAY_NAME
		FROM grid_metadata_conf
		WHERE OBJECT_TYPE='user'
		AND (DISPLAY_NAME LIKE '%E_mail%' OR DISPLAY_NAME LIKE '%email%')");

	if (empty($email_exsit)) {
		$max_column_id = db_fetch_cell("SELECT max(cast(SUBSTR(DB_COLUMN_NAME,9) AS unsigned))
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'");

		$max_position = db_fetch_cell("SELECT max(POSITION)
			FROM grid_metadata_conf
			WHERE OBJECT_TYPE='user'");

		$max_column_id++;
		$max_position++;

		execute_sql("Add the record of E-mail to the meta config table",
			"REPLACE INTO grid_metadata_conf
			VALUES ('user','meta_col" . $max_column_id . "','General','E-mail','E-mail of the user','text',$max_position,1,0,1);");
	}

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('grid_partitions_upgrade_status', 'scheduled')");
}

function partition_tables_to_9_1(){
	return array(
		'grid_jobs_finished' => array(
			'columns' => array(
				'prov_time' => "ADD COLUMN `prov_time` int(10) unsigned NOT NULL DEFAULT '0' AFTER `unkwn_time`",
				'acJobWaitTime' => "ADD COLUMN `acJobWaitTime` int(10) unsigned NOT NULL DEFAULT '0' AFTER `prov_time`"
			),
			'indexes' => array(
				'prov_time' => "ADD INDEX `prov_time`(`prov_time`)"
			)
		)
	);
}
