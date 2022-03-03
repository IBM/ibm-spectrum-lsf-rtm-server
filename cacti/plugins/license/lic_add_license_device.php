#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2021 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2021                                          |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 |  Cacti - http://www.cacti.net/                                          |
 +-------------------------------------------------------------------------+
 |  IBM Corporation - http://www.ibm.com/                                  |
 +-------------------------------------------------------------------------+
*/

include(dirname(__FILE__) . "/../../include/cli_check.php");
include_once($config["library_path"] . '/rtm_functions.php');

/* display NO errors */
error_reporting(0);

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

	$php_bin   = read_config_option('path_php_binary');
	$path_web  = read_config_option('path_webroot');
	$path_grid = read_config_option('path_webroot') . '/plugins/grid';

	global $php_bin, $path_web, $path_grid, $graphs;

	array_shift($_SERVER['argv']);
	$parms = $_SERVER['argv'];

	$licid     = 0;
	$force     = false;
	$force     = false;
	$debug     = false;
	$templates = false;
	$reindex   = true;
	$graphs    = 0;

	foreach ($parms as $parameter) {
		@list($arg, $val) = @explode('=', $parameter);
		switch ($arg) {
			case '--licid':
				$licid = trim($val);
				break;
			case '--force':
				$force = true;
				break;
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

	if (detect_and_correct_running_processes(0, 'ADDLICHOST', 3600*6)) {

		if($licid == 0) {
			$license_ids = db_fetch_assoc('SELECT service_id AS id FROM lic_services');
			foreach($license_ids as $license_id) {
				echo "NOTE: Adding/Updating Summary Device for License Server ID: " . $license_id['id'] . "\n";
				print call_user_func_array('lic_add_host', array($license_id['id'], $force, $templates));
			}
		}
		else {
			echo 'NOTE: Adding/Updating Summary Device for License Server ID: ' . $licid . "\n";
			print call_user_func_array('lic_add_host', array($licid, $force, $templates));
		}
		remove_process_entry(0, 'ADDLICHOST');
	}
}

function lic_add_host($licid, $force=false, $templates=false) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs, $reindex;

	// initialise default variables
	$license_information = '';
	$queryid = 'all';
	$host_id = 0;
	$host_value = 0;
	$id = 0;
	$host_template_id = 14;
	$snmp_community = '';
	$snmp_version = 0;
	$snmp_username = '';
	$snmp_password = '';
	$snmp_port = 161;
	$snmp_timeout = 500;
	$availability_method = '0' ;
	$ping_method = 2;
	$ping_port = 0;
	$ping_timeout = 400;
	$ping_retries = 1;
	$disabled = '';
	$notes = '';
	$snmp_auth_protocol = '';
	$snmp_priv_passphrase = '';
	$snmp_priv_protocol = '';
	$snmp_context = '';
	$max_oids = 5;
	$monitor = 'on';
	$lastupdate_time = '';

	if ($licid == '') { // check that cluster id is defined
		echo "No license server specified. please specify --licid=<licid>. Unable to continue.\n";
		return;
	}

	list($micro,$seconds) = preg_split('/ /', microtime());
	$current_time	 = round($seconds + $micro); //getting the current time in unix timestamp
	if (!$force) {
		if (read_config_option('lic_add_device') == 'on') {
			//check whether user has enable this option
		} else { 	//add license device option is not enabled
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('LIC INFO: Addition of License device is not enabled.');
			}
			return;
		}

		$lastupdate_time = read_config_option('lic_summary_device_update_'.$licid); // check the last update time

		if ($lastupdate_time == '' || $lastupdate_time == -1) {
			// No device has been added. This is the first time adding the device
			// add the license device the second time to make sure all hosts have been added correctly.
		}else if (86400-($current_time - $lastupdate_time) <= 30) { // check if more than 24 hours have passed with 30 seconds buffer
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('LIC INFO: 24 hours have passed. Time to add/refresh license device');
			}
		} else { // less than 24 hours have passed, do not continue execution
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			 	echo "It is not time to update license device, use --force to force update.\n";
				cacti_log('LIC INFO: It is not time to update the license device.');
			}
			return;
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('LIC INFO: This is a force run.');
		}
	}

	/* fetch information about the given license server id */
	$license_information = db_fetch_row_prepared('SELECT *
		FROM lic_services
		WHERE service_id=?', array($licid));

	$host_template_id = db_fetch_cell("SELECT id
		FROM host_template
		WHERE hash='305007178521f03fde7e0a66c37784c8'");

	$get_cacti_host_id = db_fetch_cell_prepared("SELECT lic_server_id
		FROM host
		WHERE lic_server_id = ?", array($licid)); // check for host that has association with the license server

	if (!empty($get_cacti_host_id)) {
		// there is already a cacti host that is mapped to the particular license server, so we will just proceed to add the graphs
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			echo "There is a an existing device for this license server. Proceed to check for new graphs.\n";
			cacti_log('LIC INFO: There is an existing device for this license server. Proceed to check for new graphs');
		}
		$add_lic_host = false;
	} else {
		// continue with operation
		$add_lic_host = true;
	}

	$lic_services = db_fetch_row_prepared('SELECT *
		FROM lic_services
		WHERE service_id=?', array($licid));

	// add license device for license server
	if ($add_lic_host) {
		// No host with the specific licid is present, so we continue to add the new device here
		$host_value = api_device_save('0', $host_template_id, $lic_services['server_name'], 'localhost',
			$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
			$availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,$notes,
			$snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids);
	} else {
		// Existing host with specific licid is present, so we do not add new device here
		$host_value = db_fetch_cell_prepared('SELECT id FROM host WHERE lic_server_id=?', array($licid));
	}

	if ($host_value != 0) { // device has been added successfully
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO : '.$lic_services['server_name'].' has been added to the device list');
		}

		// Update the newly created host to asscociate with the cluster
		db_execute_prepared("UPDATE host
			SET lic_server_id=?
			WHERE id=?", array($licid, $host_value));

		push_out_host($host_value);

		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO: Pushing out host '. $host_value.' : ' .$lic_services['server_name']);
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			echo "Unable to add license device. Exiting...\n";
			cacti_log('LIC FATAL: Unable to add license device. Exiting...');
		}

		return;
	}

	if (!empty($host_value) || $templates) {
		if (empty($host_value)) $host_value = $get_cacti_host_id;

		if(adding_license_graphs($host_value, $licid) == false) {
			return;
		}
	}

	/* This query will return the following data query
		- LM - License Server Information
		- LM License - Host Feature Usage
		- LM License - User Feature Usage
		- LM License - Vendor Feature Usage
		- LM License - License Server Usage
	*/
	$snmp_queries_id = db_fetch_assoc_prepared("SELECT snmp_query.id, snmp_query.name,
		host_snmp_query.reindex_method
		FROM (snmp_query, host_snmp_query)
		WHERE snmp_query.id = host_snmp_query.snmp_query_id
		AND host_snmp_query.host_id = ?
		ORDER BY snmp_query.id", array($host_value));

	/* correct a bug in cacti that does not allow '0' as the reindex method */
	if (!empty($host_value) && cacti_sizeof($snmp_queries_id)) {
		foreach ($snmp_queries_id as $snmp_query) {
			db_execute_prepared("REPLACE INTO host_snmp_query
				(host_id, snmp_query_id, reindex_method)
				VALUES (?, ?, 0)",
				array($host_value, $snmp_query['id']));

			db_execute_prepared("DELETE FROM poller_reindex
				WHERE host_id=?
				AND data_query_id=?",
				array($host_value, $snmp_query['id']));
		}
	}

	// In original design, "GRID - Licenses" and "GRID - Licenses Feature Use" are two DQ relationship record
    // with one graph template "GRID - License Feature Use" with hash "620954e227a1972dd9de72b7b9edddd2""
    // "GRID - Licenses" have two task_item only. It's DQ->graph hash is a453019bbc965767416c5584a864e02f.
    // And it was obsoleted and removed out-of-box since v2.1.
    // But keep in customer env to keep plotting
    // So "GRID - Licenses" would be always ignored in v2.1 routine process.
    // From v2.1 to v9.1, "GRID - License Feature Use" is also rename to "LM - Feature Use"
    // Since RTC#167796, lic_services_feature_use will include limited feature record with inUse > 0 only.
	$ignored_queries = array();
	if (strtolower(read_config_option('lic_add_features_graph')) != 'on') {
		// a453019bbc965767416c5584a864e02f
		//LM License - Feature Use: 620954e227a1972dd9de72b7b9edddd2
		$ignored_queries = array(
			'LM License - Host Feature Usage',
			'LM License - User Feature Usage',
			'LM License - Vendor Feature Usage'
		);
	}

	if ($reindex) {
		foreach ($snmp_queries_id as $snmp_query_id) {
			if (strstr($snmp_query_id['name'], 'Feature Summary Use') || in_array($snmp_query_id['name'], $ignored_queries)) {
				// do nothing
			} else {
				echo trim(passthru($php_bin.' -q ' . $path_web . '/cli/poller_reindex_hosts.php -id=' . $host_value . ' -qid=' . $snmp_query_id['id'] . ' -d'));
			}
		}
	}

	foreach ($snmp_queries_id as $snmp_query_id) {
		//if (strstr($snmp_query_id['name'], 'Feature Usage') || strstr($snmp_query_id['name'], 'Feature Summary Use')) {
		if (strstr($snmp_query_id['name'], 'Feature Summary Use')) {
			// do nothing here
		} else {
			$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
				FROM snmp_query_graph
				WHERE snmp_query_id = ?
				ORDER BY id", array($snmp_query_id['id']));

			if (cacti_sizeof($snmp_query_types)) {
				foreach ($snmp_query_types as $snmp_query_type) {
					if (in_array($snmp_query_type['name'], $ignored_queries)) {
						// do nothing here
						// seems like a duplicate graph
					} else {
						// do nothing here
						$query_field_name = get_lic_query_field_name($host_value, $snmp_query_id['id']);

						add_lic_data_query_graphs($host_value, $snmp_query_type['graph_template_id'],
							$snmp_query_id['id'], $snmp_query_type['id'],
							$query_field_name, $snmp_query_type['name']
						);
					}
				}
			}
		}
	}

	if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log('INFO: Creating/refreshing tree node now.');
	}

	if (!$force) {
		// We do not need double polling as of now
		if ($lastupdate_time == '') {
			$lastupdate_time = -1;
		} else {
			$lastupdate_time	 = $current_time;
		}

		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO: Updating license device time now');
		}

		db_execute_prepared("DELETE FROM settings
			WHERE name=?", array("lic_summary_device_update_" . $licid));

		db_execute_prepared("INSERT INTO settings
			(name, value) VALUES
			(?, ?)",
			array("lic_summary_device_update_" . $licid, $lastupdate_time));
	}
}

function get_lic_query_field_name($host_id, $query_id) {
	return db_fetch_cell_prepared("SELECT sort_field
		FROM host_snmp_query
		WHERE host_id=? AND snmp_query_id=?",
		array($host_id, $query_id));
}

function add_lic_data_query_graphs($host_id, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $snmp_query_name='', $regmatch = '', $include = 'on') {
	global $php_bin, $path_web, $path_grid, $graphs, $debug;

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
				}elseif ((($include == 'on') && (preg_match($regmatch, $item))) ||
					(($include != 'on') && (!preg_match($regmatch, $item)))) {
					/* add graph below */
				} else {
						echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id \n";
						continue;
					continue;
				}

				if ($snmp_query_name == 'GRID - License Feature Use') {
					$lic_server_id = db_fetch_cell_prepared('SELECT lic_server_id
						FROM host
						WHERE id=?', array($host_id));

					$lic_server_name = db_fetch_cell_prepared('SELECT server_name
						FROM lic_services
						WHERE service_id=?', array($lic_server_id));

					if ($item == $lic_server_name) {
						// Found the correct lic_server_name
					} else {
						continue;
					}
				}

				/* see if graph exists */
				$exists = db_fetch_cell_prepared("SELECT count(*)
					FROM graph_local
					WHERE host_id=?
					AND snmp_query_id=?
					AND graph_template_id=?
					AND snmp_index=?",
					array($host_id, $snmp_query_id, $graph_template_id, $item));

				if(!$exists) {
					echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id\n";


					$command = "$php_bin -q $path_web/cli/add_graphs.php" .
						" --graph-template-id=$graph_template_id --graph-type=ds"     .
						" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $host_id .
						" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
						" --snmp-value=\"$item\"";

					echo trim(passthru($command)) . "\n";
				}
				else {
					echo "NOTE: Already Exists item: $item for Query Type ID: $snmp_query_type_id\n";
				}
			}
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated ", true, 'GRID');
		}
	}
}

function adding_license_graphs($host_id, $licid) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs;

	$host_template_name = 'LM Server';
	/* apply 'LM Server' host template hash code to the SQL query as 'LM Server' could be renamed */
	$host_template_hash = '305007178521f03fde7e0a66c37784c8';

	/* get cluster templates */
	$templates = db_fetch_assoc_prepared("SELECT host_template.name AS host_template_name,
		graph_templates.name AS graph_template_name,
		host_template.id AS host_template_id,
		host_template_graph.graph_template_id AS host_template_graph_id
		FROM host_template_graph
		INNER JOIN host_template
		ON host_template_graph.host_template_id=host_template.id
		INNER JOIN graph_templates
		ON host_template_graph.graph_template_id=graph_templates.id
		WHERE host_template.hash=?
		ORDER BY host_template.name", array($host_template_hash));

	if (cacti_sizeof($templates)) {
		//set_grid_summary($host_id, 'yes');
		foreach($templates as $template) {
			/* workaround for a bug, see if graph is created already */
			$found = db_fetch_row_prepared("SELECT id
				FROM graph_local
				WHERE graph_template_id=?
				AND host_id=?
				LIMIT 1",
				array($template['host_template_graph_id'], $host_id));

			/* add the graph */
			if (!$found) {
				echo trim(shell_exec("$php_bin -q $path_web/cli/add_graphs.php --graph-type=cg --graph-template-id=" .$template['host_template_graph_id']. ' --host-id=' . $host_id));
				echo "\n";
				$graphs++;
			}
		}
	} else {
		echo "ERROR: No Templates found for License Device Type\n";
		return false;
	}

	return true;
}

function display_help () {
	global $config;

	print 'RTM License Server Host Automation Script Version ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8').' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "usage: lic_add_license_device.php [--licid=id] [--no-reindex] [--templates] [--debug] [--force] [-h|--help|-v|-V|--version]\n\n";
	print "--licid          - Optional License Service ID.  The default is All License Services\n";
	print "--no-reindex     - Quick option. Does not Reindex License Servers\n";
	print "--templates      - Rescan Templates for new Entries and Add Graphs\n";
	print "--force          - Force execution regardless of timing\n";
	print "--debug          - Display verbose output during execution\n";
}

