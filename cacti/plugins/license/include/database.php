<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2025                                          |
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
	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'application', 'type' => 'varchar(40)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'monthly_cost', 'type' => 'double', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'user_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'application', 'columns' => 'application');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_application_accounting', $data);

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
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_application_feature_map', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'action', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_license_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'utilization', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'peak_ut', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vendor', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'duration', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'transaction_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'enum("0","1","2","3","4","5","6","7","8")', 'NULL' => true);
	$data['columns'][] = array('name' => 'interval_end', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['primary'] = 'service_id`,`feature`,`user`,`host`,`action`,`vendor`,`date_recorded';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'interval_end', 'columns' => 'interval_end');
	$data['keys'][] = array('name' => 'date_recorded', 'columns' => 'date_recorded');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'vendor', 'columns' => 'vendor');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_daily_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'feature', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP');
	$data['primary'] = 'service_id`,`feature`,`user`,`host';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'last_updated', 'columns' => 'last_updated');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_daily_stats_traffic', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'action', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_license_count', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'utilization', 'type' => 'float', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vendor', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'duration', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'interval_end', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'date_recorded', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'event_id', 'unsigned' => true, 'type' => 'int(12)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'service_id`,`feature`,`user`,`host`,`action`,`vendor`,`date_recorded`,`event_id';
	$data['keys'][] = array('name' => 'feature', 'columns' => 'feature');
	$data['keys'][] = array('name' => 'interval_end', 'columns' => 'interval_end');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'vendor', 'columns' => 'vendor');
	$data['keys'][] = array('name' => 'event_id', 'columns' => 'event_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_interval_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'char(32)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(15)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'type', 'type' => 'tinyint(3)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'logparser_binary', 'type' => 'varchar(127)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'collector_binary', 'type' => 'varchar(127)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'lm_client', 'type' => 'varchar(127)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'lm_client_arg1', 'type' => 'varchar(127)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'failover_hosts', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => true, 'default' => '1');
	$data['columns'][] = array('name' => 'disabled', 'type' => 'tinyint(3)', 'NULL' => true, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_managers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'poller_path', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'client_path', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_description', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_hostname', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_exechost', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'poller_type', 'type' => 'smallint(5)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'poller_type', 'columns' => 'poller_type');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_pollers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'version', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'service_id`,`name';
	$data['keys'][] = array('name' => 'service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_servers', $data);

	$data = array();
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_interval', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '300');
	$data['columns'][] = array('name' => 'poller_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'poller_trigger', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'poller_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'server_portatserver', 'type' => 'varchar(512)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_subisv', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_timezone', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_querybin_path', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_name', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_vendor', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_licensetype', 'type' => 'varchar(20)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'server_licensefile', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_department', 'type' => 'varchar(45)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'server_location', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_region', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'server_support_info', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'enable_checkouts', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'timeout', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'disabled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => '');
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
	$data['columns'][] = array('name' => 'file_path', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'options_path', 'type' => 'varchar(2048)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'prefix', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'service_id';
	$data['keys'][] = array('name' => 'server_location', 'columns' => 'server_location');
	$data['keys'][] = array('name' => 'server_region', 'columns' => 'server_region');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_services', $data);

	api_plugin_db_add_column('license', 'host', array('name' => 'lic_server_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'host_template_id'));

	$data = array();
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_version', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_number_to_expire', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'total_reserved_token', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_expiration_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'poller_id`,`service_id`,`feature_name`,`feature_version`,`vendor_daemon`,`feature_expiration_date';
	$data['keys'][] = array('name' => 'poller_id', 'columns' => 'poller_id');
	$data['keys'][] = array('name' => 'service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'feature_version', 'columns' => 'feature_version');
	$data['keys'][] = array('name' => 'vendor_daemon', 'columns' => 'vendor_daemon');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_services_feature', $data);

	$data = array();
	$data['columns'][] = array('name' => 'poller_id',    'type' => 'int(10)',      'NULL' => false, 'default' => '0', 'unsigned' => true);
	$data['columns'][] = array('name' => 'service_id',   'type' => 'int(10)',      'NULL' => false, 'default' => '0', 'unsigned' => true);
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)',  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'subfeature',   'type' => 'varchar(50)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_version', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'username',     'type' => 'varchar(60)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'groupname',    'type' => 'varchar(60)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'hostname',     'type' => 'varchar(64)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'chkoutid',     'type' => 'varchar(20)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'restype',      'type' => 'int(10)',      'NULL' => false, 'default' => '0', 'unsigned' => true);
	$data['columns'][] = array('name' => 'status',       'type' => 'varchar(20)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'tokens_acquired', 'type' => 'int(10)',   'NULL' => false, 'default' => '0', 'unsigned' => true);
	$data['columns'][] = array('name' => 'tokens_acquired_date', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp',    'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present',      'type' => 'tinyint(3)',   'NULL' => false, 'default' => '1', 'unsigned' => true);
	$data['columns'][] = array('name' => 'lm_job_pid',   'type' => 'tinyint(10)',  'NULL' => true,  'default' => '0', 'unsigned' => true);
	$data['columns'][] = array('name' => 'clustername',  'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'jobid',        'type' => 'bigint(20)',   'NULL' => true,  'default' => '0');
	$data['columns'][] = array('name' => 'indexid',      'type' => 'int(10)',      'NULL' => true,  'default' => '0');
	$data['columns'][] = array('name' => 'projectName',  'type' => 'varchar(255)', 'NULL' => true,  'default' => '');
	$data['primary'] = 'service_id`,`vendor_daemon`,`feature_name`,`username`,`groupname`,`hostname`,`chkoutid`,`restype`,`status`,`tokens_acquired_date';
	$data['keys'][] = array('name' => 'idx_service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'idx_poller_id', 'columns' => 'poller_id');
	$data['keys'][] = array('name' => 'idx_vendor_daemon', 'columns' => 'vendor_daemon');
	$data['keys'][] = array('name' => 'idx_feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'idx_username', 'columns' => 'username');
	$data['keys'][] = array('name' => 'idx_hostname', 'columns' => 'hostname');
	$data['keys'][] = array('name' => 'idx_status', 'columns' => 'status');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_services_feature_details', $data);

	$data = array();
	$data['columns'][] = array('name' => 'poller_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'service_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_inuse_licenses', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_queued', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_reserved', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'vendor_daemon', 'type' => 'varchar(45)', 'NULL' => false, 'default' => 'TBD');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'vendor_status', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'vendor_version', 'type' => 'varchar(30)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'varchar(29)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'poller_id`,`feature_name`,`service_id';
	$data['keys'][] = array('name' => 'poller_id', 'columns' => 'poller_id');
	$data['keys'][] = array('name' => 'service_id', 'columns' => 'service_id');
	$data['keys'][] = array('name' => 'vendor_daemon', 'columns' => 'vendor_daemon');
	$data['keys'][] = array('name' => 'feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'feature_queued', 'columns' => 'feature_queued');
	$data['keys'][] = array('name' => 'feature_reserved', 'columns' => 'feature_reserved');
	$data['type'] = 'MEMORY';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_services_feature_use', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user_id', 'unsigned' => true, 'type' => 'smallint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(1024)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'user_id`,`name';
	$data['type'] = 'MyISAM';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_settings', $data);

	$data = array();
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(60)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'user';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_users_winsp', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id',            'type' => 'bigint(20)',  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'service_id',    'type' => 'int(10)',     'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name',  'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName',   'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'token_minutes', 'type' => 'int(10)',     'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'poll_time',     'type' => 'timestamp',   'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'idx_feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'idx_projectName', 'columns' => 'projectName');
	$data['keys'][] = array('name' => 'idx_service_id', 'columns' => 'service_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_daily_project_stats', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id',            'type' => 'bigint(20)',  'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'service_id',    'type' => 'int(10)',     'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'feature_name',  'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'projectName',   'type' => 'varchar(50)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'token_minutes', 'type' => 'int(10)',     'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'poll_time',     'type' => 'timestamp',   'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'feature_max_licenses', 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'idx_feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'idx_projectName', 'columns' => 'projectName');
	$data['keys'][] = array('name' => 'idx_service_id', 'columns' => 'service_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create ('license', 'lic_daily_project_stats_today', $data);

	db_execute('DROP VIEW IF EXISTS lic_flexlm_servers_feature_details');
	db_execute('DROP VIEW IF EXISTS lic_flexlm_servers_feature_use');
}

