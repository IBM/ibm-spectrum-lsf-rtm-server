<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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

    include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
    include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once($config['library_path'] . '/rtm_plugins.php');

	cacti_log('NOTE: Upgrading grid to v10.2.0.0 ...', true, 'UPGRADE');

	$version = '10.2';

	execute_sql("Modify table grid_clusters primary key", "ALTER TABLE grid_clusters drop primary key, add primary key(`clusterid`);");
	execute_sql("Fix export function not work because new read_config_option", "INSERT IGNORE INTO `settings` (name, value) VALUES ('grid_xport_rows', '1000'), ('grid_thold_resdown_status', '1');");

	$data = array();
    $data['columns'][] = array('name' => 'nProcs', 'unsigned' => true, 'type' => "int(10)", 'NULL' => false, 'default' => '0', 'after' => 'licClass');
    $data['columns'][] = array('name' => 'nThreads', 'unsigned' => true, 'type' => "int(10)", 'NULL' => false, 'default' => '0', 'after' => 'cores');
	$data['primary'] = array('host', 'clusterid');
	db_update_table('grid_hostinfo', $data);

	execute_sql("Modify table grid_elim_templates_graph",
		"ALTER TABLE `grid_elim_templates_graph` DROP COLUMN `t_export`, DROP COLUMN `export`;");

	$column_arr= array(
		't_alt_y_grid' => "ADD COLUMN `t_alt_y_grid` char(2) DEFAULT '' AFTER `unit_exponent_value`",
		'alt_y_grid' => "ADD COLUMN `alt_y_grid` char(2) DEFAULT NULL AFTER `t_alt_y_grid`",
		't_right_axis' => "ADD COLUMN `t_right_axis` char(2) DEFAULT '' AFTER `alt_y_grid`",
		'right_axis' => "ADD COLUMN `right_axis` varchar(20) DEFAULT NULL AFTER `t_right_axis`",
		't_right_axis_label' => "ADD COLUMN `t_right_axis_label` char(2) DEFAULT '' AFTER `right_axis`",
		'right_axis_label' => "ADD COLUMN `right_axis_label` varchar(200) DEFAULT NULL AFTER `t_right_axis_label`",
		't_right_axis_format' => "ADD COLUMN `t_right_axis_format` char(2) DEFAULT '' AFTER `right_axis_label`",
		'right_axis_format' => "ADD COLUMN `right_axis_format` mediumint(8) DEFAULT NULL AFTER `t_right_axis_format`",
		't_right_axis_formatter' => "ADD COLUMN `t_right_axis_formatter` char(2) DEFAULT '' AFTER `right_axis_format`",
		'right_axis_formatter' => "ADD COLUMN `right_axis_formatter` varchar(10) DEFAULT NULL AFTER `t_right_axis_formatter`",
		't_left_axis_formatter' => "ADD COLUMN `t_left_axis_formatter` char(2) DEFAULT '' AFTER `right_axis_formatter`",
		'left_axis_formatter' => "ADD COLUMN `left_axis_formatter` varchar(10) DEFAULT NULL AFTER `t_left_axis_formatter`",
		't_no_gridfit' => "ADD COLUMN `t_no_gridfit` char(2) DEFAULT '' AFTER `left_axis_formatter`",
		'no_gridfit' => "ADD COLUMN `no_gridfit` char(2) DEFAULT NULL AFTER `t_no_gridfit`",
		't_unit_length' => "ADD COLUMN `t_unit_length` char(2) DEFAULT '' AFTER `no_gridfit`",
		'unit_length' => "ADD COLUMN `unit_length` varchar(10) DEFAULT NULL AFTER `t_unit_length`",
		't_tab_width' => "ADD COLUMN `t_tab_width` char(2) DEFAULT '' AFTER `unit_length`",
		'tab_width' => "ADD COLUMN `tab_width` varchar(20) DEFAULT '30' AFTER `t_tab_width`",
		't_dynamic_labels' => "ADD COLUMN `t_dynamic_labels` char(2) DEFAULT '' AFTER `tab_width`",
		'dynamic_labels' => "ADD COLUMN `dynamic_labels` char(2) DEFAULT NULL AFTER `t_dynamic_labels`",
		't_force_rules_legend' => "ADD COLUMN `t_force_rules_legend` char(2) DEFAULT '' AFTER `dynamic_labels`",
		'force_rules_legend' => "ADD COLUMN `force_rules_legend` char(2) DEFAULT NULL AFTER `t_force_rules_legend`",
		't_legend_position' => "ADD COLUMN `t_legend_position` char(2) DEFAULT '' AFTER `force_rules_legend`",
		'legend_position' => "ADD COLUMN `legend_position` varchar(10) DEFAULT NULL AFTER `t_legend_position`",
		't_legend_direction' => "ADD COLUMN `t_legend_direction` char(2) DEFAULT '' AFTER `legend_position`",
		'legend_direction' => "ADD COLUMN `legend_direction` varchar(10) DEFAULT NULL AFTER `t_legend_direction`"
		);
	add_columns("grid_elim_templates_graph", $column_arr);

	$column_arr= array(
		'line_width' => "ADD COLUMN `line_width` DECIMAL(4,2) DEFAULT '0.00' AFTER `graph_type_id`",
		'dashes' => "ADD COLUMN `dashes` varchar(20) DEFAULT NULL AFTER `line_width`",
		'dash_offset' => "ADD COLUMN `dash_offset` mediumint(4) DEFAULT NULL AFTER `dashes`",
		'vdef_id' => "ADD COLUMN `vdef_id` mediumint(8) unsigned NOT NULL DEFAULT '0' AFTER `cdef_id`",
		'shift' => "ADD COLUMN `shift` char(2) DEFAULT NULL AFTER `vdef_id`",
		'textalign' => "ADD COLUMN `textalign` varchar(10) DEFAULT NULL AFTER `consolidation_function_id`"
		);
	add_columns("grid_elim_templates_item", $column_arr);

	add_column("grid_pollers", "remote", "ADD COLUMN `remote` varchar(20);");

	$rm_plugins = array('settings','boost','nectar','superlinks','rtmssh','admin','hoverhelp','logout','ptskin');
	foreach($rm_plugins as $pname){
	    api_plugin_remove_hooks ($pname);
	    api_plugin_remove_realms ($pname);
	}

	execute_sql("Fix maint immediately after upgrade when no maint before", "INSERT IGNORE INTO `settings` (name, value) VALUES ('install_complete', UNIX_TIMESTAMP());");
	execute_sql("Update RTM Online Help Locations", "REPLACE INTO `settings` VALUES ('help_loc_online_kc', 'https://www.ibm.com/support/knowledgecenter/SSZT2D_10.2.0')");
	execute_sql("Remove obsoleted RTM poller license info", "DELETE FROM `settings` WHERE name like 'lsfpoller\_%'");
	$old_character_set_connection = db_fetch_row("SHOW VARIABLES LIKE 'character_set_connection'");
	if (cacti_sizeof($old_character_set_connection)) {
		$old_character_set_connection = $old_character_set_connection['Value'];
		execute_sql("Set water mark", "SET NAMES utf8; REPLACE INTO settings value ('graph_watermark','Generated by IBM® Spectrum LSF RTM'); SET NAMES $old_character_set_connection");
	} else {
		execute_sql("Set water mark", "SET NAMES utf8; REPLACE INTO settings value ('graph_watermark','Generated by IBM® Spectrum LSF RTM');");
	}

	//grid
	plugin_rtm_migrate_realms('grid', 25, 'General LSF Data', 'grid_shared.php,grid_bjobs.php,grid_bzen.php,grid_bhosts.php,grid_bhosts_closed.php,grid_bqueues.php,grid_lsload.php,grid_lshosts.php,grid_bhpart.php,grid_busers.php,grid_jobgraph.php,grid_jobgraphzoom.php,grid_default.php,grid_ajax.php', $version);
	plugin_rtm_migrate_realms('grid', 26, 'LSF Admin Data', 'grid_dailystats.php,grid_clients.php,grid_params.php,grid_elim_graph_templates_items.php,grid_download.php', $version);
	plugin_rtm_migrate_realms('grid', 27, 'LSF Host Group Data', 'grid_bhgroups.php,grid_bjgroup.php,grid_lsgload.php,grid_bmgroup.php', $version);
	plugin_rtm_migrate_realms('grid', 28, 'LSF Advanced Options', 'grid_clusterdb.php,grid_summary.php,grid_bresourcespool.php,grid_lsf_config.php', $version);
	plugin_rtm_migrate_realms('grid', 33, 'LSF Job Array Data', 'grid_barrays.php', $version);
	plugin_rtm_migrate_realms('grid', 34, 'LSF User Group Data', 'grid_bugroup.php', $version);
	plugin_rtm_migrate_realms('grid', 35, 'LSF Project Data', 'grid_projects.php', $version);
	plugin_rtm_migrate_realms('grid', 36, 'LSF License Project Data', 'grid_license_projects.php', $version);
	plugin_rtm_migrate_realms('grid', 40, 'LSF Application Data', 'grid_bapps.php', $version);
	plugin_rtm_migrate_realms('grid', 41, 'LSF Job Group Data', 'grid_bjgroups.php', $version);
	plugin_rtm_migrate_realms('grid', 42, 'LSF Queue Distribution', 'grid_queue_distrib.php', $version);
	plugin_rtm_migrate_realms('grid', 44, 'LSF Administration', 'grid_manage_hosts.php,grid_utilities.php,grid_elim_graphs.php,grid_elim_templates.php,grid_clusters.php,grid_settings_system.php,grid_pollers.php', $version);
	plugin_rtm_migrate_realms('grid', 1046, 'LSF Extended History', 'LSF_Extended_History', $version);
	plugin_rtm_migrate_realms('grid', 1048, 'LSF Host Alert Stop/Resume', 'LSF_Host_Alert_Stop', $version);
	plugin_rtm_migrate_realms('grid', 98, 'LSF Cluster Control', 'LSF_Cluster_Control', $version);
	plugin_rtm_remove_realm_data('grid', 1047);

	//meta
	plugin_rtm_migrate_realms('meta', 40987, 'View Metadata', 'metadata.php', $version);
	plugin_rtm_migrate_realms('meta', 40988, 'Edit Metadata', 'Edit_Metadata', $version);

	//gridcstat
	plugin_rtm_migrate_realms('gridcstat', 1012, 'View Statistical Dashboard', 'gridcstat.php', $version);

	db_execute("INSERT INTO settings values('selected_theme','spectrum')");
	db_execute("INSERT INTO `settings_user` VALUES (1,'selected_theme','spectrum'),(1,'user_language','en-US');");
}
