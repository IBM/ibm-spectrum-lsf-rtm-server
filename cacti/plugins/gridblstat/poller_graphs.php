#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');

/* take the start time to log performance data */
$start = microtime(true);

/* let this script run with lot's of memory */
ini_set('memory_limit', '-1');

include_once($config['library_path'] . '/api_automation_tools.php');
include_once($config['library_path'] . '/utility.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/snmp.php');
include_once($config['library_path'] . '/data_query.php');
include_once($config['library_path'] . '/api_device.php');
include_once($config['library_path'] . '/template.php');
include_once($config['library_path'] . '/rtm_functions.php');

error_reporting(E_ALL ^ E_DEPRECATED);

$php_bin   = read_config_option('path_php_binary');
$path_web  = read_config_option('path_webroot');
$path_grid = read_config_option('path_webroot') . '/plugins/grid';

global $php_bin, $path_web, $path_grid, $graphs;

array_shift($_SERVER['argv']);
$parms = $_SERVER['argv'];

$force     = false;
$debug     = false;
$reindex   = true;
$templates = false;
$graphs    = 0;
$lsid      = 0;

foreach ($parms as $parameter){
	@list($arg, $val) = @explode('=', $parameter);
	switch ($arg) {
	case '--force':
		$force = true;
		break;
	case '--templates':
		$templates = true;
		break;
	case '--debug':
		$debug = true;
		break;
	case '--lsid':
		$lsid = $val;
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

if ($lsid == 0 || !is_numeric($lsid) || $lsid < 0) {
	echo "FATAL: You must specify a valid License Scheduler Data Collector id using '--lsid=n'\n";
	exit(1);
}

/* find when the automation was last run */
$last_run        = db_fetch_cell_prepared("SELECT graph_lastrun FROM grid_blstat_collectors WHERE lsid=?", array($lsid));
$frequency       = db_fetch_cell_prepared("SELECT graph_freq FROM grid_blstat_collectors WHERE lsid=?", array($lsid));

/* see if it's time to run */
if ($force) {
	echo "NOTE: Graph Automation is being Forced\n";
}elseif ($frequency == 0) {
	$runtime = false;
	echo "NOTE: Graph Automation is Disabled\n";
}elseif (empty($last_run) || (strtotime($last_run) + $frequency) < time()) {
	$runtime = true;
	echo "NOTE: Its Time to Run Graph Automation\n";
}else{
	$runtime = false;
	echo "NOTE: Its Not Time to Run Graph Automation\n";
}

if ($force || ($runtime && detect_and_correct_running_processes(0, 'ADDGRIDBLSTAT_' . $lsid, 3600*6))) {
	/* log the lastrun value in the database */
	db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('gridblstat_graph_lastrun', ?)", array(time()));

	echo "NOTE: Adding/Updating Virtual Device for License Scheduler\n";
	add_data_query($force || $runtime, $templates, $reindex, $lsid);
	remove_process_entry(0, 'ADDGRIDBLSTAT_' . $lsid);

	/* take the end time to log performance data */
	$end = microtime(true);

	/* log the total automation time */
	cacti_log("GRIDBLSTAT GRAPH AUTOMATION STATS: Time:" . round($end-$start,2), true, "SYSTEM");

	db_execute_prepared("UPDATE grid_blstat_collectors SET graph_lastrun=NOW() WHERE lsid=?", array($lsid));
}

function add_data_query($force, $templates, $reindex, $lsid) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs;

	/* initialize default variables */
	$queryid = "all";
	$cluster_info = '';
	$host_value = 0;
	$host_id = 0;
	$id = 0;
	$host_template_id = 14;
	$snmp_community = "";
	$snmp_version = 0;
	$snmp_username = "";
	$snmp_password = "";
	$snmp_port = 161;
	$snmp_timeout = 500;
	$availability_method = '0' ;
	$ping_method = 2;
	$ping_port = 0;
	$ping_timeout = 400;
	$ping_retries = 1;
	$disabled = "";
	$notes = "";
	$snmp_auth_protocol = "";
	$snmp_priv_passphrase = "";
	$snmp_priv_protocol = "";
	$snmp_context = "";
	$max_oids = 5;
	$monitor = "on";

	/* getting the current time in unix timestamp */
	$current_time = time();

	if (!$force) {
		$lastupdate_time = read_config_option("gridblstat_device_update");

		if ($lastupdate_time == -1) {
			/* add the summary device the second time to make sure all hosts have been added correctly */
		}else if (86400-($current_time - $lastupdate_time) <= 30) {
			/* check if more than 24 hours have passed with 30 seconds buffer */
		}else{
			/* less than 24 hours have passed, do not continue execution */
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug){
			 	echo "It is not time to update License Scheduler graphs, use --force to force update.\n";
			}
			return;
		}
	}

	/* check for host that has association with the cluster */
	$host_template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='b1528fb95b04821b0e5b6a5aedd8e659'");
	$host_id = db_fetch_cell_prepared("SELECT id
		FROM host
		INNER JOIN grid_blstat_collectors as gbc
		ON host.id=gbc.cacti_host
		WHERE host_template_id IN (SELECT id FROM host_template WHERE hash='b1528fb95b04821b0e5b6a5aedd8e659')
		AND gbc.lsid=?", array($lsid));

	if ($debug) {
		echo "DEBUG: The License Scheduler Host ID is '$host_id'\n";
	}

	/* check for cluster with no cacti host, also add summary device if not cacti host association */
	if (empty($host_id)) {
		$hostname = db_fetch_cell_prepared("SELECT CONCAT('LS - ', name, ' - Region - ', region) AS hostname FROM grid_blstat_collectors WHERE lsid=?", array($lsid));
		/* add summary device for cluster */
		$host_value = api_device_save('0', $host_template_id, $hostname, "localhost",
			$snmp_community, $snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
			$availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries,$notes,
			$snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids);

		/* device has been added successfully */
		if ($host_value != 0) {
			db_execute_prepared("UPDATE grid_blstat_collectors SET cacti_host=? WHERE lsid=?", array($host_value, $lsid));
			if ($debug) {
				echo "DEBUG: Cacti Device Added for License Scheduler\n";
			}

			push_out_host($host_value);
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug){
				cacti_log("INFO: Pushed out host ". $host_value);
			}

			$host_id = $host_value;
		}
	}

	if (!empty($host_id) || $templates) {
		adding_graphs($host_id);
	}

	if (!empty($host_id)) {
		$snmp_queries = db_fetch_assoc_prepared("SELECT snmp_query.id,
			snmp_query.name,
			host_snmp_query.reindex_method
			FROM (snmp_query, host_snmp_query)
			WHERE snmp_query.id=host_snmp_query.snmp_query_id
			AND host_snmp_query.host_id=?
			ORDER BY snmp_query.id", array($host_id));
	}else{
		$snmp_queries = array();
	}

	if ($debug) {
		echo "NOTE: " . cacti_sizeof($snmp_queries) . " Data Queries found for Device Type\n";
	}

	/* correct a bug in cacti that does not allow '0' as the reindex method */
	if (cacti_sizeof($snmp_queries)) {
		foreach ($snmp_queries as $snmp_query) {
			db_execute_prepared("UPDATE IGNORE host_snmp_query SET reindex_method=0
				WHERE host_id=? AND snmp_query_id=?", array($host_id, $snmp_query['id']));
			db_execute_prepared("DELETE FROM poller_reindex WHERE host_id=? AND data_query_id=?", array($host_id, $snmp_query['id']));
		}
	}

	if ($reindex) {
		echo trim(passthru($php_bin." -q " . $path_web . "/cli/poller_reindex_hosts.php -id=" . $host_id . " -qid=$queryid"));
	}

	$snmp_queries = db_fetch_assoc_prepared("SELECT snmp_query.id,
		snmp_query.name,
		host_snmp_query.reindex_method
		FROM (snmp_query, host_snmp_query)
		WHERE snmp_query.id=host_snmp_query.snmp_query_id
		AND host_snmp_query.host_id=?
		ORDER BY snmp_query.id", array($host_id));

	if (cacti_sizeof($snmp_queries)) {
	foreach ($snmp_queries as $snmp_query){
		$snmp_query_types = db_fetch_assoc_prepared("SELECT id, name, graph_template_id
			FROM snmp_query_graph
			WHERE snmp_query_id=?
			ORDER BY id", array($snmp_query['id']));

		if (cacti_sizeof($snmp_query_types)) {
		foreach ($snmp_query_types as $snmp_query_type){
			$query_field_name = get_query_field_name($host_id, $snmp_query["id"]);
		add_data_query_graphs($host_id, $snmp_query_type['graph_template_id'], $snmp_query['id'], $snmp_query_type['id'], $query_field_name);
	}
		}
	}
	}

	if (!$force) {
		if ($lastupdate_time == ''){
			$lastupdate_time = -1;
		}else{
			$lastupdate_time         = $current_time;
		}
		if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log("INFO: Updating summary device time now");
		}

		db_execute_prepared("REPLACE INTO settings (name, value) values ('gridblstat_device_update', ?)", array($lastupdate_time));
	}
}

function get_query_field_name($host_id, $query_id) {
	return db_fetch_cell_prepared("SELECT sort_field
		FROM host_snmp_query
		WHERE host_id=? AND snmp_query_id=?", array($host_id, $query_id));
}

function add_data_query_graphs($host_id, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = "", $include = "on"){
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

	if ($debug) {
		echo "DEBUG: Found is '$found', Field Name is '$snmp_field_name'\n";
	}

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = array_rekey(db_fetch_assoc_prepared("SELECT field_value
			FROM host_snmp_cache
			WHERE host_id=?
			AND snmp_query_id=?
			AND field_name=?", array($host_id, $snmp_query_id, $snmp_field_name)), "field_value", "field_value");

		if (cacti_sizeof($items)) {
		foreach($items as $key => $item) {
			if ($regmatch == "") {
				/* add graph below */
			}elseif ((($include == "on") && (preg_match("/$regmatch/", $item))) ||
				(($include != "on") && (!preg_match("/$regmatch/", $item)))) {
				/* add graph below */
			}else{
				echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id\n";
				continue;
			}

			/* see if graph exists */
			$exists = db_fetch_cell_prepared("SELECT count(*)
				FROM graph_local
				WHERE host_id=?
				AND graph_template_id=?
				AND snmp_query_id=?
				AND snmp_index=?", array($host_id, $graph_template_id, $snmp_query_id, $item));

			if (!$exists) {
				echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id\n";

				$command = "$php_bin -q $path_web/cli/add_graphs.php" .
					" --graph-template-id=$graph_template_id --graph-type=ds"     .
					" --snmp-query-type-id=$snmp_query_type_id --host-id=$host_id" .
					" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
					" --snmp-value='" . $item . "'";

				echo trim(passthru($command)) . "\n";
				$graphs++;
			}
		}
		}
	}elseif (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Device", TRUE, "GRID");
	}
}

function adding_graphs($host_id) {
	global $php_bin, $path_web, $path_grid, $debug, $graphs;

	/* get cluster templates */
	$templates = db_fetch_assoc("SELECT host_template.name AS host_template_name,
		graph_templates.name AS graph_template_name,
		host_template.id AS host_template_id,
		host_template_graph.graph_template_id AS host_template_graph_id
		FROM host_template_graph
		INNER JOIN host_template
		ON host_template_graph.host_template_id=host_template.id
		INNER JOIN graph_templates
		ON host_template_graph.graph_template_id=graph_templates.id
		WHERE host_template.hash='b1528fb95b04821b0e5b6a5aedd8e659'
		ORDER BY host_template.name");

	if (cacti_sizeof($templates)) {
		/* set_grid_summary($host_id, "yes"); */
		foreach($templates as $template) {
			/* workaround for a bug, see if graph is created already */
			$found = db_fetch_cell_prepared("SELECT id
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
	}else{
		echo "NOTE: No Templates found for Device Type\n";
		return false;
	}

	return true;
}

function display_help () {
	global $config;

	print "RTM License Scheduler Host Automation Script Version " . read_config_option("grid_version") . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	print "usage: poller_graphs.php [--no-reindex] [--templates] [--debug] [--force] --lsid=n [-h|--help|-v|-V|--version]\n\n";
	print "--no-reindex     - Quick option. Does not Reindex Clusters\n";
	print "--templates      - Rescan Templates for new Entries and Add Graphs\n";
	print "--force          - Force execution regardless of timing\n";
	print "--debug          - Display verbose output during execution\n";
	print "--lsid=n         - You must specify a valid License Scheduler Data Collector id, except -v, -h\n";
	print "-v -V --version  - Display this help message\n";
	print "-h --help        - Display this help message\n\n";
}

?>

