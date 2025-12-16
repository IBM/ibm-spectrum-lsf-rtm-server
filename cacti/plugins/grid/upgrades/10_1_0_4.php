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

function upgrade_to_10_1_0_4() {
	global $config;

	include_once($config['base_path'] . '/lib/rtm_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');

	cacti_log('NOTE: Upgrading grid to v10.1.0.4 ...', true, 'UPGRADE');

    $column_arr= array(
		'lsf_envdir' => "ADD COLUMN `lsf_envdir` varchar(255) NOT NULL DEFAULT '' AFTER disabled",
		'advanced_enabled' => "ADD COLUMN `advanced_enabled` char(2) NOT NULL DEFAULT '' AFTER disabled",
		'lsf_strict_checking' => "ADD COLUMN `lsf_strict_checking` char(3) DEFAULT 'N' AFTER disabled"
		);
	modify_column("grid_projects", "numJOBS", "MODIFY COLUMN `numJOBS` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'num slots of this project', ADD COLUMN `pendJOBS` int(10) unsigned NOT NULL DEFAULT '0' AFTER `numJOBS`, ADD COLUMN `runJOBS` int(10) unsigned NOT NULL DEFAULT '0' AFTER `pendJOBS`, ADD COLUMN `totalJOBS` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'num jobs of this project' AFTER `runJOBS`;");
	execute_sql("Set defalt page number in Grid", "REPLACE INTO `grid_settings` VALUES('1', 'default_grid_jobs_pageno', 'on');");

	execute_sql("Change old 'Running Jobs' to 'Running Job Slots' in graph_templates_graph table",
		"UPDATE graph_templates_graph
		SET title = REPLACE(title,'Running Jobs','Running Job Slots'), title_cache = REPLACE(title_cache,'Running Jobs','Running Job Slots')
		WHERE graph_template_id IN ( SELECT id FROM graph_templates WHERE hash = '188ae2fd9e61259060b91edef5de3e89');");
	execute_sql("Change old 'Pending Jobs' to 'Pending Job Slots' in graph_templates_graph table",
		"UPDATE graph_templates_graph
		SET title = REPLACE(title,'Pending Jobs','Pending Job Slots'), title_cache = REPLACE(title_cache,'Pending Jobs','Pending Job Slots')
		WHERE graph_template_id IN ( SELECT id FROM graph_templates WHERE hash = '7a072a0ad4e8fe22d222e67511225785');");
	execute_sql("Delete All option of Number of Records to Display in Grid Settings", "DELETE FROM grid_settings WHERE name='grid_records' AND value>=9999999;");
	/*For GPU info*/
	create_table("grid_jobs_gpu_rusage", "CREATE TABLE IF NOT EXISTS `grid_jobs_gpu_rusage` (
		`id` bigint(8) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`jobid` bigint(20) NOT NULL DEFAULT '0',
		`indexid` int(10) unsigned NOT NULL DEFAULT '0',
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`host` varchar(64) NOT NULL DEFAULT '',
		`gpu_id` smallint(5) NOT NULL DEFAULT '0',
		`exec_time` float NOT NULL DEFAULT '0',
		`energy` float NOT NULL DEFAULT '0',
		`sm_ut_avg` float NOT NULL DEFAULT '0',
		`sm_ut_max` float NOT NULL DEFAULT '0',
		`sm_ut_min` float NOT NULL DEFAULT '0',
		`mem_ut_avg` float NOT NULL DEFAULT '0',
		`mem_ut_max` float NOT NULL DEFAULT '0',
		`mem_ut_min` float NOT NULL DEFAULT '0',
		`gpu_mused_max` float NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`),
		UNIQUE KEY `cid_jid_idx_subtime_sttime_hname_gid` (`clusterid`,`jobid`,`indexid`,`submit_time`,`start_time`,`host`,`gpu_id`)
		) ENGINE=InnoDB");

	cacti_log('NOTE: Importing RTM templates for 10.1.0.4 ...', true, 'UPGRADE');

	$grid_templates = array(
		"1" => array (
			'value' => 'GRID - Projects - All - Graphs',
			'name' => 'cacti_data_query_grid_-_projects_-_all_-_graphs.xml'
		),
		"2" => array (
			'value' => 'Grid Summary',
			'name' => 'cacti_host_template_grid_summary.xml'
		),
		"3" => array (
			'value' => 'GPU Memory Utilization',
			'name' => 'cacti_elim_template_gpu_memory_utilization.xml'
		),
		"4" => array (
			'value' => 'GPU Utilization',
			'name' => 'cacti_elim_template_gpu_utilization.xml'
		),
		"5" => array (
			'value' => 'Shared GPU Memory Utilization',
			'name' => 'cacti_elim_template_shared_gpu_memory_utilization.xml'
		),
		"6" => array (
			'value' => 'Shared GPU Utilization',
			'name' => 'cacti_elim_template_shared_gpu_utilization.xml'
		),
		"7" => array (
			'value' => 'GRID - LSF Host Info Requests',
			'name' => 'cacti_graph_template_grid_-_lsf_host_info_requests.xml'
		),
		"8" => array (
			'value' => 'GRID - LSF Host Match Criteria',
			'name' => 'cacti_graph_template_grid_-_lsf_host_match_criteria.xml'
		),
		"9" => array (
			'value' => 'GRID - LSF Job Buckets',
			'name' => 'cacti_graph_template_grid_-_lsf_job_buckets.xml'
		),
		"10" => array (
			'value' => 'GRID - LSF Job Info Requests',
			'name' => 'cacti_graph_template_grid_-_lsf_job_info_requests.xml'
		),
		"11" => array (
			'value' => 'GRID - LSF Job Scheduling Interval',
			'name' => 'cacti_graph_template_grid_-_lsf_job_scheduling_interval.xml'
		),
		"12" => array (
			'value' => 'GRID - LSF Jobs Completed',
			'name' => 'cacti_graph_template_grid_-_lsf_jobs_completed.xml'
		),
		"13" => array (
			'value' => 'GRID - LSF Jobs Dispatched',
			'name' => 'cacti_graph_template_grid_-_lsf_jobs_dispatched.xml'
		),
		"14" => array (
			'value' => 'GRID - LSF Jobs Submitted',
			'name' => 'cacti_graph_template_grid_-_lsf_jobs_submitted.xml'
		),
		"15" => array (
			'value' => 'GRID - LSF Job Submit Requests',
			'name' => 'cacti_graph_template_grid_-_lsf_job_submit_requests.xml'
		),
		"16" => array (
			'value' => 'GRID - LSF MBatchD Requests',
			'name' => 'cacti_graph_template_grid_-_lsf_mbatchd_requests.xml'
		),
		"17" => array (
			'value' => 'GRID - LSF MBD File Descriptor Usage',
			'name' => 'cacti_graph_template_grid_-_lsf_mbd_file_descriptor_usage.xml'
		),
		"18" => array (
			'value' => 'GRID - LSF Queue Info Requests',
			'name' => 'cacti_graph_template_grid_-_lsf_queue_info_requests.xml'
		),
	);

	foreach($grid_templates as $grid_template) {
		if (file_exists(dirname(__FILE__) . "/../templates/upgrades/10_1_0_4/" . $grid_template['name'])) {
			cacti_log('NOTE: Importing ' . $grid_template['value'], true, 'UPGRADE');
			$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_1_0_4/" . $grid_template['name']);
		}
	}

	cacti_log('NOTE: Template import complete', true, 'UPGRADE');

	//Fix #168901
	execute_sql("Insert a space to fix input_stirng field of the DIM 'GRID - Cluster Pending by Pending Reason'", "UPDATE data_input SET input_string='<path_cacti>/scripts/ss_grid_preason.php ss_grid_preason <clusterid> \"<reason>\"' WHERE hash='5036ba4f10baf24c1bd8ab0ed18bae82'");
	execute_sql("Fix 'reason' field desc of the DIM 'GRID - Cluster Pending by Pending Reason'", "UPDATE data_input_fields SET name='The substring match on Pending Reason. No special characters.' WHERE hash='d9633077c232574856b0fa057f70f318' AND name LIKE '%space%'");

	$poller_data = db_fetch_assoc("SELECT local_data_id AS id FROM data_template_data dtd JOIN data_input di ON dtd.data_input_id=di.id WHERE dtd.local_data_id <> 0 AND di.hash='5036ba4f10baf24c1bd8ab0ed18bae82'");
	$poller_items   = array();
	$local_data_ids = array();
	$count = 0;
	cacti_log('NOTE: ' . cacti_sizeof($poller_data) . ' Data Sources need to be pushed to poller cache.', true, 'UPGRADE');
	if (cacti_sizeof($poller_data) > 0) {
		foreach ($poller_data as $data) {
				$poller_items = array_merge($poller_items, update_poller_cache($data["id"]));
				$count++;
				if($count%100 == 0) {
					cacti_log('NOTE: '. $count . " (".  round($count/cacti_sizeof($poller_data), 2)*100 . '%) Data Sources Updated', true, 'UPGRADE');
				}
		}
		cacti_log('NOTE: ' . cacti_sizeof($poller_data) . ' (100%) Data Sources Updated', true, 'UPGRADE');

		/* .. prior to recreating everything from scratch */
		poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);
	}
}
