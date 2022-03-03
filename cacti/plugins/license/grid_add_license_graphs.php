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

/* display NO errors */
error_reporting(0);

$dir = dirname(__FILE__);
ini_set('max_execution_time', '0');

chdir($dir);

if (strpos($dir, 'grid') !== false) {
	chdir('../../');
}

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/api_data_source.php');
include_once($config['base_path'] . '/lib/api_graph.php');
include_once($config['base_path'] . '/lib/snmp.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/api_device.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$list_servers = false;
$summary      = false;
$hostid       = 0;
$template     = 0;

if (cacti_sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

		switch ($arg) {
		case '-d':
			$debug = true;

			break;
		case '--summary':
			$summary = true;

			break;
		case '--host-id':
			$hostid = trim($value);

			break;
		case '--host-template':
			$template = trim($value);

			break;
		case '-h':
		case '-h':
		case '-H':
		case '--help':
		case '-v':
		case '-V':
		case '--version':
			display_help();
			return 0;
		case '--list-license-servers':
			$list_servers = true;
			break;
		case '--list-host-templates':
			displayHostTemplates(getHostTemplates());
			return 0;
			display_help();
			return 0;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}

	if ($list_servers) {
		display_license_servers(get_license_servers($template, "", $list_servers));
		return 0;

	}

	if ($hostid != 'all' && $hostid <= 0) {
		print "ERROR: Your Hostid is Invalid '$hostid'\n";
		display_help();
		exit(1);
	}

	if (isset($template)) {
		if ($template < 0) {
			print "ERROR: You must specify a valid Host Template ID '$template'\n";
			display_help();
			exit(1);
		}
	}

	if ($summary && $template > 0) {
		print "ERROR: You can not add general graphs and summary graphs at the same time\n";
		display_help();
		exit(1);
	}

	$php_bin    = read_config_option('path_php_binary');
	$path_grid  = read_config_option('path_webroot') . '/plugins/grid';
	$path_cacti = read_config_option('path_webroot');

	if (isset($template)) {
		$hosts = get_license_servers($template, $hostid);
	}

	if ($summary) {
		$host = db_fetch_row_prepared("SELECT * FROM host WHERE id=?", array($hostid));

		$snmpQueryGraph = db_fetch_row("select * from snmp_query_graph where hash='1089aa277f4d817651d7a9754d8511b1'");
		/* Summary License Use Graphs Graph Template: 52, Data Query: 23, Assoc Graph Template: 23, Query Field Name 'gridFeatureName' */
		add_graphs_for_dq($host, $snmpQueryGraph['graph_template_id'], $snmpQueryGraph['snmp_query_id'], $snmpQueryGraph['id'], 'gridFeatureName');
	} else {
		add_license_data_queries($hosts, $template);
	}
} else {
	print "ERROR: You must provide parameters to run this program\n";

	display_help();

	return 0;
}

function add_license_data_queries($hosts, $template) {
	global $php_bin, $config, $path_web, $path_cacti;

	if (cacti_sizeof($hosts)) {
		foreach($hosts as $host) {
			print trim(passthru($php_bin . ' -q ' . cacti_escapeshellcmd($path_cacti . '/cli/poller_reindex_hosts.php') .
				' -id=' . cacti_escapeshellarg($host['id']) .
				' -qid=all'));

			$snmpQueryGraph = db_fetch_row("select * from snmp_query_graph where hash='9c4403070a529eed04304d1fbd88f699'");
			/* License Use Graphs Graph Template: 52, Data Query: 14, Assoc Graph Template: 52, Query Field Name 'gridFeatureName' */
			add_graphs_for_dq($host, $snmpQueryGraph['graph_template_id'], $snmpQueryGraph['snmp_query_id'], $snmpQueryGraph['id'], 'gridFeatureName');
		}
	} else {
		print "NOTE: No License Servers Found\n";
	}
}

function add_graphs_for_dq($host, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = '', $include = 'on') {
	global $config, $php_bin, $path_grid, $path_cacti;

	/* let's see what queries are defined for this host */
	$query_types = exec_into_array($php_bin . ' -q ' . cacti_escapeshellcmd($path_grid . '/grid_add_graphs.php') .
		' --host-id=' . cacti_escapeshellarg($host['id']) .
		' --snmp-query-id=' . cacti_escapeshellarg($snmp_query_id) .
		' --list-query-types');

	$found = false;
	foreach($query_types as $type) {
		if (substr_count($type, $snmp_query_type_id)) {
			$found = true;
			break;
		}
	}

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = exec_into_array($php_bin . ' -q ' . cacti_escapeshellcmd($path_cacti . '/cli/add_graphs.php') .
			' --host-id=' . cacti_escapeshellarg($host['id']) .
			' --snmp-query-id=' . cacti_escapeshellarg($snmp_query_id) .
			' --snmp-field=' . cacti_escapeshellarg($snmp_field_name) .
			' --list-snmp-values');

		if (cacti_sizeof($items)) {
			foreach($items as $item) {
				if ((trim($item) == "") ||
					(substr($item, 0, 5) == "Known") ||
					(substr($item, 0, 6) == "FATAL:") ||
					(substr($item, 0, 6) == "ERROR:")) {
					/* ignore */
					continue;
				} else {
					if ($regmatch == "") {
						/* add graph below */
					} else if ((($include == "on") && (preg_match("/$regmatch/", $item))) ||
						(($include != "on") && (!preg_match("/$regmatch/", $item)))) {
						/* add graph below */
					} else {
						print "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"] . "\n";
						continue;
					}

					print "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id and : " . $host['hostname'] . "\n";
					$command = $php_bin . ' -q ' . cacti_escapeshellcmd($path_cacti . '/cli/add_graphs.php') .
						' --graph-template-id=' . cacti_escapeshellarg($graph_template_id) .
						' --graph-type=ds'     .
						' --snmp-query-type-id=' . cacti_escapeshellarg($snmp_query_type_id) .
						' --host-id=' . cacti_escapeshellarg($host['id']) .
						' --snmp-query-id=' . cacti_escapeshellarg($snmp_query_id) .
						' --snmp-field=' . cacti_escapeshellarg($snmp_field_name) .
						' --snmp-value=' . cacti_escapeshellarg($item);

					print trim(shell_exec($command)) . "\n";
				}
			}
		}
	} else {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Host: " . $host["hostname"], true, "RTM");
	}
}

function get_license_servers($template, $hostid = '', $list_servers=false) {
	$sql_params = array();
	$servers = array();
	$sql_where = '';

	if (strlen($template)) {
		$sql_where = "WHERE host_template_id=?";
		$sql_params[] = $template;
	}

	if ($list_servers) {
		$sql_where = 'WHERE (host_template_id=15 OR host_template_id=16)';
	}

	if (strlen($hostid)) {
		if (strtolower($hostid) != 'all') {
			if (strlen($sql_where)) {
				$sql_where .= " AND id=?";
			} else {
				$sql_where = "WHERE id=?";
			}
			$sql_params[] = $hostid;
		}
	}

	$tmpArray = db_fetch_assoc_prepared("SELECT *
		FROM host
		$sql_where
		ORDER BY hostname", $sql_params);

	if (cacti_sizeof($tmpArray)) {
		foreach ($tmpArray as $server) {
			$servers[$server['id']] = $server;
		}
	}

	return $servers;
}

function display_license_servers($servers) {
	print "Known License Servers:(id, hostname)\n";

	if (cacti_sizeof($servers)) {
		foreach($servers as $server) {
			print "\t" . $server['id'] . "\t" . $server['hostname'] . "\n";
		}
	}
}

function display_help() {
	print 'RTM Add License Graphs Utility ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "This program allows you to manage the addition of license graphs into RTM\n\n";
	print "You must provide the Host Template and the Host ID's to include.  You can use the 'all' keyword\n";
	print "for all hosts.\n\n";
	print "Usage:\n";
	print "grid_add_license_graphs.php [--host-id=n --summary] | [--host-template=n --host-id=n|all]\n\n";
	print "Required for Summary Graphs:\n";
	print "    --host-id       'n'| 'all' only hosts from this hostgroups are added\n";
	print "    --summary                  create summary graphs for host\n";
	print "Required for General Graphs:\n";
	print "    --host-template 'n'        only hosts from this hostgroups are added\n";
	print "    --host-id       'n'| 'all' only hosts from this hostgroups are added\n";
	print "Optional:\n";
	print "    --help | -h    displays this message\n";
	print "List Options:\n";
	print "    --list-license-servers\n";
	print "    --list-host-templates\n";
}
