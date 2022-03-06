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

function upgrade_to_10_2_0_11() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	$column_arr= array(
		'rtype' => "ADD COLUMN `rtype` TINYINT unsigned NOT NULL default '0' AFTER `clusterid`"
	);
	add_columns("grid_hostresources", $column_arr);
	$column_arr= array(
		'excl_resources' => "ADD COLUMN `excl_resources` varchar(255) NOT NULL default '' AFTER `resources`",
		'ngpus' => "ADD COLUMN `ngpus` int(10) unsigned NOT NULL DEFAULT '0' AFTER `nThreads`",
		'gMaxFactor' => "ADD COLUMN `gMaxFactor` float NOT NULL DEFAULT '0' AFTER `ngpus`",
		'gpu_shared_avg_mut' => "ADD COLUMN `gpu_shared_avg_mut` float NOT NULL DEFAULT '0' AFTER `gMaxFactor`",
		'gpu_shared_avg_ut' => "ADD COLUMN `gpu_shared_avg_ut` float NOT NULL DEFAULT '0' AFTER `gpu_shared_avg_mut`"
	);
	$index_arr = array(
		"clusterid_host" => "ADD INDEX `clusterid_host` (`clusterid`, `host`)"
	);
	//Do not use Cacti::db_update_table because db_update_table alter table column one by one
	add_columns_indexes("grid_hostinfo", $column_arr, $index_arr);

	$column_arr= array(
		'ngpus' => "ADD COLUMN `ngpus` int(10) unsigned NOT NULL DEFAULT '0' AFTER `numRESERVE`",
		'avail_shared_ngpus' => "ADD COLUMN `avail_shared_ngpus` int(10) unsigned NOT NULL default '0' AFTER `ngpus`",
		'avail_excl_ngpus' => "ADD COLUMN `avail_excl_ngpus` int(10) unsigned NOT NULL default '0' AFTER `avail_shared_ngpus`",
		'alloc_jsexcl_ngpus' => "ADD COLUMN `alloc_jsexcl_ngpus` int(10) unsigned NOT NULL default '0' AFTER `avail_excl_ngpus`"
	);
	add_columns("grid_hosts", $column_arr);

	create_table("grid_hostinfo_gpu", "CREATE TABLE IF NOT EXISTS `grid_hostinfo_gpu` (
		  `host` varchar(64) NOT NULL default '',
		  `clusterid` int(10) unsigned NOT NULL default '0',
		  `gpu_id` int(10) unsigned NOT NULL default '0',
		  `gBrand` varchar(20) NOT NULL default '',
		  `gModel` varchar(20) NOT NULL default '',
		  `gpu_mode` int(10) unsigned NOT NULL default '0',
		  `pstatus` int(10) unsigned NOT NULL default '0',
		  `status` varchar(20) default NULL,
		  `gpu_error` varchar(255) NOT NULL default '',
		  `gpu_driver` varchar(64) NOT NULL default '',
		  `gpu_factor` varchar(64) NOT NULL default '',
		  `gpu_temp` int(10) unsigned NOT NULL default '0',
		  `gpu_ecc` float NOT NULL DEFAULT '0',
		  `gpu_ut` float NOT NULL DEFAULT '0',
		  `gpu_mut` float NOT NULL DEFAULT '0',
		  `gpu_power_ut` float NOT NULL DEFAULT '0',
		  `gpu_mtotal` float NOT NULL DEFAULT '0',
		  `gpu_mused` float NOT NULL DEFAULT '0',
		  `gvendor` int(10) unsigned NOT NULL default '0',
		  `driverVersion` varchar(256) NOT NULL default '',
		  `present` tinyint(3) unsigned NOT NULL default '1',
		  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  PRIMARY KEY  (`host`,`clusterid`, `gpu_id`),
		  KEY `clusterid_last_updated` (`clusterid`,`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	create_table("grid_hosts_gpu", "CREATE TABLE IF NOT EXISTS `grid_hosts_gpu` (
		  `host` varchar(64) NOT NULL default '',
		  `clusterid` int(10) unsigned NOT NULL default '0',
		  `gpu_id` int(10) unsigned NOT NULL default '0',
		  `gpu_model` varchar(40) NOT NULL default '',
		  `gpu_mode` int(10) unsigned NOT NULL default '0',
		  `pstatus` int(10) unsigned NOT NULL default '0',
		  `status` varchar(20) default NULL,
		  `gpu_error` varchar(255) NOT NULL default '',
		  `prev_status` varchar(20) NOT NULL default '',
		  `time_in_state` int(10) unsigned NOT NULL default '0',
		  `mem_used` int(10) unsigned NOT NULL default '0',
		  `mem_rsv` int(10) unsigned NOT NULL default '0',
		  `numJobs` int(10) unsigned NOT NULL default '0',
		  `numRun` int(10) unsigned NOT NULL default '0',
		  `numSUSP` int(10) unsigned NOT NULL default '0',
		  `numRSV` int(10) unsigned NOT NULL default '0',
		  `socketid` int(10) unsigned NOT NULL default '0',
		  `present` tinyint(3) unsigned NOT NULL default '1',
		  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  PRIMARY KEY  (`host`,`clusterid`, `gpu_id`),
		  KEY `clusterid_last_updated` (`clusterid`,`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	create_table("grid_clusters_perfmon_metrics", "CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_metrics` (
		  `clusterid` int(10) unsigned NOT NULL default '0',
		  `metric` varchar(40) NOT NULL default '',
		  `current` double NOT NULL default '0',
		  `max` double NOT NULL default '0',
		  `min` double NOT NULL default '0',
		  `avg` double NOT NULL default '0',
		  `total` double NOT NULL default '0',
		  `present` tinyint(3) unsigned NOT NULL default '1',
		  PRIMARY KEY  (`clusterid`,`metric`)
		) ENGINE=InnoDB COMMENT='Contains Perfmon Metrics' DEFAULT CHARSET=latin1;");
	create_table("grid_clusters_perfmon_metrics_type", "CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_metrics_type` (
		  `metric` varchar(40) NOT NULL default '',
		  `type` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`metric`),
		  KEY `type_metric` (`type`,`metric`)
		) ENGINE=InnoDB COMMENT='Define Perfmon Metrics type' DEFAULT CHARSET=latin1;");
	execute_sql("Define perfmon metrics type", "INSERT IGNORE INTO `grid_clusters_perfmon_metrics_type` VALUES
		('Host information queries',1),
		('Job information queries',1),
		('Job submission requests',1),
		('Jobs accepted from remote cluster',1),
		('Jobs completed',1),
		('Jobs dispatched',1),
		('Jobs reordered',1),
		('Jobs sent to remote cluster',1),
		('Jobs submitted',1),
		('Processed requests: mbatchd',1),
		('Queue information queries',1),
		('Job buckets',2),
		('Matching host criteria',2),
		('Scheduling interval in second(s)',2),
		('MBD file descriptor usage',3),
		('Slot utilization',4),
		('Memory utilization',4);");
	execute_sql("Drop old table grid_clusters_perfmon_mbatchd_metrics", "DROP TABLE grid_clusters_perfmon_mbatchd_metrics");
	execute_sql("Drop old table grid_clusters_perfmon_scheduler_metrics", "DROP TABLE grid_clusters_perfmon_scheduler_metrics");
	execute_sql("Drop old table grid_clusters_perfmon_usage_metrics", "DROP TABLE grid_clusters_perfmon_usage_metrics");

	//Fix 255131, graph template that is percentage based should has a fixed axis at 110.
	$update_graph_templates = array(
		'1 GPU Utilization' => '24ea51505b885e89251b3a2d898e5f32',
		'2 GPUs Utilization' => 'bbc70c11d510c5460f40838ca9ed01be',
		'GPU Utilization' => '415e40a19c29ab428c055e5ff7957efe',
		'8 GPUs Utilization' => '82649ac681fb1ea6d653b810e887454f',
		'Shared GPU Utilization' => '78062cfe63b0877b4c9a4fb3225f0976',
		'Shared GPU Memory Utilization' => 'be548c1e38f97c6bc7e36ae56767a15c'
		);
	foreach ($update_graph_templates as $name => $hash) {
		db_execute_prepared("update grid_elim_templates_graph set auto_scale = '', upper_limit = 110
			where graph_template_id in (select id from grid_elim_templates where hash= ? )", array($hash));
		db_execute_prepared("update graph_templates_graph set auto_scale = '', upper_limit = 110
			where local_graph_id in (select local_graph_id from grid_elim_templates_graph_map where grid_elim_template_id in (select id from grid_elim_templates where hash= ? ))", array($hash));
		cacti_log('ELIM Graph: ' . $name . ' updated.', true, 'UPGRADE');
	}
	$update_graph_templates = array(
		'GRID - Cluster Effective Utilization' => 'c191bde34dd5da98ccfe501e414b9e9e',
		'GRID - Cluster/Host CPU Utilization' => '959f365266409e915fa7449bcba0cf1f',
		'GRID - Cluster/Host Effective UT' => 'a03fa34c731c32268f1b7e1da765e8db',
		'GRID - Host Group - CPU Utilization' => 'd60eef8ae060cbddbd649abb0871e5b8',
		'GRID - Host Group - Effective Utilization' => 'd5bc1ab844d0fd4effcff3a918da538f',
		'GRID - Queue - Effective Utilization' => '0aa65c1c3d9bfb4e0230a9de24fc8f3f',
		'Host MIB - CPU Utilization' => 'c6bb62bedec4ab97f9db9fd780bd85a6',
		'Net-SNMP - CPU Utilization' => 'eee71ec20dc7b44635ab185bbf924dc4',
		'Netware - CPU Utilization' => '46bb77f4c0c69671980e3c60d3f22fa9'
		);
	foreach ($update_graph_templates as $name => $hash) {
		db_execute_prepared("update graph_templates_graph set auto_scale = '', upper_limit = 110
			where graph_template_id in (select id from graph_templates where hash= ? )", array($hash));
		cacti_log('Graph: ' . $name . ' updated.', true, 'UPGRADE');
	}
	$data = array();
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'numJOBS');
	$data['columns'][] = array('name' => 'memSlotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'numPEND');
	$data['columns'][] = array('name' => 'slotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'memSlotUtil');
	$data['columns'][] = array('name' => 'cpuUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'slotUtil');
	$data['columns'][] = array('name' => 'memUsed', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'cpuUtil');
	$data['columns'][] = array('name' => 'memRequested', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memUsed');
	$data['columns'][] = array('name' => 'memReserved', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memRequested');
	$data['primary']   = array('clusterid', 'groupName');
	db_update_table('grid_hostgroups_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'memSlotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'total_cpu');
	$data['columns'][] = array('name' => 'slotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'memSlotUtil');
	$data['columns'][] = array('name' => 'cpuUtil', 'type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'slotUtil');
	$data['columns'][] = array('name' => 'memUsed', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'cpuUtil');
	$data['columns'][] = array('name' => 'memRequested', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memUsed');
	$data['columns'][] = array('name' => 'memReserved', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0', 'after' => 'memRequested');
	$data['primary']   = array('clusterid', 'queue');
	db_update_table('grid_queues_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'slot_share');
	$data['primary']   = array('clusterid', 'queue', 'user_or_group', 'shareAcctPath');
	db_update_table('grid_queues_shares', $data);

	$data = array();
	$data['columns'][] = array('name' => 'runJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'total_used');
	$data['columns'][] = array('name' => 'runSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'runJobs');
	$data['columns'][] = array('name' => 'pendJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'runSlots');
	$data['columns'][] = array('name' => 'pendSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'pendJobs');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'pendSlots');
	$data['primary']   = array('clusterid','name','consumer');
	db_update_table('grid_guarantee_pool_distribution', $data);


	$data = array();
	$data['columns'][] = array('name' => 'runJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'guar_used');
	$data['columns'][] = array('name' => 'runSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'runJobs');
	$data['columns'][] = array('name' => 'pendJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'runSlots');
	$data['columns'][] = array('name' => 'pendSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'pendJobs');
	$data['columns'][] = array('name' => 'memSlotUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'pendSlots');
	$data['columns'][] = array('name' => 'slotUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'memSlotUtil');
	$data['columns'][] = array('name' => 'cpuUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'slotUtil');
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'cpuUtil');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'memUsed');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'memRequested');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'after' => 'memReserved');
	$data['primary']   = array('clusterid','name');
	db_update_table('grid_guarantee_pool', $data);

	$column_arr= array(
		'owner' => "ADD COLUMN `owner` varchar(40) NOT NULL default '' AFTER `host`"
	);
	$index_arr = array(
		"clusterid_host" => "ADD INDEX `clusterid_host` (`clusterid`, `host`)",
		"clusterid_owner" => "ADD INDEX `clusterid_owner` (`clusterid`, `owner`)"
	);
	add_columns_indexes("grid_guarantee_pool_hosts", $column_arr, $index_arr);

	$data = array();
	$data['columns'][] = array('name' => 'lsf_strict_checking', 'type' => 'varchar(10)', 'NULL' => false, 'default' => 'N', 'after' => 'lsf_ego');
	$data['columns'][] = array('name' => 'lsf_krb_auth', 'type' => 'varchar(3)', 'NULL' => false, 'default' => '', 'after' => 'lsf_strict_checking');
	$data['primary']   = array('clusterid');
	db_update_table('grid_clusters', $data);

	$data = array();
	$data['columns'][] = array('name' => 'data_source_profile_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '1', 'after' => 'hosttype_value');
	$data['primary']   = array('id');
	db_update_table('grid_elim_template_instances', $data);

	add_index("host", "clusterid_host", "ADD INDEX `clusterid_host` (`clusterid`, `hostname`);");


	cacti_log('Importing RTM templates..', true, 'UPGRADE');
	$grid_templates = array(
		"1" => array (
			'value' => 'GRID - Pool - Effective Utilization',
			'name' => 'cacti_data_query_grid_-_pool_-_effective_utilization.xml'
		),
		"2" => array (
			'value' => 'GRID - LSF Jobs Reordered',
			'name' => 'cacti_graph_template_grid_-_lsf_jobs_reordered.xml'
		),
		"3" => array (
			'value' => 'GRID - LSF Memory Utilization',
			'name' => 'cacti_graph_template_grid_-_lsf_memory_utilization.xml'
		),
		"4" => array (
			'value' => 'GRID - LSF Slot Utilization',
			'name' => 'cacti_graph_template_grid_-_lsf_slot_utilization.xml'
		),
		"5" => array (
			'value' => 'GRID - Host GPU Status',
			'name' => 'cacti_data_query_grid_-_gpu_status.xml'
		),
		"6" => array (
			'value' => '2 GPUs Memory Usage',
			'name' => 'cacti_elim_template_2_gpus_memory_usage.xml'
		),
		"7" => array (
			'value' => '4 GPUs Utilization',
			'name' => 'cacti_elim_template_4_gpus_utilization.xml'
		)
	);
	foreach($grid_templates as $grid_template) {
		cacti_log(' - Importing ' . $grid_template['value'] . '.', true, 'UPGRADE');
		$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_2_0_11/" . $grid_template['name']);
	}
	cacti_log('Templates import complete.', true, 'UPGRADE');

	$column_arr= array(
		'isLoaningGSLA' => "ADD COLUMN `isLoaningGSLA` tinyint(3) unsigned NOT NULL default '0' AFTER `effectiveEligiblePendingTimeLimit`"
	);
	//Do not use Cacti::db_update_table because db_update_table alter table column one by one
	add_columns("grid_jobs", $column_arr);
	add_columns("grid_jobs_finished", $column_arr);

	$column_arr= array(
		'isborrowed' => "ADD COLUMN `isborrowed` tinyint(3) unsigned NOT NULL default '0' AFTER `ngpus`"
	);
	//Do not use Cacti::db_update_table because db_update_table alter table column one by one
	add_columns("grid_jobs_jobhosts", $column_arr);
	add_columns("grid_jobs_jobhosts_finished", $column_arr);

	create_table("grid_jobs_sla_loaning", "CREATE TABLE `grid_jobs_sla_loaning` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`jobid` bigint(20) NOT NULL DEFAULT '0',
		`indexid` int(10) unsigned NOT NULL DEFAULT '0',
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`poolname` varchar(60) NOT NULL DEFAULT '',
		`numRsrc` int(10) DEFAULT '0',
  		`mem` double DEFAULT '0',
		`last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`poolname`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

	create_table("grid_jobs_sla_loaning_finished", "CREATE TABLE `grid_jobs_sla_loaning_finished` LIKE `grid_jobs_sla_loaning`;");

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.11' WHERE directory IN ('gridpend', 'benchmark', 'gridcstat', 'RTM', 'lichist', 'meta')");

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('grid_partitions_upgrade_status', 'scheduled')");
}

function partition_tables_to_10_2_0_11(){
	return array(
		'grid_jobs_finished' => array(
			'columns' => array(
				'isLoaningGSLA'           => array( 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0', 'after' => 'effectiveEligiblePendingTimeLimit')
			)
		),
		'grid_jobs_jobhosts_finished' => array(
			'columns' => array(
				'isborrowed' => array('unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0', 'after' => 'ngpus')
			)
		)
	);
}
