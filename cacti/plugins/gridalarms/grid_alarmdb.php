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

chdir('../../');
include_once('./include/auth.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_functions.php');
include_once($config['base_path'] . '/plugins/gridalarms/lib/gridalarms_constants.php');

set_default_action();

$alarm_actions = array(
	ALERT_ACK     => __('Acknowledge', 'gridalarms'),
	ALERT_RESET   => __('Reset Acknowledgement', 'gridalarms'),
	ALERT_ENABLE  => __('Enable', 'gridalarms'),
	ALERT_DISABLE => __('Disable', 'gridalarms'),
	ALERT_DELETE  => __('Delete', 'gridalarms')
);

get_filter_request_var('id');

switch (get_nfilter_request_var('action')) {
	case 'disable':
    	if (api_user_realm_auth('gridalarms_alarm.php')) {
			alarm_disable_prepared(get_filter_request_var('id'));
		}

	    header('Location: grid_alarmdb.php?tab=alarms&header=false&id=' . get_request_var('id'));

		break;
	case 'enable':
    	if (api_user_realm_auth('gridalarms_alarm.php')) {
			alarm_enable_prepared(get_request_var('id'));
		}

	    header('Location: grid_alarmdb.php?tab=alarms&header=false&id=' . get_request_var('id'));

		break;
	case 'acknowledge':
		if (api_user_realm_auth('gridalarms_alarm.php')) {
			set_request_var('drp_action', ALERT_ACK);
			set_request_var('chk_' . get_request_var('id'), 'on');
			set_request_var('return_to', 'grid_alarmdb.php');
			do_alarms('grid');
		}

		break;
	case 'resetack':
    	if (api_user_realm_auth('gridalarms_alarm.php')) {
        	alarm_reset(get_request_var('id'));
	    }

		header('Location: grid_alarmdb.php?tab=alarms&header=false&id=' . get_request_var('id'));

		break;
	default:
		general_header();
		alarm_tabs(false);

		switch (get_nfilter_request_var('tab')) {
			case 'alarms':
				list_alarms(false);

				break;
			case 'log':
				list_alarm_log(false);

				break;
			case 'logdetail':
				list_alarm_log_detail();

				break;
			default:
				alarm_breached_items(get_request_var('id'));
		}

		bottom_footer();
}

