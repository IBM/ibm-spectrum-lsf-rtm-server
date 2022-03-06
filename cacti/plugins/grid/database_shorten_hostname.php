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
include_once($config['library_path'] . '/rtm_functions.php');
include_once(dirname(__FILE__) . '/lib/grid_functions.php');

global $config, $debug;

$debug = false;
$force   = false;

/* need to capture signals from users */
function sig_handler($signo) {

	switch ($signo) {
		case SIGTERM:
		case SIGINT:
		case SIGABRT:
		case SIGQUIT:
		case SIGSEGV:
			cacti_log('SHORTEN_HOSTNAME - WARNING: database_shorten_hostname.php is terminated.', true);
			remove_process_entry('0', 'SHORTEN_HOSTNAME');
			exit;
			break;
		default:
			break;
	}
}

if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
	pcntl_signal(SIGABRT, 'sig_handler');
	pcntl_signal(SIGQUIT, 'sig_handler');
	pcntl_signal(SIGSEGV, 'sig_handler');
}

/* process calling arguments */
$parms = $_SERVER['argv'];
if (cacti_sizeof($parms) < 2) {
	display_help();
	exit;
}
array_shift($parms);

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
		case '-h':
		case '-v':
		case '-V':
		case '--version':
		case '--help':
			display_help();
			exit;
		case '-f':
		case '--force':
			$force = true;
			break;
		case '-d':
		case '--debug':
			$debug = true;
			break;
		default:
			print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
			display_help();
			exit;
	}
}

/* set execution params */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

if (detect_and_correct_running_processes('0', 'SHORTEN_HOSTNAME', 99999999) == true ) {
	set_config_option('grid_short_hostname', 'on');

	database_shorten_hostname();
	remove_process_entry('0', 'SHORTEN_HOSTNAME');

	print "SUCCESS: Execute SQL, Set short hostname flag, Ok\n";
}

function get_domains_from_table_column($table, $column, &$domains, &$domains_str) {
	$result = db_fetch_assoc("SELECT $column FROM $table WHERE $column LIKE '%%.%%';");
	if (cacti_sizeof($result)) {
		foreach($result as $index => $arr) {
				//print "$table: ". $arr["$column"]. "\n";
				$at = strpos($arr["$column"], '.');
				if ($at !== false) {
					$domain = substr($arr["$column"],$at);
					if (!in_array($domain, $domains)) {
						$domains[] = $domain;
						$domains_str .= "$domain ";
					}
				}
		}
	}
	//print "$table: $domains_str\n";
	//print_r($domains);
}

function substr_index_table($tables) {
	//print_r($tables);
	if (cacti_sizeof($tables)) {
		foreach($tables as $table => $columns) {
			foreach($columns as $column) {
				execute_sql("shorten $table $column", "UPDATE IGNORE $table SET $column=SUBSTRING_INDEX($column, '.', 1)");
			}
		}
	}
}

function substr_index_table_partitions($table_name, $columns, $partition_id='') {
	$sql_params = array();
	$sql_where= " WHERE table_name=?";
	$sql_params[] = $table_name;
	if (!empty($partition_id)) {
		$sql_where.= " AND partition=?";
		$sql_params[] = $partition_id;
	}

	$tables = db_fetch_assoc_prepared("SELECT CONCAT(table_name,'_v',`partition`) AS table_names
		FROM grid_table_partitions
		$sql_where
		ORDER BY `partition`", $sql_params);

	if (cacti_sizeof($tables)) {
		foreach($tables as $table) {
			foreach($columns as $column) {
				execute_sql('shorten partition ' . $table['table_names'] . ' $column', 'UPDATE IGNORE ' . $table['table_names'] . " SET $column=SUBSTRING_INDEX($column, '.', 1)");
			}
		}
	}
}

function database_shorten_hostname() {
	global $config;
	global $rtm;

	//1, get domain names.
	$domains = array();
	$domains_str = '';
	get_domains_from_table_column('grid_hosts', 'host', $domains, $domains_str);
	get_domains_from_table_column('grid_hostinfo', 'host', $domains, $domains_str);
	get_domains_from_table_column('grid_load', 'host', $domains, $domains_str);
	execute_sql('backup domain names', "REPLACE INTO `settings` VALUES('domains4shorten_hostname', '$domains_str');");
	/* add a heartbeat for optimization */
	make_heartbeat(0, 'SHORTEN_HOSTNAME');
	grid_debug('Get domains from grid_hosts/grid_hostinfo/grid_load complete.');

	//2, shorten cacti tables.
	$disku_fs_usage_snmp_query_id = db_fetch_cell("SELECT id FROM snmp_query WHERE hash='ad574050ad411342d40035133cdef860';");
	if (cacti_sizeof($domains)) {
		foreach($domains as $domain) {
			execute_sql("shorten in host_snmp_cache with $domain ", "UPDATE IGNORE host_snmp_cache SET field_value=REPLACE(field_value, '$domain', '') WHERE snmp_query_id<>'$disku_fs_usage_snmp_query_id' AND field_value LIKE '%$domain%';");
			execute_sql("shorten in host with $domain ", "UPDATE IGNORE host SET hostname=REPLACE(hostname, '$domain', '') WHERE hostname LIKE '%$domain%';");
			execute_sql("shorten in data_input_data with $domain ", "UPDATE IGNORE data_input_data SET value=REPLACE(value, '$domain', '') WHERE value LIKE '%$domain%';");
			execute_sql("shorten in data_template_data with $domain ", "UPDATE IGNORE data_template_data SET name_cache=REPLACE(name_cache, '$domain', '') WHERE name_cache LIKE '%$domain%';");
			execute_sql("shorten in graph_templates_graph with $domain ", "UPDATE IGNORE graph_templates_graph SET title_cache=REPLACE(title_cache, '$domain', '') WHERE title_cache LIKE '%$domain%';");
			execute_sql("shorten in poller_item with $domain ", "UPDATE IGNORE poller_item SET arg1=REPLACE(arg1, '$domain', '') WHERE arg1 LIKE '% %$domain%';");
		}
	}
	//execute_sql("delete backup domain names", "DELETE FROM `settings` WHERE name='domains4shorten_hostname'");
	/* add a heartbeat for optimization */
	make_heartbeat(0, 'SHORTEN_HOSTNAME');
	grid_debug('Shorten hostname for cacti tables complete.');

	//3, shorten RTM key tables.
	$tables = array(
		'grid_load' => array('host'),
		'grid_hostinfo' => array('host'),
		'grid_hosts' => array('host'),
		'grid_jobs' => array('from_host', 'exec_host'),
		'grid_jobs_jobhosts' => array('exec_host'),
		'grid_jobs_reqhosts' => array('host'),
		'grid_queues_hosts' => array('host'),
		'grid_summary' => array('host'),
		'grid_summary_timeinstate' => array('host'),
		'grid_jobs_exec_hosts' => array('exec_host'),
		'grid_host_threshold' => array('hostname'),
		'grid_hosts_resources' => array('host'),
		'grid_hosts_jobtraffic' => array('host'),
		'grid_hostresources' => array('host'),
		'grid_hostgroups' => array('host'),
		'grid_guarantee_pool_hosts' => array('host'),
		'grid_clusters' => array('lsf_master'),

		'grid_jobs_finished' => array('from_host', 'exec_host'),
		'grid_jobs_jobhosts_finished' => array('exec_host'),
		'grid_jobs_reqhosts_finished' => array('host'),
		'grid_jobs_host_rusage' => array('host'),

		'lic_services_feature_details' => array('hostname'),

		'grid_blstat_tasks' => array('host'),
		'grid_blstat_users' => array('host'),
		'grid_jobs_gpu_rusage' => array('host'),

	);
	substr_index_table($tables);
	/*disku plugin and bianry are not installed by default.*/
	$is_dbexsit=db_fetch_cell("SHOW TABLES LIKE 'disku_pollers'");
	if (!empty($is_dbexsit)) {
		$tables = array(
			'disku_pollers' => array('hostname'),
		);
		substr_index_table($tables);
	}
	/* check grid_queues_distrib, new RTM which not maintened yet have not this table. */
	$is_dbexsit=db_fetch_cell("SHOW TABLES LIKE 'grid_queues_distrib'");
	if (!empty($is_dbexsit)) {
		$tables = array(
			'grid_queues_distrib' => array('host'),
		);
		substr_index_table($tables);
	}
	execute_sql('Shorten hostname for key tables complete', "REPLACE INTO settings (name,value) VALUES ('shorten_hostname_key', '1');");
	grid_debug('Shorten hostname for key tables complete.');

	//4, shorten RTM other tables.
	$tables = array(
		'grid_job_daily_stats' => array('from_host', 'exec_host'),
		'lic_interval_stats' => array('host'),
		'lic_daily_stats_traffic' => array('host'),
		'lic_daily_stats' => array('host'),
	);
	substr_index_table($tables);
	/* add a heartbeat for optimization */
	make_heartbeat(0, 'SHORTEN_HOSTNAME');
	grid_debug('Shorten hostname for other tables complete.');

	//5, shorten RTM partition tables.
	substr_index_table_partitions('grid_jobs_finished', array('from_host', 'exec_host'));
	substr_index_table_partitions('grid_jobs_jobhosts_finished', array('exec_host'));
	substr_index_table_partitions('grid_jobs_reqhosts_finished', array('host'));
	/* add a heartbeat for optimization */
	make_heartbeat(0, 'SHORTEN_HOSTNAME');
	grid_debug('Shorten hostname for job partition tables complete.');

	substr_index_table_partitions('grid_job_daily_stats', array('from_host', 'exec_host'));
	substr_index_table_partitions('grid_jobs_host_rusage', array('host'));
	/* add a heartbeat for optimization */
	make_heartbeat(0, 'SHORTEN_HOSTNAME');
	grid_debug('Shorten hostname for job daily partition tables complete.');

	substr_index_table_partitions('lic_daily_stats', array('host'));
	grid_debug('Shorten hostname for license daily partition tables complete.');
	substr_index_table_partitions('grid_jobs_gpu_rusage', array('host'));
	grid_debug('Shorten hostname for GPU partition tables complete.');
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;

	print 'IBM Spectrum LSF RTM Background Database Optimization Script ' . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8'). ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";

	print "Usage: Don't run this file directly. Please run database_shorten_hostname.sh under util directory for safety.\n";
	print "database_shorten_hostname.php [-d | --debug] -f | --force\n\n";
	print "-d | --debug          - Log verbose information to standard output\n";
	print "-f | --force          - Force to shorten hostname in tables\n";
	print "-v | -V | --version   - Display this help message\n";
	print "-h | --help           - display this help message\n\n";
}

