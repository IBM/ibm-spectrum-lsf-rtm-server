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

function upgrade_to_9_1_2() {
	global $config;

	include_once($config['library_path'] . '/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading license to v9.1.2 ...', true, 'UPGRADE');

	$status = DB_STATUS_SUCCESS;

	$column_arr= array(
		'type' => "ADD COLUMN `type` ENUM('0','1','2','3','4','5','6','7','8')  NOT NULL default '0' AFTER `transaction_count`",
		'peak_ut' => "ADD COLUMN `peak_ut` float  NOT NULL default '0' AFTER `utilization`"
	);

	$status = add_columns("lic_daily_stats", $column_arr);

	if ($status) {
		$status = execute_sql("Update default value of lic_daily_stats.type and lic_daily_stats.peak_ut",
			"UPDATE lic_daily_stats SET type='8' WHERE user='' AND host=''");
	}

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('lic_partitions_upgrade_status', 'scheduled')");

	return $status;
}

function partition_tables_to_9_1_2() {
	return array(
		'lic_daily_stats' => array(
			'columns' => array(
				'type'    => "ADD COLUMN `type` ENUM('0','1','2','3','4','5','6','7','8')  NOT NULL default '0' AFTER `transaction_count`",
				'peak_ut' => "ADD COLUMN `peak_ut` float  NOT NULL default '0' AFTER `utilization`",
			),
			'sqls' => array(
				"UPDATE lic_daily_stats SET type='8' WHERE user='' AND host=''"
			)
		)
	);
}
