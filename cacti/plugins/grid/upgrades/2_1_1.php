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

function upgrade_to_2_1_1() {
	global $system_type, $config;

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');

	execute_sql("Rename new grid_jobs_jobhosts table to grid_jobs_jobhosts_pre211",
				"RENAME TABLE `grid_jobs_jobhosts` to `grid_jobs_jobhosts_pre211`;");
	execute_sql("Recreate new grid_jobs_jobhosts table from grid_jobs_jobhosts_pre211",
				"CREATE TABLE `grid_jobs_jobhosts` LIKE `grid_jobs_jobhosts_pre211`;");
	execute_sql("Rename new grid_jobs_reqhosts table to grid_jobs_reqhosts_pre211",
				"RENAME TABLE `grid_jobs_reqhosts` to `grid_jobs_reqhosts_pre211`;");
	execute_sql("Recreate new grid_jobs_reqhosts table from grid_jobs_reqhosts_pre211",
				"CREATE TABLE `grid_jobs_reqhosts` LIKE `grid_jobs_reqhosts_pre211`;");

	create_table("grid_jobs_jobhosts_finished", "CREATE TABLE `grid_jobs_jobhosts_finished` (
		`jobid` bigint(20) unsigned NOT NULL default '0',
		`indexid` int(10) unsigned NOT NULL default '0',
		`clusterid` int(10) unsigned NOT NULL default '0',
		`exec_host` varchar(64) NOT NULL default '',
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`processes` int(11) NOT NULL default '0',
		PRIMARY KEY (`clusterid`,`jobid`, `indexid`, `submit_time`, `exec_host`),
		KEY `exec_host` (`exec_host`)
		) ENGINE=InnoDB;");
	create_table("grid_jobs_reqhosts_finished", "CREATE TABLE `grid_jobs_reqhosts_finished` (
		`jobid` bigint(20) unsigned NOT NULL default '0',
		`indexid` int(10) unsigned NOT NULL default '0',
		`clusterid` int(10) unsigned NOT NULL default '0',
		`host` varchar(64) NOT NULL default '',
		`submit_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (`clusterid`,`jobid`, `indexid`, `submit_time`, `host`),
		KEY `host` (`host`)
		) ENGINE=InnoDB;");


	/* dropping present column HASH index on MEMORY tables as it causes problems */
	execute_sql("Drop grid_hosts_resources present index", "ALTER TABLE grid_hosts_resources DROP INDEX `present`;");
	execute_sql("Drop grid_queues_shares present index", "ALTER TABLE grid_queues_shares DROP INDEX `present`;");
	execute_sql("Drop grid_queues_users_stats present index", "ALTER TABLE grid_queues_users_stats DROP INDEX `present`;");
	execute_sql("Drop grid_resources present index", "ALTER TABLE grid_resources DROP INDEX `present`;");
	execute_sql("Drop grid_summary present index", "ALTER TABLE grid_summary DROP INDEX `present`;");
	execute_sql("Drop grid_hostgroups_stats present index", "ALTER TABLE grid_hostgroups_stats DROP INDEX `present`;");

	/* Add host index */
	execute_sql("Add host index to grid_host_resources", "ALTER TABLE grid_hosts_resources ADD INDEX (`host`);");

	/* remove 1 minute RRA */
	execute_sql("Remove 1 minute RRA", "DELETE FROM rra WHERE id=5");
	execute_sql("Remove 1 minute RRA assoicated CF", "DELETE FROM rra_cf WHERE rra_id=5");
	execute_sql("Remove 1 minute RRA from data template", "DELETE from data_template_data_rra WHERE rra_id=5");

	/* fix all steps and hearbeat */
	execute_sql("Set all data template step to 300", "UPDATE data_template_data SET rrd_step=300 WHERE rrd_step=60");
	execute_sql("Set all data template heartbeat to 600", "UPDATE data_template_rrd SET rrd_heartbeat=600 WHERE rrd_heartbeat=120");
}
