<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2024                                          |
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

/* display NO errors */
error_reporting(0);

/* since we'll have additional headers, tell php when to flush them */
ob_start();

$guest_account = true;

chdir('../../');
include('./include/auth.php');
include_once($config['library_path'] . '/rrd.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

if (isset_request_var('cluster_tz') && (get_request_var('cluster_tz') == 'on' || get_request_var('cluster_tz') == 'true')) {
	$cluster_tz = db_fetch_cell('SELECT cluster_timezone FROM grid_clusters WHERE clusterid=' . get_request_var('clusterid'));
	if ($cluster_tz) {
		db_execute("SET SESSION time_zone='$cluster_tz'");
		date_default_timezone_set($cluster_tz);
		putenv("TZ=$cluster_tz");
		cacti_log("Change RRDTOOLS Graph Timezone to $cluster_tz");
	} else {
		db_execute("SET SESSION time_zone='SYSTEM'");
	}
} else {
	db_execute("SET SESSION time_zone='SYSTEM'");
}

/* open a pipe to rrdtool for writing */
$rrdtool_pipe = rrd_init();

/* let the script run for upto five minutes */
ini_set('max_execution_time', '300');

$clusterid  = get_request_var('clusterid');
$jobid      = get_request_var('jobid');
$indexid    = get_request_var('indexid');
$submit_time= get_request_var('submit_time');
$data_class = get_request_var('data_class');
$graph_type = get_request_var('graph_type');
$date1      = get_request_var('date1');
$date2      = get_request_var('date2');
$legend     = get_request_var('legend');
if (isempty_request_var('host')) {
	$host_name = null;
} else {
	$host_name = get_request_var('host');
}
if (!isset_request_var('gpu_id') || get_request_var('gpu_id') == '') {
	$gpu_id = null;
} else {
	$gpu_id = get_request_var('gpu_id');
}

$cache_directory = read_config_option('grid_cache_dir');
$filebase        = $jobid . "_" .  $indexid . "_" .  $clusterid . "_" .  $submit_time . "_" .  $data_class;
if($host_name != null){
	$filebase	.= "_" . $host_name;
}
if($gpu_id !== null){
	$filebase	.= "_" . $gpu_id;
}
$pngfile         = $cache_directory . '/' . $filebase . '_' . $graph_type . '.png';

$jobs = db_fetch_row_prepared('SELECT *
	FROM grid_jobs
	WHERE clusterid = ?
	AND jobid= ?
	AND indexid = ?
	AND submit_time= ?',
	array(
		get_request_var('clusterid'),
		get_request_var('jobid'),
		get_request_var('indexid'),
		date('Y-m-d H:i:s', get_request_var('submit_time'))
	));

if (cacti_sizeof($jobs)) {
	$table_name   = 'grid_jobs';
} else {
	$table_name   = 'grid_jobs_finished';
	// option.grid_archive_rrd_location had been obsoleted. archive_path will be a blank. rra directory will keep option.grid_cache_dir
	$archive_path = find_archive_jobgraph_rrdfile($clusterid, $jobid, $indexid, $submit_time, $data_class, $graph_type, $host_name);

	if (strlen($archive_path)) {
		$cache_directory = dirname($archive_path);
	}
}

if (strlen($cache_directory)) {
	if (is_dir($cache_directory)) {
		if ($gpu_id !== null) {
			$recreate_png = update_job_rrds_gpu($table_name, $cache_directory, $filebase,
												$clusterid, $jobid, $indexid, $submit_time,
												$data_class, $rrdtool_pipe, $host_name, $gpu_id);
		} else if ($host_name != null) {
			$recreate_png = update_job_rrds_host($table_name, $cache_directory, $filebase,
												$clusterid, $jobid, $indexid, $submit_time,
												$data_class, $rrdtool_pipe, $host_name);
		} else {
			$recreate_png = update_job_rrds($table_name, $cache_directory, $filebase,
											$clusterid, $jobid, $indexid, $submit_time,
											$data_class, $rrdtool_pipe);
		}
	} else {
		cacti_log("ERROR: Can not generate Graphs, Grid Cache Directory '" . $cache_directory . "' does not exist");
	}
} else {
	cacti_log('ERROR: Can not generate Graphs, Grid Cache Directory not set.');
}

header('Content-type: image/png');

/* flush the headers now */
ob_end_flush();

session_write_close();

/* rrd_tool pipe is closed normally in this function */
$job_update_interval = db_fetch_cell_prepared('SELECT job_minor_timing
	FROM grid_clusters
	WHERE clusterid = ?',
	array($clusterid));

if (is_numeric($job_update_interval)) {
	create_job_graph($filebase, $clusterid, $jobid, $indexid, $submit_time, $data_class, $graph_type, $date1, $date2, $legend,
		$rrdtool_pipe, $cache_directory, $host_name, $gpu_id);

	usleep(50000);
} else {
	exit;
}

if (file_exists($pngfile)) {
	$filesize = filesize($pngfile);
	$filetype = exif_imagetype($pngfile);
	if ($filesize > 0 && $filetype > 0 && $filetype < 19){
		$file = fopen($pngfile, 'rb');
		$data = fread($file, $filesize);
		fclose($file);
		input_validate_input_regex_xss_attack($data);
		print $data;
	}else{
		cacti_log('WARNING: Invalid image file: '. $pngfile);
	}
}
