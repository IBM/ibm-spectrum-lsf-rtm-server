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

$dir = dirname(__FILE__);
ini_set('max_execution_time', '0');

chdir($dir);

if (strpos($dir, 'grid') !== false) {
	chdir('../../');
}

include(dirname(__FILE__) . '/../../include/cli_check.php');
include_once($config['base_path'] . '/plugins/grid/include/grid_constants.php');
include_once($config['base_path'] . '/plugins/grid/lib/grid_functions.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/api_data_source.php');
include_once($config['base_path'] . '/lib/api_graph.php');
include_once($config['base_path'] . '/lib/snmp.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/api_device.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

if (cacti_sizeof($parms)) {
	/* setup defaults */
	$type                 = 1;

	/* host settings */
	$description          = '';
	$ip                   = '';
	$template_id          = db_fetch_cell("SELECT id FROM host_template WHERE hash='284bbabef4bb6e161af7e123c7c90969'");
	$notes                = '';

	/* snmp settings */
	$community            = '';
	$snmp_ver             = '0';
	$disable              = '0';
	$snmp_username        = '';
	$snmp_password        = '';
	$snmp_auth_protocol   = '';
	$snmp_priv_passphrase = '';
	$snmp_priv_protocol   = '';
	$snmp_context         = '';
	$snmp_port            = 161;
	$snmp_timeout         = 500;
	$max_oids             = 5;

	/* availability options */
	$avail                = '0';
	$ping_method          = 3;
	$ping_port            = 23;
	$ping_timeout         = 500;
	$ping_retries         = 2;

	/* settings for adding graphs */
	$queryid              = 0;
	$hostonly             = false;
	$hostgroup            = '';
	$hostmodel            = '';
	$hosttype             = '';
	$graphcheck           = true;
	$clustergraphs        = true;

	/* cluster settings */
	$cluster_name         = '';
	$cluster_poller       = 0;
	$cluster_lsf_envdir   = '';
	$cluster_lsfver       = '1017';
	$cluster_lim_port     = 7869;
	$cluster_masters      = '';
	$cluster_ips          = '';
	$cluster_ego          = 'N';
	$cluster_strict_chk   = '';
	$cluster_krb5         = 'N';
	$lsf_admin            = '';
	$admin_password       = '';
	$cluster_disable      = 0;

	/* frequencies and timeouts */
	$loadfreq             = 15;
	$loadto               = 60;
	$jobfreqminor         = 60;
	$hafreq               = 20;
	$jobfreqmajor         = 300;
	$jobfreqmax           = 300;
	$baseconnto           = 10;
	$batchconnto          = 10;
	$batchjobto           = 1;
	$batchjobrt           = 1;
	$basefreq             = 15;
	$baseto               = 60;
	$jobto                = 60;

	foreach($parms as $parameter) {
		if (strpos($parameter, '=')) {
			list($arg, $value) = explode('=', $parameter);
		} else {
			$arg = $parameter;
			$value = '';
		}

		switch ($arg) {
		case '--basefreq':
			if (preg_match('/(15|20|30|45|60|120|180|240)/', $value)) {
				$basefreq = $value;
			} else {
				echo "ERROR: Invalid Base Frequency for load, bhost, and bqueue information '$value'\n";
				exit -1;
			}

			break;
		case '--baseto':
			if (preg_match('/(15|20|30|45|60|120|180|240|300)/', $value)) {
				$baseto = $value;
			} else {
				echo "ERROR: Invalid Base Timeout for load, bhost, and bqueue information '$value'\n";
				exit -1;
			}

			break;
		case '--jobfreqminor':
			if (preg_match('/(15|20|30|45|60|120|180|240|300)/', $value)) {
				$jobfreqminor = $value;
			} else {
				echo "ERROR: Invalid Minor Job Frequency Value '$value'\n";
				exit -1;
			}

			break;
		case '--jobfreqmajor':
			if (preg_match('/(60|120|180|240|300|600|1200)/', $value)) {
				$jobfreqmajor = $value;
			} else {
				echo "ERROR: Invalid '$value'\n";
				exit -1;
			}

			break;
		case '--jobto':
			if (preg_match('/(15|20|30|45|60|120|240|300|600|900|1200)/', $value)) {
				$jobto = $value;
			} else {
				echo "ERROR: Invalid Maximum allowed runtime for jobs '$value'\n";
				exit -1;
			}

			break;
		case '--baseconnto':
			if (is_numeric($value) && $value > 0) {
				$baseconnto = $value;
			} else {
				echo "ERROR: The base connection timeout must be > 0\n";
				exit -1;
			}

			break;
		case '--batchconnto':
			if (is_numeric($value) && $value > 0) {
				$batchconnto = $value;
			} else {
				echo "ERROR: The batch connection timeout must be > 0\n";
				exit -1;
			}

			break;
		case '--batchjobto':
			if (is_numeric($value) && $value > 0) {
				$batchjobto = $value;
			} else {
				echo "ERROR: The jobs mbd timeout must be an integer > 0\n";
				exit -1;
			}

			break;
		case '--batchjobrt':
			if (is_numeric($value) && $value > 0) {
				$batchjobrt = $value;
			} else {
				echo "ERROR: The jobs mbd retries must be > 0\n";
				exit -1;
			}

			break;
		case '-d':
			$debug = TRUE;

			break;
		case '--type':
			$type = trim($value);

			break;

		/* cluster settings */
		case '--cluster_name':
		case '--cluster-name':
			$cluster_name = trim($value);

			break;
		case '--lim-port':
			$cluster_lim_port = trim($value);

			break;
		case '--lsf-version':
			$cluster_lsfver = trim($value);

			break;
		case '--lsf-ego':
			$cluster_ego = trim($value);

			break;
		case '--strict-chk':
			$cluster_strict_chk = trim($value);

			break;
		case '--krb5-auth':
			$cluster_krb5 = trim($value);

			break;
		case '--masters':
			$cluster_masters = trim($value);

			break;
		//case "--master-ips":
		//	$cluster_ips = trim($value);

		//	break;
		case '--cluster_poller':
		case '--pollerid':
			$cluster_poller = trim($value);

			break;
		case '--cluster_lsf_envdir':
		case '--cluster_env':
		case '--cluster-env':
			$cluster_lsf_envdir = trim($value);

			break;
		case '--clusterid':
			$clusterid = trim($value);

			break;

		case '--lsf-admin':
		case '--lsf_admin':
			$lsf_admin = trim($value);

			break;

		case '--admin-pass':
		case '--admin_pass':
			$admin_password = trim($value);
			break;

		case '--cluster-disable':
		case '--cluster_disable':
			$cluster_disable = trim($value);
			break;

		case '--graphcheck':
			$graphcheck = strtolower(trim($value));

			switch ($graphcheck) {
				case '0':
				case 'no':
				case 'false':
					$graphcheck = false;
					break;
				case '1':
				case 'yes':
				case 'true':
					$graphcheck = true;
					break;
				default:
					echo "ERROR: Invalid hostonly value: ($value)\n\n";
					display_help();
					exit(1);
			}

			break;
		case '--hostonly':
			$hostonly = strtolower(trim($value));

			switch ($hostonly) {
				case '0':
				case 'no':
				case 'false':
					$hostonly = false;
					break;
				case '1':
				case 'yes':
				case 'true':
					$hostonly = true;
					break;
				default:
					echo "ERROR: Invalid hostonly value: ($value)\n\n";
					display_help();
					exit(1);
			}
		case '--queryid':
			$queryid = trim($value);

			break;
		case '--hostgroup':
			$hostgroup = trim($value);

			break;
		case '--hostmodel':
			$hostmodel = trim($value);

			break;
		case '--hosttype':
			$hosttype = trim($value);

			break;
		case '--template':
			$template_id = $value;

			break;
		case '--community':
			$community = trim($value);

			break;
		case '--version':
			$snmp_ver = trim($value);

			break;
		case '--notes':
			$notes = trim($value);

			break;
		case '--disable':
			$disable  = $value;

			break;
		case '--username':
			$snmp_username = trim($value);

			break;
		case '--password':
			$snmp_password = trim($value);

			break;
		case '--authproto':
			$snmp_auth_protocol = trim($value);

			break;
		case '--privproto':
			$snmp_priv_protocol = trim($value);

			break;
		case '--privpass':
			$snmp_priv_passphrase = trim($value);

			break;
		case '--port':
			$snmp_port     = $value;

			break;
		case '--timeout':
			$snmp_timeout  = $value;

			break;
		case '--avail':
			switch($value) {
			case 'none':
				$avail = '0';

				break;
			case 'ping':
				$avail = '3';

				break;
			case 'snmp':
				$avail = '2';

				break;
			case 'pingsnmp':
				$avail = '1';

				break;
			default:
				echo "ERROR: Invalid Availability Parameter: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_method':
		case '--ping-method':
			switch(strtolower($value)) {
			case 'icmp':
				$ping_method = 1;

				break;
			case 'tcp':
				$ping_method = 3;

				break;
			case 'udp':
				$ping_method = 2;

				break;
			default:
				echo "ERROR: Invalid Ping Method: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_port':
		case '--ping-port':
			if (is_numeric($value) && ($value > 0)) {
				$ping_port = $value;
			} else {
				echo "ERROR: Invalid Ping Port: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--ping_retries':
		case '--ping-retries':
			if (is_numeric($value) && ($value > 0)) {
				$ping_retries = $value;
			} else {
				echo "ERROR: Invalid Ping Retries: ($value)\n\n";
				display_help();
				exit(1);
			}

			break;
		case '--disable':
			$disable  = $value;
			break;
		case '--username':
			$snmp_username = trim($value);
			break;
		case '--password':
			$snmp_password = trim($value);
			break;
		case '--avail':
			$avail = $value;
			break;
		case '--port':
			$snmp_port     = $value;
			break;
		case '--timeout':
			$snmp_timeout  = $value;
			break;
		case '-v':
		case '-V':
		case '-h':
		case '-H':
		case '--help':
			display_help();
			return 0;
		case '--list-clusters':
			display_clusters(get_clusters());
			return 0;
		case '--list-communities':
			displayCommunities();
			return 0;
		case '--list-host-groups':
			display_hostgroups();
			return 0;
		case '--list-host-models':
			display_hostmodels();
			return 0;
		case '--list-host-types':
			display_hosttypes();
			return 0;
		case '--list-host-templates':
			displayHostTemplates(getHostTemplates());
			return 0;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
			display_help();
			return 1;
		}
	}

	/* validate the disable state */
	if ($disable != 1 && $disable != 0) {
		echo "FATAL: Invalid disable flag ($disable)\n";
		return 1;
	}

	if ($disable == 0) {
		$disable = '';
	} else {
		$disable = 'on';
	}

	/* Add graphs or clusters */
	if ($type > 0) {
		/* process the various lists into validation arrays */
		$host_templates = getHostTemplates();
		$hosts          = getHosts();
		$addresses      = getAddresses();
		$clusters       = get_clusters();
		$cluster_hosts  = get_cluster_hosts($clusterid, $hostgroup, $hostmodel, $hosttype);

		/* process host template selected */
		if ($template_id < 0) {
			echo "FATAL: You must specify a host template ID\n";
			return 1;
		}

		/* process templates */
		if (!isset($host_templates[$template_id])) {
			echo "FATAL: Unknown host template id ($template_id)\n";
			return 1;
		}

		/* process clusters */
		if ((!isset($clusters[$clusterid])) && (strtolower($clusterid) != 'all')) {
			echo "FATAL; Unknown cluster id ($clusterid)\n";
			return 1;
		}

		/* process cluster hosts */
		if (!cacti_sizeof($cluster_hosts)) {
			echo "FATAL: No Cluster Hosts exist in for this clusterid\n";
			return 1;
		}

		/* process snmp information */
		if ($snmp_ver != "0" && $snmp_ver != "1" && $snmp_ver != "2" && $snmp_ver != "3") {
			echo "FATAL: Invalid snmp version ($snmp_ver)\n";
			return 1;
		} else {
			if ($snmp_port <= 0 || $snmp_port > 65535) {
				echo "FATAL: Invalid port.  Valid values are from 1-65535\n";
				return 1;
			}

			if ($snmp_timeout <= 0 || $snmp_timeout > 20000) {
				echo "FATAL: Invalid timeout.  Valid values are from 1 to 20000\n";
				return 1;
			}
		}

		/* community/user/password verification */
		if ($snmp_ver == "0" || $snmp_ver == "1" || $snmp_ver == "2") {
			/* snmp community can be blank */
		} else {
			if ($snmp_username == "" || $snmp_password == "") {
				echo "FATAL: When using snmpv3 you must supply an username and password\n";
				return 1;
			}
		}

		$php_bin   = read_config_option("path_php_binary");
		$path_grid = read_config_option("path_webroot") . "/plugins/grid";

		global $php_bin, $path_grid;

		if ($queryid == 0 && strtolower($queryid) != "all") {
			foreach ($cluster_hosts as $host) {
				$ip          = $host["host"];
				$description = $host["host"];
				$host_id     = 0;

				/* process ip */
				if (db_fetch_cell_prepared("SELECT count(*) FROM host WHERE clusterid=? AND hostname=?", array($host["clusterid"], $host["host"]))) {
					db_execute_prepared("update host set description = ? where id = ?", array($description, $addresses[$ip]));
					echo "NOTE: This IP already exists in the database ($ip) device-id: (" . $addresses[$ip] . ")\n";

					/* if we are not going to check graphs simply move on */
					if (!$graphcheck) continue;

					$host_id = db_fetch_cell_prepared("SELECT id
						FROM host
						WHERE hostname=?
						AND clusterid=?", array($ip, $host["clusterid"]));
				} else {
					echo "NOTE: Adding $description ($ip) as \"" . $host_templates[$template_id] . "\"\n";

					$host_id = api_add_griddevice_save($host_id, $template_id, $description, $ip,
							$community, $snmp_ver, $snmp_username, $snmp_password,
							$snmp_port, $snmp_timeout, $disable, $avail, $ping_method,
							$ping_port, $ping_timeout, $ping_retries, $notes,
							$snmp_auth_protocol, $snmp_priv_passphrase,
							$snmp_priv_protocol, $snmp_context, $max_oids, $host["clusterid"]);

					if (is_error_message()) {
						echo "WARNING: Failed to add this device\n";
						continue;
					} else {
						echo "NOTE: Success - new device-id: ($host_id)\n";
						echo "NOTE: Adding graphs for device-id: ($host_id)\n";
					}
				}

				if ($host_id) {
					if ($community != "") {
						/* UCD CPU Average */
						echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=4 --host-id=$host_id")) . "\n";
						/* UCD Load Average */
						echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=11 --host-id=$host_id")) . "\n";
						/* UCD Memory */
						echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=13 --host-id=$host_id")) . "\n";
					}

					/* Grid Job Stats */
					echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=44 --host-id=$host_id")) . "\n";
					/* Grid Load Average */
					echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=45 --host-id=$host_id")) . "\n";
					/* Grid IO Average */
					echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=46 --host-id=$host_id")) . "\n";
					/* Grid Memory */
					echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=47 --host-id=$host_id")) . "\n";
					/* Grid CPU */
					echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=48 --host-id=$host_id")) . "\n";
					/* Grid Memory with Reservation */
					/*echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=108 --host-id=$host_id")) . "\n";*/
					/* Grid Disk tmp and var space available */
					/*echo trim(shell_exec("$php_bin -q $path_grid/grid_add_graphs.php --graph-type=cg --graph-template-id=109 --host-id=$host_id")) . "\n";*/

					continue;
				}
			}

			if (strtolower($clusterid) != "all") {
				$clusters = get_clusters($clusterid);
			}

			/* build data query graphs */
			if (!$hostonly) {
				foreach($clusters as $cluster) {
					$check_cacti_host = db_fetch_cell_prepared("SELECT cacti_host FROM grid_clusters WHERE clusterid=?", array($cluster["clusterid"]));
					$stemplate_id     = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");

					if (empty($check_cacti_host)) {
						$host_value = addingsummaryhost(0, $stemplate_id, $cluster['clustername']."_Summary","localhost",
							$community, $snmp_ver, $snmp_username, $snmp_password,
							$snmp_port, $snmp_timeout, $disable, $avail, $ping_method,
							$ping_port, $ping_timeout, $ping_retries, $notes,
							$snmp_auth_protocol, $snmp_priv_passphrase,
							$snmp_priv_protocol, $snmp_context, $max_oids, $cluster["clusterid"],$host_templates[9]);
					}

					$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE id=?", array($cluster["cacti_host"]));

					if (!empty($host_id)){
						//$cluster_hostname = db_fetch_cell("SELECT lsf_master from grid_clusters where clusterid=".$cluster['clusterid']);
						//$cluster_cacti_id = db_fetch_cell("SELECT id from host where hostname='".$cluster_hostname."' AND clusterid=".$cluster['clusterid']);

						db_execute_prepared("UPDATE grid_clusters SET cacti_host=? WHERE clusterid=?", array($host_id, $cluster["clusterid"]));
					}

					$get_updated_clusters       = get_clusters($cluster['clusterid']);

					foreach ($get_updated_clusters as $updatedcluster){
						if ($updatedcluster["cacti_host"] > 0) {
							$host_id = db_fetch_cell_prepared("SELECT id FROM host WHERE id=?", array($updatedcluster["cacti_host"]));
							if (empty($host_id)) {
								echo "ERROR: Cacti Host for Clusterid " . $updatedcluster["clusterid"] . " does not exist\n";
								continue;
							}

							/* add cluster level graphs */
							add_cluster_graphs($host_id);

							/* add cluster data queries */
							add_cluster_data_queries($cluster, $queryid);
						}
					}
				}
			}
		}
	} else {
		if ($cluster_name == "") {
			echo "FATAL: You must specify a cluster name\n";
			return 1;
		}

		/* There are three ways to add a cluster
		 *
		 * The First method, for legacy support provide the following (creates server config):
		 * cluster_name       - The name of the cluster
		 * poller_id          - The grid poller id to handle the polling
		 * cluster_envdir     - The location of the LSF lsf.conf file
		 * cluster_krb5       - The Kerberos Auth status for this cluster
		 *
		 * The Second (and preferred way) is to provide the following information (creates server config):
		 * cluster_name       - The name of the cluster
		 * cluster_lsfver     - The lsfversion for this cluster
		 * cluster_masters    - The cluster master host list
		 * cluster_ips        - (Optional) The list of ips that are associated with the masters
		 * cluster_lim_port   - The LSF_LIMPORT for the cluster
		 * cluster_ego        - The EGO status for this cluster
		 * cluster_strict_chk - The status of strict checking of communiction for this cluster
		 * cluster_krb5       - The Kerberos Auth status for this cluster
		 *
		 * The Third method is to provide the following (maintains config):
		 * cluster_name       - The name of the cluster
		 * cluster_lsfver     - The lsfversion fo this cluster
		 * cluster_envdir     - The location of the LSF lsf.conf file
		 * cluster_krb5       - The Kerberos Auth status for this cluster
		 *
		 */
		$method = 0;
		if ($cluster_poller > 0) {
			if ($cluster_lsf_envdir == "") {
				echo "FATAL: You must specify the LSF_ENVDIR of the LSF cluster\n";
				return 1;
			} else if (!grid_check_lsf_envdir($cluster_lsf_envdir)) {
				echo 'FATAL: Unable to locate lsf.conf in '.$cluster_lsf_envdir."\n";
				return 1;
			}
			$method = 1;
		} else if ($cluster_lsf_envdir != "") {
			if (!grid_check_lsf_envdir($cluster_lsf_envdir)){
				echo "FATAL: Unable to locate lsf.conf in ".$cluster_lsf_envdir."\n";
				return 1;
			}
			$method = 3;
		} else {
			if ($cluster_masters == "") {
				echo "FATAL: You must specify a Master Host List\n";
				return 1;
			}

			if ($cluster_ips == "") {
				echo "NOTE: Assuming web server knows how to resolve Master Host list\n";
			}

			if ($cluster_lim_port <= 0) {
				echo "FATAL: The LSF LIM port must be numeric and greater than 0\n";
				return 1;
			}
			$method = 2;
		}

		if (!preg_match("/^(91|1010|1017|10010013|9.1|10.1|10.1.0.7|10.1.0.13)$/", $cluster_lsfver)) {
			echo "FATAL: The LSF Version is invalid '$cluster_lsfver'\n";
			exit -1;
		} else if(isset($rtmvermap[$cluster_lsfver])){
			$cluster_lsfver = $rtmvermap[$cluster_lsfver];
		}

		/* validate the disable state */
		if ($cluster_disable != 1 && $cluster_disable != 0) {
			echo "FATAL: Invalid disable flag ($cluster_disable)\n";
			return 1;
		}

		if ($cluster_disable == 0) {
			$cluster_disable = "";
		} else {
			$cluster_disable = "on";
		}

		/* see if the cluster already exists */
		$clusterid = db_fetch_cell_prepared("SELECT clusterid FROM grid_clusters WHERE clustername=?", array($cluster_name));

		if ($clusterid > 0) {
			echo "ERROR: You are attempting to re-add the cluster.  Can not continue\n";
			exit -1;
		} else {
			/* default naming */
			$save["clusterid"]           = "";
			$save["clustername"]         = $cluster_name;
			$save["cacti_host"]          = 0;
			$save["cacti_tree"]          = 0;
			$save["host_template_id"]    = $template_id;

			/* collection timing */
			$save["lim_timeout"]         = $baseconnto;
			$save["mbd_timeout"]         = $batchconnto;
			$save["mbd_job_timeout"]     = $batchjobto;
			$save["mbd_job_retries"]     = $batchjobrt;
			$save["collection_timing"]   = $basefreq;
			$save["max_nonjob_runtime"]  = $baseto;
			$save["job_minor_timing"]    = $jobfreqminor;
			$save["ha_timing"]           = $hafreq;
			$save["job_major_timing"]    = $jobfreqmajor;
			$save["max_job_runtime"]     = $jobto;
			$save["efficiency_queues"]   = "";
			$save["ip"]                  = $cluster_ips;
			$save["disabled"]            = "on"; // Disable the cluster until lsf.conf is in place

			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
				$save["communication"]            = "winrs";
			}

            if($lsf_admin != "") {
            	input_validate_input_regex($lsf_admin, "^[A-Za-z0-9\._\\\@\ -]+$");
               	$save["username"]   = $lsf_admin;
            }

			if($admin_password != "") {
               	$save["credential"] = $admin_password;
			}

			if ($method == 1 || $method == 3) {
				$save["lsf_envdir"]          = $cluster_lsf_envdir;
			}

			if ($method == 1) {
				$save["poller_id"]       = $cluster_poller;
			} else {
				$cluster_poller = db_fetch_cell_prepared("SELECT poller_id FROM grid_pollers WHERE lsf_version=?", array($cluster_lsfver));

				if ($cluster_poller > 0) {
					$save['poller_id']   = $cluster_poller;
				} else {
					echo "FATAL: Can not find the LSF Version for the Cluster\n";
					return 1;
				}
			}

			if (preg_match('/(0|N|No|NO)/', $cluster_krb5)) {
				$save['lsf_krb_auth'] = "";
			} else {
				$save['lsf_krb_auth'] = "on";
			}

			if ($method == 1 || $method == 3) {
				$save['lsf_master_hostname'] = grid_get_lsf_conf_variable_value($cluster_lsf_envdir, array('LSF_MASTER_LIST', 'LSF_SERVER_HOSTS'));
				$save['lsf_ego']	         = grid_get_lsf_conf_variable_value($cluster_lsf_envdir, 'LSF_ENABLE_EGO');
				$save['lim_port']	         = grid_get_lsf_conf_variable_value($cluster_lsf_envdir, 'LSF_LIM_PORT');
			} else {
				$save['lsf_master_hostname'] = $cluster_masters;
				$save['lim_port']            = $cluster_lim_port;

				if (preg_match('/(0|N|No|NO)/', $cluster_ego)) {
					$save['lsf_ego'] = "N";
				} else {
					$save['lsf_ego'] = "Y";
				}

				if ((strcasecmp($cluster_strict_chk, 'ENHANCED') == 0) || (strlen($cluster_strict_chk) == 0 && $cluster_lsfver == '10010013')) {
					$save['lsf_strict_checking'] = "ENHANCED";
				} else if (preg_match('/(1|Y|Yes|YES)/', $cluster_strict_chk)) {
					$save['lsf_strict_checking'] = "Y";
				} else {
					$save['lsf_strict_checking'] = "N";
				}
			}

			$cluster_lim_hostname = $save["lsf_master_hostname"];
			$token = strtok($cluster_lim_hostname, " ");
			$master = str_replace("\"", "", stripslashes($token));
			$save["lsf_master"] = simple_strip_host_domain($master);

			$lsf_confdir = grid_get_lsf_conf_from_lim($save);

			if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$lsf_confdir = str_replace("\\", "\\\\", $lsf_confdir);
			}

			if(!empty($lsf_confdir)) {
				$save["lsf_confdir"] = $lsf_confdir;
			} else {
				echo "Warning:  Unable to get LSF_CONFDIR.  Please check master hostname/port is correct and LIM is running.\n";
			}

			$host_id   = 0;
			$clusterid = 0;
			$clusterid = sql_save($save, "grid_clusters", "clusterid");

			if (!$clusterid) {
				echo "ERROR: Could not create cluster\n";
				exit -1;
			} else {
				if ($method < 3) {
					if(!grid_lsf_generate_conf($clusterid, $cluster_poller, $save['lsf_master_hostname'], $save['lim_port'], $save['lsf_ego'], (!isset($save['lsf_strict_checking']) || empty($save['lsf_strict_checking'])) ? 'N' : $save['lsf_strict_checking'], $save['lsf_krb_auth'])){
						echo "ERROR: Unable to generate conf file\n";
						db_execute_prepared('DELETE FROM grid_clusters WHERE clusterid=?', array($clusterid));
						exit -1;
					} else {
						echo "NOTE: conf file generated successfully\n";
					}

					/* generate ego.conf if we can */
					if(strcasecmp($save["lsf_ego"], 'Y') === 0 && grid_ego_generate_conf($clusterid, $cluster_poller, $save["lsf_ego"]) != 0) {
						echo "WARNING: Unable to initialize EGO.  EGO HA will not be functional\n";
					}
				} else {
					echo "NOTE: Using user entered conf file\n";
				}
			}

			// Enable cluster if $cluster_disable=0
			db_execute_prepared("UPDATE grid_clusters SET disabled=? WHERE clusterid=?", array($cluster_disable, $clusterid));
		}

		/* setup host and graph add frequencies */
		db_execute_prepared("UPDATE grid_clusters
			SET add_frequency='600', add_graph_frequency='86400'
			WHERE clusterid=?", array($clusterid));

		/* add the cacti host for the cluster */
		echo "NOTE: Successfully added $save[clustername] Cluster\n";

		/* give the cluster a chance to poll for the first time */
		echo "NOTE: Waiting 10 Seconds for Collection to Begin\n";
		sleep(10);

		/* get cluster level host template */
		unset($template_id);
		$template_id = db_fetch_cell("SELECT id FROM host_template WHERE hash='d8ff1374e732012338d9cd47b9da18d4'");

		if (isset($template_id)) {
			$description = $cluster_name . "_summary";
			$ip = "localhost";
			$community = "";
			$snmp_ver  = 0;
			$avail     = '0';

			$host_templates = getHostTemplates();

			echo "NOTE: Adding Cluster: $description ($ip) as \"" . $host_templates[$template_id] . "\"\n";

			$host_id = api_add_griddevice_save($host_id, $template_id, $description, $ip,
				$community, $snmp_ver, $snmp_username, $snmp_password,
				$snmp_port, $snmp_timeout, $disable, $avail, $ping_method,
				$ping_port, $ping_timeout, $ping_retries, $notes,
				$snmp_auth_protocol, $snmp_priv_passphrase,
				$snmp_priv_protocol, $snmp_context, $max_oids, $clusterid);

			if (is_error_message()) {
				echo "WARNING: Failed to add this device\n";
				exit -1;
			} else {
				echo "NOTE: Success - Cluster Device-id: ($host_id)\n";
				echo "NOTE: Adding Graphs for Cluster Device-id: ($host_id)\n";
			}

			/* update the cacti_host in the grid_clusters table */
			db_execute_prepared("UPDATE grid_clusters SET cacti_host=? WHERE clusterid=?", array($host_id, $clusterid));

			/* add cluster level graphs */
			add_cluster_graphs($host_id);
		}


	}
} else {
	display_help();

	return 0;
}

function add_cluster_graphs($host_id) {
	global $config, $php_bin;

	$php_bin   = read_config_option("path_php_binary");
	$path_web = read_config_option("path_webroot");

	/* get cluster templates */
	$templates = db_fetch_assoc("SELECT host_template.name AS host_template_name,
		graph_templates.name AS graph_template_name,
		host_template.id AS host_template_id,
		host_template_graph.graph_template_id AS host_template_graph_id
		FROM host_template_graph
		INNER JOIN host_template ON host_template_graph.host_template_id=host_template.id
		INNER JOIN graph_templates ON host_template_graph.graph_template_id=graph_templates.id
		WHERE host_template.hash='d8ff1374e732012338d9cd47b9da18d4'
		ORDER BY host_template.name");

	if (cacti_sizeof($templates)) {
		foreach($templates as $template) {
			if (substr_count($template["graph_template_name"], "Cluster/Host")) {
				$summary = " --input-fields=\"summary=yes\"";
			}else if (substr_count($template["graph_template_name"], "Cluster/Overall")) {
				$summary = " --input-fields=\"summary=no\"";
			} else {
				$summary = "";
			}

			/* workaround for a bug, see if graph is created already */
			$found = db_fetch_row_prepared("SELECT id
				FROM graph_local
				WHERE graph_template_id=?
				AND host_id=?
				LIMIT 1", array($template["host_template_graph_id"], $host_id));

			/* add the graph */
			if (!$found) {
				echo trim(shell_exec("$php_bin -q $path_web/cli/add_graphs.php --graph-type=cg --graph-template-id=" . $template['host_template_graph_id'] . " --host-id=" . $host_id . $summary));
				echo "\n";
			}
		}
	} else {
		echo "ERROR: No Templates found for Cluster Device Type\n";
		exit -1;
	}
}

function add_cluster_data_queries($cluster, $queryid) {
	global $php_bin, $config, $path_web;

	/* reindex the cluster hosts */
	if ($queryid > 0) {
		$query = " -qid=$queryid";
	} else {
		$query = "";
	}

	passthru("$php_bin -q " . read_config_option("path_webroot") . "/cli/poller_reindex_hosts.php -id=" . $cluster["cacti_host"] . " -qid=$queryid -d");

	if ($queryid == 0 || $queryid == 13 || strtolower($queryid) == "all") {
		/* Queue Job Statistics - Data Query ID 13, Graph Template ID 27, Query Field Name 'gridQname' */
		add_graphs_for_dq($cluster, 50, 13, 27, "gridQname");
		/* Queue Pending Times - Data Query ID 13, Graph Template ID 28, Query Field Name 'gridQname' */
		add_graphs_for_dq($cluster, 49, 13, 28, "gridQname");
	}

	if ($queryid == 0 || $queryid == 15 || strtolower($queryid) == "all") {
		/* HGroup CPU Capacity - Data Query ID 15 */
		add_graphs_for_dq($cluster, 81, 15, 33, "groupName");
		/* HGroup CPU Utilization - Data Query ID 15 */
		add_graphs_for_dq($cluster, 77, 15, 30, "groupName");
		/* HGroup Paging/IO Rate - Data Query ID 15 */
		add_graphs_for_dq($cluster, 78, 15, 31, "groupName");
		/* HGroup Slot Utilization - Data Query ID 15 */
		add_graphs_for_dq($cluster, 82, 15, 34, "groupName");
		/* HGroup Done Exited Jobs - Data Query ID 15 */
		add_graphs_for_dq($cluster, 91, 15, 39, "groupName");
	}

	if ($queryid == 0 || $queryid == 21 || strtolower($queryid) == "all") {
		/* License Project Efficiency - Data Query ID 21 */
		add_graphs_for_dq($cluster, 92, 21, 40, "projectLevel1");
		/* License Project Memory - Data Query ID 21 */
		add_graphs_for_dq($cluster, 94, 21, 41, "projectLevel1");
		/* License Project Pending - Data Query ID 21 */
		add_graphs_for_dq($cluster, 96, 21, 42, "projectLevel1");
		/* License Project Running - Data Query ID 21 */
		add_graphs_for_dq($cluster, 97, 21, 43, "projectLevel1");
		/* License Project Total CPU - Data Query ID 21 */
		add_graphs_for_dq($cluster, 93, 21, 44, "projectLevel1");
		/* License Project VM - Data Query ID 21 */
		add_graphs_for_dq($cluster, 95, 21, 45, "projectLevel1");
	}

	if ($queryid == 0 || $queryid == 22 || strtolower($queryid) == "all") {
		/* License Project Efficiency - Data Query ID 22 */
		/* License Project Memory - Data Query ID 22 */
		//add_graphs_for_dq($cluster, 98, 22, 46, "licenseProject");
		//add_graphs_for_dq($cluster, 99, 22, 47, "licenseProject");
		/* License Project Pending - Data Query ID 22 */
		//add_graphs_for_dq($cluster, 100, 22, 48, "licenseProject");
		/* License Project Running - Data Query ID 22 */
		//add_graphs_for_dq($cluster, 101, 22, 49, "licenseProject");
		/* License Project Total CPU - Data Query ID 22 */
		//add_graphs_for_dq($cluster, 102, 22, 50, "licenseProject");
		/* License Project VM - Data Query ID 22 */
		//add_graphs_for_dq($cluster, 103, 22, 51, "licenseProject");
	}

	if ($queryid == 0 || $queryid == 20 || strtolower($queryid) == "all") {
		/* Cluster Static Bool - Data Query ID 20, Graph Template ID 38 (CPU), Query Field Name 'resource_name' */
		//add_graphs_for_dq($cluster, 90, 20, 38, "resource_name", "(linux|solaris|batch|int|cs|od|dt)", "on");
	}

	if ($queryid == 0 || $queryid == 19 || strtolower($queryid) == "all") {
		/* Cluster Shared Resources - Data Query ID 19 */
		add_graphs_for_dq($cluster, 85, 19, 37, "resource_name");
	}

	if ($queryid == 0 || $queryid == 17 || strtolower($queryid) == "all") {
		/* User Group Information - Data Query ID 17 */
		add_graphs_for_dq($cluster, 83, 17, 35, "groupName");
	}

	if ($queryid == 0 || $queryid == 18 || strtolower($queryid) == "all") {
		/* User Stats - Data Query ID 18 */
		add_graphs_for_dq($cluster, 84, 18, 36, "user");
	}
}

function add_graphs_for_dq_ent($host, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = "", $include = "on") {
	global $config, $php_bin, $path_grid;

	$path_web = read_config_option("path_webroot");

	/* let's see what queries are defined for this host */
	$query_types = exec_into_array("$php_bin -q $path_grid/grid_add_graphs.php --host-id=" . $host["id"] . " --snmp-query-id=$snmp_query_id --list-query-types");
	$found = false;
	foreach($query_types as $type) {
	if (substr_count($type, $snmp_query_type_id)) {
		$found = true;
		break;
	}
	}

	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = exec_into_array("$php_bin -q $path_web/cli/add_graphs.php --host-id=" . $host["id"] . " --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name --list-snmp-values");

		if (cacti_sizeof($items)) {
		foreach($items as $item) {
			if ((trim($item) == "") ||
				(substr($item, 0, 5) == "Known") ||
				(substr($item, 0, 6) == "FATAL:") ||
				(substr($item, 0, 6) == "ERROR:")) {
				/* ignore */
				continue;
			} else {
				if ($regmatch == "") {
					/* add graph below */
				}else if ((($include == "on") && (preg_match("/$regmatch/", $item))) ||
					(($include != "on") && (!preg_match("/$regmatch/", $item)))) {
					/* add graph below */
				} else {
					echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $host["description"] . "\n";
					continue;
				}

				echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id and Host: " . $host["description"];
				$command = "$php_bin -q $path_web/cli/add_graphs.php" .
					" --graph-template-id=$graph_template_id --graph-type=ds"     .
					" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $host["id"] .
					" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
					" --snmp-value=$item";

				passthru($command);
			}
		}
		}
	} else {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Enterprise: " . $host["description"], TRUE, "GRID");
	}
}

function add_graphs_for_dq(&$cluster, $graph_template_id, $snmp_query_id, $snmp_query_type_id, $snmp_field_name, $regmatch = "", $include = "on") {
	global $config, $php_bin, $path_grid;

	/* let's see what queries are defined for this host */
	$query_types = exec_into_array("$php_bin -q $path_grid/grid_add_graphs.php --host-id=" . $cluster["cacti_host"] . " --snmp-query-id=$snmp_query_id --list-query-types");
	$found = false;
	foreach($query_types as $type) {
	if (substr_count($type, $snmp_query_type_id)) {
		$found = true;
		break;
	}
	}


	/* now let's create some graphs, otherwise log and error */
	if ($found) {
		$items = exec_into_array("$php_bin -q $path_grid/grid_add_graphs.php --host-id=" . $cluster["cacti_host"] . " --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name --list-snmp-values");

		if (cacti_sizeof($items)) {
		foreach($items as $item) {
			if ((trim($item) == "") ||
				(substr($item, 0, 5) == "Known") ||
				(substr($item, 0, 6) == "FATAL:") ||
				(substr($item, 0, 6) == "ERROR:")) {
				/* ignore */
				continue;
			} else {
				if ($regmatch == "") {
					/* add graph below */
				}else if ((($include == "on") && (preg_match("/$regmatch/", $item))) ||
					(($include != "on") && (!preg_match("/$regmatch/", $item)))) {
					/* add graph below */
				} else {
					echo "NOTE: Bypassig item due to Regex rule: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"] . "\n";
					continue;
				}

				echo "NOTE: Adding item: $item for Query Type ID: $snmp_query_type_id and Cluster: " . $cluster["clustername"];
				$command = "$php_bin -q $path_grid/grid_add_graphs.php" .
					" --graph-template-id=$graph_template_id --graph-type=ds"     .
					" --snmp-query-type-id=$snmp_query_type_id --host-id=" . $cluster["cacti_host"] .
					" --snmp-query-id=$snmp_query_id --snmp-field=$snmp_field_name" .
					" --snmp-value=$item";

				passthru($command);
			}
		}
		}
	} else {
		cacti_log("WARNING: Query Type ID ID: $snmp_query_type_id Not Assocated with Cluster: " . $cluster["clustername"], TRUE, "GRID");
	}
}

function get_clusters($clusterid = "") {
	$clusters = array();

	if (strtolower($clusterid) == "all") {
		$sql_where = "";
	}else if (strlen($clusterid)) {
		$sql_where = "WHERE clusterid=$clusterid";
	} else {
		$sql_where = "";
	}

	$tmpArray = db_fetch_assoc("SELECT *
		FROM grid_clusters
		$sql_where
		ORDER BY clusterid");

	foreach ($tmpArray as $cluster) {
		$clusters[$cluster["clusterid"]] = $cluster;
	}

	return $clusters;
}

function get_cluster_hosts($clusterid, $hostgroup = "", $hostmodel = "", $hosttype = "") {
	if (strtolower($clusterid) == "all") {
		if (!strlen($hostgroup) && !strlen($hostmodel) && !strlen($hosttype)) {
			return db_fetch_assoc("SELECT grid_hosts.* FROM grid_hosts
				INNER JOIN grid_clusters
				ON grid_hosts.clusterid=grid_clusters.clusterid");
		}else if (strlen($hostgroup)) {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostgroups
				ON grid_hosts.clusterid=grid_hostgroups.clusterid
				AND grid_hosts.host=grid_hostgroups.host
				INNER JOIN grid_clusters
				ON grid_hosts.clusterid=grid_clusters.clusterid
				WHERE grid_hostgroups.groupName=?", array($hostgroup));
		}else if (strlen($hostmodel)) {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostinfo
				ON grid_hosts.clusterid=grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				INNER JOIN grid_clusters
				ON grid_hosts.clusterid=grid_clusters.clusterid
				WHERE grid_hostinfo.hostModel=?", array($hostmodel));
		} else {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostinfo
				ON grid_hosts.clusterid=grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				INNER JOIN grid_clusters
				ON grid_hosts.clusterid=grid_clusters.clusterid
				WHERE grid_hostinfo.hostType=?", array($hosttype));
		}
	} else {
		if (!strlen($hostgroup) && !strlen($hostmodel) && !strlen($hosttype)) {
			return db_fetch_assoc_prepared("SELECT *
				FROM grid_hosts
				WHERE clusterid=?", array($clusterid));
		}else if (strlen($hostgroup)) {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostgroups
				ON grid_hosts.clusterid=grid_hostgroups.clusterid
				AND grid_hosts.host=grid_hostgroups.host
				WHERE grid_hostgroups.groupName=?
				AND grid_hosts.clusterid=?", array($hostgroup, $clusterid));
		}else if (strlen($hostmodel)) {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostinfo
				ON grid_hosts.clusterid=grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				WHERE grid_hostinfo.hostModel=?
				AND grid_hosts.clusterid=?", array($hostmodel, $clusterid));
		} else {
			return db_fetch_assoc_prepared("SELECT grid_hosts.*
				FROM grid_hosts
				INNER JOIN grid_hostinfo
				ON grid_hosts.clusterid=grid_hostinfo.clusterid
				AND grid_hosts.host=grid_hostinfo.host
				WHERE grid_hostinfo.hostType=?
				AND grid_hosts.clusterid=?", array($hosttype, $clusterid));
		}
	}
}

function display_clusters($clusters) {
	echo "Known Clusters:(clusterid, clustername)\n";

	if (cacti_sizeof($clusters)) {
		foreach($clusters as $cluster) {
			echo "\t" . $cluster["clusterid"] . "\t" . $cluster["clustername"] . "\n";
		}
	}
}

function display_hostgroups() {
	echo "Known Hostgroups:(groupName)\n";

	$groups = db_fetch_assoc("SELECT DISTINCT groupName
		FROM grid_hostgroups
		ORDER BY groupName");

	if (cacti_sizeof($groups)) {
		foreach($groups as $group) {
			echo "\t" . $group["groupName"] . "\n";
		}
	}
}

function display_hostmodels() {
	echo "Known Host Model:(hostModel)\n";

	$models = db_fetch_assoc("SELECT DISTINCT hostModel
		FROM grid_hostinfo
		WHERE hostModel NOT LIKE 'UN%'
		ORDER BY hostModel");

	if (cacti_sizeof($models)) {
		foreach($models as $model) {
			echo "\t" . $model["hostModel"] . "\n";
		}
	}
}

function display_hosttypes() {
	echo "Known Host Types:(hostType)\n";

	$types = db_fetch_assoc("SELECT DISTINCT hostType
		FROM grid_hostinfo
		WHERE hostType NOT LIKE 'UN%'
		ORDER BY hostType");

	if (cacti_sizeof($types)) {
		foreach($types as $type) {
			echo "\t" . $type["hostType"] . "\n";
		}
	}
}

function api_add_griddevice_save($id, $host_template_id, $description, $hostname, $snmp_community,
	$snmp_version, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disable,
	$availability_method, $ping_method, $ping_port, $ping_timeout, $ping_retries, $notes,
	$snmp_auth_protocol, $snmp_priv_passphrase,	$snmp_priv_protocol, $snmp_context,
	$max_oids, $clusterid) {

	/* initialize the array */
	$save = array();

	/* fetch some cache variables */
	if (empty($id)) {
		$_host_template_id = 0;
	} else {
		$_host_template_id = db_fetch_cell_prepared("select host_template_id from host where id=?", array($id));
	}

	$save["id"] = $id;
	$save["host_template_id"] = form_input_validate($host_template_id, "host_template_id", "^[0-9]+$", false, 3);
	$save["clusterid"] = form_input_validate($clusterid, "clusterid", "^[0-9]+$", false, 3);
	$save["description"] = form_input_validate($description, "description", "", false, 3);
	$save["hostname"] = form_input_validate(trim($hostname), "hostname", "", false, 3);
	$save["notes"] = form_input_validate($notes, "notes", "", true, 3);
	$save["snmp_version"] = form_input_validate($snmp_version, "snmp_version", "", true, 3);
	$save["snmp_community"] = form_input_validate($snmp_community, "snmp_community", "", true, 3);
	$save["snmp_username"] = form_input_validate($snmp_username, "snmp_username", "", true, 3);
	$save["snmp_password"] = form_input_validate($snmp_password, "snmp_password", "", true, 3);
	$save["snmp_auth_protocol"] = form_input_validate($snmp_auth_protocol, "snmp_auth_protocol", "", true, 3);
	$save["snmp_priv_passphrase"] = form_input_validate($snmp_priv_passphrase, "snmp_priv_passphrase", "", true, 3);
	$save["snmp_priv_protocol"] = form_input_validate($snmp_priv_protocol, "snmp_priv_protocol", "", true, 3);
	$save["snmp_context"] = form_input_validate($snmp_context, "snmp_context", "", true, 3);
	$save["snmp_port"] = form_input_validate($snmp_port, "snmp_port", "^[0-9]+$", false, 3);
	$save["snmp_timeout"] = form_input_validate($snmp_timeout, "snmp_timeout", "^[0-9]+$", false, 3);
	$save["disabled"] = form_input_validate($disable, "disabled", "", true, 3);
	$save["availability_method"] = form_input_validate($availability_method, "availability_method", "^[0-9]+$", true, 3);
	$save["ping_method"] = form_input_validate($ping_method, "ping_method", "^[0-9]+$", true, 3);
	$save["ping_port"] = form_input_validate($ping_port, "ping_port", "^[0-9]+$", true, 3);
	$save["ping_timeout"] = form_input_validate($ping_timeout, "ping_timeout", "^[0-9]+$", true, 3);
	$save["ping_retries"] = form_input_validate($ping_retries, "ping_retries", "^[0-9]+$", true, 3);
	$save["max_oids"] = form_input_validate($max_oids, "max_oids", "^[0-9]+$", true, 3);
	$save["monitor"] = "on";

	$save = api_plugin_hook_function('api_device_save', $save);

	$host_id = 0;

	if (!is_error_message()) {
		$host_id = sql_save($save, "host");

		if ($host_id) {
			raise_message(1);

			/* push out relavant fields to data sources using this host */
			push_out_host($host_id, 0);

			/* the host substitution cache is now stale; purge it */
			//kill_session_var("sess_host_cache_array");

			/* update title cache for graph and data source */
			update_data_source_title_cache_from_host($host_id);
			update_graph_title_cache_from_host($host_id);
		} else {
			raise_message(2);
		}

		/* if the user changes the host template, add each snmp query associated with it */
		if (($host_template_id != $_host_template_id) && (!empty($host_template_id))) {
			$snmp_queries = db_fetch_assoc_prepared("select snmp_query_id from host_template_snmp_query where host_template_id=?", array($host_template_id));

			if (cacti_sizeof($snmp_queries) > 0) {
			foreach ($snmp_queries as $snmp_query) {
				db_execute_prepared("replace into host_snmp_query (host_id,snmp_query_id,reindex_method) values (?, ?," . DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME . ")", array($host_id, $snmp_query["snmp_query_id"]));

				/* recache snmp data */
				run_data_query($host_id, $snmp_query["snmp_query_id"]);
			}
			}

			$graph_templates = db_fetch_assoc_prepared("select graph_template_id from host_template_graph where host_template_id=?", array($host_template_id));

			if (cacti_sizeof($graph_templates) > 0) {
			foreach ($graph_templates as $graph_template) {
				db_execute_prepared("replace into host_graph (host_id,graph_template_id) values (?, ?)", array($host_id, $graph_template["graph_template_id"]));
			}
			}
		}
	}

	return $host_id;
}
function set_grid_summary($host_id, $ans) {
	$records = db_fetch_assoc_prepared("SELECT data_template_data.local_data_id,
		data_template_data.name_cache,
		data_template_data.active,
		data_input.name as data_input_name,
		data_template.name as data_template_name,
		data_local.host_id FROM (data_local,data_template_data) LEFT JOIN
		data_input ON (data_input.id=data_template_data.data_input_id)
		LEFT JOIN data_template ON (data_local.data_template_id=data_template.id)
		WHERE data_local.id=data_template_data.local_data_id AND data_local.host_id = ?", array($host_id));


	foreach ($records as $rec) {
		$out = db_fetch_row_prepared("select * from data_template_data where local_data_id = ?", array($rec["local_data_id"]));
		$data_template_data_id = $out["id"];
		$data = db_fetch_row_prepared("select id,data_input_id,data_template_id,name,
				local_data_id from data_template_data
				where id=?", array($data_template_data_id));

		$fields = db_fetch_assoc_prepared("select * from data_input_fields where
				data_input_id=?
				and input_output='in' order by sequence", array($data["data_input_id"]));
		foreach ($fields as $field) {
			$data_input_data = db_fetch_row_prepared("select * from data_input_data
					where data_template_data_id=?
					and data_input_field_id=?", array($data["id"], $field["id"]));

			if (substr_count($field["name"], "Enter yes for grid summary")) {
				db_execute_prepared("UPDATE data_input_data set value=?
					where data_template_data_id=?
					and data_input_field_id=?",
					array($ans, $data["id"], $field["id"]));
			}
		}
	}
}

function addingsummaryhost($host_id, $template_id, $description, $ip,
	$community, $snmp_ver, $snmp_username, $snmp_password,
	$snmp_port, $snmp_timeout, $disable, $avail, $ping_method,
	$ping_port, $ping_timeout, $ping_retries, $notes,
	$snmp_auth_protocol, $snmp_priv_passphrase,
	$snmp_priv_protocol, $snmp_context, $max_oids, $clusterid,$host_templates){

	$host_id = api_add_griddevice_save($host_id, $template_id, $description, $ip, $community,
			$snmp_ver, $snmp_username, $snmp_password, $snmp_port, $snmp_timeout,
			$disable, $avail, $ping_method, $ping_port, $ping_timeout, $ping_retries,
			$notes, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context, $max_oids, $clusterid);

	echo "NOTE: Adding Cluster: $description as \"" . $host_templates . "\"\n";
	echo "NOTE: Adding graphs for device-id: ($host_id)\n";

	/* update the cacti_host in the grid_clusters table */
	db_execute_prepared("UPDATE grid_clusters SET cacti_host=? WHERE clusterid=?", array($host_id, $clusterid));
	/* add cluster level graphs */
	add_cluster_graphs($host_id);

	return $host_id;
}

function grid_check_lsf_envdir($lsf_envdir){

	$files = scandir($lsf_envdir);
	foreach ($files as $file){
		if ($file == 'lsf.conf')
			return true;
	}
	return false;
}

function grid_lsf_generate_conf($clusterid, $pollerid, $lsfmaster, $lsf_lim_port, $lsf_ego='N', $lsf_strict_checking='N', $lsf_krb_auth = ''){
	global $config, $rtm;

	$out = 0;

	$lsf_version   = db_fetch_cell_prepared('SELECT lsf_version FROM grid_pollers WHERE poller_id=?', array($pollerid));
	$advocate_port = read_config_option("advocate_port", True);
	$lsf_envdir    = $rtm["lsf".$lsf_version]["LSF_ENVDIR"].$clusterid;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://127.0.0.1:".$advocate_port."/hostSettings/lsfHosts");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST ,"false");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);

	switch($lsf_version){
		case '91':
		case '1010':
		case '1017':
		case '10010013':
			curl_setopt($ch, CURLOPT_POSTFIELDS, "ACTION=save&LSF_GET_CONF=lim&LSF_CONFDIR=".$lsf_envdir."&LSF_LIM_PORT=".$lsf_lim_port."&LSF_SERVER_HOSTS=".$lsfmaster."&LSF_VERSION=".$rtm["lsf".$lsf_version]["VERSION"]."&LSF_EGO_ENVDIR=".$lsf_envdir."&LSF_ENABLE_EGO=".$lsf_ego."&LSF_LOGDIR=".$lsf_envdir."&LSF_STRICT_CHECKING=".$lsf_strict_checking."&LSF_LOG_MASK=LOG_WARNING".($lsf_krb_auth != 'on' ? '' : '&LSB_KRB_LIB_PATH=/usr/lib64%20/usr/lib/x86_64-linux-gnu')."&LSF_GETCONF_MAX=1");
			break;
	}
	$out = curl_exec($ch);
	curl_close($ch);

	if ($out === false){
		echo "ERROR: Unable to generate lsf.conf.  Check that the advocate server is running.\n";
		db_execute_prepared('DELETE FROM grid_clusters WHERE clusterid=?', array($clusterid));
		exit -1;
	}

	$save['clusterid']  = $clusterid;
	$save['lsf_envdir'] = $lsf_envdir;
	if ($lsf_ego == '') {
		$save['lsf_ego'] = 'N';
	}

	return (sql_save($save, 'grid_clusters', 'clusterid'));
}

function grid_get_lsf_conf_from_lim($save) {
	global $config, $rtm;

	$lsf_confdir = getlsfconf($save["lsf_master"], $save["lim_port"], $save["lsf_ego"], $save['lsf_strict_checking'], $save["poller_id"]);

	return $lsf_confdir;
}

function grid_ego_generate_conf($clusterid, $pollerid, $lsf_ego) {
	global $config, $rtm;

	/* ego is not enabled, dont generate conf */
	if($lsf_ego == 'N') {
		return -1;
	}

	$pollerinfo = db_fetch_row_prepared("SELECT poller_lbindir, lsf_version FROM grid_pollers WHERE poller_id=?", array($pollerid));
	$lsf_envdir = db_fetch_cell_prepared("SELECT lsf_envdir FROM grid_clusters WHERE clusterid=?", array($clusterid));

	/* ego is valid since 7.x clusters, and disabled by default since 10.1 cluster */
	if($pollerinfo["lsf_version"] < 700 || $pollerinfo["lsf_version"] > 1010) {

		return -1;
	}

	$advocate_key = session_auth();

	/* set required parameters */
	$target[0]["name"] = "";
	$target[0]["clusterid"] = $clusterid;
	$target[0]["RTM_POLLER_BINDIR"] = $pollerinfo["poller_lbindir"];
	$target[0]["LSF_ENVDIR"] = $lsf_envdir;
	$target[0]["LSF_SERVERDIR"] = "";

	$json_ha_info = array("key"=>$advocate_key,
						  "action" => "init",
						  "target" => $target);

	$output = json_encode($json_ha_info);

	$curl_output = exec_curl("egohainit", $output);

	$content_response = $curl_output['content']; //return response from advocate in json format
	$json_decode_content_response = json_decode($content_response,true);
	$rsp_content = $json_decode_content_response["rsp"];

	return $rsp_content[0]["status_code"];
}

function display_help() {
	echo 'RTM Add Devices Utility ' . read_config_option('grid_version') . "\n";
	echo html_entity_decode('&#169;',ENT_NOQUOTES,'UTF-8') . ' Copyright International Business Machines Corp, ' . read_config_option('grid_copyright_year') . ".\n\n";
	echo "This program allows you to manage the addition of not only clusters into RTM\n";
	echo "but also hosts, and graphs for both clusters and hosts\n\n";
	echo "In it's current form, for Cluster level graphs, it will add anything associated with either the\n";
	echo "Host Template named 'GRID Summary' or 'LSF Cluster'.  For Host Level Graphs, you must specify a template\n";
	echo "to use.  The Grid Add cluster program will also add Data Query based graphs.  However, at this time, if\n";
	echo "you develop new Data Queries, you will have to hand edit this file and become familiar with it's syntax\n";
	echo "until such time as a Web based GUI is developed to monitor and control this process.\n\n";
	echo "Usage:\n";
	echo "Adding a Cluster:\n";
	echo "   Method 1 - Reads LSF Configuration from file and creates a Server Config\n\n";
	echo "   grid_add_cluster.php --type=0 --pollerid=n --cluster-name=clustername --cluster-env=path [--krb5-auth=Y]\n";
	echo "     [cluster options]\n\n";
	echo "   Method 2 - Takes all LSF Configuration information from command line and creates a Server Config\n\n";
	echo "   grid_add_cluster.php --type=0 --cluster-name=clustername --masters='master hosts' --lsf-version=10.1.0.7\n";
	echo "      --lim-port=7869\n";
	echo "     [cluster options]\n\n";
	echo "   Method 3 - Reads LSF Configuration from file and maintains that file pointer in the database\n\n";
	echo "   grid_add_cluster.php --type=0 --cluster-name=clustername --lsf-version=10.1.0.7 --cluster-env=path [--krb5-auth=Y]\n";
	echo "     [cluster options]\n\n";
	echo "Adding Cluster Hosts and Cluster Data Queries:\n";
	echo "   grid_add_cluster.php --type=1 --clusterid=[ID] --template=[ID] [--disable]\n";
	echo "     [--graphcheck=true] [--hostonly=false] [--queryid=all] [--hostgroup=]\n";
	echo "     [--hostmodel=] [--hosttype=]\n";
	echo "     [host options]\n\n";
	echo "Required and Optional for Clusters\n";
	echo "    --type         0 to add a new cluster, 1 to add devices and graphs to an existing cluster\n";
	echo "    --pollerid     The id of the poller to use for polling this cluster\n";
	echo "    --cluster-name The name for this cluster\n";
	echo "    --cluster-env  The LSF_ENVDIR for this cluster\n";
	echo "    --masters      HOSTLIST, A list of hosts, separated by a space that are the LSF Master Cantidates\n";
	//echo "    --master-ips   IPLIST, A list of IP Addresses, separated by a space that correspond to the LSF Master Cantidates\n";
	echo "    --lsf-version  10.1.0.7, 9.1|10.1|10.1.0.7|10.1.0.13 The version of the LSF Cluster\n";
	echo "    --lim-port     7869, The LSF Clusters LIM Port\n";
	//echo "    --lsf-ego      N, Y|N|0|1|Yes|No, The LSF EGO Status (for LSF 701 and above)\n";
	echo "    --strict-chk   N, Y|N|0|1|Yes|No|Enhanced, The Status of LSF strict checking of communications (for LSF 701 and above)\n";
	echo "    --krb5-auth    N, Y|N|0|1|Yes|No, The LSF Kerberos Status (for LSF 10.1.0.11 and above)\n";
	echo "    --lsf-admin    The LSF Admin for performing cluster operations\n";
	echo "    --admin-pass   The LSF Admin password for performing cluster operations\n";
	echo "    --cluster-disable  1 to add this cluster but to disable data collecting";
	echo "Required for Hosts and Data Queries:\n";
	echo "    --type         0 to add a new cluster, 1 to add devices and graphs to an exsisting cluster\n";
	echo "    --clusterid    the clusterid for the cluster that this device belongs to\n";
	echo "    --template     number (read below to get a list of templates)\n\n";
	echo "Optional for Clusters:\n";
	echo "    --basefreq     15, 15|20|30|45|60|120|180|240 Specifies the lsload, bhosts, bqueues collection frequency\n";
	echo "    --baseto       60, 15|20|30|45|60|120|180|240|300 Specified the max allow runtime for load\n";
	echo "    --jobfreqminor 30, 15|20|30|45|60|120|180|240|300 How often to collect job information\n";
	echo "    --jobfreqmajor 300, 60|120|180|240|300|600|1200 How often to refresh job reference information\n";
	echo "    --jobto        300, 15|20|30|45|60|120|240|300|600|900|1200 Maximum allowed runtime\n";
	echo "    --baseconnto   10, The amount of time to wait for a LIM connection\n";
	echo "    --batchconnto  10, The amount of time to wait for a Batch connection\n";
	echo "    --batchjobto   1, The number of minutes to attempt to reach mbd if unresponsive\n";
	echo "    --batchjobrt   1, The number of times to retry to attempt to connect to mbd\n\n";
	echo "Optional for Hosts and Data Queries:\n";
	echo "    --graphcheck   'true', 'true'|'yes'|'1'|'false'|'no'|'0' if true and a host already exists\n";
	echo "                   collector will verify that all graphs are either created or not.  When false\n";
	echo "                   if a host is found in Cacti already, no graph checking is performed.\n";
	echo "    --hostonly     'false', 'true'|'yes'|'1'|'false'|'no'|'0' hosts only ignore data queries\n";
	echo "                   option mutually exclusive to '--queryid='\n";
	echo "    --queryid      only updates data queries.  Either 'all' or a data query id.\n\n";
	echo "Optional for Hosts:\n";
	echo "    --hostgroup    only hosts from this hostgroups are added\n";
	echo "    --hostmodel    only hosts from this hostmodel are added\n";
	echo "    --hosttype     only hosts from this hosttype are added\n";
	echo "    --notes        '', General information about this host.  Must be enclosed using double quotes.\n";
	echo "    --disable      0, 1 to add this host but to disable checks and 0 to enable it\n";
	echo "    --avail        none, [none][none, snmp, pingsnmp]\n";
	echo "    --ping-method  tcp, icmp|tcp|udp\n";
	echo "    --ping-port    '', 1-65534\n";
	echo "    --ping-retries 2, the number of time to attempt to communicate with a host\n";
	echo "    --version      1, 1|2|3, snmp version\n";
	echo "    --community    '', snmp community string for snmpv1 and snmpv2.  Leave blank for no community\n";
	echo "    --port         161\n";
	echo "    --timeout      500\n";
	echo "    --username     '', snmp username for snmpv3\n";
	echo "    --password     '', snmp password for snmpv3\n";
	echo "    --authproto    '', snmp authentication protocol for snmpv3\n";
	echo "    --privpass     '', snmp privacy passphrase for snmpv3\n";
	echo "    --privproto    '', snmp privacy protocol for snmpv3\n";
	echo "    --context      '', snmp context for snmpv3\n";
	echo "    --max-oids     10, 1-60, the number of OID's that can be obtained in a single SNMP Get request\n\n";
	echo "List Options:\n";
	echo "    --list-clusters\n";
	echo "    --list-host-groups\n";
	echo "    --list-host-models\n";
	echo "    --list-host-types\n";
	echo "    --list-host-templates\n";
	echo "    --list-communities\n\n";
}

