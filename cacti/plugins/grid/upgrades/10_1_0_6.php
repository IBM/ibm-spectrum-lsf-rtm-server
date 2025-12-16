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

function upgrade_to_10_1_0_6() {
    global $config;

    include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.6 ...', true, 'UPGRADE');

	cacti_log('NOTE: Importing RTM templates for 10.1.0.6 ...', true, 'UPGRADE');

	$grid_templates = array(
		"1" => array (
			'value' => 'GRID - Cluster Effective Utilization',
			'name' => 'cacti_graph_template_grid_-_cluster_effective_utilization.xml'
		),
		"2" => array (
			'value' => 'GRID - Cluster/Host Effective UT',
			'name' => 'cacti_graph_template_grid_-_clusterhost_effective_ut.xml'
		),
		"3" => array (
			'value' => 'GRID - Host Group Stats',
			'name' => 'cacti_data_query_grid_-_host_group_stats.xml'
		),
		"5" => array (
			'value' => 'Grid Summary',
			'name' => 'cacti_host_template_grid_summary.xml'
		)
	);

	foreach($grid_templates as $grid_template) {
		if (file_exists($config['base_path'] . '/plugins/grid/templates/upgrades/10_1_0_6/' . $grid_template['name'])) {
			cacti_log("NOTE: Importing " . $grid_template['value'], true, 'UPGRADE');
			$results = rtm_do_import($config['base_path'] . '/plugins/grid/templates/upgrades/10_1_0_6/' . $grid_template['name']);
		}
	}

	cacti_log('NOTE: Templates Import Complete.', true, 'UPGRADE');

	$realm_id = db_fetch_cell("SELECT id FROM plugin_realms WHERE plugin = 'aggregate' AND file = 'color_templates.php,color_templates_items.php,aggregate_templates.php,aggregate_graphs.php,aggregate_items.php'");
	$realm_id = $realm_id + 100;
	execute_sql("Add user auth realm of Aggregate Administrator for Admin", "REPLACE INTO `user_auth_realm` VALUES ('$realm_id',1);");

	//For SUP#189661
	execute_sql("Update alert normal user realm", "UPDATE plugin_realms SET file='grid_alarmdb.php' WHERE file='gridalarms_alarm.php,grid_alarmdb.php';");
	execute_sql("Append user auth realm of Grid Administrator for build-in Admin", "REPLACE INTO `user_auth_realm` VALUES ('44',1);");

	/* SUP#189411: Update auto-scale for Graph: GRID - Cluster/Host Available Memory
	 * comment code below to avoid some customer modify auto-scale by self */
	//execute_sql("Enable auto-scale of graph 'GRID - Cluster/Host Available Memory'", "UPDATE graph_templates_graph gtg JOIN graph_templates gt ON gtg.graph_template_id=gt.id SET auto_scale='on' WHERE gt.hash='32ef20689c25cfe9c82a905ef79235c7'");
}
