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
declare(ticks = 1);

include(dirname(__FILE__) . '/../../include/cli_check.php');

/* Start Initialization Section */
include_once($config['library_path'] . '/rtm_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');

function sig_handler($signo) {
	global $lsid;

	switch ($signo) {
		case SIGTERM:
		case SIGINT:
			cacti_log('WARNING: GRIDBLSTAT Poller process terminated by user', true, 'POLLER', POLLER_VERBOSITY_LOW);
			$running_processes = db_fetch_assoc_prepared("SELECT * FROM grid_processes WHERE taskname=?", array("ADDGRIDBLSTAT_$lsid"));
			if (cacti_sizeof($running_processes)) {
				foreach($running_processes as $process) {
					if (function_exists('posix_kill')) {
						cacti_log("WARNING: Termination GRIDBLSTAT poller process with pid '" . $process['pid'] . "'", true, 'POLLER', POLLER_VERBOSITY_LOW);
						posix_kill($process['pid'], SIGTERM);
					}
				}
			}
			remove_process_entry(0, 'ADDGRIDBLSTAT_' . $lsid);
			remove_process_entry(0, 'GRIDBLSTAT_' . $lsid);
			exit;
			break;
		default:
			// ignore all other signals
	}
}

/* take the start time to log performance data */
$start = microtime(true);

/* get the srm polling cycle */
ini_set('max_execution_time', '0');

global $debug, $lsid;

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$debug  = FALSE;
$force  = FALSE;
$lsid   = 0;
$errors = array();

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-d':
	case '--debug':
		$debug = TRUE;
		break;
	case '-i':
	case '--lsid':
		$lsid = $value;
		break;
	case '-f':
	case '--force':
		$force = TRUE;
		break;
	case '-h':
	case '-v':
	case '-V':
	case '--version':
	case '--help':
		display_help();
		exit;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . PHP_EOL . PHP_EOL;
		display_help();
		exit;
	}
}

gridblstat_debug('NOTE: About to Enter Grid License Scheduler Poller Processing');

if ($lsid > 0) {
	$collector = db_fetch_row_prepared("SELECT * FROM grid_blstat_collectors WHERE lsid=?", array($lsid));
} else {
	print "ERROR: License Scheduler Service $lsid Does not Exist!\n";
	exit;
}

// install signal handlers for UNIX only
if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGTERM, 'sig_handler');
	pcntl_signal(SIGINT, 'sig_handler');
}

$freq = $collector['poller_freq'];

if ($freq == "") $freq = 30;

$cycles = floor(300 / $freq);

/* if it's time to collect user stats, do so */
$times = 1;
if ($collector['disabled'] == '' && isset($collector['disabled'])) {
	gridblstat_debug('NOTE: License Scheduler Poller Enabled');
	if (detect_and_correct_running_processes(0, 'GRIDBLSTAT_' . $lsid, 300*3)) {
	$max_time = 0;
	while (true) {
		$cycle_start = time();
		if ($times == ($cycles - 1)) {
			gridblstat_debug('NOTE: Polling for Last Time this Cycle');
			gridblstat_check_blstat(true, $collector);
		} else {
			gridblstat_debug('NOTE: Polling Cycle');
			gridblstat_check_blstat(false, $collector);
		}
		$cycle_end = time();

		gridblstat_debug('NOTE: Polling Pass Completed');

		$times++;
		if ($times >= $cycles) {
			break;
		} else {
			$total_time = $cycle_end - $cycle_start;

			if ($total_time >= $freq) {
				if ($total_time > $max_time) {
					$max_time = $total_time;
				}
			} else {
				sleep($freq - $total_time);
			}
		}
	}

	if ($max_time > 0) {
		cacti_log("WARNING: GRIDBLSTAT poller is running longer than its poller frequency for collector $lsid.  Maximum runtime is " . ($max_time) . "! Consider increasing poller interval");
	}

	// Launch the graph creation process
	$command_string = read_config_option('path_php_binary');
	$extra_args = '-q ' . $config['base_path'] . '/plugins/gridblstat/poller_graphs.php --lsid=' . $lsid;
	exec_background($command_string, $extra_args);

	// Print out cached errors
	if (cacti_sizeof($errors)) {
	foreach($errors as $error => $e) {
		cacti_log($error, true, 'GRIDBLSTAT');
	}
	}
	remove_process_entry(0, 'GRIDBLSTAT_' . $lsid);
	}
} else {
	gridblstat_debug('NOTE: License Scheduler Poller Disabled for Collector ' . $lsid);
}

function gridblstat_check_blstat($log = false, $collector = array()) {
	global $errors, $config, $force, $cnn_id, $lsid;

	/* record the start time */
	$start_time           = microtime(true);

	$db_start_time        = db_fetch_cell("SELECT NOW()");

	/* initialize a few variables */
	$feature_cnt = 0;
	$project_cnt = 0;
	$cluster_cnt = 0;
	$job_cnt     = 0;
	$users       = array();

	$grid_short_hostname = db_fetch_cell("SELECT value
		FROM settings
		WHERE name = 'grid_short_hostname'
		AND value = 'on'");

	$clusters = array_rekey(
		db_fetch_assoc('SELECT clusterid, lsf_clustername
			FROM grid_clusters'),
		'lsf_clustername', 'clusterid'
	);

	$version = $collector['ls_version'];

	$bin_realpath = realpath($config['base_path'] . '/../rtm/ls/bin');
	if($bin_realpath == false){
		cacti_log('ERROR: the directory '. $config['base_path'] . '/../rtm/ls/bin' . ' not found.', false, 'GRIDBLSTAT');
	}

	$blstat_binary  = "$bin_realpath/blstat";
	if(!file_exists($blstat_binary)){
		cacti_log("WARNING: the file $blstat_binary not found.", false, "GRIDBLSTAT");
	}

	$bluser_binary  = "$bin_realpath/blusers";
	if(!file_exists($bluser_binary)){
		cacti_log("WARNING: the file $bluser_binary not found.", false, "GRIDBLSTAT");
	}

	$blinfo_binary  = "$bin_realpath/blinfo";
	if(!file_exists($blinfo_binary)){
		cacti_log("WARNING: the file $blinfo_binary not found.", false, "GRIDBLSTAT");
	}

	$bltasks_binary = "$bin_realpath/bltasks";
	if(!file_exists($bltasks_binary)){
		cacti_log("WARNING: the file $bltasks_binary not found.", false, "GRIDBLSTAT");
	}

	/*check LS is existed or not*/
	$ls_id = $collector['name'];

	$collector = db_fetch_row_prepared("SELECT *
		FROM grid_blstat_collectors
		WHERE lsid=?", array($collector['lsid']));

	if (empty($collector['lsid'])){
		cacti_log("WARNING: LS collector $ls_id has been deleted.", false, 'GRIDBLSTAT');
		exit;
	}
		/* get the cluster environement location */
	if(!empty($collector['advanced_enabled'])){ //on
		$ls_envdir_realpath  = $collector['lsf_envdir'];
	} else {
		$ls_envdir_realpath  = realpath($config['base_path'] . '/../rtm/etc/ls' . $lsid);
		if($ls_envdir_realpath == false){
			cacti_log("ERROR: Unable to find the License Scheduler environment directory '" . $config['base_path'] . '/../rtm/etc/ls' . $lsid . "'", FALSE);
		}
	}

	putenv("LSF_ENVDIR=$ls_envdir_realpath");
	putenv("LSF_CONFDIR=$ls_envdir_realpath");

	$blinfo_clusters = shell_exec($blinfo_binary . " -C | grep -vE '^[ ]' | awk '{print $1}' | grep -E '[^ ]' | grep -vE '^(NAME|CLUSTER_NAME|interactive)' | sort | uniq");

	$blinfo_clusters = trim($blinfo_clusters?:'');

	if (strlen($blinfo_clusters)) {
		$blinfo_clusters = explode("\n", $blinfo_clusters);
		$sql_prefix = 'REPLACE INTO grid_blstat_collector_clusters (lsid, clusterid) VALUES ';
		$sql        = '';
		$sql_cnt    = 0;
		$not_added_clusters = '';
		if (cacti_sizeof($blinfo_clusters)) {
			foreach($blinfo_clusters as $clust) {
				$sql .= ($sql_cnt == 0 ? '(':",(") .  "$lsid," .  (isset($clusters[$clust]) ? $clusters[$clust]:'0') .  ')';

				if(!isset($clusters[$clust])){
					$not_added_clusters .= $clust . ' ';
				}
				$sql_cnt++;
			}
		}

		if ($sql_cnt) {
			db_execute($sql_prefix . $sql);
		}

		if (strlen($not_added_clusters)) {
			cacti_log("WARNING: cluster '$not_added_clusters' not added in the RTM Grid Plugin", false, 'GRIDBLSTAT');
		}
	} else {
		cacti_log('WARNING: blinfo returned no information from license scheduler collector:' . $collector['name'], false, 'GRIDBLSTAT');
	}

	$clusterid = $collector['clusterid'];

	gridblstat_debug('NOTE: Getting blusers -J');
	$blusers         = shell_exec($bluser_binary . ' -J');

	gridblstat_debug('NOTE: Getting blusers');
	$blusers_no_parm = shell_exec($bluser_binary);

	/* get the overall status */
	gridblstat_debug('NOTE: Getting blstat');
	// Feature delta here
	$blstat          = shell_exec($blstat_binary);

	/* get the overall cluster distribution */
	gridblstat_debug('NOTE: Getting distribution');
	// Feature delta Here
	$blstat_distrib  = shell_exec($blstat_binary . ' -s');

	/* get the task list */
	gridblstat_debug('NOTE: Getting Tasks');
	$bltasks  = shell_exec($bltasks_binary . ' -l');

	/* get the overall cluster distribution */
	gridblstat_debug("NOTE: Getting Service Domains");
	//db_execute("UPDATE grid_blstat_service_domains SET present=0 WHERE lsid=$lsid");
	//$blinfo_domains  = shell_exec($blinfo_binary . " -D | grep -v SERVICE_DOMAIN");
	exec($blinfo_binary . " -D | grep -v SERVICE_DOMAIN", $blinfo_domains, $ret);
	if (0 == $ret) {
		$add_lic_svc = read_config_option('grid_blstat_add_license_service');
		db_execute_prepared("UPDATE grid_blstat_service_domains SET present=0 WHERE lsid=?", array($lsid));
		if (cacti_sizeof($blinfo_domains)) {
			foreach($blinfo_domains as $domain) {
				if (trim($domain) == '') continue;

				$domParts = explode('(', $domain);
				if (cacti_sizeof($domParts)) {
					$domain = trim($domParts[0]);

					array_shift($domParts);

					if (cacti_sizeof($domParts)) {
						foreach($domParts as $domPart) {
							$lmgrds = trim($domPart);
							$lmgrds_pos=strrpos($lmgrds,')');
							$lm_type=trim(substr($lmgrds, $lmgrds_pos+1));
							$lmgrds=substr($lmgrds,0,$lmgrds_pos);

							/* detect the portatserver_id */
							$lmgrd_parts = preg_split('/[\s]+/', trim($lmgrds));
							if (cacti_sizeof($lmgrd_parts) == 1) {
								if (substr_count($lmgrds, ',')) {
									$lmgrd_parts = explode(',', trim($lmgrds));
								} elseif (substr_count($lmgrds, ':')) {
									$lmgrd_parts = explode(':', trim($lmgrds));
								} else {
									$lmgrd_parts = array(trim($lmgrds));
								}
							}

							foreach($lmgrd_parts as $part) {
								$ppart   = explode('.', $part);
								$part    = $ppart[0];

								$portatserver_id = 0;

								$matches = array_rekey(db_fetch_assoc_prepared("SELECT service_id, server_portatserver
									FROM lic_services
									WHERE server_portatserver LIKE ?", array("%" . $part . "%")),
									'service_id', 'server_portatserver');

								if (cacti_sizeof($matches)) {
									foreach($matches as $id => $server) {
										if (substr_count($server, ',')) {
											$servers = explode(',', $server);
										} elseif (substr_count($server, ':')) {
											$servers = explode(':', $server);
										} else {
											$servers = array($server);
										}
										foreach($servers as $server) {
											$short_server   = explode(".", $server);
											$server   = $short_server[0];
											if ($server == $part) {
												$found = true;
												$portatserver_id = $id;
												break;
											}
										}
									}
								}

								if (!empty($portatserver_id)) {
									db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?,?)", array("grid_blstats_domain_" . $lsid . "_" . $domain, $portatserver_id));
									db_execute_prepared("INSERT INTO grid_blstat_service_domains
										(lsid, service_domain, lic_id, present)
										VALUES (?, ?, ?, '1')
										ON DUPLICATE KEY UPDATE service_domain=VALUES(service_domain), present=1",
										array($lsid, $domain, $portatserver_id));
								} else {
									if (!empty($add_lic_svc)){
										$pats = explode('@', $part);
										if (cacti_sizeof($pats) == 2) {
											$licp_id = db_fetch_cell_prepared('SELECT lp.id FROM lic_pollers AS lp
												JOIN lic_managers AS lm ON lp.poller_type=lm.id WHERE lm.name=? ORDER BY lp.id', array($lm_type));

											if (!empty($licp_id)) {
												$lic_svc_name = $pats[1] . '_' . $pats[0];
												$php_bin = read_config_option('path_php_binary');
												$lic_cmd = cacti_escapeshellarg($config['base_path'] . '/plugins/license/lic_add_service.php');
												$add_args = ' --name=' . cacti_escapeshellarg($lic_svc_name) . ' --portatserver=' . cacti_escapeshellarg($part) . ' --poller=' . cacti_escapeshellarg($licp_id);
												passthru($php_bin . ' -q ' . $lic_cmd . $add_args);
												$lic_id = db_fetch_cell_prepared('SELECT service_id FROM lic_services WHERE server_portatserver=?', array($part));
												if (!empty($lic_id)) {
													db_execute_prepared("INSERT INTO grid_blstat_service_domains
														(lsid, service_domain, lic_id, present)
														VALUES (?, ?, ?, '1')
														ON DUPLICATE KEY UPDATE present=1",
														array($lsid, $domain, $lic_id));
													$errors["NOTE: Service Domain '$domain' at '$part' is not found from license plugin. RTM add it as a license service '" . $lic_svc_name . "[$lic_id]' to poller $lm_type Data"] = true;
													continue;
												}
											}
										}
									}
									$errors["WARNING: Service Domain '$domain' at '$part' is not being polled for FLEXlm/RLM Data"] = true;
									db_execute_prepared("INSERT INTO grid_blstat_service_domains
										(lsid, service_domain, lic_id, present)
										VALUES (?, ?,'0', '1')
										ON DUPLICATE KEY UPDATE service_domain=VALUES(service_domain), present=1",
										array($lsid, $domain));
								}
							}
						}
					}
				}
			}
		}
		db_execute_prepared("DELETE FROM grid_blstat_service_domains WHERE present=0 AND lsid=?", array($lsid));
	}

	/* get the flex mapping names */
	//db_execute("UPDATE grid_blstat_feature_map SET present=0 WHERE lsid=$lsid");

	//$blinfo_features  = shell_exec($blinfo_binary . ' -C | egrep "(LM_LICENSE_NAME|FLEX_NAME)"');
	exec($blinfo_binary . ' -C | egrep "(LM_LICENSE_NAME|FLEX_NAME)"', $blinfo_features, $ret);
	if (0 == $ret) {
		db_execute_prepared("UPDATE grid_blstat_feature_map SET present=0 WHERE lsid=?", array($lsid));
		if (cacti_sizeof($blinfo_features)) {
			foreach($blinfo_features as $feature) {
				if (trim($feature) == "") continue;

				if (substr_count($feature, 'FLEX_NAME')) {
					$parts = explode("FLEX_NAME:", $feature);
				} else {
					$parts = explode("LM_LICENSE_NAME:", $feature);
				}

				if (cacti_sizeof($parts)) {
					$name   = trim(str_replace("NAME:","", $parts[0]));
					$flex   = trim($parts[1]);

					$flexs = explode(" ", $flex);
					if (cacti_sizeof($flexs)) {
						/* GHE#584: settings.grid_blstats_flexname_xxx is used for drilldown on non-lm-feature page only */
						$flexstr = implode(",", $flexs);
						$sf = db_fetch_assoc_prepared("SELECT feature FROM grid_blstat WHERE (feature = ? OR feature LIKE ?) AND lsid=? AND present = 1", array($name, "$name@%", $lsid));
						if (cacti_sizeof($sf)) {
							foreach($sf as $f) {
								$bld_name = $f['feature'];
								db_execute_prepared("REPLACE INTO settings (name, value) VALUES (?, ?)", array("grid_blstats_flexname_" . $lsid . "_" . $bld_name, $flexstr));
								foreach($flexs as $flex) {
									if (strlen($flex)) {
										db_execute_prepared("REPLACE INTO grid_blstat_feature_map (lsid, bld_feature, lic_feature, present) VALUES (?, ?, ?, 1)", array($lsid, $bld_name, $flex));
									}
								}
							}
						}
					}
				}
			}
		}
		db_execute_prepared("DELETE FROM grid_blstat_feature_map WHERE present=0 AND lsid=?", array($lsid));
	}

	/* get the feature use */
	gridblstat_debug("NOTE: Getting Feature Cluster Use");
	if ($collector["ls_version"] >= "70") {
		$blstat_cluse = shell_exec("features=`" . $blinfo_binary . " |awk '{mode[\$2] = mode[\$2] \" \" \$1} END {print mode[\"Project\"]}'`;for feature in \$features;do " . $blstat_binary . " -c \$feature 2> /dev/null;done;");
	} else {
		$blstat_cluse = "";
	}

	//Only initilize the tables when the LS binaries call succeed
	if (0 == $ret) {
		/* now let's initilize the tables */
		// db_execute("UPDATE grid_blstat SET present=0 WHERE lsid=$lsid AND present=1");
		// db_execute("UPDATE grid_blstat_projects SET present=0 WHERE lsid=$lsid AND present=1");
		// db_execute("UPDATE grid_blstat_clusters SET present=0 WHERE lsid=$lsid AND present=1");
		db_execute_prepared("UPDATE grid_blstat_distribution SET present=0 WHERE lsid=?", array($lsid));
		// if ($collector["ls_version"] >= "70") {
			// db_execute("UPDATE grid_blstat_cluster_use SET present=0 WHERE lsid=$lsid AND present=1");
		// }
		db_execute_prepared("UPDATE grid_blstat_users SET present=0 WHERE lsid=?", array($lsid));
	}

	/* now lets populate the tables */
	if (rtm_strlen($blusers)) {
		$blusers = explode("\n", $blusers);

		$line1         = false;
		$line2         = false;
		$bluser_buffer = array();
		foreach($blusers as $record) {
			/* remove extra lines from string */
			$record = gridblstat_remove_spaces($record);

			if (substr($record,0,5) == "JOBID") {
				$line1 = true;
				$line2 = false;
				$job_cnt++;
			}else if (substr_count($record,"RESOURCE ")) {
				$line1 = false;
				$line2 = true;
			}else if (substr($record,0,5) == "-----") {
				$line1 = false;
				$line2 = false;
			} elseif ($line1) {
				$parts = preg_split('/[\s]+/', $record);

				if (7 > count($parts)) {
					cacti_log("WARNING: Output parsing error, please check the output of blusers [".$record."]");
				}

				$jobid = trim($parts[0]);
				if (substr_count($jobid, "[")) {
					$index_array = explode("[",$jobid);
					$indexid = trim($index_array[1]," ]");
					$parts_temp = explode("[",$jobid);
					$jobid = $parts_temp[0];
				} else {
					$indexid = 0;
				}
				$user  = trim($parts[1]);
				if($grid_short_hostname == 'on'){
					$host  = simple_strip_host_domain(trim($parts[2]));
				} else {
					$host  = trim($parts[2]);
				}
				//$proj  = trim($parts[3]);
				$clust = trim($parts[4]);
				$start = date("y-m-d H:i:s", strtotime($parts[5] . " " . $parts[6] . " " . $parts[7]));
				$users[$user] = true;
			} elseif ($line2 && strlen($record)) {
				$parts = preg_split('/[\s]+/', $record);

				$res   = trim($parts[0]);
				$rusage= trim($parts[1]);
				$sd    = trim($parts[2]);
				$proj  = trim($parts[4]); //EFFECTIVE_PROJECT
				$bluser_buffer[] = "($lsid, $jobid, $indexid, '$clust', '" . (isset($clusters[$clust]) ? $clusters[$clust]:"0") . "', '$user', '$host', '$proj', '$start', '$res', '$rusage', '$sd', 1)";
			} else {
				/* issue some warning */
			}
		}

		$sql_prefix = "INSERT INTO grid_blstat_users (lsid, jobid, indexid, `cluster`, clusterid, user, host, project, start_time, resource, rusage, service_domain, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE clusterid=VALUES(clusterid), host=VALUES(host), start_time=VALUES(start_time), rusage=VALUES(rusage), service_domain=VALUES(service_domain), user=VALUES(user), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $bluser_buffer);
	} else {
		cacti_log("WARNING: blusers returned no information from license scheduler collector:" . $collector['name'], false, "GRIDBLSTAT");
	}

	$bluser_no_parm_array = array();

	/* get the bluser */
	if (rtm_strlen($blusers_no_parm)) {
		$blusers_no_parm = explode("\n", $blusers_no_parm);
		$in_use = false;
		foreach($blusers_no_parm as $record) {
			/* remove extra lines from string */
			$record = gridblstat_remove_spaces($record);

			if (substr_count($record, "FEATURE")) {
				$in_use = true;
				continue;
			} elseif ($in_use) {
				$parts = preg_split('/[\s]+/', $record);
				if(count($parts) <6){
					continue;
				}
				$feature         = trim($parts[0]);
				$service_domain  = trim($parts[1]);
				$user            = trim($parts[2]);
				$host            = trim($parts[3]);
				$nlics           = trim($parts[4]);
				$ntasks          = trim($parts[5]);
				$bluser_no_parm_array[] = array('FEATURE'=> $feature, 'SERVICE_DOMAIN' => $service_domain, 'NLICS' => $nlics);

			} else {
				/* issue some warning */
			}
		}
	} else {
		cacti_log("WARNING: blusers returned no information from license scheduler collector:" . $collector['name'], false, "GRIDBLSTAT");
	}

	$blstat_array_pgm = array(); // if project group mode, blstat output need to cover new case. fix #73083

	/* get the feature distribution */
	if (rtm_strlen($blstat_distrib)) {
		$blstat_distrib = explode("\n", $blstat_distrib);

		$in_use = false;
		$blstat_distrib_array = array();
		foreach($blstat_distrib as $record) {
			/* remove extra lines from string */
			$record = gridblstat_remove_spaces($record);

			if ($record == '') {
				continue;
			} elseif (substr_count($record, "FEATURE:")) {
				$in_use  = false;
				$parts   = explode(":", $record);
				$feature = trim($parts[1]);

				$fd_total   = 0;
				$fd_adj     = 0;
				$fd_max     = 0;
				$fd_step    = 0;
				$fd_util    = 0;
				$fd_target  = 0;
			} elseif (substr_count($record, "FEATURE_DELTA")) {
				// FEATURE_DELTA: TOTAL: 1950 ADJ/MAX 1950/2350 STEP: 0  UTIL: 87.4% TARGET: 90.0%
				$parts     = preg_split('/[\s]+/', $record);
				$ma        = explode('/', $parts[4]);

				$fd_total  = $parts[2];
				$fd_adj    = $ma[0];
				$fd_max    = $ma[1];
				$fd_step   = $parts[6];
				$fd_util   = trim($parts[8], '%');
				$fd_target = trim($parts[8], '%');
			} elseif (substr_count($record, "SERVICE_")) {
				$parts = preg_split('/[\s]+/', $record);

				$sd    = trim($parts[1]);
				$total = trim($parts[3]);
			} elseif (substr_count($record,"LSF_USE ")) {
				$in_use = true;
			} elseif ($in_use) {
				$parts = preg_split('/[\s]+/', $record);

				$lsf_use         = trim($parts[0]);
				$lsf_deserve     = trim($parts[1]);
				$lsf_free        = trim($parts[2]);
				$non_lsf_use     = trim($parts[3]);
				$non_lsf_deserve = trim($parts[4]);
				$non_lsf_free    = trim($parts[5]);
				$blstat_distrib_array[] = "($lsid, '$feature', '$sd', '$total', '$lsf_use', '$lsf_deserve', '$lsf_free', '$non_lsf_use', '$non_lsf_deserve', '$non_lsf_free', 1)";

				//for project group mode, try to calculate TOTAL_INUSE,TOTAL_RESERVE,TOTAL_FREE,OTHERS values.
				$total_free      = $lsf_free;
				$nlics_total     = 0;
				if(count($bluser_no_parm_array) == 0){
					$nlics_total = 0;
				} else {
					foreach($bluser_no_parm_array as $k=>$v) {
						if($v['FEATURE'] == $feature && $v['SERVICE_DOMAIN'] == $sd){
							$nlics_total = $v['NLICS'] + $nlics_total;
						}
					}
				}
				$total_reserve   = $lsf_use + $non_lsf_use - $nlics_total;
				$total_inuse     = $lsf_use - $total_reserve;
				$total_others    = $lsf_deserve -  $lsf_use - $lsf_free;
				$blstat_array_pgm[] = "($lsid, '$feature', '$sd', '0', '$total_inuse', '$total_reserve', '$total_free', '0', '0', '0', '$total_others', '$fd_total', '$fd_adj', '$fd_max', '$fd_step', '$fd_util', '$fd_target', NOW(), 1)";
			} else {
				cacti_log("WARNING: Unknown blstat output1 '$record'", false, "GRIDBLSTAT");
			}
		}

		/* write the blstat information first */
		$sql_prefix = "INSERT INTO grid_blstat_distribution (lsid, feature, service_domain, total, lsf_use, lsf_deserve, lsf_free, non_lsf_use, non_lsf_deserve, non_lsf_free, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE total=VALUES(total), lsf_use=VALUES(lsf_use), lsf_deserve=VALUES(lsf_deserve), lsf_free=VALUES(lsf_free), non_lsf_use=VALUES(non_lsf_use), non_lsf_deserve=VALUES(non_lsf_deserve), non_lsf_free=VALUES(non_lsf_free), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_distrib_array);

	} else {
		cacti_log("WARNING: blstat distribution returned no information from license scheduler collector:" . $collector['name'], false, "GRIDBLSTAT");
	}

	if (rtm_strlen($blstat)) {
		$blstat = explode("\n", $blstat);

		$project_group_mode = false;
		$in_feature = false;
		$in_project = false;
		$in_cluster = false;
		$blstat_array = array();
		$blstat_array_cluster = array();
		$blstat_project_array = array();
		$blstat_cluster_array = array();
		foreach($blstat as $record) {
			/* remove extra lines from string */
			$record = gridblstat_remove_spaces($record);

			if ($record == '') {
				continue;
			} elseif (substr($record,0,8) == "FEATURE:") {
				$in_feature = true;
				$in_project = false;
				$in_cluster = false;
				$parts      = explode(":", $record);
				$feature    = trim($parts[1]);

				$fd_total   = 0;
				$fd_adj     = 0;
				$fd_max     = 0;
				$fd_step    = 0;
				$fd_util    = 0;
				$fd_target  = 0;

				$feature_cnt++;
			} elseif (substr($record,0,13) == "FEATURE_DELTA") {
				// FEATURE_DELTA: TOTAL: 1950 ADJ/MAX 1950/2350 STEP: 0  UTIL: 87.4% TARGET: 90.0%
				$parts     = preg_split('/[\s]+/', $record);
				$ma        = explode('/', $parts[4]);

				$fd_total  = $parts[2];
				$fd_adj    = $ma[0];
				$fd_max    = $ma[1];
				$fd_step   = $parts[6];
				$fd_util   = trim($parts[8], '%');
				$fd_target = trim($parts[8], '%');
			} elseif (substr($record,0,7) == "SERVICE") {
				$parts = explode(":", $record);
				$sd    = trim($parts[1]);
				$sd    = explode(" ", $sd); // Handle the multiple service domains as array
			} elseif (substr($record,0,8) == "TOTAL_TO") {
				$parts = preg_split('/[\s]+/', $record);

				$total_inuse  = 0;
				$total_res    = 0;
				$total_free   = 0;
				$total_tokens = trim($parts[1]);
				$total_alloc  = trim($parts[3]);
				$total_use    = trim($parts[5]);
				$others       = trim($parts[7]);

				if (!is_array($sd)) {
					$sd[0] = $sd;
				}

				foreach($sd as $s) {
					$blstat_array_cluster[] = "($lsid, '$feature', '$s', '1', '$total_tokens', '$total_alloc', '$total_use', '$others', '$fd_total', '$fd_adj', '$fd_max', '$fd_step', '$fd_util', '$fd_target', NOW(), 1)";
				}
			} elseif (substr($record,0,8) == "TOTAL_IN") {
				$parts = preg_split('/[\s]+/', $record);

				$total_inuse  = trim($parts[1]);
				$total_res    = trim($parts[3]);
				$total_free   = trim($parts[5]);
				$total_tokens = 0;
				$total_alloc  = 0;
				$total_use    = 0;
				$others       = trim($parts[7]);

				$n            = 0;
				foreach($sd as $s) {
					++$n;
				}
				if ($n > 1){
					$project_group_mode = true;
				} else {
					$project_group_mode = false;
				}

				if ($project_group_mode){
					$blstat_array = $blstat_array_pgm;
				} else{
					if (!is_array($sd)) {
						$sd[0] = $sd;
					}
					foreach($sd as $s) {
						$blstat_array[] = "($lsid, '$feature', '$s', '0', '$total_inuse', '$total_res', '$total_free', '$total_tokens', '$total_alloc', '$total_use', '$others', '$fd_total', '$fd_adj', '$fd_max', '$fd_step', '$fd_util', '$fd_target', NOW(), 1)";
					}
				}
			} elseif (substr($record,0,7) == "PROJECT") {
				$in_project = true;
				$in_feature = false;
				$in_cluster = false;
			} elseif (substr($record,0,7) == "CLUSTER") {
				$in_feature = false;
				$in_cluster = true;
				$in_project = false;
			} elseif ($in_project) {
				$parts = preg_split('/[\s]+/', $record);

				$project = trim($parts[0]);
				$share   = trim($parts[1]);
				if (substr($share,-1) == '%'){
					$share   = str_replace('%', '', $share);
					$own     = trim($parts[2]);
					$inuse   = trim($parts[3]);
					$reserve = trim($parts[4]);
					$free    = trim($parts[5]);
					$demand  = (isset($part[6]) ? trim($parts[6]):'0');
				} else {
					$own     = trim($parts[3]);
					$inuse   = trim($parts[4]);
					$reserve = trim($parts[5]);
					$free    = trim($parts[6]);
					$demand  = (isset($parts[7]) ? trim($parts[7]):'0');
				}

				if (!is_array($sd)) {
					$sd[0] = $sd;
				}

				foreach($sd as $s) {
					$blstat_project_array[] = "($lsid, '$feature', '$s', '$project', '$share', '$own', '$inuse', '$reserve', '$free', '$demand', NOW(), 1)";
				}

				$project_cnt++;
			} elseif ($in_cluster) {
				$parts = preg_split('/[\s]+/', $record);

				$cluster = trim($parts[0]);
				$share   = trim($parts[1]);
				if (substr($share,-1) == '%'){
					$share   = str_replace('%', '', $share);
					$alloc   = trim($parts[2]);
					$inuse   = trim($parts[4]);
					$reserve = trim($parts[5]);
					$over    = trim($parts[6]);
					$peak    = trim($parts[7]);
					$buffer  = trim($parts[8]);
					$free    = trim($parts[9]);
					$demand  = isset($parts[10]) ? trim($parts[10]):0;
				} else {
					$alloc   = trim($parts[3]);
					$inuse   = trim($parts[5]);
					$reserve = trim($parts[6]);
					$over    = trim($parts[7]);
					$peak    = trim($parts[8]);
					$buffer  = trim($parts[9]);
					$free    = trim($parts[10]);
					$demand  = isset($parts[11]) ? trim($parts[11]):0;
				}

				if (!is_array($sd)) {
					$sd[0] = $sd;
				}

				foreach($sd as $s) {
					$blstat_cluster_array[] = "($lsid, '$feature', '$s', '$cluster', '" . (isset($clusters[$cluster]) ? $clusters[$cluster]:"0") . "', '$share', '$alloc', '$inuse', '$reserve', '$over', '$peak', '$buffer', '$free', '$demand', NOW(), 1)";
				}

				$cluster_cnt++;
			} else {
				cacti_log("WARNING: Unknown blstat output22 '$record'", false, "GRIDBLSTAT");
			}
		}

		/* write the project level details next */
		$sql_prefix = "INSERT INTO grid_blstat_projects (lsid, feature, service_domain, project, `share`, own, inuse, `reserve`, free, demand, last_updated, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE `share`=VALUES(`share`), own=VALUES(own), inuse=VALUES(inuse), `reserve`=VALUES(`reserve`), free=VALUES(free), demand=VALUES(demand), last_updated=NOW(), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_project_array);

		/* write the cluster level details next */
		$sql_prefix = "INSERT INTO grid_blstat_clusters (lsid, feature, service_domain, `cluster`, clusterid, `share`, alloc, inuse, `reserve`, `over`, peak, `buffer`, free, demand, last_updated, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE clusterid=VALUES(clusterid), `share`=VALUES(`share`), alloc=VALUES(alloc), inuse=VALUES(inuse), `reserve`=VALUES(`reserve`), `over`=VALUES(`over`), peak=VALUES(peak), buffer=VALUES(buffer), free=VALUES(free), demand=VALUES(demand), last_updated=NOW(), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_cluster_array);

		/* write the blstat information first */
		$sql_prefix = "INSERT INTO grid_blstat (lsid, feature, service_domain, `type`, total_inuse, total_reserve, total_free, total_tokens, total_alloc, total_use, total_others, fd_total, fd_adj, fd_max, fd_step, fd_util, fd_target, last_updated, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE type=VALUES(type), total_inuse=VALUES(total_inuse), total_reserve=VALUES(total_reserve), total_free=VALUES(total_free), total_others=VALUES(total_others), total_tokens=VALUES(total_tokens), total_alloc=VALUES(total_alloc), total_use=VALUES(total_use), fd_total=VALUES(fd_total), fd_adj=VALUES(fd_adj), fd_max=VALUES(fd_max), fd_step=VALUES(fd_step), fd_util=VALUES(fd_util), fd_target=VALUES(fd_target), last_updated=NOW(), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_array);

		/* write the blstat information first */
		$sql_prefix = "INSERT INTO grid_blstat (lsid, feature, service_domain, `type`, total_tokens, total_alloc, total_use, total_others, fd_total, fd_adj, fd_max, fd_step, fd_util, fd_target, last_updated, present) VALUES";
		$sql_suffix = " ON DUPLICATE KEY UPDATE type=VALUES(type), total_others=VALUES(total_others), total_tokens=VALUES(total_tokens), total_alloc=VALUES(total_alloc), total_use=VALUES(total_use), fd_total=VALUES(fd_total), fd_adj=VALUES(fd_adj), fd_max=VALUES(fd_max), fd_step=VALUES(fd_step), fd_util=VALUES(fd_util), fd_target=VALUES(fd_target), last_updated=NOW(), present=1";
		gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_array_cluster);

		/* update free and reserved for cluster level */
		$values = db_fetch_assoc_prepared("SELECT feature, service_domain, SUM(inuse) AS inuse, SUM(reserve) AS reserve, SUM(free) AS free
			FROM grid_blstat_clusters
			WHERE lsid=?
			GROUP BY feature, service_domain", array($lsid));

		if (cacti_sizeof($values)) {
			foreach($values as $v) {
				db_execute_prepared("UPDATE IGNORE grid_blstat SET total_reserve=?, total_free=?, total_inuse=? WHERE lsid=? AND feature=? AND service_domain=?", array($v['reserve'], $v['free'], $v['inuse'], $lsid, $v['feature'], $v['service_domain']));
			}
		}
	} else {
		cacti_log("WARNING: blstat returned no information from license scheduler collector:" . $collector['name'], false, "GRIDBLSTAT");
	}


	if (cacti_sizeof($blstat_distrib)>0) {
		// Update grid_blstat table with totals for project based features
		db_execute_prepared("UPDATE grid_blstat AS gb INNER JOIN grid_blstat_distribution AS gbd ON gb.lsid=gbd.lsid AND gbd.feature=gb.feature AND gbd.service_domain=gb.service_domain SET gb.total_tokens=gbd.total WHERE gb.lsid=? AND gb.`type`=0", array($lsid));
	}

	/* parse out the cluster use if the correct version */
	if ($collector["ls_version"] >= "70") {
		/* get the feature distribution */
		if (strlen($blstat_cluse)) {
			$blstat_cluse = explode("\n", $blstat_cluse);

			$in_proj    = false;
			$in_proj_fd = false;
			$in_feat    = false;

			$blstat_cluse_array    = array();
			$blstat_cluse_fd_array = array();

			foreach($blstat_cluse as $record) {
				/* remove extra lines from string */
				$record = gridblstat_remove_spaces($record);

				if ($record == '') {
					continue;
				} elseif (substr($record,0,7) == "FEATURE") {
					$in_proj    = false;
					$in_proj_fd = false;
					$in_feat    = true;
					$parts      = explode(":", $record);
					$feature    = trim($parts[1]);
				} elseif (substr($record,0,7) == "PROJECT") {
					if (substr_count($record, 'OVER')) {
						$in_proj_fd = true;
					} else {
						$in_proj = true;
					}

					$in_feat = false;
				} elseif ($in_proj_fd) {
					$parts = preg_split('/[\s]+/', $record);

					if (cacti_sizeof($parts) == 9) {
						$project = trim($parts[0]);
						$cluster = trim($parts[1]);
						$alloc   = trim($parts[2]);
						$target  = trim($parts[3]);
						$inuse   = trim($parts[4]);
						$reserve = trim($parts[5]);
						$over    = trim($parts[6]);
						$free    = trim($parts[7]);
						$demand  = trim($parts[8]);
					} else {
						$cluster = trim($parts[0]);
						$alloc   = trim($parts[1]);
						$target  = trim($parts[2]);
						$inuse   = trim($parts[3]);
						$reserve = trim($parts[4]);
						$over    = trim($parts[5]);
						$free    = trim($parts[6]);
						$demand  = trim($parts[7]);
					}

					$blstat_cluse_fd_array[] = "($lsid, '$feature', '$cluster', '" . (isset($clusters[$cluster]) ? $clusters[$cluster]:"0") . "', '$project', '1', '$alloc', '$target', '$inuse', '$reserve', '$over', '$free', '$demand', '0', '0', '0', '0', NOW(), 1)";
				} elseif ($in_proj) {
					$parts = preg_split('/[\s]+/', $record);

					if (cacti_sizeof($parts) == 9) {
						$project     = trim($parts[0]);
						$cluster     = trim($parts[1]);
						$inuse       = trim($parts[2]);
						$reserve     = trim($parts[3]);
						$free        = trim($parts[4]);
						$need        = trim($parts[5]);
						$acum_use    = trim($parts[6]);
						$scaled_acum = trim($parts[7]);
						$avail       = trim($parts[8]);
					} else {
						$cluster     = trim($parts[0]);
						$inuse       = trim($parts[1]);
						$reserve     = trim($parts[2]);
						$free        = trim($parts[3]);
						$need        = trim($parts[4]);
						$acum_use    = trim($parts[5]);
						$scaled_acum = trim($parts[6]);
						$avail       = trim($parts[7]);
					}

					$blstat_cluse_array[] = "($lsid, '$feature', '$cluster', '" . (isset($clusters[$cluster]) ? $clusters[$cluster]:"0") . "', '$project', '0', '$inuse', '$reserve', '$free', '$need', '$acum_use', '$scaled_acum', '$avail', '0', '0', '0', '0', NOW(), 1)";
				} else {
					cacti_log("WARNING: Unknown blstat output3 '$record'", false, "GRIDBLSTAT");
				}
			}

			/* write the blstat cluster use information first */
			$sql_prefix = "INSERT INTO grid_blstat_cluster_use (lsid, feature, `cluster`, clusterid, project, type, inuse, `reserve`, free, need, acum_use, scaled_acum, avail, alloc, `over`, demand, target, last_updated, present) VALUES";
			$sql_suffix = " ON DUPLICATE KEY UPDATE type=VALUES(type), clusterid=VALUES(clusterid), inuse=VALUES(inuse), `reserve`=VALUES(`reserve`), free=VALUES(free), need=VALUES(need), acum_use=VALUES(acum_use), scaled_acum=VALUES(scaled_acum), avail=VALUES(avail), alloc=VALUES(alloc), `over`=VALUES(`over`), demand=VALUES(demand), target=VALUES(target), last_updated=NOW(), present=1";
			gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_cluse_array);

			/* write the blstat cluster use fast dispatch information first */
			$sql_prefix = "INSERT INTO grid_blstat_cluster_use (lsid, feature, `cluster`, clusterid, project, type, alloc, target, inuse, `reserve`, `over`, free, demand, need, acum_use, scaled_acum, avail, last_updated, present) VALUES";
			$sql_suffix = " ON DUPLICATE KEY UPDATE type=VALUES(type), clusterid=VALUES(clusterid), alloc=VALUES(alloc), target=VALUES(target), inuse=VALUES(inuse), `reserve`=VALUES(`reserve`), `over`=VALUES(`over`), free=VALUES(free), demand=VALUES(demand), alloc=VALUES(alloc), need=VALUES(need), acum_use=VALUES(acum_use), scaled_acum=VALUES(scaled_acum), avail=VALUES(avail), last_updated=NOW(), present=1";
			gridblstat_write_buffer($sql_prefix, $sql_suffix, $blstat_cluse_fd_array);
		} else {
			if ($collector["ls_version"] >= "70") {
				$blinfo_cluse = shell_exec($blinfo_binary . " |awk '{mode[\$2] = mode[\$2] \" \" \$1} END {print mode[\"Project\"]}' 2> /dev/null");
				$blinfo_cluse=trim($blinfo_cluse?:'');
				if (strlen($blinfo_cluse)) {
					cacti_log("WARNING: blstat cluster returned no information from license scheduler collector:" . $collector['name'], false, "GRIDBLSTAT");
				}
			}
		}

		/* let's parse the bltasks now */
		if (rtm_strlen($bltasks)) {
			db_execute_prepared("UPDATE grid_blstat_tasks SET present=0 WHERE lsid=?", array($lsid));

			$blt = explode("\n", $bltasks);

			$new_record = true;
			$in_header  = false;
			$bltasks_array = array();

			if (cacti_sizeof($blt)) {
				foreach($blt as $l) {
					/* remove extra lines from string */
					$l = gridblstat_remove_spaces($l);

					if ($new_record) {
						$pgid     = 0;
						$cpu      = 0;
						$mem      = 0;
						$swap     = 0;
						$tid      = 0;
						$stat     = "";
						$host     = "";
						$user     = "";
						$res_req  = "";
						$connect  = "";
						$cpu_idle = "";
						$project  = "";
						$feature  = "";
						$terminal = "";
						$command  = "";
						$new_record = false;
					}

					if (substr($l, 0, 5) == "-----") {
						/* buffer array */
						//$bltasks_array[] = "($lsid, '$feature', '$project', '$user', '$host', '$stat', '$tid', '$connect', '$terminal', '$pgid', '$cpu', '$mem', '$swap', '$cpu_idle', '$res_req', " . db_qstr($command) . ", 1)";
						$new_record = true;
					} elseif (substr($l, 0, 3) == "TID") {
						$in_header = true;
					} elseif ($in_header) {
						//$p = explode(" ", $l);
						$p = preg_split('/[\s]+/', $l);
						$tid   = $p[0];
						$user  = $p[1];
						$stat  = $p[2];
						if($grid_short_hostname == 'on'){
							$host  = simple_strip_host_domain($p[3]);
						} else {
							$host  = $p[3];
						}
						$project = $p[4];
						$feature = $p[5];
						$connect = date("Y-m-d H:i:s", strtotime($p[6] . " " . $p[7] . " " . $p[8]));
						$in_header = false;
					} elseif (substr($l, 0, 5) == "TERMI") {
						$p = explode(":", $l);
						$terminal = trim($p[1],';');
					} elseif (substr($l, 0, 4) == "PGID") {
						$p    = explode(";", $l);
						$pp   = explode(":", $p[0]);
						$pgid = $pp[1];
						$pp   = explode(":", $p[1]);
						$cpu  = $pp[1];
						$pp   = explode(":", $p[2]);
						$mem  = trim($pp[1],"KB");
						$pp   = explode(":", $p[3]);
						$swap = trim($pp[1],"KB");
					} elseif (substr($l, 0, 8) == "CPU idle") {
						$p        = explode(":", $l);
						$cpu_idle = date("Y-m-d H:i:s", strtotime($p[1]));
					} elseif (substr($l, 0, 7) == "RES_REQ") {
						$p       = explode("RES_REQ:", $l);
						$res_req = $p[1];
					} elseif (substr($l, 0, 7) == "Command") {
						$p       = explode("line:", $l);
						$command = $p[1];
						$bltasks_array[] = "($lsid, '$feature', '$project', '$user', '$host', '$stat', '$tid', '$connect', '$terminal', '$pgid', '$cpu', '$mem', '$swap', '$cpu_idle', '$res_req', " . db_qstr($command) . ", 1)";
					}
				}
			}

			/* write the blstat information first */
			// $bltasks_array[] = "('$feature', '$project', '$user', '$host', '$stat', '$tid', '$connect', '$terminal', '$pgid', '$cpu', '$mem', '$swap', '$cpu_idle', '$res_req', " . db_qstr($command) . ", 1)";
			$sql_prefix = "INSERT INTO grid_blstat_tasks (lsid, feature, project, user, host, stat, tid, connect_time, terminal, pgid, cpu_time, memory, swap, cpu_idle, res_requirements, command, present) VALUES";
			$sql_suffix = " ON DUPLICATE KEY UPDATE stat=VALUES(stat), pgid=VALUES(pgid), cpu_time=VALUES(cpu_time), memory=VALUES(memory), swap=VALUES(swap), cpu_idle=VALUES(cpu_idle), present=1";
			gridblstat_write_buffer($sql_prefix, $sql_suffix, $bltasks_array);

			db_execute_prepared("DELETE FROM grid_blstat_tasks WHERE present=0 AND lsid=?", array($lsid));
		} else {
		}
	}

	/* now let's cleanup */
	// db_execute("DELETE FROM grid_blstat WHERE present=0 AND lsid=$lsid");
	db_execute_prepared("UPDATE grid_blstat SET present=0 WHERE lsid=? AND present=1 AND last_updated<?", array($lsid, $db_start_time));
	// db_execute("DELETE FROM grid_blstat_projects WHERE present=0 AND lsid=$lsid");
	db_execute_prepared("UPDATE grid_blstat_projects SET present=0 WHERE lsid=? AND present=1 AND last_updated<?", array($lsid, $db_start_time));
	// db_execute("DELETE FROM grid_blstat_clusters WHERE present=0 AND lsid=$lsid");
	db_execute_prepared("UPDATE grid_blstat_clusters SET present=0 WHERE lsid=? AND present=1 AND last_updated<?", array($lsid, $db_start_time));
	if ($collector["ls_version"] >= "70") {
		// db_execute("DELETE FROM grid_blstat_cluster_use WHERE present=0 AND lsid=$lsid");
		db_execute_prepared("UPDATE grid_blstat_cluster_use SET present=0 WHERE lsid=? AND present=1 AND last_updated<?", array($lsid, $db_start_time));
	}
	db_execute_prepared("DELETE FROM grid_blstat_distribution WHERE present=0 AND lsid=?", array($lsid));
	db_execute_prepared("DELETE FROM grid_blstat_users WHERE present=0 AND lsid=?", array($lsid));
	db_execute_prepared("UPDATE grid_blstat_collectors SET blstat_lastrun=NOW() WHERE lsid=?", array($lsid));

	/* record the end time */
	$end_time = microtime(true);

	db_execute_prepared("REPLACE INTO settings (name,value) VALUES (?, ?)", array("gridblstat_lastrun_$lsid", time()));

	if ($log) {
		cacti_log("GRIDBLSTAT STATS: Time:" . round($end_time-$start_time,2) . ", Collector:" . $collector['name'] . ", Features:" . $feature_cnt . ", Projects:" . $project_cnt . ", Users:" . cacti_sizeof($users) . ", Jobs:" . $job_cnt, false, "SYSTEM");
	}
}

function gridblstat_remove_spaces($string) {
	$string = trim($string);

	if (strlen($string)) {
		if ($string[0] == "*") $string = trim(substr($string, 1));
	}

	return trim($string);
}

function gridblstat_write_buffer($sql_prefix, $sql_suffix, $sql_array, $max_size = 256000) {
	$overhead   = strlen($sql_prefix) + strlen($sql_suffix);

	$i       = 0;
	$buffer  = "";
	$bufsize = 0;
	if (cacti_sizeof($sql_array)) {
		foreach($sql_array as $record) {
			if ($overhead + $bufsize > $max_size) {
				db_execute($sql_prefix . $buffer . $sql_suffix);

				$i       = 0;
				$bufsize = 0;
				$buffer  = "";
			}

			if ($i == 0) {
				$delim = " ";
			} else {
				$delim = ", ";
			}

			$line     = $delim . $record;
			$bufsize += strlen($line);
			$buffer  .= $line;
			$i++;
		}

		/* insert the last record */
		if ($bufsize > 0) {
			db_execute($sql_prefix . $buffer . $sql_suffix);
			//cacti_log($sql_prefix . $buffer . $sql_suffix, true, "GRIDBLSTAT");
		}
	}
}


function gridblstat_debug($message) {
	global $debug;

	if ($debug) {
		echo $message . "\n";
	}
}

/* display_help - displays the usage of the function */
function display_help () {
	echo "RTM License Scheduler Poller " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	echo "Usage:\n";
	echo "poller_gridblstat.php --lsid=N | -i=N [-d | --debug] [-f | --force] [-h | --help | -v | -V | --version]\n\n";
	echo "-i | --lsid      - License Scheduler Service id to poll\n";
	echo "-d | --debug     - Display verbose output during execution\n";
	echo "-f | --force     - Force execution of the poller\n";
	echo "-v -V --version  - Display this help message\n";
	echo "-h --help        - display this help message\n";
}
