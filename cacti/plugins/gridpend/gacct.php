#!/usr/bin/php -q
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

include(dirname(__FILE__) . '/../../include/cli_check.php');

include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_filter_functions.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_partitioning.php');

ini_set("memory_limit", "-1");

export_jobs(process_arguments(array('--version', '-v', '-V', '--help', '-h', '-H', '-C', '-S', '-D', '-e', '-d', '-c', '-o', '-P', '-app', '-Lp' ,'-q', '-m', '-mlt', '-mgt', '-u')));

function export_jobs($args) {
	$keys = array_keys($args);
	$sql_where  = "";

	//var_dump($args);exit;

	/* check for help */
	if (in_array("--help", $keys) || in_array("-H", $keys) || in_array("-h", $keys)) {
		display_help();
		exit;
	}

	if (in_array("--version", $keys) || in_array("-V", $keys) || in_array("-v", $keys)) {
		display_help();
		exit;
	}

	/* check required time ranges */
	if (!in_array("-C", $keys) && !in_array("-S", $keys) && !in_array("-D", $keys)) {
		echo "FATAL: You must specifiy either -C, -S, or -D\n";
		display_help();
		exit;
	}

	/* submit time */
	$min_time = time();
	$max_time = 0;
	if (in_array("-S", $keys)) {
		$parts = explode(",", $args["-S"]);
		if (strtotime($parts[0]) < $min_time && strtotime($parts[0]) !== false) {
			$min_time = strtotime($parts[0]);
		}

		if (cacti_sizeof($parts) == 1) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (submit_time>='" . $parts[0] ."')";
		}elseif(cacti_sizeof($parts) == 2) {
			if (strtotime($parts[1]) > $max_time && strtotime($parts[1]) !== false) {
				$max_time = strtotime($parts[1]);
			}

			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (submit_time BETWEEN '" . $parts[0] ."' AND '" . $parts[1] . "')";
		}else{
			echo "FATAL: Submit Time specification invalid\n";
			display_help();
			exit;
		}
	}

	/* start time */
	if (in_array("-D", $keys)) {
		$parts = explode(",", $args["-D"]);
		if (strtotime($parts[0]) < $min_time && strtotime($parts[0]) !== false) {
			$min_time = strtotime($parts[0]);
		}

		if (cacti_sizeof($parts) == 1) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (start_time>='" . $parts[0] ."')";
		}elseif(cacti_sizeof($parts) == 2) {
			if (strtotime($parts[1]) > $max_time && strtotime($parts[1]) !== false) {
				$max_time = strtotime($parts[1]);
			}

			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (start_time BETWEEN '" . $parts[0] ."' AND '" . $parts[1] . "')";
		}else{
			echo "FATAL: Start Time specification invalid\n";
			display_help();
			exit;
		}
	}

	/* start time */
	if (in_array("-C", $keys)) {
		$parts = explode(",", $args["-C"]);
		if (strtotime($parts[0]) < $min_time && strtotime($parts[0]) !== false) {
			$min_time = strtotime($parts[0]);
		}

		if (cacti_sizeof($parts) == 1) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (end_time>='" . $parts[0] ."')";
		}elseif(cacti_sizeof($parts) == 2) {
			if (strtotime($parts[1]) > $max_time && strtotime($parts[1]) !== false) {
				$max_time = strtotime($parts[1]);
			}

			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (end_time BETWEEN '" . $parts[0] ."' AND '" . $parts[1] . "')";
		}else{
			echo "FATAL: End Time specification invalid\n";
			display_help();
			exit;
		}
	}

	/* cluserid's */
	$clusters = array();
	if (in_array("-c", $keys)) {
		$clusters = explode(" ", $args["-c"]);
		if (cacti_sizeof($clusters)) {
		foreach($clusters as $cluster) {
			if (!is_numeric($cluster)) {
				echo "FATAL: Invalid Clusterid '$cluster'\n";
				exit;
			}
		}
		}

		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (clusterid IN (" . implode(",", $clusters) . "))";
	}

	/* user's */
	$user = array();
	if (in_array("-u", $keys)) {
		$users = explode(" ", $args["-u"]);
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (user IN ('" . implode("','", $users) . "'))";
	}

	/* hosts's */
	if (in_array("-m", $keys)) {
		$nhosts = array();
		$hosts  = explode(" ", $args["-m"]);
		if (cacti_sizeof($hosts)) {
			foreach($hosts as $host) {
				$hgroup = db_fetch_assoc_prepared("SELECT host
					FROM grid_hostgroups
					WHERE groupName=?" . (cacti_sizeof($clusters) ? " AND clusterid IN (" . implode(",", $clusters) . ")":""), array($host));
				if (cacti_sizeof($hgroup)) {
					foreach($hgroup as $h) {
						$nhosts[$h['host']] = 1;
					}
				}else{
					$nhosts[$host] = 1;
				}
			}
		}

		if (cacti_sizeof($nhosts)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (exec_host IN ('" . implode("','", array_keys($nhosts)) . "'))";
		}
	}

	/* queues's */
	if (in_array("-q", $keys)) {
		$queues = explode(" ", $args["-q"]);

		if (cacti_sizeof($queues)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (queue IN ('" . implode("','", $queues) . "'))";
		}
	}

	/* projects's */
	if (in_array("-P", $keys)) {
		$p = explode(" ", $args["-P"]);

		if (cacti_sizeof($p)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (projectName IN ('" . implode("','", $p) . "'))";
		}
	}

	/* applications */
	if (in_array("-app", $keys)) {
		$p = explode(" ", $args["-app"]);

		if (cacti_sizeof($p)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (app IN ('" . implode("','", $p) . "'))";
		}
	}

	/* memory limit's */
	if (in_array("-mlt", $keys)) {
		$p = $args["-mlt"];

		if (cacti_sizeof($p)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (max_memory < " . ($p*1024) . ")";
		}
	}

	/* memory limit's */
	if (in_array("-mgt", $keys)) {
		$p = $args["-mgt"];

		if (cacti_sizeof($p)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (max_memory > " . ($p*1024) . ")";
		}
	}

	/* license projects's */
	if (in_array("-Lp", $keys)) {
		$p = explode(" ", $args["-Lp"]);

		if (cacti_sizeof($p)) {
			$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (licenseProject IN ('" . implode("','", $p) . "'))";
		}
	}

	/* option's */
	$options = array();
	if (in_array("-o", $keys)) {
		$o = explode(",", $args["-o"]);

		if (cacti_sizeof($o)) {
		foreach($o as $op) {
			$options[] = trim($op);
		}
		}
	}

	/* stats */
	$stat = "";
	if (in_array("-e", $keys)) {
		$stat = "'EXIT'";
	}
	if (in_array("-d", $keys)) {
		$stat .= (strlen($stat) ? ",":"") . "'DONE'";
	}

	if ($stat != "") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE") . " (stat IN($stat))";
	}

	if (read_config_option("grid_partitioning_enable") == "on") {
		if ($max_time == 0) {
			$max_time = date("Y-m-d H:i:s");
		}else{
			$max_time = date("Y-m-d H:i:s", $max_time);
		}
		$min_time = date("Y-m-d H:i:s", $min_time);

		$tables = partition_get_partitions_for_query("grid_jobs_finished", $min_time, $max_time);
		if (cacti_sizeof($tables)) {
			$sql = "";
			foreach($tables as $t) {
				$sql .= (strlen($sql) ? " UNION ALL ":"") . "SELECT gj.* FROM $t AS gj $sql_where";
			}
			$jobs = db_fetch_assoc($sql);
		}else{
			$jobs = array();
		}
	}else{
		$jobs  = db_fetch_assoc("SELECT gj.*
			FROM grid_jobs_finished AS gj
			$sql_where");
	}

	$queue_nice_levels = array_rekey(db_fetch_assoc("SELECT
		CONCAT_WS('',clusterid,'-',queuename,'') AS cluster_queue,
		nice
		FROM grid_queues"), "cluster_queue", "nice");

	/* build header */
	echo build_export_header($options) . "\n";

	if (cacti_sizeof($jobs)) {
		foreach($jobs as $job) {
			echo build_export_row($job, $queue_nice_levels, $options) . "\n";
		}
	}
}

function process_arguments($noopt = array()) {
	$result = array();
	$params = $GLOBALS['argv'];

	/* move off the file name */
	array_shift($params);
	reset($params);

	while(current($params) !== FALSE){
		$tmp = key($params);
		$p   = current($params);
		next($params);

		if ($p[0] == '-') {
			$pname = substr($p, 1);
			$value = true;
			$pret  = '-';
			if ($pname[0] == '-') {
				/* long-opt (--<param>) */
				$pname = substr($pname, 1);
				$pret .= '-';
				if (strpos($p, '=') !== false) {
					/* value specified inline (--<param>=<value>) */
					list($pname, $value) = explode('=', substr($p, 2), 2);
				}
			}
			/* check if next parameter is a descriptor or a value */
			$nextparm = current($params);
			if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm[0] != '-') {
				$tmp   = key($params);
				$value = current($params);
				next($params);
			}
			$result[$pret . $pname] = $value;
		} else {
			/* param doesn't belong to any option */
			$pret     = '';
			$result[] = $p;
		}
	}

	return $result;
}

function get_export_option($option) {
	switch($option) {
	case "projectName":
		$xoption = "project";
		break;
	case "licenseProject":
		$xoption = "lproject";
		break;
	case "num_nodes":
		$xoption = "num_cpus";
		break;
	case "stat":
		$xoption = "status";
		break;
	case "exitcode":
		$xoption = "exit";
		break;
	case "ugroup":
		$xoption = "exit";
		break;
	case "jobSLA":
		$xoption = "sla";
		break;
	case "parentGroup":
		$xoption = "pgroup";
		break;
	case "jobGroup":
		$xoption = "jgroup";
		break;
	default:
		$xoption = $option;
	}

	return $xoption;
}

function format_header($output, $option, $output_options) {
	$xoption = get_export_option($option);

	if ((!is_array($output_options) && read_grid_config_option("export_" . $xoption)) ||
		(in_array($option, $output_options))) {
		return $output . (strlen($output) ? ",":"") . '"' . $option . '"';
	}

	return $output;
}

function format_row($output, $option, $value, $output_options) {
	$xoption = get_export_option($option);

	if ((!is_array($output_options) && read_grid_config_option("export_" . $xoption)) ||
		(in_array($option, $output_options))) {
		return $output . (strlen($output) ? ",":"") . (is_numeric($value) ? $value:'"' . $value . '"');
	}

	return $output;
}

function build_export_header($options) {
	$output  = '"clusterid","clustername","jobid","indexid","queue","app","projectName","user","submit_time","start_time","end_time"';
	$output  = format_header($output, "jobname", $options);
	$output  = format_header($output, "from_host", $options);
	$output  = format_header($output, "exec_host", $options);
	$output  = format_header($output, "nice", $options);
	$output  = format_header($output, "num_cpus", $options);
	$output  = format_header($output, "num_nodes", $options);
	$output  = format_header($output, "stat", $options);
	$output  = format_header($output, "exitcode", $options);
	$output  = format_header($output, "req_memory", $options);
	$output  = format_header($output, "reserve_memory", $options);
	$output  = format_header($output, "pend_time", $options);
	$output  = format_header($output, "psusp_time", $options);
	$output  = format_header($output, "ssusp_time", $options);
	$output  = format_header($output, "ususp_time", $options);
	$output  = format_header($output, "unkwn_time", $options);
	$output  = format_header($output, "licenseProject", $options);
	$output  = format_header($output, "user_group", $options);
	$output  = format_header($output, "jobSLA", $options);
	$output  = format_header($output, "parentGroup", $options);
	$output  = format_header($output, "jobGroup", $options);
	$output  = format_header($output, "", $options);
	$output  = format_header($output, "", $options);
	$output .= ',"run_time","mem_used","swap_used","max_memory","max_swap","cpu_used","efficiency","stime","utime"';

	return $output;
}

function build_export_row($job, &$queue_nice_levels, $options) {
	global $config;

	$jobname     = grid_get_group_jobname($job);
	$exitstatus  = grid_get_exit_code($job["exitStatus"]);
	$clustername = grid_get_clustername($job["clusterid"]);
	$nice        = $queue_nice_levels[$job["clusterid"] . "-" . $job["queue"]];

	/* check queue reservation if job level is 0 */
	if ($job["mem_reserved"] == 0) {
		$qrr = db_fetch_cell_prepared("SELECT resReq FROM grid_queues WHERE queuename=?", array($job["queue"]));
		$res_req = parse_res_req_into_array($qrr);
		if (isset($res_req["rusage"])) {
			$mem_res = parse_res_req_rusage_string($res_req["rusage"], "mem");
			$job["mem_reserved"] = $mem_res["value"];
		}
	}

	$output  =
		$job['clusterid']     . ',"'  .
		$clustername          . '",'  .
		$job['jobid']         . ','   .
		$job['indexid']       . ',"'  .
		$job["queue"]         . '","' .
		$job["app"]           . '","' .
		$job["projectName"]   . '","' .
		$job["user"]          . '","' .
		$job["submit_time"]   . '","' .
		$job["start_time"]    . '","' .
		$job["end_time"]      . '"';

	$output  = format_row($output, "clusterid",      $job["clusterid"],      $options);
	$output  = format_row($output, "clustername",    $clustername,           $options);
	$output  = format_row($output, "jobname",        $jobname,               $options);
	$output  = format_row($output, "from_host",      $job["from_host"],      $options);
	$output  = format_row($output, "exec_host",      $job["exec_host"],      $options);
	$output  = format_row($output, "nice",           $nice,                  $options);
	$output  = format_row($output, "num_nodes",      $job["num_nodes"],      $options);
	$output  = format_row($output, "num_cpus",       $job["num_cpus"],       $options);
	$output  = format_row($output, "stat",           $job["stat"],           $options);
	$output  = format_row($output, "exitStatus",     $exitstatus,            $options);
	$output  = format_row($output, "req_memory",     $job["mem_requested"],  $options);
	$output  = format_row($output, "reserve_memory", $job["mem_reserved"],   $options);
	$output  = format_row($output, "pend_time",      $job["pend_time"],      $options);
	$output  = format_row($output, "psusp_time",     $job["psusp_time"],     $options);
	$output  = format_row($output, "ssusp_time",     $job["ssusp_time"],     $options);
	$output  = format_row($output, "unkwn_time",     $job["unkwn_time"],     $options);
	$output  = format_row($output, "licenseProject", $job["licenseProject"], $options);
	$output  = format_row($output, "userGroup",      $job["userGroup"],      $options);
	$output  = format_row($output, "jobSLA",         $job["sla"],            $options);
	$output  = format_row($output, "parentGroup",    $job["parentGroup"],    $options);
	$output  = format_row($output, "jobGroup",       $job["jobGroup"],       $options);

	$output .= "," . $job["run_time"] . ',';
	$output .= $job["mem_used"]   . ',';
	$output .= $job["swap_used"]  . ',';
	$output .= $job["max_memory"] . ',';
	$output .= $job["max_swap"]   . ',';
	$output .= $job["cpu_used"]   . ',';
	$output .= $job["efficiency"] . ',';
	$output .= $job["stime"]      . ',';
	$output .= $job["utime"];

	return $output;
}

function display_version() {
	echo "IBM Spectrum LSF RTM Job Exporter " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";
}

function display_help() {
	echo "RTM Job Exporter " . read_config_option("grid_version") . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8')." Copyright International Business Machines Corp, " . read_config_option("grid_copyright_year") . ".\n\n";

	echo "This program allows job records to be exported in CSV fomat.  Its syntax is meant\n";
	echo "to closely align with LSF's 'bacct' utility.  The subtile differences are related to\n";
	echo "how the arguments are called.\n\n";

	echo "Usage: gacct.php -C time0,time1 | -D time0,time1 | -S time0,time1 [-d] [-e]\n";
	echo "       [-c clusterid ...] [-o jobid,indexid,queue,...] [-m hostname ...]\n";
	echo "       [-Lp ls_project_name ...] [-P project_name ...] [-q queue_name ...]\n";
	echo "       [-u user ...] [-app app ... ] [-mlt mem_limt | -mgt mem_limit]\n";
	echo "       [-u user_name ... | -u all] [-x] [job_ID ...]\n\n";

	echo "Required and Optional for Clusters:\n\n";
	echo "-C time0,time1   - Displays accounting statistics for jobs that completed or exited\n";
	echo "                   during the specified time interval.\n\n";
	echo "-D time0,time1   - Displays accounting statistics for jobs dispatched during the\n";
	echo "                   specified time interval.\n\n";
	echo "-S time0,time1   - Displays accounting statistics for jobs submitted during the \n";
	echo "                   specified time interval.\n\n";
	echo "-c clusterid     - Displays jobs by clusterid.  If multiple clusterids are defined.\n";
	echo "                   They must be enclosed in quotes and separated by spaces.\n";
	echo "-d | -e          - Displays done and/or exited jobs.\n";
	echo "-o \"opt1,opt2\"   - Specified output format.  See list below for available fields.\n";
	echo "                   Several fields are not optional.\n";
	echo "-q queue_name    - Displays jobs that match the queue string.  Multiple queues must\n";
	echo "                   must be separated by a space and enclosed in quotes.\n";
	echo "-P project_name  - Displays jobs that match the project string.  Multiple projects\n";
	echo "                   must be separated by a space and enclosed in quotes.\n";
	echo "-app application - Displays jobs that match the application string.  Multiple applications\n";
	echo "                   must be separated by a space and enclosed in quotes.\n";
	echo "-u user          - Displays jobs that match the user string.  Multiple user must\n";
	echo "                   be separated by a space and enclosed in quotes.\n";
	echo "-Lp ls_project   - Displays jobs that match the License Project string.  Multiple \n";
	echo "                   License Projects must be separated by a space and enclosed in quotes.\n";
	echo "-mlt mem_limit   - Return only jobs that have less than this memory limit in MBytes\n";
	echo "-mgt mem_limit   - Return only jobs that have more than this memory limit in MBytes\n";
	echo "-m hostname      - Displays jobs that run on the host name or hostgroup.  Multiple\n";
	echo "                   Hosts or Host Groups must separated by a space and enclosed in \n";
	echo "                   quotes.\n\n";

	echo "Time Format Specification:\n\n";
	echo "Since RTM uses MySQL for the storage of job data, the time specification is different\n";
	echo "between 'bacct' and 'gacct'.  The time option must follow the specfication below.\n";
	echo "If only one time range is provided, the end time is the current time\n\n";
	echo "YYYY-MM-DD/HH:MM  -or- YYYY-MM-DD/HH:MM,YYYY-MM-DD/HH:MM\n\n";
	echo "YYYY  - Four Digit, for example 2011\n";
	echo "MM    - Two digit month\n";
	echo "DD    - Two digit year\n";
	echo "HH:MM - Two digit hour and minute\n\n";

	echo "Available Output Arguments:\n\n";

	echo "The following options are fixed and cannot be changed.\n\n";

	echo "  clusterid, jobid, clustername, queue, app, projectName, user, submit_time,\n";
	echo "  start_time, end_time, run_time, mem_used, swap_used, max_memory,\n";
	echo "  max_swap, cpu_used, efficiency, stime, utime\n\n";

	echo "The following arguments are available and must be enclosed by quotes and must be \n";
	echo "comma delimited.  If not specified.  System defaults will be applied.\n\n";

	echo "  jobname, from_host, exec_host, nice, num_nodes, num_cpus, stat, exitcode,\n";
	echo "  req_memory, reserve_memory, pend_time, psusp_time, ssusp_time, ususp_time,\n";
	echo "  unkwn_time, licenseProject, user_group, jobSLA, parentGroup, jobGroup\n\n";
}
