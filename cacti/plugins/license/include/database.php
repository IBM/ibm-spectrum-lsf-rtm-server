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

function license_setup_database() {

	# Table has to be created this way because the api cannot handle UNIQUE KEY
	db_execute("CREATE TABLE `lic_application_accounting` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`application` varchar(40) DEFAULT '',
		`monthly_cost` double DEFAULT 0,
		`user_id` int(10) unsigned NOT NULL DEFAULT 0,
		`last_updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`id`),
		UNIQUE KEY `application` (`application`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user_feature_name', 'type' => 'varchar(80)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'application', 'type' => 'varchar(40)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'manager_hint', 'type' => 'varchar(255)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'critical', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'user_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'service_id`,`feature_name';
	$data['keys'][] = array('name' => 'feature_name', 'columns' => 'feature_name');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_application_feature_map', $data);

	# Table has to be created this way because the api cannot handle the enumerated type and default
	db_execute("CREATE TABLE `lic_daily_stats` (
		`service_id` int(10) unsigned NOT NULL DEFAULT 0,
		`feature` varchar(50) NOT NULL DEFAULT '',
		`user` varchar(60) NOT NULL DEFAULT '',
		`host` varchar(64) NOT NULL DEFAULT '',
		`action` varchar(20) NOT NULL DEFAULT '',
		`count` int(10) unsigned NOT NULL DEFAULT 0,
		`total_license_count` int(10) unsigned NOT NULL DEFAULT 0,
		`utilization` float NOT NULL DEFAULT 0,
		`peak_ut` float NOT NULL DEFAULT 0,
		`vendor` varchar(40) NOT NULL DEFAULT '0',
		`duration` int(10) unsigned NOT NULL DEFAULT 0,
		`transaction_count` int(10) unsigned NOT NULL DEFAULT 0,
		`type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
		`interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`,`date_recorded`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `interval_end` (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `vendor` (`vendor`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	# Table has to be created this way because the api cannot handle the enumerated type and default
	db_execute("CREATE TABLE `lic_daily_stats_today` (
		`service_id` int(10) unsigned NOT NULL DEFAULT 0,
		`feature` varchar(50) NOT NULL DEFAULT '',
		`user` varchar(60) NOT NULL DEFAULT '',
		`host` varchar(64) NOT NULL DEFAULT '',
		`action` varchar(20) NOT NULL DEFAULT '',
		`count` int(10) unsigned NOT NULL DEFAULT 0,
		`total_license_count` int(10) unsigned NOT NULL DEFAULT 0,
		`utilization` float NOT NULL DEFAULT 0,
		`peak_ut` float NOT NULL DEFAULT 0,
		`vendor` varchar(40) NOT NULL DEFAULT '0',
		`duration` int(10) unsigned NOT NULL DEFAULT 0,
		`transaction_count` int(10) unsigned NOT NULL DEFAULT 0,
		`type` enum('0','1','2','3','4','5','6','7','8') NOT NULL DEFAULT '0',
		`interval_end` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`date_recorded` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`service_id`,`feature`,`user`,`host`,`action`,`vendor`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `interval_end` (`interval_end`),
		KEY `date_recorded` (`date_recorded`),
		KEY `vendor` (`vendor`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `lic_daily_stats_traffic` (
		`service_id` int(10) unsigned NOT NULL,
		`feature` varchar(50) NOT NULL,
		`user` varchar(60) NOT NULL,
		`host` varchar(64) NOT NULL,
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		PRIMARY KEY (`service_id`,`feature`,`user`,`host`),
		KEY `feature` (`feature`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `last_updated` (`last_updated`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(12)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'portatserver',  'type' => 'int(12)', 'NULL' => false);
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'feature',       'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'action',        'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'no_of_license_out_in', 'type' => 'int(12)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'user',          'type' => 'varchar(200)', 'NULL' => false);
	$data['columns'][] = array('name' => 'host',          'type' => 'varchar(200)', 'NULL' => false);
	$data['columns'][] = array('name' => 'reasons',       'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'datetime',      'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_flexlm_log', $data);

	$data = array();
	$data['columns'][] = array('name' => 'seq', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'action', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_license_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'utilization', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vendor', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'duration', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'interval_end', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'event_id', 'unsigned' => true, 'type' => 'int(12)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'seq';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'interval_end', 'columns' => 'interval_end');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'vendor', 'columns' => 'vendor');
	$data['keys'][] = array('name' => 'event_id', 'columns' => 'event_id');
	$data['keys'][] = array('name' => 'action_ut', 'columns' => 'action`,`utilization');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_interval_stats', $data);

	# create table this way because of COMMENT requirement
	db_execute("CREATE TABLE `lic_managers` (
		`id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
		`hash` char(32) NOT NULL,
		`name` varchar(20) NOT NULL,
		`description` varchar(127) NOT NULL,
		`type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '0: default, 1: json',
		`logparser_binary` varchar(127) NOT NULL,
		`collector_binary` varchar(127) NOT NULL,
		`lm_client` varchar(127) NOT NULL,
		`lm_client_arg1` varchar(127) NOT NULL,
		`failover_hosts` tinyint(3) unsigned DEFAULT 1,
		`disabled` tinyint(3) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'poller_path', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'client_path', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_description', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_hostname', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_exechost', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_type', 'type' => 'smallint(3)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_pollers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'version', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'errorno', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`name';
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_servers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'poller_interval', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '300');
	$data['columns'][] = array('name' => 'poller_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_trigger', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	# Its unclear if poller_type is used
	$data['columns'][] = array('name' => 'poller_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'server_portatserver', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_timezone', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'server_name', 'type' => 'varchar(256)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_vendor', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_licensetype', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'server_licensefile', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_department', 'type' => 'varchar(45)', 'NULL' => true);
	$data['columns'][] = array('name' => 'server_location', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_region', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_support_info', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'enable_checkouts', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'timeout', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'errorno', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'disabled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'options_path', 'type' => 'varchar(2048)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'retries', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '3');
	$data['columns'][] = array('name' => 'status', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'status_event_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'cur_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'min_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'max_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'avg_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'total_polls', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'failed_polls', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'status_fail_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'status_rec_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'availability', 'type' => 'decimal(8,5)', 'NULL' => false, 'default' => '0.00000');
	$data['columns'][] = array('name' => 'server_subisv', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'file_path', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'prefix', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'service_id';
	$data['keys'][] = array('name' => 'server_location', 'columns' => 'server_location');
	$data['keys'][] = array('name' => 'server_region', 'columns' => 'server_region');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services', $data);

	api_plugin_db_add_column('license', 'host', array('name' => 'lic_server_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'host_template_id'));

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_version', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_number_to_expire', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_reserved_token', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_expiration_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`feature_name`,`feature_version`,`vendor_daemon`,`feature_expiration_date';
	$data['keys'][] = array('name' => 'feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'feature_version', 'columns' => 'feature_version');
	$data['keys'][] = array('name' => 'vendor_daemon', 'columns' => 'vendor_daemon');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_feature', $data);

	# create table this way because of ON UPDATE requirement
	db_execute("CREATE TABLE `lic_services_feature_details` (
		`service_id` int(10) unsigned NOT NULL DEFAULT 0,
		`vendor_daemon` varchar(40) NOT NULL DEFAULT '',
		`feature_name` varchar(50) NOT NULL DEFAULT '0',
		`subfeature` varchar(50) NOT NULL DEFAULT '',
		`feature_version` varchar(50) NOT NULL DEFAULT '',
		`username` varchar(60) NOT NULL DEFAULT '',
		`groupname` varchar(60) NOT NULL DEFAULT '',
		`hostname` varchar(64) NOT NULL DEFAULT '',
		`chkoutid` varchar(20) NOT NULL DEFAULT '',
		`restype` int(10) unsigned NOT NULL DEFAULT 0,
		`status` varchar(20) NOT NULL DEFAULT '',
		`tokens_acquired` int(10) unsigned NOT NULL DEFAULT 0,
		`tokens_acquired_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		`present` tinyint(1) NOT NULL DEFAULT 1,
		`lm_job_pid` int(10) unsigned DEFAULT 0,
		`clustername` varchar(128) NOT NULL DEFAULT '',
		`jobid` bigint(20) DEFAULT 0,
		`indexid` int(10) DEFAULT 0,
		`projectName` varchar(255) DEFAULT '',
		PRIMARY KEY (`service_id`,`vendor_daemon`,`feature_name`,`username`,`groupname`,`hostname`,`chkoutid`,`restype`,`status`,`tokens_acquired_date`),
		KEY `idx_vendor_daemon` (`vendor_daemon`),
		KEY `idx_feature_name` (`feature_name`),
		KEY `idx_username` (`username`),
		KEY `idx_hostname` (`hostname`),
		KEY `idx_status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;");

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_inuse_licenses', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_queued', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_reserved', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(45)', 'NULL' => false, 'default' => 'TBD');
	$data['columns'][] = array('name' => 'present', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'vendor_status', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'vendor_version', 'type' => 'varchar(30)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(29)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'feature_name`,`service_id';
	$data['keys'][] = array('name' => 'service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'vendor_daemon', 'columns' => 'vendor_daemon');
	$data['keys'][] = array('name' => 'feature_queued', 'columns' => 'feature_queued');
	$data['keys'][] = array('name' => 'feature_reserved', 'columns' => 'feature_reserved');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_feature_use', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id',       'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature',          'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'keyword',          'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'borrow_lowwater',  'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'linger',           'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'max_borrow_hours', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'max_overdraft',    'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'timeout',          'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',          'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`feature`,`keyword';
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Includes Feature Options';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_options_feature', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature',    'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'keyword',    'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'variable',   'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'otype',      'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',       'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes',      'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',    'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`feature`,`variable`,`otype`,`name`,`keyword';
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Per Feature/Type Options';
	api_plugin_db_table_create ('license', 'lic_services_options_feature_type', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'incexcl',    'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'otype',      'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',       'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes',      'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',    'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`incexcl`,`otype`,`name';
	$data['keys'][] = array('name' => 'incexcl', 'columns' => 'incexcl');
	$data['keys'][] = array('name' => 'otype', 'columns' => 'otype');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Per Feature/Type Options';
	api_plugin_db_table_create ('license', 'lic_services_options_feature_type', $data);

	# The lic_services_options_global has to be created this way because of the primary key
	# that api_plugin_db_table_create cannot handle
	db_execute("CREATE TABLE `lic_services_options_global` (
		`service_id` int(10) unsigned NOT NULL DEFAULT 0,
		`options_path` varchar(255) NOT NULL DEFAULT '',
		`debug_path` varchar(255) DEFAULT NULL,
		`report_path` varchar(255) DEFAULT NULL,
		`nolog_in` int(10) unsigned DEFAULT NULL,
		`nolog_out` int(10) unsigned DEFAULT NULL,
		`nolog_denied` int(10) unsigned DEFAULT NULL,
		`nolog_queued` int(10) unsigned DEFAULT NULL,
		`timeoutall` int(10) unsigned DEFAULT NULL,
		`groupcaseinsens` int(10) unsigned DEFAULT NULL,
		`present` tinyint(4) DEFAULT 1,
		PRIMARY KEY (`service_id`,`options_path`(191))
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Contains Per Options File Global Settings'");

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'type' => 'int(10)',     'unsigned' => true,  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'group',      'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host',       'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`group`,`host';
	$data['keys'][] = array('name' => 'group', 'columns' => 'group');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Shows Host Group in FLEXlm';
	api_plugin_db_table_create ('license', 'lic_services_options_host_groups', $data);


	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'type' => 'int(10)',     'unsigned' => true,  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'incexcl',    'type' => 'varchar(12)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'otype',      'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',       'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes',      'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`incexcl`,`otype`,`name';
	$data['keys'][] = array('name' => 'incexcl', 'columns' => 'incexcl');
	$data['keys'][] = array('name' => 'otype', 'columns' => 'otype');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_options_incexcl_all', $data);


	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'type' => 'int(10)',      'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'num_lic',    'type' => 'int(10)',      'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature',    'type' => 'varchar(50)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'keyword',    'type' => 'varchar(60)',  'NULL' => false);
	$data['columns'][] = array('name' => 'otype',      'type' => 'varchar(10)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',       'type' => 'varchar(40)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes',      'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',   'NULL' => false, 'unsigned' => true, 'default' => '1');
	$data['primary'] = 'service_id`,`feature`,`otype`,`name`,`keyword';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'otype_name', 'columns' => 'otype`,`name');
	$data['type'] = 'MyISAM';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_options_max', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'type' => 'int(10)',      'NULL' => false, 'unsigned' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'num_lic',    'type' => 'int(10)',      'NULL' => false, 'unsigned' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'feature',    'type' => 'varchar(50)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'keyword',    'type' => 'varchar(60)',  'NULL' => false);
	$data['columns'][] = array('name' => 'otype',      'type' => 'varchar(10)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name',       'type' => 'varchar(40)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes',      'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',   'NULL' => false, 'unsigned' => true, 'default' => '1');
	$data['primary'] = 'service_id`,`feature`,`otype`,`name`,`keyword';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'otype', 'columns' => 'otype');
	$data['type'] = 'MyISAM';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_options_reserve', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'type' => 'int(10)',     'unsigned' => true,  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'group',      'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user',       'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`group`,`user';
	$data['keys'][] = array('name' => 'group', 'columns' => 'group');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['type'] = 'MyISAM';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_services_options_user_groups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user_id',     'type' => 'smallint(8)',  'NULL' => false, 'unsigned' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'name',        'type' => 'varchar(50)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value',       'type' => 'mediumtext',   'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'filter_name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'user_id`,`name`,`filter_name';
	$data['type'] = 'MyISAM';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_settings', $data);

	$data = array();
	$data['columns'][] = array('name' => 'ip_range',   'type' => 'varchar(16)', 'NULL' => false);
	$data['columns'][] = array('name' => 'hostname',   'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ip_address', 'type' => 'varchar(16)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present',    'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'ip_range`,`hostname';
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores IP range and host membership from DNS';
	api_plugin_db_table_create ('license', 'lic_ip_ranges', $data);

	$data = array();
	$data['columns'][] = array('name' => 'ldap_group', 'type' => 'varchar(40)', 'NULL' => false);
	$data['columns'][] = array('name' => 'flex_group', 'type' => 'varchar(40)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present',  'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'ldap_group`,`flex_group';
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	$data['comment'] = 'A Mapping Table of LDAP Groups to FLEXlm Group';
	api_plugin_db_table_create ('license', 'lic_ldap_to_flex_groups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'group', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false);
	$data['columns'][] = array('name' => 'present',  'type' => 'tinyint(3)',  'unsigned' => true,  'NULL' => false, 'default' => '1');
	$data['primary'] = 'group`,`user';
	$data['type'] = 'MyISAM';
	$data['charset'] = 'latin1';
	$data['comment'] = 'Stores LDAP Group Information';
	api_plugin_db_table_create ('license', 'lic_ldap_groups', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'user';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_users_winsp', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id',            'type' => 'bigint(20)',  'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'service_id',    'type' => 'int(10)',     'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name',  'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'projectName',   'type' => 'varchar(50)', 'NULL' => false, 'default' => 'none');
	$data['columns'][] = array('name' => 'token_minutes', 'type' => 'int(10)',     'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'poll_time',     'type' => 'timestamp',   'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'idx_feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'idx_projectName', 'columns' => 'projectName');
	$data['keys'][] = array('name' => 'idx_service_id', 'columns' => 'service_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Contains the license consumption by project';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_daily_project_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id',            'type' => 'bigint(20)',  'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'service_id',    'type' => 'int(10)',     'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name',  'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName',   'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'token_minutes', 'type' => 'int(10)',     'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'poll_time',     'type' => 'timestamp',   'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'idx_feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'idx_projectName', 'columns' => 'projectName');
	$data['keys'][] = array('name' => 'idx_service_id', 'columns' => 'service_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Contains the license consumption by project for today';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_daily_project_stats_today', $data);

	$data = array();
	$data['columns'][] = array('name' => 'type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'errorno',     'type' => 'int(10)',      'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'sub_errorno', 'type' => 'int(10)',      'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'error_text',  'type' => 'varchar(512)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'type`,`errorno`,`sub_errorno';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	$data['charset'] = 'latin1';
	api_plugin_db_table_create ('license', 'lic_errorcode_maps', $data);

	db_execute('DROP TABLE IF EXISTS lic_flexlm_servers_feature_details');
	db_execute('DROP VIEW IF EXISTS lic_flexlm_servers_feature_details');
	db_execute('CREATE VIEW `lic_flexlm_servers_feature_details` AS select `lic_services`.`poller_id` AS `poller_id`,`lic_services_feature_details`.`service_id` AS `portatserver_id`,`lic_services_feature_details`.`vendor_daemon` AS `vendor_daemon`,`lic_services_feature_details`.`feature_name` AS `feature_name`,`lic_services_feature_details`.`subfeature` AS `subfeature`,`lic_services_feature_details`.`feature_version` AS `feature_version`,`lic_services_feature_details`.`username` AS `username`,`lic_services_feature_details`.`groupname` AS `groupname`,`lic_services_feature_details`.`hostname` AS `hostname`,`lic_services_feature_details`.`chkoutid` AS `chkoutid`,`lic_services_feature_details`.`restype` AS `restype`,`lic_services_feature_details`.`status` AS `status`,`lic_services_feature_details`.`tokens_acquired` AS `tokens_acquired`,`lic_services_feature_details`.`tokens_acquired_date` AS `tokens_acquired_date`,`lic_services_feature_details`.`last_updated` AS `last_updated`,`lic_services_feature_details`.`present` AS `present` from (`lic_services_feature_details` join `lic_services` on((`lic_services_feature_details`.`service_id` = `lic_services`.`service_id`)))');

	db_execute('DROP TABLE IF EXISTS lic_flexlm_servers_feature_use');
	db_execute('DROP VIEW IF EXISTS  lic_flexlm_servers_feature_use');
	db_execute('CREATE VIEW `lic_flexlm_servers_feature_use` AS select `lic_services`.`poller_id` AS `poller_id`,`lic_services_feature_use`.`service_id` AS `portatserver_id`,`lic_services_feature_use`.`feature_name` AS `feature_name`,`lic_services_feature_use`.`feature_max_licenses` AS `feature_max_licenses`,`lic_services_feature_use`.`feature_inuse_licenses` AS `feature_inuse_licenses`,`lic_services_feature_use`.`feature_queued` AS `feature_queued`,`lic_services_feature_use`.`feature_reserved` AS `feature_reserved`,`lic_services_feature_use`.`vendor_daemon` AS `vendor_daemon`,`lic_services_feature_use`.`present` AS `present`,`lic_services_feature_use`.`vendor_status` AS `vendor_status`,`lic_services_feature_use`.`vendor_version` AS `vendor_version`,`lic_services_feature_use`.`status` AS `status` from (`lic_services_feature_use` join `lic_services` on((`lic_services_feature_use`.`service_id` = `lic_services`.`service_id`)))');


	print "MARK:  finished  license_setup_database()\n";
}

