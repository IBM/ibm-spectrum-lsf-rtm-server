#!/usr/bin/php -q
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

error_reporting(0);
include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['library_path'] . '/rtm_functions.php');

/* let this script run with lot's of memory */
ini_set('memory_limit', '-1');

if (!isset($called_by_script_server)) {
	include_once($config['library_path'] . '/api_automation_tools.php');
	include_once($config['library_path'] . '/utility.php');
	include_once($config['library_path'] . '/api_data_source.php');
	include_once($config['library_path'] . '/api_graph.php');
	include_once($config['library_path'] . '/snmp.php');
	include_once($config['library_path'] . '/data_query.php');
	include_once($config['library_path'] . '/api_device.php');
	include_once($config['library_path'] . '/template.php');

	$php_bin    = read_config_option('path_php_binary');
	$path_web   = read_config_option('path_webroot');
	$path_disku = read_config_option('path_webroot') . '/plugins/disku';

	global $php_bin, $path_web, $path_disku, $graphs;

	array_shift($_SERVER['argv']);
	$parms = $_SERVER['argv'];

	$force     = false;
	$debug     = false;
	$templates = false;
	$reindex   = true;
	$graphs    = 0;

	foreach ($parms as $parameter){
		@list($arg, $val) = @explode('=', $parameter);
		switch ($arg) {
			case '-f':
			case '--force':
				$force = true;
				break;
			case '-d':
			case '--debug':
				$debug = true;
				break;
			case '--no-reindex':
				$reindex = false;
				break;
			case '--templates':
				$templates = true;
				break;
			case '--help':
			case '-h':
			case '-V':
			case '-v':
			case '--version':
				display_help();
				exit(0);
			default:
				break;
		}
	}

	if (detect_and_correct_running_processes(0, 'ADDDISKUHOST', 3600*6) || $force) {
		$hostid = db_fetch_cell("SELECT h.id
			FROM host AS h
			INNER JOIN host_template AS ht
			ON h.host_template_id=ht.id
			WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'");

		if (!empty($hostid)) {
			echo "NOTE: Updating Graphs for Disk Monitoring\n";
		} else {
			echo "NOTE: Adding Summary Device and Graphs for Disk Monitoring\n";
		}

		print call_user_func_array('disku_add_device', array($hostid, $force, $templates));

		disku_add_fs_device($force, $templates);

		remove_process_entry(0, 'ADDDISKUHOST');
	}
}

function disku_add_fs_device($force=false, $templates=false){
	global $php_bin, $path_web, $path_disku, $debug, $graphs, $reindex;

	// initialise default variables
	$queryid          = 'all';

	// host information for default device
	$id               = 0;
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='0d2005384e7a4fa6c63dd3d78d82abed'");
	$snmp_community   = '';
	$snmp_version     = 0;
	$snmp_username    = '';
	$snmp_password    = '';
	$snmp_port        = 161;
	$snmp_timeout     = 500;
	$avail_method     = '0';
	$ping_method      = 2;
	$ping_port        = 0;
	$ping_timeout     = 400;
	$ping_retries     = 1;
	$disabled         = '';
	$notes            = 'This device includes disk file system graphs';
	$auth_protocol    = '';
	$priv_passphrase  = '';
	$priv_protocol    = '';
	$snmp_context     = '';
	$max_oids         = 5;
	$monitor          = 'on';
	$lastupdate_time  = '';

	if(empty($host_template_id)){
		cacti_log('WARNING: the Disk Filesystem Host template is not found.');
		return;
	}
	list($micro,$seconds) = preg_split('/ /', microtime());
	$current_time = round($seconds + $micro); //getting the current time in unix timestamp

	if (!$force) {
		if (read_config_option('disku_device_add') == 'on'){
			//check whether user has enable this option
		} else {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
				cacti_log('DISKU INFO: Addition of Disk Filesystem Device is not enabled.');
			}

			return;
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			cacti_log('DISKU INFO: This is a force device/graph creation run.');
		}
	}

	$disku_pollers = db_fetch_assoc("SELECT id, hostname, cacti_host FROM disku_pollers;");
	foreach ($disku_pollers as $disku_poller){
		if($disku_poller['cacti_host'] != 0){
			/* check for host that has association with the cluster */
			$get_cacti_host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE id=?", array($disku_poller['cacti_host']));
			if (empty($get_cacti_host_id)){
				/* if old cacti host is deleted, set the cacti host for the particular cluster to zero */
				db_execute_prepared("UPDATE disku_pollers SET cacti_host=0 WHERE id=?", array($disku_poller['id']));
				$disku_poller['cacti_host'] = 0;
			}
		}
		if (empty($disku_poller['cacti_host'])) {
			$device_name = 'Disku_'. $disku_poller['hostname'];
			$host_id = api_device_save('0', $host_template_id, $device_name, $disku_poller['hostname'],
				$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
				$avail_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,$notes,
				$auth_protocol, $priv_passphrase, $priv_protocol, $snmp_context, $max_oids);

			if (!empty($host_id)) {
				if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
					cacti_log("DISKU INFO : Disk Filesystem Device $device_name has been added to Cacti.");
				}
				/* update the cacti host in grid cluster to reflect the new association */
				db_execute_prepared("UPDATE disku_pollers SET cacti_host=? WHERE id=?", array($host_id, $disku_poller['id']));

				push_out_host($host_id);

				if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
					cacti_log("DISKU INFO: Pushed out Disk Filesystem Device $device_name");
				}
			} else {
				if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
					echo "ERROR: Unable to add Disk Filesystem Device $device_name. Exiting...\n";
					cacti_log("DISKU FATAL: Unable to Disk Filesystem Device $device_name. Exiting...");
				}
				return;
			}
			if (!empty($host_id) || $templates) {
				adding_disku_graphs($host_id);
			}

			$snmp_queries= db_fetch_assoc_prepared("SELECT sq.id, sq.name, hsq.reindex_method
				FROM snmp_query AS sq
				INNER JOIN host_snmp_query AS hsq
				ON sq.id=hsq.snmp_query_id
				WHERE hsq.host_id=?
				ORDER BY sq.id", array($host_id));

			/* correct a bug in cacti that does not allow '0' as the reindex method */
			if (!empty($host_id) && cacti_sizeof($snmp_queries)) {
				foreach ($snmp_queries as $snmp_query) {
					db_execute_prepared("UPDATE host_snmp_query SET reindex_method=0
						WHERE host_id=? AND snmp_query_id=?", array($host_id, $snmp_query['id']));
					db_execute_prepared("DELETE FROM poller_reindex WHERE host_id=? AND data_query_id=?",  array($host_id, $snmp_query['id']));
				}
			}

			if ($reindex) {
				foreach ($snmp_queries as $snmp_query){
					echo trim(passthru("$php_bin -q $path_web/cli/poller_reindex_hosts.php -id=$host_id -qid=" . $snmp_query['id'] . ' -d'));
				}
			}

			foreach ($snmp_queries as $snmp_query){
				$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
					FROM snmp_query_graph
					WHERE snmp_query_id=?
					ORDER BY id", array($snmp_query['id']));

				$query_field_name = get_disku_query_field_name($host_id, $snmp_query['id']);

				echo "NOTE: The Query Field name is '$query_field_name' on device $device_name\n";

				if (cacti_sizeof($snmp_query_types)) {
					foreach ($snmp_query_types as $snmp_query_type) {
						echo "NOTE: Adding/Updating Graphs for " . $snmp_query_type['name'] . "\n";

						add_disku_data_query_graphs($host_id, $snmp_query_type['graph_template_id'], $snmp_query['id'], $snmp_query_type['id'], $query_field_name, $snmp_query_type['name']);

						echo "NOTE: Finished Adding/Updating Graphs for " . $snmp_query_type['name'] . "\n";
					}
				}
			}
		}//end if empty
	}

	if (!$force) {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			cacti_log('INFO: Updating Disk Filesystem Device time now');
		}
	}
}

function disku_add_device($host_id, $force=false, $templates=false){
	global $php_bin, $path_web, $path_disku, $debug, $graphs, $reindex;

	// initialise default variables
	$queryid          = 'all';

	// host information for default device
	$id               = 0;
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='8cb14a5b4c4623801ffbe011191ff9d8'");
	$snmp_community   = '';
	$snmp_version     = 0;
	$snmp_username    = '';
	$snmp_password    = '';
	$snmp_port        = 161;
	$snmp_timeout     = 500;
	$avail_method     = '0';
	$ping_method      = 2;
	$ping_port        = 0;
	$ping_timeout     = 400;
	$ping_retries     = 1;
	$disabled         = '';
	$notes            = 'This device includes all disk monitoring graphs';
	$auth_protocol    = '';
	$priv_passphrase  = '';
	$priv_protocol    = '';
	$snmp_context     = '';
	$max_oids         = 5;
	$monitor          = 'on';
	$lastupdate_time  = '';

	if(empty($host_template_id)){
		cacti_log('WARNING: the Disk Monitoring Host template is not found.');
		return;
	}
	list($micro,$seconds) = preg_split('/ /', microtime());
	$current_time = round($seconds + $micro); //getting the current time in unix timestamp

	if (!$force) {
		if (read_config_option('disku_device_add') == 'on'){
			//check whether user has enable this option
		} else {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
				cacti_log('DISKU INFO: Addition of Disk Monitoring Device is not enabled.');
			}

			return;
		}

		$lastupdate_time = read_config_option('disku_summary_device_update');

		if ($lastupdate_time == '' || $lastupdate_time == -1) {
			// No device has been added. This is the first time adding the device
			// add the Disk Monitoring Device the second time to make sure all hosts have been added correctly.
		}else if (86400-($current_time - $lastupdate_time) <= 30){ // check if more than 24 hours have passed with 30 seconds buffer
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('DISKU INFO: 24 hours have passed. Time to add/refresh the Disk Monitoring Devices Graphs');
			}
		} else { // less than 24 hours have passed, do not continue execution
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			 	echo "It is not time to update the Disk Monitoring Device, use --force to force update.\n";
				cacti_log('DISKU INFO: It is not time to update the Disk Monitoring Device.');
			}
			return;
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			cacti_log('DISKU INFO: This is a force device/graph creation run.');
		}
	}

	if (!empty($host_id)){
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			echo "NOTE: There is a an existing Disk Utilization Device. Proceeding to check for new graphs.\n";
			cacti_log('DISKU INFO: There is an existing Disk Utilization Device. Proceeding to check for new graphs');
		}
		$add_host = false;
	} else {
		$add_host = true;
	}

	if ($add_host) {
		$host_id = api_device_save('0', $host_template_id, 'Disk Monitoring', 'localhost',
			$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
			$avail_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,$notes,
			$auth_protocol, $priv_passphrase, $priv_protocol, $snmp_context, $max_oids);

		if (!empty($host_id)) {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
				cacti_log('DISKU INFO : Disk Monitoring Device has been added to Cacti.');
			}

			push_out_host($host_id);

			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
				cacti_log('DISKU INFO: Pushed out Disk Monitoring Device');
			}
		} else {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
				echo "ERROR: Unable to add Disk Monitoring Device. Exiting...\n";
				cacti_log('DISKU FATAL: Unable to Disk Monitoring Device. Exiting...');
			}
			return;
		}
	}

	if (!empty($host_id) || $templates) {
		adding_disku_graphs($host_id);
	}

	$snmp_queries= db_fetch_assoc_prepared("SELECT sq.id, sq.name, hsq.reindex_method
		FROM snmp_query AS sq
		INNER JOIN host_snmp_query AS hsq
		ON sq.id=hsq.snmp_query_id
		WHERE hsq.host_id=?
		ORDER BY sq.id", array($host_id));

	/* correct a bug in cacti that does not allow '0' as the reindex method */
	if (!empty($host_id) && cacti_sizeof($snmp_queries)) {
		foreach ($snmp_queries as $snmp_query) {
			db_execute_prepared("UPDATE host_snmp_query SET reindex_method=0
				WHERE host_id=? AND snmp_query_id=?", array($host_id, $snmp_query['id']));
			db_execute_prepared("DELETE FROM poller_reindex WHERE host_id=? AND data_query_id=?", array($host_id, $snmp_query['id']));
		}
	}

	if ($reindex) {
		foreach ($snmp_queries as $snmp_query){
			echo trim(passthru("$php_bin -q $path_web/cli/poller_reindex_hosts.php -id=$host_id -qid=" . $snmp_query['id'] . ' -d'));
		}
	}

	foreach ($snmp_queries as $snmp_query){
		if (!strstr($snmp_query['name'], 'Feature Summary Use')){
			$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
				FROM snmp_query_graph
				WHERE snmp_query_id=?
				ORDER BY id", array($snmp_query['id']));

			$query_field_name = get_disku_query_field_name($host_id, $snmp_query['id']);

			echo "NOTE: The Query Field name is '$query_field_name'\n";

			if (cacti_sizeof($snmp_query_types)) {
				foreach ($snmp_query_types as $snmp_query_type) {
					echo "NOTE: Adding/Updating Graphs for " . $snmp_query_type['name'] . "\n";

					add_disku_data_query_graphs($host_id, $snmp_query_type['graph_template_id'], $snmp_query['id'], $snmp_query_type['id'], $query_field_name, $snmp_query_type['name']);

					echo "NOTE: Finished Adding/Updating Graphs for " . $snmp_query_type['name'] . "\n";
				}
			}
		}
	}

	if (!$force) {
		if ($lastupdate_time == ''){
			$lastupdate_time = -1;
		} else {
			$lastupdate_time = $current_time;
		}
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			cacti_log('INFO: Updating Disk Monitoring Device time now');
		}
		db_execute("DELETE FROM settings WHERE name='disku_summary_device_update'");
		db_execute_prepared("INSERT INTO settings (name, value) VALUES ('disku_summary_device_update', ?)", array($lastupdate_time));
	}
}

function get_disku_query_field_name($host_id, $query_id) {
	return db_fetch_cell_prepared("SELECT sort_field FROM host_snmp_query WHERE host_id=? AND snmp_query_id=?", array($host_id, $query_id));
}

function add_disku_data_query_graphs($host_id, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $snmp_query_name='', $regmatch = '', $include = 'on'){
	global $php_bin, $path_web, $path_disku, $graphs, $debug;

	/* let's see what queries are defined for this host */
	$found = db_fetch_cell_prepared("SELECT COUNT(*)
			FROM host_snmp_query AS hsq
			INNER JOIN snmp_query_graph AS sqg
			ON hsq.snmp_query_id=sqg.snmp_query_id
			WHERE hsq.host_id=?
			AND hsq.snmp_query_id=?
			AND sqg.graph_template_id=?
			AND sqg.id=?",
			array($host_id, $snmp_query_id, $graph_template_id, $snmp_query_type_id));

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = array_rekey(db_fetch_assoc_prepared("SELECT field_value
			FROM host_snmp_cache
			WHERE host_id=?
			AND snmp_query_id=?
			AND field_name=?", array($host_id, $snmp_query_id, $snmp_field_name)), 'field_value', 'field_value');

		if (cacti_sizeof($items)) {
			foreach($items as $key => $item) {
				if ($regmatch == '') {
					/* add graph below */
				}elseif ((($include == 'on') && (preg_match('/'. $regmatch . '/', $item))) ||
					(($include != 'on') && (!preg_match('/'. $regmatch . '/', $item)))) {
					/* add graph below */
				} else {
					echo "NOTE: Bypassing item due to Regex rule: $item for Query Type ID: $snmp_query_type_id \n";
					continue;
				}

				/* see if graph exists */
				$exists = db_fetch_cell_prepared("SELECT count(*)
					FROM graph_local
					WHERE host_id=?
					AND graph_template_id=?
					AND snmp_query_id=?
					AND snmp_index=?",
					array($host_id, $graph_template_id, $snmp_query_id, $item));

				if(!$exists) {
					echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id\n";

					$command = "$php_bin -q $path_web/cli/add_graphs.php" .
						" --graph-template-id=$graph_template_id --graph-type=ds"     .
						" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $host_id .
						" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
						" --snmp-value=\"$item\"";

					echo trim(passthru($command)) . "\n";
				} else {
					echo "NOTE: Already Exists item: $item for Query Type ID: $snmp_query_type_id\n";
				}
			}
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug){
			cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated ", TRUE, 'DISKU');
		}
	}
}

function adding_disku_graphs($host_id) {
	global $php_bin, $path_web, $path_disku, $debug, $graphs;

	/* get cluster templates */
	$templates = db_fetch_assoc("SELECT ht.name AS host_template_name, gt.name AS graph_template_name,
		ht.id AS host_template_id, htg.graph_template_id AS host_template_graph_id
		FROM host_template_graph AS htg
		INNER JOIN host_template AS ht
		ON htg.host_template_id=ht.id
		INNER JOIN graph_templates AS gt
		ON htg.graph_template_id=gt.id
		WHERE ht.hash='8cb14a5b4c4623801ffbe011191ff9d8'
		ORDER BY ht.name");

	if (cacti_sizeof($templates)) {
		foreach($templates as $template) {
			/* workaround for a bug, see if graph is created already */
			$found = db_fetch_row_prepared("SELECT id
				FROM graph_local
				WHERE graph_template_id=?
				AND host_id=?
				LIMIT 1", array($template["host_template_graph_id"], $host_id));

			/* add the graph */
			if (!$found) {
				echo trim(shell_exec("$php_bin -q $path_web/cli/add_graphs.php --graph-type=cg --graph-template-id=" .$template['host_template_graph_id']. " --host-id=" . $host_id));
				echo "\n";
				$graphs++;
			}
		}
	} else {
		echo "NOTE: No Templates found for Disk Monitoring Device\n";

		return false;
	}

	return true;
}

function display_help () {
	global $config;

	print "Disk Monitoring Automation Script Version " . read_config_option("grid_version") . "\n";
	print html_entity_decode('&#169;', ENT_NOQUOTES, 'UTF-8') . " Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
	print "usage: disku_add_device.php [--no-reindex] [--templates] [--debug] [--force] [-h|--help|-v|-V|--version]\n\n";
	print "--no-reindex     - Quick option. Does not Reindex License Servers\n";
	print "--templates      - Rescan Templates for new Entries and Add Graphs\n";
	print "--force          - Force execution regardless of timing\n";
	print "--debug          - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - Display this help message\n\n";
}

?>
