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

function upgrade_to_10_2_0_1() {
	global $system_type, $config;
	global $database_hostname, $database_port, $database_username, $database_password, $database_default;

    include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/functions.php');

	cacti_log('NOTE: Upgrading license to v10.2.0.1 ...', true, 'UPGRADE');

	execute_sql("Add errorno column to license servers table", "ALTER TABLE lic_servers ADD COLUMN errorno int(10) NOT NULL DEFAULT '0' AFTER version");
	execute_sql("Add errorno column to license services table", "ALTER TABLE lic_services ADD COLUMN errorno int(10) NOT NULL DEFAULT '0' AFTER timeout");

	$command='mysql -h' .$database_hostname . ' -P'. $database_port .' -u' .$database_username .' -p' .$database_password .' ' .$database_default .' < ' . "\"". $config["base_path"] . "/plugins/license/lic_errorcode_maps.sql\"";
	exec($command, $output, $worked);

	if($worked == 0){
		cacti_log('NOTE: The license error code file imported.', true, 'UPGRADE');
	}else{
		cacti_log('NOTE: The license error code file imported failed.', true, 'UPGRADE');;
	}

	$item_id = db_fetch_cell_prepared("SELECT id FROM graph_templates_item WHERE hash='4ee683974df8801ddd274711ca40cfc4'");

	if(!empty($item_id)){
		execute_sql("Remove double '%' from gprint text_format field", "UPDATE graph_templates_item SET text_format='Availability (%):' WHERE text_format='Availability (%%):' AND graph_type_id=9 AND (local_graph_template_item_id=$item_id OR id=$item_id)");
	}
}
