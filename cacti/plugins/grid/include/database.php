<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2026                                          |
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

function grid_create_tables() {

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'appName', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJOBS', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpu', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`appName';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_applications', $data);

	$data = array();
	$data['columns'][] = array('name' => 'app_id',       'type' => 'int(10)','unsigned' => true,  'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'app_name',     'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'matchResReq',  'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'matchProject', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'matchJobname', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'app_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_apps', $data);

	$data = array();
	$data['columns'][] = array('name' => 'app_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'exit_code', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'reason', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'app_id`,`exit_code';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_apps_error_codes', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_arrays` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` int(10) unsigned NOT NULL,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`stat` int(10) unsigned NOT NULL DEFAULT 0,
		`jType` int(10) unsigned NOT NULL DEFAULT 0,
		`jName` varchar(3000) NOT NULL DEFAULT '',
		`user` varchar(60) NOT NULL DEFAULT '',
		`userGroup` varchar(60) DEFAULT '',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`projectName` varchar(256) NOT NULL DEFAULT '0',
		`numJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numPEND` int(10) unsigned NOT NULL DEFAULT 0,
		`numPSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numRUN` int(10) unsigned NOT NULL DEFAULT 0,
		`numSSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numUSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numEXIT` int(10) unsigned NOT NULL DEFAULT 0,
		`numDONE` int(10) unsigned NOT NULL DEFAULT 0,
		`minMemory` double NOT NULL DEFAULT 0,
		`maxMemory` double NOT NULL DEFAULT 0,
		`avgMemory` double NOT NULL DEFAULT 0,
		`minSwap` double NOT NULL DEFAULT 0,
		`maxSwap` double NOT NULL DEFAULT 0,
		`avgSwap` double NOT NULL DEFAULT 0,
		`totalCPU` double NOT NULL DEFAULT 0,
		`totalUTime` double NOT NULL DEFAULT 0,
		`totalSTime` double NOT NULL DEFAULT 0,
		`totalEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`first_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`submit_time`),
		KEY `clusterid_last_updated` (`clusterid`,`last_updated`),
		KEY `clusterid_jName` (`clusterid`,`jName`),
		KEY `clusterid_user` (`clusterid`,`user`),
		KEY `clusterid_queue` (`clusterid`,`queue`),
		KEY `clusterid_userGroup` (`clusterid`,`userGroup`),
		KEY `clusterid_projectName` (`clusterid`,`projectName`),
		KEY `clusterid_stat` (`clusterid`,`stat`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Job Groups and Array';");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_arrays_finished` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` int(10) unsigned NOT NULL,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`stat` int(10) unsigned NOT NULL DEFAULT 0,
		`jType` int(10) unsigned NOT NULL DEFAULT 0,
		`jName` varchar(3000) NOT NULL DEFAULT '',
		`user` varchar(60) NOT NULL DEFAULT '',
		`userGroup` varchar(60) DEFAULT '',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`projectName` varchar(256) NOT NULL DEFAULT '0',
		`numJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numPEND` int(10) unsigned NOT NULL DEFAULT 0,
		`numPSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numRUN` int(10) unsigned NOT NULL DEFAULT 0,
		`numSSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numUSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numEXIT` int(10) unsigned NOT NULL DEFAULT 0,
		`numDONE` int(10) unsigned NOT NULL DEFAULT 0,
		`minMemory` double NOT NULL DEFAULT 0,
		`maxMemory` double NOT NULL DEFAULT 0,
		`avgMemory` double NOT NULL DEFAULT 0,
		`minSwap` double NOT NULL DEFAULT 0,
		`maxSwap` double NOT NULL DEFAULT 0,
		`avgSwap` double NOT NULL DEFAULT 0,
		`totalCPU` double NOT NULL DEFAULT 0,
		`totalUTime` double NOT NULL DEFAULT 0,
		`totalSTime` double NOT NULL DEFAULT 0,
		`totalEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`first_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`submit_time`),
		KEY `clusterid_last_updated` (`clusterid`,`last_updated`),
		KEY `clusterid_jName` (`clusterid`,`jName`),
		KEY `clusterid_user` (`clusterid`,`user`),
		KEY `clusterid_queue` (`clusterid`,`queue`),
		KEY `clusterid_userGroup` (`clusterid`,`userGroup`),
		KEY `clusterid_projectName` (`clusterid`,`projectName`),
		KEY `clusterid_stat` (`clusterid`,`stat`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Job Groups and Array';");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'cacti_host', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cacti_tree', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clustername', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'cluster_timezone', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'efficiency_state', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '100');
	$data['columns'][] = array('name' => 'efficiency_queues', 'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'efficiency_warn_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency_alarm_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency_clear_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'disabled', 'type' => 'char(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'total_hosts', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_clients', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hourly_started_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hourly_done_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hourly_exit_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'daily_started_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'daily_done_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'daily_exit_jobs', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lim_timeout', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '10');
	$data['columns'][] = array('name' => 'mbd_timeout', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '10');
	$data['columns'][] = array('name' => 'mbd_job_timeout', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'mbd_job_retries', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'lsf_envdir', 'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_confdir', 'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'ego_confdir', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_version', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '10');
	$data['columns'][] = array('name' => 'lsf_clustername', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_ls_error', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_lsb_error', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_lsb_jobs_error', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_lim_response', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_lsb_response', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_lsb_jobs_response', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lsf_admins', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsb_debug', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_lim_debug', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_res_debug', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_log_mask', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_master', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_masterhosts', 'type' => 'varchar(1024)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_serverhosts', 'type' => 'varchar(1024)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_lic_schedhosts', 'type' => 'varchar(1024)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_unit', 'type' => 'varchar(4)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'collection_timing', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_nonjob_runtime', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'job_minor_timing', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'job_major_timing', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ha_timing', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_job_runtime', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ip', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lim_port', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_ego', 'type' => 'char(3)', 'NULL' => true, 'default' => 'N');
	$data['columns'][] = array('name' => 'lsf_strict_checking', 'type' => 'varchar(10)', 'NULL' => false, 'default' => 'N');
	$data['columns'][] = array('name' => 'lsf_krb_auth', 'type' => 'varchar(3)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_master_hostname', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'username', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'credential', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'communication', 'type' => 'varchar(10)', 'NULL' => false, 'default' => 'ssh');
	$data['columns'][] = array('name' => 'privatekey_path', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'LSF_TOP', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'add_frequency', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'host_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '14');
	$data['columns'][] = array('name' => 'add_graph_frequency', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'advanced_enabled', 'type' => 'char(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_domain', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'email_admin', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'grididle_enabled', 'type' => 'char(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'grididle_notify', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'grididle_runtime', 'type' => 'int(10)', 'NULL' => false, 'default' => '3600');
	$data['columns'][] = array('name' => 'grididle_window', 'type' => 'int(10)', 'NULL' => false, 'default' => '3600');
	$data['columns'][] = array('name' => 'grididle_cputime', 'type' => 'int(10)', 'NULL' => false, 'default' => '24');
	$data['columns'][] = array('name' => 'grididle_jobtypes', 'type' => 'varchar(20)', 'NULL' => false, 'default' => 'all');
	$data['columns'][] = array('name' => 'grididle_jobcommands', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'grididle_exclude_queues', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'perfmon_run', 'type' => 'char(3)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'perfmon_interval', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'exec_host_res_req', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'clusterid';
	$data['keys'][] = array('name' => 'poller_id_disabled', 'columns' => 'poller_id`,`disabled');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_clusters', $data);

	api_plugin_db_add_column('grid', 'host', array('name' => 'clusterid', 'unsigned' => true, 'type' => "int(10)", 'NULL' => false, 'default' => '0', 'after' => 'host_template_id'));
	api_plugin_db_add_column('grid', 'host', array('name' => 'monitor', 'type' => "char(3)", 'NULL' => false, 'default' => 'on', 'after' => 'disabled'));

	db_add_index('host', 'INDEX', 'clusterid_host', array('clusterid', 'hostname'));

	$data = array();
	$data['columns'][] = array('name' => 'dashboard_item_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'user_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['primary'] = 'dashboard_item_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_clusters_dashboard_items', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'type' => 'int(10)', 'unsigned' => true,  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'queue',     'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reportid',  'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',      'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value',     'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`queue`,`reportid`,`name';
	$data['type'] = 'MEMORY';
	$data['comment'] = 'queue level reporting results table';
	api_plugin_db_table_create ('grid', 'grid_clusters_queue_reportdata', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'metric', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'current', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'min', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`metric';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Contains Perfmon Metrics';
	api_plugin_db_table_create ('grid', 'grid_clusters_perfmon_metrics', $data);

	$data = array();
	$data['columns'][] = array('name' => 'metric', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'metric';
	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
	#$data['keys'][] = array('name' => 'type_metric', 'columns' => 'type`,`metric');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Define Perfmon Metrics type';
	api_plugin_db_table_create ('grid', 'grid_clusters_perfmon_metrics_type', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clients', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clients_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'servers', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'servers_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpus_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cores', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cores_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'slots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'slots_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'serv_all', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'serv_ok', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'serv_closed', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'serv_unreachable', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'serv_unavail', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_servers', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_servers_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_cores', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_cores_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_vm_containers', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dc_vm_containers_peak', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_jobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_run', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_susp', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_pend', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_finished', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_users', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_active_users', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_groups', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_mbatchd_start', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'active_mbd_pid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_mbatchd_reconfig', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Contains badmin showstatus information';
	api_plugin_db_table_create ('grid', 'grid_clusters_perfmon_status', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_jobid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'start_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_run', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'poller_interval', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_submitTime', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_seenTime', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_runTime', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_doneTime', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_seenDoneTime', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pjob_startTime', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Contains Perfmon Sampling Information';
	api_plugin_db_table_create ('grid', 'grid_clusters_perfmon_summary', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'type' => 'int(10)', 'unsigned' => true,  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'reportid',  'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',      'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value',     'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`reportid`,`name';
	$data['type'] = 'MEMORY';
	$data['comment'] = 'cluster level reporting results table';
	api_plugin_db_table_create ('grid', 'grid_clusters_reportdata', $data);


	$data = array();
	$data['columns'][] = array('name' => 'grid_elim_template_instance_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'local_graph_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['primary'] = 'grid_elim_template_instance_id`,`local_graph_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores the Map of ELIM template instance to the cacti graph local.';
	api_plugin_db_table_create ('grid', 'grid_elim_instance_graphs', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'char(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'grid_elim_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hosttype_option', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hosttype_value', 'type' => 'varchar(40)', 'NULL' => true);
	$data['columns'][] = array('name' => 'data_source_profile_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'grid_elim_template_id', 'columns' => 'grid_elim_template_id');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores the instances of  ELIM graph template.';
	api_plugin_db_table_create ('grid', 'grid_elim_template_instances', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'char(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'char(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Contains each ELIM graph template name.';
	api_plugin_db_table_create ('grid', 'grid_elim_templates', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'local_graph_template_graph_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'local_graph_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'graph_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_image_format_id', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'image_format_id', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_title', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'title', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'title_cache', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 't_height', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'height', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_width', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'width', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_upper_limit', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'upper_limit', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_lower_limit', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'lower_limit', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_vertical_label', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'vertical_label', 'type' => 'varchar(200)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_slope_mode', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'slope_mode', 'type' => 'char(2)', 'NULL' => true, 'default' => 'on');
	$data['columns'][] = array('name' => 't_auto_scale', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'auto_scale', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_auto_scale_opts', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'auto_scale_opts', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_auto_scale_log', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'auto_scale_log', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_scale_log_units', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'scale_log_units', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_auto_scale_rigid', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'auto_scale_rigid', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_auto_padding', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'auto_padding', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_base_value', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'base_value', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 't_grouping', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'grouping', 'type' => 'char(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 't_unit_value', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'unit_value', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_unit_exponent_value', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'unit_exponent_value', 'type' => 'varchar(5)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 't_alt_y_grid', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'alt_y_grid', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_right_axis', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'right_axis', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_right_axis_label', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'right_axis_label', 'type' => 'varchar(200)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_right_axis_format', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'right_axis_format', 'type' => 'mediumint(8)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_right_axis_formatter', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'right_axis_formatter', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_left_axis_formatter', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'left_axis_formatter', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_no_gridfit', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'no_gridfit', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_unit_length', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'unit_length', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_tab_width', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'tab_width', 'type' => 'varchar(20)', 'NULL' => true, 'default' => '30');
	$data['columns'][] = array('name' => 't_dynamic_labels', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'dynamic_labels', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_force_rules_legend', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'force_rules_legend', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_legend_position', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'legend_position', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 't_legend_direction', 'type' => 'char(2)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'legend_direction', 'type' => 'varchar(10)', 'NULL' => true);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'local_graph_id', 'columns' => 'local_graph_id');
	$data['keys'][] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$data['keys'][] = array('name' => 'title_cache', 'columns' => 'title_cache');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'utf8mb4';
	$data['comment'] = 'Stores the actual graph data.';
	api_plugin_db_table_create ('grid', 'grid_elim_templates_graph', $data);

	$data = array();
	$data['columns'][] = array('name' => 'local_graph_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'graph_templates_graph_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'grid_elim_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'grid_elim_templates_graph_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['primary'] = 'local_graph_id`,`graph_templates_graph_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores the Map of ELIM grid_elim_templates_graph to cacti graph_templates_graph.';
	api_plugin_db_table_create ('grid', 'grid_elim_templates_graph_map', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'local_graph_template_item_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'local_graph_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'graph_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'task_item_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'color_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alpha', 'type' => 'char(2)', 'NULL' => true, 'default' => 'FF');
	$data['columns'][] = array('name' => 'graph_type_id', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'line_width', 'type' => 'decimal(4,2)', 'NULL' => true, 'default' => '0.00');
	$data['columns'][] = array('name' => 'dashes', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'dash_offset', 'type' => 'mediumint(4)', 'NULL' => true);
	$data['columns'][] = array('name' => 'cdef_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vdef_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'shift', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'consolidation_function_id', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'textalign', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'text_format', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'hard_return', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'gprint_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'resource_name', 'type' => 'varchar(40)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'resource_option', 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$data['keys'][] = array('name' => 'local_graph_id_sequence', 'columns' => 'local_graph_id`,`sequence');
	$data['keys'][] = array('name' => 'local_graph_template_item_id', 'columns' => 'local_graph_template_item_id');
	$data['keys'][] = array('name' => 'task_item_id', 'columns' => 'task_item_id');
	$data['keys'][] = array('name' => 'cdef_id', 'columns' => 'cdef_id');
	$data['keys'][] = array('name' => 'vdef_id', 'columns' => 'vdef_id');
	$data['keys'][] = array('name' => 'color_id', 'columns' => 'color_id');
	$data['keys'][] = array('name' => 'gprint_id', 'columns' => 'gprint_id');
	$data['keys'][] = array('name' => 'lgi_gti', 'columns' => 'local_graph_id`,`graph_template_id');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'utf8mb4';
	$data['comment'] = 'Stores the actual graph item data.';
	api_plugin_db_table_create ('grid', 'grid_elim_templates_item', $data);

	$data = array();
	$data['columns'][] = array('name' => 'local_graph_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'graph_templates_item_id', 'unsigned' => true, 'type' => 'int(12)', 'NULL' => false);
	$data['columns'][] = array('name' => 'grid_elim_template_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false);
	$data['columns'][] = array('name' => 'grid_elim_templates_item_id', 'unsigned' => true, 'type' => 'int(12)', 'NULL' => false);
	$data['primary'] = 'local_graph_id`,`graph_templates_item_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores the Map of ELIM grid_elim_templates_item to cacti graph_templates_item.';
	api_plugin_db_table_create ('grid', 'grid_elim_templates_item_map', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'groupName', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJOBS', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numSSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numUSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpu', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`groupName';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_groups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poolType', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rsrcName', 'type' => 'varchar(128)', 'NULL' => true);
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(32)', 'NULL' => false);
	$data['columns'][] = array('name' => 'res_select', 'type' => 'varchar(128)', 'NULL' => false);
	$data['columns'][] = array('name' => 'slots_per_host', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'policies', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'loan_duration', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'retain', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'free', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'guar_config', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'guar_used', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'runJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'runSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pendJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pendSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memSlotUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'slotUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpuUtil', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Configuration of Guarantee Resource Pools';
	api_plugin_db_table_create ('grid', 'grid_guarantee_pool', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'consumer', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'alloc', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'alloc_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'guarantee_config', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'guarantee_used', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'total_used', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'runJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'runSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pendJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pendSlots', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`consumer';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Distribution Consumer Information for Guarantee Pool';
	api_plugin_db_table_create ('grid', 'grid_guarantee_pool_distribution', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'owner', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'clusterid_host', 'columns' => 'clusterid`,`host');
	$data['keys'][] = array('name' => 'clusterid_owner', 'columns' => 'clusterid`,`owner');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Normalized Host Membership for Guarantee Pool';
	api_plugin_db_table_create ('grid', 'grid_guarantee_pool_hosts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`queue';
	$data['keys'][] = array('name' => ' queue', 'columns' => 'queue');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Loan Policies for Guarantee Pool';
	api_plugin_db_table_create ('grid', 'grid_guarantee_pool_loan_queues', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_host_closure_events` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`host` varchar(64) NOT NULL DEFAULT '',
		`admin` varchar(45) NOT NULL DEFAULT '',
		`event_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`lockid` varchar(128) NOT NULL DEFAULT '',
		`hCtrlMsg` varchar(255) NOT NULL DEFAULT '',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`host`,`event_time`,`lockid`),
		KEY `summarized` (`clusterid`,`host`,`lockid`),
		KEY `event_time_lockid` (`event_time`,`lockid`),
		KEY `last_updated` (`last_updated`),
		KEY `lockid` (`lockid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_host_closure_events_finished` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`host` varchar(64) NOT NULL DEFAULT '',
		`admin` varchar(45) NOT NULL DEFAULT '',
		`event_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`lockid` varchar(128) NOT NULL DEFAULT '',
		`hCtrlMsg` varchar(255) NOT NULL DEFAULT '',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`host`,`event_time`,`lockid`),
		KEY `summarized` (`clusterid`,`host`,`lockid`),
		KEY `event_time_lockid` (`event_time`,`lockid`),
		KEY `last_updated` (`last_updated`),
		KEY `lockid` (`lockid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'lockid', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`lockid';
	$data['keys'][] = array('name' => 'lockid', 'columns' => 'lockid');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_host_closure_lockids', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'groupName', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`groupName`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hostgroups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'groupName', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpu', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJOBS', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memSlotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'slotUtil', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpuUtil', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memUsed', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memRequested', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'memReserved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`groupName';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Tracks Host Group Statistics';
	api_plugin_db_table_create ('grid', 'grid_hostgroups_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hostType', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'hostModel', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpuFactor', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxCpus', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxMem', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxSwap', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxTmp', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'nDisks', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'resources', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'excl_resources', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'windows', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'isServer', 'type' => 'char(1)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'licensed', 'type' => 'char(1)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rexPriority', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'licFeaturesNeeded', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'licClass', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'nProcs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cores', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'nThreads', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ngpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gMaxFactor', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpu_shared_avg_mut', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpu_shared_avg_ut', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'first_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'host`,`clusterid';
	$data['keys'][] = array('name' => 'isServer', 'columns' => 'isServer');
	$data['keys'][] = array('name' => 'hostType', 'columns' => 'hostType');
	$data['keys'][] = array('name' => 'hostModel', 'columns' => 'hostModel');
	$data['keys'][] = array('name' => 'last_seen', 'columns' => 'last_seen');
	$data['keys'][] = array('name' => 'clusterid_host', 'columns' => 'clusterid`,`host');
	$data['keys'][] = array('name' => 'clusterid_is_server', 'columns' => 'clusterid`,`isServer');
	$data['keys'][] = array('name' => 'licFeaturesNeeded', 'columns' => 'licFeaturesNeeded');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hostinfo', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_hostinfo_gpu` (
		`host` varchar(64) NOT NULL DEFAULT '',
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_id` int(10) unsigned NOT NULL DEFAULT 0,
		`gBrand` varchar(20) NOT NULL DEFAULT '',
		`gModel` varchar(20) NOT NULL DEFAULT '',
		`gpu_mode` int(10) unsigned NOT NULL DEFAULT 0,
		`pstatus` int(10) unsigned NOT NULL DEFAULT 0,
		`status` varchar(20) DEFAULT NULL,
		`gpu_error` varchar(255) NOT NULL DEFAULT '',
		`gpu_driver` varchar(64) NOT NULL DEFAULT '',
		`gpu_factor` varchar(64) NOT NULL DEFAULT '',
		`gpu_temp` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_ecc` float NOT NULL DEFAULT 0,
		`gpu_ut` float NOT NULL DEFAULT 0,
		`gpu_mut` float NOT NULL DEFAULT 0,
		`gpu_power_ut` float NOT NULL DEFAULT 0,
		`gpu_mtotal` float NOT NULL DEFAULT 0,
		`gpu_mused` float NOT NULL DEFAULT 0,
		`gvendor` int(10) unsigned NOT NULL DEFAULT 0,
		`driverVersion` varchar(256) NOT NULL DEFAULT '',
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`host`,`clusterid`,`gpu_id`),
		KEY `clusterid_last_updated` (`clusterid`,`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'rtype', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'resource_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'host`,`clusterid`,`resource_name';
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['keys'][] = array('name' => 'resource_name', 'columns' => 'resource_name');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hostresources', $data);

	$data = array();
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hStatus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hCtrlMsg', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'prev_status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'time_in_state', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cpuFactor', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'windows', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'userJobLimit', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxJobs', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numRun', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numSSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numUSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mig', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'attr', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numRESERVE', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ngpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avail_shared_ngpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avail_excl_ngpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alloc_jsexcl_ngpus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'exceptional', 'unsigned' => true, 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'host`,`clusterid';
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'prev_status', 'columns' => 'prev_status');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hosts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'type_id', 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'message', 'type' => 'varchar(1024)', 'NULL' => false);
	$data['columns'][] = array('name' => 'acknowledgement', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'alert_time', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'type`,`type_id`,`clusterid`,`hostname';
	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hosts_alarm', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_hosts_gpu` (
		`host` varchar(64) NOT NULL DEFAULT '',
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_id` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_model` varchar(40) NOT NULL DEFAULT '',
		`gpu_mode` int(10) unsigned NOT NULL DEFAULT 0,
		`pstatus` int(10) unsigned NOT NULL DEFAULT 0,
		`status` varchar(20) DEFAULT NULL,
		`gpu_error` varchar(255) NOT NULL DEFAULT '',
		`prev_status` varchar(20) NOT NULL DEFAULT '',
		`time_in_state` int(10) unsigned NOT NULL DEFAULT 0,
		`mem_used` int(10) unsigned NOT NULL DEFAULT 0,
		`mem_rsv` int(10) unsigned NOT NULL DEFAULT 0,
		`numJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numRun` int(10) unsigned NOT NULL DEFAULT 0,
		`numSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numRSV` int(10) unsigned NOT NULL DEFAULT 0,
		`socketid` int(10) unsigned NOT NULL DEFAULT 0,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`host`,`clusterid`,`gpu_id`),
		KEY `clusterid_last_updated` (`clusterid`,`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'idle_slot_time', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobs_done', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobs_exited', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'job_last_started', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_ended', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_suspended', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_exited', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'host`,`clusterid';
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_hosts_jobtraffic', $data);

	# create table this way because of USING ... requirement
	db_execute("CREATE TABLE `grid_hosts_resources` (
		`host` varchar(64) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL,
		`resource_name` varchar(40) NOT NULL,
		`resType` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`flag` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`availValue` varchar(128) NOT NULL DEFAULT '',
		`totalValue` varchar(128) NOT NULL DEFAULT '',
		`reservedValue` varchar(128) NOT NULL DEFAULT '',
		`value` varchar(128) NOT NULL DEFAULT '',
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
		PRIMARY KEY (`host`,`clusterid`,`resource_name`,`resType`) USING HASH,
		KEY `value` (`value`),
		KEY `resource_name_host` (`resource_name`,`host`),
		KEY `clusterid` (`clusterid`) USING BTREE
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'resource_name', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'loadSched', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'loadStop', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'busySched', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'busyStop', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`hostname`,`resource_name';
	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
	$data['keys'][] = array('name' => 'resource_name', 'columns' => 'resource_name');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_host_threshold', $data);

	# create table this way because of USING ... requirement
	db_execute("CREATE TABLE `grid_job_daily_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`user` varchar(60) NOT NULL DEFAULT '',
		`stat` varchar(45) NOT NULL DEFAULT '',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`app` varchar(40) NOT NULL DEFAULT '',
		`from_host` varchar(64) NOT NULL DEFAULT '',
		`exec_host` varchar(64) NOT NULL DEFAULT '',
		`projectName` varchar(256) NOT NULL DEFAULT '',
		`jobs_in_state` int(10) unsigned NOT NULL DEFAULT 0,
		`jobs_wall_time` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_wall_time` int(10) unsigned NOT NULL DEFAULT 0,
		`jobs_stime` double NOT NULL,
		`jobs_utime` double NOT NULL,
		`slots_in_state` int(10) unsigned NOT NULL DEFAULT 0,
		`gpus_in_state` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_memory` double NOT NULL DEFAULT 0,
		`max_memory` double NOT NULL DEFAULT 0,
		`gpu_avg_mem` double NOT NULL DEFAULT 0,
		`gpu_max_mem` double NOT NULL DEFAULT 0,
		`interval_start` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded`),
		KEY `interval_start` (`interval_start`),
		KEY `interval_end` (`interval_end`) USING BTREE,
		KEY `date_recorded` (`date_recorded`),
		KEY `user` (`user`),
		KEY `stat` (`stat`),
		KEY `queue` (`queue`),
		KEY `app` (`app`),
		KEY `exec_host` (`exec_host`),
		KEY `projectName` (`projectName`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'stat', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'app', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'from_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'exec_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'jobs_reaching_state', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobs_wall_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpu_wall_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobs_stime', 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'jobs_utime', 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'slots_in_state', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpus_in_state', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_memory', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_memory', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpu_avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'gpu_max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'interval_start', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'interval_end', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded';
	$data['keys'][] = array('name' => 'interval_start', 'columns' => 'interval_start');
	$data['keys'][] = array('name' => 'interval_end', 'columns' => 'interval_end');
	$data['keys'][] = array('name' => 'stat', 'columns' => 'stat');
	$data['keys'][] = array('name' => 'queue', 'columns' => 'queue');
	$data['keys'][] = array('name' => 'date_recorded', 'columns' => 'date_recorded');
	$data['keys'][] = array('name' => 'clusterid_queue_stat', 'columns' => 'clusterid`,`queue`,`stat');
	$data['keys'][] = array('name' => 'clusterid_date_recorded', 'columns' => 'clusterid`,`date_recorded');
	$data['keys'][] = array('name' => 'clusterid_stat_interval_end', 'columns' => 'clusterid`,`stat`,`interval_end`,`user');
	$data['keys'][] = array('name' => 'projectName', 'columns' => 'projectName');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'app', 'columns' => 'app');
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_job_interval_stats', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs` (
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`options` int(10) unsigned NOT NULL DEFAULT 0,
		`options2` int(10) unsigned NOT NULL DEFAULT 0,
		`options3` int(10) unsigned NOT NULL DEFAULT 0,
		`options4` int(10) unsigned NOT NULL DEFAULT 0,
		`user` varchar(60) NOT NULL DEFAULT '',
		`stat` varchar(10) NOT NULL DEFAULT '',
		`prev_stat` varchar(10) NOT NULL DEFAULT '',
		`stat_changes` int(10) unsigned NOT NULL DEFAULT 0,
		`flapping_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`exitStatus` int(10) unsigned NOT NULL DEFAULT 0,
		`pendReasons` varchar(512) NOT NULL DEFAULT '',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`nice` varchar(4) NOT NULL DEFAULT '',
		`from_host` varchar(64) NOT NULL DEFAULT '',
		`exec_host` varchar(64) DEFAULT NULL,
		`execUid` int(10) unsigned NOT NULL DEFAULT 0,
		`loginShell` varchar(20) DEFAULT NULL,
		`execHome` varchar(255) NOT NULL DEFAULT '',
		`execCwd` varchar(255) NOT NULL DEFAULT '',
		`cwd` varchar(255) NOT NULL DEFAULT '',
		`exceptMask` int(10) NOT NULL DEFAULT 0,
		`exitInfo` int(10) NOT NULL DEFAULT 0,
		`postExecCmd` varchar(3000) NOT NULL DEFAULT '',
		`app` varchar(40) NOT NULL DEFAULT '',
		`execUsername` varchar(60) NOT NULL DEFAULT '',
		`mailUser` varchar(512) DEFAULT NULL,
		`jobname` varchar(3000) DEFAULT NULL,
		`jobPriority` int(10) unsigned NOT NULL DEFAULT 0,
		`jobPid` int(10) unsigned NOT NULL DEFAULT 0,
		`userPriority` int(11) DEFAULT 0,
		`projectName` varchar(256) NOT NULL DEFAULT '',
		`parentGroup` varchar(128) NOT NULL DEFAULT '',
		`sla` varchar(60) NOT NULL DEFAULT '',
		`jobGroup` varchar(512) NOT NULL DEFAULT '',
		`licenseProject` varchar(256) NOT NULL DEFAULT '',
		`command` varchar(3000) DEFAULT NULL,
		`inFile` varchar(255) DEFAULT NULL,
		`outFile` varchar(255) DEFAULT NULL,
		`errFile` varchar(255) DEFAULT NULL,
		`preExecCmd` varchar(3000) DEFAULT NULL,
		`res_requirements` varchar(512) DEFAULT NULL,
		`gpuResReq` varchar(512) DEFAULT NULL,
		`dependCond` varchar(1024) DEFAULT '',
		`mem_used` double DEFAULT NULL,
		`swap_used` double NOT NULL DEFAULT 0,
		`avg_mem` double DEFAULT 0,
		`max_memory` double DEFAULT 0,
		`gpu_mem_used` double DEFAULT 0,
		`gpu_max_memory` double DEFAULT 0,
		`max_swap` double DEFAULT 0,
		`mem_requested` double DEFAULT 0,
		`mem_requested_oper` varchar(8) DEFAULT '',
		`mem_reserved` double DEFAULT 0,
		`cpu_used` double NOT NULL DEFAULT 0,
		`utime` double NOT NULL DEFAULT 0,
		`stime` double NOT NULL DEFAULT 0,
		`efficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`effic_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`numPIDS` int(10) unsigned NOT NULL DEFAULT 0,
		`numPGIDS` int(10) unsigned NOT NULL DEFAULT 0,
		`numThreads` int(10) unsigned NOT NULL DEFAULT 0,
		`pid_alarm_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`num_nodes` int(10) unsigned NOT NULL DEFAULT 1,
		`num_cpus` int(10) unsigned NOT NULL DEFAULT 1,
		`max_allocated_processes` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'job level allocated slot',
		`maxNumProcessors` int(10) unsigned NOT NULL DEFAULT 0,
		`num_gpus` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_mode` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`reserveTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`predictedStartTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`beginTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`termTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`runtimeEstimation` int(10) unsigned DEFAULT 0,
		`pend_time` int(10) unsigned NOT NULL DEFAULT 0,
		`psusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`run_time` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_exec_time` int(10) unsigned NOT NULL DEFAULT 0,
		`ususp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`ssusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`unkwn_time` int(10) unsigned NOT NULL DEFAULT 0,
		`prov_time` int(10) unsigned NOT NULL DEFAULT 0,
		`acJobWaitTime` int(10) unsigned NOT NULL DEFAULT 0,
		`hostSpec` varchar(40) DEFAULT NULL,
		`rlimit_max_cpu` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_wallt` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_swap` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_fsize` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_data` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_stack` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_core` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_rss` float unsigned NOT NULL DEFAULT 0,
		`job_start_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`job_end_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`job_scan_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`userGroup` varchar(60) NOT NULL,
		`jobDescription` varchar(512) DEFAULT '',
		`combinedResreq` varchar(512) DEFAULT '',
		`effectiveResreq` varchar(512) DEFAULT '',
		`gpuCombinedResreq` varchar(512) DEFAULT '',
		`gpuEffectiveResreq` varchar(512) DEFAULT '',
		`chargedSAAP` varchar(256) DEFAULT '',
		`ineligiblePendingTime` int(10) unsigned NOT NULL DEFAULT 0,
		`pendState` int(10) NOT NULL DEFAULT -1,
		`effectivePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT 0,
		`effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT 0,
		`isLoaningGSLA` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`cpuPeak` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`peakEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`memEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`cpuPeakReachedTime` double NOT NULL DEFAULT 0,
		`reservationId` varchar(128) DEFAULT NULL,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`),
		KEY `submit_time` (`submit_time`),
		KEY `projectName` (`projectName`),
		KEY `start_time` (`start_time`),
		KEY `end_time` (`end_time`),
		KEY `job_start_logged` (`job_start_logged`),
		KEY `job_end_logged` (`job_end_logged`),
		KEY `job_scan_logged` (`job_scan_logged`),
		KEY `jobname` (`jobname`),
		KEY `queue_clusterid` (`queue`,`clusterid`),
		KEY `stat_clusterid_exitInfo` (`stat`,`clusterid`,`exitInfo`),
		KEY `user_clusterid` (`user`,`clusterid`),
		KEY `from_host_clusterid` (`from_host`,`clusterid`),
		KEY `exec_host_clusterid` (`exec_host`,`clusterid`),
		KEY `swap_used` (`swap_used`),
		KEY `cpu_used` (`cpu_used`),
		KEY `mem_used` (`mem_used`),
		KEY `num_nodes` (`num_nodes`),
		KEY `num_cpus` (`num_cpus`),
		KEY `pend_time` (`pend_time`),
		KEY `ineligiblePendingTime` (`ineligiblePendingTime`),
		KEY `effectivePendingTimeLimit` (`effectivePendingTimeLimit`),
		KEY `effectiveEligiblePendingTimeLimit` (`effectiveEligiblePendingTimeLimit`),
		KEY `psusp_time` (`psusp_time`),
		KEY `run_time` (`run_time`),
		KEY `ususp_time` (`ususp_time`),
		KEY `ssusp_time` (`ssusp_time`),
		KEY `unkwn_time` (`unkwn_time`),
		KEY `prov_time` (`prov_time`),
		KEY `nice` (`nice`),
		KEY `app` (`app`),
		KEY `prev_stat` (`prev_stat`),
		KEY `stat_changes` (`stat_changes`),
		KEY `flapping_logged` (`flapping_logged`),
		KEY `efficiency` (`efficiency`),
		KEY `effic_logged` (`effic_logged`),
		KEY `userGroup` (`userGroup`),
		KEY `indexid` (`indexid`),
		KEY `jobid` (`jobid`),
		KEY `pid_alarm_logged` (`pid_alarm_logged`),
		KEY `clusterid_end_logged` (`clusterid`,`job_end_logged`),
		KEY `clusterid_stat_end_logged` (`clusterid`,`stat`,`job_end_logged`),
		KEY `clusterid_stat_start_time` (`clusterid`,`stat`,`start_time`),
		KEY `clusterid_stat_last_updated` (`clusterid`,`stat`,`last_updated`),
		KEY `last_updated` (`last_updated`),
		KEY `stat_last_updated` (`stat`,`last_updated`),
		KEY `licenseProject` (`licenseProject`),
		KEY `jobGroup` (`jobGroup`),
		KEY `sla` (`sla`),
		KEY `chargedSAAP` (`chargedSAAP`),
		KEY `exitInfo` (`exitInfo`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;");   


	$data = array();
	$data['columns'][] = array('name' => 'exec_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'exec_host`,`clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_exec_hosts', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_finished` (
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`options` int(10) unsigned NOT NULL DEFAULT 0,
		`options2` int(10) unsigned NOT NULL DEFAULT 0,
		`options3` int(10) unsigned NOT NULL DEFAULT 0,
		`options4` int(10) unsigned NOT NULL DEFAULT 0,
		`user` varchar(60) NOT NULL DEFAULT '',
		`stat` varchar(10) NOT NULL DEFAULT '',
		`prev_stat` varchar(10) NOT NULL DEFAULT '',
		`stat_changes` int(10) unsigned NOT NULL DEFAULT 0,
		`flapping_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`exitStatus` int(10) unsigned NOT NULL DEFAULT 0,
		`pendReasons` varchar(512) NOT NULL DEFAULT '',
		`queue` varchar(60) NOT NULL DEFAULT '',
		`nice` varchar(4) NOT NULL DEFAULT '',
		`from_host` varchar(64) NOT NULL DEFAULT '',
		`exec_host` varchar(64) DEFAULT NULL,
		`execUid` int(10) unsigned NOT NULL DEFAULT 0,
		`loginShell` varchar(20) DEFAULT NULL,
		`execHome` varchar(255) NOT NULL DEFAULT '',
		`execCwd` varchar(255) NOT NULL DEFAULT '',
		`cwd` varchar(255) NOT NULL DEFAULT '',
		`exceptMask` int(10) NOT NULL DEFAULT 0,
		`exitInfo` int(10) NOT NULL DEFAULT 0,
		`postExecCmd` varchar(3000) NOT NULL DEFAULT '',
		`app` varchar(40) NOT NULL DEFAULT '',
		`execUsername` varchar(60) NOT NULL DEFAULT '',
		`mailUser` varchar(512) DEFAULT NULL,
		`jobname` varchar(3000) DEFAULT NULL,
		`jobPriority` int(10) unsigned NOT NULL DEFAULT 0,
		`jobPid` int(10) unsigned NOT NULL DEFAULT 0,
		`userPriority` int(11) DEFAULT 0,
		`projectName` varchar(256) NOT NULL DEFAULT '',
		`parentGroup` varchar(128) NOT NULL DEFAULT '',
		`sla` varchar(60) NOT NULL DEFAULT '',
		`jobGroup` varchar(512) NOT NULL DEFAULT '',
		`licenseProject` varchar(256) NOT NULL DEFAULT '',
		`command` varchar(3000) DEFAULT NULL,
		`inFile` varchar(255) DEFAULT NULL,
		`outFile` varchar(255) DEFAULT NULL,
		`errFile` varchar(255) DEFAULT NULL,
		`preExecCmd` varchar(3000) DEFAULT NULL,
		`res_requirements` varchar(512) DEFAULT NULL,
		`gpuResReq` varchar(512) DEFAULT NULL,
		`dependCond` varchar(1024) DEFAULT '',
		`mem_used` double DEFAULT NULL,
		`swap_used` double NOT NULL DEFAULT 0,
		`avg_mem` double DEFAULT 0,
		`max_memory` double DEFAULT 0,
		`gpu_mem_used` double DEFAULT 0,
		`gpu_max_memory` double DEFAULT 0,
		`max_swap` double DEFAULT 0,
		`mem_requested` double DEFAULT 0,
		`mem_requested_oper` varchar(8) DEFAULT '',
		`mem_reserved` double DEFAULT 0,
		`cpu_used` double NOT NULL DEFAULT 0,
		`utime` double NOT NULL DEFAULT 0,
		`stime` double NOT NULL DEFAULT 0,
		`efficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`effic_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`numPIDS` int(10) unsigned NOT NULL DEFAULT 0,
		`numPGIDS` int(10) unsigned NOT NULL DEFAULT 0,
		`numThreads` int(10) unsigned NOT NULL DEFAULT 0,
		`pid_alarm_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`num_nodes` int(10) unsigned NOT NULL DEFAULT 1,
		`num_cpus` int(10) unsigned NOT NULL DEFAULT 1,
		`max_allocated_processes` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'job level allocated slot',
		`maxNumProcessors` int(10) unsigned NOT NULL DEFAULT 0,
		`num_gpus` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_mode` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`reserveTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`predictedStartTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`beginTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`termTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`runtimeEstimation` int(10) unsigned DEFAULT 0,
		`pend_time` int(10) unsigned NOT NULL DEFAULT 0,
		`psusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`run_time` int(10) unsigned NOT NULL DEFAULT 0,
		`gpu_exec_time` int(10) unsigned NOT NULL DEFAULT 0,
		`ususp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`ssusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`unkwn_time` int(10) unsigned NOT NULL DEFAULT 0,
		`prov_time` int(10) unsigned NOT NULL DEFAULT 0,
		`acJobWaitTime` int(10) unsigned NOT NULL DEFAULT 0,
		`hostSpec` varchar(40) DEFAULT NULL,
		`rlimit_max_cpu` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_wallt` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_swap` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_fsize` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_data` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_stack` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_core` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_rss` float unsigned NOT NULL DEFAULT 0,
		`job_start_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`job_end_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`job_scan_logged` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`userGroup` varchar(60) NOT NULL,
		`jobDescription` varchar(512) DEFAULT '',
		`combinedResreq` varchar(512) DEFAULT '',
		`effectiveResreq` varchar(512) DEFAULT '',
		`gpuCombinedResreq` varchar(512) DEFAULT '',
		`gpuEffectiveResreq` varchar(512) DEFAULT '',
		`chargedSAAP` varchar(256) DEFAULT '',
		`ineligiblePendingTime` int(10) unsigned NOT NULL DEFAULT 0,
		`pendState` int(10) NOT NULL DEFAULT -1,
		`effectivePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT 0,
		`effectiveEligiblePendingTimeLimit` int(10) unsigned NOT NULL DEFAULT 0,
		`isLoaningGSLA` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`cpuPeak` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`peakEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`memEfficiency` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`cpuPeakReachedTime` double NOT NULL DEFAULT 0,
		`reservationId` varchar(128) DEFAULT NULL,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`),
		KEY `submit_time` (`submit_time`),
		KEY `projectName` (`projectName`),
		KEY `start_time` (`start_time`),
		KEY `end_time` (`end_time`),
		KEY `jobname` (`jobname`),
		KEY `queue_clusterid` (`queue`,`clusterid`),
		KEY `user_clusterid` (`user`,`clusterid`),
		KEY `from_host_clusterid` (`from_host`,`clusterid`),
		KEY `exec_host_clusterid` (`exec_host`,`clusterid`),
		KEY `cpu_used` (`cpu_used`),
		KEY `mem_used` (`mem_used`),
		KEY `num_nodes` (`num_nodes`),
		KEY `num_cpus` (`num_cpus`),
		KEY `pend_time` (`pend_time`),
		KEY `run_time` (`run_time`),
		KEY `stat_changes` (`stat_changes`),
		KEY `efficiency` (`efficiency`),
		KEY `userGroup` (`userGroup`),
		KEY `indexid` (`indexid`),
		KEY `jobid` (`jobid`),
		KEY `last_updated` (`last_updated`),
		KEY `licenseProject` (`licenseProject`),
		KEY `jobGroup` (`jobGroup`),
		KEY `sla` (`sla`),
		KEY `stat_clusterid` (`stat`,`clusterid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;");


	$data = array();
	$data['columns'][] = array('name' => 'from_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'from_host`,`clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_from_hosts', $data);

	# create table this way because of UNIQUE KEY requirement
	db_execute("CREATE TABLE `grid_jobs_gpu_rusage` (
		`id` bigint(8) unsigned NOT NULL AUTO_INCREMENT,
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`host` varchar(64) NOT NULL DEFAULT '',
		`gpu_id` smallint(5) NOT NULL DEFAULT 0,
		`exec_time` float NOT NULL DEFAULT 0,
		`energy` float NOT NULL DEFAULT 0,
		`sm_ut_avg` float NOT NULL DEFAULT 0,
		`sm_ut_max` float NOT NULL DEFAULT 0,
		`sm_ut_min` float NOT NULL DEFAULT 0,
		`mem_ut_avg` float NOT NULL DEFAULT 0,
		`mem_ut_max` float NOT NULL DEFAULT 0,
		`mem_ut_min` float NOT NULL DEFAULT 0,
		`gpu_mused_max` float NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`),
		UNIQUE KEY `cid_jid_idx_subtime_hname_gid_utime` (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`gpu_id`,`update_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# create table this way because of COMMENT requirement
	db_execute("CREATE TABLE `grid_jobs_host_rusage` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`host` varchar(64) NOT NULL DEFAULT '',
		`utime` float NOT NULL DEFAULT 0,
		`stime` float NOT NULL DEFAULT 0,
		`mem` float NOT NULL DEFAULT 0,
		`swap` float NOT NULL DEFAULT 0,
		`processes` int(11) NOT NULL DEFAULT 0 COMMENT 'job host level allocated slot',
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`update_time`),
		KEY `update_time` (`update_time`),
		KEY `submit_time` (`submit_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'indexid', 'type' => 'varchar(45)', 'NULL' => false);
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'cumulative_cpu', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'notified', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`submit_time`,`indexid`,`jobid';
	$data['keys'][] = array('name' => 'present', 'columns' => 'present');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_idled', $data);

	$data = array();
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'exec_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'processes', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ngpus', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'isborrowed', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['primary'] = 'clusterid`,`jobid`,`indexid`,`submit_time`,`exec_host';
	$data['keys'][] = array('name' => 'exec_host', 'columns' => 'exec_host');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_jobhosts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'exec_host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'processes', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ngpus', 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'isborrowed', 'type' => 'tinyint(3)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['primary'] = 'clusterid`,`jobid`,`indexid`,`submit_time`,`exec_host';
	$data['keys'][] = array('name' => 'exec_host', 'columns' => 'exec_host');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_jobhosts_finished', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'indexid', 'type' => 'varchar(45)', 'NULL' => false);
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_time', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'rusage_memory', 'unsigned' => true, 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'rusage_swap', 'unsigned' => true, 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_memory', 'unsigned' => true, 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_swap', 'unsigned' => true, 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'notified', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`submit_time`,`indexid`,`jobid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_memvio', $data);

	$data = array();
	$data['columns'][] = array('name' => 'issusp', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'reason_code', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'sub_reason_code', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'reason', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'issusp`,`reason_code`,`sub_reason_code';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_pendreason_maps', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_pendreasons` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`issusp` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`reason` int(10) unsigned NOT NULL DEFAULT 0,
		`subreason` varchar(40) NOT NULL DEFAULT '',
		`type` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`detail` varchar(128) NOT NULL DEFAULT '',
		`ratio` float NOT NULL DEFAULT 0,
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`type`,`end_time`),
		KEY `clusterid_end_time_last_updated` (`clusterid`,`end_time`,`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_pendreasons_finished` (
		`clusterid` int(10) unsigned NOT NULL,
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`issusp` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`reason` int(10) unsigned NOT NULL DEFAULT 0,
		`subreason` varchar(40) NOT NULL DEFAULT '',
		`type` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`detail` varchar(128) NOT NULL DEFAULT '',
		`ratio` float NOT NULL DEFAULT 0,
		`start_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`issusp`,`reason`,`subreason`,`type`,`end_time`),
		KEY `clusterid_end_time` (`clusterid`,`end_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_processes` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) unsigned NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`host` varchar(64) NOT NULL DEFAULT '',
		`PID` int(10) unsigned NOT NULL DEFAULT 0,
		`PGID` int(10) unsigned NOT NULL DEFAULT 0,
		`mem` double DEFAULT NULL,
		`swap` double DEFAULT NULL,
		`utime` double DEFAULT NULL,
		`stime` double DEFAULT NULL,
		`first_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`host`,`PID`) USING BTREE,
		KEY `clusterid_host` (`clusterid`,`host`),
		KEY `pid` (`PID`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'queue`,`clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_queues', $data);

	$data = array();
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`jobid`,`indexid`,`submit_time`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_reqhosts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'clusterid`,`jobid`,`indexid`,`submit_time`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_reqhosts_finished', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'rlimit_max_wallt', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'runtimeEstimation', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'run_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'notified', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`submit_time`,`indexid`,`jobid`,`type`,`rlimit_max_wallt`,`runtimeEstimation';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_runtime', $data);

	# create table this way because of COMMENT requirement
	db_execute("CREATE TABLE `grid_jobs_rusage` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`utime` float NOT NULL DEFAULT 0,
		`stime` float NOT NULL DEFAULT 0,
		`avgCpuEffi` decimal(9,5) NOT NULL DEFAULT 0.00000,
		`mem` float NOT NULL DEFAULT 0,
		`swap` float NOT NULL DEFAULT 0,
		`mem_reserved` float NOT NULL DEFAULT 0,
		`npids` int(10) unsigned NOT NULL DEFAULT 0,
		`npgids` int(10) unsigned NOT NULL DEFAULT 0,
		`nthreads` int(10) unsigned NOT NULL DEFAULT 0,
		`num_cpus` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'job level allocated slot',
		`pids` varchar(1024) NOT NULL DEFAULT '',
		`pgids` varchar(40) NOT NULL DEFAULT '',
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`update_time`),
		KEY `update_time` (`update_time`),
		KEY `submit_time` (`submit_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_sla_loaning` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`poolname` varchar(60) NOT NULL DEFAULT '',
		`numRsrc` int(10) DEFAULT 0,
		`mem` double DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`poolname`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_jobs_sla_loaning_finished` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`jobid` bigint(20) NOT NULL DEFAULT 0,
		`indexid` int(10) unsigned NOT NULL DEFAULT 0,
		`submit_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`poolname` varchar(60) NOT NULL DEFAULT '',
		`numRsrc` int(10) DEFAULT 0,
		`mem` double DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`clusterid`,`jobid`,`indexid`,`submit_time`,`poolname`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'stat', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'stat`,`clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'user`,`clusterid';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_jobs_users', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'licenseProject', 'type' => 'varchar(256)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJOBS', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpu', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`licenseProject';
	$data['keys'][] = array('name' => 'present', 'columns' => 'present');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Tracks License Project Information';
	api_plugin_db_table_create ('grid', 'grid_license_projects', $data);

	$data = array();
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'clusterid', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'prev_status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'time_in_state', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'istatus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'r15s', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'r1m', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'r15m', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ut', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pg', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'io', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ls', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'it', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'tmp', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'swp', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'host`,`clusterid';
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'prev_status', 'columns' => 'prev_status');
	$data['keys'][] = array('name' => 'istatus', 'columns' => 'istatus');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_load', $data);

	$data = array();
	$data['columns'][] = array('name' => 'hostType_match', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'signal', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'action', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'abreviation', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'text', 'NULL' => false);
	$data['primary'] = 'hostType_match`,`signal`,`abreviation';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_os_signals', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'parameter', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'parameter_class', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'parameter_value', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'parameter_default', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'clusterid`,`parameter';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_params', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_pendreasons_ignore` (
		`user_id` mediumint(8) unsigned NOT NULL,
		`issusp` tinyint(3) unsigned NOT NULL DEFAULT 0,
		`reason` int(10) unsigned NOT NULL,
		`subreason` varchar(40) NOT NULL DEFAULT '',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`user_id`,`issusp`,`reason`,`subreason`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'poller_name', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_lbindir', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_licserver_threads', 'type' => 'int(11)', 'NULL' => false, 'default' => '5');
	$data['columns'][] = array('name' => 'poller_location', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_support_info', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'lsf_version', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1017');
	$data['columns'][] = array('name' => 'remote', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'poller_max_insert_packet_size', 'type' => 'varchar(32)', 'NULL' => true);
	$data['primary'] = 'poller_id';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_pollers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'pid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'taskname', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'taskid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'heartbeat', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'taskname`,`taskid';
	$data['type'] = 'MEMORY';
	$data['charset'] = 'latin1';
	$data['row_format'] = 'Fixed';
	api_plugin_db_table_create ('grid', 'grid_processes', $data);

	# create table this way because of COMMENT requirement
	db_execute("CREATE TABLE `grid_projects` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`projectName` varchar(256) NOT NULL,
		`numRUN` int(10) unsigned NOT NULL DEFAULT 0,
		`numPEND` int(10) unsigned NOT NULL DEFAULT 0,
		`numJOBS` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'num slots of this project',
		`pendJOBS` int(10) unsigned NOT NULL DEFAULT 0,
		`runJOBS` int(10) unsigned NOT NULL DEFAULT 0,
		`totalJOBS` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'num jobs of this project',
		`efficiency` double NOT NULL DEFAULT 0,
		`avg_mem` double NOT NULL DEFAULT 0,
		`max_mem` double NOT NULL DEFAULT 0,
		`avg_swap` double NOT NULL DEFAULT 0,
		`max_swap` double NOT NULL DEFAULT 0,
		`total_cpu` bigint(20) unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`projectName`),
		KEY `present` (`present`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Tracks Project Information';");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_queues` (
		`queuename` varchar(60) NOT NULL DEFAULT '',
		`clusterid` int(11) NOT NULL DEFAULT 0,
		`description` varchar(255) DEFAULT NULL,
		`priority` int(10) unsigned NOT NULL DEFAULT 0,
		`nice` int(10) unsigned NOT NULL DEFAULT 0,
		`status` varchar(20) NOT NULL DEFAULT '',
		`reason` varchar(20) NOT NULL DEFAULT '',
		`numslots` int(10) unsigned NOT NULL DEFAULT 0,
		`maxjobs` varchar(10) NOT NULL DEFAULT '',
		`userJobLimit` varchar(20) NOT NULL DEFAULT '',
		`procJobLimit` varchar(5) NOT NULL DEFAULT '',
		`hostJobLimit` varchar(5) NOT NULL DEFAULT '',
		`nojobs` int(10) unsigned NOT NULL DEFAULT 0,
		`pendjobs` int(10) unsigned NOT NULL DEFAULT 0,
		`runjobs` int(10) unsigned NOT NULL DEFAULT 0,
		`suspjobs` int(10) unsigned NOT NULL DEFAULT 0,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		`avg_pend_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_pend_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_psusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_psusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_run_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_run_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_ususp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_ususp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_ssusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_ssusp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_unkwn_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_unkwn_time` int(10) unsigned NOT NULL DEFAULT 0,
		`avg_disp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`max_disp_time` int(10) unsigned NOT NULL DEFAULT 0,
		`hourly_started_jobs` double NOT NULL DEFAULT 0,
		`hourly_done_jobs` double NOT NULL DEFAULT 0,
		`hourly_exit_jobs` double NOT NULL DEFAULT 0,
		`daily_started_jobs` double NOT NULL DEFAULT 0,
		`daily_done_jobs` double NOT NULL DEFAULT 0,
		`daily_exit_jobs` double NOT NULL DEFAULT 0,
		`efficiency` double NOT NULL DEFAULT 0,
		`avg_mem` double NOT NULL DEFAULT 0,
		`max_mem` double NOT NULL DEFAULT 0,
		`avg_swap` double NOT NULL DEFAULT 0,
		`max_swap` double NOT NULL DEFAULT 0,
		`total_cpu` double NOT NULL DEFAULT 0,
		`dedicatedSlots` int(11) NOT NULL DEFAULT 0,
		`sharedSlots` int(11) NOT NULL DEFAULT 0,
		`openDedicatedSlots` int(11) NOT NULL DEFAULT 0,
		`openSharedSlots` int(11) NOT NULL DEFAULT 0,
		`windows` varchar(255) NOT NULL,
		`windowsD` varchar(255) NOT NULL,
		`hostSpec` varchar(64) NOT NULL,
		`qAttrib` int(11) NOT NULL DEFAULT 0,
		`qStatus` int(10) unsigned NOT NULL,
		`userShares` varchar(255) NOT NULL,
		`defaultHostSpec` varchar(64) NOT NULL,
		`procLimit` varchar(5) NOT NULL DEFAULT '',
		`admins` varchar(255) NOT NULL,
		`preCmd` varchar(255) NOT NULL,
		`postCmd` varchar(255) NOT NULL,
		`requeueEValues` varchar(64) NOT NULL,
		`resReq` varchar(255) NOT NULL,
		`slotHoldTime` int(10) unsigned NOT NULL,
		`sndJobsTo` varchar(255) NOT NULL,
		`rcvJobsFrom` varchar(255) NOT NULL,
		`resumeCond` varchar(255) NOT NULL,
		`stopCond` varchar(255) NOT NULL,
		`jobStarter` varchar(255) NOT NULL,
		`suspendActCmd` varchar(255) NOT NULL,
		`resumeActCmd` varchar(255) NOT NULL,
		`terminateActCmd` varchar(255) NOT NULL,
		`preemption` varchar(255) NOT NULL,
		`maxRschedTime` int(10) unsigned NOT NULL,
		`maxJobRequeue` int(10) unsigned NOT NULL,
		`chkpntDir` varchar(255) NOT NULL,
		`chkpntPeriod` int(11) NOT NULL DEFAULT 0,
		`imptJobBklg` int(11) NOT NULL DEFAULT 0,
		`chunkJobSize` int(10) unsigned NOT NULL,
		`minProcLimit` int(11) NOT NULL DEFAULT 0,
		`defProcLimit` int(11) NOT NULL DEFAULT 0,
		`fairshareQueues` varchar(255) NOT NULL,
		`defExtSched` varchar(255) NOT NULL,
		`mandExtSched` varchar(255) NOT NULL,
		`slotShare` int(11) NOT NULL DEFAULT 0,
		`slotPool` varchar(255) NOT NULL,
		`underRCond` int(10) unsigned NOT NULL,
		`overRCond` int(10) unsigned NOT NULL,
		`idleCond` double NOT NULL,
		`underRJobs` int(10) unsigned NOT NULL,
		`overRJobs` int(10) unsigned NOT NULL,
		`idleJobs` int(10) unsigned NOT NULL,
		`warningTimePeriod` int(11) NOT NULL DEFAULT 0,
		`warningAction` varchar(255) NOT NULL,
		`qCtrlMsg` varchar(255) NOT NULL,
		`rlimit_max_cpu` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_wallt` int(10) unsigned NOT NULL DEFAULT 0,
		`rlimit_max_swap` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_fsize` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_data` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_stack` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_core` float unsigned NOT NULL DEFAULT 0,
		`rlimit_max_rss` float unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`queuename`,`clusterid`),
		KEY `status` (`status`),
		KEY `reason` (`reason`),
		KEY `nice` (`nice`),
		KEY `clusterid` (`clusterid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'clusterid`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Show Queue Distribution for Queue';
	api_plugin_db_table_create ('grid', 'grid_queues_distrib', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`queue`,`host';
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'queue', 'columns' => 'queue');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_queues_hosts', $data);

	# create table this way because of HASH requirement
	db_execute("CREATE TABLE `grid_queues_shares` (
		`clusterid` int(10) unsigned NOT NULL,
		`queue` varchar(60) NOT NULL,
		`user_or_group` varchar(60) NOT NULL DEFAULT '',
		`shareAcctPath` varchar(200) NOT NULL DEFAULT '',
		`shares` int(10) unsigned NOT NULL,
		`priority` double NOT NULL,
		`parent_slots` int(10) unsigned DEFAULT NULL,
		`leaf_share` double DEFAULT NULL,
		`started` int(10) unsigned NOT NULL,
		`reserved` int(10) unsigned NOT NULL,
		`cpu_time` double NOT NULL,
		`run_time` bigint(20) unsigned NOT NULL,
		`pend_jobs` int(10) unsigned DEFAULT NULL,
		`pend_slots` int(10) unsigned DEFAULT NULL,
		`run_jobs` int(10) unsigned DEFAULT NULL,
		`run_slots` int(10) unsigned DEFAULT NULL,
		`relative_share` double DEFAULT NULL,
		`slot_share` int(10) unsigned DEFAULT NULL,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`queue`,`user_or_group`,`shareAcctPath`) USING HASH,
		KEY `user_or_group` (`user_or_group`),
		KEY `clusterid_user_or_group` (`clusterid`,`user_or_group`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;");

	# create table this way because of COMMENT requirement
	db_execute("CREATE TABLE `grid_queues_stats` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`queue` varchar(60) NOT NULL DEFAULT '',
		`numRUN` int(10) unsigned NOT NULL DEFAULT 0,
		`numPEND` int(10) unsigned NOT NULL DEFAULT 0,
		`numJOBS` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'num slots of this queue',
		`efficiency` double NOT NULL DEFAULT 0,
		`avg_mem` double NOT NULL DEFAULT 0,
		`max_mem` double NOT NULL DEFAULT 0,
		`avg_swap` double NOT NULL DEFAULT 0,
		`max_swap` double NOT NULL DEFAULT 0,
		`total_cpu` bigint(20) unsigned NOT NULL DEFAULT 0,
		`memSlotUtil` double NOT NULL DEFAULT 0,
		`slotUtil` double NOT NULL DEFAULT 0,
		`cpuUtil` double NOT NULL DEFAULT 0,
		`memUsed` bigint(20) unsigned NOT NULL DEFAULT 0,
		`memRequested` bigint(20) unsigned NOT NULL DEFAULT 0,
		`memReserved` bigint(20) unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`queue`),
		KEY `present` (`present`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Tracks Queue Statistical Information';");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'loadSched', 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'loadStop', 'type' => 'double', 'NULL' => false);
	$data['primary'] = 'clusterid`,`queue';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Dermines Queue Scheduling Load Thresholds';
	api_plugin_db_table_create ('grid', 'grid_queues_thresholds', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`queue`,`user';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_queues_users', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'user_or_group', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'nojobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'pendjobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'runjobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'suspjobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`queue`,`user_or_group';
	$data['keys'][] = array('name' => 'clusterid_user_or_group', 'columns' => 'clusterid`,`user_or_group');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_queues_users_stats', $data);

	# create table this way because of HASH requirement
	db_execute("CREATE TABLE `grid_resources` (
		`resource_name` varchar(20) NOT NULL,
		`value` varchar(128) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`resource_name`,`value`,`clusterid`) USING HASH,
		KEY `clusterid` (`clusterid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");


	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'consumer', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'priority', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'control_action', 'type' => 'varchar(128)', 'NULL' => false);
	$data['columns'][] = array('name' => 'auto_attach', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ego_res_req', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'max_host_idle_time', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'throughput', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Service Class Definitions';
	api_plugin_db_table_create ('grid', 'grid_service_class', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'acl_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'acl_member', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`acl_type`,`acl_member';
	$data['keys'][] = array('name' => 'acl_member', 'columns' => 'acl_member');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Access Control Information for Service Class';
	api_plugin_db_table_create ('grid', 'grid_service_class_access_control', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'goal_seq', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'goalType', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'goal_window', 'type' => 'varchar(1024)', 'NULL' => true);
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'min_config', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'goal_config', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'actual', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'optimum', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`goal_seq';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores Service Class Goals';
	api_plugin_db_table_create ('grid', 'grid_service_class_goals', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'user_or_group', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`name`,`user_or_group';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'User or User Groups Permitted to Use Service Class';
	api_plugin_db_table_create ('grid', 'grid_service_class_groups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user_id', 'unsigned' => true, 'type' => 'smallint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value', 'type' => 'mediumtext', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'filter_name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'user_id`,`name`,`filter_name';
	$data['type'] = 'MyISAM';
	$data['charset'] = 'utf8mb4';
	api_plugin_db_table_create ('grid', 'grid_settings', $data);

	api_plugin_db_add_column('grid', 'user_auth', array('name' => 'grid_settings', 'type' => 'char(2)', 'NULL' => false, 'default' => 'on', 'after' => 'graph_settings'));

	# create table this way because of HASH requirement
	db_execute("CREATE TABLE `grid_sharedresources` (
		`clusterid` int(10) unsigned NOT NULL,
		`resource_name` varchar(40) NOT NULL,
		`description` varchar(128) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`resource_name`) USING HASH,
		KEY `resource_name` (`resource_name`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'clustername', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'summary_status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'load_status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'r15s', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'r1m', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'r15m', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ut', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'pg', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'io', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'ls', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'it', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'tmp', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'swp', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'mem', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hStatus', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hCtrlMsg', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'bhost_status', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'cpuFactor', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'windows', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'userJobLimit', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJobs', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numRun', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numSSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numUSUSP', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numRESERVE', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'hostType', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'hostModel', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'maxCpus', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxMem', 'type' => 'varchar(20)', 'NULL' => true,);
	$data['columns'][] = array('name' => 'maxSwap', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'maxTmp', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'nDisks', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'isServer', 'type' => 'char(1)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'licensed', 'type' => 'char(1)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'rexPriority', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'licFeaturesNeeded', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'first_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_started', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_ended', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_suspended', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'job_last_exited', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'cacti_hostid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'monitor', 'type' => 'char(3)', 'NULL' => true);
	$data['columns'][] = array('name' => 'disabled', 'type' => 'char(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'cacti_status', 'type' => 'tinyint(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'status_rec_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'status_fail_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'min_time', 'type' => 'decimal(10,5)', 'NULL' => true, 'default' => '9.99999');
	$data['columns'][] = array('name' => 'max_time', 'type' => 'decimal(10,5)', 'NULL' => true, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'cur_time', 'type' => 'decimal(10,5)', 'NULL' => true, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'avg_time', 'type' => 'decimal(10,5)', 'NULL' => true, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'availability', 'type' => 'decimal(10,5)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`host';
	$data['keys'][] = array('name' => 'load_status', 'columns' => 'load_status');
	$data['keys'][] = array('name' => 'cacti_status', 'columns' => 'cacti_status');
	$data['keys'][] = array('name' => 'summary_status', 'columns' => 'summary_status');
	$data['keys'][] = array('name' => 'bhost_status', 'columns' => 'bhost_status');
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clustername');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'monitor', 'columns' => 'monitor');
	$data['keys'][] = array('name' => 'disabled', 'columns' => 'disabled');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_summary', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'unavail', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'busyclose', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'idleclose', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'lowres', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'busy', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'idlewjobs', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'idle', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'starved', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'admindown', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'blackhole', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false);
	$data['primary'] = 'clusterid`,`host';
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_summary_timeinstate', $data);

	# create table this way because of BTREE requirement
	db_execute("CREATE TABLE `grid_table_partitions` (
		`partition` varchar(5) NOT NULL,
		`table_name` varchar(45) NOT NULL,
		`min_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`max_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`partition`,`table_name`) USING BTREE,
		KEY `max_time` (`max_time`),
		KEY `min_time` (`min_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'groupname', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'username', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'shares', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`groupname`,`username';
	$data['keys'][] = array('name' => 'groupname', 'columns' => 'groupname');
	$data['keys'][] = array('name' => 'username', 'columns' => 'username');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('grid', 'grid_user_group_members', $data);

	$data = array();
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'userGroup', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'numRUN', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numPEND', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'numJOBS', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'efficiency', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_mem', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'avg_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'max_swap', 'type' => 'double', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_cpu', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'clusterid`,`userGroup';
	$data['keys'][] = array('name' => 'present', 'columns' => 'present');
	$data['type'] = 'InnoDB';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Tracks userGroup Stats';
	api_plugin_db_table_create ('grid', 'grid_user_group_stats', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `grid_users_or_groups` (
		`clusterid` int(10) unsigned NOT NULL DEFAULT 0,
		`user_or_group` varchar(60) NOT NULL DEFAULT '',
		`type` char(1) NOT NULL DEFAULT '',
		`procJobLimit` double NOT NULL DEFAULT 0,
		`maxJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numStartJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`numPEND` int(10) unsigned NOT NULL DEFAULT 0,
		`numRUN` int(10) unsigned NOT NULL DEFAULT 0,
		`numSSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numUSUSP` int(10) unsigned NOT NULL DEFAULT 0,
		`numRESERVE` int(10) unsigned NOT NULL DEFAULT 0,
		`maxPendJobs` int(10) unsigned NOT NULL DEFAULT 0,
		`efficiency` double NOT NULL,
		`first_seen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(3) unsigned NOT NULL DEFAULT 1,
		PRIMARY KEY (`clusterid`,`user_or_group`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

}
