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

$template_actions = array(
	TEMPLATE_EXPORT => __('Export', 'gridalarms'),
	TEMPLATE_DUP    => __('Duplicate', 'gridalarms'),
	TEMPLATE_DELETE => __('Delete', 'gridalarms')
);

$action = get_nfilter_request_var('action');

if (isset_request_var('drp_action')) {
	do_templates();
} else {
	switch ($action) {
	case 'export':
		template_export();
		break;
	default:
		top_header();
		list_templates();
		bottom_footer();
		break;
	}
}

function template_export() {
	if (isset_request_var('alarms')) {
	    $alarms = sanitize_unserialize_selected_items(get_nfilter_request_var('alarms'));

	    if ($alarms != false) {


			$first_flag = true;
			$first_alarm_name = '';

			$xml_data = '<cacti>' . PHP_EOL;

			if (cacti_sizeof($alarms)) {
				foreach ($alarms as $exp => $me) {
					$alarm = db_fetch_row_prepared('SELECT *
						FROM gridalarms_template
						WHERE id = ?',
						array($exp));

					if ($first_flag) {
						if (cacti_sizeof($alarm)) {
							$export_file_name = 'grid_alarms_' . clean_up_name($alarm['name']) . '.xml';
						} else {
							$export_file_name = 'grid_alarms_template.xml';
						}

						$first_flag = false;
					} else {
						$export_file_name = 'grid_alarms_multiple_templates.xml';
					}

					$xml_data .= PHP_EOL . export_gridalarms_template($alarm);
				}
			}

			$xml_data .= '</cacti>' . PHP_EOL;

			header('Content-type: application/xml');
			header('Content-Disposition: attachment; filename=' . $export_file_name);

			print $xml_data;

		}
	}
}
