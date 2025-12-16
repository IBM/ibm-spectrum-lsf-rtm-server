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

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$description   = '';
	$ip            = '';
	$template_id   = 0;
	$community     = '';
	$snmp_ver      = '0';
	$disable       = 0;
	$host_id       = 0;

	$notes         = '';

	$snmp_username        = '';
	$snmp_password        = '';
	$snmp_auth_protocol   = '';
	$snmp_priv_passphrase = '';
	$snmp_priv_protocol   = '';
	$snmp_context         = '';
	$snmp_port            = 161;
	$snmp_timeout         = 500;

	$avail        = '0';
	$ping_method  = 3;
	$ping_port    = 23;
	$ping_timeout = 500;
	$ping_retries = 2;
	$max_oids     = 5;

	$displayHostTemplates = FALSE;
	$displayCommunities   = FALSE;
	$quietMode            = FALSE;

	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch ($arg) {
		case '-d':
			$debug = TRUE;

			break;
		case '--description':
			$description = trim($value);

			break;
		case '--ip':
			$ip = trim($value);

			break;
		case '--id':
			$host_id = trim($value);
			break;
		case '--clusterid':
			$clusterid = trim($value);
			break;
		case '--template':
			$template_id = $value;

			break;
		case '--community':
			$community = trim($value);

			break;
		case '--version':
			$snmp_ver = trim($value);

			break;
		case '--notes':
			$notes = trim($value);

			break;
		case '--disable':
			$disable  = $value;

			break;
		case '--username':
			$snmp_username = trim($value);

			break;
		case '--password':
			$snmp_password = trim($value);

			break;
		case '--authproto':
			$snmp_auth_protocol = trim($value);

			break;
		case '--privproto':
			$snmp_priv_protocol = trim($value);

			break;
		case '--privpass':
			$snmp_priv_passphrase = trim($value);

			break;
		case '--port':
			$snmp_port     = $value;

			break;
		case '--timeout':
			$snmp_timeout  = $value;

			break;
		case '--avail':
			switch($value) {
			case 'none':
				$avail = '0';

				break;
			case 'ping':
				$avail = 3;

				break;
			case 'snmp':
				$avail = 2;

				break;
			case 'pingsnmp':
				$avail = 1;

				break;
			default:
				echo "ERROR: Invalid Availability Parameter: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_method':
			switch(strtolower($value)) {
			case 'icmp':
				$ping_method = 1;

				break;
			case 'tcp':
				$ping_method = 3;

				break;
			case 'udp':
				$ping_method = 2;

				break;
			default:
				echo "ERROR: Invalid Ping Method: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_port':
			if (is_numeric($value) && ($value > 0)) {
				$ping_port = $value;
			}else{
				echo "ERROR: Invalid Ping Port: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_retries':
			if (is_numeric($value) && ($value > 0)) {
				$ping_retries = $value;
			}else{
				echo "ERROR: Invalid Ping Retries: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--max_oids':
			if (is_numeric($value) && ($value > 0)) {
				$max_oids = $value;
			}else{
				echo "ERROR: Invalid Max OIDS: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--update':
			$update = true;
			break;
		case '--force':
			$force = true;
			break;
		case '-v':
		case '-V':
		case '-h':
		case '-H':
		case '--help':
			display_help();
			exit(0);
		case '--list-clusters':
			display_clusters(get_clusters());
			exit(0);
		case '--list-communities':
			$displayCommunities = TRUE;

			break;
		case '--list-host-templates':
			$displayHostTemplates = TRUE;

			break;
		case '--quiet':
			$quietMode = TRUE;

			break;
		default:
			echo "ERROR: Invalid Argument: ($arg)\n\n";
			display_help();
			exit(1);
		}
	}

	if ($displayCommunities) {
		displayCommunities($quietMode);
		exit(0);
	}

	if ($displayHostTemplates) {
		displayHostTemplates(getHostTemplates(), $quietMode);
		exit(0);
	}

	/* process the various lists into validation arrays */
	$host_templates = getHostTemplates();
	$hosts          = getHosts();
	$addresses      = getAddresses();
	$clusters       = get_clusters();

	/* process templates */
	if (!isset($host_templates[$template_id])) {
		echo "ERROR: Unknown template id ($template_id)\n";
		exit(1);
	}

	/* process clusters */
	if (!isset($clusters[$clusterid])) {
		echo "ERROR: Unknown cluster id ($clusterid)\n";
		exit(1);
	}

	/* process the device_id */
	if ($host_id > 0) {
		$temp_id = db_fetch_cell_prepared("SELECT id FROM host WHERE id=?", array($host_id));
		if (empty($temp_id)) {
			echo "ERROR: Device ID specified but does not exist in database ($host_id)\n";
			exit(1);
		}
	}

	/* process host description */
	if (isset($hosts[$description])) {
		if ($update) {
			$host_id = $hosts[$description];
		}else if (!$force) {
			echo "ERROR: Host Description already exists, use --force to override and add a new same named device\n";
			exit(1);
		}
	}

	if ($description == "") {
		echo "ERROR: You must supply a description for all hosts!\n";
		exit(1);
	}

	/* process ip */
	if (isset($addresses[$ip])) {
		if ($update) {
			$host_id = $addresses[$ip];
		}else if (!$force) {
			echo "ERROR: Hostname already exists, use --force to override and add a new device with the same hostname\n";
			exit(1);
		}
	}

	if ($ip == "") {
		echo "ERROR: You must supply an IP address for all hosts!\n";
		exit(1);
	}

	/* process snmp information */
	if ($snmp_ver != "0" && $snmp_ver != "1" && $snmp_ver != "2" && $snmp_ver != "3") {
		echo "ERROR: Invalid snmp version ($snmp_ver)\n";
		exit(1);
	}else{
		if ($snmp_port <= 1 || $snmp_port > 65534) {
			echo "ERROR: Invalid port.  Valid values are from 1-65534\n";
			exit(1);
		}

		if ($snmp_timeout <= 0 || $snmp_timeout > 20000) {
			echo "ERROR: Invalid timeout.  Valid values are from 1 to 20000\n";
			exit(1);
		}
	}

	/* community/user/password verification */
	if ($snmp_ver == "0" || $snmp_ver == "1" || $snmp_ver == "2") {
		/* snmp community can be blank */
	}else{
		if ($snmp_username == "" || $snmp_password == "") {
			echo "ERROR: When using snmpv3 you must supply an username and password\n";
			exit(1);
		}
	}

	/* validate the disable state */
	if ($disable != 1 && $disable != 0) {
		echo "ERROR: Invalid disable flag ($disable)\n";
		exit(1);
	}

	if ($disable == 0) {
		$disable = "";
	}else{
		$disable = "on";
	}

	echo "Adding $description ($ip) as \"" . $host_templates[$template_id] . "\" using SNMP v$snmp_ver with community \"$community\"\n";

	$host_id = api_add_griddevice_save($host_id, $template_id, $description, $ip,
				$community, $snmp_ver, $snmp_username, $snmp_password,
				$snmp_port, $snmp_timeout, $disable, $avail, $ping_method,
				$ping_port, $ping_timeout, $ping_retries, $notes,
				$snmp_auth_protocol, $snmp_priv_passphrase,
				$snmp_priv_protocol, $snmp_context, $max_oids, $clusterid);

	if (is_error_message()) {
		echo "ERROR: Failed to add this device\n";
		exit(1);
	} else {
		echo "NOTE: Success - new device-id: ($host_id)\n";
		exit(0);
	}
}else{
	display_help();
	exit(0);
}

function get_clusters() {
	$clusters = array();

	$tmpArray = db_fetch_assoc("SELECT * FROM grid_clusters ORDER BY clusterid");

	foreach ($tmpArray as $cluster) {
		$clusters[$cluster["clusterid"]] = $cluster;
	}

	return $clusters;
}

function display_clusters($clusters) {
	echo "Known Clusters:(clusterid, clustername)\n";

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $cluster) {
			echo "\t" . $cluster["clusterid"] . "\t" . $cluster["clustername"] . "\n";
		}
	}
}

function api_add_griddevice_save($id, $host_template_id, $description, $hostname, $snmp_community,
				$snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled,
				$availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries, $notes,
				$snmp_auth_protocol, $snmp_priv_passphrase,	$snmp_priv_protocol, $snmp_context,
				$max_oids, $clusterid) {

	/* fetch some cache variables */
	if (empty($id)) {
		$_host_template_id = 0;
	}else{
		$_host_template_id = db_fetch_cell_prepared("select host_template_id from host where id=?", array($id));
	}

	$save["id"] = $id;
	$save["host_template_id"] = form_input_validate($host_template_id, "host_template_id", "^[0-9]+$", false, 3);
	$save["clusterid"] = form_input_validate($clusterid, "clusterid", "^[0-9]+$", false, 3);
	$save["description"] = form_input_validate($description, "description", "", false, 3);
	$save["hostname"] = form_input_validate(trim($hostname), "hostname", "", false, 3);
	$save["notes"] = form_input_validate($notes, "notes", "", true, 3);
	$save["snmp_version"] = form_input_validate($snmp_version, "snmp_version", "", true, 3);
	$save["snmp_community"] = form_input_validate($snmp_community, "snmp_community", "", true, 3);
	$save["snmp_username"] = form_input_validate($snmp_username, "snmp_username", "", true, 3);
	$save["snmp_password"] = form_input_validate($snmp_password, "snmp_password", "", true, 3);
	$save["snmp_auth_protocol"] = form_input_validate($snmp_auth_protocol, "snmp_auth_protocol", "", true, 3);
	$save["snmp_priv_passphrase"] = form_input_validate($snmp_priv_passphrase, "snmp_priv_passphrase", "", true, 3);
	$save["snmp_priv_protocol"] = form_input_validate($snmp_priv_protocol, "snmp_priv_protocol", "", true, 3);
	$save["snmp_context"] = form_input_validate($snmp_context, "snmp_context", "", true, 3);
	$save["snmp_port"] = form_input_validate($snmp_port, "snmp_port", "^[0-9]+$", false, 3);
	$save["snmp_timeout"] = form_input_validate($snmp_timeout, "snmp_timeout", "^[0-9]+$", false, 3);
	$save["disabled"] = form_input_validate($disabled, "disabled", "", true, 3);
	$save["availability_method"] = form_input_validate($availability_method, "availability_method", "^[0-9]+$", true, 3);
	$save["ping_method"] = form_input_validate($ping_method, "ping_method", "^[0-9]+$", true, 3);
	$save["ping_port"] = form_input_validate($ping_port, "ping_port", "^[0-9]+$", true, 3);
	$save["ping_timeout"] = form_input_validate($ping_timeout, "ping_timeout", "^[0-9]+$", true, 3);
	$save["ping_retries"] = form_input_validate($ping_retries, "ping_retries", "^[0-9]+$", true, 3);
	$save["max_oids"] = form_input_validate($max_oids, "max_oids", "^[0-9]+$", true, 3);
	$save["monitor"] = "on";

	$save = api_plugin_hook_function('api_device_save', $save);

	$host_id = 0;

	if (!is_error_message()) {
		$host_id = sql_save($save, "host");

		if ($host_id) {
			raise_message(1);

			/* push out relavant fields to data sources using this host */
			push_out_host($host_id, 0);

			/* the host substitution cache is now stale; purge it */
			//kill_session_var("sess_host_cache_array");

			/* update title cache for graph and data source */
			update_data_source_title_cache_from_host($host_id);
			update_graph_title_cache_from_host($host_id);
		}else{
			raise_message(2);
		}

		/* if the user changes the host template, add each snmp query associated with it */
		if (($host_template_id != $_host_template_id) && (!empty($host_template_id))) {
			$snmp_queries = db_fetch_assoc_prepared("select snmp_query_id from host_template_snmp_query where host_template_id=?", array($host_template_id));

			if (cacti_sizeof($snmp_queries) > 0) {
			foreach ($snmp_queries as $snmp_query) {
				db_execute_prepared("replace into host_snmp_query (host_id,snmp_query_id,reindex_method) values (?, ?, ?)", array($host_id, $snmp_query["snmp_query_id"], DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME));

				/* recache snmp data */
				run_data_query($host_id, $snmp_query["snmp_query_id"]);
			}
			}

			$graph_templates = db_fetch_assoc_prepared("select graph_template_id from host_template_graph where host_template_id=?", array($host_template_id));

			if (cacti_sizeof($graph_templates) > 0) {
			foreach ($graph_templates as $graph_template) {
				db_execute_prepared("replace into host_graph (host_id,graph_template_id) values (?, ?)", array($host_id, $graph_template["graph_template_id"]));
			}
			}
		}
	}

	return $host_id;
}

function display_help() {
	echo 'RTM Add Devices Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	echo "A simple command line utility to add a device in Cacti\n\n";
	echo "usage: grid_add_device.php --description=[description] --ip=[IP] --template=[ID] --clusterid=[ID] \n";
	echo "    [--notes=\"[]\"] [--disable]\n";
	echo "    [--avail=[none]] --ping_method=[icmp] --ping_port=[N/A, 1-65534] --ping_retries=[2]\n";
	echo "    [--version=[1|2|3]] [--community=] [--port=161] [--timeout=500]\n";
	echo "    [--username= --password=] [--authproto=] [--privpass= --privproto=] [--context=]\n";
	echo "    [[--id=[device_id] [--update]] [--force] [--quiet] \n\n";

	echo "Required:\n";
	echo "    --description  the name that will be displayed by Cacti in the graphs\n";
	echo "    --ip           self explanatory (can also be a FQDN)\n";
	echo "    --clusterid    the clusterid for the cluster that this device belongs to\n";
	echo "    --template     the host template to utilize (read below to get a list of host templates)\n";
	echo "Optional:\n";
	echo "    --notes        '', General information about this host.  Must be enclosed using double quotes.\n";
	echo "    --disable      0, 1 to add this host but to disable checks and 0 to enable it\n";
	echo "    --avail        pingsnmp, [ping][none, snmp, pingsnmp]\n";
	echo "    --ping_method  tcp, icmp|tcp|udp\n";
	echo "    --ping_port    '', 1-65534\n";
	echo "    --ping_retries 2, the number of time to attempt to communicate with a host\n";
	echo "    --version      1, 1|2|3, snmp version\n";
	echo "    --community    '', snmp community string for snmpv1 and snmpv2.  Leave blank for no community\n";
	echo "    --port         161\n";
	echo "    --timeout      500\n";
	echo "    --username     '', snmp username for snmpv3\n";
	echo "    --password     '', snmp password for snmpv3\n";
	echo "    --authproto    '', snmp authentication protocol for snmpv3\n";
	echo "    --privpass     '', snmp privacy passphrase for snmpv3\n";
	echo "    --privproto    '', snmp privacy protocol for snmpv3\n";
	echo "    --context      '', snmp context for snmpv3\n";
	echo "    --max_oids     10, 1-60, the number of OID's that can be obtained in a single SNMP Get request\n\n";
	echo "Overrides:\n";
	echo "    --id           The Cacti device id.  Required if updating a device\n";
	echo "    --update       updates an already existing device.  You must specify either the id, hostname, or description\n";
	echo "    --force        adds a new device regardless of the existance of a similar device\n\n";
	echo "List Options:\n";
	echo "    --list-clusters\n";
	echo "    --list-host-templates\n";
	echo "    --list-communities\n";
	echo "    --quiet - batch mode value return\n\n";
}

