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

function upgrade_to_9_1() {
    global $config;

    include_once($config['library_path'] . '/rtm_functions.php');
    include_once($config['library_path'] . '/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v9.1 ...', true, 'UPGRADE');

	/*1. gridalarms_alarm_log: do nothing*/
	$data = array();
	$data['columns'][] = array('name' => 'id', 'type' => 'bigint(20)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'details', 'type' => 'blob', 'after' => 'description');
	$data['primary'] = array('id');
	db_update_table('gridalarms_alarm_log', $data);

	/*2. gridalarms_expression*/
	$data = array();
	$data['columns'][] = array('name' => 'alarm_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'id');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'alarm_id');
	$data['columns'][] = array('name' => 'ds_type', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true, 'default' => '0', 'after' => 'description');
	$data['columns'][] = array('name' => 'sql_query', 'type' => 'varchar(1024)', 'default' => 'NULL', 'after' => 'db_table');
	$data['columns'][] = array('name' => 'script_thold', 'type' => 'varchar(255)', 'default' => 'NULL', 'after' => 'sql_query');
	$data['columns'][] = array('name' => 'script_data', 'type' => 'varchar(255)', 'default' => 'NULL', 'after' => 'script_thold');
	$data['columns'][] = array('name' => 'script_data_type', 'type' => 'int(10)', 'unsigned' => true, 'default' => '0', 'after' => 'script_data');
	$data['primary'] = array('id');
	db_update_table('gridalarms_expression', $data);

	/* gridalarms_expression_item */
	$data = array();
	$data['columns'][] = array('name' => 'alarm_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'id');
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'alarm_id');
	$data['primary'] = array('id');
	db_update_table('gridalarms_expression_item', $data);

	/*gridalarms_alarm*/
	/*Note: column 'script_thold' and 'script_data' can't be dropped until their data is moved to gridalarms_expression*/
	$data = array();
	$data['columns'][] = array('name' => 'template_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0', 'after' => 'id');
	$data['columns'][] = array('name' => 'template_enabled', 'type' => 'char(3)', 'default' => 'NULL', 'NULL' => true, 'after' => 'template_id');
	$data['columns'][] = array('name' => 'base_time_display', 'type' => 'varchar(20)', 'NULL' => false, 'default' => '12:00am', 'after' => 'metric');
	$data['columns'][] = array('name' => 'base_time', 'type' => 'timestamp', 'NULL' => false, 'default' => '0000-00-00 00:00:00', 'after' => 'base_time_display');
	$data['columns'][] = array('name' => 'frequency', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '1', 'after' => 'base_time');
	$data['columns'][] = array('name' => 'last_runtime', 'type' => 'timestamp', 'NULL' => false, 'default' =>  '0000-00-00 00:00:00', 'after' => 'frequency');
	$data['columns'][] = array('name' => 'last_duration', 'type' => 'float', 'default' => 'NULL', 'NULL' => true, 'after' => 'last_runtime');
	$data['columns'][] = array('name' => 'notify_alert', 'type' => 'int(10)', 'unsigned' => true, 'default' => 'NULL', 'NULL' => true, 'after' => 'notify_cluster_admin');
	$data['columns'][] = array('name' => 'notify_users', 'type' => 'char(3)', 'NULL' => false, 'default' => '', 'after' => 'notify_alert');
	$data['primary'] = array('id');
	$data['keys'][] = array('name' => 'clusterid', 'columns' => array('clusterid'));
	$data['keys'][] = array('name' => 'expression_id', 'columns' => array('expression_id'));
	$data['keys'][] = array('name' => 'notify_alert', 'columns' => array('notify_alert'));
	db_update_table('gridalarms_alarm', $data);

	/* create new tables */
	gridalarms_setup_new_tables();

	/*2.1 move 'script' data (gridalarms_alarm.type=1) from gridalarms_alarms to gridalarms_expression  */
	/*2.2 update gridalarms_expression.alarm_id */
	$alarms = db_fetch_assoc("SELECT * FROM gridalarms_alarm ");
	foreach ($alarms as $alarm) {
		if ($alarm['type'] == 1) {
			$alarm['script_thold'] = str_replace("'", "\'", str_replace('"', '\"', $alarm['script_thold']));
			$alarm['script_data'] = str_replace("'", "\'", str_replace('"', '\"', $alarm['script_data']));
			db_execute("insert into gridalarms_expression
				(id, alarm_id, template_id, name, description, ds_type, type, db_table, sql_query, script_thold, script_data, script_data_type)
				values (0," . $alarm['id'] .",0,'script','script data source',2,0,'grid_jobs','','" . $alarm['script_thold'] . "','"
				. $alarm['script_data'] ."',0) ");
			$expression_id = db_fetch_insert_id();
			if ($expression_id <=0) {
				cacti_log("GRIDALARM_DBUPGRADE - move script data to gridalamrs_expression failed! alarm_id=" . $alarm['id']);
				exit -1;
			}
			db_execute("update gridalarms_alarm set expression_id= $expression_id where id =" . $alarm['id'] );
		} else {
			if ( !empty ($alarm['expression_id']) ) {
				db_execute("update gridalarms_expression set alarm_id= " . $alarm['id'] . " where id =" . $alarm['expression_id'] );
			}
		}
	}

	/*2.3 expression types changing to bitwise */
	db_execute("UPDATE gridalarms_expression SET type=64 WHERE type=5");
	db_execute("UPDATE gridalarms_expression SET type=16 WHERE type=4");
	db_execute("UPDATE gridalarms_expression SET type=8 WHERE type=3");
	db_execute("UPDATE gridalarms_expression SET type=4 WHERE type=2");
	db_execute("UPDATE gridalarms_expression SET type=2 WHERE type=1");
	db_execute("UPDATE gridalarms_expression SET type=1 WHERE type=0");

	$columns = db_fetch_assoc("SHOW COLUMNS FROM gridalarms_alarm LIKE 'script_thold'");
	if (cacti_sizeof($columns)) {
		db_execute("ALTER TABLE gridalarms_alarm DROP COLUMN `script_thold`");
	}
	$columns = db_fetch_assoc("SHOW COLUMNS FROM gridalarms_alarm LIKE 'script_data'");
	if (cacti_sizeof($columns)) {
		db_execute("ALTER TABLE gridalarms_alarm DROP COLUMN `script_data`");
	}

	/*3. gridalarms_expression_item/
	/*3.1 update alarm id*/
	$expression_items = db_fetch_assoc("SELECT * FROM gridalarms_expression_item");
	foreach ($expression_items as $expression_item) {
		$alarm_id = db_fetch_cell ("select alarm_id from gridalarms_expression where id =" . $expression_item['expression_id']);
		if ($alarm_id > 0) {
			db_execute("update gridalarms_expression_item set alarm_id= $alarm_id where id =" . $expression_item['id'] );
		}
	}

	/*4. gridalarms_metric*/
	/*4.1 expression types changing to bitwise */
	db_execute("UPDATE gridalarms_metric SET type=64 WHERE type=5");
	db_execute("UPDATE gridalarms_metric SET type=16 WHERE type=4");
	db_execute("UPDATE gridalarms_metric SET type=8 WHERE type=3");
	db_execute("UPDATE gridalarms_metric SET type=4 WHERE type=2");
	db_execute("UPDATE gridalarms_metric SET type=2 WHERE type=1");
	db_execute("UPDATE gridalarms_metric SET type=1 WHERE type=0");

	/*5. gridalarms_alarm_contacts: do nothing*/

	/*6. gridalarms_alarm*/

	/*7. generate gridalarms_metric_expression*/
	db_execute("REPLACE INTO gridalarms_metric_expression SELECT expression_id, value AS metric_id FROM gridalarms_expression_item WHERE type=3");

	/*8. generate gridalarms_alarm_layout*/
	$alarms = db_fetch_assoc("SELECT * FROM gridalarms_alarm");
	foreach ($alarms as $alarm) {
		populate_default_layout ($alarm);
	}

	/* add notification lists to edit realm */
	$id = db_fetch_cell("SELECT id FROM plugin_realms WHERE file LIKE '%gridalarms_alarm_edit.php%'");
	if (!empty($id)) {
		db_execute("UPDATE plugin_realms SET file='notify_lists.php,gridalarms_alarm.php,gridalarms_templates.php,gridalarms_alarm_edit.php,gridalarms_template_edit.php' WHERE id=$id");
	}

	db_execute("REPLACE INTO settings (name, value) VALUES ('gridalarms_db_version', '9.1.0.0');");
    return 0;
}
