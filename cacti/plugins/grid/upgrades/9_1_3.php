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

function upgrade_to_9_1_3() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/import.php');

	cacti_log('NOTE: Upgrading grid to v9.1.3 ...', true, 'UPGRADE');

	$cacti_pos=strrpos(realpath(dirname(__FILE__)), '/cacti/');
	$rtm_top=substr(realpath(dirname(__FILE__)),0,$cacti_pos);

	execute_sql("Reset the 'lic_db_upgrade' flag for license daily partition upgrade",
		"DELETE FROM settings WHERE name='lic_db_upgrade'");

	db_execute('DROP TABLE IF EXISTS grid_lsf_config_audit_log');
	db_execute('DROP TABLE IF EXISTS grid_lsf_config_item');
	db_execute('DROP TABLE IF EXISTS grid_lsf_config_item_attribute');
	db_execute('DROP TABLE IF EXISTS grid_lsf_configuration');

	db_execute('DROP TABLE IF EXISTS grid_ha_config');
	db_execute('DROP TABLE IF EXISTS grid_ha_status');
	db_execute('DROP TABLE IF EXISTS grid_ha_status_string');
	db_execute('DROP TABLE IF EXISTS grid_ha_template');
	db_execute('DROP TABLE IF EXISTS grid_ha_template_details');

	$old_grid_graph_purge_transient = read_config_option('grid_graph_purge_transient');
	if (!empty($old_grid_graph_purge_transient)) {
		switch($old_grid_graph_purge_transient) {
		case '30':
			set_config_option('grid_graph_purge_transient', '1months');
			break;
		case '180':
			set_config_option('grid_graph_purge_transient', '6months');
			break;
		case '365':
			set_config_option('grid_graph_purge_transient', '1year');
			break;
		}
	}

	add_index('user_log', 'user_id',
		'ADD INDEX `user_id`(`user_id`)');

	if (!db_column_exists('grid_clusters', 'perfmon_run')) {
		execute_sql('Add Perfmon Columns to Clusters Table',
			"ALTER TABLE `grid_clusters`
			ADD COLUMN `perfmon_run` char(3) default '' AFTER `grididle_exclude_queues`,
			ADD COLUMN `perfmon_interval` int unsigned default '60' AFTER `perfmon_run`,
			ADD COLUMN `perfmon_job` char(3) default '' AFTER `perfmon_interval`,
			ADD COLUMN `perfmon_user` varchar(40) default 'lsfadmin' AFTER `perfmon_job`,
			ADD COLUMN `perfmon_queue` varchar(40) default 'normal' AFTER `perfmon_user`");
	}

	create_table('grid_jobs_host_rusage',
		"CREATE TABLE IF NOT EXISTS `grid_jobs_host_rusage` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`jobid` bigint(20) NOT NULL DEFAULT '0',
		`indexid` int(10) unsigned NOT NULL DEFAULT '0',
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`host` varchar(64) NOT NULL DEFAULT '',
		`utime` float NOT NULL DEFAULT '0',
		`stime` float NOT NULL DEFAULT '0',
		`mem` float NOT NULL DEFAULT '0',
		`swap` float NOT NULL DEFAULT '0',
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`update_time`),
		KEY `update_time` (`update_time`),
		KEY `submit_time` (`submit_time`))
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	add_column('grid_jobs_rusage', 'mem_reserved',
		"ADD COLUMN `mem_reserved` float NOT NULL DEFAULT '0' AFTER `swap`");

	execute_sql("DROP grid_clusters_perfmon_mbatchd_metrics",
		"DROP TABLE IF EXISTS `grid_clusters_perfmon_mbatchd_metrics`");

	create_table("grid_clusters_perfmon_mbatchd_metrics",
		"CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_mbatchd_metrics` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`metric` varchar(40) NOT NULL default '',
		`current` int(10) unsigned NOT NULL default '0',
		`max` int(10) unsigned NOT NULL default '0',
		`min` int(10) unsigned NOT NULL default '0',
		`avg` double unsigned NOT NULL default '0',
		`total` bigint(20) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned default '0',
		PRIMARY KEY  (`clusterid`,`metric`))
		ENGINE=MEMORY
		COMMENT='Contains Perfmon Metrics'");

	execute_sql("DROP grid_clusters_perfmon_scheduler_metrics",
		"DROP TABLE IF EXISTS `grid_clusters_perfmon_scheduler_metrics`");

	create_table("grid_clusters_perfmon_scheduler_metrics",
		"CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_scheduler_metrics` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`metric` varchar(40) NOT NULL default '',
		`current` int(10) unsigned NOT NULL default '0',
		`max` int(10) unsigned NOT NULL default '0',
		`min` int(10) unsigned NOT NULL default '0',
		`avg` double unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned default '0',
		PRIMARY KEY  (`clusterid`,`metric`))
		ENGINE=MEMORY
		COMMENT='Contains Perfmon Metrics'");

	execute_sql("DROP grid_clusters_perfmon_usage_metrics",
		"DROP TABLE IF EXISTS `grid_clusters_perfmon_usage_metrics`");

	create_table("grid_clusters_perfmon_usage_metrics",
		"CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_usage_metrics` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`metric` varchar(40) NOT NULL default '',
		`used` bigint(20) unsigned NOT NULL default '0',
		`free` bigint(20) unsigned NOT NULL default '0',
		`total` bigint(20) unsigned NOT NULL default '0',
		`present` tinyint(3) unsigned default '0',
		PRIMARY KEY  (`clusterid`,`metric`))
		ENGINE=MEMORY
		COMMENT='Contains Perfmon Metrics'");

	execute_sql("DROP grid_clusters_perfmon_status",
		"DROP TABLE IF EXISTS `grid_clusters_perfmon_status`");

	create_table("grid_clusters_perfmon_status",
		"CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_status` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`clients` int(10) unsigned NOT NULL default '0',
		`clients_peak` int(10) unsigned NOT NULL default '0',
		`servers` int(10) unsigned NOT NULL default '0',
		`servers_peak` int(10) unsigned NOT NULL default '0',
		`cpus` int(10) unsigned NOT NULL default '0',
		`cpus_peak` int(10) unsigned NOT NULL default '0',
		`cores` int(10) unsigned NOT NULL default '0',
		`cores_peak` int(10) unsigned NOT NULL default '0',
		`slots` int(10) unsigned NOT NULL default '0',
		`slots_peak` int(10) unsigned NOT NULL default '0',
		`serv_all` int(10) unsigned NOT NULL default '0',
		`serv_ok` int(10) unsigned NOT NULL default '0',
		`serv_closed` int(10) unsigned NOT NULL default '0',
		`serv_unreachable` int(10) unsigned NOT NULL default '0',
		`serv_unavail` int(10) unsigned NOT NULL default '0',
		`dc_servers` int(10) unsigned NOT NULL default '0',
		`dc_servers_peak` int(10) unsigned NOT NULL default '0',
		`dc_cores` int(10) unsigned NOT NULL default '0',
		`dc_cores_peak` int(10) unsigned NOT NULL default '0',
		`dc_vm_containers` int(10) unsigned NOT NULL default '0',
		`dc_vm_containers_peak` int(10) unsigned NOT NULL default '0',
		`num_jobs` int(10) unsigned NOT NULL default '0',
		`num_run` int(10) unsigned NOT NULL default '0',
		`num_susp` int(10) unsigned NOT NULL default '0',
		`num_pend` int(10) unsigned NOT NULL default '0',
		`num_finished` int(10) unsigned NOT NULL default '0',
		`num_users` int(10) unsigned NOT NULL default '0',
		`num_active_users` int(10) unsigned NOT NULL default '0',
		`num_groups` int(10) unsigned NOT NULL default '0',
		`last_mbatchd_start` timestamp NOT NULL default '0000-00-00 00:00:00',
		`active_mbd_pid` int(10) unsigned NOT NULL default '0',
		`last_mbatchd_reconfig` timestamp NOT NULL default '0000-00-00 00:00:00',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`))
		ENGINE=MEMORY
		COMMENT='Contains badmin showstatus information'");

	execute_sql("DROP grid_clusters_perfmon_summary",
		"DROP TABLE IF EXISTS `grid_clusters_perfmon_summary`");

	create_table("grid_clusters_perfmon_summary",
		"CREATE TABLE IF NOT EXISTS `grid_clusters_perfmon_summary` (
		`clusterid` int(10) unsigned NOT NULL default '0',
		`last_jobid` int(10) unsigned NOT NULL default '0',
		`start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`last_run` timestamp NOT NULL default '0000-00-00 00:00:00',
		`poller_interval` int(10) unsigned NOT NULL default '0',
		`pjob_submitTime` double NOT NULL default '0',
		`pjob_seenTime` double NOT NULL default '0',
		`pjob_runTime` int(10) unsigned NOT NULL default '0',
		`pjob_doneTime` double NOT NULL default '0',
		`pjob_seenDoneTime` double NOT NULL default '0',
		`pjob_startTime` double NOT NULL default '0',
		`present` tinyint(3) unsigned NOT NULL default '0',
		PRIMARY KEY  (`clusterid`))
		ENGINE=MEMORY
		COMMENT='Contains Perfmon Sampling Information'");

	/*change user/userGroup/execUsername column length to varchar(40)*/
	execute_sql("Modify table grid_jobs",
		"ALTER TABLE `grid_jobs`
		MODIFY COLUMN `user` varchar(40) NOT NULL default '',
		MODIFY COLUMN `execUsername`  varchar(40) NOT NULL default '',
		MODIFY COLUMN `userGroup` varchar(40) NOT NULL");

	execute_sql("Modify table grid_jobs_finished",
		"ALTER TABLE `grid_jobs_finished`
		MODIFY COLUMN `user` varchar(40) NOT NULL default '',
		MODIFY COLUMN `execUsername` varchar(40) NOT NULL default '',
		MODIFY COLUMN `userGroup` varchar(40) NOT NULL default ''");

	execute_sql("Modify table grid_jobs_users",
		"ALTER TABLE `grid_jobs_users`
		MODIFY COLUMN `user` varchar(40) NOT NULL default ''");

	execute_sql("Modify table grid_user_group_stats",
		"ALTER TABLE `grid_user_group_stats`
		MODIFY COLUMN `userGroup` varchar(40) NOT NULL");

	execute_sql("Modify table grid_service_class_groups",
		"ALTER TABLE `grid_service_class_groups`
		MODIFY COLUMN `user_or_group` varchar(40) NOT NULL");

	execute_sql("Modify table grid_jobs_pendhist_daily",
		"ALTER TABLE `grid_jobs_pendhist_daily`
		MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT ''");

	execute_sql("Modify table grid_jobs_pendhist_hourly",
		"ALTER TABLE `grid_jobs_pendhist_hourly`
		MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT ''");

	execute_sql("Modify table grid_jobs_pendhist_yesterday",
       	"ALTER TABLE `grid_jobs_pendhist_yesterday`
		MODIFY COLUMN `user` varchar(40) NOT NULL DEFAULT ''");

	execute_sql("Modify table grid_queues",
		"ALTER TABLE `grid_queues`
		MODIFY COLUMN `userJobLimit` varchar(20) NOT NULL DEFAULT ''");

	/*fix problem 235565 auto_increment */

	 execute_sql("Modify table grid_host_threshold",
		"ALTER TABLE `grid_host_threshold`
		ENGINE=MEMORY,
		MODIFY COLUMN `loadStop` double NOT NULL DEFAULT '0',
		MODIFY COLUMN `id` bigint unsigned NOT NULL AUTO_INCREMENT");

	execute_sql("Modify table grid_hosts_alarm",
		"ALTER TABLE `grid_hosts_alarm`
		ENGINE=MEMORY");

	//fix Problem 227868: Table grid_jobs_idled, grid_jobs_memvio clusterid should not be auto_increment
	execute_sql("Modify table grid_hostgroups_stats",
		"ALTER TABLE `grid_hostgroups_stats`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_jobs_idled",
		"ALTER TABLE `grid_jobs_idled`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_jobs_memvio",
		"ALTER TABLE `grid_jobs_memvio`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_projects",
		"ALTER TABLE `grid_projects`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_queues_hosts",
		"ALTER TABLE `grid_queues_hosts`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_queues_stats",
		"ALTER TABLE `grid_queues_stats`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	execute_sql("Modify table grid_queues_users",
		"ALTER TABLE `grid_queues_users`
		MODIFY COLUMN `clusterid` int(10) unsigned NOT NULL default '0'");

	//change table grid_settings and settings engine to
	 execute_sql("Modify table settings",
		"ALTER TABLE `settings`
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

	 execute_sql("Modify table grid_settings",
		"ALTER TABLE `grid_settings`
		ENGINE=InnoDB
		ROW_FORMAT=Dynamic");

}
