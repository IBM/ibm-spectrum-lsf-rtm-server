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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');
include_once($config['base_path'] . '/lib/import.php');
include_once($config['base_path'] . '/lib/utility.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);
	switch ($arg) {
	case "-d":
		$debug = true;
		break;
    case "-v":
    case "-V":
    case "--version":
        display_version();
        exit;
	case '-xmls':
		$xml_files = @explode(',', $value);
		foreach($xml_files as $xml_file){
			$XML_file=trim($xml_file);
			if(!empty($XML_file)){
				echo 'Importing ' . $config['base_path'] . "/plugins/RTM/templates/$XML_file" . ".\n";
				$results = rtm_do_import($config['base_path'] . "/plugins/RTM/templates/$XML_file");
			}
		}
		exit;
	}
}

echo "Importing RTM basic device templates..\n";

$RTM_templates = array(
	'1' => array (
		'value' => 'Local Linux Machine',
		'name' => 'cacti_host_template_local_linux_machine.xml'
	),
	'2' => array (
		'value' => 'Cacti Polling Host',
		'name' => 'cacti_host_template_cacti_polling_host.xml'
	),
	'3' => array (
		'value' => 'Cisco Router',
		'name' => 'cacti_host_template_cisco_router.xml'
	),
	'4' => array (
		'value' => 'Generic SNMP-enabled Host',
		'name' => 'cacti_host_template_generic_snmp-enabled_host.xml'
	),
	'5' => array (
		'value' => 'Karlnet Wireless Bridge',
		'name' => 'cacti_host_template_karlnet_wireless_bridge.xml'
	),
	'6' => array (
		'value' => 'Net-SNMP Host',
		'name' => 'cacti_host_template_net-snmp_host.xml'
	),
	'7' => array (
		'value' => 'Netware 4/5 Server',
		'name' => 'cacti_host_template_netware_45_server.xml'
	),
	'8' => array (
		'value' => 'teMySQL Host',
		'name' => 'cacti_host_template_temysql_host.xml'
	),
	'9' => array (
		'value' => 'Windows 2000/XP Host',
		'name' => 'cacti_host_template_windows_2000xp_host.xml'
	),
	'10' => array (
		'value' => 'SNMP - Generic OID Template',
		'name' => 'cacti_graph_template_snmp_-_generic_oid_template.xml'
	),
	'11' => array (
		'value' => 'Unix - Ping Latency',
		'name' => 'cacti_graph_template_unix_-_ping_latency.xml'
	),
	'12' => array (
		'value' => 'Netware - Total Users',
		'name' => 'cacti_data_template_netware_-_total_users.xml'
	),
	'13' => array (
		'value' => 'teMySQL - Replication',
		'name' => 'cacti_data_template_temysql_-_replication.xml'
	)
);

foreach($RTM_templates as $rtmtemplate){
	echo 'Importing ' . $rtmtemplate['value'] . ".\n";
	$results = rtm_do_import($config['base_path'] . '/plugins/RTM/templates/' . $rtmtemplate['name']);
}

echo "RTM basic templates import complete.\n";

function display_version() {
	include_once(dirname(__FILE__) . '/setup.php');

	print 'IBM Spectrum LSF RTM Import Template Utility ' . get_rtm_version() . "\n";
	print rtm_copyright();
}
