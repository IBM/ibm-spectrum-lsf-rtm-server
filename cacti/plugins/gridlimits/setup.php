<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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

function plugin_gridlimits_install() {
	api_plugin_register_hook('gridlimits', 'config_arrays',        'gridlimits_config_arrays',        'setup.php');
	api_plugin_register_hook('gridlimits', 'draw_navigation_text', 'gridlimits_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('gridlimits', 'poller_bottom',        'gridlimits_poller_bottom',        'setup.php');
	api_plugin_register_hook('gridlimits', 'grid_menu',            'gridlimits_grid_menu',            'setup.php');

	api_plugin_register_realm('gridlimits', 'grid_limits.php', 'LSF Limits', 1);

	gridlimits_setup_table_new();
}

function gridlimits_poller_bottom() {
	global $config;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/gridlimits/poller_gridlimits.php"';
	exec_background($command_string, $extra_args);
}

function gridlimits_config_arrays() {
	global $grid_menu, $config, $menu, $user_auth_realms, $user_auth_realm_filenames;

	# $user_auth_realms["25'] = 'RTM -> Grid Limits';
	$user_auth_realm_filenames['grid_limits.php'] = 25;

	$grid_menu[__('Reports', 'grid')]['plugins/gridlimits/grid_limits.php'] = __('Limits', 'gridlimits');

	if (function_exists('auth_augment_roles')) {
        auth_augment_roles(__('LSF User', 'grid'), array('grid_limits.php'));
	}
}

function plugin_gridlimits_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/gridlimits/INFO', true);
	return $info['info'];
}

function plugin_gridlimits_uninstall() {
	/* Do any extra Uninstall stuff here */
	db_execute('DROP TABLE IF EXISTS grid_limits');
	db_execute('DROP TABLE IF EXISTS grid_limits_usage');
	db_execute('DROP TABLE IF EXISTS grid_limits_history');
}

function plugin_gridlimits_check_config() {
	/* Here we will check to ensure everything is configured */
	gridlimits_check_upgrade();
	return true;
}

function plugin_gridlimits_upgrade() {
	/* Here we will upgrade to the newest version */
	gridlimits_check_upgrade();
	return false;
}

function gridlimits_check_upgrade() {
	/* Let's only run this check if we are on a page
	   that actually needs the data */
}

function gridlimits_check_dependencies() {
	global $plugins, $config;
	return true;
}

function gridlimits_setup_table_new() {
	db_execute("CREATE TABLE IF NOT EXISTS grid_limits (
		clusterid int(10) unsigned NOT NULL default '0',
		limit_name varchar(100) NOT NULL,
		users blob,
		per_user blob,
		hosts blob,
		per_host blob,
		queues blob,
		per_queue blob,
		projects blob,
		per_project blob,
		lic_projects blob,
		per_lic_project blob,
		clusters blob,
		per_cluster blob,
		apps blob,
		per_app blob,
		slots int(10) unsigned default NULL,
		slots_usage int(10) unsigned default NULL,
		slots_limit int(10) unsigned default NULL,
		slots_per_processor varchar(1) default 'N',
		mem int(10) unsigned default NULL,
		mem_usage bigint unsigned default NULL,
		mem_limit bigint unsigned default NULL,
		mem_percent varchar(1) default 'N',
		swp int(10) unsigned default NULL,
		swp_usage bigint unsigned default NULL,
		swp_limit bigint unsigned default NULL,
		swp_percent varchar(1) default 'N',
		tmp int(10) unsigned default NULL,
		tmp_usage bigint unsigned default NULL,
		tmp_limit bigint unsigned default NULL,
		tmp_percent varchar(1) default 'N',
		jobs int(10) unsigned default NULL,
		jobs_usage int(10) unsigned default NULL,
		jobs_limit int(10) unsigned default NULL,
		resources blob,
		resources_usage blob,
		fwd_tasks int(10) unsigned default NULL,
		fwd_tasks_usage int(10) unsigned default NULL,
		fwd_tasks_limit int(10) unsigned default NULL,
		unit_for_limits varchar(5),
		present int(10) unsigned default NULL,
		last_updated timestamp NOT NULL default CURRENT_timestamp ON UPDATE CURRENT_timestamp,
		PRIMARY KEY (clusterid, limit_name))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	db_execute("CREATE TABLE IF NOT EXISTS grid_limits_usage (
		sequenceid bigint unsigned NOT NULL AUTO_INCREMENT,
		clusterid int(10) unsigned NOT NULL default 0,
		limit_name varchar(100) NOT NULL default '',
		users blob,
		queues blob,
		hosts blob,
		projects blob,
		lic_projects blob,
		clusters blob,
		apps blob,
		slots_usage int(10) unsigned default NULL,
		slots_limit int(10) unsigned default NULL,
		mem_usage bigint unsigned default NULL,
		mem_limit bigint unsigned default NULL,
		swp_usage bigint unsigned default NULL,
		swp_limit bigint unsigned default NULL,
		tmp_usage bigint unsigned default NULL,
		tmp_limit bigint unsigned default NULL,
		jobs_usage int(10) unsigned default NULL,
		jobs_limit int(10) unsigned default NULL,
		fwd_tasks_usage int(10) unsigned default NULL,
		fwd_tasks_limit int(10) unsigned default NULL,
		unit_for_limits varchar(5),
		resources blob,
		last_updated timestamp NOT NULL default CURRENT_timestamp ON UPDATE CURRENT_timestamp,
		PRIMARY KEY (sequenceid),
		INDEX clusterid_limit_name_last_updated(clusterid, limit_name, last_updated))
		ENGINE=Aria
		ROW_FORMAT=Dynamic");

	db_execute("CREATE TABLE IF NOT EXISTS grid_limits_history (
		sequenceid bigint unsigned NOT NULL default '0',
		clusterid int(10) unsigned NOT NULL default '0',
		limit_name varchar(100) NOT NULL default '',
		users varchar(40) NOT NULL default '',
		queues varchar(40) NOT NULL default '',
		hosts varchar(64) NOT NULL default '',
		projects varchar(100) NOT NULL default '',
		lic_projects varchar(100) NOT NULL default '',
		clusters varchar(20) NOT NULL default '',
		apps varchar(20) NOT NULL default '',
		slots_usage int(10) unsigned default NULL,
		slots_limit int(10) unsigned default NULL,
		mem_usage bigint unsigned default NULL,
		mem_limit bigint unsigned default NULL,
		swp_usage bigint unsigned default NULL,
		swp_limit bigint unsigned default NULL,
		tmp_usage bigint unsigned default NULL,
		tmp_limit bigint unsigned default NULL,
		jobs_usage int(10) unsigned default NULL,
		jobs_limit int(10) unsigned default NULL,
		fwd_tasks_usage int(10) unsigned default NULL,
		fwd_tasks_limit int(10) unsigned default NULL,
		unit_for_limits varchar(5),
		resources blob,
		last_seen timestamp DEFAULT '0000-00-00',
		PRIMARY KEY (clusterid, limit_name, users, queues, hosts, projects, lic_projects, clusters, apps))
		ENGINE=Aria
		ROW_FORMAT=Dynamic");
}

function gridlimits_draw_navigation_text($nav) {
	$nav['grid_limits.php:'] = array(
		'title'   => __('Limits', 'gridlimits'),
		'mapping' => '',
		'url'     => 'grid_limits.php',
		'level'   => '1'
	);

	$nav['grid_limits.php:'] = array(
		'title'   => __('Limits Usage', 'gridlimits'),
		'mapping' => 'grid_limits.php:',
		'url'     => 'grid_limits.php',
		'level'   => '2'
	);

	return $nav;
}

function gridlimits_grid_menu($grid_menu = array()) {
	$menu = array(
		__('Reports', 'grid') => array(
			'plugins/gridlimits/grid_limits.php'  => __('Limits', 'gridlimits')
		)
	);

	if (cacti_sizeof($grid_menu)) {
		$menu2 = array();

		foreach ($grid_menu as $gmkey => $gmval ) {
			$menu2[$gmkey] = $gmval;
			if ($gmkey == __('Reports', 'grid')) {
				foreach($gmval as $key => $menu) {
					$menu2[__('Reports', 'grid')][$key] = $menu;

					if ($menu == __('Parameters', 'grid')) {
						$menu2[__('Reports')]['plugins/gridlimits/grid_limits.php'] = __('Limits', 'gridlimits');
					}
				}
			}
		}

		return $menu2;
	}

	return $menu;
}
