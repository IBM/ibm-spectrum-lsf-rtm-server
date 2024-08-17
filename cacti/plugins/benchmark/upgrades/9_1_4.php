<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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
	global $config;

	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	if (!db_column_exists('grid_clusters_benchmarks', 'pjob_bsubTime')) {
		execute_sql ("change name",
			"ALTER TABLE `grid_clusters_benchmarks` CHANGE `pjob_submitTime` `pjob_bsubTime` double default NULL");
	}

	if (!db_column_exists('grid_clusters_benchmark_summary', 'pjob_bsubTime')) {
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
}

