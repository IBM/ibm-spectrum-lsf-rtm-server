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

include_once(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['library_path'] . '/api_automation_tools.php');
include_once($config['library_path'] . '/api_data_source.php');
include_once($config['library_path'] . '/api_graph.php');
include_once($config['library_path'] . '/api_device.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

global $debug;

$debug       = FALSE;
$client      = FALSE;
$purgeall    = FALSE;
$graphonly   = FALSE;
$disableonly = FALSE;
$dryrun      = FALSE;
$clusterid   = '';
$host_id     = '';
$last_seen   = -1;

foreach($parms as $parameter) {
	if (strpos($parameter, '=')) {
		list($arg, $value) = explode('=', $parameter);
	} else {
		$arg = $parameter;
		$value = '';
	}

	switch ($arg) {
	case '-a':
	case '--all':
		$purgeall = TRUE;
		break;
	case '-c':
	case '--clusterid':
		if(empty($value) || is_nan($value)) {
			echo "ERROR: Invalid clusterid\n\n";
			display_help();
		} else {
			$clusterid = $value;
		}
		break;
	case '-H':
	case '--host-id':
		if(empty($value) || is_nan($value)) {
			echo "ERROR: Invalid hostid\n\n";
			display_help();
		} else {
			$host_id = $value;
		}
		break;
	case '-t':
	case '--last-seen':
		$dt = date_parse_from_format('Ymd', $value);
		if (!checkdate($dt['month'], $dt['day'], $dt['year'])) {
			echo "ERROR: Invalid last seen time\n\n";
			display_help();
		} else
			$last_seen = $dt['year'] . '-' . $dt['month'] . '-' . $dt['day'];
		break;
	case '-C':
	case '--client':
		$client = TRUE;
		break;
	case '-d':
	case '--debug':
		$debug = TRUE;
		break;
	case '-g':
	case '--graphonly':
		$graphonly = TRUE;
		break;
	case '--disableonly':
		$disableonly = TRUE;
		break;
	case '--dry-run':
		$dryrun = TRUE;
		break;
	case '-v':
	case '-V':
	case '--version':
		display_version();
		break;
	case '-h':
	case '--help':
		display_help();
		break;
	default:
		print 'ERROR: Invalid Parameter ' . $parameter . "\n\n";
		display_help();
	}
}

echo "NOTE: Checking for Obsoleted Devices for LSF Compute Nodes and Clients\n";

$sql_params_and = array();
$sql_where_and = '';
if(!$purgeall) {
	if(!empty($clusterid)) {
		$sql_where_and = " AND h.clusterid=?";
		$sql_params_and[] = $clusterid;
	} else if(!empty($host_id)) {
		$sql_where_and = " AND h.id=?";
		$sql_params_and[] = $host_id;
	} else {
		print "ERROR: one of option '-a', '-c' and '-H' is required.\n\n";
		display_help();
	}
}

$sql_params_or = array();
$sql_where_or = '';
if($last_seen == -1) {
	$last_seen = read_config_option('grid_host_autopurge');
	if($last_seen > 0) {
		$last_seen = date('Y-m-d H:i:s', time()-($last_seen*86400));
	} else {
		print "NOTICE: Both '-t|--last-seen' option and grid settings 'Host Auto Purge Frequency' are disabled.\n";
		return;
	}
}

if(!empty($last_seen) && $last_seen != -1) {
	$sql_where_or .= (strlen($sql_where_or) ? ' OR ' : ' ') . "ghi.last_seen<=?";
	$sql_params_or[] = $last_seen;
}
if($client ||  read_config_option('grid_purge_lsfhost_client') == 'on') {
	$sql_where_or .= (strlen($sql_where_or) ? ' OR ' : ' ') . 'ghi.isServer=0';
}

if($disableonly || read_config_option('grid_purge_lsfhost_disable') == 'on')
	$disableonly = TRUE;

if($graphonly || read_config_option('grid_purge_lsfhost_graph') == 'on')
	$graphonly = TRUE;

if(strlen($sql_where_or)) {
	//Proceed device with 'Grid Host (w/net-snmp)' and 'Grid Host' only.
	$sql_query = "SELECT h.id hostid, h.hostname cactihost, ghi.host hostname, ghi.clusterid clusterid
		FROM host AS h
		INNER JOIN host_template AS ht
		ON h.host_template_id=ht.id
		LEFT JOIN grid_hostinfo AS ghi
		ON h.clusterid=ghi.clusterid
		AND h.hostname=ghi.host
		WHERE ht.hash IN ('284bbabef4bb6e161af7e123c7c90969', '7972b0b7c4b67da7ba7ebd020cf54f87')
		AND h.clusterid=ghi.clusterid
		AND h.hostname=ghi.host
		AND h.clusterid > 0";
	$sql_query .= "$sql_where_and AND ($sql_where_or)";

	$hosts = db_fetch_assoc_prepared($sql_query, array_merge($sql_params_and, $sql_params_or));
	//echo $sql_query . "\n";

	grid_purge_device($hosts, $debug, $disableonly, $client, $graphonly);
}

echo "NOTE: The pass for host is NULL\n";

$sql_where_or = 'ghi.host IS NULL';
//Proceed device with 'Grid Host (w/net-snmp)' and 'Grid Host' only.
$sql_query = "SELECT h.id hostid, h.hostname cactihost, ghi.host hostname, ghi.clusterid clusterid
	FROM host AS h
	INNER JOIN host_template AS ht
	ON h.host_template_id=ht.id
	LEFT JOIN grid_hostinfo AS ghi
	ON h.clusterid=ghi.clusterid
	AND h.hostname=ghi.host
	WHERE ht.hash IN ('284bbabef4bb6e161af7e123c7c90969', '7972b0b7c4b67da7ba7ebd020cf54f87')
	AND h.clusterid > 0";
$sql_query .= "$sql_where_and AND ($sql_where_or)";

$hosts = db_fetch_assoc_prepared($sql_query, $sql_params_and);
//echo $sql_query . "\n";

grid_purge_device($hosts, $debug, $disableonly, $client, $graphonly);

function grid_purge_device($hosts, $debug, $disableonly, $client, $graphonly) {
	$hostslen = cacti_sizeof($hosts);
	if ($hostslen > 0) {
		$i=0;
		$p=0;
		if(!$debug) echo ($disableonly ? 'Disabling' : 'Purging') . " progress for $hostslen devices(%): ";
		foreach($hosts AS $host) {
			$proc = floor($i*100/$hostslen);
			if($proc >= $p) {
				if($debug)
					echo ($disableonly ? 'Disabling' : 'Purging') . ' obsoleted LSF Server' . ($client ? '/Client: ' : ': ') . $proc . "%\n";
				else
					echo " $proc";
				$p++;
			}
			purge_graphs_by_host($host['hostid'], $disableonly);
			if (!$graphonly && !empty($host['hostname']) && !empty($host['clusterid']))
				api_grid_host_remove($host['hostname'], $host['clusterid']);
			$i++;
		}
		if(!$debug)
			echo ($proc < 100 ? ' 100' : '') . "\n";
		else if($proc < 100)
			echo ($disableonly ? 'Disabling' : 'Purging') . ' obsoleted LSF Server' . ($client ? '/Client: ' : ': ') . "100%\n";
		echo "NOTE: " . (($disableonly ? 'Disable' : 'Purge')) . " Complete\n";
	} else {
		echo "NOTE: Not found devices to be purged\n";
	}
}

function purge_graphs_by_host($hostid, $disableonly = FALSE) {
	global $debug, $dryrun;
	if ($disableonly) {
		if (!empty($hostid)) {
			$message = "WARINING: Disabling device[$hostid], and cleanup its poller cache\n";
			if($debug)
				echo $message;

			if(!$dryrun) {
				cacti_log(trim($message), FALSE, 'SYSTEM');
				/* disable the host */
				db_execute_prepared("update host set disabled='on' where id=?", array($hostid));

				/* update poller cache */
				db_execute_prepared("delete from poller_item where host_id=?", array($hostid));
				db_execute_prepared("delete from poller_reindex where host_id=?", array($hostid));
			}
		}
	} else {
		$data_sources_to_act_on = array();
		$graphs_to_act_on       = array();


		/* error checking */
		if (!empty($hostid)) {

			$data_sources = db_fetch_assoc_prepared('SELECT
				data_local.id AS local_data_id
				FROM data_local
				WHERE data_local.host_id= ?', array($hostid));

			if (cacti_sizeof($data_sources) > 0) {
				foreach ($data_sources as $data_source) {
					$data_sources_to_act_on[] = $data_source['local_data_id'];
				}
			}

			$graphs = db_fetch_assoc_prepared('SELECT
				graph_local.id AS local_graph_id
				FROM graph_local
				WHERE graph_local.host_id = ?',
				array($hostid));

			if (cacti_sizeof($graphs)) {
				foreach ($graphs as $graph) {
					$graphs_to_act_on[] = $graph["local_graph_id"];
				}
			}

			$message = "WARINING: Purging device[$hostid], associated " . cacti_sizeof($graphs_to_act_on) . " graphs, and cleanup its poller cache\n";
			if($debug) {
				echo $message;
			}

			if(!$dryrun) {
				cacti_log(trim($message), FALSE, 'SYSTEM');
				api_data_source_remove_multi($data_sources_to_act_on);
				api_graph_remove_multi($graphs_to_act_on);
				api_device_remove($hostid);
			}
		}
	}
}

function api_grid_host_remove($host, $clusterid) {
	global $debug, $dryrun;
	if(!$dryrun)
		db_execute_prepared("DELETE FROM grid_hostinfo WHERE clusterid=? AND host=? AND isServer=1", array($clusterid, $host));
	if($debug)
		echo "Note: Removed host['$host'] of cluster['$clusterid'] information\n";
}

/*	display_help - displays the usage of the function */
function display_help () {
	global $config;
	echo "grid_purge_device.php " . read_config_option("grid_version") . "\n\n";
	echo "Purge obsoleted LSF server/client and associated graphs by options.\n";
	echo "Usage:\n\n";
	echo "grid_purge_device.php -a|--all [-t|--last-seen='YYYYMMDD'] [OPTIONS]\n";
	echo "grid_purge_device.php -c|--clusterid=CLUSTERID  [-t|--last-seen='YYYYMMDD']\n";
	echo "                      [OPTIONS]\n";
	echo "grid_purge_device.php -H|--host-id=HOSTID [--disableonly] [-d|--debug]\n";
	echo "grid_purge_device.php -h|--help|-v|-V|--version\n\n";
	echo "--a                    - Proceed all obsoleted LSF server[client] and associated\n";
	echo "--all                    graphs for all clusters.\n\n";
	echo "-t                     - Proceed with last seen of LSF Servers.\n";
	echo "--last-seen='YYYYMMDD'   NOTE: this option does not valid for LSF client\n\n";
	echo "-c                     - Proceed under specified CLUSTERID.\n";
	echo "--clusterid=CLUSTERID\n\n";
	echo "-H|--host-id=HOSTID    - Proceed device and associated graphs by HOSTID.\n\n";
	echo "OPTIONS:\n";
	echo "-C|--client            - Proceed LSF Client and associated graphs.\n";
	echo "-g|--graphonly         - Proceed device and associated graphs only, keep LSF\n";
	echo "                         server/client info.\n";
	echo "--disableonly          - Disable Obsoleted Devices Only.\n";
	echo "--dry-run              - Try and show progress only, does not modify database.\n";
	echo "-d|--debug             - Display verbose output during execution.\n";
	echo "-h|--help              - Display this help and exit.\n";
	echo "-v|-V|--version        - Output version information and exit.\n";
	exit;
}

/*	display_version - displays version info */
function display_version () {
	global $config;
	echo 'RTM Purge Obsoleted LSF Compute Node and Graphs ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	exit;
}
