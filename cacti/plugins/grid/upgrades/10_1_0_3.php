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

function upgrade_to_10_1_0_3() {
    global $config;

	include_once($config['base_path'] . '/lib/rtm_functions.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.3 ...', true, 'UPGRADE');

	//queue level fairshare project db schema change
	modify_column("grid_queues_users_stats", "user_or_group", "MODIFY COLUMN `user_or_group` varchar(60) NOT NULL default '';");
	modify_column("grid_users_or_groups", "user_or_group", "MODIFY COLUMN `user_or_group` varchar(60) NOT NULL default '';");
	modify_column("grid_service_class_groups", "user_or_group", "MODIFY COLUMN `user_or_group` varchar(60) NOT NULL default '';");
	modify_column("grid_queues_shares", "user_or_group", "MODIFY COLUMN `user_or_group` varchar(60) NOT NULL default '';");
	modify_column("grid_queues_shares", "shareAcctPath", "MODIFY COLUMN `shareAcctPath` varchar(200) NOT NULL default '';");

	$column_arr = array(
		"parent_slots" => "ADD COLUMN `parent_slots` int(10) unsigned DEFAULT NULL AFTER `priority`",
		"leaf_share" => "ADD COLUMN `leaf_share` double DEFAULT NULL AFTER `parent_slots`",
		"pend_jobs" => "ADD COLUMN `pend_jobs` int(10) unsigned DEFAULT NULL AFTER `run_time`",
		"pend_slots" => "ADD COLUMN `pend_slots` int(10) unsigned DEFAULT NULL AFTER `pend_jobs`",
		"run_jobs" => "ADD COLUMN `run_jobs` int(10) unsigned DEFAULT NULL AFTER `pend_slots`",
		"run_slots" => "ADD COLUMN `run_slots` int(10) unsigned DEFAULT NULL AFTER `run_jobs`",
		"relative_share" => "ADD COLUMN `relative_share` double DEFAULT NULL AFTER `run_slots`",
		"slot_share" => "ADD COLUMN `slot_share` int(10) unsigned DEFAULT NULL AFTER `relative_share`",
	);
	$index_arr = array(
		'user_or_group' => 'ADD INDEX `user_or_group` (`user_or_group`)'
	);
	add_columns_indexes("grid_queues_shares", $column_arr, $index_arr);

	add_index("grid_jobs", "chargedSAAP", "ADD INDEX `chargedSAAP` (`chargedSAAP`);");

	execute_sql("Change grid_queues_shares to InnoDB", "ALTER TABLE `grid_queues_shares` ENGINE=InnoDB");

	add_column("grid_clusters", "exec_host_res_req", "ADD COLUMN `exec_host_res_req` varchar(512) NOT NULL default '' AFTER `perfmon_interval`;");

	cacti_log('NOTE: Importing RTM templates for 10.1.0.3 ...', true, 'UPGRADE');;

	$grid_templates = array(
		'1' => array (
			'value' => 'Queue - Fairshare Stats',
			'name' => 'cacti_data_query_grid_-_queue_-_fairshare_stats.xml'
		),
		'2' => array (
			'value' => 'Queue - Information',
			'name' => 'cacti_data_query_grid_-_queue_-_information.xml'
		),
		'3' => array (
			'value' => 'Grid Summary',
			'name' => 'cacti_host_template_grid_summary.xml'
		),
	);

	foreach($grid_templates as $grid_template) {
		if (file_exists(dirname(__FILE__) . '/../templates/upgrades/10_1_0_4/' . $grid_template['name'])) {
			cacti_log('NOTE: Importing ' . $grid_template['value'], true, 'UPGRADE');
			$results = rtm_do_import(dirname(__FILE__) . '/../templates/upgrades/10_1_0_4/' . $grid_template['name']);
		}
	}

	$fairshare_snmp_query_id = db_fetch_cell("select id from snmp_query where hash='cf395279d717d8a77e45d18dfd3af2bd';");
	if (!empty($fairshare_snmp_query_id)) {
		// 1) Add fairshare data query to host template 'Grid Summary'
		execute_sql("Add Queue Fairshare data query to host template 'Grid Summary'", "REPLACE INTO host_template_snmp_query
			select ht.id, sq.id from  host_template as ht, snmp_query as sq where ht.hash='d8ff1374e732012338d9cd47b9da18d4' and sq.hash='cf395279d717d8a77e45d18dfd3af2bd';");

		// 2) Add fairshare data query to all existing grid summary devices
		$grid_summary_devices=db_fetch_assoc("select host.id from host, host_template where host.host_template_id =host_template.id and host_template.hash='d8ff1374e732012338d9cd47b9da18d4';");
		if (cacti_sizeof($grid_summary_devices)) {
		    foreach($grid_summary_devices as $grid_summary_device) {
		    	execute_sql("Add Queue Fairshare data query to existing grid summary devices", "replace into host_snmp_query (host_id,snmp_query_id,sort_field, title_format,reindex_method) values ('"
		    		. $grid_summary_device["id"] . "','$fairshare_snmp_query_id', 'gridQtree', '|query_gridQtree|', 0);");
		    }
		}
	}

	//Fix #142510
	$poller_data = db_fetch_assoc("SELECT id
		FROM data_local
		WHERE data_template_id IN (SELECT id FROM data_template WHERE hash='27b2d020f95c181c92340418cf1422ad')");

	$poller_items   = array();
	$local_data_ids = array();
	$count = 0;

	cacti_log('NOTE: ' . cacti_sizeof($poller_data) . ' data sources needed to be updated', true, 'UPGRADE');

	if (cacti_sizeof($poller_data)) {
		foreach ($poller_data as $data) {
				$poller_items = array_merge($poller_items, update_poller_cache($data["id"]));
				$count++;
				if($count%100 == 0) {
					cacti_log('NOTE: ' . $count . ' ('.  round($count/cacti_sizeof($poller_data), 2)*100 . '%) Data Sources Updated', true, 'UPGRADE');
				}
		}

		cacti_log('NOTE: ' . cacti_sizeof($poller_data) . ' (100%) Data Sources Updated', true, 'UPGRADE');

		/* .. prior to recreating everything from scratch */
		poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);

	}

	$graph_template_id = db_fetch_cell("select id from graph_templates where hash='a638e75101dfd978e0bb2608a964249e';");
	// push_out_by_graph_template
	$graph_template_items = db_fetch_assoc("SELECT id FROM graph_templates_item WHERE local_graph_id=0 AND graph_template_id=$graph_template_id");

	cacti_log('NOTE: ' . cacti_sizeof($graph_template_items) . ' graph template items to be pushed out to instance.', true, 'UPGRADE');

	$count = 0;
	if (cacti_sizeof($graph_template_items)) {
		foreach($graph_template_items as $gti) {
			push_out_graph_item($gti['id']);
			$count++;
			if($count%100 == 0) {
				cacti_log('NOTE: ' . $count . ' ('.  round($count/cacti_sizeof($graph_template_items), 2)*100 . '%) graph template items have been pushed out to instance.', true, 'UPGRADE');
			}
		}
	}

	cacti_log('NOTE: ' . cacti_sizeof($graph_template_items) . ' (100%) graph template items have been pushed out to instance. Process complete.', true, 'UPGRADE');
	cacti_log('NOTE: Fix of #142510 is done!', true, 'UPGRADE');
}

