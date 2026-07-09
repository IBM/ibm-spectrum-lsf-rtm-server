<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2026                                          |
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

    include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
    include_once(dirname(__FILE__) . '/../include/grid_constants.php');
    include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
    include_once(dirname(__FILE__) . '/../../../lib/database.php');
    include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
    include_once(dirname(__FILE__) . '/../../../lib/utility.php');

    // Add new columns (avg_mem, reservationId) to grid_jobs and grid_jobs_finished tables
    $column_array = array(
    	'avg_mem' => "ADD COLUMN `avg_mem` double DEFAULT '0' AFTER `swap_used`",
        'reservationId' => "ADD COLUMN `reservationId` varchar(128) DEFAULT NULL AFTER `cpuPeakReachedTime`"
    );

    add_columns("grid_jobs", $column_array);
    add_columns("grid_jobs_finished", $column_array);

    cacti_log('NOTE: Added columns (avg_mem, reservationId) to grid_jobs and grid_jobs_finished tables', true, 'UPGRADE');

    // The default value is removed.
    db_execute("ALTER TABLE grid_settings MODIFY COLUMN value mediumtext NOT NULL DEFAULT '';");
    db_execute("ALTER TABLE grid_settings ADD COLUMN filter_name VARCHAR(100) NOT NULL DEFAULT '';");
	db_execute("ALTER TABLE grid_settings DROP PRIMARY KEY;");
  	db_execute("ALTER TABLE grid_settings ADD PRIMARY KEY (user_id, name, filter_name);");
    cacti_log('NOTE: Modifing grid_settings table', true, 'UPGRADE');

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.16' WHERE directory IN ('RTM', 'meta', 'lichist', 'gridpend', 'gridcstat')");

    // The Thold 1.8.2 and Syslog 4.2 plugins have schema changes
    db_execute("ALTER TABLE syslog_reports MODIFY COLUMN body varchar(8192) NOT NULL DEFAULT '0';");
    db_execute("ALTER TABLE thold_template MODIFY COLUMN hash varchar(32) NOT NULL;");
    db_execute("ALTER TABLE thold_template MODIFY COLUMN data_template_id int(11) NOT NULL DEFAULT 0;");
    db_execute("ALTER TABLE thold_template MODIFY COLUMN thold_fail_trigger int(11) unsigned DEFAULT NULL;");
    db_execute("ALTER TABLE thold_template MODIFY COLUMN repeat_alert int(11) unsigned DEFAULT NULL;");
    db_execute("ALTER TABLE thold_template MODIFY COLUMN email_body varchar(1024) NOT NULL DEFAULT '';");
    db_execute("ALTER TABLE thold_data MODIFY COLUMN name varchar(255) DEFAULT NULL;");
    db_execute("ALTER TABLE thold_data MODIFY COLUMN notify_extra varchar(512) DEFAULT NULL;");
    db_execute("ALTER TABLE thold_data MODIFY COLUMN email_body varchar(1024) NOT NULL DEFAULT '';");
    db_execute("ALTER TABLE plugin_thold_contacts MODIFY COLUMN type varchar(32) NOT NULL;");
    db_execute("ALTER TABLE plugin_thold_daemon_data MODIFY COLUMN rrd_reindexed varchar(600) NOT NULL;");
    db_execute("ALTER TABLE plugin_thold_log MODIFY COLUMN threshold_value varchar(64) NOT NULL;");
    db_execute("ALTER TABLE plugin_thold_log MODIFY COLUMN description varchar(255) NOT NULL;");

    db_execute("ALTER TABLE grid_processes MODIFY COLUMN taskname varchar(40) NOT NULL DEFAULT '0';");
    db_execute("UPDATE grid_processes SET taskname='REMOVING_STALE_PENDING_REASONS' where taskname='REMOVING_STALE_PENDI' ;");

    // Enable partitioning if not set
    if (read_config_option('grid_partitioning_enable') !== 'on') {
        set_config_option('grid_partitioning_enable', 'on');
        cacti_log('NOTE: Enabled Database Record Partitioning', true, 'UPGRADE');
    }
    
    //Enable partition table upgrade utility
    db_execute("REPLACE INTO settings (name, value) VALUES ('grid_partitions_upgrade_status', 'scheduled')");
    
    // Clean unused job stat
    db_execute("DELETE FROM grid_jobs_stats WHERE stat = 'PROV'");

    // Clean old pollers
    db_execute("DELETE FROM grid_pollers WHERE poller_name in ('Poller for LSF 9.1','Poller for LSF 10.1')");

    // Create entry for poller LSF 10.1.0.15
    db_execute("INSERT INTO grid_pollers (`poller_name`, `poller_lbindir`, `lsf_version`) VALUES ('Poller for LSF 10.1.0.15','/opt/IBM/rtm/lsf10.1.0.15/bin/',10010015)");
}

function partition_tables_to_10_2_0_16() {
    return array(
        'grid_jobs_finished' => array(
        	'columns' => array(
        		'avg_mem' => array('type' => 'double', 'NULL' => true, 'default' => '0', 'after' => 'swap_used'),
        		'reservationId' => array('type' => 'varchar(128)', 'NULL' => true, 'default' => NULL, 'after' => 'cpuPeakReachedTime')
			    )
		    )
    );
}
