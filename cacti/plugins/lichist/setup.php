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

function plugin_lichist_install () {
	api_plugin_register_hook('lichist', 'grid_jobs_tabs',       'lichist_jobs_tabs',            'setup.php');
	api_plugin_register_hook('lichist', 'grid_jobs_show_tab',   'lichist_jobs_show_tab',        'setup.php');
	api_plugin_register_hook('lichist', 'poller_bottom',        'lichist_poller_bottom',        'setup.php');
	api_plugin_register_hook('lichist', 'draw_navigation_text', 'lichist_draw_navigation_text', 'setup.php');

	api_plugin_register_realm('lichist', 'grid_lichist.php', 'Plugin -> LSF Job License History Viewer', 1);

	lichist_setup_table_new ();
}

function lichist_poller_bottom () {
	global $config, $grid_poller_start;

	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q "' . $config['base_path'] . '/plugins/lichist/poller_lichist.php"';
	exec_background($command_string, $extra_args);
}

function plugin_lichist_uninstall () {
	/* Do any extra Uninstall stuff here */
}

function plugin_lichist_check_config () {
	/* Here we will check to ensure everything is configured */
	lichist_check_upgrade ();
	return true;
}

function plugin_lichist_upgrade () {
	/* Here we will upgrade to the newest version */
	lichist_check_upgrade ();
	return false;
}

function plugin_lichist_version () {
    global $config;
    $info = parse_ini_file($config['base_path'] . '/plugins/lichist/INFO', true);
    return $info['info'];
}

function lichist_check_upgrade () {
	/* Let's only run this check if we are on a page
	   that actually needs the data */
}

function lichist_check_dependencies() {
	global $plugins, $config;
	return true;
}

function lichist_setup_table_new () {
	db_execute("CREATE TABLE IF NOT EXISTS `lic_services_feature_history_template` (
 		 `id` bigint NOT NULL AUTO_INCREMENT,
  		`service_id` int(10) unsigned NOT NULL DEFAULT '0',
  		`vendor_daemon` varchar(40) NOT NULL DEFAULT '',
  		`feature_name` varchar(50) NOT NULL DEFAULT '0',
  		`subfeature` varchar(50) NOT NULL DEFAULT '',
  		`feature_version` varchar(50) NOT NULL DEFAULT '',
  		`username` varchar(40) NOT NULL DEFAULT '',
  		`groupname` varchar(50) NOT NULL DEFAULT '',
  		`hostname` varchar(64) NOT NULL DEFAULT '',
  		`chkoutid` varchar(20) NOT NULL DEFAULT '',
  		`restype` int(10) unsigned NOT NULL DEFAULT '0',
  		`status` varchar(20) NOT NULL DEFAULT '',
  		`tokens_acquired` int(10) unsigned NOT NULL DEFAULT '0',
  		`tokens_acquired_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  		`last_poll_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  		`tokens_released_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  		PRIMARY KEY (`id`),
  		KEY `idx_vendor_daemon` (`vendor_daemon`),
  		KEY `idx_feature_name` (`feature_name`),
  		KEY `idx_username` (`username`),
  		KEY `idx_hostname` (`hostname`),
  		KEY `idx_status` (`status`))
		ENGINE=InnoDB");

	if (!db_table_exists('lic_services_feature_history')) {
		db_execute('CREATE TABLE lic_services_feature_history LIKE lic_services_feature_history_template;');
		db_execute('ALTER TABLE lic_services_feature_history ENGINE=InnoDB');
	}

	db_execute("CREATE TABLE IF NOT EXISTS `lic_services_feature_history_mapping` (
		`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		`jobid` bigint(20) unsigned NOT NULL,
		`indexid` int(10) NOT NULL,
		`clusterid` int(10) NOT NULL,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`exec_host` varchar(64) DEFAULT NULL,
		`history_event_id` int(15) DEFAULT NULL,
		`tokens_released_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`id`),
		UNIQUE KEY `idx_jobid` (`jobid`,`indexid`,`clusterid`,`submit_time`,`history_event_id`),
		KEY `idx_event_id` (`history_event_id`))
		ENGINE=InnoDB");
}

function lichist_draw_navigation_text ($nav) {
        $nav['grid_lichist.php:'] = array('title' => 'License History', 'mapping' => 'grid_bjobs.php:', 'url' => 'grid_lichist.php', 'level' => '2');

	return $nav;
}

function lichist_jobs_tabs($tabs) {
	global $job;


	// Show this all the time, irrespective of whether or not we think the job
	// actually has checked out any licenses.
	if ($job['stat'] == 'RUNNING' || $job['stat'] == 'DONE' || $job['stat'] == 'EXIT' || $job['stat'] == 'USUSP' ||
			$job['stat'] == 'SSUSP') {
		$total_up_license_services = db_fetch_cell("SELECT count(*) FROM lic_services WHERE disabled=''");
		if( $total_up_license_services > 0) {
			$tabs['lichist'] = 'License Usage';
		}
	}

	return $tabs;
}

function lichist_jobs_show_tab() {
	global $config;

	if ($_REQUEST['tab'] == 'lichist') {
		include('./plugins/lichist/grid_lichist.php');

	}
}

?>
