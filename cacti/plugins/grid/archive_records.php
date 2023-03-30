#!/usr/bin/php -q
<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2023                                          |
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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_api_archive.php');

/* get archive connection information */
$arch_db_type     = read_config_option('grid_archive_db_type');
$arch_db_default  = read_config_option('grid_archive_name');
$arch_db_hostname = read_config_option('grid_archive_host');
$arch_db_username = read_config_option('grid_archive_user');
$arch_db_password = read_config_option('grid_archive_password');
$arch_db_port     = read_config_option('grid_archive_port');
$arch_db_ssl      = read_config_option('grid_archive_ssl');
$conn_id          = -1;

$debug            = 1;

/* this needs lot's of memory */
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$debug          = false;
$forcerun       = false;
$forcerun_maint = false;
$now            = time();

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-d':
		$debug = true;
		break;
	case '-da':
		$archive_start_time = strtotime($value);
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
		exit;
	}
}

if (strlen($arch_db_port)) {
	$conn_id = db_connect_real($arch_db_hostname, $arch_db_username, $arch_db_password,
		$arch_db_default, $arch_db_type, $arch_db_port, 20, $arch_db_ssl);
} else {
	$conn_id = db_connect_real($arch_db_hostname, $arch_db_username, $arch_db_password,
		$arch_db_default, $arch_db_type, 3306, 20, $arch_db_ssl);
}

if (is_object($conn_id)) {
	/* transfer minor tables */
	$cluster_rows     = grid_archive_refresh_records($conn_id, 'grid_clusters');
	$hostgroup_rows   = grid_archive_refresh_records($conn_id, 'grid_hostgroups');
	$hostinfo_rows    = grid_archive_refresh_records($conn_id, 'grid_hostinfo');
	$host_sres_rows   = grid_archive_refresh_records($conn_id, 'grid_hostresources');
	$bhosts_rows      = grid_archive_refresh_records($conn_id, 'grid_hosts');
	$host_jtraf_rows  = grid_archive_refresh_records($conn_id, 'grid_hosts_jobtraffic');
	$host_dres_rows   = grid_archive_refresh_records($conn_id, 'grid_hosts_resources');
	$poller_rows      = grid_archive_refresh_records($conn_id, 'grid_pollers');
	$cluster_res_rows = grid_archive_refresh_records($conn_id, 'grid_resources');

	/* transfer the grid_jobs table */
	$job_rows         = grid_archive_refresh_update_type_records($conn_id, $now, 'grid_jobs_finished', $archive_start_time);
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'RTM Database Record Archiver ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage:\n";
	print "archive_records.php [-d] [-da][-h] [--help] [-v] [-V] [--version]\n\n";
	print "-d                        - Display verbose output during execution\n";
	print "-da='-n Hours/Days/Weeks' - Specify a start time in the past using the minus sign.\n";
	print "                            For example '-2 Days'\n";
	print "-v -V --version           - Display this help message\n";
	print "-h --help                 - display this help message\n";
}

