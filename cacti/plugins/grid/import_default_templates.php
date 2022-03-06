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
include_once($config['library_path'] . '/import.php');
include_once($config['library_path'] . '/utility.php');
include_once($config['library_path'] . '/rtm_functions.php');

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
				echo 'Importing ' . $config['base_path'] . "/plugins/grid/templates/$XML_file" . ".\n";
				$results = rtm_do_import($config['base_path'] . "/plugins/grid/templates/$XML_file");
			}
		}
        exit;
	}
}

echo "Importing Grid plugin default device templates, data queryies, graph templates...\n";

$grid_templates = array(
	'1' => array (
		'value' => 'Grid Host (w/net-snmp)',
		'name' => 'cacti_host_template_grid_host_wnet-snmp.xml'
	),
	'2' => array (
		'value' => 'Grid Host',
		'name' => 'cacti_host_template_grid_host.xml'
	),
	'3' => array (
		'value' => 'Grid Summary',
		'name' => 'cacti_host_template_grid_summary.xml'
	),
	'4' => array (
		'value' => 'GRID - Projects - All - Graphs',
		'name' => 'cacti_data_query_grid_-_projects_-_all_-_graphs.xml'
	),
    "5" => array (
        'value' => 'GRID - Host Group Stats',
        'name' => 'cacti_data_query_grid_-_host_group_stats.xml'
	),
	'6' => array (
		'value' => 'GRID - Cluster Pending by Pending Reason',
		'name' => 'cacti_graph_template_grid_-_cluster_pending_by_pending_reason.xml'
	),
    "7" => array (
        'value' => 'GRID - LSF Host Info Requests',
        'name' => 'cacti_graph_template_grid_-_lsf_host_info_requests.xml'
    ),
    "8" => array (
        'value' => 'GRID - LSF Host Match Criteria',
        'name' => 'cacti_graph_template_grid_-_lsf_host_match_criteria.xml'
    ),
    "9" => array (
        'value' => 'GRID - LSF Job Buckets',
        'name' => 'cacti_graph_template_grid_-_lsf_job_buckets.xml'
    ),
    "10" => array (
        'value' => 'GRID - LSF Job Info Requests',
        'name' => 'cacti_graph_template_grid_-_lsf_job_info_requests.xml'
    ),
    "11" => array (
        'value' => 'GRID - LSF Job Scheduling Interval',
        'name' => 'cacti_graph_template_grid_-_lsf_job_scheduling_interval.xml'
    ),
    "12" => array (
        'value' => 'GRID - LSF Jobs Completed',
        'name' => 'cacti_graph_template_grid_-_lsf_jobs_completed.xml'
    ),
    "13" => array (
        'value' => 'GRID - LSF Jobs Dispatched',
        'name' => 'cacti_graph_template_grid_-_lsf_jobs_dispatched.xml'
    ),
    "14" => array (
        'value' => 'GRID - LSF Jobs Submitted',
        'name' => 'cacti_graph_template_grid_-_lsf_jobs_submitted.xml'
    ),
    "15" => array (
        'value' => 'GRID - LSF Job Submit Requests',
        'name' => 'cacti_graph_template_grid_-_lsf_job_submit_requests.xml'
    ),
    "16" => array (
        'value' => 'GRID - LSF MBatchD Requests',
        'name' => 'cacti_graph_template_grid_-_lsf_mbatchd_requests.xml'
    ),
    "17" => array (
        'value' => 'GRID - LSF MBD File Descriptor Usage',
        'name' => 'cacti_graph_template_grid_-_lsf_mbd_file_descriptor_usage.xml'
    ),
    "18" => array (
        'value' => 'GRID - LSF Queue Info Requests',
        'name' => 'cacti_graph_template_grid_-_lsf_queue_info_requests.xml'
    ),
    "19" => array (
        'value' => 'GRID - Cluster Effective Utilization',
        'name' => 'cacti_graph_template_grid_-_cluster_effective_utilization.xml'
    ),
    "20" => array (
        'value' => 'GRID - Cluster/Host Effective UT',
        'name' => 'cacti_graph_template_grid_-_clusterhost_effective_ut.xml'
    ),
	'21' => array (
		'value' => 'ELIM - gpfs_bandwidth',
		'name' => 'cacti_elim_template_gpfs_bandwidth.xml'
	),
	'22' => array (
		'value' => 'ELIM - gpfs_file_operations',
		'name' => 'cacti_elim_template_gpfs_file_operations.xml'
	),
    '23' => array (
        'value' => '2 GPUs Memory Utilization',
        'name' => 'cacti_elim_template_2_gpus_memory_usage.xml'
    ),
    '24' => array (
        'value' => '4 GPUs Utilization',
        'name' => 'cacti_elim_template_4_gpus_utilization.xml'
    ),
    '25' => array (
        'value' => 'Shared GPU Memory Utilization',
        'name' => 'cacti_elim_template_shared_gpu_memory_utilization.xml'
    ),
    '26' => array (
        'value' => 'Shared GPU Utilization',
        'name' => 'cacti_elim_template_shared_gpu_utilization.xml'
    )
);
//Data Query include a extra graph: GRID - Host Group - Effective Utilization

foreach($grid_templates as $grid_template){
	echo 'Importing ' . $grid_template['value'] . ".\n";
	$results = rtm_do_import($config['base_path'] . '/plugins/grid/templates/' . $grid_template['name']);
}

echo "Grid Templates importing complete.\n";

function display_version() {
    include_once(dirname(__FILE__) . '/setup.php');

	print 'IBM Spectrum LSF RTM Import Template Utility ' . get_grid_version() . "\n";
	print rtm_copyright();
}
