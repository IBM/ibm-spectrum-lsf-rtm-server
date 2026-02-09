<?php

/**
 *
 */
function plugin_exlist_install () {
	api_plugin_register_hook('exlist', 'config_arrays',        'exlist_config_arrays',        'setup.php');
	api_plugin_register_hook('exlist', 'config_settings',      'exlist_config_settings',      'setup.php');
	api_plugin_register_hook('exlist', 'draw_navigation_text', 'exlist_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('exlist', 'grid_jobs_filter',     'exlist_jobs_filter',          'setup.php');
	api_plugin_register_hook('exlist', 'grid_jobs_sql_where',  'exlist_jobs_sql_where',       'setup.php');

	exlist_setup_table_new();
}

function exlist_jobs_filter() {
	$filters = db_fetch_assoc("SELECT id, name 
		FROM grid_jobs_exlists 
		WHERE enabled='on' ORDER BY name");

	if (sizeof($filters)) {
		foreach($filters as $f) {
			print "<option value='exlist" . $f['id'] . "' " . ((get_nfilter_request_var('exception') == 'exlist' . $f['id']) ? 'selected' : '') . '>' . html_escape($f['name']) . '</option>';
		}
	}
}

function exlist_jobs_sql_where($sql_where) {
	if (preg_match('(exlist)', get_nfilter_request_var('exception'))) {
		$id = str_replace('exlist', '', get_nfilter_request_var('exception'));

		$sql_where_new = db_fetch_cell_prepared('SELECT sql_where 
			FROM grid_jobs_exlists 
			WHERE id = ?',
			array($id));

		if ($sql_where != '') {
			$sql_where .= ' AND ' . $sql_where_new;
		}else{
			$sql_where = 'WHERE ' . $sql_where_new;
		}
	}

	return $sql_where;
}

/**
 *
 */
function exlist_version () {
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/exlist/INFO', true);
    return $info['info'];
}

/**
 *
 */
function plugin_exlist_uninstall () {
	db_execute('DROP TABLE IF EXISTS grid_jobs_exlists');
}

/**
 *
 */
function plugin_exlist_check_config () {
	return true;
}

/**
 *
 */
function plugin_exlist_upgrade() {
	return false;
}

/**
 *
 */
function plugin_exlist_version() {
	return exlist_version();
}

/**
 *
 */
function exlist_check_upgrade() {
}

/**
 *
 */
function exlist_check_dependencies() {
	global $plugins, $config;
	return true;
}

/**
 *
 */
function exlist_setup_table_new() {
	db_execute("CREATE TABLE IF NOT EXISTS `grid_jobs_exlists` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`name` varchar(30) NOT NULL default 'New Filter',
		`sql_where` varchar(255),
		`background_color` int unsigned default NULL,
		`enabled` char(2) DEFAULT NULL,
		PRIMARY KEY (`id`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	return true;
}

/**
 * Configures arrays for this plugin
 */
function exlist_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $user_menu, $messages;

	$menu2 = array ();
	foreach ($menu as $temp => $temp2 ) {
		$menu2[$temp] = $temp2;
		if ($temp == __('Data Collection')) {
			$menu2[__('Clusters', 'grid')]['plugins/exlist/exlists.php'] = __('Custom Job Filters', 'exlist');
			$menu2[$temp] = $temp2;
 		}
	}
	$menu = $menu2;
	
	$admin_realm = db_fetch_cell('SELECT id FROM plugin_realms WHERE file LIKE "%grid_manage_hosts.php%"') + 100;

	$user_auth_realm_filenames['exlists.php'] = $admin_realm;

	$messages['exlist_custom'] = array(
		'message' => (isset($_SESSION['sess_exlist_custom']) ? $_SESSION['sess_exlist_custom']:__('Undefined', 'exlist')),
		'type' => 'error'
	);

	$messages['exlist_custom_info'] = array(
		'message' => (isset($_SESSION['sess_exlist_custom_info']) ? $_SESSION['sess_exlist_custom_info']:__('Undefined', 'exlist')),
		'type' => 'info'
	);

	kill_session_var('sess_exlist_custom');
	kill_session_var('sess_exlist_custom_info');
}

/**
 *
 */
function exlist_config_settings () {
	global $tabs, $settings;
}

function exlist_draw_navigation_text($nav) {
	$nav['exlists.php:'] = array(
		'title' => __('Job Detail Exception Filters', 'exlist'), 
		'mapping' => 'index.php:', 
		'url' => 'exlists.php', 
		'level' => '1'
	);

	$nav['exlists.php:edit'] = array(
		'title' => __('(edit)'), 
		'mapping' => 'index.php:,exlists.php:', 
		'url' => 'exlists.php', 
		'level' => '2'
	);

	$nav['exlists.php:actions'] = array(
		'title' => __('(actions)'), 
		'mapping' => 'index.php:,exlists.php:,exlists.php:edit', 
		'url' => 'exlists.php', 
		'level' => '3'
	);

	return $nav;
}

