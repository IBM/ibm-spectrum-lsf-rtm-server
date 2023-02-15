<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright (C) 2006-2022 IBM Inc.                                        |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_gridgmgmt_install() {
	api_plugin_register_hook('gridgmgmt', 'config_arrays',         'gridgmgmt_config_arrays',        'setup.php');
	api_plugin_register_hook('gridgmgmt', 'draw_navigation_text',  'gridgmgmt_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('gridgmgmt', 'config_settings',       'gridgmgmt_config_settings',      'setup.php');
	api_plugin_register_hook('gridgmgmt', 'top_header_tabs',       'gridgmgmt_show_tab',             'setup.php');
	api_plugin_register_hook('gridgmgmt', 'top_graph_header_tabs', 'gridgmgmt_show_tab',             'setup.php');

	api_plugin_register_realm(
		'gridgmgmt',
		'projects.php,licprojects.php,licprojfeat.php,queues.php,hosts.php,hostgroups.php,jobgroups.php,applications.php,usergroups.php,users.php,shares.php,slas.php,pools.php,pools_slas.php,shared.php',
		__('LSF Graph Management', 'gridgmgmt'), 1
	);

	gridgmgmt_setup_table_new();
}

function plugin_gridgmgmt_version() {
	global $config;

	$info = parse_ini_file($config['base_path'] . '/plugins/gridgmgmt/INFO', true);
	return $info['info'];
}

function plugin_gridgmgmt_uninstall() {
	return true;
}

function plugin_gridgmgmt_check_config() {
	/* Here we will check to ensure everything is configured */
	gridgmgmt_check_upgrade();
	return true;
}

function plugin_gridgmgmt_upgrade() {
	/* Here we will upgrade to the newest version */
	gridgmgmt_check_upgrade();
	return false;
}

function gridgmgmt_check_upgrade() {
	global $config, $database_default;

	include_once($config['library_path'] . '/database.php');
	include_once($config['library_path'] . '/functions.php');

	// Let's only run this check if we are on a page that actually needs the data
	$files = array('plugins.php', 'gridgmgmt.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_gridgmgmt_version();
	$current = $current['version'];
	$old     = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='gridgmgmt'");

	if ($current != $old) {
		db_execute("UPDATE plugin_config SET version='$current' WHERE directory='gridgmgmt'");
	}
}

function gridgmgmt_check_dependencies() {
	global $plugins, $config;
	return true;
}

function gridgmgmt_setup_table_new() {
	// A place to install tables
}

function gridgmgmt_config_arrays() {
	global $menu, $menu_glyphs;

	gridgmgmt_check_upgrade();

	$menu_glyphs[__('Cluster Graphs', 'gridgmgmt')]      = 'fa fa-chart-line';
	$menu_glyphs[__('Lic Sched Graphs', 'gridgmgmt')]      = 'fa fa-chart-line';

	$menu2 = array();

	foreach ($menu as $temp => $temp2 ) {
		$menu2[$temp] = $temp2;
		if ($temp == __('Clusters')) {
			// LSF Core Graphs
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/applications.php'] = __('Applications', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/queues.php']       = __('Queues', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/shares.php']       = __('Queue Shares', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/hosts.php']        = __('Hosts', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/hostgroups.php']   = __('Host Groups', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/jobgroups.php']    = __('Job Groups', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/projects.php']     = __('Projects', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/users.php']        = __('Users', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/usergroups.php']   = __('User Groups', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/slas.php']         = __('SLA\'s', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/pools.php']        = __('Pools', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/pools_slas.php']   = __('Pools/SLA\'s', 'gridgmgmt');
			$menu2[__('Cluster Graphs', 'gridgmgmt')]['plugins/gridgmgmt/shared.php']       = __('Shared Resources', 'gridgmgmt');

			// LSF License Scheduler Graphs
			$menu2[__('Lic Sched Graphs', 'gridgmgmt')]['plugins/gridgmgmt/licprojects.php']     = __('License Project', 'gridgmgmt');
			$menu2[__('Lic Sched Graphs', 'gridgmgmt')]['plugins/gridgmgmt/licprojfeat.php']     = __('License Features', 'gridgmgmt');
		}
	}

	$menu = $menu2;

	if (function_exists('auth_augment_roles')) {
        auth_augment_roles(__('LSF Administration', 'grid'), array('projects.php'));
	}
}

function gridgmgmt_config_settings() {
	global $tabs, $settings;
}

function gridgmgmt_draw_navigation_text ($nav) {
	$nav['projects.php:'] = array(
		'title' => __('Project Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'projects.php',
		'level' => '1'
	);

	$nav['projects.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'projects.php:',
		'level' => '1'
	);

	$nav['projects.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'projects.php',
		'level' => '1'
	);

	$nav['licprojects.php:'] = array(
		'title' => __('License Projects', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojects.php',
		'level' => '1'
	);

	$nav['licprojects.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojects.php:',
		'level' => '1'
	);

	$nav['licprojects.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojects.php',
		'level' => '1'
	);

	$nav['licprojfeat.php:'] = array(
		'title' => __('License Project Features', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojfeat.php',
		'level' => '1'
	);

	$nav['licprojfeat.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojfeat.php:',
		'level' => '1'
	);

	$nav['licprojfeat.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'licprojfeat.php',
		'level' => '1'
	);

	$nav['queues.php:'] = array(
		'title' => __('Queue Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'queues.php',
		'level' => '1'
	);

	$nav['queues.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'queues.php:',
		'level' => '1'
	);

	$nav['queues.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'queues.php',
		'level' => '1'
	);

	$nav['jobgroups.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'jobgroups.php',
		'level' => '1'
	);

	$nav['jobgroups.php:'] = array(
		'title' => __('Job Groups', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'jobgroups.php',
		'level' => '1'
	);

	$nav['jobgroups.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'jobgroups.php:',
		'level' => '1'
	);

	$nav['hosts.php:'] = array(
		'title' => __('Host Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hosts.php',
		'level' => '1'
	);

	$nav['hosts.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hosts.php:',
		'level' => '1'
	);

	$nav['hosts.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hosts.php',
		'level' => '1'
	);

	$nav['hostgroups.php:'] = array(
		'title' => __('Host Group Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hostgroups.php',
		'level' => '1'
	);

	$nav['hostgroups.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hostgroups.php:',
		'level' => '1'
	);

	$nav['hostgroups.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'hostgroups.php',
		'level' => '1'
	);

	$nav['applications.php:'] = array(
		'title' => __('Application Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'applications.php',
		'level' => '1'
	);

	$nav['applications.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'applications.php:',
		'level' => '1'
	);

	$nav['applications.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'applications.php',
		'level' => '1'
	);

	$nav['users.php:'] = array(
		'title' => __('User Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'users.php',
		'level' => '1'
	);

	$nav['users.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'users.php:',
		'level' => '1'
	);

	$nav['users.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'users.php',
		'level' => '1'
	);

	$nav['shared.php:'] = array(
		'title' => __('Shared Resources Manager', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'shared.php',
		'level' => '1'
	);

	$nav['shared.php:edit'] = array(
		'title' => __('(edit)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'shared.php:',
		'level' => '1'
	);

	$nav['shared.php:actions'] = array(
		'title' => __('(actions)', 'gridgmgmt'),
		'mapping' => 'index.php:',
		'url' => 'shared.php',
		'level' => '1'
	);

	return $nav;
}

function gridgmgmt_show_tab() {
	global $config;

	return true;
}
