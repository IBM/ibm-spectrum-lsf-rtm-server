<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

/**
 * Installs the plugin; called by Cacti
 */
function plugin_meta_install () {
	// Register plugin hooks
	api_plugin_register_hook('meta', 'config_arrays', 'meta_config_arrays', 'setup.php');
	api_plugin_register_hook('meta', 'draw_navigation_text', 'meta_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('meta', 'page_head', 'meta_page_head', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_page_bottom', 'meta_page_bottom', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'lic_page_bottom', 'meta_page_bottom', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_settings_tab', 'meta_settings_tab', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_column_header', 'meta_column_header', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_column_content', 'meta_column_content', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_param', 'meta_param', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_param_where', 'meta_param_where', 'lib/metadata_api.php');
	api_plugin_register_hook('meta', 'grid_meta_column_filter', 'meta_column_filter', 'lib/metadata_api.php');
	api_plugin_register_realm('meta', 'metadata.php', 'View Metadata', 0);
	api_plugin_register_realm('meta', 'Edit_Metadata', 'Edit Metadata', 0);
	// Create plugin db tables
	meta_setup_table_new();

	return true;
}

/**
 * Returns version details
 */
function plugin_meta_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/meta/INFO', true);
	return $info['info'];
}

/**
 * Configures navigation text
 */
function meta_draw_navigation_text ($nav) {
	$nav['metadata.php:'] = array(
		'title' => __('Metadata'),
		'mapping' => 'index.php:',
		'url' => 'metadata.php:',
		'level' => '1'
	);

	$nav['metadata.php:list'] = array(
		'title' => __('Metadata'),
		'mapping' => 'index.php:',
		'url' => 'metadata.php:',
		'level' => '1'
	);

	$nav['metadata.php:actions'] = array(
		'title' => __('Actions'),
		'mapping' => 'index.php:,metadata.php:',
		'url' => 'metadata.php',
		'level' => '2'
	);

	$nav['metadata.php:detail'] = array(
		'title' => __('Edit Object'),
		'mapping' => 'index.php:,metadata.php:',
		'url' => 'metadata.php',
		'level' => '2'
	);

	$nav['metadata.php:add'] = array(
		'title' => __('Add Object'),
		'mapping' => 'index.php:,metadata.php:',
		'url' => 'metadata.php',
		'level' => '2'
	);

	$nav['metadata.php:delete'] = array(
		'title' => __('Delete Object(s)'),
		'mapping' => 'index.php:,metadata.php:',
		'url' => 'metadata.php',
		'level' => '2'
	);

	return $nav;
}

/**
 * Uninstalls the plugin
 */
function plugin_meta_uninstall () {
	db_execute('DROP TABLE IF EXISTS grid_metadata');
	db_execute('DROP TABLE IF EXISTS grid_metadata_conf');
}

/**
 * Not currently used
 */
function plugin_meta_check_config () {
	return true;
}

/**
 * Not currently used
 */
function plugin_meta_upgrade() {
	return false;
}

/**
 * Not currently used
 */
function meta_check_upgrade() {
}

/**
 * Not currently used
 */
function meta_check_dependencies() {
	global $plugins, $config;
	return true;
}

/**
 * Installs plugin tables
 */
function meta_setup_table_new() {
	global $config;
	$success = true;

	include_once($config['base_path'] . '/plugins/meta/lib/metadata_api.php');

	db_execute('CREATE TABLE IF NOT EXISTS `grid_metadata_conf` (
		`OBJECT_TYPE` varchar(20) NOT NULL,
		`DB_COLUMN_NAME` varchar(100) NOT NULL,
		`SECTION_NAME` varchar(100) NOT NULL,
		`DISPLAY_NAME` varchar(100) NOT NULL,
		`DESCRIPTION` varchar(1000),
		`DATA_TYPE` varchar(20) NOT NULL,
		`POSITION` integer(2) NOT NULL,
		`SUMMARY` integer(1) NOT NULL,
		`SEARCH` integer(1) NOT NULL,
		`POPUP` integer(1) NOT NULL)
		ENGINE=InnoDB;');

	db_execute('CREATE TABLE IF NOT EXISTS `grid_metadata` (
		`OBJECT_TYPE` varchar(20) NOT NULL,
		`OBJECT_ID` varchar(111) NOT NULL,
		`CLUSTER_ID` int(10) NOT NULL,
		`META_COL1` varchar(812) default NULL,
		`META_COL2` varchar(812) default NULL,
		`META_COL3` varchar(812) default NULL,
		`META_COL4` varchar(812) default NULL,
		`META_COL5` varchar(812) default NULL,
		`META_COL6` varchar(812) default NULL,
		`META_COL7` varchar(812) default NULL,
		`META_COL8` varchar(812) default NULL,
		`META_COL9` varchar(812) default NULL,
		`META_COL10` varchar(812) default NULL,
		`META_COL11` varchar(812) default NULL,
		`META_COL12` varchar(812) default NULL,
		`META_COL13` varchar(812) default NULL,
		`META_COL14` varchar(812) default NULL,
		`META_COL15` varchar(812) default NULL,
		`META_COL16` varchar(812) default NULL,
		`META_COL17` varchar(812) default NULL,
		`META_COL18` varchar(812) default NULL,
		`META_COL19` varchar(812) default NULL,
		`META_COL20` varchar(812) default NULL,
		PRIMARY KEY (object_type, object_id, cluster_id)
		)ENGINE=InnoDB;');

	// Load the default metadata configuration supplied with the plugin
	if (!(parse_metadata_conf($config['base_path'] . '/plugins/meta/metadata.conf.xml'))) {
		// Error
		$success = false;
	}

	return $success;
}

/**
 * Configures arrays used by the plugin
 */
function meta_config_arrays() {
	global $menu, $messages;

	$menu2 = array ();
	foreach ($menu as $temp => $temp2 ) {
			$menu2[$temp] = $temp2;
			if ($temp == __('Utilities')) {
					$menu2[__('Configuration')]['plugins/meta/metadata.php'] = __('Metadata Settings');
			} else {
					$menu2[$temp] = $temp2;
			}
	}
	$menu = $menu2;

	// Plugin messages
	$messages[100] = array(
		'message' => __('Object metadata imported successfully.'),
		'type' => 'info');

	$messages[101] = array(
		'message' => __('Object metadata imported with errors.'),
		'type' => 'error');

	$messages[102] = array(
		'message' => __('Object metadata import failed.'),
		'type' => 'error');

	$messages[103] = array(
		'message' => __('Object metadata saved successfully.'),
		'type' => 'info');

	$messages[104] = array(
		'message' => __('Object metadata saved successfully.'),
		'type' => 'info');

	$messages[105] = array(
		'message' => __('Object metadata deleted successfully.'),
		'type' => 'info');

	$messages[106] = array(
		'message' => __('Could not find the metadata configuration file.'),
		'type' => 'error');

	$messages[107] = array(
		'message' => __('MySQL error.'),
		'type' => 'error');

	$messages[108] = array(
		'message' => __('Object metadata configuration is not correctly formatted.'),
		'type' => 'error');

	$messages[109] = array(
		'message' => __('Please specify a metadata file to import.'),
		'type' => 'error');

	$messages[110] = array(
		'message' => __('Object metadata could not be imported.'),
		'type' => 'error');

	$messages[111] = array(
		'message' => __('Object metadata import file could not be opened.'),
		'type' => 'error');

	$messages[112] = array(
		'message' => __('Some metadata rows were invalid and were ignored.'),
		'type' => 'info');

	$messages[113] = array(
		'message' => __('Object metadata imported successfully with warnings.'),
		'type' => 'info');

	$messages[114] = array(
		'message' => __('Invalid metadata column header(s) encountered in the import file.'),
		'type' => 'error');

	$messages[115] = array(
		'message' => __('object_id column header must be present in the import file.'),
		'type' => 'error');

	$messages[116] = array(
		'message' => __('Object metadata configuration successfully imported.'),
		'type' => 'info');

	$messages[117] = array(
		'message' => __('Metadata object(s) successfully deleted.'),
		'type' => 'info');

	$messages[118] = array(
		'message' => __('Invalid metadata object type specified for deletion.'),
		'type' => 'error');

	$messages[50116] = array(
		'message' => __('cluster_id column header must be present in the import file.'),
		'type' => 'error');

	$messages[50117] = array(
		'message' => __('Invalid metadata object identifier specified.'),
		'type' => 'error');

	$messages[50118] = array(
		'message' => __('Object metadata save failed.'),
		'type' => 'error');

	$messages[50119] = array(
		'message' => __('No configuration is currently loaded for this object type.'),
		'type' => 'error');

	$messages[50120] = array(
		'message' => __('Metadata object already exists - no object created.'),
		'type' => 'error');

	$messages['field_input_save_2'] =  array(
			"message" => "Save Failed: Field Input Error (Check Red Fields). '<', '>', single and double quotes are not allowed to input.",
			"type" => "error");
}

/**
 * Not currently used
 */
function meta_config_settings () {
}
