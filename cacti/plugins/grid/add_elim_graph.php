#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2022 The Cacti Group                                 |
 | Copyright IBM Corp. 2006, 2022                                          |
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
include_once(dirname(__FILE__) . '/grid_elim_functions.php');
include_once($config['library_path'] . '/api_automation_tools.php');
include_once($config['library_path'] . '/utility.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/snmp.php');
include_once($config['library_path'] . '/data_query.php');
include_once($config['library_path'] . '/api_device.php');
include_once($config['library_path'] . '/template.php');
include_once($config['library_path'] . '/rtm_functions.php');

$poller_start = microtime(true);

/* input parameters */
$force     = false;
$debug     = false;
$clusterid = 0;

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

if (detect_and_correct_running_processes(0, 'ADDELIMGRAPH', 3600)) {
	/* continue */
	grid_debug('Lock for ELIM GRAPH process in place');

	$remove_proc = true;
} elseif ($force) {
	/* continue */
	grid_debug('ELIM GRAPH automation is being forced');

	$remove_proc = false;
} else {
	print "NOTE: Add ELIM GRAPH is Already Running.  Use option '--force' to override.  Exiting!\n";
	exit;
}

$check_add_host_frequency = db_fetch_assoc("SELECT add_frequency, add_graph_frequency,
	clusterid, host_template_id
	FROM grid_clusters
	WHERE disabled !='on'");

foreach ($check_add_host_frequency as $checkfrequency) {
	if ($checkfrequency['add_frequency'] == 0) {
		//if value is 0, will not add host.
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log('INFO : Cluster ' . $checkfrequency['clusterid'] . ' will not be updated.');
		}
	} else {
		grid_debug('Inspecting Cluster ' . $checkfrequency['clusterid'] . ' via option');
		$addelim_lastrun = read_config_option('addelim_lastrun_'.$checkfrequency['clusterid']);

		if ($poller_start - $addelim_lastrun >= $checkfrequency['add_frequency'] || $force) {
			if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
				cacti_log('INFO : It is time to refresh Cluster ' . $checkfrequency['clusterid']);
			}

			/* if we are forcing a run and this is not the clusterid, skip */
			if ($clusterid > 0 && $checkfrequency['clusterid'] != $clusterid) {
				grid_debug('Skipping Cluster ' . $checkfrequency['clusterid'] . ' via option');
				continue;
			}

			set_config_option('addelim_lastrun_' . $checkfrequency['clusterid'], $poller_start);

			$elim_instances = db_fetch_assoc_prepared('SELECT geti.id, geti.name
				FROM grid_elim_template_instances AS geti
				INNER JOIN grid_clusters AS gc
				ON geti.clusterid=gc.clusterid
				WHERE gc.disabled=""
				AND gc.clusterid = ?',
				array($checkfrequency['clusterid']));

			if (cacti_sizeof($elim_instances)) {
				foreach ($elim_instances as $elim_instance) {
					grid_debug("Checking ELIM Instance '" . $elim_instance['id'] . ': ' . $elim_instance['name'] . "' For Graphs");
					elim_check_add_graph($elim_instance['id']);
				}
			}
		} elseif (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
			cacti_log('INFO: It is not time to refresh Cluster ' . $checkfrequency['clusterid']);
		}
	}
}

if ($remove_proc) {
	remove_process_entry(0, 'ADDELIMGRAPH');
}


function display_help() {
	print 'RTM Add ELIM Graphs Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8').' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	print "This program allows you to control the addition of LSF elim graphs\n";
	print "Usage: add_elim_graph.php [--clusterid=n] [--force] [--debug] [--help]\n\n";
	print "   --clusterid=n  If you wish to target a clusterid, specify it's id.  Otherwise\n";
	print "                  all clusterid's are included\n";
	print "   --force        Force the automation even if it's not time.  Clusterids that\n";
	print "                  have collection disabled, will still not be included\n";
	print "   --debug        If specified, provide verbose output\n";
	print "   --help         Display this message\n\n";
}

