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
include(dirname(__FILE__) . '/lib/grid_api_archive.php');
include_once($config['base_path'] . '/lib/rtm_functions.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$debug = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-v':
	case '-V':
	case '--version':
	case '-h':
	case '--help':
		display_version();
		exit;
	}
}
/* this needs lot's of memory */
ini_set('memory_limit', '-1');

$last_archive_time = read_config_option('grid_archive_lastrun');
$now               = time();
$frequency         = read_config_option('grid_archive_frequency');

if ((!isset($last_archive_time)) ||
	(($now - $last_archive_time) > $frequency)) {
	if ((read_config_option('grid_system_collection_enabled') == 'on') &&
		(read_config_option('grid_collection_enabled') == 'on')) {
		if (detect_and_correct_running_processes(0, 'GRIDARCHIVE', $frequency*3)) {
			/* archive records */
			grid_perform_archive($now);

			/* log the archive time */
			db_execute("REPLACE INTO settings SET name='grid_archive_lastrun', value='$now'");

			/* remove the process entry */
			remove_process_entry(0, 'GRIDARCHIVE');
		}
	}
}

function display_version () {
	include_once(dirname(__FILE__) . '/setup.php');

	print 'IBM Spectrum LSF RTM Data Archive Poller ' . get_grid_version() . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
}
