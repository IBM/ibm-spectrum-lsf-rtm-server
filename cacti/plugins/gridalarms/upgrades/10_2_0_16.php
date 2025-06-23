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

include_once(__DIR__ . '/../../../include/global.php');

upgrade_to_10_2_0_16();

function upgrade_to_10_2_0_16() {
	global $config;

	include_once($config['library_path'] . '/rtm_functions.php');
	include_once($config['library_path'] . '/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.2.0.16 ...', true, 'UPGRADE');

	db_add_column('gridalarms_alarm_layout', array(
		'name' => 'type',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'sortdirection')
	);

	db_add_column('gridalarms_alarm_layout', array(
		'name' => 'units',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'type')
	);

	db_add_column('gridalarms_alarm_layout', array(
		'name' => 'align',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'units')
	);

	db_add_column('gridalarms_alarm_layout', array(
		'name' => 'digits',
		'type' => 'tinyint(4)',
		'NULL' => false,
		'default' => '-1',
		'after' => 'align')
	);

	db_add_column('gridalarms_alarm_layout', array(
		'name' => 'autoscale',
		'type' => 'tinyint(4)',
		'NULL' => false,
		'default' => '0',
		'after' => 'digits')
	);

	db_add_column('gridalarms_template_layout', array(
		'name' => 'type',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'sortdirection')
	);

	db_add_column('gridalarms_template_layout', array(
		'name' => 'units',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'type')
	);

	db_add_column('gridalarms_template_layout', array(
		'name' => 'align',
		'type' => 'varchar(10)',
		'NULL' => false,
		'default' => '',
		'after' => 'units')
	);

	db_add_column('gridalarms_template_layout', array(
		'name' => 'digits',
		'type' => 'tinyint(4)',
		'NULL' => false,
		'default' => '-1',
		'after' => 'align')
	);

	db_add_column('gridalarms_template_layout', array(
		'name' => 'autoscale',
		'type' => 'tinyint(4)',
		'NULL' => false,
		'default' => '0',
		'after' => 'digits')
	);

	db_add_column('gridalarms_alarm', array(
		'name' => 'format_file',
		'type' => 'varchar(255)',
		'NULL' => false,
		'default' => '',
		'after' => 'email_subject')
	);

	db_add_column('gridalarms_template', array(
		'name' => 'format_file',
		'type' => 'varchar(255)',
		'NULL' => false,
		'default' => '',
		'after' => 'email_subject')
	);

	db_add_column('gridalarms_alarm', array(
		'name' => 'notes',
		'type' => 'text',
		'NULL' => true,
		'after' => 'format_file')
	);

	db_add_column('gridalarms_template', array(
		'name' => 'notes',
		'type' => 'text',
		'NULL' => true,
		'after' => 'format_file')
	);

	db_add_column('gridalarms_alarm', array(
		'name' => 'external_id',
		'type' => 'varchar(40)',
		'NULL' => false,
		'default' => '',
		'after' => 'notes')
	);

	db_add_column('gridalarms_template', array(
		'name' => 'external_id',
		'type' => 'varchar(40)',
		'NULL' => false,
		'after' => 'notes')
	);

	db_add_column('gridalarms_expression', array(
		'name' => 'column_data',
		'type' => 'varchar(512)',
		'NULL' => false,
		'default' => '',
		'after' => 'sql_query')
	);

	db_add_column('gridalarms_template_expression', array(
		'name' => 'column_data',
		'type' => 'varchar(512)',
		'NULL' => false,
		'default' => '',
		'after' => 'sql_query')
	);

	return 0;
}
