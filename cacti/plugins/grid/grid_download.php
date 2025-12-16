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

chdir('../../');
include('./include/auth.php');
include_once('./lib/rrd.php');

/* set default download type */
if (!isset_request_var('dtype')) {
	if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_DEBUG) {
		cacti_log("DEBUG: Download file type is not specified, set default value 'grid_backup_file' to it");
	}
	set_request_var('dtype', 'grid_backup_file');
}

if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_DEBUG) {
	cacti_log("DEBUG: Download file type is: '" . get_request_var('dtype') . "'; Download filename is: '" . get_request_var('dfilename') . "'");
}

/* check download file name */
if (!isset_request_var('dfilename')) {
	exit;
}

input_validate_input_regex_xss_attack(get_request_var('dfilename'));
$download_file_name=get_request_var('dfilename');

switch (get_request_var('dtype')) {
	case 'grid_backup_file':
		include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
		$grid_backup_path = read_config_option('grid_backup_path');
		if(!isset($grid_backup_path)){
			cacti_log('WARNING: Database backup location is not defined');
			exit;
		}
		$grid_backup_file = $grid_backup_path . '/' . $download_file_name;
		if (!is_file($grid_backup_file)) {
			cacti_log("WARNING: specified database backup file '$grid_backup_file' does not exist.");
			exit;
		}

		$size = filesize($grid_backup_file);
		$fp   = fopen($grid_backup_file, 'rb');

		input_validate_input_regex_xss_attack(fread($fp,$size));

		header('Cache-Control: public');
		header('Content-type: application/x-gzip');
		header('Cache-Control: max-age=15');
		header('Content-Disposition: attachment; filename=' . $download_file_name);
		header('Accept-Ranges: bytes');
		if (isset($_SERVER['HTTP_RANGE'])) {
			list($a, $range) = explode('=', $_SERVER['HTTP_RANGE']);
			str_replace($range, '-', $range);
		}
		if (!isset($range)) $range = 0;

		fseek($fp,$range);
		while(!feof($fp)){
			set_time_limit(0);
			print(fread($fp,1024*8));
		}
		fclose($fp);
		exit;
		break;
	default:
		break;
}

