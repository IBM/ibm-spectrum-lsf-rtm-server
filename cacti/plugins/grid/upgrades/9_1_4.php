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

function upgrade_to_9_1_4() {
	global $system_type, $config;
	global $base_path, $php_bin, $path_web, $path_grid;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading grid to v9.1.4 ...', true, 'UPGRADE');

	$base_path = read_config_option("path_webroot");
	$php_bin   = read_config_option("path_php_binary");
	$path_web  = read_config_option("path_webroot");
	$path_grid = $path_web . "/plugins/grid";

	if (!db_column_exists('grid_clusters_benchmarks', 'pjob_submitTime')) {
		execute_sql ("change name",
			"ALTER TABLE `grid_clusters_benchmarks` CHANGE `pjob_submitTime` `pjob_bsubTime` double default NULL");
	}

	if (!db_column_exists('grid_clusters_benchmark_summary', 'pjob_submitTime')) {
		execute_sql ("change name",
			"ALTER TABLE `grid_clusters_benchmark_summary`
			CHANGE `pjob_submitTime` `pjob_bsubTime` double unsigned default '0'");
	}

	$benchmark_snmp_query_id = db_fetch_cell("select id from snmp_query where hash='df901648b4a72d6efab70f851273b9ea'");

	if (!empty($benchmark_snmp_query_id)) {
		// 1) Add Benchmark data query to host template 'Grid Summary'
		execute_sql("Add Benchmark data query to host template 'Grid Summary'",
			"REPLACE INTO host_template_snmp_query
			SELECT ht.id, sq.id
			FROM host_template AS ht, snmp_query AS sq
			WHERE ht.hash='d8ff1374e732012338d9cd47b9da18d4'
			AND sq.hash='df901648b4a72d6efab70f851273b9ea'");

		// 2) Add benchmark data query to all existing grid summary devices
		$grid_summary_devices = db_fetch_assoc("SELECT host.id
			FROM host, host_template
			WHERE host.host_template_id = host_template.id
			AND host_template.hash='d8ff1374e732012338d9cd47b9da18d4'");

		if (cacti_sizeof($grid_summary_devices)) {
			foreach($grid_summary_devices as $grid_summary_device) {
				execute_sql("Add benchmark data query to existing grid summary devices",
					"REPLACE INTO host_snmp_query
					(host_id, snmp_query_id, sort_field, title_format, reindex_method)
					VALUES ('" . $grid_summary_device["id"] . "','$benchmark_snmp_query_id', 'benchmarkId', '|query_benchmarkId|', 0)");
			}
		}
	}

	//-----Support LSF HPC Allocation Feature
	if (!db_column_exists('grid_jobs', 'max_allocated_processes')) {
		execute_sql ("add column",
			"ALTER TABLE grid_jobs
			ADD COLUMN `max_allocated_processes` int(10) unsigned NOT NULL default '0'
			COMMENT 'job level allocated slot' AFTER `num_cpus`");
	}

	if (!db_column_exists('grid_jobs_finished', 'max_allocated_processes')) {
		execute_sql ("add column",
			"ALTER TABLE grid_jobs_finished
			ADD COLUMN `max_allocated_processes` int(10) unsigned NOT NULL default '0'
			COMMENT 'job level allocated slot' AFTER `num_cpus`");
	}

	if (!db_column_exists('grid_jobs_rusage', 'num_cpus')) {
		execute_sql ("add column",
			"ALTER TABLE grid_jobs_rusage
			ADD COLUMN `num_cpus` int(10) unsigned NOT NULL default '0'
			COMMENT 'job level allocated slot' after `nthreads`");
	}

	if (!db_column_exists('grid_jobs_host_rusage', 'processes')) {
		execute_sql ("add column",
			"ALTER TABLE grid_jobs_host_rusage
			ADD COLUMN `processes` int(11) NOT NULL default '0'
			COMMENT 'job host level allocated slot'");
	}

	//-----fix 32977: The base time is not working in Grid Alert
	modify_column('gridalarms_alarm', 'base_time',
		"MODIFY COLUMN `base_time` int(10) unsigned default '0'");

	modify_column('gridalarms_template', 'base_time',
		"MODIFY COLUMN `base_time` int(10) unsigned default '0'");

	//-----	Append other db schema change
	add_index('data_template_rrd', 'duplicate_dsname_contraint',
		"ADD UNIQUE INDEX `duplicate_dsname_contraint` (`local_data_id`,`data_source_name`,`data_template_id`)");

	create_table("grid_clusters_reportdata",
		"CREATE TABLE IF NOT EXISTS grid_clusters_reportdata (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`reportid` VARCHAR(20) NOT NULL DEFAULT '',
		`name` VARCHAR(20) NOT NULL DEFAULT '',
		`value` double NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`clusterid`,`reportid`,`name`))
		ENGINE=MEMORY
		COMMENT='cluster level reporting results table'");

	add_index('grid_jobs', 'stat_clusterid_exitInfo',
		"ADD INDEX `stat_clusterid_exitInfo` (`stat`,`clusterid`,`exitInfo`)");

	execute_sql('Drop grid_jobs stat_clusterid index',
		"ALTER TABLE grid_jobs DROP INDEX `stat_clusterid`");

	add_column("host_snmp_cache", "present",
		"ADD COLUMN `present` tinyint(4) NOT NULL default '1' AFTER `oid`");

	add_index("host_snmp_cache", "present",
		"ADD INDEX `present` (`present`)");

	modify_column("poller_item", "host_id",
		"MODIFY COLUMN `host_id` mediumint(8) unsigned NOT NULL default '0'");

	add_column("poller_item", "present",
		"ADD COLUMN `present` tinyint(4) NOT NULL default '1' AFTER `action`");

	add_index("poller_item", "present",
		"ADD INDEX `present` (`present`)");

	add_column("poller_reindex", "present",
		"ADD COLUMN `present` tinyint(4) NOT NULL default '1' AFTER `action`");

	add_index("poller_reindex", "present",
		"ADD INDEX `present` (`present`)");

	add_index("user_log", "username",
		"ADD INDEX `username` (`username`)");

	modify_column("grid_applications", "appName",
		"MODIFY COLUMN `appName` varchar(40) NOT NULL default ''");

	modify_column("grid_jobs", "app",
		"MODIFY COLUMN `app` varchar(40) NOT NULL default ''");

	add_index("grid_jobs", "app",
		"ADD INDEX `app` (`app`)");

	modify_column("grid_jobs_finished", "app",
		"MODIFY COLUMN `app` varchar(40) NOT NULL default ''");

	execute_sql("Modify table poller",
		"ALTER TABLE `poller`
		ENGINE=MEMORY");

	execute_sql("Modify table poller_time",
		"ALTER TABLE `poller_time`
		ENGINE=MEMORY");

	execute_sql("Modify table poller_command",
		"ALTER TABLE `poller_command`
		ENGINE=MEMORY");

	//Note: RTC bug 49238: tables poller_item, host_snmp_cache and poller_reindex modification have to be finished firstly.
	//      Or SQL errors related to those tables will happen in clog when recreating graph polling index.
	//-----append two new fields (pending, running) to all 9.1.3 sla graphs (GRID - Guarantee SLA Resource Usage, GRID - Guarantee Resource Pool Usage)
	$sla_data_templates = db_fetch_assoc("SELECT id,name FROM data_template WHERE hash in ('d8a1ddfc0a2d409084cb1408c741b964', '77eb7246750c905ae25ba81e8d5633cd')");
	if (cacti_sizeof($sla_data_templates)) {
		foreach($sla_data_templates as $sla_data_template) {
			$command_string = read_config_option('path_php_binary') . " " . $config['base_path'] . "/plugins/grid/grid_rrd_datasource_add.php --ds='pending:GAUGE:600:0:0;running:GAUGE:600:0:0' --data-template-id=" . $sla_data_template['id'];
			$output = shell_exec($command_string);

			cacti_log("NOTE: SLA Data Template:" . html_escape($sla_data_template['name']) . ' added', true, 'UPGRADE');
		}
	}

	// append all created sla graphs to their associated cluster summary tree
	$query2 = "SELECT graph_local.id, graph_local.host_id, graph_templates.hash
		FROM graph_local, graph_templates
		WHERE graph_local.graph_template_id=graph_templates.id
		AND graph_templates.hash in ('a043b12e1a219e56e51122fc457ff386','afbb7c8839286bf03309a0ccefd79f40');";

	$result2 = db_fetch_assoc($query2);

	// For each graph instance across 1 or more clusters
	if (cacti_sizeof($result2)) {
		foreach ($result2 as $result) {
			if (!empty($result['host_id'])) {
				// Get the graph tree for that cluster
				$query3  = "SELECT cacti_tree FROM grid_clusters WHERE cacti_host=" . $result["host_id"];
				$result3 = db_fetch_cell($query3);

				// Get the SLA header nodes in that cluster's tree - Guaranteed SLAs; Guaranteed ResPools
				if ($result['hash'] == 'a043b12e1a219e56e51122fc457ff386') {
					$tree_title = 'Guaranteed SLAs';
				}
				if ($result['hash'] == 'afbb7c8839286bf03309a0ccefd79f40') {
					$tree_title = 'Guaranteed ResPools';
				}

				$query4  = "SELECT id FROM graph_tree_items WHERE graph_tree_id=" . $result3 . " AND title='$tree_title'";
				$result4 = db_fetch_cell($query4);

				// Add the graph instance to this tree item id, under the Benchmark header node
				$cmd    = "$php_bin -q $base_path/cli/add_tree.php --type=node --node-type=graph --tree-id=" . $result3  . " --graph-id=" . $result['id'] . " --parent-node=" . $result4;
				$output = shell_exec($cmd);
			}
		}
	}

	cacti_log('NOTE: Importing RTM templates for 9.1.4 ...', true, 'UPGRADE');

	$RTM_templates = array(
		"1" => array (
			'value' => 'License Scheduler - Summary',
			'name' => 'cacti_host_template_license_scheduler_summary.xml'
		),
	);

	foreach($RTM_templates as $rtmtemplates) {
		if (file_exists($config["base_path"]."/templates/".$rtmtemplates['name'])) {
			cacti_log('NOTE: Importing ' . $rtmtemplates['value'], true, 'UPGRADE');
			$results = do_import($config["base_path"]."/templates/".$rtmtemplates['name']);
		}
	}

	cacti_log('NOTE: Templates Import Complete.', true, 'UPGRADE');
}

