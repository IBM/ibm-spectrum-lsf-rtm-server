<?php
// $Id$
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

function plugin_lsfenh_install() {
	include_once(dirname(__FILE__) . '/../../lib/rtm_plugins.php');

	api_plugin_register_hook('lsfenh', 'config_form',     'lsfenh_config_form',     'setup.php');
	api_plugin_register_hook('lsfenh', 'config_arrays',   'lsfenh_config_arrays',   'setup.php');
	api_plugin_register_hook('lsfenh', 'config_settings', 'lsfenh_config_settings', 'setup.php');
	api_plugin_register_hook('lsfenh', 'poller_bottom',   'lsfenh_poller_bottom',   'setup.php');

	plugin_lsfenh_install_tables();
}

function plugin_lsfenh_install_tables() {
	return true;
}

function plugin_lsfenh_uninstall() {
	return true;
}

function plugin_lsfenh_upgrade() {
	/* do nothing database_upgrade.php script to handle this*/
	return false;
}

function plugin_lsfenh_check_config() {
	return true;
}

function plugin_lsfenh_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/lsfenh/INFO', true);
	return $info['info'];
}

function get_lsfenh_version() {
	$info = plugin_lsfenh_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}

function lsfenh_poller_bottom() {
	global $config;

	$command_string = read_config_option('path_php_binary');

	$extra_args_add_resreq = '-q ' . $config['base_path'] . '/plugins/lsfenh/poller_lsfenh.php';
	exec_background($command_string, $extra_args_add_resreq);

	$extra_args_add_resreq = '-q ' . $config['base_path'] . '/plugins/lsfenh/poller_pend.php';
	exec_background($command_string, $extra_args_add_resreq);

	$extra_args_add_resreq = '-q ' . $config['base_path'] . '/plugins/lsfenh/poller_buckets.php';
	exec_background($command_string, $extra_args_add_resreq);
}

function lsfenh_config_form() {
	return true;
}

function lsfenh_config_settings() {
	return true;
}

function lsfenh_config_arrays() {
	return true;
}

