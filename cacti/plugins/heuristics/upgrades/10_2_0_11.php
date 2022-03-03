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

function upgrade_to_10_2_0_11() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../../../lib/database.php');
	include_once(dirname(__FILE__) . '/../../../lib/functions.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');

	db_execute('DROP TABLE grid_heuristics_percentiles');

	db_execute("CREATE TABLE `grid_heuristics_percentiles` (
		`clusterid` int(10) unsigned NOT NULL,
		`queue` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
		`custom` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
		`projectName` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
		`resReq` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
		`reqCpus` int(10) unsigned NOT NULL,
		`run_time` int(10) unsigned NOT NULL,
		`max_memory` bigint(20) unsigned NOT NULL DEFAULT '0',
		`mem_used` bigint(20) unsigned NOT NULL DEFAULT '0',
		`pend_time` int(10) unsigned NOT NULL DEFAULT '0',
		`partition` int(10) unsigned NOT NULL)
		ENGINE=MyISAM
		ROW_FORMAT=DYNAMIC
		COMMENT='Table used for percentile calculations'");

	$tables = array_rekey(
		db_fetch_assoc('SELECT TABLE_NAME
			FROM information_schema.tables
			WHERE TABLE_SCHEMA="cacti"
			AND TABLE_NAME LIKE "grid_heuristics_percentiles_%"'),
		'TABLE_NAME', 'TABLE_NAME'
	);

	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			db_execute("DROP TABLE $table");
		}
	}

	if (!db_column_exists('grid_heuristics', 'custom')) {
		db_execute('LOCK TABLES `grid_heuristics` WRITE');
		db_execute('ALTER TABLE grid_heuristics
			MODIFY COLUMN run_avg float NOT NULL default "0",
			MODIFY COLUMN run_max int unsigned NOT NULL default "0",
			MODIFY COLUMN run_min int unsigned NOT NULL default "0",
			MODIFY COLUMN run_stddev float NOT NULL default "0",
			MODIFY COLUMN run_median int unsigned NOT NULL default "0",
			MODIFY COLUMN run_25thp int unsigned NOT NULL default "0",
			MODIFY COLUMN run_75thp int unsigned NOT NULL default "0",
			MODIFY COLUMN run_90thp int unsigned NOT NULL default "0",
			ADD COLUMN custom VARCHAR(128) NOT NULL DEFAULT "" AFTER queue,
			ADD COLUMN mem_avg float NOT NULL default "0" AFTER run_90thp,
			ADD COLUMN mem_max bigint unsigned NOT NULL default "0" AFTER mem_avg,
			ADD COLUMN mem_min bigint unsigned NOT NULL default "0" AFTER mem_max,
			ADD COLUMN mem_stddev float NOT NULL default "0" AFTER mem_min,
			ADD COLUMN mem_median bigint unsigned NOT NULL default "0" AFTER mem_stddev,
			ADD COLUMN mem_25thp bigint unsigned NOT NULL default "0" AFTER mem_median,
			ADD COLUMN mem_75thp bigint unsigned NOT NULL default "0" AFTER mem_25thp,
			ADD COLUMN mem_90thp bigint unsigned NOT NULL default "0" AFTER mem_75thp,
			ADD COLUMN pend_avg float NOT NULL default "0" AFTER mem_90thp,
			ADD COLUMN pend_max int unsigned NOT NULL default "0" AFTER pend_avg,
			ADD COLUMN pend_min int unsigned NOT NULL default "0" AFTER pend_max,
			ADD COLUMN pend_stddev float NOT NULL default "0" AFTER pend_min,
			ADD COLUMN pend_median int unsigned NOT NULL default "0" AFTER pend_stddev,
			ADD COLUMN pend_25thp int unsigned NOT NULL default "0" AFTER pend_median,
			ADD COLUMN pend_75thp int unsigned NOT NULL default "0" AFTER pend_25thp,
			ADD COLUMN pend_90thp int unsigned NOT NULL default "0" AFTER pend_75thp,
			DROP PRIMARY KEY,
			ADD PRIMARY KEY (`clusterid`,`queue`,`custom`,`projectName`,`resReq`,`reqCpus`),
			ROW_FORMAT=DYNAMIC');
		db_execute('UNLOCK TABLES');
		db_execute('UPDATE grid_heuristics SET custom="-"');
	}

	if (!db_column_exists('grid_heuristics_user_stats', 'custom')) {
		db_execute("ALTER TABLE grid_heuristics_user_stats
			ADD COLUMN custom VARCHAR(128) NOT NULL default '' AFTER queue,
			DROP PRIMARY KEY,
			ADD PRIMARY KEY (`clusterid`,`queue`,`custom`,`projectName`,`user`,`reqCpus`)");
	}
	$tables = array_rekey(
		db_fetch_assoc('SELECT TABLE_NAME
			FROM information_schema.tables
			WHERE TABLE_SCHEMA="cacti"
			AND (TABLE_NAME LIKE "grid_heuristics_user_history_%")'),
		'TABLE_NAME', 'TABLE_NAME'
	);

	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			if (!db_column_exists($table, 'custom')) {
				db_execute("ALTER TABLE $table
					ADD COLUMN custom VARCHAR(128) NOT NULL default '' AFTER queue,
					DROP PRIMARY KEY,
					ADD PRIMARY KEY (`clusterid`,`queue`,`custom`,`projectName`,`user`,`reqCpus`,`last_updated`)");
			}
		}
	}
}
