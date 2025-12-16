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

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['library_path'] . '/import.php');
include_once($config['library_path'] . '/utility.php');
include_once($config['library_path'] . '/rtm_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug        = FALSE;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}
	switch ($arg) {
	case "-d":
		$debug = true;
		break;
	case "-v":
    case "-V":
    case "--version":
        display_version();
        exit;
	}
}

echo "Importing RTM new host/dataquery/graph templates..\n";

$RTM_templates = array(
	'1' => array (
		'value' => 'GRID - Applications - Graphs',
		'name' => 'cacti_data_query_grid_-_applications_-_graphs.xml'
	),
	'2' => array (
		'value' => 'GRID - Job Groups - Graphs',
		'name' => 'cacti_data_query_grid_-_job_groups_-_graphs.xml'
	),
	'3' => array (
		'value' => 'GRID - Projects - All - Graphs',
		'name' => 'cacti_data_query_grid_-_projects_-_all_-_graphs.xml'
	),
	'4' => array (
		'value' => 'GRID - Cluster Pending by Pending Reason',
		'name' => 'cacti_graph_template_grid_-_cluster_pending_by_pending_reason.xml'
	),
	'5' => array (
		'value' => 'GRID - Host Group - Available Memory',
		'name' => 'cacti_graph_template_grid_-_host_group_-_available_memory.xml'
	),
	'6' => array (
		'value' => 'GRID - Host Group - Host Details',
		'name' => 'cacti_graph_template_grid_-_host_group_-_host_details.xml'
	),
	'7' => array (
		'value' => 'GRID - Host Group - Memory Stats',
		'name' => 'cacti_graph_template_grid_-_host_group_-_memory_stats.xml'
	),
	'8' => array (
		'value' => 'Net-SNMP - Context Switches',
		'name' => 'cacti_graph_template_net-snmp_-_context_switches.xml'
	),
	'9' => array (
		'value' => 'Net-SNMP - CPU Utilization',
		'name' => 'cacti_graph_template_net-snmp_-_cpu_utilization.xml'
	),
	'10' => array (
		'value' => 'Net-SNMP - Interrupts',
		'name' => 'cacti_graph_template_net-snmp_-_interrupts.xml'
	),
	'11' => array (
		'value' => 'Grid Summary',
		'name' => 'cacti_host_template_grid_summary.xml'
	),
	'12' => array (
		'value' => 'Guarantee SLA Resource Usage',
		'name' => 'cacti_data_query_grid_-_guarantee_sla_resource_usage.xml'
	),
	'13' => array (
		'value' => 'Guarantee Resource Pool Usage',
		'name' => 'cacti_data_query_grid_-_guarantee_resource_pool_usage.xml'
	),
);

foreach($RTM_templates as $rtmtemplates){
	echo 'Importing ' . $rtmtemplates['value'] . ".\n";
	$results = rtm_do_import($config['base_path'] . '/templates/' . $rtmtemplates['name']);
}

echo "Templates import complete.\n";

function display_version() {
	include_once(dirname(__FILE__) . '/setup.php');

	print 'IBM Spectrum LSF RTM Upgrade Template Utility ' . get_grid_version() . "\n";
	print rtm_copyright();
}
