#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/lib/plugins.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug  = false;
$mode   = '';
$plugin = '';
$platform_installer = true;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-m':
		$mode = $value;
		break;
	case '-p':
		$plugin = $value;
		break;
	case '-v':
	case '-V':
	case '-h':
	case '-H':
	case '--help':
	case '--version':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

if (empty($mode) || empty($plugin)) {
	display_help();
	exit;
}

$plugins = retrieve_plugin_list ();

switch ($mode) {
	case 'refresh_enable':
		$exists = db_fetch_cell_prepared('SELECT id
			FROM plugin_config
			WHERE directory = ?',
			array($plugin));

		if (empty($exists)) {
			print "Start $mode $plugin\n";
			api_plugin_install($plugin);
			api_plugin_enable ($plugin);
		}

		break;
	case 'install':
		$exists = db_fetch_cell_prepared('SELECT id
			FROM plugin_config
			WHERE directory = ?',
			array($plugin));

		print "Start $mode $plugin\n";

		api_plugin_install($plugin);

		if (!empty($exists)) {
			print "Start $mode $plugin keep original order\n";

			db_execute_prepared('UPDATE plugin_config
				SET id = ?
				WHERE directory = ?',
				array($exists, $plugin));
		}

		break;
	case 'uninstall':
		print "Start $mode $plugin\n";

		if (!in_array($plugin, $plugins)) {
			print "Cannot uninstall plugin $plugin.  It is not installed\n";
			break;
		}

		api_plugin_uninstall($plugin);
		break;
	case 'disable':
		print "Start $mode $plugin\n";

		if (!in_array($plugin, $plugins)) {
			print "Cannot disable plugin $plugin.  It is not installed\n";
			break;
		}

		api_plugin_disable ($plugin);
		break;
	case 'enable':
		print "Start $mode $plugin\n";

		if (!in_array($plugin, $plugins)) {
			print "Cannot enable plugin $plugin.  It is not installed\n";
			break;
		}

		api_plugin_enable ($plugin);
		break;
	case 'status':
		print (api_plugin_is_enabled($plugin) == '1' ? "1\n":"0\n");
		break;
}

print "Done $mode $plugin\n";

function retrieve_plugin_list () {
	$plugins = array();

	$temp = db_fetch_assoc('SELECT directory FROM plugin_config ORDER BY name');

	if (cacti_sizeof($temp)) {
		foreach ($temp as $t) {
			$plugins[] = $t['directory'];
		}
	}

	return $plugins;
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Command Line Plugin Installer ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "grid_install_plugins.php -m=[install|uninstall|enable|disable] -p=<plugin name> [-h|--help|-v|-V|--version]\n\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - display this help message\n";
}

