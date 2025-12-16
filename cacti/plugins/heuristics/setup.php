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

function plugin_heuristics_install () {
	global $config;

	include_once($config['base_path'] . '/plugins/heuristics/include/database.php');

	api_plugin_register_hook('heuristics', 'config_arrays',          'heuristics_config_arrays',        'setup.php');
	api_plugin_register_hook('heuristics', 'config_form',            'heuristics_config_form',          'setup.php');
	api_plugin_register_hook('heuristics', 'draw_navigation_text',   'heuristics_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('heuristics', 'config_settings',        'heuristics_config_settings',      'setup.php');
	api_plugin_register_hook('heuristics', 'top_header_tabs',        'heuristics_show_tab',             'setup.php');
	api_plugin_register_hook('heuristics', 'top_graph_header_tabs',  'heuristics_show_tab',             'setup.php');
	api_plugin_register_hook('heuristics', 'poller_bottom',          'heuristics_poller_bottom',        'setup.php');
	api_plugin_register_hook('heuristics', 'page_head',              'heuristics_page_head',            'setup.php');
	api_plugin_register_hook('heuristics', 'login_options_navigate', 'heuristics_login_navigate',       'setup.php');
	api_plugin_register_hook('heuristics', 'grid_busers_icon',       'heuristics_busers_icon',          'setup.php');
	api_plugin_register_hook('heuristics', 'grid_jobs_sql_where',    'heuristics_jobs_sql_where',       'setup.php');
	api_plugin_register_hook('heuristics', 'grid_menu',              'heuristics_grid_menu',            'setup.php');

	api_plugin_register_realm('heuristics', 'heuristics.php,heuristics_jobs.php,grid_heuristics.php', 'View JobIQ', 1);

	heuristics_setup_table_new();
}

function plugin_heuristics_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/heuristics/INFO', true);
	return $info['info'];
}

function plugin_heuristics_uninstall() {
	$tables = db_fetch_assoc('SHOW TABLES LIKE "grid_heuristics_percentiles_v%"');

	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			foreach($table as $key => $value) {
				db_execute('DROP TABLE IF EXISTS ' . $value);
			}
		}
	}

	$tables = db_fetch_assoc('SHOW TABLES LIKE "grid_heuristics_user_history_v%"');

	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			foreach($table as $key => $value) {
				db_execute('DROP TABLE IF EXISTS ' . $value);
			}
		}
	}
}

function plugin_heuristics_check_config() {
	/* Here we will check to ensure everything is configured */
	heuristics_check_upgrade();
	return true;
}

function plugin_heuristics_upgrade() {
	/* Here we will upgrade to the newest version */
	heuristics_check_upgrade();
	return false;
}

function heuristics_page_head() {
	global $config;

	print "\t<link type='text/css' href='" . $config['url_path'] . "plugins/heuristics/heuristics.css' rel='stylesheet'>\n";
	print "\t<script type='text/javascript' src='" . $config['url_path'] . "plugins/heuristics/heuristics.js'></script>\n";
	if (!api_plugin_is_enabled('RTM')) {
		print get_md5_include_js('/plugins/RTM/include/fusioncharts/fusioncharts.js');
	}
}

function heuristics_check_upgrade() {
	global $config, $database_default;

	include_once($config['library_path'] . '/database.php');
	include_once($config['library_path'] . '/functions.php');

	// Let's only run this check if we are on a page that actually needs the data
	$files = array('plugins.php', 'heuristics.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = get_heuristics_version();
	$old     = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='heuristics'");

	if ($current != $old) {
		db_execute_prepared("UPDATE plugin_config SET version=? WHERE directory='heuristics'", array($current));
	}
}

function heuristics_check_dependencies() {
	global $plugins, $config;
	return true;
}

function heuristics_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/heuristics/poller_heuristics.php"';
	exec_background($command_string, $extra_args);
}

function heuristics_config_form() {
	global $fields_user_user_edit_host;

	/* add jobiq as the default tab */
	$fields_user_user_edit_host['login_opts']['items'][5] = array(
		'radio_value' => '5',
		'radio_caption' => __('Show the JobIQ tab.', 'heurisitcs')
	);
}

function heuristics_grid_menu($grid_menu = array()) {
    if (!empty($grid_menu)) {
		$grid_menu[__('Reports')]['plugins/heuristics/grid_heuristics.php'] = __('Job Heuristics', 'heuristics');
	}
	return $grid_menu;
}

function heuristics_config_arrays() {
	global $messages, $heuristics_severities, $heuristics_memsizes, $heuristics_runtimes, $heuristics_history;

	$heuristics_severities = array(
		'-1'     => __('All', 'heuristics'),
		'notice' => __('Notice+', 'heuristics'),
		'warn'   => __('Warning+', 'heuristics'),
		'alarm'  => __('Alarm', 'heuristics'),
	);

	heuristics_check_upgrade();

	$heuristics_memsizes = array(
		'-1' => array('text' => __('N/A', 'heuristics'),         'sql' => ''),
		'1'  => array('text' => __('0 - 1GB', 'heuristics'),     'sql' => ' AND max_memory BETWEEN 0 AND 1048576-1'),
		'2'  => array('text' => __('1GB - 2GB', 'heuristics'),   'sql' => ' AND max_memory BETWEEN 1048576 AND 2097152-1'),
		'3'  => array('text' => __('2GB - 4GB', 'heuristics'),   'sql' => ' AND max_memory BETWEEN 2097152 AND 4194304-1'),
		'4'  => array('text' => __('4GB - 8GB', 'heuristics'),   'sql' => ' AND max_memory BETWEEN 4194304 AND 8388608-1'),
		'5'  => array('text' => __('8GB - 16GB', 'heuristics'),  'sql' => ' AND max_memory BETWEEN 8388608 AND 16777216-1'),
		'6'  => array('text' => __('16GB - 24GB', 'heuristics'), 'sql' => ' AND max_memory BETWEEN 16777216 AND 25165824-1'),
		'7'  => array('text' => __('24GB - 32GB', 'heuristics'), 'sql' => ' AND max_memory BETWEEN 25165824 AND 33554432-1'),
		'8'  => array('text' => __('32GB - 64GB', 'heuristics'), 'sql' => ' AND max_memory BETWEEN 33554432 AND 67108864-1'),
		'9'  => array('text' => __('64GB+', 'heuristics'),       'sql' => ' AND max_memory >=67108864')
	);

	$heuristics_runtimes = array(
		'-1' => array('text' => __('N/A', 'heuristics'),            'sql' => ''),
		'1'  => array('text' => __('0 to 5Min', 'heuristics'),      'sql' => ' AND run_time BETWEEN 0 AND 300-1'),
		'2'  => array('text' => __('5Min to 15Min', 'heuristics'),  'sql' => ' AND run_time BETWEEN 300 AND 900-1'),
		'3'  => array('text' => __('15Min to 30Min', 'heuristics'), 'sql' => ' AND run_time BETWEEN 900 AND 1800-1'),
		'4'  => array('text' => __('30Min to 1Hr', 'heuristics'),   'sql' => ' AND run_time BETWEEN 1800 AND 3600-1'),
		'5'  => array('text' => __('1Hr to 2Hrs', 'heuristics'),    'sql' => ' AND run_time BETWEEN 3600 AND 7200-1'),
		'6'  => array('text' => __('2Hrs to 6Hrs', 'heuristics'),   'sql' => ' AND run_time BETWEEN 7200 AND 21600-1'),
		'7'  => array('text' => __('6Hrs to 12Hrs', 'heuristics'),  'sql' => ' AND run_time BETWEEN 21600 AND 43200-1'),
		'8'  => array('text' => __('12Hrs to 1Day', 'heuristics'),  'sql' => ' AND run_time BETWEEN 43200 AND 86400-1'),
		'9'  => array('text' => __('1Day to 2Days', 'heuristics'),  'sql' => ' AND run_time BETWEEN 86400 AND 172800-1'),
		'10' => array('text' => '> 2Days',        'sql' => ' AND run_time >= 172800')
	);

	$heuristics_history = array(
		'3600'   => __('%d Hour', 1, 'heuristics'),
		'7200'   => __('%d Hours', 2, 'heuristics'),
		'14400'  => __('%d Hours', 4, 'heuristics'),
		'28800'  => __('%d Hours', 8, 'heuristics'),
		'43200'  => __('%d Hours', 12, 'heuristics'),
		'86400'  => __('%d Day', 1, 'heuristics'),
		'172800' => __('%d Days', 2, 'heuristics'),
		'259200' => __('%d Days', 3, 'heuristics'),
		'345600' => __('%d Days', 4, 'heuristics'),
		'432000' => __('%d Days', 5, 'heuristics'),
		'518400' => __('%d Days', 6, 'heuristics'),
		'604800' => __('%d Days', 7, 'heuristics')
	);

}

function heuristics_config_settings() {
	global $tabs, $settings;

	$tabs['rtmpi'] = __('RTM Plugins', 'grid');

	$temp = array(
		'heuristics_header' => array(
			'friendly_name' => __('RTM Job Heuristic Settings', 'heuristics'),
			'method' => 'spacer',
			),
		'grid_set_default_user' => array(
			'friendly_name' => __('Set Default User', 'heuristics'),
			'description' => __('If checked, the current logged in username is default user if user filter is not set when navigating to the JobIQ page. The logged in username has to be a valid user in LSF clusters.', 'heuristics'),
			'method' => 'checkbox',
			'default' => 'on'
		),
		'heuristics_days' => array(
			'friendly_name' => __('Job History Days', 'heuristics'),
			'description' => __('How many days of Job history will be retained for Percentile calculations.  Keep in mind, as job volume increases, you should limit the number of days as it will impact daily processing times.', 'heuristics'),
			'method' => 'drop_array',
			'array' => array(
				'2'  => __('Finished + %d Days', 2, 'heuristics'),
				'3'  => __('Finished + %d Days', 3, 'heuristics'),
				'4'  => __('Finished + %d Days', 4, 'heuristics'),
				'5'  => __('Finished + %d Days', 5, 'heuristics'),
				'6'  => __('Finished + %d Days', 6, 'heuristics'),
				'7'  => __('Finished + %d Week', 1, 'heuristics'),
				'14' => __('Finished + %d Weeks', 2, 'heuristics'),
				'30' => __('Finished + %d Month', 1, 'heuristics')
			),
			'default' => '7'
		),
		'heuristics_custom_column' => array(
			'friendly_name' => __('Custom Aggregation Column', 'heuristics'),
			'description' => __('Heuristics will calculate statistical data at various levels including Clusters, Queue, the Number of CPU\'s, an optional user defined column, then by Project and finally, and optionally, by Effective Resource Requirements.  This setting allows you to control Custom Aggregation column in RTM. WARNING: Changing this value will remove exiting historical Heuristics data at next polling cycle.', 'heuristics'),
			'method' => 'drop_array',
			'array' => array(
				'none'         => __('None', 'heuristics'),
				'app'          => __('Application', 'heuristics'),
				'sla'          => __('SLA', 'heuristics'),
				'chargedSAAP'  => __('Charged SAAP', 'heuristics'),
			),
			'default' => 'none'
		),
		'heuristics_low_level_agg' => array(
			'friendly_name' => __('Lowest Level Aggregation', 'heuristics'),
			'description' => __('When aggregating Statistical Data, Heuristics will Walk down the natural Hierarchy of Cluster, Queue, Number of CPU\'s, the Custom Aggregation Column, Project Name and finally Effective Resource Requirements.  You can direct Heuristics to stop walking through the Aggregations after a certain level.  This setting was created to avoid out of memory problems with Database Servers.  If you Choose to go all the way to the Resource Requirements Level, you should ensure that your MariaDB/MySQL \'tmpdir\' is set and the \'tmpdir\' is pointing to a memory file system that is in line with the number of jobs in your heuristics setting.  A 300GB memory \'tmpdir\' is required for 30 million job records at that level.', 'heuristics'),
			'method' => 'drop_array',
			'array' => array(
				'custom'  => __('Custom Aggregation Column', 'heuristics'),
				'project' => __('Project', 'heuristics'),
				'resreq'  => __('Resource Requirements (see warnings)', 'heuristics'),
			),
			'default' => 'project'
		),
		'heuristics_down_warn' => array(
			'friendly_name' => __('Down Host Warning Percent', 'heuristics'),
			'description' => __('What percentage of hosts must be down prior to being in Warning?', 'heuristics'),
			'method' => 'drop_array',
			'array' => array(
				'5'  => __('%d%% of Hosts', 5,  'heuristics'),
				'10' => __('%d%% of Hosts', 10, 'heuristics'),
				'15' => __('%d%% of Hosts', 15, 'heuristics'),
				'20' => __('%d%% of Hosts', 20, 'heuristics'),
				'25' => __('%d%% of Hosts', 25, 'heuristics'),
				'30' => __('%d%% of Hosts', 30, 'heuristics')
			),
			'default' => '5'
		),
		'heuristics_down_alarm' => array(
			'friendly_name' => __('Down Host Alarm Percent', 'heuristics'),
			'description' => __('What percentage of hosts must be down prior to being in Alarm?', 'heuristics'),
			'method' => 'drop_array',
			'array' => array(
				'10' => __('%d%% of Hosts', 10, 'heuristics'),
				'15' => __('%d%% of Hosts', 15, 'heuristics'),
				'20' => __('%d%% of Hosts', 20, 'heuristics'),
				'25' => __('%d%% of Hosts', 25, 'heuristics'),
				'30' => __('%d%% of Hosts', 30, 'heuristics'),
				'35' => __('%d%% of Hosts', 35, 'heuristics'),
				'40' => __('%d%% of Hosts', 40, 'heuristics'),
				'50' => __('%d%% of Hosts', 50, 'heuristics')
			),
			'default' => '10'
		),
		'heuristics_throughput_warning' => array(
			'friendly_name' => __('Cluster Hourly Throughput Warning', 'heuristics'),
			'description' => __('What total sum of hourly throughput must exceed prior to being in Warning?', 'heuristics'),
			'method' => 'textbox',
			'value' => '',
			'max_length' => '20',
			'default' => '10000'
		)
	);

	if (isset($settings['rtmpi'])) {
		$settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
	}else {
		$settings['rtmpi'] = $temp;
	}
}

function heuristics_draw_navigation_text ($nav) {
	$nav['heuristics.php:'] = array(
		'title' => __('Job Heuristics', 'heuristics'),
		'mapping' => '',
		'url' => 'heuristics.php',
		'level' => '0'
	);

	$nav['heuristics_jobs.php:'] = array(
		'title' => __('Jobs', 'heuristics'),
		'mapping' => 'heuristics.php:',
		'url' => 'heuristics_jobs.php',
		'level' => '1'
	);

	$nav['heuristics_jobs.php:viewlist'] = array(
		'title' => __('View Job Listing', 'heuristics'),
		'mapping' => 'heuristics.php:',
		'url' => '',
		'level' => '1'
	);

	$nav['heuristics_jobs.php:viewjob'] = array(
		'title' => __('View Job', 'heuristics'),
		'mapping' => 'heuristics.php:,heuristics_jobs.php:viewlist',
		'url' => '',
		'level' => '2'
	);

	$nav['heuristics_jobs.php:actions'] = array(
		'title' => __('Actions', 'heuristics'),
		'mapping' => 'heuristics.php:,heuristics_jobs.php:viewlist',
		'url' => '', 'level' => '2'
	);

	$nav['grid_heuristics.php:'] = array(
		'title' => __('Job Heuristics Viewer', 'heuristics'),
		'mapping' => 'grid_default.php:',
		'url' => 'grid_heuristics.php',
		'level' => '1'
	);

	return $nav;
}

function heuristic_tab_down() {
	$console_tabs = array(1 => 'grid_heuristics.php' );
	$trimmed_uri = strtok(basename($_SERVER['SCRIPT_NAME']), '?');

	if (!substr_count($trimmed_uri, 'heuristics')) {
		return false;
	} else if (array_search($trimmed_uri, $console_tabs)) {
		return false;
	} else {
		return true;
	}
}

function heuristics_show_tab() {
	global $config, $tabs_left;

	if (api_user_realm_auth('heuristics.php')) {
		if (!heuristic_tab_down()) {
			$heuristic_down = false;
		} else {
			$heuristic_down = true;
		}

		if (get_selected_theme() != 'classic') {
			if (array_search('tab-heuristic', array_rekey($tabs_left, 'id', 'id')) === false) {
				$tab_heuristic = array(
					'title' => __('JobIQ', 'heuristics'),
					'id'    => 'tab-heuristic',
					'url'   => html_escape($config['url_path'] . 'plugins/heuristics/heuristics.php')
				);

				$tabs_left[] = &$tab_heuristic;
			} else {
				foreach($tabs_left as $tab_left) {
					if ($tab_left['id'] == 'tab-heuristic') {
						$tab_heuristic = &$tab_left;
					}
				}
			}

			if ($heuristic_down) {
				$tab_heuristic['selected'] = true;
			} else {
				unset($tab_heuristic['selected']);
			}
		} else {
			print '<a href="' . $config['url_path'] . 'plugins/heuristics/heuristics.php"><img src="' . $config['url_path'] . 'plugins/heuristics/images/tab_heuristics' . ($heuristic_down ? '_down' : '') . '.png" alt="' . __esc('JobIQ', 'heuristics') . '" align="absmiddle" border="0"></a>';
		}
	}
}

function heuristics_login_navigate($login_opt) {
	global $config;

	if ($login_opt == '5') {
		header('Location: ' . $config['url_path'] . 'plugins/heuristics/heuristics.php' . (isset_request_var('noheader') ? '?noheader=true':''));
	}

	return $login_opt;
}

function heuristics_busers_icon($user) {
	global $config;

	if (is_array($user)) {
		$clusterid = $user['clusterid'];
		$user = $user['user_or_group'];
	} else {
		$clusterid = -1;
		$user = '';
	}

	print "<a href='" .  html_escape($config['url_path'] . "plugins/heuristics/heuristics.php?reset=true&user_iq=$user&clusterid=$clusterid") . "'>
		<img src='" . $config['url_path'] . "plugins/heuristics/images/jobiq.png' alt='' title='" . __esc('View User Dashboard', 'heuristics') . "'>
	</a>";

	return $user;
}

function heuristics_jobs_sql_where($sql_where) {
	global $heuristics_memsizes, $heuristics_runtimes;

	if (isset_request_var('memsize') && get_request_var('memsize') != '-1') {
		$sql_where .= $heuristics_memsizes[get_request_var('memsize')]['sql'];

	}

	if (isset_request_var('runtime') && get_request_var('runtime') != '-1') {
		$sql_where .= $heuristics_runtimes[get_request_var('runtime')]['sql'];
	}

	return $sql_where;
}

/**
 * Get version number of plugin, or RTM version if plugin version is not defined
 * @return string version number
 */
function get_heuristics_version() {
	$info = @plugin_heuristics_version();
	if (!empty($info) && isset($info['version'])) {
		return $info['version'];
	} else {
		global $config;
		include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
		return RTM_VERSION;
	}
}
