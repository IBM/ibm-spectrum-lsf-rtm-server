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

function plugin_gridcstat_install() {
	global $config;

	api_plugin_register_hook('gridcstat', 'config_arrays', 'gridcstat_config_arrays', 'setup.php');
	api_plugin_register_hook('gridcstat', 'draw_navigation_text', 'gridcstat_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('gridcstat', 'poller_bottom', 'gridcstat_poller_bottom', 'setup.php');
	api_plugin_register_hook('gridcstat', 'grid_tab_down', 'cstat_grid_tab_down', 'setup.php');
	api_plugin_register_hook('gridcstat', 'grid_menu', 'cstat_grid_menu', 'setup.php');

	api_plugin_register_realm('gridcstat', 'gridcstat.php', 'View Statistical Dashboard', 1);
}

function plugin_gridcstat_uninstall(){
}

function plugin_gridcstat_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/gridcstat/INFO', true);
	return $info['info'];
}

function gridcstat_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $user_menu, $gridcstat_rows_selector;
	global $gridcstat_time_range;

	if (!api_plugin_is_enabled('grid') && strpos(get_current_page(), 'gridcstat') !== false){
		$user_menu = cstat_grid_menu();
	}

	$gridcstat_rows_selector = array(
		-1 => 'Default:10',
		 5 => '5',
		10 => '10',
		15 => '15',
		20 => '20',
		30 => '30',
		50 => '50');

	$gridcstat_time_range = array(
		'1day' => '1 Day',
		'2days' => '2 Days',
		'5days' => '5 Days',
		'1week' => '1 Week',
		'2weeks' => '2 Weeks',
		'3weeks' => '3 Weeks',
		'1month' => '1 Month',
		'2months' => '2 Months',
		'1quarter' => '1 Quarter',
		'2quarters' => '2 Quarters',
		'3quarters' => '3 Quarters',
		'1year' => '1 Year',
		'2years' => '2 Years',
		'3years' => '3 Years',
	);
}

function gridcstat_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/gridcstat/poller_gridcstat.php"';
	exec_background($command_string, $extra_args);
}

function gridcstat_draw_navigation_text ($nav) {
	$nav['gridcstat.php:'] = array(
		'title' => 'Statistical Dashboard',
		'mapping' => 'grid_default.php:',
		'url' => 'gridcstat.php',
		'level' => '1'
	);
	return $nav;
}

function cstat_grid_tab_down($pages_grid_tab_down){
	$cstat_pages_grid_tab_down=array(
		'gridcstat.php' => ''
		);
	$pages_grid_tab_down += array("gridcstat" => $cstat_pages_grid_tab_down);
	return $pages_grid_tab_down;
}

function cstat_grid_menu($grid_menu = array()) {
  $cstat_menu = array(
        __('Dashboards') => array(
			'plugins/gridcstat/gridcstat.php'  => __('Statistical')
		)
	);

	if (!empty($grid_menu)) {
		$menu2 = array();
		foreach ($grid_menu as $gmkey => $gmval ) {
			$menu2[$gmkey] = $gmval;
			if ($gmkey == __('Dashboards', 'grid')) {
				foreach($gmval as $key => $menu) {
					$menu2[__('Dashboards')][$key] = $menu;
					if ($menu == __('Host', 'grid')) {
						$menu2[__('Dashboards')]['plugins/gridcstat/gridcstat.php'] = __('Statistical');
					}
				}
			}
		}
		return $menu2;
	}

	return $cstat_menu;
}
