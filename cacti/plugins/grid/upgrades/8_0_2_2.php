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

function upgrade_to_8_0_2_2() {
	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');

	create_table("grid_jobs_runtime", "CREATE TABLE `grid_jobs_runtime` (
        	clusterid int(10) unsigned NOT NULL default '0',
        	jobid int(10) unsigned NOT NULL default '0',
        	indexid varchar(45) NOT NULL default '0',
        	submit_time timestamp NOT NULL default '0000-00-00 00:00:00',
        	rlimit_max_wallt int(10) unsigned NOT NULL default '0',
        	runtimeEstimation int(10) unsigned default '0',
        	run_time int(10) unsigned NOT NULL default '0',
        	type int(10) unsigned NOT NULL default '0',
        	notified tinyint(3) unsigned NOT NULL default '0',
		present tinyint(3) unsigned NOT NULL default '1',
		PRIMARY KEY  (clusterid,submit_time,indexid,jobid,type,rlimit_max_wallt,runtimeEstimation)
		) ENGINE=InnoDB;");
}
