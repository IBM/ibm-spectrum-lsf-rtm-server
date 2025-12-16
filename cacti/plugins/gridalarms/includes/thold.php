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

function gridalarms_th_edit_save_thold($save) {
	if ($save['template_enabled'] != 'on') {
		// Action Type
		$save['gridadmin_action_level'] = get_request_var('gridadmin_action_level');

		// Host actions
		$save['host_action_high'] = get_request_var('host_action_high');
		if($save['host_action_high'] == 1) {//open
			$save['host_action_high_lockid'] = get_request_var('host_action_high_open_lockid');
		} else if($save['host_action_high'] == 2) {//close
			form_input_validate(trim(get_nfilter_request_var('host_action_high_close_lockid')),   'host_action_high_close_lockid', '^[A-Za-z0-9]+$', true, '148');
			$save['host_action_high_lockid'] = get_request_var('host_action_high_close_lockid');
		}
		$save['host_action_low']  = get_request_var('host_action_low');
		if($save['host_action_low'] == 1) {//open
			$save['host_action_low_lockid'] = get_request_var('host_action_low_open_lockid');
		} else if($save['host_action_low'] == 2) {//close
			form_input_validate(trim(get_nfilter_request_var('host_action_low_close_lockid')),   'host_action_low_close_lockid', '^[A-Za-z0-9]+$', true, '148');
			$save['host_action_low_lockid'] = get_request_var('host_action_low_close_lockid');
		}

		// Host actions high
		$save['job_action_high'] = get_request_var('job_action_high');
		$save['job_target_high'] = get_request_var('job_target_high');
		$save['job_signal_high'] = get_request_var('job_signal_high');

		// Host actions low
		$save['job_action_low'] = get_request_var('job_action_low');
		$save['job_target_low'] = get_request_var('job_target_low');
		$save['job_signal_low'] = get_request_var('job_signal_low');
	} else {
		$tdata = db_fetch_row_prepared('SELECT gridadmin_action_level, host_action_high, host_action_low,
			job_action_high, job_target_high, job_signal_high,
			job_action_low, job_target_low, job_signal_low
			FROM thold_template
			WHERE id = ?',
			array($save['thold_template_id']));

		if (cacti_sizeof($tdata)) {
			// Action Type
			$save['gridadmin_action_level'] = $tdata['gridadmin_action_level'];

			// Host actions
			$save['host_action_high'] = $tdata['host_action_high'];
			$save['host_action_low']  = $tdata['host_action_low'];

			// Host actions high
			$save['job_action_high'] = $tdata['job_action_high'];
			$save['job_target_high'] = $tdata['job_target_high'];
			$save['job_signal_high'] = $tdata['job_signal_high'];

			// Host actions low
			$save['job_action_low'] = $tdata['job_action_low'];
			$save['job_target_low'] = $tdata['job_target_low'];
			$save['job_signal_low'] = $tdata['job_signal_low'];
		}
	}

	return $save;
}

function gridalarms_th_edit_form_array($form_array) {
	global $signal;

	unset($signal[0]);

	if (isset($form_array['host_id']['value']) && $form_array['host_id']['value'] > 0) {
		$clusterid = db_fetch_cell_prepared('SELECT clusterid
			FROM host
			WHERE id = ?',
			array($form_array['host_id']['value']));

		$queue = db_fetch_cell_prepared('SELECT queuename
			FROM grid_queues
			WHERE clusterid = ?
			ORDER BY queuename
			LIMIT 1',
			array($clusterid));

		$queue_sql = 'SELECT queuename AS id, queuename AS name FROM grid_queues WHERE clusterid =' . $clusterid . ' ORDER BY queuename';
	} else {
		$queue = db_fetch_cell('SELECT queuename
			FROM grid_queues
			ORDER BY queuename
			LIMIT 1');

		$queue_sql = 'SELECT DISTINCT queuename AS id, queuename AS name FROM grid_queues ORDER BY queuename';
	}

	$lockids = array('all' => 'all');
	$lockids += array_rekey(db_fetch_assoc('SELECT DISTINCT lockid AS id, lockid AS name FROM grid_host_closure_lockids ORDER BY lockid'), 'id', 'name');

	if (isset($form_array['local_data_id']['value'])) {
		$type_id = db_fetch_cell_prepared('SELECT di.type_id
			FROM data_template_data AS dtd
			INNER JOIN data_input AS di
			ON dtd.data_input_id = di.id
			WHERE dtd.local_data_id = ?',
			array($form_array['local_data_id']['value']));

		if ($type_id != DATA_INPUT_TYPE_PHP_SCRIPT_SERVER) {
			return $form_array;
		}
	} elseif (isset($form_array['data_template_id']['value'])) {
		$type_id = db_fetch_cell_prepared('SELECT di.type_id
			FROM data_template_data AS dtd
			INNER JOIN data_input AS di
			ON dtd.data_input_id = di.id
			WHERE dtd.data_template_id = ?
			AND dtd.local_data_id = 0
			LIMIT 1',
			array($form_array['data_template_id']['value']));

		if ($type_id != DATA_INPUT_TYPE_PHP_SCRIPT_SERVER) {
			return $form_array;
		}
	} else {
		return $form_array;
	}

	$form_array += array(
		'event_trigger_gridadmin_type' => array(
			'friendly_name' => __('LSF Event Triggering', 'gridalarms'),
			'method' => 'spacer'
		),
		'gridadmin_action_level' => array(
			'friendly_name' => __('LSF Event Triggering Type', 'gridalarms'),
			'method' => 'drop_array',
			'description' => __('Choose the type of LSF Action that should be taken upon a Threshold breach.  NOTE: This only works for Script Server Data Input Methods.  Additionally, to support returning objects for both job and host actions, the Data Input Script must include an optional last positional \'detail\' parameter that when passed 1 will return the list of objects that are breached.  This would include either host or job information currently.  The response column names for hosts must include the \'HostName\' column header, and for jobs must The \'JobID\' column with the job being formatted \'jobid[indexid]\' with the indexid being optional.', 'gridalarms'),
			'array' => array(
				'none' => __('N/A', 'gridalarms'),
				'job'  => __('Job', 'gridalarms'),
				'host' => __('Host', 'gridalarms')
			),
			'value' => '|arg1:gridadmin_action_level|'
	    ),
		'event_trigger_gridadmin_host' => array(
			'friendly_name' => __('LSF Host Level Triggers', 'gridalarms'),
			'method' => 'spacer'
		),
		'host_action_high' => array(
			'friendly_name' => __('LSF Host Action (High Threshold)', 'gridalarms'),
			'method' => "drop_array",
			'description' => __('Choose the action to be taken on the Host when high Threshold is breached', 'gridalarms'),
			'array' => array(
				'1' => __('Open', 'gridalarms'),
				'2' => __('Close', 'gridalarms')
			),
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:host_action_high|'
		),
		'host_action_high_open_lockid' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Select LockId for Open', 'gridalarms'),
			'description' => __('LockId that are appended to LSF with the -i option. Select the LockId for Open. Default value all.', 'gridalarms'),
			'array' => $lockids,
			'default' => 'all',
			'value' => '|arg1:host_action_high_lockid|'
		),
		'host_action_high_close_lockid' => array(
			'friendly_name' => __('Input LockId for Close', 'gridalarms'),
			'method' => 'textbox',
			'default' => '',
			'max_length' => 128,
			'size' => 15,
			'description' => __('LockId that are appended to LSF with the -i option. Characters in [A-Za-z0-9]. This option will be ignored for the old LSF, which do not support -i option', 'gridalarms'),
			'value' => '|arg1:host_action_high_lockid|'
			//'value' => isset($thold_data['thold_warning_hi']) ? $thold_data['thold_warning_hi'] : ''
		),
		'host_action_low' => array(
			'friendly_name' => __('LSF Host Action (Low Threshold)', 'gridalarms'),
			'method' => "drop_array",
			'description' => __('Choose the action to be taken on the Host when low Threshold is breached', 'gridalarms'),
			'array' => array(
				'1' => __('Open', 'gridalarms'),
				'2' => __('Close', 'gridalarms')
			),
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:host_action_low|'
		),
		'host_action_low_open_lockid' => array(
			'method' => 'drop_array',
			'friendly_name' => __('Select LockId for Open', 'gridalarms'),
			'description' => __('LockId that are appended to LSF with the -i option. Select the LockId for Open. Default value all.', 'gridalarms'),
			'array' => $lockids,
			'default' => 'all',
			'value' => '|arg1:host_action_low_lockid|'
		),
		'host_action_low_close_lockid' => array(
			'friendly_name' => __('Input LockId for Close', 'gridalarms'),
			'method' => 'textbox',
			'default' => '',
			'max_length' => 128,
			'size' => 15,
			'description' => __('LockId that are appended to LSF with the -i option. Characters in [A-Za-z0-9]. This option will be ignored for the old LSF, which do not support -i option', 'gridalarms'),
			'value' => '|arg1:host_action_low_lockid|'
			//'value' => isset($thold_data['thold_warning_hi']) ? $thold_data['thold_warning_hi'] : ''
		),
		'event_trigger_gridadmin_job' => array(
			'friendly_name' => __('Event Triggering (Gridadmin Job Level Trigger)', 'gridalarms'),
			'method' => 'spacer'
		),
		'job_action_high' => array(
			'friendly_name' => __('LSF Job Action (High Threshold)', 'gridalarms'),
			'method' => 'drop_array',
			'description' => __('Choose the action to be taken on the Host when high Threshold is breached', 'gridalarms'),
			'array' => array(
				'1' => __('BSwitch', 'gridalarms'),
				'2' => __('BKill', 'gridalarms'),
				'3' => __('Force Kill', 'gridalarms'),
				'4' => __('Signal Kill', 'gridalarms'),
				'5' => __('Kill as DONE', 'gridalarms')
			),
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_action_high|'
		),
		'job_target_high' => array(
			'method' => 'drop_sql',
			'sql' => $queue_sql,
			'friendly_name' => __('LSF Job Target (High Threshold)', 'gridalarms'),
			'description' => __('Choose the target queue to transfer all the jobs. Required for Bswitch action only.', 'gridalarms'),
			'array' => $queue,
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_target_high|'
		),
		'job_signal_high' => array(
			'friendly_name' => __('LSF Job Kill Signal (High Threshold)', 'gridalarms'),
			'method' => 'drop_array',
			'description' => __('Choose the signal to kill the jobs. Required for Signal Kill action only.', 'gridalarms'),
			'array' => $signal,
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_signal_high|'
		),
		'job_action_low' => array(
			'friendly_name' => __('LSF Job Action (Low Threshold)', 'gridalarms'),
			'method' => 'drop_array',
			'description' => __('Choose the action to be taken on the Host when low Threshold is breached', 'gridalarms'),
			'array' => array(
				'1' => __('BSwitch', 'gridalarms'),
				'2' => __('BKill', 'gridalarms'),
				'3' => __('Force Kill', 'gridalarms'),
				'4' => __('Signal Kill', 'gridalarms'),
				'5' => __('Kill as DONE', 'gridalarms')
			),
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_action_low|'
		),
		'job_target_low' => array(
			'method' => 'drop_sql',
			'sql' => $queue_sql,
			'friendly_name' => __('LSF Job Target (Low Threshold)', 'gridalarms'),
			'description' => __('Choose the target queue to transfer all the jobs. Required for Bswitch action only.', 'gridalarms'),
			'array' => $queue,
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_target_low|'
		),
		'job_signal_low' => array(
			'friendly_name' => __('LSF Job Kill Signal (Low Threshold)', 'gridalarms'),
			'method' => 'drop_array',
			'description' => __('Choose the signal to kill the jobs. Required for Signal Kill action only.', 'gridalarms'),
			'array' => $signal,
			'default' => '0',
			'none_value' => __('N/A', 'gridalarms'),
			'value' => '|arg1:job_signal_low|'
		),
	);

	return $form_array;
}

function gridalarms_th_edit_javascript($thold_data) {
	global $thold_oob_templates;
?>
function switchLSFEventType() {
			var action_level = $('#gridadmin_action_level').val();
			$('#row_event_trigger_gridadmin_host').hide();
			$('#row_host_action_high').hide();
			$('#row_host_action_high_open_lockid').hide();
			$('#row_host_action_high_close_lockid').hide();
			$('#row_host_action_low').hide();
			$('#row_host_action_low_open_lockid').hide();
			$('#row_host_action_low_close_lockid').hide();
			$('#row_event_trigger_gridadmin_job').hide();
			$('#row_job_action_high').hide();
			$('#row_job_target_high').hide();
			$('#row_job_signal_high').hide();
			$('#row_job_action_low').hide();
			$('#row_job_target_low').hide();
			$('#row_job_signal_low').hide();

			switch(action_level) {
				case 'job':
					$('#row_job_action_high').show();
					$('#row_job_action_low').show();

					if ($('#job_action_high').val() == '1') { // Bswitch
						$('#row_job_target_high').show();
					} else if ($('#job_action_high').val() == '4') {
						$('#row_job_signal_high').show();
					}

					if ($('#job_action_low').val() == '1') { // Bswitch
						$('#row_job_target_low').show();
					} else if ($('#job_action_low').val() == '4') {
						$('#row_job_signal_low').show();
					}

					break;
				case 'host':
					$('#row_event_trigger_gridadmin_host').show();
					$('#row_host_action_high').show();
					var host_action_high = $('#host_action_high').val();
					if(host_action_high ==1 ){
						$('#row_host_action_high_open_lockid').show();
					} else if (host_action_high ==2 ) {
						$('#row_host_action_high_close_lockid').show();
					}
					$('#row_host_action_low').show();
					var host_action_low = $('#host_action_low').val();
					if(host_action_low ==1 ){
						$('#row_host_action_low_open_lockid').show();
					} else if (host_action_low ==2 ) {
						$('#row_host_action_low_close_lockid').show();
					}
					break;
			}
		};

		$('#gridadmin_action_level, #job_action_high, #job_action_low, #host_action_high, #host_action_low').off('change').on('change', function() {
			switchLSFEventType();
		});

		$('#template_enabled').on('change', function() {
			var status = $('#template_enabled').is(':checked');
	<?php
	if (read_config_option('gridalarm_thold_detail') != 'on'
		&& isset($thold_data['data_template_hash'])
		&& !array_key_exists($thold_data['data_template_hash'], $thold_oob_templates)){
		//Only enable LSF event triggering type drop-down list with out-of-box templates
	?>
		$('#gridadmin_action_level').attr('disabled', true);
		if($('#gridadmin_action_level').selectmenu('instance')) {
			$('#gridadmin_action_level').selectmenu('disable');
		}
	<?php
	} else {?>
		$('#gridadmin_action_level').prop('disabled', status);
	<?php
	}?>
		$('#row_host_action_high').prop('disabled', status);
			$('#row_host_action_high_open_lockid').prop('disabled', status);
			$('#row_host_action_high_close_lockid').prop('disabled', status);
			$('#row_host_action_low').prop('disabled', status);
			$('#row_host_action_low_open_lockid').prop('disabled', status);
			$('#row_host_action_low_close_lockid').prop('disabled', status);
			$('#job_action_high').prop('disabled', status);
			$('#job_target_high').prop('disabled', status);
			$('#job_signal_high').prop('disabled', status);
			$('#job_action_low').prop('disabled', status);
			$('#job_target_low').prop('disabled', status);
			$('#job_signal_low').prop('disabled', status);

			if (status) {
				$('input:not(:button):not(:submit), textarea, select').each(function() {
					$(this).addClass('ui-state-disabled');
					if ($(this).selectmenu('instance')) {
						$(this).selectmenu('disable');
					}
				});
			} else {
				$('input:not(:button):not(:submit), textarea, select').each(function() {
					$(this).removeClass('ui-state-disabled');
					if ($(this).selectmenu('instance')) {
						$(this).selectmenu('enable');
					}
				});
			}
		});

	$(function() {
<?php
	if (read_config_option('gridalarm_thold_detail') != 'on'
		&& isset($thold_data['data_template_hash'])
		&& !array_key_exists($thold_data['data_template_hash'], $thold_oob_templates)){
	//Only enable LSF event triggering type drop-down list with out-of-box templates
	?>
		$('#gridadmin_action_level').attr('disabled', true);
		if($('#gridadmin_action_level').selectmenu('instance')) {
			$('#gridadmin_action_level').selectmenu('disable');
		}
<?php
	}?>
		switchLSFEventType();
	});
<?php
	return $thold_data;
}

function gridalarms_tht_edit_save_thold($save) {
	// Action Type
	$save['gridadmin_action_level'] = get_request_var('gridadmin_action_level');

	// Host actions
	$save['host_action_high'] = get_request_var('host_action_high');
	$save['host_action_low']  = get_request_var('host_action_low');

	// Host actions high
	$save['job_action_high'] = get_request_var('job_action_high');
	$save['job_target_high'] = get_request_var('job_target_high');
	$save['job_signal_high'] = get_request_var('job_signal_high');

	// Host actions low
	$save['job_action_low'] = get_request_var('job_action_low');
	$save['job_target_low'] = get_request_var('job_target_low');
	$save['job_signal_low'] = get_request_var('job_signal_low');

	return $save;
}

/**
 *  Custom action related to plugin actions
 *
 *  @var $save - Tn array of status information for the thold to be used
 *       to determine what action to take on the Threshold.
 *       The save array is structures as follows:
 *       $save = array(
 *         'class'               => 'alert', 'warn', 'alert2warn', 'warn2normal', 'alert2normal', 'blnormal', 'blalert'
 *         'thold_data'          => $thold_data, // The thold_data object
 *         'subject'             => $subject, // The email subject
 *         'repeat_alert'        => $ra, // Is this a repeat alert
 *         'host_data'           => $h, // Host data
 *         'breach_up'           => $breach_up, // Is this a breach up
 *         'breach_down'         => $breach_down, // Is this a breach down
 *         'warning_breach_up'   => $warning_breach_up, // Is this a warning breach up
 *         'warning_breach_down' => $warning_breach_down // Is this a warning breach down
 *       );
 *       NOTE: The baseline 'class' types do not include all structure data
 */
function gridalarms_thold_action($save) {
	if (isset($save['class'])) {
		switch($save['class']) {
			case 'warn':
			case 'alert2warn':
			case 'warn2normal':
			case 'alert2normal':
			case 'blnormal':
				// Not supported
				break;
			case 'blalert':
			case 'alert':
				gridalarms_thold_gridcontrol_exec($save);
				break;
		}
	}

	return $save;
}

function gridalarms_thold_gridcontrol_exec($save) {
	global $config;

	include_once($config['base_path'] . '/plugins/RTM/include/rtm_constants.php');
	include_once($config['base_path'] . '/plugins/RTM/lib/rtm_functions.php');
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

	$run_grid     = 'false';
	$run_host     = 'false';
	$run_job      = 'false';
	$cmd          = '';
	$breach_level = '';
	$thold_data   = $save['thold_data'];

	/* if this is a supported template, and array will be returned */
	$breach_info  = gridalarms_fetch_breached_info($thold_data['local_data_id']);

	/* if the array is empty, there are not things to take action on, so return */
	if (!is_array($breach_info) || !cacti_sizeof($breach_info)) {
		return;
	}

	/* if gridadmin_action_level is not 'job' and 'host', there are not things to take action on, so return.*/
	if ($thold_data['gridadmin_action_level'] == '' || $thold_data['gridadmin_action_level'] == 'none' ) {
		return;
	}
	/* a supported template will have the clusterid as the third option */
	$full_path    = get_full_script_path($thold_data['local_data_id']);
	$length       = explode(' ',$full_path);
	$clusterid    = $length[2]; // get clusterid associated with this threshold
	$targetvalue  = array();

	if ($save['breach_up']) { //high threshold breached
		$host_action  = $thold_data['host_action_high'];
		$host_action_lockid  = $thold_data['host_action_high_lockid'];
		$job_action   = $thold_data['job_action_high'];
		$job_target   = $thold_data['job_target_high'];
		$job_signal   = $thold_data['job_signal_high'];
		$breach_level = 'High';
	} elseif ($save['breach_down']) { //low threshold breached
		$host_action  = $thold_data['host_action_low'];
		$host_action_lockid  = $thold_data['host_action_low_lockid'];
		$job_action   = $thold_data['job_action_low'];
		$job_target   = $thold_data['job_target_low'];
		$job_signal   = $thold_data['job_signal_low'];
		$breach_level = 'Low';
	}

	foreach($breach_info as $breachinfo) {  //populating data to be parse to the function sorting_json_format
		$keysvalue = array_keys($breachinfo);
		foreach($keysvalue as $keys){
			if ($keys == 'HostName') {
				$targetvalue[] = $breachinfo['HostName'] . ':' . $clusterid;
			} elseif ($keys == 'JobID') {
				$targetvalue[] = $breachinfo['JobID'] . ':' . $clusterid;
			}
		}
	}

	if (cacti_sizeof($targetvalue)) {
		if ($host_action > 0) { // check that all variables related to host actions are set
			$message_array = array();
			$message_array['message'] = 'Thold gridalarm';
			$message_array['action_lockid'] = $host_action_lockid;
			$message = $message_array;
			if ($host_action == 1) { // open host
				$lsf_control_action  = 'open';
				$action_level       = 'host';
				$run_grid           = 'true';
				$json_return_format = sorting_json_format($targetvalue, $message, $action_level); // massage parameter in json format
				$cmd = 'Open Host(s)';
			} elseif ($host_action == 2) { // close host
				$lsf_control_action = 'close';
				$action_level      = 'host';
				$run_grid          = 'true';
				$json_return_format = sorting_json_format($targetvalue, $message, $action_level); // massage parameter in json format
				$cmd = 'Close Host(s)';
			}

			$run_host = 'true'; // action to be executed is related to host
			$run_grid = 'true'; // set to true so that we will call advocate to run the command
		}

		if ($run_host == 'false') { // make sure that this is not a host related action
			if ($job_action == 1) { // BSwitch, make sure the target queue is also chosen
				$lsf_control_action  = 'switch';
				$action_level       = 'job';
				$json_return_format = sorting_json_format($targetvalue, $job_target, 'signal');
				$run_job            = 'true';
				$run_grid           = 'true';
				$cmd                = 'Switch Job(s) to Target Queue: ' . $job_target;
			} elseif ($job_action == 2) { // BKill
				$lsf_control_action  = 'kill';
				$action_level       = 'job';
				$json_return_format = sorting_json_format($targetvalue);
				$run_job            = 'true';
				$run_grid           = 'true';
				$cmd                = 'Kill Job(s)';
			} elseif ($job_action == 3) { // Force Kill
				$lsf_control_action  = 'forcekill';
				$action_level       = 'job';
				$json_return_format = sorting_json_format($targetvalue);
				$run_job            = 'true';
				$run_grid           = 'true';
				$cmd                = 'Force Kill Job(s)';
			} elseif ($job_action == 4 && $job_signal != '') { //Signal Kill
				$lsf_control_action  = 'sigkill';
				$action_level       = 'job';
				$json_return_format = sorting_json_format($targetvalue, $job_signal, 'signal');
				$run_job            = 'true';
				$run_grid           = 'true';
				$cmd                = 'Signal Kill Job(s) using signal: ' . $job_signal;
			} elseif ($job_action == 5) { // kill as DONE
				$lsf_control_action  = 'killdone';
				$action_level       = 'job';
				$json_return_format = sorting_json_format($targetvalue);
				$run_job            = 'true';
				$run_grid           = 'true';
				$cmd                = 'Kill Job(s) as DONE';
			}
		}

		if ($run_grid == 'true') { //only run gridadmin if all the necessary variables are set correctly
			$advocate_key = session_auth();
			$json_cluster_info = array (
				'key'    => $advocate_key,
				'action' => $lsf_control_action,
				'target' => $json_return_format,
			);

			$json_output = json_encode($json_cluster_info);

			$curl_output = exec_curl($action_level, $json_output); //pass to advocate for processing

			if ($curl_output['http_code'] == 400) {
				cacti_log('Event trigger: Thold Unable to invoke command: ' . $cmd . ' due to http_code = 400', true, 'GRIDALERTS');
			} elseif ($curl_output['http_code'] == 500) {
				cacti_log('Event trigger: Thold Unable to invoke command: ' . $cmd . ' due to http_code = 500', true, 'GRIDALERTS');
			} else {
				cacti_log('Event trigger (' . $breach_level . "): Thold invoking event command: " . $cmd, true, 'GRIDALERTS');
			}
		}
	}
}

function gridalarms_fetch_breached_info($local_data_id){
	global $called_by_script_server;

	$type_id = db_fetch_cell_prepared('SELECT di.type_id
		FROM data_template_data AS dtd
		INNER JOIN data_input AS di
		ON dtd.data_input_id = di.id
		WHERE dtd.local_data_id = ?',
		array($local_data_id));

	$return_value = array();
	if ($type_id == DATA_INPUT_TYPE_PHP_SCRIPT_SERVER) {
		$called_by_script_server = 1;

		$full_path = get_full_script_path($local_data_id);
		$length    = explode(' ', $full_path);

		if (!file_exists($length[0])) {
			cacti_log('WARNING: Unable to fetch breached items.  Script \'' . $length[0] . '\' does not exist.', false, 'GRIDALERTS');
		}

		if (strlen($length[0]) && file_exists($length[0])) {
			include_once($length[0]);

			$input_parameter = cacti_sizeof($length) - 2 ;
			for($i=1; $i<=cacti_sizeof($length)-1; $i++){
				$length[$i] = trim($length[$i], "' \t\n\r\0\x0B");
			}

			if (isset($length[1]) && function_exists($length[1])) {
				if ($input_parameter == 0) {
					$detail_args = array();
				} elseif ($input_parameter >= 1) {
					$detail_args = array_slice($length, 2);
				}
				array_push($detail_args, 1);
				$return_value = call_user_func_array($length[1], $detail_args);
			}
		}
	}

	return $return_value;
}

function gridalarms_thold_replacement_text($data) {
	global $config;

	if (isset($data['thold_data'])) {
		$thold = $data['thold_data'];

		$html_table  = '<tr><td><table class="cactiTable"><tr><td><center><i>' . __('No information on breached items found.', 'gridalarms') . '</i></center></td></tr></table></td></tr>';

		$items = gridalarms_fetch_breached_info($thold['local_data_id']);

		if (isset($items[0]) && is_array($items[0])) {
			$html_table  = '<table class="cactiTable">' . PHP_EOL;
			$item        = $items[0];
			$keys        = array_keys($item);
			$html_table .= '<tr>';

			if (cacti_sizeof($keys)) {
				foreach($keys as $key) {
					$html_table .= '<th><b>' . $key . '</b></th>' . PHP_EOL;
				}
			}

			$html_table .= '</tr>' . PHP_EOL;

			if (cacti_sizeof($items)) {
				foreach ($items as $item) {
					$html_table .= '<tr>';
					foreach($keys as $key) {
						$html_table .= '<td>' . $item[$key] . '</td>' . PHP_EOL;
					}

					$html_table .= '</tr>' . PHP_EOL;
				}
			}

			$html_table .= '</table>' . PHP_EOL;
		}

		if ($html_table != '') {
			$data['text'] = str_replace('<BREACHED_ITEMS>', $html_table, $data['text']);
		}
	}

	return $data;
}

function gridalarms_thold_graph_actions_url($data) {
	global $config, $thold_oob_templates;

	if (isset($data['thold_data'])) {
		$thold_data = $data['thold_data'];
		if ($thold_data['gridadmin_action_level'] == ''
			|| $thold_data['gridadmin_action_level'] == 'none'
			|| (read_config_option('gridalarm_thold_detail') != 'on'
				&& !array_key_exists($thold_data['data_template_hash'], $thold_oob_templates))){
			//Show breach item icon with out-of-box templates or global enabled
			return $data;
		}

		if (read_config_option('gridalarm_thold_detail') == 'on'
			|| $thold_data['lastread'] != '-' && $thold_data['lastread'] != '' && $thold_data['lastread'] > 0) {
			$data['actions_url'] .= '<a class="hyperLink" title="' . __('View Breached Items', 'gridalarms') . '" href="' . html_escape($config['url_path'] . 'plugins/thold/thold_graph.php?action=breached&id=' . $thold_data['id']) . '"><img alt="" src="' . $config['url_path'] . 'plugins/gridalarms/images/view_alarm_details.gif' . '"></a>';
		}
	}

	return $data;
}

function gridalarms_thold_graph_view($continue) {
	if (get_nfilter_request_var('action') == 'breached') {
		general_header();
		thold_tabs();

		$thold_data = db_fetch_row_prepared('SELECT *
			FROM thold_data
			WHERE id = ?',
			array(get_filter_request_var('id')));

		html_start_box(__('Threshold Breached Items', 'gridalarms'), '100%', false, '3', 'center', '');

		$data = array(
			'thold_data' => $thold_data,
			'text' => '<BREACHED_ITEMS>'
		);

		$data = gridalarms_thold_replacement_text($data);

		print $data['text'];

		html_end_box();

		bottom_footer();

		return true;
	} else {
		return $continue;
	}
}

function gridalarms_thold_graph_tabs($tabs) {
	if (get_nfilter_request_var('action') == 'breached') {
		$tabs['breached'] = __('Breached Items', 'gridalarms');
	}

	return $tabs;
}

function gridalarms_expand_title($data) {
	global $config;

	include_once($config['base_path'] . '/plugins/thold/thold_functions.php');

	$title         = $data['title'];
	$host_id       = $data['host_id'];
	$snmp_query_id = $data['snmp_query_id'];
	$snmp_index    = $data['snmp_index'];

	$local_data_ids = array_rekey(
		db_fetch_assoc_prepared('SELECT id
			FROM data_local
			WHERE host_id = ?
			AND snmp_query_id = ?
			AND snmp_index = ?',
			array($host_id, $snmp_query_id, $snmp_index)),
		'id', 'id'
	);

	$clusterid = db_fetch_cell_prepared('SELECT clusterid
		FROM host AS h
		INNER JOIN graph_local AS gl
		ON gl.host_id = h.id
		WHERE gl.id = ?',
		array($host_id));

	if (cacti_sizeof($clusterid)) {
		$cluster_data = db_fetch_row_prepared('SELECT *
			FROM grid_clusters
			WEHRE clusterid = ?',
			array($clusterid));

		if (cacti_sizeof($cluster_data)) {
			$title = str_replace('|cluster_clustername|', $cluster_data['clustername'], $title);
			$title = str_replace('|cluster_id|', $cluster_data['clusterid'], $title);
		}
	}

	if (cacti_sizeof($local_data_ids)) {
		$data['title'] = thold_substitute_custom_data($title, '|', '|', $local_data_ids);
	}

	return $data;
}
