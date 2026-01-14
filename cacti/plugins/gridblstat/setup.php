<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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

/* plugin_gridblstat_install - provides a generic PIA 2.x installer routine to register all plugin
     hook functions.
   @returns - null */

function gridblstat_page_head() {
	global $config;
	print get_md5_include_js('/plugins/RTM/include/select2/select2.min.js');
	print get_md5_include_css('/plugins/RTM/include/select2/select2.min.css');
	print get_md5_include_js('/plugins/RTM/include/select2/select2_custom.js');
	print get_md5_include_css('/plugins/RTM/include/select2/select2_custom.css');
}

function plugin_gridblstat_install () {
	api_plugin_register_hook('gridblstat', 'config_settings',      'gridblstat_config_settings', 'setup.php');
	api_plugin_register_hook('gridblstat', 'config_arrays',        'gridblstat_config_arrays',   'setup.php');
	api_plugin_register_hook('gridblstat', 'poller_bottom',        'gridblstat_poller_bottom',   'setup.php');
	api_plugin_register_hook('gridblstat', 'draw_navigation_text', 'gridblstat_navigation',      'setup.php');
	//api_plugin_register_hook('gridblstat', 'config_insert',        'gridblstat_config_insert',   'setup.php');
	api_plugin_register_hook('gridblstat', 'grid_menu',            'gridblstat_grid_menu',       'setup.php');
	api_plugin_register_hook('gridblstat', 'grid_tab_down',        'gridblstat_grid_tab_down',     'setup.php');
	api_plugin_register_hook('gridblstat', 'page_head',              'gridblstat_page_head',            'setup.php');
	api_plugin_register_hook('gridblstat', 'grid_cluster_remove', 'gridblstat_grid_cluster_remove', 'setup.php');

	gridblstat_setup_table_new ();
}

/* plugin_gridblstat_uninstall - a generic uninstall routine.  Right now it will do nothing as I
     don't want the tables removed from the system except for forcably by the user.  This
     may change at some point.
   @returns - null */
function plugin_gridblstat_uninstall () {
	db_execute('DROP TABLE IF EXISTS grid_blstat');
	db_execute('DROP TABLE IF EXISTS grid_blstat_cluster_use');
	db_execute('DROP TABLE IF EXISTS grid_blstat_clusters');
	db_execute('DROP TABLE IF EXISTS grid_blstat_collector_clusters');
	db_execute('DROP TABLE IF EXISTS grid_blstat_collectors');
	db_execute('DROP TABLE IF EXISTS grid_blstat_distribution');
	db_execute('DROP TABLE IF EXISTS grid_blstat_feature_map');
	db_execute('DROP TABLE IF EXISTS grid_blstat_projects');
	db_execute('DROP TABLE IF EXISTS grid_blstat_service_domains');
	db_execute('DROP TABLE IF EXISTS grid_blstat_tasks');
	db_execute('DROP TABLE IF EXISTS grid_blstat_users');

	db_execute('DELETE FROM settings WHERE name LIKE "%blstat%"');
}

/* plugin_gridblstat_check_config - this routine will verify if there is any upgrade steps that
     need to be performed on the plugin.
   @returns - (bool) always returns true for some reason */
function plugin_gridblstat_check_config () {
	/* Here we will check to ensure everything is configured */
	gridblstat_check_upgrade();
	return TRUE;
}

/* plugin_gridblstat_upgrade - this routine is similar to the config_check.  My guess is that
     the author, aka me, is doing something wrong here as a result of this discovery.
   @returns - (bool) always returns true for some reason */
function plugin_gridblstat_upgrade () {
	/* Here we will upgrade to the newest version */
	gridblstat_check_upgrade();
	return FALSE;
}

function plugin_gridblstat_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/gridblstat/INFO', true);
	return $info['info'];
}

function get_gridblstat_version() {
	$info = plugin_gridblstat_version();
	if(!empty($info) && isset($info['version'])){
		return $info['version'];
	}
	return RTM_VERSION;
}

/* gridblstat_check_upgrade - this generic routine verifies if the plugin needs upgrading or
     not.  If it does require upgrading, then it performs that upgrade and updates
     the plugin config table with the new version.
   @returns - NULL */
function gridblstat_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = get_gridblstat_version();

	$old = db_fetch_row('SELECT *
		FROM plugin_config
		WHERE directory="gridblstat"');

	if (cacti_sizeof($old) && $current != $old['version']) {
		/* if the plugin is installed and/or active */
		if ($old['status'] == 1 || $old['status'] == 4) {
			/* re-register the hooks */
			plugin_gridblstat_install();

			/* perform a database upgrade */
			gridblstat_database_upgrade($old, $current);
		}
	}

	/* update the plugin information */
	$info = plugin_gridblstat_version();

	$id = db_fetch_cell('SELECT id
		FROM plugin_config
		WHERE directory="gridblstat"');

	db_execute_prepared("UPDATE plugin_config
		SET name=?,
		author=?,
		webpage=?,
		version=?
		WHERE id=?",
		array($info['longname'], $info['author'], $info['homepage'], $info['version'], $id));
}

/* gridblstat_database_upgrade - this routine is where we 'should' be performing the upgrade.
   @returns - (bool) always returns true for some reason */
function gridblstat_database_upgrade($old, $version) {
	global $plugins, $config;
	include_once(dirname(__FILE__) . '/../grid/lib/grid_functions.php');

	if (cacti_version_compare($old['version'], 2.2, '<')) {
		db_execute("ALTER TABLE `grid_blstat_users`
			ADD COLUMN `indexid` int(10) unsigned NOT NULL AFTER `jobid`");

		db_execute("ALTER TABLE `grid_blstat_users`
			DROP PRIMARY KEY,
			ADD PRIMARY KEY USING HASH(`jobid`,`indexid`,`cluster`,`resource`)");
	}

	if (cacti_version_compare($old['version'], 3.0, '<')) {
		db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_clusters` (
			`feature` varchar(64) NOT NULL,
			`service_domain` varchar(64) NOT NULL,
			`cluster` varchar(20) NOT NULL,
			`clusterid` int(10) unsigned NOT NULL default '0',
			`share` float NOT NULL,
			`alloc` int(10) unsigned NOT NULL,
			`inuse` int(10) unsigned NOT NULL,
			`reserve` int(10) unsigned NOT NULL,
			`over` int(10) unsigned NOT NULL,
			`peak` int(10) unsigned NOT NULL,
			`buffer` int(10) unsigned NOT NULL,
			`free` int(10) unsigned NOT NULL,
			`demand` int(10) unsigned NOT NULL,
			`max_reclaim` int(10) unsigned NOT NULL default '0',
			`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
			PRIMARY KEY  USING BTREE (`feature`,`service_domain`,`cluster`),
			KEY `present` USING BTREE (`present`))
			ENGINE=InnoDB
			COMMENT='License Scheduler Cluster Details' DEFAULT CHARSET=latin1");

		db_execute("ALTER TABLE `grid_blstat`
			ADD COLUMN `total_tokens` int(10) unsigned NOT NULL AFTER `total_free`,
			ADD COLUMN `total_alloc` int(10) unsigned NOT NULL AFTER `total_tokens`,
			ADD COLUMN `total_use` int(10) unsigned NOT NULL");
	}

	if (cacti_version_compare($old['version'], 5.0, '<')) {
		db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_collectors` (
			`lsid` int(10) unsigned NOT NULL auto_increment,
			`clusterid` int(10) unsigned NOT NULL,
			`cacti_host` int(10) unsigned NULL,
			`name` varchar(128) NOT NULL,
			`region` varchar(40) DEFAULT '',
			`ls_version` int(10) unsigned NOT NULL,
			`ls_hosts` varchar(256) NOT NULL,
			`ls_admin` varchar(64) NOT NULL,
			`ls_port` int(10) unsigned NOT NULL,
			`disabled` char(2) default '',
			`poller_freq` int(10) unsigned NOT NULL,
			`blstat_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			`graph_freq` int(10) unsigned NOT NULL,
			`graph_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (`lsid`))
			ENGINE=MyISAM
			COMMENT='Defines Various License Scheduler BLD Settings'");

		db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_collector_clusters` (
			`lsid` int(10) unsigned NOT NULL default '0',
			`clusterid` int(18) unsigned NOT NULL default '0',
			PRIMARY KEY  (`lsid`,`clusterid`))
			ENGINE=MyISAM
			COMMENT='Contains list of Clusters included in the bld'");

		/* add ls_id to various tables.  default to 1 */
		db_execute("ALTER TABLE grid_blstat_clusters ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_clusters DROP PRIMARY KEY,
			ADD PRIMARY KEY USING BTREE(`lsid`, `feature`, `service_domain`, `cluster`)");

		db_execute("ALTER TABLE grid_blstat ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat DROP PRIMARY KEY,
			ADD PRIMARY KEY USING HASH(`lsid`, `feature`, `service_domain`)");

		db_execute("ALTER TABLE grid_blstat_cluster_use ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_cluster_use DROP PRIMARY KEY,
			ADD PRIMARY KEY USING HASH(`lsid`, `feature`, `project`, `cluster`)");

		db_execute("ALTER TABLE grid_blstat_feature_map ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_feature_map DROP PRIMARY KEY,
			ADD PRIMARY KEY USING BTREE(`lsid`, `bld_feature`, `lic_feature`)");

		db_execute("ALTER TABLE grid_blstat_distribution ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_distribution DROP PRIMARY KEY,
			ADD PRIMARY KEY USING HASH(`lsid`, `feature`, `service_domain`)");

		db_execute("ALTER TABLE grid_blstat_projects ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_projects DROP PRIMARY KEY,
			ADD PRIMARY KEY USING BTREE(`lsid`, `feature`, `service_domain`, `project`)");

		db_execute("ALTER TABLE grid_blstat_users ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_users DROP PRIMARY KEY,
			ADD PRIMARY KEY USING HASH(`lsid`, `jobid`, `indexid`, `cluster`, `resource`)");

		db_execute("ALTER TABLE grid_blstat_service_domains ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_service_domains DROP PRIMARY KEY,
			ADD PRIMARY KEY USING BTREE(`lsid`, `service_domain`, `lic_id`)");

		db_execute("ALTER TABLE grid_blstat_tasks ADD COLUMN lsid INTEGER unsigned default '0' FIRST");
		db_execute("ALTER TABLE grid_blstat_tasks DROP PRIMARY KEY,
			ADD PRIMARY KEY USING BTREE(`lsid`, `feature`, `host`, `user`, `tid`)");

		$lsid        = 1;
		$clusterid   = read_config_option('gridblstat_cluster');
		$name        = 'Legacy Collector';
		$ls_version  = read_config_option('gridblstat_version');
		$ls_hosts    = read_config_option('gridblstat_hosts');
		$ls_admin    = read_config_option('gridblstat_admin');
		$ls_port     = read_config_option('gridblstat_port');

		if ($ls_port == '') $ls_port = 9581;

		$disabled    = '';
		$poller_freq = read_config_option('gridblstat_poller_freq');
		$graph_freq  = read_config_option('gridblstat_graph_freq');

		$cacti_host = db_fetch_cell("SELECT h.id FROM host AS h
			INNER JOIN host_template AS ht
			ON ht.id=h.host_template_id
			WHERE ht.hash='b1528fb95b04821b0e5b6a5aedd8e659'");

		db_execute_prepared("REPLACE INTO grid_blstat_collectors
			(lsid, clusterid, name, ls_version, ls_hosts, ls_admin, ls_port, disabled, poller_freq, graph_freq, cacti_host)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($lsid, $clusterid, $name, $ls_version, $ls_hosts, $ls_admin, $ls_port, $disabled, $poller_freq, $graph_freq, $cacti_host));

		if (!empty($cacti_host)) {
			/* upgrade the graph templates */
			$php_bin  = read_config_option('path_php_binary');
			$path_web = read_config_option('path_webroot');

			$hashes = array(
				'51b53ceb3f70861c47fd169dae72a3bf',
				'e1458d6832066ae20b19f7dd6ba62002',
				'52beb3a749e29867b2d5d92f1a487e09',
				'276d7b3235d9fe1071346abfcbead663');

			$snmp_query_ids = db_fetch_assoc("SELECT id,hash FROM snmp_query WHERE hash IN ('" . implode("','", $hashes) . "')");

			foreach($snmp_query_ids as $sq) {
				switch($sq['hash']) {
				case '51b53ceb3f70861c47fd169dae72a3bf':
					$count = 2;
					break;
				case 'e1458d6832066ae20b19f7dd6ba62002':
					$count = 1;
					break;
				case '52beb3a749e29867b2d5d92f1a487e09':
					$count = 1;
					break;
				case '276d7b3235d9fe1071346abfcbead663':
					$count = 2;
					break;
				}

				/* update graphs */
				$graphs = db_fetch_assoc_prepared('SELECT id, snmp_index FROM graph_local WHERE snmp_query_id=?', array($sq['id']));

				if (cacti_sizeof($graphs)) {
					foreach($graphs as $g) {
						if (substr_count($g['snmp_index'], '|') == $count) {
							db_execute_prepared("UPDATE graph_local SET snmp_index=? WHERE id=?", array("1|" . $g['snmp_index'], $g['id']));
						}
					}
				}

				/* update data sources */
				$ds = db_fetch_assoc_prepared("SELECT id, snmp_index FROM data_local WHERE snmp_query_id=?", array($sq['id']));

				if (cacti_sizeof($ds)) {
					foreach($ds as $d) {
						if (substr_count($d['snmp_index'], '|') == $count) {
							db_execute_prepared("UPDATE data_local SET snmp_index=? WHERE id=?", array("1|" . $d['snmp_index'], $d['id']));
						}
					}
				}

				passthru($php_bin." -q " . $path_web . "/cli/poller_reindex_hosts.php -id=" . $cacti_host . " -qid=" . $sq['id']);
			}
		}
	}

	if (cacti_version_compare($old['version'], 6.0, '<')) {
		db_execute("ALTER IGNORE TABLE grid_blstat_cluster_use
			ADD COLUMN `type` TINYINT unsigned default NULL AFTER project,
			ADD COLUMN `over` INT unsigned default NULL AFTER reserve,
			ADD COLUMN alloc INT unsigned default NULL AFTER clusterid,
			ADD COLUMN demand INT unsigned default NULL AFTER free,
			ADD COLUMN `target` INT unsigned default NULL AFTER need");

		db_execute("DROP TABLE IF EXISTS `grid_blstat_cluster_use_fd`");
	}

	return TRUE;
}

/* gridblstat_check_dependencies - this routine is where I would check for other plugin
     dependencies.  There only plugin dependency at this moment is the PIA itself.
   @returns - (bool) always returns true since there are not dependencies */
function gridblstat_check_dependencies() {
	global $plugins, $config;
	return TRUE;
}

/* gridblstat_setup_table_new - this routine creates all gridblstat table if they don't
     already exist.  At some point, they would work better with the uninstall routine
     but not for now.
   @returns - NULL */
function gridblstat_setup_table_new () {
	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`type` int(10) unsigned NOT NULL,
		`total_inuse` int(10) unsigned NOT NULL,
		`total_reserve` int(10) unsigned NOT NULL,
		`total_free` int(10) unsigned NOT NULL,
		`total_tokens` int(10) unsigned NOT NULL,
		`total_alloc` int(10) unsigned NOT NULL,
		`total_use` int(10) unsigned NOT NULL,
		`total_others` int(10) unsigned NOT NULL,
		`fd_total` int(10) unsigned DEFAULT '0',
		`fd_adj` int(10) unsigned DEFAULT '0',
		`fd_max` int(10) unsigned DEFAULT '0',
		`fd_step` int(10) DEFAULT '0',
		`fd_util` double DEFAULT '0',
		`fd_target` double DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1' DEFAULT '1',
		PRIMARY KEY (`lsid`,`feature`,`service_domain`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='General License Scheduler Information';");

	/* upgrade the database if you are an old system */
	$columns = array_rekey(db_fetch_assoc("SHOW COLUMNS IN grid_blstat"), "Field", "Field");
	if (!in_array("total_tokens", $columns)) {
		db_execute("ALTER TABLE grid_blstat
			ADD COLUMN type INT(10) UNSIGNED NOT NULL AFTER service_domain,
			ADD COLUMN total_tokens INT(10) UNSIGNED NOT NULL AFTER total_free,
			ADD COLUMN total_alloc INT(10) UNSIGNED NOT NULL AFTER total_tokens,
			ADD COLUMN total_use INT(10) UNSIGNED NOT NULL AFTER total_alloc;");
	}

	if (!in_array("fd_total", $columns)) {
		db_execute("ALTER TABLE grid_blstat
			ADD COLUMN fd_total INT(10) UNSIGNED DEFAULT '0' AFTER total_others,
			ADD COLUMN fd_adj INT(10) UNSIGNED DEFAULT '0' AFTER fd_total,
			ADD COLUMN fd_max INT(10) UNSIGNED DEFAULT '0' AFTER fd_adj,
			ADD COLUMN fd_step INT(10) DEFAULT '0' AFTER fd_max,
			ADD COLUMN fd_util DOUBLE DEFAULT '0.0' AFTER fd_step,
			ADD COLUMN fd_target DOUBLE DEFAULT '0.0' AFTER fd_util;");
	}

	if (!in_array("last_updated", $columns)) {
		db_execute("ALTER TABLE grid_blstat
			ADD COLUMN last_updated timestamp NOT NULL default CURRENT_TIMESTAMP AFTER fd_target,
			MODIFY COLUMN present tinyint(3) unsigned NOT NULL default '1';");
	}

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_cluster_use` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`type` tinyint(3) unsigned DEFAULT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`alloc` int(10) unsigned DEFAULT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`over` int(10) unsigned DEFAULT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned DEFAULT NULL,
		`need` int(10) unsigned NOT NULL,
		`target` int(10) unsigned DEFAULT NULL,
		`acum_use` int(10) unsigned NOT NULL,
		`scaled_acum` int(10) unsigned NOT NULL,
		`avail` int(10) unsigned NOT NULL,
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1' DEFAULT '1',
		PRIMARY KEY (`lsid`,`feature`,`project`,`cluster`),
		KEY `present` (`present`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='License Scheduler Use by Cluster';");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_clusters` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL DEFAULT '0',
		`share` float NOT NULL,
		`alloc` int(10) unsigned NOT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`over` int(10) unsigned NOT NULL,
		`peak` int(10) unsigned NOT NULL,
		`buffer` int(10) unsigned NOT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned NOT NULL,
		`max_reclaim` int(10) unsigned NOT NULL DEFAULT '0',
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1' DEFAULT '1',
		PRIMARY KEY (`lsid`,`feature`,`service_domain`,`cluster`) USING BTREE,
		KEY `present` (`present`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='License Scheduler Cluster Details';");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_feature_map` (
		`lsid` int(10) unsigned default '0',
		`bld_feature` varchar(64) NOT NULL,
		`lic_feature` varchar(64) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  USING BTREE (`lsid`, `bld_feature`,`lic_feature`),
		KEY `lic_feature` USING BTREE (`lic_feature`),
		KEY `present` USING BTREE (`present`))
		ENGINE=InnoDB
		COMMENT='Maintains the mapping between BLD and the License Feature' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_distribution` (
		`lsid` int(10) unsigned default '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`total` int(10) unsigned NOT NULL,
		`lsf_use` int(10) unsigned NOT NULL,
		`lsf_deserve` int(10) unsigned NOT NULL,
		`lsf_free` int(10) unsigned NOT NULL,
		`non_lsf_use` int(10) unsigned NOT NULL,
		`non_lsf_deserve` int(10) unsigned NOT NULL,
		`non_lsf_free` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  (`lsid`, `feature`,`service_domain`),
		KEY `present` USING BTREE (`present`))
		ENGINE=InnoDB DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_projects` (
		`lsid` int(10) unsigned NOT NULL DEFAULT '0',
		`feature` varchar(64) NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`share` float NOT NULL,
		`own` int(10) unsigned NOT NULL,
		`inuse` int(10) unsigned NOT NULL,
		`reserve` int(10) unsigned NOT NULL,
		`free` int(10) unsigned NOT NULL,
		`demand` int(10) unsigned NOT NULL,
		`last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1' DEFAULT '1',
		PRIMARY KEY (`lsid`,`feature`,`service_domain`,`project`) USING BTREE,
		KEY `present` (`present`) USING BTREE
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='License Scheduler Project Details';");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_users` (
		`lsid` int(10) unsigned default '0',
		`jobid` int(10) unsigned NOT NULL,
		`indexid` int(10) unsigned NOT NULL,
		`cluster` varchar(20) NOT NULL,
		`clusterid` int(10) unsigned NOT NULL default '0',
		`user` varchar(60) NOT NULL,
		`host` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`start_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`resource` varchar(45) NOT NULL,
		`rusage` int(10) unsigned NOT NULL,
		`service_domain` varchar(64) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  USING HASH (`lsid`, `jobid`,`indexid`,`cluster`,`resource`),
		KEY `user` (`user`),
		KEY `host` (`host`),
		KEY `project` (`project`),
		KEY `resource` USING BTREE (`resource`),
		KEY `present` USING BTREE (`present`))
		ENGINE=InnoDB
		COMMENT='User information from License Scheduler' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_service_domains` (
		`lsid` int(10) unsigned default '0',
		`service_domain` varchar(64) NOT NULL,
		`lic_id` int(10) unsigned NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  USING BTREE (`lsid`, `service_domain`,`lic_id`),
		KEY `present` USING BTREE (`present`))
		ENGINE=InnoDB
		COMMENT='Maintains the mapping between BLD Service Domains and License Services' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_tasks` (
		`lsid` int(10) unsigned default '0',
		`feature` varchar(64) NOT NULL,
		`project` varchar(64) NOT NULL,
		`host` varchar(64) NOT NULL,
		`user` varchar(60) NOT NULL,
		`stat` varchar(10) NOT NULL,
		`tid` int(10) unsigned NOT NULL,
		`connect_time` timestamp NOT NULL default '0000-00-00 00:00:00',
		`terminal` varchar(10) NOT NULL,
		`pgid` int(10) unsigned NOT NULL,
		`cpu_time` int(10) unsigned NOT NULL,
		`memory` int(10) unsigned NOT NULL,
		`swap` int(10) unsigned NOT NULL,
		`cpu_idle` timestamp NOT NULL default '0000-00-00 00:00:00',
		`res_requirements` varchar(128) NOT NULL,
		`command` varchar(256) NOT NULL,
		`present` tinyint(3) unsigned NOT NULL DEFAULT '1',
		PRIMARY KEY  USING BTREE (`lsid`, `feature`,`host`,`user`,`tid`),
		KEY `present` USING BTREE (`present`))
		ENGINE=InnoDB
		COMMENT='License Scheduler Task Details' DEFAULT CHARSET=latin1");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_collectors` (
		`lsid` int(10) unsigned NOT NULL auto_increment,
		`clusterid` int(10) unsigned NOT NULL,
		`cacti_host` int(10) unsigned NULL,
		`name` varchar(128) NOT NULL,
		`region` varchar(40) DEFAULT '',
		`ls_version` int(10) unsigned NOT NULL,
		`ls_hosts` varchar(256) NOT NULL,
		`ls_admin` varchar(64) NOT NULL,
		`ls_port` int(10) unsigned NOT NULL,
		`disabled` char(2) default '',
		`lsf_envdir` varchar(255) NOT NULL DEFAULT '',
		`advanced_enabled` char(2) NOT NULL DEFAULT '',
		`lsf_strict_checking` varchar(10) NOT NULL default 'N',
		`poller_freq` int(10) unsigned NOT NULL,
		`blstat_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		`graph_freq` int(10) unsigned NOT NULL,
		`graph_lastrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (`lsid`))
		ENGINE=MyISAM
		COMMENT='Defines Various License Scheduler BLD Settings'");

	db_execute("CREATE TABLE IF NOT EXISTS `grid_blstat_collector_clusters` (
		`lsid` int(10) unsigned NOT NULL default '0',
		`clusterid` int(18) unsigned NOT NULL default '0',
		PRIMARY KEY  (`lsid`,`clusterid`))
		ENGINE=MyISAM
		COMMENT='Contains list of Clusters included in the bld'");
}

function gridblstat_navigation ($nav) {
	$nav['blstat_collectors.php:'] = array(
		'title' => 'License Schedulers',
		'mapping' => 'index.php:',
		'url' => 'blstat_collectors.php',
		'level' => '1');

	$nav['grid_lsdashboard.php:'] = array(
		'title' => 'License Scheduler Feature Dashboard',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:summary'] = array(
		'title' => 'License Scheduler Feature Dashboard',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:distribution'] = array(
		'title' => 'License Scheduler Distribution',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:projects'] = array(
		'title' => 'License Scheduler Project Use',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:users'] = array(
		'title' => 'License Scheduler User Jobs',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:clusters'] = array(
		'title' => 'License Scheduler Cluster Statistics',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:features'] = array(
		'title' => 'License Scheduler Feature Dashboard',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:checkouts'] = array(
		'title' => 'License Scheduler License Checkouts',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	$nav['grid_lsdashboard.php:graphs'] = array(
		'title' => 'License Scheduler Graphs',
		'mapping' => 'grid_default.php:',
		'url' => 'grid_lsdashboard.php',
		'level' => '0');

	return $nav;
}

function gridblstat_config_settings() {
	global $tabs, $messages,$settings, $grid_projectgroup_filter_levels;

	$tabs['rtmpi'] = __('RTM Plugins', 'grid');

	$temp = array(
		'gridblstat_header_presets' => array(
			'friendly_name' => __('RTM License Scheduler Display Presets', 'gridblstat'),
			'method' => 'spacer',
		),
		'grid_blstat_exception_time' => array(
			'friendly_name' => __('Exception Delay Time', 'gridblstat'),
			'description' => __('How long must a license be checked out without an LSF Job before an Exception is triggered.  We add a delay to compensate for the asynchronous nature of RTM data collection.', 'gridblstat'),
			'method' => 'drop_array',
			'default' => '300',
			'array' => array(
				'60'  => __('%d Minute', 1, 'gridblstat'),
				'300' => __('%d Minutes', 5, 'gridblstat'),
				'600' => __('%d Minutes', 10, 'gridblstat')
			),
		),
		'grid_blstat_add_license_service' => array(
			'friendly_name' => __('Automatically Add License Service for Service Domain', 'gridblstat'),
			'description' => __('Enables the License Scheduler Plugin and adds a license service (License Plugin) if a Service Domain port@server pair is not found in the License Services list.', 'gridblstat'),
			'default' => '',
			'method' => 'checkbox'
		),
		'gridblstat_header_projects' => array(
			'friendly_name' => __('RTM License Scheduler Project Settings', 'gridblstat'),
			'method' => 'spacer',
		),
		'grid_blstat_project_group_aggregation' => array(
			'friendly_name' => __('Should Hierarchical License Project Aggregation be Enabled?', 'gridblstat'),
			'description' => __('Checking this option will enable Hierarchical License Project statistics gathering based upon a predefined License Project Hierarchy delimiter.  You will then be able to view statistics at multiple levels within the License Project Hierarchy.', 'gridblstat'),
			'default' => '',
			'method' => 'checkbox'
		),
		'grid_blstat_job_stats_project_delimiter' => array(
			'friendly_name' => __('License Project Hierarchy Delimiter', 'gridblstat'),
			'description' => __('If you utilize the License Project field to represent a License Project Hierarchy, what delimiter do you use to separate each level of the Hierarchy?', 'gridblstat'),
			'method' => 'textbox',
			'default' => '_',
			'size' => '1',
			'max_length' => '2'
		),
		'grid_blstat_job_stats_project_level_number' => array(
			'friendly_name' => __('Maximum Aggregation Level', 'gridblstat'),
			'description' => __('License Projects are oftentimes treated as Hierarchal objects in LSF.  Select the maximum level of Hierarchy here that is meaningful for reporting.', 'gridblstat'),
			'method' => 'drop_array',
			'default' => '3',
			'array' => $grid_projectgroup_filter_levels,
		)
	);

	if (isset($settings['rtmpi'])) {
		$settings['rtmpi'] = array_merge($settings['rtmpi'], $temp);
	}else{
		$settings['rtmpi'] = $temp;
	}

	$messages['duplicated_ls']=array(
		'message' => __('Duplicated LS Collector: One of master hostnames in list has already been added with the same port.', 'gridblstat'),
		'type' => 'error'
	);

	$messages['ls_conf_not_found']=array(
		'message' => __('Unable to locate lsf.licensescheduler in the given directory.', 'gridblstat'),
		'type' => 'error'
	);
}

function gridblstat_poller_bottom () {
	global $config;

	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q ' . $config['base_path'] . '/plugins/gridblstat/poller_gridblstat.php';

	$collectors = db_fetch_assoc("SELECT lsid FROM grid_blstat_collectors WHERE disabled=''");

	if (cacti_sizeof($collectors)) {
	foreach($collectors as $c) {
		exec_background($command_string, "$extra_args --lsid=" . $c['lsid']);
	}
	}
}

function gridblstat_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $user_menu;
	global $grid_main_screen, $grid_projectgroup_filter_levels;

	/* upgrade the system if required */
	gridblstat_check_upgrade();

	$menu2 = array ();
	foreach ($menu as $temp => $temp2 ) {
		$menu2[$temp] = $temp2;
		if ($temp == __('Data Collection')) {
			$menu2[__('Clusters', 'grid')]['plugins/gridblstat/blstat_collectors.php'] = __('License Schedulers', 'gridblstat');
			$menu2[$temp] = $temp2;
 		}
	}
	$menu = $menu2;

	if (!api_plugin_is_enabled('grid') && strpos(get_current_page(), 'gridblstat') !== false){
		$user_menu = gridblstat_grid_menu();
	}

	$db_realm = db_fetch_cell('SELECT id FROM plugin_realms WHERE file LIKE "%grid_clusterdb.php%"') + 100;
	$admin_realm = db_fetch_cell('SELECT id FROM plugin_realms WHERE file LIKE "%grid_manage_hosts.php%"') + 100;

	$user_auth_realm_filenames['blstat_collectors.php'] = $admin_realm;
	$user_auth_realm_filenames['grid_lsdashboard.php'] = $db_realm;
	$user_auth_realm_filenames['grid_blstat_image.php'] = $db_realm;

	$grid_main_screen['../gridblstat/grid_lsdashboard.php'] = __('License Scheduler Dashboard', 'gridblstat');

	if (!cacti_sizeof($grid_projectgroup_filter_levels)) {
		$grid_projectgroup_filter_levels = array(
			'1' => __('1 Level', 'gridblstat'),
			'2' => __('2 Levels', 'gridblstat')
		);
	}
}

function gridblstat_draw_navigation_text($nav) {
	return $nav;
}

function gridblstat_grid_tab_down($pages_grid_tab_down){
    $gridblstat_pages_grid_tab_down=array(
		'grid_lsdashboard.php' => ''
    );
    $pages_grid_tab_down += array("gridblstat" => $gridblstat_pages_grid_tab_down);
    return $pages_grid_tab_down;
}

function gridblstat_grid_menu($grid_menu = array()) {

	$gridblstat_menu = array(
		__('Dashboards') => array(
			'plugins/gridblstat/grid_lsdashboard.php'  => __('License Scheduler')
		)
	);

	if (!empty($grid_menu)) {
		$menu2 = array();
		foreach ($grid_menu as $gmkey => $gmval ) {
			$menu2[$gmkey] = $gmval;
			if ($gmkey == __('Dashboards', 'grid')) {
				foreach($gmval as $key => $menu) {
					$menu2[__('Dashboards')][$key] = $menu;
					if ($menu == __('Host', 'grid')) {
						$menu2[__('Dashboards')]['plugins/gridblstat/grid_lsdashboard.php'] = __('License Scheduler');
					}
				}
			}
		}
		return $menu2;
	}

	return $gridblstat_menu;
}

function gridblstat_grid_cluster_remove($cluster){
	if (isset($cluster) && isset($cluster['clusterid'])){
		db_execute_prepared('UPDATE grid_blstat_clusters SET clusterid=0 WHERE clusterid=?', array($cluster['clusterid']));
		db_execute_prepared('UPDATE grid_blstat_cluster_use SET clusterid=0 WHERE clusterid=?', array($cluster['clusterid']));
		db_execute_prepared('UPDATE grid_blstat_collector_clusters SET clusterid=0 WHERE clusterid=?', array($cluster['clusterid']));
		db_execute_prepared('UPDATE grid_blstat_users SET clusterid=0 WHERE clusterid=?', array($cluster['clusterid']));
	}

    return $cluster;
}