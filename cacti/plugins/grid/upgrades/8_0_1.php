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

function upgrade_to_8_0_1() {
	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	modify_column("lic_daily_stats", "user", "ALTER TABLE `lic_daily_stats` MODIFY COLUMN `user` VARCHAR(64) NOT NULL DEFAULT '';");
	modify_column("lic_daily_stats", "host", "ALTER TABLE `lic_daily_stats` MODIFY COLUMN `host` VARCHAR(64) NOT NULL DEFAULT '';");
	add_index("lic_daily_stats", "interval_end", "ALTER TABLE `lic_daily_stats` ADD INDEX `interval_end`(`interval_end`);");
	add_index("lic_daily_stats", "date_recorded", "ALTER TABLE `lic_daily_stats` ADD INDEX `date_recorded`(`date_recorded`);");

	add_column("grid_jobs", "runtimeEstimation", "ALTER TABLE `grid_jobs`
		ADD COLUMN `runtimeEstimation` int(10) unsigned DEFAULT '0' AFTER `termTime`;");

	add_column("grid_jobs_finished", "runtimeEstimation", "ALTER TABLE `grid_jobs_finished`
		ADD COLUMN `runtimeEstimation` int(10) unsigned DEFAULT '0' AFTER `termTime`;");

	$RTM_templates = array();
	$RTM_templates[] = array (
                'value' => 'GRID - User Group - Memory Stats',
                'name' => 'cacti_graph_template_grid_-_user_group_-_memory_stats.xml'
	);
	$RTM_templates[] = array (
                'value' => 'GRID - User Group - Efficiency',
                'name' => 'cacti_graph_template_grid_-_user_group_-_efficiency.xml'
	);
	$RTM_templates[] = array (
                'value' => 'GRID - User Group - Total CPU',
                'name' => 'cacti_graph_template_grid_-_user_group_-_total_cpu.xml'
	);

	$data_queries  = array();
	$data_queries[] = array(
		"data_query_hash" => "64616d4b4353ac427ae5a1d6950e6264",
		"graph_templates"=> array(
			array(
				"graph_template_hash" => "20eb1e1528f2f6a76ff3e49db6ddc8fd",
				"snmp_query_graph_hash" => "8e257486cd24fdc7a4d30635e2c13b34",
				"name" => "GRID - User Group - Memory Stats",
				"snmp_query_graph_rrds" => array(
                                	array(
                                        "data_template_rrd_hash" => "8154e0d9c988b3432021e8e038cf2d46",
                                        "snmp_field_name" => "avg_mem"),
                                	array(
                                        "data_template_rrd_hash" => "ffde311eac3b6a8f5c4714692bf58631",
                                        "snmp_field_name" => "max_mem"),
				)
			),
			array(
				"graph_template_hash" => "31bd0c963db1bf52e2185f642824701c",
				"snmp_query_graph_hash" => "a19699b9bff958f1edc7a1a48b96bc6a",
				"name" => "GRID - User Group - Efficiency",
				"snmp_query_graph_rrds" => array(
									array(
                                        "data_template_rrd_hash" => "c11b2da08b29038ca7474bfbc818655c",
                                        "snmp_field_name" => "efficiency"),
				),
			),
			array(
				"graph_template_hash" => "9529969bfa3ca107adf82b36146eb367",
				"snmp_query_graph_hash" => "b87a1bc847104776d27bda24463b55d6",
				"name" => "GRID - User Group - Total CPU",
				"snmp_query_graph_rrds" => array(
									array(
										"data_template_rrd_hash" => "d65b61b0941eda47b2099753bbd163cf",
										"snmp_field_name" => "total_cpu"),
				)
			)
		),
	);
	print "Importing and binding RTM graph templates for v8.0-v8.0.1.\n";
	import_binding_data_queries_templates($RTM_templates, $data_queries);
	print "Binding graph tempaltes and dataquery complete.  for v8.0-v8.0.1\n";
}
