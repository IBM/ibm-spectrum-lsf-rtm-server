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

include(dirname(__FILE__)."/../../include/global.php");
$colors = array(
	// Syslog
	'syslog_emerg_bg' => '990203',
	'syslog_crit_bg' => 'C62227',
	'syslog_alert_bg' => 'E35220',
	'syslog_err_bg' => 'F3961E',
	'syslog_warn_bg' => 'F3B81E',
	'syslog_notice_bg' => 'F4E055',
	'syslog_info_bg' => 'D0E6CC',
	'syslog_debug_bg' => 'A0CC99',

	// Grid
	'grid_efficiency_warning_bgcolor' => 'F3B81E',
    'grid_efficiency_alarm_bgcolor' => 'E35220',
    'grid_flapping_bgcolor' => 'F4E055',
    'grid_depend_bgcolor' => 'D0E6CC',
    'grid_invalid_depend_bgcolor' => 'A0CC99',
    'grid_exit_bgcolor' => '7DB53C',
    'grid_exclusive_bgcolor' => 'F3961E',
    'grid_interactive_bgcolor' => 'C62227',
    'grid_licsched_bgcolor' => null,
    'grididle_bgcolor' => null,
    'gridmemvio_bgcolor' => null,
    'gridmemvio_us_bgcolor' => null,
    'gridrunlimitvio_bgcolor' => null
);

foreach ($colors as $key => $value) {
	$id = read_config_option($key);
	if ($value && is_numeric($id)) {
		db_execute("UPDATE colors SET hex = '{$value}' WHERE id = '{$id}'");
	}
}
