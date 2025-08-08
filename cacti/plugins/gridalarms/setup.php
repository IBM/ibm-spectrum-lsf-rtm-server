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

function plugin_gridalarms_install($upgrade = false) {
	global $config;

    if (version_compare($config['cacti_version'], '1.2') < 0) {
        return false;
    }

	// General Interactions
	api_plugin_remove_hooks('gridalarms'); //fix 206275
	api_plugin_register_hook('gridalarms', 'config_arrays', 'gridalarms_config_arrays', 'includes/settings.php');
	api_plugin_register_hook('gridalarms', 'draw_navigation_text', 'gridalarms_draw_navigation_text', 'setup.php');
	api_plugin_register_hook('gridalarms', 'poller_bottom', 'gridalarms_poller_bottom', 'setup.php');
	api_plugin_register_hook('gridalarms', 'page_bottom', 'gridalarms_bottom_footer', 'setup.php');
	api_plugin_register_hook('gridalarms', 'config_settings', 'gridalarms_config_settings', 'includes/settings.php');
	api_plugin_register_hook('gridalarms', 'grid_tab_down', 'gridalarms_grid_tab_down',	'setup.php');
	api_plugin_register_hook('gridalarms', 'grid_menu',	'gridalarms_grid_menu',	'setup.php');
	api_plugin_register_hook('gridalarms', 'rtm_landing_page', 'gridalarms_rtm_landing_page', 'setup.php');

	// Permissions Levels
	api_plugin_register_realm('gridalarms', 'grid_alarmdb.php', 'View Grid Alerts', 1);
	api_plugin_register_realm('gridalarms',
		'gridalarms_alarm.php,gridalarms_templates.php,gridalarms_alarm_edit.php,gridalarms_template_edit.php',
		'Configure Grid Alerts', 1);

	// Notification List Extension
	api_plugin_register_hook('gridalarms', 'notify_list_tabs', 'gridalarms_notify_list_tabs', 'includes/notify.php');
	api_plugin_register_hook('gridalarms', 'notify_list_save', 'gridalarms_notify_list_save', 'includes/notify.php');
	api_plugin_register_hook('gridalarms', 'notify_list_form_confirm', 'gridalarms_notify_list_form_confirm', 'includes/notify.php');
	api_plugin_register_hook('gridalarms', 'notify_list_display', 'gridalarms_notify_list_display', 'includes/notify.php');

	// Allow Modifying Thold form logic, javascript and save functions
	api_plugin_register_hook('gridalarms', 'thold_edit_save_thold', 'gridalarms_th_edit_save_thold', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_edit_form_array', 'gridalarms_th_edit_form_array', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_edit_javascript', 'gridalarms_th_edit_javascript', 'includes/thold.php');

	// Allow Modifying Thold Template form logic, javascript and save functions
	api_plugin_register_hook('gridalarms', 'thold_template_edit_save_thold', 'gridalarms_tht_edit_save_thold', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_template_edit_form_array', 'gridalarms_th_edit_form_array', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_template_edit_javascript', 'gridalarms_th_edit_javascript', 'includes/thold.php');

	// Allow additional actions based upon Threshold breaches
	api_plugin_register_hook('gridalarms', 'thold_action', 'gridalarms_thold_action', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_replacement_text', 'gridalarms_thold_replacement_text', 'includes/thold.php');

	// Allow additional actions when viewing thold_graph.php
	api_plugin_register_hook('gridalarms', 'thold_graph_tabs', 'gridalarms_thold_graph_tabs', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_graph_view', 'gridalarms_thold_graph_view', 'includes/thold.php');
	api_plugin_register_hook('gridalarms', 'thold_graph_actions_url', 'gridalarms_thold_graph_actions_url', 'includes/thold.php');

	// Replace data source names and thold information when saved
	api_plugin_register_hook('gridalarms', 'expand_title', 'gridalarms_expand_title', 'includes/thold.php');

	// Common JavaScript and CSS
	api_plugin_register_hook('gridalarms', 'page_head', 'gridalarms_page_head', 'setup.php', 1);

	// Remove/Disable cluster related alarms
	api_plugin_register_hook('gridalarms', 'grid_cluster_remove', 'gridalarms_grid_cluster_remove', 'setup.php');

	if ($upgrade) {
        plugin_gridalarms_upgrade();

        if (api_plugin_is_enabled('gridalarms')) {
            api_plugin_enable_hooks('gridalarms');
        }
	} else {
		include_once($config['base_path'] . '/plugins/gridalarms/includes/database.php');

		gridalarms_setup_new_tables();
	}
}

function gridalarms_rtm_landing_page() {
	global $config;
?>
			<li style='flex-grow:1;align-self:auto;flex-basis:30%;padding:5px;'>
				<table class='cactiTable'>
					<tr class='cactiTableTitle' style='width: 100%;'>
						<td class='landing_page_tile_large'><?php print __('Alerts', 'gridalarms');?></td>
					</tr>
					<tr>
						<td class='print_underline'>&nbsp;</td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure Grid Alerts', 'gridalarms');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Management > Alerts', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/gridalarms/gridalarms_alarm.php'><?php print __('Set up a new grid alert', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Templates > Alert Templates', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/gridalarms/gridalarms_templates.php'><?php print __('Create a new grid alert template', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure Threshold Alerts', 'gridalarms');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Management > Thresholds', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/thold/thold.php'><?php print __('Set up a new threshold alert', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Templates > Threshold Templates', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/thold/thold_templates.php'><?php print __('Create a new threshold alert template', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Configure Syslog Alerts', 'gridalarms');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Configuration > Settings > Syslog', 'gridalarms');?>'><a href='<?php print $config['url_path']?>settings.php?tab=syslog'><?php print __('Set up syslog monitoring on a cluster', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Configuration > Settings > General', 'gridalarms');?>'><a href='<?php print $config['url_path']?>settings.php?tab=general'><?php print __('Set up RTM to write to syslog', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Console > Syslog Settings > Alert Rules', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/syslog/syslog_alerts.php'><?php print __('Create syslog alerts', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableHeader'>
						<td class='landing_page_tile_medium'><?php print __('Monitor Alerts', 'gridalarms');?></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Grid > Dashboards > Alerts', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/gridalarms/grid_alarmdb.php'><?php print __('View triggered grid alerts', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Thold > Thresholds', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/thold/thold_graph.php?tab=thold'><?php print __('View triggered thresholds', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Thold > Host Status', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/thold/thold_graph.php?tab=hoststat'><?php print __('View hosts monitored by thresholds', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Syslog > Syslogs', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/syslog/syslog.php?tab=syslog'><?php print __('View syslog messages', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Syslog > Alert Log', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/syslog/syslog.php?tab=alerts'><?php print __('View syslog alert history', 'gridalarms');?></a></td>
					</tr>
					<tr class='tableRow'>
						<td class='landing_page_tile_small' title='<?php print __esc('Grid > Dashboards > Host', 'gridalarms');?>'><a href='<?php print $config['url_path']?>plugins/grid/grid_summary.php?tab=alarm'><?php print __('View host level alerts', 'gridalarms');?></a></td>
					</tr>
				</table>
			</li>
<?php
}

function gridalarms_bottom_footer() {
	if (preg_match('(gridalarms|grid_alarmdb)', basename($_SERVER['SCRIPT_NAME']))) {
		print "<div id='gridalarms_dialog' style='display:none;'></div>\n";
	}
}

function gridalarms_page_head() {
	global $config;

	print "<link href='" . $config['url_path'] . "plugins/gridalarms/includes/main.css' type='text/css' rel='stylesheet'>";
	print get_md5_include_js('plugins/gridalarms/includes/common.js');
	if (strstr($_SERVER['SCRIPT_NAME'], 'plugins' . DIRECTORY_SEPARATOR . 'gridalarms' . DIRECTORY_SEPARATOR) === false)
		return;
}

function plugin_gridalarms_check_config() {
	plugin_gridalarms_upgrade();
	return true;
}

function plugin_gridalarms_upgrade() {
    global $config, $rtm_version_numbers;

	include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
	include_once($config['library_path'] . '/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/includes/database.php');

	$gridalarms_version = read_config_option('gridalarms_version');
	if (!empty($gridalarms_version)) {
		$gridalarms_version = db_fetch_cell('SELECT version FROM plugin_config WHERE directory = "gridalarms"');
	}

	$gridalarms_current_version = get_gridalarms_version();

	gridalarms_setup_new_tables();

	/* from Pre-9.1 to 9.1, upgrade process is background */
	if (isset($gridalarms_version)) {
		rtm_plugin_upgrade_ga($gridalarms_version, $gridalarms_current_version, 'gridalarms');
	}

	db_execute_prepared('UPDATE plugin_realms
		SET file = ?
		WHERE file LIKE "%gridalarms_alarm.php%"',
		array('gridalarms_alarm.php,gridalarms_templates.php,gridalarms_alarm_edit.php,gridalarms_template_edit.php'));
}

function plugin_gridalarms_uninstall() {
	return true;
}

function plugin_gridalarms_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/gridalarms/INFO', true);
	return $info['info'];
}

function gridalarms_poller_bottom() {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q ' . $config['base_path'] . '/plugins/gridalarms/poller_gridalarms.php -M';

	exec_background($command_string, $extra_args);

	return true;
}

function gridalarms_draw_navigation_text ($nav) {
	/* view alarms from grid tab */
	$nav['grid_alarmdb.php:'] = array(
		'title'   => __('Alert Dashboard', 'gridalarms'),
		'mapping' => 'grid_default.php:',
		'url'     => 'gridalarms_alarm.php',
		'level'   => '1'
	);

	$nav['grid_alarmdb.php:details'] = array(
		'title'   => __('Breached Items', 'gridalarms'),
		'mapping' => 'grid_default.php:',
		'url'     => 'grid_alarmdb.php',
		'level'   => '1'
	);

	$nav['grid_alarmdb.php:acknowledge'] = array(
		'title'   => __('Acknowledge Alert', 'gridalarms'),
		'mapping' => 'grid_default.php:',
		'url'     => 'grid_alarmdb.php',
		'level'   => '1'
	);

	/* view alarms from console */
	$nav['gridalarms_alarm.php:'] = array(
		'title'   => __('Alerts', 'gridalarms'),
		'mapping' => 'index.php:',
		'url'     => 'gridalarms_alarm.php',
		'level'   => '1'
	);

	$nav['gridalarms_alarm.php:actions'] = array(
		'title'   => __('(Actions)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:',
		'url'     => '',
		'level'   => '2'
	);

	$nav['gridalarms_alarm.php:details'] = array(
		'title'   => __('Breached Items', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:',
		'url'     => '',
		'level'   => '2'
	);

	$nav['gridalarms_alarm.php:acknowledge'] = array(
		'title'   => __('Acknowledge Alert', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:',
		'url'     => '',
		'level'   => '2'
	);

	/* edit alarms from console */
	$nav['gridalarms_alarm_edit.php:'] = array(
		'title'   => __('(Edit)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:',
		'url'     => '',
		'level'   => '2'
	);

	$nav['gridalarms_alarm_edit.php:item_remove'] = array(
		'title'   => __('Remove Data Source Item', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:select_template'] = array(
		'title'   => __('Select Alert Template', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:',
		'url'     => '',
		'level'   => '2'
	);

	$nav['gridalarms_alarm_edit.php:expression_edit'] = array(
		'title'   => __('Data Source Item', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:save'] = array(
		'title'   => __('(Save)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:gridalarms_alarm_edit:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:metric_edit'] = array(
		'title'   => __('Metric Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:item_edit'] = array(
		'title'   => __('Data Source Item Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:layout_edit'] = array(
		'title'   => __('Alert Layout Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:layout_add'] = array(
		'title'   => __('Alert Layout Column Add', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:layout_remove'] = array(
		'title'   => __('Alert Layout Column Remove', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_alarm_edit.php:input_edit'] = array(
		'title'   => __('Custom Data Source Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_alarm.php:,gridalarms_alarm_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	/* view alert templates from console */
	$nav['gridalarms_templates.php:'] = array(
		'title'   => __('Alert Templates', 'gridalarms'),
		'mapping' => 'index.php:',
		'url'     => 'gridalarms_templates.php',
		'level'   => '1'
	);

	$nav['gridalarms_templates.php:actions'] = array(
		'title'   => __('(Actions)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:',
		'url'     => '',
		'level'   => '2'
	);

	/* edit alert templates from console */
	$nav['gridalarms_template_edit.php:'] = array(
		'title'   => __('(Edit)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:',
		'url'     => 'gridalarms_template_edit.php',
		'level'   => '2'
	);

	$nav['gridalarms_template_edit.php:item_remove'] = array(
		'title'   => __('Remove Data Source Item', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '4'
	);

	$nav['gridalarms_template_edit.php:expression_edit'] = array(
		'title'   => __('Data Source Item', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:save'] = array(
		'title'   => __('(Save)', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:',
		'url'     => 'gridalarms_template_edit.php',
		'level'   => '2'
	);

	$nav['gridalarms_template_edit.php:metric_edit'] = array(
		'title'   => __('Metric Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:item_edit'] = array(
		'title'   => __('Data Source Item Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:layout_edit'] = array(
		'title'   => __('Alert Layout Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:layout_add'] = array(
		'title'   => __('Alert Layout Column Add', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:layout_remove'] = array(
		'title'   => __('Alert Layout Column Remove', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	$nav['gridalarms_template_edit.php:input_edit'] = array(
		'title'   => __('Custom Data Input Edit', 'gridalarms'),
		'mapping' => 'index.php:,gridalarms_templates.php:,gridalarms_template_edit.php:',
		'url'     => '',
		'level'   => '3'
	);

	/* edit notification lists from console */

	return $nav;
}

function gridalarms_grid_tab_down($pages_grid_tab_down) {
	$gridalarms_pages_grid_tab_down=array(
		'grid_alarmdb.php' => ''
		);
	$pages_grid_tab_down += array("gridalarms" => $gridalarms_pages_grid_tab_down);
	return $pages_grid_tab_down;
}

function gridalarms_grid_menu($grid_menu = array()) {
  $gridalarms_menu = array(
        __('Dashboards') => array(
			'plugins/gridalarms/grid_alarmdb.php'  => __('Alerts')
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
						$menu2[__('Dashboards')]['plugins/gridalarms/grid_alarmdb.php?tab=alarms'] = __('Alerts');
					}
				}
			}
		}
		return $menu2;
	}

	return $gridalarms_menu;
}

/**
 * Keep it just for compatibility
 * @deprecated 10.2.0.1, prefer to use get_gridalarms_version or plugin_gridalarms_version
 * @return array include version, author,...
 */
function gridalarms_version() {
	return plugin_gridalarms_version();
}

/**
 * Get version number of plugin, or RTM version if plugin version is not defined
 * @return string version number
 */
function get_gridalarms_version() {
	$info = plugin_gridalarms_version();

	if (!empty($info) && isset($info['version'])) {
		return $info['version'];
	}

	return RTM_VERSION;
}

function gridalarms_grid_cluster_remove($cluster) {
	global $config;

	if (!isset($cluster) || !isset($cluster['clusterid']) || !isset($cluster['delete_type'])) {
		return $cluster;
	}

	db_execute_prepared('UPDATE gridalarms_template SET clusterid=0 WHERE clusterid=?', array($cluster['clusterid']));

	if ($cluster['delete_type'] == 1) {
		db_execute_prepared('UPDATE gridalarms_alarm SET alarm_enabled="off" WHERE clusterid=?', array($cluster['clusterid']));
	} else if ($cluster['delete_type'] == 2) {
		include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');

		$alarms = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_alarm
			WHERE clusterid = ?',
			array($cluster['clusterid']));

		if (!empty($alarms)) {
			foreach ($alarms as $alarm) {
				$del = $alarm['id'];
				api_alarm_remove($del);
			}
		}
	}

    return $cluster;
}
