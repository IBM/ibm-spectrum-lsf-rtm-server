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

function plugin_gridpend_install() {
	global $config;

	api_plugin_register_hook('gridpend', 'config_arrays',        'gridpend_config_arrays',        'setup.php');
	api_plugin_register_hook('gridpend', 'config_settings',      'gridpend_config_settings',      'setup.php');
	api_plugin_register_hook('gridpend', 'draw_navigation_text', 'gridpend_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('gridpend', 'poller_bottom',        'gridpend_poller_bottom',        'setup.php');
	api_plugin_register_hook('gridpend', 'grid_tab_down',        'gridpend_grid_tab_down',        'setup.php');
	api_plugin_register_hook('gridpend', 'grid_menu',            'gridpend_grid_menu',            'setup.php');

	api_plugin_register_realm('gridpend', 'grid_pend.php', 'Pending Reason History', true);

	plugin_gridpend_db();
}

function plugin_gridpend_uninstall() {
}

function plugin_gridpend_db() {
	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_pendhist_hourly` (
		`reason` int(10) unsigned NOT NULL,
		`subreason` varchar(40) not null DEFAULT '',
		`detail_type` varchar(15) NOT NULL DEFAULT '',
		`clusterid` int(10) unsigned NOT NULL,
		`stat` varchar(5) NOT NULL,
		`projectName` varchar(60) NOT NULL,
		`queue` varchar(40) NOT NULL,
		`user` varchar(60) NOT NULL,
		`total_pend` int(10) unsigned NOT NULL,
		`total_slots` int(10) unsigned NOT NULL,
		`date_recorded` timestamp NOT NULL default '0000-00-00 00:00:00',
		KEY `projectName` (`projectName`),
		KEY `queue` (`queue`),
		KEY `user` (`user`),
		KEY `date_recorded` (`date_recorded`),
		KEY `clusterid` (`clusterid`),
		KEY `reason` (`reason`),
		KEY `stat` (`stat`))
		ENGINE=MyISAM
		COMMENT='Hourly Statistics for Pending Reasons';");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_pendhist_daily` (
		`reason` int(10) unsigned NOT NULL,
		`subreason` varchar(40) not null DEFAULT '',
		`detail_type` varchar(15) NOT NULL DEFAULT '',
		`clusterid` int(10) unsigned NOT NULL,
		`stat` varchar(5) NOT NULL,
		`projectName` varchar(60) NOT NULL,
		`queue` varchar(40) NOT NULL,
		`user` varchar(60) NOT NULL,
		`total_pend` int(10) unsigned NOT NULL,
		`total_slots` int(10) unsigned NOT NULL,
		`year_day` int(10) NOT NULL default '2011001',
		KEY `projectName` (`projectName`),
		KEY `queue` (`queue`),
		KEY `user` (`user`),
		KEY `year_day` (`year_day`),
		KEY `clusterid` (`clusterid`),
		KEY `reason` (`reason`),
		KEY `stat` (`stat`))
		ENGINE=MyISAM
		COMMENT='Daily Statistics for Pending Reasons';");

	db_execute("CREATE TABLE IF NOT EXISTS grid_jobs_pendhist_yesterday LIKE grid_jobs_pendhist_hourly");
}

function plugin_gridpend_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/gridpend/INFO', true);
	return $info['info'];
}

function gridpend_config_arrays () {
	global $grid_menu, $gridpend_rows_selector, $user_menu;
	global $gridpend_time_range, $gridpend_max_memory;

	if (!api_plugin_is_enabled('grid') && strpos(get_current_page(), 'grid_pend') !== false){
		$user_menu = gridpend_grid_menu();
	}

	$gridpend_rows_selector = array(
		-1 => 'Default:10',
		 5 => '5',
		10 => '10',
		15 => '15',
		20 => '20',
		30 => '30',
		50 => '50');

	$gridpend_time_range = array(
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

	$gridpend_max_memory = array(
		"1024" => "1 GBytes",
		"1536" => "1.5 GBytes",
		"2048" => "2 GBytes",
		"3072" => "3 GBytes",
		"4096" => "4 GBytes",
		"5120" => "5 GBytes",
		"6144" => "6 GBytes",
		"8192" => "8 GBytes",
	    "-1" => "Infinity"
	);
}

function gridpend_config_settings() {
	global $tabs, $settings, $gridpend_max_memory;

	$tabs['rtmpi'] = 'RTM Plugins';

	$temp = array(
		'gridpend_header' => array(
			'friendly_name' => 'Pending Reason Dashboard Configuration',
			'method' => 'spacer',
			),
		'gridpend_include_exited' => array(
			'friendly_name' => 'Include Exited Jobs',
			'description' => 'If checked, the Pending Reason Statistics will include
			both \'DONE\' and \'EXIT\' job types.',
			'method' => 'checkbox',
			'default' => 'on'
			),
		"gridpend_poller_mem_limit" => array(
			"friendly_name" => "Memory Limit for Poller",
			"description" => "The maximum amount of memory for the Gridpend's Poller",
			"method" => "drop_array",
			"default" => "1024",
			"array" => $gridpend_max_memory
		)
	);

	if (isset($settings['rtmpi'])) {
		$settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
 	}else{
		$settings['rtmpi'] = $temp;
	}
}

function gridpend_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/gridpend/poller_gridpend.php"';
	exec_background($command_string, $extra_args);
}

function gridpend_draw_navigation_text ($nav) {
	$nav['grid_pend.php:'] = array(
		'title' => 'Pending Reason Dashboard',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_pend.php',
		'level' => '0'
	);
	return $nav;
}

function gridpend_grid_tab_down($pages_grid_tab_down){
    $gridpend_pages_grid_tab_down=array(
        'grid_pend.php' => ''
    );
    $pages_grid_tab_down += array("gridpend" => $gridpend_pages_grid_tab_down);
    return $pages_grid_tab_down;
}

function gridpend_grid_menu($grid_menu = array()) {
  $gridpend_menu = array(
        __('Dashboards') => array(
			'plugins/gridpend/grid_pend.php'  => __('Reasons')
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
						$menu2[__('Dashboards')]['plugins/gridpend/grid_pend.php'] = __('Reasons');
					}
				}
			}
		}
		return $menu2;
	}

	return $gridpend_menu;
}

function gridpend_memory_limit() {
    ini_set("memory_limit", "-1" == read_config_option("gridpend_poller_mem_limit") ? "-1" : (read_config_option("gridpend_poller_mem_limit") . "M"));
}
