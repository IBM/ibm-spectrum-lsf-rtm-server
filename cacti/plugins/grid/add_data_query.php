#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2023 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2023                                          |
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

$dir = dirname(__FILE__);
chdir($dir);

/* display NO errors */
error_reporting(0);

/* let this script run with lot's of memory */
ini_set('memory_limit', '-1');

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . '/../../include/cli_check.php');
	include_once($config['library_path'] . '/api_automation_tools.php');
	include_once($config['library_path'] . '/utility.php');
	include_once($config['library_path'] . '/api_data_source.php');
	include_once($config['library_path'] . '/api_graph.php');
	include_once($config['library_path'] . '/snmp.php');
	include_once($config['library_path'] . '/data_query.php');
	include_once($config['library_path'] . '/api_device.php');
	include_once($config['library_path'] . '/template.php');
	include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
	include_once($config['base_path'] . '/lib/rtm_functions.php');

	$php_bin   = read_config_option('path_php_binary');
	$path_web  = read_config_option('path_webroot');
	$path_grid = read_config_option('path_webroot') . '/plugins/grid';

	global $php_bin, $path_web, $path_grid, $graphs;

	array_shift($_SERVER['argv']);
	$parms = $_SERVER['argv'];

	$clusterid = 0;
	$force = false;
	$debug   = false;
	$reindex = true;
	$templates = false;
	$graphs  = 0;

	foreach ($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}
		switch ($arg) {
			case '--clusterid':
				$clusterid = trim($value);
				break;
			case '--force':
				$force = true;
				break;
			case '--templates':
				$templates = true;
				break;
			case '--debug':
				$debug = true;
				break;
			case '--no-reindex':
				$reindex = false;
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

	if (detect_and_correct_running_processes(0, 'ADDSUMMARYHOST', 3600*6)) {
		if($clusterid == 0) {
			$cids = db_fetch_assoc("SELECT clusterid FROM grid_clusters WHERE disabled !='on'");

			foreach ($cids as $cid) {
				echo "NOTE: Adding/Updating Summary Device for Cluster ID: " . $cid['clusterid'] . "\n";
				print call_user_func_array('add_data_query', array($cid['clusterid'], $force, $templates));
			}
		} else {
			echo 'NOTE: Adding/Updating Summary Device for Cluster ID: ' . $clusterid . "\n";
			print call_user_func_array('add_data_query', array($clusterid, $force, $templates));
		}

		remove_process_entry(0, 'ADDSUMMARYHOST');
	}
}

function add_data_query($clusterid, $force=false, $templates=false) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs, $reindex;

	/* initialize default variables */
	$queryid = 'all';
	$cluster_info = '';
	$host_value = 0;
	$host_id = 0;
	$id = 0;

	$host_template_id = db_fetch_cell("SELECT id
		FROM host_template
		WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");

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

	/* check that cluster id is defined */
	if ($clusterid == '') {
		echo "No cluster specified. please specify --clusterid=<clusterid>. Unable to continue.\n";
		return;
	}

	/* getting the current time in unix timestamp */
	$current_time = microtime(true);

	if (!$force) {
		if (read_config_option('add_summary_device') == 'on') {
			/* check whether user has enable this option */
		} else {
			/* add summary device option is not enabled */
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('INFO: Addition of Summary device is not enabled.');
			}
			return;
		}

		$lastupdate_time = read_config_option('summary_device_update_' . $clusterid); // check the last update time

		if ($lastupdate_time == -1) {
			/* add the summary device the second time to make sure all hosts have been added correctly */
		} else if (86400-($current_time - $lastupdate_time) <= 30) {
			/* check if more than 24 hours have passed with 30 seconds buffer */
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('INFO: 24 hours have passed. Time to add/refresh summary device');
			}
		} else {
			/* less than 24 hours have passed, do not continue execution */
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			 	echo "It is not time to update summary, use --force to force update.\n";
				cacti_log('INFO: It is not time to update the summary device.');
			}
			return;
		}
	} else {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO: This is a force run.');
		}
	}

	/* fetch information about the given cluster id */
	$cluster_information = db_fetch_row_prepared('SELECT *
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	if(empty($cluster_information)) {
		//need to check, fix the case when user deleting the cluster, a werid device of "_Summary' will be created.
		return;
	}

	/* check for host that has association with the cluster */
	$get_cacti_host_id = db_fetch_cell_prepared('SELECT id
		FROM host
		WHERE id = ?',
		array($cluster_information['cacti_host']));

	if (empty($get_cacti_host_id)) {
		/* if old cacti host is deleted, set the cacti host for the particular cluster to zero */
		db_execute_prepared('UPDATE grid_clusters SET cacti_host=0
			WHERE clusterid = ?',
			array($cluster_information['clusterid']));
	}

	/* get the new cacti host value */
	$cluster_information['cacti_host'] = db_fetch_cell_prepared('SELECT cacti_host
		FROM grid_clusters
		WHERE clusterid = ?',
		array($cluster_information['clusterid']));

	/* check for cluster with no cacti host, also add summary device if not cacti host association */
	if (empty($cluster_information['cacti_host'])) {
		/* add summary device for cluster */
		/* apply "grid summary" host template hash code to the SQL query as 'grid summary' could be renamed*/

		$host_template_id=db_fetch_cell("SELECT id
			FROM host_template
			WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");

		$host_value = api_device_save('0', $host_template_id,
			$cluster_information['clustername'].'_Summary', 'localhost',
			$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port,
			$snmp_timeout, $disabled, $availability_method, $ping_method, $ping_port,
			$ping_timeout, $ping_retries,$notes, $snmp_auth_protocol, $snmp_priv_passphrase,
			$snmp_priv_protocol, $snmp_context, $max_oids);

		/* device has been added successfully */
		if ($host_value != 0) {
			//Fix 233220, since cacti1.x, graphs created by api_device_save() already, adding_cluster_graphs() is not used.
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
				$local_data_id = db_fetch_cell_prepared("select id from data_local where data_template_id in
									(select id from data_template where hash=?)
									AND host_id=?", array($ch_data_template['data_template_hash'], $host_value));
				$data_template_data_id = db_fetch_cell_prepared("select id from data_template_data where local_data_id=?", array($local_data_id));
				$data_input_field_id = db_fetch_cell_prepared("select id from data_input_fields where hash=?", array($ch_data_template['data_input_field_hash']));
				db_execute_prepared("REPLACE INTO data_input_data
							(data_input_field_id, data_template_data_id, t_value, value)
							VALUES
							(?, ?, '', ?)",
							array($data_input_field_id, $data_template_data_id, 'yes')
						);
				update_poller_cache($local_data_id, true);
			}
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('INFO : ' . $cluster_information['clustername'] . '_Summary has been added to the device list');
			}

			/* update the newly created host to asscociate with the cluster */
			db_execute_prepared('UPDATE host
				SET clusterid = ?
				WHERE id = ?',
				array($cluster_information['clusterid'], $host_value));

			push_out_host($host_value);

			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
				cacti_log('INFO: Pushing out host ' . $host_value . ' : ' . $cluster_information['clustername'] . '_Summary');
			}
		}
	}

	$host_id = db_fetch_cell_prepared('SELECT id
		FROM host
		WHERE id = ?',
		array($cluster_information['cacti_host']));

	if (empty($host_id)) {
		/* update the cacti host in grid cluster to reflect the new association */
		db_execute_prepared('UPDATE grid_clusters
			SET cacti_host = ?
			WHERE clusterid = ?',
			array($host_value, $cluster_information['clusterid']));

		$host_id = $host_value;
	}

	if (!empty($host_value) || $templates) {
		if (empty($host_value)) {
			$host_value = $host_id;
		}

		//if (adding_cluster_graphs($host_value, $clusterid) == false) {
		//	return;
		//}
	}

	$snmp_queries_id = db_fetch_assoc_prepared('SELECT snmp_query.id,
		snmp_query.name, host_snmp_query.reindex_method
		FROM (snmp_query, host_snmp_query)
		WHERE snmp_query.id = host_snmp_query.snmp_query_id
		AND host_snmp_query.host_id = ?
		ORDER BY snmp_query.id',
		array($host_value));

	/* correct a bug in cacti that does not allow '0' as the reindex method */
	if (cacti_sizeof($snmp_queries_id)) {
		foreach ($snmp_queries_id as $snmp_query) {
			db_execute_prepared('REPLACE INTO host_snmp_query
				(host_id, snmp_query_id, reindex_method)
				VALUES (?, ?, ?)',
				array($host_id, $snmp_query['id'], 0));

			db_execute_prepared('DELETE
				FROM poller_reindex
				WHERE host_id = ?
				AND data_query_id = ?',
				array($host_id, $snmp_query['id']));
		}
	}

	$cluster_info = db_fetch_row_prepared('SELECT *
		FROM grid_clusters
		WHERE clusterid = ?',
		array($clusterid));

	if ($reindex) {
		passthru($php_bin . ' -q ' . $path_web . '/cli/poller_reindex_hosts.php -id=' . cacti_escapeshellarg($cluster_info['cacti_host']) . ' -qid=' . cacti_escapeshellarg($queryid) . ' -d');
	}

	$snmp_queries_id = db_fetch_assoc_prepared('SELECT snmp_query.id,
		snmp_query.name, host_snmp_query.reindex_method
		FROM (snmp_query, host_snmp_query)
		WHERE snmp_query.id = host_snmp_query.snmp_query_id
		AND host_snmp_query.host_id = ?
		ORDER BY snmp_query.id',
		array($cluster_info['cacti_host']));

	foreach ($snmp_queries_id as $snmp_query_id) {
		$snmp_query_types = db_fetch_assoc_prepared('SELECT id, name, graph_template_id
			FROM snmp_query_graph
			WHERE snmp_query_id = ?
			ORDER BY id',
			array($snmp_query_id['id']));

		if (cacti_sizeof($snmp_query_types)) {
			foreach ($snmp_query_types as $snmp_query_type) {
				$query_field_name = get_query_field_name($cluster_info['cacti_host'], $snmp_query_id['id']);

				add_data_query_graphs($cluster_info, $snmp_query_type['graph_template_id'], $snmp_query_id['id'], $snmp_query_type['id'], $query_field_name);
			}
		}
	}

	if ($graphs > 0) {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO: Creating/refreshing tree node now.');
		}

		passthru($php_bin . ' -q ' . $path_grid . '/grid_add_trees.php --clusterid=' . cacti_escapeshellarg($clusterid));
	} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log('INFO: Not Creating/refreshing tree node now as NO Graph were Added!');
	}
	$graph = 0;


	if (!$force) {
		if ($lastupdate_time == '') {
			$lastupdate_time = -1;
		} else {
			$lastupdate_time         = $current_time;
		}
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log('INFO: Updating summary device time now');
		}

		db_execute_prepared("DELETE FROM settings
			WHERE name=?", array("summary_device_update_".$clusterid));

		db_execute_prepared("INSERT INTO settings (name, value) values (?, ?)", array("summary_device_update_".$clusterid, $lastupdate_time));
	}
}

function get_query_field_name($host_id, $query_id) {
	return db_fetch_cell_prepared('SELECT sort_field
		FROM host_snmp_query
		WHERE host_id = ?
		AND snmp_query_id= ?',
		array($host_id, $query_id));
}

function add_data_query_graphs(&$cluster, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = '', $include = 'on') {
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
		array($cluster["cacti_host"], $snmp_query_id, $graph_template_id, $snmp_query_type_id));

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = array_rekey(
			db_fetch_assoc_prepared("SELECT field_value
				FROM host_snmp_cache
				WHERE host_id=?
				AND snmp_query_id=?
				AND field_name=?", array($cluster["cacti_host"], $snmp_query_id, $snmp_field_name)),
			'field_value', 'field_value'
		);

		if (cacti_sizeof($items)) {
			foreach($items as $key => $item) {
				if ($regmatch == '') {
					/* add graph below */
				} elseif ((($include == 'on') && (preg_match("/$regmatch/", $item))) ||
					(($include != 'on') && (!preg_match("/$regmatch/", $item)))) {
					/* add graph below */
				} else {
					echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster['clustername'] . "\n";
					continue;
				}

				/* see if graph exists */
				$exists = db_fetch_cell_prepared("SELECT count(*)
					FROM graph_local
					WHERE host_id=?
					AND snmp_query_id=?
					AND graph_template_id=?
					AND snmp_index=?",
					array($cluster["cacti_host"], $snmp_query_id, $graph_template_id, $item));

				if (!$exists) {
					echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"] . "\n";

					$command = "$php_bin -q $path_grid/grid_add_graphs.php" .
						" --graph-template-id=$graph_template_id --graph-type=ds"     .
						" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $cluster["cacti_host"] .
						" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
						" --snmp-value='" . $item . "'";

					passthru($command);

					$graphs++;
				}
			}
		}
	} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Cluster: " . $cluster['clustername'], true, 'GRID');
	}
}

function adding_cluster_graphs($host_id, $clusterid) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs;

	$host_template_hash = 'd8ff1374e732012338d9cd47b9da18d4';

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
		WHERE host_template.hash = ?
		ORDER BY host_template.name",
		array($host_template_hash));

	if (cacti_sizeof($templates)) {
		/* set_grid_summary($host_id, 'yes'); */
		foreach($templates as $template) {
			if (substr_count($template['graph_template_name'], 'Cluster/Host')) {
				$summary = " --input-fields='summary=yes'";
			} else if (substr_count($template['graph_template_name'], 'Cluster/Overall')) {
				$summary = " --input-fields='summary=no'";
			} else {
				$summary = '';
			}

			/* workaround for a bug, see if graph is created already */
			$found = db_fetch_row_prepared("SELECT id
				FROM graph_local
				WHERE graph_template_id=?
				AND host_id=?
				LIMIT 1", array($template['host_template_graph_id'], $host_id));


			/* add the graph */
			if (!$found) {
				echo trim(shell_exec("$php_bin -q $path_web/cli/add_graphs.php --graph-type=cg --graph-template-id=" .$template['host_template_graph_id']. " --host-id=" . $host_id . $summary));
				echo "\n";
				$graphs++;
			}
		}
	} else {
		echo "ERROR: No Templates found for Cluster Device Type\n";
		return false;
	}

	return true;
}


function display_help () {
	global $config;

	print 'RTM Cluster Host Automation Script Version ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "usage: add_data_query.php [--clusterid=id] [--no-reindex] [--templates] [--debug] [--force] [-h|--help|-v|-V|--version]\n\n";
	print "--clusterid      - Optional Cluster ID.  The default is All Clusters\n";
	print "--no-reindex     - Quick option. Does not Reindex Clusters\n";
	print "--templates      - Rescan Templates for new Entries and Add Graphs\n";
	print "--force          - Force execution regardless of timing\n";
	print "--debug          - Display verbose output during execution\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - Display this help message\n\n";
}

