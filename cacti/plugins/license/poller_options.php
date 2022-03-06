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

include(dirname(__FILE__) . "/../../include/cli_check.php");

include_once($config["library_path"] . '/rtm_functions.php');
include_once(dirname(__FILE__) . '/include/lic_functions.php');

/* take the start time to log performance data */
list($micro,$seconds) = preg_split('/ /', microtime());
$start = $seconds + $micro;

/* get the lic polling cycle */
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

/* get the start time*/
list($micro,$seconds) = preg_split("/ /", microtime());
$current_date_time = round($seconds + $micro);

/** process callling arguments*/
$parms = $_SERVER['argv'];
array_shift($parms);

global $config, $debug ;

$debug    = false;
$forcerun = false;
$lic_id   = 0;
$from_gui = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode('=', $parameter);

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = true;
		break;
	case '-f':
	case '--force':
		$forcerun = true;
		break;
	case '--id':
		$lic_id = $value;
		break;
	case '--gui':
		$from_gui = true;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . '\n\n';
		display_help();
		exit;
	}
}

if ($lic_id > 0) {
	$options = db_fetch_assoc_prepared("SELECT service_id, server_name, server_portatserver, options_path FROM lic_services
		WHERE status='3' AND disabled='' AND options_path!='' AND lic_services.service_id=?", array($lic_id));
}else{
	$options = db_fetch_assoc("SELECT service_id, server_name, server_portatserver, options_path FROM lic_services
		WHERE status='3' AND disabled='' AND options_path!=''");
}


if (!cacti_sizeof($options)) {
	lic_debug('NO options file found, exit.');
	exit;
}

if (read_config_option('grid_collection_enabled') != 'on') {
	lic_debug('DB schema upgrade in process. License option poller exit.');
	exit;
}

$if_collect_option = read_config_option('enable_option_in_file_collection');
if (empty($if_collect_option)) {
	$if_collect_option = 'off';
}


$start      = time();
$last_start = read_config_option('lic_options_processing');
if (empty($last_start)) {
	$last_start = $start;
}

/* determine if it's time to start */
$runme = false;
db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('lic_options_processing',?)", array($start));
if ($start % 43200 <= $last_start % 43200 || $last_start == $start) {
	$runme = true;
}


lic_debug('About to enter License Options File Poller Processing ');

/* update the options files.  This will happen twice a day by default */
if ($runme || $forcerun) {
	if ($forcerun) {
		remove_process_entry(0, 'LICFLEXOPTIONS');
	}

	if (detect_and_correct_running_processes(0, 'LICFLEXOPTIONS', 3600)){
		lic_debug('About to enter interval stats updating process');

		list($micro,$seconds) = preg_split('/ /', microtime());
		$start = $seconds + $micro;

		db_execute("UPDATE lic_ldap_to_flex_groups SET present=0");

		if ($lic_id > 0) {
			lic_process_options($current_date_time, $lic_id);
		}else{
			lic_process_options($current_date_time);

			log_ip_ranges();
		}

		db_execute("DELETE FROM lic_ldap_to_flex_groups WHERE present=0");

		remove_process_entry(0, 'LICFLEXOPTIONS');

		log_lic_statistics($lic_id, $from_gui);
	}
}

function lic_process_options($cur_time, $lic_id = 0) {
	global $if_collect_option, $debug;

	$use_ssh = read_config_option('lic_use_ssh_for_options');

	if ($lic_id > 0) {
		$options = db_fetch_assoc_prepared("SELECT service_id, server_name, server_portatserver, options_path
			FROM lic_services
			WHERE status='3' AND disabled='' AND options_path!='' AND lic_services.service_id=$lic_id", array($lic_id));

	}else{
		$options = db_fetch_assoc("SELECT service_id, server_name, server_portatserver, options_path
			FROM lic_services
			WHERE status='3' AND disabled='' AND options_path!=''");
	}

	if (cacti_sizeof($options)) {
	foreach($options as $lmgrd) {
		lic_debug('Processing Service ID: ' . $lmgrd['service_id']);
		$options_file = $lmgrd['options_path'];
		$options_files = explode(';', $options_file);

		$valid_options_file = true;
		foreach($options_files as $options_file) {
			$options_file = trim ($options_file);
			if (empty($options_file)) continue;

			lic_debug("Validating Options File '$options_file'");
			if (file_exists($options_file)) {  // for local options file
				if (!is_readable($options_file)) {
					cacti_log("ERROR: '" .$lmgrd["server_name"] ."' - Option File: $options_file is not readable!");
					$valid_options_file = false;
					continue;
				}
				if (!filesize($options_file)) {
					cacti_log("ERROR: '" .$lmgrd["server_name"] ."' - Option File: $options_file is empty!");
					$valid_options_file = false;
					continue;
				}
			} elseif ($use_ssh) { // for remote options file via ssh
				if ( !cacti_sizeof(get_options_file_ssh($options_file,$lmgrd['server_portatserver'])) ) {
					cacti_log("ERROR: '" .$lmgrd["server_name"] ."' - Option File: $options_file doesn't exist!");
					$valid_options_file = false;
					continue;
				}
			} else {
				cacti_log(sprintf('WARNING: Options File:\'%s\' for Service:\'%s\' does not exist locally and ssh is not enabled', $options_file, $lmgrd['server_portatserver']), false, 'LICENSE');
			}
		}
		if (!$valid_options_file) continue;

		/* set present flags to 0 */
		db_execute_prepared("UPDATE lic_services_options_feature SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_feature_type SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_incexcl_all SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_global SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_user_groups SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_host_groups SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_max SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));

		db_execute_prepared("UPDATE lic_services_options_reserve SET present=0
			WHERE service_id=?", array($lmgrd['service_id']));


		// Try to first read the file directly.  Otherwise, use ssh
		$i = $j = 1;
		foreach($options_files as $options_file) {
			$options_file = trim ($options_file);
			if (empty($options_file)) continue;

			lic_debug("Processing Options File '$options_file', Number $i");
			$option_file_contents = array();
			if (file_exists($options_file) && is_readable($options_file)) {
				$option_file_contents = file($options_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				$i++;
			} elseif ($use_ssh) {
				$option_file_contents = get_options_file_ssh($options_file,$lmgrd['server_portatserver']);
				$i++;
			}

			if (cacti_sizeof($option_file_contents)) {
				lic_load_options_file($lmgrd, $options_file, $option_file_contents);
			}

			//deal with option in file part only when 'enable_option_in_file_collection' setting is 'on'  (designed for Qcom)
			if ($if_collect_option == "on") {
				/* modification to collect custom PER_USER Options, need to change it to a hook function */
				$infile = $options_file . ".in";

				if (file_exists($infile) && is_readable($infile)) {
					lic_debug("Processing Options Input File '$infile', Number $j");
					$infile_contents = file($infile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
					$j++;
				} elseif ($use_ssh) {
					//lic_debug("Falling back to ssh to get license input options path '" . $infile . "'");
					// Need to fallback to ssh
					$infile_contents = array();
				}

				if (cacti_sizeof($infile_contents)) {
					lic_load_options_in_file($lmgrd, $infile_contents);
				}
			}
		}

		/* delete where present flags set to 0 */
		db_execute_prepared("DELETE FROM lic_services_options_feature
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_feature_type
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_incexcl_all
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_global
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_user_groups
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_host_groups
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_max
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

		db_execute_prepared("DELETE FROM lic_services_options_reserve
			WHERE service_id=? AND present=0", array($lmgrd['service_id']));

	}
	}
}

function lic_load_options_file($lmgrd, $options_file, &$file) {
	global $cnn_id;
	global $if_collect_option;

	$max_array = array();
	$ugp_array = array();
	$rsv_array = array();
	$hgp_array = array();
	$inx_array = array();
	$ft_array  = array();
	$ftt_array = array();

	$max_cnt   = 0;
	$ugp_cnt   = 0;
	$rsv_cnt   = 0;
	$hgp_cnt   = 0;
	$inx_cnt   = 0;
	$ftt_cnt   = 0;

	$debug_path   = '';
	$report_path  = '';
	$nolog_in     = 'NULL';
	$nolog_out    = 'NULL';
	$nolog_queued = 'NULL';
	$nolog_denied = 'NULL';
	$groupcaseins = 'NULL';
	$timeoutall   = 'NULL';
	$continue     = false;
	$comment      = '';
	$commenttag   = read_config_option('lic_options_note_tag');

	foreach($file as $line) {
		// Process comments first
		$line = trim($line);
		if ($if_collect_option == "on") {
			if (strpos($line, $commenttag) !== false) {
				$ncomment = trim($line, '# ');

				if ($comment != '') {
					if ($comment == $ncomment) {
						$comment = '';
					}else{
						$comment = $ncomment;
					}
				}else{
					$comment = $ncomment;
				}

				continue;
			}
		}elseif ($line == '' || $line[0] == '#' ) {
			continue;
		}

		$parts  = preg_split("/[\s]+/", $line);
		for ($i=0; $i < cacti_sizeof($parts); $i++) {
			$parts[$i] = str_replace(array("\'", '\"', '"', "'"), array("\\\'", '\\"', '\"', "\'"), $parts[$i]);
		}

		$option = $parts[0];

		if ($continue) {
			$option   = $prev;
			$continue = false;
			$parts    = preg_split("/[\s]+/", $prev . ' ' . $pgroup . ' ' . $line);
		}

		switch($option) {
		case 'BORROW_LOWWATER':
			// set the number of licenses that can not be borrowed
			$value    = trim($parts[2]);

			$ft_array[$parts[1]]['borrow_lowwater'] = $value;
			break;
		case 'DEBUGLOG':
			// debug log location
			$debug_path = trim($parts[1],' +\t');
			break;
		case 'EXCLUDE_BORROW':
			// deny a user access to a borrow a license.
			$featurep = explode(':', $parts[1]);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');
			$value    = trim($parts[2]);

			$ftt_array[] = "(" . $lmgrd['service_id'] . ",'" . $feature . "','" . $keyword . "','" . $parts[0] . "','" . $parts[2] . "','" . $parts[3] . "'," . db_qstr($comment) . ",1)";
			$ftt_cnt++;
			break;
		case 'EXCLUDE':
			// deny a user access to a feature.
			$featurep = explode(':', $parts[1]);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');
			$value    = trim($parts[2]);

			$ftt_array[] = "(" . $lmgrd['service_id'] . ",'" . $feature . "','" . $keyword . "','" . $parts[0] . "','" . $parts[2] . "','" . $parts[3] . "'," . db_qstr($comment) . ",1)";
			$ftt_cnt++;

			break;
		case 'EXCLUDEALL':
			// deny a user access to all features served by this vendor daemon.
			$inx_array[] = "(" . $lmgrd['service_id'] . ",'" . $parts[0] . "','" . $parts[1] . "','" . $parts[2] . "'," . db_qstr($comment) . ",1)";
			$inx_cnt++;

			break;
		case 'GROUP':
			// define a group of users for use with any options.
			$group = $parts[1];
			array_shift($parts);
			array_shift($parts);

			if (cacti_sizeof($parts)) {
			foreach($parts as $user) {
				if ($user != "\\") {
					$ugp_array[] = "(" . $lmgrd['service_id'] . ",'" . $group . "','" . $user . "',1)";
					$ugp_cnt++;
				}else{
					$prev     = 'GROUP';
					$pgroup   = $group;
					$continue = true;
				}
			}
			}
			break;
		case 'GROUPCASEINSENSITIVE':
			// determine if group name is case sensitive
			if ($parts[1] == 'ON') {
				$groupcaseins = 1;
			}
			break;
		case 'HOST_GROUP':
			// define a group of hosts for use with any options.
			$group = $parts[1];
			array_shift($parts);
			array_shift($parts);

			if (cacti_sizeof($parts)) {
			foreach($parts as $host) {
				if ($host != "\\") {
					$hgp_array[] = "(" . $lmgrd['service_id'] . ",'" . $group . "'," . db_qstr($host) . ",1)";
					$hgp_cnt++;
				}else{
					$prev     = 'HOST_GROUP';
					$pgroup   = $group;
					$continue = true;
				}
			}
			}
			break;
		case 'INCLUDE':
			// allow a user to use a feature.
			$featurep = explode(':', $parts[1]);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');
			$value    = trim($parts[2]);

			$ftt_array[] = "(" . $lmgrd['service_id'] . ",'" . $feature . "','" . $keyword . "','" . $parts[0] . "','" . $parts[2] . "','" . $parts[3] . "'," . db_qstr($comment) . ",1)";
			$ftt_cnt++;

			break;
		case 'INCLUDE_BORROW':
			// allow a user to borrow a license.
			$featurep = explode(':', $parts[1]);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');
			$value    = trim($parts[2]);

			$ftt_array[] = "(" . $lmgrd['service_id'] . ",'" . $feature . "','" . $keyword . "','" . $parts[0] . "','" . $parts[2] . "','" . $parts[3] . "'," . db_qstr($comment) . ",1)";
			$ftt_cnt++;

			break;
		case 'INCLUDEALL':
			// allow a user to use all features served by this vendor daemon.
			$inx_array[] = "(" . $lmgrd['service_id'] . ",'" . $parts[0] . "','" . $parts[1] . "','" . $parts[2] . "'," . db_qstr($comment) . ",1)";
			$inx_cnt++;

			break;
		case 'LINGER':
			// cause licenses to be held by the vendor daemon for a period after the application checks them in or exits.
			$value    = trim($parts[2]);

			$ft_array[$parts[1]]['linger'] = $value;
			break;
		case 'MAX':
			// limit usage for a particular feature/group - prioritizes usage among users.
			if (isset($parts[4])) {
				$featurep = explode(':', $parts[2]);
				$feature  = $featurep[0];
				$keyword  = (isset($featurep[1]) ? $featurep[1]:'');

				$max_array[] = "(" . $lmgrd['service_id'] . "," . $parts[1] . ",'" . $feature . "','" . $keyword . "','" . $parts[3] . "','" . $parts[4] . "'," . db_qstr($comment) . ",1)";
				$max_cnt++;
			}

			break;
		case 'MAX_BORROW_HOURS':
			// Changes the maximum period a license can be borrowed from that specified in the license certificate for feature.
			$value    = trim($parts[2]);

			$ft_array[$parts[1]]['max_borrow_hours'] = $value;
			break;
		case 'MAX_OVERDRAFT':
			// limit overdraft usage to less than the amount specified in the license.
			$value    = trim($parts[2]);

			$ft_array[$parts[1]]['max_overdraft'] = $value;
			break;
		case 'NOLOG':
			// turn off logging certain items.
			$option = strtoupper(trim($parts[1]));
			switch($option) {
			case 'IN':
				$nolog_in = 1;
				break;
			case 'OUT':
				$nolog_out = 1;
				break;
			case 'QUEUED':
				$nolog_queued = 1;
				break;
			case 'DENIED':
				$nolog_denied = 1;
				break;
			}
			break;
		case 'REPORTLOG':
			// specify that a logfile be written suitable for use by the FLEXadmin End-User Administration Tool.
			$report_path = trim($parts[1],'+');
			break;
		case 'RESERVE':
			// reserve licenses for a user.
			$featurep = explode(':', $parts[2]);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');

			$rsv_array[] = "(" . $lmgrd['service_id'] . "," . $parts[1] . ",'" . $feature . "','" . $keyword . "','" . $parts[3] . "','" . $parts[4] . "'," . db_qstr($comment) . ",1)";
			$rsv_cnt++;

			break;
		case 'TIMEOUT':
			// specify idle timeout for a feature, returning it to the free pool for use by another user.
			$value    = trim($parts[2]);

			$ft_array[$parts[1]]['timeout'] = $value;
			break;
		case 'TIMEOUTALL':
			// Set timeout on all features
			$timeoutall = trim($parts[1]);
			break;
		}

		if ($ftt_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_feature_type
				(`service_id`,`feature`,`keyword`,`variable`,`otype`,`name`,`notes`,`present`)
				VALUES " . implode(',', $ftt_array) . "
				ON DUPLICATE KEY UPDATE notes=VALUES(notes), present=1");

			$ftt_array = array();
			$ftt_cnt   = 0;
		}

		if ($rsv_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_reserve
				(`service_id`,`num_lic`,`feature`,`keyword`,`otype`,`name`,`notes`,`present`)
				VALUES " . implode(',', $rsv_array) . "
				ON DUPLICATE KEY UPDATE num_lic=VALUES(num_lic), notes=VALUES(notes), present=1");

			$rsv_array = array();
			$rsv_cnt   = 0;
		}

		if ($max_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_max
				(`service_id`,`num_lic`,`feature`,`keyword`,`otype`,`name`,`notes`,`present`)
				VALUES " . implode(',', $max_array) . "
				ON DUPLICATE KEY UPDATE num_lic=VALUES(num_lic), notes=VALUES(notes), present=1");

			$max_array = array();
			$max_cnt   = 0;
		}

		if ($inx_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_incexcl_all
				(`service_id`,`incexcl`,`otype`,`name`,`notes`,`present`)
				VALUES " . implode(',', $inx_array) . "
				ON DUPLICATE KEY UPDATE notes=VALUES(notes), present=1");

			$inx_array = array();
			$inx_cnt   = 0;
		}

		if ($hgp_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_host_groups
				(`service_id`,`group`,`host`,`present`)
				VALUES " . implode(',', $hgp_array) . "
				ON DUPLICATE KEY UPDATE present=1");

			$hgp_array = array();
			$hgp_cnt   = 0;
		}

		if ($ugp_cnt > 1000) {
			db_execute("INSERT INTO lic_services_options_user_groups
				(`service_id`,`group`,`user`,`present`)
				VALUES " . implode(',', $ugp_array) . "
				ON DUPLICATE KEY UPDATE present=1");

			$ugp_array = array();
			$ugp_cnt   = 0;
		}
	}

	/* update global settings first */
	db_execute_prepared("REPLACE INTO lic_services_options_global
		(service_id,options_path,debug_path,report_path,nolog_in,nolog_out,nolog_denied,nolog_queued,timeoutall,groupcaseinsens,present)
		VALUES
		(? , ?, ?, ?, ?, ?, ?, ?, ?, ?,1)",
		array($lmgrd['service_id'], $options_file, $debug_path, $report_path, $nolog_in, $nolog_out, $nolog_denied, $nolog_queued, $timeoutall, $groupcaseins));

	/* update the debug log patch in the flex table */
	if (strlen($debug_path)) {
		db_execute_prepared("UPDATE lic_services SET file_path=? WHERE service_id=?", array($debug_path, $lmgrd['service_id']));
	}

	if (cacti_sizeof($ugp_array)) {
		db_execute("INSERT INTO lic_services_options_user_groups
			(`service_id`,`group`,`user`,`present`)
			VALUES " . implode(',', $ugp_array) . "
			ON DUPLICATE KEY UPDATE present=1");
	}

	if (cacti_sizeof($hgp_array)) {
		db_execute("INSERT INTO lic_services_options_host_groups
			(`service_id`,`group`,`host`,`present`)
			VALUES " . implode(',', $hgp_array) . "
			ON DUPLICATE KEY UPDATE present=1");
	}

	if (cacti_sizeof($inx_array)) {
		db_execute("INSERT INTO lic_services_options_incexcl_all
			(`service_id`,`incexcl`,`otype`,`name`,`notes`,`present`)
			VALUES " . implode(',', $inx_array) . "
			ON DUPLICATE KEY UPDATE notes=VALUES(notes), present=1");
	}

	if (cacti_sizeof($max_array)) {
		db_execute("INSERT INTO lic_services_options_max
			(`service_id`,`num_lic`,`feature`,`keyword`,`otype`,`name`,`notes`,`present`)
			VALUES " . implode(',', $max_array) . "
			ON DUPLICATE KEY UPDATE num_lic=VALUES(num_lic), notes=VALUES(notes), present=1");
	}

	if (cacti_sizeof($rsv_array)) {
		db_execute("INSERT INTO lic_services_options_reserve
			(`service_id`,`num_lic`,`feature`,`keyword`,`otype`,`name`,`notes`,`present`)
			VALUES " . implode(',', $rsv_array) . "
			ON DUPLICATE KEY UPDATE num_lic=VALUES(num_lic), notes=VALUES(notes), present=1");
	}

	if (cacti_sizeof($ft_array)) {
		$values = array();
		foreach($ft_array as $feature => $options) {
			$featurep = explode(':', $feature);
			$feature  = $featurep[0];
			$keyword  = (isset($featurep[1]) ? $featurep[1]:'');

			$values[] = "(" . $lmgrd['service_id'] . ",'" .
				$feature . "','" . $keyword . "'," .
				(isset($options['borrow_lowwater']) ? $options['borrow_lowwater']:'NULL') . "," .
				(isset($options['linger']) ? $options['linger']:'NULL') . "," .
				(isset($options['max_borrow_hours']) ? $options['max_borrow_hours']:'NULL') . "," .
				(isset($options['max_overdraft']) ? $options['max_overdraft']:'NULL') . "," .
				(isset($options['timeout']) ? $options['timeout']:'NULL') . ",1)";

		}

		db_execute("INSERT INTO lic_services_options_feature
			(`service_id`,`feature`,`keyword`,`borrow_lowwater`,`linger`,`max_borrow_hours`,`max_overdraft`,`timeout`,`present`)
			VALUES " . implode(',', $values) . "
			ON DUPLICATE KEY UPDATE
			borrow_lowwater=VALUES(borrow_lowwater),
			linger=VALUES(linger),
			max_borrow_hours=VALUES(max_borrow_hours),
			max_overdraft=VALUES(max_overdraft),
			timeout=VALUES(timeout),
			present=1");
	}

	if (cacti_sizeof($ftt_array)) {
		db_execute("INSERT INTO lic_services_options_feature_type
			(`service_id`,`feature`,`keyword`,`variable`,`otype`,`name`,`notes`,`present`)
			VALUES " . implode(',', $ftt_array) . "
			ON DUPLICATE KEY UPDATE present=1");
	}
}

function log_ip_ranges() {
	$domain = read_config_option('lic_ip_domain');
	$ranges = array_rekey(db_fetch_assoc("SELECT DISTINCT iprange
		FROM (
			SELECT DISTINCT host AS iprange
			FROM lic_services_options_host_groups
			WHERE host LIKE '%.*%'
			UNION
			SELECT DISTINCT name AS iprange
			FROM lic_services_options_incexcl_all
			WHERE otype='INTERNET'
		) AS recordset"),"iprange", "iprange");

	$grepstr = '';

	/* put all the hosts into memory */
	$hosts = array();
	if ($domain != '') {
		$dns_server = read_config_option('lic_host_dns');
		if ($dns_server == '') {
			$hosts = shell_exec("host -l $domain | awk '{printf(\"%s %s\\n\",$1,$4)}'");
		}else{
			$hosts = shell_exec("host -l $domain $dns_server | awk '{printf(\"%s %s\\n\",$1,$4)}' | grep 'has address'");
		}
		$hosts = explode("\n", $hosts);
	}

	lic_debug("There are " . cacti_sizeof($hosts) . " hosts in the hosts table for domain $domain");

	if (cacti_sizeof($ranges) && cacti_sizeof($hosts)) {
		db_execute("UPDATE lic_ip_ranges SET present=0");

		foreach($ranges as $range) {
			lic_debug("Normalizing IP Range '$range'");

			/* make the range more pgreg compatible */
			$prange   = str_replace(".", "\.", str_replace("*", "\d{1,3}", $range));
			$matches = preg_grep("/ $prange/",$hosts);

			$i = 0;
			$myhosts = array();
			if (cacti_sizeof($matches)) {
				foreach($matches as $l) {
					$parts = explode(' ', $l);
					$host  = trim(str_replace($domain,'',$parts[0]),'.');
					$ip    = $parts[1];
					$myhosts[] = "('" . $range . "','" . $host . "','" . $ip . "',1)";
					$i++;

					if ($i % 10000 == 0) {
						db_execute("INSERT INTO lic_ip_ranges (ip_range,hostname,ip_address,present)
							VALUES " . implode(',',$myhosts) . "
							ON DUPLICATE KEY UPDATE present=1");

						$myhosts = array();
						$i = 0;
					}
				}

				if ($i > 0) {
					db_execute("INSERT INTO lic_ip_ranges (ip_range,hostname,ip_address,present)
						VALUES " . implode(',',$myhosts) . "
						ON DUPLICATE KEY UPDATE present=1");
				}
			}
		}

		db_execute("DELETE FROM lic_ip_ranges WHERE present=0");
	}
}

function log_lic_statistics($lic_id=0, $from_gui=false) {
	global $start;

	/* take time and log performance data */
	list($micro,$seconds) = preg_split("/ /", microtime());
	$end = $seconds + $micro;

	$cacti_stats = sprintf("Time:%01.4f", round($end-$start,4));

	/* log to the database */
	db_execute_prepared("REPLACE INTO settings (name,value) VALUES ('stats_lic_flex_options', ?)", array($cacti_stats));

	/* log to the logfile */
	if ($lic_id > 0 && $from_gui) {  //this is happening when forcing to update options files from GUI
		$server_name = " - " . db_fetch_cell_prepared ("SELECT server_name FROM lic_services WHERE service_id=?", array($lic_id));
		$output = false;
	} else {
		$server_name = "";
		$output = true;
	}

	cacti_log('LICENSE OPTIONS STATS' .$server_name .': ' .$cacti_stats , $output, 'SYSTEM');
}

function lic_load_options_in_file($lmgrd, &$file) {
	global $cnn_id;

	$sql = $sql1  = array();
	$comment      = '';
	$commenttag   = read_config_option('lic_options_note_tag');

	foreach($file as $line) {
		// Process comments first
		$line = trim($line);
		if (strpos($line, $commenttag) !== false) {
			$ncomment = trim($line, '# ');

			if ($comment != '') {
				if ($comment == $ncomment) {
					$comment = '';
				}else{
					$comment = $ncomment;
				}
			}else{
				$comment = $ncomment;
			}

			continue;
		}elseif ($line[0] == '#' || $line == '') {
			continue;
		}

		/* find PER_USER entry */
		if (substr_count($line, 'MAX(')) {
			$line = trim($line,'[]% MAX()');
			$parts = explode(",",str_replace(' ','',str_replace("'",'',$line)));
			if ($parts[2] == 'USER') {
				if (substr_count($parts[3], 'mail_list')) {
					$tokens = $parts[0];
					$feature = $parts[1];
					$mlparts = explode('=',$parts[3]);
					$group   = trim($mlparts[1]);

					lic_debug("Found Group '$group'");

					if (!isset($_SESSION[$group])) {
						lic_debug("Storing Group '$group'");
						store_ldap_group_members($group);
						$_SESSION[$group] = 1;
					}

					$sql[]   = "(" . $lmgrd['service_id'] . ",$tokens,'$feature','','PER_USER','$group'," . db_qstr($comment) . ",1)";
				}elseif (substr_count($parts[3], 'user')) {
					$tokens = $parts[0];
					$feature = $parts[1];
					$mlparts = explode('=',$parts[3]);
					$user   = trim($mlparts[1]);

					lic_debug("Found User '$user'");

					$sql[]   = "(" . $lmgrd['service_id'] . ",$tokens,'$feature','','IND_USER','$user'," . db_qstr($comment) . ",1)";
				}
			}
		}elseif (substr_count($line, 'HOST_GROUP(')) {
			continue;
		}elseif (substr_count($line, 'GROUP(')) {
			$line      = trim($line,'[]% GROUP()');
			$parts     = explode(",",str_replace(' ','',str_replace("'",'',$line)));
			$flexgroup = $parts[0];
			$mlparts   = explode('=',$parts[1]);
			$ldapgroup = trim($mlparts[1]);

			lic_debug("Found LDAP to LM Group '$ldapgroup'");

			if (!isset($_SESSION[$ldapgroup])) {
				lic_debug("Storing Group '$ldapgroup'");
				store_ldap_group_members($ldapgroup);
				$_SESSION[$ldapgroup] = 1;
			}

			$sql1[]   = "('" . $ldapgroup . "','" . $flexgroup . "',1)";
		}
	}

	if (cacti_sizeof($sql)) {
		db_execute("INSERT INTO lic_services_options_max
			(service_id,num_lic,feature,keyword,otype,name,notes,present)
			VALUES " . implode(',',$sql) . "
			ON DUPLICATE KEY UPDATE num_lic=VALUES(num_lic),notes=VALUES(notes),present=1");
	}

	if (cacti_sizeof($sql1)) {
		db_execute("INSERT INTO lic_ldap_to_flex_groups
			(ldap_group,flex_group,present)
			VALUES " . implode(',',$sql1) . "
			ON DUPLICATE KEY UPDATE present=VALUES(present)");
	}
}

function store_ldap_group_members($group) {  //need to remove
	$server  = read_config_option('lic_ldap_server');
	$base    = read_config_option('lic_ldap_base_dn');
	$filter  = read_config_option('lic_ldap_filter');
	$filter  = str_replace("|group_name|", $group, $filter);
	$version = read_config_option('lic_ldap_version');
	$attrs   = array('member');

	/* connect anonymously */
	$ldapConn=ldap_connect($server);

	if (is_resource($ldapConn)) {
		$ldapBind = ldap_bind($ldapConn);
		if (!$ldapBind) return false;

		ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, $version);

		$searchResults = ldap_search($ldapConn, $base, $filter, $attrs);

		$members = ldap_get_entries($ldapConn,$searchResults);

		if (cacti_sizeof($members)) {
			db_execute_prepared("UPDATE lic_ldap_groups SET present=0 WHERE `group`=?", array($group));

			$first = true;
			$sql   = array();
			$cnt   = 1;
			if (isset($members[0]['member'])) {
			foreach($members[0]['member'] AS $record) {
				if ($first) {
					$first = false;
				}else{
					$parts=explode(',',$record);
					$member=str_replace("uid=","",$parts[0]);
					$sql[] = "('$group','$member',1)";
					$cnt++;
				}

				if ($cnt % 1000 == 0) {
					db_execute("INSERT INTO lic_ldap_groups (`group`, user, present) VALUES " . implode(',',$sql) . " ON DUPLICATE KEY UPDATE present=1");
					$cnt = 1;
					$sql = array();
				}
			}
			}

			if ($cnt > 1) {
				db_execute("INSERT INTO lic_ldap_groups (`group`, user, present) VALUES " . implode(',',$sql) . " ON DUPLICATE KEY UPDATE present=1");
				$cnt = 0;
				$sql = array();
			}

			db_execute_prepared("DELETE FROM lic_ldap_groups WHERE present=0 AND `group`=?", array($group));
		}
	}else{
		return false;
	}

	return true;
}

/*      display_help - displays the usage of the function */
function display_help () {
	global $config;

	print "RTM Options Poller Process " . read_config_option('grid_version') . "\n";
	print html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
	print "usage: poller_options.php [-id=N] [--force] [--debug] [ -h | --help ] [ -V | --version ]\n\n";
	print "--id       - Process the options files for the License Service with this id.\n";
	print "--force    - Force the execution processing of the options file\n";
	print "--debug    - Display verbose output during execution\n";
	print "--version  - Display this help message\n";
	print "-h --help  - display this help message\n\n";
}

