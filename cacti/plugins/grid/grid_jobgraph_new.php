<?php
// $Id$
/*
 +-------------------------------------------------------------------------+
 | Copyright IBM Corp. 2006, 2021                                          |
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
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib//rrd.php');

/* open a pipe to rrdtool for writing */
$rrdtool_pipe = rrd_init();

$debug = 0;
/* make some calculations, this is overkill */
if (!$debug) {
	/* ================= input validation ================= */
	get_filter_request_var('clusterid');
	get_filter_request_var('indexid');
	get_filter_request_var('jobid');
	get_filter_request_var('submit_time');
	/* ==================================================== */

	/* clean up search string */
	if (isset_request_var('data_class')) {
		set_request_var('data_class', sanitize_search_string(get_request_var('data_class')));
	}

	/* clean up sort_column */
	if (isset_request_var('graph_type')) {
		set_request_var('graph_type', sanitize_search_string(get_request_var('graph_type')));
	}

	$clusterid  = get_request_var('clusterid');
	$indexid    = get_request_var('indexid');
	$jobid      = get_request_var('jobid');
	$submit_time= get_request_var('submit_time');
	$data_class = get_request_var('data_class');
	$graph_type = get_request_var('graph_type');
} else {
	/* testing */
	$_SESSION['sess_user_id'] = 1;
	$clusterid  = '2';
	$indexid    = '0';
	$jobid      = '351791';
	$submit_time= strtotime('2006-12-17 14:23:29');
	$graph_type = 'memory';
	$data_class = 'absolute';
}

$cache_directory = read_config_option('grid_cache_dir');
$filebase        = $jobid . '_' . $indexid . '_' . $submit_time . '_' . $data_class;
$pngfile         = $cache_directory . '/' . $filebase . '_' . $graph_type . '.png';

/* update the rrd file and create the png file */
update_job_rrd_and_graph($pngfile, $rrdtool_pipe, $clusterid, $indexid, $jobid,
	$submit_time, $graph_type, $data_class);

header('Content-type: image/png');

/* flush the headers now */
ob_end_flush();

session_write_close();

if (file_exists($pngfile)) {
	$file = fopen($pngfile, 'rb');
	$data = fread($file, filesize($pngfile));
	fclose($file);
	input_validate_input_regex_xss_attack($data);
	print $data;
	exit;
}

