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

function upgrade_to_9_1_4() {
	global $config;

	include_once($config['library_path'] . '/rtm_db_upgrade.php');

	cacti_log('NOTE: Upgrading license to v9.1.4 ...', true, 'UPGRADE');

	execute_sql("DROP lic_servers_pre913",
		"DROP TABLE IF EXISTS `lic_servers_pre913`");

	cacti_log("NOTE: Fix RTC 51079, recreate the views.", true, 'UPGRADE');;

	execute_sql("DROP VIEW lic_flexlm_servers_feature_details",
		"DROP VIEW IF EXISTS `lic_flexlm_servers_feature_details`");

	execute_sql("CREATE VIEW lic_flexlm_servers_feature_details",
		"CREATE VIEW lic_flexlm_servers_feature_details AS
		SELECT ls.poller_id, lsfd.service_id AS portatserver_id, lsfd.vendor_daemon, lsfd.feature_name,
		lsfd.subfeature, lsfd.feature_version, lsfd.username, lsfd.groupname, lsfd.hostname,
		lsfd.chkoutid, lsfd.restype, lsfd.status, lsfd.tokens_acquired, lsfd.tokens_acquired_date,
		lsfd.last_updated, lsfd.present
		FROM lic_services_feature_details AS lsfd
		INNER JOIN lic_services AS ls
		ON lsfd.service_id = ls.service_id");

	execute_sql("DROP VIEW lic_flexlm_servers_feature_use",
		"DROP VIEW IF EXISTS `lic_flexlm_servers_feature_use`");

	execute_sql("CREATE VIEW lic_flexlm_servers_feature_use",
		"CREATE VIEW lic_flexlm_servers_feature_use AS
		SELECT ls.poller_id, lsfu.service_id AS portatserver_id, lsfu.feature_name,
		lsfu.feature_max_licenses, lsfu.feature_inuse_licenses, lsfu.feature_queued, lsfu.feature_reserved,
		lsfu.vendor_daemon, lsfu.present, lsfu.vendor_status, lsfu.vendor_version, lsfu.status
		FROM lic_services_feature_use AS lsfu
		INNER JOIN lic_services AS ls
		ON lsfu.service_id = ls.service_id");
}

