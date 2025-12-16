#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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

include(dirname(__FILE__) . "/../../include/cli_check.php");

/* make the database more demo friendly */
print db_execute("ALTER TABLE `grid_hostgroups` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_hostresources` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_hosts` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_jobs_exec_hosts` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_jobs_from_hosts` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_jobs_queues` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_jobs_stats` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_jobs_users` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_load` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_params` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_queues` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_queues_hosts` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_queues_users` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_users_or_groups` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_hosts_resources` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_queues_shares` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_resources` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_summary` ENGINE = InnoDB");
print db_execute("ALTER TABLE `grid_user_group_members` ENGINE = InnoDB");

?>
