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
	global $config;
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading gridblstat to v9.1.4 ...', true, 'UPGRADE');

	create_table('License Scheduler Collector Table',
		"CREATE TABLE IF NOT EXISTS `grid_blstat_collectors` (
		`lsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL,
		`cacti_host` int(10) unsigned DEFAULT NULL,
		`name` varchar(128) NOT NULL,
		`region` varchar(40) DEFAULT '',
		`ls_version` int(10) unsigned NOT NULL,
		`ls_hosts` varchar(256) NOT NULL,
		`ls_admin` varchar(64) NOT NULL,
		`ls_port` int(10) unsigned NOT NULL,
		`disabled` char(2) DEFAULT '',
		`poller_freq` int(10) unsigned NOT NULL,
		`blstat_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`graph_freq` int(10) unsigned NOT NULL,
		`graph_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`lsid`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Defines Various License Scheduler BLD Settings'");

	if (db_table_exists('grid_blstat')) {
		execute_sql('Drop Legacy License Scheduler Table 1',
			'DROP TABLE IF EXISTS grid_blstat');

		execute_sql('Drop Legacy License Scheduler Table 2',
			'DROP TABLE IF EXISTS grid_blstat_cluster_use');

		execute_sql('Drop Legacy License Scheduler Table 3',
			'DROP TABLE IF EXISTS grid_blstat_clusters');

		execute_sql('Drop Legacy License Scheduler Table 4',
			'DROP TABLE IF EXISTS grid_blstat_collector_clusters');

		execute_sql('Drop Legacy License Scheduler Table 4',
			'DROP TABLE IF EXISTS grid_blstat_collector_clusters');

		execute_sql('Drop Legacy License Scheduler Table 5',
			'DROP TABLE IF EXISTS grid_blstat_distribution');

		execute_sql('Drop Legacy License Scheduler Table 6',
			'DROP TABLE IF EXISTS grid_blstat_feature_map');

		execute_sql('Drop Legacy License Scheduler Table 7',
			'DROP TABLE IF EXISTS grid_blstat_projects');

		execute_sql('Drop Legacy License Scheduler Table 8',
			'DROP TABLE IF EXISTS grid_blstat_service_domains');

		execute_sql('Drop Legacy License Scheduler Table 9',
			'DROP TABLE IF EXISTS grid_blstat_tasks');

		execute_sql('Drop Legacy License Scheduler Table 10',
			'DROP TABLE IF EXISTS grid_blstat_users');

		execute_sql('Update License Scheduler Collectors to Correct format',
			'ALTER TABLE grid_blstat_collectors');

		cacti_log('NOTE: Installing default License Scheduler Collector', true, 'UPGRADE');

		$rows = db_fetch_cell('SELECT COUNT(*) FROM grid_blstat_collectors');

		if (empty($rows)) {
			db_execute_prepared('INSERT INTO grid_blstat_collectors
				(name, ls_version, ls_hosts, ls_admin, ls_port, poller_freq, blstat_lastrun, graph_freq)
				VALUES ("Default Collector", "9.1", ?, ?, ?, ?, ?, ?)',
				array(
					read_config_option('gridblstat_hosts'),
					read_config_option('gridblstat_admin'),
					read_config_option('gridblstat_port'),
					read_config_option('gridblstat_poller_freq'),
					date('Y-m-d H:i:s', read_config_option('gridblstat_lastrun')),
					read_config_option('gridblstat_graph_freq')
				)
			);
		}
	}

	create_table('License Scheduler Summary Table',
		"CREATE TABLE `grid_blstat` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`type` int(10) unsigned NOT NULL,
		`total_inuse` int(10) unsigned NOT NULL,
		`total_reserve` int(10) unsigned NOT NULL,
		`total_free` int(10) unsigned NOT NULL,
		`total_tokens` int(10) unsigned NOT NULL,
		`total_alloc` int(10) unsigned NOT NULL,
		`total_use` int(10) unsigned NOT NULL,
		`total_others` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`service_domain`))
		ENGINE=MEMORY
		COMMENT='General License Scheduler Information'");

	create_table('License Scheduler Cluster Use Table',
		"CREATE TABLE `grid_blstat_cluster_use` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`type` tinyint(3) unsigned DEFAULT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`alloc` int(10) unsigned DEFAULT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`over` int(10) unsigned DEFAULT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned DEFAULT NULL,
		`need` int(10) unsigned NOT NULL,
		`target` int(10) unsigned DEFAULT NULL,
		`acum_use` int(10) unsigned NOT NULL,
		`scaled_acum` int(10) unsigned NOT NULL,
		`avail` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`project`,`cluster`),
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='License Scheduler Use by Cluster'");

	create_table('License Scheduler Clusters Table',
		"CREATE TABLE `grid_blstat_clusters` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`share` float NOT NULL,
		`alloc` int(10) unsigned NOT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`over` int(10) unsigned NOT NULL,
		`peak` int(10) unsigned NOT NULL,
		`buffer` int(10) unsigned NOT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned NOT NULL,
		`max_reclaim` int(10) unsigned NOT NULL DEFAULT '0',
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`service_domain`,`cluster`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='License Scheduler Cluster Details'");

	create_table('License Scheduler Collector Clusters Table',
		"CREATE TABLE `grid_blstat_collector_clusters` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`clusterid` int(18) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`lsid`,`clusterid`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic
		COMMENT='Contains list of Clusters included in the bld'");

	create_table('License Scheduler Distribution Table',
		"CREATE TABLE `grid_blstat_distribution` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`total` int(10) unsigned NOT NULL,
		`lsf_use` int(10) unsigned NOT NULL,
		`lsf_deserve` int(10) unsigned NOT NULL,
		`lsf_free` int(10) unsigned NOT NULL,
		`non_lsf_use` int(10) unsigned NOT NULL,
		`non_lsf_deserve` int(10) unsigned NOT NULL,
		`non_lsf_free` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`service_domain`),
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY");

	create_table('License Scheduler Feature Map Table',
		"CREATE TABLE `grid_blstat_feature_map` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`bld_feature` varchar(64) NOT NULL,
		`lic_feature` varchar(64) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`bld_feature`,`lic_feature`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='Maintains the mapping between BLD and the License Feature'");

	create_table('License Scheduler Projects Table',
		"CREATE TABLE `grid_blstat_projects` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`share` float NOT NULL,
		`own` int(10) unsigned NOT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`service_domain`,`project`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='License Scheduler Project Details'");

	create_table('License Scheduler Service Domains Table',
		"CREATE TABLE `grid_blstat_service_domains` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`service_domain` varchar(64) NOT NULL,
		`lic_id` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`service_domain`,`lic_id`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='Maintains the mapping between BLD Service Domains and Services'");

	create_table('License Scheduler Tasks Table',
		"CREATE TABLE `grid_blstat_tasks` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`host` varchar(64) NOT NULL,
		`user` varchar(20) NOT NULL,
		`stat` varchar(10) NOT NULL,
		`tid` int(10) unsigned NOT NULL,
		`connect_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`terminal` varchar(10) NOT NULL,
		`pgid` int(10) unsigned NOT NULL,
		`cpu_time` int(10) unsigned NOT NULL,
		`memory` int(10) unsigned NOT NULL,
		`swap` int(10) unsigned NOT NULL,
		`cpu_idle` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`res_requirements` varchar(128) NOT NULL,
		`command` varchar(256) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`feature`,`host`,`user`,`tid`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='License Scheduler Task Details'");

	create_table('License Scheduler Users Table',
		"CREATE TABLE `grid_blstat_users` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`jobid` int(10) unsigned NOT NULL,
		`indexid` int(10) unsigned NOT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`user` varchar(40) NOT NULL,
		`host` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`resource` varchar(45) NOT NULL,
		`rusage` int(10) unsigned NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL,
		PRIMARY KEY (`lsid`,`jobid`,`indexid`,`cluster`,`resource`) USING HASH,
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `project` (`project`),
		KEY `resource` (`resource`) USING BTREE,
		KEY `present` (`present`) USING BTREE)
		ENGINE=MEMORY
		COMMENT='User information from License Scheduler'");
}
