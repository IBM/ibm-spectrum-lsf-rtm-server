<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2025                                          |
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

function upgrade_to_10_2_0_16() {
	global $system_type, $config;
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../lib/gridalarms_functions.php');

	cacti_log('NOTE: Upgrading gridalarms to v10.2.0.16 ...', true, 'UPGRADE');

	db_execute("UPDATE gridalarms_template_expression SET sql_query = 'SELECT gj.clusterid, gc.clustername, jobid, indexid, submit_time, stat, user,
		effectiveEligiblePendingTimeLimit, (CAST(pend_time AS SIGNED) - CAST(ineligiblePendingTime AS SIGNED)) AS EligiblePendingTime
		FROM grid_jobs AS gj, grid_clusters AS gc \nWHERE gj.clusterid = gc.clusterid 
		AND stat in (\'PEND\', \'USUSP\', \'PSUSP\', \'SSUSP\') AND effectiveEligiblePendingTimeLimit >0 
		AND (CAST(pend_time AS SIGNED) - CAST(ineligiblePendingTime AS SIGNED)) > effectiveEligiblePendingTimeLimit \n\n' 
		where id = 7");

	db_execute("UPDATE gridalarms_expression SET sql_query = 'SELECT gj.clusterid, gc.clustername, jobid, indexid, submit_time, stat, user, 
		effectiveEligiblePendingTimeLimit, (CAST(pend_time AS SIGNED) - CAST(ineligiblePendingTime AS SIGNED)) AS EligiblePendingTime
		FROM grid_jobs AS gj, grid_clusters AS gc \nWHERE gj.clusterid = gc.clusterid 
		AND stat in (\'PEND\', \'USUSP\', \'PSUSP\', \'SSUSP\') AND effectiveEligiblePendingTimeLimit >0 
		AND (CAST(pend_time AS SIGNED) - CAST(ineligiblePendingTime AS SIGNED)) > effectiveEligiblePendingTimeLimit \n\n' 
		where template_id = 7");
}