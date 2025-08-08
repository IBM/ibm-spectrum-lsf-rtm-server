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

function gridalarms_setup_new_tables() {
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'template_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_enabled', 'type' => 'char(3)', 'NULL' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'expression_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'aggregation', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'metric', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'base_time_display', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '12:00am');
	$data['columns'][] = array('name' => 'base_time', 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'frequency', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'last_runtime', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_duration', 'type' => 'float', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_type', 'type' => 'int(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alarm_hi', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_low', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_fail_trigger', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_fail_count', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alarm_alert', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alarm_enabled', 'type' => "enum('on','off')", 'NULL' => false, 'default' => 'on');
	$data['columns'][] = array('name' => 'time_hi', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'time_low', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'time_fail_trigger', 'type' => 'int(12)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'time_fail_length', 'type' => 'int(12)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'warning_pct', 'type' => 'varchar(5)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_high', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_low', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_norm', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'cmd_retrigger_enabled', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'lastread', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'oldvalue', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'repeat_alert', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_extra', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_cluster_admin', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'notify_alert', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_users', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'syslog_priority', 'type' => 'int(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'syslog_facility', 'type' => 'int(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'syslog_enabled', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tcheck', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'exempt', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'acknowledgement', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'restored_alert', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'req_ack', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'email_body', 'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'email_subject', 'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'format_file', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes', 'type' => 'text', 'NULL' => true);
	$data['columns'][] = array('name' => 'external_id', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'alarm_enabled', 'columns' => 'alarm_enabled');
	$data['keys'][] = array('name' => 'clusterid', 'columns' => 'clusterid');
	$data['keys'][] = array('name' => 'expression_id', 'columns' => 'expression_id');
	$data['keys'][] = array('name' => 'notify_alert', 'columns' => 'notify_alert');
	$data['keys'][] = array('name' => 'tcheck', 'columns' => 'tcheck');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alerts and status similar to Thold';
	api_plugin_db_table_create('gridalarms', 'gridalarms_alarm', $data);

	$data = array();
	$data['columns'][] = array('name' => 'alarm_id', 'type' => 'int(12)', 'NULL' => false);
	$data['columns'][] = array('name' => 'contact_id', 'type' => 'int(12)', 'NULL' => false);
	$data['keys'][] = array('name' => 'alarm_id', 'columns' => 'alarm_id');
	$data['keys'][] = array('name' => 'contact_id', 'columns' => 'contact_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert contacts similar to Thold';
	api_plugin_db_table_create('gridalarms', 'gridalarms_alarm_contacts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'display_name', 'type' => 'varchar(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'column_name', 'type' => 'varchar(40)', 'NULL' => false);
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'sortposition', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sortdirection', 'unsigned' => true, 'type' => 'int(1)', 'NULL' => true);
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'units', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'align', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'digits', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '-1');
	$data['columns'][] = array('name' => 'autoscale', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Report Layout Data for Alert Tablular Reports';
	api_plugin_db_table_create('gridalarms', 'gridalarms_alarm_layout', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'time', 'type' => 'int(24)', 'NULL' => false);
	$data['columns'][] = array('name' => 'alarm_id', 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'alarm_value', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'current', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'int(5)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'int(5)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'details', 'type' => 'blob', 'NULL' => true);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'time', 'columns' => 'time');
	$data['keys'][] = array('name' => 'alarm_id', 'columns' => 'alarm_id');
	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Logs';
	api_plugin_db_table_create('gridalarms', 'gridalarms_alarm_log', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false);
	$data['columns'][] = array('name' => 'jobid', 'unsigned' => true, 'type' => 'bigint(20)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'indexid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'submit_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'queue', 'type' => 'varchar(40)', 'NULL' => false);
	$data['columns'][] = array('name' => 'host', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'feature_name', 'type' => 'varchar(50)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'user', 'type' => 'varchar(64)', 'NULL' => false);
	$data['columns'][] = array('name' => 'column01', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column02', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column03', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column04', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column05', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column06', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column07', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column08', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column09', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column10', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column11', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column12', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column13', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column14', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'column15', 'type' => 'varchar(64)', 'NULL' => true);
	$data['columns'][] = array('name' => 'first_seen', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'last_reported', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '1');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
	$data['keys'][] = array('name' => 'cjis_idx', 'columns' => 'clusterid`,`jobid`,`indexid`,`submit_time');
	$data['keys'][] = array('name' => 'queue', 'columns' => 'queue');
	$data['keys'][] = array('name' => 'host', 'columns' => 'host');
	$data['keys'][] = array('name' => 'feature_name', 'columns' => 'feature_name');
	$data['keys'][] = array('name' => 'user', 'columns' => 'user');
	$data['keys'][] = array('name' => 'last_updated', 'columns' => 'last_updated');
	$data['keys'][] = array('name' => 'last_reported', 'columns' => 'last_reported');
	$data['keys'][] = array('name' => 'alarm_id_present', 'columns' => 'alarm_id`,`present');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores an Archive of Alert Tabular Details';
	api_plugin_db_table_create('gridalarms', 'gridalarms_alarm_log_items', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ds_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_table', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'sql_query', 'type' => 'varchar(1024)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_thold', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_data', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_data_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['primary'] = 'id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Expression Details';
	api_plugin_db_table_create('gridalarms', 'gridalarms_expression', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(128)', 'NULL' => true);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(128)', 'NULL' => true);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'exp_id_name', 'columns' => 'expression_id`,`name');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Custom Input Variables and Defaults for Expressions';
	api_plugin_db_table_create('gridalarms', 'gridalarms_expression_input', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'template_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(255)', 'NULL' => false);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'expression_id', 'columns' => 'expression_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create('gridalarms', 'gridalarms_expression_item', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_table', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_column', 'type' => 'varchar(50)', 'NULL' => false);
	$data['primary'] = 'name';
	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
	$data['row_format'] = 'DYNAMIC';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Metrics to be watched for Legacy Expressions';
	api_plugin_db_table_create('gridalarms', 'gridalarms_metric', $data);

	$data = array();
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'metric_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'expression_id`,`metric_id';
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Table Linking Metrics to Expressions';
	api_plugin_db_table_create('gridalarms', 'gridalarms_metric_expression', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'clusterid', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'expression_id', 'type' => 'int(11)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'aggregation', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'metric', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'base_time_display', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '12:00am');
	$data['columns'][] = array('name' => 'base_time', 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'frequency', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'alarm_type', 'type' => 'int(3)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alarm_hi', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_low', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_fail_trigger', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'time_hi', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'time_low', 'type' => 'varchar(100)', 'NULL' => false);
	$data['columns'][] = array('name' => 'time_fail_trigger', 'type' => 'int(12)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'time_fail_length', 'type' => 'int(12)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'warning_pct', 'type' => 'varchar(5)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_high', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_low', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'trigger_cmd_norm', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'cmd_retrigger_enabled', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'repeat_alert', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_extra', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_cluster_admin', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'notify_alert', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'notify_users', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'syslog_priority', 'type' => 'int(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'syslog_facility', 'type' => 'int(2)', 'NULL' => true);
	$data['columns'][] = array('name' => 'syslog_enabled', 'type' => 'char(3)', 'NULL' => false);
	$data['columns'][] = array('name' => 'tcheck', 'type' => 'int(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'exempt', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'acknowledgement', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'restored_alert', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'req_ack', 'type' => 'char(3)', 'NULL' => false, 'default' => 'off');
	$data['columns'][] = array('name' => 'email_body', 'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'email_subject', 'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'format_file', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'notes', 'type' => 'text', 'NULL' => true);
	$data['columns'][] = array('name' => 'external_id', 'type' => 'varchar(40)', 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['keys'][] = array('name' => 'notify_alert', 'columns' => 'notify_alert');
	$data['keys'][] = array('name' => 'expression_id', 'columns' => 'expression_id');
	$data['keys'][] = array('name' => 'tcheck', 'columns' => 'tcheck');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Template definitions similar to Thold';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template', $data);

	$data = array();
	$data['columns'][] = array('name' => 'alarm_id', 'type' => 'int(12)', 'NULL' => false);
	$data['columns'][] = array('name' => 'contact_id', 'type' => 'int(12)', 'NULL' => false);
	$data['keys'][] = array('name' => 'alarm_id', 'columns' => 'alarm_id');
	$data['keys'][] = array('name' => 'contact_id', 'columns' => 'contact_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Template Contacts similar to Thold';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_contacts', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'ds_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_table', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'sql_query', 'type' => 'varchar(1024)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_thold', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_data', 'type' => 'varchar(255)', 'NULL' => true);
	$data['columns'][] = array('name' => 'script_data_type', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Template Expressions';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_expression', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(20)', 'NULL' => true);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(128)', 'NULL' => true);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(128)', 'NULL' => true);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'exp_id_name', 'columns' => 'expression_id`,`name');
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['type'] = 'InnoDB';
	$data['comment'] = 'Stores Alert Template Custom Data Inputs and Defaults';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_expression_input', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(255)', 'NULL' => false);
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['keys'][] = array('name' => 'expression_id', 'columns' => 'expression_id');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_expression_item', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'alarm_id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'display_name', 'type' => 'varchar(20)', 'NULL' => false);
	$data['columns'][] = array('name' => 'column_name', 'type' => 'varchar(40)', 'NULL' => false);
	$data['columns'][] = array('name' => 'sequence', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '1');
	$data['columns'][] = array('name' => 'sortposition', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sortdirection', 'unsigned' => true, 'type' => 'int(1)', 'NULL' => true);
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'units', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'align', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'digits', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '-1');
	$data['columns'][] = array('name' => 'autoscale', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'id';
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_layout', $data);

	$data = array();
	$data['columns'][] = array('name' => 'id', 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'int(1)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_table', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'db_column', 'type' => 'varchar(50)', 'NULL' => false);
	$data['primary'] = 'name';
	$data['keys'][] = array('name' => 'hash', 'columns' => 'hash');
	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
	$data['row_format'] = 'DYNAMIC';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_metric', $data);

	$data = array();
	$data['columns'][] = array('name' => 'expression_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'metric_id', 'unsigned' => true, 'type' => 'mediumint(8)', 'NULL' => false, 'default' => '0');
	$data['primary'] = 'expression_id`,`metric_id';
	$data['type'] = 'InnoDB';
	$data['comment'] = '';
	api_plugin_db_table_create('gridalarms', 'gridalarms_template_metric_expression', $data);

	//This need to goto new gridalarms tables
	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'gridadmin_action_level',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'trigger_cmd_norm'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'gridadmin_action_level'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_low',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_high'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_action_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_low'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_target_high',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_action_high'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_signal_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_target_high'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_action_low',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_signal_high'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_target_low',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_action_low'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'job_signal_low',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_target_low'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_high_lockid',
		'type'=> 'varchar(128)',
		'NULL' => false,
		'default' => '',
		'after' => 'notes'));

	api_plugin_db_add_column('gridalarms', 'thold_data', array(
		'name' => 'host_action_low_lockid',
		'type'=> 'varchar(128)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_high_lockid'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'gridadmin_action_level',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'trigger_cmd_norm'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'host_action_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'gridadmin_action_level'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'host_action_low',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_high'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_action_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'host_action_low'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_target_high',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_action_high'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_signal_high',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_target_high'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_action_low',
		'type'=> 'char(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_signal_high'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_target_low',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_action_low'));

	api_plugin_db_add_column('gridalarms', 'thold_template', array(
		'name' => 'job_signal_low',
		'type'=> 'varchar(100)',
		'NULL' => false,
		'default' => '',
		'after' => 'job_target_low'));
}
