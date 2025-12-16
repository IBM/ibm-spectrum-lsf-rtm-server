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

	include_once(dirname(__FILE__) . '/../lib/grid_functions.php');
	include_once(dirname(__FILE__) . '/../include/grid_constants.php');
	include_once(dirname(__FILE__) . '/../../../lib/rtm_db_upgrade.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/plugins.php');
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/template.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_device.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_data_source.php');

	cacti_log('NOTE: Upgrading grid to v10.2.0.1 ...', true, 'UPGRADE');

	cacti_log('NOTE: Register 6 hooks for device page.', true, 'UPGRADE');;

	api_plugin_register_hook('grid', 'customize_graph', 'grid_elim_customize_graph', 'setup.php');
	api_plugin_register_hook('grid', 'customize_template_details', 'grid_elim_customize_template_details', 'setup.php');
	api_plugin_register_hook('grid', 'device_filter_start', 'grid_device_filter_start', 'setup.php');
	api_plugin_register_hook('grid', 'device_sql_where', 'grid_device_sql_where', 'setup.php');
	api_plugin_register_hook('grid', 'device_display_text', 'grid_device_display_text', 'setup.php');
	api_plugin_register_hook('grid', 'device_table_replace', 'grid_device_table_replace', 'setup.php');

	if (api_plugin_is_enabled('grid')) {
		api_plugin_enable_hooks('grid');
	}

	cacti_log('NOTE: Importing RTM templates for 10.2.0.1 ...', true, 'UPGRADE');

	$grid_templates = array(
		"1" => array (
			'value' => 'Grid Summary',
			'name' => 'cacti_host_template_grid_summary.xml'
		),
		"2" => array (
			'value' => 'GRID - Projects - All - Graphs',
			'name' => 'cacti_data_query_grid_-_projects_-_all_-_graphs.xml'
		)
	);

	foreach($grid_templates as $grid_template) {
		if (file_exists(dirname(__FILE__) . "/../templates/upgrades/10_2_0_1/" . $grid_template['name'])) {
			cacti_log('NOTE: Importing ' . $grid_template['value'], true, 'UPGRADE');
			$results = rtm_do_import(dirname(__FILE__) . "/../templates/upgrades/10_2_0_1/" . $grid_template['name']);
		}
	}

	cacti_log('NOTE: Templates Import Complete.', true, 'UPGRADE');

	$data = array();
	$data['columns'][] = array('name' => 'maxMem', 'type' => 'varchar(20)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'maxSwap', 'type' => 'varchar(20)', 'NULL' => true, 'default' => '');
	$data['columns'][] = array('name' => 'maxTmp', 'type' => 'varchar(20)', 'NULL' => true, 'default' => '');
	$data['primary']   = array('host', 'clusterid');
	db_update_table('grid_hostinfo', $data);

	$data = array();
	$data['columns'][] = array('name' => 'last_updated', 'type' => 'timestamp', 'NULL' => false, 'default' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP');
	$data['columns'][] = array('name' => 'present', 'unsigned' => true, 'type' => 'tinyint(3)', 'NULL' => false, 'default' => '0');
	$data['primary'] = array('queuename','clusterid');
	db_update_table('grid_queues', $data);

	//Fix 232670
	$cdef_id = db_fetch_cell("select id from cdef where hash='634a23af5e78af0964e8d33b1a4ed26b'");
	if (!empty($cdef_id)) {
		db_execute("update graph_templates_graph set vertical_label = 'bytes'
			where graph_template_id in (select id from graph_templates where hash='6992ed4df4b44f3d5595386b8298f0ec') and local_graph_template_graph_id<>0");
		cacti_log('NOTE: Graph Template: Linux - Memory Usage updated.', true, 'UPGRADE');;

		$update_graph_templates = array(
			//'Linux - Memory Usage' => '6992ed4df4b44f3d5595386b8298f0ec',
			'GRID - Applications - Memory Stats' => 'd137adb9843f4c7d4a5a214fbfbfa348',
			'GRID - Host Group - Memory Stats' => '607920c266f264ab6ebe090fd847737b',
			'GRID - Job Groups - Memory Stats' => 'a3893a8e448da14deb1ad2e24a003669',
			'GRID - License Project - Memory Stats' => '65a52969f503ab1be69fbb0bf34feb5e',
			'GRID - Projects - Level 1 - Memory Stats' => '8e6f92257670768488d8be3db65ac2c1',
			'GRID - User Group - Memory Stats' => '20eb1e1528f2f6a76ff3e49db6ddc8fd',
			'GRID - Applications - VM Stats' => 'd9b15035d21b98a4276e81fab4c41492',
			'GRID - Job Groups - VM Stats' => '4c988731b38a12c27fb36612d4e2e288',
			'GRID - License Project - VM Stats' => 'e29c4d9ab6964a4d4531a62138396ae6',
			'GRID - Projects - Level 1 - VM Stats' => 'fac467230b46e26e7c18b157efffb956',
			'GRID - Projects - All - Memory Stats' => 'b322cf9b97576bbbe714bcdb6dbffc0d',
			'GRID - Projects - All - VM Stats' => 'e756345d0f2ac4e65b56a0826caac618'
				);
		foreach ($update_graph_templates as $name => $hash) {
			db_execute_prepared("update graph_templates_item set cdef_id = $cdef_id
				where graph_template_id in (select id from graph_templates where hash= ? ) and local_graph_template_item_id<>0", array($hash));
			db_execute_prepared("update graph_templates_graph set vertical_label = 'bytes'
				where graph_template_id in (select id from graph_templates where hash= ? ) and local_graph_template_graph_id<>0", array($hash));
			cacti_log('NOTE: Graph Template: ' . $name . ' updated.', true, 'UPGRADE');
		}
	}

	//Fix 233220, Cluster level graphs that based on "Cluster/Host" template don't work well
	$grid_hosts = db_fetch_assoc("SELECT id FROM host WHERE host_template_id IN (SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4')");
	if (cacti_sizeof($grid_hosts)) {
		foreach ($grid_hosts as $grid_host) {
			$host_value = $grid_host['id'];
			$ch_data_templates = array( 	 // Cluster/Host data templates
				'Cluster/Host Job Statistics' => array(
					'data_template_hash' => 'afe1abbeacb27ec21ccb0a3c661838b2',
					'data_input_field_hash' => '7fe32b1db0ea5a832503188dc0456dc1'
					//'data_input_hash' => 'cdb6b0efe61610286c6ea6989eb14a16'
					),
				'Cluster/Host Load Average' => array(
					'data_template_hash' => '4045358ba5a1e4111950b613accd7f0d',
					'data_input_field_hash' => '2b1300a0d6ead3c9cecb105ca32a51e6'
					//'data_input_hash' => '256c96676b915856648af5a1f7baf4a1'
					),
				'Cluster/Host IO Levels' => array(
					'data_template_hash' => 'f018f70c7c979eed1cc6b3ca1812575d',
					'data_input_field_hash' => 'f6f1b65d709541eec49a5a51d96cd919'
					//'data_input_hash' => 'b1749e1fa6655198fdc160f68007c3fe'
					),
				'Cluster/Host Available Memory' => array(
					'data_template_hash' => '86d6ad38489770a23093d6a4ab082073',
					'data_input_field_hash' => '273942f905dcd59df70ea02bb5af94b2'
					//'data_input_hash' => '57b4b8f5f8c8be3326b551130abbd905'
					),
				'Cluster/Host CPU Utilization' => array(
					'data_template_hash' => 'f8d2d0a63465b9539f9872a00a27cf32',
					'data_input_field_hash' => 'c67e51b3d83dc938c623fdf824a0e757'
					//'data_input_hash' => '1e5986ff0339ce1c26b753a427449cc1'
					)
				);

			foreach ($ch_data_templates as $ch_name => $ch_data_template) {
				$local_data_id = db_fetch_cell("select id from data_local where data_template_id in
									(select id from data_template where hash='" . $ch_data_template['data_template_hash'] . "')
									AND host_id=$host_value");
				$data_template_data_id = db_fetch_cell("select id from data_template_data where local_data_id='$local_data_id'");
				$data_input_field_id = db_fetch_cell("select id from data_input_fields where hash='" . $ch_data_template['data_input_field_hash'] . "'");
				db_execute_prepared("REPLACE INTO data_input_data
							(data_input_field_id, data_template_data_id, t_value, value)
							VALUES
							(?, ?, '', ?)",
							array($data_input_field_id, $data_template_data_id, 'yes')
						);
				update_poller_cache($local_data_id, true);

				cacti_log("NOTE: Graphs on Device $host_value with Graph Template $ch_name Updated.", true, 'UPGRADE');
			}
		}
	}

	$column_arr= array(
		'options4' => "ADD COLUMN `options4` int(10) unsigned NOT NULL default '0' AFTER `options3`",
		'num_gpus' => "ADD COLUMN `num_gpus` int(10) unsigned NOT NULL default '0' AFTER `maxNumProcessors`",
		'gpu_mode' => "ADD COLUMN `gpu_mode` int(10) unsigned NOT NULL default '0' AFTER `num_gpus`",
		'gpu_mem_used' => "ADD COLUMN `gpu_mem_used` double default '0' AFTER `max_memory`",
		'gpu_max_memory' => "ADD COLUMN `gpu_max_memory` double default '0' AFTER `gpu_mem_used`",
		'gpu_exec_time' => "ADD COLUMN `gpu_exec_time` int(10) unsigned NOT NULL default '0' AFTER `run_time`",
		'gpuResReq' => "ADD COLUMN `gpuResReq` varchar(512) default NULL AFTER `res_requirements`",
		'gpuCombinedResreq' => "ADD COLUMN `gpuCombinedResreq` varchar(512) default '' AFTER `effectiveResreq`",
		'gpuEffectiveResreq' => "ADD COLUMN `gpuEffectiveResreq` varchar(512) default '' AFTER `gpuCombinedResreq`"
	);

	//Do not use Cacti::db_update_table because db_update_table alter table column one by one
	add_columns("grid_jobs", $column_arr);
	add_columns("grid_jobs_finished", $column_arr);

	$column_arr= array(
		'ngpus' => "ADD COLUMN `ngpus` mediumint(8) NOT NULL default '0' AFTER `processes`",
	);
	add_columns("grid_jobs_jobhosts", $column_arr);
	add_columns("grid_jobs_jobhosts_finished", $column_arr);

	if(db_index_exists('grid_jobs_gpu_rusage', 'cid_jid_idx_subtime_sttime_hname_gid')) {
		db_execute("ALTER TABLE grid_jobs_gpu_rusage DROP KEY cid_jid_idx_subtime_sttime_hname_gid, ADD UNIQUE KEY `cid_jid_idx_subtime_hname_gid_utime` (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`gpu_id`,`update_time`)");
	}

	$column_arr= array(
		'app' => "ADD COLUMN `app` varchar(40) NOT NULL default '' AFTER `queue`",
		'gpu_wall_time' => "ADD COLUMN `gpu_wall_time` int(10) unsigned NOT NULL default '0' AFTER `jobs_wall_time`",
		'gpu_avg_mem' => "ADD COLUMN `gpu_avg_mem` double NOT NULL default '0' AFTER `max_memory`",
		'gpu_max_mem' => "ADD COLUMN `gpu_max_mem` double NOT NULL default '0' AFTER `gpu_avg_mem`",
		'gpus_in_state' => "ADD COLUMN `gpus_in_state` int(10) unsigned NOT NULL default '0' AFTER `slots_in_state`"
	);
    $index_arr = array(
        "app" => "ADD INDEX `app` (`app`)"
    );

	if (db_column_exists('grid_job_daily_stats', 'id')) {
		db_execute("ALTER TABLE grid_job_daily_stats DROP COLUMN id, DROP PRIMARY KEY");
	} else {
		db_execute("ALTER TABLE grid_job_daily_stats DROP PRIMARY KEY");
	}

	add_columns_indexes("grid_job_daily_stats", $column_arr, $index_arr);
	db_execute("ALTER TABLE grid_job_daily_stats ADD PRIMARY KEY (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded`)");

	db_execute("ALTER TABLE grid_job_interval_stats DROP PRIMARY KEY");
	add_columns_indexes("grid_job_interval_stats", $column_arr, $index_arr);
	db_execute("ALTER TABLE grid_job_interval_stats ADD PRIMARY KEY (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded`)");

	create_table("grid_host_closure_events", "CREATE TABLE IF NOT EXISTS `grid_host_closure_events` (
		  `clusterid` int(10) unsigned NOT NULL default '0',
		  `host` varchar(64) NOT NULL default '',
		  `admin` varchar(45) NOT NULL default '',
		  `event_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		  `lockid` varchar(128) NOT NULL default '',
		  `hCtrlMsg` varchar(255) NOT NULL default '',
		  `end_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
		  PRIMARY KEY  (`clusterid`,`host`,`event_time`,`lockid`),
		  KEY `summarized` (`clusterid`,`host`,`lockid`),
		  KEY `event_time_lockid` (`event_time`,`lockid`),
		  KEY `last_updated` (`last_updated`),
		  KEY `lockid` (`lockid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	create_table("grid_host_closure_events_finished", "CREATE TABLE IF NOT EXISTS `grid_host_closure_events_finished` LIKE grid_host_closure_events;");
	create_table("grid_host_closure_lockids", "CREATE TABLE IF NOT EXISTS `grid_host_closure_lockids` (
		  `clusterid` int(10) unsigned NOT NULL default '0',
		  `lockid` varchar(128) NOT NULL default '',
		  `last_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
		  PRIMARY KEY  (`clusterid`,`lockid`),
		  KEY `lockid` (`lockid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

	//SUP#RTC##240043
	$column_arr= array(
		'graph_templates_item_id' => "MODIFY COLUMN `graph_templates_item_id` int(12) unsigned NOT NULL",
		'grid_elim_templates_item_id' => "MODIFY COLUMN `grid_elim_templates_item_id` int(12) unsigned NOT NULL"
	);
	modify_columns("grid_elim_templates_item_map", $column_arr);

	//After move grid_setup_table() from showTab to check_config in 10.2.0.1,
	//these columns are not added if deploy 10.2.0.1 on top of fresh 10.2 before any web access.
    api_plugin_db_add_column('grid', 'host', array(
		'name' => 'clusterid',
		'unsigned' => true,
		'type' => "int(10)",
		'NULL' => false,
		'default' => '0',
		'after' => 'host_template_id'
	));
    api_plugin_db_add_column('grid', 'host', array(
		'name' => 'monitor',
		'type' => "char(3)",
		'NULL' => false,
		'default' => 'on',
		'after' => 'disabled'
	));
	api_plugin_db_add_column('grid', 'user_auth', array(
		'name' => 'grid_settings',
		'type' => 'char(2)',
		'NULL' => false,
		'default' => 'on',
		'after' => 'graph_settings'
	));

	$column_arr= array(
		'lsf_unit' => "ADD COLUMN `lsf_unit` varchar(4) NOT NULL DEFAULT '' AFTER `lsf_lic_schedhosts`",
	);
	add_columns("grid_clusters", $column_arr);

	execute_sql("Keep old date format in CLog", "INSERT IGNORE INTO `settings` (name, value) VALUES ('default_date_format', '4'), ('default_datechar', '1');");

	//Re-register 'General LSF Data' realm to remove 'Guest Reachable' grid_default.php
	api_plugin_register_realm('grid', 'grid_shared.php,grid_bjobs.php,grid_bzen.php,grid_bhosts.php,grid_bhosts_closed.php,grid_bqueues.php,grid_lsload.php,grid_lshosts.php,grid_bhpart.php,grid_busers.php,grid_jobgraph.php,grid_jobgraphzoom.php,grid_ajax.php', 'General LSF Data', 0);

	//update version for other plugins that file touched, and no much DB change
	db_execute("UPDATE plugin_config SET version='10.2.0.1' WHERE directory IN ('RTM', 'gridcstat', 'lichist', 'gridpend', 'benchmark', 'meta')");

	//Enable partition table upgrade utility
	db_execute("REPLACE INTO settings (name, value) VALUES ('grid_partitions_upgrade_status', 'scheduled')");
}

function partition_tables_to_10_2_0_1(){
	return array(
		'grid_jobs_finished' => array(
			'columns' => array(
				'options4'           => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'options3'),
				'num_gpus'           => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'maxNumProcessors'),
				'gpu_mode'           => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'num_gpus'),
				'gpu_mem_used'       => array( 'type' => 'double', 'NULL' => true, 'default' => '0', 'after' => 'max_memory'),
				'gpu_max_memory'     => array( 'type' => 'double', 'NULL' => true, 'default' => '0', 'after' => 'gpu_mem_used'),
				'gpuResReq'          => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'res_requirements'),
				'gpuCombinedResreq'  => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'effectiveResreq'),
				'gpuEffectiveResreq' => array( 'type' => 'varchar(512)', 'NULL' => true, 'default' => '', 'after' => 'gpuCombinedResreq'),
				'gpu_exec_time'      => array( 'unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'run_time')
							)
		),
		'grid_jobs_jobhosts_finished' => array(
			'columns' => array(
				'ngpus' => array('type' => 'mediumint(8)', 'NULL' => false, 'default' => '0', 'after' => 'processes')
			)
		),
		'grid_jobs_gpu_rusage' => array(
			'indexes' => array(
				'unique' => "ADD UNIQUE KEY `cid_jid_idx_subtime_hname_gid_utime` (`clusterid`,`jobid`,`indexid`,`submit_time`,`host`,`gpu_id`,`update_time`)"
			),
			'drop' => array(
				'indexes' => array('cid_jid_idx_subtime_sttime_hname_gid')
			)
		),
		'grid_job_daily_stats' => array(
			'columns' => array(
				'app' 			=> array('type' => 'varchar(40)', 'NULL' => false, 'default' => '', 'after' => 'queue'),
				'gpu_wall_time' => array('unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'jobs_wall_time'),
				'gpus_in_state' => array('unsigned' => true, 'type' => 'int(10)', 'NULL' => false, 'default' => '0', 'after' => 'slots_in_state'),
				'gpu_avg_mem'   => array('type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'max_memory'),
				'gpu_max_mem'   => array('type' => 'double', 'NULL' => false, 'default' => '0', 'after' => 'gpu_avg_mem')
			),
			'indexes' => array(
				"primary" => "ADD PRIMARY KEY (`clusterid`,`user`,`stat`,`projectName`,`exec_host`,`from_host`,`queue`,`app`,`date_recorded`)",
				"app" => "ADD INDEX `app` (`app`)"
			)
		)
	);
}
