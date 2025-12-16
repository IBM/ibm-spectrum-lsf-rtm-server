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

function upgrade_to_10_1_0_4() {
	global $config;
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading gridblstat to v10.1.0.4 ...', true, 'UPGRADE');

    $column_arr= array(
		'lsf_envdir'          => "ADD COLUMN `lsf_envdir` varchar(255) NOT NULL DEFAULT '' AFTER disabled",
		'advanced_enabled'    => "ADD COLUMN `advanced_enabled` char(2) NOT NULL DEFAULT '' AFTER disabled",
		'lsf_strict_checking' => "ADD COLUMN `lsf_strict_checking` char(3) DEFAULT 'N' AFTER disabled"
	);

	add_columns("grid_blstat_collectors", $column_arr);

	$column_arr = array(
		'fd_total'  => "ADD COLUMN fd_total INT(10) UNSIGNED DEFAULT '0' AFTER total_others",
		'fd_adj'    => "ADD COLUMN fd_adj INT(10) UNSIGNED DEFAULT '0' AFTER fd_total",
		'fd_max'    => "ADD COLUMN fd_max INT(10) UNSIGNED DEFAULT '0' AFTER fd_adj",
		'fd_step'   => "ADD COLUMN fd_step INT(10) DEFAULT '0' AFTER fd_max",
		'fd_util'   => "ADD COLUMN fd_util DOUBLE DEFAULT '0.0' AFTER fd_step",
		'fd_target' => "ADD COLUMN fd_target DOUBLE DEFAULT '0.0' AFTER fd_util"
    );

	add_columns('grid_blstat', $column_arr);
}
