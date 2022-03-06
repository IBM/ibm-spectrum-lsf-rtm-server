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

function plugin_rtm_install() {
	api_plugin_register_hook('RTM', 'custom_login',          'rtm_custom_login',    'include/auth_login.php');
	api_plugin_register_hook('RTM', 'custom_denied',         'rtm_custom_denied',   'include/permission_denied.php');
	api_plugin_register_hook('RTM', 'custom_logout',         'rtm_custom_logout',   'include/logout.php');
	api_plugin_register_hook('RTM', 'custom_logout_message', 'rtm_custom_logout',   'include/logout.php');
	api_plugin_register_hook('RTM', 'custom_password',       'rtm_custom_password', 'include/auth_changepassword.php');
	api_plugin_register_hook('RTM', 'console_before', 'rtm_console_before', 'setup.php');
	api_plugin_register_hook('RTM', 'page_head',             'rtm_page_head',       'setup.php', 1);
	api_plugin_register_hook('RTM', 'page_bottom',             'rtm_page_bottom',       'setup.php', 1);
}

function rtm_console_before() {
	global $config;

	include_once($config['base_path'] .'/plugins/RTM/console.php');

	rtm_console();
}

function rtm_page_head() {
	global $config;
	include_once($config['library_path'] . '/rtm_functions.php');

	print get_md5_include_css('plugins/RTM/include/main.css');
	print get_md5_include_js('plugins/RTM/include/fusioncharts/fusioncharts.js');
	$rtm_theme = get_selected_theme();
	$fusion_theme_path = $config['base_path'] . '/plugins/RTM/include/fusioncharts/themes/fusioncharts.theme.' . get_selected_theme() . '.js';
	if (file_exists($fusion_theme_path)) {
		print get_md5_include_js($fusion_theme_path);
	}
	print "<script type='text/javascript'>
		var brandName='" . __('IBM Spectrum') . "';
		var brandNameBold='" . __('IBM <b>Spectrum</b>') . "';
		var productName='" . __('LSF RTM 10.2.0.12') . "';
		var copyRight='" . __('© Copyright International Business Machines Corp. 1992, 2022. US Government Users Restricted Rights - Use, duplication or disclosure restricted by GSA ADP Schedule Contract with IBM Corp. Portions Copyright © 2004, 2022 The Cacti Group, Inc.') . "';
		</script>";
}

function rtm_page_bottom() {
	print "<script type='text/javascript'>
		$(function() {
			$('.cactiLogo').each(function () {
				$(this)[0].className = '';
			});
		});
		</script>";
}
function rtm_version() {
	return plugin_rtm_version();
}

function plugin_rtm_uninstall() {
	// No actions required
}

function plugin_rtm_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/RTM/INFO', true);
	return $info['info'];
}

function get_rtm_version() {
	$info = plugin_rtm_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}
