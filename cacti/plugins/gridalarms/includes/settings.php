<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

function gridalarms_config_settings() {
	global $tabs, $settings, $grid_summary_data_retention;


	if (isset($settings['alerts'])) {
		$temp = $settings['alerts'];
	}

	$settings['alerts'] = array(
		'nl_header' => array(
			'friendly_name' => __('Alert Presets', 'gridalarms'),
			'method' => 'spacer',
		),
    	'gridalarm_severity' => array(
			'friendly_name' => __('Default Alert Severity', 'gridalarms'),
			'description' => __('Select the default Severity for Alerts.  This setting will apply for both the Alert and Syslogs forwarded by the Alert system.', 'gridalarms'),
			'method' => 'drop_array',
			'default' => LOG_WARNING,
			'array' => array(
				LOG_EMERG   => __('Emergency', 'gridalarms'),
				LOG_ALERT   => __('Alert', 'gridalarms'),
				LOG_CRIT    => __('Critical', 'gridalarms'),
				LOG_ERR     => __('Error', 'gridalarms'),
				LOG_WARNING => __('Warning', 'gridalarms'),
				LOG_NOTICE  => __('Notice', 'gridalarms'),
				LOG_INFO    => __('Info', 'gridalarms'),
				LOG_DEBUG   => __('Debug', 'gridalarms')
			)
		),
    	'gridalarm_facility' => array(
			'friendly_name' => __('Alert Syslog Facility', 'gridalarms'),
			'description' => __('Select the Syslog Facility for Alerts.  This setting will apply when Alerts are forwarded through Syslog.', 'gridalarms'),
			'method' => 'drop_array',
			'default' => LOG_DAEMON,
			'array' => array(
				LOG_AUTH     => 'Auth',
				LOG_AUTHPRIV => 'Auth Private',
				LOG_CRON     => 'Cron',
				LOG_DAEMON   => 'Daemon',
				LOG_KERN     => 'Kernel',
				LOG_LOCAL0   => 'Local 0',
				LOG_LOCAL1   => 'Local 1',
				LOG_LOCAL2   => 'Local 2',
				LOG_LOCAL3   => 'Local 3',
				LOG_LOCAL4   => 'Local 4',
				LOG_LOCAL5   => 'Local 5',
				LOG_LOCAL6   => 'Local 6',
				LOG_LOCAL7   => 'Local 7',
				LOG_LPR      => 'LPR',
				LOG_MAIL     => 'Mail',
				LOG_NEWS     => 'News',
				LOG_SYSLOG   => 'Syslog',
				LOG_USER     => 'User',
				LOG_UUCP     => 'UUCP'
			)
		),
    	'gridalarm_disable_legacy' => array(
			'friendly_name' => __('Disable Grid Alert Legacy Notifications', 'gridalarms'),
			'description' => __('Checking this box will disable Legacy Alerting on all Alerts.  Legacy Alerting is defined as any Specific Email Alerts not associated with a Notification List. The concept of notifying Cluster Administrators is preserved.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => ''
		),
    	'gridalarm_user_map' => array(
			'friendly_name' => __('User Notification Method', 'gridalarms'),
			'description' => __('Send user level reports using the following methodology.  However, if a user has elected to receive an Email Notification using bsub -u, that account will be used over the method below.', 'gridalarms'),
			'method' => 'drop_array',
			'array' => array(
				0 => __('Mail Exec User', 'gridalarms'),
				1 => __('Mail to Exec User@Cluster Domain', 'gridalarms'),
				2 => __('Mail to MetaData Address', 'gridalarms'),
			),
			'default' => '0'
		),
    	'gridalarm_metadata_user_email_map' => array(
			'friendly_name' => __('MetaData User Email Column', 'gridalarms'),
			'description' => __('Select the User MetaData Column to use for the Email address.  If the Email address is embedded into a sendto: href, it will be stripped.', 'gridalarms'),
			'method' => 'drop_sql',
			'sql' => 'SELECT DB_COLUMN_NAME AS id, DISPLAY_NAME as name FROM grid_metadata_conf WHERE OBJECT_TYPE="user" ORDER BY name',
			'none_value' => __('None', 'gridalarms'),
			'default' => '0'
		),
    	'gridalarms_alarm_log_cleanup' => array(
			'friendly_name' => __('Alert/Thold Log Data Retention Period', 'gridalarms'),
			'description' => __('How long alert/Thold log be stored in the system.', 'gridalarms'),
			'method' => 'drop_array',
			'default' => '3months',
			'array' => $grid_summary_data_retention,
		),
    	'gridalarm_alert_limit' => array(
			'friendly_name' => __('Alert Item Limit', 'gridalarms'),
			'description' => __('The maximum number of alert items to return using the Table and SQL Query methods.  The default is 100.', 'gridalarms'),
			'method' => 'drop_array',
			'default' => 100,
			'array' => array(
				20   => __('%d Rows', 20, 'gridalarms'),
				50   => __('%d Rows', 50, 'gridalarms'),
				100  => __('%d Rows', 100, 'gridalarms'),
				200  => __('%d Rows', 200, 'gridalarms'),
				300  => __('%d Rows', 300, 'gridalarms'),
				400  => __('%d Rows', 400, 'gridalarms'),
				500  => __('%d Rows', 500, 'gridalarms'),
				750  => __('%d Rows', 750, 'gridalarms'),
				1000 => __('%d Rows', 1000, 'gridalarms'),
				2000 => __('%d Rows', 2000, 'gridalarms')
			)
		),
		'gridalarm_thold_detail' => array(
			'friendly_name' => __('Show View Breached Items Icons', 'gridalarms'),
			'description' => __('Enable this option to always show the "View Breached Items" icon in the thresholds list. Note: When using this option, also ensure that the data input script value includes an optional last positional "detail" parameter that, when passed with a value of 1, will return the list of objects that are breached.', 'gridalarms'),
			'method' => 'checkbox',
			'default' => ''
		)
	);

	if (isset($temp)) {
		$settings['alerts'] = array_merge($temp, $settings['alerts']);

		$settings['alerts']['thold_disable_all']['friendly_name'] = __('Disable All Thresholds and Alerts', 'gridalarms');
		$settings['alerts']['thold_disable_all']['description'] = __('Checking this box will disable Alerting on all Thresholds and Grid Alerts.  This can be used when it is necessary to perform maintenance on your network and/or data centers.', 'gridalarms');
	}
}

function gridalarms_config_arrays() {
	global 	$messages, $user_auth_realms, $user_auth_realm_filenames, $grid_menu, $menu, $gridalarms_rows_selector,
			$metric_types, $cluster_tables, $host_tables, $queue_tables, $user_tables, $job_tables, $gridalarms_severities,
			$gridalarms_expression_item_types, $gridalarms_expression_brackets_operators, $gridalarms_expression_operators,
			$gridalarms_expression_comparison_operators, $gridalarms_expression_join_operators, $license_tables,
			$alarm_types, $gridalarms_types, $aggregation, $repeatarray, $alertarray, $timearray, $log_types, $log_types_display,
			$alarm_actions, $template_actions, $alarm_bgcolors, $alarm_log_bgcolors, $frequencies;
	global	$hash_type_codes, $thold_oob_templates;
	global 	$struct_gridalarms_template, $struct_gridalarms_template_contacts, $struct_gridalarms_template_expression,
			$struct_gridalarms_template_expression_input, $struct_gridalarms_template_expression_item, $struct_gridalarms_template_layout,
			$struct_gridalarms_template_metric, $struct_gridalarms_template_metric_expression;

	$grid_menu[__('Dashboards', 'gridalarms')]['plugins/gridalarms/grid_alarmdb.php?tab=alarms'] = __('Alerts', 'gridalarms');

	$menu2 = array();
	foreach ($menu as $temp => $temp2 ) {
		$menu2[$temp] = $temp2;

		if ($temp == 'Templates') {
			$menu2[__('Templates')]['plugins/gridalarms/gridalarms_templates.php'] = __('Alert', 'gridalarms');
		}

		if ($temp == __('Management')) {
			$menu2[__('Management')]['plugins/gridalarms/gridalarms_alarm.php?tab=alarms'] = __('Alerts', 'gridalarms');
		}
	}
	$menu = $menu2;

	$gridalarms_types = array(
		0 => __('Expression', 'gridlarms'),
		1 => __('SQL Query', 'gridlarms'),
		2 => __('Script', 'gridalarms')
	);

	$aggregation = array(
		0 => 'COUNT',
		1 => 'SUM',
		2 => 'AVG',
		3 => 'MIN',
		4 => 'MAX'
	);

	$alarm_types = array (
		0 => __('High/Low Values', 'gridlarms'),
		1 => __('Time Based', 'gridlarms'),
	);

	$log_types = array(
		1  => 'alarm',
		2  => 'retrigger',
		3  => 'trigger',
		0  => 'restoral',
		4  => 'restore',
		99 => 'acknowledgement'
	);

	$log_types_display = array(
		1  => __('Alert Notify', 'gridlarms'),
		2  => __('ReTrigger Notify', 'gridlarms'),
		0  => __('Restoral Notify', 'gridlarms'),
		4  => __('Restoral Event', 'gridlarms'),
		3  => __('Trigger Event', 'gridlarms'),
		99 => __('Acknowledgement', 'gridalarms')
	);

	$repeatarray = array(
		0    => __('Never', 'gridalarms'),
		1    => __('Every %d Minutes', 5, 'gridalarms'),
		2    => __('Every %d Minutes', 10, 'gridalarms'),
		3    => __('Every %d Minutes', 15, 'gridalarms'),
		4    => __('Every %d Minutes', 20, 'gridalarms'),
		6    => __('Every %d Minutes', 30, 'gridalarms'),
		8    => __('Every %d Minutes', 45, 'gridalarms'),
		12   => __('Every Hour', 'gridalarms'),
		24   => __('Every %d Hours', 2, 'gridalarms'),
		36   => __('Every %d Hours', 3, 'gridalarms'),
		48   => __('Every %d Hours', 4, 'gridalarms'),
		72   => __('Every %d Hours', 6, 'gridalarms'),
		96   => __('Every %d Hours', 8, 'gridalarms'),
		144  => __('Every %d Hours', 12, 'gridalarms'),
		288  => __('Every Day', 'gridalarms'),
		576  => __('Every %d Days', 2, 'gridalarms'),
		2016 => __('Every Week', 'gridalarms'),
		4032 => __('Every %d Weeks', 2, 'gridalarms'),
		8640 => __('Every Month', 'gridalarms')
	);

	$frequencies  = array(
		1   => __('Every %d Minutes', 5, 'gridalarms'),
		2   => __('Every %d Minutes', 10, 'gridalarms'),
		3   => __('Every %d Minutes', 15,'gridalarms'),
		4   => __('Every %d Minutes', 20, 'gridalarms'),
		6   => __('Every %d Minutes', 30, 'gridalarms'),
		8   => __('Every %d Minutes', 45, 'gridalarms'),
		12  => __('Every Hour', 'gridalarms'),
		24  => __('Every %d Hours', 2, 'gridalarms'),
		36  => __('Every %d Hours', 3, 'gridalarms'),
		48  => __('Every %d Hours', 4, 'gridalarms'),
		72  => __('Every %d Hours', 6, 'gridalarms'),
		96  => __('Every %d Hours', 8, 'gridalarms'),
		144 => __('Every %d Hours', 12, 'gridalarms'),
		288 => __('Every Day', 'gridalarms')
	);

	$alertarray = $timearray = array(
		1    => __('%d Minutes', 5,  'gridalarms'),
		2    => __('%d Minutes', 10, 'gridalarms'),
		3    => __('%d Minutes', 15, 'gridalarms'),
		4    => __('%d Minutes', 20, 'gridalarms'),
		6    => __('%d Minutes', 30, 'gridalarms'),
		8    => __('%d Minutes', 45, 'gridalarms'),
		12   => __('%d Hour', 1, 'gridalarms'),
		24   => __('%d Hours', 2, 'gridalarms'),
		36   => __('%d Hours', 3, 'gridalarms'),
		48   => __('%d Hours', 4, 'gridalarms'),
		72   => __('%d Hours', 6, 'gridalarms'),
		96   => __('%d Hours', 8, 'gridalarms'),
		144  => __('%d Hours', 12, 'gridalarms'),
		288  => __('%d Day', 1, 'gridalarms'),
		576  => __('%d Days', 2, 'gridalarms'),
		2016 => __('%d Week', 1, 'gridalarms'),
		4032 => __('%d Weeks', 2, 'gridalarms'),
		8640 => __('%d Month', 1, 'gridalarms')
	);

	$gridalarms_severities = array(
		LOG_EMERG   => __('Emergency', 'gridalarms'),
		LOG_ALERT   => __('Alert', 'gridalarms'),
		LOG_CRIT    => __('Critical', 'gridalarms'),
		LOG_ERR     => __('Error', 'gridalarms'),
		LOG_WARNING => __('Warning', 'gridalarms'),
		LOG_NOTICE  => __('Notice', 'gridalarms'),
		LOG_INFO    => __('Info', 'gridalarms'),
		LOG_DEBUG   => __('Debug', 'gridalarms')
	);

	/* switching to bitwise status for details logging */
	$metric_types = array(
		1  => __('Job', 'gridalarms'),
		2  => __('Host/Groups', 'gridalarms'),
		4  => __('Queue', 'gridalarms'),
		8  => __('User/Groups', 'gridalarms'),
		16 => __('Cluster', 'gridalarms'),
		32 => __('License', 'gridalarms'),
		64 => __('Custom', 'gridalarms')
	);

	$license_tables = array(
		'lic_services_feature_details'	=> 'lic_services_feature_details',
		'lic_services_feature'			=> 'lic_services_feature',
		'lic_services_feature_use'		=> 'lic_services_feature_use',
		'lic_interval_stats'			=> 'lic_interval_stats',
		'lic_services'					=> 'lic_services',
		'lic_daily_stats_traffic'		=> 'lic_daily_stats_traffic',
		'lic_daily_stats'				=> 'lic_daily_stats'
	);

	$cluster_tables = array(
		'grid_clusters' => 'grid_clusters'
	);

	$host_tables = array (
		'grid_hosts'            => 'grid_hosts',
		'grid_hostgroups_stats' => 'grid_hostgroups_stats',
		'grid_hostinfo'         => 'grid_hostinfo',
		'grid_hosts_resources'  => 'grid_hosts_resources',
		'grid_load'             => 'grid_load',
		'grid_summary'          => 'grid_summary'
	);

	$user_tables = array (
		'grid_user_group_stats'          => 'grid_user_group_stats',
		'grid_users_or_groups'           => 'grid_users_or_groups',
		'grid_job_daily_user_stats'      => 'grid_job_daily_user_stats',
		'grid_job_daily_usergroup_stats' => 'grid_job_daily_usergroup_stats'
	);

	$queue_tables = array (
		'grid_queues'             => 'grid_queues',
		'grid_queues_shares'      => 'grid_queues_shares',
		'grid_queues_users_stats' => 'grid_queues_users_stats'
	);

	$job_tables = array(
		'grid_jobs'             => 'grid_jobs',
		'grid_arrays'           => 'grid_arrays',
		'grid_jobs_idled'       => 'grid_jobs_idled',
		'grid_jobs_memvio'      => 'grid_jobs_memvio',
		'grid_jobs_rusage'      => 'grid_jobs_rusage',
		'grid_jobs_jobhosts'    => 'grid_jobs_jobhosts',
		'grid_jobs_reqhosts'    => 'grid_jobs_reqhosts',
		'grid_job_daily_stats'  => 'grid_job_daily_stats',
		'grid_jobs_pendreasons' => 'grid_jobs_pendreasons',
		'grid_applications'     => 'grid_applications',
		'grid_groups'           => 'grid_groups',
		'grid_license_projects' => 'grid_license_projects',
		'grid_projects'         => 'grid_projects'
	);

	$gridalarms_expression_item_types = array(
		0 => __('Operator', 'gridalarms'),
		1 => __('Comparison Operator', 'gridalarms'),
		2 => __('Join Operators', 'gridalarms'),
		3 => __('Metric', 'gridalarms'),
		4 => __('Numeric/List Value', 'gridalarms'),
		5 => __('String Value', 'gridalarms'),
		6 => __('Brackets', 'gridalarms'),
		7 => __('Custom Data Input Items', 'gridalarms')
	);

	$gridalarms_expression_brackets_operators = array(
		'(' => '(',
		')'	=> ')'
	);

	$gridalarms_expression_operators = array(
		'+'	=> '+',
		'-'	=> '-',
		'*'	=> '*',
		'/'	=> '/'
	);

	$gridalarms_expression_comparison_operators = array(
		'>'        => '>',
		'<'        => '<',
		'='        => '=',
		'!='       => '!=',
		'>='       => '>=',
		'<='       => '<=',
		'LIKE'     => 'LIKE',
		'NOT LIKE' => 'NOT LIKE',
		'IN'       => 'IN',
		'NOT IN'   => 'NOT IN'
	);

	$gridalarms_expression_join_operators = array(
		'AND' 	=> 'AND',
		'OR'	=>	'OR'
	);

	$alarm_bgcolors = array(
		'red'    => 'FF6666',
		'yellow' => 'FAFD9E',
		'orange' => 'FF7D00',
		'green'  => 'CCFFCC',
		'grey'   => 'CDCFC4'
	);

	$alarm_log_bgcolors = array(
		'alarm'     => 'F21924',
		'warning'   => 'FB4A14',
		'retrigger' => 'FF7A30',
		'trigger'   => 'FAFD9E',
		'restoral'  => 'CCFFCC',
		'restore'   => 'CDCFC4',
		'acknowledgement' => '99CC66'
	);

 //  Because cacti1.x defined these values, to avoid duplicate, we have to move them to gridalarms_functions.php, narrows the scope to gridalarm only.
//	$hash_type_codes['gridalarms_template']                   = '18';
//	$hash_type_codes['gridalarms_template_contacts']          = '19';
//	$hash_type_codes['gridalarms_template_layout']            = '20';
//	$hash_type_codes['gridalarms_template_expression']        = '21';
//	$hash_type_codes['gridalarms_template_expression_item']   = '22';
//	$hash_type_codes['gridalarms_template_metric']            = '23';
//	$hash_type_codes['gridalarms_template_expression_input']  = '24';
//	$hash_type_codes['gridalarms_template_metric_expression'] = '25';

	$struct_gridalarms_template = array(
		'id',
		'name',
		'clusterid',
		'type',
		'expression_id',
		'aggregation',
		'metric',
		'base_time_display',
		'base_time',
		'frequency',
		'alarm_type',
		'alarm_hi',
		'alarm_low',
		'alarm_fail_trigger',
		'time_hi',
		'time_low',
		'time_fail_trigger',
		'time_fail_length',
		'warning_pct',
		'trigger_cmd_high',
		'trigger_cmd_low',
		'trigger_cmd_norm',
		'cmd_retrigger_enabled',
		'repeat_alert',
		'notify_extra',
		'notify_cluster_admin',
		'notify_alert',
		'notify_users',
		'syslog_priority',
		'syslog_facility',
		'syslog_enabled',
		'tcheck',
		'exempt',
		'acknowledgement',
		'restored_alert',
		'req_ack',
		'email_body',
		'email_subject'
	);

	$struct_gridalarms_template_contacts = array(
		'alarm_id',
		'contact_id'
	);

	$struct_gridalarms_template_expression = array(
		'id',
		'name',
		'description',
		'ds_type',
		'type',
		'db_table',
		'sql_query',
		'script_thold',
		'script_data',
		'script_data_type'
	);

	$struct_gridalarms_template_expression_input = array(
		'id',
		'expression_id',
		'name',
		'description',
		'value'
	);

	$struct_gridalarms_template_expression_item = array(
		'id',
		'expression_id',
		'sequence',
		'type',
		'value'
	);

	$struct_gridalarms_template_layout = array(
		'id',
		'alarm_id',
		'display_name',
		'column_name',
		'sequence',
		'sortposition',
		'sortdirection'
	);

	$struct_gridalarms_template_metric = array(
		'id',
		'name',
		'description',
		'type',
		'db_table',
		'db_column'
	);

	$struct_gridalarms_template_metric_expression = array(
		'expression_id',
		'metric_id'
	);

	$messages['gridalarms_metric_in_use'] = array(
		'message' => __esc('One or more of the Metrics you selected are in use by Data Sources and cannot be deleted.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_input_in_use'] = array(
		'message' => __esc('One or more of the Custom Data Input Items you selected are in use by Data Sources and cannot be deleted.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_metric_save_successful'] = array(
		'message' => __esc('Metric Saved Successfully.', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_metric_save_failed'] = array(
		'message' => __esc('Failed to save the metric.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_metric_name_exists'] = array(
		'message' => __esc('Failed to save the metric. A metric with this name already exists.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_expression_in_use'] = array(
		'message' => __esc('One or more of the Data Source you selected are in use by Alerts and cannot be deleted.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_expression_name_exists'] = array(
		'message' => __esc('Failed to save the Data Source. A Data Source with this name already exists.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_expression_empty_metric'] = array(
		'message' => __esc('No metric selected.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_alarm_save'] = array(
		'message' => __esc('Record Updated, Data Source Syntax is Valid', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_save_nods'] = array(
		'message' => __esc('Record Updated, But Disabled Due to Lack of Data Source', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_save_disable'] = array(
		'message' => __esc('Record Updated, but disabled as the Data Source syntax is not valid', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_hilo'] = array(
		'message' => __esc('You must specify either \'High Value\' or \'Low Value\' or both!<br>RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_alarm_hilo_imp'] = array(
		'message' => __esc('\'High Value\' smaller than or equal to \'Low Value\' RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_alarm_no_alarm'] = array(
		'message' => __esc('Alarm has not been created yet. RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_alarm_hilo_numeric'] = array(
		'message' => __esc('Your High Value or Low Value entries contain non-numeric value. RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$messages['alarm_acknowledged'] = array(
		'message' => __esc('Alerts Acknowledged Sucessfully', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_save_new'] = array(
		'message' => __esc('New Alert is successfully created from the Template', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_save_totemp'] = array(
		'message' => __esc('Alert Updated, template propogation is enabled', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_alarm_save_to_instance'] = array(
		'message' => __esc('Alert Updated, template propogation is disabled', 'gridalarms'),
		'type' => 'info'
	);

	$messages['gridalarms_import_hash_type_error'] = array(
		'message' => __esc('XML hash type is not gridalarms_template.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_import_invalid_hash'] = array(
		'message' => __esc('XML hash code is not valid', 'grialarms'),
		'type' => 'error'
	);

	$messages['gridalarms_import_failure'] = array(
		'message' => __esc('Failed to import the <template name> Alert template', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_progation_failure'] = array(
		'message' => __esc('Alert Template propagation failed.', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_invalid_timestamp'] = array(
		'message' => __esc('Invalid Alert Check Time input. RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$messages['gridalarms_title_replacement_failure'] = array(
		'message' => __esc('Alert name replacement failed due to non-existing custom data input. RECORD NOT UPDATED!', 'gridalarms'),
		'type' => 'error'
	);

	$thold_oob_templates = array(
		'67b772b2d1defae33f8692baa62a6f19' => 'Alert - Host With /tmp Exceed % Capacity [host_tmp_pct]',
		'e49fd2725f7dfbe45b735909b52e1d92' => 'Alert - Host With /var/tmp Exceed % Capacity [host_vartmp_pct]',
		'e58d2e27dad0c478728ca9e169116ad6' => 'Alert - Hostgroup With Low Number of Free Slots [queue_free_slots]',
		'cc1d6b0b5f819e3bb0b1b376ecb66eec' => 'Alert - Hosts With Effective r15m &gt; X [host_eff_r15m]',
		'a7f8d97d39528145a583b6a6bce5e74c' => 'Alert - Hosts With Used Mem &gt; % [host_mem_pct]',
		'c0b234c49ce038e15746a94cc773f4f2' => 'Alert - Hosts With Used Swp &gt; % [host_swp_pct]',
		'00e0a29c573070c49315c8380e076a54' => 'Alert - Hosts With X Status [host_status]',
		'34da44a9980034334421e03610626d0a' => 'Alert - Idle Jobs [host_idle_jobs]',
		'7cfb2f60b2f9616a34cf62e396c97566' => 'Alert - Jobs Pending for X seconds [pend_jobs]'
	);
}

