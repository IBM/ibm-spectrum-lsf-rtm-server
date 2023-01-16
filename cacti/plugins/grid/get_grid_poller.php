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
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

// lsf_version
if (empty($parms)) {
    echo 'LSF version not provided: 9.1 10.1 10.1.0.7 10.1.0.13' . "\n";
    exit(1);
}

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}
    switch ($arg) {
        case "-h":
        case "-v":
        case "-V":
        case "--version":
        case "--help":
            display_version();
            exit;
    }
}

if (!preg_match("/^(91|1010|1017|10010013|9.1|10.1|10.1.0.7|10.1.0.13)$/", $parms[0])) {
    echo 'LSF version invalid: 9.1 10.1 10.1.0.7 10.1.0.13' . "\n";
    exit(1);
}

if(isset($rtmvermap[$parms[0]])){
    $lsf_version = $rtmvermap[$parms[0]];
} else {
    $lsf_version = $parms[0];
}
$poller_lbindir = realpath($rtm["lsf" . $lsf_version]["RTM_POLLERBINDIR"]) . "/";
$poller_desc = $rtm["lsf" . $lsf_version]["DESC"];

$poller_id_dirs = db_fetch_assoc_prepared("select poller_id, poller_lbindir from grid_pollers where lsf_version=?", array($lsf_version));

$found=false;
if (cacti_sizeof($poller_id_dirs)) {
	foreach($poller_id_dirs as $poller_id_dir) {
		$poller_dir_real=realpath($poller_id_dir["poller_lbindir"]) . "/";
		if ( $poller_dir_real == $poller_lbindir ) {//symbolic link case. for the $poller_lbindir is absolute path.
			$found=true;
			break;
		}
	}
}
if ($found == true ) {
    print $poller_id_dir["poller_id"];
}
else {
    db_execute_prepared("INSERT INTO grid_pollers (poller_name, poller_lbindir, " .
                 "poller_location, poller_support_info, lsf_version) " .
                 "VALUES (?,?,'','',?)", array($poller_desc, $poller_lbindir, $lsf_version));

    print db_fetch_insert_id();

}

if (strtolower(read_config_option('grid_os', TRUE)) == 'on'){
	db_execute('UPDATE settings set value="OFF" WHERE name="grid_os"');
}

function display_version() {
    $version = read_config_option('grid_version');
    print "RTM LSF Poller Utility, Version $version, " . read_config_option('grid_copyright_year') . "\n";
}
