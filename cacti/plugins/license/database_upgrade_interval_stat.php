#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');

$result1= db_fetch_assoc("SHOW INDEX
	FROM lic_interval_stats
	FROM cacti
	WHERE key_name='PRIMARY'
	AND column_name='interval_end'");

if(!cacti_sizeof($result1)) {
	db_execute("ALTER TABLE lic_interval_stats DROP PRIMARY KEY");

	db_execute("ALTER TABLE lic_interval_stats ADD PRIMARY KEY(`service_id`, `feature`, `user`, `host`, `action`, `vendor`, `interval_end`, `event_id`)");

	db_execute("ALTER TABLE lic_interval_stats ADD INDEX(`date_recorded`)");

	db_execute("ALTER TABLE lic_interval_stats MODIFY interval_end datetime NOT NULL DEFAULT '0000-00-00 00:00:00'");

	db_execute("ALTER TABLE lic_interval_stats
		PARTITION BY RANGE(HOUR(interval_end)) (
			PARTITION p0 VALUES LESS THAN (1),
			PARTITION p1 VALUES LESS THAN (2),
			PARTITION p2 VALUES LESS THAN (3),
			PARTITION p3 VALUES LESS THAN (4),
			PARTITION p4 VALUES LESS THAN (5),
			PARTITION p5 VALUES LESS THAN (6),
			PARTITION p6 VALUES LESS THAN (7),
			PARTITION p7 VALUES LESS THAN (8),
			PARTITION p8 VALUES LESS THAN (9),
			PARTITION p9 VALUES LESS THAN (10),
			PARTITION p10 VALUES LESS THAN (11),
			PARTITION p11 VALUES LESS THAN (12),
			PARTITION p12 VALUES LESS THAN (13),
			PARTITION p13 VALUES LESS THAN (14),
			PARTITION p14 VALUES LESS THAN (15),
			PARTITION p15 VALUES LESS THAN (16),
			PARTITION p16 VALUES LESS THAN (17),
			PARTITION p17 VALUES LESS THAN (18),
			PARTITION p18 VALUES LESS THAN (19),
			PARTITION p19 VALUES LESS THAN (20),
			PARTITION p20 VALUES LESS THAN (21),
			PARTITION p21 VALUES LESS THAN (22),
			PARTITION p22 VALUES LESS THAN (23),
			PARTITION p23 VALUES LESS THAN (24))");
}
