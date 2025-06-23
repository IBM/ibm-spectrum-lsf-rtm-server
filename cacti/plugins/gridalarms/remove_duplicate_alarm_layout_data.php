#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

print 'Start Remove Duplicate Alert Layout Data' . PHP_EOL;

$duplicate_layouts = db_fetch_assoc('SELECT alarm_id, column_name, COUNT(*) AS alarmcount
	FROM gridalarms_alarm_layout
	GROUP BY 1,2 HAVING alarmcount >1');

foreach ($duplicate_layouts as $layout) {
	$alarm_id =$layout['alarm_id'];
	$column_name =$layout['column_name'];

	$min_id = db_fetch_cell_prepared('SELECT min(id)
		FROM gridalarms_alarm_layout
		WHERE alarm_id = ?
		AND column_name = ?',
		array($alarm_id, $column_name));

	db_execute_prepared('DELETE FROM gridalarms_alarm_layout
		WHERE alarm_id = ?
		AND column_name = ?
		AND id > ?',
		array($alarm_id, $column_name, $min_id));

}

print 'Duplicate Alert Layout Data was Removed' . PHP_EOL;

