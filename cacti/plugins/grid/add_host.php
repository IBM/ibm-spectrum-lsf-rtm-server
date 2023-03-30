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

include_once(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['library_path'] . '/api_automation_tools.php');
include_once($config['library_path'] . '/utility.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/snmp.php');
include_once($config['library_path'] . '/data_query.php');
include_once($config['library_path'] . '/api_device.php');
include_once($config['library_path'] . '/template.php');
include_once($config['library_path'] . '/rtm_functions.php');

global $addhost_exit_flag;
$addhost_exit_flag = 0;

function sig_handler($signo) {
	global $addhost_exit_flag;
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
			$addhost_exit_flag = 1;
			cacti_log("WARNING: ADDHOST Poller terminated by signal $signo", false, 'grid');
			break;
		default:
			/* ignore all other signals */
	}
}

$id             = '0'; //change 0 to '0' for new form_input_validate in cacti1.2
$snmp_community = '';
$snmp_version   = 0;
$snmp_username  = '';
$snmp_password  = '';
$snmp_port      = 161;
$snmp_timeout   = 500;
$availability_method = '0' ;
$ping_method    = 2;
$ping_port      = 0;
$ping_timeout   = 400;
$ping_retries   = 1;
$disabled       = '';
$notes          = '';
$snmp_auth_protocol   = '';
$snmp_priv_passphrase = '';
$snmp_priv_protocol   = '';
$snmp_context         = '';
$max_oids  = 5;
$monitor   = 'on';
$php_bin   = read_config_option('path_php_binary');
$path_web  = read_config_option('path_webroot');
$path_grid = read_config_option('path_webroot') . '/plugins/grid';

$poller_start         = microtime(true);
$poller_start         = round($poller_start);

/* input parameters */
$force     = false;
$debug     = false;
$clusterid = 0;
$templates = false;
$remove_proc = false;

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	foreach($parms as $p) {
		if (strpos($p, '=')) {
			list($arg, $value) = explode('=', $p);
		} else {
			$arg = $p;
			$value = '';
		};

		switch ($arg) {
		case '--debug':
			$debug = true;
			break;
		case '--force':
			$force = true;
			break;
		case '--clusterid':
			$clusterid = $value;
			break;
		case '--templates':
			$templates = true;
			break;
		case '-v':
		case '--version':
		case '-h':
		case '--help':
			display_help();
			exit(0);
		default:
			print "FATAL: Unknown parameter '$p'\n\n";
			display_help();
			exit(1);
		}
	}
}

/* install signal handlers for UNIX only */
if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
}

if (detect_and_correct_running_processes(0, 'ADDHOST', 3600)) {
	/* continue */
	grid_debug('Lock for host process in place');

	$remove_proc = true;
} elseif ($force) {
	/* continue */
	grid_debug('Host automation is being forced');

	$remove_proc = false;
} else {
	print "NOTE: Add Host is Already Running.  Use option '--force' to override.  Exiting!\n";
	exit;
}

$check_add_host_frequency = db_fetch_assoc("SELECT add_frequency, add_graph_frequency,
	clusterid, host_template_id
	FROM grid_clusters
	WHERE disabled != 'on'");

foreach ($check_add_host_frequency as $checkfrequency) {
	if ($checkfrequency['add_frequency'] == 0) {
		//if value is 0, will not add host.
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log('INFO : Cluster ' . $checkfrequency['clusterid']. ' will not be updated.');
		}
	} else {
		grid_debug('Inspecting Cluster ' . $checkfrequency['clusterid'] . ' via option');
		$addhost_lastrun = read_config_option('addhost_lastrun_'.$checkfrequency['clusterid']);
		$addgraph_lastrun = read_config_option('addgraph_lastrun_'.$checkfrequency['clusterid']);
		$difference = $poller_start - $addhost_lastrun;

		if ($poller_start - $addhost_lastrun >= $checkfrequency['add_frequency'] || $force) {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
				cacti_log('INFO : It is time to refresh Cluster ' . $checkfrequency['clusterid']);
			}

			/* if we are forcing a run and this is not the clusterid, skip */
			if ($clusterid > 0 && $checkfrequency['clusterid'] != $clusterid) {
				grid_debug('Skipping Cluster ' . $checkfrequency['clusterid'] . ' via option');
				continue;
			}

			$graph_templates = array_rekey(
				db_fetch_assoc_prepared('SELECT graph_template_id
					FROM host_template_graph AS htg
					WHERE host_template_id = ?
					UNION
					SELECT graph_template_id
					FROM host_template_snmp_query AS htsq
					INNER JOIN snmp_query_graph AS sqg
					ON htsq.snmp_query_id=sqg.snmp_query_id
					WHERE host_template_id = ?',
					array($checkfrequency['host_template_id'], $checkfrequency['host_template_id'])),
				'graph_template_id', 'graph_template_id'
			);

			$snmp_host_template = db_fetch_assoc('SELECT DISTINCT gti.graph_template_id
				FROM graph_templates_item AS gti
				INNER JOIN data_template_rrd AS dtr
				ON gti.task_item_id = dtr.id
				INNER JOIN data_template AS dt
				ON dtr.data_template_id = dt.id
				INNER JOIN data_template_data AS dtd
				ON dt.id = dtd.data_template_id
				WHERE dtr.local_data_id = 0
				AND gti.graph_template_id IN (' . implode(',', $graph_templates) . ')
				AND dtd.data_input_id IN (1,2)');

			/* check for an snmp host */
			if (cacti_sizeof($snmp_host_template)) {
				grid_debug('SNMP Based Host Template Found');
				$snmp_community = read_config_option('snmp_community');
				$snmp_version   = read_config_option('snmp_ver');
				$snmp_username  = read_config_option('snmp_username');
				$snmp_password  = read_config_option('snmp_password');
				$snmp_port      = read_config_option('snmp_port');
				$snmp_timeout   = read_config_option('snmp_timeout');
				$availability_method = read_config_option('availability_method');
				$ping_method    = read_config_option('ping_method');
				$ping_port      = read_config_option('ping_port');
				$ping_timeout   = read_config_option('ping_timeout');
				$ping_retries   = read_config_option('ping_retries');
				$disabled       = '';
				$notes          = '';
				$snmp_auth_protocol   = read_config_option('snmp_auth_protocol');
				$snmp_priv_passphrase = read_config_option('snmp_priv_passphrase');
				$snmp_priv_protocol   = read_config_option('snmp_priv_protocol');
				$snmp_context         = '';
				$max_oids  = 5;
			} else {
				grid_debug('Non SNMP Based Host Template Found');
				$snmp_community = '';
				$snmp_version   = 0;
				$snmp_username  = '';
				$snmp_password  = '';
				$snmp_port      = 161;
				$snmp_timeout   = 500;
				$availability_method = '0' ;
				$ping_method    = 2;
				$ping_port      = 0;
				$ping_timeout   = 400;
				$ping_retries   = 1;
				$disabled       = '';
				$notes          = '';
				$snmp_auth_protocol   = '';
				$snmp_priv_passphrase = '';
				$snmp_priv_protocol   = '';
				$snmp_context         = '';
				$max_oids  = 5;
			}

			set_config_option('addhost_lastrun_' . $checkfrequency['clusterid'], $poller_start);

			$grid_host = db_fetch_assoc_prepared('SELECT gh.host, gh.clusterid
					FROM grid_hosts AS gh
					INNER JOIN grid_clusters AS gc
					ON gh.clusterid=gc.clusterid
					WHERE disabled = ""
					AND gh.status <> "Unavail"
					AND gc.clusterid = ?',
					array($checkfrequency['clusterid']));

			foreach ($grid_host as $gridhost) {
				pcntl_signal_dispatch();
				if($addhost_exit_flag) {
					 break; //killed because run time > 3600 seconds for huge cluster.
				}
				grid_debug("Checking host '" . $gridhost['host'] . "' For Graphs");

				$adding_host = db_fetch_row_prepared('SELECT *
					FROM host
					WHERE hostname = ?
					AND clusterid = ?',
					array($gridhost['host'], $gridhost['clusterid']));

				if (empty($adding_host)) {
					grid_debug("Host '" . $gridhost['host'] . "' Not in Cacti, adding!");

					$description = $gridhost['host'];
					$hostname    = $gridhost['host'];

					$host_id = api_device_save($id, $checkfrequency['host_template_id'],
						$description, $hostname, $snmp_community, $snmp_version, $snmp_username,
						$snmp_password, $snmp_port, $snmp_timeout, $disabled, $availability_method,
						$ping_method, $ping_port, $ping_timeout, $ping_retries, $notes, $snmp_auth_protocol,
						$snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids);

					if ($host_id > 0) {
						if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
							cacti_log('INFO : ' . $gridhost['host'] . ' has been added to the device list');
						}

						db_execute_prepared('UPDATE host
							SET clusterid = ?
							WHERE description = ?
							AND id = ?',
							array($gridhost['clusterid'], $gridhost['host'], $host_id));

						set_config_option('addgraph_lastrun_' . $checkfrequency['clusterid'], $poller_start);

						grid_debug("Checking host '" . $gridhost['host'] . "' For Graphs!");

						check_add_graph($host_id, $checkfrequency['host_template_id'], $gridhost['host']);

						push_out_host($host_id);
						if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
							cacti_log('INFO: Pushing out host ' . $host_id . ' : ' . $gridhost['host']);
						}

						grid_debug("Checking host '" . $gridhost['host'] . "' For Data Query Graphs!");

						check_add_snmp($host_id, $checkfrequency['host_template_id']);
					} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
						cacti_log('INFO : Unable to add ' . $gridhost['host'] . ' to the device list. Check if device has already been added.');
					}
				} else {
					/* not new host, we will just update the graphs */
					$host_template_id = db_fetch_cell_prepared('SELECT host_template_id
						FROM host where id = ?',
						array($adding_host['id']));

					if (($checkfrequency['add_graph_frequency'] != 0) &&
						($checkfrequency['add_graph_frequency'] - ($poller_start - $addgraph_lastrun) <= 30) || $templates) {
						grid_debug("Checking host '" . $gridhost['host'] . "' For newly added Graph Templates!");

						set_config_option('addgraph_lastrun_' . $checkfrequency['clusterid'], $poller_start);

						$new_templates = db_fetch_assoc_prepared('SELECT graph_template_id
							FROM host_template_graph
							WHERE host_template_id = ?
							AND graph_template_id
							NOT IN (SELECT graph_template_id FROM graph_local WHERE host_id = ?)',
							array($host_template_id, $adding_host['id']));

						if (!empty($new_templates)) {
							foreach($new_templates as $newtemplate) {
								grid_debug("Found New Graph Template '" . $newtemplate['graph_template_id'] . "' For host!");
								check_add_graph($adding_host['id'], $host_template_id, $gridhost['host']);
							}

							push_out_host($adding_host['id']);
						}
					} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
						cacti_log('INFO: It is not time to check/add new graphs for Cluster ' . $checkfrequency['clusterid']);
					}
				}
			}
		} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log('INFO: It is not time to refresh Cluster ' . $checkfrequency['clusterid']);
		}
	}
}

if ($remove_proc) {
	remove_process_entry(0, 'ADDHOST');
}

function check_add_snmp($host, $host_template_id) {
	$snmp_queries = db_fetch_assoc_prepared('SELECT snmp_query_id
		FROM host_template_snmp_query
		WHERE host_template_id = ?',
		array($host_template_id));

	if (cacti_sizeof($snmp_queries)) {
		foreach ($snmp_queries as $snmp_query) {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
				cacti_log('INFO: SNMP Query ID : ' . $snmp_query['snmp_query_id'] . ' has been added to Host ID : ' . $host);
			}

			db_execute_prepared('REPLACE INTO host_snmp_query
				(host_id, snmp_query_id, reindex_method)
				VALUES (?, ?, ?)',
				array($host, $snmp_query['snmp_query_id'], DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME));
		}
	}
}

function check_add_graph($host, $host_template_id, $hostname) {
	$graph_templates = db_fetch_assoc_prepared('SELECT graph_template_id
		FROM host_template_graph
		WHERE host_template_id = ?',
		array($host_template_id));

	if (cacti_sizeof($graph_templates)) {
		foreach ($graph_templates as $graph_template) {
			$snmp_query_array = '';
			$suggested_values_array = array();

			db_execute_prepared('REPLACE INTO host_graph
				(host_id, graph_template_id)
				VALUES (?, ?)',
				array($host, $graph_template['graph_template_id']));

			$found = db_fetch_row_prepared('SELECT id
				FROM graph_local
				WHERE graph_template_id = ?
				AND host_id = ?
				LIMIT 1',
				array($graph_template['graph_template_id'], $host));

			if (!$found) {
				create_complete_graph_from_template($graph_template['graph_template_id'], $host, $snmp_query_array, $suggested_values_array);

				if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
					cacti_log('INFO: Graph Template ID : '.$graph_template['graph_template_id'] . ' has been added to ' . $hostname);
				}
			}
		}
	}
}

function reload_data_query($clusterid) {
	$grid_host = db_fetch_assoc_prepared('SELECT *
		FROM host
		WHERE clusterid = ?',
		array($clusterid));

	foreach ($grid_host as $gridhost) {
		$snmp_queries = db_fetch_assoc('SELECT snmp_query_id
			FROM host_template_snmp_query
			WHERE host_template_id = ?',
			array($gridhost['host_template_id']));

		foreach ($snmp_queries as $snmp_query) {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
				cacti_log('INFO: Reloading data query ID : ' . $snmp_query['snmp_query_id'] . ' for Host ID: ' . $gridhost['id']);
			}

			run_data_query($gridhost['id'], $snmp_query['snmp_query_id']);
		}
	}
}

function display_help() {
	print 'RTM Add Device and Graphs Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "This program allows you to control the addition of LSF hosts and their graphs\n";
	print "Usage: add_host.php [--clusterid=n] [--force] [--templates] [--debug] [--help]\n\n";
	print "   --clusterid=n  If you wish to target a clusterid, specify it's id.  Otherwise\n";
	print "                  all clusterid's are included\n";
	print "   --force        Force the automation even if it's not time.  Clusterids that\n";
	print "                  have collection disabled, will still not be included\n";
	print "   --templates    If specified, new graph templates and data queries will be checked\n";
	print "   --debug        If specified, provide verbose output\n";
	print "   --help         Display this message\n\n";
}

