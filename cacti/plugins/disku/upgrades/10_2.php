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

function upgrade_to_10_2() {
	global $system_type, $config;
	include_once($config['library_path'] . '/rtm_plugins.php');

	$version = '10.2';

	cacti_log('NOTE: Upgrading disku to v10.2.0.0 ...', true, 'UPGRADE');

	//disku
	plugin_rtm_migrate_realms('disku', 8900, 'View Disk Admin Data', 'disku_orgview.php,disku_users.php,disku_groups.php,disku_managers.php,disku_extensions.php,disku_appview.php,disku_tagview.php', $version);
	plugin_rtm_migrate_realms('disku', 8901, 'View Disk Usage Data', 'disku_dashboard.php', $version);
	plugin_rtm_migrate_realms('disku', 8902, 'Disk Usage Administration', 'disku_pollers.php,disku_paths.php,disku_extenreg.php,disku_applications.php', $version);
}
