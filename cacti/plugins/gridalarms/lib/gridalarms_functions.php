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

global $config;

include_once($config['library_path'] . '/rtm_functions.php');
include_once(dirname(__FILE__) . '/../setup.php');

/**
 * takes a string of text, and performs variable replacement on it
 *
 * @param string  $script - the string to evaluate
 * @param array   $alarm - the array containing Alert or template information
 *
 * @return string the modified script
 */
function do_script_variable_replacement(string $script, array $alarm) : string {
	$result_script = $script;

	if ($alarm['clusterid'] > 0) {
		$result_script = str_replace('|alert_clusterid|', $alarm['clusterid'], $result_script);
		$result_script = str_replace('|alert_clustername|', "'" . get_clustername($alarm['clusterid']) . "'", $result_script);
	} else {
		$result_script = str_replace('|alert_clusterid|', "''", $result_script);
		$result_script = str_replace('|alert_clustername|', "''", $result_script);
	}

	$path_webroot  = db_fetch_cell('SELECT value
		FROM settings
		WHERE name="path_webroot"');

	$result_script = str_replace('|path_cacti|', $path_webroot, $result_script);

	return $result_script;
}

/**
 * purges old entries from the alert log
 *
 * @return null
 */
function gridalarms_alarm_log_cleanup() : void {
	/* keep days of logs based on the setting of gridalarms_alarm_log_cleanup in console->setting->alerting/thold*/

	$alarm_log_retention = read_config_option('gridalarms_alarm_log_cleanup');

	if (strlen($alarm_log_retention)) {
		$alarm_log_retention_date = date('Y-m-d H:i:s', strtotime('-$alarm_log_retention'));
	} else {
		$alarm_log_retention_date = date('Y-m-d H:i:s', strtotime('-3 Months'));
	}

	db_execute_prepared('DELETE FROM gridalarms_alarm_log
		WHERE time < UNIX_TIMESTAMP(?)',
		array($alarm_log_retention_date));
}

/**
 * returns columns available for an alert expression
 *
 * @param int - $expression_id - the id of the expression to query
 *
 * @return array - an array of column names
 */
function gridalarms_get_sql_columns(int $expression_id) : array {
	$sql = db_fetch_cell_prepared('SELECT sql_query
		FROM gridalarms_expression
		WHERE id = ?',
		array($expression_id));

	if (strlen($sql)) {
		$records = db_fetch_row($sql . ' LIMIT 1');
	}

	if (cacti_sizeof($records)) {
		return array_keys($records);
	} else {
		return array();
	}
}

/**
 * returns columns available for an template expression
 *
 * @param int $expression_id - the id of the expression to query
 *
 * @return array - an array of column names
 */
function gridalarms_get_template_sql_columns(int $expression_id) : array {
	$sql = db_fetch_cell_prepared('SELECT sql_query
		FROM gridalarms_template_expression
		WHERE id = ?',
		array($expression_id));

	if (strlen($sql)) {
		$records = db_fetch_row($sql . ' LIMIT 1');
	}

	if (cacti_sizeof($records)) {
		return array_keys($records);
	} else {
		return array();
	}
}

/**
 * logs an entry to the local systems syslog facility
 *
 * @param $syslog_level		- the severity/priority of the syslog message
 * @param $syslog_facility	- the facility to log the event to
 * @param $name				- the name of the event
 * @param $breach_up		- is the event for a hi or low condition
 * @param $threshld			- the event threshold level
 * @param $currentval		- the currently measured value
 * @param $trigger			- unused, the threshold trigger count
 * @param $triggerct		- unused, the current trigger count
 *
 * @return null
 */
function rtm_logger(int $syslog_level, int $syslog_facility, string $name, int $breach_up, mixed $threshld, mixed $currentval, int $trigger, int $triggerct) : void {
	if (function_exists('define_syslog_variables')) define_syslog_variables();

	if (!isset($syslog_level)) {
		$syslog_level = LOG_WARNING;
	} elseif (isset($syslog_level) && ($syslog_level > 7 || $syslog_level < 0)) {
		$syslog_level = LOG_WARNING;
	}

	if (!isset($syslog_facility)) {
		$syslog_facility = LOG_DAEMON;
	}

	openlog('RTM-GridAlert-Log', LOG_PID | LOG_PERROR, $syslog_facility);

	if (strval($breach_up) == 'ok') {
		syslog($syslog_level, $name . ' Restored to Normal with Current Value ' . $currentval);
	} else {
		syslog($syslog_level, $name . ' Went ' . ($breach_up ? 'Above' : 'Below') . ' Threshold of ' . $threshld . ' with Current Value ' . $currentval);
	}

	closelog();
}

/**
 * sends an alert event to the Email recipients
 *
 * @param $to          - the list of addresses the Email will go to
 * @param $from        - the senders Email address
 * @param $subject     - the Email subject
 * @param $message     - the Email body
 * @param $format_file - a boolean defining if simple text should be forced
 * @param $textonly    - display mail as text (not used)
 *
 * @return null|string null upon success, or error on failure
 */
function alarm_mail(mixed $to, mixed $from, string $subject, string $message, string $format_file = '', bool $textonly = false) : ?string {
	global $config;

	include_once('./lib/reports.php');

	$subject = trim($subject);
	$message = str_replace('<SUBJECT>', $subject, $message);

	if ($from == '') {
		$from = rtm_get_defalt_mail_addr();
	}

	$from_name = rtm_get_defalt_mail_alias();

	if ($to == '') {
		return __('Mailer Error: No <b>TO</b> address set!!<br>If using the <i>Test Mail</i> link, please set the <b>Alert Email</b> setting.', 'thold');
	}

	$html = $message;

	if ($format_file != '') {
		$format_data = '';
		$report_tag  = false;
		$format_ok   = false;
		$theme       = 'classic';

		$format_ok = reports_load_format_file($format_file, $format_data, $report_tag, $theme);

		if ($format_ok) {
			if ($report_tag) {
				$html = str_replace('<REPORT>', $message, $format_data);
			} else {
				$html = $format_data . PHP_EOL . $message;
			}
		} else {
			$html = $outstr;
		}
	} elseif (file_exists($config['base_path'] . '/plugins/gridalarms/formats/alert.format')) {
		$format = file_get_contents($config['base_path'] . '/plugins/gridalarms/formats/alert.format');

		$html = str_replace('<REPORT>', $message, $format);
		$html = str_ireplace('bgcolor="#00438C"', '', $html);
		$html = str_ireplace('bgcolor="#E1E1E1"', '', $html);
		$html = str_ireplace("bgcolor='#6d88ad'", '', $html);
	}

	$text = array('text' => '', 'html' => '');

	if ($textonly) {
		$text['html'] = $message . '<br>';

		$message = str_replace('<br>',  "\n", $message);
		$message = str_replace('<BR>',  "\n", $message);
		$message = str_replace('</BR>', "\n", $message);
		$text['text'] = strip_tags(str_replace('<br>', "\n", $message));
	} else {
		$text['html'] = $html . '<br>';
		$text['text'] = strip_tags(str_replace('<br>', "\n", $message));
	}

	$version = get_gridalarms_version();

	$headers = array();
	$headers['X-Mailer']   = 'gridalarms-v' . $version;
	$headers['User-Agent'] = 'gridalarms-v' . $version;

	$error = mailer(
		array($from, $from_name),
		$to,
		'',
		'',
		'',
		$subject,
		$text['html'],
		$text['text'],
		'',
		$headers,
		!$textonly
    );

	if (strlen($error)) {
		cacti_log('ERROR: Sending Email Failed.  Error was ' . $error, true, 'GRIDALERTS');

		return $error;
	}

	return '';
}

/**
 * returns columns available for an template expression
 *
 * @param $expression_id	- the id of the expression to query
 *
 * @return - an array of column names
 */
function plugin_alarm_duration_convert(string $data, string $type) : string{
	global $repeatarray, $alertarray, $timearray;

	/* handle a null data value */
	if ($data == '') {
		return '';
	}

	switch ($type) {
		case 'repeat':
			return (isset($repeatarray[$data]) ? $repeatarray[$data] : $data);
			break;
		case 'alert':
			return (isset($alertarray[$data]) ? $alertarray[$data] : $data);
			break;
		case 'time':
			return (isset($timearray[$data]) ? $timearray[$data] : $data);
			break;
	}

	return $data;
}

/**
 * return a comma delimited list of admin Email addresses for the cluster
 *
 * @param $clusterid		- the id of the cluster in question
 *
 * @return - a string of the cluster admins emails
 */
function get_cluster_admins(int $clusterid) : string {
	if ($clusterid > 0) {
		$rows = db_fetch_assoc_prepared('SELECT email_admin
			FROM grid_clusters
			WHERE clusterid = ?',
			array($clusterid));
	} else {
		$rows = db_fetch_assoc('SELECT DISTINCT email_admin
			FROM grid_clusters
			ORDER BY email_admin');
	}

	$all_cluster_emails = array();

	if (cacti_sizeof($rows)) {
		$one_cluster_emails = array();
		foreach ($rows as $row) {
			$one_cluster_emails = explode(',', $row['email_admin']);
			foreach ($one_cluster_emails as $e) {
				if (strlen(trim($e)) > 0) {
					$all_cluster_emails[$e] = $e;
				}
			}
		}
	}

	if (cacti_sizeof($all_cluster_emails) > 0) {
		return implode(',', $all_cluster_emails);
	} else {
		return "";
	}

}

/**
 * return a comma delimited list of Email addresses for an alarm
 *
 * @param $alarm			- the alarm to be used to construct the Email list
 *
 * @return - a string of the cluster admins emails
 */
function gridalarms_build_email_list(array $alarm) : string {
	$alarm_emails = array();

	if (read_config_option('gridalarm_disable_legacy') != 'on') {
		$rows = db_fetch_assoc_prepared('SELECT ptc.data
			FROM plugin_thold_contacts AS ptc
			INNER JOIN gridalarms_alarm_contacts AS gac
			ON ptc.id=gac.contact_id
			AND ptc.data != ""
			AND gac.alarm_id = ?',
			array($alarm['id']));

		if (cacti_sizeof($rows)) {
			foreach ($rows as $row) {
				$e = trim($row['data']);
				$alarm_emails[$e] = $e;
			}
		}

		if (isset($alarm['notify_extra']) && $alarm['notify_extra'] != '') {
			$emails = explode(',', $alarm['notify_extra']);

			foreach ($emails as $e) {
				$e = trim($e);
				$alarm_emails[$e] = $e;
			}
		}
	}

	if ($alarm['notify_cluster_admin'] == 1) {
		$emails = explode(',', get_cluster_admins($alarm['clusterid']));

		if (cacti_sizeof($emails) > 0) {
			foreach ($emails as $e) {
				$e = trim($e);

				$alarm_emails[$e] = $e;
			}
		}
	}

	if ($alarm['notify_alert'] > 0) {
		$emails = explode(',', db_fetch_cell_prepared('SELECT emails
			FROM plugin_notification_lists
			WHERE id = ?',
			array($alarm['notify_alert'])));

		if (cacti_sizeof($emails)) {
			foreach ($emails as $e) {
				$e = trim($e);
				$alarm_emails[$e] = $e;
			}
		}
	}

	$alarm_emails = implode(',', $alarm_emails);

	gridalarms_debug("NOTE   - Email Receipients:'$alarm_emails'");

	return $alarm_emails;
}

/**
 * return a properly formated breach details for an alarm script
 *
 * @param $alarm        the alarm to be used to construct the Email list
 *
 * @return string|array formatted list of breached items
 */
function get_alarm_breach_details_for_script(array $alarm) : string|array {
	$expression = get_expression_by_id($alarm['expression_id']);

	if (cacti_sizeof($expression)) {
		return alarm_breached_items($alarm['id'], false, false, true);
	}

	return '';
}

/**
 * return a properly formated header for breach details
 *
 * @param $alarm        the alarm to be used to construct the Email list
 *
 * @return string|array formatted list breached item header
 */
function get_alarm_breach_details_headers_for_script(array $alarm) : string|array {
	/* expression type */
	$expression = get_expression_by_alarm_id($alarm['id']);

	if (cacti_sizeof($expression)) {
		if ($expression['ds_type'] < 2) {
			$layout = array_rekey(
				db_fetch_assoc_prepared('SELECT display_name
					FROM gridalarms_alarm_layout
					WHERE alarm_id = ?
					ORDER BY sequence ASC',
					array($alarm['id'])),
				'display_name', 'display_name'
			);

			if (cacti_sizeof($layout)) {
				return implode(',', $layout);
			}
		}
	}

	return '';
}

/**
 * return a properly formated db columns name for breach details
 *
 * @param $alarm      the alarm to be used to construct the Email list
 *
 * @return string|array formatted list breached item db columns name
 */
function get_alarm_breach_details_columns_for_script(array $alarm) : string|array {
	/* expression type */
	$expression = get_expression_by_alarm_id($alarm['id']);

	if (cacti_sizeof($expression)) {
		if ($expression['ds_type'] < 2) {
			$layout = array_rekey(
				db_fetch_assoc_prepared('SELECT column_name
					FROM gridalarms_alarm_layout
					WHERE alarm_id = ?
					ORDER BY sequence ASC',
					array($alarm['id'])),
				'column_name', 'column_name'
			);

			if (cacti_sizeof($layout)) {
				return implode(',', $layout);
			}
		}
	}

	return '';
}

/**
 * do tag subsitution on special variables for gridalarms scripts
 *
 * @param string  $script the script to update
 * @param mixed   $currentval the current value of the alert
 * @param array   $alarm the alarm to be used to construct the Email list
 *
 * @return string updated script
 */
function gridalarms_script_replace(string $script, mixed $currentval, array $alarm) : string {
	global $alarm_types;

	/* the current values */
	$script  = str_replace('<NAME>', $alarm['name'], $script);
	$script  = str_replace('<ID>', $alarm['id'], $script);

	/* hi/low messaging */
	$highvalue   = $alarm['alarm_type'] == 0 ? $alarm['alarm_hi'] : ($alarm['alarm_type'] == 1 ? $alarm['time_hi'] : '');
	$lowvalue    = $alarm['alarm_type'] == 0 ? $alarm['alarm_low'] : ($alarm['alarm_type'] == 1 ? $alarm['time_low'] : '');

	$script  = str_replace('<HI>', ($highvalue == '' ? __('N/A', 'gridalarms') : $highvalue), $script);
	$script  = str_replace('<LOW>', ($lowvalue == '' ? __('N/A', 'gridalarms') : $lowvalue), $script);
	$script  = str_replace('<VALUE>', $currentval, $script);

	$script  = str_replace('<CLUSTERID>', $alarm['clusterid'], $script);
	$script  = str_replace('|alert_clusterid|', $alarm['clusterid'], $script);
	$script  = str_replace('|alert_clustername|', get_clustername($alarm['clusterid']), $script);

	/* detail breach items */
	if (substr_count($script, '<ITEMS_HEADER>') > 0) {
		$details = get_alarm_breach_details_headers_for_script($alarm);
		$script  = str_replace('<ITEMS_HEADER>', $details, $script);
	}

	if (substr_count($script, '<ITEMS_LIST>') > 0) {
		$details = get_alarm_breach_details_for_script($alarm);
		$script  = str_replace('<ITEMS_LIST>', $details, $script);
	}

	return $script;
}

/**
 * do tag subsitution on special variables for gridalarms messages
 *
 * @param string  $alarm_text the alarm text to update
 * @param mixed   $currentval the current value of the alert
 * @param array   $alarm the alarm to be used to construct the Email list
 * @param bool    $admin the are we performing replacement for the admin
 *
 * @return string updated alert text
 */
function gridalarms_text_replace(string $alarm_text, mixed $currentval, array $alarm, string $bitems = '', bool $admin = false) : string {
	global $config, $alarm_types;

	/* the current values */
	$alarm_text  = str_replace('<VALUE>', $currentval, $alarm_text);
	$alarm_text  = str_replace('<NAME>', $alarm['name'], $alarm_text);
	$alarm_text  = str_replace('<TYPE>', $alarm_types[$alarm['alarm_type']], $alarm_text);

	/* hi/low messaging */
	$highvalue   = $alarm['alarm_type'] == 0 ? $alarm['alarm_hi'] : ($alarm['alarm_type'] == 1 ? $alarm['time_hi'] : '');
	$lowvalue    = $alarm['alarm_type'] == 0 ? $alarm['alarm_low'] : ($alarm['alarm_type'] == 1 ? $alarm['time_low'] : '');
	$alarm_text  = str_replace('<HI>', ($highvalue == '' ? __('N/A', 'gridalarms') : $highvalue), $alarm_text);
	$alarm_text  = str_replace('<LOW>', ($lowvalue == '' ? __('N/A', 'gridalarms') : $lowvalue), $alarm_text);

	/* fail triggers */
	$failtrigger = $alarm['alarm_type'] == 0 ? $alarm['alarm_fail_trigger'] : ($alarm['alarm_type'] == 1 ? $alarm['time_fail_trigger'] : '');
	$alarm_text  = str_replace('<TRIGGER>', ($failtrigger == '' ? __('N/A', 'gridalarms') : $failtrigger), $alarm_text);

	/* show durations */
	$duration    = $alarm['alarm_type'] == 1 ? plugin_alarm_duration_convert($alarm['time_fail_length'], 'time') : '';
	$alarm_text  = str_replace('<DURATION>', ($duration == '' ? __('N/A', 'gridalarms') : $duration), $alarm_text);

	/* replace cluster information */
	$alarm_text  = str_replace('|alert_clusterid|', $alarm['clusterid'], $alarm_text);
	$alarm_text  = str_replace('|alert_clustername|', get_clustername($alarm['clusterid']), $alarm_text);

	/* replace custom input information */
	$alarm_text  = gridalarms_replace_custom_input($alarm['expression_id'], $alarm_text);

	/* date values */
	$alarm_text  = str_replace('<DATE>', date('Y-m-d H:i:s'), $alarm_text);
	$alarm_text  = str_replace('<TIME>', time(), $alarm_text);

	/* use thold's base url */
	$httpurl = read_config_option('base_url');

	/* urls */
	$alarm_text  = str_replace('<DETAILS_URL>', "<a href='" . html_escape($httpurl . 'plugins/gridalarms/' . ($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') . 'action=details&id=' . $alarm['id']) . "'>Breached Items List</a>", $alarm_text);
	$alarm_text  = str_replace('<URL>', "<a href='" . html_escape($httpurl . 'plugins/gridalarms/' . ($admin ? 'gridalarms_alarm.php':'grid_alarmdb.php')) . "'>Alert Link</a>", $alarm_text);

	/* detail breach items */
	if (substr_count($alarm_text, '<DETAILS>') > 0) {
		$alarm_text = str_replace('<DETAILS>', $bitems, $alarm_text);
	}

	return $alarm_text;
}

function gridalarms_replace_custom_input(int $expression_id, string $text) : string {
	$items = db_fetch_assoc_prepared('SELECT name, value
		FROM gridalarms_expression_input
		WHERE expression_id = ?',
		array($expression_id));

	if (cacti_sizeof($items)) {
		foreach ($items as $i) {
			$text = str_replace('|input_' . trim($i['name']) . '|', trim($i['value']), $text);
		}
	}

	return $text;
}

function gridalarms_template_replace_custom_input(int $expression_id, string $text) : string {
	$items = db_fetch_assoc_prepared('SELECT name, value
		FROM gridalarms_template_expression_input
		WHERE expression_id = ?',
		array($expression_id));

	if (cacti_sizeof($items)) {
		foreach ($items as $i) {
			$text = str_replace('|input_' . $i['name'] . '|', $i['value'], $text);
		}
	}

	return $text;
}

/**
 * run the properly formatted script and discard results
 *
 * @param string $script the script to execute
 *
 * @return null
 */
function gridalarms_exec_scripts(string $script) : void {
	global $config;

	$script    = str_replace('<CACTI_ROOT>', $config['base_path'], $script);
	$command   = trim($script);
	$output    = array();
	$exit_code = 0;

	if (strlen($command)) {
		exec($command, $output, $exit_code);

		if ($exit_code != 0) {
			cacti_log("WARNING: Unable to execute Command: '" . $command ."', Output: '" . implode(', ', $output) . "'", false, 'GRIDALERTS');
		}
	}
}

/**
 * function to check an alarm based upon the alarm entry
 *
 * @param array  $alarm the alarm to be used to construct the Email list
 * @param bool   $force ignore frequency settings and run forcibly
 *
 * @return null
 */
function gridalarms_check_alarm(array $alarm, bool $force = false) : void {
	global $config, $debug, $alarm_types, $gridalarms_types, $plugins;

	include_once($config['base_path'] . '/lib/variables.php');

	gridalarms_debug('NOTE:  - ----- Check Started -----');

	/* log durations for reporting */
	$start_time = microtime(true);

	/* date for last_runtime calculation */
	$start_date = date('Y-m-d H:i:s');

	/* check for exemptions */
	$weekday = date('l');

	/* legacy variable support */
	$id = $alarm['id'];

	/* numbers required for polling calculation */
	$base_time       = $alarm['base_time'];
	$last_runtime    = strtotime($alarm['last_runtime']);
	$poller_interval = read_config_option('poller_interval');
	$samp_interval   = $alarm['frequency'] * 300;
	$samp_time       = time();

	/* Get all the info about the item from the database */
	$expression = get_expression_by_id($alarm['expression_id']);

	/* check for general exemptions */
	$alert_exempt = read_config_option('alert_exempt');

	$weekday = date('l');
	if (($weekday == 'Saturday' || $weekday == 'Sunday') && $alert_exempt == 'on') {
		gridalarms_debug('Alert checking is disabled by global weekend exemption');
		return;
	}

	/* Check for the weekend exemption on the threshold level */
	if (($weekday == 'Saturday' || $weekday == 'Sunday') && $alarm['exempt'] == 'on') {
		gridalarms_debug('NOTE   - Weekend Exemption in Affect (Current Alert) - Exiting');
		return;
	}

	/**
	 * Don't alert for this Cluster if it's selected for maintenance
	 * this is more difficult with alerts that cover multiple clusters
	 * so only support cluster specific checks for now.
	 */
	if ((api_plugin_is_enabled('maint') || in_array('maint', $plugins)) && $alarm['clusterid'] > 0) {
		include_once($config['base_path'] . '/plugins/maint/functions.php');

		$host_id = db_fetch_cell_prepared('SELECT cacti_host
			FROM grid_clusters
			WHERE clusterid = ?',
			array($alarm['clusterid']));

		if (plugin_maint_check_cacti_host($host_id)) {
			gridalarms_debug('Alert checking is disabled for Cluster by maintenance schedule');
			return;
		}
	}

	/* measure the current value */
	$currentval = '';

	gridalarms_debug('NOTE:  - About to check Value');

	//handling day light saving and timezone switch cases:
	$base_time = $base_time + date('Z',$base_time);
	$samp_time = $samp_time + date('Z',$samp_time);
	$last_runtime = $last_runtime + date('Z',$last_runtime);

	//end handling dst

	/* get current value based on the type */
	//handle the case that poller_gridalarms does not run sometimes due to the previous running is too long that overlaps the current running.
	//if the last_run_time is smaller than current target run time, then, run it.
	//How to calculate the target run time:
	//floor(($samp_time - $base_time) / $samp_interval) * $samp_interval + $base_time
	//E.g. #1
	//base_time 00:00, samp_interval 4 hours, samp_time 16:10
	//then the target time is 16:00
	//E.g. #2
	//base_time 00:00, samp_interval 4 hours, samp_time 17:20
	//then the target time is 16:00
	//E.g. #3
	//base_time 00:00, samp_interval 4 hours, samp_time 15:20
	//then the target time is 12:00
	if ($last_runtime < (floor(($samp_time - $base_time) / $samp_interval) * $samp_interval + $base_time) || $force) {
		if (cacti_sizeof($expression)) {
			switch($expression['ds_type']) {
			case 0: // Expression
				$currentval = get_current_value_by_expression($alarm);

				break;
			case 1: // SQL Query
				$currentval = get_current_value_by_sql($alarm);

				break;
			case 2: // Script
				$currentval = get_current_value_by_script($alarm);

				break;
			}
		} else {
			gridalarms_debug('NOTE:  - Returning! No Data Source Defined');
			gridalarms_debug('NOTE:  - ----- Check Completed -----');

			return;
		}
	} else {
		gridalarms_debug('NOTE:  - Returning! Not time to Poll');
		gridalarms_debug('NOTE:  - ----- Check Completed -----');

		return;
	}

	gridalarms_debug('NOTE:  - Value has been checked');

	/* if an alarm is not completed, just return */
	if ($currentval === false) {
		gridalarms_debug('NOTE:  - Returning! Current Value is Illegal');
		gridalarms_debug('NOTE:  - ----- Check Completed -----');
		return;
	}

	if ($currentval == 'NaN') {
		if ($expression['ds_type'] < 2) {
			gridalarms_debug("ERROR - Alert:'" . $alarm['name']  . "' Returned a Non-numeric Value");
			cacti_log("WARNING: Alert Name:'" . $alarm['name']  . "', Expression ID:'" . $alarm['expression_id'] . "' Returned a Non-numeric Value", false, 'GRIDALERTS');
		} else {
			$script_thold = do_script_variable_replacement($expression['script_thold'], $alarm);
			gridalarms_debug("ERROR - Alert:'" . $alarm['name']  . "' Returned a Non-numeric Value");
			cacti_log("WARNING: Alert Name:'" . $alarm['name']  . "', Script:'" . $script_thold . "' Returned a Non-numeric Value", false, 'GRIDALERTS');
		}

		gridalarms_debug('NOTE:  - Returning! Current Value is NaN');
		gridalarms_debug('NOTE:  - ----- Check Completed -----');

		return;
	}

	gridalarms_debug("NOTE   - Alert Name:'" . $alarm['name'] . "', Alert Type:'" . $gridalarms_types[$expression['ds_type']] . "', Value Type:'" . $alarm_types[$alarm['alarm_type']] . "', Value:'" . $currentval . "'");

	/* update lastread */
	db_execute_prepared('UPDATE gridalarms_alarm
		SET lastread = ?
		WHERE id = ?',
		array($currentval, $id));

	/* get the breached items for logging */
	gridalarms_debug('NOTE:  - Getting Breached Items');
	$bitems = alarm_breached_items($id, true);
	gridalarms_debug('NOTE:  - Getting Breached Items Completed');

	/* variables for sysloging */
	$syslog_set             = ($alarm['syslog_enabled'] == 'on' ? true : false);
	$alarm_syslog_priority 	= $alarm['syslog_priority'];
	$alarm_syslog_facility 	= $alarm['syslog_facility'];

	/* simplify some variables */
	$req_ack   = $alarm['req_ack'];
	$trigger   = $alarm['alarm_fail_trigger'];
	$alertstat = $alarm['alarm_alert'];

	/* building Email list */
	$alarm_emails = gridalarms_build_email_list($alarm);

	/* replace special variables */
	$msg     = gridalarms_text_replace($alarm['email_body'], $currentval, $alarm, $bitems);
	$subject = gridalarms_text_replace($alarm['email_subject'], $currentval, $alarm);

	switch ($alarm['alarm_type']) {
	case 0:	//  HI/Low
		gridalarms_debug('NOTE:  - Hi/Low Alert Detected');

		$breach_up   = ($alarm['alarm_hi'] != '' && $currentval > $alarm['alarm_hi']);
		$breach_down = ($alarm['alarm_low'] != '' && $currentval < $alarm['alarm_low']);

		if ($breach_up || $breach_down) {
			$alarm['alarm_fail_count'] = $alarm['alarm_fail_count'] + $alarm['frequency'];
			$alarm['alarm_alert'] = ($breach_up ? 2 : 1);

			// Re-Alert?
			$ra     = ($alarm['alarm_fail_count'] > $trigger && $alarm['repeat_alert'] != 0 && ($alarm['alarm_fail_count'] % $alarm['repeat_alert']) == 0);
			$status = 3;

			if ($alarm['alarm_fail_count'] == $trigger || $ra) {
				$status = 1;
			}

			if ($ra) {
				$status = 2;
			}

			gridalarms_debug("NOTE:  - Hi/Low In Breach Condition Status is '$status'");

			$logmsg = 'ALERT: ' . $alarm['name'] . ' ' . ($ra ? 'is still' : 'went') . ' ' . ($breach_up ? 'above' : 'below') . ' threshold of ' . ($breach_up ? $alarm['alarm_hi'] : $alarm['alarm_low']) . " with $currentval";

			if ($subject == '') {
				$subject = $logmsg;
			}

			if ($status == 1 || $status == 2) {
				if ($syslog_set) {
					rtm_logger($alarm_syslog_priority, $alarm_syslog_facility, $alarm['name'], $breach_up, ($breach_up ? $alarm['alarm_hi'] : $alarm['alarm_low']), $currentval, $trigger, $alarm['alarm_fail_count']);
				}

				if (trim($alarm_emails) != '') {
					alarm_mail($alarm_emails, '', $subject, $msg, $alarm['format_file']);
				}

				// After the alarm is breached(up or down) , notify admin and notify job users.
				notify_job_users($subject, $alarm, $currentval, $alarm_emails);

				if ($alarm['req_ack'] == 'on') {
					db_execute_prepared('UPDATE gridalarms_alarm
						SET acknowledgement="on"
						WHERE id = ?',
						array($id));
				}

				/*When "Run the Event Triggering Command on the Retriggered Alert" checked,  high/low post scripts are alowed to run in re-alert*/
				if ($alarm['cmd_retrigger_enabled'] == 'on') {
					$ra = false;
				}

				/* execute post execution scripts for hi/low, but only when it's not an realert. */
				if (!$ra) {
					if ($breach_up) {
						if (trim($alarm['trigger_cmd_high']) != '') {
							gridalarms_set_execenv($currentval, $alarm);

							$script = gridalarms_script_replace($alarm['trigger_cmd_high'], $currentval, $alarm);

							gridalarms_debug('DEBUG: Executing Post High Threshold Script: ' . $script);

							gridalarms_exec_scripts($script);
						}
					} elseif ($breach_down) {
						if (trim($alarm['trigger_cmd_low']) != '') {
							gridalarms_set_execenv($currentval, $alarm);

							$script = gridalarms_script_replace($alarm['trigger_cmd_low'], $currentval, $alarm);

							gridalarms_debug('DEBUG: Executing Post Low Threshold Script: ' . $script);

							gridalarms_exec_scripts($script);
						}
					}
				} else {
					gridalarms_debug('NOTE   - Not triggering post scripts because it is realert');
				}
			}

			/* don't log notice events for now.  log becomes quite chatty */
			if ($status != 3) {
				alarm_log(array(
					'type'        => 0,
					'time'        => time(),
					'alarm_id'    => $id,
					'alarm_value' => ($breach_up ? $alarm['alarm_hi'] : $alarm['alarm_low']),
					'current'     => $currentval,
					'status'      => $status,
					'description' => $logmsg,
					'emails'      => $alarm_emails,
					'details'     => $bitems)
				);
			}

			$db_columns = db_fetch_cell_prepared('SELECT count(*)
				FROM gridalarms_alarm_layout
				WHERE alarm_id = ?
				AND column_name IN ("host", "hostname", "exec_host", "from_host")',
				array($id));

			if ($db_columns) {
				$alarm_logs = db_fetch_assoc_prepared('SELECT a.clusterid, MIN(c.time) AS time,
					b.name, b.email_subject, a.alarm_id,
					a.host AS  alarmhost
					FROM gridalarms_alarm_log_items AS a
					INNER JOIN gridalarms_alarm AS b
					ON a.alarm_id = b.id
					INNER JOIN gridalarms_alarm_log AS c
					ON b.id=c.alarm_id
					WHERE a.alarm_id = ?
					AND a.host != ""
					GROUP BY alarm_id, alarmhost, clusterid',
					array($id));

				if (cacti_sizeof($alarm_logs)) {
					foreach ($alarm_logs as $log) {
						$log['email_subject'] = $subject;
						api_plugin_hook_function('gridalarms_update_hostsalarm', $log);
					}
				}
			}

			db_execute_prepared('UPDATE gridalarms_alarm
				SET alarm_alert = ?,
				alarm_fail_count = ?
				WHERE id = ?',
				array($alarm['alarm_alert'], $alarm['alarm_fail_count'], $id));
		} else {
			gridalarms_debug('NOTE:  - Hi/Low NOT In Breach Condition');

			if ($alertstat != 0) {
				$logmsg = $subject = 'NORMAL: ' . $alarm['name'] . " restored to normal with value $currentval";

				if ($syslog_set) {
					rtm_logger($alarm_syslog_priority, $alarm_syslog_facility, $alarm['name'], 'ok', 0, $currentval, $trigger, $alarm['alarm_fail_count']);
				}

				if ($alarm['alarm_fail_count'] >= $trigger) {
					if ($alarm['restored_alert'] != 'on') {
						$status = 0;

						if (trim($alarm_emails) != '') {
							alarm_mail($alarm_emails, '', $subject, $msg, $alarm['format_file']);
						}

						// After the alarm is breached(up or down) , notify admin and notify job users.
						notify_job_users($subject, $alarm, $currentval, $alarm_emails);
					} else {
						$status = 4;
					}

					if ($alarm['req_ack'] == 'on') {
						db_execute_prepared('UPDATE gridalarms_alarm
							SET acknowledgement="on"
							WHERE id = ?',
							array($id));
					}

					alarm_log(array(
						'type'        => 0,
						'time'        => time(),
						'alarm_id'    => $id,
						'alarm_value' => '',
						'current'     => $currentval,
						'status'      => $status,
						'description' => $logmsg,
						'emails'      => $alarm_emails,
						'details'     => $bitems)
					);

					/* execute post restoration script */
					if (trim($alarm['trigger_cmd_norm']) != '') {
						gridalarms_set_execenv($currentval, $alarm);

						$script = gridalarms_script_replace($alarm['trigger_cmd_norm'], $currentval, $alarm);

						gridalarms_debug('NOTE   - Executing Post Normal Threshold Script: ' . $script);

						gridalarms_exec_scripts($script);
					}
				}
			}

			db_execute_prepared('UPDATE gridalarms_alarm
				SET alarm_alert = 0,
				alarm_fail_count = 0
				WHERE id = ?',
				array($id));
		}

		break;
	case 1:	// Time Based
		gridalarms_debug('NOTE:  - Time Based Alert Detected');

		$breach_up   = ($alarm['time_hi']  != '' && $currentval > $alarm['time_hi']);
		$breach_down = ($alarm['time_low'] != '' && $currentval < $alarm['time_low']);

		$cur_time = time();
		$alarm['alarm_alert'] = ($breach_up ? 2 : ($breach_down ? 1 : 0));
		$trigger  = $alarm['time_fail_trigger'];
		$step     = 300;

		/* give 60 seconds for margin of error */
		$time     = $cur_time - ($alarm['time_fail_length'] * $step) - 60;
		$failures = db_fetch_cell_prepared('SELECT count(id)
			FROM gridalarms_alarm_log
			WHERE alarm_id = ?
			AND status NOT IN (0,4,99)
			AND time > ?',
			array($id, $time));

		if ($alarm['alarm_alert']) {
			$failures += $alarm['alarm_fail_count'];

			// We should only re-alert X minutes after last email, not every 5 pollings, etc...
			// Re-Alert?
			$realerttime = $cur_time - (($alarm['repeat_alert'] - 1) * $step) - 60;

			$lastemailtime = db_fetch_cell_prepared('SELECT time
				FROM gridalarms_alarm_log
				WHERE alarm_id = ?
				AND (status = 1 OR status = 2)
				ORDER BY time DESC
				LIMIT 1', array($id));

			$ra     = ($failures > $trigger && $alarm['repeat_alert'] != 0 && $lastemailtime > 1 && ($lastemailtime < $realerttime));
			$status = 3;

			if ($failures == $trigger) {
				$status = 1;
			}

			if ($ra) {
				$status = 2;
			}

			if ($alarm['repeat_alert'] == 0 && $failures == $trigger) {
				$lastalert = db_fetch_assoc_prepared('SELECT time, status
					FROM gridalarms_alarm_log
					WHERE alarm_id = ?
					ORDER BY time DESC',
					array($id));

				if (($lastalert['status'] == 1 || $lastalert['status'] == 2) && $time> $lastalert['time']) {
					$status = 3;
				}
			}

			if ($status == 1 || $status == 2) {
				$notify = true;
			} else {
				$notify = false;
			}

			$logmsg = ($notify ? 'ALERT: ':'TRIGGER: ') . $alarm['name'] . ' ' . ($failures > $trigger ? 'is still' : 'went') . ' ' . ($breach_up ? 'above' : 'below') . ' threshold of ' . ($breach_up ? $alarm['time_hi'] : $alarm['time_low']) . " with $currentval";

			if ($subject == '') {
				$subject = $logmsg;
			}

			if ($notify) {
				if ($syslog_set) {
					rtm_logger($alarm_syslog_priority, $alarm_syslog_facility, $alarm['name'], $breach_up, ($breach_up ? $alarm['time_hi'] : $alarm['time_low']), $currentval, $trigger, $failures);
				}

				if (trim($alarm_emails) != '') {
					alarm_mail($alarm_emails, '', $subject, $msg, $alarm['format_file']);
				}

				// After the alarm is breached(up or down) , notify admin and notify job users.
				notify_job_users($subject, $alarm, $currentval, $alarm_emails);

				if ($alarm['req_ack'] == 'on') {
					db_execute_prepared('UPDATE gridalarms_alarm
						SET acknowledgement="on"
						WHERE id = ?',
						array($id));
				}

				/* When "Run the Event Triggering Command on the Retriggered Alert" checked,
				 * high/low post scripts are alowed to run in re-alert
				 */
				if ($alarm['cmd_retrigger_enabled'] == 'on') {
					$ra = false;
				}

				/* execute post execution scripts for hi/low, but only when it's not an realert. */
				if (!$ra) {
					if ($breach_up) {
						if (trim($alarm['trigger_cmd_high']) != '') {
							gridalarms_set_execenv($currentval, $alarm);

							$script = gridalarms_script_replace($alarm['trigger_cmd_high'], $currentval, $alarm);

							gridalarms_debug('DEBUG: Executing Post High Threshold Script: ' . $script);

							gridalarms_exec_scripts($script);
						}
					} elseif ($breach_down) {
						if (trim($alarm['trigger_cmd_low']) != '') {
							gridalarms_set_execenv($currentval, $alarm);

							$script = gridalarms_script_replace($alarm['trigger_cmd_low'], $currentval, $alarm);

							gridalarms_debug('DEBUG: Executing Post Low Threshold Script: ' . $script);

							gridalarms_exec_scripts($script);
						}
					}
				}
			}

			alarm_log(array(
				'type'        => 1,
				'time'        => time(),
				'alarm_id'    => $id,
				'alarm_value' => ($breach_up ? $alarm['time_hi'] : $alarm['time_low']),
				'current'     => $currentval,
				'status'      => $status,
				'description' => $logmsg,
				'emails'      => $alarm_emails,
				'details'     => $bitems)
			);

			$db_columns = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM gridalarms_alarm_layout
				WHERE alarm_id = ?
				AND column_name IN ("host", "hostname", "exec_host", "from_host")',
				array($id));

 			if ($db_columns) {
				$alarm_logs = db_fetch_assoc_prepared('SELECT a.clusterid, MIN(c.time) AS time,
					b.name, b.email_subject,a.alarm_id, a.host AS alarmhost
					FROM gridalarms_alarm_log_items AS a
					INNER JOIN gridalarms_alarm AS b
					ON a.alarm_id = b.id
					INNER JOIN gridalarms_alarm_log AS c
					ON b.id=c.alarm_id
					WHERE a.alarm_id = ?
					AND a.host != ""
					GROUP BY alarm_id, alarmhost, clusterid',
					array($id));

				if (cacti_sizeof($alarm_logs)) {
					foreach ($alarm_logs as $log) {
						$log['email_subject'] = $subject;

						api_plugin_hook_function('gridalarms_update_hostsalarm', $log);
					}
				}
 			}

			db_execute_prepared('UPDATE gridalarms_alarm
				SET alarm_alert = ?,
				alarm_fail_count = ?
				WHERE id = ?',
				array($alarm['alarm_alert'], $failures, $id));
		} else {
			if ($alertstat != 0 && $failures < $trigger) {
				if ($syslog_set) {
					rtm_logger($alarm_syslog_priority, $alarm_syslog_facility, $alarm['name'], 'ok', 0, $currentval, $trigger, $alarm['alarm_fail_count']);
				}

				$logmsg = $subject = 'NORMAL: ' . $alarm['name'] . " restored to normal with value $currentval";

				if ($alarm['restored_alert'] != 'on') {
					$status = 0;

					if (trim($alarm_emails) != '') {
						alarm_mail($alarm_emails, '', $subject, $msg, $alarm['format_file']);
					}

					// After the alarm is breached(up or down) , notify admin and notify job users.
					notify_job_users($subject, $alarm, $currentval, $alarm_emails);
				} else {
					$status = 4;
				}

				if ($alarm['req_ack'] == 'on') {
					db_execute_prepared('UPDATE gridalarms_alarm
						SET acknowledgement="on"
						WHERE id = ?',
						array($id));
				}

				/* execute post restoration script */
				if (trim($alarm['trigger_cmd_norm']) != '') {
					gridalarms_set_execenv($currentval, $alarm);

					$script = gridalarms_script_replace($alarm['trigger_cmd_norm'], $currentval, $alarm);

					gridalarms_debug('NOTE   - Executing Post Normal Threshold Script: ' . $script);

					gridalarms_exec_scripts($script);
				}

				/* run event trigger */
				alarm_log(array(
					'type'        => 1,
					'time'        => time(),
					'alarm_id'    => $id,
					'alarm_value' => '',
					'current'     => $currentval,
					'status'      => $status,
					'description' => $logmsg,
					'emails'      => $alarm_emails,
					'details'     => $bitems)
				);

				db_execute_prepared('UPDATE gridalarms_alarm
					SET alarm_alert = 0,
					alarm_fail_count = ?
					WHERE id = ?',
					array($failures, $id));
			} else {
				db_execute_prepared('UPDATE gridalarms_alarm
					SET alarm_fail_count = ?
					WHERE id = ?',
					array($failures, $id));
			}
		}

		break;
	}

	gridalarms_debug('NOTE:  - ----- Check Completed -----');

	/* log durations for reporting */
	$end_time = microtime(true);

	db_execute_prepared('UPDATE gridalarms_alarm
		SET last_duration = ?, last_runtime = ?
		WHERE id= ?',
		array($end_time - $start_time,  $start_date, $id));
}

function notify_job_users(string $subject, array $alarm, mixed $currentval, mixed $sent_emails) : void {
	if (!($alarm['notify_cluster_admin'] == 1 || $alarm['notify_users'] == 'on')) return;

	$poller_interval = read_config_option('poller_interval') ;
	if (empty($poller_interval)) {
		$poller_interval = 300;
	}

	$total_gal_id_array = array();
	$gal_id_array       = array();
	$id                 = $alarm['id'];

	$columns = array_rekey(
		db_fetch_assoc_prepared('SELECT column_name, display_name, type, units, align, digits, autoscale
			FROM gridalarms_alarm_layout
			WHERE alarm_id = ?
			ORDER BY sequence ASC',
			array($alarm['id'])),
		'column_name', array('display_name', 'type', 'units', 'align', 'digits', 'autoscale')
	);

	// when notify_cluster_admin is checked, send all jobs to admin email
	// need to fetch breached jobs from grid_jobs and grid_jobs_finished in case jobs have been moved to grid_jobs_finished;
	if ($alarm['notify_cluster_admin'] == 1) {
		//ToDo: Only 'clusterid' is requried in next block. Combine follow 4 SQL as two. Remove GROUP BY and use DISTINCT
		$cluster_groups = db_fetch_assoc_prepared('SELECT gj.clusterid,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs AS gj
			ON gj.exec_host=gal.host
			AND gj.clusterid=gal.clusterid
			AND gj.jobid=gal.jobid
			AND gj.indexid=gal.indexid
			AND gj.submit_time=gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id=gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert>0
			AND gal.type & 2 >0
			AND (gal.last_reported="0000-00-00" OR (ga.repeat_alert>0 AND gal.last_reported<now()))
			GROUP BY clusterid
			UNION
			SELECT gj.clusterid,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs_finished AS gj
			ON gj.exec_host=gal.host
			AND gj.clusterid=gal.clusterid
			AND gj.jobid=gal.jobid
			AND gj.indexid=gal.indexid
			AND gj.submit_time=gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id=gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert>0
			AND gal.type & 2 >0
			AND (gal.last_reported="0000-00-00" OR (ga.repeat_alert>0 AND gal.last_reported<now()))
			GROUP BY clusterid
			UNION
			SELECT gal.clusterid,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs AS gj
			ON gj.clusterid=gal.clusterid
			AND gj.jobid=gal.jobid
			AND gj.indexid=gal.indexid
			AND gj.submit_time=gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id=gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert>0
			AND gal.type & 1 >0
			AND gal.jobid > 0
			AND (gal.last_reported="0000-00-00" OR (ga.repeat_alert>0 AND gal.last_reported<now()))
			GROUP BY clusterid
			UNION
			SELECT gal.clusterid,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs_finished AS gj
			ON gj.clusterid=gal.clusterid
			AND gj.jobid=gal.jobid
			AND gj.indexid=gal.indexid
			AND gj.submit_time=gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id=gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert>0
			AND gal.type & 1 >0
			AND gal.jobid > 0
			AND (gal.last_reported="0000-00-00" OR (ga.repeat_alert>0 AND gal.last_reported<now()))
			GROUP BY clusterid',
			array($id, $id, $id, $id));

		if (cacti_sizeof ($cluster_groups)) {
			foreach ($cluster_groups as $cluster_group) {
				//ToDo merge follow 4 SQL as two with OR/| expression.
				$job_items = db_fetch_assoc_prepared("SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs AS gj
					ON gj.exec_host = gal.host
					AND gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs_finished AS gj
					ON gj.exec_host = gal.host
					AND gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs AS gj
					ON gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 1 > 0
					AND gal.jobid > 0
					AND gal.clusterid = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs_finished AS gj
					ON gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id=gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 1 > 0
					AND gal.jobid > 0
					AND gal.clusterid = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))",
					array(
						$id, $cluster_group['clusterid'],
						$id, $cluster_group['clusterid'],
						$id, $cluster_group['clusterid'],
						$id, $cluster_group['clusterid']
					)
				);

				// send Email per cluster
				$gal_id_array = do_items($subject, $alarm, $currentval, $job_items, $columns, true, $sent_emails);

				if (cacti_sizeof($gal_id_array)) {
					foreach ($gal_id_array as $gal_id) {
						$total_gal_id_array[$gal_id] = $gal_id;
					}
				}
			}
		}
	}

	// when notify_users, group items by cluserid and user, send relevant jobs to their users per cluster
	// need to fetch breached jobs from grid_jobs and grid_jobs_finished in case jobs have been moved to grid_jobs_finished;
	if ($alarm['notify_users'] == 'on') {
		$cluster_user_groups = db_fetch_assoc_prepared("SELECT gj.clusterid, gj.user AS job_user, count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs AS gj
			ON gj.exec_host = gal.host
			AND gj.clusterid = gal.clusterid
			AND gj.jobid = gal.jobid
			AND gj.indexid = gal.indexid
			AND gj.submit_time = gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id = gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert > 0
			AND gal.type & 2 > 0
			AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
			GROUP BY clusterid,job_user
			UNION
			SELECT gj.clusterid, gj.user AS job_user, count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs_finished AS gj
			ON gj.exec_host = gal.host
			AND gj.clusterid = gal.clusterid
			AND gj.jobid = gal.jobid
			AND gj.indexid = gal.indexid
			AND gj.submit_time = gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id=gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert > 0
			AND gal.type & 2 > 0
			AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
			GROUP BY clusterid,job_user
			UNION
			SELECT gal.clusterid,gal.user AS job_user,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs AS gj
			ON gj.clusterid = gal.clusterid
			AND gj.jobid = gal.jobid
			AND gj.indexid = gal.indexid
			AND gj.submit_time = gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id = gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert > 0
			AND gal.type & 1 > 0
			AND gal.jobid > 0
			AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
			GROUP BY clusterid,job_user
			UNION
			SELECT gal.clusterid,gal.user AS job_user,count(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN grid_jobs_finished AS gj
			ON gj.clusterid = gal.clusterid
			AND gj.jobid = gal.jobid
			AND gj.indexid = gal.indexid
			AND gj.submit_time = gal.submit_time
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id = gal.alarm_id
			WHERE ga.id = ?
			AND ga.alarm_alert > 0
			AND gal.type & 1 > 0
			AND gal.jobid > 0
			AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
			GROUP BY clusterid,job_user",
			array($id, $id, $id, $id));

		if (cacti_sizeof ($cluster_user_groups)) {
			foreach ($cluster_user_groups as $cluster_user_group) {
				$job_items = db_fetch_assoc ("SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs AS gj
					ON gj.exec_host = gal.host
					AND gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND gj.user = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs_finished AS gj
					ON gj.exec_host = gal.host
					AND gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND gj.user = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs AS gj
					ON gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 1 > 0
					AND gal.jobid > 0
					AND gal.clusterid = ?
					AND gal.user = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT gal.*, gj.mailuser, gal.user AS job_user
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs_finished AS gj
					ON gj.clusterid = gal.clusterid
					AND gj.jobid = gal.jobid
					AND gj.indexid = gal.indexid
					AND gj.submit_time = gal.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 1 > 0
					AND gal.jobid > 0
					AND gal.clusterid = ?
					AND gal.user = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))",
					array(
						$id, $cluster_user_group['clusterid'], $cluster_user_group['job_user'],
						$id, $cluster_user_group['clusterid'], $cluster_user_group['job_user'],
						$id, $cluster_user_group['clusterid'], $cluster_user_group['job_user'],
						$id, $cluster_user_group['clusterid'], $cluster_user_group['job_user'],
					)
				);

				// send emails per cluster and user.
				$gal_id_array = do_items($subject, $alarm, $currentval, $job_items, $columns, false, $sent_emails);

				if (cacti_sizeof($gal_id_array)) {
					foreach ($gal_id_array as $gal_id) {
						$total_gal_id_array[$gal_id] = $gal_id;
					}
				}
			}
		}

		// Get job items for host based alerts not associated with a job directly
		$cluster_hosts = db_fetch_assoc_prepared("SELECT gal.id, gal.clusterid, gal.host AS host, COUNT(*)
			FROM gridalarms_alarm_log_items AS gal
			INNER JOIN gridalarms_alarm AS ga
			ON ga.id = gal.alarm_id
			WHERE ga.id = ?
			AND gal.host != ''
			AND gal.jobid = 0
			AND ga.alarm_alert > 0
			AND gal.type & 2 > 0
			AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
			GROUP BY clusterid,host", array($id));

		$newcols = array(
			'host' => array(
				'display_name' => __('Host', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
			'clustername' => array(
				'display_name' => __('Cluster Name', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
			'jobid' => array(
				'display_name' => __('JobID', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
			'indexid' => array(
				'display_name' => __('Job Index', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
			'submit_time' => array(
				'display_name' => __('Submit Time', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
			'user' => array(
				'display_name' => __('Job User', 'gridalarms'),
				'type'         => 'string',
				'align'        => 'left',
				'units'        => '',
				'digits'       => '0',
				'autoscale'    => '1'
			),
		);

		$job_items = array();

		if (cacti_sizeof($cluster_hosts)) {
			foreach ($cluster_hosts as $ch) {
				$job_items += db_fetch_assoc_prepared("SELECT '" . $ch['id'] . "' AS id, gj.clusterid, gj.exec_host AS host,
					gc.clustername AS clustername, gj.jobid, gj.indexid, gj.submit_time, gj.user AS job_user, gj.mailuser
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs AS gj
					ON gj.exec_host = gal.host
					AND gj.clusterid = gal.clusterid
					LEFT JOIN grid_jobs_jobhosts AS gjh
					ON gj.clusterid = gjh.clusterid
					AND gj.jobid = gjh.jobid
					AND gj.indexid = gjh.indexid
					AND gj.submit_time = gjh.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id=gal.alarm_id
					INNER JOIN grid_clusters AS gc
					ON gc.clusterid = gj.clusterid
					WHERE ga.id = ?
					AND gjh.jobid IS NULL
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND gj.exec_host = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))
					UNION
					SELECT '" . $ch['id'] . "' AS id, gj.clusterid, gjh.exec_host AS host, gc.clustername AS clustername,
					gj.jobid, gj.indexid, gj.submit_time, gj.user AS job_user, gj.mailuser
					FROM gridalarms_alarm_log_items AS gal
					INNER JOIN grid_jobs_jobhosts AS gjh
					ON gal.clusterid = gjh.clusterid
					AND gal.host = gjh.exec_host
					INNER JOIN grid_jobs AS gj
					ON gj.clusterid = gjh.clusterid
					AND gj.jobid = gjh.jobid
					AND gj.indexid = gjh.indexid
					AND gj.submit_time = gjh.submit_time
					INNER JOIN gridalarms_alarm AS ga
					ON ga.id = gal.alarm_id
					INNER JOIN grid_clusters AS gc
					ON gc.clusterid = gj.clusterid
					WHERE ga.id = ?
					AND ga.alarm_alert > 0
					AND gal.type & 2 > 0
					AND gj.clusterid = ?
					AND gjh.exec_host = ?
					AND (gal.last_reported = '0000-00-00' OR (ga.repeat_alert > 0 AND gal.last_reported < NOW()))",
					array(
						$id, $ch['clusterid'], $ch['host'],
						$id, $ch['clusterid'], $ch['host']
					)
				);
			}

			if (cacti_sizeof($job_items)) {
				// build a list for each user
				foreach ($job_items as $ji) {
					$new_job_items[$ji['job_user']][] = $ji;
				}

				// send emails per cluster and user.
				foreach ($new_job_items as $job_items) {
					// modify the Alert email-body to indicated that the users jobs were impacted
					$alarm['email_body'] = 'A Host based Alert was triggered that impacts your job(s) below.  ' .
						'The body of that Alert Email is also included along with a list of your jobs impacted ' .
						'by that Alert for your reference.<br><br>' . $alarm['email_body'];

					$gal_id_array = do_items($subject, $alarm, $currentval, $job_items, $newcols, false, $sent_emails);

					if (cacti_sizeof($gal_id_array)) {
						foreach ($gal_id_array as $gal_id) {
							$total_gal_id_array[$gal_id] = $gal_id;
						}
					}
				}
			}
		}
	}

	if (count($total_gal_id_array)) {
		//cacti_log('DEBUG: total_gal_id_array=' . implode (',', $total_gal_id_array));

		db_execute('UPDATE gridalarms_alarm_log_items
			SET last_reported = NOW()
			WHERE id in (' . implode (',', $total_gal_id_array) . ')');
	}
}

function do_items(string $subject, array $alarm, mixed $currentval, mixed $job_items, mixed $columns, bool $if_admin, mixed $sent_emails) : bool|array {
	global $config, $cnn_id;

	if (empty($job_items)) {
		return false;
	}

	/* use thold's base url */
	$httpurl = read_config_option('base_url');

	$display_text = array();

	foreach ($columns as $column_name => $data) {
		$display_text[] = array(
			'display' => $data['display_name'],
			'align'   => $data['align']
		);
	}

	ob_start();

	$i = 0;

	/* build new header */
	html_start_box(__('First %s Alert Breaching Items', read_config_option('gridalarm_alert_limit')), '100%', '', '3', 'center', '');

	html_header($display_text);

	foreach ($job_items as $item) {
		form_alternate_row();

		$j = 1;
		foreach ($columns as $column_name => $data) {
			if ($data['align'] == '') {
				$data['align'] = 'left';
			}

			$class = $data['align'];

			switch(strtolower($column_name)) {
			case 'user':
			case 'username':
				if (isset($item['clusterid'])) {
					print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_busers.php?query=1&clusterid=' .
						$item['clusterid'] . '&filter=' . $item['job_user']) . "'>" . $item['job_user'] . "</a></td>";
				} else {
					print "<td class='$class nowrap'>" . (isset($item['job_user']) ? $item['job_user']:__('Not Found', 'gridalarms')) . "</td>";
				}

				break;
			case 'jobid':
				if (isset($item['indexid']) && isset($item['submit_time']) && isset($item['clusterid'])) {
					print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_bjobs.php?query=1&action=viewjob&clusterid=' .
						$item['clusterid'] . '&indexid=' . $item['indexid'] . '&jobid=' . $item['jobid'] . '&submit_time=' .
						strtotime($item['submit_time']) . "' title='". $item['jobid']) . "'>" .	$item['jobid'] . "</a></td>";
				} else {
					print "<td class='$class nowrap'>" . (isset($item['jobid']) ? $item['jobid']:__('Not Found', 'gridalarms')) . "</td>";
				}

				break;
			case 'queue':
			case 'queuename':
				$queue = $item['column' . substr('0' . $j, -2)];

				if (isset($item['clusterid'])) {
					print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_bqueues.php?action=&clusterid=' .
						$item['clusterid'] . '&filter=' . $queue) . "'>$queue</a></td>";
				} else {
					print "<td class='$class nowrap'>$queue</td>";
				}

				break;
			case 'exec_host':
			case 'hostname':
			case 'host':
				if (isset($item['column' . substr('0' . $j, -2)])) {
					$host = ($item['column' . substr('0' . $j, -2)] != 'NULL' ? $item['column' . substr('0' . $j, -2)]:__('Not Found', 'gridalarms'));
				} elseif (isset($item['hostname'])) {
					$host = $item['hostname'];
				} elseif (isset($item['exec_host'])) {
					$host = $item['exec_host'];
				} elseif (isset($item['host'])) {
					$host = $item['host'];
				}

				if (isset($item['clusterid'])) {
					print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_bzen.php?action=zoom&clusterid=' .
					$item['clusterid'] . '&exec_host=' . $host) . "'>" . grid_strip_domain($host) . "</a></td>";
				} else {
					print "<td class='$class nowrap'>" . grid_strip_domain($host) . "</td>";
				}

				break;
			default:
				if (isset($item['column' . substr('0' . $j, -2)])) {
					print "<td class='$class nowrap'>" . $item['column' . substr('0' . $j, -2)] . "</td>";
				} elseif (isset($item[$column_name])) {
					print "<td class='$class nowrap'>" . html_escape($item[$column_name]) . "</td>";
				} else {
					print "<td class='$class nowrap'>" . __('Not Found', 'gridalarms') . "</td>";
				}
				break;
			}

			$j++;
		}

		form_end_row();
	}

	html_end_box(false);

	//get the new alert message content
	$email_content = ob_get_clean();
	$new_msg       = gridalarms_text_replace($alarm['email_body'], $currentval, $alarm, $email_content);

	/* obtain user emails, index is clusterid_user */
	$email_addresses = get_user_emails($job_items, $if_admin, $sent_emails);
	$email_list      = implode (',', $email_addresses);

	gridalarms_debug("email_addresses = '" . $email_list ."'");
	//gridalarms_debug(str_replace("\n","",$new_msg));

	if (trim($email_list) != '') {
		alarm_mail($email_list, '', $subject, $new_msg, $alarm['format_file']);
	}

	$gal_id_array = array();

	foreach ($job_items as $item) {
		$gal_id_array[$item['id']] = $item['id'];
	}

	return $gal_id_array;
}

function get_user_emails(array $ajobs, bool $if_admin, string $sent_emails = '') : array {
	$emails        = array();
	$email_domains = array();
	$method        = read_config_option('gridalarm_user_map');

	if (cacti_sizeof($ajobs)) {
		foreach ($ajobs as $j) {
			if ($if_admin) {
				if (!isset($emails[$j['clusterid']])) {
					$emails_from_DB_tmp = db_fetch_cell_prepared('SELECT email_admin
						FROM grid_clusters
						WHERE clusterid = ?',
						array($j['clusterid']));

					$emails[$j['clusterid']] = trim($emails_from_DB_tmp);
				}
			} elseif (!isset($emails[$j['clusterid'] . '_' . $j['job_user']])) {
				if ($j['mailuser'] != '') {
					$emails[$j['clusterid'] . '_' . $j['job_user']] = trim($j['mailuser']);
				} else {
					/* calculate based on dropown and metadata settings */
					switch ($method) {
					case '0':					//Mail Exec User
						$emails[$j['clusterid'] . '_' . $j['job_user']]  = trim($j['job_user']);

						break;
					case '1':					//Mail to Exec User@Cluster Domain
						if (!isset($email_domains[$j['clusterid']])) {
							$email_domains[$j['clusterid']] = db_fetch_cell_prepared('SELECT email_domain
								FROM grid_clusters
								WHERE clusterid = ?',
								array($j['clusterid']));
						}

						if ($email_domains[$j['clusterid']] == '') {
							$emails[$j['clusterid'] . '_' . $j['job_user']] = '';
						} else {
							$emails[$j['clusterid'] . '_' . $j['job_user']] = trim($j['job_user'] . '@' . $email_domains[$j['clusterid']]);
						}

						break;
					case '2':					//Mail to MetaData Address
						$column_name = read_config_option('gridalarm_metadata_user_email_map');
						if (isset($column_name)) {
							$email_meta = db_fetch_cell_prepared('SELECT ' . $column_name . '
								FROM grid_metadata
								WHERE object_id = ?
								AND object_type="user"
								AND cluster_id IN (0, ?)',
								array($j['job_user'], $j['clusterid']));

							if (!empty($email_meta)) {
								$emails[$j['clusterid'] . '_' . $j['job_user']] = trim($email_meta);
							}
						}

						break;
					}
				}
			}
		}
	}

	/* remove duplicate emails from sent emails */
	if (!empty($sent_emails)) {
		$sent_emails_array = explode(',', $sent_emails);

		foreach ($emails as $new_email_key => $new_email_value) {
			if (array_search(strtolower($new_email_value), array_map('strtolower', $sent_emails_array)) !== false) {
				unset($emails[$new_email_key]);
			}
		}
	}

	return $emails;
}

/**
 * function to check all alarms
 *
 * @return int|bool total alarms checked
 */
function gridalarms_check_all_alarms() : int|bool {
	global $config;

	// Do not proceed if we have chosen to globally disable all alerts
	if (read_config_option('thold_disable_all') == 'on') {
		gridalarms_debug('Grid Alert checking is disabled globally');

		return false;
	}

	$alarms = db_fetch_assoc('SELECT *
		FROM gridalarms_alarm
		WHERE alarm_enabled="on"
		AND frequency > 0');

	$total_alarms = cacti_sizeof($alarms);

	gridalarms_debug("START  - Processing $total_alarms Alert");

	api_plugin_hook_function('gridalarms_reset_hostsalarm');

	if (cacti_sizeof($alarms)) {
		foreach ($alarms as $alarm) {
			gridalarms_check_alarm($alarm);
		}
	}

	api_plugin_hook_function('gridalarms_delete_hostsalarm');
	gridalarms_debug("FINISH - Processed $total_alarms Alert");

	return $total_alarms;
}

/**
 * sets the pollers environment with gridalarm environment variables prior to script launch
 *
 * @param  mixed $currentval the alarms current value
 * @param  array $alarm the alarm to be used to construct the environment
 *
 * @return void
 */
function gridalarms_set_execenv(mixed $currentval, array $alarm) : void {
	/* the current values */
	putenv('GALERT_NAME=' . $alarm['name']);
	putenv('GALERT_ID='   . $alarm['id']);

	/* hi/low messaging */
	$highvalue   = $alarm['alarm_type'] == 0 ? $alarm['alarm_hi'] : ($alarm['alarm_type'] == 1 ? $alarm['time_hi'] : '');
	$lowvalue    = $alarm['alarm_type'] == 0 ? $alarm['alarm_low'] : ($alarm['alarm_type'] == 1 ? $alarm['time_low'] : '');

	putenv('GALERT_HI='        . ($highvalue == '' ? __('N/A', 'gridalarms') : $highvalue));
	putenv('GALERT_LOW='       . ($lowvalue == '' ? __('N/A', 'gridalarms') : $lowvalue));
	putenv('GALERT_VALUE='     . $currentval);
	putenv('GALERT_CLUSTERID=' . $alarm['clusterid']);

	/* detail breach items */
	putenv('GALERT_ITEMS_HEADER=' . get_alarm_breach_details_headers_for_script($alarm));
	putenv('GALERT_ITEMS_LIST='   . get_alarm_breach_details_for_script($alarm));
	putenv('GALERT_ITEMS_COLUMN=' . get_alarm_breach_details_columns_for_script($alarm));
}

/**
 * the main function that spawns the various alerts
 *
 * @return void
 */
function gridalarms_alarm_poller() : void {
	/* record the start time */
	$start = microtime(true);

	/* perform all thold checks */
	gridalarms_debug('START  - Alert Checking');
	$alarms = gridalarms_check_all_alarms();
	gridalarms_debug('FINISH - Alert Checking');

	/* record the end time */
	$end = microtime(true);

	/* log statistics */
	$gridalarms_alarm_stats = sprintf('Time:%01.4f Alerts:%s', $end - $start, $alarms);

	cacti_log('GRIDALERTS STATS: ' . $gridalarms_alarm_stats, false, 'SYSTEM');

	set_config_option('stats_gridalarms_alarm', $gridalarms_alarm_stats);
}

function alarm_cacti_log(string $string) : void {
	global $config;

	$environ = 'ALERT';
	/* fill in the current date for printing in the log */
	$date    = date('m/d/Y h:i:s A');

	/* determine how to log data */
	$logdestination = read_config_option('log_destination');
	$logfile        = read_config_option('path_cactilog');

	/* format the message */
	$message = "$date - " . $environ . ": " . $string . "\n";

	/* Log to Logfile */
	if ((($logdestination == 1) || ($logdestination == 2)) && (read_config_option('log_verbosity') != POLLER_VERBOSITY_NONE)) {
		if ($logfile == '') {
			$logfile = $config['base_path'] . '/log/cacti.log';
		}

		/* print the data to the log (append) */
		$fp = @fopen($logfile, 'a');

		if ($fp) {
			@fwrite($fp, $message);
			fclose($fp);
		}
	}

	/* Log to Syslog/Eventlog */
	/* Syslog is currently Unstable in Win32 */
	if (($logdestination == 2) || ($logdestination == 3)) {
		$string   = strip_tags($string);
		$log_type = '';

		if (substr($string,0,6) =='ERROR:') {
			$log_type = 'err';
		} elseif (substr($string,0,8) == 'WARNING:') {
			$log_type = 'warn';
		} elseif (substr($string,0,6)=='STATS:') {
			$log_type = 'stat';
		} elseif (substr($string,0,7)=='NOTICE:') {
			$log_type = 'note';
		}

		if (strlen($log_type)) {
			if (function_exists('define_syslog_variables')) define_syslog_variables();

			if ($config['cacti_server_os'] == 'win32') {
				openlog('Cacti', LOG_NDELAY | LOG_PID, LOG_USER);
			} else {
				openlog('Cacti', LOG_NDELAY | LOG_PID, LOG_SYSLOG);
			}

			if (($log_type == 'err') && (read_config_option('log_perror'))) {
				syslog(LOG_CRIT, $environ . ': ' . $string);
			}

			if (($log_type == 'warn') && (read_config_option('log_pwarn'))) {
				syslog(LOG_WARNING, $environ . ': ' . $string);
			}

			if ((($log_type == 'stat') || ($log_type == 'note')) && (read_config_option('log_pstats'))) {
				syslog(LOG_INFO, $environ . ': ' . $string);
			}

			closelog();
		}
	}
}

/**
 * logs events for the alarm for later reference by the operator
 *
 * @param array $save the cacti specific save array for the insert
 *
 * @return array
 */
function alarm_log(array $save) : array {
	$save['id'] = 0;

	if (read_config_option('thold_log_cacti') == 'on') {
		$alarm = db_fetch_row_prepared('SELECT *
			FROM gridalarms_alarm
			WHERE id = ?',
			array($save['alarm_id']));

		if ($save['status'] == 0) {
			$desc = 'Alert Restored  ID: ' . $save['alarm_id'];
		} else {
			$desc = 'Alert Breached  ID: ' . $save['alarm_id'];
		}

		$types = array (
			0 => 'High/Low Values',
			2 => 'Time Based',
		);

		$desc .= '  Type: ' . $types[$alarm['alarm_type']];
		$desc .= '  Enabled: ' . $alarm['alarm_enabled'];

		switch ($alarm['alarm_type']) {
			case 0:
				$desc .= '  Current: ' . $save['current'];
				$desc .= '  High: ' . $alarm['alarm_hi'];
				$desc .= '  Low: ' . $alarm['alarm_low'];
				$desc .= '  Trigger: ' . plugin_alarm_duration_convert($alarm['alarm_fail_trigger'], 'alert');

				break;
			case 1:
				$desc .= '  Current: ' . $save['current'];

				break;
			case 2:
				$desc .= '  Current: ' . $save['current'];
				$desc .= '  High: ' . $alarm['time_hi'];
				$desc .= '  Low: ' . $alarm['time_low'];
				$desc .= '  Trigger: ' . $alarm['time_fail_trigger'];
				$desc .= '  Time: ' . plugin_alarm_duration_convert($alarm['time_fail_length'], 'time');

				break;
		}

		$desc .= '  SentTo: ' . $save['emails'];

		if ($save['status'] != 1) {
			alarm_cacti_log($desc);
		}
	}

	unset($save['emails']);

	if (isset($save['details'])) {
		$save['details'] = base64url_encode($save['details']);
	}

	$id = sql_save($save, 'gridalarms_alarm_log');

	api_plugin_hook_function('gridalarms_action', $save);

	return $save;
}

/**
 * logs acknowledgement messages from alarms that have been acknowledged
 *
 * @param int    $id the id of the alarm to be acknowledged
 * @param string $desc the message that was entered by the admin
 *
 * @return void
 */
function alarm_ack_logging(int $id, string $desc, bool $email = false) : void {
	$alarm = get_alarm_by_id($id);

	if (isset($_SESSION['sess_user_id'])) {
		$user = db_fetch_cell_prepared("SELECT CONCAT('', full_name, ' (', username,')')
			FROM user_auth
			WHERE id = ?",
			array($_SESSION['sess_user_id']));
	} else {
		$user = 'system';
	}

	$syslog_level = $alarm['syslog_priority'];
	if (!isset($syslog_level)) {
		$syslog_level = read_config_option('gridalarm_severity');
	} elseif (isset($syslog_level) && ($syslog_level > 7 || $syslog_level < 0)) {
		$syslog_level = read_config_option('gridalarm_severity');
	}

	$comments = "Alert '" . $alarm['name'] . "', Acknowledged Comments:'$desc', By User:'$user'";
	$desc     = "Acknowledged Comments:'$desc', By User:'$user'";

	if ($alarm['syslog_enabled']) {
		openlog('RTM-GridAlert-Log', LOG_PID | LOG_PERROR, $alarm['syslog_facility']);

		syslog($syslog_level, $comments);
	}

	alarm_log(array(
		'type'        => $alarm['alarm_type'],
		'time'        => time(),
		'alarm_id'    => $id,
		'alarm_value' => '',
		'current'     => $alarm['lastread'],
		'status'      => 99,
		'description' => $desc,
		'emails'      => '')
	);

	if ($email) {
		/* building Email list */
	    $alarm_emails = gridalarms_build_email_list($alarm);

		$subject  = "ALERT ACK: '" . $alarm['name'] . "' Has Been Acknowledged, By $user";
		$message  = "<tr><td>Alert '" . $alarm['name'] . "' Hast Been Acknowledged at '" . date('Y-m-d H:i:s') . "'</td></tr>";
		$message .= '<tr><td>' . $desc . '</td></tr>';

		if (trim($alarm_emails) != '') {
			alarm_mail($alarm_emails, '', $subject, $message, $alarm['format_file']);
		}
	}

	cacti_log($comments, false, 'GRIDALERTS');

	raise_message('alarm_acknowledged');
}

/**
 * get the current threshold value for a script based data source
 *
 * @param  array $alarm the alarm to run
 *
 * @return mixed the return value
 */
function get_current_value_by_script(array $alarm) : mixed {
	$expression = get_expression_by_id($alarm['expression_id']);

	if (cacti_sizeof($expression)) {
		$script_thold = gridalarms_replace_custom_input($expression['id'], $expression['script_thold']);

		$script_thold = do_script_variable_replacement($script_thold, $alarm);

		$value = trim(exec_poll($script_thold));
	}

	if (trim($value) == '') {
		$value = '0';
	}

	if (is_numeric($value)) {
		return $value;
	} else {
		return 'NaN';
	}
}

/**
 * get the current threshold value for an expression based data source
 *
 * @param  array $alarm the alarm to run
 *
 * @return mixed the return value
 */
function get_current_value_by_expression(array $alarm) : mixed {
	global $aggregation;

	$expression     = get_expression_by_id($alarm['expression_id']);

	if (cacti_sizeof($expression)) {
		$primary_key_array = get_primary_key_from_table($expression['db_table']);

		$sql_from_where_extra = '';
		/* append clusterid to where clause if found in table primary keys */
		if ($alarm['clusterid'] != 0 && ($key_found = array_search('clusterid', $primary_key_array)) !== false) {
			$sql_from_where_extra = 'clusterid = ' . $alarm['clusterid'];
		}
	}
	$sql_from_where = build_expression_string_for_sql_from_where($alarm['expression_id'], $sql_from_where_extra);

	$sql_from_where = do_script_variable_replacement($sql_from_where, $alarm);

	if (trim($sql_from_where) == '') {
		return '0';
	}

	/* if an expression has not been assigned, don't run */
	if (cacti_sizeof($expression)) {
		$sql_select = 'SELECT ' . $aggregation[$alarm['aggregation']] . '(' . $alarm['metric'] . ') ';

		$sql = $sql_select . $sql_from_where;

		$value = db_fetch_cell($sql);

		if (trim($value) == '') {
			$value = '0';
		}

		if (is_numeric($value)) {
			return $value;
		} else {
			return 'NaN';
		}
	} else {
		return false;
	}
}

/**
 * get the current threshold value for an sql query based data source
 *
 * @param  array $alarm the alarm to run
 *
 * @return mixed the return value
 */
function get_current_value_by_sql(array $alarm) : mixed {
	global $aggregation;

	$expression = get_expression_by_id($alarm['expression_id']);

	/* if an expression has not been assigned, don't run */
	if (cacti_sizeof($expression)) {
		$columns = get_columns_from_sql($expression['sql_query'], 'alarm', $alarm['expression_id']);

		/* append clusterid to where clause if found in query sql */
		if ($alarm['clusterid'] != 0 && ($key_found = array_search('clusterid', $columns)) !== false) {
			$sql_from_where = ' WHERE clusterid=' . $alarm['clusterid'];
		} else {
			$sql_from_where = '';
		}

		$sql_query = gridalarms_replace_custom_input($expression['id'], $expression['sql_query']);
		$sql_query = do_script_variable_replacement($sql_query, $alarm);

		$sql_select = 'SELECT ' . $aggregation[$alarm['aggregation']] . '(' . $alarm['metric'] . ') FROM (' . $sql_query . ') AS query';

		$sql = $sql_select . $sql_from_where;

		$value = db_fetch_cell($sql);

		if (trim($value) == '') {
			$value = '0';
		}

		if (is_numeric($value)) {
			return $value;
		} else {
			return 'NaN';
		}
	} else {
		return false;
	}
}

/**
 * get the name of the cluster for a specific clusterid
 *
 * @param  int $clusterid the clusterid to check
 *
 * @return bool|string the name of the cluster
 */
function get_clustername(int $clusterid) : bool|string {
	return db_fetch_cell_prepared('SELECT clustername
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));
}

/**
 * get an associative array of clusterid, clustername combinations
 *
 * @return mixed all clusters names and ids
 */
function get_clusters() : bool|array {
	return db_fetch_assoc('SELECT clusterid, clustername
		FROM grid_clusters');
}

/**
 * create an array of dropdown compatible clusterid/name for Cacti dropdown function
 *
 * @return array of formatted array for cacti api
 */
function get_clusters_for_form_dropdown() : array {
	$clusters     = get_clusters();
	$cluster_list = array('0' => __('N/A', 'gridalarms'));

	if (cacti_sizeof($clusters)) {
		foreach ($clusters as $cluster) {
			$cluster_list[$cluster['clusterid']] = $cluster['clustername'];
		}
	}

	return $cluster_list;
}

/**
 * function to print debug messages to console when running cli's
 *
 * @param  string $message string message with or without trailing line breaks
 *
 * @return void
 */
function gridalarms_debug(string $message) : void {
	global $debug;

	if (defined('CACTI_DATE_TIME_FORMAT')) {
		$date = date(CACTI_DATE_TIME_FORMAT);
	} else {
		$date = date('Y-m-d H:i:s');
	}

	if ($debug) {
		print $date . ' - DEBUG: ' . trim($message) . PHP_EOL;
	}
}

/**
 * builds a SQL statment from the expression syntax created for the alert
 *
 * @param int    $expression_id the expression to build syntax for
 @ @param string $where_extra any extra SQL where to use
 *
 * @return mixed the sql query
 */
function build_expression_string_for_sql_from_where(int $expression_id, string $where_extra = '') : string {
	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_expression_item
		WHERE expression_id = ?
		ORDER BY sequence',
		array($expression_id));

	$expression = get_expression_by_id($expression_id);

	$i = 0;

	$gridalarms_expression_string = ' FROM ' . $expression['db_table'];

	if (strlen($where_extra) > 0 || cacti_sizeof($items) > 0) {
		$gridalarms_expression_string .= ' WHERE ';
	}

	if (strlen($where_extra) > 0 && cacti_sizeof($items) > 0) {
		$where_extra .= ' AND ';
	}

	$gridalarms_expression_string .= $where_extra;

	if (cacti_sizeof($items)) {
		foreach ($items as $item) {
			if ($i > 0) {
				$gridalarms_expression_string .= ' ';
			}

			/* metric type */
			if ($item['type'] == 3) {
				$metric_name = db_fetch_row_prepared('SELECT db_table, db_column
					FROM gridalarms_metric
					WHERE id = ?',
					array($item['value']));

				$gridalarms_expression_string .= $metric_name['db_column'];
			} elseif ($item['type'] == 5) {
				/* string value append quotes */
				$gridalarms_expression_string .= "'" . trim($item['value'], '"\'') . "'";
			} elseif ($item['type'] == 7) {
				/* string value append quotes */
				if (($item['value'] == 'clusterid') ||($item['value'] == 'clustername')) {
					$gridalarms_expression_string .= '|alert_' . $item['value'] . '|';
				} else {
					$value = db_fetch_cell_prepared('SELECT value
						FROM gridalarms_expression_input
						WHERE expression_id = ?
						AND name = ?',
						array($expression_id, $item['value']));

					if (isset($value)) { //'0' is valid input here
						$gridalarms_expression_string .= "'" . $value ."'";
					} else {
						$gridalarms_expression_string .= "''";
					}
				}
			} else {
				$gridalarms_expression_string .= $item['value'];
			}
			$i++;
		}

		return $gridalarms_expression_string;
	} else {
		return $gridalarms_expression_string;
	}
}

/**
 * builds a SQL statment from the expression syntax created for the template
 *
 * @param int     $expression_id the expression to build syntax for
 @ @param string $where_extra any extra SQL where to use
 *
 * @return string the formatted sql statment
 */
function build_template_expression_string_for_sql_from_where(int $expression_id, string $where_extra = '') : string {
	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?
		ORDER BY sequence',
		array($expression_id));

	$expression = get_template_expression_by_id($expression_id);

	$i = 0;

	$gridalarms_expression_string = ' FROM ' . $expression['db_table'];

	if (strlen($where_extra) > 0 || cacti_sizeof($items) > 0) {
		$gridalarms_expression_string .= ' WHERE ';
	}

	if (strlen($where_extra) > 0 && cacti_sizeof($items) > 0) {
		$where_extra .= ' AND ';
	}

	$gridalarms_expression_string .= $where_extra;

	if (cacti_sizeof($items)) {
		foreach ($items as $item) {
			if ($i > 0) {
				$gridalarms_expression_string .= ' ';
			}

			/* metric type */
			if ($item['type'] == 3) {
				$metric_name = db_fetch_row_prepared('SELECT db_table, db_column
					FROM gridalarms_template_metric
					WHERE id = ?',
					array($item['value']));
				$gridalarms_expression_string .= $metric_name['db_column'];
			} elseif ($item['type'] == 5) {
				/* string value append quotes */
				$gridalarms_expression_string .= "'" . trim($item['value'], '"\'') . "'";
			} elseif ($item['type'] == 7) {
				/* string value append quotes */
				if (($item['value'] == 'clusterid') ||($item['value'] == 'clustername')) {
					$gridalarms_expression_string .= '|alert_' . $item['value'] . "|";
				} else {
					$value = db_fetch_cell_prepared('SELECT value
						FROM gridalarms_template_expression_input
						WHERE expression_id = ?
						AND name = ?',
						array($expression_id, $item['value']));

					if (isset($value)) { //'0' is valid input here
						$gridalarms_expression_string .= "'" . $value . "'";
					} else {
						$gridalarms_expression_string .= "''";
					}
				}
			} else {
				$gridalarms_expression_string .= $item['value'];
			}
			$i++;
		}

		return $gridalarms_expression_string;
	} else {
		return $gridalarms_expression_string;
	}
}

/**
 * build the expression string for viewing in alert data source page
 *
 * @param int     $expression_id the expression to build syntax for
 *
 * @return string of formatted expression
 */
function get_gridalarms_expression_string(int $expression_id) : string {
	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_expression_item
		WHERE expression_id = ?
		ORDER BY sequence',
		array($expression_id));

	$gridalarms_expression_string = '';

	$i = 0;

	if (cacti_sizeof($items)) {
		foreach ($items as $item) {
			if ($i > 0) {
				$gridalarms_expression_string .= ' ';
			}

			/* string value append quotes */
			if ($item['type'] == 5) {
				$gridalarms_expression_string .= "'" . trim($item['value'], '"\'') . "'";
			} elseif ($item['type'] == 3) {
				$metric = get_metric($item['value']);
				$gridalarms_expression_string .= $metric['name'];
			} elseif ($item['type'] == 7) {
				switch($item['value']) {
					case 'clusterid':
					case 'clustername':
						$gridalarms_expression_string .= '|alert_' . $item['value'] . "|";

						break;
					default:
						$gridalarms_expression_string .= '|input_' . $item['value'] . "|";

						break;
				}
			} else {
				$gridalarms_expression_string .= $item['value'];
			}

			$i++;
		}
	}

	return $gridalarms_expression_string;
}

/**
 * build the expression string for viewing in template data source page
 *
 * @param  int    $expression_id the expression to build syntax for
 *
 * @return string of formatted expression
 */
function get_gridalarms_template_expression_string(int $expression_id) : string {
	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?
		ORDER BY sequence',
		array($expression_id));

	$gridalarms_expression_string = '';

	$i = 0;

	if (cacti_sizeof($items)) {
		foreach ($items as $item) {
			if ($i > 0) {
				$gridalarms_expression_string .= ' ';
			}

			/* string value append quotes */
			if ($item['type'] == 5) {
				$gridalarms_expression_string .= "'" . trim($item['value'], '"\'') . "'";

				cacti_log("DEBUG: Value '" . $item['value'] . "', Final '" . $gridalarms_expression_string . "'", false, 'GRIDALERTS', POLLER_VERBOSITY_DEBUG);
			} elseif ($item['type'] == 3) {
				$metric = get_template_metric($item['value']);
				$gridalarms_expression_string .= $metric['name'];

				cacti_log("DEBUG: PValue '" . $item['value'] . "', Final '" . $gridalarms_expression_string . "'", false, 'GRIDALERTS', POLLER_VERBOSITY_DEBUG);
			} elseif ($item['type'] == 7) {
				switch($item['value']) {
					case 'clusterid':
					case 'clustername':
						$gridalarms_expression_string .= '|alert_' . $item['value'] . "|";

						break;
					default:
						$gridalarms_expression_string .= '|input_' . $item['value'] . "|";

						break;
				}
			} else {
				$gridalarms_expression_string .= $item['value'];

				cacti_log("DEBUG: EValue '" . $item['value'] . "', Final '" . $gridalarms_expression_string . "'", false, 'GRIDALERTS', POLLER_VERBOSITY_DEBUG);
			}

			cacti_log("DEBUG: gridalarms_expression_string: $gridalarms_expression_string", false, 'GRIDALERTS', POLLER_VERBOSITY_DEBUG);

			$i++;
		}
	}

	return $gridalarms_expression_string;
}

/**
 * get all metrics from grid_template_metrics where the metric belongs to a particular table
 *
 * @param  string $table the table to operate on
 *
 * @return array of columns
 */
function get_gridalarms_template_metrics(string $table) : array {
	$metrics = db_fetch_assoc_prepared('SELECT id, name
		FROM gridalarms_template_metric
		WHERE db_table = ?',
		array($table));

	$metrics_array = array();

	if (cacti_sizeof($metrics)) {
		foreach ($metrics as $metric) {
			$metrics_array[$metric['id']] = $metric['name'];
		}
	}

	return $metrics_array;
}

/**
 * get all column names for a templates expression
 *
 * @param  int $expression_id the table to operate on
 *
 * @return array of columns
 */
function get_template_expression_db_columns(int $expression_id) : array {
	$metric_ids = db_fetch_assoc_prepared('SELECT value
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?
		AND type=3',
		array($expression_id));

	$metrics_array = array();

	if (cacti_sizeof($metric_ids)) {
		foreach ($metric_ids as $metric_id) {
			$db_column = db_fetch_cell_prepared('SELECT db_column
				FROM gridalarms_template_metric
				WHERE id = ?',
				array($metric_id['value']));

			$metrics_array[$db_column] = $db_column;
		}
	}

	return $metrics_array;
}

/**
 * Get expression item from grid_template_expression_item based on id.
 *
 * @param  int   $expression_id The expression id
 *
 * @return bool|array Row of expression items or false
 */
function get_template_expression_item(int $expression_id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE id = ?',
		array($expression_id));
}

function get_template_metrics_from_expression_by_id(int $expression_id) : bool|array {
	$expression = get_template_expression_by_id($expression_id);

	if ($expression['ds_type'] == '0') {
		return get_table_columns($expression['db_table']);
	} elseif ($expression['ds_type'] == '1') {
		return get_columns_from_sql($expression['sql_query'], 'template', $expression_id);
	} else {
		return false;
	}
}

function get_template_expressions() : bool|array {
	return db_fetch_assoc('SELECT * FROM gridalarms_template_expression');
}

/**
 * Get expression from grid_template_expression based on id
 *
 * @param  int  $id
 *
 * @return array Row of expressions or false
 */
function get_template_expression_by_id(int $expression_id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_template_expression
		WHERE id = ?',
		array($expression_id));
}

/**
 * Get expression from grid_template_expression_by_alarm_id based on an alarm id
 *
 * @param  int $alarm_id
 *
 * @return array - An array of a template definition
 */
function get_template_expression_by_alarm_id(int $alarm_id) : ?array {
	$alarm = get_template_by_id($alarm_id);

	if (cacti_sizeof($alarm)) {
		return get_template_expression_by_id($alarm['expression_id']);
	}
}

/**
 * Get alarm from gridalarms_template based on id
 *
 * @param Integer $id
 *
 * @return array - Row of an alert template
 */
function get_template_by_id($id) {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_template
		WHERE id = ?',
		array($id));
}

/**
 * Get all the metrics from the gridalarms_metric table
 */
function get_all_template_metrics() {
	$sort_order = '';
	if (isset_request_var('sort_column_array') && cacti_sizeof(get_request_var('sort_column_array')) > 0) {
		$sort_order =  ' ORDER BY ' . gridalarms_build_order_string(get_request_var('sort_column_array'), get_request_var('sort_direction_array'));
	}

	return db_fetch_assoc("SELECT *
		FROM gridalarms_template_metric
		$sort_order");
}

/**
 * Get a metric from the gridalarms_metric table based on id
 *
 * @param  int $id
 *
 * @return array - Row of a template metric
 */
function get_template_metric(int $id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_template_metric
		WHERE id = ?',
		array($id));
}

/**
 * Get all metrics from grid_metrics where the metric belongs to a particular table
 *
 * @param  string $table the table name
 *
 * @return array An array of metrics
 */
function get_gridalarms_metrics(string $table) : array {
	$metrics = db_fetch_assoc_prepared('SELECT id, name
		FROM gridalarms_metric
		WHERE db_table = ?',
		array($table));

	$metrics_array = array();

	if (cacti_sizeof($metrics)) {
		foreach ($metrics as $metric) {
			$metrics_array[$metric['id']] = $metric['name'];
		}
	}

	return $metrics_array;
}

function get_expression_db_columns(int $expression_id) : array {
	$metric_ids = db_fetch_assoc_prepared('SELECT value
		FROM gridalarms_expression_item
		WHERE expression_id = ?
		AND type=3',
		array($expression_id));

	$metrics_array = array();

	foreach ($metric_ids as $metric_id) {
		$db_column = db_fetch_cell_prepared('SELECT db_column
			FROM gridalarms_metric
			WHERE id = ?',
			array($metric_id['value']));

		$metrics_array[$db_column] = $db_column;
	}

	return $metrics_array;
}

/**
 * Get expression item from grid_expression_item based on id.
 *
 * @param Integer $id
 *
 * @return array - An row of expression items
 */
function get_expression_item(int $id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_expression_item
		WHERE id = ?',
		array($id));
}

function get_metrics_from_expression_by_id(int $expression_id) : bool|array{
	$expression = get_expression_by_id($expression_id);

	if ($expression['ds_type'] == '0') {
		return get_table_columns($expression['db_table']);
	} elseif ($expression['ds_type'] == '1') {
		return get_columns_from_sql($expression['sql_query'], 'alarm', $expression_id);
	}
}

function get_expressions() : bool|array {
	return db_fetch_assoc('SELECT * FROM gridalarms_expression');
}

/**
 * Get expression from grid_expression based on id
 *
 * @param  int $expression_id The rexpression to query
 *
 * @return array A row of an expression
 */
function get_expression_by_id(int $expression_id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_expression
		WHERE id = ?',
		array($expression_id));
}

/**
 * Get expression from grid_expression based on an alarm id
 *
 * @param  int $alarm_id the alarm to query
 *
 * @return array A row of an expression
 */
function get_expression_by_alarm_id(int $alarm_id) : bool|array {
	$alarm = get_alarm_by_id($alarm_id);

	if (cacti_sizeof($alarm)) {
		return get_expression_by_id($alarm['expression_id']);
	}
}

/**
 * Get alarm from gridalarms_expression based on id
 *
 * @param  int   $alarm_id
 *
 * @return array A row of an alert
 */
function get_alarm_by_id(int $alarm_id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_alarm
		WHERE id = ?',
		array($alarm_id));
}

/**
 * Get all the metrics from the gridalarms_metric table
 */
function get_all_metrics() : bool|array {
	$sort_order = '';

	if (isset_request_var('sort_column_array') && cacti_sizeof(get_request_var('sort_column_array')) > 0) {
		$sort_order =  ' ORDER BY ' . gridalarms_build_order_string(get_request_var('sort_column_array'), get_request_var('sort_direction_array'));
	}

	return db_fetch_assoc("SELECT *
		FROM gridalarms_metric
		$sort_order");
}

/**
 * Get a metric from the gridalarms_metric table based on id
 *
 * @param  int   $metric_id
 *
 * @return array A metric row
 */
function get_metric(int $metric_id) : bool|array {
	return db_fetch_row_prepared('SELECT *
		FROM gridalarms_metric
		WHERE id = ?',
		array($metric_id));
}

/**
 * Get all the tables in the database
 */
function get_tables_in_db() : array {
	$tables       = array();
	$tables_query = db_fetch_assoc('SHOW TABLES');

	foreach ($tables_query as $table) {
		foreach ($table as $table_name) {
			/* do not include partition table */
			if (substr_count($table_name, '_v') == 0) {
				$tables[$table_name] = $table_name;
			}
		}
	}

	return $tables;
}

/**
 * Get the table columns for the specified table
 *
 * @param  string $table
 *
 * @return array The table columns
 */
function get_table_columns(string $table) : bool|array {
	if (!isset($table) || empty($table) || $table == '') {
		return array();
	}

	$table_columns       = array();
	$table_columns_query = db_fetch_assoc('DESCRIBE ' . $table);

	foreach ($table_columns_query as $column) {
		$table_columns[$column['Field']] = $column['Field'];
	}

	asort($table_columns);

	return $table_columns;
}

/**
 * Get the table columns for the specified table
 *
 * @param  int    $expression_id The expression to search
 * @param  string $table The table to search
 * @param  string $type The type of query
 * @param  int    $metric_id The metric_id to query
 *
 * @return array of table columns
 */
function get_free_expression_table_columns(int $expression_id, string $table, string $type, int $metric_id = 0) : array {
	if (!isset($table) || empty($table) || $table == '') {
		return array();
	}

	$table_columns = array_rekey(
		db_fetch_assoc('DESCRIBE ' . $table),
		'Field', 'Field'
	);

	$sql_where = '';

	if ($type == 'template') {
		if ($metric_id > 0) {
			$sql_where = " AND gtme.metric_id != $metric_id";
		}

		$inuse_columns = array_rekey(
			db_fetch_assoc_prepared('SELECT DISTINCT db_column
				FROM gridalarms_template_metric AS gtm
				INNER JOIN gridalarms_template_metric_expression AS gtme
				ON gtm.id = gtme.metric_id
				WHERE db_table = ?
				AND gtme.expression_id = ?' . $sql_where,
				array($table, $expression_id)),
			'db_column', 'db_column'
		);
	} else {
		if ($metric_id > 0) {
			$sql_where = " AND gme.metric_id != $metric_id";
		}

		$inuse_columns = array_rekey(
			db_fetch_assoc_prepared('SELECT DISTINCT db_column
				FROM gridalarms_metric AS gm
				INNER JOIN gridalarms_metric_expression AS gme
				ON gm.id = gme.metric_id
				WHERE db_table = ?
				AND gme.expression_id = ?' . $sql_where,
				array($table, $expression_id)),
			'db_column', 'db_column'
		);;
	}

	if (cacti_sizeof($inuse_columns)) {
		$table_columns = array_diff($table_columns, $inuse_columns);
	}

	asort($table_columns);

	return $table_columns;
}

function get_primary_key_from_table(string $table) : array {
	if (!isset($table) || empty($table) || $table == '') {
		return array();
	}

	$table_columns = array();
	$table_columns_query = db_fetch_assoc('DESCRIBE ' . $table);

	foreach ($table_columns_query as $column) {
		if ($column['Key'] == 'PRI') {
			$table_columns[$column['Field']] = $column['Field'];
		}
	}

	asort($table_columns);

	return $table_columns;
}

/**
 * Replace last 'WHERE' part
 *
 * 1. If case insensitive string patern 'WHERE+<any characters>+)+<any white spaces>+AS' is found, replace it with 'LIMIT 1) AS'
 * 2. Otherwise cut off the last 'WHERE' part
 *
 * @param  string The base sql query
 *
 * @return string The adjusted query
 */
function replace_where_from_sql(string $sql) : string {
	$last_where_pos = strripos($sql, 'WHERE');

	if (!$last_where_pos) {
		return $sql;
	}

	$sql1 = substr($sql,0, $last_where_pos);

	$sql2 = substr($sql,$last_where_pos);
	$sql2 = str_replace(array("\n","\r"), ' ', $sql2);

	if (preg_match("/WHERE(.*?)\)(\s*)AS/i", $sql2, $matches)) {
		if (!empty($matches[0])) {
			$sql2 = str_replace ($matches[0],' LIMIT 1) AS', $sql2);
		}
	} else {
		$sql2 = ' ';
	}

	return($sql1 . $sql2);
}

function get_columns_from_sql(string $sql, string $type, int $expression_id = 0, bool $force = false) : array {
	global $gl_clusterid, $gl_clustername;

	if ($type == 'alarm') {
		$column_data = db_fetch_cell_prepared('SELECT column_data
			FROM gridalarms_expression
			WHERE id = ?',
			array($expression_id));
	} else {
		$column_data = db_fetch_cell_prepared('SELECT column_data
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($expression_id));
	}

	$sql_columns = array();
	$column_data = trim($column_data);

	if ($column_data != '' && !$force) {
		$columns = json_decode($column_data, true);

		if (cacti_sizeof($columns)) {
			foreach($columns as $column) {
				$sql_columns[$column] = $column;
			}
		}
	}

	if (!sizeof($sql_columns)) {
		$results = array();

		if ($type == 'alarm') {
			$sql = gridalarms_replace_custom_input($expression_id, $sql);
		} elseif ($type == 'template') {
			$sql = gridalarms_template_replace_custom_input($expression_id, $sql);
		}

		$tmp_cluster = db_fetch_row('SELECT clusterid, clustername
			FROM grid_clusters
			LIMIT 1');

		if (!empty($gl_clusterid)) {
			$sql = str_replace('|alert_clusterid|', $gl_clusterid, $sql);
			$sql = str_replace('|alert_clustername|', $gl_clustername, $sql);
		} else {
			if (!empty($tmp_cluster['clusterid'])) {
				$sql = str_replace('|alert_clusterid|', $tmp_cluster['clusterid'], $sql);
				$sql = str_replace('|alert_clustername|', $tmp_cluster['clustername'], $sql);
			}
		}

		if (strlen($sql)) {
			$sql = replace_where_from_sql($sql);
			$sql_columns_query = $sql . ' LIMIT 1';
			$results = db_fetch_assoc($sql_columns_query);
		}

		if (cacti_sizeof($results)) {
			$columns = array_keys($results[0]);

			foreach ($columns as $column) {
				$sql_columns[$column] = $column;
			}
		}

		db_execute_prepared('UPDATE gridalarms_expression
			SET column_data = ?
			WHERE id = ?',
			array(json_encode($sql_columns), $expression_id));
	}

	return $sql_columns;
}

/**
 * Return the list of tables based on the type selected in the form.
 *
 * @param  string $type The class of tables to return
 *
 * @return array The tables by class
 */
function get_db_tables_form(string $type) : array {
	global $metric_types, $license_tables, $cluster_tables, $host_tables, $queue_tables, $user_tables, $job_tables;

	$db_tables = array();

	if (isset($type)) {
		switch($type) {
		case 1:
			$db_tables = $job_tables;
			break;
		case 2:
			$db_tables = $host_tables;
			break;
		case 4:
			$db_tables = $queue_tables;
			break;
		case 8:
			$db_tables = $user_tables;
			break;
		case 16:
			$db_tables = $cluster_tables;
			break;
		case 32:
			$db_tables = $license_tables;
			break;
		case 64:
			$db_tables = get_tables_in_db();
			break;
		default:
			$db_tables = $job_tables;
		}
	}

	return $db_tables;
}

function alarm_log_legend() : void {
	global $alarm_log_bgcolors;

    html_start_box('', '100%', '', '3', 'center', '');

    print '<tr>';
    print '<td class="gridalarmsAlertNotify">'     . __('Alert Notify', 'gridalarms')     . '</td>';
    print '<td class="gridalarmsReTriggerNotify">' . __('ReTrigger Notify', 'gridalarms') . '</td>';
    print '<td class="gridalarmsReTriggerEvent">'  . __('Trigger Event', 'gridalarms')    . '</td>';
    print '<td class="gridalarmsAcknowledgment">'  . __('Acknowledgement', 'gridalarms')  . '</td>';
    print '<td class="gridalarmsRestoralNotify">'  . __('Restoral Notify', 'gridalarms')  . '</td>';
    print '<td class="gridalarmsRestoralEvent">'   . __('Restoral Event', 'gridalarms')   . '</td>';
    print '</tr>';

    html_end_box(false);
}

function alarm_legend() : void {
	html_start_box('', '100%', '', '3', 'center', '');

	print '<tr>';
	print '<td class="gridalarmsAlert">'           . __('Alert', 'gridalarms')                    . '</td>';
	print '<td class="gridalarmsAcknowledgementRequired">' . __('Acknowledgement Required', 'gridalarms') . '</td>';
	print '<td class="gridalarmsNotice">'          . __('Notice', 'gridalarms')                   . '</td>';
	print '<td class="gridalarmsOk">'              . __('Ok', 'gridalarms')                       . '</td>';
	print '<td class="gridalarmsDisabled">'        . __('Disabled', 'gridalarms')                 . '</td>';
	print '</tr>';

	html_end_box(false);
}

function list_alarm_log_detail() : void {
	global $log_types, $log_types_display;

	$logid = get_request_var('logid');

	$details = db_fetch_row_prepared('SELECT *
		FROM gridalarms_alarm_log
		WHERE id = ?',
		array($logid));

	$alarm      = get_alarm_by_id(get_request_var('id'));
	$expression = get_expression_by_id($alarm['expression_id']);

	html_start_box(__('Alert Log Entry Details', 'gridalarms'), '100%', '', '3', 'center', '');

	if (cacti_sizeof($details)) {
		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Name:</b></td><td>" . html_escape($alarm['name']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Alert Trigger Value:</b></td><td>" . html_escape($details['alarm_value']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Measured Value:</b></td><td>" . html_escape($details['current']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Log Time:</b></td><td>" . date("Y-m-d H:i:s", $details['time']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Status:</b></td><td>" . ucfirst($log_types_display[$details['status']]) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Log Message:</b></td><td>". html_escape($details['description']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>External ID:</b></td><td>" . html_escape($alarm['external_id']) . "</td>";
		form_end_row();

		form_alternate_row();
		print "<td style='white-space:nowrap;width:10%;'><b>Operator Notes:</b></td><td>" . $alarm['notes'] . "</td>";
		form_end_row();

		html_end_box();

		print base64url_decode($details['details']);
	} else {
		print '<tr><td>Unable to Find Log Record in Current Database!</td></tr>';
		html_end_box(false);
	}
}

function list_alarm_log(bool $admin = true) : void {
	global $alarm_bgcolors, $config, $gridalarms_platform_cols, $gridalarms_types,
		$alarm_types, $log_types, $log_types_display, $alarm_log_bgcolors, $grid_rows_selector;

	$sql_params = array();

	alarm_log_request_validation();

	if (isset_request_var('clear')) {
		set_request_var('rows', -1);
		set_request_var('alarm_type', -1);
		set_request_var('id', -1);
		set_request_var('status', -1);
		set_request_var('filter', '');
	}

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_where = '';
	if (get_request_var('alarm_type') >= 0) {
		$sql_where = 'WHERE ga.alarm_type = ?';
		$sql_params[] = get_request_var('alarm_type');
	}

	if (get_request_var('id') > 0) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'alarm_id = ?';
		$sql_params[] = get_request_var('id');
	}

	if (get_request_var('status') != -1) {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'status = ?';
		$sql_params[] = get_request_var('status');
	}

	if (get_request_var('filter') != '') {
		$sql_where .= ($sql_where != '' ? ' AND ':'WHERE ') . 'description LIKE ?';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	}

	$sql_order = get_order_string();
	$sql_limit = 'LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$sql = "SELECT gal.*, ga.name
		FROM gridalarms_alarm_log AS gal
		INNER JOIN gridalarms_alarm AS ga
		ON gal.alarm_id=ga.id
		$sql_where
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);

	html_start_box(__('Alert Logs', 'gridalarms'), '100%', '', '3', 'center', '');

	?>
	<tr class='noprint even'>
		<td>
		<form id='listalarm' action='<?php print ($admin ? 'gridalarms_alarm.php?tab=log':'grid_alarmdb.php?tab=log');?>' method=get>
			<script type='text/javascript'>

			function applyFilter() {
				strURL  = '?tab=log&header=false';
				strURL += '&status=' + $('#status').val();
				strURL += '&id=' + $('#id').val();
				strURL += '&alarm_type=' + $('#alarm_type').val();
				strURL += '&rows=' + $('#rows').val();
				strURL += '&filter=' + $('#filter').val();
				loadPageNoHeader(strURL);
			}

			function clearFilter() {
				strURL = '?tab=log&header=false&clear=true'
				loadPageNoHeader(strURL);
			}

			$(function() {
				$('#listalarm').submit(function(event) {
					event.preventDefault();
					applyFilter();
				});

				$('#clear').click(function() {
					clearFilter();
				});
			});

			</script>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridalarms');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Alert', 'gridalarms');?>
					</td>
					<td>
						<select id='id' onChange='applyFilter()'>
							<option value='-1' <?php print (get_request_var('id') == -1 ? 'selected':'');?>><?php print __('All', 'gridalarms');?></option>
							<?php
							if (get_request_var('alarm_type') >= 0) {
								$alarms = array_rekey(
									db_fetch_assoc_prepared('SELECT id, name
										FROM gridalarms_alarm
										WHERE alarm_type= ?
										ORDER BY name',
										array(get_request_var('alarm_type'))),
									'id', 'name'
								);
							} else {
								$alarms = array_rekey(
									db_fetch_assoc('SELECT id, name
										FROM gridalarms_alarm
										ORDER BY name'),
									'id', 'name'
								);
							}

							if (cacti_sizeof($alarms)) {
								foreach ($alarms as $key => $row) {
									print "<option value='" . $key . "'" . ($key == get_request_var('id') ? ' selected' : '') . '>' . html_escape($row) . '</option>';
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Status', 'gridalarms');?>
					</td>
					<td>
						<select id='status' onChange='applyFilter()'>
							<option value='-1' <?php print (get_request_var('status') == -1 ? 'selected':'');?>><?php print __('All', 'gridalarms');?></option>
							<?php
							foreach ($log_types_display as $key => $row) {
								print "<option value='" . $key . "'" . ($key == get_request_var('status') ? ' selected' : '') . '>' . $row . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<input type='submit' id='go' value='Go' title='Search for Alert'>
					</td>
					<td>
						<input type='button' id='clear' value='Clear' title='Return to the default time span'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Type', 'gridalarms');?>
					</td>
					<td>
						<select id='alarm_type' onChange='applyFilter()'>
							<option value='-1' <?php print (get_request_var('alarm_type') == -1 ? 'selected':'');?>><?php print __('All', 'gridalarms');?></option>
							<option value='0' <?php print (get_request_var('alarm_type') == 0 ? 'selected':'');?>><?php print __('Hi/Low', 'gridalarms');?></option>
							<option value='1' <?php print (get_request_var('alarm_type') == 1 ? 'selected':'');?>><?php print __('Time-Based', 'gridalarms');?></option>
						</select>
					</td>
					<td>
						<?php print __('Records', 'gridalarms');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>";
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT count(*)
		FROM gridalarms_alarm_log AS gal
		INNER JOIN gridalarms_alarm AS ga
		ON gal.alarm_id=ga.id
		$sql_where", $sql_params);

	$display_text = array(
		'name' => array(
			'display' => __('Name', 'gridalarms'),
			'sort' => 'ASC'
		),
		'time' => array(
			'display' => __('Event Time', 'gridalarms'),
			'sort' => 'DESC'
		),
		'status' => array(
			'display' => __('Status', 'gridalarms'),
			'sort' => 'ASC'
		),
		'type' => array(
			'display' => __('Type', 'gridalarms'),
			'sort' => 'ASC'
		),
		'alarm_value' => array(
			'display' => __('Alert Value', 'gridalarms'),
			'align' => 'right',
			'sort' => 'ASC'
		),
		'current' => array(
			'display' => __('Current Value', 'gridalarms'),
			'align' => 'right',
			'sort' => 'ASC'
		),
	);

	html_start_box('', '100%', '', '4', 'center', '');

	$nav = html_nav_bar(($admin ? 'gridalarms_alarm.php?tab=log':'grid_alarmdb.php?tab=log'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Alerts'), 'page', 'main');

	print $nav;

	$alarm = db_fetch_row_prepared('SELECT *
		FROM gridalarms_alarm
		WHERE id = ?',
		array(get_request_var('id')));

	html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false, $admin?'gridalarms_alarm.php?tab=log':'grid_alarmdb.php?tab=log');

	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			$url  = ($admin ? 'gridalarms_alarm.php':'grid_alarmdb.php') . '?action=logdetail&logid=' . $row['id'] . '&id=' . $row['alarm_id'];
			$name = ($row['name'] != '' ? $row['name']:__('Alert Removed', 'gridalarms'));

			$tmp_row_color = $alarm_log_bgcolors[$log_types[$row['status']]];

			rtm_form_alternate_row_color($tmp_row_color, $tmp_row_color);

			form_selectable_cell(filter_value($name, get_request_var('filter'), $url), $row['id'], '', '');
			form_selectable_cell(date('Y-m-d H:i:s', $row['time']), $row['id'], '', '');
			form_selectable_cell(ucfirst($log_types_display[$row['status']]), $row['id'], '', '');

			if ($row['type'] == '99') {
				form_selectable_cell(__('Acknowledgment', 'gridalarms'), $row['id'], '', '');
			} else {
				form_selectable_cell($alarm_types[$row['type']], $row['id'], '', '');
			}

			if ($row['alarm_value'] == '') {
				form_selectable_cell('-', $row['id'], '', 'right');
			} else {
				form_selectable_cell(html_escape($row['alarm_value']), $row['id'], '', 'right');
			}

			form_selectable_cell(html_escape($row['current']), $row['id'], '', 'right');

			form_end_row();
		}
	} else {
		print "<tr><td class='center' colspan='" . (cacti_sizeof($display_text)) . "'><em>" . __('No Alert Log Entries Found', 'gridalerts') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}

	alarm_log_legend();
}

function alarm_log_request_validation() : void {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'time',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'DESC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'alarm_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'status' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		)
	);

	validate_store_request_vars($filters, 'sess_galog');
	/* ================= input validation and session storage ================= */
}

/**
 *  This is a generic funtion for this page that makes sure that
 *  we have a good request.  We want to protect against people who
 *  like to create issues with Cacti.
 */
function alarm_template_request_validation() : void {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'alarm_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'ds_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
		)
	);

	validate_store_request_vars($filters, 'sess_gatp');
	/* ================= input validation and session storage ================= */
}

/**
 *  This is a generic funtion for this page that makes sure that
 *  we have a good request.  We want to protect against people who
 *  like to create issues with Cacti.
 */
function alarm_request_validation() : void {
	/* ================= input validation and session storage ================= */
	$filters = array(
		'rows' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'page' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => '1'
		),
		'clusterid' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
		),
		'template_id' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '0'
		),
		'filter' => array(
			'filter' => FILTER_CALLBACK,
			'pageset' => true,
			'default' => '',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_column' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'name',
			'options' => array('options' => 'sanitize_search_string')
		),
		'sort_direction' => array(
			'filter' => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => array('options' => 'sanitize_search_string')
		),
		'alarm_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'alarm_ds_type' => array(
			'filter' => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
		),
		'state' => array(
			'filter' => FILTER_VALIDATE_INT,
			'default' => 2,
			'pageset' => true,
		)
	);

	validate_store_request_vars($filters, 'sess_ga');
	/* ================= input validation and session storage ================= */
}

function populate_template_default_layout(array &$alarm) : void {
	$expression = get_template_expression_by_id($alarm['expression_id']);

	if ($expression['ds_type'] == '0') {
		$primary_key_array = get_primary_key_from_table($expression['db_table']);

		/* do the primary key first, and sort on it */
		$i = 1;

		if (cacti_sizeof($primary_key_array)) {
			foreach ($primary_key_array as $metric) {
				db_execute_prepared("REPLACE INTO gridalarms_template_layout
					(alarm_id, hash, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array(
						$alarm['id'],
						get_hash_alert_template(0, 'layout_item'),
						ucfirst($metric),
						$metric,
						$i,
						$i,
						0,
						'general',
						'left'
					)
				);

				$i++;
			}
		}

		/* do the other keys next, and dont sort it */
		$other_metrics = get_template_expression_db_columns($alarm['expression_id']);

		if (!empty($alarm['metric'])) {  //don't add it when $alarm['metric'] is "0"
			if (($key_found = array_search($alarm['metric'], $primary_key_array)) === false) {
				$other_metrics[$alarm['metric']] = $alarm['metric'];
			}
		}

		$other_metrics2 = array();
		if (cacti_sizeof($other_metrics)) {
			foreach ($other_metrics as $metric) {
				if (($key_found = array_search($metric, $primary_key_array)) === false) {
					$other_metrics2[$metric] = $metric;
				}
			}
		}

		if (cacti_sizeof($other_metrics2)) {
			foreach ($other_metrics2 as $metric) {
				db_execute_prepared("REPLACE INTO gridalarms_template_layout
					(alarm_id, hash, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array(
						$alarm['id'],
						get_hash_alert_template(0, 'layout_item'),
						ucfirst($metric),
						$metric,
						$i,
						0,
						0,
						'general',
						'left'
					)
				);

				$i++;
			}
		}
	} elseif ($expression['ds_type'] == '1') {
		$columns = get_columns_from_sql($expression['sql_query'], 'template', $alarm['expression_id']);

		/* populate this first 8 columns, leave the rest to the user */
		$i = 1;

		if (cacti_sizeof($columns)) {
			foreach ($columns as $metric) {
				db_execute_prepared("REPLACE INTO gridalarms_template_layout
					(alarm_id, hash, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
					array(
						$alarm['id'],
						get_hash_alert_template(0, 'layout_item'),
						ucfirst($metric),
						$metric,
						$i,
						($i < 4 ? $i:0),
						0,
						'general',
						'left'
					)
				);

				$i++;

				if ($i > 8) {
					break;
				}
			}
		}
	}
}

function populate_default_layout(array &$alarm) : void {
	$expression = get_expression_by_id($alarm['expression_id']);

	if ($expression['ds_type'] == '0') {
		$primary_key_array = get_primary_key_from_table($expression['db_table']);

		/* do the primary key first, and sort on it */
		$i = 1;

		if (cacti_sizeof($primary_key_array)) {
			foreach ($primary_key_array as $metric) {
				db_execute_prepared('REPLACE INTO gridalarms_alarm_layout
					(alarm_id, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$alarm['id'],
						ucfirst($metric),
						$metric,
						$i,
						$i,
						0,
						'general',
						'left'
					)
				);

				$i++;
			}
		}

		/* do the other keys next, and dont sort it */
		$other_metrics = get_expression_db_columns($alarm['expression_id']);

		if (!empty($alarm['metric'])) {  //don't add it when $alarm['metric'] is "0"
			if (($key_found = array_search($alarm['metric'], $primary_key_array)) === false) {
				$other_metrics[$alarm['metric']] = $alarm['metric'];
			}
		}

		$other_metrics2 = array();

		if (cacti_sizeof($other_metrics)) {
			foreach ($other_metrics as $metric) {
				if (($key_found = array_search($metric, $primary_key_array)) === false) {
					$other_metrics2[$metric] = $metric;
				}
			}
		}

		if (cacti_sizeof($other_metrics2)) {
			foreach ($other_metrics2 as $metric) {
				db_execute_prepared('REPLACE INTO gridalarms_alarm_layout
					(alarm_id, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$alarm['id'],
						ucfirst($metric),
						$metric,
						$i,
						0,
						0,
						'general',
						'left'
					)
				);

				$i++;
			}
		}
	} elseif ($expression['ds_type'] == '1') {
		$columns = get_columns_from_sql($expression['sql_query'], 'alarm', $alarm['expression_id']);

		/* populate this first 8 columns, leave the rest to the user */
		$i = 1;

		if (cacti_sizeof($columns)) {
			foreach ($columns as $metric) {
				db_execute_prepared('REPLACE INTO gridalarms_alarm_layout
					(alarm_id, display_name, column_name, sequence, sortposition, sortdirection, type, align)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$alarm['id'],
						ucfirst($metric),
						$metric,
						$i,
						($i < 4 ? $i:0),
						0,
						'general',
						'left'
					)
				);

				$i++;

				if ($i > 8) {
					break;
				}
			}
		}
	}
}

/**
 * alarm_breached_items - function returns the list of breached items for alarming
 */
function alarm_breached_items(int $id, bool $return_details = false, bool $limits = true, bool $forenv = false, bool $istemplate = false) : string {
	global $config, $cnn_id;

	/* ================= input validation ================= */
	input_validate_input_number($id);
	/* ==================================================== */

	if ($return_details && !$forenv) {
		// Don't return to the browser, return in variable at end
		ob_start();
	} elseif ($forenv) {
		$env = '';
	}

	$expression = array();

	/* use thold's base url */
	$httpurl = read_config_option('base_url');

	if (!$istemplate && $id > 0) {
		$alarm = get_alarm_by_id($id);

		if (cacti_sizeof($alarm)) {
			$expression = get_expression_by_id($alarm['expression_id']);
		}
	} else {
		$alarm = get_template_by_id($id);

		if (cacti_sizeof($alarm)) {
			$expression = get_template_expression_by_id($alarm['expression_id']);
		}
	}

	if (!cacti_sizeof($expression)) {
		// shorten the path, make readable for merge
	} elseif ($expression['ds_type'] == 1 || $expression['ds_type'] == 0) {
		if (!$istemplate) {
			$nocolumns = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM gridalarms_alarm_layout
				WHERE alarm_id = ?',
				array($alarm['id']));

			if (!$nocolumns) {
				populate_default_layout($alarm);
			}

			$columns = db_fetch_assoc_prepared('SELECT *
				FROM gridalarms_alarm_layout
				WHERE alarm_id = ?
				ORDER BY sequence ASC',
				array($alarm['id']));

			$sorts = db_fetch_assoc_prepared('SELECT *
				FROM gridalarms_alarm_layout
				WHERE alarm_id = ?
				AND sortposition > 0
				ORDER BY sortposition ASC',
				array($alarm['id']));

			$displays = array_rekey(
				db_fetch_assoc_prepared('SELECT *
					FROM gridalarms_alarm_layout
					WHERE alarm_id = ?
					ORDER BY sequence ASC',
					array($alarm['id'])),
				'column_name', 'display_name'
			);
		} else {
			$nocolumns = db_fetch_cell_prepared('SELECT COUNT(*)
				FROM gridalarms_template_layout
				WHERE alarm_id = ?',
				array($alarm['id']));

			if (!$nocolumns) {
				populate_template_default_layout($alarm);
			}

			$columns = db_fetch_assoc_prepared('SELECT *
				FROM gridalarms_template_layout
				WHERE alarm_id = ?
				ORDER BY sequence ASC',
				array($alarm['id']));

			$sorts = db_fetch_assoc_prepared('SELECT *
				FROM gridalarms_template_layout
				WHERE alarm_id = ?
				AND sortposition > 0
				ORDER BY sortposition ASC',
				array($alarm['id']));

			$displays = array_rekey(
				db_fetch_assoc_prepared('SELECT *
					FROM gridalarms_template_layout
					WHERE alarm_id = ?
					ORDER BY sequence ASC',
					array($alarm['id'])),
				'column_name', 'display_name'
			);
		}

		$display_text = array();
		$count        = 0;
		$flat_keys    = '';
		$first        = true;
		$clusterid    = false;
		$column_names = array();

		/* layout the column names */
		if (cacti_sizeof($columns)) {
			foreach ($columns as $c) {
				$column_names[$c['column_name']] = $c['column_name'];

				$display_text['nosort' . $count] = array(
					'display' => $displays[$c['column_name']],
					'sort'    => 'ASC',
					'align'   => ($c['align'] != '' ? $c['align']:'left')
				);

				$count++;

				if ($c['column_name'] != 'clustername' || $expression['ds_type'] == 1) {
					if ($first == true) {
						$flat_keys .= ' ' . $c['column_name'] . ' ';
						$first = false;
					} else {
						$flat_keys .= ' , ' . $c['column_name'] . ' ';
					}
				}
			}
		}

		/* create the sort order */
		$order_by = '';
		if (cacti_sizeof($sorts)) {
			foreach ($sorts as $s) {
				if ($s['column_name'] == 'clustername' && $expression['ds_type'] == 0) {
					$s['column_name'] = 'clusterid';
				}

				if ($s['column_name'] == 'clusterid') $clusterid = true;

				$order_by .= (strlen($order_by) ? ',':' ORDER BY ') . $s['column_name'] . ($s['sortdirection'] == 0 ? ' ASC':' DESC');
			}
		}

		html_start_box(__('First %s Threshold Breaching Items', read_config_option('gridalarm_alert_limit'), 'gridalarms'), '100%', '', '3', 'center', '');

		html_header_sort($display_text, '', '');

		$i=0;

		if ($expression['ds_type'] == 1) {
			/* append clusterid to where clause if found in table primary keys */
			if ($alarm['clusterid'] != 0 && ($clusterid)) {
				$sql_from_where = ' WHERE query.clusterid=' . $alarm['clusterid'];
			} else {
				$sql_from_where = '';
			}

			if (!$istemplate) {
				$sql_query = gridalarms_replace_custom_input($expression['id'], $expression['sql_query']);
			} else {
				$sql_query = gridalarms_template_replace_custom_input($expression['id'], $expression['sql_query']);
			}

			$sql_query = do_script_variable_replacement($sql_query, $alarm);

			if (!$limits) {
				$items = db_fetch_assoc('SELECT * FROM (' . $sql_query . ') AS query ' . $sql_from_where . $order_by);
			} else {
				$items = db_fetch_assoc('SELECT * FROM (' . $sql_query . '  LIMIT ' . read_config_option('gridalarm_alert_limit') . ') AS query ' . $sql_from_where . $order_by);
			}
		} else {
			/* append clusterid to where clause if found in table primary keys */
			$sql_from_where_extra = "";

			if ($alarm['clusterid'] != 0 && ($clusterid)) {
				$sql_from_where_extra = "clusterid=" . $alarm['clusterid'];
			}

			if (!$istemplate) {
				$sql_from_where = build_expression_string_for_sql_from_where($alarm['expression_id'], $sql_from_where_extra);
			} else {
				$sql_from_where = build_template_expression_string_for_sql_from_where($alarm['expression_id'], $sql_from_where_extra);
			}

			$sql_from_where = do_script_variable_replacement($sql_from_where, $alarm);

			if (!$limits) {
				$items = db_fetch_assoc('SELECT ' . $flat_keys . ' ' . $sql_from_where . $order_by);
			} else {
				$item_sql = 'SELECT * FROM (SELECT ' . $flat_keys . ' ' . $sql_from_where . ' LIMIT ' . read_config_option('gridalarm_alert_limit') . ') AS query ' . $order_by;
				$items = db_fetch_assoc($item_sql);
			}
		}

		if (cacti_sizeof($items)) {
			/* configure html for display and log term logging */
			foreach ($items as $item) {
				$line = '';
				if (!$forenv) {
					form_alternate_row();
				}

				foreach ($columns as $c) {
					$class       = $c['align'] != '' ? $c['align']:'left';
					$column_name = $c['column_name'];

					// Many of these column names are string and general
					// So we can ignore all but alignment for them.
					if (!$forenv) {
						switch(strtolower($c['column_name'])) {
						case 'clustername':
							if (isset($item['clusterid'])) {
								$cluster_name = get_clustername($item['clusterid']);
								$cluster_name = !empty($cluster_name)? $cluster_name : __('N/A', 'gridalarms');

								print "<td class='$class nowrap'>" . html_escape($cluster_name) . "</td>";
							} else {
								print "<td class='$class nowrap'" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							}

							break;
						case 'jobid':
							if (isset($column_names['indexid']) && isset($column_names['submit_time']) && isset($column_names['clusterid'])) {
								$href_str = $httpurl . 'plugins/grid/grid_bjobs.php?query=1&action=viewjob&clusterid=' .  $item['clusterid'] . '&indexid=' . $item['indexid'] . '&jobid=' . $item['jobid'] . '&submit_time=' .  strtotime($item['submit_time']) . '&title='. $item['jobid'];

								if (isset($item['start_time'])) {
									$href_str .= (strtotime($item['start_time'])? '&start_time=' . strtotime($item['start_time']) : '');
								}

								if (isset($item['end_time'])) {
									$href_str .= (strtotime($item['end_time'])? '&end_time=' . strtotime($item['end_time']) : '');
								}

								print "<td class='$class nowrap'><a href='" . html_escape($href_str) . "'>" . $item['jobid'] . "</a></td>";
							} else {
								print "<td class='$class nowrap'>" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							}

							break;
						case 'queue':
						case 'queuename':
							if (isset($column_names['clusterid'])) {
								if ('lost_and_found'== $item[$column_name]) {
									print "<td class='$class nowrap'><a class='pic'>" . html_escape($item[$column_name]) . "</a></td>";
								} else {
									print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_bqueues.php?query=1&action=&clusterid=' .
									$item['clusterid'] . '&filter=' . $item[$column_name]) . "'>" .
									html_escape($item[$column_name]) . "</a></td>";
								}
							} else {
								print "<td class='$class nowrap'>" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							}

							break;
						case 'exec_host':
						case 'from_host':
						case 'host':
							if (isset($item['clusterid'])) {
								print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_bzen.php?action=zoom&clusterid=' .
									$item['clusterid'] . '&exec_host=' . $item[$column_name]) . "'>" .
									html_escape($item[$column_name]) . "</a></td>";
							} else {
								print "<td class='$class nowrap'>" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							}

							break;
						case 'feature_name':
							print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/license/lic_checkouts.php?query=1&filter=' .
								html_escape($item[$column_name])) . "'>" .
								html_escape($item[$column_name]) . "</a></td>";

							break;
						case 'user':
						case 'username':
							if (isset($item['clusterid'])) {
								print "<td class='$class nowrap'><a href='" . html_escape($httpurl . 'plugins/grid/grid_busers.php?query=1&noactivity=true&clusterid=' .
									$item['clusterid'] . '&filter=' . $item[$column_name]) . "'>" .
									html_escape($item[$column_name]) . "</a></td>";
							} else {
								print "<td class='$class nowrap'>" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							}

							break;
						default:
							if ($c['type'] == 'general' || $c['type'] == 'string' || $c['type'] == 'date') {
								print "<td class='$class nowrap'>" . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms')) . "</td>";
							} elseif ($c['type'] == 'integer' || $c['type'] == 'double') {
								if (!isset($item[$column_name])) {
									print "<td class='$class nowrap'>" . __('Not Found', 'gridalarms') . '</td>';
								} elseif (is_numeric($item[$column_name])) {
									$suffix = '';
									switch($c['units']) {
										case 'bytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] / 1000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' B';
											}
										case 'kbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name], $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' KB';
											}
											break;
										case 'mbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] * 1000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' MB';
											}
											break;
										case 'gbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] * 1000000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' GB';
											}
											break;
										case 'tbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] * 1000000000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' TB';
											}
											break;
										case 'pbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] * 1000000000000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' PB';
											}
											break;
										case 'xbytes':
											if ($c['autoscale'] == '-1' || $c['autoscale'] == '0') {
												$value = display_job_memory($item[$column_name] * 1000000000000000, $c['digits']);
											} else {
												$value = number_format_i18n($item[$column_name], $c['digits']) . ' EB';
											}
											break;
										case 'jobs':
											$value = $item[$column_name];
											$suffix = ' Jobs';
											break;
										case 'slots':
											$value = $item[$column_name];
											$suffix = ' Slots';
											break;
										case 'seconds':
											$value = display_job_time($item[$column_name] * 60, $c['digits'], false);
											break;
										case 'minutes':
											$value = display_job_time($item[$column_name] * 60, $c['digits'], false);
											break;
										case 'hours':
											$value = display_job_time($item[$column_name] * 3600, $c['digits'], false);
											break;
										case 'days':
											$value = display_job_time($item[$column_name] * 86400, $c['digits'], false);
											break;
										default:
											$value = $item[$column_name];
											break;
									}

									print "<td class='$class nowrap'>" . html_escape($value . $suffix) . "</td>";
								} else {
									print "<td class='$class nowrap'>" . html_escape($item[$column_name]) . '</td>';
								}
							} elseif ($c['type'] == 'fpercent') {
							} elseif ($c['type'] == 'wpercent') {
							}
						}
					} else {
						if ($c == 'clustername' && $expression['ds_type'] == 0) {
							$line .= (strlen($line) ? ',':'') . (html_escape(get_clustername($item['clusterid'])));
						} else {
							$line .= (strlen($line) ? ',':'') . (isset($item[$column_name]) ? html_escape($item[$column_name]):__('Not Found', 'gridalarms'));
						}
					}
				}

				if (!$forenv) {
					form_end_row();
				} else {
					$env .= (strlen($env) ? '|':'') . $line;
				}
			}

			/* storing items into the log_items table */
			if ($return_details) {
				$sql_insert = 'INSERT INTO gridalarms_alarm_log_items ';

				db_execute_prepared('UPDATE gridalarms_alarm_log_items
					SET present = 0
					WHERE alarm_id = ?
					AND present = 1',
					array($id));

				$j=0;

				foreach ($items as $item) {
					$sql   = '';
					$csql  = '';
					$ltype = 0;
					$count = 1;
					$cols  = '';
					$first = true;
					$i=1;

					foreach ($column_names as $c) {
						/* build a sql prefix values array */
						if ($first) {
							$cols .= (strlen($cols) ? ',':'') . '`column' . substr('00' . $count,-2) . '`';
							$count++;
						}

						switch(strtolower($c)) {
							case 'clustername': // Type 16
								$ltype = $ltype | 16;

								if (isset($item['clusterid'])) {
									$sql .= (strlen($sql) ? ',':'') . db_qstr(get_clustername($item['clusterid']));
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr(get_clustername($item['clusterid']));
								} else {
									$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

									if (isset($item[$column_name])) {
										$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
									}
								}

								break;
							case 'jobid': // Type 1
								if (isset($column_names['indexid']) && isset($column_names['submit_time']) && isset($column_names['clusterid'])) {
									$ltype = $ltype | 1;
								}

								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

								break;
							case 'queue': // Type 4
							case 'queuename':
								if (isset($column_names['clusterid'])) {
									$ltype = $ltype | 4;
								}

								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

								break;
							case 'exec_host': // Type 2
							case 'from_host':
							case 'host':
							case 'hostname':
								if (isset($item['clusterid'])) {
									$ltype = $ltype | 2;
								}

								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

								break;
							case 'feature_name':
								$ltype = $ltype | 32;

								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

								break;
							case 'user':
							case 'username':
								if (isset($item['clusterid'])) {
									$ltype = $ltype | 8;
								}

								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));

								break;
							default:
								if (isset($item[$column_name])) {
									$csql .= (strlen($csql) ? ' AND ':'') . 'column' . substr('00' . $i, -2) . '=' . db_qstr($item[$column_name]);
								}

								$sql .= (strlen($sql) ? ',':'') . db_qstr(isset($item[$column_name]) ? $item[$column_name]:__('Not Found', 'gridalarms'));
						}

						$i++;
					}

					if ($first) {
						$values = '(`alarm_id`,`type`,`clusterid`,`jobid`,`indexid`,`submit_time`,`queue`,`host`,`feature_name`,`user`,' .
							$cols . ',`present`,`last_updated`) VALUES ';
						$first  = false;
					}

					if (!$ltype) {
						$ltype = 64;
					}

					$clusterid    = 0;
					$jobid        = 0;
					$indexid      = 0;
					$submit_time  = '';
					$queue        = '';
					$host         = '';
					$feature_name = '';
					$user         = '';

					if (isset($item['clusterid'])) {
						$clusterid = $item['clusterid'];
					}

					if (isset($item['jobid'])) {
						$jobid = $item['jobid'];
					}

					if (isset($item['indexid'])) {
						$indexid = $item['indexid'];
					}

					if (isset($item['submit_time'])) {
						$submit_time = $item['submit_time'];
					}

					if (isset($item['feature_name'])) {
						$feature_name = $item['feature_name'];
					}

					if (isset($item['queue'])) {
						$queue = $item['queue'];
					} elseif (isset($item['queuename'])) {
						$queue = $item['queuename'];
					}

					if (isset($item['user'])) {
						$user = $item['user'];
					} elseif (isset($item['username'])) {
						$user = $item['username'];
					}

					if (isset($item['exec_host'])) {
						$host = $item['exec_host'];
					} elseif (isset($item['host'])) {
						$host = $item['host'];
					} elseif (isset($item['hostname'])) {
						$host = $item['hostname'];
					} elseif (isset($item['from_host'])) {
						$host = $item['from_host'];
					}

					$sql_array[$j]['sql'] = '(' . $id . ',' . $ltype . ',' . $clusterid . ',' . $jobid . ',' . $indexid . ',' . db_qstr($submit_time) . ','
						. db_qstr($queue) . ',' . db_qstr($host) . ',' . db_qstr($feature_name) . ',' . db_qstr($user) . ',' . $sql . ', 1, NOW())';

					$sql_array[$j]['csql'] = "(alarm_id=$id AND clusterid=$clusterid AND host='$host' AND $csql)";
					$j++;
				}

				if (cacti_sizeof($sql_array)) {
					foreach ($sql_array as $a) {
						$exists = db_fetch_cell("SELECT id
							FROM gridalarms_alarm_log_items
							WHERE " . $a['csql'], '', false);

						if (!$exists) {
							db_execute($sql_insert . $values . $a['sql']);
						} else {
							db_execute_prepared('UPDATE gridalarms_alarm_log_items
								SET last_updated = NOW(), present = 1
								WHERE id = ?',
								array($exists));
						}
					}
				}

				db_execute_prepared('DELETE FROM gridalarms_alarm_log_items
					WHERE alarm_id = ?
					AND present = 0',
					array($id));
			}
		} else {
			if (!$forenv) {
				print "<tr><td colspan='" . cacti_sizeof($column_names) . "'><em>" . __('No Breached Items Found', 'gridalarms') . '</em></td></tr>';
			}

			if ($return_details) {
				db_execute_prepared('DELETE FROM gridalarms_alarm_log_items
					WHERE alarm_id = ?',
					array($id));
			}
		}

		if (!$forenv) {
			html_end_box(false);
		}
	} elseif ($expression['ds_type'] == 2) {
		if (!$istemplate) {
			$script_data = gridalarms_replace_custom_input($expression['id'], $expression['script_data']);
		} else {
			$script_data = gridalarms_template_replace_custom_input($expression['id'], $expression['script_data']);
		}

		$script_data = do_script_variable_replacement($script_data, $alarm);
		$script_data = "(" . str_replace("\r", "", str_replace("\n", "", $script_data)) . ")";

		$result      = shell_exec($script_data);

		if (!$forenv) {
			html_start_box(__('First %s Threshold Breaching Items', read_config_option('gridalarm_alert_limit'), 'gridalarms'), '100%', '', '3', 'center', '');
		}

		if ($result != '') {
			switch($expression['script_data_type']) {
			case '0':
			case '1':
				$header = $expression['script_data_type'] == 0 ? true:false;
				$first = true;
				if (function_exists('str_getcsv')) {
					$results = explode("\n", $result);
					foreach ($results as $r) {
						$data[] = str_getcsv($r);
					}
				} else {
					$data  = my_str_getcsv($result);
				}

				if (cacti_sizeof($data)) {
					if (!$forenv) {
						foreach ($data as $row) {
							if ($first && $header) {
								print "<tr class='tableSubHeader'>\n";
							} else {
								print "<tr>";
							}
							foreach ($row as $column) {
								if ($first && $header) {
									print "<td style='white-space:nowrap;' class='tableSubHeaderColumn'>" . html_escape($column) . "</td>\n";
								} else {
									print "<td style='white-space:nowrap;'>" . html_escape($column) . "</td>\n";
								}
							}
							print "</tr>";
							$first = false;
						}
					} else {
						if ($header) {
							array_shift($data);
						}

						$i = 0;
						foreach ($data as $row) {
							$line = implode(',', $row);
							$env .= ($i == 0 ? '':'|') . $line;
							$i++;
						}
					}
				}

				break;
			case '2': // HTML
				if (!$forenv) {
					print "<tr><td>" . html_escape($result) . "</td></tr>\n";
				}

				break;
			case '3': // Plain text
				if (!$forenv) {
					print "<tr><td><pre style='margin:1px;'>" . html_escape($result) . "</pre></td></tr>\n";
				} else {
					$env = str_replace("\n", "|", $result);
				}

				break;
			}
		} else {
			if (!$forenv) {
				print '<tr><td><em>Script Returned No Results</em></td></tr>';
			}
		}

		if (!$forenv) {
			html_end_box(false);
		}
	}

	if ($return_details && !$forenv) {
		return ob_get_clean();
	} elseif ($forenv) {
		return $env;
	} else {
		return '';
	}
}

function my_str_getcsv(string $input, string $delimiter = ',', string $enclosure = '"', string $escape = '\\', string $eol = '\n') : bool|string {
	if (is_string($input) && !empty($input)) {
		$output = array();
		$tmp    = preg_split("/".$eol."/", $input);

		if (is_array($tmp) && !empty($tmp)) {
			foreach ($tmp as $line_num => $line) {
				if (preg_match("/".$escape.$enclosure."/", $line)) {
					$line = trim($line);

					while ($strlen = strlen($line)) {
						$pos_delimiter       = strpos($line, $delimiter);
						$pos_enclosure_start = strpos($line, $enclosure);
						if ($pos_delimiter !== false && $pos_enclosure_start !== false && ($pos_enclosure_start < $pos_delimiter)) {
							$enclosed_str        = substr($line,1);
							$pos_enclosure_end   = strpos($enclosed_str,$enclosure);
							$enclosed_str        = substr($enclosed_str,0,$pos_enclosure_end);
							$output[$line_num][] = $enclosed_str;
							$offset              = $pos_enclosure_end+3;
						} elseif ($pos_enclosure_start === false && $pos_delimiter === false) {
							$output[$line_num][] = substr($line,0);
							$offset = strlen($line);
						} elseif ($pos_enclosure_start !== false && $pos_delimiter === false) {
							$output[$line_num][] = substr($line,1,strlen($line)-2);
							break;
						} else {
							$output[$line_num][] = substr($line,0,$pos_delimiter);
							$offset = (!empty($pos_enclosure_start) && ($pos_enclosure_start < $pos_delimiter)) ?$pos_enclosure_start :$pos_delimiter+1;
						}

						$line = substr($line,$offset);
					}
				} else {
					$line = preg_split("/".$delimiter."/",$line);

					/* Validating against pesky extra line breaks creating false rows. */
					if (is_array($line) && (0 != cacti_sizeof($line[0]))) {
						$output[$line_num] = $line;
					}
				}
			}

			return $output;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function alarm_tabs(bool $admin = true) : void {
	global $config;

	get_filter_request_var('id');
	get_filter_request_var('logid');

	input_validate_input_regex(get_nfilter_request_var('tab'), '^([a-zA-Z0-9_]+)$');

	load_current_session_value('tab', 'sess_gatab', 'alarms');

	/* present a tabbed interface */
	$tabs = array(
		'alarms' => __('Alerts', 'gridalarms'),
		'log'    => __('Alert Log', 'gridalarms')
	);

	if (get_nfilter_request_var('action') == 'details') {
		$tabs['details'] = __('Breached Items', 'gridalarms');
		set_request_var('tab', 'details');
	}

	if (get_nfilter_request_var('action') == 'logdetail') {
		$tabs['logdetail'] = __('Log Details', 'gridalarms');
		set_request_var('tab', 'logdetail');
	}

	$current_tab = get_request_var('tab');

	/* draw the tabs */
	print "<div class='tabs'><nav><ul>";

	if (cacti_sizeof($tabs)) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li><a class='tab" . (($tab_short_name == $current_tab) ? " selected'" : "'") .
				" href='" . html_escape($config['url_path'] .
				'plugins/gridalarms/' . ($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
				'tab=' . $tab_short_name .
				(isset_request_var('id') ? '&id=' . get_request_var('id'):'') .
				(isset_request_var('logid') ? '&logid=' . get_request_var('logid'):'')) .
				"'>" . $tabs[$tab_short_name] . '</a></li>';
		}
	}

	print '</ul></nav></div>';
}

function alarm_enabled(int $alarm_id) : bool|int {
	return db_fetch_cell_prepared('SELECT
		IF(alarm_enabled="on",1,0)
		FROM gridalarms_alarm WHERE id = ?',
		array($alarm_id));
}

function alarm_acknowledge_enabled(int $alarm_id) : bool|int {
	return db_fetch_cell_prepared('SELECT
		IF(req_ack="on",1,0)
		FROM gridalarms_alarm WHERE id = ?',
		array($alarm_id));
}

function alarm_breached(int $alarm_id) : bool|int {
	return db_fetch_cell_prepared('SELECT IF(alarm_alert>1,1,0)
		FROM gridalarms_alarm
		WHERE id = ?',
		array($alarm_id));
}

function alarm_acknowledge_required(int $alarm_id) : bool|int {
	return db_fetch_cell_prepared('SELECT IF(acknowledgement="on",1,0)
		FROM gridalarms_alarm
		WHERE id = ?',
		array($alarm_id));
}

function alarm_disable_prepared(int $alarm_id) : bool|int {
	db_execute_prepared('UPDATE gridalarms_alarm
		SET alarm_enabled="off"
		WHERE id = ?',
		array($alarm_id));
}

function alarm_enable_prepared(int $alarm_id) : bool|int {
	db_execute_prepared('UPDATE gridalarms_alarm
		SET alarm_enabled="on"
		WHERE id = ?',
		array($alarm_id));
}

function alarm_acknowledge(int $alarm_id) : bool|int{
	db_execute_prepared('UPDATE gridalarms_alarm
		SET acknowledgement="" WHERE id = ?',
		array($alarm_id));
}

function alarm_reset(int $alarm_id) : bool|int {
	db_execute_prepared('UPDATE gridalarms_alarm
		SET acknowledgement="on"
		WHERE id = ?',
		array($alarm_id));
}

function list_templates(bool $admin = true) {
	global $alarm_bgcolors, $config, $template_actions, $alarm_types,
		$frequencies, $grid_rows_selector, $gridalarms_types, $gridalarms_severities;

	//do import action
	if (isset_request_var('import_submit')) {
		$xml_data = $_SESSION['import_file_content'];

		// import gridalarms template xml to db
		import_xml_gridalarms_template($xml_data);
	} elseif (isset_request_var('import_check')) {  //check import file
		/* file upload */
		if (($_FILES['import_file']['tmp_name'] != 'none') && ($_FILES['import_file']['tmp_name'] != '')) {
			$fp = fopen($_FILES['import_file']['tmp_name'],'r');
			$xml_data = fread($fp,filesize($_FILES['import_file']['tmp_name']));
			fclose($fp);
		} else {
			raise_message(40, __("No file uploaded."), MESSAGE_LEVEL_ERROR);
			header('Location: gridalarms_templates.php');
			exit;
		}

		// check gridalarms template xml
		$dup_alarm_array = array();
		$ret = check_import_xml_gridalarms_template($xml_data, $dup_alarm_array);

		if ($ret < 0) {                                   //show error message
			raise_message(40, __("Invalid file."), MESSAGE_LEVEL_ERROR);
			header('Location: gridalarms_templates.php');
			exit;
		} elseif ($ret == 0) {                          // no duplicate alert hash code, do the import
			import_xml_gridalarms_template($xml_data);
		} else {                                         // there are duplicate alerts, show confirmation page.
			top_header();

			form_start('gridalarms_templates.php');

			html_start_box(__('Alert template Import Confirmation', 'gridalarms'), '60%', '', '3', 'center', '');

			$dup_alarm_list = '';
			foreach ($dup_alarm_array as $alarm_name => $alarm_info) {
				$dup_alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
			}

			print "<tr>
				<td class='textArea'>
					<p>When you click \"Yes\", the following Alert Template(s) will be overwritten.</p>
					<div class='itemlist'><ul>$dup_alarm_list</ul></div>
				</td>
			</tr>";

			$save_html = "<input type='submit' value='Yes' name='save'>";

			print "<tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='import_submit' value='import_submit'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? 'No':'Return') . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_templates.php\")'>$save_html
				</td>
			</tr>";

			$_SESSION['import_file_content'] = $xml_data;

			html_end_box();

			form_end();

			bottom_footer();

			exit;
		}
	} elseif (isset_request_var('import')) {  //display import page
		top_header();

		form_start('gridalarms_templates.php', '', true);

		html_start_box(__('Alert template Import', 'gridalarms'), '60%', '', '3', 'center', '');

		print "<tr>
			<td class='textArea'>
				<p>Please choose a alert template XML file in your local machine.</p>
				<p></p>
				<p><input type='file' name='import_file'></p>
			</td>
		</tr>";

		$save_html = "<input type='submit' value='Yes' name='save'>";

		print "<tr>
			<td colspan='2' class='saveRow'>
				<input type='hidden' name='import_check' value='import_check'>
				<input type='button' name='cancel' value='" . (strlen($save_html) ? 'No':'Return') . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_templates.php\")'>" . $save_html . "
			</td>
		</tr>";

		html_end_box();

		form_end(false);

		bottom_footer();

		exit;
	}

	alarm_template_request_validation();

	$statefilter = '';
	$sql_where   = '';
	$sql_params  = array();

	if (isset_request_var('alarm_type') && get_request_var('alarm_type') != '-1') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gat.alarm_type = ?';
		$sql_params[] = get_request_var('alarm_type');
	}

	if (isset_request_var('clusterid') && get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gat.clusterid = ?';
		$sql_params[] = get_request_var('clusterid');
	}

	if (isset_request_var('ds_type') && get_request_var('ds_type') >= 0) {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gate.ds_type = ?';
		$sql_params[] = get_request_var('ds_type');
	}

	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gat.name LIKE ?';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	}

	if (get_request_var('rows') <= 0) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_order = get_order_string();
	$sql_limit = ' LIMIT ' . ($rows*(get_request_var('page')-1)) . ",$rows";

	$sql = "SELECT gat.*, gate.ds_type, SUM(IF(ga.id IS NOT NULL,1,0)) AS alerts
		FROM gridalarms_template AS gat
		LEFT JOIN gridalarms_alarm AS ga
		ON ga.template_id = gat.id
		LEFT JOIN gridalarms_template_expression AS gate
		ON gat.expression_id = gate.id
		$sql_where
		GROUP BY gat.id
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);

	html_start_box(__('Alert Template Management', 'gridalarms') . rtm_hover_help('rtm_alert_templates.html', __esc('Learn More', 'gridalarms')), '100%', '', '3', 'center', 'gridalarms_template_edit.php?tab=general&id=');

	?>
	<tr class='noprint even'>
		<td>
		<form id='listalarm' action='gridalarms_templates.php' method='get'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridalarms');?>
					</td>
					<td>
						<select id='clusterid' onChange='applyFilter()'>
							<option value='0' <?php if (!isset_request_var('clusterid') || get_request_var('clusterid') == '0') print "selected";?>>All</option>
							<?php
							$clusters = get_clusters();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									if (isset_request_var('clusterid') && (get_request_var('clusterid') == $cluster["clusterid"])) {
										print "<option value='" . $cluster["clusterid"] . "' selected>"  . html_escape($cluster["clustername"]) . "</option>";
									} else {
										print "<option value='" . $cluster["clusterid"] . "'>"  . html_escape($cluster["clustername"]) . "</option>";
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Data Type', 'gridalarms');?>
					</td>
					<td>
						<select id='ds_type' onChange='applyFilter()'>
							<option value='-1' <?php if (!isset_request_var('ds_type') || get_request_var('ds_type') == '-1') print "selected";?>>All</option>
							<option value='0' <?php if (get_request_var('ds_type') == '0') print "selected";?>>Table</option>
							<option value='1' <?php if (get_request_var('ds_type') == '1') print "selected";?>>SQL Query</option>
							<option value='2' <?php if (get_request_var('ds_type') == '2') print "selected";?>>Script</option>
						</select>
					</td>
					<td>
						<?php print __('Threshold Type', 'gridalarms');?>
					</td>
					<td>
						<select id='alarm_type' onChange='applyFilter()'>
							<option value='-1' <?php if (!isset_request_var('alarm_type') || get_request_var('alarm_type') == '-1') print "selected";?>>All</option>
							<option value='0' <?php if (get_request_var('alarm_type') == '0') print "selected";?>>Hi/Low</option>
							<option value='1' <?php if (get_request_var('alarm_type') == '1') print "selected";?>>Time-Based</option>
						</select>
					</td>
					<td>
						<span>
							<input type='button' id='go' value='<?php print __('Go', 'gridalarms');?>' title='<?php print __('Search for Alert', 'gridalarms');?>'>
							<input type='button' id='clear' value='<?php print __('Clear', 'gridalarms');?>' title='<?php print __('Clear Filters', 'gridalarms');?>'>
							<input type='button' id='import' value='<?php print __('Import', 'gridalarms');?>' title='<?php print __('Import Templates', 'gridalarms');?>'>
						</span>
					</td>
					<td>
						<input type='hidden' id='tab' value='alarms'>
						<input type='hidden' id='search' value='search'>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridalarms');?>
					</td>
					<td>
						<input type='text' size='25' id='filter' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Alerts', 'gridalarms');?>
					</td>
					<td>
						<select id='rows' onChange='applyFilter()'>
						<?php
						if (cacti_sizeof($grid_rows_selector)) {
							foreach ($grid_rows_selector as $key => $value) {
								print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>\n";
							}
						}
						?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = 'gridalarms_templates.php?tab=alarms&header=false';
			strURL += '&alarm_type=' + $('#alarm_type').val();
			strURL += '&rows='       + $('#rows').val();
			strURL += '&ds_type='    + $('#ds_type').val();
			strURL += '&clusterid='  + $('#clusterid').val();
			strURL += '&filter='     + $('#filter').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = 'gridalarms_templates.php?clear=true&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#go').click(function() {
				applyFilter();
			});

			$('#import').click(function() {
				strURL  = 'gridalarms_templates.php?import=Import&header=false';
				loadPageNoHeader(strURL);
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#listalarm').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});
		});

		</script>
		</td>
	</tr>
	<?php

	html_end_box();

	$total_rows = db_fetch_cell_prepared("SELECT COUNT(*)
		FROM gridalarms_template AS gat
		LEFT JOIN gridalarms_alarm AS ga
		ON ga.template_id = gat.id
		LEFT JOIN gridalarms_template_expression AS gate
		ON gat.expression_id = gate.id
		$sql_where", $sql_params);

	$display_text = array(
		'name' => array(
			'display' => __('Name', 'gridalarms'),
			'sort' => 'ASC'
		),
		'external_id' => array(
			'display' => __('External ID', 'gridalarms'),
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Template Cluster', 'gridalarms'),
			'sort' => 'ASC'
		),
		'id' => array(
			'display' => __('ID', 'gridalarms'),
			'sort'    => 'ASC',
			'align' => 'right'
		),
		'alerts' => array(
			'display' => __('Alerts', 'gridalarms'),
			'sort'    => 'DESC',
			'align' => 'right'
		),
		'syslog_priority' => array(
			'display' => __('Severity', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'nosort2' => array(
			'display' => __('Alert Type', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'frequency' => array(
			'display' => __('Check Freq', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_type' => array(
			'display' => __('Value Type', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_hi' => array(
			'display' => __('High', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_low' => array(
			'display' => __('Low', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_fail_trigger' => array(
			'display' => __('Trigger', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'time_fail_length' => array(
			'display' => __('Duration', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'repeat_alert' => array(
			'display' => __('Repeat Alert', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		)
	);

	$nav = html_nav_bar('gridalarms_templates.php', MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Alert Templates'), 'page', 'main');

	if ($admin) {
		form_start('gridalarms_templates.php', 'chk');
	}

	print $nav;

	html_start_box('', '100%', '', '4', 'center', '');

	if ($admin) {
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);
	} else {
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	}

	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			if ($row['req_ack'] == 'on') {
				$ack = __('Yes', 'gridalarms');
			} else {
				$ack = __('No', 'gridalarms');
			}

			$name = $row['name'] != '' ?  $row['name'] : ' [' . $row['data_source_name'] . ']';

			form_alternate_row('line' . $row['id'], false);
			form_selectable_cell(filter_value($name, get_request_var('filter'), 'gridalarms_template_edit.php?tab=general&id=' . $row['id']), $row['id'], '', '');

			form_selectable_cell($row['external_id'], $row['id']);

			$url = $config['url_path'] . 'plugins/gridalarms/gridalarms_alarm.php?template_id=' . $row['id'];

			if ($row['clusterid'] == '0') {
				form_selectable_cell(__('N/A', 'gridalarms'), $row['id'], '', '');
			} else {
				$cluster_name = get_clustername($row['clusterid']);
				$cluster_name = isset($cluster_name)? html_escape($cluster_name) : __('N/A', 'gridalarms');
				form_selectable_cell($cluster_name, $row['id'], '', '');
			}

			form_selectable_cell($row['id'], $row['id'], '', 'right');
			form_selectable_cell(filter_value($row['alerts'], '', $url), $row['id'], '', 'right');

			form_selectable_cell($gridalarms_severities[$row['syslog_priority']], $row['id'], '', 'right');
			form_selectable_cell($row['ds_type'] != '' ? $gridalarms_types[$row['ds_type']] : __('Not Defined', 'gridalarms'), $row['id'], '', 'right');
			form_selectable_cell($frequencies[$row['frequency']], $row['id'], '', 'right');
			form_selectable_cell($alarm_types[$row['alarm_type']], $row['id'], '', 'right');
			form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_hi'] : ($row['alarm_type'] == 1 ? $row['time_hi'] : '')), $row['id'], '', 'right');
			form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_low'] : ($row['alarm_type'] == 1 ? $row['time_low'] : '')), $row['id'], '', 'right');
			form_selectable_cell(($row['alarm_type'] == 0 ? ('<i>' . plugin_alarm_duration_convert($row['alarm_fail_trigger'], 'alert') . '</i>') : ($row['alarm_type'] == 1 ? ('<i>' . __('%d Triggers', $row['time_fail_trigger']) . '</i>') : '')), $row['id'], '', 'right');
			form_selectable_cell(($row['alarm_type'] == 1 ? plugin_alarm_duration_convert($row['time_fail_length'], 'time') : ''), $row['id'], '', 'right');
			form_selectable_cell(($row['repeat_alert'] == '' ? '' : plugin_alarm_duration_convert($row['repeat_alert'], 'repeat')), $row['id'], '', 'right');

			form_checkbox_cell($row['name'], $row['id'], '', '');
			form_end_row();
		}
	} else {
		print '<tr><td colspan=15><em>' . __('No Alert Templates Found', 'gridalarms') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}

	if ($admin) {
		//TODO: tooltip:
		//     Select an alert and choose an action:<ul><li>Acknowledge - Acknowledge a triggered alert.</li><li>Reset Acknowledgement - Select an acknowledged alert and reset, it goes back to alert state.</li><li>Enable - Enable a disabled alert. Once the alert triggers, you can acknowledge.</li><li>Disable - Disable an alert for later use.</li></ul>
		draw_actions_dropdown($template_actions);
		form_end();
	}
}

function list_alarms(bool $admin = true) : void {
	global $alarm_bgcolors, $config, $alarm_actions, $alarm_types,
		$frequencies, $grid_rows_selector, $gridalarms_types, $gridalarms_severities;

	alarm_request_validation($admin);

	$statefilter = '';
	$sql_where   = '';
	$sql_params  = array();

	if (isset_request_var('state')) {
		if (get_request_var('state') != 0) {
			if (get_request_var('state') == 1) {
				$statefilter = "gridalarms_alarm.alarm_enabled = 'off'";
			}

			if (get_request_var('state') == 2) {
				$statefilter = "gridalarms_alarm.alarm_enabled = 'on'";
			}

			if (get_request_var('state') == 3) {
				$statefilter = 'gridalarms_alarm.alarm_alert != 0';
			}

			if ($statefilter != '') {
				$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . $statefilter;
			}
		}
	}

	if (isset_request_var('alarm_ds_type') && get_request_var('alarm_ds_type') != '-1') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_expression.ds_type = ?';
		$sql_params[] = get_request_var('alarm_ds_type');
	}

	if (isset_request_var('alarm_type') && get_request_var('alarm_type') != '-1') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.alarm_type = ?';
		$sql_params[] = get_request_var('alarm_type');
	}

	if (isset_request_var('clusterid') && get_request_var('clusterid') != '0') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.clusterid = ?';
		$sql_params[] = get_request_var('clusterid');
	}

	if (isset_request_var('template_id')) {
		if (get_request_var('template_id') > '0') {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.template_id = ?';
			$sql_params[] = get_request_var('template_id');
		} elseif (get_request_var('template_id') == '-1') {
			$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.template_id = 0';
		}
	}

	if (isset_request_var('filter') && get_request_var('filter') != '') {
		$sql_where .= ($sql_where == '' ? 'WHERE ' : ' AND ') . 'gridalarms_alarm.name LIKE ?';
		$sql_params[] = '%' . get_request_var('filter') . '%';
	}

	/* if the number of rows is -1, set it to the default */
	if (get_request_var('rows') == -1) {
		$rows = read_config_option('num_rows_table');
	} else {
		$rows = get_request_var('rows');
	}

	$sql_order = get_order_string();
    $sql_limit = 'LIMIT ' . ($rows*(get_request_var('page')-1)) . ',' . $rows;

	$sql = "SELECT gridalarms_alarm.*, gridalarms_expression.ds_type
		FROM gridalarms_alarm
		LEFT JOIN gridalarms_expression
		ON gridalarms_alarm.expression_id=gridalarms_expression.id
		$sql_where
		$sql_order
		$sql_limit";

	$result = db_fetch_assoc_prepared($sql, $sql_params);

	html_start_box(__('Alert Management', 'gridalarms') . rtm_hover_help('rtm_grid_alerts_create.html', __esc('Learn More', 'gridalarms')), '100%', '', '3', 'center', ($admin ? 'gridalarms_alarm_edit.php?action=select_template':''));

	?>
	<tr class='noprint even'>
		<td>
		<form id='listalarm' action='<?php print ($admin ? 'gridalarms_alarm.php':'grid_alarmdb.php');?>' method='get'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Cluster', 'gridalarms');?>
					</td>
					<td>
						<select id='clusterid' onChange='applyFilter()'>
							<option value='0' <?php if (!isset_request_var('clusterid') || get_request_var('clusterid') == '0') print "selected";?>><?php print __('All', 'gridalarms');?></option>
							<?php
							$clusters = get_clusters();

							if (cacti_sizeof($clusters)) {
								foreach ($clusters as $cluster) {
									if (isset_request_var('clusterid') && (get_request_var('clusterid') == $cluster["clusterid"])) {
										print "<option value='" . $cluster["clusterid"] . "' selected>"  . html_escape($cluster["clustername"]) . "</option>";
									} else {
										print "<option value='" . $cluster["clusterid"] . "'>"  . html_escape($cluster["clustername"]) . "</option>";
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Template', 'gridalarms');?>
					</td>
					<td>
						<select id='template_id' onChange='applyFilter()'>
							<option value='0' <?php if (!isset_request_var('template_id') || get_request_var('template_id') == '0') print 'selected';?>>All</option>
							<option value='-1' <?php if (!isset_request_var('template_id') || get_request_var('template_id') == '-1') print 'selected';?>>None</option>
							<?php
							$templates = db_fetch_assoc('SELECT DISTINCT gat.id, gat.name
								FROM gridalarms_template AS gat
								INNER JOIN gridalarms_alarm AS gaa
								ON gat.id = gaa.template_id
								ORDER BY gat.name');

							if (cacti_sizeof($templates)) {
								foreach ($templates as $t) {
									if (isset_request_var('template_id') && (get_request_var('template_id') == $t['id'])) {
										print "<option value='" . $t['id'] . "' selected>"  . html_escape($t['name']) . '</option>';
									} else {
										print "<option value='" . $t['id'] . "'>"  . html_escape($t['name']) . '</option>';
									}
								}
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Data Type', 'gridalarms');?>
					</td>
					<td width='1'>
						<select id='alarm_ds_type' onChange='applyFilter()'>
							<option value='-1' <?php if (!isset_request_var('alarm_ds_type') || get_request_var('alarm_ds_type') == '-1') print "selected";?>>All</option>
							<option value='0' <?php if (get_request_var('alarm_ds_type') == '0') print "selected";?>>Expression</option>
							<option value='1' <?php if (get_request_var('alarm_ds_type') == '1') print "selected";?>>SQL Query</option>
							<option value='2' <?php if (get_request_var('alarm_ds_type') == '2') print "selected";?>>Script</option>
						</select>
					</td>
					<td>
						<span>
							<input type='button' id='go' value='<?php print __esc('Go', 'gridalarms');?>' title='<?php print __esc('Search for Alert', 'gridalarms');?>'>
							<input type='button' id='clear' value='<?php print __esc('Clear', 'gridalarms');?>' title='<?php print __esc('Clear Filters', 'gridalarms');?>'>
						</span>
					</td>
				</tr>
			</table>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'gridalarms');?>
					</td>
					<td>
						<input type='text' id='filter' size='25' value='<?php print html_escape(get_request_var('filter'));?>'>
					</td>
					<td>
						<?php print __('Type', 'gridalarms');?>
					</td>
					<td width='1'>
						<select id='alarm_type' onChange='applyFilter()'>
							<option value='-1' <?php if (!isset_request_var('alarm_type') || get_request_var('alarm_type') == '-1') print "selected";?>><?php print __('All', 'gridalarms');?></option>
							<option value='0' <?php if (get_request_var('alarm_type') == '0') print "selected";?>>Hi/Low</option>
							<option value='1' <?php if (get_request_var('alarm_type') == '1') print "selected";?>>Time-Based</option>
						</select>
					</td>
					<td>
						<?php print __('State', 'gridalarms');?>
					</td>
					<td>
						<select id='state' onChange='applyFilter()'>
							<option value=0><?php print __('Any', 'gridalarms');?></option>
							<?php
							$stateFilters = array(
								1 => __('Disabled', 'gridalarms'),
								2 => __('Enabled', 'gridalarms'),
								3 => __('Triggered', 'gridalarms')
							);

							foreach ($stateFilters as $key => $row) {
								print "<option value='" . $key . "'" . (isset_request_var('state') && $key == get_request_var('state') ? ' selected' : '') . '>' . $row . '</option>';
							}
							?>
						</select>
					</td>
					<td>
						<?php print __('Alerts', 'gridalarms');?>
					</td>
					<td width='1'>
						<select id='rows' onChange='applyFilter()'>
							<?php
							if (cacti_sizeof($grid_rows_selector)) {
								foreach ($grid_rows_selector as $key => $value) {
									print '<option value="' . $key . '"'; if (get_request_var('rows') == $key) { print ' selected'; } print '>' . html_escape($value) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</form>
		<script type='text/javascript'>
		function applyFilter() {
			strURL  = '?tab=alarms&header=false';
			strURL += '&state=' + $('#state').val();
			strURL += '&alarm_ds_type=' + $('#alarm_ds_type').val();
			strURL += '&alarm_type=' + $('#alarm_type').val();
			strURL += '&template_id=' + $('#template_id').val();
			strURL += '&rows=' + $('#rows').val();
			strURL += '&clusterid=' + $('#clusterid').val();
			strURL += '&filter=' + $('#filter').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL  = '?tab=alarms&clear=true&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#go').click(function() {
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#listalarm').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});
		});
		</script>
		</td>
	</tr>
	<?php

	html_end_box();

	$display_text = array(
		'nosort0' => array(
			'display' => __('Actions', 'gridalarms'),
			'sort' => 'ASC'
		),
		'name' => array(
			'display' => __('Name', 'gridalarms'),
			'sort' => 'ASC'
		),
		'nosort1' => array(
			'display' => __('Cluster', 'gridalarms'),
			'sort' => 'ASC'
		),
		'id' => array(
			'display' => __('ID', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'syslog_priority' => array(
			'display' => __('Severity', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'frequency' => array(
			'display' => __('Check Freq', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'alarm_type' => array(
			'display' => __('Value Type', 'gridalarms'),
			'sort' => 'ASC',
			'align' => 'right'
		),
		'alarm_hi' => array(
			'display' => __('High', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_low' => array(
			'display' => __('Low', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'lastread' => array(
			'display' => __('Current', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'alarm_fail_trigger' => array(
			'display' => __('Trigger', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'time_fail_length' => array(
			'display' => __('Duration', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'repeat_alert' => array(
			'display' => __('Repeat Alert', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		)
	);

	if ($admin) {
		$display_text += array(
			'template_enabled' => array(
				'display' => __('Templated', 'gridalarms'),
				'sort' => 'DESC',
				'align' => 'right'
			)
		);
	}

	$display_text += array(
		'last_runtime' => array(
			'display' => __('Last Checked', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		),
		'last_duration' => array(
			'display' => __('Run Time', 'gridalarms'),
			'sort' => 'DESC',
			'align' => 'right'
		)
	);

	$total_rows = db_fetch_cell_prepared("SELECT count(*) FROM gridalarms_alarm
		LEFT JOIN gridalarms_expression
		ON gridalarms_alarm.expression_id = gridalarms_expression.id
		$sql_where", $sql_params);

	$nav = html_nav_bar(($admin ? 'gridalarms_alarm.php':'grid_alarmdb.php'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, cacti_sizeof($display_text) + 1, __('Alerts', 'gridalarms'), 'page', 'main');

	if ($admin) {
		form_start('gridalarms_alarm.php', 'chk');
	}

	html_start_box('', '100%', '', '4', 'center', '');

	print $nav;

	if ($admin) {
		html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	} else {
		html_header_sort($display_text, get_request_var('sort_column'), get_request_var('sort_direction'));
	}

	$i = 0;
	$c = 0;

	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			$c++;

			if ($row['alarm_alert'] != 0) {
				$alertstat = 'yes';
				if ($row["alarm_type"] == 0) {
					$bgcolor = ($row['alarm_fail_count'] >= $row['alarm_fail_trigger'] ? 'red' : 'yellow');
				} else {
					$bgcolor = ($row['alarm_fail_count'] >= $row['time_fail_trigger'] ? 'red' : 'yellow');
				}
			} else {
				$alertstat = 'no';
				$bgcolor = 'green';
			}

			if ($row['req_ack'] == 'on') {
				$ack = __('Yes', 'gridalarms');
			} else {
				$ack = __('No', 'gridalarms');
			}

			if ($row['acknowledgement'] == 'on' && $row['alarm_alert'] == 0) {
				$bgcolor = 'orange';
			}

			if ($admin) {
				if ($row['alarm_enabled'] == 'off') {
					rtm_form_alternate_row_color($alarm_bgcolors['grey'], $alarm_bgcolors['grey'], 'line' . $row["id"], false);
				} else {
					rtm_form_alternate_row_color($alarm_bgcolors[$bgcolor], $alarm_bgcolors[$bgcolor], 'line' . $row["id"], false);
				}

				if ($row['expression_id'] > 0) {
					print "<td class='nowrap' style='width:1%;'>";

					print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
						"&action=details&tab=details&id=" . $row['id']) . "' title='" . __esc('View Currently Breached Items', 'gridalarms') . "'>
						<i class='fas fa-search-plus'></i>
					</a>";

					print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
						'tab=log&id=' . $row['id'] .
						'&status=-1&alarm_type=-1&filter=') . "' title='" . __esc('View Alert History', 'gridalarms') . "'>
						<i class='tholdGlyphLog fas fa-exclamation-triangle'></i>
					</a>";

					if (alarm_enabled($row['id'])) {
						print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
							'action=disable' .
							'&id=' . $row['id']) . "' title='" . __esc('Disable Alert Details', 'gridalarms') . "'>
							<i class='tholdGlyphDisable fas fa-stop-circle'></i>
						</a>";
					} else {
						print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
							'action=enable' .
							'&id=' . $row['id']) . "' title='" . __esc('Enable Alert Details', 'gridalarms') . "'>
							<i class='tholdGlyphEnable fas fa-play-circle'></i>
						</a>";
					}

					if (alarm_acknowledge_enabled($row['id']) && alarm_acknowledge_required($row['id'])) {
						if (alarm_acknowledge_required($row['id'])) {
							print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
								'action=acknowledge' .
								'&id=' . $row['id']) . "' title='" . __esc('Acknowledge Alert', 'gridalarms') . "'>
								<i class='tholdGlyphAcknowledge fas fa-clipboard-check'></i>
							</a>";
						} else {
							print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
								'action=resetack' .
								'&id=' . $row['id']) . "' title='" . __esc('Reset Alert Acknowledgement', 'gridalarms') . "'>
								<i class='tholdGlyphAcknowledgeResume fas fa-clipboard-check'></i>
							</a>";
						}
					}

					print "</td>";
				} else {
					print "<td></td>";
				}

				$name = ($row['name'] != '' ? $row['name'] : __('Unknown [%s]', $row['data_source_name'], 'gridalarms'));
				$url  = 'gridalarms_alarm_edit.php?tab=general&id=' . $row['id'];

				form_selectable_cell(filter_value($name, get_request_var('filter'), $url), $row['id'], '', '');

				if ($row['clusterid'] == '0') {
					form_selectable_cell(__('N/A', 'gridalarms'), $row['id'], '', '');
				} else {
					$cluster_name = html_escape(get_clustername($row['clusterid']));
					$cluster_name = isset($cluster_name)? $cluster_name :__('N/A', 'gridalarms');
					form_selectable_cell($cluster_name, $row['id'], '', '');
				}

				form_selectable_cell($row['id'], $row['id'], '', 'right');

				form_selectable_cell($gridalarms_severities[$row['syslog_priority']], $row['id'], '', 'right');
				form_selectable_cell($frequencies[$row['frequency']], $row['id'], '', 'right');
				form_selectable_cell($alarm_types[$row['alarm_type']], $row['id'], '', 'right');
				form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_hi'] : ($row['alarm_type'] == 1 ? $row['time_hi'] : '')), $row['id'], '', 'right');
				form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_low'] : ($row['alarm_type'] == 1 ? $row['time_low'] : '')), $row['id'], '', 'right');
				form_selectable_cell($row['lastread'], $row['id'], '', 'right');
				form_selectable_cell(($row['alarm_type'] == 0 ? ('<i>' . plugin_alarm_duration_convert($row['alarm_fail_trigger'], 'alert') . '</i>') : ($row['alarm_type'] == 1 ? ('<i>' . __('%d Triggers', $row['time_fail_trigger'], 'gridalarms') . '</i>') : '')), $row['id'], '', 'right');

				form_selectable_cell(($row['alarm_type'] == 1 ? plugin_alarm_duration_convert($row['time_fail_length'], 'time') : ''), $row['id'], '', 'right');
				form_selectable_cell(($row['repeat_alert'] == '' ? '' : plugin_alarm_duration_convert($row['repeat_alert'], 'repeat')), $row['id'], '', 'right');

				form_selectable_cell(($row['template_enabled'] == 'on' ? __('Yes', 'gridalarms'):__('No', 'gridalarms')), $row['id'], '', 'right');

				form_selectable_cell(($row['last_runtime'] == '0000-00-00 00:00:00' ? __('N/A', 'gridalarms'):$row['last_runtime']), $row['id'], '', 'right');
				form_selectable_cell(($row['last_runtime'] == '0000-00-00 00:00:00' ? __('N/A', 'gridalarms'):round($row['last_duration'], 2) . ' sec'), $row['id'], '', 'right');

				form_checkbox_cell($row['name'], $row['id'], '', '');

				form_end_row();
			} else {
				if ($row['alarm_enabled'] == 'off') {
					rtm_form_alternate_row_color($alarm_bgcolors['grey'], $alarm_bgcolors['grey'], 'line' . $row['id'], false);
				} else {
					rtm_form_alternate_row_color($alarm_bgcolors[$bgcolor], $alarm_bgcolors[$bgcolor], 'line' . $row['id'], false);
				}

				$hasAuth = api_user_realm_auth('gridalarms_alarm_edit.php');

				if ($row['expression_id'] > 0) {
					print "<td class='nowrap' style='width:1%;'>";

					if ($hasAuth) {
						print "<a class='pic' href='" . html_escape("gridalarms_alarm_edit.php?tab=general&id=" . $row['id']) . "' title='" . __esc('Edit Alert', 'gridalarms') . "'>
						<i class='tholdGlyphEdit fas fa-wrench'></i>
						</a>";
					}

					print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
						'action=details&tab=details&id=' . $row['id']) . "' title='" . __esc('View Currently Breached Items', 'gridalarms') . "'>
						<i class='fas fa-search-plus'></i>
					</a>";

					print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
						'tab=log&id=' . $row['id'] . '&status=-1&alarm_type=-1&filter=') . "' title='" . __esc('View Alert History', 'gridalarms') . "'>
						<i class='tholdGlyphLog fas fa-exclamation-triangle'></i>
					</a>";

					if (api_user_realm_auth('gridalarms_alarm.php')) {
						if (alarm_enabled($row['id'])) {
							print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
								'action=disable' .
								'&id=' . $row['id']) . "' title='" . __esc('Disable Alert Details', 'gridalarms') . "'>
								<i class='tholdGlyphDisable fas fa-stop-circle'></i>
							</a>";
						} else {
							print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
								'action=enable' .
								'&id=' . $row['id']) . "' title='" . __esc('Enable Alert Details', 'gridalarms') . "'>
								<i class='tholdGlyphEnable fas fa-play-circle'></i>
							</a>";
						}

						if (alarm_acknowledge_enabled($row['id']) && alarm_acknowledge_required($row['id'])) {
							if (alarm_acknowledge_required($row['id'])) {
								print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
									'action=acknowledge' .
									'&id=' . $row['id']) . "' title='" . __esc('Acknowledge Alert', 'gridalarms') . "'>
									<i class='tholdGlyphAcknowledge fas fa-clipboard-check'></i>
								</a>";
							} else {
								print "<a class='pic' href='" . html_escape(($admin ? 'gridalarms_alarm.php?':'grid_alarmdb.php?') .
									'action=resetack' .
									'&id=' . $row['id']) . "' title='" . __esc('Reset Alert Acknowledgement', 'gridalarms') . "'>
									<i class='tholdGlyphAcknowledgeResume fas fa-clipboard-check'></i>
								</a>";
							}
						}
					}

					print "</td>";
				} elseif ($hasAuth) {
					print "<td class='nowrap' style='width:1%;'><a class='pic' href='" . html_escape("gridalarms_alarm_edit.php?tab=general&id=" . $row['id']) . "' title='" . __('Edit Alert', 'gridalarms') . "'>
						<i class='tholdGlyphEdit fas fa-wrench'></i>
					</a></td>";
				} else {
					print "<td class='nowrap' style='width:1%;'></td>";
				}

				form_selectable_cell(($row['name'] != '' ? html_escape($row['name']):' [' . html_escape($row['data_source_name']) . ']'), $row['id']);

				if ($row["clusterid"] == '0') {
					form_selectable_cell( __('N/A', 'gridalarms'), $row['id']);
				} else {
					$cluster_name = get_clustername($row['clusterid']);
					$cluster_name = isset($cluster_name)? $cluster_name:__('N/A', 'gridalarms');
					form_selectable_cell(html_escape($cluster_name), $row['id']);
				}

				form_selectable_cell($row['id'], $row['id'], '', 'right');

				form_selectable_cell($gridalarms_severities[$row['syslog_priority']], $row['id'], '', 'right');
				form_selectable_cell($frequencies[$row['frequency']], $row['id'], '', 'right');
				form_selectable_cell($alarm_types[$row['alarm_type']], $row['id'], '', 'right');

				form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_hi'] : ($row['alarm_type'] == 1 ? $row['time_hi'] : '')), $row['id'], '', 'right');
				form_selectable_cell(($row['alarm_type'] == 0 ? $row['alarm_low'] : ($row['alarm_type'] == 1 ? $row['time_low'] : '')), $row['id'], '', 'right');
				form_selectable_cell($row['lastread'], $row['id'], '', 'right');

				form_selectable_cell(($row['alarm_type'] == 0 ? ("<i>" . plugin_alarm_duration_convert($row['alarm_fail_trigger'], 'alert') . "</i>") : ($row['alarm_type'] == 1 ? ("<i>" . $row['time_fail_trigger'] . " Triggers</i>") : '')), $row['id'], '', 'right');

				form_selectable_cell(($row['alarm_type'] == 1 ? plugin_alarm_duration_convert($row['time_fail_length'], 'time') : ''), $row['id'], '', 'right');
				form_selectable_cell(($row['repeat_alert'] == '' ? '' : plugin_alarm_duration_convert($row['repeat_alert'], 'repeat')), $row['id'], '', 'right');

				form_selectable_cell(($row['last_runtime'] == '0000-00-00 00:00:00' ? __('N/A', 'gridalarms'):$row['last_runtime']), $row['id'], '', 'right');
				form_selectable_cell(($row['last_runtime'] == '0000-00-00 00:00:00' ? __('N/A', 'gridalarms'):round($row['last_duration'], 2) . " sec"), $row['id'], '', 'right');
			}
		}
	} else {
		print '<tr class="tableRow"><td colspan="' . (cacti_sizeof($display_text)) . '"><em>' . __('No Alerts Found', 'gridalarms') . '</em></td></tr>';
	}

	html_end_box(false);

	if (cacti_sizeof($result)) {
		print $nav;
	}

	alarm_legend();

	if ($admin) {
		draw_actions_dropdown($alarm_actions);
		form_end();
	}
}

function alarm_log_purge() : void {
	db_execute('TRUNCATE gridalarms_alarm_log');
}

function do_alarms(string $from = 'console') : void {
	global $config;

	$alarms   = array();
	$question = "";

	foreach ($_POST as $var => $val) {
		if (preg_match("/^chk_(.*)$/", $var, $matches)) {
			$del = $matches[1];
			$rra = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_alarm
				WHERE id = ?',
				array($del));

			input_validate_input_number($del);

			$alarms[$del] = $rra;
		}
	}

	switch (get_request_var('drp_action')) {
		case ALERT_DELETE:	// Delete
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $me) {
						$expression_id = db_fetch_cell_prepared('SELECT expression_id
							FROM gridalarms_alarm
							WHERE id = ?',
							array($del));

						/* delete non relational data first */
						db_execute_prepared('DELETE FROM gridalarms_alarm
							WHERE id = ?',
							array($del));

						db_execute_prepared('DELETE FROM gridalarms_alarm_contacts
							WHERE alarm_id = ?',
							array($del));

						db_execute_prepared('DELETE FROM gridalarms_alarm_layout
							WHERE alarm_id = ?',
							array($del));

						db_execute_prepared('DELETE FROM gridalarms_alarm_log
							WHERE alarm_id = ?',
							array($del));

						db_execute_prepared('DELETE FROM gridalarms_alarm_log_items
							WHERE alarm_id = ?',
							array($del));

						api_plugin_hook_function('gridalarms_delete_hostsalarm',$del);

						/* delete the expression next */
						if (!empty($expression_id)) {
							db_execute_prepared('DELETE FROM gridalarms_expression
								WHERE id = ?',
								array($expression_id));

							db_execute_prepared('DELETE FROM gridalarms_expression_input
								WHERE expression_id = ?',
								array($expression_id));

							db_execute_prepared('DELETE FROM gridalarms_expression_item
								WHERE expression_id = ?',
								array($expression_id));

							/* remove any non-shared metrics */
							$metrics = db_fetch_assoc_prepared('SELECT metric_id
								FROM gridalarms_metric_expression
								WHERE expression_id = ?',
								array($expression_id));

							if (cacti_sizeof($metrics)) {
								foreach ($metrics as $m) {
									$shared_metric = db_fetch_cell_prepared('SELECT COUNT(*)
										FROM gridalarms_metric_expression
										WHERE metric_id = ?
										AND expression_id != ?',
										array($m['metric_id'], $expression_id));

									if (!$shared_metric) {
										db_execute_prepared('DELETE FROM gridalarms_metric
											WHERE id = ?',
											array($m['metric_id']));
									}
								}
							}

							/* remove any relationship of the expression to the metric */
							db_execute_prepared('DELETE FROM gridalarms_metric_expression
								WHERE expression_id = ?',
								array($expression_id));
						}
					}
				}

				header('Location:gridalarms_alarm.php');

				exit;
			}

			top_header();

			form_start('gridalarms_alarm.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');

				exit;
			} else {
				$alarm_list = '';

				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_alarm
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarm_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}

				html_start_box(__('Delete', 'gridalarms'), '60%', '', '3', 'center', '');

				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Delete the following Alert(s).', 'gridlarms') . "</p>
						<div class='itemlist'><ul>$alarm_list</ul></div>";

				print "</td></tr>
					</td>
				</tr>";

				$save_html = "<input type='submit' value='" . __esc('Continue') . "' name='save'>";
			}

			print "<tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . ALERT_DELETE . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? __esc('Cancel', 'gridalarms'):__esc('Return', 'gridalarms')) . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_alarm.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case ALERT_DISABLE: // Disabled
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $rra) {
						//plugin_alarm_log_changes($del, 'disabled_alarm', array('id' => $del));
						db_execute_prepared("UPDATE gridalarms_alarm
							SET alarm_enabled='off'
							WHERE id = ?",
							array($del));
					}
				}

				header('Location:gridalarms_alarm.php');
				exit;
			}

			top_header();

			form_start('gridalarms_alarm.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');

				exit;
			} else {
				$alarm_list = '';
				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_alarm
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarm_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}

				html_start_box(__('Disable', 'gridalarms'), '60%', '', '3', 'center', '');

				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Disable the following Alert(s).', 'gridalarms') . "</p>
						<div class='itemlist'>
							<ul>$alarm_list</ul>
						</div>";

				print "</td></tr>\n";

				$save_html = "<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' name='save'>";
			}

			print " <tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . ALERT_DISABLE . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? __esc('Cancel', 'gridalarms'):__esc('Return', 'gridalarms')) . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_alarm.php\")'>$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case ALERT_ENABLE: // Enabled
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $rra) {
						db_execute("UPDATE gridalarms_alarm
							SET alarm_enabled='on'
							WHERE id = ?",
							array($del));
					}
				}

				header('Location:gridalarms_alarm.php');
				exit;
			}

			top_header();

			form_start('gridalarms_alarm.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');

				exit;
			} else {
				$alarm_list = '';
				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_alarm
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarm_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}

				html_start_box(__('Enable', 'gridalarms'), '60%', '', '3', 'center', '');

				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Enable the following Alert(s).', 'gridalarms') . "</p>
						<div class='itemlist'>
							<ul>$alarm_list</ul>
						</div>";

				print "</td></tr>";

				$save_html = "<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' name='save'>";
			}

			print " <tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . ALERT_ENABLE . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? __('Cancel', 'gridalarms'):__esc('Return', 'gridalarms')) . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_alarm.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case ALERT_ACK: // Acknowledgement
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $me) {
						db_execute_prepared('UPDATE gridalarms_alarm
							SET acknowledgement="off"
							WHERE id = ?',
							array($del));

						alarm_ack_logging($del, get_nfilter_request_var('message'), get_nfilter_request_var('email'));
					}
				}

				if (isset_request_var('return_to')) {
					input_validate_input_regex_xss_attack(get_nfilter_request_var('return_to'));
					header('Location:' . get_nfilter_request_var('return_to'));
				} else {
					header('Location:gridalarms_alarm.php');
				}

				exit;
			}

			if ($from == 'console') {
				top_header();
			} else {
				general_header();
			}

			form_start('gridalarms_alarm.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');
				// header('Location: gridalarms_alarm.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
				exit;
			} else {
				$header_message = __('Acknowledge Alert', 'gridalarms');

				html_start_box($header_message, '60%', '', '3', 'center', '');

				form_alternate_row();

				print "<td width='50%'>" . __('Acknowledgement Message', 'gridalarms') . '<div class="formTooltip">' .
						display_tooltip(__('Enter comments if you want to append to the Alert log. Leave it blank if no comments are required.', 'gridalarms')) . '</div>' . "
					</td>
					<td width='50%'>
						<textarea class='textAreaNotes' name='message' rows='5' cols='65'></textarea>
					</td>
				</tr>\n";

				form_alternate_row();
				print "<td width='50%'>" . __('Acknowledgement Email', 'gridalarms') . '<div class="formTooltip">' .
                        display_tooltip(__('Send an email to administrators and job users.', 'gridalarms')) . '</div>' . "
					</td>
					<td width='50%'>
						<input type='checkbox' checked='checked' id='email' name='email'>&nbsp;
						<label valign='middle' for='email'>" . __('Acknowledgement Email', 'gridalarms') . "</label>
					</td>
				</tr>";

				$save_html = "<input type='submit' value='Acknowledge' name='save'>";
			}

			print "<tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . ALERT_ACK . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? 'Cancel':'Return') . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_alarm.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case ALERT_RESET: // Dismiss Acknowledgement
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $me) {
						db_execute_prepared('UPDATE gridalarms_alarm
							SET acknowledgement="off"
							WHERE id = ?',
							array($del));

						alarm_ack_logging($del, __('Acknowledgement Reset', 'gridalarms'));
					}
				}

				if (isset_request_var('return_to')) {
					input_validate_input_regex_xss_attack(get_nfilter_request_var('return_to'));
					header('Location:' . get_nfilter_request_var('return_to'));
				} else {
					header('Location:gridalarms_alarm.php');
				}

				exit;
			}

			top_header();


			form_start('gridalarms_alarm.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');
				// header('Location: gridalarms_alarm.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
				exit;
			} else {
				$alarms_list = '';

				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_alarm
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarms_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarms_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}

				html_start_box(__('Reset Acknowledgement', 'gridalarms'), '60%', '', '3', 'center', '');

				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Reset the Acknowledgement for the following Alert(s).', 'gridalarms') . "</p>
						<div class='itemlist'>
							<ul>$alarms_list</ul>
						</div>";

				print "</td></tr>";

				$save_html = "<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' name='save'>";
			}

			print "<tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . ALERT_RESET . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? __esc('Cancel', 'gridalarms'):__esc('Return', 'gridalarms')) . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_alarm.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
	}
}

function do_templates() : void {
	global $config;

	$alarms   = array();
	$question = "";

	foreach ($_POST as $var => $val) {
		if (preg_match("/^chk_(.*)$/", $var, $matches)) {
			$del = $matches[1];
			$rra = db_fetch_cell('SELECT id FROM gridalarms_template WHERE id=' . $del);

			input_validate_input_number($del);
			$alarms[$del] = $rra;
		}
	}

	switch (get_request_var('drp_action')) {
		case TEMPLATE_DELETE:	// Delete
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $me) {
						$expression_id = db_fetch_cell_prepared('SELECT expression_id
							FROM gridalarms_template
							WHERE id = ?',
							array($del));

						/* delete non relational data first */
						db_execute_prepared('DELETE FROM gridalarms_template WHERE id = ?', array($del));
						db_execute_prepared('DELETE FROM gridalarms_template_contacts WHERE alarm_id = ?', array($del));
						db_execute_prepared('DELETE FROM gridalarms_template_layout WHERE alarm_id = ?', array($del));

						/* delete the expression next */
						if (!empty($expression_id)) {
							db_execute_prepared('DELETE FROM gridalarms_template_expression WHERE id = ?', array($expression_id));
							db_execute_prepared('DELETE FROM gridalarms_template_expression_input WHERE expression_id = ?', array($expression_id));
							db_execute_prepared('DELETE FROM gridalarms_template_expression_item WHERE expression_id = ?', array($expression_id));

							/* remove any non-shared metrics */
							$metrics = db_fetch_assoc_prepared('SELECT metric_id
								FROM gridalarms_template_metric_expression
								WHERE expression_id = ?',
								array($expression_id));

							if (cacti_sizeof($metrics)) {
								foreach ($metrics as $m) {
									$shared_metric = db_fetch_cell_prepared('SELECT COUNT(*)
										FROM gridalarms_template_metric_expression
										WHERE metric_id = ?
										AND expression_id != ?',
										array($m['metric_id'], $expression_id));

									if (!$shared_metric) {
										db_execute_prepared('DELETE FROM gridalarms_template_metric
											WHERE id = ?',
											array($m['metric_id']));
									}
								}
							}

							/* remove any relationship of the expression to the metric */
							db_execute_prepared('DELETE FROM gridalarms_template_metric_expression
								WHERE expression_id = ?',
								array($expression_id));

							/* untemplate all alarms */
							db_execute_prepared("UPDATE gridalarms_alarm
								SET template_id = 0, template_enabled = ''
								WHERE template_id = ?",
								array($del));
						}
					}
				}

				header('Location:gridalarms_templates.php');
				exit;
			}

			top_header();

			form_start('gridalarms_templates.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');
				// header('Location: gridalarms_templates.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
				exit;
			} else {
				$alarm_list = '';

				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_template
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarm_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}

				html_start_box(__('Delete', 'gridalarms'), '60%', '', '3', 'center', '');

				print "<tr>
					<td class='textArea'>
						<p>" . __('Click \'Continue\' to Delete the following Alert Template(s).', 'gridalarms') . "</p>
						<div class='itemlist'><ul>$alarm_list</ul></div>";

				print "</td></tr>";

				$save_html = "<input type='submit' value='" . __esc('Continue', 'gridalarms') . "' name='save'>";
			}

			print " <tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . TEMPLATE_DELETE . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? __esc('Cancel', 'gridalarms'):__esc('Return', 'gridalarms')) . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_templates.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case TEMPLATE_DUP:	// Duplicate
			//do the duplicate action
			if (isset_request_var('ack_action')) {
				$alarm = sanitize_unserialize_selected_items(get_request_var('selected_items'), false);

				if ($alarm != false) {
					foreach ($alarm as $del => $me) {
						$alarm_1 = db_fetch_row_prepared('SELECT *
							FROM gridalarms_template
							WHERE id = ?',
							array($del));

						duplicate_gridalarms_template($alarm_1, get_request_var('title_format'));
					}
				}

				header('Location:gridalarms_templates.php?header=false');
				exit;
			}

			//display duplicate page
			top_header();

			form_start('gridalarms_templates.php');

			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');
				// header('Location: gridalarms_templates.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
				exit;
			} else {
				$alarm_list = '';
				foreach ($alarms as $del => $rra) {
					$alarm_info = db_fetch_cell_prepared('SELECT name
						FROM gridalarms_template
						WHERE id = ?',
						array($del));

					if ($alarm_info != '') {
						$alarm_list .= '<li>' . html_escape($alarm_info) . '</li>';
					} else {
						$alarm_list .= '<li>' . __('Unknown Alert', 'gridalarms') . '</li>';
					}
				}
				html_start_box(__('Duplicate', 'gridalarms'), '60%', '', '3', 'center', '');

				print " <tr>
						<td class='textArea'>
							<p>When you click \"Yes\", the following Alert Template(s) will be duplicated. You can optionally change the title format for the new Alert Template(s).</p>
							<div class='itemlist'><ul>$alarm_list</ul></div>
							<p><strong>Title Format:</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
						</td>
						</tr>\n
						";

				$save_html = "<input type='submit' value='Yes' name='save'>";
			}

			print "<tr>
				<td colspan='2' class='saveRow'>
					<input type='hidden' name='ack_action' value='ack_action'>
					<input type='hidden' name='selected_items' value='" . (isset($alarms) ? serialize($alarms) : '') . "'>
					<input type='hidden' name='drp_action' value='" . TEMPLATE_DUP . "'>
					<input type='button' name='cancel' value='" . (strlen($save_html) ? 'No':'Return') . "' onClick='cactiReturnTo(\"" . $config['url_path'] . "plugins/gridalarms/gridalarms_templates.php\")'>
					$save_html
				</td>
			</tr>";

			html_end_box();

			form_end();

			bottom_footer();

			break;
		case TEMPLATE_EXPORT:	// export alert template
			if (cacti_sizeof($alarms) == 0) {
				raise_message(40, __("You must select at least one alert."), MESSAGE_LEVEL_ERROR);
				header('Location: gridalarms_alarm.php?header=false');
				// header('Location: gridalarms_templates.php?action=edit&header=false&id=' . get_request_var('id') . '&tab=' . get_request_var('tab'));
				exit;
			}

			top_header();

			print '<script text="text/javascript">
				function DownloadStart(url) {
					document.getElementById("download_iframe").src = url;
					setTimeout(function() {
						document.location = "gridalarms_templates.php";
						Pace.stop();
					}, 500);
				}

				$(function() {
					//debugger;
					DownloadStart(\'gridalarms_templates.php?action=export&alarms=' . serialize($alarms) . '\');
				});
			</script>
			<iframe id="download_iframe" style="display:none;"></iframe>';

			bottom_footer();

			exit;
	}
}

function check_import_xml_gridalarms_template(string $xml_data, array &$dup_alarm_array) : int {
	global $config, $hash_type_codes, $hash_version_codes;

	include_once($config['library_path'] . '/xml.php');
	include_once($config['library_path'] . '/import.php');

	$hash_type_codes_bak = $hash_type_codes;
	$hash_type_codes['gridalarms_template']                   = '18';
	$hash_type_codes['gridalarms_template_contacts']          = '19';
	$hash_type_codes['gridalarms_template_layout']            = '20';
	$hash_type_codes['gridalarms_template_expression']        = '21';
	$hash_type_codes['gridalarms_template_expression_item']   = '22';
	$hash_type_codes['gridalarms_template_metric']            = '23';
	$hash_type_codes['gridalarms_template_expression_input']  = '24';
	$hash_type_codes['gridalarms_template_metric_expression'] = '25';

	$xml_array = xml2array($xml_data);

	if (cacti_sizeof($xml_array) == 0) {
		raise_message(7); /* xml parse error */
		$hash_type_codes = $hash_type_codes_bak;
		return -1;
	}

	$dup_alarm_array = array();
	foreach ($xml_array as $hash => $hash_array) {
		$parsed_hash = parse_xml_hash($hash);

		/* invalid/wrong hash */
		if ($parsed_hash == false) {
			raise_message('gridalarms_import_invalid_hash');
			$hash_type_codes = $hash_type_codes_bak;
			return -1;
		}

		if ($parsed_hash['type'] != 'gridalarms_template') {
			raise_message('gridalarms_import_hash_type_error');
			$hash_type_codes = $hash_type_codes_bak;
			return -1;
		}

		$alarm_info = db_fetch_cell_prepared('SELECT name
			FROM gridalarms_template
			WHERE hash = ?',
			array($parsed_hash['hash']));

		if ($alarm_info != '') {
			$dup_alarm_array[$alarm_info] = $alarm_info;
		}
	}

	//restore the hash_type_codes
	$hash_type_codes = $hash_type_codes_bak;

	return cacti_sizeof($dup_alarm_array);
}

function import_xml_gridalarms_template(string $xml_data) : void {
	global $config, $hash_type_codes, $hash_version_codes;

	include_once($config['library_path'] . '/xml.php');
	include_once($config['library_path'] . '/import.php');

	$hash_type_codes_bak = $hash_type_codes;
	$hash_type_codes['gridalarms_template']                   = '18';
	$hash_type_codes['gridalarms_template_contacts']          = '19';
	$hash_type_codes['gridalarms_template_layout']            = '20';
	$hash_type_codes['gridalarms_template_expression']        = '21';
	$hash_type_codes['gridalarms_template_expression_item']   = '22';
	$hash_type_codes['gridalarms_template_metric']            = '23';
	$hash_type_codes['gridalarms_template_expression_input']  = '24';
	$hash_type_codes['gridalarms_template_metric_expression'] = '25';

	$xml_array = xml2array($xml_data);

	if (cacti_sizeof($xml_array) == 0) {
		raise_message(7); /* xml parse error */
		$hash_type_codes = $hash_type_codes_bak;
		return;
	}

	foreach ($xml_array as $hash => $hash_array) {
		/* parse information from the hash */
		/*Array ([type] => gridalarms_template [version] => 0.8.7g [hash] => 9669450f6583a8b049e32e89f397f74f) */
		$parsed_hash = parse_xml_hash($hash);

		/* invalid/wrong hash */
		if ($parsed_hash == false) {
			raise_message('gridalarms_import_invalid_hash');
			$hash_type_codes = $hash_type_codes_bak;
			return;
		}

		if ($parsed_hash['type'] != 'gridalarms_template') {
			raise_message('gridalarms_import_hash_type_error');
			$hash_type_codes = $hash_type_codes_bak;
			return;
		}

		$ret = 0;

		import_single_template($parsed_hash['hash'], $hash_array, $ret);

		if ($ret < 0) {
			$hash_type_codes = $hash_type_codes_bak;
			return;
		}
	}

	//restore the hash_type_codes
	$hash_type_codes = $hash_type_codes_bak;
}

function import_single_template(string $alarm_hash,array $hash_array, int &$ret) : void {
	global $config;
	global $struct_gridalarms_template, $struct_gridalarms_template_contacts, $struct_gridalarms_template_expression;
	global $struct_gridalarms_template_expression_input,$struct_gridalarms_template_expression_item, $struct_gridalarms_template_layout;
	global $struct_gridalarms_template_metric, $struct_gridalarms_template_metric_expression;

	include_once($config['base_path'] . '/plugins/gridalarms/includes/settings.php');

	// prime the gridalarms globals
	gridalarms_config_arrays();

	//1. import gridalarms_template_expression
	$save = array();

	if (is_array ($hash_array['gridalarms_template_expression']) && !empty($hash_array['gridalarms_template_expression'])) {
		foreach ($hash_array['gridalarms_template_expression'] as $expression_hash => $expression_array) {
			foreach ($struct_gridalarms_template_expression as $field_name) {
				if (db_column_exists('gridalarms_template_expression', $field_name)) {
					$save[$field_name] =	isset($expression_array[$field_name])? $expression_array[$field_name] : '' ;
				}
			}

			$expression_parsed_hash = parse_xml_hash($expression_hash);

			$expression_id = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_template_expression
				WHERE hash = ?',
				array($expression_parsed_hash['hash']));

			$save['id']   = (empty($expression_id) ? '0' : $expression_id);
			$save['hash'] = $expression_parsed_hash['hash'] ;

			$expression_id = sql_save($save, 'gridalarms_template_expression');

			if ($expression_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_expression <' . $save['name'] . '> import failed.';
				raise_message ('gridalarms_import_failure');

				$ret =-1;

				return;
			}
		}
	}

	//2. import gridalarms_template
	$save = array();

	foreach ($struct_gridalarms_template as $field_name) {
		if (db_column_exists('gridalarms_template', $field_name)) {
			if ($field_name == 'base_time') {
				if (strpos($hash_array['base_time'], ':') !== false) {
					$hash_array['base_time'] = strtotime($hash_array['base_time']);
				}
			}

			$save[$field_name] = isset($hash_array[$field_name]) ? $hash_array[$field_name] : '';
		}
	}

	$alarm_id = db_fetch_cell_prepared('SELECT id
		FROM gridalarms_template
		WHERE hash = ?',
		array($alarm_hash));

	$save['id']            = (empty($alarm_id) ? '0' : $alarm_id);
	$save['hash']          = $alarm_hash ;
	$save['expression_id'] = $expression_id;

	$alarm_id = sql_save($save, 'gridalarms_template');

	if ($alarm_id < 0) {
		$messages['gridalarms_import_failure']['message'] = 'gridalarms_template <' .$save['name'] . '> import failed.';

		raise_message ('gridalarms_import_failure');

		$ret = -1;

		return;
	}

	//3. import gridalarms_template_contacts
	$save = array();

	if (is_array ($hash_array['gridalarms_template_contacts']) && !empty($hash_array['gridalarms_template_contacts'])) {
		foreach ($hash_array['gridalarms_template_contacts'] as $contact_hash => $contact_array) {
			foreach ($struct_gridalarms_template_contacts as $field_name) {
				if (db_column_exists('gridalarms_template_contacts', $field_name)) {
					$save[$field_name] = isset($contact_array[$field_name]) ? $contact_array[$field_name]:'';
				}
			}

			$save['alarm_id'] = $alarm_id;

			$contact_id = db_fetch_cell_prepared('SELECT contact_id
				FROM gridalarms_template_contacts
				WHERE  alarm_id = ?
				AND contact_id = ?
				LIMIT 1',
				array($alarm_id, $save['contact_id']));

			if ($contact_id > 0) {
				continue;
			}

			$contact_id = sql_save($save, 'gridalarms_template_contacts');

			if ($contact_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_contacts (alarm_id =' . $save['alarm_id'] . ', contact_id =' .$save['contact_id'] .') import failed.';

				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}
		}
	}

	//4. import gridalarms_template_layout
	$save = array();

	if (is_array($hash_array['gridalarms_template_layout']) && !empty($hash_array['gridalarms_template_layout'])) {
		foreach ($hash_array['gridalarms_template_layout'] as $layout_hash => $layout_array) {
			foreach ($struct_gridalarms_template_layout as $field_name) {
				if (db_column_exists('gridalarms_template_layout', $field_name)) {
					$save[$field_name] = isset($layout_array[$field_name]) ? $layout_array[$field_name]:'';
				}
			}

			$layout_parsed_hash = parse_xml_hash($layout_hash);

			$layout_id = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_template_layout
				WHERE hash = ?',
				array($layout_parsed_hash['hash']));

			$save['id']       = (empty($layout_id) ? '0' : $layout_id);
			$save['hash']     = $layout_parsed_hash['hash'];
			$save['alarm_id'] = $alarm_id;

			$layout_id = sql_save($save, 'gridalarms_template_layout');

			if ($layout_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_layout <' . $save['display_name'] . '> import failed.';
				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}
		}
	}

	//5. import gridalarms_template_expression_item
	$save = array();

	if (is_array ($hash_array['gridalarms_template_expression_item']) && !empty($hash_array['gridalarms_template_expression_item'])) {
		foreach ($hash_array['gridalarms_template_expression_item'] as $item_hash => $item_array) {
			foreach ($struct_gridalarms_template_expression_item as $field_name) {
				if (db_column_exists('gridalarms_template_expression_item', $field_name)) {
					$save[$field_name] = isset($item_array[$field_name]) ? $item_array[$field_name]:'';
				}
			}

			$item_parsed_hash = parse_xml_hash($item_hash);

			$item_id = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_template_expression_item
				WHERE hash = ?',
				array($item_parsed_hash['hash']));

			$save['id']            = (empty($item_id) ? '0' : $item_id);
			$save['hash']          = $item_parsed_hash['hash'] ;
			$save['expression_id'] = $expression_id;

			$item_id = sql_save($save, 'gridalarms_template_expression_item');

			if ($item_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_expression_item (expression_id=' . $save['expression_id'] . ',sequence=' . $save['sequence'] . ') import failed.';

				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}
		}
	}

	//6,7. import gridalarms_template_metric and gridalarms_template_metric_expression
	if (is_array ($hash_array['gridalarms_template_metric']) && !empty($hash_array['gridalarms_template_metric'])) {
		foreach ($hash_array['gridalarms_template_metric'] as $metric_hash => $metric_array) {
			// import gridalarms_template_metric
			$save = array();

			foreach ($struct_gridalarms_template_metric as $field_name) {
				if (db_column_exists('gridalarms_template_metric', $field_name)) {
					$save[$field_name] = isset($metric_array[$field_name]) ? $metric_array[$field_name]:'';
				}
			}

			$old_metric_id = $save['id'];

			$metric_parsed_hash = parse_xml_hash($metric_hash);

			$metric_id = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_template_metric
				WHERE hash = ?',
				array($metric_parsed_hash['hash']));

			$save['id']   = (empty($metric_id) ? '0' : $metric_id);
			$save['hash'] = $metric_parsed_hash['hash'] ;

			$metric_id = sql_save($save, 'gridalarms_template_metric');

			if ($metric_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_metric (name=' . $save['name'] . ') import failed.';

				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}

			//update metric_id in table gridalarms_template_expression_item
			db_execute_prepared('UPDATE gridalarms_template_expression_item
				SET value = ?
				WHERE expression_id = ?
				AND value = ?
				AND type = 3',
				array($metric_id, $expression_id, $old_metric_id));

			//import gridalarms_template_metric_expression
			$save = array();

			$tmp_metric_id = db_fetch_cell_prepared('SELECT metric_id
				FROM gridalarms_template_metric_expression
				WHERE expression_id = ?
				AND metric_id = ?
				LIMIT 1',
				array($expression_id, $metric_id));

			if ($tmp_metric_id > 0) {
				continue;
			}

			$save['expression_id'] = $expression_id;
			$save['metric_id']     = $metric_id;

			$me_ex_id = sql_save($save, 'gridalarms_template_metric_expression');

			if ($me_ex_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_metric_expression (expression_id =' . $expression_id . ', metric_id =' . $metric_id . ') import failed.';

				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}
		}
	}

	//8. import gridalarms_template_expression_input
	$save = array();

	if (is_array ($hash_array['gridalarms_template_expression_input']) && !empty($hash_array['gridalarms_template_expression_input'])) {
		foreach ($hash_array['gridalarms_template_expression_input'] as $input_hash => $input_array) {
			foreach ($struct_gridalarms_template_expression_input as $field_name) {
				if (db_column_exists('gridalarms_template_expression_input', $field_name)) {
					$save[$field_name] = isset($input_array[$field_name]) ? $input_array[$field_name]:'';
				}
			}

			$input_parsed_hash = parse_xml_hash($input_hash);

			$input_id = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_template_expression_input
				WHERE hash = ?',
				array($input_parsed_hash['hash']));

			$save['id']            = (empty($input_id) ? '0' : $input_id);
			$save['hash']          = $input_parsed_hash['hash'] ;
			$save['expression_id'] = $expression_id;

			$input_id = sql_save($save, 'gridalarms_template_expression_input');

			if ($input_id < 0) {
				$messages['gridalarms_import_failure']['message'] = 'gridalarms_template_expression_input (name=' . $save['name'] . ') import failed.';

				raise_message ('gridalarms_import_failure');

				$ret = -1;

				return;
			}
		}
	}
}

function export_gridalarms_template(array $alarm) : string {
	global $struct_gridalarms_template, $struct_gridalarms_template_contacts, $struct_gridalarms_template_expression;
	global $struct_gridalarms_template_expression_input,$struct_gridalarms_template_expression_item, $struct_gridalarms_template_layout;
	global $struct_gridalarms_template_metric, $struct_gridalarms_template_metric_expression;

	global $config;
	global $hash_type_codes;

	include_once($config['base_path'] . '/lib/export.php');
	$hash_type_codes_bak = $hash_type_codes;
	$hash_type_codes['gridalarms_template']                   = '18';
	$hash_type_codes['gridalarms_template_contacts']          = '19';
	$hash_type_codes['gridalarms_template_layout']            = '20';
	$hash_type_codes['gridalarms_template_expression']        = '21';
	$hash_type_codes['gridalarms_template_expression_item']   = '22';
	$hash_type_codes['gridalarms_template_metric']            = '23';
	$hash_type_codes['gridalarms_template_expression_input']  = '24';
	$hash_type_codes['gridalarms_template_metric_expression'] = '25';

	$xml_text = '';

	//1. export gridalarms_template
	$hash['gridalarms_template'] = get_hash_version('gridalarms_template') . get_hash_alert_template($alarm['id'],'alert_template');

	$xml_text .= "\t<hash_" . $hash['gridalarms_template'] . ">\n";

	foreach ($struct_gridalarms_template as $field_name) {
		$xml_text .= "\t\t<$field_name>" . xml_character_encode($alarm[$field_name]) . "</$field_name>\n";
	}

	//2. export gridalarms_template_contacts
	$xml_text .= "\t\t<gridalarms_template_contacts>\n";

	$gridalarms_template_contacts = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_contacts
		WHERE alarm_id = ?',
		array($alarm['id']));

	if (cacti_sizeof($gridalarms_template_contacts)) {
		foreach ($gridalarms_template_contacts as $contact) {
			$hash['gridalarms_template_contacts'] = get_hash_version('gridalarms_template_contacts') . get_hash_alert_template(0, 'new_hash');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_contacts'] . ">\n";

			foreach ($struct_gridalarms_template_contacts as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($contact[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_contacts'] . ">\n";
		}
	}
	$xml_text .= "\t\t</gridalarms_template_contacts>\n";

	//3. export gridalarms_template_layout
	$xml_text .= "\t\t<gridalarms_template_layout>\n";

	$gridalarms_template_layout = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_layout
		WHERE alarm_id = ?',
		array($alarm['id']));

	if (cacti_sizeof($gridalarms_template_layout)) {
		foreach ($gridalarms_template_layout as $layout) {
			$hash['gridalarms_template_layout'] = get_hash_version('gridalarms_template_layout') . get_hash_alert_template($layout['id'], 'layout_item');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_layout'] . ">\n";

			foreach ($struct_gridalarms_template_layout as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($layout[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_layout'] . ">\n";
		}
	}

	$xml_text .= "\t\t</gridalarms_template_layout>\n";

	//4. export gridalarms_template_expression
	$xml_text .= "\t\t<gridalarms_template_expression>\n";

	$gridalarms_template_expression = db_fetch_row_prepared('SELECT *
		FROM gridalarms_template_expression
		WHERE id = ?',
		array($alarm['expression_id']));

	$hash['gridalarms_template_expression'] = get_hash_version('gridalarms_template_expression') . get_hash_alert_template($gridalarms_template_expression['id'], 'expression');

	$xml_text .= "\t\t\t<hash_" . $hash["gridalarms_template_expression"] . ">\n";

	foreach ($struct_gridalarms_template_expression as $field_name) {
		$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($gridalarms_template_expression[$field_name]) . "</$field_name>\n";
	}

	$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_expression'] . ">\n";
	$xml_text .= "\t\t</gridalarms_template_expression>\n";

	//5. export gridalarms_template_expression_item
	$xml_text .= "\t\t<gridalarms_template_expression_item>\n";

	$gridalarms_template_expression_item = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($gridalarms_template_expression_item)) {
		foreach ($gridalarms_template_expression_item as $item) {
			$hash['gridalarms_template_expression_item'] = get_hash_version('gridalarms_template_expression_item') . get_hash_alert_template($item['id'], 'expression_item');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_expression_item'] . ">\n";

			foreach ($struct_gridalarms_template_expression_item as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($item[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_expression_item'] . ">\n";
		}
	}

	$xml_text .= "\t\t</gridalarms_template_expression_item>\n";

	//6. export gridalarms_template_metric
	$xml_text .= "\t\t<gridalarms_template_metric>\n";

	$gridalarms_template_metric = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_metric AS gtm
		INNER JOIN gridalarms_template_metric_expression AS gtme
		ON gtm.id=gtme.metric_id
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($gridalarms_template_metric)) {
		foreach ($gridalarms_template_metric as $metric) {
			$hash['gridalarms_template_metric'] = get_hash_version('gridalarms_template_metric') . get_hash_alert_template($metric['id'], 'metric');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_metric'] . ">\n";

			foreach ($struct_gridalarms_template_metric as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($metric[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_metric'] . ">\n";
		}
	}
	$xml_text .= "\t\t</gridalarms_template_metric>\n";

	//7. export gridalarms_template_metric_expression
	$xml_text .= "\t\t<gridalarms_template_metric_expression>\n";

	$gridalarms_template_metric_expression = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_metric_expression
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($gridalarms_template_metric_expression)) {
		foreach ($gridalarms_template_metric_expression as $mex) {
			$hash['gridalarms_template_metric_expression'] = get_hash_version('gridalarms_template_metric_expression') . get_hash_alert_template(0, 'new_hash');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_metric_expression'] . ">\n";

			foreach ($struct_gridalarms_template_metric_expression as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($mex[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_metric_expression'] . ">\n";
		}
	}

	$xml_text .= "\t\t</gridalarms_template_metric_expression>\n";

	//8. export gridalarms_template_expression_input
	$xml_text .= "\t\t<gridalarms_template_expression_input>\n";

	$gridalarms_template_expression_input = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_input
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($gridalarms_template_expression_input)) {
		foreach ($gridalarms_template_expression_input as $input) {
			$hash['gridalarms_template_expression_input'] = get_hash_version('gridalarms_template_expression_input') . get_hash_alert_template($input['id'], 'expression_input');
			$xml_text .= "\t\t\t<hash_" . $hash['gridalarms_template_expression_input'] . ">\n";

			foreach ($struct_gridalarms_template_expression_input as $field_name) {
				$xml_text .= "\t\t\t\t<$field_name>" . xml_character_encode($input[$field_name]) . "</$field_name>\n";
			}

			$xml_text .= "\t\t\t</hash_" . $hash['gridalarms_template_expression_input'] . ">\n";
		}
	}

	$xml_text .= "\t\t</gridalarms_template_expression_input>\n";

	$xml_text .= "\t</hash_" . $hash['gridalarms_template'] . ">\n";

	//restore the hash_type_codes
	$hash_type_codes = $hash_type_codes_bak;

	return $xml_text;
}

function duplicate_gridalarms_template(array $alarm, string $title_format) : void {
	$save = array();

	//1. duplicate gridalarms_template_expression
	$expression = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression
		WHERE id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($expression)) {
		foreach ($expression as $l) {
			$save         = array();
			$save         = $l;
			$save['id']   = 0;
			$save['hash'] = generate_hash();

			$new_expression_id = sql_save($save, 'gridalarms_template_expression');
		}
	}

	//2. duplicate table gridalarms_template
	$save = array();
	$save = $alarm;

	$save['id']   = 0;
	$save['hash'] = generate_hash();
	$save['name'] =	str_replace('<template_title>', $alarm['name'], $title_format);

	$save['expression_id'] = $new_expression_id;

	$gridalarms_template_id = sql_save($save, 'gridalarms_template');

	//3. duplicate table gridalarms_template_contacts
	$contacts = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_contacts
		WHERE alarm_id = ?',
		array($alarm['id']));

	if (cacti_sizeof($contacts)) {
		foreach ($contacts as $l) {
			$save = array();
			$save = $l;

			$save['alarm_id'] = $gridalarms_template_id;

			sql_save($save, 'gridalarms_template_contacts');
		}
	}

	//4. duplicate table gridalarms_template_layout
	$layouts = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_layout
		WHERE alarm_id = ?',
		array($alarm['id']));

	if (cacti_sizeof($layouts)) {
		foreach ($layouts as $l) {
			$save       = array();
			$save       = $l;
			$save['id'] = 0;
			$save['hash']     = generate_hash();
			$save['alarm_id'] = $gridalarms_template_id;

			sql_save($save, 'gridalarms_template_layout');
		}
	}

	//5. duplicate gridalarms_template_expression_input
	$inputs = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_input
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($inputs)) {
		foreach ($inputs as $l) {
			$save = array();
			$save = $l;

			$save['id']   = 0;
			$save['hash'] = generate_hash();

			$save['expression_id'] = $new_expression_id;

			sql_save($save, 'gridalarms_template_expression_input');
		}
	}

	//6,7. duplicate gridalarms_template_expression_item, gridalarms_template_metric_expression
	//8. don't need to duplicate gridalarms_template_metric. Besdie gridalarms_template_metric.name is unique index.
	$inputs = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?',
		array($alarm['expression_id']));

	if (cacti_sizeof($inputs)) {
		foreach ($inputs as $l) {
			$save = array();
			$save = $l;

			// if type =3, create new gridalarms_template_metric_expression
			if ($save['type'] == '3') {
				$metric_expression = array();

				$metric_expression['expression_id'] = $new_expression_id ;
				$metric_expression['metric_id']     = $save['value'] ;

				sql_save($metric_expression, 'gridalarms_template_metric_expression');

			}

			$save['id']   = 0;
			$save['hash'] = generate_hash();

			$save['expression_id'] = $new_expression_id;

			sql_save($save, 'gridalarms_template_expression_item');
		}
	}
}

function template_propagation(string $type, int $id) : ?bool {
	$struct_gridalarms_template_prop = array(
		'type',
		'aggregation',
		'metric',
		'warning_pct',
		'trigger_cmd_high',
		'trigger_cmd_low',
		'trigger_cmd_norm',
		'cmd_retrigger_enabled',
		'syslog_priority',
		'syslog_facility',
		'syslog_enabled',
		'format_file',
		'tcheck',
		'external_id',
		'notes'
	);

	$struct_gridalarms_template_expression_prop = array(
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

	$struct_gridalarms_template_contacts_prop = array(
		'alarm_id',
		'contact_id'
	);

	$struct_gridalarms_template_expression_input_prop = array(
		'expression_id',
		'name',
		'description',
		'value'
	);

	$struct_gridalarms_template_expression_item_prop = array(
		'expression_id',
		'sequence',
		'type',
		'value'
	);

	$struct_gridalarms_template_layout_prop = array(
		'alarm_id',
		'display_name',
		'column_name',
		'sequence',
		'sortposition',
		'sortdirection'
	);

	$struct_gridalarms_template_metric_prop = array(
		'name',
		'description',
		'type',
		'db_table',
		'db_column'
	);

	$struct_gridalarms_template_metric_expression_prop = array(
		'expression_id',
		'metric_id'
	);

	if ($type == 'gridalarms_template') {
		$gridalarms_alarms = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_alarm
			WHERE template_id = ?',
			array($id));
	} elseif ($type == 'gridalarms_alarm') {
		$gridalarms_alarms = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_alarm
			WHERE id = ?',
			array($id));
	} else {
		return true;
	}

	if (!cacti_sizeof($gridalarms_alarms)) {
		return true;
	}

	foreach ($gridalarms_alarms as $gridalarms_alarm) {
		if ($gridalarms_alarm['template_enabled'] != 'on') {
			continue;
		}

		$template_id = $gridalarms_alarm['template_id'];
		$instance_id = $gridalarms_alarm['id'];

		$instance_expression_id = $gridalarms_alarm['expression_id'];

		//1. propagate gridalarms_template to gridalarms_alarm
		$gridalarms_template = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template
			WHERE id = ?',
			array($template_id));

		if (!cacti_sizeof($gridalarms_template)) {
			continue;
		}

		$template_expression_id = $gridalarms_template['expression_id'];

		//cacti_log('DEBUG: template_id=' . $template_id .' instance_id=' . $instance_id);
		//cacti_log('DEBUG: template_expression_id=' . $template_expression_id . ' instance_expression_id=' . $instance_expression_id);

		foreach ($struct_gridalarms_template_prop as $field_name) {
			$gridalarms_alarm[$field_name] = $gridalarms_template[$field_name];
		}

		$ret_id = sql_save($gridalarms_alarm, 'gridalarms_alarm');

		if ($ret_id < 0) {
			raise_message('gridalarms_propagation_failure');

			return false;
		}

		//2. propagate gridalarms_template_expression to gridalarms_expression
		$gridalarms_expression = db_fetch_row_prepared('SELECT *
			FROM gridalarms_expression
			WHERE id = ?
			AND template_id = ?',
			array($instance_expression_id, $template_id));

		$gridalarms_template_expression = db_fetch_row_prepared('SELECT *
			FROM gridalarms_template_expression
			WHERE id = ?',
			array($template_expression_id));

		if (!cacti_sizeof($gridalarms_expression) || !cacti_sizeof($gridalarms_template_expression)) {
			return true;
		}

		foreach ($struct_gridalarms_template_expression_prop as $field_name) {
			$gridalarms_expression[$field_name] = $gridalarms_template_expression[$field_name];
		}

		$ret_id = sql_save($gridalarms_expression, 'gridalarms_expression');

		if ($ret_id <0) {
			raise_message ('gridalarms_propagation_failure');
			return false;
		}

		//3. propagate gridalarms_template_contacts to gridalarms_alarm_contacts
		db_execute_prepared('DELETE FROM gridalarms_alarm_contacts
			WHERE alarm_id = ?',
			array($instance_id));

		db_execute_prepared("INSERT INTO gridalarms_alarm_contacts
			SELECT $instance_id, contact_id
			FROM gridalarms_template_contacts
			WHERE alarm_id = ?",
			array($template_id));

		//4. propagate gridalarms_template_layout to gridalarms_alarm_layout
		db_execute_prepared('DELETE FROM gridalarms_alarm_layout
			WHERE alarm_id = ?
			AND template_id = ?',
			array($instance_id, $template_id));

		db_execute_prepared("INSERT INTO gridalarms_alarm_layout
			(id, alarm_id, template_id, display_name, column_name, sequence, sortposition, sortdirection, type, units, align, digits, autoscale)
			SELECT 0, $instance_id, $template_id, display_name,
			column_name, sequence, sortposition, sortdirection, type, units, align, digits, autoscale
			FROM gridalarms_template_layout
			WHERE alarm_id = ?",
			array($template_id));

		//5. propogate gridalarms_template_expression_input to gridalarms_expression_input
		//db_execute("delete from gridalarms_expression_input where alarm_id= $instance_id and template_id= $template_id and expression_id= $instance_expression_id");
		//db_execute("insert into gridalarms_expression_input select 0, $instance_id, $template_id,$instance_expression_id, name,
		//			description, value from gridalarms_template_expression_input  where expression_id=$template_expression_id");

		$gridalarms_template_expression_inputs = db_fetch_assoc_prepared('SELECT *
			FROM gridalarms_template_expression_input
			WHERE expression_id = ?',
			array($template_expression_id));

		if (cacti_sizeof($gridalarms_template_expression_inputs)) {
			foreach ($gridalarms_template_expression_inputs as $t_input) {
				$tname  = $t_input['name'];
				$tdesc  = $t_input['description'];
				$tvalue = $t_input['value'];

				$has_input = db_fetch_cell_prepared('SELECT count(*)
					FROM gridalarms_expression_input
					WHERE alarm_id = ?
					AND template_id = ?
					AND expression_id = ?
					AND name = ?',
					array($instance_id, $template_id, $instance_expression_id, $tname));

				if (!$has_input) {
					db_execute_prepared('INSERT INTO gridalarms_expression_input
						VALUES (0, ?, ?, ?, ?, ?, ?)',
						array(
							$instance_id,
							$template_id,
							$instance_expression_id,
							$tname,
							$tdesc,
							$tvalue
						)
					);
				}
			}
		}

		//6. propogate gridalarms_template_expression_item to gridalarms_expression_item
		//7. propogate gridalarms_template_metric to gridalarms_metric
		//8. propogate gridalarms_template_metric_expression to gridalarms_metric_expression
		push_out_metrics_items($template_expression_id, $instance_expression_id, $instance_id, $template_id);
	}

	return true;
}

function push_out_layout(int $alarm_id, int $template_id) : void {
	/* remove any existing entries */
	db_execute_prepared('DELETE FROM gridalarms_alarm_layout
		WHERE alarm_id = ?',
		array($alarm_id));

	/* graph the layout from the template and update the layout */
	$layouts = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_layout
		WHERE alarm_id = ?',
		array($template_id));

	if (cacti_sizeof($layouts)) {
		foreach ($layouts as $l) {
			$save = $l;

			unset($save['hash']);
			unset($save['id']);

			$save['alarm_id']    = $alarm_id;
			$save['template_id'] = $template_id;

			sql_save($save, 'gridalarms_alarm_layout');
		}
	}
}

function push_out_metrics_items(int $template_expression_id, int $expression_id, int $alarm_id, int $template_id) : void {
	/* handle metrics first, it will simplify things */
	$metrics = db_fetch_assoc_prepared('SELECT gtm.*
		FROM gridalarms_template_metric AS gtm
		INNER JOIN gridalarms_template_metric_expression AS gtme
		ON gtme.metric_id = gtm.id
		WHERE gtme.expression_id = ?',
		array($template_expression_id));

	$mappings = array();

	if (cacti_sizeof($metrics)) {
		foreach ($metrics as $m) {
			$oldid = $m['id'];
			$save  = $m;

			unset($save['id']);
			unset($save['hash']);

			/* check to see if the metric already exists */
			$mid = db_fetch_cell_prepared('SELECT id
				FROM gridalarms_metric
				WHERE db_table = ?
				AND db_column = ?',
				array($m['db_table'], $m['db_column']));

			if (empty($mid)) {
				$new_name   = $save['name'];
				$new_desc   = $save['description'];
				$new_type   = $save['type'];
				$new_table  = $save['db_table'];
				$new_column = $save['db_column'];

				db_execute_prepared('REPLACE INTO gridalarms_metric VALUES
					(0, ?, ? ,? ,? ,?)',
					array($new_name, $new_desc, $new_type, $new_table, $new_column));

				$mid = db_fetch_insert_id();
			}

			/* add to the mapping table */
			db_execute_prepared('REPLACE INTO gridalarms_metric_expression
				(metric_id, expression_id) VALUES
				(?, ?)',
				array($mid, $expression_id));

			/* we keep mappings so we can handle expressions type=3 */
			$mappings[$oldid] = $mid;
		}
	}

	/* First delete all existing data in gridalarms_expression_item*/
	db_execute_prepared('DELETE FROM gridalarms_expression_item
		WHERE alarm_id = ?
		AND template_id = ?
		AND expression_id = ?',
		array($alarm_id, $template_id, $expression_id));

	/* now get the expression items now that we know the mapping from old to new */
	$items = db_fetch_assoc_prepared('SELECT *
		FROM gridalarms_template_expression_item
		WHERE expression_id = ?',
		array($template_expression_id));

	if (cacti_sizeof($items)) {
		foreach ($items as $i) {
			$save = $i;

			unset($save['id']);
			unset($save['hash']);

			$save['alarm_id']      = $alarm_id;
			$save['template_id']   = $template_id;
			$save['expression_id'] = $expression_id;

			/* if it's a metric, we need to instantiate it too */
			if ($save['type'] == 3) {
				$save['value'] = $mappings[$save['value']];
			}

			sql_save($save, 'gridalarms_expression_item');
		}
	}

	/*re-genereate gridalarms_metric_expression data*/
	db_execute_prepared('DELETE FROM gridalarms_metric_expression
		WHERE expression_id = ?',
		array($expression_id));

	db_execute_prepared('REPLACE INTO gridalarms_metric_expression
		SELECT expression_id, value
		FROM gridalarms_expression_item
		WHERE expression_id = ?
		AND type = 3',
		array($expression_id));

	/* remove non-used metric data*/
	$metrics = db_fetch_assoc('SELECT id FROM gridalarms_metric');

	if (cacti_sizeof($metrics)) {
		foreach ($metrics as $m) {
			$metric2 = db_fetch_cell_prepared('SELECT count(*)
				FROM gridalarms_metric_expression
				WHERE metric_id = ?',
				array($m['id']));

			if (!$metric2) {
				db_execute_prepared('DELETE FROM gridalarms_metric
					WHERE id = ?',
					array($m['id']));
			}
		}
	}
}

/**
 * this function will re-template an alarm, which can be distructive
 *
 * @param  array $alarm defines the alarm to retemplate
 * @param  int   $template_id the template id of the object
 @ @param  bool  $repush should the object be repushed
 *
 * @return int the id pushed out
 */
function push_out_template_to_alarm(array $alarm, int $template_id = 0, bool $repush = false) : int {
	$columns = array_rekey(
		db_fetch_assoc('SHOW COLUMNS
			FROM gridalarms_alarm'),
		'Field', 'Field'
	);

	$id = 0;

	if (cacti_sizeof($alarm)) {
		if ((isset($alarm['template_id']) && isset($alarm['id'])) || (!empty($template_id))) {
			/* get the template */
			if ($repush) {
				$save = db_fetch_row_prepared('SELECT *
					FROM gridalarms_template
					WHERE id = ?',
					array($template_id));

				$oldalarm = db_fetch_row_prepared('SELECT *
					FROM gridalarms_alarm
					WHERE id = ?',
					array($alarm['id']));

				$save['id']               = $alarm['id'];
				$save['template_id']      = isset($alarm['template_id'])    ? $alarm['template_id']:$oldalarm['template_id'];
				$save['template_enabled'] = 'on';
				$save['name']             = isset($alarm['name'])           ? $alarm['name']:$oldalarm['name'];
				$save['clusterid']        = isset($alarm['clusterid'])      ? $alarm['clusterid']:$oldalarm['clusterid'];
				$save['expression_id']    = isset($alarm['expression_id'])  ? $alarm['expression_id']:$oldalarm['expression_id'];
				$save['email_subject']    = isset($alarm['email_subject'])  ? $alarm['email_subject']:$oldalarm['email_subject'];
				$save['email_body']       = isset($alarm['email_body'])     ? $alarm['email_body']:$oldalarm['email_body'];
				$save['format_file']      = isset($alarm['format_file'])    ? $alarm['format_file']:$oldalarm['format_file'];
				$save['notify_users']     = isset($alarm['notify_users'])   ? $alarm['notify_users']:$oldalarm['notify_users'];
				$save['notify_alert']     = isset($alarm['notify_alert'])   ? $alarm['notify_alert']:$oldalarm['notify_alert'];
				$save['notify_extra']     = isset($alarm['notify_extra'])   ? $alarm['notify_extra']:$oldalarm['notify_extra'];
				$save['req_ack']          = isset($alarm['req_ack'])        ? $alarm['req_ack']:$oldalarm['req_ack'];
				$save['syslog_enabled']   = isset($alarm['syslog_enabled']) ? $alarm['syslog_enabled']:$oldalarm['syslog_enabled'];
				$save['exempt']           = isset($alarm['exempt'])         ? $alarm['exempt']:$oldalarm['exempt'];
				$save['external_id']      = isset($alarm['external_id'])    ? $alarm['external_id']:$oldalarm['external_id'];
				$save['notes']            = isset($alarm['notes'])          ? $alarm['notes']:$oldalarm['notes'];
				$alarm = array();

				/* unset non used variables */
				unset($save['hash']);
			} elseif (isset($alarm['id'])) {
				$save = db_fetch_row_prepared('SELECT *
					FROM gridalarms_alarm
					WHERE id = ?',
					array($alarm['id']));

				if (!isset($alarm['template_enabled'])) {
					$save['template_enabled'] = '';
				} else {
					$save['template_enabled'] = 'on';
				}
			} else {
				$save = db_fetch_row_prepared('SELECT *
					FROM gridalarms_template
					WHERE id = ?',
					array($template_id));

				$save['template_id']      = $template_id;
				$save['template_enabled'] = (isset($alarm['template_enabled']) ? 'on':'');

				/* unset non used variables */
				unset($save['id']);
				unset($save['hash']);
				unset($save['expression_id']);
			}

			// Colunns that always follow the template
			foreach ($columns as $column) {
				switch($column) {
				case 'id':
				case 'template_id':
				case 'clusterid':
				case 'expression_id':
				case 'frequency':
				case 'alarm_type':
				case 'base_time':
				case 'type':
				case 'aggregation':
				case 'alarm_hi':
				case 'alarm_low':
				case 'alarm_fail_trigger':
				case 'time_hi':
				case 'time_low':
				case 'time_fail_trigger':
				case 'time_fail_length':
				case 'warning_pct':
				case 'repeat_alert':
				case 'notify_alert':
				case 'syslog_priority':
				case 'syslog_facility':
					if (isset($alarm[$column])) {
						input_validate_input_number($alarm[$column]);
						$save[$column] = $alarm[$column];
					}

					break;
				case 'name':
				case 'base_time_display':
				case 'metric':
				case 'trigger_cmd_high':
				case 'trigger_cmd_low':
				case 'trigger_cmd_norm':
				case 'notify_extra':
				case 'email_body':
				case 'format_file':
				case 'email_subject':
				case 'external_id':
				case 'notes':
					if (isset($alarm[$column])) {
						$save[$column] = $alarm[$column];
					}

					break;
				case 'notify_users':
				case 'req_ack':
				case 'exempt':
				case 'restored_alert':
					if (isset($alarm[$column]) && $alarm[$column] == 'on') {
						$save[$column] = 'on';
					} else {
						$save[$column] = '';
					}

					break;
				case 'syslog_enabled':
				case 'cmd_retrigger_enabled':
					if (isset($alarm[$column])) {
						if ($alarm[$column] == 'on') {
							$save[$column] = 'on';
						} else {
							$save[$column] = '';
						}
					}

					break;
				case 'notify_cluster_admin':
					if (isset($alarm['notify_cluster_admin']) && $alarm['notify_cluster_admin'] == 'on') {
						$save['notify_cluster_admin'] = 1;
					} else {
						$save['notify_cluster_admin'] = 0;
					}

					break;
				}
			}
		}

		if (isset($save['alarm_hi']) && isset($save['alarm_low']) &&
			trim($save['alarm_hi']) != '' && trim($save['alarm_low']) != '' &&
			round($save['alarm_low'], 2) >= round($save['alarm_hi'], 2)) {

			raise_message('gridalarms_alarm_hilo_imp');

			if (!empty($alarm['id'])) {
				header('Location: gridalarms_alarm_edit.php?header=false&tab=' . $alarm['tab'] . '&id=' . $alarm['id']);
			} else {
				header('Location: gridalarms_alarm.php?header=false');
			}

			exit;
		}

		if ((isset($save['alarm_hi']) && trim($save['alarm_hi']) != '' && !is_numeric($save['alarm_hi'])) ||
			(isset($save['alarm_low']) && trim($save['alarm_low']) != '' && !is_numeric($save['alarm_low'])) ||
			(isset($save['time_hi']) && trim($save['time_hi']) != '' && !is_numeric($save['time_hi'])) ||
			(isset($save['time_low']) && trim($save['time_low']) != '' && !is_numeric($save['time_low']))) {

			raise_message('gridalarms_alarm_hilo_numeric');

			header('Location: gridalarms_alarm_edit.php?header=false&tab=' . $alarm['tab'] . '&id=' . $alarm['id']);

			exit;
		}

		if (isset($save['base_time_display'])) {
			if (strtotime($save['base_time_display'])=== false) {
				raise_message('gridalarms_invalid_timestamp');

				header('Location: gridalarms_alarm_edit.php?header=false&tab=' . $alarm['tab'] . '&id=' . $alarm['id']);

				exit;
			} else {
				$save['base_time'] = strtotime($save['base_time_display']);
			}
		}

		$save['trigger_cmd_high'] 	= str_replace('"','\'', $save['trigger_cmd_high']);
		$save['trigger_cmd_low'] 	= str_replace('"','\'', $save['trigger_cmd_low']);
		$save['trigger_cmd_norm'] 	= str_replace('"','\'', $save['trigger_cmd_norm']);

		$id = sql_save($save , 'gridalarms_alarm');

		if ($repush) {
			/* update the layout first */
			push_out_layout($id, $template_id);

			/* update the expression name and description */
			$expression = get_template_expression_by_alarm_id($template_id);
			db_execute_prepared('UPDATE gridalarms_expression
				SET name = ?, description = ?
				WHERE alarm_id = ?',
				array($expression['name'], $expression['description'], $id));

			/* update the expression items, my removing metrics */
			db_execute_prepared('DELETE FROM gridalarms_metric
				WHERE id IN(
					SELECT metric_id
					FROM (
						SELECT metric_id, count(*) AS number
						FROM gridalarms_metric AS gm
						INNER JOIN gridalarms_metric_expression AS gme
						ON gm.id=gme.metric_id
						WHERE expression_id = ?
						GROUP BY metric_id
						HAVING number=1
					) AS rs
				)',
				array($save['expression_id']));

			/* removing mapping table entries */
			db_execute_prepared('DELETE FROM gridalarms_metric_expression
				WHERE expression_id = ?',
				array($save['expression_id']));

			/* remove items */
			db_execute_prepared('DELETE FROM gridalarms_expression_item
				WHERE expression_id = ?',
				array($save['expression_id']));

			/* repopulate the metrics and metric items */
			push_out_metrics_items($expression['id'], $save['expression_id'], $save['id'], $template_id);

			/* lastly, remove any unknown input items */
			db_execute_prepared('DELETE FROM gridalarms_expression_input
				WHERE name NOT IN (
					SELECT name
					FROM gridalarms_template_expression_input
					WHERE expression_id = ?
				)',
				array($expression['id']));

			/* insert new input items into the input items table */
			$inputs = db_fetch_assoc_prepared('SELECT name
				FROM gridalarms_template_expression_input
				WHERE expression_id = ?
				AND name NOT IN (
					SELECT name
					FROM gridalarms_expression_input
					WHERE expression_id = ?
				)',
				array($expression['id'], $save['expression_id']));

			if (cacti_sizeof($inputs)) {
				foreach ($inputs as $i) {
					db_execute_prepared('INSERT INTO gridalarms_expression_input
						(expression_id, name, description, value) VALUES
						(?, ?, ?, ?)',
						array($save['expression_id'], $i['name'], $i['description'], $i['value']));
				}
			}
		} elseif ($id) {
			if (!empty($template_id)) {
				$template_expression_id = $save['expression_id'];

				$save = get_template_expression_by_id($template_expression_id);

				unset($save['id']);
				unset($save['hash']);

				$save['id']          = 0;
				$save['alarm_id']    = $id;
				$save['template_id'] = $template_id;

				$expression_id = sql_save($save, 'gridalarms_expression');

				if ($expression_id) {
					/* now that we have an expression created, set it */
					db_execute_prepared('UPDATE gridalarms_alarm
						SET expression_id = ?
						WHERE id= ?',
						array($expression_id, $id));

					push_out_layout($id, $template_id);

					push_out_metrics_items($template_expression_id, $expression_id, $id, $template_id);

					/* lastly, loop through each post and save custom items */
					foreach ($alarm as $var => $val) {
						if (preg_match('/^custom_entry_([0-9]+)$/', $var, $matches)) {
							/* ================= input validation ================= */
							input_validate_input_number($matches[1]);
							/* ==================================================== */

							$save = db_fetch_row_prepared('SELECT *
								FROM gridalarms_template_expression_input
								WHERE id = ?',
								array($matches[1]));

							$save['alarm_id']      = $id;
							$save['template_id']   = $template_id;
							$save['expression_id'] = $expression_id;
							$save['value']         =  $val;

							unset($save['id']);
							unset($save['hash']);

							sql_save($save, 'gridalarms_expression_input');
						}
					}
				}

				raise_message('gridalarms_alarm_save_new');
			} else {
				/* loop through each post and save custom items */
				foreach ($alarm as $var => $val) {
					if (preg_match("/^custom_entry_([0-9]+)$/", $var, $matches)) {
						/* ================= input validation ================= */
						input_validate_input_number($matches[1]);
						/* ==================================================== */

						db_execute_prepared('UPDATE gridalarms_expression_input
							SET value = ?
							WHERE id = ?',
							array($val, $matches[1]));
					}
				}

				raise_message('gridalarms_alarm_save');
			}
		}
	}

	return $id;
}

/**
 * Replaces the title from an alarms components
 *
 * @param  array    $alarm the alarm
 *
 * @return bool|int 1 - title replacement successful, -1 - failed to do the title replacement.
 */
function do_title_replacement(array &$alarm) : bool|int {
	$title     = $alarm['name'];
	$ret_title = '';

	$clusterid   = $alarm['clusterid'];
	$clustername = __('All Clusters', 'gridalarms');

	if (!empty($clusterid)) {
		$clustername = get_clustername($clusterid);
		if (empty($clustername)) {
			$clustername = __('All Clusters', 'gridalarms');
		}
	}

	$ret_title = str_replace('|alert_clusterid|',   "'" . $clusterid   . "'", $title);
	$ret_title = str_replace('|alert_clustername|', "'" . $clustername . "'", $ret_title);

	$input_names = array();
	foreach ($alarm as $var => $val) {
		if (preg_match('/^custom_entry_([0-9]+)$/', $var, $matches)) {
			$tinput = db_fetch_row_prepared('SELECT *
				FROM gridalarms_template_expression_input
				WHERE id = ?',
				array($matches[1]));

			if (!empty($tinput['name'])) {
				$tname = $tinput['name'];
				$input_names[$tname] = $val;
			}
		}
	}

	if (preg_match_all('/\|input_(.*?)\|/', $ret_title, $matches2)) {
		foreach ($matches2[1] as $m2) {
			$m2 = trim ($m2);

			if ($input_names[$m2]) {
				$ret_title = str_replace('|input_' . $m2 . '|', "'" . $input_names[$m2] . "'", $ret_title);
			} else {
				raise_message ('gridalarms_title_replacement_failure');
				return -1;
			}
		}
	}

	$alarm['name'] = $ret_title;

	return true;
}

/**
 * returns the current unique hash for a alert template
 *
 * @param  int    $id the ID of the alert template to return a hash for
 * @param  string $sub_type the hash for a particlar sub-type of this type
 *
 * @return string the hash of the template
 */
function get_hash_alert_template(int $id, string $sub_type = 'alert_template') : string {
	if (!empty($id)) {
		if ($sub_type == 'alert_template') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template
				WHERE id= ?',
				array($id));
		} elseif ($sub_type == 'expression') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template_expression
				WHERE id = ?',
				array($id));
		} elseif ($sub_type == 'expression_item') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template_expression_item
				WHERE id = ?',
				array($id));
		} elseif ($sub_type == 'layout_item') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template_layout
				WHERE id = ?',
				array($id));
		} elseif ($sub_type == 'metric') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template_metric
				WHERE id = ?',
				array($id));
		} elseif ($sub_type == 'expression_input') {
			$hash = db_fetch_cell_prepared('SELECT hash
				FROM gridalarms_template_expression_input
				WHERE id = ?',
				array($id));
		}

		if (preg_match('/[a-fA-F0-9]{32}/', $hash)) {
			return $hash;
		}
	}

	return generate_hash();
}

function check_gridalarms_db_upgrade() : void {
	if (read_config_option('gridalarms_db_upgrade', true) == '1') {
		unset_request_var('drp_action');
		unset_request_var('action');
	}
}

/* gridalarms_save_button - takes a form name and encode fields array and submits the form with the
	selected form fields base64 encoded
*/
function gridalarms_save_button(string $form_name, bool $encode_fields) : void {
	?>
	<table class='cactiTable'>
		<tr>
			<td class='right'>
				<input type='hidden' name='action' value='save'>
				<input type='button' value='Save' onClick='submitFunction()'>
				<script type='text/javascript'>
				keyStr = 'ABCDEFGHIJKLMNOP' +
					'QRSTUVWXYZabcdef' +
					'ghijklmnopqrstuv' +
					'wxyz0123456789+/' +
					'=';

				function submitFunction() {
					var formVariables = [ '<?php print implode("','", $encode_fields);?>' ];

					for (i in formVariables) {
						var entry = formVariables[i];
						if ($('#'+entry)) {
							$('#'+entry).attr('disabled', 'disabled');
							$('#'+entry+'_hidden').val(encode64(encodeURIComponent($('#'+entry).val())));
						}

					}

					$('form[name="<?php print $form_name;?>"]').submit();
				}

				function encode64(mystring) {
					var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
					var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
					ac = 0,
					enc = '',
					tmp_arr = [];

					if (!mystring) {
						return mystring;
					}

					do {
						o1 = mystring.charCodeAt(i++);
						o2 = mystring.charCodeAt(i++);
						o3 = mystring.charCodeAt(i++);

						bits = o1 << 16 | o2 << 8 | o3;

						h1 = bits >> 18 & 0x3f;
						h2 = bits >> 12 & 0x3f;
						h3 = bits >> 6 & 0x3f;
						h4 = bits & 0x3f;

						tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
					} while (i < mystring.length);

					enc = tmp_arr.join('');

					var r = mystring.length % 3;

					return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
				}
				</script>
			</td>
		</tr>
	</table>
	</form>
	<?php
}

function gridalarms_do_import(string $xml_file) : int {
    $fp = fopen($xml_file, 'r');
    $xml_data = fread($fp, filesize($xml_file));
    fclose($fp);

    $debug_data = 0;

    // check gridalarms template xml
    $dup_alarm_array = array();

    $ret = check_import_xml_gridalarms_template($xml_data, $dup_alarm_array);

    if ($ret <0) {                                   //show error message
        $debug_data = -1;
    } elseif ($ret == 0) {                          // no duplicate alert hash code, do the import
        import_xml_gridalarms_template($xml_data);

        $debug_data = 0;
    } else {
        import_xml_gridalarms_template($xml_data);

        $debug_data = 1;
    }

    return $debug_data;
}

/**
 * call back function to print column options
 */
function getcolumns() : void {
	$db_columns = array();

	if (isset_request_var('db_table')) {
		$db_columns = get_table_columns(get_request_var('db_table'));
	}

	$db_column_values = array_values($db_columns);

	if (cacti_sizeof($db_column_values)) {
		foreach($db_column_values as $value) {
			print "<option value='" . $value . "'>" . $value . '</option>';
		}
	}
}

/**
 * Callback function to return the tables base on the type selected
 */
function gettables() : void {
	$db_tables = array();

	if (isset_request_var('type')) {
		$db_tables = get_db_tables_form(get_request_var('type'));
	}

	$db_table_values = array_values($db_tables);

	if (cacti_sizeof($db_table_values)) {
		foreach($db_table_values as $value) {
			print "<option value='" . $value . "'>" . $value . '</option>';
		}
	}
}
